// IMathAS question-control language: a PHP-flavoured language with IMathAS macros.
// This is a port of the CM5 "clike"-based imathas mode (imathas.js), rewritten as a
// standalone StreamLanguage tokenizer since CM6's legacy-modes do not export the
// clike factory.  Token names are the CM5 ones; see tags.js for the tag mapping.
import { StreamLanguage, LanguageSupport } from "@codemirror/language";
import { imathasTokenTable } from "./tags.js";
import {
  imathasKeywords, imathasAtoms, imathasBuiltin,
  imathasSpecialVars, imathasDisallowedVars, keywords
} from "./imathas-words.js";

const kw = keywords(imathasKeywords);
const atoms = keywords(imathasAtoms);
const builtin = keywords(imathasBuiltin);

export const imathasWordList = [imathasKeywords, imathasAtoms, imathasBuiltin].join(" ").split(" ");

/* ---- string handling (ported from the CM5 php mode) ---- */

// Helper for phpString
function matchSequence(list, end, escapes) {
  if (list.length === 0) return phpString(end);
  return function (stream, state) {
    const patterns = list[0];
    for (let i = 0; i < patterns.length; i++) {
      if (stream.match(patterns[i][0])) {
        state.tokenize = matchSequence(list.slice(1), end);
        return patterns[i][1];
      }
    }
    state.tokenize = phpString(end, escapes);
    return "string";
  };
}

function phpString(closing, escapes) {
  return function (stream, state) { return phpString_(stream, state, closing, escapes); };
}

function phpString_(stream, state, closing, escapes) {
  // "Complex" syntax
  if ((escapes !== false && stream.match("${", false)) || stream.match("{$", false)) {
    state.tokenize = null;
    return "string";
  }

  // Simple syntax
  if (escapes !== false && stream.match(/^\$[a-zA-Z_][a-zA-Z0-9_]*/)) {
    // After the variable name there may appear array or object operator.
    if (stream.match("[", false)) {
      state.tokenize = matchSequence([
        [["[", null]],
        [[/\d[\w\.]*/, "number"],
         [/\$[a-zA-Z_][a-zA-Z0-9_]*/, "var"],
         [/[\w\$]+/, "variable"]],
        [["]", null]]
      ], closing, escapes);
    }
    if (stream.match(/\-\>\w/, false)) {
      state.tokenize = matchSequence([
        [["->", null]],
        [[/[\w]+/, "variable"]]
      ], closing, escapes);
    }
    return "var";
  }

  let escaped = false;
  // Normal string
  while (!stream.eol() &&
         (escaped || escapes === false ||
          (!stream.match("{$", false) &&
           !stream.match(/^(\$[a-zA-Z_][a-zA-Z0-9_]*|\$\{)/, false)))) {
    if (!escaped && stream.match(closing)) {
      state.tokenize = null;
      state.tokStack.pop(); state.tokStack.pop();
      break;
    }
    escaped = stream.next() === "\\" && !escaped;
  }
  return "string";
}

function tokenSingleQuote(stream, state) {
  let escaped = false, next;
  while ((next = stream.next()) != null) {
    if (next === "'" && !escaped) { state.tokenize = null; break; }
    escaped = !escaped && next === "\\";
  }
  return "string"; // multi-line strings allowed: tokenize stays set until closing quote
}

function tokenComment(stream, state) {
  let maybeEnd = false, ch;
  while ((ch = stream.next()) != null) {
    if (ch === "/" && maybeEnd) { state.tokenize = null; break; }
    maybeEnd = (ch === "*");
  }
  return "comment";
}

/* ---- main tokenizer ---- */

const closers = { ")": "(", "]": "[", "}": "{" };

function token(stream, state) {
  if (state.tokenize) return state.tokenize(stream, state);
  if (stream.eatSpace()) return null;

  const ch = stream.next();

  if (ch === "$") {
    stream.eatWhile(/[\w\$_]/);
    const cur = stream.current();
    if (Object.prototype.hasOwnProperty.call(imathasSpecialVars, cur)) return "specialvar";
    if (Object.prototype.hasOwnProperty.call(imathasDisallowedVars, cur)) return "badvar";
    return "var";
  }

  if (ch === '"') {
    state.tokStack.push('"', 0);
    state.tokenize = phpString('"');
    return "string";
  }

  if (ch === "'") {
    state.tokenize = tokenSingleQuote;
    return tokenSingleQuote(stream, state);
  }

  if (ch === "#") {
    stream.skipToEnd();
    return "comment";
  }

  if (ch === "/") {
    if (stream.eat("/")) { stream.skipToEnd(); return "comment"; }
    if (stream.eat("*")) { state.tokenize = tokenComment; return tokenComment(stream, state); }
  }

  if (ch === "<") {
    // heredoc / nowdoc
    const before = stream.match(/<<\s*/);
    if (before) {
      const quoted = stream.eat(/['"]/);
      stream.eatWhile(/[\w\.]/);
      const delim = stream.current().slice(before[0].length + (quoted ? 2 : 1));
      if (quoted) stream.eat(quoted);
      if (delim) {
        state.tokStack.push(delim, 0);
        state.tokenize = phpString(delim, quoted !== "'");
        return "string";
      }
    }
  }

  if (/[\[\]{}\(\),;:]/.test(ch)) {
    if (ch === "{") {
      // inside a "{$var}" interpolation, count braces so we know when the string resumes
      if (state.tokStack.length) state.tokStack[state.tokStack.length - 1]++;
      state.stack.push("{");
    } else if (ch === "(" || ch === "[") {
      state.stack.push(ch);
    } else if (ch === "}") {
      if (state.tokStack.length > 0 && !--state.tokStack[state.tokStack.length - 1]) {
        state.tokenize = phpString(state.tokStack[state.tokStack.length - 2]);
      }
      if (state.stack.length && state.stack[state.stack.length - 1] === "{") state.stack.pop();
    } else if (ch === ")" || ch === "]") {
      if (state.stack.length && state.stack[state.stack.length - 1] === closers[ch]) state.stack.pop();
    }
    return null;
  }

  if (/\d/.test(ch) || (ch === "." && stream.match(/^\d/, false))) {
    stream.eatWhile(/[\w\.]/);
    return "number";
  }

  if (/[+\-*&%=<>!?|\/:^.~@]/.test(ch)) {
    while (!stream.match(/^\/[\/*]/, false) && stream.eat(/[+\-*&%=<>!?|\/:^.~@]/)) { /* eat operator run */ }
    return "operator";
  }

  stream.eatWhile(/[\w_\xa1-￿]/);
  const cur = stream.current();
  if (Object.prototype.hasOwnProperty.call(kw, cur)) return "keyword";
  if (Object.prototype.hasOwnProperty.call(atoms, cur)) return "atom";
  if (Object.prototype.hasOwnProperty.call(builtin, cur)) return "builtin";
  return "variable";
}

export const imathasStreamParser = {
  name: "imathas",
  startState() {
    return { tokenize: null, tokStack: [], stack: [] };
  },
  copyState(s) {
    return { tokenize: s.tokenize, tokStack: s.tokStack.slice(), stack: s.stack.slice() };
  },
  token,
  // Statements are not indented relative to each other; only open brackets add a level.
  // (The CM5 mode did the same via its clike indent hook.)
  indent(state, textAfter, cx) {
    if (state.tokenize) return null; // inside a multi-line string or comment
    let depth = state.stack.length;
    if (/^[\]\)\}]/.test(textAfter)) depth--;
    return Math.max(0, depth) * cx.unit;
  },
  tokenTable: imathasTokenTable,
  languageData: {
    commentTokens: { line: "//", block: { open: "/*", close: "*/" } },
    indentOnInput: /^\s*[\}\]\)]$/,
    closeBrackets: { brackets: ["(", "[", "{", "'", '"'] }
  }
};

export const imathasLanguage = StreamLanguage.define(imathasStreamParser);

export function imathas() {
  return new LanguageSupport(imathasLanguage);
}

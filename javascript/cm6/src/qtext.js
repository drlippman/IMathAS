// Question-text language: HTML with IMathAS $variable / {$variable[...]} highlighting.
import { html } from "@codemirror/lang-html";
import { ViewPlugin, Decoration, MatchDecorator, EditorView } from "@codemirror/view";
import { Prec } from "@codemirror/state";

const ok = Decoration.mark({ class: "cm-ivar" });
const bad = Decoration.mark({ class: "cm-ivar-err" });

const validBraced = /^\{\$[a-zA-Z_][\w$]*(?:\[[\w$]*\])*\}$/;

// {$name...}  (anything up to the closing brace, so malformed ones can be flagged)
// or $name followed by optional [index] parts
const matcher = new MatchDecorator({
  regexp: /\{\$[a-zA-Z_][^}\n]*\}?|\$[a-zA-Z_]\w*(?:\[[\w$]*\])*/g,
  decoration: m => (m[0][0] === "{" && !validBraced.test(m[0]) ? bad : ok)
});

const variableHighlight = ViewPlugin.fromClass(class {
  constructor(view) { this.decorations = matcher.createDeco(view); }
  update(u) { this.decorations = matcher.updateDeco(u, this.decorations); }
}, { decorations: v => v.decorations });

// lang-html's autoCloseTags doesn't close <script>/<style> (likely because the parser treats
// an unclosed one as running to the end of the document), so handle those two ourselves.
const closeRawTags = Prec.high(EditorView.inputHandler.of((view, from, to, text) => {
  if (text !== ">" || view.state.readOnly) return false;
  const line = view.state.doc.lineAt(from);
  const m = /<(script|style)\b[^<>]*$/i.exec(line.text.slice(0, from - line.from));
  if (!m) return false;
  // already has its closing tag right after the cursor
  if (new RegExp("^\\s*</" + m[1], "i").test(view.state.doc.sliceString(to, to + 20))) return false;
  view.dispatch({
    changes: { from, to, insert: "></" + m[1] + ">" },
    selection: { anchor: from + 1 },
    userEvent: "input.type"
  });
  return true;
}));

// autoCloseTags: typing ">" after <p inserts </p>; typing "</" completes the closing tag
export function qtext(autoCloseTags) {
  return [
    html({ autoCloseTags: !!autoCloseTags, matchClosingTags: false }),
    variableHighlight,
    autoCloseTags ? closeRawTags : []
  ];
}

// Small custom extensions: tag matching, selected-text styling, click-to-focus,
// $answer line highlight.
import { StateField, StateEffect, EditorState } from "@codemirror/state";
import { EditorView, ViewPlugin, Decoration } from "@codemirror/view";
import { syntaxTree, matchBrackets, indentService } from "@codemirror/language";
import { Prec } from "@codemirror/state";

/* ---- never re-indent the contents of <pre> (whitespace there is significant) ---- */

function inPre(state, pos) {
  for (let n = syntaxTree(state).resolveInner(pos, 0); n; n = n.parent) {
    if (n.name !== "Element") continue;
    const open = n.firstChild;
    if (!open || open.name !== "OpenTag") continue;
    const tag = open.getChild("TagName");
    if (!tag || state.sliceDoc(tag.from, tag.to).toLowerCase() !== "pre") continue;
    const close = n.lastChild && n.lastChild.name === "CloseTag" ? n.lastChild : null;
    if (pos > open.to && pos <= (close ? close.from : n.to)) return true;
  }
  return false;
}

// Lines with content keep their indentation; a fresh line (Enter) copies the line above,
// which is what the CM5 HTML mode did for <pre>.
export const preserveInPre = Prec.high(indentService.of((cx, pos) => {
  if (!inPre(cx.state, pos)) return undefined;
  if (/\S/.test(cx.lineAt(pos).text)) return cx.lineIndent(pos);
  return cx.lineIndent(cx.state.doc.lineAt(pos).from);
}));

/* ---- bracket matching only inside embedded JavaScript (<script> blocks) ---- */

const bracketOk = Decoration.mark({ class: "cm-matchingBracket" });
const bracketBad = Decoration.mark({ class: "cm-nonmatchingBracket" });

// The embedded JS tree's top node is named "Script".  An empty <script></script> has no
// JS tree yet, so also accept a position between the open and close tags of a script element.
function inScript(state, pos) {
  const tree = syntaxTree(state);
  for (const side of [-1, 1]) {
    for (let n = tree.resolveInner(pos, side); n; n = n.parent) {
      if (n.name === "Script") return true;
    }
  }
  for (let n = tree.resolveInner(pos, 0); n; n = n.parent) {
    if (n.name !== "Element" || !n.firstChild || n.firstChild.name !== "OpenTag") continue;
    const tag = n.firstChild.getChild("TagName");
    if (!tag || state.sliceDoc(tag.from, tag.to).toLowerCase() !== "script") continue;
    const close = n.lastChild && n.lastChild.name === "CloseTag" ? n.lastChild : null;
    if (pos >= n.firstChild.to && pos <= (close ? close.from : n.to)) return true;
  }
  return false;
}

// Make closeBrackets() (which reads "closeBrackets" language data at the cursor) do nothing
// outside <script> blocks: first provider wins, and inside scripts we defer to the JS language.
export const closeBracketsOnlyInScript = Prec.highest(EditorState.languageData.of(
  (state, pos) => (inScript(state, pos) ? [] : [{ closeBrackets: { brackets: [] } }])
));

function scriptBrackets(state) {
  const deco = [];
  for (const r of state.selection.ranges) {
    if (!r.empty) continue;
    const h = r.head;
    const m = matchBrackets(state, h, -1) ||
      (h > 0 && matchBrackets(state, h - 1, 1)) ||
      matchBrackets(state, h, 1) ||
      (h < state.doc.length && matchBrackets(state, h + 1, -1));
    if (!m || !inScript(state, m.start.from)) continue;
    const mark = m.matched ? bracketOk : bracketBad;
    deco.push(mark.range(m.start.from, m.start.to));
    if (m.end) deco.push(mark.range(m.end.from, m.end.to));
  }
  return Decoration.set(deco, true);
}

export const scriptBracketMatching = StateField.define({
  create: scriptBrackets,
  update(value, tr) {
    return (tr.docChanged || tr.selection || syntaxTree(tr.state) !== syntaxTree(tr.startState))
      ? scriptBrackets(tr.state) : value;
  },
  provide: f => EditorView.decorations.from(f)
});

/* ---- highlight the open/close tag pair when the cursor is in a tag ---- */

const tagMark = Decoration.mark({ class: "cm-matchingtag" });
const TAG_NODES = { OpenTag: 1, CloseTag: 1, SelfClosingTag: 1, MismatchedCloseTag: 1 };

function matchingTags(state) {
  const tree = syntaxTree(state);
  const head = state.selection.main.head;
  // side 0: only nodes that strictly contain the cursor, so being merely adjacent to a tag
  // (e.g. typing text right before </li>) doesn't count as being "in" it
  let n = tree.resolveInner(head, 0);
  while (n && !TAG_NODES[n.name]) n = n.parent;
  const el = n && n.parent;
  if (el && el.name === "Element") {
    const open = el.firstChild, close = el.lastChild;
    if (open && close && open !== close && open.name === "OpenTag" && close.name === "CloseTag") {
      return Decoration.set([tagMark.range(open.from, open.to), tagMark.range(close.from, close.to)]);
    }
  }
  return Decoration.none;
}

export const matchTags = StateField.define({
  create: matchingTags,
  update(value, tr) {
    return (tr.docChanged || tr.selection || syntaxTree(tr.state) !== syntaxTree(tr.startState))
      ? matchingTags(tr.state) : value;
  },
  provide: f => EditorView.decorations.from(f)
});

/* ---- class on selected text (white-on-blue look, see CSS) ---- */

const selMark = Decoration.mark({ class: "cm-selected-text" });

export const selectedText = ViewPlugin.fromClass(class {
  constructor(view) { this.decorations = this.build(view); }
  update(u) {
    if (u.selectionSet || u.docChanged || u.viewportChanged) this.decorations = this.build(u.view);
  }
  build(view) {
    const r = [];
    for (const s of view.state.selection.ranges) if (!s.empty) r.push(selMark.range(s.from, s.to));
    return Decoration.set(r, true);
  }
}, { decorations: v => v.decorations });

/* ---- clicking blank space inside the editor box focuses the editor ---- */
/*
export const clickToFocus = ViewPlugin.define(view => {
  const scroller = view.scrollDOM;
  const onDown = e => {
    if (e.button !== 0) return;
    if (e.target !== scroller && e.target !== view.contentDOM) return; // only blank areas
    if (e.target === scroller) {
      // ignore clicks on the scrollbars
      const r = scroller.getBoundingClientRect();
      if (e.clientX - r.left >= scroller.clientWidth || e.clientY - r.top >= scroller.clientHeight) return;
      e.preventDefault(); // keep the browser from moving focus elsewhere
      const pos = view.posAtCoords({ x: e.clientX, y: e.clientY }, false);
      view.focus();
      if (pos != null) view.dispatch({ selection: { anchor: pos } });
    } else if (!view.hasFocus) {
      view.focus();
    }
  };
  scroller.addEventListener("mousedown", onDown);
  return { destroy() { scroller.removeEventListener("mousedown", onDown); } };
});
*/
/* ---- highlight lines that set $answer (used by the solver while dragging) ---- */

export const setAnswerHighlight = StateEffect.define();
const answerLine = Decoration.line({ class: "cm-answerline" });
const answerRe = /\$answers?[\[\]0-9\s]*=/i;

function answerDecos(state) {
  const r = [];
  for (let i = 1; i <= state.doc.lines; i++) {
    const l = state.doc.line(i);
    if (answerRe.test(l.text)) r.push(answerLine.range(l.from));
  }
  return Decoration.set(r);
}

export const answerLines = StateField.define({
  create: () => ({ on: false, deco: Decoration.none }),
  update(v, tr) {
    let on = v.on;
    for (const e of tr.effects) if (e.is(setAnswerHighlight)) on = e.value;
    if (on === v.on && !(on && tr.docChanged)) return v;
    return { on, deco: on ? answerDecos(tr.state) : Decoration.none };
  },
  provide: f => EditorView.decorations.from(f, v => v.deco)
});

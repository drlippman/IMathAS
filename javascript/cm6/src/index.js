// Entry point: defines window.IMathASCM, a thin helper around CodeMirror 6 for the IMathAS
// question editors.  fromTextArea() returns a plain CM6 EditorView (plus .save() and
// .toTextArea()), so everything else can use the CM6 API directly (view.state, view.dispatch).
import { EditorState, StateEffect, StateField, Prec } from "@codemirror/state";
import { EditorView, keymap, lineNumbers } from "@codemirror/view";
import {
  defaultKeymap, history, historyKeymap, indentMore, indentLess, insertTab, indentSelection
} from "@codemirror/commands";
import { bracketMatching, indentOnInput, indentUnit } from "@codemirror/language";
import { closeBrackets, closeBracketsKeymap } from "@codemirror/autocomplete";
import { imathas } from "./imathas-mode.js";
import { qtext } from "./qtext.js";
import { highlighting } from "./tags.js";
import { matchTags, scriptBracketMatching, closeBracketsOnlyInScript, preserveInPre, answerLines, setAnswerHighlight } from "./extras.js";

EditorView.EDIT_CONTEXT = false;

// Tab inserts a tab (or indents a selection), Shift-Tab dedents
const tabKeys = [{
  key: "Tab",
  run: v => (v.state.selection.ranges.some(r => !r.empty) ? indentMore(v) : insertTab(v)),
  shift: indentLess
}];

// Insert key toggles overwrite mode (as in CM5). While on, typing a character replaces the
// character after the cursor, and the cursor is drawn as a block.
const setOverwrite = StateEffect.define();
const overwriteField = StateField.define({
  create: () => false,
  update(val, tr) {
    for (const e of tr.effects) if (e.is(setOverwrite)) val = e.value;
    return val;
  },
  provide: f => EditorView.editorAttributes.from(f, on => (on ? { class: "cm-overwrite" } : {}))
});
const overwriteExt = [
  overwriteField,
  // Native caret (no drawSelection, which is problematic on mobile); block shape where supported
  EditorView.baseTheme({ "&.cm-overwrite .cm-content": { caretShape: "block", "caret-shape": "block" } }),
  Prec.high(keymap.of([{
    key: "Insert",
    run: v => { v.dispatch({ effects: setOverwrite.of(!v.state.field(overwriteField)) }); return true; }
  }])),
  EditorView.inputHandler.of((view, from, to, text) => {
    if (!view.state.field(overwriteField) || from !== to || !text || text.includes("\n")) return false;
    const line = view.state.doc.lineAt(to);
    if (to >= line.to) return false; // at end of line: just insert
    const end = Math.min(line.to, to + text.length);
    view.dispatch({
      changes: { from, to: end, insert: text },
      selection: { anchor: from + text.length },
      userEvent: "input.type",
      scrollIntoView: true
    });
    return true;
  })
];

/**
 * Replace a textarea with an editor.
 * @param {HTMLTextAreaElement} ta
 * @param {object} opts
 *   kind        "imathas" (default; question code, with line numbers, bracket matching and
 *               auto-closing) or "qtext" (HTML + $variable highlighting, tag matching)
 *   readOnly    boolean
 *   lineNumbers override the per-kind default
 *   autoCloseTags  (qtext only) auto-insert closing HTML tags; default false
 * @returns {EditorView}  with extra methods save() and toTextArea()
 */
function fromTextArea(ta, opts) {
  opts = opts || {};
  const isText = opts.kind === "qtext";
  const showNumbers = opts.lineNumbers != null ? opts.lineNumbers : !isText;

  const extensions = [
    history(),
    overwriteExt,
    highlighting,
    EditorView.lineWrapping,
    EditorState.tabSize.of(2),
    indentUnit.of("  "),
    EditorState.readOnly.of(!!opts.readOnly),
    EditorView.contentAttributes.of({ spellcheck: "false", autocorrect: "off", autocapitalize: "off" }),
    keymap.of([...tabKeys, ...defaultKeymap, ...historyKeymap])
  ];
  if (showNumbers) extensions.push(lineNumbers());
  if (isText) {
    // Smart indent while typing (Enter, closing tags), but existing text is never re-indented
    // on load, and <pre> contents are left alone.
    extensions.push(
      qtext(opts.autoCloseTags), matchTags, scriptBracketMatching, preserveInPre, indentOnInput(),
      // auto-close brackets/quotes, but only inside <script> blocks
      closeBrackets(), Prec.high(keymap.of(closeBracketsKeymap)), closeBracketsOnlyInScript
    );
  } else {
    extensions.push(
      imathas(), answerLines, bracketMatching(), closeBrackets(), indentOnInput(),
      Prec.high(keymap.of(closeBracketsKeymap))
    );
  }

  const view = new EditorView({ state: EditorState.create({ doc: ta.value, extensions }) });
  ta.parentNode.insertBefore(view.dom, ta.nextSibling);
  ta.style.display = "none";

  const form = ta.form;
  view.save = function () { ta.value = view.state.doc.toString(); };
  if (form) form.addEventListener("submit", view.save);
  view.toTextArea = function () {
    view.save();
    if (form) form.removeEventListener("submit", view.save);
    view.destroy();
    ta.style.display = "";
  };
  return view;
}

const IMathASCM = {
  fromTextArea,

  getValue: view => view.state.doc.toString(),

  setValue(view, text) {
    view.dispatch({ changes: { from: 0, to: view.state.doc.length, insert: String(text) } });
  },

  /** Selected text ("" if nothing selected; multiple ranges joined by newlines) */
  getSelection: view => view.state.selection.ranges
    .map(r => view.state.sliceDoc(r.from, r.to)).join("\n"),

  /** Collapse the selection to the cursor */
  collapseSelection(view) {
    view.dispatch({ selection: { anchor: view.state.selection.main.head } });
  },

  /** Set the editor's outer size; pass "auto"/null to leave a dimension alone */
  setSize(view, width, height) {
    const css = v => (typeof v === "number" ? v + "px" : v);
    if (width != null) view.dom.style.width = css(width);
    if (height != null) view.dom.style.height = css(height);
  },

  /** Auto-indent the whole document (no-op for read-only editors) */
  indentAll(view) {
    if (view.state.readOnly) return;
    view.dispatch({ selection: { anchor: 0, head: view.state.doc.length } });
    indentSelection(view);
    view.dispatch({ selection: { anchor: 0 } });
  },

  /** Add key bindings, e.g. addKeys(view, {"Ctrl-m": function(view) {...}}) */
  addKeys(view, map) {
    const bindings = Object.keys(map).map(key => ({
      key, preventDefault: true, run: v => { map[key](v); return true; }
    }));
    view.dispatch({ effects: StateEffect.appendConfig.of(Prec.highest(keymap.of(bindings))) });
  },

  /** Highlight (or stop highlighting) lines that assign $answer (kind "imathas" only) */
  highlightAnswerLines(view, on) {
    view.dispatch({ effects: setAnswerHighlight.of(!!on) });
  }
};

window.IMathASCM = IMathASCM;

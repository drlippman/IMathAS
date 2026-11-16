// Token names produced by the IMathAS tokenizer -> highlight tags, and tags -> CSS classes.
// Class names follow the old CM5 theme (cm-keyword, cm-variable-2, ...) so colours live in
// codemirror6.css.
import { tags as t } from "@lezer/highlight";
import { HighlightStyle, syntaxHighlighting } from "@codemirror/language";

export const imathasTokenTable = {
  keyword: t.keyword,
  atom: t.atom,
  number: t.number,
  string: t.string,
  comment: t.comment,
  variable: t.variableName,
  operator: t.operator,
  builtin: t.standard(t.variableName),
  var: t.special(t.variableName),      // $variable
  specialvar: t.special(t.typeName),   // $answer, $anstypes, ...
  badvar: t.invalid                    // variables that may not be used in questions
};

export const highlighting = syntaxHighlighting(HighlightStyle.define([
  { tag: t.keyword, class: "cm-keyword" },
  { tag: [t.atom, t.bool, t.null], class: "cm-atom" },
  { tag: t.number, class: "cm-number" },
  { tag: t.standard(t.variableName), class: "cm-builtin" },
  { tag: t.special(t.variableName), class: "cm-variable-2" },
  { tag: t.special(t.typeName), class: "cm-variable-3" },
  { tag: t.comment, class: "cm-comment" },
  { tag: [t.string, t.attributeValue], class: "cm-string" },
  { tag: [t.regexp, t.escape, t.special(t.string)], class: "cm-string-2" },
  { tag: [t.meta, t.processingInstruction], class: "cm-meta" },
  { tag: t.tagName, class: "cm-tag" },
  { tag: t.attributeName, class: "cm-attribute" },
  { tag: [t.bracket, t.angleBracket], class: "cm-bracket" },
  { tag: t.propertyName, class: "cm-property" },
  { tag: t.definition(t.variableName), class: "cm-def" },
  { tag: t.invalid, class: "cm-error" }
]));

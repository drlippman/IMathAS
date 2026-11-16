# CodeMirror 6 build for IMathAS

Builds one script and one stylesheet for the question editors (`moddataset.php`,
`modtutorialq.php`, `solver.js`). It uses CodeMirror 6 directly; `window.IMathASCM` is only a
handful of helpers.

## Build

```
cd javascript/cm6
npm install
npm run build        # -> dist/codemirror6.min.js, dist/codemirror6.min.css
npm run build:dev    # -> dist/codemirror6.js (+ sourcemap, unminified)
```

Commit the minified `dist/` files; `node_modules/` is ignored. Bump the `?v=` on the includes
in the PHP pages when you rebuild.

## Using it

```php
<script src="$staticroot/javascript/cm6/dist/codemirror6.min.js?v=..."></script>
<link rel="stylesheet" href="$staticroot/javascript/cm6/dist/codemirror6.min.css?v=...">
```

```js
var view = IMathASCM.fromTextArea(textarea, {kind: "imathas", readOnly: false});
```

`fromTextArea` hides the textarea, inserts the editor after it, and returns a normal CM6
`EditorView` with two extra methods: `view.save()` (copy content to the textarea; also run
automatically when the form is submitted) and `view.toTextArea()` (save, destroy, show the
textarea again).

Options: `kind` (`"imathas"` = question code: line numbers, bracket matching/auto-close,
`$answer` highlighting; `"qtext"` = HTML with `$var`/`{$var}` highlighting and tag matching),
`readOnly`, `lineNumbers` (override the per-kind default).

### Helpers (`IMathASCM.*`)

| function | purpose |
|---|---|
| `getValue(view)` / `setValue(view, text)` | document text |
| `getSelection(view)` | selected text, `""` if none |
| `collapseSelection(view)` | clear the selection, keep the cursor |
| `setSize(view, w, h)` | outer size; numbers are px, strings pass through |
| `indentAll(view)` | auto-indent the whole doc (skipped for read-only editors) |
| `addKeys(view, {"Ctrl-m": fn})` | extra key bindings (CM6 key names: lower-case letter after the modifier) |
| `highlightAnswerLines(view, bool)` | highlight lines assigning `$answer` |

Anything else: use the CM6 API on the view (`view.state`, `view.dispatch`, `view.dom`).

## Layout

| file | purpose |
|---|---|
| `src/index.js` | `IMathASCM`: `fromTextArea`, helpers, editor setup per `kind` |
| `src/imathas-mode.js` | IMathAS language (StreamLanguage tokenizer, ported from the CM5 clike-based mode) |
| `src/imathas-words.js` | keyword / macro / special-variable lists — update when macro libraries change |
| `src/qtext.js` | HTML + `$variable` highlighting (a `MatchDecorator`) |
| `src/extras.js` | tag matching, selected-text class, `$answer` line highlight |
| `src/tags.js` | token names → highlight tags → `cm-*` CSS classes |
| `src/codemirror6.css` | colours (the CM5 default theme palette) and the few custom classes |

## Page CSS

Native CM6 classes: `.cm-editor` (root), `.cm-scroller` (scrolling area, where
`min-height`/`max-height` go). The old `.CodeMirror*` selection rules are gone; selected text
styling comes from `codemirror6.css`.

## Notes

* Tab inserts a tab (or indents a selection), Shift-Tab dedents, as in the CM5 editor.
* IMathAS indentation is bracket-depth based; statement continuations aren't indented.
* Don't call `view.dispatch` from inside an update listener (CM6 forbids it).

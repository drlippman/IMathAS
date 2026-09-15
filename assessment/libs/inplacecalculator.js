/*
 * inplacecalculator.js
 *
 * In-place fraction calculator for IMathAS / MyOpenMath answer boxes.
 * Loaded by assessment/libs/inplacecalculator.php.
 *
 * A button rendered by inplacecalculator() or inplacecalc(), or the key
 * combination ctrl-enter, reduces the text currently selected in a MathQuill
 * answer box and writes the reduced form back over the selection.
 *
 * Nothing is written unless all of the following hold:
 *   - the selection is a rational expression: digits, unary -, the binary
 *     operators ^ * / + -, ( ) and [ ], with ^ limited to the powers -2..2;
 *   - it has no zero denominator;
 *   - the rewritten formula is still equivalent to the original, tested
 *     numerically at random values of any variables.
 *
 * Operators may be included at the head or tail of the selection: a leading
 * "-" adds a "+" ahead of the reduction, a leading "/" reduces 1/(rest), and
 * so on; "+-" is simplified to "-", "*1/" to "/", and an explicit product of a
 * number and a following variable becomes implicit.
 *
 * Equations, inequalities and comma separated lists are handled by rewriting
 * only the piece between the =, <, > or , that contains the selection.
 *
 * A full button (class "ratGo") also copies the selection to the input box
 * (class "ratIn") beside it and the reduced value to the output box (class
 * "ratOut"), and with nothing selected it reduces whatever is in its input
 * box. Several full calculators may share a page; each uses the boxes of the
 * widget it sits in. Minimal buttons (class "ratGo2") and ctrl-enter only
 * rewrite the selection.
 *
 * The only global this file claims is window.InPlaceCalculator.
 */
(function () {
  'use strict';
  if (window.InPlaceCalculator) { return; }

  /* ===================== exact rational arithmetic ===================== */

  /* BigInt constants are built by call rather than with the 1n literal syntax,
     so that a browser without BigInt fails only when a function is called
     rather than throwing a syntax error when this file is loaded. */
  var Z0 = BigInt(0), Z1 = BigInt(1), Z2 = BigInt(2);

  var BAD = { rat: 'syntax' };   // not a rational expression
  var DIV0 = { rat: 'divzero' }; // zero denominator somewhere

  /* ---------- exact rational arithmetic ---------- */

  function abs(x) { return x < Z0 ? -x : x; }

  function gcd(a, b) {
    a = abs(a);
    b = abs(b);
    while (b !== Z0) {
      var t = a % b;
      a = b;
      b = t;
    }
    return a;
  }

  /* Build a normalized fraction: denominator positive, terms coprime. */
  function mk(n, d) {
    if (d === Z0) { throw DIV0; }
    if (d < Z0) { n = -n; d = -d; }
    var g = gcd(n, d);
    if (g === Z0) { g = Z1; }
    return { n: n / g, d: d / g };
  }

  function add(x, y) { return mk(x.n * y.d + y.n * x.d, x.d * y.d); }
  function sub(x, y) { return mk(x.n * y.d - y.n * x.d, x.d * y.d); }
  function mul(x, y) { return mk(x.n * y.n, x.d * y.d); }
  function div(x, y) { return mk(x.n * y.d, x.d * y.n); }
  function neg(x) { return { n: -x.n, d: x.d }; }

  function pow(x, e) {
    switch (e) {
      case 0: return mk(Z1, Z1);
      case 1: return x;
      case 2: return mk(x.n * x.n, x.d * x.d);
      case -1: return mk(x.d, x.n);
      case -2: return mk(x.d * x.d, x.n * x.n);
    }
    throw BAD;
  }

  /* ---------- tokenizer ---------- */

  function tokenize(s) {
    if (typeof s !== 'string') { throw BAD; }
    var toks = [];
    var i = 0;
    while (i < s.length) {
      var c = s.charAt(i);
      if (c === ' ' || c === '\t' || c === '\n' || c === '\r') {
        i++;
      } else if (c >= '0' && c <= '9') {
        var j = i;
        while (j < s.length && s.charAt(j) >= '0' && s.charAt(j) <= '9') { j++; }
        toks.push({ t: 'num', v: s.substring(i, j) });
        i = j;
      } else if ('+-*/^()[]'.indexOf(c) >= 0) {
        toks.push({ t: c });
        i++;
      } else {
        throw BAD; // letters, decimal points, commas, anything else
      }
    }
    if (toks.length === 0) { throw BAD; }
    return toks;
  }

  /* ---------- parser ---------- */

  function parse(str) {
    var toks = tokenize(str);
    var p = 0;

    function peek() { return toks[p]; }

    function eat(t) {
      if (toks[p] && toks[p].t === t) { p++; return true; }
      return false;
    }

    function startsPrimary() {
      var t = peek();
      if (!t) { return false; }
      /* Two numerals in a row ("2 3") are a typo, not a product; at least one */
      /* side of an implicit multiplication must be parenthesized. */
      if (t.t === 'num' && toks[p - 1] && toks[p - 1].t === 'num') { return false; }
      return t.t === 'num' || t.t === '(' || t.t === '[';
    }

    function expr() {
      var a = term();
      while (peek() && (peek().t === '+' || peek().t === '-')) {
        var k = toks[p++].t;
        a = { k: k, a: a, b: term() };
      }
      return a;
    }

    function term() {
      var a = unary();
      for (;;) {
        if (peek() && (peek().t === '*' || peek().t === '/')) {
          var k = toks[p++].t;
          a = { k: k, a: a, b: unary() };
        } else if (startsPrimary()) {
          a = { k: '*', a: a, b: power() }; // 3(2)4 is 3*2*4
        } else {
          break;
        }
      }
      return a;
    }

    function unary() {
      if (eat('-')) { return { k: 'neg', a: unary() }; }
      return power();
    }

    function power() {
      var base = primary();
      if (eat('^')) {
        /* The exponent is a constant subexpression, so it can be evaluated */
        /* here and checked against the allowed powers -2..2. */
        var e = evalNode(unary());
        if (e.d !== Z1 || e.n > Z2 || e.n < -Z2) { throw BAD; }
        return { k: 'pow', a: base, e: Number(e.n) };
      }
      return base;
    }

    function primary() {
      var t = peek();
      if (!t) { throw BAD; }
      if (t.t === 'num') { p++; return { k: 'num', v: BigInt(t.v) }; }
      if (t.t === '(') { p++; var a = expr(); if (!eat(')')) { throw BAD; } return a; }
      if (t.t === '[') { p++; var b = expr(); if (!eat(']')) { throw BAD; } return b; }
      throw BAD;
    }

    var ast = expr();
    if (p !== toks.length) { throw BAD; } // trailing junk
    return ast;
  }

  /* ---------- evaluation ---------- */

  function evalNode(t) {
    switch (t.k) {
      case 'num': return mk(t.v, Z1);
      case 'neg': return neg(evalNode(t.a));
      case '+': return add(evalNode(t.a), evalNode(t.b));
      case '-': return sub(evalNode(t.a), evalNode(t.b));
      case '*': return mul(evalNode(t.a), evalNode(t.b));
      case '/': return div(evalNode(t.a), evalNode(t.b));
      case 'pow': return pow(evalNode(t.a), t.e);
    }
    throw BAD;
  }

  function analyze(exp) { return evalNode(parse(exp)); }

  /* ---------- canonical printing ---------- */

  function format(r) {
    if (r.d === Z1) { return String(r.n); }
    return (r.n < Z0 ? '(' + r.n + ')' : String(r.n)) + '/' + r.d;
  }

  /* ---------- public functions ---------- */

  function isRationalExp(exp) {
    try {
      analyze(exp);
      return true;
    } catch (e) {
      return e !== BAD; // "1/0" is still a rational expression
    }
  }

  function isDefinedRat(exp) {
    try {
      analyze(exp);
      return true;
    } catch (e) {
      return false;
    }
  }

  function reduce(exp) {
    try {
      return format(analyze(exp));
    } catch (e) {
      return exp; // not a rational expression, or a zero denominator
    }
  }

  function isReduced(exp) {
    /* format() emits exactly the canonical reduced fractions, so an expression */
    /* is a reduced fraction precisely when it is its own reduction. */
    return isDefinedRat(exp) && reduce(exp) === String(exp).replace(/\s+/g, '');
  }

  var RationalExp = {
    isRationalExp: isRationalExp,
    isDefinedRat: isDefinedRat,
    reduce: reduce,
    isReduced: isReduced
  };

  /* ============ is the rewrite still the same expression? ============ */

  /* ---------- numeric evaluation of general expressions ----------
     Used only to test whether a rewrite changed the meaning of the formula.
     Handles digits, decimals, variables, the usual operators, ( ) [ ], implicit
     multiplication, and a few standard functions. Multi-letter names are single
     variables, but a digit after a letter is a product, so "x4" is x times 4. */
  var RAT_FUNS = {
    sqrt: Math.sqrt, abs: Math.abs, exp: Math.exp, ln: Math.log, log: Math.log,
    sin: Math.sin, cos: Math.cos, tan: Math.tan,
    asin: Math.asin, acos: Math.acos, atan: Math.atan
  };
  function numTokens(s) {
    var t = [], i = 0;
    while (i < s.length) {
      var c = s.charAt(i);
      if (c === ' ' || c === '\t' || c === '\n' || c === '\r') { i++; continue; }
      if ((c >= '0' && c <= '9') || c === '.') {
        var j = i, dot = false;
        while (j < s.length) {
          var d = s.charAt(j);
          if (d >= '0' && d <= '9') { j++; }
          else if (d === '.' && !dot) { dot = true; j++; }
          else { break; }
        }
        var v = parseFloat(s.substring(i, j));
        if (isNaN(v)) { return null; }
        t.push({ t: 'num', v: v });
        i = j;
      } else if ((c >= 'a' && c <= 'z') || (c >= 'A' && c <= 'Z')) {
        var k = i;
        while (k < s.length && /[a-zA-Z]/.test(s.charAt(k))) { k++; }
        t.push({ t: 'id', v: s.substring(i, k) });
        i = k;
      } else if ('+-*/^()[]'.indexOf(c) >= 0) {
        t.push({ t: c });
        i++;
      } else {
        return null;
      }
    }
    return t.length ? t : null;
  }
  function numParse(str) {
    var toks = numTokens(str);
    if (!toks) { return null; }
    var p = 0;
    function peek() { return toks[p]; }
    function eat(t) { if (toks[p] && toks[p].t === t) { p++; return true; } return false; }
    function starts() {
      var t = peek();
      return !!t && (t.t === 'num' || t.t === 'id' || t.t === '(' || t.t === '[');
    }
    function expr() {
      var a = term();
      while (peek() && (peek().t === '+' || peek().t === '-')) {
        var k = toks[p++].t;
        a = { k: k, a: a, b: term() };
      }
      return a;
    }
    function term() {
      var a = unary();
      for (;;) {
        if (peek() && (peek().t === '*' || peek().t === '/')) {
          var k = toks[p++].t;
          a = { k: k, a: a, b: unary() };
        } else if (starts()) {
          a = { k: '*', a: a, b: power() };
        } else { break; }
      }
      return a;
    }
    function unary() {
      if (eat('-')) { return { k: 'neg', a: unary() }; }
      if (eat('+')) { return unary(); }
      return power();
    }
    function power() {
      var b = primary();
      if (eat('^')) { return { k: '^', a: b, b: unary() }; }
      return b;
    }
    function primary() {
      var t = peek();
      if (!t) { throw 0; }
      if (t.t === 'num') { p++; return { k: 'num', v: t.v }; }
      if (t.t === 'id') {
        p++;
        if (peek() && peek().t === '(') {
          p++;
          var arg = expr();
          if (!eat(')')) { throw 0; }
          return { k: 'fn', n: t.v, a: arg };
        }
        return { k: 'var', n: t.v };
      }
      if (t.t === '(') { p++; var a = expr(); if (!eat(')')) { throw 0; } return a; }
      if (t.t === '[') { p++; var b2 = expr(); if (!eat(']')) { throw 0; } return b2; }
      throw 0;
    }
    try {
      var ast = expr();
      if (p !== toks.length) { return null; }
      return ast;
    } catch (err) {
      return null;
    }
  }
  function numVars(n, out) {
    if (!n) { return; }
    if (n.k === 'var') { if (n.n !== 'pi') { out[n.n] = 1; } return; }
    numVars(n.a, out);
    numVars(n.b, out);
  }
  function numEval(n, env) {
    switch (n.k) {
      case 'num': return n.v;
      case 'var': return n.n === 'pi' ? Math.PI : env[n.n];
      case 'neg': return -numEval(n.a, env);
      case '+': return numEval(n.a, env) + numEval(n.b, env);
      case '-': return numEval(n.a, env) - numEval(n.b, env);
      case '*': return numEval(n.a, env) * numEval(n.b, env);
      case '/': return numEval(n.a, env) / numEval(n.b, env);
      case '^': return Math.pow(numEval(n.a, env), numEval(n.b, env));
      case 'fn':
        var f = RAT_FUNS[n.n];
        return f ? f(numEval(n.a, env)) : NaN;
    }
    return NaN;
  }
  /* Two expressions count as equivalent when they agree at randomly chosen
     values of their variables, to within rounding error. Points where either
     side is undefined are skipped, so x/x and 1 come out equivalent. An
     expression that will not parse is never treated as equivalent. */
  function ratEquiv(aStr, bStr) {
    var a = numParse(aStr), b = numParse(bStr);
    if (!a || !b) { return false; }
    var seen = {};
    numVars(a, seen);
    numVars(b, seen);
    var names = [];
    for (var key in seen) { if (seen.hasOwnProperty(key)) { names.push(key); } }
    var good = 0;
    for (var i = 0; i < 30 && good < 10; i++) {
      var env = {};
      for (var j = 0; j < names.length; j++) {
        var m = 0.5 + 3 * Math.random();
        env[names[j]] = (i % 2) ? -m : m;
      }
      var va = numEval(a, env), vb = numEval(b, env);
      if (!isFinite(va) || !isFinite(vb)) { continue; }
      good++;
      if (Math.abs(va - vb) > 1e-9 * Math.max(1, Math.abs(va), Math.abs(vb))) { return false; }
    }
    return good >= 5;
  }
  /* ---------- building the replacement ----------
     Returns null when the selection cannot be reduced, otherwise the text to
     write, how many characters it eats on either side, and the reduced
     fraction. With allowCuts false it never reaches outside the selection. */
  function ratSplice(field, s, e, allowCuts) {
    var L = RationalExp;
    var before = field.substring(0, s), after = field.substring(e);
    var core = field.substring(s, e).replace(/^\s+|\s+$/g, '');
    if (core === '') { return null; }
    var tailOp = '';
    var last = core.charAt(core.length - 1);
    if (last === '+' || last === '-' || last === '*' || last === '/') {
      tailOp = last;
      core = core.substring(0, core.length - 1).replace(/\s+$/, '');
    }
    var bch = before.charAt(before.length - 1);
    var joinable = /[a-zA-Z0-9)\]]/.test(bch);
    var head = core.charAt(0), headOp = '';
    if (head === '+') {
      headOp = '+';
      core = core.substring(1);
    } else if (head === '*') {
      headOp = '*';
      core = core.substring(1);
    } else if (head === '/') {
      headOp = '*';
      core = '1/' + core.substring(1);
    } else if (head === '-' && joinable) {
      headOp = '+';
    }
    if (core === '' || !L.isDefinedRat(core)) { return null; }
    var r = L.reduce(core);
    var repl = headOp + r;
    var leftCut = 0, rightCut = 0;
    if (repl.substring(0, 2) === '+-') { repl = repl.substring(1); }
    if (headOp === '' && /[a-zA-Z0-9]/.test(bch) && /[0-9]/.test(repl.charAt(0))) {
      repl = '*' + repl;
    }
    if (repl.substring(0, 3) === '*1/') { repl = '/' + repl.substring(3); }
    if (allowCuts) {
      if (bch === '+' && repl.charAt(0) === '-') {
        leftCut = 1;
      } else if (bch === '-' && repl.charAt(0) === '-') {
        leftCut = 1;
        repl = '+' + repl.substring(1);
      }
    }
    repl = repl + tailOp;
    if (repl.charAt(repl.length - 1) === '*' && /[a-zA-Z]/.test(after.charAt(0))) {
      repl = repl.substring(0, repl.length - 1);
    } else if (allowCuts && after.charAt(0) === '*' && /[a-zA-Z]/.test(after.charAt(1)) &&
               /[0-9)]/.test(repl.charAt(repl.length - 1))) {
      rightCut = 1;
    }
    var built = before.substring(0, before.length - leftCut) + repl + after.substring(rightCut);
    if (built === field) { return null; }
    if (!ratEquiv(field, built)) { return null; }
    return { repl: repl, left: leftCut, right: rightCut, value: r };
  }
  /* An answer box may hold an equation, an inequality or a comma separated list.
     The selection is a rational expression, so it never spans one of these
     separators; the piece between them is what gets rewritten and checked. */
  var RAT_SEP = /[=<>,]/;
  function ratSegment(field, at, end) {
    var s = 0, e = field.length, i;
    for (i = at - 1; i >= 0; i--) {
      if (RAT_SEP.test(field.charAt(i))) { s = i + 1; break; }
    }
    for (i = end; i < field.length; i++) {
      if (RAT_SEP.test(field.charAt(i))) { e = i; break; }
    }
    return { s: s, e: e };
  }
  /* MathQuill reports the selected text but not where it sits, so the selection
     is located in the field by matching. If it occurs more than once, every
     occurrence must yield the same rewrite, otherwise nothing is done. */
  function ratPlan(field, sel, allowCuts) {
    if (RAT_SEP.test(sel)) { return null; }
    var first = null, at = field.indexOf(sel);
    if (at < 0) { return null; }
    while (at >= 0) {
      var seg = ratSegment(field, at, at + sel.length);
      var p = ratSplice(field.substring(seg.s, seg.e), at - seg.s, at + sel.length - seg.s, allowCuts);
      if (!p) { return null; }
      if (!first) {
        first = p;
      } else if (p.repl !== first.repl || p.left !== first.left || p.right !== first.right) {
        return null;
      }
      at = field.indexOf(sel, at + 1);
    }
    return first;
  }
  /* ===================== the MathQuill answer boxes ===================== */

  /* Every MathQuill answer box on the page, found by id rather than by
     question number, so the same code serves single and multipart questions
     and boxes added after this file has loaded. */
  function ratNodes() {
    if (!document.querySelectorAll) { return []; }
    return document.querySelectorAll('[id^="mqinput-qn"]');
  }

  function mqField(node) {
    try { return (typeof MQ === 'function') ? MQ(node) : null; } catch (err) { return null; }
  }

  function mqText(mf) {
    try { return String(MQtoAM(mf.latex()) || ''); } catch (err) { return ''; }
  }

  function mqSelection(mf) {
    try { return String(MQtoAM(mf.getSelection()) || ''); } catch (err) { return ''; }
  }

  function noSpace(s) { return String(s).replace(/\s+/g, ''); }

  /* The box holding the selection, preferring the one with the focus. */
  function selectedBox() {
    var nodes = ratNodes(), focused = null, any = null;
    for (var i = 0; i < nodes.length; i++) {
      var mf = mqField(nodes[i]);
      if (!mf || !noSpace(mqSelection(mf))) { continue; }
      if (!any) { any = mf; }
      if (!focused && /mq-focused/.test(nodes[i].className || '')) { focused = mf; }
    }
    return focused || any;
  }

  /* ===================== the action ===================== */

  function hasClass(el, name) {
    return !!el && typeof el.className === 'string' &&
           (' ' + el.className + ' ').indexOf(' ' + name + ' ') >= 0;
  }

  /* The input and output boxes belonging to a particular button: the nearest
     enclosing element that contains one. That way a page may carry several
     full calculators, each filling its own boxes. The id is a fallback, for
     markup written before the boxes carried classes. */
  function widgetBox(button, name) {
    var node = button;
    while (node && node !== document) {
      if (node.querySelector) {
        var found = node.querySelector('.' + name + ', #' + name);
        if (found) { return found; }
      }
      node = node.parentNode;
    }
    return document.getElementById(name);
  }

  function setBox(button, name, text) {
    var box = widgetBox(button, name);
    if (box) { box.value = text; }
  }

  /* Rewrites the selection. With useBoxes, also fills the input and output
     boxes, and with nothing selected reduces the contents of the input box.
     Returns true when something was done. */
  function ratAct(useBoxes, button) {
    var mf = selectedBox();
    if (mf) {
      var sel = noSpace(mqSelection(mf));
      var field = noSpace(mqText(mf));
      var cuts = (typeof mf.keystroke === 'function');
      var plan = ratPlan(field, sel, cuts);
      if (!plan && cuts) { plan = ratPlan(field, sel, false); }
      if (!plan) { return false; }
      try {
        if (plan.left) {
          mf.keystroke('Backspace');
          mf.keystroke('Backspace');
        }
        mf.write(AMtoMQ(plan.repl));
        for (var i = 0; i < plan.right; i++) { mf.keystroke('Del'); }
        if (typeof mf.focus === 'function') { mf.focus(); }
      } catch (err) {
        return false;
      }
      if (useBoxes) {
        setBox(button, 'ratOut', plan.value);
        setBox(button, 'ratIn', sel);
      }
      return true;
    }
    if (!useBoxes) { return false; }
    var inBox = widgetBox(button, 'ratIn');
    if (!inBox || !/\S/.test(inBox.value)) { return false; }
    setBox(button, 'ratOut', reduce(inBox.value));
    return true;
  }

  /* The rewrite that a given field and selection would produce, as a string,
     or null when no replacement would be made. Same decisions as a button
     click, but without a MathQuill field, so the behaviour can be exercised
     from a test page. The field is expected to hold no spaces. */
  function ratPreview(field, start, end) {
    field = String(field);
    var sel = field.substring(start, end);
    var plan = ratPlan(field, sel, true);
    if (!plan) { plan = ratPlan(field, sel, false); }
    if (!plan) { return null; }
    return field.substring(0, start - plan.left) + plan.repl + field.substring(end + plan.right);
  }

  /* ===================== styles ===================== */

  /* The stylesheet is injected here rather than written beside each button, so
     that it cannot be lost when a question redraws its text after a submit. */
  function addStyles() {
    if (!document.head || document.getElementById('inplacecalculator-style')) { return; }
    var css =
      '.ratIn, .ratOut { vertical-align: bottom; }' +
      '.inplacebutton { border-collapse: collapse; border-radius: 10px;' +
      ' border-spacing: 0; overflow: hidden; display: inline-table; }' +
      '.inplacebutton td { padding: 4px 0px; margin: 0px;' +
      ' border: 0px ridge #ffffff; background: #73b37b; }' +
      '.inplacebutton td input { margin: 2px; padding: 3px 0px; }' +
      '.inplacebutton td button { margin: 2px; }' +
      'button.ratGo2 { padding: 2px 7px; vertical-align: baseline; }';
    var el = document.createElement('style');
    el.id = 'inplacecalculator-style';
    el.appendChild(document.createTextNode(css));
    document.head.appendChild(el);
  }

  addStyles();
  if (document.addEventListener) {
    document.addEventListener('DOMContentLoaded', addStyles, false);
  }

  /* ===================== events ===================== */

  function isFull(el) { return !!el && (hasClass(el, 'ratGo') || el.id === 'ratGo'); }

  function isMini(el) { return hasClass(el, 'ratGo2'); }

  /* The buttons are written by PHP and may appear before or after this file
     runs, and there may be many of them, so the handlers are delegated. */
  function calcButton(el) {
    while (el && el !== document) {
      if (isFull(el) || isMini(el)) { return el; }
      el = el.parentNode;
    }
    return null;
  }

  /* Suppressing the default mousedown keeps the focus, and so the selection,
     in the answer box while a button is clicked. */
  document.addEventListener('mousedown', function (e) {
    if (calcButton(e.target)) { e.preventDefault(); }
  }, true);

  document.addEventListener('click', function (e) {
    var button = calcButton(e.target);
    if (button) { ratAct(isFull(button), button); }
  }, false);

  /* Ctrl-enter rewrites the selection and touches nothing else. The keystroke
     is swallowed only when a rewrite was actually made, so it still reaches
     the page when the calculator has nothing to do. */
  document.addEventListener('keydown', function (e) {
    var enter = (e.key === 'Enter' || e.keyCode === 13);
    if (!enter || !(e.ctrlKey || e.metaKey)) { return; }
    if (ratAct(false, null)) {
      e.preventDefault();
      e.stopPropagation();
    }
  }, true);

  window.InPlaceCalculator = {
    reduce: reduce,
    isRationalExp: isRationalExp,
    isDefinedRat: isDefinedRat,
    isReduced: isReduced,
    ratEquiv: ratEquiv,
    preview: ratPreview,
    act: ratAct
  };
})();

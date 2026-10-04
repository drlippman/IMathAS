<?php 

global $allowedmacros;
if (!isset($allowedmacros) || !is_array($allowedmacros)) {
    $allowedmacros = array();
}
array_push($allowedmacros, 'diffrandfractions', 'randfractions','randfraction',
    'simplifyfraction', 'dispsimplifyfraction','fractionnumerator','fractiondenominator',
    'isreducedfraction','makerepeatingdecimal','repeatingdecimal2fraction');

if (!defined('MF_DIV0')) {
    define('MF_DIV0', 'division by zero');
    define('MF_POW', 'powers other than -5 to 5 are not accepted');
    define('MF_NOFRAC', "this doesn't make a fraction");
    define('MF_BIG', 'numbers are too large');
    define('MF_MAXDIGITS', 10000);
}

if (!class_exists('MakeFractionError')) {
    class MakeFractionError extends Exception {}
}

function randfraction($denoms, $min, $max, $symbol = '/') {
    return diffrandfractions($denoms, $min, $max, 1, $symbol, 'def', false, false)[0];
}
function randfractions($denoms, $min, $max, $n = 0, $symbol = '/', $ord = 'def') {
    return diffrandfractions($denoms, $min, $max, $n, $symbol, $ord, false, false);
}

function diffrandfractions($denoms, $min, $max, $n = 0, $symbol = '/', $ord = 'def', $diffdenom = false, $dodiff = true) {
    if (func_num_args() < 4) {
        _mf_warn("diffrandfractions expects at least 4 arguments");
        return [];
    }
    if (!is_array($denoms)) {
        $denoms = listtoarray($denoms);
    }
    $denoms = array_values(array_unique(array_filter(array_map('intval', $denoms), function ($v) {
        return $v > 0;
    })));
    if (count($denoms) == 0) {
        _mf_warn("randfraction: Need at least one positive denominator");
        return [];
    }
    if (!in_array($symbol, array('/', '//', 'pi/', 'pi//'), true)) {
        _mf_warn("randfraction: Invalid symbol '$symbol'. Allowed: '/', '//', 'pi/', 'pi//'.");
        return [];
    }
    // min and max apply to the fraction itself; for pi symbols that is the
    // coefficient of pi, as in randfractions
    $pi = (substr($symbol, 0, 2) === 'pi');
    $double = (substr($symbol, -2) === '//');
    list($min, $max) = checkMinMax($min, $max, false, 'diffrandfractions');
    $n = floor($n);
    if ($n <= 0) {
        _mf_warn("randfraction: Need n > 0");
        return [];
    }
    if ($n > 1e4) {
        _mf_warn('randfraction: $n too large');
        return [];
    }

    $used = $dodiff ? [] : false;  // "num/den" => true
    $pools = []; // den => remaining valid numerators (built lazily for small ranges)
    $out = [];   // [value, string]
    $avail = $denoms;  // denominators that may still have unused numerators
    $queue = [];       // for diffdenom: denominators not yet used this round
    while (count($out) < $n && count($avail) > 0) {
        if ($diffdenom) {
            if (count($queue) == 0) {
                $queue = $avail;
            }
            $qi = $GLOBALS['RND']->rand(0, count($queue) - 1);
            $d = $queue[$qi];
            array_splice($queue, $qi, 1);
        } else {
            $d = $avail[$GLOBALS['RND']->rand(0, count($avail) - 1)];
        }
        $num = diffrandfractions_picknum($d, $min, $max, $used, $pools);
        if ($num === null) {
            // this denominator is exhausted
            $avail = array_values(array_diff($avail, [$d]));
            $queue = array_values(array_diff($queue, [$d]));
            continue;
        }
        $out[] = [$num / $d, _mf_format($num, $d, $pi, $double)];
    }
    if (count($out) < $n) {
        return array('DNE');
    }
    if ($ord == 'inc') {
        usort($out, function ($a, $b) {
            return $a[0] <=> $b[0];
        });
    } else if ($ord == 'dec') {
        usort($out, function ($a, $b) {
            return $b[0] <=> $a[0];
        });
    }
    return array_column($out, 1);
}

function simplifyfraction($expr) {
    // try regex parse, use parser only if needed
    $r = _mf_regexparse($expr);
    if (!$r['ok']) {
        $r = _mf_core($expr);
    } else {
        try {
            [$r['num'],$r['den']] = _mf_q($r['num'],$r['den']);
        } catch (MakeFractionError $e) {
            $r = ['ok' => false, 'msg' => $e->getMessage()];
        }
    }
    if (!$r['ok']) {
        _mf_warn($r['msg']);
        return 'DNE';
    }
    return _mf_format($r['num'], $r['den'], $r['pi'], $r['double']);
}

function dispsimplifyfraction($expr) {
    return ' `' . simplifyfraction($expr) . '`';
}

/* Numerator of makefraction(expr); for a pi fraction, the coefficient of pi. */
function fractionnumerator($expr) {
    $r = _mf_regexparse($expr); //_mf_core($expr);
    if (!$r['ok']) {
        _mf_warn($r['msg']);
        return 'DNE';
    }
    return $r['num'];
}

/* Denominator of makefraction(expr), always positive. */
function fractiondenominator($expr) {
    $r = _mf_regexparse($expr); //_mf_core($expr);
    if (!$r['ok']) {
        _mf_warn($r['msg']);
        return 'DNE';
    }
    return $r['den'];
}

/*
 * isreducedfraction(expr)
 * True when expr is a reduced fraction simplified as much as possible:
 * at most one minus sign,
 * no common factor, no denominator 1, no coefficient 1 on pi.
 * Grouping symbols may surround any part, so -(1/2), (-1)/2, 1/(-2),
 * (4pi)/3, 4(pi/3) and (4/3)pi are reduced; (-1)/(-2), 4/6, 6/1, 2*3,
 * --4 and 1pi/2 are not.
 */
function isreducedfraction($expr) {
    $r = _mf_regexparse($expr); // _mf_core($expr);
    if (!$r['ok']) {
        _mf_warn($r['msg']);
        return false;
    }
    if ($r['negs'] > 1 || $r['hasonecoef']) {
        return false;
    }
    return _mf_gcd($r['num'], $r['den']) == 1;
}

/* makerepeatingdecimal("15/7") = "2.bar(142857)", makerepeatingdecimal("1/4") = ".25" */
function makerepeatingdecimal($expr) {
    $r = _mf_regexparse($expr);
    if (!$r['ok']) {
        _mf_warn($r['msg']);
        return 'DNE';
    }
    if ($r['pi']) {
        _mf_warn('pi fractions do not have repeating decimals');
        return 'DNE';
    }
    if ($r['den'] == 0) {
        _mf_warn(MF_DIV0);
        return 'DNE';
    }
    try {
        // normalize so the denominator is positive
        [$num, $den] = _mf_q($r['num'], $r['den']);
        return _mf_decimal($num, $den);
    } catch (MakeFractionError $e) {
        _mf_warn($e->getMessage());
        return 'DNE';
    }
}

/* repeatingdecimal2fraction("1.00bar(3)") = "301/300" */
function repeatingdecimal2fraction($repeatdec) {
    $s = preg_replace('/\s+/', '', (string)$repeatdec);
    $ok = preg_match('/^(-?)(\d*)(?:\.(\d*)(?:bar\((\d+)\))?)?$/', $s, $m);
    $I = isset($m[2]) ? $m[2] : '';
    $N = isset($m[3]) ? $m[3] : '';
    $R = isset($m[4]) ? $m[4] : '';
    if (!$ok || ($I === '' && $N === '' && $R === '')) {
        _mf_warn('repeatingdecimal2fraction: $repeatdec should be like -2.33 or 2.1bar(33)');
        return 'DNE';
    }
    try {
        if (strlen(ltrim($I, '0')) > 18 || strlen($N) > 18 || strlen($R) > 18) {
            throw new MakeFractionError(MF_BIG);
        }
        $iv = (int)($I === '' ? '0' : $I);
        $nv = (int)($N === '' ? '0' : $N);
        $p10 = pow(10, strlen($N));
        if ($R === '') {
            $num = _mf_chk($iv * $p10 + $nv);
            $den = $p10;
        } else {
            $M = pow(10, strlen($R)) - 1;
            $num = _mf_chk($iv * $p10 * $M + $nv * $M + (int)$R);
            $den = _mf_chk($p10 * $M);
        }
        $q = _mf_q($num, $den);
        if ($m[1] === '-') {
            $q[0] = -$q[0];
        }
        return _mf_format($q[0], $q[1], 0, false);
    } catch (MakeFractionError $e) {
        _mf_warn($e->getMessage());
        return 'DNE';
    }
}

/*--- Internals ---*/

/* Writes n/d, or (n pi)/d when $pi is 1, in the '/' or '//' form. */
function _mf_format($num, $den, $pi, $double) {
    if ($num == 0) {
        return '0';
    }
    $sign = ($num < 0) ? '-' : '';
    $a = abs($num);
    $sym = $double ? '//' : '/';
    if (!$pi) {
        return ($den == 1) ? $sign . $a : $sign . $a . $sym . $den;
    }
    $c = ($a == 1) ? 'pi' : $a . 'pi';
    if ($den == 1) {
        return $sign . $c;
    }
    if ($double || $a == 1) {
        return $sign . $c . $sym . $den;
    }
    return $sign . '(' . $c . ')/' . $den;
}

// helper for diffrandfractions: pick a random numerator n for denominator $d
// with min <= n/d <= max, gcd(n,d)=1, and "n/d" not already in $used.
// Returns null if none exist.
// $pools caches (per denominator, for the duration of one diffrandfractions
// call) the remaining valid numerators for small ranges, or once random
// sampling has failed, so the enumeration happens at most once per denominator.
function diffrandfractions_picknum($d, $min, $max, &$used, &$pools) {
    $rnd = $GLOBALS['RND'];
    if (isset($pools[$d])) {
        // already enumerated; draw and remove in O(1)
        $cnt = count($pools[$d]);
        if ($cnt == 0) {
            return null;
        }
        $i = $rnd->rand(0, $cnt - 1);
        $num = $pools[$d][$i];
        if (is_array($used)) {
            // if not reusing, remove from pool
            $pools[$d][$i] = $pools[$d][$cnt - 1];
            array_pop($pools[$d]);
            $used[$num . '/' . $d] = true;
        }
        return $num;
    }
    $lo = (int) ceil($min * $d - 1e-9);
    $hi = (int) floor($max * $d + 1e-9);
    if ($hi < $lo) {
        $pools[$d] = [];
        return null;
    }

    $isok = function ($num) use ($d, &$used) {
        return _mf_gcd($num, $d) == 1
            && ($used === false || !isset($used[$num . '/' . $d]));
    };
    // random sampling first when the range is large enough for it to pay off;
    // small ranges skip straight to the enumerated pool
    if ($hi - $lo > 50) {
        for ($t = 0; $t < 25; $t++) {
            $num = $rnd->rand($lo, $hi);
            if ($isok($num)) {
                if (is_array($used)) {
                    $used[$num . '/' . $d] = true;
                }
                return $num;
            }
        }
    }
    // enumerate once, then cache
    if ($hi - $lo > 1e4) {
        return null;
    }
    $opts = [];
    for ($num = $lo; $num <= $hi; $num++) {
        if ($isok($num)) {
            $opts[] = $num;
        }
    }
    $pools[$d] = $opts;
    return diffrandfractions_picknum($d, $min, $max, $used, $pools);
}

function _mf_regexparse($expr) {
    $pattern = '~
        ^\s*+
        (?:(?P<neg>[+-])\s*+(?=\())?\s*+     # optional outer sign, only if a "(" follows
        (?P<lp1>\()?\s*+                     # optional "(" around numerator
        (?=[+-]?(?:\d|pi))\s*+               # numerator needs a digit or pi
        (?P<coef>[+-]?\d*)\s*+               # sign and/or digits
        (?P<pi>pi)?\s*+                      # optional pi
        (?(<lp1>)\))\s*+                     # ")" only if "(" was opened
        (?:                                  # --- optional denominator ---
            \s*+(?P<div>//?)\s*+             # division symbol / or //
            (?P<lp2>\()?\s*+                 # optional "(" around denominator
            (?P<den>[+-]?\d+)\s*+            # denominator with optional sign
            (?(<lp2>)\))                     # ")" only if "(" was opened
        )?
        \s*+$
    ~xi';
    if (preg_match($pattern, $expr, $m)) {
        $hasPi = !empty($m['pi']);
        $coef  = $m['coef'];
        $doubleslash = !empty($m['div']) && $m['div'] === '//';

        $negs = 0;
        $hasonecoef = false;
        if ($hasPi && ($coef === '' || $coef === '+')) {
            $coef = 1;
        } elseif ($hasPi && $coef === '-') {
            $coef = -1;
        } else {
            $coef = (int)$coef;
            $hasonecoef = $hasPi && abs($coef) == 1;
        }
        if ($coef < 0) {
            $negs++;
        }

        if (($m['neg'] ?? '') === '-') {
            $coef = -$coef;
            $negs++;
        }

        // denominator defaults to 1 when there is no "/..." part
        $hasonecoef |= ($m['den'] ?? 0) == 1;
        $den = (($m['den'] ?? '') !== '') ? (int)$m['den'] : 1;
        if ($den < 0) {
            $negs++;
        }
        return ['ok' => true, 'num' => $coef, 'den' => $den, 'pi' => $hasPi, 
            'double' => $doubleslash, 'negs' => $negs, 'hasonecoef' => $hasonecoef];
    } 
    return ['ok' => false];
}

function _mf_decimal($num, $den) {
    $sign = ($num < 0) ? '-' : '';
    $a = abs($num);
    $ip = intdiv($a, $den);
    $rem = $a % $den;
    if ($rem == 0) {
        return ($ip == 0) ? '0' : $sign . $ip;
    }
    $istr = ($ip == 0) ? '' : (string)$ip;
    $digits = '';
    $seen = array();
    $pos = 0;
    while ($rem != 0 && !isset($seen[$rem])) {
        if ($pos >= MF_MAXDIGITS) {
            throw new MakeFractionError('the repeating block is too long');
        }
        $seen[$rem] = $pos;
        $rem = _mf_chk($rem * 10);
        $digits .= intdiv($rem, $den);
        $rem = $rem % $den;
        $pos++;
    }
    if ($rem == 0) {
        return $sign . $istr . '.' . $digits;
    }
    $p = $seen[$rem];
    return $sign . $istr . '.' . substr($digits, 0, $p) . 'bar(' . substr($digits, $p) . ')';
}

/* PHP turns an overflowing int result into a float; catch that. */
function _mf_chk($x) {
    if (!is_int($x)) {
        throw new MakeFractionError(MF_BIG);
    }
    return $x;
}

function _mf_q($n, $d) {
    if ($d == 0) {
        throw new MakeFractionError(MF_DIV0);
    }
    if ($d < 0) {
        $n = _mf_chk(-$n);
        $d = _mf_chk(-$d);
    }
    if ($n == 0) {
        return array(0, 1);
    }
    $g = _mf_gcd($n, $d);
    if ($g > 1) {
        $n = intdiv($n, $g);
        $d = intdiv($d, $g);
    }
    return array($n, $d);
}

/* All warnings go through here, so their presentation can be changed in one place. */
function _mf_warn($msg) {
    echo Sanitize::encodeStringForDisplay($msg);
}

function _mf_gcd($a, $b) {
    $a = abs($a);
    $b = abs($b);
    while ($b != 0) {
        $t = $a % $b;
        $a = $b;
        $b = $t;
    }
    return $a;
}

/* ------------------------------------------------------------------ */
/* Tokenizer, parser and evaluator                                     */
/* ------------------------------------------------------------------ */

/*
 * Usage
 *   $p = new MakeFractionParser($expr);   tokenizes; throws MakeFractionError
 *   $p->parse();                          returns the AST (computed once)
 *   $p->evaluate();                       polynomial in pi, from the parsed AST
 *   $p->isDouble();                       true when expr has a // and no single /
 *
 * Grammar
 *   expr    := term (('+' | '-') term)*
 *   term    := unary (('*' | '/' | '//') unary | implicit power)*
 *   unary   := ('-' | '+') unary | power
 *   power   := primary ['^' unary]
 *   primary := number | pi | '(' expr ')' | '[' expr ']' | '{' expr '}'
 * so -2^2 = -4, 2^-1 = 1/2, 3pi/4 = (3pi)/4 and 9(1) = 9*1.
 * AST nodes: num, pi, grp, neg, pos, add, sub, mul, div, pow.
 */
class MakeFractionParser {
    private $t = array();
    private $p = 0;
    private $ast = null;
    private $double = false;

    public function __construct($expr) {
        $this->tokenize($expr);
    }

    public function parse() {
        if ($this->ast === null) {
            $this->p = 0;
            $e = $this->expr();
            if ($this->p !== count($this->t)) {
                throw new MakeFractionError(MF_NOFRAC);
            }
            $this->ast = $e;
        }
        return $this->ast;
    }

    public function evaluate() {
        return $this->evalNode($this->parse());
    }

    public function isDouble() {
        return $this->double;
    }

    /* Every character is checked before any parsing, so the first bad
       character is reported even when the expression is also malformed. */
    private function tokenize($s) {
        $t = preg_replace('/\s+/u', '', (string)$s);
        if ($t === null) {
            $t = preg_replace('/\s+/', '', (string)$s);
        }
        $chars = preg_split('//u', $t, -1, PREG_SPLIT_NO_EMPTY);
        if ($chars === false) {
            $chars = str_split($t);
        }
        $toks = array();
        $single = 0;
        $double = 0;
        $n = count($chars);
        $i = 0;
        while ($i < $n) {
            $c = $chars[$i];
            if (strlen($c) == 1 && ctype_digit($c)) {
                $num = '';
                while ($i < $n && strlen($chars[$i]) == 1 && ctype_digit($chars[$i])) {
                    $num .= $chars[$i];
                    $i++;
                }
                $toks[] = array('num', $num);
            } elseif ($c === 'p' && $i + 1 < $n && $chars[$i + 1] === 'i') {
                $toks[] = array('pi', 'pi');
                $i += 2;
            } elseif ($c === '/' && $i + 1 < $n && $chars[$i + 1] === '/') {
                $toks[] = array('op', '//');
                $double++;
                $i += 2;
            } elseif (strlen($c) == 1 && strpos('+-*/^', $c) !== false) {
                $toks[] = array('op', $c);
                if ($c === '/') {
                    $single++;
                }
                $i++;
            } elseif (strlen($c) == 1 && strpos('([{', $c) !== false) {
                $toks[] = array('open', $c);
                $i++;
            } elseif (strlen($c) == 1 && strpos(')]}', $c) !== false) {
                $toks[] = array('close', $c);
                $i++;
            } else {
                throw new MakeFractionError($c . ' is not an accepted character');
            }
        }
        if (count($toks) == 0) {
            throw new MakeFractionError(MF_NOFRAC);
        }
        $this->t = $toks;
        $this->double = ($double > 0 && $single == 0);
    }

    private function peek() {
        return ($this->p < count($this->t)) ? $this->t[$this->p] : null;
    }

    private function isOp($tok, $v) {
        return $tok !== null && $tok[0] === 'op' && $tok[1] === $v;
    }

    private function expr() {
        $l = $this->term();
        while (true) {
            $k = $this->peek();
            if ($this->isOp($k, '+')) {
                $this->p++;
                $l = array('add', $l, $this->term());
            } elseif ($this->isOp($k, '-')) {
                $this->p++;
                $l = array('sub', $l, $this->term());
            } else {
                return $l;
            }
        }
    }

    private function term() {
        $l = $this->unary();
        while (true) {
            $k = $this->peek();
            if ($k === null) {
                return $l;
            }
            if ($this->isOp($k, '*')) {
                $this->p++;
                $l = array('mul', $l, $this->unary());
            } elseif ($this->isOp($k, '/') || $this->isOp($k, '//')) {
                $this->p++;
                $l = array('div', $l, $this->unary());
            } elseif ($k[0] === 'num' || $k[0] === 'pi' || $k[0] === 'open') {
                $l = array('mul', $l, $this->power());
            } else {
                return $l;
            }
        }
    }

    private function unary() {
        $k = $this->peek();
        if ($this->isOp($k, '-')) {
            $this->p++;
            return array('neg', $this->unary());
        }
        if ($this->isOp($k, '+')) {
            $this->p++;
            return array('pos', $this->unary());
        }
        return $this->power();
    }

    private function power() {
        $b = $this->primary();
        if ($this->isOp($this->peek(), '^')) {
            $this->p++;
            return array('pow', $b, $this->unary());
        }
        return $b;
    }

    private function primary() {
        $k = $this->peek();
        if ($k === null) {
            throw new MakeFractionError(MF_NOFRAC);
        }
        if ($k[0] === 'num') {
            $this->p++;
            $digits = ltrim($k[1], '0');
            if (strlen($digits) > 18) {
                throw new MakeFractionError(MF_BIG);
            }
            return array('num', (int)$k[1]);
        }
        if ($k[0] === 'pi') {
            $this->p++;
            return array('pi');
        }
        if ($k[0] === 'open') {
            $this->p++;
            $e = $this->expr();
            $c = $this->peek();
            $match = array('(' => ')', '[' => ']', '{' => '}');
            if ($c === null || $c[0] !== 'close' || $c[1] !== $match[$k[1]]) {
                throw new MakeFractionError(MF_NOFRAC);
            }
            $this->p++;
            return array('grp', $e);
        }
        throw new MakeFractionError(MF_NOFRAC);
    }

    /* ------------------------------------------------------------------ */
    /* Exact rational arithmetic. A rational is array(num, den), den > 0.  */
    /* ------------------------------------------------------------------ */

    private static function qadd($a, $b) {
        $g = _mf_gcd($a[1], $b[1]);
        $n = _mf_chk($a[0] * intdiv($b[1], $g) + $b[0] * intdiv($a[1], $g));
        $d = _mf_chk(intdiv($a[1], $g) * $b[1]);
        return _mf_q($n, $d);
    }

    private static function qmul($a, $b) {
        $g1 = _mf_gcd($a[0], $b[1]);
        $g2 = _mf_gcd($b[0], $a[1]);
        $n = _mf_chk(intdiv($a[0], $g1) * intdiv($b[0], $g2));
        $d = _mf_chk(intdiv($a[1], $g2) * intdiv($b[1], $g1));
        return _mf_q($n, $d);
    }

    private static function qinv($a) {
        if ($a[0] == 0) {
            throw new MakeFractionError(MF_DIV0);
        }
        return _mf_q($a[1], $a[0]);
    }

    /* ------------------------------------------------------------------ */
    /* Polynomials in pi: array(power => rational), zero terms omitted.    */
    /* "1+pi" is a legal intermediate; it just isn't a fraction at the end.*/
    /* ------------------------------------------------------------------ */

    private static function one() {
        return array(0 => array(1, 1));
    }

    private static function padd($A, $B) {
        foreach ($B as $k => $v) {
            if (isset($A[$k])) {
                $s = self::qadd($A[$k], $v);
                if ($s[0] == 0) {
                    unset($A[$k]);
                } else {
                    $A[$k] = $s;
                }
            } else {
                $A[$k] = $v;
            }
        }
        return $A;
    }

    private static function pneg($A) {
        foreach ($A as $k => $v) {
            $A[$k] = array(_mf_chk(-$v[0]), $v[1]);
        }
        return $A;
    }

    private static function pmul($A, $B) {
        $R = array();
        foreach ($A as $ka => $va) {
            foreach ($B as $kb => $vb) {
                $R = self::padd($R, array($ka + $kb => self::qmul($va, $vb)));
            }
        }
        return $R;
    }

    private static function pdiv($A, $B) {
        if (count($B) == 0) {
            throw new MakeFractionError(MF_DIV0);
        }
        if (count($B) > 1) {
            throw new MakeFractionError(MF_NOFRAC);
        }
        reset($B);
        $k = key($B);
        return self::pmul($A, array(-$k => self::qinv($B[$k])));
    }

    private static function ppow($A, $e) {
        if ($e == 0) {
            if (count($A) == 0) {
                throw new MakeFractionError(MF_NOFRAC);   /* 0^0 */
            }
            return self::one();
        }
        $R = self::one();
        for ($i = 0; $i < abs($e); $i++) {
            $R = self::pmul($R, $A);
        }
        return ($e < 0) ? self::pdiv(self::one(), $R) : $R;
    }

    private function evalNode($n) {
        switch ($n[0]) {
            case 'num':
                return ($n[1] == 0) ? array() : array(0 => array($n[1], 1));
            case 'pi':
                return array(1 => array(1, 1));
            case 'grp':
            case 'pos':
                return $this->evalNode($n[1]);
            case 'neg':
                return self::pneg($this->evalNode($n[1]));
            case 'add':
                return self::padd($this->evalNode($n[1]), $this->evalNode($n[2]));
            case 'sub':
                return self::padd($this->evalNode($n[1]), self::pneg($this->evalNode($n[2])));
            case 'mul':
                return self::pmul($this->evalNode($n[1]), $this->evalNode($n[2]));
            case 'div':
                return self::pdiv($this->evalNode($n[1]), $this->evalNode($n[2]));
            case 'pow':
                $b = $this->evalNode($n[1]);
                $e = $this->evalNode($n[2]);
                if (count($e) == 0) {
                    $k = 0;
                } elseif (count($e) == 1 && isset($e[0]) && $e[0][1] == 1
                        && $e[0][0] >= -5 && $e[0][0] <= 5) {
                    $k = $e[0][0];
                } else {
                    throw new MakeFractionError(MF_POW);
                }
                return self::ppow($b, $k);
        }
        throw new MakeFractionError(MF_NOFRAC);
    }
}

/*
 * Shared engine. Returns
 *   array('ok'=>true, 'num'=>n, 'den'=>d, 'pi'=>0|1, 'double'=>bool, 'ast'=>tree)
 * or array('ok'=>false, 'msg'=>warning).
 * 'double' is true when expr has a // and no single /.
 */
function _mf_core($expr) {
    try {
        $parser = new MakeFractionParser($expr);
        $ast = $parser->parse();
        $P = $parser->evaluate();
        if (count($P) == 0) {
            $num = 0;
            $den = 1;
            $pi = 0;
        } elseif (count($P) == 1 && (isset($P[0]) || isset($P[1]))) {
            reset($P);
            $pi = key($P);
            $num = $P[$pi][0];
            $den = $P[$pi][1];
        } else {
            throw new MakeFractionError(MF_NOFRAC);
        }
        return array('ok' => true, 'num' => $num, 'den' => $den, 'pi' => $pi,
            'double' => $parser->isDouble(), 'ast' => $ast);
    } catch (MakeFractionError $e) {
        return array('ok' => false, 'msg' => $e->getMessage());
    } catch (Throwable $e) {
        return array('ok' => false, 'msg' => MF_NOFRAC);
    }
}
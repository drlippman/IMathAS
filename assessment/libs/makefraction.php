<?php
/*
 * makefraction.php
 *
 * Exact simplification of rational expressions for MOM / IMathAS.
 * A PHP port of reduce() from the inplacecalculator library, extended
 * with curly brackets, the symbol pi, repeating decimals and random fractions.
 *
 * Place this file in assessment/libs, then in Common Control:
 *     loadlibrary("makefraction")
 *     $f = makefraction("1/2+1/3")          gives "5/6"
 *
 * Functions
 *     makefraction(expr)                     reduced fraction, or "DNE"
 *     dispmakefraction(expr)                 " `...`"  for display
 *     fractionnumerator(expr)                numerator (coefficient of pi for pi fractions)
 *     fractiondenominator(expr)              denominator, always positive
 *     isreducedfraction(expr)                true iff expr is written as a reduced fraction
 *     makerepeatingdecimal(expr)             e.g. "4.bar(3)"
 *     repeatingdecimal2fraction(repeatdec)   e.g. "13/3"
 *     randfraction(denoms, min, max [,symbol])
 *     randfractions(denoms, min, max, n [,symbol [,order]])
 *     diffrandfractions(denoms, min, max, n [,symbol [,order]])
 *
 * Accepted symbols: 0-9, pi, + - * / // ^ ( ) [ ] { }, unary - and implicit
 * multiplication. ^ is limited to the powers -2,-1,0,1,2.
 *
 * Arithmetic is exact, using PHP integers. A result that would exceed the
 * 64-bit integer range gives "DNE" with the warning "numbers are too large".
 *
 * Written for PHP 7.0 and later.
 */

global $allowedmacros;
if (!isset($allowedmacros) || !is_array($allowedmacros)) {
    $allowedmacros = array();
}
array_push($allowedmacros, 'makefraction', 'dispmakefraction', 'fractionnumerator',
    'fractiondenominator', 'isreducedfraction', 'makerepeatingdecimal',
    'repeatingdecimal2fraction', 'randfraction', 'randfractions', 'diffrandfractions');

if (!defined('MF_DIV0')) {
    define('MF_DIV0', 'division by zero');
    define('MF_POW', 'powers other than -2,-1,0,1,2 are not accepted');
    define('MF_NOFRAC', "this doesn't make a fraction");
    define('MF_BIG', 'numbers are too large');
    define('MF_MAXDIGITS', 10000);
}

if (!class_exists('MakeFractionError')) {
    class MakeFractionError extends Exception {}
}

/* All warnings go through here, so their presentation can be changed in one place. */
function _mf_warn($msg) {
    echo $msg;
}

/* ------------------------------------------------------------------ */
/* Exact rational arithmetic. A rational is array(num, den), den > 0.  */
/* ------------------------------------------------------------------ */

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

function _mf_qadd($a, $b) {
    $g = _mf_gcd($a[1], $b[1]);
    $n = _mf_chk(_mf_chk($a[0] * intdiv($b[1], $g)) + _mf_chk($b[0] * intdiv($a[1], $g)));
    $d = _mf_chk(intdiv($a[1], $g) * $b[1]);
    return _mf_q($n, $d);
}

function _mf_qmul($a, $b) {
    $g1 = _mf_gcd($a[0], $b[1]);
    $g2 = _mf_gcd($b[0], $a[1]);
    $n = _mf_chk(intdiv($a[0], $g1) * intdiv($b[0], $g2));
    $d = _mf_chk(intdiv($a[1], $g2) * intdiv($b[1], $g1));
    return _mf_q($n, $d);
}

function _mf_qinv($a) {
    if ($a[0] == 0) {
        throw new MakeFractionError(MF_DIV0);
    }
    return _mf_q($a[1], $a[0]);
}

/* Compare two rationals: -1, 0 or 1. */
function _mf_qcmp($a, $b) {
    $l = $a[0] * $b[1];
    $r = $b[0] * $a[1];
    if (!is_int($l) || !is_int($r)) {
        $l = $a[0] / $a[1];
        $r = $b[0] / $b[1];
    }
    return ($l < $r) ? -1 : (($l > $r) ? 1 : 0);
}

/* ------------------------------------------------------------------ */
/* Polynomials in pi: array(power => rational), zero terms omitted.    */
/* "1+pi" is a legal intermediate; it just isn't a fraction at the end.*/
/* ------------------------------------------------------------------ */

function _mf_one() {
    return array(0 => array(1, 1));
}

function _mf_padd($A, $B) {
    foreach ($B as $k => $v) {
        if (isset($A[$k])) {
            $s = _mf_qadd($A[$k], $v);
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

function _mf_pneg($A) {
    foreach ($A as $k => $v) {
        $A[$k] = array(_mf_chk(-$v[0]), $v[1]);
    }
    return $A;
}

function _mf_pmul($A, $B) {
    $R = array();
    foreach ($A as $ka => $va) {
        foreach ($B as $kb => $vb) {
            $R = _mf_padd($R, array($ka + $kb => _mf_qmul($va, $vb)));
        }
    }
    return $R;
}

function _mf_pdiv($A, $B) {
    if (count($B) == 0) {
        throw new MakeFractionError(MF_DIV0);
    }
    if (count($B) > 1) {
        throw new MakeFractionError(MF_NOFRAC);
    }
    reset($B);
    $k = key($B);
    return _mf_pmul($A, array(-$k => _mf_qinv($B[$k])));
}

function _mf_ppow($A, $e) {
    if ($e == 0) {
        if (count($A) == 0) {
            throw new MakeFractionError(MF_NOFRAC);   /* 0^0 */
        }
        return _mf_one();
    }
    $R = _mf_one();
    for ($i = 0; $i < abs($e); $i++) {
        $R = _mf_pmul($R, $A);
    }
    return ($e < 0) ? _mf_pdiv(_mf_one(), $R) : $R;
}

/* ------------------------------------------------------------------ */
/* Tokenizer and parser                                                */
/* ------------------------------------------------------------------ */

/* Every character is checked before any parsing, so the first bad
   character is reported even when the expression is also malformed. */
function _mf_tokenize($s) {
    $t = preg_replace('/\s+/u', '', (string)$s);
    if ($t === null) {
        $t = preg_replace('/\s+/', '', (string)$s);
    }
    $chars = preg_split('//u', $t, -1, PREG_SPLIT_NO_EMPTY);
    if ($chars === false) {
        $chars = str_split($t);
    }
    $toks = array();
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
            $i += 2;
        } elseif (strlen($c) == 1 && strpos('+-*/^', $c) !== false) {
            $toks[] = array('op', $c);
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
    return $toks;
}

/*
 * Grammar
 *   expr    := term (('+' | '-') term)*
 *   term    := unary (('*' | '/' | '//') unary | implicit power)*
 *   unary   := ('-' | '+') unary | power
 *   power   := primary ['^' unary]
 *   primary := number | pi | '(' expr ')' | '[' expr ']' | '{' expr '}'
 * so -2^2 = -4, 2^-1 = 1/2, 3pi/4 = (3pi)/4 and 9(1) = 9*1.
 * AST nodes: num, pi, grp, neg, pos, add, sub, mul, div, pow.
 */
if (!class_exists('MakeFractionParser')) {
    class MakeFractionParser {
        private $t;
        private $p = 0;

        public function __construct($toks) {
            $this->t = $toks;
        }

        public function parse() {
            $e = $this->expr();
            if ($this->p !== count($this->t)) {
                throw new MakeFractionError(MF_NOFRAC);
            }
            return $e;
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
    }
}

function _mf_eval($n) {
    switch ($n[0]) {
        case 'num':
            return ($n[1] == 0) ? array() : array(0 => array($n[1], 1));
        case 'pi':
            return array(1 => array(1, 1));
        case 'grp':
        case 'pos':
            return _mf_eval($n[1]);
        case 'neg':
            return _mf_pneg(_mf_eval($n[1]));
        case 'add':
            return _mf_padd(_mf_eval($n[1]), _mf_eval($n[2]));
        case 'sub':
            return _mf_padd(_mf_eval($n[1]), _mf_pneg(_mf_eval($n[2])));
        case 'mul':
            return _mf_pmul(_mf_eval($n[1]), _mf_eval($n[2]));
        case 'div':
            return _mf_pdiv(_mf_eval($n[1]), _mf_eval($n[2]));
        case 'pow':
            $b = _mf_eval($n[1]);
            $e = _mf_eval($n[2]);
            if (count($e) == 0) {
                $k = 0;
            } elseif (count($e) == 1 && isset($e[0]) && $e[0][1] == 1
                    && $e[0][0] >= -2 && $e[0][0] <= 2) {
                $k = $e[0][0];
            } else {
                throw new MakeFractionError(MF_POW);
            }
            return _mf_ppow($b, $k);
    }
    throw new MakeFractionError(MF_NOFRAC);
}

/*
 * Shared engine. Returns
 *   array('ok'=>true, 'num'=>n, 'den'=>d, 'pi'=>0|1, 'double'=>bool, 'ast'=>tree)
 * or array('ok'=>false, 'msg'=>warning).
 * 'double' is true when expr has a // and no single /.
 */
function _mf_core($expr) {
    try {
        $toks = _mf_tokenize($expr);
        $single = 0;
        $double = 0;
        foreach ($toks as $tk) {
            if ($tk[0] === 'op' && $tk[1] === '/') {
                $single++;
            } elseif ($tk[0] === 'op' && $tk[1] === '//') {
                $double++;
            }
        }
        $parser = new MakeFractionParser($toks);
        $ast = $parser->parse();
        $P = _mf_eval($ast);
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
            'double' => ($double > 0 && $single == 0), 'ast' => $ast);
    } catch (MakeFractionError $e) {
        return array('ok' => false, 'msg' => $e->getMessage());
    } catch (Throwable $e) {
        return array('ok' => false, 'msg' => MF_NOFRAC);
    }
}

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

/* ------------------------------------------------------------------ */
/* Public functions                                                    */
/* ------------------------------------------------------------------ */

/*
 * makefraction(expr)
 * The result uses '//' when every division in expr is '//', otherwise '/'.
 */
function makefraction($expr) {
    // try regex parse, use parser only if needed
    $r = _mf_regexparse($expr);
    if (!$r['ok']) {
        $r = _mf_core($expr);
    }
    if (!$r['ok']) {
        _mf_warn($r['msg']);
        return 'DNE';
    }
    return _mf_format($r['num'], $r['den'], $r['pi'], $r['double']);
}

function dispmakefraction($expr) {
    return ' `' . makefraction($expr) . '`';
}

function _mf_regexparse($expr) {
    $pattern = '~
        ^\s*
        (?:(?P<neg>[+-])\s*(?=\())?\s*       # optional outer sign, only if a "(" follows
        (?P<lp1>\()?\s*                      # optional "(" around numerator
        (?=[+-]?(?:\d|pi))\s*                # numerator needs a digit or pi
        (?P<coef>[+-]?\d*)\s*                # sign and/or digits
        (?P<pi>pi)?\s*                       # optional pi
        (?(<lp1>)\))\s*                      # ")" only if "(" was opened
        (?:                                  # --- optional denominator ---
            \s*(?P<div>//?)\s*               # division symbol / or //
            (?P<lp2>\()?\s*                  # optional "(" around denominator
            (?P<den>[+-]?\d+)\s*             # denominator with optional sign
            (?(<lp2>)\))                     # ")" only if "(" was opened
        )?
        \s*$
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
    /*$negs = 0;
    $d = _mf_desc($r['ast'], $negs);
    if ($d === null || $negs > 1) {
        return false;
    }
    if ($d[0] == 0) {
        return $d[1] == 1 && $d[2] == 0 && $negs == 0;
    }
    return _mf_gcd($d[0], $d[1]) == 1;
    */
}

function _mf_strip($n, &$negs) {
    while (true) {
        if ($n[0] === 'grp') {
            $n = $n[1];
        } elseif ($n[0] === 'neg') {
            $negs++;
            $n = $n[1];
        } else {
            return $n;
        }
    }
}

/* Describes an allowed written form as array(coefficient, denominator, pi),
   or null when the form is not one a reduced fraction may take. */
function _mf_desc($n, &$negs) {
    $n = _mf_strip($n, $negs);
    switch ($n[0]) {
        case 'num':
            return array($n[1], 1, 0);
        case 'pi':
            return array(1, 1, 1);
        case 'mul':
            $a = _mf_desc($n[1], $negs);
            $b = _mf_desc($n[2], $negs);
            if ($a === null || $b === null || $a[2] + $b[2] != 1) {
                return null;
            }
            $p = $a[2] ? $a : $b;          /* the side carrying pi */
            $q = $a[2] ? $b : $a;          /* the side without pi */
            if ($p[0] != 1 || ($p[1] > 1 && $q[1] > 1)) {
                return null;
            }
            if ($q[0] < 1 || ($q[0] == 1 && $q[1] == 1)) {
                return null;               /* 0pi and 1pi are not reduced */
            }
            return array($q[0], $p[1] * $q[1], 1);
        case 'div':
            $a = _mf_desc($n[1], $negs);
            $b = _mf_desc($n[2], $negs);
            if ($a === null || $b === null || $a[1] != 1 || $b[1] != 1
                    || $b[2] != 0 || $b[0] < 2 || $a[0] == 0) {
                return null;
            }
            return array($a[0], $b[0], $a[2]);
    }
    return null;
}

/* makerepeatingdecimal("15/7") = "2.bar(142857)", makerepeatingdecimal("1/4") = ".25" */
function makerepeatingdecimal($expr) {
    $r = _mf_core($expr);
    if (!$r['ok']) {
        _mf_warn($r['msg']);
        return 'DNE';
    }
    if ($r['pi']) {
        _mf_warn('pi fractions do not have repeating decimals');
        return 'DNE';
    }
    try {
        return _mf_decimal($r['num'], $r['den']);
    } catch (MakeFractionError $e) {
        _mf_warn($e->getMessage());
        return 'DNE';
    }
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

/* repeatingdecimal2fraction("1.00bar(3)") = "301/300" */
function repeatingdecimal2fraction($repeatdec) {
    $s = preg_replace('/\s+/', '', (string)$repeatdec);
    $ok = preg_match('/^(-?)(\d*)(?:\.(\d*)(?:bar\((\d+)\))?)?$/', $s, $m);
    $I = isset($m[2]) ? $m[2] : '';
    $N = isset($m[3]) ? $m[3] : '';
    $R = isset($m[4]) ? $m[4] : '';
    if (!$ok || ($I === '' && $N === '' && $R === '')) {
        _mf_warn($repeatdec . ' should be like -2.33 or 2.1bar(33)');
        return 'DNE';
    }
    try {
        if (strlen(ltrim($I, '0')) > 18 || strlen($N) > 18 || strlen($R) > 18) {
            throw new MakeFractionError(MF_BIG);
        }
        $iv = (int)($I === '' ? '0' : $I);
        $p10 = _mf_pow10(strlen($N));
        if ($R === '') {
            $num = _mf_chk(_mf_chk($iv * $p10) + (int)($N === '' ? '0' : $N));
            $den = $p10;
        } else {
            $M = _mf_pow10(strlen($R)) - 1;
            $num = _mf_chk(_mf_chk(_mf_chk($iv * $p10) * $M)
                 + _mf_chk((int)($N === '' ? '0' : $N) * $M));
            $num = _mf_chk($num + (int)$R);
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

function _mf_pow10($k) {
    $r = 1;
    for ($i = 0; $i < $k; $i++) {
        $r = _mf_chk($r * 10);
    }
    return $r;
}

/* ------------------------------------------------------------------ */
/* Random fractions                                                    */
/* ------------------------------------------------------------------ */

/* Uses the question's seeded generator when IMathAS provides one. */
function _mf_randint($a, $b) {
    if (isset($GLOBALS['RND']) && is_object($GLOBALS['RND']) && method_exists($GLOBALS['RND'], 'rand')) {
        return $GLOBALS['RND']->rand($a, $b);
    }
    return mt_rand($a, $b);
}

function _mf_randreal($min, $max) {
    return $min + ($max - $min) * _mf_randint(0, 1000000) / 1000000;
}

/*
 * Checks the arguments and finds, for each listed denominator d, the range
 * lo..hi of numerators with min <= n/d <= max. Only numerators coprime to d
 * are used, so every result has exactly a listed denominator (integers only
 * when 1 is listed). Denominators with no usable numerator are dropped.
 * Returns null (after a warning) on bad arguments.
 */
function _mf_randsetup($denoms, $min, $max, $symbol, $order) {
    if (!in_array($symbol, array('/', '//', 'pi/', 'pi//'), true)) {
        _mf_warn("Invalid symbol '$symbol'. Allowed: '/', '//', 'pi/', 'pi//'.");
        return null;
    }
    if (!in_array($order, array('', 'inc', 'dec'), true)) {
        _mf_warn("Invalid order '$order'. Allowed: '', 'inc', 'dec'.");
        return null;
    }
    if (!is_array($denoms)) {
        $denoms = explode(',', (string)$denoms);
    }
    if (count($denoms) == 0) {
        _mf_warn('denoms must be positive integers');
        return null;
    }
    foreach ($denoms as $d) {
        if (!is_numeric($d) || floor($d) != $d || $d < 1) {
            _mf_warn('denoms must be positive integers');
            return null;
        }
    }
    if (!is_numeric($min) || !is_numeric($max)) {
        _mf_warn('min and max must be numbers');
        return null;
    }
    $min = (float)$min;
    $max = (float)$max;
    $feas = array();
    if ($min <= $max) {
        foreach ($denoms as $d) {
            $d = (int)$d;
            $lo = (int)ceil($min * $d - 1e-9);
            $hi = (int)floor($max * $d + 1e-9);
            if ($lo <= $hi && _mf_coprimeup($lo, $d, $hi) !== null) {
                $feas[] = array($d, $lo, $hi);
            }
        }
    }
    return array('min' => $min, 'max' => $max, 'feas' => $feas,
        'pi' => (substr($symbol, 0, 2) === 'pi') ? 1 : 0,
        'double' => (substr($symbol, -2) === '//'));
}

/* Smallest n in from..hi coprime to d, or null. A coprime numerator
   occurs within any d consecutive integers, so at most d steps. */
function _mf_coprimeup($from, $d, $hi) {
    for ($n = $from; $n <= $hi && $n < $from + $d; $n++) {
        if (_mf_gcd($n, $d) == 1) {
            return $n;
        }
    }
    return null;
}

/* Largest n in lo..from coprime to d, or null. */
function _mf_coprimedown($from, $d, $lo) {
    for ($n = $from; $n >= $lo && $n > $from - $d; $n--) {
        if (_mf_gcd($n, $d) == 1) {
            return $n;
        }
    }
    return null;
}

/* Random real in [min,max], random listed denominator d, then the nearest
   n/d in range with n coprime to d, so n/d is already reduced. */
function _mf_drawone($S) {
    $x = _mf_randreal($S['min'], $S['max']);
    $f = $S['feas'][_mf_randint(0, count($S['feas']) - 1)];
    $d = $f[0];
    $t = $x * $d;
    $below = _mf_coprimedown((int)max($f[1] - 1, min($f[2], floor($t))), $d, $f[1]);
    $above = _mf_coprimeup((int)min($f[2] + 1, max($f[1], ceil($t))), $d, $f[2]);
    if ($below === null) {
        $n = $above;
    } elseif ($above === null) {
        $n = $below;
    } else {
        $db = $t - $below;
        $da = $above - $t;
        if (abs($db - $da) < 1e-9) {
            $n = _mf_randint(0, 1) ? $above : $below;   /* two nearest: pick one */
        } else {
            $n = ($da < $db) ? $above : $below;
        }
    }
    return _mf_q($n, $d);
}

function _mf_randfinish($list, $S, $order) {
    if ($order === 'inc') {
        usort($list, '_mf_qcmp');
    } elseif ($order === 'dec') {
        usort($list, function ($a, $b) { return _mf_qcmp($b, $a); });
    }
    $out = array();
    foreach ($list as $q) {
        $out[] = _mf_format($q[0], $q[1], $S['pi'], $S['double']);
    }
    return $out;
}

function _mf_checkn($n) {
    if (!is_numeric($n) || floor($n) != $n || $n <= 0 || $n > 100) {
        _mf_warn('n must be a positive integer less than 100');
        return null;
    }
    return (int)$n;
}

/* randfraction([2,3,5,7], 0, 1) could be "2/5" or "1". */
function randfraction($denoms, $min, $max, $symbol = '/') {
    $S = _mf_randsetup($denoms, $min, $max, $symbol, '');
    if ($S === null || count($S['feas']) == 0) {
        return 'DNE';
    }
    $q = _mf_drawone($S);
    return _mf_format($q[0], $q[1], $S['pi'], $S['double']);
}

/* n random reduced fractions; values may repeat. ["DNE"] if impossible. */
function randfractions($denoms, $min, $max, $n, $symbol = '/', $order = '') {
    $S = _mf_randsetup($denoms, $min, $max, $symbol, $order);
    $n = _mf_checkn($n);
    if ($S === null || $n === null || ($n > 0 && count($S['feas']) == 0)) {
        return array('DNE');
    }
    $list = array();
    for ($i = 0; $i < $n; $i++) {
        $list[] = _mf_drawone($S);
    }
    return _mf_randfinish($list, $S, $order);
}

/* n different random reduced fractions; ["DNE"] if impossible,
   including when there are fewer than n different fractions. */
function diffrandfractions($denoms, $min, $max, $n, $symbol = '/', $order = '') {
    $S = _mf_randsetup($denoms, $min, $max, $symbol, $order);
    $n = _mf_checkn($n);
    if ($S === null || $n === null) {
        return array('DNE');
    }
    if ($n == 0) {
        return array();
    }
    if (count($S['feas']) == 0) {
        return array('DNE');
    }

    $total = 0;
    foreach ($S['feas'] as $f) {
        $total += $f[2] - $f[1] + 1;
    }
    $pool = null;
    /* When the total is very small, or when n is fairly big relative to total but
       total is still reasonable, generate pool and pick from it */
    if ($total <= 100 || ($n > .2*$total && $total <=1000)) {
        $pool = array();
        foreach ($S['feas'] as $f) {
            for ($k = $f[1]; $k <= $f[2]; $k++) {
                if (_mf_gcd($k, $f[0]) != 1) {
                    continue;
                }
                $q = _mf_q($k, $f[0]);
                $pool[$q[0] . '/' . $q[1]] = $q;
            }
        }
        if (count($pool) < $n) {
            return array('DNE');
        }
        // shuffle pool and return first n values
        $GLOBALS['RND']->shuffle($pool);
        return _mf_randfinish(array_slice($pool, $n), $S, $order); 
    }

    // total too big, so do sampling

    $picked = array();
    $tries = 0;
    $limit = min(200 * $n, 10000);
    while (count($picked) < $n && $tries < $limit) {
        $q = _mf_drawone($S);
        $key = $q[0] . '/' . $q[1];
        if (!isset($picked[$key])) {
            $picked[$key] = $q;
        }
        $tries++;
    }
    if (count($picked) < $n) {
        return array('DNE');
    }
    return _mf_randfinish(array_values($picked), $S, $order);
}

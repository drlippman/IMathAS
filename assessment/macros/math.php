<?php

// math and function evaluation macros

array_push(
    $GLOBALS['allowedmacros'],
    'gcd',
    'lcm',
    'mod',
    'definefunc',
    'evalnumstr',
    'evalfunc',
    'ifthen',
    'cases',
    'decimaltofraction',
    'is_nicenumber',
    'getnumbervalue',
    'normalizefunc'
);


/*
function gcd($n,$m){ //greatest common divisor
    if (!is_numeric($n) || !is_numeric($m)) {
        echo "gcd requires numeric inputs.";
        return 1;
    }
	$m = (int) round(abs($m));
	$n = (int) round(abs($n));
    if($m==0 && $n==0) {return 1;} // not technically correct, but will avoid divide by 0 issues in the case of bad input
	if($m==0)return$n;
	if($n==0)return$m;
	return $m<$n?gcd($m,$n%$m):gcd($n,$m%$n);
}
*/
function gcd(...$args) {
    if (count($args) == 1 && is_array($args[0])) {
        $args = $args[0];
    }
    $g = null;
    foreach ($args as $v) {
        if (!is_numeric($v)) {
            echo "gcd requires numeric inputs.";
            return 1;
        } else if ($g === null) {
            $g = (int) round(abs($v));
        } else {
            $n = (int) round(abs($v));
            if ($g == 0 && $n == 0) {
                return 1;
            } // not technically correct, but will avoid divide by 0 issues in the case of bad input
            while ($n > 0) {
                $t = $n;
                $n = $g % $n;
                $g = $t;
            }
        }
    }
    return $g;
}
/*function lcm($n, $m) //least common multiple
{
   return round($m*($n/gcd($n,$m)));
}
*/
function lcm(...$args) {
    $g = null;
    foreach ($args as $v) {
        if (!is_numeric($v)) {
            echo "lcm requires numeric inputs.";
            return 1;
        } else if ($g === null) {
            $g = (int) round($v);
        } else {
            $v = (int) round($v);
            $g = round($g * ($v / gcd($g, $v)));
        }
    }
    return $g;
}


function definefunc($func, $varlist) {
    $vars = listtoarray($varlist);
    /*$toparen = implode('|',$vars);
	if ($toparen != '') {
		$reg = "/(" . $toparen . ")(" . $toparen . ')$/';
		  $func= preg_replace($reg,"($1)($2)",$func);
		  $reg = "/(" . $toparen . ")(sqrt|ln|log|sin|cos|tan|sec|csc|cot|abs)/";
		  $func= preg_replace($reg,"($1)$2",$func);
		  $reg = "/(" . $toparen . ")(" . $toparen . ')([^a-df-zA-Z\(])/';
		  $func= preg_replace($reg,"($1)($2)$3",$func);
		  $reg = "/([^a-zA-Z])(" . $toparen . ")([^a-zA-Z])/";
		  $func= preg_replace($reg,"$1($2)$3",$func);
		  //need second run through to catch x*x
		  $func= preg_replace($reg,"$1($2)$3",$func);
		  $reg = "/^(" . $toparen . ")([^a-zA-Z])/";
		  $func= preg_replace($reg,"($1)$2",$func);
		  $reg = "/([^a-zA-Z])(" . $toparen . ")$/";
		  $func= preg_replace($reg,"$1($2)",$func);
		  $reg = "/^(" . $toparen . ")$/";
		  $func= preg_replace($reg,"($1)",$func);

		  $reg = "/\(\((" . $toparen . ")\)\)/";
		  $func= preg_replace($reg,"($1)",$func);
		  $func= preg_replace($reg,"($1)",$func);
	}
	*/
    return array($func, $varlist);
}


function evalnumstr($str, $docomplex = false) {
    if (trim($str) === '') {
        return '';
    }
    return evalMathParser($str, $docomplex);
}


function evalfunc($farr) {
    $args = func_get_args();
    array_shift($args);
    if (is_array($farr)) {
        list($func, $varlist) = $farr;
        //$skipextracleanup = true;
    } else {
        $func = $farr;
        $varlist = array_shift($args);
        //$skipextracleanup = false;
    }
    $skipextracleanup = false;
    $func = makepretty($func);
    $vars = listtoarray($varlist);
    if (count($args) == count($vars) + 1) {
        $skipextracleanup = true;
        array_pop($args);
    } else if (count($vars) != count($args)) {
        echo "Number of inputs to function doesn't match number of variables";
        return false;
    }
    $isnum = true;
    for ($i = 0; $i < count($args); $i++) {
        if (!is_numeric($args[$i])) {
            $isnum = false;
        }
    }
    foreach ($vars as $k => $v) {
        $vars[$k] = preg_replace('/[^\w]/', '', $v);
        if ($vars[$k] == '') {
            echo "Invalid variable";
            return false;
        }
    }
    $toparen = implode('|', $vars);

    if ($isnum) {
        $func = makeMathFunction($func, implode(',', $vars), [], '', true);
        if ($func === false) {
            if ($skipextracleanup) {
                return false;
            } else {
                return '';
            }
        }
        foreach ($vars as $i => $var) {
            if (is_array($args[$i]) || !is_numeric($args[$i])) {
                echo 'invalid input to evalfunc';
                return false;
            }
            $varvals[$var] = $args[$i];
        }
        return $func($varvals);
    } else { //just replacing
        if ($toparen != '') { // && !$skipextracleanup) {
            $reg = "/(" . $toparen . ")(" . $toparen . ')$/';
            $func = preg_replace($reg, "($1)($2)", $func);
            $reg = "/(" . $toparen . ")(sqrt|ln|log|sin|cos|tan|sec|csc|cot|abs)/";
            $func = preg_replace($reg, "($1)$2", $func);
            $reg = "/(" . $toparen . ")(" . $toparen . ')([^a-df-zA-Z\(])/';
            $func = preg_replace($reg, "($1)($2)$3", $func);
            $reg = "/([^a-zA-Z])(" . $toparen . ")([^a-zA-Z])/";
            $func = preg_replace($reg, "$1($2)$3", $func);
            //need second run through to catch x*x
            $func = preg_replace($reg, "$1($2)$3", $func);
            $reg = "/^(" . $toparen . ")([^a-zA-Z])/";
            $func = preg_replace($reg, "($1)$2", $func);
            $reg = "/([^a-zA-Z])(" . $toparen . ")$/";
            $func = preg_replace($reg, "$1($2)", $func);
            $reg = "/^(" . $toparen . ")$/";
            $func = preg_replace($reg, "($1)", $func);

            $reg = "/\(\((" . $toparen . ")\)\)/";
            $func = preg_replace($reg, "($1)", $func);
            $func = preg_replace($reg, "($1)", $func);
        }
        foreach ($vars as $i => $var) {
            if (is_array($args[$i])) {
                echo 'invalid input to evalfunc';
                return false;
            }
            $func = str_replace("($var)", "({$args[$i]})", $func);
        }

        if (!$skipextracleanup) {
            $reg = '/^\((\d*?\.?\d*?)\)([^\d\.])/';
            $func = preg_replace($reg, "$1$2", $func);
            $reg = '/^\(([a-zA-Z])\)([^a-zA-Z])/';
            $func = preg_replace($reg, "$1$2", $func);

            //$reg = '/([^\d\.])\((\d*?\.?\d*?)\)$/';
            //$func= preg_replace($reg,"$1$2",$func);
            $reg = '/([^a-zA-Z])\(([a-zA-Z])\)$/';
            $func = preg_replace($reg, "$1$2", $func);

            //$reg = '/([^\d\.])\((\d*?\.?\d*?)\)([^\d\.])/';
            //$func= preg_replace($reg,"$1$2$3",$func);
            $reg = '/([^a-zA-Z])\(([a-zA-Z])\)([^a-zA-Z])/';
            $func = preg_replace($reg, "$1$2$3", $func);
        }
        return $func;
    }
}


function ifthen($c, $t, $f) {
    return $c ? $t : $f;
}

function cases($val, $inputs, $outputs, $default = '', $tol = .0015) {
    if (is_array($val)) {
        echo "First input to cases must be a number or string";
        return '';
    } else if (!is_array($inputs) || !is_array($outputs)) {
        echo "Second and third inputs to cases must be arrays";
        return '';
    } else if (count($inputs) != count($outputs)) {
        echo "Second and third inputs to cases must have same number of elements";
        return '';
    }
    $abstol = false;
    if (strval($tol)[0] == '|') {
        $tol = floatval(substr($tol, 1));
        $abstol = true;
    }
    foreach ($inputs as $k => $x) {
        if (is_numeric($x) && is_numeric($val)) {
            if ($abstol) {
                if (abs($x - $val) < $tol + 1E-12) {
                    return $outputs[$k];
                }
            } else {
                if (abs($x - $val) / (abs($x) + .0001) < $tol + 1E-12) {
                    return $outputs[$k];
                }
            }
        } else if ((string) $x === (string) $val) {
            return $outputs[$k];
        }
    }
    return $default;
}


//adapted from http://www.mindspring.com/~alanh/fracs.html
function decimaltofraction($d, $format = "fraction", $maxden = 10000000) {
    if (!is_numeric($d)) {
        echo 'decimaltofraction expects numeric input';
        return $d;
    }
    if (abs(floor($d) - $d) < 1e-12) {
        return floor($d);
    }
    if (abs($d) < 1e-12) {
        return '0';
    }
    $maxden = min($maxden, 1e16);

    if ($d < 0) {
        $sign = '-';
    } else {
        $sign = '';
    }
    $d = abs($d);
    $numerators = array(0, 1);
    $denominators = array(1, 0);

    $d2 = $d;
    $calcD = -1;
    $prevCalcD = -1;
    for ($i = 2; $i < 1000; $i++) {
        $L2 = floor($d2);
        $newdenom = $L2 * $denominators[$i - 1] + $denominators[$i - 2];
        if (abs($newdenom) > $maxden) {
            $i--;
            break;
        }
        $numerators[$i] = $L2 * $numerators[$i - 1] + $numerators[$i - 2];
        //if (Math.abs(numerators[i]) > maxNumerator) return;
        $denominators[$i] = $newdenom;

        $calcD = $numerators[$i] / $denominators[$i];
        if ($calcD == $prevCalcD) {
            break;
        }

        //appendFractionsOutput(numerators[i], denominators[i]);

        //if ($calcD == $d) { break;}
        if (abs($calcD - $d) < 1e-14) {
            break;
        }

        $prevCalcD = $calcD;

        $d2 = 1 / ($d2 - $L2);
    }
    if ($i < 1000 && abs($numerators[$i] / $denominators[$i] - $d) > 1e-12) {
        return $sign . $d;
    }
    if ($format == "mixednumber") {
        $w = floor($numerators[$i] / $denominators[$i]);
        if ($w > 0) {
            $out = "{$sign}$w";
            $n = $numerators[$i] - $w * $denominators[$i];
            if (abs($n) > 1e-12) {
                $out .= " $n/" . $denominators[$i];
            }
            return $out;
        } else {
            return $sign . $numerators[$i] . '/' . $denominators[$i];
        }
    } else {
        return $sign . $numerators[$i] . '/' . $denominators[$i];
    }
}

function is_nicenumber($x) {
    return (is_numeric($x) && is_finite($x));
}

function getnumbervalue($a) {
    $a = str_replace(',', '', $a);
    if (is_numeric($a)) {
        return $a * 1;
    } else {
        $a = evalMathParser($a);
        return $a;
    }
}

function mod($p, $n) {
    if ($n == 0) {
        return false;
    }
    return (($p % $n) + $n) % $n;
}

function normalizefunc($a, $vars = "x") {
    $variables = array_values(array_filter(array_map('trim', explode(",", $vars)), 'strlen'));
    $ofunc = array();
    for ($i = 0; $i < count($variables); $i++) {
        //find f() function variables
        if (strpos($variables[$i], '()') !== false) {
            $ofunc[] = substr($variables[$i], 0, strpos($variables[$i], '('));
            $variables[$i] = substr($variables[$i], 0, strpos($variables[$i], '('));
        }
    }
    $vlist = implode(',', $variables);
    $flist = implode(',', $ofunc);
    $afunc = parseMathQuiet($a, $vlist, [], $flist);
    if ($afunc === false) {
        return false;
    }

    return $afunc->normalizeTreeString();
}

/** not public **/
function evalMathPHP($str, $vl) {
    return evalReturnValue('return (' . mathphp($str, $vl) . ');', $str);
}
function evalReturnValue($str, $errordispstr = '', $vars = array()) {
    $preevalerror = error_get_last();
    foreach ($vars as $v => $val) {
        ${$v} = $val;
    }
    try {
        $res = @eval($str);
    } catch (Throwable $t) {
        if ($GLOBALS['myrights'] > 10) {
            echo '<p>Caught error in evaluating a function in this question: ';
            echo Sanitize::encodeStringForDisplay($t->getMessage());
            if ($errordispstr != '') {
                echo ' while evaluating ' . htmlspecialchars($errordispstr);
            }
            echo '</p>';
        }
        return false;
    }
    if ($res === false) {
        $error = error_get_last();
        if ($GLOBALS['myrights'] > 10 && $error != $preevalerror) {
            echo '<p>Caught error in evaluating a function in this question: ', $error['message'];
            if ($errordispstr != '') {
                echo ' while evaluating ' . htmlspecialchars($errordispstr);
            }
            echo '</p>';
        }
    } else {
        $error = error_get_last();
        if ($error && $error != $preevalerror && $error['type'] == E_ERROR && $GLOBALS['myrights'] > 10) {
            echo '<p>Caught error in evaluating a function in this question: ', $error['message'];
            if ($errordispstr != '') {
                echo ' while evaluating ' . htmlspecialchars($errordispstr);
            }
            echo '</p>';
        }
    }
    return $res;
}

function cleanround($n,$d) {
    $n = round($n,$d);
    return ($n==0)?0:$n;
}

function getRoundNumber($val) {
    $str = (string) $val;
    $str = str_replace('e', 'E', $str);
    if (($s = strpos($str, '.')) === false) { //no decimal places
        return 0;
    } else if (($p = strpos($str, 'E')) !== false) { //scientific notation
        $exp = ceil(-log10(abs($val)));
        if ($p - $s == 2 && $str[$s + 1] == '0') { //is 3.0E-5 type
            return ($exp);
        } else {
            return ($exp + $p - $s - 1);
        }
    } else { //regular non-scientific notation
        return (strlen($str) - $s - 1);
    }
}

function evalbasic($str, $doextra = false, $zerofornan = false) {
    global $myrights;
    $str = str_replace(',', '', $str);
    $str = preg_replace('/(\d)pi/', '$1*pi', $str);
    $str = str_replace('pi', '3.141592653', $str);
    $str = clean($str);
    if (is_numeric($str)) {
        return $str;
    } else if ($doextra || preg_match('/[^\d+\-\/\*\.\(\)]/', $str)) {
        if ($doextra) {
            $ret = evalnumstr($str);
            if ($zerofornan && !is_nicenumber($ret)) {
                return 0;
            }
            return $ret;
        } else {
            return $str;
        }
    } else if ($str === '') {
        return 0;
    } else {
        $ret = null;
        try {
            eval("\$ret = $str;");
        } catch (Throwable $t) {
            if ($myrights > 10) {
                echo '<p>Caught error in evaluating ' . Sanitize::encodeStringForDisplay($str) . ' in this question: ';
                echo Sanitize::encodeStringForDisplay($t->getMessage());
                echo '</p>';
            }
            if (!isset($ret) || !is_nicenumber($ret)) {
                return 0;
            }
        }
        if ($zerofornan && !is_nicenumber($ret)) {
            return 0;
        }
        return $ret;
    }
}



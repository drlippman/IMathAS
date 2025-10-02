<?php

// Array macro functions

array_push(
    $GLOBALS['allowedmacros'],
    'mergearrays',
    'arrayfindindex',
    'arrayfindindices',
    'stringtoarray',
    'jointsort',
    'listtoarray',
    'arraytolist',
    'joinarray',
    'calclisttoarray',
    'sortarray',
    'splicearray',
    'consecutive',
    'calconarray',
    'keepif',
    'multicalconarray',
    'calconarrayif',
    'sumarray',
    'intersectarrays',
    'diffarrays',
    'unionarrays',
    'subarray',
    'fillarray',
    'ABarray',
    'arrayhasduplicates'
);

function mergearrays() {
    $args = func_get_args();
    foreach ($args as $k => $arg) {
        if (!is_array($arg)) {
            $args[$k] = array($arg);
        }
    }
    return call_user_func_array('array_merge', $args);
}
function arrayfindindex($n, $h) {
    return array_search($n, $h);
}
function arrayfindindices($n, $h) {
    return array_keys($h, $n);
}
function stringtoarray($str) {
    $str_array = array();
    $len = strlen($str);
    for ($i = 0; $i < $len; $i++) {
        $str_array[] = $str[$i];
    }
    return $str_array;
}
function jointsort() {
    $in = func_get_args();
    if (count($in) < 2 || !is_array($in[0]) || !is_array($in[1])) {
        echo "jointsort needs at least two input arrays";
        return array();
    }
    $a = array_shift($in);
    for ($i = 0; $i < count($in); $i++) {
        if (!is_array($in[$i]) || count($in[$i]) !== count($a)) {
            echo "inputs to jointsort need to be arrays of same length";
            return $in;
        }
    }
    asort($a);
    $out = array();
    foreach ($a as $k => $v) {
        for ($i = 0; $i < count($in); $i++) {
            $out[$i][] = $in[$i][$k];
        }
    }
    $a = array_values($a);
    array_unshift($out, $a);
    return $out;
}

function listtoarray($l) {
    if (func_num_args() > 1) {
        echo "Warning:  listtoarray expects one argument, more than one provided";
    }
    if ($l === '') {
        return [];
    }
    if (is_array($l)) {
        return $l;
    }
    return array_map('trim', explode(',', $l));
}


function arraytolist($a, $sp = false) {
    if (!is_array($a)) {
        if ($GLOBALS['myrights'] > 10) {
            echo "Error: arraytolist expect an array as input";
        }
        return $a;
    }
    if ($sp) {
        return (implode(', ', $a));
    } else {
        return (implode(',', $a));
    }
}

function joinarray($a, $s = ',', $ksort = false) {
    if (!is_array($a)) {
        if ($GLOBALS['myrights'] > 10) {
            echo "Error: joinarray expect an array as input";
        }
        return $a;
    }
    if ($ksort) {
        ksort($a);
    }
    return (implode($s, $a));
}

function calclisttoarray($l) {
    $l = listtoarray($l);
    foreach ($l as $k => $tocalc) {
        $l[$k] = evalMathParser($tocalc, null);
    }
    return $l;
}


function sortarray($a) {
    if (!is_array($a)) {
        $a = listtoarray($a);
    }
    if (func_num_args() > 1) {
        $dir = func_get_arg(1);
    }
    if (func_num_args() > 2) {
        $maxkey = func_get_arg(2);
    }
    if (isset($dir) && $dir == "rev") {
        if (isset($a[0]) && is_numeric($a[0])) {
            rsort($a, SORT_NUMERIC);
        } else {
            rsort($a);
        }
    } else if (isset($dir) && $dir == "key") {
        ksort($a);
    } else if (isset($dir) && $dir == "keyfill") {
        if (empty($maxkey)) {
            $maxkey = max(array_keys($a));
        }
        for ($i = 0; $i <= $maxkey; $i++) {
            if (!isset($a[$i])) {
                $a[$i] = '';
            }
        }
        ksort($a);
    } else {
        if (isset($a[0]) && is_numeric($a[0])) {
            sort($a, SORT_NUMERIC);
        } else {
            sort($a);
        }
    }
    return $a;
}

function splicearray($a, $offset, $length = null, $replacement = []) {
    if (!is_array($a)) {
        $a = listtoarray($a);
    }
    array_splice($a, $offset, $length, $replacement);
    return $a;
}

function consecutive($min, $max, $step = 1) {
    $a = array();
    if ($min < $max && $step > 0) {
        for ($i = $min; $i < $max + $step / 100.0; $i += $step) {
            $a[] = $i;
        }
    } else if ($min > $max && $step < 0) {
        for ($i = $min; $i > $max + $step / 100.0; $i += $step) {
            $a[] = $i;
        }
    } else if (abs($min - $max) < .9 * abs($step)) {
        $a[] = $min;
    } else {
        echo "Invalid inputs to consecutive";
    }
    return $a;
}


//use: calconarray($a,"x^$p")
function calconarray($array, $todo) {
    if (!is_array($array)) {
        echo "Error - First argument to calconarray must be an array";
        return $array;
    }
    /*global $disallowedwords,$allowedmacros;
	$todo = str_replace($disallowedwords,"",$todo);
	$todo = clean($todo);
	$rsnoquote = preg_replace('/"[^"]*"/','""',$todo);
	$rsnoquote = preg_replace('/\'[^\']*\'/','\'\'',$rsnoquote);
	if (preg_match_all('/([$\w]+)\s*\([^\)]*\)/',$rsnoquote,$funcs)) {
		for ($i=0;$i<count($funcs[1]);$i++) {
			if (strpos($funcs[1][$i],"$")===false) {
				if (!in_array($funcs[1][$i],$allowedmacros)) {
					echo "{$funcs[1][$i]} is not an allowed function<BR>\n";
					return false;
				}
			}
		}
	}
	*/
    $todo = mathphp($todo, 'x', false, false);
    $todo = str_replace('(x)', '($x)', $todo);
    $todo = tryWrapEvalTodo('return(' . $todo . ');', 'calconarray');
    return array_map(my_create_function('$x', $todo), $array);
}

function keepif($array, $todo) {
    $todo = mathphp($todo, 'x', false, true);
    $todo = str_replace('(x)', '($x)', $todo);
    $todo = tryWrapEvalTodo('return(' . $todo . ');', 'keepif');
    return array_values(array_filter($array, my_create_function('$x', $todo)));
}

function multicalconarray() {
    $args = func_get_args();
    $nargs = count($args);
    $todo = array_shift($args);
    $vars = array_shift($args);
    $vars = listtoarray($vars);
    foreach ($vars as $k => $v) {
        $vars[$k] = preg_replace('/[^\w]/', '', $v);
        if ($vars[$k] == '') {
            echo "multicalconarray: Invalid variable";
            return false;
        }
    }
    if ($nargs - 2 != count($vars)) {
        echo "multicalconarray: incorrect number of data arrays";
        return false;
    }
    if (!is_array($args[0])) {
        echo "multicalconarray: value array must be an array";
        return false;
    }
    $cnt = count($args[0]);
    for ($i = 1; $i < count($args); $i++) {
        if (!is_array($args[$i])) {
            echo "multicalconarray: value array must be an array";
            return false;
        }
        if (count($args[$i]) != $cnt) {
            echo "multicalconarray: Unequal array lengths";
            return false;
        }
    }

    $todo = mathphp($todo, implode('|', $vars), false, false);
    if ($todo == '0;') {
        return 0;
    }
    for ($i = 0; $i < count($vars); $i++) {
        $todo = str_replace('(' . $vars[$i] . ')', '($' . $vars[$i] . ')', $todo);
    }
    $varlist = '$' . implode(',$', $vars);
    $func = my_create_function($varlist, tryWrapEvalTodo('return(' . $todo . ');', 'multicalconarray'));
    $out = array();
    for ($j = 0; $j < count($args[0]); $j++) {
        $inputs = array();
        for ($i = 0; $i < count($args); $i++) {
            $inputs[] = $args[$i][$j];
        }
        $out[] = call_user_func_array($func, $inputs);
    }
    return $out;
}


//use: calconarray($a,"x + .01","floor(x)==x")
function calconarrayif($array, $todo, $ifcond) {
    if (!is_array($array)) {
        echo "Error - First argument to calconarrayif must be an array";
        return $array;
    }
    /*global $disallowedwords,$allowedmacros;
	$todo = str_replace($disallowedwords,"",$todo);
	$todo = clean($todo);
	$ifcond = clean($ifcond);
	$rsnoquote = preg_replace('/"[^"]*"/','""',$todo);
	$rsnoquote = preg_replace('/\'[^\']*\'/','\'\'',$rsnoquote);
	if (preg_match_all('/([$\w]+)\s*\([^\)]*\)/',$rsnoquote,$funcs)) {
		for ($i=0;$i<count($funcs[1]);$i++) {
			if (strpos($funcs[1][$i],"$")===false) {
				if (!in_array($funcs[1][$i],$allowedmacros)) {
					echo "{$funcs[1][$i]} is not an allowed function<BR>\n";
					return false;
				}
			}
		}
	}
	*/
    $todo = mathphp($todo, 'x', false, false);
    $todo = str_replace('(x)', '($x)', $todo);
    /*
	$rsnoquote = preg_replace('/"[^"]*"/','""',$ifcond);
	$rsnoquote = preg_replace('/\'[^\']*\'/','\'\'',$rsnoquote);
	if (preg_match_all('/([$\w]+)\s*\([^\)]*\)/',$rsnoquote,$funcs)) {
		$ismath = true;
		for ($i=0;$i<count($funcs[1]);$i++) {
			if (strpos($funcs[1][$i],"$")===false) {
				if (!in_array($funcs[1][$i],$allowedmacros)) {
					echo "{$funcs[1][$i]} is not an allowed function<BR>\n";
					return false;
				}
			}
		}
	}
	$ifcond = str_replace('!=','#=',$ifcond);
	*/
    $ifcond = mathphp($ifcond, 'x', false, false);
    //$ifcond = str_replace('#=','!=',$ifcond);
    $ifcond = str_replace('(x)', '($x)', $ifcond);

    $iffunc = my_create_function('$x', tryWrapEvalTodo('return(' . $ifcond . ');', 'calconarrayif'));

    $tmpfunc = my_create_function('$x', tryWrapEvalTodo('return(' . $todo . ');', 'calconarrayif'));
    foreach ($array as $k => $x) {
        if ($iffunc($x)) {
            $array[$k] = $tmpfunc($x);
        }
    }
    return $array;
}

function sumarray($array) {
    if (!is_array($array)) {
        echo "sumarray: input must be an array";
        return '';
    }
    return array_sum($array);
}

function intersectarrays($a1, $a2) {
    return array_values(array_intersect($a1, $a2));
}

function diffarrays($a1, $a2) {
    return array_values(array_diff($a1, $a2));
}

function unionarrays($a1, $a2) {
    foreach ($a2 as $v) {
        if (!in_array($v, $a1)) {
            $a1[] = $v;
        }
    }
    return array_values($a1);
}

function subarray($a) {
    if (is_array(func_get_arg(1))) {
        $args = func_get_arg(1);
    } else {
        $args = func_get_args();
        array_shift($args);
    }
    if (count($args) < 1) {
        return array();
    }
    $out = array();
    ksort($args);
    foreach ($args as $k => $v) {
        if (strpos($v, ':') !== false) {
            $p = explode(':', $v);
            $p = array_map('evalbasic', $p);
            if (!is_numeric($p[0]) || !is_numeric($p[1])) {
                echo "subarray index ranges need to be numeric or simple calculations";
                continue;
            }
            array_splice($out, count($out), 0, array_slice($a, $p[0], $p[1] - $p[0] + 1));
        } else {
            $out[] = $a[$v] ?? '';
        }
    }
    return $out;
}

function ABarray($s, $n) {
    $s = (int) $s;
    $n = (int) $n;
    $out = array();
    for ($i = $s; $i < $s + $n; $i++) {
        $out[] = '[AB' . $i . ']';
    }
    return $out;
}

function fillarray($v, $n, $s = 0) {
    return array_fill($s, $n, $v);
}

function arrayhasduplicates($arr) {
    if (count($arr) == count(array_unique($arr))) {
        return false;
    } else {
        return true;
    }
}

/** not public **/

function lensort($a, $b) {
    return strlen($b) - strlen($a);
}

function arrayremovenull($array) {
    return array_values(array_filter($array, function ($v) {
        return (!is_null($v) && $v !== '');
    }));
}
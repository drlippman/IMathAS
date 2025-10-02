<?php

// conditional test and scoring macros

array_push(
    $GLOBALS['allowedmacros'],
    'comparenumbers',
    'comparefunctions',
    'scorestring',
    'scoremultiorder',
    'comparelogic',
    'comparesetexp',
    'comparentuples',
    'comparenumberswithunits',
    'comparesameform'
);


function comparenumbers($a, $b, $tol = '.001') {
    if (strval($tol)[0] == '|') {
        $abstolerance = floatval(substr($tol, 1));
    }
    if (!is_numeric($a)) {
        $a = evalMathParser($a);
    }
    if (!is_numeric($b)) {
        $b = evalMathParser($b);
    }
    //echo "comparing $a and $b ";
    if (isset($abstolerance)) {
        if (abs($a - $b) < $abstolerance + 1E-12) {
            return true;
        }
    } else {
        if (abs($a - $b) / (abs($a) + .0001) < $tol + 1E-12) {
            return true;
        }
    }
    return false;
}

function comparefunctions($a, $b, $vars = 'x', $tol = '.001', $domain = '-10,10') {
    if ($a == '' || $b == '') {
        return false;
    }
    //echo "comparing $a and $b";
    if (strval($tol)[0] == '|') {
        $abstolerance = floatval(substr($tol, 1));
    }
    $type = "expression";
    if (strpos($a, '=') !== false && strpos($b, '=') !== false) {
        $type = "equation";
    }

    list($variables, $tps, $flist) = numfuncGenerateTestpoints($vars, $domain);

    $vlist = implode(",", $variables);
    $a = numfuncPrepForEval($a, $variables);
    $b = numfuncPrepForEval($b, $variables);

    if ($type == 'equation') {
        if (substr_count($a, '=') != 1) {
            return false;
        }
        $a = preg_replace('/(.*)=(.*)/', '$1-($2)', $a);
        if (substr_count($b, '=') != 1) {
            return false;
        }
        $b = preg_replace('/(.*)=(.*)/', '$1-($2)', $b);
    }

    $afunc = makeMathFunction($a, $vlist, [], $flist, true);
    $bfunc = makeMathFunction($b, $vlist, [], $flist, true);
    if ($afunc === false || $bfunc === false) {
        if (!empty($GLOBALS['inQuestionTesting'])) {
            echo "<p>Debug info: one function failed to compile.</p>";
        }
        return false;
    }

    $cntnana = 0;
    $cntnanb = 0;
    $cntbothzero = 0;
    $cntzero = 0;
    $diffnan = 0;
    $correct = true;
    $ratios = array();
    $evalerr = false;
    for ($i = 0; $i < 20; $i++) {
        $varvals = array();
        for ($j = 0; $j < count($variables); $j++) {
            $varvals[$variables[$j]] = $tps[$i][$j];
        }
        $ansa = $afunc($varvals);
        $ansb = $bfunc($varvals);

        if ($ansa === false || $ansb === false) {
            $evalerr = true;
            break;
        }
        //echo "real: $ansa, my: $ansb <br/>";
        if (isNaN($ansa)) {
            $cntnana++;
            if (isNaN($ansb)) {
                $cntnanb++;
            } else {
                $diffnan++;
            }
            continue;
        } //avoid NaN problems
        if (isNaN($ansb)) {
            $cntnanb++;
            $diffnan++;
            continue;
        }

        if ($type == 'equation') {
            if (abs($ansa) > .000001 && is_numeric($ansb)) {
                $ratios[] = $ansb / $ansa;
                if (abs($ansb) <= .00000001 && $ansa != 0) {
                    $cntzero++;
                }
            } else if (abs($ansa) <= .000001 && is_numeric($ansb) && abs($ansb) <= .00000001) {
                $cntbothzero++;
            }
        } else {
            if (isset($abstolerance)) {
                if (abs($ansa - $ansb) > $abstolerance - 1E-12) {
                    $correct = false;
                    break;
                }
            } else {
                if ((abs($ansa - $ansb) / (abs($ansa) + .0001) > $tol - 1E-12)) {
                    $correct = false;
                    break;
                }
            }
        }
    }
    //echo "$i, $ansa, $ansb, $cntnana, $cntnanb";
    if ($cntnana == 20 || $cntnanb == 20) {
        if (!empty($GLOBALS['inQuestionTesting'])) {
            echo "<p>Debug info: one function evaled to Not-a-number at all test points.  Check \$domain</p>";
            echo "<p>Funcs: $a and $b</p>";
        }
        return false;
    } else if ($evalerr) {
        if (!empty($GLOBALS['inQuestionTesting'])) {
            echo "<p>Debug info: one function was invalid.</p>";
            echo "<p>Funcs: $a and $b</p>";
        }
        return false;
    } else if ($i < 20) { //broke out early
        return false;
    }

    if ($diffnan > 1) {
        return false;
    }
    if ($type == "equation") {
        if ($cntbothzero > 18) {
            $correct = true;
        } else if (count($ratios) > 0) {
            if (count($ratios) == $cntzero) {
                $correct = false;
            } else {
                $meanratio = array_sum($ratios) / count($ratios);
                for ($i = 0; $i < count($ratios); $i++) {
                    if (isset($abstolerance)) {
                        if (abs($ratios[$i] - $meanratio) > $abstolerance - 1E-12) {
                            $correct = false;
                            break;
                        }
                    } else {
                        if ((abs($ratios[$i] - $meanratio) / (abs($meanratio) + .0001) > $tol - 1E-12)) {
                            $correct = false;
                            break;
                        }
                    }
                }
            }
        } else {
            $correct = false;
        }
    }
    if ($correct) {
        return true;
    } else {
        return false;
    }
}

function scorestring($answer, $showanswer, $words, $stu, $qn, $part = null, $highlight = true) {
    $wc = array();
    if (!is_array($words)) {
        $words = listtoarray($words);
    }
    if ($answer === null || $stu === null) {
        return [$answer, $showanswer];
    }
    /*
	foreach ($words as $w) {
		if (!isset($wc[$w])) {
			$wc[$w] = 1;
		} else {
			$wc[$w]++;
		}
	}
	$words = array_keys($wc);
	*/
    if ($part === null) {
        $ans = $answer;
    } else if (!isset($answer[$part])) {
        return [$answer, $showanswer];
    } else {
        $ans = $answer[$part];
    }
    if ($highlight) {
        $sa = $ans;
        foreach ($words as $w) {
            $sa = str_replace($w, '<span style="color:#f00">' . $w . '</span>', $sa);
        }
    } else {
        $sa = $ans;
    }
    $iscorrect = true;
    if ($part === null) {
        $stua = $stu[$qn];
    } else {
        $stua = getstuans($stu, $qn, $part);
    }

    if ($stua != null) {
        foreach ($words as $w) {
            //if (substr_count($stua, $w) != $wc[$w]) {
            if (strpos($stua, $w) === false) {
                $iscorrect = false;
                break;
            }
        }
    } else {
        $iscorrect = false;
    }
    if ($part === null) {
        if ($iscorrect) {
            $answer = $stua;
        } else {
            $answer = $ans;
        }
        $showanswer = $sa;
    } else {
        if ($iscorrect) {
            $answer[$part] = $stua;
        } else {
            $answer[$part] = $ans;
        }
        $showanswer[$part] = $sa;
    }

    return array($answer, $showanswer);
}


//scoremultiorder($stua, $answer, $swap, [$type='string', $weights])
//allows groups of questions to be scored in different orders
//only works if $stua and $answer are directly comparable (i.e. basic string type or exact number)
//$swap is an array of entries of the form "1,2,3;4,5,6"  says to treat 1,2,3 as a group of questions.
//$weights is answeights, and use function like $answer,$answeights = scoremultiorder(...) if set
//$options allows other options, such as 'variables' for numfunc
//comparison is made on first entry in group
function scoremultiorder($stua, $answer, $swap, $type = 'string', $weights = null, $options = []) {
    if ($stua == null) {
        if ($weights !== null) {
            return [$answer, $weights];
        } else {
            return $answer;
        }
    }

    if ($type == 'numfunc' && isset($options['variables'])) {
        $variables = $options['variables'];
    } else {
        $variables = 'x';
    }

    $swapindices = [];
    if (!is_array($swap)) {
        $swap = [$swap];
    }
    foreach ($swap as $k => $sw) {
        $swap[$k] = explode(';', $sw);
        foreach ($swap[$k] as $i => $s) {
            $swap[$k][$i] = listtoarray($s);
            $swapindices[] = $swap[$k][$i][0];
        }
    }

    $newans = $answer;
    $newansval = [];
    if ($type == 'calculated') {
        foreach ($swapindices as $k) {
            $v = $newans[$k];
            $newansval[$k] = evalMathParser($v);
        }
    } else if ($type == 'complex' || $type == 'calccomplex') {
        foreach ($swapindices as $k) {
            $v = $newans[$k];
            $newansval[$k] = parsesloppycomplex($v);
        }
        foreach ($stua as $k => $v) {
            $stua[$k] = parsesloppycomplex($v);
        }
    } else if ($type == 'ntuple' || $type == 'calcntuple') {
        foreach ($swapindices as $k) {
            $v = $newans[$k];
            $newansval[$k] = explode(',', substr($v, 1, -1));
            if ($type == 'calcntuple') {
                foreach ($newansval[$k] as $j => $vv) {
                    $newansval[$k][$j] = evalMathParser($vv);
                }
            }
        }
        foreach ($stua as $k => $v) {
            $stua[$k] = explode(',', substr($v, 1, -1));
        }
    }
    if ($weights !== null) {
        if (!is_array($weights)) {
            $weights = explode(',', $weights);
        }
        $newweights = $weights;
    }

    foreach ($swap as $sw) {
        //for ($i=0;$i<count($sw);$i++) {
        for ($i = count($sw) - 1; $i >= 0; $i--) {
            if (!isset($sw[$i][0]) || !isset($stua[$sw[$i][0]])) {
                continue;
            }
            $tofind = $stua[$sw[$i][0]];
            $loc = -1;
            //for ($k=0;$k<count($sw);$k++) {
            for ($k = count($sw) - 1; $k >= 0; $k--) {
                if ($type == 'string' && preg_replace('/\s+/', '', strtolower($tofind)) == preg_replace('/\s+/', '', strtolower($newans[$sw[$k][0]]))) {
                    $loc = $k;
                    break;
                } else if ($type == 'number' && is_numeric($tofind) && is_numeric($newans[$sw[$k][0]]) && abs($tofind - $newans[$sw[$k][0]]) < 0.01) {
                    $loc = $k;
                    break;
                } else if ($type == 'calculated' && is_numeric($tofind) && abs($tofind - $newansval[$sw[$k][0]]) < 0.01) {
                    $loc = $k;
                    break;
                } else if ($type == 'numfunc' && $tofind !== '' && comparefunctions($tofind, $newans[$sw[$k][0]], $variables)) {
                    $loc = $k;
                    break;
                } else if (($type == 'complex' || $type == 'calccomplex' || $type == 'ntuple' || $type == 'calcntuple') &&
                    $tofind !== false && count($tofind) == 2 && is_numeric($tofind[0]) && is_numeric($tofind[1]) &&
                    abs($tofind[0] - $newansval[$sw[$k][0]][0]) < 0.01 && abs($tofind[1] - $newansval[$sw[$k][0]][1]) < 0.01
                ) {
                    $loc = $k;
                    break;
                }
            }
            if ($loc > -1 && $i != $loc) {
                //want to swap entries from $sw[$loc] with sw[$i] and swap $answer values
                $tmp = array();
                $tmpw = array();
                $tmpv = array();
                foreach ($sw[$i] as $k => $v) {
                    $tmp[$k] = $newans[$v];
                    if ($weights !== null) {
                        $tmpw[$k] = $newweights[$v];
                    }
                    if (isset($newansval[$v])) {
                        $tmpv[$k] = $newansval[$v];
                    }
                }
                foreach ($sw[$loc] as $k => $v) {
                    $newans[$sw[$i][$k]] = $newans[$v];
                    if ($weights !== null) {
                        $newweights[$sw[$i][$k]] = $newweights[$v];
                    }
                    if (isset($newansval[$v])) {
                        $newansval[$sw[$i][$k]] = $newansval[$v];
                    }
                }
                foreach ($tmp as $k => $v) {
                    $newans[$sw[$loc][$k]] = $tmp[$k];
                    if ($weights !== null) {
                        $newweights[$sw[$loc][$k]] = $tmpw[$k];
                    }
                    if (isset($tmpv[$k])) {
                        $newansval[$sw[$loc][$k]] = $tmpv[$k];
                    }
                }
            }
        }
    }

    if ($weights !== null) {
        return array($newans, $newweights);
    } else {
        return $newans;
    }
}

function comparelogic($a, $b, $vars) {
    if (!is_array($vars)) {
        $vars = array_map('trim', explode(',', $vars));
    }
    if ($a === null || $b === null || trim($a) == '' || trim($b) == '') {
        return false;
    }
    $varlist = implode(',', $vars);

    $keywords = ['\\', 'and', '^^', 'wedge', 'xor', 'oplus', 'or', 'vv', 'vee', '~', '¬', 'neg', 'iff', '<->', '<=>', 'implies', '->', '=>', 'rarr', 'to'];
    $replace =     ['',   '#a',  '#a', '#a',    '#x',  '#x',    '#o', '#o', '#o',  '!', '!', '!',   '#b',  '#b',     '#b',  '#i',      '#i', '#i', '#i',   '#i'];
    $a = str_replace($keywords, $replace, $a);
    $b = str_replace($keywords, $replace, $b);

    $afunc = makeMathFunction($a, $varlist, [], '', true);
    if ($afunc === false) {
        return false;
    }
    $bfunc = makeMathFunction($b, $varlist, [], '', true);
    if ($bfunc === false) {
        return false;
    }
    $n = count($vars);
    $max = pow(2, $n);
    $map = array_combine($vars, array_fill(0, count($vars), 0));
    for ($i = 0; $i < $max; $i++) {
        $aval = $afunc($map);
        $bval = $bfunc($map);
        if ($aval != $bval) {
            return false;
        }
        for ($j = 0; $j < $n; $j++) {
            if ($map[$vars[$j]] == 0) { // if it's 0, add 1 and stop
                $map[$vars[$j]] = 1;
                break;
            } else {
                $map[$vars[$j]] = 0; // if it's 1, set to 0 and continue on to the next one
            }
        }
    }
    return true;
}

function comparesetexp($a, $b, $vars) {
    if (!is_array($vars)) {
        $vars = array_map('trim', explode(',', $vars));
    }
    if ($a === null || $b === null || trim($a) == '' || trim($b) == '') {
        return false;
    }
    $varlist = implode(',', $vars);

    $keywords = ['and', 'nn', 'cap', 'xor', 'oplus', 'ominus', 'triangle', 'or', 'cup', 'uu', '-',  '\''];
    $replace =     ['#a',  '#a', '#a',     '#x',  '#x',    '#x',     '#x',       '#o', '#o',  '#o', '#m',    '^c'];

    $ab = [$a, $b];
    foreach ($ab as &$str) {
        $str = str_replace($keywords, $replace, $str);

        // Since complement symbols in set expresions are unary operations *after* the operand, we will shift the complement operator to before the operand here, rather than overcomplicating MathParser
        // Remove double negations
        $str = preg_replace('/(\'|\^c){1}\s*(\'|\^c){1}/', '', $str);
        // Remove any spaces before a complement symbol
        $str = preg_replace('/\s*(\'|\^c)/', '$1', $str);
        // If symbol before a complement is an object from $vars, place a ! immediately before the $var
        foreach ($vars as $var) {
            $str = preg_replace("/($var)(\'|\^c)/", '!$1', $str);
        }
        // If symbol before a complement is a right paren/bracket, place a ! before the corresponding left paren/bracket
        $rindex = max(strpos($str, ")^c"), strpos($str, "]^c"));
        while ($rindex) {
            $str = substr_replace($str, '', $rindex + 1, 2);
            $balanced = 1;
            for ($i = $rindex - 1; $i >= 0; $i--) {
                if ($str[$i] == ')' || $str[$i] == ']') {
                    $balanced++;
                } elseif ($str[$i] == '(' || $str[$i] == '[') {
                    $balanced--;
                }
                if ($balanced == 0) {
                    $str = substr_replace($str, '!', $i, 0);
                    break;
                }
            }
            $rindex = max(strpos($str, ")^c"), strpos($str, "]^c"));
        }
    }
    $a = $ab[0];
    $b = $ab[1];
    $afunc = makeMathFunction($a, $varlist, [], '', true);
    if ($afunc === false) {
        return false;
    }
    $bfunc = makeMathFunction($b, $varlist, [], '', true);
    if ($bfunc === false) {
        return false;
    }
    $n = count($vars);
    $max = pow(2, $n);
    $map = array_combine($vars, array_fill(0, count($vars), 0));
    for ($i = 0; $i < $max; $i++) {
        $aval = $afunc($map);
        $bval = $bfunc($map);
        if ($aval != $bval) {
            return false;
        }
        for ($j = 0; $j < $n; $j++) {
            if ($map[$vars[$j]] == 0) { // if it's 0, add 1 and stop
                $map[$vars[$j]] = 1;
                break;
            } else {
                $map[$vars[$j]] = 0; // if it's 1, set to 0 and continue on to the next one
            }
        }
    }
    return true;
}
function comparentuples() {
    $par = false;
    $args = func_get_args();
    if (in_array("ignoreparens", $args)) {
        $par = true;
        unset($args[array_search("ignoreparens", $args)]);
        $args = array_values($args);
    }
    $utup = $args[0];
    $vtup = $args[1];

    if (empty($utup) || empty($vtup)) {
        echo 'Eek! Comparentuples needs two nutples to compare.';
        return false;
    }
    if (!preg_match('/^[\(\[\{\<]{1}.*[\)\]\}\>]{1}$/', $utup) || !preg_match('/^[\(\[\{\<]{1}.*[\)\]\}\>]{1}$/', $vtup)) {
        return false;
    }
    if (!isset($args[2])) {
        $args[2] = '0.001';
    }
    $tol = $args[2];
    $correct = 0;
    $uparen = [$utup[0], $utup[strlen($utup) - 1]];
    $vparen = [$vtup[0], $vtup[strlen($vtup) - 1]];
    $u = listtoarray(substr($utup, 1, -1));
    $v = listtoarray(substr($vtup, 1, -1));

    if (count($u) != count($v) || count($u) == 0 || count($v) == 0) {
        return false;
    }
    $dim = count($u);
    if (!is_array($tol)) {
        $tol = listtoarray($tol);
    }
    // repeat single tol for every entry
    if (count($tol) == 1) {
        $tol = fillarray("$tol[0]", $dim);
    }
    // fill in missing values at end of tol array with default value
    if (count($tol) < $dim) {
        for ($i = count($tol); $i < $dim; $i++) {
            $tol[$i] = '0.001';
        }
    }
    foreach ($tol as $key => $in) {
        // fill empty tol's in list with default value
        if (empty($in)) {
            $tol[$key] = '0.001';
        }
    }
    for ($i = 0; $i < $dim; $i++) {
        if (comparenumbers($u[$i], $v[$i], "$tol[$i]")) {
            $correct += 1;
        }
    }
    if ($par == false) {
        if ($uparen[0] != $vparen[0] || $uparen[1] != $vparen[1]) {
            $correct += -1;
        }
    }
    if ($correct == $dim) {
        return true;
    }
}

function comparenumberswithunits($unitExp1, $unitExp2, $tol = '0.001') {
    require_once __DIR__ . '/../../assessment/libs/units.php';
    if (strval($tol)[0] == '|') {
        $abstolerance = floatval(substr($tol, 1));
    }
    [$unitVal1, $unitArray1] = parseunits($unitExp1);
    [$unitVal2, $unitArray2] = parseunits($unitExp2);
    if ($unitArray1 !== $unitArray2) {
        return false;
    }
    if ($unitArray1 === $unitArray2) {
        if (isset($abstolerance)) {
            if (abs($unitVal1 - $unitVal2) < $abstolerance + 1E-12) {
                return true;
            }
        } else {
            if (abs($unitVal1 - $unitVal2) / abs($unitVal1 + 0.0001) < $tol + 1E-12 && abs($unitVal1 - $unitVal2) / abs($unitVal2 + 0.0001) < $tol + 1E-12) {
                return true;
            }
        }
    }
}

function comparesameform($a, $b, $vars = "x") {
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
    $bfunc = parseMathQuiet($b, $vlist, [], $flist);
    if ($afunc === false || $bfunc === false) {
        return false;
    }

    return ($afunc->normalizeTreeString() === $bfunc->normalizeTreeString());
}

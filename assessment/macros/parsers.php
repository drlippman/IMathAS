<?php

// format parsers, 
// along with some function evaluation prep functions

// these are mostly internal functions, not public macros

array_push(
    $GLOBALS['allowedmacros'],
    'parseNtuple'
);

/*
 * Parses a list of string ntuples
 * do_or: for each element in list, create an array of "or" alternatives
 * do_eval: true to eval non-numeric values
 * isComplex: true to treat entries as complex numbers, populating cvals along with vals
 * ansformats: array of answerformats, used for complex numbers when evaling
 */
function parseNtuple($str, $do_or = false, $do_eval = true, $isComplex = false, $ansformats = []) {
    if ($str == 'DNE' || $str == 'oo' || $str == '-oo') {
        return $str;
    }
    $ntuples = [];
    $NCdepth = 0;
    $lastcut = 0;
    $lastend = 0;
    $inor = false;
    $str = makepretty($str);
    $matchbracket = array(
        '(' => ')',
        '[' => ']',
        '<' => '>',
        '{' => '}'
    );
    $closebracket = '';
    $openbracket = '';
    for ($i = 0; $i < strlen($str); $i++) {
        $dec = false;
        if ($str[$i] == '(' || $str[$i] == '[' || $str[$i] == '<' || $str[$i] == '{') {
            if ($NCdepth == 0) {
                if ($lastend > 0) {
                    $between = trim(substr($str, $lastend + 1, $i - $lastend - 1));
                    $inor = ($do_or && $between === 'or');
                    if ($between !== 'or' && $between !== ',' && $between !== '') {
                        // invalid
                        return $ntuples;
                    }
                }
                $lastcut = $i;
                $closebracket = $matchbracket[$str[$i]];
                $openbracket = $str[$i];
            }
            if ($openbracket == '' || $str[$i] == $openbracket) {
                $NCdepth++;
            }
        } else if ($str[$i] == $closebracket) {
            $NCdepth--;
            if ($NCdepth == 0) {
                $thisTuple = array(
                    'lb' => $str[$lastcut],
                    'rb' => $str[$i],
                    'vals' => explode(',', substr($str, $lastcut + 1, $i - $lastcut - 1))
                );
                $thisTuple['vals'] = array_map("trim", $thisTuple['vals']);
                $lastend = $i;
                if ($do_eval) {
                    for ($j = 0; $j < count($thisTuple['vals']); $j++) {
                        if ($thisTuple['vals'][$j] != 'oo' && $thisTuple['vals'][$j] != '-oo') {
                            if ($isComplex) {
                                if (in_array('generalcomplex', $ansformats)) {
                                    $thisTuple['cvals'][$j] = parseGeneralComplex($thisTuple['vals'][$j]);
                                } else {
                                    $thisTuple['cvals'][$j] = parsesloppycomplex($thisTuple['vals'][$j]);
                                }
                                $thisTuple['vals'][$j] = complexarr2str($thisTuple['cvals'][$j]);
                            } else {
                                $thisTuple['vals'][$j] = evalMathParser($thisTuple['vals'][$j]);
                            }
                        }
                    }
                } else if ($isComplex) {
                    $thisTuple['cvals'] = array_map("parsecomplex", $thisTuple['vals']);
                }
                if ($do_or && $inor) {
                    $ntuples[count($ntuples) - 1][] = $thisTuple;
                } else if ($do_or) {
                    $ntuples[] = array($thisTuple);
                } else {
                    $ntuples[] = $thisTuple;
                }
                //$inor = ($do_or && substr($str, $i+1, 2)==='or');
                $openbracket = '';
                $closebracket = '';
            }
        }
    }
    return $ntuples;
}

/** internal functions from here on */

function ineqtointerval($str, $var) {
    if ($str === 'DNE') {
        return $str;
    }
    $str = strtolower($str);
    $var = strtolower($var);
    if (empty($GLOBALS['CFG']['nocommathousandsseparator'])) {
        $str = preg_replace('/(\d)\s*,\s*(?=\d{3}\b)/', "$1", $str);
    }
    if (preg_match('/all\s*real/', $str)) {
        return '(-oo,oo)';
    }
    $outpieces = [];
    $orpts = preg_split('/\s*or\s*/', $str);
    foreach ($orpts as $str) {
        if (count($orpts) == 1 && strpos($str, '!=') !== false) {
            // special handling for != 
            $pieces = explode('!=', $str);
            if (count($pieces) != 2 || trim($pieces[0]) != $var) {
                return false;
            }
            return '(-oo,' . $pieces[1] . ')U(' . $pieces[1] . ',oo)';
        }
        $pieces = preg_split('/(<=?|>=?)/', $str, -1, PREG_SPLIT_DELIM_CAPTURE);
        $cnt = count($pieces);
        $pieces = array_map('trim', $pieces);
        if ($cnt != 3 && $cnt != 5) {
            return false; //invalid
        } else if ($cnt == 5 && ($pieces[1][0] != $pieces[3][0] || $pieces[2] != $var)) {
            return false; // mixes > with <
        }
        if ($cnt == 3 && $pieces[0] == $var && $pieces[1][0] == '>') {
            $outpieces[] = ($pieces[1] == '>' ? '(' : '[') . $pieces[2] . ',oo)';
        } else if ($cnt == 3 && $pieces[0] == $var && $pieces[1][0] == '<') {
            $outpieces[] = '(-oo,' . $pieces[2] . ($pieces[1] == '<' ? ')' : ']');
        } else if ($cnt == 3 && $pieces[2] == $var && $pieces[1][0] == '>') {
            $outpieces[] = '(-oo,' . $pieces[0] . ($pieces[1] == '>' ? ')' : ']');
        } else if ($cnt == 3 && $pieces[2] == $var && $pieces[1][0] == '<') {
            $outpieces[] = ($pieces[1] == '<' ? '(' : '[') . $pieces[0] . ',oo)';
        } else if ($cnt == 5 && $pieces[1][0] == '<') {
            $outpieces[] = ($pieces[1] == '<' ? '(' : '[') . $pieces[0] . ',' .
                $pieces[4] . ($pieces[3] == '<' ? ')' : ']');
        } else if ($cnt == 5 && $pieces[1][0] == '>') {
            $outpieces[] = ($pieces[3] == '>' ? '(' : '[') . $pieces[4] . ',' .
                $pieces[0] . ($pieces[1] == '>' ? ')' : ']');
        }
    }
    if (count($outpieces) > 0) {
        return implode('U', $outpieces);
    }
    return false;
}

function intervaltoineq($str, $var) {
    if ($str == 'DNE') {
        return 'DNE';
    } else if (trim($str) == '') {
        return '';
    }
    $arr = explode('U', $str);
    $out = array();
    $mightbeineq = '';
    foreach ($arr as $v) {
        $v = trim($v);
        $sm = $v[0];
        $em = $v[strlen($v) - 1];
        $pts = explode(',', substr($v, 1, strlen($v) - 2));
        if (count($pts) !== 2) {
            echo 'Invalid interval notation';
            return '';
        }
        if ($pts[0] == '-oo') {
            if ($pts[1] == 'oo') {
                $out[] = '"all real numbers"';
            } else {
                $out[] = $var . ($em == ']' ? 'le' : 'lt') . $pts[1];
                if ($em == ')') {
                    $mightbeineq = $pts[1];
                }
            }
        } else if ($pts[1] == 'oo') {
            $out[] = $var . ($sm == '[' ? 'ge' : 'gt') . $pts[0];
            if ($mightbeineq !== '' && count($arr) == 2 && $sm == '(' && $mightbeineq == $pts[0]) {
                return $var . ' != ' . $pts[0];
            }
        } else {
            $out[] = $pts[0] . ($sm == '[' ? ' le ' : ' lt ') . $var . ($em == ']' ? ' le ' : ' lt ') . $pts[1];
        }
    }
    return implode(' \\ "or" \\ ', $out);
}


/*
 abstracts domain and variable parsing for common numfunc stuff
 @param $variables string (comma separated)
 @param domain     string
 @param $varstoadd  array variables to add to $variables if not already present
 return 
 array of 
   $variables   array
   $ofunc       array function-acting variables
   $newdomain   multi-dim array domain parsed are normalized
   $restrictvartoint   array
*/
function numfuncParseVarsDomain($variables, $domain, $varstoadd = []) {
    $variables = array_values(array_filter(array_map('trim', explode(",", $variables)), 'strlen'));
    foreach ($varstoadd as $v) {
        if (!in_array($v, $variables)) {
            $variables[] = $v;
        }
    }
    $ofunc = array();
    for ($i = 0; $i < count($variables); $i++) {
        //find f() function variables
        if (strpos($variables[$i], '()') !== false) {
            $ofunc[] = substr($variables[$i], 0, strpos($variables[$i], '('));
            $variables[$i] = substr($variables[$i], 0, strpos($variables[$i], '('));
        }
    }
    if (!empty($domain)) {
        $fromto = array_map('trim', explode(',', $domain));
    } else {
        $fromto = array(-10, 10);
    }

    $domaingroups = array();
    $i = 0;
    $haderr = false;
    while ($i < count($fromto)) {
        if (!isset($fromto[$i + 1])) {
            if (!$haderr) {
                echo "domain values must be include min,max";
                $haderr = true;
            }
            $fromto[$i] = -10;
            $fromto[$i + 1] = 10;
        }
        if (!is_numeric($fromto[$i])) {
            $fromto[$i] = evalbasic($fromto[$i], true);
            if (!is_numeric($fromto[$i])) {
                if (!$haderr) {
                    echo "domain values must be numbers or elementary calculations";
                    $haderr = true;
                }
                $fromto[$i] = -10;
            }
        } else {
            $fromto[$i] = floatval($fromto[$i]);
        }
        if (!is_numeric($fromto[$i + 1])) {
            $fromto[$i + 1] = evalbasic($fromto[$i + 1], true);
            if (!is_numeric($fromto[$i + 1])) {
                if (!$haderr) {
                    echo "domain values must be numbers or elementary calculations";
                    $haderr = true;
                }
                $fromto[$i + 1] = 10;
            }
        } else {
            $fromto[$i + 1] = floatval($fromto[$i + 1]);
        }
        if (isset($fromto[$i + 2]) && ($fromto[$i + 2] == 'integers' || $fromto[$i + 2] == 'integer')) {
            $domaingroups[] = array($fromto[$i], $fromto[$i + 1], true);
            $i += 3;
        } else if (isset($fromto[$i + 1])) {
            $domaingroups[] = array($fromto[$i], $fromto[$i + 1], false);
            $i += 2;
        } else {
            break;
        }
    }
    uasort($variables, 'lensort');
    $newdomain = array();
    $restrictvartoint = array();
    foreach ($variables as $i => $v) {
        if (isset($domaingroups[$i])) {
            $touse = $i;
        } else {
            $touse = 0;
        }
        $newdomain[] = $domaingroups[$touse];
    }

    $variables = array_values($variables);
    if (count($ofunc) > 0) {
        usort($ofunc, 'lensort');
    }
    return [$variables, $ofunc, $newdomain, $restrictvartoint];
}

/*
 abstracts some of the common numfunc processing
 @param $variables string (comma separated) 
 @param $domain   string
 return
 array of
   $variables  array
   $testpoints  2d array
   $flist string (function variables, comma separated) 
*/
function numfuncGenerateTestpoints($variables, $domain = '') {

    list($variables, $ofunc, $fromto, $restrictvartoint) = numfuncParseVarsDomain($variables, $domain);

    $flist = '';
    if (count($ofunc) > 0) {
        $flist = implode(",", $ofunc);
    }

    $tps = [];
    for ($j = 0; $j < count($variables); $j++) {
        if ($fromto[$j][0] == $fromto[$j][1]) {
            for ($i = 0; $i < 20; $i++) {
                $tps[$i][$j] = $fromto[$j][0];
            }
        } else if (!empty($fromto[$j][2])) { // integers
            if ($fromto[$j][1] - $fromto[$j][0] > 200) {
                for ($i = 0; $i < 20; $i++) {
                    $tps[$i][$j] = rand($fromto[$j][0], $fromto[$j][1]);
                }
            } else {
                $allbetween = range($fromto[$j][0], $fromto[$j][1]);
                shuffle($allbetween);
                $n = count($allbetween);
                for ($i = 0; $i < 20; $i++) {
                    $tps[$i][$j] = $allbetween[$i % $n];
                }
            }
        } else {
            $dx = ($fromto[$j][1] - $fromto[$j][0]) / 20;
            for ($i = 0; $i < 20; $i++) {
                $tps[$i][$j] = $fromto[$j][0] + $dx * $i + $dx * rand(1, 499) / 500.0;
            }
        }
    }

    return [$variables, $tps, $flist];
}

/* do any necessary rewrite of expression (not including equation rewrite)
   before eval
   @param $expr  string  the expression to rewrite
   @param $variables array  of variables
   return:
   string of rewritten expression
*/
function numfuncPrepForEval($expr, $variables) {
    if (empty($GLOBALS['CFG']['nocommathousandsseparator'])) {
        $expr = preg_replace('/(\d)\s*,\s*(?=\d{3}(\D|\b))/', '$1', $expr);
    }

    for ($i = 0; $i < count($variables); $i++) {
        if ($variables[$i] == 'lambda') { //correct lamda/lambda
            $expr = str_replace('lamda', 'lambda', $expr);
        }
        // front end will submit p_(left) rather than p_left; strip parens
        if (preg_match('/^(\w+)_(\w+)$/', $variables[$i], $m)) {
            $expr = preg_replace('/' . $m[1] . '_\(' . $m[2] . '\)/', $m[0], $expr);
        }
    }

    return $expr;
}

function getscorenonzero() {
    global $scores;
    $out = array();
    foreach ($scores as $i => $v) {
        if (strpos($v, '~') === false) {
            $out[$i + 1] = ($v < 0) ? -1 : (($v > 0) ? 1 : 0);
        } else {
            $sp = explode('~', $v);
            $out[$i + 1] = array();
            for ($j = 0; $j < count($sp); $j++) {
                $out[$i + 1][$j] = ($sp[$j] > 0) ? 1 : 0;
            }
        }
    }
    return $out;
}

function getiscorrect() {
    global $rawscores;
    $out = array();
    if (!is_array($rawscores)) {
        return $out;
    }
    foreach ($rawscores as $i => $v) {
        if (strpos($v, '~') === false) {
            $out[$i + 1] = ($v < 0) ? -1 : (($v == 1) ? 1 : 0);
        } else {
            $sp = explode('~', $v);
            $out[$i + 1] = array();
            for ($j = 0; $j < count($sp); $j++) {
                $out[$i + 1][$j] = ($sp[$j] == 1) ? 1 : 0;
            }
        }
    }
    return $out;
}


function parsereqsigfigs($reqsigfigs) {
    $origstr = $reqsigfigs;
    $reqsigfigs = str_replace('+/-', '+-', $reqsigfigs);
    $reqsigfigoffset = 0;
    $reqsigfigparts = array_map('trim', explode('+-', $reqsigfigs));
    $reqsigfigs = $reqsigfigparts[0];
    $sigfigscoretype = array('abs', 0, 'def');
    if (count($reqsigfigparts) > 1) {
        if (substr($reqsigfigparts[1], -1) == '%') {
            $sigfigscoretype = array('rel', floatval(substr($reqsigfigparts[1], 0, -1)));
        } else {
            $sigfigscoretype = array('abs', floatval($reqsigfigparts[1]));
        }
    }
    if ($reqsigfigs[0] == '=') {
        $exactsigfig = 1;
        $reqsigfigs = substr($reqsigfigs, 1);
    } else if ($reqsigfigs[0] == 'r') {
        // for "round to __ places"
        $exactsigfig = 2;
        $reqsigfigs = substr($reqsigfigs, 1);
    } else if ($reqsigfigs[0] == '[') {
        $exactsigfig = 0;
        $reqsigfigparts = listtoarray(substr($reqsigfigs, 1, -1));
        $reqsigfigs = $reqsigfigparts[0];
        if (isset($reqsigfigparts[1])) {
            $reqsigfigoffset = $reqsigfigparts[1] - $reqsigfigparts[0];
        }
    } else {
        $exactsigfig = 0;
    }
    if (!is_numeric($reqsigfigs)) {
        echo "Invalid reqsigfigs/reqdecimals string $origstr";
    }
    return array(intval($reqsigfigs), $exactsigfig, $reqsigfigoffset, $sigfigscoretype);
}


function checkMinMax($min, $max, $isint, $funcname) {
    $err = '';
    if (!is_numeric($min) || !is_numeric($max)) {
        $err .= "min and max need to be numbers. ";
    } else if (is_infinite($min) || is_infinite($max)) {
        $err .= "min and max need to be finite values. ";
        $min = 1;
        $max = 10;
    } else if ($isint && (floor($min) != $min || floor($max) != $max)) {
        $err .= "rands expects integer min and max. ";
        $min = ceil($min);
        $max = floor($max);
    }
    if ($isint) {
        $min = intval($min);
        $max = intval($max);
    } else {
        $min = floatval($min);
        $max = floatval($max);
    }
    if ($max < $min) {
        $err .= "Need min&lt;max. ";
        $t = $max;
        $max = $min;
        $min = $t;
    }
    if ($max == 0 && $min == 0) {
        $err .= "min=0 and max=0. May suggest a problem. ";
    }
    if ($GLOBALS['myrights'] > 10 && $err != '') {
        echo "Possible error in " . Sanitize::encodeStringForDisplay($funcname) . ': ';
        echo $err;
    }
    return array($min, $max);
}

function encryptval($val, $key) {
    $cipher = "AES128";
    $ivlen = openssl_cipher_iv_length($cipher);
    $iv = openssl_random_pseudo_bytes($ivlen);
    return base64_encode($iv) . '.' . openssl_encrypt(json_encode($val), $cipher, $key, 0, $iv);
}
function decryptval($val, $key) {
    $cipher = "AES128";
    list($iv, $val) = explode('.', $val);
    return json_decode(openssl_decrypt($val, $cipher, $key, 0, base64_decode($iv)), true);
}


// returns [matrix elements as 1D array, numrows]
function parseMatrixToArray($str) {
    /*
    Handles:
      Matrices like [(1,2),(3,4)], ((1,2),(3,4)), |(1,2),(3,4)|, and [[1,2],[3,4]]
      Where parens confuse: [(sqrt(4),(2pi)/3),(5,6)]
      1x1 matrix like [3] or [(2pi)/3]
      Stored stuans format: 3|4|5|6  - won't be able to return numrows in this case
    */
    if (strpos($str, '|') !== false && strpos($str, ',') === false) {
        // stored stuans format
        return [explode('|', $str), null];
    } else if (strpos($str, ',') === false) {
        // 1x1 matrix
        $val = substr($str, 1, -1);
        if (strlen($val) > 2 && $val[0] == '(') {
            $depth = 0;
            for ($i = 0; $i < strlen($val); $i++) {
                if ($val[$i] == '(') {
                    $depth++;
                }
                if ($val[$i] == ')') {
                    $depth--;
                }
                if ($depth == 0) {
                    if ($i == strlen($val) - 1) {
                        $val = substr($val, 1, -1);
                    } else {
                        break;
                    }
                }
            }
        }
        return [[$val], 1];
    } else {
        $out = [];
        $rowcnt = 0;
        $depth = 0;
        $lastcut = 1;
        $bracketpairs = ['(' => ')', '[' => ']'];
        $rowbracket = '';
        $rowendbracket = '';
        for ($i = 1; $i < strlen($str) - 1; $i++) {
            $c = $str[$i];
            if ($rowbracket === '' && ($c == '(' || $c == '[')) {
                $rowbracket = $c;
                $rowendbracket = $bracketpairs[$c];
            }
            if ($c == $rowbracket) {
                if ($depth == 0) {
                    $lastcut = $i + 1;
                }
                $depth++;
            } else if ($c == $rowendbracket) {
                $depth--;
                if ($depth == 0) { // new row 
                    $out[] = trim(substr($str, $lastcut, $i - $lastcut));
                    $rowcnt++;
                }
            } else if ($c == ',') {
                if ($depth == 1) { // new entry in column
                    $out[] = trim(substr($str, $lastcut, $i - $lastcut));
                }
                $lastcut = $i + 1;
            }
        }
        if ($depth != 0) { // something wrong
            return [false, null];
        }
        return [$out, $rowcnt];
    }
}


// takes numeric [real,imag] array and outputs string
function complexarr2str($num) {
    $out = '';
    if (abs($num[0]) > 0 || abs($num[1]) == 0) {
        $out .= $num[0];
        if ($num[1] > 0) {
            $out .= '+';
        }
    }
    if (abs($num[1]) > 0) {
        $out .= $num[1] . 'i';
    }
    return $out;
}


//parses complex numbers.  Can handle anything, but only with
//one i in it.
function parsecomplex($v) {
    $v = str_replace(' ', '', $v);
    $v = str_replace(array('sin', 'pi'), array('s$n', 'p$'), $v);
    $v = preg_replace('/\((\d+\*?i|i)\)\/(\d+)/', '$1/$2', $v);
    $len = strlen($v);
    //preg_match_all('/(\bi|i\b)/',$v,$matches,PREG_OFFSET_CAPTURE);
    //if (count($matches[0])>1) {
    if (substr_count($v, 'i') > 1) {
        return _('error - more than 1 i in expression');
    } else {
        //$p = $matches[0][0][1];
        $p = strpos($v, 'i');
        if ($p === false) {
            $real = $v;
            $imag = 0;
        } else {
            //look left
            $nd = 0;
            for ($L = $p - 1; $L > 0; $L--) {
                $c = $v[$L];
                if ($c == ')') {
                    $nd++;
                } else if ($c == '(') {
                    $nd--;
                } else if (($c == '+' || $c == '-') && $nd == 0) {
                    break;
                }
            }
            if ($L < 0) {
                $L = 0;
            }
            if ($nd != 0) {
                return _('error - invalid form');
            }
            //look right
            $nd = 0;

            for ($R = $p + 1; $R < $len; $R++) {
                $c = $v[$R];
                if ($c == '(') {
                    $nd++;
                } else if ($c == ')') {
                    $nd--;
                } else if (($c == '+' || $c == '-') && $nd == 0) {
                    break;
                }
            }
            if ($nd != 0) {
                return _('error - invalid form');
            }
            //which is bigger?
            if ($p - $L > 0 && $R - $p > 0 && ($R == $len || $L == 0)) {
                //return _('error - invalid form');
                if ($R == $len) { // real + AiB
                    $real = substr($v, 0, $L);
                    $imag = substr($v, $L, $p - $L);
                    $imag .= '*' . substr($v, $p + 1 + (($v[$p + 1] ?? '') == '*' ? 1 : 0), $R - $p - 1);
                } else if ($L == 0) { //AiB + real
                    $real = substr($v, $R);
                    $imag = substr($v, 0, $p);
                    $imag .= '*' . substr($v, $p + 1 + (($v[$p + 1] ?? '') == '*' ? 1 : 0), $R - $p - 1);
                } else {
                    return _('error - invalid form');
                }
                $imag = str_replace('-*', '-1*', $imag);
                $imag = str_replace('+*', '+1*', $imag);
            } else if ($p - $L > 1) {
                $imag = substr($v, $L, $p - $L);
                $real = substr($v, 0, $L) . substr($v, $p + 1);
            } else if ($R - $p > 1) {
                if ($p > 0) {
                    if ($v[$p - 1] != '+' && $v[$p - 1] != '-') {
                        return _('error - invalid form');
                    }
                    $imag = $v[$p - 1] . substr($v, $p + 1 + ($v[$p + 1] == '*' ? 1 : 0), $R - $p - 1);
                    $real = substr($v, 0, $p - 1) . substr($v, $R);
                } else {
                    $imag = substr($v, $p + 1, $R - $p - 1);
                    $real = substr($v, 0, $p) . substr($v, $R);
                }
            } else { //i or +i or -i or 3i  (one digit)
                if ($v[$L] == '+') {
                    $imag = '1';
                } else if ($v[$L] == '-') {
                    $imag = '-1';
                } else if ($p == 0) {
                    $imag = '1';
                } else {
                    $imag = $v[$L];
                }
                $real = ($p > 0 ? substr($v, 0, $L) : '') . substr($v, $p + 1);
            }
            if ($real == '') {
                $real = 0;
            }
            if ($imag[0] == '/') {
                $imag = '1' . $imag;
            } else if (($imag[0] == '+' || $imag[0] == '-') && $imag[1] == '/') {
                $imag = $imag[0] . '1' . substr($imag, 1);
            }
            $imag = str_replace('*/', '/', $imag);
            if (substr($imag, -1) == '*') {
                $imag = substr($imag, 0, -1);
            }
        }
        $real = str_replace(array('s$n', 'p$'), array('sin', 'pi'), $real);
        $imag = str_replace(array('s$n', 'p$'), array('sin', 'pi'), $imag);
        $imag = ltrim($imag, '+');
        $imag = rtrim($imag, '*');
        return array($real, $imag);
    }
}

function parsesloppycomplex($v) {
    $func = makeMathFunction($v, 'i', [], '', true);
    if ($func === false) {
        return false;
    }
    $a = $func(['i' => 0]);
    $apb = $func(['i' => 4]);
    $amb = $func(['i' => -4]); // catch i's inside sqrt, log
    if (isNaN($a) || isNaN($apb) || isNaN($amb)) {
        return false;
    }
    return array($a, ($apb - $a) / 4);
}

function parseGeneralComplex($v) {
    $ev = evalMathParser($v, true);
    if ($ev === false || !is_array($ev)) {
        return false;
    }
    return $ev;
}


function ntupleToString($ntuples) {
    if (!is_array($ntuples)) {
        return $ntuples;
    }
    $out = array();
    foreach ($ntuples as $ntuple) {
        if (isset($ntuple['lb'])) {
            $out[] = $ntuple['lb'] . implode(',', $ntuple['vals']) . $ntuple['rb'];
        } else if (is_array($ntuple[0])) {
            $sub = array();
            foreach ($ntuple as $subtuple) {
                $sub[] = $subtuple['lb'] . implode(',', $subtuple['vals']) . $subtuple['rb'];
            }
            $out[] = implode(' or ', $sub);
        }
    }
    return implode(',', $out);
}


function parseInterval($str, $islist = false) {
    if (strlen($str) < 5) {
        return false;
    }
    if ($islist) {
        $ints = preg_split('/(?<=[\)\]])\s*,\s*(?=[\(\[])/', $str);
    } else {
        $ints = explode('U', $str);
    }

    $out = array();
    foreach ($ints as $int) {
        $int = trim($int);
        if (strlen($int) < 5) {
            return false;
        }
        $i = array();
        $i['lb'] = $int[0];
        $i['rb'] = $int[strlen($int) - 1];
        $i['vals'] = array_map('trim', explode(',', substr($int, 1, -1)));
        if (count($i['vals']) != 2) {
            return false;
        }
        for ($j = 0; $j < 2; $j++) {
            if ($i['vals'][$j] == '+oo') {
                $i['vals'][$j] = 'oo';
            }
            if (
                !is_numeric($i['vals'][$j]) &&
                $i['vals'][$j] != 'oo' && $i['vals'][$j] != '-oo'
            ) {
                $i['vals'][$j] = evalMathParser($i['vals'][$j]);
            }
        }
        $out[] = $i;
    }
    return $out;
}

function parsedIntervalToString($parsed, $islist) {
    $out = [];
    if ($parsed === false) {
        return '';
    }
    foreach ($parsed as $int) {
        $out[] = $int['lb'] . $int['vals'][0] . ',' . $int['vals'][1] . $int['rb'];
    }
    if ($islist) {
        return implode(',', $out);
    } else {
        return implode('U', $out);
    }
}

function parseChemical($string) {
    $string = str_replace(['<->', '<=>'], 'rightleftharpoons', $string);
    $string = str_replace(['to', 'rarr', 'implies'], '->', $string);
    $string = preg_replace('/\^{(.*?)}/', '^($1)', $string);
    $string = preg_replace('/\(\(([^\(\)]*)\)\)/', '($1)', $string);
    $string = str_replace('^+', '^(+)', $string);
    $parts = preg_split('/(->|rightleftharpoons)/', $string, -1, PREG_SPLIT_DELIM_CAPTURE);
    $reactiontype = (count($parts) > 1) ? $parts[1] : null;
    $sides = [];
    for ($i = 0; $i < count($parts); $i += 2) {
        $sideparts = [];
        $lastcut = 0;
        $str = $parts[$i];
        $strlen = strlen($str);
        $depth = 0;
        // cut by + signs, parse out coefficient and chemical
        for ($p = 1; $p < $strlen - 1; $p++) {
            $c = $str[$p];
            if ($c == '+' && $depth == 0 && $str[$p - 1] != '^' && $str[$p - 1] != '_') {
                preg_match('/^\s*(\d+)?\s*\*?\s*(.*?)\s*$/', substr($str, $lastcut, $p - $lastcut), $matches);
                $sideparts[] = [
                    $matches[1] === '' ? 1 : intval($matches[1]),
                    str_replace(' ', '', $matches[2])
                ];
                $lastcut = $p + 1;
            } else if ($c == '(' || $c == '[') {
                $depth++;
            } else if ($c == ')' || $c == ']') {
                $depth--;
            }
        }
        preg_match('/^\s*(\d+)?\s*\*?\s*(.*?)\s*$/', substr($str, $lastcut), $matches);
        $sideparts[] = [
            $matches[1] === '' ? 1 : intval($matches[1]),
            str_replace(' ', '', $matches[2])
        ];
        // sort by chemical to put in standard order
        usort($sideparts, function ($a, $b) {
            return strcmp($a[1], $b[1]);
        });
        $sides[] = $sideparts;
    }
    // if dual direction reaction, sort sides to standarize
    if (count($sides) > 1 && $reactiontype == 'rightleftharpoons') {
        usort($sides, function ($a, $b) {
            return strcmp($a[0][1], $b[0][1]);
        });
    }
    return [$sides, $reactiontype];
}

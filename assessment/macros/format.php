<?php

// format and cleaning macros

array_push(
    $GLOBALS['allowedmacros'],
    'makepretty',
    'makexpretty',
    'makexxpretty',
    'polymakepretty',
    'makeprettynegative',
    'makeprettydisp',
    'makexprettydisp',
    'makexxprettydisp',
    'polymakeprettydisp',
    'prettynegs',
    'dispreducedfraction',
    'makereducedmixednumber',
    'makereducedfraction',
    'prettyint',
    'prettyreal',
    'prettyreal_instring',
    'round_instring',
    'prettysmallnumber',
    'prettysigfig',
    'roundsigfig',
    'prettysigfig_instring',
    'makescinot',
    'numtowords',
    'fractowords',
    'prettytime',
    'today',
    'numtoroman',
    'htmldisp',
    'formatcomplex',
    'strip_parens'
);

function makepretty($exp) {
    if (is_array($exp)) {
        for ($i = 0; $i < count($exp); $i++) {
            $exp[$i] = clean($exp[$i]);
        }
    } else {
        $exp = clean($exp);
    }
    return $exp;
}

function makexpretty($exp) {
    if (is_array($exp)) {
        for ($i = 0; $i < count($exp); $i++) {
            $exp[$i] = xclean($exp[$i]);
        }
    } else {
        $exp = xclean($exp);
    }
    return $exp;

    //return makexxpretty($exp);
}

function makexxpretty($exp, $funcs = array()) {
    if (is_array($exp)) {
        for ($i = 0; $i < count($exp); $i++) {
            $exp[$i] = clean($exp[$i]);
            $exp[$i] = cleanbytoken($exp[$i], $funcs);
        }
    } else {
        $exp = clean($exp);
        $exp = cleanbytoken($exp, $funcs);
    }
    return $exp;
}

function polymakepretty($exp) {
    if (is_array($exp)) {
        for ($i = 0; $i < count($exp); $i++) {
            $exp[$i] = polyclean($exp[$i]);
        }
    } else {
        $exp = polyclean($exp);
    }
    return $exp;
}

function makeprettynegative($exp) {
    //3--4 to 3-(-4).  3+-4 to 3+(-4).  3-+4 to 3-4
    $exp = preg_replace('/(\+|\-)\s+(\+|\-)/', "$1$2", $exp);
    $exp = str_replace("-+", "-", $exp);
    $exp = preg_replace('/--([\d\.]+)/', '-(-$1)', $exp);
    $exp = preg_replace('/\+-([\d\.]+)/', '+(-$1)', $exp);
    return $exp;
}

function makeprettydisp($exp) {
    if (is_array($exp)) {
        for ($i = 0; $i < count($exp); $i++) {
            $exp[$i] = "`" . clean($exp[$i]) . "`";
        }
    } else {
        $exp = "`" . clean($exp) . "`";
    }
    return $exp;
}

function makexprettydisp($exp) {
    if (is_array($exp)) {
        for ($i = 0; $i < count($exp); $i++) {
            $exp[$i] = "`" . xclean($exp[$i]) . "`";
        }
    } else {
        $exp = "`" . xclean($exp) . "`";
    }
    return $exp;
    //return makexxprettydisp($exp);
}

function makexxprettydisp($exp, $funcs = array()) {
    if (is_array($exp)) {
        for ($i = 0; $i < count($exp); $i++) {
            $exp[$i] = clean($exp[$i]);
            $exp[$i] = "`" . cleanbytoken($exp[$i], $funcs) . "`";
        }
    } else {
        $exp = clean($exp);
        $exp = "`" . cleanbytoken($exp, $funcs) . "`";
    }
    return $exp;
}

function polymakeprettydisp($exp) {
    if (is_array($exp)) {
        for ($i = 0; $i < count($exp); $i++) {
            $exp[$i] = "`" . polyclean($exp[$i]) . "`";
        }
    } else {
        $exp = "`" . polyclean($exp) . "`";
    }
    return $exp;
}

function prettynegs($a) {
    return str_replace('-', '&#x2212;', $a);
}


function dispreducedfraction($n, $d, $dblslash = false, $varinnum = false) {
    return '`' . makereducedfraction($n, $d, $dblslash, $varinnum) . '`';
}

function makereducedmixednumber($n, $d) {
    if ($n == 0) {
        return '0';
    }
    if ($d == 0) {
        return 'undefined';
    }
    $g = gcd($n, $d);
    if ($g > 1) {
        $n = $n / $g;
        $d = $d / $g;
    }
    if ($d < 0) {
        $n = $n * -1;
        $d = $d * -1;
    }
    if ($d == 1) {
        return "$n";
    } else {
        if (abs($n) > $d) {
            $w = floor(abs($n) / $d);
            if ($n < 0) {
                $w *= -1;
            }
            $n -= $w * $d;
            return "$w " . abs($n) . "/$d";
        } else {
            return "$n/$d";
        }
    }
}

function makereducedfraction($n, $d, $dblslash = false, $varinnum = false) {
    if ($n == 0) {
        return '0';
    }
    if ($d == 0) {
        return 'undefined';
    }
    if (!is_numeric($n) || !is_numeric($d)) {
        echo "makereducedfraction requires numeric inputs";
        return $n . '/' . $d;
    }
    $g = gcd($n, $d);
    if ($g > 1) {
        $n = $n / $g;
        $d = $d / $g;
    }
    if ($d < 0) {
        $n = $n * -1;
        $d = $d * -1;
    }
    if ($dblslash === 'parts') {
        return [$n, $d];
    }
    $sign = '';
    if ($n < 0) {
        $sign = '-';
        $n = abs($n);
    }
    if ($varinnum !== false) {
        if ($n == 1) {
            $n = '';
        }
    }
    if ($d == 1) {
        if ($varinnum === false) {
            return "$sign$n";
        } else {
            return "$sign$n$varinnum";
        }
    } else {
        if ($dblslash) {
            $slash = '//';
        } else {
            $slash = '/';
        }
        if ($varinnum === false) {
            return "$sign$n$slash$d";
        } else {
            return "$sign($n$varinnum)$slash$d";
        }
    }
}

function prettyint($n) {
    if (!is_numeric($n)) {
        return $n;
    }
    return number_format($n);
}

function prettyreal($aarr, $d = 0, $comma = ',') {
    if (!is_array($aarr)) {
        $arrayout = false;
        $aarr = array($aarr);
    } else {
        $arrayout = true;
    }
    $out = array();
    $d = intval($d);
    foreach ($aarr as $a) {
        $a = str_replace(',', '', $a);
        if (is_numeric($a)) {
            $out[] = number_format($a, $d, '.', $comma);
        } else {
            $out[] = $a;
        }
    }
    if ($arrayout) {
        return $out;
    } else {
        return $out[0];
    }
}
function prettyreal_instring($str, $d = 0, $comma = ',') {
    return preg_replace_callback('/\d*\.?\d+/', function ($m) use ($d, $comma) {
        return prettyreal($m[0], $d, $comma);
    }, $str);
}
function round_instring($str, $d = 0) {
    return preg_replace_callback('/\d*\.?\d+/', function ($m) use ($d) {
        return round($m[0], $d);
    }, $str);
}
function prettysmallnumber($n, $space = false) {
    if (abs($n) < .01) {
        $a = explode("E", $n);
        if (count($a) == 2) {
            if ($n < 0) {
                $sign = '-';
            } else {
                $sign = '';
            }
            $n = str_repeat("0", -$a[1] - 1) . str_replace('.', '', abs($a[0]));
            if ($space) {
                $n = preg_replace('/(\d{3})/', '$1&thinsp;', $n);
            }
            $n = $sign . "0." . $n;
        }
    }
    return $n;
}


function roundsigfig($val, $sigfig) {
    $log = (int) floor(log10(abs($val)));
    return round($val, $sigfig - $log - 1);
}

function prettysigfig($aarr, $sigfig, $comma = ',', $choptrailing = false, $orscinot = false, $sigfigbar = false) {
    if (!is_array($aarr)) {
        $arrayout = false;
        $aarr = array($aarr);
    } else {
        $arrayout = true;
    }
    $out = array();

    foreach ($aarr as $a) {
        $a = str_replace(',', '', $a);
        if ($a === 'DNE') {
            $out[] = $a;
            continue;
        }
        if ($orscinot && is_numeric($a) && (abs($a) > 1000 || abs($a) < .001)) {
            $out[] = makescinot($a, $sigfig - 1, '*');
            continue;
        }
        $a = str_replace('xx', '*', $a);
        if (strpos($a, '*') !== false) {
            $pts = explode('*', $a);
            $a = $pts[0];
            $scinot = '*' . $pts[1];
        } else {
            $scinot = '';
        }
        if ($a == 0) {
            $out[] = 0;
            continue;
        }
        if (!is_numeric($a)) {
            $out[] = $a;
            continue;
        }
        if ($a < 0) {
            $sign = '-';
            $a *= -1;
        } else {
            $sign = '';
        }
        $loga = log10($a);
        $v = floor(-$loga - 1e-12);
        if ($v + $sigfig <= 0) {
            $multof3 = floor(- ($v + $sigfig) / 3);
            $tmp = round($a / pow(10, $multof3 * 3), $v + $sigfig + $multof3 * 3);
            $a = number_format($tmp, 0, '.', $comma) . str_repeat($comma . '000', $multof3);
            if ($sigfigbar) {
                //number of digits before first comma
                $digbc = floor(($loga + 1) % 3) + 3 * (($loga + 1) % 3 == 0);
                $anums = preg_replace('/[^\d]/', '', $a);
                if ($comma != '') {
                    //number of commas before sigfig digit
                    $acom = ($sigfig > $digbc) + floor(($sigfig - 1 - $digbc) / 3) * ($sigfig > $digbc);
                } else {
                    $acom = 0;
                }
                if (isset($anums[$sigfig]) && $anums[$sigfig] === '0' && $anums[$sigfig - 1] === '0') {
                    $a = substr_replace($a, 'overline(0)', $sigfig - 1 + $acom * strlen($comma), 1);
                } elseif ($anums[$sigfig - 1] === '0' && !isset($anums[$sigfig])) {
                    $a = $a . ".";
                }
            }
            $out[] = $sign . $a . $scinot;
        } else {
            $nv = round($a, $v + $sigfig);
            $n = number_format($a, $v + $sigfig, '.', $comma);
            if ($choptrailing && ($v + $sigfig > 0) && abs($a - round($a, $v + $sigfig)) < 1e-12) {
                $n = rtrim($n, '0');
                $n = rtrim($n, '.');
            } else {
                if (floor(-log10($nv) - 1e-12) != $v) {  //adjust for .009 -> .010 1 sig
                    $n = substr($n, 0, -1);
                }
                $n = rtrim($n, '.');
            }
            $out[] = $sign . $n . $scinot;
        }
    }
    if ($arrayout) {
        return $out;
    } else {
        return $out[0];
    }
}

function prettysigfig_instring($str, $sigfig, $comma = ',', $choptrailing = false, $orscinot = false, $sigfigbar = false) {
    return preg_replace_callback('/\d*\.?\d+/', function ($m) use ($sigfig, $comma, $choptrailing, $orscinot, $sigfigbar) {
        return prettysigfig($m[0], $sigfig, $comma = ',', $choptrailing = false, $orscinot = false, $sigfigbar = false);
    }, $str);
}

function makescinot($n, $d = 8, $f = "x") {
    if (!is_numeric($n)) {
        echo "makescinot needs numeric input; $n given.";
        return $n;
    }
    if ($n == 0) {
        return "0";
    }
    $isneg = "";
    if ($n < 0) {
        $isneg = "-";
        $n = abs($n);
    }
    $exp = floor(log10($n) + 1e-12);
    if ($d == -1) {
        $mant = round($n / pow(10, $exp), 8);
    } else {
        $mant = number_format($n / pow(10, $exp), $d);
    }
    if ($f == "*") {
        return "$isneg$mant*10^($exp)";
    } else if ($f == "E") {
        return "$isneg{$mant}E$exp";
    } else {
        return "$isneg$mant xx 10^($exp)";
    }
}

function numtowords($num, $doth = false, $addcontractiontonum = false, $addcommas = false) {
    global $placevals;

    if ($addcontractiontonum) {
        $num = strval($num);
        $len = strlen($num);
        $last = $num[$len - 1];
        if ($len > 1 && $num[$len - 2] == "1") { //ie 612
            $c = "th";
        } else if ($last == "1") {
            $c = "st";
        } else if ($last == "2") {
            $c = "nd";
        } else if ($last == "3") {
            $c = "rd";
        } else {
            $c = "th";
        }
        return $num . $c;
    }
    if ($num == 0) {
        return "zero";
    }
    $out = '';
    if ($num < 0) {
        $out .= 'negative ';
        $num = abs($num);
    }
    $num = round($num, 9);
    $int = floor($num);
    $dec = round($num - $int, 9);

    if ($int > 0) {
        $out .= convertTri($int, 0, $doth, $addcommas);
        if (abs($dec) > 1e-10) {
            $out .= " and ";
        }
    }
    if (abs($dec) > 1e-10) {
        $cnt = 0;
        while (abs($dec - round($dec)) > 1e-10 && $cnt < 9) {
            $dec = round(10 * $dec, 9);
            $cnt++;
        }
        $out .= convertTri(round($dec), 0);
        $out .= ' ' . $placevals[$cnt];
        if (round($dec) != 1) {
            $out .= 's';
        }
    }
    return trim($out);
}

function fractowords($numer, $denom, $options = 'no') { //options can combine 'hyphen','mixed','over','by' and 'literal'
    global $placevals;

    if (strpos($options, 'mixed') === false) {
        $int = '';
    }
    $numersign = sign($numer);
    $denomsign = sign($denom);
    //creates integer and new numerator for mixed numbers
    if (strpos($options, 'mixed') !== false || strpos($options, 'literal') === false) { //mixed or not literal
        if (abs($numer - floor($numer)) > 1e-9 || abs($denom - floor($denom)) > 1e-9) { //integers only
            return '';
        }
        if ($denom == 0) {
            echo 'Eek! Division by zero.';
            return '';
        }
        if ($numer == 0) {
            return 'zero';
        }
        $numernew = abs($numer) % (abs($denom));
        $numer = abs($numer); //numer and denom now positive
        $denom = abs($denom);
        $int = floor($numer / $denom);

        if ($numernew == 0) { //did fraction reduce to a whole number?
            $int = '';
            $numer = $numer * $numersign;
            $denom = $denom * $denomsign;
            return numtowords($numer / $denom);
        } elseif ($numernew != 0) { //is there a remainder after dividing?
            if ($int == 0) { //was the fraction proper to begin with?
                $numer = $numernew * $numersign * $denomsign;
                $int = '';
            } elseif ($int != 0) { //was the fraction improper to begin with?
                if (strpos($options, 'mixed') === false) { //not mixed and not literal
                    $int = '';
                    $numer = $numer * $numersign * $denomsign;
                } elseif (strpos($options, 'mixed') !== false) { //mixed and not literal
                    $int = numtowords($int * $numersign * $denomsign) . ' and ';
                    $numer = $numernew;
                }
            }
        }
    } //end (mixed or not literal)

    //handles non-mixed numbers or fractional part of mixed numbers
    if (abs($numer - floor($numer)) > 1e-9 || abs($denom - floor($denom)) > 1e-9) { //integers only
        return '';
    }
    if ($denom == 0) {
        return '';
    } else {
        if (strpos($options, 'over') === false && strpos($options, 'by') === false) { //not over, not by
            $top = numtowords($numer);
            if ($denom == 1) {
                $bot = 'whole';
            } elseif ($denom == -1) {
                $bot = 'negative whole';
            } elseif ($denom == 2) {
                if (abs($numer) == 1) {
                    $bot = 'half';
                } elseif ($numer != 1) {
                    $bot = 'halve';
                }
            } elseif ($denom == -2) {
                if ($numer == 1) {
                    $bot = 'negative half';
                } else {
                    $bot = 'negative halve';
                }
            } else if (abs(round(log10(abs($denom))) - log10(abs($denom))) < 1e-12) { // is multiple of 10
                $bot = $placevals[round(log10(abs($denom)))];
            } else {
                $bot = str_replace(' ', '-', numtowords($denom, true));
            }

            $dohypen = (strpos($options, 'hyphen') !== false && strpos($top, '-') === false && strpos($bot, '-') === false);
            if (abs($numer) == 1) {
                return $int . $top . ($dohypen ? '-' : ' ') . $bot;
            } else {
                return $int . $top . ($dohypen ? '-' : ' ') . $bot . 's';
            }
        } elseif (strpos($options, 'over') !== false) { //over or overby, prefers over
            return $int . numtowords($numer) . ' over ' . numtowords($denom);
        } elseif (strpos($options, 'by') !== false) { //by or overby
            return $int . numtowords($numer) . ' by ' . numtowords($denom);
        }
    }
}


function prettytime($time, $in, $out) {
    if ($in == 'm') {
        $time *= 60;
    } else if ($in == 'h') {
        $time *= 60 * 60;
    }
    $hrs = $time / 3600;
    $min = $time / 60;
    $outst = '';
    if (strpos($out, 'clock') !== false) { //clock time
        $hrs = floor($hrs);
        $min = floor($min - 60 * $hrs);
        $sec = round($time - 60 * $min - 3600 * $hrs);
        while ($hrs > 24) {
            $hrs -= 24;
        }
        $ampm = ($hrs < 12 ? "am" : "pm");
        if ($hrs >= 13) {
            $hrs -= 12;
        } else if ($hrs == 0) {
            $hrs = 12;
        }
        if ($out == 'sclock') {
            if ($min < 10) {
                $min = '0' . $min;
            }
            if ($sec < 10) {
                $sec = '0' . $sec;
            }
            $outst = "$hrs:$min:$sec $ampm";
        } else {
            if ($min < 10) {
                $min = '0' . $min;
            }
            $outst = "$hrs:$min $ampm";
        }
        return $outst;
    }
    if (strpos($out, 'h') !== false) { //has hrs
        if (strpos($out, 'm') !== false) { //has min
            $hrs = floor($hrs);
            if (strpos($out, 's') !== false) {  //hrs min sec
                $min = floor($min - 60 * $hrs);
                $sec = round($time - 60 * $min - 3600 * $hrs, 4);
                $outst = "$hrs hour" . ($hrs > 1 ? 's' : '');
                $outst .= ", $min minute" . ($min > 1 ? 's' : '');
                $outst .= ", and $sec second" . ($sec != 1 ? 's' : '');
            } else { //hrs min
                $min = round($min - 60 * $hrs, 4);
                $outst = "$hrs hour" . ($hrs > 1 ? 's' : '');
                $outst .= " and $min minute" . ($min != 1 ? 's' : '');
            }
        } else { //no min
            if (strpos($out, 's') !== false) {  //hrs sec
                $hrs = floor($hrs);
                $sec = round($time - 3600 * $hrs, 4);
                $outst = "$hrs hour" . ($hrs > 1 ? 's' : '');
                $outst .= " and $sec second" . ($sec != 1 ? 's' : '');
            } else { //just hrs
                $hrs = round($hrs, 4);
                $outst = "$hrs hour" . ($hrs != 1 ? 's' : '');
            }
        }
    } else { //no hours
        if (strpos($out, 'm') !== false) { //
            if (strpos($out, 's') !== false) {  //min sec
                $min = floor($min);
                $sec = round($time - 60 * $min, 4);
                $outst = "$min minute" . ($min > 1 ? 's' : '');
                $outst .= " and $sec second" . ($sec != 1 ? 's' : '');
            } else { //min only
                $min = round($min, 4);
                $outst = "$min minute" . ($min != 1 ? 's' : '');
            }
        } else if (strpos($out, 's') !== false) {  //sec
            $time = round($time, 4);
            $outst = "$time second" . ($time != 1 ? 's' : '');
        }
    }
    return $outst;
}


function today($str = "F j, Y") {
    return (date($str));
}

function numtoroman($n, $uc = true) {
    $n = intval($n);
    if ($uc) {
        $lookup = array('M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1, '<span style="border-top:1px solid;">SS</span>' => 0.5);
    } else {
        $lookup = array('m' => 1000, 'cm' => 900, 'd' => 500, 'cd' => 400, 'c' => 100, 'xc' => 90, 'l' => 50, 'xl' => 40, 'x' => 10, 'ix' => 9, 'v' => 5, 'iv' => 4, 'i' => 1, '<span style="border-top:1px solid;">ss</span>' => 0.5);
    }
    $roman = '';
    foreach ($lookup as $r => $v) {
        while ($n >= $v) {
            $roman .= $r;
            $n -= $v;
        }
    }
    return $roman;
}

function htmldisp($str, $var = '') {
    $str = preg_replace('/\^(\w+)/', '#@#$1#@%', $str);
    $str = preg_replace('/_(\w+)/', '#$#$1#$%', $str);
    if ($var == '') {
        $str = preg_replace('/([a-zA-Z]+)/', '<i>$1</i>', $str);
    } else {
        $var = str_replace(',', '|', $var);
        $str = preg_replace("/($var)/", '<i>$1</i>', $str);
    }
    $str = str_replace(array('#@#', '#@%', '#$#', '#$%'), array('<sup>', '</sup>', '<sub>', '</sub>'), $str);
    return $str;
}

function formatcomplex($real, $imag) {
    if ($imag == 0) {
        return $real;
    } else {
        if ($imag == 1) {
            return ($real == 0) ? 'i' : "$real+i";
        } else if ($imag == -1) {
            return ($real == 0) ? '-i' : "$real-i";
        } else if ($imag < 0) {
            return "$real{$imag}i";
        } else {
            return "$real+{$imag}i";
        }
    }
}


/** internal */

function clean($exp) {
    if (is_float($exp) && is_nan($exp)) {
        return 'NAN';
    }
    $exp = preg_replace('/(\+|\-)\s+(\+|\-)/', "$1$2", $exp);
    //$exp = str_replace(" ", "", $exp);  //caused problems with "x > -3"
    $exp = str_replace("+-", "-", $exp);
    $exp = str_replace("-+", "-", $exp);
    $exp = str_replace("--", "+", $exp);
    //$exp = preg_replace('/^1\*?([a-zA-Z\(])/',"$1",$exp);
    //$exp = preg_replace('/([^\d\^\.])1\*?([a-zA-Z\(])/',"$1$2",$exp);
    return $exp;
}

function xclean($exp) {
    //goals are to cleam up  1*, 0*  0+  0-  a^0  a^1
    $exp = clean($exp);
    $exp = preg_replace('/^([a-zA-Z])\^0/', '1', $exp); //x^0 -> 1
    $exp = preg_replace('/(\d)\*?([a-zA-Z])\^0$/', "$1", $exp);   //3x^0  -> 3
    $exp = preg_replace('/(\d)\*?([a-zA-Z])\^0([^\d\.])/', "$1$3", $exp); //3x^0+4 -> 3+4
    $exp = preg_replace('/([^\d])\*?([a-zA-Z])\^0$/', "$1 1", $exp); //y*x^0 -> y*1,  y+x^0 -> y+1
    $exp = preg_replace('/([^\d])\*?([a-zA-Z])\^0([^\d\.])/', "$1 1 $3", $exp);  //y*x^0+3 -> y*1+3,  y+x^0+z -> y+1+z
    $exp = preg_replace('/^0\s*\*?[^\+\-\.]+\+?/', '', $exp); // 0*23+x
    $exp = preg_replace('/^0\s*([\+\-])/', "$1", $exp);  //0+x, 0-x
    $exp = preg_replace('/[\+\-]\s*0\s*\*?[^\.\+\-]+/', '', $exp); //3+0x-4 -> 3-4
    $exp = preg_replace('/[\+\-]\s*0\s*([\+\-])/', "$1", $exp);  //3+0+4 -> 3+4
    $exp = preg_replace('/^1\s*\*?([a-zA-Z])/', "$1", $exp);  //1x -> x
    $exp = preg_replace('/([^\d\^\.])1\s*\*?([a-zA-Z\(])/', "$1$2", $exp);  //3+1x -> 3+x
    $exp = preg_replace('/\^1([^\d])/', "$1", $exp); //3x^1+4 =>3x+4
    $exp = preg_replace('/\^1$/', '', $exp);  //4x^1 -> 4x
    $exp = clean($exp);
    if (isset($exp[0]) && $exp[0] == '+') {
        $exp = substr($exp, 1);
    }
    return $exp;
}

function polyclean($exp) {
    $exp = clean($exp);

    $i = 0;
    $outstr = '';
    $p = 0;
    $parr = array('', '', '');
    $onpow = false;
    $lastsign = '+';
    $exp .= '+';
    while ($i < strlen($exp)) {
        $c = $exp[$i];
        if (($c >= '0' && $c <= '9') || $c == '.' || $c == '/' || $c == '(' || $c == ')') {
            if ($onpow) {
                $parr[2] .= $c;
            } else {
                $parr[0] .= $c;
            }
        } else if (($c <= 'z' && $c >= 'a') || ($c <= 'Z' && $c >= 'A')) {
            $parr[1] .= $c;
        } else if ($c == '^') {
            $onpow = true;
        } else if ($c == '+' || $c == '-') {
            if ($i + 1 < strlen($exp) && $parr[2] == '' && $onpow) {
                $n = $exp[$i + 1];
                if ($c == '-' && (($n >= '0' && $n <= '9') || $n == '.')) {
                    $parr[2] .= '-';
                    $i++;
                    continue;
                }
            }
            if ($parr[0] == '0') {
                $parr = array('', '', '');
                $onpow = false;
                $i++;
                $lastsign = $c;
                continue;
            } else {
                if ($outstr != '' || $lastsign == '-') {
                    $outstr .= $lastsign;
                }
            }
            if ($parr[2] == '0' || ($parr[2] == '' && $parr[1] == '')) {
                if ($parr[1] == '') {
                    $outstr .= $parr[0]; // n
                } else {
                    if ($parr[0] == '') {
                        $outstr .= 1; // x^0
                    } else {
                        $outstr .= $parr[0]; //n x^0
                    }
                }
            } else if ($parr[2] == '') {
                if ($parr[0] == '1') {
                    $outstr .= $parr[1];
                } else {
                    $outstr .= $parr[0] . ' ' . $parr[1];
                }
            } else if ($parr[2] == '1') {
                if ($parr[0] == '1') {
                    $outstr .= $parr[1];
                } else {
                    $outstr .= $parr[0] . ' ' . $parr[1]; //n x^1
                }
            } else {
                if ($parr[0] == '1') {
                    $outstr .= $parr[1] . '^' . $parr[2]; // 1 x^m
                } else {
                    $outstr .= $parr[0] . ' ' . $parr[1] . '^' . $parr[2]; // n x^m
                }
            }
            $lastsign = $c;
            $parr = array('', '', '');
            $onpow = false;
        }
        $i++;
    }
    return $outstr;
    /*
	$exp = clean($exp);
	if ($exp[0]=='+') {
		$exp = substr($exp,1);
	}
	return $exp;
	*/
}

$ones = array("", " one", " two", " three", " four", " five", " six", " seven", " eight", " nine", " ten", " eleven", " twelve", " thirteen", " fourteen", " fifteen", " sixteen", " seventeen", " eighteen", " nineteen");
$onesth = array("th", " first", " second", " third", " fourth", " fifth", " sixth", " seventh", " eighth", " ninth", " tenth", " eleventh", " twelfth", " thirteenth", " fourteenth", " fifteenth", " sixteenth", " seventeenth", " eighteenth", " nineteenth");
$tens = array("", "", " twenty", " thirty", " forty", " fifty", " sixty", " seventy", " eighty", " ninety");
$tensth = array("", "", " twentieth", " thirtieth", " fortieth", " fiftieth", " sixtieth", " seventieth", " eightieth", " ninetieth");
$triplets = array("", " thousand", " million", " billion", " trillion", " quadrillion", " quintillion", " sextillion", " septillion", " octillion", " nonillion");
$placevals = array("", "tenth", "hundredth", "thousandth", "ten-thousandth", "hundred-thousandth", "millionth", "ten-millionth", "hundred-millionth", "billionth");
// recursive fn, converts three digits per pass
function convertTri($num, $tri, $doth = false, $addcommas = false) {
    global $ones, $onesth, $tens, $tensth, $triplets;

    // chunk the number, ...rxyy
    $r = (int) ($num / 1000);
    $x = ($num / 100) % 10;
    $y = $num % 100;

    // init the output string
    $str = "";

    // do hundreds
    if ($x > 0)
        $str = $ones[$x] . " hundred";

    // do ones and tens
    if ($y < 20) {
        if ($doth && $tri == 0) {
            $str .= $onesth[$y];
        } else {
            $str .= $ones[$y];
        }
    } else {
        if ($doth && $tri == 0) {
            if ($y % 10 == 0) {
                $str .= $tensth[(int) ($y / 10)];
            } else {
                $str .= $tens[(int) ($y / 10)] . '-' . trim($onesth[$y % 10]);
            }
        } else {
            $str .= $tens[(int) ($y / 10)] . (($y % 10 == 0) ? '' : '-' . trim($ones[$y % 10]));
        }
    }
    // add triplet modifier only if there
    // is some output to be modified...
    if ($str != "")
        $str .= $triplets[$tri];
    // continue recursing?
    if ($r > 0) {
        $prev = convertTri($r, $tri + 1, false, $addcommas);
        return $prev . (($addcommas && $prev != '' && $str != '') ? ',' : '') . $str;
    } else {
        return $str;
    }
}


function cleanbytoken($str, $funcs = array()) {
    if (is_array($str)) {
        return $str;
    } //avoid errors by just skipping this if called with an array somehow
    $str = str_replace(['`', '\\'], '', $str);
    $instr = 0;
    $primeoff = 0;
    while (($p = strpos($str, "'", $primeoff)) !== false) {
        if ($instr == 0) {  //if not a match for an earlier quote
            if ($p > 0 && (ctype_alpha($str[$p - 1]) || $str[$p - 1] == '`')) {
                $str[$p] = '`';
            } else {
                $instr = 1 - $instr;
            }
        }
        $primeoff = $p + 1;
    }
    $str = preg_replace('/&(gt|lt|ge|le|ne);/', ' $1 ', $str);
    $finalout = array();
    if (trim($str) == '') {
        return '';
    }
    $tokens = cleantokenize(trim($str), $funcs);

    $out = array();
    $lasti = count($tokens) - 1;
    $grplasti = -2;
    for ($i = 0; $i <= $lasti; $i++) {
        $token = $tokens[$i];
        $lastout = count($out) - 1;
        if ($i > 0 && $tokens[$i - 1][1] == 12) { // following a separator
            $lastout = -1;
        }
        if ($grplasti < $i - 1) { // find next separator
            $grplasti = $lasti;
            for ($j = $i; $j <= $lasti; $j++) {
                if ($tokens[$j][1] == 12) {
                    $grplasti = $j - 1;
                    break;
                }
            }
        }

        if ($token[1] == 12) { // separator
            if (count($out) > 0 && $out[0] == '+') {
                array_shift($out);
            }
            if (count($out) == 0 && $i > 0) {
                $finalout[] = '0';
            } else {
                /* Disabled for now - causing issues with badly written questions
                // try to strip extraneous parens
                $cout = count($out);
                for ($j=0;$j<$cout;$j++) {
                    if (is_string($out[$j]) && strlen($out[$j])>2 && $out[$j][0]=='(' && $out[$j][strlen($out[$j])-1]==')' && strpos($out[$j],',')===false) {
                        if (($j==0 || $out[$j-1]=='+') &&
                            ($j==$cout-1 || $out[$j+1]=='+' || $out[$j+1]=='-')) {
                                $out[$j] = substr($out[$j],1,-1);
                        }
                    }
                }
                */
                $finalout[] = implode('', $out);
            }
            $finalout[] = $token[0];
            $out = []; //reset
            if ($i == $grplasti) { // if nothing is following last separator, prevent 0 being added
                $out[] = ' ';
            }
        } else if ($token[1] == 3 && $token[0] === '0') { //is the number 0 by itself
            $isone = 0;
            if ($lastout > -1) { //if not first character
                if ($out[$lastout] == '^') {
                    $isone = 2;
                    if ($lastout >= 2 && ($out[$lastout - 2] == '+' || $out[$lastout - 2] == '-' || $out[$lastout - 2] == 'pm')) {
                        //4x+x^0 -> 4x+1
                        array_splice($out, -2);
                        $out[] = 1;
                    } else if ($lastout >= 2) {
                        $isone = 1;
                        //4x^0->4, 5(x+3)^0 -> 5
                        array_splice($out, -2);
                    } else if ($lastout == 1) {
                        //x^0 -> 1
                        $out = array(1);
                    }
                } else if ($out[$lastout] == '_') {
                    $out[] = 0;
                    continue;
                } else {
                    //( )0, + 0, x0
                    while ($lastout > -1 && $out[$lastout] != '+' && $out[$lastout] != '-' && $out[$lastout] != 'pm') {
                        array_pop($out);
                        $lastout--;
                    }
                    if ($lastout > -1) {
                        array_pop($out);
                        $lastout--;
                    }
                }
            }
            if ($i < $grplasti) { //if not last character
                if ($tokens[$i + 1][0] == '^') {
                    //0^3
                    $i += 2; //skip over ^ and 3
                } else if ($isone) {
                    if ($tokens[$i + 1][0] != '+' && $tokens[$i + 1][0] != '-' && $tokens[$i + 1][0] != 'pm' && $tokens[$i + 1][0] != '/') {
                        if ($isone == 2) {
                            array_pop($out);  //pop the 1 we added since it apperears to be multiplying
                        }
                        if ($tokens[$i + 1][0] == '*') {  //x^0*y
                            $i++;
                        }
                    }
                } else {
                    while ($i < $grplasti && $tokens[$i + 1][0] != '+' && $tokens[$i + 1][0] != '-' && $tokens[$i + 1][0] != 'pm') {
                        $i++;
                    }
                }
                if ($lastout == -1 && $i < $grplasti && $tokens[$i + 1][0] == '+') {
                    $i++; // skip leading + if we removed 0 from start
                }
            }
        } else if ($token[1] == 3 && $token[0] === '1') {
            $dontuse = false;
            if ($lastout > -1) { //if not first character
                if ($out[$lastout] != '^' && $out[$lastout] != '/' && $out[$lastout] != '+' && $out[$lastout] != '-' && $out[$lastout] != 'pm' && $out[$lastout] != ' ' && $out[$lastout] != '_') {
                    //( )1, x1,*1
                    if ($out[$lastout] == '*') { //elim *
                        array_pop($out);
                    }
                    $dontuse = true;
                } else if ($out[$lastout] == '^' || $out[$lastout] == '/') {
                    if ($lastout >= 1) {
                        //4+x^1 -> 4+x, 4x^1 -> 4x,   x/1 -> x
                        array_pop($out);
                        $dontuse = true;
                        continue;
                    }
                } else if ($out[$lastout] == '_') {
                    $out[] = 1;
                    continue;
                } else if ($out[$lastout] == '-' && $lastout > 0 && (
                    $out[$lastout - 1] == '^' || $out[$lastout - 1] == '/' || $out[$lastout - 1] == '_'
                )) {
                    // x^-1*5, 5/-1*6, a_-1*6, leave alone
                    $out[] = 1;
                    continue;
                }
            }

            if ($i < $grplasti) { //if not last character
                if ($tokens[$i + 1][0] == '^') {
                    //1^3
                    $i += 2; //skip over ^ and 3
                } else if ($tokens[$i + 1][0] == '*') {
                    $i++;  //skip over *
                    $dontuse = true;
                } else if ($tokens[$i + 1][0] != '+' && $tokens[$i + 1][0] != '-' && $tokens[$i + 1][0] != 'pm' && $tokens[$i + 1][0] != '/' && !is_numeric($tokens[$i + 1][0])) {
                    // 1x, 1(), 1sin
                    if ($lastout < 2 || (($out[$lastout - 1] != '^' && $out[$lastout - 1] != '/') || $out[$lastout] != '-')) { //exclude ^-1 case and /-1 case
                        $dontuse = true;
                    }
                }
            }

            if (!$dontuse) {
                $out[] = 1;
            } else {
                continue;
            }
        } else {
            $out[] = $token[0];
        }
        if ($i < $grplasti && (($token[1] == 3 && $tokens[$i + 1][1] == 3) || ($token[1] == 4 && ($tokens[$i + 1][1] == 4 || $tokens[$i + 1][1] == 2)))) {
            $out[] = ' ';
        }
    }
    if (count($out) > 0 && $out[0] == '+') {
        array_shift($out);
    }

    if (count($out) == 0 && $lastout == -1) {
        $finalout[] = '0';
    } else {
        /* Disabled for now - causing issues with badly written questions
        // try to strip extraneous parens
        $cout = count($out);
        for ($i=0;$i<$cout;$i++) {
            if (is_string($out[$i]) && strlen($out[$i])>2 && $out[$i][0]=='(' && $out[$i][strlen($out[$i])-1]==')' && strpos($out[$i],',')===false) {
                if (($i==0 || $out[$i-1]=='+') &&
                    ($i==$cout-1 || $out[$i+1]=='+' || $out[$i+1]=='-')) {
                        $out[$i] = substr($out[$i],1,-1);
                }
            }
        }
        */
        $finalout[] = implode('', $out);
    }

    return str_replace('`', "'", implode(' ', $finalout));
}


function cleantokenize($str, $funcs) {
    /*
	2: function name
	3: number
	4: variable
	5: curlys 
	6: string
	7: line break?
	8: parens
	11: array index brackets
	12: separator
	*/
    $str = (string) $str;
    $knownfuncs = array_merge($funcs, array("sin", "cos", "sec", "csc", "tan", "csc", "cot", "sinh", "cosh", "sech", "csch", "tanh", "coth", "arcsin", "arccos", "arcsec", "arccsc", "arctan", "arccot", "arcsinh", "arccosh", "arctanh", "sqrt", "ceil", "floor", "root", "log", "ln", "abs", "max", "min"));

    $lookfor = array("e", "pi");
    $maxvarlen = 0;
    foreach ($lookfor as $v) {
        $l = strlen($v);
        if ($l > $maxvarlen) {
            $maxvarlen = $l;
        }
    }
    $connecttolast = 0;
    $i = 0;
    $cnt = 0;
    $len = strlen($str);
    $syms = array();
    $lastsym = array();
    while ($i < $len) {
        $cnt++;
        if ($cnt > 100) {
            exit;
        }
        $intype = 0;
        $out = '';
        $c = $str[$i];
        $eatenwhite = 0;
        if ($c == ',') {
            $intype = 12;
            $out .= $c;
            $i++;
        } else if ($c == '<' || $c == '>' || $c == '=') {
            $intype = 12;
            $out .= $c;
            $i++;
            if ($i < $len && $str[$i] == '=') {
                $out .= $str[$i];
                $i++;
            }
        } else if ($c >= "a" && $c <= "z" || $c >= "A" && $c <= "Z") {
            //is a string or function name

            $intype = 2; //string like function name
            do {
                $out .= $c;
                $i++;
                if ($i == $len) {
                    break;
                }
                $c = $str[$i];
            } while ($c >= "a" && $c <= "z" || $c >= "A" && $c <= "Z"); // took out : || $c>='0' && $c<='9'  don't need sin3 type function names for cleaning
            //check if it's a special word
            if ($out == 'e') {
                $intype = 3;
            } else if ($out == 'pi') {
                $intype = 3;
            } else if ($out == 'gt' || $out == 'lt' || $out == 'ge' || $out == 'le' || $out == 'geq' || $out == 'leq' || $out == 'ne' || $out == 'or') {
                $intype = 12; // separator
            } else if ($out == 'pm') {
                $intype = 0;
            } else {
                //eat whitespace
                while ($c == ' ') {
                    $i++;
                    $c = $str[$i];
                    $eatenwhite++;
                }
                //if known function at end, strip off function
                if ($c == '(' && !in_array($out, $knownfuncs)) { // moved to mathphppre-> || ($c=='^' && (substr($str,$i+1,2)=='-1' || substr($str,$i+1,4)=='(-1)'))) {
                    $outlen = strlen($out);
                    $outend = '';
                    for ($j = 1; $j < $outlen - 1; $j++) {
                        $outend = substr($out, $j);
                        if (in_array($outend, $knownfuncs)) {
                            $i = $i - $outlen + $j;
                            $c = $str[$i];
                            $out = substr($out, 0, $j);
                            break;
                        }
                    }
                }

                //if there's a ( then it's a function if it's in our list
                if ($c == '(' && $out != 'e' && $out != 'pi' && in_array($out, $knownfuncs)) {
                    //connect upcoming parens to function
                    $connecttolast = 2;
                } else {
                    //is it a known function?
                    if (in_array($out, $knownfuncs)) {
                        $intype = 6;
                    } else {
                        //if not, assume it's a variable
                        $intype = 4;
                    }
                }
            }
        } else if (($c >= '0' && $c <= '9') || ($c == '.'  && (isset($str[$i + 1]) && $str[$i + 1] >= '0' && $str[$i + 1] <= '9'))) { //is num
            $intype = 3; //number
            $cont = true;
            //handle . 3 which needs to act as concat
            if (isset($lastsym[0]) && $lastsym[0] == '.') {
                $syms[count($syms) - 1][0] .= ' ';
            }
            do {
                $out .= $c;
                $lastc = $c;
                $i++;
                if ($i == $len) {
                    break;
                }
                $c = $str[$i];
                if (($c >= '0' && $c <= '9') || ($c == '.' && (!isset($str[$i + 1]) || $str[$i + 1] != '.') && $lastc != '.')) {
                    //is still num
                } else if (($c == 'e' || $c == 'E') && isset($str[$i + 1])) {
                    //might be scientific notation:  5e6 or 3e-6
                    $d = $str[$i + 1];
                    if ($d >= '0' && $d <= '9') {
                        $out .= $c;
                        $i++;
                        if ($i == $len) {
                            break;
                        }
                        $c = $str[$i];
                    } else if (($d == '-' || $d == '+') && (isset($str[$i + 2]) && $str[$i + 2] >= '0' && $str[$i + 2] <= '9')) {
                        $out .= $c . $d;
                        $i += 2;
                        if ($i >= $len) {
                            break;
                        }
                        $c = $str[$i];
                    } else {
                        $cont = false;
                    }
                } else {
                    $cont = false;
                }
            } while ($cont);
        } else if ($c == '(' || $c == '{' || $c == '[') { //parens or curlys
            if ($c == '(') {
                $intype = 8; //parens
                $leftb = '(';
                $rightb = ')';
            } else if ($c == '{') {
                $intype = 5; //curlys
                $leftb = '{';
                $rightb = '}';
            } else if ($c == '[') {
                $intype = 11; //array index brackets
                $leftb = '[';
                $rightb = ']';
            }
            $thisn = 1;
            $inq = false;
            $j = $i + 1;
            $len = strlen($str);
            $qtype = null;
            while ($j < $len) {
                //read terms until we get to right bracket at same nesting level
                //we have to avoid strings, as they might contain unmatched brackets
                $d = $str[$j];
                if ($inq) {  //if inquote, leave if same marker (not escaped)
                    if ($d == $qtype && $str[$j - 1] != '\\') {
                        $inq = false;
                    }
                } else {
                    if ($d == '"' || $d == "'") {
                        $inq = true; //entering quotes
                        $qtype = $d;
                    } else if ($d == $leftb) {
                        $thisn++;  //increase nesting depth
                    } else if ($d == $rightb) {
                        $thisn--; //decrease nesting depth
                        if ($thisn == 0) {
                            //read inside of brackets, send recursively to interpreter
                            $inside = cleanbytoken(substr($str, $i + 1, $j - $i - 1), $funcs);
                            if ($inside == 'error') {
                                //was an error, return error token
                                return array(array('', 9));
                            }
                            //if curly, make sure we have a ;, unless preceeded by a $ which
                            //would be a variable variable
                            //if ($rightb=='}' && $lastsym[0]!='$') {
                            //	$out .= $leftb.$inside.';'.$rightb;
                            // removed 10/15/20 ^^ why the semicolon??
                            if ($rightb == '}') {
                                $out .= $leftb . ' ' . $inside . $rightb;
                            } else {
                                $out .= $leftb . $inside . $rightb;
                            }
                            $i = $j + 1;
                            break;
                        }
                    } else if ($d == "\n") {
                        //echo "unmatched parens/brackets - likely will cause an error";
                    }
                }
                $j++;
            }
            if ($j == $len) {
                $i = $j;
                echo "unmatched parens/brackets - likely will cause an error";
            } else if ($i < $len) {
                $c = $str[$i];
            }
        } else if ($c == '"' || $c == "'") { //string
            $intype = 6;
            $qtype = $c;
            do {
                $out .= $c;
                $i++;
                if ($i == $len) {
                    break;
                }
                $lastc = $c;
                $c = $str[$i];
            } while (!($c == $qtype && $lastc != '\\'));
            $out .= $c;

            $i++;
            if ($i < $len) {
                $c = $str[$i];
            }
        } else {
            //no type - just append string.  Could be operators
            $out .= $c;
            $i++;
            if ($i < $len) {
                $c = $str[$i];
            }
        }
        while ($c == ' ') { //eat up extra whitespace
            $i++;
            if ($i == $len) {
                break;
            }
            $c = $str[$i];
            if ($c == '.' && $intype == 3) { //if 3 . needs space to act like concat
                $out .= ' ';
            }
        }
        if ($intype == 12) { // remove whitespace before and after separator
            while ($i < $len && $str[$i] == ' ') {
                $i++;
            }
            while (count($syms) > 0 && $syms[count($syms) - 1][0] == ' ') {
                array_pop($syms);
            }
            $connecttolast = 0;
        }
        //if parens or array index needs to be connected to func/var, do it
        if ($connecttolast > 0 && $intype != $connecttolast) {

            $syms[count($syms) - 1][0] .= $out;
            $connecttolast = 0;
            if ($c == '[') { // multidim array ref?
                $connecttolast = 1;
            }
        } else {
            //add to symbol list, avoid repeat end-of-lines.
            if ($intype != 7 || $lastsym[1] != 7) {
                $lastsym = array($out, $intype);
                $syms[] =  array($out, $intype);
            }
        }
    }
    return $syms;
}

function makeprettyarray($a) {
    for ($i = 0; $i < count($a); $i++) {
        $a = makepretty($a);
    }
}

function makeprettydisparray($a) {
    for ($i = 0; $i < count($a); $i++) {
        $a = "`" . makepretty($a) . "`";
    }
}

/**
 * strip_parens2.php PHP function version  strip_parens.php is the inline, no function version 
 *
 * Provides strip_parens($expr): removes unneeded parentheses/brackets from
 * an algebraic expression string. Makes NO other simplifications.
 *
 * RULE
 * ----
 * Repeatedly find a parenthesized/bracketed group (t) — scanning left to
 * right and applying the first eligible group each pass, then restarting
 * the scan — that is not a function-call argument list (e.g. not the
 * "(x)" in "cos(x)"), and rewrite
 *
 *     x (t) y   ->   x t y
 *
 * whenever it is safe to do so, where x is whatever sits immediately to
 * the left of '(' (or the start of the string) and y is whatever sits
 * immediately to the right of ')' (or the end of the string).
 *
 * t's top-level (depth-1) content is split into a sequence of tokens:
 * atoms (numbers, identifiers, function calls, sub-groups) and operators
 * (+, -, *, /, ^), inserting an implicit '*' token between two adjacent
 * atoms with nothing between them. A unary +/- prefix (at the start of
 * t, or right after another top-level operator) is folded into the atom
 * it precedes rather than emitted as its own operator token.
 *
 * Operator priorities (lower number = lower priority = binds looser):
 *     1 : binary + or -
 *     2 : * or / or implicit multiplication
 *     3 : ^
 *
 *   minPrio       = the minimum priority among t's top-level operators
 *                    (+infinity if none).
 *   firstIsCaret  = true if t's leading top-level term is itself a
 *                    power expression (e.g. t="2^x" or t="2^x+1").
 *   lastIsCaret   = true if t's trailing top-level term ends with '^'
 *                    (e.g. t="2^x" or t="1+2^x").
 *   lastIsDivision = true if t's trailing top-level operator is '/'.
 *
 * General requirements: x and y must not be '^'; '(' must not be a
 * function call.
 *
 * Left side (x):
 *   - x absent (start of string): always fine.
 *   - t begins with a unary +/- sign: NEVER strip (would put that sign
 *     directly next to x, e.g. "3(-x)"->"3-x" reads as subtraction, or
 *     "1-(-x)"->"1--x" is confusing) — purely a readability rule, even
 *     though every case here is mathematically unambiguous.
 *   - x is +,-,*,/: strip only if x's priority < minPrio.
 *   - x is ')' or ']' (close of a PRECEDING bracket group): strip only
 *     if 2 <= minPrio (the two groups stay visually separated by that
 *     bracket either way, so no caret/division edge concern).
 *   - x is a bare digit/letter (implicit multiplication): strip only if
 *     2 <= minPrio AND t's leading term isn't a caret-expression AND x
 *     itself isn't the tail of a caret- or division-ending term in the
 *     surrounding text (e.g. "x^2(6)" and "x/y(z/w)" both stay) AND,
 *     if x is a digit, t doesn't also begin with a digit (two bare
 *     numbers must never visually fuse, e.g. "3(2(x+1))" stays).
 *
 * Right side (y): symmetric, using lastIsCaret/lastIsDivision of t and
 * checking what follows y in the surrounding text on the digit-fusion
 * case (t's trailing character vs y's leading character).
 *
 * Both the left and right conditions must hold to strip a given pair.
 */

function strip_parens($expr) {
    $changed = true;
    while ($changed) {
        $changed = false;
        $len = strlen($expr);

        for ($i = 0; $i < $len; $i++) {
            if ($expr[$i] !== '(' && $expr[$i] !== '[') continue;

            $j = findMatchingClose($expr, $i);
            if ($j === -1) continue; // unmatched

            $inner = substr($expr, $i + 1, $j - $i - 1);
            if ($inner === '') continue; // empty (), leave alone

            // '(' must not be a function call.
            if (precededByFunctionName($expr, $i)) continue;
            // x must not be '^'.
            if ($i > 0 && $expr[$i - 1] === '^') continue;
            // y must not be '^'.
            if ($j + 1 < $len && $expr[$j + 1] === '^') continue;

            $tokens = tokenizeTopLevel($inner);
            if ($tokens === null) continue; // shouldn't happen, but be safe

            $info = analyzeTokens($tokens);
            $minPrio       = $info['minPrio'];
            $firstIsCaret  = $info['firstIsCaret'];
            $lastIsCaret   = $info['lastIsCaret'];
            $lastIsDivision = $info['lastIsDivision'];

            // ---- left side check ----
            // If t begins with a unary +/- sign, never strip on the
            // left when there's a preceding character, since the sign
            // would then sit directly adjacent to x (e.g. "3(-x)" ->
            // "3-x" looks like subtraction, not multiplication by a
            // negative; "1-(-x)" -> "1--x" is visually confusing).
            // Stripping is only safe here if x is the very start of
            // the string (nothing precedes the sign).
            $tStartsWithUnarySign = ($inner[0] === '+' || $inner[0] === '-');

            $xOk = true;
            if ($i > 0) {
                if ($tStartsWithUnarySign) {
                    $xOk = false;
                } else {
                $xc = $expr[$i - 1];
                if ($xc === '+' || $xc === '-') {
                    $xOk = (1 < $minPrio);
                } elseif ($xc === '*' || $xc === '/') {
                    $xOk = (2 < $minPrio);
                } elseif (ctype_alnum($xc)) {
                    // x is a literal digit/letter -> implicit
                    // multiplication directly against t's leading
                    // content. Blocked if:
                    //   - t contains anything looser than
                    //     multiplication (a top-level +/-);
                    //   - t's leading term is itself a caret-expression
                    //     (e.g. 6(2^x) must not become 62^x);
                    //   - x ITSELF is the tail end of a caret-expression
                    //     in the surrounding text (e.g. x^2(6) must not
                    //     become x^26);
                    //   - x ITSELF is the tail end of a
                    //     division-ending term (e.g. x/y(z/w) must not
                    //     become x/yz/w - the y must stay glued to its
                    //     own (z/w), not merge with it);
                    //   - x is a digit AND t's first character is also
                    //     a digit, since two adjacent bare numbers
                    //     would visually fuse into one number (e.g.
                    //     3(2(x+1)) must not become 32(x+1)).
                    $precInfo = analyzePrecedingTerm($expr, $i);
                    $xOk = (2 <= $minPrio) && !$firstIsCaret &&
                           !$precInfo['isCaret'] && !$precInfo['isDivision'];
                    if ($xOk && ctype_digit($xc) && ctype_digit($inner[0])) {
                        $xOk = false;
                    }
                } elseif ($xc === ')' || $xc === ']') {
                    // x is the close of a PRECEDING bracketed group
                    // (e.g. the ')' in "(x^2)(6)"). The two groups
                    // remain visually separated by that bracket, so no
                    // caret-edge restriction is needed here - only the
                    // ordinary minPrio compatibility check applies.
                    $xOk = (2 <= $minPrio);
                }
                // any other x (shouldn't occur) leaves $xOk = true
                }
            }

            // ---- right side check ----
            $yOk = true;
            if ($j + 1 < $len) {
                $yc = $expr[$j + 1];
                if ($yc === '+' || $yc === '-') {
                    $yOk = (1 < $minPrio);
                } elseif ($yc === '*' || $yc === '/') {
                    $yOk = (2 < $minPrio);
                } elseif (ctype_alnum($yc)) {
                    // y is a literal digit/letter -> implicit
                    // multiplication directly against t's trailing
                    // content. Blocked if t's trailing term is a
                    // caret-expression (e.g. (x^2)3 must not become
                    // x^23), or if t ends in a division (e.g. (x/y)3
                    // must not become x/y3 - purely stylistic, to
                    // avoid the 3 looking like it joined the
                    // denominator), or if t contains anything looser
                    // than multiplication, or if y is a digit AND t's
                    // last character is also a digit (would visually
                    // fuse into one number).
                    $yOk = (2 <= $minPrio) && !$lastIsCaret && !$lastIsDivision;
                    if ($yOk && ctype_digit($yc) && ctype_digit($inner[strlen($inner) - 1])) {
                        $yOk = false;
                    }
                } elseif ($yc === '(' || $yc === '[') {
                    // y is the open of a FOLLOWING bracketed group
                    // (e.g. the '(' in "(x^2)(6)"). The two groups
                    // remain visually separated by that bracket, so no
                    // caret/division-edge restriction is needed here -
                    // only the ordinary minPrio compatibility check
                    // applies.
                    $yOk = (2 <= $minPrio);
                }
            }

            if ($xOk && $yOk) {
                $expr = substr($expr, 0, $i) . $inner . substr($expr, $j + 1);
                $changed = true;
                break; // restart scan from the beginning (left to right)
            }
        }
    }

    return $expr;
}

/**
 * Given a position $pos in $expr (the index of a bare digit/letter
 * character, or one past the end of a term), determines properties of
 * the top-level term ending at $pos (i.e. the text immediately to the
 * left, read backward until a lower-or-equal-priority boundary, an
 * enclosing bracket, or the start of the string):
 *
 *   - 'isCaret': true if that term is itself a caret-expression, e.g.
 *     for "x^2(6)" at $pos = the index of '(', the term ending there
 *     is "x^2", which IS a caret-expression.
 *   - 'isDivision': true if that term's last top-level operator is
 *     '/', e.g. for "x/y(z/w)" at $pos = the index of the second '(',
 *     the term ending there is "x/y", which DOES end in a division.
 *
 * This mirrors lastIsCaret / lastIsDivision from analyzeTokens(), but
 * operates directly on raw text (skipping over any balanced bracket
 * groups encountered) rather than on a pre-tokenized t, so it can look
 * arbitrarily far back through plain (non-parenthesized) text.
 */
function analyzePrecedingTerm($expr, $pos) {
    $k = $pos - 1;
    $sawCaret = false;

    while ($k >= 0) {
        $c = $expr[$k];

        if ($c === ')' || $c === ']') {
            // skip back over the balanced group (could be a function
            // call or a bare sub-group; either way it's a single atom
            // for this purpose).
            $openIdx = -1;
            $depth = 0;
            for ($m = $k; $m >= 0; $m--) {
                if ($expr[$m] === ')' || $expr[$m] === ']') {
                    $depth++;
                } elseif ($expr[$m] === '(' || $expr[$m] === '[') {
                    $depth--;
                    if ($depth === 0) { $openIdx = $m; break; }
                }
            }
            if ($openIdx === -1) break; // malformed, bail
            $k = $openIdx - 1;
            continue;
        }

        if ($c === '^') {
            $sawCaret = true;
            $k--;
            continue;
        }

        if ($c === '+' || $c === '-') {
            // Could be unary or binary. Look at what's before it; if
            // nothing, or another operator/open-bracket, it's unary
            // and part of the same term (does not break the scan, and
            // does not by itself confer caret-ness). If it's a
            // genuine binary +/- (preceded by an atom/close-bracket),
            // it ends the current top-level term here.
            $beforeSign = $k - 1;
            $isUnary = ($beforeSign < 0);
            if (!$isUnary) {
                $pc = $expr[$beforeSign];
                if ($pc === '+' || $pc === '-' || $pc === '*' || $pc === '/' ||
                    $pc === '^' || $pc === '(' || $pc === '[') {
                    $isUnary = true;
                }
            }
            if ($isUnary) {
                $k--;
                continue;
            }
            return array('isCaret' => $sawCaret, 'isDivision' => false);
        }

        if ($c === '/') {
            return array('isCaret' => $sawCaret, 'isDivision' => true);
        }

        if ($c === '*') {
            return array('isCaret' => $sawCaret, 'isDivision' => false);
        }

        if (ctype_alnum($c)) {
            $k--;
            continue;
        }

        // Some other character (e.g. '=', '<', start of string context
        // boundary) - treat as a term boundary.
        return array('isCaret' => $sawCaret, 'isDivision' => false);
    }

    return array('isCaret' => $sawCaret, 'isDivision' => false);
}

/**
 * Given the index $i of an open '(' or '[' in $expr, returns the index
 * of its matching close, or -1 if unmatched.
 */
function findMatchingClose($expr, $i) {
    $len = strlen($expr);
    $depth = 0;
    for ($k = $i; $k < $len; $k++) {
        $c = $expr[$k];
        if ($c === '(' || $c === '[') {
            $depth++;
        } elseif ($c === ')' || $c === ']') {
            $depth--;
            if ($depth === 0) return $k;
        }
    }
    return -1;
}

/**
 * Returns true if the character(s) immediately before index $i in
 * $expr spell out one of the recognized function names as a full
 * token (not part of a longer identifier run).
 */
function precededByFunctionName($expr, $i) {
    $fNames = array('sqrt', 'ln', 'log', 'cos', 'sin', 'tan', 'cot', 'sec', 'csc');
    foreach ($fNames as $fn) {
        $flen = strlen($fn);
        if ($i >= $flen && substr($expr, $i - $flen, $flen) === $fn) {
            $before = $i - $flen - 1;
            if ($before < 0 || !ctype_alpha($expr[$before])) {
                return true;
            }
        }
    }
    return false;
}

/**
 * Tokenizes a top-level expression string (the contents of a paren
 * pair, with the outer parens already removed) into a flat list of
 * tokens, where each token is one of:
 *   array('type' => 'op',   'val' => '+'|'-'|'*'|'/'|'^')
 *   array('type' => 'atom', 'val' => <substring>, 'isCaret' => bool)
 *
 * Sub-groups (...)/[...] and function calls name(...) are each
 * collapsed into a single atom token. Implicit multiplication between
 * two adjacent atoms (no operator between them) is inserted as an
 * explicit 'op' token with val '*'. A unary +/- prefix on an atom is
 * folded into that atom (not emitted as a separate 'op' token); the
 * resulting atom's 'isCaret' is false (a unary-negated power, e.g.
 * "-2^x", is NOT itself a caret-atom for edge purposes, since the
 * outer unary minus is the actual top-level operation... but per this
 * grammar's precedence, unary minus binds looser than ^, so "-2^x" is
 * -(2^x); we still mark such an atom isCaret=false conservatively,
 * since the visible leading symbol is '-', not a digit/letter, so the
 * "bare neighbor" issue does not arise on that edge anyway).
 *
 * Returns the token list, or null on malformed input (mismatched
 * parens) — callers treat null defensively, though this should not
 * happen given $inner came from a validated paren pair.
 */
function tokenizeTopLevel($s) {
    $tokens = array();
    $len = strlen($s);
    $i = 0;
    $expectOperand = true; // true at start, and right after a binary operator

    while ($i < $len) {
        $c = $s[$i];

        if ($expectOperand && ($c === '+' || $c === '-')) {
            // unary sign: fold into the atom that follows
            $signStart = $i;
            $i++;
            // allow multiple unary signs e.g. --x (not expected in this
            // grammar, but handle gracefully)
            while ($i < $len && ($s[$i] === '+' || $s[$i] === '-')) $i++;
            list($atomEnd, $isCaret) = readAtom($s, $i);
            if ($atomEnd === $i) {
                // nothing follows the sign(s); malformed, bail gracefully
                return null;
            }
            $tokens[] = array('type' => 'atom', 'val' => substr($s, $signStart, $atomEnd - $signStart), 'isCaret' => false);
            $i = $atomEnd;
            $expectOperand = false;
            continue;
        }

        if (!$expectOperand && ($c === '+' || $c === '-' || $c === '*' || $c === '/' || $c === '^')) {
            $tokens[] = array('type' => 'op', 'val' => $c);
            $i++;
            $expectOperand = true;
            continue;
        }

        if ($expectOperand) {
            list($atomEnd, $isCaret) = readAtom($s, $i);
            if ($atomEnd === $i) {
                // can't make progress; malformed
                return null;
            }
            $tokens[] = array('type' => 'atom', 'val' => substr($s, $i, $atomEnd - $i), 'isCaret' => $isCaret);
            $i = $atomEnd;
            $expectOperand = false;
            continue;
        }

        // !$expectOperand and current char is not an operator ->
        // implicit multiplication: insert synthetic '*' and re-process
        // this same character as the start of a new atom.
        if (!$expectOperand) {
            $tokens[] = array('type' => 'op', 'val' => '*');
            $expectOperand = true;
            continue;
        }

        // Shouldn't reach here.
        $i++;
    }

    return $tokens;
}

/**
 * Reads a single atom (a maximal run of digits, OR a single letter, OR
 * a parenthesized sub-group, OR a function call name(...)/name[...])
 * starting at index $i in $s, possibly followed immediately by a '^'
 * exponent chain (since ^ binds tightest of all and a "primary^expo"
 * forms the atom for the purposes of THIS atom's caret-edge status —
 * but note: a chain like x^2^3 — right associative — is read here as
 * the base 'x' atom only; the caller's main tokenizer loop will see
 * the following '^' as a separate top-level operator token, which is
 * exactly what we want, since ^ is the highest top-level priority and
 * must be visible to the priority/edge analysis).
 *
 * Returns array($endIndex, $isCaretAtom) where $isCaretAtom indicates
 * this atom is immediately the base of a top-level '^' (i.e. caller
 * should treat the resulting (atom, ^, exponent) trio as a single
 * "caret term" for edge-detection purposes). This function itself
 * only reads the base/function-call/sub-group; the caller's analysis
 * pass stitches consecutive atom/^/atom token triples together when
 * computing first/last "caret-ness".
 */
function readAtom($s, $i) {
    $len = strlen($s);
    if ($i >= $len) return array($i, false);

    $c = $s[$i];

    // Parenthesized or bracketed sub-group (could be a function call's
    // argument list too; if it's a bare group, gather the whole thing).
    if ($c === '(' || $c === '[') {
        $close = findMatchingClose($s, $i);
        if ($close === -1) return array($i, false); // malformed
        return array($close + 1, false);
    }

    // digits: a number
    if (ctype_digit($c)) {
        $j = $i;
        while ($j < $len && ctype_digit($s[$j])) $j++;
        return array($j, false);
    }

    // letters: could be a function name (followed by '(' or '['), a
    // multi-letter constant/variable name (pi, e, alpha, beta, theta),
    // or a single-letter variable.
    if (ctype_alpha($c)) {
        $fNames = array('sqrt', 'ln', 'log', 'cos', 'sin', 'tan', 'cot', 'sec', 'csc');
        foreach ($fNames as $fn) {
            $flen = strlen($fn);
            if (substr($s, $i, $flen) === $fn) {
                $afterName = $i + $flen;
                if ($afterName < $len && ($s[$afterName] === '(' || $s[$afterName] === '[')) {
                    $close = findMatchingClose($s, $afterName);
                    if ($close !== -1) return array($close + 1, false);
                }
                // function name not followed by '(' - fall through and
                // treat as a plain identifier run instead.
            }
        }
        $multiLetterNames = array('alpha', 'beta', 'theta', 'pi');
        foreach ($multiLetterNames as $nm) {
            $nlen = strlen($nm);
            if (substr($s, $i, $nlen) === $nm) {
                $after = $i + $nlen;
                if ($after >= $len || !ctype_alpha($s[$after])) {
                    return array($after, false);
                }
            }
        }
        // single-letter variable
        return array($i + 1, false);
    }

    // unrecognized character; consume one char defensively
    return array($i + 1, false);
}

/**
 * Given the flat token list from tokenizeTopLevel, computes:
 *   - minPrio: minimum priority among 'op' tokens (PHP_INT_MAX if none)
 *   - firstIsCaret: whether the leading top-level term (the run of
 *     tokens up to, but not including, the first 'op' token whose
 *     priority is <= the priority of an adjacent '^', i.e. simply: is
 *     there a top-level '^' appearing before any '+','-','*','/'?)
 *   - lastIsCaret: whether the trailing top-level term (after the last
 *     '+','-','*','/' operator, if any) ends with a '^' application,
 *     i.e. is there a top-level '^' appearing after the last
 *     '+','-','*','/'?
 */
function analyzeTokens($tokens) {
    $minPrio = PHP_INT_MAX;
    $n = count($tokens);

    $priorityOf = function ($opVal) {
        if ($opVal === '+' || $opVal === '-') return 1;
        if ($opVal === '*' || $opVal === '/') return 2;
        if ($opVal === '^') return 3;
        return PHP_INT_MAX;
    };

    foreach ($tokens as $tok) {
        if ($tok['type'] === 'op') {
            $p = $priorityOf($tok['val']);
            if ($p < $minPrio) $minPrio = $p;
        }
    }

    // firstIsCaret: scan tokens from the start; if we hit a top-level
    // '^' before any '+','-','*','/' op token, the leading term is a
    // caret-expression.
    $firstIsCaret = false;
    for ($k = 0; $k < $n; $k++) {
        $tok = $tokens[$k];
        if ($tok['type'] === 'op') {
            if ($tok['val'] === '^') {
                $firstIsCaret = true;
            }
            break; // first operator encountered settles it either way
        }
    }

    // lastIsCaret: scan tokens from the end; if we hit a top-level '^'
    // before any '+','-','*','/' op token (reading backwards), the
    // trailing term is a caret-expression.
    $lastIsCaret = false;
    $lastIsDivision = false;
    for ($k = $n - 1; $k >= 0; $k--) {
        $tok = $tokens[$k];
        if ($tok['type'] === 'op') {
            if ($tok['val'] === '^') {
                $lastIsCaret = true;
            }
            if ($tok['val'] === '/') {
                $lastIsDivision = true;
            }
            break;
        }
    }

    return array(
        'minPrio'        => $minPrio,
        'firstIsCaret'   => $firstIsCaret,
        'lastIsCaret'    => $lastIsCaret,
        'lastIsDivision' => $lastIsDivision,
    );
}

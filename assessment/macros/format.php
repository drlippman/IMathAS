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
    'formatcomplex'
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
<?php

// string handling macros

array_push(
    $GLOBALS['allowedmacros'],
    'stringlen',
    'stringpos',
    'stringclean',
    'stringappend',
    'stringprepend'
);

function stringlen($str) {
    return strlen($str);
}
function stringpos($n, $h) {
    if (!is_scalar($h) || !is_scalar($n)) {
        echo "inputs to stringpos must be strings";
        return -1;
    }
    $p = strpos($h, $n);
    if ($p === false) {
        $p = -1;
    }
    return $p;
}
function stringclean($str, $mode = 0) {
    switch ($mode) {
        case 0:
            return trim($str);
            break;
        case 1:
            return preg_replace('/\s/', '', $str);
            break;
        case 2:
            return preg_replace('/\W/', '', $str);
            break;
    }
}


function stringappend($v, $s) {
    if (is_array($v)) {
        foreach ($v as $k => $y) {
            $v[$k] = $v[$k] . $s;
        }
    } else {
        $v = $v . $s;
    }
    return $v;
}
function stringprepend($v, $s) {
    if (is_array($v)) {
        foreach ($v as $k => $y) {
            $v[$k] = $s . $v[$k];
        }
    } else {
        $v = $s . $v;
    }
    return $v;
}

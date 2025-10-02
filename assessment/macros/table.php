<?php

// table generation macros

array_push(
    $GLOBALS['allowedmacros'],
    'showarrays',
    'showrecttable',
    'horizshowarrays',
    'showdataarray'
);

function showarrays() {
    $alist = func_get_args();
    $format = "default";
    $caption = "";
    $tablealign = '';
    if (count($alist) < 2) {
        return false;
    }
    if (count($alist) % 2 == 1) {
        if (is_array($alist[count($alist) - 1])) {
            $opts = $alist[count($alist) - 1];
            if (isset($opts['align'])) {
                $format = $opts['align'];
            }
            if (isset($opts['caption'])) {
                $caption = $opts['caption'];
            }
            if (isset($opts['tablealign'])) {
                $tablealign = $opts['tablealign'];
            }
        } else if (is_string($alist[count($alist) - 1])) {
            $format = $alist[count($alist) - 1];
        }
    }
    $ncol = floor(count($alist) / 2);
    if ($format !== 'default' && strlen($format) < $ncol) {
        $format = str_repeat($format[0], $ncol);
    }
    if (count($alist) < 4 && is_array($alist[0]) && is_array($alist[1]) && is_array($alist[1][0])) {
        // alt input syntax of showarrays(array of headers, array of data arrays)
        for ($i = 0; $i < count($alist[0]); $i++) {
            $newalist[] = $alist[0][$i];
            $newalist[] = $alist[1][$i];
        }
        $alist = $newalist;
    }
    $out = '<table class=stats';
    if ($tablealign == 'center') {
        $out .= ' style="margin:0 auto;"';
    }
    $out .= '>';
    if ($caption != '') {
        $out .= '<caption>' . Sanitize::encodeStringForDisplay($caption) . '</caption>';
    }
    $hashdr = false;
    $maxlength = 0;
    for ($i = 0; $i < $ncol; $i++) {
        if (!is_scalar($alist[2 * $i])) {
            echo 'showarrays: column headers should be strings';
            $alist[2 * $i] = '';
        }
        if ($alist[2 * $i] != '') {
            $hashdr = true;
        }
        if (!is_array($alist[2 * $i + 1])) {
            $alist[2 * $i + 1] = listtoarray($alist[2 * $i + 1]);
        }
        if (count($alist[2 * $i + 1]) > $maxlength) {
            $maxlength = count($alist[2 * $i + 1]);
        }
    }
    if ($hashdr) {
        $out .= '<thead><tr>';
        for ($i = 0; $i < floor(count($alist) / 2); $i++) {
            $out .= "<th scope=\"col\">{$alist[2 *$i]}</th>";
        }
        $out .= "</tr></thead>";
    }
    $out .= "<tbody>";
    for ($j = 0; $j < $maxlength; $j++) {
        $out .= "<tr>";
        for ($i = 0; $i < floor(count($alist) / 2); $i++) {
            if ($format == 'default' || !isset($format[$i])) {
                $out .= '<td>';
            } else if ($format[$i] == 'c' || $format[$i] == 'C') {
                $out .= '<td class="c">';
            } else if ($format[$i] == 'r' || $format[$i] == 'R') {
                $out .= '<td class="r">';
            } else if ($format[$i] == 'l' || $format[$i] == 'L') {
                $out .= '<td class="l">';
            } else {
                $out .= '<td>';
            }
            if (isset($alist[2 * $i + 1][$j])) {
                $out .= $alist[2 * $i + 1][$j];
            }

            $out .= "</td>";
        }
        $out .= "</tr>";
    }
    $out .= "</tbody></table>\n";
    return $out;
}

function showrecttable($m, $clabel, $rlabel, $format = '') {
    if (count($m) != count($rlabel) || count($m[0]) != count($clabel)) {
        return 'Error - label counts don\'t match dimensions of the data';
    }
    $out = '<table class=stats><thead><tr><th></th>';
    for ($i = 0; $i < count($clabel); $i++) {
        $out .= "<th scope=\"col\">{$clabel[$i]}</th>";
    }
    $out .= "</tr></thead><tbody>";
    for ($j = 0; $j < count($m); $j++) {
        $out .= "<tr><th scope=\"row\"><b>{$rlabel[$j]}</b></th>";
        for ($i = 0; $i < count($m[$j]); $i++) {
            if ($format == 'c' || $format == 'C') {
                $out .= '<td class="c">';
            } else if ($format == 'r' || $format == 'R') {
                $out .= '<td class="r">';
            } else if ($format == 'l' || $format == 'L') {
                $out .= '<td class="l">';
            } else {
                $out .= '<td>';
            }
            $out .= $m[$j][$i] . '</td>';
        }
        $out .= "</tr>";
    }
    $out .= "</tbody></table>\n";
    return $out;
}

function horizshowarrays() {
    $alist = func_get_args();
    if (count($alist) < 2) {
        return false;
    }

    $maxlength = 0;
    for ($i = 0; $i < count($alist) / 2; $i++) {
        if (!isset($alist[2 * $i + 1])) {
            $alist[2 * $i + 1] = [];
        } else if (!is_array($alist[2 * $i + 1])) {
            $alist[2 * $i + 1] = listtoarray($alist[2 * $i + 1]);
        }
        if (count($alist[2 * $i + 1]) > $maxlength) {
            $maxlength = count($alist[2 * $i + 1]);
        }
    }
    $out = '<table class=stats>';
    for ($i = 0; $i < count($alist) / 2; $i++) {
        $out .= "<tr><th scope=\"row\"><b>{$alist[2 *$i]}</b></th>";
        if (count($alist[2 * $i + 1]) > 0) {
            $out .= "<td>" . implode("</td><td>", $alist[2 * $i + 1]) . "</td>";
        }
        if (count($alist[2 * $i + 1]) < $maxlength) {
            $out .= str_repeat('<td></td>', $maxlength - count($alist[2 * $i + 1]));
        }
        $out .= "</tr>\n";
    }
    $out .= "</tbody></table>\n";
    return $out;
}

function showdataarray($a, $n = 1, $opts = null) {
    if (!is_array($a)) {
        return '';
    }
    $n = floor($n);
    $a = array_values($a);
    $format = 'table';
    $align = "default";
    $caption = "";
    $tablealign = '';
    if (is_array($opts)) {
        if (isset($opts['format'])) {
            $format = $opts['format'];
        }
        if (isset($opts['align'])) {
            $align = strtolower($opts['align']);
        }
        if (isset($opts['caption'])) {
            $caption = $opts['caption'];
        }
        if (isset($opts['tablealign'])) {
            $tablealign = $opts['tablealign'];
        }
    } else if (is_string($opts)) {
        if ($opts == 'pre') {
            $format = 'pre';
        } else {
            $align = strtolower($opts);
        }
    }

    if ($format == 'pre') {
        $maxwidth = 1;
        $cnt = 0;
        foreach ($a as $v) {
            if (strlen($v) > $maxwidth) {
                $maxwidth = strlen($v);
            }
        }
        $out = '<pre>';
        while ($cnt < count($a)) {
            for ($i = 0; $i < $n; $i++) {
                $out .= sprintf("%{$maxwidth}s ", $a[$cnt]);
                $cnt++;
            }
            $out .= "\n";
        }
        $out .= '</pre>';
    } else {
        $cellclass = '';
        if ($align == 'c' || $align == 'r' || $align == 'l') {
            $cellclass = 'class=' . $align;
        }
        $out = '<table class=stats';
        if ($tablealign == 'center') {
            $out .= ' style="margin:0 auto;"';
        }
        $out .= '>';
        if ($caption != '') {
            $out .= '<caption>' . Sanitize::encodeStringForDisplay($caption) . '</caption>';
        }
        $out .= '<tbody>';
        $cnt = 0;
        while ($cnt < count($a)) {
            $out .= '<tr>';
            for ($i = 0; $i < $n; $i++) {
                if (isset($a[$cnt])) {
                    $out .= '<td ' . $cellclass . '>' . $a[$cnt] . '</td>';
                } else {
                    $out .= '<td></td>';
                }
                $cnt++;
            }
            $out .= '</tr>';
        }
        $out .= '</tbody></table>';
    }
    return $out;
}

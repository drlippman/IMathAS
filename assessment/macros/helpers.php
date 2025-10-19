<?php

// macros for parsing question type data,
// checking formats

array_push(
    $GLOBALS['allowedmacros'],
    'makenumberrequiretimes',
    'stringtopolyterms',
    'sorttwopointdata',
    'gettwopointlinedata',
    'gettwopointdata',
    'gettwopointformulas',
    'getineqdata',
    'getdotsdata',
    'getopendotsdata',
    'getlinesdata',
    'getsnapwidthheight',
    'getsigfigs',
    'checksigfigs',
    'getntupleparts',
    'stuansready',
    'getstuans',
    'checkreqtimes',
    'checkanswerformat'
);

function makenumberrequiretimes($arr) {
    if (!is_array($arr)) {
        $arr = listtoarray($arr);
    }
    if (count($arr) == 0) {
        return "";
    }
    foreach ($arr as $k => $num) {
        if (!is_numeric($num)) {
            echo 'inputs to makenumberrequirestimes must be numeric';
            continue;
        }
        $arr[$k] = abs($num);
    }
    $uniq = array_unique($arr);
    $out = array();
    foreach ($uniq as $num) {
        $nummatch = count(array_keys($arr, $num));
        $out[] = "#$num,=$nummatch";
    }
    return implode(',', $out);
}

function stringtopolyterms($str) {
    $out = array();
    $str = str_replace(' ', '', $str);
    if ($str == '') {
        return [];
    }
    $arr = preg_split('/(?<!\^)([-+])/', $str, -1, PREG_SPLIT_DELIM_CAPTURE);
    if ($arr[0] == '') {  //string started -3x or something; skip 0 index
        $out[] = (($arr[1] == '-') ? '-' : '') . $arr[2];
        $start = 3;
    } else {  //string started 3x or something
        $out[] = $arr[0];
        $start = 1;
    }
    for ($i = $start; $i < count($arr); $i += 2) {
        $out[] = (($arr[$i] == '-') ? '-' : '') . $arr[$i + 1];
    }
    return $out;
}

function sorttwopointdata($data, $type = '') {
    if ($type == 'line' || $type == 'lineseg' || $type == 'cos' || $type == 'exp' || $type == 'log') {
        foreach ($data as $k => $v) {
            if ($v[2] < $v[0]) {
                $data[$k] = [$v[2], $v[3], $v[0], $v[1]];
            }
        }
    }
    usort($data, function ($a, $b) {
        if ($a[0] == $b[0]) {
            return ($a[1] < $b[1]) ? -1 : 1;
        }
        return ($a[0] < $b[0]) ? -1 : 1;
    });
    return $data;
}

function gettwopointlinedata($str, $xmin = null, $xmax = null, $ymin = null, $ymax = null, $w = null, $h = null) {
    return gettwopointdata($str, 'line', $xmin, $xmax, $ymin, $ymax, $w, $h);
}
function gettwopointdata($str, $type, $xmin = null, $xmax = null, $ymin = null, $ymax = null, $w = null, $h = null) {
    if (trim($str) == '') {
        return [];
    }
    if (is_string($xmin) && strpos($xmin, ',') !== false) {
        list($xmin, $xmax, $ymin, $ymax, $w, $h) = parsedrawgrid($xmin, $xmax);
    } else {
        if ($xmin === null) {
            $xmin = -5;
        }
        if ($xmax === null) {
            $xmax = 5;
        }
        if ($ymin === null) {
            $ymin = -5;
        }
        if ($ymax === null) {
            $ymax = 5;
        }
        if ($w === null) {
            $w = 300;
        }
        if ($h === null) {
            $h = 300;
        }
    }
    if ($type == 'line') {
        $code = 5;
    } else if ($type == 'lineseg') {
        $code = 5.3;
    } else if ($type == 'ray') {
        $code = 5.2;
    } else if ($type == 'parab') {
        $code = 6;
    } else if ($type == 'halfparab') {
        $code = 6.2;
    } else if ($type == 'horizparab') {
        $code = 6.1;
    } else if ($type == '3pointparab') {
        $code = 6.7;
    } else if ($type == 'cubic') {
        $code = 6.3;
    } else if ($type == 'sqrt') {
        $code = 6.5;
    } else if ($type == 'cuberoot') {
        $code = 6.6;
    } else if ($type == 'abs') {
        $code = 8;
    } else if ($type == 'rational') {
        $code = 8.2;
    } else if ($type == 'exp') {
        $code = 8.3;
    } else if ($type == 'log') {
        $code = 8.4;
    } else if ($type == 'genexp') {
        $code = 8.5;
    } else if ($type == 'genlog') {
        $code = 8.6;
    } else if ($type == 'circle' || $type == 'circlerad') {
        $code = 7;
    } else if ($type == 'ellipse' || $type == 'ellipserad') {
        $code = 7.2;
    } else if ($type == 'sin') {
        $code = 9.1;
    } else if ($type == 'cos') {
        $code = 9;
    } else if ($type == 'vector') {
        $code = 5.4;
    } else {
        $code = -1;
    }
    $imgborder = 5;
    $pixelsperx = ($w - 2 * $imgborder) / ($xmax - $xmin);
    $pixelspery = ($h - 2 * $imgborder) / ($ymax - $ymin);
    $outpts = array();
    list($lines, $dots, $odots, $tplines, $ineqlines) = explode(';;', $str);
    if ($tplines == '') {
        return array();
    }
    $tplines = explode('),(', substr($tplines, 1, strlen($tplines) - 2));
    foreach ($tplines as $k => $val) {
        $pts = explode(',', $val);
        if ($pts[0] == $code) {
            $pts[1] = ($pts[1] - $imgborder) / $pixelsperx + $xmin;
            $pts[3] = ($pts[3] - $imgborder) / $pixelsperx + $xmin;
            $pts[2] = ($h - $pts[2] - $imgborder) / $pixelspery + $ymin;
            $pts[4] = ($h - $pts[4] - $imgborder) / $pixelspery + $ymin;
            $outpt = array($pts[1], $pts[2], $pts[3], $pts[4]);
            if ($type == 'ellipserad') {
                $pts[3] = abs($pts[3] - $pts[1]);
                $pts[4] = abs($pts[4] - $pts[2]);
                $outpt = array($pts[1], $pts[2], $pts[3], $pts[4]);
            } else if ($type == 'circlerad') {
                $pts[3] = sqrt(pow($pts[3] - $pts[1], 2) + pow($pts[4] - $pts[2], 2));
                $outpt = array($pts[1], $pts[2], $pts[3]);
            } else if ($type == 'genexp' || $type == 'genlog') {
                $pts[5] = ($pts[5] - $imgborder) / $pixelsperx + $xmin;
                $pts[6] = ($h - $pts[6] - $imgborder) / $pixelspery + $ymin;
                // Last value is the asymptote: y val for genexp, x val for genlog
                $outpt = ($type == 'genexp') ? array($pts[3], $pts[4], $pts[5], $pts[6], $pts[2]) : array($pts[3], $pts[4], $pts[5], $pts[6], $pts[1]);
            } else if ($type == '3pointparab') {
                $pts[5] = ($pts[5] - $imgborder) / $pixelsperx + $xmin;
                $pts[6] = ($h - $pts[6] - $imgborder) / $pixelspery + $ymin;
                $outpt = array($pts[1], $pts[2], $pts[3], $pts[4], $pts[5], $pts[6]);
            }
            $outpts[] = $outpt;
        }
    }
    return $outpts;
}

function gettwopointformulas($str, $type, $xmin = null, $xmax = null, $ymin = null, $ymax = null, $w = null, $h = null) {
    $args = func_get_args();
    $eqnvars = [];
    $showequation = false;
    foreach ($args as $key => $arg) {
        if (strpos($arg, 'showequation') !== false) {
            $showequation = true;
            if (!is_array($args[$key])) {
                $eqnvars = explode(",", $args[$key]);
            }
        }
    }
    $x = (!empty($eqnvars[1])) ? $eqnvars[1] : 'x';
    $y = (!empty($eqnvars[2])) ? $eqnvars[2] : 'y';
    $pts = gettwopointdata($str, $type, $xmin, $xmax, $ymin, $ymax, $w, $h);
    $outexps = [];
    $outeqs = [];
    if (!empty($pts)) {
        if ($type == 'line' || $type == 'lineseg' || $type == 'ray') {
            foreach ($pts as $key => $pt) {
                if (abs($pt[0] - $pt[2]) > 1E-12) {
                    $slope = ($pt[3] - $pt[1]) / ($pt[2] - $pt[0]);
                    $int = $pt[1] - $slope * $pt[0];
                    $outexps[] = makexxpretty("$slope $x + $int");
                    $outeqs[] = makexxpretty("$y = $slope $x + $int");
                }
            }
        } else if ($type == 'parab' || $type == 'halfparab') {
            foreach ($pts as $key => $pt) {
                if (abs($pt[0] - $pt[2]) > 1E-12) {
                    $k = ($pt[3] - $pt[1]) / pow($pt[2] - $pt[0], 2);
                    $coef1 = -2 * $pt[0] * $k;
                    $coef2 = pow($pt[0], 2) * $k + $pt[1];
                    $outexps[] = makexxpretty("$k $x^2 + $coef1 $x + $coef2");
                    $outeqs[] = makexxpretty("$y = $k $x^2 + $coef1 $x + $coef2");
                }
            }
        } else if ($type == 'horizparab') {
            foreach ($pts as $key => $pt) {
                if (abs($pt[1] - $pt[3]) > 1E-12) {
                    $k = ($pt[2] - $pt[0]) / pow($pt[3] - $pt[1], 2);
                    $coef1 = -2 * $pt[1] * $k;
                    $coef2 = pow($pt[1], 2) * $k + $pt[0];
                    $outexps[] = makexxpretty("$k $y^2 + $coef1 $y + $coef2");
                    $outeqs[] = makexxpretty("$x = $k $y^2 + $coef1 $y + $coef2");
                }
            }
        } else if ($type == '3pointparab') {
            foreach ($pts as $key => $pt) {
                if (abs($pt[0] - $pt[2]) > 1E-12 && abs($pt[4] - $pt[2]) > 1E-12  && abs($pt[4] - $pt[0]) > 1E-12) {
                    $a = ($pt[0]*($pt[5]-$pt[3]) + $pt[2]*($pt[1]-$pt[5]) + $pt[4]*($pt[3]-$pt[1]))/(($pt[0]-$pt[2])*($pt[0]-$pt[4])*($pt[2]-$pt[4]));
                    $b = ($pt[3]-$pt[1])/($pt[2]-$pt[0]) - $a*($pt[0]+$pt[2]);
                    $c = $pt[1] - $a*$pt[0]*$pt[0] - $b*$pt[0];
                    $outexps[] = makexxpretty("$a $x^2 + $b $x + $c");
                    $outeqs[] = makexxpretty("$y = $a $x^2 + $b $x + $c");
                }
            }
        } else if ($type == 'cubic') {
            foreach ($pts as $key => $pt) {
                if (abs($pt[0] - $pt[2]) > 1E-12) {
                    $k = ($pt[3] - $pt[1]) / pow($pt[2] - $pt[0], 3);
                    $coef1 = -3 * $pt[0] * $k;
                    $coef2 = 3 * pow($pt[0], 2) * $k;
                    $coef3 = -1 * pow($pt[0], 3) * $k + $pt[1];
                    $outexps[] = makexxpretty("$k $x^3 + $coef1 $x^2 + $coef2 $x + $coef3");
                    $outeqs[] = makexxpretty("$y = $k $x^3 + $coef1 $x^2 + $coef2 $x + $coef3");
                }
            }
        } else if ($type == 'sqrt') {
            foreach ($pts as $key => $pt) {
                if (abs($pt[0] - $pt[2]) > 1E-12) {
                    $k = ($pt[3] - $pt[1]) / sqrt(abs($pt[2] - $pt[0]));
                    $j = ($pt[2] > $pt[0]) ? 1 : -1;
                    $outexps[] = makexxpretty("$k sqrt($j $x - $pt[0]) + $pt[1]");
                    $outeqs[] = makexxpretty("$y = $k sqrt($j $x - $pt[0]) + $pt[1]");
                }
            }
        } else if ($type == 'cuberoot') {
            foreach ($pts as $key => $pt) {
                if (abs($pt[0] - $pt[2]) > 1E-12) {
                    $k = abs($pt[3] - $pt[1]) / pow(abs($pt[2] - $pt[0]), 1 / 3);
                    $k *= (($pt[2] - $pt[0]) * ($pt[3] - $pt[1]) < 0) ? -1 : 1;
                    $outexps[] = makexxpretty("$k root(3)($x - $pt[0]) + $pt[1]");
                    $outeqs[] = makexxpretty("$y = $k root(3)($x - $pt[0]) + $pt[1]");
                }
            }
        } else if ($type == 'abs') {
            foreach ($pts as $key => $pt) {
                if (abs($pt[0] - $pt[2]) > 1E-12) {
                    $k = ($pt[2] > $pt[0]) ? ($pt[3] - $pt[1]) / ($pt[2] - $pt[0]) : -1 * ($pt[3] - $pt[1]) / ($pt[2] - $pt[0]);
                    $outexps[] = makexxpretty("$k abs($x - $pt[0]) + $pt[1]");
                    $outeqs[] = makexxpretty("$y = $k abs($x - $pt[0]) + $pt[1]");
                }
            }
        } else if ($type == 'rational') {
            foreach ($pts as $key => $pt) {
                if (abs($pt[0] - $pt[2]) > 1E-12 && abs($pt[1] - $pt[3]) > 1E-12) {
                    $k = ($pt[3] - $pt[1]) * ($pt[2] - $pt[0]);
                    $outexps[] = makexxpretty("$k/($x - $pt[0]) + $pt[1]");
                    $outeqs[] = makexxpretty("$y = $k/($x - $pt[0]) + $pt[1]");
                }
            }
        } else if ($type == 'exp') {
            foreach ($pts as $key => $pt) {
                if ($pt[1] * $pt[3] > 0) {
                    if (abs($pt[0] - $pt[2]) > 1E-12) {
                        $k = pow($pt[3] / $pt[1], 1 / ($pt[2] - $pt[0]));
                        $j = $pt[1] / pow($k, $pt[0]);
                    }
                    $outexps[] = ($pt[3] == $pt[1]) ? $pt[1] : makexxpretty("$j($k)^$x");
                    $outeqs[] = ($pt[3] == $pt[1]) ? "$y = $pt[1]" : makexxpretty("$y = $j($k)^$x");
                } else if ($pt[1] == 0 && $pt[3] == 0) {
                    $outexps[] = 0;
                    $outeqs[] = "$y = 0";
                }
            }
        } else if ($type == 'log') {
            foreach ($pts as $key => $pt) {
                if (abs($pt[0] - $pt[2]) > 1E-12 && abs($pt[1] - $pt[3]) > 1E-12 && $pt[0] * $pt[2] > 0) {
                    $j = abs($pt[0]) / $pt[0];
                    $k = ($pt[3] - $pt[1]) / (log($j * $pt[2]) - log($j * $pt[0]));
                    $b = $pt[1] - $k * log($j * $pt[0]);
                    $outexps[] = makexxpretty("$k ln($j $x) + $b");
                    $outeqs[] = makexxpretty("$y = $k ln($j $x) + $b");
                }
            }
        } else if ($type == 'genexp') {
            foreach ($pts as $key => $pt) {
                if (abs($pt[1] - $pt[3]) < 1E-12) {
                    $outexp = "$pt[1]";
                    $outeq = "$y = $pt[1]";
                } else if (($pt[1] - $pt[4]) * ($pt[3] - $pt[4]) > 0 && abs($pt[0] - $pt[2]) > 1E-12) {
                    if (abs($pt[1] - $pt[3]) > 1E-12) {
                        $b = pow(($pt[3] - $pt[4]) / ($pt[1] - $pt[4]), 1 / ($pt[2] - $pt[0]));
                        $a = ($pt[3] - $pt[1]) / (pow($b, $pt[2]) - pow($b, $pt[0]));
                    } elseif (abs($pt[1] - $pt[3]) <= 1E-12) {
                        $outexp = "$pt[1]";
                        $outeq = "$y = $pt[1]";
                    }
                    $outexp = makexxpretty("$a($b)^$x + $pt[4]");
                    $outeq = "$y=" . $outexp;
                }
                $outexps[] = $outexp;
                $outeqs[] = $outeq;
            }
        } else if ($type == 'genlog') {
            foreach ($pts as $key => $pt) {
                if (($pt[0] - $pt[4]) * ($pt[2] - $pt[4]) > 0 && abs($pt[0] - $pt[2]) > 1E-12 && abs($pt[1] - $pt[3]) > 1E-12) {
                    $a = ($pt[3] - $pt[1]) / (log(abs($pt[2] - $pt[4])) - log(abs($pt[0] - $pt[4])));
                    $b = $pt[1] - $a * log(abs($pt[0] - $pt[4]));
                    $j = ($pt[0] > $pt[4]) ? 1 : -1;
                    $shift = $pt[4] * $j;
                    $outexp = makexxpretty("$a ln($j $x - $shift) + $b");
                    $outeq = "$y=" . $outexp;
                }
                $outexps[] = $outexp;
                $outeqs[] = $outeq;
            }
        } else if ($type == 'circle') {
            foreach ($pts as $key => $pt) {
                if (!(abs($pt[0] - $pt[2]) < 1E-12 && abs($pt[1] - $pt[3]) < 1E-12)) {
                    $rs = pow($pt[2] - $pt[0], 2) + pow($pt[3] - $pt[1], 2);
                    $xexp = ($pt[0] == 0) ? "$x^2" : "($x-$pt[0])^2";
                    $yexp = ($pt[1] == 0) ? "$y^2" : "($y-$pt[1])^2";
                    $outexps[] = makexxpretty("$xexp/$rs + $yexp/$rs");
                    $outeqs[] = makexxpretty("$xexp + $yexp = $rs");
                }
            }
        } else if ($type == 'ellipse') {
            foreach ($pts as $key => $pt) {
                if (abs($pt[0] - $pt[2]) > 1E-12 && abs($pt[1] - $pt[3]) > 1E-12) {
                    $as = pow($pt[2] - $pt[0], 2);
                    $bs = pow($pt[3] - $pt[1], 2);
                    $xexp = ($pt[0] == 0) ? "$x^2/$as" : "($x-$pt[0])^2/$as";
                    $yexp = ($pt[1] == 0) ? "$y^2/$bs" : "($y-$pt[1])^2/$bs";
                    $outexps[] = makexxpretty("$xexp + $yexp");
                    $outeqs[] = makexxpretty("$xexp + $yexp = 1");
                }
            }
        } else if ($type == 'sin') {
            foreach ($pts as $key => $pt) {
                if (abs($pt[0] - $pt[2]) > 1E-12) {
                    if (abs($pt[1] - $pt[3]) > 1E-12) {
                        $a = abs($pt[3] - $pt[1]);
                        $b = M_PI / (2 * abs($pt[2] - $pt[0]));
                        $c = $pt[1];
                        $shift = (($pt[1] - $pt[3]) * ($pt[0] - $pt[2]) > 0) ? fmod($pt[0] * $b, 2 * M_PI) : fmod((2 * $pt[2] - $pt[0]) * $b, 2 * M_PI);
                        $outexp = makexxpretty("$a sin($b $x - $shift) + $c");
                        $outeq = "$y=" . $outexp;
                    } else if (abs($pt[1] - $pt[3]) <= 1E-12) {
                        $outexp = $pt[1];
                        $outeq = "$y = $pt[1]";
                    }
                }
                $outexps[] = $outexp;
                $outeqs[] = $outeq;
            }
        } else if ($type == 'cos') {
            foreach ($pts as $key => $pt) {
                if (abs($pt[0] - $pt[2]) > 1E-12) {
                    if (abs($pt[1] - $pt[3]) > 1E-12) {
                        $a = abs($pt[3] - $pt[1]) / 2;
                        $b = M_PI / abs($pt[2] - $pt[0]);
                        $c = ($pt[1] + $pt[3]) / 2;
                        $shift = ($pt[1] > $pt[3]) ? fmod($pt[0] * $b, 2 * M_PI) : fmod($pt[2] * $b, 2 * M_PI);
                        $outexp = makexxpretty("$a cos($b $x - $shift) + $c");
                        $outeq = "$y=" . $outexp;
                    } else if (abs($pt[1] - $pt[3]) <= 1E-12) {
                        $outexp = $pt[1];
                        $outeq = "$y=$pt[1]";
                    }
                }
            }
            $outexps[] = $outexp;
            $outeqs[] = $outeq;
        }
    }
    if ($showequation === true) {
        return $outeqs;
    } else {
        return $outexps;
    }
}

function getineqdata($str, $type = 'linear', $xmin = null, $xmax = null, $ymin = null, $ymax = null, $w = null, $h = null) {
    if (is_string($xmin) && strpos($xmin, ',') !== false) {
        list($xmin, $xmax, $ymin, $ymax, $w, $h) = parsedrawgrid($xmin, $xmax);
    }
    if (trim($str) == '' || $xmax == $xmin || $ymax == $ymin) {
        return [];
    } // invalid
    $imgborder = 5;
    $pixelsperx = ($w - 2 * $imgborder) / ($xmax - $xmin);
    $pixelspery = ($h - 2 * $imgborder) / ($ymax - $ymin);
    $outpts = array();
    list($lines, $dots, $odots, $tplines, $ineqlines) = explode(';;', $str);
    if ($ineqlines == '') {
        return array();
    }
    $ineqlines = explode('),(', substr($ineqlines, 1, strlen($ineqlines) - 2));
    foreach ($ineqlines as $k => $val) {
        $pts = explode(',', $val);
        if ($type == 'linear' && $pts[0] > 10.2) {
            continue;
        } else if ($type == 'quadratic' && $pts[0] < 10.3) {
            continue;
        }
        $pts[1] = ($pts[1] - $imgborder) / $pixelsperx + $xmin;
        $pts[3] = ($pts[3] - $imgborder) / $pixelsperx + $xmin;
        $pts[5] = ($pts[5] - $imgborder) / $pixelsperx + $xmin;
        $pts[2] = ($h - $pts[2] - $imgborder) / $pixelspery + $ymin;
        $pts[4] = ($h - $pts[4] - $imgborder) / $pixelspery + $ymin;
        $pts[6] = ($h - $pts[6] - $imgborder) / $pixelspery + $ymin;
        if ($pts[0] == 10.2 || $pts[0] == 10.4) {
            $pts[0] = 'ne';
        } else {
            $pts[0] = 'eq';
        }
        $outpts[] = $pts;
    }
    return $outpts;
}

function getdotsdata($str, $xmin = null, $xmax = null, $ymin = null, $ymax = null, $w = null, $h = null) {
    if (is_string($xmin) && strpos($xmin, ',') !== false) {
        list($xmin, $xmax, $ymin, $ymax, $w, $h) = parsedrawgrid($xmin, $xmax);
    } else {
        if ($xmin === null) {
            $xmin = -5;
        }
        if ($xmax === null) {
            $xmax = 5;
        }
        if ($ymin === null) {
            $ymin = -5;
        }
        if ($ymax === null) {
            $ymax = 5;
        }
        if ($w === null) {
            $w = 300;
        }
        if ($h === null) {
            $h = 300;
        }
    }
    if (trim($str) == '' || $xmax == $xmin || $ymax == $ymin) {
        return [];
    } // invalid
    $imgborder = 5;
    $pixelsperx = ($w - 2 * $imgborder) / ($xmax - $xmin);
    $pixelspery = ($h - 2 * $imgborder) / ($ymax - $ymin);
    list($lines, $dots, $odots, $tplines, $ineqlines) = explode(';;', $str);
    if ($dots == '') {
        return array();
    }
    $dots = explode('),(', substr($dots, 1, strlen($dots) - 2));
    foreach ($dots as $k => $pt) {
        $pt =  explode(',', $pt);
        $pt[0] = ($pt[0] - $imgborder) / $pixelsperx + $xmin;
        $pt[1] = ($h - $pt[1] - $imgborder) / $pixelspery + $ymin;
        $dots[$k] = $pt;
    }
    return $dots;
}
function getopendotsdata($str, $xmin = null, $xmax = null, $ymin = null, $ymax = null, $w = null, $h = null) {
    if (is_string($xmin) && strpos($xmin, ',') !== false) {
        list($xmin, $xmax, $ymin, $ymax, $w, $h) = parsedrawgrid($xmin, $xmax);
    } else {
        if ($xmin === null) {
            $xmin = -5;
        }
        if ($xmax === null) {
            $xmax = 5;
        }
        if ($ymin === null) {
            $ymin = -5;
        }
        if ($ymax === null) {
            $ymax = 5;
        }
        if ($w === null) {
            $w = 300;
        }
        if ($h === null) {
            $h = 300;
        }
    }
    if (trim($str) == '' || $xmax == $xmin || $ymax == $ymin) {
        return [];
    } // invalid
    $imgborder = 5;
    $pixelsperx = ($w - 2 * $imgborder) / ($xmax - $xmin);
    $pixelspery = ($h - 2 * $imgborder) / ($ymax - $ymin);
    list($lines, $dots, $odots, $tplines, $ineqlines) = explode(';;', $str);
    if ($odots == '') {
        return array();
    }
    $dots = explode('),(', substr($odots, 1, strlen($odots) - 2));
    foreach ($dots as $k => $pt) {
        $pt =  explode(',', $pt);
        $pt[0] = ($pt[0] - $imgborder) / $pixelsperx + $xmin;
        $pt[1] = ($h - $pt[1] - $imgborder) / $pixelspery + $ymin;
        $dots[$k] = $pt;
    }
    return $dots;
}
function getlinesdata($str, $xmin = null, $xmax = null, $ymin = null, $ymax = null, $w = null, $h = null) {
    if (is_string($xmin) && strpos($xmin, ',') !== false) {
        list($xmin, $xmax, $ymin, $ymax, $w, $h) = parsedrawgrid($xmin, $xmax);
    } else {
        if ($xmin === null) {
            $xmin = -5;
        }
        if ($xmax === null) {
            $xmax = 5;
        }
        if ($ymin === null) {
            $ymin = -5;
        }
        if ($ymax === null) {
            $ymax = 5;
        }
        if ($w === null) {
            $w = 300;
        }
        if ($h === null) {
            $h = 300;
        }
    }
    if (trim($str) == '' || $xmax == $xmin || $ymax == $ymin) {
        return [];
    } // invalid
    $imgborder = 5;
    $pixelsperx = ($w - 2 * $imgborder) / ($xmax - $xmin);
    $pixelspery = ($h - 2 * $imgborder) / ($ymax - $ymin);
    list($lines, $dots, $odots, $tplines, $ineqlines) = explode(';;', $str);
    if ($lines == '') {
        return array();
    }
    $lines = explode(';', $lines);
    $out = array();
    foreach ($lines as $i => $line) {
        $out[$i] = array();
        $pts = explode('),(', substr($line, 1, strlen($line) - 2));
        foreach ($pts as $k => $pt) {
            $pt =  explode(',', $pt);
            if (count($pt) != 2) {
                continue;
            }
            $pt[0] = ($pt[0] - $imgborder) / $pixelsperx + $xmin;
            $pt[1] = ($h - $pt[1] - $imgborder) / $pixelspery + $ymin;
            $out[$i][$k] = array($pt[0], $pt[1]);
        }
    }
    return $out;
}

function getsnapwidthheight($xmin, $xmax, $ymin, $ymax, $width, $height, $snaptogrid) {
    $imgborder = 5;

    if (strpos($snaptogrid, ':') !== false) {
        $snapparts = explode(':', $snaptogrid);
    } else {
        $snapparts = array($snaptogrid, $snaptogrid);
    }
    $snapparts = array_map('floatval', $snapparts);
    if ($xmax - $xmin > 0 && !empty($snapparts[0])) {
        $newwidth = ($xmax - $xmin) * (round($snapparts[0] * ($width - 2 * $imgborder) / ($xmax - $xmin)) / $snapparts[0]) + 2 * $imgborder;
    } else {
        $newwidth = $width;
    }
    if ($ymax - $ymin > 0 && !empty($snapparts[1])) {
        $newheight = ($ymax - $ymin) * (round($snapparts[1] * ($height - 2 * $imgborder) / ($ymax - $ymin)) / $snapparts[1]) + 2 * $imgborder;
    } else {
        $newheight = $height;
    }
    return array($newwidth, $newheight);
}

function getsigfigs($val, $targetsigfigs = 0) {
    $val = trim(ltrim($val, " \n\r\t\v\x00-"));
    if (!is_numeric($val) || $val == 0) {
        return 0;
    }
    if (strpos($val, 'E') !== false) {
        preg_match('/^(\d*)\.?(\d*)E/', $val, $matches);
        if (!isset($matches[1])) {
            return 0;
        } // invalid
        $sigfigs = strlen($matches[1]) + strlen($matches[2]);
    } else {
        $gadploc = strpos($val, '.');
        if ($gadploc === false) { // no decimal place
            $sigfigs = max(min(strlen($val), $targetsigfigs), strlen(rtrim($val, '0')));
        } else if (abs($val) < 1) {
            $sigfigs = strlen(ltrim(substr($val, $gadploc + 1), '0'));
        } else {
            $sigfigs = strlen(ltrim($val, '0')) - 1;
        }
    }
    return $sigfigs;
}

function checksigfigs($givenans, $anans, $reqsigfigs, $exactsigfig, $reqsigfigoffset, $sigfigscoretype) {
    if (!is_numeric($givenans) || !is_numeric($anans) || $givenans * $anans < 0) {
        return false;
    } //move on if opposite signs
    if ($anans != 0) {
        $gasigfig = getsigfigs($givenans, $reqsigfigs);
        if ($exactsigfig) {
            if ($gasigfig != $reqsigfigs) {
                return false;
            }
        } else {
            if ($gasigfig < $reqsigfigs) {
                return false;
            }
            if ($reqsigfigoffset > 0 && $gasigfig - $reqsigfigs > $reqsigfigoffset) {
                return false;
            }
        }
    } else {
        $gasigfig = 0;
    }
    if ($sigfigscoretype === false) {
        return true;
    }
    $epsilon = (($anans == 0 || abs($anans) > 1) ? 1E-12 : (abs($anans) * 1E-12));
    // We've confirmed the sigfigs on givenans are acceptable, so
    // now round anans to givenans's sigfigs for numeric comparison
    $anans = roundsigfig($anans, $gasigfig);

    //checked format, now check value
    if ($sigfigscoretype[0] == 'abs') {
        if (abs($anans - $givenans) < $sigfigscoretype[1] + $epsilon) {
            return true;
        }
    } else if ($sigfigscoretype[0] == 'rel') {
        if ($anans == 0) {
            if (abs($anans - $givenans) < $sigfigscoretype[1] + $epsilon) {
                return true;
            }
        } else {
            if (abs($anans - $givenans) / (abs($anans) + $epsilon) < $sigfigscoretype[1] / 100 + $epsilon) {
                return true;
            }
        }
    }
    return false;
}
/* for reference, in case new version has issues
function checksigfigs_old($givenans, $anans, $reqsigfigs, $exactsigfig, $reqsigfigoffset, $sigfigscoretype) {
	if (!is_numeric($givenans) || !is_numeric($anans) || $givenans*$anans < 0) { return false;} //move on if opposite signs
	if ($anans!=0) {
		$v = -1*floor(-log10(abs($anans))-1e-12) - $reqsigfigs;
	}
	$epsilon = (($anans==0||abs($anans)>1)?1E-12:(abs($anans)*1E-12));
	
	if (strpos($givenans,'E')!==false) {  //handle computer-style scientific notation
		preg_match('/^-?[1-9]\.?(\d*)E/', $givenans, $matches);
		$gasigfig = 1+strlen($matches[1]);
		if ($exactsigfig) {
			if ($gasigfig != $reqsigfigs) {return false;}
		} else {
			if ($gasigfig < $reqsigfigs) {return false;}
			if ($reqsigfigoffset>0 && $gasigfig-$reqsigfigs>$reqsigfigoffset) {return false;}
		}
	} else {
		if (!$exactsigfig) {
			$absgivenans = str_replace('-','',$givenans);
			$gadploc = strpos($absgivenans,'.');
			if ($gadploc===false) { // no decimal place
                if ($anans != 0 && strlen($absgivenans) < $reqsigfigs) { return false; } //not enough digits
                if ($anans != 0 && $reqsigfigoffset>0 && strlen(rtrim($absgivenans,'0')) > $reqsigfigs + $reqsigfigoffset) {return false;} //too many sigfigs
                $gasigfig = max($reqsigfigs, strlen(rtrim($absgivenans,'0')));
            } else {
				if (abs($givenans)<1) {
					$gasigfig = strlen(ltrim(substr($absgivenans,$gadploc+1),'0'));
				} else {
					$gasigfig = strlen(ltrim($absgivenans,'0'))-1;
				}
                if ($anans != 0 && $gasigfig < $reqsigfigs ) { return false; } //not enough sigfigs
                if ($anans != 0 && $reqsigfigoffset>0 && $gasigfig > $reqsigfigs + $reqsigfigoffset) {return false;} //too many sigfigs
            }
		} else {
			$absgivenans = str_replace('-','',$givenans);
			$gadploc = strpos($absgivenans,'.');
			if ($gadploc===false) { //no decimal place
				if (strlen(rtrim($absgivenans,'0')) > $reqsigfigs || 
                    strlen($absgivenans) < $reqsigfigs
                ) { 
                    return false;
                }
                $gasigfig = $reqsigfigs;
			} else {
				if (abs($givenans)<1) {
					if (strlen(ltrim(substr($absgivenans,$gadploc+1),'0')) != $reqsigfigs) { return false;}
				} else {
					if (strlen(ltrim($absgivenans,'0'))-1 != $reqsigfigs) { return false;}
				}
                $gasigfig = $reqsigfigs;
			}
		}
	}
    if ($sigfigscoretype === false) {
        return true;
    }
    // We've confirmed the sigfigs on givenans are acceptable, so
    // now round anans to givenans's sigfigs for numeric comparison
    $anans = roundsigfig($anans, $gasigfig);

    //checked format, now check value
	if ($sigfigscoretype[0]=='abs') {
		if (abs($anans-$givenans)< $sigfigscoretype[1]+$epsilon) {return true;}
	} else if ($sigfigscoretype[0]=='rel') {
		if ($anans==0) {
			if (abs($anans - $givenans) < $sigfigscoretype[1]+$epsilon) {return true;}
		} else {
			if (abs($anans - $givenans)/(abs($anans)+$epsilon) < $sigfigscoretype[1]/100+$epsilon) {return true;}
		}
	}
	return false;
}
*/


function getntupleparts($string, $expected = null, $checknumeric = false) {
    if (empty($string) || !is_scalar($string) || strlen($string) < 5) {
        return false;
    }
    if (!preg_match('/^\s*[\[\({<].*[\]\)}>]\s*$/', $string)) {
        return false;
    }
    $ntuples = parseNtuple($string, false, false);
    if (!is_array($ntuples) || !isset($ntuples[0])) {
        return false;
    }
    if (is_numeric($expected) && $expected != count($ntuples[0]['vals'])) {
        return false;
    }
    if ($checknumeric) {
        foreach ($ntuples[0]['vals'] as $v) {
            if (!is_numeric($v)) {
                return false;
            }
        }
    }
    return $ntuples[0]['vals'];
}


function stuansready($stu, $qn, $parts = null, $anstypes = null, $answerformat = null) {
    if (!isset($stu[$qn])) {
        return false;
    }
    if (!is_array($stu[$qn])) {
        if ($parts === null) { // if not multipart, change it so it looks like one
            $stu[$qn] = [$stu[$qn]];
            $parts = [0];
        } else {
            return false;
        }
    }
    if (!is_array($parts) && is_numeric($parts)) {
        $parts = [$parts];
    }
    if (!is_array($parts)) {
        echo "Invalid input for parts in stuansready";
        return false;
    }
    if ($anstypes !== null && !is_array($anstypes)) {
        $anstypes = array_map('trim', explode(',', $anstypes));
        if (count($parts) == 1 && count($anstypes) == 1) {
            // string given as anstypes, and only one part indicated, so use that string as
            // anstype for that part
            $anstypes[$parts[0]] = $anstypes[0];
        }
    }
    foreach ($parts as $part) {
        $ors = array_map('trim', explode(' or ', $part));
        $partok = false;
        foreach ($ors as $v) {
            $blankok = false;
            if (is_string($v) && $v[0] == '~') {
                $blankok = true;
                $v = substr($v, 1);
            }
            if (!isset($stu[$qn][$v])) {
                continue;
            }
            if ($anstypes !== null && $answerformat !== null && $stu[$qn][$v] !== '') {
                $thisaf = '';
                if (is_array($answerformat) && !empty($answerformat[$v])) {
                    $thisaf = $answerformat[$v];
                } else if (!is_array($answerformat)) {
                    $thisaf = $answerformat;
                }
                if (($anstypes[$v] == 'calculated' || $anstypes[$v] == 'number') && strpos($thisaf, 'checknumeric') !== false) {
                    if (!is_numeric($stu[$qn][$v])) {
                        continue;
                    }
                } else if ($thisaf !== '') {
                    if ($anstypes[$v] == 'calculated' && !checkanswerformat($stu[$qn][$v], $thisaf)) {
                        continue;
                    }
                }
            }
            //echo $stu[$qn][$v];
            if (
                $anstypes !== null && ($anstypes[$v] === 'matrix' || $anstypes[$v] === 'calcmatrix') &&
                isset($stu[$qn][$v]) && ($stu[$qn][$v] === '' || strpos($stu[$qn][$v], '||') !== false ||
                    $stu[$qn][$v][0] === '|' || $stu[$qn][$v][strlen($stu[$qn][$v]) - 1] === '|' ||
                    strpos($stu[$qn][$v], 'NaN') !== false ||
                    ($anstypes[$v] === 'matrix' && strpos($stu[$qn][$v], '|') !== false && preg_match('/[^\d\.\-\+E\s\|]/', $stu[$qn][$v])))
            ) {
                // empty looking matrix entry
                continue;
            }
            if ($anstypes !== null && $anstypes[$v] === 'choices' && !$blankok && $stu[$qn][$v] === 'NA') {
                continue;
            }
            if (isset($stu[$qn][$v]) && ($blankok || (trim($stu[$qn][$v]) !== '' && $stu[$qn][$v] !== ';;;;;;;;'))) {
                $partok = true;
                break;
            }
        }
        if ($partok === false) {
            return false;
        }
    }
    return true;
}

function getstuans($v, $q, $i = 0, $blankasnull = true) {
    if (!isset($v[$q])) {
        return null;
    }
    if (is_array($v[$q])) {
        if (!isset($v[$q][$i])) {
            return null;
        } else if ($blankasnull && ($v[$q][$i] === '' || $v[$q][$i] === 'NA')) {
            return null;
        }
        return $v[$q][$i];
    } else {
        if ($blankasnull && ($v[$q] === '' || $v[$q] === 'NA')) {
            return null;
        }
        return $v[$q];
    }
}


function checkreqtimes($tocheck,$rtimes) {
	global $mathfuncs, $myrights;
	if (!is_string($rtimes)) {
		if ($myrights > 10 && !empty($GLOBALS['inQuestionTesting'])) {
			echo "Invalid requiretimes; should be a string";
		}
		return 1;
	}
    if ($rtimes=='') {return 1;}
	if ($tocheck=='DNE' || $tocheck=='oo' || $tocheck=='+oo' || $tocheck=='-oo') {
		return 1;
	}
	//why?  $cleanans = preg_replace('/[^\w\*\/\+\-\(\)\[\],\.\^=\|<>_!]+/','',$tocheck);
    $cleanans = $tocheck;

	//if entry used pow or exp, we want to replace them with their asciimath symbols for requiretimes purposes
	$cleanans = str_replace("pow","^",$cleanans);
	$cleanans = str_replace("exp","e",$cleanans);
	$cleanans = preg_replace('/\^\((-?[\d\.]+)\)([^\d]|$)/','^$1 $2', $cleanans);
    
	if (is_numeric($cleanans) && $cleanans>0 && $cleanans<1) {
		$cleanans = ltrim($cleanans,'0');
	}
	$ignore_case = true;
	if ($rtimes != '') {
		$list = array_map('trim',explode(",",$rtimes));
		for ($i=0;$i < count($list);$i+=2) {
			if ($list[$i]=='') {continue;}
			if (!isset($list[$i+1]) ||
			   (strlen($list[$i+1])<2 && $list[$i]!='ignore_case' && $list[$i]!='ignore_commas' && $list[$i]!='ignore_symbol')) {
				if ($myrights>10) {
					echo "Invalid requiretimes - check format";
				}
				continue;
			}
			$list[$i+1] = trim($list[$i+1]);
			if ($list[$i]=='ignore_case') {
				$ignore_case = ($list[$i+1]==='1' || $list[$i+1]==='true' || $list[$i+1]==='=1');
				continue;
			} else if ($list[$i]=='ignore_commas') {
				if ($list[$i+1]==='1' || $list[$i+1]==='true' || $list[$i+1]==='=1') {
					$cleanans = str_replace(',','',$cleanans);
				}
				continue;
			} else if ($list[$i]=='ignore_symbol') {
				$cleanans = str_replace($list[$i+1],'',$cleanans);
				continue;
			} else if ($list[$i]=='ignore_spaces') {
				if ($list[$i+1]==='1' || $list[$i+1]==='true' || $list[$i+1]==='=1') {
					$cleanans = str_replace(' ','',$cleanans);
				}
				continue;
            }
			$comp = substr($list[$i+1],0,1);
			if (substr($list[$i+1],1,1)==='=') { //<=, >=, ==, !=
				if ($comp=='<' || $comp=='>') {
					$comp .= '=';
				}
				$num = intval(substr($list[$i+1],2));
			} else {
				$num = intval(substr($list[$i+1],1));
			}
			$grouptocheck = array_map('trim', explode('||',$list[$i]));
			$okingroup = false;
			foreach ($grouptocheck as $lookfor) {
				if ($lookfor=='#') {
					$nummatch = preg_match_all('/[\d\.]+/',$cleanans,$m);
				} else if ($lookfor[0]=='#') {
					if (!isset($all_numbers)) {
						preg_match_all('/[\d\.]+/',$cleanans,$matches);
						$all_numbers = $matches[0];
						array_walk($all_numbers, 'ltrimzero');
					}
					$lookfor = trim(substr($lookfor,1));
                    if ($lookfor[0] == '-') {
                        $lookfor = substr($lookfor,1);
                    }
                    $lookfor = ltrim($lookfor, ' 0');
                    $nummatch = count(array_keys($all_numbers,$lookfor));
				} else if (strlen($lookfor)>6 && substr($lookfor,0,6)=='regex:') {
					$regex = str_replace('/','\\/',substr($lookfor,6));
					$nummatch = preg_match_all('/'.$regex.'/'.($ignore_case?'i':''),$cleanans,$m);
				} else {
					if ($ignore_case || in_array($lookfor, $mathfuncs)) {
						$nummatch = substr_count(strtolower($cleanans),strtolower($lookfor));
					} else {
						$nummatch = substr_count($cleanans,$lookfor);
					}
                }
                
				if ($comp == "=") {
					if ($nummatch==$num) {
						$okingroup = true;
						break;
					}
				} else if ($comp == "<") {
					if ($nummatch<$num) {
						$okingroup = true;
						break;
					}
				} else if ($comp == "<=") {
					if ($nummatch<=$num) {
						$okingroup = true;
						break;
					}
				} else if ($comp == ">") {
					if ($nummatch>$num) {
						$okingroup = true;
						break;
					}
				} else if ($comp == ">=") {
					if ($nummatch>=$num) {
						$okingroup = true;
						break;
					}
				} else if ($comp == "!") {
					if ($nummatch!=$num) {
						$okingroup = true;
						break;
					}
				} else if ($myrights>10) {
					echo "Invalid requiretimes - check format";
				}
			}
			if (!$okingroup) {
				return 0;
			}
		}
	}
	return 1;
}

//checks the format of a value
//tocheck:  string to check
//ansformats:  array of answer formats.  Currently supports:
//   fraction, reducedfraction, fracordec, notrig, nolongdec, scinot, mixednumber, nodecimal
//returns:  false: bad format, true: good format
function checkanswerformat($tocheck,$ansformats) {
	$tocheck = trim($tocheck);
	$tocheck = str_replace(',','',$tocheck);
	if (!is_array($ansformats)) {$ansformats = explode(',',$ansformats);}
	if (strtoupper($tocheck)=='DNE' || $tocheck=='oo' || $tocheck=='+oo' || $tocheck=='-oo') {
		return true;
	}
	if (in_array("allowmixed",$ansformats) && preg_match('/^\s*(\-?\s*\d+)\s*(_|\s)\s*(\d+)\s*\/\s*(\d+)\s*$/',$tocheck,$mnmatches)) {
		//rewrite mixed number as an improper fraction
		$num = str_replace(' ','',$mnmatches[1])*$mnmatches[4] + $mnmatches[3]*1;
		$tocheck = $num.'/'.$mnmatches[4];
	}
    if (!in_array("allowmixed",$ansformats) && preg_match('/\-?\s*\d+\s*(_|\s)\s*(\d+|\(\d+\))\s*\/\s*(\d+|\(\d+\))/',$tocheck,$mnmatches)) {
		return false;
	}

	if (in_array("fraction",$ansformats) || in_array("reducedfraction",$ansformats) || in_array("fracordec",$ansformats)) {
		$tocheck = preg_replace('/([0-9])\s+([0-9])/','$1*$2',$tocheck);
        $tocheck = preg_replace('/\s/','',$tocheck);
		if (!preg_match('/^\(?\-?\s*\(?\d+\)?\/\(?\-?\d+\)?$/',$tocheck) && !preg_match('/^\(?\d+\)?\/\(?\-?\d+\)?$/',$tocheck) && !preg_match('/^\s*?\-?\s*\d+\s*$/',$tocheck) && (!in_array("fracordec",$ansformats) || !preg_match('/^\s*?\-?\s*\d*?\.\d*?\s*$/',$tocheck))) {
			return false;
		} else {
			if (in_array("reducedfraction",$ansformats) && strpos($tocheck,'/')!==false) {
				$tocheck = str_replace(array('(',')'),'',$tocheck);
				$tmpa = explode("/",$tocheck);
				if (gcd(abs($tmpa[0]),abs($tmpa[1]))!=1 || $tmpa[1]==1) {
					return false;
				} else if (substr_count($tocheck,'-')>1) {
                    return false;
                }
			}
		}
	}
	if (in_array("notrig",$ansformats)) {
		if (preg_match('/(sin|cos|tan|cot|csc|sec)/i',$tocheck)) {
			return false;
		}
    }
    if (!in_array("allowdegrees",$ansformats)) {
        if (strpos($tocheck,'degree') !== false) {
            return false;
        }
	}
	if (in_array("nolongdec",$ansformats)) {
		if (preg_match('/\.\d{6}/',$tocheck)) {
			return false;
		}
	}
	if (in_array("decimal", $ansformats)) {
		$totest = str_replace(' ','',$tocheck);
		if (!is_numeric($totest) || !preg_match('/^\-?(\d+|\d+\.\d*|\d*\.\d+)([eE]\-?\d+)?$/',$totest)) {
			return false;
		}
	}
	if (in_array("integer", $ansformats)) {
		$totest = str_replace(' ','',$tocheck);
		if (!is_numeric($totest) || preg_match('/\..*[1-9]/',$totest)) {
			return false;
		}
	}
	if (in_array("scinotordec",$ansformats)) {
		$totest = str_replace(' ','',$tocheck);
		if (!is_numeric($totest) && !preg_match('/^\-?[1-9](\.\d*)?(\*|xx|x|X|×|✕)10\^(\(?\-?\d+\)?)$/',$totest)) {
			return false;
		}
	}
	if (in_array("scinot",$ansformats)) {
		$totest = str_replace(' ','',$tocheck);
		if (!preg_match('/^\-?[1-9](\.\d*)?(\*|xx|x|X|×|✕)10\^(\(?\(?\-?\d+\)?\)?)$/',$totest)) {
			return false;
		}
	}

	if (in_array("mixednumber",$ansformats) || in_array("sloppymixednumber",$ansformats) || in_array("mixednumberorimproper",$ansformats)) {
		if (preg_match('/^\(?\-?\s*\(?\d+\)?\/\(?\d+\)?$/',$tocheck) || preg_match('/^\(?\d+\)?\/\(?\-?\d+\)?$/',$tocheck)) { //fraction
			$tmpa = explode("/",str_replace(array(' ','(',')'),'',$tocheck));
			if (in_array("mixednumber",$ansformats)) {
				if (!in_array("allowunreduced",$ansformats) && ((gcd(abs($tmpa[0]),abs($tmpa[1]))!=1) || abs($tmpa[0])>=abs($tmpa[1]))) {
					return false;
				}
			} else if (in_array("mixednumberorimproper",$ansformats)) {
				if (!in_array("allowunreduced",$ansformats) && ((gcd(abs($tmpa[0]),abs($tmpa[1]))!=1))) {
					return false;
				}
			}
		} else if (preg_match('/^\s*\-?\s*\d+\s*(_|\s)\s*\(?(\d+)\)?\s*\/\s*\(?(\d+)\)?\s*$/',$tocheck,$mnmatches)) { //mixed number
			if (in_array("mixednumber",$ansformats)) {
				if ($mnmatches[2]>=$mnmatches[3] || (!in_array("allowunreduced",$ansformats) && gcd($mnmatches[2],$mnmatches[3])!=1)) {
					return false;
				}
			} else if (in_array("mixednumberorimproper",$ansformats)) {
				if ((!in_array("allowunreduced",$ansformats) && gcd($mnmatches[2],$mnmatches[3])!=1) || $mnmatches[2]>=$mnmatches[3])  {
					return false;
				}
			}
		} else if (preg_match('/^\s*\-?\s*\d+\s*$/',$tocheck)) { //integer

		} else { //not a valid format
			return false;
		}
	}

	if (in_array("nodecimal",$ansformats)) {
		if (strpos($tocheck,'.')!==false) {
			return false;
		}
		if (strpos($tocheck,'E-')!==false) {
			return false;
		}
		if (preg_match('/10\^\(?\-/',$tocheck)) {
			return false;
		}
	}
	return true;
}

/** internal functions */

function parsedrawgrid($str, $snaptogrid) {
    $p = array_map('trim', explode(',', $str));
    $xmin = isset($p[0]) ? $p[0] : -5;
    if (is_string($xmin)) {
        $pts = explode(':', $xmin);
        $xmin = $pts[count($pts) - 1];
    }
    $xmax = isset($p[1]) ? $p[1] : 5;
    $ymin = isset($p[2]) ? $p[2] : -5;
    if (is_string($ymin)) {
        $pts = explode(':', $ymin);
        $ymin = $pts[count($pts) - 1];
    }
    $ymax = isset($p[3]) ? $p[3] : 5;
    $w = isset($p[6]) ? $p[6] : 300;
    $h = isset($p[7]) ? $p[7] : 300;
    if ($snaptogrid !== null) { // snaptogrid given
        list($neww, $newh) = getsnapwidthheight($xmin, $xmax, $ymin, $ymax, $w, $h, $snaptogrid);
        if (abs($neww - $w) / $w < .1) {
            $w = $neww;
        }
        if (abs($newh - $h) / $h < .1) {
            $h = $newh;
        }
    }
    return [$xmin, $xmax, $ymin, $ymax, $w, $h];
}

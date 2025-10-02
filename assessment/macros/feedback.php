<?php

// feedback macros

array_push(
    $GLOBALS['allowedmacros'],
    'getfeedbackbasic',
    'getfeedbacktxt',
    'getfeedbacktxtessay',
    'getfeedbacktxtnumber',
    'getfeedbacktxtcalculated',
    'getfeedbacktxtnumfunc'
);

function getfeedbackbasic($correct, $wrong, $thisq, $partn = null) {
    global $rawscores, $imasroot, $staticroot;
    if (isset($GLOBALS['testsettings']['testtype']) && ($GLOBALS['testsettings']['testtype'] == 'NoScores' || $GLOBALS['testsettings']['testtype'] == 'EndScore')) {
        return '';
    }
    if (isset($GLOBALS['assessUIver']) && $GLOBALS['assessUIver'] > 1) {
        $val = $GLOBALS['assess2-curq-iscorrect'] ?? -1;
        if (is_array($partn) && is_array($val)) {
            $res = 1;
            foreach ($partn as $i) {
                if (!isset($val[$i])) {
                    $res = -1;
                    break;
                } else if ($val[$i] < $res) {
                    $res = $val[$i];
                }
            }
        } else if ($partn !== null && is_array($val)) {
            if (isset($val[$partn])) {
                $res = $val[$partn];
            } else {
                $res = -1;
            }
        } else {
            $res = $val;
        }
        if ($res > 0 && $res < 1) {
            $res = 0;
        }
    } else if (isset($rawscores[$thisq - 1])) {
        $qn = $thisq - 1;
        if (strpos($rawscores[$qn], '~') === false) {
            $res = ($rawscores[$qn] < 0) ? -1 : (($rawscores[$qn] == 1) ? 1 : 0);
        } else {
            $sp = explode('~', $rawscores[$qn]);
            if ($partn === null) {
                $res = 1;
                for ($j = 0; $j < count($sp); $j++) {
                    if ($sp[$j] != 1) {
                        $res = 0;
                        break;
                    }
                }
            } else {
                $res = ($sp[$partn] == 1) ? 1 : 0;
            }
        }
    } else {
        return '';
    }
    if ($res == -1) {
        return '';
    } else if ($res == 1) {
        return '<div class="feedbackwrap correct"><img src="' . $staticroot . '/img/gchk.gif" alt="Correct"/> ' . $correct . '</div>';
    } else if ($res == 0) {
        return '<div class="feedbackwrap incorrect"><img src="' . $staticroot . '/img/redx.gif" alt="Incorrect"/> ' . $wrong . '</div>';
    }
}

function getfeedbacktxt($stu, $fbtxt, $ans) {
    global $imasroot, $staticroot;
    if (isset($GLOBALS['testsettings']['testtype']) && ($GLOBALS['testsettings']['testtype'] == 'NoScores' || $GLOBALS['testsettings']['testtype'] == 'EndScore')) {
        return '';
    }
    if ($stu === null || !is_scalar($stu) || !is_scalar($ans)) {
        return " ";
    } else if ($stu === 'NA') {
        return '<div class="feedbackwrap"><img src="' . $staticroot . '/img/redx.gif" alt="Incorrect"/> ' . _("No answer selected. Try again.") . '</div>';
    } else {
        $anss = explode(' or ', $ans);
        foreach ($anss as $ans) {
            if ($stu == $ans) {
                $out = '<div class="feedbackwrap correct"><img src="' . $staticroot . '/img/gchk.gif" alt="Correct"/> ';
                if (isset($fbtxt[$stu])) {
                    $out .= $fbtxt[$stu];
                }
                return $out .= '</div>';
            }
        }
        $out = '<div class="feedbackwrap incorrect"><img src="' . $staticroot . '/img/redx.gif" alt="Incorrect"/> ';
        if (isset($fbtxt[$stu])) {
            $out .= $fbtxt[$stu];
        }
        return $out .= '</div>';
    }
}

function getfeedbacktxtessay($stu, $fbtxt) {
    if (isset($GLOBALS['testsettings']['testtype']) && ($GLOBALS['testsettings']['testtype'] == 'NoScores' || $GLOBALS['testsettings']['testtype'] == 'EndScore')) {
        return '';
    }
    if ($stu === null || !is_scalar($stu) || trim($stu) == '') {
        return '';
    } else {
        return '<div class="feedbackwrap correct">' . $fbtxt . '</div>';
    }
}

function getfeedbacktxtnumber($stu, $partial, $fbtxt, $deffb = 'Incorrect', $tol = .001) {
    global $imasroot, $staticroot;
    if (isset($GLOBALS['testsettings']['testtype']) && ($GLOBALS['testsettings']['testtype'] == 'NoScores' || $GLOBALS['testsettings']['testtype'] == 'EndScore')) {
        return '';
    }

    if ($stu === null || !is_scalar($stu)) {
        return " ";
    } else {
        $stu = trim($stu);
        // handle DNE,oo,+-oo
        if (strtoupper($stu) === 'DNE' || $stu === 'oo' || $stu === '-oo' || $stu === '+oo') {
            if ($stu == '+oo') {
                $stu = 'oo';
            }
            for ($i = 0; $i < count($partial); $i += 2) {
                if ($partial[$i] === '+oo') {
                    $partial[$i] = 'oo';
                }
                if ($stu === $partial[$i]) {
                    if ($partial[$i + 1] < 1) {
                        return '<div class="feedbackwrap incorrect"><img src="' . $staticroot . '/img/redx.gif" alt="Incorrect"/> ' . $fbtxt[$i / 2] . '</div>';
                    } else {
                        return '<div class="feedbackwrap correct"><img src="' . $staticroot . '/img/gchk.gif" alt="Correct"/> ' . $fbtxt[$i / 2] . '</div>';
                    }
                }
            }
            return '<div class="feedbackwrap incorrect"><img src="' . $staticroot . '/img/redx.gif" alt="Incorrect"/> ' . $deffb . '</div>';
        }
        $stu = preg_replace('/[^\-\d\.eE]/', '', $stu);
    }

    if (!is_numeric($stu)) {
        return '<div class="feedbackwrap incorrect"><img src="' . $staticroot . '/img/redx.gif" alt="Incorrect"/> ' . _("This answer does not appear to be a valid number.") . '</div>';
    } else {
        if (strval($tol)[0] == '|') {
            $abstol = true;
            $tol = substr($tol, 1);
        } else {
            $abstol = false;
        }
        $match = -1;
        if (!is_array($partial)) {
            $partial = listtoarray($partial);
        }
        for ($i = 0; $i < count($partial); $i += 2) {
            if ($partial[$i] === 'DNE' || $partial[$i] === 'oo' || $partial[$i] === '-oo' || $partial[$i] === '+oo') {
                continue;
            }
            if (!is_numeric($partial[$i])) {
                $partial[$i] = evalMathParser($partial[$i]);
            }
            $eps = (($partial[$i] == 0 || abs($partial[$i]) > 1) ? 1E-12 : (abs($partial[$i]) * 1E-12));
            if ($abstol) {
                if (abs($stu - $partial[$i]) < $tol + $eps) {
                    $match = $i;
                    break;
                }
            } else {
                if (abs($stu - $partial[$i]) / (abs($partial[$i]) + $eps) < $tol + 1E-12) {
                    $match = $i;
                    break;
                }
            }
        }
        if ($match > -1 && isset($fbtxt[$match / 2])) {
            if ($partial[$i + 1] < 1) {
                return '<div class="feedbackwrap incorrect"><img src="' . $staticroot . '/img/redx.gif" alt="Incorrect"/> ' . $fbtxt[$match / 2] . '</div>';
            } else {
                return '<div class="feedbackwrap correct"><img src="' . $staticroot . '/img/gchk.gif" alt="Correct"/> ' . $fbtxt[$match / 2] . '</div>';
            }
        } else {
            return '<div class="feedbackwrap incorrect"><img src="' . $staticroot . '/img/redx.gif" alt="Incorrect"/> ' . $deffb . '</div>';
        }
    }
}

function getfeedbacktxtcalculated($stu, $stunum, $partial, $fbtxt, $deffb = 'Incorrect', $answerformat = '', $requiretimes = '', $tol = .001) {
    global $imasroot, $staticroot;
    if (isset($GLOBALS['testsettings']['testtype']) && ($GLOBALS['testsettings']['testtype'] == 'NoScores' || $GLOBALS['testsettings']['testtype'] == 'EndScore')) {
        return '';
    }
    if ($stu === null || !is_numeric($stunum)) {
        return " ";
    } else {
        if (strval($tol)[0] == '|') {
            $abstol = true;
            $tol = substr($tol, 1);
        } else {
            $abstol = false;
        }
        $match = -1;
        if (!is_array($partial)) {
            $partial = listtoarray($partial);
        }
        for ($i = 0; $i < count($partial); $i += 2) {
            $idx = $i / 2;
            if (is_array($requiretimes)) {
                if ($requiretimes[$idx] != '') {
                    if (checkreqtimes(str_replace(',', '', $stu), $requiretimes[$idx]) == 0) {
                        $rightanswrongformat = $i;
                        continue;
                    }
                }
            } else if ($requiretimes != '') {
                if (checkreqtimes(str_replace(',', '', $stu), $requiretimes) == 0) {
                    $rightanswrongformat = $i;
                    continue;
                }
            }
            if (is_array($answerformat)) {
                if ($answerformat[$idx] != '') {
                    if (checkanswerformat($stu, $answerformat[$idx]) == 0) {
                        $rightanswrongformat = $i;
                        continue;
                    }
                }
            } else if ($answerformat != '') {
                if (checkanswerformat($stu, $answerformat) == 0) {
                    $rightanswrongformat = $i;
                    continue;
                }
            }
            if (!is_numeric($partial[$i])) {
                $partial[$i] = evalMathParser($partial[$i]);
            }
            $eps = (($partial[$i] == 0 || abs($partial[$i]) > 1) ? 1E-12 : (abs($partial[$i]) * 1E-12));
            if ($abstol) {
                if (abs($stunum - $partial[$i]) < $tol + $eps) {
                    $match = $i;
                    break;
                }
            } else {
                if (abs($stunum - $partial[$i]) / (abs($partial[$i]) + $eps) < $tol + 1E-12) {
                    $match = $i;
                    break;
                }
            }
        }
        if ($match > -1) {
            if ($partial[$i + 1] < 1) {
                return '<div class="feedbackwrap incorrect"><img src="' . $staticroot . '/img/redx.gif" alt="Incorrect"/> ' . $fbtxt[$match / 2] . '</div>';
            } else {
                return '<div class="feedbackwrap correct"><img src="' . $staticroot . '/img/gchk.gif" alt="Correct"/> ' . $fbtxt[$match / 2] . '</div>';
            }
        } else {
            return '<div class="feedbackwrap incorrect"><img src="' . $staticroot . '/img/redx.gif" alt="Incorrect"/> ' . $deffb . '</div>';
        }
    }
}

//$partial = array(answer,partialcreditval,answer,partialcreditval,...)
function getfeedbacktxtnumfunc($stu, $partial, $fbtxt, $deffb = 'Incorrect', $vars = 'x', $requiretimes = '', $tol = '.001', $domain = '-10,10') {
    global $imasroot, $staticroot;
    if (isset($GLOBALS['testsettings']['testtype']) && ($GLOBALS['testsettings']['testtype'] == 'NoScores' || $GLOBALS['testsettings']['testtype'] == 'EndScore')) {
        return '';
    }
    if ($stu === null || trim($stu) === '') {
        return " ";
    } else {
        if (strval($tol)[0] == '|') {
            $abstol = true;
            $tol = substr($tol, 1);
        } else {
            $abstol = false;
        }
        $type = "expression";
        if (strpos($stu, '=') !== false) {
            $type = "equation";
        }
        $stuorig = $stu;
        $stu = str_replace(array('[', ']'), array('(', ')'), $stu);
        if ($type == 'equation') {
            $stu = preg_replace('/(.*)=(.*)/', '$1-($2)', $stu);
        }

        list($variables, $tps, $flist) = numfuncGenerateTestpoints($vars, $domain);
        $numpts = count($tps);
        $vlist = implode(",", $variables);
        $stu = numfuncPrepForEval($stu, $variables);

        $origstu = $stu;
        $stufunc = makeMathFunction(makepretty($stu), $vlist, [], $flist, true);
        if ($stufunc === false) {
            return '<div class="feedbackwrap incorrect"><img src="' . $staticroot . '/img/redx.gif" alt="Incorrect"/> ' . $deffb . '</div>';
        }

        $stupts = array();
        $cntnana = 0;
        $correct = true;
        for ($i = 0; $i < $numpts; $i++) {
            $varvals = array();
            for ($j = 0; $j < count($variables); $j++) {
                $varvals[$variables[$j]] = $tps[$i][$j];
            }
            $stupts[$i] = $stufunc($varvals);
            if (isNaN($stupts[$i])) {
                $cntnana++;
            }
            if ($stupts[$i] === false) {
                $correct = false;
                break;
            }
        }
        if ($cntnana == $numpts || !$correct) { //evald to NAN at all points
            return '<div class="feedbackwrap incorrect"><img src="' . $staticroot . '/img/redx.gif" alt="Incorrect"/> ' . $deffb . '</div>';
        }

        $match = -1;
        if (!is_array($partial)) {
            $partial = listtoarray($partial);
        }
        for ($k = 0; $k < count($partial); $k += 2) {
            $correct = true;
            $b =  numfuncPrepForEval($partial[$k], $variables);
            if ($type == 'equation') {
                if (substr_count($b, '=') != 1) {
                    continue;
                }
                $b = preg_replace('/(.*)=(.*)/', '$1-($2)', $b);
            } else if (strpos($b, '=') !== false) {
                continue;
            }
            $origb = $b;
            $bfunc = makeMathFunction(makepretty($b), $vlist, [], $flist, true);
            if ($bfunc === false) {
                //parse error - skip it
                continue;
            }
            $cntnanb = 0;
            $cntbothzero = 0;
            $cntzero = 0;
            $diffnan = 0;
            $ratios = array();
            for ($i = 0; $i < $numpts; $i++) {
                $varvals = array();
                for ($j = 0; $j < count($variables); $j++) {
                    $varvals[$variables[$j]] = $tps[$i][$j];
                }
                $ansb = $bfunc($varvals);

                //echo "real: $ansa, my: $ansb <br/>";
                if (isNaN($ansb)) {
                    $cntnanb++;
                    if (!isNaN($stupts[$i])) {
                        $diffnan++;
                    }
                    continue;
                } else if (isNaN($stupts[$i])) {
                    $diffnan++;
                }
                if (isNaN($stupts[$i])) {
                    continue;
                } //avoid NaN problems

                if ($type == 'equation') {
                    if (abs($stupts[$i]) > .000001 && is_numeric($ansb)) {
                        $ratios[] = $ansb / $stupts[$i];
                        if (abs($ansb) <= .00000001 && $stupts[$i] != 0) {
                            $cntzero++;
                        }
                    } else if (abs($stupts[$i]) <= .000001 && is_numeric($ansb) && abs($ansb) <= .00000001) {
                        $cntbothzero++;
                    }
                } else {
                    if ($abstol) {
                        if (abs($stupts[$i] - $ansb) > $tol - 1E-12) {
                            $correct = false;
                            break;
                        }
                    } else {
                        if ((abs($stupts[$i] - $ansb) / (abs($stupts[$i]) + .0001) > $tol - 1E-12)) {
                            $correct = false;
                            break;
                        }
                    }
                }
            }
            //echo "$i, $ansa, $ansb, $cntnana, $cntnanb";
            if ($cntnanb == 20) {
                continue;
            } else if ($i < 20) {
                continue;
            }
            if ($diffnan > 1) {
                continue;
            }
            if ($type == "equation") {
                if ($cntbothzero > $numpts - 2) {
                    $match = $k;
                    break;
                } else if (count($ratios) > 0) {
                    if (count($ratios) == $cntzero) {
                        continue;
                    } else {
                        $meanratio = array_sum($ratios) / count($ratios);
                        for ($i = 0; $i < count($ratios); $i++) {
                            if ($abstol) {
                                if (abs($ratios[$i] - $meanratio) > $tol - 1E-12) {
                                    continue 2;
                                }
                            } else {
                                if ((abs($ratios[$i] - $meanratio) / (abs($meanratio) + .0001) > $tol - 1E-12)) {
                                    continue 2;
                                }
                            }
                        }
                    }
                } else {
                    continue;
                }
            }
            if ($correct) {
                if (is_array($requiretimes)) {
                    if ($requiretimes[$k / 2] != '') {
                        if (checkreqtimes(str_replace(',', '', $stuorig), $requiretimes[$k / 2]) == 0) {
                            $rightanswrongformat = $k;
                            continue;
                        }
                    }
                } else if ($requiretimes != '') {
                    if (checkreqtimes(str_replace(',', '', $stuorig), $requiretimes) == 0) {
                        $rightanswrongformat = $k;
                        continue;
                    }
                }
                $match = $k;
                break;
            } else {
                continue;
            }
        }
        //WHAT to do with right answer, wrong format??
        if ($match > -1) {
            if ($partial[$match + 1] < 1) {
                return '<div class="feedbackwrap incorrect"><img src="' . $staticroot . '/img/redx.gif" alt="Incorrect"/> ' . $fbtxt[$match / 2] . '</div>';
            } else {
                return '<div class="feedbackwrap correct"><img src="' . $staticroot . '/img/gchk.gif" alt="Correct"/> ' . $fbtxt[$match / 2] . '</div>';
            }
        } else {
            return '<div class="feedbackwrap incorrect"><img src="' . $staticroot . '/img/redx.gif" alt="Incorrect"/> ' . $deffb . '</div>';
        }
    }
}

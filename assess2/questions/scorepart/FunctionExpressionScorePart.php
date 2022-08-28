<?php

namespace IMathAS\assess2\questions\scorepart;

require_once(__DIR__ . '/ScorePart.php');
require_once(__DIR__ . '/../models/ScorePartResult.php');

use IMathAS\assess2\questions\models\ScorePartResult;
use IMathAS\assess2\questions\models\ScoreQuestionParams;

class FunctionExpressionScorePart implements ScorePart
{
    private $scoreQuestionParams;

    public function __construct(ScoreQuestionParams $scoreQuestionParams)
    {
        $this->scoreQuestionParams = $scoreQuestionParams;
    }

    public function getResult(): ScorePartResult
    {
        global $mathfuncs;

        $scorePartResult = new ScorePartResult();

        $options = $this->scoreQuestionParams->getVarsForScorePart();
        $qn = $this->scoreQuestionParams->getQuestionNumber();
        $givenans = $this->scoreQuestionParams->getGivenAnswer();
        $multi = $this->scoreQuestionParams->getIsMultiPartQuestion();
        $partnum = $this->scoreQuestionParams->getQuestionPartNumber();

        $defaultreltol = .0015;

        $optionkeys = ['answer', 'reltolerance', 'abstolerance', 'answerformat',
            'variables', 'domain', 'ansprompt', 'formatfeedbackon'];
        foreach ($optionkeys as $optionkey) {
            ${$optionkey} = getOptionVal($options, $optionkey, $multi, $partnum);
        }
        $optionkeys = ['partialcredit'];
        foreach ($optionkeys as $optionkey) {
            ${$optionkey} = getOptionVal($options, $optionkey, $multi, $partnum, 2);
        }
        $requiretimes = getOptionVal($options, 'requiretimes', $multi, $partnum, 1);

        if ($reltolerance === '' && $abstolerance === '') { $reltolerance = $defaultreltol;}
 
        $ansformats = array_map('trim',explode(',',$answerformat));

        if ($multi) { $qn = ($qn+1)*1000+$partnum; }

        $givenans = normalizemathunicode(trim($givenans, " \n\r\t\v\x00,"));

        $givenans = preg_replace_callback(
            '/(arcsinh|arccosh|arctanh|arcsin|arccos|arctan|arcsec|arccsc|arccot|root|sqrt|sign|sinh|cosh|tanh|sech|csch|coth|abs|sin|cos|tan|sec|csc|cot|exp|log|ln)[\(\[]/i',
            function($m) { return strtolower($m[0]); },
            $givenans
        );
        $answer = normalizemathunicode($answer);
        
        if (in_array('nosoln',$ansformats) || in_array('nosolninf',$ansformats)) {
            list($givenans, $answer) = scorenosolninf($qn, $givenans, $answer, $ansprompt);
        }

        $scorePartResult->setLastAnswerAsGiven($givenans);

        $correct = true;

        if (empty($variables)) { $variables = "x";}
        list($variables, $tps, $flist) = numfuncGenerateTestpoints($variables, $domain);
        $givenans = numfuncPrepForEval($givenans, $variables);
        $answer = numfuncPrepForEval($answer, $variables);

        $vlist = implode(",",$variables);

        //handle nosolninf case
        if ($givenans==='oo' || $givenans==='DNE') {
            if (strcmp($answer,$givenans) === 0) {
                $scorePartResult->setRawScore(1);
                return $scorePartResult;
            } else {
                $scorePartResult->setRawScore(0);
                return $scorePartResult;
            }
        } else if ($answer==='DNE' || $answer==='oo') {
            $scorePartResult->setRawScore(0);
            return $scorePartResult;
        }

        if (!in_array('inequality',$ansformats) &&
            (strpos($answer,'<')!==false || strpos($answer,'>')!==false || strpos($answer,'!=')!==false)
         ) {
            echo 'Your $answer contains an inequality sign, but you do not have $answerformat="inequality" set. This question probably will not work right.';
        } else if (!in_array('equation',$ansformats) &&
          !in_array('inequality',$ansformats) &&
          strpos($answer,'=')!==false
        ) {
            echo 'Your $answer contains an equal sign, but you do not have $answerformat="equation" set. This question probably will not work right.';
        }

        $isListAnswer = in_array('list',$ansformats);
        if (in_array('allowplusminus', $ansformats)) {
            if (!$isListAnswer) {
                $ansformats[] = 'list';
                $isListAnswer = true;
            }
            $answer = rewritePlusMinus($answer);
            $givenans = rewritePlusMinus($givenans);
        }

        if ($isListAnswer) {
            $givenanslist = explode(',', $givenans);
        } else {
            $givenanslist = [$givenans];
        }

        $givenanslistvals = array();
        $givenanslistnormalized = array();
        $givenansused = array();
        foreach ($givenanslist as $givenans) {
            //build values for student answer
            $givenansvals = array();
            if (in_array('inequality',$ansformats)) {
                if (in_array('equation',$ansformats)) {
                    preg_match('/(.*)(<=|>=|<|>|!=|=)(.*)/', $givenans, $matches);
                } else {
                    preg_match('/(.*)(<=|>=|<|>|!=)(.*)/', $givenans, $matches);
                }
                if (!empty($matches)) {
                    $toevalGivenans = $matches[3] . '-(' . $matches[1] . ')';
                    $givenInequality = $matches[2];
                } else {
                    continue;
                }
            } else if (in_array('equation',$ansformats)) {
                if (substr_count($givenans, '=')!=1) {
                    continue;
                }
                $toevalGivenans = preg_replace('/(.*)=(.*)/','$1-($2)',$givenans);
            } else if (preg_match('/(=|<|>)/', $givenans)) {
                continue;
            } else {
                $toevalGivenans = $givenans;
            }

            $givenansfunc = parseMathQuiet($toevalGivenans, $vlist, [], $flist);
            if ($givenansfunc === false) { //parse error
                continue;
            }
            for ($i = 0; $i < 20; $i++) {
                $varvals = array();
                for($j=0; $j < count($variables); $j++) {
                    $varvals[$variables[$j]] = $tps[$i][$j];
                }
                $givenansvals[] = $givenansfunc->evaluateQuiet($varvals);
            }
            $givenanslistvals[] = $givenansvals;
            if (in_array('sameform',$ansformats)) {
                $givenanslistnormalized[] = $givenansfunc->normalizeTreeString();
            }
        }

        if ($isListAnswer) {
            $answerlist = explode(',', $answer);
        } else {
            $answerlist = [$answer];
        }
        $correctscores = array();

        foreach ($answerlist as $alidx => $answer) {

            $ansarr = array_map('trim',explode(' or ',$answer));
            $partialpts = array_fill(0, count($ansarr), 1);
            $origanscnt = count($ansarr);
            if (!empty($partialcredit) && !$isListAnswer) { // partial credit only works for non-list answers
                if (!is_array($partialcredit)) {$partialcredit = explode(',',$partialcredit);}
                for ($i=0;$i<count($partialcredit);$i+=2) {
                    $partialcredit[$i] = numfuncPrepForEval($partialcredit[$i], $variables);
                    if (!in_array($partialcredit[$i], $ansarr) || $partialcredit[$i+1]<1) {
                        $ansarr[] = $partialcredit[$i];
                        $partialpts[] = $partialcredit[$i+1];
                    }
                }
            }

            $rightanswrongformat = -1;

            foreach ($ansarr as $ansidx=>$answer) {
                if (is_array($requiretimes)) {
                    if ($isListAnswer) {
                        if (isset($requiretimes[$alidx])) {
                            $thisreqtimes = $requiretimes[$alidx];
                        } else {
                            $thisreqtimes = '';
                        }
                    } else if ($ansidx<$origanscnt) {
                        $thisreqtimes = $requiretimes[0];
                    } else {
                        $thisreqtimes = $requiretimes[$ansidx-$origanscnt+1];
                    }
                } else {
                    $thisreqtimes = $requiretimes;
                }
                $answer = preg_replace('/[^\w\*\/\+\=\-\(\)\[\]\{\}\,\.\^\$\!\s\'<>]+/','',$answer);

                if (in_array('inequality',$ansformats)) {
                    if (in_array('equation',$ansformats)) {
                        preg_match('/(.*)(<=|>=|<|>|!=|=)(.*)/', $answer, $matches);
                    } else {
                        preg_match('/(.*)(<=|>=|<|>|!=)(.*)/', $answer, $matches);
                    }
                    $answer = $matches[3] . '-(' . $matches[1] . ')';
                    $answerInequality = $matches[2];
                } else if (in_array('equation',$ansformats)) {
                    $answer = preg_replace('/(.*)=(.*)/','$1-($2)',$answer);
                } 
                if ($answer == '') {
                    continue;
                }
                $origanswer = $answer;
                $answerfunc = parseMathQuiet(makepretty($answer), $vlist, [], $flist);
                if ($answerfunc === false) {  // parse error on $answer - can't do much
                    continue;
                }

                $realanstmp = array();
                for ($i = 0; $i < 20; $i++) {
                    $varvals = array();
                    for($j=0; $j < count($variables); $j++) {
                        $varvals[$variables[$j]] = $tps[$i][$j];
                    }
                    $realans = $answerfunc->evaluateQuiet($varvals);
                    $realanstmp[] = $realans;
                }
                foreach ($givenanslistvals as $gaidx => $givenansvals) {
                    if (isset($givenansused[$gaidx])) {
                        continue; // already used this givenans
                    }

                    $givenansnormalized = $givenanslistnormalized[$gaidx] ?? '';
                    $correct = true;
                    $cntnan = 0;
                    $cntzero = 0;
                    $cntbothzero = 0;
                    $stunan = 0;
                    $ysqrtot = 0;
                    $reldifftot = 0;
                    $ratios = array();
                    $diffs = array();
                    $realanss = array();

                    foreach ($realanstmp as $i=>$realans) {
                        //echo "$answer, real: $realans, my: {$givenansvals[$i]},rel: ". (abs(10^16*$givenansvals[$i]-10^16*$realans))  ."<br/>";
                        if (isNaN($realans)) {$cntnan++; continue;} //avoid NaN problems
                        if (in_array('equation',$ansformats) || in_array('inequality',$ansformats) || in_array('scalarmult',$ansformats)) {  //if equation, store ratios
                            if (isNaN($givenansvals[$i])) {
                                $stunan++;
                            } elseif (abs($realans)>.00000001 && is_numeric($givenansvals[$i])) {
                                $ratios[] = $givenansvals[$i]/$realans;
                                if (abs($givenansvals[$i])<=.00000001 && $realans!=0) {
                                    $cntzero++;
                                }
                            } else if (abs($realans)<=.00000001 && is_numeric($givenansvals[$i]) && abs($givenansvals[$i])<=.00000001) {
                                $cntbothzero++;
                            }
                        } else if (in_array('toconst',$ansformats)) {
                            $diffs[] = $givenansvals[$i] - $realans;
                            $realanss[] = $realans;
                            $ysqr = $realans*$realans;
                            $ysqrtot += 1/($ysqr+.0001);
                            $reldifftot += ($givenansvals[$i] - $realans)/($ysqr+.0001);
                        } else { //otherwise, compare points
                            if (isNaN($givenansvals[$i])) {
                                $stunan++;
                            } else if ($abstolerance !== '') {
                                if (abs($givenansvals[$i]-$realans) > $abstolerance+1E-12) { $correct = false; break;}
                            } else {
                                if ((abs($givenansvals[$i]-$realans)/(abs($realans)+.0001) > $reltolerance+1E-12)) {$correct = false; break;}
                            }
                        }
                    }

                    if ($cntnan==20 && isset($GLOBALS['teacherid'])) {
                        echo "<p>", _('Debug info: function evaled to Not-a-number at all test points.  Check $domain'), "</p>";
                    }
                    if ($stunan>1) { //if more than 1 student NaN response
                        $correct = false; continue;
                    }
                    if (in_array('equation',$ansformats) || in_array('inequality',$ansformats) || in_array('scalarmult',$ansformats)) {
                        if ($cntbothzero>18) {
                            $correct = true;
                        } else if (count($ratios)>1) {
                            if (count($ratios)==$cntzero) {
                                $correct = false; continue;
                            } else {
                                $meanratio = array_sum($ratios)/count($ratios);
                                if (in_array('inequality',$ansformats)) {
                                    if ($meanratio > 0) {
                                        if ($answerInequality != $givenInequality) {
                                            $correct = false; continue;
                                        }
                                    } else {
                                        $flippedIneq = strtr($givenInequality, ['<'=>'>', '>'=>'<']);
                                        if ($answerInequality != $flippedIneq) {
                                            $correct = false; continue;
                                        }
                                    }
                                }
                                for ($i=0; $i<count($ratios); $i++) {
                                    if ($abstolerance !== '') {
                                        if (abs($ratios[$i]-$meanratio) > $abstolerance+1E-12) {$correct = false; break;}
                                    } else {
                                        if ((abs($ratios[$i]-$meanratio)/(abs($meanratio)+.0001) > $reltolerance+1E-12)) {$correct = false; break;}
                                    }
                                }
                            }
                        } else {
                            $correct = false;
                        }
                    } else if (in_array('toconst',$ansformats)) {
                        if ($abstolerance !== '') {
                            //if abs, use mean diff - will minimize error in abs diffs
                            $meandiff = array_sum($diffs)/count($diffs);
                        } else {
                            //if relative tol, use meandiff to minimize relative error
                            $meandiff = $reldifftot/$ysqrtot;
                        }
                        if (is_nan($meandiff)) {
                            $correct=false; continue;
                        }
                        for ($i=0; $i<count($diffs); $i++) {
                            if ($abstolerance !== '') {
                                if (abs($diffs[$i]-$meandiff) > $abstolerance+1E-12) {$correct = false; break;}
                            } else {
                                //if ((abs($diffs[$i]-$meandiff)/(abs($meandiff)+0.0001) > $reltolerance-1E-12)) {$correct = false; break;}
                                if ((abs($diffs[$i]-$meandiff)/(abs($realanss[$i])+0.0001) > $reltolerance+1E-12)) {$correct = false; break;}
                            }
                        }
                    }
                    if ($correct == true) {
                        //test for correct format, if specified
                        if ($thisreqtimes!='' && checkreqtimes(str_replace(',','',$givenanslist[$gaidx]),$thisreqtimes)==0) {
                            $rightanswrongformat = $ansidx;
                            continue;
                            //$correct = false;
                        }
                        if (in_array('sameform',$ansformats)) {
                            if ($answerfunc->normalizeTreeString() != $givenanslistnormalized[$gaidx]) {
                                $rightanswrongformat = $ansidx;
                                continue;
                            }
                        }
                        $correctscores[] = $partialpts[$ansidx];
                        $givenansused[$gaidx] = 1;
                        continue 3; // skip to next answer list entry
                    }
                }
            }
        }
    
        if ($isListAnswer) {
            $score = array_sum($correctscores)/count($answerlist);
            if (count($givenanslist) > count($answerlist)) {
                $score -= (count($givenanslist) - count($answerlist))/(count($givenanslist) + count($answerlist));
            }
            $scorePartResult->setRawScore($score);
            return $scorePartResult;
        } else if (count($correctscores) > 0) {
            $scorePartResult->setRawScore($correctscores[0]);
            return $scorePartResult;
        } else if ($rightanswrongformat!=-1 && !empty($formatfeedbackon)) {
            $scorePartResult->setCorrectAnswerWrongFormat(true);
        }

        $scorePartResult->setRawScore(0);
        return $scorePartResult;
    }
}

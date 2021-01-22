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

        if (is_array($options['answer'])) {$answer = $options['answer'][$partnum];} else {$answer = $options['answer'];}
        if (isset($options['reltolerance'])) {if (is_array($options['reltolerance'])) {$reltolerance = $options['reltolerance'][$partnum];} else {$reltolerance = $options['reltolerance'];}}
        if (isset($options['abstolerance'])) {if (is_array($options['abstolerance'])) {$abstolerance = $options['abstolerance'][$partnum];} else {$abstolerance = $options['abstolerance'];}}

        if (!isset($reltolerance) && !isset($abstolerance)) { $reltolerance = $defaultreltol;}
        if (is_array($options['variables'])) {$variables = $options['variables'][$partnum];} else {$variables = $options['variables'];}

        if (isset($options['domain'])) {if (is_array($options['domain'])) {$domain = $options['domain'][$partnum];} else {$domain= $options['domain'];}}
        if (isset($options['requiretimes'])) {if (is_array($options['requiretimes'])) {$requiretimes = $options['requiretimes'][$partnum];} else {$requiretimes = $options['requiretimes'];}}

        if (isset($options['requiretimes'])) {
            if (is_array($options['requiretimes'])) {
                if (is_array($options['requiretimes'][$partnum]) || $multi) {
                    $requiretimes = $options['requiretimes'][$partnum];
                } else {
                    $requiretimes = $options['requiretimes'];
                }
            } else {
                $requiretimes = $options['requiretimes'];
            }
        }

        if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$partnum];} else {$answerformat = $options['answerformat'];}}
        if (!isset($answerformat)) { $answerformat = '';}
        $ansformats = array_map('trim',explode(',',$answerformat));
        if (isset($options['ansprompt'])) {if (is_array($options['ansprompt'])) {$ansprompt = $options['ansprompt'][$partnum];} else {$ansprompt = $options['ansprompt'];}}
        if (isset($options['formatfeedbackon'])) {if (is_array($options['formatfeedbackon'])) {$formatfeedbackon = $options['formatfeedbackon'][$partnum];} else {$formatfeedbackon = $options['formatfeedbackon'];}}

        if (is_array($options['partialcredit'][$partnum]) || ($multi && is_array($options['partialcredit']))) {$partialcredit = $options['partialcredit'][$partnum];} else {$partialcredit = $options['partialcredit'];}

        if ($multi) { $qn = ($qn+1)*1000+$partnum; }

        $givenans = normalizemathunicode(trim($givenans));
        $answer = normalizemathunicode($answer);
        
        if (in_array('nosoln',$ansformats) || in_array('nosolninf',$ansformats)) {
            list($givenans, $answer) = scorenosolninf($qn, $givenans, $answer, $ansprompt);
        }

        $scorePartResult->setLastAnswerAsGiven($givenans);

        $correct = true;

        $givenans = preg_replace('/(\d)\s*,\s*(?=\d{3}(\D|\b))/','$1',$givenans);

        if (!isset($variables)) { $variables = "x";}
        $variables = array_map('trim',explode(",",$variables));
        $ofunc = array();
        for ($i = 0; $i < count($variables); $i++) {
            if ($variables[$i]=='lambda') { //correct lamda/lambda
                $givenans = str_replace('lamda', 'lambda', $givenans);
            }
            //find f() function variables
            if (strpos($variables[$i],'()')!==false) {
                $ofunc[] = substr($variables[$i],0,strpos($variables[$i],'('));
                $variables[$i] = substr($variables[$i],0,strpos($variables[$i],'('));
            }
            // front end will submit p_(left) rather than p_left; strip parens
            if (preg_match('/^(\w+)_(\w+)$/', $variables[$i], $m)) {
              $givenans = preg_replace('/'.$m[1].'_\('.$m[2].'\)/', $m[0], $givenans);
            }
        }

        if (isset($domain)) {
            $fromto = array_map('trim',explode(",",$domain));
        } else {
            $fromto = array(-10, 10);
        }
        if (count($fromto)==1) {
            $fromto = array(-10, 10);
        }
        $domaingroups = array();
        $i=0;
        while ($i<count($fromto)) {
            if (isset($fromto[$i+2]) && $fromto[$i+2]=='integers') {
                $domaingroups[] = array($fromto[$i], $fromto[$i+1], true);
                $i += 3;
            } else if (isset($fromto[$i+1])) {
                $domaingroups[] = array($fromto[$i], $fromto[$i+1], false);
                $i += 2;
            } else {
                break;
            }
        }

        uasort($variables,'lensort');
        $newdomain = array();
        $restrictvartoint = array();
        foreach($variables as $i=>$v) {
            if (isset($domaingroups[$i])) {
                $touse = $i;
            } else {
                $touse = 0;
            }
            $newdomain[] = $domaingroups[$touse][0];
            $newdomain[] = $domaingroups[$touse][1];
            $restrictvartoint[] = $domaingroups[$touse][2];
        }
        $fromto = $newdomain;
        $variables = array_values($variables);

        if (count($ofunc)>0) {
            usort($ofunc,'lensort');
            $flist = implode("|",$ofunc);
            $answer = preg_replace('/('.$flist.')\(/',"$1*sin($1+",$answer);
            $givenans = preg_replace('/('.$flist.')\(/',"$1*sin($1+",$givenans);
        }
        $vlist = implode(",",$variables);


        for($j=0; $j < count($variables); $j++) {
            if ($fromto[2*$j+1]==$fromto[2*$j]) {
                for ($i = 0; $i < 20; $i++) {
                    $tps[$i][$j] = $fromto[2*$j];
                } 
            } else if ($restrictvartoint[$j]) {
                if ($fromto[2*$j+1]-$fromto[2*$j] > 200) {
                    for ($i = 0; $i < 20; $i++) {
                        $tps[$i][$j] = rand($fromto[2*$j],$fromto[2*$j+1]);
                    }
                } else {
                    $allbetween = range($fromto[2*$j],$fromto[2*$j+1]);
                    shuffle($allbetween);
                    $n = count($allbetween);
                    for ($i = 0; $i < 20; $i++) {
                        $tps[$i][$j] = $allbetween[$i%$n];
                    }
                }
            } else {
                $dx = ($fromto[2*$j+1]-$fromto[2*$j])/20;
                for ($i = 0; $i < 20; $i++) {
                    $tps[$i][$j] = $fromto[2*$j] + $dx*$i + $dx*rand(1,499)/500.0;
                }
            }
        }
/*
    old code.  New code above distributes the points more evenly across the domain
        for ($i = 0; $i < 20; $i++) {
            for($j=0; $j < count($variables); $j++) {
                if ($fromto[2*$j+1]==$fromto[2*$j]) {
                    $tps[$i][$j] = $fromto[2*$j];
                } else if ($restrictvartoint[$j]) {
                    $tps[$i][$j] = rand($fromto[2*$j],$fromto[2*$j+1]);
                } else {
                    $tps[$i][$j] = $fromto[2*$j] + ($fromto[2*$j+1]-$fromto[2*$j])*rand(0,499)/500.0 + 0.001;
                }
            }
        }
*/
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
            (strpos($answer,'<')!==false || strpos($answer,'>')!==false)
         ) {
            echo 'Your $answer contains an inequality sign, but you do not have $answerformat="inequality" set. This question probably will not work right.';
        } else if (!in_array('equation',$ansformats) &&
          !in_array('inequality',$ansformats) &&
          strpos($answer,'=')!==false
        ) {
            echo 'Your $answer contains an equal sign, but you do not have $answerformat="equation" set. This question probably will not work right.';
        }

        //build values for student answer
        $givenansvals = array();
        if (in_array('equation',$ansformats)) {
            $toevalGivenans = preg_replace('/(.*)=(.*)/','$1-($2)',$givenans);
        } else if (in_array('inequality',$ansformats)) {
            if (preg_match('/(.*)(<=|>=|<|>)(.*)/', $givenans, $matches)) {
                $toevalGivenans = $matches[3] . '-(' . $matches[1] . ')';
                $givenInequality = $matches[2];
            } else {
                $scorePartResult->setRawScore(0);
                return $scorePartResult;
            }
        } else {
            $toevalGivenans = $givenans;
        }

        $givenansfunc = parseMathQuiet($toevalGivenans, $vlist);
        if ($givenansfunc === false) { //parse error
            $scorePartResult->setRawScore(0);
            return $scorePartResult;
        }
        for ($i = 0; $i < 20; $i++) {
            $varvals = array();
            for($j=0; $j < count($variables); $j++) {
                $varvals[$variables[$j]] = $tps[$i][$j];
            }
            $givenansvals[] = $givenansfunc->evaluateQuiet($varvals);
        }
        if (in_array('sameform',$ansformats)) {
            $givenansnormalized = $givenansfunc->normalizeTreeString();
        }

        $ansarr = array_map('trim',explode(' or ',$answer));
        $partialpts = array_fill(0, count($ansarr), 1);
        $origanscnt = count($ansarr);
        if (isset($partialcredit)) {
            if (!is_array($partialcredit)) {$partialcredit = explode(',',$partialcredit);}
            for ($i=0;$i<count($partialcredit);$i+=2) {
                if (!in_array($partialcredit[$i], $ansarr) || $partialcredit[$i+1]<1) {
                    $ansarr[] = $partialcredit[$i];
                    $partialpts[] = $partialcredit[$i+1];
                }
            }
        }

        $rightanswrongformat = -1;
        foreach ($ansarr as $ansidx=>$answer) {
            if (is_array($requiretimes)) {
                if ($ansidx<$origanscnt) {
                    $thisreqtimes = $requiretimes[0];
                } else {
                    $thisreqtimes = $requiretimes[$ansidx-$origanscnt+1];
                }
            } else {
                $thisreqtimes = $requiretimes;
            }
            $correct = true;
            $answer = preg_replace('/[^\w\*\/\+\=\-\(\)\[\]\{\}\,\.\^\$\!\s\'<>]+/','',$answer);

            if (in_array('equation',$ansformats)) {
                if (substr_count($givenans, '=')!=1) {
                    $scorePartResult->setRawScore(0);
                    return $scorePartResult;
                }
                $answer = preg_replace('/(.*)=(.*)/','$1-($2)',$answer);
            } else if (in_array('inequality',$ansformats)) {
                preg_match('/(.*)(<=|>=|<|>)(.*)/', $answer, $matches);
                $answer = $matches[3] . '-(' . $matches[1] . ')';
                $answerInequality = $matches[2];
            }
            if ($answer == '') {
                $scorePartResult->setRawScore(0);
                return $scorePartResult;
            }
            $origanswer = $answer;
            $answerfunc = parseMathQuiet(makepretty($answer), $vlist);
            if ($answerfunc === false) {  // parse error on $answer - can't do much
                $scorePartResult->setRawScore(0);
                return $scorePartResult;
            }

            $cntnan = 0;
            $cntzero = 0;
            $cntbothzero = 0;
            $stunan = 0;
            $ysqrtot = 0;
            $reldifftot = 0;
            $ratios = array();
      			$diffs = array();
      			$realanss = array();
            for ($i = 0; $i < 20; $i++) {
                $varvals = array();
                for($j=0; $j < count($variables); $j++) {
                    $varvals[$variables[$j]] = $tps[$i][$j];
                }
                $realans = $answerfunc->evaluateQuiet($varvals);
                //echo "$answer, real: $realans, my: {$myans[$i]},rel: ". (abs($myans[$i]-$realans)/abs($realans))  ."<br/>";
                if (isNaN($realans)) {$cntnan++; continue;} //avoid NaN problems
                if (in_array('equation',$ansformats) || in_array('inequality',$ansformats) || in_array('scalarmult',$ansformats)) {  //if equation, store ratios
                    if (isNaN($givenansvals[$i])) {
                        $stunan++;
                    } elseif (abs($realans)>.000001 && is_numeric($givenansvals[$i])) {
                        $ratios[] = $givenansvals[$i]/$realans;
                        if (abs($givenansvals[$i])<=.00000001 && $realans!=0) {
                            $cntzero++;
                        }
                    } else if (abs($realans)<=.000001 && is_numeric($givenansvals[$i]) && abs($givenansvals[$i])<=.00000001) {
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
                    } else if (isset($abstolerance)) {

                        if (abs($givenansvals[$i]-$realans) > $abstolerance-1E-12) {$correct = false; break;}
                    } else {
                        if ((abs($givenansvals[$i]-$realans)/(abs($realans)+.0001) > $reltolerance-1E-12)) {$correct = false; break;}
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
                            if (isset($abstolerance)) {
                                if (abs($ratios[$i]-$meanratio) > $abstolerance-1E-12) {$correct = false; break;}
                            } else {
                                if ((abs($ratios[$i]-$meanratio)/(abs($meanratio)+.0001) > $reltolerance-1E-12)) {$correct = false; break;}
                            }
                        }
                    }
                } else {
                    $correct = false;
                }
            } else if (in_array('toconst',$ansformats)) {
                if (isset($abstolerance)) {
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
                    if (isset($abstolerance)) {
                        if (abs($diffs[$i]-$meandiff) > $abstolerance-1E-12) {$correct = false; break;}
                    } else {
                        //if ((abs($diffs[$i]-$meandiff)/(abs($meandiff)+0.0001) > $reltolerance-1E-12)) {$correct = false; break;}
                        if ((abs($diffs[$i]-$meandiff)/(abs($realanss[$i])+0.0001) > $reltolerance-1E-12)) {$correct = false; break;}
                    }
                }
            }
            if ($correct == true) {
                //test for correct format, if specified
                if ($thisreqtimes!='' && checkreqtimes(str_replace(',','',$givenans),$thisreqtimes)==0) {
                    $rightanswrongformat = $ansidx;
                    continue;
                    //$correct = false;
                }
                if (in_array('sameform',$ansformats)) {
                    if ($answerfunc->normalizeTreeString() != $givenansnormalized) {
                        $rightanswrongformat = $ansidx;
                        continue;
                    }
                }
                $scorePartResult->setRawScore($partialpts[$ansidx]);
                return $scorePartResult;
            }
        }
        if ($rightanswrongformat!=-1 && !empty($formatfeedbackon)) {
            $scorePartResult->setCorrectAnswerWrongFormat(true);
        }

        $scorePartResult->setRawScore(0);
        return $scorePartResult;
    }
}

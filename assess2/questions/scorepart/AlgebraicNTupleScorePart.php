<?php

namespace IMathAS\assess2\questions\scorepart;

require_once __DIR__ . '/ScorePart.php';
require_once __DIR__ . '/../models/ScorePartResult.php';

use IMathAS\assess2\questions\models\ScorePartResult;
use IMathAS\assess2\questions\models\ScoreQuestionParams;

class AlgebraicNTupleScorePart implements ScorePart
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

        $RND = $this->scoreQuestionParams->getRandWrapper();
        $options = $this->scoreQuestionParams->getVarsForScorePart();
        $qn = $this->scoreQuestionParams->getQuestionNumber();
        $givenans = $this->scoreQuestionParams->getGivenAnswer();
        $multi = $this->scoreQuestionParams->getIsMultiPartQuestion();
        $partnum = $this->scoreQuestionParams->getQuestionPartNumber();
        $anstype = $this->scoreQuestionParams->getAnswerType();

        $defaultreltol = .0015;

        $optionkeys = ['answer', 'reltolerance', 'abstolerance', 
            'answerformat', 'requiretimes', 'requiretimeslistpart', 'ansprompt', 
            'scoremethod', 'partweights', 'domain', 'variables'];
        foreach ($optionkeys as $optionkey) {
            ${$optionkey} = getOptionVal($options, $optionkey, $multi, $partnum);
        }
        if ($reltolerance === '' && $abstolerance === '') { $reltolerance = $defaultreltol;}

        if ($multi) { $qn = ($qn+1)*1000+$partnum; }
        
        $givenans = normalizemathunicode($givenans);
        $givenans = str_replace(array('(:',':)','<<','>>'), array('<','>','<','>'), $givenans);
        $givenans = trim($givenans," ,");
        $answer = normalizemathunicode($answer);
        
        $ansformats = array_map('trim',explode(',',$answerformat));
        $checkSameform = (in_array('sameform',$ansformats));

        $answer = str_replace(' ','',$answer);
        if (!is_array($partweights) && $partweights !== '') {
            $partweights = array_map('trim',explode(',',$partweights));
        }

        if (in_array('nosoln',$ansformats) || in_array('nosolninf',$ansformats)) {
            list($givenans, $answer) = scorenosolninf($qn, $givenans, $answer, $ansprompt);
            if ($givenans === 'DNE' || $givenans === 'oo') {
              $_POST["qn$qn-val"] = $givenans;
            }
        }

        $scorePartResult->setLastAnswerAsGiven($givenans);

        $givenans = str_replace(' ','',$givenans);

        if ($answer=='DNE') {
            if (strtoupper($givenans)=='DNE') {
                $scorePartResult->setRawScore(1);
                return $scorePartResult;
            } else {
                $scorePartResult->setRawScore(0);
                return $scorePartResult;
            }
        } else if ($answer=='oo') {
            if ($givenans=='oo') {
                $scorePartResult->setRawScore(1);
                return $scorePartResult;
            } else {
                $scorePartResult->setRawScore(0);
                return $scorePartResult;
            }
        } else if (strtoupper($givenans)=='DNE' || $givenans=='oo') {
          $scorePartResult->setRawScore(0);
          return $scorePartResult;
        }

        // clean up givenans
        $givenans =  preg_replace_callback(
            '/(arcsinh|arccosh|arctanh|arcsin|arccos|arctan|arcsec|arccsc|arccot|root|sqrt|sign|sinh|cosh|tanh|sech|csch|coth|abs|sin|cos|tan|sec|csc|cot|exp|log|ln)[\(\[]/i',
            function($m) { return strtolower($m[0]); },
            $givenans
        );

        $gaarr = parseNtuple($givenans, false, false);
        
        if ($givenans == null) {
            $scorePartResult->setRawScore(0);
            return $scorePartResult;
        }

        if (empty($variables)) { $variables = "x";}
        list($variables, $tps, $flist) = numfuncGenerateTestpoints($variables, $domain);
        $vlist = implode(",",$variables);
        $isComplex = in_array('generalcomplex', $ansformats);

        //test for correct format, if specified
        if (checkreqtimes($givenans,$requiretimes)==0) {
            $scorePartResult->setRawScore(0);
            return $scorePartResult;
        }

        if ($checkSameform) {
            $normalizedGivenAnswer = $gaarr;
        }

        // check formats and generate test values
        foreach($gaarr as $i=>$chkme) {
            foreach ($chkme['vals'] as $k=>$chkval) {
                if ($chkval != 'oo' && $chkval != '-oo') {
                    $gafunc = parseMathQuiet($chkval, $vlist, [], $flist, true, $isComplex);
                    if ($gafunc === false) {
                        $gaarr[$i]['tvals'][$k] = false;
                        $normalizedGivenAnswer[$i]['vals'][$k] = '';
                        continue;
                    }
                    $gvals = [];
                    for ($c = 0; $c < 20; $c++) {
                        $varvals = array();
                        for($j=0; $j < count($variables); $j++) {
                            $varvals[$variables[$j]] = $tps[$c][$j];
                        }
                        $gvals[] = $gafunc->evaluateQuiet($varvals);
                    }
                    $gaarr[$i]['tvals'][$k] = $gvals;
                    // generate normalized trees for sameform check
                    if ($checkSameform) {
                        $normalizedGivenAnswer[$i]['vals'][$k] = $gafunc->normalizeTreeString();
                    }
                }
            }
        }
        
        if (!empty($requiretimeslistpart)) {
            if (checkreqtimes($chkme['lb'].implode(',', $chkme['vals']).$chkme['rb'],$requiretimeslistpart)==0) {
                unset($gaarr[$i]);
            }
        }
 
        if (!is_array($gaarr) || count($gaarr)==0) {
            $scorePartResult->setRawScore(0);
            return $scorePartResult;
        }

        $answer = makepretty($answer);
        // parse and evaluate the answer, capturing "or"s
        $anarr = parseNtuple($answer, true, false);
        if ($checkSameform) {
            $normalizedAnswer = $anarr;
        }
        foreach($anarr as $i=>$anors) {
            foreach ($anors as $oi=>$chkme) {
                foreach ($chkme['vals'] as $k=>$chkval) {
                    if ($chkval != 'oo' && $chkval != '-oo') {
                        // generate normalized trees for sameform check
                        $anfunc = parseMathQuiet($chkval, $vlist, [], $flist, true, $isComplex);
                        if ($anfunc === false) {
                            $anarr[$i][$oi]['tvals'][$k] = false;
                            $normalizedAnswer[$i][$oi]['vals'][$k] = '';
                        }
                        $anvals = [];
                        for ($c = 0; $c < 20; $c++) {
                            $varvals = array();
                            for($j=0; $j < count($variables); $j++) {
                                $varvals[$variables[$j]] = $tps[$c][$j];
                            }
                            $anvals[] = $anfunc->evaluateQuiet($varvals);
                        }
                        $anarr[$i][$oi]['tvals'][$k] = $anvals;
                        if ($checkSameform) {
                            $normalizedAnswer[$i][$oi]['vals'][$k] = $anfunc->normalizeTreeString();
                        }
                    }
                }
            }
        }
        
        $gaarrcnt = count($gaarr);
        $extrapennum = count($gaarr)+count($anarr);
        $correct = 0;
        $partialmatches = array();
        $matchedans = array();
        $matchedgivenans = array();

        foreach ($anarr as $ai=>$ansors) {
            $foundloc = -1;
            foreach ($ansors as $ao=>$answer) {  //each of the "or" options
                foreach ($gaarr as $j=>$givenans) {

                    if (isset($matchedgivenans[$j])) {continue;}

                    if ($answer['lb']!=$givenans['lb'] || $answer['rb']!=$givenans['rb']) {
                        continue;
                    }

                    if (count($answer['vals'])!=count($givenans['vals'])) {
                        continue;
                    }

                    $matchedparts = [];
                    foreach ($answer['vals'] as $i=>$ansval) {
                        $gansval = $givenans['vals'][$i];
                        if (($ansval==='oo' && $gansval==='oo') || ($ansval==='-oo' && $gansval==='-oo')) {
                            $matchedparts[$i] = 1;
                            //is ok
                            continue;
                        } 
                        $cntnan = 0;
                        $stunan = 0;
                        if ($answer['tvals'][$i] === false) {
                            continue; // invalid answer
                        }
                        if ($givenans['tvals'][$i] === false) {
                            // student answer didn't parse
                            continue;
                        }
                        foreach ($answer['tvals'][$i] as $tvi=>$ansval) {
                            if (isNaN($ansval)) {
                                $cntnan++; 
                                continue;
                            }
                            $stuval = $givenans['tvals'][$i][$tvi];
                            $vcorrect = true;
                            if ($isComplex) {
                                if (!is_array($stuval)) {
                                    $stunan++; 
                                } else if ($abstolerance !== '') {
                                    if (abs($stuval[0]-$ansval[0]) > $abstolerance+1E-12) { $vcorrect=false; break;}
                                    if (abs($stuval[1]-$ansval[1]) > $abstolerance+1E-12) { $vcorrect=false; break;}
                                } else {
                                    if ((abs($stuval[0]-$ansval[0])/(abs($ansval[0])+.0001) > $reltolerance+1E-12)) {$vcorrect=false; break;}
                                    if ((abs($stuval[1]-$ansval[1])/(abs($ansval[1])+.0001) > $reltolerance+1E-12)) {$vcorrect=false; break;}
                                }
                            } else {
                                if (isNaN($stuval)) {
                                    $stunan++; 
                                } else if ($abstolerance !== '') {
                                    if (abs($stuval-$ansval) > $abstolerance+1E-12) { $vcorrect=false; break;}
                                } else {
                                    if ((abs($stuval-$ansval)/(abs($ansval)+.0001) > $reltolerance+1E-12)) { $vcorrect=false; break;}
                                }
                            }
                        }
                        if ($cntnan==20 && isset($GLOBALS['teacherid'])) {
                            echo _('Debug info: function evaled to Not-a-number at all test points.  Check $domain');
                        }
                        if ($stunan>1) { //if more than 1 student NaN response
                            $vcorrect = false; continue;
                        }
                        if ($vcorrect) {
                            $matchedparts[$i] = 1;
                        }
                    }

                    if ($checkSameform && $normalizedAnswer[$ai][$ao] != $normalizedGivenAnswer[$j]) {
                        continue;
                    }

                    if (count($matchedparts)==count($answer['vals'])) { //if totally correct
                        $correct += 1; $foundloc = $j; break 2;
                    } else if ($scoremethod=='byelement' && count($matchedparts)>0) { //if partially correct
                        if (is_array($partweights)) {
                            $fraccorrect = 0;
                            foreach ($partweights as $pwi => $pwv) {
                                if (!empty($matchedparts[$pwi])) {
                                    $fraccorrect += $pwv;
                                }
                            }
                            $fraccorrect /= array_sum($partweights);
                        } else {
                            $fraccorrect = count($matchedparts)/count($answer['vals']);
                        }
                        if (!isset($partialmatches["$ai-$j"]) || $fraccorrect>$partialmatches["$ai-$j"]) {
                            $partialmatches["$ai-$j"] = $fraccorrect;
                        }
                    }
                }
            }
            if ($foundloc>-1) {
                //array_splice($gaarr,$foundloc,1); // remove from list
                $matchedgivenans[$foundloc] = 1;
                $matchedans[$ai] = 1;
                if (count($gaarr)==count($matchedgivenans)) {
                    break;
                }
            }
        }
        if ($scoremethod=='byelement') {
            arsort($partialmatches);
            foreach ($partialmatches as $k=>$v) {
                $kp = explode('-', $k);
                if (isset($matchedans[$kp[0]]) || isset($matchedgivenans[$kp[1]])) {
                    //already used this ans or stuans
                    continue;
                } else {
                    $correct += $v;
                    $matchedans[$kp[0]] = 1;
                    $matchedgivenans[$kp[1]] = 1;
                    if (count($gaarr)==count($matchedgivenans)) {
                        break;
                    }
                }
            }
        }
        if (count($anarr)==0) { // no answers
            if ($GLOBALS['myrights']>10) {
              echo _('Eeek: No valid $answer values provided');
            }
            $score = 0;
        } else if ($gaarrcnt<=count($anarr)) {
            $score = $correct/count($anarr);
        } else {
            $score = $correct/count($anarr) - ($gaarrcnt-count($anarr))/$extrapennum;  //take off points for extranous stu answers
        }

        //$score = $correct/count($anarr) - count($gaarr)/$extrapennum;
        if ($score<0) { $score = 0; }

        $scorePartResult->setRawScore($score);
        return $scorePartResult;
    }
}

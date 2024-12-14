<?php

namespace IMathAS\assess2\questions\scorepart;

require_once __DIR__ . '/ScorePart.php';
require_once __DIR__ . '/../models/ScorePartResult.php';

use IMathAS\assess2\questions\models\ScorePartResult;
use IMathAS\assess2\questions\models\ScoreQuestionParams;

class NTupleScorePart implements ScorePart
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
            'answerformat', 'requiretimes', 'requiretimeslistpart', 'ansprompt', 'scoremethod', 'partweights'];
        foreach ($optionkeys as $optionkey) {
            ${$optionkey} = getOptionVal($options, $optionkey, $multi, $partnum);
        }
        if ($reltolerance === '' && $abstolerance === '') { $reltolerance = $defaultreltol;}

        if ($multi) { $qn = ($qn+1)*1000+$partnum; }
          $hasNumVal = !empty($_POST["qn$qn-val"]);
        if ($hasNumVal) {
          $givenansval = $_POST["qn$qn-val"];
        }

        $givenans = normalizemathunicode($givenans);
        $givenans = str_replace(array('(:',':)','<<','>>'), array('<','>','<','>'), $givenans);
        $givenans = trim($givenans," ,");
        $answer = normalizemathunicode($answer);
        
        $ansformats = array_map('trim',explode(',',$answerformat));
        $checkSameform = (in_array('sameform',$ansformats));
        $isComplex = ($anstype === 'complexntuple' || $anstype === 'calccomplexntuple');

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

        if ($isComplex && in_array('allowjcomplex', $ansformats)) {
            $givenans = str_replace('j','i', $givenans);
            $answer = str_replace('j','i', $answer);
        }

        if ($anstype=='ntuple' || $anstype=='complexntuple') {
            $gaarr = parseNtuple($givenans, false, false, $isComplex);
        } else if ($anstype=='calcntuple' || $anstype=='calccomplexntuple') {
            // parse and evaluate
            if ($hasNumVal) {
                $gaarr = parseNtuple($givenansval, false, false, $isComplex, $ansformats);
                $scorePartResult->setLastAnswerAsNumber($givenansval);
            } else {
                $gaarr = parseNtuple($givenans, false, true, $isComplex, $ansformats);
                $scorePartResult->setLastAnswerAsNumber(ntupleToString($gaarr));
            }
        }
        if ($givenans == null) {
            $scorePartResult->setRawScore(0);
            return $scorePartResult;
        }

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

        // check formats for calcntuple
        if ($anstype=='calcntuple' || $anstype=='calccomplexntuple') {

            //test for correct format, if specified
            if (checkreqtimes($givenans,$requiretimes)==0) {
                $scorePartResult->setRawScore(0);
                return $scorePartResult;
            }

            //parse the ntuple without evaluating
            $tocheck = parseNtuple($givenans, false, false, $isComplex, $ansformats);
            if (!is_array($tocheck)) {
                $scorePartResult->setRawScore(0);
                return $scorePartResult;
            }
            if ($checkSameform) {
                $normalizedGivenAnswer = $tocheck;
            }

            if ($answer != 'DNE' && $answer != 'oo') {
                foreach($tocheck as $i=>$chkme) {
                    foreach ($chkme['vals'] as $k=>$chkval) {
                        if ($chkval != 'oo' && $chkval != '-oo') {
                            if ($isComplex) {
                                if (in_array('generalcomplex', $ansformats)) {
                                    // skip format checks
                                } else if (in_array('sloppycomplex', $ansformats)) {
                                    $chkval = str_replace(array('sin', 'pi'), array('s$n', 'p$'), $chkval);
                                    if (substr_count($chkval, 'i') > 1) {
                                        unset($gaarr[$i]);
                                        continue 2;
                                    }
                                    $chkval = str_replace(array('s$n', 'p$'), array('sin', 'pi'), $chkval);
                                } else if (!is_array($chkme['cvals'][$k]) || !checkanswerformat($chkme['cvals'][$k][0],$ansformats) || !checkanswerformat($chkme['cvals'][$k][1],$ansformats)) {
                                    // eliminate answer
                                    unset($gaarr[$i]);
                                    continue 2;
                                }
                            } else {
                                if (!checkanswerformat($chkval,$ansformats)) {
                                    // eliminate answer
                                    unset($gaarr[$i]);
                                    continue 2;
                                }
                            }
                            // generate normalized trees for sameform check
                            if ($checkSameform) {
                                $anfunc = parseMathQuiet($chkval, $isComplex?'i':'');
                                if ($anfunc === false) { // parse error
                                    $normalizedGivenAnswer[$i]['vals'][$k] = '';
                                } else {
                                    $normalizedGivenAnswer[$i]['vals'][$k] = $anfunc->normalizeTreeString();
                                }
                            }
                        }
                    }
                }
                if (!empty($requiretimeslistpart)) {
                    if (checkreqtimes($chkme['lb'].implode(',', $chkme['vals']).$chkme['rb'],$requiretimeslistpart)==0) {
                        unset($gaarr[$i]);
                    }
                }
            }
        }

        if (!is_array($gaarr) || count($gaarr)==0) {
            $scorePartResult->setRawScore(0);
            return $scorePartResult;
        }

        $answer = makepretty($answer);
        // parse and evaluate the answer, capturing "or"s
        $anarr = parseNtuple($answer, true, true, $isComplex, $ansformats);
        if ($checkSameform) {
            $normalizedAnswer = parseNtuple($answer, true, false, $isComplex, $ansformats);
            foreach($normalizedAnswer as $ai=>$chkme) {
                foreach ($chkme as $ao=>$aval) {
                    foreach ($aval['vals'] as $k=>$chkval) {
                        if ($chkval != 'oo' && $chkval != '-oo') {
                            $anfunc = parseMathQuiet($chkval, $isComplex?'i':'');
                            if ($anfunc === false) {
                                $normalizedAnswer[$ai][$ao]['vals'][$k] = '';
                            } else {
                                $normalizedAnswer[$ai][$ao]['vals'][$k] = $anfunc->normalizeTreeString();
                            }
                        }
                    }
                }
            }
        }

        // ensure values are numbers
        foreach ($gaarr as $k=>$givenans) {
            if ($isComplex) {
                foreach ($givenans['cvals'] as $v) {
                    if (!is_array($v) || !is_numeric($v[0]) || !is_numeric($v[1])) {
                        unset($gaarr[$k]);
                        continue 2;
                    }
                }
            } else {
                foreach ($givenans['vals'] as $v) {
                    if (!is_numeric($v)) {
                        unset($gaarr[$k]);
                        continue 2;
                    }
                }
            }
        }
        
        if (!$isComplex && in_array('anyorder', $ansformats)) {
            foreach ($anarr as $k=>$listans) {
                foreach ($listans as $ork=>$orv) {
                    sort($anarr[$k][$ork]['vals']);
                }
            }
            foreach ($gaarr as $k=>$givenans) {
                sort($gaarr[$k]['vals']);
            }
        }

        // TODO: extend this to work with isComplex
        if (!$isComplex && in_array('scalarmult',$ansformats)) {
            //normalize the vectors
            foreach ($anarr as $k=>$listans) {
                foreach ($listans as $ork=>$orv) {
                    $mag = sqrt(array_sum(array_map(function($x) {return $x*$x;}, $orv['vals'])));
                    foreach ($orv['vals'] as $j=>$v) {
                        if (abs($v)>1e-10) {
                            if ($v<0) {
                                $mag *= -1;
                            }
                            break;
                        }
                    }
                    if (abs($mag)>0) {
                        foreach ($orv['vals'] as $j=>$v) {
                            $anarr[$k][$ork]['vals'][$j] = $v/$mag;
                        }
                    }
                }
            }
            foreach ($gaarr as $k=>$givenans) {
                $mag = sqrt(array_sum(array_map(function($x) {return $x*$x;}, $givenans['vals'])));
                foreach ($givenans['vals'] as $j=>$v) {
                    if (abs($v)>1e-10) {
                        if ($v<0) {
                            $mag *= -1;
                        }
                        break;
                    }
                }
                if (abs($mag)>0) {
                    foreach ($givenans['vals'] as $j=>$v) {
                        $gaarr[$k]['vals'][$j] = $v/$mag;
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
                        if ($isComplex) {
                            $anscval = $answer['cvals'][$i];
                            $ganscval = $givenans['cvals'][$i];
                            if (is_array($ganscval) && is_numeric($anscval[0]) && is_numeric($anscval[1]) && is_numeric($ganscval[0]) && is_numeric($ganscval[1])) {
                                if ($abstolerance !== '') {
                                    if (abs($anscval[0]-$ganscval[0]) < $abstolerance + 1E-12 && abs($anscval[1]-$ganscval[1]) < $abstolerance + 1E-12) {
                                        $matchedparts[$i] = 1;
                                    }
                                } else {
                                    if (abs($anscval[0]-$ganscval[0])/(abs($anscval[0])+.0001) < $reltolerance+ 1E-12 && abs($anscval[1]-$ganscval[1])/(abs($anscval[1])+.0001) < $reltolerance+ 1E-12) {
                                        $matchedparts[$i] = 1;
                                    }
                                }
                            } else if (($anscval==='oo' && $ganscval==='oo') || ($anscval==='-oo' && $ganscval==='-oo')) {
                                $matchedparts[$i] = 1;
                                //is ok
                            }
                        } else {
                            $gansval = $givenans['vals'][$i];
                            if (is_numeric($ansval) && is_numeric($gansval)) {
                                if ($abstolerance !== '') {
                                    if (abs($ansval-$gansval) < $abstolerance + 1E-12) {
                                        $matchedparts[$i] = 1;
                                    }
                                } else {
                                    if (abs($ansval-$gansval)/(abs($ansval)+.0001) < $reltolerance+ 1E-12) {
                                        $matchedparts[$i] = 1;
                                    }
                                }
                            } else if (($ansval==='oo' && $gansval==='oo') || ($ansval==='-oo' && $gansval==='-oo')) {
                                $matchedparts[$i] = 1;
                                //is ok
                            }
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

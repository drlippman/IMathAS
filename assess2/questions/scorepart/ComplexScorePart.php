<?php

namespace IMathAS\assess2\questions\scorepart;

require_once __DIR__ . '/ScorePart.php';
require_once __DIR__ . '/../models/ScorePartResult.php';

use IMathAS\assess2\questions\models\ScorePartResult;
use IMathAS\assess2\questions\models\ScoreQuestionParams;

class ComplexScorePart implements ScorePart
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
            'answerformat', 'requiretimes', 'requiretimeslistpart', 'ansprompt'];
        foreach ($optionkeys as $optionkey) {
            ${$optionkey} = getOptionVal($options, $optionkey, $multi, $partnum);
        }
        if ($reltolerance === '' && $abstolerance === '') {$reltolerance = $defaultreltol;}

        if ($multi) {$qn = ($qn + 1) * 1000 + $partnum;}
        $hasNumVal = !empty($_POST["qn$qn-val"]);
        if ($hasNumVal) {
            $givenansval = $_POST["qn$qn-val"];
        }

        if (empty($answerformat)) {$answerformat = '';}
        $ansformats = array_map('trim', explode(',', $answerformat));
        $checkSameform = (in_array('sameform',$ansformats));

        if (in_array('nosoln', $ansformats) || in_array('nosolninf', $ansformats)) {
            list($givenans, $answer) = scorenosolninf($qn, $givenans, $answer, $ansprompt);
            if ($givenans === 'DNE' || $givenans === 'oo') {
                $_POST["qn$qn-val"] = $givenans;
            }
        }
        $givenans = normalizemathunicode($givenans);
        $givenans = trim($givenans," ,");
        $scorePartResult->setLastAnswerAsGiven($givenans);
        if ($anstype == 'calccomplex' && $hasNumVal) {
            $givenansval = trim($givenansval," ,");
            $scorePartResult->setLastAnswerAsNumber($givenansval);
        }
        if ($givenans == null) {
            $scorePartResult->setRawScore(0);
            return $scorePartResult;
        }
        $answer = str_replace(' ', '', $answer);
        $givenans = trim($givenans);

        if ($answer == 'DNE') {
            if (strtoupper($givenans) == 'DNE') {
                $scorePartResult->setRawScore(1);
                return $scorePartResult;
            } else {
                $scorePartResult->setRawScore(0);
                return $scorePartResult;
            }
        } else if ($answer == 'oo') {
            if ($givenans == 'oo') {
                $scorePartResult->setRawScore(1);
                return $scorePartResult;
            } else {
                $scorePartResult->setRawScore(0);
                return $scorePartResult;
            }
        } else if ($givenans == 'DNE' || $givenans == 'oo') {
            $scorePartResult->setRawScore(0);
            return $scorePartResult;
        }

        $gaarr = array_map('trim', explode(',', $givenans));

        if ($anstype == 'calccomplex') {
            //test for correct format, if specified
            if (($answer != 'DNE' && $answer != 'oo') && !empty($requiretimes) && checkreqtimes($givenans, $requiretimes) == 0) {
                $scorePartResult->setRawScore(0);
                return $scorePartResult;
            }

            // rewrite +-
            if (in_array('allowplusminus', $ansformats)) {
                if (!in_array('list', $ansformats)) {
                    $ansformats[] = 'list';
                }
                $answer = rewritePlusMinus($answer);
                $givenans = rewritePlusMinus($givenans);
                $gaarr = array_map('trim', explode(',', $givenans));    
            }

            $normalizedGivenAnswers = [];
            foreach ($gaarr as $i => $tchk) {
                if (in_array('allowjcomplex', $ansformats)) {
                    $tchk = str_replace('j','i', $tchk);
                }
                if (in_array('generalcomplex', $ansformats)) {
                    // skip format checks
                } else if (in_array('sloppycomplex', $ansformats)) {
                    $tchk = str_replace(array('sin', 'pi'), array('s$n', 'p$'), $tchk);
                    if (substr_count($tchk, 'i') > 1) {
                        $scorePartResult->setRawScore(0);
                        return $scorePartResult;
                    }
                    $tchk = str_replace(array('s$n', 'p$'), array('sin', 'pi'), $tchk);
                } else {
                    // TODO: rewrite using mathparser
                    $cpts = parsecomplex($tchk);

                    if (!is_array($cpts)) {
                        unset($gaarr[$i]);
                        continue;
                    }

                    //echo $cpts[0].','.$cpts[1].'<br/>';
                    if ($answer != 'DNE' && $answer != 'oo' && (!checkanswerformat($cpts[0], $ansformats) || !checkanswerformat($cpts[1], $ansformats))) {
                        //return 0;
                        unset($gaarr[$i]);
                    }
                    if ($answer != 'DNE' && $answer != 'oo' && !empty($requiretimeslistpart) && checkreqtimes($tchk, $requiretimeslistpart) == 0) {
                        //return 0;
                        unset($gaarr[$i]);
                    }
                }
                if ($checkSameform) {
                    $gafunc = parseMathQuiet($tchk, 'i');
                    if ($gafunc === false) {
                        $normalizedGivenAnswers[$i] = '';
                    } else {
                        $normalizedGivenAnswers[$i] = $gafunc->normalizeTreeString();
                    }
                }
            }
        } else { // if "complex"
            foreach ($gaarr as $i => $tchk) {
                if (in_array('allowjcomplex', $ansformats)) {
                    $tchk = str_replace('j','i', $tchk);
                }
                $cpts = parsecomplex($tchk);
                if (!is_array($cpts)) {
                    unset($gaarr[$i]);
                    continue;
                }
                if (!is_numeric($cpts[0]) || !is_numeric($cpts[1])) {
                    unset($gaarr[$i]);
                }
            }
        }

        $ganumarr = array();
        foreach ($gaarr as $j => $givenans) {
            if (in_array('allowjcomplex', $ansformats)) {
                $givenans = str_replace('j','i',$givenans);
            }
            if (in_array('generalcomplex', $ansformats)) {
                $gaparts = parseGeneralComplex($givenans);
            } else {
                $gaparts = parsesloppycomplex($givenans);
            }

            if ($gaparts === false) { //invalid - skip it
                continue;
            }

            if (!in_array('exactlist', $ansformats)) {
                // don't add if we already have it in the list
                foreach ($ganumarr as $prevvals) {
                    if (abs($gaparts[0] - $prevvals[0]) < 1e-12 && abs($gaparts[1] - $prevvals[1]) < 1e-12) {
                        continue 2; //skip adding it to the list
                    }
                }
            }
            $ganumarr[] = $gaparts;
        }
        if ($anstype == 'calccomplex' && !$hasNumVal) {
            $givenansval = [];
            foreach ($ganumarr as $ganumval) {
                $givenansval[] = complexarr2str($ganumval);
            }
            $givenansval = implode(',', $givenansval);
            $scorePartResult->setLastAnswerAsNumber($givenansval);
        }
        $answer = makepretty($answer);
        $anarr = array_map('trim', explode(',', $answer));
        $annumarr = array();
        $normalizedAnswers = [];
        foreach ($anarr as $i => $answer) {
            if (in_array('allowjcomplex', $ansformats)) {
                $answer = str_replace('j','i',$answer);
            }
            if (in_array('generalcomplex', $ansformats)) {
                $ansparts = parseGeneralComplex($answer);
            } else {
                $ansparts = parsesloppycomplex($answer);
            }
            if ($ansparts === false) { //invalid - skip it
                continue;
            }
            if (!in_array('exactlist', $ansformats)) {
                foreach ($annumarr as $prevvals) {
                    if (abs($ansparts[0] - $prevvals[0]) < 1e-12 && abs($ansparts[1] - $prevvals[1]) < 1e-12) {
                        continue 2; //skip adding it to the list
                    }
                }
            }
            $annumarr[] = $ansparts;
            if ($checkSameform) {
                $anfunc = parseMathQuiet($answer, 'i');
                $normalizedAnswers[] = $anfunc->normalizeTreeString();
            }
        }
        if (count($ganumarr) == 0) {
            $scorePartResult->setRawScore(0);
            return $scorePartResult;
        }
        $extrapennum = count($ganumarr) + count($annumarr);
        $gaarrcnt = count($ganumarr);
        $correct = 0;
        foreach ($annumarr as $ai => $ansparts) {
            $foundloc = -1;

            foreach ($ganumarr as $j => $gaparts) {
                if (count($ansparts) != count($gaparts)) {
                    break;
                }
                for ($i = 0; $i < count($ansparts); $i++) {
                    if (is_numeric($ansparts[$i]) && is_numeric($gaparts[$i])) {
                        if ($abstolerance !== '') {
                            if (abs($ansparts[$i] - $gaparts[$i]) >= $abstolerance + 1E-12) {break;}
                        } else {
                            if (abs($ansparts[$i] - $gaparts[$i]) / (abs($ansparts[$i]) + .0001) >= $reltolerance + 1E-12) {break;}
                        }
                    }
                }
                if ($checkSameform && $normalizedAnswers[$ai] != $normalizedGivenAnswers[$j]) {
                    break;
                }
                if ($i == count($ansparts)) {
                    $correct += 1;
                    $foundloc = $j;
                    break;
                }
            }
            if ($foundloc > -1) {
                array_splice($ganumarr, $foundloc, 1); // remove from list
                if (count($ganumarr) == 0) {
                    break;
                }
            }
        }
        if (count($annumarr) == 0) {
            $scorePartResult->setRawScore(0);
            return $scorePartResult;
        }
        //$score = $correct/count($annumarr) - count($ganumarr)/$extrapennum;

        if ($gaarrcnt <= count($annumarr)) {
            $score = $correct / count($annumarr);
        } else {
            $score = $correct / count($annumarr) - count($ganumarr) / $extrapennum; //take off points for extranous stu answers
        }
        if ($score < 0) {
            $scorePartResult->setRawScore(0);
        } else {
            $scorePartResult->setRawScore($score);
        }
        return $scorePartResult;
    }
}

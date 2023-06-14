<?php

namespace IMathAS\assess2\questions\scorepart;

require_once(__DIR__ . '/ScorePart.php');
require_once(__DIR__ . '/../models/ScorePartResult.php');

use IMathAS\assess2\questions\models\ScorePartResult;
use IMathAS\assess2\questions\models\ScoreQuestionParams;

class IntervalScorePart implements ScorePart
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

        $optionkeys = ['answer', 'reltolerance', 'abstolerance', 'answerformat',
            'requiretimes', 'variables', 'ansprompt', 'scoremethod'];
        foreach ($optionkeys as $optionkey) {
            ${$optionkey} = getOptionVal($options, $optionkey, $multi, $partnum);
        }
        if ($reltolerance === '' && $abstolerance === '') { $reltolerance = $defaultreltol;}

        if (empty($variables)) { $variables = 'x';}

        if ($multi) { $qn = ($qn+1)*1000+$partnum; }

        $ansformats = array_map('trim',explode(',',$answerformat));

        if ($anstype == 'interval') {
          $ansformats[] = 'decimal';
          $anstype = 'calcinterval';
        }

        $hasNumVal = !empty($_POST["qn$qn-val"]);
        if ($hasNumVal) {
          $givenansval = $_POST["qn$qn-val"];
        }

        $givenans = normalizemathunicode($givenans);
        $givenans = str_replace('cup', 'U', $givenans);
        $givenans = trim($givenans," ,");

        if (in_array('nosoln',$ansformats)) {
            list($givenans, $answer) = scorenosolninf($qn, $givenans, $answer, $ansprompt, in_array('inequality',$ansformats)?'inequality':'interval');
            if ($givenans === 'DNE' || $givenans === 'oo') {
              $_POST["qn$qn-val"] = $givenans;
            }
        }

        $ansformatsHasList = in_array('list',$ansformats);
        //$givenans = str_replace('u', 'U', $givenans);
        $givenans = preg_replace('/\bu\b/', 'U', $givenans);
        $scorePartResult->setLastAnswerAsGiven($givenans);
        if ($hasNumVal) {
            $givenansval = trim($givenansval," ,");
            $scorePartResult->setLastAnswerAsNumber($givenansval);
        }
        $formatErr = 0;
        $formatCnt = 0;
        if ($anstype == 'calcinterval') {
            //test for correct format, if specified
            if (checkreqtimes($givenans,$requiretimes)==0) {
                $scorePartResult->setRawScore(0);
                return $scorePartResult;
            }
            if (in_array('inequality',$ansformats)) {
                $givenans = str_replace('or', ' or ', $givenans);
                if (preg_match('/all\s*real/i', $givenans)) {
                    $givenans = '(-oo,oo)';
                } else if (preg_match('/empty\s*set/i', $givenans)) {
                    $givenans = 'DNE';
                } else {
                    if ($variables != 'var') {
                        $givenans = str_replace('var','',$givenans);
                    }
                    //for simplicity, we're going to replace correct $variables with var
                    $givenans = str_ireplace($variables, 'var', $givenans);

                    // now it'll be easier to see if there's something unintended
                    preg_match_all('/([a-zA-Z]+)/',$givenans,$matches);
                    foreach ($matches[0] as $var) {
                        if (in_array($var,$mathfuncs)) { continue;}
                        if ($var!= 'or' && $var!='and' && $var!='DNE' && $var!='oo' &&
                            strtolower($var) != 'var' && $var != 'pi' && $var != 'e' 
                            && $var != 'E') {
                            $scorePartResult->setRawScore(0);
                            return $scorePartResult;
                        }
                    }
                    $orarr = explode(' or ', $givenans);
                    foreach ($orarr as $k=>$opt) {
                        $opt = trim($opt);
                        if ($opt=='DNE' || $givenans=='(-oo,oo)') {continue;} //DNE or all real numbers
                        $opts = preg_split('/(<=?|>=?)/',$opt);
                        foreach ($opts as $optp) {
                            $optp = trim($optp);
                            if (strtolower($optp)=='var') {continue;}
                            $formatCnt++;
                            if ($optp=='oo' || $optp=='+oo' || $optp=='-oo') {continue;}
                            if (!checkanswerformat($optp,$ansformats)) {
                                $formatErr++;
                            }
                        }
                    }
                    // convert it to an interval for scoring
                    if (!$hasNumVal) {
                        $givenans = ineqtointerval($givenans, 'var');
                    }
                }
            } else {
                if ($ansformatsHasList) {
                    $orarr = preg_split('/(?<=[\)\]])\s*,\s*(?=[\(\[])/', $givenans);
                } else {
                    $orarr = explode('U', $givenans);
                }

                foreach ($orarr as $opt) {
                    $opt = trim($opt);
                    if ($opt=='DNE') {continue;}
                    $opts = explode(',',substr($opt,1,strlen($opt)-2));
                    $formatCnt += 2;
                    if (count($opts) != 2) {
                        $formatErr += 2;
                        continue;
                    }
                    if (strpos($opts[0],'oo')===false &&  !checkanswerformat($opts[0],$ansformats)) {
                        $formatErr++;
                    }
                    if (strpos($opts[1],'oo')===false &&  !checkanswerformat($opts[1],$ansformats)) {
                        $formatErr++;
                    }
                }
            }
        }

        if ($givenans == null) {
            $scorePartResult->setRawScore(0);
            return $scorePartResult;
        }
        $correct = 0;
        $ansar = explode(' or ',$answer);
        $givenans = str_replace(' ','',$givenans);

        if (in_array('allowsloppyintervals',$ansformats)) {
            require_once(__DIR__ . '/../../../assessment/libs/interval_ext.php');
            if ($hasNumVal) {
                $givenansval = canonicInterval($givenansval);
            } else {
                $givenans = canonicInterval($givenans);
            }
        }

        if ($hasNumVal) {
            $gaarr = parseInterval($givenansval, $ansformatsHasList);
        } else {
            $gaarr = parseInterval($givenans, $ansformatsHasList);
        }

        if ($anstype == 'calcinterval' && !$hasNumVal) {
            $scorePartResult->setLastAnswerAsNumber(
                parsedIntervalToString($gaarr, $ansformatsHasList));
        }
        if ($gaarr === false && $givenans!=='DNE') {
            $scorePartResult->setRawScore(0);
            return $scorePartResult;
        }

        $orScores = array(0);
        foreach($ansar as $anans) {
            $answer = str_replace(' ','',$anans);

            if ($anans==='DNE') {
                if ($givenans==='DNE') {
                    $correct = 1; break;
                } else {
                    continue;
                }
            } else if ($givenans==='DNE') {
                continue;
            }

            $aarr = parseInterval($anans, in_array('list',$ansformats));

            if ($aarr === false) {
                // uh oh
                $scorePartResult->setRawScore(0);
                return $scorePartResult;
            }

            $pairScored = array();
            foreach ($aarr as $j=>$ansint) {
                $pairScored[$j] = array();
                $foundloc = -1;
                foreach ($gaarr as $k=>$gansint) {
                    $pairScored[$j][$k] = 0;
                    // check brackets
                    if ($ansint['lb']==$gansint['lb']) {
                      $pairScored[$j][$k] += .2;
                    }
                    if ($ansint['rb']==$gansint['rb']) {
                        $pairScored[$j][$k] += .2;
                    }
                    list($anssn, $ansen) = $ansint['vals'];
                    list($ganssn, $gansen) = $gansint['vals'];
                    if (!is_numeric($anssn) || !is_numeric($ganssn)) {
                        if ($anssn === $ganssn) {
                            $pairScored[$j][$k] += .3;
                        }
                    } else if ($abstolerance !== '') {
                        if (abs($anssn-$ganssn) < $abstolerance + 1E-12) {
                          $pairScored[$j][$k] += .3;
                        }
                    } else {
                        if (abs($anssn - $ganssn)/(abs($anssn)+.0001) < $reltolerance+ 1E-12) {
                          $pairScored[$j][$k] += .3;
                        }
                    }
                    if (!is_numeric($ansen) || !is_numeric($gansen)) {
                        if ($ansen === $gansen) {
                            $pairScored[$j][$k] += .3;
                        }
                    } else if ($abstolerance !== '') {
                        if (abs($ansen-$gansen) < $abstolerance + 1E-12) {
                          $pairScored[$j][$k] += .3;
                        }
                    } else {
                        if (abs($ansen - $gansen)/(abs($ansen)+.0001) < $reltolerance+ 1E-12) {
                          $pairScored[$j][$k] += .3;
                        }
                    }
                }
            }
            for ($i=0;$i<count($pairScored);$i++) {
              arsort($pairScored[$i]);
            }
            uasort($pairScored, function($a,$b) {
              return (reset($a) < reset($b));
            });
            $thisScore = 0;
            $matchedGiven = array();
            foreach ($pairScored as $j=>$arr) { // foreach ans
              foreach ($arr as $k=>$v) { // look at pairwise score with given
                if ($v == 0) { break; }  // sorted, so if we hit 0 abort
                if (!in_array($k, $matchedGiven)) { // found a score, use it
                  $matchedGiven[] = $k;
                  $thisScore += $v;
                  break;
                }
              }
            }
            $thisScore /= count($pairScored);
            // take off points for extraneous student intervals
            $thisScore -= (count($gaarr) - count($matchedGiven))/count($pairScored);
            if ($thisScore == 1) {
              $correct = 1;
              break;
            } else {
              $orScores[] = $thisScore;
            }
        }
        if ($correct == 0) {
          $correct = max($orScores);
        }
        if ($formatErr > 0) {
          $correct = (1 - $formatErr/$formatCnt)*$correct;
        }
        if (empty($scoremethod) || $scoremethod !== 'partialcredit') {
          if ($correct < .999) {
            $correct = 0;
          }
        }
        $scorePartResult->setRawScore($correct);
        return $scorePartResult;
    }
}

<?php

namespace IMathAS\assess2\questions\scorepart;

require_once(__DIR__ . '/ScorePart.php');
require_once(__DIR__ . '/../models/ScorePartResult.php');
require_once(__DIR__ . '/matrix_common.php');

use IMathAS\assess2\questions\models\ScorePartResult;
use IMathAS\assess2\questions\models\ScoreQuestionParams;

class CalculatedMatrixScorePart implements ScorePart
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
        $isRescore = $this->scoreQuestionParams->getIsRescore();

        $defaultreltol = .0015;

        if (is_array($options['answer'])) {$answer = $options['answer'][$partnum];} else {$answer = $options['answer'];}
        if (isset($options['reltolerance'])) {if (is_array($options['reltolerance'])) {$reltolerance = $options['reltolerance'][$partnum];} else {$reltolerance = $options['reltolerance'];}}
        if (isset($options['abstolerance'])) {if (is_array($options['abstolerance'])) {$abstolerance = $options['abstolerance'][$partnum];} else {$abstolerance = $options['abstolerance'];}}
        if (!isset($reltolerance) && !isset($abstolerance)) { $reltolerance = $defaultreltol;}
        if (isset($options['answersize'])) {if (is_array($options['answersize'])) {$answersize = $options['answersize'][$partnum];} else {$answersize = $options['answersize'];}}
        if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$partnum];} else {$answerformat = $options['answerformat'];}}
        if (isset($options['scoremethod'])) {if (is_array($options['scoremethod'])) {$scoremethod = $options['scoremethod'][$partnum];} else {$scoremethod = $options['scoremethod'];}}
        if (!isset($scoremethod)) {	$scoremethod = 'whole';	}

        if ($multi) { $qn = ($qn+1)*1000+$partnum; }
        $hasNumVal = !empty($_POST["qn$qn-val"]);
        if ($hasNumVal) {
          $givenansval = $_POST["qn$qn-val"];
        }
        $givenans = normalizemathunicode($givenans);

        if (!isset($answerformat)) { $answerformat = '';}
        $ansformats = array_map('trim',explode(',',$answerformat));

        if (in_array('nosoln',$ansformats) || in_array('nosolninf',$ansformats)) {
            list($givenans, $answer) = scorenosolninf($qn, $givenans, $answer, $ansprompt);
            if ($givenans === 'DNE' || $givenans === 'oo') {
              $_POST["qn$qn-val"] = $givenans;
            }
        }
        //store answers
        if ($givenans==='oo' || $givenans==='DNE') {
            $scorePartResult->setLastAnswerAsGiven($givenans);
        } else if (isset($answersize)) {
            $sizeparts = explode(',',$answersize);
            $N = $sizeparts[0];
            $givenanslist = array();
            if ($hasNumVal) {
                $givenanslistvals = explode('|', $givenansval);
            } else {
                $givenanslistvals = array();
            }
            if ($isRescore) {
              $givenanslist = explode('|', $givenans);
              foreach ($givenanslist as $i=>$v) {
                $givenanslistvals[$i] = evalMathParser($v);
              }
            } else {
              for ($i=0; $i<$sizeparts[0]*$sizeparts[1]; $i++) {
                  $givenanslist[$i] = $_POST["qn$qn-$i"];
                  if (!$hasNumVal && $_POST["qn$qn-$i"] !== '') {
                      $givenanslistvals[$i] = evalMathParser($_POST["qn$qn-$i"]);
                  }
              }
            }
            $scorePartResult->setLastAnswerAsGiven(implode('|',$givenanslist));
            $scorePartResult->setLastAnswerAsNumber(implode('|',$givenanslistvals));
        } else {
            $givenans = preg_replace('/\)\s*,\s*\(/','),(', $givenans);
            $givenanslist = explode(',', str_replace('),(', ',', substr($givenans,2,-2)));
            if ($hasNumVal) {
                $givenanslistvals = explode('|', $givenansval);
            } else {
                foreach ($givenanslist as $j=>$v) {
                    $givenanslistvals[$j] = evalMathParser($v);
                }
            }
            $N = substr_count($answer,'),(')+1;
            //this may not be backwards compatible
            $scorePartResult->setLastAnswerAsGiven($givenans);
            $scorePartResult->setLastAnswerAsNumber(implode('|',$givenanslistvals));
        }

        //handle nosolninf case
        if ($givenans==='oo' || $givenans==='DNE') {
            if ($answer==$givenans) {
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

        $correct = true;
        $incorrect = array();

        $ansr = substr($answer,2,-2);
        $ansr = preg_replace('/\)\s*\,\s*\(/',',',$ansr);
        $answerlist = explode(',',$ansr);

        foreach ($answerlist as $k=>$v) {
            $answerlist[$k] = evalMathParser($v);
        }
        if (isset($answersize)) {
            for ($i=0; $i<count($answerlist); $i++) {
                if (!checkanswerformat($givenanslist[$i],$ansformats)) {
                    //perhaps should just elim bad answer rather than all?
                    if ($scoremethod == 'byelement') {
                      $incorrect[$i] = 1;
                    } else {
                      $scorePartResult->setRawScore(0);
                      return $scorePartResult;
                    }
                }
            }

        } else {
            if (substr_count($answer,'),(')!=substr_count($givenans,'),(')) {
                $correct = false;
            }
            $tocheck = str_replace(' ','', $givenans);
            $tocheck = str_replace(array('],[','),(','>,<'),',',$tocheck);
            $tocheck = substr($tocheck,2,-2);
            $tocheck = explode(',',$tocheck);
            foreach($tocheck as $i=>$chkme) {
                if (!checkanswerformat($chkme,$ansformats)) {
                    //perhaps should just elim bad answer rather than all?
                    if ($scoremethod == 'byelement') {
                      $incorrect[$i] = 1;
                    } else {
                      $scorePartResult->setRawScore(0);
                      return $scorePartResult;
                    }
                }
            }
        }

        if (count($answerlist) != count($givenanslist)) {
            $scorePartResult->setRawScore(0);
            return $scorePartResult;
        }

        if (in_array('scalarmult',$ansformats)) {
            //scale givenanslist to the magnitude of $answerlist
            $mag = sqrt(array_sum(array_map(function($x) {return $x*$x;}, $answerlist)));
            $mag2 = sqrt(array_sum(array_map(function($x) {return $x*$x;}, $givenanslistvals)));
            if ($mag > 0 && $mag2 > 0) {
                foreach ($answerlist as $j=>$v) {
                    if (abs($v)>1e-10) {
                        if ($answerlist[$j]*$givenanslistvals[$j]<0) {
                            $mag *= -1;
                        }
                        break;
                    }
                }
                foreach ($givenanslistvals as $j=>$v) {
                    $givenanslistvals[$j] = $mag/$mag2*$v;
                }
            }
        }

        if (in_array('ref',$ansformats)) {
            // reduce correct answer to rref
            $answerlist = matrix_scorer_rref($answerlist, $N);
            $M = count($answerlist) / $N;
            for ($r=0;$r<$N;$r++) {
              $c = 0;
              while (abs($answerlist[$r*$M+$c]) < 1e-10 && $c < $M) {
                if (abs($givenanslistvals[$r*$M+$c]) > 1e-10) {
                  $correct = false; // nonzero where 0 expected
                }
                $c++;
              }
              if ($c < $M) { // if there's a first non-zero entry, should be 1
                if (abs($givenanslistvals[$r*$M+$c] - 1) > 1e-10) {
                  $correct = false;
                }
              }
            }
            // now reduce given answer to rref
            if ($correct) {
              $givenanslistvals = matrix_scorer_rref($givenanslistvals, $N);
            }
        }
        for ($i=0; $i<count($answerlist); $i++) {
            if (!isset($givenanslistvals[$i]) || isNaN($givenanslistvals[$i])) {
                $incorrect[$i] = 1;
                continue;
            } else if (isset($abstolerance)) {
                if (abs($answerlist[$i] - $givenanslistvals[$i]) > $abstolerance+1E-12) {
                    $incorrect[$i] = 1;
                    continue;
                }
            } else {
                if (abs($answerlist[$i] - $givenanslistvals[$i])/(abs($answerlist[$i])+.0001) > $reltolerance+1E-12) {
                    $incorrect[$i] = 1;
                    continue;
                }
            }
        }
        if ($correct && count($incorrect)==0) {
            $scorePartResult->setRawScore(1);
            return $scorePartResult;
        } else if ($correct && $scoremethod == 'byelement') {
            $score = (count($answerlist) - count($incorrect))/count($answerlist);
            $scorePartResult->setRawScore($score);
            return $scorePartResult;
        } else {
            $scorePartResult->setRawScore(0);
            return $scorePartResult;
        }
    }
}

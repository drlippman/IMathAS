<?php

namespace IMathAS\assess2\questions\scorepart;

require_once __DIR__ . '/ScorePart.php';
require_once __DIR__ . '/../models/ScorePartResult.php';
require_once __DIR__ . '/matrix_common.php';

use IMathAS\assess2\questions\models\ScorePartResult;
use IMathAS\assess2\questions\models\ScoreQuestionParams;

class NumMatrixScorePart implements ScorePart
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

        $anstype = $this->scoreQuestionParams->getAnswerType();
        $RND = $this->scoreQuestionParams->getRandWrapper();
        $options = $this->scoreQuestionParams->getVarsForScorePart();
        $qn = $this->scoreQuestionParams->getQuestionNumber();
        $givenans = $this->scoreQuestionParams->getGivenAnswer();
        $multi = $this->scoreQuestionParams->getIsMultiPartQuestion();
        $partnum = $this->scoreQuestionParams->getQuestionPartNumber();
        $isRescore = $this->scoreQuestionParams->getIsRescore();

        $defaultreltol = .0015;

        $optionkeys = ['answer', 'reltolerance', 'abstolerance', 'answerformat',
            'answersize', 'scoremethod', 'ansprompt'];
        foreach ($optionkeys as $optionkey) {
            ${$optionkey} = getOptionVal($options, $optionkey, $multi, $partnum);
        }
        if ($reltolerance === '' && $abstolerance === '') { $reltolerance = $defaultreltol;}

        if ($multi) { $qn = ($qn+1)*1000+$partnum; }
        if ($anstype === 'calcmatrix') {
            $hasNumVal = !empty($_POST["qn$qn-val"]);
            if ($hasNumVal) {
                $givenansval = $_POST["qn$qn-val"];
            }
        }
        $givenans = normalizemathunicode($givenans);

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
        } else if (!empty($answersize)) {
            $sizeparts = explode(',',$answersize);
            $N = $sizeparts[0];
            $givenanslist = array();
            if ($anstype === 'calcmatrix' && $hasNumVal) {
                $givenanslistvals = explode('|', $givenansval);
            } else {
                $givenanslistvals = array();
            }
            if ($isRescore) {
              $givenanslist = explode('|', $givenans);
              if ($anstype === 'calcmatrix') {
                foreach ($givenanslist as $i=>$v) {
                        $givenanslistvals[$i] = evalMathParser($v);
                }
              }
            } else {
              for ($i=0; $i<$sizeparts[0]*$sizeparts[1]; $i++) {
                  $givenanslist[$i] = $_POST["qn$qn-$i"];
                  if ($anstype === 'calcmatrix' && !$hasNumVal && $_POST["qn$qn-$i"] !== '') {
                      $givenanslistvals[$i] = evalMathParser($_POST["qn$qn-$i"]);
                  }
              }
            }
            $scorePartResult->setLastAnswerAsGiven(implode('|',$givenanslist));
            if ($anstype === 'calcmatrix') {
                $scorePartResult->setLastAnswerAsNumber(implode('|',$givenanslistvals));
            } 
        } else {
            list($givenanslist, $N) = parseMatrixToArray($givenans);
    
            //this may not be backwards compatible
            $scorePartResult->setLastAnswerAsGiven($givenans);
            if ($givenanslist === false) { // invalid answer
                $scorePartResult->setRawScore(0);
                return $scorePartResult;
            }

            if ($anstype === 'calcmatrix') {
                if ($hasNumVal) {
                    $givenanslistvals = explode('|', $givenansval);
                } else {
                    $givenanslistvals = [];
                    foreach ($givenanslist as $j=>$v) {
                        $givenanslistvals[$j] = evalMathParser($v);
                    }
                }

                //this may not be backwards compatible
                $scorePartResult->setLastAnswerAsNumber(implode('|',$givenanslistvals));
            }
        }
        
        list($answerlist, $ansN) = parseMatrixToArray($answer);

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

        foreach ($answerlist as $k=>$v) {
            $v = evalMathParser($v);
            if (is_nan($v)) {
                if (isset($GLOBALS['teacherid'])) {
                    echo _('Debug info: invalid $answer');
                }
                $answerlist[$k] = 0;
            } else if ($anstype === 'calcmatrix') {
                $answerlist[$k] = $v;
            } else { // 'matrix'
                $answerlist[$k] = preg_replace('/[^\d\.,\-E]/','',$v);
            }
        }

        // Check formats
        if ($anstype === 'calcmatrix') {
            if (!empty($answersize)) {
                for ($i=0; $i<count($answerlist); $i++) {
                    if (isset($givenanslist[$i]) && !checkanswerformat($givenanslist[$i],$ansformats)) {
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
                if ($N != $ansN) {
                    $correct = false;
                }
                foreach ($givenanslist as $i=>$chkme) {
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
        } else {
            //matrix: values are already numeric
            $givenanslistvals = $givenanslist;
        }

        if (count($answerlist) != count($givenanslist) || $answerlist[0]==='' || $givenanslist[0]==='') {
            $scorePartResult->setRawScore(0);
            return $scorePartResult;
        }

        if ($anstype === 'calcmatrix') {
            $fullmatrix = !in_array("",  $givenanslist, true) && !in_array("NaN",  $givenanslistvals, true);
        } else {
            $fullmatrix = true;
            foreach ($givenanslist as $j=>$v) {
                if (!is_numeric($v)) {
                    $fullmatrix = false;
                }
            }
        }

        if ($fullmatrix && in_array('scalarmult',$ansformats)) {
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

        if ($fullmatrix && (in_array('ref',$ansformats) || in_array('rowequiv',$ansformats))) {
            // reduce correct answer to rref
            $answerlist = matrix_scorer_rref($answerlist, $N);
            $M = count($answerlist) / $N;
            if (in_array('ref',$ansformats)) {
                for ($r=0;$r<$N;$r++) {
                $c = 0;
                while (abs($answerlist[$r*$M+$c]) < 1e-10 && $c < $M) {
                    if (abs($givenanslistvals[$r*$M+$c]) > 1e-10) {
                    $correct = false; // nonzero where 0 expected
                    }
                    $c++;
                }
                /* Removed: Not all ref defs include leading 1's
                if ($c < $M) { // if there's a first non-zero entry, should be 1
                    if (abs($givenanslistvals[$r*$M+$c] - 1) > 1e-10) {
                    $correct = false;
                    }
                }
                */
                }
            }
            // now reduce given answer to rref
            if ($correct) {
              $givenanslistvals = matrix_scorer_rref($givenanslistvals, $N);
            }
        } else if ($fullmatrix && in_array('anyroworder',$ansformats)) {
            $answerlist = matrix_scorer_roworder($answerlist, $N);
            $givenanslistvals = matrix_scorer_roworder($givenanslistvals, $N);
        }
        for ($i=0; $i<count($answerlist); $i++) {
            if (!isset($givenanslistvals[$i]) || isNaN($givenanslistvals[$i])) {
                $incorrect[$i] = 1;
                continue;
            } else if ($abstolerance !== '') {
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

<?php

namespace IMathAS\assess2\questions\scorepart;

require_once(__DIR__ . '/ScorePart.php');
require_once(__DIR__ . '/../models/ScorePartResult.php');

use IMathAS\assess2\questions\models\ScorePartResult;
use IMathAS\assess2\questions\models\ScoreQuestionParams;

class MatrixScorePart implements ScorePart
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

        $defaultreltol = .0015;

        if (is_array($options['answer']) && isset($options['answer'][$partnum])) {$answer = $options['answer'][$partnum];} else {$answer = $options['answer'];}
        if (isset($options['reltolerance'])) {if (is_array($options['reltolerance'])) {$reltolerance = $options['reltolerance'][$partnum];} else {$reltolerance = $options['reltolerance'];}}
        if (isset($options['abstolerance'])) {if (is_array($options['abstolerance'])) {$abstolerance = $options['abstolerance'][$partnum];} else {$abstolerance = $options['abstolerance'];}}
        if (!isset($reltolerance) && !isset($abstolerance)) { $reltolerance = $defaultreltol;}
        if (isset($options['answersize'])) {if (is_array($options['answersize'])) {$answersize = $options['answersize'][$partnum];} else {$answersize = $options['answersize'];}}
        if (isset($options['answerformat'])) {if (is_array($options['answerformat'])) {$answerformat = $options['answerformat'][$partnum];} else {$answerformat = $options['answerformat'];}}
        if (!isset($answerformat)) { $answerformat = '';}
        if (isset($options['ansprompt'])) {if (is_array($options['ansprompt'])) {$ansprompt = $options['ansprompt'][$partnum];} else {$ansprompt = $options['ansprompt'];}}
        $ansformats = array_map('trim',explode(',',$answerformat));

        if ($multi) { $qn = ($qn+1)*1000+$partnum; }
        $correct = true;

        if (in_array('nosoln',$ansformats) || in_array('nosolninf',$ansformats)) {
            list($givenans, $answer) = scorenosolninf($qn, $givenans, $answer, $ansprompt);
        }

        if ($givenans==='oo' || $givenans==='DNE') {
            $scorePartResult->setLastAnswerAsGiven($givenans);
        } else if (isset($answersize)) {
            $sizeparts = explode(',',$answersize);
            for ($i=0; $i<$sizeparts[0]*$sizeparts[1]; $i++) {
                $givenanslist[$i] = $_POST["qn$qn-$i"];
            }
            $N = $sizeparts[0];
            $scorePartResult->setLastAnswerAsGiven(implode("|",$givenanslist));
        } else {
            $givenans = preg_replace('/\)\s*,\s*\(/','),(',$givenans);
            $scorePartResult->setLastAnswerAsGiven($givenans);
            $givenanslist = explode(",",preg_replace('/[^\d,\.\-]/','',$givenans));
            $N = substr_count($answer,'),(')+1;
            if ($N != substr_count($_POST["qn$qn"],'),(')+1) {
              $scorePartResult->setRawScore(0);
              return $scorePartResult;
            }
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
        foreach ($givenanslist as $j=>$v) {
            if (!is_numeric($v)) {
                $scorePartResult->setRawScore(0);
                return $scorePartResult;
            }
        }

        $ansr = substr($answer,2,-2);
        $ansr = preg_replace('/\)\s*\,\s*\(/',',',$ansr);
        $answerlist = explode(',',$ansr);

        if (count($answerlist) != count($givenanslist)) {
            $scorePartResult->setRawScore(0);
            return $scorePartResult;
        }
        foreach ($answerlist as $k=>$v) {
            $v = evalMathParser($v);
            $answerlist[$k] = preg_replace('/[^\d\.,\-E]/','',$v);
        }

        if (in_array('scalarmult',$ansformats)) {
            //scale givenanslist to the magnitude of $answerlist
            $mag = sqrt(array_sum(array_map(function($x) {return $x*$x;}, $answerlist)));
            $mag2 = sqrt(array_sum(array_map(function($x) {return $x*$x;}, $givenanslist)));
            if ($mag > 0 && $mag2 > 0) {
                foreach ($answerlist as $j=>$v) {
                    if (abs($v)>1e-10) {
                        if ($answerlist[$j]*$givenanslist[$j]<0) {
                            $mag *= -1;
                        }
                        break;
                    }
                }
                foreach ($givenanslist as $j=>$v) {
                    $givenanslist[$j] = $mag/$mag2*$v;
                }
            }
        }

        if (in_array('ref',$ansformats)) {
          // reduce correct answer to rref
          $answerlist = $this->rref($answerlist, $N);
          $M = count($answerlist) / $N;
          for ($r=0;$r<$N;$r++) {
            $c = 0;
            while (abs($answerlist[$r*$M+$c]) < 1e-10 && $c < $M) {
              if (abs($givenanslist[$r*$M+$c]) > 1e-10) {
                $correct = false; // nonzero where 0 expected
              }
              $c++;
            }
            if ($c < $M) { // if there's a first non-zero entry, should be 1
              if (abs($givenanslist[$r*$M+$c] - 1) > 1e-10) {
                $correct = false;
              }
            }
          }
          // now reduce given answer to rref
          if ($correct) {
            $givenanslist = $this->rref($givenanslist, $N);
          }
        }

        for ($i=0; $i<count($answerlist); $i++) {
            if (!is_numeric($givenanslist[$i])) {
                $correct = false;
                break;
            } else if (isset($abstolerance)) {
                if (abs($answerlist[$i] - $givenanslist[$i]) > $abstolerance-1E-12) {
                    $correct = false;
                    break;
                }
            } else {
                if (abs($answerlist[$i] - $givenanslist[$i])/(abs($answerlist[$i])+.0001) > $reltolerance-1E-12) {
                    $correct = false;
                    break;
                }

            }
        }

        if ($correct) {
            $scorePartResult->setRawScore(1);
            return $scorePartResult;
        } else {
            $scorePartResult->setRawScore(0);
            return $scorePartResult;
        }
    }

    /*
      Row reduce a matrix specified by the array of values $A with $N rows
     */
    private function rref($A, $N)
    {
      $M = count($A) / $N;
      $r = 0;  $c = 0;
      while ($r < $N && $c < $M) {
        if ($A[$r*$M+$c] == 0) { //swap only if there's a 0 entry
          $max = $r;
          for ($i = $r+1; $i < $N; $i++) {
            if (abs($A[$i*$M+$c]) > abs($A[$max*$M+$c])) {
              $max = $i;
            }
          }
          if ($max != $r) { // swap rows
            for ($j=0; $j<$M; $j++) {
              $temp = $A[$r*$M+$j];
              $A[$r*$M+$j] = $A[$max*$M+$j];
              $A[$max*$M+$j] = $temp;
            }
          }
        }
        if (abs($A[$r*$M+$c]) < 1e-10) {
          $c++;
          continue;
        }
        //scale pivot row
        $div = $A[$r*$M+$c];
        for ($j = $c; $j < $M; $j++) {
          $A[$r*$M+$j] = $A[$r*$M+$j] / $div;
        }

        //get zeros above/below
        for ($i = 0; $i < $N; $i++) {
          if ($i == $r) { continue;}
          $mult = $A[$i*$M+$c];
          if ($mult == 0) { continue; }
          for ($j = $c; $j < $M; $j++) {
            $A[$i*$M+$j] = $A[$i*$M+$j] - $mult*$A[$r*$M+$j];
          }
        }
        $r++; $c++;
    }
    return $A;
  }
}

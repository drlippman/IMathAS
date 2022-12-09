<?php

namespace IMathAS\assess2\questions\scorepart;

require_once(__DIR__ . '/ScorePart.php');
require_once(__DIR__ . '/../models/ScorePartResult.php');
require_once(__DIR__ . '/matrix_common.php');

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
        $isRescore = $this->scoreQuestionParams->getIsRescore();

        $defaultreltol = .0015;

        $optionkeys = ['answer', 'reltolerance', 'abstolerance', 'answerformat',
            'answersize', 'answerformat', 'ansprompt', 'scoremethod'];
        foreach ($optionkeys as $optionkey) {
            ${$optionkey} = getOptionVal($options, $optionkey, $multi, $partnum);
        }
        if ($reltolerance === '' && $abstolerance === '') { $reltolerance = $defaultreltol;}

        $ansformats = array_map('trim',explode(',',$answerformat));

        if ($multi) { $qn = ($qn+1)*1000+$partnum; }
        $correct = true;

        $givenans = normalizemathunicode($givenans);

        if (in_array('nosoln',$ansformats) || in_array('nosolninf',$ansformats)) {
            list($givenans, $answer) = scorenosolninf($qn, $givenans, $answer, $ansprompt);
        }

        if ($givenans==='oo' || $givenans==='DNE') {
            $scorePartResult->setLastAnswerAsGiven($givenans);
        } else if (!empty($answersize)) {
            $sizeparts = explode(',',$answersize);
            $N = $sizeparts[0];
            if ($isRescore) {
              $givenanslist = explode('|', $givenans);
            } else {
              for ($i=0; $i<$sizeparts[0]*$sizeparts[1]; $i++) {
                  $givenanslist[$i] = $_POST["qn$qn-$i"];
              }
            }
            $scorePartResult->setLastAnswerAsGiven(implode("|",$givenanslist));
        } else {
            $givenans = preg_replace('/\)\s*,\s*\(/','),(',$givenans);
            $scorePartResult->setLastAnswerAsGiven($givenans);
            $givenanslist = explode(",",preg_replace('/[^\d,\.\-]/','',$givenans));
            $N = substr_count($answer,'),(')+1;
            if ($N != substr_count($givenans, '),(') + 1) {
              $scorePartResult->setRawScore(0);
              return $scorePartResult;
            }
        }
        $answer = preg_replace('/\)\s*,\s*\(/','),(',$answer);

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
        $fullmatrix = true;
        foreach ($givenanslist as $j=>$v) {
            if (!is_numeric($v)) {
                $fullmatrix = false;
            }
        }

        $ansr = substr($answer,2,-2);
        $ansr = preg_replace('/\)\s*\,\s*\(/',',',$ansr);
        $answerlist = explode(',',$ansr);

        if (count($answerlist) != count($givenanslist) || $answerlist[0]==='' || $givenanslist[0]==='') {
            $scorePartResult->setRawScore(0);
            return $scorePartResult;
        }
        foreach ($answerlist as $k=>$v) {
            $v = evalMathParser($v);
            $answerlist[$k] = preg_replace('/[^\d\.,\-E]/','',$v);
        }

        if ($fullmatrix && in_array('scalarmult',$ansformats)) {
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

        if ($fullmatrix && in_array('ref',$ansformats)) {
          // reduce correct answer to rref
          $answerlist = matrix_scorer_rref($answerlist, $N);
          $M = count($answerlist) / $N;
          for ($r=0;$r<$N;$r++) {
            $c = 0;
            while (abs($answerlist[$r*$M+$c]) < 1e-10 && $c < $M) {
              if (abs($givenanslist[$r*$M+$c]) > 1e-10) {
                $correct = false; // nonzero where 0 expected
              }
              $c++;
            }
            /* Removed: Not all ref defs include leading 1's
            if ($c < $M) { // if there's a first non-zero entry, should be 1
              if (abs($givenanslist[$r*$M+$c] - 1) > 1e-10) {
                $correct = false;
              }
            }
            */
          }
          // now reduce given answer to rref
          if ($correct) {
            $givenanslist = matrix_scorer_rref($givenanslist, $N);
          }
        } else if ($fullmatrix && in_array('anyroworder',$ansformats)) {
            $answerlist = matrix_scorer_roworder($answerlist, $N);
            $givenanslist = matrix_scorer_roworder($givenanslist, $N);
        }

        $incorrect = 0;
        for ($i=0; $i<count($answerlist); $i++) {
            if (!is_numeric($givenanslist[$i])) {
                $incorrect++;
                continue;
            } else if ($abstolerance !== '') {
                if (abs($answerlist[$i] - $givenanslist[$i]) > $abstolerance+1E-12) {
                  $incorrect++;
                  continue;
                }
            } else {
                if (abs($answerlist[$i] - $givenanslist[$i])/(abs($answerlist[$i])+.0001) > $reltolerance+1E-12) {
                  $incorrect++;
                  continue;
                }
            }
        }

        if ($correct && $incorrect == 0) {
            $scorePartResult->setRawScore(1);
            return $scorePartResult;
        } else if ($correct && $scoremethod == 'byelement') {
          $score = (count($answerlist) - $incorrect)/count($answerlist);
          $scorePartResult->setRawScore($score);
          return $scorePartResult;
        } else {
            $scorePartResult->setRawScore(0);
            return $scorePartResult;
        }
    }
}

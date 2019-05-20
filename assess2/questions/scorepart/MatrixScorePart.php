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
            $scorePartResult->setLastAnswerAsGiven(implode("|",$givenanslist));
        } else {
            $givenans = preg_replace('/\)\s*,\s*\(/','),(',$givenans);
            $scorePartResult->setLastAnswerAsGiven($givenans);
            $givenanslist = explode(",",preg_replace('/[^\d,\.\-]/','',$givenans));
            if (substr_count($answer,'),(')!=substr_count($_POST["qn$qn"],'),(')) {$correct = false;}
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
}

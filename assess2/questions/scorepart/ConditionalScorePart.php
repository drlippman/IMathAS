<?php

namespace IMathAS\assess2\questions\scorepart;

require_once(__DIR__ . '/ScorePart.php');
require_once(__DIR__ . '/../models/ScorePartResult.php');

use IMathAS\assess2\questions\models\ScorePartResult;
use IMathAS\assess2\questions\models\ScoreQuestionParams;

class ConditionalScorePart implements ScorePart
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

        $answer = $options['answer'] ?? false;
        if (isset($options['abstolerance'])) {$abstolerance = $options['abstolerance'];}
        if (isset($options['reltolerance'])) {$reltolerance = $options['reltolerance'];}
        if (!isset($reltolerance) && !isset($abstolerance)) { $reltolerance = $defaultreltol;}
        if (isset($options['domain'])) {$domain = $options['domain'];} else { $domain = "-10,10";}
        if (isset($options['variables'])) {$variables = $options['variables'];} else { $variables = "x";}
        $anstypes = $options['anstypes'];
        if (!is_array($anstypes)) {
            $anstypes = array_map('trim',explode(',',$anstypes));
        }

        if (isset($abstolerance)) {
            $tol = '|'.$abstolerance;
        } else {
            $tol = $reltolerance;
        }
        $correct = true;
        if (!is_array($answer)) { //single boolean or decimal score
            if ($answer===true) {
                $scorePartResult->setRawScore(1);
                return $scorePartResult;
            } else if ($answer===false || $answer===null) {
                $scorePartResult->setRawScore(0);
                return $scorePartResult;
            } else {
                if ($answer < 0) {
                  $answer = 0;
                } else if ($answer > 1) {
                  $answer = 1;
                }
                $scorePartResult->setRawScore($answer);
                return $scorePartResult;
            }
        }
        if (is_array($answer) && is_string($answer[0])) {  //if single {'function',$f,$g) type, make array
            $answer = array($answer);
        }
        foreach ($answer as $ans) {
            if (is_array($ans)) {
                if ($ans[0][0]=='!') {
                    $flip = true;
                    $ans[0] = substr($ans[0],1);
                } else {
                    $flip = false;
                }
                if ($ans[0]=='number') {
                    $pt = comparenumbers($ans[1],$ans[2],$tol);
                } else if ($ans[0]=='function') {
                    $pt = comparefunctions($ans[1],$ans[2],$variables,$tol,$domain);
                }
                if ($flip) {
                    $pt = !$pt;
                }
            } else {
                $pt = $ans;
            }
            if ($pt==false) {
                $scorePartResult->setRawScore(0);
                return $scorePartResult;
            }
        }
        $scorePartResult->setRawScore(1);
        return $scorePartResult;
    }
}

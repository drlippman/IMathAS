<?php

namespace IMathAS\assess2\questions\scorepart;

require_once(__DIR__ . '/ScorePart.php');
require_once(__DIR__ . '/../models/ScorePartResult.php');

use IMathAS\assess2\questions\models\ScorePartResult;
use IMathAS\assess2\questions\models\ScoreQuestionParams;

class EssayScorePart implements ScorePart
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

        require_once(dirname(__FILE__)."/../../../includes/htmLawed.php");

        $givenans = myhtmLawed($givenans);
        $scorePartResult->setLastAnswerAsGiven($givenans);
        
        if (isset($options['scoremethod']))if (is_array($options['scoremethod'])) {$scoremethod = $options['scoremethod'][$partnum];} else {$scoremethod = $options['scoremethod'];}
        if (isset($scoremethod) &&
            (($scoremethod=='takeanything'  && trim($givenans)!='') ||
                $scoremethod=='takeanythingorblank')
        ) {
            $scorePartResult->setRawScore(1);
            return $scorePartResult;
        } else if (trim($givenans)=='') {
            $scorePartResult->setRawScore(0);
            return $scorePartResult;
        } else {
            $GLOBALS['questionmanualgrade'] = true;
            $scorePartResult->setRawScore(-2);
            return $scorePartResult;
        }
    }
}

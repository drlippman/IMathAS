<?php

namespace IMathAS\assess2\questions\scorepart;

require_once __DIR__ . '/ScorePart.php';
require_once __DIR__ . '/../models/ScorePartResult.php';

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

        require_once dirname(__FILE__)."/../../../includes/htmLawed.php";

        $NC = [
            'deny_attribute' => 'on*,data*,aria*,tabindex,id'
        ]; // extended config to remove stuff that might interfere
        $givenans = myhtmLawed($givenans, $NC);
        if (strlen($givenans)>30000) {
            $givenans = substr($givenans,0,30000) . ' (remainder truncated due to length)';
            $givenans = myhtmLawed($givenans, $NC); // do again to close any truncated tags
        }
        $scorePartResult->setLastAnswerAsGiven($givenans);

        $scoremethod = getOptionVal($options, 'scoremethod', $multi, $partnum);
        
        if (!empty($scoremethod) &&
            (($scoremethod=='takeanything'  && trim($givenans)!='') ||
                $scoremethod=='takeanythingorblank')
        ) {
            $scorePartResult->setRawScore(1);
            return $scorePartResult;
        } else if (trim($givenans)=='') {
            $scorePartResult->setRawScore(0);
            return $scorePartResult;
        } else if (!empty($scoremethod) && $scoremethod=='nomanual') {
            $scorePartResult->setRawScore(0);
            return $scorePartResult;
        } else {
            $GLOBALS['questionmanualgrade'] = true;
            $scorePartResult->setRawScore(-2);
            return $scorePartResult;
        }
    }
}

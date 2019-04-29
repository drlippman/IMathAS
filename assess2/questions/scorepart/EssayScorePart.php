<?php

namespace IMathAS\assess2\questions\scorepart;

require_once(__DIR__ . '/ScorePart.php');

use IMathAS\assess2\questions\models\ScoreQuestionParams;

class EssayScorePart implements ScorePart
{
    private $scoreQuestionParams;

    public function __construct(ScoreQuestionParams $scoreQuestionParams)
    {
        $this->scoreQuestionParams = $scoreQuestionParams;
    }

    public function getScore(): int
    {
        global $mathfuncs;

        $RND = $this->scoreQuestionParams->getRandWrapper();
        $options = $this->scoreQuestionParams->getVarsForScorePart();
        $qn = $this->scoreQuestionParams->getQuestionNumber();
        $givenans = $this->scoreQuestionParams->getGivenAnswer();
        $multi = $this->scoreQuestionParams->getIsMultiPartQuestion();
        $partnum = $this->scoreQuestionParams->getQuestionPartNumber();

        $defaultreltol = .0015;

        require_once(dirname(__FILE__)."/../../../includes/htmLawed.php");

        $givenans = myhtmLawed($givenans);
        $GLOBALS['partlastanswer'] = $givenans;
        if (isset($options['scoremethod']))if (is_array($options['scoremethod'])) {$scoremethod = $options['scoremethod'][$partnum];} else {$scoremethod = $options['scoremethod'];}
        if (isset($scoremethod) &&
            (($scoremethod=='takeanything'  && trim($givenans)!='') ||
                $scoremethod=='takeanythingorblank')
        ) {
            return 1;
        } else if (trim($givenans)=='') {
            return 0;
        } else {
            $GLOBALS['questionmanualgrade'] = true;
            return -2;
        }
    }
}

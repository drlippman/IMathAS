<?php

namespace IMathAS\assess2\questions\scorepart;

require_once(__DIR__ . '/ScorePart.php');
require_once(__DIR__ . '/../models/ScorePartResult.php');

use IMathAS\assess2\questions\models\ScorePartResult;
use IMathAS\assess2\questions\models\ScoreQuestionParams;

class MultipleAnswerScorePart implements ScorePart
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

        if (is_array($options['questions'][$partnum])) {$questions = $options['questions'][$partnum];} else {$questions = $options['questions'];}
        if (isset($options['answers'])) {if (is_array($options['answers'])) {$answers = $options['answers'][$partnum];} else {$answers = $options['answers'];}}
        else if (isset($options['answer'])) {if (is_array($options['answer'])) {$answers = $options['answer'][$partnum];} else {$answers = $options['answer'];}}
        if (isset($options['noshuffle'])) {if (is_array($options['noshuffle'])) {$noshuffle = $options['noshuffle'][$partnum];} else {$noshuffle = $options['noshuffle'];}}

        if (isset($options['scoremethod']))if (is_array($options['scoremethod'])) {$scoremethod = $options['scoremethod'][$partnum];} else {$scoremethod = $options['scoremethod'];}

        if (!is_array($questions)) {
            $scorePartResult->addScoreMessage(_('Eeek!  $questions is not defined or needs to be an array.  Make sure $questions is defined in the Common Control section.'));
            $scorePartResult->setRawScore(0);
            return $scorePartResult;
        }
        if ($multi) { $qn = ($qn+1)*1000+$partnum; }
        $score = 1.0;
        if ($noshuffle == "last") {
            $randqkeys = $RND->array_rand(array_slice($questions,0,count($questions)-1),count($questions)-1);
            $RND->shuffle($randqkeys);
            array_push($randqkeys,count($questions)-1);
        } else if ($noshuffle == "all" || count($questions)==1) {
            $randqkeys = array_keys($questions);
        } else {
            $randqkeys = $RND->array_rand($questions,count($questions));
            $RND->shuffle($randqkeys);
        }
        $qcnt = count($questions);
        if ($qcnt > 1) {
          $qstr = strtolower(implode(' ', $questions));
          if (strpos($qstr, 'none of') === false) {
            $questions[] = _('None of these');
            array_push($randqkeys, $qcnt);
          }
        }
        if (trim($answers)=='') {
            $akeys = array();
        } else {
            $akeys = explode(",",$answers);
        }
        if (isset($scoremethod) && $scoremethod=='answers') {
            $deduct = 1.0/count($akeys);
        } else {
            $deduct = 1.0/$qcnt;
        }
        $origla = array();
        for ($i=0;$i<count($questions);$i++) {
            if (isset($_POST["qn$qn"][$i])) {
                $origla[] = $randqkeys[$i];
            }

            if (isset($_POST["qn$qn"][$i])!==(in_array($randqkeys[$i],$akeys))) {
                $score -= $deduct;
            }
        }

        //check for "none of these" checked
        if ($qcnt > 1 && isset($_POST["qn$qn"][count($questions)-1])) {
          $score = (trim($answers) === '') ? 1 : 0;
        }

        // just store unrandomized last answers
        $scorePartResult->setLastAnswerAsGiven(implode('|',$origla));
        if (isset($scoremethod)) {
            if ($scoremethod=='allornothing' && $score<1) {
                $score = 0;
            } else if ($scoremethod == 'takeanything') {
                $score = 1;
            }
        }
        if ($score < 0) {
            $score = 0;
        }
        $scorePartResult->setRawScore($score);
        return $scorePartResult;
    }
}

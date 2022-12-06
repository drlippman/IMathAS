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
        $isRescore = $this->scoreQuestionParams->getIsRescore();

        $defaultreltol = .0015;

        $optionkeys = ['answers', 'noshuffle', 'scoremethod'];
        foreach ($optionkeys as $optionkey) {
            ${$optionkey} = getOptionVal($options, $optionkey, $multi, $partnum);
        }
        $questions = getOptionVal($options, 'questions', $multi, $partnum, 2);
        $answers = trim($answers, ' ,');
        
        if (!is_array($questions)) {
            $scorePartResult->addScoreMessage(_('Eeek!  $questions is not defined or needs to be an array.  Make sure $questions is defined in the Common Control section.'));
            $scorePartResult->setRawScore(0);
            return $scorePartResult;
        }
        if ($multi) { $qn = ($qn+1)*1000+$partnum; }
        
        if ($noshuffle == "last") {
            $randqkeys = (array) $RND->array_rand(array_slice($questions,0,count($questions)-1),count($questions)-1);
            $RND->shuffle($randqkeys);
            array_push($randqkeys,count($questions)-1);
        } else if ($noshuffle == "all" || count($questions)==1) {
            $randqkeys = array_keys($questions);
        } else {
            $randqkeys = (array) $RND->array_rand($questions,count($questions));
            $RND->shuffle($randqkeys);
        }
        $qcnt = count($questions);
        if ($qcnt > 1 && trim($answers) == "") {
          $qstr = strtolower(implode(' ', $questions));
          if (strpos($qstr, 'none of') === false) {
            $questions[] = _('None of these');
            array_push($randqkeys, $qcnt);
            $answers = $qcnt;
          }
        }
        $ansor = explode(' or ', $answers);

        
        $origla = array();
        if ($isRescore) {
          $origla = explode('|', $givenans);
        } else {
          for ($i=0;$i<count($questions);$i++) {
              if (isset($_POST["qn$qn"][$i])) {
                  $origla[] = $randqkeys[$i];
              }
          }
        }
        $bestscore = 0;
        foreach ($ansor as $answers) {
            $score = 1.0;
            if (trim($answers)=='') {
                $akeys = array();
            } else {
                $akeys = array_map('trim',explode(',',$answers));
            }
            if ($qcnt > 1 && count($akeys) > 0 && count($origla) == 0) {
            // if there's at least one correct answer, and no answers were submitted
            // and the system still submitted it, then probably it's singlescore.
            // To not give credit for an unanswered question, set scoremethod to answers
            $scoremethod = 'answers';
            }
            if (!empty($scoremethod) && $scoremethod=='answers') {
                $deduct = 1.0/count($akeys);
            } else {
                $deduct = 1.0/$qcnt;
            }
            for ($i=0;$i<count($questions);$i++) {
                if ($isRescore) {
                    if (in_array($i,$origla)!==in_array($i,$akeys)) {
                        $score -= $deduct;
                    }
                } else {
                    if (isset($_POST["qn$qn"][$i])!==(in_array($randqkeys[$i],$akeys))) {
                        $score -= $deduct;
                    }
                }
            }
            if ($score > $bestscore) {
                $bestscore = $score;
            }
        }
        // just store unrandomized last answers
        sort($origla);
        $scorePartResult->setLastAnswerAsGiven(implode('|',$origla));
        if (!empty($scoremethod)) {
            if ($scoremethod=='allornothing' && $bestscore<1) {
                $bestscore = 0;
            } else if ($scoremethod == 'takeanything') {
                $bestscore = 1;
            }
        }
        if ($bestscore < 0) {
            $bestscore = 0;
        }

        $scorePartResult->setRawScore($bestscore);

        if (isset($GLOBALS['CFG']['hooks']['assess2/questions/scorepart/multiple_answer_score_part'])) {
            require_once($GLOBALS['CFG']['hooks']['assess2/questions/scorepart/multiple_answer_score_part']);
            if (isset($onGetResult) && is_callable($onGetResult)) {
                $onGetResult();
            }
        }

        return $scorePartResult;
    }
}

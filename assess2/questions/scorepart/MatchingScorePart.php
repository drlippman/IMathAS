<?php

namespace IMathAS\assess2\questions\scorepart;

require_once(__DIR__ . '/ScorePart.php');
require_once(__DIR__ . '/../models/ScorePartResult.php');

use Sanitize;

use IMathAS\assess2\questions\models\ScorePartResult;
use IMathAS\assess2\questions\models\ScoreQuestionParams;

class MatchingScorePart implements ScorePart
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

        $optionkeys = ['matchlist', 'noshuffle', 'scoremethod'];
        foreach ($optionkeys as $optionkey) {
            ${$optionkey} = getOptionVal($options, $optionkey, $multi, $partnum);
        }
        $questions = getOptionVal($options, 'questions', $multi, $partnum, 2);
        $answers = getOptionVal($options, 'answers', $multi, $partnum, 2);

        if (!is_array($questions) || !is_array($answers)) {
            $scorePartResult->addScoreMessage(_('Eeek!  $questions or $answers is not defined or needs to be an array.  Make sure both are defined in the Common Control section.'));
            $scorePartResult->setRawScore(0);
            return $scorePartResult;
        }
        if ($multi) { $qn = ($qn+1)*1000+$partnum; }
        $score = 1.0;
        $deduct = 1.0/count($questions);
        if ($noshuffle=="questions" || $noshuffle=='all') {
            $randqkeys = array_keys($questions);
        } else {
            $randqkeys = (array) $RND->array_rand($questions,count($questions));
            $RND->shuffle($randqkeys);
        }
        if ($noshuffle=="answers" || $noshuffle=='all') {
            $randakeys = array_keys($answers);
        } else {
            $randakeys = (array) $RND->array_rand($answers,count($answers));
            $RND->shuffle($randakeys);
        }
        if (!empty($matchlist)) {$matchlist = array_map('trim',explode(',',$matchlist));}

        $origla = array();
        for ($i=0;$i<count($questions);$i++) {
          if ($isRescore) {
            $origla = explode('|', $givenans);
            if (isset($origla[$i]) && $origla[$i] !== '') {
              if (!empty($matchlist)) {
                if ($matchlist[$i] != $origla[$i]) {
                  $score -= $deduct;
                }
              } else {
                if ($i != $origla[$i]) {
                  $score -= $deduct;
                }
              }
            } else {
              $score -= $deduct;
            }
          } else {
            if (isset($_POST["qn$qn-$i"]) && $_POST["qn$qn-$i"]!=="" && $_POST["qn$qn-$i"]!="-") {
                $qa = Sanitize::onlyInt($_POST["qn$qn-$i"]);
                $origla[$randqkeys[$i]] = $randakeys[$qa];
                if (!empty($matchlist)) {
                    if ($matchlist[$randqkeys[$i]]!=$randakeys[$qa]) {
                        $score -= $deduct;
                    }
                } else {
                    if ($randqkeys[$i]!=$randakeys[$qa]) {
                        $score -= $deduct;
                    }
                }
            } else {$origla[$randqkeys[$i]] = '';$score -= $deduct;}
          }
        }
        ksort($origla);

        // only store unrandomized
        $scorePartResult->setLastAnswerAsGiven(implode('|', $origla));
        if (!empty($scoremethod) && $scoremethod=='allornothing') {
            if ($score<.99) {
                $score = 0;
            } 
        }
        $scorePartResult->setRawScore($score);
        return $scorePartResult;
    }
}

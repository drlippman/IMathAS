<?php

namespace IMathAS\assess2\questions\scorepart;

require_once(__DIR__ . '/ScorePart.php');

use IMathAS\assess2\questions\models\ScoreQuestionParams;

class ChoicesScorePart implements ScorePart
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

        if (is_array($options['questions'][$partnum])) {$questions = $options['questions'][$partnum];} else {$questions = $options['questions'];}
        if (is_array($options['answer'])) {$answer = $options['answer'][$partnum];} else {$answer = $options['answer'];}
        if (isset($options['noshuffle'])) {if (is_array($options['noshuffle'])) {$noshuffle = $options['noshuffle'][$partnum];} else {$noshuffle = $options['noshuffle'];}} else {$noshuffle = "none";}
        if (is_array($options['partialcredit'][$partnum]) || ($multi && is_array($options['partialcredit']))) {$partialcredit = $options['partialcredit'][$partnum];} else {$partialcredit = $options['partialcredit'];}

        if (isset($partialcredit)) {
            if (!is_array($partialcredit)) {
                $partialcredit = explode(',',$partialcredit);
            }
            $creditweight = array();
            for ($i=0;$i<count($partialcredit);$i+=2) {
                $creditweight[$partialcredit[$i]] = floatval($partialcredit[$i+1]);
            }
        }

        if (!is_array($questions)) {
            echo _('Eeek!  $questions is not defined or needs to be an array.  Make sure $questions is defined in the Common Control section.');
            return false;
        }
        if ($multi) { $qn = ($qn+1)*1000+$partnum; }

        if ($noshuffle == "last") {
            $randkeys = $RND->array_rand(array_slice($questions,0,count($questions)-1),count($questions)-1);
            $RND->shuffle($randkeys);
            array_push($randkeys,count($questions)-1);
        } else if ($noshuffle == "all") {
            $randkeys = array_keys($questions);
        } else if (strlen($noshuffle)>4 && substr($noshuffle,0,4)=="last") {
            $n = intval(substr($noshuffle,4));
            if ($n>count($questions)) {
                $n = count($questions);
            }
            $randkeys = $RND->array_rand(array_slice($questions,0,count($questions)-$n),count($questions)-$n);
            $RND->shuffle($randkeys);
            for ($i=count($questions)-$n;$i<count($questions);$i++) {
                array_push($randkeys,$i);
            }
        } else {
            $randkeys = $RND->array_rand($questions,count($questions));
            $RND->shuffle($randkeys);
        }
        if ($givenans==='NA' || $givenans === null) {
            $GLOBALS['partlastanswer'] = $givenans;
        } else {
          // only store the unrandomized value
            $GLOBALS['partlastanswer'] = $randkeys[$givenans];//$givenans.'$!$'.$randkeys[$givenans];
        }
        if ($givenans == null) {return 0;}

        if ($givenans=='NA') { return 0; }
        $anss = explode(' or ',$answer);
        foreach ($anss as $k=>$v) {
            $anss[$k] = intval($v);
        }
        //if ($randkeys[$givenans] == $answer) {return 1;} else { return 0;}
        if (in_array($randkeys[$givenans],$anss)) {
            return 1;
        } else if (isset($partialcredit) && isset($creditweight[$randkeys[$givenans]])) {
            return $creditweight[$randkeys[$givenans]];
        } else {
            return 0;
        }
    }
}

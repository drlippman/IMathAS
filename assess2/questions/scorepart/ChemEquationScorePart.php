<?php

namespace IMathAS\assess2\questions\scorepart;

require_once(__DIR__ . '/ScorePart.php');
require_once(__DIR__ . '/../models/ScorePartResult.php');

use IMathAS\assess2\questions\models\ScorePartResult;
use IMathAS\assess2\questions\models\ScoreQuestionParams;

class ChemEquationScorePart implements ScorePart
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

        $optionkeys = ['answer', 'answerformat', 'requiretimes'];
        foreach ($optionkeys as $optionkey) {
            ${$optionkey} = getOptionVal($options, $optionkey, $multi, $partnum);
        }

        if ($multi) { $qn = ($qn+1)*1000+$partnum; }
        $givenans = normalizemathunicode($givenans);

        $givenans = str_replace(['<->','<=>'], 'rightleftharpoons', $givenans);
        $givenans = str_replace(['to','rarr','implies'], '->', $givenans);
        $scorePartResult->setLastAnswerAsGiven($givenans);

        if ($requiretimes !== '' && checkreqtimes($givenans,$requiretimes)==0) {
            $scorePartResult->setRawScore(0);
            return $scorePartResult;
        }

        if ($answerformat == 'reaction') {
            $givenparsed = parseChemical($givenans);
        } else {
            $givenans = str_replace(' ', '', $givenans);
        }
        
        $score = 0;

        $anss = explode(' or ', $answer);
        foreach ($anss as $ans) {
            if ($answerformat == 'reaction') {
                $ansparsed = parseChemical($ans);

                // check reaction type
                if ($givenparsed[1] !== $ansparsed[1]) {
                    continue;
                }
                foreach ($ansparsed[0] as $k=>$side) {
                    foreach ($side as $sk=>$anspart) {
                        $gapart = $givenparsed[0][$k][$sk];
                        // check chemical
                        if ($anspart[1] !== $gapart[1]) {
                            continue 3;
                        }
                        // check weight
                        if (abs($anspart[0] - $gapart[0]) > .0001) {
                            continue 3;
                        }
                    }
                }
                $score = 1; break;
            } else {
                $ans = str_replace(' ','',$ans);
                if ($ans === $givenans) {
                    $score = 1; break;
                }
            }
        }

        if ($score<0) { $score = 0; }
        $scorePartResult->setRawScore($score);
        return $scorePartResult;
        //return $correct;
    }
}

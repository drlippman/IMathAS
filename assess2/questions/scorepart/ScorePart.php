<?php

namespace IMathAS\assess2\questions\scorepart;

use IMathAS\assess2\questions\models\ScorePartResult;
use IMathAS\assess2\questions\models\ScoreQuestionParams;

/**
 * Interface ScorePart
 *
 * @package IMathAS\assess2\questions\scorepart
 */
interface ScorePart
{
    public function __construct(ScoreQuestionParams $scoreQuestionParams);

    public function getResult(): ScorePartResult;
}

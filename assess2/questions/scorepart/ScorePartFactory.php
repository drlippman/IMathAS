<?php

namespace IMathAS\assess2\questions\scorepart;

require_once(__DIR__ . '/CalculatedMatrixScorePart.php');
require_once(__DIR__ . '/CalculatedScorePart.php');
require_once(__DIR__ . '/ChoicesScorePart.php');
require_once(__DIR__ . '/ComplexScorePart.php');
require_once(__DIR__ . '/ConditionalScorePart.php');
require_once(__DIR__ . '/DrawingScorePart.php');
require_once(__DIR__ . '/EssayScorePart.php');
require_once(__DIR__ . '/FileScorePart.php');
require_once(__DIR__ . '/FunctionExpressionScorePart.php');
require_once(__DIR__ . '/IntervalScorePart.php');
require_once(__DIR__ . '/MatchingScorePart.php');
require_once(__DIR__ . '/MatrixScorePart.php');
require_once(__DIR__ . '/MultipleAnswerScorePart.php');
require_once(__DIR__ . '/NTupleScorePart.php');
require_once(__DIR__ . '/NumberScorePart.php');
require_once(__DIR__ . '/StringScorePart.php');
require_once(__DIR__ . '/ChemEquationScorePart.php');

use OutOfBoundsException;

use IMathAS\assess2\questions\models\ScoreQuestionParams;

/**
 * Class ScorePartFactory Returns an appropriate ScorePart instance based on
 *                        the answer type.
 *
 * Types of questions: (Type name : string value for the type)
 * - Number: "number"
 * - Calculated: "calculated"
 * - Multiple Choice: "choices"
 * - Multiple Answer: "multans"
 * - Matching: "matching"
 * - Function/expression: "numfunc"
 * - Drawing: "draw"
 * - N-tuple: "ntuple"
 * - Calculated N-tuple: "calcntuple"
 * - Matrix: "matrix"
 * - Calculated Matrix: "calcmatrix"
 * - Complex: "complex"
 * - Calculated Complex: "calccomplex"
 * - Interval: "interval"
 * - Calculated Interval: "calcinterval"
 * - Essay: "essay"
 * - File Upload: "file"
 * - String: "string"
 *
 * @see ScorePart for ScorePart interface.
 * @package IMathAS\assess2\questions\scorepart
 */
class ScorePartFactory
{
    /**
     * ScorePart factory. An appropriate instance is returned for the question type.
     *
     * @param ScoreQuestionParams $scoreQuestionParams
     * @return ScorePart A ScorePArt instance for the specified answer type.
     */
    public static function getScorePart(ScoreQuestionParams $scoreQuestionParams): ScorePart
    {
        $answerType = $scoreQuestionParams->getAnswerType();

        switch ($answerType) {
            case 'calcmatrix':
                return new CalculatedMatrixScorePart($scoreQuestionParams);
                break;
            case 'calculated':
                return new CalculatedScorePart($scoreQuestionParams);
                break;
            case 'choices':
                return new ChoicesScorePart($scoreQuestionParams);
                break;
            case 'complex':
            case 'calccomplex':
                return new ComplexScorePart($scoreQuestionParams);
                break;
            case 'conditional':
                return new ConditionalScorePart($scoreQuestionParams);
                break;
            case 'draw':
                return new DrawingScorePart($scoreQuestionParams);
                break;
            case 'essay':
                return new EssayScorePart($scoreQuestionParams);
                break;
            case 'file':
                return new FileScorePart($scoreQuestionParams);
                break;
            case 'interval':
            case 'calcinterval':
                return new IntervalScorePart($scoreQuestionParams);
                break;
            case 'matching':
                return new MatchingScorePart($scoreQuestionParams);
                break;
            case 'matrix':
                return new MatrixScorePart($scoreQuestionParams);
                break;
            case 'multans':
                return new MultipleAnswerScorePart($scoreQuestionParams);
                break;
            case 'number':
                return new NumberScorePart($scoreQuestionParams);
                break;
            case 'numfunc':
                return new FunctionExpressionScorePart($scoreQuestionParams);
                break;
            case 'ntuple':
            case 'calcntuple':
                return new NTupleScorePart($scoreQuestionParams);
                break;
            case 'string':
                return new StringScorePart($scoreQuestionParams);
                break;
            case 'chemeqn':
                return new ChemEquationScorePart($scoreQuestionParams);
                break;
            default:
                // This will be caught by our custom exception handler to be
                // displayed directly to question writers, who are not
                // necessarily developers nor technical users.
                throw new OutOfBoundsException('Unknown answer type (anstype) "'
                    . $answerType . '".'
                    . " See question writing help for a list of valid types.");
        }
    }
}

<?php

namespace IMathAS\assess2\questions\answerboxes;

require_once __DIR__ . '/CalculatedAnswerBox.php';
require_once __DIR__ . '/CalculatedComplexAnswerBox.php';
require_once __DIR__ . '/CalculatedIntervalAnswerBox.php';
require_once __DIR__ . '/CalculatedMatrixAnswerBox.php';
require_once __DIR__ . '/ChoicesAnswerBox.php';
require_once __DIR__ . '/ComplexAnswerBox.php';
require_once __DIR__ . '/DrawingAnswerBox.php';
require_once __DIR__ . '/EssayAnswerBox.php';
require_once __DIR__ . '/FileUploadAnswerBox.php';
require_once __DIR__ . '/FunctionExpressionAnswerBox.php';
require_once __DIR__ . '/IntervalAnswerBox.php';
require_once __DIR__ . '/MatchingAnswerBox.php';
require_once __DIR__ . '/MatrixAnswerBox.php';
require_once __DIR__ . '/MultipleAnswerAnswerBox.php';
require_once __DIR__ . '/NTupleAnswerBox.php';
require_once __DIR__ . '/ComplexNTupleAnswerBox.php';
require_once __DIR__ . '/AlgebraicNTupleAnswerBox.php';
require_once __DIR__ . '/NumberAnswerBox.php';
require_once __DIR__ . '/StringAnswerBox.php';
require_once __DIR__ . '/ChemEquationAnswerBox.php';
require_once(__DIR__ . '/MoleculeAnswerBox.php');

use OutOfBoundsException;

use Sanitize;

/**
 * Class AnswerBoxFactory Returns an appropriate answer box generator instance
 *                        based on the answer type.
 *
 * @see AnswerBox for generator interface.
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
 * - Complex N-tuple: "complexntuple"
 * - Calculated Complex N-tuple: "calccomplexntuple"
 * - Algebraic N-tuple: "algntuple"
 * - Matrix: "matrix"
 * - Calculated Matrix: "calcmatrix"
 * - Complex: "complex"
 * - Calculated Complex: "calccomplex"
 * - Interval: "interval"
 * - Calculated Interval: "calcinterval"
 * - Essay: "essay"
 * - File Upload: "file"
 * - String: "string"
 * - Molecule: "molecule"
 */
class AnswerBoxFactory
{
    /**
     * Generate answer boxes for a question.
     *
     * @param AnswerBoxParams $answerBoxParams
     * @return AnswerBox An answer box generator for the specified answer type.
     */
    public static function getAnswerBoxGenerator(AnswerBoxParams $answerBoxParams): AnswerBox
    {
        $answerType = Sanitize::simpleString($answerBoxParams->getAnswerType());
        $answerBoxParams->setAnswerType($answerType);

        $studentLastAnswers = str_replace('"', '&quot;', $answerBoxParams->getStudentLastAnswers());
        $answerBoxParams->setStudentLastAnswers($studentLastAnswers);

        switch ($answerType) {
            case 'calculated':
                return new CalculatedAnswerBox($answerBoxParams);
                break;
            case 'calccomplex':
                return new CalculatedComplexAnswerBox($answerBoxParams);
                break;
            case 'calcinterval':
                return new CalculatedIntervalAnswerBox($answerBoxParams);
                break;
            case 'calcmatrix':
            case 'calccomplexmatrix':
            case 'algmatrix':
                return new CalculatedMatrixAnswerBox($answerBoxParams);
                break;
            case 'choices':
                return new ChoicesAnswerBox($answerBoxParams);
                break;
            case 'complex':
                return new ComplexAnswerBox($answerBoxParams);
                break;
            case 'draw':
                return new DrawingAnswerBox($answerBoxParams);
                break;
            case 'essay':
                return new EssayAnswerBox($answerBoxParams);
                break;
            case 'file':
                return new FileUploadAnswerBox($answerBoxParams);
                break;
            case 'numfunc':
                return new FunctionExpressionAnswerBox($answerBoxParams);
                break;
            case 'interval':
                return new IntervalAnswerBox($answerBoxParams);
                break;
            case 'matching':
                return new MatchingAnswerBox($answerBoxParams);
                break;
            case 'complexmatrix':
            case 'matrix':
                return new MatrixAnswerBox($answerBoxParams);
                break;
            case 'multans':
                return new MultipleAnswerAnswerBox($answerBoxParams);
                break;
            case 'ntuple':
            case 'calcntuple':
                return new NTupleAnswerBox($answerBoxParams);
                break;
            case 'complexntuple':
            case 'calccomplexntuple':
                return new ComplexNTupleAnswerBox($answerBoxParams);
                break;
            case 'algntuple':
                return new AlgebraicNTupleAnswerBox($answerBoxParams);
                break;
            case 'number':
                return new NumberAnswerBox($answerBoxParams);
                break;
            case 'string':
                return new StringAnswerBox($answerBoxParams);
                break;
            case 'chemeqn':
                return new ChemEquationAnswerBox($answerBoxParams);
                break;
            case 'molecule':
                return new MoleculeAnswerBox($answerBoxParams);
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

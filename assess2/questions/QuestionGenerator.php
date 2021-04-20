<?php

namespace IMathAS\assess2\questions;

require_once(__DIR__ . "/../../assessment/mathphp2.php");
require_once(__DIR__ . "/../../assessment/mathparser.php");
require_once(__DIR__ . "/../../assessment/interpret5.php");
require_once(__DIR__ . "/../../assessment/macros.php");
require_once(__DIR__ . "/answerboxhelpers.php");
require_once(__DIR__ . "/../../includes/sanitize.php");

require_once(__DIR__ . "/ErrorHandler.php");
require_once(__DIR__ . "/QuestionHtmlGenerator.php");

use PDO;
use RuntimeException;
use Throwable;

use Rand;
use Sanitize;

use IMathAS\assess2\questions\models\Question;
use IMathAS\assess2\questions\models\QuestionParams;

/**
 * Class QuestionGenerator Generates questions and its components.
 *
 * @see QuestionHtmlGenerator
 *
 * Notes:
 *   - This is a refactor of displayq2.php.
 *   - Most code in here is being extracted as-is from displayq2.php,
 *     unless refactoring is simple or is necessary for OO-ness.
 */
class QuestionGenerator
{
    private $dbh;
    private $randWrapper;
    private $questionParams;

    private $errors = array();  // Populated by this class' error handlers.

    /**
     * Question constructor.
     *
     * @param PDO $dbh A PDO instance.
     * @param Rand $randWrapper An instance of Rand from /assessment/macros.php
     * @param QuestionParams $questionParams Params for this question.
     */
    public function __construct(
        PDO $dbh,                       // Orig: $GLOBALS['DBH']
        Rand $randWrapper,              // Orig: $GLOBALS['RND']
        QuestionParams $questionParams
    )
    {
        $this->dbh = $dbh;
        $this->randWrapper = $randWrapper;
        $this->questionParams = $questionParams;
    }

    /**
     * Get a question object containing question components as
     * ready-to-use/embed HTML.
     *
     * Notes:
     *   - This method will clear the choicemap. @see clearChoicemap()
     *
     * @return Question Contains question content as HTML.
     */
    public function getQuestion(): Question
    {
        // This lets various parts of IMathAS know that question HTML is
        // being generated for display, as opposed to just being scored.
        $GLOBALS['inquestiondisplay'] = true;

        $this->setMetricsMetadata();
        $this->clearChoicemap();

        // If question data was not provided, load it from the database.
        if (empty($this->questionParams->getQuestionData())) {
            $this->questionParams->setQuestionData($this->loadQuestionData());
        }

        $GLOBALS['curqsetid'] = $this->questionParams->getDbQuestionSetId();
        set_error_handler(array($this, 'evalErrorHandler'));
        set_exception_handler(array($this, 'evalExceptionHandler'));


        $questionHtmlGenerator = new QuestionHtmlGenerator($this->dbh,
            $this->randWrapper, $this->questionParams);
        $question = $questionHtmlGenerator->getQuestion();
        $question->addErrors($this->errors);
        $question->addErrors($questionHtmlGenerator->getErrors());

        restore_error_handler();
        restore_exception_handler();
        unset($GLOBALS['curqsetid']);

        return $question;
    }

    /**
     * Load a question's data from the database. (table: imas_questionset)
     *
     * @return array An associative array of the question's data.
     */
    private function loadQuestionData(): array
    {
        $questionId = $this->questionParams->getDbQuestionSetId();

        $stm = $this->dbh->prepare("SELECT
                qtype, control, qcontrol, qtext, answer, hasimg, extref, solution, solutionopts
                FROM imas_questionset WHERE id = :id");
        $stm->execute(array(':id' => $questionId));
        $questionData = $stm->fetch(PDO::FETCH_ASSOC);

        if (!$questionData) {
            throw new \RuntimeException(
                sprintf('Failed to get question data for question ID %d. PDO error: %s',
                    $questionId, implode(':', $this->dbh->errorInfo()))
            );
        }

        return $questionData;
    }

    /**
     * Clear out the choicemap, if necessary.
     *
     * The choicemap is a mapping of randomly repositioned displayed answers
     * to the actual answers.
     *
     * FIXME: David wants to fix the while loop.
     *
     * Notes:
     *   - This was previously done at the start of displayq() and should be
     *     done immediately before doing something, but not on class instantiation.
     */
    private function clearChoicemap(): void
    {
        $questionNumber = $this->questionParams->getQuestionNumber();
        $assessmentId = $this->questionParams->getAssessmentId();

        if (!empty($_SESSION['choicemap'][$assessmentId])) {
          unset($_SESSION['choicemap'][$assessmentId][$questionNumber]);

          foreach ($_SESSION['choicemap'][$assessmentId] as $k=>$v) {
            if (floor($k/1000) == $questionNumber + 1) {
              unset($_SESSION['choicemap'][$assessmentId][$k]);
            }
          }
        }
    }

    /**
     * Set runtime metadata for services like New Relic.
     */
    private function setMetricsMetadata(): void
    {
        if (extension_loaded('newrelic')) {
            newrelic_add_custom_parameter('cur_qsid', $this->questionParams->getDbQuestionSetId());
        }
    }

    /**
     * Add an error message to the array of errors for this question.
     *
     * @param string $errorMessage The error message.
     */
    private function addError(string $errorMessage): void
    {
        $this->errors[] = $errorMessage;
    }

    /**
     * Warning handler for question evals.
     *
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     * @param array $errcontext
     * @return bool
     */
    public function evalErrorHandler(int $errno, string $errstr, string $errfile,
                                     int $errline, array $errcontext = []): bool
    {
        ErrorHandler::evalErrorHandler($errno, $errstr, $errfile, $errline, $errcontext);

        if (E_WARNING == $errno || E_ERROR == $errno) {
          $this->addError(sprintf(
              _('Caught warning in the question code: %s on line %d in file %s'),
              $errstr, $errline, $errfile));
        }

        // True = Don't execute the PHP internal error handler.
        // False = Populate $php_errormsg.
        // Reference: https://secure.php.net/manual/en/function.set-error-handler.php
        return true;
    }

    /**
     * Exception handler for question evals.
     *
     * @param Throwable $t
     */
    public function evalExceptionHandler(Throwable $t): void
    {
        ErrorHandler::evalExceptionHandler($t);

        $this->addError(
            _('<p>Caught error while evaluating this question: ')
            . Sanitize::encodeStringForDisplay($t->getMessage())
            . '</p>');
    }
}

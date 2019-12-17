<?php

namespace IMathAS\assess2\questions;

require_once(__DIR__ . '/ErrorHandler.php');
require_once(__DIR__ . '/QuestionHtmlGenerator.php');
require_once(__DIR__ . '/models/ScoreQuestionParams.php');
require_once(__DIR__ . '/scorepart/ScorePartFactory.php');

use PDO;
use RuntimeException;
use Throwable;

use Rand;
use Sanitize;

use IMathAS\assess2\questions\models\ScoreQuestionParams;
use IMathAS\assess2\questions\scorepart\ScorePartFactory;

/**
 * Class ScoreEngine Scores answers to questions.
 *
 * Notes:
 *   - This is a refactor of displayq3.php.
 *   - Most code in here is being extracted as-is from displayq3.php, unless
 *     refactoring is simple or is necessary for OO-ness.
 */
class ScoreEngine
{
    const VARS_FOR_SCOREPART_EVALS = array(
        'abstolerance',
        'ansprompt',
        'anstypes',
        'answeights',
        'answer',
        'answers',
        'answersize',
        'answerformat',
        'domain',
        'grid',
        'matchlist',
        'noshuffle',
        'partialcredit',
        'partweights',
        'reltolerance',
        'reqdecimals',
        'reqsigfigs',
        'requiretimes',
        'requiretimeslistpart',
        'scoremethod',
        'snaptogrid',
        'strflags',
        'questions',
        'variables',
    );

    const ADDITIONAL_VARS_FOR_SCORING = array(
        'qnpointval',
    );

    private $dbh;
    private $randWrapper;
    private $userRights;
    private $errors = array(); // Populated by this class' error handlers.

    public function __construct(PDO $dbh, Rand $randWrapper)
    {
        $this->dbh = $dbh;
        $this->randWrapper = $randWrapper;
    }

    /**
     * Score a question.
     *
     * @param ScoreQuestionParams $scoreQuestionParams Params for scoring this question.
     * @return array
     */
    public function scoreQuestion(ScoreQuestionParams $scoreQuestionParams): array
    {
        // This lets various parts of IMathAS know that question HTML is
        // NOT being generated for display.
        $GLOBALS['inquestiondisplay'] = false;
        $GLOBALS['assessver'] = 2;

        set_error_handler(array($this, 'evalErrorHandler'));
        set_exception_handler(array($this, 'evalExceptionHandler'));
        ob_start();

        $this->randWrapper->srand($scoreQuestionParams->getQuestionSeed());

        if (!isset($_SESSION['choicemap'])) {
            $_SESSION['choicemap'] = array();
        }

        // If question data was not provided, load it from the database.
        $quesData = $scoreQuestionParams->getQuestionData();
        if (is_null($quesData)) {
            $quesData = $this->loadQuestionData($scoreQuestionParams);
        }

        $stuanswers = $scoreQuestionParams->getAllQuestionAnswers();
        $stuanswersval = $scoreQuestionParams->getAllQuestionAnswersAsNum();

        if ($this->isMultipartQuestion($quesData)) {
            list($stuanswers, $stuanswersval) =
                $this->processStudentAnswersMultipart($scoreQuestionParams,
                    $stuanswers, $stuanswersval);
        } else {
            list($stuanswers, $stuanswersval) =
                $this->processStudentAnswersNonMultipart($scoreQuestionParams,
                    $stuanswers, $stuanswersval);
        }

        /*
         * Evals
         */

        // User's rights are used during exception handling.
        $this->userRights = $scoreQuestionParams->getUserRights();

        // These may be needed in evals.
        $qnidx = $scoreQuestionParams->getQuestionNumber();
        $attemptn = $scoreQuestionParams->getAttemptNumber();
        $thisq = $scoreQuestionParams->getQuestionNumber() + 1;
        try {
          eval(interpret('control', $quesData['qtype'], $quesData['control']));
          $this->randWrapper->srand($scoreQuestionParams->getQuestionSeed() + 1);
          eval(interpret('answer', $quesData['qtype'], $quesData['answer']));
        } catch (\Throwable $t) {
          $this->addError(
              _('Caught error while evaluating the code in this question: ')
              . $t->getMessage());
        }

        /*
		 * Correct mistakes made by question writers.
		 */

        if (isset($choices) && !isset($questions)) {
            $questions =& $choices;
        }
        if (isset($variable) && !isset($variables)) {
            $variables =& $variable;
        }

        /*
		 * Massage some data.
		 */

        if (isset($anstypes)) {
            if (!is_array($anstypes)) {
                $anstypes = explode(",", $anstypes);
            }
            $anstypes = array_map('trim', $anstypes);
        }

        if (isset($reqdecimals)) {
            $hasGlobalAbstol = false;
            if (is_array($anstypes) && !isset($abstolerance) && !isset($reltolerance)) {
                $abstolerance = array();
            } else if (isset($anstypes) && isset($abstolerance) && !is_array($abstolerance)) {
                $abstolerance = array_fill(0, count($anstypes), $abstolerance);
                $hasGlobalAbstol = true;
            }
            if (is_array($reqdecimals)) {
                foreach ($reqdecimals as $kidx => $vval) {
                    if (substr((string)$vval, 0, 1) == '=') {
                        continue;
                    } //skip '=2' style $reqdecimals
                    if (($hasGlobalAbstol || !isset($abstolerance[$kidx])) && (!is_array($reltolerance) || !isset($reltolerance[$kidx]))) {
                        $abstolerance[$kidx] = 0.5 / (pow(10, $vval));
                    }
                }
            } else if (substr((string)$reqdecimals, 0, 1) != '=') { //skip '=2' style $reqdecimals
                if (!isset($abstolerance) && !isset($reltolerance)) { //set global abstol
                    $abstolerance = 0.5 / (pow(10, $reqdecimals));
                } else if (isset($anstypes) && !isset($reltolerance)) {
                    foreach ($anstypes as $kidx => $vval) {
                        if (!isset($abstolerance[$kidx]) && (!is_array($reltolerance) || !isset($reltolerance[$kidx]))) {
                            $abstolerance[$kidx] = 0.5 / (pow(10, $reqdecimals));
                        }
                    }
                }
            }
        }

        /*
         * Set RNG to a known state.
         */

        $this->randWrapper->srand($scoreQuestionParams->getQuestionSeed() + 2);

        /*
	     * Package local variables for scorepart().
	     */

        // These may have been defined by the question writer.
        $varsForScorepart = array();
        foreach (self::VARS_FOR_SCOREPART_EVALS as $optionKey) {
            if (!isset(${$optionKey})) {
                continue;
            }

            if ('answerformat' == $optionKey) {
                $answerformat = str_replace(' ', '', $answerformat);
            }

            $varsForScorepart[$optionKey] = ${$optionKey};
        }


        /*
         * Package additional variables for scoring, not used by scorepart().
         */
        $additionalVarsForScoring = array();
        foreach (self::ADDITIONAL_VARS_FOR_SCORING as $optionKey) {
            if (!isset(${$optionKey})) {
                continue;
            }

            $additionalVarsForScoring[$optionKey] = ${$optionKey};
        }

        /*
         * Score the student's answers.
         *
         * FIXME: Need to handle conditional questions.
         */

        $scoreQuestionParams->setVarsForScorePart($varsForScorepart);

        if ($quesData['qtype'] == "multipart") {
            $scoreResult = $this->scorePartMultiPart($scoreQuestionParams,
                $additionalVarsForScoring);
        } else {
            $scoreResult = $this->scorePartNonMultiPart($scoreQuestionParams, $quesData);
            if ($quesData['qtype'] == "conditional") {
              // Store just-build $stuanswers as lastanswer for conditional
              // in case there was no POST (like multans checkbox), null out
              // stuanswers
              for ($iidx=0;$iidx<count($anstypes);$iidx++) {
                if (!isset($stuanswers[$thisq][$iidx])) {
                  $stuanswers[$thisq][$iidx] = null;
                }
              }
              $scoreResult['lastAnswerAsGiven'] = $stuanswers[$thisq];
              $scoreResult['lastAnswerAsNumber'] = $stuanswersval[$thisq];
            }
        }

        restore_error_handler();
        restore_exception_handler();
        $errors = ob_get_clean();
        if ($errors != '') {
          $this->addError($errors);
        }
        $scoreResult['errors'] = $this->errors;

        return $scoreResult;
    }

    /**
     * Load a question's data from the database. (table: imas_questionset)
     *
     * @param ScoreQuestionParams $scoreQuestionParams
     * @return array An associative array of the question's data.
     */
    private function loadQuestionData(ScoreQuestionParams $scoreQuestionParams): array
    {
        $dbQuestionId = $scoreQuestionParams->getDbQuestionSetId();
        $questionNumber = $scoreQuestionParams->getQuestionNumber();

        $stm = $this->dbh->prepare("SELECT qtype,control,answer FROM imas_questionset WHERE id=:id");
        $stm->execute(array(':id' => $dbQuestionId));
        $questionData = $stm->fetch(PDO::FETCH_ASSOC);

        if (!$questionData) {
            throw new RuntimeException(
                sprintf('Failed to get question data for question ID %d. PDO error: %s',
                    $dbQuestionId, implode(':', $this->dbh->errorInfo()))
            );
        }

        return $questionData;
    }

    /**
     * Process student answers for a multipart question.
     *
     * FIXME: Need a better method description.
     *
     * @param ScoreQuestionParams $scoreQuestionParams Params for scoring this question.
     * @param array $stuanswers Student answers generated by generateStudentAnswers().
     * @param array $stuanswersval Student answer values generated by generateStudentAnswers().
     * @return array [0] = $stuanswers, [1] = $stuanswersval
     */
    private function processStudentAnswersMultipart(ScoreQuestionParams $scoreQuestionParams,
                                                    array $stuanswers,
                                                    array $stuanswersval)
    {
        $thisq = $scoreQuestionParams->getQuestionNumber() + 1;

        $stuanswers[$thisq] = array();
        $stuanswersval[$thisq] = array();
        $postpartstoprocess = array();
        foreach ($_POST as $postk => $postv) {
            $prefix = substr($postk, 0, 2);
            if ($prefix == 'qn') { // TODO: handle "qs" from nosolninf
                $partnum = intval(substr($postk, 2));
                if (floor($partnum / 1000) == $thisq) {
                    $kidx = round($partnum - 1000 * floor($partnum / 1000));
                    $postpartstoprocess[$partnum] = $kidx;
                }
            }
        }

        foreach ($postpartstoprocess as $partnum => $kidx) {
            if (isset($_POST["qs$partnum"]) && (
              $_POST["qs$partnum"] === 'DNE' || $_POST["qs$partnum"] === 'inf')
            ) {
              // nosolninf solution
              $stuanswers[$thisq][$kidx] = $_POST["qs$partnum"];
              $stuanswersval[$thisq][$kidx] = $_POST["qs$partnum"];
            } else if (isset($_POST["qn$partnum-0"])) {
              // either matrix or calcmatrix with answersize, or matching
                $tmp = array();
                $spc = 0;
                while (isset($_POST["qn$partnum-$spc"])) {
                    $tmp[] = $_POST["qn$partnum-$spc"];
                    $spc++;
                }
                if (isset($_SESSION['choicemap'][$partnum])) {
                  // matching - map back to unrandomized values
                  list($randqkeys, $randakeys) = $_SESSION['choicemap'][$partnum];
                  $mapped = array();
                  foreach ($tmp as $k=>$v) {
                    $mapped[$randqkeys[$k]] = $randakeys[$v];
                  }
                  ksort($mapped);
                  $stuanswers[$thisq][$kidx] = implode('|', $mapped);
                } else {
                  //matrix
                  $stuanswers[$thisq][$kidx] = implode('|', $tmp);
                  if (isset($_POST["qn$partnum-val"])) {
                    // calcmatrix values
                    $stuanswersval[$thisq][$kidx] = $_POST["qn$partnum-val"];
                  }
                }
            } else if (isset($_POST["qn$partnum"])) {
                $stuanswers[$thisq][$kidx] = $_POST["qn$partnum"];
                if (isset($_POST["qn$partnum-val"])) { // the calculated types
                  $stuanswersval[$thisq][$kidx] = $_POST["qn$partnum-val"];
                } else if (is_numeric($_POST["qn$partnum"])) { // number
                  $stuanswersval[$thisq][$kidx] = floatval($_POST["qn$partnum"]);
                }
                if (isset($_SESSION['choicemap'][$partnum])) {
                    if (is_array($stuanswers[$thisq][$kidx])) { //multans
                        foreach ($stuanswers[$thisq][$kidx] as $k => $v) {
                            $stuanswers[$thisq][$kidx][$k] = $_SESSION['choicemap'][$partnum][$v];
                        }
                        $stuanswers[$thisq][$kidx] = implode('|', $stuanswers[$thisq][$kidx]);
                    } else { // choices
                        $stuanswers[$thisq][$kidx] = $_SESSION['choicemap'][$partnum][$stuanswers[$thisq][$kidx]];
                        if ($stuanswers[$thisq][$kidx] === null) {
                            $stuanswers[$thisq][$kidx] = 'NA';
                        }
                    }
                }
            }
        }
        ksort($stuanswers[$thisq]);
        ksort($stuanswersval[$thisq]);

        return array($stuanswers, $stuanswersval);
    }

    /**
     * Process student answers for a non-multipart question.
     *
     * FIXME: Need a better method description.
     *
     * @param ScoreQuestionParams $scoreQuestionParams Params for scoring this question.
     * @param array $stuanswers Student answers generated by generateStudentAnswers().
     * @param array $stuanswersval Student answer values generated by generateStudentAnswers().
     * @return array [0] = $stuanswers, [1] = $stuanswersval
     */
    private function processStudentAnswersNonMultipart(ScoreQuestionParams $scoreQuestionParams,
                                                       array $stuanswers,
                                                       array $stuanswersval)
    {
        $qnidx = $scoreQuestionParams->getQuestionNumber();
        $thisq = $scoreQuestionParams->getQuestionNumber() + 1;

        if (isset($_POST["qs$qnidx"]) && (
          $_POST["qs$qnidx"] === 'DNE' || $_POST["qs$qnidx"] === 'inf')
        ) {
          // nosolninf solution
          $stuanswers[$thisq] = $_POST["qs$qnidx"];
          $stuanswersval[$thisq] = $_POST["qs$qnidx"];
        } else if (isset($_POST["qn$qnidx-0"])) {
          // either matrix or calcmatrix with answersize, or matching
            $tmp = array();
            $spc = 0;
            while (isset($_POST["qn$qnidx-$spc"])) {
                $tmp[] = $_POST["qn$qnidx-$spc"];
                $spc++;
            }
            if (isset($_SESSION['choicemap'][$qnidx])) {
              // matching - map back to unrandomized values
              list($randqkeys, $randakeys) = $_SESSION['choicemap'][$qnidx];
              $mapped = array();
              foreach ($tmp as $k=>$v) {
                $mapped[$randqkeys[$k]] = $randakeys[$v];
              }
              ksort($mapped);
              $stuanswers[$thisq] = implode('|', $mapped);
            } else {
              //matrix
              $stuanswers[$thisq] = implode('|', $tmp);
              if (isset($_POST["qn$qnidx-val"])) {
                // calcmatrix values
                $stuanswersval[$thisq] = $_POST["qn$qnidx-val"];
              }
            }
        } else if (isset($_POST["qn$qnidx"])) {
            $stuanswers[$thisq] = $_POST["qn$qnidx"];
            if (isset($_POST["qn$qnidx-val"])) { // the calculated types
              $stuanswersval[$thisq] = $_POST["qn$qnidx-val"];
            } else if (is_numeric($_POST["qn$qnidx"])) { // number
              $stuanswersval[$thisq] = floatval($_POST["qn$qnidx"]);
            }
            if (isset($_SESSION['choicemap'][$qnidx])) {
                if (is_array($stuanswers[$thisq])) { //multans
                    foreach ($stuanswers[$thisq] as $k => $v) {
                        $stuanswers[$thisq][$k] = $_SESSION['choicemap'][$qnidx][$v];
                    }
                    $stuanswers[$thisq] = implode('|', $stuanswers[$thisq]);
                } else { // choices
                    $stuanswers[$thisq] = $_SESSION['choicemap'][$qnidx][$stuanswers[$thisq]];
                    if ($stuanswers[$thisq] === null) {
                        $stuanswers[$thisq] = 'NA';
                    }
                }
            }
        }

        return array($stuanswers, $stuanswersval);
    }

    /**
     * Score a multipart question's answers.
     *
     * @param ScoreQuestionParams $scoreQuestionParams
     * @param array $additionalPackagedVars Additional packaged vars needed but
     *                                      not used by scorepart().
     * @return array An array of scores.
     */
    private function scorePartMultiPart(ScoreQuestionParams $scoreQuestionParams,
                                        array $additionalPackagedVars): array
    {
        $qnidx = $scoreQuestionParams->getQuestionNumber();
        $optionsPack = $scoreQuestionParams->getVarsForScorePart();

        // We need to "unpack" these into locally scoped variables.
        foreach ($optionsPack as $k => $v) {
            ${$k} = $v;
        }
        foreach ($additionalPackagedVars as $k => $v) {
            ${$k} = $v;
        }

        /*
         * Begin scoring.
         */

        $partLastAnswerAsGiven = array();
        $partLastAnswerAsNumber = array();
        if (isset($answeights)) {
  				if (!is_array($answeights)) {
  					$answeights = explode(",",$answeights);
  				}
  				$answeights = array_map('trim', $answeights);
  				if (count($answeights) != count($anstypes)) {
  					$answeights = array_fill(0, count($anstypes), 1);
  				}
  			} else {
  				if (count($anstypes)>1) {
  					$answeights = array_fill(0, count($anstypes), 1);
  				} else {
  					$answeights = array(1);
  				}
  			}
        $scores = array();
        $raw = array();
        $accpts = 0;
        $answeightTot = array_sum($answeights);
        foreach ($anstypes as $partnum => $anstype) {
            $inputReferenceNumber = ($qnidx + 1) * 1000 + $partnum;

            $scoreQuestionParams
                ->setAnswerType($anstype)
                ->setIsMultiPartQuestion(true)
                ->setQuestionPartNumber($partnum);

            // TODO: Do this a different/better way. (not accessing _POST)
			      $scoreQuestionParams->setGivenAnswer($_POST["qn$inputReferenceNumber"]);

            $scorePart = ScorePartFactory::getScorePart($scoreQuestionParams);
            $scorePartResult = $scorePart->getResult();
            $raw[$partnum] = $scorePartResult->getRawScore();

            if (isset($scoremethod) && $scoremethod == 'acct') {
                if (($anstype == 'string' || $anstype == 'number') && $answer[$partnum] === '') {
                    $scores[$partnum] = $raw[$partnum] - 1;  //0 if correct, -1 if wrong
                    // scores isn't actually used - only raw is
                    // Need to indicate to score engine to not count this if
                    // stu was correct.
                    // -1 means "don't count it", 0 means it was wrong
                    // orig is 0 for wrong, 1 for right, so just need to invert it
                    $raw[$partnum] = -1*$raw[$partnum];
                } else {
                    $scores[$partnum] = $raw[$partnum];
                    $accpts++;
                }
            } else {
                $scores[$partnum] = ($raw[$partnum] < 0) ? 0 : round($raw[$partnum] * $answeights[$partnum]/$answeightTot, 4);
            }

            $raw[$partnum] = round($raw[$partnum], 2);
            $partLastAnswerAsGiven[$partnum] = $scorePartResult->getLastAnswerAsGiven();
            $partLastAnswerAsNumber[$partnum] = $scorePartResult->getLastAnswerAsNumber();
        }

        if (isset($scoremethod) && $scoremethod == "singlescore") {
            return array(
                'scores' => array(round(array_sum($scores), 3)),
                'rawScores' => $raw,
                'lastAnswerAsGiven' => $partLastAnswerAsGiven,
                'lastAnswerAsNumber' => $partLastAnswerAsNumber,
            );
        } else if (isset($scoremethod) && $scoremethod == "allornothing") {
            if (array_sum($scores) < .98) {
                return array(
                    'scores' => array(0),
                    'rawScores' => $raw,
                    'lastAnswerAsGiven' => $partLastAnswerAsGiven,
                    'lastAnswerAsNumber' => $partLastAnswerAsNumber,
                );
            } else {
                return array(
                    'scores' => array(1),
                    'rawScores' => $raw,
                    'lastAnswerAsGiven' => $partLastAnswerAsGiven,
                    'lastAnswerAsNumber' => $partLastAnswerAsNumber,
                );
            }
        } else if (isset($scoremethod) && $scoremethod == "acct") {
            $sc = round(array_sum($scores) / $accpts, 3);
            return (array(
                'scores' => array($sc),
                'rawScores' => $raw,
                'lastAnswerAsGiven' => $partLastAnswerAsGiven,
                'lastAnswerAsNumber' => $partLastAnswerAsNumber,
            ));
        } else {
            return array(
                'scores' => $scores,
                'rawScores' => $raw,
                'lastAnswerAsGiven' => $partLastAnswerAsGiven,
                'lastAnswerAsNumber' => $partLastAnswerAsNumber,
            );
        }
    }

    /**
     * Score a non-multipart question's answers.
     *
     * @param ScoreQuestionParams $scoreQuestionParams
     * @param array $qdata The question's data as provided by loadQuestionData().
     *                     Used to determine if a question is conditional.
     * @return array An array of scores.
     */
    private function scorePartNonMultiPart(ScoreQuestionParams $scoreQuestionParams,
                                           array $qdata): array
    {
        $qnidx = $scoreQuestionParams->getQuestionNumber();

        $scoreQuestionParams
            ->setAnswerType($qdata['qtype'])
            ->setIsMultiPartQuestion(false);

        $scorePart = ScorePartFactory::getScorePart($scoreQuestionParams);
        $scorePartResult = $scorePart->getResult();
        $score = $scorePartResult->getRawScore();

        if (isset($scoremethod) && $scoremethod == "allornothing") {
            if ($score < .98) {
                $score = 0;
            }
        }

        return array(
            'scores' => array(round($score, 3)),
            'rawScores' => array(round($score, 2)),
            'lastAnswerAsGiven' => array($scorePartResult->getLastAnswerAsGiven()),
            'lastAnswerAsNumber' => array($scorePartResult->getLastAnswerAsNumber()),
        );
    }

    /**
     * Determine if a question is a multi-part question or not.
     *
     * @param array $questionData
     * @return bool True = Question is multi-part. False = It's not.
     */
    private function isMultipartQuestion(array $questionData)
    {
        return ($questionData['qtype'] == "multipart"
            || $questionData['qtype'] == 'conditional');
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
     * Warning handler for evals.
     *
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     * @param array $errcontext
     * @return bool
     */
    public function evalErrorHandler(int $errno, string $errstr, string $errfile,
                                     int $errline, array $errcontext): bool
    {
        ErrorHandler::evalErrorHandler($errno, $errstr, $errfile, $errline, $errcontext);

        if (E_WARNING == $errno || E_ERROR == $errno) {
            $this->addError(sprintf('Caught %s in the question code: %s on line %s in file %s',
                ErrorHandler::ERROR_CODES[$errno],
                $errstr, $errline, $errfile));
        }

        // True = Don't execute the PHP internal error handler.
        // False = Populate $php_errormsg.
        // Reference: https://secure.php.net/manual/en/function.set-error-handler.php
        return true;
    }

    /**
     * Exception handler for evals.
     *
     * @param Throwable $t
     */
    public function evalExceptionHandler(Throwable $t): void
    {
        ErrorHandler::evalExceptionHandler($t);

        if ($this->userRights > 10) {
            $this->addError('Caught error in evaluating the code in this question: '.
            $t->getMessage());
        } else {
            $this->addError('Something went wrong with this question.  Tell your teacher.');
        }
    }
}

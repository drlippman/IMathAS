<?php

namespace IMathAS\assess2\questions;

require_once(__DIR__ . 'ErrorHandler.php');
require_once(__DIR__ . '/QuestionHtmlGenerator.php');
require_once(__DIR__ . '/models/ScoreQuestionParams.php');

use PDO;
use RuntimeException;

use Rand;

use IMathAS\assess2\questions\models\ScoreQuestionParams;

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
    private $dbh;
    private $randWrapper;

    public function __construct(PDO $dbh, Rand $randWrapper)
    {
        $this->dbh = $dbh;
        $this->randWrapper = $randWrapper;
    }

    /**
     * Score a question. This method wraps another method around error handlers.
     *
     * @param ScoreQuestionParams $scoreQuestionParams Params for scoring this question.
     */
    public function scoreQuestion(ScoreQuestionParams $scoreQuestionParams)
    {
        set_error_handler('ErrorHandler::evalErrorHandler');
        set_exception_handler('ErrorHandler::evalExceptionHandler');

        $this->scoreQuestionCatchErrors($scoreQuestionParams);

        restore_error_handler();
        restore_exception_handler();
    }

    /**
     * Score a question.
     *
     * @param ScoreQuestionParams $scoreQuestionParams Params for scoring this question.
     */
    private function scoreQuestionCatchErrors(ScoreQuestionParams $scoreQuestionParams)
    {
        // This lets various parts of IMathAS know that question HTML is
        // NOT being generated for display.
        $GLOBALS['inquestiondisplay'] = false;

        if (!isset($_SESSION['choicemap'])) {
            $_SESSION['choicemap'] = array();
        }

        // FIXME: Where is this used? Do we need this in scope?
        $myrights = $scoreQuestionParams->getUserRights();

        $qdata = $this->loadQuestionData($scoreQuestionParams);

        list($stuanswers, $stuanswersval) = $this->generateStudentAnswers($scoreQuestionParams);

        if ($this->isMultipartQuestion($qdata)) {
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

        // TODO: Refactor error handling to use custom error handlers.
        // TODO: Also need to add student messages in custom error handlers.
        $preevalerror = error_get_last();
        try {
            $res1 = eval(interpret('control', $qdata['qtype'], $qdata['control']));
            $this->randWrapper->srand($scoreQuestionParams->getQuestionSeed() + 1);
            $res2 = eval(interpret('answer', $qdata['qtype'], $qdata['answer']));
        } catch (Throwable $t) {
            $res1 = 'caught';
            $res2 = 'caught';
            if ($myrights > 10) {
                echo '<p>Caught error in evaluating the code in this question: ';
                echo Sanitize::encodeStringForDisplay($t->getMessage());
                echo '</p>';
            }
        } catch (Exception $e) {
            $res1 = false;
            $res2 = false;
        }
        if ($res1 === false || $res2 === false) {
            if ($myrights > 10) {
                $error = error_get_last();
                echo '<p>Caught error in the question code: ', $error['message'], ' on line ', $error['line'], '</p>';
            } else {
                echo '<p>Something went wrong with this question.  Tell your teacher.</p>';
            }
        } else {
            $error = error_get_last();
            if ($error && $error != $preevalerror && $myrights > 10) {
                if ($error['type'] == $_ERROR) {
                    echo '<p>Caught error in the question code: ', $error['message'], ' on line ', $error['line'], '</p>';
                } else if ($error['type'] == E_WARNING) {
                    echo '<p>Caught warning in the question code: ', $error['message'], ' on line ', $error['line'], '</p>';
                }
            }
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

        if (isset($GLOBALS['qdatafordisplayq'])) {
            $questionData = $GLOBALS['qdatafordisplayq'];
        } else if (isset($GLOBALS['qi']) && isset($GLOBALS['qi'][$GLOBALS['questions'][$questionNumber]]['qtext'])) {
            $questionData = $GLOBALS['qi'][$GLOBALS['questions'][$questionNumber]];
        } else {
            $stm = $this->dbh->prepare("SELECT qtype,control,answer FROM imas_questionset WHERE id=:id");
            $stm->execute(array(':id' => $dbQuestionId));
            $questionData = $stm->fetch(PDO::FETCH_ASSOC);
        }

        if (!$questionData) {
            throw new RuntimeException(
                sprintf('Failed to get question data for question ID %d. PDO error: %s',
                    $dbQuestionId, implode(':', $this->dbh->errorInfo()))
            );
        }

        return $questionData;
    }

    /**
     * Generate $stuanswers and $stuanswersval.
     *
     * FIXME: Need a better method description.
     *
     * @param ScoreQuestionParams $scoreQuestionParams Params for scoring this question.
     * @return array [0] = $stuanswers, [1] = $stuanswersval
     */
    private function generateStudentAnswers(ScoreQuestionParams $scoreQuestionParams): array
    {
        // FIXME: Does this need to be in $GLOBALS?
        if (isset($GLOBALS['lastanswers'])) {
            foreach ($GLOBALS['lastanswers'] as $iidx => $ar) {
                $arv = explode('##', $ar);
                $arv = $arv[count($arv) - 1];
                $arv = explode('&', $arv);
                if (count($arv) == 1) {
                    $arv = $arv[0];
                }
                if (is_array($arv)) {
                    foreach ($arv as $kidx => $arvp) {
                        //if (is_numeric($arvp)) {
                        if ($arvp === '') {
                            $stuanswers[$iidx + 1][$kidx] = null;
                        } else {
                            if (strpos($arvp, '$f$') !== false) {
                                $tmp = explode('$f$', $arvp);
                                $arvp = $tmp[0];
                            }
                            if (strpos($arvp, '$!$') !== false) {
                                $arvp = explode('$!$', $arvp);
                                $arvp = $arvp[1];
                                if (is_numeric($arvp)) {
                                    $arvp = intval($arvp);
                                }
                            }
                            if (strpos($arvp, '$#$') !== false) {
                                $tmp = explode('$#$', $arvp);
                                $arvp = $tmp[0];
                                $stuanswersval[$iidx + 1][$kidx] = $tmp[1];
                            }
                            $stuanswers[$iidx + 1][$kidx] = $arvp;
                        }
                    }
                } else {
                    if ($arv === '' || $arv === 'ReGen') {
                        $stuanswers[$iidx + 1] = null;
                    } else {
                        if (strpos($arv, '$f$') !== false) {
                            $tmp = explode('$f$', $arv);
                            $arv = $tmp[0];
                        }
                        if (strpos($arv, '$!$') !== false) {
                            $arv = explode('$!$', $arv);
                            $arv = $arv[1];
                            if (is_numeric($arv)) {
                                $arv = intval($arv);
                            }
                        }
                        if (strpos($arv, '$#$') !== false) {
                            $tmp = explode('$#$', $arv);
                            $arv = $tmp[0];
                            $stuanswersval[$iidx + 1] = $tmp[1];
                        }
                        $stuanswers[$iidx + 1] = $arv;
                    }
                }
            }
        }

        $thisq = $scoreQuestionParams->getQuestionNumber() + 1;
        unset($stuanswers[$thisq]);  //unset old stuanswer for this question

        return array($stuanswers, $stuanswersval);
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
            if ($prefix == 'tc' || $prefix == 'qn') {
                $partnum = intval(substr($postk, 2));
                if (floor($partnum / 1000) == $thisq) {
                    $kidx = round($partnum - 1000 * floor($partnum / 1000));
                    $postpartstoprocess[$partnum] = $kidx;
                }
            }
        }

        foreach ($postpartstoprocess as $partnum => $kidx) {
            if (isset($_POST["tc$partnum"])) {
                $stuanswers[$thisq][$kidx] = $_POST["tc$partnum"];
                if ($_POST["qn$partnum"] === '') {
                    $stuanswersval[$thisq][$kidx] = null;
                    $stuanswers[$thisq][$kidx] = null;
                } else if (is_numeric($_POST["qn$partnum"])) {
                    $stuanswersval[$thisq][$kidx] = floatval($_POST["qn$partnum"]);
                } else if (substr($_POST["qn$partnum"], 0, 2) == '[(') { //calcmatrix
                    $stuav = str_replace(array('(', ')', '[', ']'), '', $_POST["qn$partnum"]);
                    $stuanswersval[$thisq][$kidx] = str_replace(',', '|', $stuav);
                } else {
                    $stuanswersval[$thisq][$kidx] = $_POST["qn$partnum"];
                }
            } else if (isset($_POST["qn$partnum"])) {
                if (isset($_POST["qn$partnum-0"])) { //calcmatrix with matrixsize
                    $tmp = array();
                    $spc = 0;
                    while (isset($_POST["qn$partnum-$spc"])) {
                        $tmp[] = $_POST["qn$partnum-$spc"];
                        $spc++;
                    }
                    $stuanswers[$thisq][$kidx] = implode('|', $tmp);
                    $stuav = str_replace(array('(', ')', '[', ']'), '', $_POST["qn$partnum"]);
                    $stuanswersval[$thisq][$kidx] = str_replace(',', '|', $stuav);
                } else {
                    $stuanswers[$thisq][$kidx] = $_POST["qn$partnum"];
                    if ($_POST["qn$partnum"] === '') {
                        $stuanswersval[$thisq][$kidx] = null;
                        $stuanswers[$thisq][$kidx] = null;
                    } else if (is_numeric($_POST["qn$partnum"])) {
                        $stuanswersval[$thisq][$kidx] = floatval($_POST["qn$partnum"]);
                    }
                    if (isset($_SESSION['choicemap'][$partnum])) {
                        if (is_array($stuanswers[$thisq][$kidx])) { //multans
                            foreach ($stuanswers[$thisq][$kidx] as $k => $v) {
                                $stuanswers[$thisq][$kidx][$k] = $_SESSION['choicemap'][$partnum][$v];
                            }
                            $stuanswers[$thisq][$kidx] = implode('|', $stuanswers[$thisq][$kidx]);
                        } else {
                            $stuanswers[$thisq][$kidx] = $_SESSION['choicemap'][$partnum][$stuanswers[$thisq][$kidx]];
                            if ($stuanswers[$thisq][$kidx] === null) {
                                $stuanswers[$thisq][$kidx] = 'NA';
                            }
                        }
                    }
                }
            } else if (isset($_POST["qn$partnum-0"])) {
                $tmp = array();
                $spc = 0;
                while (isset($_POST["qn$partnum-$spc"])) {
                    $tmp[] = $_POST["qn$partnum-$spc"];
                    $spc++;
                }
                $stuanswers[$thisq][$kidx] = implode('|', $tmp);
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

        if (isset($_POST["tc$qnidx"])) {
            $stuanswers[$thisq] = $_POST["tc$qnidx"];
            if (is_numeric($_POST["qn$qnidx"])) {
                $stuanswersval[$thisq] = floatval($_POST["qn$qnidx"]);
            } else if (substr($_POST["qn$qnidx"], 0, 2) == '[(') { //calcmatrix
                $stuav = str_replace(array('(', ')', '[', ']'), '', $_POST["qn$qnidx"]);
                $stuanswersval[$thisq] = str_replace(',', '|', $stuav);
            } else {
                $stuanswersval[$thisq] = $_POST["qn$qnidx"];
            }
        } else if (isset($_POST["qn$qnidx"])) {
            if (isset($_POST["qn$qnidx-0"])) { //calcmatrix with matrixsize
                $tmp = array();
                $spc = 0;
                while (isset($_POST["qn$qnidx-$spc"])) {
                    $tmp[] = $_POST["qn$qnidx-$spc"];
                    $spc++;
                }
                $stuanswers[$thisq] = implode('|', $tmp);
                $stuav = str_replace(array('(', ')', '[', ']'), '', $_POST["qn$qnidx"]);
                $stuanswersval[$thisq] = str_replace(',', '|', $stuav);
            } else {
                $stuanswers[$thisq] = $_POST["qn$qnidx"];
                if (is_numeric($_POST["qn$qnidx"])) {
                    $stuanswersval[$thisq] = floatval($_POST["qn$qnidx"]);
                }
                if (isset($_SESSION['choicemap'][$qnidx])) {
                    if (is_array($stuanswers[$thisq])) { //multans
                        foreach ($stuanswers[$thisq] as $k => $v) {
                            $stuanswers[$thisq][$k] = $_SESSION['choicemap'][$qnidx][$v];
                        }
                        $stuanswers[$thisq] = implode('|', $stuanswers[$thisq]);
                    } else {
                        $stuanswers[$thisq] = $_SESSION['choicemap'][$qnidx][$stuanswers[$thisq]];
                    }
                }
            }
        } else if (isset($_POST["qn$qnidx-0"])) { //matrix w answersize or matching
            $tmp = array();
            $spc = 0;
            while (isset($_POST["qn$qnidx-$spc"])) {
                $tmp[] = $_POST["qn$qnidx-$spc"];
                $spc++;
            }
            $stuanswers[$thisq] = implode('|', $tmp);
        }

        return array($stuanswers, $stuanswersval);
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
}

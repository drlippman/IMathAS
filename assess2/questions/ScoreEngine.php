<?php

namespace IMathAS\assess2\questions;

require_once(__DIR__ . "/QuestionHtmlGenerator.php");
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
 *   - Most code in here is being extracted as-is from displayq3.php,
 *     unless refactoring is simple or is necessary for OO-ness.
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

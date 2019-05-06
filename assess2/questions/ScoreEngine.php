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

    public function __construct(PDO $dbh, Rand $randWrapper)
    {
        $this->dbh = $dbh;
        $this->randWrapper = $randWrapper;
    }

    /**
     * Score a question. This method wraps another method around error handlers.
     *
     * @param ScoreQuestionParams $scoreQuestionParams Params for scoring this question.
     * @return array
     */
    public function scoreQuestionWrapper(ScoreQuestionParams $scoreQuestionParams): array
    {
        set_error_handler(array($this, 'evalErrorHandler'));
        set_exception_handler(array($this, 'evalExceptionHandler'));

        // User's rights are used during exception handling.
        $this->userRights = $scoreQuestionParams->getUserRights();

        $results = $this->scoreQuestionAllParts($scoreQuestionParams);

        restore_error_handler();
        restore_exception_handler();

        return $results;
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

        $this->randWrapper->srand($scoreQuestionParams->getQuestionSeed());

        if (!isset($_SESSION['choicemap'])) {
            $_SESSION['choicemap'] = array();
        }

        $qdata = $this->loadQuestionData($scoreQuestionParams);

        // FIXME: Found while answering a question in UI: $stuanswers is null??
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

        set_error_handler(array($this, 'evalErrorHandler'));
        set_exception_handler(array($this, 'evalExceptionHandler'));

        // User's rights are used during exception handling.
        $this->userRights = $scoreQuestionParams->getUserRights();

        // These may be needed in evals.
        $qnidx = $scoreQuestionParams->getQuestionNumber();
        $attemptn = $scoreQuestionParams->getAttemptNumber();

        eval(interpret('control', $qdata['qtype'], $qdata['control']));
        $this->randWrapper->srand($scoreQuestionParams->getQuestionSeed() + 1);
        eval(interpret('answer', $qdata['qtype'], $qdata['answer']));

        restore_error_handler();
        restore_exception_handler();

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
         * Look to see if we should splice off some autosaved answers.
         */

        if ($GLOBALS['lastanswers'][$qnidx] != '') {
            $templastans = explode('##', $GLOBALS['lastanswers'][$qnidx]);
            $countregens = count(array_keys($templastans, 'ReGen', true));
            $tosplice = ($countregens + $attemptn) - count($templastans);
            if ($tosplice < 0) {
                array_splice($templastans, $tosplice);
                $GLOBALS['lastanswers'][$qnidx] = implode('##', $templastans);
            }
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

        if ($qdata['qtype'] == "multipart") {
            $score = $this->scorePartMultiPart($scoreQuestionParams,
                $additionalVarsForScoring);
        } else {
            $score = $this->scorePartNonMultiPart($scoreQuestionParams, $qdata);
        }

        return $score;
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
        $stuanswers = array();
        $stuanswersval = array();

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

        $partla = array();
        if (isset($answeights)) {
            if (!is_array($answeights)) {
                $answeights = explode(",", $answeights);
            }

            $answeights = array_map('trim', $answeights);
            $localsum = array_sum($answeights);
            if ($localsum == 0) {
                $localsum = 1;
            }
            foreach ($answeights as $partnum => $vval) {
                $answeights[$partnum] = $vval / $localsum;
            }
        } else {
            if (count($anstypes) > 1) {
                if ($qnpointval == 0) {
                    $qnpointval = 1;
                }
                $answeights = array_fill(0, count($anstypes) - 1, round($qnpointval / count($anstypes), 2));
                $answeights[] = $qnpointval - array_sum($answeights);
                foreach ($answeights as $partnum => $vval) {
                    $answeights[$partnum] = $vval / $qnpointval;
                }
            } else {
                $answeights = array(1);
            }
        }
        $scores = array();
        $raw = array();
        $accpts = 0;
        foreach ($anstypes as $partnum => $anstype) {
            $inputReferenceNumber = ($qnidx + 1) * 1000 + $partnum;

            $scoreQuestionParams
                ->setAnswerType($anstype)
                ->setIsMultiPartQuestion(true)
                ->setQuestionPartNumber($partnum);

			// TODO: Do this a different/better way. (not accessing _POST)
			$scoreQuestionParams->setGivenAnswer($_POST["qn$inputReferenceNumber"]);

            $scorePart = ScorePartFactory::getScorePart($scoreQuestionParams);
            $raw[$partnum] = $scorePart->getScore();

            if (isset($scoremethod) && $scoremethod == 'acct') {
                if (($anstype == 'string' || $anstype == 'number') && $answer[$partnum] === '') {
                    $scores[$partnum] = $raw[$partnum] - 1;  //0 if correct, -1 if wrong
                } else {
                    $scores[$partnum] = $raw[$partnum];
                    $accpts++;
                }
            } else {
                $scores[$partnum] = ($raw[$partnum] < 0) ? 0 : round($raw[$partnum] * $answeights[$partnum], 4);
            }
            $raw[$partnum] = round($raw[$partnum], 2);
            $partla[$partnum] = $GLOBALS['partlastanswer'];
        }

        $partla = str_replace('&', '', $partla);
        $partla = preg_replace('/#+/', '#', $partla);

        if ($GLOBALS['lastanswers'][$qnidx] == '') {
            $GLOBALS['lastanswers'][$qnidx] = implode("&", $partla);
        } else {
            $GLOBALS['lastanswers'][$qnidx] .= '##' . implode("&", $partla);
        }
        if (isset($scoremethod) && $scoremethod == "singlescore") {
            return array(round(array_sum($scores), 3), implode('~', $raw));
        } else if (isset($scoremethod) && $scoremethod == "allornothing") {
            if (array_sum($scores) < .98) {
                return array(0, implode('~', $raw));
            } else {
                return array(1, implode('~', $raw));
            }
        } else if (isset($scoremethod) && $scoremethod == "acct") {
            $sc = round(array_sum($scores) / $accpts, 3);
            return (array($sc, implode('~', $raw)));
        } else {
            return array(implode('~', $scores), implode('~', $raw));
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
        $score = $scorePart->getScore();

        if (isset($scoremethod) && $scoremethod == "allornothing") {
            if ($score < .98) {
                $score = 0;
            }
        }
        if ($qdata['qtype'] != 'conditional') {
            $GLOBALS['partlastanswer'] = str_replace('&', '', $GLOBALS['partlastanswer']);
            $GLOBALS['partlastanswer'] = preg_replace('/#+/', '#', $GLOBALS['partlastanswer']);
        }
        if ($GLOBALS['lastanswers'][$qnidx] == '') {
            $GLOBALS['lastanswers'][$qnidx] = $GLOBALS['partlastanswer'];
        } else {
            $GLOBALS['lastanswers'][$qnidx] .= '##' . $GLOBALS['partlastanswer'];
        }

        return array(round($score, 3), round($score, 2));
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
            printf('<p>Caught %s in the question code: %s on line %s</p>',
                ErrorHandler::ERROR_CODES[$errno],
                Sanitize::encodeStringForDisplay($errstr), $errline);
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
            echo '<p>Caught error in evaluating the code in this question: ';
            echo Sanitize::encodeStringForDisplay($t->getMessage());
            echo '</p>';
        } else {
            echo '<p>Something went wrong with this question.  Tell your teacher.</p>';
        }
    }
}

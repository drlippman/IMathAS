<?php

namespace IMathAS\assess2\questions;

require_once(__DIR__ . '/answerboxes/AnswerBoxParams.php');
require_once(__DIR__ . '/answerboxes/AnswerBoxFactory.php');
require_once(__DIR__ . '/models/Question.php');

use PDO;

use Rand;

use IMathAS\assess2\questions\answerboxes\AnswerBoxFactory;
use IMathAS\assess2\questions\answerboxes\AnswerBoxParams;
use IMathAS\assess2\questions\models\Question;
use IMathAS\assess2\questions\models\QuestionParams;
use IMathAS\assess2\questions\models\ShowAnswer;

/**
 * Class QuestionHtmlGenerator Generates questions and its components as HTML.
 *
 * Notes:
 *   - This is a refactor of displayq2.php.
 *   - Most code in here is being extracted as-is from displayq2.php,
 *     unless refactoring is simple or is necessary for OO-ness.
 */
class QuestionHtmlGenerator
{
    /**
     * Allowed variable names used by question writers in eval'd question code
     * that need to be packaged up and passed to other methods or objects.
     */
    const ALLOWED_QUESTION_WRITER_VARS = array(
        'ansprompt',
        'anstypes',
        'answeights',
        'answer',
        'answerboxsize',
        'answerformat',
        'answers',
        'answersize',
        'answertitle',
        'background',
        'displayformat',
        'domain',
        'grid',
        'helptext',
        'hidepreview',
        'matchlist',
        'noshuffle',
        'questions',
        'questiontitle',
        'reqdecimals',
        'reqsigfigs',
        'scoremethod',
        'showanswer',
        'showanswerstyle',
        'snaptogrid',
        'strflags',
        'variables',
    );

    // Variables that need to be packed up and passed to the answerbox generator.
    const VARS_FOR_ANSWERBOX_GENERATOR = array(
        'answeights',   // Overridden by question writer if exists.
    );

    private $dbh;
    private $randWrapper;
    private $questionParams;

    private $errors = array();

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
     * Get all errors encountered during question HTML generation.
     *
     * @return array A simple array of error messages.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Eval the question code (typically written by an instructor) and
     * generate all components for the question as HTML.
     *
     * Notes:
     * - Many locally scoped things are created in here during those evals.
     * - Some variable names must not be changed, as they are expected in
     *   question writer and eval'd code.
     *
     * TODO: Need to break this method up.
     *
     * @return Question An instance of Question with all components.
     */
    public function getQuestion(): Question
    {
        $showAnswer = $this->questionParams->getShowAnswer();

        // The following variables are expected by the question writer's code in interpret().
        $stuanswers = $this->questionParams->getAllQuestionAnswers();  // Contains ALL question answers.
        $stuanswersval = $this->questionParams->getAllQuestionAnswersAsNum();
        $scorenonzero = $this->questionParams->getScoreNonZero();
        $scoreiscorrect = $this->questionParams->getScoreIsCorrect();
        $attemptn = $this->questionParams->getStudentAttemptNumber();
        $partattemptn = $this->questionParams->getStudentPartAttemptCount();
        $qdata = $this->questionParams->getQuestionData();
        $showHints = $this->questionParams->getShowHints();

        if ($qdata['hasimg'] > 0) {
            // We need to "unpack" this into locally scoped variables.
            foreach ($this->getImagesAsHtml() as $k => $v) {
                ${$k} = $v;
            }
        }

        // Use this question's RNG seed.
        $this->randWrapper->srand($this->questionParams->getQuestionSeed());

        // Eval the question writer's question code.
        // In older questions, code is broken up into three parts.
        // In "modern" questions, the last two parts are empty.
        eval(interpret('control', $qdata['qtype'], $qdata['control']));
        eval(interpret('qcontrol', $qdata['qtype'], $qdata['qcontrol']));
        eval(interpret('answer', $qdata['qtype'], $qdata['answer']));

        $toevalqtxt = interpret('qtext', $qdata['qtype'], $qdata['qtext']);
        $toevalqtxt = str_replace('\\', '\\\\', $toevalqtxt);
        $toevalqtxt = str_replace(array('\\\\n', '\\\\"', '\\\\$', '\\\\{'),
            array('\\n', '\\"', '\\$', '\\{'), $toevalqtxt);

        $toevalsoln = interpret('qtext', $qdata['qtype'], $qdata['solution']);
        $toevalsoln = str_replace('\\', '\\\\', $toevalsoln);
        $toevalsoln = str_replace(array('\\\\n', '\\\\"', '\\\\$', '\\\\{'),
            array('\\n', '\\"', '\\$', '\\{'), $toevalsoln);

        // Reset the RNG to a known state after the question code has been eval'd.
        $this->randWrapper->srand($this->questionParams->getQuestionSeed() + 2);

        /*
         * Correct mistakes made by question writers.
         */

        if (isset($choices) && !isset($questions)) {
            $questions = $choices;
        }
        if (isset($variable) && !isset($variables)) {
            $variables = $variable;
        }

        /*
         * Massage some data.
         */

        if (isset($anstypes)) {
            // The question writer may have provided values as a comma delimited string.
            if (!is_array($anstypes)) {
                $anstypes = explode(",", $anstypes);
            }
            $anstypes = array_map('trim', $anstypes);
        }

        /*
         * Package local variables for the answer box generator.
         */

        // These may have been defined by the question writer.
        $questionWriterVars = array();
        foreach (self::ALLOWED_QUESTION_WRITER_VARS as $optionKey) {
            if (!isset(${$optionKey})) {
                continue;
            }

            if ('answerformat' == $optionKey) {
                $answerformat = str_replace(' ', '', $answerformat);
            }

            $questionWriterVars[$optionKey] = ${$optionKey};
        }

        // These are also needed by the answer box generator.
        $varsForAnswerBoxGenerator = array();
        foreach (self::VARS_FOR_ANSWERBOX_GENERATOR as $var) {
            if (!isset(${$var})) {
                continue;
            }
            $varsForAnswerBoxGenerator[$var] = ${$var};
        }

        /*
         * Calculate answer weights and generate answer boxes.
         */

        // $answerbox must not be renamed, it is expected in eval'd code.
        $answerbox = $jsParams = $entryTips = $displayedAnswersForParts = $previewLocations = null;

        if ($qdata['qtype'] == "multipart" || $qdata['qtype'] == 'conditional') {
            // $anstypes is question writer defined.
            if (!isset($anstypes) && $GLOBALS['myrights'] > 10) {
                $this->addError('Error in question: missing $anstypes for multipart or conditional question');
                $anstypes = array("number");
            }

            // Calculate answer weights.
            if ($qdata['qtype'] == "multipart") {
                // $answeights - question writer defined
                if (isset($answeights)) {
                    if (!is_array($answeights)) {
                        $answeights = explode(",", $answeights);
                    }
                    $answeights = array_map('trim', $answeights);
                    $localsum = array_sum($answeights);
                    if ($localsum == 0) {
                        $localsum = 1;
                    }
                    foreach ($answeights as $atIdx => $vval) {
                        $answeights[$atIdx] = $vval / $localsum;
                    }
                } else {
                    if (count($anstypes) > 1) {
                        if ($qnpointval == 0) {
                            $qnpointval = 1;
                        }
                        $answeights = array_fill(
                            0,
                            count($anstypes) - 1,
                            round($qnpointval / count($anstypes), 3)
                        );
                        $answeights[] = $qnpointval - array_sum($answeights);
                        foreach ($answeights as $atIdx => $vval) {
                            $answeights[$atIdx] = $vval / $qnpointval;
                        }
                    } else {
                        $answeights = array(1);
                    }
                }
            }

            // Get the answers to all parts of this question.
            $lastAnswersAllParts = $stuanswers[$this->questionParams->getQuestionNumber()];

            /*
			 * Original displayq2.php notes:
			 *
			 * $questionColor   // Orig: $qcol in displayq2.php
             *                     Question color (green, yellow, red)
			 * $answerBoxes     // Orig: $answerbox in displayq2.php
             *                     Contains answer boxes. (input fields)
             *                     Can be a string or an array of strings.
             *                     Non-multipart question = string
             *                     Multipart question = array of strings
			 * $entryTips[]     // Tooltips that appear under input fields.
			 * $displayedAnswersForParts[]
             *                  // Orig: $shanspt in displayq2.php
             *                     "show answer part"; the displayed answer for
             *                     each question part (not the scored answer)
			 * $previewLocations[]
             *                  // Orig: $previewloc in displayq2.php
             *                     Sets if the preview is displayed after the
			 *                     question (default) or in a location defined
			 *                     by the question writer.
			 */

            // Generate answer boxes. (multipart question)
            foreach ($anstypes as $atIdx => $anstype) {
                $questionColor = $this->isMultipart()
                    ? $this->getAnswerColorFromRawScore(
                        $this->questionParams->getLastRawScores(), $atIdx, $answeights[$atIdx])
                    : '';

                $answerBoxParams = new AnswerBoxParams();
                $answerBoxParams
                    ->setQuestionWriterVars($questionWriterVars)
                    ->setVarsForAnswerBoxGenerator($varsForAnswerBoxGenerator)
                    ->setAnswerType($anstype)
                    ->setQuestionNumber($this->questionParams->getQuestionNumber())
                    ->setIsMultiPartQuestion($this->isMultipart())
                    ->setQuestionPartNumber($atIdx)
                    ->setStudentLastAnswers($lastAnswersAllParts[$atIdx])
                    ->setColorboxKeyword($questionColor);

                $answerBoxGenerator = AnswerBoxFactory::getAnswerBoxGenerator($answerBoxParams);
                $answerBoxGenerator->generate();

                $answerbox[$atIdx] = $answerBoxGenerator->getAnswerBox();
                $entryTips[$atIdx] = $answerBoxGenerator->getEntryTip();
                $qnRef = ($this->questionParams->getQuestionNumber()+1)*1000 + $atIdx;
                $jsParams[$qnRef] = $answerBoxGenerator->getJsParams();
                $jsParams[$qnRef]['qtype'] = $anstype;
                $displayedAnswersForParts[$atIdx] = $answerBoxGenerator->getCorrectAnswerForPart();
                $previewLocations[$atIdx] = $answerBoxGenerator->getPreviewLocation();
            }
        } else {
            // Generate answer boxes. (non-multipart question)
            $questionColor = $this->getAnswerColorFromRawScore(
                $this->questionParams->getLastRawScores(), 0, 1);

            $lastAnswer = $stuanswers[$this->questionParams->getQuestionNumber()];

            $answerBoxParams = new AnswerBoxParams();
            $answerBoxParams
                ->setQuestionWriterVars($questionWriterVars)
                ->setVarsForAnswerBoxGenerator($varsForAnswerBoxGenerator)
                ->setAnswerType($qdata['qtype'])
                ->setQuestionNumber($this->questionParams->getQuestionNumber())
                ->setIsMultiPartQuestion(false)
                ->setStudentLastAnswers($lastAnswer)
                ->setColorboxKeyword($questionColor);

            $answerBoxGenerator = AnswerBoxFactory::getAnswerBoxGenerator($answerBoxParams);
            $answerBoxGenerator->generate();

            $answerbox = $answerBoxGenerator->getAnswerBox();
            $entryTips[0] = $answerBoxGenerator->getEntryTip();
            $qnRef = $this->questionParams->getQuestionNumber();
            $jsParams[$qnRef] = $answerBoxGenerator->getJsParams();
            $jsParams[$qnRef]['qtype'] = $qdata['qtype'];
            $displayedAnswersForParts[0] = $answerBoxGenerator->getCorrectAnswerForPart();
            $previewLocations = $answerBoxGenerator->getPreviewLocation();
        }

        /*
         * For conditional question types, the entire question or answer box
         * block needs to be surrounded with a colored border when scored.
         *
         * Answer box generation still needs to happen, but we don't change
         * the color of those answer boxes. We do this instead.
         */

        if ($qdata['qtype'] == 'conditional') {
            $questionColor = $this->getAnswerColorFromRawScore(
                $this->questionParams->getLastRawScores(), 0, 1);
            if ($questionColor != '') {
                $evalQuestionText = $toevalqtxt . str_replace('"', '\\"',
                        $this->getColoredMark($questionColor));

                if (strpos($toevalqtxt, '<div') !== false || strpos($toevalqtxt, '<table') !== false) {
                    $toevalqtxt = sprintf(
                        '<div class=\\"%s\\" style=\\"display:block\\">%s</div>',
                        $questionColor, $evalQuestionText);
                } else {
                    $toevalqtxt = sprintf('<div class="%s">%s</div>',
                        $questionColor, $evalQuestionText);
                }
            }
            if (!isset($showanswer)) {
                $showanswer = _('Answers may vary');
            }
        }

        /*
         * Get hint HTML.
         */

        if (isset($hints) && is_array($hints) && count($hints) > 0 && $showHints) {
            // Eval'd question writer code expects this to be "$hintloc".
            $hintloc = $this->getHintText($hints);
        }

        /*
         * Move the preview location based on $previewloc.
         *
         * The default position for the Preview button is immediately after
         * the answer box. If the question writer has defined a different
         * location for it, this moves it there.
         */

        $answerbox = $this->adjustPreviewLocation($answerbox, $toevalqtxt, $previewLocations);

        /*
         * Get the "Show Answer" button location.
         */

        // This variable must be named $showanswerloc, as it may be used by
        // the question writer.
        $showanswerloc = $this->getShowAnswerLocation($showAnswer, $answerbox,
            $entryTips, $displayedAnswersForParts, $questionWriterVars);

        /*
         * Eval the question code.
         *
         * Answer boxes are also added here, if $answerbox is defined in the
         * eval'd question code.
         *
         * Question content (raw HTML) is stored in: $evaledqtext
         */

        eval("\$evaledqtext = \"$toevalqtxt\";"); // This creates $evaledqtext.

        /*
         * Eval the solution code.
         *
         * Solution content (raw HTML) is stored in: $evaledsoln
         */

        eval("\$evaledsoln = \"$toevalsoln\";"); // This creates $evaledsoln.
        $detailedSolutionContent = $this->getDetailedSolutionContent($evaledsoln);

        /*
         * Special answer box stuff.
         *
         * Replace [AB and/or [SAB, which has been placed into $evaledqtext
         * after being eval'd.
         */

        if (strpos($evaledqtext, '[AB') !== false) {
            if (is_array($answerbox)) {
                foreach ($answerbox as $iidx => $abox) {
                    if (strpos($evaledqtext, '[AB' . $iidx . ']') !== false) {
                        $evaledqtext = str_replace('[AB' . $iidx . ']', $abox, $evaledqtext);
                        $toevalqtxt .= '$answerbox[' . $iidx . ']';  //to prevent autoadd
                    }
                }
            } else {
                $evaledqtext = str_replace('[AB]', $answerbox, $evaledqtext);
                $toevalqtxt .= '$answerbox';
            }
        }

        if (strpos($evaledqtext, '[SAB') !== false) {
            if (!isset($showanswerloc)) { // $showanswerloc may be defined by the question writer.
                $evaledqtext = preg_replace('/\[SAB\d*\]/', '', $evaledqtext);
            } else if (is_array($showanswerloc)) {
                foreach ($showanswerloc as $iidx => $saloc) {
                    if (strpos($evaledqtext, '[SAB' . $iidx . ']') !== false) {
                        $evaledqtext = str_replace('[SAB' . $iidx . ']', $saloc, $evaledqtext);
                        $toevalqtxt .= '$showanswerloc[' . $iidx . ']';  //to prevent autoadd
                    }
                }
            } else {
                $evaledqtext = str_replace('[SAB]', $showanswerloc, $evaledqtext);
                $toevalqtxt .= '$showanswerloc';
            }
        }

        $evaledqtext = "<div class=\"question\" role=region aria-label=\"" . _('Question') . "\">\n"
            . filter($evaledqtext);

        /*
         * Disable answer box inputs
         */

        if (strpos($toevalqtxt, '$answerbox') === false) {
            if (is_array($answerbox)) {
                foreach ($answerbox as $iidx => $abox) {
                    // These were previously echo statements.
                    $evaledqtext .= filter("<div class=\"toppad\">$abox</div>\n");
                    $evaledqtext .= "<div class=spacer>&nbsp;</div>\n";
                }
            } else {  //one question only
                // This was previously an echo statement.
                $evaledqtext .= filter("<div class=\"toppad\">$answerbox</div>\n");
            }
        }

        /*
         * Add help text / hints.
         */

        if (isset($helptext) && $this->questionParams->getShowHints()) {
            // This was previously an echo statement.
            $evaledqtext .= '<div><p class="tips">' . filter($helptext) . '</p></div>';
        }

        // This closes the div around the question text, before displaying
        // possible points and the current attempt number.
        $evaledqtext .= "\n</div>\n";

        /*
         * External references. (videos, etc)
         */

        $externalReferences = $this->getExternalReferences();

        /*
		 * All done!
		 */

        $question = new Question(
            $evaledqtext,
            $jsParams,
            $evaledsoln,
            $detailedSolutionContent,
            $entryTips,
            $displayedAnswersForParts,
            $externalReferences
        );

        return $question;
    }


    /**
     * Determine if this question is a multi-part question.
     *
     * @return bool True if this is a multi-part question. False if not.
     */
    private function isMultipart(): bool
    {
        $qdata = $this->questionParams->getQuestionData();

        return 'multipart' == $qdata['qtype'] || 'conditional' == $qdata['qtype'];
    }

    /**
     * Get all question images as full HTML strings. (<img/> tags)
     *
     * Note: Original variable names (value of $imageName in this method) must
     *       be available during question code eval.
     *
     * @return array A hashmap of complete, raw HTML strings containing images.
     */
    private function getImagesAsHtml(): array
    {
        $stm = $this->dbh->prepare("SELECT
            var, filename, alttext FROM imas_qimages WHERE qsetid = :qsetid");
        $stm->execute(array(':qsetid' => $this->questionParams->getDbQuestionSetId()));

        $imagesAsHtml = array();
        while ($row = $stm->fetch(PDO::FETCH_NUM)) {
            $imageName = $row[0];
            $filename = $row[1]; // This can be a complete URL or a filename.
            $altText = $row[2];

            // Note: In displayq2.php, the original variable name is the
            //       $imageName, and it must be available during question eval.
            //       Original code: ${row[0]} = "<img tag/>"
            if (substr($filename, 0, 4) == 'http') {
                $imagesAsHtml[$imageName] =
                    sprintf('<img src="%s" alt="%s" />',
                        $filename, htmlentities($altText, ENT_QUOTES));
            } else if (isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
                $imagesAsHtml[$imageName] =
                    sprintf('<img src="%s%s.s3.amazonaws.com/qimages/%s" alt="%s" />',
                        $GLOBALS['urlmode'], $GLOBALS['AWSbucket'], $filename,
                        htmlentities($altText, ENT_QUOTES));
            } else {
                $imagesAsHtml[$imageName] =
                    sprintf('<img src="%s/assessment/qimages/%s" alt="%s" />',
                        $GLOBALS['imasroot'], $filename,
                        htmlentities($altText, ENT_QUOTES));
            }
        }

        return $imagesAsHtml;
    }

    /**
     * Get hint text for question and/or individual parts.
     *
     * @param array $hints As provided by the question writer.
     * @return string|array The hint text.
     */
    private function getHintText(array $hints)
    {
        $qdata = $this->questionParams->getQuestionData();
        $attemptn = $this->questionParams->getStudentAttemptNumber();
        $scoreiscorrect = $this->questionParams->getScoreIsCorrect();

        $thisq = $this->questionParams->getQuestionNumber() + 1;

        $hintloc = '';

        $lastkey = max(array_keys($hints));
        if ($qdata['qtype'] == "multipart" && is_array($hints[$lastkey])) { //individual part hints
            $hintloc = array();

            foreach ($hints as $iidx => $hintpart) {
                if (isset($scoreiscorrect) && $scoreiscorrect[$thisq][$iidx] == 1) {
                    continue;
                }
                $lastkey = max(array_keys($hintpart));
                if ($attemptn > $lastkey) {
                    $usenum = $lastkey;
                } else {
                    $usenum = $attemptn;
                }
                if ($hintpart[$usenum] != '') {
                    if (strpos($hintpart[$usenum], '</div>') !== false) {
                        $hintloc[$iidx] = $hintpart[$usenum];
                    } else if (strpos($hintpart[$usenum], 'button"') !== false) {
                        $hintloc[$iidx] = "<p>{$hintpart[$usenum]}</p>\n";
                    } else if (isset($hintlabel)) {
                        $hintloc[$iidx] = "<p>$hintlabel {$hintpart[$usenum]}</p>\n";
                    } else {
                        $hintloc[$iidx] = "<p><i>" . _('Hint:') . "</i> {$hintpart[$usenum]}</p>\n";
                    }
                }
            }
        } else if (!isset($scoreiscorrect) || $scoreiscorrect[$thisq] != 1) { //one hint for question
            if ($attemptn > $lastkey) {
                $usenum = $lastkey;
            } else {
                $usenum = $attemptn;
            }
            if ($hints[$usenum] != '') {
                if (strpos($hints[$usenum], '</div>') !== false) {
                    $hintloc = $hints[$usenum];
                } else if (strpos($hints[$usenum], 'button"') !== false) {
                    $hintloc = "<p>{$hints[$usenum]}</p>\n";
                } else if (isset($hintlabel)) {
                    $hintloc = "<p>$hintlabel {$hints[$usenum]}</p>\n";
                } else {
                    $hintloc = "<p><i>" . _('Hint:') . "</i> {$hints[$usenum]}</p>\n";
                }
            }
        }

        return $hintloc;
    }

    /**
     * Place the location of the preview button based on $previewloc.
     *
     * If the question writer defined $previewloc, we move the preview from
     * the default location to the location defined by the question writer.
     *
     * @param string|array $answerbox String for single answer box, array for multiple.
     * @param string $toevalqtxt The question code to be eval'd.
     * @param string|array $previewloc May be defined by the question writer.
     * @return string|array
     */
    private function adjustPreviewLocation($answerbox, string $toevalqtxt, $previewloc)
    {
        $qnidx = $this->questionParams->getQuestionNumber();

        if (is_array($answerbox)) {
            foreach ($answerbox as $iidx => $abox) {
                if (strpos($toevalqtxt, "\$previewloc[$iidx]") === false) {
                    if (strpos($answerbox[$iidx], "previewloctemp$iidx") !== false) {
                        $answerbox[$iidx] = str_replace(
                            '<span id="previewloctemp' . $iidx . '"></span>',
                            $previewloc[$iidx], $answerbox[$iidx]);
                    } else {
                        $answerbox[$iidx] .= $previewloc[$iidx];
                    }
                }
            }
        } else {
            if (strpos($toevalqtxt, '$previewloc') === false) {
                if (strpos($answerbox, "previewloctemp$qnidx") !== false) {
                    $answerbox = str_replace(
                        '<span id="previewloctemp' . $qnidx . '"></span>',
                        $previewloc, $answerbox);
                } else {
                    $answerbox .= $previewloc;
                }
            }
        }

        return $answerbox;
    }

    /**
     * Get the "Show Answer" button location.
     *
     * @param int $showAnswer @see ShowAnswer
     * @param string|array $answerBoxes String for single answer box, array for multiple.
     * @param array $entryTips Tooltips displayed for answer boxes.
     * @param array $displayedAnswersForParts
     * @param array $questionWriterVars
     * @return array|string
     */
    private function getShowAnswerLocation(int $showAnswer,
                                           $answerBoxes,
                                           array $entryTips,
                                           array $displayedAnswersForParts,
                                           array $questionWriterVars
    )
    {
        $qnidx = $this->questionParams->getQuestionNumber();
        $shanspt = $displayedAnswersForParts;

        if (ShowAnswer::ALWAYS == $showAnswer) {
            $doshowans = true;
            $nosabutton = true;
        } else {
            $nosabutton = false;
        }

        // We need to "unpack" this into locally scoped variables.
        foreach ($questionWriterVars as $k => $v) {
            ${$k} = $v;
        }

        /*
         * Business logic begins here.
         */

        $showanswerloc = '';

        if ($doshowans && isset($showanswer) && !is_array($showanswer)) {  //single showanswer defined
            $showanswerloc = (isset($showanswerstyle) && $showanswerstyle == 'inline') ? '<span>' : '<div>';
            if ($nosabutton) {
                $showanswerloc .= filter(_('Answer:') . " $showanswer\n");
            } else {
                $showanswerloc .= "<input class=\"sabtn\" type=button value=\"" . _('Show Answer') . "\" />";
                $showanswerloc .= filter(" <span id=\"ans$qnidx\" class=\"hidden\">$showanswer </span>\n");
            }
            $showanswerloc .= (isset($showanswerstyle) && $showanswerstyle == 'inline') ? '</span>' : '</div>';
        } else if ($doshowans) {
            $showanswerloc = array();
            foreach ($entryTips as $iidx => $entryTip) {
                $showanswerloc[$iidx] = (isset($showanswerstyle) && $showanswerstyle == 'inline') ? '<span>' : '<div>';
                if ($doshowans && (!isset($showanswer) || (is_array($showanswer) && !isset($showanswer[$iidx]))) && $shanspt[$iidx] !== '') {
                    if (strpos($shanspt[$iidx], '[AB') !== false) {
                        foreach ($shanspt as $subiidx => $sarep) {
                            if (strpos($shanspt[$iidx], '[AB' . $subiidx . ']') !== false) {
                                $shanspt[$iidx] = str_replace('[AB' . $subiidx . ']', $sarep, $shanspt[$iidx]);
                                $shanspt[$subiidx] = '';
                            }
                        }
                    }
                    if ($nosabutton) {
                        $showanswerloc[$iidx] .= "<span id=\"showansbtn$qnidx-$iidx\">" . filter(_('Answer:') . " {$shanspt[$iidx]}</span>\n");
                    } else {
                        $showanswerloc[$iidx] .= "<input id=\"showansbtn$qnidx-$iidx\" class=\"sabtn\" type=button value=\"" . _('Show Answer') . "\" />"; //AMprocessNode(document.getElementById(\"ans$qnidx-$iidx\"));'>";
                        // $shanspt can contain HTML.
                        $showanswerloc[$iidx] .= filter(" <span id=\"ans$qnidx-$iidx\" class=\"hidden\">{$shanspt[$iidx]}</span>\n");
                    }
                } else if ($doshowans && isset($showanswer) && is_array($showanswer)) { //use part specific showanswer
                    if (isset($showanswer[$iidx])) {
                        if ($nosabutton) {
                            $showanswerloc[$iidx] .= "<span id=\"showansbtn$qnidx-$iidx\">" . filter(_('Answer:') . " {$showanswer[$iidx]}</span>\n");
                        } else {
                            $showanswerloc[$iidx] .= "<input id=\"showansbtn$qnidx-$iidx\" class=\"sabtn\" type=button value=\"" . _('Show Answer') . "\" />";// AMprocessNode(document.getElementById(\"ans$qnidx-$iidx\"));'>";
                            $showanswerloc[$iidx] .= filter(" <span id=\"ans$qnidx-$iidx\" class=\"hidden\">{$showanswer[$iidx]}</span>\n");
                        }
                    }
                }
                $showanswerloc[$iidx] .= (isset($showanswerstyle) && $showanswerstyle == 'inline') ? '</span>' : '</div>';
            }
            if (!is_array($answerBoxes) && count($showanswerloc) == 1) { //not a multipart question
                $showanswerloc = str_replace($qnidx . '-0"', $qnidx . '"', $showanswerloc[0]);
            }
        }

        return $showanswerloc;
    }

    /**
     * Get external references for this question.
     *
     * @return string External references as raw HTML.
     */
    private function getExternalReferences(): string
    {
        $showhints = $this->questionParams->getShowHints();
        $qdata = $this->questionParams->getQuestionData();
        $qnidx = $this->questionParams->getQuestionNumber();
        $qidx = $this->questionParams->getDbQuestionSetId();

        $externalReferences = '';

        if ($showhints && ($qdata['extref'] != '' || (($qdata['solutionopts'] & 2) == 2 && $qdata['solution'] != ''))) {
            $externalReferences .= '<div><p class="tips">' . _('Get help: ');
            $extrefwidth = isset($GLOBALS['CFG']['GEN']['extrefsize']) ? $GLOBALS['CFG']['GEN']['extrefsize'][0] : 700;
            $extrefheight = isset($GLOBALS['CFG']['GEN']['extrefsize']) ? $GLOBALS['CFG']['GEN']['extrefsize'][1] : 500;
            $vidextrefwidth = isset($GLOBALS['CFG']['GEN']['vidextrefsize']) ? $GLOBALS['CFG']['GEN']['vidextrefsize'][0] : 873;
            $vidextrefheight = isset($GLOBALS['CFG']['GEN']['vidextrefsize']) ? $GLOBALS['CFG']['GEN']['vidextrefsize'][1] : 500;
            if ($qdata['extref'] != '') {
                $extref = explode('~~', $qdata['extref']);

                if (isset($GLOBALS['questions']) && (!isset($GLOBALS['sessiondata']['isteacher'])
                        || $GLOBALS['sessiondata']['isteacher'] == false) && !isset($GLOBALS['sessiondata']['stuview'])) {
                    $qref = $GLOBALS['questions'][$qnidx] . '-' . ($qnidx + 1);
                } else {
                    $qref = '';
                }
                for ($i = 0; $i < count($extref); $i++) {
                    $extrefpt = explode('!!', $extref[$i]);
                    if ($extrefpt[0] == 'video' || strpos($extrefpt[1], 'youtube.com/watch') !== false) {
                        $extrefpt[1] = $GLOBALS['basesiteurl'] . "/assessment/watchvid.php?url=" . Sanitize::encodeUrlParam($extrefpt[1]);
                        if ($extrefpt[0] == 'video') {
                            $extrefpt[0] = 'Video';
                        }
                        $externalReferences .= formpopup($extrefpt[0], $extrefpt[1], $vidextrefwidth, $vidextrefheight, "button", true, "video", $qref);
                    } else if ($extrefpt[0] == 'read') {
                        $externalReferences .= formpopup("Read", $extrefpt[1], $extrefwidth, $extrefheight, "button", true, "text", $qref);
                    } else {
                        $externalReferences .= formpopup($extrefpt[0], $extrefpt[1], $extrefwidth, $extrefheight, "button", true, "text", $qref);
                    }
                }
            }
            if (($qdata['solutionopts'] & 2) == 2 && $qdata['solution'] != '') {
                $addr = $GLOBALS['basesiteurl'] . "/assessment/showsoln.php?id=" . $qidx . '&sig=' . md5($qidx . $GLOBALS['sessiondata']['secsalt']);
                $addr .= '&t=' . ($qdata['solutionopts'] & 1) . '&cid=' . $GLOBALS['cid'];
                if ($GLOBALS['cid'] == 'embedq' && isset($GLOBALS['theme'])) {
                    $addr .= '&theme=' . Sanitize::encodeUrlParam($GLOBALS['theme']);
                }
                $externalReferences .= formpopup(_("Written Example"), $addr, $extrefwidth, $extrefheight, "button", true, "soln", $qref);
            }
            $externalReferences .= '</p></div>';
        }

        return $externalReferences;
    }

    /**
     * Get the detailed solution content.
     *
     * @param string $solutionContent The (non-detailed, eval'd) solution content.
     * @return string The detailed solution content.
     */
    private function getDetailedSolutionContent(string $solutionContent)
    {
        $solutionOptions = $this->questionParams->getQuestionData()['solutionopts'];

        $detailedSolutionContent = '<div id="writtenexample" class="review" role=region aria-label="'
            . _('Written Example') . '">' . $solutionContent . '</div>';
        if (($solutionOptions & 1) == 0) {
            $detailedSolutionContent = '<i>' . _('This solution is for a similar problem, not your specific version') . '</i><br/>' . $solutionContent;
        }

        return $detailedSolutionContent;
    }

    /**
     * Get the color for the answerbox for a single part.
     *
     * @param array|null $allScores Scores for all parts of the question.
     * @param int $partNumber The question part number.
     * @param float $answerWeight The answer weight for the question part.
     * @return string An answer box color class name.
     */
    private function getAnswerColorFromRawScore(?array $allScores,
                                                int $partNumber,
                                                float $answerWeight): string
    {
        if (empty($allScores) || !isset($allScores[$partNumber]) || $answerWeight == 0) {
            return '';
        }
        if ($allScores[$partNumber] < 0) {
            return '';
        }
        if ($allScores[$partNumber] == 0) {
            return 'ansred';
        }
        if ($allScores[$partNumber] > .98) {
            return 'ansgrn';
        }

        return 'ansyel';
    }

    /**
     * Get a raw HTML string containing an <img> tag, indicating correctness.
     *
     * @param string $color One of: ansred, ansgrn, ansorg, ansyel.
     * @return string A raw HTML string containing an <img> tag.
     */
    private function getColoredMark(string $color): string
    {
        global $imasroot;

        if (isset($GLOBALS['nocolormark'])) {
            return '';
        }

        if ($color == 'ansred') {
            return '<img class="scoreboxicon" src="' . $imasroot
                . '/img/redx.gif" width="8" height="8" alt="' . _('Incorrect') . '"/>';
        }
        if ($color == 'ansgrn') {
            return '<img class="scoreboxicon" src="' . $imasroot
                . '/img/gchk.gif" width="10" height="8" alt="' . _('Correct') . '"/>';
        }
        if ($color == 'ansorg') {
            return '<img class="scoreboxicon" src="' . $imasroot
                . '/img/orgx.gif" width="8" height="8" alt="' . _('Correct answer, but wrong format') . '"/>';
        }
        if ($color == 'ansyel') {
            return '<img class="scoreboxicon" src="' . $imasroot
                . '/img/ychk.gif" width="10" height="8" alt="' . _('Partially correct') . '"/>';
        }

        return '';
    }

    /**
     * Add an error message to the array of errors encountered.
     *
     * @param string $errorMessage The error message.
     */
    private function addError(string $errorMessage): void
    {
        $this->errors[] = $errorMessage;
    }
}

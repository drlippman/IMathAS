<?php

namespace IMathAS\assess2\questions;

require_once __DIR__ . '/answerboxes/AnswerBoxParams.php';
require_once __DIR__ . '/answerboxes/AnswerBoxFactory.php';
require_once __DIR__ . '/models/Question.php';

use PDO;

use Rand;
use Sanitize;

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
        'readerlabel'
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
        ob_start();

        $GLOBALS['inquestiondisplay'] = true;
        $GLOBALS['assessver'] = 2;

        $doShowAnswer = $this->questionParams->getShowAnswer();
        $doShowAnswerParts = $this->questionParams->getShowAnswerParts();

        if (ShowAnswer::ALWAYS == $doShowAnswer) {
            $nosabutton = true;
        } else {
            $nosabutton = false;
        }

        // The following variables are expected by the question writer's code in interpret().
        $stuanswers = $this->questionParams->getAllQuestionAnswers();  // Contains ALL question answers.
        $stuanswersval = $this->questionParams->getAllQuestionAnswersAsNum();
        $autosaves = $this->questionParams->getAllQuestionAutosaves();
        $scorenonzero = $this->questionParams->getScoreNonZero();
        $scoreiscorrect = $this->questionParams->getScoreIsCorrect();
        $attemptn = $this->questionParams->getStudentAttemptNumber();
        $partattemptn = $this->questionParams->getStudentPartAttemptCount();
        $quesData = $this->questionParams->getQuestionData();
        $showHints = ($this->questionParams->getShowHints()&1)==1;
        $thisq = $this->questionParams->getQuestionNumber() + 1;
        $correctAnswerWrongFormat = $this->questionParams->getCorrectAnswerWrongFormat();
        $printFormat = $this->questionParams->getPrintFormat();
        $teacherInGb = $this->questionParams->getTeacherInGb();
        $graphdispmode = $_SESSION['userprefs']['graphdisp'] ?? 1;
        $drawentrymode = $_SESSION['userprefs']['drawentry'] ?? 1;

        $isbareprint = !empty($GLOBALS['isbareprint']); // lazy hack

        $thiscourseid = (isset($GLOBALS['cid']) && is_numeric($GLOBALS['cid'])) ?
            intval($GLOBALS['cid']) : 0;

        if ($printFormat) {
            $GLOBALS['capturechoiceslivepoll'] = true;
        }

        if ($quesData['qtype'] == "multipart" || $quesData['qtype'] == 'conditional') {
          // if multipart/condition only has one part, the stuanswers script will
          // un-array it
          if (isset($stuanswers[$thisq]) && !is_array($stuanswers[$thisq])) {
            $stuanswers[$thisq] = array($stuanswers[$thisq]);
            if (isset($stuanswersval[$thisq])) {
              $stuanswersval[$thisq] = array($stuanswersval[$thisq]);
            }
          }
        } else {
            // if $doShowAnswerParts is set for part 0, use it for global
            if (!empty($doShowAnswerParts[0])) {
                $doShowAnswer = true;
            }
            if (isset($stuanswers[$thisq]) && is_array($stuanswers[$thisq])) {
                // shouldn't be. Log and fix.
                $db_qsetid = $this->questionParams->getDbQuestionSetId();
                $logdata = "stuanswers is array in qid $db_qsetid: " . 
                    print_r($stuanswers[$thisq], true) . 
                    '; in script ' . $_SERVER['REQUEST_URI'] . 
                    ' user ' . $GLOBALS['userid'];
                $stm = $this->dbh->prepare("INSERT INTO imas_log (log,time) VALUES (?,?)");
                $stm->execute([$logdata, time()]);
                unset($stm);
                $stuanswers[$thisq] = $stuanswers[$thisq][0];
                if (isset($stuanswersval[$thisq]) && is_array($stuanswersval[$thisq])) {
                  $stuanswersval[$thisq] = $stuanswersval[$thisq][0];
                }
            }
        }

        $stulastentry = null;
        if (isset($stuanswers[$thisq])) {
            $stulastentry = $stuanswers[$thisq];
        }
        if (isset($autosaves[$thisq])) {
            if ($quesData['qtype'] == "multipart" || $quesData['qtype'] == 'conditional') {
                foreach ($autosaves[$thisq] as $iidx=>$kidx) {
                    $stulastentry[$iidx] = $kidx;
                }
            } else {
                if (isset($autosaves[$thisq][0])) {
                    $stulastentry = $autosaves[$thisq][0];
                } else {
                    $db_qsetid = $this->questionParams->getDbQuestionSetId();
                    $logdata = "autosave pn 0 unset for non-MP in $db_qsetid: " . 
                        print_r($autosaves[$thisq], true) . 
                        '; in script ' . $_SERVER['REQUEST_URI'] . 
                        ' user ' . $GLOBALS['userid'];
                    $stm = $this->dbh->prepare("INSERT INTO imas_log (log,time) VALUES (?,?)");
                    $stm->execute([$logdata, time()]);
                    unset($stm);
                }
            }
        }

        if ($quesData['qtype'] == "multipart") {
            // if multipart only has one part, need to re-array scoreiscorrect
            if (isset($scoreiscorrect[$thisq]) && !is_array($scoreiscorrect[$thisq])) {
                $scoreiscorrect[$thisq] = array($scoreiscorrect[$thisq]);
            }
            if (isset($scorenonzero[$thisq]) && !is_array($scorenonzero[$thisq])) {
                $scorenonzero[$thisq] = array($scorenonzero[$thisq]);
            }
        } else if ($quesData['qtype'] == "conditional" && is_array($scoreiscorrect[$thisq])) {
            $scoreiscorrect[$thisq] = $scoreiscorrect[$thisq][0];
            $scorenonzero[$thisq] = $scorenonzero[$thisq][0];
        }
        if ($attemptn == 0) {
          $GLOBALS['assess2-curq-iscorrect'] = -1;
        } else {
          if ($quesData['qtype'] != "multipart" && isset($partattemptn[0])) {
            $GLOBALS['assess2-curq-iscorrect'] = ($scoreiscorrect[$thisq] < 0 ? -1 : ($scoreiscorrect[$thisq]==1 ? 1 : 0));
          } else if ($quesData['qtype'] == "multipart") {
            $GLOBALS['assess2-curq-iscorrect'] = array();
            foreach ($partattemptn as $kidx=>$iidx) {
              if ($iidx==0 || !isset($scoreiscorrect[$thisq][$kidx])) {
                $GLOBALS['assess2-curq-iscorrect'][$kidx] = -1;
              } else {
                $GLOBALS['assess2-curq-iscorrect'][$kidx] = ($scoreiscorrect[$thisq][$kidx] < 0 ? -1 : ($scoreiscorrect[$thisq][$kidx]==1 ? 1 : 0));
              }
            }
          }
        }

        if ($quesData['hasimg'] > 0) {
            // We need to "unpack" this into locally scoped variables.
            foreach ($this->getImagesAsHtml() as $kidx => $iidx) {
                ${$kidx} = $iidx;
            }
        }

        // Use this question's RNG seed.
        $currentseed = $this->questionParams->getQuestionSeed();
        $this->randWrapper->srand($this->questionParams->getQuestionSeed());

        // Eval the question writer's question code.
        // In older questions, code is broken up into three parts.
        // In "modern" questions, the last two parts are empty.
        try {
          $db_qsetid = $this->questionParams->getDbQuestionSetId();
          eval(interpret('control', $quesData['qtype'], $quesData['control'], 1, [$db_qsetid]));
          eval(interpret('qcontrol', $quesData['qtype'], $quesData['qcontrol'], 1, [$db_qsetid]));
          eval(interpret('answer', $quesData['qtype'], $quesData['answer'], 1, [$db_qsetid]));
        } catch (\Throwable $t) {
          $errsource = basename($t->getFile());
          if (strpos($errsource, 'QuestionHtmlGenerator.php') !== false) {
            $errsource = _('Common Control');
          }
          $this->addError(
              _('Caught error while evaluating the code in this question: ')
              . $t->getMessage()
              . ' on line '
              . $t->getLine()
              . ' of '
              . $errsource
          );
          if (!empty($GLOBALS['CFG']['newrelic_log_question_errors']) && extension_loaded('newrelic')) {
            newrelic_add_custom_parameter('cur_qsid', $db_qsetid);
            newrelic_notice_error($t);
          }

        }

        $toevalqtxt = interpret('qtext', $quesData['qtype'], $quesData['qtext']);
        $qtextvars = array_merge($GLOBALS['interpretcurvars'], $GLOBALS['interpretcurarrvars']);

        if (!$teacherInGb) {
            $toevalqtxt = preg_replace('~(<p[^>]*>\[teachernote\].*?\[/teachernote\]</p>|\[teachernote\].*?\[/teachernote\])~ms','',$toevalqtxt);
        } else {
            $toevalqtxt = preg_replace_callback('~(<p[^>]*>\[teachernote\](.*?)\[/teachernote\]</p>|\[teachernote\](.*?)\[/teachernote\])~ms',
                function($matches) {
                    return '<div><input class=\\"dsbtn\\" type=\\"button\\" value=\\"'._('Show Instructor Note').'\\"/>' . 
                        '<div class=\\"hidden dsbox\\" id=\\"dsboxTN'.uniqid().'\\">' . 
                        (empty($matches[2]) ? $matches[3] : $matches[2]) . '</div></div>';
                },$toevalqtxt);
        }
        //                              1                 2            3       4
        if (preg_match_all('~\[if\s+([\w_]*?)\s*(==|!=|>=|<=|>|<)\s*(\w+)\s*\](.*?)\[/if\]~ms', $toevalqtxt, $QHG_all_matches, PREG_SET_ORDER)) {
            foreach ($QHG_all_matches as $QHG_match) {
                $keep = false;
                if (isset(${$QHG_match[1]})) {
                    $val = ${$QHG_match[1]};
                    if (($QHG_match[2]=='==' && $val==$QHG_match[3]) ||
                        ($QHG_match[2]=='!=' && $val!=$QHG_match[3]) ||
                        ($QHG_match[2]=='>=' && $val>=$QHG_match[3]) ||
                        ($QHG_match[2]=='<=' && $val<=$QHG_match[3]) ||
                        ($QHG_match[2]=='>' && $val>$QHG_match[3]) ||
                        ($QHG_match[2]=='<' && $val<$QHG_match[3])
                    ) {
                        $keep = true;
                    }
                }
                if ($keep) {
                    $toevalqtxt = str_replace($QHG_match[0], $QHG_match[4], $toevalqtxt);
                } else {
                    $toevalqtxt = str_replace($QHG_match[0], '', $toevalqtxt);
                }
            }
        }
        $toevalqtxt = str_replace('\\', '\\\\', $toevalqtxt);
        $toevalqtxt = str_replace(array('\\\\n', '\\\\"', '\\\\$', '\\\\{'),
            array('\\n', '\\"', '\\$', '\\{'), $toevalqtxt);

        $toevalsoln = interpret('qtext', $quesData['qtype'], $quesData['solution']);
        $solnvars = array_merge($GLOBALS['interpretcurvars'], $GLOBALS['interpretcurarrvars']);

        $toevalsoln = str_replace('\\', '\\\\', $toevalsoln);
        $toevalsoln = str_replace(array('\\\\n', '\\\\"', '\\\\$', '\\\\{'),
            array('\\n', '\\"', '\\$', '\\{'), $toevalsoln);
        $toevalsoln = preg_replace('/\$answerbox(\[.*?\])?/', '', $toevalsoln);
        
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

        if ($quesData['qtype'] == 'conditional') {
            if (!isset($showanswer)) {
                $showanswer = _('Answers may vary');
            }
        }

        if ($printFormat) {
            if (isset($displayformat)) {
                if (is_array($displayformat)) {
                    foreach ($displayformat as $kidx => $iidx) {
                        if ($iidx == 'select' || preg_match('/\dcolumn/', $iidx)) {
                            unset($displayformat[$kidx]);
                        }
                    }
                } else if ($displayformat == 'select' || preg_match('/\dcolumn/', $displayformat)) {
                    unset($displayformat);
                }
            }
            unset($answersize);
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
                if (is_array($answerformat)) {
                    array_walk_recursive($answerformat, function(&$v, $k) {
                        $v = str_replace(' ', '', $v);
                    });
                } else {
                    $answerformat = str_replace(' ', '', $answerformat);
                }
            }
            $questionWriterVars[$optionKey] = ${$optionKey};
        }

        // These are also needed by the answer box generator.
        $varsForAnswerBoxGenerator = array();
        foreach (self::VARS_FOR_ANSWERBOX_GENERATOR as $vargenKey) {
            if (!isset(${$vargenKey})) {
                continue;
            }
            $varsForAnswerBoxGenerator[$vargenKey] = ${$vargenKey};
        }

        if (isset($GLOBALS['CFG']['hooks']['assess2/questions/question_html_generator'])) {
            require_once $GLOBALS['CFG']['hooks']['assess2/questions/question_html_generator'];
            if (isset($onBeforeAnswerBoxGenerator) && is_callable($onBeforeAnswerBoxGenerator)) {
                $onBeforeAnswerBoxGenerator();
            }
        }

        /*
         * Calculate answer weights and generate answer boxes.
         */

        // $answerbox must not be renamed, it is expected in eval'd code.
        $answerbox = $previewloc = null;
        $entryTips = $displayedAnswersForParts = $jsParams = [];

        if ($quesData['qtype'] == "multipart" || $quesData['qtype'] == 'conditional') {
            // $anstypes is question writer defined.
            if (empty($anstypes) || $anstypes[0]==='') {
              if ($GLOBALS['myrights'] > 10) {
                $this->addError('Error in question: missing $anstypes for multipart or conditional question');
              }
              $anstypes = array("number");
            }

            // Calculate answer weights.
            // $answeights - question writer defined
            if ($quesData['qtype'] == "multipart") {
                if (max(array_keys($anstypes)) != count($anstypes)-1) {
                    echo 'Error: $anstypes does not have consecutive indexes. This may cause scoring issues.';
                }
                if (isset($answeights)) {
        			if (!is_array($answeights)) {
        					$answeights = explode(",",$answeights);
                    }
                    $answeights = array_map('trim', $answeights);
                    if (count($answeights) != count($anstypes)) {
                        $answeights = array_fill_keys(array_keys($anstypes), 1); 
                    }
                    $answeights = array_map(function($v) {
                        if (is_numeric($v)) { 
                            return $v;
                        } else {
                            return evalbasic($v);
                        }
                    }, $answeights);
                    ksort($answeights);
                } else {
                    if (count($anstypes)>1) {
                        $answeights = array_fill_keys(array_keys($anstypes), 1);
                    } else {
                        $answeights = array(1);
                    }
                }    
            }

            // Get the answers to all parts of this question.
            $lastAnswersAllParts = $stuanswers[$thisq] ?? [];
            if (isset($autosaves[$thisq])) {
              foreach ($autosaves[$thisq] as $iidx=>$kidx) {
                  $lastAnswersAllParts[$iidx] = $kidx;
              }
            }
            if (!is_array($lastAnswersAllParts)) {
              // multipart questions with one part get stored as single value;
              // turn back into an array
              $lastAnswersAllParts = array($lastAnswersAllParts);
            }


            /*
             *  For sequenial multipart, skip answerbox generation for parts
             *  that won't be shown.
             *
             */
            $skipAnswerboxGeneration = array();
            $seqGroupDone = array();
            if ($quesData['qtype'] == "multipart" ||
              ($quesData['qtype'] == "conditional" && isset($seqPartDone))
            ) {
              $_seqParts = preg_split('~(<p[^>]*>(<(span|em|strong)[^>]*>)*\s*///+\s*(<\/[^>]*>)*</p[^>]*>|<br\s*/?><br\s*/?>\s*(<(span|em|strong)[^>]*>)*\s*///+\s*(<\/[^>]*>)*\s*<br\s*/?><br\s*/?>)~', $toevalqtxt);
              if (count($_seqParts) > 1) {
                if ($quesData['qtype'] != "conditional") {
                  $seqPartDone = $this->questionParams->getSeqPartDone();
                }
 
                $_lastGroupDone = true;
                foreach ($_seqParts as $kidx=>$_seqPart) {
                  $_thisGroupDone = true;
                  preg_match_all('/(\$answerbox\[|\[AB)(\d+)\]/', $_seqPart, $iidx);

                  foreach ($iidx[2] as $_pnidx) {
                    if (!$_lastGroupDone) { // not ready for it - unset stuff
                      $skipAnswerboxGeneration[$_pnidx] = true;
                      $jsParams['hasseqnext'] = true;
                      $_thisGroupDone = false;
                    }
                    if ($seqPartDone !== true && empty($seqPartDone[$_pnidx]) && ($quesData['qtype'] == "conditional" || !empty($answeights[$_pnidx]))) {
                      $_thisGroupDone = false;
                    }
                  }
                  $seqGroupDone[$kidx] = $_thisGroupDone;
                  $_lastGroupDone = $_thisGroupDone;
                }
              }
            } else {
              unset($seqPartDone);
            }
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
			 * $previewloc[]
             *                  // Orig: $previewloc in displayq2.php
             *                     Sets if the preview is displayed after the
			 *                     question (default) or in a location defined
			 *                     by the question writer.
			 */

            // Generate answer boxes. (multipart question)
            foreach ($anstypes as $atIdx => $anstype) {
                if (!empty($skipAnswerboxGeneration[$atIdx])) {
                  $answerbox[$atIdx] = "";
                  $previewloc[$atIdx] = "";
                  continue;
                }
                $questionColor = ($quesData['qtype'] == "multipart")
                    ? $this->getAnswerColorFromRawScore(
                        $this->questionParams->getLastRawScores(), $atIdx, $answeights[$atIdx])
                    : '';

                if (isset($requestclearla)) {
                  $lastAnswersAllParts[$atIdx] = '';
                  $questionColor = '';
                }
                $answerBoxParams = new AnswerBoxParams();
                $answerBoxParams
                    ->setQuestionWriterVars($questionWriterVars)
                    ->setVarsForAnswerBoxGenerator($varsForAnswerBoxGenerator)
                    ->setAnswerType($anstype)
                    ->setQuestionNumber($this->questionParams->getDisplayQuestionNumber())
                    ->setIsMultiPartQuestion($this->isMultipart())
                    ->setIsConditional($quesData['qtype'] == "conditional")
                    ->setQuestionPartNumber($atIdx)
                    ->setQuestionPartCount(count($anstypes))
                    ->setAssessmentId($this->questionParams->getAssessmentId())
                    ->setStudentLastAnswers($lastAnswersAllParts[$atIdx] ?? '')
                    ->setColorboxKeyword($questionColor)
                    ->setCorrectAnswerWrongFormat($correctAnswerWrongFormat[$atIdx] ?? false);

                try {
                  $answerBoxGenerator = AnswerBoxFactory::getAnswerBoxGenerator($answerBoxParams);
                  $answerBoxGenerator->generate();
                } catch (\Throwable $t) {
                  $this->addError(
                       _('Caught error while generating this question: ')
                       . $t->getMessage());
                  continue;
                }

                $answerbox[$atIdx] = $answerBoxGenerator->getAnswerBox();
                $answerbox[$atIdx] .= '<span class="afterquestion"></span>';
                $entryTips[$atIdx] = $answerBoxGenerator->getEntryTip();
                $qnRef = ($this->questionParams->getDisplayQuestionNumber()+1)*1000 + $atIdx;
                $jsParams[$qnRef] = $answerBoxGenerator->getJsParams();
                $jsParams[$qnRef]['qtype'] = $anstype;
                $displayedAnswersForParts[$atIdx] = $answerBoxGenerator->getCorrectAnswerForPart();
                $previewloc[$atIdx] = $answerBoxGenerator->getPreviewLocation();

                if ($printFormat) {
                    $answerbox[$atIdx] = preg_replace('/<ul class="?nomark"?>(.*?)<\/ul>/s', '<ol style="list-style-type:upper-alpha">$1</ol>', $answerbox[$atIdx]);
                    $answerbox[$atIdx] = preg_replace('/<ol class="?lalpha"?/','<ol style="list-style-type:lower-alpha"', $answerbox[$atIdx]);
                    
                    if ($anstype === 'choices') {
                        $qanskey = array_search($jsParams[$qnRef]['livepoll_ans'], $jsParams[$qnRef]['livepoll_randkeys']);
                        $displayedAnswersForParts[$atIdx] = chr(65+$qanskey) . ': ' . $displayedAnswersForParts[$atIdx];    
                    }
                }
                // enact hidetips if set
                if (!empty($hidetips) && (!is_array($hidetips) || !empty($hidetips[$atIdx]))) {
                  unset($jsParams[$qnRef]['tip']);
                  unset($jsParams[$qnRef]['longtip']);
                }
            }
            $scoremethodwhole = '';
            if (isset($scoremethod)) {
                if (!is_array($scoremethod)) {
                    $scoremethodwhole = $scoremethod;
                } else if (!empty($scoremethod['whole'])) {
                    $scoremethodwhole = $scoremethod['whole'];
                }
            }
            if (($scoremethodwhole == 'acct' || 
                $scoremethodwhole == 'singlescore' ||
                $scoremethodwhole == 'allornothing'
              ) ||
              $quesData['qtype'] == 'conditional' && count($anstypes)>1
            ) {
              $jsParams['submitall'] = 1;
            }
        } else {

            if (!empty($GLOBALS['isquestionauthor'])) {
                if (isset($anstypes)) {
                    $this->addError('It looks like you have defined $anstypes; did you mean for this question to be Multipart?');
                    unset($anstypes);
                } else if (strpos($toevalqtxt, '$answerbox[') !== false) {
                    $this->addError('It looks like you have an $answerbox with part index; did you mean for this question to be Multipart?');
                }
            }
            // Generate answer boxes. (non-multipart question)
            $questionColor = $this->getAnswerColorFromRawScore(
                $this->questionParams->getLastRawScores(), 0, 1);

            $lastAnswer = $stuanswers[$thisq] ?? '';
            if (isset($autosaves[$thisq][0])) {
              $lastAnswer = $autosaves[$thisq][0];
            }

            if (is_array($lastAnswer)) { // happens with autosaves
              //  appears to be resolved before getting here now
              //  $lastAnswer = $lastAnswer[0];
            }

            if (isset($requestclearla)) {
              $lastAnswer = '';
              $questionColor = '';
            }

            $answerBoxParams = new AnswerBoxParams();
            $answerBoxParams
                ->setQuestionWriterVars($questionWriterVars)
                ->setVarsForAnswerBoxGenerator($varsForAnswerBoxGenerator)
                ->setAnswerType($quesData['qtype'])
                ->setQuestionNumber($this->questionParams->getDisplayQuestionNumber())
                ->setAssessmentId($this->questionParams->getAssessmentId())
                ->setIsMultiPartQuestion(false)
                ->setIsConditional(false)
                ->setStudentLastAnswers($lastAnswer)
                ->setColorboxKeyword($questionColor)
                ->setCorrectAnswerWrongFormat($correctAnswerWrongFormat[0] ?? false);

            $answerBoxGenerator = AnswerBoxFactory::getAnswerBoxGenerator($answerBoxParams);
            $answerBoxGenerator->generate();

            $answerbox = $answerBoxGenerator->getAnswerBox();
            $entryTips[0] = $answerBoxGenerator->getEntryTip();
            $qnRef = $this->questionParams->getDisplayQuestionNumber();
            $jsParams[$qnRef] = $answerBoxGenerator->getJsParams();
            $jsParams[$qnRef]['qtype'] = $quesData['qtype'];
            $displayedAnswersForParts[0] = $answerBoxGenerator->getCorrectAnswerForPart();
            $previewloc = $answerBoxGenerator->getPreviewLocation();

            if ($printFormat) {
                $answerbox = preg_replace('/<ul class="?nomark"?>(.*?)<\/ul>/s', '<ol style="list-style-type:upper-alpha">$1</ol>', $answerbox);
                $answerbox = preg_replace('/<ol class="?lalpha"?/','<ol style="list-style-type:lower-alpha"', $answerbox);

                if ($quesData['qtype'] == 'choices') {
                    $qanskey = array_search($jsParams[$qnRef]['livepoll_ans'], $jsParams[$qnRef]['livepoll_randkeys']);
                    $displayedAnswersForParts[0] = chr(65+$qanskey) . ': ' . $displayedAnswersForParts[0];
                }
            }

            // enact hidetips if set
            if (!empty($hidetips)) {
              unset($jsParams[$qnRef]['tip']);
              unset($jsParams[$qnRef]['longtip']);
            }
        }



        /*
         * Get hint HTML.
         */

        if (isset($hints) && is_array($hints) && count($hints) > 0 && $showHints) {
            // Eval'd question writer code expects this to be "$hintloc".
            if (isset($hintlabel) && is_array($hintlabel)) {
                echo '$hintlabel should be a single string';
                $hintlabel = '';
            }
            $hintloc = $this->getHintText($hints, $hintlabel ?? '');
        }

        /*
         * Move the preview location based on $previewloc.
         *
         * The default position for the Preview button is immediately after
         * the answer box. If the question writer has defined a different
         * location for it, this moves it there.
         */

        $answerbox = $this->adjustPreviewLocation($answerbox, $toevalqtxt, $previewloc);

        // replace $showanswerloc[n] with [SABn] for later processing after eval
        $toevalqtxt = preg_replace('/\$showanswerloc\[(.*?)\]/','[SAB$1]', $toevalqtxt);
        // same with single $showanswerloc
        $toevalqtxt = str_replace('$showanswerloc', '[SAB]', $toevalqtxt);

        // incorporate $showanswer.  Really this should be done prior to the last line,
        // and remove the redundant logic from that function, but I don't want to refactor 
        // that much right now
        if (!empty($showanswer)) {
            if (!is_array($showanswer)) {
                $displayedAnswersForParts = [0 => $showanswer];
            } else {
                foreach ($showanswer as $iidx => $atIdx) {
                    $displayedAnswersForParts[$iidx] = $atIdx;
                }
            }
        }

        /*
         * Eval the question code.
         *
         * Answer boxes are also added here, if $answerbox is defined in the
         * eval'd question code.
         *
         * Question content (raw HTML) is stored in: $evaledqtext
         */
        $GLOBALS['qgenbreak1'] = __LINE__;
        try {
          $prep = \genVarInit($qtextvars);
          eval($prep . "\$evaledqtext = \"$toevalqtxt\";"); // This creates $evaledqtext.

        /*
         * Eval the solution code.
         *
         * Solution content (raw HTML) is stored in: $evaledsoln
         */
         $GLOBALS['qgenbreak2'] = __LINE__;
         $prep = \genVarInit($solnvars);
         eval($prep . "\$evaledsoln = \"$toevalsoln\";"); // This creates $evaledsoln.
       } catch (\Throwable $t) {
          $this->addError(
              _('Caught error while evaluating the text in this question: ')
              . $t->getMessage());
          $evaledqtext = '';
          $evaledsoln = '';
        }
        $detailedSolutionContent = $this->getDetailedSolutionContent($evaledsoln);

        /*
         * Possibly adjust the showanswer if it doesn't look right
         */
        $doShowDetailedSoln = false;
        if (isset($showanswer) && is_array($showanswer) && is_array($answerbox) && count($showanswer) < count($answerbox)) {
            $showansboxloccnt = substr_count($evaledqtext,'$showanswerloc') + substr_count($evaledqtext,'[SAB');
            if ($showansboxloccnt > 0 && count($answerbox) > $showansboxloccnt && count($showanswer) == $showansboxloccnt) {
                // not enough showanswerloc boxes for all the parts.  
                /*
                  This approach combined to a single showanswer
                $questionWriterVars['showanswer'] = implode('<br>', $showanswer);
                $toevalqtxt = preg_replace('/(\$showanswerloc\[.*?\]|\[SAB.*?\])(\s*<br\/><br\/>)?/','', $toevalqtxt);
                */
                // this approach will only show the manually placed boxes, and only show them after all 
                // preceedingly-indexed parts have been cleared for answer showing.
                ksort($showanswer);
                $_lastPartUsed = -1;
                $_thisIsReady = true;
                $doShowDetailedSoln = true;
                foreach ($showanswer as $kidx=>$atIdx) {
                    $_thisIsReady = true;
                    for ($iidx=$_lastPartUsed+1; $iidx <= $kidx; $iidx++) {
                        if (empty($doShowAnswerParts[$iidx]) && !$doShowAnswer) {
                            $_thisIsReady = false;
                            $doShowDetailedSoln = false;
                            for ($siidx=$iidx; $siidx < $kidx; $siidx++) {
                                $doShowAnswerParts[$siidx] = false;
                            }
                            break;
                        } else if ($iidx < $kidx) {
                            $doShowAnswerParts[$iidx] = false;
                        }
                    }
                    $doShowAnswerParts[$kidx] = $_thisIsReady;
                    $_lastPartUsed = $kidx;
                }
                $doShowAnswer = false; // disable automatic display of answers
            }
        }

        /*
         * Get the "Show Answer" button location.
         */

        // This variable must be named $showanswerloc, as it may be used by
        // the question writer.
        $showanswerloc = $this->getShowAnswerLocation($doShowAnswer, $doShowAnswerParts,
          $answerbox, $entryTips, $displayedAnswersForParts, $questionWriterVars,
          $anstypes ?? $quesData['qtype']);

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

        /*
         *  Handle sequenial multipart, now that all answerboxes have been inserted
         *
         *  TODO: look earlier as well, and prevent answerbox generation in obvious
         *  cases where $answerbox or [AB#] is already in the question text
         */
        if (isset($seqPartDone)) {
          $seqParts = preg_split('~(<p[^>]*>(<(span|em|strong)[^>]*>)*\s*///+\s*(<\/[^>]*>)*</p[^>]*>|<br\s*/?><br\s*/?>\s*(<(span|em|strong)[^>]*>)*\s*///+\s*(<\/[^>]*>)*\s*<br\s*/?><br\s*/?>)~', $evaledqtext);
          if (count($seqParts) > 1) {
            $newqtext = '';
            $lastGroupDone = true;
            foreach ($seqParts as $k=>$seqPart) {
              $thisGroupDone = !empty($seqGroupDone[$k]);
              preg_match_all('/<(input|select|textarea)[^>]*name="?qn(\d+)/', $seqPart, $matches);
              foreach ($matches[2] as $qnrefnum) {
                $pn = $qnrefnum % 1000;
                if (!$lastGroupDone) { // not ready for it - unset stuff
                  unset($jsParams[$qnrefnum]);
                  unset($answerbox[$pn]);
                  if (is_array($showanswerloc)) {
                    unset($showanswerloc[$pn]);
                  }
                  $jsParams['hasseqnext'] = true;
                  $thisGroupDone = false;
                }
                if ($seqPartDone !== true && empty($seqPartDone[$pn]) && !empty($answeights[$pn])) {
                  $thisGroupDone = false;
                }
              }
              if ($lastGroupDone) { // add html to output
                $newqtext .= '<p class="seqsep" role="heading" tabindex="-1">';
                $newqtext .= sprintf(_('Part %d of %d'), $k+1, count($seqParts));
                $newqtext .= '</p><div>' . $seqPart . '</div>';
              }
              $lastGroupDone = $thisGroupDone;
            }
            $evaledqtext = $newqtext;
          }
        }

        /*
         * For conditional question types, the entire question or answer box
         * block needs to be surrounded with a colored border when scored.
         *
         * Answer box generation still needs to happen, but we don't change
         * the color of those answer boxes. We do this instead.
         */

        if ($quesData['qtype'] == 'conditional') {
            $questionColor = $this->getAnswerColorFromRawScore(
                $this->questionParams->getLastRawScores(), 0, 1);
            if ($questionColor != '') {
                $evaledqtext = sprintf('<div class="%s">%s<br/></div>',
                        $questionColor, $evaledqtext);
            }
        }

        $evaledqtext = "<div class=\"question\" role=region aria-label=\"" . _('Question') . ' ' 
            . ($this->questionParams->getDisplayQuestionNumber()+1) . "\">\n"
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
         * For now, tack on the Show Answer buttons to the question code.
         * Later, we may handle these separately on the front-end
         */

        $sadiv = '';
        if (!is_array($showanswerloc) && $doShowAnswer &&
          strpos($toevalqtxt,'$showanswerloc')===false
        ) {
          $sadiv .= '<div>'.$showanswerloc.'</div>';
        } else if (is_array($showanswerloc)) {
          foreach ($showanswerloc as $iidx => $saloc) {
            // show part solution if $doShowAnswerParts for that part is enabled,
            // or if $doShowAnswer is set and $doShowAnswerParts isn't explicitly disabled
            if ((($doShowAnswer && (!isset($doShowAnswerParts[$iidx]) || $doShowAnswerParts[$iidx])) || 
                (is_array($doShowAnswerParts) && !empty($doShowAnswerParts[$iidx]))
              ) &&
              strpos($toevalqtxt,'$showanswerloc['.$iidx.']')===false
            ) {
              $sadiv .= '<div>'.$saloc.'</div>';
            } 
          }
        }
        // display detailed solution, if allowed and set
        if (($doShowAnswer || $doShowDetailedSoln) && ($quesData['solutionopts']&4)==4 && $quesData['solution'] != '') {
          if (($quesData['solutionopts']&1)==0) {
            $evaledsoln = '<i>'._('This solution is for a similar problem, not your specific version').'</i><br/>'.$evaledsoln;
          }
          if ($nosabutton) {
            $sadiv .= filter("<div><p>" . _('Detailed Solution').'</p>'. $evaledsoln .'</div>');
          } else {
            $qnidx = $this->questionParams->getDisplayQuestionNumber();
            $sadiv .= "<div><input class=\"dsbtn\" type=button value=\""._('Show Detailed Solution')."\" />";
            $sadiv .= filter(" <div class=\"hidden dsbox\" id=\"dsbox$qnidx\">$evaledsoln </div></div>\n");
          }
        }
        if ($sadiv !== '') {
          $evaledqtext .= '<div class="autoshowans">'.$sadiv.'</div>';
        }


        /*
         * Add help text / hints.
         */

        if (isset($helptext) && $showHints) {
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

        $errors = ob_get_clean();
        if ($errors != '') {
          $this->addError($errors);
        }

        $question = new Question(
            $evaledqtext,
            $jsParams,
            ($quesData['qtype'] == "multipart" && isset($answeights)) ? $answeights : array(1),
            $evaledsoln,
            $detailedSolutionContent,
            $displayedAnswersForParts,
            $externalReferences
        );

        $question->setQuestionLastMod($quesData['lastmoddate']);

        if (isset($onGetQuestion) && is_callable($onGetQuestion)) {
            $onGetQuestion();
        }

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
                    sprintf('<img src="https://%s.s3.amazonaws.com/qimages/%s" alt="%s" />',
                        $GLOBALS['AWSbucket'], $filename,
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
     * @param string $hintlabel 
     * @return string|array The hint text.
     */
    private function getHintText(array $hints, string $hintlabel)
    {
        $qdata = $this->questionParams->getQuestionData();
        $attemptn = $this->questionParams->getStudentAttemptNumber();
        $scoreiscorrect = $this->questionParams->getScoreIsCorrect();

        $thisq = $this->questionParams->getQuestionNumber() + 1;

        $hintloc = '';

        $lastkey = max(array_keys($hints));

        if ($qdata['qtype'] == "multipart" && is_array($hints[$lastkey])) { //individual part hints
            $hintloc = array();
            $partattemptn = $this->questionParams->getStudentPartAttemptCount();

            foreach ($hints as $iidx => $hintpart) {
                if (!is_array($hintpart)) { continue; } // mixed formats
                $lastkey = max(array_keys($hintpart));
                $hintloc[$iidx] = '';
                if (is_array($hintpart[$lastkey])) {  // has "show for group of questions"
                    $usenum = 10000;
                    $allcorrect = true;
                    $maxatt = 0;
                    $showfor = array_map('intval', $hintpart[$lastkey][1]);
                    foreach ($showfor as $subpn) {
                        if (!isset($partattemptn[$subpn])) {
                            $partattemptn[$subpn] = 0;
                        }
                        if (isset($scoreiscorrect[$thisq][$subpn]) && $scoreiscorrect[$thisq][$subpn] == 1) {
                            continue; // don't consider correct
                        } else {
                            $allcorrect = false;
                        }
                        if ($partattemptn[$subpn] > $lastkey && $lastkey < $usenum) {
                            $usenum = $lastkey;
                        } else if ($partattemptn[$subpn] < $usenum) {
                            $usenum = $partattemptn[$subpn];
                        }
                    }
                    if ($allcorrect) {
                        $maxatt = min($partattemptn)-1;
                        if ($maxatt > $lastkey) {
                            $usenum = $lastkey;
                        } else {
                            $usenum = max($maxatt,0);
                        }
                    }
                    if ($usenum == 10000) { 
                        continue;
                    }
                    if (!empty($hintpart[$usenum]) && is_array($hintpart[$usenum])) {
                        $hintpart[$usenum] = $hintpart[$usenum][0];
                    }
                } else {
                    if (!isset($partattemptn[$iidx])) {
                        $partattemptn[$iidx] = 0;
                    }
                    if ($partattemptn[$iidx] > $lastkey) {
                        $usenum = $lastkey;
                    } else {
                        $usenum = $partattemptn[$iidx];
                        if (!empty($scoreiscorrect[$thisq][$iidx]) && $scoreiscorrect[$thisq][$iidx]==1) {
                            $usenum--;
                        }
                    }
                }
                if (!empty($hintpart[$usenum])) {
                    if (strpos($hintpart[$usenum], '</div>') !== false) {
                        $hintloc[$iidx] = $hintpart[$usenum];
                    } else if (strpos($hintpart[$usenum], 'button"') !== false) {
                        $hintloc[$iidx] = "<p>{$hintpart[$usenum]}</p>\n";
                    } else if (!empty($hintlabel)) {
                        $hintloc[$iidx] = "<p>$hintlabel {$hintpart[$usenum]}</p>\n";
                    } else {
                        $hintloc[$iidx] = "<p><i>" . _('Hint:') . "</i> {$hintpart[$usenum]}</p>\n";
                    }
                }
            }
        } else { //one hint for question
            if ($attemptn > $lastkey) {
                $usenum = $lastkey;
            } else {
                $usenum = $attemptn;
                if (isset($scoreiscorrect) && ( 
                    (!is_array($scoreiscorrect[$thisq]) && $scoreiscorrect[$thisq] == 1) ||
                    (is_array($scoreiscorrect[$thisq]) && min($scoreiscorrect[$thisq]) == 1)
                )) {
                    $usenum--;  // if correct, use prior hint
                }
            }
            
            if (!empty($hints[$usenum])) {
                if (!is_string($hints[$usenum])) { // shouldn't be, but a hack to get old bad code from throwing errors.
                    $hintloc = $hints[$usenum]; 
                } else if (strpos($hints[$usenum], '</div>') !== false) {
                    $hintloc = $hints[$usenum];
                } else if (strpos($hints[$usenum], 'button"') !== false) {
                    $hintloc = "<p>{$hints[$usenum]}</p>\n";
                } else if (!empty($hintlabel)) {
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
        $qnidx = $this->questionParams->getDisplayQuestionNumber();

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
     * @param int $doShowAnswer @see ShowAnswer
     * @param array|string $doShowAnswerParts @see ShowAnswer
     * @param string|array $answerBoxes String for single answer box, array for multiple.
     * @param array $entryTips Tooltips displayed for answer boxes.
     * @param array $displayedAnswersForParts
     * @param array $questionWriterVars
     * @param array|string $anstypes or qtype
     * @return array|string
     */
    private function getShowAnswerLocation(int $doShowAnswer,
                                           $doShowAnswerParts,
                                           $answerBoxes,
                                           array $entryTips,
                                           array $displayedAnswersForParts,
                                           array $questionWriterVars,
                                           $procanstypes
    )
    {
        $qnidx = $this->questionParams->getDisplayQuestionNumber();
        $shanspt = $displayedAnswersForParts;

        if (ShowAnswer::ALWAYS == $doShowAnswer) {
            $doshowans = true;
            $nosabutton = true;
        } else {
            $doshowans = ($doShowAnswer > 0);
            $nosabutton = false;
        }

        // We need to "unpack" this into locally scoped variables.
        foreach ($questionWriterVars as $k => $v) {
            ${$k} = $v;
        }

        /*
         * Business logic begins here.
         */

        if (isset($showanswer) && !is_array($showanswer)) {
            $showanswer = $this->fixDegrees($showanswer, $procanstypes);
        } else if (isset($showanswer)) {
            foreach ($showanswer as $k=>$v) {
                if ($v === null || !isset($procanstypes[$k])) {continue;}
                $showanswer[$k] = $this->fixDegrees($v, $procanstypes[$k]);
            }
        }
        if (!is_array($shanspt)) {
            $shanspt = $this->fixDegrees($shanspt, $procanstypes);
        } else {
            foreach ($shanspt as $k=>$v) {
                if ($v === null) {continue;}
                $shanspt[$k] = $this->fixDegrees($v, 
                    is_array($procanstypes) ? ($procanstypes[$k] ?? '') : $procanstypes);
            }
        }
        
        $showanswerloc = '';

        if (isset($showanswer) && !is_array($showanswer) && $doshowans) {  //single showanswer defined
            $showanswerloc = (isset($showanswerstyle) && $showanswerstyle == 'inline') ? '<span>' : '<div>';
            if ($nosabutton) {
                $showanswerloc .= filter(_('Answer:') . " $showanswer\n");
            } else {
                $showanswerloc .= "<input class=\"sabtn\" type=button value=\"" . _('Show Answer') . "\" />";
                $showanswerloc .= filter(" <span id=\"ans$qnidx\" class=\"hidden\">$showanswer </span>\n");
            }
            $showanswerloc .= (isset($showanswerstyle) && $showanswerstyle == 'inline') ? '</span>' : '</div>';
        } else if (!isset($showanswer) || is_array($showanswer)) {
            $showanswerloc = array();
            foreach ($entryTips as $iidx => $entryTip) {
              $showanswerloc[$iidx] = '';
              if ($doshowans || (is_array($doShowAnswerParts) && !empty($doShowAnswerParts[$iidx]))) {
                if ((!isset($showanswer) || (is_array($showanswer) && !isset($showanswer[$iidx]))) && $shanspt[$iidx] !== '') {
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
                } else if (isset($showanswer) && is_array($showanswer) && isset($showanswer[$iidx])) { //use part specific showanswer
                    if ($nosabutton) {
                        $showanswerloc[$iidx] .= "<span id=\"showansbtn$qnidx-$iidx\">" . filter(_('Answer:') . " {$showanswer[$iidx]}</span>\n");
                    } else {
                        $showanswerloc[$iidx] .= "<input id=\"showansbtn$qnidx-$iidx\" class=\"sabtn\" type=button value=\"" . _('Show Answer') . "\" />";// AMprocessNode(document.getElementById(\"ans$qnidx-$iidx\"));'>";
                        $showanswerloc[$iidx] .= filter(" <span id=\"ans$qnidx-$iidx\" class=\"hidden\">{$showanswer[$iidx]}</span>\n");
                    }
                }
                if ($showanswerloc[$iidx] == '') {
                  unset($showanswerloc[$iidx]);
                } else {
                  $showanswerloc[$iidx] = (isset($showanswerstyle) && $showanswerstyle == 'inline')
                    ? '<span>'.$showanswerloc[$iidx].'</span>'
                    : '<div>'.$showanswerloc[$iidx].'</div>';
                }
              }
            }
            if (!is_array($answerBoxes) && count($showanswerloc) < 2) { //not a multipart question
                $showanswerloc = str_replace($qnidx . '-0"', $qnidx . '"', $showanswerloc[0] ?? '');
            }
        }

        return $showanswerloc;
    }

    /**
     * Get external references for this question.
     *
     * @return array External references as raw HTML.
     */
    private function getExternalReferences(): array
    {
        $showhints = $this->questionParams->getShowHints();
        $qdata = $this->questionParams->getQuestionData();
        $qnidx = $this->questionParams->getDisplayQuestionNumber();
        $qidx = $this->questionParams->getDbQuestionSetId();
        $qid = $this->questionParams->getQuestionId();
        $qref = '';

        $externalReferences = [];
        
        $extrefwidth = isset($GLOBALS['CFG']['GEN']['extrefsize']) ? $GLOBALS['CFG']['GEN']['extrefsize'][0] : 700;
        $extrefheight = isset($GLOBALS['CFG']['GEN']['extrefsize']) ? $GLOBALS['CFG']['GEN']['extrefsize'][1] : 500;
        $vidextrefwidth = isset($GLOBALS['CFG']['GEN']['vidextrefsize']) ? $GLOBALS['CFG']['GEN']['vidextrefsize'][0] : 873;
        $vidextrefheight = isset($GLOBALS['CFG']['GEN']['vidextrefsize']) ? $GLOBALS['CFG']['GEN']['vidextrefsize'][1] : 500;


        if (($showhints&2)==2 && $qdata['extref'] != '') {
            $extref = explode('~~', $qdata['extref']);

            if ($qid > 0 && (!isset($_SESSION['isteacher'])
                    || $_SESSION['isteacher'] == false) && !isset($_SESSION['stuview'])) {
                $qref = $qid . '-' . ($qnidx + 1);
            } 
            for ($i = 0; $i < count($extref); $i++) {
                $extrefpt = explode('!!', $extref[$i]);
                if (strpos($extrefpt[1],'youtube.com/watch')!==false ||
                            strpos($extrefpt[1],'youtu.be/')!==false ||
                            strpos($extrefpt[1],'vimeo.com/')!==false
                        ) {
                    $extrefpt[1] = $GLOBALS['basesiteurl'] . "/assessment/watchvid.php?url=" . Sanitize::encodeUrlParam($extrefpt[1]);
                    $externalReferences[] = [
                        'label' => $extrefpt[0],
                        'url' => $extrefpt[1],
                        'w' => $vidextrefwidth,
                        'h' => $vidextrefheight,
                        'ref' => $qref,
                        'descr' => !empty($extrefpt[3]) ? $extrefpt[3] : '' 
                    ];
                    //$externalReferences .= formpopup($extrefpt[0], $extrefpt[1], $vidextrefwidth, $vidextrefheight, "button", true, "video", $qref);
                } else {
                    //$externalReferences .= formpopup($extrefpt[0], $extrefpt[1], $extrefwidth, $extrefheight, "button", true, "text", $qref);
                    $externalReferences[] = [
                        'label' => $extrefpt[0],
                        'url' => $extrefpt[1],
                        'w' => $extrefwidth,
                        'h' => $extrefheight,
                        'ref' => $qref,
                        'descr' => !empty($extrefpt[3]) ? $extrefpt[3] : '' 
                    ];
                }
            }
        }
        if (($showhints&4)==4 && ($qdata['solutionopts'] & 2) == 2 && $qdata['solution'] != '') {
            $addr = $GLOBALS['basesiteurl'] . "/assessment/showsoln.php?id=" . $qidx . '&sig=' . md5($qidx . $_SESSION['secsalt']);
            $addr .= '&t=' . ($qdata['solutionopts'] & 1) . '&cid=' . $GLOBALS['cid'];
            if ($GLOBALS['cid'] == 'embedq' && isset($GLOBALS['theme'])) {
                $addr .= '&theme=' . Sanitize::encodeUrlParam($GLOBALS['theme']);
            }
            //$externalReferences .= formpopup(_("Written Example"), $addr, $extrefwidth, $extrefheight, "button", true, "soln", $qref);
            $externalReferences[] = [
                'label' => 'ex',
                'url' => $addr,
                'w' => $extrefwidth,
                'h' => $extrefheight,
                'ref' => $qref
            ]; 
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

    private function fixDegrees($str, $atype) 
    {
        if ($str === null) { return ''; }
        if (is_array($atype) || $atype == 'choices' || $atype == 'multans' || $atype == 'string') {
            return $str;
        }
        return preg_replace_callback('/`(.*?)`/s', function($m) {
            return '`' . str_replace(['degrees','degree'],'^@', $m[1]).'`';
        }, $str);
    }
}

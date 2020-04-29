<?php
/*
 * IMathAS: Assessment Standalone display class
 * (c) 2020 David Lippman
 */

/*

Javascript needs:
initq: like renderandtrack, set initvalues
  use helps to render helps in this mode
getChangedQuestions: get questions that have changed
doonsubmit: prep for submit

Behavior will be slightly different for form-submission vs ajax submission
Ideally handle both

*/

require_once(__DIR__ . '/AssessUtils.php');
require_once(__DIR__ . '/../filter/filter.php');
require_once(__DIR__ . '/questions/QuestionGenerator.php');
require_once(__DIR__ . '/questions/models/QuestionParams.php');
require_once(__DIR__ . '/questions/models/ShowAnswer.php');
require_once(__DIR__ . '/questions/ScoreEngine.php');
require_once(__DIR__ . '/questions/models/ScoreQuestionParams.php');

use IMathAS\assess2\questions\QuestionGenerator;
use IMathAS\assess2\questions\models\QuestionParams;
use IMathAS\assess2\questions\models\ShowAnswer;
use IMathAS\assess2\questions\ScoreEngine;
use IMathAS\assess2\questions\models\ScoreQuestionParams;

class AssessStandalone {

  private $DBH = null;
  private $state = array();
  private $qdata = array();
  private $now = 0;

  /**
   * Construct object
   * @param object $DBH PDO Database Handler
   */
  function __construct($DBH) {
    $this->DBH = $DBH;
    $this->now = time();
  }
  /*
   * setState($arr)
   * sets the state using an array of values.  Should have indexes:
   *  $arr['seeds'] = array of qn=>seed
   *  $arr['qsid'] = array of qn=>qsetid
   *  $arr['stuanswers'] = stuanswers for all questions, (qn+1)-indexed
   *  $arr['stuanswerval'] = stuanswersval for all questions, (qn+1)-indexed
   *  $arr['scorenonzero'] = scorenonzero for all questions, (qn+1)-indexed
   *  $arr['scoreiscorrect'] = scoreiscorrect for all questions, (qn+1)-indexed
   *  $arr['partattemptn'] = array of qn=>pn=>num of attempts made
   *  $arr['rawscores'] = array of qn=>pn=>rawscores
   */
  function setState($arr) {
    $this->state = $arr;
  }

  /*
   * getState()
   * gets the state an associative array (see setState)
   */
  function getState() {
    return $this->state;
  }

  /* setQuestionData($qsid, $data)
  * Sets question data
  */
  function setQuestionData($qsid, $data) {
    $this->qdata[$qsid] = $data;
  }

  /*
   * loadQuestionData()
   * loads the questions from state
   */
  function loadQuestionData() {
    $ph = Sanitize::generateQueryPlaceholders($this->state['qsid']);
    $stm = $this->DBH->prepare("SELECT * FROM imas_questionset WHERE id IN ($ph)");
    $stm->execute(array_values($this->state['qsid']));
    while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
      $this->qdata[$row['id']] = $row;
    }
  }

  /*
   * displayQuestion($qn, $includeCorrect)
   * displays the question $qn, using details from state
   * $includeCorrect=true to include correct answer
   */
  function displayQuestion($qn, $includeCorrect=false) {
    $qsid = $this->state['qsid'][$qn];
    $attemptn = empty($this->state['partattemptn'][$qn]) ? 0 : max($this->state['partattemptn'][$qn]);
    $seqPartDone = array();
    if (!empty($this->state['rawscores'][$qn])) {
      foreach ($this->state['rawscores'][$qn] as $pn=>$sc) {
        $seqPartDone[$pn] = ($sc>.98);
      }
    }
    $questionParams = new QuestionParams();
    $questionParams
        ->setDbQuestionSetId($qsid)
        ->setQuestionData($this->qdata[$qsid])
        ->setQuestionNumber($qn)
        ->setQuestionId(0)
        ->setAssessmentId(0)
        ->setQuestionSeed($this->state['seeds'][$qn])
        ->setShowHints(true)
        ->setShowAnswer(true)
        ->setShowAnswerParts(array())
        ->setShowAnswerButton(true)
        ->setStudentAttemptNumber($attemptn)
        ->setStudentPartAttemptCount($this->state['partattemptn'][$qn])
        ->setAllQuestionAnswers($this->state['stuanswers'])
        ->setAllQuestionAnswersAsNum($this->state['stuanswersval'])
        ->setScoreNonZero($this->state['scorenonzero'])
        ->setScoreIsCorrect($this->state['scoreiscorrect'])
        ->setLastRawScores($this->state['rawscores'][$qn])
        ->setSeqPartDone($seqPartDone);;

    $questionGenerator = new QuestionGenerator($this->DBH,
        $GLOBALS['RND'], $questionParams);
    $question = $questionGenerator->getQuestion();

    list($qout,$scripts) = $this->parseScripts($question->getQuestionContent());
    $jsparams = $question->getJsParams();
    if (count($scripts) > 0) {
      $jsparams['scripts'] = $scripts;
    }
    $jsparams['helps'] = $question->getExternalReferences();
    $answeights = $question->getAnswerPartWeights();

    if ($includeCorrect) {
      $jsparams['ans'] = $question->getCorrectAnswersForParts();
      $jsparams['stuans'] = $stuanswers[$qn+1];
    }

    return array('html' => $qout, 'jsparams' => $jsparams, 'errors'=>$question->getErrors());
  }

  /*
   * scoreQuestion($qn, $parts_to_score)
   * scores the question $qn, using details from state
   * $parts_to_score: array of pn=>bool for whether to score the part
   */
  function scoreQuestion($qn, $parts_to_score = true) {
    $qsid = $this->state['qsid'][$qn];

    $attemptn = empty($this->state['partattemptn'][$qn]) ? 0 : max($this->state['partattemptn'][$qn]);

    $scoreEngine = new ScoreEngine($this->DBH, $GLOBALS['RND']);

    $scoreQuestionParams = new ScoreQuestionParams();
    $scoreQuestionParams
        ->setUserRights($GLOBALS['myrights'])
        ->setRandWrapper($GLOBALS['RND'])
        ->setQuestionNumber($qn)
        ->setQuestionData($this->qdata[$qsid])
        ->setAssessmentId(0)
        ->setDbQuestionSetId($qsid)
        ->setQuestionSeed($this->state['seeds'][$qn])
        ->setGivenAnswer($_POST['qn'.$qn])
        ->setAttemptNumber($attemptn)
        ->setAllQuestionAnswers($this->state['stuanswers'])
        ->setAllQuestionAnswersAsNum($this->state['stuanswersval'])
        ->setQnpointval(1);

    $scoreResult = $scoreEngine->scoreQuestion($scoreQuestionParams);

    $scores = $scoreResult['scores'];
    $rawparts = $scoreResult['rawScores'];
    $partla = $scoreResult['lastAnswerAsGiven'];
    $partlaNum = $scoreResult['lastAnswerAsNumber'];

    foreach ($partla as $k=>$v) {
      if ($parts_to_score === true || !empty($parts_to_score[$k])) {
        if (!isset($this->state['partattemptn'][$qn])) {
          $this->state['partattemptn'][$qn] = array($k=>1);
        } else {
          $this->state['partattemptn'][$qn][$k]++;
        }
        if (count($partla)>1) {
          if (!is_array($this->state['stuanswers'][$qn+1])) {
            $this->state['stuanswers'][$qn+1] = array();
          }
          if (!is_array($this->state['stuanswersval'][$qn+1])) {
            $this->state['stuanswersval'][$qn+1] = array();
          }
          $this->state['stuanswers'][$qn+1][$k] = $v;
          $this->state['stuanswersval'][$qn+1][$k] = $partlaNum[$k];
        } else {
          $this->state['stuanswers'][$qn+1] = $v;
          $this->state['stuanswersval'][$qn+1] = $partlaNum[$k];
        }
        $this->state['rawscores'][$qn][$k] = $rawparts[$k];
      }
    }

    $score = array_sum($scores);
    $this->state['scorenonzero'][$qn+1] = ($score > 0);
    $this->state['scoreiscorrect'][$qn+1] = ($score > .98);
    return array('scores'=>$scores, 'raw'=>$rawparts, 'errors'=>$scoreResult['errors']);
  }

  private function parseScripts($html) {
    $scripts = array();
    preg_match_all("|<script([^>]*)>(.*?)</script>|s", $html, $matches, PREG_SET_ORDER);
    foreach ($matches as $match) {
      if (strlen(trim($match[2])) == 0 && preg_match('/src="(.*?)"/', $match[1], $sub)) {
        $scripts[] = array('src', $sub[1]);
      } else {
        if (preg_match('/document\.write.*?script.*?src="(.*?)"/', $match[2], $sub)) {
          $scripts[] = array('src', $sub[1]);
        }
        $scripts[] = array('code', $match[2]);
      }
    }
    $html = preg_replace("|<script([^>]*)>(.*?)</script>|s", '', $html);
    return array($html, $scripts);
  }

}

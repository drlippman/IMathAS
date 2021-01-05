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
   *  $arr['stuanswersval'] = stuanswersval for all questions, (qn+1)-indexed
   *  $arr['scorenonzero'] = scorenonzero for all questions, (qn+1)-indexed
   *  $arr['scoreiscorrect'] = scoreiscorrect for all questions, (qn+1)-indexed
   *  $arr['partattemptn'] = array of qn=>pn=>num of attempts made
   *  $arr['rawscores'] = array of qn=>pn=>rawscores
   *  $arr['wrongfmt'] = array of qn=>pn=>wrongfmt
   */
  function setState($arr) {
      foreach (['stuanswers','stuanswersval','scorenonzero','scoreiscorrect','partattemptn','rawscore'] as $f) {
          if (!isset($arr[$f])) {
              $arr[$f] = [];
          }
      }
      foreach ($arr['seeds'] as $qn=>$seed) {
        foreach (['scorenonzero','scoreiscorrect'] as $f) {
            if (!isset($arr[$f][$qn+1])) {
                $arr[$f][$qn+1] = false;
            }
        }
        foreach (['partattemptn','rawscore'] as $f) {
            if (!isset($arr[$f][$qn])) {
                $arr[$f][$qn] = [];
            }
        }
      }
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

  function getOpVal($options, $key, $default=null) {
      if (isset($options[$key])) {
          return $options[$key];
      } else if (isset($this->state[$key])) {
          return $this->state[$key];
      } else {
          return $default;
      }
  }

  /*
   * displayQuestion($qn, $options)
   * displays the question $qn, using details from state
   * Values in $options can override the state values
   */
  function displayQuestion($qn, $options=[]) {
    $qsid = $this->state['qsid'][$qn];
    $attemptn = empty($this->state['partattemptn'][$qn]) ? 0 : max($this->state['partattemptn'][$qn]);
    $maxtries = $this->getOpVal($options, 'maxtries', 0);
    $showansafter = $this->getOpVal($options, 'showansafter', 0); 

    $hidescoremarkers = !empty($this->getOpVal($options, 'hidescoremarkers', false));

    $showansparts = array();
    $showans = false;

    if (!empty($options['showallparts'])) {
      $seqPartDone = true;
      $showans = true;
    } else {
      $seqPartDone = array();
      if (!empty($this->state['rawscores'][$qn])) {
        foreach ($this->state['rawscores'][$qn] as $pn=>$sc) {
            if ($hidescoremarkers || $sc == -2) {
                $seqPartDone[$pn] = ($this->state['partattemptn'][$qn][$pn] > 0);
            } else if ($maxtries > 0 &&
              $this->state['partattemptn'][$qn][$pn] >= $maxtries
            ) {
                $seqPartDone[$pn] = true;
            } else {
                $seqPartDone[$pn] = ($sc>.98);
            }
            if ($showansafter > 0 && 
                ($this->state['partattemptn'][$qn][$pn] >= $showansafter || $sc>.98)
            ) {
                $showansparts[$pn] = true;
            } 
        }
        $showans = true;
        if (!is_array($this->state['scoreiscorrect'][$qn+1])) {
            $showans = $showansparts[0];
        } else {
            foreach ($this->state['scoreiscorrect'][$qn+1] as $pn=>$sc) {
                if (empty($showansparts[$pn])) {
                    $showans = false;
                    break;
                }
            }
        }
      }
    }

    $showans = !empty($this->getOpVal($options, 'showans', false)) || $showans;
    $showhints = $this->getOpVal($options, 'showhints', 3);
    $rawscores = $this->state['rawscores'][$qn];

    if ($hidescoremarkers) {
        $rawscores = array();
    }

    // showans is show-for-all override
    // showansparts is per-part show ans

    $correctAnswerWrongFormat = array();
    if (!empty($this->state['wrongfmt'][$qn])) {
        foreach ($this->state['wrongfmt'][$qn] as $pn=>$v) {
            $correctAnswerWrongFormat[$pn] = ($v>0);
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
        ->setShowHints($showhints)
        ->setShowAnswer($showans)
        ->setShowAnswerParts($showansparts)
        ->setShowAnswerButton(true)
        ->setStudentAttemptNumber($attemptn)
        ->setStudentPartAttemptCount($this->state['partattemptn'][$qn])
        ->setAllQuestionAnswers($this->state['stuanswers'])
        ->setAllQuestionAnswersAsNum($this->state['stuanswersval'])
        ->setScoreNonZero($this->state['scorenonzero'])
        ->setScoreIsCorrect($this->state['scoreiscorrect'])
        ->setLastRawScores($rawscores)
        ->setSeqPartDone($seqPartDone)
        ->setCorrectAnswerWrongFormat($correctAnswerWrongFormat);

    if (!empty($options['printformat'])) {
        $questionParams->setPrintFormat(true);
    }

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

    if (!empty($options['includeans'])) {
      $jsparams['ans'] = $question->getCorrectAnswersForParts();
      $jsparams['stuans'] = $stuanswers[$qn+1];
    }

    if ($maxtries > 0) {
      $disabled = array();
      // TODO: is this correct?  Need it to work for conditional, but
      // seems like it'd also hit singlescore
      if (count($answeights)==1 && count($this->state['partattemptn'][$qn])>1
        && $this->state['partattemptn'][$qn][0] >= $maxtries
      ) {
        $disabled[] = 'all';
      } else {
        foreach($this->state['partattemptn'][$qn] as $pn=>$att) {
          if ($att >= $maxtries) {
            $disabled[] = $pn;
          }
        }
      }
      $jsparams['maxtries'] = $maxtries;
      $jsparams['partatt'] = $this->state['partattemptn'][$qn];
      $jsparams['disabled'] = $disabled;
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
        if (!empty($scoreResult['correctAnswerWrongFormat'][$k])) {
            if (!isset($this->state['wrongfmt'])) {
                $this->state['wrongfmt'] = array();
            }
            if (!isset($this->state['wrongfmt'][$qn])) {
                $this->state['wrongfmt'][$qn] = array();
            }
            $this->state['wrongfmt'][$qn][$k] = 1;
        } else if (!empty($this->state['wrongfmt'][$qn][$k])) {
            unset($this->state['wrongfmt'][$qn][$k]);
        }
      }
      if ($parts_to_score === true || !empty($parts_to_score[$k]) ||
        (isset($this->state['rawscores'][$qn][$k]) &&
        $this->state['rawscores'][$qn][$k] >= 0)
      ) { // rec if scored, and update existing
        $this->state['rawscores'][$qn][$k] = $rawparts[$k];
      }
    }
    $allPartsAns = (count($this->state['partattemptn'][$qn]) == count($scoreResult['answeights']));
    $score = array_sum($scores);
    if (count($partla) > 1) {
      $this->state['scorenonzero'][$qn+1] = array();
      $this->state['scoreiscorrect'][$qn+1] = array();
      foreach ($partla as $k=>$v) {
        if (!isset($this->state['rawscores'][$qn][$k])) {
          $this->state['scorenonzero'][$qn+1][$k] = -1;
          $this->state['scoreiscorrect'][$qn+1][$k] = -1;
        } else {
          $this->state['scorenonzero'][$qn+1][$k] = ($this->state['rawscores'][$qn][$k]>0);
          $this->state['scoreiscorrect'][$qn+1][$k] = ($this->state['rawscores'][$qn][$k]>.98);
        }
      }
    } else {
      $this->state['scorenonzero'][$qn+1] = ($score > 0);
      $this->state['scoreiscorrect'][$qn+1] = ($score > .98);
    }

    return array(
        'scores'=>$scores,
        'raw'=>$rawparts,
        'errors'=>$scoreResult['errors'],
        'allans'=>$allPartsAns
    );
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

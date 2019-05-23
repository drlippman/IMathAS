<?php
/*
 * IMathAS: Assessment Settings Class
 * (c) 2019 David Lippman
 */

// TODO: calc and record assessment-level timeontask

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

//TODO: should be passed to displayq instead of using globals
$useeqnhelper = 4;
$showtips = 2;

/**
 * Primary class for working with assessment records
 */
class AssessRecord
{
  private $DBH = null;
  private $curUid = null;
  private $curAid = null;
  private $assessRecord = null;
  private $assess_info = null;
  private $hasRecord = false;
  private $data = null;
  private $tmpdata = null;
  private $is_practice = false;
  private $status = 'no_record';
  private $now = 0;
  private $need_to_record = false;
  private $penalties = array();

  /**
   * Construct object
   * @param object $DBH PDO Database Handler
   * @param object $assess_info  AssessInfo instance
   */
  function __construct($DBH, $assess_info = null, $is_practice = false) {
    $this->DBH = $DBH;
    $this->assess_info = $assess_info;
    $this->curAid = $assess_info->getSetting('id');
    $this->is_practice = $is_practice;
    $this->now = time();
  }

  /**
   * Load an assessment record given the user id and assessment id.
   * @param  integer $userid  The user ID
   * @return void
   */
  public function loadRecord($userid) {
    $this->curUid = $userid;
    $stm = $this->DBH->prepare("SELECT * FROM imas_assessment_records WHERE userid=? AND assessmentid=?");
    $stm->execute(array($userid, $this->curAid));
    $this->assessRecord = $stm->fetch(PDO::FETCH_ASSOC);
    if ($this->assessRecord === false) {
      $this->hasRecord = false;
      $this->assessRecord = null;
    } else {
      $this->hasRecord = true;
    }
    $this->tmpdata = null;
  }

  /**
   * Set the assessment record using pre-loaded data.
   * @param array $recordData  assoc array of imas_assessment_records.*
   */
  public function setRecord($recordData) {
    $this->curUid = $recordData['userid'];
    $this->assessRecord = $recordData;
    $this->hasRecord = true;
    $this->tmpdata = null;
  }

  /**
   * Sets whether we're working in practice mode
   * @param boolean $is_practice  True if working in practice mode
   */
  public function setInPractice($is_practice) {
    if ($is_practice !== $this->is_practice) {
      $tmp = $this->tmpdata;
      $this->tmpdata = $this->data;
      $this->data = $tmp;
    }
    $this->is_practice = $is_practice;
  }

  /**
   * Save the record to the database, if something has changed
   * @return void
   */
  public function saveRecordIfNeeded() {
    if ($this->need_to_record) {
      $this->saveRecord();
    }
  }

  /**
   * Save the record to the database
   * @return void
   */
  public function saveRecord() {
    if ($this->curUid === null || $this->curAid === null) {
      // bail if the userid isn't set
      return false;
    }

    $qarr = array();
    $fields = array('lti_sourcedid', 'timeontask', 'starttime', 'lastchange',
                    'score', 'status');
    foreach ($fields as $field) {
      $qarr[':'.$field] = $this->assessRecord[$field];
    }
    if (!$this->is_practice && $this->data !== null) {
      $fields[] = 'scoreddata';
      $qarr[':scoreddata'] = gzencode(json_encode($this->data));
    }
    if ($this->is_practice && $this->data !== null) {
      $fields[] = 'practicedata';
      $qarr[':practicedata'] = gzencode(json_encode($this->data));
    }

    if ($this->hasRecord) {
      // updating existing record
      $sets = array();
      foreach ($fields as $field) {
        $sets[] = $field.'=:'.$field;
      }
      $setlist = implode(',', $sets);
      $query = "UPDATE imas_assessment_records SET $setlist WHERE ";
      $query .= 'assessmentid=:aid AND ';
      $qarr[':aid'] = $this->curAid;
      if ($this->assessRecord['agroupid'] > 0) {
        $query .= "agroupid=:agroupid";
        $qarr[':agroupid'] = $this->assessRecord['agroupid'];
      } else {
        $query .= "userid=:userid";
        $qarr[':userid'] = $this->curUid;
      }
      $stm = $this->DBH->prepare($query);
      $stm->execute($qarr);

      $this->need_to_record = false;
    }
  }

  /**
   * Create a new record in the database.  Call after loadRecord.
   *
   * @param  array   $users         Array of users to create record for.
   *                                If false, current userid will be used. (def: false)
   * @param  int     $stugroupid    The stugroup ID, or 0 if not group (def: 0)
   * @param  boolean $recordStart   true to record the starttime now (def: true)
   * @param  string  $lti_sourcedid The LTI sourcedid (def: '')
   * @return void
   */
  public function createRecord($users = false, $stugroupid = 0, $recordStart = true, $lti_sourcedid = '') {
    // if group, lookup group members. Otherwise just use current user
    if ($users === false) {
      $users = array($this->curUid);
    }

    //initiale a blank record
    $this->assessRecord = array(
      'assessmentid' => $this->curAid,
      'userid' => $this->curUid,
      'agroupid' => $stugroupid,
      'lti_sourcedid' => $lti_sourcedid,
      'ver' => 2,
      'timeontask' => 0,
      'starttime' => $recordStart ? $this->now : 0,
      'lastchange' => 0,
      'score' => 0,
      'status' => 0,  // overridden later
      'scoreddata' => '',
      'practicedata' => ''
    );

    // generate practice data if in practice
    $waspractice = $this->is_practice;
    if ($this->is_practice) {
      $this->buildAssessData($recordStart);
      $practicetosave = ($this->data !== null) ? gzencode(json_encode($this->data)) : '';
      $this->assessRecord['practicedata'] = $practicetosave;
      $this->setInPractice(false);
    } else {
      $practicetosave = '';
    }

    //generate scored data
    $this->buildAssessData($recordStart && !$waspractice);
    $scoredtosave = ($this->data !== null) ? gzencode(json_encode($this->data)) : '';
    $this->assessRecord['scoreddata'] = $scoredtosave;

    // switch back to practice if started that way
    if ($waspractice) {
      $this->setInPractice(true);
    }

    // Save to Database
    $qarr = array();
    $vals = array();
    foreach ($users as $uid) {
      $vals[] = '(?,?,?,?,?,?,?,?,?)';
      array_push($qarr,
        $uid,
        $this->curAid,
        $stugroupid,
        ($uid==$this->curUid) ? $lti_sourcedid : '',
        $recordStart ? $this->now : 0,
        2,
        $this->assessRecord['status'],
        $scoredtosave,
        $practicetosave
      );
    }
    $query = 'INSERT INTO imas_assessment_records (userid, assessmentid,
      agroupid, lti_sourcedid, starttime, ver, status, scoreddata, practicedata)
      VALUES '.implode(',', $vals);
    $stm = $this->DBH->prepare($query);
    $stm->execute($qarr);

    $this->hasRecord = true;
  }

  /**
   * Build scoredData or practiceData from scratch
   * @param  boolean $recordStart True to record starttime now
   * @return void
   */
  public function buildAssessData($recordStart = true) {
    if ($this->data !== null && count($this->data) > 0) {
      return false;
    }

    $this->data = array(
      'submissions' => array(),
      'autosaves' => array(),
      'scored_version' => 0,
      'assess_versions' => array()
    );

    $this->buildNewAssessVersion($recordStart);
  }

  /**
   * Build a new assess_versions record
   * @param  boolean $recordStart True to record starttime now
   * @return void
   */
  public function buildNewAssessVersion($recordStart = true) {
    $this->parseData();
    $attempt = count($this->data['assess_versions']);
    // build base framework
    $out = array(
      'starttime' => $recordStart ? $this->now : 0,
      'lastchange' => 0,
      'status' => $recordStart ? 0 : -1,
      'score' => 0,
      'questions' => array()
    );

    if ($recordStart && $this->assess_info->getSetting('timelimit') > 0) {
      //recording start and has time limit, so record end time
      $out['timelimit_end'] = $this->now + $this->assess_info->getAdjustedTimelimit();
    }

    // generate the questions and seeds
    list($oldquestions, $oldseeds) = $this->getOldQuestions();
    list($questions, $seeds) = $this->assess_info->assignQuestionsAndSeeds($attempt);
    // build question data
    for ($k = 0; $k < count($questions); $k++) {
      $isWithdrawn = ($this->assess_info->getQuestionSetting($questions[$k], 'withdrawn') !== 0);
      $out['questions'][$k] = array(
        'score' => $isWithdrawn ? $this->assess_info->getQuestionSetting($questions[$k], 'points_possible') : 0,
        'rawscore' => $isWithdrawn ? 1 : 0,
        'scored_version' => 0,
        'question_versions' => array(
          array(
            'qid' => $questions[$k],
            'seed' => $seeds[$k],
            'tries' => array()
          )
        )
      );
      if ($isWithdrawn) {
        $out['questions'][$k]['withdrawn'] = 1;
      }
    }
    $this->data['assess_versions'][] = $out;

    $this->need_to_record = true;

    $this->setStatus($recordStart, false);
  }

  /**
   * Build a new question version.  Used in by-question submission to regen
   * @param  int     $qn          Question #
   * @param  int     $qid         Current Question ID
   * @param  int     $forceseed   (optional) Force a particular seed (-1 to not force)
   * @return int   New question ID
   */
  public function buildNewQuestionVersion($qn, $qid, $forceseed = -1) {
    list($oldquestions, $oldseeds) = $this->getOldQuestions();
    list($question, $seed) = $this->assess_info->regenQuestionAndSeed($qid, $oldseeds);
    // build question data
    $newver = array(
      'qid' => $question,
      'seed' => $forceseed > -1 ? $forceseed : $seed,
      'tries' => array()
    );

    $lastaver = count($this->data['assess_versions'])-1;
    $this->data['assess_versions'][$lastaver]['questions'][$qn]['question_versions'][] = $newver;

    // note that record needs to be saved
    $this->need_to_record = true;
    return $question;
  }

  /**
   * Get old questions and seeds previously used in assessment record
   * @param  int  $qn    (optional)  Question number to return seeds for. If not set, returns all
   * @return array array($questions, $seeds), where each is an array of values
   */
  public function getOldQuestions($qn = -1) {
    $questions = array();
    $seeds = array();
    $this->parseData();

    if ($this->data !== null) {
      foreach ($this->data['assess_versions'] as $ver) {
        foreach ($ver['questions'] as $thisqn=>$qdata) {
          foreach ($qdata['question_versions'] as $qver) {
            $questions[] = $qver['qid'];
            if ($qn === -1 || $qn === $thisqn) {
              $seeds[] = $qver['seed'];
            }
          }
        }
      }
    }
    return array($questions, $seeds);
  }

  /**
   * Returns whether an assessment record exists. Call after loadRecord
   * @return boolean true if an assessment record exists
   */
  public function hasRecord() {
    return ($this->hasRecord);
  }

  /**
   * Determine if there is an active assessment attempt
   * @return boolean true if there is an active assessment attempt
   */
  public function hasActiveAttempt() {
    if (empty($this->assessRecord)) {
      //no assessment record at all
      return false;
    }
    $aver = $this->getAssessVer();
    return ($aver['status'] === 0);
  }

  /**
   * Update the LTI sourcedid if needed
   * @param  string $sourcedid  The full IMathAS-format sourcedid
   * @return void
   */
  public function updateLTIsourcedId($sourcedid) {
    if (empty($this->assessRecord)) {
      //no assessment record at all
      return false;
    }
    if ($sourcedid != $this->assessRecord['lti_sourcedid']) {
      $this->assessRecord['lti_sourcedid'] = $sourcedid;
      $this->need_to_record = true;
    }
  }

  /**
   * For by-question submission, check if all questions have been attempted,
   * and update status if so
   * @return boolean true if all questions attempted
   */
  public function checkByQuestionStatus() {
    $aver = $this->getAssessVer();
    $allAttempted = true;
    for ($i=0; $i<count($aver['questions']); $i++) {
      if (count($aver['questions'][$i]['question_versions'])>1) {
        // if more than one version, then we've attempted it
        continue;
      } else if (count($aver['questions'][$i]['question_versions'][0]['tries']) > 0) {
        // have tried at least part of the question
        continue;
      } else {
        $allAttempted = false;
        break;
      }
    }
    if ($allAttempted) {
      $this->setStatus(false, false);
    }
    return $allAttempted;
  }

  /**
   * Sets overall status as active/not
   * @param boolean $active     Set true to mark as active
   * @param boolean $setattempt  True to set last attempt as submitted/unsubmitted (def: false)
   * @return void
   */
  public function setStatus($active, $setattempt = false) {
    if (empty($this->assessRecord)) {
      //no assessment record at all
      return false;
    }
    $submitby = $this->assess_info->getSetting('submitby');
    if ($this->is_practice) {
      if ($active) {
        $this->assessRecord['status'] |= 16;
      } else {
        $this->assessRecord['status'] = $this->assessRecord['status'] & ~16;
      }
      if ($setattempt) {
        $this->parseData();
        $lastver = count($this->data['assess_versions']) - 1;
        $this->data['assess_versions'][$lastver]['status'] = $active ? 0 : 1;
      }
    } else {
      // turn off both
      $this->assessRecord['status'] = $this->assessRecord['status'] & ~3;
      // status has bitwise 1: active by-assess attempt
      // status has bitwise 2: active by-question attempt
      if ($active) {
        if ($submitby == 'by_assessment') {
          $this->assessRecord['status'] |= 1;
        } else if ($submitby == 'by_question') {
          $this->assessRecord['status'] |= 2;
        }
      }
      if ($setattempt) {
        $this->parseData();
        $lastver = count($this->data['assess_versions']) - 1;
        if ($submitby == 'by_assessment') {
          // only mark as submitted if by_assessment
          $this->data['assess_versions'][$lastver]['status'] = $active ? 0 : 1;
        } else {
          // if by_question and not started, mark started
          if ($this->data['assess_versions'][$lastver]['status'] === -1 && $active) {
            $this->data['assess_versions'][$lastver]['status'] = 0;
          }
        }
        // record now as lastchange on attempt if no submissions have been made
        if ($this->data['assess_versions'][$lastver]['lastchange'] === 0) {
          $this->data['assess_versions'][$lastver]['lastchange'] = time();
        }
        if ($this->data['lastchange'] === 0) {
          $this->data['lastchange'] = time();
        }
      }
    }
    $this->need_to_record = true;
  }

  /**
   * Updates the lastchange for the current assessment version
   * @param int $time   Timestamp to set as last change
   */
  public function setLastChange($time) {
    $this->parseData();
    $lastver = count($this->data['assess_versions']) - 1;
    $this->data['assess_versions'][$lastver]['lastchange'] = $time;
    if (!$this->is_practice) {
      $this->assessRecord['lastchange'] = $time;
    }
  }

  /**
   * Save relevant POST to autosaves
   * @param int  $time          Timestamp
   * @param int  $qn            The question number
   * @param array $pn           The part number to save
   * @return void
   */
  public  function setAutoSave($time, $timeactive, $qn, $pn) {
    $this->parseData();
    $data = &$this->data['autosaves'];
    $seconds = $time - $this->assessRecord['starttime'];
    if (!isset($data[$qn])) {
      // if no autosave record, start one
      $data[$qn] = array(
        'time' => $seconds,
        'timeactive' => $timeactive,
        'post' => array(),
        'stuans' => array()
      );
    } else {
      // otherwise update time info.  Don't want to overwrite completely as we
      // may be adding additional part post/stuans data
      $data[$qn]['time'] = $seconds;
      $data[$qn]['timeactive'] = $timeactive;
    }
    $tosave = array();
    foreach ($_POST as $key=>$val) {
      if ($pn == 0) {
        if (preg_match('/^(qn|tc|qs)('.$qn.'\\b|'.(($qn+1)*1000 + $pn).'\\b)/', $key)) {
          $data[$qn]['post'][$key] = $val;
        }
      } else if (preg_match('/^(qn|tc|qs)'.(($qn+1)*1000 + $pn).'\\b/', $key)) {
        $data[$qn]['post'][$key] = $val;
      }
      if (isset($data[$qn]['post'][$key])) {
        $data[$qn]['stuans'][$pn] = $val; // TODO: fix this
      }
    }

    $this->need_to_record = true;
  }

  /**
   * Clears the autosave for a question
   * @param  int  $qn          Question number
   * @param  int  $pn          Part number.  If -1 clears whole question
   * @return void
   */
  private function clearAutoSave($qn, $pn) {
    $this->parseData();
    $data = &$this->data['autosaves'];
    if ($pn === -1) {
      unset($data[$qn]);
    } else if (isset(($data[$qn]['stuans']))) {
      if (count($data[$qn]['stuans']) === 1) {
        unset($data[$qn]);
      } else {
        unset($data[$qn]['stuans'][$pn]);
      }
    }
    $this->need_to_record = true;
  }

  /**
   * Score any autosave data.  Records it as a single submission with a date
   * based on the last autosave time
   *
   * @return void
   */
  public function scoreAutosaves() {
    $this->parseData();

    $autosaves = $this->data['autosaves'];
    if (count($autosaves) === 0) {
      return; // nothing to do
    }
    $maxtime = 0;
    foreach ($autosaves as $qn=>$qdata) {
      if ($qdata['time'] > $maxtime) {
        $maxtime = $qdata['time'];
      }
    }

    // Load the question code
    $qns = array_keys($autosaves);
    $qids = $this->getQuestionIds($qns);
    $this->assess_info->loadQuestionSettings($qids, true);

    // add a submission
    $submission = $this->addSubmission($maxtime + $this->assessRecord['starttime']);

    // score the questions
    foreach ($autosaves as $qn=>$qdata) {
      $parts_to_score = array();
      foreach (array_keys($qdata['stuans']) as $pn) {
        $parts_to_score[$pn] = true;
      }
      // TODO: This is hacky.  Fix it.
      foreach ($qdata['post'] as $key=>$val) {
        $_POST[$key] = $val;
      }

      $this->scoreQuestion(
          $qn,    // question number
          0,      // time active.  TODO: record this w autosave
          $submission,    // submission #
          $parts_to_score
      );
    }

    // clear out all autosaves
    $this->data['autosaves'] = array();

    // Recalculate scores
    $this->reTotalAssess($qns);

    $this->need_to_record = true;
  }

  /**
   * Determine if there is an unsubmitted assessment attempt
   * This includes not-yet-opened assessment attempts
   * @return boolean true if there is an unsubmitted assessment attempt
   */
  public function hasUnsubmittedAttempt() {
    if (empty($this->assessRecord)) {
      //no assessment record at all
      return false;
    }
    $this->parseData();

    if ($this->data === null) {
      return false;
    }
    if (count($this->data['assess_versions']) == 0) {
      return false;
    }
    $last_attempt = $this->data['assess_versions'][count($this->data['assess_versions'])-1];
    return ($last_attempt['status'] === 0);
  }

  /**
   * Determine if there is an unstarted assessment attempt
   * Typically only happens if first opened in review mode then later
   * in scored mode
   * @return boolean true if there is an unstarted assessment attempt
   */
  public function hasUnstartedAttempt() {
    if (empty($this->assessRecord)) {
      //no assessment record at all
      return false;
    }
    $this->parseData();

    if ($this->data === null) {
      return false;
    }
    if (count($this->data['assess_versions']) == 0) {
      return false;
    }
    $last_attempt = $this->data['assess_versions'][count($this->data['assess_versions'])-1];
    return ($last_attempt['status'] === -1);
  }

  /**
   * Determine if there is an unsubmitted assessment attempt
   * This includes not-yet-opened assessment attempts
   * @return boolean true if there is an unsubmitted assessment attempt
   */
  public function hasUnsubmittedScored() {
    if (empty($this->assessRecord)) {
      //no assessment record at all
      return false;
    }

    $waspractice = $this->is_practice;
    if ($waspractice) {
      $this->setInPractice(false);
    }

    $hasunsubmitted = $this->hasUnsubmittedAttempt();

    if ($waspractice) {
      $this->setInPractice(true);
    }
    return $hasunsubmitted;
  }

  /**
   * Check whether user is allowed to create a new assessment version
   * @return boolean              True if OK to create a new assess version
   */
  public function canMakeNewAttempt() {
    if ($this->is_practice) {
      // if in practice, can make new if we don't have one
      return ($this->data === null);
    } else {
      if ($this->data === null) {
        // if no data, can make new
        return true;
      }
      $this->parseData();
      $submitby = $this->assess_info->getSetting('submitby');
      $prev_attempt_cnt = count($this->data['assess_versions']);
      // if by-question, then we can if we have no versions yet
      if ($submitby == 'by_question' && $prev_attempt_cnt == 0) {
        return true;
      }
      if ($submitby == 'by_assessment') {
        $allowed = $this->assess_info->getSetting('allowed_attempts');
        if ($prev_attempt_cnt < $allowed) {
          return true;
        }
      }
    }
    return false;
  }


  /**
   * Get data on previous assessment attempts.
   * Call BEFORE overwriting settings when in practice mode.
   *
   * @param boolean $force_scores  Whether to force inclusion of scores. Default: false
   * @return array        An array of previous attempt info.  Each element is an
   *                      array containing key 'date', and 'score' if the
   *                      settings allow it
   */
  public function getSubmittedAttempts($force_scores = false) {
    if (empty($this->assessRecord)) {
      //no assessment record at all
      return array();
    }
    // if in practice, we want to grab scored previous attempts, so switch out
    $currently_practice = $this->is_practice;
    if ($currently_practice) {
      $this->setInPractice(false);
    }
    $this->parseData();

    $out = array();
    $is_available = ($this->assess_info->getSetting('available') === 'yes');
    $by_assessment = ($this->assess_info->getSetting('submitby') === 'by_assessment');
    $showscores = $this->assess_info->getSetting('showscores');

    foreach ($this->data['assess_versions'] as $k=>$ver) {
      // if it's a submitted version or an active one no longer available
      if ($ver['status'] === 1 || (!$is_available && $ver['status'] === 0)) {
        $out[$k] = array(
          'date' => $ver['lastchange'],
        );
        // show score if forced, or
        // if by_question and not available and showscores is allowed, or
        // if by_assessment and submitted and showscores is allowed
        if ($force_scores ||
          (!$by_assessment && !$is_available && $showscores === 'during') ||
          ($by_assessment && $ver['status'] == 1 && $showscores !== 'none')
        ) {
          $out[$k]['score'] = $ver['score'];
        }
      }
    }

    if ($currently_practice) {
      $this->setInPractice(true);
      $this->parseData();
    }

    return $out;
    //TODO:  Need to report teacher score override somehow
  }

  /**
   * Get the scored attempt version and score. Returns empty array if not allowed
   * Call BEFORE overwriting settings when in practice mode.
   *
   * @return array  'kept': version # or 'override' if instructor override
   *                        may not be set if using average
   *                'score': the final assessment score
   */
  public function getScoredAttempt() {
    if (empty($this->assessRecord)) {
      //no assessment record at all
      return array();
    }

    // if in practice, we want to grab scored previous attempts, so switch out
    $currently_practice = $this->is_practice;
    if ($currently_practice) {
      $this->setInPractice(false);
      $this->parseData();
    }

    $showscores = $this->assess_info->getSetting('showscores');
    if ($showscores === 'none') {
      return array();
    }

    $out = array('score' => $this->assessRecord['score']*1);
    if (isset($this->data['scoreoverride'])) {
      $out['kept'] = 'override';
    } else if (isset($this->data['scored_version'])) {
      $out['kept'] = $this->data['scored_version'];
    }

    if ($currently_practice) {
      $this->setInPractice(true);
      $this->parseData();
    }

    return $out;
  }

  /**
   * Get the score on an assessment version
   * @param  string $ver   Attempt #, or 'last'
   * @return float score on assessment
   */
  public function getAttemptScore($ver = 'last') {
    $this->parseData();
    if ($ver === 'last') {
      $ver = count($this->data['assess_versions']) - 1;
    }
    return $this->data['assess_versions'][$ver]['score'];
  }

  /**
   * Get group members. Only works if group record is loaded.
   *
   * @return array        An array of group member names
   */
  public function getGroupMembers() {
    if (empty($this->assessRecord)) {
      //no assessment record at all
      return array();
    }
    $names = AssessUtils::getGroupMembersByGroupId($this->assessRecord['agroupid']);

    return $names;
  }

  /**
   * Get the timestamp for when the last scored attempt time limit expires
   *
   * @return integer  timestamp for when  the current attempt time limit expires
   *                  will be 0 if there is no time limit
   */
  public function getTimeLimitExpires() {
    if (empty($this->assessRecord)) {
      return false;
    }

    $returnVal = null;
    // if in practice, we want to grab scored previous attempts, so switch out
    $currently_practice = $this->is_practice;
    if ($currently_practice) {
      $this->setInPractice(false);
      $this->parseData();
    }

    if (count($this->data['assess_versions']) == 0) {
      $returnVal = false;
    } else {
      //grab value from last (current) assess attempt
      $lastvernum = count($this->data['assess_versions']) - 1;
      $lastver = $this->data['assess_versions'][$lastvernum];
      $returnVal = $lastver['timelimit_end'];
    }
    if ($currently_practice) {
      $this->setInPractice(true);
      $this->parseData();
    }
    return $returnVal;
  }


  /**
   * Generate the question API object
   * @param  int  $qn            Question number (0 indexed)
   * @param  boolean $include_scores Whether to include scores (def: false)
   * @param  boolean $include_parts  True to include part scores and details, false for just total score (def false)
   * @param  boolean $generate_html Whether to generate question HTML (def: false)
   * @param int  $ver               Which version to grab data for, or 'last' for most recent
   * @return array  The question object
   */
  public function getQuestionObject($qn, $include_scores = false, $include_parts = false, $generate_html = false, $ver = 'last') {
    $by_question = ($this->assess_info->getSetting('submitby') == 'by_question');
    $due_date = $this->assess_info->getSetting('original_enddate');

    // get data structure for this question
    $aver = $this->getAssessVer($ver);
    $question_versions = $aver['questions'][$qn]['question_versions'];

    if (!$by_question || $ver === 'last') {
      $curq = $question_versions[count($question_versions) - 1];
    } else if ($ver === 'scored') {
      // get scored version when by_question
      $curq = $question_versions[$aver['questions'][$qn]['scored_version']];
    } else {
      $curq = $question_versions[$ver];
    }

    $tryToGet = ($ver === 'scored') ? 'all' : 'last';

    // get basic settings
    $out = $this->assess_info->getQuestionSettings($curq['qid']);

    // get regen number for by_question
    if ($by_question) {
      if (!is_numeric($ver)) {
        if ($ver === 'scored') {
          $regen = $aver['questions'][$qn]['scored_version'];
        } else {
          $regen = count($aver['questions'][$qn]['question_versions']) - 1;
        }
      } else {
        $regen = $ver;
      }
      $out['regen'] = $regen;
    } else {
      if (!is_numeric($ver)) {
        $regen = count($this->data['assess_versions']) - 1;
      } else {
        $regen = $ver;
      }
    }
    // get gbscore. For by_question this is the best of regens.
    // for by_assessment this will be the same as score
    if ($include_scores) {
      $out['gbscore'] = $aver['questions'][$qn]['score'];
      $out['gbrawscore'] = $aver['questions'][$qn]['rawscore'];
    }

    // set tries
    $parts = array();
    $score = -1;
    $try = 0;
    $status = 'unattempted';
    if (count($curq['tries']) == 0) {
      // no tries yet
      $parts[0] = array('try' => 0);
      if ($include_scores) {
        $parts[0]['score'] = 0;
        $parts[0]['rawscore'] = 0;
      }
    } else {
      // treat everything like multipart
      $try = 1e10;
      $score = 0;
      $raw = 0;
      $status = 'attempted';
      if ($include_scores) {
        // get scores. Get last try unless doing 'scored'
        list($score, $raw, $parts) = $this->getQuestionPartScores($qn, $ver, $tryToGet);
      }
      $answeights = isset($curq['answeights']) ? $curq['answeights'] : array(1);

      $answeightTot = array_sum($answeights);

      for ($pn = 0; $pn < count($answeights); $pn++) {
        // get part details
        $parttry = isset($curq['tries'][$pn]) ? count($curq['tries'][$pn]) : 0;
        $try = min($try, $parttry);
        if ($parttry === 0) {
          // if any parts are unattempted, mark question as such
          $status = 'unattempted';
        }
        if ($include_scores) {
          if ($status != 'unattempted') {
            if ($parts[$pn]['rawscore'] > .99) {
              $status = ($status === 'incorrect' || $status === 'partial') ? 'partial': 'correct';
            } else if ($parts[$pn]['rawscore'] < .01) {
              $status = ($status === 'correct' || $status === 'partial') ? 'partial': 'incorrect';
            } else {
              $status = 'partial';
            }
          }
        } else if ($include_parts) {
          $parts[$pn] = array(
            'try' => $parttry,
            'points_possible' => $out['points_possible'] * $answeights[$pn]/$answeightTot
          );
        }
      }
    }
    $out['try'] = $try;
    if ($include_parts) {
      $out['parts'] = $parts;
    }
    $out['status'] = $status;
    if ($out['withdrawn'] !== 0) {
      $out['status'] = 'attempted';
    }
    if ($include_scores) {
      $out['score'] = ($score != -1) ? $score : 0;
      // TODO:  Do we want to return score saved in gb too?
    }
    // if jumped to answer, burn tries
    if (!empty($curq['jumptoans'])) {
      $out['try'] = $out['tries_max'];
      $out['status'] = 'attempted';
      $out['did_jump_to_ans'] = true;
    }

    $out['seed'] = $curq['seed'];

    if ($generate_html) {
      $showscores = $this->assess_info->getSetting('showscores');
      $force_scores = ($aver['status'] === 1 && $showscores === 'at_end');
      $showans = $this->assess_info->getSetting('showans');
      $force_answers = ($aver['status'] === 1 && $showans === 'after_attempt');

      list($out['html'], $out['jsparams'], $out['answeights'], $out['usedautosave']) =
        $this->getQuestionHtml($qn, $ver, false, $force_scores, $force_answers);
      if ($out['usedautosave']) {
        $autosave = $this->getAutoSaves($qn);
        $out['autosave_timeactive'] = $autosave['timeactive'];
      }
      $this->setAnsweights($qn, $out['answeights'], $ver);
    } else {
      $out['html'] = null;
    }

    return $out;
  }

  /**
   * Get the rawscores and last student answers for the latest version and try
   * @param  int  $qn
   * @return array with keys 'raw' and 'stuans', each an array of scores and
   *                student answers for each part.
   */
  public function getLastRawResult($qn) {
    $by_question = ($this->assess_info->getSetting('submitby') == 'by_question');
    $aver = $this->getAssessVer($ver);
    $question_versions = $aver['questions'][$qn]['question_versions'];
    $curq = $question_versions[count($question_versions) - 1];
    $rawscores = array();
    $lastans = array();
    if (count($curq['tries']) == 0) {
      $rawscores[0] = 0;
      $lastans[0] = '';
    } else {
      $answeights = isset($curq['answeights']) ? $curq['answeights'] : array(1);
      for ($pn = 0; $pn < count($answeights); $pn++) {
        // get part details
        if (isset($curq['tries'][$pn])) {
          $lasttry = $curq['tries'][$pn][count($curq['tries'][$pn]) - 1];
          $rawscores[$pn] = $lasttry['raw'];
          $lastans[$pn] = isset($lasttry['unrand']) ? $lasttry['unrand'] : $lasttry['stuans'];
        } else {
          $rawscores[$pn] = 0;
          $lastans[$pn] = '';
        }
      }
    }
    return array(
      'raw' => $rawscores,
      'stuans' => $lastans
    );
  }

  /**
   * Sets the answeights for a question if needed
   * @param int  $qn          Question number
   * @param array $answeights   Answeights array
   * @param string  $ver         attempt number, or 'last'
   */
  private function setAnsweights($qn, $answeights, $ver = 'last') {
    $by_question = ($this->assess_info->getSetting('submitby') == 'by_question');
    $this->parseData();
    if ($this->is_practice) {
      $assessver = &$this->data['assess_versions'][0];
    } else if ($by_question || !is_numeric($ver)) {
      $assessver = &$this->data['assess_versions'][count($this->data['assess_versions']) - 1];
    } else {
      $assessver = &$this->data['assess_versions'][$ver];
    }

    $question_versions = &$assessver['questions'][$qn]['question_versions'];
    if (!$by_question || !is_numeric($ver)) {
      $curq = &$question_versions[count($question_versions) - 1];
    } else {
      $curq = &$question_versions[$ver];
    }
    if ($answeights !== $curq['answeights']) {
      $curq['answeights'] = $answeights;
      $this->need_to_record = true;
    }
  }

  /**
   * Generate the question API object for all questions
   * @param  boolean $include_scores Whether to include scores (def: false)
   * @param  boolean $include_parts  True to include part scores and details, false for just total score (def false)
   * @param  boolean $generate_html Whether to generate question HTML (def: false)
   * @param int  $ver               Which version to grab data for, or 'last' for most recent
   * @return array  The question object
   */
  public function getAllQuestionObjects($include_scores = false, $include_parts = false, $generate_html = false, $ver = 'last') {
    $out = array();
    // get data structure for current version
    $assessver = $this->getAssessVer($ver);
    for ($qn = 0; $qn < count($assessver['questions']); $qn++) {
      $out[$qn] = $this->getQuestionObject($qn, $include_scores, $include_parts, $generate_html, $ver);
    }
    return $out;
  }

  /**
   * get question part score details
   * @param  int  $qn             The question number
   * @param  string  $ver         The attempt to use, or 'last' for most recent
   * @param  string  $try         'last' for last try, or 'all' to pick best try per-part
   * @param  array   $overrides   By-part score overrides
   * @return array (question Score, question Rawscore, parts details)
   *    Where part details is array by part, with (try, score, rawscore, penalties, points_possible)
   */
  public function getQuestionPartScores ($qn, $ver = 'last', $try = 'last', $overrides = array()) {
    $by_question = ($this->assess_info->getSetting('submitby') == 'by_question');
    $due_date = $this->assess_info->getSetting('original_enddate');
    $starttime = $this->assessRecord['starttime'];

    $submissions = $this->data['submissions'];
    if ($this->is_practice) {
      $assessver = $this->data['assess_versions'][0];
    } else if ($by_question || !is_numeric($ver)) {
      $assessver = $this->data['assess_versions'][count($this->data['assess_versions']) - 1];
      if (!$by_question) {
        $regen = count($this->data['assess_versions']) - 1;
      }
    } else {
      $assessver = $this->data['assess_versions'][$ver];
      $regen = $ver;
    }

    if (!$by_question) {
      $retakepenalty = $this->assess_info->getSetting('retake_penalty');
    }

    // get data structure for this question
    $question_versions = $assessver['questions'][$qn]['question_versions'];
    if (!$by_question || $ver === 'last') {
      $qver = $question_versions[count($question_versions) - 1];
      if ($by_question) {
        $regen = count($question_versions) - 1;
      }
    } else if ($ver === 'scored') {
      // get scored version when by_question
      $qver = $question_versions[$assessver['questions'][$qn]['scored_version']];
    } else {
      $qver = $question_versions[$ver];
      $regen = $ver;
    }

    $qsettings = $this->assess_info->getQuestionSettings($qver['qid']);
    $exceptionPenalty = $this->assess_info->getSetting('exceptionpenalty');

    $answeights = isset($qver['answeights']) ? $qver['answeights'] : array(1);
    $answeightTot = array_sum($answeights);
    $partscores = array_fill(0, count($answeights), 0);
    $partrawscores = array_fill(0, count($answeights), 0);
    $parts = array();
    $is_singlescore = !empty($qver['singlescore']);
    // loop over each part
    for ($pn = 0; $pn < count($answeights); $pn++) {
      $partpenalty = array();
      $max = isset($qver['tries'][$pn]) ? count($qver['tries'][$pn]) - 1 : -1;
      if ($max == -1) {
        // no tries yet
        $parts[$pn] = array(
          'try' => 0,
          'score' => 0,
          'points_possible' => $qsettings['points_possible'] * $answeights[$pn]/$answeightTot
        );
        continue;
      }
      if ($try === 'last') {
        $min = $max;
      } else {
        $min = 0;
      }
      $partReqManual = false;
      for ($pa = $min; $pa <= $max; $pa++) {
        $parttry = $qver['tries'][$pn][$pa];
        if ($parttry['raw'] > 0) {
          list($scoreAfterPenalty,$penaltyList) = $this->scoreAfterPenalty(
            $parttry['raw'],   // score
            $qsettings['points_possible'] * $answeights[$pn]/$answeightTot,
                                // points possible
            $pa,                 // the try number
            $qsettings['retry_penalty'],  //retry penalty
            $qsettings['retry_penalty_after'], //retry penalty after
            $regen,             // the regen number
            $by_question ? $qsettings['regen_penalty'] : $retakepenalty['penalty'],
            $by_question ? $qsettings['regen_penalty_after'] : $retakepenalty['n'],
            $due_date,           // the due date
            $starttime + $submissions[$parttry['sub']], // submission time
            $exceptionPenalty,
            true
          );
          if ($scoreAfterPenalty > $partscores[$pn]) {
            $partscores[$pn] = $scoreAfterPenalty;
            $partrawscores[$pn] = $parttry['raw']*1;
            $partpenalty = $penaltyList;
          }
        } else if ($partscores[$pn]==0 && $parttry['raw']==-2) {
          // -2 indicates the item is a manual grade item
          $partReqManual = true;
        }
      }
      // apply by-part overrides, if set
      if (isset($overrides[$pn])) {
        $partrawscores[$pn] = $overrides[$pn];
        $partscores[$pn] = $overrides[$pn] * $qsettings['points_possible'] * $answeights[$pn]/$answeightTot;
      }
      if ($is_singlescore) {
        $parts[$pn] = array(
          'try' => count($qver['tries'][$pn]),
          'rawscore' => $partrawscores[$pn]
        );
        if ($pn==0) {
          $parts[$pn]['penalties'] = $partpenalty;
        }
      } else {
        $parts[$pn] = array(
          'try' => count($qver['tries'][$pn]),
          'score' => $partscores[$pn],
          'rawscore' => $partrawscores[$pn],
          'penalties' => $partpenalty,
          'req_manual' => $partReqManual,
          'points_possible' => $qsettings['points_possible'] * $answeights[$pn]/$answeightTot
        );
      }
    }
    $qScore = array_sum($partscores);
    $qRawscore = 0;
    for ($pn = 0; $pn < count($answeights); $pn++) {
      $qRawscore += $partrawscores[$pn]*$answeights[$pn];
    }
    return array($qScore, $qRawscore, $parts);
  }

  /**
   * generate the question HTML
   * @param  int  $qn               Question #
   * @param  string  $ver           Version to use, 'last' for current (def: 'last')
   * @param  boolean $clearans      true to clear answer (def: false)
   * @param  boolean $force_scores  force display of scores (def: false)
   * @param  boolean $force_answers force display of answers (def: false)
   * @return array (questionhtml, answeights)
   */
  public function getQuestionHtml($qn, $ver = 'last', $clearans = false, $force_scores = false, $force_answers = false) {
    // get assessment attempt data for given version
    $qver = $this->getQuestionVer($qn, $ver);

    // get the question settings
    $qsettings = $this->assess_info->getQuestionSettings($qver['qid']);
    $showscores = ($force_scores || ($this->assess_info->getSetting('showscores') === 'during'));
    // see if there is autosaved answers to redisplay
    $autosave = $this->getAutoSaves($qn);

    $numParts = isset($qver['answeights']) ? count($qver['answeights']) : count($qver['tries']);

    $partattemptn = array();
    $qcolors = array();
    $lastans = array();
    $showansparts = array();
    $showans = ($numParts > 0); //true by default, unless no answeights or tries yet
    $trylimit = $qsettings['tries_max'];
    $usedAutosave = array();

    list($stuanswers, $stuanswersval) = $this->getStuanswers($ver);
    list($scorenonzero, $scoreiscorrect) = $this->getScoreIsCorrect();

    for ($pn = 0; $pn < $numParts; $pn++) {
      // figure out try #
      $partattemptn[$pn] = isset($qver['tries'][$pn]) ? count($qver['tries'][$pn]) : 0;

      // TODO: Rework this to use stuanswers?  Or dont even need if we Rework
      // displayq to use stuanswers
      if ($clearans) {
        $lastans[$pn] = '';
      } else if (isset($autosave['stuans'][$pn])) {
        $lastans[$pn] = $autosave['stuans'][$pn];
        $usedAutosave[] = $pn;
      } else if ($partattemptn[$pn] > 0) {
        $lastans[$pn] = $qver['tries'][$pn][$partattemptn[$pn] - 1]['stuans'];
      } else {
        $lastans[$pn] = '';
      }

      // figure out if we should show answers
      if ($force_answers) {
        $showansparts[$pn] = true;
      } else if ($qsettings['showans'] === 'after_lastattempt' && $partattemptn[$pn] === $trylimit) {
        $showansparts[$pn] = true;  // show after last attempt
      } else if (!empty($qsettings['jump_to_answer']) && !empty($qver['jumptoans'])) {
        $showansparts[$pn] = true;  // show after jump to answer pressed
      } else if ($qsettings['showans'] === 'with_score' && $showscores && $partattemptn[$pn] > 0) {
        $showansparts[$pn] = true; // show with score
      } else if ($qsettings['showans'] === 'after_n' && $partattemptn[$pn] > $qsettings['showans_aftern']) {
        $showansparts[$pn] = true; // show after n attempts
      } else {
        $showansparts[$pn] = false;
        $showans = false;
      }
      if ($showscores && $partattemptn[$pn] > 0) {
        $qcolors[$pn] = $qver['tries'][$pn][$partattemptn[$pn] - 1]['raw'];
      }
    }
    $attemptn = (count($partattemptn) == 0) ? 0 : min($partattemptn);

    /*
    // TODO: move this to displayq input
    // TODO: pass stuanswers, stuanswersval
    $GLOBALS['qdatafordisplayq'] = $this->assess_info->getQuestionSetData($qsettings['questionsetid']);
    // TODO:  pass as input
    $GLOBALS['lastanswers'] = array($qn => implode('&', $lastans));
    $GLOBALS['lastansweights'] = array(1);

    list($qout,$jsparams) = displayq(
        $qn,                            // question number
        $qsettings['questionsetid'],    // questionset ID
        $qver['seed'],                  // seed
        $showans,                       // whether to show answers
        $qsettings['showhints'],        // whether to show hints
        $attemptn,                      // the attempt number //TODO: make by-part
        $partattemptn,                  // the attempt number by-part
        true,                          // return question text rather than echo
        $clearans,                      // whether to clear last ans //TODO: move here
        $qcolors,                        // array of part scores for score marking
        $stuanswers,
        $stuanswersval
    );
    */
    //TODO!!

    $questionParams = new QuestionParams();
    $questionParams
        ->setDbQuestionSetId($qsettings['questionsetid'])
        ->setQuestionData($this->assess_info->getQuestionSetData($qsettings['questionsetid']))
        ->setQuestionNumber($qn)
        ->setQuestionId($qver['qid'])
        ->setAssessmentId($this->assess_info->getSetting('id'))
        ->setQuestionSeed($qver['seed'])
        ->setShowHints($this->assess_info->getQuestionSetting($qver['qid'], 'showhints'))
        ->setShowAnswer($showans)
        ->setShowAnswerButton(true)
        ->setStudentAttemptNumber($attemptn)
        ->setStudentPartAttemptCount($partattemptn)
        ->setAllQuestionAnswers($stuanswers)
        ->setAllQuestionAnswersAsNum($stuanswersval)
        ->setScoreNonZero($scorenonzero)
        ->setScoreIsCorrect($scoreiscorrect);
    $questionGenerator = new QuestionGenerator($this->DBH,
        $GLOBALS['RND'], $questionParams);
    $question = $questionGenerator->getQuestion();

    $qout = $question->getQuestionContent();
    $jsparams = $question->getJsParams();
    $jsparams['helps'] = $question->getExternalReferences();
    $answeights = $question->getAnswerPartWeights();

    return array($qout, $jsparams, $answeights, $usedAutosave);
  }

  /**
   * Add a new submission record
   * @param int $time   The current timestamp
   * @return int  submission number, to record with try data
   */
  public function addSubmission($time) {
    $seconds = $time - $this->assessRecord['starttime'];

    $this->parsedata();
    $this->data['submissions'][] = $seconds;
    return count($this->data['submissions']) - 1;
  }

  /**
   * Score a question
   * @param  int  $qn             Question number
   * @param  int  $timeactive     Time the question was active, in ms
   * @param  int  $submission     The submission number, from addSubmission
   * @param  array $parts_to_score  an array, true if part is to be scored/recorded
   * @return void
   */
  public function scoreQuestion($qn, $timeactive, $submission, $parts_to_score=true) {
    $qver = $this->getQuestionVer($qn);
    $answeights = $qver['answeights'];

    // get the question settings
    $qsettings = $this->assess_info->getQuestionSettings($qver['qid']);

    $partattemptn = array();
    for ($pn = 0; $pn < count($answeights); $pn++) {
      // figure out try #
      $partattemptn[$pn] = isset($qver['tries'][$pn]) ? count($qver['tries'][$pn]) : 0;
    }
    $attemptn = (count($partattemptn) == 0) ? 0 : min($partattemptn);

    $data = array();
    /*
    // TODO: move this to displayq input
    // TODO: pass stuanswers, stuanswersval

    $GLOBALS['qdatafordisplayq'] = $this->assess_info->getQuestionSetData($qsettings['questionsetid']);
    list($scores, $rawscores) = scoreq(
      $qn,                            // question number
      $qsettings['questionsetid'],    // questionset ID
      $qver['seed'],                  // seed
      $_POST['qn'.$qn],               // the default answerbox
      $attemptn,                      // the attempt number //TODO: make by-part
      $qsettings['points_possible']   // points possible for the question
    );

    // TODO need better way to get student's answer and unrand and such
    // TODO: rework this to handle singlescore questions
    $rawparts = explode('~', $rawscores);
    $scores = explode('~', $scores);
    $partla = explode('&', $GLOBALS['lastanswers'][$qn]);
    */

    list($stuanswers, $stuanswersval) = $this->getStuanswers($ver);

    $scoreEngine = new ScoreEngine($this->DBH, $GLOBALS['RND']);

    $scoreQuestionParams = new ScoreQuestionParams();
    $scoreQuestionParams
        ->setUserRights($GLOBALS['myrights'])
        ->setRandWrapper($GLOBALS['RND'])
        ->setQuestionNumber($qn)
        ->setQuestionData($this->assess_info->getQuestionSetData($qsettings['questionsetid']))
        ->setAssessmentId($this->assess_info->getSetting('id'))
        ->setDbQuestionSetId($qsettings['questionsetid'])
        ->setQuestionSeed($qver['seed'])
        ->setGivenAnswer($_POST['qn'.$qn])
        ->setAttemptNumber($attemptn)
        ->setAllQuestionAnswers($stuanswers)
        ->setAllQuestionAnswersAsNum($stuanswersval)
        ->setQnpointval($qsettings['points_possible']);

    $scoreResult = $scoreEngine->scoreQuestion($scoreQuestionParams);
    $scores = $scoreResult['scores'];
    $rawparts = $scoreResult['rawScores'];
    $partla = $scoreResult['lastAnswerAsGiven'];
    $partlaNum = $scoreResult['lastAnswerAsNumber'];

    if (count($rawparts)===1 && count($partla) > 1) {
      // force recording of all parts for conditional
      $parts_to_score = true;
    }

    //foreach ($rawparts as $k=>$v) {
    foreach ($partla as $k=>$v) {
      if ($parts_to_score === true || !empty($parts_to_score[$k])) {
        $data[$k] = array(
          'sub' => $submission,
          'time' => round($timeactive/1000),
          'stuans' => $v   // TODO: this is wrong for most types
        );
        if (isset($partlaNum[$k])) {
          $data[$k]['stuansval'] = $partlaNum[$k];
        }
        if (isset($rawparts[$k])) {
          $data[$k]['raw'] = $rawparts[$k];
        }
        $this->clearAutoSave($qn, $k);
      }
    }

    $singlescore = (count($partla) > 1 && count($scores) == 1);
    $this->recordTry($qn, $data, $singlescore);

  }

  /**
   * Generate $stuanswers and $stuanswersval for the last tries
   * @param  string  $ver         Version to grab from, or 'last' for latest
   * @return array  ($stuanswers, $stuanswersval)
   */
  public function getStuanswers($ver = 'last') {
    $by_question = ($this->assess_info->getSetting('submitby') == 'by_question');
    // get data structure for this question
    $assessver = $this->getAssessVer($ver);
    $stuanswers = array();
    $stuanswerval = array();
    for ($qn = 0; $qn < count($assessver['questions']); $qn++) {
      $bcnt = 0;
      $question_versions = $assessver['questions'][$qn]['question_versions'];
      if (!$by_question || !is_numeric($ver)) {
        $curq = $question_versions[count($question_versions) - 1];
      } else {
        $curq = $question_versions[$ver];
      }
      $stuansparts = array();
      $stuansvalparts = array();
      for ($pn = 0; $pn < count($curq['tries']); $pn++) {
        $lasttry = $curq['tries'][$pn][count($curq['tries'][$pn]) - 1];
        $stuansparts[$pn] = isset($lasttry['unrand']) ? $lasttry['unrand'] : $lasttry['stuans'];
        $stuansvalparts[$pn] = isset($lasttry['stuansval']) ? $lasttry['stuansval'] : null;
      }
      // stuanswers array is 1-indexed
      if (count($stuansparts) > 1) {
        $stuanswers[$qn+1] = $stuansparts;
        $stuanswersval[$qn+1] = $stuansvalparts;
      } else {
        $stuanswers[$qn+1] = $stuansparts[0];
        $stuanswersval[$qn+1] = $stuansvalparts[0];
      }

    }
    return array($stuanswers, $stuanswerval);
  }

  /**
   * Generate $scoreiscorrect and $scorenonzero for the last tries
   * @param  string  $ver         Version to grab from, or 'last' for latest
   * @return array  ($scorenonzero, $scoreiscorrect)
   */
  public function getScoreIsCorrect($ver = 'last') {
    $by_question = ($this->assess_info->getSetting('submitby') == 'by_question');
    // get data structure for this question
    $assessver = $this->getAssessVer($ver);
    $scorenonzero = array();
    $scoreiscorrect = array();
    for ($qn = 0; $qn < count($assessver['questions']); $qn++) {
      $bcnt = 0;
      $question_versions = $assessver['questions'][$qn]['question_versions'];
      if (!$by_question || !is_numeric($ver)) {
        $curq = $question_versions[count($question_versions) - 1];
      } else {
        $curq = $question_versions[$ver];
      }
      if (count($curq['tries']) == 0) {
        $scorenonzero[$qn] = 0;
        $scoreiscorrect[$qn] = 0;
        continue;
      }
      $scorenonzeroparts = array();
      $scoreiscorrectparts = array();
      for ($pn = 0; $pn < count($curq['tries']); $pn++) {
        $lasttry = $curq['tries'][$pn][count($curq['tries'][$pn]) - 1];
        $scorenonzeroparts[$pn] = $lasttry['raw'] > 0;
        $scoreiscorrectparts[$pn] = $lasttry['raw'] > .99;
      }
      if (count($stuansparts) > 1) {
        $scorenonzero[$qn] = $scorenonzeroparts;
        $scoreiscorrect[$qn] = $scoreiscorrectparts;
      } else {
        $scorenonzero[$qn] = $scorenonzeroparts[0];
        $scoreiscorrect[$qn] = $scoreiscorrectparts[0];
      }

    }
    return array($scorenonzero, $scoreiscorrect);
  }


  /**
   * Gets the question ID for the given question number
   * @param  int  $qn             Question Number
   * @param  string  $ver         version #, or 'last'
   * @return int  question ID
   */
  public function getQuestionId($qn, $ver = 'last') {
    $curq = $this->getQuestionVer($qn, $ver);
    return $curq['qid'];
  }

  /**
   * Gets the question IDs for the given question numbers
   * @param  array  $qns           Array of Question Numbers
   * @param  string  $ver         version #, or 'last'
   * @return array  question IDs, indexed by question number
   */
  public function getQuestionIds($qns, $ver = 'last') {
    $by_question = ($this->assess_info->getSetting('submitby') == 'by_question');
    $assessver = $this->getAssessVer($ver);
    $out = array();
    foreach ($qns as $qn) {
      $question_versions = $assessver['questions'][$qn]['question_versions'];
      if (!$by_question || !is_numeric($ver)) {
        $curq = $question_versions[count($question_versions) - 1];
      } else {
        $curq = $question_versions[$ver];
      }
      $out[$qn] = $curq['qid'];
    }
    return $out;
  }

  /**
   * Recalculate the assessment total score, updating the record
   * @param  mixed  $rescoreQs   'all' to rescore all, or array of question numbers to re-score
   * @return float   The final assessment total
   */
  public function reTotalAssess($rescoreQs = 'all') {
    $by_question = ($this->assess_info->getSetting('submitby') == 'by_question');

    $points = $this->assess_info->getAllQuestionPoints();
    $this->parseData();

    $maxAscore = 0;
    $aScoredVer = 0;
    $totalTime = 0;
    // loop through all the assessment versions
    for ($av = 0; $av < count($this->data['assess_versions']); $av++) {
      $curAver = &$this->data['assess_versions'][$av];

      // loop through the question numbers
      $aVerScore = 0;
      for ($qn = 0; $qn < count($curAver['questions']); $qn++) {
        // if not rescoring this question, or if withdrawn, use existing score
        if (($rescoreQs !== 'all' && !in_array($qn, $rescoreQs)) ||
            !empty($curAver['questions'][$qn]['withdrawn'])
        ) {
          $aVerScore += $curAver['questions'][$qn]['score'];
          $totalTime += $curAver['questions'][$qn]['time'];
          continue;
        }
        // loop through the question versions
        $maxQscore = 0;
        $maxQrawscore = 0;
        $qScoredVer = 0;
        $totalQtime = 0;
        for ($qv = 0; $qv < count($curAver['questions'][$qn]['question_versions']); $qv++) {
          $curQver = &$curAver['questions'][$qn]['question_versions'][$qv];
          // scoreoverride can be a single value override, or array per-part
          // should be RAW (0-1) override.
          if (isset($curQver['scoreoverride']) && !is_array($curQver['scoreoverride'])) {
            $qScore = $curQver['scoreoverride'] * $points[$curQver['qid']];
            $qRawscore = $curQver['scoreoverride'];
          } else if (isset($curQver['scoreoverride']) && is_array($curQver['scoreoverride'])) {
            list($qScore, $qRawscore, $parts) = $this->getQuestionPartScores($qn, max($av,$qv), 'all', $curQver['scoreoverride']);
          } else {
            list($qScore, $qRawscore, $parts) = $this->getQuestionPartScores($qn, max($av,$qv), 'all');
          }

          $totalQtime += $this->calcTimeActive($curQver)['total'];
          if ($qScore >= $maxQscore) {
            $maxQscore = $qScore;
            $maxQrawscore = $qRawscore;
            $qScoredVer = $qv;
          }

        } // end loop over question versions
        if ($by_question) {
          $curAver['questions'][$qn]['scored_version'] = $qScoredVer;
        }
        $curAver['questions'][$qn]['score'] = $maxQscore;
        $curAver['questions'][$qn]['rawscore'] = $maxQrawscore;
        $curAver['questions'][$qn]['time'] = $totalQtime;
        $aVerScore += $maxQscore;
        $totalTime += $totalQtime;
      } // end loop over questions
      $curAver['score'] = $aVerScore;
      if ($aVerScore >= $maxAscore) {
        $maxAscore = $aVerScore;
        $aScoredVer = $av;
      }
    } // end loop over assessment versions
    if (!$by_question) {
      $this->data['scored_version'] = $aScoredVer;
    }
    if (!$this->is_practice) {
      $this->assessRecord['score'] = $maxAscore;
      $this->assessRecord['timeontask'] = $totalTime;
    }
    return $maxAscore;
  }

  /**
   * Find out if a submission is allowed per-part
   * @param  int  $qn             Question #
   * @param  int  $qid            Question ID
   * @return array indexed by part number; true if submission allowed
   */
  public function isSubmissionAllowed($qn, $qid) {
    $this->parseData();

    $by_question = ($this->assess_info->getSetting('submitby') === 'by_question');
    if ($by_question) {
      $qvers = $this->data['assess_versions'][0]['questions'][$qn]['question_versions'];
      $answeights = $qvers[count($qvers) - 1]['answeights'];
      $tries = $qvers[count($qvers) - 1]['tries'];
      if (!empty($qvers[count($qvers)-1]['jumptoans'])) {
        // jump to answer has been clicked - submission not allowed
        return array_fill(0, count($answeights), false);
      }
    } else {
      $aver = $this->data['assess_versions'][count($this->data['assess_versions']) - 1];
      $answeights = $aver['questions'][$qn]['question_versions'][0]['answeights'];
      $tries = $aver['questions'][$qn]['question_versions'][0]['tries'];
    }
    $tries_max = $this->assess_info->getQuestionSetting($qid, 'tries_max');
    $out = array();
    for ($pn = 0; $pn < count($answeights); $pn++) {
      if (!isset($tries[$pn])) {
        $out[$pn] = true;
      } else {
        $out[$pn] = (count($tries[$pn]) < $tries_max);
      }
    }

    return $out;
  }

  /**
   * Checks the tries and regen info from a submission to make sure it's for
   * the current submission
   * @param  array  $verification      Array provided by front end, with
   *                                Question # as keys, given an object with
   *                                keys 'regen' and 'tries', with the latter
   *                                an array per-part.
   */
  public function checkVerification($verification) {
    $this->parseData();

    $by_question = ($this->assess_info->getSetting('submitby') === 'by_question');
    if (!$by_question) {
      $aver = $this->data['assess_versions'][count($this->data['assess_versions']) - 1];
      $regen = count($this->data['assess_versions']) - 1;
    }
    foreach ($verification as $qn=>$qdata) {
      if ($by_question) {
        $qvers = $this->data['assess_versions'][0]['questions'][$qn]['question_versions'];
        $tries = $qvers[count($qvers) - 1]['tries'];
        $regen = count($qvers) - 1;
      } else {
        $tries = $aver['questions'][$qn]['question_versions'][0]['tries'];
      }
      if ($regen !== $qdata['regen']) {
        //echo "regen failed: $regen vs ".$qdata['regen'].". ";
        return false;
      }
      for ($i = 0; $i < count($qdata['tries']); $i++) {
        if (isset($tries[$i]) && $qdata['tries'][$i] !== count($tries[$i])) {
          //echo "tries failed $i: ".$qdata['tries'][$i]." vs ".count($tries[$i]).". ";
          return false;
        }
      }
    }
    return true;
  }

  /**
   * Find out if question can be regenerated
   * @param  int  $qn             Question #
   * @param  int  $qid            Question ID
   * @return boolean true if question can be regenerated
   */
  public function canRegenQuestion($qn, $qid) {
    $by_question = ($this->assess_info->getSetting('submitby') == 'by_question');
    if (!$by_question) {
      return false;
    }
    $this->parseData();

    $regens_max = $this->assess_info->getQuestionSetting($qid, 'regens_max');
    $regens_used = count($this->data['assess_versions'][0]['questions'][$qn]['question_versions']);
    return ($regens_used < $regens_max);
  }

  /**
   * Mark a question as "jump to answer" clicked
   * @param  int $qn      Question #
   * @param  int $qid     Question ID
   * @return void
   */
  public function doJumpToAnswer($qn, $qid) {
    // only can do jump to answer for by_question submission
    $by_question = ($this->assess_info->getSetting('submitby') == 'by_question');
    if (!$by_question) {
      return false;
    }
    // make sure question settings are correct
    $allowjump = $this->assess_info->getQuestionSetting($qid, 'jump_to_answer');
    if (!$allowjump) {
      return false;
    }
    // get last question version
    $qvers = &$this->data['assess_versions'][0]['questions'][$qn]['question_versions'];
    $curQver = &$qvers[count($qvers)-1];
    $curQver['jumptoans'] = true;
    $this->need_to_record = true;
  }

  /**
   * Withdraw questions, by setting score override to the specified values
   * @param  array $qpts  Assoc array of imas_questions.id => points to assign
   * @return float  Updated assessment final score
   */
  public function withdrawQuestions($qpts) {
    $this->parseData();
    $madeChanges = false;
    $avers = &$this->data['assess_versions'];
    for ($av = 0; $av < count($avers); $av++) {
      for ($q = 0; $q < count($avers[$av]['questions']); $q++) {
        $qdata = &$avers[$av]['questions'][$q];
        for ($qv = 0; $qv < count($qdata['question_versions']); $qv++) {
          if (isset($qpts[$qdata['question_versions'][$qv]['qid']])) {
            $qdata['withdrawn'] = 1;
            $madeChanges = true;
            // expects raw override, so set to 0 or 1.
            $pts = $qpts[$qdata['question_versions'][$qv]['qid']];
            $qdata['question_versions'][$qv]['scoreoverride'] = ($pts>0)?1:0;
            $qdata['rawscore'] = ($pts>0)?1:0;
            $qdata['score'] = $pts;
          }
        }
      }
    }
    if ($madeChanges) {
      $this->need_to_record = true;
    }
    return $this->reTotalAssess();
  }

  /**
   * Get assessment metadata for gradebook view
   * @return array
   */
  public function getGbAssessMeta() {
    $this->parseData();
    // Will also want to add in student's name
    // TODO: get latepass status

    $out['starttime'] = $this->assessRecord['starttime'];
    $out['lastchange'] = $this->assessRecord['lastchange'];
    $out['timeontask'] = $this->assessRecord['timeontask'];
    $out['scored_version'] = $this->data['scored_version'];
    return $out;
  }

  /**
   * Get array of scores and and question details for all scored questions
   * @return array
   */
  public function getGbAssessData() {
    $this->parseData();
    $by_question = ($this->assess_info->getSetting('submitby') == 'by_question');
    $out = array();
    $scored_aver = $by_question ? 0 : $this->data['scored_version'];
    for ($av = 0; $av < count($this->data['assess_versions']); $av++) {
      $aver = $this->data['assess_versions'][$av];
      $out[$av] = array(
        'score' => $aver['score'],
        'lastchange' => $aver['lastchange'],
        'status' => $aver['status'],
        'scored_version' => $scored_aver
      );
      if ($av == $scored_aver) {
        $out[$av]['status'] = 2;
        $out[$av]['feedback'] = $aver['feedback'];
        $out[$av]['starttime'] = $aver['starttime'];
        $out[$av]['questions'] = $this->getGbQuestionsData($by_question ? 'scored' : $av);
      }
    }
    return $out;
  }

  /**
   * Get the questions data for a particular assessment or question version
   * @param  string $ver   assessment or question version, or 'scored'
   * @return array
   */
  public function getGbQuestionsData($ver = 'scored') {
    $by_question = ($this->assess_info->getSetting('submitby') == 'by_question');
    if ($by_question) {
      $aver = 0;
    } else if ($aver === 'scored') {
      $aver = $this->data['scored_version'];
    } else {
      $aver = $ver;
    }
    $qdata = $this->data['assess_versions'][$aver]['questions'];
    $out = array();
    for ($qn = 0; $qn < count($qdata); $qn++) {
      $out[$qn] = array();
      $qvers = $qdata[$qn]['question_versions'];
      if (!$by_question) {
        $qver = 0;
      } else if ($ver === 'scored') {
        $qver = $qdata[$qn]['scored_version'];
      } else {
        $qver = $ver;
      }
      for ($qv = 0; $qv < count($qvers); $qv++) {
        $out[$qn][$qv] = $this->getGbQuestionVersionData($qn, $qv==$qver, $by_question ? $qv : $aver);
        if ($qv == $qver) {
          $out[$qn][$qv]['scored'] = true;
        }
      }
    }
    return $out;
  }

  /**
   * Get the specific details on a question version
   * @param  int  $qn                Question number
   * @param  boolean $generate_html Whether to return HTML of question
   * @param  string  $ver           'scored' or particular assess/question version
   * @return array
   */
  public function getGbQuestionVersionData($qn, $generate_html = false, $ver = 'scored') {
    $by_question = ($this->assess_info->getSetting('submitby') == 'by_question');
    if ($by_question) {
      $aver = 0;
    } else if ($aver === 'scored') {
      $aver = $this->data['scored_version'];
    } else {
      $aver = $ver;
    }
    if (!$by_question) {
      $qver = 0;
    } else if ($ver === 'scored') {
      $qver = $this->data['assess_versions'][$aver]['questions'][$qn]['scored_version'];
    } else {
      $qver = $ver;
    }
    $qdata = $this->data['assess_versions'][$aver]['questions'][$qn]['question_versions'][$qver];
    $out = $this->getQuestionObject($qn, true, false, $generate_html, $by_question ? $qver : $aver);
    if ($generate_html) { // only include this if we're displaying the question
      $out['qid'] = $qdata['qid'];
      $out['qsetid'] = $this->assess_info->getQuestionSetting($qdata['qid'], 'questionsetid');
      $out['seed'] = $qdata['seed'];
      $out['feedback'] = $qdata['feedback'];
      $out['other_tries'] = $this->getPreviousTries($qdata['tries']);
    }
    return $out;
  }

  /**
   * Collect the previous tries, organized by part number
   * TODO: eliminate the displayed/current try
   * @param  array $trydata  Data from a question version 'tries' array
   * @return array
   */
  private function getPreviousTries($trydata) {
    $out = array();
    for ($pn = 0; $pn < count($trydata); $pn++) {
      $out[$pn] = array();
      for ($tn = 0; $tn < count($trydata[$pn]); $tn++) {
        $out[$pn][] = $trydata[$pn][$tn]['stuans'];
      }
    }
    return $out;
  }

  /**
   * Calculate the score on a question after applying penalties
   * @param  float $score    Raw score, 0-1
   * @param  float $points   Points possible
   * @param  int $try        The try number (starts at 0)
   * @param  int $retry_penalty
   * @param  int $retry_penalty_after
   * @param  int $regen      The regen number (starts at 0)
   * @param  int $regen_penalty
   * @param  int $regen_penalty_after
   * @param  int $duedate    Original due date timestamp
   * @param  int $exceptionpenalty
   * @param  int $subtime    Timestamp question was submitted
   *
   * @param boolean $returnPenalties  Set true to return array of penalties applied (def: false)
   * @return float  score after penalties if $returnPenalties = false
   *         array(score, array of penalties) if $returnPenalties = true
   */
  private function scoreAfterPenalty($score, $points, $try, $retry_penalty, $retry_penalty_after, $regen, $regen_penalty, $regen_penalty_after, $duedate, $subtime, $exceptionpenalty, $returnPenalties = false) {
    $base = $score * $points;
    $penalties = array();
    if ($retry_penalty > 0) {
      $triesOver = $try + 1 - $retry_penalty_after;
      if ($triesOver > 1e-10) {
        $base *= (1 - $triesOver * $retry_penalty/100);
        $penalties[] = array('type'=>'retry', 'pct'=>$triesOver * $retry_penalty);
      }
    }
    if ($regen_penalty > 0) {
      $regensOver = $regen + 1 - $regen_penalty_after;
      if ($regensOver > 1e-10) {
        $base *= (1 - $regensOver * $regen_penalty/100);
        $penalties[] = array('type'=>'regen', 'pct'=>$regensOver * $regen_penalty);
      }
    }
    if ($exceptionpenalty > 0 && $subtime > $duedate) {
      $base *= (1 - $exceptionpenalty / 100);
      $penalties[] = array('type'=>'late', 'pct'=>$exceptionpenalty);
    }
    if ($returnPenalties) {
      return array($base, $penalties);
    } else {
      return $base;
    }
  }

  /**
   * Finds the time active in the question, given question version datas
   * @param  object $qdata   question version object
   * @return array  assoc array with 'total' and 'pertry' times active
   *  Note that "pertry" is a rough average for the time per full-question try
   */
  private function calcTimeActive($qdata) {
    $trycnt = 0;
    $timetot = 0;
    $subsUsed = array();
    $partsCnt = count($qdata['tries']);
    foreach ($qdata['tries'] as $pn=>$part) {
      foreach ($part as $tn=>$try) {
        $trycnt++;
        // only count the time once for each submission, no matter how many parts
        if (!isset($subsUsed[$try['sub']])) {
          $timetot += $try['time'];
          $subsUsed[$try['sub']] = 1;
        }
      }
    }
    return array(
      'total' => $timetot,
      'pertry' => ($trycnt>0) ? min($timetot, $timetot*$partsCnt/$trycnt) : 0
    );
  }

  /**
   * Get the specified version of assessment data
   * @param  string  $ver         The assessment attempt to grab, or 'last'
   * @return object  assessment data for that take
   */
  private function getAssessVer($ver = 'last') {
    $by_question = ($this->assess_info->getSetting('submitby') == 'by_question');
    $this->parseData();
    if ($this->is_practice) {
      $assessver = $this->data['assess_versions'][0];
    } else if ($by_question || !is_numeric($ver)) {
      $assessver = $this->data['assess_versions'][count($this->data['assess_versions']) - 1];
    } else {
      $assessver = $this->data['assess_versions'][$ver];
    }
    return $assessver;
  }

  /**
   * Returns the specified version of question attempt data
   * @param  int  $qn          The question number
   * @param  string  $ver         The assessment attempt to grab, or 'last'
   * @return object   question data for that version
   */
  private function getQuestionVer($qn, $ver = 'last') {
    $by_question = ($this->assess_info->getSetting('submitby') == 'by_question');
    $this->parseData();
    if ($this->is_practice) {
      $assessver = $this->data['assess_versions'][0];
    } else if ($by_question || !is_numeric($ver)) {
      $assessver = $this->data['assess_versions'][count($this->data['assess_versions']) - 1];
    } else {
      $assessver = $this->data['assess_versions'][$ver];
    }
    $question_versions = $assessver['questions'][$qn]['question_versions'];
    if (!$by_question || !is_numeric($ver)) {
      $curq = $question_versions[count($question_versions) - 1];
    } else {
      $curq = $question_versions[$ver];
    }
    return $curq;
  }

  /**
   * Get Autosave info for the given question
   * @param  int  $qn         The question number
   * @return array of autosave data
   */
  private function getAutoSaves($qn) {
    $this->parseData();
    if (isset($this->data['autosaves'][$qn])) {
      return $this->data['autosaves'][$qn];
    }
    return array();
  }

  /**
   * Record a try on a question
   * @param  int $qn      Question number
   * @param  array $data  Array of part datas
   * @param  boolean $singlescore  Whether question is multipart showing single score
   * @param  mixed $ver   Version number to record this on, or 'last'
   * @return void
   */
  private function recordTry($qn, $data, $singlescore = false, $ver='last') {
    $by_question = ($this->assess_info->getSetting('submitby') == 'by_question');
    $this->parseData();
    $isFirstVer = false;
    if ($this->is_practice) {
      $assessver = &$this->data['assess_versions'][0];
    } else if ($by_question || !is_numeric($ver)) {
      $assessver = &$this->data['assess_versions'][count($this->data['assess_versions']) - 1];
      $isFirstVer = (!$by_question && count($this->data['assess_versions']) === 1);
    } else {
      $assessver = &$this->data['assess_versions'][$ver];
    }
    $question_versions = &$assessver['questions'][$qn]['question_versions'];
    if (!$by_question || !is_numeric($ver)) {
      $curq = &$question_versions[count($question_versions) - 1];
      $isFirstVer = ($by_question && count($question_versions) === 1);
    } else {
      $curq = &$question_versions[$ver];
    }
    if ($singlescore) {
      $curq['singlescore'] = true;
    } else if (isset($curq['singlescore'])) {
      unset($curq['singlescore']);
    }
    if ($isFirstVer) {
      $hadUnattempted = $this->hasUnattemptedParts($curq);
    }
    foreach ($data as $pn=>$partdata) {
      $curq['tries'][$pn][] = $partdata;
    }
    // if it's the first version, and before this we didn't have all parts
    // attempted, but now we do, then we'll record this to firstscores
    if ($isFirstVer && $hadUnattempted && !$this->hasUnattemptedParts($curq)) {
      // record to firstscores
      $this->recordFirstScores($curq);
    }
  }

  /**
   * Determines if there are any unattempted parts
   * @param  array  $qdata  Array of question info from question_version
   * @return boolean   true if any unattempted parts
   */
  private function hasUnattemptedParts($qdata) {
    if (count($qdata['tries']) === 0 ||
      count($qdata['tries']) < count($qdata['answeights'])
    ) {
      return true;
    }
    foreach ($qdata['tries'] as $pn=>$partdata) {
      if (count($partdata) === 0) {
        return true;
      }
    }
    return false;
  }

  /**
   * Record first try info to imas_firstscores table
   * @param  array  $qdata question version data array
   * @return void
   */
  private function recordFirstScores($qdata) {
    // only record for students
    // TODO: better way to determine isstudent
    if (!$GLOBALS['isstudent']) {
      return;
    }
    $timeonfirst = 0;
    $scoredet = array();
    $scoreonfirst = 0;
    $subsUsed = array();

    for ($pn = 0; $pn < count($qdata['tries']); $pn++) {
      $firstTry = $qdata['tries'][$pn][0];
      $scoreonfirst += $firstTry['raw'] * $qdata['answeights'][$pn];
      $scoredet[$pn] = $firstTry['raw'];
      if (!isset($subsUsed[$firstTry['sub']])) {
        $timeonfirst += $firstTry['time'];
        $subsUsed[$firstTry['sub']] = 1;
      }
    }

    $query = "INSERT INTO imas_firstscores (courseid,qsetid,score,scoredet,timespent) VALUES ";
		$query .= "(:courseid, :qsetid, :score, :scoredet, :timespent)";
		$stm = $this->DBH->prepare($query);
    $stm->execute(array(
      ':courseid'=> $this->assess_info->getCourseId(),
      ':qsetid'=> $this->assess_info->getQuestionSetting($qdata['qid'], 'questionsetid'),
			':score'=> round(100*$scoreonfirst),
      ':scoredet'=> implode('~', $scoredet),
      ':timespent'=> $timeonfirst
    ));
  }

  /**
   * uncompress and decode attempt data
   * @return void
   */
  private function parseData () {
    if ($this->data === null) {
      if ($this->is_practice) {
        if ($this->assessRecord['practicedata'] != '') {
          $this->data = json_decode(gzdecode($this->assessRecord['practicedata']), true);
        }
      } else {
        if ($this->assessRecord['scoreddata'] != '') {
          $this->data = json_decode(gzdecode($this->assessRecord['scoreddata']), true);
        }
      }
      if ($this->data === null) {
        $this->data = array();
      }
    }
  }

}

<?php
/*
 * IMathAS: Assessment Settings Class
 * (c) 2019 David Lippman
 */

require_once('./AssessUtils.php');

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
  private $scoredData = null;
  private $practiceData = null;
  private $status = 'no_record';
  private $now = 0;

  /**
   * Construct object
   * @param object $DBH PDO Database Handler
   * @param object $assess_info  AssessInfo instance
   */
  function __construct($DBH, $assess_info = null) {
    $this->DBH = $DBH;
    $this->assess_info = $assess_info;
    $this->curAid = $assess_info->getSetting('id');
    $this->$now = time();
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
  }

  /**
   * Save the record to the database
   * @param  boolean $saveScored   Whether to save scored data (def: true)
   * @param  boolean $savePractice Whether to save practice data (def: false)
   * @return void
   */
  public function saveRecord($saveScored = true, $savePractice = false) {
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
    if ($saveScored && $this->scoredData !== null) {
      $fields[] = 'scoreddata';
      $qarr[':scoreddata'] = gzencode(json_encode($this->scoredData));
    }
    if ($savePractice && $this->practiceData !== null) {
      $fields[] = 'practicedata';
      $qarr[':practicedata'] = gzencode(json_encode($this->practiceData));
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
    } else {

    }
  }

  /**
   * Create a new record in the database.  Call after loadRecord.
   *
   * @param  integer $groupsetid    The groupsetid for the assessment, if group (def: 0, not group)
   * @param  boolean $recordStart   true to record the starttime now (def: true)
   * @param  string  $lti_sourcedid The LTI sourcedid (def: '')
   * @param  boolean $inpractice    True if in practice mode, to gen practice data (def: false)
   * @return void
   */
  public function createRecord($groupsetid = 0, $recordStart = true, $lti_sourcedid = '', $inpractice = false) {
    // if group, lookup group members. Otherwise just use current user
    if ($groupsetid > 0) {
      list($agroupid, $userinfo) = AssessUtils::getGroupMembers($this->curUid, $groupsetid);
      $users = array_keys($userinfo);
    } else {
      $users = array($this->curUid);
      $agroupid = 0;
    }

    //initiale a blank record
    $this->assessRecord = array(
      'assessmentid' => $this->curAid,
      'userid' => $this->curUid,
      'agroupid' => $agroupid,
      'lti_sourcedid' => $lti_sourcedid,
      'ver' => 2,
      'timeontask' => 0,
      'starttime' => $recordStart ? $this->now : 0,
      'lastchange' => 0,
      'score' => 0,
      'status' => 0,
      'scoreddata' => '',
      'practicedata' => ''
    )

    // initialize scoredData
    $this->scoredData = $this->buildAssessData(false, $recordStart);
    // if in practice, initialize practiceData
    if ($inpractice) {
      $this->practiceData = $this->buildAssessData(true, $recordStart);
    }

    // Save to Database
    $qarr = array();
    $vals = array();
    $scoredtosave = ($this->scoredData !== null) ? gzencode(json_encode($this->scoredData)) : '';
    $practicetosave = ($this->practiceData !== null) ? gzencode(json_encode($this->practiceData)) : '';
    foreach ($users as $uid) {
      $vals[] = '(?,?,?,?,?,?,?,?,?)';
      array_push($qarr,
        $uid,
        $this->curAid,
        $agroupid,
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
   * @param  boolean $ispractice  True if generating practiceData (def: false)
   * @param  boolean $recordStart True to record starttime now
   * @return array for scoredData or practiceData
   */
  public function buildAssessData($ispractice = false, $recordStart = true) {
    return array(
      'submissions' => array(),
      'autosaves' => array(),
      'scored_version' => 0,
      'assess_versions' => array(
        $this->buildNewAssessVersion($inpractice, 0, $recordStart)
      )
    )
  }

  /**
   * Build a new assess_versions record
   * @param  boolean $ispractice  True if building practice data
   * @param  integer $attempt        The attempt number
   * @param  boolean $recordStart True to record starttime now
   * @return array of assessment data
   */
  public function buildNewAssessVersion($ispractice = false, $attempt = 0, $recordStart = true) {
    // build base framework
    $out = array(
      'starttime' => $recordStart ? $this->now : 0,
      'lastchange' => 0,
      'status' => 0,
      'score' => 0,
      'questions' => array();
    )
    if ($recordStart && $this->assess_info->getSetting('timelimit') > 0) {
      //recording start and has time limit, so record end time
      $out['timelimit_end'] = $this->now + $this->assess_info->getAdjustedTimelimit();
    }

    // generate the questions and seeds
    list($oldquestions, $oldseeds) = $this->getOldQuestions($ispractice);
    list($questions, $seeds) = $this->assess_info->assignQuestionsAndSeeds($ispractice, $attempt);
    // build question data
    for ($k = 0; $k < count($questions), $k++) {
      $out['questions'][] = array(
        'scored_version' => 0,
        'question_versions' => array(
          array(
            'qid' => $questions[$k],
            'seed' => $seeds[$k],
            'tries' => array()
          )
        )
      );
    }
    return $out;
  }

  /**
   * Get old questions and seeds previously used in assessment record
   * @param  boolean $inpractice    True if in practice mode, to get practice data (def: false)
   * @return array array($questions, $seeds), where each is an array of values
   */
  public function getOldQuestions($ispractice = false) {
    $questions = array();
    $seeds = array();
    if ($ispractice) {
      $data = $this->practiceData;
    } else {
      $data = $this->scoredData;
    }
    if ($data !== null) {
      foreach ($data['assess_versions'] as $ver) {
        foreach ($ver['question_versions'] as $qver) {
          $questions[] = $qver['qid'];
          $seeds[] = $qver['seed'];
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
    // status has bitwise 1: active by-assess attempt
    // status has bitwise 2: active by-question attempt
    return ($this->assessRecord['status']&3 !== 0);
  }

  /**
   * Determine if there is an active practice attempt
   * @return boolean true if there is an active practice attempt
   */
  public function hasPracticeAttempt() {
    if (empty($this->assessRecord)) {
      //no assessment record at all
      return false;
    }
    // status has bitwise 16: active practice attempt
    return ($this->assessRecord['status']&16 !== 0);
  }

  /**
   * Get data on submitted attempts
   *
   * @param boolean $includeScores  Whether to include scores. Default: false
   * @return array        An array of previous attempt info.  Each element is an
   *                      array containing key 'date', and 'score' if the
   *                      settings allow it
   */
  public function getSubmittedAttempts($includeScores = false) {
    if (empty($this->assessRecord)) {
      //no assessment record at all
      return array();
    }
    $this->parseScored();

    $out = array();
    foreach ($this->scoredData['assess_versions'] as $k=>$ver) {
      if ($ver['status'] == 1) {  // if it's a submitted version
        $out[$k] = array(
          'date' => $ver['lastchange'],
        );
        if ($includeScores) {
          $out[$k]['score'] = $ver['score'];
        }
      }
    }
    return $out;
    //TODO:  Need to report teacher score override somehow
  }

  /**
   * Get the scored attempt version and score
   * @return array  'kept': version # or 'override' if instructor override
   *                        may not be set if using average
   *                'score': the final assessment score
   */
  public function getScoredAttempt() {
    if (empty($this->assessRecord)) {
      //no assessment record at all
      return array();
    }
    $this->parseScored();

    $out = array('score' => $this->assessRecord['score']);
    if (isset($this->scoredData['scoreoverride'])) {
      $out['kept'] = 'override';
    } else if (isset($this->scoredData['scored_version'])) {
      $out['kept'] = $this->scoredData['scored_version'];
    }
    return $out;
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
   * Get the timestamp for when the current attempt time limit expires
   *
   * @return integer  timestamp for when  the current attempt time limit expires
   *                  will be 0 if there is no time limit
   */
  public function getTimeLimitExpires() {
    if (empty($this->assessRecord)) {
      return false;
    }
    $this->parseScored();
    if (count($this->scoredData['assess_versions']) == 0) {
      return false;
    }
    //grab value from last (current) assess attempt
    $lastvernum = count($this->scoredData['assess_versions']) - 1;
    $lastver = $this->scoredData['assess_versions'][$lastvernum];
    return $lastver['timelimit_end'];
  }

  /**
   * uncompress and decode scoredData
   * @return void
   */
  private function parseScored () {
    if ($this->scoredData === null) {
      $this->scoredData = json_decode(gzdecode($this->assessRecord['scoreddata']), true);
      if ($this->scoredData === null) {
        $this->scoredData = array();
      }
    }
  }

  /**
   * uncompress and decode practiceData
   * @return void
   */
  private function parsePractice () {
    if ($this->practiceData === null) {
      $this->practiceData = json_decode(gzdecode($this->assessRecord['practicedata']), true);
      if ($this->practiceData === null) {
        $this->practiceData = array();
      }
    }
  }

  /**
   * Calculate score on an assessment version
   * @param  int $vernum  The version number (0-index)
   * @param  array $verdata The attempt data for the version
   * @return float          The total score on the version, after assessment
   *                        settings are applied
   */
  private function calcAssessVersionScores($vernum, $verdata) {
    //TODO
    return 0;
  }

}

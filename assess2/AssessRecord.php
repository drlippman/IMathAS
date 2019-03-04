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
  private $hasRecord = false;
  private $scoredData = null;
  private $practiceData = null;
  private $status = 'no_record';

  /**
   * Construct object
   * @param object $DBH PDO Database Handler
   */
  function __construct($DBH) {
    $this->DBH = $DBH;
  }

  /**
   * Load an assessment record given the user id and assessment id.
   * @param  integer $userid  The user ID
   * @param  integer $aid     The assessment ID
   * @return void
   */
  public function loadRecord($userid, $aid) {
    $this->curAid = $aid;
    $this->curUid = $userid;
    $stm = $this->DBH->prepare("SELECT * FROM imas_assessment_records WHERE userid=? AND assessmentid=?");
    $stm->execute(array($userid, $aid));
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
   * @return void
   */
  public function createRecord($groupsetid = 0, $recordStart = true, $lti_sourcedid = '') {
    // if group, lookup group members. Otherwise just use current user
    if ($groupsetid > 0) {
      list($agroupid, $userinfo) = AssessUtils::getGroupMembers($this->curUid, $groupsetid);
      $users = array_keys($userinfo);
    } else {
      $users = array($this->curUid);
      $agroupid = 0;
    }

    //initiale a blank record
    $now = time();
    $this->assessRecord = array(
      'assessmentid' => $this->curAid,
      'userid' => $this->curUid,
      'agroupid' => $agroupid,
      'lti_sourcedid' => $lti_sourcedid,
      'ver' => 2,
      'timeontask' => 0,
      'starttime' => $recordStart ? $now : 0,
      'lastchange' => 0,
      'score' => 0,
      'status' => 0,
      'scoreddata' => '',
      'practicedata' => ''
    )

    //TODO:  initialize data? might need to know if practice

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
        $recordStart ? $now : 0,
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
   * Returns whether an assessment record exists. Call after loadRecord
   * @return boolean true if an assessment record exists
   */
  public function hasRecord() {
    return ($this->hasRecord);
  }

  /**
   * Determine if there is an active assessment take
   * @return boolean true if there is an active assessment take
   */
  public function hasActiveTake() {
    if (empty($this->assessRecord)) {
      //no assessment record at all
      return false;
    }
    // status has bitwise 1: active by-assess attempt
    // status has bitwise 2: active by-question attempt
    return ($this->assessRecord['status']&3 !== 0);
  }

  /**
   * Determine if there is an active practice take
   * @return boolean true if there is an active practice take
   */
  public function hasPracticeTake() {
    if (empty($this->assessRecord)) {
      //no assessment record at all
      return false;
    }
    // status has bitwise 16: active practice attempt
    return ($this->assessRecord['status']&16 !== 0);
  }

  /**
   * Get data on submitted takes
   *
   * @param boolean $includeScores  Whether to include scores. Default: false
   * @return array        An array of previous take info.  Each element is an
   *                      array containing key 'date', and 'score' if the
   *                      settings allow it
   */
  public function getSubmittedTakes($includeScores = false) {
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
   * Get the scored take version and score
   * @return array  'kept': version # or 'override' if instructor override
   *                        may not be set if using average
   *                'score': the final assessment score
   */
  public function getScoredTake() {
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
   * Get group members
   *
   * @return array        An array of group member names
   */
  public function getGroupMembers() {
    if (empty($this->assessRecord)) {
      //no assessment record at all
      return array();
    }
    if ($this->assessRecord['agroupid'] == 0) {
      return array();
    }
    // TODO:  This function isn't correct if no assess records exist
    // FIXME!!!!!
    $query = 'SELECT iu.FirstName,iu.LastName FROM imas_users AS iu ';
    $query .= 'JOIN imas_assessment_records AS iar ON iar.userid=iu.id AND ';
    $query .= 'iar.assessmentid=? AND iar.agroupid=? ';
    $query .= 'ORDER BY iu.FirstName, iu.LastName';
    $stm = $this->DBH->prepare($query);
    $stm->execute(array($this->curAid, $this->assessRecord['agroupid']));
    $out = array();
    while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
      $out[] =  $row['FirstName'] . ' ' . $row['LastName'];
    }
    return $out;
  }

  /**
   * Get the timestamp for when the current take time limit expires
   *
   * @return integer  timestamp for when  the current take time limit expires
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
    //grab value from last (current) retake
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

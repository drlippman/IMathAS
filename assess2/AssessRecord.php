<?php
/*
 * IMathAS: Assessment Settings Class
 * (c) 2019 David Lippman
 */

/**
 * Primary class for working with assessment records
 */
class AssessRecord
{
  private $DBH = null;
  private $curArid = null;
  private $curAid = null;
  private $assessRecord = null;
  private $status = 'no_record';

  /**
   * Construct object
   * @param object $DBH PDO Database Handler
   */
  function __construct($DBH) {
    $this->DBH = $DBH;
  }

  /**
   * Load an assessment record given the id.
   * @param  integer $arid Assessment Record ID.
   * @return void
   */
  public function loadByRecordId($arid) {
    /*
    $stm->execute(array($arid));
    $this->assessRecord = $stm->fetch(PDO::FETCH_ASSOC);
    */
  }

  /**
   * Load an assessment record given the user id and assessment id.
   * @param  integer $userid  The user ID
   * @param  integer $aid     The assessment ID
   * @return void
   */
  public function loadByUserId($userid, $aid) {
    /*
    $stm = $this->DBH->prepare("SELECT * FROM imas_assessment_records WHERE userid=? AND assessmentid=?");
    $stm->execute(array($userid, $aid));
    $this->assessRecord = $stm->fetch(PDO::FETCH_ASSOC);
    */
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
    //TODO:  finish me
  }

  /**
   * Get data on takes previous to the current one
   *
   * @param boolean $includeScores  Whether to include scores. Default: false
   * @return array        An array of previous take info.  Each element is an
   *                      array containing key 'date', and 'score' if the
   *                      settings allow it
   */
  public function getPrevTakes($includeScores = false) {
    if (empty($this->assessRecord)) {
      //no assessment record at all
      return array();
    }
    //TODO: finish me
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
    //TODO: finish me
  }

  /**
   * Get the timestamp for when the current take time limit expires
   *
   * @return integer  timestamp for when  the current take time limit expires
   */
  public function getTimeLimitExpires() {
    //TODO: finish me
  }

}

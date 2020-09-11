<?php
/*
 * IMathAS: Helpers Class for working with assessment data
 * (c) 2020 David Lippman
 */

require_once(__DIR__."/../includes/ltioutcomes.php");
require_once(__DIR__.'/AssessInfo.php');
require_once(__DIR__.'/AssessRecord.php');

class AssessHelpers
{

  /**
   * Recalculute score for all assessments
   * @param  int $cid   The course ID
   * @param  int $aid   The assessment ID
   * @param  bool $updateLTI   Whether to send updated LTI grades
   * @param  bool $forceresend  Whether to send LTI even if unchanged
   * @param  string $convertsubmitby  Set non-empty to convert data for submitby change
   * @param  bool $transaction  Whether to wrap in a transaction
   */
  public static function retotalAll($cid, $aid, $updateLTI=true, $forceresend=false, $convertsubmitby='',$transaction=true) {
    global $DBH;
    // Re-total any student attempts on this assessment
      //need to re-score assessment attempts based on withdrawal
    if ($transaction) {
          $DBH->beginTransaction();
    }
  	$stm = $DBH->prepare("SELECT * FROM imas_assessment_records WHERE assessmentid=? FOR UPDATE");
  	$stm->execute(array($aid));
  	if ($stm->rowCount() > 0) {
  		$assess_info = new AssessInfo($DBH, $aid, $cid, false);
  		$assess_info->loadQuestionSettings('all', false, false);
        $submitby = $assess_info->getSetting('submitby');
  		while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
  			$assess_record = new AssessRecord($DBH, $assess_info, false);
            $assess_record->setRecord($row);
            $orig_gb_score = $assess_record->getGbScore();
            if ($convertsubmitby !== '') {
                $assess_record->convertSubmitBy($convertsubmitby);
            }
  			$assess_record->reTotalAssess();
            $assess_record->saveRecord();
            if ($updateLTI) {
                // update LTI grade
                $lti_sourcedid = $assess_record->getLTIsourcedId();
                if (strlen($lti_sourcedid) > 1 && ($submitby == 'by_question' ||
                    ($assess_record->getStatus()&64)==64)
                ) {
                    $gbscore = $assess_record->getGbScore();
                    if ($orig_gb_score['gbscore'] != $gbscore['gbscore']) {
                        $aidposs = $assess_info->getSetting('points_possible');
                        calcandupdateLTIgrade($lti_sourcedid, $aid, $line['userid'], $gbscore['gbscore'], true, $aidposs);
                    }
                }
            }
  		}
    }
    if ($transaction) {  
          $DBH->commit();
    }
  }

    /**
   * Recalculute score for one stu.  Only resends if changed.
   * @param  int $cid   The course ID
   * @param  int $aid   The assessment ID
   * @param  int $uid   The user ID 
   */
  public static function retotalOne($cid, $aid, $uid) {
    global $DBH;
    // Re-total any student attempts on this assessment
      //need to re-score assessment attempts based on withdrawal
    $DBH->beginTransaction();
  	$stm = $DBH->prepare("SELECT * FROM imas_assessment_records WHERE assessmentid=? AND userid=? FOR UPDATE");
  	$stm->execute(array($aid, $uid));
  	if ($stm->rowCount() > 0) {
  		$assess_info = new AssessInfo($DBH, $aid, $cid, false);
  		$assess_info->loadQuestionSettings('all', false, false);
        $submitby = $assess_info->getSetting('submitby');
  		while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
  			$assess_record = new AssessRecord($DBH, $assess_info, false);
            $assess_record->setRecord($row);
            $orig_gb_score = $assess_record->getGbScore();
            $assess_record->reTotalAssess();
            $gbscore = $assess_record->getGbScore();
            if ($orig_gb_score['gbscore'] != $gbscore['gbscore']) {  
                $assess_record->saveRecord();
                // update LTI grade
                $lti_sourcedid = $assess_record->getLTIsourcedId();
                if (strlen($lti_sourcedid) > 1 && ($submitby == 'by_question' ||
                    ($assess_record->getStatus()&64)==64)
                ) {
                    $aidposs = $assess_info->getSetting('points_possible');
                    calcandupdateLTIgrade($lti_sourcedid, $aid, $line['userid'], $gbscore['gbscore'], true, $aidposs);
                }
            }
  		}
    }
    $DBH->commit();
  }

  /**
   * Submit all unsubmitted quiz-style attempts
   * @param  int $cid   The course ID
   * @param  int $aid   The assessment ID
   * @return int      The number of assessments submitted
   */
  public static function submitAllUnsumitted($cid, $aid) {
    global $DBH;
    // load settings
    $assess_info = new AssessInfo($DBH, $aid, $cid, false);

    // this only makes sense for by_assessment mode
    if ($assess_info->getSetting('submitby') !== 'by_assessment') {
      return 0;
    }
    // grab all questions settings
    $assess_info->loadQuestionSettings('all', false, false);

    $DBH->beginTransaction();
    $stm = $DBH->prepare("SELECT * FROM imas_assessment_records WHERE assessmentid=? FOR UPDATE");
    $stm->execute(array($aid));

    $cnt = 0;
    while($line=$stm->fetch(PDO::FETCH_ASSOC)) {
      $GLOBALS['assessver'] = $line['ver'];

      $assess_record = new AssessRecord($DBH, $assess_info, false);
      $assess_record->setRecord($line);
      $assess_record->parseData();

      // need to check if assessment is still available for student
      $assess_info->loadException($line['userid'], true);
      if ($assess_info->getSetting('available') === 'yes') {
        // skip if still available to student and no time limit or not expired
        if (abs($assess_info->getSetting('timelimit')) > 0) {
          // has a time limit
          $timeLimitExp = max(
            $assess_record->getTimeLimitExpires(),
            $assess_record->getTimeLimitGrace()
          );
          if ($timeLimitExp == 0 || $timeLimitExp > time()) {
            continue;
          }
        } else {
          continue;
        }
      }

      if ($assess_record->hasActiveAttempt()) {
        $assess_record->scoreAutosaves();
        $assess_record->setStatus(false, true);
        // Recalculate scores based on submitted assessment.
        // Since we already retotaled for newly submitted questions, we can
        // just reuse existing question scores
        $assess_record->reTotalAssess(array());
        $assess_record->saveRecord();

        // update LTI grade
        $lti_sourcedid = $assess_record->getLTIsourcedId();
        if (strlen($lti_sourcedid) > 1) {
          $gbscore = $assess_record->getGbScore();
          $aidposs = $assess_info->getSetting('points_possible');
          calcandupdateLTIgrade($lti_sourcedid, $aid, $line['userid'], $gbscore['gbscore'], true, $aidposs);
        }
        $cnt++;
      }
    }
    $DBH->commit();
    return $cnt;
  }
}

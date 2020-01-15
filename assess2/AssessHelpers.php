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
    $assess_info->loadQuestionSettings('all', false);

    $stm = $DBH->prepare("SELECT * FROM imas_assessment_records WHERE assessmentid=?");
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
          calcandupdateLTIgrade($lti_sourcedid, $aid, $gbscore['gbscore'], true, $aidposs);
        }
        $cnt++;
      }
    }
    return $cnt;
  }
}

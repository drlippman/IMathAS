<?php
/*
 * IMathAS: Get questions for showwork
 * (c) 2020 David Lippman
 *
 *
 * Method: POST
 * Query string parameters:
 *  aid   Assessment ID
 *  cid   Course ID
 *  uid   Optional. Only allowed for teachers, to load student's assessment
 *
 * Returns: assessInfo object with compute question info
 */


$no_session_handler = 'json_error';
require_once("../init.php");
require_once("./common_start.php");
require_once("./AssessInfo.php");
require_once("./AssessRecord.php");
require_once('./AssessUtils.php');

header('Content-Type: application/json; charset=utf-8');

check_for_required('GET', array('aid', 'cid'));
$cid = Sanitize::onlyInt($_GET['cid']);
$aid = Sanitize::onlyInt($_GET['aid']);
if ($isActualTeacher && isset($_GET['uid'])) {
  $uid = Sanitize::onlyInt($_GET['uid']);
} else {
  $uid = $userid;
}

$now = time();

// load settings
$assess_info = new AssessInfo($DBH, $aid, $cid, false);
$assess_info->loadException($uid, $isstudent, $studentinfo['latepasses'] , $latepasshrs, $courseenddate);
if ($isstudent) {
  $assess_info->applyTimelimitMultiplier($studentinfo['timelimitmult']);
}

// load user's assessment record - always looking at scored
$assess_record = new AssessRecord($DBH, $assess_info, false);
$assess_record->loadRecord($uid);

if (!$assess_record->hasRecord()) {
  echo '{"error": "not_ready"}';
  exit;
}
if ($canViewAll) {
    $assess_record->setIncludeErrors(true); //only show errors to teachers/tutors
}

$assessInfoOut = array();

// if have active scored record end it
if ($assess_info->getSetting('submitby') == 'by_assessment' &&
  $assess_record->hasActiveAttempt()
) {
  echo '{"error": "active_attempt"}';
  exit;
}

// grab all questions settings and scores, based on end-of-assessment settings
$showscores = $assess_info->showScoresAtEnd();
$reshowQs = $assess_info->reshowQuestionsInGb();
$assess_info->loadQuestionSettings('all', $reshowQs);
$assessInfoOut['questions'] = $assess_record->getAllQuestionObjects($showscores, true, $reshowQs);

//prep date display
prepDateDisp($assessInfoOut);

//output JSON object
echo json_encode($assessInfoOut, JSON_INVALID_UTF8_IGNORE);

<?php
/*
 * IMathAS: Assessment after-assessment work submission
 * (c) 2020 David Lippman
 *
 *
 * Method: POST
 * Query string parameters:
 *  aid   Assessment ID
 *  cid   Course ID
 *  uid   Optional. Only allowed for teachers, to load student's assessment
 *
 * Returns: nothing
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
$assess_info->loadException($uid, $isstudent);
if ($isstudent) {
  $assess_info->applyTimelimitMultiplier($studentinfo['timelimitmult']);
}

// load user's assessment record - always operating on scored attempt here
$assess_record = new AssessRecord($DBH, $assess_info, false);
$assess_record->loadRecord($uid);

// grab all questions settings
$assess_info->loadQuestionSettings('all', false);

// grab any assessment info fields that may have updated:
$include_from_assess_info = array(
  'submitby', 'timelimit', 'timelimit_grace', 'timelimit_expires'
);
$assessInfoOut = $assess_info->extractSettings($include_from_assess_info);
//get attempt info
$assessInfoOut['has_active_attempt'] = $assess_record->hasActiveAttempt();

// adjust output if time limit is expired in by_question mode
if ($assessInfoOut['has_active_attempt'] && $assessInfoOut['timelimit'] > 0 &&
  $assessInfoOut['submitby'] == 'by_question' &&
  time() > max($assessInfoOut['timelimit_grace'],$assessInfoOut['timelimit_expires'])
) {
  $assessInfoOut['has_active_attempt'] = false;
  $assessInfoOut['can_retake'] = false;
  $assessInfoOut['pasttime'] = 1;
}

// if have active scored record end it
if (!$assess_record->hasRecord()) {
  echo '{"error": "not_ready"}';
} else if ($assessInfoOut['has_active_attempt']) {
  echo '{"error": "active_attempt"}';
} else {
  $res = $assess_record->saveWork($_POST['work']);
  if ($res !== true) {
    echo '{"error": $res}';
  } else {
    echo '{"success": true}';
  }
}

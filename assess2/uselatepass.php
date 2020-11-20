<?php
/*
 * IMathAS: Use LatePass from inside assessment player
 * (c) 2019 David Lippman
 *
 *
 *
 * Method: POST
 * Query string parameters:
 *  aid   Assessment ID
 *  cid   Course ID
 *  uid   Optional. Only allowed for teachers, to load student's assessment
 *
 * Returns: partial assessInfo object, mainly has_active_attempt
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

if (!$assess_info->redeemLatePass($uid, $latepasshrs, $courseenddate)) {
  echo '{"error": "latepass_fail"}';
  exit;
}

//diminish studentinfo with the number of used LatePasses
$studentinfo['latepasses'] -= $assess_info->getSetting('can_use_latepass');

// reload exception info (hacky, but this doesn't happen often)
$assess_info->loadException($uid, $isstudent, $studentinfo['latepasses'] , $latepasshrs, $courseenddate);

// grab any assessment info fields that may have updated
$include_from_assess_info = array(
  'available', 'startdate', 'enddate', 'original_enddate',
  'extended_with', 'latepasses_avail', 'latepass_extendto',
  'can_use_latepass', 'enddate_in', 'timelimit'
);
$assessInfoOut = $assess_info->extractSettings($include_from_assess_info);

//prep date display
prepDateDisp($assessInfoOut);

//output JSON object
echo json_encode($assessInfoOut, JSON_INVALID_UTF8_IGNORE);

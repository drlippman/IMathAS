<?php
/*
 * IMathAS: Assessment launch endpoint
 * (c) 2019 David Lippman
 *
 * Method: GET
 * Query string parameters:
 *  aid   Assessment ID
 *  cid   Course ID
 *  uid   Optional. Only allowed for teachers, to load student's assessment
 *
 * Returns: assessInfo object
 */

require("../init.php");
require("./common_start.php");
require("./AssessInfo.php");
require("./AssessRecord.php");

//validate inputs
check_for_required('GET', array('aid', 'cid'));
$cid = Sanitize::onlyInt($_GET['cid']);
$aid = Sanitize::onlyInt($_GET['aid']);
if ($isteacher && isset($_GET['uid'])) {
  $uid = Sanitize::onlyInt($_GET['uid']);
} else {
  $uid = $userid;
}

//load settings without questions
$assess_info = new AssessInfo($DBH, $aid, $cid, false);
$assess_info->loadException($uid, $isstudent, $studentinfo['latepasses'] , $latepasshrs);
if ($isstudent) {
  $assess_info->applyTimelimitMultiplier($studentinfo['timelimitmult']);
}

//load user's assessment record
$assess_record = new AssessRecord($DBH);
$assess_record->loadByUserid($uid, $aid);

//fields to extract from assess info for inclusion in output
$include_from_assess_info = array(
  'name', 'summary', 'available', 'startdate', 'enddate', 'original_enddate',
  'extended_with', 'timelimit', 'timelimit_type', 'points_possible',
  'submitby', 'displaymethod', 'groupmax', 'isgroup', 'showscores', 'viewingb',
  'can_use_latepass', 'allowed_takes', 'retake_penalty'
);
$assessInfoOut = $assess_info->extractSettings($include_from_assess_info);

//set is_lti
$assessInfoOut['is_lti'] = isset($sessiondata['ltiitemtype']);

//set has password
$assessInfoOut['has_password'] = $assess_info->hasPassword();

//get take info
$assessInfoOut['has_active_take'] = $assess_record->hasActiveTake();
//get time limit expiration of current take, if appropriate
if ($assessInfoOut['has_active_take'] && $assessInfoOut['timelimit'] > 0) {
  $assessInfoOut['timelimit_expires'] = $assess_record->getTimeLimitExpires();
}

//get prev take info
if ($assessInfoOut['submitby'] == 'by_assessment') {
  $showPrevTakeScores = ($assessInfoOut['showscores'] != 'none');
  $assessInfoOut['prev_takes'] = $assess_record->getPrevTakes($showPrevTakeScores);
}

//load group members, if applicable
if ($assessInfoOut['isgroup'] > 0) {
  $assessInfoOut['group_members'] = $assess_record->getGroupMembers();
}

//output JSON object
header('Content-Type: application/json');
echo json_encode($assessInfoOut);

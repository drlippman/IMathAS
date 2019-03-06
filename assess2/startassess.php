<?php
/*
 * IMathAS: Assessment start endpoint
 * (c) 2019 David Lippman
 *
 * Method: POST
 * Query string parameters:
 *  aid   Assessment ID
 *  cid   Course ID
 *  uid   Optional. Only allowed for teachers, to load student's assessment
 *
 * POST parameters:
 *  password            Password for the assessment, if needed
 *  new_group_members   Comma separated list of userids to add to the group, if allowed
 *
 * Returns: partial assessInfo object, adding question data if launch successful
 *          may also return {error: message} if start fails
 */

require("../init.php");
require("./common_start.php");
require("./AssessInfo.php");
require("./AssessRecord.php");
require('./AssessUtils.php');

// validate inputs
check_for_required('GET', array('aid', 'cid'));
$cid = Sanitize::onlyInt($_GET['cid']);
$aid = Sanitize::onlyInt($_GET['aid']);
if ($isteacher && isset($_GET['uid'])) {
  $uid = Sanitize::onlyInt($_GET['uid']);
} else {
  $uid = $userid;
}

$now = time();

// load settings including question info
$assess_info = new AssessInfo($DBH, $aid, $cid, 'all');
$assess_info->loadException($uid, $isstudent, $studentinfo['latepasses'] , $latepasshrs, $courseenddate);
if ($isstudent) {
  $assess_info->applyTimelimitMultiplier($studentinfo['timelimitmult']);
}

// load user's assessment record
$assess_record = new AssessRecord($DBH, $assess_info);
$assess_record->loadRecord($uid);

// check password, if needed


// reject start if has current attempt, time limit exipred, and is kick out
/* TODO: Need to figure out expected behavior here.
if ($assess_record->hasActiveAttempt() &&
  $assess_info->getSetting('timelimit') > 0 &&
  $assess_info->getSetting('timelimit_type') == 'kick_out' &&
  $assess_record->getTimeLimitExpires() < $now
) {
  return '{error: "timelimit_expired"}';
}
*/

// add any new group members, if allowed


// if there's no active assessment attempt, generate one
if (!$assess_record->hasActiveAttempt()) {
  // check to make sure we're allowed to generate one
}

// grab any assessment info fields that may have updated:
// has_active_attempt, timelimit_expires,
// prev_attempts (if we just closed out a version?)
// and those not yet loaded:
// help_features, intro, resources, video_id, category_urls


// grab question settings data

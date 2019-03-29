<?php
/*
 * IMathAS: Question Load Endpoint
 * (c) 2019 David Lippman
 *
 * Method: POST
 * Query string parameters:
 *  aid   Assessment ID
 *  cid   Course ID
 *  uid   Optional. Only allowed for teachers, to load student's assessment
 *
 * POST parameters:
 *  qn                  Question number to display
 *  regen  Optional     Set true to initiate a try-a-similar-question regen
 *
 * Returns: partial assessInfo object, mainly including the desired question
 *          object, but may also update some assessInfo fields
 */

$init_skip_csrfp = true; // TODO: get CSRFP to work
require_once("../init.php");
require_once("./common_start.php");
require_once("./AssessInfo.php");
require_once("./AssessRecord.php");
require_once('./AssessUtils.php');

header('Content-Type: application/json; charset=utf-8');

// validate inputs
check_for_required('GET', array('aid', 'cid'));
check_for_required('POST', array('qn'));
$cid = Sanitize::onlyInt($_GET['cid']);
$aid = Sanitize::onlyInt($_GET['aid']);
if ($isteacher && isset($_GET['uid'])) {
  $uid = Sanitize::onlyInt($_GET['uid']);
} else {
  $uid = $userid;
}
$qn = Sanitize::onlyInt($_POST['qn']);
$doRegen = !empty($_POST['regen']);
$jumpToAnswer = !empty($_POST['jumptoans']);

$now = time();

// load settings including question info
$assess_info = new AssessInfo($DBH, $aid, $cid, false);
$assess_info->loadException($uid, $isstudent, $studentinfo['latepasses'] , $latepasshrs, $courseenddate);
if ($isstudent) {
  $assess_info->applyTimelimitMultiplier($studentinfo['timelimitmult']);
}

// reject if not available
if ($assess_info->getSetting('available') === 'practice' && !empty($_POST['practice'])) {
  $in_practice = true;
} else if ($assess_info->getSetting('available') === 'yes') {
  $in_practice = false;
} else {
  echo '{"error": "not_avail"}';
  exit;
}

// load user's assessment record
$assess_record = new AssessRecord($DBH, $assess_info, $in_practice);
$assess_record->loadRecord($uid);

// make sure a record exists
if (!$assess_record->hasRecord() || !$assess_record->hasActiveAttempt()) {
  echo '{"error": "not_ready"}';
  exit;
}

// reject start if has current attempt, time limit expired, and is kick out
if (!$in_practice &&
  $assess_record->hasActiveAttempt() &&
  $assess_info->getSetting('timelimit') > 0 &&
  $assess_info->getSetting('timelimit_type') == 'kick_out' &&
  $assess_record->getTimeLimitExpires() < $now
) {
  echo '{"error": "timelimit_expired"}';
  exit;
}

// if there's no active assessment attempt, exit
if (!$assess_record->hasUnsubmittedAttempt()) {
  echo '{"error": "not_ready"}';
  exit;
}

// If in practice, now we overwrite settings
if ($in_practice) {
  $assess_info->overridePracticeSettings();
}

// grab any assessment info fields that may have updated:
// has_active_attempt, timelimit_expires,
// prev_attempts (if we just closed out a version?)
// and those not yet loaded:
// help_features, intro, resources, video_id, category_urls
$include_from_assess_info = array(
  'available', 'startdate', 'enddate', 'original_enddate', 'submitby',
  'extended_with', 'allowed_attempts', 'latepasses_avail', 'latepass_extendto'
);
$assessInfoOut = $assess_info->extractSettings($include_from_assess_info);
//get attempt info
$assessInfoOut['has_active_attempt'] = $assess_record->hasActiveAttempt();
//get time limit expiration of current attempt, if appropriate
if ($assessInfoOut['has_active_attempt'] && $assessInfoOut['timelimit'] > 0) {
  $assessInfoOut['timelimit_expires'] = $assess_record->getTimeLimitExpires();
}

// get current question version
$qid = $assess_record->getQuestionId($qn);

// load question settings and code
$assess_info->loadQuestionSettings(array($qid), true);


// Try a Similar Question, if requested
if ($doRegen) {
    if ($assess_record->canRegenQuestion($qn, $qid)) {
      $qid = $assess_record->buildNewQuestionVersion($qn, $qid);
      $assess_info->loadQuestionSettings(array($qid), true);
    } else {
      echo '{"error": "out_of_regens"}';
      exit;
    }
}

// jump to answer, if requested
if ($jumpToAnswer) {
  $assess_record->doJumpToAnswer($qn, $qid);
}

// grab question settings data with HTML
$showscores = $assess_info->showScoresDuring();
$assessInfoOut['questions'] = array(
  $qn => $assess_record->getQuestionObject($qn, $showscores, true, true)
);

// save record if needed
$assess_record->saveRecordIfNeeded();

//output JSON object
echo json_encode($assessInfoOut);

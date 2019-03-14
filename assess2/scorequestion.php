<?php
/*
 * IMathAS: Question Scoring Endpoint
 * (c) 2019 David Lippman
 *
 * Method: POST
 * Query string parameters:
 *  aid   Assessment ID
 *  cid   Course ID
 *  uid   Optional. Only allowed for teachers, to load student's assessment
 *
 * POST parameters:
 *  toscoreqn           Question number to score, or array of question numbers
 *  lastloaded          Time the question was last displayed
 *  autosave            Set true if autosave (no scoring)
 *
 * Returns: partial assessInfo object, mainly including the scored question
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
check_for_required('POST', array('toscoreqn', 'lastloaded'));
$cid = Sanitize::onlyInt($_GET['cid']);
$aid = Sanitize::onlyInt($_GET['aid']);
if ($isteacher && isset($_GET['uid'])) {
  $uid = Sanitize::onlyInt($_GET['uid']);
} else {
  $uid = $userid;
}
// if toscoreqn is not an array, make it into one
if (is_array($_POST['toscoreqn'])) {
  $qns = array_map('Sanitize::onlyInt', $_POST['toscoreqn']);
  $lastloaded = array_map('Sanitize::onlyInt', $_POST['lastloaded']);
} else if ($_POST['toscoreqn'] == -1) {
  $qns = array();
  $lastloaded = array(Sanitize::onlyInt($_POST['lastloaded']));
} else {
  $qns = array(Sanitize::onlyInt($_POST['toscoreqn']));
  $lastloaded = array(Sanitize::onlyInt($_POST['lastloaded']));
}
$end_attempt = !empty($_POST['endattempt']);
$autosave = !empty($_POST['autosave']);  // TODO!!


$now = time();

// load settings
$assess_info = new AssessInfo($DBH, $aid, $cid, false);
$assess_info->loadException($uid, $isstudent, $studentinfo['latepasses'] , $latepasshrs, $courseenddate);
if ($isstudent) {
  $assess_info->applyTimelimitMultiplier($studentinfo['timelimitmult']);
}

// load user's assessment record
$assess_record = new AssessRecord($DBH, $assess_info);
$assess_record->loadRecord($uid);

// reject if not available
if ($assess_info->getSetting('available') === 'practice' && !empty($_POST['practice'])) {
  $in_practice = true;
} else if ($assess_info->getSetting('available') === 'yes') {
  $in_practice = false;
} else {
  echo '{"error": "not_avail"}';
  exit;
}

// make sure a record exists
if (!$assess_record->hasRecord() || !$assess_record->hasActiveAttempt($in_practice)) {
  echo '{"error": "not_ready"}';
  exit;
}

// reject start if has current attempt, time limit expired, and is kick out
if (!$in_practice &&
  $assess_record->hasActiveAttempt() &&
  $assess_info->getSetting('timelimit') > 0 &&
  $assess_info->getSetting('timelimit_type') == 'kick_out' &&
  $assess_record->getTimeLimitExpires() < $now + 5  // TODO: adjust
) {
  echo '{"error": "timelimit_expired"}';
  exit;
}

// if there's no active assessment attempt, exit
if (!$assess_record->hasUnsubmittedAttempt($in_practice)) {
  echo '{"error": "not_ready"}';
  exit;
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

if (count($qns) > 0) {
  // get current question version ids
  $qids = $assess_record->getQuestionIds($qns, $in_practice);

  // load question settings and code
  $assess_info->loadQuestionSettings($end_attempt ? 'all' : $qids, true);

  // TODO:  Verify confirmation values (to ensure it hasn't been submitted since)


  // Record a submission
  $submission = $assess_record->addSubmission($now);

  // Score the questions
  foreach ($qns as $qn) {
    $parts_to_score = $assess_record->isSubmissionAllowed($qn, $qids[$qn], $in_practice);
    $assess_record->scoreQuestion($qn, $submission, $parts_to_score, $in_practice);
  }

  // Update lastchange and status
  // TODO

  // Recalculate scores
  $assess_record->reTotalAssess($in_practice);
  // TODO: if by-question and all questions attempted, update status
} else {
  $assess_info->loadQuestionSettings('all', false);
}

if ($end_attempt) {
  $assess_record->setStatus(false, true, $in_practice);
}

// Record record
//$assess_record->saveRecord(!$in_practice, $in_practice);

if ($end_attempt) {
  // grab all questions settings and scores, based on end-of-assessment settings
  $showscores = $assess_info->showScoresAtEnd();
  $reshowQs = $assess_info->reshowQuestionsAtEnd();
  $assessInfoOut['questions'] = $assess_record->getAllQuestionObjects($in_practice, $showscores, true, $reshowQs);
  $assessInfoOut['score'] = $assess_record->getAttemptScore($in_practice);
} else {
  // grab question settings data with HTML
  $showscores = $assess_info->showScoresDuring();
  $assessInfoOut['questions'] = array();
  foreach ($qns as $qn) {
    $assessInfoOut['questions'][$qn] = $assess_record->getQuestionObject($qn, $in_practice, $showscores, true, true);
  }
}

// save record if needed
$assess_record->saveRecordIfNeeded(!$in_practice, $in_practice);

//output JSON object
echo json_encode($assessInfoOut);

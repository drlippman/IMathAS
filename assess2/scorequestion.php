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

//error_reporting(E_ALL);

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
if ($_POST['toscoreqn'] == -1 || $_POST['toscoreqn'] === '') {
  $qns = array();
  $lastloaded = array(Sanitize::onlyInt($_POST['lastloaded']));
  $timeactive = array();
  $nonblank = array();
  $verification = array();
} else {
  $qns = array_map('Sanitize::onlyInt', explode(',', $_POST['toscoreqn']));
  $lastloaded = array_map('Sanitize::onlyInt', explode(',', $_POST['lastloaded']));
  $timeactive = array_map('Sanitize::onlyInt', explode(',', $_POST['timeactive']));
  $nonblank = json_decode($_POST['nonblank'], true);
  $verification = json_decode($_POST['verification'], true);
}
$end_attempt = !empty($_POST['endattempt']);

$now = time();

// load settings
$assess_info = new AssessInfo($DBH, $aid, $cid, false);
$assess_info->loadException($uid, $isstudent, $studentinfo['latepasses'] , $latepasshrs, $courseenddate);
if ($isstudent) {
  $assess_info->applyTimelimitMultiplier($studentinfo['timelimitmult']);
}

// reject if not available
if ($assess_info->getSetting('available') === 'practice' && !empty($_POST['practice'])) {
  $in_practice = true;
  $end_attempt = false;
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
  $now > $assess_record->getTimeLimitExpires() + 5  // TODO: adjust
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
  'extended_with', 'allowed_attempts', 'latepasses_avail', 'latepass_extendto',
  'showscores', 'timelimit'
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
  $qids = $assess_record->getQuestionIds($qns);

  // load question settings and code
  $assess_info->loadQuestionSettings($end_attempt ? 'all' : $qids, true);

  // Verify confirmation values (to ensure it hasn't been submitted since)
  if (!$assess_record->checkVerification($verification)) {
    // grab question settings data with HTML
    $showscores = $assess_info->showScoresDuring();
    $assessInfoOut['questions'] = array();
    foreach ($qns as $qn) {
      $assessInfoOut['questions'][$qn] = $assess_record->getQuestionObject($qn, $showscores, true, true);
    }
    $assessInfoOut['error'] = "already_submitted";
    echo json_encode($assessInfoOut);
    exit;
  }

  // Record a submission
  $submission = $assess_record->addSubmission($now);

  // Score the questions
  foreach ($qns as $k=>$qn) {
    if (!isset($timeactive[$k])) {
      $timeactive[$k] = 0;
    }
    $parts_to_score = $assess_record->isSubmissionAllowed($qn, $qids[$qn]);
    // only score the non-blank ones
    foreach ($parts_to_score as $pn=>$v) {
      if ($v === true && !in_array($pn, $nonblank[$qn])) {
        $parts_to_score[$pn] = false;
      }
    }
    $assess_record->scoreQuestion($qn, $timeactive[$k], $submission, $parts_to_score);
  }

  // Update lastchange
  $assess_record->setLastChange($now);
  // update status if all questions answered
  // TODO

  // Recalculate scores
  $assess_record->reTotalAssess($qns);
  // TODO: if by-question and all questions attempted, update status
} else {
  $assess_info->loadQuestionSettings('all', false);
}

if ($end_attempt) {
  // sets assessment attempt as submitted and updates status
  $assess_record->setStatus(false, true);
} else if ($assessInfoOut['submitby'] == 'by_question') {
  // checks to see if all questions are attempted and updates status
  $assess_record->checkByQuestionStatus();
}

// Record record
$assess_record->saveRecord();

if ($end_attempt) {
  // grab all questions settings and scores, based on end-of-assessment settings
  $showscores = $assess_info->showScoresAtEnd();
  $reshowQs = $assess_info->reshowQuestionsAtEnd();
  $assessInfoOut['questions'] = $assess_record->getAllQuestionObjects($showscores, true, $reshowQs, 'scored');
  $assessInfoOut['score'] = $assess_record->getAttemptScore();
  $totalScore = $assessInfoOut['score'];
  $assessInfoOut['has_active_attempt'] = false;

  //get prev attempt info
  $assessInfoOut['prev_attempts'] = $assess_record->getSubmittedAttempts();
  $assessInfoOut['scored_attempt'] = $assess_record->getScoredAttempt();

  if ($assessInfoOut['submitby'] == 'by_question') {
    $assessInfoOut['can_retake'] = false;
  } else {
    $assessInfoOut['can_retake'] = (count($assessInfoOut['prev_attempts']) < $assessInfoOut['allowed_attempts']);
  }

  // get endmsg
  if ($assessInfoOut['showscores'] != 'none') {
    $assessInfoOut['endmsg'] = AssessUtils::getEndMsg(
      $assess_info->getSetting('endmsg'),
      $totalScore,
      $assess_info->getSetting('points_possible')
    );
  }

} else {
  // grab question settings data with HTML
  $showscores = $assess_info->showScoresDuring();
  $assessInfoOut['questions'] = array();
  foreach ($qns as $qn) {
    $assessInfoOut['questions'][$qn] = $assess_record->getQuestionObject($qn, $showscores, true, true);
  }
}

// save record if needed
$assess_record->saveRecordIfNeeded();

//output JSON object
echo json_encode($assessInfoOut);

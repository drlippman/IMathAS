<?php
/*
 * IMathAS: Assessment after-the-fact submission endpoint
 * (c) 2019 David Lippman
 *
 * Calling this allows submitting a past-due or past-timelimit
 * assessment attempt.
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
// is teacher/tutor and not acting as student
$isTeacherPreview = ($canViewAll && $uid == $userid);

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

if ($isTeacherPreview) {
    $assess_record->setIsTeacherPreview(true); // disables saving student-only data
}

// grab all questions settings
$assess_info->loadQuestionSettings('all', false);

// if have active scored record end it
if ($assess_record->hasActiveAttempt()) {
  $assess_record->scoreAutosaves();
  $assess_record->setStatus(false, true);
  // Recalculate scores based on submitted assessment.
  // Since we already retotaled for newly submitted questions, we can
  // just reuse existing question scores
  $assess_record->reTotalAssess(array());
  $assess_record->saveRecord();
} else {
  echo '{"error": "no_active_attempt"}';
  exit;
}

// update LTI grade
$assess_record->updateLTIscore();

// grab any assessment info fields that may have updated:
$include_from_assess_info = array(
  'available', 'startdate', 'enddate', 'original_enddate', 'submitby',
  'extended_with', 'allowed_attempts', 'showscores', 'timelimit', 'points_possible'
);
$assessInfoOut = $assess_info->extractSettings($include_from_assess_info);

// grab all questions scores, based on end-of-assessment settings
$showscores = $assess_info->showScoresAtEnd();
$reshowQs = $assess_info->reshowQuestionsAtEnd();
$assessInfoOut['questions'] = $assess_record->getAllQuestionObjects($showscores, true, $reshowQs, 'scored');
$assessInfoOut['score'] = $assess_record->getAttemptScore();
$totalScore = $assessInfoOut['score'];
$assessInfoOut['has_active_attempt'] = false;
$assessInfoOut['has_unsubmitted_scored'] = false;

//get prev attempt info
if ($assessInfoOut['submitby'] == 'by_assessment') {
  $showPrevAttemptScores = ($assessInfoOut['showscores'] != 'none');
  $assessInfoOut['prev_attempts'] = $assess_record->getSubmittedAttempts($showPrevAttemptScores);
  if ($showPrevAttemptScores) {
    $assessInfoOut['scored_attempt'] = $assess_record->getScoredAttempt();
  }
}

if ($assessInfoOut['submitby'] == 'by_question') {
  $assessInfoOut['can_retake'] = false;
} else {
  $assessInfoOut['can_retake'] = (count($assessInfoOut['prev_attempts']) < $assessInfoOut['allowed_attempts']);
}

// get endmsg
$assessInfoOut['endmsg'] = AssessUtils::getEndMsg(
    $assess_info->getSetting('endmsg'),
    $totalScore,
    $assess_info->getSetting('points_possible')
);

$assessInfoOut['newexcused'] = $assess_record->get_new_excused();

//prep date display
prepDateDisp($assessInfoOut);

//output JSON object
echo json_encode($assessInfoOut, JSON_INVALID_UTF8_IGNORE);

<?php
/*
 * IMathAS: Question autosaving Endpoint
 * (c) 2019 David Lippman
 *
 * Method: POST
 * Query string parameters:
 *  aid   Assessment ID
 *  cid   Course ID
 *  uid   Optional. Only allowed for teachers, to load student's assessment
 *
 * POST parameters:
 *  tosaveqn           stringified json, keyed by qn, value is array of part numbers
 *  lastloaded          stringified json keyed by qn
 *
 * Returns: partial assessInfo object, mainly including the scored question
 *          object, but may also update some assessInfo fields
 */


$no_session_handler = 'json_error';
require_once("../init.php");
require_once("./common_start.php");
require_once("./AssessInfo.php");
require_once("./AssessRecord.php");
require_once('./AssessUtils.php');

//error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

// validate inputs
check_for_required('GET', array('aid', 'cid'));
$cid = Sanitize::onlyInt($_GET['cid']);
$aid = Sanitize::onlyInt($_GET['aid']);
if ($isActualTeacher && isset($_GET['uid'])) {
  $uid = Sanitize::onlyInt($_GET['uid']);
} else {
  $uid = $userid;
}
$qns = null;

if (isset($_POST['autosave-tosaveqn'])) {
    check_for_required('POST', array('autosave-tosaveqn', 'autosave-lastloaded'));
    $qns = json_decode($_POST['autosave-tosaveqn'], true);
    $lastloaded = json_decode($_POST['autosave-lastloaded'], true);
    $verification = json_decode($_POST['autosave-verification'], true);
    if ($_POST['autosave-timeactive'] == '') {
        $timeactive = [];
    } else {
        $timeactive = json_decode($_POST['autosave-timeactive'], true);
    }
} else if (isset($_POST['tosaveqn'])) { // deprecated
    check_for_required('POST', array('tosaveqn', 'lastloaded'));
    $qns = json_decode($_POST['tosaveqn'], true);
    $lastloaded = json_decode($_POST['lastloaded'], true);
    $verification = json_decode($_POST['verification'], true);
    if ($_POST['timeactive'] == '') {
        $timeactive = [];
    } else {
        $timeactive = json_decode($_POST['timeactive'], true);
    }
}

if ($qns === null || $lastloaded === null || $timeactive === null) {
  echo '{"error": "invalid_params"}';
  exit;
}

$now = time();

// load settings
$assess_info = new AssessInfo($DBH, $aid, $cid, false);
$assess_info->loadException($uid, $isstudent);
if ($isstudent) {
  $assess_info->applyTimelimitMultiplier($studentinfo['timelimitmult']);
}

$preview_all = ($canViewAll && !empty($_POST['preview_all']));

// reject if not available
if ($assess_info->getSetting('available') === 'practice' && !empty($_POST['practice'])) {
  $in_practice = true;
} else if ($assess_info->getSetting('available') === 'yes' && !empty($_POST['practice'])) {
  echo '{"error": "not_practice"}';
  exit;
} else if ($assess_info->getSetting('available') === 'yes' || $canViewAll) {
  $in_practice = false;
  if ($canViewAll) {
    $assess_info->overrideAvailable('yes', $uid!=$userid || $preview_all);
  }
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
  (($assess_info->getSetting('timelimit_type') == 'kick_out' &&
    $now > $assess_record->getTimeLimitExpires() + 10) ||
    ($assess_info->getSetting('timelimit_type') == 'allow_overtime' &&
    $now > $assess_record->getTimeLimitGrace() + 10))
) {
  echo '{"error": "timelimit_expired"}';
  exit;
}

// if there's no active assessment attempt, exit
if (!$assess_record->hasUnsubmittedAttempt()) {
  echo '{"error": "not_ready"}';
  exit;
}

list($qids,$toloadqids) = $assess_record->getQuestionIds(array_keys($qns));

// load question settings and code
$assess_info->loadQuestionSettings($toloadqids, false, false);

// If in practice, now we overwrite settings
if ($in_practice) {
  $assess_info->overridePracticeSettings();
}

// Verify confirmation values (to ensure it hasn't been submitted since)
if (!$assess_record->checkVerification($verification)) {
  // grab question settings data with HTML to update front-end
  $showscores = $assess_info->showScoresDuring();
  $assessInfoOut['questions'] = array();
  foreach ($qns as $qn=>$parts) {
    $assessInfoOut['questions'][$qn] = $assess_record->getQuestionObject($qn, $showscores, true, true);
  }
  $assessInfoOut['error'] = "already_submitted";
  echo json_encode($assessInfoOut, JSON_INVALID_UTF8_IGNORE);
  exit;
}

// autosave the requested parts
foreach ($qns as $qn=>$parts) {
  if (!isset($timeactive[$qn])) {
    $timeactive[$qn] = 0;
  }
  $ok_to_save = $assess_record->isSubmissionAllowed($qn, $qids[$qn], $parts);
  foreach ($parts as $part) {
    if ($ok_to_save === true || !empty($ok_to_save[$part])) {
      $assess_record->setAutoSave($now, $timeactive[$qn], $qn, $part);
    }
  }
  if (isset($_POST['sw' . $qn])) {  //autosaving work
    $assess_record->setAutoSave($now, $timeactive[$qn], $qn, 'work');
  }
}

// save record if needed
$assess_record->saveRecordIfNeeded();

// get settings for LTI if needed
$assess_info->loadLTIMsgPosts($userid, $canViewAll);

$include_from_assess_info = array(
    'lti_showmsg', 'lti_msgcnt', 'lti_forumcnt'
);
$out = $assess_info->extractSettings($include_from_assess_info);

//output JSON object
$out['autosave'] = 'done';
if ($assess_record->hasActiveAttempt() && $assess_info->getSetting('timelimit') > 0) {
    $out['timelimit_expiresin'] = $assess_record->getTimeLimitExpires() - $now;
    $out['timelimit_gracein'] = max($assess_record->getTimeLimitGrace() - $now, 0);
}
echo json_encode($out);

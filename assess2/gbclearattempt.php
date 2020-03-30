<?php
/*
 * IMathAS: Gradebook - clear attempt from scored record
 * (c) 2019 David Lippman
 *
 * Method: POST
 * Query string parameters:
 *  aid   Assessment ID
 *  cid   Course ID
 *  uid   Student's User ID
 *
 * POST variables:
 *  type:   'all', 'attempt', or 'qver'
 *  keepver: true to keep ques/seed, false to remove completely
 *  aver:  assessment version (for 'attempt' or 'qver')
 *  qn:    question number (for 'qver')
 *  qver:  question version (for 'qver')
 *
 * Returns: success or error message
 */


$no_session_handler = 'json_error';
require_once("../init.php");
require_once("./common_start.php");
require_once("./AssessInfo.php");
require_once("./AssessRecord.php");
require_once('./AssessUtils.php');

header('Content-Type: application/json; charset=utf-8');

if (!$isActualTeacher && !$istutor) {
  echo '{"error": "no_access"}';
  exit;
}
// validate inputs
check_for_required('GET', array('aid', 'cid', 'uid'));
check_for_required('POST', array('type', 'keepver'));
$cid = Sanitize::onlyInt($_GET['cid']);
$aid = Sanitize::onlyInt($_GET['aid']);
$uid = Sanitize::onlyInt($_GET['uid']);
$type = Sanitize::simpleString($_POST['type']);
$keepver = Sanitize::onlyInt($_POST['keepver']);
if ($type == 'attempt' || $type == 'qver') {
  $aver = Sanitize::onlyInt($_POST['aver']);
}
if ($type == 'qver') {
  $qn = Sanitize::onlyInt($_POST['qn']);
  $qver = Sanitize::onlyInt($_POST['qver']);
}

// load settings without questions
$assess_info = new AssessInfo($DBH, $aid, $cid, false);
if ($istutor) {
  $tutoredit = $assess_info->getSetting('tutoredit');
  if ($tutoredit != 1) { // no Access for editing scores
    echo '{"error": "no_access"}';
    exit;
  }
}
// get question point values for retotal later
$assess_info->loadQuestionSettings('all', false);

//load user's assessment record - start with scored data
$assess_record = new AssessRecord($DBH, $assess_info, false);
$assess_record->loadRecord($uid);
if (!$assess_record->hasRecord()) {
  echo '{"error": "invalid_record"}';
  exit;
}

if ($type == 'all' && $keepver == 0) {
  $stm = $DBH->prepare('DELETE FROM imas_assessment_records WHERE assessmentid=? AND userid=?');
  $stm->execute(array($aid, $uid));
  // update LTI grade
  $lti_sourcedid = $assess_record->getLTIsourcedId();
  if (strlen($lti_sourcedid) > 1) {
    require_once("../includes/ltioutcomes.php");
    updateLTIgrade('delete',$lti_sourcedid,$aid,$uid);
  }
  echo '{"success": "saved"}';
  exit;
} else if ($type == 'all' && $keepver == 1) {
  $assess_record->gbClearAttempts($type, $keepver);
} else if ($type == 'attempt') {
  $replacedDeleted = $assess_record->gbClearAttempts($type, $keepver, $aver);
} else if ($type == 'qver') {
  $replacedDeleted = $assess_record->gbClearAttempts($type, $keepver, $aver, $qn, $qver);
} else if ($type == 'practiceview') {
  $stm = $DBH->prepare("DELETE FROM imas_content_track WHERE typeid=:typeid AND userid=:userid AND (type='gbviewasid' OR type='gbviewassess' OR type='assessreview')");
  $stm->execute(array(
    ':typeid' => $aid,
    ':userid' => $uid
  ));
}
// recalculated totals based on removed attempts
$assess_record->reTotalAssess();

// get gbscore and scored_version
$assessInfoOut = $assess_record->getGbScore();
$assessInfoOut['replaced_deleted'] = $replacedDeleted;
if ($type == 'attempt' && ($replacedDeleted || $keepver == 1)) {
  $assessInfoOut['newver'] = $assess_record->getGbAssessVerData($aver, true);
} else if ($type == 'qver') {
  $by_question = ($assess_info->getSetting('submitby') === 'by_question');
  $assessInfoOut['assessinfo'] = $assess_record->getGbAssessVerData($aver, false);
  // get scored version
  $assessInfoOut['qinfo'] = $assess_record->getGbQuestionInfo($qn, $aver);

  if ($replacedDeleted || $keepver == 1) {
    $assessInfoOut['newver'] = $assess_record->getGbQuestionVersionData($qn, true, $by_question ? $qver : $aver);
  }
} else if ($type == 'practiceview') {
  $assessInfoOut['latepass_blocked_by_practice'] = false;
}

$assess_record->saveRecord();

// update LTI grade
$lti_sourcedid = $assess_record->getLTIsourcedId();
if (strlen($lti_sourcedid) > 1) {
  require_once("../includes/ltioutcomes.php");
  calcandupdateLTIgrade($lti_sourcedid,$aid,$uid,$assessInfoOut['gbscore'],true);
}

//output JSON object
echo json_encode($assessInfoOut);
exit;

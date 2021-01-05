<?php
/*
 * IMathAS: Gradebook - save score overrides from gbviewassess
 * (c) 2019 David Lippman
 *
 * Method: POST
 * Query string parameters:
 *  aid   Assessment ID
 *  cid   Course ID
 *  uid   Student's User ID
 *
 * POST variables:
 *  scores    JSON string with keys of form av-qn-qv-pn, or gen
 *  feedback  JSON string with keys of the form av-g, or av-qn-qv
 *
 * Returns: success or error message
 */


$no_session_handler = 'json_error';
require_once("../init.php");
require_once("./common_start.php");
require_once("./AssessInfo.php");
require_once("./AssessRecord.php");
require_once('./AssessUtils.php');
require_once('../includes/TeacherAuditLog.php');

header('Content-Type: application/json; charset=utf-8');

if (!$isActualTeacher && !$istutor) {
  echo '{"error": "no_access"}';
  exit;
}
//validate inputs
check_for_required('GET', array('aid', 'cid', 'uid'));
check_for_required('POST', array('scores', 'feedback'));
$cid = Sanitize::onlyInt($_GET['cid']);
$aid = Sanitize::onlyInt($_GET['aid']);
$uid = Sanitize::onlyInt($_GET['uid']);
$scores = json_decode($_POST['scores'], true);
$feedbacks = json_decode($_POST['feedback'], true);

//load settings without questions
$assess_info = new AssessInfo($DBH, $aid, $cid, false);
if ($istutor) {
  $tutoredit = $assess_info->getSetting('tutoredit');
  if ($tutoredit != 1) { // no Access for editing scores
    echo '{"error": "no_access"}';
    exit;
  }
}

// get user info
$query = 'SELECT iu.FirstName, iu.LastName, istu.latepass, istu.timelimitmult ';
$query .= 'FROM imas_users AS iu JOIN imas_students AS istu ON istu.userid=iu.id ';
$query .= 'WHERE iu.id=? AND istu.courseid=?';
$stm = $DBH->prepare($query);
$stm->execute(array($uid, $cid));
$studata = $stm->fetch(PDO::FETCH_ASSOC);
if ($studata === false) {
  echo '{"error": "invalid_uid"}';
  exit;
}
$assess_info->loadException($uid, true, $studata['latepass'], $latepasshrs, $courseenddate);
$assess_info->applyTimelimitMultiplier($studata['timelimitmult']);

// load question settings
$assess_info->loadQuestionSettings('all', false, false);

//load user's assessment record - start with scored data
$assess_record = new AssessRecord($DBH, $assess_info, false);
$assess_record->loadRecord($uid);
$assess_record->setInGb(true);
if (!$assess_record->hasRecord()) {
  echo '{"error": "invalid_record"}';
  exit;
}

$changes = $assess_record->setGbScoreOverrides($scores);
$assess_record->setGbFeedbacks($feedbacks);
$assess_record->saveRecord();

$out = $assess_record->getGbScore();
$out['assess_info'] = $assess_record->getGbAssessScoresAndQVersions();
$out['newscores'] = $assess_record->getScoresAfterOverrides($scores);

if (!empty($changes)) {
  TeacherAuditLog::addTracking(
    $cid,
    "Change Grades",
    $aid,
    array(
      'stu'=>$uid,
      'overrides'=>$changes
    )
  );
}

// update LTI grade
$assess_record->updateLTIscore();

//prep date display
prepDateDisp($assessInfoOut);

echo json_encode($out, JSON_INVALID_UTF8_IGNORE);

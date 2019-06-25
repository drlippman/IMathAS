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

// load question settings
$assess_info->loadQuestionSettings('all', false);

//load user's assessment record - start with scored data
$assess_record = new AssessRecord($DBH, $assess_info, false);
$assess_record->loadRecord($uid);
if (!$assess_record->hasRecord()) {
  echo '{"error": "invalid_record"}';
  exit;
}

$assess_record->setGbScoreOverrides($scores);
$assess_record->setGbFeedbacks($feedbacks);
$assess_record->saveRecord();

$out = $assess_record->getGbScore();
$out['assess_info'] = $assess_record->getGbAssessScoresAndQVersions();
$out['newscores'] = $assess_record->getScoresAfterOverrides($scores);

// update LTI grade
$lti_sourcedid = $assess_record->getLTIsourcedId();
if (strlen($lti_sourcedid) > 1) {
  require_once("../includes/ltioutcomes.php");
  calcandupdateLTIgrade($lti_sourcedid,$aid,$out['gbscore'],true);
}

//prep date display
prepDateDisp($assessInfoOut);

echo json_encode($out);

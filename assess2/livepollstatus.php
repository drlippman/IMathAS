<?php
/*
 * IMathAS: Assessment endpoint for teacher updates to livepoll status
 * (c) 2019 David Lippman
 *
 *
 * Method: POST
 * Query string parameters:
 *  aid   Assessment ID
 *  cid   Course ID
 *
 * POST
 *  curquestion   The question id
 *  curstate      The question state
 *  forceregen    (optional) set true to generate new seed
 *
 * Returns: partial assessInfo object, containing livepoll_status
 *  If selecting a new question, also returns HTML for that question
 */


$no_session_handler = 'json_error';
require_once("../init.php");
require_once("./common_start.php");
require_once("./AssessInfo.php");
require_once("./AssessRecord.php");
require_once('./AssessUtils.php');

header('Content-Type: application/json; charset=utf-8');

check_for_required('GET', array('aid', 'cid'));
check_for_required('POST', array('newquestion', 'newstate'));
$cid = Sanitize::onlyInt($_GET['cid']);
$aid = Sanitize::onlyInt($_GET['aid']);
$uid = $userid;
$newQuestion = Sanitize::onlyInt($_POST['newquestion']);
$qn = $newQuestion - 1;
$newState = Sanitize::onlyInt($_POST['newstate']);
$timelimit = Sanitize::onlyInt($_POST['timelimit'] ?? 0);

// this page is only for teachers
if (!$isteacher) {
  echo '{"error": "teacher_only"}';
  exit;
}

$now = time();

// load settings
$assess_info = new AssessInfo($DBH, $aid, $cid, false);
$assess_info->loadException($uid, $isstudent, $studentinfo['latepasses'] , $latepasshrs, $courseenddate);

// load user's assessment record - always operating on scored attempt here
$assess_record = new AssessRecord($DBH, $assess_info, false);
$assess_record->loadRecord($uid);


// grab any assessment info fields that may have updated:
$include_from_assess_info = array(
  'available', 'startdate', 'enddate', 'original_enddate', 'submitby',
  'extended_with', 'allowed_attempts', 'latepasses_avail', 'latepass_extendto',
  'showscores', 'timelimit', 'points_possible'
);
$assessInfoOut = $assess_info->extractSettings($include_from_assess_info);

// get current livepoll status
$stm = $DBH->prepare("SELECT curquestion,curstate,seed,startt FROM imas_livepoll_status WHERE assessmentid=:assessmentid");
$stm->execute(array(':assessmentid'=>$aid));
$livepollStatus = array_map('intval', $stm->fetch(PDO::FETCH_ASSOC));

if ($newState > 0) {

}

// If new question, or if previous state was 0, then we're
// preloading a new question
// Set the state and load the question HTML
// No need to send anything out to livepoll server for this
if ($newQuestion !== $livepollStatus['curquestion'] ||
  $livepollStatus['curstate'] === 0 ||
  !empty($_POST['forceregen'])
) {
  if ($newState == 2) {
      // force the newstate to be 1; shouldn't jump right to open
      $newState = 1;
  }

  // look up question HTML. Also grab seed
  // get current question version
  list($qid, $qidstoload) = $assess_record->getQuestionId($qn);
  // do regen if requested
  if (!empty($_POST['forceregen'])) {
    // need to temporarily override shuffle setting to bypass the
    // "all students same seed" setting
    $assess_info->overrideSetting('shuffle', 0);
    $qid = $assess_record->buildNewQuestionVersion($qn, $qid);
  }

  // load question settings and code
  $assess_info->loadQuestionSettings($qidstoload, true, false);

  // do not load question - question autoloader will handle that

  //TODO: ? pull any existing results (in case we refreshed)

  // extract seed
  $question_obj = $assess_record->getQuestionObject($qn, false, false, false);
  $seed = $question_obj['seed'];

  //set status
  $query = "UPDATE imas_livepoll_status SET ";
  $query .= "curquestion=?,curstate=?,seed=?,startt=?,curqid=? ";
  $query .= "WHERE assessmentid=?";
  $stm = $DBH->prepare($query);
  $stm->execute(array($newQuestion, $newState, $seed, 0, $qid, $aid));

  //output
  $assessInfoOut['livepoll_status'] = array(
    'curquestion' => $newQuestion,
    'curstate' => $newState,
    'seed' => $seed,
    'startt' => 0
  );
} else if ($newState === 2) {

  // load question seed
  list($qid, $qidstoload) = $assess_record->getQuestionId($qn);
  $assess_info->loadQuestionSettings($qidstoload, false, false);
  $question_obj = $assess_record->getQuestionObject($qn, false, false, false);
  $seed = $question_obj['seed'];

  // Opening the question for student input
  $query = "UPDATE imas_livepoll_status SET ";
  $query .= "curquestion=?,curstate=?,startt=?,curqid=? ";
  $query .= "WHERE assessmentid=?";
  $stm = $DBH->prepare($query);
  $stm->execute(array($newQuestion, $newState, $now, $qid, $aid));

  

  //output
  $assessInfoOut['livepoll_status'] = array(
    'curquestion' => $newQuestion,
    'curstate' => $newState,
    'seed' => $seed,
    'startt' => $now,
    'timelimit' => $timelimit
  );

  // call the livepoll server
  if (isset($CFG['GEN']['livepollpassword'])) {
    $livepollsig = base64_encode(sha1($aid . $qn . $seed. $CFG['GEN']['livepollpassword'] . $now, true));
  } else {
    $livepollsig = '';
  }
  $qs = Sanitize::generateQueryStringFromMap(array(
    'aid' => $aid,
    'qn' => $qn,
    'seed' => $seed,
    'startt' => $now . '-' . $timelimit,
    'now' => $now,
    'sig' => $livepollsig
  ));

  $result = file_get_contents('https://'.$CFG['GEN']['livepollserver'].':3000/startq?' . $qs);

  if ($result !== 'success') {
    echo '{"error": "'.Sanitize::encodeStringForDisplay($r).'"}';
    exit;
  }
} else if ($newState === 3 || $newState === 4) {
  // lookup question seed
  list($qid, $qidstoload) = $assess_record->getQuestionId($qn);
  $assess_info->loadQuestionSettings($qidstoload, false, false);
  $question_obj = $assess_record->getQuestionObject($qn, false, false, false);
  $seed = $question_obj['seed'];

  // Closing the question for student input
  $query = "UPDATE imas_livepoll_status SET ";
  $query .= "curquestion=?,curstate=?,startt=0,curqid=? ";
  $query .= "WHERE assessmentid=?";
  $stm = $DBH->prepare($query);
  $stm->execute(array($newQuestion, $newState, $qid, $aid));



  //output
  $assessInfoOut['livepoll_status'] = array(
    'curquestion' => $newQuestion,
    'curstate' => $newState,
    'seed' => $seed,
    'startt' => 0
  );

  // call the livepoll server
  if (isset($CFG['GEN']['livepollpassword'])) {
    $livepollsig = base64_encode(sha1($aid . $qn . $newState. $CFG['GEN']['livepollpassword'] . $now, true));
  } else {
    $livepollsig = '';
  }
  $qs = Sanitize::generateQueryStringFromMap(array(
    'aid' => $aid,
    'qn' => $qn,
    'newstate' => $newState,
    'now' => $now,
    'sig' => $livepollsig
  ));
  $result = file_get_contents('https://'.$CFG['GEN']['livepollserver'].':3000/stopq?' . $qs);

  if ($result !== 'success') {
    echo '{"error": "'.Sanitize::encodeStringForDisplay($r).'"}';
    exit;
  }
} else {
  echo '{"error": "livepoll_unknown"}';
  exit;
}

// save record if needed
$assess_record->saveRecordIfNeeded();

//prep date display
prepDateDisp($assessInfoOut);

//output JSON object
echo json_encode($assessInfoOut, JSON_INVALID_UTF8_IGNORE);

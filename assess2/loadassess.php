<?php
/*
 * IMathAS: Assessment launch endpoint
 * (c) 2019 David Lippman
 *
 * Method: GET
 * Query string parameters:
 *  aid   Assessment ID
 *  cid   Course ID
 *  uid   Optional. Only allowed for teachers, to load student's assessment
 *
 * Returns: assessInfo object
 */


$no_session_handler = 'json_error';
require_once("../init.php");
require_once("./common_start.php");
require_once("./AssessInfo.php");
require_once("./AssessRecord.php");
require_once('./AssessUtils.php');

header('Content-Type: application/json; charset=utf-8');

//validate inputs
check_for_required('GET', array('aid', 'cid'));
$cid = Sanitize::onlyInt($_GET['cid']);
$aid = Sanitize::onlyInt($_GET['aid']);
if ($isActualTeacher && isset($_GET['uid'])) {
  $uid = Sanitize::onlyInt($_GET['uid']);
} else {
  $uid = $userid;
}

$now = time();

// option to reset assessment entirely
if ($isActualTeacher && $uid == $userid && isset($_GET['reset'])) {
  require_once(__DIR__ . '/../includes/filehandler.php');
  deleteAssess2FilesOnUnenroll(array($uid), array($aid), array());
  $stm = $DBH->prepare("DELETE FROM imas_assessment_records WHERE userid=? AND assessmentid=?");
  $stm->execute(array($uid, $aid));
}

//load settings without questions
$assess_info = new AssessInfo($DBH, $aid, $cid, false);
$assess_info->loadException($uid, $isstudent, $studentinfo['latepasses'] , $latepasshrs, $courseenddate);
if ($isstudent) {
  $assess_info->applyTimelimitMultiplier($studentinfo['timelimitmult']);
}

//check to see if prereq has been met
if ($isstudent) {
  $assess_info->checkPrereq($uid);
}

//load user's assessment record - start with scored data
$assess_record = new AssessRecord($DBH, $assess_info, false);
$assess_record->loadRecord($uid);

//fields to extract from assess info for inclusion in output
$include_from_assess_info = array(
  'name', 'summary', 'available', 'startdate', 'enddate', 'enddate_in',
  'original_enddate', 'extended_with', 'timelimit', 'timelimit_type', 'points_possible',
  'submitby', 'displaymethod', 'groupmax', 'isgroup', 'showscores', 'viewingb', 'scoresingb',
  'can_use_latepass', 'allowed_attempts', 'retake_penalty', 'exceptionpenalty',
  'timelimit_multiplier', 'latepasses_avail', 'latepass_extendto', 'keepscore',
  'noprint', 'overtime_penalty', 'overtime_grace', 'reqscorename', 'reqscorevalue', 'attemptext'
);
$assessInfoOut = $assess_info->extractSettings($include_from_assess_info);

// livepoll server location, if needed
if ($assessInfoOut['displaymethod'] === 'livepoll') {
  $assessInfoOut['livepoll_server'] = $CFG['GEN']['livepollserver'];
}

// indicate if teacher or tutor user
$assessInfoOut['can_view_all'] = $canViewAll;
$assessInfoOut['is_teacher'] = $isteacher;
if ($istutor && $assess_info->getSetting('tutoredit') < 2) {
    // tutor can edit
    $assessInfoOut['tutor_gblinks'] = [
        $basesiteurl . '/course/isolateassessgrade.php?cid=' . $cid . '&aid=' . $aid,
        $basesiteurl . '/course/gb-itemanalysis2.php?cid=' . $cid . '&aid=' . $aid
    ];
}
if ($canViewAll && $userid !== $uid) {
  $assessInfoOut['view_as_stu'] = 1;
  $query = "SELECT iu.FirstName,iu.LastName FROM imas_users AS iu JOIN ";
  $query .= "imas_students AS istu ON istu.userid=iu.id WHERE ";
  $query .= "iu.id=? AND istu.courseid=?";
  $stm = $DBH->prepare($query);
  $stm->execute(array($uid, $cid));
  $row = $stm->fetch(PDO::FETCH_ASSOC);
  if ($row === false) {
    echo '{"error": "invalid_uid"}';
    exit;
  }
  $assessInfoOut['stu_fullname'] = $row['LastName'] . ', ' . $row['FirstName'];
}

// set userid
$assessInfoOut['userid'] = $uid;

//set is_lti and is_diag
$assessInfoOut['is_lti'] = isset($_SESSION['ltiitemtype']) && $_SESSION['ltiitemtype']==0;
$assessInfoOut['is_diag'] = isset($_SESSION['isdiag']);
if ($assessInfoOut['is_lti']) {
  $assessInfoOut['has_ltisourcedid'] = !empty($_SESSION['lti_lis_result_sourcedid'.$aid]);
}

//set has password
$assessInfoOut['has_password'] = $assess_info->hasPassword();

//get attempt info
$assessInfoOut['has_active_attempt'] = $assess_record->hasActiveAttempt();

// get time limit extension info 
if ($assessInfoOut['timelimit'] > 0 && !empty($assess_info->getSetting('timeext'))) {
    $assessInfoOut['timelimit_ext'] = $assess_info->getSetting('timeext');
    if (!$assessInfoOut['has_active_attempt'] && ($assess_record->getStatus()&64)==64 &&
      $assessInfoOut['timelimit_ext'] > 0
    ) {
        // has a previously submitted attempt; mark as active since we have a time 
        // limit extension available
        $assessInfoOut['has_active_attempt'] = true;
    }
}

//get time limit expiration of current attempt, if appropriate
if ($assessInfoOut['has_active_attempt'] && $assessInfoOut['timelimit'] > 0) {
  $assessInfoOut['timelimit_expires'] = $assess_record->getTimeLimitExpires();
  $assessInfoOut['timelimit_expiresin'] = $assessInfoOut['timelimit_expires'] - $now;
  $assessInfoOut['timelimit_grace'] = $assess_record->getTimeLimitGrace();
  $assessInfoOut['timelimit_gracein'] = max($assessInfoOut['timelimit_grace'] - $now, 0);
}

// if not available, see if there is an unsubmitted scored attempt
if ($assessInfoOut['available'] !== 'yes') {
  $assessInfoOut['has_unsubmitted_scored'] = $assess_record->hasUnsubmittedScored();
}

//get prev attempt info
$assessInfoOut['prev_attempts'] = $assess_record->getSubmittedAttempts();
$assessInfoOut['scored_attempt'] = $assess_record->getScoredAttempt();

if (!$assessInfoOut['has_active_attempt']) {
  if ($assessInfoOut['submitby'] == 'by_question') {
    $assessInfoOut['can_retake'] = (count($assessInfoOut['prev_attempts']) == 0);
  } else {
    $assessInfoOut['can_retake'] = (count($assessInfoOut['prev_attempts']) < $assessInfoOut['allowed_attempts']);
  }
}

$assessInfoOut['showwork_after'] = $assess_record->getShowWorkAfter();

// adjust output if time limit is expired in by_question mode
if ($assessInfoOut['has_active_attempt'] && $assessInfoOut['timelimit'] > 0 &&
  $assessInfoOut['submitby'] == 'by_question' &&
  time() > max($assessInfoOut['timelimit_grace'],$assessInfoOut['timelimit_expires']) && 
  intval($assess_info->getSetting('timeext')) <= 0
) {
  $assessInfoOut['has_active_attempt'] = false;
  $assessInfoOut['can_retake'] = false;
  if ($canViewAll && $userid == $uid) {
    $assessInfoOut['show_reset'] = true;
  }
  $assessInfoOut['pasttime'] = 1;
}

//load group members, if applicable
if ($assessInfoOut['isgroup'] > 0 && !$canViewAll) {
  list ($stugroupid, $groupmembers) = AssessUtils::getGroupMembers($uid, $assess_info->getSetting('groupsetid'));
  $assessInfoOut['group_members'] = array_values($groupmembers);
  $assessInfoOut['stugroupid'] = $stugroupid;
  if ($assessInfoOut['isgroup'] == 2) {
    if (count($assessInfoOut['group_members']) === 0) {
      // no group members yet - add self
      $assessInfoOut['group_members'][] = $userfullname;
    }
    //if can add group members, get available people
    $query = 'SELECT iu.id,iu.FirstName,iu.LastName FROM imas_users AS iu ';
    $query .= 'JOIN imas_students AS istu ON istu.userid=iu.id AND istu.courseid=? ';
    $query .= 'WHERE iu.id NOT IN (SELECT isgm.userid FROM imas_stugroupmembers AS isgm ';
    $query .= 'JOIN imas_stugroups as isg ON isg.id=isgm.stugroupid AND ';
    $query .= 'isg.groupsetid=?) AND iu.id<>? ORDER BY iu.FirstName, iu.LastName';
    $stm = $DBH->prepare($query);
    $stm->execute(array($cid, $assess_info->getSetting('groupsetid'), $uid));
    $assessInfoOut['group_avail'] = array();
    while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
      $assessInfoOut['group_avail'][$row['id']] = $row['FirstName'] . ' ' . $row['LastName'];
    }
  }
} else {
  if ($assessInfoOut['isgroup'] > 0) { // include for teachers to prevent errors
    $assessInfoOut['group_members'] = array();
  }
  $assessInfoOut['stugroupid'] = 0;
}

$assessInfoOut['userfullname'] = $userfullname;
if ($assessInfoOut['is_diag']) {
  $assessInfoOut['diag_userid'] = substr($username,0,strpos($username,'~'));
}

$assessInfoOut['useMQ'] = (!isset($_SESSION['userprefs']['useeqed']) ||
  $_SESSION['userprefs']['useeqed'] == 1);

// get excused info
if (!$canViewAll) {
    $stm = $DBH->prepare("SELECT id FROM imas_excused WHERE type='A' AND typeid=? AND userid=?");
    $stm->execute(array($aid,$uid));
    if ($stm->fetchColumn(0) !== false) {
        $assessInfoOut['excused'] = 1;
    }
}

//prep date display
prepDateDisp($assessInfoOut);

//output JSON object
echo json_encode($assessInfoOut, JSON_INVALID_UTF8_IGNORE);

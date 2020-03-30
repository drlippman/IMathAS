<?php
/*
 * IMathAS: Gradebook - Get initial assessment data for a student
 * (c) 2019 David Lippman
 *
 * Method: GET
 * Query string parameters:
 *  aid   Assessment ID
 *  cid   Course ID
 *  uid   Student's User ID
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
if ($isRealStudent || empty($_GET['uid'])) {
  $uid = $userid;
} else {
  $uid = Sanitize::onlyInt($_GET['uid']);
}

$now = time();

//load settings without questions
$assess_info = new AssessInfo($DBH, $aid, $cid, false);
if ($istutor) {
  $tutoredit = $assess_info->getSetting('tutoredit');
  if ($tutoredit == 2) { // no Access
    echo '{"error": "no_access"}';
    exit;
  }
}
$viewInGb = $assess_info->getSetting('viewingb');
if ($isstudent && $viewInGb == 'never') {
  echo '{"error": "no_access"}';
  exit;
}

// load question settings and code
$assess_info->loadQuestionSettings('all', true);

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
$assess_info->getLatePassBlockedByView();

//load user's assessment record - start with scored data
$assess_record = new AssessRecord($DBH, $assess_info, false);
$assess_record->loadRecord($uid);
$assess_record->setInGb(true);
if (!$assess_record->hasRecord()) {
  // if there's no record yet, and we're a teacher, create a record
  if ($isActualTeacher || ($istutor && $tutoredit == 1)) {
    $isGroup = $assess_info->getSetting('isgroup');
    if ($isGroup > 0) {
      if ($isGroup == 3) {
        $groupsetid = $assess_info->getSetting('groupsetid');
        list($stugroupid, $current_members) = AssessUtils::getGroupMembers($uid, $groupsetid);
        if ($stugroupid == 0) {
          // no group yet - can't do anything
          echo '{"error": "need_group"}';
          exit;
        }
      }
      // creating for group
      $assess_record->createRecord($current_members, $stugroupid, false, '');
    } else { // not group
      // creating for self
      $assess_record->createRecord(false, 0, false, '');
    }
  } else {
    echo '{"error": "invalid_record"}';
    exit;
  }
}

//fields to extract from assess info for inclusion in output
$include_from_assess_info = array(
  'name', 'submitby', 'enddate', 'can_use_latepass', 'hasexception',
  'original_enddate', 'extended_with', 'latepasses_avail', 'points_possible',
  'latepass_extendto', 'allowed_attempts', 'keepscore', 'timelimit', 'ver',
  'scoresingb', 'viewingb', 'latepass_blocked_by_practice'
);
$assessInfoOut = $assess_info->extractSettings($include_from_assess_info);

if ($isstudent && $viewInGb == 'after_due' && $now < $assessInfoOut['enddate']) {
  echo '{"error": "no_access"}';
  exit;
}

if ($isstudent) {
  $LPblockingView = true;
  // non-blocking views are ones where viewing work in GB was already allowed by settings
  if ($assessInfoOut['viewingb'] === 'immediately' ||
    ($assessInfoOut['submitby'] === 'by_assessment' && $assessInfoOut['viewingb'] == 'after_take')
  ) {
    // non-blocking views are ones where answers aren't showing
    $ansingb = $assess_info->getSetting('ansingb');
    if ($ansingb === 'never' || $ansingb === 'after_take') {
      $LPblockingView = false;
    } else if ($ansingb === 'after_due' && $now < $assessInfoOut['enddate']) {
      $LPblockingView = false;
    }
  }
  // log gradebook view
  $query = "INSERT INTO imas_content_track (userid,courseid,type,typeid,viewtime) VALUES ";
  $query .= "(:userid, :courseid, :type, :typeid, :viewtime)";
  $stm = $DBH->prepare($query);
  $stm->execute(array(':userid'=>$userid, ':courseid'=>$cid, ':typeid'=>$aid,
    ':type'=> $LPblockingView ? 'gbviewassess' : 'gbviewsafe', ':viewtime'=>$now));
}

// indicate whether teacher/tutor can edit scores or not
if ($isActualTeacher || ($istutor && $tutoredit == 1)) {
  $assessInfoOut['can_edit_scores'] = true;
  $assess_record->setTeacherInGb(true);
  // get rubrics
  $assessInfoOut['rubrics'] = array();
  $query = "SELECT id,rubrictype,rubric FROM imas_rubrics WHERE id IN
	 (SELECT DISTINCT rubric FROM imas_questions WHERE assessmentid=:assessmentid AND rubric>0)";
  $stm = $DBH->prepare($query);
  $stm->execute(array(':assessmentid'=>$aid));
  while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
    $assessInfoOut['rubrics'][$row['id']] = array(
      'type' => $row['rubrictype'],
      'data' => unserialize($row['rubric'])
    );
  }
} else {
  $assessInfoOut['can_edit_scores'] = false;
}

if (isset($CFG['GEN']['sendquestionproblemsthroughcourse'])) {
  $assessInfoOut['qerror_cid'] = $CFG['GEN']['sendquestionproblemsthroughcourse'];
}

// get student's assessment attempt metadata
$assessInfoOut = array_merge($assessInfoOut, $assess_record->getGbAssessMeta());

//get attempt info
$assessInfoOut['has_active_attempt'] = $assess_record->hasActiveAttempt();
//get time limit expiration of current attempt, if appropriate
if ($assessInfoOut['has_active_attempt'] && $assessInfoOut['timelimit'] > 0) {
  $assessInfoOut['timelimit_expires'] = $assess_record->getTimeLimitExpires();
}

// if not available, see if there is an unsubmitted scored attempt
if ($assessInfoOut['available'] !== 'yes') {
  $assessInfoOut['has_unsubmitted_scored'] = $assess_record->hasUnsubmittedScored();
}

$assessInfoOut['userfullname'] = $studata['LastName'].', '.$studata['FirstName'];

// get records of all previous attempts, as well as HTML for the scored versions
$assessInfoOut['assess_versions'] = $assess_record->getGbAssessData();
$assessInfoOut['has_practice'] = ($assess_record->getStatus()&16)>0;
if ($assessInfoOut['has_practice']) {
  $assessInfoOut['assess_versions'][] = array(
    'status' => 3
  );
}

$assessInfoOut['lti_sourcedid'] = $assess_record->getLTIsourcedId();

// generating answeights may have changed the record; save if needed
$assess_record->saveRecordIfNeeded();

//prep date display
prepDateDisp($assessInfoOut);

//output JSON object
echo json_encode($assessInfoOut, JSON_INVALID_UTF8_IGNORE);

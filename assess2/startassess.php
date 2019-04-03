<?php
/*
 * IMathAS: Assessment start endpoint
 * (c) 2019 David Lippman
 *
 * Method: POST
 * Query string parameters:
 *  aid   Assessment ID
 *  cid   Course ID
 *  uid   Optional. Only allowed for teachers, to load student's assessment
 *
 * POST parameters:
 *  password            Password for the assessment, if needed
 *  new_group_members   Comma separated list of userids to add to the group, if allowed
 *  practice            Set true if starting practice
 *
 * Returns: partial assessInfo object, adding question data if launch successful
 *          may also return {error: message} if start fails
 */

$init_skip_csrfp = true; // TODO: get CSRFP to work
$no_session_handler = 'onNoSession';
require_once("../init.php");
require_once("./common_start.php");
require_once("./AssessInfo.php");
require_once("./AssessRecord.php");
require_once('./AssessUtils.php');

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

$now = time();

// load settings including question info
$assess_info = new AssessInfo($DBH, $aid, $cid, 'all');
$assess_info->loadException($uid, $isstudent, $studentinfo['latepasses'] , $latepasshrs, $courseenddate);
if ($isstudent) {
  $assess_info->applyTimelimitMultiplier($studentinfo['timelimitmult']);
}

// reject if not available
if ($assess_info->getSetting('available') === 'practice' && !empty($_POST['practice'])) {
  $in_practice = true;
} else if ($assess_info->getSetting('available') === 'yes' || $canViewAll) {
  $in_practice = false;
} else {
  echo '{"error": "not_avail"}';
  exit;
}

// load user's assessment record
$assess_record = new AssessRecord($DBH, $assess_info, $in_practice);
$assess_record->loadRecord($uid);

// check password, if needed
if (!$in_practice &&
  (!isset($sessiondata['assess2-'.$aid]) || $sessiondata['assess2-'.$aid] != $in_practice) &&
  !$assess_info->checkPassword($_POST['password'])
) {
  echo '{"error": "invalid_password"}';
  exit;
}

// reject start if has current attempt, time limit expired, and is kick out
if (!$in_practice &&
  $assess_record->hasActiveAttempt() &&
  $assess_info->getSetting('timelimit') > 0 &&
  $assess_info->getSetting('timelimit_type') == 'kick_out' &&
  $assess_record->getTimeLimitExpires() < $now
) {
  echo '{"error": "timelimit_expired"}';
  exit;
}

// add any new group members, if allowed
if (!$canViewAll &&
  $assess_info->getSetting('isgroup') == 2 &&
  ($_POST['new_group_members'] != '' || !$assess_record->hasRecord())
) {
  $groupsetid = $assess_info->getSetting('groupsetid');
  // get current group and members
  list($stugroupid, $current_members) = AssessUtils::getGroupMembers($uid, $groupsetid);

  $potential_group_members = explode(',', $_POST['new_group_members']);
  $available_new_members = AssessUtils::checkPotentialGroupMembers($potential_group_members, $groupsetid);

  if ($stugroupid == 0) {
    // need to create a new stugroup for user and group
    $stm = $this->DBH->prepare("INSERT INTO imas_stugroups (name,groupsetid) VALUES ('Unnamed group',?)");
		$stm->execute(array($groupsetid));
    $stugroupid = $this->DBH->lastInsertId();

    $available_new_members[] = $uid;
  }
  // see if we are starting a new group or adding to existing one.
  // need to check that the user wasn't added to another group since initial launch
  // in which case we won't add the group members
  if (count($current_members) == 0 || $stugroupid == $_POST['cur_group']) {
    // Add new members to the group
    $qarr = array();
    $vals = array();
    foreach ($available_new_members as $gm_uid) {
      $vals[] = '(?,?)';
      array_push($qarr, $gm_uid, $stugroupid);
    }
    $query = 'INSERT INTO imas_stugroupmembers (userid,stugroupid) VALUES ';
    $query .= implode(',', $vals);
    $stm = $this->DBH->prepare($query);
    $stm->execute($qarr);
  }
  $current_members = array_merge($current_members, $available_new_members);
}

// if there is no active assessment record, time to create one
if (!$assess_record->hasRecord()) {
  // if it's a user-created group, we've already gotten group members above
  // Handle pre-created group case
  if (!$canViewAll && $assess_info->getSetting('isgroup') == 3) {
    list($stugroupid, $current_members) = AssessUtils::getGroupMembers($uid, $groupsetid);
    if ($stugroupid == 0) {
      // no group yet - can't do anything
      echo '{"error": "need_group"}';
      exit;
    }
  }

  // time to create a new record!
  $lti_sourcedid = '';
  if ($assess_info->getSetting('isgroup') > 0 && !$canViewAll) {
    // creating for group
    $assess_record->createRecord($current_members, $stugroupid, true, $lti_sourcedid);
  } else {
    // creating for self
    $assess_record->createRecord(false, 0, true, $lti_sourcedid);
  }
}

// if there's no active assessment attempt, generate one
if (!$assess_record->hasUnsubmittedAttempt()) {
  if ($in_practice) {
    // for practice, if we don't have unsubmitted attempt, then
    // we need to create a whole new data
    $assess_record->buildAssessData(true);
  } else {
    if ($assess_record->hasUnstartedAttempt()) {
      // has an assessment attempt they haven't started yet
      $assess_record->setStatus(true, true);
    } else if ($assess_record->canMakeNewAttempt()) {
      // if we can make a new one, do it
      $assess_record->buildNewAssessVersion(true);
    } else {
      // if we can't make one, report error
      echo '{"error": "out_of_attempts"}';
      exit;
    }
  }
}

// update lti_sourcedid if needed
if (isset($sessiondata['lti_lis_result_sourcedid'])) {
  $altltisourcedid = $sessiondata['lti_lis_result_sourcedid'].':|:'.$sessiondata['lti_outcomeurl'].':|:'.$sessiondata['lti_origkey'].':|:'.$sessiondata['lti_keylookup'];
  $assess_record->updateLTIsourcedId($altltisourcedid);
}

$assessInfoOut = array();

//get prev attempt info before switching to practice mode
$assessInfoOut['prev_attempts'] = $assess_record->getSubmittedAttempts();
$assessInfoOut['scored_attempt'] = $assess_record->getScoredAttempt();

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
  'extended_with', 'timelimit', 'timelimit_type', 'allowed_attempts',
  'latepasses_avail', 'latepass_extendto', 'showscores', 'intro',
  'interquestion_text', 'resources', 'video_id', 'category_urls', 'help_features',
  'points_possible'
);
if ($in_practice) {
  array_push($include_from_assess_info, 'displaymethod', 'showscores',
    'allowed_attempts'
  );
}
$assessInfoOut = array_merge($assessInfoOut, $assess_info->extractSettings($include_from_assess_info));

// indicate if teacher user
$assessInfoOut['can_view_all'] = $canViewAll;

//get attempt info
$assessInfoOut['has_active_attempt'] = $assess_record->hasActiveAttempt();
//get time limit expiration of current attempt, if appropriate
if ($assessInfoOut['has_active_attempt'] && $assessInfoOut['timelimit'] > 0) {
  $assessInfoOut['timelimit_expires'] = $assess_record->getTimeLimitExpires();
}

// grab question settings data
$showscores = $assess_info->showScoresDuring();
$generate_html = ($assess_info->getSetting('displaymethod') == 'full');
$assessInfoOut['questions'] = $assess_record->getAllQuestionObjects($showscores, $generate_html, $generate_html);

// if practice, add that
$assessInfoOut['in_practice'] = $in_practice;

// save record if needed
$assess_record->saveRecordIfNeeded();

// store assessment start in session data, so we know if they've gotten past
// password at some point
$sessiondata['assess2-'.$aid] = $in_practice;
writesessiondata();

//output JSON object
echo json_encode($assessInfoOut);

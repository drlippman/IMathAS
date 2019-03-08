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
 *
 * Returns: partial assessInfo object, adding question data if launch successful
 *          may also return {error: message} if start fails
 */

$init_skip_csrfp = true; // TODO: get CSRFP to work
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
if ($isteacher && isset($_GET['uid'])) {
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

// load user's assessment record
$assess_record = new AssessRecord($DBH, $assess_info);
$assess_record->loadRecord($uid);

// reject if not available
// TODO
$in_practice = false;

// check password, if needed
if (!$assess_info->checkPassword($_POST['password'])) {
  echo '{"error": "invalid_password"}';
  exit;
}

// reject start if has current attempt, time limit expired, and is kick out
if ($assess_record->hasActiveAttempt() &&
  $assess_info->getSetting('timelimit') > 0 &&
  $assess_info->getSetting('timelimit_type') == 'kick_out' &&
  $assess_record->getTimeLimitExpires() < $now
) {
  echo '{"error": "timelimit_expired"}';
  exit;
}

// add any new group members, if allowed
if ($assess_info->getSetting('isgroup') == 2 &&
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
  if ($assess_info->getSetting('isgroup') == 3) {
    list($stugroupid, $current_members) = AssessUtils::getGroupMembers($uid, $groupsetid);
    if ($stugroupid == 0) {
      // no group yet - can't do anything
      echo '{"error": "need_group"}';
      exit;
    }
  }

  // time to create a new record!
  $lti_sourcedid = '';
  if ($assess_info->getSetting('isgroup') > 0) {
    // creating for group
    $assess_record->createRecord($current_members, $stugroupid, true, $lti_sourcedid, $in_practice);
  } else {
    // creating for self
    $assess_record->createRecord(false, 0, true, $lti_sourcedid, $in_practice);
  }
}

// if there's no active assessment attempt, generate one
if (!$assess_record->hasUnsubmittedAttempt($in_practice)) {
  if ($in_practice) {
    // for practice, if we don't have unsubmitted attempt, then
    // we need to create a whole new data
    $assess_record->buildAssessData(true);
  } else {
    // if we can make a new one, do it
    if ($assess_record->canMakeNewAttempt(false)) {
      $assess_record->buildNewAssessVersion(false, true);
    } else {
      // if we can't make one, report error
      echo '{"error": "out_of_attempts"}';
    }
  }
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
  'interquestion_text', 'resources', 'video_id', 'category_urls', 'help_features'
);
$assessInfoOut = $assess_info->extractSettings($include_from_assess_info);
//get attempt info
$assessInfoOut['has_active_attempt'] = $assess_record->hasActiveAttempt();
//get time limit expiration of current attempt, if appropriate
if ($assessInfoOut['has_active_attempt'] && $assessInfoOut['timelimit'] > 0) {
  $assessInfoOut['timelimit_expires'] = $assess_record->getTimeLimitExpires();
}
//get prev attempt info
if ($assessInfoOut['submitby'] == 'by_assessment') {
  $showPrevAttemptScores = ($assessInfoOut['showscores'] != 'none');
  $assessInfoOut['prev_attempts'] = $assess_record->getSubmittedAttempts($showPrevAttemptScores);
  if ($showPrevAttemptScores) {
    $assessInfoOut['scored_attempt'] = $assess_record->getScoredAttempt();
  }
}

// grab question settings data
$showscores = $assess_info->showScoresDuring();
$generate_html = ($assess_info->getSetting('displaymethod') == 'full');
$assessInfoOut['questions'] = $assess_record->getAllQuestionObjects($in_practice, $showscores, $generate_html);

//output JSON object
echo json_encode($assessInfoOut);

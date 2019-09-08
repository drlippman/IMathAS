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


$no_session_handler = 'json_error';
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
  if ($canViewAll) {
    $assess_info->overrideAvailable('yes');
  }
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
  (($assess_info->getSetting('timelimit_type') == 'kick_out' &&
    $now > $assess_record->getTimeLimitExpires() + 10) ||
    ($assess_info->getSetting('timelimit_type') == 'allow_overtime' &&
    $now > $assess_record->getTimeLimitGrace() + 10))
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
  $current_members = array_keys($current_members); // we just want the user IDs
  if (trim($_POST['new_group_members']) == '') {
    $potential_group_members = array();
  } else {
    $potential_group_members = explode(',', $_POST['new_group_members']);
  }
  $available_new_members = AssessUtils::checkPotentialGroupMembers($potential_group_members, $groupsetid);

  if ($stugroupid == 0) {
    // need to create a new stugroup for user and group
    $stm = $DBH->prepare("INSERT INTO imas_stugroups (name,groupsetid) VALUES ('Unnamed group',?)");
		$stm->execute(array($groupsetid));
    $stugroupid = $DBH->lastInsertId();

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
    $stm = $DBH->prepare($query);

    $stm->execute($qarr);
  }
  $current_members = array_merge($current_members, $available_new_members);

  // if we already have an assess record, need to copy it to new group members
  if ($assess_record->hasRecord()) {
    // get current record
    $fieldstocopy = 'assessmentid,agroupid,timeontask,starttime,lastchange,score,status,scoreddata,practicedata,ver';
    $query = "SELECT $fieldstocopy FROM ";
    $query .= "imas_assessment_records WHERE userid=:userid AND assessmentid=:assessmentid";
    $stm = $DBH->prepare($query);
    $stm->execute(array(':userid'=>$userid, ':assessmentid'=>$aid));
    $rowgrpdata = $stm->fetch(PDO::FETCH_NUM);
    // now copy it to others
    $ph = Sanitize::generateQueryPlaceholders($rowgrpdata);
    $query = "REPLACE INTO imas_assessment_records (userid,$fieldstocopy) ";
    $query .= "VALUES (?,$ph)";
    $stm = $DBH->prepare($query);
    foreach ($available_new_members as $gm_uid) {
      $stm->execute(array_merge(array($gm_uid), $rowgrpdata));
    }
  }
}

// if there is no active assessment record, time to create one
if (!$assess_record->hasRecord()) {
  // if it's a user-created group, we've already gotten group members above
  // Handle pre-created group case
  if (!$canViewAll && $assess_info->getSetting('isgroup') == 3) {
    $groupsetid = $assess_info->getSetting('groupsetid');
    list($stugroupid, $current_members) = AssessUtils::getGroupMembers($uid, $groupsetid);
    $current_members = array_keys($current_members); // we just want the user IDs
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

// log access
if ($isRealStudent) {
  $query = "INSERT INTO imas_content_track (userid,courseid,type,typeid,viewtime) VALUES ";
  $query .= "(:userid, :courseid, :type, :typeid, :viewtime)";
  $stm = $DBH->prepare($query);
  $stm->execute(array(':userid'=>$uid, ':courseid'=>$cid,
    ':type'=>$in_practice?'assessreview':'assess',
    ':typeid'=>$aid, ':viewtime'=>time()));
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

// See if we need to do anything to the intro, since we're sending it
$assess_info->processIntro();

// grab any assessment info fields that may have updated:
// has_active_attempt, timelimit_expires,
// prev_attempts (if we just closed out a version?)
// and those not yet loaded:
// help_features, intro, resources, video_id, category_urls
$include_from_assess_info = array(
  'available', 'startdate', 'enddate', 'original_enddate', 'submitby',
  'extended_with', 'timelimit', 'timelimit_type', 'allowed_attempts',
  'latepasses_avail', 'latepass_extendto', 'showscores', 'intro',
  'interquestion_text', 'resources', 'category_urls', 'help_features',
  'points_possible'
);
if ($in_practice) {
  array_push($include_from_assess_info, 'displaymethod', 'showscores',
    'allowed_attempts'
  );
}
$assessInfoOut = array_merge($assessInfoOut, $assess_info->extractSettings($include_from_assess_info));

// filter interquestion text html
foreach ($assessInfoOut['interquestion_text'] as $k=>$v) {
  $assessInfoOut['interquestion_text'][$k]['text'] = filter($v['text']);
}
$assessInfoOut['intro'] = filter($assessInfoOut['intro']);

$assessInfoOut['show_results'] = !$assess_info->getSetting('istutorial');


//get attempt info
$assessInfoOut['has_active_attempt'] = $assess_record->hasActiveAttempt();
//get time limit expiration of current attempt, if appropriate
if ($assessInfoOut['has_active_attempt'] && $assessInfoOut['timelimit'] > 0) {
  $assessInfoOut['timelimit_expires'] = $assess_record->getTimeLimitExpires();
  $assessInfoOut['timelimit_grace'] = $assess_record->getTimeLimitGrace();
}

// grab video cues if needed
if ($assess_info->getSetting('displaymethod') === 'video_cued') {
  $viddata = $assess_info->getVideoCues();
  $assessInfoOut['videoid'] = $viddata['vidid'];
  $assessInfoOut['videoar'] = $viddata['vidar'];
  $assessInfoOut['videocues'] = $viddata['cues'];
}

// grab livepoll status if needed.  If doesn't exist, create record
if ($assess_info->getSetting('displaymethod') === 'livepoll') {
  $stm = $DBH->prepare("SELECT curquestion,curstate,seed,startt FROM imas_livepoll_status WHERE assessmentid=:assessmentid");
  $stm->execute(array(':assessmentid'=>$aid));
  if ($stm->rowCount()==0) {
    $assessInfoOut['livepoll_status'] = array("curquestion"=>0, "curstate"=>0, "seed"=>0, "startt"=>0);
    $stm = $DBH->prepare("INSERT INTO imas_livepoll_status (assessmentid,curquestion,curstate) VALUES (:assessmentid, :curquestion, :curstate) ON DUPLICATE KEY UPDATE curquestion=curquestion");
    $stm->execute(array(':assessmentid'=>$aid, ':curquestion'=>0, ':curstate'=>0));
  } else {
    $assessInfoOut['livepoll_status'] = array_map('intval', $stm->fetch(PDO::FETCH_ASSOC));

  }
  $livepollroom = $aid.'-'.($isteacher ? 'teachers':'students');
  $assessInfoOut['livepoll_data'] = array(
    'room' => $livepollroom,
    'now' => $now
  );
  if (isset($CFG['GEN']['livepollpassword'])) {
    $livepollsig = base64_encode(sha1($livepollroom . $CFG['GEN']['livepollpassword'] . $now,true));
    $assessInfoOut['livepoll_data']['sig'] = $livepollsig;
  }
}

// get settings for LTI if needed
if (isset($sessiondata['ltiitemtype']) && $sessiondata['ltiitemtype']==0) {
  if ($coursemsgset < 4 && $assessInfoOut['help_features']['message']==true) {
    $assessInfoOut['lti_showmsg'] = 1;
    // get msg count
    $stm = $DBH->prepare("SELECT COUNT(id) FROM imas_msgs WHERE msgto=:msgto AND courseid=:courseid AND (isread=0 OR isread=4)");
		$stm->execute(array(':msgto'=>$uid, ':courseid'=>$cid));
		$assessInfoOut['lti_msgcnt'] = intval($stm->fetchColumn(0));
  }
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

//prep date display
prepDateDisp($assessInfoOut);

//output JSON object
echo json_encode($assessInfoOut);

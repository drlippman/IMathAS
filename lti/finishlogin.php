<?php

/**
 * After Launch, show_postback_form shows a form which posts back
 * here to complete the login or account creation process 
 *
 */

$init_session_start = true;
$init_skip_csrfp = true;
require('../init_without_validate.php');
require_once(__DIR__ . '/lib/lti.php');
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../includes/password.php';

use \IMSGlobal\LTI;
if (!isset($_POST['launchid'])) {
  echo 'Error - missing launch id';
  exit;
}
$db = new Imathas_LTI_Database($DBH);
$launch = LTI\LTI_Message_Launch::from_cache($_POST['launchid'], $db);

$role = standardize_role($launch->get_roles());
$contextid = $launch->get_platform_context_id();
$ltiuserid = $launch->get_platform_user_id();
$platform_id = $launch->get_platform_id();

// see if we already know who this person is
$migration_claim = $launch->get_migration_claim();
$localuserid = $db->get_local_userid($launch);
$localcourse = $db->get_local_course($contextid, $launch);

// no local user yet.  Parse submitted info.
if ($localuserid === false) {
  // see if we're trying to login
  if (!empty($_POST['curSID']) && (
    ($role=='Learner' && !empty($localcourse->get_allow_direct_login())) ||
    $role=='Instructor'
  )) {
    // check login
    $stm = $DBH->prepare('SELECT password,id,groupid FROM imas_users WHERE SID=:sid');
    $stm->execute(array(':sid'=>$_POST['curSID']));
    if ($stm->rowCount()==0) {
      $err = _('Existing username or password is not valid');
    } else {
      list($realpw,$tmpuserid,$tmpgroupid) = $stm->fetch(PDO::FETCH_NUM);
      if (password_verify($_POST['curPW'],$realpw)) {
        // valid login
        $localuserid = $tmpuserid;
        if ($role == 'Instructor') {
          // if teacher, make sure the deployment is associated with the groupid
          $db->set_group_assoc($platform_id, $launch->get_deployment_id(), $tmpgroupid);
        }
      } else {
        $err = _('Existing username or password is not valid');
        unset($tmpuserid);
      }
    }
  } else if (!empty($_POST['SID']) && (
    ($role=='Learner' && !empty($localcourse->get_allow_direct_login())) ||
    ($role=='Instructor' && !empty($GLOBALS['CFG']['LTI']['allow_instr_create']))
  )) {
    // create new account
    require_once(__DIR__.'/../includes/newusercommon.php');
    $err = checkNewUserValidation();

    $groupid = 0;
    if ($role == 'Instructor') {
      $rights = isset($CFG['LTI']['instrrights']) ? $CFG['LTI']['instrrights'] : 40;
      $groups = $this->db->get_groups($platform_id, $launch->get_deployment_id());
      if (count($groups)==1) {
        $groupid = $groups[0]['id'];
      } else if (count($groups)>1 && in_array($_POST['groupid'], $groups)) {
        $groupid = intval($_POST['groupid']);
      } else {
        $err = _('Invalid group id');
      }
    } else {
      $rights = 10;
    }

    if ($err == '') {
      $localuserid = $db->create_user_account($DBH, [
        'SID' => $_POST['SID'],
        'pwhash' => password_hash($_POST['pw1'], PASSWORD_DEFAULT),
        'rights' => $rights,
        'firstname' => $_POST['firstname'],
        'lastname' => $_POST['lastname'],
        'email' => $_POST['email'],
        'msgnot' => isset($_POST['msgnot']) ? 1 : 0,
        'groupid' => $groupid
      ]);
    }
  } else if ($role=='Learner') {
    // create no-direct-login student account
    $email = '';
    if (!empty($_POST['firstname'])) {
      $firstname = $_POST['firstname'];
      $lastname = $_POST['lastname'];
    } else if ($name = parse_name_from_launch($launch->get_launch_data())) {
      list($firstname,$lastname) = $name;
      if (!empty($launch->get_launch_data()['email'])) {
        $email = $launch->get_launch_data()['email'];
      }
    } else {
      $err = _('No name provided');
    }

    if ($err == '') {
      $localuserid = $db->create_user_account([
        'SID' => uniqid(), // temporary
        'pwhash' => 'pass',
        'rights' => 10,
        'firstname' => $firstname,
        'lastname' => $lastname,
        'email' => $email,
        'msgnot' => isset($_POST['msgnot']) ? 1 : 0,
        'groupid' => 0
      ]);
      $updateSID = true;
    }
  }

  if ($localuserid !== false) {
    // we've logged into or created a local user account, so now create a
    // ltiuserid link
    $num = $db->create_lti_user($localuserid, $ltiuserid, $platform_id);
    if (!empty($updateSID)) {
      $db->set_user_SID($localuserid, 'lti-'.$num);
    }
  }
}
if ($localuserid === false) {
  // wasn't able to create a new user; redisplay form and try again.
  require(__DIR__ .'/show_postback_form.php');
  show_postback_form($launch, new Imathas_LTI_Database($DBH), $err);
  exit;
}

// We have a local userid, so log them in.
$_SESSION['lti_user_id'] = $ltiuserid;
$_SESSION['userid'] = $localuserid;
$_SESSION['ltiver'] = '1.3';
$_SESSION['tzoffset'] = $_POST['tzoffset'];
$_SESSION['time'] = time();
$tzname = '';
if (!empty($_POST['tzname'])) {
    $_SESSION['tzname'] = $_POST['tzname'];
 	if (date_default_timezone_set($_SESSION['tzname'])) {
        $tzname = $_SESSION['tzname'];
    }
}
require_once(__DIR__."/../includes/userprefs.php");
generateuserprefs();

if ($role == 'Instructor' && $localcourse === null) {
  // no course connection yet
  require(__DIR__.'/connectcourse.php');
  connect_course($launch, $db, $localuserid);
} else {

  // enroll student in course if needed
  $contextlabel = $launch->get_platform_context_label();
  $db->enroll_if_needed($localuserid, $role, $localcourse, $contextlabel);

  // we have a course connection
  if ($launch->is_deep_link_launch() && $role == 'Instructor') {
    require(__DIR__.'/deep_link_form.php');
    deep_link_form($launch, $localuserid, $localcourse, $db);
  } else if ($launch->is_submission_review_launch()) {
    require(__DIR__.'/submissionlink.php');
    link_to_submission($launch, $localuserid, $localcourse, $db);
  } else if ($launch->is_resource_launch()) {
    require(__DIR__.'/resourcelink.php');
    link_to_resource($launch, $localuserid, $localcourse, $db);
  } else {
    echo 'Error - invalid launch type';
    exit;
  }
}

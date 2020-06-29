<?php

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
$localuserid = $db->get_local_userid($ltiuserid, $platform_id);
$localcourse = $db->get_local_course($contextid, $platform_id);

// no local user yet.  Parse submitted info.
if ($localuserid === false) {
  // see if we're trying to login
  if (!empty($_POST['curSID']) && (
    ($role=='Learner' && !empty($localcourse['allow_direct_login'])) ||
    $role=='Instructor'
  )) {
    // check login
    $stm = $DBH->prepare('SELECT password,id FROM imas_users WHERE SID=:sid');
    $stm->execute(array(':sid'=>$_POST['curSID']));
    if ($stm->rowCount()==0) {
      $err = _('Existing username or password is not valid');
    } else {
      list($realpw,$tmpuserid) = $stm->fetch(PDO::FETCH_NUM);
      if (password_verify($_POST['curPW'],$realpw)) {
        $localuserid = $tmpuserid;
      } else {
        $err = _('Existing username or password is not valid');
        unset($tmpuserid);
      }
    }
  } else if (!empty($_POST['SID']) && (
    ($role=='Learner' && !empty($localcourse['allow_direct_login'])) ||
    ($role=='Instructor' && !empty($GLOBALS['lti']['allow_instr_create']))
  )) {
    // create new account
    require_once(__DIR__.'/../includes/newusercommon.php');
    $err = checkNewUserValidation();

    $groupid = 0;
    if ($role == 'Instructor') {
      $rights = isset($CFG['LTI']['instrrights']) ? $CFG['LTI']['instrrights'] : 40;
      $groups = $this->db->get_groups($launch->get_issuer(), $launch->get_deployment_id());
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

    if (!empty($_POST['firstname'])) {
      $firstname = $_POST['firstname'];
      $lastname = $_POST['lastname'];
    } else if ($name = parse_name_from_launch($launch->get_launch_data())) {
      list($firstname,$lastname) = $name;
    } else {
      $err = _('No name provided');
    }

    if ($err == '') {
      $localuserid = $db->create_user_account([
        'SID' => uniqid(), // temporary
        'pwhash' => password_hash($_POST['pw1'], PASSWORD_DEFAULT),
        'rights' => 10,
        'firstname' => $_POST['firstname'],
        'lastname' => $_POST['lastname'],
        'email' => $_POST['email'],
        'msgnot' => isset($_POST['msgnot']) ? 1 : 0,
        'groupid' => $groupid
      ]);
    }
  }

  if ($localuserid !== false) {
    // we've logged into or created a local user account, so now create a
    // ltiuserid link
    $db->create_lti_user($localuserid, $ltiuserid, $platform_id);
  }
}
if ($localuserid === false) {
  // wasn't able to create a new user; redisplay form and try again.
  require(__DIR__ .'/locallogin.php');
  show_postback_form($launch, new Imathas_LTI_Database($DBH), $err);
  exit;
}

// We have a local userid, so log them in.
$_SESSION['lti_user_id'] = $ltiuserid;
$_SESSION['userid'] = $localuserid;
require_once(__DIR__."/../includes/userprefs.php");
generateuserprefs();

// TODO: will want to set $_SESSION['ltiitemtype']

if ($role == 'Instructor' && $localcourse === false) {
  // no course connection yet
  require(__DIR__.'/connectcourse.php');
  connect_course($launch, $db, $localuserid);
} else {

  // enroll student in course if needed
  $contextlabel = $launch->get_platform_context_label();
  $db->enroll_if_needed($localuserid, $role, $localcourse['courseid'], $contextlabel);

  // we have a course connection
  if ($launch->is_deep_link_launch() && $role == 'Instructor') {
    echo 'Is deep linking request - do something';
  } else if ($launch->is_submission_review_launch()) {
    echo 'Is submission review launch';
  } else if ($launch->is_resource_launch()) {
    require(__DIR__.'/resourcelink.php');
    link_to_resource($launch, $localcourse, $db);
  } else {
    echo 'Error - invalid launch type';
    exit;
  }
}

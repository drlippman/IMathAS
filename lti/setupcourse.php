<?php

require('../init.php');

if ($myrights < 20) {
  exit;
}

require_once(__DIR__ . '/lib/lti.php');
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/helpers.php';

use \IMSGlobal\LTI;
if (!isset($_POST['launchid'])) {
  echo 'Error - missing launch id';
  exit;
}
$db = new Imathas_LTI_Database($DBH);
$launch = LTI\LTI_Message_Launch::from_cache($_POST['launchid'], $db);

$role = standardize_role($launch->get_roles());
$contextid = $launch->get_platform_context_id();
$contextlabel = $launch->get_platform_context_label();
$platform_id = $launch->get_platform_id();

// TODO: double check course connection not already established

if ($_POST['linktype'] == 'assoc') {
  $destcid = intval($_POST['assocselect']);
  if (!$db->ok_to_associate($destcid, $userid)) {
    echo 'Invalid course to associate';
    exit;
  }
  // TODO: this doesn't retain copiedfrom in imas_lti_courses.
  // It'd be nice to see if there was a way to do so.
  $db->add_lti_course($contextid, $platform_id, $destcid, $contextlabel);
  $localcourse = array('courseid'=>$destcid, 'copiedfrom'=>0);
} else if ($_POST['linktype'] == 'copy') {
  require_once(__DIR__.'/../includes/copycourse.php');
  // TODO: do we want to use the context.title instead of label here? Or both?
  $newUIver = isset($_POST['usenewassess']);
  $destcid = copycourse($_POST['copyselect'], $contextlabel);
  $db->add_lti_course($contextid, $platform_id, $destcid, $contextlabel);
  $localcourse = array('courseid'=>$destcid, 'copiedfrom'=>$_POST['copyselect'], 'UIver'=>$newUIver?2:1);
}

if ($launch->is_resource_launch()) {
  require(__DIR__.'/resourcelink.php');
  link_to_resource($launch, $userid, $localcourse, $db);
}

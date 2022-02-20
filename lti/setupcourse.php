<?php
$init_skip_csrfp = true;
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
$contexttitle = $launch->get_platform_context_title();
$platform_id = $launch->get_platform_id();

// double check course connection not already established
if ($db->get_local_course($contextid, $launch)) {
  echo 'Error: course link already established';
  exit;
}
if ($_POST['linktype'] == 'assoc') {
  $destcid = intval($_POST['assocselect']);
  if (!$db->ok_to_associate($destcid, $userid)) {
    echo 'Invalid course to associate';
    exit;
  }
  $prev_copiedfrom = $db->get_previous_copiedfrom($destcid, $platform_id);
  $newlticourseid = $db->add_lti_course($contextid, $platform_id, $destcid, $contexttitle, $prev_copiedfrom);
  $localcourse = LTI\LTI_Localcourse::new()
    ->set_courseid($destcid)
    ->set_copiedfrom($prev_copiedfrom)
    ->set_id($newlticourseid);
} else if ($_POST['linktype'] == 'copy') {
  require_once(__DIR__.'/../includes/copycourse.php');
  // TODO: do we want to use the context.title instead of label here? Or both?
  $newUIver = isset($_POST['usenewassess']) ? 2 : 1;
  $destcid = copycourse($_POST['copyselect'], $contexttitle, $newUIver);
  $newlticourseid = $db->add_lti_course($contextid, $platform_id, $destcid, $contexttitle);
  $localcourse = LTI\LTI_Localcourse::new()
    ->set_courseid($destcid)
    ->set_copiedfrom(intval($_POST['copyselect']))
    ->set_UIver($newUIver ? 2 : 1)
    ->set_id($newlticourseid);
}

if ($launch->is_resource_launch()) {
  require(__DIR__.'/resourcelink.php');
  link_to_resource($launch, $userid, $localcourse, $db);
} else if ($launch->is_deep_link_launch() && $role == 'Instructor') {
  require(__DIR__.'/deep_link_form.php');
  deep_link_form($launch, $userid, $localcourse, $db);
}

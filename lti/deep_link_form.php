<?php

function deep_link_form($launch, $localuserid, $localcourse, $db) {
  global $imasroot,$installname,$coursetheme,$CFG;

  $role = standardize_role($launch->get_roles());
  if ($role !== 'Instructor') {
    echo 'You do not have access to this page';
    exit;
  }
  $contextid = $launch->get_platform_context_id();
  $platform_id = $launch->get_platform_id();
  $resource_link = $launch->get_resource_link();

  $assessments = $db->get_assessments($localcourse['courseid']);

  $flexwidth = true;
	$nologo = true;

	require("../header.php");
	echo '<h1>'._('Select assessment to link to').'</h1>';
  echo '<form method=post action="setupdeeplink.php">';
  echo '<input type=hidden name=launchid value="'.$launch->get_launch_id().'"/>';
  echo '<p><select name=aid>';
  foreach ($assessments as $ass) {
    echo '<option value="'.Sanitize::onlyInt($ass['id']).'">';
    echo Sanitize::encodeStringForDisplay($ass['name']).'</option>';
  }
  echo '</select></p>';
  echo '<button type=submit>'._('Create Link').'</button>';
  echo '</form>';
  require('../footer.php');
}

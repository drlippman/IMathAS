<?php

use \IMSGlobal\LTI;

if (isset($GLOBALS['CFG']['hooks']['lti'])) {
    require_once($CFG['hooks']['lti']);
    /**
     * see ltihooks.php.dist for details
     */
}

/**
 * Displays the deep linking resource selection form
 * @param LTI_Message_Launch $launch
 * @param int                $localuserid
 * @param LTI_Localcourse    $localcourse
 * @param Database           $db
 */
function deep_link_form(LTI\LTI_Message_Launch $launch, int $localuserid,
  LTI\LTI_Localcourse $localcourse, LTI\Database $db
): void {
  global $imasroot,$installname,$coursetheme,$CFG;

  $role = standardize_role($launch->get_roles());
  if ($role !== 'Instructor') {
    echo 'You do not have access to this page';
    exit;
  }
  $contextid = $launch->get_platform_context_id();
  $platform_id = $launch->get_platform_id();
  $resource_link = $launch->get_resource_link();

  $assessments = $db->get_assessments($localcourse->get_courseid());

  $flexwidth = true;
	$nologo = true;

	require("../header.php");
	echo '<h1>'._('Select assessment to link to').'</h1>';
  echo '<form method=post action="setupdeeplink.php">';
  echo '<input type=hidden name=launchid value="'.$launch->get_launch_id().'"/>';
  echo '<p><select name=deeplinktarget>';
  foreach ($assessments as $ass) {
    echo '<option value="assess-'.Sanitize::onlyInt($ass['id']).'">';
    echo Sanitize::encodeStringForDisplay($ass['name']).'</option>';
  }
  if (function_exists('lti_deeplink_options')) {
    lti_deeplink_options($localcourse);
  }
  echo '</select></p>';
  echo '<button type=submit>'._('Create Link').'</button>';
  echo '</form>';
  require('../footer.php');
}

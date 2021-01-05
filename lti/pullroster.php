<?php
//IMathAS: LTI instructor home page
//(c) 2020 David Lippman

use \IMSGlobal\LTI;

require("../init.php");
if (!isset($_SESSION['ltirole']) || $_SESSION['ltirole']!='instructor') {
	echo _("Not authorized to view this page");
	exit;
}

if (!isset($_GET['launchid'])) {
    // this should have gotten inserted by general.js out of sessionstorage
    echo _('Missing launch id');
    exit;
}

require_once(__DIR__ . '/lib/lti.php');
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/helpers.php';

//Look to see if a hook file is defined, and include if it is
if (isset($GLOBALS['CFG']['hooks']['lti'])) {
  require_once($CFG['hooks']['lti']);
}
if (isset($CFG['hooks']['ltihome'])) {
	require($CFG['hooks']['ltihome']);
}

$db = new Imathas_LTI_Database($DBH);
$launch = LTI\LTI_Message_Launch::from_cache($_GET['launchid'], $db);
$contextid = $launch->get_platform_context_id();
$platform_id = $launch->get_platform_id();
$localcourse = $db->get_local_course($contextid, $launch);

if (!empty($_POST['tolock'])) {
	$db->lock_stus($_POST['tolock'], $localcourse->get_courseid());
	header(sprintf('Location: %s/lti/ltihome.php?launchid=%s',
		$GLOBALS['basesiteurl'],
		$launch->get_launch_id()
	));
	exit;
}

if (!empty($_POST['pullroster'])) {
	$nrps = $launch->get_nrps();
    $data = $nrps->get_members();
    $notfound = array();
    if ($data !== false) {
        list($newcnt,$notfound) = $db->update_roster($data, $localcourse, $platform_id);
    }

	require('../header.php');
	echo '<div class=breadcrumb>'.$breadcrumbbase.' '._('Roster Pull Results').'</div>';
    echo '<h1>'._('Roster Pull Results').'</h2>';
    
    if ($data === false) {
        echo '<p>'._('Error pulling the roster.').'</p>';
    } else {
        echo '<p>'.sprintf(_('Added %d new students to the roster.'), $newcnt).'</p>';
    }

	if (count($notfound)>0) {
		echo '<form method=post action="pullroster.php?launchid=' .
			Sanitize::encodeStringForDisplay($launch->get_launch_id()).'">';
		echo '<p>'.sprintf(_('These students are currently enrolled in %s, but were not in the LMS roster.'),
			$installname).'</p>';
		echo '<ul>';
		foreach ($notfound as $stu) {
			echo '<li><label><input type=checkbox name="tolock[]" value="'.$stu['id'].'">';
			echo ' ' . Sanitize::encodeStringForDisplay($stu['LastName'].', '.$stu['FirstName']).'</label></li>';
		}
		echo '</ul>';
		echo '<button type=submit>'._('Lock these students').'</button>';
		echo '</form>';
	}
}

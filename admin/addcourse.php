<?php
//IMathAS:  First step of new course creation
//(c) 2018 David Lippman

/*** master php includes *******/
require("../init.php");

if ($myrights < 40) {
	echo "You don't have authorization to access this page";
	exit;
}


$placeinhead = '<script src="'.$staticroot.'/javascript/copyitemslist.js" type="text/javascript"></script>';
$placeinhead .= '<link rel="stylesheet" href="'.$staticroot.'/course/libtree.css" type="text/css" />';
$placeinhead .= '<script type="text/javascript" src="'.$staticroot.'/javascript/libtree.js"></script>';
require("../header.php");

echo '<div class=breadcrumb>'.$breadcrumbbase.' '._('Add New Course').'</div>';
echo '<div class="pagetitle"><h1>'._('Add New Course').'</h1></div>';

echo '<form method="POST" action="forms.php?from=home&action=addcourse">';
$dispgroup = '';
if (($myrights >= 75 || ($myspecialrights&32)==32) && isset($_GET['for']) && $_GET['for']>0 && $_GET['for'] != $userid) {
	$stm = $DBH->prepare("SELECT FirstName,LastName,groupid FROM imas_users WHERE id=?");
	$stm->execute(array($_GET['for']));
	$forinfo = $stm->fetch(PDO::FETCH_ASSOC);
	if ($myrights==100 || ($myspecialrights&32)==32 || $forinfo['groupid']==$groupid) {
		echo '<p>'._('Adding Course For').': ';
		echo Sanitize::encodeStringforDisplay($forinfo['LastName'].', '.$forinfo['FirstName']);
		echo '<input type=hidden name=for value="'.Sanitize::onlyInt($_GET['for']).'" />';
		echo '</p>';
		$dispgroup = $forinfo['groupid'];
	}
}
echo '<p>'._('How would you like to start this course?').'</p>';
echo '<p><button type=submit name=copytype value=0>';
if (isset($CFG['addcourse']['blankbutton'])) {
	echo $CFG['addcourse']['blankbutton'];
} else {
	echo _('Start with a blank course');
}
echo '</button></p>';
if (isset($CFG['coursebrowser'])) {
		//use the course browser
		echo '<p><button type="button" onclick="showCourseBrowser('.Sanitize::encodeStringForDisplay($dispgroup).')">';
		if (isset($CFG['coursebrowsermsg'])) {
			echo $CFG['coursebrowsermsg'];
		} else {
			echo _('Copy a template or promoted course');
		}
		echo '</button>';
		echo '<input type=hidden name=coursebrowserctc id=coursebrowserctc />';
		echo '</p>';
}
echo '<p><button type=button onclick="showCopyOpts()">';
if (isset($CFG['addcourse']['copybutton'])) {
	echo $CFG['addcourse']['copybutton'];
} else if (isset($CFG['coursebrowser'])) {
	echo _('Copy from my or a colleague\'s course');
} else {
	echo _('Copy from my, a colleague\'s, or template course');
}
echo '</button></p>';
echo '<div id=copyoptions style="display:none; padding-left: 20px">';
echo '<p>',_('Select a course to copy'),'</p>';
$skipthiscourse = true;
$cid = 0;
require("../includes/coursecopylist.php");
echo '</div>';
writeEkeyField();
echo '<button type=submit id=continuebutton disabled style="display:none">'._('Continue').'</button>';
echo '</form>';

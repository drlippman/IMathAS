<?php

require("../init.php");
$cid = Sanitize::courseId($_GET['cid']);
if (isset($teacherid)) {
	$isteacher = true;
}
if (isset($tutorid)) {
	$istutor = true;
}
if (!$isteacher && !$istutor && !isset($studentid)) {
	echo _('Error - you are not a student, teacher, or tutor for this course');
	exit;
}
if ($isteacher || $istutor) {
	$canviewall = true;
} else {
	$canviewall = false;
}

if ($canviewall) {
	if (isset($sessiondata[$cid.'gbmode']) && !isset($_GET['refreshdef'])) {
		$gbmode =  $sessiondata[$cid.'gbmode'];
	} else {
		$stm = $DBH->prepare("SELECT defgbmode FROM imas_gbscheme WHERE courseid=:courseid");
		$stm->execute(array(':courseid'=>$cid));
		$gbmode = $stm->fetchColumn(0);
		$sessiondata[$cid.'gbmode'] = $gbmode;
		writesessiondata();
	}
	if (isset($sessiondata[$cid.'catfilter'])) {
		$catfilter = $sessiondata[$cid.'catfilter'];
	} else {
		$catfilter = -1;
	}
	if (isset($tutorsection) && $tutorsection!='') {
		$secfilter = $tutorsection;
	} else {
		if (isset($sessiondata[$cid.'secfilter'])) {
			$secfilter = $sessiondata[$cid.'secfilter'];
		} else {
			$secfilter = -1;
		}
	}

	$hidenc = (floor($gbmode/10)%10)%4; //0: show all, 1 stu visisble (cntingb not 0), 2 hide all (cntingb 1 or 2)
	$availshow = $gbmode%10; //0: past, 1 past&cur, 2 all, 3 past and attempted, 4=current only
} else {
	$secfilter = -1;
	$catfilter = -1;
	$hidenc = 1;
	$availshow = 1;	
}
$showpics = 0;
$lastlogin = false;
$includeduedate = false;
$includelastchange = false;
$totonleft = 0;
$avgontop = 0;
$hidesection = true;
$hidecode = true;
$links = 0;
$hidelocked = 0;

if ($canviewall && !empty($_GET['stu'])) {
	$stu = Sanitize::onlyInt($_GET['stu']);
} else {
	$stu = $userid;
}

require("gbtable2.php");

$includecomments = true;

$gbt = gbtable($stu); 

$flexwidth = true;
$nologo = true;
require("../header.php");

echo '<h1>'.sprintf(_('All Feedback For %s'), $gbt[1][0][0]).'</h1>';

for ($i=0;$i<count($gbt[0][1]);$i++) {
	if ($gbt[1][1][$i][1] == '' || $gbt[1][1][$i][1]=='<p></p>') {
		continue;
	}
	if (!$isteacher && !$istutor && $gbt[0][1][$i][4]==0) { //skip if hidden
		continue;
	}
	if ($hidenc==1 && $gbt[0][1][$i][4]==0) { //skip NC
		continue;
	} else if ($hidenc==2 && ($gbt[0][1][$i][4]==0 || $gbt[0][1][$i][4]==3)) {//skip all NC
		continue;
	}
	if ($gbt[0][1][$i][3]>$availshow) {
		continue;
	}
	if ($hidepast && $gbt[0][1][$i][3]==0) {
		continue;
	}
	echo '<h3>';
	echo Sanitize::encodeStringForDisplay($gbt[0][1][$i][0]);
	echo ' '.sprintf(_('(Score: %g/%g)'), $gbt[1][1][$i][0], $gbt[0][1][$i][2]);
	echo '</h3>';
	echo '<div class="fbbox">';
	echo Sanitize::outgoingHtml($gbt[1][1][$i][1]);
	echo '</div>';
}
	
require("../footer.php");


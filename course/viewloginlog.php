<?php
//IMathAS:  View login record
//(c) 2011 David Lippman

require("../init.php");


$cid = intval($_GET['cid']);
if (!isset($teacherid) && !isset($tutorid)) {
	$uid = $userid;
} else {
	$uid = intval($_GET['uid']);
}

$curBreadcrumb = $breadcrumbbase;
if (empty($_COOKIE['fromltimenu'])) {
    $curBreadcrumb .= " <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
}
if (isset($teacherid)) {
	if (isset($_GET['from']) && $_GET['from']=='gb') {
		$curBreadcrumb .= " <a href=\"gradebook.php?cid=$cid&stu=0\">Gradebook</a> &gt; ";
		$curBreadcrumb .= " <a href=\"gradebook.php?cid=$cid&stu=$uid\">Student Detail</a> &gt; ";
	} else {
		$curBreadcrumb .= " <a href=\"listusers.php?cid=$cid\">Roster</a> &gt; ";
	}
}
$curBreadcrumb .= "View Login Log\n";
$pagetitle = "View Login Log";
require("../header.php");
echo "<div class=\"breadcrumb\">$curBreadcrumb</div>";



echo '<div id="headerloginlog" class="pagetitle"><h1>'.$pagetitle. '</h1></div>';
echo '<div class="cpmid"><a href="viewactionlog.php?cid='.$cid.'&uid='.$uid.'">View Activity Log</a></div>';
$stm = $DBH->prepare("SELECT LastName,FirstName FROM imas_users WHERE id=:id");
$stm->execute(array(':id'=>$uid));
$row = $stm->fetch(PDO::FETCH_NUM);
printf('<h2>Login Log for <span class="pii-full-name">%s, %s</span></h2>',
	Sanitize::encodeStringForDisplay($row[0]), Sanitize::encodeStringForDisplay($row[1]));
echo '<ul class="nomark">';
$stm = $DBH->prepare("SELECT logintime,lastaction FROM imas_login_log WHERE userid=:userid AND courseid=:courseid ORDER BY logintime DESC");
$stm->execute(array(':userid'=>$uid, ':courseid'=>$cid));
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	echo '<li>'.tzdate("l, F j, Y, g:i a",$row[0]);
	if ($row[1]>0) {
		echo '.  On for about '.round(($row[1]-$row[0])/60).' minutes.';
	}
	echo '</li>';
}

echo '</ul>';
require("../footer.php");

?>

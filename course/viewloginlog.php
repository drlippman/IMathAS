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

$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\"> ".Sanitize::encodeStringForDisplay($coursename)."</a>\n";
if (isset($teacherid)) {
	if (isset($_GET['from']) && $_GET['from']=='gb') {
		$curBreadcrumb .= " &gt; <a href=\"gradebook.php?cid=$cid&stu=0\">Gradebook</a> ";
		$curBreadcrumb .= " &gt; <a href=\"gradebook.php?cid=$cid&stu=$uid\">Student Detail</a> ";
	} else {
		$curBreadcrumb .= " &gt; <a href=\"listusers.php?cid=$cid\">Roster</a> ";
	}
}
$curBreadcrumb .= "&gt; View Login Log\n";
$pagetitle = "View Login Log";
require("../header.php");
echo "<div class=\"breadcrumb\">$curBreadcrumb</div>";



echo '<div id="headerloginlog" class="pagetitle"><h1>'.$pagetitle. '</h1></div>';
echo '<div class="cpmid"><a href="viewactionlog.php?cid='.$cid.'&uid='.$uid.'">View Activity Log</a></div>';

//DB $query = "SELECT LastName,FirstName FROM imas_users WHERE id='$uid'";
//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
//DB $row = mysql_fetch_row($result);
$stm = $DBH->prepare("SELECT LastName,FirstName FROM imas_users WHERE id=:id");
$stm->execute(array(':id'=>$uid));
$row = $stm->fetch(PDO::FETCH_NUM);
printf('<h2>Login Log for %s, %s</h2>', Sanitize::encodeStringForDisplay($row[0]), Sanitize::encodeStringForDisplay($row[1]));
echo '<ul class="nomark">';

//DB $query = "SELECT logintime,lastaction FROM imas_login_log WHERE userid='$uid' AND courseid='$cid' ORDER BY logintime DESC";
//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
//DB while ($row = mysql_fetch_row($result)) {
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

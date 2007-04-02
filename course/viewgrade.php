<?php
//IMathAS:  Student view grade on offline grade with feedback
//(c) 2006 David Lippman
	require("../validate.php");
	$cid = $_GET['cid'];
	$gid = $_GET['gid'];
	$stu = $_GET['stu'];
	$gbmode = $_GET['gbmode'];
	
	echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
	echo "&gt; <a href=\"gradebook.php?stu=0&gbmode=$gbmode&cid=$cid\">Gradebook</a> ";
	echo "&gt; View Grade</div>";
	
	$query = "SELECT iu.LastName,iu.FirstName,ig.score,ig.feedback,igi.name FROM imas_users as iu, imas_grades AS ig, imas_gbitems AS igi WHERE ";
	$query .= "iu.id=ig.userid AND igi.id=ig.gbitemid AND ig.id='$gid' AND ig.userid='$userid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$row = mysql_fetch_row($result);
	$pagetitle = "View Grade";
	require("../header.php");
	echo "<h2>View Grade</h2>";
	echo "<p>Grade on <b>{$row[4]}</b> for <b>{$row[1]} {$row[0]}</b></p>";
	echo "<p>Grade: <b>{$row[2]}</b></p>";
	if (trim($row[3])!='') {
		echo "<p>Feedback:<br/>".$row[3].'</p>';
	}
	require("../footer.php");
?>

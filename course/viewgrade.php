<?php
//IMathAS:  Student view grade on offline grade with feedback
//(c) 2006 David Lippman
	require("../init.php");


	$cid = Sanitize::courseId($_GET['cid']);
	$gid = $_GET['gid'];
	$stu = $_GET['stu'];
	$gbmode = $_GET['gbmode'];


	//DB $query = "SELECT iu.LastName,iu.FirstName,ig.score,ig.feedback,igi.name FROM imas_users as iu, imas_grades AS ig, imas_gbitems AS igi WHERE ";
	//DB $query .= "iu.id=ig.userid AND igi.id=ig.gradetypeid AND ig.gradetype='offline' AND ig.id='$gid' AND ig.userid='$userid'";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB $row = mysql_fetch_row($result);
	$query = "SELECT iu.LastName,iu.FirstName,ig.score,ig.feedback,igi.name FROM imas_users as iu, imas_grades AS ig, imas_gbitems AS igi WHERE ";
	$query .= "iu.id=ig.userid AND igi.id=ig.gradetypeid AND ig.gradetype='offline' AND ig.id=:id AND ig.userid=:userid";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':id'=>$gid, ':userid'=>$userid));
	$row = $stm->fetch(PDO::FETCH_NUM);
	$pagetitle = "View Grade";
	require("../header.php");
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
	echo "&gt; <a href=\"gradebook.php?stu=0&gbmode=$gbmode&cid=$cid\">Gradebook</a> ";
	echo "&gt; View Grade</div>";

	echo '<div id="headerviewgrade" class="pagetitle"><h2>View Grade</h2></div>';
	echo "<p>Grade on <b>{$row[4]}</b> for <b>{$row[1]} {$row[0]}</b></p>";
	echo "<p>Grade: <b>{$row[2]}</b></p>";
	if (trim($row[3])!='') {
		echo "<p>Feedback:<br/>".$row[3].'</p>';
	}
	require("../footer.php");
?>

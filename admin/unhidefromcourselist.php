<?php
require("../validate.php");
$cid = intval($_GET['cid']);
if (!isset($_GET['type'])) {
	$type = 'take';
} else {
	$type = $_GET['type'];
}
if ($type=='teach') {
	$typename = "Teaching";
	$table = 'imas_teachers';
} else if ($type=='tutor') {
	$typename = "Tutoring";
	$table = 'imas_tutors';
} else {
	$typename = "Taking";
	$table = 'imas_students';
}

$pagetitle = "Unhide Courses You're $typename from Course List";
$curBreadcrumb = "$breadcrumbbase Unhide Courses\n";
require("../header.php");

echo '<div class=breadcrumb>'.$curBreadcrumb.'</div>';
echo '<h2>Return Hidden Courses You\'re '.$typename.' to Course List</h2>';

if (isset($_GET['cid'])) {
	if ($cid>0) {
		//DB $query = "UPDATE imas_students SET hidefromcourselist=0 WHERE courseid='$cid' AND userid='$userid'";
		//DB mysql_query($query) or die("Query failed : $query" . mysql_error());
		$stm = $DBH->prepare("UPDATE $table SET hidefromcourselist=0 WHERE courseid=:courseid AND userid=:userid");
		$stm->execute(array(':courseid'=>$cid, ':userid'=>$userid));
	}
}

//DB $query = 'SELECT ic.name,ic.id FROM imas_courses AS ic JOIN imas_students AS istu ON ic.id=istu.courseid ';
//DB $query .= "WHERE istu.userid='$userid' AND istu.hidefromcourselist=1";
//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
$query = 'SELECT ic.name,ic.id FROM imas_courses AS ic JOIN '.$table.' AS istu ON ic.id=istu.courseid ';
$query .= "WHERE istu.userid=:userid AND istu.hidefromcourselist=1 ORDER BY ic.name";
$stm = $DBH->prepare($query);
$stm->execute(array(':userid'=>$userid));
echo '<ul class="nomark">';
//DB if (mysql_num_rows($result)==0) {
if ($stm->rowCount()==0) {
	echo '<li>No courses to unhide</li>';
} else {
	//DB while ($row = mysql_fetch_row($result)) {
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		echo '<li>'.$row[0].' <a href="unhidefromcourselist.php?type='.$type.'&cid='.$row[1].'">Unhide</a></li>';
	}
}
echo '</ul>';
echo '<a href="../index.php">Back to Home Page</a>';
require("../footer.php");

?>

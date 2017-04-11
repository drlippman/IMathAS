<?php
require("../validate.php");
$cid = Sanitize::courseId($_GET['cid']);
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
	$type = 'take';
}

$pagetitle = "View Hidden Courses You're $typename from Course List";
$curBreadcrumb = "$breadcrumbbase Unhide Courses\n";
require("../header.php");

echo '<div class=breadcrumb>'.$curBreadcrumb.'</div>';
echo '<h2>View Hidden Courses You\'re '.$typename.'</h2>';

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
$query = 'SELECT ic.name,ic.id,ic.ownerid FROM imas_courses AS ic JOIN '.$table.' AS istu ON ic.id=istu.courseid ';
$query .= "WHERE istu.userid=:userid AND istu.hidefromcourselist=1 ";
if ($type=='take') {
	$query .= "AND ic.available=0 ";
} else {
	$query .= "AND ic.available<4 ";
}
$query .= "ORDER BY ic.name";
$stm = $DBH->prepare($query);
$stm->execute(array(':userid'=>$userid));
echo '<ul class="nomark">';
//DB if (mysql_num_rows($result)==0) {
if ($stm->rowCount()==0) {
	echo '<li>No hidden courses</li>';
} else {
	//DB while ($row = mysql_fetch_row($result)) {
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		echo '<li>';
		
		if ($type=='teach') {
			echo ' <span class="dropdown"><a role="button" tabindex=0 class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
			echo '<img src="../img/gears.png" alt="Options" class="mida"/></a>';
			echo '<ul role="menu" class="dropdown-menu">';
			echo ' <li><a href="unhidefromcourselist.php?type='.$type.'&cid='.$row[1].'">'._('Un-hide from course list').'</a></li>';
			if ($row[2]==$userid) {
				echo ' <li><a href="forms.php?from=home&action=modify&id='.$row[1].'">'._('Settings').'</a></li>';
				echo '<li><a href="forms.php?from=home&action=chgteachers&id='.$row[1].'">'._('Add/remove teachers').'</a></li>';
				echo ' <li><a href="forms.php?from=home&action=transfer&id='.$row[1].'">'._('Transfer ownership').'</a></li>';
				echo ' <li><a href="forms.php?from=home&action=delete&id='.$row[1].'">'._('Delete').'</a></li>';
			}
			echo '</ul></span> ';
			echo '<a href="../course/course.php?cid='.$row[1].'">'.$row[0].'</a> ';
		} else {
			echo '<a href="../course/course.php?cid='.$row[1].'">'.$row[0].'</a> ';
			echo ' <a href="unhidefromcourselist.php?type='.$type.'&cid='.$row[1].'" class="small">Unhide</a>';
		}
		
		echo '</li>';
	}
}
echo '</ul>';
echo '<a href="../index.php">Back to Home Page</a>';
require("../footer.php");

?>

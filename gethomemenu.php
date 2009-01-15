<?php
//returns list of courses for switch-to menu
//called via AJAX
//IMathAS
require("validate.php");
echo '<b>Switch to:</b><ul class="nomark">';
$query = "SELECT imas_courses.name,imas_courses.id FROM imas_teachers,imas_courses ";
$query .= "WHERE imas_teachers.courseid=imas_courses.id AND imas_teachers.userid='$userid' ";
$query .= "AND (imas_courses.available=0 OR imas_courses.available=1) ";
$query .= "UNION SELECT imas_courses.name,imas_courses.id FROM imas_students,imas_courses ";
$query .= "WHERE imas_students.courseid=imas_courses.id AND imas_students.userid='$userid' ";
$query .= "AND (imas_courses.available=0 OR imas_courses.available=2) ";
$query .= "ORDER BY name";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
while ($row = mysql_fetch_row($result)) {
	echo "<li><a href=\"$imasroot/course/course.php?cid={$row[1]}\">{$row[0]}</a></li>";
}
echo "<li><a href=\"$imasroot/actions.php?action=logout\">Log Out</a></li>";
echo '</ul>';

?>

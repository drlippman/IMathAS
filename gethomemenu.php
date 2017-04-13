<?php
//returns list of courses for switch-to menu
//called via AJAX
//IMathAS
require("validate.php");

echo '<b>Switch to:</b><ul class="nomark">';
$query = "SELECT imas_courses.name,imas_courses.id FROM imas_teachers,imas_courses ";
$query .= "WHERE imas_teachers.courseid=imas_courses.id AND imas_teachers.userid=:userid AND imas_teachers.hidefromcourselist=0 ";
$query .= "AND (imas_courses.available=0 OR imas_courses.available=1) ";
$query .= "UNION SELECT imas_courses.name,imas_courses.id FROM imas_students,imas_courses ";
$query .= "WHERE imas_students.courseid=imas_courses.id AND imas_students.userid=:useridB AND imas_students.hidefromcourselist=0 ";
$query .= "AND (imas_courses.available=0 OR imas_courses.available=2) ";
$query .= "UNION SELECT imas_courses.name,imas_courses.id FROM imas_tutors,imas_courses ";
$query .= "WHERE imas_tutors.courseid=imas_courses.id AND imas_tutors.userid=:useridC AND imas_tutors.hidefromcourselist=0 ";
$query .= "AND (imas_courses.available=0 OR imas_courses.available=1) ";
$query .= "ORDER BY name";
$stm = $DBH->prepare($query);
$stm->execute(array(':userid'=>$userid, ':useridB'=>$userid, ':useridC'=>$userid));
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	echo "<li><a href=\"$imasroot/course/course.php?cid={$row[1]}&folder=0\">{$row[0]}</a></li>";
}
echo "<li><a href=\"$imasroot/actions.php?action=logout\">Log Out</a></li>";
echo '</ul>';

?>

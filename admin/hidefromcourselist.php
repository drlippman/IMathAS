<?php
require("../validate.php");
$cid = Sanitize::courseId($_GET['cid']);
if (!isset($_GET['type'])) {
	$type = 'take';
} else {
	$type = $_GET['type'];
}
if ($type=='teach') {
	$table = 'imas_teachers';
} else if ($type=='tutor') {
	$table = 'imas_tutors';
} else {
	$table = 'imas_students';
}
//DB $query = "UPDATE imas_students SET hidefromcourselist=1 WHERE courseid='$cid' AND userid='$userid'";
//DB mysql_query($query) or die("Query failed : $query" . mysql_error());
//DB if (mysql_affected_rows()>0) {
$stm = $DBH->prepare("UPDATE $table SET hidefromcourselist=1 WHERE courseid=:courseid AND userid=:userid");
$stm->execute(array(':courseid'=>$cid, ':userid'=>$userid));
if ($stm->rowCount()>0) {
	echo "OK";
} else {
	echo "FAIL";
}
?>

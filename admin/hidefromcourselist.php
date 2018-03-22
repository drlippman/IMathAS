<?php
require("../init.php");
$tohide = Sanitize::courseId($_GET['tohide']);
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
$actionuserid = $userid;
if ($myrights==100 && isset($_GET['user'])) {
	$actionuserid = Sanitize::onlyInt($_GET['user']);
} else if ($myrights>=75 && isset($_GET['user'])) {
	$stm = $DBH->prepare("SELECT groupid FROM imas_users WHERE id=:id");
	$stm->execute(array(':id'=>Sanitize::onlyInt($_GET['user'])));
	if ($groupid==$stm->fetchColumn(0)) {
		$actionuserid = Sanitize::onlyInt($_GET['user']);
	}
}
//DB $query = "UPDATE imas_students SET hidefromcourselist=1 WHERE courseid='$cid' AND userid='$userid'";
//DB mysql_query($query) or die("Query failed : $query" . mysql_error());
//DB if (mysql_affected_rows()>0) {
$stm = $DBH->prepare("UPDATE $table SET hidefromcourselist=1 WHERE courseid=:courseid AND userid=:userid");
$stm->execute(array(':courseid'=>$tohide, ':userid'=>$actionuserid));
if ($stm->rowCount()>0) {
	echo "OK";
} else {
	echo "FAIL";
}
?>

<?php
require("../validate.php");
$cid = intval($_GET['cid']);
//DB $query = "UPDATE imas_students SET hidefromcourselist=1 WHERE courseid='$cid' AND userid='$userid'";
//DB mysql_query($query) or die("Query failed : $query" . mysql_error());
//DB if (mysql_affected_rows()>0) {
$stm = $DBH->prepare("UPDATE imas_students SET hidefromcourselist=1 WHERE courseid=:courseid AND userid=:userid");
$stm->execute(array(':courseid'=>$cid, ':userid'=>$userid));
if ($stm->rowCount()>0) {
	echo "OK";
} else {
	echo "FAIL";
}
?>

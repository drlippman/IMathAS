<?php
require("../validate.php");
$cid = intval($_GET['cid']);
$query = "UPDATE imas_students SET hidefromcourselist=1 WHERE courseid='$cid' AND userid='$userid'";
mysql_query($query) or die("Query failed : $query" . mysql_error());
if (mysql_affected_rows()>0) {
	echo "OK";
} else {
	echo "FAIL";
}
?>

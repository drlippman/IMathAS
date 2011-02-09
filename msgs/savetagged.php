<?php
//IMathAS.  Records tags/untags for messages
//(c) 2007 David Lippman

require("../validate.php");

if (!isset($_GET['threadid'])) {
	exit;
}

$ischanged = false;

$query = "UPDATE imas_msgs SET isread=(isread^8) WHERE msgto='$userid' AND id='{$_GET['threadid']}'";
mysql_query($query) or die("Query failed : $query " . mysql_error());
if (mysql_affected_rows()>0) {
	$ischanged = true;
}

if ($ischanged) {
	echo "OK";
} else {
	echo "Error";
}


?>

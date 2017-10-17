<?php
//IMathAS.  Records tags/untags for messages
//(c) 2007 David Lippman

require("../init.php");

if (!isset($_GET['threadid'])) {
	exit;
}

$ischanged = false;

//DB $query = "UPDATE imas_msgs SET isread=(isread^8) WHERE msgto='$userid' AND id='{$_GET['threadid']}'";
//DB mysql_query($query) or die("Query failed : $query " . mysql_error());
//DB if (mysql_affected_rows()>0) {
$stm = $DBH->prepare("UPDATE imas_msgs SET isread=(isread^8) WHERE msgto=:msgto AND id=:id");
$stm->execute(array(':msgto'=>$userid, ':id'=>$_GET['threadid']));
if ($stm->rowCount()>0) {
	$ischanged = true;
}

if ($ischanged) {
	echo "OK";
} else {
	echo "Error";
}


?>

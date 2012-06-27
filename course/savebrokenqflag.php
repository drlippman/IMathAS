<?php
//IMathAS.  Records broken question flag
//(c) 2010 David Lippman

require("../validate.php");

if (!isset($_GET['qsetid']) || $myrights<20) {
	exit;
}

$ischanged = false;

$query = "UPDATE imas_questionset SET broken='{$_GET['flag']}' WHERE id='{$_GET['qsetid']}'";
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

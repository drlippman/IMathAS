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
	if ($_GET['flag']==1) {
		$now = time();
		$msg = addslashes('Question '.intval($_GET['qsetid']).' marked broken by '.$userfullname);
		$query = "INSERT INTO imas_log (time,log) VALUES($now,'$msg')";
		mysql_query($query) or die("Query failed : $query " . mysql_error());
	}
}

if ($ischanged) {
	echo "OK";
} else {
	echo "Error";
}


?>

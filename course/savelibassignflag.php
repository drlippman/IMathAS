<?php
//IMathAS.  Records library items junk tag
//(c) 2010 David Lippman

require("../validate.php");

if (!isset($_GET['libitemid']) || $myrights<20) {
	exit;
}

$ischanged = false;

$query = "UPDATE imas_library_items SET junkflag='{$_GET['flag']}' WHERE id='{$_GET['libitemid']}'";
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

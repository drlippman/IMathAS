<?php
//IMathAS.  Records library items junk tag
//(c) 2010 David Lippman

require("../init.php");

if (!isset($_GET['libitemid']) || $myrights<20) {
	exit;
}

$ischanged = false;

//DB $query = "UPDATE imas_library_items SET junkflag='{$_GET['flag']}' WHERE id='{$_GET['libitemid']}'";
//DB mysql_query($query) or die("Query failed : $query " . mysql_error());
//DB if (mysql_affected_rows()>0) {
$stm = $DBH->prepare("UPDATE imas_library_items SET junkflag=:junkflag WHERE id=:id");
$stm->execute(array(':junkflag'=>$_GET['flag'], ':id'=>$_GET['libitemid']));
if ($stm->rowCount()>0) {
	$ischanged = true;
}

if ($ischanged) {
	echo "OK";
} else {
	echo "Error";
}


?>

<?php
//IMathAS.  Records library items junk tag
//(c) 2010 David Lippman

require("../init.php");

if (!isset($_GET['libitemid']) || $myrights<20) {
	exit;
}

$ischanged = false;
$now = time();

$stm = $DBH->prepare("UPDATE imas_library_items SET junkflag=:junkflag,lastmoddate=:lastmoddate WHERE id=:id");
$stm->execute(array(':junkflag'=>$_GET['flag'], ':lastmoddate'=>$now, ':id'=>$_GET['libitemid']));
if ($stm->rowCount()>0) {
	$ischanged = true;
}

if ($ischanged) {
	echo "OK";
} else {
	echo "Error";
}


?>

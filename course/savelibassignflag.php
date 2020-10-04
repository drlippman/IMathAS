<?php
//IMathAS.  Records library items junk tag
//(c) 2010 David Lippman

require("../init.php");

if ((!isset($_REQUEST['libitemid']) && !isset($_REQUEST['libitemids'])) || $myrights<20) {
	exit;
}

$ischanged = [];
$now = time();

if (isset($_REQUEST['libitemid'])) {
    $libitemids = [$_REQUEST['libitemid']];
    $flags = [$_REQUEST['flag']];
} else {
    $libitemids = explode(',', $_REQUEST['libitemids']);
    $flags = explode(',', $_REQUEST['flags']);
}

for ($i=0; $i<count($libitemids); $i++) {
    $stm = $DBH->prepare("UPDATE imas_library_items SET junkflag=:junkflag,lastmoddate=:lastmoddate WHERE id=:id");
    $stm->execute(array(':junkflag'=>$flags[$i], ':lastmoddate'=>$now, ':id'=>$libitemids[$i]));
    if ($stm->rowCount()>0) {
        $ischanged[$i] = 'OK';
    } else {
        $ischanged[$i] = 'Error';
    }
}

if (isset($_REQUEST['libitemid'])) {
	echo $ischanged[0];
} else {
	echo implode(',', $ischanged);
}


?>

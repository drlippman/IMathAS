<?php
//save calendar drag-and-drop

require("../init.php");

header('Content-Type: application/json');

if (!isset($teacherid)) {
	echo '{"res":"error", "error":"invalid user rights"}';
	exit;
}
if (!isset($_GET['item']) || !isset($_GET['dest'])) {
	echo '{"res":"error", "error":"missing item or destination"}';
	exit;
}

$itemtype = $_GET['item'][0];
$itempart = $_GET['item'][1];
$itemid = intval(substr($_GET['item'],2));
if ($itemid==0) {
	echo '{"res":"error", "error":"invalid item id"}';
	exit;
}

list($year,$month,$day) = explode('-', $_GET['dest']);

switch ($itemtype) {
	case 'A': $table = 'imas_assessments'; break;
	case 'F': $table = 'imas_forums'; break;
	case 'I': $table = 'imas_inlinetext'; break;
	case 'L': $table = 'imas_linkedtext'; break;
	case 'D': $table = 'imas_drillassess'; break;
	case 'C': $table = 'imas_calitems'; break;
	case 'B': $table = 'imas_courses'; break;
	default: echo '{"res":"error", "error":"invalid item type"}'; exit;
}

switch ($itempart) {
	case 'O':
	case 'S': $field = 'startdate'; break;
	case 'E': $field = 'enddate'; break;
	case 'P': $field = 'postby'; break;
	case 'R': $field = $itemtype=='A'?'reviewdate':'replyby'; break;
	case 'D': $field = 'date'; break;
	default: echo '{"res":"error", "error":"invalid item date part"}'; exit;
}
if ($itemtype == 'B') {
	// handle blocks separately
	$stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
	$stm->execute(array(':id'=>$cid));
	$items = unserialize($stm->fetchColumn(0));
	$idpts = explode(';', substr($_GET['item'],2));
	$blocktree = explode('-', $idpts[1]);
	$sub = &$items;
	for ($i = 1; $i < count($blocktree)-1; $i++) {
		$sub = &$items[$blocktree[$i]]['items'];
	}
	$block = &$sub[$blocktree[count($blocktree)-1]];
	if ($block['id'] == $idpts[0]) {
		if ($itempart == 'S') {
			$block['startdate'] = adjustDate($block['startdate'], $year,$month,$day);
		} else if ($itempart == 'E') {
			$block['enddate'] = adjustDate($block['enddate'], $year,$month,$day);
		}
		$stm = $DBH->prepare("UPDATE imas_courses SET itemorder=:itemorder WHERE id=:courseid");
		$stm->execute(array(':courseid'=>$cid, ':itemorder'=>serialize($items)));
		echo '{"res":"ok","success":"Set block '.$field.'"}';
	} else {
		echo '{"res":"error", "error":"Could not find block"}';
	}
	exit;
}
$stm = $DBH->prepare("SELECT $field FROM $table WHERE id=:id AND courseid=:courseid");
$stm->execute(array(':id'=>$itemid, ':courseid'=>$cid));
if ($stm->rowCount()==0) {
	echo '{"res":"error", "error":"invalid item id"}';
	exit;
}
$row = $stm->fetch(PDO::FETCH_NUM);
$newdate = adjustDate($row[0],$year,$month,$day);
$stm = $DBH->prepare("UPDATE $table set $field=:field WHERE id=:id AND courseid=:courseid");
$stm->execute(array(':field'=>$newdate, ':id'=>$itemid, ':courseid'=>$cid));
echo '{"res":"ok","success":"In '.$table.' SET '.$field.' to '.$newdate.'"}';

function adjustDate($val,$year,$month,$day) {
	global $tzname, $tzoffset;
	list($hour,$min) = explode('-', tzdate('G-i', $val));
	//adjust timezone based on offset if name isn't being used
	if ($tzname=='') {
		$serveroffset = date('Z')/60 + $tzoffset;
		$min += $serveroffset;
	}
	return mktime($hour, $min, 0, $month, $day, $year);
}
?>

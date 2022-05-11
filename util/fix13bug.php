<?php
require("../init.php");

// one time fix for badly copied LTI1.3 courses
if ($myrights<100) {
	exit;
}

$stm = $DBH->query("SELECT DISTINCT courseid FROM imas_lti_courses WHERE org LIKE 'LTI13-%'");
$courses = $stm->fetchAll(PDO::FETCH_COLUMN, 0);

$getstm = $DBH->prepare("SELECT itemorder FROm imas_courses WHERE id=?");
$updstm = $DBH->prepare("UPDATE imas_courses SET itemorder=:itemorder,blockcnt=:blockcnt WHERE id=:id");

function fixblocks(&$itemlist) {
	global $blockcnt;
	foreach ($itemlist as $k=>$v) {
		if (is_array($v)) { //is block
			$itemlist[$k]['id'] = $blockcnt;
			$blockcnt++;
			fixblocks($itemlist[$k]['items']);
		}
	}
}

foreach ($courses as $cid) {
    $getstm->execute([$cid]);
    $items = unserialize($getstm->fetchColumn(0));
    $blockcnt = 1;
    fixblocks($items);
    $itemorder = serialize($items);
    $updstm->execute(array(':itemorder'=>$itemorder, ':blockcnt'=>$blockcnt, ':id'=>$cid));
}

echo "Done " . count($courses);


?>

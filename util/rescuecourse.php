<?php
require_once "../init.php";
if ($myrights<100) {
	exit;
}
if (isset($_REQUEST['cid'])) {
	$stm = $DBH->prepare("SELECT itemorder,blockcnt FROM imas_courses WHERE id=:id");
	$stm->execute(array(':id'=>$_REQUEST['cid']));
	list($itemorder, $blockcnt) = $stm->fetch(PDO::FETCH_NUM);
	$items = unserialize($itemorder);
	if ($items===false) {$items = array();}
} else {
	exit;
}

$allitems = array();
$stm = $DBH->prepare("SELECT id FROM imas_items WHERE courseid=:courseid");
$stm->execute(array(':courseid'=>$_REQUEST['cid']));
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	$allitems[] = $row[0];
}

$itemsfnd = array();
function fixsub(&$items) {
	global $allitems,$itemsfnd;
	foreach($items as $k=>$item) {
		if ($item==null) {
			unset($items[$k]);
		} else if (is_array($item)) {
			if (!isset($item['items']) || !is_array($item['items'])) {
				unset($items[$k]);
			} else if (count($item['items'])>0) {
				fixsub($items[$k]['items']);
			}
		} else {
			if ($item==null || $item=='') {
				unset($items[$k]);
			} else if (!in_array($item,$allitems)) {
				unset($items[$k]);
				echo "Removed unused item from itemorder<br/>";
			} else {
				$itemsfnd[] = $item;
			}
		}
	}
	$items = array_values($items);
}
fixsub($items);

$recovereditems = array();

foreach ($allitems as $item) {
	if (!in_array($item,$itemsfnd)) {
		$recovereditems[] = $item;
	}
}

if (count($recovereditems)>0) {
	$block = array();
	$block['name'] = "Recovered items";
	$block['id'] = $blockcnt;
	$block['startdate'] = 0;
	$block['enddate'] = 2000000000;
	$block['avail'] = 0;
	$block['SH'] = "HO";
	$block['colors'] = '';
	$block['fixedheight'] = 0;
	$block['public'] = 0;
	$block['items'] = $recovereditems;
	array_push($items,$block);
	echo "recovered ". count($recovereditems) . "items";
} 

$blockcnt = 1;

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

fixblocks($items);

$itemorder = serialize($items);
$stm = $DBH->prepare("UPDATE imas_courses SET itemorder=:itemorder,blockcnt=:blockcnt WHERE id=:id");
$stm->execute(array(':itemorder'=>$itemorder, ':blockcnt'=>$blockcnt, ':id'=>$_REQUEST['cid']));

echo "Done";



//print_r($items);


?>

<?php
require("../init.php");
if ($myrights<100) {
	exit;
}
if (isset($_REQUEST['cid'])) {
	//DB $query = "SELECT itemorder,blockcnt FROM imas_courses WHERE id='{$_GET['cid']}'";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB $items = unserialize(mysql_result($result,0,0));
	//DB $blockcnt = mysql_result($result,0,1);
	$stm = $DBH->prepare("SELECT itemorder,blockcnt FROM imas_courses WHERE id=:id");
	$stm->execute(array(':id'=>$_REQUEST['cid']));
	list($itemorder, $blockcnt) = $stm->fetch(PDO::FETCH_NUM);
	$items = unserialize($itemorder);
	if ($items===false) {$items = array();}
} else {
	exit;
}

$allitems = array();
//DB $query = "SELECT id FROM imas_items WHERE courseid='{$_GET['cid']}'";
//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
//DB while ($row = mysql_fetch_row($result)) {
$stm = $DBH->prepare("SELECT id FROM imas_items WHERE courseid=:courseid");
$stm->execute(array(':courseid'=>$_REQUEST['cid']));
//DB while ($row = mysql_fetch_row($result)) {
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
	print_r($items);
	//DB $itemorder = addslashes(serialize($items));
	$itemorder = serialize($items);
	//DB $query = "UPDATE imas_courses SET itemorder='$itemorder',blockcnt=blockcnt+1 WHERE id='{$_GET['cid']}'";
	//DB mysql_query($query) or die("Query failed : $query" . mysql_error());
	$stm = $DBH->prepare("UPDATE imas_courses SET itemorder=:itemorder,blockcnt=blockcnt+1 WHERE id=:id");
	$stm->execute(array(':itemorder'=>$itemorder, ':id'=>$_REQUEST['cid']));
} else {
	//DB $itemorder = addslashes(serialize($items));
	$itemorder = serialize($items);
	//DB $query = "UPDATE imas_courses SET itemorder='$itemorder' WHERE id='{$_GET['cid']}'";
	//DB mysql_query($query) or die("Query failed : $query" . mysql_error());
	$stm = $DBH->prepare("UPDATE imas_courses SET itemorder=:itemorder WHERE id=:id");
	$stm->execute(array(':itemorder'=>$itemorder, ':id'=>$_REQUEST['cid']));
}

echo "Done";



//print_r($items);


?>

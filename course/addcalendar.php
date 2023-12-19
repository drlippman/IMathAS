<?php
//IMathAS:  Add/remove calendar item
//(c) 2006 David Lippman

/*** master php includes *******/
require_once "../init.php";

/*** pre-html data manipulation, including function code *******/

//set some page specific variables and counters
$overwriteBody = 0;
$body = "";
if (isset($_GET['tb'])) {
	$totb = $_GET['tb'];
} else {
	$totb = 'b';
}

if (!(isset($teacherid))) {
	$overwriteBody = 1;
	$body = "You need to log in as a teacher to access this page";
} elseif (!(isset($_GET['cid']))) {
	$overwriteBody = 1;
	$body = "You need to access this page from the link on the course page";
} elseif (isset($_GET['remove'])) { // a valid delete request loaded the page
	require_once "delitembyid.php";
	$DBH->beginTransaction();
	$cid = Sanitize::courseId($_GET['cid']);
	$block = $_GET['block'];

	$itemid = Sanitize::onlyInt($_GET['id']);
	delitembyid($itemid);
	removeItemFromItemorder($cid,$itemid,$block);
	$DBH->commit();
} else {
	$DBH->beginTransaction();
	$block = $_GET['block'];
	$cid = Sanitize::courseId($_GET['cid']);
	$query = "INSERT INTO imas_items (courseid,itemtype) VALUES ";
	$query .= "(:courseid, 'Calendar');";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':courseid'=>$cid));
	$itemid = $DBH->lastInsertId();
	$stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
	$stm->execute(array(':id'=>$cid));
	$line = $stm->fetch(PDO::FETCH_ASSOC);
	$items = unserialize($line['itemorder']);

	$blocktree = explode('-',$block);
	$sub =& $items;
	for ($i=1;$i<count($blocktree);$i++) {
		$sub =& $sub[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
	}
	if ($totb=='b') {
		$sub[] = $itemid;
	} else if ($totb=='t') {
		array_unshift($sub,$itemid);
	}
	$itemorder = serialize($items);
	$stm = $DBH->prepare("UPDATE imas_courses SET itemorder=:itemorder WHERE id=:id");
	$stm->execute(array(':itemorder'=>$itemorder, ':id'=>$cid));
	$DBH->commit();
}
$btf = isset($_GET['btf']) ? '&folder=' . Sanitize::encodeUrlParam($_GET['btf']) : '';
header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?cid=$cid$btf");
exit;

?>

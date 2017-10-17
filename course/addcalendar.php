<?php
//IMathAS:  Add/remove calendar item
//(c) 2006 David Lippman

/*** master php includes *******/
require("../init.php");

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
	$cid = Sanitize::courseId($_GET['cid']);
	$block = $_GET['block'];

	$itemid = Sanitize::onlyInt($_GET['id']);

	//DB $query = "DELETE FROM imas_items WHERE id='$itemid'";
	//DB mysql_query($query) or die("Query failed : " . mysql_error());
	$stm = $DBH->prepare("DELETE FROM imas_items WHERE id=:id");
	$stm->execute(array(':id'=>$itemid));

	//DB $query = "SELECT itemorder FROM imas_courses WHERE id='$cid'";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB $items = unserialize(mysql_result($result,0,0));
	$stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
	$stm->execute(array(':id'=>$cid));
	$items = unserialize($stm->fetchColumn(0));

	$blocktree = explode('-',$block);
	$sub =& $items;
	for ($i=1;$i<count($blocktree);$i++) {
		$sub =& $sub[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
	}
	$key = array_search($itemid,$sub);
	array_splice($sub,$key,1);
	//DB $itemorder = addslashes(serialize($items));
	$itemorder = serialize($items);
	//DB $query = "UPDATE imas_courses SET itemorder='$itemorder' WHERE id='$cid'";
	//DB mysql_query($query) or die("Query failed : " . mysql_error());
	$stm = $DBH->prepare("UPDATE imas_courses SET itemorder=:itemorder WHERE id=:id");
	$stm->execute(array(':itemorder'=>$itemorder, ':id'=>$cid));
} else {
	$block = $_GET['block'];
	$cid = Sanitize::courseId($_GET['cid']);

	//DB $query = "INSERT INTO imas_items (courseid,itemtype) VALUES ";
	//DB $query .= "('$cid','Calendar');";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	$query = "INSERT INTO imas_items (courseid,itemtype) VALUES ";
	$query .= "(:courseid, 'Calendar');";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':courseid'=>$cid));
	//DB $itemid = mysql_insert_id();
	$itemid = $DBH->lastInsertId();

	//DB $query = "SELECT itemorder FROM imas_courses WHERE id='$cid'";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB $line = mysql_fetch_array($result, MYSQL_ASSOC);
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

	//DB $itemorder = addslashes(serialize($items));
	$itemorder = serialize($items);
	//DB $query = "UPDATE imas_courses SET itemorder='$itemorder' WHERE id='$cid'";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	$stm = $DBH->prepare("UPDATE imas_courses SET itemorder=:itemorder WHERE id=:id");
	$stm->execute(array(':itemorder'=>$itemorder, ':id'=>$cid));

}
header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?cid=$cid");
exit;

?>

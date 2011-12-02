<?php
//IMathAS:  Add/remove calendar item
//(c) 2006 David Lippman

/*** master php includes *******/
require("../validate.php");

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
	$cid = $_GET['cid'];
	$block = $_GET['block'];	
	
	$itemid = $_GET['id'];
	
	$query = "DELETE FROM imas_items WHERE id='$itemid'";
	mysql_query($query) or die("Query failed : " . mysql_error());
			
	$query = "SELECT itemorder FROM imas_courses WHERE id='$cid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$items = unserialize(mysql_result($result,0,0));
	
	$blocktree = explode('-',$block);
	$sub =& $items;
	for ($i=1;$i<count($blocktree);$i++) {
		$sub =& $sub[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
	}
	$key = array_search($itemid,$sub);
	array_splice($sub,$key,1);
	$itemorder = addslashes(serialize($items));
	$query = "UPDATE imas_courses SET itemorder='$itemorder' WHERE id='$cid'";
	mysql_query($query) or die("Query failed : " . mysql_error());
} else {
	$block = $_GET['block'];
	$cid = $_GET['cid'];
	
	$query = "INSERT INTO imas_items (courseid,itemtype) VALUES ";
	$query .= "('$cid','Calendar');";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	
	$itemid = mysql_insert_id();
	$query = "SELECT itemorder FROM imas_courses WHERE id='$cid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$line = mysql_fetch_array($result, MYSQL_ASSOC);
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
	
	$itemorder = addslashes(serialize($items));
	$query = "UPDATE imas_courses SET itemorder='$itemorder' WHERE id='$cid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	
} 
header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/course.php?cid=$cid");
exit;

?>

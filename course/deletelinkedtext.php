<?php
//IMathAS:  Add/modify blocks of items on course page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../validate.php");

/*** pre-html data manipulation, including function code *******/

//set some page specific variables and counters
$overwriteBody = 0;
$body = "";

$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
$curBreadcrumb .= "&gt; Delete Link\n";
$pagetitle = "Delete Link";



if (!(isset($teacherid))) { // loaded by a NON-teacher
	$overwriteBody=1;
	$body = "You need to log in as a teacher to access this page";
} elseif (!(isset($_GET['cid']))) {
	$overwriteBody=1;
	$body = "You need to access this page from the course page menu";
} else { // PERMISSIONS ARE OK, PROCEED WITH PROCESSING
	$cid = $_GET['cid'];
	$block = $_GET['block'];	
	
	if ($_GET['remove']=="really") {
		require("../includes/filehandler.php");
		
		$textid = $_GET['id'];
		
		$query = "SELECT id FROM imas_items WHERE typeid='$textid' AND itemtype='LinkedText'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$itemid = mysql_result($result,0,0);
		
		$query = "DELETE FROM imas_items WHERE id='$itemid'";
		mysql_query($query) or die("Query failed : " . mysql_error());
		
		$query = "SELECT text FROM imas_linkedtext WHERE id='$textid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$text = trim(mysql_result($result,0,0));
		if (substr($text,0,5)=='file:') { //delete file if not used
			$safetext = addslashes($text);
			$query = "SELECT id FROM imas_linkedtext WHERE text='$safetext'"; //any others using file?
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_num_rows($result)==1) { 
				/*$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/files/';
				$filename = substr($text,5);
				if (file_exists($uploaddir . $filename)) {
					unlink($uploaddir . $filename);
				}*/
				deletecoursefile(substr($text,5));
			}
		}
		
		$query = "DELETE FROM imas_linkedtext WHERE id='$textid'";
		mysql_query($query) or die("Query failed : " . mysql_error());
					
		$query = "SELECT itemorder FROM imas_courses WHERE id='{$_GET['cid']}'";
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
		
		header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/course.php?cid={$_GET['cid']}");
	
		exit;
	} else {
		$query = "SELECT title FROM imas_linkedtext WHERE id='{$_GET['id']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$itemname = mysql_result($result,0,0);
	}
}
	
	

	
/******* begin html output ********/
require("../header.php");

if ($overwriteBody==1) {
	echo $body;
} else {
?>

	<div class=breadcrumb><?php echo $curBreadcrumb ?></div>
	<h3><?php echo $itemname; ?></h3>
	Are you SURE you want to delete this link item?
	<p><input type=button value="Yes, Delete" onClick="window.location='deletelinkedtext.php?cid=<?php echo $_GET['cid'] ?>&block=<?php echo $block ?>&id=<?php echo $_GET['id'] ?>&remove=really'">
	<input type=button value="Nevermind" onClick="window.location='course.php?cid=<?php echo $_GET['cid'] ?>'"></p>

<?php
}
require("../footer.php");
?> 	

	
<?php
//IMathAS:  Delete a wiki
//(c) 2010 David Lippman

/*** master php includes *******/
require("../validate.php");


/*** pre-html data manipulation, including function code *******/

//set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = "Delete Wiki";
$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; Delete Wiki";

if (!(isset($_GET['cid'])) || !(isset($_GET['block']))) { //if the cid is missing go back to the index page
	$overwriteBody = 1;
	$body = "You need to access this page from the link on the course page";
} elseif (!(isset($teacherid))) {  //there is a cid but the user isn't a teacher
	$overwriteBody = 1;
	$body = "You need to log in as a teacher to access this page";
} elseif (isset($_GET['remove'])) { // a valid delete request loaded the page
	$cid = $_GET['cid'];
	$block = $_GET['block'];

	if ($_GET['remove']=="really") {
		$wikiid = $_GET['id'];
		$DBH->beginTransaction();
		//DB $query = "SELECT id FROM imas_items WHERE typeid='$wikiid' AND itemtype='Wiki' AND courseid='$cid'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $itemid = mysql_result($result,0,0);
		$stm = $DBH->prepare("SELECT id FROM imas_items WHERE typeid=:typeid AND itemtype='Wiki' AND courseid=:courseid");
		$stm->execute(array(':typeid'=>$wikiid, ':courseid'=>$cid));
		if ($stm->rowCount()>0) {
			$itemid = $stm->fetchColumn(0);

			//DB $query = "DELETE FROM imas_items WHERE id='$itemid'";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("DELETE FROM imas_items WHERE id=:id");
			$stm->execute(array(':id'=>$itemid));

			//DB $query = "DELETE FROM imas_wikis WHERE id='$wikiid'";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("DELETE FROM imas_wikis WHERE id=:id");
			$stm->execute(array(':id'=>$wikiid));

			//DB $query = "DELETE FROM imas_wiki_revisions WHERE wikiid='$wikiid'";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("DELETE FROM imas_wiki_revisions WHERE wikiid=:wikiid");
			$stm->execute(array(':wikiid'=>$wikiid));

			//DB $query = "DELETE FROM imas_wiki_views WHERE wikiid='$wikiid'";
			//DB mysql_query($query) or die("Query failed : $query " . mysql_error());
			$stm = $DBH->prepare("DELETE FROM imas_wiki_views WHERE wikiid=:wikiid");
			$stm->execute(array(':wikiid'=>$wikiid));

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
		}
		$DBH->commit();
		header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/course.php?cid={$_GET['cid']}");

		exit;
	} else {
		//DB $query = "SELECT name FROM imas_wikis WHERE id='{$_GET['id']}'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $itemname = mysql_result($result,0,0);
		$stm = $DBH->prepare("SELECT name FROM imas_wikis WHERE id=:id");
		$stm->execute(array(':id'=>$_GET['id']));
		$itemname = $stm->fetchColumn(0);
	}
}

/******* begin html output ********/
require("../header.php");

/**** post-html data manipulation ******/
// this page has no post-html data manipulation

/***** page body *****/
/***** php display blocks are interspersed throughout the html as needed ****/
if ($overwriteBody==1) {
	echo $body;
} else {
?>
	<div class=breadcrumb><?php echo $curBreadcrumb ?></div>
		<h3><?php echo $itemname; ?></h3>
		Are you SURE you want to delete this Wiki and all associated revisions?
		<p><input type=button value="Yes, Delete" onClick="window.location='deletewiki.php?cid=<?php echo $_GET['cid'] ?>&block=<?php echo $block ?>&id=<?php echo $_GET['id'] ?>&remove=really'">
		<input type=button value="Nevermind" class="secondarybtn" onClick="window.location='course.php?cid=<?php echo $_GET['cid'] ?>'">
		</p>

<?php
}
	require("../footer.php");
?>

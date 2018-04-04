<?php
//IMathAS:  Add/modify blocks of items on course page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../init.php");


/*** pre-html data manipulation, including function code *******/

//set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = "Delete Forum";
$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=" . Sanitize::courseId($_GET['cid'])."\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; Delete Forum";
$useeditor = "description";

if (!(isset($_GET['cid'])) || !(isset($_GET['block']))) { //if the cid is missing go back to the index page
	$overwriteBody = 1;
	$body = "You need to access this page from the link on the course page";
} elseif (!(isset($teacherid))) {  //there is a cid but the user isn't a teacher
	$overwriteBody = 1;
	$body = "You need to log in as a teacher to access this page";
} elseif (isset($_REQUEST['remove'])) { // a valid delete request loaded the page
	$cid = Sanitize::courseId($_GET['cid']);
	$block = Sanitize::stripHtmlTags($_GET['block']);

	if ($_POST['remove']=="really") {
		$forumid = Sanitize::onlyInt($_GET['id']);
		$DBH->beginTransaction();
		//DB $query = "SELECT id FROM imas_items WHERE typeid='$forumid' AND itemtype='Forum' AND courseid='$cid'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $itemid = mysql_result($result,0,0);
		$stm = $DBH->prepare("SELECT id FROM imas_items WHERE typeid=:typeid AND itemtype='Forum' AND courseid=:courseid");
		$stm->execute(array(':typeid'=>$forumid, ':courseid'=>$cid));
		if ($stm->rowCount()>0) {
			$itemid = $stm->fetchColumn(0);

			//DB $query = "DELETE FROM imas_items WHERE id='$itemid'";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("DELETE FROM imas_items WHERE id=:id");
			$stm->execute(array(':id'=>$itemid));

			//DB $query = "DELETE FROM imas_forums WHERE id='$forumid'";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("DELETE FROM imas_forums WHERE id=:id");
			$stm->execute(array(':id'=>$forumid));


			//DB $query = "SELECT itemorder FROM imas_courses WHERE id='{$_GET['cid']}'";
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
			if ($key!==false) {
				array_splice($sub,$key,1);
				//DB $itemorder = addslashes(serialize($items));
				$itemorder = serialize($items);
				//DB $query = "UPDATE imas_courses SET itemorder='$itemorder' WHERE id='$cid'";
				//DB mysql_query($query) or die("Query failed : " . mysql_error());
				$stm = $DBH->prepare("UPDATE imas_courses SET itemorder=:itemorder WHERE id=:id");
				$stm->execute(array(':itemorder'=>$itemorder, ':id'=>$cid));
			}

			//DB $query = "DELETE FROM imas_forum_subscriptions WHERE forumid='$forumid'";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("DELETE FROM imas_forum_subscriptions WHERE forumid=:forumid");
			$stm->execute(array(':forumid'=>$forumid));

			$stm = $DBH->prepare("DELETE FROM imas_exceptions WHERE assessmentid=:forumid AND (itemtype='F' OR itemtype='P' OR itemtype='R')");
			$stm->execute(array(':forumid'=>$forumid));

			require_once("../includes/filehandler.php");
			//DB $query = "SELECT id FROM imas_forum_posts WHERE forumid='$forumid' AND files<>''";
			//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			//DB while ($row = mysql_fetch_row($result)) {
			$stm = $DBH->prepare("SELECT id FROM imas_forum_posts WHERE forumid=:forumid AND files<>''");
			$stm->execute(array(':forumid'=>$forumid));
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				deleteallpostfiles($row[0]);
			}

			//$query = "DELETE FROM imas_forum_views WHERE threadid IN (SELECT id FROM imas_forum_threads WHERE forumid='$forumid')";
			//DB $query = "DELETE imas_forum_views FROM imas_forum_views JOIN imas_forum_threads ";
			//DB $query .= "ON imas_forum_views.threadid=imas_forum_threads.id  WHERE imas_forum_threads.forumid='$forumid'";
	 		//DB mysql_query($query) or die("Query failed : $query " . mysql_error());
			$query = "DELETE imas_forum_views FROM imas_forum_views JOIN imas_forum_threads ";
			$query .= "ON imas_forum_views.threadid=imas_forum_threads.id  WHERE imas_forum_threads.forumid=:forumid";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':forumid'=>$forumid));

			//DB $query = "DELETE FROM imas_forum_posts WHERE forumid='$forumid'";
			//DB mysql_query($query) or die("Query failed : $query " . mysql_error());
			$stm = $DBH->prepare("DELETE FROM imas_forum_posts WHERE forumid=:forumid");
			$stm->execute(array(':forumid'=>$forumid));

			//DB $query = "DELETE FROM imas_forum_threads WHERE forumid='$forumid'";
			//DB mysql_query($query) or die("Query failed : $query " . mysql_error());
			$stm = $DBH->prepare("DELETE FROM imas_forum_threads WHERE forumid=:forumid");
			$stm->execute(array(':forumid'=>$forumid));
		}
		$DBH->commit();
		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?cid=".$cid . "&r=" . Sanitize::randomQueryStringParam());

		exit;
	} else {
		//DB $query = "SELECT name FROM imas_forums WHERE id='{$_GET['id']}'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $itemname = mysql_result($result,0,0);
		$stm = $DBH->prepare("SELECT name FROM imas_forums WHERE id=:id");
		$stm->execute(array(':id'=>$forumid));
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
	<h3><?php echo Sanitize::encodeStringForDisplay($itemname); ?></h3>
	Are you SURE you want to delete this forum and all associated postings?
	<form method="POST" action="deleteforum.php?cid=<?php echo Sanitize::courseId($_GET['cid']); ?>&block=<?php echo Sanitize::encodeUrlParam($block) ?>&id=<?php echo Sanitize::onlyInt($_GET['id']) ?>">
	<p>
	<button type=submit name="remove" value="really">Yes, Delete</button>		
	<input type=button value="Nevermind" class="secondarybtn" onClick="window.location='course.php?cid=<?php echo Sanitize::courseId($_GET['cid']); ?>'">
	</p>
	</form>

<?php
}
	require("../footer.php");
?>

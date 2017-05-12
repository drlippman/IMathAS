<?php
//IMathAS:  Add/modify blocks of items on course page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../validate.php");


/*** pre-html data manipulation, including function code *******/

//set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = "Delete Assessment";
$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; Delete Assessment";

if (!(isset($teacherid))) {
	$overwriteBody = 1;
	$body = "You need to log in as a teacher to access this page";
} elseif (!(isset($_GET['cid']))) {
	$overwriteBody = 1;
	$body = "You need to access this page from the link on the course page";
} elseif (isset($_GET['remove'])) { // a valid delete request loaded the page
	$cid = Sanitize::courseId($_GET['cid']);
	$block = $_GET['block'];

	if ($_GET['remove']=="really") {
		$aid = Sanitize::onlyInt($_GET['id']);
		$DBH->beginTransaction();
		//DB $query = "DELETE FROM imas_assessments WHERE id='$aid' AND courseid=$cid";
		//DB mysql_query($query) or die("Query failed : " . mysql_error());
		$stm = $DBH->prepare("DELETE FROM imas_assessments WHERE id=:id AND courseid=:courseid");
		$stm->execute(array(':id'=>$aid, ':courseid'=>$cid));
		if ($stm->rowCount()>0) {
			require_once('../includes/filehandler.php');
			deleteallaidfiles($aid);

			//DB $query = "DELETE FROM imas_assessment_sessions WHERE assessmentid='$aid'";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("DELETE FROM imas_assessment_sessions WHERE assessmentid=:assessmentid");
			$stm->execute(array(':assessmentid'=>$aid));

			$stm = $DBH->prepare("DELETE FROM imas_exceptions WHERE assessmentid=:assessmentid AND itemtype='A'");
			$stm->execute(array(':assessmentid'=>$aid));

			//DB $query = "DELETE FROM imas_questions WHERE assessmentid='$aid'";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("DELETE FROM imas_questions WHERE assessmentid=:assessmentid");
			$stm->execute(array(':assessmentid'=>$aid));

			//DB $query = "SELECT id FROM imas_items WHERE typeid='$aid' AND itemtype='Assessment'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $itemid = mysql_result($result,0,0);
			$stm = $DBH->prepare("SELECT id FROM imas_items WHERE typeid=:typeid AND itemtype='Assessment'");
			$stm->execute(array(':typeid'=>$aid));
			$itemid = $stm->fetchColumn(0);
			//DB $query = "DELETE FROM imas_items WHERE id='$itemid'";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("DELETE FROM imas_items WHERE id=:id");
			$stm->execute(array(':id'=>$itemid));

			//DB $query = "DELETE FROM imas_livepoll_status WHERE assessmentid='$aid'";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("DELETE FROM imas_livepoll_status WHERE assessmentid=:assessmentid");
			$stm->execute(array(':assessmentid'=>$aid));

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
		}
		$DBH->commit();
		header('Location: ' . $urlmode  . Sanitize::domainNameWithPort($_SERVER['HTTP_HOST']) . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/course.php?cid=".Sanitize::courseId($_GET['cid']));

		exit;
	} else {
		//DB $query = "SELECT name FROM imas_assessments WHERE id='{$_GET['id']}'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $itemname = mysql_result($result,0,0);
		$stm = $DBH->prepare("SELECT name FROM imas_assessments WHERE id=:id");
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
	Are you <b>SURE</b> you want to delete this assessment and all associated student attempts?
	<p>
	<input type=button value="Yes, Delete" onClick="window.location='deleteassessment.php?cid=<?php echo Sanitize::courseId($_GET['cid']); ?>&block=<?php echo $block ?>&id=<?php echo $_GET['id'] ?>&remove=really'">
	<input type=button value="Nevermind" class="secondarybtn" onClick="window.location='course.php?cid=<?php echo Sanitize::courseId($_GET['cid']); ?>'">
	</p>

<?php
}

require("../footer.php");
/**** end html code ******/
//nothing after the end of html for this page
/***** cleanup code ******/
//no cleanup code for this page

?>

<?php
//IMathAS:  Add/modify blocks of items on course page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../init.php");


/*** pre-html data manipulation, including function code *******/

//set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = "Delete Assessment";
$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=".Sanitize::courseId($_GET['cid'])."\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; Delete Assessment";

if (!(isset($teacherid))) {
	$overwriteBody = 1;
	$body = "You need to log in as a teacher to access this page";
} elseif (!(isset($_GET['cid']))) {
	$overwriteBody = 1;
	$body = "You need to access this page from the link on the course page";
} elseif (isset($_REQUEST['remove'])) { // a valid delete request loaded the page
	$cid = Sanitize::courseId($_GET['cid']);
	$block = Sanitize::stripHtmlTags($_GET['block']);
	$aid = Sanitize::onlyInt($_GET['id']);

	if ($_POST['remove']=="really") {
		$DBH->beginTransaction();
		$stm = $DBH->prepare("DELETE FROM imas_assessments WHERE id=:id AND courseid=:courseid");
		$stm->execute(array(':id'=>$aid, ':courseid'=>$cid));
		if ($stm->rowCount()>0) {
			require_once('../includes/filehandler.php');
			deleteallaidfiles($aid);
			$stm = $DBH->prepare("DELETE FROM imas_assessment_sessions WHERE assessmentid=:assessmentid");
			$stm->execute(array(':assessmentid'=>$aid));
			$stm = $DBH->prepare("DELETE FROM imas_assessment_records WHERE assessmentid=:assessmentid");
			$stm->execute(array(':assessmentid'=>$aid));

			$stm = $DBH->prepare("DELETE FROM imas_exceptions WHERE assessmentid=:assessmentid AND itemtype='A'");
			$stm->execute(array(':assessmentid'=>$aid));
			$stm = $DBH->prepare("DELETE FROM imas_questions WHERE assessmentid=:assessmentid");
			$stm->execute(array(':assessmentid'=>$aid));
			$stm = $DBH->prepare("SELECT id FROM imas_items WHERE typeid=:typeid AND itemtype='Assessment'");
			$stm->execute(array(':typeid'=>$aid));
			$itemid = $stm->fetchColumn(0);
			$stm = $DBH->prepare("DELETE FROM imas_items WHERE id=:id");
			$stm->execute(array(':id'=>$itemid));
			$stm = $DBH->prepare("DELETE FROM imas_livepoll_status WHERE assessmentid=:assessmentid");
			$stm->execute(array(':assessmentid'=>$aid));
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
				$itemorder = serialize($items);
				$stm = $DBH->prepare("UPDATE imas_courses SET itemorder=:itemorder WHERE id=:id");
				$stm->execute(array(':itemorder'=>$itemorder, ':id'=>$cid));
			}

			$stm = $DBH->prepare("UPDATE imas_assessments SET reqscoreaid=0 WHERE reqscoreaid=:assessmentid AND courseid=:courseid");
			$stm->execute(array(':assessmentid'=>$aid, ':courseid'=>$cid));
		}
		$DBH->commit();
		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?cid=".Sanitize::courseId($_GET['cid']) . "&r=" . Sanitize::randomQueryStringParam());

		exit;
	} else {
		$stm = $DBH->prepare("SELECT name FROM imas_assessments WHERE id=:id AND courseid=:cid");
		$stm->execute(array(':id'=>$aid, ':cid'=>$cid));
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
	<h2><?php echo Sanitize::encodeStringForDisplay($itemname); ?></h2>
	Are you <b>SURE</b> you want to delete this assessment and all associated student attempts?

	<form method="POST" action="deleteassessment.php?cid=<?php echo Sanitize::courseId($_GET['cid']); ?>&block=<?php echo Sanitize::encodeUrlParam($block) ?>&id=<?php echo Sanitize::onlyInt($_GET['id']) ?>">
	<p>
	<button type=submit name="remove" value="really">Yes, Delete</button>
	<input type=button value="Nevermind" class="secondarybtn" onClick="window.location='course.php?cid=<?php echo Sanitize::courseId($_GET['cid']); ?>'">
	</p>
	</form>

<?php
}

require("../footer.php");
/**** end html code ******/
//nothing after the end of html for this page
/***** cleanup code ******/
//no cleanup code for this page

?>

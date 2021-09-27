<?php
//IMathAS:  Add/modify blocks of items on course page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../init.php");
require("delitembyid.php");

/*** pre-html data manipulation, including function code *******/

//set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = _("Delete Assessment");
$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=".Sanitize::courseId($_GET['cid'])."\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; "._("Delete Assessment");

if (!(isset($teacherid))) {
	$overwriteBody = 1;
	$body = _("You need to log in as a teacher to access this page");
} elseif (!(isset($_GET['cid']))) {
	$overwriteBody = 1;
	$body = _("You need to access this page from the link on the course page");
} elseif (isset($_REQUEST['remove'])) { // a valid delete request loaded the page
	$cid = Sanitize::courseId($_GET['cid']);
	$block = Sanitize::stripHtmlTags($_GET['block']);
	$aid = Sanitize::onlyInt($_GET['id']);

	if (isset($_POST['remove']) && $_POST['remove']=="really") {
		$DBH->beginTransaction();
		$stm = $DBH->prepare("SELECT id FROM imas_items WHERE typeid=:typeid AND itemtype='Assessment' AND courseid=:courseid");
		$stm->execute(array(':typeid'=>$aid, ':courseid'=>$cid));
		if ($stm->rowCount()>0) {
			$itemid = $stm->fetchColumn(0);

			delitembyid($itemid);

			removeItemFromItemorder($cid, $itemid, $block);
		}

		$DBH->commit();
		$btf = isset($_GET['btf']) ? '&folder=' . Sanitize::encodeUrlParam($_GET['btf']) : '';
		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?cid=".Sanitize::courseId($_GET['cid']).$btf . "&r=" . Sanitize::randomQueryStringParam());

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
	<?php echo _("Are you <b>SURE</b> you want to delete this assessment and all associated student attempts?"); ?>

	<form method="POST" action="deleteassessment.php?cid=<?php echo Sanitize::courseId($_GET['cid']); ?>&block=<?php echo Sanitize::encodeUrlParam($block) ?>&id=<?php echo Sanitize::onlyInt($_GET['id']) ?>">
	<p>
	<button type=submit name="remove" value="really"><?php echo _("Yes, Delete"); ?></button>
	<button type="button" class="secondarybtn" onClick="window.location='course.php?cid=<?php echo Sanitize::courseId($_GET['cid']); ?>'"><?php echo _("Nevermind"); ?></button>
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

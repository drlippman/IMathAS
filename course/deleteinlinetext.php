<?php
//IMathAS:  Add/modify blocks of items on course page
//(c) 2006 David Lippman

/*** master php includes *******/
require_once "../init.php";
require_once "delitembyid.php";

/*** pre-html data manipulation, including function code *******/

//set some page specific variables and counters
$cid = Sanitize::courseId($_GET['cid']);
$overwriteBody = 0;
$body = "";
$pagetitle = "Delete Inline Text";
$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
$curBreadcrumb .= " &gt; Delete Inline Text\n";

if (!(isset($teacherid))) {
	$overwriteBody = 1;
	$body = "You need to log in as a teacher to access this page";
} elseif (!(isset($_GET['cid']))) {
	$overwriteBody = 1;
	$body = "You need to access this page from the link on the course page";
} elseif (isset($_REQUEST['remove'])) { // a valid delete request loaded the page
	$cid = Sanitize::courseId($_GET['cid']);
	$block = Sanitize::stripHtmlTags($_GET['block']);
	$textid = Sanitize::onlyInt($_GET['id']);
	if (isset($_POST['remove']) && $_POST['remove']=="really") {
		require_once "../includes/filehandler.php";
		$DBH->beginTransaction();
		$stm = $DBH->prepare("SELECT id FROM imas_items WHERE typeid=:typeid AND itemtype='InlineText' AND courseid=:courseid");
		$stm->execute(array(':typeid'=>$textid, ':courseid'=>$cid));
		if ($stm->rowCount()>0) {
			$itemid = $stm->fetchColumn(0);

			delitembyid($itemid);

			removeItemFromItemorder($cid, $itemid, $block);
		}
		$DBH->commit();
		$btf = isset($_GET['btf']) ? '&folder=' . Sanitize::encodeUrlParam($_GET['btf']) : '';
		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?cid=$cid$btf&r=" . Sanitize::randomQueryStringParam());
		exit;
	} else {
		$stm = $DBH->prepare("SELECT title FROM imas_inlinetext WHERE id=:id AND courseid=:cid");
		$stm->execute(array(':id'=>$textid, ':cid'=>$cid));
		$itemname = $stm->fetchColumn(0);
	}
}

/******* begin html output ********/
require_once "../header.php";

/**** post-html data manipulation ******/
// this page has no post-html data manipulation

/***** page body *****/
/***** php display blocks are interspersed throughout the html as needed ****/
if ($overwriteBody==1) {
	echo $body;
} else {
?>

<div class=breadcrumb><?php echo $curBreadcrumb; ?></div>
<h2><?php echo Sanitize::encodeStringForDisplay($itemname); ?></h2>
Are you SURE you want to delete this text item?
	<form method="POST" action="deleteinlinetext.php?cid=<?php echo Sanitize::courseId($_GET['cid']); ?>&block=<?php echo Sanitize::encodeUrlParam($block) ?>&id=<?php echo Sanitize::onlyInt($_GET['id']) ?>">
	<p>
	<button type=submit name="remove" value="really">Yes, Delete</button>
	<input type=button value="Nevermind" class="secondarybtn" onClick="window.location='course.php?cid=<?php echo Sanitize::courseId($_GET['cid']); ?>'">
	</p>
	</form>

<?php
}
	require_once "../footer.php";
?>

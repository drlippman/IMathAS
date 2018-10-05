<?php
//IMathAS:  Add/modify blocks of items on course page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../init.php");


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
	if ($_POST['remove']=="really") {
		require_once("../includes/filehandler.php");
		$DBH->beginTransaction();
		$stm = $DBH->prepare("SELECT id FROM imas_items WHERE typeid=:typeid AND itemtype='InlineText' AND courseid=:courseid");
		$stm->execute(array(':typeid'=>$textid, ':courseid'=>$cid));
		if ($stm->rowCount()>0) {
			$itemid = $stm->fetchColumn(0);
			$stm = $DBH->prepare("DELETE FROM imas_items WHERE id=:id");
			$stm->execute(array(':id'=>$itemid));
			$stm = $DBH->prepare("DELETE FROM imas_inlinetext WHERE id=:id");
			$stm->execute(array(':id'=>$textid));
			$stm = $DBH->prepare("SELECT filename FROM imas_instr_files WHERE itemid=:itemid");
			$stm->execute(array(':itemid'=>$textid));
			//$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/files/';
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				if (substr($row[0],0,4)!='http') {
					$stm2 = $DBH->prepare("SELECT id FROM imas_instr_files WHERE filename=:filename");
					$stm2->execute(array(':filename'=>$row[0]));
					if ($stm2->rowCount()==1) {
						//unlink($uploaddir . $row[0]);
						deletecoursefile($row[0]);
					}
				}
			}
			$stm = $DBH->prepare("DELETE FROM imas_instr_files WHERE itemid=:itemid");
			$stm->execute(array(':itemid'=>$textid));
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
		}
		$DBH->commit();
		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?cid=$cid&r=" . Sanitize::randomQueryStringParam());
		exit;
	} else {
		$stm = $DBH->prepare("SELECT title FROM imas_inlinetext WHERE id=:id");
		$stm->execute(array(':id'=>$textid));
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
	require("../footer.php");
?>

<?php
//IMathAS:  Add/modify blocks of items on course page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../validate.php");


/*** pre-html data manipulation, including function code *******/

//set some page specific variables and counters
$cid = $_GET['cid'];
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
} elseif (isset($_GET['remove'])) { // a valid delete request loaded the page
	$cid = $_GET['cid'];
	$block = $_GET['block'];
	if ($_GET['remove']=="really") {
		require("../includes/filehandler.php");
		$textid = $_GET['id'];
		$DBH->beginTransaction();
		//DB $query = "SELECT id FROM imas_items WHERE typeid='$textid' AND itemtype='InlineText' AND courseid='$cid'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $itemid = mysql_result($result,0,0);
		$stm = $DBH->prepare("SELECT id FROM imas_items WHERE typeid=:typeid AND itemtype='InlineText' AND courseid=:courseid");
		$stm->execute(array(':typeid'=>$textid, ':courseid'=>$cid));
		if ($stm->rowCount()>0) {
			$itemid = $stm->fetchColumn(0);

			//DB $query = "DELETE FROM imas_items WHERE id='$itemid'";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("DELETE FROM imas_items WHERE id=:id");
			$stm->execute(array(':id'=>$itemid));

			//DB $query = "DELETE FROM imas_inlinetext WHERE id='$textid'";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("DELETE FROM imas_inlinetext WHERE id=:id");
			$stm->execute(array(':id'=>$textid));

			//DB $query = "SELECT filename FROM imas_instr_files WHERE itemid='$textid'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("SELECT filename FROM imas_instr_files WHERE itemid=:itemid");
			$stm->execute(array(':itemid'=>$textid));
			//$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/files/';
			//DB while ($row = mysql_fetch_row($result)) {
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				//DB $safefn = addslashes($row[0]);
				//DB $query = "SELECT id FROM imas_instr_files WHERE filename='$safefn'";
				//DB $r2 = mysql_query($query) or die("Query failed : " . mysql_error());
				//DB if (mysql_num_rows($r2)==1) {
				$stm2 = $DBH->prepare("SELECT id FROM imas_instr_files WHERE filename=:filename");
				$stm2->execute(array(':filename'=>$row[0]));
				if ($stm2->rowCount()==1) {
					//unlink($uploaddir . $row[0]);
					deletecoursefile($row[0]);
				}
			}
			//DB $query = "DELETE FROM imas_instr_files WHERE itemid='$textid'";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("DELETE FROM imas_instr_files WHERE itemid=:itemid");
			$stm->execute(array(':itemid'=>$textid));

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
		header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/course.php?cid=$cid");
		exit;
	} else {
		//DB $query = "SELECT title FROM imas_inlinetext WHERE id='{$_GET['id']}'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $itemname = mysql_result($result,0,0);
		$stm = $DBH->prepare("SELECT title FROM imas_inlinetext WHERE id=:id");
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

<div class=breadcrumb><?php echo $curBreadcrumb; ?></div>
<h3><?php echo $itemname; ?></h3>
Are you SURE you want to delete this text item?
	<p><input type=button value="Yes, Delete" onClick="window.location='deleteinlinetext.php?cid=<?php echo $cid ?>&block=<?php echo $block ?>&id=<?php echo $_GET['id'] ?>&remove=really'">
	<input type=button value="Nevermind" class="secondarybtn" onClick="window.location='course.php?cid=<?php echo $cid ?>'"></p>

<?php
}
	require("../footer.php");
?>

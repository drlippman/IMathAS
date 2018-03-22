<?php
//IMathAS:  Add/modify blocks of items on course page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../init.php");


/*** pre-html data manipulation, including function code *******/

//set some page specific variables and counters
$overwriteBody = 0;
$body = "";

$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=".Sanitize::courseId($_GET['cid'])."\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
$curBreadcrumb .= "&gt; Delete Link\n";
$pagetitle = "Delete Link";


if (!(isset($teacherid))) { // loaded by a NON-teacher
	$overwriteBody=1;
	$body = "You need to log in as a teacher to access this page";
} elseif (!(isset($_GET['cid']))) {
	$overwriteBody=1;
	$body = "You need to access this page from the course page menu";
} else { // PERMISSIONS ARE OK, PROCEED WITH PROCESSING
	$cid = (int) Sanitize::courseId($_GET['cid']);
	$block = (string) Sanitize::stripHtmlTags($_GET['block']);
	$textid = Sanitize::onlyInt($_GET['id']);

	if ($_POST['remove']=="really") {
		require_once("../includes/filehandler.php");		

		$DBH->beginTransaction();

		//DB $query = "SELECT id FROM imas_items WHERE typeid='$textid' AND itemtype='LinkedText' AND courseid='$cid'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $itemid = mysql_result($result,0,0);
		$stm = $DBH->prepare("SELECT id FROM imas_items WHERE typeid=:typeid AND itemtype='LinkedText' AND courseid=:courseid");
		$stm->execute(array(':typeid'=>$textid, ':courseid'=>$cid));
		if ($stm->rowCount()>0) {
			$itemid = $stm->fetchColumn(0);

			//DB $query = "DELETE FROM imas_items WHERE id='$itemid'";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("DELETE FROM imas_items WHERE id=:id");
			$stm->execute(array(':id'=>$itemid));

			//DB $query = "SELECT text,points FROM imas_linkedtext WHERE id='$textid'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $row = mysql_fetch_row($result);
			$stm = $DBH->prepare("SELECT text,points FROM imas_linkedtext WHERE id=:id");
			$stm->execute(array(':id'=>$textid));
			$row = $stm->fetch(PDO::FETCH_NUM);
			$text = trim($row[0]);
			$points = $row[1];
			if (substr($text,0,5)=='file:') { //delete file if not used
				//DB $safetext = addslashes($text);
				//DB $query = "SELECT id FROM imas_linkedtext WHERE text='$safetext'";
				//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
				//DB if (mysql_num_rows($result)==1) {
				$stm = $DBH->prepare("SELECT id FROM imas_linkedtext WHERE text=:text");
				$stm->execute(array(':text'=>$text));
				if ($stm->rowCount()==1) {
					/*$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/files/';
					$filename = substr($text,5);
					if (file_exists($uploaddir . $filename)) {
						unlink($uploaddir . $filename);
					}*/
					deletecoursefile(substr($text,5));
				}
			}
			if ($points>0) {
				//DB $query = "DELETE FROM imas_grades WHERE gradetypeid='$textid' AND gradetype='exttool'";
				//DB mysql_query($query) or die("Query failed : " . mysql_error());
				$stm = $DBH->prepare("DELETE FROM imas_grades WHERE gradetypeid=:gradetypeid AND gradetype='exttool'");
				$stm->execute(array(':gradetypeid'=>$textid));
			}

			//DB $query = "DELETE FROM imas_linkedtext WHERE id='$textid'";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("DELETE FROM imas_linkedtext WHERE id=:id");
			$stm->execute(array(':id'=>$textid));

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
		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?cid=".$cid . "&r=" . Sanitize::randomQueryStringParam());

		exit;
	} else {
		//DB $query = "SELECT title FROM imas_linkedtext WHERE id='{$_GET['id']}'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $itemname = mysql_result($result,0,0);
		$stm = $DBH->prepare("SELECT title FROM imas_linkedtext WHERE id=:id");
		$stm->execute(array(':id'=>$textid));
		$itemname = $stm->fetchColumn(0);
	}
}




/******* begin html output ********/
require("../header.php");

if ($overwriteBody==1) {
	echo $body;
} else {
?>

	<div class=breadcrumb><?php echo $curBreadcrumb ?></div>
	<h3><?php echo Sanitize::encodeStringForDisplay($itemname); ?></h3>
	Are you SURE you want to delete this link item?
	<form method="POST" action="deletelinkedtext.php?cid=<?php echo Sanitize::courseId($_GET['cid']); ?>&block=<?php echo Sanitize::encodeUrlParam($block) ?>&id=<?php echo Sanitize::onlyInt($_GET['id']) ?>">
	<p>
	<button type=submit name="remove" value="really">Yes, Delete</button>		
	<input type=button value="Nevermind" class="secondarybtn" onClick="window.location='course.php?cid=<?php echo Sanitize::courseId($_GET['cid']); ?>'">
	</p>
	</form>

<?php
}
require("../footer.php");
?>

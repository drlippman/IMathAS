<?php
//IMathAS:  Add/modify blocks of items on course page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../init.php");


/*** pre-html data manipulation, including function code *******/

//set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = "Delete Course Block";
$cid = Sanitize::courseId($_GET['cid']);
$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=".$cid."\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; Delete Block";


if (!(isset($_GET['cid']))) { //if the cid is missing go back to the index page
	$overwriteBody = 1;
	$body = "You need to access this page from the link on the course page";
} elseif (!(isset($teacherid))) {  //there is a cid but the user isn't a teacher
	$overwriteBody = 1;
	$body = "You need to log in as a teacher to access this page";
} elseif (isset($_REQUEST['remove'])) { // a valid delete request loaded the page
	if ($_POST['remove']=="really") { // the request has been confirmed, delete the block
		$blocktree = explode('-',$_GET['id']);
		$blockid = array_pop($blocktree) - 1; //-1 adjust for 1-index

		//mysql_query("START TRANSACTION") or die("Query failed :$query " . mysql_error());
		$DBH->beginTransaction();
		$stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
		$stm->execute(array(':id'=>$cid));
		$items = unserialize($stm->fetchColumn(0));
		$sub =& $items;
		if (count($blocktree)>1) {
			for ($i=1;$i<count($blocktree);$i++) {
				$sub =& $sub[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
			}
		}
		if (is_array($sub[$blockid])) { //make sure it's really a block
			$blockitems = $sub[$blockid]['items'];
            $obid = $sub[$blockid]['id'];
            if ($obid != $_POST['bid']) {
                echo _('Uh oh, something changed.  Please go back and try again');
                exit;
            }
			if (count($blockitems)>0) {
				if (isset($_POST['delcontents']) && $_POST['delcontents']==1) { //clear out contents of block
					require("delitembyid.php");
					delrecurse($blockitems);
					array_splice($sub,$blockid,1); //remove block and contained items from itemorder
				} else {
					array_splice($sub,$blockid,1,$blockitems); //remove block, replace with items in block
				}
			} else {
				array_splice($sub,$blockid,1); //empty block; just remove block
			}
		}
		$itemlist = serialize($items);
		$stm = $DBH->prepare("UPDATE imas_courses SET itemorder=:itemorder WHERE id=:id");
		$stm->execute(array(':itemorder'=>$itemlist, ':id'=>$cid));
		$DBH->commit();

		$obarr = explode(',',$_COOKIE['openblocks-'.$cid]);
		$obloc = array_search($obid,$obarr);
		array_splice($obarr,$obloc,1);
		setcookie('openblocks-'.Sanitize::courseId($_GET['cid']),implode(',',array_map('Sanitize::onlyInt',$obarr)));
		$btf = isset($_GET['btf']) ? '&folder=' . Sanitize::encodeUrlParam($_GET['btf']) : '';
		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?cid=". $cid.$btf . "&r=" . Sanitize::randomQueryStringParam());

	} else {
		$blocktree = explode('-',$_GET['id']);
		$blockid = array_pop($blocktree) - 1; //-1 adjust for 1-index
		$stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
		$stm->execute(array(':id'=>$cid));
		$items = unserialize($stm->fetchColumn(0));
		$sub =& $items;
		if (count($blocktree)>1) {
			for ($i=1;$i<count($blocktree);$i++) {
				$sub =& $sub[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
			}
		}
		$itemname =  $sub[$blockid]['name'];
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
	<h2><?php echo _('Block').': ' . Sanitize::encodeStringForDisplay($itemname); ?></h2>
	<form method=post action="deleteblock.php?cid=<?php echo $cid; ?>&id=<?php echo Sanitize::encodeStringForDisplay($_GET['id']) ?>">
    <p><?php echo _('Are you SURE you want to delete this Block?');?>
    <input type=hidden name="bid" value="<?php echo intval($_GET['bid']);?>"/>
    </p>
	<p><label><input type=radio name="delcontents" value="0"/><?php echo _('Move all items out of block');?></label><br/>
	<label><input type=radio name="delcontents" value="1" checked="checked"/><?php echo _('Also Delete all items in block');?></label></p>
	<p><button type=submit name="remove" value="really"><?php echo _('Yes, Delete');?></button>
    <button type=button class="secondarybtn" onClick="window.location='course.php?cid=<?php echo $cid; ?>'"><?php echo _('Nevermind');?></button></p>
	</form>
<?php
}

require("../footer.php");
/**** end html code ******/
//nothing after the end of html for this page
/***** cleanup code ******/
//no cleanup code for this page

?>

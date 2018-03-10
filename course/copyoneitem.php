<?php
//IMathAS:  Copy One Course Item
//(c) 2006 David Lippman

/*** master php includes *******/
require("../init.php");
require("../includes/copyiteminc.php");
require("../includes/htmlutil.php");

$cid = Sanitize::courseId($_GET['cid']);

if (!isset($teacherid)) {
	echo "You need to log in as a teacher to access this page";
	exit;
}

$tocopy = $_GET['copyid'];
if (isset($_GET['noappend'])) {
	$_POST['append'] = "";
} else {
	$_POST['append'] = " (Copy)";
}
$_POST['ctc'] = $cid;
$gbcats = array();
//DB $query = "SELECT id FROM imas_gbcats WHERE courseid='$cid'";
//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
//DB while ($row = mysql_fetch_row($result)) {
$stm = $DBH->prepare("SELECT id FROM imas_gbcats WHERE courseid=:courseid");
$stm->execute(array(':courseid'=>$cid));
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	$gbcats[$row[0]] = $row[0];
}
$outcomes = array();
//DB $query = "SELECT id FROM imas_outcomes WHERE courseid='$cid'";
//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
//DB while ($row = mysql_fetch_row($result)) {
$stm = $DBH->prepare("SELECT id FROM imas_outcomes WHERE courseid=:courseid");
$stm->execute(array(':courseid'=>$cid));
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	$outcomes[$row[0]] = $row[0];
}


function copysubone(&$items,$parent,$copyinside,&$addtoarr) {
	global $blockcnt,$tocopy, $gbcats, $outcomes;
	foreach ($items as $k=>$item) {
		if (is_array($item)) {
			if (($parent.'-'.($k+1)==$tocopy) || $copyinside) { //copy block
				$newblock = array();
				//DB $newblock['name'] = $item['name'].stripslashes($_POST['append']);
				$newblock['name'] = $item['name'].$_POST['append'];
				$newblock['id'] = $blockcnt;
				$blockcnt++;
				$newblock['startdate'] = $item['startdate'];
				$newblock['enddate'] = $item['enddate'];
				$newblock['avail'] = $item['avail'];
				$newblock['SH'] = $item['SH'];
				$newblock['colors'] = $item['colors'];
				$newblock['fixedheight'] = $item['fixedheight'];
				$newblock['grouplimit'] = $item['grouplimit'];
				$newblock['items'] = array();
				if (count($item['items'])>0) {
					copysubone($items[$k]['items'],$parent.'-'.($k+1),true,$newblock['items']);
				}
				if (!$copyinside) {
					array_splice($items,$k+1,0,array($newblock));
					return 0;
				} else {
					$addtoarr[] = $newblock;
				}
			} else {
				if (count($item['items'])>0) {
					$nothin = array();
					copysubone($items[$k]['items'],$parent.'-'.($k+1),false,$nothin);
				}
			}
		} else {
			if ($item==$tocopy || $copyinside) {
				$newitem = copyitem($item,$gbcats);
				if (!$copyinside) {
					array_splice($items,$k+1,0,$newitem);
					return 0;
				} else {
					$addtoarr[] = $newitem;
				}
			}
		}
	}
}

//DB $query = "SELECT blockcnt,itemorder FROM imas_courses WHERE id='$cid';";
//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
//DB $blockcnt = mysql_result($result,0,0);
//DB $items = unserialize(mysql_result($result,0,1));
$stm = $DBH->prepare("SELECT blockcnt,itemorder,dates_by_lti FROM imas_courses WHERE id=:id");
$stm->execute(array(':id'=>$cid));
list($blockcnt, $itemorder, $datesbylti) = $stm->fetch(PDO::FETCH_NUM);
$items = unserialize($itemorder);

//DB mysql_query("START TRANSACTION") or die("Query failed :$query " . mysql_error());
$DBH->beginTransaction();

$notimportant = array();
copysubone($items,'0',false,$notimportant);
copyrubrics();

//DB $itemorder = addslashes(serialize($items));
$itemorder = serialize($items);
//DB $query = "UPDATE imas_courses SET itemorder='$itemorder',blockcnt='$blockcnt' WHERE id='$cid'";
//DB mysql_query($query) or die("Query failed : $query" . mysql_error());
$stm = $DBH->prepare("UPDATE imas_courses SET itemorder=:itemorder,blockcnt=:blockcnt WHERE id=:id");
$stm->execute(array(':itemorder'=>$itemorder, ':blockcnt'=>$blockcnt, ':id'=>$cid));

//DB mysql_query("COMMIT") or die("Query failed :$query " . mysql_error());
$DBH->commit();

header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?cid=$cid");
?>

<?php
//IMathAS:  Copy One Course Item
//(c) 2006 David Lippman

/*** master php includes *******/
require("../validate.php");
require("../includes/copyiteminc.php");
require("../includes/htmlutil.php");

$cid = $_GET['cid'];

if (!isset($teacherid)) {
	echo "You need to log in as a teacher to access this page";
	exit;
}

$tocopy = $_GET['copyid'];
$_POST['append'] = " (Copy)";
$_POST['ctc'] = $cid;

function copysubone(&$items,$parent) {
	global $blockcnt,$tocopy;
	foreach ($items as $k=>$item) {
		if (is_array($item)) {
			if ($parent.'-'.($k+1)==$tocopy) { //copy block
				$newblock = array();
				$newblock['name'] = $item['name'].stripslashes($_POST['append']);
				$newblock['id'] = $blockcnt;
				$blockcnt++;
				$newblock['startdate'] = $item['startdate'];
				$newblock['enddate'] = $item['enddate'];
				$newblock['SH'] = $item['SH'];
				$newblock['colors'] = $item['colors'];
				$newblock['items'] = array();
				if (count($item['items'])>0) {
					copysubone($item['items'],$parent.'-'.($k+1));
				}
				array_splice($items,$k,0,$newblock);
				break;
				//$addtoarr[] = $newblock;
			} else {
				if (count($item['items'])>0) {
					copysubone($item['items'],$parent.'-'.($k+1));
				}
			}
		} else {
			if ($item==$tocopy) {
				array_splice($items,$k,0,copyitem($item,false));
				break;
				//$addtoarr[] = copyitem($item,false); //gbcats?
			}
		}
	}
}

$query = "SELECT blockcnt,itemorder FROM imas_courses WHERE id='$cid';";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
$blockcnt = mysql_result($result,0,0);
$items = unserialize(mysql_result($result,0,1));

copysubone($items,'0');

$itemorder = addslashes(serialize($items));
$query = "UPDATE imas_courses SET itemorder='$itemorder',blockcnt='$blockcnt' WHERE id='$cid'";
mysql_query($query) or die("Query failed : $query" . mysql_error());

header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/course.php?cid=$cid");
?>

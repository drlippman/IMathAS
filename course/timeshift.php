<?php
//IMathAS:  Shift Course dates; made obsolete by mass change dates
//(c) 2006 David Lippman

//boost operation time
@set_time_limit(0);
ini_set("max_input_time", "3600");
ini_set("max_execution_time", "3600");

/*** master php includes *******/
require("../init.php");
include("../includes/htmlutil.php");

function shiftsub(&$itema) {
	global $shiftstring;
	foreach ($itema as $k=>$item) {
		if (is_array($item)) {
			if ($itema[$k]['startdate'] > 0) {
				$itema[$k]['startdate'] = strtotime($shiftstring, $itema[$k]['startdate']);
			}
			if ($itema[$k]['enddate'] < 2000000000) {
				$itema[$k]['enddate'] = strtotime($shiftstring, $itema[$k]['enddate']);
			}
			shiftsub($itema[$k]['items']);
		}
	}
}

 //set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = "Shift Course Dates";


	//CHECK PERMISSIONS AND SET FLAGS
if (!(isset($teacherid))) {
 	$overwriteBody = 1;
	$body = "You need to log in as a teacher to access this page";
} else {	//PERMISSIONS ARE OK, PERFORM DATA MANIPULATION

	$cid = Sanitize::courseId($_GET['cid']);

	if (isset($_POST['sdate'])) {

		$stm = $DBH->prepare("SELECT startdate,enddate FROM imas_assessments WHERE id=:id");
		$stm->execute(array(':id'=>Sanitize::onlyInt($_POST['aid'])));
		$basedate = $stm->fetchColumn(intval($_POST['base']));
		preg_match('/(\d+)\s*\/(\d+)\s*\/(\d+)/',$_POST['sdate'],$dmatches);
		$newstamp = mktime(date('G',$basedate),date('i',$basedate),0,$dmatches[1],$dmatches[2],$dmatches[3]);
		$shift = $newstamp-$basedate;
		$shiftdays = round($shift/(24*60*60));
		if ($shiftdays>0) {
			$shiftstring = "+$shiftdays days";
		} else {
			$shiftstring = "-".abs($shiftdays)." days";
		}
		
		$stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
		$stm->execute(array(':id'=>$cid));
		$items = unserialize($stm->fetchColumn(0));

		shiftsub($items);
		$itemorder = serialize($items);
		$stm = $DBH->prepare("UPDATE imas_courses SET itemorder=:itemorder WHERE id=:id");
		$stm->execute(array(':itemorder'=>$itemorder, ':id'=>$cid));

		//update items
		foreach (array("inlinetext","linkedtext","drillassess") as $table) {
			$upd = $DBH->prepare("UPDATE imas_$table SET startdate=?,enddate=? WHERE id=?");
			$stm = $DBH->prepare("SELECT id,startdate,enddate FROM imas_$table WHERE courseid=?");
			$stm->execute(array($cid));
			while ($row=$stm->fetch(PDO::FETCH_ASSOC)) {
				if ($row['startdate']>0) {
					$row['startdate'] = strtotime($shiftstring, $row['startdate']);
				} 
				if ($row['enddate']<2000000000) {
					$row['enddate'] = strtotime($shiftstring, $row['enddate']);
				}
				$upd->execute(array($row['startdate'], $row['enddate'], $row['id']));
			}
		}
		$upd = $DBH->prepare("UPDATE imas_assessments SET startdate=?,enddate=?,reviewdate=? WHERE id=?");
		$stm = $DBH->prepare("SELECT id,startdate,enddate,reviewdate FROM imas_assessments WHERE courseid=?");
		$stm->execute(array($cid));
		while ($row=$stm->fetch(PDO::FETCH_ASSOC)) {
			if ($row['startdate']>0) {
				$row['startdate'] = strtotime($shiftstring, $row['startdate']);
			} 
			if ($row['enddate']<2000000000) {
				$row['enddate'] = strtotime($shiftstring, $row['enddate']);
			}
			if ($row['reviewdate']>0 && $row['reviewdate']<2000000000) {
				$row['reviewdate'] = strtotime($shiftstring, $row['reviewdate']);
			}
			$upd->execute(array($row['startdate'], $row['enddate'], $row['reviewdate'], $row['id']));
		}
		$upd = $DBH->prepare("UPDATE imas_wikis SET startdate=?,enddate=?,editbydate=? WHERE id=?");
		$stm = $DBH->prepare("SELECT id,startdate,enddate,editbydate FROM imas_wikis WHERE courseid=?");
		$stm->execute(array($cid));
		while ($row=$stm->fetch(PDO::FETCH_ASSOC)) {
			if ($row['startdate']>0) {
				$row['startdate'] = strtotime($shiftstring, $row['startdate']);
			} 
			if ($row['enddate']<2000000000) {
				$row['enddate'] = strtotime($shiftstring, $row['enddate']);
			}
			if ($row['editbydate']>0 && $row['editbydate']<2000000000) {
				$row['editbydate'] = strtotime($shiftstring, $row['editbydate']);
			}
			$upd->execute(array($row['startdate'], $row['enddate'], $row['editbydate'], $row['id']));
		}
		$upd = $DBH->prepare("UPDATE imas_forums SET startdate=?,enddate=?,postby=?,replyby=? WHERE id=?");
		$stm = $DBH->prepare("SELECT id,startdate,enddate,postby,replyby FROM imas_forums WHERE courseid=?");
		$stm->execute(array($cid));
		while ($row=$stm->fetch(PDO::FETCH_ASSOC)) {
			if ($row['startdate']>0) {
				$row['startdate'] = strtotime($shiftstring, $row['startdate']);
			} 
			if ($row['enddate']<2000000000) {
				$row['enddate'] = strtotime($shiftstring, $row['enddate']);
			}
			if ($row['postby']>0 && $row['postby']<2000000000) {
				$row['postby'] = strtotime($shiftstring, $row['postby']);
			}
			if ($row['replyby']>0 && $row['replyby']<2000000000) {
				$row['replyby'] = strtotime($shiftstring, $row['replyby']);
			}
			$upd->execute(array($row['startdate'], $row['enddate'], $row['postby'], $row['replyby'], $row['id']));
		}
		

		//update Calendar items
		$upd = $DBH->prepare("UPDATE imas_calitems SET date=? WHERE id=?");
		$stm = $DBH->prepare("SELECT id,date FROM imas_calitems WHERE courseid=?");
		$stm->execute(array($cid));
		while ($row=$stm->fetch(PDO::FETCH_ASSOC)) {
			$row['date'] = strtotime($shiftstring, $row['date']);
			$upd->execute(array($row['date'], $row['id']));
		}
		
		//update offline items
		$upd = $DBH->prepare("UPDATE imas_gbitems SET showdate=? WHERE id=?");
		$stm = $DBH->prepare("SELECT id,showdate FROM imas_gbitems WHERE courseid=?");
		$stm->execute(array($cid));
		while ($row=$stm->fetch(PDO::FETCH_ASSOC)) {
			$row['showdate'] = strtotime($shiftstring, $row['showdate']);
			$upd->execute(array($row['showdate'], $row['id']));
		}

		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?cid=$cid&" .Sanitize::randomQueryStringParam());

		exit;
	} else { //DEFAULT DATA MANIPULATION
		$curBreadcrumb = sprintf("%s <a href=\"course.php?cid=%s\">%s</a>", $breadcrumbbase, $cid,
            Sanitize::encodeStringForDisplay($coursename));
		$curBreadcrumb .= " &gt; Shift Course Dates ";

		$sdate = tzdate("m/d/Y",time());

		//DB $query = "SELECT id,name from imas_assessments WHERE courseid='$cid'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		$stm = $DBH->prepare("SELECT id,name from imas_assessments WHERE courseid=:courseid");
		$stm->execute(array(':courseid'=>$cid));
		$i=0;
		//DB while ($line=mysql_fetch_array($result, MYSQL_ASSOC)) {
		while ($line=$stm->fetch(PDO::FETCH_ASSOC)) {
			$page_assessmentList['val'][$i] = $line['id'];
			$page_assessmentList['label'][$i] = $line['name'];
			$i++;
		}

	}
}

/******* begin html output ********/
$placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/DatePicker.js\"></script>";

require("../header.php");

if ($overwriteBody==1) {
	echo $body;
} else {
?>
	<div class=breadcrumb><?php echo $curBreadcrumb ?></div>
	<h3>Shift Course Dates</h3>
	<p>
		This page will change <b>ALL</b> course available dates and due dates based on
		changing one item.  This is intended to allow you to reset all course item
		dates for a new term in one action.
	</p>
	<form method=post action="timeshift.php?cid=<?php echo $cid ?>">
		<span class=form>Select an assessment to base the change on</span>
		<span class=formright>
			<?php writeHtmlSelect ("aid",$page_assessmentList['val'],$page_assessmentList['label'],null,null,null,$actions=" id=aid "); ?>
		</span><br class=form>
		<span class=form>Change dates based on this assessment's:</span>
		<span class=formright>
			<input type=radio id=base name=base value=0 >Available After date<br/>
			<input type=radio id=base name=base value=1 checked=1>Available Until date (Due date) <br/>
		</span><br class=form>
		<span class=form>Change date to:</span>
		<span class=formright>
			<input type=text size=10 name="sdate" value="<?php echo $sdate ?>">
			<a href="#" onClick="displayDatePicker('sdate', this); return false">
			<img src="../img/cal.gif" alt="Calendar"/>
			</a>
		</span><br class=form>
		<div class=submit><input type=submit value="Change Dates"></div>
	</form>
<?php
}

require("../footer.php");


?>

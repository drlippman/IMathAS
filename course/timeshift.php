<?php
//IMathAS:  Shift Course dates; made obsolete by mass change dates
//(c) 2006 David Lippman

/*** master php includes *******/
require("../init.php");

function writeHtmlSelect ($name,$valList,$labelList,$selectedVal=null,$defaultLabel=null,$defaultVal=null,$actions=null) {
	//$name is the html name for the select list
	//$valList is an array of strings for the html value tag
	//$labelList is an array of strings that are displayed as the select list
	//$selectVal is optional, if passed the item in $valList that matches will be output as selected

	echo "<select name=\"$name\" ";
	echo (isset($actions)) ? $actions : "" ;
	echo ">\n";
	if (isset($defaultLabel) && isset($defaultVal)) {
		printf("		<option value=\"%s\" selected>%s</option>\n", Sanitize::encodeStringForDisplay($defaultVal),
            Sanitize::encodeStringForDisplay($defaultLabel));
	}
	for ($i=0;$i<count($valList);$i++) {
		if ((isset($selectedVal)) && ($valList[$i]==$selectedVal)) {
			printf("		<option value=\"%s\" selected>%s</option>\n",
                Sanitize::encodeStringForDisplay($valList[$i]), Sanitize::encodeStringForDisplay($labelList[$i]));
		} else {
			printf("		<option value=\"%s\">%s</option>\n",
                Sanitize::encodeStringForDisplay($valList[$i]), Sanitize::encodeStringForDisplay($labelList[$i]));
		}
	}
	echo "</select>\n";

}

function shiftsub(&$itema) {
	global $shift;
	foreach ($itema as $k=>$item) {
		if (is_array($item)) {
			if ($itema[$k]['startdate'] > 0) {
				$itema[$k]['startdate'] += $shift;
			}
			if ($itema[$k]['enddate'] < 2000000000) {
				$itema[$k]['enddate'] += $shift;
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

	$cid = $_GET['cid'];

	if (isset($_POST['sdate'])) {

		//DB $query = "SELECT startdate,enddate FROM imas_assessments WHERE id='{$_POST['aid']}'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $basedate = mysql_result($result,0,intval($_POST['base']));
		$stm = $DBH->prepare("SELECT startdate,enddate FROM imas_assessments WHERE id=:id");
		$stm->execute(array(':id'=>Sanitize::onlyInt($_POST['aid'])));
		$basedate = $stm->fetchColumn(intval($_POST['base']));
		preg_match('/(\d+)\s*\/(\d+)\s*\/(\d+)/',$_POST['sdate'],$dmatches);
		$newstamp = mktime(date('G',$basedate),date('i',$basedate),0,$dmatches[1],$dmatches[2],$dmatches[3]);
		$shift = $newstamp-$basedate;

		//DB $query = "SELECT itemorder FROM imas_courses WHERE id='$cid'";
		//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
		//DB $items = unserialize(mysql_result($result,0,0));
		$stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
		$stm->execute(array(':id'=>$cid));
		$items = unserialize($stm->fetchColumn(0));

		shiftsub($items);
		//DB $itemorder = addslashes(serialize($items));
		$itemorder = serialize($items);
		//DB $query = "UPDATE imas_courses SET itemorder='$itemorder' WHERE id='$cid'";
		//DB mysql_query($query) or die("Query failed : $query" . mysql_error());
		$stm = $DBH->prepare("UPDATE imas_courses SET itemorder=:itemorder WHERE id=:id");
		$stm->execute(array(':itemorder'=>$itemorder, ':id'=>$cid));

		//DB $query = "SELECT itemtype,typeid FROM imas_items WHERE courseid='$cid'";
		//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
		//DB while ($row=mysql_fetch_row($result)) {
		$stm = $DBH->prepare("SELECT itemtype,typeid FROM imas_items WHERE courseid=:courseid");
		$stm->execute(array(':courseid'=>$cid));
		while ($row=$stm->fetch(PDO::FETCH_NUM)) {
			if ($row[0]=="InlineText") {
				$table = "imas_inlinetext";
			} else if ($row[0]=="LinkedText") {
				$table = "imas_linkedtext";
			} else if ($row[0]=="Forum") {
				$table = "imas_forums";
			} else if ($row[0]=="Assessment") {
				$table = "imas_assessments";
			} else if ($row[0]=="Calendar") {
				continue;
			} else if ($row[0]=="Wiki") {
				$table = "imas_wikis";
			}
			//DB $query = "UPDATE $table SET startdate=startdate+$shift WHERE id='{$row[1]}' AND startdate>0";
			//DB mysql_query($query) or die("Query failed : $query" . mysql_error());
			$stm2 = $DBH->prepare("UPDATE $table SET startdate=startdate+:shift WHERE id=:id AND startdate>0");
			$stm2->execute(array(':id'=>$row[1], ':shift'=>$shift));
			//DB $query = "UPDATE $table SET enddate=enddate+$shift WHERE id='{$row[1]}' AND enddate<2000000000";
			//DB mysql_query($query) or die("Query failed : $query" . mysql_error());
			$stm2 = $DBH->prepare("UPDATE $table SET enddate=enddate+:shift WHERE id=:id AND enddate<2000000000");
			$stm2->execute(array(':id'=>$row[1], ':shift'=>$shift));

			if ($row[0]=="Wiki") {
				//DB $query = "UPDATE $table SET editbydate=editbydate+$shift WHERE id='{$row[1]}' AND editbydate>0 AND editbydate<2000000000";
				//DB mysql_query($query) or die("Query failed : $query" . mysql_error());
				$stm2 = $DBH->prepare("UPDATE $table SET editbydate=editbydate+:shift WHERE id=:id AND editbydate>0 AND editbydate<2000000000");
				$stm2->execute(array(':id'=>$row[1], ':shift'=>$shift));
			} else if ($row[0]=="Forum") {
				//DB $query = "UPDATE $table SET replyby=replyby+$shift WHERE id='{$row[1]}' AND replyby>0 AND replyby<2000000000";
				//DB mysql_query($query) or die("Query failed : $query" . mysql_error());
				$stm2 = $DBH->prepare("UPDATE $table SET replyby=replyby+:shift WHERE id=:id AND replyby>0 AND replyby<2000000000");
				$stm2->execute(array(':id'=>$row[1], ':shift'=>$shift));

				//DB $query = "UPDATE $table SET postby=postby+$shift WHERE id='{$row[1]}' AND postby>0 AND postby<2000000000";
				//DB mysql_query($query) or die("Query failed : $query" . mysql_error());
				$stm2 = $DBH->prepare("UPDATE $table SET postby=postby+:shift WHERE id=:id AND postby>0 AND postby<2000000000");
				$stm2->execute(array(':id'=>$row[1], ':shift'=>$shift));
			}
		}

		//update Calendar items
		//DB $query = "UPDATE imas_calitems SET date=date+$shift WHERE courseid='$cid'";
		//DB mysql_query($query) or die("Query failed : $query" . mysql_error());
		$stm = $DBH->prepare("UPDATE imas_calitems SET date=date+:shift WHERE courseid=:courseid");
		$stm->execute(array(':courseid'=>$cid, ':shift'=>$shift));

		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?cid=$cid");

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

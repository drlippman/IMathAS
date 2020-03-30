<?php
//IMathAS:  Add/modify blocks of items on course page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../init.php");
require("../includes/htmlutil.php");
require_once("../includes/parsedatetime.php");


/*** pre-html data manipulation, including function code *******/

//set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = "Make Exception";
$cid = Sanitize::courseId($_GET['cid']);
$asid = Sanitize::onlyInt($_GET['asid']);
$aid = Sanitize::onlyInt($_GET['aid']);
$uid = Sanitize::onlyInt($_GET['uid']);
if (isset($_GET['stu'])) {
	$stu = $_GET['stu'];
} else {
	$stu=0;
}
if (isset($_GET['from'])) {
	$from = $_GET['from'];
} else {
	$from = 'gb';
}

if (!isset($_GET['asid']) || $asid == 0) {
	//assess 2
	$backurl = $GLOBALS['basesiteurl'] . "/assess2/gbviewassess.php?"
		. Sanitize::generateQueryStringFromMap(array('cid' => $cid, 'aid' => $aid, 'uid' => $uid,
			'stu' => $stu, 'from' => $from, 'r' => $rpq));
} else {
	$backurl = $GLOBALS['basesiteurl'] . "/course/gb-viewasid.php?"
		. Sanitize::generateQueryStringFromMap(array('cid' => $cid, 'asid' => $asid, 'uid' => $uid,
			'stu' => $stu, 'from' => $from, 'r' => $rpq));
}

$curBreadcrumb = $breadcrumbbase;
if (!isset($_SESSION['ltiitemtype']) || $_SESSION['ltiitemtype']!=0) {
	$curBreadcrumb .= "<a href=\"course.php?cid=". Sanitize::courseId($_GET['cid'])."\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
	if ($stu>0) {
		$curBreadcrumb .= "<a href=\"gradebook.php?stu=0&cid=$cid\">Gradebook</a> ";
		$curBreadcrumb .= "&gt; <a href=\"gradebook.php?stu=" . Sanitize::encodeUrlParam($stu) . "&cid=$cid\">Student Detail</a> &gt; ";
	} else if ($_GET['from']=="isolate") {
		$curBreadcrumb .= " <a href=\"gradebook.php?stu=0&cid=$cid\">Gradebook</a> ";
		$curBreadcrumb .= "&gt; <a href=\"isolateassessgrade.php?cid=$cid&aid=" . Sanitize::onlyInt($aid) . "\">View Scores</a> &gt; ";
	} else if ($_GET['from']=="gisolate") {
		$curBreadcrumb .= "<a href=\"gradebook.php?stu=0&cid=$cid\">Gradebook</a> ";
		$curBreadcrumb .= "&gt; <a href=\"isolateassessbygroup.php?cid=$cid&aid=" . Sanitize::onlyInt($aid) . "\">View Group Scores</a> &gt; ";
	}else if ($_GET['from']=='stugrp') {
		$curBreadcrumb .= "<a href=\"managestugrps.php?cid=$cid&aid=" . Sanitize::onlyInt($aid) . "\">Student Groups</a> &gt; ";
	} else {
		$curBreadcrumb .= "<a href=\"gradebook.php?stu=0&cid=$cid\">Gradebook</a> &gt; ";
	}
}

$curBreadcrumb .= "<a href=\"$backurl\">Assessment Detail</a> &gt Make Exception\n";

if (!(isset($teacherid))) { // loaded by a NON-teacher
	$overwriteBody=1;
	$body = "You need to log in as a teacher to access this page";
} elseif (!(isset($_GET['cid']))) {
	$overwriteBody=1;
	$body = "You need to access this page from the course page menu";
} else { // PERMISSIONS ARE OK, PROCEED WITH PROCESSING
	$cid = Sanitize::courseId($_GET['cid']);
	$waivereqscore = (isset($_POST['waivereqscore']))?1:0;
	$epenalty = (isset($_POST['overridepenalty']))?intval($_POST['newpenalty']):null;

	if (isset($_POST['sdate'])) {
		$startdate = parsedatetime($_POST['sdate'],$_POST['stime']);
		$enddate = parsedatetime($_POST['edate'],$_POST['etime']);

		//check if exception already exists
		$stm = $DBH->prepare("SELECT id FROM imas_exceptions WHERE userid=:userid AND assessmentid=:assessmentid");
		$stm->execute(array(':userid'=>$_GET['uid'], ':assessmentid'=>$aid));
		$row = $stm->fetch(PDO::FETCH_NUM);
		if ($row != null) {
			$stm = $DBH->prepare("UPDATE imas_exceptions SET startdate=:startdate,enddate=:enddate,islatepass=0,waivereqscore=:waivereqscore,exceptionpenalty=:exceptionpenalty WHERE id=:id");
			$stm->execute(array(':startdate'=>$startdate, ':enddate'=>$enddate, ':waivereqscore'=>$waivereqscore, ':exceptionpenalty'=>$epenalty, ':id'=>$row[0]));
		} else {
			$query = "INSERT INTO imas_exceptions (userid,assessmentid,startdate,enddate,waivereqscore,exceptionpenalty) VALUES ";
			$query .= "(:userid, :assessmentid, :startdate, :enddate, :waivereqscore, :exceptionpenalty)";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':userid'=>$_GET['uid'], ':assessmentid'=>$aid, ':startdate'=>$startdate, ':enddate'=>$enddate,
				':waivereqscore'=>$waivereqscore, ':exceptionpenalty'=>$epenalty));
		}
		if (isset($_POST['eatlatepass'])) {
			$n = intval($_POST['latepassn']);
			$stm = $DBH->prepare("UPDATE imas_students SET latepass = CASE WHEN latepass>$n THEN latepass-$n ELSE 0 END WHERE userid=:userid AND courseid=:courseid");
			$stm->execute(array(':userid'=>$_GET['uid'], ':courseid'=>$cid));
		}

		//force regen?
		if (isset($_POST['forceregen'])) {
			//this is not group-safe
			$stu = $_GET['uid'];
			$aid = Sanitize::onlyInt($_GET['aid']);
			$stm = $DBH->prepare("SELECT shuffle FROM imas_assessments WHERE id=:id");
			$stm->execute(array(':id'=>$aid));
			list($shuffle) = $stm->fetch(PDO::FETCH_NUM);
			$allqsameseed = (($shuffle&2)==2);
			$stm = $DBH->prepare("SELECT id,questions,lastanswers,scores FROM imas_assessment_sessions WHERE userid=:userid AND assessmentid=:assessmentid");
			$stm->execute(array(':userid'=>$stu, ':assessmentid'=>$aid));
			if ($stm->rowCount()>0) {
				$row = $stm->fetch(PDO::FETCH_NUM);
				if (strpos($row[1],';')===false) {
					$questions = explode(",",$row[1]);
					$bestquestions = $questions;
				} else {
					list($questions,$bestquestions) = explode(";",$row[1]);
					$questions = explode(",",$questions);
				}
				$lastanswers = explode('~',$row[2]);
				$curscorelist = $row[3];
				$scores = array(); $attempts = array(); $seeds = array(); $reattempting = array();
				for ($i=0; $i<count($questions); $i++) {
					$scores[$i] = -1;
					$attempts[$i] = 0;
					if ($allqsameseed && $i>0) {
						$seeds[$i] = $seeds[0];
					} else {
						$seeds[$i] = rand(1,9999);
					}
					$newla = array();
					$laarr = explode('##',$lastanswers[$i]);
					//may be some files not accounted for here...
					//need to fix
					foreach ($laarr as $lael) {
						if ($lael=="ReGen") {
							$newla[] = "ReGen";
						}
					}
					$newla[] = "ReGen";
					$lastanswers[$i] = implode('##',$newla);
				}
				$scorelist = implode(',',$scores);
				if (strpos($curscorelist,';')!==false) {
					$scorelist = $scorelist.';'.$scorelist;
				}
				$attemptslist = implode(',',$attempts);
				$seedslist = implode(',',$seeds);
				$lastanswers = str_replace('~','',$lastanswers);
				$lalist = implode('~',$lastanswers);
				$reattemptinglist = implode(',',$reattempting);
				$query = "UPDATE imas_assessment_sessions SET scores=:scores,attempts=:attempts,seeds=:seeds,lastanswers=:lastanswers,";
				$query .= "reattempting=:reattempting WHERE id=:id";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':scores'=>$scorelist, ':attempts'=>$attemptslist, ':seeds'=>$seedslist, ':lastanswers'=>$lalist,
					':reattempting'=>$reattemptinglist, ':id'=>$row[0]));
			}

		}

		header('Location: ' . $backurl);

	} else if (isset($_GET['clear'])) {
		$stm = $DBH->prepare("DELETE FROM imas_exceptions WHERE id=:id");
		$stm->execute(array(':id'=>$_GET['clear']));
		$rpq =  Sanitize::randomQueryStringParam();
		header('Location: ' . $backurl);
	} elseif (isset($_GET['aid']) && $_GET['aid']!='') {
		$stm = $DBH->prepare("SELECT LastName,FirstName FROM imas_users WHERE id=:id");
		$stm->execute(array(':id'=>$uid));
		$stuname = implode(', ', $stm->fetch(PDO::FETCH_NUM));
		$stm = $DBH->prepare("SELECT startdate,enddate,date_by_lti,ver FROM imas_assessments WHERE id=:id");
		$stm->execute(array(':id'=>Sanitize::onlyInt($_GET['aid'])));
		$row = $stm->fetch(PDO::FETCH_NUM);
		$sdate = tzdate("m/d/Y",$row[0]);
		$edate = tzdate("m/d/Y",$row[1]);
		$stime = tzdate("g:i a",$row[0]);
		$etime = tzdate("g:i a",$row[1]);
		$isDateByLTI = ($row[2]>0);
		$aVer = $row[3];

		//check if exception already exists
		$stm = $DBH->prepare("SELECT id,startdate,enddate,waivereqscore,exceptionpenalty FROM imas_exceptions WHERE userid=:userid AND assessmentid=:assessmentid");
		$stm->execute(array(':userid'=>$_GET['uid'], ':assessmentid'=>Sanitize::onlyInt($_GET['aid'])));
		$erow = $stm->fetch(PDO::FETCH_NUM);
		$page_isExceptionMsg = "";
		$savetitle = _('Create Exception');
		if ($erow != null) {
			$savetitle = _('Save Changes');
			$page_isExceptionMsg = "<p>An exception already exists.  <button type=\"button\" onclick=\"window.location.href='exception.php?cid=$cid&aid=" . Sanitize::onlyInt($_GET['aid']) . "&uid=" . Sanitize::onlyInt($_GET['uid']) . "&clear=" . Sanitize::encodeUrlParam($erow[0]) . "&asid=" . Sanitize::onlyInt($asid) . "&stu=" . Sanitize::encodeUrlParam($stu) . "&from=" . Sanitize::encodeUrlParam($from) . "'\">"._("Clear Exception").'</button> or modify below.</p>';
			$sdate = tzdate("m/d/Y",$erow[1]);
			$edate = tzdate("m/d/Y",$erow[2]);
			$stime = tzdate("g:i a",$erow[1]);
			$etime = tzdate("g:i a",$erow[2]);
			$curwaive = $erow[3];
			$curepenalty = $erow[4];
		}
		if ($isDateByLTI) {
			$page_isExceptionMsg .= '<p class="noticetext">Note: You have opted to allow your LMS to set assessment dates.  If you need to give individual ';
			$page_isExceptionMsg .=  'students different due dates, you should do so in your LMS, not here, as the date from the LMS will be given ';
			$page_isExceptionMsg .= 'priority.  Only create a manual exception here if it is for a special purpose, like waiving a prerequisite.</p>';
		}
	}
	//DEFAULT LOAD DATA MANIPULATION
	$address = $GLOBALS['basesiteurl'] . "/course/exception.php?" . Sanitize::generateQueryStringFromMap(array(
			'cid' => $_GET['cid'], 'uid' => $_GET['uid'], 'asid' => $asid, 'stu' => $stu, 'from' => $from));
	$stm = $DBH->prepare("SELECT id,name from imas_assessments WHERE courseid=:courseid ORDER BY name");
	$stm->execute(array(':courseid'=>$cid));
	$page_courseSelect = array();
	$i=0;
	while ($line=$stm->fetch(PDO::FETCH_ASSOC)) {
		$page_courseSelect['val'][$i] = $line['id'];
		$page_courseSelect['label'][$i] = $line['name'];
		$i++;
	}

}
$stm = $DBH->prepare("SELECT latepass FROM imas_students WHERE userid=:userid AND courseid=:courseid");
$stm->execute(array(':userid'=>$_GET['uid'], ':courseid'=>$cid));
$latepasses = $stm->fetchColumn(0);

/******* begin html output ********/
$placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/DatePicker.js\"></script>";
 require("../header.php");

if ($overwriteBody==1) {
	echo $body;
} else {
?>
	<script type="text/javascript">
	function nextpage() {
	   var aid = document.getElementById('aidselect').value;
	   var togo = '<?php echo Sanitize::url($address); ?>&aid=' + aid;
	   window.location = togo;
	}
	</script>


	<div class=breadcrumb><?php echo $curBreadcrumb ?></div>
	<div id="headerexception" class="pagetitle"><h1>Make Start/Due Date Exception</h1></div>

<?php
	echo '<h2>'.Sanitize::encodeStringForDisplay($stuname).'</h2>';
	echo $page_isExceptionMsg;
	echo '<p><span class="form">Assessment:</span><span class="formright">';
	writeHtmlSelect ("aidselect",$page_courseSelect['val'],$page_courseSelect['label'],Sanitize::onlyInt($_GET['aid']),"Select an assessment","", " onchange='nextpage()'");
	echo '</span><br class="form"/></p>';

	if (isset($_GET['aid']) && $_GET['aid']!='') {
		$exceptionUrl = "exception.php?" . Sanitize::generateQueryStringFromMap(array('cid' => $cid,
				'aid' => $_GET['aid'], 'uid' => $_GET['uid'], 'stu' => $stu, 'asid' => $asid, 'from' => $from,
            ));
?>
	<form method=post action="<?php echo $exceptionUrl; ?>">
		<span class=form>Available After:</span>
		<span class=formright>
			<input type=text size=10 name=sdate value="<?php echo $sdate ?>">
			<a href="#" onClick="displayDatePicker('sdate', this); return false">
			<img src="../img/cal.gif" alt="Calendar"/></A>
			at <input type=text size=10 name=stime value="<?php echo $stime ?>">
		</span><BR class=form>
		<span class=form>Available Until:</span>
		<span class=formright>
			<input type=text size=10 name=edate value="<?php echo $edate ?>">
			<a href="#" onClick="displayDatePicker('edate', this); return false">
			<img src="../img/cal.gif" alt="Calendar"/></A>
			at <input type=text size=10 name=etime value="<?php echo $etime ?>">
		</span><BR class=form>
<?php
if ($aVer == 1) { // only allow this option for old assess UI for now. TODO
?>
		<span class="form"><input type="checkbox" name="forceregen"/></span>
		<span class="formright">Force student to work on new versions of all questions?  Students
		   will keep any scores earned, but must work new versions of questions to improve score. <i>Do not use with group assessments</i>.</span><br class="form"/>
<?php
}
?>
		<span class="form"><input type="checkbox" name="eatlatepass"/></span>
		<span class="formright">Deduct <input type="input" name="latepassn" size="1" value="1"/> LatePass(es).
		   Student currently has <?php echo Sanitize::onlyInt($latepasses);?> latepasses.</span><br class="form"/>
		<span class="form"><input type="checkbox" name="waivereqscore" <?php if ($curwaive==1) echo 'checked="checked"';?>/></span>
		<span class="formright">Waive "show based on an another assessment" requirements, if applicable.</span><br class="form"/>
		<span class="form"><input type="checkbox" name="overridepenalty" <?php if ($curepenalty!==null) echo 'checked="checked"';?>/></span>
		<span class="formright">Override default exception/LatePass penalty.  Deduct <input type="input" name="newpenalty" size="2" value="<?php echo ($curepenalty===null)?0:Sanitize::onlyFloat($curepenalty);?>"/>% for questions done while in exception.
		<div class=submit><input type=submit value="<?php echo $savetitle;?>"></div>
	</form>

<?php
	}
}
require("../footer.php");
?>

<?php
//IMathAS:  Add/modify blocks of items on course page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../validate.php");
require("../includes/htmlutil.php");
require_once("../includes/parsedatetime.php");

/*** pre-html data manipulation, including function code *******/

//set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = "Make Exception";
$cid = $_GET['cid'];
$asid = $_GET['asid'];
$aid = $_GET['aid'];
$uid = $_GET['uid'];

$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a>";
$curBreadcrumb .= "&gt; <a href=\"gradebook.php?cid=$cid\">Gradebook</a> &gt; <a href=\"gb-viewasid.php?cid=$cid&asid=$asid&uid=$uid\">Assessment Detail</a> &gt Make Exception\n";

if (!(isset($teacherid))) { // loaded by a NON-teacher
	$overwriteBody=1;
	$body = "You need to log in as a teacher to access this page";
} elseif (!(isset($_GET['cid']))) {
	$overwriteBody=1;
	$body = "You need to access this page from the course page menu";
} else { // PERMISSIONS ARE OK, PROCEED WITH PROCESSING
	$cid = $_GET['cid'];
	
	if (isset($_POST['sdate'])) {
		$startdate = parsedatetime($_POST['sdate'],$_POST['stime']);
		$enddate = parsedatetime($_POST['edate'],$_POST['etime']);
		
		//check if exception already exists
		$query = "SELECT id FROM imas_exceptions WHERE userid='{$_GET['uid']}' AND assessmentid='{$_GET['aid']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$row = mysql_fetch_row($result);
		if ($row != null) {
			$query = "UPDATE imas_exceptions SET startdate=$startdate,enddate=$enddate,islatepass=0 WHERE id='{$row[0]}'";
			mysql_query($query) or die("Query failed :$query " . mysql_error());
		} else {
			$query = "INSERT INTO imas_exceptions (userid,assessmentid,startdate,enddate) VALUES ";
			$query .= "('{$_GET['uid']}','{$_GET['aid']}',$startdate,$enddate)";
			$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
		}
		if (isset($_POST['eatlatepass'])) {
			$n = intval($_POST['latepassn']);
			$query = "UPDATE imas_students SET latepass = CASE WHEN latepass>$n THEN latepass-$n ELSE 0 END WHERE userid='{$_GET['uid']}' AND courseid='$cid'";
			mysql_query($query) or die("Query failed :$query " . mysql_error());
		}
		
		//force regen?
		if (isset($_POST['forceregen'])) {
			//this is not group-safe
			$stu = $_GET['uid'];
			$aid = $_GET['aid'];
			$query = "SELECT id,questions,lastanswers FROM imas_assessment_sessions WHERE userid='$stu' AND assessmentid='$aid'";
			$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
			if (mysql_num_rows($result)>0) {
				$row = mysql_fetch_row($result);
				$questions = explode(',',$row[1]);
				$lastanswers = explode('~',$row[2]);
				$scores = array(); $attempts = array(); $seeds = array();
				for ($i=0; $i<count($questions); $i++) {
					$scores[$i] = -1;
					$attempts[$i] = 0;
					$seeds[$i] = rand(1,9999);
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
					$reattempting = array();
				}
				$scorelist = implode(',',$scores);
				$attemptslist = implode(',',$attempts);
				$seedslist = implode(',',$seeds);
				$lastanswers = str_replace('~','',$lastanswers);
				$lalist = implode('~',$lastanswers);
				$lalist = addslashes(stripslashes($lalist));
				$reattemptinglist = implode(',',$reattempting);
				$query = "UPDATE imas_assessment_sessions SET scores='$scorelist',attempts='$attemptslist',seeds='$seedslist',lastanswers='$lalist',";
				$query .= "reattempting='$reattemptinglist' WHERE id='{$row[0]}'";
				mysql_query($query) or die("Query failed :$query " . mysql_error());
			}
			
		}
		
		header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gb-viewasid.php?cid=$cid&asid=$asid&uid=$uid");
		
	} else if (isset($_GET['clear'])) {
		$query = "DELETE FROM imas_exceptions WHERE id='{$_GET['clear']}'";
		mysql_query($query) or die("Query failed :$query " . mysql_error());
		header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gb-viewasid.php?cid=$cid&asid=$asid&uid=$uid");
	} elseif (isset($_GET['aid']) && $_GET['aid']!='') {
		$query = "SELECT startdate,enddate FROM imas_assessments WHERE id='{$_GET['aid']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$row = mysql_fetch_row($result);
		$sdate = tzdate("m/d/Y",$row[0]);
		$edate = tzdate("m/d/Y",$row[1]);
		$stime = tzdate("g:i a",$row[0]);
		$etime = tzdate("g:i a",$row[1]);

		//check if exception already exists
		$query = "SELECT id,startdate,enddate FROM imas_exceptions WHERE userid='{$_GET['uid']}' AND assessmentid='{$_GET['aid']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$erow = mysql_fetch_row($result);
		$page_isExceptionMsg = "";
		if ($erow != null) {
			$page_isExceptionMsg = "<p>Exception exists.  <a href=\"exception.php?cid=$cid&aid={$_GET['aid']}&uid={$_GET['uid']}&clear={$erow[0]}&asid=$asid\">Clear Exception</a></p>\n";
			$sdate = tzdate("m/d/Y",$erow[1]);
			$edate = tzdate("m/d/Y",$erow[2]);
			$stime = tzdate("g:i a",$erow[1]);
			$etime = tzdate("g:i a",$erow[2]);
		}	
	} 
	//DEFAULT LOAD DATA MANIPULATION
	$address = $urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/exception.php?cid={$_GET['cid']}&uid={$_GET['uid']}&asid=$asid";

	$query = "SELECT id,name from imas_assessments WHERE courseid='$cid' ORDER BY name";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$page_courseSelect = array();
	$i=0;
	while ($line=mysql_fetch_array($result, MYSQL_ASSOC)) {
		$page_courseSelect['val'][$i] = $line['id'];
		$page_courseSelect['label'][$i] = $line['name'];
		$i++;
	}
	
}

$query = "SELECT latepass FROM imas_students WHERE userid='{$_GET['uid']}' AND courseid='$cid'";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
$latepasses = mysql_result($result,0,0);

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
	   var togo = '<?php echo $address; ?>&aid='+aid;
	   window.location = togo;
	}
	</script>
	

	<div class=breadcrumb><?php echo $curBreadcrumb ?></div>	
	<div id="headerexception" class="pagetitle"><h2>Make Start/Due Date Exception</h2></div>

<?php 
	writeHtmlSelect ("aidselect",$page_courseSelect['val'],$page_courseSelect['label'],$_GET['aid'],"Select an assessment","", " onchange='nextpage()'"); 

	if (isset($_GET['aid']) && $_GET['aid']!='') {
		echo $page_isExceptionMsg;
?>		
	<form method=post action="exception.php?cid=<?php echo $cid ?>&aid=<?php echo $_GET['aid'] ?>&uid=<?php echo $_GET['uid'] ?>&asid=<?php echo $asid;?>">
		<span class=form>For this student:</span><br class=form>
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

		<div class=submit><input type=submit value="Submit"></div>
		<p><input type="checkbox" name="forceregen"/> Force student to work on new versions of all questions?  Students 
		will keep any scores earned, but must work new versions of questions to improve score.</p>
		<p><input type="checkbox" name="eatlatepass"/> Deduct <input type="input" name="latepassn" size="1" value="1"/> LatePass(es).  Student currently has <?php echo $latepasses;?> latepasses.</p>
	</form>

<?php
	}
}	
require("../footer.php");
?>

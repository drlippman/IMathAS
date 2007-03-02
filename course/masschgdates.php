<?php
//IMathAS:  Mass Change Assessment Dates
//(c) 2006 David Lippman
	require("../validate.php");
	
	if (!(isset($teacherid))) {
		require("../header.php");
		echo "You need to log in as a teacher to access this page";
		require("../footer.php");
		exit;
	}
	
	$cid = $_GET['cid'];
	
	if (isset($_POST['chgcnt'])) {
		$cnt = $_POST['chgcnt'];
		for ($i=0; $i<$cnt; $i++) {
			require_once("parsedatetime.php");
			if ($_POST['sdate'.$i]=='0') {
				$startdate = 0;
			} else {
				$startdate = parsedatetime($_POST['sdate'.$i],$_POST['stime'.$i]);
			}
			if ($_POST['edate'.$i]=='2000000000') {
				$enddate = 2000000000;
			} else {
				$enddate = parsedatetime($_POST['edate'.$i],$_POST['etime'.$i]);
			}
			if ($_POST['rdate'.$i]=='0') {
				$reviewdate = 0;
			} else if ($_POST['rdate'.$i]=='2000000000') {
				$reviewdate = 2000000000;
			} else {
				$reviewdate = parsedatetime($_POST['rdate'.$i],$_POST['rtime'.$i]);	
			}
			$aid = $_POST['aid'.$i];
			if ($aid>0) {
				$query = "UPDATE imas_assessments SET startdate='$startdate',enddate='$enddate',reviewdate='$reviewdate' WHERE id='$aid'";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
			
		}
		header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/course.php?cid=$cid");
			
		exit;
	}
	
	$pagetitle = "Mass Change Assessment Dates";
	$placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/masschgdates.js\"></script>";
	require("../header.php");
	echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"course.php?cid=$cid\">$coursename</a> ";	
	echo "&gt; Mass Change Assessment Dates</div>\n";
	echo "<h2>Mass Change Assessment Dates</h2>";
	echo '<script src="../javascript/CalendarPopup.js"></script>';
	echo '<SCRIPT LANGUAGE="JavaScript" ID="js1">';
	echo 'var cal1 = new CalendarPopup();';
	echo 'var basesdates = new Array(); var baseedates = new Array(); var baserdates = new Array();';
	echo '</SCRIPT>';
	
	if (isset($_GET['orderby'])) {
		$orderby = $_GET['orderby'];
	} else {
		$orderby = 0;
	}
	
	echo '<p>Ordered by ';
	if ($orderby==0) {
		echo 'Start Date. ';
	} else if ($orderby==1) {
		echo 'End Date. ';
	} else if ($orderby==2) {
		echo 'Name. ';
	}
	if ($orderby!=0) {
		echo "<a href=\"masschgdates.php?cid=$cid&orderby=0\">Order by Start Date</a> ";
	}
	if ($orderby!=1) {
		echo "<a href=\"masschgdates.php?cid=$cid&orderby=1\">Order by End Date</a> ";
	}
	if ($orderby!=2) {
		echo "<a href=\"masschgdates.php?cid=$cid&orderby=2\">Order by Name</a> ";
	}
	echo '</p>';
	
	echo "<form method=post action=\"masschgdates.php?cid=$cid\">";
	echo '<table class=gb><thead><tr><th>Assessment</th><th>Modify<sup>*</sup></th><th>Start Date</th><th>End Date</th><th>Review Date</th><th>Send Date Chg Down List</th></thead><tbody>';
	$query = "SELECT name,startdate,enddate,reviewdate,id FROM imas_assessments WHERE courseid='$cid' ";
	if ($orderby==0) {
		$query .= 'ORDER BY startdate';
	} else if ($orderby==1) {
		$query .= 'ORDER BY enddate';
	} else if ($orderby==2) {
		$query .= 'ORDER BY name';
	}
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$cnt = 0;
	$now = time();
	while ($row = mysql_fetch_row($result)) {
		$sdate = tzdate("m/d/Y",$row[1]);
		$stime = tzdate("g:i a",$row[1]);
		$edate = tzdate("m/d/Y",$row[2]);
		$etime = tzdate("g:i a",$row[2]);
		$rdate = tzdate("m/d/Y",$row[3]);
		$rtime = tzdate("g:i a",$row[3]);
		echo '<tr class=grid>';
		echo "<td>{$row[0]}<input type=hidden name=\"aid$cnt\" value=\"{$row[4]}\"/>";
		echo "<script> basesdates[$cnt] = ";
		if ($row[1]==0) { echo '"NA"';} else {echo $row[1];}
		echo "; baseedates[$cnt] = ";
		if ($row[2]==0 || $row[2]==2000000000) { echo '"NA"';} else {echo $row[2];}
		echo "; baserdates[$cnt] = ";
		if ($row[3]==0 || $row[3]==2000000000) {echo '"NA"';} else { echo $row[3];}
		echo ";</script>";
		echo "</td><td>";
		if ($now>$row[1] && $now<$row[2]) {
			echo "<i><a href=\"addquestions.php?aid={$row[4]}&cid=$cid\">Questions</a></i>";	
		} else {
			echo "<a href=\"addquestions.php?aid={$row[4]}&cid=$cid\">Questions</a>";
		}
		echo " <a href=\"addassessment.php?id={$row[4]}&cid=$cid\">Settings</a></td>\n";
				  
		if ($row[1]==0) {
			echo "<td><input type=hidden id=\"sdate$cnt\" name=\"sdate$cnt\" value=\"0\"/>Always</td>";
		} else {
			echo "<td><input type=text size=10 id=\"sdate$cnt\" name=\"sdate$cnt\" value=\"$sdate\">";
			echo "<a href=\"#\" onClick=\"cal1.select(document.forms[0].sdate$cnt,'anchor$cnt','MM/dd/yyyy',document.forms[0].sdate$cnt.value); return false;\" NAME=\"anchor$cnt\" ID=\"anchor$cnt\"><img src=\"../img/cal.gif\" alt=\"Calendar\"/></a>";
			echo " at <input type=text size=10 id=\"stime$cnt\" name=\"stime$cnt\" value=\"$stime\"></td>";
		}
		if ($row[2]==2000000000) {
			echo "<td><input type=hidden id=\"edate$cnt\" name=\"edate$cnt\" value=\"2000000000\"/>Always</td>";
		} else {
			echo "<td><input type=text size=10 id=\"edate$cnt\" name=\"edate$cnt\" value=\"$edate\">";
			echo "<a href=\"#\" onClick=\"cal1.select(document.forms[0].edate$cnt,'anchor2$cnt','MM/dd/yyyy',document.forms[0].edate$cnt.value); return false;\" NAME=\"anchor2$cnt\" ID=\"anchor2$cnt\"><img src=\"../img/cal.gif\" alt=\"Calendar\"/></a>";
			echo " at <input type=text size=10 id=\"etime$cnt\" name=\"etime$cnt\" value=\"$etime\"></td>";
		}
		if ($row[3]==0) {
			echo "<td><input type=hidden id=\"rdate$cnt\" name=\"rdate$cnt\" value=\"0\"/>Never</td>";
		} else if ($row[3]==2000000000) {
			echo "<td><input type=hidden id=\"rdate$cnt\" name=\"rdate$cnt\" value=\"2000000000\"/>Always</td>";
		} else {
			echo "<td><input type=text size=10 id=\"rdate$cnt\" name=\"rdate$cnt\" value=\"$rdate\">";
			echo "<a href=\"#\" onClick=\"cal1.select(document.forms[0].rdate$cnt,'anchor3$cnt','MM/dd/yyyy',document.forms[0].rdate$cnt.value); return false;\" NAME=\"anchor3$cnt\" ID=\"anchor3$cnt\"><img src=\"../img/cal.gif\" alt=\"Calendar\"/></a>";
			echo " at <input type=text size=10 id=\"rtime$cnt\" name=\"rtime$cnt\" value=\"$rtime\"></td>";
		}
		echo "<td><input type=button value=\"Send Down List\" onclick=\"senddown($cnt)\"/></td>";
		echo "</tr>";
		$cnt++;
	}
	echo '</tbody></table>';
	echo "<input type=hidden name=\"chgcnt\" value=\"$cnt\" />";
	echo '<input type=submit value="Save Changes"/>';
	echo '</form>';
	//echo "<script>var acnt = $cnt;</script>";
	echo "<p><sup>*</sup> If Modify Questions is in <i>italics</i>, then the assessment is currently available, and modifying questions is <b>not recommended<b></p>";
	
	require("../footer.php");

?>

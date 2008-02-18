<?php
	require("../validate.php");
	$isteacher = (isset($teacherid) || $sessiondata['isteacher']==true);
	if (!isset($sessiondata['sessiontestid']) && !$isteacher) {
		echo "<html><body>Error. </body></html>\n";
		exit;
	}
	
	include("displayq2.php");
	require("header.php");
	echo "<style type=\"text/css\">p.tips {	display: none;}\n input.btn {display: none;}\n textarea {display: none;}\n</style>\n";
	
	function unans($sc) {
		if (strpos($sc,'~')===false) {
			return ($sc<0);
		} else {
			return (strpos($sc,'-1'));
		}
	}
	function getpointspossible($qn,$def) {
		$query = "SELECT points FROM imas_questions WHERE id='$qn'";
		$result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
		 	if ($row[0] == 9999) {
				$possible = $def;
			} else {
				$possible = $row[0];
			}
		}
		return $possible;
	}
	if ($isteacher && isset($_GET['asid'])) {
		$testid = $_GET['asid'];
	} else {
		$testid = addslashes($sessiondata['sessiontestid']);
	}
	$query = "SELECT * FROM imas_assessment_sessions WHERE id='$testid'";
	$result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
	$line = mysql_fetch_array($result, MYSQL_ASSOC);
	$questions = explode(",",$line['questions']);
	$seeds = explode(",",$line['seeds']);
	$scores = explode(",",$line['scores']);
	$attempts = explode(",",$line['attempts']);
	$lastanswers = explode("~",$line['lastanswers']);
	
	$query = "SELECT * FROM imas_assessments WHERE id='{$line['assessmentid']}'";
	$result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
	$testsettings = mysql_fetch_array($result, MYSQL_ASSOC);
	list($testsettings['testtype'],$testsettings['showans']) = explode('-',$testsettings['deffeedback']);
	
	$now = time();
	$isreview = false;
	if ($now < $testsettings['startdate'] || $testsettings['enddate']<$now) { //outside normal range for test
		$query = "SELECT startdate,enddate FROM imas_exceptions WHERE userid='$userid' AND assessmentid='{$line['assessmentid']}'";
		$result2 = mysql_query($query) or die("Query failed : " . mysql_error());
		$row = mysql_fetch_row($result2);
		if ($row!=null) {
			if ($now<$row[0] || $row[1]<$now) { //outside exception dates
				if ($now > $testsettings['startdate'] && $now<$testsettings['reviewdate']) {
					$isreview = true;
				} else {
					if (!$isteacher) {
						echo "Assessment is closed";
						echo "<br/><a href=\"../course/course.php?cid={$testsettings['courseid']}\">Return to course page</a>";
						exit;
					}
				}
			}
		} else { //no exception
			if ($now > $testsettings['startdate'] && $now<$testsettings['reviewdate']) {
				$isreview = true;
			} else {
				if (!$isteacher) {
					echo "Assessment is closed";
					echo "<br/><a href=\"../course/course.php?cid={$testsettings['courseid']}\">Return to course page</a>";
					exit;
				}
			}
		}
	}
	if ($isreview) {
		$seeds = explode(",",$line['reviewseeds']);
		$scores = explode(",",$line['reviewscores']);
		$attempts = explode(",",$line['reviewattempts']);
		$lastanswers = explode("~",$line['reviewlastanswers']);
	}
	
	echo "<h4 style=\"float:right;\">Name: $userfullname </h4>\n";
	echo "<h3>".$testsettings['name']."</h3>\n";
	
	
	$allowregen = ($testsettings['testtype']=="Practice" || $testsettings['testtype']=="Homework");
	$showeachscore = ($testsettings['testtype']=="Practice" || $testsettings['testtype']=="AsGo" || $testsettings['testtype']=="Homework");
	$showansduring = (($testsettings['testtype']=="Practice" || $testsettings['testtype']=="Homework") && $testsettings['showans']!='N');
	echo "<div class=breadcrumb>Print Ready Version</div>";
	echo '<div class=intro>'.$testsettings['intro'].'</div>';
	for ($i = 0; $i < count($questions); $i++) {
		list($qsetid,$cat) = getqsetid($questions[$i]);
		
		$showa = false;
		echo "<div>#".($i+1)." Points possible: " . getpointspossible($questions[$i],$testsettings['defpoints']) . "</div>";
		displayq($i,$qsetid,$seeds[$i],$showa,($testsettings['showhints']==1),$attempts[$i]);
		echo "<hr />";	
		
	}
?>

<?php
	require("../validate.php");
	if (!isset($sessiondata['sessiontestid'])) {
		echo "<html><body>Error. </body></html>\n";
		exit;
	}
	include("displayq2.php");
	require("header.php");
	echo "<style type=\"text/css\">p.tips {	display: none;}\n input.btn {display: none;}\n</style>\n";
	
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
	$testid = addslashes($sessiondata['sessiontestid']);
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
	
	echo "<h4 style=\"float:right;\">Name: ______________________</h4>\n";
	echo "<h3>".$testsettings['name']."</h3>\n";
	
	
	$allowregen = ($testsettings['testtype']=="Practice" || $testsettings['testtype']=="Homework");
	$showeachscore = ($testsettings['testtype']=="Practice" || $testsettings['testtype']=="AsGo" || $testsettings['testtype']=="Homework");
	$showansduring = (($testsettings['testtype']=="Practice" || $testsettings['testtype']=="Homework") && $testsettings['showans']!='N');
	echo "<div class=breadcrumb>Print Ready Version</div>";
	for ($i = 0; $i < count($questions); $i++) {
		list($qsetid,$cat) = getqsetid($questions[$i]);
		
		$showa = false;
		echo "<div>#".($i+1)." Points possible: " . getpointspossible($questions[$i],$testsettings['defpoints']) . "</div>";
		displayq($i,$qsetid,$seeds[$i],$showa);
		echo "<hr />";	
		
	}
?>

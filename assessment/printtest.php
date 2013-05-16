<?php
	require("../validate.php");
	$isteacher = (isset($teacherid) || $sessiondata['isteacher']==true);
	if (!isset($sessiondata['sessiontestid']) && !$isteacher) {
		echo "<html><body>Error. </body></html>\n";
		exit;
	}
	if (isset($teacherid) && isset($_GET['scored'])) {
		echo "scorediew";
		$scoredview = true;
	} else {
		$scoredview = false;
	}
	
	include("displayq2.php");
	include("testutil.php");
	$flexwidth = true; //tells header to use non _fw stylesheet
	if ($scoredview) {
		$placeinhead = '<script type="text/javascript">
			$(function() {
				$(\'input[value="Preview"]\').click().hide();
			});
			</script>';
	}
	require("header.php");
	echo "<style type=\"text/css\" media=\"print\">p.tips {	display: none;}\n input.btn {display: none;}\n textarea {display: none;}\n input.sabtn {display: none;}</style>\n";
	echo "<style type=\"text/css\">p.tips {	display: none;}\n </style>\n";
	echo '<script type="text/javascript">function rendersa() { ';
	echo '  el = document.getElementsByTagName("span"); ';
	echo '   for (var i=0;i<el.length;i++) {';
	echo '     if (el[i].className=="hidden") { ';
	echo '         el[i].className = "shown";';
	//echo '		 AMprocessNode(el)';
	echo '     }';
	echo '    }';
	echo '} </script>';
	
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
	$scores = explode(",",$line['bestscores']);
	$attempts = explode(",",$line['bestattempts']);
	$lastanswers = explode("~",$line['bestlastanswers']);

	if ($isteacher) {
		if ($line['userid']!=$userid) {
			$query = "SELECT LastName,FirstName FROM imas_users WHERE id='{$line['userid']}'";
			$result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
			$row = mysql_fetch_row($result);
			$userfullname = $row[1]." ".$row[0];
		}
		$userid= $line['userid'];
	}
	
	$query = "SELECT * FROM imas_assessments WHERE id='{$line['assessmentid']}'";
	$result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
	$testsettings = mysql_fetch_array($result, MYSQL_ASSOC);
	list($testsettings['testtype'],$testsettings['showans']) = explode('-',$testsettings['deffeedback']);
	
	$qi = getquestioninfo($questions,$testsettings);
	
	
	$now = time();
	$isreview = false;
	if (!$scoredview && ($now < $testsettings['startdate'] || $testsettings['enddate']<$now)) { //outside normal range for test
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
	if ($isteacher) {
		echo '<input type="button" class="btn" onclick="rendersa()" value="Show Answers" />';
	}
	if ($testsettings['showans']=='N') {
		$lastanswers = array_fill(0,count($questions),'');
	}
	for ($i = 0; $i < count($questions); $i++) {
		//list($qsetid,$cat) = getqsetid($questions[$i]);
		$qsetid = $qi[$questions[$i]]['questionsetid'];
		$cat = $qi[$questions[$i]]['category'];
		
		$showa = $isteacher;
		echo '<div class="nobreak">';
		if (isset($_GET['descr'])) {
			$query = "SELECT description FROM imas_questionset WHERE id='$qsetid'";
			$result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
			echo '<div>ID:'.$qsetid.', '.mysql_result($result,0,0).'</div>';
		} else {
			//list($points,$qattempts) = getpointspossible($questions[$i],$testsettings['defpoints'],$testsettings['defattempts']);
			$points = $qi[$questions[$i]]['points'];
			$qattempts = $qi[$questions[$i]]['attempts'];
			if ($scoredview) {
				echo "<div>#".($i+1)." ";
				echo printscore($scores[$i], $i);
				echo "</div>";
			} else {
				echo "<div>#".($i+1)." Points possible: $points.  Total attempts: $qattempts</div>";
			}
		}
		if ($scoredview) {
			$col = scorestocolors($scores[$i], $qi[$questions[$i]]['points'], $qi[$questions[$i]]['answeights']);
			displayq($i, $qsetid,$seeds[$i],2,false,$attempts[$i],false,false,false,$col);	
		} else {
			displayq($i,$qsetid,$seeds[$i],$showa,($testsettings['showhints']==1),$attempts[$i]);
		}
		echo "<hr />";	
		echo '</div>';
		
	}
?>

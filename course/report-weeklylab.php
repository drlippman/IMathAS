<?php 
//IMathAS:  Course Recent Report
//(c) 2016 David Cooper, David Lippman

/*** master php includes *******/
require("../validate.php");

/*** pre-html data manipulation, including function code *******/

// this gets points from the scores string
// warning this code was copied from courseshowitems.php
// if something changes with the way scores are stored,
// then this will need to be updated.
function getpts($scs) {
  $tot = 0;
  foreach(explode(',',$scs) as $sc) {
    $qtot = 0;
    if (strpos($sc,'~')===false) {
      if ($sc>0) { 
	$qtot = $sc;
      } 
    } else {
      $sc = explode('~',$sc);
      foreach ($sc as $s) {
	if ($s>0) { 
	  $qtot+=$s;
	}
      }
    }
    $tot += round($qtot,1);
  }
  return $tot;
}

function getpointspossible($aid, $itemorder, $defaultpoints) {
	$aitems = explode(',', $itemorder); 
	$k = 0;
	$atofind = array();
	foreach ($aitems as $v) {
		if (strpos($v,'~')!==FALSE) {
			$sub = explode('~',$v);
			if (strpos($sub[0],'|')===false) { //backwards compat
				$atofind[$k] = $sub[0];
				$aitemcnt[$k] = 1;
				$k++;
			} else {
				$grpparts = explode('|',$sub[0]);
				if ($grpparts[0]==count($sub)-1) { //handle diff point values in group if n=count of group
					for ($i=1;$i<count($sub);$i++) {
						$atofind[$k] = $sub[$i];
						$aitemcnt[$k] = 1;
						$k++;
					}
				} else {
					$atofind[$k] = $sub[1];
					$aitemcnt[$k] = $grpparts[0];
					$k++;
				}
			}
		} else {
			$atofind[$k] = $v;
			$aitemcnt[$k] = 1;
			$k++;
		}
	}
	
	$query = "SELECT points,id FROM imas_questions WHERE assessmentid='$aid'";
	$result2 = mysql_query($query) or die("Query failed : $query: " . mysql_error());
	$totalpossible = 0;
	while ($r = mysql_fetch_row($result2)) {
		if (($k = array_search($r[1],$atofind))!==false) { //only use first item from grouped questions for total pts	
			if ($r[0]==9999) {
				$totalpossible += $aitemcnt[$k]*$defaultpoints; //use defpoints
			} else {
				$totalpossible += $aitemcnt[$k]*$r[0]; //use points from question
			}
		}
	}	
	return $totalpossible;
}



//set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$cid = intval($_GET['cid']);
$pagetitle = 'Weekly Report - Lab Style Course';

if (!isset($teacherid) && !isset($tutorid) && !isset($studentid)) { //loaded by someone not in the course
	$overwriteBody=1;
	$body = _("You are not enrolled in this course.  Please return to the <a href=\"../index.php\">Home Page</a> and enroll\n");
} else if (!isset($teacherid) && !isset($tutorid)) { //loaded by a NON-teacher
	$overwriteBody=1;
	$body = _("You need to log in as a teacher to access this page\n");
} else { // PERMISSIONS ARE OK, PROCEED WITH PROCESSING

	//TODO:  Fix for timezone handling
	$oneweekago = strtotime("1 week ago");
	//$oneweekago = 0;
	$sincemonday = strtotime("Monday this week");
	
	$rangestart = $oneweekago;
	$timedescr = "In the last week";
	
	//break credit from non-credit for assignments without minscore
	$breakpercent = 75;  
		
	// Set up the main $st array with first and last name for all students
	// in a class
	$query = "select userid, firstName, lastName from imas_students as stu join imas_users as iu on iu.id = stu.userid  where courseid ='$cid' ";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$totalstudents = 0;

	//student data array. Initialize with names and zero out totals and assessment lists
	$st = array();
	while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		$uid = $line['userid'];
		$totalstudents++;
		$st[$uid]['stuname']  = $line['lastName'].', '.$line['firstName'];
		$st[$uid]['stuattemptCnt'] = 0;
		$st[$uid]['stuNocredCnt'] = 0;
		$st[$uid]['stuGotcreditCnt']  = 0;
		$st[$uid]['stuNocreditAssessList'] = array();
		$st[$uid]['stuCreditAssessList'] = array();
		$st[$uid]['totalPointsOnAttempted'] = 0;
		$st[$uid]['totalPointsPossibleOnAttempted'] = 0;
	}

	//pull assessment data
	//We're pulling the assessment info (name, itemorder) along with scores to
	//reduce number of database pulls at the expense of pulling more data
	$query = "select ias.userid, ia.name, ia.minscore, ias.bestscores, ia.id, ia.defpoints, ia.itemorder ";
	$query .= " from imas_assessment_sessions as ias join imas_assessments as ia on assessmentid=ia.id  ";
	$query .= " where (ia.courseid = '$cid' and endtime > $rangestart ) ";
	$query .= " order by ias.userid ";
	$result = mysql_query($query) or die("Query failed : " . mysql_error()); 
	 
	$assessmentInfo = array();
	$totalAttemptCount = 0;
	while($line = mysql_fetch_assoc($result)) {
		$uid = $line['userid'];
		$aid = $line['id'];
		$totalAttemptCount++;
		
		$st[$uid]['stuattemptCnt']++;
		
		//store assessment info
		if (!isset($assessmentInfo[$aid])) {
			$assessmentInfo[$aid]['name'] = $line['name'];
			$assessmentInfo[$aid]['attempts'] = 1;
			$assessmentInfo[$aid]['possible'] = getpointspossible($aid, $line['itemorder'], $line['defpoints']);
			$assessmentInfo[$aid]['minscore'] = $line['minscore'];
			$assessmentInfo[$aid]['totalPointsEarned'] = 0;
			$assessmentInfo[$aid]['nocreditstulist'] = array();
			$assessmentInfo[$aid]['gotcreditstulist'] = array();
		} else {
			$assessmentInfo[$aid]['attempts']++;
		}
		
		//get student scores
		$sp = explode(';', $line['bestscores']);   //scores:rawscores
		$pts = getpts($sp[0]);
		
		$st[$uid]['totalPointsOnAttempted'] += $pts;
		$st[$uid]['totalPointsPossibleOnAttempted'] += $assessmentInfo[$aid]['possible'];
		$assessmentInfo[$aid]['totalPointsEarned'] += $pts;
		
		if ($line['minscores']!=0) { //use minscore
			$minscore = $assessmentInfo[$aid]['minscore'];
			if (($minscore<10000 && $pts<$minscore) || ($minscore>10000 && $pts<($minscore-10000)/100*$assessmentInfo[$aid]['possible'])) {
				//student did not get credit
				$st[$uid]['stuNocredCnt']++;
				$st[$uid]['stuNocreditAssessList'][] = $aid;
				$assessmentInfo[$aid]['nocreditstulist'][] = $uid;
			} else {
				//student did get credit
				$st[$uid]['stuGotcreditCnt']++;        
				$st[$uid]['stuCreditAssessList'][] = $aid;
				$assessmentInfo[$aid]['gotcreditstulist'][] = $uid;
			}
		} else { //no minscore, so use alternate break value
			if (100*$pts/$assessmentInfo[$aid]['possible'] < $breakpercent) {
				//student did not get credit
				$st[$uid]['stuNocredCnt']++;
				$st[$uid]['stuNocreditAssessList'][] = $aid;
				$assessmentInfo[$aid]['nocreditstulist'][] = $uid;
			} else {
				//student did get credit
				$st[$uid]['stuGotcreditCnt']++;        
				$st[$uid]['stuCreditAssessList'][] = $aid;
				$assessmentInfo[$aid]['gotcreditstulist'][] = $uid;
			}
		}
		
	}
	
	$totalstudents = count($st);
	$attemptedstudents = 0;
	foreach ($st as $user) {
		if ($user['stuattemptCnt']>0) {
			$attemptedstudents++;
		}
	}
	
	$assessmentcount = count($assessmentInfo);
	
	
	$curBreadcrumb = $breadcrumbbase;
	$curBreadcrumb .= "<a href=\"course.php?cid=$cid\">$coursename</a> ";
	$curBreadcrumb .= "&gt; <a href=\"coursereports.php?cid=$cid\">Course Reports</a> ";
	
	$placeinhead = '<script type="text/javascript">function toggleList(el) { $(el).find("ul").toggle();}
		function highlightrow(el) { $(el).addClass("highlight");}
		function unhighlightrow(el) { $(el).removeClass("highlight");}
		function expandAllSummaries(type) { $("."+type+"ul").toggle(true);}
		function collapseAllSummaries(type) { $("."+type+"ul").toggle(false);}
	</script>';
}
	
/******* begin html output ********/
require("../header.php");

/**** post-html data manipulation ******/
// this page has no post-html data manipulation

/***** page body *****/
/***** php display blocks are interspersed throughout the html as needed ****/
if ($overwriteBody==1) {
	echo $body;
} else {

	echo '<div class="breadcrumb">'. $curBreadcrumb . '&gt; Weekly Report - Lab</div>';
	
	echo '<div class="pagetitle"><h2>Weekly Report - Lab Style Courses</h2></div>';
	echo '<p>This report summarizes the activy for students over the last week.  Activity is divided into ';
	echo '"credit" or "no credit".  If a "minimum score to receive credit" is set for the assignment, that is ';
	echo 'used to make the classification.  Otherwise, a score of '.$breakpercent.' is used.</p>';
	
	echo '<h3>'.$timedescr.'</h3>';
	echo '<table class="gb">';
	echo '<tr> <td>'.$attemptedstudents.' (out of '.$totalstudents.') </td><td> Students attempted at least one assessment. </td></tr>';
	echo '<tr> <td class="r">'.$assessmentcount.'</td><td> Assessments were attempted. </td></tr>';
	echo '<tr> <td class="r">'. $totalAttemptCount.'</td><td> Total attempts were made. </td></tr>';
	echo '</table>';

?>
<h3>Student Summary: </h3>
<p>Click a row for details. 
   <button type="button" onclick="expandAllSummaries('stu')">Expand All</button>
   <button type="button" onclick="collapseAllSummaries('stu')">Collapse All</button>
   </p>

<table class="gb">
<thead><tr>
   <th> Student </th>
   <th> Number of Assessments Attempted </th>
   <th> Cumulative Score </th>
   <th> Asessments with No Credit </th>
   <th> Asessments with Credit </th>
</tr>   </thead><tbody>
   
<?php

//sort student list
uasort($st, function($a,$b) {return strcasecmp($a['stuname'],$b['stuname']);});

$i = 0;
foreach ($st as $uid=>$stu) {
	if ($i%2!=0) {
		echo "<tr class=even onMouseOver=\"highlightrow(this)\" onMouseOut=\"unhighlightrow(this)\" onclick=\"toggleList(this)\">"; 
	} else {
		echo "<tr class=odd onMouseOver=\"highlightrow(this)\" onMouseOut=\"unhighlightrow(this)\" onclick=\"toggleList(this)\">"; 
	}
	
	echo '<td>'.$stu['stuname'].'</td>';
	echo '<td class="c">'.$stu['stuattemptCnt'].'</td>';
	if ($stu['totalPointsOnAttempted']>0) {
		echo '<td>'.$stu['totalPointsOnAttempted'].'/'.$stu['totalPointsPossibleOnAttempted'];
		echo ' ('. round(100*$stu['totalPointsOnAttempted']/$stu['totalPointsPossibleOnAttempted'],1) .'%)</td>';
	} else {
		echo '<td>N/A</td>';
	}
	if ($stu['stuNocredCnt'] > 0) {
		uasort($stu['stuNocreditAssessList'], function($a,$b) {global $assessmentInfo; return strcasecmp($assessmentInfo[$a]['name'],$assessmentInfo[$b]['name']);});
		
		echo '<td class="c" >'.$stu['stuNocredCnt'];
		echo '<ul class="nomark stuul" style="display:none">';
		foreach ($stu['stuNocreditAssessList'] as $aid) {
			echo '<li>'.$assessmentInfo[$aid]['name'].'</li>';
		}
		echo '</ul>';
	} else {
		echo '<td class="c">0';
	}
	echo '</td>';

	if ($stu['stuGotcreditCnt'] > 0) {
		uasort($stu['stuCreditAssessList'], function($a,$b) {global $assessmentInfo; return strcasecmp($assessmentInfo[$a]['name'],$assessmentInfo[$b]['name']);});
		
		echo '<td class="c" onclick="toggleList(\'stu_'.$uid.'\')">'.$stu['stuGotcreditCnt'];
		echo '<ul class="nomark stuul" style="display:none">';
		foreach ($stu['stuCreditAssessList'] as $aid) {
			echo '<li>'.$assessmentInfo[$aid]['name'].'</li>';
		}
		echo '</ul>';
	} else {
		echo '<td class="c">0';
	}
	echo '</td>';
	echo '</tr>';
	
	$i++;
}
?>

</tbody>
</table>

<br/>

<h3>Assessment Summary: </h3>

<p>Click a row for details. 
   <button type="button" onclick="expandAllSummaries('as')">Expand All</button>
   <button type="button" onclick="collapseAllSummaries('as')">Collapse All</button>
   </p>

<table class="gb">
  <thead>
   <th> Assessment </th>
   <th> Num Attempts </th>
   <th> Average Score </th>
   <th> No Credit </th>
   <th> Credit </th>
  </thead>
  <tbody>
<?php

//sort assessment list by name
//might be better to do a course order sort someday
uasort($assessmentInfo, function($a,$b) {return strcasecmp($a['name'],$b['name']);});

$i = 0;
foreach ($assessmentInfo as $aid=>$ainfo) {
	if ($i%2!=0) {
		echo "<tr class=even onMouseOver=\"highlightrow(this)\" onMouseOut=\"unhighlightrow(this)\" onclick=\"toggleList(this)\">"; 
	} else {
		echo "<tr class=odd onMouseOver=\"highlightrow(this)\" onMouseOut=\"unhighlightrow(this)\" onclick=\"toggleList(this)\">"; 
	}
	
	echo '<td>'.$ainfo['name'].'</td>';
	echo '<td class="c">'. $ainfo['attempts'] .'</td>';
	
	echo '<td class="c">'. round(100*($ainfo['totalPointsEarned']/$ainfo['attempts'])/$ainfo['possible'],1) .'</td>';

	if (count($ainfo['nocreditstulist']) > 0) {
		uasort($ainfo['nocreditstulist'], function($a,$b) {global $st; return strcasecmp($st[$a]['stuname'],$st[$b]['stuname']);});
		echo '<td class="c" onclick="toggleList(\'as_'.$aid.'\')">'.count($ainfo['nocreditstulist']);
		echo '<ul class="nomark asul" style="display:none">';
		foreach ($ainfo['nocreditstulist'] as $uid) {
			echo '<li>'.$st[$uid]['stuname'].'</li>';
		}
		echo '</ul>';
	} else {
		echo '<td class="c">0';
	}
	echo '</td>';

	if (count($ainfo['gotcreditstulist']) > 0) {
		uasort($ainfo['gotcreditstulist'], function($a,$b) {global $st; return strcasecmp($st[$a]['stuname'],$st[$b]['stuname']);});
		echo '<td class="c" onclick="toggleList(\'as_'.$aid.'\')">'.count($ainfo['gotcreditstulist']);
		echo '<ul class="nomark asul" style="display:none">';
		foreach ($ainfo['gotcreditstulist'] as $uid) {
			echo '<li>'.$st[$uid]['stuname'].'</li>';
		}
		echo '</ul>';
	} else {
		echo '<td class="c">0';
	}
	echo '</td>';
	echo '</tr>';
	
	$i++;
}
?>
</tbody>
</table>

<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
<?php
require("../footer.php");
}

?>

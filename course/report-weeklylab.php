<?php
//IMathAS:  Course Recent Report
//(c) 2016 David Cooper, David Lippman

/*** master php includes *******/
require("../init.php");
require("../includes/htmlutil.php");


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


//set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$cid = Sanitize::courseId($_GET['cid']);
$pagetitle = 'Activity Report - Lab Style Course';

if (!isset($teacherid) && !isset($tutorid) && !isset($studentid)) { //loaded by someone not in the course
	$overwriteBody=1;
	$body = _("You are not enrolled in this course.  Please return to the <a href=\"../index.php\">Home Page</a> and enroll\n");
} else if (!isset($teacherid) && !isset($tutorid)) { //loaded by a NON-teacher
	$overwriteBody=1;
	$body = _("You need to log in as a teacher to access this page\n");
} else { // PERMISSIONS ARE OK, PROCEED WITH PROCESSING

	if (isset($_GET['gbmode']) && $_GET['gbmode']!='') {
		$gbmode = $_GET['gbmode'];
	} else if (isset($sessiondata[$cid.'gbmode'])) {
		$gbmode =  $sessiondata[$cid.'gbmode'];
	} else {
		//DB $query = "SELECT defgbmode FROM imas_gbscheme WHERE courseid='$cid'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $gbmode = mysql_result($result,0,0);
		$stm = $DBH->prepare("SELECT defgbmode FROM imas_gbscheme WHERE courseid=:courseid");
		$stm->execute(array(':courseid'=>$cid));
		$gbmode = $stm->fetchColumn(0);
	}
	$hidelocked = ((floor($gbmode/100)%10&2)); //0: show locked, 1: hide locked

	if (isset($_POST['interval'])) {
		//settings update postback
		if (!isset($sessiondata['reportsettings-weeklylab'])) {
			$sessiondata['reportsettings-weeklylab'] = array();
		}
		$sessiondata['reportsettings-weeklylab'.$cid]['interval'] = $_POST['interval'];
		$sessiondata['reportsettings-weeklylab'.$cid]['useminscore'] = isset($_POST['useminscore']);
		$sessiondata['reportsettings-weeklylab'.$cid]['breakpercent'] = intval($_POST['breakpercent']);
		writesessiondata();

		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/report-weeklylab.php?cid=$cid" . "&r=" . Sanitize::randomQueryStringParam());
		exit;
	}

	if (isset($sessiondata['reportsettings-weeklylab'.$cid])) {
		$interval = $sessiondata['reportsettings-weeklylab'.$cid]['interval'];
		$useminscore = $sessiondata['reportsettings-weeklylab'.$cid]['useminscore'];
		$breakpercent = $sessiondata['reportsettings-weeklylab'.$cid]['breakpercent'];

	} else {
		$interval = '1week';
		$useminscore = true;
		$breakpercent = 75;
	}

	$intervalarr = array(	'today'=>'today',
				'thisweek'=>'this week (since Monday)',
				'1week'=>'one week (7 days)',
				'2week'=>'two weeks (14 days)',
				'4week'=>'one month (30 days)',
				'alltime'=>'all time');

	list($yr,$month,$day,$dayofweek) = explode('-', date('Y-m-d-w'));
	$startofday = mktime(0,0,0,$month,$day,$yr);

	if ($interval=='1week' || ($interval=='thisweek' && $dayofweek==0)) {
		$rangestart = $startofday - 7*24*60*60;
		$timedescr = 'In the last week (7 days)';
	} else if ($interval=='today' || ($interval=='thisweek' && $dayofweek==1)) {
		$rangestart = $startofday;
		$timedescr = 'Today';
	} else if ($interval=='thisweek') {
		$rangestart = $startofday - ($dayofweek-1)*24*60*60;
		$timedescr = 'This week (since Monday)';
	} else if ($interval=='2week') {
		$rangestart = $startofday - 14*24*60*60;
		$timedescr = 'In the last two weeks (14 days)';
	} else if ($interval=='4week') {
		$rangestart = $startofday - 30*24*60*60;
		$timedescr = 'In the last month (30 days)';
	} else if ($interval=='alltime') {
		$rangestart = 0;
		$timedescr = 'Since the start of the course';
	}

	// Set up the main $st array with first and last name for all students
	// in a class
	//DB $query = "select userid, firstName, lastName from imas_students as stu join imas_users as iu on iu.id = stu.userid  where courseid ='$cid' ";
	$query = "select userid, firstName, lastName from imas_students as stu join imas_users as iu on iu.id = stu.userid  where courseid=:courseid ";
	if ($hidelocked) {
		$query .= "AND stu.locked=0 ";
	}
  $stm = $DBH->prepare($query);
  $stm->execute(array(':courseid'=>$cid));
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	$totalstudents = 0;

	//student data array. Initialize with names and zero out totals and assessment lists
	$st = array();
	//DB while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
	while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
		$uid = $line['userid'];
		$totalstudents++;
		$st[$uid]['stuname']  = $line['lastName'].', '.$line['firstName'];
		$st[$uid]['stuNocreditAssessList'] = array();
		$st[$uid]['stuCreditAssessList'] = array();
		$st[$uid]['totalTimeOnTask'] = 0;
		$st[$uid]['totalPointsOnAttempted'] = 0;
		$st[$uid]['totalPointsPossibleOnAttempted'] = 0;
	}

	//pull assessment data
	//We're pulling the assessment info (name, itemorder) along with scores to
	//reduce number of database pulls at the expense of pulling more data
	//DB $query = "select ias.userid, ia.name, ia.minscore, ias.bestscores, ia.id, ia.defpoints, ia.itemorder ";
	//DB $query .= " from imas_assessment_sessions as ias join imas_assessments as ia on assessmentid=ia.id  ";
	//DB $query .= " where (ia.courseid = '$cid' and endtime > $rangestart ) ";
	//DB $query .= " order by ias.userid ";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	$query = "select ias.userid, ia.name, ia.minscore, ias.bestscores, ias.timeontask, ia.id, ia.defpoints, ia.itemorder, ia.ptsposs ";
	$query .= " from imas_assessment_sessions as ias join imas_assessments as ia on assessmentid=ia.id  ";
	$query .= " where (ia.courseid=:courseid and endtime > :rangestart ) ";
	$query .= " order by ias.userid ";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':courseid'=>$cid, ':rangestart'=>$rangestart));

	$assessmentInfo = array();
	$totalAttemptCount = 0;
	//DB while($line = mysql_fetch_assoc($result)) {
	while($line = $stm->fetch(PDO::FETCH_ASSOC)) {
		$uid = $line['userid'];
		if (!isset($st[$uid])) { continue; } //not a student we're reporting on
		$aid = $line['id'];
		$totalAttemptCount++;

		if ($line['ptsposs']==-1) {
			require_once("../includes/updateptsposs.php");
			$line['ptsposs'] = updatePointsPossible($line['id'], $line['itemorder'], $line['defpoints']);
		}
		//store assessment info
		if (!isset($assessmentInfo[$aid])) {
			$assessmentInfo[$aid]['name'] = $line['name'];
			$assessmentInfo[$aid]['attempts'] = 1;
			$assessmentInfo[$aid]['possible'] = $line['ptsposs'];
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

		$st[$uid]['totalTimeOnTask'] += array_sum(explode(',',str_replace('~',',',$line['timeontask'])));
		$st[$uid]['totalPointsOnAttempted'] += $pts;
		$st[$uid]['totalPointsPossibleOnAttempted'] += $assessmentInfo[$aid]['possible'];
		$assessmentInfo[$aid]['totalPointsEarned'] += $pts;

		if ($assessmentInfo[$aid]['minscore']!=0 && $useminscore) { //use minscore
			$minscore = $assessmentInfo[$aid]['minscore'];
			if (($minscore<10000 && $pts<$minscore) || ($minscore>10000 && $pts<($minscore-10000)/100*$assessmentInfo[$aid]['possible'])) {
				//student did not get credit
				$st[$uid]['stuNocreditAssessList'][] = $aid;
				$assessmentInfo[$aid]['nocreditstulist'][] = $uid;
			} else {
				//student did get credit
				$st[$uid]['stuCreditAssessList'][] = $aid;
				$assessmentInfo[$aid]['gotcreditstulist'][] = $uid;
			}
		} else { //no minscore, so use alternate break value
			if (100*$pts/$assessmentInfo[$aid]['possible'] < $breakpercent) {
				//student did not get credit
				$st[$uid]['stuNocreditAssessList'][] = $aid;
				$assessmentInfo[$aid]['nocreditstulist'][] = $uid;
			} else {
				//student did get credit
				$st[$uid]['stuCreditAssessList'][] = $aid;
				$assessmentInfo[$aid]['gotcreditstulist'][] = $uid;
			}
		}

	}

	$totalstudents = count($st);
	$attemptedstudents = 0;
	foreach ($st as $user) {
		if (count($user['stuCreditAssessList'])>0 || count($user['stuNocreditAssessList'])>0) {
			$attemptedstudents++;
		}
	}

	$assessmentcount = count($assessmentInfo);


	$curBreadcrumb = $breadcrumbbase;
	$curBreadcrumb .= "<a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
	$curBreadcrumb .= "&gt; <a href=\"coursereports.php?cid=$cid\">Course Reports</a> ";

	$placeinhead = '<script type="text/javascript">function toggleList(el) { $(el).find("ul").toggle();}
		function highlightrow(el) { $(el).addClass("highlight");}
		function unhighlightrow(el) { $(el).removeClass("highlight");}
		function expandAllSummaries(type) { $("."+type+"ul").toggle(true);}
		function collapseAllSummaries(type) { $("."+type+"ul").toggle(false);}
		function toggleadv(el) {
			if ($("#viewfield").is(":hidden")) {
				$(el).html("Hide report settings");
				$("#viewfield").slideDown();
			} else {
				$(el).html("Edit report settings");
				$("#viewfield").slideUp();
			}
		}
	</script>';
	$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablesorter.js?v=062216\"></script>\n";
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

	echo '<div class="breadcrumb">'. $curBreadcrumb . '&gt; Activity Report - Lab</div>';


	echo '<div class="pagetitle"><h2>Activity Report - Lab Style Courses</h2></div>';
	echo '<p>This report summarizes the activity of students for '.$intervalarr[$interval].'.<br/>';
	if ($useminscore) {
		echo 'Activity is divided into "credit" or "no credit". If a "minimum score to receive credit" is set for the assignment, that is ';
		echo 'used to make the classification.  Otherwise, a score of '.$breakpercent.' is used.</p>';
	} else {
		echo 'Activity is divided into "credit" or "no credit" using a score of '.$breakpercent.'.</p>';
	}

	echo '<p><a href="#" onclick="toggleadv(this);return false">Edit report settings</a></p>';
	echo '<fieldset style="display:none;" id="viewfield"><legend>Report Settings</legend>';
	echo '<form method="post" action="report-weeklylab.php?cid='.$cid.'">';
	echo '<span class="form">Time interval to display:</span>';
	echo '<span class="formright">';
	writeHtmlSelect('interval',array_keys($intervalarr),array_values($intervalarr),$interval,'1week');
	echo '</span><br class="form"/>';
	echo '<span class="form">Use &quot;minimum score to receive credit&quot; (if defined) to determine credit/no-credit:</span>';
	echo '<span class="formright"><input type="checkbox" name="useminscore" value="1" '.getHtmlChecked($useminscore,true).'/></span>';
	echo '<br class="form"/>';
	echo '<span class="form">Score to separate credit/no-credit:</span>';
	echo '<span class="formright"><input type="text" size="3" name="breakpercent" value="'.$breakpercent.'"/>%</span>';
	echo '<br class="form"/>';

	echo '<div class="submit"><input type="submit" value="Update"/></div>';

	echo '</form></fieldset>';


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

<table class="gb" id="stuTable">
<thead><tr>
   <th> Student </th>
   <th> Number of Assessments Attempted </th>
   <th> Total Time in Questions </th>
   <th> Cumulative Score </th>
   <th> Assessments with No Credit </th>
   <th> Assessments with Credit </th>
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

	printf('<td>%s</td>', Sanitize::encodeStringForDisplay($stu['stuname']));

	$stuattemptedCnt = count($stu['stuCreditAssessList'])+count($stu['stuNocreditAssessList']);
	echo '<td class="c">'.$stuattemptedCnt.'</td>';

	echo '<td class="c">';
	if ($stu['totalTimeOnTask']<180) {
		echo round($stu['totalTimeOnTask']/60,1).' min';
	} else {
		echo round($stu['totalTimeOnTask']/3600,1).' hrs';
	}
	echo '</td>';

	if ($stu['totalPointsPossibleOnAttempted']>0) {
		printf('<td>%s/%s', Sanitize::encodeStringForDisplay($stu['totalPointsOnAttempted']),
            Sanitize::encodeStringForDisplay($stu['totalPointsPossibleOnAttempted']));
		echo ' ('. round(100*$stu['totalPointsOnAttempted']/$stu['totalPointsPossibleOnAttempted'],1) .'%)</td>';
	} else {
		echo '<td>N/A</td>';
	}
	echo '<td class="c">';
	if (count($stu['stuNocreditAssessList']) > 0) {
		uasort($stu['stuNocreditAssessList'], function($a,$b) {global $assessmentInfo; return strcasecmp($assessmentInfo[$a]['name'],$assessmentInfo[$b]['name']);});

		echo count($stu['stuNocreditAssessList']);
		echo '<ul class="nomark stuul" style="display:none">';
		foreach ($stu['stuNocreditAssessList'] as $aid) {
			printf('<li>%s</li>', Sanitize::encodeStringForDisplay($assessmentInfo[$aid]['name']));
		}
		echo '</ul>';
	} else {
		echo '0';
	}
	echo '</td>';

	echo '<td class="c">';
	if (count($stu['stuCreditAssessList']) > 0) {
		uasort($stu['stuCreditAssessList'], function($a,$b) {global $assessmentInfo; return strcasecmp($assessmentInfo[$a]['name'],$assessmentInfo[$b]['name']);});

		echo count($stu['stuCreditAssessList']);
		echo '<ul class="nomark stuul" style="display:none">';
		foreach ($stu['stuCreditAssessList'] as $aid) {
			printf('<li>%s</li>', Sanitize::encodeStringForDisplay($assessmentInfo[$aid]['name']));
		}
		echo '</ul>';
	} else {
		echo '0';
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

<table class="gb" id="assessTable">
  <thead>
   <th> Assessment </th>
   <th> Number of Attempts </th>
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

	echo '<td class="c">'. round(100*($ainfo['totalPointsEarned']/$ainfo['attempts'])/$ainfo['possible'],1) .'%</td>';

	echo '<td class="c">';
	if (count($ainfo['nocreditstulist']) > 0) {
		uasort($ainfo['nocreditstulist'], function($a,$b) {global $st; return strcasecmp($st[$a]['stuname'],$st[$b]['stuname']);});
		echo count($ainfo['nocreditstulist']);
		echo '<ul class="nomark asul" style="display:none">';
		foreach ($ainfo['nocreditstulist'] as $uid) {
			printf('<li>%s</li>', Sanitize::encodeStringForDisplay($st[$uid]['stuname']));
		}
		echo '</ul>';
	} else {
		echo '0';
	}
	echo '</td>';

	echo '<td class="c">';
	if (count($ainfo['gotcreditstulist']) > 0) {
		uasort($ainfo['gotcreditstulist'], function($a,$b) {global $st; return strcasecmp($st[$a]['stuname'],$st[$b]['stuname']);});
		echo count($ainfo['gotcreditstulist']);
		echo '<ul class="nomark asul" style="display:none">';
		foreach ($ainfo['gotcreditstulist'] as $uid) {
			printf('<li>%s</li>', Sanitize::encodeStringForDisplay($st[$uid]['stuname']));
		}
		echo '</ul>';
	} else {
		echo '0';
	}
	echo '</td>';
	echo '</tr>';

	$i++;
}
?>
</tbody>
</table>

<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>

<script type="text/javascript">
$(function() {
 initSortTable('stuTable',Array("S","N","P","N","N"),true,true,true);
 initSortTable('assessTable',Array("S","N","N","N","N"),true,true,true);
});
</script>
<?php
require("../footer.php");
}

?>

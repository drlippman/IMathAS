<?php
//(c) 2013 David Lippman, part of IMathAS
//
// Assessment "badness" report, based on
// Lumen Learning's ImprovOER model

require("../init.php");

if (!isset($teacherid) && !isset($adminid)) {
	exit;
}

//set globals for gbtable2
$setorderby = 2;
$includeduedate = true;
$canviewall = true;
$catfilter = -1;
$secfilter = -1;
require("gbtable2.php");

$gbt = gbtable();

$lastrow = count($gbt)-1;
$n = count($gbt)-2;
if ($n==0) {
	echo "Need at least 1 student's data";
	exit;
}
$assessmetrics = array();
$maxattemptratio = 0;

//estimate course start/end dates
//DB $query = "SELECT min(enddate),max(enddate) FROM imas_assessments WHERE courseid='$cid' AND enddate<2000000000";
//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
//DB $row = mysql_fetch_row($result);
$stm = $DBH->prepare("SELECT min(enddate),max(enddate) FROM imas_assessments WHERE courseid=:courseid AND enddate<2000000000");
$stm->execute(array(':courseid'=>$cid));
$row = $stm->fetch(PDO::FETCH_NUM);
$mindate = $row[0];
$maxdate = $row[1];

//DB $query = "SELECT min(showdate),max(showdate) FROM imas_gbitems WHERE courseid='$cid' AND showdate<2000000000";
//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
//DB $row = mysql_fetch_row($result);
$stm = $DBH->prepare("SELECT min(showdate),max(showdate) FROM imas_gbitems WHERE courseid=:courseid AND showdate<2000000000");
$stm->execute(array(':courseid'=>$cid));
$row = $stm->fetch(PDO::FETCH_NUM);
if ($row[0]<$mindate) { $mindate = $row[0];}
if ($row[1]>$maxdate) { $maxdate = $row[1];}

$discussdates = array();
//DB $query = "SELECT id,postby,replyby FROM imas_forums WHERE courseid='$cid'";
//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
//DB while ($row = mysql_fetch_row($result)) {
$stm = $DBH->prepare("SELECT id,postby,replyby FROM imas_forums WHERE courseid=:courseid");
$stm->execute(array(':courseid'=>$cid));
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	if ($row[2]<2000000000) {
		$discussdates[$row[0]] = $row[2];
	} else if ($row[1]<2000000000) {
		$discussdates[$row[0]] = $row[1];
	}
}


$numsubmissions = array();
$numattempts = array();
$timesonfirst = array();
//pull assessment data to look at number of attempts
//DB $query = "SELECT ia.defattempts,ias.* FROM imas_assessment_sessions as ias JOIN imas_assessments as ia ON ias.assessmentid=ia.id AND ia.courseid='$cid'";
//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
//DB while ($row = mysql_fetch_assoc($result)) {
$stm = $DBH->prepare("SELECT ia.defattempts,ias.* FROM imas_assessment_sessions as ias JOIN imas_assessments as ia ON ias.assessmentid=ia.id AND ia.courseid=:courseid");
$stm->execute(array(':courseid'=>$cid));
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
	if (!isset($numsubmissions[$row['assessmentid']])) {
		$numsubmissions[$row['assessmentid']] = 1;
		$numattempts[$row['assessmentid']] = 0;
		$timesonfirst[$row['assessmentid']] = 0;
	} else {
		$numsubmissions[$row['assessmentid']]++;
	}
	$qparts = explode('~',$row['bestlastanswers']);
	$attcnt = 0;
	foreach ($qparts as $qans) {
		//if regened, count as max number of attempts
		if (strpos($qans,'ReGen##')!==false) {
			if ($row['defattempts']==0) {
				$attcnt += 5;
			} else {
				$attcnt += $row['defattempts'];
			}
		} else {
			//if not regen, count number of reattempts
			$attcnt += min(5,substr_count($qans,'##')+1);
		}
	}
	//this is the attempts per question value
	$attcnt /= count($qparts);
	$numattempts[$row['assessmentid']] += $attcnt;

	$timeparts = explode(',',$row['timeontask']);
	foreach ($timeparts as $timeinf) {
		$iteminf = explode('~',$timeinf);
		$timesonfirst[$row['assessmentid']] += $iteminf[0]/count($timeparts);
	}
}

$attemptratios = array();
$timesratios = array();
foreach ($numattempts as $aid=>$cnt) {
	$attemptratios[$aid] = $cnt/$numsubmissions[$aid];
	$timesratios[$aid] = log($timesonfirst[$aid]/$numsubmissions[$aid]);
}
$maxratio = max($attemptratios);
$maxtimeratio = max($timesratios);

$assessbadness = array();

foreach ($gbt[0][1] as $col=>$data) {
	if ($data[9]=='') {continue;}
	if ($data[4]!=1) {continue;}  //only use if counted in score
	$assessmetrics[$col] = array();
	$assessmetrics[$col]['name'] = $data[0];
	$assessmetrics[$col]['ptsposs'] = $data[2];
	$assessmetrics[$col]['mean'] = $gbt[$lastrow][1][$col][0]/$data[2];
	$fivepts = explode('<br/>',$data[9]);
	$fivepts = explode('%,&nbsp;',$fivepts[2]);  //these are already percents
	$assessmetrics[$col]['q1'] = $fivepts[1]/100;
	$assessmetrics[$col]['median'] = $fivepts[2]/100;
	$assessmetrics[$col]['q3'] = $fivepts[3]/100;
	if ($data[6]==0) { //was online assessment
		$latecnt = 0;
		for ($i=1;$i<$lastrow;$i++) {
			if ($gbt[$i][1][$col][6]>0) { //work done in exception or in latepass
				$latecnt++;
			}
		}
		$assessmetrics[$col]['late'] = $latecnt/$n;
	} else {
		$assessmetrics[$col]['late'] = 0;
	}
	$numsub = 0;
	for ($i=1;$i<$lastrow;$i++) {
		if (isset($gbt[$i][1][$col][0])) {
			$numsub++;
		}
	}
	$assessmetrics[$col]['subperc'] = $numsub/$n;
	if ($data[6]==2) { //discussion item.  Replace enddate with postby /replyby date if exists
		if (isset($discussdates[$data[7]])) {
			$data[11] = $discussdates[$data[7]];
		}
	}
	$assessmetrics[$col]['time'] = ($data[11]>$maxdate)?1:($data[11]-$mindate)/($maxdate-$mindate);
	if ($data[6]==0) { //was online assessment
		$assessmetrics[$col]['attemptratio'] = $attemptratios[$data[7]]/$maxratio;
		$assessmetrics[$col]['timesratios'] = $timesratios[$data[7]]/$maxtimeratio;
	} else {
		$assessmetrics[$col]['attemptratio'] = 1/$maxratio;
		$assessmetrics[$col]['timesratios'] = 0;
	}
	$weights = array('perf'=>0.25, 'late'=>0.15, 'sub'=>0.25, 'timein'=>0.05, 'attempt'=>0.20, 'times'=>0.1);
	$assessbadness[$col] = $weights['perf']*(0.25*(1-$assessmetrics[$col]['mean']) + 0.25*(1-$assessmetrics[$col]['median']));
	$assessbadness[$col] += $weights['perf']*(0.25*(1-$assessmetrics[$col]['q1']) + 0.25*(1-$assessmetrics[$col]['q3']));
	$assessbadness[$col] += $weights['late']*$assessmetrics[$col]['late'] + $weights['sub']*(1-$assessmetrics[$col]['subperc']);
	$assessbadness[$col] += $weights['timein']*$assessmetrics[$col]['time']+$weights['attempt']*$assessmetrics[$col]['attemptratio']+$weights['times']*$assessmetrics[$col]['timesratios'];
	if ($assessmetrics[$col]['timesratios']==0) {
		$assessbadness[$col] /= (1-$weights['times']);
	}
}

arsort($assessbadness);

$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablesorter.js\"></script>\n";
require("../header.php");
$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; "._('Assessment Metric');
echo '<div class=breadcrumb>'.$curBreadcrumb.'</div>';
echo '<div id="headeraddlinkedtext" class="pagetitle"><h1>'._('Assessment Metric').'</h1></div>';

echo '<table id="myTable" class="gb"><thead><tr><th>Item Name</th><th>Magic Metric</th>';
echo '<th>Mean</th><th>Q1</th><th>Median</th><th>Q3</th><th>Percent Late</th><th>Percent Submitted</th>';
echo '<th>Time in Term</th><th>Attempts</th><th>Times</th></tr></thead><tbody>';
$c = 0;
foreach ($assessbadness as $col=>$badness) {
	echo '<tr class="'.($c%2==0?'even':'odd').'">';  $c++;
	echo '<td>'.$assessmetrics[$col]['name'].'</td>';
	echo '<td>'.round(100*$badness,1).'</td>';
	echo '<td>'.round(100*$assessmetrics[$col]['mean'],1).'%</td>';
	echo '<td>'.round(100*$assessmetrics[$col]['q1'],1).'%</td>';
	echo '<td>'.round(100*$assessmetrics[$col]['median'],1).'%</td>';
	echo '<td>'.round(100*$assessmetrics[$col]['q3'],1).'%</td>';
	echo '<td>'.round(100*$assessmetrics[$col]['late'],1).'%</td>';
	echo '<td>'.round(100*$assessmetrics[$col]['subperc'],1).'%</td>';
	echo '<td>'.round(100*$assessmetrics[$col]['time'],1).'%</td>';
	echo '<td>'.round(100*$assessmetrics[$col]['attemptratio'],1).'%</td>';
	echo '<td>'.round(100*$assessmetrics[$col]['timesratios'],1).'%</td>';
	echo '</tr>';
}
echo '</tbody></table><br/>';
echo "<script>initSortTable('myTable',Array('S','N','N','N','N','N','N','N','N','N','N'),true);</script>\n";
?>
<p>The "Magic Metric" is a lower-is-likely-better metric that:</p>
<ul>
<li>Decreases as performance metrics (mean, quartiles, median) increase. </li>
<li>Increases with as the percentage of students who submit an assignment late increases (Percent Late).</li>
<li>Decreases as the percentage of students who submit the assignment at all increases (Percent Submitted).</li>
<li>Increases as the assignment position in the course increases (Time in Term).</li>
<li>Increases as the number of attempts per question (normalized to percent of max) increases (Attempts).</li>
<li>Increases as the time on the first attempt of each question (log, normalized to a percent of max)  increases (Times).</li>
</ul>
<p>This metric is intended to help you see, at a quick glance, which assessments might be the most problematic in your
course.</p>
<?php
require("../footer.php");
?>

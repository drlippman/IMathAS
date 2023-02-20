<?php
//IMathAS: Data dump of assessment results across course copies
//(c) 2019 David Lippman for Lumen Learning


ini_set("max_execution_time", "600");


require("../init.php");

if ($myrights<100) {
	echo 'You do not have the authority for this action';
	exit;
}

$curBreadcrumb = $breadcrumbbase;
$curBreadcrumb .= ' <a href="../admin/admin2.php">Admin</a>';
if ($myrights == 100) {
	$curBreadcrumb .= ' &gt; <a href="utils.php">Utilities</a>';
}
$curBreadcrumb .= ' &gt; Cross-Course Results';

function reporterror($err) {
	extract($GLOBALS, EXTR_SKIP | EXTR_REFS);
	require("../header.php");
	echo '<div class=breadcrumb>'.$curBreadcrumb.'</div>';
	echo '<h1>Cross-Course Assessment Results</h1>';
	echo '<p class=noticetext>'.$err.'</p>';
	require("../footer.php");
	exit;
}

if (empty($_REQUEST['basecourse'])) {
	require("../header.php");
	echo '<div class=breadcrumb>'.$curBreadcrumb.'</div>';
	echo '<h1>Cross-Course Assessment Results</h1>';
	echo '<p>This utility allows you to output assessment averages from all copies of the specified course ID.</p>';
	echo '<p>All scores are averages reported as percents.  The average only includes students who took the assessment.</p>';
	echo '<form method=post>';
	echo '<p>Base Course ID: <input name=basecourse size=10 /></p>';
	echo '<p>Include copies of this course with students active in the last <input name=days value=30 size=3 /> days</p>';
	echo '<p>Output format: <select name=output><option value=html selected>Online</option><option value=csv>CSV download</option></select></p>';
	echo '<p><button type=submit>Generate</button></p>';
	echo '</form>';
	require("../footer.php");
	exit;
}

$basecourse = Sanitize::onlyInt($_REQUEST['basecourse']);

//get course groupid
$query = 'SELECT iu.groupid FROM imas_courses AS ic JOIN imas_users AS iu ';
$query .= 'ON ic.ownerid=iu.id WHERE ic.id=?';
$stm = $DBH->prepare($query);
$stm->execute(array($basecourse));
$lookupgroup = $stm->fetchColumn(0);
if ($myrights < 100 && $lookupgroup != $groupid) {
	reporterror('You do not have access to this course ID');
	exit;
}

$ts = microtime(true);

//pull ancestor courses
$days = intval($_REQUEST['days']);
$old = time() - (empty($days)?30:$days)*24*60*60;
$anregex = '[[:<:]]'.$basecourse.'[[:>:]]';
$query = 'SELECT ic.id,ic.ancestors,ic.name FROM imas_courses AS ic JOIN imas_users AS iu ON ic.ownerid=iu.id
	  JOIN imas_students AS istu ON istu.courseid=ic.id WHERE
	  iu.groupid=? AND ic.ancestors REGEXP ?
	  GROUP BY istu.courseid
	  HAVING AVG(istu.lastaccess)>? ORDER BY ic.name';
$stm = $DBH->prepare($query);
$stm->execute(array($lookupgroup, $anregex, $old));

//echo "Ancestor lookup done: ".(microtime(true)-$ts).'<br>';

$courses = array();
$coursedepth = array();
$coursenames = array();
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
	$courses[] = $row['id'];
	$coursenames[$row['id']] = $row['name'];
	//get ancestor copy depth
	$coursedepth[$row['id']] = array_search($basecourse, explode(',', $row['ancestors']));
}

if (count($courses)==0) {
	reporterror("No copies of this course ID with activity in the specified range were found");
	exit;
}

$phcids = Sanitize::generateQueryPlaceholders($courses);

//pull teacher names and course title
$teachers = array();
$query = "SELECT it.courseid,GROUP_CONCAT(CONCAT(iu.LastName, ' ', SUBSTR(iu.FirstName,1,1)) SEPARATOR ', ') as teachers ";
$query .= "FROM imas_teachers AS it JOIN imas_users AS iu ON it.userid=iu.id ";
$query .= "WHERE it.courseid IN ($phcids) GROUP BY it.courseid";
$stm = $DBH->prepare($query);
$stm->execute($courses);
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
	$teachers[$row['courseid']] = $row['teachers'];
}

//echo "Teacher lookup done: ".(microtime(true)-$ts).'<br>';

//pull assessment names
$assessdata = array();
$stm = $DBH->prepare('SELECT id,name,ptsposs,itemorder FROM imas_assessments WHERE courseid=? ORDER BY name');
$stm->execute(array($basecourse));
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
	$row['qcnt'] = substr_count($row['itemorder'], ',');
	unset($row['itemorder']);

	if ($row['ptsposs']==-1) {
		require_once("../includes/updateptsposs.php");
		$row['ptsposs'] = updatePointsPossible($row['id']);
	}
	$assessdata[$row['id']] = $row;
}

//find assessment copies
$assesscopies = array();
$stm = $DBH->prepare("SELECT id,courseid,ancestors,itemorder,ptsposs FROM imas_assessments WHERE courseid IN ($phcids)");
$stm->execute($courses);
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
	//see if one of our assessments is at the proper depth of ancestors
	//entry should be cid:aid if copied from another course, but handle other just in case
	$ancestors = explode(',', $row['ancestors']);
	$sourceaid = explode(':', $ancestors[$coursedepth[$row['courseid']]]);
	if (count($sourceaid)>1) {
		$sourceaid = $sourceaid[1];
	} else {
		$sourceaid = $sourceaid[0];
	}
	if (empty($assessdata[$sourceaid])) {
		//invalid assessment id
		continue;
	}
	$qcnt = substr_count($row['itemorder'], ',');
	if ($qcnt != $assessdata[$sourceaid]['qcnt']) {
		//invalid question count
		continue;
	}
	if ($row['ptsposs']==-1) {
		require_once("../includes/updateptsposs.php");
		$row['ptsposs'] = updatePointsPossible($row['id']);
	}
	if ($row['ptsposs'] != $assessdata[$sourceaid]['ptsposs']) {
		//wrong points possible
		continue;
	}
	$assesscopies[$row['id']] = array('source'=>$sourceaid, 'course'=>$row['courseid']);
}
$phcopyaids = Sanitize::generateQueryPlaceholders($assesscopies);

//echo "Find assess copies lookup done: ".(microtime(true)-$ts).'<br>';


//pull assessment data
$assessresults = array();
$query = 'SELECT assessmentid,bestscores FROM imas_assessment_sessions WHERE ';
$query .= "assessmentid IN ($phcopyaids)";
$stm = $DBH->prepare($query);
$stm->execute(array_keys($assesscopies));
//echo "Assess data lookup done: ".(microtime(true)-$ts).'<br>';
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
	$sp = explode(';', $row['bestscores']);
	$scores = str_replace(array('-1','-2','~'), array('0','0',','), $sp[0]);
	$total = array_sum(explode(',', $scores));

	$baseassess = $assesscopies[$row['assessmentid']]['source'];
	$assesscourse = $assesscopies[$row['assessmentid']]['course'];
	if (!isset($assessresults[$baseassess])) {
		$assessresults[$baseassess] = array();
	}
	if (!isset($assessresults[$baseassess][$assesscourse])) {
		$assessresults[$baseassess][$assesscourse] = array();
	}
	$assessresults[$baseassess][$assesscourse][] = $total;
}
// pull new assessment data
$query = 'SELECT assessmentid,score FROM imas_assessment_records WHERE ';
$query .= "assessmentid IN ($phcopyaids)";
$stm = $DBH->prepare($query);
$stm->execute(array_keys($assesscopies));
//echo "Assess data lookup done: ".(microtime(true)-$ts).'<br>';
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
	$baseassess = $assesscopies[$row['assessmentid']]['source'];
	$assesscourse = $assesscopies[$row['assessmentid']]['course'];
	if (!isset($assessresults[$baseassess])) {
		$assessresults[$baseassess] = array();
	}
	if (!isset($assessresults[$baseassess][$assesscourse])) {
		$assessresults[$baseassess][$assesscourse] = array();
	}
	$assessresults[$baseassess][$assesscourse][] = $row['score'];
}

//echo "Assess data processing done: ".(microtime(true)-$ts).'<br>';

//form output rows
$headerrow = array('Assessment');
foreach ($courses as $courseid) {
	$headerrow[] = $coursenames[$courseid].' ID'.$courseid.' ('.$teachers[$courseid].')';
}

//form body rows
$bodydata = array();
$bodyheaderinfo = array();
foreach ($assessdata as $aid=>$ainfo) {
	if ($_REQUEST['output']=='html') {
		$bodyheaderinfo[] = array('id'=>$aid, 'name'=>$ainfo['name']);
	} else {
		$bodydata[] = $ainfo['name'];
	}
	$outrow = array();
	foreach ($courses as $courseid) {
		if (!isset($assessresults[$aid][$courseid])) {
			$outrow[] = '-';
		} else {
			$avg = array_sum($assessresults[$aid][$courseid])/count($assessresults[$aid][$courseid]);
			$outrow[] = round(100*$avg/$ainfo['ptsposs'], 1);
		}
	}
	$bodydata[] = $outrow;
}

if ($_REQUEST['output']=='html') {
	require("../header.php");
	echo '<div class=breadcrumb>'.$curBreadcrumb.'</div>';
	echo '<h1>Cross-Course Assessment Results</h1>';
	echo '<p>All scores are averages reported as percents.  The average only includes students who took the assessment.</p>';
	echo '<table class=gb><thead><tr><th>';
	echo implode('</th><th>', array_map('Sanitize::encodeStringForDisplay', $headerrow));
	echo '</th></tr></thead><tbody>';
	foreach ($bodydata as $i=>$bodyrow) {
		if ($i%2!=0) {echo "<tr class=even>"; } else {echo "<tr class=odd>";}
		$qs = Sanitize::generateQueryStringFromMap(array(
			'output'=>'html',
			'days'=>$days,
			'baseassess'=>$bodyheaderinfo[$i]['id']));
		echo '<td><a href="crosscoursedatadetail.php?'.$qs.'" target="xdetail">';
		echo Sanitize::encodeStringForDisplay($bodyheaderinfo[$i]['name']).'</a></td>';
		echo '<td>'.implode('</td><td>', array_map('Sanitize::encodeStringForDisplay', $bodyrow)).'</td></tr>';
	}
	echo '</tbody></table>';
	$qs = Sanitize::generateQueryStringFromMap(array(
		'output'=>'csv',
		'days'=>$days,
		'basecourse'=>$basecourse));
	echo '<p><a href="crosscoursedata.php?'.$qs.'">Download as CSV</a></p>';
	require("../footer.php");
} else {
	header('Content-type: text/csv');
	header("Content-Disposition: attachment; filename=\"gradebook-$cid.csv\"");
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	$out = fopen('php://output', 'w');
	fputcsv($out, $headerrow);
	foreach ($bodydata as $bodyrow) {
		fputcsv($out,$bodyrow);
	}
	fclose($out);
}


function getpts($sc) {
	if (strpos($sc,'~')===false) {
		if ($sc>0) {
			return $sc;
		} else {
			return 0;
		}
	} else {
		$sc = explode('~',$sc);
		$tot = 0;
		foreach ($sc as $s) {
			if ($s>0) {
				$tot+=$s;
			}
		}
		return round($tot,1);
	}
}

<?php
//IMathAS: Data dump of assessment details across course copies
//(c) 2019 David Lippman for Lumen Learning


ini_set("max_execution_time", "600");


require("../init.php");

if ($myrights<75) {
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
	echo '<h1>Cross-Course Question Results</h1>';
	echo '<p class=noticetext>'.$err.'</p>';
	require("../footer.php");
	exit;
}

if (empty($_REQUEST['baseassess'])) {
	require("../header.php");
	echo '<div class=breadcrumb>'.$curBreadcrumb.'</div>';
	echo '<h1>Cross-Course Assessment Question Results</h1>';
	echo '<p>This utility allows you to output question averages from all copies of the specified assessment.</p>';
	echo '<p>All scores are averages reported as percents.  The average only includes students who took the assessment, but will include students who did not attempt the question.</p>';
	echo '<form method=post>';
	echo '<p>Base Assessment ID: <input name=baseassess size=10 /></p>';
	echo '<p>Include copies of this course with students active in the last <input name=days value=30 size=3 /> days</p>';
	echo '<p>Output format: <select name=output><option value=html selected>Online</option><option value=csv>CSV download</option></select></p>';
	echo '<p><button type=submit>Generate</button></p>';
	echo '</form>';
	require("../footer.php");
	exit;
}

$baseassess = Sanitize::onlyInt($_REQUEST['baseassess']);

//get assessment course
$stm = $DBH->prepare('SELECT name,courseid,itemorder,ptsposs,defpoints FROM imas_assessments WHERE id=?');
$stm->execute(array($baseassess));
list($assessname,$basecourse,$itemorder,$ptsposs,$defpoints) = $stm->fetch(PDO::FETCH_NUM);
if ($ptsposs==-1) {
	require_once("../includes/updateptsposs.php");
	$ptsposs = updatePointsPossible($baseassess);
}
$qarr = array();
$questionnums = array();
foreach (explode(',', $itemorder) as $k=>$item) {
	$p = explode('~', $item);
	if (count($p) == 1) { //regular question
		$qarr[] = $item;
		$questionnums[] = $k+1;
	} else { //grouped
		if (strpos($p[0],'|')!==false) {
			array_shift($p);
		}
		foreach ($p as $pk=>$v) {
			$qarr[] = $v;
			$questionnums[] = ($k+1).'.'.($v+1);
		}
	}
}
$qcnt = count($qarr);

//maps question # to order #
$qmap = array_flip($qarr);


//get course groupid
$query = 'SELECT iu.groupid FROM imas_courses AS ic JOIN imas_users AS iu ';
$query .= 'ON ic.ownerid=iu.id WHERE ic.id=?';
$stm = $DBH->prepare($query);
$stm->execute(array($basecourse));
$lookupgroup = $stm->fetchColumn(0);
if ($myrights < 100 && $lookupgroup != $groupid) {
	reporterror('You do not have access to this assessment ID');
	exit;
}

$ts = microtime(true);

//pull descendent assessments
$assessdata = array();
$courses = array();
$days = intval($_REQUEST['days']);
$old = time() - (empty($days)?30:$days)*24*60*60;
$anregex1 = '[[:<:]]'.$basecourse.':'.$baseassess.'[[:>:]]';
$anregex2 = '^'.$baseassess.'[[:>:]]';
$query = 'SELECT ia.id,ia.courseid,ia.itemorder,ia.ptsposs FROM imas_assessments AS ia ';
$query .= 'JOIN imas_courses AS ic ON ic.id=ia.courseid ';
$query .= 'JOIN imas_users AS iu ON ic.ownerid=iu.id ';
$query .= 'JOIN imas_assessment_sessions AS ias ON ia.id=ias.assessmentid ';
$query .= 'WHERE iu.groupid=? AND (ia.ancestors REGEXP ? OR ia.ancestors REGEXP ?)';
$query .= 'GROUP BY ia.id HAVING MAX(ias.endtime)>?';
$stm = $DBH->prepare($query);
$stm->execute(array($lookupgroup, $anregex1, $anregex2, $old));
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
	$row['itemorder'] = explode(',', str_replace('~',',',preg_replace('/\d+\|\d+~/','',$row['itemorder'])));
	if ($qcnt != count($row['itemorder'])) {
		//invalid question count
		continue;
	}
	if ($row['ptsposs']==-1) {
		require_once("../includes/updateptsposs.php");
		$row['ptsposs'] = updatePointsPossible($row['id']);
	}
	if ($row['ptsposs'] != $ptsposs) {
		//wrong points possible
		continue;	
	}
	$assessdata[$row['id']] = $row;
	$courses[$row['id']] = $row['courseid'];
	$qmap = $qmap + array_flip($row['itemorder']);	
}
// pull descendents looking at new assessment data
$query = 'SELECT ia.id,ia.courseid,ia.itemorder,ia.ptsposs,ia.ancestors FROM imas_assessments AS ia ';
$query .= 'JOIN imas_courses AS ic ON ic.id=ia.courseid ';
$query .= 'JOIN imas_users AS iu ON ic.ownerid=iu.id ';
$query .= 'JOIN imas_assessment_records AS iar ON ia.id=iar.assessmentid ';
$query .= 'WHERE iu.groupid=? AND (ia.ancestors REGEXP ? OR ia.ancestors REGEXP ?)';
$query .= 'GROUP BY ia.id HAVING MAX(iar.lastchange)>?';
$stm = $DBH->prepare($query);
$stm->execute(array($lookupgroup, $anregex1, $anregex2, $old));
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
    if ($row['courseid'] == $basecourse) { continue; } // don't want to include any assess from the basecourse.
    $ancestors = explode(',', $row['ancestors']);
    foreach ($ancestors as $ancestor) {
        $pts = explode(':', $ancestor);
        if (count($pts)==2 && $pts[0] == $basecourse) {
            // found first copy from basecourse
            if ($pts[1] != $baseassess) {
                // most direct ancestor from basecourse is not baseassess,
                // so basecourse must have had copies of the assess
                continue 2;
            } 
            break; // found a copy, don't need to continue loop
        } 
    }
	$row['itemorder'] = explode(',', str_replace('~',',',preg_replace('/\d+\|\d+~/','',$row['itemorder'])));
	if ($qcnt != count($row['itemorder'])) {
		//invalid question count
		continue;
	}
	if ($row['ptsposs']==-1) {
		require_once("../includes/updateptsposs.php");
		$row['ptsposs'] = updatePointsPossible($row['id']);
	}
	if ($row['ptsposs'] != $ptsposs) {
		//wrong points possible
		continue;	
	}
	$assessdata[$row['id']] = $row;
	$courses[$row['id']] = $row['courseid'];
	$qmap = $qmap + array_flip($row['itemorder']);	
}

//pull qsetids to verify assessments match
$allaids = array_keys($assessdata);
$allaids[] = $baseassess;
$locationqsetid = array();
$possible = array();
$phaids = Sanitize::generateQueryPlaceholders($allaids);
$stm = $DBH->prepare("SELECT id,assessmentid,questionsetid,points FROM imas_questions WHERE assessmentid IN ($phaids) ORDER BY assessmentid");
$stm->execute($allaids);
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
	$loc = $qmap[$row['id']];
	if (!isset($assessdata[$row['assessmentid']]) && $row['assessmentid'] != $baseassess) {
		continue;
	}
	$keepassess = true;
	if ($row['assessmentid'] == $baseassess) {
		$locationqsetid[$loc] = $row['questionsetid'];
		if ($row['points'] == 9999) {
			$possible[$loc] = $defpoints;
		} else {
			$possible[$loc] = $row['points'];
		}
	} else if ($row['questionsetid'] != $locationqsetid[$loc]) {
		//wrong questionsetid - the teacher changed the question
		//get this assessment out of the list
		$keepassess = false;
		echo "hit qsetid";
	} else if ($row['points'] == 9999 && $possible[$loc] != $defpoints) {
		//changed point values
		echo "hit pts 1";
		$keepassess = false;
	} else if ($row['points'] != 9999 && $possible[$loc] != $row['points']) {
		//changed point values
		echo "hit pts 2";
		$keepassess = false;
	}
	if (!$keepassess) {
		unset($assessdata[$row['assessmentid']]);
		unset($courses[$row['assessmentid']]);
	}
}

if (count($courses)==0) {
	reporterror("No equivalent copies were found");
	exit;
}

//pull question titles
$qsids = Sanitize::generateQueryPlaceholders($locationqsetid);
$stm = $DBH->prepare("SELECT id,description FROM imas_questionset WHERE id IN ($qsids)");
$stm->execute(array_values($locationqsetid));
$questiontitles = array();
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
	$questiontitles[$row['id']] = $row['description'];
}

//pull courses names
$phcids = Sanitize::generateQueryPlaceholders($courses);
$stm = $DBH->prepare("SELECT id,name FROM imas_courses WHERE id IN ($phcids)");
$stm->execute(array_values($courses));
$coursenames = array();
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
	$coursenames[$row['id']] = $row['name'];
}


//pull teacher names and course title
$teachers = array();
$query = "SELECT it.courseid,GROUP_CONCAT(CONCAT(iu.LastName, ' ', SUBSTR(iu.FirstName,1,1)) SEPARATOR ', ') as teachers ";
$query .= "FROM imas_teachers AS it JOIN imas_users AS iu ON it.userid=iu.id ";
$query .= "WHERE it.courseid IN ($phcids) GROUP BY it.courseid";
$stm = $DBH->prepare($query);
$stm->execute(array_values($courses));
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
	$teachers[$row['courseid']] = $row['teachers'];	
}

$phcopyaids = Sanitize::generateQueryPlaceholders($assessdata);

//pull assessment data
$assessresults = array_fill(0, $qcnt, array());
$query = 'SELECT assessmentid,questions,bestscores FROM imas_assessment_sessions WHERE ';
$query .= "assessmentid IN ($phcopyaids)";
$stm = $DBH->prepare($query);
$stm->execute(array_keys($assessdata));
//echo "Assess data lookup done: ".(microtime(true)-$ts).'<br>';
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
	if (strpos($line['questions'],';')===false) {
		$questions = explode(",",$row['questions']);
	} else {
		list($questions,$bestquestions) = explode(";",$row['questions']);
		$questions = explode(",",$bestquestions);
	}
	
	$sp = explode(';', $row['bestscores']);
	$scores = explode(',', $sp[0]);
	
	$thisaid = $row['assessmentid'];
	foreach ($questions as $k=>$qid) {
		$qn = $qmap[$qid];
		if (!isset($assessresults[$qn][$thisaid])) {
			$assessresults[$qn][$thisaid] = array();
		}
		$assessresults[$qn][$thisaid][] = getpts($scores[$k]);
	}
}

//pull new assessment data
$query = 'SELECT assessmentid,scoreddata FROM imas_assessment_records WHERE ';
$query .= "assessmentid IN ($phcopyaids)";
$stm = $DBH->prepare($query);
$stm->execute(array_keys($assessdata));
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
    $data = json_decode(gzdecode($row['scoreddata']), true);
    if (empty($data)) { continue; }
    $thisaid = $row['assessmentid'];

    $avertouse = isset($data['scored_version']) ? $data['scored_version'] : 0;
    $aver = $data['assess_versions'][$avertouse];
    foreach ($aver['questions'] as $qdata) {
        $qvertouse = isset($qdata['scored_version']) ? $qdata['scored_version'] : 0;
        $qid = $qdata['question_versions'][$qvertouse]['qid'];
        $qn = $qmap[$qid];
		if (!isset($assessresults[$qn][$thisaid])) {
			$assessresults[$qn][$thisaid] = array();
		}
		$assessresults[$qn][$thisaid][] = $qdata['score'];
    }
}

//echo "Assess data processing done: ".(microtime(true)-$ts).'<br>';

//form output rows
$headerrow = array('Question');
foreach ($courses as $courseid) {
	$headerrow[] = $coursenames[$courseid].' ('.$teachers[$courseid].')';
}

//form body rows
$bodydata = array();

foreach ($qarr as $loc=>$qid) {
	$outrow = array($questionnums[$loc].': '.$questiontitles[$locationqsetid[$loc]]);
	foreach (array_keys($assessdata) as $aid) {
		if (!isset($assessresults[$loc][$aid])) {
			$outrow[] = '-';
		} else {
			$avg = array_sum($assessresults[$loc][$aid])/count($assessresults[$loc][$aid]);
			$outrow[] = round(100*$avg/$possible[$loc], 1);
		}
	}
	$bodydata[] = $outrow;
}

if ($_REQUEST['output']=='html') {
	require("../header.php");
	echo '<div class=breadcrumb>'.$curBreadcrumb.'</div>';
	echo '<h1>Cross-Course Assessment Results</h1>';
	echo '<h2>'.Sanitize::encodeStringForDisplay($assessname).'</h2>';
	echo '<p>All scores are averages reported as percents.  The average only includes students who took the assessment.</p>';
	echo '<table class=gb><thead><tr><th>';
	echo implode('</th><th>', array_map('Sanitize::encodeStringForDisplay', $headerrow));
	echo '</th></tr></thead><tbody>';
	foreach ($bodydata as $i=>$bodyrow) {
		if ($i%2!=0) {echo "<tr class=even>"; } else {echo "<tr class=odd>";}
		echo '<td>'.implode('</td><td>', array_map('Sanitize::encodeStringForDisplay', $bodyrow)).'</td></tr>';
	}
	echo '</tbody></table>';
	$qs = Sanitize::generateQueryStringFromMap(array(
		'output'=>'csv',
		'days'=>$days,
		'baseassess'=>$baseassess));
	echo '<p><a href="crosscoursedatadetail.php?'.$qs.'">Download as CSV</a></p>';
	require("../footer.php");
} else {
	header('Content-type: text/csv');
	header("Content-Disposition: attachment; filename=\"gradebook-$cid.csv\"");
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	$out = fopen('php://output', 'w');
	fputcsv($out, array($assessname));
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
	  

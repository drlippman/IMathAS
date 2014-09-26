<?php
//IMathAS:  Item Analysis addon - get student list for different features
//(c) 2013 David Lippman for Lumen Learning

require("../validate.php");
$flexwidth = true;
$nologo = true;
require("../header.php");

$isteacher = isset($teacherid);
$cid = $_GET['cid'];
$aid = $_GET['aid'];
$qid = $_GET['qid'];
$type = $_GET['type'];
if (!$isteacher) {
	echo "This page not available to students";
	exit;
}
$catfilter = -1;
if (isset($tutorsection) && $tutorsection!='') {
	$secfilter = $tutorsection;
} else {
	if (isset($_GET['secfilter'])) {
		$secfilter = $_GET['secfilter'];
		$sessiondata[$cid.'secfilter'] = $secfilter;
		writesessiondata();
	} else if (isset($sessiondata[$cid.'secfilter'])) {
		$secfilter = $sessiondata[$cid.'secfilter'];
	} else {
		$secfilter = -1;
	}
}

function getstunames($a) {
	if (count($a)==0) { return array();}
	$a = implode(',',$a);
	$query = "SELECT LastName,FirstName,id FROM imas_users WHERE id IN ($a)";
	$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
	$names = array();
	while ($row = mysql_fetch_row($result)) {
		$names[$row[2]] = $row[0].', '.$row[1];
	}
	return $names;
}

$stus = array();
if ($type=='notstart') {
	$query = "SELECT ims.userid FROM imas_students AS ims LEFT JOIN imas_assessment_sessions AS ias ON ims.userid=ias.userid AND ias.assessmentid='$aid'";
	$query .= "WHERE ias.id IS NULL AND ims.courseid='$cid' AND ims.locked=0 ";
	if ($secfilter!=-1) {
		$query .= " AND ims.section='$secfilter' ";
	}
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$stus[] = $row[0];
	}
	$stunames = getstunames($stus);
	natsort($stunames);
	echo '<h3>Students who have not started this assessment</h3><ul>';
	foreach ($stunames as $name) {
		echo '<li>'.$name.'</li>';
	}
	echo '</ul>';
} else if ($type=='help') {
	$query = "SELECT DISTINCT ict.userid FROM imas_content_track AS ict JOIN imas_students AS ims ON ict.userid=ims.userid WHERE ims.courseid='$cid' AND ict.courseid='$cid' AND ict.type='extref' AND ict.typeid='$qid' AND ims.locked=0 ";
	if ($secfilter!=-1) {
		$query .= " AND ims.section='$secfilter' ";
	}
	$result = mysql_query($query) or die("Query failed : $query;  " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$stus[] = $row[0];
	}	
	$stunames = getstunames($stus);
	natsort($stunames);
	echo '<h3>Students who clicked on help for this question</h3><ul>';
	foreach ($stunames as $name) {
		echo '<li>'.$name.'</li>';
	}
	echo '</ul>';
} else {
	$query = "SELECT ias.questions,ias.bestscores,ias.bestattempts,ias.bestlastanswers,ias.starttime,ias.endtime,ias.timeontask,ias.userid FROM imas_assessment_sessions AS ias,imas_students ";
	$query .= "WHERE ias.userid=imas_students.userid AND imas_students.courseid='$cid' AND ias.assessmentid='$aid' AND imas_students.locked=0";
	if ($secfilter!=-1) {
		$query .= " AND imas_students.section='$secfilter' ";
	}
	$result = mysql_query($query) or die("Query failed : $query;  " . mysql_error());
	$stuincomp = array();
	$stuscores = array();
	$stutimes = array();
	$sturegens = array();
	$stuatt = array();
	while ($line=mysql_fetch_array($result, MYSQL_ASSOC)) {
		if (strpos($line['questions'],';')===false) {
			$questions = explode(",",$line['questions']);
		} else {
			list($questions,$bestquestions) = explode(";",$line['questions']);
			$questions = explode(",",$bestquestions);
		}
		$sp = explode(';', $line['bestscores']);
		$scores = explode(',', $sp[0]);
		$attp = explode(',',$line['bestattempts']);
		$bla = explode('~',$line['bestlastanswers']);
		$timeot = explode(',',$line['timeontask']);
		$k = array_search($qid, $questions);
		if ($k===false) {continue;}
		if (strpos($scores[$k],'-1')!==false) {
			$stuincomp[$line['userid']] = 1;
		} else {
			$stuscores[$line['userid']] = getpts($scores[$k]);
			$stuatt[$line['userid']] = $attp[$k];
			$sturegens[$line['userid']] = substr_count($bla[$k],'ReGen');
		
			$timeot[$k] = explode('~',$timeot[$k]);
		
			$stutimes[$line['userid']] = array_sum($timeot[$k]);
		}
	}
	if ($type=='incomp') {
		$stunames = getstunames(array_keys($stuincomp));
		natsort($stunames);
		echo '<h3>Students who have started the assignment, but have not completed this question</h3><ul>';
		foreach ($stunames as $name) {
			echo '<li>'.$name.'</li>';
		}
		echo '</ul>';	
	} else if ($type=='score') {
		$stunames = getstunames(array_keys($stuscores));
		asort($stuscores);
		echo '<h3>Students with lowest scores</h3><table class="gb"><thead><tr><th>Name</th><th>Score</th></tr></thead><tbody>';
		foreach ($stuscores as $uid=>$sc) {
			echo '<tr><td>'.$stunames[$uid].'</td><td>'.$sc.'</td></tr>';
		}
		echo '</tbody></table>';
	} else if ($type=='att') {
		$stunames = getstunames(array_keys($stuatt));
		arsort($stuatt);
		arsort($sturegens);
		echo '<h3>Students with most attempts on scored version and Most Regens</h3><table class="gb"><thead><tr><th>Name</th><th>Attempts</th><th style="border-right:1px solid">&nbsp;</th><th>Name</th><th>Regens</th></tr></thead><tbody>';
		
		$rows = array();
		foreach ($stuatt as $uid=>$sc) {
			$rows[] = '<tr><td>'.$stunames[$uid].'</td><td>'.$sc.'</td><td style="border-right:1px solid">&nbsp;</td>';
		}
		$rrc = 0;
		foreach ($sturegens as $uid=>$sc) {
			$rows[$rrc] .= '<td>'.$stunames[$uid].'</td><td>'.$sc.'</td></tr>';
			$rrc++;
		}
		foreach ($rows as $r) {
			echo $r;
		}
		
		echo '</tbody></table>';
		
	} else if ($type=='time') {
		$stunames = getstunames(array_keys($stutimes));
		arsort($stutimes);
		echo '<h3>Students with most time spent on this question</h3><table class="gb"><thead><tr><th>Name</th><th>Time</th></tr></thead><tbody>';
		foreach ($stutimes as $uid=>$sc) {
			echo '<tr><td>'.$stunames[$uid].'</td><td>';
			if ($sc<60) {
				$sc .= ' sec';
			} else {
				$sc = round($sc/60,2) . ' min';
			}
			echo $sc;
			echo '</td></tr>';
		}
		echo '</tbody></table>';
	}
}
require("../footer.php");

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
?>

<?php
//IMathAS:  Item Analysis addon - get student list for different features
//(c) 2013 David Lippman for Lumen Learning

require("../init.php");
$flexwidth = true;
$nologo = true;
require("../header.php");


$isteacher = isset($teacherid);
$istutor = isset($tutorid);
$cid = Sanitize::courseId($_GET['cid']);
$aid = Sanitize::onlyInt($_GET['aid']);
$qid = $_GET['qid'];
$type = $_GET['type'];
if (!$isteacher && !$istutor) {
	echo "This page not available to students";
	exit;
}
if ($istutor) {
	$stm = $DBH->prepare("SELECT tutoredit FROM imas_assessments WHERE id=?");
	$stm->execute(array($aid));
	if ($stm->fetchColumn(0)==2) {
		echo 'You do not have access to view this assessment';
		exit;
	}
}
$catfilter = -1;
if (isset($tutorsection) && $tutorsection!='') {
	$secfilter = $tutorsection;
} else {
	if (isset($_GET['secfilter'])) {
		$secfilter = $_GET['secfilter'];
		$_SESSION[$cid.'secfilter'] = $secfilter;
	} else if (isset($_SESSION[$cid.'secfilter'])) {
		$secfilter = $_SESSION[$cid.'secfilter'];
	} else {
		$secfilter = -1;
	}
}

function getstunames($a) {
	global $DBH;
	if (count($a)==0) { return array();}
	$a = array_map('Sanitize::onlyInt', $a);
	$query_placeholders = Sanitize::generateQueryPlaceholders($a);
	$stm = $DBH->prepare("SELECT LastName,FirstName,id FROM imas_users WHERE id IN ($query_placeholders)");
	$stm->execute($a);
	$names = array();
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$names[$row[2]] = $row[0].', '.$row[1];
	}
	return $names;
}

$stus = array();
if ($type=='notstart') {
	$query = "SELECT ims.userid FROM imas_students AS ims LEFT JOIN imas_assessment_sessions AS ias ON ims.userid=ias.userid AND ias.assessmentid=:assessmentid ";
	$query .= "WHERE ias.id IS NULL AND ims.courseid=:courseid AND ims.locked=0 ";
	if ($secfilter!=-1) {
		$query .= " AND ims.section=:section ";
	}
	$stm = $DBH->prepare($query);
	if ($secfilter!=-1) {
		$stm->execute(array(':assessmentid'=>$aid, ':courseid'=>$cid, ':section'=>$secfilter));
	} else {
		$stm->execute(array(':assessmentid'=>$aid, ':courseid'=>$cid));
	}
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$stus[] = $row[0];
	}
	$stunames = getstunames($stus);
	natsort($stunames);
	echo '<h2>Students who have not started this assessment</h2><ul>';
	foreach ($stunames as $name) {
		echo sprintf('<li><span class="pii-full-name">%s</span></li>', Sanitize::encodeStringForDisplay($name));
	}
	echo '</ul>';
} else if ($type=='help') {
	$query = "SELECT DISTINCT ict.userid FROM imas_content_track AS ict JOIN imas_students AS ims ON ict.userid=ims.userid WHERE ims.courseid=:courseid AND ict.courseid=:courseid2 AND ict.type='extref' AND ict.typeid=:typeid AND ims.locked=0 ";
	if ($secfilter!=-1) {
		$query .= " AND ims.section=:section ";
	}
	$stm = $DBH->prepare($query);
	if ($secfilter!=-1) {
		$stm->execute(array(':courseid'=>$cid, ':courseid2'=>$cid, ':typeid'=>$qid, ':section'=>$secfilter));
	} else {
		$stm->execute(array(':courseid'=>$cid, ':courseid2'=>$cid, ':typeid'=>$qid));
	}
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$stus[] = $row[0];
	}
	$stunames = getstunames($stus);
	natsort($stunames);
	echo '<h2>Students who clicked on help for this question</h2><ul>';
	foreach ($stunames as $name) {
		echo sprintf('<li><span class="pii-full-name">%s</span></li>', Sanitize::encodeStringForDisplay($name));
	}
	echo '</ul>';
} else {
	$query = "SELECT ias.questions,ias.bestscores,ias.bestattempts,ias.bestlastanswers,ias.starttime,ias.endtime,ias.timeontask,ias.userid FROM imas_assessment_sessions AS ias,imas_students ";
	$query .= "WHERE ias.userid=imas_students.userid AND imas_students.courseid=:courseid AND ias.assessmentid=:assessmentid AND imas_students.locked=0";
	if ($secfilter!=-1) {
		$query .= " AND imas_students.section=:section ";
	}
	$stm = $DBH->prepare($query);
	if ($secfilter!=-1) {
		$stm->execute(array(':courseid'=>$cid, ':assessmentid'=>$aid, ':section'=>$secfilter));
	} else {
		$stm->execute(array(':courseid'=>$cid, ':assessmentid'=>$aid));
	}
	$stuincomp = array();
	$stuscores = array();
	$stutimes = array();
	$sturegens = array();
	$stuatt = array();
	while ($line=$stm->fetch(PDO::FETCH_ASSOC)) {
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
		echo '<h2>Students who have started the assignment, but have not completed this question</h2><ul>';
		foreach ($stunames as $name) {
			echo sprintf('<li><span class="pii-full-name">%s</span></li>', Sanitize::encodeStringForDisplay($name));
		}
		echo '</ul>';
	} else if ($type=='score') {
		$stunames = getstunames(array_keys($stuscores));
		asort($stuscores);
		echo '<h2>Students with lowest scores</h2><table class="gb"><thead><tr><th>Name</th><th>Score</th></tr></thead><tbody>';
		foreach ($stuscores as $uid=>$sc) {
			echo sprintf('<tr><td><span class="pii-full-name">%s</span></td><td>%s</td></tr>',
				Sanitize::encodeStringForDisplay($stunames[$uid]),
				Sanitize::encodeStringForDisplay($sc));
		}
		echo '</tbody></table>';
	} else if ($type=='att') {
		$stunames = getstunames(array_keys($stuatt));
		arsort($stuatt);
		arsort($sturegens);
		echo '<h2>Students with most attempts on scored version and Most Regens</h2><table class="gb"><thead><tr><th>Name</th><th>Attempts</th><th style="border-right:1px solid">&nbsp;</th><th>Name</th><th>Regens</th></tr></thead><tbody>';

		$rows = array();
		foreach ($stuatt as $uid=>$sc) {
			$rows[] = sprintf('<tr><td><span class="pii-full-name">%s</span></td><td>%s</td><td style="border-right:1px solid">&nbsp;</td>',
				Sanitize::encodeStringForDisplay($stunames[$uid]), Sanitize::encodeStringForDisplay($sc));
		}
		$rrc = 0;
		foreach ($sturegens as $uid=>$sc) {
			$rows[$rrc] .= sprintf('<td><span class="pii-full-name">%s</span></td><td>%s</td></tr>',
				Sanitize::encodeStringForDisplay($stunames[$uid]),
				Sanitize::encodeStringForDisplay($sc));
			$rrc++;
		}
		foreach ($rows as $r) {
			echo $r;
		}

		echo '</tbody></table>';

	} else if ($type=='time') {
		$stunames = getstunames(array_keys($stutimes));
		arsort($stutimes);
		echo '<h2>Students with most time spent on this question</h2><table class="gb"><thead><tr><th>Name</th><th>Time</th></tr></thead><tbody>';
		foreach ($stutimes as $uid=>$sc) {
			echo sprintf('<tr><td><span class="pii-full-name">%s</span></td><td>',
				Sanitize::encodeStringForDisplay($stunames[$uid])
			);
			if ($sc<60) {
				$sc .= ' sec';
			} else {
				$sc = round($sc/60,2) . ' min';
			}
			echo Sanitize::encodeStringForDisplay($sc);
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

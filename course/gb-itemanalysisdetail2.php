<?php
//IMathAS:  Item Analysis addon - get student list for different features; assess2 ver
//(c) 2019 David Lippman for Lumen Learning

require("../init.php");
$flexwidth = true;
$nologo = true;
require("../header.php");


$isteacher = isset($teacherid);
$istutor = isset($tutorid);
$cid = Sanitize::courseId($_GET['cid']);
$aid = Sanitize::onlyInt($_GET['aid']);
$qid = $_GET['qid'] ?? '';
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
	$query = "SELECT ims.userid FROM imas_students AS ims LEFT JOIN ";
  $query .= "imas_assessment_records AS iar ON ims.userid=iar.userid AND iar.assessmentid=:assessmentid ";
	$query .= "WHERE iar.userid IS NULL AND ims.courseid=:courseid AND ims.locked=0 ";
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
		echo sprintf('<li>%s</li>', Sanitize::encodeStringForDisplay($name));
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
		echo sprintf('<li>%s</li>', Sanitize::encodeStringForDisplay($name));
	}
	echo '</ul>';
} else {
	$query = "SELECT iar.userid,iar.scoreddata FROM imas_assessment_records AS iar,imas_students ";
	$query .= "WHERE iar.userid=imas_students.userid AND imas_students.courseid=:courseid ";
	$query .= "AND iar.assessmentid=:assessmentid AND imas_students.locked=0";
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
		$scoredData = json_decode(gzdecode($line['scoreddata']), true);
		$scoredAssessmentIndex = $scoredData['scored_version'];
		$scoredAssessment = $scoredData['assess_versions'][$scoredAssessmentIndex];

		// we need to find one particular question ID. There isn't an easy way to do
		// this in the new data structure, so we'll just loop over the questions until
		// we find the one we want
		foreach ($scoredAssessment['questions'] as $questionIndex => $questionData) {
		    // Get the scored question version.
		    $scoredQuestionIndex = $questionData['scored_version'];
		    $scoredQuestion = $questionData['question_versions'][$scoredQuestionIndex];

		    // Wee if this is the question we want; if not continue
		    if ($scoredQuestion['qid'] != $qid) {
		      continue;
		    }

		    // Now grab the data we want
		    if (empty($scoredQuestion['scored_try']) ||
		      in_array(-1, $scoredQuestion['scored_try'])
		    ) {
		      // incomplete
		      $stuincomp[$line['userid']] = 1;
		    } else {
		      $stuscores[$line['userid']] = $questionData['rawscore'];
		      $scoredTries = array_map(function($n) { return ++$n; }, $scoredQuestion['scored_try']);
		      $stuatt[$line['userid']] = max($scoredTries);
		      $sturegens[$line['userid']] = count($questionData['question_versions']) - 1;
		      $stutimes[$line['userid']] = $questionData['time'];
		    }
		}
	}
	if ($type=='incomp') {
		$stunames = getstunames(array_keys($stuincomp));
		natsort($stunames);
		echo '<h2>Students who have started the assignment, but have not completed this question</h2><ul>';
		foreach ($stunames as $name) {
			echo sprintf('<li>%s</li>', Sanitize::encodeStringForDisplay($name));
		}
		echo '</ul>';
	} else if ($type=='score') {
		$stunames = getstunames(array_keys($stuscores));
		asort($stuscores);
		echo '<h2>Students with lowest scores</h2><table class="gb"><thead><tr><th>Name</th><th>Score</th></tr></thead><tbody>';
		foreach ($stuscores as $uid=>$sc) {
			echo sprintf('<tr><td>%s</td><td>%s%%</td></tr>', Sanitize::encodeStringForDisplay($stunames[$uid]),
				Sanitize::encodeStringForDisplay(round(100*$sc)));
		}
		echo '</tbody></table>';
		echo '<p>Note: Scores are before any penalties are applied.</p>';
	} else if ($type=='att' || $type=='attr') {
		$stunames = getstunames(array_keys($stuatt));
		arsort($stuatt);
		arsort($sturegens);

		if ($type=='attr') {
			echo '<h2>Students with most tries on scored version and Most Regens</h2><table class="gb"><thead><tr><th>Name</th><th>Tries</th><th>&nbsp;</th><th style="border-left:1px solid">Name</th><th>Regens</th></tr></thead><tbody>';
		} else {
			echo '<h2>Students with most tries on scored attempt</h2><table class="gb"><thead><tr><th>Name</th><th>Tries</th></tr></thead><tbody>';
		}

		$rows = array();
		foreach ($stuatt as $uid=>$sc) {
			$rows[] = sprintf('<td>%s</td><td>%s</td><td>&nbsp;</td>',
				Sanitize::encodeStringForDisplay($stunames[$uid]), Sanitize::encodeStringForDisplay($sc));
		}
		if ($type=='attr') {
			$rrc = 0;
			foreach ($sturegens as $uid=>$sc) {
				$rows[$rrc] .= sprintf('<td style="border-left:1px solid">%s</td><td>%s</td>', Sanitize::encodeStringForDisplay($stunames[$uid]),
					Sanitize::encodeStringForDisplay($sc));
				$rrc++;
			}
		}
		foreach ($rows as $r) {
			echo '<tr>'.$r.'</tr>';
		}

		echo '</tbody></table>';

	} else if ($type=='time') {
		$stunames = getstunames(array_keys($stutimes));
		arsort($stutimes);
		echo '<h2>Students with most time spent on this question</h2><table class="gb"><thead><tr><th>Name</th><th>Time</th></tr></thead><tbody>';
		foreach ($stutimes as $uid=>$sc) {
			echo sprintf('<tr><td>%s</td><td>', Sanitize::encodeStringForDisplay($stunames[$uid]));
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

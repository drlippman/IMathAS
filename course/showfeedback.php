<?php
//IMathAS - display feedback
//(c) 2018 David Lippman

$flexwidth = true;
$nologo = true;
require("../init.php");
require("../header.php");

if (!isset($studentid) && !isset($tutorid) && !isset($teacherid)) {
	echo "You are not registered in this course";
	exit;
}

$id = Sanitize::onlyInt($_GET['id']);
$type = Sanitize::simpleString($_GET['type']);

if ($type=='A') {
	$query = "SELECT ia.name,ias.feedback FROM imas_assessment_sessions AS ias ";
	$query .= "JOIN imas_assessments AS ia ON ia.id=ias.assessmentid ";
	$query .= "WHERE ias.id=? AND ia.courseid=? ";
	$qarr = array($id, $cid);
	if (isset($studentid)) {
		$query .= "AND ias.userid=?";
		$qarr[] = $userid;
	}
	$stm = $DBH->prepare($query);
	$stm->execute($qarr);
	
	list($aname, $origfeedback) = $stm->fetch(PDO::FETCH_NUM);
	
	echo '<h1>'.sprintf(_('Feedback on %s'), Sanitize::encodeStringForDisplay($aname)).'</h1>';
	$feedback = json_decode($origfeedback, true);
	if ($feedback === null) {
		$feedback = array('Z'=>$origfeedback);
	}
	$fbkeys = array_keys($feedback);
	natsort($fbkeys);
	
	foreach ($fbkeys as $key) {
		if ($feedback[$key]=='' || $feedback[$key]=='<p></p>') {
			continue;
		}
		if ($key=='Z') {
			echo '<p>'._('Overall feedback:').'</p>';
		} else {
			$qn = substr($key,1);
			echo '<p>'.sprintf(_('Feedback on Question %d:'), Sanitize::onlyInt($qn+1)).'</p>';
		}
		echo '<div class="fbbox">'.Sanitize::outgoingHtml($feedback[$key]).'</div>';
	}
} else if ($type=='O' || $type=='E') {
	if ($type=='O') {
		$query = "SELECT igi.name,ig.feedback FROM imas_gbitems AS igi ";
		$query .= "JOIN imas_grades AS ig ON igi.id=ig.gradetypeid AND ig.gradetype='offline' ";
	} else if ($type=='E') {
		$query = "SELECT igi.name,ig.feedback FROM imas_linkedtext AS igi ";
		$query .= "JOIN imas_grades AS ig ON igi.id=ig.gradetypeid AND ig.gradetype='exttool' ";
	}
	$query .= "WHERE ig.id=? AND igi.courseid=? ";
	$qarr = array($id, $cid);
	if (isset($studentid)) {
		$query .= "AND ig.userid=?";
		$qarr[] = $userid;
	}
	$stm = $DBH->prepare($query);
	$stm->execute($qarr);
	
	list($aname, $feedback) = $stm->fetch(PDO::FETCH_NUM);
	echo '<h1>'.sprintf(_('Feedback on %s'), Sanitize::encodeStringForDisplay($aname)).'</h1>';
	echo '<div class="fbbox">'.Sanitize::outgoingHtml($feedback).'</div>';
}

require("../footer.php");
	

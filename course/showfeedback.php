<?php
//IMathAS - display feedback
//(c) 2018 David Lippman

$flexwidth = true;
$nologo = true;
require_once "../init.php";
require_once "../header.php";

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
} else if ($type=='A2') {
	$uid = Sanitize::simpleString($_GET['uid']);
	if (isset($studentid)) {
		$uid = $userid;
	}
	$query = "SELECT ia.name,ia.submitby,ia.deffeedbacktext,iar.scoreddata FROM imas_assessment_records AS iar ";
	$query .= "JOIN imas_assessments AS ia ON ia.id=iar.assessmentid ";
	$query .= "WHERE iar.assessmentid=? AND iar.userid=? AND ia.courseid=? ";
	$stm = $DBH->prepare($query);
	$stm->execute(array($id, $uid, $cid));

	list($aname, $submitby, $deffb, $scoreddata) = $stm->fetch(PDO::FETCH_NUM);
	$scoreddata = json_decode(Sanitize::gzexpand($scoreddata), true);
	$by_question = ($submitby == 'by_question');

	echo '<h1>'.sprintf(_('Feedback on %s'), Sanitize::encodeStringForDisplay($aname)).'</h1>';
	$hasFb = false;
	foreach ($scoreddata['assess_versions'] as $av => $aver) {
		$fbdisp = '';
		foreach ($aver['questions'] as $qn => $qdata) {
			$qfb = '';
			foreach ($qdata['question_versions'] as $qv => $qver) {
				if (!empty($qver['feedback'])) {
					if ($by_question && count($qdata['question_versions']) > 1) {
						$qfb .= '<p>'.sprintf(_('Feedback on Version %d:'), $qv+1).'</p>';
					}
					$qfb .= '<div class="fbbox">'.Sanitize::outgoingHtml($qver['feedback']).'</div>';
				}
			}
			if ($qfb != '') {
				$fbdisp .= '<p>'.sprintf(_('Feedback on Question %d:'), Sanitize::onlyInt($qn+1)).'</p>';
				$fbdisp .= $qfb;
			}
		}
		if (!empty($aver['feedback'])) {
			if ($by_question) {
				$fbdisp .= '<p>'._('Overall feedback:').'</p>';
			} else {
				$fbdisp .= '<p>'._('Overall feedback on this attempt:').'</p>';
			}
			$fbdisp .= '<div class="fbbox">'.Sanitize::outgoingHtml($aver['feedback']).'</div>';
		}
		if ($fbdisp != '') {
			if (!$by_question) {
				echo '<h2>'.sprintf(_('Feedback on attempt %d'), Sanitize::onlyInt($av)+1).'</h2>';
			}
			$hasFb = true;
			echo $fbdisp;
		}
	}
	if (!$hasFb && $deffb !== '') {
		echo '<p>'.Sanitize::encodeStringForDisplay($deffb).'</p>';
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

require_once "../footer.php";

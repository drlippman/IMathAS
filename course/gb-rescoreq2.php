<?php
//IMathAS: Re-score a question
//(c) 2018 David Lippman

require("../init.php");
require("../assess2/AssessInfo.php");
require("../assess2/AssessRecord.php");

if (!isset($teacherid) && !isset($tutorid)) {
	require("../header.php");
	echo "You need to log in as a teacher or tutor to access this page";
	require("../footer.php");
	exit;
}

if (empty($_GET['cid']) || empty($_GET['aid']) || empty($_GET['qid'])) {
	echo "Missing required info";
	exit;
}

$cid = Sanitize::onlyInt($_GET['cid']);
$aid = Sanitize::onlyInt($_GET['aid']); //imas_assessments id
$qid = Sanitize::onlyInt($_GET['qid']);

if (isset($_POST['go'])) {
	$trytouse = ($_POST['vertouse'] == 1) ? 'last' : 'first';
	$assess_info = new AssessInfo($DBH, $aid, $cid, false);
	$assess_info->loadQuestionSettings('all', true, false);
	$DBH->beginTransaction();
	$query = "SELECT iar.* FROM imas_assessment_records AS iar
							JOIN imas_students ON imas_students.userid = iar.userid
						WHERE iar.assessmentid = :assessmentid
							AND imas_students.courseid = :courseid FOR UPDATE";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':courseid'=>$cid, ':assessmentid'=>$aid));
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		$GLOBALS['assessver'] = $row['ver'];

		$assess_record = new AssessRecord($DBH, $assess_info, false);
		$assess_record->setRecord($row);
		$assess_record->setTeacherInGb(true);
		// do the rescore
        $assess_record->regradeQuestion($qid, $trytouse);
        $assess_record->updateLTIscore(true, false);
		$assess_record->saveRecord();
	}
	$DBH->commit();

	header('Location: ' . $GLOBALS['basesiteurl'] ."/course/addquestions2.php?cid=$cid&aid=$aid");

	exit;
} else {
	require("../header.php");
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=".Sanitize::courseId($cid)."\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
	echo "&gt; <a href=\"addquestions2.php?cid=$cid&aid=$aid\">"._('Add/Remove Questions')."</a> ";
	echo "&gt; "._('Regrade Question').'</div>';

	echo '<div id="headergb-rescoreq" class="pagetitle"><h1>'._('Regrade Question').'</h1></div>';

	echo '<form method="post" action="gb-rescoreq2.php?cid='.$cid.'&aid='.$aid.'&qid='.$qid.'">';
	echo '<input type=hidden name=go value=1 />';

	echo '<p>'._('This will allow you rescore this question. ');
	echo _('This is intended to be used after fixing a bug in the question code. ').'</p>';
	echo '<p>'._('This page will re-submit the student\'s answer to this question, scoring it as if it was their first try on that version. This will be repeated for all versions of the assessment or question.');
	echo '</p><p>'._('This process will wipe out all record of other tries the student made on this question, and reset their try count to 1 on this question. ').'</p>';

	echo '<p>'._('Which try from the student do you want to resubmit?').'<br/>';
	echo ' <label><input type=radio name=vertouse value=0 checked /> ';
	echo _('The first try. Recommended for single-part questions, to rescore the student\'s first try.').'</label><br/>';
	echo ' <label><input type=radio name=vertouse value=1 /> ';
	echo _('The last try. Recommended for multi-part questions, to preserve the score on working parts.').'</label></p>';

	echo '<p>'._('Are you SURE you want to proceed?').'</p>';
	echo '<p><button type="submit">'._('Rescore Question').'</button></p>';
	echo '</form>';

	echo '<p>'._('Please be patient - it may take a minute or two').'</p>';

	require("../footer.php");
}

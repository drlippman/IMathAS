<?php
//IMathAS: swap questions
//(c) 2021 David Lippman

/*** master php includes *******/
require("../init.php");
require_once("../includes/TeacherAuditLog.php");

 //set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = _("Question Search and Replace");

    //CHECK PERMISSIONS AND SET FLAGS
if (!(isset($teacherid))) {
    $overwriteBody = 1;
    $body = _("You need to log in as a teacher to access this page");
} else {	//PERMISSIONS ARE OK, PERFORM DATA MANIPULATION
    $cid = Sanitize::courseId($_GET['cid']);
    $aid = Sanitize::onlyInt($_GET['aid']);
    if (!empty($_GET['from']) && $_GET['from'] == 'addq2') {
        $addq = 'addquestions2';
        $from = 'addq2';
    } else {
        $addq = 'addquestions';
        $from = 'addq';
    }

    $curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
	$curBreadcrumb .= "&gt; <a href=\"$addq.php?aid=$aid&cid=$cid\">"._("Add/Remove Questions")."</a> &gt; ";
	$curBreadcrumb .= _("Question Search and Replace");

    $didreplace = false;

    if (!empty($_POST['replacein']) && !empty($_POST['replaceid'])) {
        $replaceid = Sanitize::onlyInt($_POST['replaceid']);
        $qsetid = Sanitize::onlyInt($_POST['qsetid']);

        $replaceins = array_map('intval', $_POST['replacein']);

        $ph = Sanitize::generateQueryPlaceholders($_POST['replacein']);
        $query = 'UPDATE imas_questions, imas_assessments SET imas_questions.questionsetid=?
            WHERE imas_questions.assessmentid = imas_assessments.id AND
            imas_questions.questionsetid=? AND
            imas_assessments.courseid=? AND
            imas_assessments.id IN ('.$ph.')';
        $stm = $DBH->prepare($query);
        $stm->execute(array_merge([$replaceid, $qsetid, $cid], $replaceins));
        $chgcnt = $stm->rowCount();

        // audit logging
        TeacherAuditLog::addTracking(
            $cid,
            "Question Settings Change",
            null,
            array(
                'action' => 'Mass Question Replacement',
                'oldqsetid' => $qsetid,
                'newqsetid' => $replaceid,
                'aids' => $replaceins
            )
        );

        $didreplace = true;
    }
    if (isset($_POST['qsetid'])) {
        $qsetid = Sanitize::onlyInt($_POST['qsetid']);
        $query = 'SELECT DISTINCT ia.name,ia.id AS aid,iq.id,COUNT(istu.userid) AS takencnt FROM imas_assessments AS ia 
            JOIN imas_questions AS iq ON ia.id=iq.assessmentid
            LEFT JOIN imas_assessment_records AS iar ON iar.assessmentid=ia.id
            LEFT JOIN imas_students AS istu ON iar.userid=istu.userid AND istu.courseid=?
            WHERE ia.courseid=? AND iq.questionsetid=? GROUP BY ia.id ORDER BY ia.name';
        $stm = $DBH->prepare($query);
        $stm->execute([$cid, $cid, $qsetid]);
        $results = $stm->fetchAll(PDO::FETCH_ASSOC);
    }
}

require("../header.php");

if ($overwriteBody==1) {
	echo $body;
} else {

	echo '<div class="breadcrumb">' . $curBreadcrumb . '</div>';
    echo '<div class="pagetitle"><h1>' . $pagetitle . '</h1></div>';
    echo '<form method="post">';
    if (empty($qsetid)) {
        echo '<p>'._('This will find all usages of a question in this course.').'</p>';
        echo '<p>'._('Search for Question ID').': <input name=qsetid size=10 /></p>';
        echo '<p><button type="submit">'._('Search').'</button>';
    } else {
        if ($didreplace) {
            echo '<p>'.sprintf(_('Replace changed %d questions'), $chgcnt).'<p>';
        } else if ($results === false){ 
            echo 'error';
        } else if (count($results) == 0) {
            echo _('Question not found');
        } else {
            echo '<p>'.sprintf(_('Question ID %d was found in:'), $qsetid).'</p>';
            echo '<ul class=nomark>';
            foreach ($results as $result) {
                echo '<li>';
                echo '<input type=checkbox name="replacein[]" value="'.$result['aid'].'" ';
                if ($result['takencnt'] > 0) {
                    echo 'disabled class="q-taken"';
                }
                echo '>';
                echo '<a href="'.$addq.'.php?cid='.$cid.'&aid='.$result['aid'].'" target="_blank">';
                echo $result['name'].'</a></li>';
            }
            echo '</ul>';
            echo '<input type=hidden name=qsetid value="'.$qsetid.'" />';
            echo '<p>'. _('In selected assessments, replace with question ID');
            echo ': <input name=replaceid size=10 /></p>';
            echo '<p><button type="submit">'._('Replace').'</button>';
            echo '<p>'._('Note: assessments that students have already started are disabled, as replacing questions in those will cause problems if the questions have different types or number of parts. ');
            echo sprintf(_('If you are absolutely sure you know what you are doing, you can %s enable %s them.'),
                '<a href="#" onclick="$(\'.q-taken\').prop(\'disabled\', false)">', '</a>');
            echo '</p>';
        }
    }
    echo '</form>';
}
require('../footer.php');

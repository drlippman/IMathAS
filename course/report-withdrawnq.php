<?php

require("../init.php");

if (!isset($teacherid)) {
  echo "Not for you";
  exit;
} 

$query = 'SELECT DISTINCT ia.name,ia.id,iq.questionsetid,iqs.description FROM
    imas_assessments AS ia
    JOIN imas_questions AS iq ON iq.assessmentid=ia.id
    JOIN imas_questionset AS iqs ON iq.questionsetid=iqs.id
    WHERE ia.courseid=? AND iq.withdrawn=1
    ORDER BY ia.name,iq.questionsetid';
$stm = $DBH->prepare($query);
$stm->execute(array($cid));

$pagetitle = _('Withdrawn Questions Report');
$curBreadcrumb = $breadcrumbbase;
$curBreadcrumb .= "<a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
$curBreadcrumb .= "&gt; <a href=\"coursereports.php?cid=$cid\">Course Reports</a> ";

require("../header.php");
echo '<div class="breadcrumb">'. $curBreadcrumb . '&gt; '.$pagetitle.'</div>';
echo '<div class="pagetitle"><h1>'.$pagetitle.'</h1></div>';

echo '<p>'._('This page lists questions in assessments that have been withdrawn.').'</p>';

if ($stm->rowCount()==0) {
    echo '<p>'._('No questions have been withdrawn').'</p>';
} else {
    echo '<table class=gb><thead><tr>';
    echo '<th>'._('Assessment').'</th>';
    echo '<th>'._('Question ID').'</th>';
    echo '<th>'._('Description').'</th>';
    echo '</tr></thead><tbody>';

    while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
        $qs = 'cid='.intval($cid).'&aid='.intval($row['id']);
        echo '<tr><td><a href="addquestions2.php?'.$qs.'">'.Sanitize::encodeStringForDisplay($row['name']).'</a></td>';
        echo '<td>'.Sanitize::encodeStringForDisplay($row['questionsetid']).'</td>';
        echo '<td>'.Sanitize::encodeStringForDisplay($row['description']).'</td>';
        echo '</tr>';
    }
}
require('../footer.php');

<?php

require("../init.php");

if (!isset($teacherid)) {
  echo "Not for you";
  exit;
} else if ($courseUIver == 1) {
    echo "This page doesn't work for old assessments";
    exit;
}

if (isset($adminasteacher)) {
  $stm = $DBH->prepare("SELECT userid FROM imas_teachers WHERE courseid=? ORDER BY id LIMIT 1");
  $stm->execute(array($cid));
  $uid = $stm->fetchColumn(0);
} else {
  $uid = $userid;
}

if (isset($_POST['edatetype'])) {
    if ($_POST['edatetype'] == '24h') {
        $old = time() - 24*60*60;
        $ttype = '24h';
    } else if ($_POST['edatetype'] == '1w') {
        $old = time() - 7*24*60*60;
        $ttype = '1w';
    } else {
        require_once("../includes/parsedatetime.php");
        $old = parsedatetime($_POST['edate'],$_POST['etime'],time() - 24*60*60);
        $ttype = 'edate';
    }
} else {
    $old = time() - 24*60*60;
    $ttype = '24h';
}
$edate = tzdate("m/d/Y",$old);
$etime = tzdate("g:i a",$old);

$query = 'SELECT iar.assessmentid,ia.name,iar.userid,iu.FirstName,iu.LastName,iar.lastchange,iar.score
    FROM imas_assessment_records AS iar
    JOIN imas_assessments AS ia ON ia.id=iar.assessmentid AND ia.courseid=? 
    JOIN imas_users AS iu ON iar.userid=iu.id
    JOIN imas_students AS istu ON istu.userid=iar.userid AND istu.courseid=?
    WHERE iar.lastchange > ?
    ORDER BY ia.name,iu.LastName';
$stm = $DBH->prepare($query);
$stm->execute(array($cid,$cid,$old));

$pagetitle = _('Recent Submissions Report');
$curBreadcrumb = $breadcrumbbase;
$curBreadcrumb .= "<a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
$curBreadcrumb .= "&gt; <a href=\"coursereports.php?cid=$cid\">Course Reports</a> ";

$placeinhead = "<script type=\"text/javascript\" src=\"$staticroot/javascript/DatePicker.js\"></script>";
require("../header.php");
echo '<div class="breadcrumb">'. $curBreadcrumb . '&gt; '.$pagetitle.'</div>';
echo '<div class="pagetitle"><h1>'.$pagetitle.'</h1></div>';

echo '<p>'._('This page lists assessment submissions that have changed recently.').'</p>';

echo '<form method="post">';
echo '<p>'._('Show changes made:').'</p>';
echo '<ul class=nomark>';
echo '<li><label><input type=radio name=edatetype value="24h" '.($ttype=='24h'?'checked':'').' />';
echo  _('In the last 24 hours').'</label></li>';
echo '<li><label><input type=radio name=edatetype value="1w" '.($ttype=='1w'?'checked':'').' />';
echo  _('In the last week').'</label></li>';
echo '<li><label><input type=radio name=edatetype value="edate" '.($ttype=='edate'?'checked':'').' />';
echo  _('Since:').'</label> <input type=text size=10 name=edate value="'.$edate.'">
    <a href="#" onClick="displayDatePicker(\'edate\', this); return false">
    <img src="'.$staticroot.'/img/cal.gif" alt="Calendar"/></a>
    at <input type=text size=10 name=etime value="'.$etime.'"></li>';
echo '</ul>';
echo '<p><button type=submit>'._('Update').'</button></p>';

if ($stm->rowCount()==0) {
    echo '<p>'._('Nothing has been submitted in the specified time interval').'</p>';
} else {
    echo '<table class=gb><thead><tr>';
    echo '<th>'._('Assessment').'</th>';
    echo '<th>'._('Student').'</th>';
    echo '<th>'._('Score').'</th>';
    echo '<th>'._('Last Changed').'</th>';
    echo '</tr></thead><tbody>';

    while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
        $qs = 'cid='.intval($cid).'&aid='.intval($row['assessmentid']).'&uid='.intval($row['userid']);
        echo '<tr><td>'.Sanitize::encodeStringForDisplay($row['name']).'</td>';
        echo '<td>'.Sanitize::encodeStringForDisplay($row['LastName'].', '.$row['FirstName']).'</td>';
        echo '<td><a href="../assess2/gbviewassess.php?'.$qs.'" target="_blank">';
        echo Sanitize::encodeStringForDisplay($row['score']).'</a></td>';
        echo '<td>'.tzdate('n/j/y g:ia', $row['lastchange']).'</td>';
        echo '</tr>';
    }
}
require('../footer.php');

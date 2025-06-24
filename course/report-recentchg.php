<?php

require_once "../init.php";

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
        require_once "../includes/parsedatetime.php";
        $old = parsedatetime($_POST['edate'],$_POST['etime'],time() - 24*60*60);
        $ttype = 'edate';
    }
} else {
    $old = time() - 24*60*60;
    $ttype = '24h';
}
$edate = tzdate("m/d/Y",$old);
$etime = tzdate("g:i a",$old);

$stm = $DBH->prepare("SELECT id,name FROM imas_gbcats WHERE courseid=:courseid");
$stm->execute(array(':courseid'=>$cid));
$gbcats = array(-1 => 'All', 0 => _('Default'));
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
    $gbcats[$row[0]] = $row[1];
}
if (isset($_POST['catfilter'])) {
    $catfilter = Sanitize::onlyInt($_POST['catfilter']);
} else {
    $catfilter = -1;
}


$query = 'SELECT iar.assessmentid,ia.name,iar.userid,iu.FirstName,iu.LastName,iar.lastchange,iar.score,iar.status,
    IF(ia.submitby="by_assessment",iar.scoreddata,"") AS scoreddata
    FROM imas_assessment_records AS iar
    JOIN imas_assessments AS ia ON ia.id=iar.assessmentid AND ia.courseid=? ';
if ($catfilter > -1) {
    $query .= 'AND ia.gbcategory=? ';
}
$query .= 'JOIN imas_users AS iu ON iar.userid=iu.id
    JOIN imas_students AS istu ON istu.userid=iar.userid AND istu.courseid=?
    WHERE iar.lastchange > ?
    ORDER BY ia.name,iu.LastName';
$stm = $DBH->prepare($query);
if ($catfilter > -1) {
    $stm->execute(array($cid,$catfilter,$cid,$old));
} else {
    $stm->execute(array($cid,$cid,$old));
}

$pagetitle = _('Recent Submissions Report');
$curBreadcrumb = $breadcrumbbase;
$curBreadcrumb .= "<a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
$curBreadcrumb .= "&gt; <a href=\"coursereports.php?cid=$cid\">Course Reports</a> ";

$placeinhead = "<script type=\"text/javascript\" src=\"$staticroot/javascript/DatePicker.js\"></script>";
$placeinhead .= '<script>
    function showfb2(aid,uid,type) {
        GB_show(_("Feedback"), "showfeedback.php?cid="+cid+"&type="+type+"&id="+aid+"&uid="+uid, 500, 500);
        return false;
    }
</script>';
require_once "../header.php";
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
echo  _('Since:').'</label> <input type=text size=10 name=edate value="'.$edate.'" aria-label="start date">
    <a href="#" onClick="displayDatePicker(\'edate\', this); return false">
    <img src="'.$staticroot.'/img/cal.gif" alt="Calendar"/></a>
    at <input type=text size=10 name=etime value="'.$etime.'" aria-label="start time"></li>';
echo '</ul>';
echo '<p><label for=catfilter>'._('In Category').'</label>: <select id=catfilter name=catfilter>';
foreach ($gbcats as $k=>$v) {
    echo '<option value="'. Sanitize::onlyInt($k).'" ';
    if ($k == $catfilter) { echo 'selected'; }
    echo '>'.Sanitize::encodeStringForDisplay($v).'</option>';
}
echo '</select></p>';
echo '<p><button type=submit>'._('Update').'</button></p>';

if ($stm->rowCount()==0) {
    echo '<p>'._('Nothing has been submitted in the specified time interval').'</p>';
} else {
    echo '<table class=gb><thead><tr>';
    echo '<th>'._('Assessment').'</th>';
    echo '<th>'._('Student').'</th>';
    echo '<th>'._('Score').'</th>';
    echo '<th>'._('Completed Attempts').'</th>';
    echo '<th>'._('Last Changed').'</th>';
    echo '<th>'._('Feedback').'</th>';
    echo '</tr></thead><tbody>';

    while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
        $qs = 'cid='.intval($cid).'&aid='.intval($row['assessmentid']).'&uid='.intval($row['userid']);
        echo '<tr><td>'.Sanitize::encodeStringForDisplay($row['name']).'</td>';
        echo '<td><span class="pii-full-name">'.Sanitize::encodeStringForDisplay($row['LastName'].', '.$row['FirstName']).'</span></td>';
        echo '<td><a href="../assess2/gbviewassess.php?'.$qs.'" target="_blank">';
        echo Sanitize::encodeStringForDisplay($row['score']);
        if (($row['status']&3) == 1) { // has unsubmitted attempt or questions
            echo ' (IP)';
        }
        echo '</a></td>';
        echo '<td class=c>';
        if ($row['scoreddata'] !== '') {
            $data = json_decode(Sanitize::gzexpand($row['scoreddata']), true);
            $totcnt = count($data['assess_versions']);
            if (($row['status']&1) == 1) { // has unsubmitted attempt
                $totcnt--;
            }
            echo $totcnt;
        }
        echo '</td>';
        echo '<td>'.tzdate('n/j/y g:ia', $row['lastchange']).'</td>';
        echo '<td>';
        if (($row['status']&8) == 8) {
            echo '<a href="#" class="small feedbacksh pointer" ';
            echo 'onclick="return showfb2('.Sanitize::onlyInt($row['assessmentid']).','.Sanitize::onlyInt($row['userid']).',\'A2\')">';
            echo _('[Show Feedback]'), '</a>';
        }
        echo '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    echo '<p>&nbsp;</p><p>'._('Note: For quiz-style assessments, an IP marker here indicates either an in-progress or unsubmitted attempt.').'</p>';
}
require_once '../footer.php';

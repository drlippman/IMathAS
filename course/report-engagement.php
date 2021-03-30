<?php

require("../init.php");

if (!isset($teacherid)) {
  echo "Not for you";
  exit;
}

if (isset($adminasteacher)) {
  $stm = $DBH->prepare("SELECT userid FROM imas_teachers WHERE courseid=? ORDER BY id LIMIT 1");
  $stm->execute(array($cid));
  $uid = $stm->fetchColumn(0);
} else {
  $uid = $userid;
}

$stm = $DBH->prepare("SELECT count(id) FROM imas_students WHERE courseid=? AND locked=0");
$stm->execute(array($cid));
$numStu = $stm->fetchColumn(0);

$stm = $DBH->prepare("SELECT count(id) FROM imas_inlinetext WHERE courseid=?");
$stm->execute(array($cid));
$numInline = $stm->fetchColumn(0);

$t0 = microtime(true);

$stm = $DBH->prepare("SELECT count(id) FROM imas_msgs WHERE msgfrom=? AND courseid=? AND parent=0");
$stm->execute(array($uid,$cid));
$msgInstrInitiated = $stm->fetchColumn(0);

$t1 = microtime(true);

$stm = $DBH->prepare("SELECT count(id) FROM imas_msgs WHERE msgto=? AND courseid=? AND parent=0");
$stm->execute(array($uid,$cid));
$msgReceivedCnt = $stm->fetchColumn(0);

$t2 = microtime(true);

$query = 'SELECT c.senddate-p.senddate AS diff ';
$query .= 'FROM imas_msgs AS p JOIN imas_msgs AS c ';
$query .= 'ON p.id=c.parent AND c.msgfrom=p.msgto AND c.courseid=? ';
$query .= 'WHERE p.courseid=? AND p.msgto=? AND p.parent=0';
$stm = $DBH->prepare($query);
$stm->execute(array($cid,$cid,$uid));
$diffs = $stm->fetchAll(PDO::FETCH_COLUMN, 0);
sort($diffs);
$msgRepliedCnt = count($diffs);
$msgMedDelay = $diffs[ceil($msgRepliedCnt/2)-1];
$msgP95Delay = $diffs[floor(.95*$msgRepliedCnt)-1];

$t3 = microtime(true);

$stm = $DBH->prepare("SELECT id FROM imas_forums WHERE courseid=?");
$stm->execute(array($cid));
$forums = $stm->fetchAll(PDO::FETCH_COLUMN, 0);
$forumsafe = implode(',',$forums);

if (count($forums) > 0) {
    $stm = $DBH->prepare("SELECT count(id) FROM imas_forum_posts WHERE userid=? AND forumid IN ($forumsafe) AND parent=0");
    $stm->execute(array($uid));
    $forumInstrInitiated = $stm->fetchColumn(0);

    $t4 = microtime(true);

    $stm = $DBH->prepare("SELECT count(id) FROM imas_forum_posts WHERE userid<>? AND forumid IN ($forumsafe) AND parent=0");
    $stm->execute(array($uid));
    $forumStuCnt = $stm->fetchColumn(0);

    $t5 = microtime(true);


    $query = 'SELECT min(c.postdate-p.postdate) AS diff ';
    $query .= 'FROM imas_forum_posts AS p JOIN imas_forum_posts AS c ';
    $query .= "ON c.userid=? AND c.parent>0 AND p.threadid=c.threadid AND c.forumid IN ($forumsafe) ";
    $query .= "WHERE p.forumid IN ($forumsafe) AND p.parent=0 GROUP BY p.id";
    $stm = $DBH->prepare($query);
    $stm->execute(array($uid));
    $diffs = $stm->fetchAll(PDO::FETCH_COLUMN, 0);
    sort($diffs);
    $forumRepliedCnt = count($diffs);
    $forumMedDelay = $diffs[ceil($forumRepliedCnt/2)-1];
    $forumP95Delay = $diffs[floor(.95*$forumRepliedCnt)-1];
}
$t6 = microtime(true);

if ($courseUIver > 1) {
  $query = 'SELECT count(iar.userid) FROM imas_assessment_records AS iar ';
  $query .= 'JOIN imas_assessments AS ia ON ia.id=iar.assessmentid AND ia.courseid=? ';
  $query .= 'WHERE (iar.status&8)=8';
  $stm = $DBH->prepare($query);
  $stm->execute(array($cid));
  $feedbackCnt = $stm->fetchColumn(0);
} else {
  $query = 'SELECT count(ias.id) FROM imas_assessment_sessions AS ias ';
  $query .= 'JOIN imas_assessments AS ia ON ia.id=ias.assessmentid AND ia.courseid=? ';
  $query .= "WHERE ias.feedback<>''";
  $stm = $DBH->prepare($query);
  $stm->execute(array($cid));
  $feedbackCnt = $stm->fetchColumn(0);
}

$pagetitle = _('Instructor Engagement Report');
$curBreadcrumb = $breadcrumbbase;
$curBreadcrumb .= "<a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
$curBreadcrumb .= "&gt; <a href=\"coursereports.php?cid=$cid\">Course Reports</a> ";


require("../header.php");
echo '<div class="breadcrumb">'. $curBreadcrumb . '&gt; '.$pagetitle.'</div>';
echo '<div class="pagetitle"><h1>'.$pagetitle.'</h1></div>';

echo '<p>'._('This page lists a number of metrics which may or may not be useful in helping your assess your level of engagement with students. ');
echo _('The relevance of these metrics will vary greatly depending on the structure of your course and how you run it.').'</p>';

echo '<p>'._('Students: ') . $numStu .'</p>';

echo '<p>'._('Inline text items: ') . $numInline .'</p>';

echo '<p>';
echo _('Instructor initiated messages: ') . $msgInstrInitiated;
echo '</p>';

echo '<p>';
echo _('Student initiated messages: ') . $msgReceivedCnt;
echo '<br/>'._('Instructor replied to: ') . $msgRepliedCnt;
echo '<br/>'._('Median response time: ') . round($msgMedDelay/3600,1) . _(' hours');
echo '<br/>'._('95th percentile response time: ') . round($msgP95Delay/3600,1) . _(' hours');
echo '</p>';
if (count($forums) > 0) {
    echo '<p>';
    echo _('Instructor initiated forum posts: ') . $forumInstrInitiated;
    echo '</p>';

    echo '<p>';
    echo _('Student forum posts: ') . $forumStuCnt;
    echo '<br/>'._('Instructor replied to: ') . $forumRepliedCnt;
    echo '<br/>'._('Median response time: ') . round($forumMedDelay/3600,1) . _(' hours');
    echo '<br/>'._('95th percentile response time: ') . round($forumP95Delay/3600,1) . _(' hours');
    echo '</p>';
}

echo '<p>';
echo _('Feedback left on assessments: ') . $feedbackCnt;
echo '</p>';

echo '<p>'._('Forum metrics only consider top-level posts, and replies anywhere in the thread.').'</p>';
echo '<p>'._('Remember not all messages or posts warrant replies.').'</p>';

//echo ($t1-$t0).', '.($t2-$t1).', '.($t3-$t2).', '.($t4-$t3).', '.($t5-$t4).', '.($t6-$t5);

require('../footer.php');

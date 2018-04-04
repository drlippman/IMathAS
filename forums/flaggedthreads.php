<?php
//IMathAS:  Flagged threads list for a course
//(c) 2017 David Lippman
require("../init.php");


$cid = Sanitize::courseId($_GET['cid']);
$from = $_GET['from'];

$now = time();
$query = "SELECT imas_forums.name,imas_forums.id,imas_forum_threads.id as threadid,imas_forum_threads.lastposttime FROM imas_forum_threads ";
$query .= "JOIN imas_forums ON imas_forum_threads.forumid=imas_forums.id AND imas_forum_threads.lastposttime<:now ";
$array = array(':now'=>$now);
if (!isset($teacherid)) {
  $query .= "AND (imas_forums.avail=2 OR (imas_forums.avail=1 AND imas_forums.startdate<$now && imas_forums.enddate>$now)) ";
}
$query .= "LEFT JOIN imas_forum_views AS mfv ";
$query .= "ON mfv.threadid=imas_forum_threads.id AND mfv.userid=:userid WHERE imas_forums.courseid=:courseid ";
$array[':userid']=  $userid;
$array[':courseid']=$cid;
if (!isset($teacherid)) {
  $query .= "AND (imas_forum_threads.stugroupid=0 OR imas_forum_threads.stugroupid IN (SELECT stugroupid FROM imas_stugroupmembers WHERE userid=:userid2)) ";
  $array[':userid2']=$userid;
}
$query .= "AND (mfv.tagged=1)";
$stm = $DBH->prepare($query);
$stm->execute($array);
$result = $stm->fetchALL(PDO::FETCH_ASSOC);

// $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
$forumname = array();
$forumids = array();
$lastpost = array();
foreach ($result  as $line) {
  $forumname[$line['threadid']] = $line['name'];
  $forumids[$line['threadid']] = $line['id'];
  $lastpost[$line['threadid']] = tzdate("D n/j/y, g:i a",$line['lastposttime']);
}
$lastforum = '';

if (isset($_GET['unflagall'])) {
  if (count($forumids)>0) {
    $threadids = implode(',', array_map('intval', array_keys($lastpost)));
    $DBH->query("UPDATE imas_forum_views SET tagged=0 WHERE threadid IN ($threadids)");
  }
  if ($from=='home') {
    header('Location: ' . $GLOBALS['basesiteurl'] . "/forums/../index.php?r=" . Sanitize::randomQueryStringParam());
  } else {
    header('Location: ' . $GLOBALS['basesiteurl'] . "/forums/../course/course.php?cid=$cid&r=" . Sanitize::randomQueryStringParam());
  }
  exit;
}


$placeinhead = "<style type=\"text/css\">\n@import url(\"$imasroot/forums/forums.css\");\n</style>\n";
$placeinhead .= '<script type="text/javascript" src="'.$imasroot.'/javascript/tablesorter.js?v=011517"></script>';
$placeinhead .= "<script type=\"text/javascript\">var AHAHsaveurl = '" . $GLOBALS['basesiteurl'] . "/savetagged.php?cid=$cid';</script>";
$placeinhead .= '<script type="text/javascript" src="'.$imasroot.'/javascript/thread.js?v=011517"></script>';
$pagetitle = _('Flagged Forum Posts');
require("../header.php");
echo "<div class=breadcrumb>$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; <a href=\"forums.php?cid=$cid\">Forums</a> &gt; "._('Flagged Forum Posts')."</div>\n";
echo '<div id="headerflaggedthreads" class="pagetitle"><h2>'._('Flagged Forum Posts').'</h2></div>';
echo "<p><button type=\"button\" onclick=\"window.location.href='flaggedthreads.php?from=" . Sanitize::encodeUrlParam($from) . "&cid=$cid&unflagall=true'\">" . _('Unflag All') . "</button></p>";

if (count($lastpost)>0) {
  echo '<table class="gb forum" id="newthreads"><thead><th>Topic</th><th>Started By</th><th>Forum</th><th>Last Post Date</th></thead><tbody>';
  $threadids = implode(',', array_map('intval', array_keys($lastpost)));
  //DB $query = "SELECT imas_forum_posts.*,imas_users.LastName,imas_users.FirstName,imas_forum_threads.lastposttime FROM imas_forum_posts,imas_users,imas_forum_threads ";
  //DB $query .= "WHERE imas_forum_posts.userid=imas_users.id AND imas_forum_posts.threadid=imas_forum_threads.id AND ";
  //DB $query .= "imas_forum_posts.threadid IN ($threadids) AND imas_forum_posts.parent=0 ORDER BY imas_forum_posts.forumid, imas_forum_threads.lastposttime DESC";
  //DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
  //DB while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
  $query = "SELECT imas_forum_posts.*,imas_users.LastName,imas_users.FirstName,imas_forum_threads.lastposttime FROM imas_forum_posts,imas_users,imas_forum_threads ";
  $query .= "WHERE imas_forum_posts.userid=imas_users.id AND imas_forum_posts.threadid=imas_forum_threads.id AND ";
  $query .= "imas_forum_posts.threadid IN ($threadids) imas_forum_threads.lastposttime<$now AND imas_forum_posts.parent=0 ORDER BY imas_forum_threads.lastposttime DESC";
  $stm = $DBH->query($query);
  $alt = 0;
  while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
    if ($line['isanon']==1) {
      $name = "Anonymous";
    } else {
      $name = "{$line['LastName']}, {$line['FirstName']}";
    }
    echo '<tr id="tr'.$line['threadid'].'" class="tagged">';
    echo "<td><span class=\"right\"><img class=\"pointer\" id=\"tag{$line['threadid']}\" src=\"$imasroot/img/flagfilled.gif\" onClick=\"toggletagged({$line['threadid']});return false;\" alt=\"Flagged\" /></span>";
    echo "<a href=\"posts.php?cid=$cid&forum=" . Sanitize::encodeUrlParam($forumids[$line['threadid']]) . "&thread=" . Sanitize::encodeUrlParam($line['threadid']) . "&page=-5\">" . Sanitize::encodeStringForDisplay($line['subject']) . "</a></td><td>" . Sanitize::encodeStringForDisplay($name) . "</td>";
    echo "<td><a href=\"thread.php?cid=$cid&forum=" . Sanitize::encodeUrlParam($forumids[$line['threadid']]) . "\">" . Sanitize::encodeStringForDisplay($forumname[$line['threadid']]) . '</a></td>';
    echo "<td>{$lastpost[$line['threadid']]}</td></tr>";
  }
  echo '</tbody></table>';
  echo '<script type="text/javascript">	initSortTable("newthreads",Array("S","S","S","D"),true);</script>';
} else {
  echo "No flagged posts";
}
require("../footer.php");
?>

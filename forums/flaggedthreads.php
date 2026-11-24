<?php
//IMathAS:  Flagged threads list for a course
//(c) 2017 David Lippman
require_once "../init.php";
require_once "threadlistfuncs.php";

$cid = Sanitize::courseId($_GET['cid']);
$from = $_GET['from'] ?? '';

$threadsperpage = $listperpage;
$page = Sanitize::onlyInt($_GET['page'] ?? 1);
if ($page < 1) {
	$page = 1;
}

$now = time();
//$fromwhere comes from threadlistfuncs.php's courseThreadWhereClause(),
//the single source of truth for "which threads match this view" (also
//used by posts.php's edge-resolution for type=courseflagged), so this
//can't silently drift out of sync with that logic.
list($fromwhere, $qarr) = courseThreadWhereClause($cid, 'flagged', isset($teacherid), $userid, $now);

$offset = max(0, ($page-1)*$threadsperpage);
$query = "SELECT imas_forums.name,imas_forums.id,imas_forum_threads.id as threadid,imas_forum_threads.lastposttime " . $fromwhere;
$query .= " ORDER BY imas_forum_threads.lastposttime DESC LIMIT $offset,$threadsperpage";
$stm = $DBH->prepare($query);
$stm->execute($qarr);
$result = $stm->fetchALL(PDO::FETCH_ASSOC);

$forumname = array();
$forumids = array();
$lastpost = array();
foreach ($result  as $line) {
  $forumname[$line['threadid']] = $line['name'];
  $forumids[$line['threadid']] = $line['id'];
  $lastpost[$line['threadid']] = tzdate("D n/j/y, g:i a",$line['lastposttime']);
}
$lastforum = '';

// count matching threads, for pagination - reuses $fromwhere so this can't
// drift from the main query above
$countquery = "SELECT COUNT(imas_forum_threads.id) " . $fromwhere;
$stm = $DBH->prepare($countquery);
$stm->execute($qarr);
$numpages = max(1, ceil($stm->fetchColumn(0)/$threadsperpage));

if (isset($_GET['unflagall'])) {
  //Unflag All is a bulk action independent of the current page - pull the
  //full matching id list (no LIMIT), not just what's currently displayed.
  $allquery = "SELECT imas_forum_threads.id " . $fromwhere;
  $stm = $DBH->prepare($allquery);
  $stm->execute($qarr);
  $allids = array_map('intval', $stm->fetchAll(PDO::FETCH_COLUMN, 0));
  if (count($allids)>0) {
    $threadids = implode(',', $allids);
    $DBH->query("UPDATE imas_forum_views SET tagged=0 WHERE threadid IN ($threadids)");
  }
  if ($from=='home') {
    header('Location: ' . $GLOBALS['basesiteurl'] . "/forums/../index.php?r=" . Sanitize::randomQueryStringParam());
  } else {
    $btf = isset($_GET['btf']) ? '&folder=' . Sanitize::encodeUrlParam($_GET['btf']) : '';
		header('Location: ' . $GLOBALS['basesiteurl'] . "/forums/../course/course.php?cid=$cid$btf&r=" . Sanitize::randomQueryStringParam());
  }
  exit;
}


$placeinhead = "<style type=\"text/css\">\n@import url(\"$staticroot/forums/forums.css\");\n</style>\n";
$placeinhead .= '<script type="text/javascript" src="'.$staticroot.'/javascript/tablesorter.js?v=112426"></script>';
$placeinhead .= "<script type=\"text/javascript\">var AHAHsaveurl = '" . $GLOBALS['basesiteurl'] . "/forums/savetagged.php?cid=$cid';</script>";
$placeinhead .= '<script type="text/javascript" src="'.$staticroot.'/javascript/thread.js?v=021326"></script>';
$placeinhead .= '<script type="text/javascript" src="'.$staticroot.'/javascript/forumthreadcache.js?v=090226"></script>';
$pagetitle = _('Flagged Forum Posts');
require_once "../header.php";
echo "<div class=breadcrumb>$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; <a href=\"forums.php?cid=$cid\">Forums</a> &gt; "._('Flagged Forum Posts')."</div>\n";
echo '<div id="headerflaggedthreads" class="pagetitle"><h1>'._('Flagged Forum Posts').'</h1></div>';
echo "<p><button type=\"button\" onclick=\"window.location.href='flaggedthreads.php?from=" . Sanitize::encodeUrlParam($from) . "&cid=$cid&unflagall=true'\">" . _('Unflag All') . "</button></p>";

if (count($lastpost)>0) {
  $pager = renderThreadListPager($page, $numpages, "flaggedthreads.php?cid=$cid&from=".Sanitize::encodeUrlParam($from));
  if ($pager != '') {
    echo "<div>$pager</div>";
  }
  echo '<table class="gb forum" id="newthreads"><thead><th>Topic</th><th>Started By</th><th>Forum</th><th>Last Post Date</th></thead><tbody>';
  $threadids = implode(',', array_map('intval', array_keys($lastpost)));
  $query = "SELECT imas_forum_posts.*,imas_users.LastName,imas_users.FirstName,imas_forum_threads.lastposttime FROM imas_forum_posts,imas_users,imas_forum_threads ";
  $query .= "WHERE imas_forum_posts.userid=imas_users.id AND imas_forum_posts.threadid=imas_forum_threads.id AND ";
  $query .= "imas_forum_posts.threadid IN ($threadids) AND imas_forum_threads.lastposttime<$now AND imas_forum_posts.parent=0 ORDER BY imas_forum_threads.lastposttime DESC";
  $stm = $DBH->query($query);
  $alt = 0;
  while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
    if ($line['isanon']==1) {
      $name = "Anonymous";
    } else {
      $name = "{$line['LastName']}, {$line['FirstName']}";
    }
    echo '<tr id="tr'.$line['threadid'].'" class="tagged">';
    echo '<td><div class=flexgroup><span style="flex-grow:1">';
    echo "<a class=\"threadlink\" href=\"posts.php?cid=$cid&forum=" . Sanitize::encodeUrlParam($forumids[$line['threadid']]) . "&thread=" . Sanitize::encodeUrlParam($line['threadid']) . "&type=courseflagged&page=$page\">" . Sanitize::encodeStringForDisplay($line['subject']) . "</a>";
    echo '</span><button type=button class="plain nopad" onclick="toggletagged('.Sanitize::onlyInt($line['threadid']).');" role="switch" aria-checked="'.(!empty($tags[$line['threadid']])?'true':'false').'" aria-label="'._('Tag post').'">';
		echo "<img class=\"pointer\" id=\"tag".Sanitize::onlyInt($line['threadid'])."\" src=\"$staticroot/img/flagfilled.svg\" alt=\"\"/>";
		echo '</button>';
    echo "</div></td><td><span class='pii-full-name'>" . Sanitize::encodeStringForDisplay($name) . "</span></td>";
    echo "<td><a href=\"thread.php?cid=$cid&forum=" . Sanitize::encodeUrlParam($forumids[$line['threadid']]) . "\">" . Sanitize::encodeStringForDisplay($forumname[$line['threadid']]) . '</a></td>';
    echo "<td>{$lastpost[$line['threadid']]}</td></tr>";
  }
  echo '</tbody></table>';
  echo '<script type="text/javascript">	initSortTable("newthreads",Array("S","S","S","D"),true);</script>';
  echo '<script>ForumThreadCache.seedFromPage({type: "courseflagged", tagfilter: "", numpages: '.intval($numpages).', threadsperpage: '.intval($threadsperpage).'});</script>';
  if ($pager != '') {
    echo "<div>$pager</div>";
  }
} else {
  echo "No flagged posts";
}
require_once "../footer.php";
?>

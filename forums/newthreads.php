<?php
//IMathAS:  New threads list for a course
//(c) 2006 David Lippman
require_once "../init.php";
require_once "threadlistfuncs.php";
$cid = Sanitize::courseId($_GET['cid']);
$from = $_GET['from'] ?? '';

if (!isset($teacherid) && !isset($tutorid) && !isset($studentid)) {
	exit;
}

$threadsperpage = $listperpage;
$page = Sanitize::onlyInt($_GET['page'] ?? 1);
if ($page < 1) {
	$page = 1;
}

/*
$query = "SELECT imas_forums.name,imas_forums.id,imas_forum_posts.threadid,max(imas_forum_posts.postdate) as lastpost,mfv.lastview,count(imas_forum_posts.id) as pcount FROM imas_forum_posts ";
$query .= "JOIN imas_forums ON imas_forum_posts.forumid=imas_forums.id LEFT JOIN (SELECT * FROM imas_forum_views WHERE userid='$userid') AS mfv ";
$query .= "ON mfv.threadid=imas_forum_posts.threadid WHERE imas_forums.courseid='$cid' AND imas_forums.grpaid=0 ";
$query .= "GROUP BY imas_forum_posts.threadid HAVING ((max(imas_forum_posts.postdate)>mfv.lastview) OR (mfv.lastview IS NULL))";
*/
$now = time();
//$fromwhere comes from threadlistfuncs.php's courseThreadWhereClause(),
//the single source of truth for "which threads match this view" (also
//used by posts.php's edge-resolution for type=coursenew), so this can't
//silently drift out of sync with that logic.
list($fromwhere, $qarr) = courseThreadWhereClause($cid, 'new', isset($teacherid), $userid, $now);

$offset = max(0, ($page-1)*$threadsperpage);
$query = "SELECT imas_forums.name,imas_forums.id,imas_forum_threads.id as threadid,imas_forum_threads.lastposttime,mfv.tagged " . $fromwhere;
$query .= " ORDER BY imas_forum_threads.lastposttime DESC LIMIT $offset,$threadsperpage";
$stm = $DBH->prepare($query);
$stm->execute($qarr);
$result = $stm->fetchALL(PDO::FETCH_ASSOC);

$forumname = array();
$forumids = array();
$lastpost = array();
$tags = array();
foreach ($result  as $line) {
  $forumname[$line['threadid']] = $line['name'];
  $forumids[$line['threadid']] = $line['id'];
  $lastpost[$line['threadid']] = tzdate("D n/j/y, g:i a",$line['lastposttime']);
  $tags[$line['threadid']] = $line['tagged'];
}
$lastforum = '';

// count matching threads, for pagination - reuses $fromwhere so this can't
// drift from the main query above
$countquery = "SELECT COUNT(imas_forum_threads.id) " . $fromwhere;
$stm = $DBH->prepare($countquery);
$stm->execute($qarr);
$numpages = max(1, ceil($stm->fetchColumn(0)/$threadsperpage));

if (isset($_GET['markread']) && isset($_POST['checked']) && !empty($_POST['checked'])) {
	$checked = array_map('Sanitize::onlyInt', $_POST['checked']);
  // ensure checked are new threads
  $checked = array_intersect($checked, array_keys($lastpost));
	$toupdate = array();
  if (count($checked)>0) {
    $threadids_query_placeholders = Sanitize::generateQueryPlaceholders($checked);
    $stm = $DBH->prepare("SELECT threadid FROM imas_forum_views WHERE userid=? AND threadid IN ($threadids_query_placeholders)");
    $stm->execute(array_merge(array($userid), $checked));
    while ($row = $stm->fetch(PDO::FETCH_NUM)) {
      $toupdate[] = $row[0];
    }
  }

	if (count($toupdate)>0) {
		$toupdatelistSanitize = array_map('Sanitize::onlyInt', $toupdate);
		$toupdatelist_query_placeholders = Sanitize::generateQueryPlaceholders($toupdatelistSanitize);
  	$stm = $DBH->prepare("UPDATE imas_forum_views SET lastview=? WHERE userid=? AND threadid IN ($toupdatelist_query_placeholders)");
		$stm->execute(array_merge(array($now, $userid), $toupdatelistSanitize));
  }
  $toinsert = array_diff($checked,$toupdate);
  if (count($toinsert)>0) {
  		$ph =
  		$query = "INSERT INTO imas_forum_views (userid,threadid,lastview) VALUES ";
  		$qarray = array();
  		$first = true;
  		foreach($toinsert as $i=>$tid) {
        if (!$first) {
            $query .= ',';
        }
        $query .= "(?,?,?)";
        array_push($qarray, $userid, $tid, $now);
        $first = false;
      }
		  $stm = $DBH->prepare($query);
		  $stm->execute($qarray);
	}
	header('Location: ' . $GLOBALS['basesiteurl'] . "/forums/newthreads.php?cid=$cid&from=".Sanitize::simpleString($from).($page>1?'&page='.$page:''));
       	exit;
}

$placeinhead = "<style type=\"text/css\">\n@import url(\"$staticroot/forums/forums.css\");\n</style>\n";
$placeinhead .= '<script type="text/javascript" src="'.$staticroot.'/javascript/tablesorter.js?v=112426"></script>';
$placeinhead .= "<script type=\"text/javascript\" src=\"$staticroot/javascript/thread.js?v=021326\"></script>";
$placeinhead .= "<script type=\"text/javascript\" src=\"$staticroot/javascript/forumthreadcache.js?v=090226\"></script>";
$placeinhead .= "<script type=\"text/javascript\">var AHAHsaveurl = '" . $GLOBALS['basesiteurl'] . "/forums/savetagged.php?cid=$cid';";
$placeinhead .= '$(function() {$("img[src*=\'flag\']").attr("title","Flag Message");});</script>';
$pagetitle = _('New Forum Posts');
require_once "../header.php";

echo "<div class=breadcrumb>$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; <a href=\"forums.php?cid=$cid\">Forums</a> &gt; New Forum Posts</div>\n";
echo '<div id="headernewthreads" class="pagetitle"><h1>New Forum Posts</h1></div>';

if (count($lastpost)>0) {
  $pager = renderThreadListPager($page, $numpages, "newthreads.php?cid=$cid&from=".Sanitize::encodeUrlParam($from));
  if ($pager != '') {
    echo "<div>$pager</div>";
  }
  echo '<form id=qform method=post action="newthreads.php?from='.Sanitize::encodeUrlParam($from).'&cid='.$cid.'&markread=true'.($page>1?'&page='.$page:'').'">';
  echo '<p>Check: <a href="#" onclick="return chkAllNone(\'qform\',\'checked[]\',true)">'._('All').'</a> <a href="#" onclick="return chkAllNone(\'qform\',\'checked[]\',false)">'._('None').'</a> ';
  echo '<button type=submit>'._('Mark Selected as Read').'</button></p>';
  echo '<table class="gb forum" id="newthreads"><thead><th></th><th>Topic</th><th>Started By</th><th>Forum</th><th>Last Post Date</th></thead><tbody>';
  $threadids = array_map('intval', array_keys($lastpost));
	$ph = Sanitize::generateQueryPlaceholders($threadids);
  $query = "SELECT imas_forum_posts.isanon,imas_forum_posts.threadid,imas_forum_posts.subject,imas_users.LastName,imas_users.FirstName,imas_forum_threads.lastposttime FROM imas_forum_posts,imas_users,imas_forum_threads ";
  $query .= "WHERE imas_forum_posts.userid=imas_users.id AND imas_forum_posts.threadid=imas_forum_threads.id AND ";
  $query .= "imas_forum_posts.threadid IN ($ph) AND imas_forum_threads.lastposttime<? AND imas_forum_posts.parent=0 ORDER BY imas_forum_threads.lastposttime DESC";
  $stm = $DBH->prepare($query);
	$stm->execute(array_merge($threadids, array($now)));
  $alt = 0;
  $ln = 0;
  while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
    $ln++;
    if ($line['isanon']==1) {
      $name = "Anonymous";
    } else {
      $name = Sanitize::encodeStringForDisplay($line['LastName']).", ". Sanitize::encodeStringForDisplay($line['FirstName']);
    }
    if ($alt==0) {$stripe = "even"; $alt=1;} else {$stripe = "odd"; $alt=0;}
    echo '<tr>';
    $classes = array();
    if (!empty($tags[$line['threadid']])) {
        $classes[] = "tagged";
    }
    echo "<tr id=\"tr".Sanitize::onlyInt($line['threadid'])."\"";
    if (count($classes)>0) {
            echo ' class="'.implode(' ',$classes).'"';
    }
    echo "><td>";
    echo '<input type=checkbox name="checked[]" value="'.Sanitize::onlyInt($line['threadid']).'" id="cb'.$ln.'"/></td>';
    echo '<td><div class=flexgroup><label for="cb'.$ln.'" style="flex-grow:1">';
    echo "<a class=\"threadlink\" href=\"posts.php?cid=$cid&forum=".Sanitize::onlyInt($forumids[$line['threadid']])."&thread=".Sanitize::onlyInt($line['threadid'])."&type=coursenew&page=$page\">".Sanitize::encodeStringForDisplay($line['subject'])."</a></label>";

    echo '<button type=button class="plain nopad" onclick="toggletagged('.Sanitize::onlyInt($line['threadid']).');" role="switch" aria-checked="'.(!empty($tags[$line['threadid']])?'true':'false').'" aria-label="'._('Tag post').'">';
			if (!empty($tags[$line['threadid']])) {
				echo "<img class=\"pointer\" id=\"tag".Sanitize::onlyInt($line['threadid'])."\" src=\"$staticroot/img/flagfilled.svg\" alt=\"\"/>";
			} else {
				echo "<img class=\"pointer\" id=\"tag".Sanitize::onlyInt($line['threadid'])."\" src=\"$staticroot/img/flagempty.svg\" alt=\"\"/>";
			}
		echo '</button>';
    echo "</div></td>";
    printf("<td><span class='pii-full-name'>%s</span></td>", Sanitize::encodeStringForDisplay($name));
    echo "<td><a href=\"thread.php?cid=$cid&forum=".Sanitize::onlyInt($forumids[$line['threadid']])."\">".Sanitize::encodeStringForDisplay($forumname[$line['threadid']]).'</a></td>';
    echo "<td>".Sanitize::encodeStringForDisplay($lastpost[$line['threadid']])."</td></tr>";
  }
  echo '</tbody></table>';
  echo '<script type="text/javascript">	initSortTable("newthreads",Array(false,"S","S","S","D"),true);</script>';
  echo '<script>ForumThreadCache.seedFromPage({type: "coursenew", tagfilter: "", numpages: '.intval($numpages).', threadsperpage: '.intval($threadsperpage).'});</script>';
  echo '</form>';
  if ($pager != '') {
    echo "<div>$pager</div>";
  }
} else {
  echo "No new posts";
}
require_once "../footer.php";
?>

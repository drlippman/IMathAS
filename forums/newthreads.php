<?php
//IMathAS:  New threads list for a course
//(c) 2006 David Lippman
require("../init.php");
$cid = Sanitize::courseId($_GET['cid']);
$from = $_GET['from'];

/*
$query = "SELECT imas_forums.name,imas_forums.id,imas_forum_posts.threadid,max(imas_forum_posts.postdate) as lastpost,mfv.lastview,count(imas_forum_posts.id) as pcount FROM imas_forum_posts ";
$query .= "JOIN imas_forums ON imas_forum_posts.forumid=imas_forums.id LEFT JOIN (SELECT * FROM imas_forum_views WHERE userid='$userid') AS mfv ";
$query .= "ON mfv.threadid=imas_forum_posts.threadid WHERE imas_forums.courseid='$cid' AND imas_forums.grpaid=0 ";
$query .= "GROUP BY imas_forum_posts.threadid HAVING ((max(imas_forum_posts.postdate)>mfv.lastview) OR (mfv.lastview IS NULL))";
*/
$now = time();
$query = "SELECT imas_forums.name,imas_forums.id,imas_forum_threads.id as threadid,imas_forum_threads.lastposttime,mfv.tagged FROM imas_forum_threads ";
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
$query .= "AND (imas_forum_threads.lastposttime>mfv.lastview OR (mfv.lastview IS NULL))";
$stm = $DBH->prepare($query);
$stm->execute($array);
$result = $stm->fetchALL(PDO::FETCH_ASSOC);

// $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
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

if (isset($_GET['markread']) && isset($_POST['checked']) && count($_POST['checked'])>0) {
	$checked = array_map('Sanitize::onlyInt', $_POST['checked']);
	$toupdate = array();
	$threadids_query_placeholders = Sanitize::generateQueryPlaceholders($checked);
	$stm = $DBH->prepare("SELECT threadid FROM imas_forum_views WHERE userid=? AND threadid IN ($threadids_query_placeholders)");
	$stm->execute(array_merge(array($userid), $checked));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$toupdate[] = $row[0];
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
	if (count($forumids)==count($checked)) { //marking all read
		if ($from=='home') {
			header('Location: ' . $GLOBALS['basesiteurl'] . "/index.php");
		} else {
      $btf = isset($_GET['btf']) ? '&folder=' . Sanitize::encodeUrlParam($_GET['btf']) : '';
  		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?cid=$cid$btf");
		}
	} else {
		header('Location: ' . $GLOBALS['basesiteurl'] . "/forums/newthreads.php?cid=$cid&from=".Sanitize::simpleString($from));
	}
       	exit;
}

$placeinhead = "<style type=\"text/css\">\n@import url(\"$staticroot/forums/forums.css\");\n</style>\n";
$placeinhead .= '<script type="text/javascript" src="'.$staticroot.'/javascript/tablesorter.js?v=011517"></script>';
$pagetitle = _('New Forum Posts');
require("../header.php");

echo "<div class=breadcrumb>$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; <a href=\"forums.php?cid=$cid\">Forums</a> &gt; New Forum Posts</div>\n";
echo '<div id="headernewthreads" class="pagetitle"><h1>New Forum Posts</h1></div>';

if (count($lastpost)>0) {
  echo '<form id=qform method=post action="newthreads.php?from='.Sanitize::encodeUrlParam($from).'&cid='.$cid.'&markread=true">';
  echo '<p>Check: <a href="#" onclick="return chkAllNone(\'qform\',\'checked[]\',true)">'._('All').'</a> <a href="#" onclick="return chkAllNone(\'qform\',\'checked[]\',false)">'._('None').'</a> ';
  echo '<button type=submit>'._('Mark Selected as Read').'</button></p>';
  echo '<table class="gb forum" id="newthreads"><thead><th></th><th>Topic</th><th>Started By</th><th>Forum</th><th>Last Post Date</th></thead><tbody>';
  $threadids = array_map('intval', array_keys($lastpost));
	$ph = Sanitize::generateQueryPlaceholders($threadids);
  $query = "SELECT imas_forum_posts.*,imas_users.LastName,imas_users.FirstName,imas_forum_threads.lastposttime FROM imas_forum_posts,imas_users,imas_forum_threads ";
  $query .= "WHERE imas_forum_posts.userid=imas_users.id AND imas_forum_posts.threadid=imas_forum_threads.id AND ";
  $query .= "imas_forum_posts.threadid IN ($ph) AND imas_forum_threads.lastposttime<? AND imas_forum_posts.parent=0 ORDER BY imas_forum_threads.lastposttime DESC";
  $stm = $DBH->prepare($query);
	$stm->execute(array_merge($threadids, array($now)));
  $alt = 0;
  while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
    if ($line['isanon']==1) {
      $name = "Anonymous";
    } else {
      $name = Sanitize::encodeStringForDisplay($line['LastName']).", ". Sanitize::encodeStringForDisplay($line['FirstName']);
    }
    if ($alt==0) {$stripe = "even"; $alt=1;} else {$stripe = "odd"; $alt=0;}
    echo '<tr>';
    echo '<td><input type=checkbox name="checked[]" value="'.Sanitize::onlyInt($line['threadid']).'"/></td>';
    echo "<td><a href=\"posts.php?cid=$cid&forum=".Sanitize::onlyInt($forumids[$line['threadid']])."&thread=".Sanitize::onlyInt($line['threadid'])."&page=-3\">".Sanitize::encodeStringForDisplay($line['subject'])."</a></td>";
    printf("<td>%s</td>", Sanitize::encodeStringForDisplay($name));
    echo "<td><a href=\"thread.php?cid=$cid&forum=".Sanitize::onlyInt($forumids[$line['threadid']])."\">".Sanitize::encodeStringForDisplay($forumname[$line['threadid']]).'</a></td>';
    echo "<td>".Sanitize::encodeStringForDisplay($lastpost[$line['threadid']])."</td></tr>";
  }
  echo '</tbody></table>';
  echo '<script type="text/javascript">	initSortTable("newthreads",Array("S","S","S","D"),true);</script>';
  echo '</form>';
} else {
  echo "No new posts";
}
require("../footer.php");
?>

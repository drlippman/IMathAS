<?php
//IMathAS:  New threads list for a course
//(c) 2006 David Lippman
require("../validate.php");
$cid = $_GET['cid'];
$from = $_GET['from'];
/*
$query = "SELECT imas_forums.name,imas_forums.id,imas_forum_posts.threadid,max(imas_forum_posts.postdate) as lastpost,mfv.lastview,count(imas_forum_posts.id) as pcount FROM imas_forum_posts ";
$query .= "JOIN imas_forums ON imas_forum_posts.forumid=imas_forums.id LEFT JOIN (SELECT * FROM imas_forum_views WHERE userid='$userid') AS mfv ";
$query .= "ON mfv.threadid=imas_forum_posts.threadid WHERE imas_forums.courseid='$cid' AND imas_forums.grpaid=0 ";
$query .= "GROUP BY imas_forum_posts.threadid HAVING ((max(imas_forum_posts.postdate)>mfv.lastview) OR (mfv.lastview IS NULL))";
*/
$now = time();
//DB $query = "SELECT imas_forums.name,imas_forums.id,imas_forum_threads.id as threadid,imas_forum_threads.lastposttime FROM imas_forum_threads ";
//DB $query .= "JOIN imas_forums ON imas_forum_threads.forumid=imas_forums.id ";
$query = "SELECT imas_forums.name,imas_forums.id,imas_forum_threads.id as threadid,imas_forum_threads.lastposttime,mfv.tagged FROM imas_forum_threads ";
$query .= "JOIN imas_forums ON imas_forum_threads.forumid=imas_forums.id ";
$array = array();
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

if (isset($_GET['markallread'])) {
  $now = time();
  if (count($forumids)>0) {
    $forumidlist = array_map('Sanitize::onlyInt', $forumids);
    $forumidlist_query_placeholders = Sanitize::generateQueryPlaceholders($forumidlist);
    //DB $query = "SELECT DISTINCT threadid FROM imas_forum_posts WHERE forumid IN ($forumidlist)";
    //DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
    $stm = $DBH->prepare("SELECT DISTINCT threadid FROM imas_forum_posts WHERE forumid IN ($forumidlist_query_placeholders)");
	  $stm->execute($forumidlist);

    $threadids = array();
    while ($row = $stm->fetch(PDO::FETCH_NUM)) {
      $threadids[] = $row[0];
    }
    if (count($threadids)>0) {
      // $threadlist = implode(',', $threadids);  //INT vals from DB - safe
      $threadidsSanitize = array_map('Sanitize::onlyInt', $threadids);//INT vals from DB - safe
      $threadids_query_placeholders = Sanitize::generateQueryPlaceholders($threadidsSanitize);

      $toupdate = array();
      //DB $query = "SELECT threadid FROM imas_forum_views WHERE userid='$userid' AND threadid IN ($threadlist)";
      //DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
      //DB while ($row = mysql_fetch_row($result)) {
      //DB $to
      $stm = $DBH->prepare("SELECT threadid FROM imas_forum_views WHERE userid=? AND threadid IN ($threadids_query_placeholders)");
      $stm->execute(array_merge(array($userid), $threadidsSanitize));
      while ($row = $stm->fetch(PDO::FETCH_NUM)) {
        $toupdate[] = $row[0];
      }
      if (count($toupdate)>0) {
        $toupdatelistSanitize = array_map('Sanitize::onlyInt', $toupdate);//INT vals from DB - safe
        $toupdatelist_query_placeholders = Sanitize::generateQueryPlaceholders($toupdatelistSanitize);
        //DB $query = "UPDATE imas_forum_views SET lastview=$now WHERE userid='$userid AND threadid IN ($toupdatelist)'";
        //DB mysql_query($query) or die("Query failed : $query " . mysql_error());
  			$stm = $DBH->prepare("UPDATE imas_forum_views SET lastview=? WHERE userid=? AND threadid IN ($toupdatelist_query_placeholders)");
		    $stm->execute(array_merge(array($now, $userid), $toupdatelistSanitize));
  		}
      $toinsert = array_diff($threadids,$toupdate);
      if (count($toinsert)>0) {
        //DB $query = "INSERT INTO imas_forum_views (userid,threadid,lastview) VALUES ";
        //DB $query .= ",('$userid','$tid',$now)";
        $query = "INSERT INTO imas_forum_views (userid,threadid,lastview) VALUES ";
        $array = array();

        $first = true;
        foreach($toinsert as $i=>$tid) {
          if (!$first) {
						$query .= ',';
					}
          //DB $query .= "('$userid','$tid',$now)";
					$query .= "(?,?,?)";
          array_push($array, $userid, $tid, $now);

          $first = false;
        }
        $stm = $DBH->prepare($query);
        $stm->execute($array);

        // mysql_query($query) or die("Query failed : $query " . mysql_error());
      }
    }
  }
  if ($from=='home') {
    header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/../index.php");
  } else {
    header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/../course/course.php?cid=$cid");
  }
  exit;
}


$placeinhead = "<style type=\"text/css\">\n@import url(\"$imasroot/forums/forums.css\");\n</style>\n";
$placeinhead .= '<script type="text/javascript" src="'.$imasroot.'/javascript/tablesorter.js?v=011517"></script>';
$pagetitle = _('New Forum Posts');
require("../header.php");
echo "<div class=breadcrumb>$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; <a href=\"forums.php?cid=$cid\">Forums</a> &gt; New Forum Posts</div>\n";
echo '<div id="headernewthreads" class="pagetitle"><h2>New Forum Posts</h2></div>';
echo "<p><button type=\"button\" onclick=\"window.location.href='newthreads.php?from=$from&cid=$cid&markallread=true'\">"._('Mark all Read')."</button></p>";

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
  $query .= "imas_forum_posts.threadid IN ($threadids) AND imas_forum_posts.parent=0 ORDER BY imas_forum_threads.lastposttime DESC";
  $stm = $DBH->query($query);
  $alt = 0;
  while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
    if ($line['isanon']==1) {
      $name = "Anonymous";
    } else {
      $name = "{$line['LastName']}, {$line['FirstName']}";
    }
    if ($alt==0) {$stripe = "even"; $alt=1;} else {$stripe = "odd"; $alt=0;}
    echo '<tr>';
    echo "<td><a href=\"posts.php?cid=$cid&forum={$forumids[$line['threadid']]}&thread={$line['threadid']}&page=-3\">{$line['subject']}</a></td>";
    echo "<td>$name</td>";
    echo "<td><a href=\"thread.php?cid=$cid&forum={$forumids[$line['threadid']]}\">".$forumname[$line['threadid']].'</a></td>';
    echo "<td>{$lastpost[$line['threadid']]}</td></tr>";
  }
  echo '</tbody></table>';
  echo '<script type="text/javascript">	initSortTable("newthreads",Array("S","S","S","D"),true);</script>';
} else {
  echo "No new posts";
}
require("../footer.php");
?>

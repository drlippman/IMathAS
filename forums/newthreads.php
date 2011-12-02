<?php
//IMathAS:  New threads list for a course
//(c) 2006 David Lippman
   	require("../validate.php");
	$cid = $_GET['cid'];
	
	/*
	$query = "SELECT imas_forums.name,imas_forums.id,imas_forum_posts.threadid,max(imas_forum_posts.postdate) as lastpost,mfv.lastview,count(imas_forum_posts.id) as pcount FROM imas_forum_posts ";
	$query .= "JOIN imas_forums ON imas_forum_posts.forumid=imas_forums.id LEFT JOIN (SELECT * FROM imas_forum_views WHERE userid='$userid') AS mfv ";
	$query .= "ON mfv.threadid=imas_forum_posts.threadid WHERE imas_forums.courseid='$cid' AND imas_forums.grpaid=0 ";
	$query .= "GROUP BY imas_forum_posts.threadid HAVING ((max(imas_forum_posts.postdate)>mfv.lastview) OR (mfv.lastview IS NULL))";
	*/
	$now = time();
	$query = "SELECT imas_forums.name,imas_forums.id,imas_forum_threads.id as threadid,imas_forum_threads.lastposttime FROM imas_forum_threads ";
	$query .= "JOIN imas_forums ON imas_forum_threads.forumid=imas_forums.id ";
	if (!isset($teacherid)) {
		$query .= "AND (imas_forums.avail=2 OR (imas_forums.avail=1 AND imas_forums.startdate<$now && imas_forums.enddate>$now)) ";
	}
	$query .= "LEFT JOIN imas_forum_views AS mfv ";
	$query .= "ON mfv.threadid=imas_forum_threads.id AND mfv.userid='$userid' WHERE imas_forums.courseid='$cid' ";//AND imas_forums.grpaid=0 ";
	if (!isset($teacherid)) {
		$query .= "AND (imas_forum_threads.stugroupid=0 OR imas_forum_threads.stugroupid IN (SELECT stugroupid FROM imas_stugroupmembers WHERE userid='$userid')) ";
	}
	$query .= "AND (imas_forum_threads.lastposttime>mfv.lastview OR (mfv.lastview IS NULL))";
	
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$forumname = array();
	$forumids = array();
	$lastpost = array();
	while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		$forumname[$line['threadid']] = $line['name'];
		$forumids[$line['threadid']] = $line['id'];
		$lastpost[$line['threadid']] = tzdate("F j, Y, g:i a",$line['lastposttime']);
	}
	$lastforum = '';
	
	if (isset($_GET['markallread'])) {
		$now = time();
		if (count($forumids)>0) {
			$forumidlist = implode(',',$forumids);
			$query = "SELECT DISTINCT threadid FROM imas_forum_posts WHERE forumid IN ($forumidlist)";
			$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			
			$threadids = array();
			while ($row = mysql_fetch_row($result)) {
				$threadids[] = $row[0];
			}
			if (count($threadids)>0) {
				$threadlist = implode(',',$threadids);
				$toupdate = array();
				$query = "SELECT threadid FROM imas_forum_views WHERE userid='$userid' AND threadid IN ($threadlist)";
				$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
				while ($row = mysql_fetch_row($result)) {
					$toupdate[] = $row[0];
				}
				if (count($toupdate)>0) {
					$touplodatelist= implode(',',$toupdate);
					$query = "UPDATE imas_forum_views SET lastview=$now WHERE userid='$userid AND threadid IN ($toupdatelist)'";
					mysql_query($query) or die("Query failed : $query " . mysql_error());
				}
				$toinsert = array_diff($threadids,$toupdate);
				if (count($toinsert)>0) {
					$query = "INSERT INTO imas_forum_views (userid,threadid,lastview) VALUES ";
					$first = true;
					foreach($toinsert as $i=>$tid) {
						if (!$first) {
							$query .= ",('$userid','$tid',$now)";
						} else {
							$query .= "('$userid','$tid',$now)";
							$first = false;
						}
					}
					mysql_query($query) or die("Query failed : $query " . mysql_error());
				}
			}
		}
		
		header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/../index.php");
	}
	
	
	$placeinhead = "<style type=\"text/css\">\n@import url(\"$imasroot/forums/forums.css\");\n</style>\n";
	require("../header.php");
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">$coursename</a> &gt; New Forum Topics</div>\n";
	echo '<div id="headernewthreads" class="pagetitle"><h2>New Forum Posts</h2></div>';
	echo "<p><a href=\"newthreads.php?cid=$cid&markallread=true\">Mark all Read</a></p>";

	if (count($lastpost)>0) {
		$threadids = implode(',',array_keys($lastpost));
		$query = "SELECT imas_forum_posts.*,imas_users.LastName,imas_users.FirstName FROM imas_forum_posts,imas_users ";
		$query .= "WHERE imas_forum_posts.userid=imas_users.id AND imas_forum_posts.threadid IN ($threadids) AND imas_forum_posts.parent=0 ORDER BY imas_forum_posts.forumid";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());

		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			if ($forumname[$line['threadid']]!=$lastforum) {
				if ($lastforum!='') { echo '</tbody></table>';}
				echo "<h4>Forum: <a href=\"thread.php?cid=$cid&forum={$forumids[$line['threadid']]}\">".$forumname[$line['threadid']].'</a></h4><table class="forum"><thead><th>Topic</th><th>Last Post Date</th></thead><tbody>';
				$lastforum = $forumname[$line['threadid']];
			}
			if ($line['isanon']==1) {
				$name = "Anonymous";
			} else {
				$name = "{$line['LastName']}, {$line['FirstName']}";
			}
			echo "<tr><td><a href=\"posts.php?cid=$cid&forum={$forumids[$line['threadid']]}&thread={$line['threadid']}&page=-3\">{$line['subject']}</a></b>: $name</td>";
			echo "<td>{$lastpost[$line['threadid']]}</td></tr>";
		}
		if ($lastforum!='') { echo '</tbody></table>';}
		echo '</ul>';
	} else {
		echo "No new posts";
	}
	require("../footer.php");
?>

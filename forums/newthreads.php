<?php
//IMathAS:  New threads list for a course
//(c) 2006 David Lippman
   	require("../validate.php");
	$cid = $_GET['cid'];
	
	$placeinhead = "<style type=\"text/css\">\n@import url(\"$imasroot/forums/forums.css\");\n</style>\n";
	require("../header.php");
	echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"../course/course.php?cid=$cid\">$coursename</a> &gt; New Forum Topics</div>\n";
	echo "<h3>New Forum Posts</h3>\n";

	$query = "SELECT imas_forums.name,imas_forums.id,imas_forum_posts.threadid,max(imas_forum_posts.postdate) as lastpost,mfv.lastview,count(imas_forum_posts.id) as pcount FROM imas_forum_posts ";
	$query .= "JOIN imas_forums ON imas_forum_posts.forumid=imas_forums.id LEFT JOIN (SELECT * FROM imas_forum_views WHERE userid='$userid') AS mfv ";
	$query .= "ON mfv.threadid=imas_forum_posts.threadid WHERE imas_forums.courseid='$cid' ";
	$query .= "GROUP BY imas_forum_posts.threadid HAVING ((max(imas_forum_posts.postdate)>mfv.lastview) OR (mfv.lastview IS NULL))";
	
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$forumname = array();
	$forumids = array();
	$lastpost = array();
	while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		$forumname[$line['threadid']] = $line['name'];
		$forumids[$line['threadid']] = $line['id'];
		$lastpost[$line['threadid']] = tzdate("F j, Y, g:i a",$line['lastpost']);
	}
	$lastforum = '';
	if (count($lastpost)>0) {
		$threadids = implode(',',array_keys($lastpost));
		$query = "SELECT imas_forum_posts.*,imas_users.LastName,imas_users.FirstName FROM imas_forum_posts,imas_users ";
		$query .= "WHERE imas_forum_posts.userid=imas_users.id AND imas_forum_posts.id IN ($threadids) ORDER BY imas_forum_posts.forumid";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());

		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			if ($forumname[$line['threadid']]!=$lastforum) {
				if ($lastforum!='') { echo '</tbody></table>';}
				echo '<h4>Forum: '.$forumname[$line['threadid']].'</h4><table class="forum"><thead><th>Topic</th><th>Last Post Date</th></thead><tbody>';
				$lastforum = $forumname[$line['threadid']];
			}
			echo "<tr><td><a href=\"posts.php?cid=$cid&forum={$forumids[$line['threadid']]}&thread={$line['id']}&page=-2\">{$line['subject']}</a></b>: {$line['LastName']}, {$line['FirstName']}</td>";
			echo "<td>{$lastpost[$line['threadid']]}</td></tr>";
		}
		echo '</ul>';
	} else {
		echo "No new posts";
	}
	require("../footer.php");
?>

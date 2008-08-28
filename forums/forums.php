<?php
	//Listing of all forums for a course - not being used
	//(c) 2006 David Lippman
	
	require("../validate.php");
	if (!isset($teacherid) && !isset($tutorid) && !isset($studentid)) {
	   require("../header.php");
	   echo "You are not enrolled in this course.  Please return to the <a href=\"../index.php\">Home Page</a> and enroll\n";
	   require("../footer.php");
	   exit;
	}
	if (isset($teacherid)) {
		$isteacher = true;	
	} else {
		$isteacher = false;
	}
	
	if (!isset($_GET['cid'])) {
		exit;
	}
	
	$cid = $_GET['cid'];
	
	
	$pagetitle = "Forums";
	$placeinhead = "<style type=\"text/css\">\n@import url(\"$imasroot/forums/forums.css\");\n</style>\n";
	require("../header.php");
	echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"../course/course.php?cid=$cid\">$coursename</a> &gt; Forums</div>\n";
	
?>
	
	<h3>Forums</h3>
	
	<table class=forum>
	<thead>
	<tr><th>Forum Name</th><th>Threads</th><th>Posts</th><th>Last Post Date</th></tr>
	</thead>
	<tbody>
<?php
	
	$query = "SELECT imas_forums.id,COUNT(imas_forum_posts.id) FROM imas_forums LEFT JOIN imas_forum_posts ON ";
	$query .= "imas_forums.id=imas_forum_posts.forumid WHERE imas_forum_posts.parent=0 AND imas_forums.courseid='$cid' GROUP BY imas_forum_posts.forumid ORDER BY imas_forums.id";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$threadcount[$row[0]] = $row[1];
	}

	$query = "SELECT imas_forums.id,COUNT(imas_forum_posts.id) AS postcount,MAX(imas_forum_posts.postdate) AS maxdate FROM imas_forums LEFT JOIN imas_forum_posts ON ";
	$query .= "imas_forums.id=imas_forum_posts.forumid WHERE imas_forums.courseid='$cid' GROUP BY imas_forum_posts.forumid ORDER BY imas_forums.id";

	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$postcount[$row[0]] = $row[1];
		$maxdate[$row[0]] = $row[2];
	}

	$query = "SELECT imas_forums.id,imas_forum_posts.threadid,max(imas_forum_posts.postdate) as lastpost,mfv.lastview,count(imas_forum_posts.id) as pcount FROM imas_forum_posts ";
	$query .= "JOIN imas_forums ON imas_forum_posts.forumid=imas_forums.id LEFT JOIN (SELECT * FROM imas_forum_views WHERE userid='$userid') AS mfv ";
	$query .= "ON mfv.threadid=imas_forum_posts.threadid WHERE imas_forums.courseid='$cid' AND imas_forums.grpaid=0 ";
	$query .= "GROUP BY imas_forum_posts.threadid HAVING ((max(imas_forum_posts.postdate)>mfv.lastview) OR (mfv.lastview IS NULL))";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		$newcnt[$line['id']] += $line['pcount'];
	}
	
	
	$query = "SELECT * FROM imas_forums WHERE imas_forums.courseid='$cid'";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		echo "<tr><td>";
		if ($isteacher) {
			echo '<span class="right">';
			echo "<a href=\"../course/addforum.php?cid=$cid&id={$line['id']}\">Modify</a> ";
			echo '</span>';
		}
		echo "<b><a href=\"thread.php?cid=$cid&forum={$line['id']}\">{$line['name']}</a></b> ";
		if ($newcnt[$line['id']]>0) {
			 echo "<a href=\"thread.php?cid=$cid&forum={$line['id']}&page=-1\" style=\"color:red\">New Posts ({$newcnt[$line['id']]})</a>";
		}
		echo "</td>\n";
		if (isset($threadcount[$line['id']])) {
			$threads = $threadcount[$line['id']];
			$posts = $postcount[$line['id']];
			$lastpost = tzdate("F j, Y, g:i a",$maxdate[$line['id']]);
		} else {
			$threads = 0;
			$posts = 0;
			$lastpost = '';
		}
		echo "<td class=c>$threads</td><td class=c>$posts</td><td class=c>$lastpost</td></tr>\n";
	}
?>
	</tbody>
	</table>
<?php

	
	require("../footer.php");
?>
	
	
	
	

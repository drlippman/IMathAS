<?php
	//Lists forum posts by Student name
	//(c) 2006 David Lippman
	
	require("../validate.php");
	if (!isset($teacherid)) {
	   require("../header.php");
	   echo "You must be a teacher to access this page\n";
	   require("../footer.php");
	   exit;
	}
	
	$forumid = $_GET['forum'];
	$cid = $_GET['cid'];
	
	require("../header.php");
	echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"../course/course.php?cid=$cid\">$coursename</a> &gt; <a href=\"thread.php?cid=$cid&forum=$forumid&page=$page\">Forum Topics</a> &gt; Posts by Name</div>\n";
	
	$query = "SELECT name FROM imas_forums WHERE id='$forumid'";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$forumname = mysql_result($result,0,0);
	
	echo "<h2>Posts by Name - $forumname</h2>\n";
	
	$query = "SELECT imas_forum_posts.*,imas_users.FirstName,imas_users.LastName,imas_users.email from imas_forum_posts,imas_users ";
	$query .= "WHERE imas_forum_posts.userid=imas_users.id AND imas_forum_posts.forumid='$forumid' ORDER BY ";
	$query .= "imas_users.LastName,imas_users.FirstName,imas_forum_posts.postdate DESC";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$laststu = -1;
	while ($line =  mysql_fetch_array($result, MYSQL_ASSOC)) {
		if ($line['userid']!=$laststu) {
			if ($laststu!=-1) {
				echo '</ul>';
			}
			echo "<b>{$line['LastName']}, {$line['FirstName']}</b>";
			echo '<ul>';
			$laststu = $line['userid'];
		}
		echo '<li>';
		if ($line['parent']!=0) {
			echo '<span style="color:green;">';
		}
		echo $line['subject'];
		if ($line['parent']!=0) {
			echo '</span>';
		}
		echo '<br/>';
		$dt = tzdate("F j, Y, g:i a",$line['postdate']);
		echo '&nbsp; &nbsp; Posted: '.$dt;
		echo '</li>';
	}
	echo '</ul>';
	
	echo "<p>Color code<br/>Black: New thread</br><span style=\"color:green;\">Green: Reply</span></p>";
	
	echo "<p><a href=\"thread.php?cid=$cid&forum=$forumid&page={$_GET['page']}\">Back to Thread List</a></p>";
	
	require("../footer.php");
	
?>

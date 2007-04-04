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
	
	$placeinhead = "<style type=\"text/css\">\n@import url(\"$imasroot/forums/forums.css\");\n</style>\n";
	require("../header.php");
	echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"../course/course.php?cid=$cid\">$coursename</a> &gt; <a href=\"thread.php?cid=$cid&forum=$forumid&page=$page\">Forum Topics</a> &gt; Posts by Name</div>\n";
	
	$query = "SELECT name FROM imas_forums WHERE id='$forumid'";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$forumname = mysql_result($result,0,0);
	
	echo "<h2>Posts by Name - $forumname</h2>\n";
	
	echo "<script>\n";
	echo "function toggleshow(bnum) {\n";
	echo "   var node = document.getElementById('m'+bnum);\n";
	echo "   var butn = document.getElementById('butn'+bnum);\n";
	echo "   if (node.className == 'blockitems') {\n";
	echo "       node.className = 'hidden';\n";
	echo "       butn.value = '+';\n";
	echo "   } else { ";
	echo "       node.className = 'blockitems';\n";
	echo "       butn.value = '-';\n";
	echo "   }\n";
	echo "}\n";
	echo "</script>";
	
	$query = "SELECT imas_forum_posts.*,imas_users.FirstName,imas_users.LastName,imas_users.email from imas_forum_posts,imas_users ";
	$query .= "WHERE imas_forum_posts.userid=imas_users.id AND imas_forum_posts.forumid='$forumid' ORDER BY ";
	$query .= "imas_users.LastName,imas_users.FirstName,imas_forum_posts.postdate DESC";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$laststu = -1;
	$cnt = 0;
	while ($line =  mysql_fetch_array($result, MYSQL_ASSOC)) {
		if ($line['userid']!=$laststu) {
			if ($laststu!=-1) {
				echo '</div>';
			}
			echo "<b>{$line['LastName']}, {$line['FirstName']}</b>";
			echo '<div class="forumgrp">';
			$laststu = $line['userid'];
		}
		echo '<div class="block">';
		if ($line['parent']!=0) {
			echo '<span style="color:green;">';
		}
		echo "<input type=\"button\" value=\"+\" onclick=\"toggleshow($cnt)\" id=\"butn$cnt\" />";
		echo '<b>'.$line['subject'].'</b>';
		if ($line['parent']!=0) {
			echo '</span>';
		}
		$dt = tzdate("F j, Y, g:i a",$line['postdate']);
		echo ', Posted: '.$dt;
		echo '</div>';
		echo "<div id=\"m$cnt\" class=\"hidden\">".filter($line['message']).'</div>';
		$cnt++;
	}
	echo '</div>';
	
	echo "<p>Color code<br/>Black: New thread</br><span style=\"color:green;\">Green: Reply</span></p>";
	
	echo "<p><a href=\"thread.php?cid=$cid&forum=$forumid&page={$_GET['page']}\">Back to Thread List</a></p>";
	
	require("../footer.php");
	
?>

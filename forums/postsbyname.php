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
	if (isset($teacherid)) {
		$isteacher = true;	
	} else {
		$isteacher = false;
	}
	
	$forumid = $_GET['forum'];
	$cid = $_GET['cid'];
	
	if (isset($_GET['markallread'])) {
		$query = "SELECT DISTINCT threadid FROM imas_forum_posts WHERE forumid='$forumid'";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		$now = time();
		while ($row = mysql_fetch_row($result)) {
			$query = "UPDATE imas_forum_views SET lastview=$now WHERE userid='$userid' AND threadid='{$row[0]}'";
			mysql_query($query) or die("Query failed : $query " . mysql_error());
			if (mysql_affected_rows()==0) {
				$query = "INSERT INTO imas_forum_views (userid,threadid,lastview) VALUES ('$userid','{$row[0]}',$now)";
				mysql_query($query) or die("Query failed : $query " . mysql_error());
			}
		}
	}
	
	$query = "SELECT settings,replyby,defdisplay,name,points FROM imas_forums WHERE id='$forumid'";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$forumsettings = mysql_result($result,0,0);
	$allowreply = ($isteacher || (time()<mysql_result($result,0,1)));
	$allowanon = (($forumsettings&1)==1);
	$allowmod = ($isteacher || (($forumsettings&2)==2));
	$allowdel = ($isteacher || (($forumsettings&4)==4));
	$haspoints = (mysql_result($result,0,4)>0);
	
	$caller = "byname";
	include("posthandler.php");
	
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
	echo "function toggleshowall() {\n";
	echo "  for (var i=0; i<bcnt; i++) {";
	echo "    var node = document.getElementById('m'+i);\n";
	echo "    var butn = document.getElementById('butn'+i);\n";
	echo "    node.className = 'blockitems';\n";
	echo "    butn.value = '-';\n";
	echo "  }\n";
	echo "  document.getElementById(\"toggleall\").value = 'Collapse All';";
	echo "  document.getElementById(\"toggleall\").onclick = togglecollapseall;";
	echo "}\n";
	echo "function togglecollapseall() {\n";
	echo "  for (var i=0; i<bcnt; i++) {";
	echo "    var node = document.getElementById('m'+i);\n";
	echo "    var butn = document.getElementById('butn'+i);\n";
	echo "    node.className = 'hidden';\n";
	echo "    butn.value = '+';\n";
	echo "  }\n";
	echo "  document.getElementById(\"toggleall\").value = 'Expand All';";
	echo "  document.getElementById(\"toggleall\").onclick = toggleshowall;";
	echo "}\n";
	echo "</script>";
	
	$query = "SELECT imas_forum_posts.*,imas_users.FirstName,imas_users.LastName,imas_users.email,ifv.lastview from imas_forum_posts JOIN imas_users ";
	$query .= "ON imas_forum_posts.userid=imas_users.id LEFT JOIN (SELECT threadid,lastview FROM imas_forum_views WHERE userid='$userid') AS ifv ON ";
	$query .= "ifv.threadid=imas_forum_posts.threadid WHERE imas_forum_posts.forumid='$forumid' ORDER BY ";
	$query .= "imas_users.LastName,imas_users.FirstName,imas_forum_posts.postdate DESC";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$laststu = -1;
	$cnt = 0;
	echo "<input type=\"button\" value=\"Expand All\" onclick=\"toggleshowall()\" id=\"toggleall\"/> ";
	echo "<a href=\"postsbyname.php?cid=$cid&forum=$forumid&markallread=true\">Mark all Read</a><br/>";
	
	if ($isteacher && $haspoints) {
		echo "<form method=post action=\"thread.php?cid=$cid&forum=$forumid&page=$page&score=true\">";
	}
	
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
		
		echo '<span class="right">';
		if ($haspoints) {
			if ($isteacher) {
				echo "<input type=text size=2 name=\"score[{$line['id']}]\" value=\"";
				if ($line['points']!=null) {
					echo $line['points'];
				}
				echo "\"/> Pts. ";
			} else if ($line['ownerid']==$userid && $line['points']!=null) {
				echo "<span class=red>{$points[$child]}</span> ";
			}
		}
		echo "<a href=\"posts.php?cid=$cid&forum=$forumid&thread={$line['threadid']}\">Thread</a> ";
		if ($isteacher || ($line['ownerid']==$userid && $allowmod)) {
			echo "<a href=\"postsbyname.php?cid=$cid&forum=$forumid&thread={$line['threadid']}&modify={$line['id']}\">Modify</a> \n";
		}
		if ($isteacher || ($allowdel && $line['ownerid']==$userid)) {
			echo "<a href=\"postsbyname.php?cid=$cid&forum=$forumid&thread={$line['threadid']}&remove={$line['id']}\">Remove</a> \n";
		}
		if ($line['posttype']!=2 && $myrights > 5 && $allowreply) {
			echo "<a href=\"postsbyname.php?cid=$cid&forum=$forumid&thread={$line['threadid']}&modify=reply&replyto={$line['id']}\">Reply</a>";
		}
		echo '</span>';
		echo "<input type=\"button\" value=\"+\" onclick=\"toggleshow($cnt)\" id=\"butn$cnt\" />";
		echo '<b>'.$line['subject'].'</b>';
		if ($line['parent']!=0) {
			echo '</span>';
		}
		$dt = tzdate("F j, Y, g:i a",$line['postdate']);
		echo ', Posted: '.$dt;
		if ($line['lastview']==null || $line['postdate']>$line['lastview']) {
			echo " <span style=\"color:red;\">New</span>\n";
		}
		echo '</div>';
		echo "<div id=\"m$cnt\" class=\"hidden\">".filter($line['message']).'</div>';
		$cnt++;
	}
	echo '</div>';
	echo "<script>var bcnt = $cnt;</script>";
	if ($isteacher && $haspoints) {
		echo "<div><input type=submit value=\"Save Grades\" /></div>";
		echo "</form>";
	}
	
	echo "<p>Color code<br/>Black: New thread</br><span style=\"color:green;\">Green: Reply</span></p>";
	
	echo "<p><a href=\"thread.php?cid=$cid&forum=$forumid&page={$_GET['page']}\">Back to Thread List</a></p>";
	
	require("../footer.php");
	
?>

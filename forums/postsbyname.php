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
			$query = "SELECT id FROM imas_forum_views WHERE userid='$userid' AND threadid='{$row[0]}'";
			$r2 = mysql_query($query) or die("Query failed : $query " . mysql_error());
			if (mysql_num_rows($r2)>0) {
				$r2id = mysql_result($r2,0,0);
				$query = "UPDATE imas_forum_views SET lastview=$now WHERE id='$r2id'";
				mysql_query($query) or die("Query failed : $query " . mysql_error());
			} else{
				$query = "INSERT INTO imas_forum_views (userid,threadid,lastview) VALUES ('$userid','{$row[0]}',$now)";
				mysql_query($query) or die("Query failed : $query " . mysql_error());
			}
		}
	}
	
	$query = "SELECT settings,replyby,defdisplay,name,points,rubric FROM imas_forums WHERE id='$forumid'";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$forumsettings = mysql_result($result,0,0);
	$allowreply = ($isteacher || (time()<mysql_result($result,0,1)));
	$allowanon = (($forumsettings&1)==1);
	$allowmod = ($isteacher || (($forumsettings&2)==2));
	$allowdel = ($isteacher || (($forumsettings&4)==4));
	$pointspos = mysql_result($result,0,4);
	$haspoints = ($pointspos>0);
	$rubric = mysql_result($result,0,5);
	
	$caller = "byname";
	include("posthandler.php");
	
	$placeinhead = '<link rel="stylesheet" href="'.$imasroot.'/forums/forums.css?ver=082911" type="text/css" />';
	if ($haspoints && $rubric != 0) {
		$placeinhead .= '<script type="text/javascript" src="'.$imasroot.'/javascript/rubric.js?v=120311"></script>';
		require("../includes/rubric.php");
	}
	require("../header.php");
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">$coursename</a> &gt; <a href=\"thread.php?cid=$cid&forum=$forumid&page=$page\">Forum Topics</a> &gt; Posts by Name</div>\n";
	
	$query = "SELECT name FROM imas_forums WHERE id='$forumid'";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$forumname = mysql_result($result,0,0);
	
	echo '<div id="headerpostsbyname" class="pagetitle">';
	echo "<h2>Posts by Name - $forumname</h2>\n";
	echo '</div>';
?>
	<script type="text/javascript">
	function toggleshow(bnum) {
	   var node = document.getElementById('m'+bnum);
	   var butn = document.getElementById('butn'+bnum);
	   if (node.className == 'blockitems') {
	       node.className = 'hidden';
	       butn.value = '+';
	   } else { 
	       node.className = 'blockitems';
	       butn.value = '-';
	   }
	}
	function toggleshowall() {
	  for (var i=0; i<bcnt; i++) {
	    var node = document.getElementById('m'+i);
	    var butn = document.getElementById('butn'+i);
	    node.className = 'blockitems';
	    butn.value = '-';
	  }
	  document.getElementById("toggleall").value = 'Collapse All';
	  document.getElementById("toggleall").onclick = togglecollapseall;
	}
	function onsubmittoggle() {
		for (var i=0; i<bcnt; i++) {
		    var node = document.getElementById('m'+i);
		    node.className = 'pseudohidden';
		  }	
	}
	function togglecollapseall() {
	  for (var i=0; i<bcnt; i++) {
	    var node = document.getElementById('m'+i);
	    var butn = document.getElementById('butn'+i);
	    node.className = 'hidden';
	    butn.value = '+';
	  }
	  document.getElementById("toggleall").value = 'Expand All';
	  document.getElementById("toggleall").onclick = toggleshowall;
	}
	function onarrow(e,field) {
		if (window.event) {
			var key = window.event.keyCode;
		} else if (e.which) {
			var key = e.which;
		}
		
		if (key==40 || key==38) {
			var i;
			for (i = 0; i < field.form.elements.length; i++)
			   if (field == field.form.elements[i])
			       break;
			
		      if (key==38) {
			      i = i-2;
			      if (i<0) { i=0;}
		      } else {
			      i = (i + 2) % field.form.elements.length;
		      }
		      if (field.form.elements[i].type=='text') {
			      field.form.elements[i].focus();
		      }
		      return false;
		} else {
			return true;
		}
	}
	function onenter(e,field) {
		if (window.event) {
			var key = window.event.keyCode;
		} else if (e.which) {
			var key = e.which;
		}
		if (key==13) {
			var i;
			for (i = 0; i < field.form.elements.length; i++)
			   if (field == field.form.elements[i])
			       break;
		      i = (i + 2) % field.form.elements.length;
		      field.form.elements[i].focus();
		      return false;
		} else {
			return true;
		}
	}
	</script>
<?php

	if ($haspoints && $rubric != 0) {
		$query = "SELECT id,rubrictype,rubric FROM imas_rubrics WHERE id=$rubric";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		if (mysql_num_rows($result)>0) {
			$row = mysql_fetch_row($result);
			echo printrubrics(array($row));
		}
	}
	
	$scores = array();
	$feedback = array();
	if ($haspoints) {
		$query = "SELECT refid,score,feedback FROM imas_grades WHERE gradetype='forum' AND gradetypeid='$forumid'";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			$scores[$row[0]] = $row[1];
			$feedback[$row[0]] = $row[2];
		}
	}
	$query = "SELECT imas_forum_posts.*,imas_users.FirstName,imas_users.LastName,imas_users.email,ifv.lastview from imas_forum_posts JOIN imas_users ";
	$query .= "ON imas_forum_posts.userid=imas_users.id LEFT JOIN (SELECT DISTINCT threadid,lastview FROM imas_forum_views WHERE userid='$userid') AS ifv ON ";
	$query .= "ifv.threadid=imas_forum_posts.threadid WHERE imas_forum_posts.forumid='$forumid' AND imas_forum_posts.isanon=0 ORDER BY ";
	$query .= "imas_users.LastName,imas_users.FirstName,imas_forum_posts.postdate DESC";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$laststu = -1;
	$cnt = 0;
	echo "<input type=\"button\" value=\"Expand All\" onclick=\"toggleshowall()\" id=\"toggleall\"/> ";
	echo "<a href=\"postsbyname.php?cid=$cid&forum=$forumid&markallread=true\">Mark all Read</a><br/>";
	
	if ($isteacher && $haspoints) {
		echo "<form method=post action=\"thread.php?cid=$cid&forum=$forumid&page=$page&score=true\" onsubmit=\"onsubmittoggle()\">";
	}
	$curdir = rtrim(dirname(__FILE__), '/\\');
	while ($line =  mysql_fetch_array($result, MYSQL_ASSOC)) {
		if ($line['userid']!=$laststu) {
			if ($laststu!=-1) {
				echo '</div>';
			}
			echo "<b>{$line['LastName']}, {$line['FirstName']}</b>";
			if (file_exists("$curdir/../course/files/userimg_sm{$line['userid']}.jpg")) {
				echo "<img src=\"$imasroot/course/files/userimg_sm{$line['userid']}.jpg\" onclick=\"togglepic(this)\"/>";
			}
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
				echo "<input type=text size=2 name=\"score[{$line['id']}]\" id=\"score{$line['id']}\" onkeypress=\"return onenter(event,this)\" onkeyup=\"onarrow(event,this)\" value=\"";
				if (isset($scores[$line['id']])) {
					echo $scores[$line['id']];
				}
				
				echo "\"/> Pts ";
				if ($rubric != 0) {
					echo printrubriclink($rubric,$pointspos,"score{$line['id']}", "feedback{$line['id']}").' ';
				}
			} else if ($line['ownerid']==$userid && isset($scores[$line['id']])) {
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
		echo "<div id=\"m$cnt\" class=\"hidden\">".filter($line['message']);
		if ($haspoints) {
			if ($isteacher && $ownerid[$child]!=$userid) {
				echo '<hr/>';
				echo "Private Feedback: <textarea cols=\"50\" rows=\"2\" name=\"feedback[{$line['id']}]\" id=\"feedback{$line['id']}\">";
				if ($feedback[$line['id']]!==null) {
					echo $feedback[$line['id']];
				}
				echo "</textarea>";
			} else if ($ownerid[$child]==$userid && $feedback[$line['id']]!=null) {
				echo '<div class="signup">Private Feedback: ';
				echo $feedback[$line['id']];
				echo '</div>';
			}
		}
		echo '</div>';
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

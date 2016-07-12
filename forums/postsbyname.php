<?php
	//Lists forum posts by Student name
	//(c) 2006 David Lippman
	
	require("../validate.php");
	/*if (!isset($teacherid) && !isset($tutorid)) {
	   require("../header.php");
	   echo "You must be a teacher to access this page\n";
	   require("../footer.php");
	   exit;
	}*/
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
	
	$query = "SELECT settings,replyby,defdisplay,name,points,rubric,tutoredit, groupsetid FROM imas_forums WHERE id='$forumid'";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	list($forumsettings, $replyby, $defdisplay, $forumname, $pointspos, $rubric, $tutoredit, $groupsetid) = mysql_fetch_row($result);
	$allowanon = (($forumsettings&1)==1);
	$allowmod = ($isteacher || (($forumsettings&2)==2));
	$allowdel = ($isteacher || (($forumsettings&4)==4));
	$postbeforeview = (($forumsettings&16)==16);
	$haspoints = ($pointspos>0);
	
	$canviewall = (isset($teacherid) || isset($tutorid));
	$caneditscore = (isset($teacherid) || (isset($tutorid) && $tutoredit==1));
	$canviewscore = (isset($teacherid) || (isset($tutorid) && $tutoredit<2));
	$allowreply = ($canviewall || (time()<$replyby));
	
	$caller = "byname";
	include("posthandler.php");
	
	$placeinhead = '<link rel="stylesheet" href="'.$imasroot.'/forums/forums.css?ver=082911" type="text/css" />';
	if ($haspoints && $caneditscore && $rubric != 0) {
		$placeinhead .= '<script type="text/javascript" src="'.$imasroot.'/javascript/rubric.js?v=120311"></script>';
		require("../includes/rubric.php");
	}
	require("../header.php");
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">$coursename</a> &gt; <a href=\"thread.php?cid=$cid&forum=$forumid&page=$page\">Forum Topics</a> &gt; Posts by Name</div>\n";
	
	echo '<div id="headerpostsbyname" class="pagetitle">';
	echo "<h2>Posts by Name - $forumname</h2>\n";
	echo '</div>';
	if (!$canviewall && $postbeforeview) {
		$query = "SELECT id FROM imas_forum_posts WHERE forumid='$forumid' AND parent=0 AND userid='$userid' LIMIT 1";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		if (mysql_num_rows($result)==0) {
			echo '<p>This page is blocked. In this forum, you must post your own thread before you can read those posted by others.</p>';
			require("../footer.php");
			exit;
		}
	}
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

	if ($haspoints && $caneditscore && $rubric != 0) {
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
	$dofilter = false;
	if (!$canviewall && $groupsetid>0) {
		$query = 'SELECT i_sg.id FROM imas_stugroups AS i_sg JOIN imas_stugroupmembers as i_sgm ON i_sgm.stugroupid=i_sg.id ';
		$query .= "WHERE i_sgm.userid='$userid' AND i_sg.groupsetid='$groupsetid'";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		if (mysql_num_rows($result)>0) {
			$groupid = mysql_result($result,0,0);
		} else {
			$groupid=0;
		}
		$dofilter = true;
		$query = "SELECT id FROM imas_forum_threads WHERE (stugroupid=0 OR stugroupid='$groupid') AND forumid='$forumid'";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		$limthreads = array();
		while ($row = mysql_fetch_row($result)) {
			$limthreads[] = $row[0];
		}
		if (count($limthreads)==0) {
			$limthreads = '0';
		} else {
			$limthreads = implode(',',$limthreads);
		}
	}
	
	$query = "SELECT imas_forum_posts.*,imas_users.FirstName,imas_users.LastName,imas_users.email,imas_users.hasuserimg,ifv.lastview from imas_forum_posts JOIN imas_users ";
	$query .= "ON imas_forum_posts.userid=imas_users.id LEFT JOIN (SELECT DISTINCT threadid,lastview FROM imas_forum_views WHERE userid='$userid') AS ifv ON ";
	$query .= "ifv.threadid=imas_forum_posts.threadid WHERE imas_forum_posts.forumid='$forumid' AND imas_forum_posts.isanon=0 ";
	if ($dofilter) {
		$query .= "AND imas_forum_posts.threadid IN ($limthreads) ";
	}
	$query .= "ORDER BY imas_users.LastName,imas_users.FirstName,imas_forum_posts.postdate DESC";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$laststu = -1;
	$cnt = 0;
	echo "<input type=\"button\" value=\"Expand All\" onclick=\"toggleshowall()\" id=\"toggleall\"/> ";

	echo "<button type=\"button\" onclick=\"window.location.href='postsbyname.php?cid=$cid&forum=$forumid&markallread=true'\">"._('Mark all Read')."</button><br/>";
	
	if ($caneditscore && $haspoints) {
		echo "<form method=post action=\"thread.php?cid=$cid&forum=$forumid&page=$page&score=true\" onsubmit=\"onsubmittoggle()\">";
	}
	$curdir = rtrim(dirname(__FILE__), '/\\');
	function printuserposts($name, $uid, $content, $postcnt, $replycnt) {
		echo "<b>$name</b> (";
		echo $postcnt.($postcnt==1?' post':' posts').', '.$replycnt. ($replycnt==1?' reply':' replies').')';
		if ($line['hasuserimg']==1) {
			if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
				echo "<img src=\"{$urlmode}s3.amazonaws.com/{$GLOBALS['AWSbucket']}/cfiles/userimg_sm$uid.jpg\"  onclick=\"togglepic(this)\"  />";
			} else {
				echo "<img src=\"$imasroot/course/files/userimg_sm$uid.jpg\"  onclick=\"togglepic(this)\" />";
			}
		}
		echo '<div class="forumgrp">'.$content.'</div>';
	}
	$content = ''; $postcnt = 0; $replycnt = 0; $lastname = '';
	while ($line =  mysql_fetch_array($result, MYSQL_ASSOC)) {
		if ($line['userid']!=$laststu) {
			if ($laststu!=-1) {
				printuserposts($lastname, $laststu, $content, $postcnt, $replycnt);
				$content = '';  $postcnt = 0; $replycnt = 0;
			}
			$laststu = $line['userid'];
			$lastname = "{$line['LastName']}, {$line['FirstName']}";
		}
		$content .= '<div class="block">';
		if ($line['parent']!=0) {
			$content .= '<span style="color:green;">';
			$replycnt++;
		} else {
			$postcnt++;
		}
		
		$content .= '<span class="right">';
		if ($haspoints) {
			if ($caneditscore) {
				$content .= "<input type=text size=2 name=\"score[{$line['id']}]\" id=\"score{$line['id']}\" onkeypress=\"return onenter(event,this)\" onkeyup=\"onarrow(event,this)\" value=\"";
				if (isset($scores[$line['id']])) {
					$content .= $scores[$line['id']];
				}
				
				$content .= "\"/> Pts ";
				if ($rubric != 0) {
					$content .= printrubriclink($rubric,$pointspos,"score{$line['id']}", "feedback{$line['id']}").' ';
				}
			} else if (($line['ownerid']==$userid || $canviewscore) && isset($scores[$line['id']])) {
				$content .= "<span class=red>{$scores[$line['id']]} pts</span> ";
			}
		}
		$content .= "<a href=\"posts.php?cid=$cid&forum=$forumid&thread={$line['threadid']}\">Thread</a> ";
		if ($isteacher || ($line['ownerid']==$userid && $allowmod)) {
			$content .= "<a href=\"postsbyname.php?cid=$cid&forum=$forumid&thread={$line['threadid']}&modify={$line['id']}\">Modify</a> \n";
		}
		if ($isteacher || ($allowdel && $line['ownerid']==$userid)) {
			$content .= "<a href=\"postsbyname.php?cid=$cid&forum=$forumid&thread={$line['threadid']}&remove={$line['id']}\">Remove</a> \n";
		}
		if ($line['posttype']!=2 && $myrights > 5 && $allowreply) {
			$content .= "<a href=\"postsbyname.php?cid=$cid&forum=$forumid&thread={$line['threadid']}&modify=reply&replyto={$line['id']}\">Reply</a>";
		}
		$content .= '</span>';
		$content .= "<input type=\"button\" value=\"+\" onclick=\"toggleshow($cnt)\" id=\"butn$cnt\" />";
		$content .= '<b>'.$line['subject'].'</b>';
		if ($line['parent']!=0) {
			$content .= '</span>';
		}
		$dt = tzdate("F j, Y, g:i a",$line['postdate']);
		$content .= ', Posted: '.$dt;
		if ($line['lastview']==null || $line['postdate']>$line['lastview']) {
			$content .= " <span style=\"color:red;\">New</span>\n";
		}
		$content .= '</div>';
		$content .= "<div id=\"m$cnt\" class=\"hidden\">".filter($line['message']);
		if ($haspoints) {
			if ($caneditscore && $ownerid[$child]!=$userid) {
				$content .= '<hr/>';
				$content .= "Private Feedback: <textarea cols=\"50\" rows=\"2\" name=\"feedback[{$line['id']}]\" id=\"feedback{$line['id']}\">";
				if ($feedback[$line['id']]!==null) {
					$content .= $feedback[$line['id']];
				}
				$content .= "</textarea>";
			} else if (($ownerid[$child]==$userid || $canviewscore) && $feedback[$line['id']]!=null) {
				$content .= '<div class="signup">Private Feedback: ';
				$content .= $feedback[$line['id']];
				$content .= '</div>';
			}
		}
		$content .= '</div>';
		$cnt++;
	}
	printuserposts($lastname, $laststu, $content, $postcnt, $replycnt);
	echo "<script>var bcnt = $cnt;</script>";
	if ($caneditscore && $haspoints) {
		echo "<div><input type=submit value=\"Save Grades\" /></div>";
		echo "</form>";
	}
	
	echo "<p>Color code<br/>Black: New thread</br><span style=\"color:green;\">Green: Reply</span></p>";
	
	echo "<p><a href=\"thread.php?cid=$cid&forum=$forumid&page={$_GET['page']}\">Back to Thread List</a></p>";
	
	require("../footer.php");
	
?>

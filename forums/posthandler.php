<?php
//IMathAS:  include with posts.php and postsbyname.php for handling deletes, replies, etc.
//(c) 2006 David Lippman
if (!isset($caller)) {
	exit;
}
if ($caller=="posts") {
	$returnurl = "posts.php?view=$view&cid=$cid&page=$page&forum=$forumid&thread=$threadid";
	$returnname = "Posts";
} else if ($caller=="byname") {
	$threadid = $_GET['thread'];
	$returnurl = "postsbyname.php?cid=$cid&forum=$forumid&thread=$threadid";
	$returnname = "Posts by Name";
	
}

if (isset($_GET['modify'])) { //adding or modifying post
	if ($threadid==0) {
		echo "I don't know what thread you're replying to.  Please go back and try again.";
	}
	if (isset($_POST['subject'])) {  //form submitted
		if (isset($_POST['postanon']) && $_POST['postanon']==1) {
			$isanon = 1;
		} else {
			$isanon = 0;
		}
		require_once("../includes/htmLawed.php");
		$htmlawedconfig = array('elements'=>'*-script-form');
		$_POST['message'] = addslashes(htmLawed(stripslashes($_POST['message']),$htmlawedconfig));
		$_POST['subject'] = strip_tags($_POST['subject']);
		
		if ($_GET['modify']=="reply") {
			$now = time();
			$query = "INSERT INTO imas_forum_posts (forumid,threadid,subject,message,userid,postdate,parent,posttype,isanon) VALUES ";
			$query .= "('$forumid','$threadid','{$_POST['subject']}','{$_POST['message']}','$userid',$now,'{$_GET['replyto']}',0,'$isanon')";
			mysql_query($query) or die("Query failed : $query " . mysql_error());
			
			$query = "UPDATE imas_forum_threads SET lastposttime=$now,lastpostuser='$userid' WHERE id='$threadid'";
			mysql_query($query) or die("Query failed : $query " . mysql_error());
			
			if ($isteacher && isset($_POST['points']) && trim($_POST['points'])!='') {
				$query = "SELECT id FROM imas_grades WHERE gradetype='forum' AND refid='{$_GET['replyto']}'";
				$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
				if (mysql_num_rows($result)>0) {
					$gradeid = mysql_result($result,0,0);
					$query = "UPDATE imas_grades SET score='{$_POST['points']}' WHERE id=$gradeid";
					//$query = "UPDATE imas_forum_posts SET points='{$_POST['points']}' WHERE id='{$_GET['replyto']}'";
					mysql_query($query) or die("Query failed : $query " . mysql_error());
				} else {
					$query = "SELECT userid FROM imas_forum_posts WHERE id='{$_GET['replyto']}'";
					$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
					$uid = mysql_result($result,0,0);
					$query = "INSERT INTO imas_grades (gradetype,gradetypeid,userid,refid,score) VALUES ";
					$query .= "('forum','$forumid','$uid','{$_GET['replyto']}','{$_POST['points']}')";
					mysql_query($query) or die("Query failed : $query " . mysql_error());
				}
			}
			
			$query = "SELECT iu.email FROM imas_users AS iu,imas_forum_subscriptions AS ifs WHERE ";
			$query .= "iu.id=ifs.userid AND ifs.forumid='$forumid' AND iu.id<>'$userid'";
			$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			if (mysql_num_rows($result)>0) {
				$headers  = 'MIME-Version: 1.0' . "\r\n";
				$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
				$headers .= "From: $sendfrom\r\n";
				$message  = "<h4>This is an automated message.  Do not respond to this email</h4>\r\n";
				$message .= "<p>A new post has been started in forum $forumname in course $coursename</p>\r\n";
				$message .= "<p>Subject:".stripslashes($_POST['subject'])."</p>";
				$message .= "<p>Poster: $userfullname</p>";
				$message .= "<a href=\"" . $urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/$returnurl\">";
				$message .= "View Posting</a>\r\n";
			}
			while ($row = mysql_fetch_row($result)) {
				mail($row[0],'New forum post notification',$message,$headers);
			}
			
		} else {
			$query = "UPDATE imas_forum_posts SET subject='{$_POST['subject']}',message='{$_POST['message']}',isanon='$isanon' ";
			$query .= "WHERE id='{$_GET['modify']}'";
			if (!$isteacher) { $query .= " AND userid='$userid'";}
			mysql_query($query) or die("Query failed : $query " . mysql_error());
		}
		header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/$returnurl");
		exit;
	} else { //display mod
		$pagetitle = "Add/Modify Post";
		$useeditor = "message";
		require("../header.php");
		echo "<div class=breadcrumb>$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">$coursename</a> ";
		echo "&gt; <a href=\"thread.php?page=$page&cid=$cid&forum=$forumid\">Forum Topics</a> &gt; ";
		echo "<a href=\"$returnurl\">$returnname</a> &gt; ";
		if ($_GET['modify']!="reply") {
			echo "Modify Posting</div>\n";
			$query = "SELECT * from imas_forum_posts WHERE id='{$_GET['modify']}'";
			$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			$line = mysql_fetch_array($result, MYSQL_ASSOC);
			echo '<div id="headerposthandler" class="pagetitle"><h2>Modify Post</h2></div>';
		} else {
			echo "Post Reply</div>\n";
			//$query = "SELECT subject,points FROM imas_forum_posts WHERE id='{$_GET['replyto']}'";
			$query = "SELECT ifp.subject,ig.score FROM imas_forum_posts AS ifp LEFT JOIN imas_grades AS ig ON ";
			$query .= "ig.gradetype='forum' AND ifp.id=ig.refid WHERE ifp.id='{$_GET['replyto']}'";
			$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			$sub = mysql_result($result,0,0);
			$sub = str_replace('"','&quot;',$sub);
			$line['subject'] = "Re: $sub";
			$line['message'] = "";
			$points = mysql_result($result,0,1);
			if ($isteacher) {
				$query = "SELECT points FROM imas_forums WHERE id='$forumid'";
				$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
				$haspoints = (mysql_result($result,0,0)>0);
			}
			echo '<div id="headerposthandler" class="pagetitle"><h2>Post Reply</h2></div>';
		}
		echo "<form method=post action=\"$returnurl&modify={$_GET['modify']}&replyto={$_GET['replyto']}\">\n";
		echo "<span class=form><label for=\"subject\">Subject:</label></span>";
		echo "<span class=formright><input type=text size=50 name=subject id=subject value=\"{$line['subject']}\"></span><br class=form>\n";
		echo "<span class=form><label for=\"message\">Message:</label></span>";
		echo "<span class=left><div class=editor><textarea id=message name=message style=\"width: 100%;\" rows=20 cols=70>";
		echo htmlentities($line['message']);
		echo "</textarea></div></span><br class=form>\n";
		if ($allowanon==1) {
			echo "<span class=form>Post Anonymously:</span><span class=formright>";
			echo "<input type=checkbox name=\"postanon\" value=1 ";
			if ($line['isanon']==1) {echo "checked=1";}
			echo "></span><br class=form/>";
		}
		if ($isteacher && $haspoints && $_GET['modify']=='reply') {
			echo '<span class="form">Points for message you\'re replying to:</span><span class="formright">';
			echo '<input type="text" size="4" name="points" value="'.$points.'" /></span><br class="form" />';
		}
		echo "<div class=submit><input type=submit value='Submit'></div>\n";

		if ($_GET['modify']=='reply') {
			echo "<p>Replying to:</p>";
			$query = "SELECT imas_forum_posts.*,imas_users.FirstName,imas_users.LastName from imas_forum_posts,imas_users ";
			$query .= "WHERE imas_forum_posts.userid=imas_users.id AND (imas_forum_posts.id='$threadid' OR imas_forum_posts.threadid='$threadid')";
			$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			while ($line =  mysql_fetch_array($result, MYSQL_ASSOC)) {
				$parent[$line['id']] = $line['parent'];
				$date[$line['id']] = $line['postdate'];
				$subject[$line['id']] = $line['subject'];
				if ($sessiondata['graphdisp']==0) {
					$line['message'] = preg_replace('/<embed[^>]*alt="([^"]*)"[^>]*>/',"[$1]", $line['message']);
				}
				$message[$line['id']] = $line['message'];
				$posttype[$line['id']] = $line['posttype'];
				if ($line['isanon']==1) {
					$poster[$line['id']] = "Anonymous";	
				} else {
					$poster[$line['id']] = $line['FirstName'] . ' ' . $line['LastName'];
					if ($isteacher && $line['userid']!=$userid) {
						$poster[$line['id']] .= " <a class=\"small\" href=\"$imasroot/course/gradebook.php?cid=$cid&stu={$line['userid']}\" target=\"_popoutgradebook\">[GB]</a>";
					}
				}
			}
			function printparents($id) {
				global $parent,$date,$subject,$message,$posttype,$poster;
				echo "<div class=block>";
				echo "<b>{$subject[$id]}</b><br/>Posted by: {$poster[$id]}, ";
				echo tzdate("F j, Y, g:i a",$date[$id]);
				echo "</div><div class=blockitems>";
				echo filter($message[$id]);
				echo "</div>\n";
				if ($parent[$id]!=0) {
					printparents($parent[$id]);
				}
			}
			printparents($_GET['replyto']);
		}
		echo '</form>';
		require("../footer.php");
		exit;
	}
} else if (isset($_GET['remove']) && $allowdel) {// $isteacher) { //removing post
	if (isset($_GET['confirm'])) {
		$go = true;
		if (!$isteacher) {
			$query = "SELECT id FROM imas_forum_posts WHERE parent='{$_GET['remove']}'";
			$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			if (mysql_num_rows($result)>0) {
				$go = false;
			}
		} 
		if ($go) {
			$query = "SELECT parent FROM imas_forum_posts WHERE id='{$_GET['remove']}'";
			$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			$parent = mysql_result($result,0,0);
			if ($parent==0) {
				$query = "DELETE FROM imas_forum_posts WHERE threadid='{$_GET['remove']}'";
				mysql_query($query) or die("Query failed : $query " . mysql_error());
				
				$query = "DELETE FROM imas_forum_threads WHERE id='{$_GET['remove']}'";
				mysql_query($query) or die("Query failed : $query " . mysql_error());
				
				$query = "DELETE FROM imas_forum_views WHERE threadid='{$_GET['remove']}'";
				mysql_query($query) or die("Query failed : $query " . mysql_error());
				$lastpost = true;
			} else {
				$query = "DELETE FROM imas_forum_posts WHERE id='{$_GET['remove']}'";
				mysql_query($query) or die("Query failed : $query " . mysql_error());
				
				$query = "UPDATE imas_forum_posts SET parent='$parent' WHERE parent='{$_GET['remove']}'";
				mysql_query($query) or die("Query failed : $query " . mysql_error());
				$lastpost = false;	
			}
		}
		if ($caller == "posts" && $lastpost) {
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/thread.php?page=$page&cid=$cid&forum=$forumid");
		} else {
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/$returnurl");
		}
		exit;
	} else {
		$pagetitle = "Remove Post";
		$query = "SELECT parent FROM imas_forum_posts WHERE id='{$_GET['remove']}'";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		$parent = mysql_result($result,0,0);
		require("../header.php");
		if (!$isteacher) {
			$query = "SELECT id FROM imas_forum_posts WHERE parent='{$_GET['remove']}'";
			$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			if (mysql_num_rows($result)>0) {
				echo "Someone has replied to this post, so you cannot remove it.  <a href=\"$returnurl\">Back</a>";
				require("../footer.php");
				exit;
			}
		} 
		echo "<div class=breadcrumb>$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">$coursename</a> ";
		echo "&gt; <a href=\"thread.php?page=$page&cid=$cid&forum=$forumid\">Forum Topics</a> &gt; ";
		echo "<a href=\"$returnurl\">$returnname</a> &gt; Remove Post</div>";
		
		echo "<h3>Remove Post</h3>\n";
		if ($parent==0) {
			echo "<p>Are you SURE you want to remove this thread and all replies?</p>\n";
		} else {
			echo "<p>Are you SURE you want to remove this post?</p>\n";
		}
		
		echo "<p><input type=button value=\"Yes, Remove\" onClick=\"window.location='$returnurl&remove={$_GET['remove']}&confirm=true'\">\n";
		echo "<input type=button value=\"Nevermind\" onClick=\"window.location='$returnurl'\"></p>\n";
		require("../footer.php");
		exit;
	}
} else if (isset($_GET['move']) && $isteacher) { //moving post to a different forum   NEW ONE
	if (isset($_POST['movetype'])) {
		$query = "SELECT * FROM imas_forum_posts WHERE threadid='$threadid'";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		while ($line =  mysql_fetch_array($result, MYSQL_ASSOC)) {
			$children[$line['parent']][] = $line['id'];
		}
		$tochange = array();
		
		function addchildren($b,&$tochange,$children) {
			if (count($children[$b])>0) {
				foreach ($children[$b] as $child) {
					$tochange[] = $child;
					if (isset($children[$child]) && count($children[$child])>0) {
						addchildren($child,$tochange,$children);
					}
				}
			}
		}
		addchildren($_GET['move'],$tochange,$children);
		$tochange[] = $_GET['move'];
		$list = "'".implode("','",$tochange)."'";
		
		if ($_POST['movetype']==0) { //move to different forum
			if ($children[0][0] == $_GET['move']) { //is post head of thread?
				//if head of thread, then :
				$query = "UPDATE imas_forum_posts SET forumid='{$_POST['movetof']}' WHERE threadid='{$_GET['move']}'";
				mysql_query($query) or die("Query failed : $query " . mysql_error());
				$query = "UPDATE imas_forum_threads SET forumid='{$_POST['movetof']}' WHERE id='{$_GET['move']}'";
				mysql_query($query) or die("Query failed : $query " . mysql_error());
			} else {
				//if not head of thread, need to create new thread, move items to new thread, then move forum
				$query = "SELECT lastposttime,lastpostuser FROM imas_forum_threads WHERE id='$threadid'";
				$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
				$row = mysql_fetch_row($result);
				//set all lower posts to new threadid and forumid
				$query = "UPDATE imas_forum_posts SET threadid='{$_GET['move']}',forumid='{$_POST['movetof']}' WHERE id IN ($list)";
				mysql_query($query) or die("Query failed : $query " . mysql_error());
				//set post to head of thread
				$query = "UPDATE imas_forum_posts SET parent=0 WHERE id='{$_GET['move']}'";
				mysql_query($query) or die("Query failed : $query " . mysql_error());
				//create new threads listing
				$query = "INSERT INTO imas_forum_threads (id,forumid,lastposttime,lastpostuser) VALUES ('{$_GET['move']}','{$_POST['movetof']}','{$row[0]}','{$row[1]}')";
				mysql_query($query) or die("Query failed : $query " . mysql_error());
			}
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/thread.php?page=$page&cid=$cid&forum=$forumid");
			exit;
		} else if ($_POST['movetype']==1) { //move to different thread
			if ($_POST['movetot'] != $threadid) {
				$query = "SELECT id FROM imas_forum_posts WHERE threadid='{$_POST['movetot']}' AND parent=0";
				$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
				$base = mysql_result($result,0,0);
				
				$query = "UPDATE imas_forum_posts SET threadid='{$_POST['movetot']}' WHERE id IN ($list)";
				$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
				
				$query = "UPDATE imas_forum_posts SET parent='$base' WHERE id='{$_GET['move']}'";
				$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
				if ($base != $_GET['move'] ) {//if not moving back to self, 
					//delete thread.  One will only exist if moved post was head of thread
					$query = "DELETE FROM imas_forum_threads WHERE id='{$_GET['move']}'";
					mysql_query($query) or die("Query failed : $query " . mysql_error());
				}
			}
			
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/thread.php?page=$page&cid=$cid&forum=$forumid");
			exit;
			
		}
	} else {
		$placeinhead .= '<script type="text/javascript">function toggleforumselect(v) { 
			if (v==0) {document.getElementById("fsel").style.display="block";document.getElementById("tsel").style.display="none";}
			if (v==1) {document.getElementById("tsel").style.display="block";document.getElementById("fsel").style.display="none";}
			}</script>';
		$pagetitle = "Move Thread";
		
		require("../header.php");
		echo "<div class=breadcrumb>$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">$coursename</a> ";
		echo "&gt; <a href=\"thread.php?page=$page&cid=$cid&forum=$forumid\">Forum Topics</a> &gt; ";
		echo "<a href=\"$returnurl\">$returnname</a> &gt; Move Thread</div>";
		$query = "SELECT parent FROM imas_forum_posts WHERE id='{$_GET['move']}'";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		if (mysql_result($result,0,0)==0) {
			$ishead = true;
		} else {
			$ishead = false;
		}
		echo "<h3>Move Thread</h3>\n";
		echo "<form method=post action=\"$returnurl&move={$_GET['move']}\">";
		echo "<p>What do you want to do?<br/>";
		if ($ishead) {
			echo '<input type="radio" name="movetype" value="0" checked="checked" onclick="toggleforumselect(0)"/> Move thread to different forum<br/>';
			echo '<input type="radio" name="movetype" value="1" onclick="toggleforumselect(1)"/> Move post to be a reply to a thread';
		} else {
			echo '<input type="radio" name="movetype" value="0" onclick="toggleforumselect(0)"/> Move post to be a new thread in this or another forum<br/>';
			echo '<input type="radio" name="movetype" value="1" checked="checked" onclick="toggleforumselect(1)"/> Move post to be a reply to a different thread';
		}
		echo '</p>';
		echo '<div id="fsel" ';
		if (!$ishead) {echo 'style="display:none;"';}
		echo '>Move to forum:<br/>';
		$query = "SELECT id,name FROM imas_forums WHERE courseid='$cid'";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			echo "<input type=\"radio\" name=\"movetof\" value=\"{$row[0]}\" ";
			if ($row[0]==$forumid) {echo 'checked="checked"';}
			echo "/>{$row[1]}<br/>";
		}
		echo '</div>';
		
		echo '<div id="tsel" ';
		if ($ishead) {echo 'style="display:none;"';}
		echo '>Move to thread:<br/>';
		$query = "SELECT threadid,subject FROM imas_forum_posts WHERE forumid='$forumid' AND parent=0 ORDER BY id DESC";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			echo "<input type=\"radio\" name=\"movetot\" value=\"{$row[0]}\" ";
			if ($row[0]==$threadid) {echo 'checked="checked"';}
			echo "/>{$row[1]}<br/>";
		}
		echo '</div>';
		
		echo "<p><input type=submit value=\"Move\">\n";
		echo "<input type=button value=\"Nevermind\" onClick=\"window.location='$returnurl'\"></p>\n";
		echo "</form>";
		require("../footer.php");
		exit;
		
	}
}
?>

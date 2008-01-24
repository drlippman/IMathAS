<?php
//IMathAS:  include with posts.php and postsbyname.php for handling deletes, replies, etc.
//(c) 2006 David Lippman
if (!isset($caller)) {
	exit;
}
if ($caller=="posts") {
	$returnurl = "posts.php?view=$view&cid=$cid&page=$page&forum=$forumid&thread=$threadid";
	$returnname = "Posts";
} else if ($caller="byname") {
	$returnurl = "postsbyname.php?cid=$cid&forum=$forumid";
	$returnname = "Posts by Name";
	$threadid = $_GET['thread'];
}
if (isset($_GET['modify'])) { //adding or modifying post
	if (isset($_POST['subject'])) {  //form submitted
		if (isset($_POST['postanon']) && $_POST['postanon']==1) {
			$isanon = 1;
		} else {
			$isanon = 0;
		}
		if ($_GET['modify']=="reply") {
			$now = time();
			$query = "INSERT INTO imas_forum_posts (forumid,threadid,subject,message,userid,postdate,parent,posttype,isanon) VALUES ";
			$query .= "('$forumid','$threadid','{$_POST['subject']}','{$_POST['message']}','$userid',$now,'{$_GET['replyto']}',0,'$isanon')";
			mysql_query($query) or die("Query failed : $query " . mysql_error());
			
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
				$message .= "<a href=\"http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/$returnurl\">";
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
		header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/$returnurl");
		exit;
	} else { //display mod
		$pagetitle = "Add/Modify Post";
		$useeditor = "message";
		require("../header.php");
		echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"../course/course.php?cid=$cid\">$coursename</a> ";
		echo "&gt; <a href=\"thread.php?page=$page&cid=$cid&forum=$forumid\">Forum Topics</a> &gt; ";
		echo "<a href=\"$returnurl\">$returnname</a> &gt; ";
		if ($_GET['modify']!="reply") {
			echo "Modify Posting</div>\n";
			$query = "SELECT * from imas_forum_posts WHERE id='{$_GET['modify']}'";
			$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			$line = mysql_fetch_array($result, MYSQL_ASSOC);
			echo "<h3>Modify Post</h3>\n";
		} else {
			echo "Post Reply</div>\n";
			$query = "SELECT subject FROM imas_forum_posts WHERE id='{$_GET['replyto']}'";
			$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			$sub = mysql_result($result,0,0);
			$sub = str_replace('"','&quot;',$sub);
			$line['subject'] = "Re: $sub";
			$line['message'] = "";
			echo "<h3>Post Reply</h3>\n";
		}
		echo "<form method=post action=\"$returnurl&modify={$_GET['modify']}&replyto={$_GET['replyto']}\">\n";
		echo "<span class=form><label for=\"subject\">Subject:</label></span>";
		echo "<span class=formright><input type=text size=50 name=subject id=subject value=\"{$line['subject']}\"></span><br class=form>\n";
		echo "<span class=form><label for=\"message\">Message:</label></span>";
		echo "<span class=left><div class=editor><textarea id=message name=message style=\"width: 100%;\" rows=20 cols=70>{$line['message']}</textarea></div></span><br class=form>\n";
		if ($allowanon==1) {
			echo "<span class=form>Post Anonymously:</span><span class=formright>";
			echo "<input type=checkbox name=\"postanon\" value=1 ";
			if ($line['isanon']==1) {echo "checked=1";}
			echo "></span><br class=form/>";
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
		require("../footer.php");
		exit;
	}
} else if (isset($_GET['remove']) && $isteacher) { //removing post
	if (isset($_GET['confirm'])) {
		$query = "SELECT parent FROM imas_forum_posts WHERE id='{$_GET['remove']}'";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		$parent = mysql_result($result,0,0);
		
		$query = "DELETE FROM imas_forum_posts WHERE id='{$_GET['remove']}'";
		mysql_query($query) or die("Query failed : $query " . mysql_error());
		
		$query = "UPDATE imas_forum_posts SET parent='$parent' WHERE parent='{$_GET['remove']}'";
		mysql_query($query) or die("Query failed : $query " . mysql_error());
		header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/$returnurl");
		exit;
	} else {
		$pagetitle = "Remove Post";
		require("../header.php");
		echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"../course/course.php?cid=$cid\">$coursename</a> ";
		echo "&gt; <a href=\"thread.php?page=$page&cid=$cid&forum=$forumid\">Forum Topics</a> &gt; ";
		echo "<a href=\"$returnurl\">$returnname</a> &gt; Remove Post</div>";
		
		echo "<h3>Remove Post</h3>\n";
		echo "<p>Are you SURE you want to remove this post?</p>\n";
		echo "<p><input type=button value=\"Yes, Remove\" onClick=\"window.location='$returnurl&remove={$_GET['remove']}&confirm=true'\">\n";
		echo "<input type=button value=\"Nevermind\" onClick=\"window.location='$returnurl'\"></p>\n";
		require("../footer.php");
		exit;
	}
}
?>

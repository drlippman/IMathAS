<?php
	//Displays forums posts
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
	
	$cid = $_GET['cid'];
	$forumid = $_GET['forum'];
	$threadid = $_GET['thread'];
	$page = $_GET['page'];
	
	$query = "SELECT settings,replyby,defdisplay,name FROM imas_forums WHERE id='$forumid'";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$forumsettings = mysql_result($result,0,0);
	$allowreply = ($isteacher || (time()<mysql_result($result,0,1)));
	$defdisplay = mysql_result($result,0,2);
	$allowanon = (($forumsettings&1)==1);
	$allowmod = ($isteacher || (($forumsettings&2)==2));
	$allowdel = ($isteacher || (($forumsettings&4)==4));
	$forumname = mysql_result($result,0,3);
	
		
	if (isset($_GET['view'])) {
		$view = $_GET['view'];
	} else {
		$view = $defdisplay;  //0: expanded, 1: collapsed, 2: condensed
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
					$message .= "<a href=\"http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/posts.php?cid=$cid&forum=$forumid&thread=$threadid\">";
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
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/posts.php?view=$view&cid=$cid&page=$page&forum=$forumid&thread=$threadid");
			exit;
		} else { //display mod
			$pagetitle = "Add/Modify Post";
			$useeditor = "message";
			require("../header.php");
			echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"../course/course.php?cid=$cid\">$coursename</a> ";
			echo "&gt; <a href=\"thread.php?page=$page&cid=$cid&forum=$forumid\">Forum Topics</a> &gt; ";
			echo "<a href=\"posts.php?view=$view&cid=$cid&forum=$forumid&page=$page&thread=$threadid\">Posts</a> &gt; ";
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
				$line['subject'] = "Re: $sub";
				$line['message'] = "";
				echo "<h3>Post Reply</h3>\n";
			}
			echo "<form method=post action=\"posts.php?view=$view&cid=$cid&forum=$forumid&page=$page&thread=$threadid&modify={$_GET['modify']}&replyto={$_GET['replyto']}\">\n";
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
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/posts.php?view=$view&cid=$cid&page=$page&forum=$forumid&thread=$threadid");
			exit;
		} else {
			$pagetitle = "Remove Post";
			require("../header.php");
			echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"../course/course.php?cid=$cid\">$coursename</a> ";
			echo "&gt; <a href=\"thread.php?page=$page&cid=$cid&forum=$forumid\">Forum Topics</a> &gt; ";
			echo "<a href=\"posts.php?view=$view&cid=$cid&forum=$forumid&page=$page&thread=$threadid\">Posts</a> &gt; Remove Post</div>";
			
			echo "<h3>Remove Post</h3>\n";
			echo "<p>Are you SURE you want to remove this post?</p>\n";
			echo "<p><input type=button value=\"Yes, Remove\" onClick=\"window.location='posts.php?view=$view&cid=$cid&forum=$forumid&page=$page&thread=$threadid&remove={$_GET['remove']}&confirm=true'\">\n";
			echo "<input type=button value=\"Nevermind\" onClick=\"window.location='posts.php?view=$view&cid=$cid&forum=$forumid&page=$page&thread=$threadid'\"></p>\n";
			require("../footer.php");
			exit;
		}
	}
	
	$pagetitle = "Posts";
	$placeinhead = "<style type=\"text/css\">\n@import url(\"$imasroot/forums/forums.css\");\n</style>\n";
	require("../header.php");
	
	$allowmsg = false;
	if (!$isteacher) {
		$query = "SELECT msgset FROM imas_courses WHERE id='$cid'";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		if (mysql_result($result,0,0)==0) {
			$allowmsg = true;
		} 
	}
		
	
	$query = "SELECT imas_forum_posts.*,imas_users.FirstName,imas_users.LastName,imas_users.email from imas_forum_posts,imas_users ";
	$query .= "WHERE imas_forum_posts.userid=imas_users.id AND (imas_forum_posts.id='$threadid' OR imas_forum_posts.threadid='$threadid') ORDER BY imas_forum_posts.id";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	while ($line =  mysql_fetch_array($result, MYSQL_ASSOC)) {
		if ($line['parent']==0) {
			if ($line['replyby']!=null) {
				$allowreply = ($isteacher || (time()<$line['replyby']));
			}
		}
		if ($line['id']==$threadid) {
			$newviews = $line['views']+1;
		}
		$children[$line['parent']][] = $line['id'];
		$date[$line['id']] = $line['postdate'];
		$n = 0;
		while (strpos($line['subject'],'Re: ')===0) {
			$line['subject'] = substr($line['subject'],4);
			$n++;
		}
		if ($n==1) {
			$line['subject'] = 'Re: '.$line['subject'];
		} else if ($n>1) {
			$line['subject'] = "Re<sup>$n</sup>: ".$line['subject'];
		}
			
		$subject[$line['id']] = $line['subject'];
		if ($sessiondata['graphdisp']==0) {
			$line['message'] = preg_replace('/<embed[^>]*alt="([^"]*)"[^>]*>/',"[$1]", $line['message']);
		}
		$message[$line['id']] = $line['message'];
		$posttype[$line['id']] = $line['posttype'];
		$ownerid[$line['id']] = $line['userid'];
		if ($line['isanon']==1) {
			$poster[$line['id']] = "Anonymous";
			$ownerid[$line['id']] = 0;
		} else {
			$poster[$line['id']] = $line['FirstName'] . ' ' . $line['LastName'];
			$email[$line['id']] = $line['email'];
		}
		
	}
	//update view count
	$query = "UPDATE imas_forum_posts SET views='$newviews' WHERE id='$threadid'";
	mysql_query($query) or die("Query failed : $query " . mysql_error());
	
	//mark as read
	$query = "SELECT lastview FROM imas_forum_views WHERE userid='$userid' AND threadid='$threadid'";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$now = time();
	if (mysql_num_rows($result)>0) {
		$lastview = mysql_result($result,0,0);
		$query = "UPDATE imas_forum_views SET lastview=$now WHERE userid='$userid' AND threadid='$threadid'";
		mysql_query($query) or die("Query failed : $query " . mysql_error());
	} else {
		$lastview = 0;
		$query = "INSERT INTO imas_forum_views (userid,threadid,lastview) VALUES ('$userid','$threadid',$now)";
		mysql_query($query) or die("Query failed : $query " . mysql_error());
	}
	
	echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"../course/course.php?cid=$cid\">$coursename</a> &gt; <a href=\"thread.php?cid=$cid&forum=$forumid&page=$page\">Forum Topics</a> &gt; Posts</div>\n";
	echo "<h3>Posts - {$subject[$threadid]}</h3>\n";
	echo "<input type=button value=\"Expand All\" onclick=\"showall()\"/>";
	echo "<input type=button value=\"Collapse All\" onclick=\"collapseall()\"/>";
	if ($view==2) {
		echo "<a href=\"posts.php?view=$view&cid=$cid&forum=$forumid&page=$page&thread=$threadid&view=0\">View Expanded</a>";
	} else {
		
		echo "<a href=\"posts.php?view=$view&cid=$cid&forum=$forumid&page=$page&thread=$threadid&view=2\">View Condensed</a>";
	}
	
	
	echo "<script>\n";
	echo "function toggleshow(bnum) {\n";
	echo "   var node = document.getElementById('block'+bnum);\n";
	echo "   var butn = document.getElementById('butb'+bnum);\n";
	echo "   if (node.className == 'forumgrp') {\n";
	echo "       node.className = 'hidden';\n";
	echo "       if (butn.value=='Collapse') {butn.value = 'Expand';} else {butn.value = '+';}\n";
	//echo "       butn.value = 'Expand';\n";
	echo "   } else { ";
	echo "       node.className = 'forumgrp';\n";
	echo "       if (butn.value=='Expand') {butn.value = 'Collapse';} else {butn.value = '-';}\n";
	
	//echo "       butn.value = 'Collapse';\n";
	echo "   }\n";
	echo "}\n";
	echo "function toggleitem(inum) {\n";
	echo "   var node = document.getElementById('item'+inum);\n";
	echo "   var butn = document.getElementById('buti'+inum);\n";
	echo "   if (node.className == 'blockitems') {\n";
	echo "       node.className = 'hidden';\n";
	echo "       butn.value = 'Show';\n";
	echo "   } else { ";
	echo "       node.className = 'blockitems';\n";
	echo "       butn.value = 'Hide';\n";
	echo "   }\n";
	echo "}\n";
	echo "function showall() {\n";
	echo "   for (var i=0;i<bcnt;i++) {";
	echo "     var node = document.getElementById('block'+i);\n";
	echo "     var butn = document.getElementById('butb'+i);\n";
	echo "     node.className = 'forumgrp';\n";
	//echo "     butn.value = 'Collapse';\n";
	echo "       if (butn.value=='Expand') {butn.value = 'Collapse';} else {butn.value = '-';}\n";
	
	echo "   }\n";
	echo "}\n";
	echo "function collapseall() {\n";
	echo "   for (var i=0;i<bcnt;i++) {";
	echo "     var node = document.getElementById('block'+i);\n";
	echo "     var butn = document.getElementById('butb'+i);\n";
	echo "     node.className = 'hidden';\n";
	//echo "     butn.value = 'Expand';\n";
	echo "       if (butn.value=='Collapse') {butn.value = 'Expand';} else {butn.value = '+';}\n";
	
	echo "   }\n";
	echo "}\n";

	echo "</script>\n";
	
	$bcnt = 0;
	$icnt = 0;
	function printchildren($base) {
		global $children,$date,$subject,$message,$poster,$email,$forumid,$threadid,$isteacher,$cid,$userid,$ownerid,$posttype,$lastview,$bcnt,$icnt,$myrights,$allowreply,$allowmod,$allowdel,$view,$page,$allowmsg;
		foreach($children[$base] as $child) {
			echo "<div class=block> ";
			if ($view==2) {
				echo "<span class=right>";
				echo "<input type=button id=\"buti$icnt\" value=\"Show\" onClick=\"toggleitem($icnt)\">\n";
				
				if ($isteacher || ($ownerid[$child]==$userid && $allowmod)) {
					echo "<a href=\"posts.php?view=$view&cid=$cid&forum=$forumid&thread=$threadid&page=$page&modify=$child\">Modify</a> \n";
				}
				if ($isteacher || ($allowdel && $ownerid[$child]==$userid && !isset($children[$child]))) {
					echo "<a href=\"posts.php?view=$view&cid=$cid&forum=$forumid&thread=$threadid&page=$page&remove=$child\">Remove</a> \n";
				}
				if ($posttype[$child]!=2 && $myrights > 5 && $allowreply) {
					echo "<a href=\"posts.php?view=$view&cid=$cid&forum=$forumid&thread=$threadid&page=$page&modify=reply&replyto=$child\">Reply</a>";
				}
				echo "</span>";
				if (isset($children[$child])) {
					echo "<input type=button id=\"butb$bcnt\" value=\"-\" onClick=\"toggleshow($bcnt)\">\n";
				}
				echo "<b>{$subject[$child]}</b> Posted by: ";
				if ($isteacher && $ownerid[$child]!=0) {
					echo "<a href=\"mailto:{$email[$child]}\">";
				} else if ($allowmsg && $ownerid[$child]!=0) {
					echo "<a href=\"../msgs/msglist.php?cid=$cid&add=new&to={$ownerid[$child]}\">";
				}
				echo $poster[$child];
				if (($isteacher || $allowmsg) && $ownerid[$child]!=0) {
					echo "</a>";
				}
				echo ', ';
				echo tzdate("F j, Y, g:i a",$date[$child]);
				if ($date[$child]>$lastview) {
					echo " <span style=\"color:red;\">New</span>\n";
				}
				echo "</div>\n";
				echo "<div class=hidden id=\"item$icnt\">";
				
				
				echo filter($message[$child]);
				echo "</div>\n";
				$icnt++;
				if (isset($children[$child]) && ($posttype[$child]!=3 || $isteacher)) { //if has children
					echo "<div class=forumgrp id=\"block$bcnt\">\n";
					$bcnt++;
					printchildren($child);
					echo "</div>\n";
				}
			} else {
				echo "<span class=right>";
				if (isset($children[$child])) {
					echo "<input type=button id=\"butb$bcnt\" value=\"Collapse\" onClick=\"toggleshow($bcnt)\">\n";
				}
				if ($isteacher || ($ownerid[$child]==$userid && $allowmod)) {
					echo "<a href=\"posts.php?view=$view&cid=$cid&forum=$forumid&thread=$threadid&page=$page&modify=$child\">Modify</a> \n";
				}
				if ($isteacher || ($allowdel && $ownerid[$child]==$userid && !isset($children[$child]))) {
					echo "<a href=\"posts.php?view=$view&cid=$cid&forum=$forumid&thread=$threadid&page=$page&remove=$child\">Remove</a> \n";
				}
				if ($posttype[$child]!=2 && $myrights > 5 && $allowreply) {
					echo "<a href=\"posts.php?view=$view&cid=$cid&forum=$forumid&thread=$threadid&page=$page&modify=reply&replyto=$child\">Reply</a>";
				}
				echo "</span>\n";
				echo "<b>{$subject[$child]}</b><br/>Posted by: ";
				if ($isteacher && $ownerid[$child]!=0) {
					echo "<a href=\"mailto:{$email[$child]}\">";
				} else if ($allowmsg && $ownerid[$child]!=0) {
					echo "<a href=\"../msgs/msglist.php?cid=$cid&add=new&to={$ownerid[$child]}\">";
				}
				echo $poster[$child];
				if (($isteacher || $allowmsg) && $ownerid[$child]!=0) {
					echo "</a>";
				}
				echo ', ';
				echo tzdate("F j, Y, g:i a",$date[$child]);
				if ($date[$child]>$lastview) {
					echo " <span style=\"color:red;\">New</span>\n";
				}
				echo "</div>\n";
				echo "<div class=blockitems>";
				echo filter($message[$child]);
				echo "</div>\n";
				if (isset($children[$child]) && ($posttype[$child]!=3 || $isteacher)) { //if has children
					echo "<div class=";
					if ($view==0) {
						echo '"forumgrp"';
					} else if ($view==1) {
						echo '"hidden"';
					}
					echo " id=\"block$bcnt\">\n";
					$bcnt++;
					printchildren($child);
					echo "</div>\n";
				}
			}
		}
	}
	printchildren(0);
	echo "<script type=\"text/javascript\">";
	echo "var bcnt =".$bcnt.";\n";
	echo "</script>";
	echo "<div class=right><a href=\"thread.php?cid=$cid&forum=$forumid&page=$page\">Back to Forum Topics</a></div>\n";
	require("../footer.php");
?>

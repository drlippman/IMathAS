<?php
	//Displays forum threads
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
	
	$threadsperpage = 20;
	
	$cid = $_GET['cid'];
	$forumid = $_GET['forum'];
	$query = "SELECT name,postby,settings FROM imas_forums WHERE id='$forumid'";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$forumname = mysql_result($result,0,0);
	$postby = mysql_result($result,0,1);
	$allowmod = ((mysql_result($result,0,2)&2)==2);
	$allowdel = ((mysql_result($result,0,2)&4)==4);
	
	if (!isset($_GET['page']) || $_GET['page']=='') {
		$page = 1;
	} else {
		$page = $_GET['page'];
	}
	
	if (isset($_GET['modify'])) { //adding or modifying thread
		if (isset($_POST['subject'])) {  //form submitted
			if ($isteacher) {
				$type = $_POST['type'];
			} else {
				$type = 0;
			}
			if (trim($_POST['subject'])=='') {
				$_POST['subject']= '(none)';
			}
			if (isset($_POST['postanon']) && $_POST['postanon']==1) {
				$isanon = 1;
			} else {
				$isanon = 0;
			}
			if (!isset($_POST['replyby']) || $_POST['replyby']=="null") {
				$replyby = "NULL";
			} else if ($_POST['replyby']=="Always") {
				$replyby = 2000000000;
			} else if ($_POST['replyby']=="Never") {
				$replyby = 0;
			} else {
				require_once("../course/parsedatetime.php");
				$replyby = parsedatetime($_POST['replybydate'],$_POST['replybytime']);
			}
			
			if ($_GET['modify']=="new") {	
				$now = time();
				$query = "INSERT INTO imas_forum_posts (forumid,subject,message,userid,postdate,parent,posttype,isanon,replyby) VALUES ";
				$query .= "('$forumid','{$_POST['subject']}','{$_POST['message']}','$userid',$now,0,'$type','$isanon',$replyby)";
				mysql_query($query) or die("Query failed : $query " . mysql_error());
				$threadid = mysql_insert_id();
				$query = "UPDATE imas_forum_posts SET threadid='$threadid' WHERE id='$threadid'";
				mysql_query($query) or die("Query failed : $query " . mysql_error());
				$query = "INSERT INTO imas_forum_views (userid,threadid,lastview) VALUES ('$userid','$threadid',$now)";
				mysql_query($query) or die("Query failed : $query " . mysql_error());
				
				$query = "SELECT iu.email FROM imas_users AS iu,imas_forum_subscriptions AS ifs WHERE ";
				$query .= "iu.id=ifs.userid AND ifs.forumid='$forumid' AND iu.id<>'$userid'";
				$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
				if (mysql_num_rows($result)>0) {
					$headers  = 'MIME-Version: 1.0' . "\r\n";
					$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
					$headers .= "From: $sendfrom\r\n";
					$message  = "<h4>This is an automated message.  Do not respond to this email</h4>\r\n";
					$message .= "<p>A new thread has been started in forum $forumname in course $coursename</p>\r\n";
					$message .= "<p>Subject:".stripslashes($_POST['subject'])."</p>";
					$message .= "<a href=\"http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/posts.php?cid=$cid&forum=$forumid&thread=$threadid\">";
					$message .= "View Posting</a>\r\n";
				}
				while ($row = mysql_fetch_row($result)) {
					mail($row[0],'New forum post notification',$message,$headers);
				}
			} else {
				$query = "UPDATE imas_forum_posts SET subject='{$_POST['subject']}',message='{$_POST['message']}',posttype='$type',replyby=$replyby,isanon='$isanon' ";
				$query .= "WHERE id='{$_GET['modify']}'";
				if (!$isteacher) { $query .= " AND userid='$userid'";}
				mysql_query($query) or die("Query failed : $query " . mysql_error());
			}
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/thread.php?page=$page&cid=$cid&forum=$forumid");
			exit;
		} else { //display mod
			$pagetitle = "Add/Modify Thread";
			$useeditor = "message";
			require("../header.php");
			echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"../course/course.php?cid=$cid\">$coursename</a> ";
			echo "&gt; <a href=\"thread.php?page=$page&cid=$cid&forum=$forumid\">Forum Topics</a> &gt;";
			if ($_GET['modify']!="new") {
				echo "Modify Thread</div>\n";
				$query = "SELECT * from imas_forum_posts WHERE id='{$_GET['modify']}'";
				$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
				$line = mysql_fetch_array($result, MYSQL_ASSOC);
				echo "<h3>Modify Thread - \n";
				$replyby = $line['replyby'];
			} else {
				echo "Add Thread</div>\n";
				$line['subject'] = "";
				$line['message'] = "";
				$line['posttype'] = 0;
				$replyby = null;
				echo "<h3>Add Thread - \n";
			}
			$query = "SELECT name,settings FROM imas_forums WHERE id='$forumid'";
			$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			$allowanon = mysql_result($result,0,1)%2;
			echo mysql_result($result,0,0).'</h3>';
			
			if ($replyby!=null && $replyby<2000000000 && $replyby>0) {
				$replybydate = tzdate("m/d/Y",$replyby);
				$replybytime = tzdate("g:i a",$replyby);	
			} else {
				$replybydate = tzdate("m/d/Y",time()+7*24*60*60);
				$replybytime = tzdate("g:i a",time()+7*24*60*60);
			}
			echo "<form method=post action=\"thread.php?page=$page&cid=$cid&forum=$forumid&modify={$_GET['modify']}\">\n";
			echo "<span class=form><label for=\"subject\">Subject:</label></span>";
			echo "<span class=formright><input type=text size=50 name=subject id=subject value=\"{$line['subject']}\"></span><br class=form>\n";
			echo "<span class=form><label for=\"message\">Message:</label></span>";
			echo "<span class=left><div class=editor><textarea id=message name=message style=\"width: 100%;\" rows=20 cols=70>{$line['message']}</textarea></div></span><br class=form>\n";
			if ($isteacher) {
				echo "<span class=form>Post Type:</span><span class=formright>\n";
				echo "<input type=radio name=type value=0 ";
				if ($line['posttype']==0) { echo "checked=1";}
				echo ">Regular<br>\n";
				echo "<input type=radio name=type value=1 ";
				if ($line['posttype']==1) { echo "checked=1";}
				echo ">Displayed at top of list<br>\n";
				echo "<input type=radio name=type value=2 ";
				if ($line['posttype']==2) { echo "checked=1";}
				echo ">Displayed at top and locked (no replies)<br>\n";
				echo "<input type=radio name=type value=3 ";
				if ($line['posttype']==3) { echo "checked=1";}
				echo ">Displayed at top and replies hidden from students\n";
				echo "</span><br class=form>";
				echo "<span class=form>Allow replies:</span><span class=formright>\n";
				echo "<input type=radio name=replyby value=\"null\" ";
				if ($line['replyby']==null) { echo "checked=1";}
				echo "/>Use default<br/>";
				echo "<input type=radio name=replyby value=\"Always\" ";
				if ($line['replyby']==2000000000) { echo "checked=1";}
				echo "/>Always<br/>";
				echo "<input type=radio name=replyby value=\"Never\" ";
				if ($line['replyby']==='0') { echo "checked=1";}
				echo "/>Never<br/>";
				echo "<input type=radio name=replyby value=\"Date\" ";
				if ($line['replyby']<2000000000 && $line['replyby']>0) { echo "checked=1";}
				echo "/>Before: "; 
				echo "<input type=text size=10 name=replybydate value=\"$replybydate\"/>";
				echo "<A HREF=\"#\" onClick=\"cal1.select(document.forms[0].replybydate,'anchor3','MM/dd/yyyy',(document.forms[0].replybydate.value==$replybydate')?(document.forms[0].replyby.value):(document.forms[0].replyby.value)); return false;\" NAME=\"anchor3\" ID=\"anchor3\"><img src=\"../img/cal.gif\" alt=\"Calendar\"/></A>";
				echo "at <input type=text size=10 name=replybytime value=\"$replybytime\"></span><br class=\"form\" />";
			} else {
				if ($allowanon==1) {
					echo "<span class=form>Post Anonymously:</span><span class=formright>";
					echo "<input type=checkbox name=\"postanon\" value=1 ";
					if ($line['isanon']==1) {echo "checked=1";}
					echo "></span><br class=form/>";
				}
			}
			echo "<div class=submit><input type=submit value='Submit'></div>\n";
			require("../footer.php");
			exit;
		}
	} else if (isset($_GET['remove']) && $isteacher) { //removing thread
		if (isset($_GET['confirm'])) {
			$query = "DELETE FROM imas_forum_posts WHERE id='{$_GET['remove']}'";
			mysql_query($query) or die("Query failed : $query " . mysql_error());

			$query = "DELETE FROM imas_forum_posts WHERE threadid='{$_GET['remove']}'";
			mysql_query($query) or die("Query failed : $query " . mysql_error());
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/thread.php?page=$page&cid=$cid&forum=$forumid");
			exit;
		} else {
			$pagetitle = "Remove Thread";
			require("../header.php");
			echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"../course/course.php?cid=$cid\">$coursename</a> ";
			echo "&gt; <a href=\"thread.php?page=$page&cid=$cid&forum=$forumid\">Forum Topics</a> &gt; Remove Thread</div>";
			echo "<h3>Remove Thread</h3>\n";
			echo "<p>Are you SURE you want to remove this Thread and all enclosed posts?</p>\n";

			echo "<p><input type=button value=\"Yes, Remove\" onClick=\"window.location='thread.php?page=$page&cid=$cid&forum=$forumid&remove={$_GET['remove']}&confirm=true'\">\n";
			echo "<input type=button value=\"Nevermind\" onClick=\"window.location='thread.php?page=$page&cid=$cid&forum=$forumid'\"></p>\n";
			require("../footer.php");
			exit;
		}
	}
	
	$pagetitle = "Threads";
	$placeinhead = "<style type=\"text/css\">\n@import url(\"$imasroot/forums/forums.css\");\n</style>\n";
	require("../header.php");
	
	
	echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"../course/course.php?cid=$cid\">$coursename</a> &gt; Forum Topics</div>\n";
	echo "<h3>Forum - $forumname</h3>\n";
	
	
	$query = "SELECT COUNT(id) FROM imas_forum_posts WHERE parent=0 AND forumid='$forumid'";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$numpages = ceil(mysql_result($result,0,0)/$threadsperpage);
	
	if ($numpages > 1) {
		echo "<div style=\"padding: 5px;\">Page: ";
		if ($page < $numpages/2) {
			$min = max(2,$page-4);
			$max = min($numpages-1,$page+8+$min-$page);
		} else {
			$max = min($numpages-1,$page+4);
			$min = max(2,$page-8+$max-$page);
		}
		if ($page==1) {
			echo "<b>1</b> ";
		} else {
			echo "<a href=\"thread.php?page=1&cid=$cid&forum=$forumid\">1</a> ";
		}
		if ($min!=2) { echo " ... ";}
		for ($i = $min; $i<=$max; $i++) {
			if ($page == $i) {
				echo "<b>$i</b> ";
			} else {
				echo "<a href=\"thread.php?page=$i&cid=$cid&forum=$forumid\">$i</a> ";
			}
		}
		if ($max!=$numpages-1) { echo " ... ";}
		if ($page == $numpages) {
			echo "<b>$numpages</b> ";
		} else {
			echo "<a href=\"thread.php?page=$numpages&cid=$cid&forum=$forumid\">$numpages</a> ";
		}
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		if ($page>1) {
			echo "<a href=\"thread.php?page=".($page-1)."&cid=$cid&forum=$forumid\">Previous</a> ";
		}
		if ($page < $numpages) {
			echo "<a href=\"thread.php?page=".($page+1)."&cid=$cid&forum=$forumid\">Next</a> ";
		}
		echo "</div>\n";
	}
	
?>
		
	<table class=forum>
	<thead>
	<tr><th>Topic</th><th>Replies</th><th>Views (Unique)</th><th>Last Post Date</th></tr>
	</thead>
	<tbody>
<?php
	
	
	$query = "SELECT threadid,COUNT(id) AS postcount,MAX(postdate) AS maxdate FROM imas_forum_posts ";
	$query .= "WHERE forumid='$forumid' GROUP BY threadid";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$postcount[$row[0]] = $row[1] -1;
		$maxdate[$row[0]] = $row[2];
	}
	
	$query = "SELECT threadid,lastview FROM imas_forum_views WHERE userid='$userid'";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$lastview[$row[0]] = $row[1];
	}
	
	$query = "SELECT imas_forum_posts.id,count(imas_forum_views.userid) FROM imas_forum_views,imas_forum_posts ";
	$query .= "WHERE imas_forum_views.threadid=imas_forum_posts.id AND imas_forum_posts.parent=0 AND ";
	$query .= "imas_forum_posts.forumid='$forumid' GROUP BY imas_forum_posts.id";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$uniqviews[$row[0]] = $row[1]-1;
	}
	
	$query = "SELECT * FROM imas_forum_posts WHERE parent=0 AND forumid='$forumid' ORDER BY posttype DESC,id DESC ";
	$offset = ($page-1)*$threadsperpage;
	$query .= "LIMIT $offset,$threadsperpage";// OFFSET $offset"; 
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		if (isset($postcount[$line['id']])) {
			$posts = $postcount[$line['id']];
			$lastpost = tzdate("F j, Y, g:i a",$maxdate[$line['id']]);
		} else {
			$posts = 0;
			$lastpost = '';
		}
		echo "<tr ";
		if ($line['posttype']>0) {
			echo "class=sticky";
		}
		echo "><td>";
		echo "<span class=right>\n";
		if ($isteacher || ($line['userid']==$userid && $allowmod)) {
			echo "<a href=\"thread.php?page=$page&cid=$cid&forum=$forumid&modify={$line['id']}\">Modify</a> ";
		} 
		if ($isteacher || ($allowdel && $line['userid']==$userid && $posts==0)) {
			echo "<a href=\"thread.php?page=$page&cid=$cid&forum=$forumid&remove={$line['id']}\">Remove</a>";
		}
		echo "</span>\n";
		
		echo "<b><a href=\"posts.php?cid=$cid&forum=$forumid&thread={$line['id']}&page=$page\">{$line['subject']}</a></b>";
		
		echo "</td>\n";
		
		echo "<td class=c>$posts</td><td class=c>{$line['views']} ({$uniqviews[$line['id']]})</td><td class=c>$lastpost ";
		if ($lastpost=='' || $maxdate[$line['id']]>$lastview[$line['id']]) {
			echo "<span style=\"color: red;\">New</span>";
		}
		echo "</td></tr>\n";
	}
?>
	</tbody>
	</table>
<?php
	if ($myrights > 5 && time()<$postby) {
		echo "<p><a href=\"thread.php?page=$page&cid=$cid&forum=$forumid&modify=new\">Add New Thread</a></p>\n";
	}
	
	require("../footer.php");
?>

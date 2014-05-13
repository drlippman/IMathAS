<?php
//IMathAS:  include with posts.php and postsbyname.php for handling deletes, replies, etc.
//(c) 2006 David Lippman
@set_time_limit(0);
ini_set("max_input_time", "600");
ini_set("max_execution_time", "600");
ini_set("memory_limit", "104857600");
ini_set("upload_max_filesize", "10485760");
ini_set("post_max_size", "10485760");

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
	
} else if ($caller=='thread') {
	$returnurl = "thread.php?page=$page&cid=$cid&forum=$forumid";
	$returnname = "Forum Topics";
}

if (isset($_GET['modify'])) { //adding or modifying post
	if ($caller=='thread') {
		$threadid = $_GET['modify'];
	}
	if ($_GET['modify']!='new' && $threadid==0) {
		echo "I don't know what thread you're replying to.  Please go back and try again.";
	}
	if (isset($_POST['subject'])) {  //form submitted
		if (isset($_POST['postanon']) && $_POST['postanon']==1) {
			$isanon = 1;
		} else {
			$isanon = 0;
		}
		if ($isteacher) {
			$type = $_POST['type'];
			if (!isset($_POST['replyby']) || $_POST['replyby']=="null") {
				$replyby = "NULL";
			} else if ($_POST['replyby']=="Always") {
				$replyby = 2000000000;
			} else if ($_POST['replyby']=="Never") {
				$replyby = 0;
			} else {
				require_once("../includes/parsedatetime.php");
				$replyby = parsedatetime($_POST['replybydate'],$_POST['replybytime']);
			}
		} else {
			$type = 0;
			$replyby = "NULL";
		}
		if (isset($_POST['tag'])) {
			$tag = $_POST['tag'];
		} else {
			$tag = '';
		}
		require_once("../includes/htmLawed.php");
		$htmlawedconfig = array('elements'=>'*-script-form');
		$_POST['message'] = addslashes(htmLawed(stripslashes($_POST['message']),$htmlawedconfig));
		$_POST['subject'] = trim(strip_tags($_POST['subject']));
		if (trim($_POST['subject'])=='') {
			$_POST['subject']= '(none)';
		}
		if ($_GET['modify']=="new") { //new thread
			$now = time();
			if ($groupsetid>0) {
				if ($isteacher) {
					if (isset($_POST['stugroup'])) {
						$groupid = $_POST['stugroup'];
					} else {
						$groupid = 0;
					}
				} 
			}
			$query = "INSERT INTO imas_forum_posts (forumid,subject,message,userid,postdate,parent,posttype,isanon,replyby,tag) VALUES ";
			$query .= "('$forumid','{$_POST['subject']}','{$_POST['message']}','$userid',$now,0,'$type','$isanon',$replyby,'$tag')";
			mysql_query($query) or die("Query failed : $query " . mysql_error());
			$threadid = mysql_insert_id();
			$query = "UPDATE imas_forum_posts SET threadid='$threadid' WHERE id='$threadid'";
			mysql_query($query) or die("Query failed : $query " . mysql_error());
			
			$query = "INSERT INTO imas_forum_threads (id,forumid,lastposttime,lastpostuser,stugroupid) VALUES ('$threadid','$forumid',$now,'$userid','$groupid')";
			mysql_query($query) or die("Query failed : $query " . mysql_error());
			
			$query = "INSERT INTO imas_forum_views (userid,threadid,lastview) VALUES ('$userid','$threadid',$now)";
			mysql_query($query) or die("Query failed : $query " . mysql_error());
			$sendemail = true;	
			
			if (isset($studentid)) {
				$query = "INSERT INTO imas_content_track (userid,courseid,type,typeid,viewtime,info) VALUES ";
				$query .= "('$userid','$cid','forumpost','$threadid',$now,'$forumid')";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
			
			$_GET['modify'] = $threadid;
			$files = array();
		} else if ($_GET['modify']=="reply") { //new reply post
			
			$query = "SELECT userid FROM imas_forum_posts WHERE id='{$_GET['replyto']}'";
			$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			if (mysql_num_rows($result)==0) {//parent post deleted
				$sendemail = false;
				require("../header.php");
				echo '<h2>Error:</h2><p>It looks like the post you were replying to was deleted.  Your post is below in case you ';
				echo 'want to copy-and-paste it somewhere. <a href="'.$returnurl.'">Continue</a></p>';
				echo '<hr>';
				echo '<p>Message:</p><div class="editor">'.filter(stripslashes($_POST['message'])).'</div>';
				echo '<p>HTML format:</p>';
				echo '<div class="editor">'.htmlentities(stripslashes($_POST['message'])).'</div>';
				require("../footer.php");
				exit;
			} else {
				$uid = mysql_result($result,0,0);
				
				$now = time();
				$query = "INSERT INTO imas_forum_posts (forumid,threadid,subject,message,userid,postdate,parent,posttype,isanon) VALUES ";
				$query .= "('$forumid','$threadid','{$_POST['subject']}','{$_POST['message']}','$userid',$now,'{$_GET['replyto']}',0,'$isanon')";
				mysql_query($query) or die("Query failed : $query " . mysql_error());
				$_GET['modify'] = mysql_insert_id();
				
				$query = "UPDATE imas_forum_threads SET lastposttime=$now,lastpostuser='$userid' WHERE id='$threadid'";
				mysql_query($query) or die("Query failed : $query " . mysql_error());
				
				if (isset($studentid)) {
					$query = "INSERT INTO imas_content_track (userid,courseid,type,typeid,viewtime,info) VALUES ";
					$query .= "('$userid','$cid','forumreply','{$_GET['modify']}',$now,'$forumid;$threadid')";
					mysql_query($query) or die("Query failed : " . mysql_error());
				}
				
				if ($isteacher && isset($_POST['points']) && trim($_POST['points'])!='') {
					$query = "SELECT id FROM imas_grades WHERE gradetype='forum' AND refid='{$_GET['replyto']}'";
					$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
					if (mysql_num_rows($result)>0) {
						$gradeid = mysql_result($result,0,0);
						$query = "UPDATE imas_grades SET score='{$_POST['points']}' WHERE id=$gradeid";
						//$query = "UPDATE imas_forum_posts SET points='{$_POST['points']}' WHERE id='{$_GET['replyto']}'";
						mysql_query($query) or die("Query failed : $query " . mysql_error());
					} else {
						//moved up as a "did the post get deleted" check
						//$query = "SELECT userid FROM imas_forum_posts WHERE id='{$_GET['replyto']}'";
						//$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
						//$uid = mysql_result($result,0,0);
						$query = "INSERT INTO imas_grades (gradetype,gradetypeid,userid,refid,score) VALUES ";
						$query .= "('forum','$forumid','$uid','{$_GET['replyto']}','{$_POST['points']}')";
						mysql_query($query) or die("Query failed : $query " . mysql_error());
					}
				}
				$sendemail = true;
				$files = array();
			}
		} else {
			$query = "UPDATE imas_forum_posts SET subject='{$_POST['subject']}',message='{$_POST['message']}',isanon='$isanon',tag='$tag',posttype='$type',replyby=$replyby ";
			$query .= "WHERE id='{$_GET['modify']}'";
			if (!$isteacher) { $query .= " AND userid='$userid'";}
			mysql_query($query) or die("Query failed : $query " . mysql_error());
			if ($caller=='thread' || $_GET['thread']==$_GET['modify']) {
				if ($groupsetid>0 && $isteacher && isset($_POST['stugroup'])) {
					$groupid = $_POST['stugroup'];
					$query = "UPDATE imas_forum_threads SET stugroupid='$groupid' WHERE id='{$_GET['modify']}'";
					mysql_query($query) or die("Query failed : $query " . mysql_error());
				}
			}
			
			if (isset($studentid)) {
				$query = "INSERT INTO imas_content_track (userid,courseid,type,typeid,viewtime,info) VALUES ";
				$query .= "('$userid','$cid','forummod','{$_GET['modify']}',$now,'$forumid;$threadid')";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
			
			$sendemail = false;
			$query = "SELECT files FROM imas_forum_posts WHERE id='{$_GET['modify']}'";
			$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			$files = mysql_result($result,0,0);
			if ($files=='') {
				$files = array();
			} else {
				$files = explode('@@',$files);
			}
		}
		if ($sendemail) {
			$query = "SELECT iu.email FROM imas_users AS iu,imas_forum_subscriptions AS ifs WHERE ";
			$query .= "iu.id=ifs.userid AND ifs.forumid='$forumid' AND iu.id<>'$userid'";
			$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			if (mysql_num_rows($result)>0) {
				$headers  = 'MIME-Version: 1.0' . "\r\n";
				$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
				$headers .= "From: $sendfrom\r\n";
				$message  = "<h4>This is an automated message.  Do not respond to this email</h4>\r\n";
				$message .= "<p>A new post has been made in forum $forumname in course $coursename</p>\r\n";
				$message .= "<p>Subject:".stripslashes($_POST['subject'])."</p>";
				$message .= "<p>Poster: $userfullname</p>";
				$message .= "<a href=\"" . $urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/$returnurl\">";
				$message .= "View Posting</a>\r\n";
			}
			while ($row = mysql_fetch_row($result)) {
				$row[0] = trim($row[0]);
				if ($row[0]!='' && $row[0]!='none@none.com') {
					mail($row[0],'New forum post notification',$message,$headers);
				}
			}
		}
		//now handle any files
		if (isset($_POST['filedesc'])) {
			foreach ($_POST['filedesc'] as $i=>$v) {
				$files[2*$i] = str_replace('@@','@',$v);
			}
			for ($i=count($files)/2-1;$i>=0;$i--) {
				if (isset($_POST['filedel'][$i])) {
					if (deleteforumfile($_GET['modify'],$files[2*$i+1])) {
						array_splice($files,2*$i,2);
					}
				}
			}
		}
		if (isset($_FILES['newfile-0'])) {
			require_once("../includes/filehandler.php");
			$i = 0;
			$badextensions = array(".php",".php3",".php4",".php5",".bat",".com",".pl",".p");
			while (isset($_FILES['newfile-'.$i]) && is_uploaded_file($_FILES['newfile-'.$i]['tmp_name'])) {
				$userfilename = preg_replace('/[^\w\.]/','',basename($_FILES['newfile-'.$i]['name']));
				if (trim($_POST['newfiledesc-'.$i])=='') {
					$_POST['newfiledesc-'.$i] = $userfilename;
				}
				$_POST['newfiledesc-'.$i] = str_replace('@@','@',$_POST['newfiledesc-'.$i]);
				$extension = strtolower(strrchr($userfilename,"."));
				if (!in_array($extension,$badextensions) && storeuploadedfile('newfile-'.$i,'ffiles/'.$_GET['modify'].'/'.$userfilename,"public")) {
					$files[] = stripslashes($_POST['newfiledesc-'.$i]);
					$files[] = $userfilename;
				}
				$i++;
			}
		}
		$files = addslashes(implode('@@',$files));
		$query = "UPDATE imas_forum_posts SET files='$files' WHERE id='{$_GET['modify']}'";
		mysql_query($query) or die("Query failed : $query " . mysql_error());
			
		header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/$returnurl");
		exit;
	} else { //display mod
		if ($caller=='thread') {
			$pagetitle = "Add/Modify Thread";
		} else {
			$pagetitle = "Add/Modify Post";
		}
		$useeditor = "message";
		$loadgraphfilter = true;
		$placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/DatePicker.js\"></script>";

		require("../header.php");
		echo "<div class=breadcrumb>$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">$coursename</a> ";
		if ($caller != 'thread') {
			echo "&gt; <a href=\"thread.php?page=$page&cid=$cid&forum=$forumid\">Forum Topics</a> ";
		}
		echo "&gt; <a href=\"$returnurl\">$returnname</a> &gt; ";
		$notice = '';
		if ($_GET['modify']!="reply" && $_GET['modify']!='new') {
			echo "Modify Posting</div>\n";
			$query = "SELECT * from imas_forum_posts WHERE id='{$_GET['modify']}'";
			$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			$line = mysql_fetch_array($result, MYSQL_ASSOC);
			$replyby = $line['replyby'];
			echo '<div id="headerposthandler" class="pagetitle"><h2>Modify Post</h2></div>';
		} else {
			if ($_GET['modify']=='reply') {
				echo "Post Reply</div>\n";
					//$query = "SELECT subject,points FROM imas_forum_posts WHERE id='{$_GET['replyto']}'";
				$query = "SELECT ifp.subject,ig.score FROM imas_forum_posts AS ifp LEFT JOIN imas_grades AS ig ON ";
				$query .= "ig.gradetype='forum' AND ifp.id=ig.refid WHERE ifp.id='{$_GET['replyto']}'";
				$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
				$sub = mysql_result($result,0,0);
				$sub = str_replace('"','&quot;',$sub);
				$line['subject'] = "Re: $sub";
				$line['message'] = "";
				$line['files'] = '';
				$replyby = $line['replyby'];
				$points = mysql_result($result,0,1);
				if ($isteacher) {
					$query = "SELECT points FROM imas_forums WHERE id='$forumid'";
					$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
					$haspoints = (mysql_result($result,0,0)>0);
				}
				echo '<div id="headerposthandler" class="pagetitle"><h2>Post Reply</h2></div>';
			} else if ($_GET['modify']=='new') {
				echo "Add Thread</div>\n";
				$line['subject'] = "";
				$line['message'] = "";
				$line['posttype'] = 0;
				$line['files'] = '';
				$line['tag'] = '';
				$curstugroupid = 0;
				$replyby = null;
				echo "<h2>Add Thread - \n";
				if (isset($_GET['quoteq'])) {
					require_once("../assessment/displayq2.php");
					$showa = false;
					$parts = explode('-',$_GET['quoteq']);
					if (count($parts)==5) {
						//wants to show ans
						$query = "SELECT seeds,attempts,questions FROM imas_assessment_sessions WHERE userid='$userid' AND assessmentid='{$parts[3]}'";
						$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
						$seeds = explode(',',mysql_result($result,0,0));
						$seeds = $seeds[$parts[0]];
						$attempts = explode(',',mysql_result($result,0,1));
						$attempts = $attempts[$parts[0]];
						$qs = explode(',',mysql_result($result,0,2));
						$qid = intval($qs[$parts[0]]);
						$query = "SELECT questionsetid,attempts,showans FROM imas_questions WHERE id=$qid";
						$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
						$parts[1] = mysql_result($result,0,0);
						$allowedattempts = mysql_result($result,0,1);
						$showans = mysql_result($result,0,2);
						
						$query = "SELECT defattempts,deffeedback,displaymethod FROM imas_assessments WHERE id='{$parts[3]}'";
						$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
						list($displaymode,$defshowans) = explode('-',mysql_result($result,0,1));
						if ($allowedattempts==9999) {
							$allowedattempts = mysql_result($result,0,0);	
						}
						if ($showans==0) {
							$showans = $defshowans;
						}
						if ($attempts >= $allowedattempts) {
							if ($showans=='F' || $showans=='J') { 
								$showa = true;
							}
						}
								
					}
					$message = displayq($parts[0],$parts[1],$parts[2],$showa,false,0,true);
					$message = printfilter(forcefiltergraph($message));
					$message = preg_replace('/(`[^`]*`)/',"<span class=\"AM\">$1</span>",$message);
					
					$line['message'] = '<p> </p><br/><hr/>'.$message;
					if (isset($parts[3])) {  
						$query = "SELECT name,itemorder FROM imas_assessments WHERE id='".intval($parts[3])."'";
						$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
						$line['subject'] = 'Question about #'.($parts[0]+1).' in '.str_replace('"','&quot;',mysql_result($result,0,0));
						$itemorder = mysql_result($result,0,1);
						$isgroupedq = false;
						if (strpos($itemorder, '~')!==false) {
							$itemorder = explode(',',$itemorder);
							$icnt = 0;
							foreach ($itemorder as $item) {
								if (strpos($item,'~')===false) {
									if ($icnt==$parts[0]) { break;}
									$icnt++; 
								} else {
									$pts = explode('|',$item);
									if ($parts[0]<$icnt+$pts[0]) { 
										$isgroupedq = true; break;
									}
									$icnt += $pts[0];
								}
							}
						} 
						
						if (!$isgroupedq) {
							$query = "SELECT ift.id FROM imas_forum_posts AS ifp JOIN imas_forum_threads AS ift ON ifp.threadid=ift.id AND ifp.parent=0 ";
							$query .= "WHERE ifp.subject='".addslashes($line['subject'])."' AND ift.forumid='$forumid'";
							if ($groupsetid >0 && !$isteacher) {
								$query .= " AND ift.stugroupid='$groupid'";
							}
							$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
							if (mysql_num_rows($result)>0) {
								$notice =  '<span style="color:red;font-weight:bold">This question has already been posted about.</span><br/>';
								$notice .= 'Please read and participate in the existing discussion.';
								while ($row = mysql_fetch_row($result)) {
									$notice .=  "<br/><a href=\"posts.php?cid=$cid&forum=$forumid&thread={$row[0]}\">{$line['subject']}</a>";
								}
							}
						}
					}	
				} //end if quoteq
			}
		}
		$query = "SELECT name,settings,forumtype,taglist FROM imas_forums WHERE id='$forumid'";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		$allowanon = mysql_result($result,0,1)%2;
		if ($_GET['modify']=='new') {
			echo mysql_result($result,0,0).'</h2>';
		}
		$forumtype = mysql_result($result,0,2);
		$taglist = mysql_result($result,0,3);
		if ($replyby!=null && $replyby<2000000000 && $replyby>0) {
			$replybydate = tzdate("m/d/Y",$replyby);
			$replybytime = tzdate("g:i a",$replyby);	
		} else {
			$replybydate = tzdate("m/d/Y",time()+7*24*60*60);
			$replybytime = tzdate("g:i a",time()+7*24*60*60);
		}
		
		echo "<form enctype=\"multipart/form-data\" method=\"post\" action=\"$returnurl&modify={$_GET['modify']}&replyto={$_GET['replyto']}\">\n";
		echo '<input type="hidden" name="MAX_FILE_SIZE" value="10485760" />';
		if (isset($notice) && $notice!='') {
			echo '<span class="form">&nbsp;</span><span class="formright">'.$notice.'</span><br class="form"/>';
		} else {
			echo "<span class=form><label for=\"subject\">Subject:</label></span>";
			echo "<span class=formright><input type=text size=50 name=subject id=subject value=\"{$line['subject']}\"></span><br class=form>\n";
			if ($forumtype==1) { //file forum
				echo '<script type="text/javascript">
					var filecnt = 1;
					function addnewfile(t) {
						var s = document.createElement("span");
						s.innerHTML = \'Description: <input type="text" name="newfiledesc-\'+filecnt+\'" /> File: <input type="file" name="newfile-\'+filecnt+\'" /><br/>\';
						t.parentNode.insertBefore(s,t);
						filecnt++;
					}</script>';
				echo "<span class=form>Files:</span>";
				echo "<span class=formright>";
				if ($line['files']!='') {
					require_once('../includes/filehandler.php');
					$files = explode('@@',$line['files']);
					for ($i=0;$i<count($files)/2;$i++) {
						echo '<input type="text" name="filedesc['.$i.']" value="'.$files[2*$i].'"/>';
						echo '<a href="'.getuserfileurl('ffiles/'.$_GET['modify'].'/'.$files[2*$i+1]).'" target="_blank">View</a> ';
						echo 'Delete? <input type="checkbox" name="filedel['.$i.']" value="1"/><br/>';
					}
				}
				echo 'Description: <input type="text" name="newfiledesc-0" /> ';
				echo 'File: <input type="file" name="newfile-0" /><br/>';
				echo '<a href="#" onclick="addnewfile(this);return false;">Add another file</a>';
				echo "</span><br class=form>\n";
			}
			if ($taglist!='' && ($_GET['modify']=='new' || $_GET['modify']==$threadid)) {
				$p = strpos($taglist,':');
				echo '<span class="form"><label for="tag">'.substr($taglist,0,$p).'</label></span>'; 
				echo '<span class="formright"><select name="tag">';
				echo '<option value="">Select...</option>';
				$tags = explode(',',substr($taglist,$p+1));
				foreach ($tags as $tag) {
					$tag =  str_replace('"','&quot;',$tag);   
					echo '<option value="'.$tag.'" ';
					if ($tag==$line['tag']) {echo 'selected="selected"';}
					echo '>'.$tag.'</option>';
				}
				echo '</select></span><br class="form" />';
			}
			echo "<span class=form><label for=\"message\">Message:</label></span>";
			echo "<span class=left><div class=editor><textarea id=message name=message style=\"width: 100%;\" rows=20 cols=70>";
			echo htmlentities($line['message']);
			echo "</textarea></div></span><br class=form>\n";
			if (!$isteacher && $allowanon==1) {
				echo "<span class=form>Post Anonymously:</span><span class=formright>";
				echo "<input type=checkbox name=\"postanon\" value=1 ";
				if ($line['isanon']==1) {echo "checked=1";}
				echo "></span><br class=form/>";
			}
			if ($isteacher && ($_GET['modify']=='new' || $line['userid']==$userid) && ($_GET['modify']=='new' || $_GET['modify']==$_GET['thread'] || ($_GET['modify']!='reply' && $line['parent']==0))) {
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
				echo ">Displayed at top and students can only see their own replies\n";
				echo "</span><br class=form>";
				echo "<span class=form>Allow replies: </span><span class=formright>\n";
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
				if ($line['replyby']!==null && $line['replyby']<2000000000 && $line['replyby']>0) { echo "checked=1";}
				echo "/>Before: "; 
				echo "<input type=text size=10 name=replybydate value=\"$replybydate\"/>";
				echo '<a href="#" onClick="displayDatePicker(\'replybydate\', this); return false">';
				//echo "<A HREF=\"#\" onClick=\"cal1.select(document.forms[0].replybydate,'anchor3','MM/dd/yyyy',(document.forms[0].replybydate.value==$replybydate')?(document.forms[0].replyby.value):(document.forms[0].replyby.value)); return false;\" NAME=\"anchor3\" ID=\"anchor3\">
				echo "<img src=\"../img/cal.gif\" alt=\"Calendar\"/></A>";
				echo "at <input type=text size=10 name=replybytime value=\"$replybytime\"></span><br class=\"form\" />";
				if ($groupsetid >0) {
					echo '<span class="form">Set thread to group:</span><span class="formright">';
					echo '<select name="stugroup">';
					echo '<option value="0" ';
					if ($curstugroupid==0) { echo 'selected="selected"';}
					echo '>Non group-specific</option>';
					$query = "SELECT id,name FROM imas_stugroups WHERE groupsetid='$groupsetid' ORDER BY name";
					$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
					while ($row = mysql_fetch_row($result)) {
						echo '<option value="'.$row[0].'" ';
						if ($curstugroupid==$row[0]) { echo 'selected="selected"';}
						echo '>'.$row[1].'</option>';
					}
					echo '</select></span><br class="form" />';
				}
					
			}
			if ($isteacher && $haspoints && $_GET['modify']=='reply') {
				echo '<span class="form">Points for message you\'re replying to:</span><span class="formright">';
				echo '<input type="text" size="4" name="points" value="'.$points.'" /></span><br class="form" />';
			}
			if ($_GET['modify']=='reply') {
				echo "<div class=submit><input type=submit value='Post Reply'></div>\n";
			} else if ($_GET['modify']=='new') { 
				echo "<div class=submit><input type=submit value='Post Thread'></div>\n";
			} else {
				echo "<div class=submit><input type=submit value='Save Changes'></div>\n";
			}
	
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
			} else {
				echo '<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>';	
			}
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
			require_once("../includes/filehandler.php");
			$query = "SELECT parent,files FROM imas_forum_posts WHERE id='{$_GET['remove']}'";
			$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			$parent = mysql_result($result,0,0);
			$files = mysql_result($result,0,1);
			if ($parent==0) {
				$query = "SELECT id FROM imas_forum_posts WHERE threadid='{$_GET['remove']}' AND files<>''";
				$r = mysql_query($query) or die("Query failed : $query " . mysql_error());
				while ($row = mysql_fetch_row($r)) {
					deleteallpostfiles($row[0]); //delete files for each post
				}
				
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
				
				if ($files!= '') {
					deleteallpostfiles($_GET['remove']);
				}
			}
			$query = "DELETE FROM imas_grades WHERE gradetype='forum' AND refid='{$_GET['remove']}'";
			mysql_query($query) or die("Query failed : $query " . mysql_error());
			
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
		if ($caller!='thread') {echo "&gt; <a href=\"thread.php?page=$page&cid=$cid&forum=$forumid\">Forum Topics</a> ";}
		echo "&gt; <a href=\"$returnurl\">$returnname</a> &gt; Remove Post</div>";
		
		echo "<h3>Remove Post</h3>\n";
		if ($parent==0) {
			echo "<p>Are you SURE you want to remove this thread and all replies?</p>\n";
		} else {
			echo "<p>Are you SURE you want to remove this post?</p>\n";
		}
		
		echo "<p><input type=button value=\"Yes, Remove\" onClick=\"window.location='$returnurl&remove={$_GET['remove']}&confirm=true'\">\n";
		echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onClick=\"window.location='$returnurl'\"></p>\n";
		require("../footer.php");
		exit;
	}
} else if (isset($_GET['move']) && $isteacher) { //moving post to a different forum   NEW ONE
	if (isset($_POST['movetype'])) {
		$threadid = intval($_POST['thread']);
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
		if ($caller=='thread') {
			$threadid = $_GET['move'];
		}
		$placeinhead .= '<script type="text/javascript">function toggleforumselect(v) { 
			if (v==0) {document.getElementById("fsel").style.display="block";document.getElementById("tsel").style.display="none";}
			if (v==1) {document.getElementById("tsel").style.display="block";document.getElementById("fsel").style.display="none";}
			}</script>';
		$pagetitle = "Move Thread";
		
		require("../header.php");
		echo "<div class=breadcrumb>$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">$coursename</a> ";
		if ($caller != 'thread') {echo "&gt; <a href=\"thread.php?page=$page&cid=$cid&forum=$forumid\">Forum Topics</a> ";}
		echo "&gt; <a href=\"$returnurl\">$returnname</a> &gt; Move Thread</div>";
		$query = "SELECT parent FROM imas_forum_posts WHERE id='{$_GET['move']}'";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		if (mysql_result($result,0,0)==0) {
			$ishead = true;
			echo "<h3>Move Thread</h3>\n";
		} else {
			$ishead = false;
			echo "<h3>Move Post</h3>\n";
		}
		
		echo "<form method=post action=\"$returnurl&move={$_GET['move']}\">";
		echo '<input type="hidden" name="thread" value="'.$threadid.'"/>';
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
			if ($ishead && $row[0]==$threadid) {continue;}
			echo "<input type=\"radio\" name=\"movetot\" value=\"{$row[0]}\" ";
			if ($row[0]==$threadid) {echo 'checked="checked"';}
			echo "/>{$row[1]}<br/>";
		}
		echo '</div>';
		
		echo "<p><input type=submit value=\"Move\">\n";
		echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onClick=\"window.location='$returnurl'\"></p>\n";
		echo "</form>";
		require("../footer.php");
		exit;
		
	}
}
?>

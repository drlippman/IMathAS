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
	
	$threadsperpage = $listperpage;
	
	$cid = $_GET['cid'];
	$forumid = $_GET['forum'];
	if (!isset($_GET['page']) || $_GET['page']=='') {
		$page = 1;
	} else {
		$page = $_GET['page'];
	}
	
	if ($isteacher && isset($_POST['score'])) {
		$existingscores = array();
		$query = "SELECT refid,id FROM imas_grades WHERE gradetype='forum' AND gradetypeid='$forumid'";
		$res = mysql_query($query) or die("Query failed : $query " . mysql_error());
		while ($row = mysql_fetch_row($res)) {
			$existingscores[$row[0]] = $row[1];
		}
		$postuserids = array();
		$refids = "'".implode("','",array_keys($_POST['score']))."'";
		$query = "SELECT id,userid FROM imas_forum_posts WHERE id IN ($refids)";
		$res = mysql_query($query) or die("Query failed : $query " . mysql_error());
		while ($row = mysql_fetch_row($res)) {
			$postuserids[$row[0]] = $row[1];
		}
		foreach($_POST['score'] as $k=>$v) {
			if (isset($_POST['feedback'][$k])) {
				$feedback = $_POST['feedback'][$k];
			} else {
				$feedback = '';
			}
			if (is_numeric($v)) {
				if (isset($existingscores[$k])) {
					$query = "UPDATE imas_grades SET score='$v',feedback='$feedback' WHERE id='{$existingscores[$k]}'";
				} else {
					$query = "INSERT INTO imas_grades (gradetype,gradetypeid,userid,refid,score,feedback) VALUES ";
					$query .= "('forum','$forumid','{$postuserids[$k]}','$k','$v','$feedback')";
				}
				mysql_query($query) or die("Query failed : $query " . mysql_error());
			} else {
				if (isset($existingscores[$k])) {
					$query = "DELETE FROM imas_grades WHERE id='{$existingscores[$k]}'";
					mysql_query($query) or die("Query failed : $query " . mysql_error());
				}
			}
		}
		if (isset($_POST['save']) && $_POST['save']=='Save Grades and View Previous') {
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/posts.php?page=$page&cid=$cid&forum=$forumid&thread={$_POST['prevth']}");
		} else if (isset($_POST['save']) && $_POST['save']=='Save Grades and View Next') {
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/posts.php?page=$page&cid=$cid&forum=$forumid&thread={$_POST['nextth']}");
		} else {
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/thread.php?page=$page&cid=$cid&forum=$forumid");
		}
			exit;	
	}
	$query = "SELECT name,postby,settings,groupsetid,sortby FROM imas_forums WHERE id='$forumid'";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$forumname = mysql_result($result,0,0);
	$postby = mysql_result($result,0,1);
	$allowmod = ((mysql_result($result,0,2)&2)==2);
	$allowdel = (((mysql_result($result,0,2)&4)==4) || $isteacher);
	$groupsetid = mysql_result($result,0,3);
	$sortby = mysql_result($result,0,4);
	$dofilter = false;
	$now = time();
	$grpqs = '';
	if ($groupsetid>0) {
		if (isset($_GET['ffilter'])) {
			$sessiondata['ffilter'.$forumid] = $_GET['ffilter'];
			writesessiondata();
		}
		if (!$isteacher) {
			$query = 'SELECT i_sg.id FROM imas_stugroups AS i_sg JOIN imas_stugroupmembers as i_sgm ON i_sgm.stugroupid=i_sg.id ';
			$query .= "WHERE i_sgm.userid='$userid' AND i_sg.groupsetid='$groupsetid'";
			$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			if (mysql_num_rows($result)>0) {
				$groupid = mysql_result($result,0,0);
			} else {
				$groupid=0;
			}
			$dofilter = true;
		} else {
			if (isset($sessiondata['ffilter'.$forumid]) && $sessiondata['ffilter'.$forumid]>-1) {
				$groupid = $sessiondata['ffilter'.$forumid];
				$dofilter = true;
				$grpqs = "&grp=$groupid";
			} else {
				$groupid = 0;
			}
		}
		if ($dofilter) {
			$limthreads = array();
			if ($isteacher || $groupid==0) {
				$query = "SELECT id FROM imas_forum_threads WHERE stugroupid='$groupid'";
			} else {
				$query = "SELECT id FROM imas_forum_threads WHERE stugroupid=0 OR stugroupid='$groupid'";
			}
			$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				$limthreads[] = $row[0];
			}
			if (count($limthreads)==0) {
				$limthreads = '0';
			} else {
				$limthreads = implode(',',$limthreads);
			}
		}
	} else {
		$groupid = 0;
	}
	
	
	
	
	if (isset($_GET['search']) && trim($_GET['search'])!='') {
		require("../header.php");
		echo "<div class=breadcrumb>$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">$coursename</a> &gt; ";
		echo "<a href=\"thread.php?page=$page&cid=$cid&forum=$forumid\">Forum Topics</a> &gt; Search Results</div>\n";
	
		echo "<h2>Forum Search Results</h2>";
		
		$safesearch = $_GET['search'];
		$safesearch = str_replace(' and ', ' ',$safesearch);
		$searchterms = explode(" ",$safesearch);
		$searchlikes = "(imas_forum_posts.message LIKE '%".implode("%' AND imas_forum_posts.message LIKE '%",$searchterms)."%')";
		$searchlikes2 = "(imas_forum_posts.subject LIKE '%".implode("%' AND imas_forum_posts.subject LIKE '%",$searchterms)."%')";
		$searchlikes3 = "(imas_users.LastName LIKE '%".implode("%' AND imas_users.LastName LIKE '%",$searchterms)."%')";
		if (isset($_GET['allforums'])) {
			$query = "SELECT imas_forums.id,imas_forum_posts.threadid,imas_forum_posts.subject,imas_forum_posts.message,imas_users.FirstName,imas_users.LastName,imas_forum_posts.postdate,imas_forums.name FROM imas_forum_posts,imas_forums,imas_users ";
			$query .= "WHERE imas_forum_posts.forumid=imas_forums.id ";
			if (!$isteacher) {
				$query .= "AND (imas_forums.avail=2 OR (imas_forums.avail=1 AND imas_forums.startdate<$now && imas_forums.enddate>$now)) ";
			}
			$query .= "AND imas_users.id=imas_forum_posts.userid AND imas_forums.courseid='$cid' AND ($searchlikes OR $searchlikes2 OR $searchlikes3)";
		} else {
			$query = "SELECT imas_forum_posts.forumid,imas_forum_posts.threadid,imas_forum_posts.subject,imas_forum_posts.message,imas_users.FirstName,imas_users.LastName,imas_forum_posts.postdate ";
			$query .= "FROM imas_forum_posts,imas_users WHERE imas_forum_posts.forumid='$forumid' AND imas_users.id=imas_forum_posts.userid AND ($searchlikes OR $searchlikes2 OR $searchlikes3)";
		}
		if ($dofilter) {
			$query .= " AND imas_forum_posts.threadid IN ($limthreads)";
		}
		$query .= " ORDER BY imas_forum_posts.postdate DESC";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			echo "<div class=block>";
			echo "<b>{$row[2]}</b>";
			if (isset($_GET['allforums'])) {
				echo ' (in '.$row[7].')';
			}
			echo "<br/>Posted by: {$row[4]} {$row[5]}, ";
			echo tzdate("F j, Y, g:i a",$row[6]);
			
			echo "</div><div class=blockitems>";
			echo filter($row[3]);
			echo "<p><a href=\"posts.php?cid=$cid&forum={$row[0]}&thread={$row[1]}\">Show full thread</a></p>";
			echo "</div>\n";
		}
		require("../footer.php");
		exit;
	}
	
	if (isset($_GET['markallread'])) {
		$query = "SELECT DISTINCT threadid FROM imas_forum_posts WHERE forumid='$forumid'";
		if ($dofilter) {
			$query .= " AND threadid IN ($limthreads)";
		}
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
	$caller = 'thread';
	if (isset($_GET['modify']) || isset($_GET['remove']) || isset($_GET['move'])) {
		require("posthandler.php");
	}
	/*
	
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
			if (isset($_POST['tag'])) {
				$tag = $_POST['tag'];
			} else {
				$tag = '';
			}
			require_once("../includes/htmLawed.php");
			$htmlawedconfig = array('elements'=>'*-script-form');
			$_POST['message'] = addslashes(htmLawed(stripslashes($_POST['message']),$htmlawedconfig));
			$_POST['subject'] = strip_tags($_POST['subject']);
			
			if ($_GET['modify']=="new") {	
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
				
				$query = "SELECT iu.email FROM imas_users AS iu,imas_forum_subscriptions AS ifs WHERE ";
				$query .= "iu.id=ifs.userid AND ifs.forumid='$forumid' AND iu.id<>'$userid'";
				if ($dofilter) {
					$query .= " AND (iu.id IN (SELECT userid FROM imas_stugroupmembers WHERE stugroupid='$groupid') OR iu.id IN (SELECT userid FROM imas_teachers WHERE courseid='$cid'))";
				}
				$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
				if (mysql_num_rows($result)>0) {
					$headers  = 'MIME-Version: 1.0' . "\r\n";
					$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
					$headers .= "From: $sendfrom\r\n";
					$message  = "<h4>This is an automated message.  Do not respond to this email</h4>\r\n";
					$message .= "<p>A new thread has been started in forum $forumname in course $coursename</p>\r\n";
					$message .= "<p>Subject:".stripslashes($_POST['subject'])."</p>";
					$message .= "<p>Poster: $userfullname</p>";
					$message .= "<a href=\"". $urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/posts.php?cid=$cid&forum=$forumid&thread=$threadid\">";
					$message .= "View Posting</a>\r\n";
				}
				while ($row = mysql_fetch_row($result)) {
					$row[0] = trim($row[0]);
					if ($row[0]!='' && $row[0]!='none@none.com') {
						mail($row[0],'New forum post notification',$message,$headers);
					}
				}
				$_GET['modify'] = $threadid;
				$files = array();
			} else {
				$query = "UPDATE imas_forum_posts SET subject='{$_POST['subject']}',message='{$_POST['message']}',posttype='$type',replyby=$replyby,isanon='$isanon',tag='$tag' ";
				$query .= "WHERE id='{$_GET['modify']}'";
				if (!$isteacher) { $query .= " AND userid='$userid'";}
				mysql_query($query) or die("Query failed : $query " . mysql_error());
				if ($groupsetid>0 && $isteacher && isset($_POST['stugroup'])) {
					$groupid = $_POST['stugroup'];
					$query = "UPDATE imas_forum_threads SET stugroupid='$groupid' WHERE id='{$_GET['modify']}'";
					mysql_query($query) or die("Query failed : $query " . mysql_error());
				}
				
				$query = "SELECT files FROM imas_forum_posts WHERE id='{$_GET['modify']}'";
				$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
				$files = explode('@@',mysql_result($result,0,0));
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
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/thread.php?page=$page&cid=$cid&forum=$forumid");
			exit;
		} else { //display mod
			$pagetitle = "Add/Modify Thread";
			$useeditor = "message";
			$placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/DatePicker.js\"></script>";
			$loadgraphfilter = true;
			require("../header.php");
			echo "<div class=breadcrumb>$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">$coursename</a> ";
			echo "&gt; <a href=\"thread.php?page=$page&cid=$cid&forum=$forumid\">Forum Topics</a> &gt; ";
			$notice = '';
			if ($_GET['modify']!="new") {
				echo "Modify Thread</div>\n";
				if ($groupsetid>0) {
					$query = "SELECT stugroupid FROM imas_forum_threads WHERE id='{$_GET['modify']}'";
					$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
					$curstugroupid = mysql_result($result,0,0);
				}
				$query = "SELECT * from imas_forum_posts WHERE id='{$_GET['modify']}'";
				$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
				$line = mysql_fetch_array($result, MYSQL_ASSOC);
				echo "<h3>Modify Thread - \n";
				$line['subject'] = str_replace('"','&quot;',$line['subject']);
				$replyby = $line['replyby'];
			} else {
				echo "Add Thread</div>\n";
				$line['subject'] = "";
				$line['message'] = "";
				$line['posttype'] = 0;
				$line['files'] = '';
				$line['tag'] = '';
				$curstugroupid = 0;
				$replyby = null;
				echo "<h3>Add Thread - \n";
				if (isset($_GET['quoteq'])) {
					require_once("../assessment/displayq2.php");
					$parts = explode('-',$_GET['quoteq']);
					$message = displayq($parts[0],$parts[1],$parts[2],false,false,0,true);
					$message = printfilter(forcefiltergraph($message));
					$message = preg_replace('/(`[^`]*`)/',"<span class=\"AM\">$1</span>",$message);
					
					$line['message'] = '<p> </p><br/><hr/>'.$message;
	
					if (isset($parts[3])) {  
						$query = "SELECT name FROM imas_assessments WHERE id='".intval($parts[3])."'";
						$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
						$line['subject'] = 'Question about #'.($parts[0]+1).' in '.str_replace('"','&quot;',mysql_result($result,0,0));
						
						$query = "SELECT ift.id FROM imas_forum_posts AS ifp JOIN imas_forum_threads AS ift ON ifp.threadid=ift.id AND ifp.parent=0 ";
						$query .= "WHERE ifp.subject='".addslashes($line['subject'])."' AND ift.forumid='$forumid'";
						if ($groupsetid >0 && !$isteacher) {
							$query .= " AND ift.stugroupid='$groupid'";
						}
						$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
						if (mysql_num_rows($result)>0) {
							$notice =  '<br/><span style="color:red;font-weight:bold">This question has already been posted about.</span><br/>You may want to read the ';
							$notice .=   'existing threads before re-posting the question.';
							while ($row = mysql_fetch_row($result)) {
								$notice .=  "<br/><a href=\"posts.php?cid=$cid&forum=$forumid&thread={$row[0]}\">{$line['subject']}</a>";
							}
						}
					}	
				}
			}
			$query = "SELECT name,settings,forumtype,taglist FROM imas_forums WHERE id='$forumid'";
			$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			$allowanon = mysql_result($result,0,1)%2;
			echo mysql_result($result,0,0).'</h3>';
			$forumtype = mysql_result($result,0,2);
			$taglist = mysql_result($result,0,3);
			
			
			
			if ($replyby!=null && $replyby<2000000000 && $replyby>0) {
				$replybydate = tzdate("m/d/Y",$replyby);
				$replybytime = tzdate("g:i a",$replyby);	
			} else {
				$replybydate = tzdate("m/d/Y",time()+7*24*60*60);
				$replybytime = tzdate("g:i a",time()+7*24*60*60);
			}
			echo "<form enctype=\"multipart/form-data\" method=\"post\" action=\"thread.php?page=$page&cid=$cid&forum=$forumid&modify={$_GET['modify']}\">\n";
			echo "<span class=form><label for=\"subject\">Subject:</label></span>";
			echo "<span class=formright><input type=text size=50 name=subject id=subject value=\"{$line['subject']}\">$notice</span><br class=form>\n";
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
			if ($taglist!='') {
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
				echo ">Displayed at top and students can only see their own replies\n";
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
					
			} else {
				if ($allowanon==1) {
					echo "<span class=form>Post Anonymously:</span><span class=formright>";
					echo "<input type=checkbox name=\"postanon\" value=1 ";
					if ($line['isanon']==1) {echo "checked=1";}
					echo "></span><br class=form/>";
				}
			}
			echo "<div class=submit><input type=submit value='Submit'></div>\n";
			echo '</form><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>';
			require("../footer.php");
			exit;
		}
	} else if (isset($_GET['remove']) && $allowdel) { //isteacher) { //removing thread
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
				$query = "DELETE FROM imas_forum_posts WHERE id='{$_GET['remove']}'";
				mysql_query($query) or die("Query failed : $query " . mysql_error());
				
				$query = "DELETE FROM imas_forum_threads WHERE id='{$_GET['remove']}'";
				mysql_query($query) or die("Query failed : $query " . mysql_error());
	
				$query = "DELETE FROM imas_forum_posts WHERE threadid='{$_GET['remove']}'";
				mysql_query($query) or die("Query failed : $query " . mysql_error());
				
				$query = "DELETE FROM imas_forum_views WHERE threadid='{$_GET['remove']}'";
				mysql_query($query) or die("Query failed : $query " . mysql_error());
			}
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/thread.php?page=$page&cid=$cid&forum=$forumid");
			exit;
		} else {
			$pagetitle = "Remove Thread";
			require("../header.php");
			if (!$isteacher) {
				$query = "SELECT id FROM imas_forum_posts WHERE parent='{$_GET['remove']}'";
				$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
				if (mysql_num_rows($result)>0) {
					echo "Someone has replied to this post, so you cannot remove it.  <a href=\"thread.php?page=$page&cid=$cid&forum=$forumid\">Back</a>";
					require("../footer.php");
					exit;
				}
			} 
			echo "<div class=breadcrumb>$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">$coursename</a> ";
			echo "&gt; <a href=\"thread.php?page=$page&cid=$cid&forum=$forumid\">Forum Topics</a> &gt; Remove Thread</div>";
			echo "<h3>Remove Thread</h3>\n";
			echo "<p>Are you SURE you want to remove this Thread and all enclosed posts?</p>\n";

			echo "<p><input type=button value=\"Yes, Remove\" onClick=\"window.location='thread.php?page=$page&cid=$cid&forum=$forumid&remove={$_GET['remove']}&confirm=true'\">\n";
			echo "<input type=button value=\"Nevermind\" onClick=\"window.location='thread.php?page=$page&cid=$cid&forum=$forumid'\"></p>\n";
			require("../footer.php");
			exit;
		}
	}
	*/
	$pagetitle = "Threads";
	$placeinhead = "<style type=\"text/css\">\n@import url(\"$imasroot/forums/forums.css\");\n</style>\n";
	$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/thread.js\"></script>";
	$placeinhead .= "<script type=\"text/javascript\">var AHAHsaveurl = '" . $urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/savetagged.php?cid=$cid';</script>";
	require("../header.php");
	
	
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">$coursename</a> &gt; Forum Topics</div>\n";
	echo '<div id="headerthread" class="pagetitle"><h2>Forum: '.$forumname.'</h2></div>';

	$query = "SELECT threadid,COUNT(id) AS postcount,MAX(postdate) AS maxdate FROM imas_forum_posts ";
	$query .= "WHERE forumid='$forumid' ";
	if ($dofilter) {
		$query .= " AND threadid IN ($limthreads)";
	}
	$query .= "GROUP BY threadid";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$postcount = array();
	$maxdate = array();
	
	while ($row = mysql_fetch_row($result)) {
		$postcount[$row[0]] = $row[1] -1;
		$maxdate[$row[0]] = $row[2];
	}
	
	$query = "SELECT threadid,lastview,tagged FROM imas_forum_views WHERE userid='$userid'";
	if ($dofilter) {
		$query .= " AND threadid IN ($limthreads)";
	}
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$lastview = array();
	$tags = array();
	while ($row = mysql_fetch_row($result)) {
		$lastview[$row[0]] = $row[1];
		if ($row[2]==1) {
			$tags[$row[0]] = 1;
		}
	}
	$taggedlist = implode(',',array_keys($tags));
	//make new list
	$newpost = array();
	foreach (array_keys($maxdate) as $tid) {
		if (!isset($lastview[$tid]) || $lastview[$tid]<$maxdate[$tid]) {
			$newpost[] = $tid;
		}
	}
	$newpostlist = implode(',',$newpost);
	if ($page==-1 && count($newpost)==0) {
		$page = 1;
	} else if ($page==-2 && count($tags)==0) {
		$page = 1;
	}
	$prevnext = '';
	if ($page>0) {
		$query = "SELECT COUNT(id) FROM imas_forum_posts WHERE parent=0 AND forumid='$forumid'";
		if ($dofilter) {
			$query .= " AND threadid IN ($limthreads)";
		}
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		$numpages = ceil(mysql_result($result,0,0)/$threadsperpage);
		
		if ($numpages > 1) {
			$prevnext .= "Page: ";
			if ($page < $numpages/2) {
				$min = max(2,$page-4);
				$max = min($numpages-1,$page+8+$min-$page);
			} else {
				$max = min($numpages-1,$page+4);
				$min = max(2,$page-8+$max-$page);
			}
			if ($page==1) {
				$prevnext .= "<b>1</b> ";
			} else {
				$prevnext .= "<a href=\"thread.php?page=1&cid=$cid&forum=$forumid\">1</a> ";
			}
			if ($min!=2) { $prevnext .= " ... ";}
			for ($i = $min; $i<=$max; $i++) {
				if ($page == $i) {
					$prevnext .= "<b>$i</b> ";
				} else {
					$prevnext .= "<a href=\"thread.php?page=$i&cid=$cid&forum=$forumid\">$i</a> ";
				}
			}
			if ($max!=$numpages-1) { $prevnext .= " ... ";}
			if ($page == $numpages) {
				$prevnext .= "<b>$numpages</b> ";
			} else {
				$prevnext .= "<a href=\"thread.php?page=$numpages&cid=$cid&forum=$forumid\">$numpages</a> ";
			}
			$prevnext .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
			
			if ($page>1) {
				$prevnext .= "<a href=\"thread.php?page=".($page-1)."&cid=$cid&forum=$forumid\">Previous</a> ";
			} else {
				$prevnext .= "Previous ";
			}
			if ($page < $numpages) {
				$prevnext .= "| <a href=\"thread.php?page=".($page+1)."&cid=$cid&forum=$forumid\">Next</a> ";
			} else {
				$prevnext .= "| Next ";
			}
			
			echo "<div>$prevnext</div>";
		}
	}
	echo "<form method=get action=\"thread.php\">";
	echo "<input type=hidden name=page value=\"$page\"/>";
	echo "<input type=hidden name=cid value=\"$cid\"/>";
	echo "<input type=hidden name=forum value=\"$forumid\"/>";
	
?>
	Search: <input type=text name="search" /> <input type=checkbox name="allforums" />All forums in course? <input type="submit" value="Search"/>
	</form>
<?php
	if ($isteacher && $groupsetid>0) {
		if (isset( $sessiondata['ffilter'.$forumid])) {
			$curfilter = $sessiondata['ffilter'.$forumid];
		} else {
			$curfilter = -1;
		}
		
		$groupnames = array();
		$groupnames[0] = "Non-group-specific";
		$query = "SELECT id,name FROM imas_stugroups WHERE groupsetid='$groupsetid' ORDER BY id";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$grpnums = 1;
		while ($row = mysql_fetch_row($result)) {
			if ($row[1] == 'Unnamed group') { 
				$row[1] .= " $grpnums";
				$grpnums++;
			}
			$groupnames[$row[0]] = $row[1];
		}
		natsort($groupnames);
		
		$query = "SELECT id,name FROM imas_stugroups WHERE groupsetid='$groupsetid' ORDER BY id";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		echo "<script type=\"text/javascript\">";
		echo 'function chgfilter() {';
		echo '  var ffilter = document.getElementById("ffilter").value;';
		echo "  window.location = \"thread.php?page=$pages&cid=$cid&forum=$forumid&ffilter=\"+ffilter;";
		echo '}';
		echo '</script>';
		echo '<p>Show posts for group: <select id="ffilter" onChange="chgfilter()"><option value="-1" ';
		if ($curfilter==-1) { echo 'selected="1"';}
		echo '>All groups</option>';
		foreach ($groupnames as $gid=>$gname) {
			echo "<option value=\"$gid\" ";
			if ($curfilter==$gid) { echo 'selected="1"';}
			echo ">$gname</option>";
		}
		echo '</select></p>';
	}
	echo '<p>';
	$toshow = array();
	if (($myrights > 5 && time()<$postby) || $isteacher) {
		$toshow[] =  "<a href=\"thread.php?page=$page&cid=$cid&forum=$forumid&modify=new\">Add New Thread</a>\n";
	}
	if ($isteacher) {
		$toshow[] =  "<a href=\"postsbyname.php?page=$page&cid=$cid&forum=$forumid\">List Posts by Name</a>";
	}
	
	if ($page<0) {
		$toshow[] =  "<a href=\"thread.php?cid=$cid&forum=$forumid&page=1\">Show All</a>";
	} else {
		if (count($newpost)>0) {
			$toshow[] =  "<a href=\"thread.php?cid=$cid&forum=$forumid&page=-1\">Limit to New</a>";
		}
		$toshow[] =  "<a href=\"thread.php?cid=$cid&forum=$forumid&page=-2\">Limit to Flagged</a>";
	} 
	if (count($newpost)>0) {
		$toshow[] =  "<a href=\"thread.php?page=$page&cid=$cid&forum=$forumid&markallread=true\">Mark all Read</a>";
	}
	
	echo implode(' | ',$toshow);
	
	echo "</p>";
	
?>
	<table class=forum>
	<thead>
	<tr><th>Topic</th>
<?php
	if ($isteacher && $groupsetid>0 && !$dofilter) {
		echo '<th>Group</th>';
	}
?>
	<th>Replies</th><th>Views (Unique)</th><th>Last Post Date</th></tr>
	</thead>
	<tbody>
<?php
	
	
	$query = "SELECT imas_forum_posts.id,count(imas_forum_views.userid) FROM imas_forum_views,imas_forum_posts ";
	$query .= "WHERE imas_forum_views.threadid=imas_forum_posts.id AND imas_forum_posts.parent=0 AND ";
	$query .= "imas_forum_posts.forumid='$forumid' ";
	if ($dofilter) {
		$query .= "AND imas_forum_posts.threadid IN ($limthreads) ";
	}
	if ($page==-1) {
		$query .= "AND imas_forum_posts.threadid IN ($newpostlist) ";
	} else if ($page==-2) {
		$query .= "AND imas_forum_posts.threadid IN ($taggedlist) ";
	}
	$query .= "GROUP BY imas_forum_posts.id";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$uniqviews[$row[0]] = $row[1]-1;
	}
	
	$query = "SELECT imas_forum_posts.*,imas_forum_threads.views as tviews,imas_users.LastName,imas_users.FirstName,imas_forum_threads.stugroupid FROM imas_forum_posts,imas_users,imas_forum_threads WHERE ";
	$query .= "imas_forum_posts.userid=imas_users.id AND imas_forum_posts.threadid=imas_forum_threads.id AND imas_forum_posts.parent=0 AND imas_forum_posts.forumid='$forumid' ";	
	
	if ($dofilter) {
		$query .= "AND imas_forum_posts.threadid IN ($limthreads) ";
	}
	if ($page==-1) {
		$query .= "AND imas_forum_posts.threadid IN ($newpostlist) ";
	} else if ($page==-2) {
		$query .= "AND imas_forum_posts.threadid IN ($taggedlist) ";
	}
	if ($sortby==0) {
		$query .= "ORDER BY imas_forum_posts.posttype DESC,imas_forum_posts.id DESC ";
	} else if ($sortby==1) {
		$query .= "ORDER BY imas_forum_posts.posttype DESC,imas_forum_threads.lastposttime DESC ";
	}
	$offset = ($page-1)*$threadsperpage;
	if ($page>0) {
		$query .= "LIMIT $offset,$threadsperpage";// OFFSET $offset";
	}
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	if (mysql_num_rows($result)==0) {
		echo '<tr><td colspan='.(($isteacher && $grpaid>0 && !$dofilter)?5:4).'>No posts have been made yet.  Click Add New Thread to start a new discussion</td></tr>';
	}
	while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		if (isset($postcount[$line['id']])) {
			$posts = $postcount[$line['id']];
			$lastpost = tzdate("F j, Y, g:i a",$maxdate[$line['id']]);
		} else {
			$posts = 0;
			$lastpost = '';
		}
		echo "<tr id=\"tr{$line['id']}\"";
		if ($line['posttype']>0) {
			echo "class=sticky";
		} else if (isset($tags[$line['id']])) {
			echo "class=tagged";
		}
		echo "><td>";
		echo "<span class=right>\n";
		if ($line['tag']!='') { //category tags
			echo '<span class="forumcattag">'.$line['tag'].'</span> ';
		}
		if ($line['posttype']==0) {
			if (isset($tags[$line['id']])) {
				echo "<img class=\"pointer\" id=\"tag{$line['id']}\" src=\"$imasroot/img/flagfilled.gif\" onClick=\"toggletagged({$line['id']});return false;\" />";
			} else {
				echo "<img class=\"pointer\" id=\"tag{$line['id']}\" src=\"$imasroot/img/flagempty.gif\" onClick=\"toggletagged({$line['id']});return false;\" />";
			}
		}
		if ($isteacher) {
			echo "<a href=\"thread.php?page=$page&cid=$cid&forum={$line['forumid']}&move={$line['id']}\">Move</a> ";
		}
		if ($isteacher || ($line['userid']==$userid && $allowmod && time()<$postby)) {
			echo "<a href=\"thread.php?page=$page&cid=$cid&forum={$line['forumid']}&modify={$line['id']}\">Modify</a> ";
		} 
		if ($isteacher || ($allowdel && $line['userid']==$userid && $posts==0)) {
			echo "<a href=\"thread.php?page=$page&cid=$cid&forum={$line['forumid']}&remove={$line['id']}\">Remove</a>";
		}
		echo "</span>\n";
		if ($line['isanon']==1) {
			$name = "Anonymous";
		} else {
			$name = "{$line['LastName']}, {$line['FirstName']}";
		}
		echo "<b><a href=\"posts.php?cid=$cid&forum=$forumid&thread={$line['id']}&page=$page$grpqs\">{$line['subject']}</a></b>: $name";
		
		echo "</td>\n";
		if ($isteacher && $groupsetid>0 && !$dofilter) {
			echo '<td class=c>'.$groupnames[$line['stugroupid']].'</td>';
		}
		
		echo "<td class=c>$posts</td><td class=c>{$line['tviews']} ({$uniqviews[$line['id']]})</td><td class=c>$lastpost ";
		if ($lastpost=='' || $maxdate[$line['id']]>$lastview[$line['id']]) {
			echo "<span style=\"color: red;\">New</span>";
		}
		echo "</td></tr>\n";
	}
?>
	</tbody>
	</table>
<?php
	if (($myrights > 5 && time()<$postby) || $isteacher) {
		echo "<p><a href=\"thread.php?page=$page&cid=$cid&forum=$forumid&modify=new\">Add New Thread</a></p>\n";
	}
	if ($prevnext!='') {
		echo "<p>$prevnext</p>";
	}
	
	require("../footer.php");
?>

<?php
	//Displays Message list
	//(c) 2006 David Lippman
	/*
isread:
# to  frm
0 NR  --
1 R   --
2 DR  --
3 DNR --
4 NR  D
5 R   D

0 - not read
1 - read
2 - deleted not read
3 - deleted and read
4 - deleted by sender
5 - deleted by sender,read

isread is bitwise:
1      2         4                   8
Read   Deleted   Deleted by Sender   Tagged

If (isread&2)==2 && (isread&4)==4  then should be deleted
  
	*/
	require("../validate.php");
	if ($cid!=0 && !isset($teacherid) && !isset($tutorid) && !isset($studentid)) {
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
	$cansendmsgs = false;
	$threadsperpage = $listperpage;
	
	$cid = $_GET['cid'];
	if (!isset($_GET['page']) || $_GET['page']=='') {
		$page = 1;
	} else {
		$page = $_GET['page'];
	}
	if ($page==-2) {
		$limittotagged = 1;
	} else {
		$limittotagged = 0;
	}
	if (isset($_GET['filtercid'])) {
		$filtercid = $_GET['filtercid'];
	} else if ($cid!='admin' && $cid>0) {
		$filtercid = $cid;
	} else {
		$filtercid = 0;
	}
	if (isset($_GET['filteruid'])) {
		$filteruid = intval($_GET['filteruid']);
	} else {
		$filteruid = 0;
	}
	$type = $_GET['type'];
	
	if (isset($_GET['add'])) {
		if (isset($_POST['subject'])) {
			require_once("../includes/htmLawed.php");
			$htmlawedconfig = array('elements'=>'*-script');
			$_POST['message'] = addslashes(htmLawed(stripslashes($_POST['message']),$htmlawedconfig));
			$_POST['subject'] = addslashes(htmlentities(stripslashes($_POST['subject'])));
			
			$query = "INSERT INTO imas_msgs (title,message,msgto,msgfrom,senddate,isread,courseid) VALUES ";
			$now = time();
			$query .= "('{$_POST['subject']}','{$_POST['message']}','{$_POST['to']}','$userid',$now,0,'{$_POST['courseid']}')";
			mysql_query($query) or die("Query failed : $query " . mysql_error());
			$msgid = mysql_insert_id();
			
			if ($_GET['replyto']>0) {
				$query = "UPDATE imas_msgs SET replied=1";
				if (isset($_POST['sendunread'])) {
					$query .= ',isread=(isread&~1)';
				}
				$query .= " WHERE id='{$_GET['replyto']}'";
				mysql_query($query) or die("Query failed : $query " . mysql_error());
				$query = "SELECT baseid FROM imas_msgs WHERE id='{$_GET['replyto']}'";
				$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
				$baseid = mysql_result($result,0,0);
				if ($baseid==0) {
					$baseid = $_GET['replyto'];
				}
				$query = "UPDATE imas_msgs SET baseid='$baseid',parent='{$_GET['replyto']}' WHERE id='$msgid'";
				mysql_query($query) or die("Query failed : $query " . mysql_error());
			} 
			$query = "SELECT name FROM imas_courses WHERE id='{$_POST['courseid']}'";
			$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			$cname = mysql_result($result,0,0);
			
			$query = "SELECT msgnotify,email FROM imas_users WHERE id='{$_POST['to']}'";
			$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			if (mysql_result($result,0,0)==1) {
				$email = mysql_result($result,0,1);
				$query = "SELECT FirstName,LastName FROM imas_users WHERE id='$userid'";
				$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
				$from = mysql_result($result,0,0).' '.mysql_result($result,0,1);
				$headers  = 'MIME-Version: 1.0' . "\r\n";
				$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
				$headers .= "From: $sendfrom\r\n";
				$message  = "<h4>This is an automated message.  Do not respond to this email</h4>\r\n";
				$message .= "<p>You've received a new message</p><p>From: $from<br />Course: $cname.</p>\r\n";
				$message .= "<p>Subject:".stripslashes($_POST['subject'])."</p>";
				$message .= "<a href=\"http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/viewmsg.php?cid=$cid&msgid=$msgid\">";
				$message .= "View Message</a></p>\r\n";
				$message .= "<p>If you do not wish to receive email notification of new messages, please ";
				$message .= "<a href=\"http://" . $_SERVER['HTTP_HOST'] . $imasroot . "/forms.php?action=chguserinfo\">click here to change your ";
				$message .= "user preferences</a></p>\r\n";
				mail($email,'New message notification',$message,$headers);
			}
			if ($type=='new') {
				header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/newmsglist.php?cid=$cid");
			} else {
				header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/msglist.php?page=$page&cid=$cid&filtercid=$filtercid");				
			}
			exit;
		} else {
			$pagetitle = "New Message";
			$useeditor = "message";
			$loadgraphfilter = true;
			$placeinhead = '<script type="text/javascript">
				function checkrecipient() {
					if (document.getElementById("to").value=="0") {
						alert("No recipient selected");
						return false;
					} else {
						return true;
					}
				}</script>';
			require("../header.php");
			echo "<div class=breadcrumb>$breadcrumbbase ";
			if ($cid>0 && (!isset($sessiondata['ltiitemtype']) || $sessiondata['ltiitemtype']!=0)) {
				echo "<a href=\"../course/course.php?cid=$cid\">$coursename</a> &gt; ";
			}
			if ($type=='sent') {
				echo " <a href=\"sentlist.php?page=$page&cid=$cid&filtercid=$filtercid\">Sent Message List</a> ";
			} else if ($type=='allstu') {
				echo " <a href=\"msglist.php?page=$page&cid=$cid&filtercid=$filtercid\">Message List</a> &gt; <a href=\"allstumsglist.php?page=$page&cid=$cid&filterstu=$filterstu\">Student Messages</a> ";
			} else if ($type=='new') {
				echo " <a href=\"newmsglist.php?cid=$cid\">New Message List</a> ";
			} else {
				echo " <a href=\"msglist.php?page=$page&cid=$cid&filtercid=$filtercid\">Message List</a> ";
			}
			
			if (isset($_GET['toquote'])) {
				$replyto = $_GET['toquote'];
			} else if (isset($_GET['replyto'])) {
				$replyto = $_GET['replyto'];
			} else {
				$replyto = 0;
			}
			
			if ($replyto > 0) {
				echo "&gt; <a href=\"viewmsg.php?page=$page&type=$type&cid=$cid&filtercid=$filtercid&msgid=$replyto\">Message</a> ";
				echo "&gt; Reply</div>";
				echo "<h2>Reply</h2>\n";
			} else {
				echo "&gt; New Message</div>";
				echo "<h2>New Message</h2>\n";
			}
			
			
			if ($cid>0) {
				$query = "SELECT msgset FROM imas_courses WHERE id='$cid'";
				$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
				$msgset = mysql_result($result,0,0);
				$msgmonitor = (floor($msgset/5)&1);
				$msgset = $msgset%5;
			}
			if (isset($_GET['toquote']) || isset($_GET['replyto'])) {
				$query = "SELECT title,message,courseid FROM imas_msgs WHERE id='$replyto'";
				$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
				$title = "Re: ".str_replace('"','&quot;',mysql_result($result,0,0));
				if (isset($_GET['toquote'])) {
					$message = mysql_result($result,0,1);
					$message = '<p> </p><br/><hr/>In reply to:<br/>'.$message;
				} else {
					$message = '';
				}
				$courseid = mysql_result($result,0,2);
			} else if (isset($_GET['quoteq'])) {
				require("../assessment/displayq2.php");
				$parts = explode('-',$_GET['quoteq']);
				$message = displayq($parts[0],$parts[1],$parts[2],false,false,0,true);
				$message = printfilter(forcefiltergraph($message));
				if (isset($CFG['GEN']['AWSforcoursefiles']) && $CFG['GEN']['AWSforcoursefiles'] == true) {
					require_once("../includes/filehandler.php");
					$message = preg_replace_callback('|'.$imasroot.'/filter/graph/imgs/([^\.]*?\.png)|', function ($matches) {
						$curdir = rtrim(dirname(__FILE__), '/\\');
						return relocatefileifneeded($curdir.'/../filter/graph/imgs/'.$matches[1], 'gimgs/'.$matches[1]);
					    }, $message);
				}
				$message = preg_replace('/(`[^`]*`)/',"<span class=\"AM\">$1</span>",$message);
				
				$message = '<p> </p><br/><hr/>'.$message;
				//$message .= '<span class="hidden">QREF::'.htmlentities($_GET['quoteq']).'</span>';
				$courseid = $cid;
				if (isset($parts[3])) {  //sending to instructor
					$query = "SELECT name FROM imas_assessments WHERE id='".intval($parts[3])."'";
					$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
					$title = 'Question about #'.($parts[0]+1).' in '.str_replace('"','&quot;',mysql_result($result,0,0));
					if ($_GET['to']=='instr') {
						unset($_GET['to']);
						$msgset = 1; //force instructor only list
					}
				} else {
					$title = '';
				}
			} else if (isset($_GET['title'])) {
				$title = $_GET['title'];
				$message = '';
				$courseid=$cid;
			} else {
				$title = '';
				$message = '';
				$courseid=$cid;
			}
			
			echo "<form method=post action=\"msglist.php?page=$page&type=$type&cid=$cid&add={$_GET['add']}&replyto=$replyto\" onsubmit=\"return checkrecipient();\">\n";
			echo "<span class=form>To:</span><span class=formright>\n";
			if (isset($_GET['to'])) {
				$query = "SELECT iu.LastName,iu.FirstName,iu.email,i_s.lastaccess,iu.hasuserimg FROM imas_users AS iu ";
				$query .= "LEFT JOIN imas_students AS i_s ON iu.id=i_s.userid AND i_s.courseid='$courseid' WHERE iu.id='{$_GET['to']}'";
				$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
				$row = mysql_fetch_row($result);
				echo $row[0].', '.$row[1];
				$ismsgsrcteacher = false;
				if ($courseid==$cid && $isteacher) {
					$ismsgsrcteacher = true;
				} else if ($courseid!=$cid) {
					$query = "SELECT id FROM imas_teachers WHERE userid='$userid' AND courseid='$courseid'";
					$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
					if (mysql_num_rows($result)!=0) {
						$ismsgsrcteacher = true;
					}
				}
				if ($ismsgsrcteacher) {
					echo " <a href=\"mailto:{$row[2]}\">email</a> | ";
					echo " <a href=\"$imasroot/course/gradebook.php?cid=$courseid&stu={$_GET['to']}\" target=\"_popoutgradebook\">gradebook</a>";
					if ($row[3]!=null) {
						echo " | Last login ".tzdate("F j, Y, g:i a",$row[3]);
					}
				}
				echo "<input type=hidden name=to value=\"{$_GET['to']}\"/>";
				$curdir = rtrim(dirname(__FILE__), '/\\');
				if (isset($_GET['to']) && $row[4]==1) {
					if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
						echo " <img style=\"vertical-align: middle;\" src=\"{$urlmode}s3.amazonaws.com/{$GLOBALS['AWSbucket']}/cfiles/userimg_sm{$_GET['to']}.jpg\"  onclick=\"togglepic(this)\" /><br/>";
					} else {
						echo " <img style=\"vertical-align: middle;\" src=\"$imasroot/course/files/userimg_sm{$_GET['to']}.jpg\"  onclick=\"togglepic(this)\" /><br/>";
					}
				}
			} else {
				echo "<select name=\"to\" id=\"to\">";
				echo '<option value="0">Select a recipient...</option>';
				if ($isteacher || $msgset<2) {
					$query = "SELECT imas_users.id,imas_users.FirstName,imas_users.LastName FROM ";
					$query .= "imas_users,imas_teachers WHERE imas_users.id=imas_teachers.userid AND ";
					$query .= "imas_teachers.courseid='$cid' ORDER BY imas_users.LastName";
					$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
					while ($row = mysql_fetch_row($result)) {
						echo "<option value=\"{$row[0]}\">{$row[2]}, {$row[1]}</option>";
					}
					$query = "SELECT imas_users.id,imas_users.FirstName,imas_users.LastName FROM ";
					$query .= "imas_users,imas_tutors WHERE imas_users.id=imas_tutors.userid AND ";
					$query .= "imas_tutors.courseid='$cid' ";
					if (!$isteacher && $studentinfo['section']!=null) {
						$query .= "AND (imas_tutors.section='".addslashes($studentinfo['section'])."' OR imas_tutors.section='') ";
					}
					$query .= "ORDER BY imas_users.LastName";
					$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
					while ($row = mysql_fetch_row($result)) {
						echo "<option value=\"{$row[0]}\">{$row[2]}, {$row[1]}</option>";
					} 
					
					
				}
				if ($isteacher || $msgset==0 || $msgset==2) {
					$query = "SELECT imas_users.id,imas_users.FirstName,imas_users.LastName FROM ";
					$query .= "imas_users,imas_students WHERE imas_users.id=imas_students.userid AND ";
					$query .= "imas_students.courseid='$cid' ORDER BY imas_users.LastName";
					$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
					while ($row = mysql_fetch_row($result)) {
						echo "<option value=\"{$row[0]}\">{$row[2]}, {$row[1]}</option>";
					}
				}
				echo "</select>";
			}
			
			
				
			echo "</span><br class=form />";
			echo "<input type=hidden name=courseid value=\"$courseid\"/>\n";
			echo "<span class=form><label for=\"subject\">Subject:</label></span>";
			echo "<span class=formright><input type=text size=50 name=subject id=subject value=\"$title\"></span><br class=form>\n";
			echo "<span class=form><label for=\"message\">Message:</label></span>";
			echo "<span class=left><div class=editor><textarea id=message name=message style=\"width: 100%;\" rows=20 cols=70>";
			echo htmlentities($message);
			echo "</textarea></div></span><br class=form>\n";
			if ($replyto>0) {
				echo '<span class="form"></span><span class="formright"><input type="checkbox" name="sendunread" value="1"/> '._('Mark original message unread').'</span><br class="form"/>';
			}
			echo '<div class="submit"><button type="submit" name="submit" value="send">'._('Send Message').'</button></div>';

			echo "</span></p>\n";
			
			if ($msgmonitor==1) {
				echo "<p><span class=red>Note</span>: Student-to-student messages may be monitored by your instructor</p>";
			}
			echo '</form>';
			require("../footer.php");
			exit;
		}
	}
	if (isset($_POST['unread'])) {
		if (count($_POST['checked'])>0) {
		$checklist = "'".implode("','",$_POST['checked'])."'";
		$query = "UPDATE imas_msgs SET isread=(isread&~1) WHERE id IN ($checklist) AND (isread&1)=1";
		mysql_query($query) or die("Query failed : $query " . mysql_error());
	}
	}
	if (isset($_POST['markread'])) {
		if (count($_POST['checked'])>0) {
		$checklist = "'".implode("','",$_POST['checked'])."'";
		$query = "UPDATE imas_msgs SET isread=(isread|1) WHERE id IN ($checklist) AND (isread&1)=0";
		mysql_query($query) or die("Query failed : $query " . mysql_error());
	}
	}
	if (isset($_POST['remove'])) {
		if (count($_POST['checked'])>0) {
		$checklist = "'".implode("','",$_POST['checked'])."'";
		$query = "DELETE FROM imas_msgs WHERE id IN ($checklist) AND (isread&4)=4";
		mysql_query($query) or die("Query failed : $query " . mysql_error());
		$query = "UPDATE imas_msgs SET isread=(isread|2) WHERE id IN ($checklist)";
		mysql_query($query) or die("Query failed : $query " . mysql_error());
		if ($type=='new') {
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/newmsglist.php?cid=$cid");
		} else {
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/msglist.php?page=$page&cid=$cid&filtercid=$filtercid");				
		}
		exit;
	}
	}
	if (isset($_GET['removeid'])) {
		$query = "DELETE FROM imas_msgs WHERE id='{$_GET['removeid']}' AND (isread&4)=4";
		mysql_query($query) or die("Query failed : $query " . mysql_error());
		$query = "UPDATE imas_msgs SET isread=(isread|2) WHERE id='{$_GET['removeid']}'";
		mysql_query($query) or die("Query failed : $query " . mysql_error());
	}
	
	$pagetitle = "Messages";
	$placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/msg.js\"></script>";
	$placeinhead .= "<script type=\"text/javascript\">var AHAHsaveurl = '". $urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/savetagged.php?cid=$cid';</script>";
	$placeinhead .= '<style type="text/css"> tr.tagged {background-color: #dff;}</style>';
	require("../header.php");
	$curdir = rtrim(dirname(__FILE__), '/\\');
	
	echo "<div class=breadcrumb>$breadcrumbbase ";
	if ($cid>0 && (!isset($sessiondata['ltiitemtype']) || $sessiondata['ltiitemtype']!=0)) {
		echo " <a href=\"../course/course.php?cid=$cid\">$coursename</a> &gt; ";
	}
	echo " Message List</div>";
	echo '<div id="headermsglist" class="pagetitle"><h2>Messages</h2></div>';

	if ($myrights > 5 && $cid>0) {
		$query = "SELECT msgset FROM imas_courses WHERE id='$cid'";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		$msgset = mysql_result($result,0,0);
		$msgmonitor = (floor($msgset/5)&1);
		$msgset = $msgset%5;
		if ($msgset<3 || $isteacher) {
			$cansendmsgs = true;
		}
	}	
	$actbar = array();
	
	if ($cansendmsgs) {
		$actbar[] = "<button type=\"button\" onclick=\"window.location.href='msglist.php?page=$page&cid=$cid&filtercid=$filtercid&filteruid=$filteruid&add=new'\">"._('Send New Message')."</button>";
	}
	if ($page==-2) {
		$actbar[] = "<a href=\"msglist.php?page=1&cid=$cid&filtercid=$filtercid&filteruid=$filteruid\">Show All</a>";
	} else {
		$actbar[] = "<a href=\"msglist.php?page=-2&cid=$cid&filtercid=$filtercid&filteruid=$filteruid\">Limit to Tagged</a>";
	}
	$actbar[] = "<a href=\"sentlist.php?cid=$cid\">Sent Messages</a>";
	
	if ($isteacher && $cid>0 && $msgmonitor==1) {
		$actbar[] = "<a href=\"allstumsglist.php?cid=$cid\">Student Messages</a>";
	}
	$actbar[] = '<input type="button" value="Pictures" onclick="rotatepics()" title="View/hide student pictures, if available" />';
	echo '<div class="cpmid">'.implode(' | ',$actbar).'</div>';
	
	
	$query = "SELECT COUNT(id) FROM imas_msgs WHERE msgto='$userid' AND (isread&2)=0";
	if ($filtercid>0) {
		$query .= " AND courseid='$filtercid'";
	}
	if ($filteruid>0) {
		$query .= " AND msgfrom='$filteruid'";
	}
	if ($limittotagged==1) {
		$query .= " AND (isread&8)=8";
	}
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$numpages = ceil(mysql_result($result,0,0)/$threadsperpage);
	if ($numpages==0 && $filteruid>0) {
		//might have changed filtercid w/o changing user.
		//we'll open up to all users then
		$filteruid = 0;	
		$query = "SELECT COUNT(id) FROM imas_msgs WHERE msgto='$userid' AND (isread&2)=0";
		if ($filtercid>0) {
			$query .= " AND courseid='$filtercid'";
		}
		if ($limittotagged==1) {
			$query .= " AND (isread&8)=8";
		}
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		$numpages = ceil(mysql_result($result,0,0)/$threadsperpage);
	}
	$prevnext = '';
	if ($numpages > 1 && !$limittotagged) {
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
			$prevnext .= "<a href=\"msglist.php?page=1&cid=$cid&filtercid=$filtercid&filteruid=$filteruid\">1</a> ";
		}
		if ($min!=2) { $prevnext .= " ... ";}
		for ($i = $min; $i<=$max; $i++) {
			if ($page == $i) {
				$prevnext .= "<b>$i</b> ";
			} else {
				$prevnext .= "<a href=\"msglist.php?page=$i&cid=$cid&filtercid=$filtercid&filteruid=$filteruid\">$i</a> ";
			}
		}
		if ($max!=$numpages-1) { $prevnext .= " ... ";}
		if ($page == $numpages) {
			$prevnext .= "<b>$numpages</b> ";
		} else {
			$prevnext .= "<a href=\"msglist.php?page=$numpages&cid=$cid&filtercid=$filtercid&filteruid=$filteruid\">$numpages</a> ";
		}
		$prevnext .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		if ($page>1) {
			$prevnext .= "<a href=\"msglist.php?page=".($page-1)."&cid=$cid&filtercid=$filtercid&filteruid=$filteruid\">Previous</a> ";
		} else {
			$prevnext .= 'Previous ';
		}
		if ($page < $numpages) {
			$prevnext .= "| <a href=\"msglist.php?page=".($page+1)."&cid=$cid&filtercid=$filtercid&filteruid=$filteruid\">Next</a> ";
		} else {
			$prevnext .= '| Next';
		}
		echo "<div>$prevnext</div>\n";
	}
	$address = $urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/msglist.php?cid=$cid&filtercid=";
	
	
?>
<script type="text/javascript">
function chgfilter() {
	var filtercid = document.getElementById("filtercid").value;
	var filteruid = document.getElementById("filteruid").value;
	window.location = "<?php echo $address;?>"+filtercid+"&filteruid="+filteruid;
}
</script>	
	<form id="qform" method=post action="msglist.php?page=<?php echo $page;?>&cid=<?php echo $cid;?>">
	<p>Filter by course: <select id="filtercid" onchange="chgfilter()">
<?php
	echo "<option value=\"0\" ";
	if ($filtercid==0) {
		echo "selected=1 ";
	}
	echo ">All courses</option>";
	$query = "SELECT DISTINCT imas_courses.id,imas_courses.name FROM imas_courses,imas_msgs WHERE imas_courses.id=imas_msgs.courseid AND imas_msgs.msgto='$userid'";
	$query .= " ORDER BY imas_courses.name";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		echo "<option value=\"{$row[0]}\" ";
		if ($filtercid==$row[0]) {
			echo 'selected=1';
		}
		echo " >{$row[1]}</option>";
	}
	echo "</select> ";
	echo 'By sender: <select id="filteruid" onchange="chgfilter()"><option value="0" ';
	if ($filteruid==0) {
		echo 'selected="selected" ';
	}
	echo '>All</option>';
	$query = "SELECT DISTINCT imas_users.id, imas_users.LastName, imas_users.FirstName FROM imas_users ";
	$query .= "JOIN imas_msgs ON imas_msgs.msgfrom=imas_users.id WHERE imas_msgs.msgto='$userid'";
	if ($filtercid>0) {
		$query .= " AND imas_msgs.courseid='$filtercid'";
	}
	$query .= " ORDER BY imas_users.LastName, imas_users.FirstName";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		echo "<option value=\"{$row[0]}\" ";
		if ($filteruid==$row[0]) {
			echo 'selected=1';
		}
		echo " >{$row[1]}, {$row[2]}</option>";
	}
	echo "</select></p>";
	
?>
	Check: <a href="#" onclick="return chkAllNone('qform','checked[]',true)">All</a> <a href="#" onclick="return chkAllNone('qform','checked[]',false)">None</a>
	With Selected: <input type=submit name="unread" value="Mark as Unread">
	<input type=submit name="markread" value="Mark as Read">
	<input type=submit name="remove" value="Delete">
	
			
	<table class=gb id="myTable">
	<thead>
	<tr><th></th><th>Message</th><th>Replied</th><th></th><th>Flag</th><th>From</th><th>Course</th><th>Sent</th></tr>
	</thead>
	<tbody>
<?php
	$query = "SELECT imas_msgs.id,imas_msgs.title,imas_msgs.senddate,imas_msgs.replied,imas_users.LastName,imas_users.FirstName,imas_msgs.isread,imas_courses.name,imas_msgs.msgfrom,imas_users.hasuserimg ";
	$query .= "FROM imas_msgs LEFT JOIN imas_users ON imas_users.id=imas_msgs.msgfrom LEFT JOIN imas_courses ON imas_courses.id=imas_msgs.courseid WHERE ";
	$query .= "imas_msgs.msgto='$userid' AND (imas_msgs.isread&2)=0 ";
	if ($filteruid>0) {
		$query .= "AND imas_msgs.msgfrom='$filteruid' ";
	} 
	if ($filtercid>0) {
		$query .= "AND imas_msgs.courseid='$filtercid' ";
	}
	if ($limittotagged) {
		$query .= "AND (imas_msgs.isread&8)=8 ";
	}

	$query .= "ORDER BY senddate DESC ";
	$offset = ($page-1)*$threadsperpage;
	if (!$limittotagged) {
		$query .= "LIMIT $offset,$threadsperpage";// OFFSET $offset";
	}
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	if (mysql_num_rows($result)==0) {
		echo "<tr><td></td><td>No messages</td><td></td></tr>";
	}
	while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		if (trim($line['title'])=='') {
			$line['title'] = '[No Subject]';
		}
		$n = 0;
		while (strpos($line['title'],'Re: ')===0) {
			$line['title'] = substr($line['title'],4);
			$n++;
		}
		if ($n==1) {
			$line['title'] = 'Re: '.$line['title'];
		} else if ($n>1) {
			$line['title'] = "Re<sup>$n</sup>: ".$line['title'];
		}
		echo "<tr id=\"tr{$line['id']}\" ";
		if (($line['isread']&8)==8) {
			echo 'class="tagged" ';
		}
		echo "><td><input type=checkbox name=\"checked[]\" value=\"{$line['id']}\"/></td><td>";
		echo "<a href=\"viewmsg.php?page$page&cid=$cid&filtercid=$filtercid&filteruid=$filteruid&type=msg&msgid={$line['id']}\">";
		if (($line['isread']&1)==0) {
			echo "<b>{$line['title']}</b>";
		} else {
			echo $line['title'];
		}
		echo "</a></td><td>";
		if ($line['replied']==1) {
			echo "Yes";
		}
		if ($line['LastName']==null) {
			$line['LastName'] = "[Deleted]";
		}
		echo '</td><td>';
		
		if ($line['hasuserimg']==1) {
			if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
				echo " <img src=\"{$urlmode}s3.amazonaws.com/{$GLOBALS['AWSbucket']}/cfiles/userimg_sm{$line['msgfrom']}.jpg\" style=\"display:none;\"  class=\"userpic\"  />";
			} else {
				echo " <img src=\"$imasroot/course/files/userimg_sm{$line['msgfrom']}.jpg\" style=\"display:none;\" class=\"userpic\"  />";
			}
		}
		
		echo "</td><td>";
		if (($line['isread']&8)==8) {
			echo "<img class=\"pointer\" id=\"tag{$line['id']}\" src=\"$imasroot/img/flagfilled.gif\" onClick=\"toggletagged({$line['id']});return false;\" />";
		} else {
			echo "<img class=\"pointer\" id=\"tag{$line['id']}\" src=\"$imasroot/img/flagempty.gif\" onClick=\"toggletagged({$line['id']});return false;\" />";
		}
		echo '</td>';
		echo "<td>{$line['LastName']}, {$line['FirstName']}</td>";
		
		
		if ($line['name']==null) {
			$line['name'] = "[Deleted]";
		}
		echo "<td>{$line['name']}</td>";
		$senddate = tzdate("F j, Y, g:i a",$line['senddate']);
		echo "<td>$senddate</td></tr>";
	}
?>
	</tbody>
	</table>
	</form>
<?php
	if ($prevnext != '') {
		echo "<p>$prevnext</p>";
	}
	if ($cansendmsgs) {
		echo "<p><button type=\"button\" onclick=\"window.location.href='msglist.php?page=$page&cid=$cid&filtercid=$filtercid&filteruid=$filteruid&add=new'\">"._('Send New Message')."</button></p>";
	}
	
	echo '<p>&nbsp;</p>';
	require("../footer.php");
?>
		
	

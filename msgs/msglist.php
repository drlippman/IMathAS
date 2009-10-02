<?php
	//Displays Message list
	//(c) 2006 David Lippman
	
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
	$threadsperpage = 20;
	
	$cid = $_GET['cid'];
	if (!isset($_GET['page']) || $_GET['page']=='') {
		$page = 1;
	} else {
		$page = $_GET['page'];
	}
	if (isset($_GET['filtercid'])) {
		$filtercid = $_GET['filtercid'];
	} else if ($cid!='admin' && $cid>0) {
		$filtercid = $cid;
	} else {
		$filtercid = 0;
	}
	
	if (isset($_GET['add'])) {
		if (isset($_POST['subject'])) {
			require_once("../includes/htmLawed.php");
			$htmlawedconfig = array('elements'=>'*-script');
			$_POST['message'] = addslashes(htmLawed(stripslashes($_POST['message']),$htmlawedconfig));
			$_POST['subject'] = strip_tags($_POST['subject']);
			
			$query = "INSERT INTO imas_msgs (title,message,msgto,msgfrom,senddate,isread,courseid) VALUES ";
			$now = time();
			$query .= "('{$_POST['subject']}','{$_POST['message']}','{$_POST['to']}','$userid',$now,0,'{$_POST['courseid']}')";
			mysql_query($query) or die("Query failed : $query " . mysql_error());
			$msgid = mysql_insert_id();
			
			if ($_GET['replyto']>0) {
				$query = "UPDATE imas_msgs SET replied=1 WHERE id='{$_GET['replyto']}'";
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
				$message .= "<a href=\"http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/viewmsg.php?cid=0&msgid=$msgid\">";
				$message .= "View Message</a></p>\r\n";
				$message .= "<p>If you do not wish to receive email notification of new messages, please ";
				$message .= "<a href=\"http://" . $_SERVER['HTTP_HOST'] . $imasroot . "/forms.php?action=chguserinfo\">click here to change your ";
				$message .= "user preferences</a></p>\r\n";
				mail($email,'New message notification',$message,$headers);
			}
		} else {
			$pagetitle = "New Message";
			$useeditor = "message";
			$loadgraphfiler = true;
			require("../header.php");
			echo "<div class=breadcrumb>$breadcrumbbase ";
			if ($cid>0) {
				echo "<a href=\"../course/course.php?cid=$cid\">$coursename</a> &gt;  ";
			}
			echo "<a href=\"msglist.php?page=$page&cid=$cid&filtercid=$filtercid\">Message List</a> &gt; ";
			
			echo "New Message</div>\n";
			
			echo "<h3>New Message</h3>\n";
			if (isset($_GET['toquote'])) {
				$replyto = $_GET['toquote'];
			} else if (isset($_GET['replyto'])) {
				$replyto = $_GET['replyto'];
			} else {
				$replyto = 0;
			}
			if ($cid>0) {
				$query = "SELECT msgset FROM imas_courses WHERE id='$cid'";
				$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
				$msgset = mysql_result($result,0,0);
				$msgmonitor = floor($msgset/5);
				$msgset = $msgset%5;
			}
				
			echo "<form method=post action=\"msglist.php?page=$page&cid=$cid&add={$_GET['add']}&replyto=$replyto\">\n";
			echo "<span class=form>To:</span><span class=formright>\n";
			if (isset($_GET['to'])) {
				$query = "SELECT LastName,FirstName FROM imas_users WHERE id='{$_GET['to']}'";
				$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
				$row = mysql_fetch_row($result);
				echo $row[0].', '.$row[1];
				echo "<input type=hidden name=to value=\"{$_GET['to']}\"/>";
			} else {
				echo "<select name=\"to\">";
				if ($isteacher || $msgset<2) {
					$query = "SELECT imas_users.id,imas_users.FirstName,imas_users.LastName FROM ";
					$query .= "imas_users,imas_teachers WHERE imas_users.id=imas_teachers.userid AND ";
					$query .= "imas_teachers.courseid='$cid' ORDER BY imas_users.LastName";
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
				$message = preg_replace('/(`[^`]*`)/',"<span class=\"AM\">$1</span>",$message);
				$message = forcefiltergraph($message);
				$message = '<p> </p><br/><hr/>'.$message;
				$courseid = $cid;
				$title = '';
			} else {
				$title = '';
				$message = '';
				$courseid=$cid;
			}
			echo "</span><br class=form />";
			echo "<input type=hidden name=courseid value=\"$courseid\"/>\n";
			echo "<span class=form><label for=\"subject\">Subject:</label></span>";
			echo "<span class=formright><input type=text size=50 name=subject id=subject value=\"$title\"></span><br class=form>\n";
			echo "<span class=form><label for=\"message\">Message:</label></span>";
			echo "<span class=left><div class=editor><textarea id=message name=message style=\"width: 100%;\" rows=20 cols=70>";
			echo htmlentities($message);
			echo "</textarea></div></span><br class=form>\n";
			
			echo "<div class=submit><input type=submit value='Submit'></div>\n";
			if ($msgmonitor==1) {
				echo "<p><span class=red>Note</span>: Student-to-student messages may be monitored by your instructor</p>";
			}
			require("../footer.php");
			exit;
		}
	}
	if (isset($_POST['unread'])) {
		$checklist = "'".implode("','",$_POST['checked'])."'";
		$query = "UPDATE imas_msgs SET isread=isread-1 WHERE id IN ($checklist) AND (isread=1 OR isread=5)";
		mysql_query($query) or die("Query failed : $query " . mysql_error());
	}
	if (isset($_POST['remove'])) {
		$checklist = "'".implode("','",$_POST['checked'])."'";
		$query = "DELETE FROM imas_msgs WHERE id IN ($checklist) AND isread>1";
		mysql_query($query) or die("Query failed : $query " . mysql_error());
		$query = "UPDATE imas_msgs SET isread=isread+2 WHERE id IN ($checklist) AND isread<2";
		mysql_query($query) or die("Query failed : $query " . mysql_error());
	}
	if (isset($_GET['removeid'])) {
		$query = "DELETE FROM imas_msgs WHERE id='{$_GET['removeid']}' AND isread>1";
		mysql_query($query) or die("Query failed : $query " . mysql_error());
		$query = "UPDATE imas_msgs SET isread=isread+2 WHERE id='{$_GET['removeid']}' AND isread<2";
		mysql_query($query) or die("Query failed : $query " . mysql_error());
	}
	
	$pagetitle = "Messages";
	require("../header.php");
	$curdir = rtrim(dirname(__FILE__), '/\\');
	
	echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> ";
	if ($cid>0) {
		echo "&gt; <a href=\"../course/course.php?cid=$cid\">$coursename</a> ";
	}
	echo "&gt; Message List</div>";
	echo "<h3>Messages</h3>";	
	
	$query = "SELECT COUNT(id) FROM imas_msgs WHERE msgto='$userid' AND (isread<2 OR isread>3)";
	if ($filtercid>0) {
		$query .= " AND courseid='$filtercid'";
	}
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$numpages = ceil(mysql_result($result,0,0)/$threadsperpage);
	
	if ($numpages > 1) {
		echo "<span class=\"right\" style=\"padding: 5px;\">Page: ";
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
			echo "<a href=\"msglist.php?page=1&cid=$cid&filtercid=$filtercid\">1</a> ";
		}
		if ($min!=2) { echo " ... ";}
		for ($i = $min; $i<=$max; $i++) {
			if ($page == $i) {
				echo "<b>$i</b> ";
			} else {
				echo "<a href=\"msglist.php?page=$i&cid=$cid&filtercid=$filtercid\">$i</a> ";
			}
		}
		if ($max!=$numpages-1) { echo " ... ";}
		if ($page == $numpages) {
			echo "<b>$numpages</b> ";
		} else {
			echo "<a href=\"msglist.php?page=$numpages&cid=$cid&filtercid=$filtercid\">$numpages</a> ";
		}
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		if ($page>1) {
			echo "<a href=\"msglist.php?page=".($page-1)."&cid=$cid&filtercid=$filtercid\">Previous</a> ";
		}
		if ($page < $numpages) {
			echo "<a href=\"msglist.php?page=".($page+1)."&cid=$cid&filtercid=$filtercid\">Next</a> ";
		}
		echo "</span>\n";
	}
	$address = "http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/msglist.php?cid=$cid&filtercid=";
	
	if ($myrights > 5 && $cid>0) {
		$query = "SELECT msgset FROM imas_courses WHERE id='$cid'";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		$msgset = mysql_result($result,0,0);
		$msgmonitor = floor($msgset/5);
		$msgset = $msgset%5;
		if ($msgset<3 || $isteacher) {
			$cansendmsgs = true;
		}
	}	
	if ($cansendmsgs) {
		echo "<a href=\"msglist.php?page=$page&cid=$cid&filtercid=$filtercid&add=new\">Send New Message</a>\n";
	}
?>
<script type="text/javascript">
function chkAll(frm, arr, mark) {
  for (i = 0; i <= frm.elements.length; i++) {
   try{
     if(frm.elements[i].name == arr) {
       frm.elements[i].checked = mark;
     }
   } catch(er) {}
  }
}
function chgfilter() {
	var filtercid = document.getElementById("filtercid").value;
	window.location = "<?php echo $address;?>"+filtercid;
}
var picsize = 0;
function rotatepics() {
	picsize = (picsize+1)%3;
	picshow(picsize);
}
function picshow(size) {
	if (size==0) {
		els = document.getElementById("myTable").getElementsByTagName("img");
		for (var i=0; i<els.length; i++) {
			els[i].style.display = "none";
		}
	} else {
		els = document.getElementById("myTable").getElementsByTagName("img");
		for (var i=0; i<els.length; i++) {
			els[i].style.display = "inline";
			if (els[i].getAttribute("src").match("userimg_sm")) {
				if (size==2) {
					els[i].setAttribute("src",els[i].getAttribute("src").replace("_sm","_"));
				}
			} else if (size==1) {
				els[i].setAttribute("src",els[i].getAttribute("src").replace("_","_sm"));
			}
		}
	}
}
</script>	
	<form method=post action="msglist.php?page=<?php echo $page;?>&cid=<?php echo $cid;?>">
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
	echo "</select></p>";
	
?>
	Check/Uncheck All: <input type="checkbox" name="ca2" value="1" onClick="chkAll(this.form, 'checked[]', this.checked)">	
	With Selected: <input type=submit name="unread" value="Mark as Unread">
	<input type=submit name="remove" value="Delete">
	<input type="button" value="Pictures" onclick="rotatepics()" />
			
	<table class=gb id="myTable">
	<thead>
	<tr><th></th><th>Message</th><th>Replied</th><th></th><th>From</th><th>Course</th><th>Sent</th></tr>
	</thead>
	<tbody>
<?php
	$query = "SELECT imas_msgs.id,imas_msgs.title,imas_msgs.senddate,imas_msgs.replied,imas_users.LastName,imas_users.FirstName,imas_msgs.isread,imas_courses.name,imas_msgs.msgfrom ";
	$query .= "FROM imas_msgs LEFT JOIN imas_users ON imas_users.id=imas_msgs.msgfrom LEFT JOIN imas_courses ON imas_courses.id=imas_msgs.courseid WHERE ";
	$query .= "imas_msgs.msgto='$userid' AND (imas_msgs.isread<2 OR imas_msgs.isread>3) ";
	if ($filtercid>0) {
		$query .= "AND imas_msgs.courseid='$filtercid' ";
	}
	$query .= "ORDER BY senddate DESC ";
	$offset = ($page-1)*$threadsperpage;
	$query .= "LIMIT $offset,$threadsperpage";// OFFSET $offset"; 
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
		echo "<tr><td><input type=checkbox name=\"checked[]\" value=\"{$line['id']}\"/></td><td>";
		echo "<a href=\"viewmsg.php?page$page&cid=$cid&filtercid=$filtercid&type=msg&msgid={$line['id']}\">";
		if ($line['isread']==0 || $line['isread']==4) {
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
		if (file_exists("$curdir/../course/files/userimg_sm{$line['msgfrom']}.jpg")) {
			echo "<img src=\"$imasroot/course/files/userimg_sm{$line['msgfrom']}.jpg\" style=\"display:none;\"  />";
		} 
		echo "</td><td>{$line['LastName']}, {$line['FirstName']}</td>";
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
	if ($cansendmsgs) {
		echo "<p><a href=\"msglist.php?page=$page&cid=$cid&filtercid=$filtercid&add=new\">Send New Message</a></p>\n";
	}
	echo "<p><a href=\"sentlist.php?cid=$cid\">Sent Messages</a></p>";
	
	if ($isteacher && $cid>0 && $msgmonitor==1) {
		echo "<p><a href=\"allstumsglist.php?cid=$cid\">Student Messages</a></p>";
	}
	
	require("../footer.php");
?>
		
	

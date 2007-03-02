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
	
	$threadsperpage = 20;
	
	$cid = $_GET['cid'];
	if (!isset($_GET['page']) || $_GET['page']=='') {
		$page = 1;
	} else {
		$page = $_GET['page'];
	}
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

	if (isset($_GET['add'])) {
		if (isset($_POST['subject'])) {
			$query = "INSERT INTO imas_msgs (title,message,msgto,msgfrom,senddate,isread,courseid) VALUES ";
			$now = time();
			$query .= "('{$_POST['subject']}','{$_POST['message']}','{$_POST['to']}','$userid',$now,0,'{$_POST['courseid']}')";
			mysql_query($query) or die("Query failed : $query " . mysql_error());
			$msgid = mysql_insert_id();
			
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
				$message .= "<p>You're received a new message from $from</p>\r\n";
				$message .= "<p>Subject:".stripslashes($_POST['subject'])."</p>";
				$message .= "<a href=\"http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/viewmsg.php?cid=0&msgid=$msgid\">";
				$message .= "View Message</a>\r\n";
				mail($email,'New message notification',$message,$headers);
			}
		} else {
			$pagetitle = "New Message";
			$useeditor = "message";
			require("../header.php");
			echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"../course/course.php?cid=$cid\">$coursename</a> ";
			echo "&gt; <a href=\"msglist.php?page=$page&cid=$cid\">Message List</a> &gt; ";
			
			echo "New Message</div>\n";
			
			echo "<h3>New Message</h3>\n";
			
			echo "<form method=post action=\"msglist.php?page=$page&cid=$cid&add={$_GET['add']}\">\n";
			echo "<span class=form>To:</span><span class=formright>\n";
			if (isset($_GET['to'])) {
				$query = "SELECT LastName,FirstName FROM imas_users WHERE id='{$_GET['to']}'";
				$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
				$row = mysql_fetch_row($result);
				echo $row[0].', '.$row[1];
				echo "<input type=hidden name=to value=\"{$_GET['to']}\"/>";
			} else {
				echo "<select name=\"to\">";
				$query = "SELECT imas_users.id,imas_users.FirstName,imas_users.LastName FROM ";
				$query .= "imas_users,imas_teachers WHERE imas_users.id=imas_teachers.userid AND ";
				$query .= "imas_teachers.courseid='$cid' ORDER BY imas_users.LastName";
				$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
				while ($row = mysql_fetch_row($result)) {
					echo "<option value=\"{$row[0]}\">{$row[2]}, {$row[1]}</option>";
				}
				$query = "SELECT imas_users.id,imas_users.FirstName,imas_users.LastName FROM ";
				$query .= "imas_users,imas_students WHERE imas_users.id=imas_students.userid AND ";
				$query .= "imas_students.courseid='$cid' ORDER BY imas_users.LastName";
				$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
				while ($row = mysql_fetch_row($result)) {
					echo "<option value=\"{$row[0]}\">{$row[2]}, {$row[1]}</option>";
				}
				echo "</select>";
			}
			if (isset($_GET['toquote']) || isset($_GET['replyto'])) {
				if (isset($_GET['toquote'])) {
					$replyto = $_GET['toquote'];
				} else {
					$replyto = $_GET['replyto'];
				}
				$query = "SELECT title,message,courseid FROM imas_msgs WHERE id='$replyto'";
				$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
				$title = "Re: ".mysql_result($result,0,0);
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
				$message = displayq($parts[0],$parts[1],$parts[2],false,true);
				$message = preg_replace('/(`[^`]*`)/',"<span class=\"AM\">$1</span>",$message);
				$message = forcefiltergraph($message);
				$message = '<p> </p><br/><hr/>'.$message;
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
			echo "<span class=left><div class=editor><textarea id=message name=message style=\"width: 100%;\" rows=20 cols=70>$message</textarea></div></span><br class=form>\n";
			
			echo "<div class=submit><input type=submit value='Submit'></div>\n";
			require("../footer.php");
			exit;
		}
	}
	*/
	if (isset($_POST['remove'])) {
		$checklist = "'".implode("','",$_POST['checked'])."'";
		$query = "DELETE FROM imas_msgs WHERE id IN ($checklist) AND isread>1";
		mysql_query($query) or die("Query failed : $query " . mysql_error());
		$query = "UPDATE imas_msgs SET isread=isread+4 WHERE id IN ($checklist) AND isread<2";
		mysql_query($query) or die("Query failed : $query " . mysql_error());
	}
	if (isset($_GET['removeid'])) {
		$query = "DELETE FROM imas_msgs WHERE id='{$_GET['removeid']}' AND isread>1";
		mysql_query($query) or die("Query failed : $query " . mysql_error());
		$query = "UPDATE imas_msgs SET isread=isread+4 WHERE id='{$_GET['removeid']}' AND isread<2";
		mysql_query($query) or die("Query failed : $query " . mysql_error());
	}
	
	$pagetitle = "Messages";
	require("../header.php");
	
	echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> ";
	if ($cid>0) {
		echo "&gt; <a href=\"../course/course.php?cid=$cid\">$coursename</a> ";
	}
	echo "&gt; Sent Message List</div>";
	echo "<h3>Sent Messages</h3>";		
	
	$query = "SELECT COUNT(id) FROM imas_msgs WHERE msgfrom='$userid' AND isread<4";
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
			echo "<a href=\"msglist.php?page=1&cid=$cid\">1</a> ";
		}
		if ($min!=2) { echo " ... ";}
		for ($i = $min; $i<=$max; $i++) {
			if ($page == $i) {
				echo "<b>$i</b> ";
			} else {
				echo "<a href=\"msglist.php?page=$i&cid=$cid\">$i</a> ";
			}
		}
		if ($max!=$numpages-1) { echo " ... ";}
		if ($page == $numpages) {
			echo "<b>$numpages</b> ";
		} else {
			echo "<a href=\"msglist.php?page=$numpages&cid=$cid\">$numpages</a> ";
		}
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		if ($page>1) {
			echo "<a href=\"msglist.php?page=".($page-1)."&cid=$cid\">Previous</a> ";
		}
		if ($page < $numpages) {
			echo "<a href=\"msglist.php?page=".($page+1)."&cid=$cid\">Next</a> ";
		}
		echo "</div>\n";
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
</script>	
	<form method=post action="sentlist.php?page=<?php echo $page;?>&cid=<?php echo $cid;?>">
	Check/Uncheck All: <input type="checkbox" name="ca2" value="1" onClick="chkAll(this.form, 'checked[]', this.checked)">	
	With Selected: 	<input type=submit name="remove" value="Remove from Sent Message List">
			
	<table class=gb>
	<thead>
	<tr><th></th><th>Message</th><th>To</th><th>Read</th><th>Sent</th></tr>
	</thead>
	<tbody>
<?php
	$query = "SELECT imas_msgs.id,imas_msgs.title,imas_msgs.senddate,imas_users.LastName,imas_users.FirstName,imas_msgs.isread FROM imas_msgs,imas_users ";
	$query .= "WHERE imas_users.id=imas_msgs.msgto AND imas_msgs.msgfrom='$userid' AND imas_msgs.isread<4 ORDER BY senddate DESC ";
	$offset = ($page-1)*$threadsperpage;
	$query .= "LIMIT $offset,$threadsperpage";// OFFSET $offset"; 
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	if (mysql_num_rows($result)==0) {
		echo "<tr><td></td><td>No messages</td><td></td><td></td></tr>";
	}
	while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		if (trim($line['title'])=='') {
			$line['title'] = '[No Subject]';
		}
		echo "<tr><td><input type=checkbox name=\"checked[]\" value=\"{$line['id']}\"/></td><td>";
		echo "<a href=\"viewmsg.php?page$page&cid=$cid&type=sent&msgid={$line['id']}\">";
		echo $line['title'];
		echo "</a></td>";
		echo "<td>{$line['LastName']}, {$line['FirstName']}</td>";
		if ($line['isread']==1 || $line['isread']==3) {
			echo "<td>Yes</td>";
		} else {
			echo "<td>No</td>";
		}
		$senddate = tzdate("F j, Y, g:i a",$line['senddate']);
		echo "<td>$senddate</td></tr>";
	}
?>
	</tbody>
	</table>
	</form>
<?php
	echo "<p><a href=\"msglist.php?cid=$cid\">Back to Messages</a></p>";
	
	require("../footer.php");
?>
		
	

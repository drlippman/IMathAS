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
	if (isset($_GET['filtercid'])) {
		$filtercid = $_GET['filtercid'];
	} else {
		$filtercid = 0;
	}
	if (isset($_GET['filterstu'])) {
		$filterstu = $_GET['filterstu'];
	} else {
		$filterstu = 0;
	}
	
	$cid = $_GET['cid'];
	$page = $_GET['page'];
	$type = $_GET['type'];
	
	$teacherof = array();
	$query = "SELECT courseid FROM imas_teachers WHERE userid='$userid'";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$teacherof[$row[0]] = true;
	}
	
	if (isset($_GET['markunread'])) {
		$msg = $_GET['msgid'];	
		$query = "UPDATE imas_msgs SET isread=isread-1 WHERE id='$msg'";
		mysql_query($query) or die("Query failed : $query " . mysql_error());
		if ($type=='new') {
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/newmsglist.php?cid=$cid");
		} else {
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/msglist.php?page=$page&cid=$cid&filtercid=$filtercid");				
		}
		exit;
	}
	
	$pagetitle = "Messages";
	$placeinhead = '<script type="text/javascript">
		function showtrimmed(el) {
			if (el.innerHTML.match(/Show/)) {
				document.getElementById("trimmed").style.display="block";
				el.innerHTML = "[Hide trimmed content]";
			} else {
				document.getElementById("trimmed").style.display="none";
				el.innerHTML = "[Show trimmed content]";
			}
		}
		</script>';
	require("../header.php");
	echo "<div class=breadcrumb>$breadcrumbbase ";
	if ($cid>0 && (!isset($sessiondata['ltiitemtype']) || $sessiondata['ltiitemtype']!=0)) {
		echo " <a href=\"../course/course.php?cid=$cid\">$coursename</a> &gt; ";
	}
	if ($type=='sent') {
		echo " <a href=\"sentlist.php?page=$page&cid=$cid&filtercid=$filtercid\">Sent Message List</a> &gt; Message</div>";
	} else if ($type=='allstu') {
		echo " <a href=\"msglist.php?page=$page&cid=$cid&filtercid=$filtercid\">Message List</a> &gt; <a href=\"allstumsglist.php?page=$page&cid=$cid&filterstu=$filterstu\">Student Messages</a> &gt; Message</div>";
	} else if ($type=='new') {
		echo " <a href=\"newmsglist.php?cid=$cid\">New Message List</a> &gt; Message</div>";
	} else {
		echo " <a href=\"msglist.php?page=$page&cid=$cid&filtercid=$filtercid\">Message List</a> &gt; Message</div>";
	}
	echo '<div id="headerviewmsg" class="pagetitle"><h2>Message</h2></div>';

			
	
	$msgid = $_GET['msgid'];
	
	$query = "SELECT imas_msgs.*,imas_users.LastName,imas_users.FirstName,imas_users.email,imas_users.hasuserimg,imas_students.section ";
	$query .= "FROM imas_msgs JOIN imas_users ON imas_msgs.msgfrom=imas_users.id LEFT JOIN imas_students ON imas_students.userid=imas_users.id AND imas_students.courseid='$cid' ";
	$query .= "WHERE imas_msgs.id='$msgid' ";
	
	if ($type!='allstu' || !$isteacher) {
		$query .= "AND (imas_msgs.msgto='$userid' OR imas_msgs.msgfrom='$userid')";
	}
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	if (mysql_num_rows($result)==0) {
		echo "Message not found";
		require("../footer.php");
		exit;
	}
	$line = mysql_fetch_array($result, MYSQL_ASSOC);
	$senddate = tzdate("F j, Y, g:i a",$line['senddate']);
	$curdir = rtrim(dirname(__FILE__), '/\\');
	if ($line['hasuserimg']==1) {
		if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
			echo " <img style=\"float:left;\" src=\"{$urlmode}s3.amazonaws.com/{$GLOBALS['AWSbucket']}/cfiles/userimg_sm{$line['msgfrom']}.jpg\"  onclick=\"togglepic(this)\" /><br/>";
		} else {
			echo " <img style=\"float:left;\" src=\"$imasroot/course/files/userimg_sm{$line['msgfrom']}.jpg\"  onclick=\"togglepic(this)\" /><br/>";
		}
	}
	echo "<table class=gb ><tbody>";
	echo "<tr><td><b>From:</b></td><td>{$line['LastName']}, {$line['FirstName']}";
	if ($line['section']!='') {
		echo ' <span class="small">(Section: '.$line['section'].')</span>';
	}
	if (isset($teacherof[$line['courseid']])) {
		echo " <a href=\"mailto:{$line['email']}\">email</a> | ";
		echo " <a href=\"$imasroot/course/gradebook.php?cid={$line['courseid']}&stu={$line['msgfrom']}\" target=\"_popoutgradebook\">gradebook</a>";
		if (preg_match('/Question\s+about\s+#(\d+)\s+in\s+(.*)\s*$/',$line['title'],$matches)) {
			$query = "SELECT id FROM imas_assessments WHERE name='{$matches[2]}' AND courseid='{$line['courseid']}'";
			$res = mysql_query($query) or die("Query failed : $query " . mysql_error());
			if (mysql_num_rows($res)>0) {
				$aid = mysql_result($res,0,0);
				$query = "SELECT id FROM imas_assessment_sessions WHERE assessmentid='$aid' AND userid='{$line['msgfrom']}'";
				$res = mysql_query($query) or die("Query failed : $query " . mysql_error());
				if (mysql_num_rows($res)>0) {
					$asid = mysql_result($res,0,0);
					echo " | <a href=\"$imasroot/course/gb-viewasid.php?cid={$line['courseid']}&uid={$line['msgfrom']}&asid=$asid\" target=\"_popoutgradebook\">assignment</a>";
				}
			}
		}
	}
	echo "</td></tr><tr><td><b>Sent:</b></td><td>$senddate</td></tr>";
	echo "<tr><td><b>Subject:</b></td><td>{$line['title']}</td></tr>";
	echo "</tbody></table>";
	echo "<div style=\"border: 1px solid #000; margin: 10px; padding: 10px;\">";
	if (($p = strpos($line['message'],'<hr'))!==false) {
		$line['message'] = substr($line['message'],0,$p).'<a href="#" class="small" onclick="showtrimmed(this);return false;">[Show trimmed content]</a><div id="trimmed" style="display:none;">'.substr($line['message'],$p).'</div>';
	}
	echo filter($line['message']);
	echo "</div>";
	
	if ($type!='sent' && $type!='allstu') {
		if ($line['courseid']>0) {
			$query = "SELECT msgset FROM imas_courses WHERE id='{$line['courseid']}'";
			$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			$msgset = mysql_result($result,0,0);
			$msgmonitor = floor($msgset/5);
			$msgset = $msgset%5;
			if ($msgset<3 || $isteacher) {
				$cansendmsgs = true;
				if ($msgset==1 && !$isteacher) { //check if sending to teacher 
					$query = "SELECT id FROM imas_teachers WHERE userid='{$line['msgfrom']}' and courseid='{$line['courseid']}'";
					$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
					if (mysql_num_rows($result)==0) {
						$cansendmsgs = false;
					}
				} else if ($msgset==2 && !$isteacher) { //check if sending to stu
					$query = "SELECT id FROM imas_students WHERE userid='{$line['msgfrom']}' and courseid='{$line['courseid']}'";
					$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
					if (mysql_num_rows($result)==0) {
						$cansendmsgs = false;
					}
				} 
			} else {
				$cansendmsgs = false;
			}
		} else {
			$cansendmsgs = true;
		}
		if ($cansendmsgs) {
			echo "<a href=\"msglist.php?cid=$cid&filtercid=$filtercid&page=$page&type=$type&add=new&to={$line['msgfrom']}&toquote=$msgid\">Reply</a> | ";
		}
		echo "<a href=\"msghistory.php?cid=$cid&filtercid=$filtercid&page=$page&msgid=$msgid&type=$type\">View Conversation</a> | ";
		echo "<a href=\"msglist.php?cid=$cid&filtercid=$filtercid&page=$page&removeid=$msgid&type=$type\">Delete</a>";
		if ($isteacher && $line['courseid']==$cid) {
			echo " | <a href=\"$imasroot/course/gradebook.php?cid={$line['courseid']}&stu={$line['msgfrom']}\">Gradebook</a>";
		}
		echo " | <a href=\"viewmsg.php?markunread=true&cid=$cid&filtercid=$filtercid&page=$page&msgid=$msgid&type=$type\">Mark Unread</a>";
	} else if ($type=='sent' && $type!='allstu') {
		echo "<a href=\"msghistory.php?cid=$cid&filtercid=$filtercid&page=$page&msgid=$msgid&type=$type\">View Conversation</a>";
		
	}
	if ($type!='sent' && $type!='allstu' && ($line['isread']==0 || $line['isread']==4)) {
		$query = "UPDATE imas_msgs SET isread=isread+1 WHERE id='$msgid'";
		mysql_query($query) or die("Query failed : $query " . mysql_error());
	}
	require("../footer.php");
?>

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
	
	$pagetitle = "Messages";
	require("../header.php");
	echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> ";
	if ($cid>0) {
		echo "&gt; <a href=\"../course/course.php?cid=$cid\">$coursename</a> ";
	}
	if ($type=='sent') {
		echo "&gt; <a href=\"sentlist.php?page=$page&cid=$cid&filtercid=$filtercid\">Sent Message List</a> &gt; Message</div>";
	} else if ($type=='allstu') {
		echo "&gt; <a href=\"msglist.php?page=$page&cid=$cid&filtercid=$filtercid\">Message List</a> &gt; <a href=\"allstumsglist.php?page=$page&cid=$cid&filterstu=$filterstu\">Student Messages</a> &gt; Message</div>";
	} else {
		echo "&gt; <a href=\"msglist.php?page=$page&cid=$cid&filtercid=$filtercid\">Message List</a> &gt; Message</div>";
	}
			
	
	$msgid = $_GET['msgid'];
	$query = "SELECT imas_msgs.*,imas_users.LastName,imas_users.FirstName,imas_users.email ";
	$query .= "FROM imas_msgs,imas_users WHERE imas_msgs.msgfrom=imas_users.id AND imas_msgs.id='$msgid' ";
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
	echo "<table class=gb><tbody>";
	echo "<tr><td><b>From:</b></td><td>{$line['LastName']}, {$line['FirstName']}";
	if ($isteacher) {
		echo " <a href=\"mailto:{$line['email']}\">email</a>";
	}
	echo "</td></tr><tr><td><b>Sent:</b></td><td>$senddate</td></tr>";
	echo "<tr><td><b>Subject:</b></td><td>{$line['title']}</td></tr>";
	echo "</tbody></table>";
	echo "<div style=\"border: 1px solid #000; margin: 10px; padding: 10px;\">";
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
			echo "<a href=\"msglist.php?cid=$cid&filtercid=$filtercid&page=$page&add=new&to={$line['msgfrom']}&replyto=$msgid\">Reply</a> | ";
			echo "<a href=\"msglist.php?cid=$cid&filtercid=$filtercid&page=$page&add=new&to={$line['msgfrom']}&toquote=$msgid\">Quote in Reply</a> | ";
		}
		echo "<a href=\"msghistory.php?cid=$cid&filtercid=$filtercid&page=$page&msgid=$msgid&type=$type\">View Conversation</a> | ";
		echo "<a href=\"msglist.php?cid=$cid&filtercid=$filtercid&page=$page&removeid=$msgid\">Delete</a>";
		if ($isteacher && $line['courseid']==$cid) {
			echo " | <a href=\"$imasroot/course/gradebook.php?cid={$line['courseid']}&stu={$line['msgfrom']}\">Gradebook</a>";
		}
	} else if ($type=='sent' && $type!='allstu') {
		echo "<a href=\"msghistory.php?cid=$cid&filtercid=$filtercid&page=$page&msgid=$msgid&type=$type\">View Conversation</a>";
		
	}
	if ($type!='sent' && $type!='allstu' && ($line['isread']==0 || $line['isread']==4)) {
		$query = "UPDATE imas_msgs SET isread=isread+1 WHERE id='$msgid'";
		mysql_query($query) or die("Query failed : $query " . mysql_error());
	}
	require("../footer.php");
?>

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
		echo "&gt; <a href=\"sentlist.php?page=$page&cid=$cid\">Sent Message List</a> &gt; Message</div>";
	} else {
		echo "&gt; <a href=\"msglist.php?page=$page&cid=$cid&filtercid=$filtercid\">Message List</a> &gt; Message</div>";
	}
			
	
	$msgid = $_GET['msgid'];
	$query = "SELECT imas_msgs.*,imas_users.LastName,imas_users.FirstName,imas_users.email ";
	$query .= "FROM imas_msgs,imas_users WHERE imas_msgs.msgfrom=imas_users.id AND imas_msgs.id='$msgid' ";
	$query .= "AND (imas_msgs.msgto='$userid' OR imas_msgs.msgfrom='$userid')";
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
	
	if ($type!='sent') {
		echo "<a href=\"msglist.php?cid=$cid&filtercid=$filtercid&page=$page&add=new&to={$line['msgfrom']}&replyto=$msgid\">Reply</a> | ";
		echo "<a href=\"msglist.php?cid=$cid&filtercid=$filtercid&page=$page&add=new&to={$line['msgfrom']}&toquote=$msgid\">Quote in Reply</a> | ";
		echo "<a href=\"msglist.php?cid=$cid&filtercid=$filtercid&page=$page&removeid=$msgid\">Delete</a>";
		if ($isteacher && $line['courseid']==$cid) {
			echo " | <a href=\"$imasroot/course/gradebook.php?cid={$line['courseid']}&stu={$line['msgfrom']}\">Gradebook</a>";
		}
	}
	if ($type!='sent' && ($line['isread']==0 || $line['isread']==4)) {
		$query = "UPDATE imas_msgs SET isread=isread+1 WHERE id='$msgid'";
		mysql_query($query) or die("Query failed : $query " . mysql_error());
	}
	require("../footer.php");
?>

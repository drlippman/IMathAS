<?php
	//Displays forums posts
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
	if (isset($_GET['view'])) {
		$view = $_GET['view'];
	} else {
		$view = 0;  //0: expanded, 1: collapsed, 2: condensed
	}
	if (isset($_GET['filtercid'])) {
		$filtercid = $_GET['filtercid'];
	} else if ($cid!='admin' && $cid>0) {
		$filtercid = $cid;
	} else {
		$filtercid = 0;
	}
	
	$cid = $_GET['cid'];
	$msgid = $_GET['msgid'];
	$page = $_GET['page'];
	$type = $_GET['type'];
	
	$pagetitle = "Message Conversation";
	$placeinhead = "<style type=\"text/css\">\n@import url(\"$imasroot/forums/forums.css\");\n</style>\n";
	require("../header.php");
	
	$allowmsg = false;
	
	$query = "SELECT baseid FROM imas_msgs WHERE id='$msgid'";
	if ($type!='allstu' || !$isteacher) {
		$query .= " AND (msgto='$userid' OR msgfrom='$userid')";
	}
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	if (mysql_num_rows($result)==0) {
		echo "Message not found";
		require("../footer.php");
		exit;
	}
	$baseid = mysql_result($result,0,0);
	if ($baseid==0) {
		$baseid=$msgid;
	}
	$query = "SELECT imas_msgs.*,imas_users.FirstName,imas_users.LastName,imas_users.email from imas_msgs,imas_users ";
	$query .= "WHERE imas_msgs.msgfrom=imas_users.id AND (imas_msgs.id='$baseid' OR imas_msgs.baseid='$baseid') ORDER BY imas_msgs.id";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	while ($line =  mysql_fetch_array($result, MYSQL_ASSOC)) {
		$children[$line['parent']][] = $line['id'];
		$date[$line['id']] = $line['senddate'];
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
			
		$subject[$line['id']] = $line['title'];
		if ($sessiondata['graphdisp']==0) {
			$line['message'] = preg_replace('/<embed[^>]*alt="([^"]*)"[^>]*>/',"[$1]", $line['message']);
		}
		$message[$line['id']] = $line['message'];
		$ownerid[$line['id']] = $line['userid'];
		$poster[$line['id']] = $line['FirstName'] . ' ' . $line['LastName'];
		$email[$line['id']] = $line['email'];
		
	}
	echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> ";
	if ($cid>0) {
		echo "&gt; <a href=\"../course/course.php?cid=$cid\">$coursename</a> ";
	}
	if ($type=='sent') {
		echo "&gt; <a href=\"sentlist.php?page=$page&cid=$cid&filtercid=$filtercid\">Sent Message List</a> &gt; ";
	} else if ($type=='allstu') {
		echo "&gt; <a href=\"msglist.php?page=$page&cid=$cid&filtercid=$filtercid\">Message List</a> &gt; <a href=\"allstumsglist.php?page=$page&cid=$cid&filterstu=$filterstu\">Student Messages</a> &gt; ";
	} else {
		echo "&gt; <a href=\"msglist.php?page=$page&cid=$cid&filtercid=$filtercid\">Message List</a> &gt; ";
	}
	echo "<a href=\"viewmsg.php?page=$page&cid=$cid&msgid=$msgid&type=$type&filtercid=$filtercid\">Message</a> &gt; Message Conversation</div>\n";
	
	
	echo "<p><b style=\"font-size: 120%\">Message: {$subject[$msgid]}</b></p>\n";
	echo "<input type=button value=\"Expand All\" onclick=\"showall()\"/>";
	echo "<input type=button value=\"Collapse All\" onclick=\"collapseall()\"/>";
	if ($view==2) {
		echo "<a href=\"msghistory.php?view=$view&cid=$cid&page=$page&msgid=$msgid&view=0\">View Expanded</a>";
	} else {
		echo "<a href=\"msghistory.php?view=$view&cid=$cid&page=$page&msgid=$msgid&view=2\">View Condensed</a>";
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
		global $children,$date,$subject,$message,$poster,$email,$forumid,$threadid,$isteacher,$cid,$userid,$ownerid,$points,$posttype,$lastview,$bcnt,$icnt,$myrights,$allowreply,$allowmod,$allowdel,$view,$page,$allowmsg,$haspoints;
		foreach($children[$base] as $child) {
			echo "<div class=block> ";
			if ($view==2) {
				echo "<span class=right>";
				echo "<input type=button id=\"buti$icnt\" value=\"Show\" onClick=\"toggleitem($icnt)\">\n";
				
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
	require("../footer.php");
?>

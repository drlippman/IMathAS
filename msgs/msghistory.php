<?php
	//Displays message history as conversation
	//(c) 2006 David Lippman

	require("../init.php");
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
		$filtercid = Sanitize::onlyInt($_GET['filtercid']);
	} else if ($cid!='admin' && $cid>0) {
		$filtercid = $cid;
	} else {
		$filtercid = 0;
	}
	$view = 0;

	$cid = Sanitize::courseId($_GET['cid']);
	$msgid = Sanitize::onlyInt($_GET['msgid']);
	$page = Sanitize::onlyInt($_GET['page']);
	$type =  Sanitize::encodeStringForDisplay($_GET['type']);

	$pagetitle = "Message Conversation";
	$placeinhead = "<style type=\"text/css\">\n@import url(\"$imasroot/forums/forums.css\");\n</style>\n";
	$placeinhead .= '<script type="text/javascript">
		function showtrimmed(el,n) {
			if (el.innerHTML.match(/Show/)) {
				document.getElementById("trimmed"+n).style.display="block";
				el.innerHTML = "[Hide trimmed content]";
			} else {
				document.getElementById("trimmed"+n).style.display="none";
				el.innerHTML = "[Show trimmed content]";
			}
		}
		</script>';
	require("../header.php");

	$allowmsg = false;

	//DB $query = "SELECT baseid FROM imas_msgs WHERE id='$msgid'";
	//DB $query .= " AND (msgto='$userid' OR msgfrom='$userid')";
	//DB if ($type!='allstu' || !$isteacher) {
	//DB 	$query .= " AND (msgto='$userid' OR msgfrom='$userid')";
	//DB }
	$query = "SELECT baseid FROM imas_msgs WHERE id=:id";
	if ($type!='allstu' || !$isteacher) {
		$query .= " AND (msgto=:msgto OR msgfrom=:msgfrom)";
	}
	$stm = $DBH->prepare($query);
	if ($type!='allstu' || !$isteacher) {
		$stm->execute(array(':id'=>$msgid, ':msgto'=>$userid, ':msgfrom'=>$userid));
	} else {
		$stm->execute(array(':id'=>$msgid));
	}

	//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	//DB if (mysql_num_rows($result)==0) {
	if ($stm->rowCount()==0) {
		echo "Message not found";
		require("../footer.php");
		exit;
	}
	//DB $baseid = mysql_result($result,0,0);
	$baseid = $stm->fetchColumn(0);
	if ($baseid==0) {
		$baseid=$msgid;
	}
	//DB $query = "SELECT imas_msgs.*,imas_users.FirstName,imas_users.LastName,imas_users.email from imas_msgs,imas_users ";
	//DB $query .= "WHERE imas_msgs.msgfrom=imas_users.id AND (imas_msgs.id='$baseid' OR imas_msgs.baseid='$baseid') ORDER BY imas_msgs.id";
	//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	//DB while ($line =  mysql_fetch_array($result, MYSQL_ASSOC)) {
	$query = "SELECT imas_msgs.*,imas_users.FirstName,imas_users.LastName,imas_users.email from imas_msgs,imas_users ";
	$query .= "WHERE imas_msgs.msgfrom=imas_users.id AND (imas_msgs.id=:id OR imas_msgs.baseid=:baseid) ORDER BY imas_msgs.id";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':id'=>$baseid, ':baseid'=>$baseid));
	while ($line =  $stm->fetch(PDO::FETCH_ASSOC)) {
		$children[$line['parent']][] = $line['id'];
		$date[$line['id']] = $line['senddate'];
		$n = 0;
		while (strpos($line['title'],'Re: ')===0) {
			$line['title'] = substr($line['title'],4);
			$n++;
		}
		$line['title'] = Sanitize::encodeStringForDisplay($line['title']);
		if ($n==1) {
			$line['title'] = 'Re: '.$line['title'];
		} else if ($n>1) {
			$line['title'] = "Re<sup>$n</sup>: ".$line['title'];
		}

		$subject[$line['id']] = $line['title'];
		if ($sessiondata['graphdisp']==0) {
			$line['message'] = preg_replace('/<embed[^>]*alt="([^"]*)"[^>]*>/',"[$1]", $line['message']);
		}
		if (($p = strpos($line['message'],'<hr'))!==false) {
			$line['message'] = substr($line['message'],0,$p).'<a href="#" class="small" onclick="showtrimmed(this,\''.$line['id'].'\');return false;">[Show trimmed content]</a><div id="trimmed'.$line['id'].'" style="display:none;">'.substr($line['message'],$p).'</div>';
		}
		$message[$line['id']] = $line['message'];
		$ownerid[$line['id']] = $line['msgfrom'];
		$poster[$line['id']] = $line['FirstName'] . ' ' . $line['LastName'];
		$email[$line['id']] = $line['email'];

	}
	if ($line['courseid']>0) {
		//DB $query = "SELECT msgset FROM imas_courses WHERE id='{$line['courseid']}'";
		//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		//DB $msgset = mysql_result($result,0,0);
		$stm = $DBH->prepare("SELECT msgset FROM imas_courses WHERE id=:id");
		$stm->execute(array(':id'=>$line['courseid']));
		$msgset = $stm->fetchColumn(0);
		$msgmonitor = floor($msgset/5);
		$msgset = $msgset%5;
		if ($msgset<3 || $isteacher) {
			$cansendmsgs = true;
			if ($msgset==1 && !$isteacher) { //check if sending to teacher
				//DB $query = "SELECT id FROM imas_teachers WHERE userid='{$line['msgfrom']}' and courseid='{$line['courseid']}'";
				//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
				//DB if (mysql_num_rows($result)==0) {
				$stm = $DBH->prepare("SELECT id FROM imas_teachers WHERE userid=:userid and courseid=:courseid");
				$stm->execute(array(':userid'=>$line['msgfrom'], ':courseid'=>$line['courseid']));
				if ($stm->rowCount()==0) {
					$cansendmsgs = false;
				}
			} else if ($msgset==2 && !$isteacher) { //check if sending to stu
				//DB $query = "SELECT id FROM imas_students WHERE userid='{$line['msgfrom']}' and courseid='{$line['courseid']}'";
				//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
				//DB if (mysql_num_rows($result)==0) {
				$stm = $DBH->prepare("SELECT id FROM imas_students WHERE userid=:userid and courseid=:courseid");
				$stm->execute(array(':userid'=>$line['msgfrom'], ':courseid'=>$line['courseid']));
				if ($stm->rowCount()==0) {
					$cansendmsgs = false;
				}
			}
		} else {
			$cansendmsgs = false;
		}
	} else {
		$cansendmsgs = true;
	}



	echo "<div class=breadcrumb>$breadcrumbbase ";
	if ($cid>0 && (!isset($sessiondata['ltiitemtype']) || $sessiondata['ltiitemtype']!=0)) {
		echo " <a href=\"../course/course.php?cid=$cid\">$coursename</a> &gt; ";
	}
	if ($type=='sent') {
		echo " <a href=\"sentlist.php?page=$page&cid=$cid&filtercid=$filtercid\">Sent Message List</a> &gt; ";
	} else if ($type=='allstu') {
		echo " <a href=\"msglist.php?page=$page&cid=$cid&filtercid=$filtercid\">Message List</a> &gt; <a href=\"allstumsglist.php?page=$page&cid=$cid&filterstu=$filterstu\">Student Messages</a> &gt; ";
	} else if ($type=='new') {
		echo " <a href=\"newmsglist.php?cid=$cid\">New Message List</a> &gt; ";
	} else {
		echo " <a href=\"msglist.php?page=$page&cid=$cid&filtercid=$filtercid\">Message List</a> &gt; ";
	}
	echo "<a href=\"viewmsg.php?page=$page&cid=$cid&msgid=$msgid&type=$type&filtercid=$filtercid\">Message</a> &gt; Message Conversation</div>\n";

	echo '<div id="headermsghistory" class="pagetitle"><h1>Message Conversation</h1></div>';
	echo "<p><a href=\"viewmsg.php?page=$page&cid=$cid&msgid=$msgid&type=$type&filtercid=$filtercid\">Back to Message</a></p>";
	//echo "<p><b>Message: {$subject[$msgid]}</b></p>\n";
	echo '<button onclick="expandall()">'._('Expand All').'</button>';
	echo '<button onclick="collapseall()">'._('Collapse All').'</button>';
	echo '<button onclick="showall()">'._('Show All').'</button>';
	echo '<button onclick="hideall()">'._('Hide All').'</button>';


?>
	<script type="text/javascript">
	function toggleshow(bnum) {
	   var node = document.getElementById('block'+bnum);
	   var butn = document.getElementById('butb'+bnum);
	   if (node.className == 'forumgrp') {
	       node.className = 'hidden';
	       //if (butn.value=='Collapse') {butn.value = 'Expand';} else {butn.value = '+';}
	//       butn.value = 'Expand';
		butn.src = imasroot+'/img/expand.gif';
	   } else {
	       node.className = 'forumgrp';
	       //if (butn.value=='Expand') {butn.value = 'Collapse';} else {butn.value = '-';}
	//       butn.value = 'Collapse';
		butn.src = imasroot+'/img/collapse.gif';
	}
	}
	function toggleitem(inum) {
	   var node = document.getElementById('item'+inum);
	   var butn = document.getElementById('buti'+inum);
	   if (node.className == 'blockitems') {
	       node.className = 'hidden';
	       butn.value = 'Show';
	   } else {
	       node.className = 'blockitems';
	       butn.value = 'Hide';
	   }
	}
	function expandall() {
	   for (var i=0;i<bcnt;i++) {
	     var node = document.getElementById('block'+i);
	     var butn = document.getElementById('butb'+i);
	     node.className = 'forumgrp';
	//     butn.value = 'Collapse';
	       //if (butn.value=='Expand' || butn.value=='Collapse') {butn.value = 'Collapse';} else {butn.value = '-';}
	       butn.src = imasroot+'/img/collapse.gif';
	   }
	}
	function collapseall() {
	   for (var i=0;i<bcnt;i++) {
	     var node = document.getElementById('block'+i);
	     var butn = document.getElementById('butb'+i);
	     node.className = 'hidden';
	//     butn.value = 'Expand';
	       //if (butn.value=='Collapse' || butn.value=='Expand' ) {butn.value = 'Expand';} else {butn.value = '+';}
	       butn.src = imasroot+'/img/expand.gif';
	   }
	}

	function showall() {
	   for (var i=0;i<icnt;i++) {
	     var node = document.getElementById('item'+i);
	     var buti = document.getElementById('buti'+i);
	     node.className = "blockitems";
	     buti.value = "Hide";
	   }
	}
	function hideall() {
	   for (var i=0;i<icnt;i++) {
	     var node = document.getElementById('item'+i);
	     var buti = document.getElementById('buti'+i);
	     node.className = "hidden";
	     buti.value = "Show";
	   }
	}
	</script>
<?php
	$bcnt = 0;
	$bcnt = 0;
	$icnt = 0;
	function printchildren($base) {
		global $children,$date,$subject,$message,$poster,$email,$forumid,$threadid,$isteacher,$cid,$userid,$ownerid,$points,$posttype,$lastview,$bcnt,$icnt,$myrights,$allowreply,$allowmod,$allowdel,$view,$page,$allowmsg,$haspoints,$cansendmsgs;
		global $filtercid,$page,$type,$imasroot;
		$curdir = rtrim(dirname(__FILE__), '/\\');
		foreach($children[$base] as $child) {
			echo "<div class=block> ";
			echo '<span class="leftbtns">';
			if (isset($children[$child])) {
				if ($view==1) {
					$lbl = '+';
					$img = "expand";
				} else {
					$lbl = '-';
					$img = "collapse";
				}
				//echo "<input type=button id=\"butb$bcnt\" value=\"$lbl\" onClick=\"toggleshow($bcnt)\"> ";
				echo "<img class=\"pointer\" id=\"butb$bcnt\" src=\"$imasroot/img/$img.gif\" onClick=\"toggleshow($bcnt)\" alt=\"Expand/Collapse\"/> ";
			}

			echo '</span>';

			echo "<span class=right>";

			if ($ownerid[$child]!=$userid && $cansendmsgs) {
				echo "<a href=\"msglist.php?cid=$cid&filtercid=" . Sanitize::encodeUrlParam($filtercid) . "&page=" . Sanitize::encodeUrlParam($page) . "&type=" . Sanitize::encodeUrlParam($type) . "&add=new&to=" . Sanitize::encodeUrlParam($ownerid[$child]) . "&toquote=" . Sanitize::encodeUrlParam($child) . "\">Reply</a> ";
			}

			echo "<input type=button id=\"buti$icnt\" value=\"Hide\" onClick=\"toggleitem($icnt)\">\n";

			echo "</span>\n";
			echo "<b>{$subject[$child]}</b><br/>Posted by: ";
			if ($isteacher && $ownerid[$child]!=0) {
				echo "<a href=\"mailto:" . Sanitize::emailAddress($email[$child]) . "\">";
			} else if ($allowmsg && $ownerid[$child]!=0) {
				echo "<a href=\"../msgs/msglist.php?cid=$cid&add=new&to=" . Sanitize::encodeUrlParam($ownerid[$child]) . "\">";
			}
			echo Sanitize::encodeStringForDisplay($poster[$child]); // This is a user's first and last name.
			if (($isteacher || $allowmsg) && $ownerid[$child]!=0) {
				echo "</a>";
			}
			echo ', ';
			echo tzdate("F j, Y, g:i a",$date[$child]);
			if ($date[$child]>$lastview) {
				echo " <span class=noticetext>New</span>\n";
			}

			echo "</div>\n";
			echo "<div class=\"blockitems\" id=\"item$icnt\">";
			$icnt++;
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

	printchildren(0);

	echo "<script type=\"text/javascript\">";
	echo "var bcnt =".$bcnt."; var icnt = $icnt;\n";
	echo "</script>";
	require("../footer.php");
?>

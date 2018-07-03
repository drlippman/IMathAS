<?php
	//Displays Message list
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
	} else {
		$filtercid = 0;
	}
	if (isset($_GET['filterstu'])) {
		$filterstu = Sanitize::onlyInt($_GET['filterstu']);
	} else {
		$filterstu = 0;
	}

	$cid = Sanitize::courseId($_GET['cid']);
	$page = Sanitize::onlyInt($_GET['page']);
	$type = $_GET['type'];

	$teacherof = array();
	//DB $query = "SELECT courseid FROM imas_teachers WHERE userid='$userid'";
	//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	//DB while ($row = mysql_fetch_row($result)) {
	$stm = $DBH->prepare("SELECT courseid FROM imas_teachers WHERE userid=:userid");
	$stm->execute(array(':userid'=>$userid));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$teacherof[$row[0]] = true;
	}

	if (isset($_GET['markunread'])) {
		$msg = Sanitize::onlyInt($_GET['msgid']);
		//DB $query = "UPDATE imas_msgs SET isread=isread-1 WHERE id='$msg'";
		//DB mysql_query($query) or die("Query failed : $query " . mysql_error());
		$stm = $DBH->prepare("UPDATE imas_msgs SET isread=isread-1 WHERE id=:id and isread>0");
		$stm->execute(array(':id'=>$msg));
		if ($type=='new') {
			header('Location: ' . $GLOBALS['basesiteurl'] . "/msgs/newmsglist.php?cid=$cid&r=" .Sanitize::randomQueryStringParam());
		} else {
			header('Location: ' . $GLOBALS['basesiteurl'] . "/msgs/msglist.php?page=$page&cid=$cid&filtercid=$filtercid&r=" .Sanitize::randomQueryStringParam());
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
		echo " <a href=\"../course/course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
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
	echo '<div id="headerviewmsg" class="pagetitle"><h1>Message</h1></div>';



	$msgid = Sanitize::onlyInt($_GET['msgid']);

	//DB $query = "SELECT imas_msgs.*,imas_users.LastName,imas_users.FirstName,imas_users.email,imas_users.hasuserimg,imas_students.section ";
	//DB $query .= "FROM imas_msgs JOIN imas_users ON imas_msgs.msgfrom=imas_users.id LEFT JOIN imas_students ON imas_students.userid=imas_users.id AND imas_students.courseid='$cid' ";
	//DB $query .= "WHERE imas_msgs.id='$msgid' ";
	//DB if ($type!='allstu' || !$isteacher) {
		//DB $query .= "AND (imas_msgs.msgto='$userid' OR imas_msgs.msgfrom='$userid')";
	//DB }
	//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	//DB if (mysql_num_rows($result)==0) {
	$query = "SELECT imas_msgs.*,imas_users.LastName,imas_users.FirstName,imas_users.email,imas_users.hasuserimg,imas_students.section ";
	if ($type=='sent') {
		$query .= "FROM imas_msgs JOIN imas_users ON imas_msgs.msgto=imas_users.id ";
	} else {
		$query .= "FROM imas_msgs JOIN imas_users ON imas_msgs.msgfrom=imas_users.id ";
	}
	$query .= "LEFT JOIN imas_students ON imas_students.userid=imas_users.id AND imas_students.courseid=:courseid ";
	$query .= "WHERE imas_msgs.id=:id ";
	if ($type!='allstu' || !$isteacher) {
		$query .= "AND (imas_msgs.msgto=:msgto OR imas_msgs.msgfrom=:msgfrom)";
	}
	$stm = $DBH->prepare($query);
	if ($type!='allstu' || !$isteacher) {
		$stm->execute(array(':courseid'=>$cid, ':id'=>$msgid, ':msgto'=>$userid, ':msgfrom'=>$userid));
	} else {
		$stm->execute(array(':courseid'=>$cid, ':id'=>$msgid));
	}
	if ($stm->rowCount()==0) {
		echo "Message not found";
		require("../footer.php");
		exit;
	}
	//DB $line = mysql_fetch_array($result, MYSQL_ASSOC);
	$line = $stm->fetch(PDO::FETCH_ASSOC);

	$isteacher = isset($teacherof[$line['courseid']]);

	$senddate = tzdate("F j, Y, g:i a",$line['senddate']);
	$curdir = rtrim(dirname(__FILE__), '/\\');
	if ($line['hasuserimg']==1) {
		if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
			echo " <img style=\"float:left;\" src=\"{$urlmode}{$GLOBALS['AWSbucket']}.s3.amazonaws.com/cfiles/userimg_sm{$line['msgfrom']}.jpg\"  onclick=\"togglepic(this)\" alt=\"User picture\"/><br/>";
		} else {
			echo " <img style=\"float:left;\" src=\"$imasroot/course/files/userimg_sm{$line['msgfrom']}.jpg\"  onclick=\"togglepic(this)\" alt=\"User picture\"/><br/>";
		}
	}
	echo "<table class=gb ><tbody>";
	if ($type=='sent') {
		echo '<tr><td><b>'._('To').':</b></td>';
	} else {
		echo '<tr><td><b>'._('From').':</b></td>';
	}
	printf("<td>%s, %s", Sanitize::encodeStringForDisplay($line['LastName']),Sanitize::encodeStringForDisplay($line['FirstName']));
	if ($line['section']!='') {
		echo ' <span class="small">(Section: '.Sanitize::encodeStringForDisplay($line['section']).')</span>';
	}
	if (isset($teacherof[$line['courseid']])) {
		echo " <a href=\"mailto:".Sanitize::emailAddress($line['email'])."\">email</a> | ";
		echo " <a href=\"$imasroot/course/gradebook.php?cid=". Sanitize::courseId($line['courseid'])."&stu=".Sanitize::encodeUrlParam($line['msgfrom'])."\" target=\"_popoutgradebook\">gradebook</a>";
		if (preg_match('/Question\s+about\s+#(\d+)\s+in\s+(.*)\s*$/',$line['title'],$matches)) {
			//DB $aname = addslashes($matches[2]);
			$qn = $matches[1];
			$aname = $matches[2];

			//DB $query = "SELECT id,enddate FROM imas_assessments WHERE name='$aname' AND courseid='{$line['courseid']}'";
			//DB $res = mysql_query($query) or die("Query failed : $query " . mysql_error());
			$stm = $DBH->prepare("SELECT id,startdate,enddate,allowlate FROM imas_assessments WHERE (name=:name OR name=:name2) AND courseid=:courseid");
			$stm->execute(array(':name'=>$aname, ':name2'=>htmlentities($aname), ':courseid'=>$line['courseid']));
			//DB if (mysql_num_rows($res)>0) {
			if ($stm->rowCount()>0) {

				//DB list($aid,$due) = mysql_fetch_row($res);
				$adata = $stm->fetch(PDO::FETCH_ASSOC);
				$due = $adata['enddate'];

				//list($aid,$due) = $stm->fetch(PDO::FETCH_NUM);
				//DB $query = "SELECT enddate FROM imas_exceptions WHERE userid='{$line['msgfrom']}' AND assessmentid='$aid' AND itemtype='A'";
				//DB $res = mysql_query($query) or die("Query failed : $query " . mysql_error());
				//DB if (mysql_num_rows($res)>0) {
					//DB $due = mysql_result($res,0,0);
				$stm = $DBH->prepare("SELECT startdate,enddate,islatepass FROM imas_exceptions WHERE userid=:userid AND assessmentid=:assessmentid AND itemtype='A'");
				$stm->execute(array(':userid'=>$line['msgfrom'], ':assessmentid'=>$adata['id']));
				if ($stm->rowCount()>0) {
					$exception = $stm->fetch(PDO::FETCH_NUM);
					require_once("../includes/exceptionfuncs.php");
					$exceptionfuncs = new ExceptionFuncs($userid, $cid, true);
					$useexception = $exceptionfuncs->getCanUseAssessException($exception, $adata, true);
					if ($useexception) {
						$due = $exception[1];
					}
				}
				$duedate = tzdate('D m/d/Y g:i a',$due);

				//DB $query = "SELECT id FROM imas_assessment_sessions WHERE assessmentid='$aid' AND userid='{$line['msgfrom']}'";
				//DB $res = mysql_query($query) or die("Query failed : $query " . mysql_error());
				//DB if (mysql_num_rows($res)>0) {
					//DB $asid = mysql_result($res,0,0);
				$stm = $DBH->prepare("SELECT id FROM imas_assessment_sessions WHERE assessmentid=:assessmentid AND userid=:userid");
				$stm->execute(array(':assessmentid'=>$adata['id'], ':userid'=>$line['msgfrom']));
				if ($stm->rowCount()>0) {
					$asid = $stm->fetchColumn(0);
					echo " | <a href=\"$imasroot/course/gb-viewasid.php?cid=".Sanitize::courseId($line['courseid'])."&uid=".Sanitize::encodeUrlParam($line['msgfrom'])."&asid=".Sanitize::onlyInt($asid)."#qwrap".Sanitize::encodeUrlParam($qn)."\" target=\"_popoutgradebook\">assignment</a>";
					if ($due<2000000000) {
						echo ' <span class="small">Due '.Sanitize::encodeStringForDisplay($duedate).'</span>';
					}
				}
			}
		}
	}
	echo "</td></tr><tr><td><b>Sent:</b></td><td>$senddate</td></tr>";
	echo "<tr><td><b>Subject:</b></td><td>".Sanitize::encodeStringForDisplay($line['title'])."</td></tr>";
	echo "</tbody></table>";
	echo "<div style=\"border: 1px solid #000; margin: 10px; padding: 10px;\">";
	if (($p = strpos($line['message'],'<hr'))!==false) {
		$line['message'] = substr($line['message'],0,$p).'<a href="#" class="small" onclick="showtrimmed(this);return false;">[Show trimmed content]</a><div id="trimmed" style="display:none;">'.substr($line['message'],$p).'</div>';
	}
	echo filter($line['message']);
	echo "</div>";

	if ($type!='sent' && $type!='allstu') {
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
		if ($cansendmsgs) {
			echo "<button type=\"button\" onclick=\"window.location.href='msglist.php?"
				. Sanitize::generateQueryStringFromMap(array('cid' => $cid, 'filtercid' => $filtercid,
					'page' => $page, 'type' => $type, 'add' => 'new', 'to' => $line['msgfrom'], 'toquote' => $msgid))
				. "'\">"._('Reply')."</button> | ";
		}
		echo "<button type=\"button\" onclick=\"if(confirm('"._('Are you SURE you want to delete this message?')."')){window.location.href='msglist.php?"
			. Sanitize::generateQueryStringFromMap(array('cid' => $cid, 'filtercid' => $filtercid, 'page' => $page,
				'removeid' => $msgid, 'type' => $type))
			. "'}\">"._('Delete')."</button>";

		echo " | <button type=\"button\" onclick=\"window.location.href='viewmsg.php?"
			. Sanitize::generateQueryStringFromMap(array('markunread' => 'true', 'cid' => $cid,
				'filtercid' => $filtercid, 'page' => $page, 'msgid' => $msgid, 'type' => $type))
			. "'\">"._('Mark Unread')."</button>";

		echo " | <a href=\"msghistory.php?"
			. Sanitize::generateQueryStringFromMap(array('cid' => $cid, 'filtercid' => $filtercid, 'page' => $page,
				'msgid' => $msgid, 'type' => $type))
			."\">View Conversation</a> ";
		if ($isteacher && $line['courseid']==$cid) {
			echo " | <a href=\"$imasroot/course/gradebook.php?"
				. Sanitize::generateQueryStringFromMap(array('cid' => $line['courseid'], 'stu' => $line['msgfrom']))
			."\">Gradebook</a>";
		}

	} else if ($type=='sent' && $type!='allstu') {
		echo "<a href=\"msghistory.php?"
			. Sanitize::generateQueryStringFromMap(array('cid' => $cid, 'filtercid' => $filtercid, 'page' => $page,
				'msgid' => $msgid, 'type' => $type))
			."\">View Conversation</a>";

	}
	if ($type!='sent' && $type!='allstu' && ($line['isread']==0 || $line['isread']==4)) {
		//DB $query = "UPDATE imas_msgs SET isread=isread+1 WHERE id='$msgid'";
		//DB mysql_query($query) or die("Query failed : $query " . mysql_error());
		$stm = $DBH->prepare("UPDATE imas_msgs SET isread=isread+1 WHERE id=:id");
		$stm->execute(array(':id'=>$msgid));
	}
	require("../footer.php");
?>

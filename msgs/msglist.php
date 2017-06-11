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
  $to = ($_GET['to']) ? Sanitize::onlyInt($_GET['to']) : NULL;

	$cansendmsgs = false;
	$threadsperpage = intval($listperpage);

	$cid = Sanitize::courseId($_GET['cid']);
	if (!isset($_GET['page']) || $_GET['page']=='') {
		$page = 1;
	} else {
		$page = Sanitize::onlyInt($_GET['page']);
	}
	if ($page==-2) {
		$limittotagged = 1;
	} else {
		$limittotagged = 0;
	}
	if (isset($_GET['filtercid'])) {
		$filtercid = intval($_GET['filtercid']);
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

	if (isset($_GET['getstulist'])) {
		$cid = intval($_GET['getstulist']);
		if ($cid==0) { echo '[]'; exit;}

		//DB $query = "SELECT msgset FROM imas_courses WHERE id='$cid'";
		//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		//DB $msgset = mysql_result($result,0,0);
		$stm = $DBH->prepare("SELECT msgset FROM imas_courses WHERE id=:id");
		$stm->execute(array(':id'=>$cid));
		$msgset = $stm->fetchColumn(0);
		$msgmonitor = (floor($msgset/5)&1);
		$msgset = $msgset%5;

		$opts = array();
		if ($isteacher || $msgset<2) {
			//DB $query = "SELECT imas_users.id,imas_users.FirstName,imas_users.LastName FROM ";
			//DB $query .= "imas_users,imas_teachers WHERE imas_users.id=imas_teachers.userid AND ";
			//DB $query .= "imas_teachers.courseid='$cid' ORDER BY imas_users.LastName";
			//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			//DB while ($row = mysql_fetch_row($result)) {
			$query = "SELECT imas_users.id,imas_users.FirstName,imas_users.LastName FROM ";
			$query .= "imas_users,imas_teachers WHERE imas_users.id=imas_teachers.userid AND ";
			$query .= "imas_teachers.courseid=:courseid ORDER BY imas_users.LastName";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':courseid'=>$cid));
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$opts[] = "<option value=\"{$row[0]}\">{$row[2]}, {$row[1]}</option>";
			}

			//DB $query = "SELECT imas_users.id,imas_users.FirstName,imas_users.LastName FROM ";
			//DB $query .= "imas_users,imas_tutors WHERE imas_users.id=imas_tutors.userid AND ";
			//DB $query .= "imas_tutors.courseid='$cid' ";
      //DB if (!$isteacher && $studentinfo['section']!=null) {
			//DB 	$query .= "AND (imas_tutors.section='".addslashes($studentinfo['section'])."' OR imas_tutors.section='') ";
			//DB }
			//DB $query .= "ORDER BY imas_users.LastName";
			//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			//DB while ($row = mysql_fetch_row($result)) {
			$query = "SELECT imas_users.id,imas_users.FirstName,imas_users.LastName FROM ";
			$query .= "imas_users,imas_tutors WHERE imas_users.id=imas_tutors.userid AND ";
			$query .= "imas_tutors.courseid=:courseid ";
      if (!$isteacher && $studentinfo['section']!=null) {
			     $query .= "AND (imas_tutors.section=:section OR imas_tutors.section='') ";
      }
			$query .= "ORDER BY imas_users.LastName";
			$stm = $DBH->prepare($query);
      if (!$isteacher && $studentinfo['section']!=null) {
			   $stm->execute(array(':courseid'=>$cid, ':section'=>$studentinfo['section']));
      } else {
         $stm->execute(array(':courseid'=>$cid));
      }
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$opts[] = sprintf('<option value="%d">%s, %s</option>', $row[0],
                    Sanitize::encodeStringForDisplay($row[2]), Sanitize::encodeStringForDisplay($row[1]));
			}


		}
		if ($isteacher || $msgset==0 || $msgset==2) {
			//DB $query = "SELECT imas_users.id,imas_users.FirstName,imas_users.LastName FROM ";
			//DB $query .= "imas_users,imas_students WHERE imas_users.id=imas_students.userid AND ";
			//DB $query .= "imas_students.courseid='$cid' ORDER BY imas_users.LastName";
			//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			//DB while ($row = mysql_fetch_row($result)) {
			$query = "SELECT imas_users.id,imas_users.FirstName,imas_users.LastName FROM ";
			$query .= "imas_users,imas_students WHERE imas_users.id=imas_students.userid AND ";
			$query .= "imas_students.courseid=:courseid ORDER BY imas_users.LastName";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':courseid'=>$cid));
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$opts[] = sprintf('<option value="%d">%s, %s</option>', $row[0],
					Sanitize::encodeStringForDisplay($row[2]), Sanitize::encodeStringForDisplay($row[1]));
			}
		}
		echo json_encode($opts);
		exit;
	}
	if (isset($_GET['add'])) {
		if (isset($_POST['subject']) && isset($_POST['to']) && $_POST['to']!='0') {
			require_once("../includes/htmLawed.php");
			//DB $_POST['message'] = addslashes(myhtmLawed(stripslashes($_POST['message'])));
			//DB $_POST['subject'] = addslashes(htmlentities(stripslashes($_POST['subject'])));
      $_POST['message'] = myhtmLawed($_POST['message']);
			$_POST['subject'] = htmlentities($_POST['subject']);

      $now = time();
			//DB $query = "INSERT INTO imas_msgs (title,message,msgto,msgfrom,senddate,isread,courseid) VALUES ";
			//DB $query .= "('{$_POST['subject']}','{$_POST['message']}','{$_POST['to']}','$userid',$now,0,'{$_POST['courseid']}')";
			//DB mysql_query($query) or die("Query failed : $query " . mysql_error());
			//DB $msgid = mysql_insert_id();
			$query = "INSERT INTO imas_msgs (title,message,msgto,msgfrom,senddate,isread,courseid) VALUES ";
			$query .= "(:title, :message, :msgto, :msgfrom, :senddate, :isread, :courseid)";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':title'=>$_POST['subject'], ':message'=>$_POST['message'], ':msgto'=>$_POST['to'],
        ':msgfrom'=>$userid, ':senddate'=>$now, ':isread'=>0, ':courseid'=>$_POST['courseid']));
			$msgid = $DBH->lastInsertId();

			if ($_GET['replyto']>0) {
				$query = "UPDATE imas_msgs SET replied=1";
				if (isset($_POST['sendunread'])) {
					$query .= ',isread=(isread&~1)';
				}
        $query .= " WHERE id=:id";
				//DB $query .= " WHERE id='{$_GET['replyto']}'";
				//DB mysql_query($query) or die("Query failed : $query " . mysql_error());
				$stm = $DBH->prepare($query);
				$stm->execute(array(':id'=>$_GET['replyto']));
				//DB $query = "SELECT baseid FROM imas_msgs WHERE id='{$_GET['replyto']}'";
				//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
				//DB $baseid = mysql_result($result,0,0);
				$stm = $DBH->prepare("SELECT baseid FROM imas_msgs WHERE id=:id");
				$stm->execute(array(':id'=>$_GET['replyto']));
				$baseid = $stm->fetchColumn(0);
				if ($baseid==0) {
					$baseid = $_GET['replyto'];
				}
				//DB $query = "UPDATE imas_msgs SET baseid='$baseid',parent='{$_GET['replyto']}' WHERE id='$msgid'";
				//DB mysql_query($query) or die("Query failed : $query " . mysql_error());
				$stm = $DBH->prepare("UPDATE imas_msgs SET baseid=:baseid,parent=:parent WHERE id=:id");
				$stm->execute(array(':baseid'=>$baseid, ':parent'=>$_GET['replyto'], ':id'=>$msgid));
			}
			//DB $query = "SELECT name FROM imas_courses WHERE id='{$_POST['courseid']}'";
			//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			//DB $cname = mysql_result($result,0,0);
			$stm = $DBH->prepare("SELECT name FROM imas_courses WHERE id=:id");
			$stm->execute(array(':id'=>$_POST['courseid']));
			$cname = $stm->fetchColumn(0);

			//DB $query = "SELECT msgnotify,email FROM imas_users WHERE id='{$_POST['to']}'";
			//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			$stm = $DBH->prepare("SELECT msgnotify,email,FCMtoken FROM imas_users WHERE id=:id");
			$stm->execute(array(':id'=>$_POST['to']));
      list($msgnotify, $email, $FCMtokenTo) = $stm->fetch(PDO::FETCH_NUM);
			//DB if (mysql_result($result,0,0)==1) {
      if ($msgnotify==1) {
				//DB $email = mysql_result($result,0,1);
				//DB $query = "SELECT FirstName,LastName FROM imas_users WHERE id='$userid'";
				//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
				//DB $from = mysql_result($result,0,0).' '.mysql_result($result,0,1);
				/*$stm = $DBH->prepare("SELECT FirstName,LastName FROM imas_users WHERE id=:id");
				$stm->execute(array(':id'=>$userid));
				$from = implode(' ', $stm->fetch(PDO::FETCH_NUM));*/
				$headers  = 'MIME-Version: 1.0' . "\r\n";
				$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
				$headers .= "From: $sendfrom\r\n";
				$message  = "<h4>This is an automated message.  Do not respond to this email</h4>\r\n";
				$message .= "<p>You've received a new message</p><p>From: $userfullname<br />Course: $cname.</p>\r\n";
				//DB $message .= "<p>Subject: ".stripslashes($_POST['subject'])."</p>";
        $message .= "<p>Subject: ". Sanitize::encodeStringForDisplay($_POST['subject'])."</p>";
				$message .= "<a href=\"" . $GLOBALS['basesiteurl'] . "/msgs/viewmsg.php?cid=" . Sanitize::courseId($_POST['courseid']) . "&msgid=$msgid\">";
				$message .= "View Message</a></p>\r\n";
				$message .= "<p>If you do not wish to receive email notification of new messages, please ";
				$message .= "<a href=\"" . $GLOBALS['basesiteurl'] . "/forms.php?action=chguserinfo\">click here to change your ";
				$message .= "user preferences</a></p>\r\n";
				mail($email,'New message notification',$message,$headers);
			}
			if ($FCMtokenTo != '') {
				require_once("../includes/FCM.php");
				$url = $GLOBALS['basesiteurl'] . "/msgs/viewmsg.php?cid=".Sanitize::courseId($_POST['courseid'])."&msgid=$msgid";
				sendFCM($FCMtokenTo,"Msg from: $userfullname",$_POST['subject'],$url);
			}
			if ($type=='new') {
				header('Location: ' . $GLOBALS['basesiteurl'] . "/msgs/newmsglist.php?cid=$cid");
			} else {
				header('Location: ' . $GLOBALS['basesiteurl'] . "/msgs/msglist.php?page=$page&cid=$cid&filtercid=$filtercid");
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
				}
				function updateTo(el) {
					var newcid = $(el).val();
					$("#to").hide();
					if (newcid>0) {
						$(el).after($("<img>", {src: imasroot+"/img/updating.gif", alt: "Loading recipients..."}));
						$.ajax({
							url: "msglist.php?cid=0&getstulist="+newcid,
							dataType: "json"
						}).done(function(optarr) {
							$("#to").empty().append("<option value=\"0\">Select a recipient...</option>");
							for (var i=0;i<optarr.length;i++) {
								$("#to").append($(optarr[i]));
							}
							$("#to").show();
							$(el).siblings("img").remove();
						});
					} else {
						$("#to").val(0);
					}
				}
				</script>';
			require("../header.php");
			echo "<div class=breadcrumb>$breadcrumbbase ";
			if ($cid>0 && (!isset($sessiondata['ltiitemtype']) || $sessiondata['ltiitemtype']!=0)) {
				echo "<a href=\"../course/course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
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
				echo "&gt; <a href=\"viewmsg.php?page=$page&type=".Sanitize::encodeUrlParam($type)."&cid=$cid&filtercid=$filtercid&msgid=".Sanitize::onlyInt($replyto)."\">Message</a> ";
				echo "&gt; Reply</div>";
				echo "<h2>Reply</h2>\n";
			} else {
				echo "&gt; New Message</div>";
				echo "<h2>New Message</h2>\n";
			}


			if ($filtercid>0) {
				//DB $query = "SELECT msgset FROM imas_courses WHERE id='$filtercid'";
				//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
				//DB $msgset = mysql_result($result,0,0);
				$stm = $DBH->prepare("SELECT msgset FROM imas_courses WHERE id=:id");
				$stm->execute(array(':id'=>$filtercid));
				$msgset = $stm->fetchColumn(0);
				$msgmonitor = (floor($msgset/5)&1);
				$msgset = $msgset%5;
			} else {
				$course_array = array();
				//DB $query = "SELECT i_c.id,i_c.name,i_c.msgset,2 AS userrole FROM imas_courses AS i_c JOIN imas_teachers ON ";
				//DB $query .= "i_c.id=imas_teachers.courseid WHERE imas_teachers.userid='$userid' ";
				//DB $query .= "UNION SELECT i_c.id,i_c.name,i_c.msgset,1 AS userrole FROM imas_courses AS i_c JOIN imas_tutors ON ";
				//DB $query .= "i_c.id=imas_tutors.courseid WHERE imas_tutors.userid='$userid' ";
				//DB $query .= "UNION SELECT i_c.id,i_c.name,i_c.msgset,0 AS userrole FROM imas_courses AS i_c JOIN imas_students ON ";
				//DB $query .= "i_c.id=imas_students.courseid WHERE imas_students.userid='$userid' ";
				//DB $query .= "ORDER BY userrole DESC, name";
				//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
				//DB while ($row = mysql_fetch_assoc($result)) {
				$query = "SELECT i_c.id,i_c.name,i_c.msgset,2 AS userrole FROM imas_courses AS i_c JOIN imas_teachers ON ";
				$query .= "i_c.id=imas_teachers.courseid WHERE imas_teachers.userid=:userid ";
				$query .= "UNION SELECT i_c.id,i_c.name,i_c.msgset,1 AS userrole FROM imas_courses AS i_c JOIN imas_tutors ON ";
				$query .= "i_c.id=imas_tutors.courseid WHERE imas_tutors.userid=:userid2 ";
				$query .= "UNION SELECT i_c.id,i_c.name,i_c.msgset,0 AS userrole FROM imas_courses AS i_c JOIN imas_students ON ";
				$query .= "i_c.id=imas_students.courseid WHERE imas_students.userid=:userid3 ";
				$query .= "ORDER BY userrole DESC, name";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':userid'=>$userid, ':userid2'=>$userid, ':userid3'=>$userid));
				while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
					if ($row['userrole']==0 && $row['msgset']%5>=3) {continue;}
					if (!isset($course_array[$row['userrole']])) {
						$course_array[$row['userrole']] = array();
					}
					$course_array[$row['userrole']][] = $row;
				}
				$courseopts = '';
				for ($i=2;$i>=0;$i--) {
					if (isset($course_array[$i])) {
						$courseopts .= '<optgroup label="';
						if ($i==2) { $courseopts .= _("Teaching"); }
						else if ($i==1) { $courseopts .= _("Tutoring"); }
						else if ($i==0) { $courseopts .= _("Student"); }
						$courseopts .= '">';
						foreach ($course_array[$i] as $r) {
							$courseopts .= '<option value="'.$r['id'].'">'.$r['name'].'</option>';
						}
						$courseopts .= '</optgroup>';
					}
				}
			}

			$courseid=($cid==0)?$filtercid:$cid;
			if (isset($_GET['toquote']) || isset($_GET['replyto'])) {
				//DB $query = "SELECT title,message,courseid FROM imas_msgs WHERE id='$replyto'";
				//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
				$stm = $DBH->prepare("SELECT title,message,courseid FROM imas_msgs WHERE id=:id");
				$stm->execute(array(':id'=>$replyto));
        list($title, $message, $courseid) = $stm->fetch(PDO::FETCH_NUM);
				$title = "Re: ".str_replace('"','&quot;',$title);
				if (isset($_GET['toquote'])) {
					//DB $message = mysql_result($result,0,1);
					$message = '<br/><hr/>In reply to:<br/>'.$message;
				} else {
					$message = '';
				}
				//DB $courseid = mysql_result($result,0,2);
			} else if (isset($_GET['quoteq'])) {
				require("../assessment/displayq2.php");
				$parts = explode('-',$_GET['quoteq']);
				$GLOBALS['assessver'] = $parts[4];
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

				$message = '<br/><hr/>'.$message;
				//$message .= '<span class="hidden">QREF::'.htmlentities($_GET['quoteq']).'</span>';
				if (isset($parts[3])) {  //sending out of assessment instructor
					//DB $query = "SELECT name FROM imas_assessments WHERE id='".intval($parts[3])."'";
					//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
					$stm = $DBH->prepare("SELECT name FROM imas_assessments WHERE id=:id");
					$stm->execute(array(':id'=>$parts[3]));
					if (isset($teacherid) || isset($tutorid)) {
						//DB $title = 'Question #'.($parts[0]+1).' in '.str_replace('"','&quot;',mysql_result($result,0,0));
						$title = 'Question #'.($parts[0]+1).' in '.str_replace('"','&quot;',$stm->fetchColumn(0));
					} else {
						//DB $title = 'Question about #'.($parts[0]+1).' in '.str_replace('"','&quot;',mysql_result($result,0,0));
						$title = 'Question about #'.($parts[0]+1).' in '.str_replace('"','&quot;',$stm->fetchColumn(0));
					}
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
			} else {
				$title = '';
				$message = '';
			}

			echo "<form method=post action=\"msglist.php?page=$page&type=".Sanitize::encodeUrlParam($type)."&cid=$cid&add={$_GET['add']}&replyto=".Sanitize::onlyInt($replyto).'"';
			if (!isset($_GET['to'])) {
				echo " onsubmit=\"return checkrecipient();\"";
			}
			echo ">\n";
			echo "<span class=form>To:</span><span class=formright>\n";
			if (isset($_GET['to'])) {
				//DB $query = "SELECT iu.LastName,iu.FirstName,iu.email,i_s.lastaccess,iu.hasuserimg FROM imas_users AS iu ";
				//DB $query .= "LEFT JOIN imas_students AS i_s ON iu.id=i_s.userid AND i_s.courseid='$courseid' WHERE iu.id='$to'";
				//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
				//DB $row = mysql_fetch_row($result);
				$query = "SELECT iu.LastName,iu.FirstName,iu.email,i_s.lastaccess,iu.hasuserimg FROM imas_users AS iu ";
				$query .= "LEFT JOIN imas_students AS i_s ON iu.id=i_s.userid AND i_s.courseid=:courseid WHERE iu.id=:id";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':courseid'=>$courseid, ':id'=>$_GET['to']));
				$row = $stm->fetch(PDO::FETCH_NUM);
				echo $row[0].', '.$row[1];
				$ismsgsrcteacher = false;
				if ($courseid==$cid && $isteacher) {
					$ismsgsrcteacher = true;
				} else if ($courseid!=$cid) {
					//DB $query = "SELECT id FROM imas_teachers WHERE userid='$userid' AND courseid='$courseid'";
					//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
					//DB if (mysql_num_rows($result)!=0) {
					$stm = $DBH->prepare("SELECT id FROM imas_teachers WHERE userid=:userid AND courseid=:courseid");
					$stm->execute(array(':userid'=>$userid, ':courseid'=>$courseid));
					if ($stm->rowCount()!=0) {
						$ismsgsrcteacher = true;
					}
				}
				if ($ismsgsrcteacher) {
					echo " <a href=\"mailto:{$row[2]}\">email</a> | ";
					echo " <a href=\"$imasroot/course/gradebook.php?cid=$courseid&stu=$to\" target=\"_popoutgradebook\">gradebook</a>";
					if ($row[3]!=null) {
						echo " | Last login ".tzdate("F j, Y, g:i a",$row[3]);
					}
				}
				echo "<input type=hidden name=to value=\"$to\"/>";
				$curdir = rtrim(dirname(__FILE__), '/\\');
				if (isset($_GET['to']) && $row[4]==1) {
					if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
						echo " <img style=\"vertical-align: middle;\" src=\"{$urlmode}{$GLOBALS['AWSbucket']}.s3.amazonaws.com/cfiles/userimg_sm$to.jpg\"  onclick=\"togglepic(this)\" alt=\"User picture\"/><br/>";
					} else {
						echo " <img style=\"vertical-align: middle;\" src=\"$imasroot/course/files/userimg_sm$to.jpg\"  onclick=\"togglepic(this)\" alt=\"User picture\"/><br/>";
					}
				}
				echo "<input type=hidden name=courseid value=\"$courseid\"/>\n";
			} else {
				if ($filtercid>0) {
					echo "<select name=\"to\" id=\"to\">";
					echo '<option value="0">Select a recipient...</option>';
					if ($isteacher || $msgset<2) {
						//DB $query = "SELECT imas_users.id,imas_users.FirstName,imas_users.LastName FROM ";
						//DB $query .= "imas_users,imas_teachers WHERE imas_users.id=imas_teachers.userid AND ";
						//DB $query .= "imas_teachers.courseid='$courseid' ORDER BY imas_users.LastName";
						//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
						//DB while ($row = mysql_fetch_row($result)) {
						$query = "SELECT imas_users.id,imas_users.FirstName,imas_users.LastName FROM ";
						$query .= "imas_users,imas_teachers WHERE imas_users.id=imas_teachers.userid AND ";
						$query .= "imas_teachers.courseid=:courseid ORDER BY imas_users.LastName";
						$stm = $DBH->prepare($query);
						$stm->execute(array(':courseid'=>$courseid));
						$cnt = $stm->rowCount();
						while ($row = $stm->fetch(PDO::FETCH_NUM)) {
							echo "<option value=\"{$row[0]}\"";
							if ($cnt==1 && $msgset==1 && !$isteacher) {
								echo ' selected="selected"';
							}
							echo ">{$row[2]}, {$row[1]}</option>";
						}
            //DB $query = "SELECT imas_users.id,imas_users.FirstName,imas_users.LastName FROM ";
      			//DB $query .= "imas_users,imas_tutors WHERE imas_users.id=imas_tutors.userid AND ";
      			//DB $query .= "imas_tutors.courseid='$cid' ";
            //DB if (!$isteacher && $studentinfo['section']!=null) {
      			//DB 	$query .= "AND (imas_tutors.section='".addslashes($studentinfo['section'])."' OR imas_tutors.section='') ";
      			//DB }
      			//DB $query .= "ORDER BY imas_users.LastName";
      			//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
      			//DB while ($row = mysql_fetch_row($result)) {
      			$query = "SELECT imas_users.id,imas_users.FirstName,imas_users.LastName FROM ";
      			$query .= "imas_users,imas_tutors WHERE imas_users.id=imas_tutors.userid AND ";
      			$query .= "imas_tutors.courseid=:courseid ";
            if (!$isteacher && $studentinfo['section']!=null) {
      			     $query .= "AND (imas_tutors.section=:section OR imas_tutors.section='') ";
            }
      			$query .= "ORDER BY imas_users.LastName";
      			$stm = $DBH->prepare($query);
            if (!$isteacher && $studentinfo['section']!=null) {
      			   $stm->execute(array(':courseid'=>$cid, ':section'=>$studentinfo['section']));
            } else {
               $stm->execute(array(':courseid'=>$cid));
            }
            //DB while ($row = mysql_fetch_row($result)) {
            while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				            printf('<option value="%d">%s, %s</option>', $row[0],
                                Sanitize::encodeStringForDisplay($row[2]), Sanitize::encodeStringForDisplay($row[1]));
						}


					}
					if ($isteacher || $msgset==0 || $msgset==2) {
						//DB $query = "SELECT imas_users.id,imas_users.FirstName,imas_users.LastName FROM ";
						//DB $query .= "imas_users,imas_students WHERE imas_users.id=imas_students.userid AND ";
						//DB $query .= "imas_students.courseid='$courseid' ORDER BY imas_users.LastName";
						//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
						//DB while ($row = mysql_fetch_row($result)) {
						$query = "SELECT imas_users.id,imas_users.FirstName,imas_users.LastName FROM ";
						$query .= "imas_users,imas_students WHERE imas_users.id=imas_students.userid AND ";
						$query .= "imas_students.courseid=:courseid ORDER BY imas_users.LastName";
						$stm = $DBH->prepare($query);
						$stm->execute(array(':courseid'=>$courseid));
						while ($row = $stm->fetch(PDO::FETCH_NUM)) {
							printf('<option value="%d">%s, %s</option>', $row[0],
                                Sanitize::encodeStringForDisplay($row[2]), Sanitize::encodeStringForDisplay($row[1]));
						}
					}
					echo "</select>";
					echo "<input type=hidden name=courseid value=\"$courseid\"/>\n";
				} else {
					echo '<select name="courseid" onchange="updateTo(this)" aria-label="Select a course">';
					echo '<option value="0">Select a course...</option>';
					echo $courseopts;
					echo '</select><br/>';
					echo '<select name="to" id="to" style="display:none;" aria-label="Select an individual ">';
					echo '<option value="0">Select an individual...</option></select>';
				}

			}



			echo "</span><br class=form />";

			echo "<span class=form><label for=\"subject\">Subject:</label></span>";
			echo "<span class=formright><input type=text size=50 name=subject id=subject value=\"".Sanitize::encodeStringForDisplay($title)."\"></span><br class=form>\n";
			echo "<span class=form><label for=\"message\">Message:</label></span>";
			echo "<span class=left><div class=editor><textarea id=message name=message style=\"width: 100%;\" rows=20 cols=70>";
			echo htmlentities($message);
			echo "</textarea></div></span><br class=form>\n";
			if ($replyto>0) {
				echo '<span class="form"></span><span class="formright"><input type="checkbox" name="sendunread" id="sendunread" value="1"/> <label for="sendunread">'._('Mark original message unread').'</label></span><br class="form"/>';
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
  		//DB $checklist = "'".implode("','",$_POST['checked'])."'";
      $checklist = implode(',', array_map('intval', $_POST['checked']));
  		$query = "UPDATE imas_msgs SET isread=(isread&~1) WHERE id IN ($checklist) AND (isread&1)=1";
  		//DB mysql_query($query) or die("Query failed : $query " . mysql_error());
      $DBH->query($query);
	 }
	}
	if (isset($_POST['markread'])) {
		if (count($_POST['checked'])>0) {
      //DB $checklist = "'".implode("','",$_POST['checked'])."'";
      $checklist = implode(',', array_map('intval', $_POST['checked']));
      $query = "UPDATE imas_msgs SET isread=(isread|1) WHERE id IN ($checklist) AND (isread&1)=0";
      //DB mysql_query($query) or die("Query failed : $query " . mysql_error());
      $DBH->query($query);
	  }
	}
	if (isset($_POST['remove'])) {
		if (count($_POST['checked'])>0) {
      //DB $checklist = "'".implode("','",$_POST['checked'])."'";
      $checklist = implode(',', array_map('intval', $_POST['checked']));
  		$query = "DELETE FROM imas_msgs WHERE id IN ($checklist) AND (isread&4)=4";
      //DB mysql_query($query) or die("Query failed : $query " . mysql_error());
      $DBH->query($query);
  		$query = "UPDATE imas_msgs SET isread=(isread|2) WHERE id IN ($checklist)";
      //DB mysql_query($query) or die("Query failed : $query " . mysql_error());
      $DBH->query($query);
  		if ($type=='new') {
  			header('Location: ' . $GLOBALS['basesiteurl'] . "/msgs/newmsglist.php?cid=$cid");
  		} else {
  			header('Location: ' . $GLOBALS['basesiteurl'] . "/msgs/msglist.php?page=$page&cid=$cid&filtercid=$filtercid");
  		}
  		exit;
  	}
	}
	if (isset($_GET['removeid'])) {
		//DB $query = "DELETE FROM imas_msgs WHERE id='{$_GET['removeid']}' AND (isread&4)=4";
		//DB mysql_query($query) or die("Query failed : $query " . mysql_error());
		$stm = $DBH->prepare("DELETE FROM imas_msgs WHERE id=:id AND (isread&4)=4");
		$stm->execute(array(':id'=>$_GET['removeid']));
		//DB $query = "UPDATE imas_msgs SET isread=(isread|2) WHERE id='{$_GET['removeid']}'";
		//DB mysql_query($query) or die("Query failed : $query " . mysql_error());
		$stm = $DBH->prepare("UPDATE imas_msgs SET isread=(isread|2) WHERE id=:id");
		$stm->execute(array(':id'=>$_GET['removeid']));
	}

	$pagetitle = "Messages";
	$placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/msg.js\"></script>";
	$placeinhead .= "<script type=\"text/javascript\">var AHAHsaveurl = '". $GLOBALS['basesiteurl'] . "/msgs/savetagged.php?cid=$cid';</script>";
	$placeinhead .= '<style type="text/css"> tr.tagged {background-color: #dff;}</style>';
	require("../header.php");
	$curdir = rtrim(dirname(__FILE__), '/\\');

	echo "<div class=breadcrumb>$breadcrumbbase ";
	if ($cid>0 && (!isset($sessiondata['ltiitemtype']) || $sessiondata['ltiitemtype']!=0)) {
		echo " <a href=\"../course/course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
	}
	echo " Message List</div>";
	echo '<div id="headermsglist" class="pagetitle"><h2>Messages</h2></div>';

	if ($myrights > 5 && $filtercid>0) {
		//DB $query = "SELECT msgset FROM imas_courses WHERE id='$filtercid'";
		//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		//DB $msgset = mysql_result($result,0,0);
		$stm = $DBH->prepare("SELECT msgset FROM imas_courses WHERE id=:id");
		$stm->execute(array(':id'=>$filtercid));
		$msgset = $stm->fetchColumn(0);
		$msgmonitor = (floor($msgset/5)&1);
		$msgset = $msgset%5;
		if ($msgset<3 || $isteacher) {
			$cansendmsgs = true;
		}
	} else if ($myrights > 5 && $filtercid==0) {
		$cansendmsgs = true;
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

  //DB $query = "SELECT COUNT(id) FROM imas_msgs WHERE msgto='$userid' AND (isread&2)=0";
  //DB if ($filtercid>0) {
	//DB 	$query .= " AND courseid='$filtercid'";
	//DB }
	//DB if ($filteruid>0) {
	//DB 	$query .= " AND msgfrom='$filteruid'";
	//DB }
	//DB if ($limittotagged==1) {
	//DB 	$query .= " AND (isread&8)=8";
	//DB }

	$query = "SELECT COUNT(id) FROM imas_msgs WHERE msgto=:msgto AND (isread&2)=0";
  $qarr = array(':msgto'=>$userid);
	if ($filtercid>0) {
		$query .= " AND courseid=:courseid";
    $qarr[':courseid'] = $filtercid;
	}
	if ($filteruid>0) {
		$query .= " AND msgfrom=:msgfrom";
    $qarr[':msgfrom'] = $filteruid;
	}
	if ($limittotagged==1) {
		$query .= " AND (isread&8)=8";
	}
	//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
  $stm = $DBH->prepare($query);
  $stm->execute($qarr);

	//DB $numpages = ceil(mysql_result($result,0,0)/$threadsperpage);
	$numpages = ceil($stm->fetchColumn(0)/$threadsperpage);
	if ($numpages==0 && $filteruid>0) {
		//might have changed filtercid w/o changing user.
		//we'll open up to all users then
		$filteruid = 0;
		//DB $query = "SELECT COUNT(id) FROM imas_msgs WHERE msgto='$userid' AND (isread&2)=0";
		//DB if ($filtercid>0) {
		//DB 	$query .= " AND courseid='$filtercid'";
		//DB }
		//DB if ($limittotagged==1) {
		//DB 	$query .= " AND (isread&8)=8";
		//DB }
    $query = "SELECT COUNT(id) FROM imas_msgs WHERE msgto=:msgto AND (isread&2)=0";
		if ($filtercid>0) {
			$query .= " AND courseid=:courseid";
		}
		if ($limittotagged==1) {
			$query .= " AND (isread&8)=8";
		}
    $stm = $DBH->prepare($query);
    if ($filtercid>0) {
      $stm->execute(array(':msgto'=>$userid, ':courseid'=>$filtercid));
    } else {
      $stm->execute(array(':msgto'=>$userid));
    }
		//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		//DB $numpages = ceil(mysql_result($result,0,0)/$threadsperpage);
		$numpages = ceil($stm->fetchColumn(0)/$threadsperpage);
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
	$address = $GLOBALS['basesiteurl'] . "/msgs/msglist.php?cid=$cid&filtercid=";


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
	//DB $query = "SELECT DISTINCT imas_courses.id,imas_courses.name FROM imas_courses,imas_msgs WHERE imas_courses.id=imas_msgs.courseid AND imas_msgs.msgto='$userid'";
	//DB $query .= " ORDER BY imas_courses.name";
	//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	//DB while ($row = mysql_fetch_row($result)) {
	$query = "SELECT DISTINCT imas_courses.id,imas_courses.name FROM imas_courses,imas_msgs WHERE imas_courses.id=imas_msgs.courseid AND imas_msgs.msgto=:msgto";
	$query .= " ORDER BY imas_courses.name";
	$stm = $DBH->prepare($query);
	$stm->execute(array(':msgto'=>$userid));
	$msgcourses = array();
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$msgcourses[$row[0]] = $row[1];
	}
	if (!isset($msgcourses[$cid])) {
		$msgcourses[$cid] = $coursename;
	}
	natsort($msgcourses);
	foreach ($msgcourses as $k=>$v) {
		echo "<option value=\"$k\" ";
		if ($filtercid==$k) {
			echo 'selected=1';
		}
		echo " >$v</option>";
	}
	echo "</select> ";
	echo 'By sender: <select id="filteruid" onchange="chgfilter()"><option value="0" ';
	if ($filteruid==0) {
		echo 'selected="selected" ';
	}
	echo '>All</option>';
	//DB $query = "SELECT DISTINCT imas_users.id, imas_users.LastName, imas_users.FirstName FROM imas_users ";
	//DB $query .= "JOIN imas_msgs ON imas_msgs.msgfrom=imas_users.id WHERE imas_msgs.msgto='$userid'";
	//DB if ($filtercid>0) {
		//DB $query .= " AND imas_msgs.courseid='$filtercid'";
	//DB }
	//DB $query .= " ORDER BY imas_users.LastName, imas_users.FirstName";
	//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	//DB while ($row = mysql_fetch_row($result)) {
	$query = "SELECT DISTINCT imas_users.id, imas_users.LastName, imas_users.FirstName FROM imas_users ";
	$query .= "JOIN imas_msgs ON imas_msgs.msgfrom=imas_users.id WHERE imas_msgs.msgto=:msgto";
	if ($filtercid>0) {
    	$query .= " AND imas_msgs.courseid=:courseid";
  }
	$query .= " ORDER BY imas_users.LastName, imas_users.FirstName";
	$stm = $DBH->prepare($query);
  if ($filtercid>0) {
	  $stm->execute(array(':msgto'=>$userid, ':courseid'=>$filtercid));
  } else {
    $stm->execute(array(':msgto'=>$userid));
  }
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
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
  $offset = ($page-1)*$threadsperpage;
  //DB $query = "SELECT imas_msgs.id,imas_msgs.title,imas_msgs.senddate,imas_msgs.replied,imas_users.LastName,imas_users.FirstName,imas_msgs.isread,imas_courses.name,imas_msgs.msgfrom,imas_users.hasuserimg ";
	//DB $query .= "FROM imas_msgs LEFT JOIN imas_users ON imas_users.id=imas_msgs.msgfrom LEFT JOIN imas_courses ON imas_courses.id=imas_msgs.courseid WHERE ";
	//DB $query .= "imas_msgs.msgto='$userid' AND (imas_msgs.isread&2)=0 ";
	//DB if ($filteruid>0) {
	//DB 	$query .= "AND imas_msgs.msgfrom='$filteruid' ";
	//DB }
	//DB if ($filtercid>0) {
	//DB 	$query .= "AND imas_msgs.courseid='$filtercid' ";
	//DB }
	//DB if ($limittotagged) {
	//DB 	$query .= "AND (imas_msgs.isread&8)=8 ";
	//DB }
	//DB $query .= "ORDER BY senddate DESC ";
	//DB if (!$limittotagged) {
	//DB 	$query .= "LIMIT $offset,$threadsperpage";// OFFSET $offset";
	//DB }

  $query = "SELECT imas_msgs.id,imas_msgs.title,imas_msgs.senddate,imas_msgs.replied,imas_users.LastName,imas_users.FirstName,imas_msgs.isread,imas_courses.name,imas_msgs.msgfrom,imas_users.hasuserimg ";
	$query .= "FROM imas_msgs LEFT JOIN imas_users ON imas_users.id=imas_msgs.msgfrom LEFT JOIN imas_courses ON imas_courses.id=imas_msgs.courseid WHERE ";
	$query .= "imas_msgs.msgto=:msgto AND (imas_msgs.isread&2)=0 ";
  $qarr = array(':msgto'=>$userid);
  if ($filteruid>0) {
		$query .= "AND imas_msgs.msgfrom=:msgfrom ";
    $qarr[':msgfrom']=$filteruid;
	}
	if ($filtercid>0) {
		$query .= "AND imas_msgs.courseid=:courseid ";
    $qarr[':courseid']=$filtercid;
	}
	if ($limittotagged) {
		$query .= "AND (imas_msgs.isread&8)=8 ";
	}
	$query .= "ORDER BY senddate DESC ";
	if (!$limittotagged) {
		$query .= "LIMIT $offset,$threadsperpage";// INT values
	}
	//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
  $stm = $DBH->prepare($query);
  $stm->execute($qarr);
	//DB if (mysql_num_rows($result)==0) {
	if ($stm->rowCount()==0) {
		echo "<tr><td></td><td>No messages</td><td></td></tr>";
	}
	//DB while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
	while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
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
		echo "<a href=\"viewmsg.php?page=$page&cid=$cid&filtercid=$filtercid&filteruid=$filteruid&type=msg&msgid={$line['id']}\">";
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
				echo " <img src=\"{$urlmode}{$GLOBALS['AWSbucket']}.s3.amazonaws.com/cfiles/userimg_sm{$line['msgfrom']}.jpg\" style=\"display:none;\"  class=\"userpic\"  alt=\"User picture\"/>";
			} else {
				echo " <img src=\"$imasroot/course/files/userimg_sm{$line['msgfrom']}.jpg\" style=\"display:none;\" class=\"userpic\"  alt=\"User picture\"/>";
			}
		}

		echo "</td><td>";
		if (($line['isread']&8)==8) {
			echo "<img class=\"pointer\" id=\"tag{$line['id']}\" src=\"$imasroot/img/flagfilled.gif\" onClick=\"toggletagged({$line['id']});return false;\" alt=\"Flagged\"/>";
		} else {
			echo "<img class=\"pointer\" id=\"tag{$line['id']}\" src=\"$imasroot/img/flagempty.gif\" onClick=\"toggletagged({$line['id']});return false;\" alt=\"Not flagged\"/>";
		}
		echo '</td>';
		echo "<td>{$line['LastName']}, {$line['FirstName']}</td>";


		if ($line['name']==null) {
			$line['name'] = "[Deleted]";
		}
		echo "<td>".Sanitize::encodeStringForDisplay($line['name'])."</td>";
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

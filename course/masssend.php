<?php
//IMathAS:  Mass send email or message to students; called from List Users or Gradebook
//(c) 2006 David Lippman


	if (!(isset($teacherid))) {
		require("../header.php");
		echo "You need to log in as a teacher to access this page";
		require("../footer.php");
		exit;
	}

	if (isset($_POST['message'])) {
		$toignore = array();
		if (intval($_POST['aidselect'])!=0) {
			$limitaid = $_POST['aidselect'];
			$limittype = $_POST['limittype'];

			if ($limittype=='comp') {
				$query = "SELECT IAS.userid FROM imas_assessment_sessions AS IAS WHERE ";
				$query .= "IAS.bestscores NOT LIKE '%-1%' AND IAS.assessmentid=:assessmentid";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':assessmentid'=>$limitaid));
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					$toignore[] = $row[0];
				}
			} else if ($limittype=='start') {
				$query = "SELECT IAS.userid FROM imas_assessment_sessions AS IAS WHERE ";
				$query .= "IAS.assessmentid=:assessmentid";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':assessmentid'=>$limitaid));
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					$toignore[] = $row[0];
				}
			}
		}
		require_once("../includes/htmLawed.php");
		//DB $_POST['message'] = addslashes(myhtmLawed(stripslashes($_POST['message'])));
		//DB $_POST['subject'] = addslashes(strip_tags(stripslashes($_POST['subject'])));
		$_POST['message'] = myhtmLawed($_POST['message']);
		$_POST['subject'] = strip_tags($_POST['subject']);

		if ($_GET['masssend']=="Message") {
			$now = time();
			//DB $tolist = "'".implode("','",explode(",",$_POST['tolist']))."'";
			$tolist = implode(',', array_map('intval', explode(",",$_POST['tolist'])));
			//DB $query = "SELECT FirstName,LastName,id,msgnotify,email FROM imas_users WHERE id IN ($tolist)";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->query("SELECT FirstName,LastName,id,msgnotify,email FROM imas_users WHERE id IN ($tolist)");
			$emailaddys = array();
			//DB while ($row = mysql_fetch_row($result)) {
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				if (!in_array($row[2],$toignore)) {
					$fullnames[$row[2]] = strip_tags($row[1]. ', '.$row[0]);
					//DB $firstnames[$row[2]] = addslashes($row[0]);
					//DB $lastnames[$row[2]] = addslashes($row[1]);
					$firstnames[$row[2]] = strip_tags($row[0]);
					$lastnames[$row[2]] = strip_tags($row[1]);

					if ($row[3]==1) {
						$emailaddys[$row[2]] = "{$row[0]} {$row[1]} <{$row[4]}>";
					}
				}
			}

			$tolist = explode(',',$_POST['tolist']);

			if (isset($_POST['savesent'])) {
				$isread = 0;
			} else {
				$isread = 4;
			}

			//DB $query = "SELECT FirstName,LastName FROM imas_users WHERE id='$userid'";
			//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			//DB $from = mysql_result($result,0,0).' '.mysql_result($result,0,1);
			$stm = $DBH->prepare("SELECT FirstName,LastName FROM imas_users WHERE id=:id");
			$stm->execute(array(':id'=>$userid));
			$from = implode(' ', $stm->fetch(PDO::FETCH_NUM));
			$headers  = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
			$headers .= "From: $sendfrom\r\n";
			$messagep1 = "<h4>This is an automated message.  Do not respond to this email</h4>\r\n";
			$messagep1 .= "<p>You've received a new message</p><p>From: $from<br />Course: $coursename.</p>\r\n";
			//DB $messagep1 .= "<p>Subject: ".stripslashes($_POST['subject'])."</p>";
			$messagep1 .= "<p>Subject: ".$_POST['subject']."</p>";
			$messagep1 .= "<a href=\"" . $GLOBALS['basesiteurl'] . "/msgs/viewmsg.php?cid=$cid&msgid=";
			$messagep2 = "\">";
			$messagep2 .= "View Message</a></p>\r\n";
			$messagep2 .= "<p>If you do not wish to receive email notification of new messages, please ";
			$messagep2 .= "<a href=\"" . $GLOBALS['basesiteurl'] . "/forms.php?action=chguserinfo\">click here to change your ";
			$messagep2 .= "user preferences</a></p>\r\n";

			foreach ($tolist as $msgto) {
				if (!in_array($msgto,$toignore)) {
					$message = str_replace(array('LastName','FirstName'),array($lastnames[$msgto],$firstnames[$msgto]),$_POST['message']);
					//DB $query = "INSERT INTO imas_msgs (title,message,msgto,msgfrom,senddate,isread,courseid) VALUES ";
					//DB $query .= "('{$_POST['subject']}','$message','$msgto','$userid',$now,$isread,'$cid')";
					//DB mysql_query($query) or die("Query failed : " . mysql_error());
					//DB $msgid = mysql_insert_id();
					$query = "INSERT INTO imas_msgs (title,message,msgto,msgfrom,senddate,isread,courseid) VALUES ";
					$query .= "(:title, :message, :msgto, :msgfrom, :senddate, :isread, :courseid)";
					$stm = $DBH->prepare($query);
					$stm->execute(array(':title'=>$_POST['subject'], ':message'=>$message, ':msgto'=>$msgto, ':msgfrom'=>$userid,
						':senddate'=>$now, ':isread'=>$isread, ':courseid'=>$cid));
					$msgid = $DBH->lastInsertId();
					if (isset($emailaddys[$msgto])) {
						mail($emailaddys[$msgto],'New message notification',$messagep1.$msgid.$messagep2,$headers);
					}
				}
			}

			$tolist = array();
			if ($_POST['self']=="self") {
				$tolist[] = $userid;
			} else if ($_POST['self']=="allt") {
				//DB $query = "SELECT imas_users.id FROM imas_teachers,imas_users WHERE imas_teachers.courseid='$cid' AND imas_teachers.userid=imas_users.id ";
				//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
				//DB while ($row = mysql_fetch_row($result)) {
				$stm = $DBH->prepare("SELECT imas_users.id FROM imas_teachers,imas_users WHERE imas_teachers.courseid=:courseid AND imas_teachers.userid=imas_users.id ");
				$stm->execute(array(':courseid'=>$cid));
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					$tolist[] = $row[0];
				}
			}
			$sentto = implode('<br/>',$fullnames);
			//DB $message = $_POST['message'] . addslashes("<p>Instructor note: Message sent to these students from course $coursename: <br/> $sentto </p>\n");
			$message = $_POST['message'] . "<p>Instructor note: Message sent to these students from course $coursename: <br/> $sentto </p>\n";
			if (isset($_POST['tutorcopy'])) {
				$message .= '<p>A copy was sent to all tutors.</p>';
				$stm = $DBH->prepare("SELECT imas_users.id FROM imas_tutors,imas_users WHERE imas_tutors.courseid=:courseid AND imas_tutors.userid=imas_users.id ");
				$stm->execute(array(':courseid'=>$cid));
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					$tolist[] = $row[0];
				}
			}
			foreach ($tolist as $msgto) {
				//DB $query = "INSERT INTO imas_msgs (title,message,msgto,msgfrom,senddate,isread,courseid) VALUES ";
				//DB $query .= "('{$_POST['subject']}','$message','$msgto','$userid',$now,0,'$cid')";
				//DB mysql_query($query) or die("Query failed : " . mysql_error());
				$query = "INSERT INTO imas_msgs (title,message,msgto,msgfrom,senddate,isread,courseid) VALUES ";
				$query .= "(:title, :message, :msgto, :msgfrom, :senddate, :isread, :courseid)";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':title'=>$_POST['subject'], ':message'=>$message, ':msgto'=>$msgto, ':msgfrom'=>$userid, ':senddate'=>$now, ':isread'=>0, ':courseid'=>$cid));
			}

		} else {

			//$query = "SELECT imas_users.FirstName,imas_users.LastName,imas_users.email,imas_users.id ";
			//$query .= "FROM imas_students,imas_users WHERE imas_students.courseid='$cid' AND imas_students.userid=imas_users.id";
			//DB $tolist = "'".implode("','",explode(",",$_POST['tolist']))."'";
			$tolist = implode(',', array_map('intval', explode(",",$_POST['tolist'])));
			//DB $query = "SELECT FirstName,LastName,email,id FROM imas_users WHERE id IN ($tolist)";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->query("SELECT FirstName,LastName,email,id FROM imas_users WHERE id IN ($tolist)");
			$emailaddys = array();
			//DB while ($row = mysql_fetch_row($result)) {
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				if (!in_array($row[3],$toignore)) {
					$emailaddys[] = "{$row[0]} {$row[1]} <{$row[2]}>";
					$firstnames[] = $row[0];
					$lastnames[] = $row[1];
				}
			}

			//if (isset($_POST['limit']) && $_POST['aidselect']!=0) {
				$sentto = implode('<br/>',$emailaddys);
			//} else {
			//	$sentto = "All students";
			//}
			//DB $subject = stripslashes($_POST['subject']);
			//DB $message = stripslashes($_POST['message']);
			$subject = $_POST['subject']; // Sanitized by strip_tags near line 40.
			$message = $_POST['message']; // Sanitized by myhtmLawed near line 40.
			$sessiondata['mathdisp']=2;
			$sessiondata['graphdisp']=2;
			require("../filter/filter.php");
			$message = filter($message);
			$message = preg_replace('/<img([^>])*src="\//','<img $1 src="'.$urlmode  . Sanitize::domainNameWithPort($_SERVER['HTTP_HOST']) .'/',$message);
			$headers  = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
			//DB $query = "SELECT FirstName,LastName,email FROM imas_users WHERE id='$userid'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $row = mysql_fetch_row($result);
			$stm = $DBH->prepare("SELECT FirstName,LastName,email FROM imas_users WHERE id=:id");
			$stm->execute(array(':id'=>$userid));
			$row = $stm->fetch(PDO::FETCH_NUM);
			$self = "{$row[0]} {$row[1]} <{$row[2]}>";
			//$headers .= "From: $self\r\n";
			$headers .= "From: $sendfrom\r\n";
			$headers .= "Reply-To: $self\r\n";
			$message = "<p><b>Note:</b>This email was sent by ".htmlentities($self)." from $installname. If you need to reply, make sure your reply goes to their email address.</p><p></p>".$message;
			$teacheraddys = array();
			if ($_POST['self']!="none") {
				$teacheraddys[] = $self;
			}
			foreach ($emailaddys as $k=>$addy) {
				$addy = trim($addy);
				if ($addy!='' && $addy!='none@none.com') {
					mail($addy,$subject,str_replace(array('LastName','FirstName'),array($lastnames[$k],$firstnames[$k]),$message),$headers);
				}
			}


			$message .= "<p>Instructor note: Email sent to these students from course $coursename: <br/> $sentto </p>\n";
			if (isset($_POST['tutorcopy'])) {
				$message .= '<p>A copy was sent to all tutors.</p>';
			}
			if ($_POST['self']=="allt") {
				//DB $query = "SELECT imas_users.FirstName,imas_users.LastName,imas_users.email,imas_users.id ";
				//DB $query .= "FROM imas_teachers,imas_users WHERE imas_teachers.courseid='$cid' AND imas_teachers.userid=imas_users.id ";
				//DB $query .= "AND imas_users.id<>'$userid'";
				//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
				//DB while ($row = mysql_fetch_row($result)) {
				$query = "SELECT imas_users.FirstName,imas_users.LastName,imas_users.email,imas_users.id ";
				$query .= "FROM imas_teachers,imas_users WHERE imas_teachers.courseid=:courseid AND imas_teachers.userid=imas_users.id ";
				$query .= "AND imas_users.id<>:userid";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':courseid'=>$cid, ':userid'=>$userid));
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					$teacheraddys[] = "{$row[0]} {$row[1]} <{$row[2]}>";
				}
				$message .= "<p>A copy was also emailed to all instructors for this course</p>\n";
			}
			if (isset($_POST['tutorcopy'])) {
				$query = "SELECT imas_users.FirstName,imas_users.LastName,imas_users.email,imas_users.id ";
				$query .= "FROM imas_teachers,imas_users WHERE imas_tutors.courseid=:courseid AND imas_tutors.userid=imas_users.id ";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':courseid'=>$cid));
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					$teacheraddys[] = "{$row[0]} {$row[1]} <{$row[2]}>";
				}
			}
			//$headers  = 'MIME-Version: 1.0' . "\r\n";
			//$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
			//$headers .= "From: $sendfrom\r\n";
			foreach ($teacheraddys as $addy) {
				mail($addy,$subject,$message,$headers);
			}
			//mail(implode(', ',$emailaddys),$subject,$message,$headers);
		}
		if ($calledfrom=='lu') {
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/listusers.php?cid=$cid");
		} else if ($calledfrom=='gb') {
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/gradebook.php?cid=$cid");
		} else if ($calledfrom=='itemsearch') {
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/admin2.php");
		}
		exit;
	} else {
		$stm = $DBH->prepare("SELECT count(id) FROM imas_tutors WHERE courseid=:courseid");
		$stm->execute(array(':courseid'=>$_GET['cid']));
		$hastutors = ($stm->fetchColumn(0)>0);

		$sendtype = (isset($_POST['posted']))?$_POST['posted']:$_POST['submit']; //E-mail or Message
		$useeditor = "message";
		$pagetitle = "Send Mass $sendtype";
		require("../header.php");
		echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
		if ($calledfrom=='lu') {
			echo "&gt; <a href=\"listusers.php?cid=$cid\">List Students</a> &gt; Send Mass $sendtype</div>\n";
		} else if ($calledfrom=='gb') {
			echo "&gt; <a href=\"gradebook.php?cid=$cid&gbmode={$_GET['gbmode']}\">Gradebook</a> &gt; Send Mass $sendtype</div>\n";
		} else if ($calledfrom=='itemsearch') {
			echo "&gt; Send Mass $sendtype</div>\n";
		}
		if (count($_POST['checked'])==0) {
			echo "No users selected.  ";
			if ($calledfrom=='lu') {
				echo "<a href=\"listusers.php?cid=$cid\">Try again</a>\n";
			} else if ($calledfrom=='gb') {
				echo "<a href=\"gradebook.php?cid=$cid&gbmode={$_GET['gbmode']}\">Try again</a>\n";
			}
			require("../footer.php");
			exit;
		}
		echo '<div id="headermasssend" class="pagetitle">';
		echo "<h3>Send Mass $sendtype</h3>\n";
		echo '</div>';
		if ($calledfrom=='lu') {
			echo "<form method=post action=\"listusers.php?cid=$cid&masssend=$sendtype\">\n";
		} else if ($calledfrom=='gb') {
			echo "<form method=post action=\"gradebook.php?cid=$cid&gbmode={$_GET['gbmode']}&masssend=$sendtype\">\n";
		} else if ($calledfrom=='itemsearch') {
			echo "<form method=post action=\"itemsearch.php?masssend=$sendtype\">\n";
		}
		echo "<span class=form><label for=\"subject\">Subject:</label></span>";
		echo "<span class=formright><input type=text size=50 name=subject id=subject value=\"{$line['subject']}\"></span><br class=form>\n";
		echo "<span class=form><label for=\"message\">Message:</label></span>";
		echo "<span class=left><div class=editor><textarea id=message name=message style=\"width: 100%;\" rows=20 cols=70> </textarea></div></span><br class=form>\n";
		echo "<p><i>Note:</i> <b>FirstName</b> and <b>LastName</b> can be used as form-mail fields that will autofill with each students' first/last name</p>";
		echo "<span class=form><label for=\"self\">Send copy to:</label></span>";
		echo "<span class=formright><input type=radio name=self id=self value=\"none\">Only Students<br/> ";
		echo "<input type=radio name=self id=self value=\"self\" checked=checked>Students and you<br/> ";
		echo "<input type=radio name=self id=self value=\"allt\">Students and all instructors of this course";
		if ($hastutors) {
			echo '<br/><input type="checkbox" name="tutorcopy" id="tutorcopy" value="tutorcopy">Also send a copy to tutors';
		}
		echo '</span><br class=form>';
		if ($sendtype=='Message') {
			echo '<span class="form"><label for="savesent">Save in sent messages?</label></span>';
			echo '<span class="formright"><input type="checkbox" name="savesent" checked="checked" /></span><br class="form" />';
		}


		echo "<span class=form><label for=\"limit\">Limit send: </label></span>";
		echo "<span class=formright>";
		echo 'Only send to students who haven\'t <select name="limittype">';
		echo ' <option value="start" selected>started</option>';
		echo ' <option value="comp">tried every problem in</option>';
		echo '</select> this assessment: ';
		echo "<select name=\"aidselect\" id=\"aidselect\">\n";
		echo "<option value=\"0\">Don't limit - send to all</option>\n";
		//DB $query = "SELECT id,name from imas_assessments WHERE courseid='$cid'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB while ($line=mysql_fetch_array($result, MYSQL_ASSOC)) {
		$stm = $DBH->prepare("SELECT id,name from imas_assessments WHERE courseid=:courseid ORDER BY name");
		$stm->execute(array(':courseid'=>$cid));
		while ($line=$stm->fetch(PDO::FETCH_ASSOC)) {
			echo "<option value=\"{$line['id']}\" ";
			if (isset($_GET['aid']) && ($_GET['aid']==$line['id'])) {echo "SELECTED";}
			echo ">{$line['name']}</option>\n";
		}
		echo "</select>\n";
		echo "<input type=hidden name=\"tolist\" value=\"" . implode(',',$_POST['checked']) . "\">\n";
		echo "</span><br class=form />\n";
		echo "<div class=submit><input type=submit value=\"Send $sendtype\"></div>\n";
		echo "</form>\n";
		//DB $tolist = "'".implode("','",$_POST['checked'])."'";
		$tolist = implode(',', array_map('intval', $_POST['checked']));
		//DB $query = "SELECT LastName,FirstName,SID FROM imas_users WHERE id IN ($tolist) ORDER BY LastName,FirstName";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		$stm = $DBH->query("SELECT LastName,FirstName,SID FROM imas_users WHERE id IN ($tolist) ORDER BY LastName,FirstName");
		echo '<p>Unless limited, message will be sent to:<ul>';
		//DB while ($row = mysql_fetch_row($result)) {
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			printf("<li>%s, %s (%s)</li>", Sanitize::encodeStringForDisplay($row[0]),
				Sanitize::encodeStringForDisplay($row[1]), Sanitize::encodeStringForDisplay($row[2]));
		}
		echo '</ul>';
		require("../footer.php");
		exit;
	}

?>

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
			$query = "SELECT IAS.userid FROM imas_assessment_sessions AS IAS WHERE ";
			$query .= "IAS.scores NOT LIKE '%-1%' AND IAS.assessmentid='$limitaid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				$toignore[] = $row[0];
			}
		}
		require_once("../includes/htmLawed.php");
		$htmlawedconfig = array('elements'=>'*-script');
		$_POST['message'] = addslashes(htmLawed(stripslashes($_POST['message']),$htmlawedconfig));
		$_POST['subject'] = strip_tags($_POST['subject']);
		if ($_GET['masssend']=="Message") {
			$now = time();
			$tolist = "'".implode("','",explode(",",$_POST['tolist']))."'";
			$query = "SELECT FirstName,LastName,id FROM imas_users WHERE id IN ($tolist)";
			
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$emailaddys = array();
			while ($row = mysql_fetch_row($result)) {
				if (!in_array($row[2],$toignore)) {
					$fullnames[$row[2]] = $row[1]. ', '.$row[0];
					$firstnames[$row[2]] = addslashes($row[0]);
					$lastnames[$row[2]] = addslashes($row[1]);
				}
			}
			
			$tolist = explode(',',$_POST['tolist']);
			
			if (isset($_POST['savesent'])) {
				$isread = 0;
			} else {
				$isread = 4;
			}
			
			foreach ($tolist as $msgto) {
				if (!in_array($msgto,$toignore)) {
					$message = str_replace(array('LastName','FirstName'),array($lastnames[$msgto],$firstnames[$msgto]),$_POST['message']);
					$query = "INSERT INTO imas_msgs (title,message,msgto,msgfrom,senddate,isread,courseid) VALUES ";
					$query .= "('{$_POST['subject']}','$message','$msgto','$userid',$now,$isread,'$cid')";
					mysql_query($query) or die("Query failed : " . mysql_error());
				}
			}
			
			$tolist = array();
			if ($_POST['self']=="self") {
				$tolist[] = $userid;
			} else if ($_POST['self']=="allt") {
				$query = "SELECT imas_users.id FROM imas_teachers,imas_users WHERE imas_teachers.courseid='$cid' AND imas_teachers.userid=imas_users.id ";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				while ($row = mysql_fetch_row($result)) {
					$tolist[] = $row[0];
				}
			}
			$sentto = implode('<br/>',$fullnames);
			$message = $_POST['message'] . addslashes("<p>Instructor note: Message sent to these students from course $coursename: <br/> $sentto </p>\n");
			
			foreach ($tolist as $msgto) {
				$query = "INSERT INTO imas_msgs (title,message,msgto,msgfrom,senddate,isread,courseid) VALUES ";
				$query .= "('{$_POST['subject']}','$message','$msgto','$userid',$now,0,'$cid')";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
			
		} else {
					
			//$query = "SELECT imas_users.FirstName,imas_users.LastName,imas_users.email,imas_users.id ";
			//$query .= "FROM imas_students,imas_users WHERE imas_students.courseid='$cid' AND imas_students.userid=imas_users.id";
			$tolist = "'".implode("','",explode(",",$_POST['tolist']))."'";
			$query = "SELECT FirstName,LastName,email,id FROM imas_users WHERE id IN ($tolist)";
			
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$emailaddys = array();
			while ($row = mysql_fetch_row($result)) {
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
			$subject = stripslashes($_POST['subject']);
			$message = stripslashes($_POST['message']);
			$sessiondata['mathdisp']=2;
			$sessiondata['graphdisp']=2;
			require("../filter/filter.php");
			$message = filter($message);
			$message = preg_replace('/<img([^>])*src="\//','<img $1 src="'.$urlmode  . $_SERVER['HTTP_HOST'].'/',$message);
			$headers  = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
			$query = "SELECT FirstName,LastName,email FROM imas_users WHERE id='$userid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$row = mysql_fetch_row($result);
			$self = "{$row[0]} {$row[1]} <{$row[2]}>";
			$headers .= "From: $self\r\n";
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
			if ($_POST['self']=="allt") {
				$query = "SELECT imas_users.FirstName,imas_users.LastName,imas_users.email,imas_users.id ";
				$query .= "FROM imas_teachers,imas_users WHERE imas_teachers.courseid='$cid' AND imas_teachers.userid=imas_users.id ";
				$query .= "AND imas_users.id<>'$userid'";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				while ($row = mysql_fetch_row($result)) {
					$teacheraddys[] = "{$row[0]} {$row[1]} <{$row[2]}>";	
				}
				$message .= "<p>A copy was also emailed to all instructors for this course</p>\n";
			}
			
			foreach ($teacheraddys as $addy) {
				mail($addy,$subject,$message,$headers);
			}
			//mail(implode(', ',$emailaddys),$subject,$message,$headers);
		}
		if ($calledfrom=='lu') {
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/listusers.php?cid=$cid");
		} else if ($calledfrom=='gb') {
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gradebook.php?cid=$cid");
		}
		exit;
	} else {
		$sendtype = (isset($_POST['posted']))?$_POST['posted']:$_POST['submit']; //E-mail or Message
		$useeditor = "message";
		$pagetitle = "Send Mass $sendtype";
		require("../header.php");
		echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
		if ($calledfrom=='lu') {
			echo "&gt; <a href=\"listusers.php?cid=$cid\">List Students</a> &gt; Send Mass $sendtype</div>\n";
		} else if ($calledfrom=='gb') {
			echo "&gt; <a href=\"gradebook.php?cid=$cid&gbmode={$_GET['gbmode']}\">Gradebook</a> &gt; Send Mass $sendtype</div>\n";
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
		}
		echo "<span class=form><label for=\"subject\">Subject:</label></span>";
		echo "<span class=formright><input type=text size=50 name=subject id=subject value=\"{$line['subject']}\"></span><br class=form>\n";
		echo "<span class=form><label for=\"message\">Message:</label></span>";
		echo "<span class=left><div class=editor><textarea id=message name=message style=\"width: 100%;\" rows=20 cols=70> </textarea></div></span><br class=form>\n";
		echo "<p><i>Note:</i> <b>FirstName</b> and <b>LastName</b> can be used as form-mail fields that will autofill with each students' first/last name</p>";
		echo "<span class=form><label for=\"self\">Send copy to:</label></span>";
		echo "<span class=formright><input type=radio name=self id=self value=\"none\">Only Students<br/> ";
		echo "<input type=radio name=self id=self value=\"self\" checked=checked>Self<br/> ";
		echo "<input type=radio name=self id=self value=\"allt\">All instructors of this course</span><br class=form>\n";
		if ($sendtype=='Message') {
			echo '<span class="form"><label for="savesent">Save in sent messages?</label></span>';
			echo '<span class="formright"><input type="checkbox" name="savesent" checked="checked" /></span><br class="form" />';
		}
			
		
		echo "<span class=form><label for=\"limit\">Limit send: </label></span>";
		echo "<span class=formright>";
		echo "to students who haven't completed: ";
		echo "<select name=\"aidselect\" id=\"aidselect\">\n";
		echo "<option value=\"0\">Don't limit - send to all</option>\n";
		$query = "SELECT id,name from imas_assessments WHERE courseid='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($line=mysql_fetch_array($result, MYSQL_ASSOC)) {
			echo "<option value=\"{$line['id']}\" ";
			if (isset($_GET['aid']) && ($_GET['aid']==$line['id'])) {echo "SELECTED";}
			echo ">{$line['name']}</option>\n";
		}
		echo "</select>\n";
		echo "<input type=hidden name=\"tolist\" value=\"" . implode(',',$_POST['checked']) . "\">\n";
		echo "</span><br class=form />\n";
		echo "<div class=submit><input type=submit value=\"Send $sendtype\"></div>\n";
		echo "</form>\n";
		$tolist = "'".implode("','",$_POST['checked'])."'";
		$query = "SELECT LastName,FirstName,SID FROM imas_users WHERE id IN ($tolist) ORDER BY LastName,FirstName";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		echo '<p>Unless limited, message will be sent to:<ul>';
		while ($row = mysql_fetch_row($result)) {
			echo "<li>{$row[0]}, {$row[1]} ({$row[2]})</li>";
		}
		echo '</ul>';
		require("../footer.php");
		exit;
	}
		
?>

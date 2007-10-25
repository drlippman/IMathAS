<?php
	//IMathAS:  Basic Actions
	//(c) 20006 David Lippman
	if ($_GET['action']=="newuser") {
		require_once("config.php");
		if ($loginformat!='' && !preg_match($loginformat,$_POST['SID'])) {
			echo "<html><body>\n";
			echo "$loginprompt is invalid.  <a href=\"forms.php?action=newuser\">Try Again</a>\n";
			echo "</html></body>\n";
			exit;
		}
		$query = "SELECT id FROM imas_users WHERE SID='{$_POST['SID']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		if (mysql_num_rows($result)>0) {
			echo "<html><body>\n";
			echo "$loginprompt '{$_POST['SID']}' is used.  <a href=\"forms.php?action=newuser\">Try Again</a>\n";
			echo "</html></body>\n";
			exit;
		}
		if (!preg_match('/^[a-zA-Z][\w\.-]*[a-zA-Z0-9]@[a-zA-Z0-9][\w\.-]*[a-zA-Z0-9]\.[a-zA-Z][a-zA-Z\.]*[a-zA-Z]$/',$_POST['email'])) {
			echo "<html><body>\n";
			echo "Invalid email address.  <a href=\"forms.php?action=newuser\">Try Again</a>\n";
			echo "</html></body>\n";
			exit;
		}
		if ($_POST['pw1'] != $_POST['pw2']) {
			echo "<html><body>\n";
			echo "Passwords don't match.  <a href=\"forms.php?action=newuser\">Try Again</a>\n";
			echo "</html></body>\n";
			exit;
		}
		if ($_POST['SID']=="" || $_POST['firstname']=="" || $_POST['lastname']=="" || $_POST['email']=="" || $_POST['pw1']=="") {
			echo "<html><body>\n";
			echo "Please include all information.  <a href=\"forms.php?action=newuser\">Try Again</a>\n";
			echo "</html></body>\n";
			exit;
		}
		$md5pw = md5($_POST['pw1']);
		if ($emailconfirmation) {$initialrights = 0;} else {$initialrights = 10;}
		if (isset($_POST['msgnot'])) {
			$msgnot = 1;
		} else {
			$msgnot = 0;
		}
		$query = "INSERT INTO imas_users (SID, password, rights, FirstName, LastName, email, msgnotify) ";
		$query .= "VALUES ('{$_POST['SID']}','$md5pw',$initialrights,'{$_POST['firstname']}','{$_POST['lastname']}','{$_POST['email']}',$msgnot);";
		mysql_query($query) or die("Query failed : " . mysql_error());
		if ($emailconfirmation) {
			$id = mysql_insert_id();
			$headers  = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
			$headers .= "From: $sendfrom\r\n";
			$message  = "<h4>This is an automated message from $installname.  Do not respond to this email</h4>\r\n";
			$message .= "<p>To complete your $installname registration, please click on the following link, or copy ";
			$message .= "and paste it into your webbrowser:</p>\r\n";
			$message .= "<a href=\"http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/actions.php?action=confirm&id=$id\">";
			$message .= "http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/actions.php?action=confirm&id=$id</a>\r\n";
			mail($_POST['email'],'IMathAS Confirmation',$message,$headers);
			echo "<html><body>\n";
			echo "Registration recorded.  You should shortly receive an email with confirmation instructions.";
			echo "<a href=\"$imasroot/index.php\">Back to main login page</a>\n";
			echo "</body></html>\n";
			exit;
		} else {
			echo "<html><body>\n";
			echo "<p>Your account with username <b>{$_POST['SID']}</b> has been created.  If you forget your password, you can ask your ";
			echo "instructor to reset your password.</p>\n";
			echo "You can now <a href=\"http://";
			echo $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/index.php";
			echo "\">return to the login page</a> and login with your new username and password</p>";
			echo "</body></html>";
		}
		//header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/index.php");
		exit;
	} else if ($_GET['action']=="confirm") {
		require_once("config.php");
		$query = "UPDATE imas_users SET rights=10 WHERE id='{$_GET['id']}' AND rights=0";
		mysql_query($query) or die("Query failed : " . mysql_error());
		if (mysql_affected_rows()>0) {
			echo "<html><body>\n";
			echo "Confirmed.  Please <a href=\"index.php\">Log In</a>\n";
			echo "</html></body>\n";	
			exit;
		} else {
			echo "<html><body>\n";
			echo "Error.\n";
			echo "</html></body>\n";
		}
	} else if ($_GET['action']=="resetpw") {
		require_once("config.php");
		if (isset($_POST['username'])) {
			$query = "SELECT password,id,email FROM imas_users WHERE SID='{$_POST['username']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_num_rows($result)>0) {
				$code = mysql_result($result,0,0);
				$id = mysql_result($result,0,1);
				$headers  = 'MIME-Version: 1.0' . "\r\n";
				$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
				$headers .= "From: $sendfrom\r\n";
				$message  = "<h4>This is an automated message from $installname.  Do not respond to this email</h4>\r\n";
				$message .= "<p>Your username was entered in the Reset Password page.  If you did not do this, you may ignore and delete this message. ";
				$message .= "If you did request a password reset, click the link below, or copy and paste it into your browser's address bar.  Your ";
				$message .= "password will then be reset to: password.</p>";
				$message .= "<a href=\"http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/actions.php?action=resetpw&id=$id&code=$code\">";
				$message .= "http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/actions.php?action=resetpw&id=$id&code=$code</a>\r\n";
				mail(mysql_result($result,0,2),'Password Reset Request',$message,$headers);
			} else {
				echo "Invalid Username.  <a href=\"index.php\">Try again</a>";
				exit;
			}
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/index.php");
		} else if (isset($_GET['code'])) {
			$query = "SELECT password FROM imas_users WHERE id='{$_GET['id']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_num_rows($result)>0 && $_GET['code']===mysql_result($result,0,0)) {
				$newpw = md5("password");
				$query = "UPDATE imas_users SET password='$newpw' WHERE id='{$_GET['id']}' LIMIT 1";
				mysql_query($query) or die("Query failed : " . mysql_error());
				echo "Password Reset.  ";
				echo "<a href=\"index.php\">Login with password: password</a>";
				echo "<p>After logging in, select Change User Info to change your password</p>";
				exit;
			}
		}
	}
	
	require("validate.php");
	if ($_GET['action']=="logout") {
		$sessionid = session_id();
		$query = "DELETE FROM imas_sessions WHERE sessionid='$sessionid'";
		mysql_query($query) or die("Query failed : " . mysql_error());
		$_SESSION = array();
		if (isset($_COOKIE[session_name()])) {
			setcookie(session_name(), '', time()-42000, '/');
		}
		session_destroy();
	} else if ($_GET['action']=="chgpwd") {
		$query = "SELECT password FROM imas_users WHERE id = '$userid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$line = mysql_fetch_array($result, MYSQL_ASSOC);
		if ((md5($_POST['oldpw'])==$line['password']) && ($_POST['newpw1'] == $_POST['newpw2']) && $myrights>5) {
			$md5pw =md5($_POST['newpw1']);
			$query = "UPDATE imas_users SET password='$md5pw' WHERE id='$userid'";
			mysql_query($query) or die("Query failed : " . mysql_error()); 
		} else {
			echo "<html><body>Password change failed.  <A HREF=\"forms.php?action=chgpwd\">Try Again</a>\n";
			echo "</body></html>\n";
			exit;
		}
		
	} else if ($_GET['action']=="enroll") {
		if ($myrights < 6) {
			echo "<html><body>\nError: Guests can't enroll in courses</body></html";
			exit;
		}
		if ($_POST['cid']=="" || $_POST['ekey']=="" || !is_numeric($_POST['cid'])) {
			echo "<html><body>\n";
			echo "Please include both Course ID and Enrollment Key.  <a href=\"index.php\">Try Again</a>\n";
			echo "</html></body>\n";
			exit;
		}
		$query = "SELECT enrollkey FROM imas_courses WHERE id = '{$_POST['cid']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$line = mysql_fetch_array($result, MYSQL_ASSOC);
		if ($line == null) {
			echo "<html><body>\n";
			echo "Course not found.  <a href=\"index.php\">Try Again</a>\n";
			echo "</html></body>\n";
			exit;
		} else if ($line['enrollkey'] != $_POST['ekey']) {
			echo "<html><body>\n";
			echo "Incorrect Enrollment Key.  <a href=\"index.php\">Try Again</a>\n";
			echo "</html></body>\n";
			exit;
		} else {
			$query = "SELECT * FROM imas_students WHERE userid='$userid' AND courseid='{$_POST['cid']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_num_rows($result)>0) {
				echo "<html><body>\n";
				echo "You are already enrolled in the course.  Click on the course name on the <a href=\"index.php\">main page</a> to access the course\n";
				echo "</html></body>\n";
				exit;
			} else {
				$query = "INSERT INTO imas_students (userid,courseid) VALUES ('$userid','{$_POST['cid']}');";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
		}	
	} else if ($_GET['action']=="unenroll") {
		if ($myrights < 6) {
			echo "<html><body>\nError: Guests can't unenroll from courses</body></html";
			exit;
		}
		if (!isset($_GET['cid'])) {
			echo "<html><body>\n";
			echo "Course ID not specified.  <a href=\"index.php\">Try Again</a>\n";
			echo "</html></body>\n";
			exit;
		}
		$cid = $_GET['cid'];
		$query = "DELETE FROM imas_students WHERE userid='$userid' AND courseid='$cid'";
		mysql_query($query) or die("Query failed : " . mysql_error());
		$query = "SELECT id FROM imas_assessments WHERE courseid='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			$query = "DELETE FROM imas_assessment_sessions WHERE assessmentid='{$row[0]}' AND userid='$userid'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			$query = "DELETE FROM imas_exceptions WHERE assessmentid='{$row[0]}' AND userid='$userid'";
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
		$query = "SELECT id FROM imas_gbitems WHERE courseid='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			$query = "DELETE FROM imas_grades WHERE gbitemid='{$row[0]}' AND userid='$userid'";
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
		$query = "SELECT id FROM imas_forums WHERE courseid='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			$q2 = "SELECT threadid FROM imas_forum_posts WHERE forumid='{$row[0]}'";
			$r2 = mysql_query($q2) or die("Query failed : " . mysql_error());
			while ($rw2 = mysql_fetch_row($r2)) {
				$query = "DELETE FROM imas_forum_views WHERE threadid='{$rw2[0]}' AND userid='$userid'";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
		}
	} else if ($_GET['action']=="chguserinfo") {
		if (isset($_POST['msgnot'])) {
			$msgnot = 1;
		} else {
			$msgnot = 0;
		}
		if (isset($_POST['qrd']) || $myrights<20) {
			$qrightsdef = 0;
		} else {
			$qrightsdef = 2;
		}
		if (isset($_POST['usedeflib'])) {
			$usedeflib = 1;
		} else {
			$usedeflib = 0;
		}
		if ($myrights<20) {
			$deflib = 0;
		} else {
			$deflib = $_POST['libs'];
		}
		$query = "UPDATE imas_users SET FirstName='{$_POST['firstname']}',LastName='{$_POST['lastname']}',email='{$_POST['email']}',msgnotify=$msgnot,qrightsdef=$qrightsdef,deflib='$deflib',usedeflib='$usedeflib' ";
		$query .= "WHERE id='$userid'";
		mysql_query($query) or die("Query failed : " . mysql_error());
	} 
	header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/index.php");
	
?>

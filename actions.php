<?php
	//IMathAS:  Basic Actions
	//(c) 20006 David Lippman
ini_set("memory_limit", "104857600");
ini_set("upload_max_filesize", "10485760");
ini_set("post_max_size", "10485760");

	if (isset($_GET['greybox'])) {
		$isgb = true;
		$gb = '&greybox=true';
		$flexwidth = true;
		$nologo = true;
	} else {
		$isgb = false;
		$gb = '';
	}
	if((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO']=='https'))  {
		 $urlmode = 'https://';
	} else {
		 $urlmode = 'http://';
	}
	require_once("includes/password.php");

	if ($_GET['action']=="newuser") {
		require_once("config.php");
		$error = '';
		if (isset($studentTOS) && !isset($_POST['agree'])) {
			$error = "<p>You must agree to the Terms and Conditions to set up an account</p>";
		}
		$_POST['SID'] = trim($_POST['SID']);
		if ($loginformat!='' && !preg_match($loginformat,$_POST['SID'])) {
			$error .= "<p>$loginprompt is invalid.</p>";
		}
		//DB $query = "SELECT id FROM imas_users WHERE SID='{$_POST['SID']}'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB if (mysql_num_rows($result)>0) {
		$stm = $DBH->prepare('SELECT id FROM imas_users WHERE SID=:sid');
		$stm->execute(array(':sid'=>$_POST['SID']));
		if ($stm->rowCount()>0) {
			$error .= "<p>$loginprompt '{$_POST['SID']}' is used. </p>";
		}
		//
		if (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/',$_POST['email'])) {
			$error .= "<p>Invalid email address.</p>";
		}
		if ($_POST['pw1'] != $_POST['pw2']) {
			$error .= "<p>Passwords don't match. </p>";
		}
		if ($_POST['SID']=="" || $_POST['firstname']=="" || $_POST['lastname']=="" || $_POST['email']=="" || $_POST['pw1']=="") {
			$error .= "<p>Please include all information.</p>";
		}
		if ($error != '') {
			require("header.php");
			if ($gb == '') {
				echo "<div class=breadcrumb><a href=\"index.php\">Home</a> &gt; New User Signup</div>\n";
			}
			echo '<div id="headerforms" class="pagetitle"><h2>New User Signup</h2></div>';
			echo $error;
			echo '<p><a href="forms.php?action=newuser">Try Again</a></p>';
			require("footer.php");
			exit;
		}

		if (isset($CFG['GEN']['newpasswords'])) {
			$md5pw = password_hash($_POST['pw1'], PASSWORD_DEFAULT);
		} else {
			$md5pw = md5($_POST['pw1']);
		}
		if ($emailconfirmation) {$initialrights = 0;} else {$initialrights = 10;}
		if (isset($_POST['msgnot'])) {
			$msgnot = 1;
		} else {
			$msgnot = 0;
		}
		if (isset($CFG['GEN']['homelayout'])) {
			$homelayout = $CFG['GEN']['homelayout'];
		} else {
			$homelayout = '|0,1,2||0,1';
		}
		if (isset($_POST['courseselect']) && $_POST['courseselect']>0) {
			$_POST['courseid'] = $_POST['courseselect'];
			$_POST['ekey'] = '';
		}
		if (!isset($_GET['confirmed'])) {
			//DB $query = "SELECT SID FROM imas_users WHERE email='{$_POST['email']}'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB if (mysql_num_rows($result)>0) {
			$stm = $DBH->prepare('SELECT SID FROM imas_users WHERE email=:email');
			$stm->execute(array(':email'=>$_POST['email']));
			if ($stm->rowCount()>0) {
				$nologo = true;
				require("header.php");
				echo '<form method="post" action="actions.php?action=newuser&amp;confirmed=true'.$gb.'">';
				//DB echo '<input type="hidden" name="SID" value="'.stripslashes($_POST['SID']).'" />';
				//DB echo '<input type="hidden" name="firstname" value="'.stripslashes($_POST['firstname']).'" />';
				//DB echo '<input type="hidden" name="lastname" value="'.stripslashes($_POST['lastname']).'" />';
				//DB echo '<input type="hidden" name="email" value="'.stripslashes($_POST['email']).'" />';
				//DB echo '<input type="hidden" name="pw1" value="'.stripslashes($_POST['pw1']).'" />';
				//DB echo '<input type="hidden" name="pw2" value="'.stripslashes($_POST['pw2']).'" />';
				//DB echo '<input type="hidden" name="courseid" value="'.stripslashes($_POST['courseid']).'" />';
				//DB echo '<input type="hidden" name="ekey" value="'.stripslashes($_POST['ekey']).'" />';
				echo '<input type="hidden" name="SID" value="'.$_POST['SID'].'" />';
				echo '<input type="hidden" name="firstname" value="'.$_POST['firstname'].'" />';
				echo '<input type="hidden" name="lastname" value="'.$_POST['lastname'].'" />';
				echo '<input type="hidden" name="email" value="'.$_POST['email'].'" />';
				echo '<input type="hidden" name="pw1" value="'.$_POST['pw1'].'" />';
				echo '<input type="hidden" name="pw2" value="'.$_POST['pw2'].'" />';
				echo '<input type="hidden" name="courseid" value="'.$_POST['courseid'].'" />';
				echo '<input type="hidden" name="ekey" value="'.$_POST['ekey'].'" />';
				if (isset($_POST['agree'])) {
					echo '<input type="hidden" name="agree" value="1" />';
				}
				echo '<p> </p>';
				echo '<p>It appears an account already exists with the same email address you just entered. ';
				echo 'If you are creating an account because you forgot your username, you can ';
				echo '<a href="forms.php?action=lookupusername">look up your username</a> instead.</p>';
				echo '<input type="submit" value="Create new account anyways"/>';
				echo '</form>';
				require("footer.php");
				exit;
			}
		}
		//DB $query = "INSERT INTO imas_users (SID, password, rights, FirstName, LastName, email, msgnotify, homelayout) ";
		//DB $query .= "VALUES ('{$_POST['SID']}','$md5pw',$initialrights,'{$_POST['firstname']}','{$_POST['lastname']}','{$_POST['email']}',$msgnot,'$homelayout');";
		//DB mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $newuserid = mysql_insert_id();

		$query = "INSERT INTO imas_users (SID, password, rights, FirstName, LastName, email, msgnotify, homelayout) ";
		$query .= "VALUES (:SID, :password, :rights, :FirstName, :LastName, :email, :msgnotify, :homelayout)";

		$stm = $DBH->prepare($query);
		$stm->execute(array(':SID'=>$_POST['SID'], ':password'=>$md5pw, ':rights'=>$initialrights, ':FirstName'=>$_POST['firstname'],
			':LastName'=>$_POST['lastname'], ':email'=>$_POST['email'], ':msgnotify'=>$msgnot, ':homelayout'=>$homelayout));
		$newuserid = $DBH->lastInsertId();

		if ($emailconfirmation) {
			$id = $newuserid;
			$headers  = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
			$headers .= "From: $sendfrom\r\n";
			$message  = "<h4>This is an automated message from $installname.  Do not respond to this email</h4>\r\n";
			$message .= "<p>To complete your $installname registration, please click on the following link, or copy ";
			$message .= "and paste it into your webbrowser:</p>\r\n";
			$message .= "<a href=\"" . $urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/actions.php?action=confirm&id=$id\">";
			$message .= $urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/actions.php?action=confirm&id=$id</a>\r\n";
			mail($_POST['email'],'IMathAS Confirmation',$message,$headers);
			require("header.php");
			if ($gb == '') {
				echo "<div class=breadcrumb><a href=\"index.php\">Home</a> &gt; New User Signup</div>\n";
			}
			echo '<div id="headerforms" class="pagetitle"><h2>New User Signup</h2></div>';
			echo "Registration recorded.  You should shortly receive an email with confirmation instructions.";
			echo "<a href=\"$imasroot/index.php\">Back to main login page</a>\n";
			require("footer.php");
			exit;

		} else {
			$pagetitle = 'Account Created';
			require("header.php");
			echo "<div class=breadcrumb><a href=\"index.php\">Home</a> &gt; New User Signup</div>\n";
			echo '<div id="headerforms" class="pagetitle"><h2>New User Signup</h2></div>';
			echo "<p>Your account with username <b>{$_POST['SID']}</b> has been created.  If you forget your password, you can ask your ";
			echo "instructor to reset your password or use the forgotten password link on the login page.</p>\n";
			if (trim($_POST['courseid'])!='') {
				$error = '';

				if (!is_numeric($_POST['courseid'])) {
					$error = 'Invalid course id';
				} else {
					//DB $query = "SELECT enrollkey,allowunenroll,deflatepass FROM imas_courses WHERE id = '{$_POST['courseid']}' AND (available=0 OR available=2)";
					//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
					//DB $line = mysql_fetch_array($result, MYSQL_ASSOC);

					$query = "SELECT enrollkey,allowunenroll,deflatepass FROM imas_courses WHERE id=:cid AND (available=0 OR available=2)";
					$stm = $DBH->prepare($query);
					$stm->execute(array(':cid'=>$_POST['courseid']));
					$line = $stm->fetch(PDO::FETCH_ASSOC);

					if ($line==null) {
						$error = 'Course not found';
					} else if (($line['allowunenroll']&2)==2) {
						$error = 'Course is closed for self enrollment';
					} else if ($_POST['ekey']=="" && $line['enrollkey'] != '') {
						$error = 'No enrollment key provided';
					} else {
						$keylist = array_map('strtolower',array_map('trim',explode(';',$line['enrollkey'])));
						$_POST['ekey'] = trim($_POST['ekey']);
						if (!in_array(strtolower($_POST['ekey']), $keylist)) {
							$error = 'Incorrect enrollment key';
						} else {
							if (count($keylist)>1) {
								//DB $query = "INSERT INTO imas_students (userid,courseid,section,latepass) VALUES ('$newuserid','{$_POST['courseid']}','{$_POST['ekey']}','{$line['deflatepass']}');";
								$query = "INSERT INTO imas_students (userid,courseid,section,latepass) VALUES (:uid,:cid,:section,:latepass);";
								$array = [':uid'=>$newuserid, ':cid'=>$_POST['courseid'], ':section'=>$_POST['ekey'],':latepass'=>$line['deflatepass']];
							} else {
								//DB $query = "INSERT INTO imas_students (userid,courseid,latepass) VALUES ('$newuserid','{$_POST['courseid']}','{$line['deflatepass']}');";
								$query = "INSERT INTO imas_students (userid,courseid,latepass) VALUES (:uid,:cid,:latepass);";
								$array = [':uid'=>$newuserid, ':cid'=>$_POST['courseid'], ':latepass'=>$line['deflatepass']];
							}
							$stm = $DBH->prepare($query);
							$stm->execute($array);
							//DB mysql_query($query) or die("Query failed : " . mysql_error());
							echo '<p>You have been enrolled in course ID '.$_POST['courseid'].'</p>';
						}
					}
				}
				if ($error != '') {
					echo "<p>$error, so we were not able to enroll you in your course.  After you log in, you can ";
					echo 'try enrolling again.  You do <b>not</b> need to create another account.</p>';
				}
			}


			echo "You can now <a href=\"";
			echo $urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/index.php";
			echo "\">return to the login page</a> and login with your new username and password</p>";
			require("footer.php");
		}
		//header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/index.php");
		exit;
	} else if ($_GET['action']=="confirm") {
		require_once("config.php");
		//DB $query = "UPDATE imas_users SET rights=10 WHERE id='{$_GET['id']}' AND rights=0";
		//DB mysql_query($query) or die("Query failed : " . mysql_error());
		//DB if (mysql_affected_rows()>0) {

		$query = "UPDATE imas_users SET rights=10 WHERE id=:id AND rights=0";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':id'=>$_GET['id']));

		if ($stm->rowCount()>0) {
			require("header.php");
			echo "Confirmed.  Please <a href=\"index.php\">Log In</a>\n";
			require("footer.php");
			exit;
		} else {
			require("header.php");
			echo "Error.\n";
			require("footer.php");
		}
	} else if ($_GET['action']=="resetpw") {
		require_once("config.php");
		if (isset($_POST['username'])) {
			//DB $query = "SELECT id,email,rights FROM imas_users WHERE SID='{$_POST['username']}'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB if (mysql_num_rows($result)>0) {

			$query = "SELECT id,email,rights FROM imas_users WHERE SID=:sid";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':sid'=>$_POST['username']));
			if ($stm->rowCount()>0) {
				list($id,$email,$rights) = $stm->fetch(PDO::FETCH_NUM);
				//DB mysql_fetch_row($result);

				$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
				$code = '';
				for ($i=0;$i<10;$i++) {
					$code .= substr($chars,rand(0,61),1);
				}
				//DB $query = "UPDATE imas_users SET remoteaccess='$code' WHERE id=$id";
				//DB mysql_query($query) or die("Query failed : " . mysql_error());

				$query = "UPDATE imas_users SET remoteaccess=:code WHERE id=:id";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':code'=>$code, ':id'=>$id));

				$headers  = 'MIME-Version: 1.0' . "\r\n";
				$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
				$headers .= "From: $sendfrom\r\n";
				$message  = "<h4>This is an automated message from $installname.  Do not respond to this email</h4>\r\n";
				$message .= "<p>Your username was entered in the Reset Password page.  If you did not do this, you may ignore and delete this message. ";
				$message .= "If you did request a password reset, click the link below, or copy and paste it into your browser's address bar.  You ";
				$message .= "will then be prompted to choose a new password.</p>";
				$message .= "<a href=\"" .$urlmode. $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/actions.php?action=resetpw&id=$id&code=$code\">";
				$message .= $urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/actions.php?action=resetpw&id=$id&code=$code</a>\r\n";
				if (isset($CFG['GEN']['useSESmail'])) {
					SESmail($email, $sendfrom, 'Password Reset Request',$message);
				} else {
					mail($email,'Password Reset Request',$message,$headers);
				}

				require("header.php");
				echo '<p>An email with a password reset link has been sent your email address on record: <b>'.$email.'.</b><br/> ';
				echo 'If you do not see it in a few minutes, check your spam or junk box to see if the email ended up there.<br/>';
				echo 'It may help to add <b>'.$sendfrom.'</b> to your contacts list.</p>';
				echo '<p>If you still have trouble or the wrong email address is on file, contact your instructor - they can reset your password for you.</p>';
				require("footer.php");
				exit;
			} else {
				echo "Invalid Username.  <a href=\"index.php$gb\">Try again</a>";
				exit;
			}
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/index.php");
		} else if (isset($_POST['pw1'])) {
			if ($_POST['pw1']!=$_POST['pw2']) {
				echo 'Passwords do not match.  <a href="actions.php?action=resetpw&code='.$_POST['code'].'&id='.$_POST['id'].'">Try again</a>';
				exit;
			}
			//DB $query = "SELECT remoteaccess FROM imas_users WHERE id='{$_POST['id']}'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $realcode = mysql_result($result,0,0);
			//DB if (mysql_num_rows($result)>0 && $_POST['code']===$realcode && $realcode!='') {

			$query = "SELECT remoteaccess FROM imas_users WHERE id=:id";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':id'=>$_POST['id']));
			if ($stm->rowCount() > 0) {
				$row = $stm->fetch(PDO::FETCH_ASSOC);
				if ($row['remoteaccess']!='' && $row['remoteaccess']===$_POST['code']) {
					if (isset($CFG['GEN']['newpasswords'])) {
						$newpw = password_hash($_POST['pw1'], PASSWORD_DEFAULT);
					} else {
						$newpw = md5($_POST['pw1']);
					}
					//DB $query = "UPDATE imas_users SET password='$newpw',remoteaccess='' WHERE id='{$_POST['id']}' LIMIT 1";
					//DB mysql_query($query) or die("Query failed : " . mysql_error());

					$query = "UPDATE imas_users SET password=:newpw,remoteaccess='' WHERE id=:id LIMIT 1";
					$stm = $DBH->prepare($query);
					$stm->execute(array(':id'=>$_POST['id'], ':newpw'=>$newpw));
					echo "Password Reset.  ";
					echo "<a href=\"index.php\">Login with your new password</a>";
				} else {
					echo _('Invalid code');
				}
			} else {
				echo 'Invalid user';
			}
			exit;
		} else if (isset($_GET['code'])) {
			//DB $query = "SELECT remoteaccess FROM imas_users WHERE id='{$_GET['id']}'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $realcode = mysql_result($result,0,0);
			//DB if (mysql_num_rows($result)>0 && $_GET['code']===$realcode && $realcode!='') {

			$query = "SELECT remoteaccess FROM imas_users WHERE id=:id";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':id'=>$_GET['id']));
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			if ($row !== false && $row['remoteaccess']!='' && $row['remoteaccess']===$_GET['code']) {
				echo '<html><body><form method="post" action="actions.php?action=resetpw">';
				echo '<input type="hidden" name="code" value="'.$_GET['code'].'"/>';
				echo '<input type="hidden" name="id" value="'.$_GET['id'].'"/>';
				echo '<p>Please select a new password:</p>';
				echo '<p>Enter new password:  <input type="password" size="25" name="pw1"/><br/>';
				echo '<p>Verify new password:  <input type="password" size="25" name="pw2"/></p>';
				echo '<p><input type="submit" value="Submit"/></p>';
				echo '</form>';
				echo '</body></html>';
				exit;
			} else {
				echo '<html><body>Invalid reset code.  If you have requested a password reset multiple times, you need the link from ';
				echo 'the most recent email.</body></html>';
				exit;
			}

		}
	} else if ($_GET['action']=="lookupusername") {
		require_once("config.php");
		//DB $query = "SELECT SID,lastaccess FROM imas_users WHERE email='{$_POST['email']}' AND SID NOT LIKE 'lti-%'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB if (mysql_num_rows($result)>0) {

		$query = "SELECT SID,lastaccess FROM imas_users WHERE email=:email AND SID NOT LIKE 'lti-%'";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':email'=>$_POST['email']));
		if ($stm->rowCount() > 0) {
			echo $stm->rowCount();
			echo " usernames match this email address and were emailed.  <a href=\"index.php\">Return to login page</a>";
			$headers  = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
			$headers .= "From: $sendfrom\r\n";
			$message  = "<h4>This is an automated message from $installname.  Do not respond to this email</h4>\r\n";
			$message .= "<p>Your email was entered in the Username Lookup page on $installname.  If you did not do this, you may ignore and delete this message.  ";
			$message .= "All usernames using this email address are listed below</p><p>";
			//DB while ($row = mysql_fetch_row($result)) {
			while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
				if ($row['lastaccess']==0) {
					$lastlogin = "Never";
				} else {
					$lastlogin = date("n/j/y g:ia",$row['lastaccess']);
				}
				$message .= "Username: <b>{$row['SID']}</b>.  Last logged in: $lastlogin<br/>";
			}
			$message .= "</p><p>If you forgot your password, use the Lost Password link at the login page.</p>";
			if (isset($CFG['GEN']['useSESmail'])) {
				SESmail($_POST['email'], $sendfrom, "$installname Username Request",$message);
			} else {
				mail($_POST['email'],"$installname Username Request",$message,$headers);
			}

			exit;
		} else {
			//DB $query = "SELECT SID,lastaccess FROM imas_users WHERE email='{$_POST['email']}'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB if (mysql_num_rows($result)>0) {

			$query = "SELECT SID,lastaccess FROM imas_users WHERE email=:email AND SID LIKE 'lti-%'";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':email'=>$_POST['email']));
			if ($stm->rowCount() > 0) {
				echo "Your account can only be accessed through your school's learning management system. <a href=\"index.php\">Return to login page</a>";
			} else {
				echo "No usernames match this email address <a href=\"index.php\">Return to login page</a>";
			}
			exit;
		}
	}

	require("validate.php");
	if ($_GET['action']=="logout") {
		$sessionid = session_id();
		//DB $query = "DELETE FROM imas_sessions WHERE sessionid='$sessionid'";
		//DB mysql_query($query) or die("Query failed : " . mysql_error());
		$stm = $DBH->prepare("DELETE FROM imas_sessions WHERE sessionid=?");
		$stm->execute(array($sessionid));
		$_SESSION = array();
		if (isset($_COOKIE[session_name()])) {
			setcookie(session_name(), '', time()-42000, '/');
		}
		session_destroy();
	} else if ($_GET['action']=="chgpwd") {
		//DB $query = "SELECT password FROM imas_users WHERE id = '$userid'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $line = mysql_fetch_array($result, MYSQL_ASSOC);
		$stm = $DBH->prepare("SELECT password FROM imas_users WHERE id=:uid");
		$stm->execute(array(':uid'=>$userid));
		$line = $stm->fetch(PDO::FETCH_ASSOC);
		if ((md5($_POST['oldpw'])==$line['password'] || (isset($CFG['GEN']['newpasswords']) && password_verify($_POST['oldpw'],$line['password']))) && ($_POST['newpw1'] == $_POST['newpw2']) && $myrights>5) {
			if (isset($CFG['GEN']['newpasswords'])) {
				$newpw = password_hash($_POST['newpw1'], PASSWORD_DEFAULT);
			} else {
				$newpw =md5($_POST['newpw1']);
			}
			//DB $query = "UPDATE imas_users SET password='$md5pw' WHERE id='$userid'";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("UPDATE imas_users SET password=:newpw WHERE id=:uid LIMIT 1");
			$stm->execute(array(':uid'=>$userid, ':newpw'=>$newpw));
		} else {
			echo "<html><body>Password change failed.  <A HREF=\"forms.php?action=chgpwd$gb\">Try Again</a>\n";
			echo "</body></html>\n";
			exit;
		}

	} else if ($_GET['action']=="enroll") {
		if ($myrights < 6) {
			echo "<html><body>\nError: Guests can't enroll in courses</body></html";
			exit;
		}
		if (isset($_POST['courseselect']) && $_POST['courseselect']>0) {
			$_POST['cid'] = $_POST['courseselect'];
			$_POST['ekey'] = '';
		}
		$pagetopper = '';
		if ($gb == '') {
			$pagetopper .= "<div class=breadcrumb><a href=\"index.php\">Home</a> &gt; Enroll in a Course</div>\n";
		}
		$pagetopper .= '<div id="headerforms" class="pagetitle"><h2>Enroll in a Course</h2></div>';
		if ($_POST['cid']=="" || !is_numeric($_POST['cid'])) {
			require("header.php");
			echo $pagetopper;
			echo "Please include Course ID.  <a href=\"forms.php?action=enroll$gb\">Try Again</a>\n";
			require("footer.php");
			exit;
		}
		//DB $query = "SELECT enrollkey,allowunenroll,deflatepass FROM imas_courses WHERE id = '{$_POST['cid']}' AND (available=0 OR available=2)";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $line = mysql_fetch_array($result, MYSQL_ASSOC);

		$stm = $DBH->prepare("SELECT enrollkey,allowunenroll,deflatepass FROM imas_courses WHERE id = :cid AND (available=0 OR available=2)");
		$stm->execute(array(':cid'=>$_POST['cid']));
		$line = $stm->fetch(PDO::FETCH_ASSOC);
		if ($line === false) {
			require("header.php");
			echo $pagetopper;
			echo "Course not found.  <a href=\"forms.php?action=enroll$gb\">Try Again</a>\n";
			require("footer.php");
			exit;
		} else if (($line['allowunenroll']&2)==2) {
			require("header.php");
			echo $pagetopper;
			echo "Course is closed for self enrollment.  Contact your instructor for access.  <a href=\"index.php\">Return to home page.</a>\n";
			require("footer.php");
			exit;
		} else if ($_POST['ekey']=="" && $line['enrollkey'] != '') {
			require("header.php");
			echo $pagetopper;
			echo "Please include Enrollment Key.  <a href=\"forms.php?action=enroll$gb\">Try Again</a>\n";
			require("footer.php");
			exit;
		}  else {
			//DB $query = "SELECT * FROM imas_teachers WHERE userid='$userid' AND courseid='{$_POST['cid']}'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB if (mysql_num_rows($result)>0) {
			$stm = $DBH->prepare("SELECT id FROM imas_teachers WHERE userid=:uid AND courseid=:cid");
			$stm->execute(array(':uid'=>$userid, ':cid'=>$_POST['cid']));
			if ($stm->rowCount() > 0) {
				require("header.php");
				echo $pagetopper;
				echo "You are a teacher for this course, and can't enroll as a student.  Use Student View to see ";
				echo "the class from a student's perspective, or create a dummy student account.  ";
				echo "Click on the course name on the <a href=\"index.php\">main page</a> to access the course\n";
				require("footer.php");
				exit;
			}
			//DB $query = "SELECT * FROM imas_tutors WHERE userid='$userid' AND courseid='{$_POST['cid']}'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB if (mysql_num_rows($result)>0) {
			$stm = $DBH->prepare("SELECT id FROM imas_tutors WHERE userid=:uid AND courseid=:cid");
			$stm->execute(array(':uid'=>$userid, ':cid'=>$_POST['cid']));
			if ($stm->rowCount() > 0) {
				require("header.php");
				echo $pagetopper;
				echo "You are a tutor for this course, and can't enroll as a student. ";
				echo "Click on the course name on the <a href=\"index.php\">main page</a> to access the course\n";
				require("footer.php");
				exit;
			}
			//DB $query = "SELECT * FROM imas_students WHERE userid='$userid' AND courseid='{$_POST['cid']}'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB if (mysql_num_rows($result)>0) {
			$stm = $DBH->prepare("SELECT id FROM imas_students WHERE userid=:uid AND courseid=:cid");
			$stm->execute(array(':uid'=>$userid, ':cid'=>$_POST['cid']));
			if ($stm->rowCount() > 0) {
				require("header.php");
				echo $pagetopper;
				echo "You are already enrolled in the course.  Click on the course name on the <a href=\"index.php\">main page</a> to access the course\n";
				require("footer.php");
				exit;
			} else {
				$keylist = array_map('strtolower',array_map('trim',explode(';',$line['enrollkey'])));
				if (!in_array(strtolower(trim($_POST['ekey'])), $keylist)) {
					require("header.php");
					echo $pagetopper;
					echo "Incorrect Enrollment Key.  <a href=\"forms.php?action=enroll$gb\">Try Again</a>\n";
					require("footer.php");
					exit;
				} else {
					if (count($keylist)>1) {
						//DB $query = "INSERT INTO imas_students (userid,courseid,section,latepass) VALUES ('$userid','{$_POST['cid']}','{$_POST['ekey']}','{$line['deflatepass']}');";
						$query = "INSERT INTO imas_students (userid,courseid,section,latepass) VALUES (:uid,:cid,:section,:latepass);";
						$array = [':uid'=>$userid, ':cid'=>$_POST['cid'], ':section'=>$_POST['ekey'],':latepass'=>$line['deflatepass']];
					} else {
						//DB $query = "INSERT INTO imas_students (userid,courseid,latepass) VALUES ('$userid','{$_POST['cid']}','{$line['deflatepass']}');";
						$query = "INSERT INTO imas_students (userid,courseid,latepass) VALUES (:uid,:cid,:latepass);";
						$array = [':uid'=>$userid, ':cid'=>$_POST['cid'], ':latepass'=>$line['deflatepass']];
					}
					$stm = $DBH->prepare($query);
					$stm->execute($array);
					//DB mysql_query($query) or die("Query failed : " . mysql_error());
					require("header.php");
					echo $pagetopper;
					echo '<p>You have been enrolled in course ID '.$_POST['cid'].'</p>';
					echo "<p>Return to the <a href=\"index.php\">main page</a> and click on the course name to access the course</p>";
					require("footer.php");
					exit;
				}


				//$query = "INSERT INTO imas_students (userid,courseid) VALUES ('$userid','{$_POST['cid']}');";
				//mysql_query($query) or die("Query failed : " . mysql_error());
			}
		}
	} else if ($_GET['action']=="unenroll") {
		if ($myrights < 6) {
			echo "<html><body>\nError: Guests can't unenroll from courses</body></html";
			exit;
		}
		if (!isset($_GET['cid'])) {
			require("header.php");
			echo "Course ID not specified.  <a href=\"index.php\">Try Again</a>\n";
			require("footer.php");
			exit;
		}
		$cid = $_GET['cid'];
		//DB $query = "SELECT allowunenroll FROM imas_courses WHERE id='$cid'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB if (mysql_result($result,0,0)==1) {
		$stm = $DBH->prepare("SELECT allowunenroll FROM imas_courses WHERE id=:cid");
		$stm->execute(array(':cid'=>$cid));
		if ($stm->fetchColumn()==1) {
			//DB $query = "DELETE FROM imas_students WHERE userid='$userid' AND courseid='$cid'";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("DELETE FROM imas_students WHERE userid=:uid AND courseid=:cid");
			$stm->execute(array(':uid'=>$userid,':cid'=>$cid));
			//DB $query = "SELECT id FROM imas_assessments WHERE courseid='$cid'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB while ($row = mysql_fetch_row($result)) {
			/*
			$stm = $DBH->prepare("SELECT id FROM imas_assessments WHERE courseid=:cid");
			$stm->execute(array(':cid'=>$cid));
			while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
				//DB $query = "DELETE FROM imas_assessment_sessions WHERE assessmentid='{$row[0]}' AND userid='$userid'";
				//DB mysql_query($query) or die("Query failed : " . mysql_error());
				$stm = $DBH->prepare("DELETE FROM imas_assessment_sessions WHERE assessmentid=:aid AND userid=:uid");
				$stm->execute(array(':uid'=>$userid,':aid'=>$row['id']));
				//DB $query = "DELETE FROM imas_exceptions WHERE assessmentid='{$row[0]}' AND userid='$userid'";
				//DB mysql_query($query) or die("Query failed : " . mysql_error());
				$stm = $DBH->prepare("DELETE FROM imas_exceptions WHERE assessmentid=:aid AND itemtype='A' AND userid=:uid");
				$stm->execute(array(':uid'=>$userid,':aid'=>$row['id']));
			}
			*/
			$stm = $DBH->prepare("DELETE FROM imas_assessment_sessions WHERE assessmentid IN (SELECT id FROM imas_assessments WHERE courseid=:cid) AND userid=:uid");
			$stm->execute(array(':uid'=>$userid,':cid'=>$cid));

			$stm = $DBH->prepare("DELETE FROM imas_exceptions WHERE itemtype='A' AND assessmentid IN (SELECT id FROM imas_assessments WHERE courseid=:cid) AND userid=:uid");
			$stm->execute(array(':uid'=>$userid,':cid'=>$cid));

			//DB $query = "DELETE FROM imas_drillassess_sessions WHERE drillassessid IN (SELECT id FROM imas_drillassess WHERE courseid='$cid') AND userid='$userid'";
			//DB mysql_query($query) or die("Query failed : $query" . mysql_error());
			$stm = $DBH->prepare("DELETE FROM imas_drillassess_sessions WHERE drillassessid IN (SELECT id FROM imas_drillassess WHERE courseid=:cid) AND userid=:uid");
			$stm->execute(array(':uid'=>$userid,':cid'=>$cid));

			//DB $query = "SELECT id FROM imas_gbitems WHERE courseid='$cid'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB while ($row = mysql_fetch_row($result)) {
				//DB $query = "DELETE FROM imas_grades WHERE gradetype='offline' AND gradetypeid='{$row[0]}' AND userid='$userid'";
				//DB mysql_query($query) or die("Query failed : " . mysql_error());
			//}
			$stm = $DBH->prepare("DELETE FROM imas_grades WHERE gradetype='offline' AND gradetypeid= IN (SELECT id FROM imas_gbitems WHERE courseid=:cid) AND userid=:uid");
			$stm->execute(array(':uid'=>$userid,':cid'=>$cid));

			//DB $query = "SELECT id FROM imas_forums WHERE courseid='$cid'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB while ($row = mysql_fetch_row($result)) {
				//DB $q2 = "SELECT threadid FROM imas_forum_posts WHERE forumid='{$row[0]}'";
				//DB $r2 = mysql_query($q2) or die("Query failed : " . mysql_error());
				//DB while ($rw2 = mysql_fetch_row($r2)) {
					//DB $query = "DELETE FROM imas_forum_views WHERE threadid='{$rw2[0]}' AND userid='$userid'";
					//DB mysql_query($query) or die("Query failed : " . mysql_error());
			//	}
			//}
			$query = "DELETE FROM imas_forum_views WHERE userid=:uid AND threadid IN ";
			$query .= "(SELECT ifp.threadid FROM imas_forum_posts AS ifp JOIN imas_forums ON ifp.forumid=imas_forums.id WHERE imas_forums.courseid=:cid)";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':uid'=>$userid,':cid'=>$cid));

			$query = "DELETE FROM imas_exceptions WHERE (itemtype='F' OR itemtype='P' OR itemtype='R') AND userid=:uid AND assessmentid IN ";
			$query .= "(SELECT id FROM imas_forums WHERE courseid=:cid)";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':uid'=>$userid,':cid'=>$cid));

		}
	} else if ($_GET['action']=="chguserinfo") {
		$pagetopper = '';
		if ($gb == '') {
			$pagetopper .= "<div class=breadcrumb><a href=\"index.php\">Home</a> &gt; Modify User Profile</div>\n";
		}
		$pagetopper .= '<div id="headerforms" class="pagetitle"><h2>Modify User Profile</h2></div>';
		require('includes/userpics.php');
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
		$homelayout[0] = array();
		$homelayout[1] = array(0,1,2);
		$homelayout[2] = array();
		if (isset($_POST['homelayout10'])) {
			$homelayout[2][] = 10;
		}
		if (isset($_POST['homelayout11'])) {
			$homelayout[2][] = 11;
		}
		$homelayout[3] = array();
		if (isset($_POST['homelayout3-0'])) {
			$homelayout[3][] = 0;
		}
		if (isset($_POST['homelayout3-1'])) {
			$homelayout[3][] = 1;
		}
		foreach ($homelayout as $k=>$v) {
			$homelayout[$k] = implode(',',$v);
		}
		$perpage = intval($_POST['perpage']);
		if (isset($CFG['GEN']['fixedhomelayout']) && $CFG['GEN']['homelayout']) {
			$deflayout = explode('|',$CFG['GEN']['homelayout']);
			foreach ($CFG['GEN']['fixedhomelayout'] as $k) {
				$homelayout[$k] = $deflayout[$k];
			}
		}

		$layoutstr = implode('|',$homelayout);
		if (is_uploaded_file($_FILES['stupic']['tmp_name'])) {
			processImage($_FILES['stupic'],$userid,200,200);
			processImage($_FILES['stupic'],'sm'.$userid,40,40);
			$chguserimg = ",hasuserimg=1";
		} else if (isset($_POST['removepic'])) {
			deletecoursefile('userimg_'.$userid.'.jpg');
			deletecoursefile('userimg_sm'.$userid.'.jpg');
			$chguserimg = ",hasuserimg=0";
		} else {
			$chguserimg = '';
		}
		$_POST['theme'] = str_replace(array('/','..'), '', $_POST['theme']);

		//DEB $query = "UPDATE imas_users SET FirstName='{$_POST['firstname']}',LastName='{$_POST['lastname']}',email='{$_POST['email']}',msgnotify=$msgnot,qrightsdef=$qrightsdef,deflib='$deflib',usedeflib='$usedeflib',homelayout='$layoutstr',theme='{$_POST['theme']}',listperpage='$perpage'$chguserimg ";
		//DB $query .= "WHERE id='$userid'";
		//DB mysql_query($query) or die("Query failed : " . mysql_error());

		$query = "UPDATE imas_users SET FirstName=:FirstName, LastName=:LastName, email=:email, msgnotify=:msgnotify, qrightsdef=:qrightsdef, deflib=:deflib,";
		$query .= "usedeflib=:usedeflib, homelayout=:homelayout, theme=:theme, listperpage=:listperpage $chguserimg WHERE id=:uid";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':FirstName'=>$_POST['firstname'],
			':LastName'=>$_POST['lastname'], ':email'=>$_POST['email'], ':msgnotify'=>$msgnot, ':homelayout'=>$layoutstr, ':qrightsdef'=>$qrightsdef,
			':deflib'=>$deflib, ':usedeflib'=>$usedeflib, ':theme'=>$_POST['theme'], ':listperpage'=>$perpage, ':uid'=>$userid));

		if (isset($_POST['dochgpw'])) {
			//DB $query = "SELECT password FROM imas_users WHERE id = '$userid'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $line = mysql_fetch_array($result, MYSQL_ASSOC);
			$stm = $DBH->prepare("SELECT password FROM imas_users WHERE id = :uid");
			$stm->execute(array(':uid'=>$userid));
			$line = $stm->fetch(PDO::FETCH_ASSOC);
			if ((md5($_POST['oldpw'])==$line['password'] || (isset($CFG['GEN']['newpasswords']) && password_verify($_POST['oldpw'],$line['password']))) && ($_POST['newpw1'] == $_POST['newpw2']) && $myrights>5) {
				if (isset($CFG['GEN']['newpasswords'])) {
					$newpw = password_hash($_POST['newpw1'], PASSWORD_DEFAULT);
				} else {
					$newpw =md5($_POST['newpw1']);
				}
				//DB $query = "UPDATE imas_users SET password='$md5pw' WHERE id='$userid'";
				//DB mysql_query($query) or die("Query failed : " . mysql_error());
				$stm = $DBH->prepare("UPDATE imas_users SET password = :newpw WHERE id = :uid");
				$stm->execute(array(':uid'=>$userid, ':newpw'=>$newpw));
			} else {
				require("header.php");
				echo $pagetopper;
				echo "Password change failed.  <a href=\"forms.php?action=chguserinfo$gb\">Try Again</a>\n";
				require("footer.php");
				exit;
			}
		}

		if (isset($_POST['settimezone'])) {
			if (date_default_timezone_set($_POST['settimezone'])) {
				$tzname = $_POST['settimezone'];
				//DB $query = "UPDATE imas_sessions SET tzname='$tzname' WHERE sessionid='$sessionid'";
				//DB mysql_query($query) or die("Query failed : " . mysql_error());
				$stm = $DBH->prepare("UPDATE imas_sessions SET tzname=:tzname WHERE sessionid=:sessionid");
				$stm->execute(array(':tzname'=>$tzname, ':sessionid'=>$sessionid));
			}
		}
	} else if ($_GET['action']=="forumwidgetsettings") {
		$checked = $_POST['checked'];
		$all = explode(',',$_POST['allcourses']);
		foreach ($all as $k=>$v) {
			$all[$k] = intval($v);
		}
		$tohide = array_diff($all,$checked);
		$hidelist = implode(',', $tohide);
		//DB $query = "UPDATE imas_users SET hideonpostswidget='$hidelist' WHERE id='$userid'";
		//DB mysql_query($query) or die("Query failed : " . mysql_error());
		$stm = $DBH->prepare("UPDATE imas_users SET hideonpostswidget=:hidelist WHERE id= :uid");
		$stm->execute(array(':uid'=>$userid, ':hidelist'=>$hidelist));
	} else if ($_GET['action']=="googlegadget") {
		if (isset($_GET['clear'])) {
			//DB $query = "UPDATE imas_users SET remoteaccess='' WHERE id='$userid'";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("UPDATE imas_users SET remoteaccess='' WHERE id = :uid");
			$stm->execute(array(':uid'=>$userid));
		}
	}
	if ($isgb) {
		echo '<html><body>Changes Recorded.  <input type="button" onclick="top.GB_hide()" value="Done" /></body></html>';
	} else {
		header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/index.php");
	}




?>

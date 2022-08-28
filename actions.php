<?php
	//IMathAS:  Basic Actions
	//(c) 2006 David Lippman




//Look to see if a hook file is defined, and include if it is
if (isset($CFG['hooks']['actions'])) {
	require($CFG['hooks']['actions']);
}

require_once("includes/sanitize.php");

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

	if (isset($_GET['action']) && $_GET['action']=="newuser") {
		$init_session_start = true;
		require_once("init_without_validate.php");
		require_once("includes/newusercommon.php");
        if (!isset($_SESSION['challenge']) || $_POST['challenge'] !== $_SESSION['challenge'] ||
            !empty($_POST['hval']) ||
            !isset($_SESSION['newuserstart']) || (time() - $_SESSION['newuserstart']) < 5
        ) {
            echo "Invalid submission";
            exit;
        }
		$_SESSION['challenge'] = '';
        unset($_SESSION['newuserstart']);

		$error = '';
		if (isset($studentTOS) && !isset($_POST['agree'])) {
			$error = "<p>"._("You must agree to the Terms and Conditions to set up an account")."</p>";
		}

		// Sanitize form data

		$_POST['SID'] = Sanitize::stripHtmlTags(trim($_POST['SID']));
		$_POST['email'] = Sanitize::emailAddress(trim($_POST['email']));
		$_POST['firstname'] = Sanitize::stripHtmlTags(trim($_POST['firstname']));
		$_POST['lastname'] = Sanitize::stripHtmlTags(trim($_POST['lastname']));
		$_POST['courseid'] = Sanitize::courseId(trim($_POST['courseid']));

        $error .= checkNewUserValidation();
        
        if (isset($CFG['GEN']['COPPA']) && empty($_POST['over13'])) {
            if (!is_numeric($_POST['courseid'])) {
                $error = _('Invalid course id');
            } else {
                $query = "SELECT enrollkey,allowunenroll FROM imas_courses WHERE id=:cid AND (available=0 OR available=2)";
                $stm = $DBH->prepare($query);
                $stm->execute(array(':cid'=>$_POST['courseid']));
                $line = $stm->fetch(PDO::FETCH_ASSOC);

                if ($line==null) {
                    $error = _('Course not found');
                } else if (($line['allowunenroll']&2)==2) {
                    $error = _('Course is closed for self enrollment');
                } else if ($_POST['ekey']=="" && $line['enrollkey'] != '') {
                    $error = _('No enrollment key provided');
                } else {
                    $keylist = array_map('trim',explode(';',$line['enrollkey']));
                    if (($p = array_search(strtolower(trim($_POST['ekey'])), array_map('strtolower', $keylist))) === false) {
                        $error = _('Incorrect enrollment key');
                    }
                }
            }
        }

		if ($error != '') {
			require("header.php");
			if ($gb == '') {
				echo "<div class=breadcrumb><a href=\"index.php\">Home</a> &gt; ",_("New User Signup"),"</div>\n";
			}
			echo '<div id="headerforms" class="pagetitle"><h1>',_('New User Signup'),'</h1></div>';
			echo $error;
			//call hook, if defined
			if (function_exists('onNewUserError')) {
				onNewUserError();
			} else {
				echo '<p><a href="forms.php?action=newuser">',_('Try Again'),'</a></p>';
			}
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
			$_POST['courseid'] = Sanitize::courseId(trim($_POST['courseselect']));
			$_POST['ekey'] = '';
		}
		if (!isset($_GET['confirmed'])) {
			//look for existing account. ignore any LTI accounts
			$stm = $DBH->prepare("SELECT SID FROM imas_users WHERE email=:email AND SID NOT LIKE 'lti-%'");
			$stm->execute(array(':email'=>$_POST['email']));
			if ($stm->rowCount()>0) {
				$nologo = true;
                $_SESSION['newuserstart'] = time() - 10;
				require("header.php");
				echo '<form method="post" action="actions.php?action=newuser&amp;confirmed=true'.$gb.'">';
				echo '<input type="hidden" name="SID" value="'.Sanitize::encodeStringForDisplay($_POST['SID']).'" />';
				echo '<input type="hidden" name="firstname" value="'.Sanitize::encodeStringForDisplay($_POST['firstname']).'" />';
				echo '<input type="hidden" name="lastname" value="'.Sanitize::encodeStringForDisplay($_POST['lastname']).'" />';
				echo '<input type="hidden" name="email" value="'.Sanitize::encodeStringForDisplay($_POST['email']).'" />';
				echo '<input type="hidden" name="pw1" value="'.Sanitize::encodeStringForDisplay($_POST['pw1']).'" />';
				echo '<input type="hidden" name="pw2" value="'.Sanitize::encodeStringForDisplay($_POST['pw2']).'" />';
				echo '<input type="hidden" name="courseid" value="'.Sanitize::encodeStringForDisplay($_POST['courseid']).'" />';
				echo '<input type="hidden" name="ekey" value="'.Sanitize::encodeStringForDisplay($_POST['ekey']).'" />';
				$_SESSION['challenge'] = uniqid();
				echo '<input type=hidden name=challenge value="'.Sanitize::encodeStringForDisplay($_SESSION['challenge']).'"/>';
				if (isset($_POST['agree'])) {
					echo '<input type="hidden" name="agree" value="1" />';
				}
				echo '<p> </p>';
				echo '<p>',_('It appears an account already exists with the same email address you just entered'),'. ';
				echo sprintf(_('If you are creating an account because you forgot your username, you can %s look up your username %s instead.'),'<a href="forms.php?action=lookupusername">','</a>'),'</p>';
				echo '<input type="submit" value="',_('Create new account anyways'),'"/>';
				echo '</form>';
				require("footer.php");
				exit;
			}
		}

        $jsondata = [];
        if (isset($CFG['GEN']['COPPA']) && empty($_POST['over13'])) {
            $jsondata['under13'] = 1;
        }

		$query = "INSERT INTO imas_users (SID, password, rights, FirstName, LastName, email, msgnotify, homelayout, jsondata) ";
		$query .= "VALUES (:SID, :password, :rights, :FirstName, :LastName, :email, :msgnotify, :homelayout, :jsondata)";

		$stm = $DBH->prepare($query);
		$stm->execute(array(
			':SID'=>$_POST['SID'],
			':password'=>$md5pw,
			':rights'=>$initialrights,
			':FirstName'=>Sanitize::stripHtmlTags($_POST['firstname']),
			':LastName'=>Sanitize::stripHtmlTags($_POST['lastname']),
			':email'=>Sanitize::emailAddress($_POST['email']),
			':msgnotify'=>$msgnot,
            ':homelayout'=>$homelayout,
            ':jsondata'=>json_encode($jsondata)
        ));
		$newuserid = $DBH->lastInsertId();

		if ($emailconfirmation) {
			$id = $newuserid;
			$message  = "<h3>".sprintf(_("This is an automated message from %s.  Do not respond to this email"),$installname)."</h3>\r\n";
			$message .= "<p>".sprintf(_("To complete your %s registration, please click on the following link, or copy and paste it into your webbrowser:"),$installname)."</p>\r\n";
			$message .= "<a href=\"" . $GLOBALS['basesiteurl'] . "/actions.php?action=confirm&id=$id\">";
			$message .= $GLOBALS['basesiteurl'] . "/actions.php?action=confirm&id=$id</a>\r\n";
			require_once("./includes/email.php");
			send_email($_POST['email'], $sendfrom, $installname._(' Confirmation'), $message, array(), array(), 10);

			require("header.php");
			if ($gb == '') {
				echo "<div class=breadcrumb><a href=\"index.php\">Home</a> &gt; ",_("New User Signup"),"</div>\n";
			}
			echo '<div id="headerforms" class="pagetitle"><h1>',_('New User Signup'),'</h1></div>';
			echo _("Registration recorded.  You should shortly receive an email with confirmation instructions.");
			echo "<a href=\"$imasroot/index.php\">",_("Back to main login page"),"</a>\n";
			require("footer.php");
			exit;

		} else {
			$pagetitle = _('Account Created');
			require("header.php");
			echo "<div class=breadcrumb><a href=\"index.php\">Home</a> &gt; ",_("New User Signup"),"</div>\n";
			echo '<div id="headerforms" class="pagetitle"><h1>',_('New User Signup'),'</h1></div>';
			echo "<p>",sprintf(_("Your account with username %s has been created.  If you forget your password, you can ask your instructor to reset your password or use the forgotten password link on the login page."),"<b>" . Sanitize::encodeStringForDisplay($_POST['SID']) . "</b>"),"</p>\n";
			if (trim($_POST['courseid'])!='') {
				$error = '';

				if (!is_numeric($_POST['courseid'])) {
					$error = _('Invalid course id');
				} else {

					$query = "SELECT enrollkey,allowunenroll,deflatepass,msgset FROM imas_courses WHERE id=:cid AND (available=0 OR available=2)";
					$stm = $DBH->prepare($query);
					$stm->execute(array(':cid'=>$_POST['courseid']));
					$line = $stm->fetch(PDO::FETCH_ASSOC);

					if ($line==null) {
						$error = _('Course not found');
					} else if (($line['allowunenroll']&2)==2) {
						$error = _('Course is closed for self enrollment');
					} else if ($_POST['ekey']=="" && $line['enrollkey'] != '') {
						$error = _('No enrollment key provided');
					} else {
                        $keylist = array_map('trim',explode(';',$line['enrollkey']));
                        if (($p = array_search(strtolower(trim($_POST['ekey'])), array_map('strtolower', $keylist))) === false) {
							$error = _('Incorrect enrollment key');
						} else {
                            $_POST['ekey'] = $keylist[$p];
                            require('./includes/setSectionGroups.php');
							if (count($keylist)>1) {
								$query = "INSERT INTO imas_students (userid,courseid,section,latepass) VALUES (:uid,:cid,:section,:latepass);";
								$array = array(
									':uid'=>$newuserid,
									':cid'=>$_POST['courseid'],
									':section'=>$_POST['ekey'],
									':latepass'=>$line['deflatepass']
                                );
                                setSectionGroups($newuserid, $_POST['courseid'], $_POST['ekey']);
							} else {
								$query = "INSERT INTO imas_students (userid,courseid,latepass) VALUES (:uid,:cid,:latepass);";
                                $array = array(':uid'=>$newuserid, ':cid'=>$_POST['courseid'], ':latepass'=>$line['deflatepass']);
                                setSectionGroups($newuserid, $_POST['courseid'], '');
							}
							$stm = $DBH->prepare($query);
							$stm->execute($array);
							echo '<p>',sprintf(_("You have been enrolled in course ID %s"),Sanitize::encodeStringForDisplay($_POST['courseid'])),'</p>';

                            sendMsgOnEnroll($line['msgset'], $_POST['courseid'], $newuserid);

						}
					}
				}
				if ($error != '') {
					echo "<p>$error, ",_("so we were not able to enroll you in your course.  After you log in, you can try enrolling again.  You do <b>not</b> need to create another account."),"</p>";
				}
			}


			echo "<p>",sprintf(_("You can now %s return to the login page %s and login with your new username and password"),"<a href=\"" . $GLOBALS['basesiteurl'] . "/index.php\">","</a>"),"</p>";
			require("footer.php");
		}
		//header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/index.php");
		exit;
	} else if (isset($_GET['action']) && $_GET['action']=="confirm") {
		require_once("init_without_validate.php");

		$query = "UPDATE imas_users SET rights=10 WHERE id=:id AND rights=0";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':id'=>Sanitize::onlyInt($_GET['id'])));

		if ($stm->rowCount()>0) {
			require("header.php");
			echo sprintf(_("Confirmed.  Please %s Log In %s"),"<a href=\"index.php\">","</a>\n");
			require("footer.php");
			exit;
		} else {
			require("header.php");
			echo _("Error").".\n";
			require("footer.php");
		}
	} else if (isset($_GET['action']) && $_GET['action']=="resetpw") {
        $init_session_start = true;
		require_once("init_without_validate.php");
		if (isset($_POST['username'])) {
            if (!isset($_SESSION['challenge']) || $_POST['challenge'] !== $_SESSION['challenge'] ||
                !empty($_POST['terms']) ||
                !isset($_SESSION['resetpwstart']) || (time() - $_SESSION['resetpwstart']) < 3
            ) {
                echo "Invalid submission";
                exit;
            }
            $_SESSION['challenge'] = '';
            unset($_SESSION['resetpwstart']);

			$query = "SELECT id,email,rights,lastemail FROM imas_users WHERE SID=:sid";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':sid'=>$_POST['username']));
			if ($stm->rowCount()>0) {
				list($id,$email,$rights,$lastemail) = $stm->fetch(PDO::FETCH_NUM);
				if (time() - $lastemail < 60) {
					echo 'Please wait and try again';
					exit;
				}
				if (substr($email,0,7)==='BOUNCED') {
					require("header.php");
					echo '<p>';
					echo _('The email address on record for this username is invalid.').' ';
					if ($myrights < 20) {
						echo _('Contact your teacher for help resetting your password.');
					} else {
						echo _('Contact the system administrator for help resetting your password:').' ';
						$addr = isset($accountapproval) ? $accountapproval : $sendfrom;
						echo $addr.'.';
					}
					echo '</p>';
					require("footer.php");
					exit;
				}

				$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
				$code = '';
				for ($i=0;$i<10;$i++) {
					$code .= substr($chars,rand(0,61),1);
				}

				$query = "UPDATE imas_users SET remoteaccess=:code,lastemail=:now WHERE id=:id";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':code'=>$code, ':now'=>time(), ':id'=>$id));

				$message  = "<h3>".sprintf(_('This is an automated message from %s. Do not respond to this email'),$installname)."</h3>\r\n";
				$message .= "<p>"._('Your username was entered in the Reset Password page.  If you did not do this, you may ignore and delete this message. ');
				$message .= _("If you did request a password reset, click the link below, or copy and paste it into your browser's address bar.  You will then be prompted to choose a new password.")."</p>";
				$message .= "<a href=\"" . $GLOBALS['basesiteurl'] . "/forms.php?action=resetpw&id=$id&code=$code\">";
				$message .= $GLOBALS['basesiteurl'] . "/forms.php?action=resetpw&id=$id&code=$code</a>\r\n";

				require_once("./includes/email.php");
				send_email($email, $sendfrom, $installname._(' Password Reset Request'), $message, array(), array(), 10);

				require("header.php");
				echo '<p>',_('An email with a password reset link has been sent your email address on record'),': <b>'.Sanitize::emailAddress($email).'.</b><br/> ';
				echo _('If you do not see it in a few minutes, check your spam or junk box to see if the email ended up there.'),'<br/>';
				echo sprintf(_('It may help to add %s to your contacts list.'),'<b>'.Sanitize::encodeStringForDisplay($sendfrom).'</b>'),'</p>';
				echo '<p>',_('If you still have trouble or the wrong email address is on file, contact your instructor - they can reset your password for you.'),'</p>';
				if (function_exists('getInstructorSupport')) {
					getInstructorSupport($rights);
				}
				require("footer.php");
				exit;
			} else {
				echo _("Invalid Username"),".  <a href=\"index.php$gb\">",_("Try again"),"</a>";
				exit;
			}
			header('Location: ' . $GLOBALS['basesiteurl'] . "/index.php?r=" . Sanitize::randomQueryStringParam());
		} else if (isset($_POST['pw1'])) {
			if ($_POST['pw1']!=$_POST['pw2']) {
				echo _('Passwords do not match'),'.  <a href="forms.php?action=resetpw&code='.Sanitize::encodeUrlParam($_POST['code'])
					.'&id='.Sanitize::encodeUrlParam($_POST['id']).'">',_('Try again'),'</a>';
				exit;
			}

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

					$query = "UPDATE imas_users SET password=:newpw,remoteaccess='' WHERE id=:id LIMIT 1";
					$stm = $DBH->prepare($query);
					$stm->execute(array(':id'=>$_POST['id'], ':newpw'=>$newpw));
					echo _("Password Reset"),".  ";
					echo "<a href=\"index.php\">",_("Login with your new password"),"</a>";
				} else {
					echo _('Invalid code');
				}
			} else {
				echo _('Invalid user');
			}
			exit;
		} else if (isset($_GET['code'])) {
			//moved to forms.php - keep redirect for to keep old links working for now.
			header('Location: ' . $GLOBALS['basesiteurl'] . '/action=resetpw&id='.Sanitize::onlyInt($_GET['id']).'&code='.Sanitize::encodeUrlParam($code) . "&r=" . Sanitize::randomQueryStringParam());
		}
	} else if (isset($_GET['action']) && $_GET['action']=="lookupusername") {
        $init_session_start = true;
		require_once("init_without_validate.php");
        if (!isset($_SESSION['challenge']) || $_POST['challenge'] !== $_SESSION['challenge'] ||
            !empty($_POST['terms']) ||
            !isset($_SESSION['lookupusernamestart']) || (time() - $_SESSION['lookupusernamestart']) < 3
        ) {
            echo ($_POST['challenge'] !== $_SESSION['challenge']) ? 'challenge bad' : 'challenge ok';
            if ((time() - $_SESSION['lookupusernamestart']) < 5) { echo 'time blocked';}

            echo "Invalid submission";
            exit;
        }
        $_SESSION['challenge'] = '';
        unset($_SESSION['lookupusernamestart']);

		$query = "SELECT id,SID,lastaccess,lastemail FROM imas_users WHERE email=:email AND SID NOT LIKE 'lti-%'";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':email'=>$_POST['email']));
		if ($stm->rowCount() > 0) {
			$cnt = $stm->rowCount();
			$message  = "<h3>".sprintf(_("This is an automated message from %s. Do not respond to this email"),$installname)."</h3>\r\n";
			$message .= "<p>".sprintf(_("Your email was entered in the Username Lookup page on %s.  If you did not do this, you may ignore and delete this message."),$installname)."  ";
			$message .= _("All usernames using this email address are listed below")."</p><p>";
			$ids = array();
			while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
				if (time() - $row['lastemail'] < 60) {
					echo 'Please wait and try again';
					exit;
				}
				$ids[] = $row['id'];
				if ($row['lastaccess']==0) {
					$lastlogin = _("Never");
				} else {
					$lastlogin = date("n/j/y g:ia",$row['lastaccess']);
				}
				$message .= _("Username").": <b>{$row['SID']}</b>.  "._("Last logged in").": $lastlogin<br/>";
			}
			$message .= "</p><p>"._("If you forgot your password, use the Lost Password link at the login page.")."</p>";

			require_once("./includes/email.php");
			send_email($_POST['email'], $sendfrom, $installname._(' Username Request'), $message, array(), array(), 10);
			echo $cnt . _(" usernames match this email address and were emailed"),".  <a href=\"index.php\">",_("Return to login page"),"</a>";

			$ids = implode(',', $ids); // database values, so safe
			$stm = $DBH->prepare("UPDATE imas_users SET lastemail=? WHERE id IN ($ids)");
			$stm->execute(array(time()));
			exit;
		} else {

			$query = "SELECT SID,lastaccess FROM imas_users WHERE email=:email AND SID LIKE 'lti-%'";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':email'=>$_POST['email']));
			if ($stm->rowCount() > 0) {
				echo _("Your account can only be accessed through your school's learning management system.")," <a href=\"index.php\">",_("Return to login page"),"</a>";
			} else {
				echo _("No usernames match this email address, or the email address provided is invalid.")," <a href=\"index.php\">",_("Return to login page"),"</a>";
			}
			exit;
		}
	} else if (isset($_GET['action']) && $_GET['action']=="checkusername") {
		require_once("init_without_validate.php");
		if (isset($_GET['originalSID']) && $_GET['originalSID']==$_GET['SID']) {
			echo "true";
			exit;
		}
		$stm = $DBH->prepare("SELECT id FROM imas_users WHERE SID=:SID");
		$stm->execute(array(':SID'=>$_GET['SID']));
		if ($stm->rowCount()>0) {
			echo "false";
		} else {
			echo "true";
		}
		exit;
	}

	require("init.php");
	if (isset($_GET['action']) && $_GET['action']=="logout") {
		$_SESSION = array();
		if (isset($_COOKIE[session_name()])) {
			setcookie(session_name(), '', time()-42000, '/', null, false, true);
		}
		session_destroy();
	} else if (isset($_GET['action']) && ($_GET['action']=="chgpwd" || $_GET['action']=="forcechgpwd")) {
		$stm = $DBH->prepare("SELECT password,email,lastemail FROM imas_users WHERE id=:uid");
		$stm->execute(array(':uid'=>$userid));
		$line = $stm->fetch(PDO::FETCH_ASSOC);
		if ((md5($_POST['oldpw'])==$line['password'] || (isset($CFG['GEN']['newpasswords']) && password_verify($_POST['oldpw'],$line['password']))) && ($_POST['pw1'] == $_POST['pw2']) && $myrights>5) {
			if (isset($CFG['GEN']['newpasswords'])) {
				$newpw = password_hash($_POST['pw1'], PASSWORD_DEFAULT);
			} else {
				$newpw =md5($_POST['pw1']);
			}
			$stm = $DBH->prepare("UPDATE imas_users SET password=:newpw,forcepwreset=0 WHERE id=:uid LIMIT 1");
			$stm->execute(array(':uid'=>$userid, ':newpw'=>$newpw));

			if ($_GET['action']=="chgpwd" && time() - $line['lastemail'] > 60) {
				require_once("./includes/email.php");
				$message = '<p><b>'._('This is an automated message. Do not reply to this email.').'</b></p>';
				$message .= '<p>'.sprintf(_('Hi, your account details on %s were recently changed.'), $installname).' ';
				$message .= _('Your password was changed.');
				$message .= '</p><p>'._('If this was you, you can disregard this email.').' ';
				$message .= _('If you did not make these changes, please log into your account and correct the changes and change your password.').' ';

				$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
				$code = '';
				for ($i=0;$i<10;$i++) {
					$code .= substr($chars,rand(0,61),1);
				}

				$query = "UPDATE imas_users SET remoteaccess=:code,lastemail=:now WHERE id=:id";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':code'=>$code, ':now'=>time(), ':id'=>$userid));

				$message .= _('If you are unable to log into your account, use the following link.'). ' ';
				$message .= '<a href="' . $GLOBALS['basesiteurl'] . "/forms.php?action=resetpw&id=$userid&code=$code\">";
				$message .= _('Reset Password').'</a></p>';

				send_email($line['email'], $sendfrom,
					_('Alert:'). ' '.$installname.' '._('Account Activity'),
					$message, array(), array(), 10
				);

			}
		} else {
			echo "<html><body>",_("Password change failed"),".  <a href=\"forms.php?action=".Sanitize::simpleString($_GET['action']).$gb."\">",_("Try Again"),"</a>\n";
			echo "</body></html>\n";
			exit;
		}

	} else if (isset($_GET['action']) && $_GET['action']=="enroll") {
		if ($myrights < 6) {
			echo "<html><body>\n",_("Error: Guests can't enroll in courses"),"</body></html";
			exit;
		}
		if (isset($_POST['courseselect']) && $_POST['courseselect']>0) {
			$_POST['cid'] = $_POST['courseselect'];
			$_POST['ekey'] = '';
		}
		$pagetopper = '';
		if ($gb == '') {
			$pagetopper .= "<div class=breadcrumb><a href=\"index.php\">"._("Home")."</a> &gt; "._("Enroll in a Course")."</div>\n";
		}
		$pagetopper .= '<div id="headerforms" class="pagetitle"><h1>'._('Enroll in a Course').'</h1></div>';
		if ($_POST['cid']=="" || !is_numeric($_POST['cid'])) {
			require("header.php");
			echo $pagetopper;
			echo _("Please include Course ID."),"  <a href=\"forms.php?action=enroll$gb\">",_("Try Again"),"</a>\n";
			require("footer.php");
			exit;
		}

		$stm = $DBH->prepare("SELECT enrollkey,allowunenroll,deflatepass,msgset FROM imas_courses WHERE id = :cid AND (available=0 OR available=2)");
		$stm->execute(array(':cid'=>$_POST['cid']));
		$line = $stm->fetch(PDO::FETCH_ASSOC);

		if ($line === false) {
			require("header.php");
			echo $pagetopper;
			echo _("Course not found."),"  <a href=\"forms.php?action=enroll$gb\">",_("Try Again"),"</a>\n";
			require("footer.php");
			exit;
		} else if (($line['allowunenroll']&2)==2) {
			require("header.php");
			echo $pagetopper;
			echo _("Course is closed for self enrollment.  Contact your instructor for access."),"  <a href=\"index.php\">",_("Return to home page."),"</a>\n";
			require("footer.php");
			exit;
		} else if ($_POST['ekey']=="" && $line['enrollkey'] != '') {
			require("header.php");
			echo $pagetopper;
			echo _("Please include Enrollment Key."),"  <a href=\"forms.php?action=enroll$gb\">",_("Try Again"),"</a>\n";
			require("footer.php");
			exit;
		}  else {
			$stm = $DBH->prepare("SELECT id FROM imas_teachers WHERE userid=:uid AND courseid=:cid");
			$stm->execute(array(':uid'=>$userid, ':cid'=>$_POST['cid']));
			if ($stm->rowCount() > 0) {
				require("header.php");
				echo $pagetopper;
				echo _("You are a teacher for this course, and can't enroll as a student.  Use Student View to see the class from a student's perspective, or create a dummy student account.  ");
				echo _("Click on the course name on the <a href=\"index.php\">main page</a> to access the course"),"\n";
				require("footer.php");
				exit;
			}
			$stm = $DBH->prepare("SELECT id FROM imas_tutors WHERE userid=:uid AND courseid=:cid");
			$stm->execute(array(':uid'=>$userid, ':cid'=>$_POST['cid']));
			if ($stm->rowCount() > 0) {
				require("header.php");
				echo $pagetopper;
				echo _("You are a tutor for this course, and can't enroll as a student. ");
				echo _("Click on the course name on the <a href=\"index.php\">main page</a> to access the course"),"\n";
				require("footer.php");
				exit;
			}
			$stm = $DBH->prepare("SELECT id FROM imas_students WHERE userid=:uid AND courseid=:cid");
			$stm->execute(array(':uid'=>$userid, ':cid'=>$_POST['cid']));
			if ($stm->rowCount() > 0) {
				require("header.php");
				echo $pagetopper;
				echo _("You are already enrolled in the course.  Click on the course name on the <a href=\"index.php\">main page</a> to access the course"),"\n";
				require("footer.php");
				exit;
			} else {
                $keylist = array_map('trim',explode(';',$line['enrollkey']));
                if (($p = array_search(strtolower(trim($_POST['ekey'])), array_map('strtolower', $keylist))) === false) {
					require("header.php");
					echo $pagetopper;
					echo _("Incorrect Enrollment Key."),"  <a href=\"forms.php?action=enroll$gb\">",_("Try Again"),"</a>\n";
					require("footer.php");
					exit;
				} else {
                    $_POST['ekey'] = $keylist[$p];
                    require('./includes/setSectionGroups.php');
					if (count($keylist)>1) {
						$query = "INSERT INTO imas_students (userid,courseid,section,latepass) VALUES (:uid,:cid,:section,:latepass);";
                        $array = array(':uid'=>$userid, ':cid'=>$_POST['cid'], ':section'=>$_POST['ekey'],':latepass'=>$line['deflatepass']);
                        setSectionGroups($userid, $_POST['cid'], $_POST['ekey']);
					} else {
						$query = "INSERT INTO imas_students (userid,courseid,latepass) VALUES (:uid,:cid,:latepass);";
                        $array = array(':uid'=>$userid, ':cid'=>$_POST['cid'], ':latepass'=>$line['deflatepass']);
                        setSectionGroups($userid, $_POST['cid'], '');
					}
					$stm = $DBH->prepare($query);
                    $stm->execute($array);

                    sendMsgOnEnroll($line['msgset'], $_POST['cid'], $userid);

					//call hook, if defined
					if (function_exists('onEnroll')) {
						onEnroll($_POST['cid']);
					}

					require("header.php");
					echo $pagetopper;
					echo '<p>',_('You have been enrolled in course ID ').Sanitize::courseId($_POST['cid']).'</p>';
					echo "<p>",_("Return to the <a href=\"index.php\">main page</a> and click on the course name to access the course"),"</p>";
					require("footer.php");
					exit;
				}


				//$query = "INSERT INTO imas_students (userid,courseid) VALUES ('$userid','{$_POST['cid']}');";
				//mysql_query($query) or die("Query failed : " . mysql_error());
			}
		}
	} else if (isset($_POST['action']) && $_POST['action']=="unenroll") {
		if ($myrights < 6) {
			echo "<html><body>\n",_("Error: Guests can't unenroll from courses"),"</body></html>";
			exit;
		}
		if (!isset($_GET['cid'])) {
			require("header.php");
			echo _("Course ID not specified."),"  <a href=\"index.php\">",_("Try Again"),"</a>\n";
			require("footer.php");
			exit;
		}
		$cid = Sanitize::courseId($_GET['cid']);
		$stm = $DBH->prepare("SELECT allowunenroll FROM imas_courses WHERE id=:cid");
		$stm->execute(array(':cid'=>$cid));
		if ($stm->fetchColumn()==1) {
			$stm = $DBH->prepare("DELETE FROM imas_students WHERE userid=:uid AND courseid=:cid");
			$stm->execute(array(':uid'=>$userid,':cid'=>$cid));
			/*
			$stm = $DBH->prepare("SELECT id FROM imas_assessments WHERE courseid=:cid");
			$stm->execute(array(':cid'=>$cid));
			while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
				$stm = $DBH->prepare("DELETE FROM imas_assessment_sessions WHERE assessmentid=:aid AND userid=:uid");
				$stm->execute(array(':uid'=>$userid,':aid'=>$row['id']));
				$stm = $DBH->prepare("DELETE FROM imas_exceptions WHERE assessmentid=:aid AND itemtype='A' AND userid=:uid");
				$stm->execute(array(':uid'=>$userid,':aid'=>$row['id']));
			}
			*/
			$stm = $DBH->prepare("DELETE FROM imas_assessment_sessions WHERE assessmentid IN (SELECT id FROM imas_assessments WHERE courseid=:cid) AND userid=:uid");
			$stm->execute(array(':uid'=>$userid,':cid'=>$cid));
			$stm = $DBH->prepare("DELETE FROM imas_assessment_records WHERE assessmentid IN (SELECT id FROM imas_assessments WHERE courseid=:cid) AND userid=:uid");
			$stm->execute(array(':uid'=>$userid,':cid'=>$cid));

			$stm = $DBH->prepare("DELETE FROM imas_exceptions WHERE itemtype='A' AND assessmentid IN (SELECT id FROM imas_assessments WHERE courseid=:cid) AND userid=:uid");
			$stm->execute(array(':uid'=>$userid,':cid'=>$cid));
			$stm = $DBH->prepare("DELETE FROM imas_drillassess_sessions WHERE drillassessid IN (SELECT id FROM imas_drillassess WHERE courseid=:cid) AND userid=:uid");
			$stm->execute(array(':uid'=>$userid,':cid'=>$cid));
			//}
			$stm = $DBH->prepare("DELETE FROM imas_grades WHERE gradetype='offline' AND gradetypeid IN (SELECT id FROM imas_gbitems WHERE courseid=:cid) AND userid=:uid");
			$stm->execute(array(':uid'=>$userid,':cid'=>$cid));
			//	}
			//}
			$query = "DELETE FROM imas_forum_views WHERE userid=:uid AND threadid IN ";
			$query .= "(SELECT ifp.threadid FROM imas_forum_posts AS ifp JOIN imas_forums ON ifp.forumid=imas_forums.id WHERE imas_forums.courseid=:cid)";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':uid'=>$userid,':cid'=>$cid));

			$stm = $DBH->prepare("DELETE FROM imas_grades WHERE gradetype='forum' AND gradetypeid IN (SELECT id FROM imas_forums WHERE courseid=:cid) AND userid=:uid");
			$stm->execute(array(':uid'=>$userid,':cid'=>$cid));

			$query = "DELETE FROM imas_exceptions WHERE (itemtype='F' OR itemtype='P' OR itemtype='R') AND userid=:uid AND assessmentid IN ";
			$query .= "(SELECT id FROM imas_forums WHERE courseid=:cid)";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':uid'=>$userid,':cid'=>$cid));

		}
	} else if (isset($_GET['action']) && $_GET['action']=="chguserinfo") {
		$pagetopper = '';
		if ($gb == '') {
			$pagetopper .= "<div class=breadcrumb><a href=\"index.php\">Home</a> &gt; Modify User Profile</div>\n";
		}
		$pagetopper .= '<div id="headerforms" class="pagetitle"><h1>Modify User Profile</h1></div>';
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

		$stm = $DBH->prepare("SELECT email,lastemail,mfa,password FROM imas_users WHERE id=?");
		$stm->execute(array($userid));
        list($old_email,$lastemail,$lastmfa,$oldpw) = $stm->fetch(PDO::FETCH_NUM);

        if ($lastmfa !== '') {
            $mfadata = json_decode($lastmfa, true);
            if (!empty($mfadata['mfatype']) && $mfadata['mfatype'] == 'admin') {
                $lastmfatype = 1;
            } else {
                $lastmfatype = 2;
            }
        } else {
            $lastmfatype = 0;
        }
        
        if (isset($_POST['dochgpw']) || 
            $_POST['dochgmfa'] < $lastmfatype ||
            trim($old_email) != trim($_POST['email'])
        ) {
            // these changes require security check
            if ((md5($_POST['oldpw'])==$oldpw || (isset($CFG['GEN']['newpasswords']) && password_verify($_POST['oldpw'],$oldpw))) && $myrights>5) {
                // pw ok
            } else {
                require("header.php");
                echo $pagetopper;
                echo _("Password verification failed."),"  <a href=\"forms.php?action=chguserinfo$gb\">",_("Try Again"),"</a>\n";
                require("footer.php");
                exit;
            }
            if ($lastmfatype > 0) {
                // also check MFA
                require_once('includes/GoogleAuthenticator.php');
                $MFA = new GoogleAuthenticator();
   
                if (!$MFA->verifyCode($mfadata['secret'], $_POST['oldmfa'])) {
                    // MFA ok
                    require("header.php");
                    echo $pagetopper;
                    echo "2-factor authentication verification failed.  <a href=\"forms.php?action=chguserinfo$gb\">Try Again</a>\n";
                    require("footer.php");
                    exit;
                }
            }
        }
        // if we're here, then security checks for pw, email, and mfa change are OK

		$query = "UPDATE imas_users SET FirstName=:FirstName, LastName=:LastName, email=:email, msgnotify=:msgnotify, qrightsdef=:qrightsdef, deflib=:deflib,";
		$query .= "usedeflib=:usedeflib, homelayout=:homelayout, theme=:theme, listperpage=:listperpage $chguserimg WHERE id=:uid";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':FirstName'=>$_POST['firstname'],
			':LastName'=>$_POST['lastname'], ':email'=>$_POST['email'], ':msgnotify'=>$msgnot, ':homelayout'=>$layoutstr, ':qrightsdef'=>$qrightsdef,
			':deflib'=>$deflib, ':usedeflib'=>$usedeflib, ':theme'=>$_POST['theme'], ':listperpage'=>$perpage, ':uid'=>$userid));

		$pwchanged = false;
		if (isset($_POST['dochgpw'])) {
			if ($_POST['pw1'] == $_POST['pw2'] && $myrights>5) {
				if (isset($CFG['GEN']['newpasswords'])) {
					$newpw = password_hash($_POST['pw1'], PASSWORD_DEFAULT);
				} else {
					$newpw =md5($_POST['pw1']);
				}
				$stm = $DBH->prepare("UPDATE imas_users SET password = :newpw WHERE id = :uid");
				$stm->execute(array(':uid'=>$userid, ':newpw'=>$newpw));
				$pwchanged = true;
			} else {
				require("header.php");
				echo $pagetopper;
				echo _("Password change failed."),"  <a href=\"forms.php?action=chguserinfo$gb\">",_("Try Again"),"</a>\n";
				require("footer.php");
				exit;
			}
		}
		if ($_POST['dochgmfa'] > 0) {
            if ($lastmfatype == 0) {
                //enabling new
                require_once('includes/GoogleAuthenticator.php');
                $MFA = new GoogleAuthenticator();
                $mfasecret = $_POST['mfasecret'];

                if ($MFA->verifyCode($mfasecret, $_POST['mfaverify'])) {
                    $mfadata = array(
                        'secret'=>$mfasecret, 
                        'last'=>'', 
                        'laston'=>0, 
                        'mfatype'=>($_POST['dochgmfa'] == 1 ? 'admin' : 'all')
                    );
                } else {
                    require("header.php");
                    echo $pagetopper;
                    echo "Incorrect 2-factor authentication code.  <a href=\"forms.php?action=chguserinfo$gb\">Try Again</a>\n";
                    require("footer.php");
                    exit;
                }
            } else {
                $mfadata['mfatype'] = ($_POST['dochgmfa'] == 1 ? 'admin' : 'all');
            }
            $stm = $DBH->prepare("UPDATE imas_users SET mfa = :mfa WHERE id = :uid");
			$stm->execute(array(':uid'=>$userid, ':mfa'=>json_encode($mfadata)));
		} else {
            // disable MFA
			$stm = $DBH->prepare("UPDATE imas_users SET mfa = '' WHERE id = :uid");
			$stm->execute(array(':uid'=>$userid));
		}

		require("includes/userprefs.php");
		storeUserPrefs();

		if (($pwchanged || trim($old_email) != trim($_POST['email'])) && (time() - $lastemail > 60)) {
			require_once("./includes/email.php");
			$message = '<p><b>'._('This is an automated message. Do not reply to this email.').'</b></p>';
			$message .= '<p>'.sprintf(_('Hi, your account details on %s were recently changed.'), $installname).' ';
			if ($old_email != $_POST['email']) {
				$message .= sprintf(_('Your email address was changed to %s.'), Sanitize::encodeStringForDisplay($_POST['email'])).' ';
			}
			if ($pwchanged) {
				$message .= _('Your password was changed.');
			}
			$message .= '</p><p>'._('If this was you, you can disregard this email.').' ';
			$message .= _('If you did not make these changes, please log into your account and correct the changes and change your password.').' ';

			if ($pwchanged) {
				$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
				$code = '';
				for ($i=0;$i<10;$i++) {
					$code .= substr($chars,rand(0,61),1);
				}

				$query = "UPDATE imas_users SET remoteaccess=:code,lastemail=:now WHERE id=:id";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':code'=>$code, ':now'=>time(), ':id'=>$userid));

				$message .= _('If you are unable to log into your account, use the following link.'). ' ';
				$message .= '<a href="' . $GLOBALS['basesiteurl'] . "/forms.php?action=resetpw&id=$userid&code=$code\">";
				$message .= _('Reset Password').'</a></p>';
			}

			send_email($old_email, $sendfrom,
				_('Alert:'). ' '.$installname.' '._('Account Activity'),
				$message, array(), array(), 10
			);

		}

	} else if (isset($_POST['action']) && $_POST['action'] == 'cleartrustedmfa') {
        $stm = $DBH->prepare("SELECT mfa FROM imas_users WHERE id= :uid");
        $stm->execute(array(':uid'=>$userid));
        $mfadata = json_decode($stm->fetchColumn(0), true);
        if ($mfadata !== false) {
            unset($mfadata['trusted']);
            unset($mfadata['logintrusted']);
            $stm = $DBH->prepare("UPDATE imas_users SET mfa=:mfa WHERE id=:uid");
            $stm->execute(array(':mfa'=>json_encode($mfadata), ':uid'=>$userid));
            echo "OK";
            exit;
        }
        echo "FAIL";
        exit;
    } else if (isset($_GET['action']) && $_GET['action']=="forumwidgetsettings") {
		if (empty($_POST['checked'])) {
			$checked = array();
		} else {
			$checked = $_POST['checked'];
		}
		$all = explode(',',$_POST['allcourses']);
		foreach ($all as $k=>$v) {
			$all[$k] = intval($v);
		}
		$tohide = array_diff($all,$checked);
		$hidelist = implode(',', $tohide);
		$stm = $DBH->prepare("UPDATE imas_users SET hideonpostswidget=:hidelist WHERE id= :uid");
		$stm->execute(array(':uid'=>$userid, ':hidelist'=>$hidelist));
	} else if (isset($_GET['action']) && $_GET['action']=="googlegadget") {
		if (isset($_GET['clear'])) {
			$stm = $DBH->prepare("UPDATE imas_users SET remoteaccess='' WHERE id = :uid");
			$stm->execute(array(':uid'=>$userid));
		}
	}
	if ($isgb) {
		echo '<html><body>',_('Changes Recorded.'),'  <input type="button" onclick="parent.GB_hide()" value="',_('Done'),'" /></body></html>';
	} else if (isset($_SESSION['ltiitemtype']) && $_SESSION['ltiitemtype']==0) {
		$stm = $DBH->prepare("SELECT courseid FROM imas_assessments WHERE id=:id");
		$stm->execute(array(':id'=>$_SESSION['ltiitemid']));
		$cid = Sanitize::courseId($stm->fetchColumn(0));
		if (isset($_SESSION['ltiitemver']) && $_SESSION['ltiitemver'] > 1) {
			header('Location: ' . $GLOBALS['basesiteurl'] . "/assess2/?cid=$cid&aid={$_SESSION['ltiitemid']}&r=".Sanitize::randomQueryStringParam());
		} else {
			header('Location: ' . $GLOBALS['basesiteurl'] . "/assessment/showtest.php?cid=$cid&id={$_SESSION['ltiitemid']}&r=".Sanitize::randomQueryStringParam());
		}
	} else {
		header('Location: ' . $GLOBALS['basesiteurl'] . "/index.php?r=" . Sanitize::randomQueryStringParam());
	}




?>

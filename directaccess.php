<?php
//IMathAS:  Course direct access - redirects to course page or presents
//login / new student page specific for course
//(c) 2007 David Lippman

	$curdir = rtrim(dirname(__FILE__), '/\\');
	 if (!file_exists("$curdir/config.php")) {
		 header('Location: ' . $GLOBALS['basesiteurl'] . "/install.php?r=" . Sanitize::randomQueryStringParam());
	 }
	$init_session_start = true;
 	require_once(__DIR__ . "/init_without_validate.php");
	require_once(__DIR__ ."/includes/newusercommon.php");
	$cid = Sanitize::courseId($_GET['cid']);

 	if (!isset($_GET['cid'])) {
		echo _("Invalid address.  Address must be directaccess.php?cid=###, where ### is your courseid");
		exit;
	}
	 if((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO']=='https'))  {
		 $urlmode = 'https://';
	 } else {
		 $urlmode = 'http://';
	 }
	if (isset($_SERVER['QUERY_STRING'])) {
		 $querys = '?'.Sanitize::fullQueryString($_SERVER['QUERY_STRING']);
	 } else {
		 $querys = '';
	 }
	 $page_newaccounterror = '';
	 if (isset($_POST['submit']) && $_POST['submit']=="Sign Up") {
        if (!isset($_SESSION['challenge']) || $_POST['challenge'] !== $_SESSION['challenge'] ||
        !empty($_POST['hval']) ||
        !isset($_SESSION['newuserstart']) || (time() - $_SESSION['newuserstart']) < 5
     ) {
        echo "Invalid submission";
        exit;
     }
     $_SESSION['challenge'] = '';
     unset($_SESSION['newuserstart']);

		unset($_POST['username']);
		unset($_POST['password']);

		$page_newaccounterror = checkNewUserValidation();
		$stm = $DBH->prepare("SELECT enrollkey,deflatepass,allowunenroll,msgset FROM imas_courses WHERE id=:id");
		$stm->execute(array(':id'=>$_GET['cid']));
        list($enrollkey,$deflatepass,$allowunenroll,$msgset) = $stm->fetch(PDO::FETCH_NUM);
        if (($allowunenroll&2)==2) {
            $page_newaccounterror .= _('Course is closed for self enrollment.  Contact your instructor for access.');
        } else if (strlen($enrollkey)>0 && trim($_POST['ekey2'])=='') {
			$page_newaccounterror .= _("Please provide the enrollment key");
		} else if (strlen($enrollkey)>0) {
            $keylist = array_map('trim',explode(';',$enrollkey));
            if (($p = array_search(strtolower(trim($_POST['ekey2'])), array_map('strtolower', $keylist))) === false) {
				$page_newaccounterror .= _("Enrollment key is invalid.");
			} else {
                $_POST['ekey'] = $keylist[$p];
                $_POST['ekey2'] = $keylist[$p];
			}
		}

		if ($page_newaccounterror=='') {//no error
			if (isset($CFG['GEN']['newpasswords'])) {
				require_once("includes/password.php");
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
			if (!empty($_POST['code'])) {
				$code = preg_replace('/[^\w]/','',$_POST['code']);
			} else {
				$code = '';
			}
			if (isset($CFG['GEN']['homelayout'])) {
				$homelayout = $CFG['GEN']['homelayout'];
			} else {
				$homelayout = '|0,1,2||0,1';
            }
            $jsondata = [];
            if (isset($CFG['GEN']['COPPA']) && empty($_POST['over13'])) {
                $jsondata['under13'] = 1;
            }
			$query = "INSERT INTO imas_users (SID, password, rights, FirstName, LastName, email, msgnotify, homelayout, jsondata) ";
			$query .= "VALUES (:SID, :password, :rights, :FirstName, :LastName, :email, :msgnotify, :homelayout, :jsondata)";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':SID'=>$_POST['SID'], ':password'=>$md5pw, ':rights'=>$initialrights,
				':FirstName'=>Sanitize::stripHtmlTags($_POST['firstname']),
				':LastName'=>Sanitize::stripHtmlTags($_POST['lastname']),
				':email'=>Sanitize::emailAddress($_POST['email']),
				':msgnotify'=>$msgnot, ':homelayout'=>$homelayout, ':jsondata'=>json_encode($jsondata)));
            $newuserid = $DBH->lastInsertId();
            require('./includes/setSectionGroups.php');
			if (strlen($enrollkey)>0 && count($keylist)>1) {
				$stm = $DBH->prepare("INSERT INTO imas_students (userid,courseid,section,gbcomment,latepass) VALUES (:userid, :courseid, :section, :gbcomment, :latepass)");
                $stm->execute(array(':userid'=>$newuserid, ':courseid'=>$cid, ':section'=>$_POST['ekey2'], ':gbcomment'=>$code, ':latepass'=>$deflatepass));
                setSectionGroups($newuserid, $cid, $_POST['ekey2']);
			} else {
				$stm = $DBH->prepare("INSERT INTO imas_students (userid,courseid,gbcomment,latepass) VALUES (:userid, :courseid, :gbcomment, :latepass)");
                $stm->execute(array(':userid'=>$newuserid, ':courseid'=>$cid, ':gbcomment'=>$code, ':latepass'=>$deflatepass));
                setSectionGroups($newuserid, $cid, '');
            }
            sendMsgOnEnroll($msgset, $cid, $newuserid);

			if ($emailconfirmation) {
				$id = $DBH->lastInsertId();

				$message  = "<h3>".sprintf(_("This is an automated message from %s.  Do not respond to this email."),$installname)."</h3>\r\n";
				$message .= "<p>".sprintf(_("To complete your %s registration, please click on the following link, or copy and paste it into your webbrowser:"),$installname)."</p>\r\n";
				$message .= "<a href=\"" . $GLOBALS['basesiteurl'] . "/actions.php?action=confirm&id=$id\">";
				$message .= $GLOBALS['basesiteurl'] . "/actions.php?action=confirm&id=$id</a>\r\n";

				require_once("./includes/email.php");
				send_email($_POST['email'], $sendfrom, $installname._(' Confirmation'), $message, array(), array(), 10);

				echo "<html><body>\n";
				echo _("Registration recorded.  You should shortly receive an email with confirmation instructions.");
				echo "<a href=\"$imasroot/directaccess.php?cid=$cid\">",_("Back to login page"),"</a>\n";
				echo "</body></html>\n";
				exit;
			} else {
				$_POST['username'] = $_POST['SID'];
				$_POST['password'] = $_POST['pw1'];
			}
		}
	 }
	//check for session
	$origquerys = $querys;
	if (!empty($_POST['ekey'])) {
		$addtoquerystring = "ekey=".Sanitize::encodeUrlParam($_POST['ekey']);
	}
	$init_session_start = true;
	require_once(__DIR__ ."/validate.php");
	$flexwidth = true;
	if ($verified) { //already have session
		if (!isset($studentid) && !isset($teacherid) && !isset($tutorid)) {  //have account, not a student
			$stm = $DBH->prepare("SELECT name,enrollkey,deflatepass,allowunenroll,msgset FROM imas_courses WHERE id=:id");
			$stm->execute(array(':id'=>$_GET['cid']));
			list($coursename,$enrollkey,$deflatepass,$allowunenroll,$msgset) = $stm->fetch(PDO::FETCH_NUM);
            $keylist = array_map('trim',explode(';',$enrollkey));
            if (($allowunenroll&2)==2) {
                require("header.php");
                echo "<h1>" . Sanitize::encodeStringForDisplay($coursename) . "</h1>";
                echo '<p>'._('Course is closed for self enrollment.  Contact your instructor for access.').'</p>';
                require("footer.php");
                exit;
            } else if (strlen($enrollkey)==0 || (isset($_REQUEST['ekey']) && in_array($_REQUEST['ekey'], $keylist))) {
                require('./includes/setSectionGroups.php');
				if (count($keylist)>1) {
					$stm = $DBH->prepare("INSERT INTO imas_students (userid,courseid,section,latepass) VALUES (:userid, :courseid, :section, :latepass)");
                    $stm->execute(array(':userid'=>$userid, ':courseid'=>$cid, ':section'=>$_REQUEST['ekey'], ':latepass'=>$deflatepass));
                    setSectionGroups($userid, $cid, $_REQUEST['ekey']);
				} else {
					$stm = $DBH->prepare("INSERT INTO imas_students (userid,courseid,latepass) VALUES (:userid, :courseid, :latepass)");
                    $stm->execute(array(':userid'=>$userid, ':courseid'=>$cid, ':latepass'=>$deflatepass));
                    setSectionGroups($userid, $cid, '');
                }
                sendMsgOnEnroll($msgset, $cid, $userid);

				header('Location: ' . $GLOBALS['basesiteurl'] . '/course/course.php?cid='. $cid. '&r=' . Sanitize::randomQueryStringParam());
				exit;
			} else {
				require("header.php");
				echo "<h1>" . Sanitize::encodeStringForDisplay($coursename) . "</h1>";
				echo '<form method="post" action="directaccess.php?cid='.$cid.'">';
				echo '<p>',_('Incorrect enrollment key.  Try again.'),'</p>';
				echo "<p>",_("Course Enrollment Key:"),"  <input type=text name=\"ekey\"></p>";
				echo "<p><input type=\"submit\" value=\"",_("Submit"),"\"></p>";
				echo "</form>";
				require("footer.php");
				exit;
			}
		} else {
			header('Location: ' . $GLOBALS['basesiteurl'] . '/course/course.php?cid='. $cid . '&r=' . Sanitize::randomQueryStringParam());
			exit;
		}
	} else { //not verified
		//$placeinhead = "<link rel=\"stylesheet\" href=\"$imasroot/infopages.css\" type=\"text/css\" />\n";
		$stm = $DBH->prepare("SELECT name FROM imas_courses WHERE id=:id");
		$stm->execute(array(':id'=>$_GET['cid']));
		$coursename = $stm->fetchColumn(0);

		if (isset($CFG['GEN']['directaccessincludepath'])) {
			$placeinhead = "<link rel=\"stylesheet\" href=\"$staticroot/".$CFG['GEN']['directaccessincludepath']."infopages.css\" type=\"text/css\">\n";
		} else {
			$placeinhead = "<link rel=\"stylesheet\" href=\"$staticroot/infopages.css\" type=\"text/css\">\n";
		}
		//$placeinhead = "<style type=\"text/css\">div#header {clear: both;height: 75px;background-color: #9C6;margin: 0px;padding: 0px;border-left: 10px solid #036;border-bottom: 5px solid #036;} \n.vcenter {font-family: sans-serif;font-size: 28px;margin: 0px;margin-left: 30px;padding-top: 25px;color: #fff;}</style>";
		$placeinhead .= "<script type=\"text/javascript\" src=\"$staticroot/javascript/jstz_min.js\" ></script>";
		$pagetitle = $coursename;
		$challenge = uniqid();
		$_SESSION['challenge'] = $challenge;
        $_SESSION['newuserstart'] = time();
		$placeinhead .= '<script type="text/javascript" src="'.$staticroot.'/javascript/jquery.validate.min.js?v=122917"></script>';
		if (isset($CFG['locale'])) {
			$placeinhead .= '<script type="text/javascript" src="'.$staticroot.'/javascript/jqvalidatei18n/messages_'.substr($CFG['locale'],0,2).'.min.js"></script>';
		}
		require("header.php");
		//echo "<div class=\"breadcrumb\">$breadcrumbbase $coursename Access</div>";
		echo "<div id=\"header\"><div class=\"vcenter\">" . Sanitize::encodeStringForDisplay($coursename) . "</div></div>";
		//echo '<span style="float:right;margin-top:10px;">'.$smallheaderlogo.'</span>';

		$cid = intval($_GET['cid']);
		$curdir = rtrim(dirname(__FILE__), '/\\');
		if (file_exists("$curdir/".(isset($CFG['GEN']['directaccessincludepath'])?$CFG['GEN']['directaccessincludepath']:'')."directaccess$cid.html")) {
			require("$curdir/".(isset($CFG['GEN']['directaccessincludepath'])?$CFG['GEN']['directaccessincludepath']:'')."directaccess$cid.html");
		}
		$stm = $DBH->prepare("SELECT enrollkey,allowunenroll FROM imas_courses WHERE id=:id");
        $stm->execute(array(':id'=>$cid));
        list($enrollkey,$allowunenroll) = $stm->fetch(PDO::FETCH_NUM);
        if (($allowunenroll&2)==2) {
            echo '<p>', _('Course is closed for self enrollment.  Contact your instructor for access.'),'</p>';
            echo '<p><a href="'.$GLOBALS['basesiteurl'].'/index.php">',_('Go to home page'),'</a>. ';
            echo _('If you are already enrolled, you can log in there.'),'</p>';
            require('footer.php');
            exit;
        }

?>
<form id="pageform" class=limitaftervalidate method="post" action="directaccess.php<?php echo $querys; ?>">

<?php
if ($enrollkey!='closed') {
?>
<h2><?php echo sprintf(_("Do you already have an account on %s?"), $installname);?></h2>
<p>
<input type=radio name="curornew" value="0" onclick="setlogintype(0)" <?php if ($page_newaccounterror=='') {echo 'checked';}?> /> I already have an account on <?php echo $installname;?><br/>
<input type=radio name="curornew" value="1" onclick="setlogintype(1)" <?php if ($page_newaccounterror!='') {echo 'checked';}?> /> I need to create a new account
</p>
<?php
}
?>

<fieldset id="curuser" <?php if ($page_newaccounterror!='') {echo 'style="display:none"';}?>>
<legend><?php echo _("Already have an account"); ?></legend>
<p><b><?php echo _("Login"); ?></b>.  <?php echo sprintf(_("If you have an account on %s but are not enrolled in this course, logging in below will enroll you in this course."),$installname) ?></p>
<?php
	if ($haslogin) {echo '<p style="color: red;">',_('Login Error.  Try Again'),'</p>';}
?>
<span class=form><?php echo $loginprompt;?>:</span><input class="form" type="text" size="15" id="username" name="username"><br class="form">
<span class=form><?php echo _('Password'); ?>:</span><input class="form" type="password" size="15" id="password" name="password"><br class="form">
<?php
if (isset($_GET['ekey'])) {
    echo '<input type="hidden" name="ekey" value="'.Sanitize::encodeStringForDisplay($_GET['ekey']).'">';
} else if (strlen($enrollkey)>0) {
	echo '<span class=form><label for="ekey">',_('Course Enrollment Key'),':</label></span><input class=form type=text size=12 name="ekey" id="ekey" value="' . (isset($_REQUEST['ekey']) ? Sanitize::encodeStringForDisplay($_REQUEST['ekey']) : "") . '"/><BR class=form>';
}
?>
<div class=submit><input type="submit" value=<?php echo "\"",_("Login and Enroll"),"\"" ?>></div>
<span class=form> </span><span class=formright><a href="<?php echo $imasroot; ?>/forms.php?action=resetpw"><?php echo _("Forgot Password") ?></a></span><br class="form">
</table>
<div><noscript><?php echo sprintf(_("JavaScript is not enabled.  JavaScript is required for %s.  Please enable JavaScript and reload this page"),$installname) ?></noscript></div>

<input type="hidden" id="tzoffset" name="tzoffset" value="">
<input type="hidden" id="tzname" name="tzname" value="">
<input type="hidden" id="challenge" name="challenge" value="<?php echo $challenge; ?>" />
<span class="sr-only"><label aria-hidden=true">Do not fill this out <input name=hval tabindex="-1"></label></span>
<script type="text/javascript">
$(function() {
        var thedate = new Date();
        document.getElementById("tzoffset").value = thedate.getTimezoneOffset();
        var tz = jstz.determine();
        document.getElementById("tzname").value = tz.name();
				<?php
					if ($page_newaccounterror!='') {
						echo 'document.getElementById("SID").focus();';
					} else {
						echo 'document.getElementById("username").focus();';
					}
				?>
});
</script>

<?php
	if ($enrollkey!='closed') {
?>
</fieldset>
<script type="text/javascript">
var logintype=<?php echo ($page_newaccounterror=='')?'0':'1';?>;
function setlogintype(n) {
	if (n==0) {
		$("#curuser").show();
		$("#newuser").hide();
	} else {
		$("#curuser").hide();
		$("#newuser").show();
	}
	logintype=n;
}
</script>
<fieldset id="newuser" <?php if ($page_newaccounterror=='') {echo 'style="display:none"';}?>>
<legend>New to <?php echo $installname; ?></legend>

<p><b><?php echo _("New Student Enrollment") ?></b></p>
<?php
if ($page_newaccounterror!='') {
	echo '<p class=noticetext>' . Sanitize::encodeStringForDisplay($page_newaccounterror) . '</p>';
}
?>
<span class=form><label for="SID"><?php echo $longloginprompt;?>:</label></span> <input class=form type=text size=12 id=SID name=SID <?php if (isset($_POST['SID'])) { printf('value="%s"', Sanitize::encodeStringForDisplay($_POST['SID'])); } ?>><BR class=form>
<span class=form><label for="pw1"><?php echo _("Choose a password:"); ?></label></span><input class=form type=password size=20 id=pw1 name=pw1 <?php if (isset($_POST['pw1'])) { printf('value="%s"', Sanitize::encodeStringForDisplay($_POST['pw1'])); } ?>><BR class=form>
<span class=form><label for="pw2"><?php echo _("Confirm password:"); ?></label></span> <input class=form type=password size=20 id=pw2 name=pw2 <?php if (isset($_POST['pw2'])) { printf('value="%s"', Sanitize::encodeStringForDisplay($_POST['pw2'])); } ?>><BR class=form>
<span class=form><label for="firstname"><?php echo _("Enter First Name:"); ?></label></span> <input class=form type=text size=20 id=firstname name=firstname autocomplete="given-name" <?php if (isset($_POST['firstname'])) { printf('value="%s"', Sanitize::encodeStringForDisplay($_POST['firstname'])); } ?>><BR class=form>
<span class=form><label for="lastname"><?php echo _("Enter Last Name:"); ?></label></span> <input class=form type=text size=20 id=lastname name=lastname autocomplete="family-name" <?php if (isset($_POST['lastname'])) { printf('value="%s"', Sanitize::encodeStringForDisplay($_POST['lastname'])); } ?>><BR class=form>
<span class=form><label for="email"><?php echo _("Enter E-mail address:"); ?></label></span>  <input class=form type=text size=60 id=email name=email autocomplete="email" <?php if (isset($_POST['email'])) { printf('value="%s"', Sanitize::encodeStringForDisplay($_POST['email'])); } ?>><BR class=form>
<?php
if (isset($_GET['getsid'])) {
	echo '<span class="form"><label for="code">',_('If you are registered at a Washington Community College, enter your Student ID Number:'),'</label></span><input class="form" type="text" size="20" id="code" name="code"><BR class=form>';
}
?>
<span class=form><label for="msgnot"><?php echo _("Notify me by email when I receive a new message:"); ?></label></span><span class=formright><input type=checkbox id=msgnot name=msgnot /></span><BR class=form>
<?php
    if (isset($CFG['GEN']['COPPA'])) {
        echo "<span class=form><label for=\"over13\">",_('I am 13 years old or older'),"</label></span><span class=formright><input type=checkbox name=over13 id=over13 onchange=\"toggleOver13()\"></span><br class=form />\n";
    }
    if (isset($_GET['ekey'])) {
        echo '<input type="hidden" name="ekey2" value="'.Sanitize::encodeStringForDisplay($_GET['ekey']).'">';
    } else if (strlen($enrollkey)>0) {
?>
<span class=form><label for="ekey2"><?php echo _("Course Enrollment Key:"); ?></label></span><input class=form type=text size=12 name="ekey2" id="ekey2" <?php if (isset($_POST['ekey2'])) { printf('value="%s"', Sanitize::encodeStringForDisplay($_POST['ekey2'])); } ?>/><BR class=form>
<?php
	}
?>
<div class=submit><input type="submit" name="submit" value="Sign Up"></div>
</fieldset>
<?php
	}
?>
</form>

<?php
	$requiredrules = array(
		'username'=>'{depends: function(element) {return logintype==0}}',
		'password'=>'{depends: function(element) {return logintype==0}}',
		'ekey'=>'{depends: function(element) {return logintype==0}}',
		'SID'=>'{depends: function(element) {return logintype==1}}',
		'pw1'=>'{depends: function(element) {return logintype==1}}',
		'pw2'=>'{depends: function(element) {return logintype==1}}',
		'firstname'=>'{depends: function(element) {return logintype==1}}',
		'lastname'=>'{depends: function(element) {return logintype==1}}',
		'email'=>'{depends: function(element) {return logintype==1}}',
		'ekey2'=>'{depends: function(element) {return logintype==1}}'
	);
	showNewUserValidation('pageform', (strlen($enrollkey)>0)?array('ekey','ekey2'):array(), $requiredrules);

	require("footer.php");
	}

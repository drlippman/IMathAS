<?php
//IMathAS:  Course direct access - redirects to course page or presents
//login / new student page specific for course
//(c) 2007 David Lippman

	$curdir = rtrim(dirname(__FILE__), '/\\');
	 if (!file_exists("$curdir/config.php")) {
		 header('Location: ' . $GLOBALS['basesiteurl'] . "/install.php?r=" . Sanitize::randomQueryStringParam());
	 }
 	require_once(__DIR__ . "/init_without_validate.php");
	require_once(__DIR__ ."/includes/newusercommon.php");
	$cid = Sanitize::courseId($_GET['cid']);

 	if (!isset($_GET['cid'])) {
		echo "Invalid address.  Address must be directaccess.php?cid=###, where ### is your courseid";
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
		unset($_POST['username']);
		unset($_POST['password']);
		$page_newaccounterror = checkNewUserValidation();

		//DB $query = "SELECT enrollkey,deflatepass FROM imas_courses WHERE id = '$cid'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB list($enrollkey,$deflatepass) = mysql_fetch_row($result);
		$stm = $DBH->prepare("SELECT enrollkey,deflatepass FROM imas_courses WHERE id=:id");
		$stm->execute(array(':id'=>$_GET['cid']));
		list($enrollkey,$deflatepass) = $stm->fetch(PDO::FETCH_NUM);
		if (strlen($enrollkey)>0 && trim($_POST['ekey2'])=='') {
			$page_newaccounterror .= "Please provide the enrollment key";
		} else if (strlen($enrollkey)>0) {
			$keylist = array_map('trim',explode(';',$enrollkey));
			if (!in_array($_POST['ekey2'], $keylist)) {
				$page_newaccounterror .= "Enrollment key is invalid.";
			} else {
				$_POST['ekey'] = $_POST['ekey2'];
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
			//DB $query = "INSERT INTO imas_users (SID, password, rights, FirstName, LastName, email, msgnotify, homelayout) ";
			//DB $query .= "VALUES ('{$_POST['SID']}','$md5pw',$initialrights,'{$_POST['firstname']}','{$_POST['lastname']}','{$_POST['email']}',$msgnot,'$homelayout');";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $newuserid = mysql_insert_id();
			$query = "INSERT INTO imas_users (SID, password, rights, FirstName, LastName, email, msgnotify, homelayout) ";
			$query .= "VALUES (:SID, :password, :rights, :FirstName, :LastName, :email, :msgnotify, :homelayout)";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':SID'=>$_POST['SID'], ':password'=>$md5pw, ':rights'=>$initialrights, ':FirstName'=>$_POST['firstname'], ':LastName'=>$_POST['lastname'], ':email'=>$_POST['email'], ':msgnotify'=>$msgnot, ':homelayout'=>$homelayout));
			$newuserid = $DBH->lastInsertId();
			if (strlen($enrollkey)>0 && count($keylist)>1) {
				//DB $query = "INSERT INTO imas_students (userid,courseid,section,gbcomment,latepass) VALUES ('$userid','$cid','{$_POST['ekey2']}','$code','$deflatepass');";
				//DB mysql_query($query) or die("Query failed : " . mysql_error());
				$stm = $DBH->prepare("INSERT INTO imas_students (userid,courseid,section,gbcomment,latepass) VALUES (:userid, :courseid, :section, :gbcomment, :latepass)");
				$stm->execute(array(':userid'=>$newuserid, ':courseid'=>$cid, ':section'=>$_POST['ekey2'], ':gbcomment'=>$code, ':latepass'=>$deflatepass));
			} else {
				//DB $query = "INSERT INTO imas_students (userid,courseid,gbcomment,latepass) VALUES ('$newuserid','$cid','$code','$deflatepass');";
				//DB mysql_query($query) or die("Query failed : " . mysql_error());
				$stm = $DBH->prepare("INSERT INTO imas_students (userid,courseid,gbcomment,latepass) VALUES (:userid, :courseid, :gbcomment, :latepass)");
				$stm->execute(array(':userid'=>$newuserid, ':courseid'=>$cid, ':gbcomment'=>$code, ':latepass'=>$deflatepass));
			}

			if ($emailconfirmation) {
				//DB $id = mysql_insert_id();
				$id = $DBH->lastInsertId();
				$headers  = 'MIME-Version: 1.0' . "\r\n";
				$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
				$headers .= "From: $sendfrom\r\n";
				$message  = "<h3>This is an automated message from $installname.  Do not respond to this email</h3>\r\n";
				$message .= "<p>To complete your $installname registration, please click on the following link, or copy ";
				$message .= "and paste it into your webbrowser:</p>\r\n";
				$message .= "<a href=\"" . $GLOBALS['basesiteurl'] . "/actions.php?action=confirm&id=$id\">";
				$message .= $GLOBALS['basesiteurl'] . "/actions.php?action=confirm&id=$id</a>\r\n";
				mail(Sanitize::emailAddress($_POST['email']), $installname.' Confirmation',$message,$headers);
				echo "<html><body>\n";
				echo "Registration recorded.  You should shortly receive an email with confirmation instructions.";
				echo "<a href=\"$imasroot/directaccess.php?cid=$cid\">Back to login page</a>\n";
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
	if ($_POST['ekey']!='') {
		$addtoquerystring = "ekey=".Sanitize::encodeUrlParam($_POST['ekey']);
	}
	require("init.php");
	$flexwidth = true;
	if ($verified) { //already have session
		if (!isset($studentid) && !isset($teacherid) && !isset($tutorid)) {  //have account, not a student
			//DB $query = "SELECT name,enrollkey,deflatepass FROM imas_courses WHERE id='$cid'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB list($coursename,$enrollkey,$deflatepass) = mysql_fetch_row($result);
			$stm = $DBH->prepare("SELECT name,enrollkey,deflatepass FROM imas_courses WHERE id=:id");
			$stm->execute(array(':id'=>$_GET['cid']));
			list($coursename,$enrollkey,$deflatepass) = $stm->fetch(PDO::FETCH_NUM);
			$keylist = array_map('trim',explode(';',$enrollkey));
			if (strlen($enrollkey)==0 || (isset($_REQUEST['ekey']) && in_array($_REQUEST['ekey'], $keylist))) {
				if (count($keylist)>1) {
					//DB $query = "INSERT INTO imas_students (userid,courseid,section,latepass) VALUES ('$userid','$cid','{$_REQUEST['ekey']}','$deflatepass')";
					//DB mysql_query($query) or die("Query failed : " . mysql_error());
					$stm = $DBH->prepare("INSERT INTO imas_students (userid,courseid,section,latepass) VALUES (:userid, :courseid, :section, :latepass)");
					$stm->execute(array(':userid'=>$userid, ':courseid'=>$cid, ':section'=>$_REQUEST['ekey'], ':latepass'=>$deflatepass));
				} else {
					//DB $query = "INSERT INTO imas_students (userid,courseid,latepass) VALUES ('$userid','$cid','$deflatepass')";
					//DB mysql_query($query) or die("Query failed : " . mysql_error());
					$stm = $DBH->prepare("INSERT INTO imas_students (userid,courseid,latepass) VALUES (:userid, :courseid, :latepass)");
					$stm->execute(array(':userid'=>$userid, ':courseid'=>$cid, ':latepass'=>$deflatepass));
				}

				header('Location: ' . $GLOBALS['basesiteurl'] . '/course/course.php?cid='. $cid. '&r=' . Sanitize::randomQueryStringParam());
				exit;
			} else {
				require("header.php");
				echo "<h1>" . Sanitize::encodeStringForDisplay($coursename) . "</h1>";
				echo '<form method="post" action="directaccess.php?cid='.$cid.'">';
				echo '<p>Incorrect enrollment key.  Try again.</p>';
				echo "<p>Course Enrollment Key:  <input type=text name=\"ekey\"></p>";
				echo "<p><input type=\"submit\" value=\"Submit\"></p>";
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

		//DB $query = "SELECT name FROM imas_courses WHERE id='$cid'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $coursename = mysql_result($result,0,0);
		$stm = $DBH->prepare("SELECT name FROM imas_courses WHERE id=:id");
		$stm->execute(array(':id'=>$_GET['cid']));
		$coursename = $stm->fetchColumn(0);

		if (isset($CFG['GEN']['directaccessincludepath'])) {
			$placeinhead = "<link rel=\"stylesheet\" href=\"$imasroot/".$CFG['GEN']['directaccessincludepath']."infopages.css\" type=\"text/css\">\n";
		} else {
			$placeinhead = "<link rel=\"stylesheet\" href=\"$imasroot/infopages.css\" type=\"text/css\">\n";
		}
		//$placeinhead = "<style type=\"text/css\">div#header {clear: both;height: 75px;background-color: #9C6;margin: 0px;padding: 0px;border-left: 10px solid #036;border-bottom: 5px solid #036;} \n.vcenter {font-family: sans-serif;font-size: 28px;margin: 0px;margin-left: 30px;padding-top: 25px;color: #fff;}</style>";
		$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/jstz_min.js\" ></script>";
		$pagetitle = $coursename;
		 if (isset($_SESSION['challenge'])) {
			 $challenge = $_SESSION['challenge'];
		 } else {
			 $challenge = base64_encode(microtime() . rand(0,9999));
			 $_SESSION['challenge'] = $challenge;
		 }
		$placeinhead .= '<script type="text/javascript" src="'.$imasroot.'/javascript/jquery.validate.min.js?v=122917"></script>';
		if (isset($CFG['locale'])) {
			$placeinhead .= '<script type="text/javascript" src="'.$imasroot.'/javascript/jqvalidatei18n/messages_'.$CFG['locale'].'.min.js"></script>';
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

		//DB $query = "SELECT enrollkey FROM imas_courses WHERE id='$cid'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $enrollkey = mysql_result($result,0,0);
		$stm = $DBH->prepare("SELECT enrollkey FROM imas_courses WHERE id=:id");
		$stm->execute(array(':id'=>$cid));
		$enrollkey = $stm->fetchColumn(0);

?>
<form id="pageform" class=limitaftervalidate method="post" action="directaccess.php<?php echo $querys; ?>">

<?php
if ($enrollkey!='closed') {
?>
<h2>Do you already have an account on <?php echo $installname;?>?</h2>
<p>
<input type=radio name="curornew" value="0" onclick="setlogintype(0)" <?php if ($page_newaccounterror=='') {echo 'checked';}?> /> I already have an account on <?php echo $installname;?><br/>
<input type=radio name="curornew" value="1" onclick="setlogintype(1)" <?php if ($page_newaccounterror!='') {echo 'checked';}?> /> I need to create a new account
</p>
<?php
}
?>

<fieldset id="curuser" <?php if ($page_newaccounterror!='') {echo 'style="display:none"';}?>>
<legend>Already have an account</legend>
<p><b>Login</b>.  If you have an account on <?php echo $installname;?> but are not enrolled in this course, logging in below will enroll you in this course.</p>
<?php
	if ($haslogin) {echo '<p style="color: red;">Login Error.  Try Again</p>';}
?>
<span class=form><?php echo $loginprompt;?>:</span><input class="form" type="text" size="15" id="username" name="username"><br class="form">
<span class=form>Password:</span><input class="form" type="password" size="15" id="password" name="password"><br class="form">
<?php
if (strlen($enrollkey)>0) {
	echo '<span class=form><label for="ekey">Course Enrollment Key:</label></span><input class=form type=text size=12 name="ekey" id="ekey" value="' . (isset($_REQUEST['ekey']) ? Sanitize::encodeStringForDisplay($_REQUEST['ekey']) : "") . '"/><BR class=form>';
}
?>
<div class=submit><input type="submit" value="Login and Enroll"></div>
<span class=form> </span><span class=formright><a href="<?php echo $imasroot; ?>/forms.php?action=resetpw">Forgot Password</a></span><br class="form">
</table>
<div><noscript>JavaScript is not enabled.  JavaScript is required for <?php echo $installname; ?>.  Please enable JavaScript and reload this page</noscript></div>

<input type="hidden" id="tzoffset" name="tzoffset" value="">
<input type="hidden" id="tzname" name="tzname" value="">
<input type="hidden" id="challenge" name="challenge" value="<?php echo $challenge; ?>" />
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

<p><b>New Student Enrollment</b></p>
<?php
if ($page_newaccounterror!='') {
	echo '<p class=noticetext>' . Sanitize::encodeStringForDisplay($page_newaccounterror) . '</p>';
}
?>
<span class=form><label for="SID"><?php echo $longloginprompt;?>:</label></span> <input class=form type=text size=12 id=SID name=SID <?php if (isset($_POST['SID'])) { printf('value="%s"', Sanitize::encodeStringForDisplay($_POST['SID'])); } ?>><BR class=form>
<span class=form><label for="pw1">Choose a password:</label></span><input class=form type=password size=20 id=pw1 name=pw1 <?php if (isset($_POST['pw1'])) { printf('value="%s"', Sanitize::encodeStringForDisplay($_POST['pw1'])); } ?>><BR class=form>
<span class=form><label for="pw2">Confirm password:</label></span> <input class=form type=password size=20 id=pw2 name=pw2 <?php if (isset($_POST['pw2'])) { printf('value="%s"', Sanitize::encodeStringForDisplay($_POST['pw2'])); } ?>><BR class=form>
<span class=form><label for="firstname">Enter First Name:</label></span> <input class=form type=text size=20 id=firstname name=firstname <?php if (isset($_POST['firstname'])) { printf('value="%s"', Sanitize::encodeStringForDisplay($_POST['firstname'])); } ?>><BR class=form>
<span class=form><label for="lastname">Enter Last Name:</label></span> <input class=form type=text size=20 id=lastname name=lastname <?php if (isset($_POST['lastname'])) { printf('value="%s"', Sanitize::encodeStringForDisplay($_POST['lastname'])); } ?>><BR class=form>
<span class=form><label for="email">Enter E-mail address:</label></span>  <input class=form type=text size=60 id=email name=email <?php if (isset($_POST['email'])) { printf('value="%s"', Sanitize::encodeStringForDisplay($_POST['email'])); } ?>><BR class=form>
<?php
if (isset($_GET['getsid'])) {
	echo '<span class="form"><label for="code">If you are registered at a Washington Community College, enter your Student ID Number:</label></span><input class="form" type="text" size="20" id="code" name="code"><BR class=form>';
}
?>
<span class=form><label for="msgnot">Notify me by email when I receive a new message:</label></span><span class=formright><input type=checkbox id=msgnot name=msgnot /></span><BR class=form>
<?php
	if (strlen($enrollkey)>0) {
?>
<span class=form><label for="ekey">Course Enrollment Key:</label></span><input class=form type=text size=12 name="ekey2" id="ekey2" <?php if (isset($_POST['ekey2'])) { printf('value="%s"', Sanitize::encodeStringForDisplay($_POST['ekey2'])); } ?>/><BR class=form>
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



?>

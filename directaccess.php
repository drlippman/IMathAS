<?php
//IMathAS:  Course direct access - redirects to course page or presents 
//login / new student page specific for course
//(c) 2007 David Lippman
	$curdir = rtrim(dirname(__FILE__), '/\\');
	 if (!file_exists("$curdir/config.php")) {
		 header('Location: http://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/install.php");
	 }
 	require_once("$curdir/config.php");
 
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
		 $querys = '?'.$_SERVER['QUERY_STRING'];
	 } else {
		 $querys = '';
	 }
	 $page_newaccounterror = '';
	 if (isset($_POST['submit']) && $_POST['submit']=="Sign Up") {
		unset($_POST['username']);
		unset($_POST['password']);
		require_once("config.php");
		if ($_POST['SID']=="" || $_POST['firstname']=="" || $_POST['lastname']=="" || $_POST['email']=="" || $_POST['pw1']=="") {
			$page_newaccounterror .= "Please include all information. ";
		} 
		if ($loginformat!='' && !preg_match($loginformat,$_POST['SID'])) {
			$page_newaccounterror .= "$loginprompt is invalid. ";
		} else {
			$query = "SELECT id FROM imas_users WHERE SID='{$_POST['SID']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_num_rows($result)>0) {
				$page_newaccounterror .= "$loginprompt '{$_POST['SID']}' is already used. ";
			} 
		}
		if (!preg_match('/^[a-zA-Z][\w\.-]*[a-zA-Z0-9]@[a-zA-Z0-9][\w\.-]*[a-zA-Z0-9]\.[a-zA-Z][a-zA-Z\.]*[a-zA-Z]$/',$_POST['email'])) {
			$page_newaccounterror .= "Invalid email address. ";
		} 
		if ($_POST['pw1'] != $_POST['pw2']) {	
			$page_newaccounterror .= "Passwords don't match. ";
		} 
		
		$query = "SELECT enrollkey,deflatepass FROM imas_courses WHERE id = '{$_GET['cid']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		list($enrollkey,$deflatepass) = mysql_fetch_row($result);
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
			$query = "INSERT INTO imas_users (SID, password, rights, FirstName, LastName, email, msgnotify, homelayout) ";
			$query .= "VALUES ('{$_POST['SID']}','$md5pw',$initialrights,'{$_POST['firstname']}','{$_POST['lastname']}','{$_POST['email']}',$msgnot,'$homelayout');";
			mysql_query($query) or die("Query failed : " . mysql_error());
			$newuserid = mysql_insert_id();
			$query = "INSERT INTO imas_students (userid,courseid,gbcomment,latepass) VALUES ('$newuserid','{$_GET['cid']}','$code','$deflatepass');";
			mysql_query($query) or die("Query failed : " . mysql_error());
			if ($emailconfirmation) {
				$id = mysql_insert_id();
				$headers  = 'MIME-Version: 1.0' . "\r\n";
				$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
				$headers .= "From: $sendfrom\r\n";
				$message  = "<h4>This is an automated message from $installname.  Do not respond to this email</h4>\r\n";
				$message .= "<p>To complete your $installname registration, please click on the following link, or copy ";
				$message .= "and paste it into your webbrowser:</p>\r\n";
				$message .= "<a href=\"". $urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/actions.php?action=confirm&id=$id\">";
				$message .= $urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/actions.php?action=confirm&id=$id</a>\r\n";
				mail($_POST['email'],'IMathAS Confirmation',$message,$headers);
				echo "<html><body>\n";
				echo "Registration recorded.  You should shortly receive an email with confirmation instructions.";
				echo "<a href=\"$imasroot/directaccess.php?cid={$_GET['cid']}\">Back to login page</a>\n";
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
		$addtoquerystring = "ekey=".urlencode($_POST['ekey']);
	}
	require("validate.php");
	
	if ($verified) { //already have session
		if (!isset($studentid) && !isset($teacherid) && !isset($tutorid)) {  //have account, not a student
			$query = "SELECT name,enrollkey,deflatepass FROM imas_courses WHERE id='{$_GET['cid']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			list($coursename,$enrollkey,$deflatepass) = mysql_fetch_row($result);
			$keylist = array_map('trim',explode(';',$enrollkey));
			if (strlen($enrollkey)==0 || (isset($_REQUEST['ekey']) && in_array($_REQUEST['ekey'], $keylist))) {
				if (count($keylist)>1) {
					$query = "INSERT INTO imas_students (userid,courseid,section,latepass) VALUES ('$userid','{$_GET['cid']}','{$_REQUEST['ekey']}','$deflatepass');";		
				} else {
					$query = "INSERT INTO imas_students (userid,courseid,latepass) VALUES ('$userid','{$_GET['cid']}','$deflatepass');";
				}
				mysql_query($query) or die("Query failed : " . mysql_error());
				header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . $imasroot . '/course/course.php?cid='. $_GET['cid']);
				exit;
			} else {
				require("header.php");
				echo "<h2>$coursename</h2>";
				echo '<form method="post" action="directaccess.php?cid='.$_GET['cid'].'">';
				echo '<p>Incorrect enrollment key.  Try again.</p>';
				echo "<p>Course Enrollment Key:  <input type=text name=\"ekey\"></p>";
				echo "<p><input type=\"submit\" value=\"Submit\"></p>";
				echo "</form>";
				require("footer.php");
				exit;
			}
		} else {
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . $imasroot . '/course/course.php?cid='. $_GET['cid']);
			exit;
		}
	} else { //not verified
		//$placeinhead = "<link rel=\"stylesheet\" href=\"$imasroot/infopages.css\" type=\"text/css\" />\n";
		$pref = 0;
		 if (isset($_COOKIE['mathgraphprefs'])) {
			 $prefparts = explode('-',$_COOKIE['mathgraphprefs']);
			 if ($prefparts[0]==2 && $prefparts[1]==2) { //img all
				$pref = 3;	 
			 } else if ($prefparts[0]==2) { //img math
				 $pref = 4;
			 } else if ($prefparts[1]==2) { //img graph
				 $pref = 2;
			 }
				 
		 }
	 
		$query = "SELECT name FROM imas_courses WHERE id='{$_GET['cid']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$coursename = mysql_result($result,0,0);
		
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
		require("header.php");
		//echo "<div class=\"breadcrumb\">$breadcrumbbase $coursename Access</div>";
		echo "<div id=\"header\"><div class=\"vcenter\">$coursename</div></div>";
		//echo '<span style="float:right;margin-top:10px;">'.$smallheaderlogo.'</span>';
		
		$cid = intval($_GET['cid']);
		$curdir = rtrim(dirname(__FILE__), '/\\');
		if (file_exists("$curdir/".(isset($CFG['GEN']['directaccessincludepath'])?$CFG['GEN']['directaccessincludepath']:'')."directaccess$cid.html")) {
			require("$curdir/".(isset($CFG['GEN']['directaccessincludepath'])?$CFG['GEN']['directaccessincludepath']:'')."directaccess$cid.html");
		} 
		
		$query = "SELECT enrollkey FROM imas_courses WHERE id='{$_GET['cid']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$enrollkey = mysql_result($result,0,0);
		
?>
<form method="post" action="<?php echo $_SERVER['PHP_SELF'].$querys;?>">

<h3 style="color:#036;">Already have an account?</h3>
<p><b>Login</b>.  If you have an account on <?php echo $installname;?> but are not enrolled in this course, you will be able to enroll in this course.</p>
<?php
	if ($haslogin) {echo '<p style="color: red;">Login Error.  Try Again</p>';}
?>
<span class=form><?php echo $loginprompt;?>:</span><input class="form" type="text" size="15" id="username" name="username"><br class="form">
<span class=form>Password:</span><input class="form" type="password" size="15" id="password" name="password"><br class="form">
<?php
if (strlen($enrollkey)>0) {
	echo '<span class=form><label for="ekey">Course Enrollment Key:</label></span><input class=form type=text size=12 name="ekey" id="ekey" value="'.(isset($_POST['ekey'])?$_POST['ekey']:"").'"/><BR class=form>';
}
?>
<span class=form> </span><span class=formright><a href="<?php echo $imasroot; ?>/forms.php?action=resetpw">Forgot Password</a></span><br class="form">
</table>
<div id="settings">JavaScript is not enabled.  JavaScript is required for <?php echo $installname; ?>.  Please enable JavaScript and reload this page</div>

<input type="hidden" id="tzoffset" name="tzoffset" value=""> 
<input type="hidden" id="tzname" name="tzname" value=""> 
<input type="hidden" id="challenge" name="challenge" value="<?php echo $challenge; ?>" />
<script type="text/javascript">        
        var thedate = new Date();  
        document.getElementById("tzoffset").value = thedate.getTimezoneOffset();  
        var tz = jstz.determine(); 
        document.getElementById("tzname").value = tz.name();
</script> 


<script type="text/javascript"> 
	function updateloginarea() {
		setnode = document.getElementById("settings"); 
		var html = ""; 
		html += '<span class=form>Accessibility:</span><span class=formright> ';
		//html += "<a href='#' onClick=\"window.open('<?php echo $imasroot;?>/help.php?section=loggingin','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))\">Help</a>";
		html += '<input type="radio" name="access" value="0" <?php if ($pref==0) {echo "checked=1";} ?> />Use visual display<br/>';
		html += '<input type="radio" name="access" value="2" <?php if ($pref==2) {echo "checked=1";} ?> />Force image-based graphs<br/>';
		html += '<input type="radio" name="access" value="4" <?php if ($pref==4) {echo "checked=1";} ?> />Force image-based math<br/>';
		html += '<input type="radio" name="access" value="3" <?php if ($pref==3) {echo "checked=1";} ?> />Force image based display<br/>';
		html += '<input type="radio" name="access" value="1">Use text-based display</span><br class=form>';
		
		if (AMnoMathML) {
			html += '<input type=hidden name="mathdisp" value="0">';
		} else {
			html += '<input type=hidden name="mathdisp" value="1">';
		}
		if (ASnoSVG) {
			html += '<input type=hidden name="graphdisp" value="2">';
		} else {
			html += '<input type=hidden name="graphdisp" value="1">';
		}
		if (!AMnoMathML && !ASnoSVG) {
			html += '<input type=hidden name="isok" value=1>';
		} 
		html += '<div class=submit><input name="submit" type="submit" value="Login"></div>';
		setnode.innerHTML = html; 
<?php
	if ($page_newaccounterror!='') {
		echo 'document.getElementById("SID").focus();';
	} else {
		echo 'document.getElementById("username").focus();';
	}
?>
	}
	var existingonload = window.onload;
	if (existingonload) {
		window.onload = function() {existingonload(); updateloginarea();}
	} else {
		window.onload = updateloginarea;
	}
</script>
<?php
	if ($enrollkey!='closed') {
?>
<script type="text/javascript">
function enablenewstu() {
	document.getElementById("newstubutton").style.display = "none";
	document.getElementById("newstu").style.display = "block";
}
</script>
<h3 style="color:#036;">New to <?php echo $installname; ?>?</h3>

<div id="newstubutton" style="display:<?php echo ($page_newaccounterror!=''?"none":"block");?>;" >
 <input type=button onclick="enablenewstu()" value="Create an account and Enroll">
</div>
<div id="newstu" style="display:<?php echo ($page_newaccounterror!=''?"block":"none");?>;">

<p><b>New Student Enrollment</b></p>
<?php 
if ($page_newaccounterror!='') {
	echo '<p style="color:red;">'.$page_newaccounterror.'</p>';
}
?>
<script type="text/javascript" src="<?php echo $imasroot;?>/javascript/validateform.js"></script>
<span class=form><label for="SID"><?php echo $longloginprompt;?>:</label></span> <input class=form type=text size=12 id=SID name=SID <?php if (isset($_POST['SID'])) {echo "value=\"{$_POST['SID']}\"";}?>><BR class=form>
<span class=form><label for="pw1">Choose a password:</label></span><input class=form type=password size=20 id=pw1 name=pw1><BR class=form>
<span class=form><label for="pw2">Confirm password:</label></span> <input class=form type=password size=20 id=pw2 name=pw2><BR class=form>
<span class=form><label for="firstname">Enter First Name:</label></span> <input class=form type=text size=20 id=firstname name=firstname <?php if (isset($_POST['firstname'])) {echo "value=\"{$_POST['firstname']}\"";}?>><BR class=form>
<span class=form><label for="lastname">Enter Last Name:</label></span> <input class=form type=text size=20 id=lastname name=lastname <?php if (isset($_POST['lastname'])) {echo "value=\"{$_POST['lastname']}\"";}?>><BR class=form>
<span class=form><label for="email">Enter E-mail address:</label></span>  <input class=form type=text size=60 id=email name=email <?php if (isset($_POST['email'])) {echo "value=\"{$_POST['email']}\"";}?>><BR class=form>
<?php
if (isset($_GET['getsid'])) {
	echo '<span class="form"><label for="code">If you are registered at a Washington Community College, enter your Student ID Number:</label></span><input class="form" type="text" size="20" id="code" name="code"><BR class=form>';
}
?>
<span class=form><label for="msgnot">Notify me by email when I receive a new message:</label></span><span class=formright><input type=checkbox id=msgnot name=msgnot /></span><BR class=form>
<?php
	if (strlen($enrollkey)>0) {
?>
<span class=form><label for="ekey">Course Enrollment Key:</label></span><input class=form type=text size=12 name="ekey2" id="ekey2" <?php if (isset($_POST['ekey2'])) {echo "value=\"{$_POST['ekey2']}\"";}?>/><BR class=form>
<?php
	}
?>
<div class=submit><input type="submit" name="submit" value="Sign Up"></div>
</div>
<?php
	}
?>
</form>

<?php
	require("footer.php");
	}
	
	

?>

<?php
if (!isset($imasroot)) { //don't allow direct access to loginpage.php
	header("Location: index.php");
	exit;
}
//any extra CSS, javascript, etc needed for login page
	$placeinhead = "<link rel=\"stylesheet\" href=\"$imasroot/infopages.css\" type=\"text/css\" />\n";
	$placeinhead .= "<script type=\"text/javascript\" src=\"$imasroot/javascript/jstz_min.js\" ></script>";
	$nologo = true;
	require("header.php");
	if (!empty($_SERVER['QUERY_STRING'])) {
		 $querys = '?'.$_SERVER['QUERY_STRING'];
	 } else {
		 $querys = '';
	 }
	 $loginFormAction = $GLOBALS['basesiteurl'] . substr($_SERVER['SCRIPT_NAME'],strlen($imasroot)) . Sanitize::encodeStringForDisplay($querys);
	 if (!empty($_SESSION['challenge'])) {
		 $challenge = $_SESSION['challenge'];
	 } else {
		 //use of microtime guarantees no challenge used twice
		 $challenge = base64_encode(microtime() . rand(0,9999));
		 $_SESSION['challenge'] = $challenge;
	 }
	 $pagetitle = "About Us";
	 include("infoheader.php");
	 
?>
	


<div id="loginbox">
<form method="post" action="<?php echo $loginFormAction;?>">
<?php
	if ($haslogin) {
		if ($badsession) {
			if (isset($_COOKIE[session_name()])) {
				echo 'Problems with session storage';
			}  else {
				echo '<p>Unable to establish a session.  Check that your browser is set to allow session cookies</p>';
			}
		} else if (isset($line['password']) && substr($line['password'],0,8)=='cleared_') {
			echo '<p>Your password has expired since your account has been unused. Use the Reset Password link below to reset your password.</p>';
		} else {
			echo "<p>Login Error.  Try Again</p>\n";
		}
	}
?>
<b>Login</b>

<div><noscript>JavaScript is not enabled.  JavaScript is required for <?php echo $installname; ?>.  Please enable JavaScript and reload this page</noscript></div>

<table>
<tr><td><label for="username"><?php echo $loginprompt;?></label>:</td><td><input type="text" size="15" id="username" name="username" /></td></tr>
<tr><td><label for="password">Password</label>:</td><td><input type="password" size="15" id="password" name="password" /></td></tr>
</table>
<div class=textright><input type="submit" value="Login"></div>

<div class="textright"><a href="<?php echo $imasroot; ?>/forms.php?action=newuser">Register as a new student</a></div>
<div class="textright"><a href="<?php echo $imasroot; ?>/forms.php?action=resetpw">Forgot Password</a><br/>
<a href="<?php echo $imasroot; ?>/forms.php?action=lookupusername">Forgot Username</a></div>

<input type="hidden" id="tzoffset" name="tzoffset" value=""> 
<input type="hidden" id="tzname" name="tzname" value=""> 
<input type="hidden" id="challenge" name="challenge" value="<?php echo $challenge; ?>" />
<script type="text/javascript">     
$(function() {
        var thedate = new Date();  
        document.getElementById("tzoffset").value = thedate.getTimezoneOffset();
        var tz = jstz.determine(); 
        document.getElementById("tzname").value = tz.name();
        $("#username").focus();
});
</script>  

</form>
</div>
<div class="text">
<p><?php echo $installname; ?> is a web based mathematics assessment and course management platform.  </p>
 <img style="float: left; margin-right: 20px;" src="<?php echo $imasroot; ?>/img/screens.jpg" alt="Computer screens"/>

<p>This system is designed for mathematics, providing delivery of homework, quizzes, tests, practice tests,
and diagnostics with rich mathematical content.  Students can receive immediate feedback on algorithmically generated questions with
numerical or algebraic expression answers.
</p>

<p>If you already have an account, you can log on using the box to the right.</p>
<p>If you are a new student to the system, <a href="<?php echo $imasroot; ?>/forms.php?action=newuser">Register as a new student</a></p>
<p>If you are an instructor, you can <a href="<?php echo $imasroot;?>/newinstructor.php">request an account</a></p>

<p>Also available:
<ul>
<li><a href="<?php echo $imasroot;?>/info/enteringanswers.php">Help for student with entering answers</a></li>
<li><a href="<?php echo $imasroot;?>/docs/docs.php">Instructor Documentation</a></li>
</ul>

<br class=clear>
<p class="textright"><?php echo $installname;?> is powered by <a href="http://www.imathas.com">IMathAS</a> &copy; 2006-<?php echo date("Y");?> David Lippman</p>
</div>
<?php 
	require("footer.php");
?>

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
	if (isset($_SERVER['QUERY_STRING'])) {
		 $querys = '?'.$_SERVER['QUERY_STRING'];
	 } else {
		 $querys = '';
	 }
	 if (!empty($_SESSION['challenge'])) {
		 $challenge = $_SESSION['challenge'];
	 } else {
		 //use of microtime guarantees no challenge used twice
		 $challenge = base64_encode(microtime() . rand(0,9999));
		 $_SESSION['challenge'] = $challenge;
	 }
	 $pagetitle = "About Us";
	 include("infoheader.php");
	 
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
?>
	


<div id="loginbox">
<form method="post" action="<?php echo $_SERVER['PHP_SELF'].$querys;?>">
<?php
	if ($haslogin) {
		if ($badsession) {
			if (isset($_COOKIE[session_name()])) {
				echo 'Problems with session storage';
			}  else {
				echo '<p>Unable to establish a session.  Check that your browser is set to allow session cookies</p>';
			}
		} else {
			echo "<p>Login Error.  Try Again</p>\n";
		}
	}
?>
<b>Login</b>
<table>
<tr><td><label for="username"><?php echo $loginprompt;?></label>:</td><td><input type="text" size="15" id="username" name="username" /></td></tr>
<tr><td><label for="password">Password</label>:</td><td><input type="password" size="15" id="password" name="password" /></td></tr>
</table>
<div id="settings">JavaScript is not enabled.  JavaScript is required for <?php echo $installname; ?>.  Please enable JavaScript and reload this page</div>
<div class="textright"><a href="<?php echo $imasroot; ?>/forms.php?action=newuser">Register as a new student</a></div>
<div class="textright"><a href="<?php echo $imasroot; ?>/forms.php?action=resetpw">Forgot Password</a><br/>
<a href="<?php echo $imasroot; ?>/forms.php?action=lookupusername">Forgot Username</a></div>
<div class="textright"><a href="<?php echo $imasroot; ?>/checkbrowser.php">Browser check</a></div>
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
		html += 'Accessibility: ';
		html += "<a href='#' onClick=\"window.open('<?php echo $imasroot;?>/help.php?section=loggingin','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))\">Help</a>";
		html += '<div style="margin-top: 0px;margin-right:0px;text-align:right;padding:0px"><select name="access"><option value="0">Use defaults</option>';
		html += '<option value="3">Force image-based display</option>';
		html += '<option value="1">Use text-based display</option></select></div>';
		
		if (!MathJaxCompatible) {
			html += '<input type=hidden name="mathdisp" value="0">';
		} else {
			html += '<input type=hidden name="mathdisp" value="1">';
		}
		if (ASnoSVG) {
			html += '<input type=hidden name="graphdisp" value="2">';
		} else {
			html += '<input type=hidden name="graphdisp" value="1">';
		}
		if (MathJaxCompatible && !ASnoSVG) {
			html += '<input type=hidden name="isok" value=1>';
		} 
		html += '<div class=textright><input type="submit" value="Login"></div>';
		setnode.innerHTML = html; 
		document.getElementById("username").focus();
	}
	var existingonload = window.onload;
	if (existingonload) {
		window.onload = function() {existingonload(); updateloginarea();}
	} else {
		window.onload = updateloginarea;
	}
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
<p class="textright"><?php echo $installname;?> is powered by <a href="http://www.imathas.com">IMathAS</a> &copy; 2006-2013 David Lippman</p>
</div>
<?php 
	require("footer.php");
?>

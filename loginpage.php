<?php
if (!isset($imasroot)) {
	header("Location: index.php");
	exit;
}
//any extra CSS, javascript, etc needed for login page
	$placeinhead = "<link rel=\"stylesheet\" href=\"$imasroot/wamaphome.css\" type=\"text/css\" />\n";
	$nologo = true;
	require("header.php");
	if (isset($_SERVER['QUERY_STRING'])) {
		 $querys = '?'.$_SERVER['QUERY_STRING'];
	 } else {
		 $querys = '';
	 }
?>
	
<div id="logo">
<img src="<?php echo $imasroot; ?>/img/wamaptxt.gif" alt="WAMAP.org: Washington Mathematics Assessment and Placement"/>
</div>

<ul id="navlist">
<li><a href="<?php echo $imasroot; ?>/index.php">About Us</a></li>
<li><a href="<?php echo $imasroot; ?>/info/classroom.html">Classroom</a></li>
<li><a href="<?php echo $imasroot; ?>/diag/index.php">Diagnostics</a></li>
<li><a href="<?php echo $imasroot; ?>/info/news.html">News</a></li>
</ul>

<div id="header">
<img class="floatright" src="<?php echo $imasroot; ?>/img/graph.gif" alt="graph image" />
<div class="vcenter">About Us</div>
</div>

<div id="loginbox">
<form method="post" action="<?php echo $_SERVER['PHP_SELF'].$querys;?>">
<?php
	if ($haslogin) {echo "<p>Login Error.  Try Again</p>\n";}
?>
<b>Login</b>
<table>
<tr><td><?php echo $loginprompt;?>:</td><td><input type="text" size="15" id="username" name="username"></td></tr>
<tr><td>Password:</td><td><input type="password" size="15" id="password" name="password"></td></tr>
</table>
<div id="settings">JavaScript is not enabled.  JavaScript is required for IMathAS.  Please enable JavaScript and reload this page</div>
<div class="textright"><a href="/forms.php?action=newuser">Register as a new student</a></div>
<div class="textright"><a href="/forms.php?action=resetpw">Forgot Password</a></div>
<div class="textright"><a href="/checkbrowser.php">Browser check</a></div>
<input type="hidden" id="tzoffset" name="tzoffset" value=""> 
<script type="text/javascript">        
        var thedate = new Date();  
        document.getElementById("tzoffset").value = thedate.getTimezoneOffset();  
</script> 


<script type="text/javascript"> 
        setnode = document.getElementById("settings"); 
        var html = ""; 
        html += 'Accessibility: ';
	html += "<a href='#' onClick=\"window.open('<?php echo $imasroot;?>/help.php?section=loggingin','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))\">Help</a>";
	html += '<br/><input type="radio" name="access" value="0" checked=1>Use visual display<br/>';
	html += '<input type="radio" name="access" value="2">Force image-based graphs<br/><input type="radio" name="access" value="3">Force image based display<br/>';
	html += '<input type="radio" name="access" value="1">Use text-based display';
	
	if (AMnoMathML) {
		html += '<input type=hidden name="mathdisp" value="0">';
	} else {
		html += '<input type=hidden name="mathdisp" value="1">';
	}
	if (!AMnoMathML && !ASnoSVG) {
		html += '<input type=hidden name="isok" value=1>';
	} 
	html += '<div class=textright><input type="submit" value="Login"></div>';
        setnode.innerHTML = html; 
	document.getElementById("username").focus();
</script>
</form>
</div>
<div class="text">
<p>WAMAP is a web based mathematics assessment and course management platform.  Its use is provided free to Washington State public 
educational institution students and instructors.
 </p>
 <img style="float: left; margin-right: 20px;" src="<?php echo $imasroot; ?>/img/screens.jpg" alt="Computer screens"/>

<p>This system is designed for mathematics, providing delivery of homework, quizzes, tests, practice tests,
and diagnostics with rich mathematical content.  Students can receive immediate feedback on algorithmically generated questions with
numerical or algebraic expression answers.
</p>

<p>If you already have an account, you can log on using the box to the right.</p>
<p>If you are new to WAMAP, use the links above to find information about using WAMAP in the classroom, or to access diagnostic assessments.</p>
<br class=clear>
<p class="textright">WAMAP is powered by <a href="http://www.imathas.com">IMathAS</a> &copy; 2006 David Lippman<br/> 
Web hosting sponsored by the <a href="http://www.transitionmathproject.org">Transition Math Project</a><br/>
<a href="/info/credits.html">Credits</a></p>
</div>
<?php 
	require("footer.php");
?>

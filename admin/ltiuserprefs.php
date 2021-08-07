<?php
//IMathAS:  User preference editing for LTI users

require('../init.php');
require('../includes/userprefs.php');
$action = "ltiuserprefs.php?cid=".Sanitize::courseId($cid);
if (isset($_GET['greybox'])) {
	$flexwidth = true; 
	$nologo = true;
	$action .= "&greybox=true";
	$greybox = true;
} else {
	$greybox = false;
}
$placeinhead = "<script type=\"text/javascript\" src=\"$staticroot/javascript/jstz_min.js\" ></script>";
require('../header.php');

if (isset($_POST['mathdisp'])) {
	storeUserPrefs();
	echo '<p>'._('Preferences saved. Your new preferences will go into effect when you visit a new page or reload the current page.').'</p>';
	if ($greybox) {
		echo '<input type="button" onclick="parent.GB_hide()" value="'._('Done').'" />';
	}
} else {
	echo '<div id="headerforms" class="pagetitle"><h1>'._('User Preferences').'</h1></div>';
	echo "<form id=\"pageform\" enctype=\"multipart/form-data\" method=post action=\"$action\">\n";
	showUserPrefsForm();
	echo "<div class=submit><input type=submit value='Update Info'></div>\n";
	echo "</form>\n";
    echo '<script>function doSubmit() { document.getElementById("pageform").submit(); parent.$("#GB_footer button.primary").hide();}</script>';
}

require('../footer.php');
?>

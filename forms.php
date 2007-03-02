<?php
//IMathAS:  Basic forms
//(c) 2006 David Lippman
require("config.php");
if ($_GET['action']!="newuser" && $_GET['action']!="resetpw") {
	require("validate.php");
}
require("header.php");	
echo "<div class=breadcrumb><a href=\"index.php\">Home</a> &gt; Form</div>\n";
switch($_GET['action']) {
	case "newuser":
		echo "<h3>New User Signup</h3>\n";
		echo "<script type=\"text/javascript\" src=\"$imasroot/javascript/validateform.js\"></script>\n";
		echo "<form method=post action=\"actions.php?action=newuser\" onsubmit=\"return validateForm(this)\">\n";
		echo "<span class=form><label for=\"SID\">$longloginprompt:</label></span> <input class=form type=text size=12 id=SID name=SID><BR class=form>\n";
		echo "<span class=form><label for=\"pw1\">Choose a password:</label></span><input class=form type=password size=20 id=pw1 name=pw1><BR class=form>\n";
		echo "<span class=form><label for=\"pw2\">Confirm password:</label></span> <input class=form type=password size=20 id=pw2 name=pw2><BR class=form>\n";
		echo "<span class=form><label for=\"firstname\">Enter First Name:</label></span> <input class=form type=text size=20 id=firstnam name=firstname><BR class=form>\n";
		echo "<span class=form><label for=\"lastname\">Enter Last Name:</label></span> <input class=form type=text size=20 id=lastname name=lastname><BR class=form>\n";
		echo "<span class=form><label for=\"email\">Enter E-mail address:</label></span>  <input class=form type=text size=60 id=email name=email><BR class=form>\n";
		echo "<span class=form><label for=\"msgnot\">Notify me by email when I receive a new message:</label></span><span class=formright><input type=checkbox id=msgnot name=msgnot /></span><BR class=form>\n";
		echo "<div class=submit><input type=submit value='Sign Up'></div>\n";
		echo "</form>\n";
		break;
	case "chgpwd":
		echo "<h3>Change Your Password</h3>\n";
		echo "<form method=post action=\"actions.php?action=chgpwd\">\n";
		echo "<span class=form><label for=\"oldpw\">Enter old password:</label></span> <input class=form type=password id=oldpw name=oldpw size=40 /> <BR class=form>\n";
		echo "<span class=form><label for=\"newpw1\">Enter new password:</label></span>  <input class=form type=password id=newpw1 name=newpw1 size=40> <BR class=form>\n";
		echo "<span class=form><label for=\"newpw1\">Verify new password:</label></span>  <input class=form type=password id=newpw2 name=newpw2 size=40> <BR class=form>\n";
		echo "<div class=submit><input type=submit value=Submit></div></form>\n";
		break;
	case "chguserinfo":
		$query = "SELECT * FROM imas_users WHERE id='$userid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$line = mysql_fetch_array($result, MYSQL_ASSOC);
		echo "<h3>User Info</h3>\n";
		echo "<form method=post action=\"actions.php?action=chguserinfo\">\n";
		echo "<span class=form><label for=\"firstname\">Enter First Name:</label></span> <input class=form type=text size=20 id=firstname name=firstname value=\"{$line['FirstName']}\"><BR class=form>\n";
		echo "<span class=form><label for=\"lastname\">Enter Last Name:</label></span> <input class=form type=text size=20 id=lastname name=lastname value=\"{$line['LastName']}\"><BR class=form>\n";
		echo "<span class=form><label for=\"email\">Enter E-mail address:</label></span>  <input class=form type=text size=60 id=email name=email value=\"{$line['email']}\"><BR class=form>\n";
		echo "<span class=form><label for=\"msgnot\">Notify me by email when I receive a new message:</label></span><span class=formright><input type=checkbox id=msgnot name=msgnot ";
		if ($line['msgnotify']==1) {echo "checked=1";}
		echo " /></span><BR class=form>\n";
		echo "<div class=submit><input type=submit value='Update Info'></div>\n";
		echo "</form>\n";
		break;
	case "unenroll":
		if (!isset($_GET['cid'])) { echo "Course ID not specified\n"; break;}
		echo "<h3>Unenroll</h3>\n";
		echo "Are you SURE you want to unenroll from this course?  All assessment attempts will be deleted.\n";
		echo "<p><input type=button onclick=\"window.location='actions.php?action=unenroll&cid={$_GET['cid']}'\" value=\"Really Unenroll\">\n";
		echo "<input type=button value=\"Never Mind\" onclick=\"window.location='./course/course.php?cid={$_GET['cid']}'\"></p>\n";
		break;
	case "resetpw":
		echo "<h3>Reset Password</h3>\n";
		echo "<form method=post action=\"actions.php?action=resetpw\">\n";
		echo "<p>Enter your User Name below and click Submit.  An email will be sent to your email address on file.  A link in that email will ";
		echo "reset your password.</p>";
		echo "<p>User Name: <input type=text name=\"username\"/></p>";
		echo "<p><input type=submit value=\"Submit\" /></p></form>";
		break;
		
}
	require("footer.php");
?>


		
				

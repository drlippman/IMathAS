<?php
//IMathAS:  Basic forms
//(c) 2006 David Lippman
require("config.php");
if ($_GET['action']!="newuser" && $_GET['action']!="resetpw" && $_GET['action']!="lookupusername") {
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
		echo "<span class=form><label for=\"firstname\">Enter First Name:</label></span> <input class=form type=text size=20 id=firstname name=firstname value=\"{$line['FirstName']}\" /><br class=\"form\" />\n";
		echo "<span class=form><label for=\"lastname\">Enter Last Name:</label></span> <input class=form type=text size=20 id=lastname name=lastname value=\"{$line['LastName']}\"><BR class=form>\n";
		echo "<span class=form><label for=\"email\">Enter E-mail address:</label></span>  <input class=form type=text size=60 id=email name=email value=\"{$line['email']}\"><BR class=form>\n";
		echo "<span class=form><label for=\"msgnot\">Notify me by email when I receive a new message:</label></span><span class=formright><input type=checkbox id=msgnot name=msgnot ";
		if ($line['msgnotify']==1) {echo "checked=1";}
		echo " /></span><BR class=form>\n";
		if ($myrights>19) {
			echo "<span class=form><label for=\"qrd\">Make new questions private by default?<br/>(recommended for new users):</label></span><span class=formright><input type=checkbox id=qrd name=qrd ";
			if ($line['qrightsdef']==0) {echo "checked=1";}
			echo " /></span><BR class=form>\n";
			if ($line['deflib']==0) {
				$lname = "Unassigned";
			} else {
				$query = "SELECT name FROM imas_libraries WHERE id='{$line['deflib']}'";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				$lname = mysql_result($result,0,0);
			}
			
			echo "<script type=\"text/javascript\">";
			echo "var curlibs = '{$line['deflib']}';";
			echo "function libselect() {";
			echo "  window.open('$imasroot/course/libtree2.php?libtree=popup&type=radio&libs='+curlibs,'libtree','width=400,height='+(.7*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420));";
			echo " }";
			echo "function setlib(libs) {";
			echo "  document.getElementById(\"libs\").value = libs;";
			echo "  curlibs = libs;";
			echo "}";
			echo "function setlibnames(libn) {";
			echo "  document.getElementById(\"libnames\").innerHTML = libn;";
			echo "}";
			echo "</script>";
			echo "<span class=form>Default question library:</span><span class=formright> <span id=\"libnames\">$lname</span><input type=hidden name=\"libs\" id=\"libs\"  value=\"{$line['deflib']}\">\n";
			echo " <input type=button value=\"Select Library\" onClick=\"libselect()\"></span><br class=form> ";
			
			echo "<span class=form>Use default question library for all templated questions?</span>";
			echo "<span class=formright><input type=checkbox name=\"usedeflib\"";
			if ($line['usedeflib']==1) {echo "checked=1";}
			echo "></span><br class=form>";
			
		}
		echo "<div class=submit><input type=submit value='Update Info'></div>\n";
		if ($myrights>19) {
			echo "<p>Default question library is used for all local (assessment-only) copies of questions created when you ";
			echo "edit a question (that's not yours) in an assessment.  You can elect to have all templated questions ";
			echo "be assigned to this library.</p>";
		}
		echo '<p><a href="forms.php?action=googlegadget">Get Google Gadget</a> to monitor your messages and forum posts</p>';
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
	case "lookupusername":
		echo "<h3>Lookup Username</h3>\n";
		echo "<form method=post action=\"actions.php?action=lookupusername\">\n"; 
		echo "If you can't remember your username, enter your email address below.  An email will be sent to your email address with your username. ";
		echo "<p>Email: <input type=text name=\"email\"/></p>";
		echo "<p><input type=submit value=\"Submit\" /></p></form>";
		break;
	case "googlegadget":
		$query = "SELECT remoteaccess FROM imas_users WHERE id='$userid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$code = mysql_result($result,0,0);
		if ($code=='' || isset($_GET['regen'])) {
			$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
			do {
				$pass = '';
				for ($i=0;$i<10;$i++) {
					$pass .= substr($chars,rand(0,61),1);
				}	
				$query = "SELECT id FROM imas_users WHERE remoteaccess='$pass'";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
			} while (mysql_num_rows($result)>0);
			$query = "UPDATE imas_users SET remoteaccess='$pass' WHERE id='$userid'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			$code = $pass;
		}
		echo "<h3>Google Gadget Access Code</h3>";
		echo "<p>The $installname Google Gadget allow you to view a list of new forum posts ";
		echo "and messages from your iGoogle page.  To install, click the link below to add ";
		echo "the gadget to your iGoogle page, then use the Access key below in the settings ";
		echo "to gain access to your data</p>";
		
		echo '<p>Add to iGoogle: <a href="http://fusion.google.com/add?source=atgs&moduleurl=http%3A//'.$_SERVER['HTTP_HOST'].$imasroot.'/google-postreader.php"><img src="http://gmodules.com/ig/images/plus_google.gif" border="0" alt="Add to Google"></a></p>';
		echo "<p>Access Code: $code</p>";
		echo "<p><a href=\"forms.php?action=googlegadget&regen=true\">Generate a new Access code<a/><br/>";
		echo "<p><a href=\"actions.php?action=googlegadget&clear=true\">Clear Access code</a></p>";
		echo "<p>Note: This access code only allows Google to access a list of new posts and messages, and does not provide access to grades or any other data stored at $installname.  Be aware that this form of access is insecure and could be intercepted by a third party.</p>";
		break;
}
	require("footer.php");
?>


		
				

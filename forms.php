<?php
//IMathAS:  Basic forms
//(c) 2006 David Lippman
require("config.php");
if ($_GET['action']!="newuser" && $_GET['action']!="resetpw" && $_GET['action']!="lookupusername") {
	require("validate.php");
} else {
	if (isset($CFG['CPS']['theme'])) {
		$defaultcoursetheme = $CFG['CPS']['theme'][0];
	} else if (!isset($defaultcoursetheme)) {
		 $defaultcoursetheme = "default.css";
	}
	$coursetheme = $defaultcoursetheme;
}
if (isset($_GET['greybox'])) {
	$gb = '&greybox=true';
	$flexwidth = true;
	$nologo = true;
} else {
	$gb = '';
}
require("header.php");	
if ($gb == '') {
	echo "<div class=breadcrumb><a href=\"index.php\">Home</a> &gt; Form</div>\n";
}
switch($_GET['action']) {
	case "newuser":
		echo '<div id="headerforms" class="pagetitle"><h2>New User Signup</h2></div>';
		//echo "<script type=\"text/javascript\" src=\"$imasroot/javascript/validateform.js\"></script>\n";
		echo "<form method=post action=\"actions.php?action=newuser$gb\" onsubmit=\"return validateForm(this)\">\n";
		echo "<span class=form><label for=\"SID\">$longloginprompt:</label></span> <input class=\"form\" type=\"text\" size=12 id=SID name=SID><BR class=\"form\">\n";
		echo "<span class=\"form\"><label for=\"pw1\">Choose a password:</label></span><input class=\"form\" type=\"password\" size=20 id=pw1 name=pw1><BR class=\"form\">\n";
		echo "<span class=\"form\"><label for=\"pw2\">Confirm password:</label></span> <input class=\"form\" type=\"password\" size=20 id=pw2 name=pw2><BR class=\"form\">\n";
		echo "<span class=\"form\"><label for=\"firstname\">Enter First Name:</label></span> <input class=\"form\" type=\"text\" size=20 id=firstname name=firstname><BR class=\"form\">\n";
		echo "<span class=\"form\"><label for=\"lastname\">Enter Last Name:</label></span> <input class=\"form\" type=\"text\" size=20 id=lastname name=lastname><BR class=\"form\">\n";
		echo "<span class=\"form\"><label for=\"email\">Enter E-mail address:</label></span>  <input class=\"form\" type=\"text\" size=60 id=email name=email><BR class=\"form\">\n";
		echo "<span class=form><label for=\"msgnot\">Notify me by email when I receive a new message:</label></span><span class=formright><input type=checkbox id=msgnot name=msgnot checked=\"checked\" /></span><BR class=form>\n";
		if (isset($studentTOS)) {
			echo "<span class=form><label for=\"agree\">I have read and agree to the Terms of Use (below)</label></span><span class=formright><input type=checkbox name=agree></span><br class=form />\n";
		} else if (isset($CFG['GEN']['TOSpage'])) {
			echo "<span class=form><label for=\"agree\">I have read and agree to the <a href=\"#\" onclick=\"GB_show('Terms of Use','".$CFG['GEN']['TOSpage']."',700,500);return false;\">Terms of Use</a></label></span><span class=formright><input type=checkbox name=agree></span><br class=form />\n";
		}
		
		if (!$emailconfirmation) {
			$query = "SELECT id,name FROM imas_courses WHERE (istemplate&4)=4 AND available<4 ORDER BY name";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$doselfenroll = false;
			if (mysql_num_rows($result)>0) {//if (isset($CFG['GEN']['selfenrolluser'])) {
				$doselfenroll = true;
				echo '<p>Select the course you\'d like to enroll in</p>';
				echo '<p><select id="courseselect" name="courseselect" onchange="courseselectupdate(this);">';
				echo '<option value="0" selected="selected">My teacher gave me a course ID (enter below)</option>';
				echo '<optgroup label="Self-study courses">';
				while ($row = mysql_fetch_row($result)) {
					echo '<option value="'.$row[0].'">'.$row[1].'</option>';
				}
				echo '</optgroup>';
				echo '</select></p>';
				echo '<div id="courseinfo">';
				echo '<script type="text/javascript"> function courseselectupdate(el) { var c = document.getElementById("courseinfo"); var c2 = document.getElementById("selfenrollwarn"); ';
				echo 'if (el.value==0) {c.style.display="";c2.style.display="none";} else {c.style.display="none";c2.style.display="";}}</script>';
			} else {
				echo '<p>If you already know your course ID, you can enter it now.  Otherwise, leave this blank and you can enroll later.</p>';
			}
			echo '<span class="form"><label for="courseid">Course ID:</label></span><input class="form" type="text" size="20" name="courseid"/><br class="form"/>';
			echo '<span class="form"><label for="ekey">Enrollment Key:</label></span><input class="form" type="text" size="20" name="ekey"/><br class="form"/>';
			if ($doselfenroll) {
				echo '</div>';
				echo '<div id="selfenrollwarn" style="color:red;display:none;">Warning: You have selected a non-credit self-study course. ';
				echo 'If you are using '.$installname.' with an instructor-led course, this is NOT what you want; nothing you do in the self-study ';
				echo 'course will be viewable by your instructor or count towards your course.  For an instructor-led ';
				echo 'course, you need to enter the course ID and key provided by your instructor.</div>';
			}
		}
		echo "<div class=submit><input type=submit value='Sign Up'></div>\n";
		echo "</form>\n";
		if (isset($studentTOS)) {
			include($studentTOS);
		}
		break;
	case "chgpwd":
		echo '<div id="headerforms" class="pagetitle"><h2>Change Your Password</h2></div>';
		echo "<form method=post action=\"actions.php?action=chgpwd$gb\">\n";
		echo "<span class=form><label for=\"oldpw\">Enter old password:</label></span> <input class=form type=password id=oldpw name=oldpw size=40 /> <BR class=form>\n";
		echo "<span class=form><label for=\"newpw1\">Enter new password:</label></span>  <input class=form type=password id=newpw1 name=newpw1 size=40> <BR class=form>\n";
		echo "<span class=form><label for=\"newpw1\">Verify new password:</label></span>  <input class=form type=password id=newpw2 name=newpw2 size=40> <BR class=form>\n";
		echo "<div class=submit><input type=submit value=Submit></div></form>\n";
		break;
	case "chguserinfo":
		$query = "SELECT * FROM imas_users WHERE id='$userid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$line = mysql_fetch_array($result, MYSQL_ASSOC);
		echo '<script type="text/javascript">function togglechgpw(val) { if (val) {document.getElementById("pwinfo").style.display="";} else {document.getElementById("pwinfo").style.display="none";} } </script>';
		
		echo '<div id="headerforms" class="pagetitle"><h2>User Info</h2></div>';
		echo "<form enctype=\"multipart/form-data\" method=post action=\"actions.php?action=chguserinfo$gb\">\n";
		echo '<fieldset id="userinfoprofile"><legend>Profile Settings</legend>';
		echo "<span class=form><label for=\"firstname\">Enter First Name:</label></span> <input class=form type=text size=20 id=firstname name=firstname value=\"{$line['FirstName']}\" /><br class=\"form\" />\n";
		echo "<span class=form><label for=\"lastname\">Enter Last Name:</label></span> <input class=form type=text size=20 id=lastname name=lastname value=\"{$line['LastName']}\"><BR class=form>\n";
		echo '<span class="form"><label for="dochgpw">Change Password?</label></span> <span class="formright"><input type="checkbox" name="dochgpw" onclick="togglechgpw(this.checked)" /></span><br class="form" />';
		echo '<div style="display:none" id="pwinfo">';
		echo "<span class=form><label for=\"oldpw\">Enter old password:</label></span> <input class=form type=password id=oldpw name=oldpw size=40 /> <BR class=form>\n";
		echo "<span class=form><label for=\"newpw1\">Enter new password:</label></span>  <input class=form type=password id=newpw1 name=newpw1 size=40> <BR class=form>\n";
		echo "<span class=form><label for=\"newpw1\">Verify new password:</label></span>  <input class=form type=password id=newpw2 name=newpw2 size=40> <BR class=form>\n";
		echo '</div>';
		echo "<span class=form><label for=\"email\">Enter E-mail address:</label></span>  <input class=form type=text size=60 id=email name=email value=\"{$line['email']}\"><BR class=form>\n";
		echo "<span class=form><label for=\"msgnot\">Notify me by email when I receive a new message:</label></span><span class=formright><input type=checkbox id=msgnot name=msgnot ";
		if ($line['msgnotify']==1) {echo "checked=1";}
		echo " /></span><BR class=form>\n";
		
		echo "<span class=form><label for=\"stupic\">Picture:</label></span>";
		echo "<span class=\"formright\">";
		if ($line['hasuserimg']==1) {
			if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
				echo "<img src=\"{$urlmode}s3.amazonaws.com/{$GLOBALS['AWSbucket']}/cfiles/userimg_$userid.jpg\"/> <input type=\"checkbox\" name=\"removepic\" value=\"1\" /> Remove ";
			} else {
				$curdir = rtrim(dirname(__FILE__), '/\\');
				$galleryPath = "$curdir/course/files/";
				echo "<img src=\"$imasroot/course/files/userimg_$userid.jpg\"/> <input type=\"checkbox\" name=\"removepic\" value=\"1\" /> Remove ";
			}
		} else {
			echo "No Pic ";
		}
		echo '<br/><input type="file" name="stupic"/></span><br class="form" />';
		echo '<span class="form"><label for="perpage">Messages/Posts per page:</label></span>';
		echo '<span class="formright"><select name="perpage">';
		for ($i=10;$i<=100;$i+=10) {
			echo '<option value="'.$i.'" ';
			if ($i==$line['listperpage']) {echo 'selected="selected"';}
			echo '>'.$i.'</option>';
		}
		echo '</select></span><br class="form" />';
		
		$pagelayout = explode('|',$line['homelayout']);
		foreach($pagelayout as $k=>$v) {
			if ($v=='') {
				$pagelayout[$k] = array();
			} else {
				$pagelayout[$k] = explode(',',$v);
			}
		}
		$hpsets = '';
		if (!isset($CFG['GEN']['fixedhomelayout']) || !in_array(2,$CFG['GEN']['fixedhomelayout'])) {
			$hpsets .= '<input type="checkbox" name="homelayout10" ';
			if (in_array(10,$pagelayout[2])) {$hpsets .= 'checked="checked"';}
			$hpsets .=  ' /> New messages widget<br/>';
			
			$hpsets .= '<input type="checkbox" name="homelayout11" ';
			if (in_array(11,$pagelayout[2])) {$hpsets .= 'checked="checked"';}
			$hpsets .= ' /> New forum posts widget<br/>';
		}
		if (!isset($CFG['GEN']['fixedhomelayout']) || !in_array(3,$CFG['GEN']['fixedhomelayout'])) {
			
			$hpsets .= '<input type="checkbox" name="homelayout3-0" ';
			if (in_array(0,$pagelayout[3])) {$hpsets .= 'checked="checked"';}
			$hpsets .= ' /> New messages notes on course list<br/>';
			
			$hpsets .= '<input type="checkbox" name="homelayout3-1" ';
			if (in_array(1,$pagelayout[3])) {$hpsets .= 'checked="checked"';}
			$hpsets .= ' /> New posts notes on course list<br/>';
		}
		if ($hpsets != '') {
			echo '<span class="form">Show on home page:</span><span class="formright">';
			echo $hpsets;
			echo '</span><br class="form" />';
			
		}
		echo '</fieldset>';
		
		if ($myrights>19) {
			echo '<fieldset id="userinfoinstructor"><legend>Instructor Options</legend>';
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
			echo "> ";
			echo "</span><br class=form>";
			echo "<p>Default question library is used for all local (assessment-only) copies of questions created when you ";
			echo "edit a question (that's not yours) in an assessment.  You can elect to have all templated questions ";
			echo "be assigned to this library.</p>";
			echo '</fieldset>';
			
		}
		echo "<div class=submit><input type=submit value='Update Info'></div>\n";
		
		//echo '<p><a href="forms.php?action=googlegadget">Get Google Gadget</a> to monitor your messages and forum posts</p>';
		echo "</form>\n";
		break;
	case "enroll":
		echo '<div id="headerforms" class="pagetitle"><h2>Enroll in a Course</h2></div>';
		echo "<form method=post action=\"actions.php?action=enroll$gb\">";
		$query = "SELECT id,name FROM imas_courses WHERE (istemplate&4)=4 AND available<4 ORDER BY name";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$doselfenroll = false;
		if (mysql_num_rows($result)>0) {//if (isset($CFG['GEN']['selfenrolluser'])) {
			$doselfenroll = true;
			echo '<p>Select the course you\'d like to enroll in</p>';
			echo '<p><select id="courseselect" name="courseselect" onchange="courseselectupdate(this);">';
			echo '<option value="0" selected="selected">My teacher gave me a course ID (enter below)</option>';
			echo '<optgroup label="Self-study courses">';
			while ($row = mysql_fetch_row($result)) {
				echo '<option value="'.$row[0].'">'.$row[1].'</option>';
			}
			echo '</optgroup>';
			echo '</select></p>';
			echo '<div id="courseinfo">';
			echo '<script type="text/javascript"> function courseselectupdate(el) { var c = document.getElementById("courseinfo"); var c2 = document.getElementById("selfenrollwarn"); ';
			echo 'if (el.value==0) {c.style.display="";c2.style.display="none";} else {c.style.display="none";c2.style.display="";}}</script>';
		} else {
			echo '<p>If you already know your course ID, you can enter it now.  Otherwise, leave this blank and you can enroll later.</p>';
		}
		echo '<span class="form"><label for="cid">Course ID:</label></span><input class="form" type="text" size="20" name="cid"/><br class="form"/>';
		echo '<span class="form"><label for="ekey">Enrollment Key:</label></span><input class="form" type="text" size="20" name="ekey"/><br class="form"/>';
		if ($doselfenroll) {
			echo '</div>';
			echo '<div id="selfenrollwarn" style="color:red;display:none;">Warning: You have selected a non-credit self-study course. ';
			echo 'If you are using '.$installname.' with an instructor-led course, this is NOT what you want; nothing you do in the self-study ';
			echo 'course will be viewable by your instructor or count towards your course.  For an instructor-led ';
			echo 'course, you need to enter the course ID and key provided by your instructor.</div>';
		}
		echo '<div class=submit><input type=submit value="Sign Up"></div></form>';
		break;
	case "unenroll":
		if (!isset($_GET['cid'])) { echo "Course ID not specified\n"; break;}
		echo '<div id="headerforms" class="pagetitle"><h2>Unenroll</h2></div>';
		
		echo "Are you SURE you want to unenroll from this course?  All assessment attempts will be deleted.\n";
		echo "<p><input type=button onclick=\"window.location='actions.php?action=unenroll&cid={$_GET['cid']}'\" value=\"Really Unenroll\">\n";
		echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='./course/course.php?cid={$_GET['cid']}'\"></p>\n";
		break;
	case "resetpw":
		echo '<div id="headerforms" class="pagetitle"><h2>Reset Password</h2></div>';
		echo "<form method=post action=\"actions.php?action=resetpw$gb\">\n";
		echo "<p>Enter your User Name below and click Submit.  An email will be sent to your email address on file.  A link in that email will ";
		echo "reset your password.</p>";
		echo "<p>User Name: <input type=text name=\"username\"/></p>";
		echo "<p><input type=submit value=\"Submit\" /></p></form>";
		break;
	case "lookupusername":
		echo '<div id="headerforms" class="pagetitle"><h2>Lookup Username</h2></div>';
		echo "<form method=post action=\"actions.php?action=lookupusername$gb\">\n"; 
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
		echo '<div id="headerforms" class="pagetitle"><h2>Google Gadget Access Code</h2></div>';
		echo "<p>The $installname Google Gadget allow you to view a list of new forum posts ";
		echo "and messages from your iGoogle page.  To install, click the link below to add ";
		echo "the gadget to your iGoogle page, then use the Access key below in the settings ";
		echo "to gain access to your data</p>";
		
		echo '<p>Add to iGoogle: <a href="http://fusion.google.com/add?source=atgs&moduleurl=http%3A//'.$_SERVER['HTTP_HOST'].$imasroot.'/google-postreader.php"><img src="http://gmodules.com/ig/images/plus_google.gif" border="0" alt="Add to Google"></a></p>';
		echo "<p>Access Code: $code</p>";
		echo "<p><a href=\"forms.php?action=googlegadget&regen=true$gb\">Generate a new Access code<a/><br/>";
		echo "<p><a href=\"actions.php?action=googlegadget&clear=true$gb\">Clear Access code</a></p>";
		echo "<p>Note: This access code only allows Google to access a list of new posts and messages, and does not provide access to grades or any other data stored at $installname.  Be aware that this form of access is insecure and could be intercepted by a third party.</p>";
		echo "<p>You can also bookmark <a href=\"getpostlist.php?key=$code\">this page</a> to be able to access your post list without needing to log in.</p>";
		break;
}
	require("footer.php");
?>


		
				

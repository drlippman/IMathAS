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

switch($_GET['action']) {
	case "newuser":
		if ($gb == '') {
			echo "<div class=breadcrumb><a href=\"index.php\">Home</a> &gt; New User Signup</div>\n";
		}
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
			$doselfenroll = false;
			//DB $query = "SELECT id,name FROM imas_courses WHERE (istemplate&4)=4 AND available<4 ORDER BY name";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB if (mysql_num_rows($result)>0) {
			$stm = $DBH->query("SELECT id,name FROM imas_courses WHERE (istemplate&4)=4 AND available<4 ORDER BY name");
			if ($stm->rowCount()>0) {
				$doselfenroll = true;
				echo '<p>Select the course you\'d like to enroll in</p>';
				echo '<p><select id="courseselect" name="courseselect" onchange="courseselectupdate(this);">';
				echo '<option value="0" selected="selected">My teacher gave me a course ID (enter below)</option>';
				echo '<optgroup label="Self-study courses">';
				//DB while ($row = mysql_fetch_row($result)) {
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
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
		if ($gb == '') {
			echo "<div class=breadcrumb><a href=\"index.php\">Home</a> &gt; Change Password</div>\n";
		}
		echo '<div id="headerforms" class="pagetitle"><h2>Change Your Password</h2></div>';
		echo "<form method=post action=\"actions.php?action=chgpwd$gb\">\n";
		echo "<span class=form><label for=\"oldpw\">Enter old password:</label></span> <input class=form type=password id=oldpw name=oldpw size=40 /> <BR class=form>\n";
		echo "<span class=form><label for=\"newpw1\">Enter new password:</label></span>  <input class=form type=password id=newpw1 name=newpw1 size=40> <BR class=form>\n";
		echo "<span class=form><label for=\"newpw1\">Verify new password:</label></span>  <input class=form type=password id=newpw2 name=newpw2 size=40> <BR class=form>\n";
		echo "<div class=submit><input type=submit value=Submit></div></form>\n";
		break;
	case "chguserinfo":
		//DB $query = "SELECT * FROM imas_users WHERE id='$userid'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $line = mysql_fetch_array($result, MYSQL_ASSOC);
		$stm = $DBH->prepare("SELECT * FROM imas_users WHERE id=:id");
		$stm->execute(array(':id'=>$userid));
		$line = $stm->fetch(PDO::FETCH_ASSOC);
		echo '<script type="text/javascript">function togglechgpw(val) { if (val) {document.getElementById("pwinfo").style.display="";} else {document.getElementById("pwinfo").style.display="none";} } </script>';
		if ($gb == '') {
			echo "<div class=breadcrumb><a href=\"index.php\">Home</a> &gt; Modify User Profile</div>\n";
		}
		echo '<div id="headerforms" class="pagetitle"><h2>User Profile</h2></div>';
		echo "<form enctype=\"multipart/form-data\" method=post action=\"actions.php?action=chguserinfo$gb\">\n";
		echo '<fieldset id="userinfoprofile"><legend>Profile Settings</legend>';
		echo "<span class=form><label for=\"firstname\">Enter First Name:</label></span> <input class=form type=text size=20 id=firstname name=firstname value=\"{$line['FirstName']}\" /><br class=\"form\" />\n";
		echo "<span class=form><label for=\"lastname\">Enter Last Name:</label></span> <input class=form type=text size=20 id=lastname name=lastname value=\"{$line['LastName']}\"><BR class=form>\n";
		if ($myrights>10 && $groupid>0) {
			//DB $query = "SELECT name FROM imas_groups WHERE id=".intval($groupid);
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $r = mysql_fetch_row($result);
			$stm = $DBH->prepare("SELECT name FROM imas_groups WHERE id=:id");
			$stm->execute(array(':id'=>$groupid));
			$r = $stm->fetch(PDO::FETCH_NUM);
			echo '<span class="form">'._('Group').':</span><span class="formright">'.$r[0].'</span><br class="form"/>';
		}
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
		echo '<span class="form">'._('Overwrite default course theme on all pages:').'</span><span class="formright">';
		echo '<select name="theme">';
		echo '<option value="" '.($line['theme']==''?'selected':'').'>'._('Use course default theme').'</option>';
		if (isset($CFG['GEN']['stuthemes'])) {
			foreach ($CFG['GEN']['stuthemes'] as $k=>$v) {
				echo '<option value="'.$k.'" '.($line['theme']==$k?'selected':'').'>'._($v).'</option>';
			}
		} else {
			echo '<option value="highcontrast.css" '.($line['theme']=='highcontrast.css'?'selected':'').'>'._('High contrast, dark on light').'</option>';
			echo '<option value="highcontrast_dark.css" '.($line['theme']=='highcontrast_dark.css'?'selected':'').'>'._('High contrast, light on dark').'</option>';
		}
		echo '</select><br class="form" />';

		if (isset($CFG['GEN']['translatewidgetID'])) {
			echo '<span class="form">Attempt to translate pages into another language:</span>';
			echo '<span class="formright">';
			echo '<div id="google_translate_element"></div><script type="text/javascript">';
			echo ' function googleTranslateElementInit() {';
			echo '  new google.translate.TranslateElement({pageLanguage: "en", layout: google.translate.TranslateElement.InlineLayout.HORIZONTAL}, "google_translate_element");';
			echo ' }</script><script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>';
			echo '<br class="form"/>';
			unset($CFG['GEN']['translatewidgetID']);
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
				//DB $query = "SELECT name FROM imas_libraries WHERE id='{$line['deflib']}'";
				//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
				//DB $lname = mysql_result($result,0,0);
				$stm = $DBH->prepare("SELECT name FROM imas_libraries WHERE id=:id");
				$stm->execute(array(':id'=>$line['deflib']));
				$lname = $stm->fetchColumn(0);
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
		if ($tzname!='') {
			echo '<fieldset><legend>Timezone</legend>';
			echo '<p>Due Dates and other times are being shown to you correct for the <b>'.$tzname.'</b> timezone.</p>';
			echo '<p>You may change the timezone the dates display based on if you would like. This change will only last until you close your browser or log out.</p>';
			echo '<p>Set timezone to: <select name="settimezone" id="settimezone">';
			$timezones = array('Etc/GMT+12', 'Pacific/Pago_Pago', 'America/Adak', 'Pacific/Honolulu', 'Pacific/Marquesas', 'Pacific/Gambier', 'America/Anchorage', 'America/Los_Angeles', 'Pacific/Pitcairn', 'America/Phoenix', 'America/Denver', 'America/Guatemala', 'America/Chicago', 'Pacific/Easter', 'America/Bogota', 'America/New_York', 'America/Caracas', 'America/Halifax', 'America/Santo_Domingo', 'America/Santiago', 'America/St_Johns', 'America/Godthab', 'America/Argentina/Buenos_Aires', 'America/Montevideo', 'Etc/GMT+2', 'Etc/GMT+2', 'Atlantic/Azores', 'Atlantic/Cape_Verde', 'Etc/UTC', 'Europe/London', 'Europe/Berlin', 'Africa/Lagos', 'Africa/Windhoek', 'Asia/Beirut', 'Africa/Johannesburg', 'Asia/Baghdad', 'Europe/Moscow', 'Asia/Tehran', 'Asia/Dubai', 'Asia/Baku', 'Asia/Kabul', 'Asia/Yekaterinburg', 'Asia/Karachi', 'Asia/Kolkata', 'Asia/Kathmandu', 'Asia/Dhaka', 'Asia/Omsk', 'Asia/Rangoon', 'Asia/Krasnoyarsk', 'Asia/Jakarta', 'Asia/Shanghai', 'Asia/Irkutsk', 'Australia/Eucla', 'Australia/Eucla', 'Asia/Yakutsk', 'Asia/Tokyo', 'Australia/Darwin', 'Australia/Adelaide', 'Australia/Brisbane', 'Asia/Vladivostok', 'Australia/Sydney', 'Australia/Lord_Howe', 'Asia/Kamchatka', 'Pacific/Noumea', 'Pacific/Norfolk', 'Pacific/Auckland', 'Pacific/Tarawa', 'Pacific/Chatham', 'Pacific/Tongatapu', 'Pacific/Apia', 'Pacific/Kiritimati');
			foreach ($timezones as $tz) {
				echo '<option value="'.$tz.'" '.($tz==$tzname?'selected':'').'>'.$tz.'</option>';
			}
			echo '</select></p>';
			echo '</fieldset>';


		}
		echo "<div class=submit><input type=submit value='Update Info'></div>\n";

		//echo '<p><a href="forms.php?action=googlegadget">Get Google Gadget</a> to monitor your messages and forum posts</p>';
		echo "</form>\n";
		break;
	case "enroll":
		if ($gb == '') {
			echo "<div class=breadcrumb><a href=\"index.php\">Home</a> &gt; Enroll in a Course</div>\n";
		}
		echo '<div id="headerforms" class="pagetitle"><h2>Enroll in a Course</h2></div>';
		echo "<form method=post action=\"actions.php?action=enroll$gb\">";
		$doselfenroll = false;
		//DB $query = "SELECT id,name FROM imas_courses WHERE (istemplate&4)=4 AND available<4 ORDER BY name";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB if (mysql_num_rows($result)>0) {
		$stm = $DBH->query("SELECT id,name FROM imas_courses WHERE (istemplate&4)=4 AND available<4 ORDER BY name");
		if ($stm->rowCount()>0) {
			$doselfenroll = true;
			echo '<p>Select the course you\'d like to enroll in</p>';
			echo '<p><select id="courseselect" name="courseselect" onchange="courseselectupdate(this);">';
			echo '<option value="0" selected="selected">My teacher gave me a course ID (enter below)</option>';
			echo '<optgroup label="Self-study courses">';
			//DB while ($row = mysql_fetch_row($result)) {
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
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
		if ($gb == '') {
			echo "<div class=breadcrumb><a href=\"index.php\">Home</a> &gt; Unenroll</div>\n";
		}
		echo '<div id="headerforms" class="pagetitle"><h2>Unenroll</h2></div>';

		echo "Are you SURE you want to unenroll from this course?  All assessment attempts will be deleted.\n";
		echo "<p><input type=button onclick=\"window.location='actions.php?action=unenroll&cid={$_GET['cid']}'\" value=\"Really Unenroll\">\n";
		echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='./course/course.php?cid={$_GET['cid']}'\"></p>\n";
		break;
	case "resetpw":
		if ($gb == '') {
			echo "<div class=breadcrumb><a href=\"index.php\">Home</a> &gt; Password Reset</div>\n";
		}
		echo '<div id="headerforms" class="pagetitle"><h2>Reset Password</h2></div>';
		echo "<form method=post action=\"actions.php?action=resetpw$gb\">\n";
		echo "<p>Enter your User Name below and click Submit.  An email will be sent to your email address on file.  A link in that email will ";
		echo "reset your password.</p>";
		echo "<p>User Name: <input type=text name=\"username\"/></p>";
		echo "<p><input type=submit value=\"Submit\" /></p></form>";
		break;
	case "lookupusername":
		if ($gb == '') {
			echo "<div class=breadcrumb><a href=\"index.php\">Home</a> &gt; Username Lookup</div>\n";
		}
		echo '<div id="headerforms" class="pagetitle"><h2>Lookup Username</h2></div>';
		echo "<form method=post action=\"actions.php?action=lookupusername$gb\">\n";
		echo "If you can't remember your username, enter your email address below.  An email will be sent to your email address with your username. ";
		echo "<p>Email: <input type=text name=\"email\"/></p>";
		echo "<p><input type=submit value=\"Submit\" /></p></form>";
		break;
	case "forumwidgetsettings":
		//DB $query = "SELECT hideonpostswidget FROM imas_users WHERE id='$userid'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $hidelist = explode(',', mysql_result($result,0,0));
		$stm = $DBH->prepare("SELECT hideonpostswidget FROM imas_users WHERE id=:id");
		$stm->execute(array(':id'=>$userid));
		$hidelist = explode(',', $stm->fetchColumn(0));
		if ($gb == '') {
			echo "<div class=breadcrumb><a href=\"index.php\">Home</a> &gt; Forum Widget Settings</div>\n";
		}
		echo '<div id="headerforms" class="pagetitle"><h2>Forum Widget Settings</h2></div>';
		echo '<p>The most recent 10 posts from each course show in the New Forum Posts widget.  Select the courses you want to show in the widget.</p>';
		echo "<form method=post action=\"actions.php?action=forumwidgetsettings$gb\">\n";
		$allcourses = array();
		//DB $query = "SELECT ic.id,ic.name FROM imas_courses AS ic JOIN imas_teachers AS it ON ic.id=it.courseid WHERE it.userid='$userid' ORDER BY ic.name";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB if (mysql_num_rows($result)>0) {
		$stm = $DBH->prepare("SELECT ic.id,ic.name FROM imas_courses AS ic JOIN imas_teachers AS it ON ic.id=it.courseid WHERE it.userid=:userid ORDER BY ic.name");
		$stm->execute(array(':userid'=>$userid));
		if ($stm->rowCount()>0) {
			echo '<p><b>Courses you\'re teaching:</b> Check: <a href="#" onclick="$(\'.teaching\').prop(\'checked\',true);return false;">All</a> <a href="#" onclick="$(\'.teaching\').prop(\'checked\',false);return false;">None</a>';
			//DB while ($row = mysql_fetch_row($result)) {
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$allcourses[] = $row[0];
				echo '<br/><input type="checkbox" name="checked[]" class="teaching" value="'.$row[0].'" ';
				if (!in_array($row[0],$hidelist)) {echo 'checked="checked"';}
				echo '/> '.$row[1];
			}
			echo '</p>';
		}
		//DB $query = "SELECT ic.id,ic.name FROM imas_courses AS ic JOIN imas_tutors AS it ON ic.id=it.courseid WHERE it.userid='$userid' ORDER BY ic.name";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB if (mysql_num_rows($result)>0) {
		$stm = $DBH->prepare("SELECT ic.id,ic.name FROM imas_courses AS ic JOIN imas_tutors AS it ON ic.id=it.courseid WHERE it.userid=:userid ORDER BY ic.name");
		$stm->execute(array(':userid'=>$userid));
		if ($stm->rowCount()>0) {
			echo '<p><b>Courses you\'re tutoring:</b> Check: <a href="#" onclick="$(\'.tutoring\').prop(\'checked\',true);return false;">All</a> <a href="#" onclick="$(\'.tutoring\').prop(\'checked\',false);return false;">None</a>';
			//DB while ($row = mysql_fetch_row($result)) {
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$allcourses[] = $row[0];
				echo '<br/><input type="checkbox" name="checked[]" class="tutoring" value="'.$row[0].'" ';
				if (!in_array($row[0],$hidelist)) {echo 'checked="checked"';}
				echo '/> '.$row[1];
			}
			echo '</p>';
		}
		//DB $query = "SELECT ic.id,ic.name FROM imas_courses AS ic JOIN imas_students AS it ON ic.id=it.courseid WHERE it.userid='$userid' ORDER BY ic.name";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB if (mysql_num_rows($result)>0) {
		$stm = $DBH->prepare("SELECT ic.id,ic.name FROM imas_courses AS ic JOIN imas_students AS it ON ic.id=it.courseid WHERE it.userid=:userid ORDER BY ic.name");
		$stm->execute(array(':userid'=>$userid));
		if ($stm->rowCount()>0) {
			echo '<p><b>Courses you\'re taking:</b> Check: <a href="#" onclick="$(\'.taking\').prop(\'checked\',true);return false;">All</a> <a href="#" onclick="$(\'.taking\').prop(\'checked\',false);return false;">None</a>';
			//DB while ($row = mysql_fetch_row($result)) {
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$allcourses[] = $row[0];
				echo '<br/><input type="checkbox" name="checked[]" class="taking" value="'.$row[0].'" ';
				if (!in_array($row[0],$hidelist)) {echo 'checked="checked"';}
				echo '/> '.$row[1];
			}
			echo '</p>';
		}
		echo '<input type="hidden" name="allcourses" value="'.implode(',',$allcourses).'"/>';
		echo '<input type="submit" value="Save Changes"/>';
		echo '</form>';
		break;
	case "googlegadget":
		//DB $query = "SELECT remoteaccess FROM imas_users WHERE id='$userid'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $code = mysql_result($result,0,0);
		$stm = $DBH->prepare("SELECT remoteaccess FROM imas_users WHERE id=:id");
		$stm->execute(array(':id'=>$userid));
		$code = $stm->fetchColumn(0);
		if ($code=='' || isset($_GET['regen'])) {
			$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
			do {
				$pass = '';
				for ($i=0;$i<10;$i++) {
					$pass .= substr($chars,rand(0,61),1);
				}
				//DB $query = "SELECT id FROM imas_users WHERE remoteaccess='$pass'";
				//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
				$stm = $DBH->prepare("SELECT id FROM imas_users WHERE remoteaccess=:remoteaccess");
				$stm->execute(array(':remoteaccess'=>$pass));
			//DB } while (mysql_num_rows($result)>0);
			} while ($stm->rowCount()>0);
			//DB $query = "UPDATE imas_users SET remoteaccess='$pass' WHERE id='$userid'";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("UPDATE imas_users SET remoteaccess=:remoteaccess WHERE id=:id");
			$stm->execute(array(':remoteaccess'=>$pass, ':id'=>$userid));
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

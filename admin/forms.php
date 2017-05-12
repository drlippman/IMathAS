<?php
//IMathAS:  Admin forms
//(c) 2006 David Lippman
require("../validate.php");
require("../header.php");

$from = 'admin';
$backloc = 'admin.php';
if (isset($_GET['from'])) {
	if ($_GET['from']=='home') {
		$from = 'home';
		$backloc = '../index.php';
	}
}
if (!isset($_GET['cid'])) {
	echo "<div class=breadcrumb>$breadcrumbbase ";
	if ($from != 'home') {
		echo "<a href=\"admin.php\">Admin</a> &gt; ";
	}
	echo "Form</div>\n";
}

switch($_GET['action']) {
	case "delete":
		if ($myrights < 40) { echo "You don't have the authority for this action"; break;}
		//DB $query = "SELECT name FROM imas_courses WHERE id='{$_GET['id']}'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $name = mysql_result($result,0,0);
		$stm = $DBH->prepare("SELECT name FROM imas_courses WHERE id=:id");
		$stm->execute(array(':id'=>$_GET['id']));
		$name = $stm->fetchColumn(0);
		echo '<div id="headerforms" class="pagetitle"><h2>Delete Course</h2></div>';
		echo "<p>Are you sure you want to delete the course <b>$name</b>?</p>\n";
		echo "<p><input type=button value=\"Delete\" onclick=\"window.location='actions.php?from=$from&action=delete&id={$_GET['id']}'\">\n";
		echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='$backloc'\"></p>\n";
		break;
	case "deladmin":
		echo "<p>Are you sure you want to delete this user?</p>\n";
		echo "<p><input type=button value=\"Delete\" onclick=\"window.location='actions.php?from=$from&action=deladmin&id={$_GET['id']}'\">\n";
		echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='$backloc'\"></p>\n";
		break;
	case "chgpwd":
		echo '<div id="headerforms" class="pagetitle"><h2>Change Your Password</h2></div>';
		echo "<form method=post action=\"actions.php?from=$from&action=chgpwd\">\n";
		echo "<span class=form>Enter old password:</span>  <input class=form type=password name=oldpw size=40> <BR class=form>\n";
		echo "<span class=form>Enter new password:</span> <input class=form type=password name=newpw1 size=40> <BR class=form>\n";
		echo "<span class=form>Verify new password:</span>  <input class=form type=password name=newpw2 size=40> <BR class=form>\n";
		echo '<div class=submit><input type="submit" value="'._('Save').'"></div></form>';
		break;

	case "chgrights":
	case "newadmin":
		echo "<form method=post action=\"actions.php?from=$from&action={$_GET['action']}";
		if ($_GET['action']=="chgrights") { echo "&id={$_GET['id']}"; }
		echo "\">\n";
		if ($_GET['action'] == "newadmin") {
			echo "<span class=form>New User username:</span>  <input class=form type=text size=40 name=adminname><BR class=form>\n";
			echo "<span class=form>First Name:</span> <input class=form type=text size=40 name=firstname><BR class=form>\n";
			echo "<span class=form>Last Name:</span> <input class=form type=text size=40 name=lastname><BR class=form>\n";
			echo "<span class=form>Email:</span> <input class=form type=text size=40 name=email><BR class=form>\n";
			echo '<span class="form">Password:</span> <input class="form" type="text" size="40" name="password"/><br class="form"/>';
			$oldgroup = 0;
			$oldrights = 10;
		} else {
			//DB $query = "SELECT FirstName,LastName,rights,groupid,specialrights FROM imas_users WHERE id='{$_GET['id']}'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $line = mysql_fetch_array($result, MYSQL_ASSOC);
			$stm = $DBH->prepare("SELECT FirstName,LastName,rights,groupid,specialrights FROM imas_users WHERE id=:id");
			$stm->execute(array(':id'=>$_GET['id']));
			$line = $stm->fetch(PDO::FETCH_ASSOC);
			echo "<h2>{$line['FirstName']} {$line['LastName']}</h2>\n";
			$oldgroup = $line['groupid'];
			$oldrights = $line['rights'];
			$oldspecialrights = $line['specialrights'];
		}
		echo '<script type="text/javascript">
			function onrightschg() {
				var selrights = this.value;
				if (selrights<75) {
					$("input[name^=specialrights]").prop("checked",false);
				} else if (selrights==75) {
					$("#specialrights1,#specialrights4,#specialrights8").prop("checked",true);
					$("#specialrights2").prop("checked",false);
				} else if (selrights==100) {
					$("input[name^=specialrights]").prop("checked",true);
				}
			}
			$(function() {
				$("input[name=newrights]").on("change", onrightschg);
				});
			</script>';
		echo "<BR><span class=form><img src=\"$imasroot/img/help.gif\" alt=\"Help\" onClick=\"window.open('$imasroot/help.php?section=rights','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))\"/> Set User rights to: </span> \n";
		echo "<span class=formright><input type=radio name=\"newrights\" value=\"5\" ";
		if ($oldrights == 5) {echo "CHECKED";}
		echo "> Guest User <BR>\n";
		echo "<input type=radio name=\"newrights\" value=\"10\" ";
		if ($oldrights == 10) {echo "CHECKED";}
		echo "> Student <BR>\n";
		//obscelete
		//echo "<input type=radio name=\"newrights\" value=\"15\" ";
		//if ($oldrights == 15) {echo "CHECKED";}
		//echo "> TA/Tutor/Proctor <BR>\n";
		echo "<input type=radio name=\"newrights\" value=\"20\" ";
		if ($oldrights == 20) {echo "CHECKED";}
		echo "> Teacher <BR>\n";
		echo "<input type=radio name=\"newrights\" value=\"40\" ";
		if ($oldrights == 40) {echo "CHECKED";}
		echo "> Limited Course Creator <BR>\n";
		echo "<input type=radio name=\"newrights\" value=\"75\" ";
		if ($oldrights == 75) {echo "CHECKED";}
		echo "> Group Admin <BR>\n";
		if ($myrights==100) {
			echo "<input type=radio name=\"newrights\" value=\"100\" ";
			if ($oldrights == 100) {echo "CHECKED";}
			echo "> Full Admin </span><BR class=form>\n";
		}
		echo '<span class="form">Task Rights:</span><span class="formright">';
		if ($myrights>=75) {
			echo '<input type="checkbox" name="specialrights1" id="specialrights1" ';
			if (($oldspecialrights&1)==1) { echo 'checked';}
			echo '><label for="specialrights1">Designate group template courses</label><br/>';
		}
		if ($myrights==100) {
			echo '<input type="checkbox" name="specialrights2" id="specialrights2" ';
			if (($oldspecialrights&2)==2) { echo 'checked';}
			echo '><label for="specialrights2">Designate global template courses</label><br/>';
		}
		if ($myrights>=75) {
			echo '<input type="checkbox" name="specialrights4" id="specialrights4" ';
			if (($oldspecialrights&4)==4) { echo 'checked';}
			echo '><label for="specialrights4">Create Diagnostic logins</label><br/>';
		}
		if ($myrights>=75 && !$allownongrouplibs) {
			echo '<input type="checkbox" name="specialrights8" id="specialrights8" ';
			if (($oldspecialrights&8)==8) { echo 'checked';}
			echo '><label for="specialrights8">Create public (open to all) question libraries</label><br/>';
		}
		echo '</span><br class="form"/>';

		if ($myrights == 100) {
			echo "<span class=form>Assign to group: </span>";
			echo "<span class=formright><select name=\"group\" id=\"group\">";
			echo "<option value=0>Default</option>\n";
			//DB $query = "SELECT id,name FROM imas_groups ORDER BY name";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB while ($row = mysql_fetch_row($result)) {
			$stm = $DBH->query("SELECT id,name FROM imas_groups ORDER BY name");
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				echo "<option value=\"{$row[0]}\" ";
				if ($oldgroup==$row[0]) {
					echo "selected=1";
				}
				echo ">{$row[1]}</option>\n";
			}
			echo "</select><br class=form />\n";
		}


		echo "<div class=submit><input type=submit value=Save></div></form>\n";
		break;
	case "modify":
	case "addcourse":
		if ($myrights < 40) { echo "You don't have the authority for this action"; break;}

		$isadminview = false;
		if ($_GET['action']=='modify') {
			//DB $query = "SELECT * FROM imas_courses WHERE id='{$_GET['id']}'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB if (mysql_num_rows($result)==0) {break;}
			//DB $line = mysql_fetch_array($result, MYSQL_ASSOC);
			$stm = $DBH->prepare("SELECT * FROM imas_courses WHERE id=:id");
			$stm->execute(array(':id'=>$_GET['id']));
			if ($stm->rowCount()==0) {break;}
			$line = $stm->fetch(PDO::FETCH_ASSOC);
			if ($myrights<75 && $line['ownerid']!=$userid) {
				echo "You don't have the authority for this action"; break;
			} else if ($myrights > 74 && $line['ownerid']!=$userid) {
				$isadminview = true;
				//DB $query = "SELECT iu.FirstName, iu.LastName, iu.groupid, ig.name FROM imas_users AS iu JOIN imas_groups AS ig ON ig.id=iu.groupid WHERE iu.id={$line['ownerid']}";
				//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
				//DB $udat = mysql_fetch_array($result, MYSQL_ASSOC);
				$stm = $DBH->prepare("SELECT iu.FirstName, iu.LastName, iu.groupid, ig.name FROM imas_users AS iu JOIN imas_groups AS ig ON ig.id=iu.groupid WHERE iu.id=:id");
				$stm->execute(array(':id'=>$line['ownerid']));
				$udat = $stm->fetch(PDO::FETCH_ASSOC);
				if ($myrights===75 && $udat['groupid']!=$groupid) {
					echo "You don't have the authority for this action"; break;
				}
			}
			$courseid = $line['id'];
			$name = $line['name'];
			$ekey = $line['enrollkey'];
			$hideicons = $line['hideicons'];
			$picicons = $line['picicons'];
			$allowunenroll = $line['allowunenroll'];
			$copyrights = $line['copyrights'];
			$msgset = $line['msgset']%5;
			$msgmonitor = (floor($line['msgset']/5))&1;
			$msgOnEnroll = (floor($line['msgset']/5))&2;
			$toolset = $line['toolset'];
			$avail = $line['available'];
			$lockaid = $line['lockaid'];
			$ltisecret = $line['ltisecret'];
			$theme = $line['theme'];
			$showlatepass = $line['showlatepass'];
			$istemplate = $line['istemplate'];
			$deflatepass = $line['deflatepass'];
			$deftime = $line['deftime'];
		} else {
			$courseid = _("Will be assigned when the course is created");
			$name = "Enter course name here";
			$ekey = "Enter enrollment key here";
			$hideicons = isset($CFG['CPS']['hideicons'])?$CFG['CPS']['hideicons'][0]:0;
			$picicons = isset($CFG['CPS']['picicons'])?$CFG['CPS']['picicons'][0]:0;
			$allowunenroll = isset($CFG['CPS']['allowunenroll'])?$CFG['CPS']['allowunenroll'][0]:0;
			//0 no un, 1 allow un;  0 allow enroll, 2 no enroll

			$copyrights = isset($CFG['CPS']['copyrights'])?$CFG['CPS']['copyrights'][0]:0;
			$msgset = isset($CFG['CPS']['msgset'])?$CFG['CPS']['msgset'][0]:0;
			$toolset = isset($CFG['CPS']['toolset'])?$CFG['CPS']['toolset'][0]:0;
			$msgmonitor = (floor($msgset/5))&1;
			$msgOnEnroll = (floor($msgset/5))&2;
			$msgset = $msgset%5;
			$theme = isset($CFG['CPS']['theme'])?$CFG['CPS']['theme'][0]:$defaultcoursetheme;
			$showlatepass = isset($CFG['CPS']['showlatepass'])?$CFG['CPS']['showlatepass'][0]:0;
			$istemplate = 0;
			$avail = 0;
			$lockaid = 0;
			$deftime = isset($CFG['CPS']['deftime'])?$CFG['CPS']['deftime'][0]:600;
			$deflatepass = isset($CFG['CPS']['deflatepass'])?$CFG['CPS']['deflatepass'][0]:0;
			$ltisecret = "";
		}
		$defetime = $deftime%10000;
		$hr = floor($defetime/60)%12;
		$min = $defetime%60;
		$am = ($defetime<12*60)?'am':'pm';
		$deftimedisp = (($hr==0)?12:$hr).':'.(($min<10)?'0':'').$min.' '.$am;
		if ($deftime>10000) {
			$defstime = floor($deftime/10000);
			$hr = floor($defstime/60)%12;
			$min = $defstime%60;
			$am = ($defstime<12*60)?'am':'pm';
			$defstimedisp = (($hr==0)?12:$hr).':'.(($min<10)?'0':'').$min.' '.$am;
		} else {
			$defstimedisp = $deftimedisp;
		}

		if (isset($_GET['cid'])) {
			$cid = Sanitize::courseId($_GET['cid']);
			echo "<div class=breadcrumb>$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; Course Settings</div>";
		}
		echo '<div id="headerforms" class="pagetitle"><h2>';
		if ($_GET['action']=='modify') {
			echo _('Course Settings');
		} else {
			echo _('Add New Course');
		}
		echo '</h2></div>';
		echo "<form method=post action=\"actions.php?from=$from&action={$_GET['action']}";
		if (isset($_GET['cid'])) {
			echo "&cid=$cid";
		}
		if ($_GET['action']=="modify") { echo "&id={$_GET['id']}"; }
		echo "\">\n";
		echo "<span class=form>Course ID:</span><span class=formright>$courseid</span><br class=form>\n";
		if ($isadminview) {
			echo '<span class="form">Owner:</span><span class="formright">';
			echo $udat['LastName'].', '.$udat['FirstName'].' ('.$udat['name'].')</span><br class="form"/>';
		}
		echo "<span class=form>Enter Course name:</span><input class=form type=text size=80 name=\"coursename\" value=\"$name\"><BR class=form>\n";
		echo "<span class=form>Enter Enrollment key:</span><input class=form type=text size=30 name=\"ekey\" value=\"$ekey\"><BR class=form>\n";
		echo '<span class=form>Available?</span><span class=formright>';
		echo '<input type="checkbox" name="stuavail" value="1" ';
		if (($avail&1)==0) { echo 'checked="checked"';}
		echo '/>Available to students</span><br class="form" />';
		if ($_GET['action']=="modify") {
			echo '<span class=form>Lock for assessment:</span><span class=formright><select name="lockaid">';
			echo '<option value="0" ';
			if ($lockaid==0) { echo 'selected="1"';}
			echo '>No lock</option>';
			//DB $query = "SELECT id,name FROM imas_assessments WHERE courseid='{$_GET['id']}' ORDER BY name";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB while ($row = mysql_fetch_row($result)) {
			$stm = $DBH->prepare("SELECT id,name FROM imas_assessments WHERE courseid=:courseid ORDER BY name");
			$stm->execute(array(':courseid'=>$_GET['id']));
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				echo "<option value=\"{$row[0]}\" ";
				if ($lockaid==$row[0]) { echo 'selected="1"';}
				echo ">{$row[1]}</option>";
			}
			echo '</select></span><br class="form"/>';
		}

		if (!isset($CFG['CPS']['deftime']) || $CFG['CPS']['deftime'][1]==1) {
			echo "<span class=form>Default start/end time for new items:</span><span class=formright>";
			echo 'Start: <input name="defstime" type="text" size="8" value="'.$defstimedisp.'"/>, ';
			echo 'end: <input name="deftime" type="text" size="8" value="'.$deftimedisp.'"/>';
			echo '</span><br class="form"/>';
		}

		if (!isset($CFG['CPS']['theme']) || $CFG['CPS']['theme'][1]==1) {
			echo "<span class=form>Theme:</span><span class=formright>";
			echo " <select name=\"theme\">";
			if (isset($CFG['CPS']['themelist'])) {
				$themes = explode(',',$CFG['CPS']['themelist']);
				if (isset($CFG['CPS']['themenames'])) {
					$themenames = explode(',',$CFG['CPS']['themenames']);
				}
			} else {
				$handle = opendir("../themes/");
				$themes = array();
				while (false !== ($file = readdir($handle))) {
					if (substr($file,strpos($file,'.'))=='.css') {
						$themes[] = $file;
					}
				}
				sort($themes);
			}
			foreach ($themes as $k=>$file) {
				echo "<option value=\"$file\" ";
				if ($file==$theme) { echo 'selected="selected"';}
				echo '>';
				if (isset($themenames)) {
					echo $themenames[$k];
				} else {
					echo substr($file,0,strpos($file,'.'));
				}
				echo '</option>';
			}

			echo " </select></span><br class=\"form\" />";
		}
		
		if (!isset($CFG['CPS']['unenroll']) || $CFG['CPS']['unenroll'][1]==1) {
			echo "<span class=form>Allow students to self-<u>un</u>enroll</span><span class=formright>";
			echo '<input type=radio name="allowunenroll" value="0" ';
			if (($allowunenroll&1)==0) { echo "checked=1";}
			echo '/> No <input type=radio name="allowunenroll" value="1" ';
			if (($allowunenroll&1)==1) { echo "checked=1";}
			echo '/> Yes </span><br class=form />';

			echo "<span class=form>Allow students to self-enroll</span><span class=formright>";
			echo '<input type=radio name="allowenroll" value="2" ';
			if (($allowunenroll&2)==2) { echo "checked=1";}
			echo '/> No <input type=radio name="allowenroll" value="0" ';
			if (($allowunenroll&2)==0) { echo "checked=1";}
			echo '/> Yes </span><br class=form />';
		}
		if (!isset($CFG['CPS']['copyrights']) || $CFG['CPS']['copyrights'][1]==1) {
			echo "<span class=form>Allow other instructors to copy course items:</span><span class=formright>";
			echo '<input type=radio name="copyrights" value="0" ';
			if ($copyrights==0) { echo "checked=1";}
			echo '/> Require enrollment key from everyone<br/> <input type=radio name="copyrights" value="1" ';
			if ($copyrights==1) { echo "checked=1";}
			echo '/> No key required for group members, require key from others <br/><input type=radio name="copyrights" value="2" ';
			if ($copyrights==2) { echo "checked=1";}
			echo '/> No key required from anyone</span><br class=form />';
		}
		if (!isset($CFG['CPS']['msgset']) || $CFG['CPS']['msgset'][1]==1) {
			echo "<span class=form>Message System:</span><span class=formright>";
			//0 on, 1 to instr, 2 to stu, 3 nosend, 4 off
			echo '<input type=radio name="msgset" value="0" ';
			if ($msgset==0) { echo "checked=1";}
			echo '/> On for send and receive<br/> <input type=radio name="msgset" value="1" ';
			if ($msgset==1) { echo "checked=1";}
			echo '/> On for receive, students can only send to instructor<br/> <input type=radio name="msgset" value="2" ';
			if ($msgset==2) { echo "checked=1";}
			echo '/> On for receive, students can only send to students<br/> <input type=radio name="msgset" value="3" ';
			if ($msgset==3) { echo "checked=1";}
			echo '/> On for receive, students cannot send<br/> <input type=radio name="msgset" value="4" ';
			if ($msgset==4) { echo "checked=1";}
			echo '/> Off <br/> <input type=checkbox name="msgmonitor" value="1" ';
			if ($msgmonitor==1) { echo "checked=1";}
			echo '/> Enable monitoring of student-to-student messages ';
			echo '</span><br class=form />';
		}
		if (!isset($CFG['CPS']['toolset']) || $CFG['CPS']['toolset'][1]==1) {
			echo "<span class=form>Navigation Links for Students:</span><span class=formright>";
			echo '<input type="checkbox" name="toolset-cal" value="1" ';
			if (($toolset&1)==0) { echo 'checked="checked"';}
			echo '> Calendar<br/>';

			echo '<input type="checkbox" name="toolset-forum" value="2" ';
			if (($toolset&2)==0) { echo 'checked="checked"';}
			echo '> Forum List';

			echo '</span><br class=form />';
		}

		if (!isset($CFG['CPS']['deflatepass']) || $CFG['CPS']['deflatepass'][1]==1) {
			echo '<span class="form">Auto-assign LatePasses on course enroll:</span><span class="formright">';
			echo '<input type="text" size="3" name="deflatepass" value="'.$deflatepass.'"/> LatePasses</span><br class="form" />';
		}
		if (!isset($CFG['CPS']['msgonenroll']) || $CFG['CPS']['msgonenroll'][1]==1) {
			echo '<span class="form">'._('Send teachers a message when students enroll').':</span><span class="formright">';
			echo '<input type="checkbox" name="msgonenroll" value="10" ';
			if ($msgOnEnroll>0) { echo 'checked="checked"';}
			echo '/> '._('Send').'</span><br class="form" />';
		}

		if (!isset($CFG['CPS']['showlatepass']) || $CFG['CPS']['showlatepass'][1]==1) {
			echo '<span class="form">Show remaining LatePasses on student gradebook page:</span><span class="formright">';
			echo '<input type=checkbox name="showlatepass" value="1" ';
			if ($showlatepass==1) {echo 'checked="checked"';};
			echo ' /></span><br class="form" />';
		}

		if (isset($enablebasiclti) && $enablebasiclti==true && isset($_GET['id'])) {
			echo '<span class="form">LTI access secret (max 10 chars; blank to not use)</span>';
			echo '<span class="formright"><input name="ltisecret" type="text" value="'.$ltisecret.'" maxlength="10"/> ';
			echo '<button type="button" onclick="document.getElementById(\'ltiurl\').style.display=\'\';this.parentNode.removeChild(this);">'._('Show LTI key and URL').'</button>';
			echo '<span id="ltiurl" style="display:none;">';
			if (isset($_GET['id'])) {
				echo '<br/>URL: '.$urlmode.Sanitize::domainNameWithPort($_SERVER['HTTP_HOST']).$imasroot.'/bltilaunch.php<br/>';
				echo 'Key: LTIkey_'.$_GET['id'].'_0 (to allow students to login directly to '.$installname.') or<br/>';
				echo 'Key: LTIkey_'.$_GET['id'].'_1 (to only allow access through the LMS )';
			} else {
				echo 'Course ID not yet set.';
			}
			echo '</span></span><br class="form" />';
		}
		if (($myspecialrights&1)==1 || ($myspecialrights&2)==2 || $myrights==100) {
			echo '<span class="form">Mark course as template?</span>';
			echo '<span class="formright">';
			if (($myspecialrights&1)==1 || $myrights==100) {
				echo '<input type=checkbox name="isgrptemplate" value="2" ';
				if (($istemplate&2)==2) {echo 'checked="checked"';};
				echo ' /> Mark as group template course';
			}
			if (($myspecialrights&2)==2 || $myrights==100) {
				echo '<br/><input type=checkbox name="istemplate" value="1" ';
				if (($istemplate&1)==1) {echo 'checked="checked"';};
				echo ' /> Mark as global template course';
			}
			if ($myrights==100) {
				echo '<br/><input type=checkbox name="isselfenroll" value="4" ';
				if (($istemplate&4)==4) {echo 'checked="checked"';};
				echo ' /> Mark as self-enroll course';
				if (isset($CFG['GEN']['guesttempaccts'])) {
					echo '<br/><input type=checkbox name="isguest" value="8" ';
					if (($istemplate&8)==8) {echo 'checked="checked"';};
					echo ' /> Mark as guest-access course';
				}
			}
			echo '</span><br class="form" />';
		}

		if (isset($CFG['CPS']['templateoncreate']) && $_GET['action']=='addcourse' ) {
			echo '<span class="form">Use content from a template course:</span>';
			echo '<span class="formright"><select name="usetemplate" onchange="templatepreviewupdate(this)">';
			echo '<option value="0" selected="selected">Start with blank course</option>';
			//$query = "SELECT ic.id,ic.name,ic.copyrights FROM imas_courses AS ic,imas_teachers WHERE imas_teachers.courseid=ic.id AND imas_teachers.userid='$templateuser' ORDER BY ic.name";
			$globalcourse = array();
			$groupcourse = array();
			$terms = array();
			//DB $query = "SELECT id,name,copyrights,istemplate,termsurl FROM imas_courses WHERE (istemplate&1)=1 AND available<4 AND copyrights=2 ORDER BY name";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB while ($row = mysql_fetch_row($result)) {
			$stm = $DBH->query("SELECT id,name,copyrights,istemplate,termsurl FROM imas_courses WHERE (istemplate&1)=1 AND available<4 AND copyrights=2 ORDER BY name");
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$globalcourse[$row[0]] = $row[1];
				if ($row[4]!='') {
					$terms[$row[0]] = $row[4];
				}
			}
			//DB $query = "SELECT ic.id,ic.name,ic.copyrights,ic.termsurl FROM imas_courses AS ic JOIN imas_users AS iu ON ic.ownerid=iu.id WHERE ";
			//DB $query .= "iu.groupid='$groupid' AND (ic.istemplate&2)=2 AND ic.copyrights>0 AND ic.available<4 ORDER BY ic.name";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB while ($row = mysql_fetch_row($result)) {
			$query = "SELECT ic.id,ic.name,ic.copyrights,ic.termsurl FROM imas_courses AS ic JOIN imas_users AS iu ON ic.ownerid=iu.id WHERE ";
			$query .= "iu.groupid=:groupid AND (ic.istemplate&2)=2 AND ic.copyrights>0 AND ic.available<4 ORDER BY ic.name";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':groupid'=>$groupid));
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$groupcourse[$row[0]] = $row[1];
				if ($row[3]!='') {
					$terms[$row[0]] = $row[3];
				}
			}
			if (count($groupcourse)>0) {
				echo '<optgroup label="Group Templates">';
				foreach ($groupcourse as $id=>$name) {
					echo '<option value="'.$id.'"';
					if (isset($terms[$id])) {
						echo ' data-termsurl="'.$terms[$id].'"';
					}
					echo '>'.$name.'</option>';
				}
				echo '</optgroup>';
			}
			if (count($globalcourse)>0) {
				if (count($groupcourse)>0) {
					echo '<optgroup label="System-wide Templates">';
				}
				foreach ($globalcourse as $id=>$name) {
					echo '<option value="'.$id.'"';
					if (isset($terms[$id])) {
						echo ' data-termsurl="'.$terms[$id].'"';
					}
					echo '>'.$name.'</option>';
				}
				if (count($groupcourse)>0) {
					echo '</optgroup>';
				}
			}
			echo '</select><span id="templatepreview"></span>';
			echo '<span id="termsbox" style="display:none;"><br/>';
			echo 'This course has additional <a target="_blank" href="" id="termsurl">Terms of Use</a> you must agree to before copying the course.<br/>';
			echo '<input type="checkbox" name="termsagree" /> I agree to the Terms of Use specified in the link above.</span>';
			echo '</span><br class="form" />';
			echo '<script type="text/javascript"> function templatepreviewupdate(el) {';
			echo '  var outel = document.getElementById("templatepreview");';
			echo '  if (el.value>0) {';
			echo '    outel.innerHTML = "<a href=\"'.$imasroot.'/course/course.php?cid="+el.value+"\" target=\"preview\">Preview</a>";';
			echo '    if ($(el).find(":selected").data("termsurl")) { $("#termsbox").show(); $("#termsurl").attr("href",$(el).find(":selected").data("termsurl")); }';
			echo '      else { $("#termsbox").hide(); }';
			echo '  } else {outel.innerHTML = "";}';
			echo '}</script>';
		}


		echo "<div class=submit><input type=submit value=Submit></div></form>\n";
		break;
	case "chgteachers":
		//DB $query = "SELECT name FROM imas_courses WHERE id='{$_GET['id']}'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $line = mysql_fetch_array($result, MYSQL_ASSOC);
		$stm = $DBH->prepare("SELECT name FROM imas_courses WHERE id=:id");
		$stm->execute(array(':id'=>$_GET['id']));
		$line = $stm->fetch(PDO::FETCH_ASSOC);
		echo '<div id="headerforms" class="pagetitle">';
		echo "<h2>{$line['name']}</h2>\n";
		echo '</div>';

		echo "<h4>Current Teachers:</h4>\n";
		//DB $query = "SELECT imas_users.FirstName,imas_users.LastName,imas_teachers.id,imas_teachers.userid ";
		//DB $query .= "FROM imas_users,imas_teachers WHERE imas_teachers.courseid='{$_GET['id']}' AND " ;
		//DB $query .= "imas_teachers.userid=imas_users.id ORDER BY imas_users.LastName;";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $num = mysql_num_rows($result);
		$query = "SELECT imas_users.FirstName,imas_users.LastName,imas_teachers.id,imas_teachers.userid ";
		$query .= "FROM imas_users,imas_teachers WHERE imas_teachers.courseid=:courseid AND " ;
		$query .= "imas_teachers.userid=imas_users.id ORDER BY imas_users.LastName;";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':courseid'=>$_GET['id']));
		$num = $stm->rowCount();
		echo '<form method="post" action="actions.php?from='.$from.'&action=remteacher&cid='.Sanitize::onlyInt($_GET['id']).'&tot='.$num.'">';
		echo 'With Selected: <input type="submit" value="Remove as Teacher"/>';
		echo "<table cellpadding=5>\n";
		$onlyone = ($num==1);
		//DB while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {

			if ($onlyone) {
				echo '<tr><td></td>';
			} else {
				echo '<tr><td><input type="checkbox" name="tid[]" value="'.$line['id'].'"/></td>';
			}

			printf("<td>%s, %s</td>", Sanitize::encodeStringForDisplay($line['LastName']),
				Sanitize::encodeStringForDisplay($line['FirstName']));
			if ($onlyone) {
				echo "<td></td></tr>";
			} else {
				echo "<td><A href=\"actions.php?from=$from&action=remteacher&cid=".Sanitize::onlyInt($_GET['id'])."&tid={$line['id']}\">Remove as Teacher</a></td></tr>\n";
			}
			$used[$line['userid']] = true;
		}
		echo "</table></form>\n";

		echo "<h4>Potential Teachers:</h4>\n";
		if ($myrights<100) {
			//DB $query = "SELECT id,FirstName,LastName,rights FROM imas_users WHERE rights>19 AND (rights<76 or rights>78) AND groupid='$groupid' ORDER BY LastName;";
			$stm = $DBH->prepare("SELECT id,FirstName,LastName,rights FROM imas_users WHERE rights>19 AND (rights<76 or rights>78) AND groupid=:groupid ORDER BY LastName;");
			$stm->execute(array(':groupid'=>$groupid));
		} else if ($myrights==100) {
			//DB $query = "SELECT id,FirstName,LastName,rights FROM imas_users WHERE rights>19 AND (rights<76 or rights>78) ORDER BY LastName;";
			$stm = $DBH->query("SELECT id,FirstName,LastName,rights FROM imas_users WHERE rights>19 AND (rights<76 or rights>78) ORDER BY LastName;");
		}
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		echo '<form method="post" action="actions.php?from='.$from.'&action=addteacher&cid='.Sanitize::onlyInt($_GET['id']).'">';
		echo 'With Selected: <input type="submit" value="Add as Teacher"/>';
		echo "<table cellpadding=5>\n";
		//DB while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
			if (trim($line['LastName'])=='' && trim($line['FirstName'])=='') {continue;}
			if ($used[$line['id']]!=true) {
				//if ($line['rights']<20) { $type = "Tutor/TA/Proctor";} else {$type = "Teacher";}
				echo '<tr><td><input type="checkbox" name="atid[]" value="'.$line['id'].'"/></td>';
				printf("<td>%s, %s </td> ", Sanitize::encodeStringForDisplay($line['LastName']),
					Sanitize::encodeStringForDisplay($line['FirstName']));
				echo "<td><a href=\"actions.php?from=$from&action=addteacher&cid=".Sanitize::onlyInt($_GET['id'])."&tid={$line['id']}\">Add as Teacher</a></td></tr>\n";
			}
		}
		echo "</table></form>\n";
		echo "<p><input type=button value=\"Done\" onclick=\"window.location='$backloc'\" /></p>\n";
		break;
	case "importmacros":
		echo "<h3>Install Macro File</h3>\n";
		echo "<p><b>Warning:</b> Macro Files have a large security risk.  <b>Only install macro files from a trusted source</b></p>\n";
		echo "<p><b>Warning:</b> Install will overwrite any existing macro file of the same name</p>\n";
		echo "<form enctype=\"multipart/form-data\" method=post action=\"actions.php?from=$from&action=importmacros\">\n";
		echo "<input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"300000\" />\n";
		echo "<span class=form>Import file: </span><span class=formright><input name=\"userfile\" type=\"file\" /></span><br class=form>\n";
		echo "<div class=submit><input type=submit value=\"Submit\"></div>\n";
		echo "</form>\n";
		break;

	case "importqimages":
		echo "<h3>Install Question Images</h3>\n";
		echo "<p><b>Warning:</b> This has a large security risk.  <b>Only install question images from a trusted source</b>, and where you've verified the archive only contains images.</p>\n";
		echo "<p><b>Warning:</b> Install will ignore files with the same filename as existing files.</p>\n";
		echo "<form enctype=\"multipart/form-data\" method=post action=\"actions.php?from=$from&action=importqimages\">\n";
		echo "<input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"5000000\" />\n";
		echo "<span class=form>Import file: </span><span class=formright><input name=\"userfile\" type=\"file\" /></span><br class=form>\n";
		echo "<div class=submit><input type=submit value=\"Submit\"></div>\n";
		echo "</form>\n";
		break;
	case "importcoursefiles":
		echo "<h3>Install Course files</h3>\n";
		echo "<p><b>Warning:</b> This has a large security risk.  <b>Only install course files from a trusted source</b>, and where you've verified the archive only contains regular files (no PHP files).</p>\n";
		echo "<p><b>Warning:</b> Install will ignore files with the same filename as existing files.</p>\n";
		echo "<form enctype=\"multipart/form-data\" method=post action=\"actions.php?from=$from&action=importcoursefiles\">\n";
		echo "<input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"10000000\" />\n";
		echo "<span class=form>Import file: </span><span class=formright><input name=\"userfile\" type=\"file\" /></span><br class=form>\n";
		echo "<div class=submit><input type=submit value=\"Submit\"></div>\n";
		echo "</form>\n";
		break;
	case "transfer":
		echo '<div id="headerforms" class="pagetitle">';
		echo "<h3>Transfer Course Ownership</h3>\n";
		echo '</div>';
		echo "<form method=post action=\"actions.php?from=$from&action=transfer&id={$_GET['id']}\">\n";
		echo "Transfer course ownership to: <select name=newowner>\n";
		if ($myrights < 100) {
			//DB $query = "SELECT id,FirstName,LastName FROM imas_users WHERE rights>19 AND groupid='$groupid' ORDER BY LastName";
			$stm = $DBH->prepare("SELECT id,FirstName,LastName FROM imas_users WHERE rights>19 AND groupid=:groupid ORDER BY LastName");
			$stm->execute(array(':groupid'=>$groupid));
		} else {
			//DB $query = "SELECT id,FirstName,LastName FROM imas_users WHERE rights>19 ORDER BY LastName";
			$stm = $DBH->query("SELECT id,FirstName,LastName FROM imas_users WHERE rights>19 ORDER BY LastName");
		}
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB while ($row = mysql_fetch_row($result)) {
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			echo "<option value=\"$row[0]\">$row[2], $row[1]</option>\n";
		}
		echo "</select>\n";
		echo "<p><input type=submit value=\"Transfer\">\n";
		echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='$backloc'\"></p>\n";
		echo "</form>\n";
		break;
	case "deloldusers":
		echo "<h3>Delete Old Users</h3>\n";
		echo "<form method=post action=\"actions.php?from=$from&action=deloldusers\">\n";
		echo "<span class=form>Delete Users older than:</span>";
		echo "<span class=formright><input type=text name=months size=4 value=\"6\"/> Months</span><br class=form>\n";
		echo "<span class=form>Delete Who:</span>";
		echo "<span class=formright><input type=radio name=who value=\"students\" CHECKED>Students<br/>\n";
		echo "<input type=radio name=who value=\"all\">Everyone but Admins</span><br class=form>\n";
		echo "<div class=submit><input type=submit value=\"Delete\"></div>\n";
		echo "</form>\n";
		break;
	case "listltidomaincred":
		if ($myrights<100) { echo "not allowed"; exit;}
		echo '<div id="headerforms" class="pagetitle">';
		echo "<h3>Modify LTI Domain Credentials</h3>\n";
		echo '</div>';
		echo "<table><tr><th>Domain</th><th>Key</th><th>Can create Instructors?</th><th>Modify</th><th>Delete</th></tr>\n";
		//DB $query = "SELECT id,email,SID,rights FROM imas_users WHERE rights=11 OR rights=76 OR rights=77";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB while ($row = mysql_fetch_row($result)) {
		$stm = $DBH->query("SELECT id,email,SID,rights FROM imas_users WHERE rights=11 OR rights=76 OR rights=77");
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			echo "<tr><td>{$row[1]}</td><td>{$row[2]}</td>";
			if ($row[3]==76) {
				echo '<td>Yes</td>';
			} else {
				echo '<td>No</td>';
			}
			echo "<td><a href=\"forms.php?action=modltidomaincred&id={$row[0]}\">Modify</a></td>\n";
			if ($row[0]==0) {
				echo "<td></td>";
			} else {
				echo "<td><a href=\"actions.php?from=$from&action=delltidomaincred&id={$row[0]}\" onclick=\"return confirm('Are you sure?');\">Delete</a></td>\n";
			}
			echo "</tr>\n";
		}
		echo "</table>\n";
		echo "<form method=post action=\"actions.php?from=$from&action=modltidomaincred&id=new\">\n";
		echo "<p>Add new LTI key/secret: <br/>";
		echo "Domain: <input type=text name=\"ltidomain\" size=20><br/>\n";
		echo "Key: <input type=text name=\"ltikey\" size=20><br/>\n";
		echo "Secret: <input type=text name=\"ltisecret\" size=20><br/>\n";
		echo "Can create instructors: <select name=\"createinstr\"><option value=\"11\" selected=\"selected\">No</option>";
		echo "<option value=\"76\">Yes, and creates $installname login</option>";
		//echo "<option value=\"77\">Yes, with access via LMS only</option>
		echo "</select><br/>\n";
		echo 'Associate with group <select name="groupid"><option value="0">Default</option>';
		//DB $query = "SELECT id,name FROM imas_groups ORDER BY name";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB while ($row = mysql_fetch_row($result)) {
		$stm = $DBH->query("SELECT id,name FROM imas_groups ORDER BY name");
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			echo '<option value="'.$row[0].'">'.$row[1].'</option>';
		}
		echo '</select><br/>';
		echo "<input type=submit value=\"Add LTI Credentials\"></p>\n";
		echo "</form>\n";
		break;
	case "modltidomaincred":
		if ($myrights<100) { echo "not allowed"; exit;}
		echo '<div id="headerforms" class="pagetitle">';
		echo "<h3>Modify LTI Domain Credentials</h3>\n";
		echo '</div>';
		//DB $query = "SELECT id,email,SID,password,rights,groupid FROM imas_users WHERE id='{$_GET['id']}'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $row = mysql_fetch_row($result);
		$stm = $DBH->prepare("SELECT id,email,SID,password,rights,groupid FROM imas_users WHERE id=:id");
		$stm->execute(array(':id'=>$_GET['id']));
		$row = $stm->fetch(PDO::FETCH_NUM);
		echo "<form method=post action=\"actions.php?from=$from&action=modltidomaincred&id={$row[0]}\">\n";
		echo "Modify LTI key/secret: <br/>";
		echo "Domain: <input type=text name=\"ltidomain\" value=\"{$row[1]}\" size=20><br/>\n";
		echo "Key: <input type=text name=\"ltikey\" value=\"{$row[2]}\" size=20><br/>\n";
		echo "Secret: <input type=text name=\"ltisecret\"  value=\"{$row[3]}\" size=20><br/>\n";
		echo "Can create instructors: <select name=\"createinstr\"><option value=\"11\" ";
		if ($row[4]==11) {echo 'selected="selected"';}
		echo ">No</option><option value=\"76\" ";
		if ($row[4]==76) {echo 'selected="selected"';}
		echo ">Yes</option></select><br/>\n";
		echo 'Associate with group <select name="groupid"><option value="0">Default</option>';
		//DB $query = "SELECT id,name FROM imas_groups ORDER BY name";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB while ($r = mysql_fetch_row($result)) {
		$stm = $DBH->query("SELECT id,name FROM imas_groups ORDER BY name");
		while ($r = $stm->fetch(PDO::FETCH_NUM)) {
			echo '<option value="'.$r[0].'"';
			if ($r[0]==$row[5]) { echo ' selected="selected"';}
			echo '>'.$r[1].'</option>';
		}
		echo '</select><br/>';
		echo "<input type=submit value=\"Update LTI Credentials\">\n";
		echo "</form>\n";
		break;

	case "listgroups":
		echo '<div id="headerforms" class="pagetitle">';
		echo "<h3>Modify Groups</h3>\n";
		echo '</div>';
		echo "<table><tr><th>Group Name</th><th>Modify</th><th>Delete</th></tr>\n";
		//DB $query = "SELECT id,name FROM imas_groups ORDER BY name";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB while ($row = mysql_fetch_row($result)) {
		$stm = $DBH->query("SELECT id,name FROM imas_groups ORDER BY name");
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			echo "<tr><td>{$row[1]}</td>";
			echo "<td><a href=\"forms.php?action=modgroup&id={$row[0]}\">Modify</a></td>\n";
			if ($row[0]==0) {
				echo "<td></td>";
			} else {
				echo "<td><a href=\"actions.php?from=$from&action=delgroup&id={$row[0]}\" onclick=\"return confirm('Are you SURE you want to delete this group?');\">Delete</a></td>\n";
			}
			echo "</tr>\n";
		}
		echo "</table>\n";
		echo "<form method=post action=\"actions.php?from=$from&action=addgroup\">\n";
		echo "Add new group: <input type=text name=gpname id=gpname size=50><br/>\n";
		echo "<input type=submit value=\"Add Group\">\n";
		echo "</form>\n";
		break;
	case "modgroup":
		echo '<div id="headerforms" class="pagetitle"><h2>Rename Instructor Group</h2></div>';
		//DB $query = "SELECT name,parent FROM imas_groups WHERE id='{$_GET['id']}'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB list($gpname,$parent) = mysql_fetch_row($result);
		$stm = $DBH->prepare("SELECT name,parent FROM imas_groups WHERE id=:id");
		$stm->execute(array(':id'=>$_GET['id']));
		list($gpname,$parent) = $stm->fetch(PDO::FETCH_NUM);

		echo "<form method=post action=\"actions.php?from=$from&action=modgroup&id={$_GET['id']}\">\n";
		echo "Group name: <input type=text size=50 name=gpname id=gpname value=\"$gpname\"><br/>\n";
		echo 'Parent: <select name="parentid"><option value="0" ';
		if ($parent==0) { echo ' selected="selected"';}
		echo '>None</option>';
		//DB $query = "SELECT id,name FROM imas_groups ORDER BY name";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB while ($r = mysql_fetch_row($result)) {
		$stm = $DBH->query("SELECT id,name FROM imas_groups ORDER BY name");
		while ($r = $stm->fetch(PDO::FETCH_NUM)) {
			echo '<option value="'.$r[0].'"';
			if ($r[0]==$parent) { echo ' selected="selected"';}
			echo '>'.$r[1].'</option>';
		}
		echo '</select><br/>';
		echo "<input type=submit value=\"Update Group\">\n";
		echo "</form>\n";
		break;
	case "removediag":
		echo "<p>Are you sure you want to delete this diagnostic?  This does not delete the connected course and does not remove students or their scores.</p>\n";
		echo "<p><input type=button value=\"Delete\" onclick=\"window.location='actions.php?from=$from&action=removediag&id={$_GET['id']}'\">\n";
		echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='$backloc'\"></p>\n";
		break;
}

require("../footer.php");
?>

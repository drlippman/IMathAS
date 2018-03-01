<?php
//IMathAS:  Admin forms
//(c) 2006 David Lippman
require("../init.php");
$placeinhead = '<script type="text/javascript" src="'.$imasroot.'/javascript/jquery.validate.min.js?v=122917"></script>';
require("../header.php");
require("../includes/htmlutil.php");

$from = 'admin2';
$backloc = 'admin2.php';
if (!empty($_GET['from'])) {
	if ($_GET['from']=='home') {
		$from = 'home';
		$backloc = '../index.php';
	} else if ($_GET['from']=='admin2') {
		$from = 'admin2';
		$backloc = 'admin2.php';
	} else if (substr($_GET['from'],0,2)=='ud') {
		$userdetailsuid = Sanitize::onlyInt(substr($_GET['from'],2));
		$from = 'ud'.$userdetailsuid;
		$backloc = 'userdetails.php?id='.Sanitize::encodeUrlParam($userdetailsuid);
	} else if (substr($_GET['from'],0,2)=='gd') {
		$groupdetailsgid = Sanitize::onlyInt(substr($_GET['from'],2));
		$from = 'gd'.$groupdetailsgid;
		$backloc = 'admin2.php?groupdetails='.Sanitize::encodeUrlParam($groupdetailsgid);
	}
}
if (!isset($_GET['cid'])) {
	echo "<div class=breadcrumb>$breadcrumbbase ";
	if ($from == 'admin') {
		echo "<a href=\"admin2.php\">Admin</a> &gt; ";
	} else if ($from == 'admin2') {
		echo '<a href="admin2.php">'._('Admin').'</a> &gt; ';
	} else if (substr($_GET['from'],0,2)=='ud') {
		echo '<a href="admin2.php">'._('Admin').'</a> &gt; <a href="'.$backloc.'">'._('User Details').'</a> &gt; ';
	} else if (substr($_GET['from'],0,2)=='gd') {
		echo '<a href="admin2.php">'._('Admin').'</a> &gt; <a href="'.$backloc.'">'._('Group Details').'</a> &gt; ';
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
		echo "<p>Are you sure you want to delete the course <b>".Sanitize::encodeStringForDisplay($name)."</b>?</p>\n";
		echo '<form method="POST" action="actions.php?from='.Sanitize::encodeUrlParam($from).'&id='.Sanitize::encodeUrlParam($_GET['id']).'">';
		echo '<p><button type=submit name="action" value="delete">'._('Delete').'</button>';
		echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='".Sanitize::encodeStringForJavascript($backloc)."'\"></p>\n";
		echo '</form>';
		break;
	case "deladmin":
		if ($myrights < 75) { echo "You don't have the authority for this action"; break;}
		$stm = $DBH->prepare("SELECT FirstName,LastName,SID FROM imas_users WHERE id=:id");
		$stm->execute(array(':id'=>$_GET['id']));
		$line = $stm->fetch(PDO::FETCH_ASSOC);
		echo "<p>Are you sure you want to delete this user, <b>";
		printf("%s, %s (%s)", Sanitize::encodeStringForDisplay($line['LastName']), Sanitize::encodeStringForDisplay($line['FirstName']), Sanitize::encodeStringForDisplay($line['SID']));
		echo "</b>?</p>\n";
		echo '<form method="POST" action="actions.php?from='.Sanitize::encodeUrlParam($from).'&id='.Sanitize::encodeUrlParam($_GET['id']).'">';
		echo '<p><button type=submit name="action" value="deladmin">'._('Delete').'</button>';
		echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='".Sanitize::encodeStringForJavascript($backloc)."'\"></p>\n";
		echo '</form>';
		break;
	case "chgrights":
	case "newadmin":
		if ($myrights < 75 && ($myspecialrights&16)!=16 && ($myspecialrights&32)!=32) { echo "You don't have the authority for this action"; break;}
		echo "<form method=post id=userform class=limitaftervalidate action=\"actions.php?from=".Sanitize::encodeUrlParam($from);
		if ($_GET['action']=="chgrights") { echo "&id=".Sanitize::encodeUrlParam($_GET['id']); }
		echo "\">\n";
		echo '<input type=hidden name=action value="'.Sanitize::encodeStringForDisplay($_GET['action']).'" />';
		if ($_GET['action'] == "newadmin") {
			echo '<div class="pagetitle"><h2>'._('New User').'</h2></div>';
			$oldgroup = (isset($_GET['group'])?Sanitize::onlyInt($_GET['group']):0);
			$oldrights = 10;
		} else {
			//DB $query = "SELECT FirstName,LastName,rights,groupid,specialrights FROM imas_users WHERE id='{$_GET['id']}'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $line = mysql_fetch_array($result, MYSQL_ASSOC);
			$stm = $DBH->prepare("SELECT SID,FirstName,LastName,email,rights,groupid,specialrights FROM imas_users WHERE id=:id");
			$stm->execute(array(':id'=>$_GET['id']));
			$line = $stm->fetch(PDO::FETCH_ASSOC);
			printf("<div class=pagetitle><h2>%s %s</h2></div>\n", Sanitize::encodeStringForDisplay($line['FirstName']),
				Sanitize::encodeStringForDisplay($line['LastName']));
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
					$("input[name^=specialrights]").prop("checked",false);
					$("#specialrights1,#specialrights4,#specialrights8,#specialrights16").prop("checked",true);
				} else if (selrights==100) {
					$("input[name^=specialrights]").prop("checked",true);
				}
			}
			function chknewgroup(el) {
				$("#newgroup").toggle(el.value==-1);
			}
			$(function() {
				$("input[name=newrights]").on("change", onrightschg);
				});
			function checkgroupisnew() {
				var proposedgroup = $("input[name=newgroupname]").val().replace(/^\s+/,"").replace(/\s+$/,"").replace(/\s+/g," ").toLowerCase();
				$("#group").find("option").each(function(i) {
					if ($(this).text().toLowerCase()==proposedgroup) {
						alert("That group name already exists!");
						$("#group").val(this.value).trigger("change");
						break;
					}
				});
			}
			</script>';
		echo "<span class=form>Username:</span>  <input class=form type=text size=40 name=SID ";
		if ($_GET['action'] != "newadmin") {
			echo 'value="'.Sanitize::encodeStringForDisplay($line['SID']).'"';
		}
		echo "><BR class=form>\n";
		echo "<span class=form>First Name:</span> <input class=form type=text size=40 name=firstname ";
		if ($_GET['action'] != "newadmin") {
			echo 'value="'.Sanitize::encodeStringForDisplay($line['FirstName']).'"';
		}
		echo "><BR class=form>\n";
		echo "<span class=form>Last Name:</span> <input class=form type=text size=40 name=lastname ";
		if ($_GET['action'] != "newadmin") {
			echo 'value="'.Sanitize::encodeStringForDisplay($line['LastName']).'"';
		}
		echo "><BR class=form>\n";
		echo "<span class=form>Email:</span> <input class=form type=text size=40 name=email ";
		if ($_GET['action'] != "newadmin") {
			echo 'value="'.Sanitize::encodeStringForDisplay($line['email']).'"';
		}
		echo "><BR class=form>\n";
		if ($_GET['action'] == "newadmin") {
			echo '<span class="form">Password:</span> <input class="form" type="text" size="40" name="pw1"/><br class="form"/>';
		} else {
			echo '<span class=form>Reset password?</span><span class=formright><input type=checkbox name="doresetpw" value="1" onclick="$(\'#newpwwrap\').toggle(this.checked)"/> ';
			echo '<span id="newpwwrap" style="display:none">Set temporary password to: <input type=text size=20 name="newpassword" /></span></span><br class=form />';
		}

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
		if ($myrights>=75) {
			echo "<input type=radio name=\"newrights\" value=\"75\" ";
			if ($oldrights == 75) {echo "CHECKED";}
			echo "> Group Admin <BR>\n";
		}
		if ($myrights==100) {
			echo "<input type=radio name=\"newrights\" value=\"100\" ";
			if ($oldrights == 100) {echo "CHECKED";}
			echo "> Full Admin";
		}
		echo "</span><BR class=form>\n";
		echo '<span class="form">Task Rights:</span><span class="formright">';
		if ($myrights==100 || ($myrights>=75 && ($myspecialrights&1)==1)) {
			echo '<input type="checkbox" name="specialrights1" id="specialrights1" ';
			if (($oldspecialrights&1)==1) { echo 'checked';}
			echo '><label for="specialrights1">Designate group template courses</label><br/>';
		}
		if ($myrights==100) {
			echo '<input type="checkbox" name="specialrights2" id="specialrights2" ';
			if (($oldspecialrights&2)==2) { echo 'checked';}
			echo '><label for="specialrights2">Designate global template courses</label><br/>';
		}
		if ($myrights==100 || ($myrights>=75 && ($myspecialrights&4)==4)) {
			echo '<input type="checkbox" name="specialrights4" id="specialrights4" ';
			if (($oldspecialrights&4)==4) { echo 'checked';}
			echo '><label for="specialrights4">Create Diagnostic logins</label><br/>';
		}
		if (($myrights==100 || ($myrights>=75 && ($myspecialrights&8)==8)) && !$allownongrouplibs) {
			echo '<input type="checkbox" name="specialrights8" id="specialrights8" ';
			if (($oldspecialrights&8)==8) { echo 'checked';}
			echo '><label for="specialrights8">Create public (open to all) question libraries</label><br/>';
		}
		if ($myrights>=75) {
			echo '<input type="checkbox" name="specialrights16" id="specialrights16" ';
			if (($oldspecialrights&16)==16) { echo 'checked';}
			echo '><label for="specialrights16">Create new instructor accounts (own group)</label><br/>';
		}
		if ($myrights==100) {
			echo '<input type="checkbox" name="specialrights32" id="specialrights32" ';
			if (($oldspecialrights&32)==32) { echo 'checked';}
			echo '><label for="specialrights32">Create new instructor accounts (any group)</label><br/>';

			echo '<input type="checkbox" name="specialrights64" id="specialrights64" ';
			if (($oldspecialrights&64)==64) { echo 'checked';}
			echo '><label for="specialrights64">Approve instructor account requests</label><br/>';
		}
		echo '</span><br class="form"/>';

		if ($myrights == 100 || ($myspecialrights&32)==32) {
			echo "<span class=form>Assign to group: </span>";
			echo "<span class=formright><select name=\"group\" id=\"group\" onchange=\"chknewgroup(this)\">";
			echo '<option value="-1">New Group</option>';
			echo "<option value=0 ";
			if ($oldgroup==0) {
				echo "selected=1";
			}
			echo ">Default</option>\n";
			//DB $query = "SELECT id,name FROM imas_groups ORDER BY name";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB while ($row = mysql_fetch_row($result)) {
			$stm = $DBH->query("SELECT id,name FROM imas_groups ORDER BY name");
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				printf('<option value="%d" ', Sanitize::onlyInt($row[0]));
				if ($oldgroup==$row[0]) {
					echo "selected=1";
				}
				$row[1] = preg_replace('/\s+/', ' ', trim($row[1]));
				printf(">%s</option>\n", Sanitize::encodeStringForDisplay($row[1]));
			}
			echo "</select>";
			echo '<span id="newgroup" style="display:none"><br/>New group name: ';
			echo ' <input name=newgroupname size=20 onblur="checkgroupisnew()"/></span>';
			echo "</span><br class=form />\n";
		}
		echo "<div class=submit><input type=submit value=Save></div></form>\n";
		if ($_GET['action'] == "newadmin") {
			require_once("../includes/newusercommon.php");
			showNewUserValidation("userform");
		}
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
				$stm = $DBH->prepare("SELECT iu.FirstName, iu.LastName, iu.groupid, ig.name FROM imas_users AS iu LEFT JOIN imas_groups AS ig ON ig.id=iu.groupid WHERE iu.id=:id");
				$stm->execute(array(':id'=>$line['ownerid']));
				$udat = $stm->fetch(PDO::FETCH_ASSOC);
				if ($udat['groupid']==0) {
					$udat['name'] = _('Default Group');
				}
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
			$jsondata = json_decode($line['jsondata'], true);
			if ($jsondata===null || !isset($jsondata['browser'])) {
				$browser = array();
			} else {
				$browser = $jsondata['browser'];
			}
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
			$browser = array();
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
		echo "<form method=post action=\"actions.php?from=".Sanitize::encodeUrlParam($from);
		if (isset($_GET['cid'])) {
			echo "&cid=$cid";
		}
		if ($_GET['action']=="modify") { echo "&id=".Sanitize::encodeUrlParam($_GET['id']); }
		echo "\">\n";
		echo '<script type="text/javascript">
		$(function() {
			$("form").on("submit",function(e) {
				var needsgrp = $("input[name=isgrptemplate]:checked").length;
				var needsall = $("input[name=istemplate]:checked,input[name=isselfenroll]:checked").length;

				var copyrights = $("input[name=copyrights]:checked").val();
				var ok = true;
				if (copyrights<2 && $("input[name=promote]:checked").length>0) {
					alert(_("Promoting a course requires setting the copy permissions to: no key required for anyone"));
					ok = false;
				}
				if (copyrights<2 && needsall>0) {
					alert(_("Setting a course as a template or self enroll requires setting the copy permissions to: no key required for anyone"));
					ok = false;
				}
				if (copyrights<1 && needsgrp) {
					alert(_("Setting a course as a group template requires setting the copy permissions to: no key required for group (or no key required for anyone)"));
					ok = false;
				}
				if (!ok) {
					setTimeout(function() {
						$("form").removeClass("submitted").removeClass("submitted2");
					}, 500);
					return false;
				}
			});
		})
		</script>';
		echo '<input type=hidden name=action value="'.Sanitize::encodeStringForDisplay($_GET['action']) .'" />';
		echo "<span class=form>Course ID:</span><span class=formright>".Sanitize::encodeStringForDisplay($courseid)."</span><br class=form>\n";
		if ($isadminview) {
			echo '<span class="form">Owner:</span><span class="formright">';
			printf('%s, %s (%s)</span><br class="form"/>', Sanitize::encodeStringForDisplay($udat['LastName']),
				Sanitize::encodeStringForDisplay($udat['FirstName']), Sanitize::encodeStringForDisplay($udat['name']));
		}
		echo "<span class=form>Enter Course name:</span><input class=form type=text size=80 name=\"coursename\" value=\"".Sanitize::encodeStringForDisplay($name)."\"><BR class=form>\n";
		echo "<span class=form>Enter Enrollment key:</span><input class=form type=text size=30 name=\"ekey\" value=\"".Sanitize::encodeStringForDisplay($ekey)."\"><BR class=form>\n";
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
				printf('<option value="%d" ', Sanitize::onlyInt($row[0]));
				if ($lockaid==$row[0]) { echo 'selected="1"';}
				printf(">%s</option>", Sanitize::encodeStringForDisplay($row[1]));
			}
			echo '</select></span><br class="form"/>';
		}

		if (!isset($CFG['CPS']['deftime']) || $CFG['CPS']['deftime'][1]==1) {
			echo "<span class=form>Default start/end time for new items:</span><span class=formright>";
			echo 'Start: <input name="defstime" type="text" size="8" value="'.Sanitize::encodeStringForDisplay($defstimedisp).'"/>, ';
			echo 'end: <input name="deftime" type="text" size="8" value="'.Sanitize::encodeStringForDisplay($deftimedisp).'"/>';
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
				printf('<option value="%s" ', Sanitize::encodeStringForDisplay($file));
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
			echo '<input type="text" size="3" name="deflatepass" value="'.Sanitize::encodeStringForDisplay($deflatepass).'"/> LatePasses</span><br class="form" />';
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
			echo '<span class="formright"><input name="ltisecret" type="text" value="'.Sanitize::encodeStringForDisplay($ltisecret).'" maxlength="10"/> ';
			echo '<button type="button" onclick="document.getElementById(\'ltiurl\').style.display=\'\';this.parentNode.removeChild(this);">'._('Show LTI key and URL').'</button>';
			echo '<span id="ltiurl" style="display:none;">';
			if (isset($_GET['id'])) {
				echo '<br/>URL: ' . $GLOBALS['basesiteurl'] . '/bltilaunch.php<br/>';
				echo 'Key: LTIkey_'.Sanitize::encodeStringForDisplay($_GET['id']).'_0 (to allow students to login directly to '.$installname.') or<br/>';
				echo 'Key: LTIkey_'.Sanitize::encodeStringForDisplay($_GET['id']).'_1 (to only allow access through the LMS )';
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

		if (!isset($CFG['coursebrowserRightsToPromote'])) {
			$CFG['coursebrowserRightsToPromote'] = 40;
		}
		if ($_GET['action']=='modify' && isset($CFG['coursebrowser']) && $CFG['coursebrowserRightsToPromote']<=$myrights) {
			$browserprops = json_decode(file_get_contents(__DIR__.'/../javascript/'.$CFG['coursebrowser'], false, null, 25), true);
			echo '<script type="text/javascript">
				function changepromote() {
					$("#promotediv").toggle($("input[name=promote]").prop("checked"));
					//TODO: Add required to name and descrip field on show
				}
				function chgother(el) {
					var id = el.id;
					if (el.value != "other") {
						$("#"+id+"otherwrap").hide();
					} else {
						$("#"+id+"otherwrap").show();
					}
				}
			</script>';

			echo '<span class="form">'._('Promote Course').'</span>';
			echo '<span class=formright><label><input type=checkbox name=promote value=1 onchange="changepromote()" ';
			if (($istemplate&16)==16) {echo 'checked="checked"';};
			echo ' /> '._('Promote in Course Browser').'</label></span><br class="form">';
			echo '<fieldset id=promotediv '.((($istemplate&16)==16)?'':'style="display:none"').'>';
			echo '<legend>'._('Course Browser Settings').'</legend>';
			echo '<p class="noticetext">'._('Before sharing your course, check to ensure that it only contains materials you created or have the rights to share.');
			echo ' '._('Your course should contain no commercial, copyrighted content, including textbook pages, activites, test bank items, etc.');
			echo ' '._('Openly licensed material, clearly marked with an open license, is fine to include.');
			echo ' '._('If sharing your own materials (textbook, activities, paper assessments), please mark the materials with an open license, or include a blanket license statement somewhere in the course.');
			echo '</p><p>';

			if (empty($browser['name'])) {
				$browser['name'] = trim($name);
			}
			if (empty($browser['owner'])) {
				if (!isset($udat)) {
					$stm = $DBH->prepare("SELECT iu.FirstName, iu.LastName, ig.name FROM imas_users AS iu JOIN imas_groups AS ig ON ig.id=iu.groupid WHERE iu.id=:id");
					$stm->execute(array(':id'=>$userid));
					$udat = $stm->fetch(PDO::FETCH_ASSOC);
				}
				$browser['owner'] = $udat['FirstName'].' '.$udat['LastName'].' ('.$udat['name'].')';
			}

			foreach ($browserprops as $propname=>$propvals) {
				if (!empty($propvals['fixed'])) { continue; }
				echo '<span class=form>'.$propvals['name'];
				if (!empty($propvals['subname'])) {
					echo '<br/><span class=small>'.$propvals['subname'].'</span>';
				}
				echo '</span>';
				echo '<span class=formright>';
				if (isset($propvals['options'])) {  //is select
					if (!empty($propvals['multi'])) { //checkboxes
						if (isset($browser[$propname]) && !is_array($browser[$propname])) {
							$browser[$propname] = array($browser[$propname]);
						}
						foreach ($propvals['options'] as $k=>$v) {
							echo '<label><input type=checkbox name="browser'.$propname.'[]" value="'. Sanitize::encodeStringForDisplay($k).'" ';
							echo ((isset($browser[$propname]) && in_array($k,$browser[$propname]))?'checked':'').' /> '.Sanitize::encodeStringForDisplay($v).'</label><br/>';
						}
					} else { //single select
						$ingroup = false;
						echo '<select name="browser'.$propname.'" id="browser'.$propname.'"';
						if (isset($propvals['options']['other'])) {
							echo ' onchange="chgother(this)"';
						}
						echo '>';

						foreach ($propvals['options'] as $k=>$v) {
							if (substr($k,0,5)=='group') {
								if ($ingroup) {
									echo '</optgroup>';
								}
								echo '<optgroup label="'.Sanitize::encodeStringForDisplay($v).'">';
								$ingroup = true;
							} else {
								echo '<option value="'.Sanitize::encodeStringForDisplay($k).'"';
								if ($k==$browser[$propname]) { echo ' selected';}
								echo '>';
								echo Sanitize::encodeStringForDisplay($v).'</option>';
							}
						}
						if ($ingroup) {
							echo '</optgroup>';
						}
						echo '</select>';

						if (isset($propvals['options']['other'])) {
							echo '<span id="browser'.$propname.'otherwrap" '.($browser[$propname]!='other'?'style="display:none"':'').'>';
							echo '<br/>Other: <input type=text size=40 name="browser'.$propname.'other" value="'.($browser[$propname]=='other'?Sanitize::encodeStringForDisplay($browser[$propname.'other']):'').'"></span>';
						}
					}
				} else if ($propvals['type']=='string') {
					echo '<input type=text name="browser'.$propname.'" size=50 value="'.Sanitize::encodeStringForDisplay($browser[$propname]).'" />';
				} else if ($propvals['type']=='textarea') {
					echo '<textarea rows=6 cols=70 name=browser'.$propname.'>'.Sanitize::encodeStringForDisplay($browser[$propname]).'</textarea>';
				}
				echo '</span><br class="form">';
			}
			echo '</p></fieldset>';
		}

		if (isset($CFG['CPS']['templateoncreate']) && $_GET['action']=='addcourse' ) {
			if (isset($CFG['coursebrowser'])) {
				//use the course browser
				if (isset($CFG['coursebrowsermsg'])) {
					echo '<span class="form">'.$CFG['coursebrowsermsg'].'</span>';
				} else {
					echo '<span class="form">'._('Copy a template or promoted course').'</span>';
				}
				echo '<span class="formright" id="browsertocopy"><input type="hidden" id="usetemplate" name="usetemplate" ';
				if (isset($_GET['tocopyid']) && isset($_GET['tocopyname'])) {
					echo 'value="'.Sanitize::onlyInt($_GET['tocopyid']).'">';
					echo '<span id="templatename">'.Sanitize::encodeStringForDisplay($_GET['tocopyname']).'</span>';
				} else {
					echo 'value="0">';
					echo '<span id="templatename">'._('Start with a blank course').'</span>';
				}
				echo '<br/><button type="button" onclick="showCourseBrowser()">'._('Browse Courses').'</button>';
				echo '<span id="termsbox" style="display:none;"><br/>';
				echo 'This course has additional <a target="_blank" href="" id="termsurl">Terms of Use</a> you must agree to before copying the course.<br/>';
				echo '<input type="checkbox" name="termsagree" /> I agree to the Terms of Use specified in the link above.</span>';
				echo '</span><br class="form" />';

				echo '<script type="text/javascript">
					function showCourseBrowser() {
						GB_show("Course Browser","coursebrowser.php?embedded=true",800,"auto");
					}
					function setCourse(course) {
						$("#usetemplate").val(course.id);
						$("#templatename").text(course.name);
						if (course.termsurl && course.termsurl != "") {
							$("#termsbox").show(); $("#termsurl").attr("href",course.termsurl);
						} else {
							$("#termsbox").hide();
						}
						GB_hide();
					}
					</script>';

			} else {
				//select a template course from a pulldown
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
						printf('<option value="%d"', $id);
						if (isset($terms[$id])) {
							printf(' data-termsurl="%s"', Sanitize::encodeStringForDisplay($terms[$id]));
						}
						printf('>%s</option>', Sanitize::encodeStringForDisplay($name));
					}
					echo '</optgroup>';
				}
				if (count($globalcourse)>0) {
					if (count($groupcourse)>0) {
						echo '<optgroup label="System-wide Templates">';
					}
					foreach ($globalcourse as $id=>$name) {
						printf('<option value="%d"', $id);
						if (isset($terms[$id])) {
							printf(' data-termsurl="%s"', Sanitize::encodeStringForDisplay($terms[$id]));
						}
						printf('>%s</option>', Sanitize::encodeStringForDisplay($name));
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
		}


		echo "<div class=submit><input type=submit value=Submit></div></form>\n";
		break;
	case "importmacros":
		if ($myrights < 100) { echo "You don't have the authority for this action"; break;}
		
		echo "<h3>Install Macro File</h3>\n";
		echo "<p><b>Warning:</b> Macro Files have a large security risk.  <b>Only install macro files from a trusted source</b></p>\n";
		echo "<p><b>Warning:</b> Install will overwrite any existing macro file of the same name</p>\n";
		echo "<form enctype=\"multipart/form-data\" method=post action=\"actions.php?from=".Sanitize::encodeUrlParam($from)."\">\n";
		echo '<input type=hidden name=action value="importmacros" />';
		echo "<input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"300000\" />\n";
		echo "<span class=form>Import file: </span><span class=formright><input name=\"userfile\" type=\"file\" /></span><br class=form>\n";
		echo "<div class=submit><input type=submit value=\"Submit\"></div>\n";
		echo "</form>\n";
		break;

	case "importqimages":
		if ($myrights < 100) { echo "You don't have the authority for this action"; break;}
		echo "<h3>Install Question Images</h3>\n";
		echo "<p><b>Warning:</b> This has a large security risk.  <b>Only install question images from a trusted source</b>, and where you've verified the archive only contains images.</p>\n";
		echo "<p><b>Warning:</b> Install will ignore files with the same filename as existing files.</p>\n";
		echo "<form enctype=\"multipart/form-data\" method=post action=\"actions.php?from=".Sanitize::encodeUrlParam($from)."\">\n";
		echo '<input type=hidden name=action value="importqimages" />';
		echo "<input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"5000000\" />\n";
		echo "<span class=form>Import file: </span><span class=formright><input name=\"userfile\" type=\"file\" /></span><br class=form>\n";
		echo "<div class=submit><input type=submit value=\"Submit\"></div>\n";
		echo "</form>\n";
		break;
	case "importcoursefiles":
		if ($myrights < 100) { echo "You don't have the authority for this action"; break;}
		echo "<h3>Install Course files</h3>\n";
		echo "<p><b>Warning:</b> This has a large security risk.  <b>Only install course files from a trusted source</b>, and where you've verified the archive only contains regular files (no PHP files).</p>\n";
		echo "<p><b>Warning:</b> Install will ignore files with the same filename as existing files.</p>\n";
		echo "<form enctype=\"multipart/form-data\" method=post action=\"actions.php?from=".Sanitize::encodeUrlParam($from)."\">\n";
		echo '<input type=hidden name=action value="importcoursefiles" />';
		echo "<input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"10000000\" />\n";
		echo "<span class=form>Import file: </span><span class=formright><input name=\"userfile\" type=\"file\" /></span><br class=form>\n";
		echo "<div class=submit><input type=submit value=\"Submit\"></div>\n";
		echo "</form>\n";
		break;
	case "deloldusers":
		if ($myrights < 100) { echo "You don't have the authority for this action"; break;}
		echo "<h3>Delete Old Users</h3>\n";
		echo "<form method=post action=\"actions.php?from=".Sanitize::encodeUrlParam($from)."\">\n";
		echo '<input type=hidden name=action value="deloldusers" />';
		echo "<span class=form>Delete Users older than:</span>";
		echo "<span class=formright><input type=text name=months size=4 value=\"6\"/> Months</span><br class=form>\n";
		echo "<span class=form>Delete Who:</span>";
		echo "<span class=formright><input type=radio name=who value=\"students\" CHECKED>Students<br/>\n";
		echo "<input type=radio name=who value=\"all\">Everyone but Admins</span><br class=form>\n";
		echo "<div class=submit><input type=submit value=\"Delete\"></div>\n";
		echo "</form>\n";
		break;
	case "listltidomaincred":
		if ($myrights < 100) { echo "You don't have the authority for this action"; break;}
		echo '<div id="headerforms" class="pagetitle">';
		echo "<h3>Modify LTI Domain Credentials</h3>\n";
		echo '</div>';
		echo "<table><tr><th>Domain</th><th>Key</th><th>Can create Instructors?</th><th>Modify</th><th>Delete</th></tr>\n";
		//DB $query = "SELECT id,email,SID,rights FROM imas_users WHERE rights=11 OR rights=76 OR rights=77";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB while ($row = mysql_fetch_row($result)) {
		$stm = $DBH->query("SELECT id,email,SID,rights FROM imas_users WHERE rights=11 OR rights=76 OR rights=77");
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			printf("<tr><td>%s</td><td>%s</td>", Sanitize::encodeStringForDisplay($row[1]),
				Sanitize::encodeStringForDisplay($row[2]));
			if ($row[3]==76) {
				echo '<td>Yes</td>';
			} else {
				echo '<td>No</td>';
			}
			echo "<td><a href=\"forms.php?action=modltidomaincred&id=".Sanitize::encodeUrlParam($row[0])."\">Modify</a></td>\n";
			if ($row[0]==0) {
				echo "<td></td>";
			} else {
				echo "<td><a href=\"forms.php?from=".Sanitize::encodeUrlParam($from)."&action=delltidomaincred&id=".Sanitize::encodeUrlParam($row[0])."\">Delete</a></td>\n";
			}
			echo "</tr>\n";
		}
		echo "</table>\n";
		echo "<form method=post action=\"actions.php?from=".Sanitize::encodeUrlParam($from)."&id=new\">\n";
		echo '<input type=hidden name=action value="modltidomaincred" />';
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
			printf('<option value="%d">%s</option>', Sanitize::encodeStringForDisplay($row[0]), Sanitize::encodeStringForDisplay($row[1]));
		}
		echo '</select><br/>';
		echo "<input type=submit value=\"Add LTI Credentials\"></p>\n";
		echo "</form>\n";
		break;
	case "delltidomaincred":
		if ($myrights<100) { echo "not allowed"; exit;}
		echo '<div id="headerforms" class="pagetitle">';
		echo "<h3>Delete LTI Domain Credentials</h3>\n";
		echo '</div>';
		$stm = $DBH->prepare("SELECT id,email,SID,password,rights,groupid FROM imas_users WHERE id=:id");
		$stm->execute(array(':id'=>$_GET['id']));
		$row = $stm->fetch(PDO::FETCH_NUM);
		echo '<p>Are you SURE you want to delete the LTI Domain Credentials for <strong>';
		echo Sanitize::encodeStringForDisplay($row[1] . ' ('.$row[2].')');
		echo '</strong>?</p>';
		echo '<form method="POST" action="actions.php?from='.Sanitize::encodeUrlParam($from).'&id='.Sanitize::encodeUrlParam($_GET['id']).'">';
		echo '<p><button type=submit name="action" value="delltidomaincred">'._('Delete').'</button>';
		echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='".Sanitize::encodeStringForJavascript($backloc)."'\"></p>\n";
		echo '</form>';
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
		echo "<form method=post action=\"actions.php?from=".Sanitize::encodeUrlParam($from)."&action=modltidomaincred&id=".Sanitize::onlyInt($row[0])."\">\n";
		echo '<input type=hidden name=action value="modltidomaincred" />';
		echo "Modify LTI key/secret: <br/>";
		echo "Domain: <input type=text name=\"ltidomain\" value=\"".Sanitize::encodeStringForDisplay($row[1])."\" size=20><br/>\n";
		echo "Key: <input type=text name=\"ltikey\" value=\"".Sanitize::encodeStringForDisplay($row[2])."\" size=20><br/>\n";
		echo "Secret: <input type=text name=\"ltisecret\"  value=\"".Sanitize::encodeStringForDisplay($row[3])."\" size=20><br/>\n";
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
			printf('<option value="%d"', Sanitize::onlyInt($r[0]));
			if ($r[0]==$row[5]) { echo ' selected="selected"';}
			echo '>'.Sanitize::encodeStringForDisplay($r[1]).'</option>';
		}
		echo '</select><br/>';
		echo "<input type=submit value=\"Update LTI Credentials\">\n";
		echo "</form>\n";
		break;

	case "listfedpeers":
		if ($myrights<100) { echo "not allowed"; exit;}
		echo '<div id="headerforms" class="pagetitle">';
		echo "<h3>View Federation Peers</h3>\n";
		echo '</div>';
		echo "<table><tr><th>Name</th><th>Description</th><th>Their Last Pull</th><th>Our Last Pull</th><th>Modify</th><th>Delete</th><th>Pull</th></tr>\n";

		$query = "SELECT ifp.id,ifp.peername,ifp.peerdescription,ifp.lastpull,max(pulls.pulltime) as uspull FROM imas_federation_peers AS ifp ";
		$query .= "LEFT JOIN imas_federation_pulls AS pulls ON ifp.id=pulls.peerid GROUP BY pulls.peerid";

		$query = "SELECT ifp.*,pulls.pulltime AS uspull,pulls.step FROM imas_federation_peers AS ifp ";
		$query .= "LEFT JOIN imas_federation_pulls as pulls ON ifp.id=pulls.peerid WHERE ";
		$query .= "pulls.id=(SELECT id FROM imas_federation_pulls AS ifps WHERE ifps.peerid=ifp.id ORDER BY pulltime DESC LIMIT 1) ";
		$query .= "OR pulls.id IS NULL";
		$stm = $DBH->query($query);
		while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
			$lastpull = $row['lastpull'] > 0 ? tzdate("n/j/y", $row['lastpull']) : _('Never');
			$uspull = $row['uspull'] === null ? _('Never') : tzdate("n/j/y", $row['uspull']);

			printf("<tr><td>%s</td><td>%s</td>", Sanitize::encodeStringForDisplay($row['peername']),
				Sanitize::encodeStringForDisplay($row['peerdescription']));
			echo '<td>'.Sanitize::encodeStringForDisplay($lastpull).'</td>';
			echo '<td>'.Sanitize::encodeStringForDisplay($uspull);
			if ($row['uspull']!==null && $row['step']<99) {
				echo '<br/>'._('Incomplete').'. <a href="federationpull.php?peer='.Sanitize::onlyInt($row['id']).'">'._('Continue').'</a>.';
			}
			echo '</td>';
			printf('<td><a href="forms.php?action=modfedpeers&id=%d&from=%s">', $row['id'], Sanitize::encodeUrlParam($from));
			echo _('Modify'),'</a></td>';
			printf("<td><a href=\"actions.php?action=delfedpeers&id=%d&from=%s\" onclick=\"return confirm('Are you sure?');\">", $row['id'],  Sanitize::encodeUrlParam($from));
			echo _('Delete'),'</a></td>';
			echo '<td><a href="federationpull.php?peer='.Sanitize::onlyInt($row['id']).'&stage=-1">'._('Start New Pull').'</a></td>';
			echo "</tr>\n";
		}
		echo "</table>\n";
		echo "<form method=post action=\"actions.php?id=new&from=".Sanitize::encodeUrlParam($from)."\">\n";
		echo "<p>Add new federation peer: <br/>";
		echo '<input type="hidden" name="action" value="modfedpeers" />';
		echo "Install Name: <input type=text name=\"peername\" size=20><br/>\n";
		echo "Description: <input type=text name=\"peerdescription\" size=50><br/>\n";
		echo "Root URL: <input type=text name=\"url\" size=50><br/>\n";
		echo "Secret: <input type=text name=\"secret\" size=20><br/>\n";
		echo "<input type=submit value=\"Add Federation Peer\"></p>\n";
		echo "</form>\n";
		break;
	case "modfedpeers":
		if ($myrights<100) { echo "not allowed"; exit;}
		echo '<div id="headerforms" class="pagetitle">';
		echo "<h3>Modify Federation Peer</h3>\n";
		echo '</div>';
		//DB $query = "SELECT id,email,SID,password,rights,groupid FROM imas_users WHERE id='{$_GET['id']}'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $row = mysql_fetch_row($result);
		$stm = $DBH->prepare("SELECT id,peername,peerdescription,url,secret,lastpull FROM imas_federation_peers WHERE id=:id");
		$stm->execute(array(':id'=>$_GET['id']));
		$row = $stm->fetch(PDO::FETCH_ASSOC);
		printf("<form method=post action=\"actions.php?id=%d&from=%s\">\n", Sanitize::onlyInt($row['id']), Sanitize::encodeUrlParam($from));
		echo "Modify federation peer: <br/>";
		echo '<input type="hidden" name="action" value="modfedpeers" />';
		printf("Install Name: <input type=text name=\"peername\" value=\"%s\" size=20><br/>\n",
			Sanitize::encodeStringForDisplay($row['peername']));
		printf("Description: <input type=text name=\"peerdescription\" value=\"%s\" size=50><br/>\n",
			Sanitize::encodeStringForDisplay($row['peerdescription']));
		printf("Root URL: <input type=text name=\"url\" value=\"%s\" size=50><br/>\n",
			Sanitize::encodeStringForDisplay($row['url']));
		printf("Secret: <input type=text name=\"secret\"  value=\"%s\" size=20><br/>\n",
			Sanitize::encodeStringForDisplay($row['secret']));
		echo "<input type=submit value=\"Update Federation Peer\">\n";
		echo "</form>\n";
		break;

	case "listgroups":
		if ($myrights < 100) { echo "You don't have the authority for this action"; break;}
		echo '<div id="headerforms" class="pagetitle">';
		echo "<h3>Modify Groups</h3>\n";
		echo '</div>';
		echo "<table class=gb><thead><tr><th>Group Name</th><th>Modify</th><th>Delete</th></tr></thead><tbody>\n";
		if ($from=='admin2') {
			echo '<tr class="even"><td><a href="admin2.php?groupdetails=0">'._('Default Group').'</a></td><td></td><td></td></tr>';
		}
		//DB $query = "SELECT id,name FROM imas_groups ORDER BY name";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB while ($row = mysql_fetch_row($result)) {
		$stm = $DBH->query("SELECT id,name FROM imas_groups ORDER BY name");
		$alt = 1;
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			if ($alt==0) {echo "<tr class=\"even\">"; $alt=1;} else {echo "<tr class=\"odd\">"; $alt=0;}
			if ($from=='admin2') {
				echo '<td><a href="admin2.php?groupdetails='.Sanitize::onlyInt($row[0]).'">'.Sanitize::encodeStringForDisplay($row[1]).'</a></td>';
			} else {
				echo "<td>".Sanitize::encodeStringForDisplay($row[1])."</td>";
			}
			printf("<td><a href=\"forms.php?action=modgroup&id=%s\">Modify</a></td>\n",
				Sanitize::encodeUrlParam($row[0]));
			if ($row[0]==0) {
				echo "<td></td>";
			} else {
				printf("<td><a href=\"forms.php?from=%s&action=delgroup&id=%d\">Delete</a></td>\n",
					Sanitize::encodeUrlParam($from), Sanitize::onlyInt($row[0]));
			}
			echo "</tr>\n";
		}
		echo "</tbody></table>\n";
		printf("<form method=post action=\"actions.php?from=%s\">\n", Sanitize::encodeUrlParam($from));
		echo '<input type=hidden name=action value="addgroup" />';
		echo "Add new group: <input type=text name=gpname id=gpname size=50><br/>\n";
		echo "<input type=submit value=\"Add Group\">\n";
		echo "</form>\n";
		break;
	case "delgroup":
		if ($myrights<100) { echo "not allowed"; exit;}
		echo '<div id="headerforms" class="pagetitle">';
		echo "<h3>Delete Group</h3>\n";
		echo '</div>';
		$stm = $DBH->prepare("SELECT name,parent FROM imas_groups WHERE id=:id");
		$stm->execute(array(':id'=>$_GET['id']));
		list($gpname,$parent) = $stm->fetch(PDO::FETCH_NUM);
		echo '<p>Are you SURE you want to delete the group <strong>';
		echo Sanitize::encodeStringForDisplay($gpname);
		echo '</strong>?</p>';
		echo '<form method="POST" action="actions.php?from='.Sanitize::encodeUrlParam($from).'&id='.Sanitize::encodeUrlParam($_GET['id']).'">';
		echo '<p><button type=submit name="action" value="delgroup">'._('Delete').'</button>';
		echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='".Sanitize::encodeStringForJavascript($backloc)."'\"></p>\n";
		echo '</form>';
		break;
	case "modgroup":
		if ($myrights < 100) { echo "You don't have the authority for this action"; break;}
		echo '<div id="headerforms" class="pagetitle"><h2>Rename Instructor Group</h2></div>';
		//DB $query = "SELECT name,parent FROM imas_groups WHERE id='{$_GET['id']}'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB list($gpname,$parent) = mysql_fetch_row($result);
		$stm = $DBH->prepare("SELECT name,parent FROM imas_groups WHERE id=:id");
		$stm->execute(array(':id'=>$_GET['id']));
		list($gpname,$parent) = $stm->fetch(PDO::FETCH_NUM);

		printf("<form method=post action=\"actions.php?from=%s&id=%s\">\n",
			Sanitize::encodeUrlParam($from), Sanitize::encodeUrlParam($_GET['id']));
		echo '<input type=hidden name=action value="modgroup" />';
		echo "Group name: <input type=text size=50 name=gpname id=gpname value=\"".Sanitize::encodeStringForDisplay($gpname)."\"><br/>\n";
		echo 'Parent: <select name="parentid"><option value="0" ';
		if ($parent==0) { echo ' selected="selected"';}
		echo '>None</option>';
		//DB $query = "SELECT id,name FROM imas_groups ORDER BY name";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB while ($r = mysql_fetch_row($result)) {
		$stm = $DBH->query("SELECT id,name FROM imas_groups ORDER BY name");
		while ($r = $stm->fetch(PDO::FETCH_NUM)) {
			echo '<option value="'.Sanitize::encodeStringForDisplay($r[0]).'"';
			if ($r[0]==$parent) { echo ' selected="selected"';}
			echo '>'.Sanitize::encodeStringForDisplay($r[1]).'</option>';
		}
		echo '</select><br/>';
		echo "<input type=submit value=\"Update Group\">\n";
		echo "</form>\n";
		break;
	case "removediag":
		if ($myrights<100 && ($myspecialrights&4)!=4) { echo "You don't have the authority for this action"; break;}
		echo "<p>Are you sure you want to delete this diagnostic?  This does not delete the connected course and does not remove students or their scores.</p>\n";
		echo '<form method="POST" action="actions.php?from='.Sanitize::encodeUrlParam($from).'&id='.Sanitize::encodeUrlParam($_GET['id']).'">';
		echo '<p><button type=submit name="action" value="removediag">'._('Delete').'</button>';
		echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='".Sanitize::encodeStringForJavascript($backloc)."'\"></p>\n";
		echo '</form>';

		break;
	case "findstudent":
		if ($myrights < 20) { echo "You don't have the authority for this action"; break;}
		echo '<div id="headerforms" class="pagetitle"><h2>Find Student</h2></div>';
		echo '<form method="post" action="forms.php?from='.Sanitize::encodeUrlParam($from).'&action=findstudent">';
		echo '<p>Enter all or part of the name, email, or username: ';
		echo '<input type=text size=20 name=userinfo value="'.Sanitize::encodeStringForDisplay($_POST['userinfo']).'"/></p>';
		echo '<input type="submit">';
		echo '</form>';
		if (!empty($_POST['userinfo'])) {
			$words = preg_split('/\s+/', str_replace(',',' ',trim($_POST['userinfo'])));
			$query = "SELECT iu.id,iu.LastName,iu.FirstName,iu.SID,ic.name,ic.id AS cid";
			if ($from!='home' && $myrights>=75) {
				$query .= ",iut.LastName AS teacherfirst,iut.FirstName AS teacherlast";
			}
			$query .= " FROM imas_users AS iu JOIN ";
			$query .= "imas_students AS i_s ON iu.id=i_s.userid JOIN imas_courses AS ic ON ic.id=i_s.courseid ";
			$myrights = 75;
			if ($from=='home' || $myrights<75) {
				$query .= "JOIN imas_teachers AS i_t ON ic.id=i_t.courseid ";
				$query .= "WHERE i_t.userid=? AND ";
				$qarr = array($userid);
			} else { 
				$query .= "JOIN imas_teachers AS i_t ON ic.id=i_t.courseid ";
				$query .= "JOIN imas_users AS iut ON i_t.userid=iut.id ";
				if ($myrights<100) {
					$query .= "WHERE iut.groupid=? AND ";
					$qarr = array($groupid);
				} else {
					$query .= "WHERE ";	
					$qarr = array();
				}
			}
			if (count($words)==1 && strpos($words[0],'@')!==false) {
				$query .= "(iu.email=? OR iu.SID=?)";
				array_push($qarr, $words[0], $words[0]);
			} else if (count($words)==1) {
				$query .= "(iu.LastName LIKE ? OR iu.FirstName Like ? OR iu.SID LIKE ?)";
				array_push($qarr, $words[0].'%', $words[0].'%', '%'.$words[0].'%');
			} else if (count($words)==2) {
				$query .= "((iu.LastName LIKE ? AND iu.FirstName Like ?) OR (iu.LastName LIKE ? AND iu.FirstName Like ?))";
				array_push($qarr, $words[0].'%', $words[1].'%', $words[1].'%', $words[0].'%');
			}
			$query .= " ORDER BY LastName, FirstName LIMIT 200";
			$stm = $DBH->prepare($query);
			$stm->execute($qarr);
			if ($stm->rowCount()==0) {
				echo '<p>No matches <a href="forms.php?from='.Sanitize::encodeUrlParam($from).'&action=findstudent">Try again</a></p>';
			} else {
				echo '<table class="gb"><thead><th>Student</th><th>Username</th><th>Course</th>';
				if ($from!='home' && $myrights>=75) {
					echo '<th>Instructor</th>';
				}
				echo '</thead><tbody>';
				$i = 0;
				while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
					echo ($i==0)?'<tr class=even>':'<tr class=odd>'; $i = 1-$i;
					echo '<td>';
					echo '<a href="../course/gradebook.php?cid='.Sanitize::onlyInt($row['cid']).'&stu='.Sanitize::onlyInt($row['id']).'">';
					echo Sanitize::encodeStringForDisplay($row['LastName'].', '.$row['FirstName']).'</td>';
					echo '</a><td>'.Sanitize::encodeStringForDisplay($row['SID']).'</td>'; 
					echo '<td>'.Sanitize::encodeStringForDisplay($row['name']).'</td>';
					if ($from!='home' && $myrights>=75) {
						echo '<td>'.Sanitize::encodeStringForDisplay($row['teacherlast'].', '.$row['teacherfirst']).'</td>';
					}
					echo '</td></tr>';
				}
				echo '</tbody></table>';
			}		
		} 
		
		break;
}

require("../footer.php");
?>

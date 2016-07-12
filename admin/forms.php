<?php
//IMathAS:  Admin forms
//(c) 2006 David Lippman
require("../validate.php");
require("../header.php");
if (!isset($_GET['cid'])) {
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"admin.php\">Admin</a> &gt; Form</div>\n";
}
switch($_GET['action']) {
	case "delete":
		$query = "SELECT name FROM imas_courses WHERE id='{$_GET['id']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$name = mysql_result($result,0,0);
		echo '<div id="headerforms" class="pagetitle"><h2>Delete Course</h2></div>';
		echo "<p>Are you sure you want to delete the course <b>$name</b>?</p>\n";
		echo "<p><input type=button value=\"Delete\" onclick=\"window.location='actions.php?action=delete&id={$_GET['id']}'\">\n";
		echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='admin.php'\"></p>\n";
		break;
	case "deladmin":
		echo "<p>Are you sure you want to delete this user?</p>\n";
		echo "<p><input type=button value=\"Delete\" onclick=\"window.location='actions.php?action=deladmin&id={$_GET['id']}'\">\n";
		echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='admin.php'\"></p>\n";
		break;
	case "chgpwd":
		echo '<div id="headerforms" class="pagetitle"><h2>Change Your Password</h2></div>';
		echo "<form method=post action=\"actions.php?action=chgpwd\">\n";
		echo "<span class=form>Enter old password:</span>  <input class=form type=password name=oldpw size=40> <BR class=form>\n";
		echo "<span class=form>Enter new password:</span> <input class=form type=password name=newpw1 size=40> <BR class=form>\n";
		echo "<span class=form>Verify new password:</span>  <input class=form type=password name=newpw2 size=40> <BR class=form>\n";
		echo '<div class=submit><input type="submit" value="'._('Save').'"></div></form>';
		break;
	
	case "chgrights":
	case "newadmin":
		echo "<form method=post action=\"actions.php?action={$_GET['action']}";
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
			$query = "SELECT FirstName,LastName,rights,groupid,specialrights FROM imas_users WHERE id='{$_GET['id']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$line = mysql_fetch_array($result, MYSQL_ASSOC);
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
			$query = "SELECT id,name FROM imas_groups ORDER BY name";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
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
			$query = "SELECT * FROM imas_courses WHERE id='{$_GET['id']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_num_rows($result)==0) {break;}
			$line = mysql_fetch_array($result, MYSQL_ASSOC);
			if ($myrights<75 && $line['ownerid']!=$userid) {
				echo "You don't have the authority for this action"; break;
			} else if ($myrights > 74 && $line['ownerid']!=$userid) {
				$isadminview = true;
				$query = "SELECT iu.FirstName, iu.LastName, iu.groupid, ig.name FROM imas_users AS iu JOIN imas_groups AS ig ON ig.id=iu.groupid WHERE iu.id={$line['ownerid']}";
				$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
				$udat = mysql_fetch_array($result, MYSQL_ASSOC);
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
			$msgQtoInstr = (floor($line['msgset']/5))&2;
			$toolset = $line['toolset'];
			$cploc = $line['cploc'];
			$theme = $line['theme'];
			$topbar = explode('|',$line['topbar']);
			$topbar[0] = explode(',',$topbar[0]);
			$topbar[1] = explode(',',$topbar[1]);
			if ($topbar[0][0] == null) {unset($topbar[0][0]);}
			if ($topbar[1][0] == null) {unset($topbar[1][0]);}
			if (!isset($topbar[2])) {$topbar[2] = 0;}
			$avail = $line['available'];
			$lockaid = $line['lockaid'];
			$ltisecret = $line['ltisecret'];
			$chatset = $line['chatset'];
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
			$msgQtoInstr = (floor($msgset/5))&2;
			$msgset = $msgset%5;
			
			$cploc = isset($CFG['CPS']['cploc'])?$CFG['CPS']['cploc'][0]:1;
			
			$topbar = isset($CFG['CPS']['topbar'])?$CFG['CPS']['topbar'][0]:array(array(),array(),0);
			$theme = isset($CFG['CPS']['theme'])?$CFG['CPS']['theme'][0]:$defaultcoursetheme;
			$chatset = isset($CFG['CPS']['chatset'])?$CFG['CPS']['chatset'][0]:0;
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
			$cid = $_GET['cid'];
			echo "<div class=breadcrumb>$breadcrumbbase <a href=\"../course/course.php?cid=$cid\">$coursename</a> &gt; Course Settings</div>";
		} 
		echo '<div id="headerforms" class="pagetitle"><h2>Course Settings</h2></div>';
		echo "<form method=post action=\"actions.php?action={$_GET['action']}";
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
		echo '/>Available to students<br/><input type="checkbox" name="teachavail" value="2" ';
		if (($avail&2)==0) { echo 'checked="checked"';}
		echo '/>Show on instructors\' home page</span><br class="form" />';
		if ($_GET['action']=="modify") {
			echo '<span class=form>Lock for assessment:</span><span class=formright><select name="lockaid">';
			echo '<option value="0" ';
			if ($lockaid==0) { echo 'selected="1"';}
			echo '>No lock</option>';
			$query = "SELECT id,name FROM imas_assessments WHERE courseid='{$_GET['id']}' ORDER BY name";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
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
		if (!isset($CFG['CPS']['picicons']) || $CFG['CPS']['picicons'][1]==1) {
		
			echo "<span class=form>Icons:</span><span class=formright>\n";
			echo 'Icon Style: <input type=radio name="picicons" value="0" ';
			if ($picicons==0) { echo "checked=1";} 
			echo '/> Text-based <input type=radio name="picicons" value="1" ';
			if ($picicons==1) { echo "checked=1";}
			echo '/> Images</span><br class="form" />';
		}
		
		if (!isset($CFG['CPS']['hideicons']) || $CFG['CPS']['hideicons'][1]==1) {
		
			echo "<span class=form>Show Icons:</span><span class=formright>\n";
		
			echo 'Assessments: <input type=radio name="HIassess" value="0" ';
			if (($hideicons&1)==0) { echo "checked=1";}     
			echo '/> Show <input type=radio name="HIassess" value="1" ';
			if (($hideicons&1)==1) { echo "checked=1";}
			echo '/> Hide<br/>';
			
			echo 'Inline Text: <input type=radio name="HIinline" value="0" ';
			if (($hideicons&2)==0) { echo "checked=1";}     
			echo '/> Show <input type=radio name="HIinline" value="2" ';
			if (($hideicons&2)==2) { echo "checked=1";}
			echo '/> Hide<br/>';
			
			echo 'Linked Text: <input type=radio name="HIlinked" value="0" ';
			if (($hideicons&4)==0) { echo "checked=1";}     
			echo '/> Show <input type=radio name="HIlinked" value="4" ';
			if (($hideicons&4)==4) { echo "checked=1";}
			echo '/> Hide<br/>';
			
			echo 'Forums: <input type=radio name="HIforum" value="0" ';
			if (($hideicons&8)==0) { echo "checked=1";}     
			echo '/> Show <input type=radio name="HIforum" value="8" ';
			if (($hideicons&8)==8) { echo "checked=1";}
			echo '/> Hide<br/>';
			
			echo 'Blocks: <input type=radio name="HIblock" value="0" ';
			if (($hideicons&16)==0) { echo "checked=1";}     
			echo '/> Show <input type=radio name="HIblock" value="16" ';
			if (($hideicons&16)==16) { echo "checked=1";}
			echo '/> Hide</span><br class=form />';
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
			//<br/><input type=checkbox name="msgqtoinstr" value="1" ';
			//if ($msgQtoInstr==2) { echo "checked=1";}
			//echo '/> Enable &quot;Message instructor about this question&quot; links
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
			
			echo '<span class="form">Pull-downs for course item reordering</span>';
			echo '<span class="formright"><input type="checkbox" name="toolset-reord" value="4" ';
			if (($toolset&4)==0) { echo 'checked="checked"';}
			echo '> Show</span><br class="form"/>';
		}
		
		if (!isset($CFG['CPS']['chatset']) || $CFG['CPS']['chatset'][1]==1) {
			if (isset($mathchaturl) && $mathchaturl!='') {
				echo '<span class="form">Enable live chat:</span><span class="formright">';
				echo '<input type=checkbox name="chatset" value="1" ';
				if ($chatset==1) {echo 'checked="checked"';};
				echo ' /></span><br class="form" />';
			}
		}
		if (!isset($CFG['CPS']['deflatepass']) || $CFG['CPS']['deflatepass'][1]==1) {
			echo '<span class="form">Auto-assign LatePasses on course enroll:</span><span class="formright">';
			echo '<input type="text" size="3" name="deflatepass" value="'.$deflatepass.'"/> LatePasses</span><br class="form" />';
		}
		if (!isset($CFG['CPS']['showlatepass']) || $CFG['CPS']['showlatepass'][1]==1) {
			echo '<span class="form">Show remaining LatePasses on student gradebook page:</span><span class="formright">';
			echo '<input type=checkbox name="showlatepass" value="1" ';
			if ($showlatepass==1) {echo 'checked="checked"';};
			echo ' /></span><br class="form" />';
		}
		
		if (!isset($CFG['CPS']['topbar']) || $CFG['CPS']['topbar'][1]==1) {
			echo "<span class=form>Student Quick Pick Top Bar items:</span><span class=formright>";
			echo '<input type=checkbox name="stutopbar[]" value="0" ';
			if (in_array(0,$topbar[0])) { echo 'checked=1'; }
			echo ' /> Messages <br /><input type=checkbox name="stutopbar[]" value="3" ';
			if (in_array(3,$topbar[0])) { echo 'checked=1'; }
			echo ' /> Forums <br /><input type=checkbox name="stutopbar[]" value="1" ';
			if (in_array(1,$topbar[0])) { echo 'checked=1'; }
			echo ' /> Gradebook <br /><input type=checkbox name="stutopbar[]" value="2" ';
			if (in_array(2,$topbar[0])) { echo 'checked=1'; }
			echo ' /> Calendar <br /><input type=checkbox name="stutopbar[]" value="9" ';
			if (in_array(9,$topbar[0])) { echo 'checked=1'; }
			echo ' /> Log Out</span><br class=form />';
			
			echo "<span class=form>Instructor Quick Pick Top Bar items:</span><span class=formright>";
			echo '<input type=checkbox name="insttopbar[]" value="0" ';
			if (in_array(0,$topbar[1])) { echo 'checked=1'; }
			echo ' /> Messages<br /><input type=checkbox name="insttopbar[]" value="6" ';
			if (in_array(6,$topbar[1])) { echo 'checked=1'; }
			echo ' /> Forums<br /><input type=checkbox name="insttopbar[]" value="1" ';
			if (in_array(1,$topbar[1])) { echo 'checked=1'; }
			echo ' /> Student View<br /><input type=checkbox name="insttopbar[]" value="2" ';
			if (in_array(2,$topbar[1])) { echo 'checked=1'; }
			echo ' /> Gradebook<br /><input type=checkbox name="insttopbar[]" value="3" ';
			if (in_array(3,$topbar[1])) { echo 'checked=1'; }
			echo ' /> Roster<br /><input type=checkbox name="insttopbar[]" value="7" ';
			if (in_array(7,$topbar[1])) { echo 'checked=1'; }
			echo ' /> Groups<br/><input type=checkbox name="insttopbar[]" value="4" ';
			if (in_array(4,$topbar[1])) { echo 'checked=1'; }
			echo ' /> Calendar<br/><input type=checkbox name="insttopbar[]" value="5" ';
			if (in_array(5,$topbar[1])) { echo 'checked=1'; }
			echo ' /> Quick View<br /><input type=checkbox name="insttopbar[]" value="9" ';
			if (in_array(9,$topbar[1])) { echo 'checked=1'; }
			echo ' /> Log Out</span><br class=form />';
			
			echo '<span class="form">Quick Pick Bar location:</span><span class="formright">';
			echo '<input type="radio" name="topbarloc" value="0" '. ($topbar[2]==0?'checked="checked"':'').'>Top of course page<br/>';
			echo '<input type="radio" name="topbarloc" value="1" '. ($topbar[2]==1?'checked="checked"':'').'>Top of all pages';
			echo '</span><br class="form" />';
		}
		if (!isset($CFG['CPS']['cploc']) || $CFG['CPS']['cploc'][1]==1) {
			echo '<span class=form>Instructor course management links location:</span><span class=formright>';
			echo '<input type=radio name="cploc" value="0" ';
			if (($cploc&1)==0) {echo "checked=1";}
			echo ' /> Bottom of page<br /><input type=radio name="cploc" value="1" ';
			if (($cploc&1)==1) {echo "checked=1";}
			echo ' /> Left side bar</span><br class=form />';
			
			echo '<span class=form>View Control links:</span><span class=formright>';
			echo '<input type=radio name="cplocview" value="0" ';
			if (($cploc&4)==0) {echo "checked=1";}
			echo ' /> With other course management links<br /><input type=radio name="cplocview" value="4" ';
			if (($cploc&4)==4) {echo "checked=1";}
			echo ' /> Buttons at top right</span><br class=form />';
			
			echo '<span class=form>Student links location:</span><span class=formright>';
			echo '<input type=radio name="cplocstu" value="0" ';
			if (($cploc&2)==0) {echo "checked=1";}
			echo ' /> Bottom of page<br /><input type=radio name="cplocstu" value="2" ';
			if (($cploc&2)==2) {echo "checked=1";}
			echo ' /> Left side bar</span><br class=form />';
		}
		
		if (isset($enablebasiclti) && $enablebasiclti==true && isset($_GET['id'])) {
			echo '<span class="form">LTI access secret (max 10 chars; blank to not use)</span>';
			echo '<span class="formright"><input name="ltisecret" type="text" value="'.$ltisecret.'" maxlength="10"/> ';
			echo '<button type="button" onclick="document.getElementById(\'ltiurl\').style.display=\'\';this.parentNode.removeChild(this);">'._('Show LTI key and URL').'</button>';
			echo '<span id="ltiurl" style="display:none;">';
			if (isset($_GET['id'])) {
				echo '<br/>URL: '.$urlmode.$_SERVER['HTTP_HOST'].$imasroot.'/bltilaunch.php<br/>';
				echo 'Key: LTIkey_'.$_GET['id'].'_0 (to allow students to login directly to '.$installname.') or<br/>';
				echo 'Key: LTIkey_'.$_GET['id'].'_1 (to only allow access through the LMS )';
			} else {
				echo 'Course ID not yet set.';
			}		
			echo '</span></span><br class="form" />';
		}
		if (($myspecialrights&1)==1 || ($myspecialrights&2)==2) {
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
			$query = "SELECT id,name,copyrights,istemplate,termsurl FROM imas_courses WHERE (istemplate&1)=1 AND available<4 AND copyrights=2 ORDER BY name";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				$globalcourse[$row[0]] = $row[1];
				if ($row[4]!='') {
					$terms[$row[0]] = $row[4];
				}
			}
			$query = "SELECT ic.id,ic.name,ic.copyrights,ic.termsurl FROM imas_courses AS ic JOIN imas_users AS iu ON ic.ownerid=iu.id WHERE ";
			$query .= "iu.groupid='$groupid' AND (ic.istemplate&2)=2 AND ic.copyrights>0 AND ic.available<4 ORDER BY ic.name";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
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
		$query = "SELECT name FROM imas_courses WHERE id='{$_GET['id']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$line = mysql_fetch_array($result, MYSQL_ASSOC);
		echo '<div id="headerforms" class="pagetitle">';
		echo "<h2>{$line['name']}</h2>\n";
		echo '</div>';
		
		echo "<h4>Current Teachers:</h4>\n";
		$query = "SELECT imas_users.FirstName,imas_users.LastName,imas_teachers.id,imas_teachers.userid ";
		$query .= "FROM imas_users,imas_teachers WHERE imas_teachers.courseid='{$_GET['id']}' AND " ;
		$query .= "imas_teachers.userid=imas_users.id ORDER BY imas_users.LastName;";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$num = mysql_num_rows($result);
		echo '<form method="post" action="actions.php?action=remteacher&cid='.$_GET['id'].'&tot='.$num.'">';
		echo 'With Selected: <input type="submit" value="Remove as Teacher"/>';
		echo "<table cellpadding=5>\n";
		$onlyone = ($num==1);
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			
			if ($onlyone) {
				echo '<tr><td></td>';
			} else {
				echo '<tr><td><input type="checkbox" name="tid[]" value="'.$line['id'].'"/></td>';
			}
			
			echo "<td>{$line['LastName']}, {$line['FirstName']}</td>";
			if ($onlyone) {
				echo "<td></td></tr>";
			} else {
				echo "<td><A href=\"actions.php?action=remteacher&cid={$_GET['id']}&tid={$line['id']}\">Remove as Teacher</a></td></tr>\n";
			}
			$used[$line['userid']] = true;
		}
		echo "</table></form>\n";
		
		echo "<h4>Potential Teachers:</h4>\n";
		if ($myrights<100) {
			$query = "SELECT id,FirstName,LastName,rights FROM imas_users WHERE rights>19 AND (rights<76 or rights>78) AND groupid='$groupid' ORDER BY LastName;";
		} else if ($myrights==100) {
			$query = "SELECT id,FirstName,LastName,rights FROM imas_users WHERE rights>19 AND (rights<76 or rights>78) ORDER BY LastName;";
		}
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		echo '<form method="post" action="actions.php?action=addteacher&cid='.$_GET['id'].'">';
		echo 'With Selected: <input type="submit" value="Add as Teacher"/>';
		echo "<table cellpadding=5>\n";
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			if (trim($line['LastName'])=='' && trim($line['FirstName'])=='') {continue;}
			if ($used[$line['id']]!=true) {
				//if ($line['rights']<20) { $type = "Tutor/TA/Proctor";} else {$type = "Teacher";}
				echo '<tr><td><input type="checkbox" name="atid[]" value="'.$line['id'].'"/></td>';
				echo "<td>{$line['LastName']}, {$line['FirstName']} </td> ";
				echo "<td><a href=\"actions.php?action=addteacher&cid={$_GET['id']}&tid={$line['id']}\">Add as Teacher</a></td></tr>\n";
			}
		}
		echo "</table></form>\n";
		echo "<p><input type=button value=\"Done\" onclick=\"window.location='admin.php'\" /></p>\n";
		break;
	case "importmacros":
		echo "<h3>Install Macro File</h3>\n";
		echo "<p><b>Warning:</b> Macro Files have a large security risk.  <b>Only install macro files from a trusted source</b></p>\n";
		echo "<p><b>Warning:</b> Install will overwrite any existing macro file of the same name</p>\n"; 
		echo "<form enctype=\"multipart/form-data\" method=post action=\"actions.php?action=importmacros\">\n";
		echo "<input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"300000\" />\n";
		echo "<span class=form>Import file: </span><span class=formright><input name=\"userfile\" type=\"file\" /></span><br class=form>\n";
		echo "<div class=submit><input type=submit value=\"Submit\"></div>\n";
		echo "</form>\n";
		break;
	
	case "importqimages":
		echo "<h3>Install Question Images</h3>\n";
		echo "<p><b>Warning:</b> This has a large security risk.  <b>Only install question images from a trusted source</b>, and where you've verified the archive only contains images.</p>\n";
		echo "<p><b>Warning:</b> Install will ignore files with the same filename as existing files.</p>\n"; 
		echo "<form enctype=\"multipart/form-data\" method=post action=\"actions.php?action=importqimages\">\n";
		echo "<input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"5000000\" />\n";
		echo "<span class=form>Import file: </span><span class=formright><input name=\"userfile\" type=\"file\" /></span><br class=form>\n";
		echo "<div class=submit><input type=submit value=\"Submit\"></div>\n";
		echo "</form>\n";
		break;
	case "importcoursefiles":
		echo "<h3>Install Course files</h3>\n";
		echo "<p><b>Warning:</b> This has a large security risk.  <b>Only install course files from a trusted source</b>, and where you've verified the archive only contains regular files (no PHP files).</p>\n";
		echo "<p><b>Warning:</b> Install will ignore files with the same filename as existing files.</p>\n"; 
		echo "<form enctype=\"multipart/form-data\" method=post action=\"actions.php?action=importcoursefiles\">\n";
		echo "<input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"10000000\" />\n";
		echo "<span class=form>Import file: </span><span class=formright><input name=\"userfile\" type=\"file\" /></span><br class=form>\n";
		echo "<div class=submit><input type=submit value=\"Submit\"></div>\n";
		echo "</form>\n";
		break;
	case "transfer":
		echo '<div id="headerforms" class="pagetitle">';
		echo "<h3>Transfer Course Ownership</h3>\n";
		echo '</div>';
		echo "<form method=post action=\"actions.php?action=transfer&id={$_GET['id']}\">\n";
		echo "Transfer course ownership to: <select name=newowner>\n";
		$query = "SELECT id,FirstName,LastName FROM imas_users WHERE rights>19";
		if ($myrights < 100) {
			$query .= " AND groupid='$groupid'";
		}
		$query .= " ORDER BY LastName";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			echo "<option value=\"$row[0]\">$row[2], $row[1]</option>\n";
		}
		echo "</select>\n";
		echo "<p><input type=submit value=\"Transfer\">\n";
		echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='admin.php'\"></p>\n";
		echo "</form>\n";
		break;
	case "deloldusers":
		echo "<h3>Delete Old Users</h3>\n";
		echo "<form method=post action=\"actions.php?action=deloldusers\">\n";
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
		$query = "SELECT id,email,SID,rights FROM imas_users WHERE rights=11 OR rights=76 OR rights=77";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
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
				echo "<td><a href=\"actions.php?action=delltidomaincred&id={$row[0]}\" onclick=\"return confirm('Are you sure?');\">Delete</a></td>\n";
			}
			echo "</tr>\n";
		}
		echo "</table>\n";
		echo "<form method=post action=\"actions.php?action=modltidomaincred&id=new\">\n";
		echo "<p>Add new LTI key/secret: <br/>";
		echo "Domain: <input type=text name=\"ltidomain\" size=20><br/>\n";
		echo "Key: <input type=text name=\"ltikey\" size=20><br/>\n";
		echo "Secret: <input type=text name=\"ltisecret\" size=20><br/>\n";
		echo "Can create instructors: <select name=\"createinstr\"><option value=\"11\" selected=\"selected\">No</option>";
		echo "<option value=\"76\">Yes, and creates $installname login</option>";
		//echo "<option value=\"77\">Yes, with access via LMS only</option>
		echo "</select><br/>\n";
		echo 'Associate with group <select name="groupid"><option value="0">Default</option>';
		$query = "SELECT id,name FROM imas_groups ORDER BY name";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
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
		$query = "SELECT id,email,SID,password,rights,groupid FROM imas_users WHERE id='{$_GET['id']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$row = mysql_fetch_row($result);
		echo "<form method=post action=\"actions.php?action=modltidomaincred&id={$row[0]}\">\n";
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
		$query = "SELECT id,name FROM imas_groups ORDER BY name";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($r = mysql_fetch_row($result)) {
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
		$query = "SELECT id,name FROM imas_groups ORDER BY name";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			echo "<tr><td>{$row[1]}</td>";
			echo "<td><a href=\"forms.php?action=modgroup&id={$row[0]}\">Modify</a></td>\n";
			if ($row[0]==0) {
				echo "<td></td>";
			} else {
				echo "<td><a href=\"actions.php?action=delgroup&id={$row[0]}\" onclick=\"return confirm('Are you SURE you want to delete this group?');\">Delete</a></td>\n";
			}
			echo "</tr>\n";
		}
		echo "</table>\n";
		echo "<form method=post action=\"actions.php?action=addgroup\">\n";
		echo "Add new group: <input type=text name=gpname id=gpname size=50><br/>\n";
		echo "<input type=submit value=\"Add Group\">\n";
		echo "</form>\n";
		break;
	case "modgroup":
		echo '<div id="headerforms" class="pagetitle"><h2>Rename Instructor Group</h2></div>';
		$query = "SELECT name,parent FROM imas_groups WHERE id='{$_GET['id']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		list($gpname,$parent) = mysql_fetch_row($result);
		
		echo "<form method=post action=\"actions.php?action=modgroup&id={$_GET['id']}\">\n";
		echo "Group name: <input type=text size=50 name=gpname id=gpname value=\"$gpname\"><br/>\n";
		echo 'Parent: <select name="parentid"><option value="0" ';
		if ($parent==0) { echo ' selected="selected"';}
		echo '>None</option>';
		$query = "SELECT id,name FROM imas_groups ORDER BY name";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($r = mysql_fetch_row($result)) {
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
		echo "<p><input type=button value=\"Delete\" onclick=\"window.location='actions.php?action=removediag&id={$_GET['id']}'\">\n";
		echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='admin.php'\"></p>\n";
		break;
}

require("../footer.php");
?>


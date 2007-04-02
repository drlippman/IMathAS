<?php
//IMathAS:  Admin forms
//(c) 2006 David Lippman
require("../validate.php");
require("../header.php");
if (!isset($_GET['cid'])) {
	echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"admin.php\">Admin</a> &gt; Form</div>\n";
}
switch($_GET['action']) {
	case "delete":
		$query = "SELECT name FROM imas_courses WHERE id='{$_GET['id']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$name = mysql_result($result,0,0);
		echo "<p>Are you sure you want to delete the course <b>$name</b>?</p>\n";
		echo "<p><input type=button value=\"Delete\" onclick=\"window.location='actions.php?action=delete&id={$_GET['id']}'\">\n";
		echo "<input type=button value=\"Nevermind\" onclick=\"window.location='admin.php'\"></p>\n";
		break;
	case "deladmin":
		echo "<p>Are you sure you want to delete this user?</p>\n";
		echo "<p><input type=button value=\"Delete\" onclick=\"window.location='actions.php?action=deladmin&id={$_GET['id']}'\">\n";
		echo "<input type=button value=\"Nevermind\" onclick=\"window.location='admin.php'\"></p>\n";
		break;
	case "chgpwd":
		echo "<h4>Change Your Password</h4>\n";
		echo "<form method=post action=\"actions.php?action=chgpwd\">\n";
		echo "<span class=form>Enter old password:</span>  <input class=form type=password name=oldpw size=40> <BR class=form>\n";
		echo "<span class=form>Enter new password:</span> <input class=form type=password name=newpw1 size=40> <BR class=form>\n";
		echo "<span class=form>Verify new password:</span>  <input class=form type=password name=newpw2 size=40> <BR class=form>\n";
		echo "<div class=submit><input type=submit value=Submit></div></form>\n";
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
			echo "<span class=form>Password will default to: </span><span class=formright>password</span><BR class=form>\n";
			$oldgroup = 0;
			$oldrights = 10;
		} else {
			$query = "SELECT FirstName,LastName,rights,groupid FROM imas_users WHERE id='{$_GET['id']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$line = mysql_fetch_array($result, MYSQL_ASSOC);
			echo "<h2>{$line['FirstName']} {$line['LastName']}</h2>\n";
			$oldgroup = $line['groupid'];
			$oldrights = $line['rights'];
			
		}
		echo "<BR><span class=form><img src=\"$imasroot/img/help.gif\" alt=\"Help\" onClick=\"window.open('$imasroot/help.php?section=rights','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))\"/> Set User rights to: </span> \n";
		echo "<span class=formright><input type=radio name=\"newrights\" value=\"5\" ";
		if ($oldrights == 5) {echo "CHECKED";}
		echo "> Guest User <BR>\n";
		echo "<input type=radio name=\"newrights\" value=\"10\" ";
		if ($oldrights == 10) {echo "CHECKED";}
		echo "> Student <BR>\n";
		echo "<input type=radio name=\"newrights\" value=\"15\" ";
		if ($oldrights == 15) {echo "CHECKED";}
		echo "> TA/Tutor/Proctor <BR>\n";
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
		
		if ($myrights == 100) {
			echo "<span class=form>Assign to group: </span>";
			echo "<span class=formright><select name=\"group\" id=\"group\">";
			echo "<option value=0>Default</option>\n";
			$query = "SELECT id,name FROM imas_groups";
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
		
		echo "<div class=submit><input type=submit value=Submit></div></form>\n";
		break;
	case "modify":
	case "addcourse":
		if ($_GET['action']=='modify') {
			$query = "SELECT id,name,enrollkey,hideicons,allowunenroll,copyrights,msgset,topbar,cploc,available,lockaid FROM imas_courses WHERE id='{$_GET['id']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$line = mysql_fetch_array($result, MYSQL_ASSOC);
			$courseid = $line['id'];
			$name = $line['name'];
			$ekey = $line['enrollkey'];
			$hideicons = $line['hideicons'];
			$allowunenroll = $line['allowunenroll'];
			$copyrights = $line['copyrights'];
			$msgset = $line['msgset'];
			$cploc = $line['cploc'];
			$topbar = explode('|',$line['topbar']);
			$topbar[0] = explode(',',$topbar[0]);
			$topbar[1] = explode(',',$topbar[1]);
			if ($topbar[0][0] == null) {unset($topbar[0][0]);}
			if ($topbar[1][0] == null) {unset($topbar[1][0]);}
			$avail = $line['available'];
			$lockaid = $line['lockaid'];
		} else {
			$courseid = "Not yet set";
			$name = "Enter course name here";
			$ekey = "Enter enrollment key here";
			$hideicons = 0;
			$allowunenroll = 0;
			$copyrights = 0;
			$msgset = 0;
			$cploc = 0;
			$topbar = array(array(),array());
			$avail = 0;
			$lockaid = 0;
		}
		if (isset($_GET['cid'])) {
			$cid = $_GET['cid'];
			echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"../course/course.php?cid=$cid\">$coursename</a> &gt; Course Settings</div>";
		} 
		
		echo "<form method=post action=\"actions.php?action={$_GET['action']}";
		if (isset($_GET['cid'])) {
			echo "&cid=$cid";
		}
		if ($_GET['action']=="modify") { echo "&id={$_GET['id']}"; }
		echo "\">\n";
		echo "<span class=form>Course ID:</span><span class=formright>$courseid</span><br class=form>\n";
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
			
		echo "<span class=form>Icons:</span><span class=formright>\n";
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
		
		echo 'Folders: <input type=radio name="HIblock" value="0" ';
		if (($hideicons&16)==0) { echo "checked=1";}     
		echo '/> Show <input type=radio name="HIblock" value="16" ';
		if (($hideicons&16)==16) { echo "checked=1";}
		echo '/> Hide</span><br class=form />';
		
		echo "<span class=form>Allow students to self-unenroll</span><span class=formright>";
		echo '<input type=radio name="allowunenroll" value="0" ';
		if ($allowunenroll==0) { echo "checked=1";}
		echo '/> No <input type=radio name="allowunenroll" value="1" ';
		if ($allowunenroll==1) { echo "checked=1";}
		echo '/> Yes </span><br class=form />';
		
		echo "<span class=form>Allow other instructors to copy course items:</span><span class=formright>";
		echo '<input type=radio name="copyrights" value="0" ';
		if ($copyrights==0) { echo "checked=1";}
		echo '/> Require enrollment key from everyone<br/> <input type=radio name="copyrights" value="1" ';
		if ($copyrights==1) { echo "checked=1";}
		echo '/>No key required for group members, require key from others <br/><input type=radio name="copyrights" value="2" ';
		if ($copyrights==2) { echo "checked=1";}
		echo '/>No key required from anyone</span><br class=form />';
		
		echo "<span class=form>Message System:</span><span class=formright>";
		echo '<input type=radio name="msgset" value="0" ';
		if ($msgset==0) { echo "checked=1";}
		echo '/> On for send and receive<br/> <input type=radio name="msgset" value="1" ';
		if ($msgset==1) { echo "checked=1";}
		echo '/> On for receive, students can only send to instructor<br/> <input type=radio name="msgset" value="2" ';
		if ($msgset==2) { echo "checked=1";}
		echo '/> On for receive, students cannot send<br/> <input type=radio name="msgset" value="3" ';
		if ($msgset==3) { echo "checked=1";}
		echo '/> Off</span><br class=form />';
		
		echo "<span class=form>Student Quick Pick Top Bar items:</span><span class=formright>";
		echo '<input type=checkbox name="stutopbar[]" value="0" ';
		if (in_array(0,$topbar[0])) { echo 'checked=1'; }
		echo ' /> Messages <br /><input type=checkbox name="stutopbar[]" value="1" ';
		if (in_array(1,$topbar[0])) { echo 'checked=1'; }
		echo ' /> Gradebook <br /><input type=checkbox name="stutopbar[]" value="9" ';
		if (in_array(9,$topbar[0])) { echo 'checked=1'; }
		echo ' /> Log Out</span><br class=form />';
		
		echo "<span class=form>Instructor Quick Pick Top Bar items:</span><span class=formright>";
		echo '<input type=checkbox name="insttopbar[]" value="0" ';
		if (in_array(0,$topbar[1])) { echo 'checked=1'; }
		echo ' /> Messages<br /><input type=checkbox name="insttopbar[]" value="1" ';
		if (in_array(1,$topbar[1])) { echo 'checked=1'; }
		echo ' /> Student View<br /><input type=checkbox name="insttopbar[]" value="2" ';
		if (in_array(2,$topbar[1])) { echo 'checked=1'; }
		echo ' /> Gradebook<br /><input type=checkbox name="insttopbar[]" value="3" ';
		if (in_array(3,$topbar[1])) { echo 'checked=1'; }
		echo ' /> List Students<br /><input type=checkbox name="insttopbar[]" value="9" ';
		if (in_array(9,$topbar[1])) { echo 'checked=1'; }
		echo ' /> Log Out</span><br class=form />';
		
		echo '<span class=form>Instructor course management links location:</span><span class=formright>';
		echo '<input type=radio name="cploc" value="0" ';
		if ($cploc==0) {echo "checked=1";}
		echo ' /> Bottom of page<br /><input type=radio name="cploc" value="1" ';
		if ($cploc==1) {echo "checked=1";}
		echo ' /> Left side bar</span><br class=form />';
		
		echo "<div class=submit><input type=submit value=Submit></div></form>\n";
		break;
	case "chgteachers":
		$query = "SELECT name FROM imas_courses WHERE id='{$_GET['id']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$line = mysql_fetch_array($result, MYSQL_ASSOC);
		echo "<h2>{$line['name']}</h2>\n";
		
		echo "<h4>Current Teachers:</h4>\n";
		$query = "SELECT imas_users.FirstName,imas_users.LastName,imas_teachers.id,imas_teachers.userid ";
		$query .= "FROM imas_users,imas_teachers WHERE imas_teachers.courseid='{$_GET['id']}' AND ";
		$query .= "imas_teachers.userid=imas_users.id ORDER BY imas_users.LastName;";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		echo "<table cellpadding=5>\n";
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			echo "<tr><td>{$line['LastName']}, {$line['FirstName']}</td> ";
			echo "<td><A href=\"actions.php?action=remteacher&cid={$_GET['id']}&tid={$line['id']}\">Remove as Teacher</a></td></tr>\n";
			$used[$line['userid']] = true;
		}
		echo "</table>\n";
		
		echo "<h4>Potential Teachers:</h4>\n";
		if ($myrights==75) {
			$query = "SELECT id,FirstName,LastName,rights FROM imas_users WHERE rights>14 AND groupid='$groupid' ORDER BY LastName;";
		} else if ($myrights==100) {
			$query = "SELECT id,FirstName,LastName,rights FROM imas_users WHERE rights>14 ORDER BY LastName;";
		}
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		echo "<table cellpadding=5>\n";
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			if ($used[$line['id']]!=true) {
				if ($line['rights']<20) { $type = "Tutor/TA/Proctor";} else {$type = "Teacher";}
				echo "<tr><td>{$line['LastName']}, {$line['FirstName']} </td> ";
				echo "<td><A href=\"actions.php?action=addteacher&cid={$_GET['id']}&tid={$line['id']}\">Add as $type</a></td></tr>\n";
			}
		}
		echo "</table>\n";
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
	case "transfer":
		echo "<h3>Transfer Course Ownership</h3>\n";
		echo "<form method=post action=\"actions.php?action=transfer&id={$_GET['id']}\">\n";
		echo "Transfer to: <select name=newowner>\n";
		$query = "SELECT id,FirstName,LastName FROM imas_users WHERE rights>19";
		if ($myrights < 100) {
			$query .= " AND groupid='$groupid'";
		}
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			echo "<option value=\"$row[0]\">$row[2], $row[1]</option>\n";
		}
		echo "</select>\n";
		echo "<p><input type=submit value=\"Transfer\">\n";
		echo "<input type=button value=\"Nevermind\" onclick=\"window.location='admin.php'\"></p>\n";
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
	case "listgroups":
		echo "<h3>Modify Groups</h3>\n";
		echo "<table><tr><th>Group Name</th><th>Modify</th><th>Delete</th></tr>\n";
		$query = "SELECT id,name FROM imas_groups";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			echo "<tr><td>{$row[1]}</td>";
			echo "<td><a href=\"forms.php?action=modgroup&id={$row[0]}\">Modify</a></td>\n";
			if ($row[0]==0) {
				echo "<td></td>";
			} else {
				echo "<td><a href=\"actions.php?action=delgroup&id={$row[0]}\">Delete</a></td>\n";
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
		$query = "SELECT name FROM imas_groups WHERE id='{$_GET['id']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$gpname = mysql_result($result,0,0);
		echo "<form method=post action=\"actions.php?action=modgroup&id={$_GET['id']}\">\n";
		echo "Group name: <input type=text size=50 name=gpname id=gpname value=\"$gpname\"><br/>\n";
		echo "<input type=submit value=\"Update Group\">\n";
		echo "</form>\n";
		break;
	case "removediag":
		case "delete":
		echo "<p>Are you sure you want to delete this diagnostic?  This does not delete the connected course and does not remove students or their scores.</p>\n";
		echo "<p><input type=button value=\"Delete\" onclick=\"window.location='actions.php?action=removediag&id={$_GET['id']}'\">\n";
		echo "<input type=button value=\"Nevermind\" onclick=\"window.location='admin.php'\"></p>\n";
		break;
}

require("../footer.php");
?>


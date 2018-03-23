<?php
//IMathAS:  Main admin page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../init.php");
require("../includes/htmlutil.php");

/*** pre-html data manipulation, including function code *******/

 //set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = $installname . "Administration";

$curBreadcrumb = "$breadcrumbbase Admin\n";


 if ($myrights>=75) {
	 if (isset($_GET['showcourses'])) {
     $showcourses = Sanitize::onlyInt($_GET['showcourses']);
		 setcookie('showcourses', $showcourses,0,'','',false,true);
	 } else if (isset($_COOKIE['showcourses'])) {
		 $showcourses = $_COOKIE['showcourses'];
	 } else {
		 $showcourses = 0; //0: mine, #: userid
	 }
 } else {
	 $showcourses = 0;
 }
 if ($myrights==100) {
	 if (isset($_GET['showusers'])) {
     $showusers = Sanitize::onlyInt($_GET['showusers']);
		 setcookie('showusers', $showusers, 0,'','',false,true);
	 } else if (isset($_COOKIE['showusers'])) {
		 $showusers = Sanitize::onlyInt($_COOKIE['showusers']);
	 } else {
		 $showusers = $groupid;
	 }
 } else {
	 $showusers = 0;
 }

if ($myrights < 40) {
 	$overwriteBody = 1;
	$body = "You don't have authority to view this page.";
} else {
 //data manipulation here
 //data processing for COURSES block
	//DB $query = "SELECT imas_courses.id,imas_courses.ownerid,imas_courses.name,imas_courses.available,imas_users.FirstName,imas_users.LastName FROM imas_courses,imas_users ";
	//DB $query .= "WHERE imas_courses.ownerid=imas_users.id ";
	$query = "SELECT imas_courses.id,imas_courses.ownerid,imas_courses.name,imas_courses.available,imas_users.FirstName,imas_users.LastName FROM imas_courses,imas_users ";
	$query .= "WHERE imas_courses.ownerid=imas_users.id ";
  $qarr = array();
	if ($myrights<100) { $query .= " AND imas_courses.available<4 ";}
	if (($myrights >= 40 && $myrights<75) || $showcourses==0) {
    //DB $query .= " AND imas_courses.ownerid='$userid'";
    $query .= " AND imas_courses.ownerid=:ownerid";
    $qarr[':ownerid'] = $userid;
  }
	if ($myrights >= 75 && $showcourses>0) {
		//DB $query .= " AND imas_courses.ownerid='$showcourses'";
    $query .= " AND imas_courses.ownerid=:ownerid";
    $qarr[':ownerid'] = $showcourses;
		$query .= " ORDER BY imas_users.LastName,imas_courses.name";
	} else {
		$query .= " ORDER BY imas_courses.name";
	}
  $stm = $DBH->prepare($query);
	$stm->execute($qarr);

	//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
	$page_courseList = array();
	$i=0;
	//DB while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
	while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
		$page_courseList[$i]['id'] = $line['id'];
		$page_courseList[$i]['name'] = $line['name'];
		$page_courseList[$i]['LastName'] = $line['LastName'];
		$page_courseList[$i]['FirstName'] = $line['FirstName'];
		$page_courseList[$i]['ownerid'] = $line['ownerid'];
		$page_courseList[$i]['available'] = $line['available'];
		if (isset($CFG['GEN']['addteachersrights'])) {
			$minrights = $CFG['GEN']['addteachersrights'];
		} else {
			$minrights = 40;
		}
		$page_courseList[$i]['addRemove'] = ($myrights<$minrights) ? "" : "<a href=\"addremoveteachers.php?id=".Sanitize::onlyInt($line['id'])."\" class=\"artl\">Add/Remove</a>";
		$page_courseList[$i]['transfer'] = ($line['ownerid']!=$userid && $myrights <75) ? "" : "<a href=\"transfercourse.php?id=".Sanitize::onlyInt($line['id'])."\" class=\"trl\">Transfer</a>";
		$i++;
	}

	//get list of teachers for the select box
	if ($myrights==75) {
		//DB $query = "SELECT id,LastName,FirstName,SID FROM imas_users WHERE rights>10 AND groupid='$groupid' ORDER BY LastName,FirstName";
		$stm = $DBH->prepare("SELECT id,LastName,FirstName,SID FROM imas_users WHERE rights>10 AND groupid=:groupid ORDER BY LastName,FirstName");
		$stm->execute(array(':groupid'=>$groupid));
	} else if ($myrights==100) {
		//DB $query = "SELECT id,LastName,FirstName,SID FROM imas_users WHERE rights>10 ORDER BY LastName,FirstName";
		$stm = $DBH->query("SELECT id,LastName,FirstName,SID FROM imas_users WHERE rights>10 ORDER BY LastName,FirstName");
	}
	//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
	$i=0;
	//DB while ($row = mysql_fetch_row($result)) {
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$page_teacherSelectVal[$i] = $row[0];
		$page_teacherSelectLabel[$i] = sprintf("%s, %s (%s)", Sanitize::encodeStringForDisplay($row[1]),
            Sanitize::encodeStringForDisplay($row[2]), Sanitize::encodeStringForDisplay($row[3]));
		$i++;
	}

	//data processing for diagnostics block
	if (($myspecialrights&4)==4 || $myrights == 100) {

		if ($myrights<75) {
      //DB $query = "SELECT d.id,d.name,d.public FROM imas_diags as d JOIN imas_users AS u ON u.id=d.ownerid";
			//DB $query .= " WHERE d.ownerid='$userid' ORDER BY d.name";
      $query = "SELECT d.id,d.name,d.public FROM imas_diags as d JOIN imas_users AS u ON u.id=d.ownerid";
			$query .= " WHERE d.ownerid=:ownerid ORDER BY d.name";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':ownerid'=>$userid));
		} else if ($myrights<100) {
      //DB $query = "SELECT d.id,d.name,d.public FROM imas_diags as d JOIN imas_users AS u ON u.id=d.ownerid";
			//DB $query .= " WHERE u.groupid='$groupid' ORDER BY d.name";
      $query = "SELECT d.id,d.name,d.public FROM imas_diags as d JOIN imas_users AS u ON u.id=d.ownerid";
			$query .= " WHERE u.groupid=:groupid ORDER BY d.name";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':groupid'=>$groupid));
		} else {
      $stm = $DBH->query("SELECT id,name,public FROM imas_diags ORDER BY name");
    }
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		$i=0;
		//DB while ($row = mysql_fetch_row($result)) {
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$page_diagnosticsId[$i] = $row[0];
			$page_diagnosticsName[$i] = $row[1];
			$page_diagnosticsAvailable[$i] = ($row[2]&1) ? "Yes" : "No";
			$page_diagnosticsPublic[$i] = ($row[2]&2) ? "Yes" : "No";
			$i++;
		}
	}

	//DATA PROCESSING FOR USERS BLOCK
	if ($myrights < 100) {
		$page_userBlockTitle = "Group - Non Students";
		//DB $query = "SELECT id,SID,FirstName,LastName,rights,lastaccess FROM imas_users WHERE rights > 10 AND groupid='$groupid' ORDER BY LastName";
		$stm = $DBH->prepare("SELECT id,SID,FirstName,LastName,rights,lastaccess FROM imas_users WHERE rights > 10 AND groupid=:groupid ORDER BY LastName");
		$stm->execute(array(':groupid'=>$groupid));
	} else {
		if ($showusers==-1) {
			$page_userBlockTitle = "Pending Users";
			//DB $query = "SELECT id,SID,FirstName,LastName,email,rights,lastaccess FROM imas_users WHERE rights=0 OR rights=12 ORDER BY LastName";
			$stm = $DBH->query("SELECT id,SID,FirstName,LastName,email,rights,lastaccess FROM imas_users WHERE rights=0 OR rights=12 ORDER BY LastName");
		} else if (is_numeric($showusers)) {
			$page_userBlockTitle = "Group Users";
			//DB $query = "SELECT id,SID,FirstName,LastName,email,rights,lastaccess FROM imas_users WHERE rights > 10 AND groupid='$showusers' ORDER BY LastName";
			$stm = $DBH->prepare("SELECT id,SID,FirstName,LastName,email,rights,lastaccess FROM imas_users WHERE rights > 11 AND rights<>76 AND groupid=:groupid ORDER BY LastName");
			$stm->execute(array(':groupid'=>$showusers));
		} else {
			$page_userBlockTitle = "All Users - $showusers";
			//DB $query = "SELECT id,SID,FirstName,LastName,email,rights,lastaccess FROM imas_users WHERE substring(LastName,1,1)='$showusers' ORDER BY LastName";
			$stm = $DBH->prepare("SELECT id,SID,FirstName,LastName,email,rights,lastaccess FROM imas_users WHERE substring(LastName,1,1)=:showusers ORDER BY LastName");
			$stm->execute(array(':showusers'=>$showusers));
		}
	}

	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());

	$i=0;
	//DB while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
	while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
		$page_userDataId[$i] = $line['id'];
		$page_userDataSid[$i] = $line['SID'];
		$page_userDataEmail[$i] = $line['email'];
		$page_userDataLastName[$i] = $line['LastName'];
		$page_userDataFirstName[$i] = $line['FirstName'];
		switch ($line['rights']) {
			case 5: $page_userDataType[$i] = "Guest"; break;
			case 10:$page_userDataType[$i] = "Student"; break;
			case 15: $page_userDataType[$i] = "Tutor/TA/Proctor"; break;
			case 20: $page_userDataType[$i] = "Teacher"; break;
			case 40: $page_userDataType[$i] = "LimCourseCreator"; break;
			case 75: $page_userDataType[$i] = "GroupAdmin"; break;
			case 100: $page_userDataType[$i] = "Admin"; break;
		}
		$page_userDataLastAccess[$i] = ($line['lastaccess']>0) ? date("n/j/y g:i a",$line['lastaccess']) : "never" ;
		$i++;
	}

	//prepare user select
	$page_userSelectVal[0] = -1;
	$page_userSelectLabel[0] = "Pending";
	$page_userSelectVal[1] = 0;
	$page_userSelectLabel[1] = "Default";
	$i=2;
	//DB $query = "SELECT id,name,parent from imas_groups ORDER BY name";
	//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
	$stm = $DBH->query("SELECT id,name,parent from imas_groups ORDER BY name");
	$groupdata = array();
	//DB while ($row = mysql_fetch_row($result)) {
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$groupdata[$row[0]] = $row;
	}
	foreach ($groupdata as $row) {
		$page_userSelectVal[$i] = $row[0];
		$page_userSelectLabel[$i] = $row[1];
		if ($row[2]>0) {
			$page_userSelectLabel[$i] .= ' ('.$groupdata[$row[2]][1].')';
		}
		$i++;
	}
	/*for ($let=ord("A");$let<=ord("Z");$let++) {
		$page_userSelectVal[$i] = chr($let);
		$page_userSelectLabel[$i] = chr($let);
		$i++;
	}*/


}

$placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablesorter.js\"></script>\n";
$placeinhead .= "<script>\nfunction showcourses() { \n";
$placeinhead .= "  var uid=document.getElementById(\"seluid\").value; \n";
$placeinhead .= "  if (uid>0) {window.location='admin.php?showcourses='+uid;} \n";
$placeinhead .= "}\n";
$placeinhead .= "function showgroupusers() { ";
$placeinhead .= "  var grpid=document.getElementById(\"selgrpid\").value; ";
$placeinhead .= "  window.location='admin.php?showusers='+grpid;";
$placeinhead .= "}";
$placeinhead .= '$(function() {$(".artl").attr("title","'._("Add or remove additional teachers").'");';
$placeinhead .= '$(".sl").attr("title","'._("Modify course settings").'");$(".trl").attr("title","'._("Transfer course ownership to someone else").'");});';
$placeinhead .= '</script>';

 /******* begin html output ********/
 require("../header.php");

 if ($overwriteBody==1) {
	echo $body;
} else {

?>
	<div class=breadcrumb><?php echo $curBreadcrumb  ?></div>
	<div id="headeradmin" class="pagetitle"><h2><?php echo $installname ?> Administration</h2></div>
	<b>Hello <?php echo $username ?></b>

<?php //WRITE OUT COURSES BLOCK ?>
	<h3>Courses</h3>
	<div class=item>
	<table class=gb border=0 width="90%">
		<thead>
			<tr><th>Name</th><th>Course ID</th><th>Owner</th><th>Settings</th><th>Teachers</th><th>Transfer</th><th>Delete</th>
			</tr>
		</thead>
		<tbody>
<?php

	$alt = 0;
	for ($i=0;$i<count($page_courseList);$i++) {
		if ($alt==0) {echo "	<tr class=even>"; $alt=1;} else {echo "	<tr class=odd>"; $alt=0;}
?>
				<td><a href="../course/course.php?cid=<?php echo Sanitize::courseId($page_courseList[$i]['id']); ?>">
				<?php
				if (($page_courseList[$i]['available']&1)==1) {
					echo '<i>';
				}
				if (($page_courseList[$i]['available']&2)==2) {
					echo '<span style="color:#aaf;">';
				}
				if (($page_courseList[$i]['available']&4)==4) {
					echo '<span style="color:#faa;text-decoration: line-through;">';
				}

				echo Sanitize::encodeStringForDisplay($page_courseList[$i]['name']);

				if (($page_courseList[$i]['available']&1)==1) {
					echo '</i>';
				}
				if (($page_courseList[$i]['available']&2)==2 || ($page_courseList[$i]['available']&4)==4) {
					echo '</span>';
				}

				?>
				</a>
				</td>
				<td class=c><?php echo Sanitize::onlyInt($page_courseList[$i]['id']); ?></td>
				<td><?php echo Sanitize::encodeStringForDisplay($page_courseList[$i]['LastName']) ?>, <?php echo Sanitize::encodeStringForDisplay($page_courseList[$i]['FirstName']) ?></td>
				<td class=c><a href="forms.php?action=modify&id=<?php echo Sanitize::onlyInt($page_courseList[$i]['id']); ?>" class="sl">Settings</a></td>
				<td class=c><?php echo $page_courseList[$i]['addRemove']; ?></td>
				<td class=c><?php echo $page_courseList[$i]['transfer']; ?></td>
				<td class=c><a href="forms.php?action=delete&id=<?php echo Sanitize::onlyInt($page_courseList[$i]['id']); ?>">Delete</a></td>
			</tr>
<?php
	}
?>
			</tbody>
		</table>
	<input type=button value="Add New Course" onclick="window.location='forms.php?action=addcourse'" />
<?php
	if ($myrights>=75) {
		if ($showcourses>0) {
			echo "<input type=button value=\"Show My Courses\" onclick=\"window.location='admin.php?showcourses=0'\" />";
		}

		echo " Show courses of: ";
		writeHtmlSelect ("seluid",$page_teacherSelectVal,$page_teacherSelectLabel,$showcourses,"Select a user..",0,"onchange=\"showcourses()\"");
	}
?>
	</div>

<?php //END COURSE BLOCK, BEGIN ADMINISTRATION BLOCK ?>

	<h3>Administration</h3>
	<div class=cp>
		<A HREF="forms.php?action=chgpwd">Change my password</a><BR>
		<A HREF="../help.php?section=administration">Help</a><BR>
		<A HREF="actions.php?action=logout">Log Out</a><BR>
	</div>
<?php
	if($myrights<75 && isset($CFG['GEN']['allowteacherexport'])) {
?>
	<div class=cp>
	<a href="export.php?cid=admin">Export Question Set</a><BR>
	<a href="exportlib.php?cid=admin">Export Libraries</a>
	</div>
<?php
	} else if($myrights >= 75) {
?>
	<div class=cp>
	<span class=column>
	<a href="../course/manageqset.php?cid=admin">Manage Question Set</a><BR>
	<a href="../course/managelibs.php?cid=admin">Manage Libraries</a><br/>
	<a href="exportlib.php?cid=admin">Export Libraries</a><BR>
	</span>
<?php
	if ($myrights == 100) {
?>
	<span class=column>
	<a href="forms.php?action=listgroups">Edit Groups</a><br/>
	<a href="importlib.php?cid=admin">Import Libraries</a><br/>
	<a href="importstu.php?cid=admin">Import Students from File</a>
	</span>
	<?php if ($allowmacroinstall) {
		echo '<span class="column">';
		echo "<a href=\"forms.php?action=importmacros\">Install Macro File</a><br/>\n";
		echo "<a href=\"forms.php?action=importqimages\">Install Question Images</a><br/>\n";
		echo "<a href=\"forms.php?action=importcoursefiles\">Install Course Files</a><br/>\n";
		echo '</span>';
	}
	echo '<span class="column">';
	if ($enablebasiclti) {
		echo "<a href=\"forms.php?action=listltidomaincred\">LTI Provider Creds</a><br/>\n";
	}
	echo "<a href=\"forms.php?action=listfedpeers\">Federation Peers</a><br/>\n";
	echo "<a href=\"externaltools.php?cid=admin\">External Tools</a><br/>\n";
	echo '</span>';
	echo '<span class="column">';
	echo "<a href=\"../util/utils.php\">Admin Utilities</a><br/>\n";
	echo '</span>';

	?>

<?php
		}
?>
	<div class=clear></div>
	</div>
<?php
	}
// END OF ADMINISTRATION BLOCK, BEGIN DIAGNOSTICS BLOCK
	if(($myspecialrights&4)==4 || $myrights == 100) {
?>
	<h4>Diagnostics</h4>
	<div class=item>
	<table class=gb width="90%" id="diagTable">
		<thead>
		<tr><th>Name</th><th>Available</th><th>Public</th><th>&nbsp;</th><th>&nbsp;</th><th>&nbsp;</th></tr>
		</thead>
		<tbody>
<?php
        $alt = 0;
	for ($i=0;$i<count($page_diagnosticsId);$i++) {
		if ($alt==0) {echo "	<tr class=even>"; $alt=1;} else {echo "	<tr class=odd>"; $alt=0;}
?>

				<td><a href="<?php echo $imasroot;?>/diag/index.php?id=<?php echo Sanitize::onlyInt($page_diagnosticsId[$i]); ?>">
				<?php echo Sanitize::encodeStringForDisplay($page_diagnosticsName[$i]) ?></a></td>
				<td class=c><?php echo Sanitize::encodeStringForDisplay($page_diagnosticsAvailable[$i]); ?></td>
				<td class=c><?php echo Sanitize::encodeStringForDisplay($page_diagnosticsPublic[$i]); ?></td>
				<td><a href="diagsetup.php?id=<?php echo Sanitize::onlyInt($page_diagnosticsId[$i]); ?>">Modify</a></td>
				<td><a href="forms.php?action=removediag&id=<?php echo Sanitize::onlyInt($page_diagnosticsId[$i]); ?>">Remove</a></td>
				<td><a href="diagonetime.php?id=<?php echo Sanitize::onlyInt($page_diagnosticsId[$i]); ?>">One-time Passwords</a></td>
			</tr>
<?php
	}
?>
		</tbody>
	</table>
	<input type=button value="Add New Diagnostic" onclick="window.location='<?php echo $imasroot;?>/admin/diagsetup.php'">
	</div>

<?php
	}

	//END DIAGNOSITICS BLOCK, BEGIN USERS BLOCK

	if($myrights >= 75) {
?>
	<h4><?php echo $page_userBlockTitle ?></h4>
	<div class=item>
		<table class=gb width="90%" id="myTable">
		<thead>
			<tr><th>Name</th><th>Username</th><th>Email</th><th>Rights</th><th>Last Login</th><th>Rights</th><th>Delete</th></tr>
		</thead>
		<tbody>
<?php
		for ($i=0;$i<count($page_userDataId);$i++) {
			if ($alt==0) {echo "	<tr class=even>"; $alt=1;} else {echo "	<tr class=odd>"; $alt=0;}
?>
				<td><?php echo Sanitize::encodeStringForDisplay($page_userDataLastName[$i]) . ", " . Sanitize::encodeStringForDisplay($page_userDataFirstName[$i]) ?></td>
				<td><?php echo Sanitize::encodeStringForDisplay($page_userDataSid[$i]) ?></td>
				<td><?php echo Sanitize::encodeStringForDisplay($page_userDataEmail[$i]) ?></td>
				<td><?php echo Sanitize::encodeStringForDisplay($page_userDataType[$i]); ?></td>
				<td><?php echo Sanitize::encodeStringForDisplay($page_userDataLastAccess[$i]); ?></td>
				<td class=c><a href="forms.php?action=chgrights&id=<?php echo Sanitize::onlyInt($page_userDataId[$i]); ?>">Change</a></td>
				<td class=c><a href="forms.php?action=deladmin&id=<?php echo Sanitize::onlyInt($page_userDataId[$i]); ?>">Delete</a></td>
			</tr>
<?php
		}
?>
		</tbody>
		</table>
		<script type="text/javascript">
		initSortTable('myTable',Array('S','S','S','S','D',false,false),true);
		</script>

		<input type=button value="Add New User" onclick="window.location='forms.php?action=newadmin'">

<?php
		if ($myrights==100) {
			writeHtmlSelect ("selgrpid",$page_userSelectVal,$page_userSelectLabel,$showusers,null,null,"onchange=\"showgroupusers()\"");
		}

?>
		<p>Passwords reset to: password</p>
	</div>

<?php
	}
}
 require("../footer.php");
?>

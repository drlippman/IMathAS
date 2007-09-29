<?php 
//IMathAS:  Main admin page
//(c) 2006 David Lippman
 require("../validate.php");  //checks for session.  If not, gives login page
 if ($myrights>=75) {
	 if (isset($_GET['showcourses'])) {
		 setcookie('showcourses',$_GET['showcourses']);
		 $showcourses = $_GET['showcourses'];
	 } else if (isset($_COOKIE['showcourses'])) {
		 $showcourses = $_COOKIE['showcourses'];
	 } else {
		 $showcourses = 0; //0: mine, 1: groups, 2: all
	 }
 } else {
	 $showcourses = 0;
 }
 if ($myrights==100) {
	 if (isset($_GET['showusers'])) {
		 setcookie('showusers',$_GET['showusers']);
		 $showusers = $_GET['showusers'];
	 } else if (isset($_COOKIE['showusers'])) {
		 $showusers = $_COOKIE['showusers'];
	 } else {
		 $showusers = 0;
	 }
 } else {
	 $showusers = 0;
 }
 require("../header.php");
 echo "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablesorter.js\"></script>\n";

 if ($myrights < 40) { exit("You don't have authority to view this page.");}
 echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; Admin</div>\n";
 echo "<h2>$installname Administration</h2>";
 echo "<b>Hello $username</b>";


?> 
<h3>Courses</h3>
<div class=item>
<table class=gb border=0 width="90%">
<thead><tr><th>Name</th><th>Course ID</th><th>Owner</th><th>&nbsp;</th><th>&nbsp;</th><th>&nbsp;</th><th>&nbsp;</th></tr></thead>
<tbody>
<?php
$query = "SELECT imas_courses.id,imas_courses.ownerid,imas_courses.name,imas_users.FirstName,imas_users.LastName FROM imas_courses,imas_users ";
$query .= "WHERE imas_courses.ownerid=imas_users.id";
if ($myrights == 40 || $showcourses==0) { $query .= " AND imas_courses.ownerid='$userid'";}
//if ($myrights == 75 || $showcourses==1) { $query .= " AND imas_users.groupid='$groupid'";}
if ($myrights >= 75 && $showcourses>0) {
	$query .= " AND imas_courses.ownerid='$showcourses'";
	$query .= " ORDER BY imas_users.LastName,imas_courses.name";
} else {
	$query .= " ORDER BY imas_courses.name";
}

$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
$alt = 0;
while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
	if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
	echo "<td><a href=\"../course/course.php?cid={$line['id']}\">{$line['name']}</a></td><td class=c>{$line['id']}</td>\n";
	echo "<td>{$line['LastName']}, {$line['FirstName']}</td>\n";
	echo "<td><a href=\"forms.php?action=modify&id={$line['id']}\">Modify</a></td>\n";
	if ($myrights<75) { 
		echo "<td></td>"; 
	} else {
		echo "<td><a href=\"forms.php?action=chgteachers&id={$line['id']}\">Add/Remove Teachers</a></td>\n";
	}
	if ($line['ownerid']!=$userid && $myrights <75) {
		echo "<td></td>";
	} else {
		echo "<td><a href=\"forms.php?action=transfer&id={$line['id']}\">Transfer</a></td>\n";
	}
	echo "<td><a href=\"forms.php?action=delete&id={$line['id']}\">Delete</a></td>\n";
	echo "</tr>\n";
}
?>
</tbody>
</table>
<input type=button value="Add New Course" onclick="window.location='forms.php?action=addcourse'" /> 
<?php
if ($myrights>=75) {
	//echo " Show: ";
	//if ($showcourses == 0 || $showcourses == 2) {
	//	echo "<input type=button value=\"Show Group Courses\" onclick=\"window.location='admin.php?showcourses=1'\" />";
	//} else {
	if ($showcourses>0) {
		echo "<input type=button value=\"Show My Courses\" onclick=\"window.location='admin.php?showcourses=0'\" />";
	}
	echo "<script>function showcourses() { ";
	echo "  var uid=document.getElementById(\"seluid\").value; ";
	echo "  if (uid>0) {window.location='admin.php?showcourses='+uid;}";
	echo "}</script>";
	echo " Show courses of: <select id=\"seluid\" onchange=\"showcourses()\">";
	echo '<option value="0">Select a user..</option>';
	if ($myrights==75) {
		$query = "SELECT id,LastName,FirstName FROM imas_users WHERE rights>10 AND groupid='$groupid' ORDER BY LastName,FirstName";
	} else if ($myrights==100) {
		$query = "SELECT id,LastName,FirstName FROM imas_users WHERE rights>10 ORDER BY LastName,FirstName";
	}
	$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		echo "<option value=\"{$row[0]}\" ";
		if ($showcourses==$row[0]) {
			echo "selected=1";
		}
		echo ">{$row[1]}, {$row[2]}</option>";
	}
	echo '</select>';
}
/*if ($myrights==100) {
	if ($showcourses == 0 || $showcourses == 1) {
		echo "<input type=button value=\"Show All Courses\" onclick=\"window.location='admin.php?showcourses=2'\" />";
	} else {
		echo "<input type=button value=\"Show My Courses\" onclick=\"window.location='admin.php?showcourses=0'\" />";
	}
}
*/
?>
</div>

<h3>Administration</h3>
<div class=cp>
<A HREF="forms.php?action=chgpwd">Change my password</a><BR>
<A HREF="../help.php?section=administration">Help</a><BR>
<A HREF="actions.php?action=logout">Log Out</a><BR>
</div>
<?php if($myrights < 75) {require("../footer.php");  exit;} ?>
<div class=cp>
<span class=column>
<a href="../course/manageqset.php?cid=admin">Manage Question Set</a><BR>
<a href="export.php?cid=admin">Export Question Set</a><BR>
<a href="import.php?cid=admin">Import Question Set</a><BR>
</span>
<span class=column>
<a href="../course/managelibs.php?cid=admin">Manage Libraries</a><br>
<a href="exportlib.php?cid=admin">Export Libraries</a><BR>
<a href="importlib.php?cid=admin">Import Libraries</a></span>
<?php if ($myrights == 100) {
echo "<span class=column>";
if ($allowmacroinstall) { echo "<a href=\"forms.php?action=importmacros\">Install Macro File</a><br/>\n";}
echo <<<END
<a href="forms.php?action=deloldusers">Delete Old Users</a><br/>
<a href="importstu.php?cid=admin">Import Students from File</a><br/>
</span>
<span class=column>
<a href="forms.php?action=listgroups">Edit Groups</a><br/>
</span>
END;
} ?>
<div class=clear></div>
</div>

<h4>Diagnostics</h4>
<div class=item>
<table class=gb width="90%" id="diagTable">
<thead>
<tr><th>Name</th><th>Available</th><th>Public</th><th>&nbsp;</th><th>&nbsp;</th></tr>
</thead>
<tbody>
<?php
$query = "SELECT id,name,public FROM imas_diags WHERE ownerid='$groupid'";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
while ($row = mysql_fetch_row($result)) {
	echo "<tr><td><a href=\"$imasroot/diag/index.php?id={$row[0]}\">{$row[1]}</a></td><td class=c>";
	if ($row[2]&1) {echo 'Yes';} else {echo 'No';}
	echo '</td><td class=c>';
	if ($row[2]&2) {echo 'Yes';} else {echo 'No';}
	echo "</td><td><a href=\"diagsetup.php?id={$row[0]}\">Modify</a></td>";
	echo "<td><a href=\"forms.php?action=removediag&id={$row[0]}\">Remove</a></td></tr>\n";
}
?>
</tbody>
</table>
<input type=button value="Add New Diagnostic" onclick="window.location='<?php echo $imasroot;?>/admin/diagsetup.php'">
</div>

<?
if ($myrights < 100 || $showusers==0) {
	echo "<h4>Group Non Students</h4>\n";
} else {
	if ($showusers==2) {
		echo "<h4>All Users</h4>\n";
	} else if ($showusers==3) {
		echo "<h4>Pending Users</h4>\n";
	} else if ($showusers == 1) { 
		echo "<h4>All Non Students</h4>\n";
	} 
}	
?>
<div class=item>
<table class=gb width="90%" id="myTable">
<thead>
<tr><th>Name</th><th>Username</th><th>Email</th><th>Rights</th><th>Last Login</th><th>Rights</th><th>Password</th><th>Delete</th></tr>
</thead>
<tbody>
<?php 
if ($myrights < 100) {
	$query = "SELECT id,SID,FirstName,LastName,rights,lastaccess FROM imas_users WHERE rights > 10 AND groupid='$groupid' ORDER BY LastName";
} else {
	if (is_numeric($showusers) && $showusers==0) {
		$showusers = $groupid;
	}
	if (is_numeric($showusers)) {
		$query = "SELECT id,SID,FirstName,LastName,email,rights,lastaccess FROM imas_users WHERE rights > 10 AND groupid='$showusers' ORDER BY LastName";
	} else if ($showusers==-1) {
		$query = "SELECT id,SID,FirstName,LastName,email,rights,lastaccess FROM imas_users WHERE rights=0 ORDER BY LastName";
	} else {
		$query = "SELECT id,SID,FirstName,LastName,email,rights,lastaccess FROM imas_users WHERE substring(LastName,1,1)='$showusers' ORDER BY LastName";
	}
}
$result = mysql_query($query) or die("Query failed : " . mysql_error());
$alt = 0;
while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
	if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
	echo "<td>{$line['LastName']}, {$line['FirstName']}</td><td>{$line['SID']}</td><td>{$line['email']}</td>\n";
	echo "<td>";
	switch ($line['rights']) {
		case 5: echo "Guest"; break;
		case 10: echo "Student"; break;
		case 15: echo "Tutor/TA/Proctor"; break;
		case 20: echo "Teacher"; break;
		case 40: echo "LimCourseCreator"; break;
		case 75: echo "GroupAdmin"; break;
		case 100: echo "Admin"; break;
	}
	echo "</td>\n";
	if ($line['lastaccess']>0) {
		$lastaccess = date("n/j/y g:i a",$line['lastaccess']);
	} else {
		$lastaccess = "never";
	}
	echo "<td>$lastaccess</td>\n";
	echo "<td><a href=\"forms.php?action=chgrights&id={$line['id']}\">Change</a></td> \n";
	echo "<td><a href=\"actions.php?action=resetpwd&id={$line['id']}\">Reset</a></td> \n";
	echo "<td><a href=\"forms.php?action=deladmin&id={$line['id']}\">Delete</a></td></tr>\n";
}
?>
</tbody>
</table>
<script type="text/javascript">
initSortTable('myTable',Array('S','S','S','S',false,false,false),true);
</script>

<input type=button value="Add New User" onclick="window.location='forms.php?action=newadmin'">
<?php
if ($myrights == 100) {
	echo "<script>function showgroupusers() { ";
	echo "  var grpid=document.getElementById(\"selgrpid\").value; ";
	echo "  window.location='admin.php?showusers='+grpid;";
	echo "}</script>";
	
	echo " Show: ";
	echo "<select id=\"selgrpid\" onchange=\"showgroupusers()\">";
	echo '<option value="-1" ';
	if ($showusers==-1) {
		echo "selected=1";
	}
	echo '>Pending</option>';
	$query = "SELECT id,name from imas_groups ORDER BY name";
	$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		echo "<option value=\"{$row[0]}\" ";
		if ($row[0] == $showusers) {
			echo "selected=1";
		}
		echo ">{$row[1]}</option>";
	}
	for ($i=ord("A");$i<=ord("Z");$i++) {
		echo "<option value=\"".chr($i)."\" ";
		if (chr($i) == $showusers) {
			echo "selected=1";
		}
		echo ">".chr($i)."</option>";
	}
	echo "</select>";
	
}
?>
<p>*Passwords will be reset to <i>Password</i></p>
</div>

<?php
 require("../footer.php");
?>

 
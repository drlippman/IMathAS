<?php

require("../validate.php");
if ($myrights<100) {
	echo "You are not authorized to view this page";
	exit;
}

$curBreadcrumb = "$breadcrumbbase <a href=\"$imasroot/admin/admin.php\">Admin</a>\n";

if (isset($_GET['removelti'])) {
	$id = intval($_GET['removelti']);
	$query = "DELETE FROM imas_ltiusers WHERE id=$id";
	mysql_query($query) or die("Query failed : " . mysql_error());	
}

if (isset($_GET['form'])) {
	$curBreadcrumb = $curBreadcrumb . " &gt; <a href=\"$imasroot/util/utils.php\">Utils</a> \n";
	
	if ($_GET['form']=='emu') {
		require("../header.php");
		echo '<div class="breadcrumb">'.$curBreadcrumb.' &gt; Emulate User</div>';
		echo '<form method="post" action="'.$imasroot.'/admin/actions.php?action=emulateuser">';
		echo 'Emulate user with userid: <input type="text" size="5" name="uid"/>';
		echo '<input type="submit" value="Go"/>';
		require("../footer.php");
	} else if ($_GET['form']=='rescue') {
		require("../header.php");
		echo '<div class="breadcrumb">'.$curBreadcrumb.' &gt; Recover Items</div>';
		echo '<form method="post" action="'.$imasroot.'/util/rescuecourse.php">';
		echo 'Recover lost items in course ID: <input type="text" size="5" name="cid"/>';
		echo '<input type="submit" value="Go"/>';
		require("../footer.php");
	} else if ($_GET['form']=='lookup') {
		require("../header.php");
		echo '<div class="breadcrumb">'.$curBreadcrumb.' &gt; User Lookup</div>';
		
		if (!empty($_POST['FirstName']) || !empty($_POST['LastName']) || !empty($_POST['SID']) || !empty($_POST['email'])) {
			if (!empty($_POST['SID'])) {
				$query = "SELECT imas_users.*,imas_groups.name FROM imas_users LEFT JOIN imas_groups ON imas_users.groupid=imas_groups.id WHERE imas_users.SID='{$_POST['SID']}'";
			} else if (!empty($_POST['email'])) {
				$query = "SELECT imas_users.*,imas_groups.name FROM imas_users LEFT JOIN imas_groups ON imas_users.groupid=imas_groups.id WHERE imas_users.email='{$_POST['email']}'";
			} else  {
				$query = "SELECT imas_users.*,imas_groups.name FROM imas_users LEFT JOIN imas_groups ON imas_users.groupid=imas_groups.id WHERE ";
				if (!empty($_POST['LastName'])) {
					$query .= "imas_users.LastName='{$_POST['LastName']}' ";
					if (!empty($_POST['FirstName'])) {
						$query .= "AND ";
					}
				}
				if (!empty($_POST['FirstName'])) {
					$query .= "imas_users.FirstName='{$_POST['FirstName']}' ";
				}
				$query .= "ORDER BY imas_users.LastName,imas_users.FirstName";
			}
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_num_rows($result)==0) {
				echo "No results found";
			} else {
				while ($row = mysql_fetch_assoc($result)) {
					echo '<p><b>'.$row['LastName'].', '.$row['FirstName'].'</b></p>';
					echo '<form method="post" action="../admin/actions.php?action=resetpwd&id='.$row['id'].'">';
					echo '<ul><li>Username: <a href="../admin/admin.php?showcourses='.$row['id'].'">'.$row['SID'].'</a></li>';
					echo '<li>ID: '.$row['id'].'</li>';
					if ($row['name']!=null) {
						echo '<li>Group: '.$row['name'].'</li>';
					}
					echo '<li>Email: '.$row['email'].'</li>';
					echo '<li>Last Login: '.tzdate("n/j/y g:ia", $row['lastaccess']).'</li>';
					echo '<li>Rights: '.$row['rights'].'</li>';
					echo '<li>Reset Password to <input type="text" name="newpw"/> <input type="submit" value="'._('Go').'"/></li>';
					$query = "SELECT ic.id,ic.name FROM imas_courses AS ic JOIN imas_students AS istu ON istu.courseid=ic.id AND istu.userid=".$row['id'];
					$res2 = mysql_query($query) or die("Query failed : " . mysql_error());
					if (mysql_num_rows($res2)>0) {
						echo '<li>Enrolled as student in: <ul>';
						while ($r = mysql_fetch_row($res2)) {
							echo '<li><a target="_blank" href="../course/course.php?cid='.$r[0].'">'.$r[1].' (ID '.$r[0].')</a></li>';
						}
						echo '</ul></li>';
					}
					$query = "SELECT ic.id,ic.name FROM imas_courses AS ic JOIN imas_tutors AS istu ON istu.courseid=ic.id AND istu.userid=".$row['id'];
					$res2 = mysql_query($query) or die("Query failed : " . mysql_error());
					if (mysql_num_rows($res2)>0) {
						echo '<li>Tutor in: <ul>';
						while ($r = mysql_fetch_row($res2)) {
							echo '<li><a target="_blank" href="../course/course.php?cid='.$r[0].'">'.$r[1].' (ID '.$r[0].')</a></li>';
						}
						echo '</ul></li>';
					}
					$query = "SELECT ic.id,ic.name FROM imas_courses AS ic JOIN imas_teachers AS istu ON istu.courseid=ic.id AND istu.userid=".$row['id'];
					$res2 = mysql_query($query) or die("Query failed : " . mysql_error());
					if (mysql_num_rows($res2)>0) {
						echo '<li>Teacher in: <ul>';
						while ($r = mysql_fetch_row($res2)) {
							echo '<li><a target="_blank" href="../course/course.php?cid='.$r[0].'">'.$r[1].' (ID '.$r[0].')</a></li>';
						}
						echo '</ul></li>';
					}
					$query = "SELECT org,id FROM imas_ltiusers WHERE userid=".$row['id'];
					$res2 = mysql_query($query) or die("Query failed : " . mysql_error());
					if (mysql_num_rows($res2)>0) {
						echo '<li>LTI connections: <ul>';
						while ($r = mysql_fetch_row($res2)) {
							echo '<li>'.$r[0].' <a href="utils.php?removelti='.$r[1].'">Remove connection</a></li>';
						}
						echo '</ul></li>';
					}
					echo '</ul>';
					echo '</form>';
				}
			}
			
		} else {
			echo '<form method="post" action="utils.php?form=lookup">';
			echo 'Look up user:  LastName: <input type="text" name="LastName" />, FirstName: <input type="text" name="FirstName" />, or username: <input type="text" name="SID"/>, or email: <input type="text" name="email"/>';
			echo '<input type="submit" value="Go"/>';
			
		}
		require("../footer.php");
		
	}
	
	
} else {
	//listing of utilities
	require("../header.php");
	echo '<div class="breadcrumb">'.$curBreadcrumb.' &gt; Utilities</div>';
	echo '<h3>Admin Utilities </h3>';
	if (isset($_GET['debug'])) {
		echo '<p>Debug Mode Enabled - Error reporting is now turned on.</p>';
	}
	echo '<a href="utils.php?form=lookup">User lookup</a><br/>';
	echo '<a href="getstucnt.php">Get Student Count</a><br/>';
	echo '<a href="'.$imasroot.'/admin/approvepending.php">Approve Pending Instructor Accounts</a><br/>';
	echo '<a href="utils.php?debug=true">Enable Debug Mode</a><br/>';
	echo '<a href="replacevids.php">Replace YouTube videos</a><br/>';
	echo '<a href="utils.php?form=rescue">Recover lost items</a><br/>';
	echo '<a href="utils.php?form=emu">Emulate User</a><br/>';
	echo '<a href="listextref.php">List ExtRefs</a><br/>';
	echo '<a href="updateextref.php">Update ExtRefs</a><br/>';
	echo '<a href="listwronglibs.php">List WrongLibFlags</a><br/>';
	echo '<a href="updatewronglibs.php">Update WrongLibFlags</a><br/>';
	echo '<a href="blocksearch.php">Search Block titles</a><br/>';
	echo '<a href="itemsearch.php">Search inline/linked items</a><br/>';
	require("../footer.php");
}
?>

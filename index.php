<?php
//IMathAS:  Main page (class list, enrollment form)
//(c) 2006 David Lippman

/*** master php includes *******/
require("./validate.php");

/*** pre-html data manipulation, including function code *******/
function writeAdminMenuItem() {
	global $myrights;
	if ($myrights > 39) {
		echo "<A HREF=\"admin/admin.php\">Go to Admin page</a><BR>\n";
	}
}

function writeHelpMenuItem() {
	global $myrights;
	if ($myrights > 10) {
		echo "<a href=\"docs/docs.php\">Documentation</a><br/>\n";
	} else if ($myrights > 9) {
		echo "<a href=\"help.php?section=usingimas\">Help Using IMathAS</a><br/>\n";
	}
}

function writeUserInfoMenuItem() {	
	global $myrights;
	if ($myrights > 5) {
		echo "<a href=\"forms.php?action=chgpwd\">Change Password</a><BR>\n";
		echo "<a href=\"forms.php?action=chguserinfo\">Change User Info</a><BR>\n";
	}
}	

function writeLibraryMenuItem() {
	global $myrights;
	if ($myrights>19) {
		echo "<span class=column>";
		echo "<a href=\"course/manageqset.php?cid=0\">Manage Question Set</a><br/>";
		echo "<a href=\"course/managelibs.php?cid=0\">Manage Libraries</a>";
		echo "</span>";
	}
}



$placeinhead = "<style type=\"text/css\">\nh3 { margin: 2px;}\nul { margin: 5px;}\n</style>\n";
$nologo = true;

//create courseid list, this is in prep for creating a course id array as an object property
//or session variable
/*
$page_currentUserCourseIds = array();
$query = "SELECT DISTINCT imas_students.courseid, imas_teachers.courseid ";
$query .= "FROM imas_students, imas_teachers ";
$query .= "WHERE imas_students.userid = '$userid' OR imas_teachers.userid = '$userid'";

$result = mysql_query($query) or die("Query failed : " . mysql_error());
while ($row = mysql_fetch_row($result)) {
	$page_currentUserCourseIds[$row[0]] = $row[0];
	$page_currentUserCourseIds[$row[1]] = $row[1];
}
   
if ($debug==1) {
	echo $query . "<br>\n";
	var_dump($page_currentUserCourseIds);
} 

//check for new posts
$newpostscnt = array();
if (count($page_currentUserCourseIds)>0) {
	$query = "SELECT courseid,count(*) FROM ";
	$query .= "(SELECT imas_forums.courseid,imas_forum_posts.threadid,max(imas_forum_posts.postdate),mfv.lastview FROM imas_forum_posts ";
	$query .= "JOIN imas_forums ON imas_forum_posts.forumid=imas_forums.id LEFT JOIN (SELECT * FROM imas_forum_views WHERE userid='$userid') AS mfv ";
	$query .= "ON mfv.threadid=imas_forum_posts.threadid WHERE imas_forums.courseid IN (";
	$i=0;
	foreach ($page_currentUserCourseIds as $val) {
		$query .= ($i>0) ? ", " : "";
		$query .= "'$val'";
		$i++;
	}
	
	$query .= ") AND imas_forums.grpaid=0 GROUP BY imas_forum_posts.threadid HAVING ((max(imas_forum_posts.postdate)>mfv.lastview) OR (mfv.lastview IS NULL))) AS newitems ";
	$query .= "GROUP BY courseid";
	
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$newpostscnt[$row[0]] = $row[1];
	}
	
	if ($debug==1) {
		echo $query . "<br>\n";
		var_dump($newpostscnt);
	}
}
*/


//check for new posts - do for each type if there are courses
$newpostscnt = array();

//check for new messages    
$newmsgcnt = array();
$query = "SELECT courseid,COUNT(id) FROM imas_msgs WHERE msgto='$userid' AND (isread=0 OR isread=4) GROUP BY courseid";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
while ($row = mysql_fetch_row($result)) {
	$newmsgcnt[$row[0]] = $row[1];
}

// check to see if the user is enrolled as a student
$query = "SELECT imas_courses.name,imas_courses.id FROM imas_students,imas_courses ";
$query .= "WHERE imas_students.courseid=imas_courses.id AND imas_students.userid='$userid' ";
$query .= "AND (imas_courses.available=0 OR imas_courses.available=2) ORDER BY imas_courses.name";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
$line = mysql_fetch_array($result, MYSQL_ASSOC);
if ($line == null) {
	$noclass = true;
} else {
	//check for new posts
	$query = "SELECT courseid,count(*) FROM ";
	$query .= "(SELECT imas_forums.courseid,imas_forum_threads.id FROM imas_forum_threads ";
	$query .= "JOIN imas_forums ON imas_forum_threads.forumid=imas_forums.id LEFT JOIN imas_forum_views AS mfv ";
	$query .= "ON mfv.threadid=imas_forum_threads.id AND mfv.userid='$userid' WHERE imas_forums.courseid IN ";
	$query .= "(SELECT courseid FROM imas_students WHERE userid='$userid') AND imas_forums.grpaid=0 ";
	$query .= "AND (imas_forum_threads.lastposttime>mfv.lastview OR (mfv.lastview IS NULL))) AS newitems ";
	$query .= "GROUP BY courseid";
	$r2 = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_row($r2)) {
		$newpostscnt[$row[0]] = $row[1];
	}
	$page_studentCourseData = array();
	$i=0;
	do {
		$page_studentCourseData[$i] = $line;
		
		if (isset($newmsgcnt[$page_studentCourseData[$i]['id']]) && $newmsgcnt[$page_studentCourseData[$i]['id']]>0) {
			$page_studentCourseData[$i]['courseDisplayTag'] = " <span style=\"color:red\">New Messages ({$newmsgcnt[$page_studentCourseData[$i]['id']]})</span>";
		}
		if (isset($newpostscnt[$page_studentCourseData[$i]['id']]) && $newpostscnt[$page_studentCourseData[$i]['id']]>0) {
			$page_studentCourseData[$i]['courseDisplayTag'] .= " <a href=\"forums/newthreads.php?cid={$page_studentCourseData[$i]['id']}\" style=\"color:red\">New Posts (". $newpostscnt[$page_studentCourseData[$i]['id']] .")</a>";
		}
		
		
		
		$i++;
	} while ($line = mysql_fetch_array($result, MYSQL_ASSOC));
}

//check for classes the current user is teaching
$query = "SELECT imas_courses.name,imas_courses.id,imas_courses.available,imas_courses.lockaid FROM imas_teachers,imas_courses ";
$query .= "WHERE imas_teachers.courseid=imas_courses.id AND imas_teachers.userid='$userid' ";
$query .= "AND (imas_courses.available=0 OR imas_courses.available=1) ORDER BY imas_courses.name";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
$line = mysql_fetch_array($result, MYSQL_ASSOC);

if ($line == null) {
	$isTeaching = false;
} else {
	$isTeaching = true;
	
	//check for forum posts to teachers
	$query = "SELECT courseid,count(*) FROM ";
	$query .= "(SELECT imas_forums.courseid,imas_forum_threads.id FROM imas_forum_threads ";
	$query .= "JOIN imas_forums ON imas_forum_threads.forumid=imas_forums.id LEFT JOIN imas_forum_views AS mfv ";
	$query .= "ON mfv.threadid=imas_forum_threads.id AND mfv.userid='$userid' WHERE imas_forums.courseid IN ";
	$query .= "(SELECT courseid FROM imas_teachers WHERE userid='$userid') AND imas_forums.grpaid=0 ";
	$query .= "AND (imas_forum_threads.lastposttime>mfv.lastview OR (mfv.lastview IS NULL))) AS newitems ";
	$query .= "GROUP BY courseid";
	$r2 = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_row($r2)) {
		$newpostscnt[$row[0]] = $row[1];
	}
	
	$page_teacherCourseData = array();
	$i=0;
	do {
		$page_teacherCourseData[$i] = $line;
		if (($page_teacherCourseData[$i]['available']&1)==1) {
			$page_teacherCourseData[$i]['courseDisplayTag'] = "<span style=\"color:green;\">Hidden</span>";
		}
		
		if ($page_teacherCourseData[$i]['lockaid']>0) {
			$page_teacherCourseData[$i]['courseDisplayTag'] .= " <span style=\"color:green;\">In Lockdown</span>";
		}
		if (isset($newmsgcnt[$page_teacherCourseData[$i]['id']]) && $newmsgcnt[$page_teacherCourseData[$i]['id']]>0) {
			$page_teacherCourseData[$i]['courseDisplayTag'] .= " <a href=\"msgs/msglist.php?cid={$page_teacherCourseData[$i]['id']}\" style=\"color:red\">New Messages ({$newmsgcnt[$page_teacherCourseData[$i]['id']]})</a>";
		}
		if (isset($newpostscnt[$page_teacherCourseData[$i]['id']]) && $newpostscnt[$page_teacherCourseData[$i]['id']]>0) {
			$page_teacherCourseData[$i]['courseDisplayTag'] .= " <a href=\"forums/newthreads.php?cid={$page_teacherCourseData[$i]['id']}\" style=\"color:red\">New Posts (". $newpostscnt[$page_teacherCourseData[$i]['id']] .")</a>";
		}
		
		$i++;
	} while ($line = mysql_fetch_array($result, MYSQL_ASSOC));
}


//check for classes the current user is tutoring
//TODO:  check for new posts for tutors
$query = "SELECT imas_courses.name,imas_courses.id,imas_courses.available,imas_courses.lockaid FROM imas_tutors,imas_courses ";
$query .= "WHERE imas_tutors.courseid=imas_courses.id AND imas_tutors.userid='$userid' ";
$query .= "AND (imas_courses.available=0 OR imas_courses.available=1) ORDER BY imas_courses.name";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
$line = mysql_fetch_array($result, MYSQL_ASSOC);

if ($line == null) {
	$isTutoring = false;
} else {
	$isTutoring = true;
	//check for forum posts to tutors
	$query = "SELECT courseid,count(*) FROM ";
	$query .= "(SELECT imas_forums.courseid,imas_forum_threads.id FROM imas_forum_threads ";
	$query .= "JOIN imas_forums ON imas_forum_threads.forumid=imas_forums.id LEFT JOIN imas_forum_views AS mfv ";
	$query .= "ON mfv.threadid=imas_forum_threads.id AND mfv.userid='$userid' WHERE imas_forums.courseid IN ";
	$query .= "(SELECT courseid FROM imas_tutors WHERE userid='$userid') AND imas_forums.grpaid=0 ";
	$query .= "AND (imas_forum_threads.lastposttime>mfv.lastview OR (mfv.lastview IS NULL))) AS newitems ";
	$query .= "GROUP BY courseid";
	$r2 = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_row($r2)) {
		$newpostscnt[$row[0]] = $row[1];
	}
	$page_tutorCourseData = array();
	$i=0;
	do {
		$page_tutorCourseData[$i] = $line;
		if (($page_tutorCourseData[$i]['available']&1)==1) {
			$page_tutorCourseData[$i]['courseDisplayTag'] = "<span style=\"color:green;\">Hidden</span>";
		}
		
		if ($page_tutorCourseData[$i]['lockaid']>0) {
			$page_tutorCourseData[$i]['courseDisplayTag'] .= " <span style=\"color:green;\">In Lockdown</span>";
		}
		if (isset($newmsgcnt[$page_teacherCourseData[$i]['id']]) && $newmsgcnt[$page_tutorCourseData[$i]['id']]>0) {
			$page_tutorCourseData[$i]['courseDisplayTag'] .= " <a href=\"msgs/msglist.php?cid={$page_tutorCourseData[$i]['id']}\" style=\"color:red\">New Messages ({$newmsgcnt[$page_tutorCourseData[$i]['id']]})</a>";
		}
		if (isset($newpostscnt[$page_tutorCourseData[$i]['id']]) && $newpostscnt[$page_tutorCourseData[$i]['id']]>0) {
			$page_tutorCourseData[$i]['courseDisplayTag'] .= " <a href=\"forums/newthreads.php?cid={$page_tutorCourseData[$i]['id']}\" style=\"color:red\">New Posts (". $newpostscnt[$page_tutorCourseData[$i]['id']] .")</a>";
		}
		
		$i++;
	} while ($line = mysql_fetch_array($result, MYSQL_ASSOC));
}

/******* end data manipulation, start html  ***********/
require("header.php");

/****** begin page body ***********/
echo "<h2>Welcome to $installname, $userfullname</h2>";


//student block	
if ($noclass == true) {
	echo "<h4>You are not currently enrolled in any classes as a student</h4>\n";
} else {
?>		
<div class=block>
	<h3>Courses You're Taking</h3>
</div>
<div class=blockitems>
	<ul class=nomark>
<?php
	for ($i=0;$i<count($page_studentCourseData);$i++) {
?>	
		<li><A HREF="course/course.php?folder=0&cid=<?php echo $page_studentCourseData[$i]['id'] ?>"><?php echo $page_studentCourseData[$i]['name'] ?></a>
		<?php echo $page_studentCourseData[$i]['courseDisplayTag'] ?>
		</li>
<?php
	} 
?>	
	</ul>
</div>
<?php
}
// END STUDENT BLOCK 	

// ENROLLMENT BUTTON AND FORM
if ($myrights > 5) {
	if (!$noclass || $myrights>10) {
?>	
	<p><input id="enrollButton" type=button onClick="document.getElementById('signup').className='signup';this.style.display='none';" value="Enroll in a new class"></p>
<?php
	}
?>	
	<div id="signup" class="<?php echo ($noclass && $myrights<15) ? "signup" : "hidden"; ?>">
		<span>Enroll in a new class: 
			<img align="absmiddle" src="<?php echo $imasroot ?>/img/help.gif" alt="Help" onClick="window.open('<?php echo $imasroot ?>/help.php?section=homepage','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))"/>
			</span>
			<span style="margin-left: 250px;">&nbsp;</span>
			<a href="" onclick="document.getElementById('signup').className='hidden';document.getElementById('enrollButton').style.display='';javascript:return false;">
<?php
			if (!($noclass && $myrights<15)) {
?>
			<span style="text-decoration: none;background-color: #ddf;padding: 3px; border: 1px solid #000;font-size: 70%;">X</span><span style="text-decoration: none; background-color: #ddf;padding: 3px; border: 1px solid #000;font-size: 70%;">Close Form</span>
<?php
			}
?>
			</a>
		<BR>
		<form method=post action="actions.php?action=enroll">
		<span class=form><label for="cid">Course id:</label></span> 
		<input class=form type=text size=6 id=cid name=cid><BR class=form>
		<span class=form><label for="ekey">Enrollment key:</label></span> 
		<input class=form type=text size=10 id="ekey" name="ekey"><BR class=form>
		<div class=submit><input type=submit value="Sign Up"></div>
		</form>
	</div>
<?php	
}

// TUTOR BLOCK 	
if ($isTutoring == true) {
?>
<div class=block>
	<h3>Courses You're Tutoring</h3>
</div>
<div class=blockitems>
	<ul class=nomark>
<?php
	for ($i=0;$i<count($page_tutorCourseData);$i++) {
?>
		<li><A HREF="course/course.php?folder=0&cid=<?php echo $page_tutorCourseData[$i]['id'] ?>"><?php echo $page_tutorCourseData[$i]['name'] ?></a>
		<?php echo $page_tutorCourseData[$i]['courseDisplayTag'] ?>
		</li>
<?php
	}
?>
	</ul>
</div>
<?php
}

// TEACHER BLOCK 	
if ($isTeaching == true) {
?>
<div class=block>
	<h3>Courses You're Teaching</h3>
</div>
<div class=blockitems>
	<ul class=nomark>
<?php
	for ($i=0;$i<count($page_teacherCourseData);$i++) {
?>
		<li><A HREF="course/course.php?folder=0&cid=<?php echo $page_teacherCourseData[$i]['id'] ?>"><?php echo $page_teacherCourseData[$i]['name'] ?></a>
		<?php echo $page_teacherCourseData[$i]['courseDisplayTag'] ?>
		</li>
<?php
	}
?>
	</ul>
</div>
<?php
}
// END TEACHER BLOCK 

//MESSAGING BLOCK
?>	
	<div class=cp>
		<span class=column>
		<a href="<?php echo $imasroot ?>/msgs/msglist.php?cid=0">Messages</a>
		<?php echo (count($newmsgcnt)>0) ? " <span style=\"color:red\">New Messages (" . array_sum($newmsgcnt) . ")</span>" : ""; ?>
		</span>
		
		<div class=clear></div>
	</div>

<!-- ADMINISTRATION MENU BLOCK  -->
	
	<div class=cp>
		<span class=column>
	
	<?php
	writeAdminMenuItem();
	writeHelpMenuItem();
	writeUserInfoMenuItem();
	?>
	
			<a href="actions.php?action=logout">Log Out</a>
		</span>
	
	<?php writeLibraryMenuItem(); ?>
		<div class=clear></div>
	</div>
	
<?php	
	require("footer.php");
?>
	

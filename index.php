<?php
//IMathAS:  Main page (class list, enrollment form)
//(c) 2006 David Lippman
   require("validate.php");
   $placeinhead = "<style type=\"text/css\">\nh3 { margin: 2px;}\nul { margin: 5px;}\n</style>\n";
   $nologo = true;
   require("header.php");
   echo "<h2>Welcome to $installname, $userfullname</h2>";


	$newmsgcnt = array();
	$query = "SELECT courseid,COUNT(id) FROM imas_msgs WHERE msgto='$userid' AND (isread=0 OR isread=4) GROUP BY courseid";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$newmsgcnt[$row[0]] = $row[1];
	}

	$query = "SELECT imas_courses.name,imas_courses.id FROM imas_students,imas_courses ";
	$query .= "WHERE imas_students.courseid=imas_courses.id AND imas_students.userid='$userid' ORDER BY imas_courses.name";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$line = mysql_fetch_array($result, MYSQL_ASSOC);
	if ($line == null) {
		echo "<h4>You are not currently enrolled in any classes as a student</h4>\n";
		$noclass = true;
	} else {
			
		echo "<div class=block>";
		echo "<h3>Courses You're Taking</h3>";
		echo "</div>";
		echo "<div class=blockitems>";
		echo "<ul class=nomark>\n";
		do {
			echo "<li><A HREF=\"course/course.php?folder=0&cid={$line['id']}\">{$line['name']}</a>";
			if (isset($newmsgcnt[$line['id']]) && $newmsgcnt[$line['id']]>0) {
				echo " <span style=\"color:red\">New Messages ({$newmsgcnt[$line['id']]})</span>";
			}
			
			echo "</li>\n";
		} while ($line = mysql_fetch_array($result, MYSQL_ASSOC));
		echo "</ul>\n";
		echo "</div>\n";
	}
	if ($myrights > 5) {
		if (!$noclass || $myrights>10) {
			echo "<p><input type=button onClick=\"document.getElementById('signup').className='signup';this.style.display='none';\" value=\"Enroll in a new class\"></p>\n";
		}
		echo "<div id=\"signup\" class=";
		if ($noclass && $myrights<15) { echo '"signup"'; } else {echo '"hidden"';}
		echo ">Enroll in a new class: <img src=\"$imasroot/img/help.gif\" alt=\"Help\" onClick=\"window.open('$imasroot/help.php?section=homepage','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))\"/><BR>\n";
		echo "<form method=post action=\"actions.php?action=enroll\">\n";
		echo "<span class=form><label for=\"cid\">Course id:</label></span> <input class=form type=text size=6 id=cid name=cid><BR class=form>\n";
		echo "<span class=form><label for=\"ekey\">Enrollment key:</label></span> <input class=form type=text size=10 id=\"ekey\" name=\"ekey\"><BR class=form>\n";
		echo "<div class=submit><input type=submit value='Sign Up'></div></form></div>\n";
	}
	$query = "SELECT imas_courses.name,imas_courses.id FROM imas_teachers,imas_courses ";
	$query .= "WHERE imas_teachers.courseid=imas_courses.id AND imas_teachers.userid='$userid' ORDER BY imas_courses.name";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$line = mysql_fetch_array($result, MYSQL_ASSOC);
	if ($line != null) {
		
		echo "<div class=block>";
		echo "<h3>Courses You're Teaching</h3>";
		echo "</div><div class=blockitems>\n";
		echo "<ul class=nomark>\n";
		do {
			echo "<li><A HREF=\"course/course.php?folder=0&cid={$line['id']}\">{$line['name']}</a>";
			if (isset($newmsgcnt[$line['id']]) && $newmsgcnt[$line['id']]>0) {
				echo " <span style=\"color:red\">New Messages ({$newmsgcnt[$line['id']]})</span>";
			}
			
			echo "</li>\n";
		} while ($line = mysql_fetch_array($result, MYSQL_ASSOC));
		echo "</ul>\n";
		echo "</div>\n";
	}
	echo "<div class=cp>\n";
	echo "<span class=column>";
	echo "<a href=\"$imasroot/msgs/msglist.php?cid=0\">Messages</a> ";
	if (count($newmsgcnt)>0) {
	   echo " <span style=\"color:red\">New Messages (";
	   echo array_sum($newmsgcnt);
	   echo ")</span>";
	}
	echo "</span>";
	echo "<div class=clear></div></div>\n";
	
	echo "<div class=cp>\n";
	echo "<span class=column>";
	if ($myrights > 39) {
		echo "<A HREF=\"admin/admin.php\">Go to Admin page</a><BR>\n";
	}
	if ($myrights > 10) {
		echo "<a href=\"docs/docs.html\">Documentation</a><br/>\n";
	} else if ($myrights > 9) {
		echo "<a href=\"help.php?section=usingimas\">Help Using IMathAS</a><br/>\n";
	}
	if ($myrights > 5) {
		echo "<a href=\"forms.php?action=chgpwd\">Change Password</a><BR>\n";
		echo "<a href=\"forms.php?action=chguserinfo\">Change User Info</a><BR>\n";
	}
	echo "<a href=\"actions.php?action=logout\">Log Out</a>";
	echo "</span>";
	if ($myrights>19) {
		echo "<span class=column>";
		echo "<a href=\"course/manageqset.php?cid=0\">Manage Question Set</a><br/>";
		echo "<a href=\"course/managelibs.php?cid=0\">Manage Libraries</a>";
		echo "</span>";
	}
	echo "<div class=clear></div></div>\n";
	
	
	require("footer.php");
?>
	

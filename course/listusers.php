<?php
//IMathAS:  List students in a course
//(c) 2006 David Lippman
	require("../validate.php");
	if (!(isset($teacherid))) {
		require("../header.php");
		echo "You need to log in as a teacher to access this page";
		require("../footer.php");
		exit;
	}
	$cid = $_GET['cid'];
	if (isset($_GET['assigncode'])) {
		if (isset($_POST['submit'])) {
			$keys = array_keys($_POST['sec']);
			foreach ($keys as $stuid) {
				if ($_POST['sec'][$stuid]=='') {
					$_POST['sec'][$stuid] = "NULL";
				} else {
					$_POST['sec'][$stuid] = "'".$_POST['sec'][$stuid]."'";
				}
				if ($_POST['code'][$stuid]=='') {
					$_POST['code'][$stuid] = "NULL";
				} else {
					$_POST['code'][$stuid] = intval($_POST['code'][$stuid]);
				}
			}
			foreach ($keys as $stuid) {
				$query = "UPDATE imas_students SET section={$_POST['sec'][$stuid]},code={$_POST['code'][$stuid]} WHERE id='$stuid'";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/listusers.php?cid=$cid");
			exit;
	
		} else {
			$query = "SELECT imas_students.id,imas_users.FirstName,imas_users.LastName,imas_students.section,imas_students.code ";
			$query .= "FROM imas_students,imas_users WHERE imas_students.courseid='$cid' AND imas_students.userid=imas_users.id ORDER BY imas_users.LastName,imas_users.FirstName";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			require("../header.php");
			echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
			echo "&gt; <a href=\"listusers.php?cid=$cid\">List Students</a> &gt; Assign Codes</div>\n";
			echo "<h3>Assign Section/Code Numbers</h3>";
			echo "<form method=post action=\"listusers.php?cid=$cid&assigncode=1\">\n";
			echo "<table class=gb><thead><tr><th>Name</th><th>Section</th><th>Code</th></tr></thead><tbody>";
			while ($line=mysql_fetch_array($result, MYSQL_ASSOC)) {
				echo "<tr><td>{$line['LastName']}, {$line['FirstName']}</td>";
				echo "<td><input type=text name=\"sec[{$line['id']}]\" value=\"{$line['section']}\"/></td>";
				echo "<td><input type=text name=\"code[{$line['id']}]\" value=\"{$line['code']}\"/></td></tr>";
			}
			echo "</tbody></table>";
			echo "<input type=submit name=submit value=\"Submit\"/>";
			echo "</form>";
			require("../footer.php");
			exit;
		}
	}
	if (isset($_GET['enroll'])) {
		if (isset($_POST['username'])) {
			$query = "SELECT id FROM imas_users WHERE SID='{$_POST['username']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_num_rows($result)==0) {
				echo "<html><body>Error, username doesn't exist. <a href=\"listusers.php?cid=$cid&enroll=student\">Try again</a></body></html>\n";
				exit;
			} else {
				$id = mysql_result($result,0,0);
				$vals = "$id,$cid";
				$query = "INSERT INTO imas_students (userid,courseid";
				if (trim($_POST['section'])!='') {
					$query .= ",section";
					$vals .= ",".$_POST['section'];
				}
				if (trim($_POST['code'])!='') {
					$query .= ",code";
					$vals .= ",".$_POST['code'];
				}
				$query .= ") VALUES ($vals)";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/listusers.php?cid=$cid");
			exit;
		} else {
			require("../header.php");
			echo "<form method=post action=\"listusers.php?cid=$cid&enroll=student\">\n";
			echo "<span class=form>Username to enroll:</span><span class=formright>";
			echo "<input type=text name=username></span><br class=form>\n";
			echo "<span class=form>Section (optional):</span><span class=formright>";
			echo "<input type=text name=section></span><br class=form>\n";
			echo "<span class=form>Code (optional):</span><span class=formright>";
			echo "<input type=text name=code></span><br class=form>\n";
			
			echo "<div class=submit><input type=submit value=\"Enroll\"></div>\n";
			echo "</form>\n";
			require("../footer.php");
			exit;
		}
	}
	if (isset($_GET['newstu'])) {
		if (isset($_POST['SID'])) {
			$query = "SELECT id FROM imas_users WHERE SID='{$_POST['SID']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_num_rows($result)>0) {
				echo "<html><body>\n";
				echo "$loginprompt '{$_POST['SID']}' is used.  <a href=\"listusers.php?cid=$cid&newstu=new\">Try Again</a>\n";
				echo "</html></body>\n";
				exit;
			} else {
				$md5pw = md5($_POST['pw1']);
				$query = "INSERT INTO imas_users (SID, password, rights, FirstName, LastName, email, msgnotify) ";
				$query .= "VALUES ('{$_POST['SID']}','$md5pw',10,'{$_POST['firstname']}','{$_POST['lastname']}','{$_POST['email']}',0);";
				mysql_query($query) or die("Query failed : " . mysql_error());
				$newuserid = mysql_insert_id();
				$query = "INSERT INTO imas_students (userid,courseid) VALUES ($newuserid,'$cid')";
				mysql_query($query) or die("Query failed : " . mysql_error());
				header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/listusers.php?cid=$cid");
				exit;
			}
		} else {
			require("../header.php");
			echo "<h3>Create and Enroll New Student</h3>";
			echo "<form method=post action=\"listusers.php?cid=$cid&newstu=new\">\n";
			echo "<span class=form><label for=\"SID\">$loginprompt:</label></span> <input class=form type=text size=12 id=SID name=SID><BR class=form>\n";
			echo "<span class=form><label for=\"pw1\">Choose a password:</label></span><input class=form type=password size=20 id=pw1 name=pw1><BR class=form>\n";
			echo "<span class=form><label for=\"firstname\">Enter First Name:</label></span> <input class=form type=text size=20 id=firstnam name=firstname><BR class=form>\n";
			echo "<span class=form><label for=\"lastname\">Enter Last Name:</label></span> <input class=form type=text size=20 id=lastname name=lastname><BR class=form>\n";
			echo "<span class=form><label for=\"email\">Enter E-mail address:</label></span>  <input class=form type=text size=60 id=email name=email><BR class=form>\n";
			echo "<div class=submit><input type=submit value=\"Create and Enroll\"></div>\n";
			echo "</form>";
			require("../footer.php");
			exit;
		}
	}
	if (isset($_GET['chgstuinfo'])) {
		if (isset($_POST['firstname'])) {
			$query = "UPDATE imas_users SET FirstName='{$_POST['firstname']}',LastName='{$_POST['lastname']}',email='{$_POST['email']}'";
			if (isset($_POST['doresetpw'])) {
				$newpw = md5($_POST['password']);
				$query .= ",password='$newpw'";
			}
			$query .= " WHERE id='{$_GET['uid']}'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/listusers.php?cid=$cid");
			exit;
		} else {
			require("../header.php");
			$query = "SELECT * FROM imas_users WHERE id='{$_GET['uid']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$line = mysql_fetch_array($result, MYSQL_ASSOC);
			echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
			echo "&gt; <a href=\"listusers.php?cid=$cid\">List Students</a> &gt; Change Student Info</div>\n";
			echo "<h3>Change Student Info</h3>\n";
			echo "<form method=post action=\"listusers.php?cid=$cid&chgstuinfo=true&uid={$_GET['uid']}\">\n";
			echo "<span class=form><label for=\"firstname\">Enter First Name:</label></span> <input class=form type=text size=20 id=firstname name=firstname value=\"{$line['FirstName']}\"><BR class=form>\n";
			echo "<span class=form><label for=\"lastname\">Enter Last Name:</label></span> <input class=form type=text size=20 id=lastname name=lastname value=\"{$line['LastName']}\"><BR class=form>\n";
			echo "<span class=form><label for=\"email\">Enter E-mail address:</label></span>  <input class=form type=text size=60 id=email name=email value=\"{$line['email']}\"><BR class=form>\n";
			echo "<span class=form>Reset password?</span><span class=formright><input type=checkbox name=\"doresetpw\" value=\"1\" /> Reset to: <input type=text size=20 name=\"password\" /></span><br class=form />\n";
			echo "<div class=submit><input type=submit value=\"Update Info\"></div>\n";
			echo "</form>";
			require("../footer.php");
			exit;
			
		}
		
	}
	if ((isset($_POST['submit']) && ($_POST['submit']=="E-mail" || $_POST['submit']=="Message"))|| isset($_GET['masssend']))  {
		$calledfrom='lu';
		include("masssend.php");
	}
	if ((isset($_POST['submit']) && $_POST['submit']=="Make Exception") || isset($_GET['massexception'])) {
		$calledfrom='lu';
		include("massexception.php");
	}
	if (isset($_POST['submit']) && $_POST['submit']=="Unenroll") {
		$_GET['action'] = "unenroll";
		if (isset($_POST['ca'])) {
			$_GET['uid'] = "all";
		} else {
			$_GET['uid'] = "selected";
		}
	}
	if (isset($_GET['action'])) {
		if (isset($_GET['confirmed'])) {
			if (isset($_GET['action']) && $_GET['action']=="resetpw") {
				$newpw = "5f4dcc3b5aa765d61d8327deb882cf99";  //md5("password")
				$query = "UPDATE imas_users SET password='$newpw' WHERE id='{$_GET['uid']}'";
				mysql_query($query) or die("Query failed : " . mysql_error());
			} else if ($_GET['action']=="unenroll") {
				if ($_GET['uid']=="selected") {
					$tounenroll = explode(",",$_POST['tounenroll']);
				} else if ($_GET['uid']=="all") {
					$query = "SELECT userid FROM imas_students WHERE courseid='$cid'";
					$result = mysql_query($query) or die("Query failed : " . mysql_error());
					while ($row = mysql_fetch_row($result)) {
						$tounenroll[] = $row[0];
					}
				} else {
					$tounenroll[] = $_GET['uid'];
				}
				
				if (count($tounenroll)>0) {
				 foreach ($tounenroll as $uid) {
					$query = "DELETE FROM imas_students WHERE userid='$uid' AND courseid='$cid'";
					mysql_query($query) or die("Query failed : " . mysql_error());
					$query = "SELECT id FROM imas_assessments WHERE courseid='$cid'";
					$result = mysql_query($query) or die("Query failed : " . mysql_error());
					while ($row = mysql_fetch_row($result)) {
						$query = "DELETE FROM imas_assessment_sessions WHERE assessmentid='{$row[0]}' AND userid='$uid'";
						mysql_query($query) or die("Query failed : " . mysql_error());
						$query = "DELETE FROM imas_exceptions WHERE assessmentid='{$row[0]}' AND userid='$uid'";
						mysql_query($query) or die("Query failed : " . mysql_error());
					}
					$query = "SELECT id FROM imas_gbitems WHERE courseid='$cid'";
					$result = mysql_query($query) or die("Query failed : " . mysql_error());
					while ($row = mysql_fetch_row($result)) {
						$query = "DELETE FROM imas_grades WHERE gbitemid='{$row[0]}' AND userid='$uid'";
						mysql_query($query) or die("Query failed : " . mysql_error());
					}
					
					$query = "SELECT id FROM imas_forums WHERE courseid='$cid'";
					$result = mysql_query($query) or die("Query failed : " . mysql_error());
					while ($row = mysql_fetch_row($result)) {
						$q2 = "SELECT threadid FROM imas_forum_posts WHERE forumid='{$row[0]}'";
						$r2 = mysql_query($q2) or die("Query failed : " . mysql_error());
						while ($rw2 = mysql_fetch_row($r2)) {
							$query = "DELETE FROM imas_forum_views WHERE threadid='{$rw2[0]}' AND userid='$uid'";
							mysql_query($query) or die("Query failed : " . mysql_error());
						}
						if ($_GET['uid']=="all" || isset($_POST['delforumposts'])) {
							$query = "DELETE FROM imas_forum_posts WHERE forumid='{$row[0]}' AND posttype=0";
							mysql_query($query) or die("Query failed : " . mysql_error());
						}
						
					}
				 }
				}
				if ($_GET['uid']=="all" && isset($_POST['removeoffline'])) {
					$query = "DELETE from imas_gbitems WHERE courseid='$cid'";
					mysql_query($query) or die("Query failed : " . mysql_error());
				}
			}
			
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/listusers.php?cid=$cid");
			exit;
		} else {
			require("../header.php");
			echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
			echo "&gt; <a href=\"listusers.php?cid=$cid\">List Students</a> &gt Confirm Change</div>\n";
			echo "<form method=post action=\"listusers.php?cid=$cid&action={$_GET['action']}&uid={$_GET['uid']}&confirmed=true\">\n";
			if ((isset($_GET['action']) && $_GET['action']=="unenroll") || (isset($_POST['submit']) && $_POST['submit']=="Unenroll Students")) {
				if ($_GET['uid']=="all") {
					echo "Are you SURE you want to unenroll ALL students?\n";
					echo "<p>This will also clear all regular posts from all class forums</p>\n";
					echo "<p>Also remove all offline grade items from gradebook? <input type=checkbox name=\"removeoffline\" value=\"1\" /></p>";
				} else if ($_GET['uid']=="selected") {
					if (count($_POST['checked'])==0) {
						echo "No users selected.  <a href=\"listusers.php?cid=$cid\">Try again</a>\n";
						echo "</form>";
						require("../footer.php");
						exit;
					}
					echo "Are you SURE you want to unenroll the selected students?\n";
					echo '<ul>';
					$ulist = "'".implode("','",$_POST['checked'])."'";
					$query = "SELECT LastName,FirstName,SID FROM imas_users WHERE id IN ($ulist)";
					$result = mysql_query($query) or die("Query failed : " . mysql_error());
					while ($row = mysql_fetch_row($result)) {
						echo "<li>{$row[0]}, {$row[1]} ({$row[2]})</li>";
					}
					echo '</ul>';
					$query = "SELECT COUNT(id) FROM imas_students WHERE courseid='{$_GET['cid']}'";
					$result = mysql_query($query) or die("Query failed : " . mysql_error());
					if (count($_POST['checked']) > floor(mysql_result($result,0,0)/2)) {
						echo "<p>Also delete <b>ALL</b> forum posts by ALL students (not just the selected ones)? <input type=checkbox name=\"delforumposts\"/></p>";
					}
					echo "<input type=hidden name=\"tounenroll\" value=\"".implode(",",$_POST['checked'])."\">\n";
				} else {
					$query = "SELECT FirstName,LastName,SID FROM imas_users WHERE id='{$_GET['uid']}'";
					$result = mysql_query($query) or die("Query failed : " . mysql_error());
					$row = mysql_fetch_row($result);
					echo "Are you SURE you want to unenroll {$row[0]} {$row[1]} ($row[2])?";
				}
			} else if ($_GET['action']=="resetpw") {
				echo "Are you sure you want to reset this student's password?\n";	
			} 
			//echo "<p><input type=button value=\"Yes, I'm Sure\" onclick=\"window.location='listusers.php?cid=$cid&action={$_GET['action']}&uid={$_GET['uid']}&confirmed=true'\">\n";
			echo "<p><input type=submit value=\"Yes, I'm Sure\">\n";
			echo "<input type=button value=\"Nevermind\" onclick=\"window.location='listusers.php?cid=$cid'\"></p>\n";
			echo "</form>\n";
			require("../footer.php");
			exit;
		}
		
	}
	$pagetitle = "Student List";
	require("../header.php");
	
	echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
	echo "&gt; List Students</div>\n";
	echo "<h3>Students</h3>\n";
	
	echo <<<END
<script type="text/javascript">
function chkAll(frm, arr, mark) {
  for (i = 0; i <= frm.elements.length; i++) {
   try{
     if(frm.elements[i].name == arr) {
       frm.elements[i].checked = mark;
     }
   } catch(er) {}
  }
}
</script>
END;
	$query = "SELECT count(id) FROM imas_students WHERE imas_students.courseid='$cid' AND imas_students.section IS NOT NULL";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	if (mysql_result($result,0,0)>0) {
		$hassection = true;
	} else {
		$hassection = false;
	}
	$query = "SELECT count(id) FROM imas_students WHERE imas_students.courseid='$cid' AND imas_students.code IS NOT NULL";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	if (mysql_result($result,0,0)>0) {
		$hascode = true;
	} else {
		$hascode = false;
	}	
	
	$query = "SELECT imas_students.id,imas_students.userid,imas_users.FirstName,imas_users.LastName,imas_users.email,imas_users.SID,imas_users.lastaccess,imas_students.section,imas_students.code ";
	$query .= "FROM imas_students,imas_users WHERE imas_students.courseid='$cid' AND imas_students.userid=imas_users.id ";
	if ($hassection) {
		$query .= "ORDER BY imas_students.section,imas_users.LastName,imas_users.FirstName";
	} else {
		$query .= "ORDER BY imas_users.LastName,imas_users.FirstName";
	}
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	echo "<form method=post action=\"listusers.php?cid=$cid\">\n";
	echo "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablesorter.js\"></script>\n";
	echo "Check/Uncheck All: <input type=\"checkbox\" name=\"ca\" value=\"1\" onClick=\"chkAll(this.form, 'checked[]', this.checked)\"> \n";
	echo "With Selected:  <input type=submit name=submit value=\"E-mail\"> <input type=submit name=submit value=\"Message\"> <input type=submit name=submit value=\"Unenroll\"> <input type=submit name=submit value=\"Make Exception\">\n";
	
	echo "<table class=gb id=myTable>\n";
	echo "<thead><tr><th></th>";
	if ($hassection) {
		echo "<th>Section</th>";
	}
	if ($hascode) {
		echo "<th>Code</th>";
	}
	echo "<th>Last</th><th>First</th><th>Email</th><th>$loginprompt</th><th>Last Login</th><th>Grades</th><th>Due Dates</th><th>Chg Info</th><th>Unenroll</th></tr></thead>\n";
	$alt = 0;
	echo "<tbody>\n";
	while ($line=mysql_fetch_array($result, MYSQL_ASSOC)) {
		if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
		if ($line['lastaccess']>0) {
			$lastaccess = date("n/j/y g:ia",$line['lastaccess']);
		} else {
			$lastaccess = "never";
		}
		echo "<td><input type=checkbox name='checked[]' value='{$line['userid']}'></td>";
		if ($hassection) {
			echo "<td>{$line['section']}</td>";
		}
		if ($hascode) {
			echo "<td>{$line['code']}</td>";
		}
		echo "<td>{$line['LastName']}</td><td>{$line['FirstName']}</td>\n";
		echo "<td><a href=\"mailto:{$line['email']}\">{$line['email']}</a></td>\n";
		echo "<td>{$line['SID']}</td>\n";
		echo "<td>$lastaccess</td>\n";
		echo "<td><a href=\"gradebook.php?cid=$cid&stu={$line['userid']}&from=listusers\">Grades</a></td>\n";
		echo "<td><a href=\"exception.php?cid=$cid&uid={$line['userid']}\">Exception</a></td>\n";
		//echo "<td><a href=\"listusers.php?cid=$cid&action=resetpw&uid={$line['userid']}\">Reset Password</a></td>\n";
		echo "<td><a href=\"listusers.php?cid=$cid&chgstuinfo=true&uid={$line['userid']}\">Chg</a></td>\n";
		echo "<td><a href=\"listusers.php?cid=$cid&action=unenroll&uid={$line['userid']}\">Unenroll</a></td>\n";
		echo "</tr>\n\n";
	}
	echo "</tbody></table>\n";
	echo "<script type=\"text/javascript\">\n";
	echo "initSortTable('myTable',Array(false,";
	if ($hassection) {
		echo "'S',";
	} 
	if ($hascode) {
		echo "'N',";
	}
	echo "'S','S','S','S','D',false,false,false),true);\n";
	echo "</script>\n";
	echo "</form>\n";
	
	echo "<p><input type=button value=\"Unenroll All Students\" onclick=\"window.location='listusers.php?cid=$cid&action=unenroll&uid=all'\"></p>\n";
	echo "<div class=cp>";
	echo "<a href=\"$imasroot/admin/importstu.php?cid=$cid\">Import Students from File</a><br/>\n";
	echo "<a href=\"listusers.php?cid=$cid&enroll=student\">Enroll Student with known username</a><br/>\n";
	echo "<a href=\"listusers.php?cid=$cid&newstu=new\">Create and Enroll new student</a><br/>\n";
	echo "<a href=\"listusers.php?cid=$cid&assigncode=1\">Assign Sections and/or Codes</a>\n";
	echo "</div>";
	require("../footer.php");
?>

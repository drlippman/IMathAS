<?php
//var_dump($_GET);
//var_dump($_POST);
//IMathAS:  Main course page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../init.php");


/*** pre-html data manipulation, including function code *******/

//set some page specific variables and counters
$cid = Sanitize::courseId($_GET['cid']);
if (isset($_GET['secfilter'])) {
	$secfilter = $_GET['secfilter'];
	$sessiondata[$cid.'secfilter'] = $secfilter;
	writesessiondata();
} else if (isset($sessiondata[$cid.'secfilter'])) {
	$secfilter = $sessiondata[$cid.'secfilter'];
} else {
	$secfilter = -1;
}
if (isset($_GET['rmode'])) {
	$rmode = $_GET['rmode'];
	$sessiondata[$cid.'rmode'] = $rmode;
	writesessiondata();
} else if (isset($sessiondata[$cid.'rmode'])) {
	$rmode = $sessiondata[$cid.'rmode'];
} else {
	$rmode = 0;
}
$showemail = (($rmode&1)==1);
$showSID = (($rmode&2)==2);

$overwriteBody = 0;
$body = "";
$pagetitle = "";
$hasInclude = 0;
if (!isset($CFG['GEN']['allowinstraddstus'])) {
	$CFG['GEN']['allowinstraddstus'] = true;
}
if (!isset($CFG['GEN']['allowinstraddtutors'])) {
	$CFG['GEN']['allowinstraddtutors'] = true;
}
$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\"> ".Sanitize::encodeStringForDisplay($coursename)."</a>\n";

if (!isset($teacherid)) { // loaded by a NON-teacher
	$overwriteBody=1;
	$body = "You need to log in as a teacher to access this page";
} else { // PERMISSIONS ARE OK, PROCEED WITH PROCESSING

	if (isset($_POST['submit']) && $_POST['submit']=="Unenroll") {
		$_GET['action'] = "unenroll";
	}
	if (isset($_POST['submit']) && $_POST['submit']=="Lock") {
		$_GET['action'] = "lock";
	}
	if (isset($_POST['lockinstead'])) {
		$_GET['action'] = "lock";
		$_POST['tolock'] = $_POST['tounenroll'];
	}

	if (isset($_GET['assigncode'])) {

		$curBreadcrumb .= " &gt; <a href=\"listusers.php?cid=$cid\">Roster</a> &gt; Assign Codes\n";
		$pagetitle = "Assign Section/Code Numbers";

		if (isset($_POST['submit'])) {
			$keys = array_keys($_POST['sec']);
			foreach ($keys as $stuid) {
				if ($_POST['sec'][$stuid]=='') {
					//DB $_POST['sec'][$stuid] = "NULL";
					$_POST['sec'][$stuid] = null;
				//DB } else {
				//DB 	$_POST['sec'][$stuid] = "'".$_POST['sec'][$stuid]."'";
			  }
				if ($_POST['code'][$stuid]=='') {
					//DB $_POST['code'][$stuid] = "NULL";
					$_POST['code'][$stuid] = null;
				//DB } else {
				//DB 	$_POST['code'][$stuid] = intval($_POST['code'][$stuid]);
				}
			}
			foreach ($keys as $stuid) {
				//DB $query = "UPDATE imas_students SET section={$_POST['sec'][$stuid]},code={$_POST['code'][$stuid]} WHERE id='$stuid' AND courseid='$cid' ";
				//DB mysql_query($query) or die("Query failed : " . mysql_error());
				$stm = $DBH->prepare("UPDATE imas_students SET section=:section,code=:code WHERE id=:id AND courseid=:courseid ");
				$stm->execute(array(':section'=>$_POST['sec'][$stuid], ':code'=>$_POST['code'][$stuid], ':id'=>$stuid, ':courseid'=>$cid));
			}
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/listusers.php?cid=$cid&r=" . Sanitize::randomQueryStringParam());
			exit;

		} else {
			//DB $query = "SELECT imas_students.id,imas_users.FirstName,imas_users.LastName,imas_students.section,imas_students.code ";
			//DB $query .= "FROM imas_students,imas_users WHERE imas_students.courseid='$cid' AND imas_students.userid=imas_users.id ";
			//DB $query .= "ORDER BY imas_users.LastName,imas_users.FirstName";
			//DB $resultStudentList = mysql_query($query) or die("Query failed : " . mysql_error());
			$query = "SELECT imas_students.id,imas_users.FirstName,imas_users.LastName,imas_students.section,imas_students.code ";
			$query .= "FROM imas_students,imas_users WHERE imas_students.courseid=:courseid AND imas_students.userid=imas_users.id ";
			$query .= "ORDER BY imas_users.LastName,imas_users.FirstName";
			$resultStudentList = $DBH->prepare($query);
			$resultStudentList->execute(array(':courseid'=>$cid));
		}
	} elseif (isset($_GET['enroll']) && ($myrights==100 || (isset($CFG['GEN']['allowinstraddbyusername']) && $CFG['GEN']['allowinstraddbyusername']==true))) {

		$curBreadcrumb .= " &gt; <a href=\"listusers.php?cid=$cid\">Roster</a> &gt; Enroll Students\n";
		$pagetitle = "Enroll an Existing User";

		if (isset($_POST['username'])) {
			//DB $query = "SELECT id FROM imas_users WHERE SID='{$_POST['username']}'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB if (mysql_num_rows($result)==0) {
			$stm = $DBH->prepare("SELECT id FROM imas_users WHERE SID=:SID");
			$stm->execute(array(':SID'=>$_POST['username']));
			if ($stm->rowCount()==0) {
				$overwriteBody = 1;
				$body = "Error, username doesn't exist. <a href=\"listusers.php?cid=$cid&enroll=student\">Try again</a>\n";
				if ($CFG['GEN']['allowinstraddstus']) {
					$body .= "or <a href=\"listusers.php?cid=$cid&newstu=new\">create and enroll a new student</a>";
				}
			} else {
				//DB $id = mysql_result($result,0,0);
				$id = $stm->fetchColumn(0);
				//DB $query = "SELECT id FROM imas_teachers WHERE userid='$id' AND courseid='$cid'";
				//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
				//DB if (mysql_num_rows($result)>0) {
				$stm = $DBH->prepare("SELECT id FROM imas_teachers WHERE userid=:userid AND courseid=:courseid");
				$stm->execute(array(':userid'=>$id, ':courseid'=>$cid));
				if ($stm->rowCount()>0) {
					echo "Teachers can't be enrolled as students - use Student View, or create a separate student account.";
					exit;
				}
				//DB $query = "SELECT id FROM imas_tutors WHERE userid='$id' AND courseid='$cid'";
				//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
				//DB if (mysql_num_rows($result)>0) {
				$stm = $DBH->prepare("SELECT id FROM imas_tutors WHERE userid=:userid AND courseid=:courseid");
				$stm->execute(array(':userid'=>$id, ':courseid'=>$cid));
				if ($stm->rowCount()>0) {
					echo "Tutors can't be enrolled as students.";
					exit;
				}
				//DB $query = "SELECT id FROM imas_students WHERE userid='$id' AND courseid='$cid'";
				//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
				//DB if (mysql_num_rows($result)>0) {
				$stm = $DBH->prepare("SELECT id FROM imas_students WHERE userid=:userid AND courseid=:courseid");
				$stm->execute(array(':userid'=>$id, ':courseid'=>$cid));
				if ($stm->rowCount()>0) {
					echo "This username is already enrolled in the class.";
					exit;
				}
				//DB $query = "SELECT deflatepass FROM imas_courses WHERE id='$cid'";
				//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
				//DB $row = mysql_fetch_row($result);
				$stm = $DBH->prepare("SELECT deflatepass FROM imas_courses WHERE id=:id");
				$stm->execute(array(':id'=>$cid));
				$row = $stm->fetch(PDO::FETCH_NUM);
				$deflatepass = $row[0];

				//DB $vals = "$id,'$cid','$deflatepass'";
				//DB $query = "INSERT INTO imas_students (userid,courseid,latepass";
				$query = "INSERT INTO imas_students (userid,courseid,latepass,section,code) ";
				$query .= "VALUES (:userid,:courseid,:latepass,:section,:code)";
				$stm = $DBH->prepare($query);
				$stm->execute(array(":userid"=>$id,":courseid"=>$cid,":latepass"=>$deflatepass,
					":section"=>trim($_POST['section'])!=''?trim($_POST['section']):null,
					":code"=>trim($_POST['code'])!=''?trim($_POST['code']):null
					));
				//DB if (trim($_POST['section'])!='') {
				//DB 	$query .= ",section";
					//DB $vals .= ",'".$_POST['section']."'";
				//DB }
				//DB if (trim($_POST['code'])!='') {
				//DB 	$query .= ",code";
					//DB $vals .= ",'".$_POST['code']."'";
				//DB }
				//DB $query .= ") VALUES ($vals)";
				//DB mysql_query($query) or die("Query failed : " . mysql_error());

				header('Location: ' . $GLOBALS['basesiteurl'] . "/course/listusers.php?cid=$cid&r=" . Sanitize::randomQueryStringParam());
				exit;
			}

		}
	} elseif (isset($_GET['newstu']) && $CFG['GEN']['allowinstraddstus']) {
		$curBreadcrumb .= " &gt; <a href=\"listusers.php?cid=$cid\">Roster</a> &gt; Enroll Students\n";
		$pagetitle = "Enroll a New Student";
		$placeinhead .= '<script type="text/javascript" src="'.$imasroot.'/javascript/jquery.validate.min.js?v=122917"></script>';

		if (isset($_POST['SID'])) {
			require_once("../includes/newusercommon.php");
			$errors = checkNewUserValidation(array('SID','firstname','lastname','email','pw1'));
			if ($errors != '') {
				$overwriteBody = 1;
				$body = $errors . "<br/><a href=\"listusers.php?cid=$cid&newstu=new\">Try Again</a>\n";
			} else {
				if (isset($CFG['GEN']['newpasswords'])) {
					require_once("../includes/password.php");
					$md5pw = password_hash($_POST['pw1'], PASSWORD_DEFAULT);
				} else {
					$md5pw = md5($_POST['pw1']);
				}
				//DB $query = "INSERT INTO imas_users (SID, password, rights, FirstName, LastName, email, msgnotify) ";
				//DB $query .= "VALUES ('{$_POST['SID']}','$md5pw',10,'{$_POST['firstname']}','{$_POST['lastname']}','{$_POST['email']}',0);";
				//DB mysql_query($query) or die("Query failed : " . mysql_error());
				//DB $newuserid = mysql_insert_id();
				$query = "INSERT INTO imas_users (SID, password, rights, FirstName, LastName, email, msgnotify, forcepwreset) ";
				$query .= "VALUES (:SID, :password, :rights, :FirstName, :LastName, :email, :msgnotify, 1);";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':SID'=>$_POST['SID'], ':password'=>$md5pw, ':rights'=>10,
					':FirstName'=>$_POST['firstname'], ':LastName'=>$_POST['lastname'], ':email'=>$_POST['email'], ':msgnotify'=>0));
				$newuserid = $DBH->lastInsertId();
				//$query = "INSERT INTO imas_students (userid,courseid) VALUES ($newuserid,'$cid')";
				//DB $query = "SELECT deflatepass FROM imas_courses WHERE id='$cid'";
				//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
				//DB $row = mysql_fetch_row($result);
				$stm = $DBH->prepare("SELECT deflatepass FROM imas_courses WHERE id=:id");
				$stm->execute(array(':id'=>$cid));
				$row = $stm->fetch(PDO::FETCH_NUM);
				$deflatepass = $row[0];

				//DB $vals = "$newuserid,'$cid','$deflatepass'";
				//DB $query = "INSERT INTO imas_students (userid,courseid,latepass";
				//DB if (trim($_POST['section'])!='') {
				//DB 	$query .= ",section";
				//DB 	$vals .= ",'".$_POST['section']."'";
				//DB }
				//DB if (trim($_POST['code'])!='') {
				//DB 	$query .= ",code";
				//DB 	$vals .= ",'".$_POST['code']."'";
				//DB }
				//DB $query .= ") VALUES ($vals)";
				//DB mysql_query($query) or die("Query failed : " . mysql_error());
				$query = "INSERT INTO imas_students (userid,courseid,latepass,section,code) ";
				$query .= "VALUES (:userid,:courseid,:latepass,:section,:code)";
				$stm = $DBH->prepare($query);
				$stm->execute(array(":userid"=>$newuserid,":courseid"=>$cid,":latepass"=>$deflatepass,
					":section"=>trim($_POST['section'])!=''?trim($_POST['section']):null,
					":code"=>trim($_POST['code'])!=''?trim($_POST['code']):null
					));

				header('Location: ' . $GLOBALS['basesiteurl'] . "/course/listusers.php?cid=$cid&r=" . Sanitize::randomQueryStringParam());
				exit;
			}
		}
	} elseif (isset($_POST['submit']) && $_POST['submit']=="Copy Emails") {
		$curBreadcrumb .= " &gt; <a href=\"listusers.php?cid=$cid\">Roster</a> &gt; Copy Emails\n";
		$pagetitle = "Copy Student Emails";
		if (count($_POST['checked'])>0) {
			//DB $ulist = "'".implode("','",$_POST['checked'])."'";$ulist = "'".implode("','",$_POST['checked'])."'";
			$ulist = implode(',', array_map('intval', $_POST['checked']));
			//DB $query = "SELECT imas_users.FirstName,imas_users.LastName,imas_users.email ";
			//DB $query .= "FROM imas_students JOIN imas_users ON imas_students.userid=imas_users.id WHERE imas_students.courseid='$cid' AND imas_users.id IN ($ulist)";
			//DB $query .= "ORDER BY imas_users.LastName,imas_users.FirstName";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			$query = "SELECT imas_users.FirstName,imas_users.LastName,imas_users.email ";
			$query .= "FROM imas_students JOIN imas_users ON imas_students.userid=imas_users.id WHERE imas_students.courseid=:courseid AND imas_users.id IN ($ulist) ";
			$query .= "ORDER BY imas_users.LastName,imas_users.FirstName";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':courseid'=>$cid));
			$stuemails = array();
			//DB while ($row = mysql_fetch_row($result)) {
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$stuemails[] = Sanitize::encodeStringForDisplay($row[0]) . ' ' . Sanitize::encodeStringForDisplay($row[1]) .  ' &lt;' . Sanitize::encodeStringForDisplay($row[2]) . '&gt;';
			}
			$stuemails = implode('; ',$stuemails);
		}

	} elseif (isset($_GET['chgstuinfo'])) {
		$curBreadcrumb .= " &gt; <a href=\"listusers.php?cid=$cid\">Roster</a> &gt; Change User Info\n";
		$pagetitle = "Change Student Info";
		$placeinhead .= '<script type="text/javascript" src="'.$imasroot.'/javascript/jquery.validate.min.js?v=122917"></script>';
		require_once("../includes/newusercommon.php");

		if (isset($_POST['firstname'])) {
			$msgout = '';
			if (checkFormatAgainstRegex($_POST['SID'], $loginformat)) {
				$un = $_POST['SID'];
				$updateusername = true;
			} else {
				$updateusername = false;
			}

			//DB $query = "SELECT id FROM imas_users WHERE SID='$un'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB if (mysql_num_rows($result)>0) {
			$stm = $DBH->prepare("SELECT id FROM imas_users WHERE SID=:SID");
			$stm->execute(array(':SID'=>$un));
			if ($stm->rowCount()>0) {
				$updateusername = false;
			}
			if ($updateusername) {
				$msgout .= '<p>Username changed to '.Sanitize::encodeStringForDisplay($un).'</p>';
			} else {
				$msgout .= '<p>Username left unchanged</p>';
			}
			//DB $query = "UPDATE imas_users SET FirstName='{$_POST['firstname']}',LastName='{$_POST['lastname']}',email='{$_POST['email']}'";
			$query = "UPDATE imas_users SET FirstName=:FirstName,LastName=:LastName";

			$qarr = array(':FirstName'=>$_POST['firstname'], ':LastName'=>$_POST['lastname']);
			if ($updateusername) {
				//DB $query .= ",SID='$un'";
				$query .= ",SID=:SID";
				$qarr[':SID'] = $un;
			}
			if (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/',$_POST['email']) ||
		    (isset($CFG['acct']['emailFormat']) && !checkFormatAgainstRegex($_POST['email'], $CFG['acct']['emailFormat']))) {
		    $msgout .= '<p>Invalid email address - left unchanged</p>';
		  } else {
				$query .= ",email=:email";
				$qarr[':email'] = $_POST['email'];
			}
			if (isset($_POST['doresetpw'])) {
				if (isset($CFG['acct']['passwordFormat']) && !checkFormatAgainstRegex($_POST['pw1'], $CFG['acct']['passwordFormat'])) {
					$msgout .= '<p>Invalid password - left unchanged</p>';
				} else {
					if (isset($CFG['GEN']['newpasswords'])) {
						require_once("../includes/password.php");
						$newpw = password_hash($_POST['pw1'], PASSWORD_DEFAULT);
					} else {
						$newpw = md5($_POST['pw1']);
					}
					//DB $query .= ",password='$newpw'";
					$query .= ",password=:password,forcepwreset=1";
					$qarr[':password'] = $newpw;
					$msgout .= '<p>Password updated</p>';
				}
			}

			//DB $query .= " WHERE id='{$_GET['uid']}'";
			$query .= " WHERE id=:id";
			$qarr[':id'] = $_GET['uid'];
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare($query);
			$stm->execute($qarr);

			//DB $code = "'{$_POST['code']}'";
			//DB $section = "'{$_POST['section']}'";
			$code = $_POST['code'];
			$section = $_POST['section'];
			if (trim($_POST['section'])==='') {
				//DB $section = "NULL";
				$section = null;
			}
			if (trim($_POST['code'])==='') {
				//DB $code = "NULL";
				$code = null;
			}
			if (isset($_POST['locked'])) {
				$locked = time();
			} else {
				$locked = 0;
			}
			if (isset($_POST['hidefromcourselist'])) {
				$hide = 1;
			} else {
				$hide = 0;
			}
			$timelimitmult = floatval($_POST['timelimitmult']);
			//echo $timelimitmult;
			if ($timelimitmult <= 0) {
				$timelimitmult = '1.0';
			}
			$latepasses = intval($_POST['latepasses']);
			//echo $timelimitmult;

			if ($locked==0) {
				//DB $query = "UPDATE imas_students SET code=$code,section=$section,locked=$locked,timelimitmult='$timelimitmult',hidefromcourselist=$hide WHERE userid='{$_GET['uid']}' AND courseid='$cid'";
				//DB mysql_query($query) or die("Query failed : " . mysql_error());
				$stm = $DBH->prepare("UPDATE imas_students SET code=:code,section=:section,locked=:locked,timelimitmult=:timelimitmult,hidefromcourselist=:hidefromcourselist,latepass=:latepass WHERE userid=:userid AND courseid=:courseid");
				$stm->execute(array(':code'=>$code, ':section'=>$section, ':locked'=>$locked, ':timelimitmult'=>$timelimitmult, ':hidefromcourselist'=>$hide, ':latepass'=>$latepasses, ':userid'=>$_GET['uid'], ':courseid'=>$cid));
			} else {
				//DB $query = "UPDATE imas_students SET code=$code,section=$section,timelimitmult='$timelimitmult',hidefromcourselist=$hide WHERE userid='{$_GET['uid']}' AND courseid='$cid'";
				//DB mysql_query($query) or die("Query failed : " . mysql_error());
				$stm = $DBH->prepare("UPDATE imas_students SET code=:code,section=:section,timelimitmult=:timelimitmult,hidefromcourselist=:hidefromcourselist,latepass=:latepass WHERE userid=:userid AND courseid=:courseid");
				$stm->execute(array(':code'=>$code, ':section'=>$section, ':timelimitmult'=>$timelimitmult, ':hidefromcourselist'=>$hide, ':latepass'=>$latepasses, ':userid'=>$_GET['uid'], ':courseid'=>$cid));
				//DB $query = "UPDATE imas_students SET locked=$locked WHERE userid='{$_GET['uid']}' AND courseid='$cid' AND locked=0";
				//DB mysql_query($query) or die("Query failed : " . mysql_error());
				$stm = $DBH->prepare("UPDATE imas_students SET locked=:locked WHERE userid=:userid AND courseid=:courseid AND locked=0");
				$stm->execute(array(':locked'=>$locked, ':userid'=>$_GET['uid'], ':courseid'=>$cid));
			}

			require('../includes/userpics.php');

			// $_FILES[]['tmp_name'] is not user provided. This is safe.
			if (is_uploaded_file($_FILES['stupic']['tmp_name'])) {
				processImage($_FILES['stupic'],Sanitize::onlyInt($_GET['uid']),200,200);
				processImage($_FILES['stupic'],'sm'.Sanitize::onlyInt($_GET['uid']),40,40);
				$chguserimg = 1;
			} else if (isset($_POST['removepic'])) {
				deletecoursefile('userimg_'.Sanitize::onlyInt($_GET['uid']).'.jpg');
				deletecoursefile('userimg_sm'.Sanitize::onlyInt($_GET['uid']).'.jpg');
				$chguserimg = 0;
			} else {
				$chguserimg = -1;
			}
			if ($chguserimg != -1) {
				//DB $query = "UPDATE imas_users SET $chguserimg WHERE id='{$_GET['uid']}'";
				//DB mysql_query($query) or die("Query failed : " . mysql_error());
				$stm = $DBH->prepare("UPDATE imas_users SET hasuserimg=:chguserimg WHERE id=:id");
				$stm->execute(array(':id'=>$_GET['uid'], ':chguserimg'=>$chguserimg));
			}


			require("../header.php");
			echo '<div class="breadcrumb">'.$curBreadcrumb.'</div>';
			echo '<div id="headerlistusers" class="pagetitle"><h2>'.$pagetitle.'</h2></div>';
			echo "<p>User info updated. ";
			echo $msgout;
			echo "</p><p><a href=\"listusers.php?cid=$cid\">OK</a></p>";
			require("../footer.php");

			//header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/listusers.php?cid=$cid");
			exit;
		} else {
			//DB $query = "SELECT imas_users.*,imas_students.code,imas_students.section,imas_students.locked,imas_students.timelimitmult,imas_students.hidefromcourselist FROM imas_users,imas_students ";
			//DB $query .= "WHERE imas_users.id=imas_students.userid AND imas_users.id='{$_GET['uid']}' AND imas_students.courseid='$cid'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $lineStudent = mysql_fetch_array($result, MYSQL_ASSOC);
			$query = "SELECT imas_users.*,imas_students.code,imas_students.section,imas_students.locked,imas_students.timelimitmult,imas_students.hidefromcourselist,imas_students.latepass FROM imas_users,imas_students ";
			$query .= "WHERE imas_users.id=imas_students.userid AND imas_users.id=:id AND imas_students.courseid=:courseid";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':id'=>$_GET['uid'], ':courseid'=>$cid));
			$lineStudent = $stm->fetch(PDO::FETCH_ASSOC);

		}

	} elseif ((isset($_POST['submit']) && ($_POST['submit']=="E-mail" || $_POST['submit']=="Message"))|| isset($_GET['masssend']))  {
		$calledfrom='lu';
		$overwriteBody = 1;
		$fileToInclude = "masssend.php";
	} elseif ((isset($_POST['submit']) && $_POST['submit']=="Make Exception") || isset($_GET['massexception'])) {
		$calledfrom='lu';
		$overwriteBody = 1;
		$fileToInclude = "massexception.php";
	} elseif (isset($_GET['action']) && $_GET['action']=="unenroll" && !isset($CFG['GEN']['noInstrUnenroll'])){
		$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\"> ".Sanitize::encodeStringForDisplay($coursename)."</a>\n";
		$curBreadcrumb .= " &gt; <a href=\"listusers.php?cid=$cid\">Roster</a> &gt; Confirm Change\n";
		$pagetitle = "Unenroll Students";
		$calledfrom='lu';
		$overwriteBody = 1;
		$fileToInclude = "unenroll.php";

	} elseif (isset($_GET['action']) && $_GET['action']=="lock") {
		$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\"> ".Sanitize::encodeStringForDisplay($coursename)."</a>\n";
		$curBreadcrumb .= " &gt; <a href=\"listusers.php?cid=$cid\">Roster</a> &gt; Confirm Change\n";
		$pagetitle = "LockStudents";
		$calledfrom='lu';
		$overwriteBody = 1;
		$fileToInclude = "lockstu.php";

	} elseif (isset($_POST['action']) && $_POST['action']=="lockone" && is_numeric($_POST['uid'])) {
		$now = time();
		//DB $query = "UPDATE imas_students SET locked='$now' WHERE courseid='$cid' AND userid=".intval($_GET['uid']);
		//DB mysql_query($query) or die("Query failed : " . mysql_error());
		$stm = $DBH->prepare("UPDATE imas_students SET locked=:locked WHERE courseid=:courseid AND userid=:userid");
		$stm->execute(array(':locked'=>$now, ':courseid'=>$cid, ':userid'=>$_POST['uid']));

		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/listusers.php?cid=$cid&r=" . Sanitize::randomQueryStringParam());
		exit;
	} elseif (isset($_POST['action']) && $_POST['action']=="unlockone" && is_numeric($_POST['uid'])) {
		$now = time();
		//DB $query = "UPDATE imas_students SET locked=0 WHERE courseid='$cid' AND userid=".intval($_GET['uid']);
		//DB mysql_query($query) or die("Query failed : " . mysql_error());
		$stm = $DBH->prepare("UPDATE imas_students SET locked=0 WHERE courseid=:courseid AND userid=:userid");
		$stm->execute(array(':courseid'=>$cid, ':userid'=>$_POST['uid']));

		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/listusers.php?cid=$cid&r=" . Sanitize::randomQueryStringParam());
		exit;
	} else { //DEFAULT DATA MANIPULATION HERE

		$curBreadcrumb .= " &gt; Roster\n";
		$pagetitle = "Student Roster";

		//DB $query = "SELECT DISTINCT section FROM imas_students WHERE imas_students.courseid='$cid' AND imas_students.section IS NOT NULL ORDER BY section";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB if (mysql_num_rows($result)>0) {
		$stm = $DBH->prepare("SELECT DISTINCT section FROM imas_students WHERE imas_students.courseid=:courseid AND imas_students.section IS NOT NULL ORDER BY section");
		$stm->execute(array(':courseid'=>$cid));
		if ($stm->rowCount()>0) {
			$hassection = true;
			$sectionselect = "<br/><select id=\"secfiltersel\" onchange=\"chgsecfilter()\"><option value=\"-1\" ";
			if ($secfilter==-1) {$sectionselect .= 'selected=1';}
			$sectionselect .=  '>All</option>';
			//DB while ($row = mysql_fetch_row($result)) {
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$sectionselect .=  "<option value=\"" . Sanitize::encodeStringForDisplay($row[0]) . "\" ";
				if ($row[0]==$secfilter) {
					$sectionselect .=  'selected=1';
				}
				$sectionselect .=  ">". Sanitize::encodeStringForDisplay($row[0]) . "</option>";
			}
			$sectionselect .=  "</select>";
		} else {
			$hassection = false;
		}
		//DB $query = "SELECT count(id) FROM imas_students WHERE imas_students.courseid='$cid' AND imas_students.code IS NOT NULL";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB if (mysql_result($result,0,0)>0) {
		$stm = $DBH->prepare("SELECT count(id) FROM imas_students WHERE imas_students.courseid=:courseid AND imas_students.code IS NOT NULL");
		$stm->execute(array(':courseid'=>$cid));
		if ($stm->fetchColumn(0)>0) {
			$hascode = true;
		} else {
			$hascode = false;
		}

		if ($hassection) {
			//DB $query = "SELECT usersort FROM imas_gbscheme WHERE courseid='$cid'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $row = mysql_fetch_row($result);
			$stm = $DBH->prepare("SELECT usersort FROM imas_gbscheme WHERE courseid=:courseid");
			$stm->execute(array(':courseid'=>$cid));
			$row = $stm->fetch(PDO::FETCH_NUM);
			$sectionsort = ($row[0]==0);
		} else {
			$sectionsort = false;
		}
		//DB $query = "SELECT imas_students.id,imas_students.userid,imas_users.FirstName,imas_users.LastName,imas_users.email,imas_users.SID,imas_students.lastaccess,imas_students.section,imas_students.code,imas_students.locked,imas_users.hasuserimg,imas_students.timelimitmult ";
		//DB $query .= "FROM imas_students,imas_users WHERE imas_students.courseid='$cid' AND imas_students.userid=imas_users.id ";
		//DB if ($secfilter>-1) {
			//DB $query .= "AND imas_students.section='$secfilter' ";
		//DB }
		//DB if ($sectionsort) {
			//DB $query .= "ORDER BY imas_students.section,imas_users.LastName,imas_users.FirstName";
		//DB } else {
			//DB $query .= "ORDER BY imas_users.LastName,imas_users.FirstName";
		//DB }
		//DB $resultDefaultUserList = mysql_query($query) or die("Query failed : " . mysql_error());
		$haslatepasses = false;

		$query = "SELECT imas_students.id,imas_students.userid,imas_users.FirstName,imas_users.LastName,imas_users.email,imas_users.SID,imas_students.lastaccess,";
		$query .= "imas_students.section,imas_students.code,imas_students.locked,imas_users.hasuserimg,imas_students.timelimitmult,imas_students.latepass ";
		$query .= "FROM imas_students,imas_users WHERE imas_students.courseid=:courseid AND imas_students.userid=imas_users.id ";
		if ($secfilter!=-1) {
			$query .= "AND imas_students.section=:section ";
		}
		if ($sectionsort) {
			$query .= "ORDER BY imas_students.section,imas_users.LastName,imas_users.FirstName";
		} else {
			$query .= "ORDER BY imas_users.LastName,imas_users.FirstName";
		}
		$resultDefaultUserList = $DBH->prepare($query);
		if ($secfilter!=-1) {
			$resultDefaultUserList->execute(array(':courseid'=>$cid, ':section'=>$secfilter));
		} else {
			$resultDefaultUserList->execute(array(':courseid'=>$cid));
		}
		$defaultUserList = array();
		while ($line=$resultDefaultUserList->fetch(PDO::FETCH_ASSOC)) {
			$defaultUserList[] = $line;
			if ($line['latepass']>0) {
				$haslatepasses=true;
			}
		}
		$hasSectionRowHeader = ($hassection)? "<th>Section$sectionselect</th>" : "";
		$hasCodeRowHeader = ($hascode) ? "<th>Code</th>" : "";
		$hasLatePassHeader = ($haslatepasses) ? "<th>LatePasses</th>" : "";
		$hasSectionSortTable = ($hassection) ? "'S'," : "";
		$hasCodeSortTable = ($hascode) ? "'N'," : "";
		$hasLatePassSortTable = ($haslatepasses) ? ",'N'" : "";

	}
} //END DATA MANIPULATION

//$pagetitle = "Student List";

/******* begin html output ********/
if ($fileToInclude==null || $fileToInclude=="") {

$placeinhead .= "<script type=\"text/javascript\">";
$placeinhead .= 'function chgsecfilter() { ';
$placeinhead .= '       var sec = document.getElementById("secfiltersel").value; ';
$address = $GLOBALS['basesiteurl'] . "/course/listusers.php?cid=$cid";
$placeinhead .= "       var toopen = '$address&secfilter=' + encodeURIComponent(sec);\n";
$placeinhead .= "  	window.location = toopen; \n";
$placeinhead .= "}\n";
$placeinhead .= '$(function() { $(".lal").attr("title","View login log");
	$(".gl").attr("title","View student grades");
	$(".ex").attr("title","Set due date exceptions");
	$(".ui").attr("title","Edit student profile and options");
	$(".ll").attr("title","Lock student out of the course");
	$(".ull").attr("title","Allow student access to the course");
	$("input[type=checkbox]").on("change",function() {$(this).parents("tr").toggleClass("highlight");});
	});';
$placeinhead .= "</script>";
$placeinhead .= '<script type="text/javascript">$(function() {
  var html = \'<span class="dropdown"><a role="button" tabindex=0 class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><img src="../img/gears.png" alt="Options"/></a>\';
  html += \'<ul role="menu" class="dropdown-menu">\';
  $("img[data-uid]").each(function (i,el) {
  	var uid = $(el).attr("data-uid");
	var thishtml = html + \' <li><a href="listusers.php?cid=\'+cid+\'&chgstuinfo=true&uid=\'+uid+\'">'._('Student profile and options').'</a></li>\';
	if ($(el).siblings("span.greystrike").length) {
		thishtml += \' <li><a href="#" onclick="postRosterForm(\'+uid+\',\\\'unlockone\\\');return false;">'._('Unlock').'</a></li>\';
	} else {
		thishtml += \' <li><a href="#" onclick="postRosterForm(\'+uid+\',\\\'lockone\\\');return false;">'._('Lock out of course').'</a></li>\';
	}
	thishtml += \' <li><a href="viewloginlog.php?cid=\'+cid+\'&uid=\'+uid+\'">'._('Login Log').'</a></li>\';
	thishtml += \' <li><a href="viewactionlog.php?cid=\'+cid+\'&uid=\'+uid+\'">'._('Activity Log').'</a></li>\';
	thishtml += \'</ul></span> \';
	$(el).replaceWith(thishtml);
  });
  $(".dropdown-toggle").dropdown();
  });
  function postRosterForm(uid,action) {
  	$("<form>", {method: "POST", action: $("#qform").attr("action")})
	  .append($("<input>", {name:"action", value:action, type:"hidden"}))
	  .append($("<input>", {name:"uid", value:uid, type:"hidden"}))
	  .appendTo("body").submit();
  }
  </script>';

require("../header.php");
$curdir = rtrim(dirname(__FILE__), '/\\');
}
/**** post-html data manipulation ******/
// this page has no post-html data manipulation

/***** page body *****/
/***** php display blocks are interspersed throughout the html as needed ****/
if ($overwriteBody==1) {
	if (strlen($body)<2) {
		include("./$fileToInclude");
	} else {
		echo $body;
	}
} else {
?>
	<div class=breadcrumb><?php echo $curBreadcrumb ?></div>
	<div id="headerlistusers" class="pagetitle"><h2><?php echo $pagetitle ?></h2></div>
<?php

	if (isset($_GET['assigncode'])) {
?>
	<form method=post action="listusers.php?cid=<?php echo $cid ?>&assigncode=1">
		<table class=gb>
			<thead>
			<tr>
				<th>Name</th><th>Section</th><th>Code</th>
			</tr>
			</thead>
			<tbody>
<?php
		//DB while ($line=mysql_fetch_array($resultStudentList, MYSQL_ASSOC)) {
		while ($line=$resultStudentList->fetch(PDO::FETCH_ASSOC)) {
?>
			<tr>
				<td><?php echo Sanitize::encodeStringForDisplay($line['LastName']) . ", " . Sanitize::encodeStringForDisplay($line['FirstName']); ?></td>
				<td><input type=text name="sec[<?php echo Sanitize::onlyInt($line['id']); ?>]" value="<?php echo Sanitize::encodeStringForDisplay($line['section']); ?>"/></td>
				<td><input type=text name="code[<?php echo Sanitize::onlyInt($line['id']); ?>]" value="<?php echo Sanitize::encodeStringForDisplay($line['code']); ?>"/></td>
			</tr>
<?php
		}
?>
			</tbody>
		</table>
		<input type=submit name=submit value="Submit"/>
	</form>
<?php
	} elseif (isset($_GET['enroll']) && ($myrights==100 || (isset($CFG['GEN']['allowinstraddbyusername']) && $CFG['GEN']['allowinstraddbyusername']==true))) {
?>
	<form method=post action="listusers.php?enroll=student&cid=<?php echo $cid ?>">
		<span class=form>Username to enroll:</span>
		<span class=formright><input type="text" name="username"></span><br class=form>
		<span class=form>Section (optional):</span>
		<span class=formright><input type="text" name="section"></span><br class=form>
		<span class=form>Code (optional):</span>
		<span class=formright><input type="text" name="code"></span><br class=form>
		<div class=submit><input type="submit" value="Enroll"></div>
	</form>
<?php
	} elseif (isset($_GET['newstu']) && $CFG['GEN']['allowinstraddstus']) {
?>

	<form method=post id=pageform class=limitaftervalidate action="listusers.php?cid=<?php echo $cid ?>&newstu=new">
		<span class=form><label for="SID"><?php echo $loginprompt;?>:</label></span> <input class=form type=text size=12 id=SID name=SID><BR class=form>
	<span class=form><label for="pw1">Choose a password:</label></span><input class=form type=text size=20 id=pw1 name=pw1><BR class=form>
	<span class=form><label for="firstname">Enter First Name:</label></span> <input class=form type=text size=20 id=firstnam name=firstname><BR class=form>
	<span class=form><label for="lastname">Enter Last Name:</label></span> <input class=form type=text size=20 id=lastname name=lastname><BR class=form>
	<span class=form><label for="email">Enter E-mail address:</label></span>  <input class=form type=text size=60 id=email name=email><BR class=form>
	<span class=form>Section (optional):</span>
		<span class=formright><input type="text" name="section"></span><br class=form>
	<span class=form>Code (optional):</span>
		<span class=formright><input type="text" name="code"></span><br class=form>
	<div class=submit><input type=submit value="Create and Enroll"></div>
	</form>

<?php
		require_once("../includes/newusercommon.php");
		showNewUserValidation("pageform");
	} elseif (isset($_POST['submit']) && $_POST['submit']=="Copy Emails") {
		if (count($_POST['checked'])==0) {
			echo "No student selected. <a href=\"listusers.php?cid=$cid\">Try again</a>";
		} else {
			echo '<textarea id="emails" rows="30" cols="60">'.Sanitize::encodeStringForDisplay($stuemails).'</textarea>';
			echo '<script type="text/javascript">addLoadEvent(function(){var el=document.getElementById("emails");el.focus();el.select();})</script>';
		}

	}elseif (isset($_GET['chgstuinfo'])) {
?>
		<form enctype="multipart/form-data" id=pageform method=post action="listusers.php?cid=<?php echo $cid ?>&chgstuinfo=true&uid=<?php echo Sanitize::onlyInt($_GET['uid']) ?>" class="limitaftervalidate"/>
			<span class=form><label for="SID">Enter User Name (login name):</label></span>
			<input class=form type=text size=20 id=SID name=SID value="<?php echo Sanitize::encodeStringForDisplay($lineStudent['SID']); ?>"/><br class=form>
			<span class=form><label for="firstname">Enter First Name:</label></span>
			<input class=form type=text size=20 id=firstname name=firstname value="<?php echo Sanitize::encodeStringForDisplay($lineStudent['FirstName']); ?>"/><br class=form>
			<span class=form><label for="lastname">Enter Last Name:</label></span>
			<input class=form type=text size=20 id=lastname name=lastname value="<?php echo Sanitize::encodeStringForDisplay($lineStudent['LastName']); ?>"/><BR class=form>
			<span class=form><label for="email">Enter E-mail address:</label></span>
			<input class=form type=text size=60 id=email name=email value="<?php echo Sanitize::encodeStringForDisplay($lineStudent['email']); ?>"/><BR class=form>
			<span class=form><label for="stupic">Picture:</label></span>
			<span class="formright">
			<?php
		if ($lineStudent['hasuserimg']==1) {
			if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
				echo "<img src=\"{$urlmode}{$GLOBALS['AWSbucket']}.s3.amazonaws.com/cfiles/userimg_" . Sanitize::onlyInt($_GET['uid']) . ".jpg\" alt=\"User picture\"/> <input type=\"checkbox\" name=\"removepic\" value=\"1\" /> Remove ";
			} else {
				$curdir = rtrim(dirname(__FILE__), '/\\');
				$galleryPath = "$curdir/course/files/";
				echo "<img src=\"$imasroot/course/files/userimg_" . Sanitize::onlyInt($_GET['uid']) . ".jpg\" alt=\"User picture\"/> <input type=\"checkbox\" name=\"removepic\" value=\"1\" /> Remove ";
			}
		} else {
			echo "No Pic ";
		}
		?>
			<br/><input type="file" name="stupic"/></span><br class="form" />
			<span class=form>Section (optional):</span>
			<span class=formright><input type="text" name="section" value="<?php echo Sanitize::encodeStringForDisplay($lineStudent['section']); ?>"/></span><br class=form>
			<span class=form>Code (optional):</span>
			<span class=formright><input type="text" name="code" value="<?php echo Sanitize::encodeStringForDisplay($lineStudent['code']); ?>"/></span><br class=form>
			<span class=form>Time Limit Multiplier:</span>
			<span class=formright><input type="number" min="0.01" step="0.01" name="timelimitmult" value="<?php echo Sanitize::encodeStringForDisplay($lineStudent['timelimitmult']); ?>"/></span><br class=form>
			<span class=form>LatePasses:</span>
			<span class=formright><input type="number" min="0" name="latepasses" value="<?php echo Sanitize::encodeStringForDisplay($lineStudent['latepass']); ?>"/></span><br class=form>
			<span class=form>Lock out of course?</span>
			<span class=formright><input type="checkbox" name="locked" value="1" <?php if ($lineStudent['locked']>0) {echo ' checked="checked" ';} ?>/></span><br class=form>
			<span class="form">Student has course hidden from course list?</span>
			<span class="formright"><input type="checkbox" name="hidefromcourselist" value="1" <?php if ($lineStudent['hidefromcourselist']>0) {echo ' checked="checked" ';} ?>/></span><br class=form>
			<span class=form><label for="doresetpw">Reset password?</label></span>
			<span class=formright>
				<input type=checkbox name="doresetpw" id="doresetpw" value="1" onclick="$('#newpwwrap').toggle(this.checked)" /> 
				<span id="newpwwrap" style="display:none"><label for="pw1">Set temporary password to:</label> 
				<input type=text size=20 name="pw1" id="pw1" /></span>
			</span><br class=form />
			<div class=submit><input type=submit value="Update Info"></div>
		</form>

<?php
		$requiredrules = array('pw1'=>'{depends: function(element) {return $("#doresetpw").is(":checked")}}');
		require_once("../includes/newusercommon.php");
		showNewUserValidation("pageform", array(), $requiredrules, array('originalSID'=>$lineStudent['SID']));
	} else {
?>

	<script type="text/javascript">
	var picsize = 0;
	function rotatepics() {
		picsize = (picsize+1)%3;
		picshow(picsize);
	}
	function chgpicsize() {
		var size = document.getElementById("picsize").value;
		picshow(size);
	}
	function picshow(size) {
		if (size==0) {
			els = document.getElementById("myTable").getElementsByTagName("img");
			for (var i=0; i<els.length; i++) {
				els[i].style.display = "none";
			}
		} else {
			els = document.getElementById("myTable").getElementsByTagName("img");
			for (var i=0; i<els.length; i++) {
				els[i].style.display = "inline";
				if (els[i].getAttribute("src").match("userimg_sm")) {
					if (size==2) {
						els[i].setAttribute("src",els[i].getAttribute("src").replace("_sm","_"));
					}
				} else if (size==1) {
					els[i].setAttribute("src",els[i].getAttribute("src").replace("_","_sm"));
				}
			}
		}
	}
	function chgrmode() {
		var rmode = 1*$("#showemail").val()+2*$("#showsid").val();
		window.location = "listusers.php?cid="+cid+"&rmode="+rmode;
	}
	</script>
	<script type="text/javascript" src="<?php echo $imasroot ?>/javascript/tablesorter.js"></script>
	<div class="cpmid">
	<?php
	echo '<span class="column" style="width:auto;">';
	echo "<a href=\"logingrid.php?cid=$cid\">View Login Grid</a><br/>";
	echo "<a href=\"listusers.php?cid=$cid&assigncode=1\">Assign Sections and/or Codes</a>";
	echo '</span>';
	echo '<span class="column" style="width:auto;">';
	echo "<a href=\"latepasses.php?cid=$cid\">Manage LatePasses</a>";
	if ($CFG['GEN']['allowinstraddtutors']) {
		echo "<br/><a href=\"managetutors.php?cid=$cid\">Manage Tutors</a>";
	}
	echo '</span>';
	echo '<span class="column" style="width:auto;">';
	if ($myrights==100 || (isset($CFG['GEN']['allowinstraddbyusername']) && $CFG['GEN']['allowinstraddbyusername']==true)) {
		echo "<a href=\"listusers.php?cid=$cid&enroll=student\">Enroll Student with known username</a><br/>";
	}
	echo "<a href=\"enrollfromothercourse.php?cid=$cid\">Enroll students from another course</a>";
	if (isset($CFG['GEN']['allowinstraddstus']) && $CFG['GEN']['allowinstraddstus']==true) {
		echo '</span><span class="column" style="width:auto;">';
		echo "<a href=\"$imasroot/admin/importstu.php?cid=$cid\">Import Students from File</a><br/>";
		echo "<a href=\"listusers.php?cid=$cid&newstu=new\">Create and Enroll new student</a>";
	}
	echo '</span>';
	echo '<br class="clear"/>';
	echo '</div>';
	echo '<p>Pictures: <select id="picsize" onchange="chgpicsize()">';
	echo "<option value=0 selected>", _('None'), "</option>";
	echo "<option value=1>", _('Small'), "</option>";
	echo "<option value=2>", _('Big'), "</option></select> ";
	echo 'Email: <select id="showemail" onchange="chgrmode()">';
	echo '<option value=1 '.($showemail==true?'selected':'').'>'._('Show').'</option>';
	echo '<option value=0 '.($showemail==true?'':'selected').'>'._('Hide').'</option>';
	echo '</select> ';
	echo Sanitize::encodeStringForDisplay($loginprompt).': <select id="showsid" onchange="chgrmode()">';
	echo '<option value=1 '.($showSID==true?'selected':'').'>'._('Show').'</option>';
	echo '<option value=0 '.($showSID==true?'':'selected').'>'._('Hide').'</option>';
	echo '</select> ';
	echo '</p>';
	?>
	<form id="qform" method=post action="listusers.php?cid=<?php echo $cid ?>">
		<p>Check: <a href="#" onclick="return chkAllNone('qform','checked[]',true)">All</a> <a href="#" onclick="return chkAllNone('qform','checked[]',true,'locked')">Non-locked</a> <a href="#" onclick="return chkAllNone('qform','checked[]',false)">None</a>
		With Selected:
		<?php
		  if (!isset($CFG['GEN']['noEmailButton'])) {
		  	  echo '<input type=submit name=submit value="E-mail" title="Send e-mail to the selected students">';
		  }
		?>
		<input type=submit name=submit value="Message" title="Send a message to the selected students">
		<input type=submit name=submit value="Lock" title="Lock selected students out of the course">
		<input type=submit name=submit value="Make Exception" title="Make due date exceptions for selected students">
		<input type=submit name=submit value="Copy Emails" title="Get copyable list of email addresses for selected students">
		<?php
		if (!isset($CFG['GEN']['noInstrUnenroll'])) {
			echo '<input type=submit name=submit value="Unenroll" title="Unenroll the selected students">';
		}
		?>
		</p>

	<table class=gb id=myTable>
		<thead>
		<tr>
			<th></th>
			<th></th>
			<?php echo $hasSectionRowHeader; ?>
			<?php echo $hasCodeRowHeader; ?>
			<th>Name</th>
			<th></th>
			<?php
			if ($showSID) {
				echo '<th>'.Sanitize::encodeStringForDisplay($loginprompt).'</th>';
			}
			if ($showemail) {
				echo '<th>'._('Email').'</th>';
			}
			?>
			<th>Last Access</th>
			<th>Grades</th>
			<?php echo $hasLatePassHeader; ?>
		</tr>
		</thead>
		<tbody>
<?php
		$alt = 0;
		$numstu = 0;  $numunlocked = 0;
		//DB while ($line=mysql_fetch_array($resultDefaultUserList, MYSQL_ASSOC)) {
		foreach ($defaultUserList as $line) {
			if ($line['section']==null) {
				$line['section'] = '';
			}
			$icons = '';
			$numstu++;
			if ($line['locked']>0) {
				$icons .= '<img src="../img/lock.png" alt="Locked" title="Locked"/>';
			} else {
				$numunlocked++;
			}
			if ($line['timelimitmult']!=1) {
				$icons .= '<img src="../img/time.png" alt="'._('Has a time limit multiplier set').'" title="'._('Has a time limit multiplier set').'"/> ';
			}
			if ($icons != '') {
				$icons = '<a href="listusers.php?cid='.$cid.'&chgstuinfo=true&uid='.Sanitize::onlyInt($line['userid']).'">'.$icons.'</a>';
			}

			$lastaccess = ($line['lastaccess']>0) ? tzdate("n/j/y g:ia",$line['lastaccess']) : "never";

			$hasSectionData = ($hassection) ? "<td>{$line['section']}</td>" : "";
			$hasCodeData = ($hascode) ? "<td>{$line['code']}</td>" : "";
			if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
?>
				<td><input type=checkbox name="checked[]" value="<?php echo Sanitize::onlyInt($line['userid']); ?>" <?php if ($line['locked']>0) echo 'class="locked"'?>></td>
				<td>
<?php

	if ($line['hasuserimg']==1) {
		if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
			echo "<img src=\"{$urlmode}{$GLOBALS['AWSbucket']}.s3.amazonaws.com/cfiles/userimg_sm" . Sanitize::onlyInt($line['userid']) . ".jpg\" style=\"display:none;\" alt=\"User picture\" />";
		} else {
			echo "<img src=\"$imasroot/course/files/userimg_sm" . Sanitize::onlyInt($line['userid']) . ".jpg\" style=\"display:none;\" alt=\"User picture\" />";
		}
	}
?>
				</td>
				<?php
				echo $hasSectionData;
				echo Sanitize::outgoingHtml($hasCodeData);
				$nameline = '<a href="listusers.php?cid='.$cid.'&chgstuinfo=true&uid=' . Sanitize::onlyInt($line['userid']) . '" class="ui">';
				$nameline .= Sanitize::encodeStringForDisplay($line['LastName']).', '.Sanitize::encodeStringForDisplay($line['FirstName']) . '</a>';
				echo '<td><img data-uid="'. Sanitize::onlyInt($line['userid']) .'" src="../img/gears.png"/> ';
				if ($line['locked']>0) {
					echo '<span class="greystrike">'.$nameline.'</span></td>';
					echo '<td>'.$icons.'</td>';
					if ($showSID) {
						echo '<td><span class="greystrike">'.Sanitize::encodeStringForDisplay($line['SID']).'</span></td>';
					}
					if ($showemail) {
						echo '<td><span class="greystrike">'.Sanitize::emailAddress($line['email']).'</span></td>';
					}
					echo '<td><span class="greystrike"><a href="viewloginlog.php?cid='.$cid.'&uid='.Sanitize::onlyInt($line['userid']).'" class="lal">'.$lastaccess.'</a></span></td>';
				} else {
					echo $nameline.'</td>';
					echo '<td>'.$icons.'</td>';
					if ($showSID) {
						echo '<td>'.Sanitize::encodeStringForDisplay($line['SID']).'</td>';
					}
					if ($showemail) {
						echo '<td>'.Sanitize::emailAddress($line['email']).'</td>';
					}
					echo '<td><a href="viewloginlog.php?cid='.$cid.'&uid='.Sanitize::onlyInt($line['userid']).'" class="lal">'.$lastaccess.'</a></td>';
				}
				?>

				<td><a href="gradebook.php?cid=<?php echo $cid ?>&stu=<?php echo Sanitize::onlyInt($line['userid']); ?>&from=listusers" class="gl">Grades</a></td>
				<?php
				if ($haslatepasses) {
					echo '<td>'.Sanitize::onlyInt($line['latepass']).'</td>';
				}
				?>
			</tr>
<?php
		}
?>

			</tbody>
		</table>
<?php
		echo "<p>Number of students: <b>$numunlocked</b>";
		if ($numstu != $numunlocked) {
			echo " ($numstu including locked students)";
		}
		echo '</p>';
?>
		<script type="text/javascript">
			initSortTable('myTable',Array(false,false,<?php echo $hasSectionSortTable ?><?php echo $hasCodeSortTable ?>'S',false,'D',false<?php echo $hasLatePassSortTable ?>),true);
		</script>
	</form>



	<p></p>
<?php
	}
}

require("../footer.php");
?>

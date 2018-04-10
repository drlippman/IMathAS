<?php
//IMathAS:  enroll students based on roster in another class
//(c) 2009 David Lippman

/*** master php includes *******/
require("../init.php");


/*** pre-html data manipulation, including function code *******/
$cid = Sanitize::courseId($_GET['cid']);
$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\"> ".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; <a href=\"listusers.php?cid=$cid\">List Students</a>\n";

if (!isset($teacherid)) { // loaded by a NON-teacher
	$overwriteBody=1;
	$body = "You need to log in as a teacher to access this page";
} else { // PERMISSIONS ARE OK, PROCEED WITH PROCESSING
	if (isset($_POST['process'])) {
		//get deflatepass
		$query = "SELECT deflatepass FROM imas_courses WHERE id=:cid";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':cid'=>$cid));
		$deflatepass = $stm->fetchColumn(0);

		//know students.  Do work
		$todo = array();
		foreach ($_POST['checked'] as $stu) {
			$stu = intval($stu);
			if ($stu>0) {
				$todo[] = $stu;
			}
		}
		if (count($todo)>0) {
			$todolist = implode(',', $todo);
			$dontdo = array();
			//DB $query = "SELECT userid FROM imas_students WHERE courseid='$cid' AND userid IN ($todolist)";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB while ($row = mysql_fetch_row($result)) {
			$stm = $DBH->prepare("SELECT userid FROM imas_students WHERE courseid=:courseid AND userid IN ($todolist)");
			$stm->execute(array(':courseid'=>$cid));
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$dontdo[] = $row[0];
			}
			$vals = array();
			$qarr = array();
			$_POST['section'] = trim($_POST['section']);
			foreach ($todo as $stu) {
				if (in_array($stu,$dontdo)) {continue;}
				//DB $vals[] = "($stu,'$cid'$section)";
				$vals[] = "(?,?,?,?)";
				array_push($qarr, $stu, $cid, ($_POST['section']!='')?$_POST['section']:null, $deflatepass);
			}
			if (count($vals)>0) {
				//DB $query = 'INSERT INTO imas_students (userid,courseid';
				//DB if (trim($_POST['section'])!='') {
				//DB 	$query .= ',section';
				//DB }
				//DB $query .= ') VALUES '.implode(',',$vals);
				//DB mysql_query($query) or die("Query failed : " . mysql_error());
				$stm = $DBH->prepare('INSERT INTO imas_students (userid,courseid,section,latepass) VALUES '.implode(',', $vals));
				$stm->execute($qarr);
			}
		}
		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/listusers.php?cid=$cid" . "&r=" . Sanitize::randomQueryStringParam());
		exit;

	} else if (isset($_POST['sourcecourse'])) {
		//know source course
		$source = intval($_POST['sourcecourse']);
		//DB $query = "SELECT iu.FirstName,iu.LastName,iu.id FROM imas_users AS iu JOIN imas_students ON iu.id=imas_students.userid WHERE imas_students.courseid='$source' ORDER BY iu.LastName,iu.FirstName";
		//DB $resultStudentList = mysql_query($query) or die("Query failed : " . mysql_error());
		$resultStudentList = $DBH->prepare("SELECT iu.FirstName,iu.LastName,iu.id FROM imas_users AS iu JOIN imas_students ON iu.id=imas_students.userid WHERE imas_students.courseid=:courseid ORDER BY iu.LastName,iu.FirstName");
		$resultStudentList->execute(array(':courseid'=>$source));

	} else {
		//DB $query = "SELECT ic.id,ic.name FROM imas_courses AS ic JOIN imas_teachers ON imas_teachers.courseid=ic.id WHERE imas_teachers.userid='$userid' ORDER BY ic.name";
		//DB $resultCourseList = mysql_query($query) or die("Query failed : " . mysql_error());
		$resultCourseList = $DBH->prepare("SELECT ic.id,ic.name FROM imas_courses AS ic JOIN imas_teachers ON imas_teachers.courseid=ic.id WHERE imas_teachers.userid=:userid ORDER BY ic.name");
		$resultCourseList->execute(array(':userid'=>$userid));
	}

} //END DATA MANIPULATION

/******* begin html output ********/

$pagetitle = "Enroll Students From Another Course";
require("../header.php");
$curBreadcrumb .= '&gt; Enroll From Another Course';

/***** page body *****/
if ($overwriteBody==1) {
	if (strlen($body)<2) {
		include("./$fileToInclude");
	} else {
		echo $body;
	}
} else {
	?>
	<div class=breadcrumb><?php echo $curBreadcrumb ?></div>
	<div id="headerenrollfromothercourse" class="pagetitle"><h2><?php echo $pagetitle ?></h2></div>
	<form id="qform" method="post" action="enrollfromothercourse.php?cid=<?php echo $cid ?>">
	<?php
	if (isset($resultCourseList)) {
		echo '<p>Select a course to choose students from:</p><p>';
		$cnt = 0;
		//DB while ($line=mysql_fetch_array($resultCourseList, MYSQL_ASSOC)) {
		while ($line=$resultCourseList->fetch(PDO::FETCH_ASSOC)) {
			echo '<input type="radio" name="sourcecourse" value="' . Sanitize::encodeStringForDisplay($line['id']) . '" ';
			if ($cnt==0) {echo 'checked="checked"';}
			echo '/> ' . Sanitize::encodeStringForDisplay($line['name']) . '<br/>';
			$cnt++;
		}
		echo '<input type="submit" value="Choose Students" />';
		echo '</form>';
	} else {
		echo '<input type="hidden" name="process" value="true" />';
		echo '<p>Select students to enroll:</p><p>';
		echo 'Check: <a href="#" onclick="return chkAllNone(\'qform\',\'checked[]\',true)">All</a> <a href="#" onclick="return chkAllNone(\'qform\',\'checked[]\',false)">None</a>';
		echo '<p>';
		//DB while ($line=mysql_fetch_array($resultStudentList, MYSQL_ASSOC)) {
		while ($line=$resultStudentList->fetch(PDO::FETCH_ASSOC)) {
			echo '<input type=checkbox name="checked[]" value="' . Sanitize::encodeStringForDisplay($line['id']) . '"/>';
			printf('%s, %s<br/>', Sanitize::encodeStringForDisplay($line['LastName']),
                Sanitize::encodeStringForDisplay($line['FirstName']));
		}
		echo '</p><p>Assign to section: <input type="text" name="section" />  (optional)</p>';
		echo '</p><p><input type="submit" value="Enroll These Students" /></p>';
		echo '</form>';
	}

}

require("../footer.php");
?>

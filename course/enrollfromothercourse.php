<?php
//IMathAS:  enroll students based on roster in another class
//(c) 2009 David Lippman

/*** master php includes *******/
require("../validate.php");

/*** pre-html data manipulation, including function code *******/
$cid = $_GET['cid'];
$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\"> $coursename</a> &gt; <a href=\"listusers.php?cid=$cid\">List Students</a>\n";

if (!isset($teacherid)) { // loaded by a NON-teacher
	$overwriteBody=1;
	$body = "You need to log in as a teacher to access this page";
} else { // PERMISSIONS ARE OK, PROCEED WITH PROCESSING
	if (isset($_POST['process'])) {
		//know students.  Do work
		$todo = array();
		foreach ($_POST['checked'] as $stu) {
			$stu = intval($stu);
			if ($stu>0) {
				$todo[] = $stu;
			}
		}
		$todolist = implode(',',$todo);
		$query = "SELECT userid FROM imas_students WHERE courseid='$cid' AND userid IN ($todolist)";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$dontdo = array();
		while ($row = mysql_fetch_row($result)) {
			$dontdo[] = $row[0];
		}
		$vals = array();
		if (trim($_POST['section'])!='') {
			$section = ",'".trim($_POST['section'])."'";
		} else {
			$section = '';
		}
		foreach ($todo as $stu) {
			if (in_array($stu,$dontdo)) {continue;}
			$vals[] = "($stu,'$cid'$section)";
		}
		if (count($vals)>0) {
			$query = 'INSERT INTO imas_students (userid,courseid';
			if (trim($_POST['section'])!='') {
				$query .= ',section';
			}
			$query .= ') VALUES '.implode(',',$vals);
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
		header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/listusers.php?cid=$cid");
		exit;
	
	} else if (isset($_POST['sourcecourse'])) {
		//know source course
		$source = intval($_POST['sourcecourse']);
		$query = "SELECT iu.FirstName,iu.LastName,iu.id FROM imas_users AS iu JOIN imas_students ON iu.id=imas_students.userid WHERE imas_students.courseid='$source' ORDER BY iu.LastName,iu.FirstName";
		$resultStudentList = mysql_query($query) or die("Query failed : " . mysql_error());
		
	} else {
		$query = "SELECT ic.id,ic.name FROM imas_courses AS ic JOIN imas_teachers ON imas_teachers.courseid=ic.id WHERE imas_teachers.userid='$userid' ORDER BY ic.name";
		$resultCourseList = mysql_query($query) or die("Query failed : " . mysql_error());
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
		while ($line=mysql_fetch_array($resultCourseList, MYSQL_ASSOC)) {
			echo '<input type="radio" name="sourcecourse" value="'.$line['id'].'" ';
			if ($cnt==0) {echo 'checked="checked"';}
			echo '/> '.$line['name'].'<br/>';
			$cnt++;
		}
		echo '<input type="submit" value="Choose Students" />';
		echo '</form>';
	} else {
		echo '<input type="hidden" name="process" value="true" />';
		echo '<p>Select students to enroll:</p><p>';
		echo 'Check: <a href="#" onclick="return chkAllNone(\'qform\',\'checked[]\',true)">All</a> <a href="#" onclick="return chkAllNone(\'qform\',\'checked[]\',false)">None</a>';
		echo '<p>';
		while ($line=mysql_fetch_array($resultStudentList, MYSQL_ASSOC)) {
			echo '<input type=checkbox name="checked[]" value="'.$line['id'].'"/>';
			echo $line['LastName'].', '.$line['FirstName'].'<br/>';
		}
		echo '</p><p>Assign to section: <input type="text" name="section" />  (optional)</p>';
		echo '</p><p><input type="submit" value="Enroll These Students" /></p>';
		echo '</form>';	
	}
	
}
	
require("../footer.php");	
?>

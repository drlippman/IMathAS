<?php
//IMathAS:  enroll students based on roster in another class
//(c) 2009 David Lippman

/*** master php includes *******/
require_once "../init.php";


/*** pre-html data manipulation, including function code *******/
$cid = Sanitize::courseId($_GET['cid']);
$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\"> ".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; <a href=\"listusers.php?cid=$cid\">" . _('List Students') . "</a>\n";

if (!isset($teacherid)) { // loaded by a NON-teacher
	$overwriteBody=1;
	$body = _('You need to log in as a teacher to access this page');
} else { // PERMISSIONS ARE OK, PROCEED WITH PROCESSING
	if (isset($_POST['process'])) {
		//get deflatepass
		$query = "SELECT deflatepass FROM imas_courses WHERE id=:cid";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':cid'=>$cid));
		$deflatepass = $stm->fetchColumn(0);

		//know students.  Do work
		$todo = array();
		if (!empty($_POST['checked'])) {
			foreach ($_POST['checked'] as $stu) {
				$stu = intval($stu);
				if ($stu>0) {
					$todo[] = $stu;
				}
			}
		}
		$_POST['section'] = trim($_POST['section'] ?? '');
		$section = $_POST['section'];

		//know tutors
		$todotutors = array();
		if (!empty($_POST['checkedtutors']) && !(isset($CFG['GEN']['allowinstraddtutors']) && $CFG['GEN']['allowinstraddtutors']==false)) {
			foreach ($_POST['checkedtutors'] as $tut) {
				$tut = intval($tut);
				if ($tut>0) {
					$todotutors[] = $tut;
				}
			}
		}
		if (count($todotutors)>0) {
			// check tutors are actually tutors in one of teacher's courses
			$tutlist = implode(',', $todotutors);
			$stm = $DBH->prepare("SELECT DISTINCT itu.userid FROM imas_tutors AS itu JOIN
				imas_teachers AS it ON itu.courseid=it.courseid WHERE
				it.userid=? AND itu.userid IN ($tutlist)");
			$stm->execute([$userid]);
			$todotutors = [];
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$todotutors[] = intval($row[0]);
			}
			if (count($todotutors)>0) {
				// skip anyone already a student, teacher, or tutor in this course
				$tutlist = implode(',', $todotutors);
				$dontdotut = array();
				foreach (['imas_students','imas_teachers','imas_tutors'] as $tbl) {
					$stm = $DBH->prepare("SELECT userid FROM $tbl WHERE courseid=? AND userid IN ($tutlist)");
					$stm->execute([$cid]);
					while ($row = $stm->fetch(PDO::FETCH_NUM)) {
						$dontdotut[] = intval($row[0]);
					}
				}
				$todotutors = array_values(array_diff($todotutors, $dontdotut));
			}
			if (count($todotutors)>0) {
				$tvals = array();
				$tqarr = array();
				foreach ($todotutors as $tut) {
					$tvals[] = "(?,?,?)";
					array_push($tqarr, $tut, $cid, $section);
				}
				$stm = $DBH->prepare('INSERT INTO imas_tutors (userid,courseid,section) VALUES '.implode(',', $tvals));
				$stm->execute($tqarr);
				require_once '../includes/TeacherAuditLog.php';
				TeacherAuditLog::addTracking(
					$cid,
					"Roster Action",
					null,
					array(
						'action' => 'Add Tutors',
						'IDs' => $todotutors
					)
				);
			}
		}
		if (count($todo)>0) {
			// check students are actually in teacher's course
			$todolist = implode(',', $todo);
			$stm = $DBH->prepare("SELECT DISTINCT istu.userid FROM imas_students AS istu JOIN 
				imas_teachers AS it ON istu.courseid=it.courseid WHERE
				it.userid=? AND istu.userid IN ($todolist)");
			$stm->execute([$userid]);
			$todo = [];
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$todo[] = intval($row[0]);
			}
			if (count($todo)==0 && count($todotutors)==0) {
				echo _('No valid students');
				exit;
			}
			//check they're not already enrolled, or a teacher/tutor here
			$todolist = implode(',', $todo);
			$dontdo = array();
			if (count($todo)>0) {
				foreach (['imas_students','imas_teachers','imas_tutors'] as $tbl) {
					$stm = $DBH->prepare("SELECT userid FROM $tbl WHERE courseid=:courseid AND userid IN ($todolist)");
					$stm->execute(array(':courseid'=>$cid));
					while ($row = $stm->fetch(PDO::FETCH_NUM)) {
						$dontdo[] = $row[0];
					}
				}
			}
			$vals = array();
			$qarr = array();
            require_once '../includes/setSectionGroups.php';
			foreach ($todo as $stu) {
				if (in_array($stu,$dontdo)) {continue;}
				$vals[] = "(?,?,?,?)";
                array_push($qarr, $stu, $cid, ($_POST['section']!='')?$_POST['section']:null, $deflatepass);
                setSectionGroups($stu, $cid, $_POST['section']);
			}
			if (count($vals)>0) {
				$stm = $DBH->prepare('INSERT INTO imas_students (userid,courseid,section,latepass) VALUES '.implode(',', $vals));
				$stm->execute($qarr);
			}
		}
		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/listusers.php?cid=$cid" . "&r=" . Sanitize::randomQueryStringParam());
		exit;

	} else if (isset($_POST['sourcecourse'])) {
		//know source course
		$source = intval($_POST['sourcecourse']);
		$stm = $DBH->prepare("SELECT id FROM imas_teachers WHERE courseid=? AND userid=?");
		$stm->execute([$source, $userid]);
		$check = $stm->fetchColumn(0);
		if ($check === false) {
			echo _('Error - invalid course');
			exit;
		}
		$resultStudentList = $DBH->prepare("SELECT iu.FirstName,iu.LastName,iu.id FROM imas_users AS iu JOIN imas_students ON iu.id=imas_students.userid WHERE imas_students.courseid=:courseid ORDER BY iu.LastName,iu.FirstName");
		$resultStudentList->execute(array(':courseid'=>$source));
		$resultTutorList = null;
		if (!(isset($CFG['GEN']['allowinstraddtutors']) && $CFG['GEN']['allowinstraddtutors']==false)) {
			$resultTutorList = $DBH->prepare("SELECT iu.FirstName,iu.LastName,iu.id FROM imas_users AS iu JOIN imas_tutors ON iu.id=imas_tutors.userid WHERE imas_tutors.courseid=:courseid ORDER BY iu.LastName,iu.FirstName");
			$resultTutorList->execute(array(':courseid'=>$source));
		}

	} else {
		$resultCourseList = $DBH->prepare("SELECT ic.id,ic.name FROM imas_courses AS ic JOIN imas_teachers ON imas_teachers.courseid=ic.id WHERE imas_teachers.userid=:userid ORDER BY ic.name");
		$resultCourseList->execute(array(':userid'=>$userid));
	}

} //END DATA MANIPULATION

/******* begin html output ********/

$pagetitle = _('Enroll Students From Another Course');
require_once "../header.php";
$curBreadcrumb .= '&gt; ' . _('Enroll From Another Course');

/***** page body *****/
if (!empty($overwriteBody)) {
	if (strlen($body)<2) {
		require_once "./$fileToInclude";
	} else {
		echo $body;
	}
} else {
	?>
	<div class=breadcrumb><?php echo $curBreadcrumb ?></div>
	<div id="headerenrollfromothercourse" class="pagetitle"><h1><?php echo $pagetitle ?></h1></div>
	<form id="qform" method="post" action="enrollfromothercourse.php?cid=<?php echo $cid ?>">
	<?php
	if (isset($resultCourseList)) {
		echo '<p>' . _('Select a course to choose students from:') . '</p><p>';
		$cnt = 0;
		while ($line=$resultCourseList->fetch(PDO::FETCH_ASSOC)) {
			echo '<label><input type="radio" name="sourcecourse" value="' . Sanitize::encodeStringForDisplay($line['id']) . '" ';
			if ($cnt==0) {echo 'checked="checked"';}
			echo '/> ' . Sanitize::encodeStringForDisplay($line['name']) . '</label><br/>';
			$cnt++;
		}
		echo '<input type="submit" value="' . _('Choose Students') . '" />';
		echo '</form>';
	} else {
		echo '<input type="hidden" name="process" value="true" />';
		echo '<p>' . _('Select students to enroll:') . '</p><p>';
		echo _('Check:') . ' <a href="#" onclick="return chkAllNone(\'qform\',\'checked[]\',true)">' . _('All') . '</a> <a href="#" onclick="return chkAllNone(\'qform\',\'checked[]\',false)">' . _('None') . '</a>';
		echo '<p>';
		while ($line=$resultStudentList->fetch(PDO::FETCH_ASSOC)) {
			echo '<label><input type=checkbox name="checked[]" value="' . Sanitize::encodeStringForDisplay($line['id']) . '"/>';
			printf('<span class="pii-full-name">%s, %s</span></label><br/>', Sanitize::encodeStringForDisplay($line['LastName']),
                Sanitize::encodeStringForDisplay($line['FirstName']));
		}
		echo '</p>';
		if ($resultTutorList !== null) {
			$tutors = $resultTutorList->fetchAll(PDO::FETCH_ASSOC);
			if (count($tutors)>0) {
				echo '<p>' . _('Select tutors to add:') . '</p><p>';
				echo _('Check:') . ' <a href="#" onclick="return chkAllNone(\'qform\',\'checkedtutors[]\',true)">' . _('All') . '</a> <a href="#" onclick="return chkAllNone(\'qform\',\'checkedtutors[]\',false)">' . _('None') . '</a>';
				echo '<p>';
				foreach ($tutors as $line) {
					echo '<label><input type=checkbox name="checkedtutors[]" value="' . Sanitize::encodeStringForDisplay($line['id']) . '"/>';
					printf('<span class="pii-full-name">%s, %s</span></label><br/>', Sanitize::encodeStringForDisplay($line['LastName']),
						Sanitize::encodeStringForDisplay($line['FirstName']));
				}
				echo '</p>';
			}
		}
		echo '<p><label>' . _('Assign to section:') . ' <input type="text" name="section" />  ' . _('(optional)') . '</label></p>';
		echo '<p><input type="submit" value="' . _('Enroll Selected Users') . '" /></p>';
		echo '</form>';
	}

}

require_once "../footer.php";
?>

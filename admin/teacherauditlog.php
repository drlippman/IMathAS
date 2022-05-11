<?php
//IMathAS:  View Teacher Audit Log
//contributed by Lumen Learning

/*** master php includes *******/
require("../init.php");
require("../includes/htmlutil.php");
require_once("../includes/TeacherAuditLog.php");

/*** pre-html data manipulation, including function code *******/

//set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = "Teacher Audit Log";
$userid = Sanitize::onlyInt($_GET['userid']);
$cid = Sanitize::courseId($_GET['cid']);

$curBreadcrumb = "$breadcrumbbase <a href=\"admin2.php\">Admin</a> &gt; <a href=\"userdetails.php?id=$userid\">User Details</a> ";
$curBreadcrumb .= "&gt; Teacher Audit Log\n";

if (isset($_GET['id'])) {
	$stm = $DBH->prepare("SELECT courseid FROM imas_assessments WHERE id=?");
	$stm->execute(array(intval($_GET['id'])));
	if ($stm->rowCount()==0 || $stm->fetchColumn(0) != $_GET['cid']) {
		echo "Invalid ID";
		exit;
	}
}

if ($myrights <75) {
	$overwriteBody=1;
	$body = "You need to log in as an admin to access this page";
} elseif (!(isset($_GET['cid']))) {
	$overwriteBody=1;
	$body = "You need to select the course";
}
function formatdate($date) {
    return tzdate("M j, Y, g:i a", $date);
}


//BEGIN DISPLAY BLOCK

 /******* begin html output ********/
//$placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/DatePicker.js?v=080818\"></script>";

require("../header.php");

if ($overwriteBody==1) {
    echo $body;
} else {
    $stm = $DBH->prepare("SELECT ic.name,ic.ownerid,iu.groupid FROM imas_courses AS ic JOIN imas_users AS iu ON ic.ownerid=iu.id WHERE ic.id=?");
    $stm->execute(array($cid));
    list($coursename, $courseownerid, $coursegroupid) = $stm->fetch(PDO::FETCH_NUM);

    echo '<div class=breadcrumb>', $curBreadcrumb, '</div>';
    echo '<div id="headeruserdetail" class="pagetitle"><h1>' . _('Teacher Audit Log') . ': ';
    echo Sanitize::encodeStringForDisplay($coursename);
    echo '</h1></div>';

		$query = '(SELECT iu.id,iu.FirstName,iu.LastName FROM imas_users AS iu ';
		$query .= 'JOIN imas_teachers AS it ON it.userid=iu.id WHERE it.courseid=?) UNION ';
        $query .= '(SELECT iu.id,iu.FirstName,iu.LastName FROM imas_users AS iu ';
		$query .= 'JOIN imas_tutors AS it ON it.userid=iu.id WHERE it.courseid=?)';
		$stm = $DBH->prepare($query);
		$stm->execute(array($cid, $cid));
		$teacherNames = array();
		while($row = $stm->fetch(PDO::FETCH_ASSOC)) {
			$teacherNames[$row['id']] = $row['LastName'].', '.$row['FirstName'];
		}

    $teacher_actions = TeacherAuditLog::findActionsByCourse($cid);
    if (empty($teacher_actions)) {
        echo "<p>Nothing to report</p>";
    } else {
        echo '<table><tr>';
        echo '<th>Date/Time</th>';
        echo '<th>Teacher</th>';
        echo '<th>Action</th>';
        echo '<th>ItemID</th>';
        echo '<th>Details</th>';
        echo '</tr>';

        foreach ($teacher_actions as $action) {
            echo '<tr>';
            echo '<td>' . formatdate($action['created_at']) . '</td>';
            echo "<td><span class='pii-full-name'>";
						echo Sanitize::encodeStringForDisplay($teacherNames[$action['userid']]);
						echo " (" . Sanitize::onlyInt($action['userid']) . ')</span></td>';
            echo '<td>' . Sanitize::encodeStringForDisplay($action['action']) . '</td>';
            echo '<td>' . Sanitize::onlyInt($action['itemid']) . '</td>';
            echo '<td><a href="javascript:alert(\'' . Sanitize::encodeStringForDisplay($action['metadata']) . '\')">Details</a></td>';
            echo '</tr>';
        }
    }
}

require("../footer.php");

<?php
//IMathAS:  View Teacher Audit Log
//contributed by Lumen Learning

/*** master php includes *******/
require("../init.php");
require("../includes/htmlutil.php");
require_once("../includes/TeacherAuditLog.php");

/*** pre-html data manipulation, including function code *******/

$assessnames = [];
function loadAssess() {
    global $assessnames,$DBH,$cid;
    $stm = $DBH->prepare('SELECT id,name FROM imas_assessments WHERE courseid=?');
    $stm->execute([$cid]);
    while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
        $assessnames[$row['id']] = $row['name'];
    }
}
$forumnames = [];
function loadForum() {
    global $assessnames,$DBH,$cid;
    $stm = $DBH->prepare('SELECT id,name FROM imas_forums WHERE courseid=?');
    $stm->execute([$cid]);
    while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
        $forumnames[$row['id']] = $row['name'];
    }
}
$exttoolnames = [];
function loadExttool() {
    global $assessnames,$DBH,$cid;
    $stm = $DBH->prepare('SELECT id,title FROM imas_linkedtext WHERE courseid=?');
    $stm->execute([$cid]);
    while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
        $exttoolnames[$row['id']] = $row['title'];
    }
}
$offlinenames = [];
function loadOffline() {
    global $assessnames,$DBH,$cid;
    $stm = $DBH->prepare('SELECT id,name FROM imas_gbitems WHERE courseid=?');
    $stm->execute([$cid]);
    while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
        $offlinenames[$row['id']] = $row['name'];
    }
}

$stunames = [];
function getAllNames($allactions) {
    global $stunames, $offlinenames, $DBH;
    $uids = [];
    foreach ($allactions as $action) {
        if ($action['action'] == 'Delete Item' || $action['action'] == 'Clear Attempts') {
            $data = json_decode($action['metadata'], true);
            if (isset($data['grades'])) {
                if (isset($data['type']) && $data['type']=='Delete Offline') { // these are grades=>itemid=>uid=>grade
                    foreach ($data['grades'] as $itemid=>$grades) {
                        $uids = array_merge($uids, array_keys($grades));
                    }
                    foreach ($data['items'] as $itemid=>$data) {
                        $offlinenames[$itemid] = $data['name'];
                    }
                } else { // others are grades=>uid=>grade
                    $uids = array_merge($uids, array_keys($data['grades']));
                }
            }
        } else if ($action['action'] == 'Unenroll') {
            $data = json_decode($action['metadata'], true);
            $uids = array_merge($uids, explode(',', $data['unenrolled']));
        }
    }
    $uids = array_values(array_unique($uids));
    if (count($uids)>0) {
        $ph = Sanitize::generateQueryPlaceholders($uids);
        $stm = $DBH->prepare("SELECT id,FirstName,LastName FROM imas_users WHERE id IN ($ph)");
        $stm->execute($uids);
        while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
            $stunames[$row['id']] = $row['LastName'] . ', ' . $row['FirstName'];
        }
    }
}
function mapMetadata($action) {
    global $stunames, $assessnames, $forumnames, $exttoolnames, $offlinenames;
    if ($action['action'] == 'Delete Item' || $action['action'] == 'Clear Attempts') {
        $data = json_decode($action['metadata'], true);
        if (isset($data['grades'])) {
            $newgrades = [];
            if (isset($data['type']) && $data['type']=='Delete Offline') {
                foreach ($data['grades'] as $itemid=>$grades) {
                    foreach ($grades as $uid=>$grade) {
                        $newgrades[$offlinenames[$itemid] ?? $itemid][$stunames[$uid] ?? $uid] = $grade;
                    }
                }
            } else {
                foreach ($data['grades'] as $uid=>$grade) {
                    $newgrades[$stunames[$uid] ?? $uid] = $grade;
                }
            }
            $data['grades'] = $newgrades;
        }
        $action['metadata'] = json_encode($data);
    } else if ($action['action'] == 'Unenroll') {
        $data = json_decode($action['metadata'], true);
        $data['unenrolled'] = explode(',',$data['unenrolled']);
        foreach ($data['unenrolled'] as $k=>$v) {
            $data['unenrolled'][$k] = $stunames[$v];
        }
        $newgrades = [];
        foreach ($data['grades'] as $uid=>$grades) {
            $newgrades[$stunames[$uid] ?? $uid] = [];
            if (isset($grades['assessment'])) {
                if (count($assessnames)==0) { loadAssess(); }
                foreach ($grades['assessment'] as $aid=>$grade) {
                    $newgrades[$stunames[$uid] ?? $uid]['assessment'][$assessnames[$aid] ?? $aid] = $grade;
                }
            }
            if (isset($grades['offline'])) {
                if (count($offlinenames)==0) { loadOffline(); }
                foreach ($grades['offline'] as $aid=>$grade) {
                    $newgrades[$stunames[$uid] ?? $uid]['offline'][$offlinenames[$aid] ?? $aid] = $grade;
                }
            }
            if (isset($grades['forum'])) {
                if (count($forumnames)==0) { loadForum(); }
                foreach ($grades['forum'] as $aid=>$grade) {
                    $newgrades[$stunames[$uid] ?? $uid]['forum'][$forumnames[$aid] ?? $aid] = $grade;
                }
            }
            if (isset($grades['exttool'])) {
                if (count($exttoolnames)==0) { loadExttool(); }
                foreach ($grades['exttool'] as $aid=>$grade) {
                    $newgrades[$stunames[$uid] ?? $uid]['exttool'][$exttoolnames[$aid] ?? $aid] = $grade;
                }
            }
        }
        $data['grades'] = $newgrades;
        $action['metadata'] = json_encode($data);
    }
    return $action;
}

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

    echo '<div class=breadcrumb>', $curBreadcrumb, '</div>';
    echo '<div id="headeruserdetail" class="pagetitle"><h1>' . _('Teacher Audit Log') . ': ';
    echo Sanitize::encodeStringForDisplay($coursename);
    echo '</h1></div>';

    $teacher_actions = TeacherAuditLog::findActionsByCourse($cid);
    if (empty($teacher_actions)) {
        echo "<p>Nothing to report</p>";
    } else {
        getAllNames($teacher_actions);
        echo '<table><tr>';
        echo '<th>Date/Time</th>';
        echo '<th>Teacher</th>';
        echo '<th>Action</th>';
        echo '<th>ItemID</th>';
        echo '<th>Details</th>';
        echo '</tr>';

        foreach ($teacher_actions as $action) {
            $action = mapMetadata($action);
            echo '<tr>';
            echo '<td>' . formatdate($action['created_at']) . '</td>';
            echo "<td><span class='pii-full-name'>";
						echo Sanitize::encodeStringForDisplay($teacherNames[$action['userid']]);
						echo " (" . Sanitize::onlyInt($action['userid']) . ')</span></td>';
            echo '<td>' . Sanitize::encodeStringForDisplay($action['action']) . '</td>';
            echo '<td>' . Sanitize::onlyInt($action['itemid']) . '</td>';
            echo '<td><a href="#" onclick="GB_show(\'Details\',\'<span>' . Sanitize::encodeStringForDisplay(str_replace(',',', ',$action['metadata'])) . '</span>\'); return false;">Details</a></td>';
            echo '</tr>'."\n";
        }
    }
}

require("../footer.php");

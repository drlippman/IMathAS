<?php
require("../init.php");

if (!isset($_GET['type'])) {
	$type = 'take';
} else {
	$type = $_GET['type'];
}
if ($type=='teach') {
	$typename = "Teaching";
	$table = 'imas_teachers';
} else if ($type=='tutor') {
	$typename = "Tutoring";
	$table = 'imas_tutors';
} else {
	$typename = "Taking";
	$table = 'imas_students';
	$type = 'take';
}
$actionuserid = $userid;
$userIdInt = Sanitize::onlyInt($_GET['user'] ?? 0);
 if ($myrights==100 && !empty($userIdInt)) {
	$actionuserid = $userIdInt;
} else if ($myrights>=75 && !empty($userIdInt)) {
	$stm = $DBH->prepare("SELECT groupid FROM imas_users WHERE id=:id");
	$stm->execute(array(':id'=>$userIdInt));
	if ($groupid==$stm->fetchColumn(0)) {
		$actionuserid = $userIdInt;
	}
}
if (isset($_GET['tohide'])) {
    $tohide = Sanitize::courseId($_GET['tohide']);
	if ($tohide>0) {
		$stm = $DBH->prepare("UPDATE $table SET hidefromcourselist=0 WHERE courseid=:courseid AND userid=:userid");
		$stm->execute(array(':courseid'=>$tohide, ':userid'=>$actionuserid));
		if (isset($_GET['ajax'])) {
			if ($stm->rowCount()>0) {
				echo "OK";
			} else {
				echo "ERROR";
			}
			exit;
		}
	}
}
if (!empty($_GET['toundel'])) {
    $stm = $DBH->prepare('UPDATE imas_courses SET available=0 WHERE id=? AND ownerid=?');
    $stm->execute([$_GET['toundel'], $actionuserid]);
    if (isset($_GET['ajax'])) {
        if ($stm->rowCount()>0) {
            echo "OK";
        } else {
            echo "ERROR";
        }
        exit;
    }
}

$pagetitle = "View Hidden Courses You're $typename from Course List";
$curBreadcrumb = "$breadcrumbbase Unhide Courses\n";
require("../header.php");

echo '<div class=breadcrumb>'.$curBreadcrumb.'</div>';
echo '<h1>View Hidden Courses You\'re '.$typename.'</h1>';
$query = 'SELECT ic.name,ic.id,ic.ownerid,ic.available,ic.cleanupdate FROM imas_courses AS ic JOIN '.$table.' AS istu ON ic.id=istu.courseid ';
$query .= "WHERE istu.userid=:userid AND ((istu.hidefromcourselist=1 ";
if ($type=='take') {
	$query .= "AND ic.available=0) ";
} else {
    $query .= "AND ic.available<4) ";
    if ($type == 'teach' && $myrights > 20) {
        $query .= 'OR (ic.available=4 AND ic.ownerid=:ownerid)';
    }
}
$query .= ") ORDER BY ic.name";
$stm = $DBH->prepare($query);
if ($type == 'teach' && $myrights > 20) {
    $stm->execute(array(':userid'=>$userid, ':ownerid'=>$userid));
} else {
    $stm->execute(array(':userid'=>$userid));
}
$hascleanup = false;
$deleted = [];
echo '<ul class="nomark courselist">';
if ($stm->rowCount()==0) {
	echo '<li>No hidden courses</li>';
} else {
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
        if ($row['available'] == 4) {
            $deleted[] = $row;
            continue;
        }
		echo '<li>';

		if ($type=='teach') {
			echo ' <span class="dropdown"><a role="button" tabindex=0 class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
			echo '<img src="'.$staticroot.'/img/gears.png" alt="Options" class="mida"/></a>';
			echo '<ul role="menu" class="dropdown-menu">';
			echo ' <li><a href="unhidefromcourselist.php?type='.Sanitize::encodeUrlParam($type).'&tohide='.$row['id'].'">'._('Un-hide from course list').'</a></li>';
			if ($row['ownerid']==$userid && $myrights>20) {
				echo ' <li><a href="forms.php?from=home&action=modify&id='.$row['id'].'">'._('Settings').'</a></li>';
				echo '<li><a href="addremoveteachers.php?from=home&id='.$row['id'].'">'._('Add/remove teachers').'</a></li>';
				echo ' <li><a href="transfercourse.php?from=home&id='.$row['id'].'">'._('Transfer ownership').'</a></li>';
				echo ' <li><a href="forms.php?from=home&action=delete&id='.$row['id'].'">'._('Delete').'</a></li>';
			} else if ($row['ownerid']!==$userid) {
				echo '<li><a href="#" onclick="removeSelfAsCoteacher(this,'.$row['id'].');return false;">'._('Remove yourself as a co-teacher').'</a></li>';
			}
			echo '</ul></span> ';
			echo '<a href="../course/course.php?cid='.$row['id'].'">'.Sanitize::encodeStringForDisplay($row['name']).'</a> ';
			if ($row['cleanupdate']>1) {
				echo ' <span style="color:orange;" title="'._('course is scheduled for cleanup').'">**</span>';	
				$hascleanup = true;
			}
			if (isset($row['available']) && (($row['available']&1)==1)) {
				echo ' <em style="color:green;">', _('Unavailable'), '</em>';
			}
		} else {
			echo '<a href="../course/course.php?cid='.$row['id'].'">'.Sanitize::encodeStringForDisplay($row['name']).'</a> ';
			echo ' <a href="unhidefromcourselist.php?type='.Sanitize::encodeUrlParam($type).'&tohide='.$row['id'].'" class="small">Unhide</a>';
		}

		echo '</li>';
	}
}
echo '</ul>';
if ($hascleanup) {
	echo '<p class="small info"><span style="color:orange;">**</span> ';
	echo _('course is scheduled for cleanup').'</p>';
}
if (count($deleted) > 0) {
    echo '<h1>',_('Courses Scheduled for Deletion'),'</h1>';
    echo '<ul class="nomark courselist">';
    foreach ($deleted as $row) {
        echo '<li><span class="dropdown"><a role="button" tabindex=0 class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
        echo '<img src="'.$staticroot.'/img/gears.png" alt="Options" class="mida"/></a>';
        echo '<ul role="menu" class="dropdown-menu">';
        echo ' <li><a href="unhidefromcourselist.php?type='.Sanitize::encodeUrlParam($type).'&toundel='.$row['id'].'">'._('Un-Delete').'</a></li>';
        echo '</ul></span> ';
        echo '<a href="../course/course.php?cid='.$row['id'].'">'.Sanitize::encodeStringForDisplay($row['name']).'</a> ';
		echo '</li>';
    }
    echo '</ul>';
}
echo '<p><a href="../index.php">Back to Home Page</a></p>';
require("../footer.php");

?>

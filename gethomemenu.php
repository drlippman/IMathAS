<?php
//returns list of courses for switch-to menu
//called via AJAX
//IMathAS
$init_skip_csrfp = true;
require("init.php");
//require("header.php");

$stm = $DBH->prepare("SELECT jsondata FROM imas_users WHERE id=:id");
$stm->execute(array(':id'=>$userid));
$userjson = json_decode($stm->fetchColumn(0), true);


echo '<b>Switch to:</b>';
echo '<div class="tabwrap">';
$query = "SELECT imas_courses.name,imas_courses.id,";
$query .= "IF(UNIX_TIMESTAMP()<imas_courses.startdate OR UNIX_TIMESTAMP()>imas_courses.enddate,0,1) as active, 0 ";
$query .= "FROM imas_teachers,imas_courses ";
$query .= "WHERE imas_teachers.courseid=imas_courses.id AND imas_teachers.userid=:userid AND imas_teachers.hidefromcourselist=0 ";
$query .= "AND (imas_courses.available=0 OR imas_courses.available=1) ";
$query .= "UNION SELECT imas_courses.name,imas_courses.id,";
$query .= "IF(UNIX_TIMESTAMP()<imas_courses.startdate OR UNIX_TIMESTAMP()>imas_courses.enddate,0,1) as active, 1 ";
$query .= "FROM imas_students,imas_courses ";
$query .= "WHERE imas_students.courseid=imas_courses.id AND imas_students.userid=:useridB AND imas_students.hidefromcourselist=0 ";
$query .= "AND (imas_courses.available=0 OR imas_courses.available=2) ";
$query .= "UNION SELECT imas_courses.name,imas_courses.id,";
$query .= "IF(UNIX_TIMESTAMP()<imas_courses.startdate OR UNIX_TIMESTAMP()>imas_courses.enddate,0,1) as active, 2 ";
$query .= "FROM imas_tutors,imas_courses ";
$query .= "WHERE imas_tutors.courseid=imas_courses.id AND imas_tutors.userid=:useridC AND imas_tutors.hidefromcourselist=0 ";
$query .= "AND (imas_courses.available=0 OR imas_courses.available=1) ";
$query .= "ORDER BY active DESC,name";
$stm = $DBH->prepare($query);
$stm->execute(array(':userid'=>$userid, ':useridB'=>$userid, ':useridC'=>$userid));
$allcourses = array(array(),array(),array());
$defaultorder = array(array(),array(),array());
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	$allcourses[$row[3]][$row[1]] = $row;
	$defaultorder[$row[3]][] = $row[1];
}
$usedtypecnt = 0;
for ($i=0;$i<3;$i++) {
	if (count($allcourses[$i])>0) {
		$usedtypecnt++;
	}
}
$usetabs = ($usedtypecnt>1);

$firsttab = -1;
$types = array('teach','take','tutor');
$typenames = array(_('Teaching'), _('Taking'), _('Tutoring'));
if ($usetabs) {
	echo '<ul class="tablist" role="tablist">';
	for ($i=0;$i<3;$i++) {
		if (count($allcourses[$i])==0) {continue;}
		if ($firsttab==-1) {
			$firsttab = $i;
			echo '<li class="active">';
		} else {
			echo '<li>';
		}
		echo '<a href="#" role="tab" id="homemenutab'.$i.'" ';
		echo 'aria-controls="homemenutabpanel'.$i.'" ';
		echo 'aria-selected="'.(($firsttab==$i)?'true':'false').'" ';
		echo 'onclick="setActiveTab(this);return false;">';
		echo $typenames[$i];
		echo '</a></li>';
	}
	echo '</ul>';
}

for ($i=0;$i<3;$i++) {
	if (count($allcourses[$i])==0) {continue;}
	echo '<div class="tabpanel" ';
	if ($usetabs) {
		echo 'id="homemenutabpanel'.$i.'" aria-labelledby="homemenutab'.$i.'" ';
	}
	if ($usetabs) {
		echo 'aria-hidden="'.(($firsttab==$i)?'false':'true').'" ';
		if ($firsttab!=$i) {
			echo 'style="display:none;" ';
		}
	}
	echo '><ul class="courselist">';
	if (isset($userjson['courseListOrder'][$types[$i]])) {
		$printed = array();
		printCourseOrder($userjson['courseListOrder'][$types[$i]], $allcourses[$i], $printed);
		$notlisted = array_diff(array_keys($allcourses[$i]), $printed);
		foreach ($notlisted as $course) {
			if (isset($allcourses[$i][$course])) {
				printCourseLine($allcourses[$i][$course]);
			}
		}
	} else {
		foreach ($defaultorder[$i] as $course) {
			printCourseLine($allcourses[$i][$course]);
		}
	}
	echo '</ul></div>';
}
function printCourseOrder($order, $data, &$printed) {
	foreach ($order as $item) {
		if (is_array($item)) {
			echo '<li class="coursegroup"><b>'.Sanitize::encodeStringForDisplay($item['name']).'</b>';
			echo '<ul class="courselist">';
			printCourseOrder($item['courses'], $data, $printed);
			echo '</ul></li>';
		} else if (isset($data[$item])) {
			printCourseLine($data[$item]);
			$printed[] = $item;
		}
	}		
}
function printCourseLine($row) {
	global $imasroot;
	echo "<li>";
	if ($row[2]==0) {
		echo '<i>'._('Inactive: ').'</i>';
	}
	echo "<a href=\"$imasroot/course/course.php?cid=" . Sanitize::courseId($row[1])
		. "&folder=0\">".Sanitize::encodeStringForDisplay($row[0]) . "</a></li>";
}
echo '</div>';
echo "<p><a href=\"$imasroot/actions.php?action=logout\">Log Out</a></p>";

?>

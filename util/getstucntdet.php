<!DOCTYPE html>
<html>
<head>
<style type="text/css">
ul {
	list-style-type: none;
}
</style>
</head>
<body>
<?php
	require("../init.php");
	if ($myrights<100 && ($myspecialrights&(32+64))==0) {
		exit;
	}
	$now = time();


	$start = $now - 60*60*24*30;
	$end = $now;
	if (isset($_GET['start'])) {
		$parts = explode('-',$_GET['start']);
		if (count($parts)==3) {
			$start = mktime(0,0,0,$parts[0],$parts[1],$parts[2]);
		}
	} else if (isset($_GET['days'])) {
		$start = $now - 60*60*24*intval($_GET['days']);
	}

	if (isset($_GET['end'])) {
		$parts = explode('-',$_GET['end']);
		if (count($parts)==3) {
			$end = mktime(0,0,0,$parts[0],$parts[1],$parts[2]);
		}
	}

	echo '<h1>Enrollments from '.date('M j, Y',$start).' to '.date('M j, Y',$end).'</h1>';
	echo '<p>This will list all students who last accessed the course between those dates.</p>';

	echo '<p>Courses marked with <sup>*</sup> have more than one instructor, and the enrollments have already been counted earlier so will be omitted</p>';

	/*if (isset($CFG['GEN']['guesttempaccts'])) {
		$skipcid = $CFG['GEN']['guesttempaccts'];
	} else {
		$skipcid = array();
	}
	$stm = $DBH->query("SELECT id FROM imas_courses WHERE (istemplate&4)=4");
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$skipcid[] = $row[0];
	}
	$skipcids = implode(',',$skipcid);
	*/
    $query = "SELECT g.name,u.LastName,u.FirstName,c.id,c.name AS cname,COUNT(DISTINCT s.id),u.email FROM imas_students AS s JOIN imas_teachers AS t ";
    $query .= "ON s.courseid=t.courseid AND s.lastaccess>$start ";
	if ($end != $now) {
		$query .= "AND s.lastaccess<$end ";
	}
	$query .= "JOIN imas_courses AS c ON t.courseid=c.id ";
	$query .= "JOIN imas_users as u ";
	$query .= "ON u.id=t.userid JOIN imas_groups AS g ON g.id=u.groupid GROUP BY u.id,c.id ORDER BY g.name,u.LastName,u.FirstName,c.name";
	$stm = $DBH->query($query);
    $lastgroup = '';  $grpcnt = 0; $grpdata = '';  $lastuser = ''; $userdata = '';
    $grpinstrcnt = 0; $lastemail = '';
	$seencid = array();
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		if ($row[1].', '.$row[2]!=$lastuser) {
			if ($lastuser != '') {
				$grpdata .= '<li><span class="pii-full-name"><b>'.Sanitize::encodeStringForDisplay($lastuser).'</b></span><ul>';
				$grpdata .= $userdata;
				$grpdata .= '</ul></li>';
			}
			$userdata = '';
			$lastuser = $row[1].', '.$row[2];
            $lastemail = $row[6];
            $grpinstrcnt++;
		}
		if ($row[0] != $lastgroup) {
			if ($lastgroup != '') {
				echo "<p><b>".Sanitize::encodeStringForDisplay($lastgroup)."</b>: ".Sanitize::encodeStringForDisplay($grpcnt);
				echo '<ul>'.$grpdata.'</ul></p>';
			}
			$grpcnt = 0;  $grpdata = ''; $grpinstrcnt = 0;
			$lastgroup = $row[0];
		}
		$userdata .= "<li>".Sanitize::encodeStringForDisplay($row[4]).' ('.Sanitize::encodeStringForDisplay($row[3]).'): <b>'.Sanitize::encodeStringForDisplay($row[5]).'</b>';
		if (!in_array($row[3],$seencid)) {
			$grpcnt += $row[5];
			$seencid[] = $row[3];
		} else {
			$userdata .= "<sup>*</sup>";
		}
		$userdata .= "</li>";
	}
	$grpdata .= '<li><span class="pii-full-name"><b>'.Sanitize::encodeStringForDisplay($lastuser).'</b></span><ul>';
	$grpdata .= $userdata;
	$grpdata .= '</ul></li>';
    $grpinstrcnt++;
    echo "<p><b>" . Sanitize::encodeStringForDisplay($lastgroup) . "</b>: " . Sanitize::encodeStringForDisplay($grpcnt) . " students, " . Sanitize::encodeStringForDisplay($grpinstrcnt) . " instructors";
    echo '<ul>'.$grpdata.'</ul></p>';

?>
</body>
</html>

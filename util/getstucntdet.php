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
	require("../validate.php");
	if ($myrights<40) {
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
	
	echo '<h2>Enrollments from '.date('M j, Y',$start).' to '.date('M j, Y',$end).'</h2>';
	echo '<p>This will list all students who last accessed the course between those dates.</p>';
	
	echo '<p>Courses marked with <sup>*</sup> have more than one instructor, and the enrollments have already been counted earlier so will be omitted</p>';
	
	/*if (isset($CFG['GEN']['guesttempaccts'])) {
		$skipcid = $CFG['GEN']['guesttempaccts'];
	} else {
		$skipcid = array();
	}
	
	$query = "SELECT id FROM imas_courses WHERE (istemplate&4)=4";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$skipcid[] = $row[0];
	}
	$skipcids = implode(',',$skipcid);
	*/
	$query = "SELECT g.name,u.LastName,u.FirstName,c.id,c.name AS cname, COUNT(DISTINCT s.id) FROM imas_students AS s JOIN imas_teachers AS t ";
	$query .= "ON s.courseid=t.courseid AND s.lastaccess>$start ";
	if ($end != $now) {
		$query .= "AND s.lastaccess<$end ";
	}
	$query .= "JOIN imas_courses AS c ON t.courseid=c.id ";
	$query .= "JOIN imas_users as u ";
	$query .= "ON u.id=t.userid JOIN imas_groups AS g ON g.id=u.groupid GROUP BY u.id,c.id ORDER BY g.name,u.LastName,u.FirstName,c.name";
	
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$lastgroup = '';  $grpcnt = 0; $grpdata = '';  $lastuser = ''; $userdata = '';
	$seencid = array();
	while ($row = mysql_fetch_row($result)) {
		if ($row[1].', '.$row[2]!=$lastuser) {
			if ($lastuser != '') {
				$grpdata .= '<li><b>'.$lastuser.'</b><ul>';
				$grpdata .= $userdata;
				$grpdata .= '</ul></li>';
			}
			$userdata = '';
			$lastuser = $row[1].', '.$row[2];
		}
		if ($row[0] != $lastgroup) {
			if ($lastgroup != '') {
				echo "<p><b>$lastgroup</b>: $grpcnt";
				echo '<ul>'.$grpdata.'</ul></p>';
			}
			$grpcnt = 0;  $grpdata = '';
			$lastgroup = $row[0];
		}
		$userdata .= "<li>".$row[4].' ('.$row[3].'): <b>'.$row[5].'</b>';
		if (!in_array($row[3],$seencid)) {
			$grpcnt += $row[5];
			$seencid[] = $row[3];
		} else {
			$userdata .= "<sup>*</sup>";
		}
		$userdata .= "</li>";
	}
	$grpdata .= '<li><b>'.$lastuser.'</b><ul>';
	$grpdata .= $userdata;
	$grpdata .= '</ul></li>';
	$userdata = '';
	$lastuser = $row[1].', '.$row[2];
	echo "<p><b>$lastgroup</b>: $grpcnt";
	echo '<ul>'.$grpdata.'</ul></p>';
	
?>
</body>
</html>

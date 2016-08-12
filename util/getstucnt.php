<?php
	require("../validate.php");
	if ($myrights<40) {
		exit;
	}
	$now = time();
	$date = mktime(0,0,0,7,10,2011);
	echo "<p>Active users since 7/10/11</p>";
	$query = "SELECT count(DISTINCT imas_users.id) FROM imas_users,imas_students WHERE ";
	$query .= "imas_users.id=imas_students.userid AND imas_users.lastaccess>$date";

	//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$stm = $DBH->query($query);
	//DB echo "<p>Student count: ".mysql_result($result,0,0);
	echo "<p>Student count: ".$stm->fetchColumn(0);

	if (isset($_GET['days'])) {
		$days = intval($_GET['days']);
	} else {
		$days = 30;
	}

	if (isset($CFG['GEN']['guesttempaccts'])) {
		$skipcid = $CFG['GEN']['guesttempaccts'];
	} else {
		$skipcid = array();
	}
	//DB $query = "SELECT id FROM imas_courses WHERE (istemplate&4)=4";
	//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	//DB while ($row = mysql_fetch_row($result)) {
	$stm = $DBH->query("SELECT id FROM imas_courses WHERE (istemplate&4)=4");
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$skipcid[] = $row[0];
	}
	$skipcids = implode(',',$skipcid);

	$date = $now - 60*60*24*$days;
	echo "<p>Active enrollments in $days Days</p>";
	$query = "SELECT count(imas_students.id) FROM imas_users,imas_students WHERE ";
	$query .= "imas_users.id=imas_students.userid AND imas_users.lastaccess>$date";
	if (count($skipcid)>0) {
		$query .= " AND imas_students.courseid NOT IN ($skipcids)";
	}

	//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$stm = $DBH->query($query);
	//DB echo "<p>Student count: ".mysql_result($result,0,0);
	echo "<p>Student count: ".$stm->fetchColumn(0);

	echo "<p>Active users in $days Days</p>";
	$query = "SELECT count(DISTINCT imas_users.id) FROM imas_users,imas_students WHERE ";
	$query .= "imas_users.id=imas_students.userid AND imas_users.lastaccess>$date";
	if (count($skipcid)>0) {
		$query .= " AND imas_students.courseid NOT IN ($skipcids)";
	}

	//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$stm = $DBH->query($query);
	//DB echo "<p>Student count: ".mysql_result($result,0,0);
	echo "<p>Student count: ".$stm->fetchColumn(0);

	$query = "SELECT count(DISTINCT imas_users.id) FROM imas_users,imas_teachers WHERE ";
	$query .= "imas_users.id=imas_teachers.userid AND imas_users.lastaccess>$date";
	if (count($skipcid)>0) {
		$query .= " AND imas_teachers.courseid NOT IN ($skipcids)";
	}
	//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$stm = $DBH->query($query);
	//DB echo "</p><p>Teacher count: ".mysql_result($result,0,0)."</p>";
	echo "</p><p>Teacher count: ".$stm->fetchColumn(0)."</p>";

	echo "<p>Active student association (by course owner)</p>";
	$query = "SELECT g.name,u.LastName,COUNT(DISTINCT s.id) FROM imas_students AS s JOIN imas_courses AS t ";
	$query .= "ON s.courseid=t.id AND s.lastaccess>$date  JOIN imas_users as u  ";
	$query .= "ON u.id=t.ownerid JOIN imas_groups AS g ON g.id=u.groupid GROUP BY u.id ORDER BY g.name";
	//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$stm = $DBH->query($query);
	$lastgroup = '';  $grpcnt = 0; $grpdata = '';
	//DB while ($row = mysql_fetch_row($result)) {
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		if ($row[0] != $lastgroup) {
			if ($lastgroup != '') {
				echo "<b>$lastgroup</b>: $grpcnt<br/>";
				echo $grpdata;
			}
			$grpcnt = 0;  $grpdata = '';
			$lastgroup = $row[0];
		}
		$grpdata .= "{$row[1]}:  {$row[2]}<br/>";
		$grpcnt += $row[2];
	}
	echo "<b>$lastgroup</b>: $grpcnt<br/>";
	echo $grpdata;

	echo "<p>Active students last hour: ";
	$date = $now - 60*60;
	$query = "SELECT count(DISTINCT imas_users.id) FROM imas_users,imas_students WHERE ";
	$query .= "imas_users.id=imas_students.userid AND imas_users.lastaccess>$date";

	//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$stm = $DBH->query($query);
	//DB echo mysql_result($result,0,0)."</p>";
	echo $stm->fetchColumn(0)."</p>";



	if (isset($_GET['emails']) && $myrights>75) {
		//DB $query = "SELECT email FROM imas_users WHERE rights>20";
		//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		$stm = $DBH->query("SELECT email FROM imas_users WHERE rights>20");
		echo "<p>";
		//DB while ($row = mysql_fetch_row($result)) {
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			echo $row[0]."; ";
		}
		echo "</p>";
	}

?>

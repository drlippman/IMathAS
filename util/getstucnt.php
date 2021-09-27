<?php
	require("../init.php");
	if ($myrights<100 && ($myspecialrights&(32+64))==0) {
		exit;
	}
	$now = time();
	$date = mktime(0,0,0,7,10,2011);
	echo "<p>Active users since 7/10/11</p>";
	$query = "SELECT count(DISTINCT imas_users.id) FROM imas_users,imas_students WHERE ";
	$query .= "imas_users.id=imas_students.userid AND imas_users.lastaccess>$date";
	$stm = $DBH->query($query);
	echo "<p>Student count: ".Sanitize::onlyInt($stm->fetchColumn(0));

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
	$stm = $DBH->query("SELECT id FROM imas_courses WHERE istemplate > 0 AND (istemplate&4)=4");
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$skipcid[] = $row[0];
	}
	$skipcids = implode(',',$skipcid);

	$date = $now - 60*60*24*$days;
	echo "<p>Active enrollments in " . Sanitize::onlyInt($days) . " Days</p>";
	$query = "SELECT count(imas_students.id) FROM imas_users,imas_students WHERE ";
	$query .= "imas_users.id=imas_students.userid AND imas_users.lastaccess>$date";
	if (count($skipcid)>0) {
		$query .= " AND imas_students.courseid NOT IN ($skipcids)";
	}
	$stm = $DBH->query($query);
	echo "<p>Student count: ".Sanitize::onlyInt($stm->fetchColumn(0));

	echo "<p>Active users in " . Sanitize::onlyInt($days). "Days</p>";
	$query = "SELECT count(DISTINCT imas_users.id) FROM imas_users,imas_students WHERE ";
	$query .= "imas_users.id=imas_students.userid AND imas_users.lastaccess>$date";
	if (count($skipcid)>0) {
		$query .= " AND imas_students.courseid NOT IN ($skipcids)";
	}
	$stm = $DBH->query($query);
	echo "<p>Student count: ".Sanitize::onlyInt($stm->fetchColumn(0));

	$query = "SELECT count(DISTINCT imas_users.id) FROM imas_users,imas_teachers WHERE ";
	$query .= "imas_users.id=imas_teachers.userid AND imas_users.lastaccess>$date";
	if (count($skipcid)>0) {
		$query .= " AND imas_teachers.courseid NOT IN ($skipcids)";
	}
	$stm = $DBH->query($query);
	echo "</p><p>Teacher count: ".Sanitize::onlyInt($stm->fetchColumn(0))."</p>";

	echo "<p>Active student association (by course owner)</p>";
	$query = "SELECT g.name,u.LastName,COUNT(DISTINCT s.id) FROM imas_students AS s JOIN imas_courses AS t ";
	$query .= "ON s.courseid=t.id AND s.lastaccess>$date  JOIN imas_users as u  ";
	$query .= "ON u.id=t.ownerid JOIN imas_groups AS g ON g.id=u.groupid GROUP BY u.id ORDER BY g.name";
	$stm = $DBH->query($query);
	$lastgroup = '';  $grpcnt = 0; $grpdata = '';
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		if ($row[0] != $lastgroup) {
			if ($lastgroup != '') {
				echo "<b>". Sanitize::encodeStringForDisplay($lastgroup)."</b>: ". Sanitize::onlyInt($grpcnt)."<br/>";
				echo $grpdata;
			}
			$grpcnt = 0;  $grpdata = '';
			$lastgroup = $row[0];
		}

		$grpdata .= "<span class='pii-last-name'>".Sanitize::encodeStringForDisplay($row[1]) ."</span>:  ".  Sanitize::encodeStringForDisplay($row[2]) ."<br/>";
		$grpcnt += $row[2];
	}
	echo "<b>".Sanitize::encodeStringForDisplay($lastgroup). "</b>: " .Sanitize::onlyInt($grpcnt) ."<br/>";
	echo $grpdata;

	echo "<p>Active students last hour: ";
	$date = $now - 60*60;
	$query = "SELECT count(DISTINCT imas_users.id) FROM imas_users,imas_students WHERE ";
	$query .= "imas_users.id=imas_students.userid AND imas_users.lastaccess>$date";
	$stm = $DBH->query($query);
	echo Sanitize::encodeStringForDisplay($stm->fetchColumn(0)) . "</p>";



	if (isset($_GET['emails']) && $myrights>75) {
		$stm = $DBH->query("SELECT email FROM imas_users WHERE rights>20");
		echo "<p>";
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			echo '<span class="pii-email">'.Sanitize::encodeStringForDisplay($row[0]) . "</span>; ";
		}
		echo "</p>";
	}

?>

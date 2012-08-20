<?php
	require("../validate.php");
	$now = time();
	$date = mktime(0,0,0,7,10,2011);  
	echo "<p>Active users since 7/10/11</p>";
	$query = "SELECT count(DISTINCT imas_users.id) FROM imas_users,imas_students WHERE ";
	$query .= "imas_users.id=imas_students.userid AND imas_users.lastaccess>$date";
	
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	echo "<p>Student count: ".mysql_result($result,0,0);

	$date = $now - 60*60*24*7;  
	echo "<p>Active users in 7 Days</p>";
	$query = "SELECT count(DISTINCT imas_users.id) FROM imas_users,imas_students WHERE ";
	$query .= "imas_users.id=imas_students.userid AND imas_users.lastaccess>$date";
	
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	echo "<p>Student count: ".mysql_result($result,0,0);
	
	$query = "SELECT count(DISTINCT imas_users.id) FROM imas_users,imas_teachers WHERE ";
	$query .= "imas_users.id=imas_teachers.userid AND imas_users.lastaccess>$date";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	echo "</p><p>Teacher count: ".mysql_result($result,0,0)."</p>";

	echo "<p>Active student association</p>";
	$query = "SELECT g.name,u.LastName,COUNT(DISTINCT s.id) FROM imas_students AS s JOIN imas_teachers AS t ";
	$query .= "ON s.courseid=t.courseid AND s.lastaccess>$date  JOIN imas_users as u  ";
	$query .= "ON u.id=t.userid JOIN imas_groups AS g ON g.id=u.groupid GROUP BY u.id ORDER BY g.name";
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	$lastgroup = '';
	while ($row = mysql_fetch_row($result)) {
		if ($row[0] != $lastgroup) {
			echo "<b>{$row[0]}</b><br/>";
			$lastgroup = $row[0];
		}
		echo "{$row[1]}:  {$row[2]}<br/>";
	}
	
	
	echo "<p>Active students last hour: ";
	$date = $now - 60*60;
	$query = "SELECT count(DISTINCT imas_users.id) FROM imas_users,imas_students WHERE ";
	$query .= "imas_users.id=imas_students.userid AND imas_users.lastaccess>$date";
	
	$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
	echo mysql_result($result,0,0)."</p>";
	
	
	
	if (isset($_GET['emails']) && $myrights>75) {
		$query = "SELECT email FROM imas_users WHERE rights>20";
		$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		echo "<p>";
		while ($row = mysql_fetch_row($result)) {
			echo $row[0]."; ";
		}
		echo "</p>";
	}
	
?>


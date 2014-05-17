<?php
require("../validate.php");
if ($myrights<100) {exit;}
if ((isset($_POST['submit']) && $_POST['submit']=="Message") || isset($_GET['masssend'])) {
	$cid = $CFG['GEN']['sendquestionproblemsthroughcourse'];
	$teacherid = true;
	$calledfrom = "itemsearch";
	require("../course/masssend.php");
	exit;
}

require("../header.php");
echo '<h3>Search through inline and link text items</h3>';
echo '<form method="post"><p>Search: <input type="text" name="search" size="40" value="'.htmlentities(stripslashes($_POST['search'])).'"> <input type="submit" value="Search"/></p>';
if (isset($_POST['search'])) {
	echo '<p>';
	echo '<input type="submit" name="submit" value="Message"></p><p>';
	$srch = $_POST['search'];
	$query = "SELECT DISTINCT imas_users.*,imas_courses.id AS cid,imas_groups.name AS groupname FROM imas_users JOIN imas_courses ON imas_users.id=imas_courses.ownerid JOIN imas_groups ON imas_groups.id=imas_users.groupid WHERE imas_courses.id IN ";
	$query .= "(SELECT courseid FROM imas_inlinetext WHERE text LIKE '%$srch%') OR imas_courses.id IN ";
	$query .= "(SELECT courseid FROM imas_linkedtext WHERE text LIKE '%$srch%' OR summary LIKE '%$srch%') ORDER BY imas_groups.name,imas_users.LastName";
	$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
	$lastperson = '';
	echo "Count: ".mysql_num_rows($result);
	while ($row = mysql_fetch_assoc($result)) {
		$thisperson = $row['LastName'].', '.$row['FirstName'];
		if ($thisperson != $lastperson) {
			echo '<br/><input type="checkbox" name="checked[]" value="'.$row['id'].'" checked="checked"> '.$thisperson .' ('.$row['groupname'].')';
			$lastperson= $thisperson;
		}
		echo ' <a href="../course/course.php?cid='.$row['cid'].'" target="_blank">'.$row['cid'].'</a>';
	}
	echo '</p>';
	
}
echo '</form>';
?>

<?php
require("../validate.php");

$pagetitle = "Unhide Courses from Course List";
$curBreadcrumb = "$breadcrumbbase Unhide Courses\n";
require("../header.php");

echo '<div class=breadcrumb>'.$curBreadcrumb.'</div>';
echo '<h2>Unhide Courses from Course List</h2>';

if (isset($_GET['cid'])) {
	$cid = intval($_GET['cid']);
	if ($cid>0) {
		$query = "UPDATE imas_students SET hidefromcourselist=0 WHERE courseid='$cid' AND userid='$userid'";
		mysql_query($query) or die("Query failed : $query" . mysql_error());
	}
}

$query = 'SELECT ic.name,ic.id FROM imas_courses AS ic JOIN imas_students AS istu ON ic.id=istu.courseid ';
$query .= "WHERE istu.userid='$userid' AND istu.hidefromcourselist=1";
$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
echo '<ul class="nomark">';
if (mysql_num_rows($result)==0) {
	echo '<li>No courses to unhide</li>';
} else {
	while ($row = mysql_fetch_row($result)) {
		echo '<li>'.$row[0].' <a href="unhidefromcourselist.php?cid='.$row[1].'">Unhide</a></li>';
	}
}
echo '</ul>';
echo '<a href="../index.php">Back to Home Page</a>';
require("../footer.php");

?>


<?php
//returns list of courses for switch-to menu
//called via AJAX
//IMathAS
require("validate.php");

if (isset($_GET['cid']) && $_GET['cid']>0) {
	$cid = $_GET['cid'];
	//DB $query = "SELECT topbar FROM imas_courses WHERE id='$cid'";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB $topbar = explode('|',mysql_result($result,0,0));
	$stm = $DBH->prepare("SELECT topbar FROM imas_courses WHERE id=:id");
	$stm->execute(array(':id'=>$cid));
	$topbar = explode('|',$stm->fetchColumn(0));
	$topbar[0] = explode(',',$topbar[0]);
	$topbar[1] = explode(',',$topbar[1]);
	if (!isset($topbar[2])) {$topbar[2] = 0;}
	if ($topbar[0][0] == null) {unset($topbar[0][0]);}
	if ($topbar[1][0] == null) {unset($topbar[1][0]);}
	if (isset($teacherid) && count($topbar[1])>0 && $topbar[2]==0) {
		echo '<b>Jump to:</b><ul class="nomark">';
		if (in_array(0,$topbar[1]) && $msgset<4) { //messages
			echo "<li><a href=\"$imasroot/msgs/msglist.php?cid=$cid\">Messages</a>$newmsgs </li>";
		}
		if (in_array(1,$topbar[1])) { //Stu view
			echo "<li><a href=\"course.php?cid=$cid&stuview=0\">Student View</a> </li>";
		}
		if (in_array(2,$topbar[1])) { //Gradebook
			echo "<li><a href=\"gradebook.php?cid=$cid\">Show Gradebook</a> </li> ";
		}
		if (in_array(3,$topbar[1])) { //List stu
			echo "<li><a href=\"listusers.php?cid=$cid\">List Students</a> </li> \n";
		}
		if (in_array(4,$topbar[1])) { //Calendar
			echo "<li><a href=\"showcalendar.php?cid=$cid\">Calendar</a> </li> \n";
		}
		if (in_array(5,$topbar[1])) { //Calendar
			echo "<li><a href=\"course.php?cid=$cid&quickview=on\">Quick View</a>  </li> \n";
		}
		echo "<li><a href=\"$imasroot/actions.php?action=logout\">Log Out</a></li>";
		echo '</ul>';
	} else if (!isset($teacherid)  && $topbar[2]==0 && (count($topbar[0])>0 || $previewshift>-1)) {
		echo '<ul class="nomark">';
		if (in_array(0,$topbar[0]) && $msgset<4) { //messages
			echo "<li><a href=\"$imasroot/msgs/msglist.php?cid=$cid\">Messages</a>$newmsgs </li> ";
		}
		if (in_array(3,$topbar[0])) { //forums
			echo "<li><a href=\"$imasroot/forums/forums.php?cid=$cid\">Forums</a>$newpostscnt </li> ";
		}
		if (in_array(1,$topbar[0])) { //Gradebook
			echo "<li><a href=\"gradebook.php?cid=$cid\">Show Gradebook</a> </li> ";
		}
		if (in_array(2,$topbar[0])) { //Calendar
			echo "<li><a href=\"showcalendar.php?cid=$cid\">Calendar</a> </li>\n";
		}
		echo "<li><a href=\"$imasroot/actions.php?action=logout\">Log Out</a></li>";
		echo '</ul>';
	}

}
echo '<b>Switch to:</b><ul class="nomark">';
//DB $query = "SELECT imas_courses.name,imas_courses.id FROM imas_teachers,imas_courses ";
//DB $query .= "WHERE imas_teachers.courseid=imas_courses.id AND imas_teachers.userid='$userid' ";
//DB $query .= "AND (imas_courses.available=0 OR imas_courses.available=1) ";
//DB $query .= "UNION SELECT imas_courses.name,imas_courses.id FROM imas_students,imas_courses ";
//DB $query .= "WHERE imas_students.courseid=imas_courses.id AND imas_students.userid='$userid' ";
//DB $query .= "AND (imas_courses.available=0 OR imas_courses.available=2) ";
//DB $query .= "ORDER BY name";
//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
//DB while ($row = mysql_fetch_row($result)) {
$query = "SELECT imas_courses.name,imas_courses.id FROM imas_teachers,imas_courses ";
$query .= "WHERE imas_teachers.courseid=imas_courses.id AND imas_teachers.userid=:userid ";
$query .= "AND (imas_courses.available=0 OR imas_courses.available=1) ";
$query .= "UNION SELECT imas_courses.name,imas_courses.id FROM imas_students,imas_courses ";
$query .= "WHERE imas_students.courseid=imas_courses.id AND imas_students.userid=:useridB ";
$query .= "AND (imas_courses.available=0 OR imas_courses.available=2) ";
$query .= "ORDER BY name";
$stm = $DBH->prepare($query);
$stm->execute(array(':userid'=>$userid, ':useridB'=>$userid));
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	echo "<li><a href=\"$imasroot/course/course.php?cid={$row[1]}&folder=0\">{$row[0]}</a></li>";
}
echo "<li><a href=\"$imasroot/actions.php?action=logout\">Log Out</a></li>";
echo '</ul>';

?>

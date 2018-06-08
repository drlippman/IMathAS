<?php
//IMathAS 2014
//list forum thread viewers

require("../init.php");

if (!isset($teacherid)) {
	echo "Not authorized to view this page";
	exit;
}
if (!isset($_GET['thread'])) {
	echo "No thread specified";
	exit;
}
$thread = intval($_GET['thread']);

//DB query = "SELECT imas_forums.id FROM imas_forums JOIN imas_forum_threads ON imas_forums.id=imas_forum_threads.forumid ";
//DB $query .= " WHERE imas_forum_threads.id=$thread AND imas_forums.courseid='$cid'";
//DB $result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
//DB if (mysql_num_rows($result)==0) {
$query = "SELECT imas_forums.id FROM imas_forums JOIN imas_forum_threads ON imas_forums.id=imas_forum_threads.forumid ";
$query .= "WHERE imas_forum_threads.id=:id AND imas_forums.courseid=:courseid";
$stm = $DBH->prepare($query);
$stm->execute(array(':id'=>$thread, ':courseid'=>$cid));
if ($stm->rowCount()==0) {
	echo 'Invalid thread';
	exit;
}
$flexwidth = true;
$nologo = true;
require("../header.php");

echo '<h3>'._('Thread Views').'</h3>';

//DB $query = "SELECT iu.LastName,iu.FirstName,ifv.lastview FROM imas_users AS iu JOIN ";
//DB $query .= "imas_forum_views AS ifv ON iu.id=ifv.userid WHERE ifv.threadid=$thread ORDER BY ifv.lastview";
//DB $result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
//DB if (mysql_num_rows($result)==0) {
$query = "SELECT iu.LastName,iu.FirstName,ifv.lastview FROM imas_users AS iu JOIN ";
$query .= "imas_forum_views AS ifv ON iu.id=ifv.userid WHERE ifv.threadid=:threadid ORDER BY ifv.lastview";
$stm = $DBH->prepare($query);
$stm->execute(array(':threadid'=>$thread));
if ($stm->rowCount()==0) {
	echo '<p>'._('No thread views').'</p>';
} else {
	echo '<table><thead><tr><th>'._('Name').'</th><th>'._('Last Viewed').'</th></tr></thead>';
	echo '<tbody>';
	//DB while ($row = mysql_fetch_assoc($result)) {
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		echo '<tr><td>'.$row['LastName'].', '.$row['FirstName'].'</td>';
		echo '<td>'.tzdate("F j, Y, g:i a", $row['lastview']).'</td></tr>';
	}
	echo '</tbody></table>';
}
echo '<p class="small">'._('Note: Only the most recent thread view per person is shown').'</p>';
require("../footer.php");
?>

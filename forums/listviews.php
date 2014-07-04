<?php
//IMathAS 2014
//list forum thread viewers

require("../validate.php");

if (!isset($teacherid)) {
	echo "Not authorized to view this page";
	exit;
}
if (!isset($_GET['thread'])) {
	echo "No thread specified";
	exit;
}
$thread = intval($_GET['thread']);

$query = "SELECT imas_forums.id FROM imas_forums JOIN imas_forum_threads ON imas_forums.id=imas_forum_threads.forumid ";
$query .= " WHERE imas_forum_threads.id=$thread AND imas_forums.courseid='$cid'";
$result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
if (mysql_num_rows($result)==0) {
	echo 'Invalid thread';
	exit;
}
$flexwidth = true;
require("../header.php");

echo '<h4>'._('Thread Views').'</h4>';

$query = "SELECT iu.LastName,iu.FirstName,ifv.lastview FROM imas_users AS iu JOIN ";
$query .= "imas_forum_views AS ifv ON iu.id=ifv.userid WHERE ifv.threadid=$thread ORDER BY ifv.lastview";
$result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
if (mysql_num_rows($result)==0) {
	echo '<p>'._('No thread views').'</p>';
} else {
	echo '<table><thead><tr><th>'._('Name').'</th><th>'._('Last Viewed').'</th></tr></thead>';
	echo '<tbody>';
	while ($row = mysql_fetch_assoc($result)) {
		echo '<tr><td>'.$row['LastName'].', '.$row['FirstName'].'</td>';
		echo '<td>'.tzdate("F j, Y, g:i a", $row['lastview']).'</td></tr>';
	}
	echo '</tbody></table>';
}
echo '<p class="small">'._('Note: Only the most recent thread view per person is shown').'</p>';
require("../footer.php");
?>

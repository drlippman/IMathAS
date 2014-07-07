<?php
//IMathAS 2014
//list forum thread likes

require("../validate.php");

if (!isset($_GET['post'])) {
	echo "No post specified";
	exit;
}
$postid = intval($_GET['post']);

$query = "SELECT imas_forums.id FROM imas_forums JOIN imas_forum_posts ON imas_forums.id=imas_forum_posts.forumid ";
$query .= " WHERE imas_forum_posts.id=$postid AND imas_forums.courseid='$cid'";
$result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
if (mysql_num_rows($result)==0) {
	echo 'Invalid post';
	exit;
}
$flexwidth = true;
$nologo = true;
require("../header.php");

echo '<h4>'._('Post Likes').'</h4>';

$query = "SELECT iu.LastName,iu.FirstName FROM imas_users AS iu JOIN ";
$query .= "imas_forum_likes AS ifl ON iu.id=ifl.userid WHERE ifl.postid=$postid ORDER BY iu.LastName,iu.FirstName";
$result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
if (mysql_num_rows($result)==0) {
	echo '<p>'._('No post likes').'</p>';
} else {
	echo '<ul class="nomark">';
	while ($row = mysql_fetch_assoc($result)) {
		echo '<li>'.$row['LastName'].', '.$row['FirstName'].'</li>';
	}
	echo '</ul>';
}
require("../footer.php");
?>

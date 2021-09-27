<?php
//IMathAS 2014
//list forum thread likes

require("../init.php");

if (!isset($_GET['post'])) {
	echo "No post specified";
	exit;
}
$postid = intval($_GET['post']);
$query = "SELECT imas_forums.id FROM imas_forums JOIN imas_forum_posts ON imas_forums.id=imas_forum_posts.forumid ";
$query .= " WHERE imas_forum_posts.id=:id AND imas_forums.courseid=:courseid";
$stm = $DBH->prepare($query);
$stm->execute(array(':id'=>$postid, ':courseid'=>$cid));
if ($stm->rowCount()==0) {
	echo 'Invalid post';
	exit;
}
$flexwidth = true;
$nologo = true;
require("../header.php");

echo '<h3>'._('Post Likes').'</h3>';
$query = "SELECT iu.LastName,iu.FirstName FROM imas_users AS iu JOIN ";
$query .= "imas_forum_likes AS ifl ON iu.id=ifl.userid WHERE ifl.postid=:postid ORDER BY iu.LastName,iu.FirstName";
$stm = $DBH->prepare($query);
$stm->execute(array(':postid'=>$postid));
if ($stm->rowCount()==0) {
	echo '<p>'._('No post likes').'</p>';
} else {
	echo '<ul class="nomark">';
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		printf('<li><span class="pii-full-name">%s, %s</span></li>',
			Sanitize::encodeStringForDisplay($row['LastName']),
			Sanitize::encodeStringForDisplay($row['FirstName']));
	}
	echo '</ul>';
}
require("../footer.php");
?>

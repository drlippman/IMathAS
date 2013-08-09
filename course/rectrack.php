<?php
/*
Types:
inlinetext	In inline text summary		imas_inlinetext.id
linkedsum	In linked text summary		imas_linkedtext.id
linkedlink	Linked text main link		imas_linkedtext.id
linkedintext	In Linked text test		imas_linkedtext.id
linkedviacal	Linked text via Calendar	imas_linkedtext.id
extref		Button click in question	imas_questions.id
assessintro	Link in assessment intro	imas_assessments.id
assess		Link to assessment		imas_assessments.id
assessviacal	Link to assessment via calendar	imas_assessments.id
assesssum	Link in assessment summary	imas_assessments.id
wiki		Link to wiki			imas_wikis.id
wikiintext	Link in wiki text		imas_wikis.id
forumpost	new forum post			imas_forum_posts.id/imas_forum_threads.id,  info has imas_forums.id 
forumreply	new forum reply			imas_forum_posts.id,  info has imas_forums.id ; imas_forum_threads.id 
forummod	modify form post/reply		imas_forum_posts.id,  info has imas_forums.id ; imas_forum_threads.id 
*/

require("../validate.php");
if (isset($studentid)) {
	$now = time();
	$query = "INSERT INTO imas_content_track (userid,courseid,type,typeid,viewtime,info) VALUES ";
	$query .= "('$userid','$cid','{$_POST['type']}','{$_POST['typeid']}',$now,'{$_POST['info']}')";
	mysql_query($query) or die("Query failed : " . mysql_error());
} 

?>

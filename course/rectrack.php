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
	if (isset($_POST['unloadinglinked'])) {
		$typeid = intval($_POST['unloadinglinked']);
		//DB $query = "SELECT id FROM imas_content_track WHERE courseid='$cid' AND userid='$userid' AND type='linkedlink' AND typeid='$typeid' ORDER BY viewtime DESC LIMIT 1";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB if (mysql_num_rows($result)>0) {
			//DB $row = mysql_fetch_row($result);
		$stm = $DBH->prepare("SELECT id FROM imas_content_track WHERE courseid=:courseid AND userid=:userid AND type='linkedlink' AND typeid=:typeid ORDER BY viewtime DESC LIMIT 1");
		$stm->execute(array(':courseid'=>$cid, ':userid'=>$userid, ':typeid'=>$typeid));
		if ($stm->rowCount()>0) {
			$row = $stm->fetch(PDO::FETCH_NUM);
			//DB $query = "UPDATE imas_content_track SET info=CONCAT(info,'::$now') WHERE id=".$row[0];
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("UPDATE imas_content_track SET info=CONCAT(info,:info) WHERE id=:id");
			$stm->execute(array(':info'=>"::$now", ':id'=>$row[0]));
		}
	}
	if (isset($_POST['type'])) {
		//DB $query = "INSERT INTO imas_content_track (userid,courseid,type,typeid,viewtime,info) VALUES ";
		//DB $query .= "('$userid','$cid','{$_POST['type']}','{$_POST['typeid']}',$now,'{$_POST['info']}')";
		//DB mysql_query($query) or die("Query failed : " . mysql_error());
		$query = "INSERT INTO imas_content_track (userid,courseid,type,typeid,viewtime,info) VALUES ";
		$query .= "(:userid, :courseid, :type, :typeid, :viewtime, :info)";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':userid'=>$userid, ':courseid'=>$cid, ':type'=>$_POST['type'], ':typeid'=>$_POST['typeid'], ':viewtime'=>$now, ':info'=>$_POST['info']));
	}
}

?>

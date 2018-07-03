<?php
if (empty($_GET['cid']) || empty($_GET['postid']) || !isset($_GET['like'])) {
	echo "fail";
	exit;
}
require("../init.php");
if (!isset($teacherid) && !isset($tutorid) && !isset($studentid)) {
	echo "fail";
	exit;
}
if (isset($teacherid)) {
	$isteacher = 2;
} else if (isset($tutorid)) {
	$isteacher = 1;
} else {
	$isteacher = 0;
}
$cid = intval($_GET['cid']);
$postid = intval($_GET['postid']);
$like = intval($_GET['like']);

if ($like==0) {
	//DB $query = "DELETE FROM imas_forum_likes WHERE postid=$postid AND userid='$userid'";
	//DB $result = mysql_query($query);
	//DB $aff =  mysql_affected_rows();
	$stm = $DBH->prepare("DELETE FROM imas_forum_likes WHERE postid=:postid AND userid=:userid");
	$stm->execute(array(':postid'=>$postid, ':userid'=>$userid));
	$aff =  $stm->rowCount();
} else {
	//DB $query = "SELECT id FROM imas_forum_likes WHERE postid=$postid AND userid='$userid'";
	//DB $result = mysql_query($query);
	//DB if (mysql_num_rows($result)>0) {
	$stm = $DBH->prepare("SELECT id FROM imas_forum_likes WHERE postid=:postid AND userid=:userid");
	$stm->execute(array(':postid'=>$postid, ':userid'=>$userid));
	if ($stm->rowCount()>0) {
		$aff = 0;
	} else {
		//DB $query = "SELECT threadid FROM imas_forum_posts WHERE id=$postid";
		//DB $result = mysql_query($query);
		//DB if (mysql_num_rows($result)==0) {echo "fail";exit;}
		//DB $threadid = mysql_result($result,0,0);
		$stm = $DBH->prepare("SELECT threadid FROM imas_forum_posts WHERE id=:id");
		$stm->execute(array(':id'=>$postid));
		if ($stm->rowCount()==0) {echo "fail";exit;}
		$threadid = $stm->fetchColumn(0);

		//DB $query = "INSERT INTO imas_forum_likes (userid,threadid,postid,type) VALUES ";
		//DB $query .= "('$userid',$threadid,$postid,$isteacher)";
		//DB mysql_query($query);
		$query = "INSERT INTO imas_forum_likes (userid,threadid,postid,type) VALUES ";
		$query .= "(:userid, :threadid, :postid, :type)";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':userid'=>$userid, ':threadid'=>$threadid, ':postid'=>$postid, ':type'=>$isteacher));
		$aff = 1;
	}
}

$likes = array(0,0,0);
//DB $query = "SELECT type,count(*) FROM imas_forum_likes WHERE postid='$postid'";
//DB $query .= "GROUP BY type";
//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
//DB while ($row = mysql_fetch_row($result)) {
$stm = $DBH->prepare("SELECT type,count(*) FROM imas_forum_likes WHERE postid=:postid GROUP BY type");
$stm->execute(array(':postid'=>$postid));
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	$likes[$row[0]] = $row[1];
}
$likemsg = 'Liked by ';
$likecnt = 0;
$likeclass = '';
if ($likes[0]>0) {
	$likeclass = ' liked';
	$likemsg .= $likes[0].' ' . ($likes[0]==1?'student':'students');
	$likecnt += $likes[0];
}
if ($likes[1]>0 || $likes[2]>0) {
	$likeclass = ' likedt';
	$n = $likes[1] + $likes[2];
	if ($likes[0]>0) { $likemsg .= ' and ';}
	$likemsg .= $n.' ';
	if ($likes[2]>0) {
		$likemsg .= ($n==1?'teacher':'teachers');
		if ($likes[1]>0) {
			$likemsg .= '/tutors/TAs';
		}
	} else if ($likes[1]>0) {
		$likemsg .= ($n==1?'tutor/TA':'tutors/TAs');
	}
	$likecnt += $n;
}
if ($likemsg=='Liked by ') {
	$likemsg = '';
} else {
	$likemsg .= '.';
}
if ($like==1) {
	$likemsg = 'You like this. '.$likemsg;
} else {
	$likemsg = 'Click to like this post. '.$likemsg;;
}
header('Content-type: application/json');
echo '{"aff":'.$aff.', "classn":"'.$likeclass.'", "msg":"'.$likemsg.'", "cnt":'.$likecnt.'}';
?>
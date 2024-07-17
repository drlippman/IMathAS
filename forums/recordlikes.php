<?php
if (empty($_GET['cid']) || empty($_GET['postid']) || !isset($_GET['like'])) {
	echo "fail";
	exit;
}
require_once "../init.php";
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
	$stm = $DBH->prepare("DELETE FROM imas_forum_likes WHERE postid=:postid AND userid=:userid");
	$stm->execute(array(':postid'=>$postid, ':userid'=>$userid));
	$aff =  $stm->rowCount();
} else {
	$stm = $DBH->prepare("SELECT id FROM imas_forum_likes WHERE postid=:postid AND userid=:userid");
	$stm->execute(array(':postid'=>$postid, ':userid'=>$userid));
	if ($stm->rowCount()>0) {
		$aff = 0;
	} else {
		$stm = $DBH->prepare("SELECT threadid FROM imas_forum_posts WHERE id=:id");
		$stm->execute(array(':id'=>$postid));
		if ($stm->rowCount()==0) {echo "fail";exit;}
		$threadid = $stm->fetchColumn(0);
		$query = "INSERT INTO imas_forum_likes (userid,threadid,postid,type) VALUES ";
		$query .= "(:userid, :threadid, :postid, :type)";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':userid'=>$userid, ':threadid'=>$threadid, ':postid'=>$postid, ':type'=>$isteacher));
		$aff = 1;
	}
}

$likes = array(0,0,0);
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
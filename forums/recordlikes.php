<?php
if (empty($_GET['cid']) || empty($_GET['postid']) || !isset($_GET['like'])) {
	echo "fail";
	exit;
}
require("../validate.php");
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
	$query = "DELETE FROM imas_forum_likes WHERE postid=$postid AND userid='$userid'";	
	$result = mysql_query($query);
	$aff =  mysql_affected_rows();
} else {
	$query = "SELECT id FROM imas_forum_likes WHERE postid=$postid AND userid='$userid'";
	$result = mysql_query($query);
	if (mysql_num_rows($result)>0) {
		$aff =0;
	} else {
		$query = "SELECT threadid FROM imas_forum_posts WHERE id=$postid";
		$result = mysql_query($query);
		if (mysql_num_rows($result)==0) {echo "fail";exit;}
		$threadid = mysql_result($result,0,0);
		
		$query = "INSERT INTO imas_forum_likes (userid,threadid,postid,type) VALUES ";
		$query .= "('$userid',$threadid,$postid,$isteacher)";
		mysql_query($query);
		$aff = 1;
	}
}

$likes = array(0,0,0);
$query = "SELECT type,count(*) FROM imas_forum_likes WHERE postid='$postid'";
$query .= "GROUP BY type";	
$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
while ($row = mysql_fetch_row($result)) {
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

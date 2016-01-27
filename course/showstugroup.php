<?php
//IMathAS:  List student group, AJAX called
//(c) 2016 David Lippman

/*** master php includes *******/
require("../validate.php");

/*** pre-html data manipulation, including function code *******/
$cid = intval($_GET['cid']);
$sgid = intval($_GET['gid']);

$out = '<ul>';
$userfound = false;
$query = "SELECT iu.id,iu.LastName,iu.FirstName FROM imas_users AS iu JOIN imas_stugroupmembers AS isg ";
$query .= "ON iu.id=isg.userid WHERE isg.stugroupid=$sgid ORDER BY iu.LastName,iu.FirstName";
$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
while ($row = mysql_fetch_row($result)) {
	if ($row[0]==$userid) {$userfound = true;}
	$out .= '<li>'.$row[1].', '.$row[2].'</li>';
}
$out .= '</ul>';

if ($userfound || isset($teacherid) || isset($tutorid)) {
	echo $out;
} else {
	echo "user $userid not found";
	echo $out;
}
?>		

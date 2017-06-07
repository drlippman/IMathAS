<?php
//IMathAS:  List student group, AJAX called
//(c) 2016 David Lippman

/*** master php includes *******/
require("../init.php");

/*** pre-html data manipulation, including function code *******/
$cid = intval($_GET['cid']);
$sgid = intval($_GET['gid']);

$out = '<ul>';
$userfound = false;
//DB $query = "SELECT iu.id,iu.LastName,iu.FirstName FROM imas_users AS iu JOIN imas_stugroupmembers AS isg ";
//DB $query .= "ON iu.id=isg.userid WHERE isg.stugroupid=$sgid ORDER BY iu.LastName,iu.FirstName";
//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
//DB while ($row = mysql_fetch_row($result)) {
$query = "SELECT iu.id,iu.LastName,iu.FirstName FROM imas_users AS iu JOIN imas_stugroupmembers AS isg ";
$query .= "ON iu.id=isg.userid WHERE isg.stugroupid=:stugroupid ORDER BY iu.LastName,iu.FirstName";
$stm = $DBH->prepare($query);
$stm->execute(array(':stugroupid'=>$sgid));
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
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

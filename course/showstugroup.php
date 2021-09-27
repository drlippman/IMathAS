<?php
//IMathAS:  List student group, AJAX called
//(c) 2016 David Lippman

/*** master php includes *******/
require("../init.php");

/*** pre-html data manipulation, including function code *******/
$cid = intval($_GET['cid']);
$sgid = intval($_GET['gid']);

if (!isset($teacherid) && !isset($tutorid) && !isset($studentid)) {
	exit;
}

$out = '<ul>';
$userfound = false;
$query = "SELECT iu.id,iu.LastName,iu.FirstName FROM imas_users AS iu JOIN imas_stugroupmembers AS isg ";
$query .= "ON iu.id=isg.userid WHERE isg.stugroupid=:stugroupid ORDER BY iu.LastName,iu.FirstName";
$stm = $DBH->prepare($query);
$stm->execute(array(':stugroupid'=>$sgid));
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	if ($row[0]==$userid) {$userfound = true;}
	$out .= sprintf('<li><span class="pii-full-name">%s, %s</span></li>',
		Sanitize::encodeStringForDisplay($row[1]),
		Sanitize::encodeStringForDisplay($row[2]));
}
$out .= '</ul>';

if ($userfound || isset($teacherid) || isset($tutorid)) {
	echo $out;
} else {
	echo "You were not found in this group";
}
?>

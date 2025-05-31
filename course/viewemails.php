<?php
require_once "../init.php";

if (!isset($teacherid)) {
	exit;
}

$ids = explode('-',$_GET['ids']);
$idlist = implode(',', array_map('intval', $ids));
$query = "SELECT imas_users.FirstName,imas_users.LastName,imas_users.email ";
$query .= "FROM imas_students JOIN imas_users ON imas_students.userid=imas_users.id WHERE imas_students.courseid=:courseid AND imas_users.id IN ($idlist) ";
$query .= "ORDER BY imas_users.LastName,imas_users.FirstName";
$stm = $DBH->prepare($query);
$stm->execute(array(':courseid'=>$cid));
$stuemails = array();
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	$row[2] = str_replace('BOUNCED', '', $row[2]);
	$name = $row[0] . ' ' . $row[1];
	$stuemails[] = '"'.Sanitize::encodeStringForDisplay(str_replace('"','',$name)) . '" &lt;' . Sanitize::encodeStringForDisplay($row[2]) . '&gt;';
}
$stuemails = implode('; ',$stuemails);
$flexwidth = true;
$nologo = true;
$noskipnavlink = true;
require_once "../header.php";

echo '<textarea id="emails" style="width:470px;height:400px;" aria-label="email list">'.Sanitize::encodeStringForDisplay($stuemails).'</textarea>';
echo '<script type="text/javascript">addLoadEvent(function(){var el=document.getElementById("emails");el.focus();el.select();})</script>';
require_once "../footer.php";
?>

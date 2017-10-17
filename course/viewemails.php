<?php
require("../init.php");

if (!isset($teacherid)) {
	exit;
}

$ids = explode('-',$_GET['ids']);
$idlist = implode(',', array_map('intval', $ids));

//DB $query = "SELECT imas_users.FirstName,imas_users.LastName,imas_users.email ";
//DB $query .= "FROM imas_students JOIN imas_users ON imas_students.userid=imas_users.id WHERE imas_students.courseid='$cid' AND imas_users.id IN ($idlist)";
//DB $query .= "ORDER BY imas_users.LastName,imas_users.FirstName";
//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
$query = "SELECT imas_users.FirstName,imas_users.LastName,imas_users.email ";
$query .= "FROM imas_students JOIN imas_users ON imas_students.userid=imas_users.id WHERE imas_students.courseid=:courseid AND imas_users.id IN ($idlist) ";
$query .= "ORDER BY imas_users.LastName,imas_users.FirstName";
$stm = $DBH->prepare($query);
$stm->execute(array(':courseid'=>$cid));
$stuemails = array();
//DB while ($row = mysql_fetch_row($result)) {
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	$stuemails[] = $row[0].' '.$row[1]. ' &lt;'.$row[2].'&gt;';
}
$stuemails = implode('; ',$stuemails);
$flexwidth = true;
$nologo = true;
require("../header.php");

echo '<textarea id="emails" style="width:470px;height:400px;">'.Sanitize::encodeStringForDisplay($stuemails).'</textarea>';
echo '<script type="text/javascript">addLoadEvent(function(){var el=document.getElementById("emails");el.focus();el.select();})</script>';
require("../footer.php");
?>

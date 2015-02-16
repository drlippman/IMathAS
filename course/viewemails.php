<?php
require("../validate.php");

if (!isset($teacherid)) {
	exit;
}

$ids = explode('-',$_GET['ids']);
foreach ($ids as $k=>$v) {
	$ids[$k] = intval($v);
}
$idlist = implode(',',$ids);

$query = "SELECT imas_users.FirstName,imas_users.LastName,imas_users.email ";
$query .= "FROM imas_students JOIN imas_users ON imas_students.userid=imas_users.id WHERE imas_students.courseid='$cid' AND imas_users.id IN ($idlist)";
$query .= "ORDER BY imas_users.LastName,imas_users.FirstName";
$result = mysql_query($query) or die("Query failed : " . mysql_error());
$stuemails = array();
while ($row = mysql_fetch_row($result)) {
	$stuemails[] = $row[0].' '.$row[1]. ' &lt;'.$row[2].'&gt;';
}
$stuemails = implode('; ',$stuemails);
$flexwidth = true;
$nologo = true;
require("../header.php");

echo '<textarea id="emails" style="width:470px;height:400px;">'.$stuemails.'</textarea>';
echo '<script type="text/javascript">addLoadEvent(function(){var el=document.getElementById("emails");el.focus();el.select();})</script>';
require("../footer.php");
?>

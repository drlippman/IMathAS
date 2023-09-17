<?php

require_once "../init.php";
$stm = $DBH->query("SELECT uniqueid,lastmoddate,extref FROM imas_questionset WHERE extref<>''");
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	$row = array_map('Sanitize::encodeStringForDisplay', $row);
	echo implode('@',$row).'<br/>';
}

?>

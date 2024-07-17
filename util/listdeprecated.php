<?php

require_once "../init.php";
$stm = $DBH->query("SELECT A.uniqueid,B.uniqueid,A.description,B.description FROM imas_questionset AS A JOIN imas_questionset AS B ON A.replaceby=B.id");
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	$row = array_map('Sanitize::encodeStringForDisplay', $row);
	echo implode('@',$row).'<br/>';
}

?>

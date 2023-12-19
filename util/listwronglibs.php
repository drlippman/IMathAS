<?php

require_once "../init.php";

if ($myrights<100) {exit;}
$stm = $DBH->query("SELECT iqs.uniqueid,il.uniqueid FROM imas_questionset AS iqs
  JOIN imas_library_items AS ili ON iqs.id=ili.qsetid AND ili.deleted=0
  JOIN imas_libraries AS il ON ili.libid=il.id AND il.deleted=0 
  WHERE ili.junkflag>0 AND ili.deleted=0");
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	echo Sanitize::encodeStringForDisplay(implode('@',$row)).'<br/>';
}

?>

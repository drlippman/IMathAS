<?php

require("../init.php");

if ($myrights<100) {exit;}

//DB $query = "SELECT iqs.uniqueid,il.uniqueid FROM imas_questionset AS iqs
  //DB JOIN imas_library_items AS ili ON iqs.id=ili.qsetid
  //DB JOIN imas_libraries AS il ON ili.libid=il.id
  //DB WHERE ili.junkflag>0";
//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
//DB while ($row = mysql_fetch_row($result)) {
$stm = $DBH->query("SELECT iqs.uniqueid,il.uniqueid FROM imas_questionset AS iqs
  JOIN imas_library_items AS ili ON iqs.id=ili.qsetid
  JOIN imas_libraries AS il ON ili.libid=il.id
  WHERE ili.junkflag>0");
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	echo implode('@',$row).'<br/>';
}

?>

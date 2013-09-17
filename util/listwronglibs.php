<?php

require("../validate.php");

if ($myrights<100) {exit;}

$query = "SELECT iqs.uniqueid,il.uniqueid FROM imas_questionset AS iqs 
  JOIN imas_library_items AS ili ON iqs.id=ili.qsetid 
  JOIN imas_libraries AS il ON ili.libid=il.id 
  WHERE ili.junkflag>0";
$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
while ($row = mysql_fetch_row($result)) {
	echo implode('@',$row).'<br/>';
}

?>

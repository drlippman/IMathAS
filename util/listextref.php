<?php

require("../init.php");

//DB $query = "SELECT uniqueid,lastmoddate,extref FROM imas_questionset WHERE extref<>''";
//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
//DB while ($row = mysql_fetch_row($result)) {
$stm = $DBH->query("SELECT uniqueid,lastmoddate,extref FROM imas_questionset WHERE extref<>''");
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	echo implode('@',$row).'<br/>';
}

?>

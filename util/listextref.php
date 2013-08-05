<?php

require("../validate.php");

$query = "SELECT uniqueid,lastmoddate,extref FROM imas_questionset WHERE extref<>''";
$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
while ($row = mysql_fetch_row($result)) {
	echo implode('@',$row).'<br/>';
}

?>

<?php

require("../init.php");

//DB $query = "SELECT A.uniqueid,B.uniqueid,A.description,B.description FROM imas_questionset AS A JOIN imas_questionset AS B ON A.replaceby=B.id";
//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
//DB while ($row = mysql_fetch_row($result)) {
$stm = $DBH->query("SELECT A.uniqueid,B.uniqueid,A.description,B.description FROM imas_questionset AS A JOIN imas_questionset AS B ON A.replaceby=B.id");
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
	$row = array_map('Sanitize::encodeStringForDisplay', $row);
	echo implode('@',$row).'<br/>';
}

?>

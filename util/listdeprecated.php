<?php

require("../validate.php");

$query = "SELECT A.uniqueid,B.uniqueid,A.description,B.description FROM imas_questionset AS A JOIN imas_questionset AS B ON A.replaceby=B.id";
$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
while ($row = mysql_fetch_row($result)) {
	echo implode('@',$row).'<br/>';
}

?>


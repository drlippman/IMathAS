<?php

//Add additional indexes
$DBH->beginTransaction();

 $query = "CREATE FULLTEXT INDEX descidx ON imas_questionset(description)";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }


if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ Added fulltext index on questionset description</p>";

return true;

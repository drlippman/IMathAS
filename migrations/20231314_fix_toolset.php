<?php

//change 
$DBH->beginTransaction();

 $query = "UPDATE imas_courses SET toolset = toolset & ~4";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
 
if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ Fixed toolset values</p>";

return true;

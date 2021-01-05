<?php

//Add autoexecuse
$DBH->beginTransaction();

 $query = "ALTER TABLE `imas_exceptions` ADD `timeext` SMALLINT DEFAULT '0'";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

$DBH->commit();

echo "<p style='color: green;'>✓ Added timeext to imas_exceptions</p>";

return true;

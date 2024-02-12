<?php

//change 
$DBH->beginTransaction();

 $query = "ALTER TABLE `imas_ltinonces` MODIFY `nonce` VARCHAR(254),
    ADD INDEX `nonce` (`nonce`)";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
 
if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ Adjusted indexes imas_ltinonces</p>";

return true;

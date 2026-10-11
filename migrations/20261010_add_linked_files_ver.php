<?php

$DBH->beginTransaction();

$query = 'ALTER TABLE imas_linked_files ADD COLUMN `ver` SMALLINT(5) UNSIGNED NOT NULL DEFAULT 0';
$res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

if ($DBH->inTransaction()) { $DBH->commit(); }
echo '<p style="color: green;">✓ add ver to imas_linked_files</p>';

return true;

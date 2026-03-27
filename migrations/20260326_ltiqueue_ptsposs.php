<?php

$DBH->beginTransaction();

// add column to store pts poss on assess so we can use scoreMaximum
$query = 'ALTER TABLE imas_ltiqueue ADD COLUMN `ptsposs` SMALLINT UNSIGNED NOT NULL DEFAULT 1';
$res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

if ($DBH->inTransaction()) { $DBH->commit(); }
echo '<p style="color: green;">✓ add ptsposs to imas_ltiqueue</p>';

return true;

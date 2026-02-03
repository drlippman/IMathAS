<?php

$DBH->beginTransaction();

//intent: 1: don't include in direct msg list, 2: don't include in help msg list
$query = 'ALTER TABLE imas_tutors ADD COLUMN `nomsg` INT UNSIGNED NOT NULL DEFAULT 0';
$res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

if ($DBH->inTransaction()) { $DBH->commit(); }
echo '<p style="color: green;">✓ add nomsg to imas_tutors</p>';

return true;

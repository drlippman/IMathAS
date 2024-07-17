<?php

//Add new imas_questions columns for the new assessment player design
$DBH->beginTransaction();

$query = "ALTER TABLE `imas_questions`
 	MODIFY showhints TINYINT(1) NOT NULL DEFAULT 0";
$res = $DBH->query($query);
if ($res===false) {
	echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
}

if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ Added columns for new assessplayer to imas_questions</p>";

return true;

// REMOVED after deciding not to implement it:
// ADD COLUMN regenpenalty VARCHAR(6) NOT NULL DEFAULT '9999',

<?php

// Fixes an issue with assessment setting migration, where
$DBH->beginTransaction();

$query = "UPDATE `imas_assessments` SET scoresingb='immediately' WHERE ver=2 AND showscores='during' AND submitby='by_question'";
$res = $DBH->query($query);
if ($res===false) {
	echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
}


$query = "UPDATE `imas_assessments` SET scoresingb='after_take' WHERE ver=2 AND showscores='during' AND submitby='by_assessment'";
$res = $DBH->query($query);
if ($res===false) {
	echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
}

if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ Fixed bad assess2 settings</p>";

return true;

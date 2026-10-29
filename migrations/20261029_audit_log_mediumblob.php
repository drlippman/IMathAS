<?php

//Allow larger metadata in teacher audit log; large unenrolls exceeded BLOB's 64KB
$DBH->beginTransaction();

$query = 'ALTER TABLE `imas_teacher_audit_log` MODIFY COLUMN `metadata` MEDIUMBLOB NOT NULL';
$res = $DBH->query($query);
if ($res===false) {
	echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
}

if ($DBH->inTransaction()) { $DBH->commit(); }
echo '<p style="color: green;">✓ changed imas_teacher_audit_log.metadata to MEDIUMBLOB</p>';

return true;

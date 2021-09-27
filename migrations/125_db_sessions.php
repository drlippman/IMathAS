<?php

/*
 * Create a table for storing PHP session data.
 */

$DBH->beginTransaction();

$query = 'CREATE TABLE IF NOT EXISTS `php_sessions` (
	`id` varchar(32) NOT NULL,
	`access` int(10) unsigned DEFAULT NULL,
	`data` text,
	PRIMARY KEY (`id`),
	INDEX (`access`)
) ENGINE = InnoDB ROW_FORMAT=DYNAMIC ';

$res = $DBH->query($query);
if ($res === false) {
	echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
}

if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ Database updated.</p>";

/*
 * Done!
 */

return true;


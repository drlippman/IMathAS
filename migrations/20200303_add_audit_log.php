<?php
// Table for audit log

$DBH->beginTransaction();

//Add tables and columns for new assessment player records
$query = "CREATE TABLE `imas_audit_log` (
	  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	  `userid` INT(10) UNSIGNED NOT NULL,
	  `courseid` INT(10) UNSIGNED NOT NULL,
		`typeid` INT(10) UNSIGNED NUL NULL DEFAULT '0',
		`time` INT (10) UNSIGNED NOT NULL,
		`page` VARCHAR(30) NOT NULL,
		`details` TEXT NOT NULL,
	  INDEX (`courseid`)
	) ENGINE=InnoDB;";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

$DBH->commit();

echo "<p style='color: green;'>✓ table imas_audit_log created.</p>";

return true;

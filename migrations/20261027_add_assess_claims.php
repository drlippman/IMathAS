<?php

$DBH->beginTransaction();

$query = 'CREATE TABLE `imas_assess_claims` (
  `userid` INT(10) UNSIGNED NOT NULL,
  `assessmentid` INT(10) UNSIGNED NOT NULL,
  `sessionid` VARCHAR(128) NOT NULL,
  `lastseen` INT(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`userid`, `assessmentid`)
) ENGINE=InnoDB ROW_FORMAT=DYNAMIC';
$res = $DBH->query($query);
if ($res===false) {
	echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
}

if ($DBH->inTransaction()) { $DBH->commit(); }
echo '<p style="color: green;">✓ created imas_assess_claims</p>';

return true;

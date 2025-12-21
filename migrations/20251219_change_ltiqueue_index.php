<?php

//Add imas_onetime_pw table
$DBH->beginTransaction();

$query = 'ALTER TABLE imas_ltiqueue DROP INDEX `sendon`, 
    DROP INDEX `failures`,
    ADD INDEX `send_fail` (`sendon`, `failures`)';
$res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

$query = 'ALTER TABLE imas_content_track DROP INDEX `userid`';
$res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

$query = 'CREATE FULLTEXT INDEX authoridx ON imas_questionset(author)';
$res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

if ($DBH->inTransaction()) { $DBH->commit(); }
echo '<p style="color: green;">✓ change index on ltiqueue, adjust some other indexes</p>';

return true;

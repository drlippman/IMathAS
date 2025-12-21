<?php

$DBH->beginTransaction();

$query = 'ALTER TABLE imas_questionerrorlog ADD COLUMN `ownerid` INT UNSIGNED NOT NULL DEFAULT "0",
    ADD INDEX `ownerid` (`ownerid`)';
$res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

$query = 'UPDATE imas_questionerrorlog 
    INNER JOIN imas_questionset ON imas_questionerrorlog.qsetid = imas_questionset.id
    SET imas_questionerrorlog.ownerid = imas_questionset.ownerid;';
$res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

if ($DBH->inTransaction()) { $DBH->commit(); }
echo '<p style="color: green;">✓ add ownerid to imas_questionerrorlog</p>';

return true;

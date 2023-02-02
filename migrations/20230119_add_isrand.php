<?php

// Add indices for send zeros query
$DBH->beginTransaction();

$query = 'ALTER TABLE imas_questionset ADD COLUMN isrand TINYINT(1) NOT NULL, ADD INDEX (isrand);';
$res = $DBH->query($query);
if ($res===false) {
    echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
    $DBH->rollBack();
    return false;
}


if ($DBH->inTransaction()) { $DBH->commit(); }
echo '<p>Add questionset isrand</p>';

return true;

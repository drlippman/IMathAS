<?php

// Add indices for send zeros query
$DBH->beginTransaction();

$query = 'ALTER TABLE imas_lti_platforms ADD COLUMN created_by INT(10), ADD INDEX (created_by);';
$res = $DBH->query($query);
if ($res===false) {
    echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
    $DBH->rollBack();
    return false;
}


if ($DBH->inTransaction()) { $DBH->commit(); }
echo '<p>LTI 1.3 add platform created_by</p>';

return true;

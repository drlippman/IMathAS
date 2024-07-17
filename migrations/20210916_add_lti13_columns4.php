<?php

// Add indices for send zeros query
$DBH->beginTransaction();

$query = 'ALTER TABLE imas_lti_platforms ADD COLUMN `auth_server` varchar(2000) NOT NULL;';
$res = $DBH->query($query);
if ($res===false) {
    echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
    $DBH->rollBack();
    return false;
}


if ($DBH->inTransaction()) { $DBH->commit(); }
echo '<p>LTI 1.3 add platform auth_server</p>';

return true;

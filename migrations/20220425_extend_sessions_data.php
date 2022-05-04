<?php

// Add indices for send zeros query
$DBH->beginTransaction();

$query = "ALTER TABLE `php_sessions` MODIFY data MEDIUMTEXT";
$res = $DBH->query($query);
if ($res===false) {
    echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
    $DBH->rollBack();
    return false;
}


if ($DBH->inTransaction()) { $DBH->commit(); }
echo '<p>Session data store changed to mediumtext</p>';

return true;

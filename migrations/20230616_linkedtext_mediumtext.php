<?php

// Add indices for send zeros query
$DBH->beginTransaction();

$query = "ALTER TABLE `imas_linkedtext` MODIFY `text` MEDIUMTEXT";
$res = $DBH->query($query);
if ($res===false) {
    echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
    $DBH->rollBack();
    return false;
}


if ($DBH->inTransaction()) { $DBH->commit(); }
echo '<p>linkedtext text changed to mediumtext</p>';

return true;

<?php

// Add indices for send zeros query
$DBH->beginTransaction();

$query = "ALTER TABLE `imas_livepoll_status`
 ADD COLUMN `curqid` INT(10) NOT NULL DEFAULT '0'";
$res = $DBH->query($query);
if ($res===false) {
    echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
    $DBH->rollBack();
    return false;
}


if ($DBH->inTransaction()) { $DBH->commit(); }
echo '<p>Add livepollstatus curqid</p>';

return true;

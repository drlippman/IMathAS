<?php

// Add indices for send zeros query
$DBH->beginTransaction();

$query = "ALTER TABLE `imas_courses` MODIFY copyrights TINYINT(1) NOT NULL DEFAULT '0'";
$res = $DBH->query($query);
if ($res===false) {
    echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
    $DBH->rollBack();
    return false;
}


if ($DBH->inTransaction()) { $DBH->commit(); }
echo '<p>Change course copyrights to signed</p>';

return true;

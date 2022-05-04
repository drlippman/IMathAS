<?php

// Add indices for send zeros query
$DBH->beginTransaction();

$query = "ALTER TABLE `imas_ltiqueue`
 ADD COLUMN `isstu` TINYINT(1) NOT NULL DEFAULT '1',
 ADD COLUMN `addedon` INT(10) NOT NULL DEFAULT '0'";
$res = $DBH->query($query);
if ($res===false) {
    echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
    $DBH->rollBack();
    return false;
}


if ($DBH->inTransaction()) { $DBH->commit(); }
echo '<p>LTI queue add isstu, addedon</p>';

return true;

<?php

// Add a keyseturl column to imas_ltiqueue to record the hostname
// a user used when queueing a grade return for an LMS.
$DBH->beginTransaction();

$query = "ALTER TABLE `imas_ltiqueue` 
  ADD COLUMN `keyseturl` VARCHAR(256) NULL,
  ALGORITHM=INPLACE,
  LOCK=NONE;";
$res = $DBH->query($query);
if ($res===false) {
    echo "<p>Query failed: ($query) : ".print_r($DBH->errorInfo(),true)."</p>";
    $DBH->rollBack();
    return false;
}


$DBH->commit();
echo '<p>Add keyseturl columnn to imas_ltiqueue.</p>';

return true;

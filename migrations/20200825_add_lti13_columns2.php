<?php

// Add indices for send zeros query
$DBH->beginTransaction();

$query = 'ALTER TABLE imas_lti_platforms ADD COLUMN uniqid CHAR(13), ADD INDEX (uniqid);';
$res = $DBH->query($query);
if ($res===false) {
    echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
    $DBH->rollBack();
    return false;
}


$DBH->commit();
echo '<p>LTI 1.3 add platform uniqid</p>';

return true;

<?php

// Add indices for send zeros query
$DBH->beginTransaction();

$query = 'ALTER TABLE imas_assessments ADD INDEX (submitby);';
$res = $DBH->query($query);
if ($res===false) {
    echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
    $DBH->rollBack();
    return false;
}
$query = 'ALTER TABLE imas_ltiusers ADD INDEX (userid);';
$res = $DBH->query($query);
if ($res===false) {
    echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
    $DBH->rollBack();
    return false;
}
$query = 'ALTER TABLE imas_exceptions ADD INDEX (enddate), ADD INDEX (is_lti)';
$res = $DBH->query($query);
if ($res===false) {
    echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
    $DBH->rollBack();
    return false;
}
$query = "ALTER TABLE imas_courses ";
$query .= "ADD COLUMN ltisendzeros TINYINT UNSIGNED NOT NULL DEFAULT '0',";
$query .= "ADD INDEX (ltisendzeros), ADD INDEX (UIver)";
$res = $DBH->query($query);
if ($res===false) {
    echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
    $DBH->rollBack();
    return false;
}


$DBH->commit();
echo '<p>LTI 1.3 columns for sending zeros created</p>';

return true;

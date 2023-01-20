<?php

// Add indices for send zeros query
$DBH->beginTransaction();

$query = "UPDATE imas_questionset SET isrand=1 WHERE control LIKE '%shuffle(%' OR control REGEXP 'rand[a-zA-Z]*\\\\('";
$res = $DBH->query($query);
if ($res===false) {
    echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
    $DBH->rollBack();
    return false;
}


if ($DBH->inTransaction()) { $DBH->commit(); }
echo '<p>Add set isrand for existing questions</p>';

return true;

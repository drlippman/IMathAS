<?php

// update showhints bitwise field for separation: enable 4 if 2 is enabled now
$DBH->beginTransaction();

$query = "UPDATE imas_assessments SET showhints=(showhints|4) WHERE showhints>1";
$res = $DBH->query($query);
if ($res===false) {
    echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
    $DBH->rollBack();
    return false;
}

$query = "UPDATE imas_questions SET showhints=(showhints|4) WHERE showhints>1";
$res = $DBH->query($query);
if ($res===false) {
    echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
    $DBH->rollBack();
    return false;
}


if ($DBH->inTransaction()) { $DBH->commit(); }
echo '<p>Backwards compatibility fix for showhints value</p>';

return true;

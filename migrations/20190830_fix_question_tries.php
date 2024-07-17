<?php

// modquestion2 was suggesting "0 for unlimited", causing problems.
// this sets any "0" values to "use default" instead.
$DBH->beginTransaction();

$query = "UPDATE `imas_questions` SET attempts=9999 WHERE attempts=0";
$res = $DBH->query($query);
if ($res===false) {
	echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
}

if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ Fixed bad attempts values in imas_questions</p>";

return true;

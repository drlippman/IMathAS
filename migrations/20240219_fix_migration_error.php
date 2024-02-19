<?php

//change 
$DBH->beginTransaction();

 $query = "UPDATE imas_assessments SET scoresingb='after_take' WHERE 
    scoresingb='never' AND submitby='by_assessment' AND showscores='at_end'";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
 
if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ Fixed bad settings from migration error</p>";

return true;

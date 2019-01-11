<?php

//Add additional indexes
$DBH->beginTransaction();

 $query = "CREATE INDEX text_pfx ON imas_linkedtext(text(2))";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }

 $query = "CREATE INDEX courseid ON imas_lti_courses(courseid)";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
$DBH->commit();

echo "<p style='color: green;'>✓ Added linketext text index, LTI courseid index</p>";

return true;

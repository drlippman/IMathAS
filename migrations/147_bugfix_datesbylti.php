<?php

//Add course start and end dates
$DBH->beginTransaction();

 $query = "UPDATE imas_exceptions JOIN imas_assessments ON imas_exceptions.assessmentid = imas_assessments.id SET imas_exceptions.is_lti=0 WHERE imas_assessments.date_by_lti=0";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
 
$DBH->commit();

echo "<p style='color: green;'>✓ Corrected issue with date_by_lti</p>";

return true;

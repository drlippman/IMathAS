<?php

//Add MFA field to imas_users
$DBH->beginTransaction();

$tables = array('imas_students','imas_teachers','imas_tutors','imas_users',
	'imas_groups','imas_courses','imas_ltiusers','imas_lti_courses',
	'imas_assessments','imas_grades','imas_lti_placements');
 
foreach ($tables as $table) {
	// add column
	$query = "ALTER TABLE `$table` ADD COLUMN `created_on` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, ";
	$query .= "ADD COLUMN `updated_on` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
	$res = $DBH->query();
	if ($res===false) {
		echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
		$DBH->rollBack();
		return false;
	}
	// see if created_at already exists
 	$stm = $DBH->query("SHOW COLUMNS FROM `$table` LIKE 'created_at'");
 	if ($stm->rowCount() > 0) {
 		// already exists - copy to new column
 	 	$DBH->query("UPDATE `$table` SET `created_on` = FROM_UNIXTIME(`created_at`)");
 	} 
}
 
$DBH->commit();

echo "<p style='color: green;'>✓ Added created_on and updated_on columns</p>";

return true;

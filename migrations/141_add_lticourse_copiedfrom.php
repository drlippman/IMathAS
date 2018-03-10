<?php

$DBH->beginTransaction();

 $query = "ALTER TABLE `imas_lti_courses` ADD `copiedfrom` INT(10) UNSIGNED NOT NULL DEFAULT '0'";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }


$DBH->commit();

echo "<p style='color: green;'>✓ Added copiedfrom field to imas_lti_courses</p>";

return true;

<?php

//Add jsondata field to imas_users
$DBH->beginTransaction();

 /*
 dates_by_lti:
   0: off - normal dates handling
   1: on - set dates by LTI launch
 */
 $query = "ALTER TABLE  `imas_courses` ADD `dates_by_lti` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0'";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
  /*
 date_by_lti:
   0: off - normal dates handling
   1: date not yet set (acts like avail=0)
   2: date set by teacher - overwrite on later launches
   3: date set by first student - don't overwrite on later launches
 */
 $query = "ALTER TABLE  `imas_assessments` ADD `date_by_lti` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0', ADD INDEX ( `date_by_lti` )";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
 /*
 is_lti:
   0: off - normal exception handling
   1: on - exception will be used even if default date is later
 */
 $query = "ALTER TABLE  `imas_exceptions` ADD `is_lti` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0'";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
 

$DBH->commit();

echo "<p style='color: green;'>✓ Added dates by LTI fields</p>";

return true;

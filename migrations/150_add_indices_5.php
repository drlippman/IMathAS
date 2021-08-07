<?php

//Add additional indexes
$DBH->beginTransaction();

 $query = "ALTER TABLE  `imas_libraries` ADD INDEX ( `groupid` )";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
 
 $query = 'DELETE imas_grades FROM imas_grades JOIN ';
 $query .= "(SELECT min(id) as minid,refid FROM imas_grades WHERE gradetype='forum' AND refid>0 GROUP BY refid having count(id)>1) AS duplic ";
 $query .= "ON imas_grades.refid=duplic.refid AND imas_grades.gradetype='forum' WHERE imas_grades.id > duplic.minid";
 $stm = $DBH->query($query);
	
 $query = 'DELETE imas_grades FROM imas_grades JOIN ';
 $query .= "(SELECT min(id) as minid,gradetypeid,userid FROM imas_grades WHERE gradetype='offline' GROUP BY gradetypeid,userid having count(id)>1) AS duplic ";
 $query .= "ON imas_grades.gradetypeid=duplic.gradetypeid AND imas_grades.userid=duplic.userid AND imas_grades.gradetype='offline' WHERE imas_grades.id > duplic.minid";
 $stm = $DBH->query($query);
 
 $query = 'DELETE imas_grades FROM imas_grades JOIN ';
 $query .= "(SELECT min(id) as minid,gradetypeid,userid FROM imas_grades WHERE gradetype='exttool' GROUP BY gradetypeid,userid having count(id)>1) AS duplic ";
 $query .= "ON imas_grades.gradetypeid=duplic.gradetypeid AND imas_grades.userid=duplic.userid AND imas_grades.gradetype='exttool' WHERE imas_grades.id > duplic.minid";
 $stm = $DBH->query($query);
 
 $query = "ALTER TABLE `imas_grades` ADD UNIQUE `ensureuniq` (`gradetype`, `userid`, `gradetypeid`, `refid`)";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ Added index for library groupid and to prevent duplicate grade records</p>";

return true;

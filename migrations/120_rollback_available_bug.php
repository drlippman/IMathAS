<?php

$DBH->beginTransaction();

//upgrade 118 originally accidentally set courses with available=4 (deleted) 
//to available=2 this undoes that.  No issues running this after the corrected
//118 upgrade

$query = 'UPDATE imas_courses SET available=4 WHERE available=2';
$res = $DBH->query($query);
if ($res===false) {
	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
	 $DBH->rollBack();
	 return false;
}
echo '<p>Rollback of incorrect course available value change</p>';
 
if ($DBH->inTransaction()) { $DBH->commit(); }

return true;

?>

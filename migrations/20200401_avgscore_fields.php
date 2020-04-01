<?php

//Add better avgtime, avgscore columns
$DBH->beginTransaction();

 $query = "ALTER TABLE `imas_questionset` CHANGE avgtime oldavgtime varchar(254)";
 $res = $DBH->query($query);
 if ($res===false) {
 	 echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
	$DBH->rollBack();
	return false;
 }
 $query = "ALTER TABLE `imas_questionset`
  ADD COLUMN `avgn` INT(10) NOT NULL DEFAULT '0',
  ADD COLUMN `avgtime` DOUBLE NOT NULL DEFAULT '0',
  ADD COLUMN `vartime` DOUBLE NOT NULL DEFAULT '0',
  ADD COLUMN `avgscore` DOUBLE NOT NULL DEFAULT '0',
  ADD COLUMN `varscore` DOUBLE NOT NULL DEFAULT '0'";
 $res = $DBH->query($query);
 if ($res===false) {
    echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
 $DBH->rollBack();
 return false;
 }

 $query = "UPDATE imas_questionset SET
   avgtime = SUBSTRING_INDEX(SUBSTRING_INDEX(oldavgtime, ',', 2), ',', -1),
   avgscore = SUBSTRING_INDEX(SUBSTRING_INDEX(oldavgtime, ',', 3), ',', -1),
   avgn = SUBSTRING_INDEX(SUBSTRING_INDEX(oldavgtime, ',', 4), ',', -1),
   vartime = avgtime*avgtime*11,
   varscore = 1111
   WHERE 1";
 $res = $DBH->query($query);
 if ($res===false) {
    echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
 $DBH->rollBack();
 return false;
 }

$DBH->commit();

echo "<p style='color: green;'>✓ Added new avgtime,avgscore columns to imas_questionset</p>";

return true;

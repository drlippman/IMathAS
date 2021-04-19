<?php

//Add better meantime, meanscore columns
$DBH->beginTransaction();

 $query = "ALTER TABLE `imas_questionset`
  ADD COLUMN `meantimen` INT(10) NOT NULL DEFAULT '0',
  ADD COLUMN `meantime` DOUBLE NOT NULL DEFAULT '0',
  ADD COLUMN `vartime` DOUBLE NOT NULL DEFAULT '0',
  ADD COLUMN `meanscoren` INT(10) NOT NULL DEFAULT '0',
  ADD COLUMN `meanscore` DOUBLE NOT NULL DEFAULT '0',
  ADD COLUMN `varscore` DOUBLE NOT NULL DEFAULT '0'";
 $res = $DBH->query($query);
 if ($res===false) {
    echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
 $DBH->rollBack();
 return false;
 }

 $query = "UPDATE imas_questionset SET
   meantime = SUBSTRING_INDEX(SUBSTRING_INDEX(avgtime, ',', 2), ',', -1),
   meanscore = SUBSTRING_INDEX(SUBSTRING_INDEX(avgtime, ',', 3), ',', -1),
   meanscoren = SUBSTRING_INDEX(SUBSTRING_INDEX(avgtime, ',', 4), ',', -1),
   meantimen = SUBSTRING_INDEX(SUBSTRING_INDEX(avgtime, ',', 4), ',', -1),
   vartime = meantime*meantime*11,
   varscore = 1111
   WHERE 1";
 $res = $DBH->query($query);
 if ($res===false) {
    echo "<p>Query failed: ($query) : " . $DBH->errorInfo() . "</p>";
 $DBH->rollBack();
 return false;
 }

 // Drop avgtime later

if ($DBH->inTransaction()) { $DBH->commit(); }

echo "<p style='color: green;'>✓ Added new meantime,meanscore columns to imas_questionset</p>";

return true;

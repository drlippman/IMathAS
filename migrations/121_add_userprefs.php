<?php


$query = 'CREATE TABLE `imas_user_prefs` (
	  `id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	  `item` VARCHAR(31) NOT NULL,
	  `value` VARCHAR(31) NOT NULL,
	  `userid` INT(10) unsigned NOT NULL,
	  INDEX (`userid`)
	) ENGINE = InnoDB ROW_FORMAT=DYNAMIC ;';
$res = $DBH->query($query);
if ($res===false) {
	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
	 $DBH->rollBack();
	 return false;
}
echo '<p>table imas_user_prefs created</p>';
 
$DBH->beginTransaction();

//move usertheme from imas_users to imas_user_prefs.
if (!$isDBsetup) {
	$query = "INSERT INTO imas_user_prefs (item,value,userid) SELECT 'usertheme',theme,id FROM imas_users WHERE theme<>''";
	$res = $DBH->query($query);
	if ($res===false) {
		 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
		 $DBH->rollBack();
		 return false;
	}
	echo '<p>Moved userthemes to user_prefs table</p>';
	echo '<p>This update replaces the Accessibility pulldown on the login page with discrete settings under the user profile. ';
	echo 'You will want to update your login page. See loginpage.php.dist for an example.</p>';
}
if ($DBH->inTransaction()) { $DBH->commit(); }

return true;

?>

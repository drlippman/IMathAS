<?php  
//change counter; increase by 1 each time a change is made
$latest = 1;

if (!empty($dbsetup)) {  //initial setup - just write upgradecounter.txt
	$handle = fopen("upgradecounter.txt",'w');
	fwrite($handle,$latest);
	fclose($handle);	
} else { //doing upgrade
	require("validate.php");
	if ($myrights<100) {
		echo "No rights, aborting";
	}
	
	$handle = @fopen("updatecounter.txt",'r');
	if ($handle===false) {
		$last = 0;
	} else {
		$last = intval(trim(fgets($handle)));
		fclose($handle);
	}
	
	
	if ($last==$latest) {
		echo "No changes to make.";
	} else {
		if ($last < 1) {
			$query = "ALTER TABLE `imas_forums` CHANGE `settings` `settings` TINYINT( 2 ) UNSIGNED NOT NULL DEFAULT '0';";
			mysql_query($query) or die("Query failed : " . mysql_error());
			$query = "ALTER TABLE `imas_forums` ADD `sortby` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0';";
			mysql_query($query) or die("Query failed : " . mysql_error());		
		}
		
		$handle = fopen("upgradecounter.txt",'w');
		fwrite($handle,$latest);
		fclose($handle);
		echo "Upgrades complete";
	}	
}

?>

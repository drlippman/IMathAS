<?php  
//change counter; increase by 1 each time a change is made
$latest = 6;

if (!empty($dbsetup)) {  //initial setup - just write upgradecounter.txt
	$handle = fopen("upgradecounter.txt",'w');
	fwrite($handle,$latest);
	fclose($handle);	
} else { //doing upgrade
	require("validate.php");
	if ($myrights<100) {
		echo "No rights, aborting";
		exit;
	}
	
	$handle = @fopen("upgradecounter.txt",'r');
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
		if ($last < 2) {
			 $query = " ALTER TABLE `imas_gbcats` CHANGE `chop` `chop` DECIMAL( 3, 2 ) UNSIGNED NOT NULL DEFAULT '1'"; 
			 mysql_query($query) or die("Query failed : " . mysql_error());
		}
		if ($last < 3) {
			$sql = 'CREATE TABLE `imas_forum_threads` (`id` INT(10) UNSIGNED NOT NULL, `forumid` INT(10) UNSIGNED NOT NULL, ';
			$sql .= '`lastposttime` INT(10) UNSIGNED NOT NULL, `lastpostuser` INT(10) UNSIGNED NOT NULL, `views` INT(10) UNSIGNED NOT NULL, ';
			$sql .= 'PRIMARY KEY (`id`), INDEX (`forumid`), INDEX(`lastposttime`))  COMMENT = \'Forum threads\'';	
			mysql_query($sql) or die("Query failed : " . mysql_error());
			
			$query = "INSERT INTO imas_forum_threads (id,forumid,lastpostuser,lastposttime) SELECT threadid,forumid,userid,max(postdate) FROM imas_forum_posts GROUP BY threadid";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			
			$query = "UPDATE imas_forum_threads ift, imas_forum_posts ifp SET ift.views=ifp.views WHERE ift.id=ifp.threadid AND ifp.parent=0";
			mysql_query($query) or die("Query failed : " . mysql_error());
			
			$query = "ALTER TABLE `imas_exceptions` ADD `islatepass` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0';";
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
		if ($last < 4) {
			$query = "ALTER TABLE `imas_assessments` ADD `endmsg` TEXT NOT NULL ;";
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
		if ($last < 5) {
			$query = "ALTER TABLE `imas_gbcats` ADD `hidden` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0';";
			mysql_query($query) or die("Query failed : " . mysql_error());
			
			$query = "UPDATE imas_gbscheme SET defaultcat=CONCAT(defaultcat,',0');";
			mysql_query($query) or die("Query failed : " . mysql_error());
			
			$query = "ALTER TABLE `imas_gbscheme` CHANGE `defaultcat` `defaultcat` VARCHAR( 254 ) NOT NULL DEFAULT '0,0,1,0,-1,0'";
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
		if ($last < 6) {
			$query = 'CREATE TABLE `imas_tutors` (`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `userid` INT(10) UNSIGNED NOT NULL, `courseid` INT(10) UNSIGNED NOT NULL, `section` VARCHAR(40) NOT NULL, INDEX (`userid`, `courseid`)) COMMENT = \'course tutors\'';
			mysql_query($query) or die("Query failed : " . mysql_error());
			$query = 'ALTER TABLE `imas_students` CHANGE `section` `section` VARCHAR( 40 ) NULL DEFAULT NULL';
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
		$handle = fopen("upgradecounter.txt",'w');
		fwrite($handle,$latest);
		fclose($handle);
		echo "Upgrades complete";
	}	
}

?>

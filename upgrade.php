<?php
//Database and data storage upgrade script
//Call this script via the web as an admin each time you update the code

require('migrator.php');

//don't use this anymore:  create files in the /migrations/ directory
//old approach: change counter; increase by 1 each time a change is made
$latest_oldstyle = 119;

@set_time_limit(0);
ini_set("max_input_time", "6000");
ini_set("max_execution_time", "6000");
ini_set("memory_limit", "104857600");

if (isset($dbsetup) && $dbsetup==true) {  //initial setup run from setupdb.php

	//create dbscheme entry for DB ver
	//store in the $latest_oldstyle, which matches the version in dbsetup
	$stm = $DBH->prepare("INSERT INTO imas_dbschema (id,ver) VALUES (:id, :ver)");
	$stm->execute(array(':id'=>1, ':ver'=>$latest_oldstyle));
	
	$last = $latest_oldstyle;
	
	//now we'll run the new-style migration stuff
} else {  //called from web or cli - doing upgrade
	$c = file_get_contents("config.php");
	if (strpos($c, '$DBH')===false) {
		echo '<p>The database connection mechanism has been updated to PDO. You will
			need to revise your config.php before continuing to use the system.
			In your existing config.php, remove everything below the line
			<code>//no need to change anything from here on</code> and replace it
			with the following code</p>
<pre>
/* Connecting, selecting database */
// MySQL with PDO_MYSQL
try {
	$DBH = new PDO("mysql:host=$dbserver;dbname=$dbname", $dbusername, $dbpassword);
	$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
	$GLOBALS["DBH"] = $DBH;
} catch(PDOException $e) {
	die("Could not connect to database: " . $e->getMessage());
}
$DBH->query("set session sql_mode=\'\'");

unset($dbserver);
unset($dbusername);
unset($dbpassword);
</pre>
			<p>On a production server, you may wish to set the PDO::ATTR_ERRMODE to PDO::ERRMODE_SILENT to hide database errors from users.</p>
			<p>Run upgrade.php again after making those changes</p>';
			exit;
	}
	$use_local_sessions = true;
	if (php_sapi_name() == 'cli') { //allow direct calling from command line
		$init_skip_csrfp = true;
		require("init_without_validate.php");
	} else {
		$init_skip_csrfp = true;
		require("init.php");
		if ($myrights<100) {
			echo "No rights, aborting";
			exit;
		}
	}

	//DB $query = "SELECT ver FROM imas_dbschema WHERE id=1";
	//DB $result = mysql_query($query);
	$stm = $DBH->query("SELECT ver FROM imas_dbschema WHERE id=1");
	//DB if ($result===false) {
	if ($stm===false || $stm->rowCount()==0) {//for upgrading older versions
		$handle = @fopen("upgradecounter.txt",'r');
		if ($handle===false) {
			$last = 0;
		} else if (isset($_GET['last'])) {
			$last = floatval($_GET['last']);
			fclose($handle);
		} else {
			$last = intval(trim(fgets($handle)));
			fclose($handle);
		}
	} else {
		//DB $last = mysql_result($result,0,0);
		$last = $stm->fetchColumn(0);
	}
}

	$DBH->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
/***
  start older DB update stuff
***/

		if ($last < 1) {
			$query = "ALTER TABLE `imas_forums` CHANGE `settings` `settings` TINYINT( 2 ) UNSIGNED NOT NULL DEFAULT '0';";
			$DBH->query($query);
			$query = "ALTER TABLE `imas_forums` ADD `sortby` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0';";
			$DBH->query($query);
		}
		if ($last < 2) {
			 $query = " ALTER TABLE `imas_gbcats` CHANGE `chop` `chop` DECIMAL( 3, 2 ) UNSIGNED NOT NULL DEFAULT '1'";
			 $DBH->query($query);
		}
		if ($last < 3) {
			$sql = 'CREATE TABLE `imas_forum_threads` (`id` INT(10) UNSIGNED NOT NULL, `forumid` INT(10) UNSIGNED NOT NULL, ';
			$sql .= '`lastposttime` INT(10) UNSIGNED NOT NULL, `lastpostuser` INT(10) UNSIGNED NOT NULL, `views` INT(10) UNSIGNED NOT NULL, ';
			$sql .= 'PRIMARY KEY (`id`), INDEX (`forumid`), INDEX(`lastposttime`))  COMMENT = \'Forum threads\'';
			$DBH->query($sql);

			$query = "INSERT INTO imas_forum_threads (id,forumid,lastpostuser,lastposttime) SELECT threadid,forumid,userid,max(postdate) FROM imas_forum_posts GROUP BY threadid";
			$result = $DBH->query($query);

			$query = "UPDATE imas_forum_threads ift, imas_forum_posts ifp SET ift.views=ifp.views WHERE ift.id=ifp.threadid AND ifp.parent=0";
			$DBH->query($query);

			$query = "ALTER TABLE `imas_exceptions` ADD `islatepass` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0';";
			$DBH->query($query);
		}
		if ($last < 4) {
			$query = "ALTER TABLE `imas_assessments` ADD `endmsg` TEXT NOT NULL ;";
			$DBH->query($query);
		}
		if ($last < 5) {
			$query = "ALTER TABLE `imas_gbcats` ADD `hidden` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0';";
			$DBH->query($query);

			$query = "UPDATE imas_gbscheme SET defaultcat=CONCAT(defaultcat,',0');";
			$DBH->query($query);

			$query = "ALTER TABLE `imas_gbscheme` CHANGE `defaultcat` `defaultcat` VARCHAR( 254 ) NOT NULL DEFAULT '0,0,1,0,-1,0'";
			$DBH->query($query);
		}
		if ($last < 6) {
			//add imas_tutors table
			$query = 'CREATE TABLE `imas_tutors` (`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `userid` INT(10) UNSIGNED NOT NULL, `courseid` INT(10) UNSIGNED NOT NULL, `section` VARCHAR(40) NOT NULL, INDEX (`userid`, `courseid`)) COMMENT = \'course tutors\'';
			$DBH->query($query);
			$query = 'ALTER TABLE `imas_students` CHANGE `section` `section` VARCHAR( 40 ) NULL DEFAULT NULL';
			$DBH->query($query);
		}
		if ($last < 7) {
			//for existing diag, put level2 selector as section
			$query = "SELECT imas_students.id,imas_users.email FROM imas_students JOIN imas_users ON imas_users.id=imas_students.userid AND imas_users.SID LIKE '%~%~%'";
			$stm = $DBH->query($query);
			//DB while ($row = mysql_fetch_row($result)) {
			$stm2 = $DBH->prepare("UPDATE imas_students SET section=:section WHERE id=:id");
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$epts = explode('@',$row[1]);
				//DB $query = "UPDATE imas_students SET section='{$epts[1]}' WHERE id='{$row[0]}'";
				//DB $DBH->query($query);
				$stm2->execute(array(':section'=>$epts[1], ':id'=>$row[0]));
			}
		}
		if ($last < 8) {
			//move existing tutors to new system
			$query = "SELECT u.id,t.id,t.courseid FROM imas_users as u JOIN imas_teachers as t ON u.id=t.userid AND u.rights=15";
			$stm = $DBH->query($query);
			$lastuser = -1;
			//DB while ($row = mysql_fetch_row($result)) {
			$stm2 = $DBH->prepare("UPDATE imas_users SET rights=10 WHERE id=:id");
			$stm3 = $DBH->prepare("DELETE FROM imas_teachers WHERE id=:id");
			$stm4 = $DBH->prepare("INSERT INTO imas_tutors (userid,courseid,section) VALUES (:userid, :courseid, '')");
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				if ($row[0]!=$lastuser) {
					//DB $query = "UPDATE imas_users SET rights=10 WHERE id='{$row[0]}'";
					//DB $DBH->query($query);
					$stm2->execute(array(':id'=>$row[0]));
					$lastuser = $row[0];
				}
				//DB $query = "DELETE FROM imas_teachers WHERE id='{$row[1]}'";
				$stm3->execute(array(':id'=>$row[1]));
				//DB $query = "INSERT INTO imas_tutors (userid,courseid,section) VALUES ('{$row[0]}','{$row[2]}','')";
				$stm4->execute(array(':userid'=>$row[0], ':courseid'=>$row[2]));
			}
		}
		if ($last < 9) {
			//if postback
			if (isset($_POST['diag'])) {
				foreach ($_POST['diag'] as $did=>$uid) {
					//DB $query = "UPDATE imas_diags SET ownerid='$uid' WHERE id='$did'";
					$stm = $DBH->prepare("UPDATE imas_diags SET ownerid=:ownerid WHERE id=:id");
					$stm->execute(array(':ownerid'=>$uid, ':id'=>$did));
				}
			} else {
				//change diag owner to userid from groupid
				$ambig = false;
				$out = '';
				$query = "SELECT id,ownerid,name FROM imas_diags";
				$stm = $DBH->query($query);
				//DB if (mysql_num_rows($result)>0) {
				if ($stm->rowCount()>0) {
					$owners = array();
					$dnames = array();
					//DB while ($row = mysql_fetch_row($result)) {
					while ($row = $stm->fetch(PDO::FETCH_NUM)) {
						$owners[$row[1]][] = $row[0];
						$dnames[$row[0]] = $row[2];
					}
					$ow = array_keys($owners);
					$users = array();
					$stm = $DBH->prepare("SELECT id,LastName,FirstName FROM imas_users WHERE groupid=:groupid AND rights>59 ORDER BY id");
					foreach ($ow as $ogrp) {
						//DB $query = "SELECT id,LastName,FirstName FROM imas_users WHERE groupid='$ogrp' AND rights>59 ORDER BY id";
						$stm->execute(array(':groupid'=>$ogrp));
						//DB if (mysql_num_rows($result)==0) {
						if ($stm->rowCount()==0) {
							echo "Orphaned Diags: ".implode(',',$owners[$ogrp]).'<br/>';
						//DB } else if (mysql_num_rows($result)==1) {
						} else if ($stm->rowCount()==1) {
							//DB $uid = mysql_result($result,0,0);
							$uid = $stm->fetchColumn(0);
							$query = "UPDATE imas_diags SET ownerid=$uid WHERE id IN (".implode(',',$owners[$ogrp]).")";
							$DBH->query($query);
						} else {
							$ops = '';
							//DB while ($row = mysql_fetch_row($result)) {
							while ($row = $stm->fetch(PDO::FETCH_NUM)) {
								$ops .= sprintf('<option value="%d">%s, %s</option>', $row[0],
									Sanitize::encodeStringForDisplay($row[1]), Sanitize::encodeStringForDisplay($row[2]));
							}
							foreach ($owners[$ogrp] as $did) {
								$out .= "Diag <b>".$dnames[$did]."</b> Owner: <select name=\"diag[$did]\">";
								$out .= $ops;
								$out .= "</select><br/>";
							}
							$ambig = true;
						}
					}
					if ($ambig) {
						echo "<form method=\"post\" action=\"upgrade.php\">";
						echo "<p>Converting diagnostic ownership to userid.  Ambiguous situations exist.  Please select the ";
						echo "appropriate owner for each diagnostic</p><p>";
						echo $out;
						echo '</p><input type="submit" value="Submit"/></form>';
						//exit - will continue on postback
						//update counter to 8, so will continue at 9 on submit, but won't redo earlier ones
						$handle = fopen("upgradecounter.txt",'w');
						fwrite($handle,8);
						fclose($handle);
						exit;
					}
				}
			}
		}
		if ($last < 10) {
			$query = "ALTER TABLE `imas_gbitems` ADD `tutoredit` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0';";
			$DBH->query($query);
		}
		if ($last < 11) {
			$query = "ALTER TABLE `imas_assessments` ADD `tutoredit` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0';";
			$DBH->query($query);
			$query = "ALTER TABLE `imas_students` ADD `locked` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0';";
			$DBH->query($query);
		}
		if ($last < 12) {
			$query = "ALTER TABLE `imas_diags` ADD `reentrytime` SMALLINT( 5 ) UNSIGNED NOT NULL DEFAULT '0';";
			$DBH->query($query);
		}
		if ($last < 13) {
			$query = "CREATE TABLE `imas_diag_onetime` (
				`id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`diag` INT( 10 ) UNSIGNED NOT NULL ,
				`time` INT( 10 ) UNSIGNED NOT NULL ,
				`code` VARCHAR( 9 ) NOT NULL ,
				INDEX (`diag`), INDEX(`time`), INDEX(`code`)
				) ENGINE = InnoDB;";
			$DBH->query($query);
		}
		if ($last < 14) {
			$query = "ALTER TABLE `imas_forums` ADD `cntingb` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '1';";
			$DBH->query($query);
		}
		if ($last < 15) {
			$query = "ALTER TABLE `imas_linkedtext` ADD `target` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0';";
			$res = $DBH->query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last < 16) {
			 $query = "ALTER TABLE `imas_forum_posts` CHANGE `points` `points` DECIMAL( 5, 1 ) UNSIGNED NULL DEFAULT NULL";
			 $res = $DBH->query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 echo "<p>SimpleLTI has been deprecated and replaced with BasicLTI.  If you have enablesimplelti in your config.php, change it to enablebasiclti.  ";
			 echo "If you do not have either currently in your config.php and want to allow imathas to act as a BasicLTI producer, add \$enablebasiclti = true to config.php</p>";
		}
		if ($last < 17) {
			 $query = "ALTER TABLE `imas_assessments` ADD `eqnhelper` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0';";
			 $res = $DBH->query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last < 18) {
			 $query = "ALTER TABLE `imas_courses` ADD `newflag` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0';";
			$res = $DBH->query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last < 19) {
			 $query = "ALTER TABLE `imas_students` ADD `timelimitmult` DECIMAL(3,2) UNSIGNED NOT NULL DEFAULT '1.0';";
			 $res = $DBH->query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last < 20) {
			 $query = "ALTER TABLE `imas_assessments` ADD `caltag` CHAR( 2 ) NOT NULL DEFAULT '?R';";
			 $res = $DBH->query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last < 21) {
			 $query = "ALTER TABLE `imas_courses` ADD `showlatepass` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0';";
			 $res = $DBH->query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last < 22) {
			 $query = "ALTER TABLE `imas_assessments` ADD `showtips` TINYINT( 1 ) NOT NULL DEFAULT '1';";
			 $res = $DBH->query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last < 23) {
			$query = 'CREATE TABLE `imas_stugroupset` ('
				. ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
				. ' `courseid` INT(10) UNSIGNED NOT NULL, '
				. ' `name` VARCHAR(254) NOT NULL, '
				. ' INDEX (`courseid`)'
				. ' )'
				. ' ENGINE = InnoDB'
				. ' COMMENT = \'Student Group Sets\';';
			$res = $DBH->query($query);
			if ($res===false) {
				 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			}
			echo '<p>imas_stugroupset created<br/>';

			$query = 'CREATE TABLE `imas_stugroups` ('
				. ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
				. ' `groupsetid` INT(10) UNSIGNED NOT NULL, '
				. ' `name` VARCHAR(254) NOT NULL, '
				. ' INDEX (`groupsetid`)'
				. ' )'
				. ' ENGINE = InnoDB'
				. ' COMMENT = \'Student Groups\';';
			$res = $DBH->query($query);
			if ($res===false) {
				 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			}
			echo 'imas_stugroups created<br/>';

			$query = 'CREATE TABLE `imas_stugroupmembers` ('
				. ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
				. ' `stugroupid` INT(10) UNSIGNED NOT NULL, '
				. ' `userid` INT(10) UNSIGNED NOT NULL, '
				. ' INDEX (`stugroupid`), INDEX (`userid`)'
				. ' )'
				. ' ENGINE = InnoDB'
				. ' COMMENT = \'Student Group Members\';';
			$res = $DBH->query($query);
			if ($res===false) {
				 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			}
			echo 'imas_stugroupmembers created<br/></p>';
			echo '<p>It is now possible to specify a default course theme by setting $defaultcoursetheme = "theme.css"; in config.php</p>';

		}
		if ($last < 24) {
			 $query = "ALTER TABLE `imas_assessments` ADD `groupsetid` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0';";
			 $res = $DBH->query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }

			 $query = "ALTER TABLE `imas_forums` ADD `groupsetid` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0';";
			 $res = $DBH->query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 /*later (once groups are done)
			 $query = "ALTER TABLE `imas_forums` DROP COLUMN `grpaid`";
			 $res = $DBH->query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 */
			 $query = "ALTER TABLE `imas_forum_threads` ADD `stugroupid` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0';";
			 $res = $DBH->query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }

			 $query = "ALTER TABLE `imas_forum_threads` ADD INDEX(`stugroupid`);";
			 $res = $DBH->query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }

		}
		if ($last < 25) {
			 $query = "ALTER TABLE `imas_libraries` ADD `sortorder` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0';";
			 $res = $DBH->query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last < 26) {
			 $query = "ALTER TABLE `imas_users` ADD `homelayout` VARCHAR(32) NOT NULL DEFAULT '|0,1,2||0,1'";
			 $res = $DBH->query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last < 27) {
			$query = "ALTER TABLE `imas_diag_onetime` ADD `goodfor` INT(10) NOT NULL DEFAULT '0'";
			 $res = $DBH->query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last < 28) {
			$sql = 'CREATE TABLE `imas_wikis` ('
				. ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
				. ' `name` VARCHAR(50) NOT NULL, '
				. ' `description` TEXT NOT NULL, '
				. ' `courseid` INT(10) UNSIGNED NOT NULL, '
				. ' `startdate` INT(10) UNSIGNED NOT NULL, '
				. ' `editbydate` INT(10) UNSIGNED NOT NULL, '
				. ' `enddate` INT(10) UNSIGNED NOT NULL, '
				. ' `settings` TINYINT(2) UNSIGNED NOT NULL DEFAULT \'0\', '
				. ' `groupsetid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
				. ' `avail` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'1\','
				. ' INDEX (`courseid`), '
				. ' INDEX(`avail`), INDEX(`startdate`), INDEX(`enddate`), INDEX(`editbydate`) '
				. ' )'
				. ' ENGINE = InnoDB'
				. ' COMMENT = \'Wikis\';';
			 $res = $DBH->query($sql);
			 if ($res===false) {
				 echo "<p>Query failed: ($sql) : ".$DBH->errorInfo()."</p>";
			 }

			$sql = 'CREATE TABLE `imas_wiki_revisions` ('
				. ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
				. ' `wikiid` INT(10) UNSIGNED NOT NULL, '
				. ' `stugroupid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\', '
				. ' `userid` INT(10) UNSIGNED NOT NULL, '
				. ' `time` INT(10) UNSIGNED NOT NULL,'
				. ' `revision` TEXT NOT NULL, '
				. ' INDEX (`wikiid`), INDEX(`stugroupid`), INDEX(`time`) '
				. ' )'
				. ' ENGINE = InnoDB'
				. ' COMMENT = \'Wiki revisions\';';
			 $res = $DBH->query($sql);
			 if ($res===false) {
				 echo "<p>Query failed: ($sql) : ".$DBH->errorInfo()."</p>";
			 }

			$sql = 'CREATE TABLE `imas_wiki_views` ('
				. ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
				. ' `userid` INT(10) UNSIGNED NOT NULL, '
				. ' `wikiid` INT(10) UNSIGNED NOT NULL, '
				. ' `lastview` INT(10) UNSIGNED NOT NULL,'
				 . ' INDEX (`userid`), INDEX(`wikiid`)'
				. ' )'
				. ' ENGINE = InnoDB'
				. ' COMMENT = \'Wiki last viewings\';';
			 $res = $DBH->query($sql);
			 if ($res===false) {
				 echo "<p>Query failed: ($sql) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last<29) {
			//this is a bug fix for a typo in the homelayout default
			$query = 'ALTER TABLE `imas_users` CHANGE `homelayout` `homelayout` VARCHAR( 32 ) NOT NULL DEFAULT \'|0,1,2||0,1\'';
			$res = $DBH->query($query);
			if ($res===false) {
				echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			}
			$query = 'UPDATE `imas_users` SET homelayout = CONCAT(\'|0,1,2\',SUBSTR(homelayout,7))';
			$res = $DBH->query($query);
			if ($res===false) {
				echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			}
		}
		if ($last<30) {
			$query = "ALTER TABLE `imas_assessments` ADD `calrtag` VARCHAR(254) NOT NULL DEFAULT 'R';";
			$res = $DBH->query($query);
			if ($res===false) {
			 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			}
			$query = "UPDATE imas_assessments SET calrtag=substring(caltag,2,1),caltag=substring(caltag,1,1)";
			$res = $DBH->query($query);
			if ($res===false) {
			 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			}
			$query = 'ALTER TABLE `imas_assessments` CHANGE `caltag` `caltag` VARCHAR( 254 ) NOT NULL DEFAULT \'?\'';
			$res = $DBH->query($query);
			if ($res===false) {
			 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			}
			$query = 'ALTER TABLE `imas_inlinetext` CHANGE `caltag` `caltag` VARCHAR( 254 ) NOT NULL DEFAULT \'!\'';
			$res = $DBH->query($query);
			if ($res===false) {
			 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			}
			$query = 'ALTER TABLE `imas_linkedtext` CHANGE `caltag` `caltag` VARCHAR( 254 ) NOT NULL DEFAULT \'!\'';
			$res = $DBH->query($query);
			if ($res===false) {
			 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			}
			$query = 'ALTER TABLE `imas_calitems` CHANGE `tag` `tag` VARCHAR( 254 ) NOT NULL';
			$res = $DBH->query($query);
			if ($res===false) {
			 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			}
		}
		if ($last < 30.5) {
			if (isset($GLOBALS['AWSkey'])) {
				//update files.  Need to update before changinge agroupids so we will know the curs3asid
				//DB $query = "SELECT id,agroupid,lastanswers,bestlastanswers,reviewlastanswers,assessmentid FROM imas_assessment_sessions ";
				//DB $query .= "WHERE lastanswers LIKE '%@FILE:%' OR bestlastanswers LIKE '%@FILE:%' OR reviewlastanswers LIKE '%@FILE:%'";
				//DB $result = mysql_query($query) or die("Query failed : $query:" . $DBH->errorInfo());
				$query = "SELECT id,agroupid,lastanswers,bestlastanswers,reviewlastanswers,assessmentid FROM imas_assessment_sessions ";
				$query .= "WHERE lastanswers LIKE '%@FILE:%' OR bestlastanswers LIKE '%@FILE:%' OR reviewlastanswers LIKE '%@FILE:%'";
				$stm = $DBH->query($query);
				require_once("./includes/filehandler.php");
				$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
				$doneagroups = array();
				$stm2 = $DBH->prepare("UPDATE imas_assessment_sessions SET lastanswers=:lastanswers,bestlastanswers=:bestlastanswers,reviewlastanswers=:reviewlastanswers WHERE id=:id");
				//DB while ($row = mysql_fetch_row($result)) {
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					//set path to aid/asid/  or aid/agroupid/  - won't interefere with random values, and easier to do.
					if ($row[1]==0) {
						$path = $row[5].'/'.$row[0];
						$curs3asid = $row[0];
					} else {
						$path = $row[5].'/'.$row[1];
						$curs3asid = $row[1];
					}
					if ($row[1]==0 || !in_array($row[1],$doneagroups)) {
						preg_match_all('/@FILE:(.*?)@/',$row[2].$row[3].$row[4],$matches);
						$tomove = array_unique($matches[1]);
						foreach ($tomove as $file) {
							if (@$s3->copyObject($GLOBALS['AWSbucket'],"adata/$curs3asid/$file",$GLOBALS['AWSbucket'],"adata/$path/$file")) {
								@$s3->deleteObject($GLOBALS['AWSbucket'],"adata/$curs3asid/$file");
							}
						}
						if ($row[1]>0) {
							$doneagroups[] = $row[1];
						}
					}

					//DB $la = addslashes(preg_replace('/@FILE:/',"@FILE:$path/",$row[2]));
					//DB $bla = addslashes(preg_replace('/@FILE:/',"@FILE:$path/",$row[3]));
					//DB $rla = addslashes(preg_replace('/@FILE:/',"@FILE:$path/",$row[4]));
					$la = preg_replace('/@FILE:/',"@FILE:$path/",$row[2]);
					$bla = preg_replace('/@FILE:/',"@FILE:$path/",$row[3]);
					$rla = preg_replace('/@FILE:/',"@FILE:$path/",$row[4]);
					//DB $query = "UPDATE imas_assessment_sessions SET lastanswers='$la',bestlastanswers='$bla',reviewlastanswers='$rla' WHERE id={$row[0]}";
					//DB $res = mysql_query($query) or die("Query failed : $query:" . $DBH->errorInfo());
					$stm2->execute(array(':lastanswers'=>$la, ':bestlastanswers'=>$bla, ':reviewlastanswers'=>$rla, ':id'=>$row[0]));
				}
				echo 'Done up through s3 file change.  <a href="upgrade.php?last=30.5">Continue</a>';
				exit;
			}
		}
		if ($last < 31) {
			//implement groups changes
			//DB $query = "SELECT courseid,id,name FROM imas_assessments WHERE isgroup>0";
			//DB $result = mysql_query($query) or die("Query failed : $query:" . $DBH->errorInfo());
			$stm = $DBH->query("SELECT courseid,id,name FROM imas_assessments WHERE isgroup>0");
			$assessgrpset = array();
			//DB while ($row = mysql_fetch_row($result)) {
			$stm2 = $DBH->prepare("INSERT INTO imas_stugroupset (courseid,name) VALUES (:courseid, :name)");
			$stm3 = $DBH->prepare("UPDATE imas_assessments SET groupsetid=:groupsetid WHERE id=:id");
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				//DB $query = "INSERT INTO imas_stugroupset (courseid,name) VALUES ('{$row[0]}','Group set for {$row[2]}')";
				//DB $res = mysql_query($query) or die("Query failed : $query:" . $DBH->errorInfo());
				//DB $assessgrpset[$row[1]] = mysql_insert_id();
				$stm2->execute(array(':courseid'=>$row[0], ':name'=>"Group set for $row[2]"));
				$assessgrpset[$row[1]] = $DBH->lastInsertId();
				//DB $query = "UPDATE imas_assessments SET groupsetid={$assessgrpset[$row[1]]} WHERE id={$row[1]}";
				//DB mysql_query($query) or die("Query failed : $query:" . $DBH->errorInfo());
				$stm3->execute(array(':groupsetid'=>$assessgrpset[$row[1]], ':id'=>$row[1]));
			}

			//identify student group relations
			//DB $query = "SELECT userid,id,agroupid,assessmentid FROM imas_assessment_sessions WHERE agroupid>0";
			//DB $result = mysql_query($query) or die("Query failed : $query:" . $DBH->errorInfo());
			$stm = $DBH->query("SELECT userid,id,agroupid,assessmentid FROM imas_assessment_sessions WHERE agroupid>0");
			$agroupusers = array();
			$agroupaids = array();
			//DB while ($row = mysql_fetch_row($result)) {
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				if (!isset($assessgrpset[$row[3]])) { //why would agroupid>0 and not isgroup>0?
					continue;
				}
				if (!isset($agroupusers[$row[2]])) {
					$agroupusers[$row[2]] = array();
				}
				$agroupusers[$row[2]][] = $row[0];
				$agroupaids[$row[2]] = $row[3];
			}

			//create new student groups
			$agroups = array_keys($agroupaids);
			$agroupstugrp = array();
			$userref = array();
			foreach($agroups as $agroup) {
				//DB $query = "INSERT INTO imas_stugroups (groupsetid,name) VALUES (".$assessgrpset[$agroupaids[$agroup]].",'Unnamed group')";
				//DB $result = mysql_query($query) or die("Query failed : $query:" . $DBH->errorInfo());
				//DB $stugrp = mysql_insert_id();
				$stm = $DBH->prepare("INSERT INTO imas_stugroups (groupsetid,name) VALUES (:groupsetid,'Unnamed group')");
				$stm->execute(array(':groupsetid'=>$assessgrpset[$agroupaids[$agroup]]));
				$stugrp = $DBH->lastInsertId();
				if (count($agroupusers[$agroup])>0) {
					foreach ($agroupusers[$agroup] as $k=>$v) {
						$agroupusers[$agroup][$k] = "($stugrp,$v)";
						$userref[$v.'-'.$agroupaids[$agroup]] = $stugrp;  //$userref[userid-aid] = stugrp
					}
					$query = "INSERT INTO imas_stugroupmembers (stugroupid,userid) VALUES ".implode(',',$agroupusers[$agroup]);
					//DB $result = mysql_query($query) or die("Query failed : $query:" . $DBH->errorInfo());
					$DBH->query($query); //is DB INTs - safe
				}
				//DB $query = "UPDATE imas_assessment_sessions SET agroupid='$stugrp' WHERE agroupid='$agroup'";
				//DB $result = mysql_query($query) or die("Query failed : $query:" . $DBH->errorInfo());
				$stm = $DBH->prepare("UPDATE imas_assessment_sessions SET agroupid=:agroupid WHERE agroupid=:agroupid");
				$stm->execute(array(':agroupid'=>$stugrp, ':agroupid'=>$agroup));
			}

			//update forums and forum posts for groups
			$forumaid = array();
			//DB $query = "SELECT id,grpaid FROM imas_forums WHERE grpaid>0";
			//DB $result = mysql_query($query) or die("Query failed : $query:" . $DBH->errorInfo());
			//DB while ($row = mysql_fetch_row($result)) {
			$stm = $DBH->query("SELECT id,grpaid FROM imas_forums WHERE grpaid>0");
			$stm2 = $DBH->prepare("UPDATE imas_forums SET groupsetid=:groupsetid WHERE id=:id");
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$forumaid[$row[0]] = $row[1];
				//DB $query = "UPDATE imas_forums SET groupsetid={$assessgrpset[$row[1]]} WHERE id={$row[0]}";
				//DB mysql_query($query) or die("Query failed : $query:" . $DBH->errorInfo());
				$stm2->execute(array(':groupsetid'=>$assessgrpset[$row[1]], ':id'=>$row[0]));
			}
			if (count($forumaid)>0) {
				$forumlist = implode(',',array_keys($forumaid));
				$query = "SELECT forumid,threadid,userid FROM imas_forum_posts WHERE forumid IN ($forumlist) AND parent=0";
				//DB $result = mysql_query($query) or die("Query failed : $query:" . $DBH->errorInfo());
				$stm = $DBH->query($query); //is DB INTs - safe
				//DB while ($row = mysql_fetch_row($result)) {
				$stm2 = $DBH->prepare("UPDATE imas_forum_threads SET stugroupid=:stugroupid WHERE id=:id");
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					if (!isset($userref[$row[2].'-'.$forumaid[$row[0]]])) {
						continue;
					}
					$stugrp = $userref[$row[2].'-'.$forumaid[$row[0]]];
					//DB $query = "UPDATE imas_forum_threads SET stugroupid=$stugrp WHERE id={$row[1]}";
					//DB mysql_query($query) or die("Query failed : $query:" . $DBH->errorInfo());
					$stm2->execute(array(':stugroupid'=>$stugrp, ':id'=>$row[1]));
				}
			}



		}
		if ($last<32) {
			 $query = "ALTER TABLE `imas_stugroupset` ADD `delempty` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '1';";
			 $res = $DBH->query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }

			 $query = 'ALTER TABLE `imas_forums` CHANGE `name` `name` VARCHAR( 254 ) NOT NULL';
			 $res = $DBH->query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }

			 $query = 'ALTER TABLE `imas_wikis` CHANGE `name` `name` VARCHAR( 254 ) NOT NULL';
			 $res = $DBH->query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }

			 $query = 'ALTER TABLE `imas_gbitems` CHANGE `name` `name` VARCHAR( 254 ) NOT NULL';
			 $res = $DBH->query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last<33) {
			$query = 'ALTER TABLE `imas_questionset` ADD `extref` TEXT NOT NULL DEFAULT \'\'';
			 $res = $DBH->query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last<34) {
			$query = 'ALTER TABLE `imas_assessments` ADD `deffeedbacktext` VARCHAR( 512 ) NOT NULL DEFAULT \'\'';
			 $res = $DBH->query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last<35) {
			$query = 'ALTER TABLE `imas_users` ADD `listperpage` TINYINT(3) UNSIGNED NOT NULL DEFAULT \'20\'';
			 $res = $DBH->query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last<36) {
			$query = 'ALTER TABLE `imas_library_items` ADD `junkflag` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\'';
			 $res = $DBH->query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last<37) {
			$query = 'ALTER TABLE `imas_questionset` ADD `deleted` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\'';
			 $res = $DBH->query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last<38) {
			 $query = 'ALTER TABLE `imas_forums` ADD `caltag` VARCHAR( 254 ) NOT NULL DEFAULT \'FP--FR\'';
			 $res = $DBH->query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last<39) {
			$sql = 'CREATE TABLE `imas_rubrics` ('
				. ' `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '
				. ' `ownerid` INT(10) UNSIGNED NOT NULL, '
				. ' `groupid` INT(10) NOT NULL DEFAULT \'-1\', '
				. ' `name` VARCHAR(254) NOT NULL, '
				. ' `rubrictype` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\', '
				. ' `rubric` TEXT NOT NULL, '
				 . ' INDEX(`ownerid`), INDEX(`groupid`)'
				. ' )'
				. ' ENGINE = InnoDB'
				. ' COMMENT = \'Rubrics\';';
			 $res = $DBH->query($sql);
			 if ($res===false) {
				 echo "<p>Query failed: ($sql) : ".$DBH->errorInfo()."</p>";
			 }
			 $query = 'ALTER TABLE `imas_questions` ADD `rubric` INT(10) UNSIGNED NOT NULL DEFAULT \'0\'';
			 $res = $DBH->query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 $query = 'ALTER TABLE `imas_gbitems` ADD `rubric` INT(10) UNSIGNED NOT NULL DEFAULT \'0\'';
			 $res = $DBH->query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }

		}
		if ($last<40) {
			 $query = 'ALTER TABLE `imas_gbscheme` ADD `stugbmode` TINYINT(2) UNSIGNED NOT NULL DEFAULT \'5\'';
			 $res = $DBH->query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last<41) {
			 $query = 'ALTER TABLE `imas_grades` CHANGE `gbitemid` `gradetypeid` INT(10) NOT NULL';
			 $res = $DBH->query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 $query = 'ALTER TABLE `imas_grades` ADD `refid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\'';
			 $res = $DBH->query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 $query = 'ALTER TABLE `imas_grades` ADD `gradetype` VARCHAR(15) NOT NULL DEFAULT \'offline\'';
			 $res = $DBH->query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 $query = "ALTER TABLE `imas_grades` ADD INDEX(`gradetypeid`);";
			 $res = $DBH->query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			  $query = "ALTER TABLE `imas_grades` ADD INDEX(`gradetype`);";
			 $res = $DBH->query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 $query = "ALTER TABLE `imas_grades` ADD INDEX(`refid`);";
			 $res = $DBH->query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 $query = "SELECT id,forumid,userid,points FROM imas_forum_posts WHERE points IS NOT NULL";
			 $stm = $DBH->query($query);
			 $i = 0;
			 //DB while ($row = mysql_fetch_row($res)) {
			 while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			 	 if ($i%500==0) {
			 	 	 if ($i>0) {
			 	 	 	 //DB mysql_query($ins);
						 $DBH->query($ins);
			 	 	 }
			 	 	 $ins = "INSERT INTO imas_grades (gradetype,gradetypeid,refid,userid,score) VALUES ";
			 	 } else {
			 	 	 $ins .= ",";
			 	 }
			 	 $ins .= "('forum',{$row[1]},{$row[0]},{$row[2]},{$row[3]})"; //is INTs - safe
			 	 $i++;
			 }
			 if ($i>0) {
			 	 //DB mysql_query($ins);
				 $DBH->query($ins);
			 }
		}
		if ($last<42) {
			$query = "ALTER TABLE `imas_questionset` ADD INDEX(`deleted`);";
			 $res = $DBH->query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last<43) {
			$query = 'CREATE TABLE `imas_drillassess` (
				`id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`courseid` INT( 10 ) UNSIGNED NOT NULL ,
				`itemdescr` TEXT NOT NULL ,
				`itemids` TEXT NOT NULL ,
				`scoretype` CHAR( 3 ) NOT NULL ,
				`showtype` TINYINT( 1 ) UNSIGNED NOT NULL ,
				`n` SMALLINT( 5 ) UNSIGNED NOT NULL ,
				`classbests` TEXT NOT NULL ,
				`showtostu` TINYINT( 1 ) UNSIGNED NOT NULL ,
				INDEX ( `courseid` )
				) ENGINE = InnoDB;';
			 $res = $DBH->query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			$query = 'CREATE TABLE `imas_drillassess_sessions` (
				`id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`drillassessid` INT( 10 ) UNSIGNED NOT NULL ,
				`userid` INT( 10 ) UNSIGNED NOT NULL ,
				`curitem` TINYINT( 3 ) NOT NULL ,
				`seed` SMALLINT( 5 ) UNSIGNED NOT NULL ,
				`curscores` TEXT NOT NULL ,
				`starttime` INT( 10 ) UNSIGNED NOT NULL ,
				`scorerec` TEXT NOT NULL ,
				INDEX ( `drillassessid`), INDEX(`userid` )
				) ENGINE = InnoDB;';
			 $res = $DBH->query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last<44) {
			//bug fix for wrong userid being recorded on forum grades
			$query = "SELECT ig.id,ifp.userid FROM imas_grades AS ig JOIN imas_forum_posts AS ifp ";
			$query .= "ON ig.gradetype='forum' AND ig.refid=ifp.id AND ifp.userid<>ig.userid";
			$stm = $DBH->query($query);
			$stm2 = $DBH->prepare("UPDATE imas_grades SET userid=:userid WHERE id=:id");
			//DB while ($row = mysql_fetch_row($res)) {
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				//DB $query = "UPDATE imas_grades SET userid={$row[1]} WHERE id={$row[0]}";
				//DB mysql_query($query);
				$stm2->execute(array(':userid'=>$row[1], ':id'=>$row[0]));
			}
		}
		if ($last < 45) {
			$query = 'ALTER TABLE `imas_assessment_sessions` ADD `timeontask` TEXT NOT NULL';
			$res = $DBH->query($query);
			if ($res===false) {
			  echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			}
			$query = 'CREATE TABLE `imas_login_log` (
				`id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`userid` INT( 10 ) UNSIGNED NOT NULL ,
				`courseid` INT( 10 ) UNSIGNED NOT NULL ,
				`logintime` INT( 10 ) UNSIGNED NOT NULL ,
				 INDEX(`userid` ), INDEX(`courseid`)
				) ENGINE = InnoDB;';
			 $res = $DBH->query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last < 46) {
			$query = 'ALTER TABLE `imas_questionset` ADD `avgtime` SMALLINT(5) UNSIGNED NOT NULL DEFAULT \'0\'';
			$res = $DBH->query($query);
			if ($res===false) {
			  echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			}
		}
		if ($last < 47) {
			$query = 'CREATE TABLE `imas_lti_courses` (
				`id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`org` VARCHAR( 255 ) NOT NULL ,
				`contextid` VARCHAR( 255 ) NOT NULL ,
				`courseid` INT( 10 ) UNSIGNED NOT NULL ,
				 INDEX(`org`,`contextid`)
				) ENGINE = InnoDB;';
			 $res = $DBH->query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			$query = 'CREATE TABLE `imas_lti_placements` (
				`id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`org` VARCHAR( 255 ) NOT NULL ,
				`contextid` VARCHAR( 255 ) NOT NULL ,
				`linkid` VARCHAR( 255 ) NOT NULL ,
				`typeid` INT( 10 ) UNSIGNED NOT NULL ,
				`placementtype` VARCHAR( 10 ) NOT NULL ,
				 INDEX(`org`, `contextid`, `linkid`)
				) ENGINE = InnoDB;';
			 $res = $DBH->query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			$query = 'ALTER TABLE `imas_assessment_sessions` ADD `lti_sourcedid` TEXT NOT NULL';
			$res = $DBH->query($query);
			if ($res===false) {
			  echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			}
		}
		if ($last < 48) {
			 $query = 'ALTER TABLE `imas_ltiusers` CHANGE `org` `org` VARCHAR( 254 ) NOT NULL';
			 $res = $DBH->query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			  $query = 'ALTER TABLE `imas_ltiusers` CHANGE `ltiuserid` `ltiuserid` VARCHAR( 254 ) NOT NULL';
			 $res = $DBH->query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last<49) {
			$query = "ALTER TABLE `imas_login_log` ADD `lastaction` INT(10) UNSIGNED NOT NULL";
			 $res = $DBH->query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last<50) {
			$query = "ALTER TABLE `imas_students` CHANGE `locked` `locked` INT(10) UNSIGNED NOT NULL";
			 $res = $DBH->query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 $query = "ALTER TABLE `imas_gbscheme` ADD `colorize` VARCHAR (20) NOT NULL";
			 $res = $DBH->query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last < 51) {
			$query = 'CREATE TABLE `imas_external_tools` (
				`id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`name` VARCHAR( 255 ) NOT NULL ,
				`url` VARCHAR( 255 ) NOT NULL ,
				`ltikey` VARCHAR( 255 ) NOT NULL ,
				`secret` VARCHAR( 255 ) NOT NULL ,
				`custom` VARCHAR( 255 ) NOT NULL ,
				`privacy` TINYINT( 1 ) UNSIGNED NOT NULL ,
				`courseid` INT( 10 ) UNSIGNED NOT NULL ,
				`groupid` INT( 10 ) UNSIGNED NOT NULL ,
				INDEX ( `url` ), INDEX( `courseid` ), INDEX( `groupid` )
				) ENGINE = InnoDB COMMENT = \'LTI external tools\'';
			$res = $DBH->query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last < 52) {
			 $query = 'ALTER TABLE `imas_assessments` ADD `posttoforum` INT(10) UNSIGNED NOT NULL DEFAULT \'0\'';
			 $res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 $query = 'ALTER TABLE `imas_assessments` ADD `msgtoinstr` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\'';
			 $res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 //grab msg to instr settings and move to asssessments
			  $query = "SELECT id,msgset FROM imas_courses WHERE msgset>9";
			  $res = $DBH->query($query);
			  //DB while ($row = mysql_fetch_row($res)) {
				$stm = $DBH->prepare("UPDATE imas_assessments SET msgtoinstr=1 WHERE courseid=:courseid");
			  while ($row = $res->fetch(PDO::FETCH_NUM)) {
			  	  //DB $query = "UPDATE imas_assessments SET msgtoinstr=1 WHERE courseid={$row[0]}";
			  	  //DB mysql_query($query);
			  	  $stm->execute(array(':courseid'=>$row[0]));
			  }
			  //DB $query = "UPDATE imas_courses SET msgset=msgset-10 WHERE msgset>9";
			  //DB mysql_query($query);
			  $stm = $DBH->query("UPDATE imas_courses SET msgset=msgset-10 WHERE msgset>9");

		}
		if ($last<53) {
			$query = "ALTER TABLE `imas_forums` ADD `forumtype` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0'";
			 $res = $DBH->query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			$query = "ALTER TABLE `imas_forums` ADD `taglist` TEXT NOT NULL";
			 $res = $DBH->query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			$query = "ALTER TABLE `imas_forum_posts` ADD `files` TEXT NOT NULL";
			 $res = $DBH->query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			$query = "ALTER TABLE `imas_forum_posts` ADD `tag` VARCHAR(255) NOT NULL";
			 $res = $DBH->query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			$query = "ALTER TABLE `imas_forum_posts` ADD INDEX(`tag`)";
			 $res = $DBH->query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last<54) {
			$query = "UPDATE `imas_questionset` SET userights=4 WHERE userights=3";
			$res = $DBH->query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last<55) {
			 $query = 'ALTER TABLE `imas_questionset` ADD `broken` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\'';
			 $res = $DBH->query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last<56) {
			 $query = 'ALTER TABLE `imas_wiki_views` ADD `stugroupid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\'';
			 $res = $DBH->query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 $query = "ALTER TABLE `imas_wiki_views` ADD INDEX(`stugroupid`);";
			 $res = $DBH->query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last<57) {
			 $query = 'ALTER TABLE `imas_questions` ADD `showhints` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\'';
			 $res = $DBH->query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last < 58) {
			 $query = 'ALTER TABLE `imas_assessment_sessions` CHANGE `lastanswers` `lastanswers` MEDIUMTEXT NOT NULL';
			 $res = $DBH->query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 $query = 'ALTER TABLE `imas_assessment_sessions` CHANGE `bestlastanswers` `bestlastanswers` MEDIUMTEXT NOT NULL';
			 $res = $DBH->query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 $query = 'ALTER TABLE `imas_assessment_sessions` CHANGE `reviewlastanswers` `reviewlastanswers` MEDIUMTEXT NOT NULL';
			 $res = $DBH->query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 $query = 'ALTER TABLE `imas_courses` CHANGE `itemorder` `itemorder` MEDIUMTEXT NOT NULL';
			 $res = $DBH->query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last < 59) {
			 $query = 'ALTER TABLE `imas_assessments` ADD `istutorial` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\'';
			 $res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last < 60) {
			 $query = 'CREATE TABLE `imas_badgesettings` (
				  `id` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
				  `name` varchar(128) NOT NULL,
				  `badgetext` varchar(254) NOT NULL,
				  `description` varchar(128) NOT NULL,
				  `longdescription` text NOT NULL,
				  `courseid` int(10) unsigned NOT NULL,
				  `requirements` text NOT NULL,
				  INDEX(`courseid`)
				) ENGINE=InnoDB;';
			$res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }

			$query = 'CREATE TABLE `imas_badgerecords` (
				  `id` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
				  `userid` int(10) unsigned NOT NULL,
				  `badgeid` int(10) unsigned NOT NULL,
				  `data` text NOT NULL,
				  INDEX (`userid`), INDEX(`badgeid`)
				) ENGINE=InnoDB;';
			$res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }

		}
		if ($last < 61) {
			 $query = 'ALTER TABLE `imas_assessments` ADD `viddata` TEXT NOT NULL DEFAULT \'\'';
			 $res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last < 62) {
			 $query = 'CREATE TABLE `imas_bookmarks` (
				`id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`courseid` INT( 10 ) UNSIGNED NOT NULL ,
				`userid` INT( 10 ) UNSIGNED NOT NULL ,
				`name` VARCHAR( 128 ) NOT NULL ,
				`value` TEXT NOT NULL ,
				INDEX ( `courseid`) , INDEX( `userid`) , INDEX( `name` )
				) ENGINE = InnoDB';
			$res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last < 63) {
			 $query = 'ALTER TABLE `imas_forums` ADD `rubric` INT( 10 ) UNSIGNED NOT NULL DEFAULT \'0\'';
			 $res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last < 64) {
			 $query = 'ALTER TABLE `imas_courses` ADD `toolset` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT \'0\'';
			 $res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last < 65) {
			$query = 'CREATE TABLE `imas_dbschema` (
				`id` INT( 10 ) UNSIGNED NOT NULL PRIMARY KEY ,
				`ver` SMALLINT( 4 ) UNSIGNED NOT NULL
				) ENGINE = InnoDB';
			$res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 } else {
			 	 //DB $query = "INSERT INTO imas_dbschema (id,ver) VALUES (1,$latest)";
			 	 //DB mysql_query($query) or die ("can't run $query");
			 	 $stm = $DBH->prepare("INSERT INTO imas_dbschema (id,ver) VALUES (:id, :ver)");
			 	 $stm->execute(array(':id'=>1, ':ver'=>65));
			 }
			echo "Moved upgrade counter to database<br/>";
		}
		if ($last < 66) {
			$query = 'CREATE TABLE `imas_log` (
				`id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`time` INT( 10 ) UNSIGNED NOT NULL ,
				`log` TEXT NOT NULL
				) ENGINE = InnoDB';
			$res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			echo "Added imas_log table<br/>";
		}
		if ($last < 67) {
			 $query = 'ALTER TABLE `imas_users` ADD `hasuserimg` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT \'0\'';
			 $res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 $hasimg = array();
			 if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
				require_once("includes/filehandler.php");
				$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
				$arr = $s3->getBucket($GLOBALS['AWSbucket'],"cfiles/");
				if ($arr!=false) {
					foreach ($arr as $k=>$v) {
						if (substr(basename($arr[$k]['name']),0,10)=='userimg_sm') {
							$hasimg[] = intval(substr(basename($arr[$k]['name']),10,-4));
						}
					}
				}
			 } else {
			 	 $curdir = rtrim(dirname(__FILE__), '/\\');
			 	 $galleryPath = "$curdir/course/files";

			 	 if ($handle = @opendir($galleryPath)) {
			 	 	 while (false !== ($file=readdir($handle))) {
			 	 	 	 if ($file != "." && $file != ".." && !is_dir($file)) {
			 	 	 	 	 if (substr(basename($file),0,10)=='userimg_sm') {
			 	 	 	 	 	$hasimg[] = intval(substr(basename($file),10,-4));
			 	 	 	 	 }
			 	 	 	 }
			 	 	 }
			 	 	 closedir($handle);
			 	 }

			 }
			 if (count($hasimg)>0) {
			 	 $haslist = implode(',',$hasimg);
			 	 $query = "UPDATE imas_users SET hasuserimg=1 WHERE id IN ($haslist)"; //is INTs - safe
			 	 //DB mysql_query($query);
				 $DBH->query($query);
			 	 //DB $n = mysql_affected_rows();
				 $n = $DBH->rowCount();
			 }
			 echo "hasuserimg field added, $n user images identified<br/>";
		}
		if ($last < 68) {
			 $query = 'ALTER TABLE `imas_assessments` CHANGE `intro` `intro` MEDIUMTEXT NOT NULL';
			 $res = $DBH->query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 $query = 'INSERT INTO imas_dbschema (id,ver) VALUES (2,100)';
			 $res = $DBH->query($query);
			 echo "changed assessment intro to mediumtext, moved guest acct counter to DB<br/>";
		}
		if ($last < 69) {
			$query = 'CREATE TABLE `imas_forum_likes` (
				`id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`userid` INT(10) UNSIGNED NOT NULL,
				`threadid` INT(10) UNSIGNED NOT NULL,
				`postid` INT(10) UNSIGNED NOT NULL,
				`type` TINYINT(1) UNSIGNED NOT NULL
				) ENGINE = InnoDB';
			$res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			echo "Added imas_forum_likes table<br/>";
		}
		if ($last < 70) {
			 $query = 'ALTER TABLE `imas_assessments`  ADD `ancestors` TEXT NOT NULL DEFAULT \'\'';
			 $res = $DBH->query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 $query = 'ALTER TABLE `imas_courses`  ADD `ancestors` TEXT NOT NULL DEFAULT \'\'';
			 $res = $DBH->query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 echo "Added ancestor tracking, assessments and courses<br/>";
			 $query = 'CREATE TABLE `imas_content_track` (
				`id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`userid` INT(10) UNSIGNED NOT NULL,
				`courseid` INT(10) UNSIGNED NOT NULL,
				`type` VARCHAR(254) NOT NULL,
				`typeid` INT(10) UNSIGNED NOT NULL,
				`viewtime` INT(10) UNSIGNED NOT NULL,
				`info` VARCHAR(254) NOT NULL,
				INDEX ( `courseid`) , INDEX( `userid`)
				) ENGINE = InnoDB';
			$res = $DBH->query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 } else {
			 	 echo "Added imas_content_track table.<br/>";
			 }

			$query = 'ALTER TABLE `imas_courses`  ADD `istemplate` TINYINT(1) NOT NULL DEFAULT \'0\'';
			 $res = $DBH->query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 //1: global template.  2: group template.  4: self-enroll course.  8: guest temp access
			 if (isset($templateuser)) {
			 	 $query = "UPDATE imas_courses SET istemplate=(istemplate | 1) WHERE id IN (SELECT courseid FROM imas_teachers WHERE userid=$templateuser)";
			 	 $res = $DBH->query($query);
			 }
			 if (isset($CFG['GEN']['selfenrolluser'])) {
			 	 $query = "UPDATE imas_courses SET istemplate=(istemplate | 4) WHERE id IN (SELECT courseid FROM imas_teachers WHERE userid={$CFG['GEN']['selfenrolluser']})";
			 	 $res = $DBH->query($query);
			 }
			  if (isset($CFG['GEN']['guesttempaccts']) && count($CFG['GEN']['guesttempaccts'])>0) {
			  	 $query = "UPDATE imas_courses SET istemplate=(istemplate | 8) WHERE id IN (".implode(',',$CFG['GEN']['guesttempaccts']).")";
			 	 $res = $DBH->query($query);
			  }
		}
		if ($last < 71) {
			 $query = 'ALTER TABLE `imas_courses`  ADD `outcomes` TEXT NOT NULL DEFAULT \'\'';
			 $res = $DBH->query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }

			 $query = 'CREATE TABLE `imas_outcomes` (
				`id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`courseid` INT(10) UNSIGNED NOT NULL,
				`name` VARCHAR(255) NOT NULL,
				`ancestors` TEXT NOT NULL,
				INDEX ( `courseid`)
				) ENGINE = InnoDB';
			 $res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }

			 echo "Added imas_outcomes table<br/>";

			 //replace library category # with library name
			 $query = 'UPDATE imas_questions AS iq, imas_libraries AS il SET iq.category=il.name WHERE iq.category=il.id AND il.name IS NOT NULL';
			 $DBH->query($query);

			 $query = 'ALTER TABLE `imas_assessments`  ADD `defoutcome` INT(10) UNSIGNED NOT NULL DEFAULT \'0\'';
			 $res = $DBH->query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 $query = 'ALTER TABLE `imas_linkedtext`  ADD `outcomes` TEXT NOT NULL';
			 $res = $DBH->query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 $query = 'ALTER TABLE `imas_forums`  ADD `outcomes`  TEXT NOT NULL';
			 $res = $DBH->query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 $query = 'ALTER TABLE `imas_gbitems`  ADD `outcomes`  TEXT NOT NULL';
			 $res = $DBH->query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }

			//add indexes to forum_likes
			 $query = "ALTER TABLE `imas_forum_likes` ADD INDEX(`userid`);";
			 $res = $DBH->query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 $query = "ALTER TABLE `imas_forum_likes` ADD INDEX(`postid`);";
			 $res = $DBH->query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 $query = "ALTER TABLE `imas_forum_likes` ADD INDEX(`threadid`);";
			 $res = $DBH->query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }

		}
		if ($last<72) {
			 $query = 'ALTER TABLE `imas_inlinetext`  ADD `outcomes` TEXT NOT NULL';
			 $res = $DBH->query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last<73) {
			 $query = 'ALTER TABLE `imas_students`  ADD `hidefromcourselist` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\'';
			 $res = $DBH->query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last<74) {
			$query = 'CREATE TABLE `imas_firstscores` (
				`id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`courseid` INT(10) UNSIGNED NOT NULL,
				`qsetid` INT(10) UNSIGNED NOT NULL,
				`score` TINYINT(3) UNSIGNED NOT NULL,
				`scoredet` TEXT NOT NULL,
				`timespent` SMALLINT(5) UNSIGNED NOT NULL,
				INDEX ( `courseid`), INDEX(`qsetid`)
				) ENGINE = InnoDB';
			 $res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 } else {
			 	 echo "Added imas_firstscores</br>";
			 }
			 $query = 'ALTER TABLE `imas_students`  ADD `stutype` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\'';
			 $res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 $query = 'ALTER TABLE `imas_students`  ADD `custominfo` TEXT NOT NULL DEFAULT \'\'';
			 $res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 $query = 'ALTER TABLE `imas_groups`  ADD `grouptype` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\'';
			 $res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last<75) {
			 $query = 'ALTER TABLE `imas_questionset`  ADD `replaceby` INT(10) UNSIGNED NOT NULL DEFAULT \'0\'';
			 $res = $DBH->query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last<76) {
			$query = 'ALTER TABLE `imas_questions`  ADD `extracredit` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\'';
			 $res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 $query = 'ALTER TABLE `imas_courses`  ADD `deflatepass` TINYINT(2) UNSIGNED NOT NULL DEFAULT \'0\'';
			 $res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 $query = 'ALTER TABLE `imas_courses`  ADD `deftime` SMALLINT(4) UNSIGNED NOT NULL DEFAULT \'600\'';
			 $res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last<77) {
			$query = 'ALTER TABLE imas_assessment_sessions add unique index(userid, assessmentid)';
			 $res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last<78) {
			//fix for firstscore bug
			$query = 'UPDATE imas_firstscores SET score=100 WHERE score>0';
			$res = $DBH->query($query);
			$query = 'UPDATE imas_firstscores SET score=100*scoredet WHERE scoredet NOT LIKE \'%~%\'';
			$res = $DBH->query($query);
			$query = "UPDATE imas_firstscores SET score=round(100*length(replace(replace(scoredet,'~',''),'0',''))/length(replace(scoredet,'~',''))) ";
			$query .= "WHERE scoredet LIKE '%~%' AND scoredet LIKE '%0%' AND scoredet LIKE '%1%' AND scoredet NOT LIKE '%.%'";
			$res = $DBH->query($query);
		}
		if ($last<79) {
			$query = 'ALTER TABLE imas_inlinetext ADD `isplaylist` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\'';
			 $res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last<80) {
			$query = 'ALTER TABLE imas_sessions ADD `tzname` VARCHAR(255) NOT NULL DEFAULT \'\'';
			 $res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 echo "<p><b>Important</b>: This update adds improved timezone handling, fixing issues that ";
			 echo "were occurring on servers set for UTC. Utilizing the improvements requires PHP 5.1 or higher ";
			 echo "and requires updating your local loginpage.php file. See changes to loginpage.php.dist; changes are ";
			 echo "on lines 9, 69, and 75-76.</p>";
		}
		if ($last<81) {
			$query = 'ALTER TABLE imas_users ADD `hideonpostswidget` TEXT NOT NULL';
			 $res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last<82) {
			 $query = 'ALTER TABLE imas_questionset ADD `solution` TEXT NOT NULL';
			 $res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last<83) {
			 $query = 'ALTER TABLE imas_questionset ADD `solutionopts` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\'';
			 $res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last<84) {
			 $query = 'ALTER TABLE `imas_users` CHANGE `password` `password` VARCHAR(254) NOT NULL';
			 $res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 $hash = '$2y$04$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';
			 $test = crypt("password", $hash);
			 $pass = ($test == $hash);
			 echo '<p>This update adds more secure password hashing, but must be manually enabled, since it requires loginpage changes.  To enable it:</p> <ul>';
			 if (!$pass) {
			 	 echo '<li>Upgrade to PHP 5.3.8 or later</li>';
			 }
			 echo '<li>Update your loginpage.php.  Compare to loginpage.php.dist. Note: <ul>';
			 echo   '<li>Remove md5.js script load</li>';
			 echo   '<li>Remove onsubmit from form</li>';
			 echo   '<li>Change id="passwordentry" to name="password" in the password entry box</li>';
			 echo   '<li>Remove the hidden password input</li></ul></li>';
			 echo '<li>Update your newinstructor.php.  Compare to newinstructor.php.dist</li>';
			 echo '<li>Add to your config.php: <ul>';
			 echo   '<li>If you want a nondisruptive transition: $CFG[\'GEN\'][\'newpasswords\'] = "transition";</li>';
			 echo   '<li>If you want to invalidate all current passwords: $CFG[\'GEN\'][\'newpasswords\'] = "only";</li></ul></li>';
			 echo '<p>Note: Enabling this change also means that passwords not be hashed client-side anymore, which means that if you are not ';
			 echo   'using TLS/SSL, then passwords are being sent in plaintext.  That is bad.  Get SSL - it is the only way to protect both passwords and student data.</p>';
		}
		if ($last<85) {
			 if (isset($CFG['GEN']['deflicense'])) {
			 	 $license = intval($CFG['GEN']['deflicense']);
			 } else {
			 	 $license = 1;
			 }
			 $query = 'ALTER TABLE imas_questionset ADD `license` TINYINT(1) UNSIGNED NOT NULL DEFAULT \''.$license.'\'';
			 $res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }

			 $query = 'ALTER TABLE  `imas_questionset` CHANGE  `author`  `author` TEXT NOT NULL';
			 $res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 $query = 'ALTER TABLE imas_questionset ADD `ancestorauthors` TEXT NOT NULL';
			 $res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last<86) {
			$query = 'ALTER TABLE imas_questionset ADD `importuid` VARCHAR(254) NOT NULL DEFAULT \'\'';
			 $res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 $query = 'ALTER TABLE imas_questionset ADD `otherattribution` TEXT NOT NULL';
			 $res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			$query = "SELECT id,ancestors,author FROM imas_questionset WHERE ancestors<>''";
			$res = $DBH->query($query);
			if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			}
			//DB while ($row = mysql_fetch_row($res)) {
			$stm3 = $DBH->prepare("UPDATE imas_questionset SET ancestorauthors=:authors WHERE id=:id");
			while ($row = $res->fetch(PDO::FETCH_NUM)) {
				//DB $query = "SELECT author FROM imas_questionset WHERE id IN ({$row[1]})";
				//DB $res2 = mysql_query($query);
				$stm2 = $DBH->query("SELECT author FROM imas_questionset WHERE id IN ({$row[1]})");
				$thisauthor = array();
				//DB while ($r = mysql_fetch_row($res2)) {
				while ($r = $stm2->fetch(PDO::FETCH_NUM)) {
					if ($r[0] != $row[2] && !in_array($r[0],$thisauthor)) {
						$thisauthor[] = $r[0];
					}
				}
				//DB $query = "UPDATE imas_questionset SET ancestorauthors='".addslashes(implode('; ',$thisauthor))."' WHERE id='{$row[0]}'";
				//DB mysql_query($query);
				$stm3->execute(array(':id'=>$row[0], ':authors'=>implode('; ',$thisauthor)));
			}

		}
		if ($last<87) {
			$query = 'ALTER TABLE imas_gbcats ADD `calctype` TINYINT(1) NOT NULL DEFAULT \'0\'';
			$res = $DBH->query($query);
			if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			}
			$query = 'UPDATE imas_gbcats SET calctype=1 WHERE dropn<>0';
			$res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last<88) {
			$query = "ALTER TABLE `imas_forums` ADD `tutoredit` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0';";
			$res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last<89) {
			$query = "ALTER TABLE `imas_linkedtext` ADD `points` SMALLINT( 4 ) UNSIGNED NOT NULL DEFAULT '0';";
			$res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last<90) {
			$query = "ALTER TABLE  `imas_students` CHANGE  `code`  `code` VARCHAR( 32 ) NULL DEFAULT NULL";
			$res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last<91) {
			$query = 'UPDATE imas_gbcats SET calctype=1 WHERE dropn<>0';
			$res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last<92) {
			$query = "ALTER TABLE  `imas_questionset` CHANGE  `avgtime`  `avgtime` VARCHAR( 254 ) NOT NULL DEFAULT '0'";
			$res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last<93) {
			$query = "ALTER TABLE  `imas_dbschema` CHANGE  `ver`  `ver` INT(10) NOT NULL";
			$res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 echo '<p>Question usage data (average time per attempt, avg first score) is now pre-computed. ';
			 echo 'Run "Update question usage data" from the Admin utilities menu to update (at least once a term ';
			 echo 'is recommended)</p>';
		}
		if ($last<94) {
			$query = "ALTER TABLE `imas_exceptions` ADD `waivereqscore` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0';";
			$res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 $query = "ALTER TABLE `imas_assessments` ADD INDEX (ancestors(10)) ";
			 $res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }

		}
		if ($last<95) {
			$query = "ALTER TABLE  `imas_courses` CHANGE  `deftime`  `deftime` INT(10) NOT NULL";
			$res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last<96) {
			$query = "ALTER TABLE  `imas_students` ADD INDEX ( `locked` )";
			 $res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 $query = "ALTER TABLE  `imas_content_track` ADD INDEX ( `typeid` )";
			 $res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last<97) {
			echo "<p>Use of AsciiMathML for display has been removed from the system, and AsciiMath rendering via MathJax ";
			echo "is now the default math display option.  It is recommended you compare your loginpage.php against ";
			echo "loginpage.php.dist, and update the accessibility options.</p>";
		}
		if ($last<98) {
			$query = "ALTER TABLE `imas_users` ADD `theme` VARCHAR( 32 ) NOT NULL DEFAULT '';";
			$res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last<99) {
			$query = "ALTER TABLE `imas_courses` ADD `termsurl` VARCHAR( 254 ) NOT NULL DEFAULT '';";
			$res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last<100) {
			$query = "ALTER TABLE `imas_forums` ADD `postinstr` TEXT NOT NULL";
			$res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 $query = "ALTER TABLE `imas_forums` ADD `replyinstr` TEXT NOT NULL";
			$res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last<101) {
			$query = "ALTER TABLE  `imas_assessments` CHANGE  `reqscore`  `reqscore` SMALLINT(4) NOT NULL DEFAULT '0';";
			$res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last<102) {
			$query = 'ALTER TABLE `imas_exceptions`  ADD `exceptionpenalty` TINYINT(1) UNSIGNED NULL DEFAULT NULL';
			 $res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last<103) {
			$query = "ALTER TABLE  `imas_groups` ADD  `parent` INT(10) UNSIGNED NOT NULL DEFAULT '0';";
			$res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last<104) {
			$query = "ALTER TABLE  `imas_drillassess` ADD  `name` VARCHAR(254) NOT NULL;";
			 $res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 $query = "ALTER TABLE  `imas_drillassess` ADD  `summary` TEXT NOT NULL;";
			 $res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			$query = "ALTER TABLE  `imas_drillassess` ADD  `startdate` INT(10) UNSIGNED NOT NULL;";
			$res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 $query = "ALTER TABLE  `imas_drillassess` ADD  `enddate` INT(10) UNSIGNED NOT NULL;";
			 $res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 $query = "ALTER TABLE  `imas_drillassess` ADD  `avail` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1';";
			 $res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 $query = "ALTER TABLE  `imas_drillassess` ADD  `caltag` VARCHAR(254) NOT NULL DEFAULT 'D';";
			 $res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			  $query = "ALTER TABLE `imas_drillassess` ADD INDEX(`startdate`);";
			 $res = $DBH->query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			  $query = "ALTER TABLE `imas_drillassess` ADD INDEX(`enddate`);";
			 $res = $DBH->query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			  $query = "ALTER TABLE `imas_drillassess` ADD INDEX(`avail`);";
			 $res = $DBH->query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last<105) {
			 $query = "ALTER TABLE  `imas_ltiusers` ADD INDEX ( `ltiuserid` )";
			 $res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 $query = "ALTER TABLE  `imas_courses` ADD INDEX ( `istemplate` )";
			 $res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 $query = "ALTER TABLE  `imas_msgs` ADD INDEX ( `courseid` )";
			 $res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 $query = "ALTER TABLE  `imas_users` ADD INDEX ( `groupid` )";
			 $res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 $query = "ALTER TABLE  `imas_forum_views` ADD INDEX ( `lastview` )";
			 $res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 $query = "ALTER TABLE  `imas_questionset` ADD INDEX ( `replaceby` )";
			 $res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }

		}
		if ($last<106) {
			$query = "ALTER TABLE  `imas_users` ADD  `specialrights` SMALLINT(5) UNSIGNED NOT NULL DEFAULT '0';";
			$res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last<107) {
			$query = "UPDATE imas_users SET specialrights=4 WHERE rights=60";
			$res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			$query = "UPDATE imas_users SET specialrights=13 WHERE rights=75";
			$res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 $query = "UPDATE imas_users SET specialrights=15 WHERE rights=100";
			 $res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 $query = "UPDATE imas_users SET rights=40 WHERE rights=60";
			 $res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last<108) {
			$query = "ALTER TABLE  `imas_questions` CHANGE  `showans`  `showans` CHAR(1) NOT NULL DEFAULT '0';";
			$res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last<109) {
			$query = 'CREATE TABLE `imas_livepoll_status` (
				  `assessmentid` INT(10) unsigned NOT NULL PRIMARY KEY,
				  `curquestion` TINYINT(2) unsigned NOT NULL,
				  `curstate` TINYINT(1) unsigned NOT NULL,
				  `seed` INT(10) unsigned NOT NULL,
				  `startt` BIGINT(13) unsigned NOT NULL
				) ENGINE=InnoDB;';
			$res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 echo '<p>table imas_livepoll_status created</p>';
		}
		if ($last<110) {
			 $query = "ALTER TABLE  `imas_assessment_sessions` ADD INDEX ( `endtime` )";
			 $res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
		if ($last<111) {
			$query = "ALTER TABLE  `imas_exceptions` ADD `itemtype` CHAR(1) NOT NULL DEFAULT 'A';";
			$res = $DBH->query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 $query = "ALTER TABLE  `imas_exceptions` ADD INDEX ( `itemtype` )";
			 $res = $DBH->query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			$query = "ALTER TABLE  `imas_forums` ADD `allowlate` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0';";
			$res = $DBH->query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
			 echo '<p>Added forum latepass columns</p>';
		}
		if ($last<112) {
			//fix residual database f-up from bltilaunch copy of template not inserting imas_gbscheme record
			$res = $DBH->query('INSERT INTO imas_gbscheme (courseid) SELECT ic.id FROM imas_courses AS ic LEFT JOIN imas_gbscheme ON ic.id=imas_gbscheme.courseid WHERE imas_gbscheme.id IS NULL');
			if ($res===false) {
				echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			}
		}
		if ($last<113) {
			$query = "ALTER TABLE  `imas_assessment_sessions` ADD `ver` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0';";
			$res = $DBH->query($query);
			if ($res===false) {
				echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			}
			echo '<p>Added assessment sessions ver</p>';
		}
		if ($last<114) {
			$query = "ALTER TABLE  `imas_sessions` ADD INDEX ( `userid` )";
			$res = $DBH->query($query);
			if ($res===false) {
				echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			}
		}
		if ($last<115) {
			$query = "ALTER TABLE  `imas_users` ADD `FCMtoken` VARCHAR(512) NOT NULL DEFAULT '';";
			$res = $DBH->query($query);
			if ($res===false) {
				echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			}
			echo '<p>Added FCMtoken column</p>';
		}
		if ($last<116) {
			echo '<p>Custom themes will need updating if they are currently using activewrapper to show/hide instructor controls.  Add this code to your theme:<br/>';
			echo '<code>
div.item:hover span.instronly, div.block:hover span.instronly {
  visibility: visible;
}
span.instronly {
  visibility: hidden;
}
			</code></p>';
		}
		if ($last<117) {
			//rewrite way imas_courses.available works
			$query = "ALTER TABLE `imas_teachers` ADD `hidefromcourselist` TINYINT(1) NOT NULL DEFAULT '0';";
			$res = $DBH->query($query);
			if ($res===false) {
				echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			}
			$query = "ALTER TABLE `imas_tutors` ADD `hidefromcourselist` TINYINT(1) NOT NULL DEFAULT '0';";
			$res = $DBH->query($query);
			if ($res===false) {
				echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			}
		}
		if ($last<118) {
			$query = "UPDATE imas_teachers SET hidefromcourselist=1 WHERE courseid IN ";
			$query .= "(SELECT id FROM imas_courses WHERE available>1)";
			$res = $DBH->query($query);
			
			$query = "UPDATE imas_courses SET available=available-2 WHERE available>1 AND available<4";
			$res = $DBH->query($query);	
		}
		if ($last<119) {
			$query = "ALTER TABLE  `imas_questions` ADD `fixedseeds` TEXT NULL DEFAULT NULL;";
			$res = $DBH->query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".$DBH->errorInfo()."</p>";
			 }
		}
	
/***
  end older DB update stuff
***/
	if ($last<119) { 
		//if we just ran any of those changes, update DB, otherwise
		//let Migrator handle updating the ver
		$stm = $DBH->prepare("UPDATE imas_dbschema SET ver=:ver WHERE id=1");
		$stm->execute(array(':ver'=>$latest_oldstyle));
	}
		
	$migrator = new Migrator($DBH, (isset($dbsetup) && $dbsetup==true));
	$migrator->migrateAll();

	echo "Migrations complete";
	

?>

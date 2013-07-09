<?php  
//change counter; increase by 1 each time a change is made
//TODO:  change linked text tex to mediumtext
$latest = 73;


@set_time_limit(0);
ini_set("max_input_time", "600");
ini_set("max_execution_time", "600");
ini_set("memory_limit", "104857600");

if (!empty($dbsetup)) {  //initial setup - just write upgradecounter.txt
	$query = "INSERT INTO imas_dbschema (id,ver) VALUES (1,$latest)";
	mysql_query($query);
	//$handle = fopen("upgradecounter.txt",'w');
	//fwrite($handle,$latest);
	//fclose($handle);	
} else { //doing upgrade
	require("validate.php");
	if ($myrights<100) {
		echo "No rights, aborting";
		exit;
	}
	$query = "SELECT ver FROM imas_dbschema WHERE id=1";
	$result = mysql_query($query);
	if ($result===false) { //for upgrading older versions
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
		$last = mysql_result($result,0,0);
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
			//add imas_tutors table
			$query = 'CREATE TABLE `imas_tutors` (`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `userid` INT(10) UNSIGNED NOT NULL, `courseid` INT(10) UNSIGNED NOT NULL, `section` VARCHAR(40) NOT NULL, INDEX (`userid`, `courseid`)) COMMENT = \'course tutors\'';
			mysql_query($query) or die("Query failed : " . mysql_error());
			$query = 'ALTER TABLE `imas_students` CHANGE `section` `section` VARCHAR( 40 ) NULL DEFAULT NULL';
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
		if ($last < 7) {
			//for existing diag, put level2 selector as section
			$query = "SELECT imas_students.id,imas_users.email FROM imas_students JOIN imas_users ON imas_users.id=imas_students.userid AND imas_users.SID LIKE '%~%~%'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				$epts = explode('@',$row[1]);
				$query = "UPDATE imas_students SET section='{$epts[1]}' WHERE id='{$row[0]}'";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
		}
		if ($last < 8) {
			//move existing tutors to new system
			$query = "SELECT u.id,t.id,t.courseid FROM imas_users as u JOIN imas_teachers as t ON u.id=t.userid AND u.rights=15";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$lastuser = -1;
			while ($row = mysql_fetch_row($result)) {
				if ($row[0]!=$lastuser) {
					$query = "UPDATE imas_users SET rights=10 WHERE id='{$row[0]}'";
					mysql_query($query) or die("Query failed : " . mysql_error());
					$lastuser = $row[0];
				}
				$query = "DELETE FROM imas_teachers WHERE id='{$row[1]}'";
				mysql_query($query) or die("Query failed : " . mysql_error());
				$query = "INSERT INTO imas_tutors (userid,courseid,section) VALUES ('{$row[0]}','{$row[2]}','')";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
		}
		if ($last < 9) {
			//if postback
			if (isset($_POST['diag'])) {
				foreach ($_POST['diag'] as $did=>$uid) {
					$query = "UPDATE imas_diags SET ownerid='$uid' WHERE id='$did'";
					mysql_query($query) or die("Query failed : " . mysql_error());
				}
			} else {
				//change diag owner to userid from groupid
				$ambig = false;
				$out = '';
				$query = "SELECT id,ownerid,name FROM imas_diags";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				if (mysql_num_rows($result)>0) {
					$owners = array();
					$dnames = array();
					while ($row = mysql_fetch_row($result)) {
						$owners[$row[1]][] = $row[0];
						$dnames[$row[0]] = $row[2];
					}
					$ow = array_keys($owners);
					$users = array();
					foreach ($ow as $ogrp) {
						$query = "SELECT id,LastName,FirstName FROM imas_users WHERE groupid='$ogrp' AND rights>59 ORDER BY id";
						$result = mysql_query($query) or die("Query failed : " . mysql_error());
						if (mysql_num_rows($result)==0) {
							echo "Orphaned Diags: ".implode(',',$owners[$ogrp]).'<br/>';
						} else if (mysql_num_rows($result)==1) {
							$uid = mysql_result($result,0,0);
							$query = "UPDATE imas_diags SET ownerid=$uid WHERE id IN (".implode(',',$owners[$ogrp]).")";
							mysql_query($query) or die("Query failed : " . mysql_error());
						} else {
							$ops = '';
							while ($row = mysql_fetch_row($result)) {
								$ops .= "<option value=\"{$row[0]}\">{$row[1]}, {$row[2]}</option>";
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
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
		if ($last < 11) {
			$query = "ALTER TABLE `imas_assessments` ADD `tutoredit` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0';";
			mysql_query($query) or die("Query failed : " . mysql_error());
			$query = "ALTER TABLE `imas_students` ADD `locked` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0';";
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
		if ($last < 12) {
			$query = "ALTER TABLE `imas_diags` ADD `reentrytime` SMALLINT( 5 ) UNSIGNED NOT NULL DEFAULT '0';";
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
		if ($last < 13) {
			$query = "CREATE TABLE `imas_diag_onetime` (
				`id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`diag` INT( 10 ) UNSIGNED NOT NULL ,
				`time` INT( 10 ) UNSIGNED NOT NULL ,
				`code` VARCHAR( 9 ) NOT NULL ,
				INDEX (`diag`), INDEX(`time`), INDEX(`code`)
				) ENGINE = InnoDB;";
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
		if ($last < 14) {
			$query = "ALTER TABLE `imas_forums` ADD `cntingb` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '1';";
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
		if ($last < 15) {
			$query = "ALTER TABLE `imas_linkedtext` ADD `target` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0';";
			$res =  mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
		}
		if ($last < 16) {
			 $query = "ALTER TABLE `imas_forum_posts` CHANGE `points` `points` DECIMAL( 5, 1 ) UNSIGNED NULL DEFAULT NULL"; 
			 $res =  mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
			 echo "<p>SimpleLTI has been deprecated and replaced with BasicLTI.  If you have enablesimplelti in your config.php, change it to enablebasiclti.  ";
			 echo "If you do not have either currently in your config.php and want to allow imathas to act as a BasicLTI producer, add \$enablebasiclti = true to config.php</p>";
		}
		if ($last < 17) {
			 $query = "ALTER TABLE `imas_assessments` ADD `eqnhelper` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0';"; 
			 $res =  mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
		}
		if ($last < 18) {
			 $query = "ALTER TABLE `imas_courses` ADD `newflag` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0';"; 
			$res =  mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
		}
		if ($last < 19) {
			 $query = "ALTER TABLE `imas_students` ADD `timelimitmult` DECIMAL(3,2) UNSIGNED NOT NULL DEFAULT '1.0';"; 
			 $res =  mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
		}
		if ($last < 20) {
			 $query = "ALTER TABLE `imas_assessments` ADD `caltag` CHAR( 2 ) NOT NULL DEFAULT '?R';"; 
			 $res = mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
		}
		if ($last < 21) {
			 $query = "ALTER TABLE `imas_courses` ADD `showlatepass` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0';"; 
			 $res = mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
		}
		if ($last < 22) {
			 $query = "ALTER TABLE `imas_assessments` ADD `showtips` TINYINT( 1 ) NOT NULL DEFAULT '1';"; 
			 $res = mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
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
			$res = mysql_query($query);
			if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
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
			$res = mysql_query($query);
			if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
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
			$res = mysql_query($query);
			if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			}
			echo 'imas_stugroupmembers created<br/></p>';
			echo '<p>It is now possible to specify a default course theme by setting $defaultcoursetheme = "theme.css"; in config.php</p>';
			
		}
		if ($last < 24) {
			 $query = "ALTER TABLE `imas_assessments` ADD `groupsetid` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0';"; 
			 $res = mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
	
			 $query = "ALTER TABLE `imas_forums` ADD `groupsetid` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0';"; 
			 $res = mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
			 /*later (once groups are done)
			 $query = "ALTER TABLE `imas_forums` DROP COLUMN `grpaid`"; 
			 $res = mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
			 */
			 $query = "ALTER TABLE `imas_forum_threads` ADD `stugroupid` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0';"; 
			 $res = mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
			 
			 $query = "ALTER TABLE `imas_forum_threads` ADD INDEX(`stugroupid`);"; 
			 $res = mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
			
		}
		if ($last < 25) {
			 $query = "ALTER TABLE `imas_libraries` ADD `sortorder` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0';"; 
			 $res = mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
		}
		if ($last < 26) {
			 $query = "ALTER TABLE `imas_users` ADD `homelayout` VARCHAR(32) NOT NULL DEFAULT '|0,1,2||0,1'"; 
			 $res = mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
		}
		if ($last < 27) {
			$query = "ALTER TABLE `imas_diag_onetime` ADD `goodfor` INT(10) NOT NULL DEFAULT '0'"; 
			 $res = mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
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
			 $res = mysql_query($sql);
			 if ($res===false) {
				 echo "<p>Query failed: ($sql) : ".mysql_error()."</p>";
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
			 $res = mysql_query($sql);
			 if ($res===false) {
				 echo "<p>Query failed: ($sql) : ".mysql_error()."</p>";
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
			 $res = mysql_query($sql);
			 if ($res===false) {
				 echo "<p>Query failed: ($sql) : ".mysql_error()."</p>";
			 }
		}
		if ($last<29) {
			//this is a bug fix for a typo in the homelayout default
			$query = 'ALTER TABLE `imas_users` CHANGE `homelayout` `homelayout` VARCHAR( 32 ) NOT NULL DEFAULT \'|0,1,2||0,1\'';
			$res = mysql_query($query);
			if ($res===false) {
				echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			}
			$query = 'UPDATE `imas_users` SET homelayout = CONCAT(\'|0,1,2\',SUBSTR(homelayout,7))';
			$res = mysql_query($query);
			if ($res===false) {
				echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			}
		}
		if ($last<30) {
			$query = "ALTER TABLE `imas_assessments` ADD `calrtag` VARCHAR(254) NOT NULL DEFAULT 'R';"; 
			$res = mysql_query($query);
			if ($res===false) {
			 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			}
			$query = "UPDATE imas_assessments SET calrtag=substring(caltag,2,1),caltag=substring(caltag,1,1)";
			$res = mysql_query($query);
			if ($res===false) {
			 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			}
			$query = 'ALTER TABLE `imas_assessments` CHANGE `caltag` `caltag` VARCHAR( 254 ) NOT NULL DEFAULT \'?\'';
			$res = mysql_query($query);
			if ($res===false) {
			 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			}
			$query = 'ALTER TABLE `imas_inlinetext` CHANGE `caltag` `caltag` VARCHAR( 254 ) NOT NULL DEFAULT \'!\'';
			$res = mysql_query($query);
			if ($res===false) {
			 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			}
			$query = 'ALTER TABLE `imas_linkedtext` CHANGE `caltag` `caltag` VARCHAR( 254 ) NOT NULL DEFAULT \'!\'';
			$res = mysql_query($query);
			if ($res===false) {
			 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			}
			$query = 'ALTER TABLE `imas_calitems` CHANGE `tag` `tag` VARCHAR( 254 ) NOT NULL';
			$res = mysql_query($query);
			if ($res===false) {
			 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			}
		}
		if ($last < 30.5) {
			if (isset($GLOBALS['AWSkey'])) {
				//update files.  Need to update before changinge agroupids so we will know the curs3asid
				$query = "SELECT id,agroupid,lastanswers,bestlastanswers,reviewlastanswers,assessmentid FROM imas_assessment_sessions ";
				$query .= "WHERE lastanswers LIKE '%@FILE:%' OR bestlastanswers LIKE '%@FILE:%' OR reviewlastanswers LIKE '%@FILE:%'";
				$result = mysql_query($query) or die("Query failed : $query:" . mysql_error());
				require("./includes/filehandler.php");
				$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
				$doneagroups = array();
				while ($row = mysql_fetch_row($result)) {
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
					
					$la = addslashes(preg_replace('/@FILE:/',"@FILE:$path/",$row[2]));
					$bla = addslashes(preg_replace('/@FILE:/',"@FILE:$path/",$row[3]));
					$rla = addslashes(preg_replace('/@FILE:/',"@FILE:$path/",$row[4]));
					$query = "UPDATE imas_assessment_sessions SET lastanswers='$la',bestlastanswers='$bla',reviewlastanswers='$rla' WHERE id={$row[0]}";
					$res = mysql_query($query) or die("Query failed : $query:" . mysql_error());
				}
				echo 'Done up through s3 file change.  <a href="upgrade.php?last=30.5">Continue</a>';
				exit;
			}
		}
		if ($last < 31) {
			//implement groups changes
			$query = "SELECT courseid,id,name FROM imas_assessments WHERE isgroup>0";
			$result = mysql_query($query) or die("Query failed : $query:" . mysql_error());
			$assessgrpset = array();
			while ($row = mysql_fetch_row($result)) {
				$query = "INSERT INTO imas_stugroupset (courseid,name) VALUES ('{$row[0]}','Group set for {$row[2]}')";
				$res = mysql_query($query) or die("Query failed : $query:" . mysql_error());
				$assessgrpset[$row[1]] = mysql_insert_id();
				$query = "UPDATE imas_assessments SET groupsetid={$assessgrpset[$row[1]]} WHERE id={$row[1]}";
				mysql_query($query) or die("Query failed : $query:" . mysql_error());
			}
			
			//identify student group relations
			$query = "SELECT userid,id,agroupid,assessmentid FROM imas_assessment_sessions WHERE agroupid>0";
			$result = mysql_query($query) or die("Query failed : $query:" . mysql_error());
			$agroupusers = array();
			$agroupaids = array();
			while ($row = mysql_fetch_row($result)) {
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
				$query = "INSERT INTO imas_stugroups (groupsetid,name) VALUES (".$assessgrpset[$agroupaids[$agroup]].",'Unnamed group')";
				$result = mysql_query($query) or die("Query failed : $query:" . mysql_error());
				$stugrp = mysql_insert_id();
				if (count($agroupusers[$agroup])>0) {
					foreach ($agroupusers[$agroup] as $k=>$v) {
						$agroupusers[$agroup][$k] = "($stugrp,$v)";
						$userref[$v.'-'.$agroupaids[$agroup]] = $stugrp;  //$userref[userid-aid] = stugrp
					}
					$query = "INSERT INTO imas_stugroupmembers (stugroupid,userid) VALUES ".implode(',',$agroupusers[$agroup]);
					$result = mysql_query($query) or die("Query failed : $query:" . mysql_error());
				}
				$query = "UPDATE imas_assessment_sessions SET agroupid='$stugrp' WHERE agroupid='$agroup'";
				$result = mysql_query($query) or die("Query failed : $query:" . mysql_error());
			}
			
			//update forums and forum posts for groups
			$query = "SELECT id,grpaid FROM imas_forums WHERE grpaid>0";
			$forumaid = array();
			$result = mysql_query($query) or die("Query failed : $query:" . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				$forumaid[$row[0]] = $row[1];
				$query = "UPDATE imas_forums SET groupsetid={$assessgrpset[$row[1]]} WHERE id={$row[0]}";
				mysql_query($query) or die("Query failed : $query:" . mysql_error());
			}
			if (count($forumaid)>0) {
				$forumlist = implode(',',array_keys($forumaid));
				$query = "SELECT forumid,threadid,userid FROM imas_forum_posts WHERE forumid IN ($forumlist) AND parent=0";
				$result = mysql_query($query) or die("Query failed : $query:" . mysql_error());
				while ($row = mysql_fetch_row($result)) {
					if (!isset($userref[$row[2].'-'.$forumaid[$row[0]]])) {
						continue;
					}
					$stugrp = $userref[$row[2].'-'.$forumaid[$row[0]]];
					$query = "UPDATE imas_forum_threads SET stugroupid=$stugrp WHERE id={$row[1]}";
					mysql_query($query) or die("Query failed : $query:" . mysql_error());
				}
			}
			
			
			
		}
		if ($last<32) {
			 $query = "ALTER TABLE `imas_stugroupset` ADD `delempty` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '1';"; 
			 $res =  mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
			 
			 $query = 'ALTER TABLE `imas_forums` CHANGE `name` `name` VARCHAR( 254 ) NOT NULL';
			 $res = mysql_query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
			 
			 $query = 'ALTER TABLE `imas_wikis` CHANGE `name` `name` VARCHAR( 254 ) NOT NULL';
			 $res = mysql_query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
			 
			 $query = 'ALTER TABLE `imas_gbitems` CHANGE `name` `name` VARCHAR( 254 ) NOT NULL';
			 $res = mysql_query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
		}
		if ($last<33) {
			$query = 'ALTER TABLE `imas_questionset` ADD `extref` TEXT NOT NULL DEFAULT \'\'';
			 $res = mysql_query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
		}
		if ($last<34) {
			$query = 'ALTER TABLE `imas_assessments` ADD `deffeedbacktext` VARCHAR( 512 ) NOT NULL DEFAULT \'\'';
			 $res = mysql_query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
		}
		if ($last<35) {
			$query = 'ALTER TABLE `imas_users` ADD `listperpage` TINYINT(3) UNSIGNED NOT NULL DEFAULT \'20\'';
			 $res = mysql_query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
		}
		if ($last<36) {
			$query = 'ALTER TABLE `imas_library_items` ADD `junkflag` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\'';
			 $res = mysql_query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
		}
		if ($last<37) {
			$query = 'ALTER TABLE `imas_questionset` ADD `deleted` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\'';
			 $res = mysql_query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
		}
		if ($last<38) {
			 $query = 'ALTER TABLE `imas_forums` ADD `caltag` VARCHAR( 254 ) NOT NULL DEFAULT \'FP--FR\'';
			 $res = mysql_query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
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
			 $res = mysql_query($sql);
			 if ($res===false) {
				 echo "<p>Query failed: ($sql) : ".mysql_error()."</p>";
			 }
			 $query = 'ALTER TABLE `imas_questions` ADD `rubric` INT(10) UNSIGNED NOT NULL DEFAULT \'0\'';
			 $res = mysql_query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
			 $query = 'ALTER TABLE `imas_gbitems` ADD `rubric` INT(10) UNSIGNED NOT NULL DEFAULT \'0\'';
			 $res = mysql_query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
			 
		}
		if ($last<40) {
			 $query = 'ALTER TABLE `imas_gbscheme` ADD `stugbmode` TINYINT(2) UNSIGNED NOT NULL DEFAULT \'5\'';
			 $res = mysql_query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
		}
		if ($last<41) {
			 $query = 'ALTER TABLE `imas_grades` CHANGE `gbitemid` `gradetypeid` INT(10) NOT NULL';
			 $res = mysql_query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
			 $query = 'ALTER TABLE `imas_grades` ADD `refid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\'';
			 $res = mysql_query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
			 $query = 'ALTER TABLE `imas_grades` ADD `gradetype` VARCHAR(15) NOT NULL DEFAULT \'offline\'';
			 $res = mysql_query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
			 $query = "ALTER TABLE `imas_grades` ADD INDEX(`gradetypeid`);"; 
			 $res = mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
			  $query = "ALTER TABLE `imas_grades` ADD INDEX(`gradetype`);"; 
			 $res = mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
			 $query = "ALTER TABLE `imas_grades` ADD INDEX(`refid`);"; 
			 $res = mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
			 $query = "SELECT id,forumid,userid,points FROM imas_forum_posts WHERE points IS NOT NULL";
			 $res = mysql_query($query);
			 $i = 0;
			 while ($row = mysql_fetch_row($res)) {
			 	 if ($i%500==0) {
			 	 	 if ($i>0) {
			 	 	 	 mysql_query($ins);
			 	 	 } 
			 	 	 $ins = "INSERT INTO imas_grades (gradetype,gradetypeid,refid,userid,score) VALUES ";
			 	 } else {
			 	 	 $ins .= ",";
			 	 }
			 	 $ins .= "('forum',{$row[1]},{$row[0]},{$row[2]},{$row[3]})"; 
			 	 $i++;
			 }
			 if ($i>0) {
			 	 mysql_query($ins);
			 }
		}
		if ($last<42) {
			$query = "ALTER TABLE `imas_questionset` ADD INDEX(`deleted`);"; 
			 $res = mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
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
			 $res = mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
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
			 $res = mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }		
		}
		if ($last<44) {
			//bug fix for wrong userid being recorded on forum grades
			$query = "SELECT ig.id,ifp.userid FROM imas_grades AS ig JOIN imas_forum_posts AS ifp ";
			$query .= "ON ig.gradetype='forum' AND ig.refid=ifp.id AND ifp.userid<>ig.userid";
			$res = mysql_query($query);
			while ($row = mysql_fetch_row($res)) {
				$query = "UPDATE imas_grades SET userid={$row[1]} WHERE id={$row[0]}";
				mysql_query($query);
			}
		}
		if ($last < 45) {
			$query = 'ALTER TABLE `imas_assessment_sessions` ADD `timeontask` TEXT NOT NULL';
			$res = mysql_query($query);
			if ($res===false) {
			  echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			}
			$query = 'CREATE TABLE `imas_login_log` (
				`id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`userid` INT( 10 ) UNSIGNED NOT NULL ,
				`courseid` INT( 10 ) UNSIGNED NOT NULL ,
				`logintime` INT( 10 ) UNSIGNED NOT NULL ,
				 INDEX(`userid` ), INDEX(`courseid`)
				) ENGINE = InnoDB;';
			 $res = mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
		}  
		if ($last < 46) {
			$query = 'ALTER TABLE `imas_questionset` ADD `avgtime` SMALLINT(5) UNSIGNED NOT NULL DEFAULT \'0\'';
			$res = mysql_query($query);
			if ($res===false) {
			  echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
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
			 $res = mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
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
			 $res = mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
			$query = 'ALTER TABLE `imas_assessment_sessions` ADD `lti_sourcedid` TEXT NOT NULL';
			$res = mysql_query($query);
			if ($res===false) {
			  echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			}
		}
		if ($last < 48) {
			 $query = 'ALTER TABLE `imas_ltiusers` CHANGE `org` `org` VARCHAR( 254 ) NOT NULL';
			 $res = mysql_query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
			  $query = 'ALTER TABLE `imas_ltiusers` CHANGE `ltiuserid` `ltiuserid` VARCHAR( 254 ) NOT NULL';
			 $res = mysql_query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
		}
		if ($last<49) {
			$query = "ALTER TABLE `imas_login_log` ADD `lastaction` INT(10) UNSIGNED NOT NULL";
			 $res = mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
		}
		if ($last<50) {
			$query = "ALTER TABLE `imas_students` CHANGE `locked` `locked` INT(10) UNSIGNED NOT NULL";
			 $res = mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
			 $query = "ALTER TABLE `imas_gbscheme` ADD `colorize` VARCHAR (20) NOT NULL";
			 $res = mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
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
			$res = mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
		}
		if ($last < 52) {
			 $query = 'ALTER TABLE `imas_assessments` ADD `posttoforum` INT(10) UNSIGNED NOT NULL DEFAULT \'0\'';
			 $res = mysql_query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
			 $query = 'ALTER TABLE `imas_assessments` ADD `msgtoinstr` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\'';
			 $res = mysql_query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
			 //grab msg to instr settings and move to asssessments
			 $query = "SELECT id,msgset FROM imas_courses WHERE msgset>9";
			  $res = mysql_query($query);
			  while ($row = mysql_fetch_row($res)) {
			  	  $query = "UPDATE imas_assessments SET msgtoinstr=1 WHERE courseid={$row[0]}";
			  	  mysql_query($query);
			  }
			  $query = "UPDATE imas_courses SET msgset=msgset-10 WHERE msgset>9";
			  mysql_query($query);
			 
		}
		if ($last<53) {
			$query = "ALTER TABLE `imas_forums` ADD `forumtype` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0'";
			 $res = mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
			$query = "ALTER TABLE `imas_forums` ADD `taglist` TEXT NOT NULL";
			 $res = mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
			$query = "ALTER TABLE `imas_forum_posts` ADD `files` TEXT NOT NULL";
			 $res = mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
			$query = "ALTER TABLE `imas_forum_posts` ADD `tag` VARCHAR(255) NOT NULL";
			 $res = mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
			$query = "ALTER TABLE `imas_forum_posts` ADD INDEX(`tag`)"; 
			 $res = mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
		}
		if ($last<54) {
			$query = "UPDATE `imas_questionset` SET userights=4 WHERE userights=3";
			$res = mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
		}
		if ($last<55) {
			 $query = 'ALTER TABLE `imas_questionset` ADD `broken` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\'';
			 $res = mysql_query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
		}
		if ($last<56) {
			 $query = 'ALTER TABLE `imas_wiki_views` ADD `stugroupid` INT(10) UNSIGNED NOT NULL DEFAULT \'0\'';
			 $res = mysql_query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
			 $query = "ALTER TABLE `imas_wiki_views` ADD INDEX(`stugroupid`);"; 
			 $res = mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }	
		}
		if ($last<57) {
			 $query = 'ALTER TABLE `imas_questions` ADD `showhints` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\'';
			 $res = mysql_query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
		}
		if ($last < 58) {
			 $query = 'ALTER TABLE `imas_assessment_sessions` CHANGE `lastanswers` `lastanswers` MEDIUMTEXT NOT NULL';
			 $res = mysql_query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
			 $query = 'ALTER TABLE `imas_assessment_sessions` CHANGE `bestlastanswers` `bestlastanswers` MEDIUMTEXT NOT NULL';
			 $res = mysql_query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
			 $query = 'ALTER TABLE `imas_assessment_sessions` CHANGE `reviewlastanswers` `reviewlastanswers` MEDIUMTEXT NOT NULL';
			 $res = mysql_query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
			 $query = 'ALTER TABLE `imas_courses` CHANGE `itemorder` `itemorder` MEDIUMTEXT NOT NULL';
			 $res = mysql_query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
		}
		if ($last < 59) {
			 $query = 'ALTER TABLE `imas_assessments` ADD `istutorial` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\'';
			 $res = mysql_query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
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
			$res = mysql_query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
			 
			$query = 'CREATE TABLE `imas_badgerecords` (
				  `id` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
				  `userid` int(10) unsigned NOT NULL,
				  `badgeid` int(10) unsigned NOT NULL,
				  `data` text NOT NULL,
				  INDEX (`userid`), INDEX(`badgeid`)
				) ENGINE=InnoDB;';
			$res = mysql_query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
				
		}
		if ($last < 61) {
			 $query = 'ALTER TABLE `imas_assessments` ADD `viddata` TEXT NOT NULL DEFAULT \'\'';
			 $res = mysql_query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
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
			$res = mysql_query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
		}
		if ($last < 63) {
			 $query = 'ALTER TABLE `imas_forums` ADD `rubric` INT( 10 ) UNSIGNED NOT NULL DEFAULT \'0\'';
			 $res = mysql_query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
		}
		if ($last < 64) {
			 $query = 'ALTER TABLE `imas_courses` ADD `toolset` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT \'0\'';
			 $res = mysql_query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
		}
		if ($last < 65) {
			$query = 'CREATE TABLE `imas_dbschema` (
				`id` INT( 10 ) UNSIGNED NOT NULL PRIMARY KEY ,
				`ver` SMALLINT( 4 ) UNSIGNED NOT NULL
				) ENGINE = InnoDB';
			$res = mysql_query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 } else {
			 	 $query = "INSERT INTO imas_dbschema (id,ver) VALUES (1,$latest)";
			 	 mysql_query($query) or die ("can't run $query");
			 }
			echo "Moved upgrade counter to database<br/>";
		}
		if ($last < 66) {
			$query = 'CREATE TABLE `imas_log` (
				`id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`time` INT( 10 ) UNSIGNED NOT NULL ,
				`log` TEXT NOT NULL 
				) ENGINE = InnoDB';
			$res = mysql_query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 } 
			echo "Added imas_log table<br/>";
		}
		if ($last < 67) {
			 $query = 'ALTER TABLE `imas_users` ADD `hasuserimg` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT \'0\'';
			 $res = mysql_query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
			 $hasimg = array();
			 if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
				require("includes/filehandler.php");
				$s3 = new S3($GLOBALS['AWSkey'],$GLOBALS['AWSsecret']);
				$arr = $s3->getBucket($GLOBALS['AWSbucket'],"cfiles/");
				if ($arr!=false) {
					foreach ($arr as $k=>$v) {
						if (substr(basename($arr[$k]['name']),0,10)=='userimg_sm') {
							$hasimg[] = substr(basename($arr[$k]['name']),10,-4);
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
			 	 	 	 	 	$hasimg[] = substr(basename($file),10,-4); 
			 	 	 	 	 }
			 	 	 	 }
			 	 	 }
			 	 	 closedir($handle);
			 	 }
			 	
			 }
			 if (count($hasimg)>0) {
			 	 $haslist = implode(',',$hasimg);
			 	 $query = "UPDATE imas_users SET hasuserimg=1 WHERE id IN ($haslist)";
			 	 mysql_query($query);
			 	 $n = mysql_affected_rows();
			 }
			 echo "hasuserimg field added, $n user images identified<br/>";
		}
		if ($last < 68) {
			 $query = 'ALTER TABLE `imas_assessments` CHANGE `intro` `intro` MEDIUMTEXT NOT NULL';
			 $res = mysql_query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
			 $query = 'INSERT INTO imas_dbschema (id,ver) VALUES (2,100)';
			 $res = mysql_query($query);
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
			$res = mysql_query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 } 
			echo "Added imas_forum_likes table<br/>";
		}
		if ($last < 70) {
			 $query = 'ALTER TABLE `imas_assessments`  ADD `ancestors` TEXT NOT NULL DEFAULT \'\'';
			 $res = mysql_query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
			 $query = 'ALTER TABLE `imas_courses`  ADD `ancestors` TEXT NOT NULL DEFAULT \'\'';
			 $res = mysql_query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
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
			$res = mysql_query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 } else {
			 	 echo "Added imas_content_track table.<br/>";
			 }
			 
			$query = 'ALTER TABLE `imas_courses`  ADD `istemplate` TINYINT(1) NOT NULL DEFAULT \'0\'';
			 $res = mysql_query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
			 //1: global template.  2: group template.  4: self-enroll course.  8: guest temp access
			 if (isset($templateuser)) {
			 	 $query = "UPDATE imas_courses SET istemplate=(istemplate | 1) WHERE id IN (SELECT courseid FROM imas_teachers WHERE userid=$templateuser)";
			 	 $res = mysql_query($query);
			 }
			 if (isset($CFG['GEN']['selfenrolluser'])) {
			 	 $query = "UPDATE imas_courses SET istemplate=(istemplate | 4) WHERE id IN (SELECT courseid FROM imas_teachers WHERE userid={$CFG['GEN']['selfenrolluser']})";
			 	 $res = mysql_query($query);
			 }
			  if (isset($CFG['GEN']['guesttempaccts']) && count($CFG['GEN']['guesttempaccts'])>0) {
			  	 $query = "UPDATE imas_courses SET istemplate=(istemplate | 8) WHERE id IN (".implode(',',$CFG['GEN']['guesttempaccts']).")";
			 	 $res = mysql_query($query); 
			  }
		}
		if ($last < 71) {
			 $query = 'ALTER TABLE `imas_courses`  ADD `outcomes` TEXT NOT NULL DEFAULT \'\'';
			 $res = mysql_query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
			 
			 $query = 'CREATE TABLE `imas_outcomes` (
				`id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`courseid` INT(10) UNSIGNED NOT NULL, 
				`name` VARCHAR(255) NOT NULL, 
				`ancestors` TEXT NOT NULL,
				INDEX ( `courseid`) 
				) ENGINE = InnoDB';
			 $res = mysql_query($query);
			 if ($res===false) {
			 	 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 } 
			 
			 echo "Added imas_outcomes table<br/>";
			
			 //replace library category # with library name
			 $query = 'UPDATE imas_questions AS iq, imas_libraries AS il SET iq.category=il.name WHERE iq.category=il.id AND il.name IS NOT NULL';
			 mysql_query($query);
			 
			 $query = 'ALTER TABLE `imas_assessments`  ADD `defoutcome` INT(10) UNSIGNED NOT NULL DEFAULT \'0\'';
			 $res = mysql_query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
			 $query = 'ALTER TABLE `imas_linkedtext`  ADD `outcomes` TEXT NOT NULL';
			 $res = mysql_query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
			 $query = 'ALTER TABLE `imas_forums`  ADD `outcomes`  TEXT NOT NULL';
			 $res = mysql_query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
			 $query = 'ALTER TABLE `imas_gbitems`  ADD `outcomes`  TEXT NOT NULL';
			 $res = mysql_query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
			
			//add indexes to forum_likes
			 $query = "ALTER TABLE `imas_forum_likes` ADD INDEX(`userid`);"; 
			 $res = mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
			 $query = "ALTER TABLE `imas_forum_likes` ADD INDEX(`postid`);"; 
			 $res = mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
			 $query = "ALTER TABLE `imas_forum_likes` ADD INDEX(`threadid`);"; 
			 $res = mysql_query($query);
			 if ($res===false) {
				 echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
			
		}
		if ($last<72) {
			 $query = 'ALTER TABLE `imas_inlinetext`  ADD `outcomes` TEXT NOT NULL';
			 $res = mysql_query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
		}
		if ($last<73) {
			 $query = 'ALTER TABLE `imas_students`  ADD `hidefromcourselist` TINYINT(1) UNSIGNED NOT NULL DEFAULT \'0\'';
			 $res = mysql_query($query);
			 if ($res===false) {
			  echo "<p>Query failed: ($query) : ".mysql_error()."</p>";
			 }
		}
		/*$handle = fopen("upgradecounter.txt",'w');
		if ($handle===false) {
			echo '<p>Error: unable open upgradecounter.txt for writing</p>';
		} else {
			$fwrite = fwrite($handle,$latest);
			if ($fwrite === false) {
				echo '<p>Error: unable to write to upgradecounter.txt</p>';
			}
			fclose($handle);
		}
		*/
		$query = "UPDATE imas_dbschema SET ver=$latest WHERE id=1";
		mysql_query($query);
		echo "Upgrades complete";
	}	
}

?>
<?php  
//change counter; increase by 1 each time a change is made
$latest = 35;


@set_time_limit(0);
ini_set("max_input_time", "600");
ini_set("max_execution_time", "600");
ini_set("memory_limit", "104857600");

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
	} else if (isset($_GET['last'])) {
		$last = floatval($_GET['last']);	
		fclose($handle);
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
				) TYPE = innodb;";
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
				. ' TYPE = innodb'
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
				. ' TYPE = innodb'
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
				. ' TYPE = innodb'
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
				. ' TYPE = innodb'
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
				. ' TYPE = innodb'
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
				. ' TYPE = innodb'
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
		$handle = fopen("upgradecounter.txt",'w');
		if ($handle===false) {
			echo '<p>Error: unable open upgradecounter.txt for writing</p>';
		} else {
			$fwrite = fwrite($handle,$latest);
			if ($fwrite === false) {
				echo '<p>Error: unable to write to upgradecounter.txt</p>';
			}
			fclose($handle);
		}
		echo "Upgrades complete";
	}	
}

?>

<?php
//IMathAS:  Manage student groups
//(c) 2010 David Lippman

/*** master php includes *******/
require("../validate.php");
require("../includes/htmlutil.php");
require("../includes/stugroups.php");
require("../includes/filehandler.php");

/*** pre-html data manipulation, including function code *******/
$cid = $_GET['cid'];
if ( isset($_GET['grpsetid'])) {
	$grpsetid =  $_GET['grpsetid'];
}

//set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = "Manage Student Groups";
$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\">$coursename</a> ";

if (!(isset($teacherid))) { // loaded by a NON-teacher
	$overwriteBody=1;
	$body = "You need to log in as a teacher to access this page";
} else {
	if (isset($_GET['addgrp']) && isset($_POST['grpname']) && isset($_GET['grpsetid'])) {
		//adding a group.  Could be a "add new group" only, or adding a new group while assigning students
		if (trim($_POST['grpname'])=='') {
			$_POST['grpname'] = 'Unnamed group';
		}
		$query = "INSERT INTO imas_stugroups (groupsetid,name) VALUES ('$grpsetid','{$_POST['grpname']}')";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		if (!isset($_POST['stutoadd'])) { //if not adding students also
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/managestugrps.php?cid=$cid&grpsetid={$_GET['grpsetid']}");
			exit();
		} else {
			$_POST['addtogrpid'] = mysql_insert_id();
			$_GET['addstutogrp'] = true;
		}
	} 
	if (isset($_GET['addgrpset'])) {
		//adding groupset
		if (isset($_POST['grpsetname'])) {
			if (trim($_POST['grpsetname'])=='') {
				$_POST['grpsetname'] = 'Unnamed group set';
			}
			//if name is set
			$query = "INSERT INTO imas_stugroupset (name,courseid) VALUES ('{$_POST['grpsetname']}','$cid')";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/managestugrps.php?cid=$cid");
			exit();
		}
		$curBreadcrumb .= " &gt; <a href=\"managestugrps.php?cid=$cid\">Manage Student Groups</a> &gt; Add Group Set";
	} else if (isset($_GET['delgrpset'])) {
		//deleting groupset
		if (isset($_GET['confirm'])) {
			//if name is set
			deletegroupset($_GET['delgrpset']);
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/managestugrps.php?cid=$cid");
			exit();
		} else {
			$query = "SELECT name FROM imas_stugroupset WHERE id='{$_GET['delgrpset']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$page_grpsetname = mysql_result($result,0,0);
		}
			
		$curBreadcrumb .= " &gt; <a href=\"managestugrps.php?cid=$cid\">Manage Student Groups</a> &gt; Delete Group Set";
	} else if (isset($_GET['rengrpset'])) {
		//renaming groupset	
		if (isset($_POST['grpsetname'])) {
			//if name is set
			$query = "UPDATE imas_stugroupset SET name='{$_POST['grpsetname']}' WHERE id='{$_GET['rengrpset']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/managestugrps.php?cid=$cid");
			exit();
		} else {
			$query = "SELECT name FROM imas_stugroupset WHERE id='{$_GET['rengrpset']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$page_grpsetname = mysql_result($result,0,0);
		}
		$curBreadcrumb .= " &gt; <a href=\"managestugrps.php?cid=$cid\">Manage Student Groups</a> &gt; Rename Group Set";
	} else if (isset($_GET['copygrpset'])) {
		//copying groupset
		$query = "SELECT name FROM imas_stugroupset WHERE id='{$_GET['copygrpset']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$grpsetname = addslashes(mysql_result($result,0,0)) . ' (copy)';
		
		$query = "INSERT INTO imas_stugroupset (name,courseid) VALUES ('$grpsetname','$cid')";
		mysql_query($query) or die("Query failed : " . mysql_error());
		$newgrpset = mysql_insert_id();
		
		$query = "SELECT id,name FROM imas_stugroups WHERE groupsetid='{$_GET['copygrpset']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			$row[1] = addslashes($row[1]);
			$query = "INSERT INTO imas_stugroups (name,groupsetid) VALUES ('{$row[1]}',$newgrpset)";
			mysql_query($query) or die("Query failed : " . mysql_error());
			$newstugrp = mysql_insert_id();
			$toadd = array();
			$query = "SELECT userid FROM imas_stugroupmembers WHERE stugroupid='{$row[0]}'";
			$r2 = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($sgm = mysql_fetch_row($r2)) {
				$toadd[] = "('{$sgm[0]}',$newstugrp)";
			}
			if (count($toadd)>0) {
				$query = "INSERT INTO imas_stugroupmembers (userid,stugroupid) VALUES ".implode(',',$toadd);
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
		}
		header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/managestugrps.php?cid=$cid");
		exit();
	} else if (isset($_GET['addstutogrp'])) {
		//submitting list of students to add to a group
		$stustoadd = $_POST['stutoadd'];
		if ($_POST['addtogrpid']=='--new--') {
			//adding a new group; need to ask for group
			$_GET['addgrp'] = true;
			$stulist = implode(',',$stustoadd);	
		} else {
			$grpid = $_POST['addtogrpid'];
			$loginfo = "instr adding stu to group $grpid. ";
			if (!is_array($stustoadd)) {
				$stustoadd = explode(',',$stustoadd);
			}
			
			$alreadygroupedstu = array();
			$stulist = "'".implode("','",$stustoadd)."'";
			$query = "SELECT i_sgm.userid FROM imas_stugroupmembers as i_sgm JOIN imas_stugroups as i_sg ON i_sgm.stugroupid=i_sg.id ";
			$query .= "WHERE i_sg.groupsetid='$grpsetid' AND i_sgm.userid IN ($stulist)";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				$alreadygroupedstu[] = $row[0];
			}
			$stustoadd = array_diff($stustoadd,$alreadygroupedstu);
			
			$query = "SELECT userid FROM imas_stugroupmembers WHERE stugroupid='$grpid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$existinggrpmembers = array();
			while ($row = mysql_fetch_row($result)) {
				$existinggrpmembers[] = $row[0];
			}
			
			if (count($stustoadd)>0) {
				$query = 'INSERT INTO imas_stugroupmembers (stugroupid,userid) VALUES ';
				for ($i=0;$i<count($stustoadd);$i++) {
					if ($i>0) {$query .= ',';};
					$query .= "('$grpid','{$stustoadd[$i]}')";
					$loginfo .= "adding {$stustoadd[$i]}.";
				}
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				
				$query = "SELECT id FROM imas_assessments WHERE groupsetid='$grpsetid'";
				$resultaid = mysql_query($query) or die("Query failed : " . mysql_error());
				$stulist = "'".implode("','",$stustoadd)."'";
				while (($aid = mysql_fetch_row($resultaid)) && $grpsetid>0) {
					//if asid exists for this grpid, need to update students.
					//if no asid exists already, but the students we're adding have one, use one (which?) of theirs
					//otherwise do nothing
					$fieldstocopy = 'assessmentid,agroupid,questions,seeds,scores,attempts,lastanswers,starttime,endtime,bestseeds,bestattempts,bestscores,bestlastanswers,feedback,reviewseeds,reviewattempts,reviewscores,reviewlastanswers,reattempting,reviewreattempting,timeontask';
					$rowgrptest = '';
					$query = "SELECT $fieldstocopy ";
					$query .= "FROM imas_assessment_sessions WHERE agroupid='$grpid' AND assessmentid='{$aid[0]}'";
					$result = mysql_query($query) or die("Query failed : $query:" . mysql_error());
					if (mysql_num_rows($result)>0) {
						//asid already exists for group - use it
						$rowgrptest = addslashes_deep(mysql_fetch_row($result)); 
						$grpasidexists = true;
					} else {
						//use asid from first student assessment
						$grpasidexists = false;
						$query = "SELECT id,$fieldstocopy ";
						$query .= "FROM imas_assessment_sessions WHERE userid IN ($stulist) AND assessmentid='{$aid[0]}'";
						$result = mysql_query($query) or die("Query failed : $query:" . mysql_error());
						if (mysql_num_rows($result)>0) {
							$row = mysql_fetch_row($result);
							$srcasid = array_shift($row);
							$rowgrptest = addslashes_deep($row);
							$rowgrptest[1] = $grpid; //use new groupid
							while ($row = mysql_fetch_row($result)) { //delete files from everyone else's attempts
								deleteasidfilesfromstring2($row[7].$row[13],'id',$row[0],$row[1]);	
							}
						}
					}
					if ($rowgrptest != '') {  //if an assessment session already exists
						$fieldstocopyarr = explode(',',$fieldstocopy);
						$insrow = "'".implode("','",$rowgrptest)."'";
						if ($grpasidexists==false) {
							//asid coming from added group member.  Also copy to any existing group members
							$stustoadd = array_merge($stustoadd,$existinggrpmembers);
						}
						foreach ($stustoadd as $stuid) {
							$query = "SELECT id,agroupid FROM imas_assessment_sessions WHERE userid='$stuid' AND assessmentid={$aid[0]}";
							$result = mysql_query($query) or die("Query failed : $query:" . mysql_error());
							if (mysql_num_rows($result)>0) {  
								$loginfo .= "updating ias for $stuid.";
								$row = mysql_fetch_row($result);
								$sets = array();
								foreach ($fieldstocopyarr as $k=>$val) {
									$sets[] = "$val='{$rowgrptest[$k]}'";
								}
								$setslist = implode(',',$sets);
								$query = "UPDATE imas_assessment_sessions SET $setslist WHERE id='{$row[0]}'";
								//$query = "UPDATE imas_assessment_sessions SET assessmentid='{$rowgrptest[0]}',agroupid='{$rowgrptest[1]}',questions='{$rowgrptest[2]}'";
								//$query .= ",seeds='{$rowgrptest[3]}',scores='{$rowgrptest[4]}',attempts='{$rowgrptest[5]}',lastanswers='{$rowgrptest[6]}',";
								//$query .= "starttime='{$rowgrptest[7]}',endtime='{$rowgrptest[8]}',bestseeds='{$rowgrptest[9]}',bestattempts='{$rowgrptest[10]}',";
								//$query .= "bestscores='{$rowgrptest[11]}',bestlastanswers='{$rowgrptest[12]}'  WHERE id='{$row[0]}'";
								//$query = "UPDATE imas_assessment_sessions SET agroupid='$agroupid' WHERE id='{$row[0]}'";
								mysql_query($query) or die("Query failed : $query:" . mysql_error());
							} else {
								$loginfo .= "inserting ias for $stuid.";
								$query = "INSERT INTO imas_assessment_sessions (userid,$fieldstocopy) ";
								$query .= "VALUES ('$stuid',$insrow)";
								mysql_query($query) or die("Query failed : $query:" . mysql_error());
							}
						}
					}
				}
			}
			if (count($alreadygroupedstu)>0) {
				require("../header.php");
				echo '<p>Some students joined a group already and were skipped:</p><p>';
				$stulist = "'".implode("','",$alreadygroupedstu)."'";
				$query = "SELECT FirstName,LastName FROM imas_users WHERE id IN ($stulist) ORDER BY LastName, FirstName";
				$result = mysql_query($query) or die("Query failed : $query:" . mysql_error());
				while ($row = mysql_fetch_row($result)) {
					echo $row[1].', '.$row[0].'<br/>';
					$loginfo .= $row[1].', '.$row[0].' already in group.';
				}
				echo "<p><a href=\"managestugrps.php?cid=$cid&grpsetid={$_GET['grpsetid']}\">Continue</a></p>";
				require("../footer.php");
				$now = time();
				if (isset($GLOBALS['CFG']['log'])) {
					$query = "INSERT INTO imas_log (time,log) VALUES ($now,'".addslashes($loginfo)."')";
					mysql_query($query) or die("Query failed : " . mysql_error());
				}
			} else {
				$now = time();
				if (isset($GLOBALS['CFG']['log'])) {
					$query = "INSERT INTO imas_log (time,log) VALUES ($now,'".addslashes($loginfo)."')";
					mysql_query($query) or die("Query failed : " . mysql_error());
				}
				header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/managestugrps.php?cid=$cid&grpsetid={$_GET['grpsetid']}");
			}
			exit();
		}
		
	} else if (isset($_GET['addgrp'])) {
		$query = "SELECT name FROM imas_stugroupset WHERE id='$grpsetid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$page_grpsetname = mysql_result($result,0,0);
		$curBreadcrumb .= " &gt; <a href=\"managestugrps.php?cid=$cid\">Manage Student Groups</a> &gt; <a href=\"managestugrps.php?cid=$cid&grpsetid=$grpsetid\">$page_grpsetname</a> &gt; Add Group";
	} else if (isset($_GET['delgrp'])) {
		//deleting groupset
		if (isset($_GET['confirm'])) {
			//if name is set
			deletegroup($_GET['delgrp'], $_POST['delposts']==1);
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/managestugrps.php?cid=$cid&grpsetid=$grpsetid");
			exit();
		} else {
			$query = "SELECT name FROM imas_stugroups WHERE id='{$_GET['delgrp']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$page_grpname = mysql_result($result,0,0);
			$query = "SELECT name FROM imas_stugroupset WHERE id='$grpsetid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$page_grpsetname = mysql_result($result,0,0);
		}
			
		$curBreadcrumb .= " &gt; <a href=\"managestugrps.php?cid=$cid\">Manage Student Groups</a> &gt; <a href=\"managestugrps.php?cid=$cid&grpsetid=$grpsetid\">$page_grpsetname</a> &gt; Delete Group";
	} else if (isset($_GET['rengrp'])) {
		//renaming groupset	
		if (isset($_POST['grpname'])) {
			//if name is set
			$query = "UPDATE imas_stugroups SET name='{$_POST['grpname']}' WHERE id='{$_GET['rengrp']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/managestugrps.php?cid=$cid&grpsetid=$grpsetid");
			exit();
		} else {
			$query = "SELECT name FROM imas_stugroups WHERE id='{$_GET['rengrp']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$page_grpname = mysql_result($result,0,0);
			$query = "SELECT name FROM imas_stugroupset WHERE id='$grpsetid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$page_grpsetname = mysql_result($result,0,0);
		}
		$curBreadcrumb .= " &gt; <a href=\"managestugrps.php?cid=$cid\">Manage Student Groups</a> &gt; <a href=\"managestugrps.php?cid=$cid&grpsetid=$grpsetid\">$page_grpsetname</a> &gt; Rename Group";
	} else if (isset($_GET['removeall'])) {
		//removing all group members
		if (isset($_GET['confirm'])) {
			//if name is set
			removeallgroupmembers($_GET['removeall']);
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/managestugrps.php?cid=$cid&grpsetid=$grpsetid");
			exit();
		} else {
			$query = "SELECT name FROM imas_stugroups WHERE id='{$_GET['removeall']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$page_grpname = mysql_result($result,0,0);
			$query = "SELECT name FROM imas_stugroupset WHERE id='$grpsetid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$page_grpsetname = mysql_result($result,0,0);
		}	
		$curBreadcrumb .= " &gt; <a href=\"managestugrps.php?cid=$cid\">Manage Student Groups</a> &gt; <a href=\"managestugrps.php?cid=$cid&grpsetid=$grpsetid\">$page_grpsetname</a> &gt; Remove all group members";
		
	} else if (isset($_GET['remove']) && isset($_GET['grpid'])) {
		//removing one group member
		if (isset($_GET['confirm'])) {
			//if name is set
			removegroupmember($_GET['grpid'],$_GET['remove']);
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/managestugrps.php?cid=$cid&grpsetid=$grpsetid");
			exit();
		} else {
			$query = "SELECT LastName, FirstName FROM imas_users WHERE id='{$_GET['remove']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$page_stuname = mysql_result($result,0,0).', '.mysql_result($result,0,1);
			$query = "SELECT name FROM imas_stugroups WHERE id='{$_GET['grpid']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$page_grpname = mysql_result($result,0,0);
			$query = "SELECT name FROM imas_stugroupset WHERE id='$grpsetid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$page_grpsetname = mysql_result($result,0,0);
		}	
		$curBreadcrumb .= " &gt; <a href=\"managestugrps.php?cid=$cid\">Manage Student Groups</a> &gt; <a href=\"managestugrps.php?cid=$cid&grpsetid=$grpsetid\">$page_grpsetname</a> &gt; Remove group member";
		
	} else if (isset($_GET['grpsetid'])) {
		//groupset selected, show groups
		$grpsetid = $_GET['grpsetid'];
		$query = "SELECT name FROM imas_stugroupset WHERE id='$grpsetid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$page_grpsetname = mysql_result($result,0,0);	
		
		//$page_grps will be an array, groupid=>name
		$page_grps = array();
		$page_grpmembers = array();
		$grpnums = 1;
		$query = "SELECT id,name FROM imas_stugroups WHERE groupsetid='$grpsetid' ORDER BY id";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			if ($row[1] == 'Unnamed group') { 
				$row[1] .= " $grpnums";
				$grpnums++;
			}
			$page_grps[$row[0]] = $row[1];
			$page_grpmembers[$row[0]] = array();
		}
		$grpids = implode(',',array_keys($page_grps));
		
		natsort($page_grps);
		
		//get all students
		$stunames = array();
		$hasuserimg = array();
		$query = "SELECT iu.id,iu.FirstName,iu.LastName,iu.hasuserimg FROM imas_users AS iu JOIN imas_students ON iu.id=imas_students.userid WHERE imas_students.courseid='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			$stunames[$row[0]] = $row[2].', '.$row[1];
			$hasuserimg[$row[0]] = $row[3];
		}
		
		//$page_grpmembers will be groupid=>array(  userid=>stuname )
		$stuuseridsingroup = array();
		if (count($page_grps)>0) {
			$query = "SELECT stugroupid,userid FROM imas_stugroupmembers WHERE stugroupid IN ($grpids)";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				if (!isset($page_grpmembers[$row[0]])) {
					$page_grpmembers[$row[0]] = array();
				}
				$page_grpmembers[$row[0]][$row[1]] = $stunames[$row[1]];
				$stuuseridsingroup[] = $row[1];
			}
			//sort each group member list by name
			foreach ($page_grpmembers as $k=>$stuarr) {
				natcasesort($stuarr);
				$page_grpmembers[$k] = $stuarr;
			}
		}
		$ungrpids = array_diff(array_keys($stunames),$stuuseridsingroup);
		$page_ungrpstu = array();
		foreach ($ungrpids as $uid) {
			$page_ungrpstu[$uid] = $stunames[$uid];
		}
		natcasesort($page_ungrpstu);
		
		$curBreadcrumb .= " &gt; <a href=\"managestugrps.php?cid=$cid\">Manage Student Groups</a> &gt; $page_grpsetname";
			
	} else { 
		//no groupset selected
		$page_groupsets = array();
		$query = "SELECT id,name FROM imas_stugroupset WHERE courseid='$cid' ORDER BY name";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			$page_groupsets[] = $row;
		}
		$curBreadcrumb .= " &gt; Manage Student Groups";
	}
	

}

/******* begin html output ********/
require("../header.php");

/**** post-html data manipulation ******/
// this page has no post-html data manipulation

/***** page body *****/
/***** php display blocks are interspersed throughout the html as needed ****/
if ($overwriteBody==1) {
	echo $body;
} else {	
?>
	<div class="breadcrumb"><?php echo $curBreadcrumb ?></div>
	<div id="headermanagestugrps" class="pagetitle"><h2><?php echo $pagetitle ?></h2></div>
<?php
	if (isset($_GET['addgrpset'])) {
		//add new group set
		echo '<h4>Add new set of student groups</h4>';
		echo "<form method=\"post\" action=\"managestugrps.php?cid=$cid&addgrpset=true\">";
		echo '<p>New group set name: <input name="grpsetname" type="text" /></p>';
		echo '<p><input type="submit" value="Create" />';
		echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onClick=\"window.location='managestugrps.php?cid=$cid'\" /></p>";
		echo '</form>';
	} else if (isset($_GET['delgrpset'])) {
		echo '<h4>Delete student group set</h4>';
		echo "<p>Are you SURE you want to delete the set of student groups <b>$page_grpsetname</b> and all the groups contained within it? ";
		$used = '';
		$query = "SELECT name FROM imas_assessments WHERE isgroup>0 AND groupsetid='{$_GET['delgrpset']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			$used .= "Assessment: {$row[0]}<br/>";
		}
		$query = "SELECT name FROM imas_forums WHERE groupsetid='{$_GET['delgrpset']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			$used .= "Forum: {$row[0]}<br/>";
		}
		$query = "SELECT name FROM imas_wikis WHERE groupsetid='{$_GET['delgrpset']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			$used .= "Wiki: {$row[0]}<br/>";
		}
		if ($used != '') {
			echo '<p>This set of groups is currently used in the assessments, wikis, and/or forums below.  These items will be set to non-group if this group set is deleted</p><p>';
			echo "$used</p>";	
		} else {
			echo '<p>This set of groups is not currently being used</p>';
		}
		echo "<p><input type=button value=\"Yes, Delete\" onClick=\"window.location='managestugrps.php?cid=$cid&delgrpset={$_GET['delgrpset']}&confirm=true'\" /> ";
		echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onClick=\"window.location='managestugrps.php?cid=$cid'\" /></p>";
		
	} else if (isset($_GET['rengrpset'])) {
		echo '<h4>Rename student group set</h4>';
		echo "<form method=\"post\" action=\"managestugrps.php?cid=$cid&rengrpset={$_GET['rengrpset']}\">";
		echo '<p>New group set name: <input name="grpsetname" type="text" value="'.$page_grpsetname.'"/></p>';
		echo '<p><input type="submit" value="Rename" />';
		echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onClick=\"window.location='managestugrps.php?cid=$cid'\" /></p>";
		echo '</form>';
	} else if (isset($_GET['addgrp'])) {
		//add new group set
		echo '<h4>Add new student group</h4>';
		echo "<form method=\"post\" action=\"managestugrps.php?cid=$cid&grpsetid=$grpsetid&addgrp=true\">";
		if (isset($stulist)) {
			echo "<input type=\"hidden\" name=\"stutoadd\" value=\"$stulist\" />";
		}
		echo '<p>New group name: <input name="grpname" type="text" /></p>';
		echo '<p><input type="submit" value="Create" />';
		echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onClick=\"window.location='managestugrps.php?cid=$cid&grpsetid=$grpsetid'\" /></p>";
		echo '</form>';
	} else if (isset($_GET['delgrp'])) {
		echo '<h4>Delete student group</h4>';
		echo "<form method=\"post\" action=\"managestugrps.php?cid=$cid&grpsetid=$grpsetid&delgrp={$_GET['delgrp']}&confirm=true\" >";
		echo "<p>Are you SURE you want to delete the student group <b>$page_grpname</b>?</p>";
		echo "<p>Any wiki page content for this group will be deleted.</p>";
		echo '<p><input type="radio" name="delposts" value="1" checked="checked" /> Delete group forum posts ';
		echo '<input type="radio" name="delposts" value="0" /> Make group forum posts non-group-specific posts</p>';
		echo '<p><input type="submit" value="Yes, Delete"> ';
		//echo "<p><input type=button value=\"Yes, Delete\" onClick=\"window.location='managestugrps.php?cid=$cid&grpsetid=$grpsetid&delgrp={$_GET['delgrp']}&confirm=true'\" /> ";
		echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onClick=\"window.location='managestugrps.php?cid=$cid&grpsetid=$grpsetid'\" /></p>";
		echo '</form>';
		
	} else if (isset($_GET['rengrp'])) {
		echo '<h4>Rename student group</h4>';
		echo "<form method=\"post\" action=\"managestugrps.php?cid=$cid&grpsetid=$grpsetid&rengrp={$_GET['rengrp']}\">";
		echo '<p>New group name: <input name="grpname" type="text" value="'.$page_grpname.'"/></p>';
		echo '<p><input type="submit" value="Rename" />';
		echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onClick=\"window.location='managestugrps.php?cid=$cid&grpsetid=$grpsetid'\" /></p>";
		echo '</form>';
	} else if (isset($_GET['removeall'])) {
		echo '<h4>Remove ALL group members</h4>';
		echo "<p>Are you SURE you want to remove <b>ALL</b> members of the student group <b>$page_grpname</b>?</p>";
		echo "<p><input type=button value=\"Yes, Remove\" onClick=\"window.location='managestugrps.php?cid=$cid&grpsetid=$grpsetid&removeall={$_GET['removeall']}&confirm=true'\" /> ";
		echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onClick=\"window.location='managestugrps.php?cid=$cid&grpsetid=$grpsetid'\" /></p>";
	} else if (isset($_GET['remove']) && $_GET['grpid']) {
		echo '<h4>Remove group member</h4>';
		echo "<p>Are you SURE you want to remove <b>$page_stuname</b> from the student group <b>$page_grpname</b>?</p>";
		echo "<p><input type=button value=\"Yes, Remove\" onClick=\"window.location='managestugrps.php?cid=$cid&grpsetid=$grpsetid&grpid={$_GET['grpid']}&remove={$_GET['remove']}&confirm=true'\" /> ";
		echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onClick=\"window.location='managestugrps.php?cid=$cid&grpsetid=$grpsetid'\" /></p>";
	} else if (isset($_GET['grpsetid'])) {
		?>
		<script type="text/javascript">
		var picsize = 0;
		function rotatepics(el) {
			picsize = (picsize+1)%3;
			if (picsize==0) {
				$(el).html("<?php echo _('View Pictures'); ?>");
			} else if (picsize==1) {
				$(el).html("<?php echo _('View Big Pictures'); ?>");
			} else {
				$(el).html("<?php echo _('Hide Pictures'); ?>");
			}
			picshow(picsize);
		}
		function picshow(size) {
			if (size==0) {
				els = document.getElementById("myTable").getElementsByTagName("img");
				for (var i=0; i<els.length; i++) {
					els[i].style.display = "none";
				}
			} else {
				els = document.getElementById("myTable").getElementsByTagName("img");
				for (var i=0; i<els.length; i++) {
					els[i].style.display = "inline";
					if (els[i].getAttribute("src").match("userimg_sm")) {
						if (size==2) {
							els[i].setAttribute("src",els[i].getAttribute("src").replace("_sm","_"));
						}
					} else if (size==1) {
						els[i].setAttribute("src",els[i].getAttribute("src").replace("_","_sm"));
					}
				}
			}
		}
		</script>
		<?php
		$curdir = rtrim(dirname(__FILE__), '/\\');

		//groupset selected - list members
		echo "<h3>Managing groups in set $page_grpsetname</h3>";
		echo '<div id="myTable">';
		echo "<p><button type=\"button\" onclick=\"window.location.href='managestugrps.php?cid=$cid&grpsetid=$grpsetid&addgrp=true'\">"._('Add New Group').'</button> ';
		if (array_sum($hasuserimg)>0) {
			echo ' <button type="button" onclick="rotatepics(this)" >'._('View Pictures').'</button><br/>';
		}
		echo '</p>';
		
		if (count($page_grps)==0) {
			echo '<p>No student groups have been created yet</p>';
		}
		foreach ($page_grps as $grpid=>$grpname) {
			echo "<b>Group: $grpname</b> | ";
			echo "<a href=\"managestugrps.php?cid=$cid&grpsetid=$grpsetid&rengrp=$grpid\">Rename</a> | ";
			echo "<a href=\"managestugrps.php?cid=$cid&grpsetid=$grpsetid&delgrp=$grpid\">Delete</a> | ";
			echo "<a href=\"managestugrps.php?cid=$cid&grpsetid=$grpsetid&removeall=$grpid\">Remove all members</a>";
			echo '<ul>';
			if (count($page_grpmembers[$grpid])==0) {
				echo '<li>No group members</li>';
			} else {
				foreach ($page_grpmembers[$grpid] as $uid=>$name) {
					echo '<li>';
					if ($hasuserimg[$uid]==1) {
						if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
							echo "<img src=\"{$urlmode}s3.amazonaws.com/{$GLOBALS['AWSbucket']}/cfiles/userimg_sm{$uid}.jpg\" style=\"display:none;\"  />";
						} else {
							echo "<img src=\"$imasroot/course/files/userimg_sm{$uid}.jpg\" style=\"display:none;\"  />";
						}
					}
					echo "$name | <a href=\"managestugrps.php?cid=$cid&grpsetid=$grpsetid&remove=$uid&grpid=$grpid\">Remove from group</a></li>";
				}
			}
			echo '</ul>';
		}
			
		
		echo '<h3>Students not in a group yet</h3>';
		if (count($page_ungrpstu)>0) {
			echo "<form method=\"post\" action=\"managestugrps.php?cid=$cid&grpsetid=$grpsetid&addstutogrp=true\">";
			echo 'With selected, add to group ';
			echo '<select name="addtogrpid">';
			echo "<option value=\"--new--\">New Group</option>";
			foreach ($page_grps as $grpid=>$grpname) {
				echo "<option value=\"$grpid\">$grpname</option>";
			}
			echo '</select>';
			echo '<input type="submit" value="Add"/>';
			echo '<ul class="nomark">';
			foreach ($page_ungrpstu as $uid=>$name) {
				echo "<li><input type=\"checkbox\" name=\"stutoadd[]\" value=\"$uid\" />";
				if ($hasuserimg[$uid]==1) {
					if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
						echo "<img src=\"{$urlmode}s3.amazonaws.com/{$GLOBALS['AWSbucket']}/cfiles/userimg_sm{$uid}.jpg\" style=\"display:none;\"  />";
					} else {
						echo "<img src=\"$imasroot/course/files/userimg_sm{$uid}.jpg\" style=\"display:none;\"  />";
					}
				}
				echo "$name</li>";
			}
			echo '</ul>';
			echo '</form>';
			echo '<p>&nbsp;</p>';
		} else {
			echo '<p>None</p>';
		}
		echo '</div>';
	} else {
		//list all groups
		echo '<h4>Student Group Sets</h4>';
		if (count($page_groupsets)==0) {
			echo '<p>No existing sets of groups</p>';
		} else {
			echo '<p>Select a set of groups to modify the groups in that set</p>';
			echo '<table><tbody><tr>';
			foreach ($page_groupsets as $gs) {
				echo "<td><a href=\"managestugrps.php?cid=$cid&grpsetid={$gs[0]}\">{$gs[1]}</a></td><td class=small>";
				echo "<a href=\"managestugrps.php?cid=$cid&rengrpset={$gs[0]}\">Rename</a> | ";
				echo "<a href=\"managestugrps.php?cid=$cid&copygrpset={$gs[0]}\">Copy</a> | ";
				echo "<a href=\"managestugrps.php?cid=$cid&delgrpset={$gs[0]}\">Delete</a>";
				
				echo '</td></tr>';
			}
			echo '</body></table>';
		}
		
		echo '<p><button type="button" onclick="window.location.href=\'managestugrps.php?cid='.$cid.'&addgrpset=ask\'">'._('Add new set of groups').'</button></p>';
	}
	
}

require("../footer.php");

?>

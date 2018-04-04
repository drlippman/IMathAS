<?php
//IMathAS:  Manage student groups
//(c) 2010 David Lippman

/*** master php includes *******/
require("../init.php");
require("../includes/htmlutil.php");
require("../includes/stugroups.php");
require_once("../includes/filehandler.php");

/*** pre-html data manipulation, including function code *******/
$cid = Sanitize::courseId($_GET['cid']);
if ( isset($_GET['grpsetid'])) {
	$grpsetid =  Sanitize::onlyInt($_GET['grpsetid']);
}

//set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = "Manage Student Groups";
$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";

if (!(isset($teacherid))) { // loaded by a NON-teacher
	$overwriteBody=1;
	$body = "You need to log in as a teacher to access this page";
} else {
	if (isset($_GET['addgrp']) && isset($_POST['grpname']) && isset($_GET['grpsetid'])) {
		//adding a group.  Could be a "add new group" only, or adding a new group while assigning students
		if (trim($_POST['grpname'])=='') {
			$_POST['grpname'] = 'Unnamed group';
		}
		//DB $query = "INSERT INTO imas_stugroups (groupsetid,name) VALUES ('$grpsetid','{$_POST['grpname']}')";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		$stm = $DBH->prepare("INSERT INTO imas_stugroups (groupsetid,name) VALUES (:groupsetid, :name)");
		$stm->execute(array(':groupsetid'=>$grpsetid, ':name'=>$_POST['grpname']));
		if (!isset($_POST['stutoadd'])) { //if not adding students also
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/managestugrps.php?cid=$cid&grpsetid=" . Sanitize::encodeUrlParam($_GET['grpsetid']) . "&r=" . Sanitize::randomQueryStringParam());
			exit();
		} else {
			//DB $_POST['addtogrpid'] = mysql_insert_id();
			$_POST['addtogrpid'] = $DBH->lastInsertId();
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
			//DB $query = "INSERT INTO imas_stugroupset (name,courseid) VALUES ('{$_POST['grpsetname']}','$cid')";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("INSERT INTO imas_stugroupset (name,courseid) VALUES (:name, :courseid)");
			$stm->execute(array(':name'=>$_POST['grpsetname'], ':courseid'=>$cid));
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/managestugrps.php?cid=$cid" . "&r=" . Sanitize::randomQueryStringParam());
			exit();
		}
		$curBreadcrumb .= " &gt; <a href=\"managestugrps.php?cid=$cid\">Manage Student Groups</a> &gt; Add Group Set";
	} else if (isset($_GET['delgrpset'])) {
		$deleteGroupSet = sanitize::onlyInt($_GET['delgrpset']);
		//deleting groupset
		if (isset($_POST['confirm'])) {
			//if name is set
			deletegroupset($deleteGroupSet, $cid);
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/managestugrps.php?cid=$cid");
			exit();
		} else {
			//DB $query = "SELECT name FROM imas_stugroupset WHERE id='{$_GET['delgrpset']}'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $page_grpsetname = mysql_result($result,0,0);
			$stm = $DBH->prepare("SELECT name FROM imas_stugroupset WHERE id=:id AND courseid=:courseid");
			$stm->execute(array(':id'=>$deleteGroupSet, ':courseid'=>$cid));
			$page_grpsetname = $stm->fetchColumn(0);
		}

		$curBreadcrumb .= " &gt; <a href=\"managestugrps.php?cid=$cid\">Manage Student Groups</a> &gt; Delete Group Set";
	} else if (isset($_GET['rengrpset'])) {
		$renameGrpSet = sanitize::onlyInt($_GET['rengrpset']);
		//renaming groupset
		if (isset($_POST['grpsetname'])) {
			//if name is set
			//DB $query = "UPDATE imas_stugroupset SET name='{$_POST['grpsetname']}' WHERE id='{$_GET['rengrpset']}'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("UPDATE imas_stugroupset SET name=:name WHERE id=:id");
			$stm->execute(array(':name'=>$_POST['grpsetname'], ':id'=>$renameGrpSet)); //formerly ':id'=>$_GET['rengrpset']
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/managestugrps.php?cid=$cid" . "&r=" . Sanitize::randomQueryStringParam());
			exit();
		} else {
			//DB $query = "SELECT name FROM imas_stugroupset WHERE id='{$_GET['rengrpset']}'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $page_grpsetname = mysql_result($result,0,0);
			$stm = $DBH->prepare("SELECT name FROM imas_stugroupset WHERE id=:id AND courseid=:courseid");
			$stm->execute(array(':id'=>$renameGrpSet, ':courseid'=>$cid));
			$page_grpsetname = $stm->fetchColumn(0);
		}
		$curBreadcrumb .= " &gt; <a href=\"managestugrps.php?cid=$cid\">Manage Student Groups</a> &gt; Rename Group Set";
	} else if (isset($_GET['copygrpset'])) {
		//copying groupset
		$copygrpset = Sanitize::onlyInt($_GET['copygrpset']);
		//DB $query = "SELECT name FROM imas_stugroupset WHERE id='{$_GET['copygrpset']}'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $grpsetname = addslashes(mysql_result($result,0,0)) . ' (copy)';
		$stm = $DBH->prepare("SELECT name FROM imas_stugroupset WHERE id=:id");
		$stm->execute(array(':id'=>$copygrpset));
		$grpsetname = $stm->fetchColumn(0) . ' (copy)';

		//DB $query = "INSERT INTO imas_stugroupset (name,courseid) VALUES ('$grpsetname','$cid')";
		//DB mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $newgrpset = mysql_insert_id();
		$stm = $DBH->prepare("INSERT INTO imas_stugroupset (name,courseid) VALUES (:name, :courseid)");
		$stm->execute(array(':name'=>$grpsetname, ':courseid'=>$cid));
		$newgrpset = $DBH->lastInsertId();

		//DB $query = "SELECT id,name FROM imas_stugroups WHERE groupsetid='{$_GET['copygrpset']}'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB while ($row = mysql_fetch_row($result)) {
		$stm = $DBH->prepare("SELECT id,name FROM imas_stugroups WHERE groupsetid=:groupsetid");
		$stm->execute(array(':groupsetid'=>$copygrpset));
		$ins_grp_stm = $DBH->prepare("INSERT INTO imas_stugroups (name,groupsetid) VALUES (:name, :groupsetid)");
		$sel_grpmem_stm = $DBH->prepare("SELECT userid FROM imas_stugroupmembers WHERE stugroupid=:stugroupid");

		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			//DB $row[1] = addslashes($row[1]);
			//DB $query = "INSERT INTO imas_stugroups (name,groupsetid) VALUES ('{$row[1]}',$newgrpset)";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $newstugrp = mysql_insert_id();
			$ins_grp_stm->execute(array(':name'=>$row[1], ':groupsetid'=>$newgrpset));
			$newstugrp = $DBH->lastInsertId();
			$toadd = array();
			//DB $query = "SELECT userid FROM imas_stugroupmembers WHERE stugroupid='{$row[0]}'";
			//DB $r2 = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB while ($sgm = mysql_fetch_row($r2)) {
			$sel_grpmem_stm->execute(array(':stugroupid'=>$row[0]));
			$toaddval = array();
			while ($sgm = $sel_grpmem_stm->fetch(PDO::FETCH_NUM)) {
				//DB $toadd[] = "('{$sgm[0]}',$newstugrp)";
				$toadd[] = "(?,?)";
				array_push($toaddval, $sgm[0], $newstugrp);
			}
			if (count($toadd)>0) {
				//DB $query = "INSERT INTO imas_stugroupmembers (userid,stugroupid) VALUES ".implode(',',$toadd);
				//DB mysql_query($query) or die("Query failed : " . mysql_error());
				$ins_grpmem_stm = $DBH->prepare("INSERT INTO imas_stugroupmembers (userid,stugroupid) VALUES ".implode(',',$toadd));
				$ins_grpmem_stm->execute($toaddval);
			}
		}
		header('Location: ' . $GLOBALS['basesiteurl'] . "/course/managestugrps.php?cid=$cid" . "&r=" . Sanitize::randomQueryStringParam());
		exit();
	} else if (isset($_GET['addstutogrp']) && !empty($_POST['stutoadd'])) {
		//submitting list of students to add to a group
		$stustoadd = $_POST['stutoadd'];
		if ($_POST['addtogrpid']=='--new--') {
			//adding a new group; need to ask for group
			$_GET['addgrp'] = true;
			$stulist = implode(',',$stustoadd);
		} else {
			$grpid = Sanitize::onlyInt($_POST['addtogrpid']);
			$loginfo = "instr adding stu to group $grpid. ";
			if (!is_array($stustoadd)) {
				$stustoadd = explode(',',$stustoadd);
			}

			$alreadygroupedstu = array();
			//DB $stulist = "'".implode("','",$stustoadd)."'";
			$stulist = implode(',', array_map('intval', $stustoadd));
			//DB $query = "SELECT i_sgm.userid FROM imas_stugroupmembers as i_sgm JOIN imas_stugroups as i_sg ON i_sgm.stugroupid=i_sg.id ";
			//DB $query .= "WHERE i_sg.groupsetid='$grpsetid' AND i_sgm.userid IN ($stulist)";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB while ($row = mysql_fetch_row($result)) {
			$query = "SELECT i_sgm.userid FROM imas_stugroupmembers as i_sgm JOIN imas_stugroups as i_sg ON i_sgm.stugroupid=i_sg.id ";
			$query .= "WHERE i_sg.groupsetid=:groupsetid AND i_sgm.userid IN ($stulist)";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':groupsetid'=>$grpsetid));
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$alreadygroupedstu[] = $row[0];
			}
			$stustoadd = array_diff($stustoadd,$alreadygroupedstu);

			$existinggrpmembers = array();
			//DB $query = "SELECT userid FROM imas_stugroupmembers WHERE stugroupid='$grpid'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB while ($row = mysql_fetch_row($result)) {
			$stm = $DBH->prepare("SELECT userid FROM imas_stugroupmembers WHERE stugroupid=:stugroupid");
			$stm->execute(array(':stugroupid'=>$grpid));
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$existinggrpmembers[] = $row[0];
			}

			if (count($stustoadd)>0) {
				$insarr = array();
				$query = 'INSERT INTO imas_stugroupmembers (stugroupid,userid) VALUES ';
				for ($i=0;$i<count($stustoadd);$i++) {
					if ($i>0) {$query .= ',';};
					//DB $query .= "('$grpid','{$stustoadd[$i]}')";
					$query .= "(?,?)";
					array_push($insarr, $grpid, $stustoadd[$i]);
					$loginfo .= "adding {$stustoadd[$i]}.";
				}
				$stm = $DBH->prepare($query);
				$stm->execute($insarr);
				//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());

				//DB already set above $stulist = "'".implode("','",$stustoadd)."'";
				//DB $query = "SELECT id FROM imas_assessments WHERE groupsetid='$grpsetid'";
				//DB $resultaid = mysql_query($query) or die("Query failed : " . mysql_error());
				//DB while (($aid = mysql_fetch_row($resultaid)) && $grpsetid>0) {
				$stm = $DBH->prepare("SELECT id FROM imas_assessments WHERE groupsetid=:groupsetid");
				$stm->execute(array(':groupsetid'=>$grpsetid));
				while (($aid = $stm->fetch(PDO::FETCH_NUM)) && $grpsetid>0) {
					//if asid exists for this grpid, need to update students.
					//if no asid exists already, but the students we're adding have one, use one (which?) of theirs
					//otherwise do nothing
					$fieldstocopy = 'assessmentid,agroupid,questions,seeds,scores,attempts,lastanswers,starttime,endtime,bestseeds,bestattempts,bestscores,bestlastanswers,feedback,reviewseeds,reviewattempts,reviewscores,reviewlastanswers,reattempting,reviewreattempting,timeontask,ver';
					$rowgrptest = '';
					//DB $query = "SELECT $fieldstocopy ";
					//DB $query .= "FROM imas_assessment_sessions WHERE agroupid='$grpid' AND assessmentid='{$aid[0]}'";
					//DB $result = mysql_query($query) or die("Query failed : $query:" . mysql_error());
					//DB if (mysql_num_rows($result)>0) {
					$query = "SELECT $fieldstocopy ";
					$query .= "FROM imas_assessment_sessions WHERE agroupid=:agroupid AND assessmentid=:assessmentid";
					$stm2 = $DBH->prepare($query);
					$stm2->execute(array(':agroupid'=>$grpid, ':assessmentid'=>$aid[0]));
					if ($stm2->rowCount()>0) {
						//asid already exists for group - use it
						//DB $rowgrptest = addslashes_deep(mysql_fetch_row($result));
						$rowgrptest = $stm2->fetch(PDO::FETCH_ASSOC);
						$grpasidexists = true;
					} else {
						//use asid from first student assessment
						$grpasidexists = false;
						//DB $query = "SELECT id,$fieldstocopy ";
						//DB $query .= "FROM imas_assessment_sessions WHERE userid IN ($stulist) AND assessmentid='{$aid[0]}'";
						//DB $result = mysql_query($query) or die("Query failed : $query:" . mysql_error());
						//DB if (mysql_num_rows($result)>0) {
						$query = "SELECT id,$fieldstocopy ";
						$query .= "FROM imas_assessment_sessions WHERE userid IN ($stulist) AND assessmentid=:assessmentid";
						$stm2 = $DBH->prepare($query);
						$stm2->execute(array(':assessmentid'=>$aid[0]));
						if ($stm2->rowCount()>0) {
							//DB $row = mysql_fetch_row($result);
							$rowgrptest = $stm2->fetch(PDO::FETCH_ASSOC);
							//DB $srcasid = array_shift($row);
							//DB $rowgrptest = addslashes_deep($row);
							$srcasid = $rowgrptest['id'];
							unset($rowgrptest['id']);
							//DB $rowgrptest[1] = $grpid; //use new groupid
							$rowgrptest['agroupid'] = $grpid;
							//DB while ($row = mysql_fetch_row($result)) {
							while ($row = $stm2->fetch(PDO::FETCH_ASSOC)) {
								deleteasidfilesfromstring2($row['lastanswers'].$row['bestlastanswers'],'id',$row['id'],$row['assessmentid']);
							}
						}
					}
					if ($rowgrptest != '') {  //if an assessment session already exists
						$fieldstocopyarr = explode(',',$fieldstocopy);
						//DB $insrow = "'".implode("','",$rowgrptest)."'";
						$insrow = ":".implode(',:',$fieldstocopyarr);
						if ($grpasidexists==false) {
							//asid coming from added group member.  Also copy to any existing group members
							$stustoadd = array_merge($stustoadd,$existinggrpmembers);
						}
						foreach ($stustoadd as $stuid) {
							//DB $query = "SELECT id,agroupid FROM imas_assessment_sessions WHERE userid='$stuid' AND assessmentid={$aid[0]}";
							//DB $result = mysql_query($query) or die("Query failed : $query:" . mysql_error());
							//DB if (mysql_num_rows($result)>0) {
							$stm2 = $DBH->prepare("SELECT id,agroupid FROM imas_assessment_sessions WHERE userid=:userid AND assessmentid=:assessmentid");
							$stm2->execute(array(':userid'=>$stuid, ':assessmentid'=>$aid[0]));
							if ($stm2->rowCount()>0) {
								$loginfo .= "updating ias for $stuid.";
								//DB $row = mysql_fetch_row($result);
								$row = $stm2->fetch(PDO::FETCH_NUM);
								$sets = array();
								foreach ($fieldstocopyarr as $k=>$val) {
									//DB $sets[] = "$val='{$rowgrptest[$k]}'";
									$sets[] = "$val=:$val";
								}
								$setslist = implode(',',$sets);
								//DB $query = "UPDATE imas_assessment_sessions SET $setslist WHERE id='{$row[0]}'";
								$stm2 = $DBH->prepare("UPDATE imas_assessment_sessions SET $setslist WHERE id=:id");
								$stm2->execute(array(':id'=>$row[0]) + $rowgrptest);
								//$query = "UPDATE imas_assessment_sessions SET assessmentid='{$rowgrptest[0]}',agroupid='{$rowgrptest[1]}',questions='{$rowgrptest[2]}'";
								//$query .= ",seeds='{$rowgrptest[3]}',scores='{$rowgrptest[4]}',attempts='{$rowgrptest[5]}',lastanswers='{$rowgrptest[6]}',";
								//$query .= "starttime='{$rowgrptest[7]}',endtime='{$rowgrptest[8]}',bestseeds='{$rowgrptest[9]}',bestattempts='{$rowgrptest[10]}',";
								//$query .= "bestscores='{$rowgrptest[11]}',bestlastanswers='{$rowgrptest[12]}'  WHERE id='{$row[0]}'";
								//$query = "UPDATE imas_assessment_sessions SET agroupid='$agroupid' WHERE id='{$row[0]}'";
								//DB mysql_query($query) or die("Query failed : $query:" . mysql_error());
							} else {
								$loginfo .= "inserting ias for $stuid.";
								//DB $query = "INSERT INTO imas_assessment_sessions (userid,$fieldstocopy) ";
								//DB $query .= "VALUES ('$stuid',$insrow)";
								//DB mysql_query($query) or die("Query failed : $query:" . mysql_error());
								$query = "INSERT INTO imas_assessment_sessions (userid,$fieldstocopy) ";
								$query .= "VALUES (:stuid,$insrow)";
								$stm2 = $DBH->prepare($query);
								$stm2->execute(array(':stuid'=>$stuid) + $rowgrptest);
							}
						}
					}
				}
			}
			if (count($alreadygroupedstu)>0) {
				require("../header.php");
				echo '<p>Some students joined a group already and were skipped:</p><p>';
				//DB $stulist = "'".implode("','",$alreadygroupedstu)."'";
				$stulist = array_map('Sanitize::onlyInt', $alreadygroupedstu);
				//DB $query = "SELECT FirstName,LastName FROM imas_users WHERE id IN ($stulist) ORDER BY LastName, FirstName";
				//DB $result = mysql_query($query) or die("Query failed : $query:" . mysql_error());
				//DB while ($row = mysql_fetch_row($result)) {
				$query_placeholders = Sanitize::generateQueryPlaceholders($stulist);
				$stm = $DBH->prepare("SELECT FirstName,LastName FROM imas_users WHERE id IN ($query_placeholders) ORDER BY LastName, FirstName");
				$stm->execute($stulist);
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					echo Sanitize::encodeStringForDisplay($row[1]).', '.Sanitize::encodeStringForDisplay($row[0]).'<br/>';
					$loginfo .= $row[1].', '.$row[0].' already in group.';
				}
				echo "<p><a href=\"managestugrps.php?cid=$cid&grpsetid=" . Sanitize::encodeUrlParam($_GET['grpsetid']) . "\">Continue</a></p>";
				require("../footer.php");
				$now = time();
				if (isset($GLOBALS['CFG']['log'])) {
					//DB $query = "INSERT INTO imas_log (time,log) VALUES ($now,'".addslashes($loginfo)."')";
					//DB mysql_query($query) or die("Query failed : " . mysql_error());
					$stm = $DBH->prepare("INSERT INTO imas_log (time,log) VALUES (:time, :log)");
					$stm->execute(array(':time'=>$now, ':log'=>$loginfo));
				}
			} else {
				$now = time();
				if (isset($GLOBALS['CFG']['log'])) {
					//DB $query = "INSERT INTO imas_log (time,log) VALUES ($now,'".addslashes($loginfo)."')";
					//DB mysql_query($query) or die("Query failed : " . mysql_error());
					$stm = $DBH->prepare("INSERT INTO imas_log (time,log) VALUES (:time, :log)");
					$stm->execute(array(':time'=>$now, ':log'=>$loginfo));
				}
				header('Location: ' . $GLOBALS['basesiteurl'] . "/course/managestugrps.php?cid=$cid&grpsetid=" . Sanitize::encodeUrlParam($_GET['grpsetid']) . "&r=" . Sanitize::randomQueryStringParam());
			}
			exit();
		}

	} else if (isset($_GET['addgrp'])) {
		//DB $query = "SELECT name FROM imas_stugroupset WHERE id='$grpsetid'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $page_grpsetname = mysql_result($result,0,0);
		$stm = $DBH->prepare("SELECT name FROM imas_stugroupset WHERE id=:id");
		$stm->execute(array(':id'=>$grpsetid));
		$page_grpsetname = $stm->fetchColumn(0);
		$curBreadcrumb .= " &gt; <a href=\"managestugrps.php?cid=$cid\">Manage Student Groups</a> &gt; <a href=\"managestugrps.php?cid=$cid&grpsetid=$grpsetid\">".Sanitize::encodeStringForDisplay($page_grpsetname)."</a> &gt; Add Group";
	} else if (isset($_GET['delgrp'])) {
		//deleting groupset
		$delgrp = sanitize::onlyInt($_GET['delgrp']);
		if (isset($_GET['confirm']) && isset($_POST['delposts'])) {
			//if name is set
			deletegroup($_GET['delgrp'], $_POST['delposts']==1);
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/managestugrps.php?cid=$cid&grpsetid=$grpsetid" . "&r=" . Sanitize::randomQueryStringParam());
			exit();
		} else {
			//DB $query = "SELECT name FROM imas_stugroups WHERE id='{$_GET['delgrp']}'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $page_grpname = mysql_result($result,0,0);
			$stm = $DBH->prepare("SELECT name FROM imas_stugroups WHERE id=:id");
			$stm->execute(array(':id'=>$delgrp));
			$page_grpname = $stm->fetchColumn(0);
			//DB $query = "SELECT name FROM imas_stugroupset WHERE id='$grpsetid'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $page_grpsetname = mysql_result($result,0,0);
			$stm = $DBH->prepare("SELECT name FROM imas_stugroupset WHERE id=:id");
			$stm->execute(array(':id'=>$grpsetid));
			$page_grpsetname = $stm->fetchColumn(0);
		}

		$curBreadcrumb .= " &gt; <a href=\"managestugrps.php?cid=$cid\">Manage Student Groups</a> &gt; <a href=\"managestugrps.php?cid=$cid&grpsetid=$grpsetid\">".Sanitize::encodeStringForDisplay($page_grpsetname)."</a> &gt; Delete Group";
	} else if (isset($_GET['rengrp'])) {
		//renaming groupset
		$renGrp = sanitize::onlyInt($_GET['rengrp']);
		if (isset($_POST['grpname'])) {
			//if name is set
			//DB $query = "UPDATE imas_stugroups SET name='{$_POST['grpname']}' WHERE id='{$_GET['rengrp']}'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->prepare("UPDATE imas_stugroups SET name=:name WHERE id=:id");
			$stm->execute(array(':name'=>$_POST['grpname'], ':id'=>$_GET['rengrp']));
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/managestugrps.php?cid=$cid&grpsetid=$grpsetid" . "&r=" . Sanitize::randomQueryStringParam());
			exit();
		} else {
			//DB $query = "SELECT name FROM imas_stugroups WHERE id='{$_GET['rengrp']}'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $page_grpname = mysql_result($result,0,0);
			$stm = $DBH->prepare("SELECT name FROM imas_stugroups WHERE id=:id");
			$stm->execute(array(':id'=>$renGrp));
			$page_grpname = $stm->fetchColumn(0);
			//DB $query = "SELECT name FROM imas_stugroupset WHERE id='$grpsetid'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $page_grpsetname = mysql_result($result,0,0);
			$stm = $DBH->prepare("SELECT name FROM imas_stugroupset WHERE id=:id");
			$stm->execute(array(':id'=>$grpsetid));
			$page_grpsetname = $stm->fetchColumn(0);
		}
		$curBreadcrumb .= " &gt; <a href=\"managestugrps.php?cid=$cid\">Manage Student Groups</a> &gt; <a href=\"managestugrps.php?cid=$cid&grpsetid=$grpsetid\">".Sanitize::encodeStringForDisplay($page_grpsetname)."</a> &gt; Rename Group";
	} else if (isset($_GET['removeall'])) {
		//removing all group members
		$removeall = sanitize::onlyInt($_GET['removeall']);
		if (isset($_POST['confirm'])) {
			//if name is set
			removeallgroupmembers($_GET['removeall']);
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/managestugrps.php?cid=$cid&grpsetid=$grpsetid" . "&r=" . Sanitize::randomQueryStringParam());
			exit();
		} else {
			//DB $query = "SELECT name FROM imas_stugroups WHERE id='{$_GET['removeall']}'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $page_grpname = mysql_result($result,0,0);
			$stm = $DBH->prepare("SELECT name FROM imas_stugroups WHERE id=:id");
			$stm->execute(array(':id'=>$removeall));
			$page_grpname = $stm->fetchColumn(0);
			//DB $query = "SELECT name FROM imas_stugroupset WHERE id='$grpsetid'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $page_grpsetname = mysql_result($result,0,0);
			$stm = $DBH->prepare("SELECT name FROM imas_stugroupset WHERE id=:id");
			$stm->execute(array(':id'=>$grpsetid));
			$page_grpsetname = $stm->fetchColumn(0);
		}
		$curBreadcrumb .= " &gt; <a href=\"managestugrps.php?cid=$cid\">Manage Student Groups</a> &gt; <a href=\"managestugrps.php?cid=$cid&grpsetid=$grpsetid\">".Sanitize::encodeStringForDisplay($page_grpsetname)."</a> &gt; Remove all group members";

	} else if (isset($_GET['remove']) && isset($_GET['grpid'])) {
		//removing one group member
		$removegrpid = sanitize::onlyInt($_GET['grpid']);
		$remove = sanitize::onlyInt($_GET['remove']);
		if (isset($_POST['confirm'])) {
			//if name is set
			removegroupmember($_GET['grpid'],$_GET['remove']);
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/managestugrps.php?cid=$cid&grpsetid=$grpsetid" . "&r=" . Sanitize::randomQueryStringParam());
			exit();
		} else {
			//DB $query = "SELECT LastName, FirstName FROM imas_users WHERE id='{$_GET['remove']}'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $page_stuname = mysql_result($result,0,0).', '.mysql_result($result,0,1);
			$stm = $DBH->prepare("SELECT LastName, FirstName FROM imas_users WHERE id=:id");
			$stm->execute(array(':id'=>$remove));
			$page_stuname = implode(', ', $stm->fetch(PDO::FETCH_NUM));
			//DB $query = "SELECT name FROM imas_stugroups WHERE id='{$_GET['grpid']}'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $page_grpname = mysql_result($result,0,0);
			$stm = $DBH->prepare("SELECT name FROM imas_stugroups WHERE id=:id");
			$stm->execute(array(':id'=>$removegrpid));
			$page_grpname = $stm->fetchColumn(0);
			//DB $query = "SELECT name FROM imas_stugroupset WHERE id='$grpsetid'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $page_grpsetname = mysql_result($result,0,0);
			$stm = $DBH->prepare("SELECT name FROM imas_stugroupset WHERE id=:id");
			$stm->execute(array(':id'=>$grpsetid));
			$page_grpsetname = $stm->fetchColumn(0);
		}
		$curBreadcrumb .= " &gt; <a href=\"managestugrps.php?cid=$cid\">Manage Student Groups</a> &gt; <a href=\"managestugrps.php?cid=$cid&grpsetid=$grpsetid\">".Sanitize::encodeStringForDisplay($page_grpsetname)."</a> &gt; Remove group member";

	} else if (isset($_GET['grpsetid'])) {
		//groupset selected, show groups
		$grpsetid = Sanitize::onlyInt($_GET['grpsetid']);
		//DB $query = "SELECT name FROM imas_stugroupset WHERE id='$grpsetid'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $page_grpsetname = mysql_result($result,0,0);
		$stm = $DBH->prepare("SELECT name FROM imas_stugroupset WHERE id=:id");
		$stm->execute(array(':id'=>$grpsetid));
		$page_grpsetname = $stm->fetchColumn(0);

		//$page_grps will be an array, groupid=>name
		$page_grps = array();
		$page_grpmembers = array();
		$grpnums = 1;
		//DB $query = "SELECT id,name FROM imas_stugroups WHERE groupsetid='$grpsetid' ORDER BY id";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB while ($row = mysql_fetch_row($result)) {
		$stm = $DBH->prepare("SELECT id,name FROM imas_stugroups WHERE groupsetid=:groupsetid ORDER BY id");
		$stm->execute(array(':groupsetid'=>$grpsetid));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			if ($row[1] == 'Unnamed group') {
				$row[1] .= " $grpnums";
				$grpnums++;
			}
			$page_grps[$row[0]] = $row[1];
			$page_grpmembers[$row[0]] = array();
		}
		$grpids = implode(',',array_keys($page_grps));

		natsort($page_grps);

		//DB $query = "SELECT DISTINCT section FROM imas_students WHERE imas_students.courseid='$cid' AND imas_students.section IS NOT NULL ORDER BY section";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB if (mysql_num_rows($result)>1) {
		$stm = $DBH->prepare("SELECT DISTINCT section FROM imas_students WHERE imas_students.courseid=:courseid AND imas_students.section IS NOT NULL ORDER BY section");
		$stm->execute(array(':courseid'=>$cid));
		if ($stm->rowCount()>1) {
			$hassection = true;
		} else {
			$hassection = false;
		}
		if ($hassection) {
			//DB $query = "SELECT usersort FROM imas_gbscheme WHERE courseid='$cid'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $row = mysql_fetch_row($result);
			$stm = $DBH->prepare("SELECT usersort FROM imas_gbscheme WHERE courseid=:courseid");
			$stm->execute(array(':courseid'=>$cid));
			$row = $stm->fetch(PDO::FETCH_NUM);
			$sectionsort = ($row[0]==0);
		} else {
			$sectionsort = false;
		}

		//get all students
		$stunames = array();
		$hasuserimg = array();
		$stulocked = array();
		//DB $query = "SELECT iu.id,iu.FirstName,iu.LastName,iu.hasuserimg,imas_students.section,imas_students.locked FROM imas_users AS iu JOIN imas_students ON iu.id=imas_students.userid WHERE imas_students.courseid='$cid'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB while ($row = mysql_fetch_row($result)) {
		$stm = $DBH->prepare("SELECT iu.id,iu.FirstName,iu.LastName,iu.hasuserimg,imas_students.section,imas_students.locked FROM imas_users AS iu JOIN imas_students ON iu.id=imas_students.userid WHERE imas_students.courseid=:courseid");
		$stm->execute(array(':courseid'=>$cid));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			if ($sectionsort) {
				$stunames[$row[0]] = '<span class="small">(Section '.Sanitize::encodeStringForDisplay($row[4]).')</span> '.Sanitize::encodeStringForDisplay($row[2]).', '.Sanitize::encodeStringForDisplay($row[1]);
			} else if ($hassection) {
				$stunames[$row[0]] = Sanitize::encodeStringForDisplay($row[2]).', '.Sanitize::encodeStringForDisplay($row[1]).' <span class="small">(Section '.Sanitize::encodeStringForDisplay($row[4]).')</span>';
			} else {
				$stunames[$row[0]] = Sanitize::encodeStringForDisplay($row[2]).', '.Sanitize::encodeStringForDisplay($row[1]);
			}
			$hasuserimg[$row[0]] = $row[3];
			$stulocked[$row[0]] = $row[5];
		}

		//$page_grpmembers will be groupid=>array(  userid=>stuname )
		$stuuseridsingroup = array();
		if (count($page_grps)>0) {
			//DB $query = "SELECT stugroupid,userid FROM imas_stugroupmembers WHERE stugroupid IN ($grpids)";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB while ($row = mysql_fetch_row($result)) {
			$stm = $DBH->query("SELECT stugroupid,userid FROM imas_stugroupmembers WHERE stugroupid IN ($grpids)"); //known INT from DB
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
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

		$curBreadcrumb .= " &gt; <a href=\"managestugrps.php?cid=$cid\">Manage Student Groups</a> &gt; " . Sanitize::encodeStringForDisplay($page_grpsetname);

	} else {
		//no groupset selected
		$page_groupsets = array();
		//DB $query = "SELECT id,name FROM imas_stugroupset WHERE courseid='$cid' ORDER BY name";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB while ($row = mysql_fetch_row($result)) {
		$stm = $DBH->prepare("SELECT id,name FROM imas_stugroupset WHERE courseid=:courseid ORDER BY name");
		$stm->execute(array(':courseid'=>$cid));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
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
		echo "<p>Are you SURE you want to delete the set of student groups <b>" . Sanitize::encodeStringForDisplay($page_grpsetname) . "</b> and all the groups contained within it? ";
		$used = '';
		//DB $query = "SELECT name FROM imas_assessments WHERE isgroup>0 AND groupsetid='{$_GET['delgrpset']}'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB while ($row = mysql_fetch_row($result)) {
		$stm = $DBH->prepare("SELECT name FROM imas_assessments WHERE isgroup>0 AND groupsetid=:groupsetid");
		$stm->execute(array(':groupsetid'=>$deleteGroupSet));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$used .= "Assessment: " . Sanitize::encodeStringForDisplay($row[0]) . "<br/>";
		}
		//DB $query = "SELECT name FROM imas_forums WHERE groupsetid='{$_GET['delgrpset']}'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB while ($row = mysql_fetch_row($result)) {
		$stm = $DBH->prepare("SELECT name FROM imas_forums WHERE groupsetid=:groupsetid");
		$stm->execute(array(':groupsetid'=>$deleteGroupSet));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$used .= "Forum: " . Sanitize::encodeStringForDisplay($row[0]) . "<br/>";
		}
		//DB $query = "SELECT name FROM imas_wikis WHERE groupsetid='{$_GET['delgrpset']}'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB while ($row = mysql_fetch_row($result)) {
		$stm = $DBH->prepare("SELECT name FROM imas_wikis WHERE groupsetid=:groupsetid");
		$stm->execute(array(':groupsetid'=>$deleteGroupSet));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$used .= "Wiki: " . Sanitize::encodeStringForDisplay($row[0]) . "<br/>";
		}
		if ($used != '') {
			echo '<p>This set of groups is currently used in the assessments, wikis, and/or forums below.  These items will be set to non-group if this group set is deleted</p><p>';
			echo "$used</p>";
		} else {
			echo '<p>This set of groups is not currently being used</p>';
		}
		$querystring = http_build_query(array('cid'=>$cid, 'delgrpset'=>$deleteGroupSet));
		echo "<form method=\"post\" action=\"managestugrps.php?$querystring\">";
		echo '<p><button type="submit" name="confirm" value="true">'._('Yes, Delete').'</button> ';
		echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onClick=\"window.location='managestugrps.php?cid=$cid'\" /></p>";
		echo '</form>';

	} else if (isset($_GET['rengrpset'])) {
		echo '<h4>Rename student group set</h4>';
		echo "<form method=\"post\" action=\"managestugrps.php?cid=$cid&rengrpset=" . Sanitize::encodeUrlParam($_GET['rengrpset']) . "\">";
		echo '<p>New group set name: <input name="grpsetname" type="text" value="'.Sanitize::encodeStringForDisplay($page_grpsetname).'"/></p>';
		echo '<p><input type="submit" value="Rename" />';
		echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onClick=\"window.location='managestugrps.php?cid=$cid'\" /></p>";
		echo '</form>';
	} else if (isset($_GET['addgrp'])) {
		//add new group set
		echo '<h4>Add new student group</h4>';
		echo "<form method=\"post\" action=\"managestugrps.php?cid=$cid&grpsetid=" . Sanitize::encodeUrlParam($grpsetid) . "&addgrp=true\">";
		if (isset($stulist)) {
			echo "<input type=\"hidden\" name=\"stutoadd\" value=\"" . Sanitize::encodeStringForDisplay($stulist) . "\" />";
		}
		echo '<p>New group name: <input name="grpname" type="text" /></p>';
		echo '<p><input type="submit" value="Create" />';
		echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onClick=\"window.location='managestugrps.php?cid=$cid&grpsetid=" . Sanitize::encodeStringForJavascript($grpsetid) . "'\" /></p>";
		echo '</form>';
	} else if (isset($_GET['delgrp'])) {
		echo '<h4>Delete student group</h4>';
		echo "<form method=\"post\" action=\"managestugrps.php?cid=$cid&grpsetid=" . Sanitize::encodeUrlParam($grpsetid) . "&delgrp=" . Sanitize::encodeUrlParam($_GET['delgrp']) . "&confirm=true\" >";
		echo "<p>Are you SURE you want to delete the student group <b>" . Sanitize::encodeStringForDisplay($page_grpname) . "</b>?</p>";
		echo "<p>Any wiki page content for this group will be deleted.</p>";
		echo '<p><input type="radio" name="delposts" value="1" checked="checked" /> Delete group forum posts ';
		echo '<input type="radio" name="delposts" value="0" /> Make group forum posts non-group-specific posts</p>';
		echo '<p><input type="submit" value="Yes, Delete"> ';
		//echo "<p><input type=button value=\"Yes, Delete\" onClick=\"window.location='managestugrps.php?cid=$cid&grpsetid=$grpsetid&delgrp={$_GET['delgrp']}&confirm=true'\" /> ";
		echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onClick=\"window.location='managestugrps.php?cid=$cid&grpsetid=" . Sanitize::encodeUrlParam($grpsetid) . "'\" /></p>";
		echo '</form>';

	} else if (isset($_GET['rengrp'])) {
		echo '<h4>Rename student group</h4>';
		echo "<form method=\"post\" action=\"managestugrps.php?cid=$cid&grpsetid=" . Sanitize::encodeUrlParam($grpsetid) . "&rengrp=" . Sanitize::encodeUrlParam($_GET['rengrp']) . "\">";
		echo '<p>New group name: <input name="grpname" type="text" value="'.Sanitize::encodeStringForDisplay($page_grpname).'"/></p>';
		echo '<p><input type="submit" value="Rename" />';
		echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onClick=\"window.location='managestugrps.php?cid=$cid&grpsetid=" . Sanitize::encodeStringForJavascript($grpsetid) . "'\" /></p>";
		echo '</form>';
	} else if (isset($_GET['removeall'])) {
		echo '<h4>Remove ALL group members</h4>';
		echo "<p>Are you SURE you want to remove <b>ALL</b> members of the student group <b>" . Sanitize::encodeStringForDisplay($page_grpname) . "</b>?</p>";
		
		$querystring = http_build_query(array('cid'=>$cid, 'grpsetid'=>$grpsetid, 'removeall'=>Sanitize::onlyInt($_GET['removeall'])));
		echo "<form method=\"post\" action=\"managestugrps.php?$querystring\">";
		echo '<p><button type="submit" name="confirm" value="true">'._('Yes, Remove').'</button> ';
		echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onClick=\"window.location='managestugrps.php?cid=$cid&grpsetid=" . Sanitize::encodeStringForJavascript($grpsetid) . "'\" /></p>";
		echo '</form>';
		
	} else if (isset($_GET['remove']) && $_GET['grpid']) {
		echo '<h4>Remove group member</h4>';
		echo "<p>Are you SURE you want to remove <b>" . Sanitize::encodeStringForDisplay($page_stuname) . "</b> from the student group <b>" . Sanitize::encodeStringForDisplay($page_grpname) . "</b>?</p>";
		
		$querystring = http_build_query(array('cid'=>$cid, 'grpsetid'=>$grpsetid, 'grpid'=>Sanitize::onlyInt($_GET['grpid']), 'remove'=>Sanitize::onlyInt($_GET['remove'])));
		echo "<form method=\"post\" action=\"managestugrps.php?$querystring\">";
		echo '<p><button type="submit" name="confirm" value="true">'._('Yes, Remove').'</button> ';
		echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onClick=\"window.location='managestugrps.php?cid=$cid&grpsetid=" . Sanitize::encodeStringForJavascript($grpsetid) . "'\" /></p>";
		echo '</form>';
		
		
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
		echo "<h3>Managing groups in set " . Sanitize::encodeStringForDisplay($page_grpsetname) . "</h3>";
		echo '<div id="myTable">';
		echo "<p><button type=\"button\" onclick=\"window.location.href='managestugrps.php?cid=$cid&grpsetid=" . Sanitize::encodeUrlParam($grpsetid) . "&addgrp=true'\">"._('Add New Group').'</button> ';
		if (array_sum($hasuserimg)>0) {
			echo ' <button type="button" onclick="rotatepics(this)" >'._('View Pictures').'</button><br/>';
		}
		echo '</p>';

		if (count($page_grps)==0) {
			echo '<p>No student groups have been created yet</p>';
		}
		foreach ($page_grps as $grpid=>$grpname) {
			echo "<b>Group: " . Sanitize::encodeStringForDisplay($grpname) . "</b> | ";
			echo "<a href=\"managestugrps.php?cid=$cid&grpsetid=" . Sanitize::encodeUrlParam($grpsetid) . "&rengrp=" . Sanitize::encodeUrlParam($grpid) . "\">Rename</a> | ";
			echo "<a href=\"managestugrps.php?cid=$cid&grpsetid=" . Sanitize::encodeUrlParam($grpsetid) . "&delgrp=" . Sanitize::encodeUrlParam($grpid) . "\">Delete</a> | ";
			echo "<a href=\"managestugrps.php?cid=$cid&grpsetid=" . Sanitize::encodeUrlParam($grpsetid) . "&removeall=" . Sanitize::encodeUrlParam($grpid) . "\">Remove all members</a>";
			echo '<ul>';
			if (count($page_grpmembers[$grpid])==0) {
				echo '<li>No group members</li>';
			} else {
				foreach ($page_grpmembers[$grpid] as $uid=>$name) {
					echo '<li>';
					if ($hasuserimg[$uid]==1) {
						if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
							echo "<img src=\"{$urlmode}{$GLOBALS['AWSbucket']}.s3.amazonaws.com/cfiles/userimg_sm{$uid}.jpg\" style=\"display:none;\" alt=\"User picture\" />";
						} else {
							echo "<img src=\"$imasroot/course/files/userimg_sm{$uid}.jpg\" style=\"display:none;\" alt=\"User picture\"/>";
						}
					}
					if ($stulocked[$uid]) {
						echo '<span class="greystrike">'.$name.'</span>';
					} else {
						echo $name;
					}
					echo " | <a href=\"managestugrps.php?cid=$cid&grpsetid=" . Sanitize::encodeUrlParam($grpsetid) . "&remove=" . Sanitize::onlyInt($uid) . "&grpid=" . Sanitize::encodeUrlParam($grpid) . "\">Remove from group</a></li>";
				}
			}
			echo '</ul>';
		}


		echo '<h3>Students not in a group yet</h3>';
		if (count($page_ungrpstu)>0) {
			echo "<form method=\"post\" action=\"managestugrps.php?cid=$cid&grpsetid=" . Sanitize::encodeUrlParam($grpsetid) . "&addstutogrp=true\">";
			echo 'With selected, add to group ';
			echo '<select name="addtogrpid">';
			echo "<option value=\"--new--\">New Group</option>";
			foreach ($page_grps as $grpid=>$grpname) {
				echo "<option value=\"" . Sanitize::encodeStringForDisplay($grpid) . "\">" . Sanitize::encodeStringForDisplay($grpname) . "</option>";
			}
			echo '</select>';
			echo '<input type="submit" value="Add"/>';
			echo '<ul class="nomark">';
			foreach ($page_ungrpstu as $uid=>$name) {
				echo "<li><input type=\"checkbox\" name=\"stutoadd[]\" value=\"".Sanitize::encodeStringForDisplay($uid)."\" id=\"chk".Sanitize::encodeStringForDisplay($uid)."\"/><label for=\"chk".Sanitize::encodeStringForDisplay($uid)."\">";
				if ($hasuserimg[$uid]==1) {
					if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
						echo "<img src=\"{$urlmode}{$GLOBALS['AWSbucket']}.s3.amazonaws.com/cfiles/userimg_sm{$uid}.jpg\" style=\"display:none;\" alt=\"User picture\"/>";
					} else {
						echo "<img src=\"$imasroot/course/files/userimg_sm{$uid}.jpg\" style=\"display:none;\" alt=\"User picture\"/>";
					}
				}
				if ($stulocked[$uid]) {
					echo '<span class="greystrike">'.$name.'</span>';
				} else {
					echo $name;
				}
				echo "<label></li>";
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
				echo "<td><a href=\"managestugrps.php?cid=$cid&grpsetid=" . Sanitize::encodeUrlParam($gs[0]) . "\">" . Sanitize::encodeStringForDisplay($gs[1]) . "</a></td><td class=small>";
				echo "<a href=\"managestugrps.php?cid=$cid&rengrpset=" . Sanitize::encodeUrlParam($gs[0]) . "\">Rename</a> | ";
				echo "<a href=\"managestugrps.php?cid=$cid&copygrpset=" . Sanitize::encodeUrlParam($gs[0]) . "\">Copy</a> | ";
				echo "<a href=\"managestugrps.php?cid=$cid&delgrpset=" . Sanitize::encodeUrlParam($gs[0]) . "\">Delete</a>";

				echo '</td></tr>';
			}
			echo '</body></table>';
		}

		echo '<p><button type="button" onclick="window.location.href=\'managestugrps.php?cid='.$cid.'&addgrpset=ask\'">'._('Add new set of groups').'</button></p>';
	}

}

require("../footer.php");

?>

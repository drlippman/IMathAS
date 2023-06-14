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
		$stm = $DBH->prepare("INSERT INTO imas_stugroups (groupsetid,name) VALUES (:groupsetid, :name)");
		$stm->execute(array(':groupsetid'=>$grpsetid, ':name'=>$_POST['grpname']));
		if (!isset($_POST['stutoadd'])) { //if not adding students also
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/managestugrps.php?cid=$cid&grpsetid=" . Sanitize::encodeUrlParam($_GET['grpsetid']) . "&r=" . Sanitize::randomQueryStringParam());
			exit();
		} else {
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
			$stm = $DBH->prepare("INSERT INTO imas_stugroupset (name,courseid) VALUES (:name, :courseid)");
			$stm->execute(array(':name'=>Sanitize::stripHtmlTags($_POST['grpsetname']), ':courseid'=>$cid));
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
			$stm = $DBH->prepare("UPDATE imas_stugroupset SET name=:name WHERE id=:id");
			$stm->execute(array(':name'=>Sanitize::stripHtmlTags($_POST['grpsetname']), ':id'=>$renameGrpSet)); //formerly ':id'=>$_GET['rengrpset']
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/managestugrps.php?cid=$cid" . "&r=" . Sanitize::randomQueryStringParam());
			exit();
		} else {
			$stm = $DBH->prepare("SELECT name FROM imas_stugroupset WHERE id=:id AND courseid=:courseid");
			$stm->execute(array(':id'=>$renameGrpSet, ':courseid'=>$cid));
			$page_grpsetname = $stm->fetchColumn(0);
		}
		$curBreadcrumb .= " &gt; <a href=\"managestugrps.php?cid=$cid\">Manage Student Groups</a> &gt; Rename Group Set";
	} else if (isset($_GET['copygrpset'])) {
		//copying groupset
		$copygrpset = Sanitize::onlyInt($_GET['copygrpset']);
		$stm = $DBH->prepare("SELECT name FROM imas_stugroupset WHERE id=:id");
		$stm->execute(array(':id'=>$copygrpset));
		$grpsetname = $stm->fetchColumn(0) . ' (copy)';
		$stm = $DBH->prepare("INSERT INTO imas_stugroupset (name,courseid) VALUES (:name, :courseid)");
		$stm->execute(array(':name'=>$grpsetname, ':courseid'=>$cid));
		$newgrpset = $DBH->lastInsertId();
		$stm = $DBH->prepare("SELECT id,name FROM imas_stugroups WHERE groupsetid=:groupsetid");
		$stm->execute(array(':groupsetid'=>$copygrpset));
		$ins_grp_stm = $DBH->prepare("INSERT INTO imas_stugroups (name,groupsetid) VALUES (:name, :groupsetid)");
		$sel_grpmem_stm = $DBH->prepare("SELECT userid FROM imas_stugroupmembers WHERE stugroupid=:stugroupid");

		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$ins_grp_stm->execute(array(':name'=>$row[1], ':groupsetid'=>$newgrpset));
			$newstugrp = $DBH->lastInsertId();
			$toadd = array();
			$sel_grpmem_stm->execute(array(':stugroupid'=>$row[0]));
			$toaddval = array();
			while ($sgm = $sel_grpmem_stm->fetch(PDO::FETCH_NUM)) {
				$toadd[] = "(?,?)";
				array_push($toaddval, $sgm[0], $newstugrp);
			}
			if (count($toadd)>0) {
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
			$stulist = implode(',', array_map('intval', $stustoadd));
			$query = "SELECT i_sgm.userid FROM imas_stugroupmembers as i_sgm JOIN imas_stugroups as i_sg ON i_sgm.stugroupid=i_sg.id ";
			$query .= "WHERE i_sg.groupsetid=:groupsetid AND i_sgm.userid IN ($stulist)";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':groupsetid'=>$grpsetid));
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$alreadygroupedstu[] = $row[0];
			}
			$stustoadd = array_diff($stustoadd,$alreadygroupedstu);

			$existinggrpmembers = array();
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
					$query .= "(?,?)";
					array_push($insarr, $grpid, $stustoadd[$i]);
					$loginfo .= "adding {$stustoadd[$i]}.";
				}
				$stm = $DBH->prepare($query);
				$stm->execute($insarr);
				$stm = $DBH->prepare("SELECT id,ver FROM imas_assessments WHERE groupsetid=:groupsetid");
				$stm->execute(array(':groupsetid'=>$grpsetid));
				while ((list($aid,$aver) = $stm->fetch(PDO::FETCH_NUM)) && $grpsetid>0) {
					//if asid exists for this grpid, need to update students.
					//if no asid exists already, but the students we're adding have one, use one (which?) of theirs
					//otherwise do nothing
					if ($aver>1) {
						$fieldstocopy = 'assessmentid,agroupid,timeontask,starttime,lastchange,score,status,scoreddata,practicedata,ver';
						$rowgrptest = '';
						$query = "SELECT $fieldstocopy ";
						$query .= "FROM imas_assessment_records WHERE agroupid=:agroupid AND assessmentid=:assessmentid";
					} else {
						$fieldstocopy = 'assessmentid,agroupid,questions,seeds,scores,attempts,lastanswers,starttime,endtime,bestseeds,bestattempts,bestscores,bestlastanswers,feedback,reviewseeds,reviewattempts,reviewscores,reviewlastanswers,reattempting,reviewreattempting,timeontask,ver';
						$rowgrptest = '';
						$query = "SELECT $fieldstocopy ";
						$query .= "FROM imas_assessment_sessions WHERE agroupid=:agroupid AND assessmentid=:assessmentid";
					}
					$stm2 = $DBH->prepare($query);
					$stm2->execute(array(':agroupid'=>$grpid, ':assessmentid'=>$aid));
					if ($stm2->rowCount()>0) {
						//asid already exists for group - use it
						$rowgrptest = $stm2->fetch(PDO::FETCH_ASSOC);
						$grpasidexists = true;
					} else {
						//use asid from first student assessment
						$grpasidexists = false;
						if ($aver > 1) {
							$query = "SELECT userid,$fieldstocopy ";
							$query .= "FROM imas_assessment_records WHERE userid IN ($stulist) AND assessmentid=:assessmentid";
						} else {
							$query = "SELECT id,$fieldstocopy ";
							$query .= "FROM imas_assessment_sessions WHERE userid IN ($stulist) AND assessmentid=:assessmentid";
						}
						$stm2 = $DBH->prepare($query);
						$stm2->execute(array(':assessmentid'=>$aid));
						if ($stm2->rowCount()>0) {
							// first student - grab their data to copy to others
							$rowgrptest = $stm2->fetch(PDO::FETCH_ASSOC);

							// remaining students: delete any files in their existing records
							// since we'll be overwriting them
							if ($aver > 1) {
								$otherstus = array_diff($stustoadd, array($rowgrptest['userid']));
								deleteAssess2FilesOnUnenroll($otherstus, array($aid), array($aid));
							} else {
								while ($row = $stm2->fetch(PDO::FETCH_ASSOC)) {
									deleteasidfilesfromstring2($row['lastanswers'].$row['bestlastanswers'],'id',$row['id'],$row['assessmentid']);
								}
							}
							unset($rowgrptest['id']);
							unset($rowgrptest['userid']);
							$rowgrptest['agroupid'] = $grpid;
						}
					}
					if ($rowgrptest != '') {  //if an assessment session already exists
						$fieldstocopyarr = explode(',',$fieldstocopy);
						$insrow = ":".implode(',:',$fieldstocopyarr);
						if ($grpasidexists==false) {
							//asid coming from added group member.  Also copy to any existing group members
							$stustoadd = array_merge($stustoadd,$existinggrpmembers);
						}
						foreach ($stustoadd as $stuid) {
							if ($aver > 1) {
								$stm2 = $DBH->prepare("SELECT agroupid FROM imas_assessment_records WHERE userid=:userid AND assessmentid=:assessmentid");
							} else {
								$stm2 = $DBH->prepare("SELECT id,agroupid FROM imas_assessment_sessions WHERE userid=:userid AND assessmentid=:assessmentid");
							}
							$stm2->execute(array(':userid'=>$stuid, ':assessmentid'=>$aid));
							if ($stm2->rowCount()>0) {
								$loginfo .= "updating ias for $stuid.";
								$row = $stm2->fetch(PDO::FETCH_NUM);
								$sets = array();
								foreach ($fieldstocopyarr as $k=>$val) {
									$sets[] = "$val=:$val";
								}
								$setslist = implode(',',$sets);
								if ($aver > 1) {
									$stm2 = $DBH->prepare("UPDATE imas_assessment_records SET $setslist WHERE userid=:userid AND assessmentid=:assessmentid");
									$stm2->execute(array(':userid'=>$stuid, ':assessmentid'=>$aid) + $rowgrptest);
								} else {
									$stm2 = $DBH->prepare("UPDATE imas_assessment_sessions SET $setslist WHERE id=:id");
									$stm2->execute(array(':id'=>$row[0]) + $rowgrptest);
								}
								//$query = "UPDATE imas_assessment_sessions SET assessmentid='{$rowgrptest[0]}',agroupid='{$rowgrptest[1]}',questions='{$rowgrptest[2]}'";
								//$query .= ",seeds='{$rowgrptest[3]}',scores='{$rowgrptest[4]}',attempts='{$rowgrptest[5]}',lastanswers='{$rowgrptest[6]}',";
								//$query .= "starttime='{$rowgrptest[7]}',endtime='{$rowgrptest[8]}',bestseeds='{$rowgrptest[9]}',bestattempts='{$rowgrptest[10]}',";
								//$query .= "bestscores='{$rowgrptest[11]}',bestlastanswers='{$rowgrptest[12]}'  WHERE id='{$row[0]}'";
								//$query = "UPDATE imas_assessment_sessions SET agroupid='$agroupid' WHERE id='{$row[0]}'";
							} else {
								$loginfo .= "inserting ias for $stuid.";
								if ($aver > 1) {
									$query = "INSERT INTO imas_assessment_records (userid,$fieldstocopy) ";
									$query .= "VALUES (:stuid,$insrow)";
								} else {
									$query = "INSERT INTO imas_assessment_sessions (userid,$fieldstocopy) ";
									$query .= "VALUES (:stuid,$insrow)";
								}
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
				$stulist = array_map('Sanitize::onlyInt', $alreadygroupedstu);
				$query_placeholders = Sanitize::generateQueryPlaceholders($stulist);
				$stm = $DBH->prepare("SELECT FirstName,LastName FROM imas_users WHERE id IN ($query_placeholders) ORDER BY LastName, FirstName");
				$stm->execute($stulist);
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				    echo '<span class="pii-full-name">';
					echo Sanitize::encodeStringForDisplay($row[1]).', '.Sanitize::encodeStringForDisplay($row[0]).'</span><br/>';
					$loginfo .= $row[1].', '.$row[0].' already in group.';
				}
				echo "<p><a href=\"managestugrps.php?cid=$cid&grpsetid=" . Sanitize::encodeUrlParam($_GET['grpsetid']) . "\">Continue</a></p>";
				require("../footer.php");
				$now = time();
				if (isset($GLOBALS['CFG']['log'])) {
					$stm = $DBH->prepare("INSERT INTO imas_log (time,log) VALUES (:time, :log)");
					$stm->execute(array(':time'=>$now, ':log'=>$loginfo));
				}
			} else {
				$now = time();
				if (isset($GLOBALS['CFG']['log'])) {
					$stm = $DBH->prepare("INSERT INTO imas_log (time,log) VALUES (:time, :log)");
					$stm->execute(array(':time'=>$now, ':log'=>$loginfo));
				}
				header('Location: ' . $GLOBALS['basesiteurl'] . "/course/managestugrps.php?cid=$cid&grpsetid=" . Sanitize::encodeUrlParam($_GET['grpsetid']) . "&r=" . Sanitize::randomQueryStringParam());
			}
			exit();
		}

	} else if (isset($_GET['addgrp'])) {
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
			$stm = $DBH->prepare("SELECT name FROM imas_stugroups WHERE id=:id");
			$stm->execute(array(':id'=>$delgrp));
			$page_grpname = $stm->fetchColumn(0);
			$stm = $DBH->prepare("SELECT name FROM imas_stugroupset WHERE id=:id");
			$stm->execute(array(':id'=>$grpsetid));
			$page_grpsetname = $stm->fetchColumn(0);
		}

		$curBreadcrumb .= " &gt; <a href=\"managestugrps.php?cid=$cid\">Manage Student Groups</a> &gt; <a href=\"managestugrps.php?cid=$cid&grpsetid=$grpsetid\">".Sanitize::encodeStringForDisplay($page_grpsetname)."</a> &gt; Delete Group";
	} else if (isset($_GET['addrandgrps'])) {
		if (isset($_POST['grpsize']) && intval($_POST['grpsize'])>0) {
			$stm = $DBH->prepare("SELECT id FROM imas_stugroups WHERE groupsetid=?");
			$stm->execute(array($grpsetid));
			if ($stm->rowCount()==0) { //check there's no existing groups;
				if (isset($_POST['inclocked'])) {
					$stm = $DBH->prepare("SELECT userid,section FROM imas_students WHERE courseid=?");
				} else {
					$stm = $DBH->prepare("SELECT userid,section FROM imas_students WHERE courseid=? AND locked=0");
				}
				$stm->execute(array($cid));
                $stuallsecs = [];
                while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
                    $sec = (!empty($_POST['sepsections'])) ? $row['section'] : 'all';
                    $stuallsecs[$sec][] = $row['userid'];
                }
                $groupnumcnt = 1;
                foreach ($stuallsecs as $sec=>$stus) {
                    shuffle($stus);
                    $n = count($stus);
                    $grpn = intval(Sanitize::onlyInt($_POST['grpsize']));
                    $rem = $n%$grpn;
                    if ($_POST['grpadj']==0 || ($_POST['grpadj']==2 && $rem>=$grpn/2)) {
                        $numgrps = ceil($n/$grpn);
                        if ($rem>0) {
                            $rem = $grpn - $rem;
                        }
                        $grpsize = array_fill(0, $numgrps, $grpn);
                        for ($i=0;$i<$rem;$i++) {
                            $grpsize[$i%$numgrps]--;  //reduce number in group
                        }
                    } else {
                        $numgrps = floor($n/$grpn);
                        $grpsize = array_fill(0, $numgrps, $grpn);
                        for ($i=0;$i<$rem;$i++) {
                            $grpsize[$i%$numgrps]++;  //increase number in group
                        }
                    }
                    $grpsize = array_reverse($grpsize);

                    $ins_grp_stm = $DBH->prepare("INSERT INTO imas_stugroups (groupsetid,name) VALUES (?,?)");
                    $ins_grpmem_stm = $DBH->prepare("INSERT INTO imas_stugroupmembers (userid,stugroupid) VALUES (?,?)");
                    $stucnt = 0;
                    for ($i=0;$i<$numgrps;$i++) {
                        $ins_grp_stm->execute(array($grpsetid, sprintf(_('Random Group %d'), $groupnumcnt)));
                        $newgrpid = $DBH->lastInsertId();
                        $groupnumcnt++;
                        for ($j=0;$j<$grpsize[$i];$j++) {
                            $ins_grpmem_stm->execute(array($stus[$stucnt], $newgrpid));
                            $stucnt++;
                        }
                    }
                }
			}
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/managestugrps.php?cid=$cid&grpsetid=$grpsetid" . "&r=" . Sanitize::randomQueryStringParam());
		} else {
			$stm = $DBH->prepare("SELECT name FROM imas_stugroupset WHERE id=:id");
			$stm->execute(array(':id'=>$grpsetid));
			$page_grpsetname = $stm->fetchColumn(0);

            $stm = $DBH->prepare("SELECT DISTINCT section FROM imas_students WHERE imas_students.courseid=:courseid AND imas_students.section IS NOT NULL ORDER BY section");
            $stm->execute(array(':courseid'=>$cid));
            $hassection = ($stm->rowCount()>1);

			$curBreadcrumb .= " &gt; <a href=\"managestugrps.php?cid=$cid\">Manage Student Groups</a> &gt; <a href=\"managestugrps.php?cid=$cid&grpsetid=$grpsetid\">".Sanitize::encodeStringForDisplay($page_grpsetname)."</a> &gt; Create Random Groups";
		}

	} else if (isset($_GET['rengrp'])) {
		//renaming groupset
		$renGrp = sanitize::onlyInt($_GET['rengrp']);
		if (isset($_POST['grpname'])) {
			//if name is set
			$stm = $DBH->prepare("UPDATE imas_stugroups SET name=:name WHERE id=:id");
			$stm->execute(array(':name'=>$_POST['grpname'], ':id'=>$_GET['rengrp']));
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/managestugrps.php?cid=$cid&grpsetid=$grpsetid" . "&r=" . Sanitize::randomQueryStringParam());
			exit();
		} else {
			$stm = $DBH->prepare("SELECT name FROM imas_stugroups WHERE id=:id");
			$stm->execute(array(':id'=>$renGrp));
			$page_grpname = $stm->fetchColumn(0);
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
			removeallgroupmembers($removeall);
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/managestugrps.php?cid=$cid&grpsetid=$grpsetid" . "&r=" . Sanitize::randomQueryStringParam());
			exit();
		} else {
			$stm = $DBH->prepare("SELECT name FROM imas_stugroups WHERE id=:id");
			$stm->execute(array(':id'=>$removeall));
			$page_grpname = $stm->fetchColumn(0);
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
			$stm = $DBH->prepare("SELECT LastName, FirstName FROM imas_users WHERE id=:id");
			$stm->execute(array(':id'=>$remove));
			$page_stuname = implode(', ', $stm->fetch(PDO::FETCH_NUM));
			$stm = $DBH->prepare("SELECT name FROM imas_stugroups WHERE id=:id");
			$stm->execute(array(':id'=>$removegrpid));
			$page_grpname = $stm->fetchColumn(0);
			$stm = $DBH->prepare("SELECT name FROM imas_stugroupset WHERE id=:id");
			$stm->execute(array(':id'=>$grpsetid));
			$page_grpsetname = $stm->fetchColumn(0);
		}
		$curBreadcrumb .= " &gt; <a href=\"managestugrps.php?cid=$cid\">Manage Student Groups</a> &gt; <a href=\"managestugrps.php?cid=$cid&grpsetid=$grpsetid\">".Sanitize::encodeStringForDisplay($page_grpsetname)."</a> &gt; Remove group member";

	} else if (isset($_GET['grpsetid'])) {
		//groupset selected, show groups
		$grpsetid = Sanitize::onlyInt($_GET['grpsetid']);
		$stm = $DBH->prepare("SELECT name FROM imas_stugroupset WHERE id=:id");
		$stm->execute(array(':id'=>$grpsetid));
		$page_grpsetname = $stm->fetchColumn(0);

		//$page_grps will be an array, groupid=>name
		$page_grps = array();
		$page_grpmembers = array();
		$grpnums = 1;
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
		$stm = $DBH->prepare("SELECT DISTINCT section FROM imas_students WHERE imas_students.courseid=:courseid AND imas_students.section IS NOT NULL ORDER BY section");
		$stm->execute(array(':courseid'=>$cid));
        $hassection = ($stm->rowCount()>1);

		if ($hassection) {
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
	<div id="headermanagestugrps" class="pagetitle"><h1><?php echo $pagetitle ?></h1></div>
<?php
	if (isset($_GET['addgrpset'])) {
		//add new group set
		echo '<h3>Add new set of student groups</h3>';
		echo "<form method=\"post\" action=\"managestugrps.php?cid=$cid&addgrpset=true\">";
		echo '<p>New group set name: <input name="grpsetname" type="text" /></p>';
		echo '<p><input type="submit" value="Create" />';
		echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onClick=\"window.location='managestugrps.php?cid=$cid'\" /></p>";
		echo '</form>';
	} else if (isset($_GET['delgrpset'])) {
		echo '<h3>Delete student group set</h3>';
		echo "<p>Are you SURE you want to delete the set of student groups <b>" . Sanitize::encodeStringForDisplay($page_grpsetname) . "</b> and all the groups contained within it? ";
		$used = '';
		$stm = $DBH->prepare("SELECT name FROM imas_assessments WHERE isgroup>0 AND groupsetid=:groupsetid");
		$stm->execute(array(':groupsetid'=>$deleteGroupSet));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$used .= "Assessment: " . Sanitize::encodeStringForDisplay($row[0]) . "<br/>";
		}
		$stm = $DBH->prepare("SELECT name FROM imas_forums WHERE groupsetid=:groupsetid");
		$stm->execute(array(':groupsetid'=>$deleteGroupSet));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$used .= "Forum: " . Sanitize::encodeStringForDisplay($row[0]) . "<br/>";
		}
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
		echo '<h3>Rename student group set</h3>';
		echo "<form method=\"post\" action=\"managestugrps.php?cid=$cid&rengrpset=" . Sanitize::encodeUrlParam($_GET['rengrpset']) . "\">";
		echo '<p>New group set name: <input name="grpsetname" type="text" value="'.Sanitize::encodeStringForDisplay($page_grpsetname).'"/></p>';
		echo '<p><input type="submit" value="Rename" />';
		echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onClick=\"window.location='managestugrps.php?cid=$cid'\" /></p>";
		echo '</form>';
	} else if (isset($_GET['addgrp'])) {
		//add new group set
		echo '<h3>Add new student group</h3>';
		echo "<form method=\"post\" action=\"managestugrps.php?cid=$cid&grpsetid=" . Sanitize::encodeUrlParam($grpsetid) . "&addgrp=true\">";
		if (isset($stulist)) {
			echo "<input type=\"hidden\" name=\"stutoadd\" value=\"" . Sanitize::encodeStringForDisplay($stulist) . "\" />";
		}
		echo '<p>New group name: <input name="grpname" type="text" /></p>';
		echo '<p><input type="submit" value="Create" />';
		echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onClick=\"window.location='managestugrps.php?cid=$cid&grpsetid=" . Sanitize::encodeStringForJavascript($grpsetid) . "'\" /></p>";
		echo '</form>';
	} else if (isset($_GET['delgrp'])) {
		echo '<h3>Delete student group</h3>';
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
		echo '<h3>Rename student group</h3>';
		echo "<form method=\"post\" action=\"managestugrps.php?cid=$cid&grpsetid=" . Sanitize::encodeUrlParam($grpsetid) . "&rengrp=" . Sanitize::encodeUrlParam($_GET['rengrp']) . "\">";
		echo '<p>New group name: <input name="grpname" type="text" value="'.Sanitize::encodeStringForDisplay($page_grpname).'"/></p>';
		echo '<p><input type="submit" value="Rename" />';
		echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onClick=\"window.location='managestugrps.php?cid=$cid&grpsetid=" . Sanitize::encodeStringForJavascript($grpsetid) . "'\" /></p>";
		echo '</form>';
	} else if (isset($_GET['removeall'])) {
		echo '<h3>Remove ALL group members</h3>';
		echo "<p>Are you SURE you want to remove <b>ALL</b> members of the student group <b>" . Sanitize::encodeStringForDisplay($page_grpname) . "</b>?</p>";

		$querystring = http_build_query(array('cid'=>$cid, 'grpsetid'=>$grpsetid, 'removeall'=>Sanitize::onlyInt($_GET['removeall'])));
		echo "<form method=\"post\" action=\"managestugrps.php?$querystring\">";
		echo '<p><button type="submit" name="confirm" value="true">'._('Yes, Remove').'</button> ';
		echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onClick=\"window.location='managestugrps.php?cid=$cid&grpsetid=" . Sanitize::encodeStringForJavascript($grpsetid) . "'\" /></p>";
		echo '</form>';

	} else if (isset($_GET['addrandgrps']) && !empty($grpsetid)) {
		//add new group set
		echo '<h3>Create random student groups</h3>';
		echo "<form method=\"post\" action=\"managestugrps.php?cid=$cid&grpsetid=" . Sanitize::encodeUrlParam($grpsetid) . "&addrandgrps=true\">";
		echo '<span class=form><label for=grpsize>'._('Group size').'</label>:</span>';
		echo '<span class=formright><input type=text size=2 id=grpsize name=grpsize value=4 /></span><br class=form />';
		echo '<span class=form><label for=grpadj>'._('Unequal groups').'</label>:</span>';
		echo '<span class=formright><select id=grpadj name=grpadj>';
		echo ' <option value="0" selected>'._('Make smaller groups if needed').'</option>';
		echo ' <option value="1">'._('Make larger groups if needed').'</option>';
		echo ' <option value="2">'._('Make smaller or larger groups if needed').'</option>';
		echo '</select></span><br class=form />';
		echo '<span class=form>'._('Locked students:').'</span>';
		echo '<span class=formright><label><input type=checkbox name=inclocked />'._('Include locked students').'</label></span><br class=form />';
        if ($hassection) {
            echo '<span class=form>'._('Sections:').'</span>';
            echo '<span class=formright><label><input type=checkbox name=sepsections checked />'._('Group members should have same section').'</label></span><br class=form />';
        }
		echo '<div class=submit><input type="submit" value="Create" />';
		echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onClick=\"window.location='managestugrps.php?cid=$cid&grpsetid=" . Sanitize::encodeStringForJavascript($grpsetid) . "'\" /></div>";
		echo '</form>';
	} else if (isset($_GET['remove']) && $_GET['grpid']) {
		echo '<h3>Remove group member</h3>';
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
		function hidelinks(el) {
			if ($(el).text()==_('Hide Links')) {
				$(".linkgrp").hide();
				$(el).text(_('Show Links'));
			} else {
				$(".linkgrp").show();
				$(el).text(_('Hide Links'));
			}
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
		echo "<h2>Managing groups in set " . Sanitize::encodeStringForDisplay($page_grpsetname) . "</h2>";
		echo '<div id="myTable">';
		echo "<p><button type=\"button\" onclick=\"window.location.href='managestugrps.php?cid=$cid&grpsetid=" . Sanitize::encodeUrlParam($grpsetid) . "&addgrp=true'\">"._('Add New Group').'</button> ';
		if (count($page_grps)==0) {
			echo " <button type=\"button\" onclick=\"window.location.href='managestugrps.php?cid=$cid&grpsetid=" . Sanitize::encodeUrlParam($grpsetid) . "&addrandgrps=true'\">"._('Create Random Groups').'</button> ';
		} else {
			echo ' <button type="button" onclick="hidelinks(this)" >'._('Hide Links').'</button>';
		}
		if (array_sum($hasuserimg)>0) {
			echo ' <button type="button" onclick="rotatepics(this)" >'._('View Pictures').'</button><br/>';
		}
		echo '</p>';

		if (count($page_grps)==0) {
			echo '<p>No student groups have been created yet</p>';
		}
		foreach ($page_grps as $grpid=>$grpname) {
			echo "<b>Group: " . Sanitize::encodeStringForDisplay($grpname) . "</b> <span class=linkgrp>| ";
			echo "<a href=\"managestugrps.php?cid=$cid&grpsetid=" . Sanitize::encodeUrlParam($grpsetid) . "&rengrp=" . Sanitize::encodeUrlParam($grpid) . "\">Rename</a> | ";
			echo "<a href=\"managestugrps.php?cid=$cid&grpsetid=" . Sanitize::encodeUrlParam($grpsetid) . "&delgrp=" . Sanitize::encodeUrlParam($grpid) . "\">Delete</a> | ";
			echo "<a href=\"managestugrps.php?cid=$cid&grpsetid=" . Sanitize::encodeUrlParam($grpsetid) . "&removeall=" . Sanitize::encodeUrlParam($grpid) . "\">Remove all members</a>";
			echo '</span><ul>';
			if (count($page_grpmembers[$grpid])==0) {
				echo '<li>No group members</li>';
			} else {
				foreach ($page_grpmembers[$grpid] as $uid=>$name) {
					echo '<li>';
					if ($hasuserimg[$uid]==1) {
						if(isset($GLOBALS['CFG']['GEN']['AWSforcoursefiles']) && $GLOBALS['CFG']['GEN']['AWSforcoursefiles'] == true) {
							echo "<img class=\"pii-image\" src=\"{$urlmode}{$GLOBALS['AWSbucket']}.s3.amazonaws.com/cfiles/userimg_sm{$uid}.jpg\" style=\"display:none;\" alt=\"User picture\" />";
						} else {
							echo "<img class=\"pii-image\" src=\"$imasroot/course/files/userimg_sm{$uid}.jpg\" style=\"display:none;\" alt=\"User picture\"/>";
						}
					}
					if ($stulocked[$uid]) {
						echo '<span class="greystrike pii-full-name">'.$name.'</span>';
					} else {
						echo '<span class="pii-full-name">'.$name.'</span>';
					}
					echo " <span class=linkgrp>| <a href=\"managestugrps.php?cid=$cid&grpsetid=" . Sanitize::encodeUrlParam($grpsetid) . "&remove=" . Sanitize::onlyInt($uid) . "&grpid=" . Sanitize::encodeUrlParam($grpid) . "\">Remove from group</a></span></li>";
				}
			}
			echo '</ul>';
		}


		echo '<h2>Students not in a group yet</h2>';
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
						echo "<img class=\"pii-image\" src=\"{$urlmode}{$GLOBALS['AWSbucket']}.s3.amazonaws.com/cfiles/userimg_sm{$uid}.jpg\" style=\"display:none;\" alt=\"User picture\"/>";
					} else {
						echo "<img class=\"pii-image\" src=\"$imasroot/course/files/userimg_sm{$uid}.jpg\" style=\"display:none;\" alt=\"User picture\"/>";
					}
				}
				if ($stulocked[$uid]) {
					echo '<span class="greystrike pii-full-name">'.$name.'</span>';
				} else {
					echo '<span class="pii-full-name">'.$name.'</span>';
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
		echo '<h3>Student Group Sets</h3>';
		if (count($page_groupsets)==0) {
			echo '<p>No existing sets of groups</p>';
		} else {
			echo '<p>Select a set of groups to modify the groups in that set</p>';
			echo '<table><tbody>';
			foreach ($page_groupsets as $gs) {
                if ($gs[1] == '##autobysection##') {
                    echo '<tr style="display:none;">';
                } else {
                    echo '<tr>';
                }
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

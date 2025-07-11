<?php
//IMathAS:  Main admin page
//(c) 2006 David Lippman

/*** master php includes *******/
require_once "../init.php";
require_once "../includes/htmlutil.php";

 //set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = "Manage Question Sets";
$helpicon = "";
if (!isset($courseUIver)) {
    $courseUIver = 2;
}
//data manipulation here
$isadmin = false;
$isgrpadmin = false;

	//CHECK PERMISSIONS AND SET FLAGS
if ($myrights<20) {
 	$overwriteBody = 1;
	$body = "You need to log in as a teacher to access this page";
} elseif (isset($_GET['cid']) && $_GET['cid']=="admin" && $myrights <75) {
 	$overwriteBody = 1;
	$body = "You need to log in as an admin to access this page";
} elseif (!(isset($_GET['cid'])) && $myrights < 75) {
 	$overwriteBody = 1;
	$body = "Please access this page from the menu links only.";
} else {	//PERMISSIONS ARE OK, PERFORM DATA MANIPULATION

	$cid = Sanitize::courseId($_GET['cid']);
	if ($cid=='admin') {
		if ($myrights >74 && $myrights<100) {
			$isgrpadmin = true;
		} else if ($myrights == 100) {
			$isadmin = true;
		}
	}

	if ($isadmin || $isgrpadmin) {
		$curBreadcrumb = "$breadcrumbbase <a href=\"../admin/admin2.php\">Admin</a> ";
	} else if ($cid==0) {
		$curBreadcrumb = "<a href=\"../index.php\">Home</a> ";
	} else {
		$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
	}


	if (isset($_POST['remove']) || isset($_GET['remove'])) {
		if (isset($_GET['confirmed'])) {
			if ($_POST['remove']!='') {
				$remlist = implode(',', array_map('intval', explode(',',$_POST['remove'])));
				if (!$isadmin) {
					$oktorem = array();
					if ($isgrpadmin) {
						$query = "SELECT imas_questionset.id FROM imas_questionset,imas_users WHERE ";
						$query .= "imas_questionset.id IN ($remlist) AND imas_questionset.ownerid=imas_users.id ";
						$query .= "AND imas_users.groupid=:groupid";
						$stm = $DBH->prepare($query);
						$stm->execute(array(':groupid'=>$groupid));
					} else {
						$stm = $DBH->prepare("SELECT id FROM imas_questionset WHERE id IN ($remlist) AND ownerid=:ownerid");
						$stm->execute(array(':ownerid'=>$userid));
					}
					while ($row = $stm->fetch(PDO::FETCH_NUM)) {
						$oktorem[] = $row[0];
					}
					$remlist = implode(',',$oktorem);
				}
				//remlist now only contains questions ok to remove
				$now = time();

                if ($remlist != '') {
                    //delete all library items for that question, regardless of owner
                    $stm = $DBH->prepare("UPDATE imas_library_items SET deleted=1,lastmoddate=:now WHERE qsetid IN ($remlist) AND deleted=0");
                    $stm->execute(array(':now'=>$now));

                    //now delete the questions
                    $stm = $DBH->prepare("UPDATE imas_questionset SET deleted=1,lastmoddate=:now WHERE id IN ($remlist)");
                    $stm->execute(array(':now'=>$now));
                }

			}
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/manageqset.php?cid=$cid" . "&r=" . Sanitize::randomQueryStringParam());

			exit;
		} else {
			if (isset($_POST['remove']) && !isset($_POST['nchecked'])) {
				$overwriteBody = 1;
				$body = "No questions selected.  <a href=\"manageqset.php?cid=$cid\">Go back</a>\n";
			} else {
				$pagetitle = "Confirm Delete";
				$curBreadcrumb .= " &gt; <a href=\"manageqset.php?cid=$cid\">Manage Question Set </a>";
				$curBreadcrumb .= " &gt; Confirm Delete";
				if (isset($_POST['remove'])) {
					$rlist = Sanitize::encodeStringForDisplay(implode(",",$_POST['nchecked']));
				} else {
					$rlist = Sanitize::encodeStringForDisplay($_GET['remove']);
				}
			}
		}
	} else if (isset($_POST['transfer']) || isset($_GET['transfer'])) {
		if (isset($_POST['newowner'])) {
			if ($_POST['transfer']!='') {
				$translist = implode(',', array_map('intval', explode(',',$_POST['transfer'])));

				if ($isgrpadmin) {
					$stm = $DBH->prepare("SELECT imas_questionset.id FROM imas_questionset,imas_users WHERE imas_questionset.ownerid=imas_users.id AND imas_questionset.id IN ($translist) AND imas_users.groupid=:groupid");
					$stm->execute(array(':groupid'=>$groupid));
					$upd_stm = $DBH->prepare("UPDATE imas_questionset SET ownerid=:ownerid WHERE id=:id");
					while ($row = $stm->fetch(PDO::FETCH_NUM)) {
						$upd_stm->execute(array(':ownerid'=>$_POST['newowner'], ':id'=>$row[0]));
					}

				} else {
					if (!$isadmin) {
						$query = "UPDATE imas_questionset SET ownerid=:ownerid WHERE id IN ($translist)";
						$query .= " AND ownerid=:ownerid2";
						$stm = $DBH->prepare($query);
						$stm->execute(array(':ownerid'=>$_POST['newowner'], ':ownerid2'=>$userid));
					} else {
						$stm = $DBH->prepare("UPDATE imas_questionset SET ownerid=:ownerid WHERE id IN ($translist)");
						$stm->execute(array(':ownerid'=>$_POST['newowner']));
					}
				}

			}
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/manageqset.php?cid=$cid" . "&r=" . Sanitize::randomQueryStringParam());

			exit;
		} else {

			$pagetitle ="Transfer Ownership";
			$curBreadcrumb .= " &gt; <a href=\"manageqset.php?cid=$cid\">Manage Question Set </a>";
			$curBreadcrumb .= " &gt; Transfer QSet";

			if (isset($_POST['transfer']) && !isset($_POST['nchecked'])) {
				$overwriteBody = 1;
				$body = "No questions selected.  <a href=\"manageqset.php?cid=$cid\">Go back</a>\n";
			} else {
				if (isset($_POST['transfer'])) {
					$tlist = implode(",",$_POST['nchecked']);
				} else {
					$tlist = $_GET['transfer'];
				}
			}
            /*
            if ($isadmin) {
			    $stm = $DBH->prepare("SELECT id,FirstName,LastName FROM imas_users WHERE rights>19 AND lastaccess>? ORDER BY LastName,FirstName");
                $stm->execute([time()-30*24*60*60]);
            } else {
                $stm = $DBH->prepare("SELECT id,FirstName,LastName FROM imas_users WHERE rights>19 AND groupid=? ORDER BY LastName,FirstName");
                $stm->execute([$groupid]);
            }
			$i=0;
			$page_transferUserList = array();
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$page_transferUserList['val'][$i] = $row[0];
				$page_transferUserList['label'][$i] = $row[2] . ", " . $row[1];
				$i++;
			}
            */
		}
	} else if (isset($_POST['chglib'])) {
		if (isset($_POST['qtochg'])) {
			if ($_POST['chglib']!='') {
				$newlibs = array_map('intval', explode(',',$_POST['libs'])); 
				sort($newlibs); // put unassigned first if selected
				if (count($newlibs)>0 && $newlibs[0]==0) { //get rid of unassigned if checked
					array_shift($newlibs);
				}
				//Verify we have rights to add to all of newlibs
				$newliblist = implode(',', $newlibs);
				if (!$isadmin && count($newlibs)>0) {
					$oktoaddto = array();
					$stm = $DBH->query("SELECT id,ownerid,userights,groupid FROM imas_libraries WHERE id IN ($newliblist)");
					while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
						if ($isgrpadmin) {
							if ($row['groupid']==$groupid || $row['userights']==8) {
								$oktoaddto[] = $row['id'];
							}
						} else {
							if ($row['ownerid']==$userid || (($row['userights']%3)==2 && $row['groupid']==$groupid) || $row['userights']==8) {
								$oktoaddto[] = $row['id'];
							}
						}
					}
					$newlibs = $oktoaddto;
				}

				$libarray = explode(',',$_POST['qtochg']); //qsetids to change
				if ($_POST['qtochg']=='') {
					$libarray = array();
				}
				//make a list of all the questions that we're changing libraries for
				$chglist = implode(',', array_map('intval', $libarray));

				//pull a list of all non-deleted library items for these questions
				$alllibs = array();
				$dellibs = array();
				$stm = $DBH->query("SELECT qsetid,libid,deleted FROM imas_library_items WHERE qsetid IN ($chglist) AND libid>0");
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					if ($row[2]==0) {
						$alllibs[$row[0]][] = $row[1];
					} else {
						$dellibs[$row[0]][] = $row[1];
					}
				}

				//pull a list of non-deleted library items for these questions that user has the rights to modify
				if ($isadmin) {
					$query = "SELECT ili.qsetid,ili.libid FROM imas_library_items AS ili LEFT JOIN imas_libraries AS il ON ";
					$query .= "ili.libid=il.id AND il.deleted=0 WHERE ili.qsetid IN ($chglist) AND ili.deleted=0";
					$stm = $DBH->query($query);
				} else if ($isgrpadmin) {
					$query = "SELECT imas_library_items.qsetid,imas_library_items.libid FROM imas_library_items,imas_users WHERE ";
					$query .= "imas_library_items.ownerid=imas_users.id AND (imas_users.groupid=:groupid OR imas_library_items.libid=0) ";
					$query .= "AND imas_library_items.qsetid IN ($chglist) AND imas_library_items.deleted=0";
					$stm = $DBH->prepare($query);
					$stm->execute(array(':groupid'=>$groupid));
				} else {
					$query = "SELECT ili.qsetid,ili.libid FROM imas_library_items AS ili LEFT JOIN imas_libraries AS il ON ";
					$query .= "ili.libid=il.id AND il.deleted=0 WHERE ili.qsetid IN ($chglist) ";
					$query .= "AND ((ili.ownerid=:ownerid AND (il.ownerid=:ownerid2 OR il.userights%3<>1)) OR ili.libid=0) AND ili.deleted=0";
					$stm = $DBH->prepare($query);
					$stm->execute(array(':ownerid'=>$userid, ':ownerid2'=>$userid));
				}
				$mylibs = array();
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					$mylibs[$row[0]][] = $row[1];
				}

				$now = time();
				if ($_POST['action']==0) {//add, keep existing
					/*
					get list of existing library assignments
					remove any additions that already exist
					add to new libraries
					*/
					$ins_stm = $DBH->prepare("INSERT INTO imas_library_items (libid,qsetid,ownerid,lastmoddate) VALUES (:libid, :qsetid, :ownerid, :now)");
					$undel_stm = $DBH->prepare("UPDATE imas_library_items SET deleted=0,lastmoddate=:now,ownerid=:ownerid WHERE qsetid=:qsetid AND libid=:libid");
					$del_stm = $DBH->prepare("UPDATE imas_library_items SET deleted=1,lastmoddate=:now WHERE qsetid=:qsetid AND libid=0");

					foreach ($libarray as $qsetid) { //for each question
						//determine which checked libraries it's not already in
						if (isset($alllibs[$qsetid])) {
							$toadd = array_values(array_diff($newlibs,$alllibs[$qsetid]));
						} else {
							$toadd = $newlibs;
						}
						if (isset($dellibs[$qsetid])) {
							$toundel = array_values(array_intersect($newlibs,$dellibs[$qsetid]));
						} else {
							$toundel = array();
						}
						$toaddnew = array_values(array_diff($toadd, $toundel));

						$alladded = array_merge($toaddnew,$toundel);

						foreach ($toundel as $libid) {
							if ($libid==0) { continue;} //no need to add to unassigned using "keep existing"
							$undel_stm->execute(array(':libid'=>$libid, ':qsetid'=>$qsetid, ':ownerid'=>$userid, ':now'=>$now));
						}
						foreach($toaddnew as $libid) {
							if ($libid==0) { continue;} //no need to add to unassigned using "keep existing"
							$ins_stm->execute(array(':libid'=>$libid, ':qsetid'=>$qsetid, ':ownerid'=>$userid, ':now'=>$now));
						}
						//delete unassigned library item
						if (count($alladded)>1 || (count($alladded)>0 && $alladded[0]!=0)) {
							$del_stm->execute(array(':qsetid'=>$qsetid, ':now'=>$now));
						}
					}
				} else if ($_POST['action']==1 || $_POST['action']==3) { //add, remove existing
					/*
					get list of existing library assignments
					rework existing to new libs
					remove any excess existing
					add to any new
					*/
					$ins_stm = $DBH->prepare("INSERT INTO imas_library_items (libid,qsetid,ownerid,lastmoddate) VALUES (:libid, :qsetid, :ownerid, :now)");
					$undel_stm = $DBH->prepare("UPDATE imas_library_items SET deleted=0,lastmoddate=:now,ownerid=:ownerid WHERE qsetid=:qsetid AND libid=:libid");
					$del_stm = $DBH->prepare("UPDATE imas_library_items SET deleted=1,lastmoddate=:now WHERE libid=:libid AND qsetid=:qsetid");
					$sel_stm = $DBH->prepare("SELECT id,deleted FROM imas_library_items WHERE qsetid=:qsetid AND (deleted=0 OR (libid=0 AND deleted=1)) ORDER BY deleted");
					$unassn_undel_stm = $DBH->prepare("UPDATE imas_library_items SET deleted=0,lastmoddate=:now WHERE libid=0 AND qsetid=:qsetid");

					foreach ($libarray as $qsetid) { //for each question
						//determine which checked libraries it's not already in
						if (isset($alllibs[$qsetid])) {
							$toadd = array_values(array_diff($newlibs,$alllibs[$qsetid]));
						} else {
							$toadd = $newlibs;
						}
						if (isset($dellibs[$qsetid])) {
							$toundel = array_values(array_intersect($toadd,$dellibs[$qsetid]));
						} else {
							$toundel = array();
						}
						$toaddnew = array_values(array_diff($toadd,$toundel));
						//print_r($toundel);
						//print_r($toaddnew);
						//exit;

						//and add them
						foreach ($toundel as $libid) {
							if ($libid==0) { continue;} //we'll handle unassigned later
							$undel_stm->execute(array(':libid'=>$libid, ':qsetid'=>$qsetid, ':ownerid'=>$userid, ':now'=>$now));
						}
						foreach($toaddnew as $libid) {
							if ($libid==0) { continue;} //we'll handle unassigned later
							$ins_stm->execute(array(':libid'=>$libid, ':qsetid'=>$qsetid, ':ownerid'=>$userid, ':now'=>$now));
						}
						//determine which libraries to remove from; my lib assignments - newlibs
						if (!empty($_SESSION['lastsearchlibsc'.$cid])) {
							$listedlibs = explode(',', $_SESSION['lastsearchlibsc'.$cid]);
						} else {
							$listedlibs = array();
						}
						if (isset($mylibs[$qsetid])) {
							if ($_POST['action']==1) { //remove: my lib assignments - newlibs
								$toremove = array_diff($mylibs[$qsetid],$newlibs);
							} else if ($_POST['action']==3) { //remove:  listed libs that are my libs - newlibs
								$toremove = array_diff(array_intersect($listedlibs, $mylibs[$qsetid]), $newlibs);
							}
							foreach($toremove as $libid) {
								$del_stm->execute(array(':libid'=>$libid, ':qsetid'=>$qsetid, ':now'=>$now));
							}

							//check for unassigneds if not adding to any new libs
							if (count($newlibs)==0) {
								$sel_stm->execute(array(':qsetid'=>$qsetid));
								if ($sel_stm->rowCount()==0) { //no library items exist - add unassigned
									$ins_stm->execute(array(':libid'=>0, ':qsetid'=>$qsetid, ':ownerid'=>$userid, ':now'=>$now));
								} else { //we have active lib item or deleted unassigned
									if ($sel_stm->fetchColumn(1)==1) { //has no active lib items, so must have unassigned to undelete
										$unassn_undel_stm->execute(array(':qsetid'=>$qsetid, ':now'=>$now));
									}
								}
							}
						}
					}
				} else if ($_POST['action']==2) { //remove
					/*
					get list of exisiting assignments
					if not in checked list, remove
					*/
					$del_stm = $DBH->prepare("UPDATE imas_library_items SET deleted=1,lastmoddate=:now WHERE libid=:libid AND qsetid=:qsetid");
					$ins_stm = $DBH->prepare("INSERT INTO imas_library_items (libid,qsetid,ownerid,lastmoddate) VALUES (:libid, :qsetid, :ownerid, :now)");
					$sel_stm = $DBH->prepare("SELECT id,deleted FROM imas_library_items WHERE qsetid=:qsetid AND (deleted=0 OR (libid=0 AND deleted=1)) ORDER BY deleted");
					$unassn_undel_stm = $DBH->prepare("UPDATE imas_library_items SET deleted=0,lastmoddate=:now WHERE libid=0 AND qsetid=:qsetid");

					foreach ($libarray as $qsetid) { //for each question
						//determine which libraries to remove from; my lib assignments - newlibs
						if (isset($mylibs[$qsetid])) {
                            $toremove = array_values(array_diff($mylibs[$qsetid],$newlibs));
                            if (count($toremove)==1 && $toremove[0] == 0) {
                                // only in unassigned - nothing to do
                                continue;
                            }
							foreach($toremove as $libid) {
								$del_stm->execute(array(':libid'=>$libid, ':qsetid'=>$qsetid, ':now'=>$now));
							}
							//check for unassigneds
							if (count($newlibs)==0) {
								$sel_stm->execute(array(':qsetid'=>$qsetid));
								if ($sel_stm->rowCount()==0) { //no library items exist - add unassigned
									$ins_stm->execute(array(':libid'=>0, ':qsetid'=>$qsetid, ':ownerid'=>$userid, ':now'=>$now));
								} else { //we have active lib item or deleted unassigned
									if ($sel_stm->fetchColumn(1)==1) { //has no active lib items, so must have unassigned to undeleted
										$unassn_undel_stm->execute(array(':qsetid'=>$qsetid, ':now'=>$now));
									}
								}
							}
						}
					}
				}
			}
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/manageqset.php?cid=$cid" . "&r=" . Sanitize::randomQueryStringParam());

			exit;
		} else {
			$pagetitle = "Modify Library Assignments";
			$curBreadcrumb .= " &gt; <a href=\"manageqset.php?cid=$cid\">Manage Question Set </a>";
			$curBreadcrumb .= " &gt; Modify Assignments";



			if (!isset($_POST['nchecked'])) {
				$overwriteBody = 1;
				$body = "No questions selected.  <a href=\"manageqset.php?cid=$cid\">Go back</a>\n";
			} else {
				$clist = implode(',', array_map('intval', $_POST['nchecked']));
				$stm = $DBH->query("SELECT DISTINCT ili.libid FROM imas_library_items AS ili WHERE ili.qsetid IN ($clist) AND deleted=0");
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					$checkedlibs[] = $row[0];
				}
			}
		}


	}
	/* else if (isset($_POST['template'])) {
		if (isset($_POST['qtochg'])) {
			if (!isset($_POST['libs'])) {
				$overwriteBody = 1;
				$body = "<html><body>No library selected.  <a href=\"manageqset.php?cid=$cid\">Go back</a></body></html>\n";
			}
			$lib = $_POST['libs'];
			$qtochg = explode(',',$_POST['qtochg']);
			$now = time();
			$stm = $DBH->prepare("SELECT firstName,lastName FROM imas_users WHERE id=:id");
			$stm->execute(array(':id'=>$userid));
			$row = $stm->fetch(PDO::FETCH_NUM);
			$myname = $row[1].','.$row[0];
			foreach ($qtochg as $k=>$qid) {
				$ancestors = ''; $ancestorauthors = '';
				$stm = $DBH->prepare("SELECT description,userights,qtype,control,qcontrol,qtext,answer,hasimg,ancestors,ancestorauthors,license,author FROM imas_questionset WHERE id=:id");
				$stm->execute(array(':id'=>$qid));
				$row = $stm->fetch(PDO::FETCH_ASSOC);
				$lastauthor = $row['author']; //DB array_pop($row);
				$ancestors = $row['ancestors'];
				$ancestorauthors = $row['ancestorauthors'];
				if ($ancestors!='') {
					$ancestors = $qid . ','. $ancestors;
				} else {
					$ancestors = $qid;
				}
				if ($ancestorauthors!='') {
					$ancestorauthors = $lastauthor.'; '.$ancestorauthors;
				} else {
					$ancestorauthors = $lastauthor;
				}
				$row['description'] .= " (copy by $userfullname)";
				$mt = microtime();
				$uqid = substr($mt,11).substr($mt,2,3).$k;
				$query = "INSERT INTO imas_questionset (uniqueid,adddate,lastmoddate,ownerid,author,description,userights,qtype,control,qcontrol,qtext,answer,hasimg,ancestors,ancestorauthors,license) VALUES ";
        $query .= "(:uniqueid, :adddate, :lastmoddate, :ownerid, :author, :description, :userights, :qtype, :control, :qcontrol, :qtext, :answer, :hasimg, :ancestors, :ancestorauthors, :license)";
        $stm = $DBH->prepare($query);
        $stm->execute(array(':uniqueid'=>$uqid, ':adddate'=>$now, ':lastmoddate'=>$now, ':ownerid'=>$userid, ':author'=>$myname,
          ':description'=>$row['description'], ':userights'=>$row['userights'], ':qtype'=>$row['qtype'], ':control'=>$row['control'],
          ':qcontrol'=>$row['qcontrol'], ':qtext'=>$row['qtext'], ':answer'=>$row['answer'], ':hasimg'=>$row['hasimg'],
          ':ancestors'=>$ancestors, ':ancestorauthors'=>$ancestorauthors, ':license'=>$row['license']));
				$nqid = $DBH->lastInsertId();
				$stm = $DBH->prepare("INSERT INTO imas_library_items (libid,qsetid,ownerid) VALUES (:libid, :qsetid, :ownerid)");
				$stm->execute(array(':libid'=>$lib, ':qsetid'=>$nqid, ':ownerid'=>$userid));
				$stm = $DBH->prepare("SELECT var,filename,alttext FROM imas_qimages WHERE qsetid=:qsetid");
				$stm->execute(array(':qsetid'=>$qid));
        $img_ins_stm = $DBH->prepare("INSERT INTO imas_qimages (qsetid,var,filename,alttext) VALUES (:qsetid, :var, :filename, :alttext)");
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					$img_ins_stm->execute(array(':qsetid'=>$nqid, ':var'=>$row[0], ':filename'=>$row[1], ':alttext'=>$row[2]));
				}

			}
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/manageqset.php?cid=$cid");

			exit;
		} else {
			$pagetitle = "Template Questions";
			$curBreadcrumb .= " &gt; <a href=\"manageqset.php?cid=$cid\">Manage Question Set </a>";
			$curBreadcrumb .= " &gt; Template Questions";

			$clist = implode(",",$_POST['nchecked']);
			$selecttype = "radio";

			if (!isset($_POST['nchecked'])) {
				$overwriteBody = 1;
				$body = "No questions selected.  <a href=\"manageqset.php?cid=$cid\">Go back</a>\n";
			}
		}

	}*/
	else if (isset($_POST['license'])) {
		if (isset($_POST['qtochg'])) {
			$qtochg = implode(',', array_map('intval', explode(',',$_POST['qtochg'])));
			if ($_POST['sellicense']!=-1) {
				if (!$isadmin) {
          $query = "UPDATE imas_questionset SET license=:license WHERE id IN ($qtochg)";
					$query .= " AND ownerid=:ownerid";
					$stm = $DBH->prepare($query);
					$stm->execute(array(':license'=>$_POST['sellicense'], ':ownerid'=>$userid));
				} else {
          $stm = $DBH->prepare("UPDATE imas_questionset SET license=:license WHERE id IN ($qtochg)");
          $stm->execute(array(':license'=>$_POST['sellicense']));
        }
			}
			if ($_POST['otherattribtype']!=-1) {
				if ($_POST['otherattribtype']==0) {
					if (!$isadmin) {
            $query = "UPDATE imas_questionset SET otherattribution=:otherattribution WHERE id IN ($qtochg)";
						$query .= " AND ownerid=:ownerid";
						$stm = $DBH->prepare($query);
						$stm->execute(array(':otherattribution'=>$_POST['addattr'], ':ownerid'=>$userid));
					} else {
            $stm = $DBH->prepare("UPDATE imas_questionset SET otherattribution=:otherattribution WHERE id IN ($qtochg)");
            $stm->execute(array(':otherattribution'=>$_POST['addattr']));
          }
				} else {
					if (!$isadmin) {
            $query = "SELECT id,otherattribution FROM imas_questionset WHERE id IN ($qtochg)";
						$query .= " AND ownerid=:ownerid";
						$stm = $DBH->prepare($query);
						$stm->execute(array(':ownerid'=>$userid));
					} else {
            $stm = $DBH->query("SELECT id,otherattribution FROM imas_questionset WHERE id IN ($qtochg)");
          }
          $upd_stm = $DBH->prepare("UPDATE imas_questionset SET otherattribution=:otherattribution WHERE id=:id");
					while ($row = $stm->fetch(PDO::FETCH_NUM)) {
            $attr = $row[1] . $_POST['addattr'];
						$upd_stm->execute(array(':otherattribution'=>$attr, ':id'=>$row[0]));
					}
				}
			}
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/manageqset.php?cid=$cid" . "&r=" . Sanitize::randomQueryStringParam());

			exit;
		} else {
			$pagetitle = _("Change Question License/Attribution");
			$curBreadcrumb .= " &gt; <a href=\"manageqset.php?cid=$cid\">Manage Question Set </a>";
			$curBreadcrumb .= " &gt; "._("Change Question License/Attribution");

			if (!isset($_POST['nchecked'])) {
				$overwriteBody = 1;
				$body = "No questions selected.  <a href=\"manageqset.php?cid=$cid\">Go back</a>\n";
			} else {
                $clist = Sanitize::encodeStringForDisplay(implode(",",$_POST['nchecked']));
            }
		}

	} else if (isset($_POST['chgrights'])) {
		if (isset($_POST['qtochg'])) {
			$chglist = implode(',', array_map('intval', explode(',',$_POST['qtochg'])));
			if ($isgrpadmin) {
				$stm = $DBH->prepare("SELECT imas_questionset.id FROM imas_questionset,imas_users WHERE imas_questionset.ownerid=imas_users.id AND imas_questionset.id IN ($chglist) AND imas_users.groupid=:groupid");
				$stm->execute(array(':groupid'=>$groupid));
				$tochg = array();
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					$tochg[] = $row[0];
				}
				if (count($tochg)>0) {
					$chglist = implode(',',$tochg);
					$stm = $DBH->prepare("UPDATE imas_questionset SET userights=:userights WHERE id IN ($chglist)");
					$stm->execute(array(':userights'=>$_POST['newrights']));
				}
			} else {
				if (!$isadmin) {
          $query = "UPDATE imas_questionset SET userights=:userights WHERE id IN ($chglist)";
					$query .= " AND ownerid=:ownerid";
					$stm = $DBH->prepare($query);
					$stm->execute(array(':userights'=>$_POST['newrights'], ':ownerid'=>$userid));
				} else {
          $stm = $DBH->prepare("UPDATE imas_questionset SET userights=:userights WHERE id IN ($chglist)");
          $stm->execute(array(':userights'=>$_POST['newrights']));
        }
			}
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/manageqset.php?cid=$cid" . "&r=" . Sanitize::randomQueryStringParam());
			exit;
		} else {
			$pagetitle = "Change Question Rights";
			$curBreadcrumb .= " &gt; <a href=\"manageqset.php?cid=$cid\">Manage Question Set </a>";
			$curBreadcrumb .= " &gt; Change Question Rights";

			if (!isset($_POST['nchecked'])) {
				$overwriteBody = 1;
				$body = "No questions selected.  <a href=\"manageqset.php?cid=$cid\">Go back</a>\n";
			} else {
                $clist = Sanitize::encodeStringForDisplay(implode(",", $_POST['nchecked']));
            }
		}
	} else if (isset($_GET['transfer'])) {
		//postback handled by $_POST['transfer'] block
		$pagetitle = "Transfer Ownership";
		$curBreadcrumb .= " &gt; <a href=\"manageqset.php?cid=$cid\">Manage Question Set </a>";
		$curBreadcrumb .= " &gt; Transfer QSet";
        /*
		$stm = $DBH->query("SELECT id,FirstName,LastName FROM imas_users WHERE rights>19 ORDER BY LastName,FirstName");
		$i=0;
		$page_transferUserList = array();
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$page_transferUserList['val'][$i] = $row[0];
			$page_transferUserList['label'][$i] = $row[2] . ", " . $row[1];
			$i++;
		}
        */

	} else { //DEFAULT DATA MANIPULATION
		$curBreadcrumb .= " &gt; " . _('Manage Question Set');
		$pagetitle = _("Question Set Management");
		$helpicon = "<img src=\"$staticroot/img/help.gif\" alt=\"Help\" onClick=\"window.open('$imasroot/help.php?section=managequestionset','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))\"/>";
		if ($isadmin) {
			$page_adminMsg =  '<p>'._("You are in Admin mode, which means actions will apply to all questions, regardless of owner").'</p>';
		} else if ($isgrpadmin) {
			$page_adminMsg = '<p>'._("You are in Group Admin mode, which means actions will apply to all group's questions, regardless of owner").'</p>';
		} else {
			$page_adminMsg = "";
		}
		//load filter.  Need earlier than usual header.php load
		$curdir = rtrim(dirname(__FILE__), '/\\');
		require_once "$curdir/../filter/filter.php";

		if (isset($_SESSION['searchtypec'.$cid])) {
            $searchtype = $_SESSION['searchtypec'.$cid];
        } else {
            $searchtype = 'libs';
        }
        if (isset($_SESSION['searchinc'.$cid])) {
            $searchin = $_SESSION['searchinc'.$cid];
        } else if ($searchtype == 'libs') {
            $searchin = [$userdeflib];
            $_SESSION['searchinc'.$cid] = $searchin;
            $_SESSION['lastsearchlibsc'.$cid] = implode(',', $searchin);
        } else {
            $searchin = [];
        }
        if (isset($_SESSION['lastsearchc'.$cid])) {
            $searchterms = $_SESSION['lastsearchc'.$cid];
        } else {
            $searchterms = '';
        }

        // do initial search
        require_once '../includes/questionsearch.php';
        $search_parsed = parseSearchString($searchterms);
        $searchoptions = ['includelastmod' => 1, 'includeowner' => ($cid=='admin'?1:0)];
        if ($isadmin) {
            $searchoptions['isadmin'] = true;
        } else if ($isgrpadmin) {
            $searchoptions['isgroupadmin'] = $groupid;
        }
        $search_results = searchQuestions($search_parsed, $userid, $searchtype, $searchin, $searchoptions);
	}
}

$testqpage = ($courseUIver>1) ? 'testquestion2.php' : 'testquestion.php';

/******* begin html output ********/
$placeinhead = "<script type=\"text/javascript\" src=\"$staticroot/javascript/junkflag.js\"></script>";
$placeinhead .= "<script type=\"text/javascript\" src=\"$staticroot/javascript/qsearch.js?v=071125\"></script>";
$placeinhead .= "<script type=\"text/javascript\" src=\"$staticroot/javascript/DatePicker.js?v=080818\"></script>";
$placeinhead .= "<script type=\"text/javascript\">var JunkFlagsaveurl = '" . $GLOBALS['basesiteurl'] . "/course/savelibassignflag.php';</script>";
$placeinhead .= "<link rel=\"stylesheet\" href=\"$staticroot/course/addquestions2.css?v=060823\" type=\"text/css\" />";
$placeinhead .= "<script type=\"text/javascript\">
        var previewqaddr = '$imasroot/course/$testqpage?cid=$cid';
        var qsearchaddr = '$imasroot/course/qsearch.php?cid=$cid';
        var aselectaddr = '$imasroot/course/assessselect.php?cid=$cid';
        var assessver = 2;
        var curaid = 0;
        var curcid = \"$cid\";
        function postWSform(val) {
            $('#selq').append($('<input>', {name:val, value:val, type:'hidden'})).submit();
        }
		</script>";
if (!empty($_POST['chglib'])) {
	$placeinhead .= '<link rel="stylesheet" href="'.$staticroot.'/javascript/accessibletree.css" type="text/css" />';
	$placeinhead .= '<script type="text/javascript" src="'.$staticroot.'/javascript/accessibletree.js?v=031111"></script>';
}
$placeinhead .= '<style>
  .qisprivate {
    color: #db0000;
  }
  </style>';
require_once "../header.php";

$address = $GLOBALS['basesiteurl'] . '/course';

if ($overwriteBody==1) {
	echo $body;
} else {

?>
<script type="text/javascript">

var baseaddr = '<?php echo $address ?>';

function doaction(todo,id) {
	var addrmod = baseaddr+'/moddataset.php?cid=<?php echo $cid ?>&id=';
	var addrtemp = baseaddr+'/moddataset.php?cid=<?php echo $cid ?>&template=true&id=';
	var addrmq = baseaddr+'/manageqset.php?cid=<?php echo $cid ?>';
	if (todo=="mod") {
		addr = addrmod+id;
	} else if (todo=="temp") {
		addr = addrtemp+id;
	} else if (todo=="del") {
		addr = addrmq+'&remove='+id;
	} else if (todo=="tr") {
		addr = addrmq+'&transfer='+id;
	}
	window.location = addr;
}

<?php
if (isset($searchin)) {
    echo 'var curlibs = \'' . Sanitize::encodeStringForJavascript(implode(',',$searchin)) . '\';';
}
if (isset($searchtype)) {
    echo 'var cursearchtype = \'' . Sanitize::simpleString($searchtype) . '\';';
}
?>
</script>

	<div class="breadcrumb"><?php echo $curBreadcrumb ?></div>
	<div id="headermanageqset" class="pagetitle"><h1><?php echo $pagetitle; echo $helpicon; ?></h1></div>

<?php
	if (isset($_POST['remove']) || isset($_GET['remove'])) {
?>
		Are you SURE you want to delete these questions from the Question Set.  This will make them unavailable
		to all users.  If any are currently being used in an assessment, it will mess up that assessment.
		<form method=post action="manageqset.php?cid=<?php echo $cid ?>&confirmed=true">
			<input type=hidden name=remove value="<?php echo Sanitize::encodeStringForDisplay($rlist); ?>">
			<p>
				<input type=submit value="Really Delete">
				<input type=button value="Nevermind" class="secondarybtn" onclick="window.location='manageqset.php?cid=<?php echo $cid ?>'">
			</p>
		</form>
<?php
	} else if (isset($_POST['transfer']) || isset($_GET['transfer'])) {
?>
		<form method=post action="manageqset.php?cid=<?php echo $cid ?>">
			<input type=hidden name=transfer value="<?php echo Sanitize::encodeStringForDisplay($tlist); ?>">

			<?php //writeHtmlSelect("newowner",$page_transferUserList['val'],$page_transferUserList['label']); ?>
            <?php require_once '../includes/userlookupform.php'; 
                generateUserLookupForm(_('Transfer question ownership to:'), 'newowner');
            ?>

			<p>
				<input type=submit value="Transfer">
				<input type=button value="Nevermind" class="secondarybtn" onclick="window.location='manageqset.php?cid=<?php echo $cid ?>'">
			</p>
		</form>
<?php
	} else if (isset($_POST['chglib'])) {
?>
		<script type="text/javascript">
		var chgliblaststate = 0;
		var existinglibs = [<?php echo implode(',', array_map(function($i){return '"lib'.$i.'"';}, $checkedlibs));?>];
		function chglibtoggle(rad) {
			var val = rad.value;
			var help = document.getElementById("chglibhelp");
			if (val==0) {
				help.innerHTML = "Select libraries to add these questions to. ";
				if (chgliblaststate==2) {
					treeWidget.unselectAll();
				}
			} else if (val==1 || val==3) {
				help.innerHTML = "Select libraries to add these questions to.  Questions will only be removed from existing libraries if you have the rights to make those changes.";
				if (chgliblaststate==2) {
					treeWidget.unselectAll();
				}
			} else if (val==2) {
				help.innerHTML = "Unselect the libraries you want to remove questions from.  The questions will not be deleted; they will be moved to Unassigned if no other library assignments exist.  Questions will only be removed from existing libraries if you have the rights to make those changes.";
				if (chgliblaststate==0 || chgliblaststate==1 || chgliblaststate==3) {
					treeWidget.setSelectedItems(existinglibs);
				}
			}
			chgliblaststate = val;
		}
		</script>
		<form method=post action="manageqset.php?cid=<?php echo $cid ?>">
			<input type=hidden name=chglib value="true">
			<input type=hidden name=qtochg value="<?php echo Sanitize::encodeStringForDisplay($clist); ?>">
			What do you want to do with these questions?<br/>
			<label><input type=radio name="action" value="0" onclick="chglibtoggle(this)" checked="checked"/> Add to libraries, keeping any existing library assignments</label><br/>
			<label><input type=radio name="action" value="1" onclick="chglibtoggle(this)"/> Add to libraries, removing existing library assignments</label><br/>
			<?php
			if (isset($_SESSION['searchtypec'.$cid]) && isset($_SESSION['lastsearchlibsc'.$cid]) && $_SESSION['searchtypec'.$cid]=='libs' && $_SESSION['lastsearchlibsc'.$cid]!='0') {
				echo '<label><input type=radio name="action" value="3" onclick="chglibtoggle(this)"/> Add to libraries, removing library assignment in currently listed libraries</label><br/>';
			}
			?>
			<label><input type=radio name="action" value="2" onclick="chglibtoggle(this)"/> Remove library assignments</label>
			<p id="chglibhelp" style="font-weight: bold;">
			Select libraries to add these questions to.
			</p>

			<?php 
			$select = 'children';
			$mode = 'multi';
			$_GET['selectrights'] = 1;
			require_once "libtree3.php"; 
			
			?>


			<p>
				<input type=submit value="Make Changes">
				<input type=button value="Nevermind" class="secondarybtn" onclick="window.location='manageqset.php?cid=<?php echo $cid ?>'">
			</p>
		</form>
<?php
	} /* else if (isset($_POST['template'])) {
?>

		<form method=post action="manageqset.php?cid=<?php echo $cid ?>">
			<input type=hidden name=template value="true">

			<p>
				This page will create new copies of these questions.  It is recommended that you place these new copies in a
				different library that the questions are currently are in, so you can distinguish the new versions from the originals.
			</p>
			<p>Select the library into which to put the new copies:</p>

			<input type=hidden name=qtochg value="<?php echo Sanitize::encodeStringForDisplay($clist); ?>">

			<?php require_once "libtree.php"; ?>

			<p>
				<input type=submit value="Template Questions">
				<input type=button value="Nevermind" class="secondarybtn" onclick="window.location='manageqset.php?cid=<?php echo $cid ?>'">
			</p>
		</form>
<?php
	} */ else if (isset($_POST['license'])) {
?>

	<form method=post action="manageqset.php?cid=<?php echo $cid ?>">
		<input type=hidden name="license" value="true">

		<input type=hidden name=qtochg value="<?php echo Sanitize::encodeStringForDisplay($clist); ?>">

		<p>This will allow you to change the license or attribution on questions, if you have the rights to change them</p>

		<p>Note:  Be cautious when changing licenses or attribution on questions.  Some important things to note:
		<ul>
		 <li>If questions are currently copyrighted or contain copyrighted content, you CAN NOT change the license
		     unless you have removed all copyrighted material from the question.</li>
		 <li>If questions are licensed under the IMathAS Community License or a Creative Commons license, you CAN NOT
		     change the license unless you are the creator of the questions and all questions it was previously derived from.</li>
		 <li>If the question currently has additional attribution listed, you CAN NOT remove that attribution unless
		     you have removed from the question all parts that require the attribution.</li>
		</ul>
		<p class=noticetext>
		  In short, you should only be changing license if the questions are your original works, not built on top of existing
		  community work.
		<p>
		<p>
		License: <select name="sellicense">
			<option value="-1">Do not change license</option>
			<option value="0">Copyrighted</option>
			<option value="1">IMathAS / WAMAP / MyOpenMath Community License</option>
			<option value="2">Public Domain</option>
			<option value="3">Creative Commons Attribution-NonCommercial-ShareAlike</option>
			<option value="4">Creative Commons Attribution-ShareAlike</option>
		</select>
		</p>
		<p>Other Attribution: <select name="otherattribtype">
			<option value="-1">Do not change attribution</option>
			<option value="0">Replace existing attribution</option>
			<option value="1">Append to existing attribution</option>
			</select><br/>
			Additional Attribution: <input type="text" size="80" name="addattr" />
		</p>

			<input type=submit value="Change License / Attribution">
			<input type=button value="Nevermind" class="secondarybtn" onclick="window.location='manageqset.php?cid=<?php echo $cid ?>'">
		</p>
	</form>
<?php
	} else if (isset($_POST['chgrights'])) {
?>
		<form method=post action="manageqset.php?cid=<?php echo $cid ?>">
			<input type=hidden name="chgrights" value="true">

			<p>
				This will allow you to change the use rights of the selected questions, if you can change those rights.
			</p>
			<p>Select the new rights for these questions: <select name="newrights">
			 	<option value="0">Private</option>
				<option value="2" selected="selected">Allow use, use as template, no modifications</option>
				<option value="3">Allow use by all and modifications by group</option>
				<option value="4">Allow use and modifications by all</option>
				</select>
			</p>

			<input type="hidden" name="qtochg" value="<?php echo Sanitize::encodeStringForDisplay($clist); ?>">


			<p>
				<input type=submit value="Change Rights">
				<input type=button value="Nevermind" class="secondarybtn" onclick="window.location='manageqset.php?cid=<?php echo $cid ?>'">
			</p>
		</form>
<?php
	} else if (isset($_GET['remove'])) {
?>
		Are you SURE you want to delete this question from the Question Set.  This will make it unavailable
		to all users.  If it is currently being used in an assessment, it will mess up that assessment.

		<form method=post action="manageqset.php?cid=<?php echo $cid ?>&confirmed=true">
			<input type=hidden name=remove value="<?php echo Sanitize::onlyInt($_GET['remove']); ?>">
			<p>
				<input type=submit value="Really Delete">
				<input type=button value="Nevermind" class="secondarybtn" onclick="window.location='manageqset.php?cid=<?php echo $cid ?>'">
			</p>
		</form>
<?php
	} else if (isset($_GET['transfer'])) {
?>

		<form method=post action="manageqset.php?cid=<?php echo $cid ?>">
			<input type=hidden name=transfer value="<?php echo Sanitize::onlyInt($_GET['transfer']); ?>">
			
			<?php //writeHtmlSelect("newowner",$page_transferUserList['val'],$page_transferUserList['label']); ?>
            <?php require_once '../includes/userlookupform.php'; 
                generateUserLookupForm(_('Transfer question ownership to:'), 'newowner');
            ?>
			<p>
				<input type=submit value="Transfer">
				<input type=button value="Nevermind" class="secondarybtn" onclick="window.location='manageqset.php?cid=<?php echo $cid ?>'">
			</p>
		</form>

<?php
	} else { //DEFAULT DISPLAY

		echo $page_adminMsg;

		require_once('../includes/questionsearch.php');
		outputSearchUI($searchtype, $searchterms, $search_results);

		echo "<script type=\"text/javascript\" src=\"$staticroot/javascript/tablesorter.js?v=082913\"></script>\n";
		echo "<form id=\"selq\" method=post action=\"manageqset.php?cid=$cid\">\n";
		//echo "Check/Uncheck All: <input type=\"checkbox\" name=\"ca2\" value=\"1\" onClick=\"chkAll(this.form, 'nchecked[]', this.checked)\">\n";
		echo '<div class=pdiv>Check: <a href="#" onclick="return chkAllNone(\'selq\',\'nchecked[]\',true)">All</a> <a href="#" onclick="return chkAllNone(\'selq\',\'nchecked[]\',false)">None</a> ';

        echo '<span class="dropdown">';
		echo ' <a role=button tabindex=0 class="dropdown-toggle arrow-down" id="dropdownMenuWithsel" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
		echo _('With Selected').'</a>';
		echo '<ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenuWithsel">';
		echo ' <li><a href="#" onclick="postWSform(\'transfer\');return false;" title="',_("Transfer question ownership"),'">', _('Transfer'), "</a></li>";
		echo ' <li><a href="#" onclick="postWSform(\'remove\');return false;">', _('Delete'), "</a></li>";
		echo ' <li><a href="#" onclick="postWSform(\'chglib\');return false;" title="',_("Change library assignments"),'">',_('Library Assignment'), "</a></li>";
        echo ' <li><a href="#" onclick="postWSform(\'chgrights\');return false;" title="',_("Change use rights"),'">',_('Change Rights'), "</a></li>";
        echo ' <li><a href="#" onclick="postWSform(\'license\');return false;" title="',_("Change license or attribution"),'">',_('Change License'), "</a></li>";
		echo '</ul></span>';

        echo " <input type=button value=\"Add New Question\" onclick=\"window.location='moddataset.php?cid=$cid'\">\n";

		if (!$isadmin && !$isgrpadmin) {
			echo "<br/>(Delete and Transfer only applies to your questions)\n";
		} else if ($isgrpadmin) {
			echo "<br/>(Delete and Transfer only apply to group's questions)\n";
		}
        echo '</div>';
		echo '<table cellpadding="5" id="myTable" class="gb zebra potential-question-list" style="clear:both; position:relative;" tabindex="-1"></table>';
        echo '<p><span id="searchnums">' . _('Showing') . ' <span id="searchnumvals"></span></span>
                <a href="#" id="searchprev" style="display:none">' . _('Previous Results'). ' </a>
                <a href="#" id="searchnext" style="display:none">' . _('More Results'). ' </a>
            </p>';
		echo "</form>\n";
        echo '<script type="text/javascript">
            $(function() {
                displayQuestionList(' . json_encode($search_results, JSON_INVALID_UTF8_IGNORE) . ');
                setlibhistory();
            });
            </script>';
		echo "<p></p>\n";
	}

}
require_once "../footer.php";


function delqimgs($qsid) {
  global $DBH;
	$del_stm = $DBH->prepare("DELETE FROM imas_qimages WHERE id=:id");
	$stm2 = $DBH->prepare("SELECT id FROM imas_qimages WHERE filename=:filename");

	$stm = $DBH->prepare("SELECT id,filename,var FROM imas_qimages WHERE qsetid=:qsetid");
	$stm->execute(array(':qsetid'=>$qsid));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		if (substr($row[1],0,4)!='http') {
			$stm2->execute(array(':filename'=>$row[1]));
			if ($stm2->rowCount()==1) {
				unlink(rtrim(dirname(__FILE__), '/\\') .'/../assessment/qimages/'.$row[1]);
			}
		}
		$del_stm->execute(array(':id'=>$row[0]));
	}
}
?>

<?php
//IMathAS:  Main admin page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../init.php");
require("../includes/htmlutil.php");

 //set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = "Manage Question Sets";
$helpicon = "";

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

				//delete all library items for that question, regardless of owner
				$stm = $DBH->prepare("UPDATE imas_library_items SET deleted=1,lastmoddate=:now WHERE qsetid IN ($remlist) AND deleted=0");
				$stm->execute(array(':now'=>$now));

				//now delete the questions
				$stm = $DBH->prepare("UPDATE imas_questionset SET deleted=1,lastmoddate=:now WHERE id IN ($remlist)");
				$stm->execute(array(':now'=>$now));

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
				//DB $translist = "'".implode("','",explode(',',$_POST['transfer']))."'";
				$translist = implode(',', array_map('intval', explode(',',$_POST['transfer'])));

				if ($isgrpadmin) {
					//DB $query = "SELECT imas_questionset.id FROM imas_questionset,imas_users WHERE imas_questionset.ownerid=imas_users.id AND imas_questionset.id IN ($translist) AND imas_users.groupid='$groupid'";
					//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
					//DB while ($row = mysql_fetch_row($result)) {
					$stm = $DBH->prepare("SELECT imas_questionset.id FROM imas_questionset,imas_users WHERE imas_questionset.ownerid=imas_users.id AND imas_questionset.id IN ($translist) AND imas_users.groupid=:groupid");
					$stm->execute(array(':groupid'=>$groupid));
					$upd_stm = $DBH->prepare("UPDATE imas_questionset SET ownerid=:ownerid WHERE id=:id");
					while ($row = $stm->fetch(PDO::FETCH_NUM)) {
						//DB $query = "UPDATE imas_questionset SET ownerid='{$_POST['newowner']}' WHERE id='{$row[0]}'";
						//DB mysql_query($query) or die("Query failed : $query " . mysql_error());
						$upd_stm->execute(array(':ownerid'=>$_POST['newowner'], ':id'=>$row[0]));
					}

				} else {
					if (!$isadmin) {
						//DB $query = "UPDATE imas_questionset SET ownerid='{$_POST['newowner']}' WHERE id IN ($translist)";
						//DB $query .= " AND ownerid='$userid'";
						$query = "UPDATE imas_questionset SET ownerid=:ownerid WHERE id IN ($translist)";
						$query .= " AND ownerid=:ownerid2";
						$stm = $DBH->prepare($query);
						$stm->execute(array(':ownerid'=>$_POST['newowner'], ':ownerid2'=>$userid));
					} else {
						//DB $query = "UPDATE imas_questionset SET ownerid='{$_POST['newowner']}' WHERE id IN ($translist)";
						$stm = $DBH->prepare("UPDATE imas_questionset SET ownerid=:ownerid WHERE id IN ($translist)");
						$stm->execute(array(':ownerid'=>$_POST['newowner']));
					}
					//DB mysql_query($query) or die("Query failed : $query " . mysql_error());
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

			//DB $query = "SELECT id,FirstName,LastName FROM imas_users WHERE rights>19 ORDER BY LastName,FirstName";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->query("SELECT id,FirstName,LastName FROM imas_users WHERE rights>19 ORDER BY LastName,FirstName");
			$i=0;
			$page_transferUserList = array();
			//DB while ($row = mysql_fetch_row($result)) {
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$page_transferUserList['val'][$i] = $row[0];
				$page_transferUserList['label'][$i] = $row[2] . ", " . $row[1];
				$i++;
			}

		}
	} else if (isset($_POST['chglib'])) {
		if (isset($_POST['qtochg'])) {
			if ($_POST['chglib']!='') {
				$newlibs = $_POST['libs']; //array is sanitized later
				if ($_POST['libs']=='') {
					$newlibs = array();
				} else {
					$newlibs = array_map('intval', $newlibs);
					if ($newlibs[0]==0) { //get rid of unassigned if checked
						array_shift($newlibs);
					}
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
						if ($sessiondata['lastsearchlibs'.$cid]!='') {
							$listedlibs = explode(',', $sessiondata['lastsearchlibs'.$cid]);
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
							$toremove = array_diff($mylibs[$qsetid],$newlibs);
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
				//DB $query = "SELECT DISTINCT ili.libid FROM imas_library_items AS ili WHERE ili.qsetid IN ($clist)";
				//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
				//DB while ($row = mysql_fetch_row($result)) {
				$stm = $DBH->query("SELECT DISTINCT ili.libid FROM imas_library_items AS ili WHERE ili.qsetid IN ($clist) AND deleted=0");
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					$checked[] = $row[0];
				}
				$_GET['selectrights'] = 1;
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
			//DB $query = "SELECT firstName,lastName FROM imas_users WHERE id='$userid'";
			//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
			//DB $row = mysql_fetch_row($result);
			$stm = $DBH->prepare("SELECT firstName,lastName FROM imas_users WHERE id=:id");
			$stm->execute(array(':id'=>$userid));
			$row = $stm->fetch(PDO::FETCH_NUM);
			$myname = $row[1].','.$row[0];
			foreach ($qtochg as $k=>$qid) {
				$ancestors = ''; $ancestorauthors = '';
				//DB $query = "SELECT description,userights,qtype,control,qcontrol,qtext,answer,hasimg,ancestors,ancestorauthors,license,author FROM imas_questionset WHERE id='$qid'";
				//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
				//DB $row = mysql_fetch_row($result);
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
				//DB $query = "INSERT INTO imas_questionset (uniqueid,adddate,lastmoddate,ownerid,author,description,userights,qtype,control,qcontrol,qtext,answer,hasimg,ancestors,ancestorauthors,license) VALUES ";
        //DB $query .= "('$uqid','$now','$now','$userid','$myname',$row['description'],$row['userights'],$row['qtype'],$row['control'],$row['qcontrol'],$row['qtext'],$row['answer'],$row['hasimg'],$row['ancestors'],$row['ancestorauthors'],$row['license'])";
				$query = "INSERT INTO imas_questionset (uniqueid,adddate,lastmoddate,ownerid,author,description,userights,qtype,control,qcontrol,qtext,answer,hasimg,ancestors,ancestorauthors,license) VALUES ";
        $query .= "(:uniqueid, :adddate, :lastmoddate, :ownerid, :author, :description, :userights, :qtype, :control, :qcontrol, :qtext, :answer, :hasimg, :ancestors, :ancestorauthors, :license)";
        $stm = $DBH->prepare($query);
        $stm->execute(array(':uniqueid'=>$uqid, ':adddate'=>$now, ':lastmoddate'=>$now, ':ownerid'=>$userid, ':author'=>$myname,
          ':description'=>$row['description'], ':userights'=>$row['userights'], ':qtype'=>$row['qtype'], ':control'=>$row['control'],
          ':qcontrol'=>$row['qcontrol'], ':qtext'=>$row['qtext'], ':answer'=>$row['answer'], ':hasimg'=>$row['hasimg'],
          ':ancestors'=>$ancestors, ':ancestorauthors'=>$ancestorauthors, ':license'=>$row['license']));
				//DB $query .= "('$uqid','$now','$now','$userid','$myname','".implode("','",addslashes_deep($row))."')";
				//DB mysql_query($query) or die("Query failed : $query" . mysql_error());
				//DB $nqid = mysql_insert_id();
				$nqid = $DBH->lastInsertId();
				//DB $query = "INSERT INTO imas_library_items (libid,qsetid,ownerid) VALUES ('$lib','$nqid','$userid')";
				//DB mysql_query($query) or die("Query failed :$query " . mysql_error());
				$stm = $DBH->prepare("INSERT INTO imas_library_items (libid,qsetid,ownerid) VALUES (:libid, :qsetid, :ownerid)");
				$stm->execute(array(':libid'=>$lib, ':qsetid'=>$nqid, ':ownerid'=>$userid));

				//DB $query = "SELECT var,filename,alttext FROM imas_qimages WHERE qsetid='$qid'";
				//DB $result = mysql_query($query) or die("Query failed :$query " . mysql_error());
				//DB while ($row = mysql_fetch_row($result)) {
				$stm = $DBH->prepare("SELECT var,filename,alttext FROM imas_qimages WHERE qsetid=:qsetid");
				$stm->execute(array(':qsetid'=>$qid));
        $img_ins_stm = $DBH->prepare("INSERT INTO imas_qimages (qsetid,var,filename,alttext) VALUES (:qsetid, :var, :filename, :alttext)");
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					//DB $query = "INSERT INTO imas_qimages (qsetid,var,filename,alttext) VALUES ('$nqid','{$row[0]}','{$row[1]}','{$row[2]}')";
					//DB mysql_query($query) or die("Query failed :$query " . mysql_error());
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
          //DB $query = "UPDATE imas_questionset SET license=".intval($_POST['sellicense'])." WHERE id IN ($qtochg)";
					//DB $query .= " AND ownerid='$userid'";
          $query = "UPDATE imas_questionset SET license=:license WHERE id IN ($qtochg)";
					$query .= " AND ownerid=:ownerid";
					$stm = $DBH->prepare($query);
					$stm->execute(array(':license'=>$_POST['sellicense'], ':ownerid'=>$userid));
				} else {
          //DB $query = "UPDATE imas_questionset SET license=".intval($_POST['sellicense'])." WHERE id IN ($qtochg)";
          $stm = $DBH->prepare("UPDATE imas_questionset SET license=:license WHERE id IN ($qtochg)");
          $stm->execute(array(':license'=>$_POST['sellicense']));
        }
				//DB mysql_query($query) or die("Query failed : $query" . mysql_error());
			}
			if ($_POST['otherattribtype']!=-1) {
				if ($_POST['otherattribtype']==0) {
					if (!$isadmin) {
            //DB $query = "UPDATE imas_questionset SET otherattribution='{$_POST['addattr']}' WHERE id IN ($qtochg)";
						//DB $query .= " AND ownerid='$userid'";
            $query = "UPDATE imas_questionset SET otherattribution=:otherattribution WHERE id IN ($qtochg)";
						$query .= " AND ownerid=:ownerid";
						$stm = $DBH->prepare($query);
						$stm->execute(array(':otherattribution'=>$_POST['addattr'], ':ownerid'=>$userid));
					} else {
            //DB $query = "UPDATE imas_questionset SET otherattribution='{$_POST['addattr']}' WHERE id IN ($qtochg)";
            $stm = $DBH->prepare("UPDATE imas_questionset SET otherattribution=:otherattribution WHERE id IN ($qtochg)");
            $stm->execute(array(':otherattribution'=>$_POST['addattr']));
          }
					//DB mysql_query($query) or die("Query failed : $query" . mysql_error());
				} else {
					if (!$isadmin) {
            //DB $query = "SELECT id,otherattribution FROM imas_questionset WHERE id IN ($qtochg)";
						//DB $query .= " AND ownerid='$userid'";
            $query = "SELECT id,otherattribution FROM imas_questionset WHERE id IN ($qtochg)";
						$query .= " AND ownerid=:ownerid";
						$stm = $DBH->prepare($query);
						$stm->execute(array(':ownerid'=>$userid));
					} else {
            //DB $query = "SELECT id,otherattribution FROM imas_questionset WHERE id IN ($qtochg)";
            $stm = $DBH->query("SELECT id,otherattribution FROM imas_questionset WHERE id IN ($qtochg)");
          }
					//DB $result = mysql_query($query) or die("Query failed : $query" . mysql_error());
					//DB while ($row = mysql_fetch_row($result)) {
          $upd_stm = $DBH->prepare("UPDATE imas_questionset SET otherattribution=:otherattribution WHERE id=:id");
					while ($row = $stm->fetch(PDO::FETCH_NUM)) {
						//DB $attr = addslashes($row[1]) . $_POST['addattr'];
            $attr = $row[1] . $_POST['addattr'];
						//DB $query = "UPDATE imas_questionset SET otherattribution='$attr' WHERE id={$row[0]}";
						//DB mysql_query($query) or die("Query failed : $query" . mysql_error());
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

			$clist = Sanitize::encodeStringForDisplay(implode(",",$_POST['nchecked']));

			if (!isset($_POST['nchecked'])) {
				$overwriteBody = 1;
				$body = "No questions selected.  <a href=\"manageqset.php?cid=$cid\">Go back</a>\n";
			}
		}

	} else if (isset($_POST['chgrights'])) {
		if (isset($_POST['qtochg'])) {
			//DB $chglist = "'".implode("','",explode(',',$_POST['qtochg']))."'";
			$chglist = implode(',', array_map('intval', explode(',',$_POST['qtochg'])));
			if ($isgrpadmin) {
				//DB $query = "SELECT imas_questionset.id FROM imas_questionset,imas_users WHERE imas_questionset.ownerid=imas_users.id AND imas_questionset.id IN ($chglist) AND imas_users.groupid='$groupid'";
				//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
				$stm = $DBH->prepare("SELECT imas_questionset.id FROM imas_questionset,imas_users WHERE imas_questionset.ownerid=imas_users.id AND imas_questionset.id IN ($chglist) AND imas_users.groupid=:groupid");
				$stm->execute(array(':groupid'=>$groupid));
				$tochg = array();
				//DB while ($row = mysql_fetch_row($result)) {
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					$tochg[] = $row[0];
				}
				if (count($tochg)>0) {
					$chglist = implode(',',$tochg);
					//DB $query = "UPDATE imas_questionset SET userights='{$_POST['newrights']}' WHERE id IN ($chglist)";
					//DB mysql_query($query) or die("Query failed : $query " . mysql_error());
					$stm = $DBH->prepare("UPDATE imas_questionset SET userights=:userights WHERE id IN ($chglist)");
					$stm->execute(array(':userights'=>$_POST['newrights']));
				}
			} else {
				if (!$isadmin) {
          //DB $query = "UPDATE imas_questionset SET userights='{$_POST['newrights']}' WHERE id IN ($chglist)";
					//DB $query .= " AND ownerid='$userid'";
          $query = "UPDATE imas_questionset SET userights=:userights WHERE id IN ($chglist)";
					$query .= " AND ownerid=:ownerid";
					$stm = $DBH->prepare($query);
					$stm->execute(array(':userights'=>$_POST['newrights'], ':ownerid'=>$userid));
				} else {
          //DB $query = "UPDATE imas_questionset SET userights='{$_POST['newrights']}' WHERE id IN ($chglist)";
          $stm = $DBH->prepare("UPDATE imas_questionset SET userights=:userights WHERE id IN ($chglist)");
          $stm->execute(array(':userights'=>$_POST['newrights']));
        }
				//DB mysql_query($query) or die("Query failed : $query " . mysql_error());
			}
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/manageqset.php?cid=$cid" . "&r=" . Sanitize::randomQueryStringParam());
			exit;
		} else {
			$pagetitle = "Change Question Rights";
			$curBreadcrumb .= " &gt; <a href=\"manageqset.php?cid=$cid\">Manage Question Set </a>";
			$curBreadcrumb .= " &gt; Change Question Rights";

			$clist = Sanitize::encodeStringForDisplay(implode(",", $_POST['nchecked']));

			if (!isset($_POST['nchecked'])) {
				$overwriteBody = 1;
				$body = "No questions selected.  <a href=\"manageqset.php?cid=$cid\">Go back</a>\n";
			}
		}
	} else if (isset($_GET['transfer'])) {

		//postback handled by $_POST['transfer'] block
		$pagetitle = "Transfer Ownership";
		$curBreadcrumb .= " &gt; <a href=\"manageqset.php?cid=$cid\">Manage Question Set </a>";
		$curBreadcrumb .= " &gt; Transfer QSet";

		//DB $query = "SELECT id,FirstName,LastName FROM imas_users WHERE rights>19 ORDER BY LastName,FirstName";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		$stm = $DBH->query("SELECT id,FirstName,LastName FROM imas_users WHERE rights>19 ORDER BY LastName,FirstName");
		$i=0;
		$page_transferUserList = array();
		//DB while ($row = mysql_fetch_row($result)) {
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$page_transferUserList['val'][$i] = $row[0];
			$page_transferUserList['label'][$i] = $row[2] . ", " . $row[1];
			$i++;
		}


	} else { //DEFAULT DATA MANIPULATION
		$curBreadcrumb .= " &gt; Manage Question Set";
		$pagetitle = "Question Set Management";
		$helpicon = "<img src=\"$imasroot/img/help.gif\" alt=\"Help\" onClick=\"window.open('$imasroot/help.php?section=managequestionset','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))\"/>";
		if ($isadmin) {
			$page_adminMsg =  "You are in Admin mode, which means actions will apply to all questions, regardless of owner";
		} else if ($isgrpadmin) {
			$page_adminMsg = "You are in Group Admin mode, which means actions will apply to all group's questions, regardless of owner";
		} else {
			$page_adminMsg = "";
		}
		//load filter.  Need earlier than usual header.php load
		$curdir = rtrim(dirname(__FILE__), '/\\');
		require_once("$curdir/../filter/filter.php");

			//remember search
		if (isset($_POST['search'])) {
			$safesearch = trim($_POST['search']);
			$safesearch = str_replace(' and ', ' ',$safesearch);
			//DB $search = stripslashes($safesearch);
			$search = $safesearch;
			$search = str_replace('"','&quot;',$search);
			$sessiondata['lastsearch'.$cid] = $safesearch; //str_replace(" ","+",$safesearch);
			if (isset($_POST['searchall'])) {
				$searchall = 1;
			} else {
				$searchall = 0;
			}
			$sessiondata['searchall'.$cid] = $searchall;
			if (isset($_POST['searchmine'])) {
				$searchmine = 1;
			} else {
				$searchmine = 0;
			}
			$sessiondata['searchmine'.$cid] = $searchmine;


			if ($searchall==1 && trim($search)=='' && $searchmine==0) {
				$overwriteBody = 1;
				$body = "Must provide a search term when searching all libraries <a href=\"manageqset.php\">Try again</a>";
				$searchall = 0;
			}
			$hidepriv = 0;
			$skipfederated = 0;
			if ($isadmin) {
				if (isset($_POST['hidepriv'])) {
					$hidepriv = 1;
				} else {
					$hidepriv = 0;
				}
				$sessiondata['hidepriv'.$cid] = $hidepriv;
				if (isset($_POST['skipfederated'])) {
					$skipfederated = 1;
				} else {
					$skipfederated = 0;
				}
				$sessiondata['skipfederated'.$cid] = $skipfederated;
			}

			writesessiondata();
		} else if (isset($sessiondata['lastsearch'.$cid])) {
			$safesearch = trim($sessiondata['lastsearch'.$cid]); //str_replace("+"," ",$sessiondata['lastsearch'.$cid]);
			//DB $search = stripslashes($safesearch);
			$search = $safesearch;
			$search = str_replace('"','&quot;',$search);
			$searchall = $sessiondata['searchall'.$cid];
			$searchmine = $sessiondata['searchmine'.$cid];
			$hidepriv = 0; $skipfederated = 0;
			if ($isadmin) {
				$hidepriv = $sessiondata['hidepriv'.$cid];
				$skipfederated = $sessiondata['skipfederated'.$cid];
			}
		} else {
			$search = '';
			$searchall = 0;
			$searchmine = 0;
			$hidepriv = 0;
			$skipfederated = 0;
			$safesearch = '';
		}
    $searchlikevals = array();
		$isIDsearch = false;
		if (trim($safesearch)=='') {
			$searchlikes = '';
		} else {
			if (substr($safesearch,0,6)=='regex:') {
				$safesearch = substr($safesearch,6);
        //DB $searchlikes = "imas_questionset.description REGEXP '$safesearch' AND ";
				$searchlikes = "imas_questionset.description REGEXP ? AND ";
				$searchlikevals[] = $safesearch;
			} else if ($safesearch=='isbroken') {
				$searchlikes = "imas_questionset.broken=1 AND ";
			} else if (substr($safesearch,0,7)=='childof') {
				//DB $searchlikes = "imas_questionset.ancestors REGEXP '[[:<:]]".substr($safesearch,8)."[[:>:]]' AND ";
        $searchlikes = "imas_questionset.ancestors REGEXP ? AND ";
        $searchlikevals[] = '[[:<:]]'.substr($safesearch,8).'[[:>:]]';

			} else if (substr($safesearch,0,3)=='id=') {
				//DB $searchlikes = "imas_questionset.id='".substr($safesearch,3)."' AND ";
				$searchlikes = "imas_questionset.id=? AND ";
				$searchlikevals = array(substr($safesearch,3));
				$isIDsearch = true;
			} else {
				$searchterms = explode(" ",$safesearch);
				$searchlikes = '';
				foreach ($searchterms as $k=>$v) {
					if (substr($v,0,5) == 'type=') {
						//DB $searchlikes .= "imas_questionset.qtype='".substr($v,5)."' AND ";
            $searchlikes .= "imas_questionset.qtype=? AND ";
            $searchlikevals[] = substr($v,5);
						unset($searchterms[$k]);
					}
				}
        //DB $searchlikes .= "((imas_questionset.description LIKE '%".implode("%' AND imas_questionset.description LIKE '%",$searchterms)."%') ";
				if (count($searchterms)>0) {
					$searchlikes .= "((imas_questionset.description LIKE ?".str_repeat(" AND imas_questionset.description LIKE ?",count($searchterms)-1).") ";
					foreach ($searchterms as $t) {
						$searchlikevals[] = "%$t%";
					}

					if (ctype_digit($safesearch)) {
	          //DB $searchlikes .= "OR imas_questionset.id='$safesearch') AND ";
						$searchlikes .= "OR imas_questionset.id=?) AND ";
						$searchlikevals[] = $safesearch;
						$isIDsearch = true;
					} else {
						$searchlikes .= ") AND";
					}
				}
			}
		}

		if (isset($_POST['libs'])) {
		  if ($_POST['libs']=='') {
		    $_POST['libs'] = $userdeflib;
		  }
		  $searchlibs = $_POST['libs'];
			//$sessiondata['lastsearchlibs'] = implode(",",$searchlibs);
			$sessiondata['lastsearchlibs'.$cid] = $searchlibs;
			writesessiondata();
		} else if (isset($_GET['listlib'])) {
			$searchlibs = $_GET['listlib'];
			$sessiondata['lastsearchlibs'.$cid] = $searchlibs;
			$searchall = 0;
			$sessiondata['searchall'.$cid] = $searchall;
			$sessiondata['lastsearch'.$cid] = '';
			$searchlikes = '';
			$searchlikevals = array();
			$search = '';
			$safesearch = '';
			writesessiondata();
		}else if (isset($sessiondata['lastsearchlibs'.$cid])) {
			//$searchlibs = explode(",",$sessiondata['lastsearchlibs']);
			$searchlibs = $sessiondata['lastsearchlibs'.$cid];
		} else {
			$searchlibs = $userdeflib;
		}

		//DB $llist = "'".implode("','",explode(',',$searchlibs))."'";
		$llist = implode(',', array_map('intval', explode(',',$searchlibs)));

		$libsortorder = array();
		if (substr($searchlibs,0,1)=="0") {
			$lnamesarr[0] = "Unassigned";
			$libsortorder[0] = 0;
		}

		//DB $query = "SELECT name,id,sortorder FROM imas_libraries WHERE id IN ($llist)";
		//DB $result = mysql_query($query) or die("Query failed: $query: " . mysql_error());
		//DB while ($row = mysql_fetch_row($result)) {
		$stm = $DBH->query("SELECT name,id,sortorder FROM imas_libraries WHERE id IN ($llist)");
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$lnamesarr[$row[1]] = $row[0];
			$libsortorder[$row[1]] = $row[2];
		}
		if (count($lnamesarr)>0) {
			$lnames = implode(", ",$lnamesarr);
		} else {$lnames = '';}

		/*
		if ($searchall==1 && trim($search)=='') {
			$overwriteBody = 1;
			$body = "Must provide a search term when searching all libraries";
		}
		*/
    $qarr = $searchlikevals;
		//DB $query = "SELECT DISTINCT imas_questionset.id,imas_questionset.ownerid,imas_questionset.description,imas_questionset.userights,imas_questionset.lastmoddate,imas_questionset.extref,imas_questionset.replaceby,";
		//DB $query .= "imas_questionset.qtype,imas_users.firstName,imas_users.lastName,imas_users.groupid,imas_library_items.libid,imas_library_items.junkflag, imas_library_items.id AS libitemid ";
		//DB $query .= "FROM imas_questionset,imas_library_items,imas_users WHERE imas_questionset.deleted=0 AND $searchlikes ";
		//DB $query .= "imas_library_items.qsetid=imas_questionset.id AND imas_questionset.ownerid=imas_users.id ";
		$query = "SELECT DISTINCT imas_questionset.id,imas_questionset.ownerid,imas_questionset.description,imas_questionset.userights,imas_questionset.lastmoddate,imas_questionset.extref,imas_questionset.replaceby,";
		$query .= "imas_questionset.qtype,imas_users.firstName,imas_users.lastName,imas_users.groupid,imas_library_items.libid,imas_library_items.junkflag, imas_library_items.id AS libitemid ";
		$query .= "FROM imas_questionset,imas_library_items,imas_users WHERE imas_questionset.deleted=0 AND imas_library_items.deleted=0 AND $searchlikes ";
		$query .= "imas_library_items.qsetid=imas_questionset.id AND imas_questionset.ownerid=imas_users.id ";

		if ($isadmin) {
			if ($hidepriv==1) {
				$query .= " AND imas_questionset.userights>0";
			}
		} else if ($isgrpadmin) {
			//DB $query .= "AND (imas_users.groupid='$groupid' OR imas_questionset.userights>0) ";
			//DB $query .= "AND (imas_library_items.libid > 0 OR imas_users.groupid='$groupid')";
			$query .= "AND (imas_users.groupid=? OR imas_questionset.userights>0) ";
			$qarr[] = $groupid;
			if ($isIDsearch) {
				$query .= "AND (imas_library_items.libid > 0 OR imas_users.groupid=? OR imas_questionset.id=?)";
				$qarr[] = $groupid;
				$qarr[] = $safesearch;
			} else {
				$query .= "AND (imas_library_items.libid > 0 OR imas_users.groupid=?)";
				$qarr[] = $groupid;
			}
		} else {
			//DB $query .= "AND (imas_questionset.ownerid='$userid' OR imas_questionset.userights>0) ";
			//DB $query .= "AND (imas_library_items.libid > 0 OR imas_questionset.ownerid='$userid')";
			$query .= "AND (imas_questionset.ownerid=? OR imas_questionset.userights>0) ";
			$qarr[] = $userid;
			if ($isIDsearch) {
				$query .= "AND (imas_library_items.libid > 0 OR imas_questionset.ownerid=? OR imas_questionset.id=?)";
				$qarr[] = $userid;
				$qarr[] = $safesearch;
			} else {
				$query .= "AND (imas_library_items.libid > 0 OR imas_questionset.ownerid=?)";
				$qarr[] = $userid;
			}
		}
		if ($searchall==0) {
			$query .= " AND imas_library_items.libid IN ($llist)";
		}
		if ($searchmine==1) {
			//DB $query .= " AND imas_questionset.ownerid='$userid'";
			$query .= " AND imas_questionset.ownerid=?";
			$qarr[] = $userid;
		}
		if ($skipfederated==1) {
			$query .= " AND imas_questionset.id NOT IN (SELECT iq.id FROM imas_questionset AS iq JOIN imas_library_items as ili on ili.qsetid=iq.id AND ili.deleted=0";
			$query .= " JOIN imas_libraries AS il ON ili.libid=il.id AND il.deleted=0 WHERE il.federationlevel>0)";
		}
		$query.= " ORDER BY imas_library_items.libid,imas_library_items.junkflag,imas_questionset.replaceby,imas_questionset.id ";
		if ($searchall==1 || (($isadmin || $isgrpadmin) && $llist{0}=='0')) {
			$query .= " LIMIT 300";
		}

		//DB $resultLibs = mysql_query($query) or die("Query failed : " . mysql_error
		$resultLibs = $DBH->prepare($query);
		$resultLibs->execute($qarr);
		//DB $searchlimited = (mysql_num_rows($resultLibs)==300);
		$searchlimited = ($resultLibs->rowCount()==300);
		$page_questionTable = array();
		$page_libstouse = array();
		$page_libqids = array();
		$lastlib = -1;
		$ln=1;

		//DB while ($line = mysql_fetch_array($resultLibs, MYSQL_ASSOC)) {
		while ($line = $resultLibs->fetch(PDO::FETCH_ASSOC)) {
			if (isset($page_questionTable[$line['id']])) {
				continue;
			}
			if ($lastlib!=$line['libid'] && (isset($lnamesarr[$line['libid']]) || $searchall==1)) {
				$page_libstouse[] = $line['libid'];
				$lastlib = $line['libid'];
				$page_libqids[$line['libid']] = array();
			}
			if ($libsortorder[$line['libid']]==1) { //alpha
				$page_libqids[$line['libid']][$line['id']] = trim($line['description']);
			} else { //id
				$page_libqids[$line['libid']][] = $line['id'];
			}
			$i = $line['id'];

			$page_questionTable[$i]['checkbox'] = "<input type=checkbox name='nchecked[]' value='" . Sanitize::onlyInt($line['id']) . "' id='qo$ln'>";
			if ($line['userights']==0) {
				$page_questionTable[$i]['desc'] = '<span class="noticetext">'.filter(Sanitize::encodeStringForDisplay($line['description'])).'</span>';
			} else if ($line['replaceby']>0 || $line['junkflag']>0) {
				$page_questionTable[$i]['desc'] = '<span class="grey"><i>'.filter(Sanitize::encodeStringForDisplay($line['description'])).'</i></span>';
			} else {
				$page_questionTable[$i]['desc'] = filter(Sanitize::encodeStringForDisplay($line['description']));
			}

			if ($line['extref']!='') {
				$page_questionTable[$i]['cap'] = 0;
				$extref = explode('~~',$line['extref']);
				$hasvid = false;  $hasother = false; $hascap = false;
				foreach ($extref as $v) {
					if (strtolower(substr($v,0,5))=="video" || strpos($v,'youtube.com')!==false || strpos($v,'youtu.be')!==false) {
						$hasvid = true;
						if (strpos($v,'!!1')!==false) {
							$page_questionTable[$i]['cap'] = 1;
						}
					} else {
						$hasother = true;
					}
				}
				$page_questionTable[$i]['extref'] = '';
				if ($hasvid) {
					$page_questionTable[$i]['extref'] .= "<img src=\"$imasroot/img/video_tiny.png\" alt=\"Video\"/>";
				}
				if ($hasother) {
					$page_questionTable[$i]['extref'] .= "<img src=\"$imasroot/img/html_tiny.png\" alt=\"Help Resource\"/>";
				}
			}


			$page_questionTable[$i]['preview'] = "<input type=button value=\"Preview\" onClick=\"previewq('selform',$ln,".Sanitize::onlyInt($line['id']).")\"/>";
			$page_questionTable[$i]['type'] = $line['qtype'];
			if ($searchall==1) {
				$page_questionTable[$i]['lib'] = "<a href=\"manageqset.php?cid=$cid&listlib={$line['libid']}\">List lib</a>";
			} else {
				$page_questionTable[$i]['junkflag'] = $line['junkflag'];
				$page_questionTable[$i]['libitemid'] = $line['libitemid'];
			}
			$page_questionTable[$i]['times'] = 0;

			if ($isadmin || $isgrpadmin) {
				$page_questionTable[$i]['mine'] = Sanitize::encodeStringForDisplay($line['lastName']) . ',' . Sanitize::encodeStringForDisplay(substr($line['firstName'],0,1));
				if ($line['userights']==0) {
					$page_questionTable[$i]['mine'] .= ' <i>Priv</i>';
				}
			} else if ($line['ownerid']==$userid) {
				if ($line['userights']==0) {
					$page_questionTable[$i]['mine'] = '<i>Priv</i>';
				} else {
					$page_questionTable[$i]['mine'] = 'Yes';
				}
			} else {
				$page_questionTable[$i]['mine'] = '';
			}
			$page_questionTable[$i]['action'] = "<select onchange=\"doaction(this.value,".Sanitize::onlyInt($line['id']).")\"><option value=\"0\">Action..</option>";
			if ($isadmin || ($isgrpadmin && $line['groupid']==$groupid) || $line['ownerid']==$userid || ($line['userights']==3 && $line['groupid']==$groupid) || $line['userights']>3) {
				$page_questionTable[$i]['action'] .= '<option value="mod">Modify Code</option>';
			} else {
				$page_questionTable[$i]['action'] .= '<option value="mod">View Code</option>';
			}
			$page_questionTable[$i]['action'] .= '<option value="temp">Template (copy)</option>';
			if ($isadmin || ($isgrpadmin && $line['groupid']==$groupid) || $line['ownerid']==$userid) {
				$page_questionTable[$i]['action'] .= '<option value="del">Delete</option>';
				$page_questionTable[$i]['action'] .= '<option value="tr">Transfer</option>';
			}
			$page_questionTable[$i]['action'] .= '</select>';


			$page_questionTable[$i]['lastmod'] =  date("m/d/y",$line['lastmoddate']);
			$page_questionTable[$i]['add'] = "<a href=\"modquestion.php?qsetid={$line['id']}&cid=$cid\">Add</a>";
			$ln++;
		}
		//pull question useage data
		if (count($page_questionTable)>0) {
			$allusedqids = implode(',', array_map('intval', array_keys($page_questionTable)));
			//DB $query = "SELECT questionsetid,COUNT(id) FROM imas_questions WHERE questionsetid IN ($allusedqids) GROUP BY questionsetid";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB while ($row = mysql_fetch_row($result)) {
			$stm = $DBH->query("SELECT questionsetid,COUNT(id) FROM imas_questions WHERE questionsetid IN ($allusedqids) GROUP BY questionsetid");
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$page_questionTable[$row[0]]['times'] = $row[1];
			}
		}

		//sort alpha sorted libraries
		foreach ($page_libstouse as $libid) {
			if ($libsortorder[$libid]==1) {
				natcasesort($page_libqids[$libid]);
				$page_libqids[$libid] = array_keys($page_libqids[$libid]);
			}
		}
		if ($searchall==1) {
			$page_libstouse = array_keys($page_libqids);
		}
	}

}

/******* begin html output ********/
$placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/junkflag.js\"></script>";
$placeinhead .= "<script type=\"text/javascript\">var JunkFlagsaveurl = '" . $GLOBALS['basesiteurl'] . "/course/savelibassignflag.php';";
$placeinhead .= '$(function(){$(".wlf").attr("title","'.('Flag a question if it is in the wrong library').'");});</script>';
if ($_POST['chglib']) {
	$placeinhead .= '<link rel="stylesheet" href="'.$imasroot.'/course/libtree.css" type="text/css" />';
	$placeinhead .= '<script type="text/javascript" src="'.$imasroot.'/javascript/libtree2.js?v=031111"></script>';
}
require("../header.php");

$address = $GLOBALS['basesiteurl'] . '/course';

if ($overwriteBody==1) {
	echo $body;
} else {
?>
<script type="text/javascript">
function previewq(formn,loc,qn) {
	var addr = '<?php echo $imasroot ?>/course/testquestion.php?cid=<?php echo $cid ?>&checked=0&qsetid='+qn+'&loc=qo'+loc+'&formn='+formn;
	previewpop = window.open(addr,'Testing','width='+(.4*screen.width)+',height='+(.8*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(.6*screen.width-20));
	previewpop.focus();
}
function sethighlightrow(loc) {
	$("tr.highlight").removeClass("highlight");
	$("#"+loc).closest("tr").addClass("highlight");	
}
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

var curlibs = '<?php echo Sanitize::encodeStringForDisplay($searchlibs); ?>';

function libselect() {
	window.open('libtree2.php?cid=<?php echo $cid ?>&libtree=popup&libs='+curlibs,'libtree','width=400,height='+(.7*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420));
}
function setlib(libs) {
	document.getElementById("libs").value = libs;
	curlibs = libs;
}
function setlibnames(libn) {
	document.getElementById("libnames").innerHTML = libn;
}
function getnextprev(formn,loc) {
	var form = document.getElementById(formn);
	var prevq = 0; var nextq = 0; var found=false;
	var prevl = 0; var nextl = 0;
	for (var e = 0; e < form.elements.length; e++) {
		var el = form.elements[e];
		if (typeof el.type == "undefined") {
			continue;
		}
		if (el.type == 'checkbox' && el.name=='nchecked[]') {
			if (found) {
				nextq = el.value;
				nextl = el.id;
				break;
			} else if (el.id==loc) {
				found = true;
			} else {
				prevq = el.value;
				prevl = el.id;
			}
		}
	}
	return ([[prevl,prevq],[nextl,nextq]]);
}
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
			Transfer question ownership to:

			<?php writeHtmlSelect("newowner",$page_transferUserList['val'],$page_transferUserList['label']); ?>

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
		function chglibtoggle(rad) {
			var val = rad.value;
			var help = document.getElementById("chglibhelp");
			if (val==0) {
				help.innerHTML = "Select libraries to add these questions to. ";
				if (chgliblaststate==2) {
					initlibtree(false);
				}
			} else if (val==1 || val==3) {
				help.innerHTML = "Select libraries to add these questions to.  Questions will only be removed from existing libraries if you have the rights to make those changes.";
				if (chgliblaststate==2) {
					initlibtree(false);
				}
			} else if (val==2) {
				help.innerHTML = "Unselect the libraries you want to remove questions from.  The questions will not be deleted; they will be moved to Unassigned if no other library assignments exist.  Questions will only be removed from existing libraries if you have the rights to make those changes.";
				if (chgliblaststate==0 || chgliblaststate==1 || chgliblaststate==3) {
					initlibtree(true);
				}
			}
			chgliblaststate = val;
		}
		</script>
		<form method=post action="manageqset.php?cid=<?php echo $cid ?>">
			<input type=hidden name=chglib value="true">
			<input type=hidden name=qtochg value="<?php echo Sanitize::encodeStringForDisplay($clist); ?>">
			What do you want to do with these questions?<br/>
			<input type=radio name="action" value="0" onclick="chglibtoggle(this)" checked="checked"/> Add to libraries, keeping any existing library assignments<br/>
			<input type=radio name="action" value="1" onclick="chglibtoggle(this)"/> Add to libraries, removing existing library assignments<br/>
			<?php
			if ($sessiondata['searchall'.$cid]==0 && $sessiondata['lastsearchlibs'.$cid]!='0') {
				echo '<input type=radio name="action" value="3" onclick="chglibtoggle(this)"/> Add to libraries, removing library assignment in currently listed libraries<br/>';
			}
			?>
			<input type=radio name="action" value="2" onclick="chglibtoggle(this)"/> Remove library assignments
			<p id="chglibhelp" style="font-weight: bold;">
			Select libraries to add these questions to.
			</p>

			<?php $libtreeshowchecks = false; include("libtree2.php"); ?>


			<p>
				<input type=submit value="Make Changes">
				<input type=button value="Nevermind" class="secondarybtn" onclick="window.location='manageqset.php?cid=<?php echo $cid ?>'">
			</p>
		</form>
<?php
	} else if (isset($_POST['template'])) {
?>

		<form method=post action="manageqset.php?cid=<?php echo $cid ?>">
			<input type=hidden name=template value="true">

			<p>
				This page will create new copies of these questions.  It is recommended that you place these new copies in a
				different library that the questions are currently are in, so you can distinguish the new versions from the originals.
			</p>
			<p>Select the library into which to put the new copies:</p>

			<input type=hidden name=qtochg value="<?php echo Sanitize::encodeStringForDisplay($clist); ?>">

			<?php include("libtree.php"); ?>

			<p>
				<input type=submit value="Template Questions">
				<input type=button value="Nevermind" class="secondarybtn" onclick="window.location='manageqset.php?cid=<?php echo $cid ?>'">
			</p>
		</form>
<?php
	} else if (isset($_POST['license'])) {
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
			Transfer question ownership to:

			<?php writeHtmlSelect("newowner",$page_transferUserList['val'],$page_transferUserList['label']); ?>

			<p>
				<input type=submit value="Transfer">
				<input type=button value="Nevermind" class="secondarybtn" onclick="window.location='manageqset.php?cid=<?php echo $cid ?>'">
			</p>
		</form>

<?php
	} else { //DEFAULT DISPLAY

		echo $page_adminMsg;

		echo "<form method=post action=\"manageqset.php?cid=$cid\">\n";

		echo "In Libraries: <span id=\"libnames\">" . Sanitize::encodeStringForDisplay($lnames) . "</span><input type=hidden name=\"libs\" id=\"libs\"  value=\"" . Sanitize::encodeStringForDisplay($searchlibs) . "\">\n";
		//echo " <input type=button value=\"Select Libraries\" onClick=\"libselect()\"> <br>";
		echo '<input type="button" value="Select Libraries" onClick="GB_show(\'Library Select\',\'libtree2.php?cid='.$cid.'&libtree=popup&libs=\'+curlibs,500,500)" /> <br>';

		echo "Search: <input type=text size=15 name=search value=\"$search\"> <input type=checkbox name=\"searchall\" value=\"1\" ";
		if ($searchall==1) {echo "checked=1";}
		echo "/>Search all libs <input type=checkbox name=\"searchmine\" value=\"1\" ";
		if ($searchmine==1) {echo "checked=1";}
		echo "/>Mine only ";
		if ($isadmin) {
			echo "<input type=checkbox name=\"hidepriv\" value=\"1\" ";
			if ($hidepriv==1) {echo "checked=1";}
			echo "/>Hide Private ";
			echo "<input type=checkbox name=\"skipfederated\" value=\"1\" ";
			if ($skipfederated==1) {echo "checked=1";}
			echo "/>Hide Federated ";
		}

		echo '<input type=submit value="Search" title="List or search selected libraries">';
		echo "<input type=button value=\"Add New Question\" onclick=\"window.location='moddataset.php?cid=$cid'\">\n";
		echo "</form>";

		echo "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablesorter.js?v=082913\"></script>\n";
		echo "<form id=\"selform\" method=post action=\"manageqset.php?cid=$cid\">\n";
		//echo "Check/Uncheck All: <input type=\"checkbox\" name=\"ca2\" value=\"1\" onClick=\"chkAll(this.form, 'nchecked[]', this.checked)\">\n";
		echo 'Check: <a href="#" onclick="return chkAllNone(\'selform\',\'nchecked[]\',true)">All</a> <a href="#" onclick="return chkAllNone(\'selform\',\'nchecked[]\',false)">None</a> ';

		echo "With Selected: <input type=submit name=\"transfer\" value=\"Transfer\" title=\"Transfer question ownership\">\n";
		echo "<input type=submit name=\"remove\" value=\"Delete\">\n";
		echo "<input type=submit name=\"chglib\" value=\"Library Assignment\" title=\"Change library assignments\">\n";
		echo "<input type=submit name=\"chgrights\" value=\"Change Rights\" title=\"Change use rights\">\n";
		//echo "<input type=submit name=\"template\" value=\"Template\" title=\"Make a copy of all selected questions\">\n";
		echo "<input type=submit name=\"license\" value=\"License\" title=\"Change license or attribution\">\n";
		if (!$isadmin && !$isgrpadmin) {
			echo "<br/>(Delete and Transfer only applies to your questions)\n";
		} else if ($isgrpadmin) {
			echo "<br/>(Delete and Transfer only apply to group's questions)\n";
		}
		echo "<table id=myTable class=gb><thead>\n";
		echo "<tr><th>&nbsp;</th><th>Description</th><th>&nbsp;</th><th>ID</th><th>Preview</th><th>Action</th><th>Type</th><th>Times Used</th><th>Last Mod</th>";
		if ($isadmin || $isgrpadmin) { echo "<th>Owner</th>";} else {echo "<th>Mine</th>";}
		if ($searchall==1) {
			echo "<th>Library</th>";
		} else if ($searchall==0) {
			echo '<th><span onmouseover="tipshow(this,\'Flag a question if it is in the wrong library\')" onmouseout="tipout()">Wrong Lib</span></th>';
		}
		echo "</tr>\n";
		echo "</thead><tbody>\n";
		$alt = 0;
		$ln = 1;
		for ($j=0; $j<count($page_libstouse); $j++) {
			if ($searchall==0) {
				if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
				echo '<td></td>';
				echo '<td colspan="8">';
				echo '<b>'.Sanitize::encodeStringForDisplay($lnamesarr[$page_libstouse[$j]]).'</b>';
				echo '</td></tr>';
			}
			for ($i=0;$i<count($page_libqids[$page_libstouse[$j]]); $i++) {
				$qid = Sanitize::encodeStringForDisplay($page_libqids[$page_libstouse[$j]][$i]);
				if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
				echo '<td>'.$page_questionTable[$qid]['checkbox'].'</td>';
				echo '<td>'.$page_questionTable[$qid]['desc'].'</td>';
				echo '<td class="nowrap"><div';
				if ($page_questionTable[$qid]['cap']) {echo ' class="ccvid"';}
				echo '>'.$page_questionTable[$qid]['extref'].'</div></td>';
				echo '<td>'.$qid.'</td>';
				echo '<td>'.$page_questionTable[$qid]['preview'].'</td>';
				echo '<td>'.$page_questionTable[$qid]['action'].'</td>';
				echo '<td>'.Sanitize::encodeStringForDisplay($page_questionTable[$qid]['type']).'</td>';
				echo '<td class="c">'.Sanitize::encodeStringForDisplay($page_questionTable[$qid]['times']).'</td>';
				echo '<td>'.$page_questionTable[$qid]['lastmod'].'</td>';
				echo '<td class="c">'.$page_questionTable[$qid]['mine'].'</td>';
				if ($searchall==1) {
					echo '<td>'.$page_questionTable[$qid]['lib'].'</td>';
				} else if ($searchall==0) {
					if ($page_questionTable[$qid]['junkflag']==1) {
						echo "<td class=c><img class=\"pointer wlf\" id=\"tag{$page_questionTable[$qid]['libitemid']}\" src=\"$imasroot/img/flagfilled.gif\" onClick=\"toggleJunkFlag({$page_questionTable[$qid]['libitemid']});return false;\" alt=\"Flagged\"/></td>";
					} else {
						echo "<td class=c><img class=\"pointer wlf\" id=\"tag{$page_questionTable[$qid]['libitemid']}\" src=\"$imasroot/img/flagempty.gif\" onClick=\"toggleJunkFlag({$page_questionTable[$qid]['libitemid']});return false;\" alt=\"Not flagged\"/></td>";
					}
				}
				$ln++;
			}
		}
		if ($searchlimited) {
			echo '<tr><td></td><td><i>'._('Search cut off at 300 results').'</i></td></tr>';
		}
		echo "</tbody></table>\n";
		echo "<script type=\"text/javascript\">\n";
		echo "initSortTable('myTable',Array(false,'S',false,'N',false,false,'S','N','D','S',false),true);\n";
		echo "</script>\n";
		echo "</form>\n";
		echo "<p></p>\n";
	}

}
require("../footer.php");


function delqimgs($qsid) {
  global $DBH;
	//DB $query = "SELECT id,filename,var FROM imas_qimages WHERE qsetid='$qsid'";
	//DB $result = mysql_query($query) or die("Query failed :$query " . mysql_error());
	//DB while ($row = mysql_fetch_row($result)) {
	$del_stm = $DBH->prepare("DELETE FROM imas_qimages WHERE id=:id");
	$stm2 = $DBH->prepare("SELECT id FROM imas_qimages WHERE filename=:filename");

	$stm = $DBH->prepare("SELECT id,filename,var FROM imas_qimages WHERE qsetid=:qsetid");
	$stm->execute(array(':qsetid'=>$qsid));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		//DB $query = "SELECT id FROM imas_qimages WHERE filename='{$row[1]}'";
		//DB $r2 = mysql_query($query) or die("Query failed :$query " . mysql_error());
		//DB if (mysql_num_rows($r2)==1) {
		if (substr($row[1],0,4)!='http') {
			$stm2->execute(array(':filename'=>$row[1]));
			if ($stm2->rowCount()==1) {
				unlink(rtrim(dirname(__FILE__), '/\\') .'/../assessment/qimages/'.$row[1]);
			}
		}
		//DB $query = "DELETE FROM imas_qimages WHERE id='{$row[0]}'";
		//DB mysql_query($query) or die("Query failed :$query " . mysql_error());
		$del_stm->execute(array(':id'=>$row[0]));
	}
}
?>

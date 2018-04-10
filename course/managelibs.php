<?php
//IMathAS:  Main admin page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../init.php");
require("../includes/htmlutil.php");

 //set some page specific variables and counters
$overwriteBody = 0;
$body = "";
$pagetitle = "Library Management";
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

	$now = time();

	$curBreadcrumb = "<a href=\"../index.php\">Home</a>";
	if ($isadmin || $isgrpadmin) {
		$curBreadcrumb .= " &gt; <a href=\"../admin/admin2.php\">Admin</a> ";
	}
	if ($cid!=0) {
		$curBreadcrumb .= " &gt; <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
	}

	if (isset($_POST['remove'])) {
		if (isset($_GET['confirmed'])) {
			if ($_POST['remove']!='') {
				$remlist = implode(',' , array_map('intval', explode(',',$_POST['remove'])));
				if (!$isadmin) {
					if ($isgrpadmin) {
						$stm = $DBH->prepare("SELECT id FROM imas_libraries WHERE id IN ($remlist) AND groupid=:groupid");
						$stm->execute(array(':groupid'=>$groupid));
					} else {
						$stm = $DBH->prepare("SELECT id FROM imas_libraries WHERE id IN ($remlist) AND ownerid=:ownerid");
						$stm->execute(array(':ownerid'=>$userid));
					}
					$oklib = array();
					while ($row = $stm->fetch(PDO::FETCH_NUM)) {
						$oklib[] = $row[0];
					}
					$remlist = implode(',', $oklib);
				}
				$DBH->beginTransaction();
				// $remlist now only contains libraries that are OK to delete
				//now actually delete the libraries
				$now = time();
				$stm = $DBH->prepare("UPDATE imas_libraries SET deleted=1,lastmoddate=:now WHERE id IN ($remlist)");
				$stm->execute(array(':now'=>$now));

				// note the question IDs in the deleted libraries
				$qidstocheck = array();
				$stm = $DBH->query("SELECT DISTINCT qsetid FROM imas_library_items WHERE libid IN ($remlist) AND deleted=0");
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					$qidstocheck[] = $row[0];
				}

				// delete the library items
				$stm = $DBH->prepare("UPDATE imas_library_items SET deleted=1,lastmoddate=:now WHERE libid IN ($remlist)");
				$stm->execute(array(':now'=>$now));

				if (count($qidstocheck)>0) {
					$qids = array_map('Sanitize::onlyInt', $qidstocheck);//INTs from DB
					$qids_query_placeholders = Sanitize::generateQueryPlaceholders($qids);
					//DB $query = "SELECT DISTINCT qsetid FROM `imas_library_items` WHERE qsetid IN ($qids)";
					//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
					$stm = $DBH->prepare("SELECT DISTINCT qsetid FROM `imas_library_items` WHERE qsetid IN ($qids_query_placeholders) AND deleted=0");
					$stm->execute($qids);
					$okqids = array();
					while ($row = $stm->fetch(PDO::FETCH_NUM)) {
						$okqids[] = $row[0];
					}
					//get a list of questions with no more library items
					$qidstofix = array_values(array_diff($qidstocheck,$okqids));
					$qlist = array_map('Sanitize::onlyInt', $qidstofix);//INTs from DB
					$qlist_query_placeholders = Sanitize::generateQueryPlaceholders($qlist);
					if ($_POST['delq']=='yes' && count($qidstofix)>0) {
						//$query = "DELETE FROM imas_questionset WHERE id IN ($qlist)";
						//DB $query = "UPDATE imas_questionset SET deleted=1 WHERE id IN ($qlist)";
						//DB mysql_query($query) or die("Query failed : " . mysql_error());
						$stm = $DBH->prepare("UPDATE imas_questionset SET deleted=1,lastmoddate=? WHERE id IN ($qlist_query_placeholders)");
						$stm->execute(array_merge(array($now),$qlist));
							//echo "del: $qlist";
							/*foreach ($qidstofix as $qid) {
								delqimgs($qid);
							}*/
					} else if (count($qidstofix)>0) {
						//see which questions with no active lib items already have an unassigned lib item we can undeleted
						$stm = $DBH->prepare("SELECT DISTINCT qsetid FROM `imas_library_items` WHERE qsetid IN ($qlist_query_placeholders) AND libid=0 AND deleted=1");
						$stm->execute($qlist);
						$toundelqids = array();
						while ($row = $stm->fetch(PDO::FETCH_NUM)) {
							$toundelqids[] = $row[0];
						}
						//undelete those lib items
						if (count($toundelqids)>0) {
							$toundel_query_placeholders = Sanitize::generateQueryPlaceholders($toundelqids);
							//$undellist = implode(',', $toundelqids);
							$stm = $DBH->prepare("UPDATE `imas_library_items` SET deleted=0,lastmoddate=? WHERE qsetid IN ($toundel_query_placeholders) AND libid=0");
							$stm->execute(array_merge(array($now),$toundelqids));
						}

						//for questions with no active lib items or unassigned to undelete, add an unassigned lib item
						$qidstoadd = array_values(array_diff($qidstofix, $toundelqids));
						$stm = $DBH->prepare("INSERT INTO imas_library_items ( qsetid,libid,lastmoddate) VALUES (:qsetid, :libid, :lastmoddate)");
						foreach($qidstoadd as $qid) {
							$stm->execute(array(':qsetid'=>$qid, ':libid'=>0, ':lastmoddate'=>$now));
						}
					}
				}
				$DBH->commit();
			}
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/managelibs.php?cid=$cid" . "&r=" . Sanitize::randomQueryStringParam());

			exit;
		} else {
			$pagetitle = "Confirm Removal";
			$curBreadcrumb .= " &gt; <a href=\"managelibs.php?cid=$cid\">Manage Libraries</a> &gt; Confirm Removal ";
			if (!isset($_POST['nchecked'])) {
				$overwriteBody = 1;
				$body = "No libraries selected.  <a href=\"managelibs.php?cid=$cid\">Go back</a>\n";
			} else {
				$oktorem = array();
				$stm = $DBH->prepare("SELECT count(id) FROM imas_libraries WHERE parent=:parent AND deleted=0");
				for ($i=0; $i<count($_POST['nchecked']); $i++) {
					$stm->execute(array(':parent'=>$_POST['nchecked'][$i]));
					$libcnt= $stm->fetchColumn(0);
					if ($libcnt == 0) {
						$oktorem[] = $_POST['nchecked'][$i];
					}
				}
				$rlist = implode(",", array_map('intval', $oktorem));
				$hasChildWarning = (count($_POST['nchecked'])>count($oktorem)) ? "<p>Warning:  Some libraries selected have children, and cannot be deleted.</p>\n": "";
			}
		}
	} else if (isset($_POST['chgrights'])) {
		if (isset($_POST['newrights'])) {
			if ($_POST['newrights']!='') {
				//DB $llist = "'".implode("','",explode(',',$_POST['chgrights']))."'";
				$llist = implode(',', array_map('intval', explode(',',$_POST['chgrights'])));
				//DB $query = "UPDATE imas_libraries SET userights='{$_POST['newrights']}',lastmoddate=$now WHERE id IN ($llist)";
        if ($isadmin && $_POST['newfed']>-1) {
          $query = "UPDATE imas_libraries SET userights=:userights,federationlevel=:fedlevel,lastmoddate=:lastmoddate WHERE id IN ($llist)";
				  $qarr = array(':userights'=>$_POST['newrights'], ':fedlevel'=>$_POST['newfed'], ':lastmoddate'=>$now);
        } else {
				  $query = "UPDATE imas_libraries SET userights=:userights,lastmoddate=:lastmoddate WHERE id IN ($llist)";
				  $qarr = array(':userights'=>$_POST['newrights'], ':lastmoddate'=>$now);
        }
				if (!$isadmin) {
					//DB $query .= " AND groupid='$groupid'";
					$query .= " AND groupid=:groupid";
					$qarr[':groupid']=$groupid;
				}
				if (!$isadmin && !$isgrpadmin) {
					//DB $query .= " AND ownerid='$userid'";
					$query .= " AND ownerid=:ownerid";
					$qarr[':ownerid'] = $userid;
				}
				$stm = $DBH->prepare($query);
				$stm->execute($qarr);
				//DB mysql_query($query) or die("Query failed : $query " . mysql_error());

			}
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/managelibs.php?cid=$cid" . "&r=" . Sanitize::randomQueryStringParam());

			exit;

		} else {
			$pagetitle = "Change Library Rights";
			$curBreadcrumb .= " &gt; <a href=\"managelibs.php?cid=$cid\">Manage Libraries</a> &gt; Change Library Rights ";
			if (!isset($_POST['nchecked'])) {
				$overwriteBody = 1;
				$body = "No libraries selected.  <a href=\"managelibs.php?cid=$cid\">Go back</a>\n";
			} else {
				$tlist = Sanitize::encodeStringForDisplay(implode(",",$_POST['nchecked']));
				$page_libRights = array();
				$page_libRights['val'][0] = 0;
				$page_libRights['val'][1] = 1;
				$page_libRights['val'][2] = 2;

				$page_libRights['label'][0] = "Private";
				$page_libRights['label'][1] = "Closed to group, private to others";
				$page_libRights['label'][2] = "Open to group, private to others";

				if ($isadmin || ($myspecialrights&8)==8 || $allownongrouplibs) {
					$page_libRights['label'][3] = "Closed to all";
					$page_libRights['label'][4] = "Open to group, closed to others";
					$page_libRights['label'][5] = "Open to all";
					$page_libRights['val'][3] = 4;
					$page_libRights['val'][4] = 5;
					$page_libRights['val'][5] = 8;
				}
			}

		}



	} else if (isset($_POST['chgsort'])) {
		if (isset($_POST['sortorder'])) {
			if ($_POST['sortorder']!='') {
				//DB $llist = "'".implode("','",explode(',',$_POST['chgrights']))."'";
				$llist = implode(',', array_map('intval', explode(',',$_POST['chgsort'])));
				//DB $query = "UPDATE imas_libraries SET userights='{$_POST['newrights']}',lastmoddate=$now WHERE id IN ($llist)";
				$query = "UPDATE imas_libraries SET sortorder=:sortorder,lastmoddate=:lastmoddate WHERE id IN ($llist)";
				$qarr = array(':sortorder'=>$_POST['sortorder'], ':lastmoddate'=>$now);
				if (!$isadmin) {
					//DB $query .= " AND groupid='$groupid'";
					$query .= " AND groupid=:groupid";
					$qarr[':groupid']=$groupid;
				}
				if (!$isadmin && !$isgrpadmin) {
					//DB $query .= " AND ownerid='$userid'";
					$query .= " AND ownerid=:ownerid";
					$qarr[':ownerid'] = $userid;
				}
				$stm = $DBH->prepare($query);
				$stm->execute($qarr);
				//DB mysql_query($query) or die("Query failed : $query " . mysql_error());

			}
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/managelibs.php?cid=$cid" . "&r=" . Sanitize::randomQueryStringParam());

			exit;

		} else {
			$pagetitle = "Change Library Sort Order";
			$curBreadcrumb .= " &gt; <a href=\"managelibs.php?cid=$cid\">Manage Libraries</a> &gt; Change Library Sort Order ";
			if (!isset($_POST['nchecked'])) {
				$overwriteBody = 1;
				$body = "No libraries selected.  <a href=\"managelibs.php?cid=$cid\">Go back</a>\n";
			} else {
				$tlist = Sanitize::encodeStringForDisplay(implode(",",$_POST['nchecked']));
			}
		}

	} else if (isset($_POST['transfer'])) {
		if (isset($_POST['newowner'])) {
			if ($_POST['transfer']!='') {
				//DB $translist = "'".implode("','",explode(',',$_POST['transfer']))."'";
				$translist = implode(',', array_map('intval', explode(',',$_POST['transfer'])));

				//added for mysql 3.23 compatibility
				//DB $query = "SELECT groupid FROM imas_users WHERE id='{$_POST['newowner']}'";
				//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
				//DB $newgpid = mysql_result($result,0,0);
				$stm = $DBH->prepare("SELECT groupid FROM imas_users WHERE id=:id");
				$stm->execute(array(':id'=>$_POST['newowner']));
				$newgpid = $stm->fetchColumn(0);
				//DB $query = "UPDATE imas_libraries SET ownerid='{$_POST['newowner']}',groupid='$newgpid' WHERE imas_libraries.id IN ($translist)";
				$query = "UPDATE imas_libraries SET ownerid=:ownerid,groupid=:groupid WHERE imas_libraries.id IN ($translist)";
				$qarr = array(':ownerid'=>$_POST['newowner'], ':groupid'=>$newgpid);

				if (!$isadmin) {
				  //DB $query .= " AND groupid='$groupid'";
				  $query .= " AND groupid=:groupid";
				  $qarr[':groupid']=$groupid;
				}
				if (!$isadmin && !$isgrpadmin) {
				  //DB $query .= " AND ownerid='$userid'";
				  $query .= " AND ownerid=:ownerid";
				  $qarr[':ownerid'] = $userid;
				}
				$stm = $DBH->prepare($query);
				$stm->execute($qarr);
				//DB mysql_query($query) or die("Query failed : $query " . mysql_error());

			}
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/managelibs.php?cid=$cid" . "&r=" . Sanitize::randomQueryStringParam());

			exit;
		} else {
			$pagetitle = "Confirm Transfer";
			$curBreadcrumb .= " &gt; <a href=\"managelibs.php?cid=$cid\">Manage Libraries</a> &gt; Confirm Transfer ";
			if (!isset($_POST['nchecked'])) {
				$overwriteBody = 1;
				$body = "No libraries selected.  <a href=\"managelibs.php?cid=$cid\">Go back</a>\n";
			} else {
				$tlist = implode(",", array_map('intval', $_POST['nchecked']));
				//DB $query = "SELECT id,FirstName,LastName FROM imas_users WHERE rights>19 ORDER BY LastName,FirstName";
				//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
				$stm = $DBH->query("SELECT id,FirstName,LastName FROM imas_users WHERE rights>19 ORDER BY LastName,FirstName");
				$i=0;
				//DB while ($row = mysql_fetch_row($result)) {
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					$page_newOwnerList['val'][$i] = $row[0];
					$page_newOwnerList['label'][$i] = $row[2] . ", " . $row[1];
					$i++;
				}
			}
		}
	} else if (isset($_POST['setparent'])) {
		if (isset($_POST['libs'])) {
			if ($_POST['libs']!='') {
				$toset = array();
				$_POST['setparent'] = explode(',',$_POST['setparent']);
				foreach ($_POST['setparent'] as $alib) {
					if ($alib != $_POST['libs']) {
						$toset[] = $alib;
					}
				}
				if (count($toset)>0) {
					//DB $parlist = "'".implode("','",$toset)."'";
					$parlist = implode(',', array_map('intval',$toset));
					//DB $query = "UPDATE imas_libraries SET parent='{$_POST['libs']}',lastmoddate=$now WHERE id IN ($parlist)";
					$query = "UPDATE imas_libraries SET parent=:parent,lastmoddate=:lastmoddate WHERE id IN ($parlist)";
					$qarr = array(':parent'=>$_POST['libs'], ':lastmoddate'=>$now);
					  if ($isgrpadmin) {
					    //DB $query .= " AND groupid='$groupid'";
					    $query .= " AND groupid=:groupid";
					    $qarr[':groupid']=$groupid;
					  } else if (!$isadmin && !$isgrpadmin) {
					    //DB $query .= " AND ownerid='$userid'";
					    $query .= " AND ownerid=:ownerid";
					    $qarr[':ownerid'] = $userid;
					  }
					  $stm = $DBH->prepare($query);
					  $stm->execute($qarr);
					  //DB mysql_query($query) or die("Query failed : $query " . mysql_error());
				}
			}
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/managelibs.php?cid=$cid" . "&r=" . Sanitize::randomQueryStringParam());

			exit;
		} else {
			$pagetitle = "Set Parent";
			$curBreadcrumb .= " &gt; <a href=\"managelibs.php?cid=$cid\">Manage Libraries</a> &gt; Set Parent ";
			$parent1 = "";

			if (!isset($_POST['nchecked'])) {
				$overwriteBody = 1;
				$body = "No libraries selected.  <a href=\"managelibs.php?cid=$cid\">Go back</a>\n";
			} else {
				$tlist = Sanitize::encodeStringForDisplay(implode(",",$_POST['nchecked']));
			}
		}
	} else if (isset($_GET['transfer'])) {
		if (isset($_POST['newowner'])) {

			//added for mysql 3.23 compatibility
			//DB $query = "SELECT groupid FROM imas_users WHERE id='{$_POST['newowner']}'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $newgpid = mysql_result($result,0,0);
			$stm = $DBH->prepare("SELECT groupid FROM imas_users WHERE id=:id");
			$stm->execute(array(':id'=>$_POST['newowner']));
			$newgpid = $stm->fetchColumn(0);

			//$query = "UPDATE imas_libraries,imas_users SET imas_libraries.ownerid='{$_POST['newowner']}'";
			//$query .= ",imas_libraries.groupid=imas_users.groupid WHERE imas_libraries.ownerid=imas_users.id AND ";
			//$query .= "imas_libraries.id='{$_GET['transfer']}'";
			//DB $query = "UPDATE imas_libraries SET ownerid='{$_POST['newowner']}',groupid='$newgpid' WHERE imas_libraries.id='{$_GET['transfer']}'";
			$query = "UPDATE imas_libraries SET ownerid=:ownerid,groupid=:groupid WHERE imas_libraries.id=:id";
			$qarr = array(':ownerid'=>$_POST['newowner'], ':groupid'=>$newgpid, ':id'=>$_GET['transfer']);
		      if ($isgrpadmin) {
			//DB $query .= " AND groupid='$groupid'";
			$query .= " AND groupid=:groupid";
			$qarr[':groupid']=$groupid;
		      } else if (!$isadmin && !$isgrpadmin) {
			//DB $query .= " AND ownerid='$userid'";
			$query .= " AND ownerid=:ownerid";
			$qarr[':ownerid'] = $userid;
		      }
		      $stm = $DBH->prepare($query);
		      $stm->execute($qarr);
		      //DB mysql_query($query) or die("Query failed : $query " . mysql_error());
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/managelibs.php?cid=$cid" . "&r=" . Sanitize::randomQueryStringParam());
			exit;
		} else {
			$pagetitle = "Transfer Library";
			$curBreadcrumb .= " &gt; <a href=\"managelibs.php?cid=$cid\">Manage Libraries</a> &gt; $pagetitle ";
			//DB $query = "SELECT id,FirstName,LastName FROM imas_users WHERE rights>19 ORDER BY LastName,FirstName";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			$stm = $DBH->query("SELECT id,FirstName,LastName FROM imas_users WHERE rights>19 ORDER BY LastName,FirstName");
			$i=0;
			$page_newOwnerList = array();
			//DB while ($row = mysql_fetch_row($result)) {
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$page_newOwnerList['val'][$i] = $row[0];
				$page_newOwnerList['label'][$i] = $row[2] . ", " . $row[1];
				$i++;
			}

		}

	} else if (isset($_GET['modify'])) {
		if (isset($_POST['name']) && trim($_POST['name'])!='') {
			if ($_GET['modify']=="new") {
				$_POST['name'] = str_replace(array(',','\\"','\\\'','~'),"",$_POST['name']);
				//DB $query = "SELECT * FROM imas_libraries WHERE name='{$_POST['name']}' AND parent='{$_POST['libs']}'";
				//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
				//DB if (mysql_num_rows($result)>0) {
				$stm = $DBH->prepare("SELECT * FROM imas_libraries WHERE name=:name AND parent=:parent");
				$stm->execute(array(':name'=>$_POST['name'], ':parent'=>$_POST['libs']));
				if ($stm->rowCount()>0) {
					$overwriteBody =1;
					$body = "Library already exists by that name with this parent.\n";
					$body .= "<p><a href=\"managelibs.php?cid=$cid&modify=new\">Try Again</a></p>\n";
				} else {
					$mt = microtime();
					$uqid = substr($mt,11).substr($mt,2,6);
					//DB $query = "INSERT INTO imas_libraries (uniqueid,adddate,lastmoddate,name,ownerid,userights,sortorder,parent,groupid) VALUES ";
					//DB $query .= "($uqid,$now,$now,'{$_POST['name']}','$userid','{$_POST['rights']}','{$_POST['sortorder']}','{$_POST['libs']}','$groupid')";
					//DB mysql_query($query) or die("Query failed : " . mysql_error());
					$query = "INSERT INTO imas_libraries (uniqueid,adddate,lastmoddate,name,ownerid,userights,sortorder,parent,groupid,federationlevel) VALUES ";
					$query .= "(:uniqueid, :adddate, :lastmoddate, :name, :ownerid, :userights, :sortorder, :parent, :groupid, :fedlevel)";
					$stm = $DBH->prepare($query);
					$stm->execute(array(':uniqueid'=>$uqid, ':adddate'=>$now, ':lastmoddate'=>$now, ':name'=>$_POST['name'], ':ownerid'=>$userid,
						':userights'=>$_POST['rights'], ':sortorder'=>$_POST['sortorder'], ':parent'=>$_POST['libs'], ':groupid'=>$groupid,
            ':fedlevel'=>($isadmin?$_POST['fedlevel']:0)));
					header('Location: ' . $GLOBALS['basesiteurl'] . "/course/managelibs.php?cid=$cid" . "&r=" . Sanitize::randomQueryStringParam());
					exit;
				}
			} else {

				//DB $query = "UPDATE imas_libraries SET name='{$_POST['name']}',userights='{$_POST['rights']}',sortorder='{$_POST['sortorder']}',lastmoddate=$now";
				//DB if ($_GET['modify'] != $_POST['libs']) {
					//DB $query .= ",parent='{$_POST['libs']}'";
				//DB }
				//DB $query .= " WHERE id='{$_GET['modify']}'";
				$query = "UPDATE imas_libraries SET name=:name,userights=:userights,sortorder=:sortorder,lastmoddate=:lastmoddate";
				$qarr = array(':name'=>$_POST['name'], ':userights'=>$_POST['rights'], ':sortorder'=>$_POST['sortorder'], ':lastmoddate'=>$now, ':id'=>$_GET['modify']);
				if ($_GET['modify'] != $_POST['libs']) {
					$query .= ",parent=:parent";
					$qarr[':parent']=$_POST['libs'];
				}
        if ($isadmin) {
          $query .= ",federationlevel=:fedlevel";
          $qarr[':fedlevel'] = $_POST['fedlevel'];
        }
				$query .= " WHERE id=:id";

				if ($isgrpadmin) {
				  //DB $query .= " AND groupid='$groupid'";
				  $query .= " AND groupid=:groupid";
				  $qarr[':groupid']=$groupid;
				} else if (!$isadmin) {
				  //DB $query .= " AND ownerid='$userid'";
				  $query .= " AND ownerid=:ownerid";
				  $qarr[':ownerid'] = $userid;
				}
				$stm = $DBH->prepare($query);
				$stm->execute($qarr);
				//DB mysql_query($query) or die("Query failed : $query " . mysql_error());

				header('Location: ' . $GLOBALS['basesiteurl'] . "/course/managelibs.php?cid=$cid" . "&r=" . Sanitize::randomQueryStringParam());
				exit;
			}
		} else {
			$pagetitle = "Library Settings";
			$curBreadcrumb .= " &gt; <a href=\"managelibs.php?cid=$cid\">Manage Libraries</a> &gt; $pagetitle ";

			if ($_GET['modify']!="new") {
				$pagetitle = "Modify Library\n";
				if ($isgrpadmin) {
					$stm = $DBH->prepare("SELECT il.name,il.userights,il.parent,il.sortorder,iu.firstName,iu.lastName FROM imas_libraries AS il JOIN imas_users AS iu ON il.ownerid=iu.id WHERE il.id=:id AND il.groupid=:groupid");
					$stm->execute(array(':id'=>$_GET['modify'], ':groupid'=>$groupid));
				} else if ($isadmin) {
					//DB $query = "SELECT name,userights,parent,sortorder FROM imas_libraries WHERE id='{$_GET['modify']}'";
					$stm = $DBH->prepare("SELECT il.name,il.userights,il.parent,il.sortorder,iu.firstName,iu.lastName,il.federationlevel FROM imas_libraries AS il JOIN imas_users AS iu ON il.ownerid=iu.id WHERE il.id=:id");
					$stm->execute(array(':id'=>$_GET['modify']));
				} else {
					//DB $query = "SELECT name,userights,parent,sortorder FROM imas_libraries WHERE id='{$_GET['modify']}' AND ownerid='$userid'";
					$stm = $DBH->prepare("SELECT name,userights,parent,sortorder FROM imas_libraries WHERE id=:id AND ownerid=:ownerid");
					$stm->execute(array(':id'=>$_GET['modify'], ':ownerid'=>$userid));
				}
				//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
				//DB if ($row = mysql_fetch_row($result)) {
				if ($row = $stm->fetch(PDO::FETCH_NUM)) {
					$name = $row[0];
					$rights = $row[1];
					$parent = $row[2];
					$sortorder = $row[3];
					if ($isgrpadmin || $isadmin) {
						$ownername = $row[5].', '.$row[4];
				  }
          if ($isadmin) {
            $fedlevel = $row[6];
          }
				}
			} else {
				$pagetitle = "Add Library\n";
				if (isset($_GET['parent'])) {
					$parent = Sanitize::encodeStringForDisplay($_GET['parent']);
				}
        $fedlevel = 0;
			}
			if (!isset($name)) {
				$name = '';
				$pagetitle = "Add Library\n";
			}
			if (!isset($rights)) {
				if ($isadmin || $allownongrouplibs) {
					$rights = 8;
				} else {
					$rights = 2;
				}
			}
			if (!isset($parent)) {$parent = 0;}
			if (!isset($sortorder)) {$sortorder = 0;}
			$parent1 = $parent;

			if ($parent==0) {
				$lnames = "Root";
			} else {
				//DB $query = "SELECT name FROM imas_libraries WHERE id='$parent'";
				//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
				//DB $lnames = mysql_result($result,0,0);
				$stm = $DBH->prepare("SELECT name FROM imas_libraries WHERE id=:id");
				$stm->execute(array(':id'=>$parent));
				$lnames = $stm->fetchColumn(0);
			}
		}

		$page_libRights = array();
		$page_libRights['val'][0] = 0;
		$page_libRights['val'][1] = 1;
		$page_libRights['val'][2] = 2;

		$page_libRights['label'][0] = "Private";
		$page_libRights['label'][1] = "Closed to group, private to others";
		$page_libRights['label'][2] = "Open to group, private to others";

		if ($isadmin || ($myspecialrights&8)==8 || $allownongrouplibs) {
			$page_libRights['label'][3] = "Closed to all";
			$page_libRights['label'][4] = "Open to group, closed to others";
			$page_libRights['label'][5] = "Open to all";
			$page_libRights['val'][3] = 4;
			$page_libRights['val'][4] = 5;
			$page_libRights['val'][5] = 8;
		}

	} else { //DEFAULT PROCESSING HERE
		$pagetitle = "Library Management";
		$helpicon = "&nbsp;&nbsp; <img src=\"$imasroot/img/help.gif\" alt=\"Help\" onClick=\"window.open('$imasroot/help.php?section=managelibraries','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))\"/>";
		$curBreadcrumb .= " &gt; Manage Libraries ";
		if ($isadmin) {
			$page_AdminModeMsg = "You are in Admin mode, which means actions will apply to all libraries, regardless of owner";
		} else if ($isgrpadmin) {
			$page_AdminModeMsg =  "You are in Group Admin mode, which means actions will apply to all libraries from your group, regardless of owner";
		} else {
			$page_AdminModeMsg = "";
		}

		//DB $query = "SELECT imas_libraries.id,imas_libraries.name,imas_libraries.ownerid,imas_libraries.userights,imas_libraries.sortorder,imas_libraries.parent,imas_libraries.groupid,count(imas_library_items.id) AS count ";
		//DB $query .= "FROM imas_libraries LEFT JOIN imas_library_items ON imas_library_items.libid=imas_libraries.id ";
		//DB $query .= "GROUP BY imas_libraries.id";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		$query = "SELECT imas_libraries.id,imas_libraries.name,imas_libraries.ownerid,imas_libraries.userights,imas_libraries.federationlevel,imas_libraries.sortorder,imas_libraries.parent,imas_libraries.groupid,count(imas_library_items.id) AS count ";
		$query .= "FROM imas_libraries LEFT JOIN imas_library_items ON imas_library_items.libid=imas_libraries.id and imas_library_items.deleted=0 ";
		$query .= "WHERE imas_libraries.deleted=0 GROUP BY imas_libraries.id ORDER BY imas_libraries.federationlevel DESC,imas_libraries.id";
		$stm = $DBH->query($query);
		$rights = array();
		$sortorder = array();

		//DB while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
			$id = $line['id'];
			$name = $line['name'];
			$parent = $line['parent'];
			$qcount[$id] = $line['count'];
			$ltlibs[$parent][] = $id;
			$parents[$id] = $parent;
			$names[$id] = $name;
			$rights[$id] = $line['userights'];
			$sortorder[$id] = $line['sortorder'];
			$ownerids[$id] = $line['ownerid'];
			$groupids[$id] = $line['groupid'];
			$federated[$id] = ($line['federationlevel']>0);
		}

		$page_appliesToMsg = (!$isadmin) ? "(Only applies to your libraries)" : "";
	}
}

$placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/libtree.js\"></script>\n";
$placeinhead .= "<style type=\"text/css\">\n<!--\n@import url(\"$imasroot/course/libtree.css\");\n-->\n</style>\n";
/******* begin html output ********/
require("../header.php");

if ($overwriteBody==1) {
	echo $body;
} else {
?>
	<script>
	var curlibs = '<?php echo Sanitize::encodeStringForJavascript($parent1); ?>';
	function libselect() {
		window.open('libtree2.php?cid=<?php echo $cid ?>&libtree=popup&select=parent&selectrights=1&type=radio&libs='+curlibs,'libtree','width=400,height='+(.7*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420));
	}
	function setlib(libs) {
		document.getElementById("libs").value = libs;
		curlibs = libs;
	}
	function setlibnames(libn) {
		document.getElementById("libnames").innerHTML = libn;
	}
	</script>


	<style type="text/css">
	ul.base ul {
		border-top: 1px solid #ddd;
	}
	ul.base li {
		border-bottom: 1px solid #ddd;
		padding-top: 5px;
	}
	span.fedico {
		color: #aaa;
	}
	</style>

	<div class=breadcrumb><?php echo $curBreadcrumb; ?></div>
	<div id="headermanagelibs" class="pagetitle"><h2><?php echo $pagetitle; echo $helpicon; ?></h2></div>

<?php
	if (isset($_POST['remove'])) {
?>
 	<?php echo $hasChildWarning; ?>
	Are you SURE you want to delete these libraries?
	<form method=post action="managelibs.php?cid=<?php echo $cid ?>&confirmed=true">
		<p>
			<input type=radio name="delq" value="no" CHECKED>
			Move questions in library to Unassigned<br>
			<input type=radio name="delq" value="yes" >
			Also delete questions in library
		</p>
		<input type=hidden name=remove value="<?php echo $rlist ?>">
		<p>
			<input type=submit value="Really Delete">
			<input type=button value="Nevermind" class="secondarybtn" onclick="window.location='managelibs.php?cid=<?php echo $cid ?>'">
		</p>
	</form>
<?php
	} else if (isset($_POST['transfer'])) {
?>
	<form method=post action="managelibs.php?cid=<?php echo $cid ?>">
		<input type=hidden name=transfer value="<?php echo Sanitize::encodeStringForDisplay($tlist); ?>">
		Transfer library ownership to:
		<?php writeHtmlSelect ("newowner",$page_newOwnerList['val'],$page_newOwnerList['label'],$selectedVal=null,$defaultLabel=null,$defaultVal=null,$actions=null) ?>

		<p>
			<input type=submit value="Transfer">
			<input type=button value="Nevermind" class="secondarybtn" onclick="window.location='managelibs.php?cid=<?php echo $cid ?>'">
		</p>
	</form>
<?php
	} else if (isset($_POST['chgrights'])) {
?>
	<form method=post action="managelibs.php?cid=<?php echo $cid ?>">
		<input type=hidden name=chgrights value="<?php echo Sanitize::encodeStringForDisplay($tlist); ?>">
		<span class=form>Library use rights: </span>
		<span class=formright>
			<?php writeHtmlSelect ("newrights",$page_libRights['val'],$page_libRights['label'],$rights,$defaultLabel=null,$defaultVal=null,$actions=null) ?>
		</span><br class=form>
    <?php
    if ($isadmin) {
      echo '<span class=form>Federation: </span>';
      echo '<span class=formright>';
      writeHtmlSelect("newfed",array(-1,0,1,2),array("Don't change","Not federated","Federated","Federated, top of list"));
      echo '</span><br class=form>';
    }
    ?>
		<p>
			<input type=submit value="Change Rights">
			<input type=button value="Nevermind" class="secondarybtn" onclick="window.location='managelibs.php?cid=<?php echo $cid ?>'">
		</p>
	</form>
<?php
	} else if (isset($_POST['chgsort'])) {
?>
	<form method=post action="managelibs.php?cid=<?php echo $cid ?>">
		<input type=hidden name=chgsort value="<?php echo Sanitize::encodeStringForDisplay($tlist); ?>">
		<span class=form>Sort order: </span>
		<span class=formright>
			<input type="radio" name="sortorder" value="0" checked/> Creation date<br/>
			<input type="radio" name="sortorder" value="1"/> Alphabetical
		</span><br class=form>
		<p>
			<input type=submit value="Change Sort Order">
			<input type=button value="Nevermind" class="secondarybtn" onclick="window.location='managelibs.php?cid=<?php echo $cid ?>'">
		</p>
	</form>
<?php
	}else if (isset($_POST['setparent'])) {
?>
	<form method=post action="managelibs.php?cid=<?php echo $cid ?>">
		<input type=hidden name=setparent value="<?php echo Sanitize::encodeStringForDisplay($tlist); ?>">
		<span class=form>New Parent Library: </span>
		<span class=formright>
			<span id="libnames"></span>
			<input type=hidden name="libs" id="libs"  value="<?php echo Sanitize::encodeStringForDisplay($parent); ?>">
			<input type=button value="Select Library" onClick="libselect()">
		</span><br class=form>

		<p>
			<input type=submit value="Set Parent">
			<input type=button value="Nevermind" class="secondarybtn" onclick="window.location='managelibs.php?cid=<?php echo $cid ?>'">
		</p>
	</form>
<?php
	} else if (isset($_GET['modify'])) {
?>
	<form method=post action="managelibs.php?cid=<?php echo $cid ?>&modify=<?php echo Sanitize::encodeUrlParam($_GET['modify']); ?>">
		<span class=form>Library Name:</span>
		<span class=formright><input type=text name="name" value="<?php echo Sanitize::encodeStringForDisplay($name); ?>" size=20></span><br class=form>
		<?php
		if (($isgrpadmin || $isadmin) && isset($ownername)) {
			echo '<span class=form>Owner:</span><span class=formright>'.Sanitize::encodeStringForDisplay($ownername).'</span><br class=form />';
		}
		?>
		<span class=form>Rights: </span>
		<span class=formright>
			<?php writeHtmlSelect ("rights",$page_libRights['val'],$page_libRights['label'],$rights,$defaultLabel=null,$defaultVal=null,$actions=null) ?>
		</span><br class=form>

    <?php
    if ($isadmin) {
      echo '<span class=form>Federation: </span>
      <span class=formright>';
      writeHtmlSelect ("fedlevel",array(0,1,2),array('Not federated','Federated','Federated, top of list'),$fedlevel);
      echo '</span><br class=form>';
    }

     ?>
		<span class=form>Sort order: </span>
		<span class=formright>
			<input type="radio" name="sortorder" value="0" <?php writeHtmlChecked($sortorder,0); ?> />Creation date<br/>
			<input type="radio" name="sortorder" value="1" <?php writeHtmlChecked($sortorder,1); ?> />Alphabetical
		</span><br class=form>

		<span class=form>Parent Library:</span>
		<span class=formright>
			<span id="libnames"><?php echo Sanitize::encodeStringForDisplay($lnames); ?></span>
			<input type=hidden name="libs" id="libs"  value="<?php echo Sanitize::encodeStringForDisplay($parent); ?>">
			<input type=button value="Select Library" onClick="libselect()">
		</span><br class=form>
		<div class=submit>
			<input type=submit value="Save Changes">
			<input type=button value="Nevermind" class="secondarybtn" onclick="window.location='managelibs.php?cid=<?php echo $cid ?>'">
		</div>
	</form>

	<i>Note</i>: Creating a library with rights less restrictive than the parent library will force
	the parent library to match the rights of the child library.
<?php
	} else { //DEFAULT DISPLAY

		echo $page_AdminModeMsg;
?>
	<form method=post action="managelibs.php?cid=<?php echo $cid ?>">
		<input type=button value="Add New Library" onclick="window.location='managelibs.php?modify=new&cid=<?php echo $cid ?>'">
	</form>

<?php
		foreach ($rights as $k=>$n) {
			setparentrights($k);
		}

		$qcount[0] = addupchildqs(0);
?>

	<form id="qform" method=post action="managelibs.php?cid=<?php echo $cid ?>">
		<div>
			Check: <a href="#" onclick="return chkAllNone('qform','nchecked[]',true)">All</a> <a href="#" onclick="return chkAllNone('qform','nchecked[]',false)">None</a>
			With Selected: <input type=submit name="transfer" value="Transfer" title="Transfer library ownership">
			<input type=submit name="remove" value="Delete" title="Delete library">
			<input type=submit name="setparent" value="Change Parent" title="Change the parent library">
			<input type=submit name="chgrights" value="Change Rights" title="Change library use rights">
			<input type=submit name="chgsort" value="Change Sort" title="Change library sort order">
			<?php echo $page_appliesToMsg ?>

		</div>
		<p>
			Root

			<ul class=base>
<?php
		$count = 0;

		if (isset($ltlibs[0])) {
			printlist(0);
		}
?>
			</ul>
		</p>
		<p>
			<b>Color Code</b><br/>
			<span class=r8>Open to all</span><br/>
			<span class=r4>Closed</span><br/>
			<span class=r5>Open to group, closed to others</span><br/>
			<span class=r2>Open to group, private to others</span><br/>
			<span class=r1>Closed to group, private to others</span><br/>
			<span class=r0>Private</span>
		</p>

	</form>
<?php
	}


}

require("../footer.php");

function delqimgs($qsid) {
  global $DBH;

  $srch_stm = $DBH->prepare("SELECT id FROM imas_qimages WHERE filename=:filename");
  $del_stm = $DBH->prepare("DELETE FROM imas_qimages WHERE id=:id");

	//DB $query = "SELECT id,filename,var FROM imas_qimages WHERE qsetid='$qsid'";
	//DB $result = mysql_query($query) or die("Query failed :$query " . mysql_error());
	$stm = $DBH->prepare("SELECT id,filename,var FROM imas_qimages WHERE qsetid=:qsetid");
	$stm->execute(array(':qsetid'=>$qsid));
	//DB while ($row = mysql_fetch_row($result)) {
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		//DB $query = "SELECT id FROM imas_qimages WHERE filename='{$row[1]}'";
		//DB $r2 = mysql_query($query) or die("Query failed :$query " . mysql_error());
		//DB if (mysql_num_rows($r2)==1) {
		if (substr($row[1],0,4)!='http') {
			$srch_stm->execute(array(':filename'=>$row[1]));
			if ($srch_stm->rowCount()==1) {
				unlink(rtrim(dirname(__FILE__), '/\\') .'/../assessment/qimages/'.$row[1]);
			}
		}
		//DB $query = "DELETE FROM imas_qimages WHERE id='{$row[0]}'";
		//DB mysql_query($query) or die("Query failed :$query " . mysql_error());
		$del_stm->execute(array(':id'=>$row[0]));
	}
}

function printlist($parent) {
	global $names,$ltlibs,$count,$qcount,$cid,$rights,$sortorder,$ownerids,$userid,$isadmin,$groupids,$groupid,$isgrpadmin,$federated;
	$arr = $ltlibs[$parent];

	if ($sortorder[$parent]==1) {
		$orderarr = array();
		foreach ($arr as $child) {
			$orderarr[$child] = $names[$child];
		}
		natcasesort($orderarr);
		$arr = array_keys($orderarr);
	}
	if ($parent==0 && $isadmin) {
		$arr[] = -2;
		$arr[] = -3;
		$names[-2] = "Root Level Private Libraries";
		$names[-3] = "Root Level Group Libraries";
		$rights[-2] = 0;
		$rights[-3] = 2;
		$ltlibs[-2] = array();
		$ltlibs[-3] = array();
	}

	foreach ($arr as $child) {
		if ($isadmin && $parent==0 && $rights[$child]<5 && $child>=0 && $ownerids[$child]!=$userid && ($rights[$child]==0 || $groupids[$child]!=$groupid)) {
			if ($rights[$child]==0) {
				$ltlibs[-2][] = $child;
			} else {
				$ltlibs[-3][] = $child;
			}
			continue;
		}
		//if ($rights[$child]>0 || $ownerids[$child]==$userid || $isadmin) {
		if ($rights[$child]>2 || ($rights[$child]>0 && $groupids[$child]==$groupid) || $ownerids[$child]==$userid || ($isgrpadmin && $groupids[$child]==$groupid) ||$isadmin) {
			if (!$isadmin) {
				if ($rights[$child]==5 && $groupids[$child]!=$groupid) {
					$rights[$child]=4;  //adjust coloring
				}
			}
			if (isset($ltlibs[$child])) { //library has children
				//echo "<li><input type=button id=\"b$count\" value=\"-\" onClick=\"toggle($count)\"> {$names[$child]}";
				echo "<li class=lihdr><span class=dd>-</span><span class=\"hdr btn\" id=\"bn" . Sanitize::encodeStringForDisplay($child) . "\" onClick=\"toggle('n" . Sanitize::encodeStringForJavascript($child) . "')\">+</span> ";
				if ($child>=0) {
					echo "<input type=checkbox name=\"nchecked[]\" value=" . Sanitize::encodeStringForDisplay($child) . "> ";
				}
				echo "<span class=hdr onClick=\"toggle('n" . Sanitize::encodeStringForJavascript($child) . "')\"><span class=\"r" . Sanitize::encodeStringForDisplay($rights[$child]) . "\">" . Sanitize::encodeStringForDisplay($names[$child]) ;
				if ($federated[$child]) {
					echo ' <span class=fedico title="Federated">&lrarr;</span>';
				}
				echo "</span> </span>\n";
				//if ($isadmin) {
				if ($child>=0) {
				  echo " ({$qcount[$child]}) ";

					echo "<span class=op>";
					if ($ownerids[$child]==$userid || ($isgrpadmin && $groupids[$child]==$groupid) || $isadmin) {
						echo "<a href=\"managelibs.php?cid=$cid&modify=" . Sanitize::encodeUrlParam($child) . "\">Modify</a> | ";
					}
					echo "<a href=\"managelibs.php?cid=$cid&modify=new&parent=" . Sanitize::encodeUrlParam($child) . "\">Add Sub</a> ";
					echo "</span>";
				}
				echo "<ul class=hide id=\"n" . Sanitize::encodeStringForDisplay($child) . "\">\n";
				$count++;
				printlist($child);
				echo "</ul></li>\n";

			} else if ($child>=0) {  //no children

				echo "<li><span class=dd>-</span><input type=checkbox name=\"nchecked[]\" value=" . Sanitize::encodeStringForDisplay($child) . "> <span class=\"r" . Sanitize::encodeStringForDisplay($rights[$child]) . "\">" . Sanitize::encodeStringForDisplay($names[$child]);
				if ($federated[$child]) {
					echo ' <span class=fedico title="Federated">&lrarr;</span>';
				}
				echo "</span> ";
				//if ($isadmin) {
				  echo " ({$qcount[$child]}) ";
				//}
				echo "<span class=op>";
				if ($ownerids[$child]==$userid || ($isgrpadmin && $groupids[$child]==$groupid) || $isadmin) {
					echo "<a href=\"managelibs.php?cid=$cid&modify=".Sanitize::encodeUrlParam($child)."\">Modify</a> | ";
				}
				if ($qcount[$child]==0) {
					echo "<a href=\"managelibs.php?cid=$cid&modify=new&parent=" . Sanitize::encodeUrlParam($child) . "\">Add Sub</a> ";
				} else {
					echo "<a href=\"reviewlibrary.php?cid=$cid&lib=" . Sanitize::encodeUrlParam($child) . "\">Preview</a>";
				}
				echo "</span>";
				echo "</li>\n";


			}
		}
	}
}

function addupchildqs($p) {
	global $qcount,$ltlibs;
	if (isset($ltlibs[$p])) { //if library has children
		foreach ($ltlibs[$p] as $child) {
			$qcount[$p] += addupchildqs($child);
		}
	}
	return $qcount[$p];
}

function setparentrights($alibid) {
	global $rights,$parents;
	if ($parents[$alibid]>0) {
		if ($rights[$parents[$alibid]] < $rights[$alibid]) {
		//if (($rights[$parents[$alibid]]>2 && $rights[$alibid]<3) || ($rights[$alibid]==0 && $rights[$parents[$alibid]]>0)) {
			$rights[$parents[$alibid]] = $rights[$alibid];
		}
		setparentrights($parents[$alibid]);
	}
}

?>

<?php
//IMathAS:  Main admin page
//(c) 2006 David Lippman

/*
 Note on library userights:
 8 Open to all 
 5 Open to group, closed to others
 4 Closed to all
 2 Open to group, private to others
 1 Closed to group, private to others
 0 Private

 >=4 visible outside group
 >=0 visible to group
*/

/*** master php includes *******/
require_once "../init.php";
require_once "../includes/htmlutil.php";

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

	if (!empty($_POST['libs'])) {
		$checkedLibraries = array_map('intval', explode(',', $_POST['libs']));
		$ph = Sanitize::generateQueryPlaceholders($checkedLibraries);
		$stm = $DBH->prepare("SELECT name FROM imas_libraries WHERE id IN ($ph)");
		$stm->execute($checkedLibraries);
		$libchgul = '<ul>';
		while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
			$libchgul .= '<li>'.Sanitize::encodeStringForDisplay($row['name']).'</li>';
		}
		$libchgul .= '</ul>';
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
                if ($remlist == '') {
                    echo _("No selected libraries can be deleted");
                    exit;
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
					$stm = $DBH->prepare("SELECT DISTINCT qsetid FROM `imas_library_items` WHERE qsetid IN ($qids_query_placeholders) AND deleted=0");
					$stm->execute($qids);
					$okqids = array();
					while ($row = $stm->fetch(PDO::FETCH_NUM)) {
						$okqids[] = $row[0];
					}
					//get a list of questions with no more library items
					$qidstofix = array_values(array_diff($qidstocheck,$okqids));
                    if (count($qidstofix)>0) {
                        $qlist = array_map('Sanitize::onlyInt', $qidstofix);//INTs from DB
                        $qlist_query_placeholders = Sanitize::generateQueryPlaceholders($qlist);
                        if ($_POST['delq']=='yes') {
                            //$query = "DELETE FROM imas_questionset WHERE id IN ($qlist)";
                            $stm = $DBH->prepare("UPDATE imas_questionset SET deleted=1,lastmoddate=? WHERE id IN ($qlist_query_placeholders)");
                            $stm->execute(array_merge(array($now),$qlist));
                                //echo "del: $qlist";
                                /*foreach ($qidstofix as $qid) {
                                    delqimgs($qid);
                                }*/
                        } else {
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
				}
				$DBH->commit();
			}
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/managelibs.php?cid=$cid" . "&r=" . Sanitize::randomQueryStringParam());

			exit;
		} else {
			$pagetitle = "Confirm Removal";
			$curBreadcrumb .= " &gt; <a href=\"managelibs.php?cid=$cid\">Manage Libraries</a> &gt; Confirm Removal ";
			if (!isset($_POST['libs'])) {
				$overwriteBody = 1;
				$body = "No libraries selected.  <a href=\"managelibs.php?cid=$cid\">Go back</a>\n";
			} else {
				$oktorem = array();
				$stm = $DBH->prepare("SELECT count(id) FROM imas_libraries WHERE parent=:parent AND deleted=0");
				for ($i=0; $i<count($checkedLibraries); $i++) {
					$stm->execute(array(':parent'=>$checkedLibraries[$i]));
					$libcnt= $stm->fetchColumn(0);
					if ($libcnt == 0) {
						$oktorem[] = $checkedLibraries[$i];
					}
				}
				$rlist = implode(",", array_map('intval', $oktorem));
				$hasChildWarning = (count($checkedLibraries)>count($oktorem)) ? "<p>Warning:  Some libraries selected have children, and cannot be deleted.</p>\n": "";
			}
		}
	} else if (isset($_POST['chgrights'])) {
		if (isset($_POST['newrights'])) {
			if ($_POST['newrights']!='') {
				$llist = implode(',', array_map('intval', explode(',',$_POST['chgrights'])));
        if ($isadmin && $_POST['newfed']>-1) {
          $query = "UPDATE imas_libraries SET userights=:userights,federationlevel=:fedlevel,lastmoddate=:lastmoddate WHERE id IN ($llist)";
				  $qarr = array(':userights'=>$_POST['newrights'], ':fedlevel'=>$_POST['newfed'], ':lastmoddate'=>$now);
        } else {
				  $query = "UPDATE imas_libraries SET userights=:userights,lastmoddate=:lastmoddate WHERE id IN ($llist)";
				  $qarr = array(':userights'=>$_POST['newrights'], ':lastmoddate'=>$now);
        }
				if (!$isadmin) {
					$query .= " AND groupid=:groupid";
					$qarr[':groupid']=$groupid;
				}
				if (!$isadmin && !$isgrpadmin) {
					$query .= " AND ownerid=:ownerid";
					$qarr[':ownerid'] = $userid;
				}
				$stm = $DBH->prepare($query);
				$stm->execute($qarr);

			}
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/managelibs.php?cid=$cid" . "&r=" . Sanitize::randomQueryStringParam());

			exit;

		} else {
			$pagetitle = "Change Library Rights";
			$curBreadcrumb .= " &gt; <a href=\"managelibs.php?cid=$cid\">Manage Libraries</a> &gt; Change Library Rights ";
			if (!isset($_POST['libs'])) {
				$overwriteBody = 1;
				$body = "No libraries selected.  <a href=\"managelibs.php?cid=$cid\">Go back</a>\n";
			} else {
                $tlist = Sanitize::encodeStringForDisplay(implode(",",$checkedLibraries));
                $rights = 0;
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
				$llist = implode(',', array_map('intval', explode(',',$_POST['chgsort'])));
				$query = "UPDATE imas_libraries SET sortorder=:sortorder,lastmoddate=:lastmoddate WHERE id IN ($llist)";
				$qarr = array(':sortorder'=>$_POST['sortorder'], ':lastmoddate'=>$now);
				if (!$isadmin) {
					$query .= " AND groupid=:groupid";
					$qarr[':groupid']=$groupid;
				}
				if (!$isadmin && !$isgrpadmin) {
					$query .= " AND ownerid=:ownerid";
					$qarr[':ownerid'] = $userid;
				}
				$stm = $DBH->prepare($query);
				$stm->execute($qarr);

			}
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/managelibs.php?cid=$cid" . "&r=" . Sanitize::randomQueryStringParam());

			exit;

		} else {
			$pagetitle = "Change Library Sort Order";
			$curBreadcrumb .= " &gt; <a href=\"managelibs.php?cid=$cid\">Manage Libraries</a> &gt; Change Library Sort Order ";
			if (!isset($_POST['libs'])) {
				$overwriteBody = 1;
				$body = "No libraries selected.  <a href=\"managelibs.php?cid=$cid\">Go back</a>\n";
			} else {
				$tlist = Sanitize::encodeStringForDisplay(implode(",",$checkedLibraries));
			}
		}

	} else if (isset($_POST['transfer'])) {
		if (isset($_POST['newowner'])) {
			if ($_POST['transfer']!='') {
				$translist = implode(',', array_map('intval', explode(',',$_POST['transfer'])));

				//added for mysql 3.23 compatibility
				$stm = $DBH->prepare("SELECT groupid FROM imas_users WHERE id=:id");
				$stm->execute(array(':id'=>$_POST['newowner']));
				$newgpid = $stm->fetchColumn(0);
				$query = "UPDATE imas_libraries SET ownerid=:ownerid,groupid=:groupid WHERE imas_libraries.id IN ($translist)";
				$qarr = array(':ownerid'=>$_POST['newowner'], ':groupid'=>$newgpid);

				if (!$isadmin) {
				  $query .= " AND groupid=:groupid";
				  $qarr[':groupid']=$groupid;
				}
				if (!$isadmin && !$isgrpadmin) {
				  $query .= " AND ownerid=:ownerid";
				  $qarr[':ownerid'] = $userid;
				}
				$stm = $DBH->prepare($query);
				$stm->execute($qarr);

			}
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/managelibs.php?cid=$cid" . "&r=" . Sanitize::randomQueryStringParam());

			exit;
		} else {
			$pagetitle = "Confirm Transfer";
			$curBreadcrumb .= " &gt; <a href=\"managelibs.php?cid=$cid\">Manage Libraries</a> &gt; Confirm Transfer ";
			if (!isset($_POST['libs'])) {
				$overwriteBody = 1;
				$body = "No libraries selected.  <a href=\"managelibs.php?cid=$cid\">Go back</a>\n";
			} else {
				$tlist = implode(",", array_map('intval', $checkedLibraries));
				/*$stm = $DBH->query("SELECT id,FirstName,LastName FROM imas_users WHERE rights>19 ORDER BY LastName,FirstName");
				$i=0;
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					$page_newOwnerList['val'][$i] = $row[0];
					$page_newOwnerList['label'][$i] = $row[2] . ", " . $row[1];
					$i++;
				}
                */
			}
		}
	} else if (isset($_POST['setparent'])) {
		if (isset($_POST['parentlib'])) {
			if ($_POST['parentlib']!='') {
				$toset = array();
				$_POST['setparent'] = explode(',',$_POST['setparent']);
				foreach ($_POST['setparent'] as $alib) {
					if ($alib != $_POST['parentlib']) {
						$toset[] = $alib;
					}
				}
				if (count($toset)>0) {
					$parlist = implode(',', array_map('intval',$toset));
					$query = "UPDATE imas_libraries SET parent=:parent,lastmoddate=:lastmoddate WHERE id IN ($parlist)";
					$qarr = array(':parent'=>$_POST['parentlib'], ':lastmoddate'=>$now);
					  if ($isgrpadmin) {
					    $query .= " AND groupid=:groupid";
					    $qarr[':groupid']=$groupid;
					  } else if (!$isadmin && !$isgrpadmin) {
					    $query .= " AND ownerid=:ownerid";
					    $qarr[':ownerid'] = $userid;
					  }
					  $stm = $DBH->prepare($query);
					  $stm->execute($qarr);
				}
			}
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/managelibs.php?cid=$cid" . "&r=" . Sanitize::randomQueryStringParam());

			exit;
		} else {
			$pagetitle = "Set Parent";
			$curBreadcrumb .= " &gt; <a href=\"managelibs.php?cid=$cid\">Manage Libraries</a> &gt; Set Parent ";
            $parent1 = "";
            $parent = '';

			if (!isset($_POST['libs'])) {
				$overwriteBody = 1;
				$body = "No libraries selected.  <a href=\"managelibs.php?cid=$cid\">Go back</a>\n";
			} else {
				$tlist = Sanitize::encodeStringForDisplay(implode(",",$checkedLibraries));
			}
		}
	} else if (isset($_GET['transfer'])) {
		if (isset($_POST['newowner'])) {

			//added for mysql 3.23 compatibility
			$stm = $DBH->prepare("SELECT groupid FROM imas_users WHERE id=:id");
			$stm->execute(array(':id'=>$_POST['newowner']));
			$newgpid = $stm->fetchColumn(0);

			//$query = "UPDATE imas_libraries,imas_users SET imas_libraries.ownerid='{$_POST['newowner']}'";
			//$query .= ",imas_libraries.groupid=imas_users.groupid WHERE imas_libraries.ownerid=imas_users.id AND ";
			//$query .= "imas_libraries.id='{$_GET['transfer']}'";
			$query = "UPDATE imas_libraries SET ownerid=:ownerid,groupid=:groupid WHERE imas_libraries.id=:id";
			$qarr = array(':ownerid'=>$_POST['newowner'], ':groupid'=>$newgpid, ':id'=>$_GET['transfer']);
		      if ($isgrpadmin) {
			$query .= " AND groupid=:groupid";
			$qarr[':groupid']=$groupid;
		      } else if (!$isadmin && !$isgrpadmin) {
			$query .= " AND ownerid=:ownerid";
			$qarr[':ownerid'] = $userid;
		      }
		      $stm = $DBH->prepare($query);
		      $stm->execute($qarr);
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/managelibs.php?cid=$cid" . "&r=" . Sanitize::randomQueryStringParam());
			exit;
		} else {
			$pagetitle = "Transfer Library";
			$curBreadcrumb .= " &gt; <a href=\"managelibs.php?cid=$cid\">Manage Libraries</a> &gt; $pagetitle ";
			/*
            $stm = $DBH->query("SELECT id,FirstName,LastName FROM imas_users WHERE rights>19 ORDER BY LastName,FirstName");
			$i=0;
			$page_newOwnerList = array();
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$page_newOwnerList['val'][$i] = $row[0];
				$page_newOwnerList['label'][$i] = $row[2] . ", " . $row[1];
				$i++;
			}
            */

		}

	} else if (isset($_GET['modify'])) {
		if (isset($_POST['name']) && trim($_POST['name'])!='') {
			if ($_GET['modify']=="new") {
				$_POST['name'] = str_replace(array(',','\\"','\\\'','~'),"",$_POST['name']);
				$stm = $DBH->prepare("SELECT * FROM imas_libraries WHERE name=:name AND parent=:parent");
				$stm->execute(array(':name'=>$_POST['name'], ':parent'=>$_POST['parentlib']));
				if ($stm->rowCount()>0) {
					$overwriteBody =1;
					$body = "Library already exists by that name with this parent.\n";
					$body .= "<p><a href=\"managelibs.php?cid=$cid&modify=new\">Try Again</a></p>\n";
				} else {
					$mt = microtime();
					$uqid = substr($mt,11).substr($mt,2,6);
					$query = "INSERT INTO imas_libraries (uniqueid,adddate,lastmoddate,name,ownerid,userights,sortorder,parent,groupid,federationlevel) VALUES ";
					$query .= "(:uniqueid, :adddate, :lastmoddate, :name, :ownerid, :userights, :sortorder, :parent, :groupid, :fedlevel)";
					$stm = $DBH->prepare($query);
					$stm->execute(array(':uniqueid'=>$uqid, ':adddate'=>$now, ':lastmoddate'=>$now, ':name'=>$_POST['name'], ':ownerid'=>$userid,
						':userights'=>$_POST['rights'], ':sortorder'=>$_POST['sortorder'], ':parent'=>$_POST['parentlib'], ':groupid'=>$groupid,
            ':fedlevel'=>($isadmin?$_POST['fedlevel']:0)));
					header('Location: ' . $GLOBALS['basesiteurl'] . "/course/managelibs.php?cid=$cid" . "&r=" . Sanitize::randomQueryStringParam());
					exit;
				}
			} else {
				$query = "UPDATE imas_libraries SET name=:name,userights=:userights,sortorder=:sortorder,lastmoddate=:lastmoddate";
				$qarr = array(':name'=>$_POST['name'], ':userights'=>$_POST['rights'], ':sortorder'=>$_POST['sortorder'], ':lastmoddate'=>$now, ':id'=>$_GET['modify']);
				if ($_GET['modify'] != $_POST['parentlib']) {
					$query .= ",parent=:parent";
					$qarr[':parent']=$_POST['parentlib'];
				}
        if ($isadmin) {
          $query .= ",federationlevel=:fedlevel";
          $qarr[':fedlevel'] = $_POST['fedlevel'];
        }
				$query .= " WHERE id=:id";

				if ($isgrpadmin) {
				  $query .= " AND groupid=:groupid";
				  $qarr[':groupid']=$groupid;
				} else if (!$isadmin) {
				  $query .= " AND ownerid=:ownerid";
				  $qarr[':ownerid'] = $userid;
				}
				$stm = $DBH->prepare($query);
				$stm->execute($qarr);

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
					$stm = $DBH->prepare("SELECT il.name,il.userights,il.parent,il.sortorder,iu.firstName,iu.lastName,il.federationlevel FROM imas_libraries AS il JOIN imas_users AS iu ON il.ownerid=iu.id WHERE il.id=:id");
					$stm->execute(array(':id'=>$_GET['modify']));
				} else {
					$stm = $DBH->prepare("SELECT name,userights,parent,sortorder FROM imas_libraries WHERE id=:id AND ownerid=:ownerid");
					$stm->execute(array(':id'=>$_GET['modify'], ':ownerid'=>$userid));
				}
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
		$helpicon = "&nbsp;&nbsp; <img src=\"$staticroot/img/help.gif\" alt=\"Help\" onClick=\"window.open('$imasroot/help.php?section=managelibraries','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))\"/>";
		$curBreadcrumb .= " &gt; Manage Libraries ";
		if ($isadmin) {
			$page_AdminModeMsg = "You are in Admin mode, which means actions will apply to all libraries, regardless of owner";
		} else if ($isgrpadmin) {
			$page_AdminModeMsg =  "You are in Group Admin mode, which means actions will apply to all libraries from your group, regardless of owner";
		} else {
			$page_AdminModeMsg = "";
		}
		
		$page_appliesToMsg = (!$isadmin) ? "(Only applies to your libraries)" : "";
	}
}

$placeinhead = "<script type=\"text/javascript\" src=\"$staticroot/javascript/accessibletree.js\"></script>\n";
$placeinhead .= '<link rel="stylesheet" href="'.$staticroot.'/javascript/accessibletree.css" type="text/css" />';

/******* begin html output ********/
require_once "../header.php";

if ($overwriteBody==1) {
	echo $body;
} else {
?>
	<script>
	var curlibs = '<?php echo Sanitize::encodeStringForJavascript($parent1 ?? ''); ?>';
	// used for selecting parents
	function libselect() {
		GB_show('<?php echo _('Library Select');?>','libtree3.php?libtree=popup&select=parents&addroot=true&selectrights=1&mode=single&libs='+curlibs,500);
	}
	function setlib(libs) {
		document.getElementById("parentlib").value = libs;
		curlibs = libs;
	}
	function setlibnames(libn) {
		document.getElementById("libnames").textContent = libn;
	}
	function postWSform(val) {
        $('#qform').append($('<input>', {name:val, value:val, type:'hidden'})).submit();
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
	<div id="headermanagelibs" class="pagetitle"><h1><?php echo $pagetitle; echo $helpicon; ?></h1></div>

<?php
	if (isset($_POST['remove'])) {
?>
	<p>Potentially Impacted Libraries:</p>
	<?php echo $libchgul; ?>

 	<?php echo $hasChildWarning; ?>
	Are you SURE you want to delete these libraries?
	<form method=post action="managelibs.php?cid=<?php echo $cid ?>&confirmed=true">
		<p>
			<label><input type=radio name="delq" value="no" CHECKED>
			Yes, and move questions in library to Unassigned</label><br>
			<label><input type=radio name="delq" value="yes" >
			Yes, and also delete questions in library</label>
		</p>
		<input type=hidden name=remove value="<?php echo Sanitize::encodeStringForDisplay($rlist); ?>">
		<p>
			<input type=submit value="Really Delete">
			<input type=button value="Nevermind" class="secondarybtn" onclick="window.location='managelibs.php?cid=<?php echo $cid ?>'">
		</p>
	</form>
<?php
	} else if (isset($_POST['transfer'])) {
?>
	<form method=post action="managelibs.php?cid=<?php echo $cid ?>">
		<p>Potentially Impacted Libraries:</p>
		<?php echo $libchgul; ?>

		<input type=hidden name=transfer value="<?php echo Sanitize::encodeStringForDisplay($tlist); ?>">
		
		<?php //writeHtmlSelect ("newowner",$page_newOwnerList['val'],$page_newOwnerList['label'],$selectedVal=null,$defaultLabel=null,$defaultVal=null,$actions=null) ?>
        <?php require_once '../includes/userlookupform.php'; 
            generateUserLookupForm(_('Transfer library ownership to:'), 'newowner');
        ?>

		<p>
			<input type=submit value="Transfer">
			<input type=button value="Nevermind" class="secondarybtn" onclick="window.location='managelibs.php?cid=<?php echo $cid ?>'">
		</p>
	</form>
<?php
	} else if (isset($_POST['chgrights'])) {
?>
	<form method=post action="managelibs.php?cid=<?php echo $cid ?>">
		<p>Potentially Impacted Libraries:</p>
		<?php echo $libchgul; ?>

		<input type=hidden name=chgrights value="<?php echo Sanitize::encodeStringForDisplay($tlist); ?>">
		<label for=newrights class=form>Library use rights: </label>
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
		<p>Potentially Impacted Libraries:</p>
		<?php echo $libchgul; ?>

		<input type=hidden name=chgsort value="<?php echo Sanitize::encodeStringForDisplay($tlist); ?>">
		<span class=form id="lbl">Sort order: </span>
		<span class=formright role=radiogroup aria-labelledby="lbl">
			<label><input type="radio" name="sortorder" value="0" checked/> Creation date</label><br/>
			<label><input type="radio" name="sortorder" value="1"/> Alphabetical</label>
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
		<p>Potentially Impacted Libraries:</p>
		<?php echo $libchgul; ?>

		<input type=hidden name=setparent value="<?php echo Sanitize::encodeStringForDisplay($tlist); ?>">
		<span class=form>New Parent Library: </span>
		<span class=formright>
			<span id="libnames"></span>
			<input type=hidden name="parentlib" id="parentlib"  value="<?php echo Sanitize::encodeStringForDisplay($parent); ?>">
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
		<label for=name class=form>Library Name:</label>
		<span class=formright><input type=text name="name" id=name value="<?php echo Sanitize::encodeStringForDisplay($name); ?>" size=20></span><br class=form>
		<?php
		if (($isgrpadmin || $isadmin) && isset($ownername)) {
			echo '<span class=form>Owner:</span><span class=formright>'.Sanitize::encodeStringForDisplay($ownername).'</span><br class=form />';
		}
		?>
		<label for=rights class=form>Rights: </label>
		<span class=formright>
			<?php writeHtmlSelect ("rights",$page_libRights['val'],$page_libRights['label'],$rights,$defaultLabel=null,$defaultVal=null,$actions=null) ?>
		</span><br class=form>

    <?php
    if ($isadmin) {
      echo '<label for=fedlevel class=form>Federation: </label>
      <span class=formright>';
      writeHtmlSelect ("fedlevel",array(0,1,2),array('Not federated','Federated','Federated, top of list'),$fedlevel);
      echo '</span><br class=form>';
    }

     ?>
		<span id="sortlbl" class=form>Sort order: </span>
		<span class=formright role=radiogroup aria-labelledby="sortlbl">
			<label><input type="radio" name="sortorder" value="0" <?php writeHtmlChecked($sortorder,0); ?> />Creation date</label><br/>
			<label><input type="radio" name="sortorder" value="1" <?php writeHtmlChecked($sortorder,1); ?> />Alphabetical</label>
		</span><br class=form>

		<span class=form>Parent Library:</span>
		<span class=formright>
			<span id="libnames"><?php echo Sanitize::encodeStringForDisplay($lnames); ?></span>
			<input type=hidden name="parentlib" id="parentlib"  value="<?php echo Sanitize::encodeStringForDisplay($parent); ?>">
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

	<form id="qform" method=post action="managelibs.php?cid=<?php echo $cid ?>">
		<div style="margin: 10px 0">
			Check: <a href="#" onclick="treeWidget.unselectAll();return false;">None</a>
			<span class="dropdown">
				<a role=button tabindex=0 class="dropdown-toggle arrow-down" id="dropdownMenuWithsel" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				<?php echo _('With Selected');?>
				</a>
				<ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenuWithsel">
				<li><a href="#" onclick="postWSform('transfer');return false;"><?php echo _("Transfer library ownership");?></a></li>
				<li><a href="#" onclick="postWSform('remove');return false;"><?php echo _("Delete library");?></a></li>
				<li><a href="#" onclick="postWSform('setparent');return false;"><?php echo _("Change parent library");?></a></li>
				<li><a href="#" onclick="postWSform('chgrights');return false;"><?php echo _("Change library use rights");?></a></li>
				<li><a href="#" onclick="postWSform('chgsort');return false;"><?php echo _("Change library sort order");?></a></li>
				</ul>
			</span>
			<?php echo $page_appliesToMsg ?>
		</div>
		
		<?php
		$select = 'all';
		$mode = 'multi';
		$showcounts = true;
		$showmanagelibslinks = true;
		$hideunassigned = true;
		$_GET['selectrights'] = 1;
		$includecounts = true;
		require_once "libtree3.php";
		?>
	</form>
<?php
	}


}

require_once "../footer.php";

function delqimgs($qsid) {
  global $DBH;

  $srch_stm = $DBH->prepare("SELECT id FROM imas_qimages WHERE filename=:filename");
  $del_stm = $DBH->prepare("DELETE FROM imas_qimages WHERE id=:id");
	$stm = $DBH->prepare("SELECT id,filename,var FROM imas_qimages WHERE qsetid=:qsetid");
	$stm->execute(array(':qsetid'=>$qsid));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		if (substr($row[1],0,4)!='http') {
			$srch_stm->execute(array(':filename'=>$row[1]));
			if ($srch_stm->rowCount()==1) {
				unlink(rtrim(dirname(__FILE__), '/\\') .'/../assessment/qimages/'.$row[1]);
			}
		}
		$del_stm->execute(array(':id'=>$row[0]));
	}
}

function addupchildqs($p) {
	global $qcount,$ltlibs;
	if (isset($ltlibs[$p])) { //if library has children
		foreach ($ltlibs[$p] as $child) {
            if (!isset($qcount[$p])) {
                $qcount[$p] = 0;
            }
			$qcount[$p] += addupchildqs($child);
		}
	}
	return $qcount[$p];
}

?>

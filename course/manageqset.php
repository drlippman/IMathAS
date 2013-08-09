<?php
//IMathAS:  Main admin page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../validate.php");
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

	$cid = $_GET['cid'];
	if ($cid=='admin') {
		if ($myrights >74 && $myrights<100) {
			$isgrpadmin = true;
		} else if ($myrights == 100) {
			$isadmin = true;
		}
	}
			
	if ($isadmin || $isgrpadmin) {
		$curBreadcrumb = "$breadcrumbbase <a href=\"../admin/admin.php\">Admin</a> ";
	} else if ($cid==0) {
		$curBreadcrumb = "<a href=\"../index.php\">Home</a> ";
	} else {
		$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\">$coursename</a> ";
	}	
		
		
	if (isset($_POST['remove'])) {
		if (isset($_GET['confirmed'])) {
			if ($_POST['remove']!='') {
				$remlist = "'".implode("','",explode(',',$_POST['remove']))."'";
				
				if ($isadmin) {
					$query = "DELETE FROM imas_library_items WHERE qsetid IN ($remlist)";
					mysql_query($query) or die("Query failed :$query " . mysql_error());
					/*foreach (explode(',',$_POST['remove']) as $qid) {
						delqimgs($qid);
					}*/
				} else if ($isgrpadmin) {
					$query = "SELECT imas_questionset.id FROM imas_questionset,imas_users WHERE ";
					$query .= "imas_questionset.id IN ($remlist) AND imas_questionset.ownerid=imas_users.id ";
					$query .= "AND imas_users.groupid='$groupid'";
					$result = mysql_query($query) or die("Query failed : " . mysql_error());
					while ($row = mysql_fetch_row($result)) {
						$query = "DELETE FROM imas_library_items WHERE qsetid='$row[0]'";
						mysql_query($query) or die("Query failed : $query" . mysql_error());
						//delqimgs($row[0]);
					}
					
				} else {
					$query = "SELECT id FROM imas_questionset WHERE id IN ($remlist) AND ownerid='$userid'";
					$result = mysql_query($query) or die("Query failed : " . mysql_error());
					while ($row = mysql_fetch_row($result)) {
						$query = "DELETE FROM imas_library_items WHERE qsetid='$row[0]'";
						mysql_query($query) or die("Query failed : $query" . mysql_error());
						//delqimgs($row[0]);
					}
				}
				
				if ($isgrpadmin) {
					$query = "SELECT imas_questionset.id FROM imas_questionset,imas_users WHERE imas_questionset.ownerid=imas_users.id AND imas_questionset.id IN ($remlist) AND imas_users.groupid='$groupid'";
					$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
					while ($row = mysql_fetch_row($result)) {
						//$query = "DELETE FROM imas_questionset WHERE id='{$row[0]}'";
						$query = "UPDATE imas_questionset SET deleted=1 WHERE id='{$row[0]}'";
						mysql_query($query) or die("Query failed : " . mysql_error());
					}
				} else {
					$query = "UPDATE imas_questionset SET deleted=1 WHERE id IN ($remlist)";
					//$query = "DELETE FROM imas_questionset WHERE id IN ($remlist)";
					if (!$isadmin) {
						$query .= " AND ownerid='$userid'";
					}
					mysql_query($query) or die("Query failed : " . mysql_error());
				}
				
			}
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/manageqset.php?cid=$cid");
			
			exit;
		} else {
			if (!isset($_POST['nchecked'])) {
				$overwriteBody = 1;
				$body = "No questions selected.  <a href=\"manageqset.php?cid=$cid\">Go back</a>\n";
			}
			$pagetitle = "Confirm Delete";
			$curBreadcrumb .= " &gt; <a href=\"manageqset.php?cid=$cid\">Manage Question Set </a>";
			$curBreadcrumb .= " &gt; Confirm Delete";
			
			$rlist = implode(",",$_POST['nchecked']);
		}
	} else if (isset($_POST['transfer'])) {
		if (isset($_POST['newowner'])) {
			if ($_POST['transfer']!='') {
				$translist = "'".implode("','",explode(',',$_POST['transfer']))."'";
				
				if ($isgrpadmin) {
					$query = "SELECT imas_questionset.id FROM imas_questionset,imas_users WHERE imas_questionset.ownerid=imas_users.id AND imas_questionset.id IN ($translist) AND imas_users.groupid='$groupid'";
					$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
					while ($row = mysql_fetch_row($result)) {
						$query = "UPDATE imas_questionset SET ownerid='{$_POST['newowner']}' WHERE id='{$row[0]}'";
						mysql_query($query) or die("Query failed : $query " . mysql_error());
					}

				} else {
					$query = "UPDATE imas_questionset SET ownerid='{$_POST['newowner']}' WHERE id IN ($translist)";
					if (!$isadmin) {
						$query .= " AND ownerid='$userid'";
					}
					mysql_query($query) or die("Query failed : $query " . mysql_error());
				}
		
			}
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/manageqset.php?cid=$cid");
			
			exit;
		} else {

			$pagetitle ="Transfer Ownership";
			$curBreadcrumb .= " &gt; <a href=\"manageqset.php?cid=$cid\">Manage Question Set </a>";
			$curBreadcrumb .= " &gt; Transfer QSet";
			
			$tlist = implode(",",$_POST['nchecked']);
			if (!isset($_POST['nchecked'])) {
				$overwriteBody = 1;
				$body = "No questions selected.  <a href=\"manageqset.php?cid=$cid\">Go back</a>\n";
			}
			
			$query = "SELECT id,FirstName,LastName FROM imas_users WHERE rights>19 ORDER BY LastName,FirstName";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$i=0;
			$page_transferUserList = array();
			while ($row = mysql_fetch_row($result)) {
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
					if ($newlibs[0]==0 && count($newlibs)>1) { //get rid of unassigned if checked and others are checked
						array_shift($newlibs);
					}
				}
				
				$libarray = explode(',',$_POST['qtochg']); //qsetids to change
				if ($_POST['qtochg']=='') {
					$libarray = array();
				}
				$chglist = "'".implode("','",$libarray)."'";
				
				$alllibs = array();
				$query = "SELECT qsetid,libid FROM imas_library_items WHERE qsetid IN ($chglist)";
				$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
				while ($row = mysql_fetch_row($result)) {
					$alllibs[$row[0]][] = $row[1];	
				}
				if ($isgrpadmin) {
					$query = "SELECT imas_library_items.qsetid,imas_library_items.libid FROM imas_library_items,imas_users WHERE ";
					$query .= "imas_library_items.ownerid=imas_users.id AND (imas_users.groupid='$groupid' OR imas_library_items.libid=0)";
					$query .= "AND imas_library_items.qsetid IN ($chglist)";
				} else {
					$query = "SELECT ili.qsetid,ili.libid FROM imas_library_items AS ili LEFT JOIN imas_libraries AS il ON ";
					$query .= "ili.libid=il.id WHERE ili.qsetid IN ($chglist)";
					if (!$isadmin) {
						//unassigned, or owner and lib not closed or mine
						$query .= " AND ((ili.ownerid='$userid' AND (il.ownerid='$userid' OR il.userights%3<>1)) OR ili.libid=0)";
					}
				}
				$mylibs = array();
				$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
				while ($row = mysql_fetch_row($result)) {
					$mylibs[$row[0]][] = $row[1];	
				}
				
				if ($_POST['action']==0) {//add, keep existing
					/*
					get list of existing library assignments
					remove any additions that already exist
					add to new libraries
					*/
					foreach ($libarray as $qsetid) { //for each question
						//determine which checked libraries it's not already in
						$toadd = array_values(array_diff($newlibs,$alllibs[$qsetid]));
						//and add them
						foreach($toadd as $libid) {
							if ($libid==0) { continue;} //no need to add to unassigned using "keep existing" 
							$query = "INSERT INTO imas_library_items (libid,qsetid,ownerid) VALUES ('$libid','$qsetid','$userid')";
							mysql_query($query) or die("Query failed :$query " . mysql_error());	
						}
						if (count($toadd)>1 || (count($toadd)>0 && $toadd[0]!=0)) {
							$query = "DELETE FROM imas_library_items WHERE qsetid='$qsetid' AND libid=0";
							mysql_query($query) or die("Query failed :$query " . mysql_error());	
						}
					}
				} else if ($_POST['action']==1) { //add, remove existing
					/*
					get list of existing library assignments
					rework existing to new libs
					remove any excess existing
					add to any new
					*/
					foreach ($libarray as $qsetid) { //for each question
						//determine which checked libraries it's not already in
						$toadd = array_diff($newlibs,$alllibs[$qsetid]);
						//and add them
						foreach($toadd as $libid) {
							$query = "INSERT INTO imas_library_items (libid,qsetid,ownerid) VALUES ('$libid','$qsetid','$userid')";
							mysql_query($query) or die("Query failed :$query " . mysql_error());	
						}
						//determine which libraries to remove from; my lib assignments - newlibs
						if (isset($mylibs[$qsetid])) {
							$toremove = array_diff($mylibs[$qsetid],$newlibs);
							foreach($toremove as $libid) {
								$query = "DELETE FROM imas_library_items WHERE libid='$libid' AND qsetid='$qsetid'";
								mysql_query($query) or die("Query failed :$query " . mysql_error());	
							}
						
							//check for unassigneds
							$query = "SELECT id FROM imas_library_items WHERE qsetid='$qsetid'";
							$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
							if (mysql_num_rows($result)==0) {
								$query = "INSERT INTO imas_library_items (libid,qsetid,ownerid) VALUES (0,'$qsetid','$userid')";
								mysql_query($query) or die("Query failed :$query " . mysql_error());
							}
						}
					}
					
					
					
				} else if ($_POST['action']==2) { //remove
					/*
					get list of exisiting assignments
					if not in checked list, remove
					*/
					foreach ($libarray as $qsetid) { //for each question
						//determine which libraries to remove from; my lib assignments - newlibs
						if (isset($mylibs[$qsetid])) {
							$toremove = array_diff($mylibs[$qsetid],$newlibs);
							foreach($toremove as $libid) {
								$query = "DELETE FROM imas_library_items WHERE libid='$libid' AND qsetid='$qsetid'";
								mysql_query($query) or die("Query failed :$query " . mysql_error());	
							}
							//check for unassigneds
							$query = "SELECT id FROM imas_library_items WHERE qsetid='$qsetid'";
							$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
							if (mysql_num_rows($result)==0) {
								$query = "INSERT INTO imas_library_items (libid,qsetid,ownerid) VALUES (0,'$qsetid','$userid')";
								mysql_query($query) or die("Query failed :$query " . mysql_error());
							}
						}
					}
					
				}
/*
				if ($_POST['onlyadd']==1) { //only adding to newly check libs
					$query = "SELECT libid FROM imas_library_items WHERE qsetid IN ($chglist)";
					$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
					while ($row = mysql_fetch_row($result)) {
						$checked[] = $row[0];	
					}
					
					$toadd = array_values(array_diff($newlibs,$checked));
					if (count($toadd)>0) { 
						foreach ($libarray as $qsetid) {
							foreach($toadd as $libid) { 
								$query = "INSERT INTO imas_library_items (libid,qsetid,ownerid) VALUES ('$libid','$qsetid','$userid')";
								mysql_query($query) or die("Query failed :$query " . mysql_error());
							} 
						}
					}
					
				} //making changes
				foreach ($libarray as $qsetid) {
					if ($isgrpadmin) {
						$query = "SELECT imas_library_items.libid FROM imas_library_items,imas_users WHERE ";
						$query .= "imas_library_items.ownerid=imas_users.id AND imas_users.groupid='$groupid' ";
						$query .= "AND imas_library_items.qsetid='$qsetid'";
					} else {
						$query = "SELECT libid FROM imas_library_items WHERE qsetid='$qsetid'";
						if (!$isadmin) {
							$query .= " AND (ownerid='$userid' OR libid=0)";
						}
					}
					$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
					$existing = array();
					while($row = mysql_fetch_row($result)) { 
						$existing[] = $row[0]; 
					}  
					if ($_POST['onlyadd']==1) { //don't do per question adding here; already did new check adds
						$toadd = array();	
					} else {
						$toadd = array_values(array_diff($newlibs,$existing));
					}
					$toremove = array_values(array_diff($existing,$newlibs));
										
					while(count($toremove)>0 && count($toadd)>0) { 
						$tochange = array_shift($toremove); 
						$torep = array_shift($toadd); 
						$query = "UPDATE imas_library_items SET libid='$torep' WHERE qsetid='$qsetid' AND libid='$tochange'";
						mysql_query($query) or die("Query failed :$query " . mysql_error());
					} 
					if (count($toadd)>0) { 
						foreach($toadd as $libid) { 
							$query = "INSERT INTO imas_library_items (libid,qsetid,ownerid) VALUES ('$libid','$qsetid','$userid')";
							mysql_query($query) or die("Query failed :$query " . mysql_error());
						} 
					} else if (count($toremove)>0) { 
						foreach($toremove as $libid) { 
							$query = "DELETE FROM imas_library_items WHERE libid='$libid' AND qsetid='$qsetid'";
							mysql_query($query) or die("Query failed :$query " . mysql_error());
						} 
					} 
					//if (count($newlibs)==0) {
						$query = "SELECT id FROM imas_library_items WHERE qsetid='$qsetid'";
						$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
						if (mysql_num_rows($result)==0) {
							$query = "INSERT INTO imas_library_items (libid,qsetid,ownerid) VALUES (0,'$qsetid','$userid')";
							mysql_query($query) or die("Query failed :$query " . mysql_error());
						}
					//}
					
				}
*/
				
			}
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/manageqset.php?cid=$cid");
			
			exit;
		} else {
			$pagetitle = "Modify Library Assignments";
			$curBreadcrumb .= " &gt; <a href=\"manageqset.php?cid=$cid\">Manage Question Set </a>";
			$curBreadcrumb .= " &gt; Modify Assignments";
			
			
		
			if (!isset($_POST['nchecked'])) {
				$overwriteBody = 1;
				$body = "No questions selected.  <a href=\"manageqset.php?cid=$cid\">Go back</a>\n";
			} else {
				$clist = implode(",",$_POST['nchecked']);
				$query = "SELECT DISTINCT ili.libid FROM imas_library_items AS ili WHERE ili.qsetid IN ($clist)";
				$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
				while ($row = mysql_fetch_row($result)) {
					$checked[] = $row[0];	
				}
				$_GET['selectrights'] = 1;
			}
			/*
			if (!$isadmin && !$isgrpadmin) {
				$query = "SELECT libid FROM imas_library_items WHERE qsetid IN ($clist) AND ownerid!='$userid'";
				$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
				while ($row = mysql_fetch_row($result)) {
					$locked[] = $row[0];	
				}
			} else if ($isgrpadmin) {
				$query = "SELECT ili.libid FROM imas_library_items AS ili,imas_users WHERE ";
				$query .= "ili.ownerid=imas_users.id AND imas_users.groupid!='$groupid' AND ili.qsetid IN ($clist)";
				$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
				while ($row = mysql_fetch_row($result)) {
					$locked[] = $row[0];	
				}
			}
			*/
			
			/*if ($isgrpadmin) {
				$query = "SELECT ili.libid FROM imas_library_items AS ili,imas_users WHERE ";
				$query .= "ili.ownerid=imas_users.id AND imas_users.groupid='$groupid' AND ili.qsetid IN ($clist)";
			} else {
				$query = "SELECT libid FROM imas_library_items WHERE qsetid IN ($clist)";
				if (!$isadmin) {
					$query .= " AND ownerid='$userid'";
				}
			}
			*/
			
			
		}		
		
		
	} else if (isset($_POST['template'])) {
		if (isset($_POST['qtochg'])) {
			if (!isset($_POST['libs'])) {
				$overwriteBody = 1;
				$body = "<html><body>No library selected.  <a href=\"manageqset.php?cid=$cid\">Go back</a></body></html>\n";
			}
			$lib = $_POST['libs'];
			$qtochg = explode(',',$_POST['qtochg']);
			$now = time();
			$query = "SELECT firstName,lastName FROM imas_users WHERE id='$userid'";
			$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
			$row = mysql_fetch_row($result);
			$myname = $row[1].','.$row[0];
			foreach ($qtochg as $k=>$qid) {
				$query = "SELECT description,userights,qtype,control,qcontrol,qtext,answer,hasimg FROM imas_questionset WHERE id='$qid'";
				$result = mysql_query($query) or die("Query failed : $query" . mysql_error());
				$row = mysql_fetch_row($result);
				$row[0] .= " (copy by $userfullname)";
				$mt = microtime();
				$uqid = substr($mt,11).substr($mt,2,3).$k;
				$query = "INSERT INTO imas_questionset (uniqueid,adddate,lastmoddate,ownerid,author,description,userights,qtype,control,qcontrol,qtext,answer,hasimg) VALUES ";
				$query .= "('$uqid','$now','$now','$userid','$myname','".implode("','",addslashes_deep($row))."')";
				mysql_query($query) or die("Query failed : $query" . mysql_error());
				$nqid = mysql_insert_id();
				$query = "INSERT INTO imas_library_items (libid,qsetid,ownerid) VALUES ('$lib','$nqid','$userid')";
				mysql_query($query) or die("Query failed :$query " . mysql_error());
				
				$query = "SELECT var,filename,alttext FROM imas_qimages WHERE qsetid='$qid'";
				$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
				while ($row = mysql_fetch_row($result)) {
					$query = "INSERT INTO imas_qimages (qsetid,var,filename,alttext) VALUES ('$nqid','{$row[0]}','{$row[1]}','{$row[2]}')";
					mysql_query($query) or die("Query failed :$query " . mysql_error());
				}
				
			}
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/manageqset.php?cid=$cid");
			
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
		
	} else if (isset($_POST['chgrights'])) {
		if (isset($_POST['qtochg'])) {
			if ($isgrpadmin) {
				$query = "SELECT imas_questionset.id FROM imas_questionset,imas_users WHERE imas_questionset.ownerid=imas_users.id AND imas_questionset.id IN ($chglist) AND imas_users.groupid='$groupid'";
				$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
				$tochg = array();
				while ($row = mysql_fetch_row($result)) {
					$tochg[] = $row[0];
				}
				if (count($tochg)>0) {
					$chglist = implode(',',$tochg);
					$query = "UPDATE imas_questionset SET userights='{$_POST['newrights']}' WHERE id IN ($chglist)";
					mysql_query($query) or die("Query failed : $query " . mysql_error());
				}
			} else {
				$chglist = "'".implode("','",explode(',',$_POST['qtochg']))."'";
				$query = "UPDATE imas_questionset SET userights='{$_POST['newrights']}' WHERE id IN ($chglist)";
				if (!$isadmin) {
					$query .= " AND ownerid='$userid'";
				}
				mysql_query($query) or die("Query failed : $query " . mysql_error());
			}
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/manageqset.php?cid=$cid");
			exit;
		} else {
			$pagetitle = "Change Question Rights";
			$curBreadcrumb .= " &gt; <a href=\"manageqset.php?cid=$cid\">Manage Question Set </a>";
			$curBreadcrumb .= " &gt; Change Question Rights";
			
			$clist = implode(",",$_POST['nchecked']);
			
			if (!isset($_POST['nchecked'])) {
				$overwriteBody = 1;
				$body = "No questions selected.  <a href=\"manageqset.php?cid=$cid\">Go back</a>\n";
			}
		}
	} else if (isset($_GET['remove'])) {
		if (isset($_GET['confirmed'])) {
			if ($isgrpadmin) {
				$query = "SELECT imas_questionset.id FROM imas_questionset,imas_users WHERE ";
				$query .= "imas_questionset.ownerid=imas_users.id AND imas_users.groupid='$groupid' AND ";
				$query .= "imas_questionset.id='{$_GET['remove']}'";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				if (mysql_num_rows($result)>0) {
					$query = "UPDATE imas_questionset SET deleted=1 WHERE id='{$_GET['remove']}'";
					//$query = "DELETE FROM imas_questionset WHERE id='{$_GET['remove']}'";
				} else {
					header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/manageqset.php?cid=$cid");
					exit;
				}

			} else {
				//$query = "DELETE FROM imas_questionset WHERE id='{$_GET['remove']}'";
				$query = "UPDATE imas_questionset SET deleted=1 WHERE id='{$_GET['remove']}'";
				if (!$isadmin) {
					$query .= " AND ownerid='$userid'";
				}
			}
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_affected_rows($link)>0) {
				$query = "DELETE FROM imas_library_items WHERE qsetid='{$_GET['remove']}'";
				mysql_query($query) or die("Query failed : " . mysql_error());
				//delqimgs($_GET['remove']);
			}
			
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/manageqset.php?cid=$cid");
			
			exit;
		} else {
			$pagetitle = "Confirm Delete";
			$curBreadcrumb .= " &gt; <a href=\"manageqset.php?cid=$cid\">Manage Question Set </a>";
			$curBreadcrumb .= " &gt; Confirm Delete";
			
		}
	} else if (isset($_GET['transfer'])) {
		if (isset($_POST['newowner'])) {
			
			if ($isgrpadmin) {
				$query = "SELECT imas_questionset.id FROM imas_questionset,imas_users WHERE imas_questionset.ownerid=imas_users.id AND imas_questionset.id='{$_GET['transfer']}' AND imas_users.groupid='$groupid'";
				$result = mysql_query($query) or die("Query failed : $query " . mysql_error());
				if (mysql_num_rows($result)>0) {
					$query = "UPDATE imas_questionset SET ownerid='{$_POST['newowner']}' WHERE id='{$_GET['transfer']}'";
					mysql_query($query) or die("Query failed : " . mysql_error());
				}

			} else {
				$query = "UPDATE imas_questionset SET ownerid='{$_POST['newowner']}' WHERE id='{$_GET['transfer']}'";
				if (!$isadmin) {
					$query .= " AND ownerid='$userid'";
				}
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
			
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/manageqset.php?cid=$cid");
			exit;
		} else {
			$pagetitle = "Transfer Ownership";
			$curBreadcrumb .= " &gt; <a href=\"manageqset.php?cid=$cid\">Manage Question Set </a>";
			$curBreadcrumb .= " &gt; Transfer QSet";
			
			$query = "SELECT id,FirstName,LastName FROM imas_users WHERE rights>19 ORDER BY LastName,FirstName";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$i=0;
			$page_transferUserList = array();
			while ($row = mysql_fetch_row($result)) {
				$page_transferUserList['val'][$i] = $row[0];
				$page_transferUserList['label'][$i] = $row[2] . ", " . $row[1];
				$i++;
			}	
			
			
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
			$safesearch = $_POST['search'];
			$safesearch = str_replace(' and ', ' ',$safesearch);
			$search = stripslashes($safesearch);
			$search = str_replace('"','&quot;',$search);
			$sessiondata['lastsearch'.$cid] = $safesearch; //str_replace(" ","+",$safesearch);
			if (isset($_POST['searchmine'])) {
				$searchmine = 1;
			} else {
				$searchmine = 0;
			}
			$sessiondata['searchmine'.$cid] = $searchmine;
			if (isset($_POST['searchall'])) {
				$searchall = 1;
			} else {
				$searchall = 0;
			}
			$sessiondata['searchall'.$cid] = $searchall;
			if ($searchall==1 && trim($search)=='' && $searchmine==0) {
				$overwriteBody = 1;
				$body = "Must provide a search term when searching all libraries <a href=\"manageqset.php\">Try again</a>";
				$searchall = 0;
			} 
			
			writesessiondata();
		} else if (isset($sessiondata['lastsearch'.$cid])) {
			$safesearch = $sessiondata['lastsearch'.$cid]; //str_replace("+"," ",$sessiondata['lastsearch'.$cid]);
			$search = stripslashes($safesearch);
			$search = str_replace('"','&quot;',$search);
			$searchall = $sessiondata['searchall'.$cid];
			$searchmine = $sessiondata['searchmine'.$cid];
		} else {
			$search = '';
			$searchall = 0;
			$searchmine = 0;
			$safesearch = '';
		}
		if (trim($safesearch)=='') {
			$searchlikes = '';
		} else {
			if (substr($safesearch,0,6)=='regex:') {
				$safesearch = substr($safesearch,6);
				$searchlikes = "imas_questionset.description REGEXP '$safesearch' AND ";
			} else {$searchterms = explode(" ",$safesearch);
				$searchlikes = "((imas_questionset.description LIKE '%".implode("%' AND imas_questionset.description LIKE '%",$searchterms)."%') ";
				if (substr($safesearch,0,3)=='id=') {
					$searchlikes = "imas_questionset.id='".substr($safesearch,3)."' AND ";
				} else if (is_numeric($safesearch)) {
					$searchlikes .= "OR imas_questionset.id='$safesearch') AND ";
				} else {
					$searchlikes .= ") AND";
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
			$search = '';
			$safesearch = '';
			writesessiondata();
		}else if (isset($sessiondata['lastsearchlibs'.$cid])) {
			//$searchlibs = explode(",",$sessiondata['lastsearchlibs']);
			$searchlibs = $sessiondata['lastsearchlibs'.$cid];
		} else {
			$searchlibs = $userdeflib;
		}
		
		$llist = "'".implode("','",explode(',',$searchlibs))."'";
		
		$libsortorder = array();
		if (substr($searchlibs,0,1)=="0") {
			$lnamesarr[0] = "Unassigned";
			$libsortorder[0] = 0;
		}
		
		$query = "SELECT name,id,sortorder FROM imas_libraries WHERE id IN ($llist)";
		$result = mysql_query($query) or die("Query failed: $query: " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
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
		
		$query = "SELECT DISTINCT imas_questionset.id,imas_questionset.ownerid,imas_questionset.description,imas_questionset.userights,imas_questionset.lastmoddate,imas_questionset.extref,";
		$query .= "imas_questionset.qtype,imas_users.firstName,imas_users.lastName,imas_users.groupid,imas_library_items.libid,imas_library_items.junkflag, imas_library_items.id AS libitemid ";
		$query .= "FROM imas_questionset,imas_library_items,imas_users WHERE imas_questionset.deleted=0 AND $searchlikes ";
		$query .= "imas_library_items.qsetid=imas_questionset.id AND imas_questionset.ownerid=imas_users.id ";
		
		if ($isadmin) {
			if ($searchall==0) {
				$query .= "AND imas_library_items.libid IN ($llist)";
			}
			if ($searchmine==1) {
				$query .= " AND imas_questionset.ownerid='$userid'";
			}
		} else if ($isgrpadmin) {
			$query .= "AND (imas_users.groupid='$groupid' OR imas_questionset.userights>0) "; 
			$query .= "AND (imas_library_items.libid > 0 OR imas_users.groupid='$groupid')";
			if ($searchall==0) {
				$query .= " AND imas_library_items.libid IN ($llist)";
			}
			if ($searchmine==1) {
				$query .= " AND imas_questionset.ownerid='$userid'";
			}
		} else {
			$query .= "AND (imas_questionset.ownerid='$userid' OR imas_questionset.userights>0) "; 
			$query .= "AND (imas_library_items.libid > 0 OR imas_questionset.ownerid='$userid')";	
			if ($searchall==0) {
				$query .= " AND imas_library_items.libid IN ($llist)";
			}
			if ($searchmine==1) {
				$query .= " AND imas_questionset.ownerid='$userid'";
			}
		}
		$query.= " ORDER BY imas_library_items.libid,imas_library_items.junkflag,imas_questionset.id LIMIT 500";
		$resultLibs = mysql_query($query) or die("Query failed : " . mysql_error());
		
		$page_questionTable = array();
		$page_libstouse = array();
		$page_libqids = array();
		$lastlib = -1;
		$ln=1;
		
		while ($line = mysql_fetch_array($resultLibs, MYSQL_ASSOC)) {
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
			
			$page_questionTable[$i]['checkbox'] = "<input type=checkbox name='nchecked[]' value='" . $line['id'] . "' id='qo$ln'>";
			if ($line['userights']==0) {
				$page_questionTable[$i]['desc'] = '<span class="red">'.filter($line['description']).'</span>';
			} else {
				$page_questionTable[$i]['desc'] = filter($line['description']);
			}
			
			if ($line['extref']!='') {
				$extref = explode('~~',$line['extref']);
				$hasvid = false;  $hasother = false;
				foreach ($extref as $v) {
					if (strtolower(substr($v,0,5))=="video" || strpos($v,'youtube.com')!==false || strpos($v,'youtu.be')!==false) {
						$hasvid = true;
					} else {
						$hasother = true;
					}
				}
				$page_questionTable[$i]['extref'] = '';
				if ($hasvid) {
					$page_questionTable[$i]['extref'] .= "<img src=\"$imasroot/img/video_tiny.png\"/>";
				}
				if ($hasother) {
					$page_questionTable[$i]['extref'] .= "<img src=\"$imasroot/img/html_tiny.png\"/>";
				}
			}
					
				
			$page_questionTable[$i]['preview'] = "<input type=button value=\"Preview\" onClick=\"previewq('selform',$ln,{$line['id']})\"/>";
			$page_questionTable[$i]['type'] = $line['qtype'];
			if ($searchall==1) {
				$page_questionTable[$i]['lib'] = "<a href=\"manageqset.php?cid=$cid&listlib={$line['libid']}\">List lib</a>";
			} else {
				$page_questionTable[$i]['junkflag'] = $line['junkflag'];	
				$page_questionTable[$i]['libitemid'] = $line['libitemid'];
			}
			$page_questionTable[$i]['times'] = 0;
			
			if ($isadmin || $isgrpadmin) {
				$page_questionTable[$i]['mine'] = $line['lastName'] . ',' . substr($line['firstName'],0,1);
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
			$page_questionTable[$i]['action'] = "<select onchange=\"doaction(this.value,{$line['id']})\"><option value=\"0\">Action..</option>";
			if ($isadmin || ($isgrpadmin && $line['groupid']==$groupid) || $line['ownerid']==$userid || ($line['userights']==3 && $line['groupid']==$groupid) || $line['userights']>3) {
				$page_questionTable[$i]['action'] .= '<option value="mod">Modify Code</option>';
			} else {
				$page_questionTable[$i]['action'] .= '<option value="mod">View Code</option>';
			}
			$page_questionTable[$i]['action'] .= '<option value="temp">Template</option>';
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
			$allusedqids = implode(',', array_keys($page_questionTable));
			$query = "SELECT questionsetid,COUNT(id) FROM imas_questions WHERE questionsetid IN ($allusedqids) GROUP BY questionsetid";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
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
$placeinhead .= "<script type=\"text/javascript\">var JunkFlagsaveurl = '".$urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/savelibassignflag.php';</script>";
	
require("../header.php");

$address = $urlmode . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\');

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

var curlibs = '<?php echo $searchlibs ?>';

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
	<div id="headermanageqset" class="pagetitle"><h2><?php echo $pagetitle; echo $helpicon; ?></h2></div>

<?php	
	if (isset($_POST['remove'])) {
?>
		Are you SURE you want to delete these questions from the Question Set.  This will make them unavailable
		to all users.  If any are currently being used in an assessment, it will mess up that assessment.
		<form method=post action="manageqset.php?cid=<?php echo $cid ?>&confirmed=true">
			<input type=hidden name=remove value="<?php echo $rlist ?>">
			<p>
				<input type=submit value="Really Delete">
				<input type=button value="Never Mind" onclick="window.location='manageqset.php?cid=<?php echo $cid ?>'">
			</p>
		</form>	
<?php
	} else if (isset($_POST['transfer'])) {
?>
		<form method=post action="manageqset.php?cid=<?php echo $cid ?>">
			<input type=hidden name=transfer value="<?php echo $tlist ?>">
			Transfer to: 
		
			<?php writeHtmlSelect("newowner",$page_transferUserList['val'],$page_transferUserList['label']); ?>
		
			<p>
				<input type=submit value="Transfer">
				<input type=button value="Never Mind" onclick="window.location='manageqset.php?cid=<?php echo $cid ?>'">
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
			} else if (val==1) {
				help.innerHTML = "Select libraries to add these questions to.  Questions will only be removed from existing libraries if you have the rights to make those changes.";
				if (chgliblaststate==2) {
					initlibtree(false);
				}
			} else if (val==2) {
				help.innerHTML = "Unselect the libraries you want to remove questions from.  The questions will not be deleted; they will be moved to Unassigned if no other library assignments exist.  Questions will only be removed from existing libraries if you have the rights to make those changes.";
				if (chgliblaststate==0 || chgliblaststate==1) {
					initlibtree(true);
				}
			}
			chgliblaststate = val;
		}
		</script>
		<form method=post action="manageqset.php?cid=<?php echo $cid ?>">
			<input type=hidden name=chglib value="true">
			<input type=hidden name=qtochg value="<?php echo $clist ?>">
			What do you want to do with these questions?<br/>
			<input type=radio name="action" value="0" onclick="chglibtoggle(this)" checked="checked"/> Add to libraries, keeping any existing library assignments<br/>
			<input type=radio name="action" value="1" onclick="chglibtoggle(this)"/> Add to libraries, removing existing library assignments<br/>
			<input type=radio name="action" value="2" onclick="chglibtoggle(this)"/> Remove library assignments
			<p id="chglibhelp" style="font-weight: bold;">
			Select libraries to add these questions to.
			</p>
			
			<?php $libtreeshowchecks = false; include("libtree2.php"); ?>

			
			<p>
				<input type=submit value="Make Changes">
				<input type=button value="Never Mind" onclick="window.location='manageqset.php?cid=<?php echo $cid ?>'">
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
			
			<input type=hidden name=qtochg value="<?php echo $clist ?>">
			
			<?php include("libtree.php"); ?>
			
			<p>
				<input type=submit value="Template Questions">
				<input type=button value="Never Mind" onclick="window.location='manageqset.php?cid=<?php echo $cid ?>'">
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
			
			<input type="hidden" name="qtochg" value="<?php echo $clist ?>">
			
			
			<p>
				<input type=submit value="Change Rights">
				<input type=button value="Never Mind" onclick="window.location='manageqset.php?cid=<?php echo $cid ?>'">
			</p>
		</form>
<?php		
	} else if (isset($_GET['remove'])) {
?>
		Are you SURE you want to delete this question from the Question Set.  This will make it unavailable 
		to all users.  If it is currently being used in an assessment, it will mess up that assessment.
		<p>
			<input type=button onclick="window.location='manageqset.php?cid=<?php echo $cid ?>&remove=<?php echo $_GET['remove'] ?>&confirmed=true'" value="Really Delete">
			<input type=button value="Never Mind" onclick="window.location='manageqset.php?cid=<?php echo $cid ?>'">
		</p>
<?php
	} else if (isset($_GET['transfer'])) {
?>
		<form method=post action="manageqset.php?cid=<?php echo $cid ?>&transfer=<?php echo $_GET['transfer'] ?>">
			Transfer to: 
		
			<?php writeHtmlSelect("newowner",$page_transferUserList['val'],$page_transferUserList['label']); ?>

			<p>
				<input type=submit value="Transfer">
				<input type=button value="Never Mind" onclick="window.location='manageqset.php?cid=<?php echo $cid ?>'">
			</p>
		</form>
<?php	
	} else { //DEFAULT DISPLAY
		
		echo $page_adminMsg;
		
		echo "<form method=post action=\"manageqset.php?cid=$cid\">\n";

		echo "In Libraries: <span id=\"libnames\">$lnames</span><input type=hidden name=\"libs\" id=\"libs\"  value=\"$searchlibs\">\n";
		//echo " <input type=button value=\"Select Libraries\" onClick=\"libselect()\"> <br>"; 
		echo '<input type="button" value="Select Libraries" onClick="GB_show(\'Library Select\',\'libtree2.php?cid='.$cid.'&libtree=popup&libs=\'+curlibs,500,500)" /> <br>';
		
		echo "Search: <input type=text size=15 name=search value=\"$search\"> <input type=checkbox name=\"searchall\" value=\"1\" ";
		if ($searchall==1) {echo "checked=1";}
		echo "/>Search all libs <input type=checkbox name=\"searchmine\" value=\"1\" ";
		if ($searchmine==1) {echo "checked=1";}
		echo "/>Mine only ";
		
		echo "<input type=submit value=Search>\n";
		echo "<input type=button value=\"Add New Question\" onclick=\"window.location='moddataset.php?cid=$cid'\">\n";
		echo "</form>";
		
		echo "<script type=\"text/javascript\" src=\"$imasroot/javascript/tablesorter.js\"></script>\n";
		echo "<form id=\"selform\" method=post action=\"manageqset.php?cid=$cid\">\n";
		//echo "Check/Uncheck All: <input type=\"checkbox\" name=\"ca2\" value=\"1\" onClick=\"chkAll(this.form, 'nchecked[]', this.checked)\">\n";
		echo 'Check: <a href="#" onclick="return chkAllNone(\'selform\',\'nchecked[]\',true)">All</a> <a href="#" onclick="return chkAllNone(\'selform\',\'nchecked[]\',false)">None</a> ';

		echo "With Selected: <input type=submit name=\"transfer\" value=\"Transfer\">\n";
		echo "<input type=submit name=\"remove\" value=\"Delete\">\n";
		echo "<input type=submit name=\"chglib\" value=\"Library Assignment\">\n";
		echo "<input type=submit name=\"chgrights\" value=\"Chg Rights\">\n";
		echo "<input type=submit name=\"template\" value=\"Template\">\n";
		if (!$isadmin && !$isgrpadmin) { 
			echo "<br/>(Delete and Transfer only applies to your questions)\n";
		} else if ($isgrpadmin) {
			echo "<br/>(Delete and Transfer only apply to group's questions)\n";
		}
		echo "<table id=myTable class=gb><thead>\n";
		echo "<tr><th>&nbsp;</th><th>Description</th><th>&nbsp;</th><th>&nbsp;</th><th>Action</th><th>Type</th><th>Times Used</th><th>Last Mod</th>";
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
				echo '<b>'.$lnamesarr[$page_libstouse[$j]].'</b>';
				echo '</td></tr>';
			}
			for ($i=0;$i<count($page_libqids[$page_libstouse[$j]]); $i++) {
				$qid =$page_libqids[$page_libstouse[$j]][$i];
				if ($alt==0) {echo "<tr class=even>"; $alt=1;} else {echo "<tr class=odd>"; $alt=0;}
				echo '<td>'.$page_questionTable[$qid]['checkbox'].'</td>';
				echo '<td>'.$page_questionTable[$qid]['desc'].'</td>';
				echo '<td class="nowrap">'.$page_questionTable[$qid]['extref'].'</td>';
				echo '<td>'.$page_questionTable[$qid]['preview'].'</td>';
				echo '<td>'.$page_questionTable[$qid]['action'].'</td>';
				echo '<td>'.$page_questionTable[$qid]['type'].'</td>';
				echo '<td class="c">'.$page_questionTable[$qid]['times'].'</td>';
				echo '<td>'.$page_questionTable[$qid]['lastmod'].'</td>';
				echo '<td class="c">'.$page_questionTable[$qid]['mine'].'</td>';
				if ($searchall==1) {
					echo '<td>'.$page_questionTable[$qid]['lib'].'</td>';
				} else if ($searchall==0) {
					if ($page_questionTable[$qid]['junkflag']==1) {
						echo "<td class=c><img class=\"pointer\" id=\"tag{$page_questionTable[$qid]['libitemid']}\" src=\"$imasroot/img/flagfilled.gif\" onClick=\"toggleJunkFlag({$page_questionTable[$qid]['libitemid']});return false;\" /></td>";
					} else {
						echo "<td class=c><img class=\"pointer\" id=\"tag{$page_questionTable[$qid]['libitemid']}\" src=\"$imasroot/img/flagempty.gif\" onClick=\"toggleJunkFlag({$page_questionTable[$qid]['libitemid']});return false;\" /></td>";
					}
				} 
				$ln++;
			}
		}

		echo "</tbody></table>\n";
		echo "<script type=\"text/javascript\">\n";
		echo "initSortTable('myTable',Array(false,'S',false,false,'S','N','D'";
		echo ",'S'";
		echo "),true);\n";
		echo "</script>\n";
		echo "</form>\n";
		echo "<p></p>\n";
	}

}
require("../footer.php");


function delqimgs($qsid) {
	$query = "SELECT id,filename,var FROM imas_qimages WHERE qsetid='$qsid'";
	$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$query = "SELECT id FROM imas_qimages WHERE filename='{$row[1]}'";
		$r2 = mysql_query($query) or die("Query failed :$query " . mysql_error());
		if (mysql_num_rows($r2)==1) { //don't delete if file is used in other questions
			unlink(rtrim(dirname(__FILE__), '/\\') .'/../assessment/qimages/'.$row[1]);
		}
		$query = "DELETE FROM imas_qimages WHERE id='{$row[0]}'";
		mysql_query($query) or die("Query failed :$query " . mysql_error());
	}
}
?>

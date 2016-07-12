<?php
//IMathAS:  Main admin page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../validate.php");
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

	$cid = $_GET['cid'];
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
		$curBreadcrumb .= " &gt; <a href=\"../admin/admin.php\">Admin</a> ";
	}	
	if ($cid!=0) {
		$curBreadcrumb .= " &gt; <a href=\"course.php?cid=$cid\">$coursename</a> ";
	}	
		
	if (isset($_POST['remove'])) {
		if (isset($_GET['confirmed'])) {
			if ($_POST['remove']!='') {
				$remlist = "'".implode("','",explode(',',$_POST['remove']))."'";
				
				$query = "SELECT DISTINCT qsetid FROM imas_library_items WHERE libid IN ($remlist)";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				while ($row = mysql_fetch_row($result)) {
					$qidstocheck[] = $row[0];
				}
				
				if ($isadmin) {
					$query = "DELETE FROM imas_library_items WHERE libid IN ($remlist)";
					mysql_query($query) or die("Query failed : " . mysql_error());
				} else if ($isgrpadmin) {
					$query = "SELECT id FROM imas_libraries WHERE id IN ($remlist) AND groupid='$groupid'";
					$result = mysql_query($query) or die("Query failed : " . mysql_error());
					while ($row = mysql_fetch_row($result)) {
						$query = "DELETE FROM imas_library_items WHERE libid='$row[0]'";
						mysql_query($query) or die("Query failed : " . mysql_error());
					}
				} else {
					$query = "SELECT id FROM imas_libraries WHERE id IN ($remlist) AND ownerid='$userid'";
					$result = mysql_query($query) or die("Query failed : " . mysql_error());
					while ($row = mysql_fetch_row($result)) {
						$query = "DELETE FROM imas_library_items WHERE libid='$row[0]'";
						mysql_query($query) or die("Query failed : " . mysql_error());
					}
				}
								
				if (isset($qidstocheck)) {
					$qids = implode(",",$qidstocheck);
					$query = "SELECT DISTINCT qsetid FROM `imas_library_items` WHERE qsetid IN ($qids)";
					$result = mysql_query($query) or die("Query failed : " . mysql_error());
					$okqids = array();
					while ($row = mysql_fetch_row($result)) {
						$okqids[] = $row[0];
					}
					$qidstofix = array_diff($qidstocheck,$okqids);
					if ($_POST['delq']=='yes' && count($qidstofix)>0) {
						$qlist = implode(',',$qidstofix);
						//$query = "DELETE FROM imas_questionset WHERE id IN ($qlist)";
						$query = "UPDATE imas_questionset SET deleted=1 WHERE id IN ($qlist)";
						mysql_query($query) or die("Query failed : " . mysql_error());
						/*foreach ($qidstofix as $qid) {
							delqimgs($qid);
						}*/
					} else {
						foreach($qidstofix as $qid) {
							$query = "INSERT INTO imas_library_items (qsetid,libid) VALUES ('$qid',0)";
							mysql_query($query) or die("Query failed : " . mysql_error());
						}
					}
				}
				$query = "DELETE FROM imas_libraries WHERE id IN ($remlist)";
				if (!$isadmin) {
					$query .= " AND groupid='$groupid'";
				}
				if (!$isadmin && !$isgrpadmin) {
					$query .= " AND ownerid='$userid'";
				}
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/managelibs.php?cid=$cid");
			
			exit;
		} else {
			$pagetitle = "Confirm Removal";
			$curBreadcrumb .= " &gt; <a href=\"managelibs.php?cid=$cid\">Manage Libraries</a> &gt; Confirm Removal ";
			if (!isset($_POST['nchecked'])) {
				$overwriteBody = 1;
				$body = "No libraries selected.  <a href=\"managelibs.php?cid=$cid\">Go back</a>\n";
			} else {
				$oktorem = array();
				for ($i=0; $i<count($_POST['nchecked']); $i++) {
					$query = "SELECT count(id) FROM imas_libraries WHERE parent='{$_POST['nchecked'][$i]}'";
					$result = mysql_query($query) or die("Query failed : " . mysql_error());
					$libcnt= mysql_result($result,0,0);
					if ($libcnt == 0) {
						$oktorem[] = $_POST['nchecked'][$i];
					}
				}
				$rlist = implode(",",$oktorem);
				$hasChildWarning = (count($_POST['nchecked'])>count($oktorem)) ? "<p>Warning:  Some libraries selected have children, and cannot be deleted.</p>\n": "";
			}
		}
	} else if (isset($_POST['chgrights'])) {
		if (isset($_POST['newrights'])) {
			if ($_POST['newrights']!='') {
				$llist = "'".implode("','",explode(',',$_POST['chgrights']))."'";
				$query = "UPDATE imas_libraries SET userights='{$_POST['newrights']}',lastmoddate=$now WHERE id IN ($llist)";
				if (!$isadmin) {
					$query .= " AND groupid='$groupid'";
				}
				if (!$isadmin && !$isgrpadmin) {
					$query .= " AND ownerid='$userid'";
				}
				mysql_query($query) or die("Query failed : $query " . mysql_error());
			}
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/managelibs.php?cid=$cid");
			
			exit;
			
		} else {
			$pagetitle = "Change Library Rights";
			$curBreadcrumb .= " &gt; <a href=\"managelibs.php?cid=$cid\">Manage Libraries</a> &gt; Change Library Rights ";
			if (!isset($_POST['nchecked'])) {
				$overwriteBody = 1;
				$body = "No libraries selected.  <a href=\"managelibs.php?cid=$cid\">Go back</a>\n";
			} else {
				$tlist = implode(",",$_POST['nchecked']);
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
		
		
		
	} else if (isset($_POST['transfer'])) {
		if (isset($_POST['newowner'])) {
			if ($_POST['transfer']!='') {
				$translist = "'".implode("','",explode(',',$_POST['transfer']))."'";
				
				//added for mysql 3.23 compatibility
				$query = "SELECT groupid FROM imas_users WHERE id='{$_POST['newowner']}'";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				$newgpid = mysql_result($result,0,0);
				$query = "UPDATE imas_libraries SET ownerid='{$_POST['newowner']}',groupid='$newgpid' WHERE imas_libraries.id IN ($translist)";
				
				if (!$isadmin) {
					$query .= " AND groupid='$groupid'";
				}
				if (!$isadmin && !$isgrpadmin) {
					$query .= " AND ownerid='$userid'";
				}
				mysql_query($query) or die("Query failed : $query " . mysql_error());
			}
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/managelibs.php?cid=$cid");
			
			exit;
		} else {
			$pagetitle = "Confirm Transfer";
			$curBreadcrumb .= " &gt; <a href=\"managelibs.php?cid=$cid\">Manage Libraries</a> &gt; Confirm Transfer ";
			if (!isset($_POST['nchecked'])) {
				$overwriteBody = 1;
				$body = "No libraries selected.  <a href=\"managelibs.php?cid=$cid\">Go back</a>\n";
			} else {
				$tlist = implode(",",$_POST['nchecked']);
				$query = "SELECT id,FirstName,LastName FROM imas_users WHERE rights>19 ORDER BY LastName,FirstName";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				$i=0;
				while ($row = mysql_fetch_row($result)) {
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
					$parlist = "'".implode("','",$toset)."'";
					$query = "UPDATE imas_libraries SET parent='{$_POST['libs']}',lastmoddate=$now WHERE id IN ($parlist)";
					if (!$isadmin) {
						$query .= " AND groupid='$groupid'";
					}
					if (!$isadmin && !$isgrpadmin) {
						$query .= " AND ownerid='$userid'";
					}
					mysql_query($query) or die("Query failed : $query " . mysql_error());
				}
			}
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/managelibs.php?cid=$cid");
			
			exit;
		} else {
			$pagetitle = "Set Parent";
			$curBreadcrumb .= " &gt; <a href=\"managelibs.php?cid=$cid\">Manage Libraries</a> &gt; Set Parent ";
			$parent1 = "";
		
			if (!isset($_POST['nchecked'])) {
				$overwriteBody = 1;
				$body = "No libraries selected.  <a href=\"managelibs.php?cid=$cid\">Go back</a>\n";
			} else {
				$tlist = implode(",",$_POST['nchecked']);
			}
		}
	} else if (isset($_GET['remove'])) {
		if (isset($_GET['confirmed'])) {
			$query = "SELECT DISTINCT qsetid FROM imas_library_items WHERE libid='{$_GET['remove']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				$qidstocheck[] = $row[0];
			}
			$query = "DELETE FROM imas_libraries WHERE id='{$_GET['remove']}'";
			if (!$isadmin) {
				$query .= " AND groupid='$groupid'";
			}
			if (!$isadmin && !$isgrpadmin) {
				$query .= " AND ownerid='$userid'";
			}
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_affected_rows()>0 && count($qidstocheck)>0) {
				$query = "DELETE FROM imas_library_items WHERE libid='{$_GET['remove']}'";
				mysql_query($query) or die("Query failed : " . mysql_error());
				$qids = implode(",",$qidstocheck);
				$query = "SELECT DISTINCT qsetid FROM `imas_library_items` WHERE qsetid IN ($qids)";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				$okqids = array();
				while ($row = mysql_fetch_row($result)) {
					$okqids[] = $row[0];
				}
				$qidstofix = array_diff($qidstocheck,$okqids);
				if ($_POST['delq']=='yes' && count($qidstofix)>0) {
					$qlist = implode(',',$qidstofix);
					//$query = "DELETE FROM imas_questionset WHERE id IN ($qlist)";
					$query = "UPDATE imas_questionset SET deleted=1 WHERE id IN ($qlist)";
					mysql_query($query) or die("Query failed : " . mysql_error());
					/*foreach ($qidstofix as $qid) {
						delqimgs($qid);
					}*/
				} else {
					foreach($qidstofix as $qid) {
						$query = "INSERT INTO imas_library_items (qsetid,libid) VALUES ('$qid',0)";
						mysql_query($query) or die("Query failed : " . mysql_error());
					}
				}
			}
						
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/managelibs.php?cid=$cid");
			
			exit;
		} else {
			$query = "SELECT count(id) FROM imas_libraries WHERE parent='{$_GET['remove']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$libcnt= mysql_result($result,0,0);
			$pagetitle = ($libcnt>0) ? "Error" : "Remove Library";
			$curBreadcrumb .= " &gt; <a href=\"managelibs.php?cid=$cid\">Manage Libraries</a> &gt; $pagetitle ";
		}
	} else if (isset($_GET['transfer'])) {
		if (isset($_POST['newowner'])) {
			
			//added for mysql 3.23 compatibility
			$query = "SELECT groupid FROM imas_users WHERE id='{$_POST['newowner']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$newgpid = mysql_result($result,0,0);
				
			//$query = "UPDATE imas_libraries,imas_users SET imas_libraries.ownerid='{$_POST['newowner']}'";
			//$query .= ",imas_libraries.groupid=imas_users.groupid WHERE imas_libraries.ownerid=imas_users.id AND ";
			//$query .= "imas_libraries.id='{$_GET['transfer']}'";
			$query = "UPDATE imas_libraries SET ownerid='{$_POST['newowner']}',groupid='$newgpid' WHERE imas_libraries.id='{$_GET['transfer']}'";
			if (!$isadmin) {
				$query .= " AND groupid='$groupid'";
			}
			if (!$isadmin && !$isgrpadmin) {
				$query .= " AND ownerid='$userid'";
			}
			mysql_query($query) or die("Query failed : $query " . mysql_error());
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/managelibs.php?cid=$cid");
			exit;
		} else {
			$pagetitle = "Transfer Library";
			$curBreadcrumb .= " &gt; <a href=\"managelibs.php?cid=$cid\">Manage Libraries</a> &gt; $pagetitle ";
			$query = "SELECT id,FirstName,LastName FROM imas_users WHERE rights>19 ORDER BY LastName,FirstName";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$i=0;
			$page_newOwnerList = array();
			while ($row = mysql_fetch_row($result)) {
				$page_newOwnerList['val'][$i] = $row[0];			
				$page_newOwnerList['label'][$i] = $row[2] . ", " . $row[1];
				$i++;
			}

		}
	
	} else if (isset($_GET['modify'])) {
		if (isset($_POST['name']) && trim($_POST['name'])!='') {
			if ($_GET['modify']=="new") {
				$_POST['name'] = str_replace(array(',','\\"','\\\'','~'),"",$_POST['name']);
				$query = "SELECT * FROM imas_libraries WHERE name='{$_POST['name']}' AND parent='{$_POST['libs']}'";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				if (mysql_num_rows($result)>0) {
					$overwriteBody =1;
					$body = "Library already exists by that name with this parent.\n";
					$body .= "<p><a href=\"managelibs.php?cid=$cid&modify=new\">Try Again</a></p>\n";
				} else {
					$mt = microtime();
					$uqid = substr($mt,11).substr($mt,2,6);
					$query = "INSERT INTO imas_libraries (uniqueid,adddate,lastmoddate,name,ownerid,userights,sortorder,parent,groupid) VALUES ";
					$query .= "($uqid,$now,$now,'{$_POST['name']}','$userid','{$_POST['rights']}','{$_POST['sortorder']}','{$_POST['libs']}','$groupid')";
					mysql_query($query) or die("Query failed : " . mysql_error());
					header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/managelibs.php?cid=$cid");
					exit;
				}
			} else {
				
				$query = "UPDATE imas_libraries SET name='{$_POST['name']}',userights='{$_POST['rights']}',sortorder='{$_POST['sortorder']}',lastmoddate=$now";
				if ($_GET['modify'] != $_POST['libs']) {
					$query .= ",parent='{$_POST['libs']}'";
				}
				$query .= " WHERE id='{$_GET['modify']}'";
				if (!$isadmin) {
					$query .= " AND groupid='$groupid'";
				}
				if (!$isadmin && !$isgrpadmin) {
					$query .= " AND ownerid='$userid'";
				}
				mysql_query($query) or die("Query failed : " . mysql_error());
				
				header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/managelibs.php?cid=$cid");
				exit;
			}
		} else {
			$pagetitle = "Library Settings";
			$curBreadcrumb .= " &gt; <a href=\"managelibs.php?cid=$cid\">Manage Libraries</a> &gt; $pagetitle ";
			
			if ($_GET['modify']!="new") {
				$pagetitle = "Modify Library\n";
				$query = "SELECT name,userights,parent,sortorder FROM imas_libraries WHERE id='{$_GET['modify']}'";
				if (!$isadmin) {
					$query .= " AND ownerid='$userid'";
				}
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				if ($row = mysql_fetch_row($result)) {
					$name = $row[0];
					$rights = $row[1];
					$parent = $row[2];
					$sortorder = $row[3];
				}
			} else {
				$pagetitle = "Add Library\n";
				if (isset($_GET['parent'])) {
					$parent = $_GET['parent'];
				}
			}
			if (!isset($name)) { $name = '';}
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
				$query = "SELECT name FROM imas_libraries WHERE id='$parent'";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				$lnames = mysql_result($result,0,0);
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
		
		$query = "SELECT imas_libraries.id,imas_libraries.name,imas_libraries.ownerid,imas_libraries.userights,imas_libraries.sortorder,imas_libraries.parent,imas_libraries.groupid,count(imas_library_items.id) AS count ";
		$query .= "FROM imas_libraries LEFT JOIN imas_library_items ON imas_library_items.libid=imas_libraries.id ";
		$query .= "GROUP BY imas_libraries.id";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$rights = array();
		$sortorder = array();
		
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
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
	var curlibs = '<?php echo $parent1 ?>';
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
		<input type=hidden name=transfer value="<?php echo $tlist ?>">
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
		<input type=hidden name=chgrights value="<?php echo $tlist ?>">
		<span class=form>Library use rights: </span>
		<span class=formright>
			<?php writeHtmlSelect ("newrights",$page_libRights['val'],$page_libRights['label'],$rights,$defaultLabel=null,$defaultVal=null,$actions=null) ?>
		</span><br class=form>
		<p>
			<input type=submit value="Change Rights">
			<input type=button value="Nevermind" class="secondarybtn" onclick="window.location='managelibs.php?cid=<?php echo $cid ?>'">
		</p>
	</form>
<?php			
	}else if (isset($_POST['setparent'])) {
?>
	<form method=post action="managelibs.php?cid=<?php echo $cid ?>">
		<input type=hidden name=setparent value="<?php echo $tlist ?>">
		<span class=form>New Parent Library: </span>
		<span class=formright>
			<span id="libnames"></span>
			<input type=hidden name="libs" id="libs"  value="<?php echo $parent ?>">
			<input type=button value="Select Library" onClick="libselect()">
		</span><br class=form>
	
		<p>
			<input type=submit value="Set Parent">
			<input type=button value="Nevermind" class="secondarybtn" onclick="window.location='managelibs.php?cid=<?php echo $cid ?>'">
		</p>
	</form>
<?php
	} else if (isset($_GET['remove'])) {
		if ($libcnt>0) {
?>		
	The library selected has children libraries.  A parent library cannot be removed until all
	children libraries are removed.
	<p><a href="managelibs.php?cid=<?php echo $cid ?>">Back to Library Manager</a>
<?php
		} else {
?>

	<form method=post action="managelibs.php?cid=<?php echo $cid ?>&remove=<?php echo $_GET['remove'] ?>&confirmed=true">
		Are you SURE you want to delete this Library?
		<p>
			<input type=radio name="delq" value="no" CHECKED>Move questions in library to Unassigned<br>
			<input type=radio name="delq" value="yes" >Also delete questions in library
		</p>
		<p>
			<input type=submit value="Really Delete">
			<input type=button value="Nevermind" class="secondarybtn" onclick="window.location='managelibs.php?cid=<?php echo $cid ?>'">
		</p>
	</form>
<?php
		}

	} else if (isset($_GET['transfer'])) {
?>
	<form method=post action="managelibs.php?cid=<?php echo $cid ?>&transfer=<?php echo $_GET['transfer'] ?>">
		Transfer library ownership to:
		<?php	
		writeHtmlSelect ("newowner",$page_newOwnerList['val'],$page_newOwnerList['label'],$selectedVal=null,$defaultLabel=null,$defaultVal=null,$actions=null) ;
		?>
		<p>
			<input type=submit value="Transfer">
			<input type=button value="Nevermind" class="secondarybtn" onclick="window.location='managelibs.php?cid=<?php echo $cid ?>'">
		</p>
	</form>
<?php	
	} else if (isset($_GET['modify'])) {
?>
	<form method=post action="managelibs.php?cid=<?php echo $cid ?>&modify=<?php echo $_GET['modify'] ?>">
		<span class=form>Library Name:</span>
		<span class=formright><input type=text name="name" value="<?php echo $name ?>" size=20></span><br class=form>
		<span class=form>Rights: </span>
		<span class=formright>
			<?php writeHtmlSelect ("rights",$page_libRights['val'],$page_libRights['label'],$rights,$defaultLabel=null,$defaultVal=null,$actions=null) ?>
		</span><br class=form>
		
		<span class=form>Sort order: </span>
		<span class=formright>
			<input type="radio" name="sortorder" value="0" <?php writeHtmlChecked($sortorder,0); ?> />Creation date<br/>
			<input type="radio" name="sortorder" value="1" <?php writeHtmlChecked($sortorder,1); ?> />Alphabetical
		</span><br class=form>
		
		<span class=form>Parent Library:</span>
		<span class=formright>
			<span id="libnames"><?php echo $lnames ?></span>
			<input type=hidden name="libs" id="libs"  value="<?php echo $parent ?>">
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

function printlist($parent) {
	global $names,$ltlibs,$count,$qcount,$cid,$rights,$sortorder,$ownerids,$userid,$isadmin,$groupids,$groupid,$isgrpadmin;
	$arr = $ltlibs[$parent];
	if ($sortorder[$parent]==1) {
		$orderarr = array();
		foreach ($arr as $child) {
			$orderarr[$child] = $names[$child];
		}
		natcasesort($orderarr);
		$arr = array_keys($orderarr);
	}
		
	foreach ($arr as $child) {
		//if ($rights[$child]>0 || $ownerids[$child]==$userid || $isadmin) {
		if ($rights[$child]>2 || ($rights[$child]>0 && $groupids[$child]==$groupid) || $ownerids[$child]==$userid || ($isgrpadmin && $groupids[$child]==$groupid) ||$isadmin) {
			if (!$isadmin) {
				if ($rights[$child]==5 && $groupids[$child]!=$groupid) {
					$rights[$child]=4;  //adjust coloring
				}
			}
			if (isset($ltlibs[$child])) { //library has children
				//echo "<li><input type=button id=\"b$count\" value=\"-\" onClick=\"toggle($count)\"> {$names[$child]}";
				echo "<li class=lihdr><span class=dd>-</span><span class=hdr onClick=\"toggle($child)\"><span class=btn id=\"b$child\">+</span> ";
				echo "</span><input type=checkbox name=\"nchecked[]\" value=$child> <span class=hdr onClick=\"toggle($child)\"><span class=\"r{$rights[$child]}\">{$names[$child]}</span> </span>\n";
				//if ($isadmin) {
				  echo " ({$qcount[$child]}) ";
				//}
				echo "<span class=op>";
				if ($ownerids[$child]==$userid || ($isgrpadmin && $groupids[$child]==$groupid) || $isadmin) {
					echo "<a href=\"managelibs.php?cid=$cid&modify=$child\">Modify</a> | ";
					echo "<a href=\"managelibs.php?cid=$cid&remove=$child\">Delete</a> | ";
					echo "<a href=\"managelibs.php?cid=$cid&transfer=$child\">Transfer</a> | ";
				}
				echo "<a href=\"managelibs.php?cid=$cid&modify=new&parent=$child\">Add Sub</a> ";
				echo "<ul class=hide id=$child>\n";
				echo "</span>";
				$count++;
				printlist($child);
				echo "</ul></li>\n";
				
			} else {  //no children
				
				echo "<li><span class=dd>-</span><input type=checkbox name=\"nchecked[]\" value=$child> <span class=\"r{$rights[$child]}\">{$names[$child]}</span> ";
				//if ($isadmin) {
				  echo " ({$qcount[$child]}) ";
				//}
				echo "<span class=op>";
				if ($ownerids[$child]==$userid || ($isgrpadmin && $groupids[$child]==$groupid) || $isadmin) {
					echo "<a href=\"managelibs.php?cid=$cid&modify=$child\">Modify</a> | ";
					echo "<a href=\"managelibs.php?cid=$cid&remove=$child\">Delete</a> | ";
					echo "<a href=\"managelibs.php?cid=$cid&transfer=$child\">Transfer</a> | ";
				}
				if ($qcount[$child]==0) {
					echo "<a href=\"managelibs.php?cid=$cid&modify=new&parent=$child\">Add Sub</a> ";
				} else {
					echo "<a href=\"reviewlibrary.php?cid=$cid&lib=$child\">Preview</a>";
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

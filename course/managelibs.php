<?php
//IMathAS:  Question library manager
//(c) 2006 David Lippman
	require("../validate.php");
	if ($myrights<20) {
		require("../header.php");
		echo "You need to log in as a teacher to access this page";
		require("../footer.php");
		exit;
	}
	$isadmin = false;
	$isgrpadmin = false;
	if (isset($_GET['cid']) && $_GET['cid']==="admin") {
		if ($myrights <75) {
			require("../header.php");
			echo "You need to log in as an admin to access this page";
			require("../footer.php");
			exit;
		} else if ($myrights < 100) {
			$isgrpadmin = true;
		} else if ($myrights == 100) {
			$isadmin = true;
		}
	} 
	
	$cid = $_GET['cid'];
	
	$now = time();
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
						$query = "DELETE FROM imas_questionset WHERE id IN ($qlist)";
						mysql_query($query) or die("Query failed : " . mysql_error());
						foreach ($qidstofix as $qid) {
							delqimgs($qid);
						}
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
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/managelibs.php?cid=$cid");
			
			exit;
		} else {
			$pagetitle = "Confirm Removal";
			require("../header.php");
			if (!isset($_POST['nchecked'])) {
				echo "No libraries selected.  <a href=\"managelibs.php?cid=$cid\">Go back</a>\n";
				require("../footer.php");
				exit;
			}
			$oktorem = array();
			for ($i=0; $i<count($_POST['nchecked']); $i++) {
				$query = "SELECT count(id) FROM imas_libraries WHERE parent='{$_POST['nchecked'][$i]}'";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				$libcnt= mysql_result($result,0,0);
				if ($libcnt == 0) {
					$oktorem[] = $_POST['nchecked'][$i];
				}
			}
			echo "<h3>Confirm</h3>\n";
			if (count($_POST['nchecked'])>count($oktorem)) {
				echo "<p>Warning:  Some libraries selected have children, and cannot be deleted.</p>\n";
			}
			echo "Are you SURE you want to delete these libraries?\n.";
			echo "<form method=post action=\"managelibs.php?cid=$cid&confirmed=true\">\n";
			$rlist = implode(",",$oktorem);
			echo "<p><input type=radio name=\"delq\" value=\"no\" CHECKED>Move questions in library to Unassigned<br>\n";
			echo "<input type=radio name=\"delq\" value=\"yes\" >Also delete questions in library</p>\n";
			echo "<input type=hidden name=remove value=\"$rlist\">\n";
			echo "<p><input type=submit value=\"Really Delete\">\n";
			echo "<input type=button value=\"Never Mind\" onclick=\"window.location='managelibs.php?cid=$cid'\"></p>\n";
		}
	} else if (isset($_POST['transfer'])) {
		if (isset($_POST['newowner'])) {
			if ($_POST['transfer']!='') {
				$translist = "'".implode("','",explode(',',$_POST['transfer']))."'";
				
				//added for mysql 3.23 compatibility
				$query = "SELECT groupid FROM imas_users WHERE id='{$_POST['newowner']}'";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				$newgpid = mysql_result($result,0,0);
				
				//$query = "UPDATE imas_libraries,imas_users SET imas_libraries.ownerid='{$_POST['newowner']}'";
				//$query .= ",imas_libraries.groupid=imas_users.groupid WHERE imas_libraries.ownerid=imas_users.id AND ";
				//$query .= "imas_libraries.id IN ($translist)";
				$query = "UPDATE imas_libraries SET ownerid='{$_POST['newowner']}',groupid='$newgpid' WHERE imas_libraries.id IN ($translist)";
				
				if (!$isadmin) {
					$query .= " AND groupid='$groupid'";
				}
				if (!$isadmin && !$isgrpadmin) {
					$query .= " AND ownerid='$userid'";
				}
				mysql_query($query) or die("Query failed : $query " . mysql_error());
			}
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/managelibs.php?cid=$cid");
			
			exit;
		} else {
			$pagetitle = "Confirm Transfer";
			require("../header.php");
			if (!isset($_POST['nchecked'])) {
				echo "No libraries selected.  <a href=\"managelibs.php?cid=$cid\">Go back</a>\n";
				require("../footer.php");
				exit;
			}
			echo "<h3>Transfer Ownership</h3>\n";
			echo "<form method=post action=\"managelibs.php?cid=$cid\">\n";
			$tlist = implode(",",$_POST['nchecked']);
			echo "<input type=hidden name=transfer value=\"$tlist\">\n";
			echo "Transfer to: <select name=newowner>\n";
			$query = "SELECT id,FirstName,LastName FROM imas_users WHERE rights>19 ORDER BY LastName,FirstName";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				echo "<option value=\"$row[0]\">$row[2], $row[1]</option>\n";
			}
			echo "</select>\n";
			echo "<p><input type=submit value=\"Transfer\">\n";
			echo "<input type=button value=\"Never Mind\" onclick=\"window.location='managelibs.php?cid=$cid'\"></p>\n";
			echo "</form>\n";
			
		}
	} else if (isset($_POST['setparent'])) {
		if (isset($_POST['libs'])) {
			if ($_POST['libs']!='') {
				$parlist = "'".implode("','",explode(',',$_POST['setparent']))."'";
				$query = "UPDATE imas_libraries SET parent='{$_POST['libs']}',lastmoddate=$now WHERE id IN ($parlist)";
				if (!$isadmin) {
					$query .= " AND groupid='$groupid'";
				}
				if (!$isadmin && !$isgrpadmin) {
					$query .= " AND ownerid='$userid'";
				}
				mysql_query($query) or die("Query failed : $query " . mysql_error());
			}
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/managelibs.php?cid=$cid");
			
			exit;
		} else {
			$pagetitle = "Set Parent";
			require("../header.php");
			if (!isset($_POST['nchecked'])) {
				echo "No libraries selected.  <a href=\"managelibs.php?cid=$cid\">Go back</a>\n";
				require("../footer.php");
				exit;
			}
			echo "<h3>Set Parent</h3>\n";
			echo "<form method=post action=\"managelibs.php?cid=$cid\">\n";
			$tlist = implode(",",$_POST['nchecked']);
			echo "<input type=hidden name=setparent value=\"$tlist\">\n";
						
			echo <<<END
<script>
var curlibs = '';
function libselect() {
	window.open('libtree2.php?cid=$cid&libtree=popup&select=parent&selectrights=1&type=radio&libs='+curlibs,'libtree','width=400,height='+(.7*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420));
}
function setlib(libs) {
	document.getElementById("libs").value = libs;
	curlibs = libs;
}
function setlibnames(libn) {
	document.getElementById("libnames").innerHTML = libn;
}
</script>
END;
			echo "<span class=form>New Parent Library: </span><span class=formright><span id=\"libnames\"></span><input type=hidden name=\"libs\" id=\"libs\"  value=\"$parent\">\n";
			echo "<input type=button value=\"Select Library\" onClick=\"libselect()\"></span><br class=form> ";
			
			echo "<p><input type=submit value=\"Set Parent\">\n";
			echo "<input type=button value=\"Never Mind\" onclick=\"window.location='managelibs.php?cid=$cid'\"></p>\n";
			echo "</form>\n";
			
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
					$query = "DELETE FROM imas_questionset WHERE id IN ($qlist)";
					mysql_query($query) or die("Query failed : " . mysql_error());
					foreach ($qidstofix as $qid) {
						delqimgs($qid);
					}
				} else {
					foreach($qidstofix as $qid) {
						$query = "INSERT INTO imas_library_items (qsetid,libid) VALUES ('$qid',0)";
						mysql_query($query) or die("Query failed : " . mysql_error());
					}
				}
			}
						
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/managelibs.php?cid=$cid");
			
			exit;
		} else {
			$pagetitle = "Remove Library";
			require("../header.php");
			$query = "SELECT count(id) FROM imas_libraries WHERE parent='{$_GET['remove']}'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$libcnt= mysql_result($result,0,0);
			if ($libcnt>0) {
				echo "<h3>Error</h3>\n";
				echo "The library selected has children libraries.  A parent library cannot be removed until all ";
				echo "children libraries are removed.";
				echo "<p><a href=\"managelibs.php?cid=$cid\">Back to Library Manager</a>\n";
			} else {
				echo "<h3>Confirm</h3>\n";
				echo "<form method=post action=\"managelibs.php?cid=$cid&remove={$_GET['remove']}&confirmed=true\">\n";
				echo "Are you SURE you want to delete this Library?";
				echo "<p><input type=radio name=\"delq\" value=\"no\" CHECKED>Move questions in library to Unassigned<br>\n";
				echo "<input type=radio name=\"delq\" value=\"yes\" >Also delete questions in library</p>\n";
				echo "<p><input type=submit value=\"Really Delete\">\n";
				//echo "<p><input type=button onclick=\"window.location='managelibs.php?cid=$cid&remove={$_GET['remove']}&confirmed=true'\" value=\"Really Delete\">\n";
				echo "<input type=button value=\"Never Mind\" onclick=\"window.location='managelibs.php?cid=$cid'\"></p>\n";
			}
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
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/managelibs.php?cid=$cid");
			exit;
		} else {
			$pagetitle = "Transfer Library";
			require("../header.php");
			echo "<h3>Transfer Ownership</h3>\n";
			echo "<form method=post action=\"managelibs.php?cid=$cid&transfer={$_GET['transfer']}\">\n";
			echo "Transfer to: <select name=newowner>\n";
			$query = "SELECT id,FirstName,LastName FROM imas_users WHERE rights>19 ORDER BY LastName,FirstName";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				echo "<option value=\"$row[0]\">$row[2], $row[1]</option>\n";
			}
			echo "</select>\n";
			echo "<p><input type=submit value=\"Transfer\">\n";
			echo "<input type=button value=\"Never Mind\" onclick=\"window.location='managelibs.php?cid=$cid'\"></p>\n";
			echo "</form>\n";
		}
	
	} else if (isset($_GET['modify'])) {
		if (isset($_POST['name']) && trim($_POST['name'])!='') {
			if ($_GET['modify']=="new") {
				$_POST['name'] = str_replace(array(',','\\"','\\\'','~'),"",$_POST['name']);
				$query = "SELECT * FROM imas_libraries WHERE name='{$_POST['name']}' AND parent='{$_POST['libs']}'";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				if (mysql_num_rows($result)>0) {
					require("../header.php");
					echo "Library already exists by that name with this parent.\n";
					echo "<p><a href=\"managelibs.php?cid=$cid&modify=new\">Try Again</a></p>\n";
					require("../footer.php");
					exit;
				} else {
					$mt = microtime();
					$uqid = substr($mt,11).substr($mt,2,6);
					$query = "INSERT INTO imas_libraries (uniqueid,adddate,lastmoddate,name,ownerid,userights,parent,groupid) VALUES ";
					$query .= "($uqid,$now,$now,'{$_POST['name']}','$userid','{$_POST['rights']}','{$_POST['libs']}','$groupid')";
					mysql_query($query) or die("Query failed : " . mysql_error());
				}
			} else {
				$query = "UPDATE imas_libraries SET name='{$_POST['name']}',userights='{$_POST['rights']}',parent='{$_POST['libs']}',lastmoddate=$now ";
				$query .= "WHERE id='{$_GET['modify']}'";
				if (!$isadmin) {
					$query .= " AND groupid='$groupid'";
				}
				if (!$isadmin && !$isgrpadmin) {
					$query .= " AND ownerid='$userid'";
				}
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
			
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/managelibs.php?cid=$cid");
			
			exit;
			
		} else {
			$pagetitle = "Library Settings";
			require("../header.php");
			if ($_GET['modify']!="new") {
				echo "<h3>Modify Library</h3>\n";
				$query = "SELECT name,userights,parent FROM imas_libraries WHERE id='{$_GET['modify']}'";
				if (!$isadmin) {
					$query .= " AND ownerid='$userid'";
				}
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				if ($row = mysql_fetch_row($result)) {
					$name = $row[0];
					$rights = $row[1];
					$parent = $row[2];
				}
			} else {
				echo "<h3>Add Library</h3>\n";
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
			
			
			
			echo "<form method=post action=\"managelibs.php?cid=$cid&modify={$_GET['modify']}\">\n";
			echo "<span class=form>Library Name:</span><span class=formright><input type=text name=\"name\" value=\"$name\" size=20></span><br class=form>\n";
			echo "<span class=form>Rights: </span><span class=formright><select name=\"rights\">\n";
			echo "<option value=\"0\" ";
			if ($rights==0) {echo "SELECTED";}
			echo ">Private</option>\n";
			echo "<option value=\"1\" ";
			if ($rights==1) {echo "SELECTED";}
			echo ">Closed to group, private to others</option>\n";
			echo "<option value=\"2\" ";
			if ($rights==2) {echo "SELECTED";}
			echo ">Open to group, private to others</option>\n";
			if ($isadmin || $isgrpadmin || $allownongrouplibs) {
				echo "<option value=\"4\" ";
				if ($rights==4) {echo "SELECTED";}
				echo ">Closed to all</option>\n";
				echo "<option value=\"5\" ";
				if ($rights==5) {echo "SELECTED";}
				echo ">Open to group, closed to others</option>\n";
				echo "<option value=\"8\" ";
				if ($rights==8) {echo "SELECTED";}
				echo ">Open to all</option>\n";
			}
			
			echo "</select></span><br class=form>\n";
			echo <<<END
<script>
var curlibs = '$parent';
function libselect() {
	window.open('libtree2.php?cid=$cid&libtree=popup&select=parent&selectrights=1&type=radio&libs='+curlibs,'libtree','width=400,height='+(.7*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420));
}
function setlib(libs) {
	document.getElementById("libs").value = libs;
	curlibs = libs;
}
function setlibnames(libn) {
	document.getElementById("libnames").innerHTML = libn;
}
</script>
END;
			if ($parent==0) {
				$lnames = "Root";
			} else {
				$query = "SELECT name FROM imas_libraries WHERE id='$parent'";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				$lnames = mysql_result($result,0,0);
			}
	
			echo "<span class=form>Parent Library:</span><span class=formright><span id=\"libnames\">$lnames</span><input type=hidden name=\"libs\" id=\"libs\"  value=\"$parent\">\n";
			echo "<input type=button value=\"Select Library\" onClick=\"libselect()\"></span><br class=form> ";
			echo "<div class=submit><input type=submit value=\"Update\">\n";
			echo "<input type=button value=\"Never Mind\" onclick=\"window.location='managelibs.php?cid=$cid'\"></div>\n";
			echo "</form>\n";
			echo "<i>Note</i>: Creating a library with rights less restrictive than the parent library will force the parent";
			echo " library to match the rights of the child library";
		}
		
		
	} else {
		$pagetitle = "Library Management";
		$placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/libtree.js\"></script>\n";
		$placeinhead .= "<style type=\"text/css\">\n<!--\n@import url(\"$imasroot/course/libtree.css\");\n-->\n</style>\n";
		require("../header.php");
		echo <<<END
<style type="text/css">
ul {
	border-top: 1px solid #ddd;
}
li {
	border-bottom: 1px solid #ddd;
	padding-top: 5px;
}
</style>

END;
		if ($isadmin || $isgrpadmin) {
			echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"../admin/admin.php\">Admin</a> &gt; Manage Libraries</div>\n";
		} else if ($cid==0){
			echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; Manage Libraries</div>\n";
		} else {
			echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"course.php?cid=$cid\">$coursename</a> &gt; Manage Libraries</div>\n";
		}
		echo "<h3>Library Management <img src=\"$imasroot/img/help.gif\" alt=\"Help\" onClick=\"window.open('$imasroot/help.php?section=managelibraries','help','top=0,width=400,height=500,scrollbars=1,left='+(screen.width-420))\"/></h3>\n";
		if ($isadmin) {
			echo "You are in Admin mode, which means actions will apply to all libraries, regardless of owner";
		} else if ($isgrpadmin) {
			echo "You are in Group Admin mode, which means actions will apply to all libraries from your group, regardless of owner";
		}
		
		echo "<form method=post action=\"managelibs.php?cid=$cid\">\n";
		
		echo "<input type=button value=\"Add New Library\" onclick=\"window.location='managelibs.php?modify=new&cid=$cid'\">\n";
		echo "</form>";
		
		$query = "SELECT imas_libraries.id,imas_libraries.name,imas_libraries.ownerid,imas_libraries.userights,imas_libraries.parent,imas_libraries.groupid,count(imas_library_items.id) AS count ";
		$query .= "FROM imas_libraries LEFT JOIN imas_library_items ON imas_library_items.libid=imas_libraries.id ";
		//$query .= "WHERE imas_libraries.name LIKE '%$search%' ";
		//if (!$isadmin) {
		//	$query .= "WHERE (imas_libraries.ownerid=$userid OR imas_libraries.userights>0) ";	
		//} 
		$query .= "GROUP BY imas_libraries.id";
		
		
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$rights = array();
		
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$id = $line['id'];
			$name = $line['name'];
			$parent = $line['parent'];
			$qcount[$id] = $line['count'];
			$ltlibs[$parent][] = $id;
			$parents[$id] = $parent;
			$names[$id] = $name;
			$rights[$id] = $line['userights'];
			$ownerids[$id] = $line['ownerid'];
			$groupids[$id] = $line['groupid'];
		}
		//if parent has lower userights, up them to match child library
		
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
		foreach ($rights as $k=>$n) {
			setparentrights($k);
		}
		
		//if ($isadmin) {
			function addupchildqs($p) {
				global $qcount,$ltlibs;
				if (isset($ltlibs[$p])) { //if library has children
					foreach ($ltlibs[$p] as $child) {
						$qcount[$p] += addupchildqs($child);
					}
				}
				return $qcount[$p];
			}
			$qcount[0] = addupchildqs(0);
		//}
		
		
		echo "<form method=post action=\"managelibs.php?cid=$cid\">\n";
		echo "<div>";
		echo "Check/Uncheck All: <input type=\"checkbox\" name=\"ca2\" value=\"1\" onClick=\"libchkAll(this.form, 'nchecked[]', this.checked)\">\n";	
		echo "With Selected: <input type=submit name=\"transfer\" value=\"Transfer\">\n";
		echo "<input type=submit name=\"remove\" value=\"Delete\">\n";
		echo "<input type=submit name=\"setparent\" value=\"Change Parent\">\n";
		if (!$isadmin) { echo "(Only applies to your libraries)\n";}
		echo "</div>";
		
		echo "<p>\n";
		echo "Root";
		
		echo "<ul class=base>";
		
			
		//echo "<li><span class=dd>-</span><input type=checkbox name=\"nchecked[]\" value=0> Unassigned</li>\n";
			
		
		$count = 0;
		function printlist($parent) {
			global $names,$ltlibs,$count,$qcount,$cid,$rights,$ownerids,$userid,$isadmin,$groupids,$groupid,$isgrpadmin;
			$arr = $ltlibs[$parent];
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
							echo "<a href=\"managelibs.php?cid=$cid&modify=new&parent=$child\">Add Sub</a> ";
						}
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
						echo "<a href=\"reviewlibrary.php?cid=$cid&lib=$child\">Preview</a>";
						echo "</span>";
						echo "</li>\n";
						
						
					}
				}
			}
		}
		if (isset($ltlibs[0])) {
			printlist(0);
		}
		echo "</ul>";
		echo "</p>\n";
		echo "<p><b>Color Code</b><br/>";
		echo "<span class=r8>Open to all</span><br/>\n";
		echo "<span class=r4>Closed</span><br/>\n";
		echo "<span class=r5>Open to group, closed to others</span><br/>\n";
		echo "<span class=r2>Open to group, private to others</span><br/>\n";
		echo "<span class=r1>Closed to group, private to others</span><br/>\n";
		echo "<span class=r0>Private</span></p>\n";
		
		echo "</form>\n";
		echo "<p></p>\n";
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

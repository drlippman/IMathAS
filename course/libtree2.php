<?php
	require_once("../validate.php");

	if (isset($_GET['libtree']) && $_GET['libtree']=="popup") {
		$isadmin = false;
		$isgrpadmin = false;
		if (isset($_GET['cid']) && $_GET['cid']=="admin") {
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
		echo <<<END
<!DOCTYPE html>
<html>
<head>
<title>IMathAS Library Selection</title>
END;

	if (isset($coursetheme)) {
		$coursetheme = str_replace('_fw','',$coursetheme);
		echo '<link rel="stylesheet" href="'."$imasroot/themes/$coursetheme?v=012810\" type=\"text/css\" />";
	}
	echo <<<END
<link rel="stylesheet" href="$imasroot/course/libtree.css" type="text/css" />
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js" type="text/javascript"></script>
<script type="text/javascript" src="$imasroot/javascript/general.js?v=031111"></script>
<script type="text/javascript" src="$imasroot/javascript/libtree2.js?v=031111"></script>
</head>
<body>
<form id="libselectform">
END;
	}
	echo "<script type=\"text/javascript\">";
	//DB $query = "SELECT imas_libraries.id,imas_libraries.name,imas_libraries.parent,imas_libraries.ownerid,imas_libraries.userights,imas_libraries.sortorder,imas_libraries.groupid,COUNT(imas_library_items.id) AS count ";
	//DB $query .= "FROM imas_libraries LEFT JOIN imas_library_items ON imas_library_items.libid=imas_libraries.id GROUP BY imas_libraries.id";
	$query = "SELECT imas_libraries.id,imas_libraries.name,imas_libraries.parent,imas_libraries.ownerid,imas_libraries.userights,imas_libraries.sortorder,imas_libraries.groupid,COUNT(imas_library_items.id) AS count ";
	$query .= "FROM imas_libraries LEFT JOIN imas_library_items ON imas_library_items.libid=imas_libraries.id GROUP BY imas_libraries.id";
	$stm = $DBH->query($query);
	//$query = "SELECT id,name,parent FROM imas_libraries ORDER BY parent";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());

	if (isset($_GET['select'])) {
		$select = $_GET['select'];
	} else if (!isset($select)) {
		$select = "child";
	}

	echo "var select = '$select';\n";
	if (isset($_GET['type'])) {
		echo "var treebox = '{$_GET['type']}';\n";
	} else {
		echo "var treebox = 'checkbox';\n";
	}

	if (isset($_GET['selectrights'])) {
		$selectrights = $_GET['selectrights'];
	} else {
		$selectrights = 0;
	}
	$allsrights = 2+3*$selectrights;

	$rights = array();
	$sortorder = array();
	//DB while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
	while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
		$id = $line['id'];
		//DB $name = addslashes($line['name']);
		$name = htmlentities($line['name'], ENT_QUOTES);
		$parent = $line['parent'];
		if ($line['count']==0) {
			$isempty[$id] = true;
		}
		$ltlibs[$parent][] = $id;
		$parents[$id] = $parent;
		$names[$id] = $name;
		$rights[$id] = $line['userights'];
		$sortorder[$id] = $line['sortorder'];
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

	if (isset($_GET['libs']) && $_GET['libs']!='') {
		$checked = explode(",",$_GET['libs']);
	} else if (!isset($checked)) {
		$checked = array();
	}
	//echo "//checked: ".implode(',',$checked)."\n";
	if (isset($_GET['locklibs']) && $_GET['locklibs']!='') {
		$locked = explode(",",$_GET['locklibs']);
	} else if (!isset($locked)) {
		$locked = array();
	}
	$checked = array_merge($checked,$locked);
	$toopen = array();

	echo "var tree = {\n";
	echo " 0:[\n";
	$donefirst[0] = 2;
	if ($_GET['type']=="radio" && $select == "child") {
		echo "[0,8,\"Unassigned\",0,";
		if (in_array(0,$checked)) { echo "1";} else {echo "0";}
		echo "]";
	} else {
		echo "[0,8,\"Unassigned\",0,0]";
	}

	if (isset($ltlibs[0])) {
		if (isset($base)) {
			printlist($base);
		} else {
			printlist(0);
		}
	}

	function printlist($parent) {
		global $names,$ltlibs,$checked,$toopen, $select,$isempty,$rights,$sortorder,$ownerids,$isadmin,$selectrights,$allsrights,$published,$userid,$locked,$groupids,$groupid,$isgrpadmin,$donefirst;
		$newchildren = array();
		$arr = array();
		if ($parent==0 && isset($published)) {
			$arr = explode(',',$published);
		} else {
			$arr = $ltlibs[$parent];
		}
		if (count($arr)==0) {return;}
		if ($donefirst[$parent]==0) {
			echo ",\n$parent:[";
			$donefirst[$parent]==1;
		}
		if ($sortorder[$parent]==1) {
			$orderarr = array();
			foreach ($arr as $child) {
				$orderarr[$child] = $names[$child];
			}
			natcasesort($orderarr);
			$arr = array_keys($orderarr);
		}
		foreach ($arr as $child) {
			if ($rights[$child]>$allsrights || (($rights[$child]%3)>$selectrights && $groupids[$child]==$groupid) || $ownerids[$child]==$userid || ($isgrpadmin && $groupids[$child]==$groupid) ||$isadmin) {
			//if ($rights[$child]>$selectrights || $ownerids[$child]==$userid || $isadmin) {
				if (!$isadmin) {
					if ($rights[$child]==5 && $groupids[$child]!=$groupid) {
						$rights[$child]=4;  //adjust coloring
					}
				}
				if ($donefirst[$parent]==2) {echo ",";} else {$donefirst[$parent]=2;}
				echo "[$child,{$rights[$child]},'{$names[$child]}',";
				if (isset($ltlibs[$child])) { //library has children
					if ($select == "parent" || $select=="all") {
						if ($_GET['type']=="radio") {
							if (in_array($child,$locked)) { //removed the following which prevented creation of sublibraries of "open to all" libs.  Not sure why I had that.   || ($select=="parent" && $rights[$child]>2 && !$allownongrouplibs && !$isadmin && !$isgrpadmin)) {
								echo "1,";
							} else {
								echo ",";
							}
							if (in_array($child,$checked)) { echo "1";} //else {echo "0";}
							echo "]";
						} else {
							if (in_array($child,$locked)) {
								echo "1,";
							} else {
								echo ",";
							}
							if (in_array($child,$checked)) { echo "1";} //else {echo "0";}
							echo "]";
						}
					} else {
						echo "-1,]";
					}
					$newchildren[] = $child;
				} else {  //no children
					if ($select == "child" || $select=="all" || $isempty[$child]==true) {
						if ($_GET['type']=="radio") {
							if (in_array($child,$locked)) { // || ($select=="parent" && $rights[$child]>2 && !$allownongrouplibs && !$isadmin && !$isgrpadmin)) {
								echo "1,";
							} else {
								echo ",";
							}
							if (in_array($child,$checked)) { echo "1";} //else {echo "0";}
							echo "]";
						} else {
							if (in_array($child,$locked)) {
								echo "1,";
							} else {
								echo ",";
							}
							if (in_array($child,$checked)) { echo "1";} //else {echo "0";}
							echo "]";
						}
					} else {
						echo "-1,]";
					}

				}
			}
		}
		echo "]";
		foreach ($newchildren as $newchild) {
			$donefirst[$newchild] = false;
			printlist($newchild);
		}
	}

	echo "};";
	echo "</script>";  //end tree definition script
	if (isset($_GET['base'])) {
		$base = $_GET['base'];
	}
	echo "<input type=button value=\"Uncheck all\" onclick=\"uncheckall(this.form)\"/><br>";

	if (isset($base)) {
		echo "<input type=hidden name=\"rootlib\" value=$base>{$names[$base]}</span>";
	} else {
		if ($select == "parent") {
			echo "<input type=radio name=\"libs\" value=0 ";
			if (in_array(0,$checked)) { echo "CHECKED";	}
			echo "> <span id=\"n0\" class=\"r8\">Root</span>";
		} else {
			echo "<span class=\"r8\">Root</span>";
		}
	}
	echo "<div id=tree></div>";
	echo "<script type=\"text/javascript\">\n";
	echo "function initlibtree(showchecks) {";
	echo "showlibtreechecks = showchecks;";
	echo "var tree = document.getElementById(\"tree\");";
	echo "while (tree.childNodes.length>0) { tree.removeChild(tree.firstChild);}";
	echo "tree.appendChild(buildbranch(0)); ";
	echo "if (showchecks) {";
	$expand = array();
	foreach ($checked as $child) {
		if (isset($base)) {
			if ($parents[$child]!=$base) {
				setshow($parents[$child]);
			}
		} else {
			if ($parents[$child]!=0) {
				setshow($parents[$child]);
			}
		}
	}
	function setshow($id) {
		global $parents,$base,$expand;
		array_unshift($expand,$id);
		if (isset($base)) {
			if (isset($parents[$id]) && $parents[$id]!=$base) {
				setshow($parents[$id]);
			}
		} else {
			if (isset($parents[$id]) && $parents[$id]!=0) {
				setshow($parents[$id]);
			}
		}
	}
	$setshowed = array();
	for ($i=0;$i<count($expand);$i++) {
		if (in_array($expand[$i],$setshowed)) {
			continue;
		} else {
			echo "addbranch({$expand[$i]});\n";
			$setshowed[] = $expand[$i];
		}
	}
	echo "}";
	echo "}";
	if (isset($libtreeshowchecks) && $libtreeshowchecks==false) {
		echo "addLoadEvent(function() {initlibtree(false);})";
	} else {
		echo "addLoadEvent(function() {initlibtree(true);})";
	}
	echo "</script>\n";

	$colorcode =  "<p><b>Color Code</b><br/>";
	$colorcode .= "<span class=r8>Open to all</span><br/>\n";
	$colorcode .= "<span class=r4>Closed</span><br/>\n";
	$colorcode .= "<span class=r5>Open to group, closed to others</span><br/>\n";
	$colorcode .= "<span class=r2>Open to group, private to others</span><br/>\n";
	$colorcode .= "<span class=r1>Closed to group, private to others</span><br/>\n";
	$colorcode .= "<span class=r0>Private</span></p>\n";
	if (isset($_GET['libtree']) && $_GET['libtree']=="popup") {
		echo <<<END
<input type=button value="Use Libraries" onClick="setlib(this.form)">
$colorcode
</form>
</body>
</html>
END;
	} else {
		echo $colorcode;
	}
?>

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
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>IMathAS Library Selection</title>
END;
	}
	echo <<<END
<style type="text/css">
<!--
@import url("$imasroot/course/libtree.css");
-->
</style>
<script type="text/javascript" src="$imasroot/javascript/libtree2.js"></script>
END;
	if (isset($_GET['libtree']) && $_GET['libtree']=="popup") {
		echo <<<END
</head>
<body>
<form>
END;
	} 
	echo "<script type=\"text/javascript\">";
	$query = "SELECT imas_libraries.id,imas_libraries.name,imas_libraries.parent,imas_libraries.ownerid,imas_libraries.userights,imas_libraries.groupid,COUNT(imas_library_items.id) AS count ";
	$query .= "FROM imas_libraries LEFT JOIN imas_library_items ON imas_library_items.libid=imas_libraries.id GROUP BY imas_libraries.id";
	//$query = "SELECT id,name,parent FROM imas_libraries ORDER BY parent";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	
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
	while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		$id = $line['id'];
		$name = addslashes($line['name']);
		$parent = $line['parent'];
		if ($line['count']==0) {
			$isempty[$id] = true;
		}
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
		global $names,$ltlibs,$checked,$toopen, $select,$isempty,$rights,$ownerids,$isadmin,$selectrights,$allsrights,$published,$userid,$locked,$groupids,$groupid,$isgrpadmin,$donefirst;
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
							if (in_array($child,$locked) || ($select=="parent" && $rights[$child]>2 && !$allownongrouplibs && !$isadmin && !$isgrpadmin)) { 
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
							if (in_array($child,$locked) || ($select=="parent" && $rights[$child]>2 && !$allownongrouplibs && !$isadmin && !$isgrpadmin)) { 
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
	echo "document.getElementById(\"tree\").appendChild(buildbranch(0)); ";
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
<input type=button value="Use Libaries" onClick="setlib(this.form)">
$colorcode
</form>
</body>
</html>
END;
	} else {
		echo $colorcode;
	}
?>

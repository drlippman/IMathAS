<?php
	require_once("../init.php");

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
		echo '<link rel="stylesheet" href="'."$staticroot/themes/$coursetheme?v=012810\" type=\"text/css\" />";
	}
	echo <<<END
<script type="text/javascript">var imasroot = "$imasroot";var staticroot = "$staticroot";</script>
<link rel="stylesheet" href="$staticroot/course/libtree.css?v=090317" type="text/css" />
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js" type="text/javascript"></script>
<script type="text/javascript" src="$staticroot/javascript/general.js?v=031111"></script>
<script type="text/javascript" src="$staticroot/javascript/libtree2.js?v=041422"></script>
</head>
<body>
<form id="libselectform">
END;
	}
	echo "<script type=\"text/javascript\">";
	$query = "SELECT imas_libraries.id,imas_libraries.name,imas_libraries.parent,imas_libraries.ownerid,imas_libraries.userights,imas_libraries.sortorder,imas_libraries.groupid,imas_libraries.federationlevel,COUNT(imas_library_items.id) AS count ";
	$query .= "FROM imas_libraries LEFT JOIN imas_library_items ON imas_library_items.libid=imas_libraries.id AND imas_library_items.deleted=0 WHERE imas_libraries.deleted=0 ";
	$qarr = array();
	if ($isadmin) {
		//no filter
	} else if ($isgrpadmin) {
		//any group owned library or visible to all
		$query .= "AND (imas_libraries.groupid=:groupid OR imas_libraries.userights>2) ";
		$qarr[':groupid'] = $groupid;
	} else {
		//owned, group
		$query .= "AND ((imas_libraries.ownerid=:userid OR imas_libraries.userights>2) ";
		$query .= "OR (imas_libraries.userights>0 AND imas_libraries.userights<3 AND imas_libraries.groupid=:groupid)) ";
		$qarr[':groupid'] = $groupid;
		$qarr[':userid'] = $userid;
	}
	$query .= " GROUP BY imas_libraries.id";
	$query .= " ORDER BY imas_libraries.federationlevel DESC,imas_libraries.id";
	if (count($qarr)==0) {
		$stm = $DBH->query($query);
	} else {
		$stm = $DBH->prepare($query);
		$stm->execute($qarr);
	}

	//$query = "SELECT id,name,parent FROM imas_libraries ORDER BY parent";

	if (isset($_GET['select'])) {
		$select = $_GET['select'];
	} else if (!isset($select)) {
		$select = "child";
	}

	echo "var select = '" . Sanitize::encodeStringForJavascript($select) . "';\n";
	if (isset($_GET['type'])) {
		echo "var treebox = '" . Sanitize::encodeStringForJavascript($_GET['type']) . "';\n";
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
	$federated = array();
	while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
		$id = $line['id'];
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
		$federated[$id] = ($line['federationlevel']>0);
	}
	//if parent has lower userights, up them to match child library
	function setparentrights($alibid) {
		global $rights,$parents;
		if (!empty($parents[$alibid])) {
			if (!isset($rights[$parents[$alibid]]) || $rights[$parents[$alibid]] < $rights[$alibid]) {
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

	$treearr = array();
	if (isset($_GET['type']) && $_GET['type']=="radio" && $select == "child") {
		$treearr[0] = array(array(0,8,_('Unassigned'),0,in_array(0,$checked)?1:0));
	} else if (isset($_GET['type']) && $_GET['type']=="radio" && $select == "parent") {
		$treearr[0] = array(array(0,8,_('Unassigned'),-1,0,0));
	} else {
		$treearr[0] = array(array(0,8,_('Unassigned'),0,0,0));
	}

	if (isset($ltlibs[0])) {
		if (isset($base)) {
			printlist($base);
		} else {
			printlist(0);
		}
	}

	function printlist($parent) {
		global $treearr,$names,$ltlibs,$checked,$toopen, $select,$isempty,$rights,$sortorder,$ownerids,$isadmin,$selectrights,$allsrights,$published,$userid;
		global $locked,$groupids,$groupid,$isgrpadmin,$federated,$parents;
		$newchildren = array();
		$arr = array();
		if ($parent==0 && isset($published)) {
			$arr = explode(',',$published);
		} else {
			$arr = $ltlibs[$parent];
		}
		if ($parent==0 && $isadmin) {
			$toplevelprivate = array();
			$toplevelgroup = array();
		}
		if (count($arr)==0) {return;}
		if (!isset($treearr[$parent])) {
			$treearr[$parent] = array();
		}
		if (isset($sortorder[$parent]) && $sortorder[$parent]==1) {
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
				$thisjson = array();
				if (!$isadmin) {
					if ($rights[$child]==5 && $groupids[$child]!=$groupid) {
						$rights[$child]=4;  //adjust coloring
					}
				}
				$thisjson[0] = Sanitize::onlyInt($child);
				$thisjson[1] = Sanitize::onlyInt($rights[$child]);
				$thisjson[2] = Sanitize::encodeStringForDisplay($names[$child]);
				if (isset($ltlibs[$child])) { //library has children
					if ($select == "parent" || $select=="all") {
						$thisjson[3] = in_array($child,$locked)?1:0;
						$thisjson[4] = in_array($child,$checked)?1:0;
					} else {
						$thisjson[3] = -1;
						$thisjson[4] = 0;
					}
					$newchildren[] = $child;
				} else { // no children
					if ($select == "child" || $select=="all" || !empty($isempty[$child])) {
						$thisjson[3] = in_array($child,$locked)?1:0;
						$thisjson[4] = in_array($child,$checked)?1:0;
					} else {
						$thisjson[3] = -1;
						$thisjson[4] = 0;
					}
				}
				$thisjson[5] = $federated[$child];
				if ($isadmin && $parent==0 && $rights[$child]<5 && $ownerids[$child]!=$userid && ($rights[$child]==0 || $groupids[$child]!=$groupid)) {
					if ($rights[$child]==0) {
						$toplevelprivate[] = $thisjson;
						$parents[$child] = -2;
					} else {
						$toplevelgroup[] = $thisjson;
						$parents[$child] = -3;
					}
				} else {
					$treearr[$parent][] = $thisjson;
				}
			}
		}
		if ($parent==0 && $isadmin) {
			if (count($toplevelprivate)>0) {
				$treearr[$parent][] = array(-2,0,_('Root Level Private Libraries'),-1,0,0);
				$treearr[-2] = $toplevelprivate;
				$parents[-2] = 0;
			}
			if (count($toplevelprivate)>0) {
				$treearr[$parent][] = array(-3,2,_('Root Level Group Libraries'),-1,0,0);
				$treearr[-3] = $toplevelgroup;
				$parents[-3] = 0;
			}
		}
		foreach ($newchildren as $newchild) {
			printlist($newchild);
		}
	}

	echo "var tree = ".json_encode($treearr, JSON_INVALID_UTF8_IGNORE).";";
	echo "</script>";  //end tree definition script
	if (isset($_GET['base'])) {
		$base = $_GET['base'];
	}
	echo "<input type=button value=\"Uncheck all\" onclick=\"uncheckall(this.form)\"/><br>";

	if (isset($base)) {
		echo "<input type=hidden name=\"rootlib\" value=" . Sanitize::encodeStringForDisplay($base) . ">" . Sanitize::encodeStringForDisplay($names[$base]) . "</span>";
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
			if (!empty($parents[$child])) {
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

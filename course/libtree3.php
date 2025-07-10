<?php
/* Library selection UI for IMathAS
   (c) 2025 David Lippman

   Can be displayed standalone using libtree=popup in URL, or can be included
   in a page for inline display

   Options:
   $_GET['select'] = 'children', 'parents', or 'all' (default children)
   $_GET['mode'] = 'single' or 'multi' (default multi)
   $_GET['selectrights'] = min addrights to limit to. 
      0 for all libs
      1 for libs you can add to
*/

if (isset($_GET['libtree']) && $_GET['libtree']=="popup") {
    require_once "../init.php";
    $isadmin = false;
    $isgrpadmin = false;
    if (isset($_GET['cid']) && $_GET['cid']=="admin") {
        if ($myrights <75) {
            require_once "../header.php";
            echo "You need to log in as an admin to access this page";
            require_once "../footer.php";
            exit;
        } else if ($myrights < 100) {
            $isgrpadmin = true;
        } else if ($myrights == 100) {
            $isadmin = true;
        }
        $cid = 'admin';
    } else if ($myrights<20) {
        exit;
    }
    $placeinhead = '<script src="'.$staticroot.'/javascript/accessibletree.js?v=070625"></script>';
    $placeinhead .= '<link rel="stylesheet" href="'.$staticroot.'/javascript/accessibletree.css?v=070625" type="text/css" />';
    $noskipnavlink = true;
    $hideAllHeaderNav = true;
    $flexwidth = true;
    $nologo = true;
    $pagetitle = _('Library Selection');
    require_once '../header.php';
} 
else if (isset($_GET['getsubs']) && isset($_GET['cid']) && $_GET['cid']=="admin") {
    // eventually maybe get this working for non-admins
    require_once "../init.php";
    if ($myrights < 100) {
        exit;
    }
    $isadmin = true;
    $cid = 'admin';
    $includecounts = !empty($_GET['counts']);
    
    $parent = Sanitize::onlyInt($_GET['getsubs']);
    if ($parent > 0) {
        $query = "SELECT imas_libraries.id,imas_libraries.name,imas_libraries.parent,imas_libraries.ownerid,imas_libraries.userights,imas_libraries.sortorder,imas_libraries.groupid,imas_libraries.federationlevel,COUNT(imas_library_items.id) AS count ";
        $query .= "FROM imas_libraries LEFT JOIN imas_library_items ON imas_library_items.libid=imas_libraries.id AND imas_library_items.deleted=0 WHERE imas_libraries.deleted=0 AND imas_libraries.parent=? ";
        $query .= 'GROUP BY imas_libraries.id ';
        if (!empty($_GET['sortorder'])) {
            $query .= " ORDER BY imas_libraries.name";
        } else {
            $query .= " ORDER BY imas_libraries.id";
        }
        $stm = $DBH->prepare($query);
        $stm->execute([$parent]);
        $out = [];
        $locked = [];
        $checked = [];
        $ids = [];
        while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
            $out[$row['id']] = genItem($row);
            $ids[] = $row['id'];
        }
        // do second query to check for children
        $ph = Sanitize::generateQueryPlaceholders($ids);
        $query = "SELECT a.id,a.sortorder,count(b.id) AS count FROM imas_libraries as a LEFT JOIN ";
        $query .= "imas_libraries AS b ON b.parent=a.id WHERE a.id IN ($ph) GROUP BY a.id ";
        $stm = $DBH->prepare($query);
        $stm->execute($ids);
        while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
            if ($row['count']>0) {
                $out[$row['id']]['childrenUrl'] = "libtree3.php?cid=$cid&getsubs=".$row['id']."&sortorder=".intval($row['sortorder']).($includecounts? '&counts=true':'');
            }
        }
        if (!empty($_GET['sortorder'])) {
            usort($out, function($a,$b) {
                return strncasecmp($a['label'],$b['label'],100);
            });
        } else {
            usort($out, function($a,$b) {
                return strcmp($a['id'],$b['id']);
            });
        }
        echo json_encode($out, JSON_INVALID_UTF8_IGNORE);
    }
    exit;
} else if ($myrights<20) {
    exit;
}

// get settings
if (isset($_GET['select'])) {
    $select = $_GET['select'];
} else if (!isset($select)) {
    $select = "children";
}
if (isset($_GET['mode'])) {
    $mode = $_GET['mode'];
} else {
    $mode = 'multi';
}
if (isset($_GET['selectrights'])) {
    $selectrights = $_GET['selectrights'];
} else {
    $selectrights = 0;
}
$allsrights = 2+3*$selectrights;

$addrootnode = !empty($_GET['addroot']);
if (!empty($_GET['counts'])) {
    $includecludes = true;
} else {
    $includecounts = !empty($includecounts);
}

// get checked/locked
if (isset($_GET['libs']) && $_GET['libs']!='') {
    $checked = explode(",",$_GET['libs']);
} else if (!isset($checked)) {
    $checked = [];
}
if (isset($_GET['locklibs']) && $_GET['locklibs']!='') {
    $locked = explode(",",$_GET['locklibs']);
} else if (!isset($locked)) {
    $locked = [];
}
$checked = array_merge($checked,$locked);

// Get info on ALL libraries we have access to
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

$libdata = [];
$childlibs = [];
while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
    $id = $line['id'];
    $libdata[$id] = $line;
    if (!isset($childlibs[$line['parent']])) {
        $childlibs[$line['parent']] = [];
    }
    $childlibs[$line['parent']][] = $id;
}

//if parent has lower userights, up them to match child library
function setparentrights($alibid) {
    global $rights,$libdata;
    if (!empty($libdata[$alibid]['parent'])) {
        $parent = $libdata[$alibid]['parent'];
        if (!isset($libdata[$parent])) {
            return;
        }
        if ($libdata[$parent]['userights'] < $libdata[$alibid]['userights']) {
            $libdata[$parent]['userights'] = $libdata[$alibid]['userights'];
        }
        setparentrights($parent);
    }
}
foreach ($libdata as $k=>$n) {
    setparentrights($k);
}

function genItem($data) {
    global $isadmin, $isgrpadmin, $userid, $groupid, $cid;
    global $childlibs, $locked, $checked, $showmanagelibslinks, $allsrights, $selectrights, $includecounts;

    $item = [];
    $item['id'] = "lib".$data['id'];
    $item['label'] = $data['name'];
    $rights = $data['userights'];
    if (!$isadmin) {
        if ($rights==5 && $data['groupid']!=$groupid) {
            $rights=4;  //adjust coloring
        }
    }
    $item['userights'] = $rights;
    $item['federated'] = ($data['federationlevel']>0);
    if ($includecounts) {
        $item['count'] = $data['count'];
    }
    if (!empty($showmanagelibslinks) && $data['id']>0) {
        $links = [];
        if ($data['ownerid']==$userid || ($isgrpadmin && $data['groupid']==$groupid) || $isadmin) {
            $links[] = [
                'label'=>_('Modify'), 
                'href'=>'managelibs.php?cid='.$cid.'&modify='.$data['id']
            ];
        }
        if (!empty($childlibs[$data['id']]) || $data['count'] === 0) {
            if (
                $data['userights']>$allsrights || 
                (($data['userights']%3)>$selectrights && $data['groupid']==$groupid) || 
                $data['ownerid']==$userid || 
                ($isgrpadmin && $data['groupid']==$groupid) ||
                $isadmin
            ) {
                $links[] = [
                    'label'=>_('Add Sub'), 
                    'href'=>'managelibs.php?cid='.$cid.'&modify=new&parent='.$data['id']
                ];
            }
        }
        if (count($links)>0) {
            $item['links'] = $links;
        }
    }
    if (in_array($data['id'],$locked)) {
        $item['locked'] = true;
    }
    if (in_array($data['id'],$checked)) {
        $item['selected'] = true;
    }
    return $item;
}

function containsChecked($child) {
    global $checked, $libdata, $childlibs;
    if (in_array($libdata[$child]['id'],$checked)) {
        return true;
    }
    if (!empty($childlibs[$child])) {
        foreach ($childlibs[$child] as $sub) {
            if (containsChecked($sub)) {
                return true;
            }
        }
    }
    return false;
}

// generate json for output
// returns an array of data for the children of the given parent ID
function getChildren($parent) {
    global $childlibs,$libdata,$allsrights,$selectrights,$userid,$groupid,$isadmin,$isgrpadmin;
    global $locked, $checked, $cid, $includecounts;

    $out = [];
    $children = $childlibs[$parent];
    $toplevelprivate = [];
    $toplevelgroup = [];
    if (isset($libdata[$parent]) && $libdata[$parent]['sortorder']==1) {
        $orderarr = array();
        foreach ($children as $child) {
            $orderarr[$child] = $libdata[$child]['name'];
        }
        natcasesort($orderarr);
        $children = array_keys($orderarr);
    }
    foreach ($children as $child) {
        if (
            $libdata[$child]['userights']>$allsrights || 
            (($libdata[$child]['userights']%3)>$selectrights && $libdata[$child]['groupid']==$groupid) || 
            $libdata[$child]['ownerid']==$userid || 
            ($isgrpadmin && $libdata[$child]['groupid']==$groupid) ||
            $isadmin
        ) {
            $item = genItem($libdata[$child]);

            $rights = $libdata[$child]['userights'];
            if (!$isadmin) {
                if ($rights==5 && $libdata[$child]['groupid']!=$groupid) {
                    $rights=4;  //adjust coloring
                }
            }
            
            if ($isadmin && $parent==0 && $rights<5 && 
                $libdata[$child]['ownerid']!=$userid && 
                ($rights==0 || $libdata[$child]['groupid']!=$groupid) &&
                !containsChecked($child)
            ) {
                if (!empty($childlibs[$child])) {
                    $item['childrenUrl'] = "libtree3.php?cid=$cid&getsubs=".$child."&sortorder=".intval($libdata[$child]['sortorder']).($includecounts? '&counts=true':'');
                }
                if ($rights==0) {
                    $toplevelprivate[] = $item;
                } else {
                    $toplevelgroup[] = $item;
                }
            } else {
                if (!empty($childlibs[$child])) {
                    $item['children'] = getChildren($child);
                }
                $out[] = $item;
            }
        }
    }
    if ($isadmin && $parent==0 && count($toplevelprivate)>0) {
        $out[] = [
            'id'=>"librlp",
            'label'=>_('Root Level Private Libraries'),
            'userights'=>0,
            'notselectable'=>true,
            'children' => $toplevelprivate
        ];
    }
    if ($isadmin && $parent==0 && count($toplevelgroup)>0) {
        $out[] = [
            'id'=>"librlg",
            'label'=>_('Root Level Group Libraries'),
            'userights'=>2,
            'notselectable'=>true,
            'children' => $toplevelgroup
        ];
    }
    return $out;
}

$treearr = getChildren(0);
if (!empty($addrootnode)) {
    $root = genItem(['id'=>'0','name'=>_('Root'),'userights'=>8,'federationlevel'=>0]);
    $root['expanded'] = true;
    $root['children'] = $treearr;
    $treearr = [$root];
} else if (empty($hideunassigned)) {
    // add unassigned
    array_unshift($treearr, genItem(['id'=>'0','name'=>_('Unassigned'),'userights'=>0,'federationlevel'=>0, 'ownerid'=>$userid, 'groupid'=>$groupid]));
}
echo '<div id="treecontainer"></div>';
echo '<input type=hidden name=libs id=selected>';
echo "<script>var treedata = ".json_encode($treearr, JSON_INVALID_UTF8_IGNORE).";</script>";
echo '<script>
var selectedLibs = [];
var selectedNames = [];
var treeWidget;
$(function() {
    const container = document.getElementById("treecontainer");
    treeWidget = new AccessibleTreeWidget(container, treedata, {
        selectionMode: "'.Sanitize::encodeStringforDisplay($mode).'",
        selectableItems: "'.Sanitize::encodeStringforDisplay($select).'",
        showCounts: '.(!empty($includecounts) ? 'true' : 'false').',
        onSelectionChange: (selectedIds, selectedLabels) => {
            selectedLibs = selectedIds;
            selectedNames = selectedLabels;
            document.getElementById("selected").value = selectedIds.join(",").replace(/lib/g,"");
        },
        onLoadError: (error, item) => {
            alert(`Failed to load children for "${item.label}": ${error.message}`);
        }
    });
});
function setlib() {
    if (opener) {
        opener.setlib(selectedLibs.join(",").replace(/lib/g,""));
        opener.setlibnames(selectedNames.join(", "));
        self.close();
	} else if (window.parent != window.self) {
		window.parent.setlib(selectedLibs.join(",").replace(/lib/g,""));
		window.parent.setlibnames(selectedNames.join(", "));
		window.parent.GB_hide();
	}
}
</script>';

if (isset($_GET['libtree']) && $_GET['libtree']=="popup") {
    echo '<button type=button onclick="setlib()">'._('Use Libraries').'</button>';
}

$colorcode ="<p><b>Color Code</b></p><ul class=nomark>";
$colorcode .= "<li><span class=r8>Open to all</span></li>";
$colorcode .= "<li><span class=r4>Closed</span></li>";
$colorcode .= "<li><span class=r5>Open to group, closed to others</span></li>";
$colorcode .= "<li><span class=r2>Open to group, private to others</span></li>";
$colorcode .= "<li><span class=r1>Closed to group, private to others</span></li>";
$colorcode .= "<li><span class=r0>Private</span><li></ul>";
echo $colorcode;

if (isset($_GET['libtree']) && $_GET['libtree']=="popup") {
    require_once '../footer.php';
}
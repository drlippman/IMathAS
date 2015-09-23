<?php
use \app\components\AppConstant;

global $names, $ltlibs, $checked, $toopen, $isempty, $rights, $sortorder, $ownerids, $isadmin, $selectrights, $allsrights, $published, $userid, $locked, $groupids, $groupid, $isgrpadmin;
if (isset($params['libtree']) && $params['libtree'] == "popup") {
    $isadmin = false;
    $isgrpadmin = false;
    if (isset($params['cid']) && $params['cid'] == "admin") {
        if ($myRights < AppConstant::GROUP_ADMIN_RIGHT) {

            echo AppConstant::REQUIRED_ADMIN_ACCESS;


        } else if ($myRights < AppConstant::ADMIN_RIGHT) {
            $isgrpadmin = true;
        } else if ($myRights == AppConstant::ADMIN_RIGHT) {
            $isadmin = true;
        }
    }
    echo <<<END
<!DOCTYPE html>
<html>
<head>
<title>IMathAS Library Selection</title>
END;
}
if (isset($coursetheme)) {
    $coursetheme = str_replace('_fw', '', $coursetheme);
    echo '<link rel="stylesheet" href="' . "$imasroot/themes/$coursetheme?v=012810\" type=\"text/css\" />";
}
echo <<<END
END;
if (isset($params['libtree']) && $params['libtree'] == "popup") {
    echo <<<END
</head>
<body>
<form>
END;
}
$query = \app\models\Libraries::getDataForLibTree();
if (isset($params['select'])) {
    $select = $params['select'];
} else if (!isset($select)) {
    $select = "child";
}
if (isset($params['selectrights'])) {
    $selectrights = $params['selectrights'];
} else {
    $selectrights = AppConstant::NUMERIC_ZERO;
}
if (!isset($params['type'])) {
    if (isset($selecttype)) {
        $params['type'] = $selecttype;
    } else {
        $params['type'] = "checkbox";
    }
}
$allsrights = AppConstant::NUMERIC_TWO + AppConstant::NUMERIC_THREE * $selectrights;

$rights = array();
$sortorder = array();
foreach ($query as $line) {
    $id = $line['id'];
    $name = $line['name'];
    $parent = $line['parent'];
    if ($line['count'] == AppConstant::NUMERIC_ZERO) {
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
function setparentrights($alibid)
{
    global $rights, $parents;
    if ($parents[$alibid] > AppConstant::NUMERIC_ZERO) {
        if ($rights[$parents[$alibid]] < $rights[$alibid]) {
            $rights[$parents[$alibid]] = $rights[$alibid];
        }
        setparentrights($parents[$alibid]);
    }
}

foreach ($rights as $k => $n) {
    setparentrights($k);
}

if (isset($params['libs']) && $params['libs'] != '') {
    $checked = explode(",", $params['libs']);
} else if (!isset($checked)) {
    $checked = array();
}
if (isset($params['locklibs']) && $params['locklibs'] != '') {
    $locked = explode(",", $params['locklibs']);
} else if (!isset($locked)) {
    $locked = array();
}
$checked = array_merge($checked, $locked);
$toopen = array();

if (isset($params['base'])) {
    $base = $params['base'];
}
if (isset($base)) {
    echo "<input type=hidden name=\"rootlib\" value=$base>{$names[$base]}</span>";
} else {
    if ($select == "parent") {
        echo "<input type=radio name=\"libs\" value=0 ";
        if (in_array(0, $checked)) {
            echo "CHECKED";
        }
        echo "> <span id=\"n0\" class=\"r8\">Root</span>";
    } else {
        echo "<span class=\"r8\">Root</span>";
    }
}
echo "<ul class=base>";

if ($select == "child") {
    if ($params['type'] == "radio") {
        echo "<li><span class=dd>-</span><input type=radio name=\"libs\" value=0 ";
        if (in_array(AppConstant::NUMERIC_ZERO, $checked)) {
            echo "CHECKED";
        }
        echo "> <span id=\"n0\" class=\"r8\">Unassigned</span></li>\n";
    } else {
        echo "<li><span class=dd>-</span><input type=checkbox name=\"libs[]\" value=0 ";
        echo "> <span id=\"n0\" class=\"r8\">Unassigned</span></li>\n";
    }
} else {
    echo "<li><span class=dd>---</span><span class=\"r8\">Unassigned</span></li>\n";
}

if (isset($ltlibs[0])) {
    if (isset($base)) {
        printlist($base);
    } else {
        printlist(AppConstant::NUMERIC_ZERO);
    }
}
echo "</ul>";
$colorcode = "<p><b>Color Code</b><br/>";
$colorcode .= "<span class=r8>Open to all</span><br/>\n";
$colorcode .= "<span class=r4>Closed</span><br/>\n";
$colorcode .= "<span class=r5>Open to group, closed to others</span><br/>\n";
$colorcode .= "<span class=r2>Open to group, private to others</span><br/>\n";
$colorcode .= "<span class=r1>Closed to group, private to others</span><br/>\n";
$colorcode .= "<span class=r0>Private</span></p>\n";

function printlist($parent)
{
    global $names, $params, $ltlibs, $checked, $toopen, $select, $isempty, $rights, $sortorder, $ownerids, $isadmin, $selectrights, $allsrights, $published, $userid, $locked, $groupids, $groupid, $isgrpadmin;
    $arr = array();
    if ($parent == AppConstant::NUMERIC_ZERO && isset($published)) {
        $arr = explode(',', $published);
    } else {
        $arr = $ltlibs[$parent];
    }
    if (count($arr) == AppConstant::NUMERIC_ZERO) {
        return;
    }
    if ($sortorder[$parent] == AppConstant::NUMERIC_ONE) {
        $orderarr = array();
        foreach ($arr as $child) {
            $orderarr[$child] = $names[$child];
        }
        natcasesort($orderarr);
        $arr = array_keys($orderarr);
    }
    foreach ($arr as $child) {

        if ($rights[$child] > $allsrights || (($rights[$child] % AppConstant::NUMERIC_THREE) > $selectrights && $groupids[$child] == $groupid) || $ownerids[$child] == $userid || ($isgrpadmin && $groupids[$child] == $groupid) || $isadmin) {
            if (!$isadmin) {
                if ($rights[$child] == AppConstant::NUMERIC_FIVE && $groupids[$child] != $groupid) {
                    $rights[$child] = AppConstant::NUMERIC_FOUR;
                }
            }
            if (isset($ltlibs[$child])) {
                echo "<li class=lihdr><span class=dd>-</span><span class=hdr onClick=\"toggle($child)\"><span class=btn id=\"b$child\">+</span> ";
                if ($select == "parent" || $select == "all") {
                    if ($_GET['type'] == "radio") {
                        if (in_array($child, $locked) || ($select == "parent" && $rights[$child] > AppConstant::NUMERIC_TWO && !$allownongrouplibs && !$isadmin && !$isgrpadmin)) {
                            echo "</span><input type=radio disabled=\"disabled\" ";
                        } else {
                            echo "</span><input type=radio name=\"libs\" value=$child ";
                        }
                        if (in_array($child, $checked)) {
                            echo "CHECKED";
                        }
                        echo "><span class=hdr onClick=\"toggle($child)\">";
                    } else {
                        if (in_array($child, $locked)) {
                            echo "</span><input type=checkbox disabled=\"disabled\" ";
                        } else {
                            echo "</span><input type=checkbox name=\"libs[]\" value=$child ";
                        }

                        if (in_array($child, $checked)) {
                            echo "CHECKED";
                        }
                        echo "><span class=hdr onClick=\"toggle($child)\">";
                    }
                }
                echo " <span id=\"n$child\" class=\"r{$rights[$child]}\">{$names[$child]}</span> </span>\n";
                echo "<ul class=hide id=$child>\n";
                printlist($child);
                echo "</ul></li>\n";

            } else {

                if ($select == "child" || $select == "all" || $isempty[$child] == true) {
                    if ($params['type'] == "radio") {
                        if (in_array($child, $locked) || ($select == "parent" && $rights[$child] > AppConstant::NUMERIC_TWO && !$allownongrouplibs && !$isadmin && !$isgrpadmin)) {
                            echo "<li><span class=dd>---</span> <input type=radio disabled=\"disabled\" ";
                        } else {
                            if ($select == "parent") {
                                echo "<li><span class=dd>---</span> <input type=radio name=\"libs\" value=$child ";
                            } else {
                                echo "<li><span class=dd>-</span> <input type=radio name=\"libs\" value=$child ";
                            }
                        }

                        if (in_array($child, $checked)) {
                            echo "CHECKED";
                        }
                        echo "> <span id=\"n$child\" class=\"r{$rights[$child]}\">{$names[$child]}</span></li>\n";
                    } else {
                        if (in_array($child, $locked)) {
                            echo "<li><span class=dd>-</span><input type=checkbox disabled=\"disabled\" ";

                        } else {
                            echo "<li><span class=dd>-</span><input type=checkbox name=\"libs[]\" value=$child ";
                        }
                        if (in_array($child, $checked)) {
                            echo "CHECKED";
                        }
                        echo "> <span id=\"n$child\" class=\"r{$rights[$child]}\">{$names[$child]}</span></li>\n";
                    }
                } else {
                    echo "<li><span class=dd>---</span> <span id=\"n$child\" class=\"r{$rights[$child]}\">{$names[$child]}</span></li>\n";
                }

            }
        }
    }
}

echo "<script type=\"text/javascript\">\n";
foreach ($checked as $child) {
    if (isset($base)) {
        if ($parents[$child] != $base) {
            setshow($parents[$child]);
        }
    } else {
        if ($parents[$child] != AppConstant::NUMERIC_ZERO) {
            setshow($parents[$child]);
        }
    }
}
function setshow($id)
{
    global $parents, $base;
    echo "document.getElementById($id).className = \"show\";";
    echo "document.getElementById('b$id').innerHTML = \"-\";";
    if (isset($base)) {
        if (isset($parents[$id]) && $parents[$id] != $base) {
            setshow($parents[$id]);
        }
    } else {
        if (isset($parents[$id]) && $parents[$id] != AppConstant::NUMERIC_ZERO) {
            setshow($parents[$id]);
        }
    }
}

echo "</script>\n";

if (isset($params['libtree']) && $params['libtree'] == "popup") {
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

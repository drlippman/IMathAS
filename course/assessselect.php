<?php

require_once '../init.php';

if (!isset($teacherid)) {
    echo 'You are not authorized to view this page';
    exit;
}

$aid = intval($_GET['aid'] ?? 0);
if (!empty($_GET['curassess'])) {
    $cur = explode(',', $_GET['curassess']);
} else {
    $cur = [];
}

$qs = 'cid=' . Sanitize::courseId($cid) . '&aid=' . $aid;
if (!empty($_GET['curassess'])) {
    $qs .= '&curassess=' . Sanitize::encodeUrlParam($_GET['curassess']);
}

// course picker for "select from another course"
if (!empty($_GET['pickcourse'])) {
    $stm = $DBH->prepare("SELECT jsondata FROM imas_users WHERE id=?");
    $stm->execute([$userid]);
    $userjson = json_decode($stm->fetchColumn(0), true);

    $stm = $DBH->prepare("SELECT ic.id,ic.name,it.hidefromcourselist FROM imas_courses AS ic JOIN imas_teachers AS it ON it.courseid=ic.id WHERE it.userid=? AND ic.id<>? AND ic.available<4 ORDER BY ic.name");
    $stm->execute([$userid, $cid]);
    $myCourses = [];
    while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
        $myCourses[$row['id']] = $row;
    }
    $printed = [];
    function printCourseOrder($order, $myCourses, &$printed, $qs, $level = 0) {
        foreach ($order as $item) {
            if (is_array($item)) {
                ob_start();
                printCourseOrder($item['courses'] ?? [], $myCourses, $printed, $qs, $level + 1);
                $sub = ob_get_clean();
                if ($sub != '') {
                    echo '<li><b>' . Sanitize::encodeStringForDisplay($item['name']) . '</b><ul class="nomark">' . $sub . '</ul></li>';
                }
            } else if (isset($myCourses[$item]) && !in_array($item, $printed)) {
                printCourseLink($myCourses[$item], $qs);
                $printed[] = $item;
            }
        }
    }
    function printCourseLink($course, $qs) {
        echo '<li><a href="assessselect.php?' . $qs . '&sourcecid=' . Sanitize::onlyInt($course['id']) . '">';
        echo Sanitize::encodeStringForDisplay($course['name']) . '</a></li>';
    }

    $placeinhead = '<script>function setassess() {}</script>'; // footer button is a no-op here
    $pagetitle = _('Select Course');
    $flexwidth = true;
    $nologo = true;
    require_once "../header.php";
    echo '<h2>' . _('Select a course') . '</h2>';
    echo '<ul class="nomark">';
    echo '<li><a href="assessselect.php?' . $qs . '&sourcecid=' . Sanitize::courseId($cid) . '">' . _('This course') . '</a></li>';
    echo '</ul>';
    echo '<p><b>' . _('Other Courses') . '</b></p>';
    echo '<ul class="nomark">';
    $hidden = [];
    if (isset($userjson['courseListOrder']['teach'])) {
        printCourseOrder($userjson['courseListOrder']['teach'], $myCourses, $printed, $qs);
    }
    foreach ($myCourses as $id => $course) {
        if (in_array($id, $printed)) {
            continue;
        }
        if ($course['hidefromcourselist']) {
            $hidden[] = $course;
        } else {
            printCourseLink($course, $qs);
        }
    }
    echo '</ul>';
    if (count($hidden) > 0) {
        echo '<p><b>' . _('Hidden Courses') . '</b></p>';
        echo '<ul class="nomark">';
        foreach ($hidden as $course) {
            printCourseLink($course, $qs);
        }
        echo '</ul>';
    }
    require_once '../footer.php';
    exit;
}

// determine source course
$srccid = $cid;
$srcname = '';
$sourcecid = intval($_GET['sourcecid'] ?? 0);
if ($sourcecid == 0 && count($cur) > 0) {
    // infer the course from the currently selected assessments
    $stm = $DBH->prepare("SELECT courseid FROM imas_assessments WHERE id=?");
    $stm->execute([intval($cur[0])]);
    $sourcecid = intval($stm->fetchColumn(0));
}
if ($sourcecid > 0 && $sourcecid != $cid) {
    $stm = $DBH->prepare("SELECT ic.id,ic.name,ic.itemorder FROM imas_courses AS ic JOIN imas_teachers AS it ON it.courseid=ic.id WHERE ic.id=? AND it.userid=?");
    $stm->execute([$sourcecid, $userid]);
    if ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
        $srccid = $row['id'];
        $srcname = $row['name'];
        $items = unserialize($row['itemorder']);
    }
}

// get assessment list
if ($srcname == '') {
    $stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
    $stm->execute(array(':id'=>$cid));
    $items = unserialize($stm->fetchColumn(0));
}

$itemassoc = array();
$query = "SELECT ii.id AS itemid,ia.id,ia.name,ia.summary FROM imas_items AS ii JOIN imas_assessments AS ia ";
$query .= "ON ii.typeid=ia.id AND ii.itemtype='Assessment' WHERE ii.courseid=:courseid AND ia.id<>:aid";
$stm = $DBH->prepare($query);
$stm->execute(array(':courseid'=>$srccid, ':aid'=>$aid));
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
    $itemassoc[$row['itemid']] = $row;
}

$i=0;
$page_assessmentList = array();
function addtoassessmentlist($items) {
    global $page_assessmentList, $itemassoc, $i;
    foreach ($items as $item) {
        if (is_array($item)) {
            addtoassessmentlist($item['items']);
        } else if (isset($itemassoc[$item])) {
            $page_assessmentList[$i]['id'] = $itemassoc[$item]['id'];
            $page_assessmentList[$i]['name'] = $itemassoc[$item]['name'];
            $itemassoc[$item]['summary'] = strip_tags($itemassoc[$item]['summary']);
            if (strlen($itemassoc[$item]['summary'])>500) {
                $itemassoc[$item]['summary'] = substr($itemassoc[$item]['summary'],0,497).'...';
            }
            $page_assessmentList[$i]['summary'] = $itemassoc[$item]['summary'];
            $i++;
        }
    }
}
addtoassessmentlist($items);

$placeinhead = '<script>
    function uncheckall() {
        $("input[type=checkbox]").prop("checked",false);
    }
    function checkall() {
        $("input[type=checkbox]").prop("checked",true);
    }
    function setassess() {
        var aids = [];
        var aidnames = [];
        $("input[type=checkbox]:checked").each(function(i,el) {
            aids.push(el.value);
            aidnames.push(el.parentNode.nextElementSibling.firstChild.innerText);
        });
        window.parent.setassess(aids.join(","));
		window.parent.setassessnames(aidnames.join(", "));
		window.parent.GB_hide();
    }
</script>
<style> 
.sumtxt {
    display: block;
    margin-left: 10px;
    font-size: 70%;
    max-height: 1.4em;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}
</style>';
$flexwidth = true;
$nologo = true;
$pagetitle = _('Select Assessment');
require_once "../header.php";

echo '<p>'._('Check:') . ' <a href="#" onclick="checkall()">',_('All'),'</a> <a href="#" onclick="uncheckall(); return false">',_('None'),'</a> ';
echo '<button type="button" onclick="window.location.href=\'assessselect.php?' . $qs . '&pickcourse=1\'">',_('Select from another course'),'</button></p>';
if ($srcname != '') {
    echo '<p><b>', sprintf(_('Assessments from course: %s'), Sanitize::encodeStringForDisplay($srcname)), '</b></p>';
}

echo '<table class="gb zebra" style="width:100%;table-layout:fixed;"><thead><tr>';
echo '<th style="width:1.4em"><span class="sr-only">',_('Select'),'</span></th>';
echo '<th>',_('Assessment'),'</th>';
echo '</tr>';
echo '</thead><tbody>';

foreach ($page_assessmentList as $i=>$assess) {
    echo '<tr><td><input type="checkbox" value="'.$assess['id'].'" id="cb'.$assess['id'].'"';
    if (in_array($assess['id'], $cur)) {
        echo 'checked';
    }
    echo '></td>';
    echo '<td><label for="cb'.$assess['id'].'">'.Sanitize::encodeStringForDisplay($assess['name']);
    echo '</label><span class="sumtxt">'.Sanitize::encodeStringForDisplay($assess['summary']).'</span>';
    echo '</td>';
    echo '</tr>';
}
echo '</tbody></table>';

echo '<p><button type="button" onclick="setassess()">',_('Use Assessments'),'</button></p>';

require_once '../footer.php';

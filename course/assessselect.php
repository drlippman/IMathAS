<?php

require('../init.php');

if (!isset($teacherid)) {
    echo 'You are not authorized to view this page';
    exit;
}

$aid = intval($_GET['aid']);
if (!empty($_GET['curassess'])) {
    $cur = explode(',', $_GET['curassess']);
} else {
    $cur = [];
}

// get assessment list 
$stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=:id");
$stm->execute(array(':id'=>$cid));
$items = unserialize($stm->fetchColumn(0));

$itemassoc = array();
$query = "SELECT ii.id AS itemid,ia.id,ia.name,ia.summary FROM imas_items AS ii JOIN imas_assessments AS ia ";
$query .= "ON ii.typeid=ia.id AND ii.itemtype='Assessment' WHERE ii.courseid=:courseid AND ia.id<>:aid";
$stm = $DBH->prepare($query);
$stm->execute(array(':courseid'=>$cid, ':aid'=>$aid));
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
require("../header.php");

echo '<p><button type="button" onclick="uncheckall()">',_('Uncheck All'),'</button> ';
echo '<button type="button" onclick="checkall()">',_('Select All'),'</button></p>';

echo '<table class="gb zebra" style="width:100%;table-layout:fixed;"><thead><tr>';
echo '<th style="width:1.4em"><span class="sr-only">',_('Select'),'</span></th>';
echo '<th>',_('Assessment'),'</th>';
echo '</tr>';
echo '</thead><tbody>';

foreach ($page_assessmentList as $i=>$assess) {
    echo '<tr><td><input type="checkbox" value="'.$assess['id'].'" ';
    if (in_array($assess['id'], $cur)) {
        echo 'checked';
    }
    echo '></td>';
    echo '<td><span>'.Sanitize::encodeStringForDisplay($assess['name']);
    echo '</span><span class="sumtxt">'.Sanitize::encodeStringForDisplay($assess['summary']).'</span>';
    echo '</td>';
    echo '</tr>';
}
echo '</tbody></table>';

echo '<p><button type="button" onclick="setassess()">',_('Use Assessments'),'</button></p>';

require('../footer.php');

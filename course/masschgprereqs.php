<?php 

// Mass set assessment prereqs
// David Lippman

require('../init.php');
require("../includes/htmlutil.php");

if (!isset($teacherid)) {
    echo 'You are not authorized to view this page';
    exit;
}

// Handle postbacks
if (isset($_POST['reqscore'])) {
    $query = 'UPDATE imas_assessments SET reqscoreaid=?,reqscore=?,reqscoretype=? 
        WHERE id=? AND courseid=?';
    $stm = $DBH->prepare($query);

    // reqscoretype is
    //   1 if greyed out, 0 not shown
    //   2 if percent, 0 if pts
    foreach ($_POST['reqscoredisptype'] as $aid=>$reqscoredisptype) {
        $reqscoreaid = $_POST['reqscoreaid'][$aid];
        $ptspercent = $_POST['ptspercent'][$aid];
        $reqscore = $_POST['reqscore'][$aid];
        $reqscoretype = 0;
        if ($reqscoredisptype == 'none') { // no prereq
            $reqscore = 0;
            $reqscoreaid = 0;
        } else {
            if ($reqscoredisptype == 'grey') { // greyed out
                $reqscoretype += 1;
            }
            if ($ptspercent == 'percent') {
                $reqscoretype += 2;
            }
        }
        $stm->execute(array($reqscoreaid, $reqscore, $reqscoretype, $aid, $cid));
    }
    header('Location: '.$basesiteurl.'/course/course.php?cid='.$cid);
    exit;
}

// Data load 
$query = 'SELECT id,name,reqscore,reqscoreaid,reqscoretype FROM imas_assessments 
    WHERE courseid=:courseid ORDER BY name';
$stm = $DBH->prepare($query);
$stm->execute(array(':courseid'=>$cid));
$assessments = array();
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
    if ($row['reqscoreaid']==0) {
        $row['reqscoredisptype'] = 'none'; // no prereq
    } else if ($row['reqscore']<0 || $row['reqscoretype']&1) {
        $row['reqscoredisptype'] = 'grey'; // show greyed
    } else {
        $row['reqscoredisptype'] = 'after'; // Show only after 
    }
    $row['reqscore'] = abs($row['reqscore']);
    if ($row['reqscoretype']&2) {
        $row['ptspercent'] = 'percent'; // percent
    } else {
        $row['ptspercent'] = 'points'; // points
    }
    $assessments[$row['id']] = $row;
}

// get course order 
$stm = $DBH->prepare('SELECT itemorder FROM imas_courses WHERE id=?');
$stm->execute(array($cid));
$itemorder = unserialize($stm->fetchColumn(0));
$stm = $DBH->prepare("SELECT id,typeid FROM imas_items WHERE courseid=? AND itemtype='Assessment'");
$stm->execute(array($cid));
$itemmap = $stm->fetchAll(PDO::FETCH_KEY_PAIR);

function flattenitems($items, &$itemmap, &$assessorder) {
    foreach ($items as $item) {
        if (is_array($item)) { // block
            flattenitems($item['items'], $itemmap, $assessorder);
        } else if (isset($itemmap[$item])) { // is an assessment
            $assessorder[] = $itemmap[$item];
        }
    }
}
$assessorder = array();
flattenitems($itemorder, $itemmap, $assessorder);

$from = Sanitize::simpleString($_GET['from']);

// HTML display

$pagetitle = _('Mass Change Prereqs');
$placeinhead = '<script type="text/javascript" src="'. $staticroot . '/javascript/tablesorter.js"></script>';
require('../header.php');

echo '<div class=breadcrumb>';
echo $breadcrumbbase . '<a href="course.php?cid='.$cid.'">'
    . Sanitize::encodeStringForDisplay($coursename) . '</a> &gt; ';
if ($from == 'chgassessments') {
    echo '<a href="chgassessments.php?cid='.$cid.'">';
} else {
    echo '<a href="chgassessments2.php?cid='.$cid.'">';
}
echo _('Mass Change Assessments').'</a> &gt; ';
echo _('Mass Change Prereqs');
echo '</div>';

echo '<h1>' . _('Mass Change Prereqs') . '</h1>';

echo '<p class="small">' . _('Note: To set the same prereq for multiple assessments at once, or to clear prereqs, it may be faster to use the Mass Change Assessments page.') . '</p>';
echo '<form method=post action="masschgprereqs.php?cid='.$cid.'">';

echo '<table id="myTable" class="gb"><thead><tr>';
echo '<th>' . _('Assessment') . '</th>';
echo '<th>' . _('Prerequisite') . '</th>';
echo '</tr></thead><tbody>';

foreach ($assessorder as $orderkey=>$id) {
    $row = $assessments[$id];

    echo '<tr class="'. (($orderkey%2==0) ? 'even' : 'odd') .'">';
    echo '<td>' . Sanitize::encodeStringForDisplay($row['name']) . '</td>';
    echo '<td>';
    echo '<label for="reqscoredisptype['.$id.']" class="sr-only">';
    echo _('Prerequisite type') . '</label>';
    writeHtmlSelect('reqscoredisptype['.$id.']',
        array('none','after','grey'), 
        array(_('No prerequisite'),_('Show only after'), _('Show greyed until')), 
        $row['reqscoredisptype']);
    echo ' <span id="reqscorewrap'.$id.'" ';
    if ($row['reqscoredisptype'] == 'none') {
        echo 'style="display:none;"';
    }
    echo '>';
    echo '<label>'._('a score of');
    echo ' <input size=4 name="reqscore['.$id.']" value="'.Sanitize::onlyInt($row['reqscore']).'" />';
    echo '</label>';
    echo '<label for="ptspercent['.$id.']" class="sr-only">'; 
    echo _('score type').'</label>';
    writeHtmlSelect('ptspercent['.$id.']', 
        array('points','percent'), 
        array(_('points'), _('percent')),
        $row['ptspercent']);
    echo ' ' . _('is obtained on'). ' ';

    echo '<select name="reqscoreaid['.$id.']" ';
    echo 'aria-label="' . _('prerequisite assessment') . '">';

    $selected = $row['reqscoreaid'];
    if ($selected == 0) { // no current selection
        $selectedkey = max(0, $orderkey - 1);
        $selected = $assessorder[$selectedkey];
    }
    foreach ($assessorder as $reqid) {
        $name = $assessments[$reqid]['name'];
        echo '<option value="'.$reqid.'"  ';
        if ($reqid == $selected) {
            echo 'selected';
        }
        echo '>' . Sanitize::encodeStringForDisplay($name) . '</option>';
    }
    echo '</select>';
    echo '</span>';
    echo '</td>';
    echo '</tr>';
}
echo '</tbody></table>';

echo '<p><button type=submit>'._('Submit').'</button></p>';

echo '</form>';

?>
<script type="text/javascript">
$(function() {
    $('select[id^=reqscoredisptype]').on('change', function() {
        var val = $(this).val();
        if (val == 'none') {
            $(this).next().hide();
        } else {
            $(this).next().show();
        }
    });
});
initSortTable('myTable',Array('S',false),true);

</script>
<?php

require('../footer.php');




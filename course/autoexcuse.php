<?php

require('../init.php');

if (!isset($teacherid)) {
    echo "You need to log in as a teacher to access this page";
    exit;
}

if (empty($_GET['aid'])) {
    echo 'Need assessment id';
    exit;
}

$aid = intval($_GET['aid']);

$stm = $DBH->prepare('SELECT submitby,ver,autoexcuse FROM imas_assessments WHERE id=? AND courseid=?');
$stm->execute(array($aid, $cid));
$row = $stm->fetch(PDO::FETCH_ASSOC);
if ($row === false) {
    echo 'Invalid id';
    exit;
}
if ($row['submitby'] != 'by_assessment' || $row['ver'] < 2) {
    echo 'This is only supported for quiz-style assessments';
    exit;
}

$query = "SELECT iar.userid FROM imas_assessment_records AS iar,imas_students WHERE ";
$query .= "iar.assessmentid=:assessmentid AND iar.userid=imas_students.userid AND imas_students.courseid=:courseid LIMIT 1";
$stm = $DBH->prepare($query);
$stm->execute(array(':assessmentid'=>$aid, ':courseid'=>$cid));
$beentaken = ($stm->rowCount() > 0);

if (isset($_POST['cat']) || isset($_POST['newcat'])) {
    $excusals = array();
    if (isset($_POST['sc'])) {
        foreach ($_POST['sc'] as $k=>$score) {
            if ($score>=0 && $score<=100) {
                $excusals[] = [
                    'sc'=>$score,
                    'cat'=>$_POST['cat'][$k],
                    'aid'=>$_POST['aid'][$k]
                ];
            }
        }
    }
    if (isset($_POST['newsc'])) {
        foreach ($_POST['newsc'] as $k=>$score) {
            if ($score>=0 && $score<=100) {
                $excusals[] = [
                    'sc'=>$score,
                    'cat'=>$_POST['newcat'][$k],
                    'aid'=>$_POST['newaid'][$k]
                ];
            }
        }
    }

    $query = 'UPDATE imas_assessments SET autoexcuse=? WHERE id=? AND courseid=?';
    $stm = $DBH->prepare($query);
    $stm->execute(array(json_encode($excusals), $aid, $cid));

    if ($beentaken) {
        // retotal student assessment records with changes, which will set new excusals
        require_once('../assess2/AssessHelpers.php');
        AssessHelpers::retotalAll($cid, $aid, false);
    }

    header('Location: ' . $GLOBALS['basesiteurl'] . "/course/addquestions.php?cid=$cid&aid=$aid");
    exit;
}

if ($row['autoexcuse'] === '' || $row['autoexcuse'] === null) {
    $excusals = array();
} else {
    $excusals = json_decode($row['autoexcuse'], true);
    if ($excusals === null) {
        $excusals = array();
    }
}

// get all assessment names
$stm = $DBH->prepare('SELECT id,name FROM imas_assessments WHERE courseid=? ORDER BY name');
$stm->execute(array($cid));
$allassess = [];
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
    if ($row['id'] == $aid) { continue; }
    $allassess[$row['id']] = $row['name'];
}

// get outcomes
$stm = $DBH->prepare("SELECT id,name FROM imas_outcomes WHERE courseid=:courseid ORDER by name");
$stm->execute(array(':courseid'=>$cid));
$outcomenames = array();
while ($row = $stm->fetch(PDO::FETCH_NUM)) {
    $outcomenames[$row[0]] = $row[1];
}

// get categories
$categories = [];
$query = "SELECT DISTINCT category FROM imas_questions WHERE assessmentid=:aid";
$stm = $DBH->prepare($query);
$stm->execute(array(':aid'=>$aid));
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
    if (is_numeric($row['category'])) {
        if ($row['category'] == 0) {
            continue;
        }
        //outcome
        $categories[] = [
            'type'=>'O', 
            'cat'=>$row['category'], 
            'name'=>$outcomenames[$row['category']]
        ];
    } else if (strncmp($row['category'],"AID-",4) == 0) {
        $categories[] = [
            'type'=>'A', 
            'cat'=>$row['category'], 
            'name'=>$allassess[substr($row['category'],4)]
        ];
    } else {
        $categories[] = [
            'type'=>'Z', 
            'cat'=>$row['category'], 
            'name'=>$row['category']
        ];
    }
}
usort($categories, function($a,$b) {
    if ($a['type'] == $b['type']) {
        return strcmp($a['name'],$b['name']);
    } else {
        return strcmp($a['type'],$b['type']);
    }
});

// figure out existing excusal cat names
foreach ($excusals as $k=>$exc) {
    if (is_numeric($exc['cat'])) {
        $excusals[$k]['name'] = $outcomenames[$exc['cat']];
    } else if (strncmp($exc['cat'],"AID-",4) == 0) {
        $excusals[$k]['name'] = $allassess[substr($exc['cat'],4)];
    } 
}

$pagetitle = _('Auto Excuse');
require('../header.php');
?>
<script type="text/javascript">
var newcnt = 0;
function delrule(e) {
    e.preventDefault();
    $(this).closest('li').remove();
}
$(function() {
    $('.delrule').on('click', delrule);
    $('#newcat').on('change', function(e) {
        var newcat = $('#newcat').val();
        if (newcat.substr(0,4)=='AID-' && $('#newaid').val()=='') {
            $('#newaid').val(newcat.substr(4));
        }
    });
    $('#addnewrule').on('click', function(e) {
        if ($('#newaid').val()=='') {
            $("#err").text(_('Need to select an assessment'));
            return;
        } else {
            $("#err").empty();
        }
        var newcat = $("#newcat :selected").val();
        var newcatname = $("#newcat :selected").text().replace(/^<?php echo _('category');?>/,'');
        var newaid = $("#newaid :selected").val();
        var newaidname = $("#newaid :selected").text();
        var newsc = $("#newscore").val();
        var li = $('<li>')
            .append($('<input>', {
                type: 'hidden',
                name: 'newcat['+newcnt+']',
                value: newcat
            }))
            .append($('<input>', {
                type: 'hidden',
                name: 'newaid['+newcnt+']',
                value: newaid
            }))
            .append('<?php echo _('A score of').' '; ?>')
            .append($('<input>', {
                type: 'text',
                name: 'newsc['+newcnt+']',
                value: newsc
            }).attr('size',2))
            .append('%');
        if (newcat=='whole') {
            li.append(' <strong><?php echo _('on the whole assessment');?></strong> ');
        } else {
            li.append(' <?php echo _('on category');?> <strong>'+newcatname+'</strong> ');
        }
        li.append(' <?php echo _('will excuse');?> <strong>'+newaidname+'</strong> ');
        li.append($('<a>', {
            href: "#", 
            text: '<?php echo _('Delete Rule');?>'
        }).on('click', delrule));
        $("#rules").append(li);
        $("#newaid").val("");
        newcnt++;
    });
});
</script>
<?php
echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
echo "&gt; <a href=\"addquestions.php?cid=$cid&amp;aid=$aid\">"._('Add/Remove Questions')."</a> ";
echo "&gt; "._('Auto Excuse')."</div>";

echo '<div class="pagetitle"><h1>' . _('Auto Excuse') . '</h1></div>';

echo '<div class="cpmid">';
echo '<a href="categorize.php?aid='.$aid.'&amp;cid='.$cid.'">'._('Categorize Questions').'</a>';
echo '</div>';

echo "<form method=\"post\" action=\"autoexcuse.php?cid=$cid&amp;aid=$aid\" />";
echo '<p>'._('This page allows you to automatically excuse students from other assessments based on their scores on this assessment.');
echo ' '._('This can be based on their overall score, or on their score with question categories.');
echo ' '._('This feature will not work with LMS integration.').'</p>';

if ($beentaken) {
    echo '<p><strong>'._('Note').'</strong>: ';
    echo _('Students have already taken this assessment. Changes to rules will apply retroactively, but only to excuse new assessments; existing excused assignments will not be un-excused.');
    echo '</p>';
}

echo '<h2>'._('Rules').':</h2>';

echo '<ul id="rules">';

foreach ($excusals as $k=>$exc) {
    echo '<li>';
    echo '<input type="hidden" name="cat['.$k.']" 
        value="'.Sanitize::encodeStringForDisplay($exc['cat']).'">';
    echo '<input type="hidden" name="aid['.$k.']" 
        value="'.Sanitize::encodeStringForDisplay($exc['aid']).'">';
    echo '<label>'._('A score of').' <input type=text name="sc['.$k.']" 
        size=2 value="'.Sanitize::onlyInt($exc['sc']).'">% </label> ';
    if ($exc['cat'] == 'whole') {
        echo _('on the whole assessment');
    } else {
        echo _('on category').' <strong>';
        if (isset($exc['name'])) {
            echo Sanitize::encodeStringForDisplay($exc['name']);
        } else {
            echo Sanitize::encodeStringForDisplay($exc['cat']);
        }
        echo '</strong>';
    }
    echo ' '._('will excuse').' <strong>';
    echo Sanitize::encodeStringForDisplay($allassess[$exc['aid']]);
    echo '</strong> <a href="#" class="delrule">'._('Delete Rule').'</a>';
    echo '</li>';
}
echo '</ul>';

echo '<h2>'._('Add New Rule').'</h2>';
echo '<p>'._('A score of').' <input type=text id=newscore size=2 value=75 />%';
echo '<br>'._('on').' <select id=newcat>';
echo ' <option value="whole">'._('the whole assessment').'</option>';
foreach ($categories as $cat) {
    echo '<option value="'.Sanitize::encodeStringForDisplay($cat['cat']).'">';
    echo _('category').' '.Sanitize::encodeStringForDisplay($cat['name']);
    echo '</option>';
}
echo '</select>';
echo '<br>'._('will excuse') . ' <select id=newaid>';
echo '<option value="">'._('Select...').'</option>';
foreach ($allassess as $aid=>$name) {
    echo '<option value="'.$aid.'">'.Sanitize::encodeStringForDisplay($name).'</option>';
}
echo '</select>';
echo '</p>';
echo '<p><button type="button" id="addnewrule">'._('Add Rule').'</button></p>';
echo '<p class="noticetext" id="err"></p>';
echo '<p><button type="submit">'._('Save Changes').'</button></p>';
echo '</form>';

require('../footer.php');



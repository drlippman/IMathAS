<?php

require_once "../init.php";
if ($myrights<20) {exit;}

$isadmin = (isset($_GET['cid']) && $_GET['cid']=='admin' && $myrights==100);
$qcid = ($isadmin ? 'admin' : 0);

$sort = $_GET['sort'] ?? 'timesused';

if (!empty($_POST['checked'])) {
    $data = array_map('intval', $_POST['checked']);
    $ph = Sanitize::generateQueryPlaceholders($data);
    
    $query = "DELETE FROM imas_questionerrorlog WHERE qsetid IN ($ph)";
    if (!$isadmin) {
        $data[] = $userid;
        $query .= " AND ownerid=?";
    }
    $stm = $DBH->prepare($query);
    $stm->execute($data);
    echo "saved";
    exit;
}

if ($isadmin) {
    if (!empty($_GET['limited'])) {
        if (isset($CFG['GEN']['qerroronold'])) {
            $old = time() - 60*60*24*$CFG['GEN']['qerroronold'][0];
        } else {
            $old = time() - 60*60*24*30;
        }
        $query = 'SELECT iqe.* FROM imas_questionerrorlog AS iqe
            JOIN imas_questionset AS iqs ON iqe.qsetid=iqs.id
            JOIN imas_users AS iu ON iqs.ownerid=iu.id
            WHERE iqs.userights>0 AND iu.lastaccess<'.$old.'
            ORDER BY iqe.qsetid';
    } else if (!empty($_GET['public'])) {
        $query = 'SELECT iqe.* FROM imas_questionerrorlog AS iqe
            JOIN imas_questionset AS iqs ON iqe.qsetid=iqs.id
            WHERE iqs.userights>0 ORDER BY iqe.qsetid';
    } else {
        $query = 'SELECT * FROM imas_questionerrorlog ORDER BY qsetid';
    }

    $stm = $DBH->query($query);
} else {
    $query = 'SELECT * FROM imas_questionerrorlog WHERE ownerid=? ORDER BY qsetid';

    $stm = $DBH->prepare($query);
    $stm->execute([$userid]);
}

$allrows = [];
$allids = [];
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
    if (!isset($allrows[$row['qsetid']])) {
        $allrows[$row['qsetid']] = [];
    }
    $allrows[$row['qsetid']][] = $row;
}
foreach ($allrows as $k=>$v) {
    usort($allrows[$k], function($a,$b) {
        return $b['etime'] <=> $a['etime'];
    });
}
$timesused = [];
$allids = array_keys($allrows);
if (count($allids)>0) {
    $allqids = implode(',', array_map('intval', array_unique($allids)));
    $stm = $DBH->query("SELECT questionsetid,COUNT(id) FROM imas_questions WHERE questionsetid IN ($allqids) GROUP BY questionsetid");
    $timesused = [];
    while ($row = $stm->fetch(PDO::FETCH_NUM)) {
        $timesused[$row[0]] = $row[1];
    }
}
if ($sort === 'recent') {
    $qorder = array_keys($allrows);
    usort($qorder, function($a,$b) use ($allrows) {
        return $allrows[$b][0]['etime'] <=> $allrows[$a][0]['etime'];
    });
} else {
    arsort($timesused);
    $qorder = array_keys($timesused);
    $qorder = array_merge($qorder, array_diff($allids,$qorder));
}

$placeinhead = '<style type="text/css"> 
.fixedbottomright {position: fixed; right: 10px; bottom: 10px; z-index:10;}
.fixedonscroll[data-fixed=true] {
	z-index: 50;
	margin-top: -5px;
	padding: 5px 0px;;
	background-color: #fff;
	width: 100%;
}
</style>
<script type="text/javascript">
function quicksave() {
    if ($("input:checked").length == 0) { return; }
	$("#quicksavenotice").html(_("Saving...") + \' <img src="\'+staticroot+\'/img/updating.gif" alt=\"\"/>\');
	$.ajax({
		url: window.location.href,
		type: "POST",
		data: $("#mainform").serialize()
	}).done(function(msg) {
		if (msg=="saved") {
            $("input:checked").closest("li").slideUp();
			$("#quicksavenotice").html(_("Saved"));
			setTimeout(function() {$("#quicksavenotice").html("&nbsp;");}, 2000);
		} else {
			$("#quicksavenotice").html(msg);
		}
	}).fail(function(jqXHR, textStatus) {
		$("#quicksavenotice").html(textStatus);
	});
}
</script>';
$pagetitle = _('Question Errors');
require_once '../header.php';

echo '<div class=breadcrumb><a href="../index.php">'._('Home').'</a> &gt; '._('Question Errors').'</div>';
echo '<h2>'._('Question Errors').'</h2>';

echo '<p>'._('The questions listed below have logged an error. Some error may occur on display, some on scoring, and some may only occur on invalid student inputs. ');
echo _('Click the Seed to test that particular version of the question. Click the question number to edit the question. ');
echo _('Once you have fixed the issue or determined it does not need fixing, clear the log entry. ');
echo '</p>';
echo '<p>'._('Sort by: ');
if ($sort === 'recent') {
    echo ' <strong>'._('Most Recent Error').'</strong> | <a href="questionerrors.php?cid='.$qcid.'&sort=timesused">'._('Most Used Questions').'</a>';
} else {
    echo ' <a href="questionerrors.php?cid='.$qcid.'&sort=recent">'._('Most Recent Error').'</a> | <strong>'._('Most Used Questions').'</strong>';
}
echo '</p>';
if (isset($CFG['hooks']['util/questionerrors'])) {
	require_once $CFG['hooks']['util/questionerrors'];
}

if ($isadmin) {
    if (!empty($_GET['public'])) {
        echo '<p>All public questions</p>';
    } else {
        echo '<p>All questions</p>';
    }
}
echo '<form id=mainform method=post>';
//echo '<p>'._('With selected:').'<button type=submit>'._('Clear error').'</button></p>';
echo '<div class="fixedonscroll">';
echo _('With selected:') . ' <button type="button" id="quicksavebtn" onclick="quicksave()">'._('Clear log').'</button> ';
echo '<span class="noticetext" id="quicksavenotice" aria-live="polite" aria-atomic=true>&nbsp;</span>';
echo '</div>';
echo '<div class="fixedonscrollpad"></div>';
echo '<ul class="nomark">';
$lastqsetid = 0;
foreach ($qorder as $qsetid) {
    echo '<li><label><input type=checkbox name="checked[]" value="'.$qsetid.'"> ';
    echo 'Question <a target="_blank" href="../course/moddataset.php?cid='.$qcid.'&id='.$qsetid.'">#'.$qsetid.'</a></label>';
    echo ' <span class="small grey">(Used '.($timesused[$qsetid] ?? 0).' times)</span>';
    echo '<ul>';
    foreach ($allrows[$qsetid] as $row) {
        echo '<li><a target="_blank" href="../course/testquestion2.php?cid=0&qsetid='.$qsetid.'&seed='.intval($row['seed']).'">';
        echo 'Seed '.intval($row['seed']).' <span class=small>('.date('n/j/Y', $row['etime']).')</span></a>';
        echo ': ' . Sanitize::encodeStringForDisplay($row['error']).'</li>';
    }
    echo '</ul></li>';
}
echo '</ul>';
echo '</form>';

require_once '../footer.php';




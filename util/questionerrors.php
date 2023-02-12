<?php

require("../init.php");
if ($myrights<20) {exit;}

$isadmin = (isset($_GET['cid']) && $_GET['cid']=='admin' && $myrights==100);
$qcid = ($isadmin ? 'admin' : 0);

if (!empty($_POST['checked'])) {
    $data = array_map('intval', $_POST['checked']);
    $ph = Sanitize::generateQueryPlaceholders($data);
    
    $query = "DELETE imas_questionerrors FROM imas_questionerrors 
        JOIN imas_questionset ON imas_questionerrors.qsetid=imas_questionset.id
        WHERE imas_questionerrors.qsetid IN ($ph)";
    if (!$isadmin) {
        $data[] = $userid;
        $query .= " AND imas_questionset.ownerid=?";
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
        $query = 'SELECT iqe.*,count(iqe.seed) as errcnt FROM imas_questionerrors AS iqe
            JOIN imas_questionset AS iqs ON iqe.qsetid=iqs.id
            JOIN imas_users AS iu ON iqs.ownerid=iu.id
            WHERE iqs.userights>0 AND iu.lastaccess<'.$old.'
            GROUP BY iqe.qsetid,iqe.error ORDER BY iqe.qsetid';
    } else if (!empty($_GET['public'])) {
        $query = 'SELECT iqe.*,count(iqe.seed) as errcnt FROM imas_questionerrors AS iqe
            JOIN imas_questionset AS iqs ON iqe.qsetid=iqs.id
            WHERE iqs.userights>0 GROUP BY iqe.qsetid,iqe.error ORDER BY iqe.qsetid';
    } else {
        $query = 'SELECT *,count(seed) AS errcnt FROM imas_questionerrors GROUP BY qsetid,error ORDER BY qsetid';
    }

    $stm = $DBH->query($query);
} else {
    $query = 'SELECT iqe.*,count(iqe.seed) as errcnt FROM imas_questionerrors AS iqe
        JOIN imas_questionset AS iqs ON iqe.qsetid=iqs.id
        WHERE iqs.ownerid=? GROUP BY iqe.qsetid,iqe.error ORDER BY iqe.qsetid';

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
$timesused = [];
$allids = array_keys($allrows);
if (count($allids)>0) {
    $allqids = implode(',', array_unique($allids));
    $stm = $DBH->query("SELECT questionsetid,COUNT(id) FROM imas_questions WHERE questionsetid IN ($allqids) GROUP BY questionsetid");
    $timesused = [];
    while ($row = $stm->fetch(PDO::FETCH_NUM)) {
        $timesused[$row[0]] = $row[1];
    }
}
arsort($timesused);
$qorder = array_keys($timesused);
$qorder = array_merge($qorder, array_diff($allids,$qorder));

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
	$("#quicksavenotice").html(_("Saving...") + \' <img src="\'+staticroot+\'/img/updating.gif"/>\');
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
require('../header.php');

echo '<div class=breadcrumb><a href="../index.php">'._('Home').'</a> &gt; '._('Question Errors').'</div>';
echo '<h2>'._('Question Errors').'</h2>';

echo '<p>'._('The questions listed below have logged an error. Some error may occur on display, some on scoring, and some may only occur on invalid student inputs. ');
echo _('Click the Seed to test that particular version of the question. Click the question number to edit the question. ');
echo _('Once you have fixed the issue or determined it does not need fixing, clear the log entry. ');
echo '</p>';
if (isset($CFG['hooks']['util/questionerrors'])) {
	require($CFG['hooks']['util/questionerrors']);
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
echo 'With selected: <button type="button" id="quicksavebtn" onclick="quicksave()">'._('Clear log').'</button> ';
echo '<span class="noticetext" id="quicksavenotice">&nbsp;</span>';
echo '</div>';
echo '<ul class="nomark">';
$lastqsetid = 0;
foreach ($qorder as $qsetid) {
    echo '<li><input type=checkbox name="checked[]" value="'.$qsetid.'"> ';
    echo 'Question <a target="_blank" href="../course/moddataset.php?cid='.$qcid.'&id='.$qsetid.'">#'.$qsetid.'</a>';
    echo ' <span class="small grey">(Used '.($timesused[$qsetid] ?? 0).' times)</span>';
    echo '<ul>';
    foreach ($allrows[$qsetid] as $row) {
        echo '<li><a target="_blank" href="../course/testquestion2.php?cid=0&qsetid='.$qsetid.'&seed='.intval($row['seed']).'">';
        echo 'Seed '.intval($row['seed']).'</a>';
        if ($row['errcnt'] > 1) {
            echo '<span class="small grey">(and '.($row['errcnt']-1).' other)</span>';
        }
        echo ': ' . Sanitize::encodeStringForDisplay($row['error']).'</li>';
    }
    echo '</ul></li>';
}
echo '</ul>';
echo '</form>';

require('../footer.php');




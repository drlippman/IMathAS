<?php

require_once "../init.php";

$cid = Sanitize::courseId($_GET['cid']);
$errmsg = '';
$pagetitle = _('Cleanup messages');
require '../header.php';

echo "<div class=breadcrumb>$breadcrumbbase ";
if ($cid>0 && (!isset($_SESSION['ltiitemtype']) || $_SESSION['ltiitemtype']!=0)) {
    echo "<a href=\"../course/course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
}
echo " <a href=\"msglist.php?cid=$cid\">" . _('Message List') . "</a> &gt; ";
echo _('Cleanup messages') . '</div>';

echo '<h1>' . _('Cleanup messages') . '</h1>';

if (isset($_POST['deltype']) && is_numeric($_POST['delold']) && 
    ((isset($_POST['onlynotenrolled']) && floatval($_POST['delold']) >= 0) || 
        floatval($_POST['delold']) > 0
    )
) {
    // process
    $deltype = Sanitize::simpleString($_POST['deltype']);
    $delold = time() - (floatval($_POST['delold']) * 365 * 24 * 60 * 60); // convert to seconds
    $onlynotenrolled = !empty($_POST['onlynotenrolled']);
    $cnt = 0;

    $enrolledstusubquery = '(SELECT istu.userid FROM imas_students AS istu JOIN imas_teachers AS iteach ON istu.courseid=iteach.courseid WHERE iteach.userid=:me)';

    if ($deltype == 'r' || $deltype == 'rs') {
        $qarr = [':to' => $userid, ':old' => $delold];
        if ($onlynotenrolled) {
            $qarr[':me'] = $userid;
        }
        // deleted: 0 not deleted, 1 deleted by sender, 2 deleted by reader
        // deleting received.  Delete if deleted=1; set deleted=2 if deleted=0
        $query = 'UPDATE imas_msgs SET deleted=2 WHERE deleted=0 AND msgto=:to AND senddate<:old';
        if ($onlynotenrolled) {
            $query .= ' AND msgfrom NOT IN ' . $enrolledstusubquery;
        }
        $stm = $DBH->prepare($query);
        $stm->execute($qarr);
        $cnt += $stm->rowCount();

        $query = 'DELETE FROM imas_msgs WHERE deleted=1 AND msgto=:to AND senddate<:old';
        if ($onlynotenrolled) {
            $query .= ' AND msgfrom NOT IN ' . $enrolledstusubquery;
        }
        $stm = $DBH->prepare($query);
        $stm->execute($qarr);
        $cnt += $stm->rowCount();
    }
    if ($deltype == 's' || $deltype == 'rs') {
        // deleted: 0 not deleted, 1 deleted by sender, 2 deleted by reader
        // deleting sent.  Delete if deleted=2; set deleted=1 if deleted=0
        $qarr = [':from' => $userid, ':old' => $delold];
        if ($onlynotenrolled) {
            $qarr[':me'] = $userid;
        }
        $query = 'UPDATE imas_msgs SET deleted=1 WHERE deleted=0 AND msgfrom=:from AND senddate<:old';
        if ($onlynotenrolled) {
            $query .= ' AND msgto NOT IN ' . $enrolledstusubquery;
        }
        $stm = $DBH->prepare($query);
        $stm->execute($qarr);
        $cnt += $stm->rowCount();

        $query = 'DELETE FROM imas_msgs WHERE deleted=2 AND msgfrom=:from AND senddate<:old';
        if ($onlynotenrolled) {
            $query .= ' AND msgto NOT IN ' . $enrolledstusubquery;
        }
        $stm = $DBH->prepare($query);
        $stm->execute($qarr);
        $cnt += $stm->rowCount();
    }

    echo '<p>' . sprintf(_('%d messages deleted'), $cnt) . '</p>';
    echo '<p><a href="msglist.php?cid=' . $cid .'">' . _('Done') . '</a></p>';
    require '../footer.php';
    exit;
} else {
    if (!empty($_POST)) {
        if (empty($_POST['deltype'])) {
            $errmsg .= _('Select what kind of message to delete.').' ';
        }
        if (!is_numeric($_POST['delold']) || $_POST['delold'] < 0) {
            $errmsg .= _('Enter a positive number for number of years old.').' ';
        }
    }
    if ($errmsg != '') {
        echo '<p class=red>'._('Error:').' '.$errmsg.'</p>';
    }
}



echo '<p>' . _('This page will help you delete old messages') . '</p>';
echo '<form method=post>';
echo '<p>' . _('What kind of messages do you want to delete?');
echo '<ul class=nomark><li><label><input type=radio name=deltype value="r"> ' . _('Messages I have received only') .'</label></li>';
echo '<li><label><input type=radio name=deltype value="s"> ' . _('Messages I have sent only') .'</label></li>';
echo '<li><label><input type=radio name=deltype value="rs"> ' . _('Messages I have received or sent') .'</label></li>';
echo '</ul></p>';

echo '<p><span id="yr1">' . _('Delete messages older than:');
echo '</span> <input type=text size=2 name=delold aria-labelledby="yr1 yr2"> <span id="yr2">' . _('years old') . '</span></p>';

if ($myrights > 10) {
    echo '<p><label><input type=checkbox name=onlynotenrolled value=1> ' . _('Only delete messages from or to students no longer enrolled in my classes') . '</label></p>';
}

echo '<button type=submit id=submitbtn>' . _('Delete Messages') . '</button>';
echo '<script>
$(function() {
  $("#submitbtn").on("click", function() {
    return confirm("'._('Are you SURE you want to delete these messages? This cannot be undone.').'");
  });
});
</script>';

echo '</form>';

require '../footer.php';

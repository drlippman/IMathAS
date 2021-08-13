<?php

require("../init.php");

if ($myrights<100 && ($myspecialrights&64)!=64) {exit;}

if (!empty($_POST['todel'])) {
    $todel = array_unique(array_map('intval', $_POST['todel']));
    $todellist = implode(',', $todel);

    $reqdata['actions'][] = array(
		'by'=>$userid,
		'on'=>time(),
		'status'=>10);

    // update reqdata with deny
    $upd = $DBH->prepare("UPDATE imas_instr_acct_reqs SET reqdata=?,status=10 WHERE userid=?");
    $stm = $DBH->query("SELECT userid,reqdata FROM imas_instr_acct_reqs WHERE userid IN ($todellist)");
    while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
        $reqdata = json_decode($row['reqdata'], true);
        if (!isset($reqdata['actions'])) {
            $reqdata['actions'] = array();
        }
        $reqdata['actions'][] = array(
            'by'=>$userid,
            'on'=>time(),
            'status'=>10);
        $upd->execute(array(json_encode($reqdata), $row['userid']));
    }

    // unenroll from any instructor-enroll courses
    if (isset($CFG['GEN']['enrollonnewinstructor'])) {
        require("../includes/unenroll.php");
        foreach ($CFG['GEN']['enrollonnewinstructor'] as $rcid) {
            unenrollstu($rcid, $todel);
        }
    }

    // demote to student account
    $DBH->query("UPDATE imas_users SET rights=10 WHERE id IN ($todellist)");

    header('Location: ' . $GLOBALS['basesiteurl'] . "/util/finddupacctreq.php");
    exit;
}

$query = 'SELECT a.id,a.SID,a.FirstName,a.LastName,a.email,a.rights,imas_instr_acct_reqs.status,imas_instr_acct_reqs.reqdata,
  b.SID as extSID, b.FirstName as extFirst,b.LastName as extLast,b.email as extemail,imas_groups.name 
  FROM imas_users AS a 
  JOIN imas_instr_acct_reqs ON a.id=imas_instr_acct_reqs.userid AND imas_instr_acct_reqs.status<10 
  JOIN imas_users AS b ON ((a.LastName=b.LastName AND a.FirstName = b.FirstName) OR (a.email=b.email)) 
    AND b.rights>19
  JOIN imas_groups ON imas_groups.id=b.groupid ORDER BY a.id';

$stm = $DBH->query($query);

// prepare query for requests that have already been upgraded to teacher to mark as approved
$upd = $DBH->prepare("UPDATE imas_instr_acct_reqs SET status=11 WHERE userid=?");

$status = [
    0 => _('New Request'),
    1 => _('Needs Investigation'),
    2 => _('Waiting for Confirmation'),
    3 => _('Probably should be Denied'),
    4 => _('Requested More Info')
];

$placeinhead = '<style>tbody tr:nth-child(odd) {background-color: #eee;}</style>';
require('../header.php');
echo '<h1>'._('Duplicate Pending Account Requests').'</h1>';
echo '<form method="POST" action="finddupacctreq.php">';

echo '<table class="gb"><thead><tr>';
echo '<th colspan=6 style="border-right:2px solid">'._('Pending User').'</th>';
echo '<th colspan=4>'._('Existing Teacher').'</th>';
echo '</tr><tr><th>'._('Delete').'</th>';
echo '<th>'._('Name').'</th>';
echo '<th>'._('Username').'</th>';
echo '<th>'._('Email').'</th>';
echo '<th>'._('School').'</th>';
echo '<th>'._('Status').'</th>';
echo '<th>'._('Name').'</th>';
echo '<th>'._('Username').'</th>';
echo '<th>'._('Email').'</th>';
echo '<th>'._('Group').'</th></tr></thead>';
echo '<tbody>';
$listedusers = [];
$fixedcnt = 0;
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
    if ($row['rights']>19) { // already upgraded to teacher
        $upd->execute(array($row['id']));
        $fixedcnt++;
        continue;
    }
    echo '<tr>';
    if (!isset($listedusers[$row['id']])) {
        echo '<td><input type=checkbox name="todel[]" value="'.$row['id'].'"></td>';
        $listedusers[$row['id']] = 1;
    } else {
        echo '<td></td>';
    }
    echo '<td><span class="pii-full-name">'.Sanitize::encodeStringForDisplay($row['LastName'].', '.$row['FirstName']).'</span></td>';
    echo '<td><span class="pii-username">'.Sanitize::encodeStringForDisplay($row['SID']).'</span></td>';
    echo '<td><span class="pii-email">'.Sanitize::encodeStringForDisplay($row['email']).'</span></td>';
    $userdata = json_decode($row['reqdata'], true);
    if (isset($userdata['ipeds'])) {
        // handle requests with ipeds info 
        if (strpos($userdata['ipeds'],'-') !== false) {
            list($ipedstype,$ipedsval) = explode('-', $userdata['ipeds']);
            $query = "SELECT IF(ip.type='A',ip.agency,ip.school) AS schoolname
                FROM imas_ipeds AS ip 
                WHERE ip.type=? and ip.ipedsid=?";
            $stm2 = $DBH->prepare($query);
            $stm2->execute(array($ipedstype, $ipedsval));
            $r2 = $stm2->fetch(PDO::FETCH_ASSOC);
            $schoolname = $r2['schoolname'];
        } else if ($userdata['ipeds'] == '0') {
            $schoolname = $userdata['otherschool'];
        }
    } else if (isset($userdata['school'])) {
        $schoolname = $userdata['school'];
    }
    echo '<td>'.Sanitize::encodeStringForDisplay($schoolname).'</td>';
    echo '<td>'.$status[$row['status']].'</td>';

    echo '<td><span class="pii-full-name">'.Sanitize::encodeStringForDisplay($row['extLast'].', '.$row['extFirst']).'</span></td>';
    echo '<td><span class="pii-username">'.Sanitize::encodeStringForDisplay($row['extSID']).'</span></td>';
    echo '<td><span class="pii-email">'.Sanitize::encodeStringForDisplay($row['extemail']).'</span></td>';
    echo '<td>'.Sanitize::encodeStringForDisplay($row['name']).'</td>';
    echo '</tr>';
}
echo '</tbody></table>';

echo '<p><button type=submit>'._('Delete Selected Requests').'</button></p>';
echo '<p>'._('This will mark the request as denied (without sending an email) and demote the account to a student account.').'</p>';

echo '</form>';

echo '<p>'.$fixedcnt.' '._('requests had already been upgraded, and have been updated').'</p>';

require('../footer.php');

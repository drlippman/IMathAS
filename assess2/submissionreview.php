<?php

/*
 IMathAS: An intermediary page for LTI submission review links to check that access is even allowed
 and output simple messages if not
*/

require_once "../init.php";
require_once "./AssessInfo.php";
require_once "./AssessRecord.php";
require_once './AssessUtils.php';

if (empty($_GET['cid']) || empty($_GET['aid']) || empty($_GET['uid'])) {
    echo 'Invalid link';
    exit;
}

if (!isset($studentid) && !isset($tutorid) && !isset($teacherid)) {
    echo 'Invalid link';
    exit;
}

/*$isRealStudent = (isset($studentid) && !isset($_SESSION['stuview']));

if (!$isRealStudent) {
  $studentinfo = array('latepasses' => 0, 'timelimitmult' => 1);
}
*/

$aid = intval($_GET['aid']);
$uid = intval($_GET['uid']);
$now = time();
$link = $basesiteurl . '/assess2/gbviewassess.php?cid='.$cid.'&uid='.$uid.'&aid='.$aid;
if (isset($studentid) && $uid !== $userid) {
    echo 'Invalid link';
    exit;
}
if (isset($studentid)) {
    $assess_info = new AssessInfo($DBH, $aid, $cid, false);
    $viewInGb = $assess_info->getSetting('viewingb');

    // get user info
    $query = 'SELECT iu.FirstName, iu.LastName, istu.latepass, istu.timelimitmult ';
    $query .= 'FROM imas_users AS iu JOIN imas_students AS istu ON istu.userid=iu.id ';
    $query .= 'WHERE iu.id=? AND istu.courseid=?';
    $stm = $DBH->prepare($query);
    $stm->execute(array($uid, $cid));
    $studata = $stm->fetch(PDO::FETCH_ASSOC);
    if ($studata === false) {
        echo 'Invalid user';
        exit;
    }

    $assess_info->loadException($uid, true, $studata['latepass'], $latepasshrs, $courseenddate);
    $assess_info->applyTimelimitMultiplier($studata['timelimitmult']);
    $assess_info->getLatePassStatus();

    //fields to extract from assess info for inclusion in output
    $include_from_assess_info = array(
    'enddate', 'can_use_latepass', 'latepass_enddate', 'viewingb'
    );
    $assessInfoOut = $assess_info->extractSettings($include_from_assess_info);

    if ($viewInGb == 'never' ||
        ($viewInGb == 'after_due' && $now < $assessInfoOut['enddate']) ||
        ($viewInGb == 'after_lp' && $now < $assessInfoOut['latepass_enddate']) ||
        ($now < $assessInfoOut['enddate'] && $assess_info->getSetting('timeext')>0)
    ) {
        echo _('Your submission cannot be viewed at this time.');
        exit;
    }
    if ($now > $assessInfoOut['enddate'] && $assessInfoOut['can_use_latepass'] > 0) {
        require('../header.php');
        echo '<p>'._('This assignment can still be reopened. If you review your work in the gradebook now, you will not be able to later use a LatePass.').'</p>';
        echo '<p><a href="'.Sanitize::encodeStringForDisplay($link).'">'._('Review work anyway').'</a></p>';
        require('../footer.php');
        exit;
    }
}
header('Location:'.$link);

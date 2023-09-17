<?php
//IMathAS:  Record system notice read in custominfo

require_once "../init.php";

$stm = $DBH->prepare("SELECT custominfo FROM imas_students WHERE courseid=1 AND userid=:userid");
$stm->execute(array(':userid'=>$userid));

if (!$stm) {
    echo 'FAIL';
    exit;
} else {
    $custominfo = json_decode($stm->fetchColumn(0),true);
    if ($custominfo===null) {
        $custominfo = array('noticedismiss'=>array($_GET['n']=>1));
    } else if (!isset($custominfo['noticedismiss'])) {
        $custominfo['noticedismiss'] = array($_GET['n']=>1);
    } else {
        $custominfo['noticedismiss'][$_GET['n']]=1;
    }
    $newcustom = json_encode($custominfo);
    $stm = $DBH->prepare("UPDATE imas_students SET custominfo=:custom WHERE courseid=1 AND userid=:userid");
    $stm->execute(array(':userid'=>$userid, ':custom'=>$newcustom));
    echo 'OK';
    exit;
}
?>

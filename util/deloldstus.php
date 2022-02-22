<?php
//IMathAS: Delete old students, only if they're no longer enrolled in any courses
//(c) 2022 David Lippman

//boost operation time
@set_time_limit(300);

ini_set("max_execution_time", "300");


require("../init_without_validate.php");
require("../includes/unenroll.php");
require("../includes/AWSSNSutil.php");
require_once("../includes/filehandler.php");

if (php_sapi_name() == "cli") {
	//running command line - no need for auth code
} else if (!isset($CFG['cleanup']['authcode'])) {
	echo 'You need to set $CFG[\'cleanup\'][\'authcode\'] in config.php';
	exit;
} else if (!isset($_GET['authcode']) || $CFG['cleanup']['authcode']!=$_GET['authcode']) {
	echo 'No authcode or invalid authcode provided';
	exit;
}

$userid = 0;

$starttime = time();
$batchsize = 1000;
if (isset($_GET['batchsize']) && is_numeric($_GET['batchsize'])) {
	$batchsize = Sanitize::onlyInt($_GET['batchsize']);
	if ($batchsize < 1) {
		$batchsize = 1000;
	}
}
if (isset($CFG['cleanup']['oldstu'])) {
    $olddays = intval($CFG['cleanup']['oldstu']);
} else {
    $olddays = 365;
}
// select students who aren't enrolled in any courses, and haven't logged in within $olddays days
$query = "SELECT iu.id FROM imas_users AS iu LEFT JOIN imas_students AS istu ";
$query .= "ON iu.id=istu.userid WHERE istu.id IS NULL AND iu.rights<11 AND iu.lastaccess<? ";
$query .= "LIMIT $batchsize ";
$stm = $DBH->prepare($query);
$stm->execute(array(time()-$olddays*24*60*60)); //a week old
$stus = $stm->fetchAll(PDO::FETCH_COLUMN, 0);

if (count($stus) > 0) {
    $ph = Sanitize::generateQueryPlaceholders($stus);
    $toDelTable = array('user_prefs', 'bookmarks', 'content_track', 'ltiusers');
    foreach ($toDelTable as $table) {
        $stm = $DBH->prepare("DELETE FROM imas_$table WHERE userid IN ($ph)");
        $stm->execute($stus);
    }
    $stm = $DBH->prepare("DELETE FROM imas_msgs WHERE msgto IN ($ph)");
    $stm->execute($stus);
    $stm = $DBH->prepare("DELETE FROM imas_msgs WHERE msgfrom IN ($ph)");
    $stm->execute($stus);
    
    //delete profile pics
    $pics = [];
    foreach ($stus as $deluid) {
        //deletecoursefiles(['userimg_'.$deluid.'.jpg', 'userimg_sm'.$deluid.'.jpg']);
        array_push($pics, 'userimg_'.$deluid.'.jpg', 'userimg_sm'.$deluid.'.jpg');
        if (count($pics)>500) {
            deletecoursefiles($pics);
            $pics = [];
        }
        //delete all user uploads
        deletealluserfiles($deluid);
    }
    if (count($pics)>0) {
        deletecoursefiles($pics);
        $pics = [];
    }

    // now actually delete user record
    $stm = $DBH->prepare("DELETE FROM imas_users WHERE id IN ($ph)");
    $stm->execute($stus);
}

$n = count($stus);
$timespent = time() - $starttime;
echo "DONE w $n in $timespent";
?>

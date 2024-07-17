<?php
//IMathAS: Delete old guest users
//(c) 2018 David Lippman

//boost operation time
@set_time_limit(300);

ini_set("max_execution_time", "300");


require_once "../init_without_validate.php";
require_once "../includes/unenroll.php";
require_once "../includes/AWSSNSutil.php";

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
$batchsize = 50;
if (isset($_GET['batchsize']) && is_numeric($_GET['batchsize'])) {
	$batchsize = Sanitize::onlyInt($_GET['batchsize']);
	if ($batchsize < 1) {
		$batchsize = 50;
	}
}
$query = "SELECT istu.courseid,istu.userid FROM imas_students AS istu JOIN imas_users AS iu ";
$query .= "ON istu.userid=iu.id WHERE iu.rights=5 AND iu.lastaccess<? ";
$query .= "ORDER BY istu.courseid LIMIT $batchsize ";
$stm = $DBH->prepare($query);
$stm->execute(array(time()-7*24*60*60)); //a week old
$stus = array();
$n = 0;
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
	if (!isset($stus[$row['courseid']])) {
		$stus[$row['courseid']] = array();
	}
	$stus[$row['courseid']][] = $row['userid'];
	$n++;
}

foreach ($stus as $cid=>$cstus) {
	unenrollstu($cid, $cstus);
}
$timespent = time() - $starttime;
echo "DONE w $n in $timespent";
?>

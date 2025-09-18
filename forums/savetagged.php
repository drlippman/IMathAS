<?php
//IMathAS.  Records tags/untags
//(c) 2007 David Lippman

require_once "../init.php";

if (!isset($teacherid) && !isset($tutorid) && !isset($studentid)) {
	echo "Error";
	exit;
}

if (!isset($_GET['threadid'])) {
  exit;
}

$query = "SELECT ifs.courseid FROM imas_forums AS ifs 
    JOIN imas_forum_threads AS ift ON ift.forumid=ifs.id 
    WHERE ift.id=?";
$stm = $DBH->prepare($query);
$stm->execute([$_GET['threadid']]);
if ($stm->fetchColumn(0) !== $cid) {
  echo 'Error';
  exit;
}

$query = "INSERT INTO imas_forum_views (userid,threadid,lastview,tagged) ";
$query .= "VALUES (:userid, :threadid, :lastview, :tagged) ";
$query .= "ON DUPLICATE KEY UPDATE tagged=VALUES(tagged)";
$stm = $DBH->prepare($query);
$stm->execute(array(':userid'=>$userid, ':threadid'=>$_GET['threadid'], ':lastview'=>0, ':tagged'=>$_GET['tagged']));
if ($stm->rowCount()>0) {
  echo "OK";
} else {
  echo "Error";
}

?>

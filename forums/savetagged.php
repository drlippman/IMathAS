<?php
//IMathAS.  Records tags/untags
//(c) 2007 David Lippman

require_once "../init.php";

if (!isset($_GET['threadid'])) {
  exit;
}

$ischanged = false;
$stm = $DBH->prepare("UPDATE imas_forum_views SET tagged=:tagged WHERE userid=:userid AND threadid=:threadid");
$stm->execute(array(':tagged'=>$_GET['tagged'], ':userid'=>$userid, ':threadid'=>$_GET['threadid']));
if ($stm->rowCount()>0) {
  
  $ischanged = true;
}
if (!$ischanged) {
  $query = "INSERT INTO imas_forum_views (userid,threadid,lastview,tagged) ";
  $query .= "VALUES (:userid, :threadid, :lastview, :tagged)";
  $stm = $DBH->prepare($query);
  $stm->execute(array(':userid'=>$userid, ':threadid'=>$_GET['threadid'], ':lastview'=>0, ':tagged'=>$_GET['tagged']));
  if ($stm->rowCount()>0) {
    $ischanged = true;
  }
}

if ($ischanged) {
  echo "OK";
} else {
  echo "Error";
}


?>

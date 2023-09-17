<?php
require_once "../init.php";
if ($myrights<100) { exit;}

if (isset($_POST['data'])) {
	$info = array();
	$lines = explode("\n",$_POST['data']);
	foreach ($lines as $line) {
		list($uid,$lastm,$extref) = explode('@',trim($line));
		$extref = str_replace(array("\r","\t"),'',$extref);
		$info[$uid] = array($lastm,$extref);
	}
	$add_extref_stm = $DBH->prepare("UPDATE imas_questionset SET extref=:extref WHERE id=:id");
	$stm = $DBH->query("SELECT id,uniqueid,lastmoddate,extref FROM imas_questionset WHERE 1");
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		if (!isset($info[$row['uniqueid']])) {continue;}
		if (trim($row['extref'])!=trim($info[$row['uniqueid']][1])) {  //if different
			if ($row['extref']=='') {
				echo "Found new extref.  Adding.<br/>";
				//add it
				$add_extref_stm->execute(array(':extref'=>$info[$row['uniqueid']][1], ':id'=>$row['id']));
			} else {
				if ($row['lastmoddate']>$info[$row['uniqueid']][0]) {
					echo 'Local more recent '.Sanitize::onlyInt($row['id']).': '.Sanitize::encodeStringForDisplay($row['extref']). ' vs. '.Sanitize::encodeStringForDisplay($info[$row['uniqueid']][1]).'.  Skipping.<br/>';
				} else {
					echo 'Import more recent '.Sanitize::onlyInt($row['id']).': '.Sanitize::encodeStringForDisplay($row['extref']). ' vs. '.Sanitize::encodeStringForDisplay($info[$row['uniqueid']][1]).'.   Updating.<br/>';
					$add_extref_stm->execute(array(':extref'=>$info[$row['uniqueid']][1], ':id'=>$row['id']));
				}
			}
		}
	}
} else {
	echo '<html><body><b>Do NOT use this unless you know what you are doing.</b>';
	echo '<form method="post"><textarea name="data" rows="30" cols="80"></textarea>';
	echo '<input type="submit"></form></body></html>';
}
?>

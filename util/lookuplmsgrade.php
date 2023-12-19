<?php

require_once "../init.php";

if ($myrights<100) {
	echo "You are not authorized to view this page";
	exit;
}

require_once "../header.php";

if (!isset($_POST['sourcedid'])) {
	echo '<form method="post">';
	echo 'lti_sourcedid string: <input type="text" size="90" name="sourcedid" /><br/>';
	echo 'Secret (blank ok if same system): <input type="text" size=15 name=secret /><br/>';
	echo '<input type=submit /></form>';
} else {
	require_once "../includes/ltioutcomes.php";

	list($lti_sourcedid,$ltiurl,$ltikey,$keytype) = explode(':|:', $_POST['sourcedid']);

	$secret = '';
	if (!empty($_POST['secret'])) {
		$secret = trim($_POST['secret']);
	} else if (strlen($lti_sourcedid)>1 && strlen($ltiurl)>1 && strlen($ltikey)>1) {
		if ($keytype=='c') {
			$keyparts = explode('_',$ltikey);
			$stm = $DBH->prepare("SELECT ltisecret FROM imas_courses WHERE id=:id");
			$stm->execute(array(':id'=>$keyparts[1]));
			if ($stm->rowCount()>0) {
				$secret = $stm->fetchColumn(0);
			}
		} else {
			$stm = $DBH->prepare("SELECT password FROM imas_users WHERE SID=:SID AND (rights=11 OR rights=76 OR rights=77)");
			$stm->execute(array(':SID'=>$ltikey));
			if ($stm->rowCount()>0) {
				$secret = $stm->fetchColumn(0);
			}
		}
	}

	if ($secret != '') {
		//echo "<p>Calling $ltiurl with $ltikey, $secret, and $lti_sourcedid</p>";
		$value = sendLTIOutcome('read',$ltikey,$secret,$ltiurl,$lti_sourcedid,0,true);
		echo "<p>LMS grade: ".Sanitize::encodeStringForDisplay($value[1])."</p>";
	} else {
		echo "Unable to lookup secret";
	}
}
require_once "../footer.php";

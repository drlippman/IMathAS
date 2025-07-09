<?php

require_once "../init.php";

if ($myrights<100) {
	echo "You are not authorized to view this page";
	exit;
}
$pagetitle = _('Resend LTI Grade');
require_once "../header.php";

if (!isset($_POST['aid']) || !isset($_POST['uid'])) {
	echo '<form method="post">';
	echo 'Assessment ID: <input type="text" size="5" name="aid" /><br/>';
	echo 'User ID: <input type="text" size="5" name="uid" /><br/>';
	echo '<input type=submit value="Resend Grade" /></form>';
} else {
	$aid = Sanitize::onlyInt($_POST['aid']);
  $uid = Sanitize::onlyInt($_POST['uid']);

	require_once "../includes/ltioutcomes.php";

	$stm = $DBH->prepare("SELECT ver,ptsposs FROM imas_assessments WHERE id=:id");
	$stm->execute(array(':id'=>$aid));
	list($ver, $ptsposs) = $stm->fetch(PDO::FETCH_NUM);

	if ($ver == 2) {
  	$query = "SELECT lti_sourcedid as sourcedid, score as scores "
            . " FROM imas_assessment_records WHERE assessmentid=:aid AND userid=:uid";
  } else {
    $query = "SELECT lti_sourcedid as sourcedid, bestscores as scores "
            . " FROM imas_assessment_sessions WHERE assessmentid=:aid AND userid=:uid";
  }
	$stm = $DBH->prepare($query);
	$stm->execute(array(':aid'=>$aid, ':uid'=>$uid));
	if ($stm->rowCount()==0) {
		echo "No record found";
		exit;
	}
	$results = $stm->fetch(PDO::FETCH_ASSOC);

	if ($ver == 1) {
		$results['scores'] = getpts($results['scores']);
	}

	list($lti_sourcedid,$ltiurl,$ltikey,$keytype) = explode(':|:', $results['sourcedid']);

	$secret = '';
	if (strlen($lti_sourcedid)>1 && strlen($ltiurl)>1 && strlen($ltikey)>1) {
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
		$grade = min(1, max(0,$results['scores']/$ptsposs));
		$grade = number_format($grade,8);
		$response = sendLTIOutcome('update',$ltikey,$secret,$ltiurl,$lti_sourcedid,$grade,true);
		echo "<p>Url: ".Sanitize::encodeStringForDisplay($ltiurl).'</p>';
		echo "<p>Key: ".Sanitize::encodeStringForDisplay($ltikey).'</p>';
		echo "<p>Secret: ".Sanitize::encodeStringForDisplay($secret).'</p>';
		echo "<p>Sourcedid: ".Sanitize::encodeStringForDisplay($lti_sourcedid).'</p>';
		echo "<p>Grade: ".Sanitize::encodeStringForDisplay($grade).'</p>';
		echo "<p>LMS response:</p><pre>";
		print_r($response);
		echo '</pre>';
		//echo htmlentities($response)."</pre>";
	} else {
		echo "Unable to lookup secret given $ltikey with type $keytype";
	}
}

function getpts($scs) {
	$tot = 0;
  	foreach(explode(',',$scs) as $sc) {
        $qtot = 0;
        if (strpos($sc,'~')===false) {
            if ($sc>0) {
                $qtot = $sc;
            }
        } else {
            $sc = explode('~',$sc);
            foreach ($sc as $s) {
                if ($s>0) {
                    $qtot+=$s;
                }
            }
        }
        $tot += round($qtot,1);
    }
	return $tot;
}

require_once "../footer.php";

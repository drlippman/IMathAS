<?php
// localhost/imathas/course/claimbadge.php?cid=3942&badgid=4&userid=108534
require("../init.php");


$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=".Sanitize::courseId($_GET['cid'])."\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";

$placeinhead = '<script src="http://beta.openbadges.org/issuer.js"></script>';
require("../header.php");

echo "<div class=\"breadcrumb\">$curBreadcrumb &gt; Claim Badge</div>";

if (isset($teacherid)) {
	if (empty($_GET['userid'])) {
		echo 'Only students can claim a badge';
	} else {
		$userid = Sanitize::onlyInt($_GET['userid']);
		//DB $query = "SELECT SID FROM imas_users WHERE id='$userid'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $username = mysql_result($result,0,0);
		$stm = $DBH->prepare("SELECT SID FROM imas_users WHERE id=:id");
		$stm->execute(array(':id'=>$userid));
		$username = $stm->fetchColumn(0);
	}
} else if (!isset($studentid) && !isset($teacherid)) {
	echo 'Must be a student in this class to claim a badge';

} else if (!isset($_GET['badgeid'])) {
	echo 'No badge id provided';
//} else if (isset($teacherid) || isset($tutorid))  {
//	echo 'This page is only relevant to student.';
//} else if (!isset($studentid)) {
//	echo 'You are not authorized to view this page.';
} else {
	$badgeid = Sanitize::onlyInt($_GET['badgeid']);
	//DB $query = "SELECT name, requirements FROM imas_badgesettings WHERE id=$badgeid AND courseid='$cid'";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB if (mysql_num_rows($result)==0) {
	$stm = $DBH->prepare("SELECT name, requirements FROM imas_badgesettings WHERE id=:id AND courseid=:courseid");
	$stm->execute(array(':id'=>$badgeid, ':courseid'=>$cid));
	if ($stm->rowCount()==0) {
		echo 'Invalid badge ID for this course';
	} else {
		//DB list($name,$req) = mysql_fetch_row($result);
		list($name,$req) = $stm->fetch(PDO::FETCH_NUM);
		$req = unserialize($req);

		//get student's scores
		require("gbtable2.php");
		$secfilter = -1;
		$gbt = gbtable($userid);


		//DB $query = "SELECT id,name FROM imas_gbcats WHERE courseid='$cid' ORDER BY name";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		$stm = $DBH->prepare("SELECT id,name FROM imas_gbcats WHERE courseid=:courseid ORDER BY name");
		$stm->execute(array(':courseid'=>$cid));
		$gtypes = array('0'=>'Past Due', '3'=>'Past and Attempted', '1'=>'Past and Available', '2'=>'All Items');

		//DB while ($row=mysql_fetch_row($result)) {
		while ($row=$stm->fetch(PDO::FETCH_NUM)) {
			$gbcats[$row[0]] = $row[1];
		}

		$reqmet = true;
		echo '<h1>Badge: ' . Sanitize::encodeStringForDisplay($name) . '</h1>';
		echo '<p>Badge requirements:</p>';
		echo '<table class="gb"><thead><tr><th>Category/Course Total</th><th>Score Required</th><th>Your Score</th><th>Requirement Met</th></tr></thead><tbody>';
		foreach ($req['data'] as $r) {  //r = array(gbcat, gradetype, score)
			$metthis = false;
			echo '<tr><td>';
			if ($r[0]>0) {//is a category total
				echo Sanitize::encodeStringForDisplay($gbcats[$r[0]]) . ' (' . Sanitize::encodeStringForDisplay($gtypes[$r[1]]) . ')';
			} else {
				echo 'Course Total ('.$gtypes[$r[1]].')';
			}
			echo '</td><td>';
			echo Sanitize::encodeStringForDisplay($r[2]) . '%';
			echo '</td><td>';
			if ($r[0]>0) {//is a category total
				foreach ($gbt[0][2] as $i=>$catinfo) {
					if ($catinfo[10]==$r[0]) { //found category
						if ($r[1]==3) {
							$mypercent = round(100*$gbt[1][2][$i][3]/$gbt[1][2][$i][4],1);
						} else {
							if ($catinfo[$r[1]+3]==0) {
								$mypercent= 0;
							} else {
								$mypercent = round(100*$gbt[1][2][$i][$r[1]]/$catinfo[$r[1]+3],1);
							}
						}
						echo $mypercent.'%';
						if ($mypercent>=$r[2]) {
							$metthis = true;
						}
					}
				}
			} else { //is a course total
				if ($r[1]==3) { //past and attempted
					if ($gbt[1][3][8]==null) {
						$mypercent = $gbt[1][3][6];
					} else {
						$mypercent = $gbt[1][3][8];
					}
				} else {
					if ($gbt[1][3][3+$r[1]]==null) {
						$mypercent = $gbt[1][3][$r[1]];
					} else {
						$mypercent = $gbt[1][3][3+$r[1]];
					}
				}
				echo $mypercent.'%';
				if ($mypercent>=$r[2]) {
					$metthis = true;
				}
			}
			echo '</td><td>';
			if ($metthis==true) {
				echo 'Yes!';
			} else {
				echo 'No';
				$reqmet = false;
			}
			echo '</td></tr>';
		}
		echo '</tbody></table>';

		if ($reqmet) {
			echo '<h2>Badge Requirements have been met!</h2>';
			$verify = Sanitize::encodeUrlParam(hash('sha256', $username . $userid));
			$url = $GLOBALS['basesiteurl'] . '/course/verifybadge.php?format=json&userid='.$userid.'&badgeid='.$badgeid.'&v='.$verify;

			echo '<p><input type="button" value="Claim Badge" onclick="OpenBadges.issue([\''.$url.'\'], function(errors,successes) { })"/><br/>FireFox, Chrome, Safari, or IE 9+ is needed to claim badge.</p>';
		}
	}

}
require("../footer.php");

?>

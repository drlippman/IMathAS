<?php
//IMathAS:  Redeem latepasses
//(c) 2007 David Lippman

	require_once "../init.php";

	$cid = Sanitize::courseId($_GET['cid']);
	$aid = Sanitize::onlyInt($_GET['aid']);

	require_once "../includes/exceptionfuncs.php";
	if (isset($studentid) && !isset($_SESSION['stuview'])) {
		$exceptionfuncs = new ExceptionFuncs($userid, $cid, true, $studentinfo['latepasses'], $latepasshrs);
	} else {
		$exceptionfuncs = new ExceptionFuncs($userid, $cid, false);
	}

	if (isset($_REQUEST['from'])) {
		$from = Sanitize::simpleString($_REQUEST['from']);
	} else {
		$from = 'default';
	}

	$now = time();

	$latepasses = $studentinfo['latepasses'];

	if (isset($_GET['undo'])) {
		require_once "../header.php";
		echo "<div class=breadcrumb>";
		if ($from != 'ltitimelimit') {
			echo "$breadcrumbbase ";
		}
		if ($cid>0 && (!isset($_SESSION['ltiitemtype']) || $_SESSION['ltiitemtype']!=0)) {
			echo " <a href=\"../course/course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
		}
		if ($from == 'gb') {
			echo ' <a href="gradebook.php?cid='.$cid.'">'._('Gradebook').'</a> &gt; ';
		}
		echo "Un-use LatePass</div>";
		$stm = $DBH->prepare("SELECT enddate,islatepass FROM imas_exceptions WHERE userid=:userid AND assessmentid=:assessmentid AND itemtype='A'");
		$stm->execute(array(':userid'=>$userid, ':assessmentid'=>$aid));
		if ($stm->rowCount()==0) {
			echo '<p>Invalid</p>';
		} else {
			$row = $stm->fetch(PDO::FETCH_NUM);
			if ($row[1]==0) {
				echo '<p>Invalid</p>';
			} else {
				$now = time();
				$stm = $DBH->prepare("SELECT enddate FROM imas_assessments WHERE id=:id");
				$stm->execute(array(':id'=>$aid));
				$enddate = $stm->fetchColumn(0);
				//if it's past original due date and latepass is for less than latepasshrs past now, too late
				if ($now > $enddate && $row[0] < strtotime("+".$latepasshrs." hours", $now)) {
					echo '<p>Too late to un-use this LatePass</p>';
				} else {
					if ($now < $enddate) { //before enddate, return all latepasses
						$maxLP = max(0,floor(($row[0]-$enddate)/($latepasshrs*3600)+.05));
						$n = min($maxLP,$row[1]);
						$stm = $DBH->prepare("DELETE FROM imas_exceptions WHERE userid=:userid AND assessmentid=:assessmentid AND itemtype='A'");
						$stm->execute(array(':userid'=>$userid, ':assessmentid'=>$aid));
					} else { //figure how many are unused
						$n = floor(($row[0] - $now)/($latepasshrs*60*60) + .05);
						$tothrs = $n*$latepasshrs;
						$newend = strtotime("-".$tothrs." hours", $row[0]);
						//$newend = $row[0] - $n*$latepasshrs*60*60;
						if ($row[1]>$n) {
							$stm = $DBH->prepare("UPDATE imas_exceptions SET islatepass=islatepass-:n,enddate=:enddate WHERE userid=:userid AND assessmentid=:assessmentid AND itemtype='A'");
							$stm->execute(array(':enddate'=>$newend, ':userid'=>$userid, ':assessmentid'=>$aid, ':n'=>$n));
						} else {
							$stm = $DBH->prepare("DELETE FROM imas_exceptions WHERE userid=:userid AND assessmentid=:assessmentid AND itemtype='A'");
							$stm->execute(array(':userid'=>$userid, ':assessmentid'=>$aid));
							$n = $row[1];
						}
					}
					printf("<p>Returning %d LatePass", Sanitize::onlyInt($n));
					echo ($n>1?"es":"")."</p>";
					$stm = $DBH->prepare("UPDATE imas_students SET latepass=latepass+:n WHERE userid=:userid AND courseid=:courseid");
					$stm->execute(array(':userid'=>$userid, ':courseid'=>$cid, ':n'=>$n));
				}
			}
		}

		if ($from=='ltitimelimit') {
			echo "<p><a href=\"../bltilaunch.php?accessibility=ask'\">Continue</a></p>";
		} else if ($from=='gb') {
			echo "<p><a href=\"gradebook.php?cid=$cid\">Continue</a></p>";
		} else if ((!isset($_SESSION['ltiitemtype']) || $_SESSION['ltiitemtype']!=0)) {
			echo "<p><a href=\"course.php?cid=$cid\">Continue</a></p>";
		} else {
			echo "<p><a href=\"../assessment/showtest.php?cid=$cid&id={$_SESSION['ltiitemid']}\">Continue</a></p>";
		}
		require_once "../footer.php";

	} else if (isset($_POST['confirm'])) {
		//$addtime = $latepasshrs*60*60;
		$stm = $DBH->prepare("SELECT allowlate,enddate,startdate,LPcutoff FROM imas_assessments WHERE id=:id");
		$stm->execute(array(':id'=>$aid));
		list($allowlate,$enddate,$startdate,$LPcutoff) =$stm->fetch(PDO::FETCH_NUM);
		if ($LPcutoff<$enddate) {
			$LPcutoff = 0;  //ignore nonsensical values
		}
		$stm = $DBH->prepare("SELECT startdate,enddate,islatepass,is_lti FROM imas_exceptions WHERE userid=:userid AND assessmentid=:assessmentid AND itemtype='A'");
		$stm->execute(array(':userid'=>$userid, ':assessmentid'=>$aid));
		$hasexception = false;
		if ($stm->rowCount()==0) {
			$usedlatepasses = 0;
			$thised = $enddate;
			$useexception = false;
			$canuselatepass = $exceptionfuncs->getCanUseAssessLatePass(array('startdate'=>$startdate, 'enddate'=>$enddate, 'allowlate'=>$allowlate, 'LPcutoff'=>$LPcutoff, 'id'=>$aid));
		} else {
			$r = $stm->fetch(PDO::FETCH_NUM);
			list($useexception, $canundolatepass, $canuselatepass) = $exceptionfuncs->getCanUseAssessException($r, array('startdate'=>$startdate, 'enddate'=>$enddate, 'allowlate'=>$allowlate, 'LPcutoff'=>$LPcutoff, 'id'=>$aid));
			if ($useexception) {
				if (!empty($r[3])) { //is_lti - use count in exception
					$usedlatepasses = $r[2];
				} else {
				$usedlatepasses = min(max(0,round(($r[1] - $enddate)/($latepasshrs*3600))), $r[2]);
				}
				$thised = $r[1];
			} else {
				$usedlatepasses = 0;
				$thised = $enddate;
			}
			$hasexception = true;
		}
		if ($now>$thised) {
			//$LPneeded = ceil(($now - $thised)/($latepasshrs*3600) - .0001);
			$LPneeded = $exceptionfuncs->calcLPneeded($thised);
		} else {
			$LPneeded = 1;
		}

		if ($canuselatepass) {
			$stm = $DBH->prepare("UPDATE imas_students SET latepass=latepass-:lps WHERE userid=:userid AND courseid=:courseid AND latepass>=:lps2");
			$stm->execute(array(':lps'=>$LPneeded, ':lps2'=>$LPneeded, ':userid'=>$userid, ':courseid'=>$cid));
			if ($stm->rowCount()>0) {
				$enddate = min(strtotime("+".($latepasshrs*$LPneeded)." hours", $thised), $courseenddate);
				if ($LPcutoff>0) {
					$enddate = min($enddate, $LPcutoff);
				}
				if ($hasexception) { //already have exception
					$stm = $DBH->prepare("UPDATE imas_exceptions SET enddate=:enddate,islatepass=islatepass+:lps WHERE userid=:userid AND assessmentid=:assessmentid AND itemtype='A'");
					$stm->execute(array(':lps'=>$LPneeded, ':userid'=>$userid, ':assessmentid'=>$aid, ':enddate'=>$enddate));
				} else {
					$stm = $DBH->prepare("INSERT INTO imas_exceptions (userid,assessmentid,startdate,enddate,islatepass,itemtype) VALUES (:userid, :assessmentid, :startdate, :enddate, :islatepass, :itemtype)");
					$stm->execute(array(':userid'=>$userid, ':assessmentid'=>$aid, ':startdate'=>$startdate, ':enddate'=>$enddate, ':islatepass'=>$LPneeded, ':itemtype'=>'A'));
				}
			}
		}
		if ($from=='ltitimelimit') {
			header('Location: ' . $GLOBALS['basesiteurl'] . "/bltilaunch.php?accessibility=ask" . "&r=" . Sanitize::randomQueryStringParam());
		} else if ($from=='gb') {
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/gradebook.php?cid=$cid" . "&r=" . Sanitize::randomQueryStringParam());
		} else if ((!isset($_SESSION['ltiitemtype']) || $_SESSION['ltiitemtype']!=0)) {
			$btf = isset($_GET['btf']) ? '&folder=' . Sanitize::encodeUrlParam($_GET['btf']) : '';
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?cid=$cid$btf" . "&r=" . Sanitize::randomQueryStringParam());
		} else if (isset($_SESSION['ltiitemver']) && $_SESSION['ltiitemver'] > 1) {
			header('Location: ' . $GLOBALS['basesiteurl'] . "/assess2/?cid=$cid&aid={$_SESSION['ltiitemid']}" . "&r=" . Sanitize::randomQueryStringParam());
		} else {
			header('Location: ' . $GLOBALS['basesiteurl'] . "/assessment/showtest.php?cid=$cid&id={$_SESSION['ltiitemid']}" . "&r=" . Sanitize::randomQueryStringParam());
		}
	} else {
		require_once "../header.php";
		echo "<div class=breadcrumb>";
		if ($from != 'ltitimelimit') {
			echo "$breadcrumbbase ";
		}
		if ($cid>0 && (!isset($_SESSION['ltiitemtype']) || $_SESSION['ltiitemtype']!=0)) {
			echo " <a href=\"../course/course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
		}
		if ($from == 'gb') {
			echo ' <a href="gradebook.php?cid='.$cid.'">'._('Gradebook').'</a> &gt; ';
		}
		echo "Redeem LatePass</div>\n";
		//$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\"> $coursename</a>\n";
		//$curBreadcrumb .= " Redeem LatePass\n";
		//echo "<div class=\"breadcrumb\">$curBreadcrumb</div>";
		$stm = $DBH->prepare("SELECT allowlate,enddate,startdate,timelimit,LPcutoff,ver FROM imas_assessments WHERE id=:id");
		$stm->execute(array(':id'=>$aid));
		list($allowlate,$enddate,$startdate,$timelimit,$LPcutoff,$aVer) =$stm->fetch(PDO::FETCH_NUM);
		if ($LPcutoff<$enddate) {
			$LPcutoff = 0;  //ignore nonsensical values
		}
		$stm = $DBH->prepare("SELECT startdate,enddate,islatepass,is_lti FROM imas_exceptions WHERE userid=:userid AND assessmentid=:assessmentid AND itemtype='A'");
		$stm->execute(array(':userid'=>$userid, ':assessmentid'=>$aid));
		$hasexception = false;
		if ($stm->rowCount()==0) {
			$usedlatepasses = 0;
			$thised = $enddate;
			$canuselatepass = $exceptionfuncs->getCanUseAssessLatePass(array('startdate'=>$startdate, 'enddate'=>$enddate, 'allowlate'=>$allowlate, 'LPcutoff'=>$LPcutoff, 'id'=>$aid));
		} else {
			$r = $stm->fetch(PDO::FETCH_NUM);
			list($useexception, $canundolatepass, $canuselatepass) = $exceptionfuncs->getCanUseAssessException($r, array('startdate'=>$startdate, 'enddate'=>$enddate, 'allowlate'=>$allowlate, 'LPcutoff'=>$LPcutoff, 'id'=>$aid));
			if ($useexception) {
				if (!empty($r[3])) { //is_lti - use count in exception
					$usedlatepasses = $r[2];
				} else {
				$usedlatepasses = min(max(0,round(($r[1] - $enddate)/($latepasshrs*3600))), $r[2]);
				}
				$thised = $r[1];
			} else {
				$usedlatepasses = 0;
				$thised = $enddate;
				if ($now>$enddate) {
					//$LPneeded = ceil(($now - $enddate)/($latepasshrs*3600) - .0001);
					$LPneeded = $exceptionfuncs->calcLPneeded($enddate);
				}
			}
			$hasexception = true;
		}

		if ($now>$thised) {
			//$LPneeded = ceil(($now - $thised)/($latepasshrs*3600) - .0001);
			$LPneeded = $exceptionfuncs->calcLPneeded($thised);
		} else {
			$LPneeded = 1;
		}
		$limitedByCourseEnd = (strtotime("+".($latepasshrs*$LPneeded)." hours", $thised) > $courseenddate && ($LPcutoff==0 || $LPcutoff>$courseenddate));
		$limitedByLPcutoff = ($LPcutoff>0 && strtotime("+".($latepasshrs*$LPneeded)." hours", $thised) > $LPcutoff && $LPcutoff<$courseenddate);

		$timelimitstatus = $exceptionfuncs->getTimelimitStatus($aid, $aVer);

		if ($latepasses==0) { //shouldn't get here if 0
			echo "<p>You have no late passes remaining.</p>";
		} else if ($canuselatepass) {
			echo '<div id="headerredeemlatepass" class="pagetitle"><h1>Redeem LatePass</h1></div>';
			echo "<form method=post action=\"redeemlatepass.php?cid=$cid&aid=$aid\">";
			if ($allowlate%10>1) {
			    echo '<p>You may use up to '.Sanitize::onlyInt($allowlate%10-1-$usedlatepasses).' more LatePass(es) on this assessment.</p>';
			}
			echo "<p>You have ".Sanitize::encodeStringForDisplay($latepasses)." LatePass(es) remaining.</p>";

			if ($LPneeded==1) {
				echo '<p>';
				if ($limitedByCourseEnd) {
					echo sprintf("You can redeem one LatePass for an extension up to the course end date, %s. ", tzdate("D n/j/y, g:i a", $courseenddate));
				} else if ($limitedByLPcutoff) {
					echo sprintf("You can redeem one LatePass for an extension up to the cutoff date, %s. ", tzdate("D n/j/y, g:i a", $LPcutoff));
				} else {
					echo "You can redeem one LatePass for a ".Sanitize::encodeStringForDisplay($latepasshrs)." hour extension on this assessment. ";
				}
				echo "</p><p>Are you sure you want to redeem a LatePass?</p>";
			} else {
				echo "<p>Each LatePass gives a ".Sanitize::encodeStringForDisplay($latepasshrs)." hour extension on this assessment. ";
				if ($limitedByCourseEnd) {
					echo sprintf("You would need %d LatePasses to reopen this assignment up to the course end date, %s. ", $LPneeded, tzdate("D n/j/y, g:i a", $courseenddate));
				} else if ($limitedByLPcutoff) {
					echo sprintf("You would need %d LatePasses to reopen this assignment up to the cutoff date, %s. ", $LPneeded, tzdate("D n/j/y, g:i a", $LPcutoff));
				} else {
					echo "You would need $LPneeded LatePasses to reopen this assignment. ";
				}
				echo "</p><p>Are you sure you want to redeem $LPneeded LatePasses?</p>";
			}
			if ($timelimitstatus=='started') {
				echo '<p class="noticetext">'._('Reminder: You have already started this assessment, and it has a time limit.  Using a LatePass does <b>not</b> extend or pause the time limit, only the due date.').'</p>';
			} else if ($timelimitstatus=='expired') {
				echo '<p class="noticetext">'._('Your time limit has expired on this assessment.  Using a LatePass does <b>not</b> extend the time limit, so there is no reason to use a LatePass.').'</p>';
			} else if ($timelimitstatus=='outofattempts') {
				echo '<p class="noticetext">'._('You have used all your attempts on this question.  Using a LatePass does <b>not</b> add another attempt, so there is no reason to use a LatePass.').'</p>';
			}
			echo '<p><input type="hidden" name="confirm" value="true" />';
			echo '<input type="hidden" name="from" value="'.Sanitize::encodeStringForDisplay($from).'" />';
			if ($timelimitstatus!='expired') {
				echo "<input type=submit value=\"Yes, Redeem LatePass(es)\"/>";
			}
			if ($from=='ltitimelimit') {
				echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='../bltilaunch.php?accessibility=ask'\"/>";
			} else if ($from=='gb') {
				echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='gradebook.php?cid=$cid'\"/>";
			} else if ((!isset($_SESSION['ltiitemtype']) || $_SESSION['ltiitemtype']!=0)) {
				echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='course.php?cid=$cid'\"/>";
			} else if (isset($_SESSION['ltiitemver']) && $_SESSION['ltiitemver'] > 1) {
				echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='../assess2/?cid=$cid&aid={$_SESSION['ltiitemid']}'\"/>";
			} else {
				echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='../assessment/showtest.php?cid=$cid&id={$_SESSION['ltiitemid']}'\"/>";
			}
			echo "</p></form>";
		} else {
			echo "<p>You are not allowed to use additional latepasses on this assessment.</p>";
		}
		require_once "../footer.php";
	}

?>

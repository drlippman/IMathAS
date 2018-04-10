<?php
//IMathAS:  Redeem latepasses
//(c) 2007 David Lippman

	require("../init.php");

	$cid = Sanitize::courseId($_GET['cid']);
	$aid = Sanitize::onlyInt($_GET['aid']);

	require("../includes/exceptionfuncs.php");
	if (isset($studentid) && !isset($sessiondata['stuview'])) {
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
		require("../header.php");
		echo "<div class=breadcrumb>";
		if ($from != 'ltitimelimit') {
			echo "$breadcrumbbase ";
		}
		if ($cid>0 && (!isset($sessiondata['ltiitemtype']) || $sessiondata['ltiitemtype']!=0)) {
			echo " <a href=\"../course/course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
		}
		if ($from == 'gb') {
			echo ' <a href="gradebook.php?cid='.$cid.'">'._('Gradebook').'</a> &gt; ';
		}
		echo "Un-use LatePass</div>";
		//DB $query = "SELECT enddate,islatepass FROM imas_exceptions WHERE userid='$userid' AND assessmentid='$aid' AND itemtype='A'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB if (mysql_num_rows($result)==0) {
		$stm = $DBH->prepare("SELECT enddate,islatepass FROM imas_exceptions WHERE userid=:userid AND assessmentid=:assessmentid AND itemtype='A'");
		$stm->execute(array(':userid'=>$userid, ':assessmentid'=>$aid));
		if ($stm->rowCount()==0) {
			echo '<p>Invalid</p>';
		} else {
			//DB $row = mysql_fetch_row($result);
			$row = $stm->fetch(PDO::FETCH_NUM);
			if ($row[1]==0) {
				echo '<p>Invalid</p>';
			} else {
				$now = time();
				//DB $query = "SELECT enddate FROM imas_assessments WHERE id='$aid'";
				//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
				//DB $enddate = mysql_result($result,0,0);
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
						//DB $query = "DELETE FROM imas_exceptions WHERE userid='$userid' AND assessmentid='$aid' AND itemtype='A'";
						//DB mysql_query($query) or die("Query failed : " . mysql_error());
						$stm = $DBH->prepare("DELETE FROM imas_exceptions WHERE userid=:userid AND assessmentid=:assessmentid AND itemtype='A'");
						$stm->execute(array(':userid'=>$userid, ':assessmentid'=>$aid));
					} else { //figure how many are unused
						$n = floor(($row[0] - $now)/($latepasshrs*60*60) + .05);
						$tothrs = $n*$latepasshrs;
						$newend = strtotime("-".$tothrs." hours", $row[0]);
						//$newend = $row[0] - $n*$latepasshrs*60*60;
						if ($row[1]>$n) {
							//DB $query = "UPDATE imas_exceptions SET islatepass=islatepass-$n,enddate=$newend WHERE userid='$userid' AND assessmentid='$aid' AND itemtype='A'";
							//DB mysql_query($query) or die("Query failed : " . mysql_error());
							$stm = $DBH->prepare("UPDATE imas_exceptions SET islatepass=islatepass-:n,enddate=:enddate WHERE userid=:userid AND assessmentid=:assessmentid AND itemtype='A'");
							$stm->execute(array(':enddate'=>$newend, ':userid'=>$userid, ':assessmentid'=>$aid, ':n'=>$n));
						} else {
							//DB $query = "DELETE FROM imas_exceptions WHERE userid='$userid' AND assessmentid='$aid' AND itemtype='A'";
							//DB mysql_query($query) or die("Query failed : " . mysql_error());
							$stm = $DBH->prepare("DELETE FROM imas_exceptions WHERE userid=:userid AND assessmentid=:assessmentid AND itemtype='A'");
							$stm->execute(array(':userid'=>$userid, ':assessmentid'=>$aid));
							$n = $row[1];
						}
					}
					printf("<p>Returning %d LatePass", Sanitize::onlyInt($n));
					echo ($n>1?"es":"")."</p>";
					//DB $query = "UPDATE imas_students SET latepass=latepass+$n WHERE userid='$userid' AND courseid='$cid'";
					//DB mysql_query($query) or die("Query failed : " . mysql_error());
					$stm = $DBH->prepare("UPDATE imas_students SET latepass=latepass+:n WHERE userid=:userid AND courseid=:courseid");
					$stm->execute(array(':userid'=>$userid, ':courseid'=>$cid, ':n'=>$n));
				}
			}
		}

		if ($from=='ltitimelimit') {
			echo "<p><a href=\"../bltilaunch.php?accessibility=ask'\">Continue</a></p>";
		} else if ($from=='gb') {
			echo "<p><a href=\"gradebook.php?cid=$cid\">Continue</a></p>";
		} else if ((!isset($sessiondata['ltiitemtype']) || $sessiondata['ltiitemtype']!=0)) {
			echo "<p><a href=\"course.php?cid=$cid\">Continue</a></p>";
		} else {
			echo "<p><a href=\"../assessment/showtest.php?cid=$cid&id={$sessiondata['ltiitemid']}\">Continue</a></p>";
		}
		require("../footer.php");

	} else if (isset($_POST['confirm'])) {
		//$addtime = $latepasshrs*60*60;
		//DB $query = "SELECT allowlate,enddate,startdate FROM imas_assessments WHERE id='$aid'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB list($allowlate,$enddate,$startdate) =mysql_fetch_row($result);
		$stm = $DBH->prepare("SELECT allowlate,enddate,startdate FROM imas_assessments WHERE id=:id");
		$stm->execute(array(':id'=>$aid));
		list($allowlate,$enddate,$startdate) =$stm->fetch(PDO::FETCH_NUM);

		//DB $query = "SELECT enddate,islatepass FROM imas_exceptions WHERE userid='$userid' AND assessmentid='$aid' AND itemtype='A'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		$stm = $DBH->prepare("SELECT startdate,enddate,islatepass,is_lti FROM imas_exceptions WHERE userid=:userid AND assessmentid=:assessmentid AND itemtype='A'");
		$stm->execute(array(':userid'=>$userid, ':assessmentid'=>$aid));
		$hasexception = false;
		//DB if (mysql_num_rows($result)==0) {
		if ($stm->rowCount()==0) {
			$usedlatepasses = 0;
			$thised = $enddate;
			$useexception = false;
			$canuselatepass = $exceptionfuncs->getCanUseAssessLatePass(array('startdate'=>$startdate, 'enddate'=>$enddate, 'allowlate'=>$allowlate, 'id'=>$aid));
		} else {
			//DB $r = mysql_fetch_row($result);
			$r = $stm->fetch(PDO::FETCH_NUM);
			list($useexception, $canundolatepass, $canuselatepass) = $exceptionfuncs->getCanUseAssessException($r, array('startdate'=>$startdate, 'enddate'=>$enddate, 'allowlate'=>$allowlate, 'id'=>$aid));
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

		if ($canuselatepass) {
			//DB $query = "UPDATE imas_students SET latepass=latepass-1 WHERE userid='$userid' AND courseid='$cid' AND latepass>0";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB if (mysql_affected_rows()>0) {
			$stm = $DBH->prepare("UPDATE imas_students SET latepass=latepass-1 WHERE userid=:userid AND courseid=:courseid AND latepass>0");
			$stm->execute(array(':userid'=>$userid, ':courseid'=>$cid));
			if ($stm->rowCount()>0) {
				$enddate = min(strtotime("+".$latepasshrs." hours", $thised), $courseenddate);
				if ($hasexception) { //already have exception
					//DB $query = "UPDATE imas_exceptions SET enddate=enddate+$addtime,islatepass=islatepass+1 WHERE userid='$userid' AND assessmentid='$aid' AND itemtype='A'";
					//DB mysql_query($query) or die("Query failed : " . mysql_error());
					$stm = $DBH->prepare("UPDATE imas_exceptions SET enddate=:enddate,islatepass=islatepass+1 WHERE userid=:userid AND assessmentid=:assessmentid AND itemtype='A'");
					$stm->execute(array(':userid'=>$userid, ':assessmentid'=>$aid, ':enddate'=>$enddate));
				} else {

					//DB $query = "INSERT INTO imas_exceptions (userid,assessmentid,startdate,enddate,islatepass,itemtype) VALUES ('$userid','$aid','$startdate','$enddate',1,'A')";
					//DB mysql_query($query) or die("Query failed : " . mysql_error());
					$stm = $DBH->prepare("INSERT INTO imas_exceptions (userid,assessmentid,startdate,enddate,islatepass,itemtype) VALUES (:userid, :assessmentid, :startdate, :enddate, :islatepass, :itemtype)");
					$stm->execute(array(':userid'=>$userid, ':assessmentid'=>$aid, ':startdate'=>$startdate, ':enddate'=>$enddate, ':islatepass'=>1, ':itemtype'=>'A'));
				}
			}
		}
		if ($from=='ltitimelimit') {
			header('Location: ' . $GLOBALS['basesiteurl'] . "/bltilaunch.php?accessibility=ask" . "&r=" . Sanitize::randomQueryStringParam());
		} else if ($from=='gb') {
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/gradebook.php?cid=$cid" . "&r=" . Sanitize::randomQueryStringParam());
		} else if ((!isset($sessiondata['ltiitemtype']) || $sessiondata['ltiitemtype']!=0)) {
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?cid=$cid" . "&r=" . Sanitize::randomQueryStringParam());
		} else {
			header('Location: ' . $GLOBALS['basesiteurl'] . "/assessment/showtest.php?cid=$cid&id={$sessiondata['ltiitemid']}" . "&r=" . Sanitize::randomQueryStringParam());
		}
	} else {
		require("../header.php");
		echo "<div class=breadcrumb>";
		if ($from != 'ltitimelimit') {
			echo "$breadcrumbbase ";
		}
		if ($cid>0 && (!isset($sessiondata['ltiitemtype']) || $sessiondata['ltiitemtype']!=0)) {
			echo " <a href=\"../course/course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
		}
		if ($from == 'gb') {
			echo ' <a href="gradebook.php?cid='.$cid.'">'._('Gradebook').'</a> &gt; ';
		}
		echo "Redeem LatePass</div>\n";
		//$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\"> $coursename</a>\n";
		//$curBreadcrumb .= " Redeem LatePass\n";
		//echo "<div class=\"breadcrumb\">$curBreadcrumb</div>";

		//DB $query = "SELECT allowlate,enddate,startdate FROM imas_assessments WHERE id='$aid'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB list($allowlate,$enddate,$startdate) =mysql_fetch_row($result);
		$stm = $DBH->prepare("SELECT allowlate,enddate,startdate,timelimit FROM imas_assessments WHERE id=:id");
		$stm->execute(array(':id'=>$aid));
		list($allowlate,$enddate,$startdate,$timelimit) =$stm->fetch(PDO::FETCH_NUM);

		//DB $query = "SELECT enddate,islatepass FROM imas_exceptions WHERE userid='$userid' AND assessmentid='$aid' AND itemtype='A'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		$stm = $DBH->prepare("SELECT startdate,enddate,islatepass,is_lti FROM imas_exceptions WHERE userid=:userid AND assessmentid=:assessmentid AND itemtype='A'");
		$stm->execute(array(':userid'=>$userid, ':assessmentid'=>$aid));
		$hasexception = false;
		//DB if (mysql_num_rows($result)==0) {
		if ($stm->rowCount()==0) {
			$usedlatepasses = 0;
			$thised = $enddate;
			$canuselatepass = $exceptionfuncs->getCanUseAssessLatePass(array('startdate'=>$startdate, 'enddate'=>$enddate, 'allowlate'=>$allowlate, 'id'=>$aid));
		} else {
			//DB $r = mysql_fetch_row($result);
			$r = $stm->fetch(PDO::FETCH_NUM);
			list($useexception, $canundolatepass, $canuselatepass) = $exceptionfuncs->getCanUseAssessException($r, array('startdate'=>$startdate, 'enddate'=>$enddate, 'allowlate'=>$allowlate, 'id'=>$aid));
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
		$limitedByCourseEnd = (strtotime("+".$latepasshrs." hours", $thised) > $courseenddate);
		$timelimitstatus = $exceptionfuncs->getTimelimitStatus($aid);

		if ($latepasses==0) { //shouldn't get here if 0
			echo "<p>You have no late passes remaining.</p>";
		} else if ($canuselatepass) {
			echo '<div id="headerredeemlatepass" class="pagetitle"><h2>Redeem LatePass</h2></div>';
			echo "<form method=post action=\"redeemlatepass.php?cid=$cid&aid=$aid\">";
			if ($allowlate%10>1) {
			    echo '<p>You may use up to '.Sanitize::onlyInt($allowlate%10-1-$usedlatepasses).' more LatePass(es) on this assessment.</p>';
			}
			echo "<p>You have ".Sanitize::encodeStringForDisplay($latepasses)." LatePass(es) remaining.  ";
			if ($limitedByCourseEnd) {
				echo sprintf("You can redeem one LatePass for an extension up to the course end date, %s. ", tzdate("D n/j/y, g:i a", $courseenddate));
			} else {
				echo "You can redeem one LatePass for a ".Sanitize::encodeStringForDisplay($latepasshrs)." hour extension on this assessment. ";
			}
			echo "Are you sure you want to redeem a LatePass?</p>";
			if ($timelimitstatus=='started') {
				echo '<p class="noticetext">'._('Reminder: You have already started this assessment, and it has a time limit.  Using a LatePass does <b>not</b> extend or pause the time limit, only the due date.').'</p>';
			} else if ($timelimitstatus=='expired') {
				echo '<p class="noticetext">'._('Your time limit has expired on this assessment.  Using a LatePass does <b>not</b> extend the time limit, so there is no reason to use a LatePass.').'</p>';
			}
			echo '<p><input type="hidden" name="confirm" value="true" />';
			echo '<input type="hidden" name="from" value="'.Sanitize::encodeStringForDisplay($from).'" />';
			if ($timelimitstatus!='expired') {
				echo "<input type=submit value=\"Yes, Redeem LatePass\"/>";
			}
			if ($from=='ltitimelimit') {
				echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='../bltilaunch.php?accessibility=ask'\"/>";
			} else if ($from=='gb') {
				echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='gradebook.php?cid=$cid'\"/>";	
			} else if ((!isset($sessiondata['ltiitemtype']) || $sessiondata['ltiitemtype']!=0)) {
				echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='course.php?cid=$cid'\"/>";
			} else {
				echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='../assessment/showtest.php?cid=$cid&id={$sessiondata['ltiitemid']}'\"/>";
			}
			echo "</p></form>";
		} else {
			echo "<p>You are not allowed to use additional latepasses on this assessment.</p>";
		}
		require("../footer.php");
	}

?>

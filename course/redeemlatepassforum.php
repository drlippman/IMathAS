<?php
//IMathAS:  Redeem latepasses
//(c) 2007 David Lippman

	require("../init.php");

	$cid = Sanitize::courseId($_GET['cid']);
	$fid = Sanitize::onlyInt($_GET['fid']);
	$from = $_GET['from'] ?? '';

	require("../includes/exceptionfuncs.php");
	if (isset($studentid) && !isset($_SESSION['stuview'])) {
		$exceptionfuncs = new ExceptionFuncs($userid, $cid, true, $studentinfo['latepasses'], $latepasshrs);
	} else {
		$exceptionfuncs = new ExceptionFuncs($userid, $cid, false);
	}

	$stm = $DBH->prepare("SELECT allowlate,postby,replyby,enddate FROM imas_forums WHERE id=:id");
	$stm->execute(array(':id'=>$fid));
	$fdata = $stm->fetch(PDO::FETCH_ASSOC);
	$allowlate = $fdata['allowlate'];
	$postby = $fdata['postby'];
	$replyby = $fdata['replyby'];

	$allowlaten = $allowlate%10; //allowlateon:  0: both together, 1: both separate (not used), 2: posts, 3: replies
	$allowlateon = floor($allowlate/10)%10;

	$now = time();

	if (isset($_GET['undo'])) {
		require("../header.php");
		echo "<div class=breadcrumb>$breadcrumbbase ";
		if ($cid>0 && (!isset($_SESSION['ltiitemtype']) || $_SESSION['ltiitemtype']!=0)) {
			echo " <a href=\"../course/course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
		}
		echo "Un-use LatePass</div>";
		$stm = $DBH->prepare("SELECT startdate,enddate,islatepass,waivereqscore,itemtype FROM imas_exceptions WHERE userid=:userid AND assessmentid=:assessmentid AND (itemtype='F' OR itemtype='R' OR itemtype='P')");
		$stm->execute(array(':userid'=>$userid, ':assessmentid'=>$fid));
		if ($stm->rowCount()==0) {
			echo '<p>Invalid</p>';
		} else {
			$row = $stm->fetch(PDO::FETCH_NUM);
			if ($row[2]==0) {
				echo '<p>Invalid</p>';
			} else {
				$now = time();

				//allowlateon:  0: both together, 1: both separate (not used), 2: posts, 3: replies
				//if it's past original due date and latepass is for less than latepasshrs past now, too late
				$postn = 0; $replyn = 0; $newpostend = 0; $newreplyend = 0;
				if ($row[4]=='F' && $row[0]==0) { //only extended reply - treat like reply exception
					$row[4] = 'R';
				} else if ($row[4]=='F' && $row[1]==0) { //only extended reply - treat like reply exception
					$row[4] = 'P';
				}
				if ($row[4]=='F' || $row[4]=='P') {
					if ($now > $postby && $row[0] < strtotime("+".$latepasshrs." hours",$now)) {
						$postn = 0;  //too late to un-use
					} else if ($now < $postby) {//before postbydate, return all latepasses
						$postn = $row[2];
					} else {
						$postn = floor(($row[0] - $now)/($latepasshrs*60*60) + .05);  //if ==$row[2] then returning all
					}
				}
				if ($row[4]=='F' || $row[4]=='R') {
					if ($now > $replyby && $row[1] < strtotime("+".$latepasshrs." hours",$now)) {
						$replyn = 0;  //too late to un-use
					} else if ($now < $replyby) {//before replybydate, return all latepasses
						$replyn = $row[2];
					} else {
						$replyn = floor(($row[1] - $now)/($latepasshrs*60*60) + .05);  //if ==$row[2] then returning all
					}
				}

				if ($postn==0 && $replyn==0) {
					echo '<p>Too late to un-use this LatePass</p>';
				} else {
					if (($row[4]=='F' && $postn==$row[2] && $replyn==$row[2]) || ($row[4]=='R' && $replyn==$row[2]) || ($row[4]=='P' && $postn==$row[2])) {
						//returning all the latepasses
						$stm = $DBH->prepare("DELETE FROM imas_exceptions WHERE userid=:userid AND assessmentid=:assessmentid AND (itemtype='F' OR itemtype='R' OR itemtype='P')");
						$stm->execute(array(':userid'=>$userid, ':assessmentid'=>$fid));
						$toreturn = $row[2];
					} else {
						if ($row[4]=='F') {
							$toreturn = min($postn,$replyn);
							$tothrs = $toreturn*$latepasshrs;
							$newpostend = strtotime("-".$tothrs." hours", $row[0]); // - $toreturn*$latepasshrs*60*60;
							$newreplyend = strtotime("-".$tothrs." hours", $row[1]); //$row[1] - $toreturn*$latepasshrs*60*60;
						} else if ($row[4]=='R') {
							$toreturn = $replyn;
							$tothrs = $toreturn*$latepasshrs;
							$newreplyend = strtotime("-".$tothrs." hours", $row[1]); //$row[1] - $replyn*$latepasshrs*60*60;
						} else if ($row[4]=='P') {
							$toreturn = $postn;
							$tothrs = $toreturn*$latepasshrs;
							$newpostend = strtotime("-".$tothrs." hours", $row[0]); //$row[0] - $postn*$latepasshrs*60*60;
						}
						if ($postby==2000000000) {
							$newpostend = $postby;
						}
						if ($replyby==2000000000) {
							$newreplyend = $replyby;
						}
						$stm = $DBH->prepare("UPDATE imas_exceptions SET islatepass=islatepass-:toreturn,startdate=:startdate,enddate=:enddate WHERE userid=:userid AND assessmentid=:assessmentid AND (itemtype='F' OR itemtype='R' OR itemtype='P')");
						$stm->execute(array(':startdate'=>$newpostend, ':enddate'=>$newreplyend, ':userid'=>$userid, ':assessmentid'=>$fid, ':toreturn'=>$toreturn));
					}
					echo "<p>Returning ".Sanitize::encodeStringForDisplay($toreturn)." LatePass".($toreturn>1?"es":"")."</p>";
					$stm = $DBH->prepare("UPDATE imas_students SET latepass=latepass+:toreturn WHERE userid=:userid AND courseid=:courseid");
					$stm->execute(array(':userid'=>$userid, ':courseid'=>$cid, ':toreturn'=>$toreturn));
				}
			}
		}
		if ($from=='forum') {
			echo "<p><a href=\"../forums/thread.php?cid=".Sanitize::courseId($cid)."&forum=".Sanitize::onlyInt($fid)."\">Continue</a></p>";
		} else {
			echo "<p><a href=\"course.php?cid=".Sanitize::courseId($cid)."\">Continue</a></p>";
		}

		require("../footer.php");

	} else if (isset($_POST['confirm'])) {

		$stm = $DBH->prepare("SELECT startdate,enddate,islatepass,waivereqscore,itemtype FROM imas_exceptions WHERE userid=:userid AND assessmentid=:assessmentid AND (itemtype='F' OR itemtype='R' OR itemtype='P')");
		$stm->execute(array(':userid'=>$userid, ':assessmentid'=>$fid));
		$hasexception = false;
		$usedlatepassespost = 0; $usedlatepassesreply = 0;

		if ($stm->rowCount()==0) { //no existing exception
			$usedlatepasses = 0;
			$thispostby = $postby;
			$thisreplyby = $replyby;
			if ($allowlateon==0) {
				$itemtype = 'F';
			} else if ($allowlateon==2) {
				$itemtype = 'P';
			} else if ($allowlateon==3) {
				$itemtype = 'R';
			}
			list($canundolatepassP, $canundolatepassR, $canundolatepass, $canuselatepassP, $canuselatepassR, $thispostby, $thisreplyby, $ed) = $exceptionfuncs->getCanUseLatePassForums(null, $fdata);
			$hasexception = false;
		} else {
			$r = $stm->fetch(PDO::FETCH_NUM);
			list($canundolatepassP, $canundolatepassR, $canundolatepass, $canuselatepassP, $canuselatepassR, $thispostby, $thisreplyby, $ed) = $exceptionfuncs->getCanUseLatePassForums($r, $fdata);
			$itemtype = $r[4];
			$hasexception = true;
		}

		$LPneededP = 0; $LPneededR = 0;
		if ($canuselatepassP) {
			if ($now>$thispostby) {
				$LPneededP = $exceptionfuncs->calcLPneeded($thispostby);
			} else {
				$LPneededP = 1;
			}
		}
		if ($canuselatepassR) {
			if ($now>$thisreplyby) {
				$LPneededR = $exceptionfuncs->calcLPneeded($thisreplyby);
			} else {
				$LPneededR = 1;
			}
		}
		$LPneeded = max($LPneededP,$LPneededR);
		if (isset($_POST['numtoredeem']) && intval($_POST['numtoredeem'])<=$LPneeded) {
			$LPneeded = Sanitize::onlyInt($_POST['numtoredeem']);
		}

		if ($canuselatepassP || $canuselatepassR) {
			$newpostby = $thispostby; $newreplyby = $thisreplyby;
			if ($canuselatepassP) {
				$newpostby = min(strtotime("+".($latepasshrs*$LPneeded)." hours", $thispostby), $courseenddate);
			}
			if ($canuselatepassR) {
				$newreplyby = min(strtotime("+".($latepasshrs*$LPneeded)." hours", $thisreplyby), $courseenddate);
			}

			$stm = $DBH->prepare("UPDATE imas_students SET latepass=latepass-:lps WHERE userid=:userid AND courseid=:courseid AND latepass>=:lps2");
			$stm->execute(array(':lps'=>$LPneeded, ':lps2'=>$LPneeded, ':userid'=>$userid, ':courseid'=>$cid));
			if ($stm->rowCount()>0) {
				//start date is postby
				//end date is replyby
				if ($hasexception) { //already have exception
					$stm = $DBH->prepare("UPDATE imas_exceptions SET startdate=:startdate,enddate=:enddate,islatepass=islatepass+:lps WHERE userid=:userid AND assessmentid=:assessmentid AND itemtype=:itemtype");
					$stm->execute(array(':lps'=>$LPneeded, ':userid'=>$userid, ':assessmentid'=>$fid, ':startdate'=>$newpostby, ':enddate'=>$newreplyby, ':itemtype'=>$itemtype));
				} else {
					$stm = $DBH->prepare("INSERT INTO imas_exceptions (userid,assessmentid,startdate,enddate,islatepass,itemtype) VALUES (:userid, :assessmentid, :startdate, :enddate, :islatepass, :itemtype)");
					$stm->execute(array(':userid'=>$userid, ':assessmentid'=>$fid, ':startdate'=>$newpostby, ':enddate'=>$newreplyby, ':islatepass'=>$LPneeded, ':itemtype'=>$itemtype));
				}
			}
		}
		/* old code

		//start date is postby
		//end date is replyby
		if ($stm->rowCount()==0) {
			$thispostby = $postby;
			$thisreplyby = $replyby;
			if ($allowlateon==0) {
				$itemtype = 'F';
			} else if ($allowlateon==2) {
				$itemtype = 'P';
			} else if ($allowlateon==3) {
				$itemtype = 'R';
			}
			$startdate = 0;
			$enddate = 0;
		} else {
			$hasexception = true;
			$r = $stm->fetch(PDO::FETCH_NUM);
			$usedlatepassespost = min(max(0,round(($r[0] - $postby)/($latepasshrs*3600) + .05)), $r[2]);
			$thispostby = $r[0];
			$usedlatepassesreply = min(max(0,round(($r[1] - $replyby)/($latepasshrs*3600) + .05)), $r[2]);
			$thisreplyby = $r[1];
			$itemtype = $r[3];
			$startdate = $thispostby;
			$enddate = $thisreplyby;
		}

		$addtimepost = 0; $addtimereply = 0;
		if (($itemtype=='F' || $itemtype=='P') && $postby<2000000000 && $postby>0 && ($allowlaten==1 || $allowlaten-1>$usedlatepassespost) && ($now<$thispostby || ($allowlate>100 && $now < strtotime("+".$latepasshrs." hours", $thispostby)))) {
			$addtimepost = $latepasshrs;
			$startdate = strtotime("+".$latepasshrs." hours",$thispostby);
		}
		if (($itemtype=='F' || $itemtype=='R') && $replyby<2000000000 && $replyby>0 && ($allowlaten==1 || $allowlaten-1>$usedlatepassesreply) && ($now<$thisreplyby || ($allowlate>100 && $now < strtotime("+".$latepasshrs." hours", $thisreplyby)))) {
			$addtimereply = $latepasshrs;
			$enddate = strtotime("+".$latepasshrs." hours",$thisreplyby);
		}
		if ($addtimepost>0 || $addtimereply>0) {
			if ($hasexception) {
				$stm = $DBH->prepare("UPDATE imas_exceptions SET startdate=:startdate,enddate=:enddate,islatepass=islatepass+1 WHERE userid=:userid AND assessmentid=:assessmentid AND itemtype=:itemtype");
				$stm->execute(array(':userid'=>$userid, ':assessmentid'=>$fid, ':itemtype'=>$itemtype, ':startdate'=>$startdate, ':enddate'=>$enddate));
			} else {
				$stm = $DBH->prepare("INSERT INTO imas_exceptions (userid,assessmentid,startdate,enddate,islatepass,itemtype) VALUES (:userid, :assessmentid, :startdate, :enddate, :islatepass, :itemtype)");
				$stm->execute(array(':userid'=>$userid, ':assessmentid'=>$fid, ':startdate'=>$startdate, ':enddate'=>$enddate, ':islatepass'=>1, ':itemtype'=>$itemtype));
			}
		}
		*/
		if ($from=='forum') {
			header('Location: ' . $GLOBALS['basesiteurl'] . "/forums/thread.php?cid=".Sanitize::courseId($cid)."&forum=".Sanitize::onlyInt($fid) . "&r=" . Sanitize::randomQueryStringParam());
		} else {
			$btf = isset($_GET['btf']) ? '&folder=' . Sanitize::encodeUrlParam($_GET['btf']) : '';
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?cid=".Sanitize::courseId($cid) .$btf. "&r=" . Sanitize::randomQueryStringParam());
		}

	} else {
		//TO HERE - TODO keep going
		require("../header.php");
		echo "<div class=breadcrumb>$breadcrumbbase ";
		if ($cid>0 && (!isset($_SESSION['ltiitemtype']) || $_SESSION['ltiitemtype']!=0)) {
			echo " <a href=\"../course/course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
		}
		echo "Redeem LatePass</div>\n";

		$numlatepass = $studentinfo['latepasses'];

		$stm = $DBH->prepare("SELECT startdate,enddate,islatepass,waivereqscore,itemtype FROM imas_exceptions WHERE userid=:userid AND assessmentid=:assessmentid AND (itemtype='F' OR itemtype='R' OR itemtype='P')");
		$stm->execute(array(':userid'=>$userid, ':assessmentid'=>$fid));
		$hasexception = false;
		$usedlatepassespost = 0; $usedlatepassesreply = 0;
		if ($stm->rowCount()==0) {
			if ($allowlateon==0) {
				$itemtype = 'F';
			} else if ($allowlateon==2) {
				$itemtype = 'P';
			} else if ($allowlateon==3) {
				$itemtype = 'R';
			}
			list($canundolatepassP, $canundolatepassR, $canundolatepass, $canuselatepasspost, $canuselatepassreply, $thispostby, $thisreplyby, $ed) = $exceptionfuncs->getCanUseLatePassForums(null, $fdata);

		} else {
			$hasexception = true;
			$r = $stm->fetch(PDO::FETCH_NUM);
			$usedlatepassespost = min(max(0,round(($r[0] - $postby)/($latepasshrs*3600) + .05)), $r[2]);
			$usedlatepassesreply = min(max(0,round(($r[1] - $replyby)/($latepasshrs*3600) + .05)), $r[2]);
			list($canundolatepassP, $canundolatepassR, $canundolatepass, $canuselatepasspost, $canuselatepassreply, $thispostby, $thisreplyby, $ed) = $exceptionfuncs->getCanUseLatePassForums($r, $fdata);
			$itemtype = $r[4];
		}

		if ($canuselatepasspost && $now>$thispostby) {
			$LPneededP = $exceptionfuncs->calcLPneeded($thispostby);
		} else {
			$LPneededP = 1;
		}
		if ($now>$thisreplyby && $canuselatepassreply) {
			$LPneededR = $exceptionfuncs->calcLPneeded($thisreplyby);
		} else {
			$LPneededR = 1;
		}
		$LPneeded = max($LPneededR,$LPneededP);
		$limitedByCourseEnd = false;
		if ($canuselatepasspost) {
			$limitedByCourseEnd = (strtotime("+".($latepasshrs*$LPneeded)." hours", $thispostby) > $courseenddate);
		}
		if ($canuselatepassreply) {
			$limitedByCourseEnd = $limitedByCourseEnd || (strtotime("+".($latepasshrs*$LPneeded)." hours", $thisreplyby) > $courseenddate);
		}

		if ($numlatepass==0) { //shouldn't get here if 0
			echo "<p>You have no late passes remaining.</p>";
		} else if ($canuselatepasspost || $canuselatepassreply) {
			echo '<div id="headerredeemlatepass" class="pagetitle"><h1>Redeem LatePass</h1></div>';
			echo "<form method=post action=\"redeemlatepassforum.php?cid=".Sanitize::courseId($cid)."&fid=".Sanitize::onlyInt($fid)."&from=".Sanitize::encodeUrlParam($from)."\">";

			echo "<p>You have ".Sanitize::onlyInt($numlatepass)." LatePass(es) remaining.</p>";

			$extendwhat = '';
			if ($canuselatepasspost) {
				$extendwhat .= " the <b>New Threads</b> due date ";
				if ($canuselatepassreply) {
					$extendwhat .= "and";
				}
			}
			if ($canuselatepassreply) {
				$extendwhat .= " the <b>Replies</b> due date ";
			}
			if ($LPneeded==1) {
				echo '<p>';
				if ($limitedByCourseEnd) {
					echo sprintf("You can redeem one LatePass for an extension on $extendwhat up to the course end date, %s. ", tzdate("D n/j/y, g:i a", $courseenddate));
				} else {
					echo "You can redeem one LatePass for a ".Sanitize::encodeStringForDisplay($latepasshrs)." hour extension on ";
					echo "$extendwhat for this forum assignment.";
				}
				echo "</p><p>Are you sure you want to redeem a LatePass?</p>";
				echo "<input type=submit value=\"Yes, Redeem LatePass\"/> ";
			} else {
				echo "<p>Each LatePass gives a ".Sanitize::encodeStringForDisplay($latepasshrs)." hour extension.</p><p> ";
				if (!$canuselatepasspost || !$canuselatepassreply || $LPneededP==$LPneededR) { //only one option
					if ($limitedByCourseEnd) {
						echo sprintf("You would need %d LatePasses to extend $extendwhat up to the course end date, %s. ", $LPneeded, tzdate("D n/j/y, g:i a", $courseenddate));
					} else {
						echo "You would need $LPneeded LatePasses to extend $extendwhat for this forum assignment. ";
					}
					echo "</p><p>Are you sure you want to redeem $LPneeded LatePasses?</p>";
					echo "<input type=submit value=\"Yes, Redeem LatePasses\"/> ";
				} else {
					if ($canuselatepasspost) {
						if ($limitedByCourseEnd) {
							echo sprintf("You would need %d LatePasses to extend the <b>New Threads</b> due date up to the course end date, %s. ", $LPneededP, tzdate("D n/j/y, g:i a", $courseenddate));
						} else {
							echo "You would need $LPneededP LatePasses to extend the <b>New Threads</b> due date for this forum assignment. ";
						}
						echo "<p><button type=submit name=numtoredeem value=$LPneededP>Redeem $LPneededP LatePass(es)</button></p>";
					}
					if ($canuselatepassreply) {
						if ($limitedByCourseEnd) {
							echo sprintf("You would need %d LatePasses to extend the <b>Replies</b> due date up to the course end date, %s. ", $LPneededR, tzdate("D n/j/y, g:i a", $courseenddate));
						} else {
							echo "You would need $LPneededR LatePasses to extend the <b>Replies</b> due date for this forum assignment. ";
						}
						echo "<p><button type=submit name=numtoredeem value=$LPneededR>Redeem $LPneededR LatePass(es)</button></p>";
					}
				}
			}
			echo '<input type="hidden" name="confirm" value="true" />';
			if ($from=='forum') {
				echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='../forums/thread.php?cid=".Sanitize::courseId($cid)."&forum=".Sanitize::onlyInt($fid)."'\"/>";
			} else {
				echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='course.php?cid=".Sanitize::courseId($cid)."'\"/>";

			}

			echo "</form>";
		} else {
			echo "<p>You are not allowed to use additional latepasses on this forum assignment.</p>";
		}
		require("../footer.php");
	}

?>

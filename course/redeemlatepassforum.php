<?php
//IMathAS:  Redeem latepasses
//(c) 2007 David Lippman

	require("../init.php");


	$cid = Sanitize::courseId($_GET['cid']);
	$fid = Sanitize::onlyInt($_GET['fid']);
	$from = $_GET['from'];

	//DB $query = "SELECT latepasshrs FROM imas_courses WHERE id='$cid'";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB $hours = mysql_result($result,0,0);
	$stm = $DBH->prepare("SELECT latepasshrs FROM imas_courses WHERE id=:id");
	$stm->execute(array(':id'=>$cid));
	$hours = $stm->fetchColumn(0);
	$now = time();

	if (isset($_GET['undo'])) {
		require("../header.php");
		echo "<div class=breadcrumb>$breadcrumbbase ";
		if ($cid>0 && (!isset($sessiondata['ltiitemtype']) || $sessiondata['ltiitemtype']!=0)) {
			echo " <a href=\"../course/course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
		}
		echo "Un-use LatePass</div>";
		//DB $query = "SELECT startdate,enddate,islatepass,itemtype FROM imas_exceptions WHERE userid='$userid' AND assessmentid='$fid' AND (itemtype='F' OR itemtype='R' OR itemtype='P')";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB if (mysql_num_rows($result)==0) {
		$stm = $DBH->prepare("SELECT startdate,enddate,islatepass,itemtype FROM imas_exceptions WHERE userid=:userid AND assessmentid=:assessmentid AND (itemtype='F' OR itemtype='R' OR itemtype='P')");
		$stm->execute(array(':userid'=>$userid, ':assessmentid'=>$fid));
		if ($stm->rowCount()==0) {
			echo '<p>Invalid</p>';
		} else {
			//DB $row = mysql_fetch_row($result);
			$row = $stm->fetch(PDO::FETCH_NUM);
			if ($row[2]==0) {
				echo '<p>Invalid</p>';
			} else {
				$now = time();
				//DB $query = "SELECT allowlate,postby,replyby FROM imas_forums WHERE id='$fid'";
				//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
				//DB list($allowlate,$postby,$replyby) = mysql_fetch_row($result);
				$stm = $DBH->prepare("SELECT allowlate,postby,replyby FROM imas_forums WHERE id=:id");
				$stm->execute(array(':id'=>$fid));
				list($allowlate,$postby,$replyby) = $stm->fetch(PDO::FETCH_NUM);
				$allowlaten = $allowlate%10;
				$allowlateon = floor($allowlate/10)%10;
				//allowlateon:  0: both together, 1: both separate (not used), 2: posts, 3: replies
				//if it's past original due date and latepass is for less than latepasshrs past now, too late
				$postn = 0; $replyn = 0; $newpostend = 0; $newreplyend = 0;
				if ($row[3]=='F' && $row[0]==0) { //only extended reply - treat like reply exception
					$row[3] = 'R';
				} else if ($row[3]=='F' && $row[1]==0) { //only extended reply - treat like reply exception
					$row[3] = 'P';
				}
				if ($row[3]=='F' || $row[3]=='P') {
					if ($now > $postby && $row[0] < strtotime("+".$hours." hours",$now)) {
						$postn = 0;  //too late to un-use
					} else if ($now < $postby) {//before postbydate, return all latepasses
						$postn = $row[2];
					} else {
						$postn = floor(($row[0] - $now)/($hours*60*60) + .05);  //if ==$row[2] then returning all
					}
				}
				if ($row[3]=='F' || $row[3]=='R') {
					if ($now > $replyby && $row[1] < strtotime("+".$hours." hours",$now)) {
						$replyn = 0;  //too late to un-use
					} else if ($now < $replyby) {//before replybydate, return all latepasses
						$replyn = $row[2];
					} else {
						$replyn = floor(($row[1] - $now)/($hours*60*60) + .05);  //if ==$row[2] then returning all
					}
				}

				if ($postn==0 && $replyn==0) {
					echo '<p>Too late to un-use this LatePass</p>';
				} else {
					if (($row[3]=='F' && $postn==$row[2] && $replyn==$row[2]) || ($row[3]=='R' && $replyn==$row[2]) || ($row[3]=='P' && $postn==$row[2])) {
						//returning all the latepasses
						//DB $query = "DELETE FROM imas_exceptions WHERE userid='$userid' AND assessmentid='$fid' AND (itemtype='F' OR itemtype='R' OR itemtype='P')";
						//DB mysql_query($query) or die("Query failed : " . mysql_error());
						$stm = $DBH->prepare("DELETE FROM imas_exceptions WHERE userid=:userid AND assessmentid=:assessmentid AND (itemtype='F' OR itemtype='R' OR itemtype='P')");
						$stm->execute(array(':userid'=>$userid, ':assessmentid'=>$fid));
						$toreturn = $row[2];
					} else {
						if ($row[3]=='F') {
							$toreturn = min($postn,$replyn);
							$tothrs = $toreturn*$hours;
							$newpostend = strtotime("-".$tothrs." hours", $row[0]); // - $toreturn*$hours*60*60;
							$newreplyend = strtotime("-".$tothrs." hours", $row[1]); //$row[1] - $toreturn*$hours*60*60;
						} else if ($row[3]=='R') {
							$toreturn = $replyn;
							$tothrs = $toreturn*$hours;
							$newreplyend = strtotime("-".$tothrs." hours", $row[1]); //$row[1] - $replyn*$hours*60*60;
						} else if ($row[3]=='P') {
							$toreturn = $postn;
							$tothrs = $toreturn*$hours;
							$newpostend = strtotime("-".$tothrs." hours", $row[0]); //$row[0] - $postn*$hours*60*60;
						}
						//DB $query = "UPDATE imas_exceptions SET islatepass=islatepass-$toreturn,startdate=$newpostend,enddate=$newreplyend WHERE userid='$userid' AND assessmentid='$fid' AND (itemtype='F' OR itemtype='R' OR itemtype='P')";
						//DB mysql_query($query) or die("Query failed : " . mysql_error());
						$stm = $DBH->prepare("UPDATE imas_exceptions SET islatepass=islatepass-:toreturn,startdate=:startdate,enddate=:enddate WHERE userid=:userid AND assessmentid=:assessmentid AND (itemtype='F' OR itemtype='R' OR itemtype='P')");
						$stm->execute(array(':startdate'=>$newpostend, ':enddate'=>$newreplyend, ':userid'=>$userid, ':assessmentid'=>$fid, ':toreturn'=>$toreturn));
					}
					echo "<p>Returning ".Sanitize::encodeStringForDisplay($toreturn)." LatePass".($toreturn>1?"es":"")."</p>";
					//DB $query = "UPDATE imas_students SET latepass=latepass+$toreturn WHERE userid='$userid' AND courseid='$cid'";
					//DB mysql_query($query) or die("Query failed : $query " . mysql_error());
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

		//DB $query = "SELECT allowlate,postby,replyby FROM imas_forums WHERE id='$fid'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB list($allowlate,$postby,$replyby) = mysql_fetch_row($result);
		$stm = $DBH->prepare("SELECT allowlate,postby,replyby FROM imas_forums WHERE id=:id");
		$stm->execute(array(':id'=>$fid));
		list($allowlate,$postby,$replyby) = $stm->fetch(PDO::FETCH_NUM);
		$allowlaten = $allowlate%10;
		$allowlateon = floor($allowlate/10)%10;

		//DB $query = "SELECT startdate,enddate,islatepass,itemtype FROM imas_exceptions WHERE userid='$userid' AND assessmentid='$fid' AND (itemtype='F' OR itemtype='R' OR itemtype='P')";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		$stm = $DBH->prepare("SELECT startdate,enddate,islatepass,itemtype FROM imas_exceptions WHERE userid=:userid AND assessmentid=:assessmentid AND (itemtype='F' OR itemtype='R' OR itemtype='P')");
		$stm->execute(array(':userid'=>$userid, ':assessmentid'=>$fid));
		$hasexception = false;
		$usedlatepassespost = 0; $usedlatepassesreply = 0;
		
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
			//DB $r = mysql_fetch_row($result);
			$r = $stm->fetch(PDO::FETCH_NUM);
			$usedlatepassespost = min(max(0,round(($r[0] - $postby)/($hours*3600) + .05)), $r[2]);
			$thispostby = $r[0];
			$usedlatepassesreply = min(max(0,round(($r[1] - $replyby)/($hours*3600) + .05)), $r[2]);
			$thisreplyby = $r[1];
			$itemtype = $r[3];
			$startdate = $thispostby;
			$enddate = $thisreplyby;
		}

		$addtimepost = 0; $addtimereply = 0;												
		if (($itemtype=='F' || $itemtype=='P') && $postby<2000000000 && $postby>0 && ($allowlaten==1 || $allowlaten-1>$usedlatepassespost) && ($now<$thispostby || ($allowlate>100 && $now < strtotime("+".$hours." hours", $thispostby)))) {
			$addtimepost = $hours;
			$startdate = strtotime("+".$hours." hours",$thispostby); 
		}																
		if (($itemtype=='F' || $itemtype=='R') && $replyby<2000000000 && $replyby>0 && ($allowlaten==1 || $allowlaten-1>$usedlatepassesreply) && ($now<$thisreplyby || ($allowlate>100 && $now < strtotime("+".$hours." hours", $thisreplyby)))) {
			$addtimereply = $hours;
			$enddate = strtotime("+".$hours." hours",$thisreplyby);
		}
		if ($addtimepost>0 || $addtimereply>0) {
			if ($hasexception) {
				//DB $query = "UPDATE imas_exceptions SET startdate=startdate+$addtimepost,enddate=enddate+$addtimereply,islatepass=islatepass+1 WHERE userid='$userid' AND assessmentid='$fid' AND itemtype='$itemtype'";
				//DB mysql_query($query) or die("Query failed : " . mysql_error());
				$stm = $DBH->prepare("UPDATE imas_exceptions SET startdate=:startdate,enddate=:enddate,islatepass=islatepass+1 WHERE userid=:userid AND assessmentid=:assessmentid AND itemtype=:itemtype");
				$stm->execute(array(':userid'=>$userid, ':assessmentid'=>$fid, ':itemtype'=>$itemtype, ':startdate'=>$startdate, ':enddate'=>$enddate));
			} else {
				//DB $query = "INSERT INTO imas_exceptions (userid,assessmentid,startdate,enddate,islatepass,itemtype) VALUES ('$userid','$fid','$startdate','$enddate',1,'$itemtype')";
				//DB mysql_query($query) or die("Query failed : " . mysql_error());
				$stm = $DBH->prepare("INSERT INTO imas_exceptions (userid,assessmentid,startdate,enddate,islatepass,itemtype) VALUES (:userid, :assessmentid, :startdate, :enddate, :islatepass, :itemtype)");
				$stm->execute(array(':userid'=>$userid, ':assessmentid'=>$fid, ':startdate'=>$startdate, ':enddate'=>$enddate, ':islatepass'=>1, ':itemtype'=>$itemtype));
			}
		}

		if ($from=='forum') {
			header('Location: ' . $GLOBALS['basesiteurl'] . "/forums/thread.php?cid=".Sanitize::courseId($cid)."&forum=".Sanitize::onlyInt($fid));
		} else {
			header('Location: ' . $GLOBALS['basesiteurl'] . "/course/course.php?cid=".Sanitize::courseId($cid));
		}

	} else {
		//TO HERE - TODO keep going
		require("../header.php");
		echo "<div class=breadcrumb>$breadcrumbbase ";
		if ($cid>0 && (!isset($sessiondata['ltiitemtype']) || $sessiondata['ltiitemtype']!=0)) {
			echo " <a href=\"../course/course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";
		}
		echo "Redeem LatePass</div>\n";
		//$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\"> $coursename</a>\n";
		//$curBreadcrumb .= " Redeem LatePass\n";
		//echo "<div class=\"breadcrumb\">$curBreadcrumb</div>";

		//DB $query = "SELECT latepass FROM imas_students WHERE userid='$userid' AND courseid='$cid'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $numlatepass = mysql_result($result,0,0);
		$stm = $DBH->prepare("SELECT latepass FROM imas_students WHERE userid=:userid AND courseid=:courseid");
		$stm->execute(array(':userid'=>$userid, ':courseid'=>$cid));
		$numlatepass = $stm->fetchColumn(0);

		//DB $query = "SELECT allowlate,postby,replyby FROM imas_forums WHERE id='$fid'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB list($allowlate,$postby,$replyby) = mysql_fetch_row($result);
		$stm = $DBH->prepare("SELECT allowlate,postby,replyby FROM imas_forums WHERE id=:id");
		$stm->execute(array(':id'=>$fid));
		list($allowlate,$postby,$replyby) = $stm->fetch(PDO::FETCH_NUM);
		$allowlaten = $allowlate%10;
		$allowlateon = floor($allowlate/10)%10;

		//DB $query = "SELECT startdate,enddate,islatepass,itemtype FROM imas_exceptions WHERE userid='$userid' AND assessmentid='$fid' AND (itemtype='F' OR itemtype='R' OR itemtype='P')";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		$stm = $DBH->prepare("SELECT startdate,enddate,islatepass,itemtype FROM imas_exceptions WHERE userid=:userid AND assessmentid=:assessmentid AND (itemtype='F' OR itemtype='R' OR itemtype='P')");
		$stm->execute(array(':userid'=>$userid, ':assessmentid'=>$fid));
		$hasexception = false;
		$usedlatepassespost = 0; $usedlatepassesreply = 0;
		//DB if (mysql_num_rows($result)==0) {
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
		} else {
			$hasexception = true;
			//DB $r = mysql_fetch_row($result);
			$r = $stm->fetch(PDO::FETCH_NUM);
			$usedlatepassespost = min(max(0,round(($r[0] - $postby)/($hours*3600) + .05)), $r[2]);
			$thispostby = $r[0];
			$usedlatepassesreply = min(max(0,round(($r[1] - $replyby)/($hours*3600) + .05)), $r[2]);
			$thisreplyby = $r[1];
			$itemtype = $r[3];
		}
		$canuselatepass = false;
		if (($itemtype=='F' || $itemtype=='P') && $postby!=2000000000 && ($allowlaten==1 || $allowlaten-1>$usedlatepassespost) && ($now<$thispostby || ($allowlate>100 && $now < strtotime("+".$hours." hours", $thispostby)))) {
			$canuselatepasspost = true;
		}
		if (($itemtype=='F' || $itemtype=='R') && $replyby!=2000000000 && ($allowlaten==1 || $allowlaten-1>$usedlatepassesreply) && ($now<$thisreplyby || ($allowlate>100 && $now < strtotime("+".$hours." hours", $thisreplyby)))) {
			$canuselatepassreply = true;
		}

		if ($numlatepass==0) { //shouldn't get here if 0
			echo "<p>You have no late passes remaining.</p>";
		} else if ($canuselatepasspost || $canuselatepassreply) {
			echo '<div id="headerredeemlatepass" class="pagetitle"><h2>Redeem LatePass</h2></div>';
			echo "<form method=post action=\"redeemlatepassforum.php?cid=".Sanitize::courseId($cid)."&fid=".Sanitize::onlyInt($fid)."&from=".Sanitize::encodeUrlParam($from)."\">";
			if ($allowlaten>1) {
				echo '<p>You may use up to '.($allowlaten-1-$usedlatepasses).' more LatePass(es) on this forum assignment.</p>';
			}
			echo "<p>You have ".Sanitize::onlyInt($numlatepass)." LatePass(es) remaining.</p>";
			echo "<p>You can redeem one LatePass for a ".Sanitize::onlyInt($hours)." hour extension on ";
			if ($canuselatepasspost) {
				echo " the <b>New Threads</b> due date ";
				if ($canuselatepassreply) {
					echo "and";
				}
			}
			if ($canuselatepassreply) {
				echo " the <b>Replies</b> due date ";
			}
			echo "for this forum assignment.</p><p>Are you sure you want to redeem a LatePass?</p>";
			echo '<input type="hidden" name="confirm" value="true" />';
			echo "<input type=submit value=\"Yes, Redeem LatePass\"/> ";
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

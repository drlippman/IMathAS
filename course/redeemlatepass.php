<?php
//IMathAS:  Redeem latepasses
//(c) 2007 David Lippman

	require("../validate.php");
	$cid = $_GET['cid'];
	$aid = $_GET['aid'];

	//DB $query = "SELECT latepasshrs FROM imas_courses WHERE id='$cid'";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB $hours = mysql_result($result,0,0);
	$stm = $DBH->prepare("SELECT latepasshrs FROM imas_courses WHERE id=:id");
	$stm->execute(array(':id'=>$cid));
	$hours = $stm->fetchColumn(0);
	$now = time();

	$viewedassess = array();
	//DB $query = "SELECT typeid FROM imas_content_track WHERE courseid='$cid' AND userid='$userid' AND type='gbviewasid'";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB while ($row = mysql_fetch_row($result)) {
	$stm = $DBH->prepare("SELECT typeid FROM imas_content_track WHERE courseid=:courseid AND userid=:userid AND type='gbviewasid'");
	$stm->execute(array(':courseid'=>$cid, ':userid'=>$userid));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$viewedassess[] = $row[0];
	}

	if (isset($_GET['undo'])) {
		require("../header.php");
		echo "<div class=breadcrumb>$breadcrumbbase ";
		if ($cid>0 && (!isset($sessiondata['ltiitemtype']) || $sessiondata['ltiitemtype']!=0)) {
			echo " <a href=\"../course/course.php?cid=$cid\">$coursename</a> &gt; ";
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
				if ($now > $enddate && $row[0] < $now + $hours*60*60) {
					echo '<p>Too late to un-use this LatePass</p>';
				} else {
					if ($now < $enddate) { //before enddate, return all latepasses
						$n = $row[1];
						//DB $query = "DELETE FROM imas_exceptions WHERE userid='$userid' AND assessmentid='$aid' AND itemtype='A'";
						//DB mysql_query($query) or die("Query failed : " . mysql_error());
						$stm = $DBH->prepare("DELETE FROM imas_exceptions WHERE userid=:userid AND assessmentid=:assessmentid AND itemtype='A'");
						$stm->execute(array(':userid'=>$userid, ':assessmentid'=>$aid));
					} else { //figure how many are unused
						$n = floor(($row[0] - $now)/($hours*60*60));
						$newend = $row[0] - $n*$hours*60*60;
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
					echo "<p>Returning $n LatePass".($n>1?"es":"")."</p>";
					//DB $query = "UPDATE imas_students SET latepass=latepass+$n WHERE userid='$userid' AND courseid='$cid'";
					//DB mysql_query($query) or die("Query failed : " . mysql_error());
					$stm = $DBH->prepare("UPDATE imas_students SET latepass=latepass+:n WHERE userid=:userid AND courseid=:courseid");
					$stm->execute(array(':userid'=>$userid, ':courseid'=>$cid, ':n'=>$n));
				}
			}
		}

		if ((!isset($sessiondata['ltiitemtype']) || $sessiondata['ltiitemtype']!=0)) {
			echo "<p><a href=\"course.php?cid=$cid\">Continue</a></p>";
		} else {
			echo "<p><a href=\"../assessment/showtest.php?cid=$cid&id={$sessiondata['ltiitemid']}\">Continue</a></p>";
		}
		require("../footer.php");

	} else if (isset($_GET['confirm'])) {
		$addtime = $hours*60*60;
		//DB $query = "SELECT allowlate,enddate,startdate FROM imas_assessments WHERE id='$aid'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB list($allowlate,$enddate,$startdate) =mysql_fetch_row($result);
		$stm = $DBH->prepare("SELECT allowlate,enddate,startdate FROM imas_assessments WHERE id=:id");
		$stm->execute(array(':id'=>$aid));
		list($allowlate,$enddate,$startdate) =$stm->fetch(PDO::FETCH_NUM);

		//DB $query = "SELECT enddate,islatepass FROM imas_exceptions WHERE userid='$userid' AND assessmentid='$aid' AND itemtype='A'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		$stm = $DBH->prepare("SELECT enddate,islatepass FROM imas_exceptions WHERE userid=:userid AND assessmentid=:assessmentid AND itemtype='A'");
		$stm->execute(array(':userid'=>$userid, ':assessmentid'=>$aid));
		$hasexception = false;
		//DB if (mysql_num_rows($result)==0) {
		if ($stm->rowCount()==0) {
			$usedlatepasses = 0;
			$thised = $enddate;
		} else {
			//DB $r = mysql_fetch_row($result);
			$r = $stm->fetch(PDO::FETCH_NUM);
			$usedlatepasses = min(max(0,round(($r[0] - $enddate)/($hours*3600))), $r[1]);
			$hasexception = true;
			$thised = $r[0];
		}

		if (($allowlate%10==1 || $allowlate%10-1>$usedlatepasses) && ($now<$thised || ($allowlate>10 && ($now-$thised)<$hours*3600 && !in_array($aid,$viewedassess)))) {
			//DB $query = "UPDATE imas_students SET latepass=latepass-1 WHERE userid='$userid' AND courseid='$cid' AND latepass>0";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB if (mysql_affected_rows()>0) {
			$stm = $DBH->prepare("UPDATE imas_students SET latepass=latepass-1 WHERE userid=:userid AND courseid=:courseid AND latepass>0");
			$stm->execute(array(':userid'=>$userid, ':courseid'=>$cid));
			if ($stm->rowCount()>0) {
				if ($hasexception) { //already have exception
					//DB $query = "UPDATE imas_exceptions SET enddate=enddate+$addtime,islatepass=islatepass+1 WHERE userid='$userid' AND assessmentid='$aid' AND itemtype='A'";
					//DB mysql_query($query) or die("Query failed : " . mysql_error());
					$stm = $DBH->prepare("UPDATE imas_exceptions SET enddate=enddate+:addtime,islatepass=islatepass+1 WHERE userid=:userid AND assessmentid=:assessmentid AND itemtype='A'");
					$stm->execute(array(':userid'=>$userid, ':assessmentid'=>$aid, ':addtime'=>$addtime));
				} else {
					$enddate = $enddate + $addtime;
					//DB $query = "INSERT INTO imas_exceptions (userid,assessmentid,startdate,enddate,islatepass,itemtype) VALUES ('$userid','$aid','$startdate','$enddate',1,'A')";
					//DB mysql_query($query) or die("Query failed : " . mysql_error());
					$stm = $DBH->prepare("INSERT INTO imas_exceptions (userid,assessmentid,startdate,enddate,islatepass,itemtype) VALUES (:userid, :assessmentid, :startdate, :enddate, :islatepass, :itemtype)");
					$stm->execute(array(':userid'=>$userid, ':assessmentid'=>$aid, ':startdate'=>$startdate, ':enddate'=>$enddate, ':islatepass'=>1, ':itemtype'=>'A'));
				}
			}
		}
		if ((!isset($sessiondata['ltiitemtype']) || $sessiondata['ltiitemtype']!=0)) {
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/course.php?cid=$cid");
		} else {
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . $imasroot . "/assessment/showtest.php?cid=$cid&id={$sessiondata['ltiitemid']}");
		}
	} else {
		require("../header.php");
		echo "<div class=breadcrumb>$breadcrumbbase ";
		if ($cid>0 && (!isset($sessiondata['ltiitemtype']) || $sessiondata['ltiitemtype']!=0)) {
			echo " <a href=\"../course/course.php?cid=$cid\">$coursename</a> &gt; ";
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

		//DB $query = "SELECT allowlate,enddate,startdate FROM imas_assessments WHERE id='$aid'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB list($allowlate,$enddate,$startdate) =mysql_fetch_row($result);
		$stm = $DBH->prepare("SELECT allowlate,enddate,startdate FROM imas_assessments WHERE id=:id");
		$stm->execute(array(':id'=>$aid));
		list($allowlate,$enddate,$startdate) =$stm->fetch(PDO::FETCH_NUM);

		//DB $query = "SELECT enddate,islatepass FROM imas_exceptions WHERE userid='$userid' AND assessmentid='$aid' AND itemtype='A'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		$stm = $DBH->prepare("SELECT enddate,islatepass FROM imas_exceptions WHERE userid=:userid AND assessmentid=:assessmentid AND itemtype='A'");
		$stm->execute(array(':userid'=>$userid, ':assessmentid'=>$aid));
		$hasexception = false;
		//DB if (mysql_num_rows($result)==0) {
		if ($stm->rowCount()==0) {
			$usedlatepasses = 0;
			$thised = $enddate;
		} else {
			//DB $r = mysql_fetch_row($result);
			$r = $stm->fetch(PDO::FETCH_NUM);
			$usedlatepasses = min(max(0,round(($r[0] - $enddate)/($hours*3600))), $r[1]);
			$hasexception = true;
			$thised = $r[0];
		}

		if ($numlatepass==0) { //shouldn't get here if 0
			echo "<p>You have no late passes remaining.</p>";
		} else if (($allowlate%10==1 || $allowlate%10-1>$usedlatepasses) && ($now<$thised || ($allowlate>10 && ($now-$thised)<$hours*3600 && !in_array($aid,$viewedassess)))) {
			echo '<div id="headerredeemlatepass" class="pagetitle"><h2>Redeem LatePass</h2></div>';
			echo "<form method=post action=\"redeemlatepass.php?cid=$cid&aid=$aid&confirm=true\">";
			if ($allowlate%10>1) {
				echo '<p>You may use up to '.($allowlate%10-1-$usedlatepasses).' more LatePass(es) on this assessment.</p>';
			}
			echo "<p>You have $numlatepass LatePass(es) remaining.  You can redeem one LatePass for a $hours hour ";
			echo "extension on this assessment.  Are you sure you want to redeem a LatePass?</p>";
			echo "<input type=submit value=\"Yes, Redeem LatePass\"/>";
			if ((!isset($sessiondata['ltiitemtype']) || $sessiondata['ltiitemtype']!=0)) {
				echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='course.php?cid=$cid'\"/>";
			} else {
				echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='../assessment/showtest.php?cid=$cid&id={$sessiondata['ltiitemid']}'\"/>";
			}
			echo "</form>";
		} else {
			echo "<p>You are not allowed to use additional latepasses on this assessment.</p>";
		}
		require("../footer.php");
	}

?>

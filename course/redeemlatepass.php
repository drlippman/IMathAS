<?php
//IMathAS:  Redeem latepasses
//(c) 2007 David Lippman

	require("../validate.php");
	$cid = $_GET['cid'];
	$aid = $_GET['aid'];
	
	$query = "SELECT latepasshrs FROM imas_courses WHERE id='$cid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$hours = mysql_result($result,0,0);
	$now = time();
	
	$viewedassess = array();
	$query = "SELECT typeid FROM imas_content_track WHERE courseid='$cid' AND userid='$userid' AND type='gbviewasid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$viewedassess[] = $row[0];
	}
	
	if (isset($_GET['undo'])) {
		require("../header.php");
		echo "<div class=breadcrumb>$breadcrumbbase ";
		if ($cid>0 && (!isset($sessiondata['ltiitemtype']) || $sessiondata['ltiitemtype']!=0)) {
			echo " <a href=\"../course/course.php?cid=$cid\">$coursename</a> &gt; ";
		}
		echo "Un-use LatePass</div>";
		$query = "SELECT enddate,islatepass FROM imas_exceptions WHERE userid='$userid' AND assessmentid='$aid' AND itemtype='A'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		if (mysql_num_rows($result)==0) {
			echo '<p>Invalid</p>';
		} else {
			$row = mysql_fetch_row($result);
			if ($row[1]==0) {
				echo '<p>Invalid</p>';
			} else {
				$now = time();
				$query = "SELECT enddate FROM imas_assessments WHERE id='$aid'";
				$result = mysql_query($query) or die("Query failed : " . mysql_error());
				$enddate = mysql_result($result,0,0);
				//if it's past original due date and latepass is for less than latepasshrs past now, too late
				if ($now > $enddate && $row[0] < $now + $hours*60*60) {
					echo '<p>Too late to un-use this LatePass</p>';
				} else {
					if ($now < $enddate) { //before enddate, return all latepasses
						$n = $row[1];
						$query = "DELETE FROM imas_exceptions WHERE userid='$userid' AND assessmentid='$aid' AND itemtype='A'";
						mysql_query($query) or die("Query failed : " . mysql_error());
					} else { //figure how many are unused
						$n = floor(($row[0] - $now)/($hours*60*60));
						$newend = $row[0] - $n*$hours*60*60;
						if ($row[1]>$n) {
							$query = "UPDATE imas_exceptions SET islatepass=islatepass-$n,enddate=$newend WHERE userid='$userid' AND assessmentid='$aid' AND itemtype='A'";
							mysql_query($query) or die("Query failed : " . mysql_error());
						} else {
							$query = "DELETE FROM imas_exceptions WHERE userid='$userid' AND assessmentid='$aid' AND itemtype='A'";
							mysql_query($query) or die("Query failed : " . mysql_error());
							$n = $row[1];
						}
					}
					echo "<p>Returning $n LatePass".($n>1?"es":"")."</p>";
					$query = "UPDATE imas_students SET latepass=latepass+$n WHERE userid='$userid' AND courseid='$cid'";
					mysql_query($query) or die("Query failed : " . mysql_error());
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
		$query = "SELECT allowlate,enddate,startdate FROM imas_assessments WHERE id='$aid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		list($allowlate,$enddate,$startdate) =mysql_fetch_row($result);
		
		$query = "SELECT enddate,islatepass FROM imas_exceptions WHERE userid='$userid' AND assessmentid='$aid' AND itemtype='A'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$hasexception = false;
		if (mysql_num_rows($result)==0) {
			$usedlatepasses = 0;
			$thised = $enddate;
		} else {
			$r = mysql_fetch_row($result);
			$usedlatepasses = min(max(0,round(($r[0] - $enddate)/($hours*3600))), $r[1]);
			$hasexception = true;
			$thised = $r[0];
		}
		
		if (($allowlate%10==1 || $allowlate%10-1>$usedlatepasses) && ($now<$thised || ($allowlate>10 && ($now-$thised)<$hours*3600 && !in_array($aid,$viewedassess)))) {
			$query = "UPDATE imas_students SET latepass=latepass-1 WHERE userid='$userid' AND courseid='$cid' AND latepass>0";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			if (mysql_affected_rows()>0) {
				if ($hasexception) { //already have exception
					$query = "UPDATE imas_exceptions SET enddate=enddate+$addtime,islatepass=islatepass+1 WHERE userid='$userid' AND assessmentid='$aid' AND itemtype='A'";
					mysql_query($query) or die("Query failed : " . mysql_error());
				} else {
					$enddate = $enddate + $addtime;
					$query = "INSERT INTO imas_exceptions (userid,assessmentid,startdate,enddate,islatepass,itemtype) VALUES ('$userid','$aid','$startdate','$enddate',1,'A')";
					mysql_query($query) or die("Query failed : " . mysql_error());
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
		
		$query = "SELECT latepass FROM imas_students WHERE userid='$userid' AND courseid='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$numlatepass = mysql_result($result,0,0);
		
		$query = "SELECT allowlate,enddate,startdate FROM imas_assessments WHERE id='$aid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		list($allowlate,$enddate,$startdate) =mysql_fetch_row($result);
		
		$query = "SELECT enddate,islatepass FROM imas_exceptions WHERE userid='$userid' AND assessmentid='$aid' AND itemtype='A'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$hasexception = false;
		if (mysql_num_rows($result)==0) {
			$usedlatepasses = 0;
			$thised = $enddate;
		} else {
			$r = mysql_fetch_row($result);
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

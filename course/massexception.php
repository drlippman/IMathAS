<?php
//IMathAS:  Make deadline exceptions for a multiple students; included by listusers and gradebook
//(c) 2007 David Lippman

	if (!isset($imasroot)) {
		echo "This file cannot be called directly";
		exit;
	}
	
	if (isset($_POST['clears'])) {
		$clearlist = "'".implode("','",$_POST['clears'])."'";
		$query = "DELETE FROM imas_exceptions WHERE id IN ($clearlist)";
		mysql_query($query) or die("Query failed :$query " . mysql_error());	
	}
	if (isset($_POST['addexc'])) {
		require_once("../includes/parsedatetime.php");
		$startdate = parsedatetime($_POST['sdate'],$_POST['stime']);
		$enddate = parsedatetime($_POST['edate'],$_POST['etime']);
		
		foreach(explode(',',$_POST['tolist']) as $stu) {
			foreach($_POST['addexc'] as $aid) {
				$query = "SELECT id FROM imas_exceptions WHERE userid='$stu' AND assessmentid='$aid'";
				$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
				if (mysql_num_rows($result)==0) {
					$query = "INSERT INTO imas_exceptions (userid,assessmentid,startdate,enddate) VALUES ";
					$query .= "('$stu','$aid',$startdate,$enddate)";
				} else {
					$eid = mysql_result($result,0,0);
					$query = "UPDATE imas_exceptions SET startdate=$startdate,enddate=$enddate,islatepass=0 WHERE id=$eid";
				}
				mysql_query($query) or die("Query failed :$query " . mysql_error());	
				if (isset($_POST['forceregen'])) {
					//this is not group-safe
					$query = "SELECT id,questions,lastanswers FROM imas_assessment_sessions WHERE userid='$stu' AND assessmentid='$aid'";
					$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
					if (mysql_num_rows($result)>0) {
						$row = mysql_fetch_row($result);
						$questions = explode(',',$row[1]);
						$lastanswers = explode('~',$row[2]);
						$scores = array(); $attempts = array(); $seeds = array();
						for ($i=0; $i<count($questions); $i++) {
							$scores[$i] = -1;
							$attempts[$i] = 0;
							$seeds[$i] = rand(1,9999);
							$newla = array();
							$laarr = explode('##',$lastanswers[$i]);
							//may be some files not accounted for here... 
							//need to fix
							foreach ($laarr as $lael) {
								if ($lael=="ReGen") {
									$newla[] = "ReGen";
								}
							}
							$newla[] = "ReGen";
							$lastanswers[$i] = implode('##',$newla);
							$reattempting = array();
						}
						$scorelist = implode(',',$scores);
						$attemptslist = implode(',',$attempts);
						$seedslist = implode(',',$seeds);
						$lastanswers = str_replace('~','',$lastanswers);
						$lalist = implode('~',$lastanswers);
						$lalist = addslashes(stripslashes($lalist));
						$reattemptinglist = implode(',',$reattempting);
						$query = "UPDATE imas_assessment_sessions SET scores='$scorelist',attempts='$attemptslist',seeds='$seedslist',lastanswers='$lalist',";
						$query .= "reattempting='$reattemptinglist' WHERE id='{$row[0]}'";
						mysql_query($query) or die("Query failed :$query " . mysql_error());
					}
					
				} else if (isset($_POST['forceclear'])) {
					//this is not group-safe
					$query = "DELETE FROM imas_assessment_sessions WHERE userid='$stu' AND assessmentid='$aid'";
					$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
				}
					
			}
		}
		if (isset($_POST['eatlatepass'])) {
			$n = intval($_POST['latepassn']);
			$tolist = implode("','",explode(',',$_POST['tolist']));
			$query = "UPDATE imas_students SET latepass = CASE WHEN latepass>$n THEN latepass-$n ELSE 0 END WHERE userid IN ('$tolist') AND courseid='$cid'";
			mysql_query($query) or die("Query failed :$query " . mysql_error());
		}
		if (isset($_POST['sendmsg'])) {
			$_POST['submit'] = "Message";
			$_POST['checked'] = explode(',',$_POST['tolist']);
			require("masssend.php");
			exit;
		}
	}


	$pagetitle = "Manage Exceptions";
	$placeinhead = "<script type=\"text/javascript\" src=\"$imasroot/javascript/DatePicker.js\"></script>";

	require("../header.php");
	
	
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
	if ($calledfrom=='lu') {
		echo "&gt; <a href=\"listusers.php?cid=$cid\">List Students</a> &gt; Manage Exceptions</div>\n";
	} else if ($calledfrom=='gb') {
		echo "&gt; <a href=\"gradebook.php?cid=$cid";
		if (isset($_GET['uid'])) {
			echo "&stu={$_GET['uid']}";
		}
		echo "\">Gradebook</a> &gt; Manage Exceptions</div>\n";
	}
	
	echo '<div id="headermassexception" class="pagetitle"><h2>Manage Exceptions</h2></div>';
	if ($calledfrom=='lu') {
		echo "<form method=post action=\"listusers.php?cid=$cid&massexception=1\">\n";
	} else if ($calledfrom=='gb') {
		echo "<form method=post action=\"gradebook.php?cid=$cid&massexception=1";
		if (isset($_GET['uid'])) {
			echo "&uid={$_GET['uid']}";
		}
		echo "\">\n";
	}
	
	if (isset($_POST['tolist'])) {
		$_POST['checked'] = explode(',',$_POST['tolist']);
	}
	if (isset($_GET['uid'])) {
		$tolist = "'{$_GET['uid']}'";
		echo "<input type=hidden name=\"tolist\" value=\"{$_GET['uid']}\">\n";
	} else {
		if (count($_POST['checked'])==0) {
			echo "<p>No students selected.</p>";
			if ($calledfrom=='lu') {
				echo "<a href=\"listusers.php?cid=$cid\">Try Again</a>\n";
			} else if ($calledfrom=='gb') {
				echo "<a href=\"gradebook.php?cid=$cid\">Try Again</a>\n";
			}
			require("../footer.php");
			exit;
		}
		echo "<input type=hidden name=\"tolist\" value=\"" . implode(',',$_POST['checked']) . "\">\n";
		$tolist = "'".implode("','",$_POST['checked'])."'";
	}
	
	
	$isall = false;
	if (isset($_POST['ca'])) {
		$isall = true;
		echo "<input type=hidden name=\"ca\" value=\"1\"/>";
	}
	
	
	if (isset($_GET['uid']) || count($_POST['checked'])==1) {
		$query = "SELECT iu.LastName,iu.FirstName,istu.section FROM imas_users AS iu JOIN imas_students AS istu ON iu.id=istu.userid WHERE iu.id=$tolist";
		$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
		$row = mysql_fetch_row($result);
		echo "<h2>{$row[0]}, {$row[1]}";
		if ($row[2]!='') {
			echo ' <span class="small">(Section: '.$row[2].')</span>';
		}
		echo "</h2>";
	}
	
	$query = "SELECT ie.id,iu.LastName,iu.FirstName,ia.name,iu.id,ia.id,ie.startdate,ie.enddate FROM imas_exceptions AS ie,imas_users AS iu,imas_assessments AS ia ";
	$query .= "WHERE ie.assessmentid=ia.id AND ie.userid=iu.id AND ia.courseid='$cid' AND iu.id IN ($tolist) ";
	if ($isall) {
		$query .= "ORDER BY ia.name,iu.LastName,iu.FirstName";
	} else {
		$query .= "ORDER BY iu.LastName,iu.FirstName,ia.name";
	}
	$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
	if (mysql_num_rows($result)>0) {
		echo "<h4>Existing Exceptions</h4>";
		echo "Select exceptions to clear";
		echo '<ul>';
		if ($isall) {
			$lasta = 0;
			while ($row = mysql_fetch_row($result)) {
				$sdate = tzdate("m/d/y g:i a", $row[6]);
				$edate = tzdate("m/d/y g:i a", $row[7]);
				if ($lasta!=$row[5]) {
					if ($lasta!=0) {
						echo "</ul></li>";
					}
					echo "<li>{$row[3]} <ul>";
					$lasta = $row[5];
				}
				echo "<li><input type=checkbox name=\"clears[]\" value=\"{$row[0]}\" />{$row[1]}, {$row[2]} ($sdate - $edate)</li>";
			}
			echo "</ul></li>";
		} else {
			$lasts = 0;
			$assessarr = array();
			while ($row = mysql_fetch_row($result)) {
				$sdate = tzdate("m/d/y g:i a", $row[6]);
				$edate = tzdate("m/d/y g:i a", $row[7]);
				if ($lasts!=$row[4]) {
					if ($lasts!=0) {
						natsort($assessarr);
						foreach ($assessarr as $id=>$val) {
							echo "<li><input type=checkbox name=\"clears[]\" value=\"$id\" />$val</li>";
						}
						echo "</ul></li>";
						$assessarr = array();
					}
					echo "<li>{$row[1]}, {$row[2]} <ul>";
					$lasts = $row[4];
				}
				$assessarr[$row[0]] = "{$row[3]} ($sdate - $edate)";
			}
			natsort($assessarr);
			foreach ($assessarr as $id=>$val) {
				echo "<li><input type=checkbox name=\"clears[]\" value=\"$id\" />$val</li>";
			}
			echo "</ul></li>";
		}
		echo '</ul>';
		
		echo "<input type=submit value=\"Record Changes\" />";
	} else {
		echo "<p>No exceptions currently exist for the selected students.</p>";
	}
	$query = "SELECT latepass FROM imas_students WHERE courseid='$cid' AND userid IN ($tolist)";
	$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
	$row = mysql_fetch_row($result);
	$lpmin = $row[0];
	$lpmax = $row[0];
	while ($row = mysql_fetch_row($result)) {
		if ($row[0]<$lpmin) { $lpmin = $row[0];}
		if ($row[0]>$lpmax) { $lpmax = $row[0];}
	}
	if (count($_POST['checked'])<2) {
		$lpmsg = "This student has $lpmin latepasses.";
	} else if ($lpmin==$lpmax) {
		$lpmsg = "These students all have $lpmin latepasses.";
	} else {
		$lpmsg = "These students have $lpmin-$lpmax latepasses.";
	}
	
	echo "<h4>Make New Exception</h4>";
	
	$now = time();
	$wk = $now + 7*24*60*60;
	$sdate = tzdate("m/d/Y",$now);
	$edate = tzdate("m/d/Y",$wk);
	$stime = tzdate("g:i a",$now);
	$etime = tzdate("g:i a",$wk);
	echo "<span class=form>Available After:</span><span class=formright><input type=text size=10 name=sdate value=\"$sdate\">\n"; 
	echo "<a href=\"#\" onClick=\"displayDatePicker('sdate', this); return false\"><img src=\"../img/cal.gif\" alt=\"Calendar\"/></A>\n";
	echo "at <input type=text size=10 name=stime value=\"$stime\"></span><BR class=form>\n";

	echo "<span class=form>Available Until:</span><span class=formright><input type=text size=10 name=edate value=\"$edate\">\n"; 
	echo "<a href=\"#\" onClick=\"displayDatePicker('edate', this); return false\"><img src=\"../img/cal.gif\" alt=\"Calendar\"/></A>\n";
	echo "at <input type=text size=10 name=etime value=\"$etime\"></span><BR class=form>\n";
	
	echo "Set Exception for assessments:";
	echo "<ul>";
	$query = "SELECT id,name FROM imas_assessments WHERE courseid='$cid'";
	$query .= ' ORDER BY name';
	$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
	$assessarr = array();
	while ($row = mysql_fetch_row($result)) {
		$assessarr[$row[0]] = $row[1];
	}
	natsort($assessarr);
	foreach ($assessarr as $id=>$val) {
		echo "<li><input type=checkbox name=\"addexc[]\" value=\"$id\" ";
		if (isset($_POST['assesschk']) && in_array($id,$_POST['assesschk'])) { echo 'checked="checked" ';}
		echo "/>$val</li>";
	}
	echo '</ul>';
	echo "<input type=submit value=\"Record Changes\" />";
	echo '<p><input type="checkbox" name="forceregen"/> Force student to work on new versions of all questions?  Students ';
	echo 'will keep any scores earned, but must work new versions of questions to improve score.</p>';
	echo '<p><input type="checkbox" name="forceclear"/> Clear student\'s attempts?  Students ';
	echo 'will <b>not</b> keep any scores earned, and must rework all problems.</p>';
	
	echo '<p><input type="checkbox" name="eatlatepass"/> Deduct <input type="input" name="latepassn" size="1" value="1"/> LatePass(es) from each student. '.$lpmsg.'</p>';
	
	echo '<p><input type="checkbox" name="sendmsg"/> Send message to these students?</p>';
	
	if (!isset($_GET['uid']) && count($_POST['checked'])>1) {
		echo "<h4>Students Selected</h4>";
		$query = "SELECT LastName,FirstName FROM imas_users WHERE id IN ($tolist) ORDER BY LastName,FirstName";
		$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
		echo "<ul>";
		while ($row = mysql_fetch_row($result)) {
			echo "<li>{$row[0]}, {$row[1]}</li>";
		}
		echo '</ul>';
	}
	echo '</form>';
	require("../footer.php");
	exit;
		

?>

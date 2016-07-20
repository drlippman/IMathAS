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
	if (isset($_POST['addexc']) || isset($_POST['addfexc'])) {
		require_once("../includes/parsedatetime.php");
		$startdate = parsedatetime($_POST['sdate'],$_POST['stime']);
		$enddate = parsedatetime($_POST['edate'],$_POST['etime']);
		$epenalty = (isset($_POST['overridepenalty']))?intval($_POST['newpenalty']):'NULL';
		$waivereqscore = (isset($_POST['waivereqscore']))?1:0;
		
		$forumitemtype = $_POST['forumitemtype'];
		$postbydate = ($forumitemtype=='R')?0:parsedatetime($_POST['pbdate'],$_POST['pbtime']);
		$replybydate = ($forumitemtype=='P')?0:parsedatetime($_POST['rbdate'],$_POST['rbtime']);
		
		if (!isset($_POST['addexc'])) { $_POST['addexc'] = array();}
		if (!isset($_POST['addfexc'])) { $_POST['addfexc'] = array();} 
		foreach(explode(',',$_POST['tolist']) as $stu) {
			foreach($_POST['addexc'] as $aid) {
				$query = "SELECT id FROM imas_exceptions WHERE userid='$stu' AND assessmentid='$aid' and itemtype='A'";
				$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
				if (mysql_num_rows($result)==0) {
					$query = "INSERT INTO imas_exceptions (userid,assessmentid,startdate,enddate,waivereqscore,exceptionpenalty,itemtype) VALUES ";
					$query .= "('$stu','$aid',$startdate,$enddate,$waivereqscore,$epenalty,'A')";
				} else {
					$eid = mysql_result($result,0,0);
					$query = "UPDATE imas_exceptions SET startdate=$startdate,enddate=$enddate,islatepass=0,waivereqscore=$waivereqscore,exceptionpenalty=$epenalty WHERE id=$eid";
				}
				mysql_query($query) or die("Query failed :$query " . mysql_error());	
				if (isset($_POST['forceregen'])) {
					//this is not group-safe
					$query = "SELECT shuffle FROM imas_assessments WHERE id='$aid'";
					$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
					list($shuffle) = mysql_fetch_row($result);
					$allqsameseed = (($shuffle&2)==2);
			
					$query = "SELECT id,questions,lastanswers,scores FROM imas_assessment_sessions WHERE userid='$stu' AND assessmentid='$aid'";
					$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
					if (mysql_num_rows($result)>0) {
						$row = mysql_fetch_row($result);
						if (strpos($row[1],';')===false) {
							$questions = explode(",",$row[1]);
						} else {
							list($questions,$bestquestions) = explode(";",$row[1]);
							$questions = explode(",",$questions);
						}
						$lastanswers = explode('~',$row[2]);
						$curscorelist = $row[3];
						$scores = array(); $attempts = array(); $seeds = array(); $reattempting = array();
						for ($i=0; $i<count($questions); $i++) {
							$scores[$i] = -1;
							$attempts[$i] = 0;
							if ($allqsameseed && $i>0) {
								$seeds[$i] = $seeds[0];
							} else {
								$seeds[$i] = rand(1,9999);
							}
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
						}
						$scorelist = implode(',',$scores);
						if (strpos($curscorelist,';')!==false) {
							$scorelist = $scorelist.';'.$scorelist;
						}
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
			foreach($_POST['addfexc'] as $fid) {
				$query = "SELECT id FROM imas_exceptions WHERE userid='$stu' AND assessmentid='$fid' and (itemtype='F' OR itemtype='P' OR itemtype='R')";
				$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
				if (mysql_num_rows($result)==0) {
					$query = "INSERT INTO imas_exceptions (userid,assessmentid,startdate,enddate,itemtype) VALUES ";
					$query .= "('$stu','$fid',$postbydate,$replybydate,'$forumitemtype')";
				} else {
					$eid = mysql_result($result,0,0);
					$query = "UPDATE imas_exceptions SET startdate=$postbydate,enddate=$replybydate,islatepass=0,itemtype='$forumitemtype' WHERE id=$eid";
				}
				mysql_query($query) or die("Query failed :$query " . mysql_error());
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
	$placeinhead .= '<style type="text/css">
	   fieldset { margin-bottom: 10px;}
	   fieldset legend {font-weight: bold;}
	   span.form { float:none; display: inline-block; width: 140px;}
	   span.formright { float:none; width: auto; display: inline-block;}
	   fieldset.split { float:left; }
	   .optionlist p.list { margin: 7px 0 7px 20px; padding: 0;}
	   .optionlist input[type=checkbox] {margin-left:-20px;}
	   </style>';

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
		echo "<form method=post action=\"listusers.php?cid=$cid&massexception=1\" id=\"qform\">\n";
	} else if ($calledfrom=='gb') {
		echo "<form method=post action=\"gradebook.php?cid=$cid&massexception=1";
		if (isset($_GET['uid'])) {
			echo "&uid={$_GET['uid']}";
		}
		echo "\" id=\"qform\">\n";
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
		$query = "SELECT iu.LastName,iu.FirstName,istu.section FROM imas_users AS iu JOIN imas_students AS istu ON iu.id=istu.userid WHERE iu.id=$tolist AND istu.courseid='$cid'";
		$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
		$row = mysql_fetch_row($result);
		echo "<h2>{$row[0]}, {$row[1]}";
		if ($row[2]!='') {
			echo ' <span class="small">(Section: '.$row[2].')</span>';
		}
		echo "</h2>";
	}
	
	$query = "(SELECT ie.id AS eid,iu.LastName,iu.FirstName,ia.name as itemname,iu.id AS userid,ia.id AS itemid,ie.startdate,ie.enddate,ie.waivereqscore,ie.itemtype FROM imas_exceptions AS ie,imas_users AS iu,imas_assessments AS ia ";
	$query .= "WHERE ie.assessmentid=ia.id AND ie.userid=iu.id AND ia.courseid='$cid' AND iu.id IN ($tolist) )";
	$query .= "UNION (SELECT ie.id AS eid,iu.LastName,iu.FirstName,i_f.name as itemname,iu.id AS userid,i_f.id AS itemid,ie.startdate,ie.enddate,ie.waivereqscore,ie.itemtype FROM imas_exceptions AS ie,imas_users AS iu,imas_forums AS i_f ";
	$query .= "WHERE ie.assessmentid=i_f.id AND ie.userid=iu.id AND i_f.courseid='$cid' AND iu.id IN ($tolist) )";
	if ($isall) {
		$query .= "ORDER BY itemname,LastName,FirstName";
	} else {
		$query .= "ORDER BY LastName,FirstName,itemname";
	}
	$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
	echo '<h3>'._("Existing Exceptions").'</h3>';
	echo '<fieldset><legend>'._("Existing Exceptions").'</legend>';
	if (mysql_num_rows($result)>0) {
		//echo "<h4>Existing Exceptions</h4>";
		echo "Select exceptions to clear. ";
		echo 'Check: <a href="#" onclick="return chkAllNone(\'qform\',\'clears[]\',true)">All</a> <a href="#" onclick="return chkAllNone(\'qform\',\'clears[]\',false)">None</a>. ';
	
		echo '<ul>';
		if ($isall) {
			$lasta = 0;
			while ($row = mysql_fetch_assoc($result)) {
				$sdate = tzdate("m/d/y g:i a", $row['startdate']);
				$edate = tzdate("m/d/y g:i a", $row['enddate']);
				if ($lasta!=$row['itemid']) {
					if ($lasta!=0) {
						echo "</ul></li>";
					}
					echo "<li>{$row['itemname']} <ul>";
					$lasta = $row['itemid'];
				}
				echo "<li><input type=checkbox name=\"clears[]\" value=\"{$row['eid']}\" />{$row['LastName']}, {$row['FirstName']} ";
				if ($row['itemtype']=='A') {
					echo "($sdate - $edate)";
				} else if ($row['itemtype']=='F') {
					echo "(PostBy: $sdate, ReplyBy: $edate)";	
				} else if ($row['itemtype']=='P') {
					echo "(PostBy: $sdate)";	
				} else if ($row['itemtype']=='R') {
					echo "(ReplyBy: $edate)";	
				}
				if ($row['waivereqscore']==1) {
					echo ' <i>('._('waives prereq').')</i>';
				}
				echo "</li>";
			}
			echo "</ul></li>";
		} else {
			$lasts = 0;
			$assessarr = array();
			while ($row = mysql_fetch_assoc($result)) {
				$sdate = tzdate("m/d/y g:i a", $row['startdate']);
				$edate = tzdate("m/d/y g:i a", $row['enddate']);
				if ($lasts!=$row['userid']) {
					if ($lasts!=0) {
						natsort($assessarr);
						foreach ($assessarr as $id=>$val) {
							echo "<li><input type=checkbox name=\"clears[]\" value=\"$id\" />$val</li>";
						}
						echo "</ul></li>";
						$assessarr = array();
					}
					echo "<li>{$row['LastName']}, {$row['FirstName']} <ul>";
					$lasts = $row['userid'];
				}
				$assessarr[$row['eid']] = "{$row['itemname']} ";
				if ($row['itemtype']=='A') {
					$assessarr[$row['eid']] .= "($sdate - $edate)";
				} else if ($row['itemtype']=='F') {
					$assessarr[$row['eid']] .= "(PostBy: $sdate, ReplyBy: $edate)";	
				} else if ($row['itemtype']=='P') {
					$assessarr[$row['eid']] .= "(PostBy: $sdate)";	
				} else if ($row['itemtype']=='R') {
					$assessarr[$row['eid']] .= "(ReplyBy: $edate)";	
				}
				if ($row['waivereqscore']==1) {
					$assessarr[$row['eid']] .= ' <i>('._('waives prereq').')</i>';
				}
				
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
	echo '</fieldset>';
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
	
	//echo "<h4>Make New Exception</h4>";
	echo '<h3>'._("Make New Exception").'</h3>';
	echo '<fieldset class="optionlist"><legend>'._("Exception Options").'</legend>'; 
	echo '<p class="list"><input type="checkbox" name="eatlatepass"/> Deduct <input type="input" name="latepassn" size="1" value="1"/> LatePass(es) from each student. '.$lpmsg.'</p>';
	echo '<p class="list"><input type="checkbox" name="sendmsg"/> Send message to these students?</p>';
	echo '<p>For assessments:</p>';
	echo '<p class="list"><input type="checkbox" name="forceregen"/> Force student to work on new versions of all questions?  Students ';
	echo 'will keep any scores earned, but must work new versions of questions to improve score. <i>Do not use with group assessments</i>.</p>';
	echo '<p class="list"><input type="checkbox" name="forceclear"/> Clear student\'s attempts?  Students ';
	echo 'will <b>not</b> keep any scores earned, and must rework all problems.</p>';
	echo '<p class="list"><input type="checkbox" name="waivereqscore"/> Waive "show based on an another assessment" requirements, if applicable.</p>';
	echo '<p class="list"><input type="checkbox" name="overridepenalty"/> Override default exception/LatePass penalty.  Deduct <input type="input" name="newpenalty" size="2" value="0"/>% for questions done while in exception.</p>';
	echo '</fieldset>';
	
	
	$query = "SELECT id,name FROM imas_forums WHERE courseid='$cid' AND ((postby>0 AND postby<2000000000) OR (replyby>0 AND replyby<2000000000))";
	$query .= ' ORDER BY name';
	$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
	$forumarr = array();
	while ($row = mysql_fetch_row($result)) {
		$forumarr[$row[0]] = $row[1];
	}
	
	$query = "SELECT id,name FROM imas_assessments WHERE courseid='$cid'";
	$query .= ' ORDER BY name';
	$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
	$assessarr = array();
	while ($row = mysql_fetch_row($result)) {
		$assessarr[$row[0]] = $row[1];
	}
	if (count($forumarr)>0 && count($assessarr)>0) {
		$fclass = ' class="split"';
	} else {
		$fclass = '';
	}
	if (count($assessarr)>0) {
		echo '<fieldset'.$fclass.'><legend>'._("New Assessment Exception").'</legend>';
		
		$now = time();
		$wk = $now + 7*24*60*60;
		$sdate = tzdate("m/d/Y",$now);
		$edate = tzdate("m/d/Y",$wk);
		$stime = tzdate("g:i a",$now);
		$hr = floor($coursedeftime/60)%12;
		$min = $coursedeftime%60;
		$am = ($coursedeftime<12*60)?'am':'pm';
		$etime = (($hr==0)?12:$hr).':'.(($min<10)?'0':'').$min.' '.$am;
		//$etime = tzdate("g:i a",$wk);
		echo "<span class=form>Available After:</span><span class=formright>";
		echo "<input type=text size=10 name=sdate value=\"$sdate\">\n"; 
		echo "<a href=\"#\" onClick=\"displayDatePicker('sdate', this); return false\"><img src=\"../img/cal.gif\" alt=\"Calendar\"/></A>\n";
		echo "at <input type=text size=10 name=stime value=\"$stime\"></span><BR class=form>\n";
	
		echo "<span class=form>Available Until:</span><span class=formright><input type=text size=10 name=edate value=\"$edate\">\n"; 
		echo "<a href=\"#\" onClick=\"displayDatePicker('edate', this); return false\"><img src=\"../img/cal.gif\" alt=\"Calendar\"/></A>\n";
		echo "at <input type=text size=10 name=etime value=\"$etime\"></span><BR class=form>\n";
		
		echo "Set Exception for assessments: ";
		echo 'Check: <a href="#" onclick="return chkAllNone(\'qform\',\'addexc[]\',true)">All</a> <a href="#" onclick="return chkAllNone(\'qform\',\'addexc[]\',false)">None</a>. ';
		
		echo '<ul class="nomark">';
		
		natsort($assessarr);
		foreach ($assessarr as $id=>$val) {
			echo "<li><input type=checkbox name=\"addexc[]\" value=\"$id\" ";
			if (isset($_POST['assesschk']) && in_array($id,$_POST['assesschk'])) { echo 'checked="checked" ';}
			echo "/>$val</li>";
		}
		echo '</ul>';
		echo "<input type=submit value=\"Record Changes\" />";
		echo '</fieldset>';
	}
	
	if (count($forumarr)>0) {
		echo '<fieldset'.$fclass.'><legend>'._("New Forum Exception").'</legend>';
		
		$now = time();
		$wk = $now + 7*24*60*60;
		$pbdate = tzdate("m/d/Y",$wk);
		$rbdate = tzdate("m/d/Y",$wk);
		$hr = floor($coursedeftime/60)%12;
		$min = $coursedeftime%60;
		$am = ($coursedeftime<12*60)?'am':'pm';
		$pbtime = (($hr==0)?12:$hr).':'.(($min<10)?'0':'').$min.' '.$am;
		$rbtime = $pbtime;
		//$etime = tzdate("g:i a",$wk);
		echo '<span class="form">Exception type:</span><span class="formright"><select name="forumitemtype">';
		echo '<option value="F" checked">Override Post By and Reply By</option>';
		echo '<option value="P" checked">Override Post By only</option>';
		echo '<option value="R" checked">Override Reply By only</option></select></span><br class="form"/>';
		
		echo "<span class=form>Post By:</span><span class=formright><input type=text size=10 name=pbdate value=\"$pbdate\">\n"; 
		echo "<a href=\"#\" onClick=\"displayDatePicker('pbdate', this); return false\"><img src=\"../img/cal.gif\" alt=\"Calendar\"/></A>\n";
		echo "at <input type=text size=10 name=pbtime value=\"$pbtime\"></span><BR class=form>\n";
	
		echo "<span class=form>Reply By:</span><span class=formright><input type=text size=10 name=rbdate value=\"$rbdate\">\n"; 
		echo "<a href=\"#\" onClick=\"displayDatePicker('rbdate', this); return false\"><img src=\"../img/cal.gif\" alt=\"Calendar\"/></A>\n";
		echo "at <input type=text size=10 name=rbtime value=\"$rbtime\"></span><BR class=form>\n";
		
		 
		
		echo "Set Exception for forums: ";
		echo 'Check: <a href="#" onclick="return chkAllNone(\'qform\',\'addfexc[]\',true)">All</a> <a href="#" onclick="return chkAllNone(\'qform\',\'addfexc[]\',false)">None</a>. ';
		
		echo '<ul class="nomark">';
		
		natsort($forumarr);
		foreach ($forumarr as $id=>$val) {
			echo "<li><input type=checkbox name=\"addfexc[]\" value=\"$id\" ";
			if (isset($_POST['forumchk']) && in_array($id,$_POST['forumchk'])) { echo 'checked="checked" ';}
			echo "/>$val</li>";
		}
		echo '</ul>';
		echo "<input type=submit value=\"Record Changes\" />";
		echo '</fieldset>';
	}
	
	
	
	if (!isset($_GET['uid']) && count($_POST['checked'])>1) {
		echo '<fieldset><legend>'._("Students Selected").'</legend>';
		//echo "<h4>Students Selected</h4>";
		$query = "SELECT LastName,FirstName FROM imas_users WHERE id IN ($tolist) ORDER BY LastName,FirstName";
		$result = mysql_query($query) or die("Query failed :$query " . mysql_error());
		echo "<ul>";
		while ($row = mysql_fetch_row($result)) {
			echo "<li>{$row[0]}, {$row[1]}</li>";
		}
		echo '</ul>';
		echo '</fieldset>';
	}
	echo '</form>';
	require("../footer.php");
	exit;
		

?>

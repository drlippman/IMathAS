<?php
//IMathAS:  Make deadline exceptions for a multiple students; included by listusers and gradebook
//(c) 2007 David Lippman


	if (!isset($imasroot)) {
		echo "This file cannot be called directly";
		exit;
	}

	if (isset($_POST['clears'])) {
		//DB $clearlist = "'".implode("','",$_POST['clears'])."'";
		$clearlist = implode(',', array_map('intval', $_POST['clears']));
		//DB $query = "DELETE FROM imas_exceptions WHERE id IN ($clearlist)";
		//DB mysql_query($query) or die("Query failed :$query " . mysql_error());
		$stm = $DBH->query("DELETE FROM imas_exceptions WHERE id IN ($clearlist)");
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
		$toarr = array_map('Sanitize::onlyInt', explode(',', $_POST['tolist']));
		$addexcarr = array_map('Sanitize::onlyInt', $_POST['addexc']);
		$addfexcarr = array_map('Sanitize::onlyInt', $_POST['addfexc']);
		$existingExceptions = array();
		if (count($addexcarr)>0 && count($toarr)>0) {
			//prepull users with exceptions
			$uidplaceholders = Sanitize::generateQueryPlaceholders($toarr);
			$aidplaceholders = Sanitize::generateQueryPlaceholders($addexcarr);
			$stm = $DBH->prepare("SELECT userid,assessmentid FROM imas_exceptions WHERE userid IN ($uidplaceholders) AND assessmentid IN ($aidplaceholders) and itemtype='A'");
			$stm->execute(array_merge($toarr, $addexcarr));
			while ($row = $stm->fetch(PDO::FETCH_NUM)) {
				$existingExceptions[$row[0].'-'.$row[1]] = 1;
			}
		}
		//set up inserts
		$insertExceptionHolders = array();
		$insertExceptionVals = array();
		foreach ($toarr as $stu) {
			foreach ($addexcarr as $aid) {
				if (!isset($existingExceptions[$stu.'-'.$aid])) {
					$insertExceptionHolders[] = "(?,?,?,?,?,?,?)";
					array_push($insertExceptionVals, $stu, $aid, $startdate, $enddate, $waivereqscore, $epenalty, 'A');
				}
			}
		}
		//run update
		if (count($addexcarr)>0 && count($toarr)>0) {
			$stm = $DBH->prepare("UPDATE imas_exceptions SET startdate=?,enddate=?,islatepass=0,waivereqscore=?,exceptionpenalty=? WHERE userid IN ($uidplaceholders) AND assessmentid IN ($aidplaceholders) and itemtype='A'");
			$stm->execute(array_merge(array($startdate, $enddate, $waivereqscore, $epenalty), $toarr, $addexcarr));
		}

		//run inserts
		if (count($insertExceptionVals)>0) {
			$query = "INSERT INTO imas_exceptions (userid,assessmentid,startdate,enddate,waivereqscore,exceptionpenalty,itemtype) VALUES ";
			$query .= implode(',', $insertExceptionHolders);
			$stm = $DBH->prepare($query);
			$stm->execute($insertExceptionVals);
		}
		foreach($toarr as $stu) {
			foreach($addexcarr as $aid) {
				if (isset($_POST['forceregen'])) {
					//this is not group-safe
					//DB $query = "SELECT shuffle FROM imas_assessments WHERE id='$aid'";
					//DB $result = mysql_query($query) or die("Query failed :$query " . mysql_error());
					//DB list($shuffle) = mysql_fetch_row($result);
					$stm = $DBH->prepare("SELECT shuffle FROM imas_assessments WHERE id=:id");
					$stm->execute(array(':id'=>$aid));
					$shuffle = $stm->fetchColumn(0);
					$allqsameseed = (($shuffle&2)==2);

					//DB $query = "SELECT id,questions,lastanswers,scores FROM imas_assessment_sessions WHERE userid='$stu' AND assessmentid='$aid'";
					//DB $result = mysql_query($query) or die("Query failed :$query " . mysql_error());
					//DB if (mysql_num_rows($result)>0) {
						//DB $row = mysql_fetch_row($result);
					$stm = $DBH->prepare("SELECT id,questions,lastanswers,scores FROM imas_assessment_sessions WHERE userid=:userid AND assessmentid=:assessmentid");
					$stm->execute(array(':userid'=>$stu, ':assessmentid'=>$aid));
					if ($stm->rowCount()>0) {
						$row = $stm->fetch(PDO::FETCH_NUM);
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
						//DB $lalist = addslashes(stripslashes($lalist));
						$reattemptinglist = implode(',',$reattempting);
						//DB $query = "UPDATE imas_assessment_sessions SET scores='$scorelist',attempts='$attemptslist',seeds='$seedslist',lastanswers='$lalist',";
						//DB $query .= "reattempting='$reattemptinglist' WHERE id='{$row[0]}'";
						//DB mysql_query($query) or die("Query failed :$query " . mysql_error());
						$query = "UPDATE imas_assessment_sessions SET scores=:scores,attempts=:attempts,seeds=:seeds,lastanswers=:lastanswers,";
						$query .= "reattempting=:reattempting WHERE id=:id";
						$stm = $DBH->prepare($query);
						$stm->execute(array(':scores'=>$scorelist, ':attempts'=>$attemptslist, ':seeds'=>$seedslist, ':lastanswers'=>$lalist,
							':reattempting'=>$reattemptinglist, ':id'=>$row[0]));
					}

				} else if (isset($_POST['forceclear'])) {
					//this is not group-safe
					//DB $query = "DELETE FROM imas_assessment_sessions WHERE userid='$stu' AND assessmentid='$aid'";
					//DB $result = mysql_query($query) or die("Query failed :$query " . mysql_error());
					$stm = $DBH->prepare("DELETE FROM imas_assessment_sessions WHERE userid=:userid AND assessmentid=:assessmentid");
					$stm->execute(array(':userid'=>$stu, ':assessmentid'=>$aid));
				}

			}
			/* work in progress
			$existingForumExceptions = array();
			if (count($addfexcarr)>0 && count($toarr)>0) {
				//prepull users with forum exceptions
				$uidplaceholders = Sanitize::generateQueryPlaceholders($toarr);
				$fidplaceholders = Sanitize::generateQueryPlaceholders($addfexcarr);
				$stm = $DBH->prepare("SELECT userid,assessmentid FROM imas_exceptions WHERE userid IN ($uidplaceholders) AND assessmentid IN ($fidplaceholders) and (itemtype='F' OR itemtype='P' OR itemtype='R')");
				$stm->execute(array_merge($toarr, $addfexcarr));
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					$existingForumExceptions[$row[0].'-'.$row[1]] = 1;
				}
			}
			*/
			foreach($addfexcarr as $fid) {
				//DB $query = "SELECT id FROM imas_exceptions WHERE userid='$stu' AND assessmentid='$fid' and (itemtype='F' OR itemtype='P' OR itemtype='R')";
				//DB $result = mysql_query($query) or die("Query failed :$query " . mysql_error());
				//DB if (mysql_num_rows($result)==0) {
				$stm = $DBH->prepare("SELECT id FROM imas_exceptions WHERE userid=:userid AND assessmentid=:assessmentid and (itemtype='F' OR itemtype='P' OR itemtype='R')");
				$stm->execute(array(':userid'=>$stu, ':assessmentid'=>$fid));
				if ($stm->rowCount()==0) {
					//DB $query = "INSERT INTO imas_exceptions (userid,assessmentid,startdate,enddate,itemtype) VALUES ";
					//DB $query .= "('$stu','$fid',$postbydate,$replybydate,'$forumitemtype')";
					$query = "INSERT INTO imas_exceptions (userid,assessmentid,startdate,enddate,itemtype) VALUES ";
					$query .= "(:userid, :assessmentid, :startdate, :enddate, :itemtype)";
					$stm = $DBH->prepare($query);
					$stm->execute(array(':userid'=>$stu, ':assessmentid'=>$fid, ':startdate'=>$postbydate, ':enddate'=>$replybydate, ':itemtype'=>$forumitemtype));
				} else {
					//DB $eid = mysql_result($result,0,0);
					$eid = $stm->fetchColumn(0);
					//DB $query = "UPDATE imas_exceptions SET startdate=$postbydate,enddate=$replybydate,islatepass=0,itemtype='$forumitemtype' WHERE id=$eid";
					$stm = $DBH->prepare("UPDATE imas_exceptions SET startdate=:startdate,enddate=:enddate,islatepass=0,itemtype=:itemtype WHERE id=:id");
					$stm->execute(array(':startdate'=>$postbydate, ':enddate'=>$replybydate, ':itemtype'=>$forumitemtype, ':id'=>$eid));
				}
				//DB mysql_query($query) or die("Query failed :$query " . mysql_error());
			}
		}

		if (isset($_POST['eatlatepass'])) {
			$n = intval($_POST['latepassn']);
			//DB $tolist = implode("','",explode(',',$_POST['tolist']));
			$tolist = implode(',', array_map('intval', explode(',',$_POST['tolist'])));
			//DB $query = "UPDATE imas_students SET latepass = CASE WHEN latepass>$n THEN latepass-$n ELSE 0 END WHERE userid IN ('$tolist') AND courseid='$cid'";
			//DB mysql_query($query) or die("Query failed :$query " . mysql_error());
			$stm = $DBH->prepare("UPDATE imas_students SET latepass = CASE WHEN latepass>$n THEN latepass-$n ELSE 0 END WHERE userid IN ($tolist) AND courseid=:courseid");
			$stm->execute(array(':courseid'=>$cid));
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

	$cid = Sanitize::courseId($_GET['cid']);
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=$cid\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
	if ($calledfrom=='lu') {
		echo "&gt; <a href=\"listusers.php?cid=$cid\">List Students</a> &gt; Manage Exceptions</div>\n";
	} else if ($calledfrom=='gb') {
		echo "&gt; <a href=\"gradebook.php?cid=$cid";
		if (isset($_GET['uid'])) {
			echo "&stu=" . Sanitize::onlyInt($_GET['uid']);
		}
		echo "\">Gradebook</a> &gt; Manage Exceptions</div>\n";
	}

	echo '<div id="headermassexception" class="pagetitle"><h2>Manage Exceptions</h2></div>';
	if ($calledfrom=='lu') {
		echo "<form method=post action=\"listusers.php?cid=$cid&massexception=1\" id=\"qform\">\n";
	} else if ($calledfrom=='gb') {
		echo "<form method=post action=\"gradebook.php?cid=$cid&massexception=1";
		if (isset($_GET['uid'])) {
			echo "&uid=" . Sanitize::onlyInt($_GET['uid']);
		}
		echo "\" id=\"qform\">\n";
	}

	if (isset($_POST['tolist'])) {
		$_POST['checked'] = explode(',',$_POST['tolist']);
	}
	if (isset($_GET['uid'])) {
		//DB $tolist = "'{$_GET['uid']}'";
		$tolist = intval($_GET['uid']);
		echo "<input type=hidden name=\"tolist\" value=\"" . Sanitize::onlyInt($_GET['uid']) . "\">\n";
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
		echo "<input type=hidden name=\"tolist\" value=\"" . Sanitize::encodeStringForDisplay(implode(',',$_POST['checked'])) . "\">\n";
		//DB $tolist = "'".implode("','",$_POST['checked'])."'";
		$tolist = implode(',', array_map('intval', $_POST['checked']));
	}


	$isall = false;
	if (isset($_POST['ca'])) {
		$isall = true;
		echo "<input type=hidden name=\"ca\" value=\"1\"/>";
	}


	if (isset($_GET['uid']) || count($_POST['checked'])==1) {
		//DB $query = "SELECT iu.LastName,iu.FirstName,istu.section FROM imas_users AS iu JOIN imas_students AS istu ON iu.id=istu.userid WHERE iu.id=$tolist AND istu.courseid='$cid'";
		//DB $result = mysql_query($query) or die("Query failed :$query " . mysql_error());
		//DB $row = mysql_fetch_row($result);
		$stm = $DBH->prepare("SELECT iu.LastName,iu.FirstName,istu.section FROM imas_users AS iu JOIN imas_students AS istu ON iu.id=istu.userid WHERE iu.id=:id AND istu.courseid=:courseid");
		$stm->execute(array(':id'=>$tolist, ':courseid'=>$cid));
		$row = $stm->fetch(PDO::FETCH_NUM);
		echo "<h2>" . Sanitize::encodeStringForDisplay($row[0]) . ", " . Sanitize::encodeStringForDisplay($row[1]);
		if ($row[2]!='') {
			echo ' <span class="small">(Section: '.Sanitize::encodeStringForDisplay($row[2]).')</span>';
		}
		echo "</h2>";
	}

	//DB $query = "(SELECT ie.id AS eid,iu.LastName,iu.FirstName,ia.name as itemname,iu.id AS userid,ia.id AS itemid,ie.startdate,ie.enddate,ie.waivereqscore,ie.itemtype FROM imas_exceptions AS ie,imas_users AS iu,imas_assessments AS ia ";
	//DB $query .= "WHERE ie.assessmentid=ia.id AND ie.userid=iu.id AND ia.courseid='$cid' AND iu.id IN ($tolist) )";
	//DB $query .= "UNION (SELECT ie.id AS eid,iu.LastName,iu.FirstName,i_f.name as itemname,iu.id AS userid,i_f.id AS itemid,ie.startdate,ie.enddate,ie.waivereqscore,ie.itemtype FROM imas_exceptions AS ie,imas_users AS iu,imas_forums AS i_f ";
	//DB $query .= "WHERE ie.assessmentid=i_f.id AND ie.userid=iu.id AND i_f.courseid='$cid' AND iu.id IN ($tolist) )";
	//DB if ($isall) {
		//DB $query .= "ORDER BY itemname,LastName,FirstName";
	//DB } else {
		//DB $query .= "ORDER BY LastName,FirstName,itemname";
	//DB }
	//DB $result = mysql_query($query) or die("Query failed :$query " . mysql_error());
	$query = "(SELECT ie.id AS eid,iu.LastName,iu.FirstName,ia.name as itemname,iu.id AS userid,ia.id AS itemid,ie.startdate,ie.enddate,ie.waivereqscore,ie.islatepass,ie.itemtype,ie.is_lti FROM imas_exceptions AS ie,imas_users AS iu,imas_assessments AS ia ";
	$query .= "WHERE ie.itemtype='A' AND ie.assessmentid=ia.id AND ie.userid=iu.id AND ia.courseid=:courseid AND iu.id IN ($tolist) ) ";
	$query .= "UNION (SELECT ie.id AS eid,iu.LastName,iu.FirstName,i_f.name as itemname,iu.id AS userid,i_f.id AS itemid,ie.startdate,ie.enddate,ie.waivereqscore,ie.islatepass,ie.itemtype,ie.is_lti FROM imas_exceptions AS ie,imas_users AS iu,imas_forums AS i_f ";
	$query .= "WHERE (ie.itemtype='F' OR ie.itemtype='P' OR ie.itemtype='R') AND ie.assessmentid=i_f.id AND ie.userid=iu.id AND i_f.courseid=:courseid2 AND iu.id IN ($tolist) )";
	if ($isall) {
		$query .= "ORDER BY itemname,LastName,FirstName";
	} else {
		$query .= "ORDER BY LastName,FirstName,itemname";
	}
	$stm = $DBH->prepare($query);
	$stm->execute(array(':courseid'=>$cid, ':courseid2'=>$cid));

	echo '<h3>'._("Existing Exceptions").'</h3>';
	echo '<fieldset><legend>'._("Existing Exceptions").'</legend>';
	//DB if (mysql_num_rows($result)>0) {
	if ($stm->rowCount()>0) {
		//echo "<h4>Existing Exceptions</h4>";
		echo "Select exceptions to clear. ";
		echo 'Check: <a href="#" onclick="return chkAllNone(\'qform\',\'clears[]\',true)">All</a> <a href="#" onclick="return chkAllNone(\'qform\',\'clears[]\',false)">None</a>. ';

		echo '<ul>';
		if ($isall) {
			$lasta = 0;
			//DB while ($row = mysql_fetch_assoc($result)) {
			while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
				$sdate = tzdate("m/d/y g:i a", $row['startdate']);
				$edate = tzdate("m/d/y g:i a", $row['enddate']);
				if ($lasta!=$row['itemid']) {
					if ($lasta!=0) {
						echo "</ul></li>";
					}
					echo "<li>" . Sanitize::encodeStringForDisplay($row['itemname']) ." <ul>";
					$lasta = $row['itemid'];
				}
				printf('<li><input type=checkbox name="clears[]" value="%s" />%s, %s ',
					Sanitize::encodeStringForDisplay($row['eid']), Sanitize::encodeStringForDisplay($row['LastName']),
					Sanitize::encodeStringForDisplay($row['FirstName']));
				if ($row['itemtype']=='A') {
					echo Sanitize::encodeStringForDisplay("($sdate - $edate)");
				} else if ($row['itemtype']=='F') {
					echo Sanitize::encodeStringForDisplay("(PostBy: $sdate, ReplyBy: $edate)");
				} else if ($row['itemtype']=='P') {
					echo Sanitize::encodeStringForDisplay("(PostBy: $sdate)");
				} else if ($row['itemtype']=='R') {
					echo Sanitize::encodeStringForDisplay("(ReplyBy: $edate)");
				}
				if ($row['waivereqscore']==1) {
					echo ' <i>('._('waives prereq').')</i>';
				}
				if ($row['islatepass']>0) {
					echo ' <i>('._('LatePass').')</i>';
				} else if ($row['is_lti']>0) {
					echo ' <i>('._('Set by LTI').')</i>';
				} 
				echo "</li>";
				
			}
			echo "</ul></li>";
		} else {
			$lasts = 0;
			$assessarr = array();
			$notesarr = array();
			//DB while ($row = mysql_fetch_assoc($result)) {
			while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
				$sdate = tzdate("m/d/y g:i a", $row['startdate']);
				$edate = tzdate("m/d/y g:i a", $row['enddate']);
				if ($lasts!=$row['userid']) {
					if ($lasts!=0) {
						natsort($assessarr);
						foreach ($assessarr as $id=>$val) {
							echo "<li><input type=checkbox name=\"clears[]\" value=\"" . Sanitize::onlyInt($id) . "\" />".Sanitize::encodeStringForDisplay($val)."</li>";
						}
						echo "</ul></li>";
						$assessarr = array();
					}
					printf("<li>%s, %s <ul>", Sanitize::encodeStringForDisplay($row['LastName']),
						Sanitize::encodeStringForDisplay($row['FirstName']));
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
				$notesarr[$row['eid']] = '';
				if ($row['waivereqscore']==1) {
					$notesarr[$row['eid']] .= ' ('._('waives prereq').')';
				}
				if ($row['islatepass']>0) {
					$notesarr[$row['eid']] .= ' ('._('LatePass').')';
				} else if ($row['is_lti']>0) {
					$notesarr[$row['eid']] .= ' ('._('Set by LTI').')';
				} 

			}
			natsort($assessarr);
			foreach ($assessarr as $id=>$val) {
				echo "<li><input type=checkbox name=\"clears[]\" value=\"" . Sanitize::onlyInt($id) . "\" />".Sanitize::encodeStringForDisplay($val);
				if ($notesarr[$id]!='') {
					echo ' <em class=small>'.Sanitize::encodeStringForDisplay($notesarr[$id]).'</em>';
				}
				echo "</li>";
			}
			echo "</ul></li>";
		}
		echo '</ul>';

		echo "<input type=submit value=\"Record Changes\" />";
	} else {
		echo "<p>No exceptions currently exist for the selected students.</p>";
	}
	echo '</fieldset>';
	//DB $query = "SELECT latepass FROM imas_students WHERE courseid='$cid' AND userid IN ($tolist)";
	//DB $result = mysql_query($query) or die("Query failed :$query " . mysql_error());
	//DB $row = mysql_fetch_row($result);
	$stm = $DBH->prepare("SELECT latepass FROM imas_students WHERE courseid=:courseid AND userid IN ($tolist)");
	$stm->execute(array(':courseid'=>$cid));
	$row = $stm->fetch(PDO::FETCH_NUM);
	$lpmin = $row[0];
	$lpmax = $row[0];
	//DB while ($row = mysql_fetch_row($result)) {
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
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
	echo '<p class="list"><input type="checkbox" name="eatlatepass"/> Deduct <input type="input" name="latepassn" size="1" value="1"/> LatePass(es) from each student. '.Sanitize::encodeStringForDisplay($lpmsg).'</p>';
	echo '<p class="list"><input type="checkbox" name="sendmsg"/> Send message to these students?</p>';
	echo '<p>For assessments:</p>';
	echo '<p class="list"><input type="checkbox" name="forceregen"/> Force student to work on new versions of all questions?  Students ';
	echo 'will keep any scores earned, but must work new versions of questions to improve score. <i>Do not use with group assessments</i>.</p>';
	echo '<p class="list"><input type="checkbox" name="forceclear"/> Clear student\'s attempts?  Students ';
	echo 'will <b>not</b> keep any scores earned, and must rework all problems.</p>';
	echo '<p class="list"><input type="checkbox" name="waivereqscore"/> Waive "show based on an another assessment" requirements, if applicable.</p>';
	echo '<p class="list"><input type="checkbox" name="overridepenalty"/> Override default exception/LatePass penalty.  Deduct <input type="input" name="newpenalty" size="2" value="0"/>% for questions done while in exception.</p>';
	echo '</fieldset>';


	//DB $query = "SELECT id,name FROM imas_forums WHERE courseid='$cid' AND ((postby>0 AND postby<2000000000) OR (replyby>0 AND replyby<2000000000))";
	//DB $query .= ' ORDER BY name';
	//DB $result = mysql_query($query) or die("Query failed :$query " . mysql_error());
	$query = "SELECT id,name FROM imas_forums WHERE courseid=:courseid AND ((postby>0 AND postby<2000000000) OR (replyby>0 AND replyby<2000000000))";
	$query .= ' ORDER BY name';
	$stm = $DBH->prepare($query);
	$stm->execute(array(':courseid'=>$cid));
	$forumarr = array();
	//DB while ($row = mysql_fetch_row($result)) {
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$forumarr[$row[0]] = $row[1];
	}

	//DB $query = "SELECT id,name FROM imas_assessments WHERE courseid='$cid' ORDER BY name";
	//DB $result = mysql_query($query) or die("Query failed :$query " . mysql_error());
	$stm = $DBH->prepare("SELECT id,name FROM imas_assessments WHERE courseid=:courseid ORDER BY name");
	$stm->execute(array(':courseid'=>$cid));
	$assessarr = array();
	//DB while ($row = mysql_fetch_row($result)) {
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
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
			echo "/>" . Sanitize::encodeStringForDisplay($val) . "</li>";
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
			echo "/>" . Sanitize::encodeStringForDisplay($val) . "</li>";
		}
		echo '</ul>';
		echo "<input type=submit value=\"Record Changes\" />";
		echo '</fieldset>';
	}



	if (!isset($_GET['uid']) && count($_POST['checked'])>1) {
		echo '<fieldset><legend>'._("Students Selected").'</legend>';
		//echo "<h4>Students Selected</h4>";
		//DB $query = "SELECT LastName,FirstName FROM imas_users WHERE id IN ($tolist) ORDER BY LastName,FirstName";
		//DB $result = mysql_query($query) or die("Query failed :$query " . mysql_error());
		$stm = $DBH->query("SELECT LastName,FirstName FROM imas_users WHERE id IN ($tolist) ORDER BY LastName,FirstName");
		echo "<ul>";
		//DB while ($row = mysql_fetch_row($result)) {
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			printf("<li>%s, %s</li>", Sanitize::encodeStringForDisplay($row[0]),
				Sanitize::encodeStringForDisplay($row[1]));
		}
		echo '</ul>';
		echo '</fieldset>';
	}
	echo '</form>';
	require("../footer.php");
	exit;


?>

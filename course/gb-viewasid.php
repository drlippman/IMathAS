<?php
//IMathAS:  View/Edit and Question breakdown views
//(c) 2007 David Lippman
	require("../init.php");
	require_once("../includes/filehandler.php");


	$isteacher = isset($teacherid);
	$istutor = isset($tutorid);
	$cid = Sanitize::courseId($_GET['cid']);
	$asid = intval($_GET['asid']);
	if (!isset($_GET['uid']) && !$isteacher && !$istutor) {
		$get_uid = $userid;
	} else {
		$get_uid = Sanitize::onlyInt($_GET['uid']);
	}

	if ($isteacher || $istutor) {
		if (isset($sessiondata[$cid.'gbmode'])) {
			$gbmode =  $sessiondata[$cid.'gbmode'];
		} else {
			//DB $query = "SELECT defgbmode FROM imas_gbscheme WHERE courseid='$cid'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $gbmode = mysql_result($result,0,0);
			$stm = $DBH->prepare("SELECT defgbmode FROM imas_gbscheme WHERE courseid=:courseid");
			$stm->execute(array(':courseid'=>$cid));
			$gbmode = $stm->fetchColumn(0);
		}
		if (isset($_GET['stu']) && $_GET['stu']!='') {
			$stu = Sanitize::onlyInt($_GET['stu']);
		} else {
			$stu = 0;
		}
		if (isset($_GET['from'])) {
			$from = $_GET['from'];
		} else {
			$from = 'gb';
		}
		//Gbmode : Links NC Dates
		$totonleft = floor($gbmode/1000)%10 ; //0 right, 1 left
		$links = ((floor($gbmode/100)%10)&1); //0: view/edit, 1 q breakdown
		$hidenc = (floor($gbmode/10)%10)%4; //0: show all, 1 stu visisble (cntingb not 0), 2 hide all (cntingb 1 or 2)
		$availshow = $gbmode%10; //0: past, 1 past&cur, 2 all
		if (isset($_GET['links'])) {
			$links = intval($_GET['links']);
		}
	} else {
		$links = 0;
		$stu = 0;
		$from = 'gb';
		$now = time();
	}

	$overwriteBody = false;


	if ($_GET['asid']=="new" && $isteacher) {
		$aid = Sanitize::onlyInt($_GET['aid']);
		//student could have started, so better check to make sure it still doesn't exist
		//DB $query = "SELECT id FROM imas_assessment_sessions WHERE userid='{$get_uid}' AND assessmentid='$aid' ORDER BY id";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB if (mysql_num_rows($result)>0) {
			//DB $_GET['asid'] = mysql_result($result,0,0);
		$stm = $DBH->prepare("SELECT id FROM imas_assessment_sessions WHERE userid=:userid AND assessmentid=:assessmentid ORDER BY id");
		$stm->execute(array(':userid'=>$get_uid, ':assessmentid'=>$aid));
		if ($stm->rowCount()>0) {
			$_GET['asid'] = $stm->fetchColumn(0);
		} else {
			//DB $query = "SELECT * FROM imas_assessments WHERE id='$aid'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $adata = mysql_fetch_array($result, MYSQL_ASSOC);
			$stm = $DBH->prepare("SELECT * FROM imas_assessments WHERE id=:id");
			$stm->execute(array(':id'=>$aid));
			$adata = $stm->fetch(PDO::FETCH_ASSOC);

			$stugroupmem = array();
			$agroupid = 0;
			$doadd = true;
			if ($adata['isgroup']>0) { //if is group assessment, and groups already exist, create asid for all in group
				$query = 'SELECT i_sg.id FROM imas_stugroups as i_sg JOIN imas_stugroupmembers as i_sgm ON i_sg.id=i_sgm.stugroupid ';
				$query .= "WHERE i_sgm.userid=:userid AND i_sg.groupsetid=:groupsetid";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':userid'=>$get_uid, ':groupsetid'=>$adata['groupsetid']));
				if ($stm->rowCount()>0) {
					//DB $agroupid = mysql_result($result,0,0);
					$agroupid = $stm->fetchColumn(0);
					//DB $query = "SELECT userid FROM imas_stugroupmembers WHERE stugroupid=$agroupid AND userid<>'{$get_uid}'";
					//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
					//DB while ($row = mysql_fetch_row($result)) {
					$stm = $DBH->prepare("SELECT userid FROM imas_stugroupmembers WHERE stugroupid=:stugroupid AND userid<>:uid");
					$stm->execute(array(':stugroupid'=>$agroupid, ':uid'=>$get_uid));
					while ($row = $stm->fetch(PDO::FETCH_NUM)) {
						$stugroupmem[] = $row[0];
					}
				}
				//check that no group member has started the assessment
				$ph = Sanitize::generateQueryPlaceholders($stugroupmem);
				$fieldstocopy = 'assessmentid,agroupid,questions,seeds,scores,attempts,lastanswers,starttime,endtime,bestseeds,bestattempts,bestscores,bestlastanswers,feedback,reviewseeds,reviewattempts,reviewscores,reviewlastanswers,reattempting,reviewreattempting,timeontask,ver';
				$stm = $DBH->prepare("SELECT $fieldstocopy FROM imas_assessment_sessions WHERE userid IN ($ph) AND assessmentid=? ORDER BY id");
				$stm->execute(array_merge($stugroupmem, array($aid)));
				if ($stm->rowCount()>0) {
					$doadd = false;
					$row = $stm->fetch(PDO::FETCH_ASSOC);
					$fieldstocopyarr = explode(',',$fieldstocopy);
					$insrow = ":".implode(',:',$fieldstocopyarr);	
					$query = "INSERT INTO imas_assessment_sessions (userid,$fieldstocopy) ";
					$query .= "VALUES (:stuid,$insrow)";
					$stm = $DBH->prepare($query);
					$row[':stuid'] = $get_uid;
					$stm->execute($row);
					$asid = $DBH->lastInsertId();
					$_GET['asid'] = $asid;
				}
			}
			$stugroupmem[] = $get_uid;

			if ($doadd) {			
				require("../assessment/asidutil.php");
				list($qlist,$seedlist,$reviewseedlist,$scorelist,$attemptslist,$lalist) = generateAssessmentData($adata['itemorder'],$adata['shuffle'],$aid);
				//$starttime = time();
				foreach ($stugroupmem as $uid) {
					//DB $query = "INSERT INTO imas_assessment_sessions (userid,agroupid,assessmentid,questions,seeds,scores,attempts,lastanswers,starttime,bestscores,bestattempts,bestseeds,bestlastanswers,reviewscores,reviewattempts,reviewseeds,reviewlastanswers) ";
					//DB $query .= "VALUES ('$uid','$agroupid','$aid','$qlist','$seedlist','$scorelist;$scorelist','$attemptslist','$lalist',0,'$scorelist;$scorelist;$scorelist','$attemptslist','$seedlist','$lalist','$scorelist;$scorelist','$attemptslist','$reviewseedlist','$lalist');";
					//DB mysql_query($query) or die("Query failed : " . mysql_error());
					//DB $asid = mysql_insert_id();
					$query = "INSERT INTO imas_assessment_sessions (userid,agroupid,assessmentid,questions,seeds,scores,attempts,lastanswers,starttime,bestscores,bestattempts,bestseeds,bestlastanswers,reviewscores,reviewattempts,reviewseeds,reviewlastanswers,ver) ";
					$query .= "VALUES (:userid, :agroupid, :assessmentid, :questions, :seeds, :scores, :attempts, :lastanswers, :starttime, :bestscores, :bestattempts, :bestseeds, :bestlastanswers, :reviewscores, :reviewattempts, :reviewseeds, :reviewlastanswers, 2);";
					$stm = $DBH->prepare($query);
					$stm->execute(array(':userid'=>$uid, ':agroupid'=>$agroupid, ':assessmentid'=>$aid, ':questions'=>$qlist, ':seeds'=>$seedlist,
						':scores'=>"$scorelist;$scorelist", ':attempts'=>$attemptslist, ':lastanswers'=>$lalist, ':starttime'=>0,
						':bestscores'=>"$scorelist;$scorelist;$scorelist", ':bestattempts'=>$attemptslist, ':bestseeds'=>$seedlist, ':bestlastanswers'=>$lalist,
						':reviewscores'=>"$scorelist;$scorelist", ':reviewattempts'=>$attemptslist, ':reviewseeds'=>$reviewseedlist, ':reviewlastanswers'=>$lalist));
					$asid = $DBH->lastInsertId();
				}
				$_GET['asid'] = $asid;
			}
		}
		header('Location: ' . $GLOBALS['basesiteurl'] ."/course/gb-viewasid.php?stu=$stu&asid={$_GET['asid']}&from=$from&cid=$cid&uid={$get_uid}");

	}
	//PROCESS ANY TODOS
	if (isset($_REQUEST['clearattempt']) && isset($_GET['asid']) && $isteacher) {
		if (isset($_POST['clearattempt']) && $_POST['clearattempt']=='confirmed') {
			//DB $query = "SELECT ias.assessmentid,ias.lti_sourcedid FROM imas_assessment_sessions AS ias ";
			//DB $query .= "JOIN imas_assessments AS ia ON ias.assessmentid=ia.id WHERE ias.id='{$_GET['asid']}' AND ia.courseid='$cid'";
			//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			//DB if (mysql_num_rows($result)>0) {
			$query = "SELECT ias.assessmentid,ias.lti_sourcedid FROM imas_assessment_sessions AS ias ";
			$query .= "JOIN imas_assessments AS ia ON ias.assessmentid=ia.id WHERE ias.id=:id AND ia.courseid=:courseid";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':id'=>$_GET['asid'], ':courseid'=>$cid));
			if ($stm->rowCount()>0) {
				//DB $aid = mysql_result($result,0,0);
				//DB $ltisourcedid = mysql_result($result,0,1);
				list($aid, $ltisourcedid) = $stm->fetch(PDO::FETCH_NUM);
				if (strlen($ltisourcedid)>1) {
					require_once("../includes/ltioutcomes.php");
					updateLTIgrade('delete',$ltisourcedid,$aid);
				}

				$qp = getasidquery($_GET['asid']);
				deleteasidfilesbyquery2($qp[0],$qp[1],$qp[2],1);
				//deleteasidfilesbyquery(array($qp[0]=>$qp[1]),1);

				//DB $query = "DELETE FROM imas_assessment_sessions";// WHERE id='{$_GET['asid']}'";
				//DB $query .= " WHERE {$qp[0]}='{$qp[1]}' AND assessmentid='{$qp[2]}'";
				//DB //$query .= getasidquery($_GET['asid']);
				//DB $query = "DELETE FROM imas_assessment_sessions";
				//DB $query .= " WHERE {$qp[0]}='{$qp[1]}' AND assessmentid='{$qp[2]}'";
				//DB mysql_query($query) or die("Query failed : " . mysql_error());
				$query = "DELETE FROM imas_assessment_sessions";
				$query .= " WHERE {$qp[0]}=:qval AND assessmentid=:assessmentid"; //$qp[0] is "id" or "agroupid" from getasidquery
				$stm = $DBH->prepare($query);
				$stm->execute(array(':assessmentid'=>$qp[2], ':qval'=>$qp[1]));
			}
			if ($from=='isolate') {
				header('Location: ' . $GLOBALS['basesiteurl'] . "/course/isolateassessgrade.php?stu=$stu&cid=".Sanitize::courseId($_GET['cid'])."&aid=$aid&gbmode=$gbmode");
			} else if ($from=='gisolate') {
				header('Location: ' . $GLOBALS['basesiteurl'] . "/course/isolateassessbygroup.php?stu=$stu&cid=".Sanitize::courseId($_GET['cid'])."&aid=$aid&gbmode=$gbmode");
			} else if ($from=='stugrp') {
				header('Location: ' . $GLOBALS['basesiteurl'] . "/course/managestugrps.php?cid=".Sanitize::courseId($_GET['cid'])."&aid=$aid");
			} else if ($from=='gbtesting') {
				header('Location: ' . $GLOBALS['basesiteurl'] . "/course/gb-testing.php?stu=$stu&cid=".Sanitize::courseId($_GET['cid'])."&gbmode=$gbmode");
			} else {
				header('Location: ' . $GLOBALS['basesiteurl'] . "/course/gradebook.php?stu=$stu&cid=".Sanitize::courseId($_GET['cid'])."&gbmode=$gbmode");
			}
			exit;
		} else {
			$isgroup = isasidgroup($_GET['asid']);
			if ($isgroup) {
				$pers = 'group';
				$body = getconfirmheader(true);
			} else {
				$pers = 'student';
				$body = getconfirmheader();
			}
			$overwriteBody = true;
			$querystring = http_build_query(array('stu'=>$stu, 'cid'=>$cid, 'asid'=>$_GET['asid'], 'from'=>$from, 'uid'=>$get_uid));
			$body .= "<p>Are you sure you want to clear this $pers's assessment attempt?  This will make it appear the $pers never tried the assessment, and the $pers will receive a new version of the assessment.</p>";
			$body .= '<form method="POST" action="gb-viewasid.php?'.$querystring.'">';
			$body .= '<p><button type=submit name="clearattempt" value="confirmed">'._('Really Clear').'</button> ';
			$body .= "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='gb-viewasid.php?$querystring'\"></p>\n";
			$body .= '</form>';
			//exit;
		}
	}
	if (isset($_REQUEST['breakfromgroup']) && isset($_GET['asid']) && $isteacher) {
		if (isset($_POST['breakfromgroup']) && $_POST['breakfromgroup']=="confirmed") {
			include("../includes/stugroups.php");
			//DB $query = "SELECT userid,agroupid FROM imas_assessment_sessions WHERE id='{$_GET['asid']}'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $row = mysql_fetch_row($result);
			$stm = $DBH->prepare("SELECT userid,agroupid FROM imas_assessment_sessions WHERE id=:id");
			$stm->execute(array(':id'=>$_GET['asid']));
			$row = $stm->fetch(PDO::FETCH_NUM);
			removegroupmember($row[1],$row[0]);
			header('Location: ' . $GLOBALS['basesiteurl'] ."/course/gb-viewasid.php?stu=$stu&asid={$_GET['asid']}&from=$from&cid=$cid&uid={$get_uid}");
		} else {
			$overwriteBody = true;
			$body = getconfirmheader();
			$querystring = http_build_query(array('stu'=>$stu, 'cid'=>$cid, 'asid'=>$_GET['asid'], 'from'=>$from, 'uid'=>$get_uid));
			$body .= "<p>Are you sure you want to separate this student from their current group?</p>";
			$body .= '<form method="POST" action="gb-viewasid.php?'.$querystring.'">';
			$body .= '<p><button type=submit name="breakfromgroup" value="confirmed">'._('Really Separate').'</button> ';
			$body .= "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='gb-viewasid.php?$querystring'\"></p>\n";
			$body .= '</form>';
			//exit;
		}
	}
	if (isset($_REQUEST['clearscores']) && isset($_GET['asid']) && $isteacher) {
		if (isset($_POST['clearscores']) && $_POST['clearscores']=="confirmed") {

			//DB $query = "SELECT ias.assessmentid FROM imas_assessment_sessions AS ias ";
			//DB $query .= "JOIN imas_assessments AS ia ON ias.assessmentid=ia.id WHERE ias.id='{$_GET['asid']}' AND ia.courseid='$cid'";
			//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			//DB if (mysql_num_rows($result)>0) {
			$query = "SELECT ias.assessmentid FROM imas_assessment_sessions AS ias ";
			$query .= "JOIN imas_assessments AS ia ON ias.assessmentid=ia.id WHERE ias.id=:id AND ia.courseid=:courseid";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':id'=>$_GET['asid'], ':courseid'=>$cid));
			if ($stm->rowCount()>0) {
				//$whereqry = getasidquery($_GET['asid']);
				$qp = getasidquery($_GET['asid']);
				//deleteasidfilesbyquery(array($qp[0]=>$qp[1]),1);
				deleteasidfilesbyquery2($qp[0],$qp[1],$qp[2],1);
				//DB $whereqry = " WHERE {$qp[0]}='{$qp[1]}' AND assessmentid='{$qp[2]}'";
				//DB $query = "SELECT seeds,lti_sourcedid FROM imas_assessment_sessions $whereqry";
				//DB $query = "SELECT seeds,lti_sourcedid FROM imas_assessment_sessions WHERE {$qp[0]}='{$qp[1]}' AND assessmentid='{$qp[2]}'";
				//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
				$stm = $DBH->prepare("SELECT seeds,lti_sourcedid FROM imas_assessment_sessions WHERE {$qp[0]}=:qval AND assessmentid=:assessmentid");
				$stm->execute(array(':assessmentid'=>$qp[2], ':qval'=>$qp[1]));
				//DB $seeds = explode(',',mysql_result($result,0,0));
				//DB $ltisourcedid = mysql_result($result,0,1);
				list($seeds, $ltisourcedid) = $stm->fetch(PDO::FETCH_NUM);
				$seeds = explode(',', $seeds);
				if (strlen($ltisourcedid)>1) {
					require_once("../includes/ltioutcomes.php");
					updateLTIgrade('update',$ltisourcedid,$aid,0);
				}


				$scores = array_fill(0,count($seeds),-1);
				$attempts = array_fill(0,count($seeds),0);
				$lastanswers = array_fill(0,count($seeds),'');
				$scorelist = implode(",",$scores);
				$attemptslist = implode(",",$attempts);
				$lalist = implode("~",$lastanswers);
				$bestscorelist = implode(',',$scores);
				$bestattemptslist = implode(',',$attempts);
				$bestseedslist = implode(',',$seeds);
				$bestlalist = implode('~',$lastanswers);

				//DB $query = "UPDATE imas_assessment_sessions SET scores='$scorelist;$scorelist',attempts='$attemptslist',lastanswers='$lalist',reattempting='',";
				//DB $query .= "bestscores='$bestscorelist;$bestscorelist;$bestscorelist',bestattempts='$bestattemptslist',bestseeds='$bestseedslist',bestlastanswers='$bestlalist' ";
				//DB $query .= $whereqry;//"WHERE id='{$_GET['asid']}'";
				//DB mysql_query($query) or die("Query failed : " . mysql_error());
				$query = "UPDATE imas_assessment_sessions SET scores=:scores,attempts=:attempts,lastanswers=:lastanswers,reattempting='',";
				$query .= "bestscores=:bestscores,bestattempts=:bestattempts,bestseeds=:bestseeds,bestlastanswers=:bestlastanswers ";
				$query .= "WHERE {$qp[0]}=:qval AND assessmentid=:assessmentid";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':assessmentid'=>$qp[2], ':qval'=>$qp[1], ':attempts'=>$attemptslist, ':lastanswers'=>$lalist, ':scores'=>"$scorelist;$scorelist",
					':bestattempts'=>$bestattemptslist, ':bestseeds'=>$bestseedslist, ':bestlastanswers'=>$bestlalist, ':bestscores'=>"$bestscorelist;$bestscorelist;$bestscorelist"));
			}
			header('Location: ' . $GLOBALS['basesiteurl'] ."/course/gb-viewasid.php?stu=$stu&asid={$_GET['asid']}&from=$from&cid=$cid&uid={$get_uid}");
		} else {
			$isgroup = isasidgroup($_GET['asid']);
			$overwriteBody = true;

			if ($isgroup) {
				$pers = 'group';
				$body = getconfirmheader(true);
			} else {
				$pers = 'student';
				$body = getconfirmheader();
			}
			$querystring = http_build_query(array('stu'=>$stu, 'cid'=>$cid, 'asid'=>$_GET['asid'], 'from'=>$from, 'uid'=>$get_uid));
			$body .= "<p>Are you sure you want to clear this $pers's scores for this assessment?</p>";
			$body .= '<form method="POST" action="gb-viewasid.php?'.$querystring.'">';
			$body .= '<p><button type=submit name="clearscores" value="confirmed">'._('Really Clear').'</button> ';
			$body .= "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='gb-viewasid.php?$querystring'\"></p>\n";
			$body .= '</form>';
			//exit;
		}
	}
	if (isset($_REQUEST['clearq']) && isset($_GET['asid']) && $isteacher) {
		if (isset($_POST['clearq'])) { //postback
			$qp = getasidquery($_GET['asid']);
			//DB $whereqry = " WHERE {$qp[0]}='{$qp[1]}' AND assessmentid='{$qp[2]}'";
			//$whereqry = getasidquery($_GET['asid']);

			//DB $query = "SELECT attempts,lastanswers,reattempting,scores,bestscores,bestattempts,bestlastanswers,lti_sourcedid FROM imas_assessment_sessions $whereqry ORDER BY id"; //WHERE id='{$_GET['asid']}'";
			//DB $query = "SELECT attempts,lastanswers,reattempting,scores,bestscores,bestattempts,bestlastanswers,lti_sourcedid ";
			//DB $query .= "FROM imas_assessment_sessions WHERE {$qp[0]}='{$qp[1]}' AND assessmentid='{$qp[2]}' ORDER BY id";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $line = mysql_fetch_array($result, MYSQL_ASSOC);
			$query = "SELECT attempts,lastanswers,reattempting,scores,seeds,bestscores,bestattempts,bestlastanswers,bestseeds,lti_sourcedid ";
			$query .= "FROM imas_assessment_sessions WHERE {$qp[0]}=:qval AND assessmentid=:assessmentid ORDER BY id";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':assessmentid'=>$qp[2], ':qval'=>$qp[1]));
			$line = $stm->fetch(PDO::FETCH_ASSOC);

			if (strpos($line['scores'],';')===false) {
				$noraw = true;
				$scores = explode(",",$line['scores']);
				$bestscores = explode(",",$line['bestscores']);
			} else {
				$sp = explode(';',$line['scores']);
				$scores = explode(',', $sp[0]);
				$rawscores = explode(',', $sp[1]);
				$sp = explode(';',$line['bestscores']);
				$bestscores = explode(',', $sp[0]);
				$bestrawscores = explode(',', $sp[1]);
				$firstrawscores = explode(',', $sp[2]);
				$noraw = false;
			}

			$attempts = explode(",",$line['attempts']);
			$seeds = explode(",",$line['seeds']);
			$lastanswers = explode("~",$line['lastanswers']);
			$reattempting = explode(',',$line['reattempting']);
			$bestattempts = explode(",",$line['bestattempts']);
			$bestlastanswers = explode("~",$line['bestlastanswers']);
			$bestseeds = explode(",",$line['bestseeds']);

			$clearid = $_POST['clearq'];
			if ($clearid!=='' && is_numeric($clearid) && isset($scores[$clearid])) {
				deleteasidfilesfromstring2($lastanswers[$clearid].$bestlastanswers[$clearid],$qp[0],$qp[1],$qp[2]);
				$scores[$clearid] = -1;
				$attempts[$clearid] = 0;
				$lastanswers[$clearid] = '';
				$bestscores[$clearid] = -1;
				$bestattempts[$clearid] = 0;
				$bestlastanswers[$clearid] = '';
				if (!$noraw) {
					$rawscores[$clearid] = -1;
					$bestrawscores[$clearid] = -1;
					$firstscores[$clearid] = -1;
				}
				if (isset($_POST['regen'])) {
					$seeds[$clearid] = rand(1,9999);
					$bestseeds[$clearid] = $seeds[$clearid];
				}

				$loc = array_search($clearid,$reattempting);
				if ($loc!==false) {
					array_splice($reattempting,$loc,1);
				}

				if (!$noraw) {
					$scorelist = implode(",",$scores).';'.implode(",",$rawscores);
					$bestscorelist = implode(',',$bestscores).';'.implode(",",$bestrawscores).';'.implode(",",$firstscores);
				} else {
					$scorelist = implode(",",$scores);
					$bestscorelist = implode(',',$bestscores);
				}
				$attemptslist = implode(",",$attempts);
				$seedlist = implode(",",$seeds);
				$bestseedlist = implode(",",$bestseeds);
				//DB $lalist = addslashes(implode("~",$lastanswers));
				$lalist = implode("~",$lastanswers);

				$bestattemptslist = implode(',',$bestattempts);
				//DB $bestlalist = addslashes(implode('~',$bestlastanswers));
				$bestlalist = implode('~',$bestlastanswers);
				$reattemptinglist = implode(',',$reattempting);

				//DB $query = "UPDATE imas_assessment_sessions SET scores='$scorelist',attempts='$attemptslist',lastanswers='$lalist',";
				//DB $query .= "bestscores='$bestscorelist',bestattempts='$bestattemptslist',bestlastanswers='$bestlalist',reattempting='$reattemptinglist' ";
				//DB $query .= $whereqry; //"WHERE id='{$_GET['asid']}'";
				//DB mysql_query($query) or die("Query failed : " . mysql_error());
				$query = "UPDATE imas_assessment_sessions SET scores=:scores,attempts=:attempts,lastanswers=:lastanswers,seeds=:seeds,";
				$query .= "bestscores=:bestscores,bestattempts=:bestattempts,bestlastanswers=:bestlastanswers,bestseeds=:bestseeds,reattempting=:reattempting ";
				$query .= "WHERE {$qp[0]}=:qval AND assessmentid=:assessmentid ";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':assessmentid'=>$qp[2], ':qval'=>$qp[1], ':scores'=>$scorelist, ':attempts'=>$attemptslist, ':lastanswers'=>$lalist, ':seeds'=>$seedlist,
					':bestscores'=>$bestscorelist, ':bestattempts'=>$bestattemptslist, ':bestlastanswers'=>$bestlalist, ':bestseeds'=>$bestseedlist, ':reattempting'=>$reattemptinglist));
				if (strlen($line['lti_sourcedid'])>1) {
					require_once("../includes/ltioutcomes.php");
					calcandupdateLTIgrade($line['lti_sourcedid'],$aid,$bestscores);
				}

				header('Location: ' . $GLOBALS['basesiteurl'] ."/course/gb-viewasid.php?stu=$stu&asid={$_GET['asid']}&from=$from&cid=$cid&uid={$get_uid}");
			} else {
				echo "$clearid";
				print_r($scores);
				echo "<p>Error.  Try again.</p>";
			}
			//unset($_GET['asid']);
			unset($_GET['clearq']);

		} else {
			$isgroup = isasidgroup($_GET['asid']);
			$overwriteBody = true;
			if ($isgroup) {
				$pers = 'group';
				$body = getconfirmheader(true);
			} else {
				$pers = 'student';
				$body = getconfirmheader();
			}
			$querystring = http_build_query(array('stu'=>$stu, 'cid'=>$cid, 'asid'=>$_GET['asid'], 'from'=>$from, 'uid'=>$get_uid));
			$body .= "<p>Are you sure you want to clear this $pers's scores for this question?</p>";
			$body .= '<form method="POST" action="gb-viewasid.php?'.$querystring.'">';
			$body .= '<p><button type=submit name="noregen" value="1">'._('Really Clear').'</button> ';
			$body .= '<button type=submit name="regen" value="1">'._('Really Clear and Regen').'</button> ';
			$body .= '<input type="hidden" name="clearq" value="'.Sanitize::encodeStringForDisplay($_GET['clearq']).'"/>';
			$body .= "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='gb-viewasid.php?$querystring'\"></p>\n";
			$body .= '</form>';

			//echo "<p><input type=button onclick=\"window.location='gb-viewasid.php?stu=$stu&gbmode=$gbmode&cid=$cid&from=$from&asid={$_GET['asid']}&clearq={$_GET['clearq']}&uid={$get_uid}&confirmed=true'\" value=\"Really Clear\"> \n";
			//echo "<input type=button onclick=\"window.location='gb-viewasid.php?stu=$stu&gbmode=$gbmode&cid=$cid&from=$from&asid={$_GET['asid']}&clearq={$_GET['clearq']}&uid={$get_uid}&regen=1&confirmed=true'\" value=\"Really Clear and Regen\"> \n";
			//echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='gb-viewasid.php?stu=$stu&from=$from&gbmode=$gbmode&cid=$cid&asid={$_GET['asid']}&uid={$get_uid}'\"></p>\n";
			//exit;
		}
	}
	if (isset($_GET['forcegraphimg'])) {
		$sessiondata['graphdisp'] = 2;
	}

	//OUTPUTS
	if ($links==0) { //View/Edit full assessment
		require("../assessment/displayq2.php");
		require_once("../includes/htmLawed.php");
		if (isset($_GET['update']) && ($isteacher || $istutor)) {
			if (isoktorec()) {
				//DB $query = "SELECT bestscores FROM imas_assessment_sessions WHERE id='{$_GET['asid']}'";
				//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
				//DB $bestscores = mysql_result($result,0,0);
				$stm = $DBH->prepare("SELECT bestscores FROM imas_assessment_sessions WHERE id=:id");
				$stm->execute(array(':id'=>$_GET['asid']));
				$bestscores = $stm->fetchColumn(0);
				$bsp = explode(';',$bestscores);

				$scores = array();
				$feedback = array();
				$i = 0;
				while (isset($_POST['sb-'.$i]) || isset($_POST["sb-$i-0"])) {
					$j=0;
					$scpt = array();
					if (isset($_POST["sb-$i-0"])) {

						while (isset($_POST["sb-$i-$j"])) {
							if ($_POST["sb-$i-$j"]!='N/A' && $_POST["sb-$i-$j"]!='NA') {
								$scpt[$j] = $_POST["sb-$i-$j"];
							} else {
								$scpt[$j] = -1;
							}
							$j++;
						}
						$scores[$i] = implode('~',$scpt);
					} else {
						if ($_POST['sb-'.$i]!='N/A' && $_POST["sb-$i-$j"]!='NA') {
							$scores[$i] = $_POST['sb-'.$i];
						} else {
							$scores[$i] = -1;
						}
					}
					if (trim(strip_tags($_POST["fb-$i"])) != '') { 
						$feedback["Q$i"] = Sanitize::incomingHtml($_POST["fb-$i"]);
					}
					$i++;
				}
				$scorelist = implode(",",$scores);
				if (count($bsp)>1) { //tack on rawscores and firstscores
					$scorelist .= ';'.$bsp[1].';'.$bsp[2];
				}
				if (trim(strip_tags($_POST['feedback'])) != '') {
					$feedback['Z'] = Sanitize::incomingHtml($_POST['feedback']);
				}
				if (count($feedback)>0) {
					$feedbackout = json_encode($feedback);
				} else {
					$feedbackout = '';
				}

				if (isset($_POST['updategroup'])) {
					$qp = getasidquery($_GET['asid']);
					//DB $query = "UPDATE imas_assessment_sessions SET bestscores='$scorelist',feedback='$feedback'";
					//DB $query .=  " WHERE {$qp[0]}='{$qp[1]}' AND assessmentid='{$qp[2]}'";
					$query = "UPDATE imas_assessment_sessions SET bestscores=:bestscores,feedback=:feedback";
					$query .=  " WHERE {$qp[0]}=:qval AND assessmentid=:assessmentid";
					$stm = $DBH->prepare($query);
					$stm->execute(array(':bestscores'=>$scorelist, ':feedback'=>$feedbackout, ':assessmentid'=>$qp[2], ':qval'=>$qp[1]));
					//$query .= getasidquery($_GET['asid']);
				} else {
					//DB $query = "UPDATE imas_assessment_sessions SET bestscores='$scorelist',feedback='$feedback'";
					//DB $query .= "WHERE id='{$_GET['asid']}'";
					$query = "UPDATE imas_assessment_sessions SET bestscores=:bestscores,feedback=:feedback WHERE id=:id";
					$stm = $DBH->prepare($query);
					$stm->execute(array(':bestscores'=>$scorelist, ':feedback'=>$feedbackout, ':id'=>$_GET['asid']));
				}
				//DB $q2 = "SELECT assessmentid,lti_sourcedid FROM imas_assessment_sessions WHERE id='{$_GET['asid']}'";
				//DB $res = mysql_query($q2) or die("Query failed : $q2 " . mysql_error());
				//DB $row = mysql_fetch_row($res);
				$stm = $DBH->prepare("SELECT assessmentid,lti_sourcedid FROM imas_assessment_sessions WHERE id=:id");
				$stm->execute(array(':id'=>$_GET['asid']));
				$row = $stm->fetch(PDO::FETCH_NUM);
				$aid = $row[0];
				if (strlen($row[1])>1) {
					//update LTI score
					require_once("../includes/ltioutcomes.php");
					calcandupdateLTIgrade($row[1],$row[0],$scores);
				}
			} else {
				echo "No authority to change scores.";
				exit;
			}
			//DB mysql_query($query) or die("Query failed : $query " . mysql_error());
			if ($from=='isolate') {
				header('Location: ' . $GLOBALS['basesiteurl'] . "/course/isolateassessgrade.php?stu=$stu&cid=".Sanitize::courseId($_GET['cid'])."&aid=$aid&gbmode=$gbmode");
			} else if ($from=='gisolate') {
				header('Location: ' . $GLOBALS['basesiteurl'] . "/course/isolateassessbygroup.php?stu=$stu&cid=".Sanitize::courseId($_GET['cid'])."&aid=$aid&gbmode=$gbmode");
			} else if ($from=='stugrp') {
				header('Location: ' . $GLOBALS['basesiteurl'] . "/course/managestugrps.php?cid=".Sanitize::courseId($_GET['cid'])."&aid=$aid");
			} else if ($from=='gbtesting') {
				header('Location: ' . $GLOBALS['basesiteurl'] . "/course/gb-testing.php?stu=$stu&cid=".Sanitize::courseId($_GET['cid'])."&gbmode=$gbmode");
			} else {
				header('Location: ' . $GLOBALS['basesiteurl'] . "/course/gradebook.php?stu=$stu&cid=".Sanitize::courseId($_GET['cid'])."&gbmode=$gbmode");
			}
			exit;
		}
		$useeditor='review';
		$sessiondata['coursetheme'] = $coursetheme;
		$sessiondata['isteacher'] = $isteacher;
		if ($isteacher || $istutor) {
			$placeinhead = '<script type="text/javascript" src="'.$imasroot.'/javascript/rubric.js?v=031417"></script>';
			require("../includes/rubric.php");
			$placeinhead .= '<script type="text/javascript" src="'.$imasroot.'/javascript/gb-scoretools.js?v=120617"></script>';
			if ($sessiondata['useed']!=0) {
				$placeinhead .= '<script type="text/javascript"> initeditor("divs","fbbox",null,true);</script>';
			}
		}
		require("../assessment/header.php");
		echo "<style type=\"text/css\">p.tips {	display: none;} .pseudohidden {visibility:hidden;position:absolute;}\n</style>\n";
		if (isset($_GET['starttime']) && $isteacher) {

			//$query .= getasidquery($_GET['asid']);
			$qp = getasidquery($_GET['asid']);
			//DB $query = "UPDATE imas_assessment_sessions SET starttime='{$_GET['starttime']}' ";
			//DB $query .=  " WHERE {$qp[0]}='{$qp[1]}' AND assessmentid='{$qp[2]}'";
			//DB mysql_query($query) or die("Query failed : $query " . mysql_error());
			$query = "UPDATE imas_assessment_sessions SET starttime=:starttime ";
			$query .= "WHERE {$qp[0]}=:qval AND assessmentid=:assessmentid";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':starttime'=>$_GET['starttime'], ':assessmentid'=>$qp[2], ':qval'=>$qp[1]));
		}

		//DB $query = "SELECT imas_assessments.name,imas_assessments.timelimit,imas_assessments.defpoints,imas_assessments.tutoredit,imas_assessments.defoutcome,";
		//DB $query .= "imas_assessments.showhints,imas_assessments.deffeedback,imas_assessments.enddate,imas_assessment_sessions.* ";
		//DB $query .= "FROM imas_assessments,imas_assessment_sessions ";
		//DB $query .= "WHERE imas_assessments.id=imas_assessment_sessions.assessmentid AND imas_assessment_sessions.id='{$_GET['asid']}' AND imas_assessments.courseid='$cid'";
		//DB if (!$isteacher && !$istutor) {
			//DB $query .= " AND imas_assessment_sessions.userid='$userid'";
		//DB }
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB if (mysql_num_rows($result)==0) {
		$query = "SELECT imas_assessments.name,imas_assessments.timelimit,imas_assessments.defpoints,imas_assessments.tutoredit,imas_assessments.defoutcome,";
		$query .= "imas_assessments.showhints,imas_assessments.deffeedback,imas_assessments.startdate,imas_assessments.enddate,imas_assessments.allowlate,imas_assessment_sessions.* ";
		$query .= "FROM imas_assessments,imas_assessment_sessions ";
		$query .= "WHERE imas_assessments.id=imas_assessment_sessions.assessmentid AND imas_assessment_sessions.id=:id AND imas_assessments.courseid=:courseid";
		if (!$isteacher && !$istutor) {
			$query .= " AND imas_assessment_sessions.userid=:userid";
		}
		$stm = $DBH->prepare($query);
		if (!$isteacher && !$istutor) {
			$stm->execute(array(':id'=>$_GET['asid'], ':courseid'=>$cid, ':userid'=>$userid));
		} else {
			$stm->execute(array(':id'=>$_GET['asid'], ':courseid'=>$cid));
		}
		if ($stm->rowCount()==0) {
			echo "uh oh.  Bad assessment id";
			exit;
		}
		//DB $line=mysql_fetch_array($result, MYSQL_ASSOC);
		$line=$stm->fetch(PDO::FETCH_ASSOC);
		$GLOBALS['assessver'] = $line['ver'];

		if (!$isteacher && !$istutor) {
			//DB $query = "INSERT INTO imas_content_track (userid,courseid,type,typeid,viewtime) VALUES ";
			//DB $query .= "($userid,'$cid','gbviewasid','{$line['assessmentid']}',$now)";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			$query = "INSERT INTO imas_content_track (userid,courseid,type,typeid,viewtime) VALUES ";
			$query .= "(:userid, :courseid, 'gbviewasid', :typeid, :viewtime)";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':userid'=>$userid, ':courseid'=>$cid, ':typeid'=>$line['assessmentid'], ':viewtime'=>$now));
		}

		echo "<div class=breadcrumb>$breadcrumbbase ";
		if (!isset($sessiondata['ltiitemtype']) || $sessiondata['ltiitemtype']!=0) {
			echo "<a href=\"course.php?cid=".Sanitize::courseId($_GET['cid'])."\">".Sanitize::encodeStringForDisplay($coursename)."</a> &gt; ";

			if ($stu>0) {
				echo "<a href=\"gradebook.php?stu=0&cid=$cid\">Gradebook</a> ";
				echo "&gt; <a href=\"gradebook.php?stu=$stu&cid=$cid\">Student Detail</a> &gt; ";
				$backurl = "gradebook.php?stu=$stu&cid=$cid";
			} else if ($_GET['from']=="isolate") {
				echo " <a href=\"gradebook.php?stu=0&cid=$cid\">Gradebook</a> ";
				echo "&gt; <a href=\"isolateassessgrade.php?cid=$cid&aid={$line['assessmentid']}\">View Scores</a> &gt; ";
				$backurl = "isolateassessgrade.php?cid=$cid&aid={$line['assessmentid']}";
			} else if ($_GET['from']=="gisolate") {
				echo "<a href=\"gradebook.php?stu=0&cid=$cid\">Gradebook</a> ";
				echo "&gt; <a href=\"isolateassessbygroup.php?cid=$cid&aid={$line['assessmentid']}\">View Group Scores</a> &gt; ";
				$backurl = "isolateassessbygroup.php?cid=$cid&aid={$line['assessmentid']}";
			} else if ($_GET['from']=='stugrp') {
				echo "<a href=\"managestugrps.php?cid=$cid&aid={$line['assessmentid']}\">Student Groups</a> &gt; ";
				$backurl = "managestugrps.php?cid=$cid&aid={$line['assessmentid']}";
			} else if ($_GET['from']=='gbtesting') {
				echo "<a href=\"gb-testing.php?stu=0&cid=$cid\">Diagnostic Gradebook</a> &gt; ";
				$backurl = "gb-testing.php?stu=0&cid=$cid";
			} else {
				echo "<a href=\"gradebook.php?stu=0&cid=$cid\">Gradebook</a> &gt; ";
				$backurl = "gradebook.php?stu=0&cid=$cid";
			}
		}
		if ($overwriteBody) { //doing a confirm action
			echo '<a href="'.Sanitize::encodeStringForDisplay('gb-viewasid.php?stu='.$stu.'&asid='.$_GET['asid'].'&from='.$from.'&cid='.$cid.'&uid='.$get_uid).'&from='.$_GET['from'].'">';
			echo _('Assessment Detail').'</a> &gt; Confirm Action</div>';
			echo $body;
			require("../footer.php");
			exit;
		} else {
			echo "Detail</div>";
			}
		if (($isteacher || $istutor) && isset($_GET['asid']) && $_GET['asid']!="new") {
			echo '<div class="cpmid">';
			echo '<a href="'.Sanitize::encodeStringForDisplay('gb-viewasid.php?stu='.$stu.'&asid='.$_GET['asid'].'&from='.$from.'&cid='.$cid.'&uid='.$get_uid.'&links=1').'">';
			echo _('Show Score Summary');
			echo '</a>';
			echo '</div>';
		}
		echo '<div id="headergb-viewasid" class="pagetitle"><h2>Grade Book Detail</h2></div>';

		//DB $query = "SELECT imas_users.FirstName,imas_users.LastName,imas_students.timelimitmult FROM imas_users JOIN imas_students ON imas_users.id=imas_students.userid WHERE imas_users.id='{$get_uid}' AND imas_students.courseid='$cid'";
		//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		//DB $row = mysql_fetch_row($result);
		$stm = $DBH->prepare("SELECT imas_users.FirstName,imas_users.LastName,imas_students.timelimitmult FROM imas_users JOIN imas_students ON imas_users.id=imas_students.userid WHERE imas_users.id=:id AND imas_students.courseid=:courseid");
		$stm->execute(array(':id'=>$get_uid, ':courseid'=>$cid));
		$row = $stm->fetch(PDO::FETCH_NUM);
		echo "<h3>{$row[1]}, {$row[0]}</h3>\n";


		//do time limit mult
		$timelimitmult = $row[2];
		$line['timelimit'] *= $timelimitmult;

		//unencode feedback
		$feedback = json_decode($line['feedback'], true);
		if ($feedback === null) {
			$feedback = array('Z'=>$line['feedback']);
		}

		$teacherreview = $get_uid;

		if ($isteacher || ($istutor && $line['tutoredit']==1)) {
			$canedit = 1;
		} else {
			$canedit = 0;
		}

		if ($canedit) {
			//DB $query = "SELECT id,rubrictype,rubric FROM imas_rubrics WHERE id IN ";
			//DB $query .= "(SELECT DISTINCT rubric FROM imas_questions WHERE assessmentid={$line['assessmentid']} AND rubric>0)";
			//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
			//DB if (mysql_num_rows($result)>0) {
			$query = "SELECT id,rubrictype,rubric FROM imas_rubrics WHERE id IN ";
			$query .= "(SELECT DISTINCT rubric FROM imas_questions WHERE assessmentid=:assessmentid AND rubric>0)";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':assessmentid'=>$line['assessmentid']));
			if ($stm->rowCount()>0) {
				$rubrics = array();
				//DB while ($row = mysql_fetch_row($result)) {
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					$rubrics[] = $row;
				}
				echo printrubrics($rubrics);
			}
			unset($rubrics);
		}

		list($testtype,$showans) = explode('-',$line['deffeedback']);
		if ($showans=='N' && !$isteacher && !$istutor) {
			echo "You shouldn't be here";
			require("../footer.php");
			exit;
		}
		echo "<h4>{$line['name']}</h4>\n";

		$aid = $line['assessmentid'];

		if (($isteacher || $istutor) && !isset($_GET['lastver']) && !isset($_GET['reviewver'])) {
			if ($line['agroupid']>0) {
				//DB $q2 = "SELECT i_u.LastName,i_u.FirstName FROM imas_assessment_sessions AS i_a_s,imas_users AS i_u WHERE ";
				//DB $q2 .= "i_u.id=i_a_s.userid AND i_a_s.assessmentid='$aid' AND i_a_s.agroupid='{$line['agroupid']}' ORDER BY LastName,FirstName";
				//DB $result = mysql_query($q2) or die("Query failed : " . mysql_error());
				$query = "SELECT i_u.LastName,i_u.FirstName FROM imas_assessment_sessions AS i_a_s,imas_users AS i_u WHERE ";
				$query .= "i_u.id=i_a_s.userid AND i_a_s.assessmentid=:assessmentid AND i_a_s.agroupid=:agroupid ORDER BY LastName,FirstName";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':assessmentid'=>$aid, ':agroupid'=>$line['agroupid']));
				echo "<p>Group members: <ul>";
				//DB while ($row = mysql_fetch_row($result)) {
				while ($row = $stm->fetch(PDO::FETCH_NUM)) {
					echo "<li>{$row[0]}, {$row[1]}</li>";
				}
				echo "</ul></p>";
			}
		}

		if ($line['starttime']==0) {
			echo '<p>Started: Not yet started<br/>';
		} else {
			echo "<p>Started: " . tzdate("F j, Y, g:i a",$line['starttime']) ."<br/>\n";
		}
		if ($line['endtime']==0) {
			echo "Not Submitted</p>\n";
		} else {
			echo "Last change: " . tzdate("F j, Y, g:i a",$line['endtime']) . "<br/>";
			$timespent = round(($line['endtime']-$line['starttime'])/60);
			if ($timespent<250) {
				echo "Time spent: ". $timespent . " minutes<br/>\n";
			}
			$timeontask = array_sum(explode(',',str_replace('~',',',$line['timeontask'])));
			if ($timeontask>0) {
				echo "Total time questions were on-screen: ". round($timeontask/60,1) . " minutes.\n";
			}
			echo '</p>';
		}
		$saenddate = $line['enddate'];
		unset($exped);
		//DB $query = "SELECT enddate FROM imas_exceptions WHERE userid='{$get_uid}' AND assessmentid='{$line['assessmentid']}' AND itemtype='A'";
		//DB $r2 = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB if (mysql_num_rows($r2)>0) {
			//DB $exped = mysql_result($r2,0,0);
		$stm2 = $DBH->prepare("SELECT startdate,enddate,islatepass FROM imas_exceptions WHERE userid=:userid AND assessmentid=:assessmentid AND itemtype='A'");
		$stm2->execute(array(':userid'=>$get_uid, ':assessmentid'=>$line['assessmentid']));
		$useexception = false;
		if ($stm2->rowCount()>0) {
			$exception = $stm2->fetch(PDO::FETCH_NUM);
			$exped = $exception[1];
			if ($exped>$saenddate) {
				$saenddate = $exped;
			}
			require("../includes/exceptionfuncs.php");
			$exceptionfuncs = new ExceptionFuncs($userid, $cid, !($isteacher || $istutor));
			$useexception = $exceptionfuncs->getCanUseAssessException($exception, $line, true);
		}

		if ($isteacher) {
			if (isset($exped) && $exped!=$line['enddate']) {
				$lpnote = ($exception[2]>0)?" (LatePass)":"";
				if ($useexception) {
					echo "<p>Has exception$lpnote, with due date: ".tzdate("F j, Y, g:i a",$exped);
					echo "  <button type=\"button\" onclick=\"window.location.href='exception.php?cid=$cid&aid={$line['assessmentid']}&uid={$get_uid}&asid={$_GET['asid']}&from=$from&stu=$stu'\">Edit Exception</button>";
					echo "<br/>Original Due Date: ". ($line['enddate']==2000000000?"None":tzdate("F j, Y, g:i a",$line['enddate']));
				} else {
					echo "<p>Had exception$lpnote, with due date: ".tzdate("F j, Y, g:i a",$exped);
					echo "  <button type=\"button\" onclick=\"window.location.href='exception.php?cid=$cid&aid={$line['assessmentid']}&uid={$get_uid}&asid={$_GET['asid']}&from=$from&stu=$stu'\">Edit Exception</button>";
					echo "<br/>Assessment Due Date is being used instead: ". ($line['enddate']==2000000000?"None":tzdate("F j, Y, g:i a",$line['enddate']));

				}
			} else {
				echo "<p>Due Date: ". ($line['enddate']==2000000000?"None":tzdate("F j, Y, g:i a",$line['enddate']));
				echo "  <button type=\"button\" onclick=\"window.location.href='exception.php?cid=$cid&aid={$line['assessmentid']}&uid={$get_uid}&asid={$_GET['asid']}&from=$from&stu=$stu'\">Make Exception</button>";
			}
			echo "</p>";
		}
		if ($isteacher) {
			if ($line['agroupid']>0) {
				echo "<p>This assignment is linked to a group.  Changes will affect the group unless specified. ";
				echo "<a href=\"gb-viewasid.php?stu=$stu&cid=$cid&asid={$_GET['asid']}&from=$from&uid={$get_uid}&breakfromgroup=true\">Separate from Group</a></p>";
			}
		}
		echo "<form id=\"mainform\" method=post action=\"gb-viewasid.php?stu=$stu&cid=$cid&from=$from&asid={$_GET['asid']}&update=true\">\n";

		if ($isteacher) {
			echo "<div class=\"cpmid\"><a href=\"gb-viewasid.php?stu=$stu&cid=$cid&asid={$_GET['asid']}&from=$from&uid={$get_uid}&clearattempt=true\" onmouseover=\"tipshow(this,'Clear everything, resetting things like the student never started.  Student will get new versions of questions.')\" onmouseout=\"tipout()\">Clear Attempt</a> | ";
			echo "<a href=\"gb-viewasid.php?stu=$stu&cid=$cid&asid={$_GET['asid']}&from=$from&uid={$get_uid}&clearscores=true\" onmouseover=\"tipshow(this,'Clear scores and attempts, but keep same versions of questions')\" onmouseout=\"tipout()\">Clear Scores</a> | ";
			echo "<a href=\"#\" onclick=\"markallfullscore();$('#uppersubmit').show();return false;\" onmouseover=\"tipshow(this,'Change all scores to full credit')\" onmouseout=\"tipout()\">All Full Credit</a> ";
			echo '<input style="display:none;" id="uppersubmit" type="submit" value="Record Changed Grades"> | ';
			echo "<a href=\"$imasroot/assessment/showtest.php?cid=$cid&id={$line['assessmentid']}&actas={$get_uid}\" onmouseover=\"tipshow(this,'Take on role of this student, bypassing date restrictions, to submit answers')\" onmouseout=\"tipout()\">View as student</a> | ";
			echo "<a href=\"$imasroot/assessment/printtest.php?cid=$cid&asid={$_GET['asid']}\" target=\"_blank\" onmouseover=\"tipshow(this,'Pull up a print version of this student\'s assessment')\" onmouseout=\"tipout()\">Print Version</a> ";

			echo "</div>\n";
		}

		if (($line['timelimit']>0) && ($line['endtime'] - $line['starttime'] > $line['timelimit'])) {
			$over = $line['endtime']-$line['starttime'] - $line['timelimit'];
			echo "<p>Time limit exceeded by ";
			if ($over > 60) {
				$overmin = floor($over/60);
				echo "$overmin minutes, ";
				$over = $over - $overmin*60;
			}
			echo "$over seconds.<BR>\n";
			$reset = $line['endtime']-$line['timelimit'];
			if ($isteacher) {
				echo "<a href=\"gb-viewasid.php?stu=$stu&starttime=$reset&asid={$_GET['asid']}&from=$from&cid=$cid&uid={$get_uid}\">Clear overtime and accept grade</a></p>\n";
			}
		}



		if (strpos($line['questions'],';')===false) {
			$questions = explode(",",$line['questions']);
			$bestquestions = $questions;
		} else {
			list($questions,$bestquestions) = explode(";",$line['questions']);
			$questions = explode(",",$questions);
			$bestquestions = explode(",",$bestquestions);
		}
		if ($line['timeontask']=='') {
			$timesontask = array_fill(0,count($questions),'');
		} else {
			$timesontask = explode(',',$line['timeontask']);
		}
		if (isset($_GET['lastver'])) {
			$seeds = explode(",",$line['seeds']);
			$sp = explode(";",$line['scores']);
			$scores = explode(",",$sp[0]);
			if (isset($sp[1])) {$rawscores = explode(",",$sp[1]);}
			$attempts = explode(",",$line['attempts']);
			$lastanswers = explode("~",$line['lastanswers']);
			echo "<p>";
			echo "<a href=\"gb-viewasid.php?stu=$stu&asid={$_GET['asid']}&from=$from&cid=$cid&uid={$get_uid}\">Show Scored Attempts</a> | ";
			echo "<b>Showing Last Attempts</b> | ";
			echo "<a href=\"gb-viewasid.php?stu=$stu&asid={$_GET['asid']}&from=$from&cid=$cid&uid={$get_uid}&reviewver=1\">Show Review Attempts</a>";
			echo "</p>";
		} else if (isset($_GET['reviewver'])) {
			$seeds = explode(",",$line['reviewseeds']);
			$sp = explode(";",$line['reviewscores']);
			$scores = explode(",",$sp[0]);
			if (isset($sp[1])) {$rawscores = explode(",",$sp[1]);}
			$attempts = explode(",",$line['reviewattempts']);
			$lastanswers = explode("~",$line['reviewlastanswers']);
			echo "<p>";
			echo "<a href=\"gb-viewasid.php?stu=$stu&asid={$_GET['asid']}&from=$from&cid=$cid&uid={$get_uid}\">Show Scored Attempts</a> | ";
			echo "<a href=\"gb-viewasid.php?stu=$stu&asid={$_GET['asid']}&from=$from&cid=$cid&uid={$get_uid}&lastver=1\">Show Last Graded Attempts</a> | ";
			echo "<b>Showing Review Attempts</b>";
			echo "</p>";
		}else {
			$seeds = explode(",",$line['bestseeds']);
			$sp = explode(";",$line['bestscores']);
			$scores = explode(",",$sp[0]);
			if (isset($sp[1])) {$rawscores = explode(",",$sp[1]);}
			$attempts = explode(",",$line['bestattempts']);
			$lastanswers = explode("~",$line['bestlastanswers']);
			$questions = $bestquestions;
			echo "<p><b>Showing Scored Attempts</b> | ";
			echo "<a href=\"gb-viewasid.php?stu=$stu&asid={$_GET['asid']}&cid=$cid&from=$from&uid={$get_uid}&lastver=1\">Show Last Attempts</a> | ";
			echo "<a href=\"gb-viewasid.php?stu=$stu&asid={$_GET['asid']}&cid=$cid&from=$from&uid={$get_uid}&reviewver=1\">Show Review Attempts</a>";
			echo "</p>";
		}

		//DB $query = "SELECT iq.id,iq.points,iq.withdrawn,iqs.qtype,iqs.control,iq.rubric,iq.showhints,iqs.extref,iqs.ownerid ";
		//DB $query .= "FROM imas_questions AS iq, imas_questionset AS iqs ";
		//DB $query .= "WHERE iq.questionsetid=iqs.id AND iq.assessmentid='{$line['assessmentid']}'";
		//DB $result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
		$query = "SELECT iq.id AS qid,iq.points,iq.withdrawn,iq.rubric,iq.showhints,iqs.* ";
		$query .= "FROM imas_questions AS iq, imas_questionset AS iqs ";
		$query .= "WHERE iq.questionsetid=iqs.id AND iq.assessmentid=:assessmentid";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':assessmentid'=>$line['assessmentid']));
		$totalpossible = 0;
		$pts = array();
		$qsetids = array();
		$withdrawn = array();
		$rubric = array();
		$extref = array();
		$owners = array();
		$qsdata = array();
		//DB while ($r = mysql_fetch_row($result)) {
		while ($r = $stm->fetch(PDO::FETCH_ASSOC)) {
			if ($r['points']==9999) {
				$pts[$r['qid']] = $line['defpoints'];  //use defpoints
			} else {
				$pts[$r['qid']] = $r['points']; //use points from question
			}
			//$totalpossible += $pts[$r['qid']];  do later
			$withdrawn[$r['qid']] = $r['withdrawn'];
			$rubric[$r['qid']] = $r['rubric'];
			$qsetids[$r['qid']] = $r['id'];
			if ($r['qtype']=='multipart') {
				$answeights[$r['qid']] = getansweights($r['qid'],$r['control']);
				for ($i=0; $i<count($answeights[$r['qid']])-1; $i++) {
					$answeights[$r['qid']][$i] = round($answeights[$r['qid']][$i]*$pts[$r['qid']],2);
				}
				//adjust for rounding
				$diff = $pts[$r['qid']] - array_sum($answeights[$r['qid']]);
				$answeights[$r['qid']][count($answeights[$r['qid']])-1] += $diff;

			}
			if (($line['showhints']==1 && $r['showhints']!=1) || $r['showhints']==2) {
				if ($r['extref']!='') {
					$extref[$r['qid']] = explode('~~',$r['extref']);
				}
			}
			$owners[$r['qid']] = $r['ownerid'];
			$qsdata[$r['qid']] = $r;
		}

		echo '<p><button type="button" id="hctoggle" onclick="hidecorrect()">'._('Hide Correct Questions').'</button>';
		echo ' <button type="button" id="hptoggle" onclick="hideperfect()">'._('Hide Perfect Questions').'</button>';
		echo ' <button type="button" id="hnatoggle" onclick="hideNA()">'._('Hide Unanswered Questions').'</button>';
		echo ' <button type="button" id="showanstoggle" onclick="showallans()">'._('Show All Answers').'</button>';
		echo ' <button type="button" id="prevtoggle" onclick="previewall()">'._('Preview All').'</button></p>';
		$total = 0;

		for ($i=0; $i<count($questions);$i++) {
			echo "<div ";
			if ($canedit && getpts($scores[$i])==$pts[$questions[$i]]) {
				echo 'class="iscorrect isperfect"';
			} else if ($canedit && ((isset($rawscores) && isperfect($rawscores[$i])) || getpts($scores[$i])==$pts[$questions[$i]])) {
				echo 'class="iscorrect"';
			} else if ($scores[$i]==-1) {
				echo 'class="notanswered"';
			} else {
				echo 'class="iswrong"';
			}
			echo ' id="qwrap'.($i+1).'"';
			$totalpossible += $pts[$questions[$i]];
			echo '>';

			$qsetid = $qsetids[$questions[$i]];
			if ($isteacher || $istutor || ($testtype=="Practice" && $showans!="V") || ($testtype!="Practice" && (($showans=="I"  && !in_array(-1,$scores))|| ($showans!="V" && time()>$saenddate)))) {$showa=true;} else {$showa=false;}

			if (isset($answeights[$questions[$i]])) {
				$GLOBALS['questionscoreref'] = array("scorebox$i",$answeights[$questions[$i]]);
			} else {
				$GLOBALS['questionscoreref'] = array("scorebox$i",$pts[$questions[$i]]);
			}

			if (isset($rawscores[$i])) {
				//$colors = scorestocolors($rawscores[$i],$pts[$questions[$i]],$answeights[$questions[$i]],false);
				if (strpos($rawscores[$i],'~')!==false) {
					$colors = explode('~',$rawscores[$i]);
				} else {
					$colors = array($rawscores[$i]);
				}
			} else {
				$colors = array();
			}
			$capturechoices = true;
			$choicesdata = array();
			$qdatafordisplayq = $qsdata[$questions[$i]];
			$qtypes = displayq($i,$qsetid,$seeds[$i],$showa,false,$attempts[$i],false,false,false,$colors);
			echo '</div>';

			if ($scores[$i]==-1) { $scores[$i]="N/A";} else {$total+=getpts($scores[$i]);}
			echo "<div class=review>Question ".($i+1).": ";
			if ($withdrawn[$questions[$i]]==1) {
				echo "<span class=\"red\">Question Withdrawn</span> ";
			}
			list($pt,$parts) = printscore($scores[$i]);
			if ($canedit && $parts=='') {
				echo "<input type=text size=4 id=\"scorebox$i\" name=\"sb-$i\" value=\"$pt\">";
				if ($rubric[$questions[$i]]!=0) {
					echo printrubriclink($rubric[$questions[$i]],$pts[$questions[$i]],"scorebox$i","fb-$i",($i+1));
				}
			} else {
				echo $pt;
			}

			if ($parts!='') {
				if ($canedit) {
					echo " (parts: ";
					$prts = explode(', ',$parts);
					for ($j=0;$j<count($prts);$j++) {
						echo "<input type=text size=2 id=\"scorebox$i-$j\" name=\"sb-$i-$j\" value=\"{$prts[$j]}\">";
						if ($rubric[$questions[$i]]!=0) {
							echo printrubriclink($rubric[$questions[$i]],$answeights[$questions[$i]][$j],"scorebox$i-$j","fb-$i",($i+1).' pt '.($j+1));
						}
						echo ' ';
					}
					echo ")";
				} else {
					echo " (parts: $parts)";
				}
			}
			echo " out of {$pts[$questions[$i]]} ";
			if ($parts!='') {
				echo '(parts: ';
				for ($j=0;$j<count($answeights[$questions[$i]]);$j++) {
					if ($j>0) { echo ', ';}
					echo "<span id=\"ptpos$i-$j\">".$answeights[$questions[$i]][$j].'</span>';
				}
				echo ') ';
			}
			echo "in {$attempts[$i]} attempt(s) ";
			if ($isteacher || $istutor) {
				if (empty($feedback["Q$i"])) {
					echo '<a href="#" onclick="return revealfb('.$i.');" id="fb-'.$i.'-add">Add Feedback</a>';
					echo '<span id="fb-'.$i.'-wrap" style="display:none;">';
				} else {
					echo '<span id="fb-'.$i.'-wrap">';
				}
				echo '<br/>'._('Feedback').':<br/>';
				if ($sessiondata['useed']==0) {
					echo '<textarea id="fb-'.$i.'" name="fb-'.$i.'" class="fbbox" cols=60 rows=2>'.Sanitize::encodeStringForDisplay($feedback["Q$i"]).'</textarea>';
				} else {
					echo '<div id="fb-'.$i.'" class="fbbox" cols=60 rows=2>'.Sanitize::outgoingHtml($feedback["Q$i"]).'</div>';
				}
				echo '</span>';
					
				if ($canedit && getpts($scores[$i])==$pts[$questions[$i]]) {
					echo '<div class="iscorrect isperfect">';
				} else if ($canedit && ((isset($rawscores) && isperfect($rawscores[$i])) || getpts($scores[$i])==$pts[$questions[$i]])) {
					echo '<div class="iscorrect">';
				} else if ($scores[$i]==='N/A') {
					echo '<div class="notanswered">';
				} else {
					echo '<div>';
				}
				if ($canedit && $parts!='') {
					$togr = array();
					foreach ($qtypes as $k=>$t) {
						if ($t=='essay' || $t=='file') {
							$togr[] = $k;
						}
					}

					echo 'Quick grade: <a href="#" class="quickgrade" onclick="quickgrade('.$i.',0,\'scorebox\','.count($prts).',['.implode(',',$answeights[$questions[$i]]).']);return false;">Full credit all parts</a>';
					if (count($togr)>0) {
						$togr = implode(',',$togr);
						echo ' | <a href="#" onclick="quickgrade('.$i.',1,\'scorebox\',['.$togr.'],['.implode(',',$answeights[$questions[$i]]).']);return false;">Full credit all manually-graded parts</a>';
					}
				} else if ($canedit) {
					echo 'Quick grade: <a class="quickgrade" href="#" onclick="quicksetscore(\'scorebox'.$i.'\','.$pts[$questions[$i]].');return false;">Full credit</a>';
				}
				$laarr = explode('##',$lastanswers[$i]);

				if ($attempts[$i]!=count($laarr)) {
					//echo " (clicked \"Jump to answer\")";
				}
				if (count($laarr)>1) {
					echo "<br/>Previous Attempts:";
					$cnt =1;
					for ($k=0;$k<count($laarr)-1;$k++) {
						if ($laarr[$k]=="ReGen") {
							echo ' ReGen ';
						} else {
							echo "  <b>$cnt:</b> " ;
							if (preg_match('/@FILE:(.+?)@/',$laarr[$k],$match)) {
								$url = getasidfileurl($match[1]);
								echo "<a href=\"$url\" target=\"_new\">".basename($match[1])."</a>";
							} else {
								//remove any $f$ wrong format markers
								$laarr[$k] = preg_replace('/\$f\$.*?(&|$)/','$1', $laarr[$k]);

								//remove any $#$ numeric value bits
								$laarr[$k] = preg_replace('/\$#\$.*?(&|$)/','$1', $laarr[$k]);

								//replace MC with visual of answer
								if (strpos($laarr[$k],'$!$')) {
									if (strpos($laarr[$k],'&')) { //is multipart q
										$laparr = explode('&',$laarr[$k]);
										foreach ($laparr as $lk=>$v) {
											if (strpos($v,'$!$')!==false) {
												$qn = ($i+1)*1000+$lk;
												$tmp = explode('$!$',$v);
												$laparr[$lk] = prepchoicedisp($choicesdata[$qn][0]=='matching'?$tmp[0]:$tmp[1], $choicesdata[$qn]);
											} else {
												$laparr[$lk] = Sanitize::encodeStringForDisplay(str_replace(array('%nbsp;','%%'),array('&nbsp;','&'),$laparr[$lk]));
											}
										}
										$laarr[$k] = implode('; ',$laparr);
									} else {
										$tmp = explode('$!$',$laarr[$k]);
										$laarr[$k] = prepchoicedisp($choicesdata[$i][0]=='matching'?$tmp[0]:$tmp[1], $choicesdata[$i]);
									}
								} else {
									$laarr[$k] = Sanitize::encodeStringForDisplay(str_replace(array('&','%nbsp;','%%'),array('; ','&nbsp;','&'),strip_tags($laarr[$k])));
								}

								echo $laarr[$k];
							}
							$cnt++;
						}
					}
				}
				if ($timesontask[$i]!='' && !isset($_GET['reviewver'])) {
					echo '<br/>Average time per submission: ';
					$timesarr = explode('~',$timesontask[$i]);
					$avgtime = array_sum($timesarr)/count($timesarr);
					if ($avgtime<60) {
						echo round($avgtime,1) . ' seconds ';
					} else {
						echo round($avgtime/60,1) . ' minutes ';
					}
					echo '<br/>';
				}
				if ($isteacher) {
					echo "<br/><a target=\"_blank\" href=\"$imasroot/msgs/msglist.php?cid=$cid&add=new&quoteq=$i-$qsetid-{$seeds[$i]}-{$line['assessmentid']}-{$line['ver']}&to={$get_uid}\">Use in Msg</a>";
					//having issues with greybox in assessments
					//echo '<br/>';
					//echo "<a href=\"#\" onclick=\"GB_show('Send Message','$imasroot/course/sendmsgmodal.php?sendtype=msg&cid=$cid&quoteq=$i-$qsetid-{$seeds[$i]}&to={$get_uid}',800,'auto')\" title=\"Send Message\">", _('Use in Message'), "</a>";


					echo " | <a href=\"gb-viewasid.php?stu=$stu&cid=$cid&from=$from&asid={$_GET['asid']}&uid={$get_uid}&clearq=$i\">Clear Score</a> ";
					echo "(Question ID: <a href=\"$imasroot/course/moddataset.php?id=$qsetid&cid=$cid&qid={$questions[$i]}&aid=$aid\">$qsetid</a>";
					if (isset($CFG['GEN']['sendquestionproblemsthroughcourse'])) {
						echo ". <a href=\"$imasroot/msgs/msglist.php?add=new&cid={$CFG['GEN']['sendquestionproblemsthroughcourse']}&to={$owners[$questions[$i]]}&title=Problem%20with%20question%20id%20$qsetid\" target=\"_blank\">Message owner</a> to report problems.";
					}
					echo ')';


					if (isset($extref[$questions[$i]])) {
						echo "&nbsp; Had help available: ";
						foreach ($extref[$questions[$i]] as $v) {
							$extrefpt = explode('!!',$v);
							echo '<a href="'.$extrefpt[1].'" target="_blank">'.$extrefpt[0].'</a> ';
						}
					}
				}
				echo '</div>';
			} else { //is student
				if (!empty($feedback["Q$i"])) {
					echo '<br/>'._('Feedback').': ';
					echo '<div class="fbbox">'.Sanitize::outgoingHtml($feedback["Q$i"]).'</div>';
				}
			}
			echo "</div>\n";

		}
		echo "<p></p><div class=review>Total: $total/$totalpossible</div>\n";
		if ($canedit && !isset($_GET['lastver']) && !isset($_GET['reviewver'])) {
			echo "<p>General feedback:<br/>";
			if ($sessiondata['useed']==0) {
				echo "<textarea cols=60 rows=4 id=\"feedback\" name=\"feedback\" class=\"fbbox\">";
				if (!empty($feedback["Z"])) {
					echo Sanitize::encodeStringForDisplay($feedback["Z"]);
				}
				echo "</textarea></p>";
			} else {
				echo "<div cols=60 rows=4 id=\"feedback\" class=\"fbbox\">";
				if (!empty($feedback["Z"])) {
					echo Sanitize::outgoingHtml($feedback["Z"]);
				}
				echo "</div></p>";
			}
			if ($line['agroupid']>0) {
				echo "<p>Update grade for all group members? <input type=checkbox name=\"updategroup\" checked=\"checked\" /></p>";
			}
			echo "<p><input type=submit value=\"Record Changed Grades\"> ";
			if (!isset($sessiondata['ltiitemtype']) || $sessiondata['ltiitemtype']!=0) {
				echo "<a href=\"$backurl\">Return to GradeBook without saving</a></p>\n";
			}
			/*
			if ($line['agroupid']>0) {
				$q2 = "SELECT i_u.LastName,i_u.FirstName FROM imas_assessment_sessions AS i_a_s,imas_users AS i_u WHERE ";
				$q2 .= "i_u.id=i_a_s.userid AND i_a_s.agroupid='{$line['agroupid']}'";
				$result = mysql_query($q2) or die("Query failed : " . mysql_error());
				echo "Group members: <ul>";
				while ($row = mysql_fetch_row($result)) {
					echo "<li>{$row[0]}, {$row[1]}</li>";
				}
				echo "</ul>";
			}
			*/

		} else if (trim($line['feedback'])!='') {
			echo "<p>"._('General Instructor Feedback').":<div class=\"fbbox\">";
			if (!empty($feedback["Z"])) {
				echo Sanitize::outgoingHtml($feedback["Z"]);
			}
			echo "</div></p>";
			if (!isset($sessiondata['ltiitemtype']) || $sessiondata['ltiitemtype']!=0) {
				echo "<p><a href=\"gradebook.php?stu=$stu&cid=$cid\">Return to GradeBook</a></p>\n";
			}
		}
		echo "</form>";
		echo '<p>&nbsp;</p>';


		//DB $query = "SELECT COUNT(id) from imas_questions WHERE assessmentid='{$line['assessmentid']}' AND category<>'0'";
		//DB $result = mysql_query($query) or die("Query failed : $query;  " . mysql_error());
		//DB if (mysql_result($result,0,0)>0) {
		$stm = $DBH->prepare("SELECT COUNT(id) from imas_questions WHERE assessmentid=:assessmentid AND category<>'0'");
		$stm->execute(array(':assessmentid'=>$line['assessmentid']));
		if ($stm->fetchColumn(0)>0) {
			include("../assessment/catscores.php");
			catscores($questions,$scores,$line['defpoints'], $line['defoutcome'],$cid);
		}
		require("../footer.php");

	} else if ($links==1) { //show grade detail question/category breakdown
		$placeinhead = "<script type=\"text/javascript\">function previewq(qn) {
			var addr = '$imasroot/course/testquestion.php?cid=$cid&qsetid='+qn;
			previewpop = window.open(addr,'Testing','width='+(.4*screen.width)+',height='+(.8*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(.6*screen.width-20));
			previewpop.focus();
		}</script>";
		require("../header.php");
		echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=". Sanitize::courseId($_GET['cid'])."\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
		echo "&gt; <a href=\"gradebook.php?stu=0&cid=$cid\">Gradebook</a> ";
		if ($stu>0) {echo "&gt; <a href=\"gradebook.php?stu=$stu&cid=$cid\">Student Detail</a> ";}
		echo "&gt; Detail</div>";

		$isdiag = false;
		if ($istutor || $isteacher) {
			//DB $query = "SELECT sel1name,sel2name FROM imas_diags WHERE cid='$cid'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB if (mysql_num_rows($result)>0) {
			$stm = $DBH->prepare("SELECT sel1name,sel2name FROM imas_diags WHERE cid=:cid");
			$stm->execute(array(':cid'=>$cid));
			if ($stm->rowCount()>0) {
				$isdiag = true;
				//DB list($sel1name,$sel2name) = mysql_fetch_row($result);
				list($sel1name,$sel2name) = $stm->fetch(PDO::FETCH_NUM);
			}
		}

		//DB $query = "SELECT FirstName,LastName,SID FROM imas_users WHERE id='{$get_uid}'";
		//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		//DB $row = mysql_fetch_row($result);
		$stm = $DBH->prepare("SELECT FirstName,LastName,SID FROM imas_users WHERE id=:id");
		$stm->execute(array(':id'=>$get_uid));
		$row = $stm->fetch(PDO::FETCH_NUM);

		if (($isteacher || $istutor) && isset($_GET['asid']) && $_GET['asid']!="new") {
			echo '<div class="cpmid">';
			echo '<a href="gb-viewasid.php?stu=$stu&asid='.$_GET['asid'].'&from='.$from.'&cid='.$cid.'&uid='.$get_uid.'&links=0">';
			echo _('Show Score Details');
			echo '</a>';
			echo '</div>';
		}
		if ($isdiag) {
			$selparts = explode('~',$row[2]);
			$ID = $selparts[0];
			$term = $selparts[1];
			echo "<h2>Score Report</h2>\n";
			echo "<h3>{$row[1]}, {$row[0]}<br/>($ID)</h3>\n";
		} else {
			echo "<h2>Grade Book Summary</h2>\n";
			echo "<h3>{$row[1]}, {$row[0]}</h3>\n";
		}

		//DB $query = "SELECT imas_assessments.name,imas_assessments.defpoints,imas_assessments.defoutcome,imas_assessments.endmsg,imas_assessment_sessions.* ";
		//DB $query .= "FROM imas_assessments,imas_assessment_sessions ";
		//DB $query .= "WHERE imas_assessments.id=imas_assessment_sessions.assessmentid AND imas_assessment_sessions.id='{$_GET['asid']}'";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB $line=mysql_fetch_array($result, MYSQL_ASSOC);
		$query = "SELECT imas_assessments.name,imas_assessments.defpoints,imas_assessments.defoutcome,imas_assessments.endmsg,imas_assessment_sessions.* ";
		$query .= "FROM imas_assessments,imas_assessment_sessions ";
		$query .= "WHERE imas_assessments.id=imas_assessment_sessions.assessmentid AND imas_assessment_sessions.id=:id";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':id'=>$_GET['asid']));
		$line=$stm->fetch(PDO::FETCH_ASSOC);
		$GLOBALS['assessver'] = $line['ver'];

		if (!$isteacher && !$istutor) {
			//DB $query = "INSERT INTO imas_content_track (userid,courseid,type,typeid,viewtime) VALUES ";
			//DB $query .= "($userid,'$cid','gbviewasid','{$line['assessmentid']}',$now)";
			//DB mysql_query($query) or die("Query failed : " . mysql_error());
			$query = "INSERT INTO imas_content_track (userid,courseid,type,typeid,viewtime) VALUES ";
			$query .= "(:userid, :courseid, 'gbviewasid', :typeid, :viewtime)";
			$stm = $DBH->prepare($query);
			$stm->execute(array(':userid'=>$userid, ':courseid'=>$cid, ':typeid'=>$line['assessmentid'], ':viewtime'=>$now));
		}

		$scores = array();
		$qs = explode(',',$line['questions']);
		$sp = explode(';',$line['bestscores']);
		foreach(explode(',',$sp[0]) as $k=>$score) {
			$scores[$qs[$k]] = getpts($score);
		}

		$placeholders = Sanitize::generateQueryPlaceholders($qs);
		$query = "SELECT imas_questionset.description,imas_questions.id,imas_questions.points,imas_questions.withdrawn,imas_questions.questionsetid FROM imas_questionset,imas_questions WHERE imas_questionset.id=imas_questions.questionsetid";
		$query .= " AND imas_questions.id IN ($placeholders)";
		$stm = $DBH->prepare($query);
		$stm->execute($qs);
		$i=1;
		$totpt = 0;
		$totposs = 0;
		$qbreakdown = '';
		$qdata = array();
		while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
			if ($row['points']==9999) {
				$row['points']= $line['defpoints'];
			}
			$totpt += $scores[$row['id']];
			$totposs += $row['points'];
			$qdata[$row['id']] = $row;
		}

		$pc = round(100*$totpt/$totposs,1);

		$endmsg = unserialize($line['endmsg']);
		$outmsg = '';
		if (isset($endmsg['msgs'])) {
			foreach ($endmsg['msgs'] as $sc=>$msg) { //array must be reverse sorted
				if (($endmsg['type']==0 && $totpt>=$sc) || ($endmsg['type']==1 && $pc>=$sc)) {
					$outmsg = $msg;
					break;
				}
			}
			if ($outmsg=='') {
				$outmsg = $endmsg['def'];
			}
			if (!isset($endmsg['commonmsg'])) {$endmsg['commonmsg']='';}

			if (strpos($outmsg,'redirectto:')!==false) {
				$outmsg = '';
			}
		}

		echo "<h4>{$line['name']}</h4>\n";
		if ($line['starttime']==0) {
			echo '<p>Started: Not yet started<br/>';
		} else {
			echo "<p>Started: " . tzdate("F j, Y, g:i a",$line['starttime']) ."<BR>\n";
		}
		if ($line['endtime']==0) {
			echo "Not Submitted</p>\n";
		} else {
			echo "Last change: " . tzdate("F j, Y, g:i a",$line['endtime']) . "</p>\n";
		}

		if ($outmsg!='') {
			echo "<p class=noticetext style=\"font-weight: bold;\">$outmsg</p>";
			if ($endmsg['commonmsg']!='' && $endmsg['commonmsg']!='<p></p>') {
				echo $endmsg['commonmsg'];
			}
		}

		//DB $query = "SELECT COUNT(id) from imas_questions WHERE assessmentid='{$line['assessmentid']}' AND category<>'0'";
		//DB $result = mysql_query($query) or die("Query failed : $query;  " . mysql_error());
		//DB if (mysql_result($result,0,0)>0) {
		$stm = $DBH->prepare("SELECT COUNT(id) from imas_questions WHERE assessmentid=:assessmentid AND category<>'0'");
		$stm->execute(array(':assessmentid'=>$line['assessmentid']));
		if ($stm->fetchColumn(0)>0) {
			include("../assessment/catscores.php");
			catscores(explode(',',$line['questions']),explode(',',$sp[0]),$line['defpoints'], $line['defoutcome'],$cid);
		}

		if (!($istutor && $isdiag)) {
			echo "<h4>Question Breakdown</h4>\n";
			echo "<table cellpadding=5 class=gb><thead><tr><th>Q#</th><th>Question</th><th>Points / Possible</th><th>Preview</th></tr></thead><tbody>\n";
			foreach ($qs as $i=>$qid) {
				if ($i%2!=0) {echo "<tr class=even>"; } else {echo "<tr class=odd>";}
				echo '<td>'.($i+1).'</td>';
				echo '<td>';
				if ($row['withdrawn']==1) {
					echo '<span class="noticetext">'._('Withdrawn') . '</span> ';
				}
				echo Sanitize::encodeStringForDisplay($qdata[$qid]['description']);
				echo "</td><td>";
				echo $scores[$qid] , ' / ' , $qdata[$qid]['points'];
				echo "</td>";
				echo "<td><input type=button value=\"Preview\" onClick=\"previewq(".Sanitize::onlyInt($qdata[$qid]['questionsetid']).")\"/></td>";
				echo "</tr>\n";
			}
			echo "</table>\n";
		}

		echo "<p>Total:  $totpt / $totposs  ($pc %)</p>\n";

		echo "<p><a href=\"gradebook.php?stu=$stu&cid=$cid\">Return to GradeBook</a></p>\n";
		require("../footer.php");

	}

function getpts($sc) {
	if (strpos($sc,'~')===false) {
		if ($sc>0) {
			return $sc;
		} else {
			return 0;
		}
	} else {
		$sc = explode('~',$sc);
		$tot = 0;
		foreach ($sc as $s) {
			if ($s>0) {
				$tot+=$s;
			}
		}
		return round($tot,1);
	}
}
function isperfect($sc) {
	if (strpos($sc,'~')===false) {
		if ($sc==1) {
			return true;
		}
	} else if (strpos($sc,'.')===false && strpos($sc,'0')===false) {
		return true;
	}
	return false;
}
function getasidquery($asid) {
	global $DBH;
	//DB $query = "SELECT agroupid,assessmentid FROM imas_assessment_sessions WHERE id='$asid'";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB $agroupid = mysql_result($result,0,0);
	//DB $aid= mysql_result($result,0,1);
	$stm = $DBH->prepare("SELECT agroupid,assessmentid FROM imas_assessment_sessions WHERE id=:id");
	$stm->execute(array(':id'=>$asid));
	list($agroupid,$aid) = $stm->fetch(PDO::FETCH_NUM);
	if ($agroupid>0) {
		return array('agroupid',$agroupid,$aid);
		//return (" WHERE agroupid='$agroupid'");
	} else {
		return array('id',$asid,$aid);
		//return (" WHERE id='$asid' LIMIT 1");
	}
}
function isasidgroup($asid) {
	global $DBH;
	//DB $query = "SELECT agroupid FROM imas_assessment_sessions WHERE id='$asid'";
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	//DB return (mysql_result($result,0,0)>0);
	$stm = $DBH->prepare("SELECT agroupid FROM imas_assessment_sessions WHERE id=:id");
	$stm->execute(array(':id'=>$asid));
	return ($stm->fetchColumn(0)>0);
}
function printscore($sc) {
	if (strpos($sc,'~')===false) {

		return array($sc,'');
	} else {
		$pts = getpts($sc);
		$sc = str_replace('-1','N/A',$sc);
		$sc = str_replace('~',', ',$sc);
		return array($pts,$sc);
	}
}
//evals a portion of the control section to extract the $answeights
//which might be randomizer determined, hence the seed
function getansweights($qi,$code) {
	global $seeds,$questions;
	if (preg_match('/scoremethod\s*=\s*"(singlescore|acct|allornothing)"/', $code)) {
		return array(1);
	}
	$i = array_search($qi,$questions);
	return sandboxgetweights($code,$seeds[$i]);
}

function sandboxgetweights($code,$seed) {
	srand($seed);
	$code = interpret('control','multipart',$code);
	if (($p=strrpos($code,'answeights'))!==false) {
		$np = strpos($code,"\n",$p);
		if ($np !== false) {
			$code = substr($code,0,$np).';if(isset($answeights)){return;};'.substr($code,$np);
		} else {
			$code .= ';if(isset($answeights)){return;};';
		}
		//$code = str_replace("\n",';if(isset($answeights)){return;};'."\n",$code);
	} else {
		$p=strrpos($code,'answeights');
		$np = strpos($code,"\n",$p);
		if ($np !== false) {
			$code = substr($code,0,$np).';if(isset($anstypes)){return;};'.substr($code,$np);
		} else {
			$code .= ';if(isset($answeights)){return;};';
		}
		//$code = str_replace("\n",';if(isset($anstypes)){return;};'."\n",$code);
	}

	eval($code);
	if (!isset($answeights)) {
		if (!is_array($anstypes)) {
			$anstypes = explode(",",$anstypes);
		}
		$n = count($anstypes);
		if ($n>1) {
			$answeights = array_fill(0,$n-1,round(1/$n,3));
			$answeights[] = 1-array_sum($answeights);
		} else {
			$answeights = array(1);
		}
	} else if (!is_array($answeights)) {
		$answeights =  explode(',',$answeights);
	}
	$sum = array_sum($answeights);
	if ($sum==0) {$sum = 1;}
	foreach ($answeights as $k=>$v) {
		$answeights[$k] = $v/$sum;
	}
	return $answeights;
}

function getconfirmheader($group=false) {
	global $DBH, $isteacher, $istutor, $userid;
	if ($group) {
		$out = '<h3>Whole Group</h3>';
	} else {
		//DB $query = "SELECT FirstName,LastName FROM imas_users WHERE id='{$get_uid}'";
		//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		//DB $row = mysql_fetch_row($result);
		$stm = $DBH->prepare("SELECT FirstName,LastName FROM imas_users WHERE id=:id");
		$stm->execute(array(':id'=>$get_uid));
		$row = $stm->fetch(PDO::FETCH_NUM);
		$out = "<h3>{$row[1]}, {$row[0]}</h3>\n";
	}
	//DB $query = "SELECT imas_assessments.name FROM imas_assessments,imas_assessment_sessions ";
	//DB $query .= "WHERE imas_assessments.id=imas_assessment_sessions.assessmentid AND imas_assessment_sessions.id='{$_GET['asid']}'";
	//DB if (!$isteacher && !$istutor) {
		//DB $query .= " AND imas_assessment_sessions.userid='$userid'";
	//DB }
	//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
	$query = "SELECT imas_assessments.name FROM imas_assessments,imas_assessment_sessions ";
	$query .= "WHERE imas_assessments.id=imas_assessment_sessions.assessmentid AND imas_assessment_sessions.id=:id";
	if (!$isteacher && !$istutor) {
		$query .= " AND imas_assessment_sessions.userid=:userid";
	}
	$stm = $DBH->prepare($query);
	if (!$isteacher && !$istutor) {
		$stm->execute(array(':id'=>$_GET['asid'], ':userid'=>$userid));
	} else {
		$stm->execute(array(':id'=>$_GET['asid']));
	}
	//DB $out .= "<h4>".mysql_result($result,0,0)."</h4>";
	$out .= "<h4>".$stm->fetchColumn(0)."</h4>";
	return $out;
}

function isoktorec() {
	global $DBH,$isteacher, $istutor;
	$oktorec = false;
	if ($isteacher) {
		$oktorec = true;
	} else if ($istutor) {
		//DB $query = "SELECT ia.tutoredit FROM imas_assessments AS ia JOIN imas_assessment_sessions AS ias ON ia.id=ias.assessmentid ";
		//DB $query .= "WHERE ias.id='{$_GET['asid']}'";
		//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		//DB if (mysql_result($result,0,0)==1) {
		$query = "SELECT ia.tutoredit FROM imas_assessments AS ia JOIN imas_assessment_sessions AS ias ON ia.id=ias.assessmentid ";
		$query .= "WHERE ias.id=:id";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':id'=>$_GET['asid']));
		if ($stm->fetchColumn(0)==1) {
			$oktorec = true;
		}
	}
	return $oktorec;
}
function scorestocolors($sc,$pts,$answ,$noraw) {
	if (!$noraw) {
		$pts = 1;
	}
	if (trim($sc)=='') {return '';}
	if (strpos($sc,'~')===false) {
		if ($pts==0) {
			$color = 'ansgrn';
		} else if ($sc<0) {
			$color = '';
		} else if ($sc==0) {
			$color = 'ansred';
		} else if ($pts-$sc<.011) {
			$color = 'ansgrn';
		} else {
			$color = 'ansyel';
		}
		return array($color);
	} else {
		$scarr = explode('~',$sc);
		if ($noraw) {
			for ($i=0; $i<count($answ)-1; $i++) {
				$answ[$i] = round($answ[$i]*$pts,2);
			}
			//adjust for rounding
			$diff = $pts - array_sum($answ);
			$answ[count($answ)-1] += $diff;
		} else {
			$answ = array_fill(0,count($scarr),1);
		}

		$out = array();
		foreach ($scarr as $k=>$v) {
			if ($answ[$k]==0) {
				$color = 'ansgrn';
			} else if ($v < 0) {
				$color = '';
			} else if ($v==0) {
				$color = 'ansred';
			} else if ($answ[$k]-$v < .011) {
				$color = 'ansgrn';
			} else {
				$color = 'ansyel';
			}
			$out[$k] = $color;
		}
		return $out;
	}
}

function prepchoicedisp($v,$choicesdata) {
	if ($v=='') {return '';}
	foreach ($choicesdata[1] as $k=>$c) {
		$sh = strip_tags($c);
		if (trim($sh)=='' || strpos($c,'<table')!==false) {
			$sh = "[view]";
		} else if (strlen($sh)>15) {
			$sh = substr($sh,0,15).'...';
		}
		if ($sh!=$c) {
			$choicesdata[1][$k] = '<span onmouseover="tipshow(this,\''.Sanitize::encodeStringForDisplay(trim(str_replace("\n",' ',$c))).'\')" onmouseout="tipout()">'.Sanitize::encodeStringForDisplay($sh).'</span>';
		}
	}
	if ($choicesdata[0]=='choices') {
		return ($choicesdata[1][$v]);
	} else if ($choicesdata[0]=='multans') {
		$p = explode('|',$v);
		$out = array();
		foreach ($p as $pv) {
			$out[] = $choicesdata[1][$pv];
		}
		return 'Selected: '.implode(', ',$out);
	} else if ($choicesdata[0]=='matching') {
		return $v;
	}

}
?>

<?php
//IMathAS:  View/Edit and Question breakdown views
//(c) 2007 David Lippman
	require("../validate.php");
	require_once("../includes/filehandler.php");

	$isteacher = isset($teacherid);
	$istutor = isset($tutorid);
	$cid = $_GET['cid'];
	$asid = intval($_GET['asid']);
	if (!isset($_GET['uid']) && !$isteacher && !$istutor) {
		$_GET['uid'] = $userid;
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
			$stu = $_GET['stu'];
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
	} else {
		$links = 0;
		$stu = 0;
		$from = 'gb';
		$now = time();
	}



	if ($_GET['asid']=="new" && $isteacher) {
		$aid = $_GET['aid'];
		//student could have started, so better check to make sure it still doesn't exist
		//DB $query = "SELECT id FROM imas_assessment_sessions WHERE userid='{$_GET['uid']}' AND assessmentid='$aid' ORDER BY id";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB if (mysql_num_rows($result)>0) {
			//DB $_GET['asid'] = mysql_result($result,0,0);
		$stm = $DBH->prepare("SELECT id FROM imas_assessment_sessions WHERE userid=:userid AND assessmentid=:assessmentid ORDER BY id");
		$stm->execute(array(':userid'=>$_GET['uid'], ':assessmentid'=>$aid));
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
			if ($adata['isgroup']>0) { //if is group assessment, and groups already exist, create asid for all in group
				//DB $query = 'SELECT i_sg.id FROM imas_stugroups as i_sg JOIN imas_stugroupmembers as i_sgm ON i_sg.id=i_sgm.stugroupid ';
				//DB $query .= "WHERE i_sgm.userid='{$_GET['uid']}' AND i_sg.groupsetid={$adata['groupsetid']}";
				//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
				//DB if (mysql_num_rows($result)>0) {
				$query = 'SELECT i_sg.id FROM imas_stugroups as i_sg JOIN imas_stugroupmembers as i_sgm ON i_sg.id=i_sgm.stugroupid ';
				$query .= "WHERE i_sgm.userid=:userid AND i_sg.groupsetid=:groupsetid";
				$stm = $DBH->prepare($query);
				$stm->execute(array(':userid'=>$_GET['uid'], ':groupsetid'=>$adata['groupsetid']));
				if ($stm->rowCount()>0) {
					//DB $agroupid = mysql_result($result,0,0);
					$agroupid = $stm->fetchColumn(0);
					//DB $query = "SELECT userid FROM imas_stugroupmembers WHERE stugroupid=$agroupid AND userid<>'{$_GET['uid']}'";
					//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
					//DB while ($row = mysql_fetch_row($result)) {
					$stm = $DBH->prepare("SELECT userid FROM imas_stugroupmembers WHERE stugroupid=:stugroupid AND userid<>:uid");
					$stm->execute(array(':stugroupid'=>$agroupid, ':uid'=>$_GET['uid']));
					while ($row = $stm->fetch(PDO::FETCH_NUM)) {
						$stugroupmem[] = $row[0];
					}
				}
			}
			$stugroupmem[] = $_GET['uid'];

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
		header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') ."/gb-viewasid.php?stu=$stu&asid={$_GET['asid']}&from=$from&cid=$cid&uid={$_GET['uid']}");

	}
	//PROCESS ANY TODOS
	if (isset($_GET['clearattempt']) && isset($_GET['asid']) && $isteacher) {
		if ($_GET['clearattempt']=="confirmed") {
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
				header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/isolateassessgrade.php?stu=$stu&cid={$_GET['cid']}&aid=$aid&gbmode=$gbmode");
			} else if ($from=='gisolate') {
				header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/isolateassessbygroup.php?stu=$stu&cid={$_GET['cid']}&aid=$aid&gbmode=$gbmode");
			} else if ($from=='stugrp') {
				header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/managestugrps.php?cid={$_GET['cid']}&aid=$aid");
			} else if ($from=='gbtesting') {
				header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gb-testing.php?stu=$stu&cid={$_GET['cid']}&gbmode=$gbmode");
			} else {
				header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gradebook.php?stu=$stu&cid={$_GET['cid']}&gbmode=$gbmode");
			}
			exit;
		} else {
			$isgroup = isasidgroup($_GET['asid']);
			if ($isgroup) {
				$pers = 'group';
				echo getconfirmheader(true);
			} else {
				$pers = 'student';
				echo getconfirmheader();
			}
			echo "<p>Are you sure you want to clear this $pers's assessment attempt?  This will make it appear the $pers never tried the assessment, and the $pers will receive a new version of the assessment.</p>";
			echo "<p><input type=button onclick=\"window.location='gb-viewasid.php?stu=$stu&gbmode=$gbmode&cid=$cid&asid={$_GET['asid']}&from=$from&clearattempt=confirmed'\" value=\"Really Clear\">\n";
			echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='gb-viewasid.php?stu=$stu&gbmode=$gbmode&cid=$cid&from=$from&asid={$_GET['asid']}&uid={$_GET['uid']}'\"></p>\n";
			exit;
		}
	}
	if (isset($_GET['breakfromgroup']) && isset($_GET['asid']) && $isteacher) {
		if ($_GET['breakfromgroup']=="confirmed") {
			include("../includes/stugroups.php");
			//DB $query = "SELECT userid,agroupid FROM imas_assessment_sessions WHERE id='{$_GET['asid']}'";
			//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
			//DB $row = mysql_fetch_row($result);
			$stm = $DBH->prepare("SELECT userid,agroupid FROM imas_assessment_sessions WHERE id=:id");
			$stm->execute(array(':id'=>$_GET['asid']));
			$row = $stm->fetch(PDO::FETCH_NUM);
			removegroupmember($row[1],$row[0]);
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') ."/gb-viewasid.php?stu=$stu&asid={$_GET['asid']}&from=$from&cid=$cid&uid={$_GET['uid']}");
		} else {
			echo getconfirmheader();
			echo "<p>Are you sure you want to separate this student from their current group?</p>";
			echo "<p><input type=button onclick=\"window.location='gb-viewasid.php?stu=$stu&gbmode=$gbmode&cid=$cid&from=$from&asid={$_GET['asid']}&uid={$_GET['uid']}&breakfromgroup=confirmed'\" value=\"Really Separate\">\n";
			echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='gb-viewasid.php?stu=$stu&from=$from&gbmode=$gbmode&cid=$cid&asid={$_GET['asid']}&uid={$_GET['uid']}'\"></p>\n";
			exit;
		}
	}
	if (isset($_GET['clearscores']) && isset($_GET['asid']) && $isteacher) {
		if ($_GET['clearscores']=="confirmed") {

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
			header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') ."/gb-viewasid.php?stu=$stu&asid={$_GET['asid']}&from=$from&cid=$cid&uid={$_GET['uid']}");
		} else {
			$isgroup = isasidgroup($_GET['asid']);
			if ($isgroup) {
				$pers = 'group';
				echo getconfirmheader(true);
			} else {
				$pers = 'student';
				echo getconfirmheader();
			}
			echo "<p>Are you sure you want to clear this $pers's scores for this assessment?</p>";
			echo "<p><input type=button onclick=\"window.location='gb-viewasid.php?stu=$stu&gbmode=$gbmode&from=$from&cid=$cid&asid={$_GET['asid']}&uid={$_GET['uid']}&clearscores=confirmed'\" value=\"Really Clear\">\n";
			echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='gb-viewasid.php?stu=$stu&from=$from&gbmode=$gbmode&cid=$cid&asid={$_GET['asid']}&uid={$_GET['uid']}'\"></p>\n";
			exit;
		}
	}
	if (isset($_GET['clearq']) && isset($_GET['asid']) && $isteacher) {
		if ($_GET['confirmed']=="true") {
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

			$clearid = $_GET['clearq'];
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
				if (isset($_GET['regen']) && $_GET['regen']==1) {
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

				header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') ."/gb-viewasid.php?stu=$stu&asid={$_GET['asid']}&from=$from&cid=$cid&uid={$_GET['uid']}");
			} else {
				echo "$clearid";
				print_r($scores);
				echo "<p>Error.  Try again.</p>";
			}
			//unset($_GET['asid']);
			unset($_GET['clearq']);

		} else {
			$isgroup = isasidgroup($_GET['asid']);
			if ($isgroup) {
				$pers = 'group';
				echo getconfirmheader(true);
			} else {
				$pers = 'student';
				echo getconfirmheader();
			}
			echo "<p>Are you sure you want to clear this $pers's scores for this question?</p>";
			echo "<p><input type=button onclick=\"window.location='gb-viewasid.php?stu=$stu&gbmode=$gbmode&cid=$cid&from=$from&asid={$_GET['asid']}&clearq={$_GET['clearq']}&uid={$_GET['uid']}&confirmed=true'\" value=\"Really Clear\"> \n";
			echo "<input type=button onclick=\"window.location='gb-viewasid.php?stu=$stu&gbmode=$gbmode&cid=$cid&from=$from&asid={$_GET['asid']}&clearq={$_GET['clearq']}&uid={$_GET['uid']}&regen=1&confirmed=true'\" value=\"Really Clear and Regen\"> \n";
			echo "<input type=button value=\"Nevermind\" class=\"secondarybtn\" onclick=\"window.location='gb-viewasid.php?stu=$stu&from=$from&gbmode=$gbmode&cid=$cid&asid={$_GET['asid']}&uid={$_GET['uid']}'\"></p>\n";
			exit;
		}
	}
	if (isset($_GET['forcegraphimg'])) {
		$sessiondata['graphdisp'] = 2;
	}

	//OUTPUTS
	if ($links==0) { //View/Edit full assessment
		require("../assessment/displayq2.php");
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
				$i = 0;
				while (isset($_POST[$i]) || isset($_POST["$i-0"])) {
					$j=0;
					$scpt = array();
					if (isset($_POST["$i-0"])) {

						while (isset($_POST["$i-$j"])) {
							if ($_POST["$i-$j"]!='N/A' && $_POST["$i-$j"]!='NA') {
								$scpt[$j] = $_POST["$i-$j"];
							} else {
								$scpt[$j] = -1;
							}
							$j++;
						}
						$scores[$i] = implode('~',$scpt);
					} else {
						if ($_POST[$i]!='N/A' && $_POST["$i-$j"]!='NA') {
							$scores[$i] = $_POST[$i];
						} else {
							$scores[$i] = -1;
						}
					}
					$i++;
				}
				$scorelist = implode(",",$scores);
				if (count($bsp)>1) { //tack on rawscores and firstscores
					$scorelist .= ';'.$bsp[1].';'.$bsp[2];
				}
				$feedback = $_POST['feedback'];

				if (isset($_POST['updategroup'])) {
					$qp = getasidquery($_GET['asid']);
					//DB $query = "UPDATE imas_assessment_sessions SET bestscores='$scorelist',feedback='$feedback'";
					//DB $query .=  " WHERE {$qp[0]}='{$qp[1]}' AND assessmentid='{$qp[2]}'";
					$query = "UPDATE imas_assessment_sessions SET bestscores=:bestscores,feedback=:feedback";
					$query .=  " WHERE {$qp[0]}=:qval AND assessmentid=:assessmentid";
					$stm = $DBH->prepare($query);
					$stm->execute(array(':bestscores'=>$scorelist, ':feedback'=>$feedback, ':assessmentid'=>$qp[2], ':qval'=>$qp[1]));
					//$query .= getasidquery($_GET['asid']);
				} else {
					//DB $query = "UPDATE imas_assessment_sessions SET bestscores='$scorelist',feedback='$feedback'";
					//DB $query .= "WHERE id='{$_GET['asid']}'";
					$query = "UPDATE imas_assessment_sessions SET bestscores=:bestscores,feedback=:feedback WHERE id=:id";
					$stm = $DBH->prepare($query);
					$stm->execute(array(':bestscores'=>$scorelist, ':feedback'=>$feedback, ':id'=>$_GET['asid']));
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
				header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/isolateassessgrade.php?stu=$stu&cid={$_GET['cid']}&aid=$aid&gbmode=$gbmode");
			} else if ($from=='gisolate') {
				header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/isolateassessbygroup.php?stu=$stu&cid={$_GET['cid']}&aid=$aid&gbmode=$gbmode");
			} else if ($from=='stugrp') {
				header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/managestugrps.php?cid={$_GET['cid']}&aid=$aid");
			} else if ($from=='gbtesting') {
				header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gb-testing.php?stu=$stu&cid={$_GET['cid']}&gbmode=$gbmode");
			} else {
				header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/gradebook.php?stu=$stu&cid={$_GET['cid']}&gbmode=$gbmode");
			}
			exit;
		}
		$useeditor='review';
		$sessiondata['coursetheme'] = $coursetheme;
		$sessiondata['isteacher'] = $isteacher;
		if ($isteacher || $istutor) {
			$placeinhead = '<script type="text/javascript" src="'.$imasroot.'/javascript/rubric.js?v=113016"></script>';
			require("../includes/rubric.php");
		}
		require("../assessment/header.php");
		echo "<style type=\"text/css\">p.tips {	display: none;}\n</style>\n";


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
			echo "<a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> &gt; ";

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
		echo "Detail</div>";
		echo '<div id="headergb-viewasid" class="pagetitle"><h2>Grade Book Detail</h2></div>';
		//DB $query = "SELECT imas_users.FirstName,imas_users.LastName,imas_students.timelimitmult FROM imas_users JOIN imas_students ON imas_users.id=imas_students.userid WHERE imas_users.id='{$_GET['uid']}' AND imas_students.courseid='$cid'";
		//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		//DB $row = mysql_fetch_row($result);
		$stm = $DBH->prepare("SELECT imas_users.FirstName,imas_users.LastName,imas_students.timelimitmult FROM imas_users JOIN imas_students ON imas_users.id=imas_students.userid WHERE imas_users.id=:id AND imas_students.courseid=:courseid");
		$stm->execute(array(':id'=>$_GET['uid'], ':courseid'=>$cid));
		$row = $stm->fetch(PDO::FETCH_NUM);
		echo "<h3>{$row[1]}, {$row[0]}</h3>\n";


		//do time limit mult
		$timelimitmult = $row[2];
		$line['timelimit'] *= $timelimitmult;


		$teacherreview = $_GET['uid'];

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
		//DB $query = "SELECT enddate FROM imas_exceptions WHERE userid='{$_GET['uid']}' AND assessmentid='{$line['assessmentid']}' AND itemtype='A'";
		//DB $r2 = mysql_query($query) or die("Query failed : " . mysql_error());
		//DB if (mysql_num_rows($r2)>0) {
			//DB $exped = mysql_result($r2,0,0);
		$stm2 = $DBH->prepare("SELECT startdate,enddate,islatepass FROM imas_exceptions WHERE userid=:userid AND assessmentid=:assessmentid AND itemtype='A'");
		$stm2->execute(array(':userid'=>$_GET['uid'], ':assessmentid'=>$line['assessmentid']));
		$useexception = false;
		if ($stm2->rowCount()>0) {
			$exception = $stm2->fetch(PDO::FETCH_NUM);
			$exped = $exception[1];
			if ($exped>$saenddate) {
				$saenddate = $exped;
			}
			require("../includes/exceptionfuncs.php");
			$useexception = getCanUseAssessException($exception, $line, true); 
		}

		if ($isteacher) {
			if (isset($exped) && $exped!=$line['enddate']) {
				$lpnote = ($exception[2]>0)?" (LatePass)":"";
				if ($useexception) {
					echo "<p>Has exception$lpnote, with due date: ".tzdate("F j, Y, g:i a",$exped);
					echo "  <button type=\"button\" onclick=\"window.location.href='exception.php?cid=$cid&aid={$line['assessmentid']}&uid={$_GET['uid']}&asid={$_GET['asid']}&from=$from&stu=$stu'\">Edit Exception</button>";
					echo "<br/>Original Due Date: ". ($line['enddate']==2000000000?"None":tzdate("F j, Y, g:i a",$line['enddate']));
				} else {
					echo "<p>Had exception$lpnote, with due date: ".tzdate("F j, Y, g:i a",$exped);
					echo "  <button type=\"button\" onclick=\"window.location.href='exception.php?cid=$cid&aid={$line['assessmentid']}&uid={$_GET['uid']}&asid={$_GET['asid']}&from=$from&stu=$stu'\">Edit Exception</button>";
					echo "<br/>Assessment Due Date is being used instead: ". ($line['enddate']==2000000000?"None":tzdate("F j, Y, g:i a",$line['enddate']));

				}
			} else {
				echo "<p>Due Date: ". ($line['enddate']==2000000000?"None":tzdate("F j, Y, g:i a",$line['enddate']));
				echo "  <button type=\"button\" onclick=\"window.location.href='exception.php?cid=$cid&aid={$line['assessmentid']}&uid={$_GET['uid']}&asid={$_GET['asid']}&from=$from&stu=$stu'\">Make Exception</button>";
			}
			echo "</p>";
		}
		if ($isteacher) {
			if ($line['agroupid']>0) {
				echo "<p>This assignment is linked to a group.  Changes will affect the group unless specified. ";
				echo "<a href=\"gb-viewasid.php?stu=$stu&cid=$cid&asid={$_GET['asid']}&from=$from&uid={$_GET['uid']}&breakfromgroup=true\">Separate from Group</a></p>";
			}
		}
		echo "<form id=\"mainform\" method=post action=\"gb-viewasid.php?stu=$stu&cid=$cid&from=$from&asid={$_GET['asid']}&update=true\">\n";

		if ($isteacher) {
			echo "<div class=\"cpmid\"><a href=\"gb-viewasid.php?stu=$stu&cid=$cid&asid={$_GET['asid']}&from=$from&uid={$_GET['uid']}&clearattempt=true\" onmouseover=\"tipshow(this,'Clear everything, resetting things like the student never started.  Student will get new versions of questions.')\" onmouseout=\"tipout()\">Clear Attempt</a> | ";
			echo "<a href=\"gb-viewasid.php?stu=$stu&cid=$cid&asid={$_GET['asid']}&from=$from&uid={$_GET['uid']}&clearscores=true\" onmouseover=\"tipshow(this,'Clear scores and attempts, but keep same versions of questions')\" onmouseout=\"tipout()\">Clear Scores</a> | ";
			echo "<a href=\"#\" onclick=\"markallfullscore();$('#uppersubmit').show();return false;\" onmouseover=\"tipshow(this,'Change all scores to full credit')\" onmouseout=\"tipout()\">All Full Credit</a> ";
			echo '<input style="display:none;" id="uppersubmit" type="submit" value="Record Changed Grades"> | ';
			echo "<a href=\"$imasroot/assessment/showtest.php?cid=$cid&id={$line['assessmentid']}&actas={$_GET['uid']}\" onmouseover=\"tipshow(this,'Take on role of this student, bypassing date restrictions, to submit answers')\" onmouseout=\"tipout()\">View as student</a> | ";
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
				echo "<a href=\"gb-viewasid.php?stu=$stu&starttime=$reset&asid={$_GET['asid']}&from=$from&cid=$cid&uid={$_GET['uid']}\">Clear overtime and accept grade</a></p>\n";
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
			echo "<a href=\"gb-viewasid.php?stu=$stu&asid={$_GET['asid']}&from=$from&cid=$cid&uid={$_GET['uid']}\">Show Scored Attempts</a> | ";
			echo "<b>Showing Last Attempts</b> | ";
			echo "<a href=\"gb-viewasid.php?stu=$stu&asid={$_GET['asid']}&from=$from&cid=$cid&uid={$_GET['uid']}&reviewver=1\">Show Review Attempts</a>";
			echo "</p>";
		} else if (isset($_GET['reviewver'])) {
			$seeds = explode(",",$line['reviewseeds']);
			$sp = explode(";",$line['reviewscores']);
			$scores = explode(",",$sp[0]);
			if (isset($sp[1])) {$rawscores = explode(",",$sp[1]);}
			$attempts = explode(",",$line['reviewattempts']);
			$lastanswers = explode("~",$line['reviewlastanswers']);
			echo "<p>";
			echo "<a href=\"gb-viewasid.php?stu=$stu&asid={$_GET['asid']}&from=$from&cid=$cid&uid={$_GET['uid']}\">Show Scored Attempts</a> | ";
			echo "<a href=\"gb-viewasid.php?stu=$stu&asid={$_GET['asid']}&from=$from&cid=$cid&uid={$_GET['uid']}&lastver=1\">Show Last Graded Attempts</a> | ";
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
			echo "<a href=\"gb-viewasid.php?stu=$stu&asid={$_GET['asid']}&cid=$cid&from=$from&uid={$_GET['uid']}&lastver=1\">Show Last Attempts</a> | ";
			echo "<a href=\"gb-viewasid.php?stu=$stu&asid={$_GET['asid']}&cid=$cid&from=$from&uid={$_GET['uid']}&reviewver=1\">Show Review Attempts</a>";
			echo "</p>";
		}

		//DB $query = "SELECT iq.id,iq.points,iq.withdrawn,iqs.qtype,iqs.control,iq.rubric,iq.showhints,iqs.extref,iqs.ownerid ";
		//DB $query .= "FROM imas_questions AS iq, imas_questionset AS iqs ";
		//DB $query .= "WHERE iq.questionsetid=iqs.id AND iq.assessmentid='{$line['assessmentid']}'";
		//DB $result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
		$query = "SELECT iq.id,iq.points,iq.withdrawn,iqs.qtype,iqs.control,iq.rubric,iq.showhints,iqs.extref,iqs.ownerid ";
		$query .= "FROM imas_questions AS iq, imas_questionset AS iqs ";
		$query .= "WHERE iq.questionsetid=iqs.id AND iq.assessmentid=:assessmentid";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':assessmentid'=>$line['assessmentid']));
		$totalpossible = 0;
		$pts = array();
		$withdrawn = array();
		$rubric = array();
		$extref = array();
		$owners = array();
		//DB while ($r = mysql_fetch_row($result)) {
		while ($r = $stm->fetch(PDO::FETCH_NUM)) {
			if ($r[1]==9999) {
				$pts[$r[0]] = $line['defpoints'];  //use defpoints
			} else {
				$pts[$r[0]] = $r[1]; //use points from question
			}
			//$totalpossible += $pts[$r[0]];  do later
			$withdrawn[$r[0]] = $r[2];
			$rubric[$r[0]] = $r[5];
			if ($r[3]=='multipart') {
				//if (preg_match('/answeights\s*=\s*("|\')([\d\.\,\s]+)/',$line['control'],$match)) {
				/*if (($p = strpos($r[4],'answeights'))!==false) {
					$p = strpos($r[4],"\n",$p);
					$answeights[$r[0]] = getansweights($r[0],$r[4]);
				} else {
					preg_match('/anstypes(.*)/',$r[4],$match);
					$n = substr_count($match[1],',')+1;
					if ($n>1) {
						$answeights[$r[0]] = array_fill(0,$n-1,round(1/$n,3));
						$answeights[$r[0]][] = 1-array_sum($answeights[$r[0]]);
					} else {
						$answeights[$r[0]] = array(1);
					}
				}
				*/
				$answeights[$r[0]] = getansweights($r[0],$r[4]);
				for ($i=0; $i<count($answeights[$r[0]])-1; $i++) {
					$answeights[$r[0]][$i] = round($answeights[$r[0]][$i]*$pts[$r[0]],2);
				}
				//adjust for rounding
				$diff = $pts[$r[0]] - array_sum($answeights[$r[0]]);
				$answeights[$r[0]][count($answeights[$r[0]])-1] += $diff;

			}
			if (($line['showhints']==1 && $r[6]!=1) || $r[6]==2) {
				if ($r[7]!='') {
					$extref[$r[0]] = explode('~~',$r[7]);
				}
			}
			$owners[$r[0]] = $r[8];
		}
		echo '<script type="text/javascript">
			function hidecorrect() {
				var butn = $("#hctoggle");
				if (!butn.hasClass("hchidden")) {
					butn.html("'._('Show Correct Questions').'");
					butn.addClass("hchidden");
					$(".iscorrect").hide();
				} else {
					butn.html("'._('Hide Correct Questions').'");
					butn.removeClass("hchidden");
					$(".iscorrect").show();
				}
			}
			function hideperfect() {
				var butn = $("#hptoggle");
				if (!butn.hasClass("hphidden")) {
					butn.html("'._('Show Perfect Questions').'");
					butn.addClass("hphidden");
					$(".isperfect").hide();
				} else {
					butn.html("'._('Hide Perfect Questions').'");
					butn.removeClass("hphidden");
					$(".isperfect").show();
				}
			}
			function hideNA() {
				var butn = $("#hnatoggle");
				if (!butn.hasClass("hnahidden")) {
					butn.html("'._('Show Unanswered Questions').'");
					butn.addClass("hnahidden");
				} else {
					butn.html("'._('Hide Unanswered Questions').'");
					butn.removeClass("hnahidden");
				}
				$(".notanswered").toggle();
			}
			function showallans() {
				$("span[id^=\'ans\']").removeClass("hidden");
				$(".sabtn").replaceWith("<span>Answer: </span>");
			}
			function previewall() {
				$(\'input[value="Preview"]\').trigger(\'click\').remove();
			}
			var focuscolorlock = false;
			$(function() {
				$(".review input[name*=\'-\']").each(function(i, el) {
					var partname = $(el).attr("name");
					var idparts = partname.split("-");
					var qn = (idparts[0]*1+1)*1000+idparts[1]*1;
					$(el).on("mouseover", function () {
						if (!focuscolorlock) {$("#qn"+qn+", #tc"+qn+", #qnwrap"+qn+", #showansbtn"+partname+", #scorebox"+partname+", #ptpos"+partname).css("background-color","yellow")};
					}).on("mouseout", function () {
						if (!focuscolorlock) {$("#qn"+qn+", #tc"+qn+", #qnwrap"+qn+", #showansbtn"+partname+", #scorebox"+partname+", #ptpos"+partname).css("background-color","")};
					}).on("focus", function () {
						focuscolorlock = true;
						$("#qn"+qn+", #tc"+qn+", #qnwrap"+qn+", #showansbtn"+partname+", #scorebox"+partname+", #ptpos"+partname).css("background-color","yellow");
					}).on("blur", function () {
						focuscolorlock = false;
						$("#qn"+qn+", #tc"+qn+", #qnwrap"+qn+", #showansbtn"+partname+", #scorebox"+partname+", #ptpos"+partname).css("background-color","");
					});
				});
				$("input[id^=\'showansbtn\']").each(function(i, el) {
					var partname = $(el).attr("id").substring(10);
					var idparts = partname.split("-");
					var qn = (idparts[0]*1+1)*1000+idparts[1]*1;
					$(el).on("mouseover", function () {
						if (!focuscolorlock) {$("#qn"+qn+", #tc"+qn+", #qnwrap"+qn+", #showansbtn"+partname+", #scorebox"+partname+", #ptpos"+partname).css("background-color","yellow")};
					}).on("mouseout", function () {
						if (!focuscolorlock) {$("#qn"+qn+", #tc"+qn+", #qnwrap"+qn+", #showansbtn"+partname+", #scorebox"+partname+", #ptpos"+partname).css("background-color","")};
					});
				});
				$("input[id^=\'qn\'], input[id^=\'tc\'], select[id^=\'qn\'], div[id^=\'qnwrap\'], span[id^=\'qnwrap\']").each(function(i,el) {
					var qn = $(el).attr("id");
					if (qn.length>6 && qn.substring(0,6)=="qnwrap") {
						qn = qn.substring(6)*1;
					} else {
						qn = qn.substring(2)*1;
					}
					if (qn>999) {
						var partname = (Math.floor(qn/1000)-1)+"-"+(qn%1000);
						$(el).on("mouseover", function () {
							if (!focuscolorlock) {$("#qn"+qn+", #tc"+qn+", #qnwrap"+qn+", #showansbtn"+partname+", #scorebox"+partname+", #ptpos"+partname).css("background-color","yellow")};
						}).on("mouseout", function () {
							if (!focuscolorlock) {$("#qn"+qn+", #tc"+qn+", #qnwrap"+qn+", #showansbtn"+partname+", #scorebox"+partname+", #ptpos"+partname).css("background-color","")};
						}).on("focus", function () {
							focuscolorlock = true;
							$("#qn"+qn+", #tc"+qn+", #qnwrap"+qn+", #showansbtn"+partname+", #scorebox"+partname+", #ptpos"+partname).css("background-color","yellow");
						}).on("blur", function () {
							focuscolorlock = false;
							$("#qn"+qn+", #tc"+qn+", #qnwrap"+qn+", #showansbtn"+partname+", #scorebox"+partname+", #ptpos"+partname).css("background-color","");
						});
					}
				});
			});
			</script>';

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
			list($qsetid,$cat) = getqsetid($questions[$i]);
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
			$qtypes = displayq($i,$qsetid,$seeds[$i],$showa,false,$attempts[$i],false,false,false,$colors);
			echo '</div>';

			if ($scores[$i]==-1) { $scores[$i]="N/A";} else {$total+=getpts($scores[$i]);}
			echo "<div class=review>Question ".($i+1).": ";
			if ($withdrawn[$questions[$i]]==1) {
				echo "<span class=\"red\">Question Withdrawn</span> ";
			}
			list($pt,$parts) = printscore($scores[$i]);
			if ($canedit && $parts=='') {
				echo "<input type=text size=4 id=\"scorebox$i\" name=\"$i\" value=\"$pt\">";
				if ($rubric[$questions[$i]]!=0) {
					echo printrubriclink($rubric[$questions[$i]],$pts[$questions[$i]],"scorebox$i","feedback",($i+1));
				}
			} else {
				echo $pt;
			}

			if ($parts!='') {
				if ($canedit) {
					echo " (parts: ";
					$prts = explode(', ',$parts);
					for ($j=0;$j<count($prts);$j++) {
						echo "<input type=text size=2 id=\"scorebox$i-$j\" name=\"$i-$j\" value=\"{$prts[$j]}\">";
						if ($rubric[$questions[$i]]!=0) {
							echo printrubriclink($rubric[$questions[$i]],$answeights[$questions[$i]][$j],"scorebox$i-$j","feedback",($i+1).' pt '.($j+1));
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
				echo ')';
			}
			echo "in {$attempts[$i]} attempt(s)\n";
			if ($isteacher || $istutor) {
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
								if (strpos($laarr[$k],'$f$')) {
									if (strpos($laarr[$k],'&')) { //is multipart q
										$laparr = explode('&',$laarr[$k]);
										foreach ($laparr as $lk=>$v) {
											if (strpos($v,'$f$')) {
												$tmp = explode('$f$',$v);
												$laparr[$lk] = $tmp[0];
											}
										}
										$laarr[$k] = implode('&',$laparr);
									} else {
										$tmp = explode('$f$',$laarr[$k]);
										$laarr[$k] = $tmp[0];
									}
								}
								if (strpos($laarr[$k],'$!$')) {
									if (strpos($laarr[$k],'&')) { //is multipart q
										$laparr = explode('&',$laarr[$k]);
										foreach ($laparr as $lk=>$v) {
											if (strpos($v,'$!$')) {
												$qn = ($i+1)*1000+$lk;
												$tmp = explode('$!$',$v);
												//$laparr[$lk] = $tmp[0];
												$laparr[$lk] = prepchoicedisp($choicesdata[$qn][0]=='matching'?$tmp[0]:$tmp[1], $choicesdata[$qn]);
											}
										}
										$laarr[$k] = implode('&',$laparr);
									} else {
										$tmp = explode('$!$',$laarr[$k]);
										//$laarr[$k] = $tmp[0];
										$laarr[$k] = prepchoicedisp($choicesdata[$i][0]=='matching'?$tmp[0]:$tmp[1], $choicesdata[$i]);
									}
								} else {
									$laarr[$k] = strip_tags($laarr[$k]);
								}


								if (strpos($laarr[$k],'$#$')) {
									if (strpos($laarr[$k],'&')) { //is multipart q
										$laparr = explode('&',$laarr[$k]);
										foreach ($laparr as $lk=>$v) {
											if (strpos($v,'$#$')) {
												$tmp = explode('$#$',$v);
												$laparr[$lk] = $tmp[0];
											}
										}
										$laarr[$k] = implode('&',$laparr);
									} else {
										$tmp = explode('$#$',$laarr[$k]);
										$laarr[$k] = $tmp[0];
									}
								}

								echo str_replace(array('&','%nbsp;','%%'),array('; ','&nbsp;','&'), $laarr[$k]);
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
					echo "<br/><a target=\"_blank\" href=\"$imasroot/msgs/msglist.php?cid=$cid&add=new&quoteq=$i-$qsetid-{$seeds[$i]}-{$line['assessmentid']}-{$line['ver']}&to={$_GET['uid']}\">Use in Msg</a>";
					//having issues with greybox in assessments
					//echo '<br/>';
					//echo "<a href=\"#\" onclick=\"GB_show('Send Message','$imasroot/course/sendmsgmodal.php?sendtype=msg&cid=$cid&quoteq=$i-$qsetid-{$seeds[$i]}&to={$_GET['uid']}',800,'auto')\" title=\"Send Message\">", _('Use in Message'), "</a>";


					echo " | <a href=\"gb-viewasid.php?stu=$stu&cid=$cid&from=$from&asid={$_GET['asid']}&uid={$_GET['uid']}&clearq=$i\">Clear Score</a> ";
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
			}
			echo "</div>\n";

		}
		echo "<p></p><div class=review>Total: $total/$totalpossible</div>\n";
		if ($canedit && !isset($_GET['lastver']) && !isset($_GET['reviewver'])) {
			echo "<p>Feedback to student:<br/><textarea cols=60 rows=4 id=\"feedback\" name=\"feedback\">{$line['feedback']}</textarea></p>";
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
			echo "<p>Instructor Feedback:<div class=\"intro\">{$line['feedback']}</div></p>";
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
		require("../header.php");
		echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid={$_GET['cid']}\">$coursename</a> ";
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

		//DB $query = "SELECT FirstName,LastName,SID FROM imas_users WHERE id='{$_GET['uid']}'";
		//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		//DB $row = mysql_fetch_row($result);
		$stm = $DBH->prepare("SELECT FirstName,LastName,SID FROM imas_users WHERE id=:id");
		$stm->execute(array(':id'=>$_GET['uid']));
		$row = $stm->fetch(PDO::FETCH_NUM);

		if ($isdiag) {
			$selparts = explode('~',$row[2]);
			$ID = $selparts[0];
			$term = $selparts[1];
			echo "<h2>Score Report</h2>\n";
			echo "<h3>{$row[1]}, {$row[0]}<br/>($ID)</h3>\n";
		} else {
			echo "<h2>Grade Book Detail</h2>\n";
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
		//DB $query = "SELECT imas_questionset.description,imas_questions.id,imas_questions.points,imas_questions.withdrawn FROM imas_questionset,imas_questions WHERE imas_questionset.id=imas_questions.questionsetid";
		//DB $query .= " AND imas_questions.id IN ({$line['questions']})";
		//DB $result = mysql_query($query) or die("Query failed : " . mysql_error());
		$query = "SELECT imas_questionset.description,imas_questions.id,imas_questions.points,imas_questions.withdrawn FROM imas_questionset,imas_questions WHERE imas_questionset.id=imas_questions.questionsetid";
		$query .= " AND imas_questions.id IN (".implode(',', array_map('intval', $qs)).")";
		$stm = $DBH->query($query);
		$i=1;
		$totpt = 0;
		$totposs = 0;
		$qbreakdown = '';
		//DB while ($row = mysql_fetch_row($result)) {
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			if ($i%2!=0) {$qbreakdown .= "<tr class=even>"; } else {$qbreakdown .= "<tr class=odd>";}
			$qbreakdown .= '<td>';
			if ($row[3]==1) {
				$qbreakdown .= '<span class="noticetext">Withdrawn</span> ';
			}
			$qbreakdown .= $row[0];
			$qbreakdown .= "</td><td>{$scores[$row[1]]} / ";
			if ($row[2]==9999) {
				$poss= $line['defpoints'];
			} else {
				$poss = $row[2];
			}
			$qbreakdown .= $poss;

			$qbreakdown .= "</td></tr>\n";
			$i++;
			$totpt += $scores[$row[1]];
			$totposs += $poss;
		}
		$pc = round(100*$totpt/$totposs,1);


		$endmsg = unserialize($line['endmsg']);
		$outmsg = '';
		if (isset($endmsg['msgs'])) {
			foreach ($endmsg['msgs'] as $sc=>$msg) { //array must be reverse sorted
				if (($endmsg['type']==0 && $total>=$sc) || ($endmsg['type']==1 && $pc>=$sc)) {
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
		echo "<p>Started: " . tzdate("F j, Y, g:i a",$line['starttime']) ."<BR>\n";
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
			echo "<table cellpadding=5 class=gb><thead><tr><th>Question</th><th>Points / Possible</th></tr></thead><tbody>\n";
			echo $qbreakdown;
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
		//DB $query = "SELECT FirstName,LastName FROM imas_users WHERE id='{$_GET['uid']}'";
		//DB $result = mysql_query($query) or die("Query failed : $query " . mysql_error());
		//DB $row = mysql_fetch_row($result);
		$stm = $DBH->prepare("SELECT FirstName,LastName FROM imas_users WHERE id=:id");
		$stm->execute(array(':id'=>$_GET['uid']));
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
		$c = str_replace('&','%%',$c);
		$sh = strip_tags($c);
		if (trim($sh)=='' || strpos($c,'<table')!==false) {
			$sh = "[view]";
		} else if (strlen($sh)>15) {
			$sh = substr($sh,0,15).'...';
		}
		if ($sh!=$c) {
			$choicesdata[1][$k] = '<span onmouseover="tipshow(this,\''.trim(str_replace('&','%%',htmlentities($c,ENT_QUOTES|ENT_HTML401))).'\')" onmouseout="tipout()">'.$sh.'</span>';
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

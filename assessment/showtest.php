<?php
//IMathAS:  Frontend of testing engine - manages administration of assessments
//(c) 2006 David Lippman

	require("../validate.php");
	if (!isset($sessiondata['sessiontestid']) && !isset($teacherid) && !isset($studentid)) {
		echo "<html><body>";

		echo "You are not authorized to view this page.  If you are trying to reaccess a test you've already ";
		echo "started, access it from the course page</body></html>\n";
		exit;
	}
	include("displayq2.php");
	include("testutil.php");
	//error_reporting(0);  //prevents output of error messages
	
	//check to see if test starting test or returning to test
	if (isset($_GET['id'])) {
		//check dates, determine if review
		$aid = $_GET['id'];
		$isreview = false;
		
		$query = "SELECT deffeedback,startdate,enddate,reviewdate,shuffle,itemorder,password FROM imas_assessments WHERE id='$aid'";
		$result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
		$adata = mysql_fetch_array($result, MYSQL_ASSOC);
		$now = time();
		
		if ($now < $adata['startdate'] || $adata['enddate']<$now) { //outside normal range for test
			$query = "SELECT startdate,enddate FROM imas_exceptions WHERE userid='$userid' AND assessmentid='$aid'";
			$result2 = mysql_query($query) or die("Query failed : " . mysql_error());
			$row = mysql_fetch_row($result2);
			if ($row!=null) {
				if ($now<$row[0] || $row[1]<$now) { //outside exception dates
					if ($now > $adata['startdate'] && $now<$adata['reviewdate']) {
						$isreview = true;
					} else {
						if (!isset($teacherid)) {
							echo "Assessment is closed";
							exit;
						}
					}
				}
			} else { //no exception
				if ($now > $adata['startdate'] && $now<$adata['reviewdate']) {
					$isreview = true;
				} else {
					if (!isset($teacherid)) {
						echo "Assessment is closed";
						exit;
					}
				}
			}
		}
		
		//check for password
		if (trim($adata['password'])!='' && !isset($teacherid)) { //has passwd
			$pwfail = true;
			if (isset($_POST['password'])) {
				if (trim($_POST['password'])==trim($adata['password'])) {
					$pwfail = false;
				} else {
					$out = "<p>Password incorrect.  Try again.<p>";
				}
			} 
			if ($pwfail) {
				require("../header.php");
				echo $out;
				echo "<p>Password required for access.</p>";
				echo "<form method=post action=\"showtest.php?cid={$_GET['cid']}&id={$_GET['id']}\">";
				echo "<p>Password: <input type=text name=\"password\" /></p>";
				echo "<input type=submit value=\"Submit\" />";
				echo "</form>";
				require("../footer.php");
				exit;
			}
		}
		
		$query = "SELECT id,agroupid FROM imas_assessment_sessions WHERE userid='$userid' AND assessmentid='{$_GET['id']}'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$line = mysql_fetch_array($result, MYSQL_ASSOC);
		
		if ($line == null) { //starting test
			//get question set
			
			if (trim($adata['itemorder'])=='') {
				echo "No questions in assessment!";
				exit;
			}
			$questions = explode(",",$adata['itemorder']);
			foreach($questions as $k=>$q) {
				if (strpos($q,'~')!==false) {
					$sub = explode('~',$q);
					$questions[$k] = $sub[array_rand($sub,1)];
				}
			}
			if ($adata['shuffle']&1) {shuffle($questions);}
			
			if ($adata['shuffle']&2) { //all questions same random seed
				if ($adata['shuffle']&4) { //all students same seed
					$seeds = array_fill(0,count($questions),$aid);
				} else {
					$seeds = array_fill(0,count($questions),rand(1,9999));
				}
			} else {
				if ($adata['shuffle']&4) { //all students same seed
					for ($i = 0; $i<count($questions);$i++) {
						$seeds[] = $aid + $i;
					}
				} else {
					for ($i = 0; $i<count($questions);$i++) {
						$seeds[] = rand(1,9999);
					}
				}
			}


			$scores = array_fill(0,count($questions),-1);
			$attempts = array_fill(0,count($questions),0);
			$lastanswers = array_fill(0,count($questions),'');
			
			$starttime = time();
			
			if (!isset($questions)) {  //assessment has no questions!
				echo "<html><body>Assessment has no questions!";
				echo "</body></html>\n";
				exit;
			} 
			
			$qlist = implode(",",$questions);
			$seedlist = implode(",",$seeds);
			$scorelist = implode(",",$scores);
			$attemptslist = implode(",",$attempts);
			$lalist = implode("~",$lastanswers);
			
			$bestscorelist = implode(',',$scores);
			$bestattemptslist = implode(',',$attempts);
			$bestseedslist = implode(',',$seeds);
			$bestlalist = implode('~',$lastanswers);
			
			$query = "INSERT INTO imas_assessment_sessions (userid,assessmentid,questions,seeds,scores,attempts,lastanswers,starttime,bestscores,bestattempts,bestseeds,bestlastanswers) ";
			$query .= "VALUES ('$userid','{$_GET['id']}','$qlist','$seedlist','$scorelist','$attemptslist','$lalist',$starttime,'$bestscorelist','$bestattemptslist','$bestseedslist','$bestlalist');";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$sessiondata['sessiontestid'] = mysql_insert_id();
			$sessiondata['isreview'] = $isreview;
			if (isset($teacherid)) {
				$sessiondata['isteacher']=true;
			} else {
				$sessiondata['isteacher']=false;
			}
			$sessiondata['groupid'] = 0;
			$query = "SELECT name FROM imas_courses WHERE id='{$_GET['cid']}'";
			$result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
			$sessiondata['coursename'] = mysql_result($result,0,0);
			writesessiondata();
			session_write_close();
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/showtest.php");
			exit;
		} else { //returning to test
			
			if ($isreview) { //past enddate, before reviewdate
				//clear out test for review.
				$questions = explode(",",$adata['itemorder']);
				if ($line['shuffle']&2) {
					$seeds = array_fill(0,count($questions),rand(1,9999));	
				} else {
					for ($i = 0; $i<count($questions);$i++) {
						$seeds[] = rand(1,9999);
					}
				}
				$scores = array_fill(0,count($questions),-1);
				$attempts = array_fill(0,count($questions),0);
				$lastanswers = array_fill(0,count($questions),'');
				$seedlist = implode(",",$seeds);
				$scorelist = implode(",",$scores);
				$attemptslist = implode(",",$attempts);
				$lalist = implode("~",$lastanswers);
				
				$query = "UPDATE imas_assessment_sessions SET scores='$scorelist',seeds='$seedlist',attempts='$attemptslist',lastanswers='$lalist' WHERE userid='$userid' AND assessmentid='$aid' LIMIT 1";
				mysql_query($query) or die("Query failed : $query: " . mysql_error());
				
			}
			$deffeedback = explode('-',$adata['deffeedback']);
			//removed: $deffeedback[0] == "Practice" || 
			if ($myrights<6 || isset($teacherid)) {  // is teacher or guest - delete out out assessment session
				$query = "DELETE FROM imas_assessment_sessions WHERE userid='$userid' AND assessmentid='$aid' LIMIT 1";
				$result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
				header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/showtest.php?cid={$_GET['cid']}&id=$aid");
				exit;
			}
			//Return to test.
			$sessiondata['sessiontestid'] = $line['id'];
			$sessiondata['isreview'] = $isreview;
			if (isset($teacherid)) {
				$sessiondata['isteacher']=true;
			} else {
				$sessiondata['isteacher']=false;
			}
			
			
			$sessiondata['groupid'] = $line['agroupid'];
		
			$query = "SELECT name FROM imas_courses WHERE id='{$_GET['cid']}'";
			$result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
			$sessiondata['coursename'] = mysql_result($result,0,0);
			writesessiondata();
			session_write_close();
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/showtest.php");
		}
		exit;
	} 
	
	//already started test
	if (!isset($sessiondata['sessiontestid'])) {
		echo "<html><body>Error.  Access test from course page</body></html>\n";
		exit;
	}
	$testid = addslashes($sessiondata['sessiontestid']);
	$isteacher = $sessiondata['isteacher'];
	$query = "SELECT * FROM imas_assessment_sessions WHERE id='$testid'";
	$result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
	$line = mysql_fetch_array($result, MYSQL_ASSOC);
	$questions = explode(",",$line['questions']);
	$seeds = explode(",",$line['seeds']);
	$scores = explode(",",$line['scores']);
	$attempts = explode(",",$line['attempts']);
	$lastanswers = explode("~",$line['lastanswers']);
	$bestseeds = explode(",",$line['bestseeds']);
	$bestscores = explode(",",$line['bestscores']);
	$bestattempts = explode(",",$line['bestattempts']);
	$bestlastanswers = explode("~",$line['bestlastanswers']);
	$starttime = $line['starttime'];
	
	$query = "SELECT * FROM imas_assessments WHERE id='{$line['assessmentid']}'";
	$result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
	$testsettings = mysql_fetch_array($result, MYSQL_ASSOC);
	list($testsettings['testtype'],$testsettings['showans']) = explode('-',$testsettings['deffeedback']);
	
	$qi = getquestioninfo($questions,$testsettings);
	$now = time();
	//check for dates - kick out student if after due date
	if (!$isteacher) {
		if ($now < $testsettings['startdate'] || $testsettings['enddate']<$now) { //outside normal range for test
			$query = "SELECT startdate,enddate FROM imas_exceptions WHERE userid='$userid' AND assessmentid='{$line['assessmentid']}'";
			$result2 = mysql_query($query) or die("Query failed : " . mysql_error());
			$row = mysql_fetch_row($result2);
			if ($row!=null) {
				if ($now<$row[0] || $row[1]<$now) { //outside exception dates
					if ($now > $testsettings['startdate'] && $now<$testsettings['reviewdate']) {
						$isreview = true;
					} else {
						if (!isset($teacherid)) {
							echo "Assessment is closed";
							echo "<br/><a href=\"../course/course.php?cid={$testsettings['courseid']}\">Return to course page</a>";
							exit;
						}
					}
				}
			} else { //no exception
				if ($now > $testsettings['startdate'] && $now<$testsettings['reviewdate']) {
					$isreview = true;
				} else {
					if (!isset($teacherid)) {
						echo "Assessment is closed";
						echo "<br/><a href=\"../course/course.php?cid={$testsettings['courseid']}\">Return to course page</a>";
						exit;
					}
				}
			}
		}
	}
	if ($isreview) {
		$testsettings['displaymethod'] = "SkipAround";
		$testsettings['testtype']="Practice";
		$testsettings['defattempts'] = 0;
		$testsettings['defpenalty'] = 0;
		$testsettings['showans'] = '0';
	}
	$allowregen = ($testsettings['testtype']=="Practice" || $testsettings['testtype']=="Homework");
	$showeachscore = ($testsettings['testtype']=="Practice" || $testsettings['testtype']=="AsGo" || $testsettings['testtype']=="Homework");
	$showansduring = (($testsettings['testtype']=="Practice" || $testsettings['testtype']=="Homework") && $testsettings['showans']!='N' && $testsettings['showans']!='F');
	$showansafterlast = ($testsettings['showans']==='F');
	$noindivscores = ($testsettings['testtype']=="EndScore" || $testsettings['testtype']=="NoScores");
	$showhints = ($testsettings['showhints']==1);
	$regenonreattempt = (($testsettings['shuffle']&8)==8);
	
	if (isset($_GET['reattempt'])) {
		if ($_GET['reattempt']=="all") {
			$remainingposs = getallremainingpossible($qi,$questions,$testsettings,$attempts);
			for ($i = 0; $i<count($questions); $i++) {
				if ($attempts[$i]<$qi[$questions[$i]]['attempts'] || $qi[$questions[$i]]['attempts']==0) {
					if ($noindivscores || getpts($scores[$i])<$remainingposs[$i]) {
						$scores[$i] = -1;
						if (($regenonreattempt && $qi[$questions[$i]]['regen']==0) || $qi[$questions[$i]]['regen']==1) {
							$seeds[$i] = rand(1,9999);
						}
					}
				}
			}
		} else {
			$toclear = $_GET['reattempt'];
			if ($attempts[$toclear]<$qi[$questions[$toclear]]['attempts'] || $qi[$questions[$toclear]]['attempts']==0) {
				$scores[$toclear] = -1;	
				if (($regenonreattempt && $qi[$questions[$toclear]]['regen']==0) || $qi[$questions[$toclear]]['regen']==1) {
					$seeds[$toclear] = rand(1,9999);
				}
			}
		}
		recordtestdata();
	}
	if (isset($_GET['regen']) && $allowregen) {
		srand();
		$toregen = $_GET['regen'];
		$seeds[$toregen] = rand(1,9999);
		$scores[$toregen] = -1;
		$attempts[$toregen] = 0;
		$newla = array();
		$laarr = explode('##',$lastanswers[$toregen]);
		foreach ($laarr as $lael) {
			if ($lael=="ReGen") {
				$newla[] = "ReGen";
			}
		}
		$newla[] = "ReGen";
		$lastanswers[$toregen] = implode('##',$newla);
		
		recordtestdata();
	}
	if (isset($_GET['regenall']) && $allowregen) {
		srand();
		if ($_GET['regenall']=="missed") {
			for ($i = 0; $i<count($questions); $i++) {
				if (getpts($scores[$i])<$qi[$questions[$i]]['points']) { 
					$scores[$i] = -1;
					$attempts[$i] = 0;
					$seeds[$i] = rand(1,9999);
					$newla = array();
					$laarr = explode('##',$lastanswers[$i]);
					foreach ($laarr as $lael) {
						if ($lael=="ReGen") {
							$newla[] = "ReGen";
						}
					}
					$newla[] = "ReGen";
					$lastanswers[$i] = implode('##',$newla);
				}
			}
		} else if ($_GET['regenall']=="all") {
			for ($i = 0; $i<count($questions); $i++) {
				$scores[$i] = -1;
				$attempts[$i] = 0;
				$seeds[$i] = rand(1,9999);
				$newla = array();
				$laarr = explode('##',$lastanswers[$i]);
				foreach ($laarr as $lael) {
					if ($lael=="ReGen") {
						$newla[] = "ReGen";
					}
				}
				$newla[] = "ReGen";
				$lastanswers[$i] = implode('##',$newla);	
			}
		} else if ($_GET['regenall']=="fromscratch" && $testsettings['testtype']=="Practice" && !$isreview) {
			$query = "DELETE FROM imas_assessment_sessions WHERE userid='$userid' AND assessmentid='{$testsettings['id']}' LIMIT 1";
			$result = mysql_query($query) or die("Query failed : $query: " . mysql_error());
			header("Location: http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/showtest.php?cid={$testsettings['courseid']}&id={$testsettings['id']}");
			exit;	
		}
		
		recordtestdata();
			
	}
			
	
	$isdiag = isset($sessiondata['isdiag']);
	if ($isdiag) {
		$diagid = $sessiondata['isdiag'];
	}

	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
	$useeditor = 1;
	require("header.php");
	
	if (!$isdiag) {
		echo "<div class=breadcrumb><a href=\"../index.php\">Home</a> &gt; <a href=\"../course/course.php?cid={$testsettings['courseid']}\">{$sessiondata['coursename']}</a> ";
	 echo "&gt; Assessment</div>";
	}
	

	if (!$sessiondata['isteacher'] && ($testsettings['isgroup']==1 || $testsettings['isgroup']==2) && ($sessiondata['groupid']==0 || isset($_GET['addgrpmem']))) {
		if (isset($_POST['user1'])) {
			if ($sessiondata['groupid']==0) {
				//double check not already added to group by someone else
				$query = "SELECT agroupid FROM imas_assessment_sessions WHERE id='$testid'";
				$result = mysql_query($query) or die("Query failed : $query:" . mysql_error());
				$agroupid = mysql_result($result,0,0);
				if ($agroupid==0) { //really has no group, create group
					$query = "UPDATE imas_assessment_sessions SET agroupid='$testid' WHERE id='$testid'";
					mysql_query($query) or die("Query failed : $query:" . mysql_error());
					$agroupid = $testid;
				} else {
					echo "<p>Someone already added you to a group.  Using that group.</p>";
				}
				$sessiondata['groupid'] = $agroupid;
				writesessiondata();
			} else {
				$agroupid = $sessiondata['groupid'];
			}
			$query = "SELECT assessmentid,agroupid,questions,seeds,scores,attempts,lastanswers,starttime,endtime,bestseeds,bestattempts,bestscores,bestlastanswers ";
			$query .= "FROM imas_assessment_sessions WHERE id='$testid'";
			$result = mysql_query($query) or die("Query failed : $query:" . mysql_error());
			$row = mysql_fetch_row($result);
			$insrow = "'".implode("','",addslashes_deep($row))."'";
			for ($i=1;$i<7;$i++) {
				if ($_POST['user'.$i]!=0) {
					$query = "SELECT password,LastName,FirstName FROM imas_users WHERE id='{$_POST['user'.$i]}'";
					$result = mysql_query($query) or die("Query failed : $query:" . mysql_error());
					$thisusername = mysql_result($result,0,2) . ' ' . mysql_result($result,0,1);	
					if ($testsettings['isgroup']==1) {
						$md5pw = md5($_POST['pw'.$i]);
						if (mysql_result($result,0,0)!=$md5pw) {
							echo "<p>$thisusername: password incorrect</p>";
							$errcnt++;
							continue;
						} 
					} 
						
					$thisuser = $_POST['user'.$i];
					$query = "SELECT id,agroupid FROM imas_assessment_sessions WHERE userid='{$_POST['user'.$i]}' AND assessmentid={$testsettings['id']}";
					$result = mysql_query($query) or die("Query failed : $query:" . mysql_error());
					if (mysql_num_rows($result)>0) {
						$row = mysql_fetch_row($result);
						if ($row[1]>0) { 
							echo "<p>$thisusername already has a group.  No change made</p>";
						} else {
							$query = "UPDATE imas_assessment_sessions SET agroupid='$agroupid' WHERE id='{$row[0]}'";
							mysql_query($query) or die("Query failed : $query:" . mysql_error());
							echo "<p>$thisusername added to group, overwriting existing attempt.</p>";
						}
					} else {
						$query = "INSERT INTO imas_assessment_sessions (userid,assessmentid,agroupid,questions,seeds,scores,attempts,lastanswers,starttime,endtime,bestseeds,bestattempts,bestscores,bestlastanswers) ";
						$query .= "VALUES ('{$_POST['user'.$i]}',$insrow)";
						mysql_query($query) or die("Query failed : $query:" . mysql_error());
						echo "<p>$thisusername added to group.</p>";
					}
				}
			}
		} else {
			echo '<h2>Select group members</h2>';
			
			if ($sessiondata['groupid']>0) { //adding members to existing grp
				echo "Current Group Members: <ul>";
				$query = "SELECT imas_users.id,imas_users.FirstName,imas_users.LastName FROM imas_users,imas_assessment_sessions WHERE ";
				$query .= "imas_users.id=imas_assessment_sessions.userid AND imas_assessment_sessions.agroupid='{$sessiondata['groupid']}' ORDER BY imas_users.LastName,imas_users.FirstName";
				$result = mysql_query($query) or die("Query failed : $query;  " . mysql_error());
				while ($row = mysql_fetch_row($result)) {
					$curgrp[] = $row[0];
					echo "<li>{$row[2]}, {$row[1]}</li>";
				}
				echo "</ul>";	
			} else {
				echo "Current Group Member: $userfullname</br>";
				$curgrp = array($userid);
			}
			$curids = "'".implode("','",$curgrp)."'";
			$selops = '<option value="0">Select a name..</option>';
			$query = "SELECT imas_users.id,imas_users.FirstName,imas_users.LastName FROM imas_users,imas_students ";
			$query .= "WHERE imas_users.id=imas_students.userid AND imas_students.courseid='{$testsettings['courseid']}' ";
			$query .= "AND imas_users.id NOT IN ($curids) ORDER BY imas_users.LastName,imas_users.FirstName";
			$result = mysql_query($query) or die("Query failed : $query;  " . mysql_error());
			while ($row = mysql_fetch_row($result)) {
				$selops .= "<option value=\"{$row[0]}\">{$row[2]}, {$row[1]}</option>";
			}
			echo '<p>Each group member (other than the currently logged in student) to be added should select their name ';
			if ($testsettings['isgroup']==1) {
				echo 'and enter their password ';
			}
			echo 'here.</p>';
			echo '<form method=post action="showtest.php?addgrpmem=true">';
			echo 'Username: <select name="user1">'.$selops.'</select> ';
			if ($testsettings['isgroup']==1) {
				echo 'Password: <input type=password name="pw1" />';
			}
			echo '<br />Username: <select name="user2">'.$selops.'</select> ';
			if ($testsettings['isgroup']==1) {
				echo 'Password: <input type=password name="pw2" />';
			}
			echo '<br />Username: <select name="user3">'.$selops.'</select> ';
			if ($testsettings['isgroup']==1) {
				echo 'Password: <input type=password name="pw3" />';
			}
			echo '<br />Username: <select name="user4">'.$selops.'</select> ';
			if ($testsettings['isgroup']==1) {
				echo 'Password: <input type=password name="pw4" />';
			}
			echo '<br />Username: <select name="user5">'.$selops.'</select> ';
			if ($testsettings['isgroup']==1) {
				echo 'Password: <input type=password name="pw5" />';
			}
			echo '<br />Username: <select name="user6">'.$selops.'</select> ';
			if ($testsettings['isgroup']==1) {
				echo 'Password: <input type=password name="pw6" />';
			}
			echo '<br /><input type=submit value="Record Group and Continue"/>';
			echo '</form>';
			require("../footer.php");
			exit;
		}
	}
	if (!$sessiondata['isteacher'] && $testsettings['isgroup']==3  && $sessiondata['groupid']==0) {
		//double check not already added to group by someone else
		$query = "SELECT agroupid FROM imas_assessment_sessions WHERE id='$testid'";
		$result = mysql_query($query) or die("Query failed : $query:" . mysql_error());
		$agroupid = mysql_result($result,0,0);
		if ($agroupid==0) { //really has no group, create group
			$query = "UPDATE imas_assessment_sessions SET agroupid='$testid' WHERE id='$testid'";
			mysql_query($query) or die("Query failed : $query:" . mysql_error());
			$agroupid = $testid;
		} else {
			echo "<p>Someone already added you to a group.  Using that group.</p>";
		}
		$sessiondata['groupid'] = $agroupid;
		writesessiondata();
	}
	
	echo "<h2>{$testsettings['name']}</h2>\n";
	
	if ($testsettings['testtype']=="Practice" && !$isreview) {
		echo "<div class=right><span style=\"color:#f00\">Practice Test.</span>  <a href=\"showtest.php?regenall=fromscratch\">Create new version.</a></div>";
	}
	if ($testsettings['timelimit']>0 && !$isreview) {
		$now = time();
		$remaining = $testsettings['timelimit']-($now - $starttime);
		if ($testsettings['timelimit']>3600) {
			$tlhrs = floor($testsettings['timelimit']/3600);
			$tlrem = $testsettings['timelimit'] % 3600;
			$tlmin = floor($tlrem/60);
			$tlsec = $tlrem % 60;
			$tlwrds = "$tlhrs hour";
			if ($tlhrs > 1) { $tlwrds .= "s";}
			if ($tlmin > 0) { $tlwrds .= ", $tlmin minute";}
			if ($tlmin > 1) { $tlwrds .= "s";}
			if ($tlsec > 0) { $tlwrds .= ", $tlsec second";}
			if ($tlsec > 1) { $tlwrds .= "s";}
		} else if ($testsettings['timelimit']>60) {
			$tlmin = floor($testsettings['timelimit']/60);
			$tlsec = $testsettings['timelimit'] % 60;
			$tlwrds = "$tlmin minute";
			if ($tlmin > 1) { $tlwrds .= "s";}
			if ($tlsec > 0) { $tlwrds .= ", $tlsec second";}
			if ($tlsec > 1) { $tlwrds .= "s";}
		} else {
			$tlwrds = $testsettings['timelimit'] . " second(s)";
		}
		if ($remaining < 0) {
			echo "<div class=right>Timelimit: $tlwrds.  Time Expired</div>\n";
		} else {
		if ($remaining > 3600) {
			$hours = floor($remaining/3600);
			$remaining = $remaining - 3600*$hours;
		} else { $hours = 0;}
		if ($remaining > 60) {
			$minutes = floor($remaining/60);
			$remaining = $remaining - 60*$minutes;
		} else {$minutes=0;}
		$seconds = $remaining;
		echo "<div class=right>Timelimit: $tlwrds. <span id=timeremaining>$hours:$minutes:$seconds</span> remaining</div>\n";
		echo "<script type=\"text/javascript\">\n";
		echo " hours = $hours; minutes = $minutes; seconds = $seconds; done=false;\n";
		echo " function updatetime() {\n";
		echo "	  seconds--;\n";
		echo "    if (seconds==0 && minutes==0 && hours==0) {done=true; alert(\"Time Limit has elapsed\");}\n";
		echo "    if (seconds==0 && minutes==5 && hours==0) {document.getElementById('timeremaining').style.color=\"#f00\";}\n";
		echo "    if (seconds < 0) { seconds=59; minutes--; }\n";
		echo "    if (minutes < 0) { minutes=59; hours--;}\n";
		echo "	  str = '';\n";
		echo "	  if (hours > 0) { str += hours + ':';}\n";
		echo "    if (hours > 0 && minutes <10) { str += '0';}\n";
		echo "	  if (minutes >0) {str += minutes + ':';}\n";
		echo "	    else if (hours>0) {str += '0:';}\n";
		echo "      else {str += ':';}\n";
		echo "    if (seconds<10) { str += '0';}\n";
		echo "	  str += seconds + '';\n";
		echo "	  document.getElementById('timeremaining').innerHTML = str;\n";
		echo "    if (!done) {setTimeout(\"updatetime()\",1000);}\n";
		echo " }\n";
		echo " updatetime();\n";
		echo "</script>\n";
		}
	} else if ($isreview) {
		echo "<div class=right style=\"color:#f00\">In Review Mode - no scores will be saved<br/><a href=\"showtest.php?regenall=all\">Create new versions of all questions.</a></div>\n";	
	} else {
		echo "<div class=right>No time limit</div>\n";
	}
	if ($_GET['action']=="skip" || $_GET['action']=="seq") {
		echo "<div class=right><span onclick=\"document.getElementById('intro').className='intro';\"><a href=\"#\">Show Instructions</a></span></div>\n";
	}
	if (isset($_GET['action'])) {
		
		if ($_GET['action']=="scoreall") {
			//score test
			for ($i=0; $i < count($questions); $i++) {
				if (isset($_POST["qn$i"]) || isset($_POST['qn'.(1000*($i+1))]) || isset($_POST["qn$i-0"]) || isset($_POST['qn'.(1000*($i+1)).'-0'])) {
					if ($_POST['verattempts'][$i]!=$attempts[$i]) {
						echo "Question ".($i+1)." has been submittted since you viewed it.  Your answer just submitted was not scored or recorded.<br/>";
					} else {
						scorequestion($i);
					}
				}
			}
			//record scores
			
			$now = time();
			if (isset($_POST['saveforlater'])) {
				recordtestdata(true);
				echo "<p>Answers saved, but not submitted for grading.  You may continue with the test, or ";
				echo "come back to it later. ";
				if ($testsettings['timelimit']>0) {echo "The timelimit will continue to count down";}
				echo "</p><p><a href=\"showtest.php\">Return to test</a> or ";
				if (!$isdiag) {
					echo "<a href=\"../course/course.php?cid={$testsettings['courseid']}\">Return to Course Page</a></p>\n";
				} else {
					echo "<a href=\"../diag/index.php?id=$diagid\">Return to Diagnostics Page</a></p>\n";
				}
			} else {
				recordtestdata();
				
				showscores($questions,$attempts,$testsettings);
			
				endtest($testsettings);
				if (!$isdiag) {
					echo "<p><A href=\"../course/course.php?cid={$testsettings['courseid']}\">Return to Course Page</a></p>\n";
				} else {
					echo "<p><a href=\"../diag/index.php?id=$diagid\">Return to Diagnostics Page</a></p>\n";
				}
			}
		} else if ($_GET['action']=="shownext") {
			if (isset($_GET['score'])) {
				$last = $_GET['score'];
				
				if ($_POST['verattempts']!=$attempts[$last]) {
					echo "<p>The last question has been submittted since you viewed it, and that grade is shown below.  Your answer just submitted was not scored or recorded.</p>";
				} else {
					scorequestion($last);
					
					//record score
					
					recordtestdata();
				}
				if ($showeachscore) {
					$possible = $qi[$questions[$last]]['points'];
					echo "<p>Previous Question:<br/>Score on last attempt: ";
					echo printscore($scores[$last],$possible);
					if ($allowregen && !$isreview) {
						echo "<br/>Score in gradebook: ";
						echo printscore($bestscores[$last],$possible);
					} 
					echo "</p>\n";
					if (canimprove($last)) {
						echo "<p><a href=\"showtest.php?action=shownext&to=$last&reattempt=$last\">Reattempt last question</a>.  If you do not reattempt now, you will have another chance once you complete the test.</p>\n";
					}
				}
				//working now page not cached
				if ($allowregen) {
					echo "<p><a href=\"showtest.php?action=shownext&to=$last&regen=$last\">Try another similar question</a></p>\n";
				}
				//show next
				unset($toshow);
				for ($i=$last+1;$i<count($questions);$i++) {
					if (unans($scores[$i])) {
						$toshow=$i;
						$done = false;
						break;
					}
				}
				if (!isset($toshow)) { //no more to show
					$done = true;
				} 
			} else if (isset($_GET['to'])) {
				$toshow = addslashes($_GET['to']);
				$done = false;
			}
			
			if (!$done) { //can show next
				echo "<form method=post action=\"showtest.php?action=shownext&score=$toshow\" onsubmit=\"return doonsubmit(this)\">\n";
				basicshowq($toshow);
				showqinfobar($toshow,true,true);
				echo "<input type=submit class=btn value=Continue>\n";
			} else { //are all done
				showscores($questions,$attempts,$testsettings);
				endtest($testsettings);
				if (!$isdiag) {
					echo "<p><A HREf=\"../course/course.php?cid={$testsettings['courseid']}\">Return to Course Page</a></p>\n";
				} else {
					echo "<p><a href=\"../diag/index.php?id=$diagid\">Return to Diagnostics Page</a></p>\n";
				}
			}
		} else if ($_GET['action']=="skip") {

			if (isset($_GET['score'])) { //score a problem
				$qn = $_GET['score'];
				
				if ($_POST['verattempts']!=$attempts[$qn]) {
					echo "<p>This question has been submittted since you viewed it, and that grade is shown below.  Your answer just submitted was not scored or recorded.</p>";
				} else {
					scorequestion($qn);
					
					//record score
					
					recordtestdata();
				}
				$lefttodo = shownavbar($questions,$scores,$qn,$testsettings['showcat']);
				
				echo "<div class=inset>\n";
				echo "<a name=\"beginquestions\"></a>\n";
				$reattemptsremain = false;
				if ($showeachscore) {
					$possible = $qi[$questions[$qn]]['points'];
					echo "<p>Score on last attempt: ";
					echo printscore($scores[$qn],$possible);
					echo "</p>\n";
					if ($allowregen && !$isreview) {
						echo "<p>Score in gradebook: ";
						echo printscore($bestscores[$qn],$possible);
						echo "</p>";
					} 
					
					if (canimprove($qn)) {
						echo "<p><a href=\"showtest.php?action=skip&to=$qn&reattempt=$qn\">Reattempt last question</a></p>\n";
						$reattemptsremain = true;
					}
				}
				if ($allowregen) {
					echo "<p><a href=\"showtest.php?action=skip&to=$qn&regen=$qn\">Try another similar question</a></p>\n";
				}
				if ($lefttodo > 0) {
					echo "<p>Question scored.  <b>Select another question</b></p>";
					if ($reattemptsremain == false && $showeachscore) {
						echo "<p>This question, with your last answer";
						if (($showansafterlast && $qi[$questions[$qn]]['showans']=='0') || $qi[$questions[$qn]]['showans']=='F') {
							echo " and correct answer";
						}
						echo ", can be viewed by clicking on the question number again.</p>";
					}
					echo "<p>or click <a href=\"showtest.php?action=skip&done=true\">here</a> to end and score test now</p>\n";
				} else {
					echo "<a href=\"showtest.php?action=skip&done=true\">Click here to finalize and score test</a>\n";
				}
				echo "</div>\n";
			} else if (isset($_GET['to'])) { //jump to a problem
				$next = $_GET['to'];
				echo filter("<div id=intro class=hidden>{$testsettings['intro']}</div>\n");
				
				$lefttodo = shownavbar($questions,$scores,$next,$testsettings['showcat']);
				if (unans($scores[$next])) {
					echo "<div class=inset>\n";
					echo "<form method=post action=\"showtest.php?action=skip&score=$next\" onsubmit=\"return doonsubmit(this)\">\n";
					echo "<a name=\"beginquestions\"></a>\n";
					basicshowq($next);
					showqinfobar($next,true,true);
					echo "<input type=submit class=btn value=Submit>\n";
					echo "</div>\n";
					echo "</form>\n";
				} else {
					echo "<div class=inset>\n";
					echo "<a name=\"beginquestions\"></a>\n";
					echo "You've already done this problem.\n";
					$reattemptsremain = false;
					if ($showeachscore) {
						$possible = $qi[$questions[$next]]['points'];
						echo "<p>Score on last attempt: ";
						echo printscore($scores[$next],$possible);
						echo "</p>\n";
						if ($isreview || $allowregen) {
							echo "<p>Score in gradebook: ";
							echo printscore($bestscores[$next],$possible);
							echo "</p>";
						} 
						if (canimprove($next)) {
							echo "<p><a href=\"showtest.php?action=skip&to=$next&reattempt=$next\">Reattempt this question</a></p>\n";
							$reattemptsremain = true;
						}
					}
					if ($allowregen) {
						echo "<p><a href=\"showtest.php?action=skip&to=$next&regen=$next\">Try another similar question</a></p>\n";
					}
					if ($lefttodo == 0) {
						echo "<a href=\"showtest.php?action=skip&done=true\">Click here to finalize and score test</a>\n";
					}
					if (!$reattemptsremain && $showeachscore) {
						echo "<p>Question with last attempt is displayed for your review only</p>";
						$showa = false;
						$qshowansafterlast = (($showansafterlast && $qi[$questions[$next]]['showans']=='0') || $qi[$questions[$next]]['showans']=='F');
						displayq($next,$qi[$questions[$next]]['questionsetid'],$seeds[$next],$qshowansafterlast,false,$attempts[$next],false,false);
					}
					echo "</div>\n";
				}
			} else if (isset($_GET['done'])) { //are all done

				showscores($questions,$attempts,$testsettings);
				endtest($testsettings);
				if (!$isdiag) {
					echo "<p><a href=\"../course/course.php?cid={$testsettings['courseid']}\">Return to Course Page</a></p>\n";
				} else {
					echo "<p><a href=\"../diag/index.php?id=$diagid\">Return to Diagnostics Page</a></p>\n";
				}
			}
		} else if ($_GET['action']=="seq") {
			if (isset($_GET['score'])) { //score a problem
				$qn = $_GET['score'];
				if ($_POST['verattempts']!=$attempts[$qn]) {
					echo "<p>The last question has been submittted since you viewed it, and that score is shown below. Your answer just submitted was not scored or recorded.</p>";
				} else {
					scorequestion($qn);
					//record score
					recordtestdata();
				}
				
				echo "<div class=review style=\"margin-top:5px;\">\n";
				$reattemptsremain = false;
				if ($showeachscore) {
					$possible = $qi[$questions[$qn]]['points'];
					echo "<p>Score on last attempt: ";
					echo printscore($scores[$qn],$possible);
					echo "</p>\n";
					if ($allowregen && !$isreview) {
						echo "<p>Score in gradebook: ";
						echo printscore($bestscores[$qn],$possible);
						echo "</p>";
					} 
					if (canimprove($qn)) {
						echo "<p><a href=\"showtest.php?action=seq&to=$qn&reattempt=$qn\">Reattempt last question</a></p>\n";
						$reattemptsremain = true; 
					}
				}
				if ($allowregen) {
					echo "<p><a href=\"showtest.php?action=seq&to=$qn&regen=$qn\">Try another similar question</a></p>\n";
				}
				unset($toshow);
				if ($reattemptsremain) {
					$toshow = $qn;
				} else {
					for ($i=$qn+1;$i<count($questions);$i++) {
						if (unans($scores[$i])) {
							$toshow=$i;
							$done = false;
							break;
						}
					}
					if (!isset($toshow)) {
						for ($i=0;$i<$qn;$i++) {
							if (unans($scores[$i])) {
								$toshow=$i;
								$done = false;
								break;
							}
						}
					}
				}
				if (!isset($toshow)) { //no more to show
					$done = true;
				} 
				if (!$done) {
					echo "<p>Question scored. Continue with assessment, or click <a href=\"showtest.php?action=seq&done=true\">here</a> to end and score test now</p>\n";
					echo "</div>\n";
					echo "<hr/>";
				} else {
					echo "</div>\n";
					//echo "<a href=\"showtest.php?action=skip&done=true\">Click here to finalize and score test</a>\n";
				}
				
				
			}
			if (isset($_GET['to'])) { //jump to a problem
				$toshow = $_GET['to'];
			}
			if ($done || isset($_GET['done'])) { //are all done

				showscores($questions,$attempts,$testsettings);
				endtest($testsettings);
				if (!$isdiag) {
					echo "<p><A HREf=\"../course/course.php?cid={$testsettings['courseid']}\">Return to Course Page</a></p>\n";
				} else {
					echo "<p><a href=\"../diag/index.php?id=$diagid\">Return to Diagnostics Page</a></p>\n";
				}
			} else { //show more test 
				echo filter("<div id=intro class=hidden>{$testsettings['intro']}</div>\n");
				
				echo "<form method=post action=\"showtest.php?action=seq&score=$toshow\" onsubmit=\"return doonsubmit(this,false,true)\">\n";
				echo "<input type=hidden name=\"verattempts\" value=\"{$attempts[$toshow]}\" />";
				
				for ($i = 0; $i < count($questions); $i++) {
					
					$reattemptsremain = canimprove($i);
					$qavail = false;
					if ($i==$toshow) {
						if (unans($scores[$i]) && $attempts[$i]==0) {
							echo "<img src=\"$imasroot/img/q_fullbox.gif\"/> ";
						} else {
							echo "<img src=\"$imasroot/img/q_halfbox.gif\"/> ";
						}
						echo "<span class=current><a name=\"curq\">Question</a> ".($i+1).".</span>  ";
					} else {
						if (unans($scores[$i]) && $attempts[$i]==0) {
							echo "<img src=\"$imasroot/img/q_fullbox.gif\"/> ";
							echo "<a href=\"showtest.php?action=seq&to=$i#curq\">Question ". ($i+1) . "</a>.  ";
							$qavail = true;
						} else if (($attempts[$i]<$qi[$questions[$i]]['attempts'] || $qi[$questions[$i]]['attempts']==0) && $reattemptsremain) {
							echo "<img src=\"$imasroot/img/q_halfbox.gif\"/> ";
							echo "<a href=\"showtest.php?action=seq&to=$i#curq\">Question ". ($i+1) . "</a>.  ";
							$qavail = true;
						} else {
							echo "<img src=\"$imasroot/img/q_emptybox.gif\"/> ";
							echo "Question ". ($i+1) . ".  ";
						}
					}
					if ($showeachscore) {
						$pts = getpts($bestscores[$i]);
						if ($pts<0) { $pts = 0;}
						echo "Points: $pts out of " . $qi[$questions[$i]]['points'] . " possible";
					} else {
						echo "Points possible: ". $qi[$questions[$i]]['points'];
					}
					
					
					if ($qi[$questions[$i]]['attempts']==0) {
						echo ".  Unlimited attempts";
					} else {
						echo '.  '.($qi[$questions[$i]]['attempts']-$attempts[$i])." attempts of ".$qi[$questions[$i]]['attempts']." remaining.";
					}
					if ($testsettings['showcat']>0 && $qi[$questions[$i]]['category']!='0') {
						echo "  Category: {$qi[$questions[$i]]['category']}.";
					}
					
					if ($i==$toshow) {
						basicshowq($i,false);
					} else if ($qavail) {
						echo "<div class=todoquestion>";
						basicshowq($i,true);
						echo "</div>";
					} else {
						basicshowq($i,true);
					}
					
					if ($i==$toshow) {
						echo "<div><input type=submit class=btn value=\"Submit Question ".($i+1)."\"></div><p></p>\n";
					}
					echo "<hr/>";
				}
				
			}
		}
	} else { //starting test display  
		$canimprove = false;
		$ptsearned = 0;
		$perfectscore = false;
		
		for ($j=0; $j<count($questions);$j++) {
			$canimproveq[$j] = canimprove($j);
			if ($canimproveq[$j]) {
				$canimprove = true;
			}
			$ptsearned += getpts($scores[$j]);
		}
		$testsettings['intro'] .= "<p>Total Points Possible: " . totalpointspossible($qi) . "</p>";
		if ($testsettings['isgroup']>0) {
			$testsettings['intro'] .= "<p><span style=\"color:red;\">This is a group assessment.  Any changes effect all group members.</span><br/>";
			if (!$isteacher) {
				$testsettings['intro'] .= "Group Members: <ul>";
				$query = "SELECT imas_users.id,imas_users.FirstName,imas_users.LastName FROM imas_users,imas_assessment_sessions WHERE ";
				$query .= "imas_users.id=imas_assessment_sessions.userid AND imas_assessment_sessions.agroupid='{$sessiondata['groupid']}' ORDER BY imas_users.LastName,imas_users.FirstName";
				$result = mysql_query($query) or die("Query failed : $query;  " . mysql_error());
				while ($row = mysql_fetch_row($result)) {
					$curgrp[] = $row[0];
					$testsettings['intro'] .= "<li>{$row[2]}, {$row[1]}</li>";
				}
				$testsettings['intro'] .= "</ul>";
			
				if ($testsettings['isgroup']==1 || $testsettings['isgroup']==2) {
					$testsettings['intro'] .= "<a href=\"showtest.php?addgrpmem=true\">Add Group Members</a></p>";
				} else {
					$testsettings['intro'] .= '</p>';
				}
			}
		}
		if ($ptsearned==totalpointspossible($qi)) {
			$perfectscore = true; 
		} 
		if ($testsettings['displaymethod'] == "AllAtOnce") {
			echo filter("<div class=intro>{$testsettings['intro']}</div>\n");
			echo "<form method=post action=\"showtest.php?action=scoreall\" onsubmit=\"return doonsubmit(this,true)\">\n";
			$numdisplayed = 0;
			for ($i = 0; $i < count($questions); $i++) {
				if (unans($scores[$i])) {
					basicshowq($i);
					showqinfobar($i,true,false);
					$numdisplayed++;
				}
			}
			if ($numdisplayed > 0) {
				echo "<BR><input type=submit class=btn value=Submit>\n";
				echo "<input type=submit class=btn name=\"saveforlater\" value=\"Save answers\">\n";
			} else {
				if ($canimprove) {
					if ($noindivscores) {
						echo "<p><a href=\"showtest.php?reattempt=all\">Reattempt test</a> on questions allowed (note: all scores, correct and incorrect, will be cleared)</p>";
					} else {
						echo "<p><a href=\"showtest.php?reattempt=all\">Reattempt test</a> on questions missed where allowed</p>";
					}
					if ($allowregen) {
						echo "<p><a href=\"showtest.php?regenall=missed\">Try similar problems</a> for all questions with less than perfect scores.</p>";
					}
				} else {
					if ($perfectscore) {
						echo "<p>Assessment is complete with perfect score.</p>";
						if ($allowregen) {
							echo "<p><a href=\"showtest.php?regenall=all\">Try similar problems</a> for all questions.</p>";
						}
					} else if ($canimprove) { //no more attempts
						if ($allowregen) {
							echo "<p>No attempts left on current versions of questions.</p>\n";
							echo "<p><a href=\"showtest.php?regenall=missed\">Try similar problems</a> for all questions with less than perfect scores.</p>";
						} else {
							echo "<p>No attempts left on this test</p>\n";
						}
					} else { //more attempts, but can't be improved.
						if ($allowregen) {
							echo "<p>Assessment cannot be improved with current versions of questions.</p>\n";
							echo "<p><a href=\"showtest.php?regenall=missed\">Try similar problems</a> for all questions with less than perfect scores.</p>";
						} else {
							echo "<p>Assessment is complete, and cannot be improved with reattempts.</p>\n";
						}
					}
					if (!$isdiag) {
						echo "<a href=\"../course/course.php?cid={$testsettings['courseid']}\">Return to Course Page</a>\n";
					} else {
						echo "<a href=\"../diag/index.php?id=$diagid\">Return to Diagnostics Page</a>\n";
					}
				}
			}
		} else if ($testsettings['displaymethod'] == "OneByOne") {
			for ($i = 0; $i<count($questions);$i++) {
				if (unans($scores[$i])) {
					break;
				}
			}
			if ($i == count($questions)) {
				if ($canimprove) {
					if ($noindivscores) {
						echo "<p><a href=\"showtest.php?reattempt=all\">Reattempt test</a> on questions allowed (note: all scores, correct and incorrect, will be cleared)</p>";
					} else {
						echo "<p><a href=\"showtest.php?reattempt=all\">Reattempt test</a> on questions missed where allowed</p>";
					}
					if ($allowregen) {
						echo "<p><a href=\"showtest.php?regenall=missed\">Try similar problems</a> for all questions with less than perfect scores.</p>";
					}
				} else {
					if ($perfectscore) {
						echo "<p>Assessment is complete with perfect score.</p>";
						if ($allowregen) {
							echo "<p><a href=\"showtest.php?regenall=all\">Try similar problems</a> for all questions.</p>";
						}
					} else if ($canimprove) { //no more attempts
						echo "<p>No attempts left on this test</p>\n";	
					}  else { //more attempts, but can't be improved
						if ($allowregen) {
							echo "<p>Assessment cannot be improved with current versions of questions.</p>\n";
							echo "<p><a href=\"showtest.php?regenall=missed\">Try similar problems</a> for all questions with less than perfect scores.</p>";
						} else {
							echo "<p>Assessment is complete, and cannot be improved with reattempts.</p>\n";
						}
					}
					if (!$isdiag) {
						echo "<a href=\"../course/course.php?cid={$testsettings['courseid']}\">Return to Course Page</a>\n";
					} else {
						echo "<a href=\"../diag/index.php?id=$diagid\">Return to Diagnostics Page</a>\n";
					}
				}
			} else {
				echo filter("<div class=intro>{$testsettings['intro']}</div>\n");
				echo "<form method=post action=\"showtest.php?action=shownext&score=$i\" onsubmit=\"return doonsubmit(this)\">\n";
				basicshowq($i);
				showqinfobar($i,true,true);
				echo "<input type=submit class=btn value=Next>\n";
			}
		} else if ($testsettings['displaymethod'] == "SkipAround") {
			echo filter("<div class=intro>{$testsettings['intro']}</div>\n");
			
			for ($i = 0; $i<count($questions);$i++) {
				if (unans($scores[$i])) {
					break;
				}
			}
			shownavbar($questions,$scores,$i,$testsettings['showcat']);
			if ($i == count($questions)) {
				if ($canimprove) {
					echo "<div class=inset><br>\n";
					echo "<a name=\"beginquestions\"></a>\n";
					if ($noindivscores) {
						echo "<p><a href=\"showtest.php?reattempt=all\">Reattempt test</a> on questions allowed (note: all scores, correct and incorrect, will be cleared)</p>";
					} else {
						echo "<p><a href=\"showtest.php?reattempt=all\">Reattempt test</a> on questions missed where allowed</p>";
					}
					if ($allowregen) {
						echo "<p>To try a similar problem, select a question</p>";	
					}
					echo "</div>\n";
				} else {
					echo "<div class=inset>";
					echo "<a name=\"beginquestions\"></a>\n";
					
					if ($perfectscore) {
						echo "<p>Assessment is complete with perfect score.</p>";
						echo "<p>To try a similar problem, select a question.</p>";
					} else if ($canimprove) { //no more attempts
						if ($allowregen) {
							echo "<p>No attempts left on current versions of questions.</p>\n";
							echo "<p>To try a similar problem, select a question.</p>";
						} else {
							echo "<p>No attempts left on this test.</p>\n";
						}
					} else { //more attempts, but cannot be improved
						if ($allowregen) {
							echo "<p>Assessment cannot be improved with current versions of questions.</p>\n";
							echo "<p>To try a similar problem, select a question.</p>";
						} else {
							echo "<p>Assessment is complete, and cannot be improved with reattempts.</p>\n";
						}
					}
					
					if (!$isdiag) {
						echo "<a href=\"../course/course.php?cid={$testsettings['courseid']}\">Return to Course Page</a></div>\n";
					} else {
						echo "<a href=\"../diag/index.php?id=$diagid\">Return to Diagnostics Page</a></div>\n";
					}
				}
			} else {
				echo "<form method=post action=\"showtest.php?action=skip&score=$i\" onsubmit=\"return doonsubmit(this)\">\n";
				echo "<div class=inset>\n";
				echo "<a name=\"beginquestions\"></a>\n";
				basicshowq($i);
				showqinfobar($i,true,true);
				echo "<input type=submit class=btn value=Submit>\n";
				echo "</div>\n";
				echo "</form>\n";
			}
		} else if ($testsettings['displaymethod'] == "Seq") {
			for ($i = 0; $i<count($questions);$i++) {
				if ($canimproveq[$i]) {
					break;
				}
			}
			if ($i == count($questions)) {
				if ($canimprove) {
					if ($noindivscores) {
						echo "<p><a href=\"showtest.php?reattempt=all\">Reattempt test</a> on questions allowed (note: all scores, correct and incorrect, will be cleared)</p>";
					} else {
						echo "<p><a href=\"showtest.php?reattempt=all\">Reattempt test</a> on questions missed where allowed</p>";
					}
					if ($allowregen) {
						echo "<p><a href=\"showtest.php?regenall=missed\">Try similar problems</a> for all questions with less than perfect scores.</p>";
					}
				} else {
					if ($perfectscore) {
						echo "<p>Assessment is complete with perfect score.</p>";
						if ($allowregen) {
							echo "<p><a href=\"showtest.php?regenall=all\">Try similar problems</a> for all questions.</p>";
						}
					} else if ($canimprove) { //no more attempts
						echo "<p>No attempts left on this test</p>\n";	
					}  else { //more attempts, but can't be improved
						if ($allowregen) {
							echo "<p>Assessment cannot be improved with current versions of questions.</p>\n";
							echo "<p><a href=\"showtest.php?regenall=missed\">Try similar problems</a> for all questions with less than perfect scores.</p>";
						} else {
							echo "<p>Assessment is complete, and cannot be improved with reattempts.</p>\n";
						}
					}
					if (!$isdiag) {
						echo "<a href=\"../course/course.php?cid={$testsettings['courseid']}\">Return to Course Page</a>\n";
					} else {
						echo "<a href=\"../diag/index.php?id=$diagid\">Return to Diagnostics Page</a>\n";
					}
				}
			} else {
				$curq = $i;
				echo filter("<div class=intro>{$testsettings['intro']}</div>\n");
				echo "<form method=post action=\"showtest.php?action=seq&score=$i\" onsubmit=\"return doonsubmit(this,false,true)\">\n";
				echo "<input type=hidden name=\"verattempts\" value=\"{$attempts[$i]}\" />";
				for ($i = 0; $i < count($questions); $i++) {
					
					$qavail = false;
					if ($i==$curq) {
						if (unans($scores[$i]) && $attempts[$i]==0) {
							echo "<img src=\"$imasroot/img/q_fullbox.gif\"/> ";
						} else {
							echo "<img src=\"$imasroot/img/q_halfbox.gif\"/> ";
						}
						echo "<span class=current>Question ".($i+1).".</span>  ";
					} else {
						if (unans($scores[$i]) && $attempts[$i]==0) {
							echo "<img src=\"$imasroot/img/q_fullbox.gif\"/> ";
							echo "<a href=\"showtest.php?action=seq&to=$i#curq\">Question ". ($i+1) . "</a>.  ";
							$qavail = true;
						} else if (($attempts[$i]<$qi[$questions[$i]]['attempts'] || $qi[$questions[$i]]['attempts']==0) && $canimproveq[$i]) {
							echo "<img src=\"$imasroot/img/q_halfbox.gif\"/> ";
							echo "<a href=\"showtest.php?action=seq&to=$i#curq\">Question ". ($i+1) . "</a>.  ";
							$qavail = true;
						} else {
							echo "<img src=\"$imasroot/img/q_emptybox.gif\"/> ";
							echo "Question ". ($i+1) . ".  ";
						}
					}
					if ($showeachscore) {
						$pts = getpts($bestscores[$i]);
						if ($pts<0) { $pts = 0;}
						echo "Points: $pts out of " . $qi[$questions[$i]]['points'] . " possible";
					} else {
						echo "Points possible: ". $qi[$questions[$i]]['points'];
					}
					if ($qi[$questions[$i]]['attempts']==0) {
						echo ".  Unlimited attempts";
					} else {
						echo '.  '.($qi[$questions[$i]]['attempts']-$attempts[$i])." attempts of ".$qi[$questions[$i]]['attempts']." remaining.";
					}
					if ($testsettings['showcat']>0 && $qi[$questions[$i]]['category']!='0') {
						echo "  Category: {$qi[$questions[$i]]['category']}.";
					}
					
					if ($i==$curq) {
						basicshowq($i,false);
					} else if ($qavail) {
						echo "<div class=todoquestion>";
						basicshowq($i,true);
						echo "</div>";
					} else {
						basicshowq($i,true);
					}
					if ($i==$curq) {
						echo "<div><input type=submit class=btn value=\"Submit Question ".($i+1)."\"></div><p></p>\n";
					}
					echo "<hr/>";
				}
			}
		}
	}
	require("../footer.php");
	
	function shownavbar($questions,$scores,$current,$showcat) {
		global $imasroot,$isdiag,$testsettings,$attempts,$qi;
		$todo = 0;
		
		echo "<a href=\"#beginquestions\"><img class=skipnav src=\"$imasroot/img/blank.gif\" alt=\"Skip Navigation\" /></a>\n";
		echo "<div class=navbar>";
		echo "<h4>Questions</h4>\n";
		echo "<ul class=qlist>\n";
		for ($i = 0; $i < count($questions); $i++) {
			echo "<li>";
			if ($current == $i) { echo "<span class=current>";}
			if (unans($scores[$i])) {
				$todo++;
			}
			if (unans($scores[$i]) && $attempts[$i]==0) {
				echo "<img src=\"$imasroot/img/q_fullbox.gif\"/>";
			} else if (canimprove($i)) {
				echo "<img src=\"$imasroot/img/q_halfbox.gif\"/>";
			} else {
				echo "<img src=\"$imasroot/img/q_emptybox.gif\"/>";
			}
			
			if ($showcat>1 && $qi[$questions[$i]]['category']!='0') {
				echo "<a href=\"showtest.php?action=skip&to=$i\">". ($i+1) . ") {$qi[$questions[$i]]['category']}</a>";
			} else {
				echo "<a href=\"showtest.php?action=skip&to=$i\">Question ". ($i+1) . "</a>";
			}
			
			if ($current == $i) { echo "</span>";}
			
			echo "</li>\n";
		}
		echo "</ul>";
		if (!$isdiag) {
			echo "<p><a href=\"#\" onclick=\"window.open('$imasroot/assessment/printtest.php','printver','width=400,height=300,menubar=1,scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420))\">Print Version</a></p> ";
		}

		echo "</div>\n";
		return $todo;
	}
	
	function showscores($questions,$attempts,$testsettings) {
		global $isdiag,$allowregen,$isreview,$noindivscores,$scores,$bestscores,$qi;
		if ($isdiag) {
			global $userid;
			$query = "SELECT * from imas_users WHERE id='$userid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$userinfo = mysql_fetch_array($result, MYSQL_ASSOC);
			echo "<h3>{$userinfo['LastName']}, {$userinfo['FirstName']}: ";
			echo substr($userinfo['SID'],0,strpos($userinfo['SID'],'d'));
			echo "</h3>\n";
		}
		
		echo "<h3>Scores:</h3>\n";
		
		if (!$noindivscores) {
			echo "<table class=scores>";
			for ($i=0;$i < count($scores);$i++) {
				echo "<tr><td>";
				if ($bestscores[$i] == -1) {
					$bestscores[$i] = 0;
				}
				if ($scores[$i] == -1) {
					$scores[$i] = 0;
					echo 'Question '. ($i+1) . ': </td><td>';
					if ($isreview || $allowregen) {
						echo "Last attempt: ";
					}
					echo "Not answered";
					echo "</td>";
					if ($isreview || $allowregen) {
						echo "<td>  Score in gradebook: ";
						echo printscore($bestscores[$i],$qi[$questions[$i]]['points']);
						echo "</td>";
					}
					echo "</tr>\n";
				} else {
					echo 'Question '. ($i+1) . ': </td><td>';
					if ($isreview || $allowregen) {
						echo "Last attempt: ";
					}
					echo printscore($scores[$i],$qi[$questions[$i]]['points']);
					echo "</td>";
					if ($isreview || $allowregen) {
						echo "<td>  Score in Gradebook: ";
						echo printscore($bestscores[$i],$qi[$questions[$i]]['points']);
						echo "</td>";
					}
					echo "</tr>\n";
				}
			}
			echo "</table>";
		}
		global $testid;
		
		recordtestdata();
			
		if ($testsettings['testtype']!="NoScores") {
			$total = 0;
			$lastattempttotal = 0;
			for ($i =0; $i < count($bestscores);$i++) {
				if (getpts($bestscores[$i])>0) { $total += getpts($bestscores[$i]);}
				if (getpts($scores[$i])>0) { $lastattempttotal += getpts($scores[$i]);}
			}
			$totpossible = totalpointspossible($qi);
			
			if ($allowregen || $isreview) {
				echo "<p>Total Points on Last Attempts:  $lastattempttotal out of $totpossible possible</p>\n";
			}
			
			if ($total<$testsettings['minscore']) {
				echo "<p><b>Total Points Earned:  $total out of $totpossible possible: ";	
			} else {
				echo "<p><b>Total Points in Gradebook: $total out of $totpossible possible: ";
			}
			
			$average = round(100*((float)$total)/((float)$totpossible),1);
			echo "$average % </b></p>\n";	
			
			if ($total<$testsettings['minscore']) {
				echo "<p><span style=\"color:red;\"><b>A score of {$testsettings['minscore']} is required to receive credit for this assessment<br/>Grade in Gradebook: No Credit (NC)</span></p> ";	
			}
		} else {
			echo "<p><b>Your scores have been recorded for this assessment.</b></p>";
		}
		
		//if timelimit is exceeded
		$now = time();
		if (($testsettings['timelimit']>0) && (($now-$GLOBALS['starttime']) > $testsettings['timelimit'])) {
			$over = $now-$GLOBALS['starttime'] - $testsettings['timelimit'];
			echo "<p>Time limit exceeded by ";
			if ($over > 60) {
				$overmin = floor($over/60);
				echo "$overmin minutes, ";
				$over = $over - $overmin*60;
			}
			echo "$over seconds.<BR>\n";
			echo "Grade is subject to acceptance by the instructor</p>\n";
		}
		
		
		if ($total < $totpossible) {
			if (canimproveany()) {
				if ($noindivscores) {
					echo "<p><a href=\"showtest.php?reattempt=all\">Reattempt test</a> on questions allowed (note: where reattempts are allowed, all scores, correct and incorrect, will be cleared)</p>";
				} else {
					echo "<p><a href=\"showtest.php?reattempt=all\">Reattempt test</a> on questions missed where allowed</p>";
				}
			}
			if ($allowregen) {
				echo "<p><a href=\"showtest.php?regenall=missed\">Try similar problems</a> for all questions with less than perfect scores.</p>";
				echo "<p><a href=\"showtest.php?regenall=all\">Try similar problems</a> for all questions.</p>";
			}
		}
		if ($testsettings['testtype']!="NoScores") {
			$hascatset = false;
			foreach($qi as $qii) {
				if ($qii['category']!='0') {
					$hascatset = true;
					break;
				}
			}
			if ($hascatset) {
				include("../assessment/catscores.php");
				catscores($questions,$bestscores,$testsettings['defpoints']);
			}
		}
			
		
	}

	function endtest($testsettings) {
		
		//unset($sessiondata['sessiontestid']);
	}

?>

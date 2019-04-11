<?php

//util function for unenrolling students
//$cid = courseid
//$tounenroll = array of userids
//$delforum = delete all forum posts
//$deloffline = delete offline items from gradebook
//$unwithdraw = unset any withdrawn questions
//$delwikirev = delete wiki revisions, 1: all, 2: group wikis only
function unenrollstu($cid,$tounenroll,$delforum=false,$deloffline=false,$withwithdraw=false,$delwikirev=false,$usereplaceby=false) {
	global $DBH, $userid;
	$cid = intval($cid);
	$stulist = implode(',', array_map('intval', $tounenroll));
	$forums = array();
	$threads = array();
	$stm2 = $DBH->prepare("SELECT threadid FROM imas_forum_posts WHERE forumid=:forumid");

	$stm = $DBH->query("SELECT id FROM imas_forums WHERE courseid=$cid"); //sanitized above
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$forums[] = $row[0];
		$stm2->execute(array(':forumid'=>$row[0]));
		while ($rw2 = $stm2->fetch(PDO::FETCH_NUM)) {
			$threads[] = $rw2[0];
		}
	}
	$threadlist = implode(',',$threads);
	$forumlist = implode(',',$forums);

	$assesses = array();
	$stm = $DBH->query("SELECT id FROM imas_assessments WHERE courseid=$cid");
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$assesses[] = $row[0];
	}
	$aidlist =  implode(',',$assesses);

	$wikis = array();
	$grpwikis = array();
	$stm = $DBH->query("SELECT id,groupsetid FROM imas_wikis WHERE courseid=$cid");
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$wikis[] = $row[0];
		if ($row[1]>0) {
			$grpwikis[] = $row[0];
		}
	}
	$wikilist =  implode(',',$wikis);
	$grpwikilist = implode(',',$grpwikis);

	$drills = array();
	$stm = $DBH->query("SELECT id FROM imas_drillassess WHERE courseid=$cid");
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$drills[] = $row[0];
	}
	$drilllist =  implode(',',$drills);

	$exttools = array();
	$stm = $DBH->query("SELECT id FROM imas_linkedtext WHERE courseid=$cid AND points>0");
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$exttools[] = $row[0];
	}
	$exttoolslist =  implode(',',$exttools);

	$stugroups = array();
	$stm = $DBH->query("SELECT imas_stugroups.id FROM imas_stugroups JOIN imas_stugroupset ON imas_stugroups.groupsetid=imas_stugroupset.id WHERE imas_stugroupset.courseid=$cid");
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$stugroups[] = $row[0];
	}
	$curdir = rtrim(dirname(__FILE__), '/\\');
	require_once("$curdir/filehandler.php");
	if ($withwithdrawn=='remove' || $usereplaceby) {
		require_once("$curdir/updateassess.php");
	}
	if (count($tounenroll)>0) {
		$gbitems = array();
		$stm = $DBH->query("SELECT id FROM imas_gbitems WHERE courseid=$cid");
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$gbitems[] = $row[0];
		}
		$gblist = implode(',',$gbitems);
		//new
		if (count($assesses)>0) {
			deleteasidfilesbyquery2('userid',$tounenroll,$assesses);
			//deleteasidfilesbyquery(array('assessmentid'=>$assesses, 'userid'=>$tounenroll));
			$query = "DELETE FROM imas_assessment_sessions WHERE assessmentid IN ($aidlist) AND userid IN ($stulist)";
			$DBH->query($query); //values already sanitized
			$query = "DELETE FROM imas_exceptions WHERE itemtype='A' AND assessmentid IN ($aidlist) AND userid IN ($stulist)";
			$DBH->query($query); //values already sanitized
		}
		if (count($drills)>0) {
			$query = "DELETE FROM imas_drillassess_sessions WHERE drillassessid IN ($drilllist) AND userid IN ($stulist)";
			$DBH->query($query); //values already sanitized
		}
		if (count($exttools)>0) {
			$query = "DELETE FROM imas_grades WHERE gradetype='exttool' AND gradetypeid IN ($exttoolslist) AND userid IN ($stulist)";
			$DBH->query($query); //values already sanitized
		}
		if (count($gbitems)>0) {
			$query = "DELETE FROM imas_grades WHERE gradetype='offline' AND gradetypeid IN ($gblist) AND userid IN ($stulist)";
			$DBH->query($query); //values already sanitized
		}
		if (count($threads)>0) {
			$query = "DELETE FROM imas_forum_views WHERE threadid IN ($threadlist)  AND userid IN ($stulist)";
			$DBH->query($query); //values already sanitized
		}
		if (count($wikis)>0) {
			$query = "DELETE FROM imas_wiki_views WHERE wikiid IN ($wikilist)  AND userid IN ($stulist)";
			$DBH->query($query); //values already sanitized
		}
		if (count($forums)>0) {
			$query = "DELETE FROM imas_grades WHERE gradetype='forum' AND gradetypeid IN ($forumlist) AND userid IN ($stulist)";
			$DBH->query($query); //values already sanitized
			$query = "DELETE FROM imas_exceptions WHERE (itemtype='F' OR itemtype='P' OR itemtype='R') AND assessmentid IN ($forumlist) AND userid IN ($stulist)";
			$DBH->query($query); //values already sanitized
		}

		if (count($stugroups)>0) {
			$stugrouplist = implode(',',$stugroups);
			$query = "DELETE FROM imas_stugroupmembers WHERE userid IN ($stulist) AND stugroupid IN ($stugrouplist)";
			$DBH->query($query); //values already sanitized
		}



	}
	if ($delforum && count($forums)>0) {
		$query = "DELETE imas_forum_threads FROM imas_forum_posts JOIN imas_forum_threads ON imas_forum_posts.threadid=imas_forum_threads.id AND imas_forum_posts.posttype=0 WHERE imas_forum_threads.forumid IN ($forumlist)";
		$DBH->query($query); //values already sanitized
		$stm = $DBH->query("SELECT id FROM imas_forum_posts WHERE forumid IN ($forumlist) AND files<>''");
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			deleteallpostfiles($row[0]);
		}

		$query = "DELETE FROM imas_forum_posts WHERE forumid IN ($forumlist) AND posttype=0";
		$DBH->query($query); //values already sanitized

		/* //old
		foreach ($forums as $fid) {
			$query = "DELETE imas_forum_threads FROM imas_forum_posts JOIN imas_forum_threads ON imas_forum_posts.threadid=imas_forum_threads.id AND imas_forum_posts.posttype=0 WHERE imas_forum_threads.forumid='$fid'";
			mysql_query($query) or die("Query failed : " . mysql_error());

			$query = "DELETE FROM imas_forum_posts WHERE forumid='$fid' AND posttype=0";
			mysql_query($query) or die("Query failed : " . mysql_error());
		}*/
	}
	if ($delwikirev===1 && count($wikis)>0) {
		$query = "DELETE FROM imas_wiki_revisions WHERE wikiid IN ($wikilist)";
		$DBH->query($query); //values already sanitized
	} else if ($delwikirev===2 && count($grpwikis)>0) {
		$query = "DELETE FROM imas_wiki_revisions WHERE wikiid IN ($grpwikilist)";
		$DBH->query($query); //values already sanitized
	}
	if ($deloffline) {
		$DBH->query("DELETE FROM imas_gbitems WHERE courseid=$cid");
	}
	if ($withwithdraw=='unwithdraw' && count($assesses)>0) {
		$query = "UPDATE imas_questions SET withdrawn=0 WHERE assessmentid IN ($aidlist)";
		$DBH->query($query); //values already sanitized
		/*foreach ($assesses as $aid) {
			$query = "UPDATE imas_questions SET withdrawn=0 WHERE assessmentid='$aid'";
			mysql_query($query) or die("Query failed : " . mysql_error());
		}*/
	}

	if ($withwithdrawn=='remove' || $usereplaceby) {
		$msg = updateassess($cid, $withwithdraw=='remove', $usereplaceby);
	}


	if (count($tounenroll)>0) {
		$query = "DELETE FROM imas_students WHERE userid IN ($stulist) AND courseid=$cid";
		$DBH->query($query); //values already sanitized

		$query = "DELETE FROM imas_login_log WHERE userid IN ($stulist) AND courseid=$cid";
		$DBH->query($query); //values already sanitized

		$query = "DELETE FROM imas_content_track WHERE userid IN ($stulist) AND courseid=$cid";
		$DBH->query($query); //values already sanitized
	}
	
	$lognote = "Unenroll in $cid run by $userid via script ".basename($_SERVER['PHP_SELF']);
	$lognote .= ". Unenrolled: $stulist";
	$stm = $DBH->prepare("INSERT INTO imas_log (time,log) VALUES (?,?)");
	$stm->execute(array(time(), $lognote));

}

?>

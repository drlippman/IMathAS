<?php

//util function for unenrolling students
//$cid = courseid
//$tounenroll = array of userids
//$delforum = delete all forum posts
//$deloffline = delete offline items from gradebook
//$unwithdraw = unset any withdrawn questions
function unenrollstu($cid,$tounenroll,$delforum=false,$deloffline=false,$unwithdraw=false) {
	$forums = array();
	$threads = array();
	$query = "SELECT id FROM imas_forums WHERE courseid='$cid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$forums[] = $row[0];
		$q2 = "SELECT threadid FROM imas_forum_posts WHERE forumid='{$row[0]}'";
		$r2 = mysql_query($q2) or die("Query failed : " . mysql_error());
		while ($rw2 = mysql_fetch_row($r2)) {
			$threads[] = $rw2[0];
		}
	}
	$threadlist = implode(',',$threads);
	$forumlist = implode(',',$forums);
	
	$assesses = array();
	$query = "SELECT id FROM imas_assessments WHERE courseid='$cid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$assesses[] = $row[0];
	}
	$aidlist =  implode(',',$assesses);
	
	$stugroups = array();
	$query = "SELECT imas_stugroups.id FROM imas_stugroups JOIN imas_stugroupset ON imas_stugroups.groupsetid=imas_stugroupset.id WHERE imas_stugroupset.courseid='$cid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$stugroups[] = $row[0];
	}
	$stugrouplist = implode(',',$stugroups);
	
	if (count($tounenroll)>0) {
		$curdir = rtrim(dirname(__FILE__), '/\\');
		require_once("$curdir/filehandler.php");
		$gbitems = array();
		$query = "SELECT id FROM imas_gbitems WHERE courseid='$cid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			$gbitems[] = $row[0];
		}
		$gblist = implode(',',$gbitems);
		//new
		$stulist = "'".implode("','",$tounenroll)."'";
		if (count($assesses)>0) {
			$query = "DELETE FROM imas_assessment_sessions WHERE assessmentid IN ($aidlist) AND userid IN ($stulist)";
			mysql_query($query) or die("Query failed : $query" . mysql_error());
			$query = "DELETE FROM imas_exceptions WHERE assessmentid IN ($aidlist) AND userid IN ($stulist)";
			mysql_query($query) or die("Query failed : $query" . mysql_error());
			deleteasidfilesbyquery(array('assessmentid'=>$assesses, 'userid'=>$tounenroll));
		}
		if (count($gbitems)>0) {
			$query = "DELETE FROM imas_grades WHERE gbitemid IN ($gblist) AND userid IN ($stulist)";
			mysql_query($query) or die("Query failed : $query" . mysql_error());
		}
		if (count($threads)>0) {
			$query = "DELETE FROM imas_forum_views WHERE threadid IN ($threadlist)  AND userid IN ($stulist)";
			mysql_query($query) or die("Query failed : $query" . mysql_error());
		}
		
		$query = "DELETE FROM imas_students WHERE userid IN ($stulist) AND courseid='$cid'";
		mysql_query($query) or die("Query failed : $query" . mysql_error());
		
		$query = "DELETE FROM imas_stugroupmembers WHERE userid IN ($stulist) AND stugroupid IN ($stugrouplist)";
		mysql_query($query) or die("Query failed : $query" . mysql_error());
		
		/*
		foreach ($tounenroll as $uid) {
			foreach ($assesses as $aid) {
				deleteasidfilesbyquery(array('assessmentid'=>$aid, 'userid'=>$uid));
			}
		}*/
				
		
		//old, replaced with above
		/*
		foreach ($tounenroll as $uid) {
			$query = "DELETE FROM imas_students WHERE userid='$uid' AND courseid='$cid'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			foreach ($assesses as $aid) {
				deleteasidfilesbyquery(array('assessmentid'=>$aid, 'userid'=>$uid));
				
				$query = "DELETE FROM imas_assessment_sessions WHERE assessmentid='$aid' AND userid='$uid'";
				mysql_query($query) or die("Query failed : " . mysql_error());
				$query = "DELETE FROM imas_exceptions WHERE assessmentid='$aid' AND userid='$uid'";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
			foreach ($gbitems as $gbid) {
				$query = "DELETE FROM imas_grades WHERE gbitemid='$gbid' AND userid='$uid'";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
			
			foreach ($threads as $tid) {
				$query = "DELETE FROM imas_forum_views WHERE threadid='$tid' AND userid='$uid'";
				mysql_query($query) or die("Query failed : " . mysql_error());
			}
		}
		*/
		
		
	}
	if ($delforum && count($forums)>0) {
		$query = "DELETE imas_forum_threads FROM imas_forum_posts JOIN imas_forum_threads ON imas_forum_posts.threadid=imas_forum_threads.id AND imas_forum_posts.posttype=0 WHERE imas_forum_threads.forumid IN ($forumlist)";
		mysql_query($query) or die("Query failed : " . mysql_error());
			
		$query = "DELETE FROM imas_forum_posts WHERE forumid IN ($forumlist) AND posttype=0";
		mysql_query($query) or die("Query failed : " . mysql_error());	
		/* //old
		foreach ($forums as $fid) {
			$query = "DELETE imas_forum_threads FROM imas_forum_posts JOIN imas_forum_threads ON imas_forum_posts.threadid=imas_forum_threads.id AND imas_forum_posts.posttype=0 WHERE imas_forum_threads.forumid='$fid'";
			mysql_query($query) or die("Query failed : " . mysql_error());
			
			$query = "DELETE FROM imas_forum_posts WHERE forumid='$fid' AND posttype=0";
			mysql_query($query) or die("Query failed : " . mysql_error());	
		}*/
	}
	if ($deloffline) {
		$query = "DELETE FROM imas_gbitems WHERE courseid='$cid'";
		mysql_query($query) or die("Query failed : " . mysql_error());
	}
	if ($unwithdraw && count($assesses)>0) {
		$query = "UPDATE imas_questions SET withdrawn=0 WHERE assessmentid IN ($aidlist)";
		mysql_query($query) or die("Query failed : " . mysql_error());
		/*foreach ($assesses as $aid) {
			$query = "UPDATE imas_questions SET withdrawn=0 WHERE assessmentid='$aid'";
			mysql_query($query) or die("Query failed : " . mysql_error());
		}*/
	}
		 
}

?>

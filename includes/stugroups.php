<?php

function deletegroupset($grpsetid) {
	$query = "SELECT id FROM imas_stugroups WHERE groupsetid='$grpsetid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		deletegroup($row[0]);
	}
	$query = "DELETE FROM imas_stugroupset WHERE id='$grpsetid'";
	mysql_query($query) or die("Query failed : " . mysql_error());
	
	$query = "UPDATE imas_assessments SET isgroup=0,groupsetid=0 WHERE groupsetid='$grpsetid'";
	mysql_query($query) or die("Query failed : " . mysql_error());
	
	$query = "UPDATE imas_forums SET groupsetid=0 WHERE groupsetid='$grpsetid'";
	mysql_query($query) or die("Query failed : " . mysql_error());
	
	$query = "UPDATE imas_wikis SET groupsetid=0 WHERE groupsetid='$grpsetid'";
	mysql_query($query) or die("Query failed : " . mysql_error());

}

function deletegroup($grpid,$delposts=true) {
	removeallgroupmembers($grpid);
	
	if ($delposts) {
		$query = "SELECT id FROM imas_forum_threads WHERE stugroupid='$grpid'";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		$todel = array();
		while ($row = mysql_fetch_row($result)) {
			$todel[] = $row[0];
		}
		if (count($todel)>0) {
			$dellist = implode(',',$todel);
			$query = "DELETE FROM imas_forum_threads WHERE id IN ($dellist)";
			mysql_query($query) or die("Query failed : " . mysql_error());
			$query = "DELETE FROM imas_forum_posts WHERE threadid IN ($dellist)";
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
	} else {
		$query = "UPDATE imas_forum_threads SET stugroupid=0 WHERE stugroupid='$grpid'";
		mysql_query($query) or die("Query failed : " . mysql_error());
	}
	$query = "DELETE FROM imas_stugroups WHERE id='$grpid'";
	mysql_query($query) or die("Query failed : " . mysql_error());
	
	$query = "DELETE FROM imas_wiki_revisions WHERE stugroupid='$grpid'";
	mysql_query($query) or die("Query failed : " . mysql_error());
}

function removeallgroupmembers($grpid) {
	$query = "DELETE FROM imas_stugroupmembers WHERE stugroupid='$grpid'";
	mysql_query($query) or die("Query failed : " . mysql_error());
	
	//$query = "SELECT assessmentid,userid FROM imas_assessment_sessions WHERE agroupid='$grpid'";
	//$result = mysql_query($query) or die("Query failed : " . mysql_error());
	
	//any assessment session using this group, set group to 0
	$query = "UPDATE imas_assessment_sessions SET agroupid=0 WHERE agroupid='$grpid'";
	mysql_query($query) or die("Query failed : " . mysql_error());
	
	$now = time();
	
	if (isset($GLOBALS['CFG']['log'])) {
		$query = "INSERT INTO imas_log (time,log) VALUES ($now,'deleting members from $grpid')";
		mysql_query($query) or die("Query failed : " . mysql_error());
	}
}

function removegroupmember($grpid, $uid) {
	$query = "DELETE FROM imas_stugroupmembers WHERE stugroupid='$grpid' AND userid='$uid'";
	mysql_query($query) or die("Query failed : " . mysql_error());
	
	//update any assessment sessions using this group
	$query = "UPDATE imas_assessment_sessions SET agroupid=0 WHERE agroupid='$grpid' AND userid='$uid'";
	mysql_query($query) or die("Query failed : " . mysql_error());
	
	$now = time();
	if (isset($GLOBALS['CFG']['log'])) {
		$query = "INSERT INTO imas_log (time,log) VALUES ($now,'deleting $uid from $grpid')";
		mysql_query($query) or die("Query failed : " . mysql_error());
	}
}

?>

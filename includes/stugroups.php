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
}

function deletegroup($grpid) {
	removeallgroupmembers($grpid);
	
	$query = "DELETE FROM imas_stugroups WHERE id='$grpid'";
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
}

function removegroupmember($grpid, $uid) {
	$query = "DELETE FROM imas_stugroupmembers WHERE stugroupid='$grpid' AND userid='$uid'";
	mysql_query($query) or die("Query failed : " . mysql_error());
	
	//update any assessment sessions using this group
	$query = "UPDATE imas_assessment_sessions SET agroupid=0 WHERE agroupid='$grpid' AND userid='$uid'";
	mysql_query($query) or die("Query failed : " . mysql_error());
}

?>

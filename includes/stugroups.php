<?php

function deletegroupset($grpsetid) {
	$query = "SELECT id FROM imas_stugroups WHERE groupsetid='$grpsetid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		deletegroup($row[0]);
	}
	$query = "DELETE FROM imas_stugroupset WHERE id='$grpsetid'";
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
/*
	$query = "SELECT sessionid,sessiondata FROM imas_sessions WHERE userid='$thisuserid'";
	$result = mysql_query($query) or die("Query failed : $query:" . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$tmpsessdata = unserialize(base64_decode($row[1]));
		if ($tmpsessdata['sessiontestid']==$_GET['asid']) {
			$tmpsessdata['sessiontestid'] = $newasid;
			$tmpsessdata['groupid'] = 0;
			$tmpsessdata = base64_encode(serialize($tmpsessdata));
			$query = "UPDATE imas_sessions SET sessiondata='$tmpsessdata' WHERE sessionid='{$row[0]}'";
			mysql_query($query) or die("Query failed : $query:" . mysql_error());
		}
	}
	
	$query = "UPDATE imas_assessment_sessions SET agroupid=0 WHERE id='{$_GET['asid']}'";
	mysql_query($query) or die("Query failed : " . mysql_error());
	*/
}

?>

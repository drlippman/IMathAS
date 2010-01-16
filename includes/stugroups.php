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
}

function removegroupmember($grpid, $uid) {
	$query = "DELETE FROM imas_stugroupmembers WHERE stugroupid='$grpid' AND userid='$uid'";
	mysql_query($query) or die("Query failed : " . mysql_error());
}

?>

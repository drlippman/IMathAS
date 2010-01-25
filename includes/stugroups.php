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
	
	$query = "SELECT assessmentid,userid FROM imas_assessment_sessions WHERE agroupid='$grpid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	$uidstocopy = array();
	while ($row = mysql_fetch_row($result)) {
		$uidstocopy[$row[0]][] = $row[1];
	}
	$aids = array_keys($uidstocopy);
	foreach ($aids as $aid) {
		foreach ($uidstocopy as $uid) {
			$query = "SELECT lastanswers,bestlastanswers,id FROM imas_assessment_sessions WHERE assessmentid='$aid' AND userid='$uid'";
			$result = mysql_query($query) or die("Query failed : " . mysql_error());
			$row = mysql_fetch_row($result);
			$asid = array_pop($row);
			$str = implode(' ',$row);
			$cnt = copyasidfilesfromstring($str,'grp'.$grpid,$uid);
			//if no files to copy, abort
			if ($cnt==0) {break;}
			$la = addslashes(str_replace("adata/grp$grpid/$aid/","adata/$asid/",$row[0]));
			$bla = addslashes(str_replace("adata/grp$grpid/$aid/","adata/$asid/",$row[1]));
			$query = "UPDATE imas_assessment_sessions SET lastanswers='$la',bestlastanswers='$bla' WHERE id='$asid'";
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
	}
	
	//any assessment session using this group, set group to 0
	$query = "UPDATE imas_assessment_sessions SET agroupid=0 WHERE agroupid='$grpid'";
	mysql_query($query) or die("Query failed : " . mysql_error());
}

function removegroupmember($grpid, $uid) {
	$query = "DELETE FROM imas_stugroupmembers WHERE stugroupid='$grpid' AND userid='$uid'";
	mysql_query($query) or die("Query failed : " . mysql_error());
	
	$query = "SELECT lastanswers,bestlastanswers,id,assessmentid FROM imas_assessment_sessions WHERE agroupid='$grpid' AND userid='$uid'";
	$result = mysql_query($query) or die("Query failed : " . mysql_error());
	while ($row = mysql_fetch_row($result)) {
		$aid = array_pop($row);
		$asid = array_pop($row);
		$str = implode(' ',$row);
		$cnt = copyasidfilesfromstring($str,'grp'.$grpid,$uid);
		if ($cnt>0) {
			$la = addslashes(str_replace("adata/grp$grpid/$aid/","adata/$asid/",$row[0]));
			$bla = addslashes(str_replace("adata/grp$grpid/$aid/","adata/$asid/",$row[1]));
			$query = "UPDATE imas_assessment_sessions SET lastanswers='$la',bestlastanswers='$bla' WHERE id='$asid'";
			mysql_query($query) or die("Query failed : " . mysql_error());
		}
	}
	//update any assessment sessions using this group
	$query = "UPDATE imas_assessment_sessions SET agroupid=0 WHERE agroupid='$grpid' AND userid='$uid'";
	mysql_query($query) or die("Query failed : " . mysql_error());

}

?>

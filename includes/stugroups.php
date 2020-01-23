<?php

function deletegroupset($grpsetid) {
	global $DBH;
	$grpsetid = intval($grpsetid);
	$query = "SELECT id FROM imas_stugroups WHERE groupsetid=$grpsetid";
	$stm = $DBH->query($query); //sanitized above - no need for prepared
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		deletegroup($row[0]);
	}
	$query = "DELETE FROM imas_stugroupset WHERE id=$grpsetid";
	$stm = $DBH->query($query); //sanitized above - no need for prepared

	$query = "UPDATE imas_assessments SET isgroup=0,groupsetid=0 WHERE groupsetid=$grpsetid";
	$stm = $DBH->query($query); //sanitized above - no need for prepared

	$query = "UPDATE imas_forums SET groupsetid=0 WHERE groupsetid=$grpsetid";
	$stm = $DBH->query($query); //sanitized above - no need for prepared

	$query = "UPDATE imas_wikis SET groupsetid=0 WHERE groupsetid=$grpsetid";
	$stm = $DBH->query($query); //sanitized above - no need for prepared

}

function deletegroup($grpid,$delposts=true) {
	global $DBH;
	$grpid = Sanitize::onlyInt($grpid);
	removeallgroupmembers($grpid);

	if ($delposts) {
		$stm = $DBH->query("SELECT id FROM imas_forum_threads WHERE stugroupid=$grpid"); //sanitized above - no need for prepared
		$todel = array();
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$todel[] = $row[0];
		}
		if (count($todel)>0) {
			$dellist = implode(',',$todel);  //known to be safe INTs
			$query = "DELETE FROM imas_forum_threads WHERE id IN ($dellist)";
			$stm = $DBH->query($query); //sanitized above - no need for prepared
			$query = "DELETE FROM imas_forum_posts WHERE threadid IN ($dellist)";
			$stm = $DBH->query($query); //sanitized above - no need for prepared
		}
	} else {
		$query = "UPDATE imas_forum_threads SET stugroupid=0 WHERE stugroupid=$grpid";
		$stm = $DBH->query($query); //sanitized above - no need for prepared
	}
	$query = "DELETE FROM imas_stugroups WHERE id=$grpid";
	$stm = $DBH->query($query); //sanitized above - no need for prepared

	$query = "DELETE FROM imas_wiki_revisions WHERE stugroupid=$grpid";
	$stm = $DBH->query($query); //sanitized above - no need for prepared
}

function removeallgroupmembers($grpid) {
	global $DBH;
	$grpid = intval($grpid);
	$query = "DELETE FROM imas_stugroupmembers WHERE stugroupid=$grpid";
	$stm = $DBH->query($query); //sanitized above - no need for prepared

	//$query = "SELECT assessmentid,userid FROM imas_assessment_sessions WHERE agroupid=$grpid";
	//$result = mysql_query($query) or die("Query failed : " . mysql_error());

	//any assessment session using this group, set group to 0
	$query = "UPDATE imas_assessment_sessions SET agroupid=0 WHERE agroupid=$grpid";
	$stm = $DBH->query($query); //sanitized above - no need for prepared

	$stm = $DBH->prepare("UPDATE imas_assessment_records SET agroupid=0 WHERE agroupid=?");
	$stm->execute(array($grpid));

	$now = time();

	if (isset($GLOBALS['CFG']['log'])) {
		$query = "INSERT INTO imas_log (time,log) VALUES ($now,'deleting members from $grpid')";
		$stm = $DBH->query($query); //sanitized above - no need for prepared
	}
}

function removegroupmember($grpid, $uid) {
	global $DBH;
	$grpid = intval($grpid);
	$uid = intval($uid);
	$query = "DELETE FROM imas_stugroupmembers WHERE stugroupid=$grpid AND userid=$uid";
	$stm = $DBH->query($query); //sanitized above - no need for prepared

	//update any assessment sessions using this group
	$query = "UPDATE imas_assessment_sessions SET agroupid=0 WHERE agroupid=$grpid AND userid=$uid";
	$stm = $DBH->query($query); //sanitized above - no need for prepared

	$stm = $DBH->prepare("UPDATE imas_assessment_records SET agroupid=0 WHERE agroupid=? AND userid=?");
	$stm->execute(array($grpid, $uid));

	$now = time();
	if (isset($GLOBALS['CFG']['log'])) {
		$query = "INSERT INTO imas_log (time,log) VALUES ($now,'deleting $uid from $grpid')";
		$stm = $DBH->query($query); //sanitized above - no need for prepared
	}
}

?>

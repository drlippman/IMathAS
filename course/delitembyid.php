<?php
require_once("../includes/filehandler.php");

function delitembyid($itemid) {
	global $DBH, $cid;
	$stm = $DBH->prepare("SELECT itemtype,typeid FROM imas_items WHERE id=:id");
	$stm->execute(array(':id'=>$itemid));
	list($itemtype,$typeid) = $stm->fetch(PDO::FETCH_NUM);
	$typeid = Sanitize::simpleString($typeid);

	if ($itemtype == "InlineText") {
		$stm = $DBH->prepare("DELETE FROM imas_inlinetext WHERE id=:id");
		$stm->execute(array(':id'=>$typeid));
		$stm = $DBH->prepare("SELECT filename FROM imas_instr_files WHERE itemid=:itemid");
		$stm->execute(array(':itemid'=>$typeid));
		//$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/files/';
		$file_src = $DBH->prepare("SELECT id FROM imas_instr_files WHERE filename=:filename");
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			if (substr($row[0],0,4)!='http' && strpos($row[0], $cid.'/') === 0) {
				$file_src->execute(array(':filename'=>$row[0]));
				if ($file_src->rowCount()==1) {
					//unlink($uploaddir . $row[0]);
					deletecoursefile($row[0]);
				}
			}
		}
		$stm = $DBH->prepare("DELETE FROM imas_instr_files WHERE itemid=:itemid");
		$stm->execute(array(':itemid'=>$typeid));


	} else if ($itemtype == "LinkedText") {
		$stm = $DBH->prepare("SELECT text,points FROM imas_linkedtext WHERE id=:id");
		$stm->execute(array(':id'=>$typeid));
		list($text,$points) = $stm->fetch(PDO::FETCH_NUM);
		if (strpos($text, 'file:'.$cid.'/') === 0) { //delete file if not used
			$stm = $DBH->prepare("SELECT id FROM imas_linkedtext WHERE text=:text");
			$stm->execute(array(':text'=>$text));
			if ($stm->rowCount()==1) {
				//$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/files/';
				$filename = substr($text,5);
				//unlink($uploaddir . $filename);
				deletecoursefile($filename);
			}
		} 
		if ($points>0) {
			$stm = $DBH->prepare("DELETE FROM imas_grades WHERE gradetypeid=:gradetypeid AND gradetype='exttool'");
			$stm->execute(array(':gradetypeid'=>$typeid));
		}
		$stm = $DBH->prepare("DELETE FROM imas_linkedtext WHERE id=:id");
		$stm->execute(array(':id'=>$typeid));
	} else if ($itemtype == "Forum") {
		$stm = $DBH->prepare("DELETE FROM imas_forums WHERE id=:id");
		$stm->execute(array(':id'=>$typeid));
		$stm = $DBH->prepare("SELECT id FROM imas_forum_posts WHERE forumid=:forumid AND files<>''");
		$stm->execute(array(':forumid'=>$typeid));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			deleteallpostfiles($row[0]);
		}
		$stm = $DBH->prepare("DELETE FROM imas_forum_subscriptions WHERE forumid=:forumid");
		$stm->execute(array(':forumid'=>$typeid));

		$stm = $DBH->prepare("DELETE FROM imas_exceptions WHERE assessmentid=:forumid AND (itemtype='F' OR itemtype='P' OR itemtype='R')");
		$stm->execute(array(':forumid'=>$typeid));


		//$query = "DELETE FROM imas_forum_views WHERE threadid IN (SELECT id FROM imas_forum_threads WHERE forumid='$typeid')";
		$query = "DELETE imas_forum_views FROM imas_forum_views JOIN imas_forum_threads ";
		$query .= "ON imas_forum_views.threadid=imas_forum_threads.id  WHERE imas_forum_threads.forumid=:forumid";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':forumid'=>$typeid));
		$stm = $DBH->prepare("DELETE FROM imas_forum_posts WHERE forumid=:forumid");
		$stm->execute(array(':forumid'=>$typeid));
		$stm = $DBH->prepare("DELETE FROM imas_forum_threads WHERE forumid=:forumid");
		$stm->execute(array(':forumid'=>$typeid));

		$stm = $DBH->prepare("DELETE FROM imas_grades WHERE gradetype='forum' AND gradetypeid=:forumid");
		$stm->execute(array(':forumid'=>$typeid));

	} else if ($itemtype == "Assessment") {

		deleteallaidfiles($typeid);
		$stm = $DBH->prepare("DELETE FROM imas_assessment_sessions WHERE assessmentid=:assessmentid");
		$stm->execute(array(':assessmentid'=>$typeid));
		$stm = $DBH->prepare("DELETE FROM imas_assessment_records WHERE assessmentid=:assessmentid");
		$stm->execute(array(':assessmentid'=>$typeid));

		$stm = $DBH->prepare("DELETE FROM imas_exceptions WHERE assessmentid=:assessmentid AND itemtype='A'");
		$stm->execute(array(':assessmentid'=>$typeid));
		$stm = $DBH->prepare("DELETE FROM imas_questions WHERE assessmentid=:assessmentid");
		$stm->execute(array(':assessmentid'=>$typeid));
		$stm = $DBH->prepare("DELETE FROM imas_assessments WHERE id=:id");
		$stm->execute(array(':id'=>$typeid));
		$stm = $DBH->prepare("DELETE FROM imas_livepoll_status WHERE assessmentid=:assessmentid");
		$stm->execute(array(':assessmentid'=>$typeid));

		$stm = $DBH->prepare("UPDATE imas_assessments SET reqscoreaid=0 WHERE reqscoreaid=:assessmentid AND courseid=:courseid");
		$stm->execute(array(':assessmentid'=>$typeid, ':courseid'=>$cid));

	} else if ($itemtype == "Drill") {
		$stm = $DBH->prepare("DELETE FROM imas_drillassess_sessions WHERE drillassessid=:drillassessid");
		$stm->execute(array(':drillassessid'=>$typeid));
		$stm = $DBH->prepare("DELETE FROM imas_drillassess WHERE id=:id");
		$stm->execute(array(':id'=>$typeid));
	} else if ($itemtype == 'Wiki') {
		$stm = $DBH->prepare("DELETE FROM imas_wikis WHERE id=:id");
		$stm->execute(array(':id'=>$typeid));
		$stm = $DBH->prepare("DELETE FROM imas_wiki_revisions WHERE wikiid=:wikiid");
		$stm->execute(array(':wikiid'=>$typeid));
		$stm = $DBH->prepare("DELETE FROM imas_wiki_views WHERE wikiid=:wikiid");
		$stm->execute(array(':wikiid'=>$typeid));

	}
	$stm = $DBH->prepare("DELETE FROM imas_items WHERE id=:id");
	$stm->execute(array(':id'=>$itemid));

}

function delrecurse($itemarr) { //delete items, recursing through blocks as needed
	foreach($itemarr as $itemid) {
		if (is_array($itemid)) {
			delrecurse($itemid['items']);
		} else {
			delitembyid($itemid);
		}
	}
}
?>

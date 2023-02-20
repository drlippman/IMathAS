<?php
//IMathAS: Course hard deletion code
//(c) 2018 David Lippman

require_once(__DIR__."/filehandler.php");

//Look to see if a hook file is defined, and include if it is
if (isset($CFG['hooks']['delete'])) {
	require($CFG['hooks']['delete']);
}

function deleteCourse($cid) {
	global $DBH,$CFG;

	$DBH->beginTransaction();

	if (!empty($CFG['GEN']['doSafeCourseDelete'])) {
		// hard delete, so also delete log entries
		$stm = $DBH->prepare("DELETE FROM imas_teacher_audit_log WHERE courseid=:id");
		$stm->execute(array(':id'=>$cid));
    }
    
    if (function_exists('delete_custom_items_by_course')) {
        delete_custom_items_by_course($cid);
    }

	$stm = $DBH->prepare("DELETE FROM imas_courses WHERE id=:id");
	$stm->execute(array(':id'=>$cid));
	$stm = $DBH->prepare("SELECT id FROM imas_assessments WHERE courseid=:courseid");
	$stm->execute(array(':courseid'=>$cid));

	while ($line = $stm->fetch(PDO::FETCH_NUM)) {
		deleteallaidfiles($line[0]);
		$stm2 = $DBH->prepare("DELETE FROM imas_questions WHERE assessmentid=:assessmentid");
		$stm2->execute(array(':assessmentid'=>$line[0]));
		$stm2 = $DBH->prepare("DELETE FROM imas_assessment_sessions WHERE assessmentid=:assessmentid");
		$stm2->execute(array(':assessmentid'=>$line[0]));
		$stm2 = $DBH->prepare("DELETE FROM imas_assessment_records WHERE assessmentid=:assessmentid");
		$stm2->execute(array(':assessmentid'=>$line[0]));
		$stm2 = $DBH->prepare("DELETE FROM imas_exceptions WHERE assessmentid=:assessmentid AND itemtype='A'");
		$stm2->execute(array(':assessmentid'=>$line[0]));
		$stm2 = $DBH->prepare("DELETE FROM imas_livepoll_status WHERE assessmentid=:assessmentid");
		$stm2->execute(array(':assessmentid'=>$line[0]));
	}

	$stm = $DBH->prepare("DELETE FROM imas_assessments WHERE courseid=:courseid");
	$stm->execute(array(':courseid'=>$cid));

	$stm = $DBH->prepare("SELECT id FROM imas_drillassess WHERE courseid=:courseid");
	$stm->execute(array(':courseid'=>$cid));
	while ($line = $stm->fetch(PDO::FETCH_NUM)) {
		$stm2 = $DBH->prepare("DELETE FROM imas_drillassess_sessions WHERE drillassessid=:drillassessid");
		$stm2->execute(array(':drillassessid'=>$line[0]));
	}
	$stm = $DBH->prepare("DELETE FROM imas_drillassess WHERE courseid=:courseid");
	$stm->execute(array(':courseid'=>$cid));

	$stm = $DBH->prepare("SELECT id FROM imas_forums WHERE courseid=:courseid");
	$stm->execute(array(':courseid'=>$cid));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$stm2 = $DBH->prepare("SELECT id FROM imas_forum_posts WHERE forumid=:forumid AND files<>''");
		$stm2->execute(array(':forumid'=>$row[0]));
		while ($row2 = $stm2->fetch(PDO::FETCH_NUM)) {
			deleteallpostfiles($row2[0]);
		}
		$query = "DELETE imas_forum_views FROM imas_forum_views JOIN ";
		$query .= "imas_forum_threads ON imas_forum_views.threadid=imas_forum_threads.id ";
		$query .= "WHERE imas_forum_threads.forumid=:forumid";
		$stm2 = $DBH->prepare($query);
		$stm2->execute(array(':forumid'=>$row[0]));

		$stm2 = $DBH->prepare("DELETE FROM imas_forum_posts WHERE forumid=:forumid");
		$stm2->execute(array(':forumid'=>$row[0]));

		$stm2 = $DBH->prepare("DELETE FROM imas_forum_threads WHERE forumid=:forumid");
		$stm2->execute(array(':forumid'=>$row[0]));

		$stm2 = $DBH->prepare("DELETE FROM imas_exceptions WHERE assessmentid=:assessmentid AND (itemtype='F' OR itemtype='P' OR itemtype='R')");
		$stm2->execute(array(':assessmentid'=>$row[0]));

	}
	$stm = $DBH->prepare("DELETE FROM imas_forums WHERE courseid=:courseid");
	$stm->execute(array(':courseid'=>$cid));

	$stm2 = $DBH->prepare("SELECT id FROM imas_wikis WHERE courseid=:courseid");
	$stm2->execute(array(':courseid'=>$cid));
	while ($wid = $stm2->fetch(PDO::FETCH_NUM)) {
		$stm3 = $DBH->prepare("DELETE FROM imas_wiki_revisions WHERE wikiid=:wikiid");
		$stm3->execute(array(':wikiid'=>$wid[0]));
		$stm3 = $DBH->prepare("DELETE FROM imas_wiki_views WHERE wikiid=:wikiid");
		$stm3->execute(array(':wikiid'=>$wid[0]));
	}
	$stm = $DBH->prepare("DELETE FROM imas_wikis WHERE courseid=:courseid");
	$stm->execute(array(':courseid'=>$cid));

	//delete inline text files
	$stm3 = $DBH->prepare("SELECT id FROM imas_inlinetext WHERE courseid=:courseid");
	$stm3->execute(array(':courseid'=>$cid));
	while ($ilid = $stm3->fetch(PDO::FETCH_NUM)) {
		$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/../course/files/';
		$stm = $DBH->prepare("SELECT filename FROM imas_instr_files WHERE itemid=:itemid");
		$stm->execute(array(':itemid'=>$ilid[0]));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			if (substr($row[0],0,4)!='http' && strpos($row[0], $cid.'/') === 0) {
				$stm2 = $DBH->prepare("SELECT id FROM imas_instr_files WHERE filename=:filename");
				$stm2->execute(array(':filename'=>$row[0]));
				if ($stm2->rowCount()==1) {
					//unlink($uploaddir . $row[0]);
					deletecoursefile($row[0]);
				}
			}
		}
		$stm = $DBH->prepare("DELETE FROM imas_instr_files WHERE itemid=:itemid");
		$stm->execute(array(':itemid'=>$ilid[0]));
	}
	$stm = $DBH->prepare("DELETE FROM imas_inlinetext WHERE courseid=:courseid");
	$stm->execute(array(':courseid'=>$cid));

	//delete linked text files
	$stm = $DBH->prepare("SELECT text,points,id,fileid FROM imas_linkedtext WHERE courseid=:courseid");
	$stm->execute(array(':courseid'=>$cid));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$fileid = $row[3];
		if ($fileid > 0) { // has file id - can use that approach
			$stm = $DBH->prepare("SELECT count(id) FROM imas_linkedtext WHERE fileid=?");
			$stm->execute(array($fileid));
			if ($stm->fetchColumn(0) == 1) { // only one use of this file
				$filename = substr($row[0],5);
				deletecoursefile($filename);
				$stm = $DBH->prepare("DELETE FROM imas_linked_files WHERE id=?");
				$stm->execute(array($fileid));
			}
		} else if (strpos($row[0], 'file:'.$cid.'/') === 0) { // if file is from this course
			$stm2 = $DBH->prepare("SELECT id FROM imas_linkedtext WHERE text=:text");
			$stm2->execute(array(':text'=>$row[0]));
			if ($stm2->rowCount()==1) {
				//$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/../course/files/';
				$filename = substr($row[0],5);
				//unlink($uploaddir . $filename);
				deletecoursefile($filename);
			}
		}
		if ($row[1]>0) {
			$stm2 = $DBH->prepare("DELETE FROM imas_grades WHERE gradetypeid=:gradetypeid AND gradetype='exttool'");
			$stm2->execute(array(':gradetypeid'=>$row[2]));
		}
	}


	$stm = $DBH->prepare("DELETE FROM imas_linkedtext WHERE courseid=:courseid");
	$stm->execute(array(':courseid'=>$cid));
	$stm = $DBH->prepare("DELETE FROM imas_items WHERE courseid=:courseid");
	$stm->execute(array(':courseid'=>$cid));
	$stm = $DBH->prepare("DELETE FROM imas_teachers WHERE courseid=:courseid");
	$stm->execute(array(':courseid'=>$cid));
	$stm = $DBH->prepare("DELETE FROM imas_students WHERE courseid=:courseid");
	$stm->execute(array(':courseid'=>$cid));
	$stm = $DBH->prepare("DELETE FROM imas_tutors WHERE courseid=:courseid");
	$stm->execute(array(':courseid'=>$cid));

	$stm = $DBH->prepare("SELECT id FROM imas_gbitems WHERE courseid=:courseid");
	$stm->execute(array(':courseid'=>$cid));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$stm2 = $DBH->prepare("DELETE FROM imas_grades WHERE gradetype='offline' AND gradetypeid=:gradetypeid");
		$stm2->execute(array(':gradetypeid'=>$row[0]));
	}
	$stm = $DBH->prepare("DELETE FROM imas_gbitems WHERE courseid=:courseid");
	$stm->execute(array(':courseid'=>$cid));
	$stm = $DBH->prepare("DELETE FROM imas_gbscheme WHERE courseid=:courseid");
	$stm->execute(array(':courseid'=>$cid));
	$stm = $DBH->prepare("DELETE FROM imas_gbcats WHERE courseid=:courseid");
	$stm->execute(array(':courseid'=>$cid));

	$stm = $DBH->prepare("DELETE FROM imas_calitems WHERE courseid=:courseid");
	$stm->execute(array(':courseid'=>$cid));

	$stm = $DBH->prepare("SELECT id FROM imas_stugroupset WHERE courseid=:courseid");
	$stm->execute(array(':courseid'=>$cid));
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		$stm2 = $DBH->prepare("SELECT id FROM imas_stugroups WHERE groupsetid=:groupsetid");
		$stm2->execute(array(':groupsetid'=>$row[0]));
		while ($row2 = $stm2->fetch(PDO::FETCH_NUM)) {
			$stm3 = $DBH->prepare("DELETE FROM imas_stugroupmembers WHERE stugroupid=:stugroupid");
			$stm3->execute(array(':stugroupid'=>$row2[0]));
		}
		$stm4 = $DBH->prepare("DELETE FROM imas_stugroups WHERE groupsetid=:groupsetid");
		$stm4->execute(array(':groupsetid'=>$row[0]));
	}
	$stm = $DBH->prepare("DELETE FROM imas_stugroupset WHERE courseid=:courseid");
	$stm->execute(array(':courseid'=>$cid));

	$stm = $DBH->prepare("DELETE FROM imas_external_tools WHERE courseid=:courseid");
	$stm->execute(array(':courseid'=>$cid));
	$stm = $DBH->prepare("DELETE FROM imas_content_track WHERE courseid=:courseid");
	$stm->execute(array(':courseid'=>$cid));
	$stm = $DBH->prepare("DELETE FROM imas_excused WHERE courseid=:courseid");
	$stm->execute(array(':courseid'=>$cid));

	$DBH->commit();
}

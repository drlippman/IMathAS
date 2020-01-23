<?php
//IMathAS:  Save changes to addquestions submitted through AHAH
//(c) 2007 IMathAS/WAMAP Project
	require("../init.php");
	$cid = Sanitize::courseId($_GET['cid']);
	$aid = Sanitize::onlyInt($_GET['aid']);
	if (!isset($teacherid)) {
		echo "error: validation";
	}
	$stm = $DBH->prepare("SELECT itemorder,viddata,intro,defpoints,courseid FROM imas_assessments WHERE id=:id");
	$stm->execute(array(':id'=>$aid));
	list($rawitemorder, $viddata,$current_intro_json, $defpoints,$assesscourseid) = $stm->fetch(PDO::FETCH_NUM);
	if ($assesscourseid != $cid) {
		echo "error: invalid ID";
		exit;
	}
	$itemorder = str_replace('~',',',$rawitemorder);
	$curitems = array();
	foreach (explode(',',$itemorder) as $qid) {
		if (strpos($qid,'|')===false) {
			$curitems[] = $qid;
		}
	}

	if (($intro=json_decode($current_intro_json,true))!==null) { //is json intro
		$current_intro = $intro[0];
	} else {
		$current_intro = $current_intro_json; //it actually isn't JSON
	}
	$new_text_segments_json = json_decode($_REQUEST['text_order'],true);
	if (count($new_text_segments_json)>0) {
		require_once("../includes/htmLawed.php");
		foreach ($new_text_segments_json as $k=>$seg) {
			$new_text_segments_json[$k]['text'] = myhtmlawed($seg['text']);
			if (isset($new_text_segments_json[$k]['pagetitle'])) {
				$new_text_segments_json[$k]['pagetitle'] = strip_tags($seg['pagetitle']);
			}
		}
		array_unshift($new_text_segments_json, $current_intro);
		$new_intro = json_encode($new_text_segments_json);
	} else {
		$new_intro = $current_intro;
	}

	$submitted = $_REQUEST['order'];
	$submitted = str_replace('~',',',$submitted);
	$newitems = array();
	foreach (explode(',',$submitted) as $qid) {
		if (strpos($qid,'|')===false) {
			$newitems[] = Sanitize::onlyInt($qid);
		}
	}
	$toremove = array_diff($curitems,$newitems);

	if ($viddata != '') {
		$viddata = unserialize($viddata);
		$qorder = explode(',',$rawitemorder);
		$qidbynum = array();
		for ($i=0;$i<count($qorder);$i++) {
			if (strpos($qorder[$i],'~')!==false) {
				$qids = explode('~',$qorder[$i]);
				if (strpos($qids[0],'|')!==false) { //pop off nCr
					$qidbynum[$i] = $qids[1];
				} else {
					$qidbynum[$i] = $qids[0];
				}
			} else {
				$qidbynum[$i] = $qorder[$i];
			}
		}

		$qorder = explode(',',$_REQUEST['order']);
		$newbynum = array();
		if (trim($_REQUEST['order'])!='') {
			for ($i=0;$i<count($qorder);$i++) {
				if (strpos($qorder[$i],'~')!==false) {
					$qids = explode('~',$qorder[$i]);
					if (strpos($qids[0],'|')!==false) { //pop off nCr
						$newbynum[$i] = $qids[1];
					} else {
						$newbynum[$i] = $qids[0];
					}
				} else {
					$newbynum[$i] = $qorder[$i];
				}
			}
		}

		$qidbynumflip = array_flip($qidbynum);

		$newviddata = array();
		$newviddata[0] = $viddata[0];
		for ($i=0;$i<count($newbynum);$i++) {   //for each new item
			$oldnum = $qidbynumflip[$newbynum[$i]];
			$found = false; //look for old item in viddata
			for ($j=1;$j<count($viddata);$j++) {
				if (isset($viddata[$j][2]) && $viddata[$j][2]==$oldnum) {
					//if found, copy data, and any non-question data following
					$new = $viddata[$j];
					$new[2] = $i;  //update question number;
					$newviddata[] = $new;
					$j++;
					while (isset($viddata[$j]) && !isset($viddata[$j][2])) {
						$newviddata[] = $viddata[$j];
						$j++;
					}
					$found = true;
					break;
				}
			}
			if (!$found) {
				//item was not found in viddata.  it should have been.
				//count happen if the first item in a group was removed, perhaps
				//Add a blank item
				$newviddata[] =  array('','',$i);
			}
		}
		//any old items will not get copied.
		$viddata = serialize($newviddata);
	}

	$DBH->beginTransaction();

	//update question point values
	$ptschanged = false;
	if (isset($_POST['pts'])) {
		$newpts = json_decode($_POST['pts'], true);
		$upd_pts = $DBH->prepare("UPDATE imas_questions SET points=? WHERE id=?");
		$stm = $DBH->prepare("SELECT id,points FROM imas_questions WHERE assessmentid=?");
		$stm->execute(array($aid));
		while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
			if (!isset($newpts['qn'.$row['id']])) {
				continue;  //shouldn't happen
			}
			if ($row['points'] != $newpts['qn'.$row['id']]) {
				$upd_pts->execute(array($newpts['qn'.$row['id']], $row['id']));
				$ptschanged = true;
			}
		}
	}

	$qarr = array(':itemorder'=>$_REQUEST['order'], ':viddata'=>$viddata, ':intro'=>$new_intro, ':id'=>$aid, ':courseid'=>$cid);
	$query = "UPDATE imas_assessments SET itemorder=:itemorder,viddata=:viddata,intro=:intro";
	if (isset($_POST['defpts'])) {
		$defpoints = Sanitize::onlyInt($_POST['defpts']);
		$query .= ",defpoints=:defpts";
		$qarr[':defpts'] = $defpoints;
	}
	$query .= " WHERE id=:id AND courseid=:courseid";
	//store new itemorder
	$stm = $DBH->prepare($query);
	$stm->execute($qarr);
	if ($stm->rowCount()>0 || $ptschanged) {
		//delete any removed questions
		if (count($toremove)>0) {
			$toremove = implode(',', array_map('intval', $toremove));
			$stm = $DBH->query("DELETE FROM imas_questions WHERE id IN ($toremove)");
		}
		//update points possible
		require_once("../includes/updateptsposs.php");
		updatePointsPossible($aid, $_REQUEST['order'], $defpoints);

		// Delete any teacher or tutor attempts on this assessment
		$query = 'DELETE iar FROM imas_assessment_records AS iar JOIN
			imas_teachers AS usr ON usr.userid=iar.userid AND usr.courseid=?
			WHERE iar.assessmentid=?';
		$stm = $DBH->prepare($query);
		$stm->execute(array($cid, $aid));
		$query = 'DELETE iar FROM imas_assessment_records AS iar JOIN
			imas_tutors AS usr ON usr.userid=iar.userid AND usr.courseid=?
			WHERE iar.assessmentid=?';
		$stm = $DBH->prepare($query);
		$stm->execute(array($cid, $aid));

		echo "OK";
	} else {
		echo "error: not saved";
	}
	$DBH->commit();

?>

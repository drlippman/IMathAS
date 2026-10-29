<?php
//IMathAS:  Save changes to addquestions submitted through AHAH
//(c) 2007 IMathAS/WAMAP Project
	require_once "../init.php";
	require_once "../includes/viddatautil.php";
	$cid = Sanitize::courseId($_GET['cid']);
	$aid = Sanitize::onlyInt($_GET['aid']);
	if (!isset($teacherid)) {
        echo "error: validation";
        exit;
    }
    
	$stm = $DBH->prepare("SELECT itemorder,viddata,intro,defpoints,courseid,ver,showhints,showwork,drilljson,displaymethod FROM imas_assessments WHERE id=:id");
	$stm->execute(array(':id'=>$aid));
	list($rawitemorder, $viddata,$current_intro_json, $defpoints,$assesscourseid,$aver,$showhints,$showwork,$rawdrilljson,$assessdisplaymethod) = $stm->fetch(PDO::FETCH_NUM);
	if ($assesscourseid != $cid) {
		echo "error: invalid ID";
		exit;
    }
    
    $showwork = ($showwork & 3);
    if ($aver > 1) {
		$query = "SELECT iar.userid FROM imas_assessment_records AS iar,imas_students WHERE ";
		$query .= "iar.assessmentid=:assessmentid AND iar.userid=imas_students.userid AND imas_students.courseid=:courseid LIMIT 1";
	} else {
		$query = "SELECT ias.id FROM imas_assessment_sessions AS ias,imas_students WHERE ";
		$query .= "ias.assessmentid=:assessmentid AND ias.userid=imas_students.userid AND imas_students.courseid=:courseid LIMIT 1";
	}
	$stm = $DBH->prepare($query);
	$stm->execute(array(':assessmentid'=>$aid, ':courseid'=>$cid));
	if ($stm->rowCount() > 0) {
		$beentaken = true;
	} else {
		$beentaken = false;
	}

    if (isset($_POST['addnewdef'])) {
        if (!isset($_POST['lastitemhash']) || md5($rawitemorder . $current_intro_json) !== $_POST['lastitemhash']) {
            echo '{"error": "assessment questions have changed elsewhere. Reload the page and try again."}';
            exit;
        }
        header('Content-Type: application/json; charset=utf-8');
        if ($beentaken) {
            echo '{"error": "'._('Students have started the assessment, and you cannot change questions or order after students have started; reload the page').'"}';
            exit;
        }
        $DBH->beginTransaction();
        $newqs = array_map('intval', $_POST['addnewdef']);
        $qids = [];
        foreach ($newqs as $qsetid) {
            if ($qsetid == 0) { continue; }
            $query = "INSERT INTO imas_questions (assessmentid,points,attempts,penalty,questionsetid,showhints) ";
            $query .= "VALUES (:assessmentid, :points, :attempts, :penalty, :questionsetid, :showhints);";
            $stm = $DBH->prepare($query);
            $stm->execute(array(':assessmentid'=>$aid, ':points'=>9999, ':attempts'=>9999,
                ':penalty'=>9999, ':questionsetid'=>$qsetid,
                ':showhints' => ($aver > 1) ? -1 : 0
            ));
            $qids[] = $DBH->lastInsertId();
        }
        //add to itemorder
        if (($_POST['asgroup'] ?? 0) == 1) {
            $newitems = '1|0~'.implode('~', $qids);
            $numnew = 1;
        } else {
            $newitems = implode(',', $qids);
            $numnew = count($qids);
        }
        if ($rawitemorder=='') {
            $itemorder = $newitems;
        } else {
            $itemorder  = $rawitemorder . "," . $newitems;
        }
        $viddata = appendBlankVidSegments($rawitemorder, $numnew, $viddata);
        $stm = $DBH->prepare("UPDATE imas_assessments SET itemorder=:itemorder,viddata=:viddata WHERE id=:id");
        $stm->execute(array(':itemorder'=>$itemorder, ':viddata'=>$viddata, ':id'=>$aid));

        require_once "../includes/updateptsposs.php";
        updatePointsPossible($aid, $itemorder, $defpoints);
        $DBH->commit();

        require_once '../includes/addquestions2util.php';
        list($jsarr, $existingqs, $introconvertmsg) = getQuestionsAsJSON($cid, $aid, [
            'intro' => $current_intro_json,
            'itemorder' => $itemorder,
            'showwork' => $showwork,
            'showhints' => $showhints
        ]);
        
        echo json_encode(['itemarray'=>$jsarr, 'lastitemhash'=>md5($itemorder . $current_intro_json)], 
            JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_INVALID_UTF8_IGNORE);
        exit;
    } else if (isset($_POST['addnew'])) {

    } 

	function removeGrpNames($order) {
		return preg_replace('/(\d+\|\d+)\|.*?~/', '$1~', $order);
	}

	if (!isset($_POST['order']) || !isset($_POST['text_order']) || !isset($_POST['lastitemhash'])) {
		echo "error: missing required values";
		exit;
    }
    if ($beentaken && removeGrpNames($rawitemorder) != removeGrpNames($_REQUEST['order'])) {
        echo 'error: '._('Students have started the assessment, and you cannot change questions or order after students have started; reload the page');
        exit;
    }
    if (md5($rawitemorder . $current_intro_json) !== $_POST['lastitemhash']) {
        echo "error: assessment questions have changed elsewhere. Reload the page and try again.";
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
	if ($new_text_segments_json === null) {
		echo 'Error: error in text segments.';
		exit;
	}
	if (count($new_text_segments_json)>0) {
		require_once "../includes/htmLawed.php";
		foreach ($new_text_segments_json as $k=>$seg) {
			$new_text_segments_json[$k]['text'] = myhtmlawed($seg['text']);
			if (isset($new_text_segments_json[$k]['pagetitle'])) {
				$new_text_segments_json[$k]['pagetitle'] = strip_tags($seg['pagetitle']);
			}
		}
		array_unshift($new_text_segments_json, $current_intro);
		$new_intro = json_encode($new_text_segments_json, JSON_INVALID_UTF8_IGNORE);
	} else {
		$new_intro = $current_intro;
	}

	$submitted = $_REQUEST['order'];
	$submitted = str_replace('~',',',$submitted);
	$newitems = array();
	if ($submitted !== '') {
		foreach (explode(',',$submitted) as $qid) {
			if (strpos($qid,'|')===false) {
				$newitems[] = Sanitize::onlyInt($qid);
			}
		}
	}
	$toremove = array_diff($curitems,$newitems);
	if (!empty(array_diff($newitems, $curitems))) {
		// this would mean there's a qid in the itemorder that didn't previously exist
		echo "error: invalid item in order";
        exit;
	}

	if ($viddata != '') {
		$viddata = remapVidData($rawitemorder, $_REQUEST['order'], $viddata);
	}

	$DBH->beginTransaction();

	//update question point values
	$ptschanged = false;
	if (isset($_POST['pts'])) {
		$newpts = json_decode($_POST['pts'], true);
        $newextracredit = json_decode($_POST['extracredit'], true);
		$upd_pts = $DBH->prepare("UPDATE imas_questions SET points=?,extracredit=? WHERE id=?");
		$stm = $DBH->prepare("SELECT id,points,extracredit FROM imas_questions WHERE assessmentid=?");
		$stm->execute(array($aid));
		while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
			if (!isset($newpts['qn'.$row['id']])) {
				continue;  //shouldn't happen
			}
			if ($row['points'] != $newpts['qn'.$row['id']] || $row['extracredit'] != $newextracredit['qn'.$row['id']]) {
				$upd_pts->execute(array($newpts['qn'.$row['id']], $newextracredit['qn'.$row['id']], $row['id']));
				$ptschanged = true;
			}
		}
	} else if (isset($_POST['extracredit'])) {
        $newextracredit = json_decode($_POST['extracredit'], true);
		$upd_pts = $DBH->prepare("UPDATE imas_questions SET extracredit=? WHERE id=?");
		$stm = $DBH->prepare("SELECT id,extracredit FROM imas_questions WHERE assessmentid=?");
		$stm->execute(array($aid));
		while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
			if ($row['extracredit'] != $newextracredit['qn'.$row['id']]) {
				$upd_pts->execute(array($newextracredit['qn'.$row['id']], $row['id']));
				$ptschanged = true;
			}
		}
    }

	//update drill display names
	$drilljsonchanged = false;
	if ($assessdisplaymethod == 'drill' && isset($_POST['dispnames'])) {
		$newdispnames = json_decode($_POST['dispnames'], true);
		if (is_array($newdispnames)) {
			$drilljson = ($rawdrilljson != '') ? json_decode($rawdrilljson, true) : array();
			if (!is_array($drilljson)) { $drilljson = array(); }
			$dispnames = array();
			$stm = $DBH->prepare("SELECT id FROM imas_questions WHERE assessmentid=?");
			$stm->execute(array($aid));
			while ($qrow = $stm->fetch(PDO::FETCH_ASSOC)) {
				$qkey = 'qn' . $qrow['id'];
				if (!empty($newdispnames[$qkey])) {
					$dispnames[$qkey] = Sanitize::stripHtmlTags($newdispnames[$qkey]);
				}
			}
			$drilljson['dispnames'] = $dispnames;
			$upd_drilljson = $DBH->prepare("UPDATE imas_assessments SET drilljson=? WHERE id=? AND courseid=?");
			$upd_drilljson->execute(array(json_encode($drilljson), $aid, $cid));
			$drilljsonchanged = ($upd_drilljson->rowCount() > 0);
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
		require_once "../includes/updateptsposs.php";
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

        echo md5($_REQUEST['order'] . $new_intro);
		//echo "OK";
	} else if ($drilljsonchanged) {
		// itemorder/points didn't change, but the drill display names did;
		// the hash is unchanged since it doesn't depend on drilljson
		echo md5($_REQUEST['order'] . $new_intro);
	} else {
		echo "error: not saved";
	}
	$DBH->commit();

?>

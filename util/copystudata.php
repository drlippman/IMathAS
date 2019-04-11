<?php 
//Copy students and assessment data from one course to another
//IMathAS (c) 2018 David Lippman for Lumen Learning

function copyStuData($destcid, $sourcecid = null) {
	global $DBH, $itemtypemap;
	
	if ($sourcecid===null) {
		$stm = $DBH->prepare("SELECT ancestors FROM imas_courses WHERE id=?");
		$stm->execute(array($destcid));
		$row = $stm->fetch(PDO::FETCH_ASSOC);
		if ($row['ancestors']=='') {
			return 0;
		}
		$ancestors = explode(',', $row['ancestors']);
		$sourcecid = $ancestors[0];
	}
	
	$stm = $DBH->prepare("SELECT itemorder FROM imas_courses WHERE id=?");
	$stm->execute(array($sourcecid));
	$sourceitems = unserialize($stm->fetchColumn(0));
	
	$stm = $DBH->prepare("SELECT id,name,itemorder,ancestors FROM imas_assessments WHERE courseid=?");
	$stm->execute(array($destcid));
	$destq = array();
	$assessmap = array();  //maps source aid -> dest aid
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		$row['itemorder'] = str_replace('~',',',$row['itemorder']);
		$row['itemorder'] = preg_replace('/\d+\|\d+,/','',$row['itemorder']);
		$qs = explode(',', $row['itemorder']);
		$ancestors = explode(',', $row['ancestors']);
		$assessmap[$ancestors[0]] = $row['id'];
		$destq[$row['id']] = $qs;
	}
	
	$stm = $DBH->prepare("SELECT id,name,itemorder FROM imas_assessments WHERE courseid=?");
	$stm->execute(array($sourcecid));
	$qmap = array();
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		if (!isset($assessmap[$row['id']])) { continue; }
		
		$row['itemorder'] = str_replace('~',',',$row['itemorder']);
		$row['itemorder'] = preg_replace('/\d+\|\d+,/','',$row['itemorder']);
	
		$qs = explode(',', $row['itemorder']);
		$destaid = $assessmap[$row['id']];
		foreach ($qs as $k=>$v) {
			$qmap[$v] = $destq[$destaid][$k];
		}
	}
	
	//copy students
	$stm = $DBH->prepare("SELECT userid FROM imas_students WHERE courseid=?");
	$stm->execute(array($destcid));
	$existingstu = array();
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		$existingstu[] = $row['userid'];
	}
	
	$stufieldlist = 'userid,section,code,gbcomment,latepass,lastaccess,gbinstrcomment,locked,timelimitmult,stutype,custominfo';
	$stufields = explode(',', $stufieldlist);
	$execarr = array();
	$stm = $DBH->prepare("SELECT $stufieldlist FROM imas_students WHERE courseid=?");
	$stm->execute(array($sourcecid));
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		if (in_array($row['userid'], $existingstu)) { continue; } //don't add again
		$execarr[] = $destcid;
		foreach ($stufields as $field) {
			$execarr[] = $row[$field];
		}
	}
	if (count($execarr)>0) {
		$ph = Sanitize::generateQueryPlaceholdersGrouped($execarr, count($stufields)+1);
		$stm = $DBH->prepare("INSERT INTO imas_students (courseid,$stufieldlist) VALUES $ph");
		$stm->execute($execarr);
	}
	                          

	//copy assessment sessions
	$stm = $DBH->prepare("SELECT ias.* FROM imas_assessment_sessions AS ias JOIN imas_assessments AS ia on ias.assessmentid=ia.id AND ia.courseid=?");
	$stm->execute(array($sourcecid));
	$fieldlist = '';
	$execarr = array();
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		if (!isset($assessmap[$row['assessmentid']])) { continue; }
		unset($row['id']);
		unset($row['lti_sourcedid']);
		if ($fieldlist == '') {
			$fields = array_keys($row);
			$fields = array_map('Sanitize::simpleString', $fields);
			$fieldlist = implode(',', $fields);
		}
		//remap questions
		$qs = explode(',', $row['questions']);
		foreach ($qs as $k=>$v) {
			$qs[$k] = $qmap[$v];
		}
		$row['questions'] = implode(',', $qs);
		
		//ungroup it
		$row['agroupid'] = 0;
		//map assessment
		$row['assessmentid'] = $assessmap[$row['assessmentid']]; 
	
		foreach ($fields as $field) {
			$execarr[] = $row[$field];
		}
	}
	//insert assessment sessions
	if (count($execarr)>0) {
		$ph = Sanitize::generateQueryPlaceholdersGrouped($execarr, count($fields));
		$stm = $DBH->prepare("INSERT IGNORE INTO imas_assessment_sessions ($fieldlist) VALUES $ph");
		$stm->execute($execarr);
	}
	
	//copy content tracking data
	if (count($itemtypemap)>0) {
		$execarr = array();
		//$itemtypemap[$itemtype.$typeid] = $newtypeid;
		$stm = $DBH->prepare("SELECT * FROM imas_content_track WHERE courseid=?");
		$stm->execute(array($sourcecid));
		while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
			$adding = false;
			if (strpos($row['type'], 'inline')!==false && 
				isset($itemtypemap['InlineText'.$row['typeid']])) {
				$execarr[] = $itemtypemap['InlineText'.$row['typeid']];
			} else if (strpos($row['type'], 'linked')!==false && 
				isset($itemtypemap['LinkedText'.$row['typeid']])) {
				$execarr[] = $itemtypemap['LinkedText'.$row['typeid']];
			} else if (strpos($row['type'], 'extref')!==false && 
				isset($qmap[$row['typeid']])) {
				$execarr[] = $qmap[$row['typeid']];
			} else if (strpos($row['type'], 'assess')!==false && 
				isset($itemtypemap['Assessment'.$row['typeid']])) {
				$execarr[] = $itemtypemap['Assessment'.$row['typeid']];
			} else if (strpos($row['type'], 'wiki')!==false && 
				isset($itemtypemap['Wiki'.$row['typeid']])) {
				$execarr[] = $itemtypemap['Wiki'.$row['typeid']];
			} else if (strpos($row['type'], 'forum')!==false && 
				isset($itemtypemap['Forum'.$row['typeid']])) {
				$execarr[] = $itemtypemap['Forum'.$row['typeid']];
			} else if (strpos($row['type'], 'drill')!==false && 
				isset($itemtypemap['Drill'.$row['typeid']])) {
				$execarr[] = $itemtypemap['Drill'.$row['typeid']];
			} else {
				continue;
			}
			array_push($execarr, $row['userid'], $destcid, $row['type'], $row['viewtime'], $row['info']);
		}
		//insert content track data
		if (count($execarr)>0) {
			$ph = Sanitize::generateQueryPlaceholdersGrouped($execarr, 6);
			$stm = $DBH->prepare("INSERT IGNORE INTO imas_content_track (typeid, userid, courseid, type, viewtime, info) VALUES $ph");
			$stm->execute($execarr);
		}
	}

	return 1;
}


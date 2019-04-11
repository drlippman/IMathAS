<?php
//Function for calculating points possible for an assessment
//(c) IMathAS 2018

function updatePointsPossible($aid, $itemorder = null, $defpoints = null) {
	global $DBH;
	
	if ($itemorder === null || $defpoints === null) {
		$stm = $DBH->prepare("SELECT itemorder,defpoints FROM imas_assessments WHERE id=?");
		$stm->execute(array($aid));
		list($itemorder,$defpoints) = $stm->fetch(PDO::FETCH_NUM);
	}
	
	$stm = $DBH->prepare("SELECT id,points FROM imas_questions WHERE assessmentid=? AND points<9999");
	$stm->execute(array($aid));
	$questionpointdata = array();
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		$questionpointdata[$row['id']] = $row['points'];
	}
	$poss = calcPointsPossible($itemorder, $questionpointdata, $defpoints);
	
	$stm = $DBH->prepare("UPDATE imas_assessments SET ptsposs=? WHERE id=?");
	$stm->execute(array($poss, $aid));
	return $poss;
}

function calcPointsPossible($itemorder, $questionpointdata, $defpoints) {
	if (is_array($itemorder)) {
		$aitems = $itemorder;
	} else {
		$aitems = explode(',', $itemorder);
	}
	
	$totalpossible = 0;
	foreach ($aitems as $v) {
		if (strpos($v,'~')!==FALSE) {
			$sub = explode('~',$v);
			if (strpos($sub[0],'|')===false) { //backwards compat
				$totalpossible += (isset($questionpointdata[$sub[0]]))?$questionpointdata[$sub[0]]:$defpoints;
			} else {
				$grpparts = explode('|',$sub[0]);
				if ($grpparts[0]==count($sub)-1) { //handle diff point values in group if n=count of group
					for ($i=1;$i<count($sub);$i++) {
						$totalpossible += (isset($questionpointdata[$sub[$i]]))?$questionpointdata[$sub[$i]]:$defpoints;
					}
				} else {
					$totalpossible += $grpparts[0]*((isset($questionpointdata[$sub[1]]))?$questionpointdata[$sub[1]]:$defpoints);
				}
			}
		} else {
			$totalpossible += (isset($questionpointdata[$v]))?$questionpointdata[$v]:$defpoints;
		}
	}	
	return $totalpossible;
}

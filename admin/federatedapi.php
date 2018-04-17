<?php
//IMathAS:  Federated libraries update send
//(c) 2017 David Lippman

//exit; //not ready for use yet.

if (empty($_GET['peer']) || !isset($_GET['since']) || !isset($_GET['sig'])) {
	exit;
}

$since = intval($_GET['since']);
require("../init_without_validate.php");
require_once("../includes/filehandler.php");

$peerName = Sanitize::stripHtmlTags($_GET['peer']);
$stm = $DBH->prepare("SELECT id,secret FROM imas_federation_peers WHERE peername=:peername");
$stm->execute(array(':peername'=>$peerName));
if ($stm->rowCount()==0) {
	echo '{error:"Unknown peer"}';
	exit;
}
$peer = $stm->fetch(PDO::FETCH_ASSOC);
if (function_exists("hash_hmac")) {
	$computed_signature =  base64_encode(hash_hmac('sha1', $_GET['peer'], $peer['secret'], true));
} else {
	$computed_signature = base64_encode(custom_hmac('sha1', $_GET['peer'], $peer['secret'], true));
}
if ($computed_signature != $_GET['sig']) {
	echo '{error:"Invalid authorization"}';
	exit;
}


$stm = $DBH->prepare("SELECT pulltime FROM imas_federation_pulls WHERE peerid=:peerid");
$stm->execute(array(":peerid"=>$peer['id']));
$toskip = array();
while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
	$toskip[] = $row['pulltime'];
}
if (count($toskip)>0) {
	$skipph = Sanitize::generateQueryPlaceholders($toskip);
}

if (isset($_GET['stage'])) {
	$stage = intval($_GET['stage']);
} else {
	$stage = 0;
}

if ($stage == 0) { //send updated libraries
	$query = 'SELECT A.id,A.uniqueid,A.federationlevel,A.name,A.deleted,A.lastmoddate,A.parent,B.uniqueid as parentuid ';
	$query .= 'FROM imas_libraries AS A LEFT JOIN imas_libraries AS B ON A.parent=B.id ';
	$query .= 'WHERE A.lastmoddate>? AND A.federationlevel>0 AND A.userights=8 ';
	if (count($toskip)>0) {
		$query .= "AND A.lastmoddate NOT IN ($skipph)";
		$stm = $DBH->prepare($query);
		$stm->execute(array_merge(array($since),$toskip));
	} else {
		$stm = $DBH->prepare($query);
		$stm->execute(array($since));
	}
	$libs = array();
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		$libs[] = array('uid'=>$row['uniqueid'], 'fl'=>$row['federationlevel'], 'n'=>$row['name'],
			'd'=>$row['deleted'], 'lm'=>$row['lastmoddate'], 'p'=>$row['parent']==0?0:$row['parentuid']);
	}
	echo json_encode(array('since'=>$since, 'stage'=>0, 'data'=>$libs));

	//record this pull
	$now = time();
	$stm = $DBH->prepare('UPDATE imas_federation_peers SET lastpull=:now WHERE peername=:peername');
	$stm->execute(array(':now'=>$now, ':peername'=>$peerName));
	exit;
} else if ($stage == 1) { //send updated questions
	//we're going to order from most recent back so that if something updates while we're
	//pulling, it might just cause us to re-send something rather than miss something
	//newly updated stuff will catch the next pull
	if (isset($_GET['offset'])) {
		$offset = intval($_GET['offset']);
		if ($offset<0) { echo '{error:"Invalid offset"}'; exit;}
	} else {
		$offset = 0;
	}
	$batchsize = 1000;
	//TODO:  Handle replaceby, sourceinstall, includecodefrom, includeqtextfrom
	$query = 'SELECT iq.*,il.uniqueid AS ulibid,ili.junkflag,ili.deleted AS libdel,ili.lastmoddate AS liblastmod FROM imas_questionset as iq ';
	$query .= 'JOIN imas_library_items AS ili ON iq.id=ili.qsetid ';
	$query .= 'JOIN imas_libraries AS il ON il.id=ili.libid AND il.federationlevel>0 AND il.userights=8 ';
	$query .= 'WHERE iq.lastmoddate>? AND iq.userights>1 AND iq.license>0 ';
	if (count($toskip)>0) {
		$query .= "AND iq.lastmoddate NOT IN ($skipph) ";
	}
	$query .= 'ORDER BY iq.lastmoddate DESC LIMIT '.$batchsize.' OFFSET '.$offset;
	$stm = $DBH->prepare($query);

	$img_stm = $DBH->prepare("SELECT var,filename,alttext FROM imas_qimages WHERE qsetid=:qsetid");

	if (count($toskip)>0) {
		$stm = $DBH->prepare($query);
		$stm->execute(array_merge(array($since),$toskip));
	} else {
		$stm = $DBH->prepare($query);
		$stm->execute(array($since));
	}
	$qinfo = array();
	$qcnt = -1; $lastq = -1; $linecnt = -1; $hasmoreq = false;
	$includedqs = array();  //includecodefrom to resolve
	$includetoresolve = array();
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		$linecnt++;
		if ($row['id']==$lastq) { //same question, different libid
			$qinfo[$qcnt]['libs'][] = array('ulibid'=>$row['ulibid'], 'junkflag'=>$row['junkflag'], 'deleted'=>$row['libdel']);
		} else { //new question
			//we need to stop before the full offset to ensure all the library entries
			//for a question are sent with the question
			if ($linecnt>.9*$batchsize) {$hasmoreq = true; break;}
			$qcnt++;
			if (preg_match_all('/includecodefrom\((\d+)\)/',$row['control'],$matches,PREG_PATTERN_ORDER) >0) {
				$includedqs = array_merge($includedqs,$matches[1]);
				$includetoresolve[$qcnt] = 1;
			}
			if (preg_match_all('/includeqtextfrom\((\d+)\)/',$row['qtext'],$matches,PREG_PATTERN_ORDER) >0) {
				$includedqs = array_merge($includedqs,$matches[1]);
				$includetoresolve[$qcnt] = 1;
			}
			$qinfo[$qcnt] = array('uniqueid'=>$row['uniqueid'], 'adddate'=>$row['adddate'],
				'lastmoddate'=>$row['lastmoddate'], 'author'=>$row['author'],
				'description'=>$row['description'], 'qtype'=>$row['qtype'], 'control'=>$row['control'],
				'qcontrol'=>$row['qcontrol'], 'qtext'=>$row['qtext'], 'answer'=>$row['answer'],
				'extref'=>$row['extref'], 'deleted'=>$row['deleted'], 'broken'=>$row['broken'],
				'solution'=>$row['solution'], 'solutionopts'=>$row['solutionopts'], 'license'=>$row['license'],
				'ancestorauthors'=>$row['ancestorauthors'], 'otherattribution'=>$row['otherattribution'],
				'libs'=>array(array('ulibid'=>$row['ulibid'], 'junkflag'=>$row['junkflag'], 'deleted'=>$row['libdel'], 'lastmoddate'=>$row['liblastmod'])),
				'imgs'=>array());
			if ($row['hasimg']>0) {
				$img_stm->execute(array(':qsetid'=>$row['id']));
				while ($imgrow = $img_stm->fetch(PDO::FETCH_ASSOC)) {
					$qinfo[$qcnt]['imgs'][] = array('var'=>$imgrow['var'],
						'filename'=>getqimageurl($imgrow['filename'],true),
						'alttext'=>$imgrow['alttext']);
				}
			}
			$lastq = $row['id'];
		}
	}
	$includedqs = array_unique($includedqs);
	if (count($includedqs)>0) {
		$placeholders = Sanitize::generateQueryPlaceholders($includedqs);
		$stm = $DBH->prepare("SELECT id,uniqueid FROM imas_questionset WHERE id IN ($placeholders)");
		$stm->execute($includedqs);
		$includedbackref = array();
		while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
			$includedbackref[$row['id']] = $row['uniqueid'];
		}
		foreach ($includetoresolve as $i=>$v) {
			$qinfo[$i]['control'] = preg_replace_callback('/includecodefrom\((\d+)\)/', function($matches) use ($includedbackref) {
					return "includecodefrom(UID".$includedbackref[$matches[1]].")";
				}, $qinfo[$i]['control']);
			$qinfo[$i]['qtext'] = preg_replace_callback('/includeqtextfrom\((\d+)\)/', function($matches) use ($includedbackref) {
					return "includeqtextfrom(UID".$includedbackref[$matches[1]].")";
				}, $qinfo[$i]['qtext']);
		}
	}
	echo json_encode(array('since'=>$since, 'stage'=>1, 'nextoffset'=>$hasmoreq?($offset+$linecnt):-1, 'data'=>$qinfo));
	exit;
} else if ($stage == 3) { //send updated library items for unchanged questions
	$query = 'SELECT il.uniqueid,iq.uniqueid,ili.junkflag,ili.deleted FROM ';
	$query .= 'imas_libraries AS il JOIN imas_library_items AS ili ON il.id=ili.libid AND il.federationlevel>0 AND il.userights=8 ';
	$query .= 'JOIN imas_questionset AS iq ON iq.id=ili.qsetid ';
	$query .= 'WHERE ili.lastmoddate>? AND iq.lastmoddate<=? ';
	if (count($toskip)>0) {
		$query .= "AND ili.lastmoddate NOT IN ($skipph) ";
	}
	$stm = $DBH->prepare($query);
	if (count($toskip)>0) {
		$stm = $DBH->prepare($query);
		$stm->execute(array_merge(array($since,$since),$toskip));
	} else {
		$stm = $DBH->prepare($query);
		$stm->execute(array($since,$since));
	}
	$libitems = array();
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		 $libitems[] = array('ulibid'=>$row[0], 'uniqueid'=>$row[1], 'junkflag'=>$row[2], 'deleted'=>$row[3]);
	}
	$replacebys = array();
	$query = 'SELECT iq.uniqueid,iq2.uniqueid FROM imas_questionset AS iq ';
	$query .= 'LEFT JOIN imas_questionset AS iq2 ON iq.replaceby=iq2.id ';
	$query .= 'WHERE iq.replaceby>0 AND iq.lastmoddate>? ';
	if (count($toskip)>0) {
		$query .= "AND iq.lastmoddate NOT IN ($skipph) ";
	}
	$stm = $DBH->prepare($query);
	if (count($toskip)>0) {
		$stm = $DBH->prepare($query);
		$stm->execute(array_merge(array($since),$toskip));
	} else {
		$stm = $DBH->prepare($query);
		$stm->execute(array($since));
	}
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		if ($row[1]===null) {continue;}
		$replacebys[] = array('uniqueid'=>$row[0], 'replaceby'=>$row[1]);
	}

	echo json_encode(array('since'=>$since, 'stage'=>3, 'data'=>array('libitems'=>$libitems,'replacebys'=>$replacebys)), JSON_HEX_TAG);
	exit;
}
?>

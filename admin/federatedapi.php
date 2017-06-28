<?php
//IMathAS:  Federated libraries update send
//(c) 2017 David Lippman

exit; //not ready for use yet.

if (empty($_GET['peer']) || empty($_GET['since']) || !isset($_SERVER['HTTP_AUTHORIZATON'])) {
//	exit;
}
$since = intval($_GET['since']);
require("../init_without_validate.php");
require("../includes/filehandler.php");
/*
$stm = $DBH->prepare("SELECT id,secret FROM imas_federation_peers WHERE peername=:peername");
if (!$stm->execute(array(':peername'=>$_GET['peer']))) {
	echo '{error:"Unknown peer"}';
	exit;
}
$peer = $stm->fetch(PDO::FETCH_ASSOC);

if ($peer['secret'] != $_SERVER['HTTP_AUTHORIZATON']) {
	echo '{error:"Invalid authorization"}';
	exit;
}
*/
if (isset($_GET['stage'])) {
	$stage = intval($_GET['stage']);
} else {
	$stage = 0;
}

if ($stage == 0) { //send updated libraries
	$query = 'SELECT A.id,A.uniqueid,A.federationlevel,A.name,A.deleted,A.lastmoddate,A.parent,B.uniqueid as parentuid ';
	$query .= 'FROM imas_libraries AS A LEFT JOIN imas_libraries AS B ON A.parent=B.id ';
	$query .= 'WHERE A.lastmoddate>:since AND A.federationlevel>0 AND A.userights=8';
	$stm = $DBH->prepare($query);
	$stm->execute(array(':since'=>$since));
	$libs = array();
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		$libs[] = array('uid'=>$row['uniqueid'], 'fl'=>$row['federationlevel'], 'n'=>$row['name'],
			'd'=>$row['deleted'], 'lm'=>$row['lastmoddate'], 'p'=>$row['parent']==0?0:$row['parentuid']);
	}
	echo json_encode(array('since'=>$since, 'stage'=>0, 'data'=>$libs));

	//record this pull
	$now = time();
	$stm = $DBH->prepare('UPDATE imas_federation_peers SET lastpull=:now WHERE peername=:peername')
	$stm->execute(array(':now'=>$now, ':peername'=>$_GET['peer']));
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
	//TODO:  Handle replaceby, sourceinstall
	$query = 'SELECT iq.*,il.uniqueid AS ulibid,ili.junkflag,ili.deleted AS libdel FROM imas_questionset as iq ';
	$query .= 'JOIN imas_library_items AS ili ON iq.id=ili.qsetid AND iq.lastmoddate>:since AND iq.userights>1 AND iq.license>0 ';
	$query .= 'JOIN imas_libraries AS il ON il.id=ili.libid AND il.federationlevel>0 AND il.userights=8 ';
	$query .= 'ORDER BY iq.lastmoddate DESC LIMIT '.$batchsize.' OFFSET '.$offset;
	$stm = $DBH->prepare($query);

	$img_stm = $DBH->prepare("SELECT var,filename,alttext FROM imas_qimages WHERE qsetid=:qsetid");

	$stm->execute(array(':since'=>$since));
	$qinfo = array();
	$qcnt = -1; $lastq = -1; $linecnt = -1;
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		$linecnt++;
		if ($row['id']==$lastq) { //same question, different libid
			$qinfo[$qcnt]['libs'][] = array('l'=>$row['ulibid'], 'j'=>$row['junkflag'], 'd'=>$row['libdel']);
		} else { //new question
			//we need to stop before the full offset to ensure all the library entries
			//for a question are sent with the question
			if ($linecnt>.9*$batchsize) {break;}
			$qcnt++;
			$qinfo[$qcnt] = array('uid'=>$row['uniqueid'], 'lm'=>$row['lastmoddate'], 'au'=>$row['author'],
				'ds'=>$row['description'], 'qt'=>$row['qtype'], 'cc'=>$row['control'],
				'qc'=>$row['qcontrol'], 'qt'=>$row['qtext'], 'an'=>$row['answer'],
				'ex'=>$row['extref'], 'd'=>$row['deleted'], 'b'=>$row['broken'],
				'sl'=>$row['solution'], 'so'=>$row['solutionopts'], 'li'=>$row['license'],
				'aa'=>$row['ancestorauthors'], 'oa'=>$row['otherattribution'],
				'libs'=>array(array('l'=>$row['ulibid'], 'j'=>$row['junkflag'], 'd'=>$row['libdel'])),
				'is'=>array());
			if ($row['hasimg']>0) {
				$img_stm->execute(array(':qsetid'=>$row['id']));
				while ($imgrow = $img_stm->fetch(PDO::FETCH_ASSOC)) {
					$qinfo[$qcnt]['is'][] = array('v'=>$imgrow['var'],
						'f'=>getqimageurl($imgrow['filename'],true),
						'a'=>$imgrow['alttext']);
				}
			}
			$lastq = $row['id'];
		}
	}
	echo json_encode(array('since'=>$since, 'stage'=>1, 'nextoffset'=>($offset+$linecnt), 'data'=>$qinfo));
	exit;
} else if ($stage == 2) { //send updated library items for unchanged questions
	$query = 'SELECT il.uniqueid,iq.uniqueid,ili.junkflag,ili.deleted FROM ';
	$query .= 'imas_libraries AS il JOIN imas_library_items AS ili ON il.id=ili.libid AND il.federationlevel>0 AND il.userights=8 ';
	$query .= 'JOIN imas_questionset AS iq ON iq.id=ili.qsetid ';
	$query .= 'WHERE ili.lastmoddate>:since AND iq.lastmoddate<=:since2 ';
	$stm = $DBH->prepare($query);
	$stm->execute(array(':since'=>$since, ':since2'=>$since));
	$libitems = array();
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		 $libitems[] = array('l'=>$row[0], 'q'=>$row[1], 'j'=>$row[2], 'd'=>$row[3]);
	}
	echo json_encode(array('since'=>$since, 'stage'=>3, 'data'=>$libitems));
	exit;
}
?>

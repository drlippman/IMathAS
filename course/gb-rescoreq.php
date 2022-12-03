<?php
//IMathAS: Re-score a question
//(c) 2018 David Lippman

require("../init.php");
require("../assessment/displayq2.php");
require("../includes/ltioutcomes.php");

if (!isset($teacherid) && !isset($tutorid)) {
	require("../header.php");
	echo "You need to log in as a teacher or tutor to access this page";
	require("../footer.php");
	exit;
}

if (empty($_GET['cid']) || empty($_GET['aid']) || empty($_GET['qid']) || empty($_GET['qsid'])) {
	echo "Missing required info";
	exit;
}

$cid = Sanitize::onlyInt($_GET['cid']);
$aid = Sanitize::onlyInt($_GET['aid']); //imas_assessments id
$qid = Sanitize::onlyInt($_GET['qid']);
$qsid = Sanitize::onlyInt($_GET['qsid']);

function overwriteval($list, $loc, $val, $delim=',', $delim2='') {
	if ($delim2 != '') {
		$arr = explode($delim2, $list);
	} else {
		$arr = array($list);
	}
	foreach ($arr as $k=>$arrv) {
		$arr2 = explode($delim, $arrv);
		$arr2[$loc] = is_array($val)?$val[$k]:$val;
		$arr[$k] = implode($delim, $arr2);
	}
	return implode($delim2, $arr);
}

function getpts($sc) {
	if (strpos($sc,'~')===false) {
		if ($sc>0) {
			return $sc;
		} else {
			return 0;
		}
	} else {
		$sc = explode('~',$sc);
		$tot = 0;
		foreach ($sc as $s) {
			if ($s>0) {
				$tot+=$s;
			}
		}
		return round($tot,1);
	}
}

if (isset($_POST['record'])) {
	$qnref = array();
	$attemptref = array();
	foreach ($_POST['asidref'] as $v) {
		$vals = explode('-', $v);
		$qnref[$vals[1]] = $vals[0];
		$attemptref[$vals[1]] = $vals[2];
	}
	//get point value
	$stm = $DBH->prepare("SELECT points FROM imas_questions WHERE id=?");
	$stm->execute(array($qid));
	$points = $stm->fetchColumn(0);
	if ($points == 9999) {
		$stm = $DBH->prepare("SELECT defpoints FROM imas_assessments WHERE id=?");
		$stm->execute(array($aid));
		$points = $stm->fetchColumn(0);
	}
	
	//pull assessment_sessions records
	$query = "UPDATE imas_assessment_sessions SET scores=:scores,attempts=:attempts,seeds=:seeds,lastanswers=:lastanswers,";
	$query .= "bestattempts=:bestattempts,bestscores=:bestscores,bestlastanswers=:bestlastanswers,";
	$query .= "reattempting=:reattempting WHERE id=:id LIMIT 1";
	$updstm = $DBH->prepare($query);
		
	$query = "SELECT id,ver,questions,seeds,scores,attempts,lastanswers,reattempting,";
	$query .= "bestseeds,bestscores,bestattempts,bestlastanswers,lti_sourcedid,userid ";
	$query .= "FROM imas_assessment_sessions WHERE assessmentid=?";
	$stm = $DBH->prepare($query);
	$stm->execute(array($aid));
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		if (!isset($qnref[$row['id']])) {
			continue; //don't have it for some reason
		}
		$dispqn = $qnref[$row['id']];
		$GLOBALS['assessver'] = $row['ver'];
		$qlist = explode(';', $row['questions']);
		if (count($qlist)>1) {
			$qlist = explode(',', $qlist[1]);
		} else {
			$qlist = explode(',', $qlist[0]);
		}
		$qloc = array_search($qid, $qlist);
		if ($qloc === false) {
			continue; //question isn't in this student's list
		}
		$seedlist = explode(',', $row['bestseeds']);
		$seed = $seedlist[$qloc];
		
		//score it
		$lastanswers = array();
		list($score,$rawscore) = scoreq($dispqn, $qsid, $seed, $_POST['qn'.$dispqn], $attemptref[$dispqn], $points);
		if (strpos($score,'~')===false) {
			$after = round($score*$points,1);
			if ($after < 0) { $after = 0;}
		} else {
			$fparts = explode('~',$score);
			foreach ($fparts as $k=>$fpart) {
				$after[$k] = round($fpart*$points,2);
				if ($after[$k]<0) {$after[$k]=0;}
			}
			$after = implode('~',$after);
		}
		$score = $after;
		
		//record it
		// overwriteval($list, $loc, $val, $delim=',', $delim2='')
		$seedslist = overwriteval($row['seeds'], $qloc, $seed);
		$bestscorelist = overwriteval($row['bestscores'], $qloc, array($score, $rawscore, $rawscore), ',', ';');
		$scorelist = overwriteval($row['scores'], $qloc, array($score, $rawscore, $rawscore), ',', ';');
		$bestattemptslist = overwriteval($row['bestattempts'], $qloc, 1);
		$attemptslist = overwriteval($row['attempts'], $qloc, 1);
		$bestlalist = overwriteval($row['bestlastanswers'], $qloc, $lastanswers[$dispqn], '~');
		$lalist = overwriteval($row['lastanswers'], $qloc, $lastanswers[$dispqn], '~');
		$reattempting = explode(',', $row['reattempting']);
		$rloc = array_search($qloc,$reattempting);
		if ($rloc!==false) {
			array_splice($reattempting,$rloc,1);
		}
		$reattemptinglist = implode(',', $reattempting);
		
		$updstm->execute(array(':id'=>$row['id'], ':scores'=>$scorelist, ':attempts'=>$attemptslist, ':seeds'=>$seedslist, 
			':lastanswers'=>$lalist, ':bestattempts'=>$bestattemptslist, ':bestscores'=>$bestscorelist,
			':bestlastanswers'=>$bestlalist, ':reattempting'=>$reattemptinglist));
		
		if (strlen($row['lti_sourcedid'])>1) {
			$bsarr = explode(';', $bestscorelist);
			$bs = explode(',', $bsarr[0]);
			calcandupdateLTIgrade($row['lti_sourcedid'],$aid,$row['userid'],$bs,true, -1, false);
		}
	}
	
	header('Location: ' . $GLOBALS['basesiteurl'] ."/course/addquestions.php?cid=$cid&aid=$aid");

	exit;
} else if (isset($_POST['go'])) {

	$placeinhead = '<script type="text/javascript">
	$(function() {
		doonsubmit(document.getElementById("rescoreform"),false,true);
		$("#rescoreform").submit();
	});
	</script>';
    $useeqnhelper = 0;
	require("../assessment/header.php");
	echo '<h1>'._('Regrade Question').'</h1>';
	echo '<p>'._('Please be patient - this page will auto-submit when it is done loading').'</p>';
	
	echo '<form id="rescoreform" method="post" action="gb-rescoreq.php?cid='.$cid.'&aid='.$aid.'&qid='.$qid.'&qsid='.$qsid.'" >';
	echo '<input type=hidden name=record value=1 />';
	
	//get question code
	$stm = $DBH->prepare("SELECT qtype,control,qcontrol,qtext,answer,hasimg,extref,solution,solutionopts FROM imas_questionset WHERE id=:id");
	$stm->execute(array(':id'=>$qsid));
	$qdatafordisplayq = $stm->fetch(PDO::FETCH_ASSOC);
	
	//pull assessment_sessions records
	$cnt = 0;
	$stm = $DBH->prepare("SELECT id,questions,bestseeds,bestlastanswers,ver FROM imas_assessment_sessions WHERE assessmentid=?");
	$stm->execute(array($aid));
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		$GLOBALS['assessver'] = $row['ver'];
		$qlist = explode(';', $row['questions']);
		if (count($qlist)>1) {
			$qlist = explode(',', $qlist[1]);
		} else {
			$qlist = explode(',', $qlist[0]);
		}
		$qloc = array_search($qid, $qlist);
		if ($qloc === false) {
			continue; //question isn't in this student's list
		}
		$lalist = explode('~', $row['bestlastanswers']);
		$laarr = explode('##',str_replace('ReGen##','',$lalist[$qloc]));

		if ($_POST['vertouse']==1) {
			$attemptn = count($laarr)-1;
			$la = $laarr[$attemptn];
		} else {
			$la = $laarr[0];
			$attemptn = 0;
		}
		$seedlist = explode(',', $row['bestseeds']);
		$seed = $seedlist[$qloc];
	
		echo '<input type="hidden" name="asidref[]" value="'.Sanitize::encodeStringForDisplay($cnt.'-'.$row['id'].'-'.$attemptn).'" />';
		$lastanswers = array($cnt => $la);
		displayq($cnt, $qsid, $seed, false, false, $attemptn);
		$cnt++;
	}
	echo '</form>';
	require("../footer.php");
} else {
	require("../header.php");
	echo "<div class=breadcrumb>$breadcrumbbase <a href=\"course.php?cid=".Sanitize::courseId($cid)."\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
	echo "&gt; <a href=\"addquestions.php?cid=$cid&aid=$aid\">"._('Add/Remove Questions')."</a> ";
	echo "&gt; "._('Regrade Question').'</div>';
	
	echo '<div id="headergb-rescoreq" class="pagetitle"><h1>'._('Regrade Question').'</h1></div>';
	
	echo '<form method="post" action="gb-rescoreq.php?cid='.$cid.'&aid='.$aid.'&qid='.$qid.'&qsid='.$qsid.'">';
	echo '<input type=hidden name=go value=1 />';
	
	echo '<p>'._('This will allow you rescore this question. ');
	echo _('This is intended to be used after fixing a bug in the question code. ').'</p>';
	echo '<p>'._('This page will re-submit the student\'s answer to their best-scored version of this question (the latest version if all are 0), scoring it as if it was their first attempt. ');
	echo _('This process will wipe out all record of other attempts the student made on this question, and reset their attempt count to 1 on this question. ').'</p>';
	
	echo '<p>'._('Which answer from the student do you want to resubmit?').'<br/>';
	echo ' <label><input type=radio name=vertouse value=0 checked /> ';
	echo _('The first attempt. Recommended for single-part questions, to rescore the student\'s first try.').'</label><br/>';
	echo ' <label><input type=radio name=vertouse value=1 /> ';
	echo _('The last attempt. Recommended for multi-part questions, to preserve the score on working parts.').'</label></p>';
	
	echo '<p>'._('Are you SURE you want to proceed?').'</p>';
	echo '<p><button type="submit">'._('Rescore Question').'</button></p>';
	echo '</form>';
	
	echo '<p>'._('When you continue, all students\' attempts will be loaded up on your screen and then automatically submitted for regrading. ');
	echo _('Please be patient - it may take a minute or two').'</p>';
	
	require("../footer.php");
}
		
<?php
//IMathAS:  Test display utility functions
//(c) 2007 David Lippman

//Returns Question settings for a single or set of questions
//qns:  array of or single question ids
//testsettings: assoc array of assessment settings
//Returns:  id,questionsetid,category,points,penalty,attempts
function getquestioninfo($qns,$testsettings) {
	if (!is_array($qns)) {
		$qns = array($qns);
	} 
	$qnlist = "'".implode("','",$qns)."'";	
	$query = "SELECT iq.id,iq.questionsetid,iq.category,iq.points,iq.penalty,iq.attempts,iq.regen,iq.showans,il.name FROM imas_questions AS iq LEFT JOIN imas_libraries as il ";
	$query .= "ON iq.category=il.id WHERE iq.id IN ($qnlist)";
	$result = mysql_query($query) or die("Query failed: $query: " . mysql_error());
	while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		if ($line['name']!=null) {
			$line['category'] = $line['name'];
		}
		unset($line['name']);
		if ($line['points']==9999) {
			$line['points'] = $testsettings['defpoints'];
		}
		if ($line['attempts']==9999) {
			$line['attempts'] = $testsettings['defattempts'];
		}
		$out[$line['id']] = $line;
	}
	return $out;
}

//calculates points after penalty
//frac: decimal showing partial credit
//qi:  getquestioninfo[qid]
//attempts: scalar attempts on question
//testsettings: assoc array of assessment settings
function calcpointsafterpenalty($frac,$qi,$testsettings,$attempts) {
	$points = $qi['points'];
	$penalty = $qi['penalty'];
	$lastonly = $false;
	$skipsome = 0;
	if ($penalty{0}==='L') {
		$lastonly = true;
		$penalty = substr($penalty,1);
	} else if ($penalty{0}==='S') {
		$skipsome = $penalty{1};
		$penalty = substr($penalty,2);
	}
	if ($penalty == 9999) { 
		$penalty = $testsettings['defpenalty'];
		if ($penalty{0}==='L') {
			$lastonly = true;
			$penalty = substr($penalty,1);
		} else if ($penalty{0}==='S') {
			$skipsome = $penalty{1};
			$penalty = substr($penalty,2);
		}
	}
	$rowatt = $qi['attempts'];
	
	if ($attempts<$rowatt || $rowatt==0) { //has remaining attempts
		if ($lastonly && $rowatt>0 && $attempts+1<$rowatt) {
			$penalty = 0;
		} else if ($lastonly && $rowatt>0) {
			$attempts = 1;
		} else if ($skipsome>0) {
			$attempts = $attempts - $skipsome;
			if ($attempts<0) {
				$attempts = 0;
			}
		}
		if ($lastonly && $rowatt==1) { //no penalty if only one attempt is allowed!
			$penalty = 0;
		}
		if (strpos($frac,'~')===false) {
			$after = round($frac*$points - $points*$attempts*$penalty/100.0,1);
			if ($after < 0) { $after = 0;}
		} else {
			$fparts = explode('~',$frac);
			foreach ($fparts as $k=>$fpart) {
				$after[$k] = round($fpart*$points*(1 - $attempts*$penalty/100.0),2);
				if ($after[$k]<0) {$after[$k]=0;}
			}
			$after = implode('~',$after);
		}
		return $after;
	} else { //no remaining attempts
		return 0;
	}
}

//Get total of points possible
function totalpointspossible($qi) {
	$poss = 0;
	foreach($qi as $qii) {
		$poss += $qii['points'];
	}
	return $poss;
}
//Get remaining possible points on a question
//qi:  getquestioninfo[qid]
//attempts: scalar attempts on question
//testsettings: assoc array of assessment settings
function getremainingpossible($qi,$testsettings,$attempts) {
	$possible = calcpointsafterpenalty(1,$qi,$testsettings,$attempts);
	if ($possible<0) { $possible = 0;}
	return $possible;
}

//Get remaining possible points on all questions
//qi:  getquestioninfo
//questions: array of question ids
//attempts: array of attempts on questions, indexed same as questions
//testsettings: assoc array of assessment settings 
//returns array indexed same as questions
function getallremainingpossible($qi,$questions,$testsettings,$attempts) {
	$rposs = array();
	foreach($questions as $k=>$qid) {
		$rposs[$k] = calcpointsafterpenalty(1,$qi[$qid],$testsettings,$attempts[$k]);
	}
	return $rposs;
}

//calculates points based on return from scoreq
function getpts($sc) {
	if (strpos($sc,'~')===false) {
		return $sc;
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

//determines if a question has not been attempted
function unans($sc) {
	if (strpos($sc,'~')===false) {
		return ($sc<0);
	} else {
		return (strpos($sc,'-1')!==FALSE);
	}
}

//creates display of score  (chg from previous: does not echo self)
function printscore($sc,$poss) {
	if (strpos($sc,'~')===false) {
		$sc = str_replace('-1','N/A',$sc);
		$out =  "$sc out of $poss";
	} else {
		$pts = getpts($sc);
		$sc = str_replace('-1','N/A',$sc);
		$sc = str_replace('~',', ',$sc);
		$out =  "$pts out of $poss (parts: $sc)";
	}	
	return $out;	
}

//scores a question
//qn: question index in questions array
//qi: getquestioninfo[qid]
function scorequestion($qn) { 
	global $questions,$scores,$seeds,$testsettings,$qi,$attempts,$lastanswers,$isreview,$bestseeds,$bestscores,$bestattempts,$bestlastanswers;
	//list($qsetid,$cat) = getqsetid($questions[$qn]);
	$scores[$qn] = calcpointsafterpenalty(scoreq($qn,$qi[$questions[$qn]]['questionsetid'],$seeds[$qn],$_POST["qn$qn"]),$qi[$questions[$qn]],$testsettings,$attempts[$qn]);
	$attempts[$qn]++;
	
	if (getpts($scores[$qn])>getpts($bestscores[$qn]) && !$isreview) {
		$bestseeds[$qn] = $seeds[$qn];
		$bestscores[$qn] = $scores[$qn];
		$bestattempts[$qn] = $attempts[$qn];
		$bestlastanswers[$qn] = $lastanswers[$qn];
	}
}

//records everything but questions array
//if limit=true, only records lastanswers
function recordtestdata($limit=false) { 
	global $bestscores,$bestattempts,$bestseeds,$bestlastanswers,$scores,$attempts,$seeds,$lastanswers,$testid,$testsettings,$sessiondata;
	$bestscorelist = implode(',',$bestscores);
	$bestattemptslist = implode(',',$bestattempts);
	$bestseedslist = implode(',',$bestseeds);
	$bestlastanswers = str_replace('~','',$bestlastanswers);
	$bestlalist = implode('~',$bestlastanswers);
	$bestlalist = addslashes(stripslashes($bestlalist));
	
	$scorelist = implode(',',$scores);
	$attemptslist = implode(',',$attempts);
	$seedslist = implode(',',$seeds);
	$lastanswers = str_replace('~','',$lastanswers);
	$lalist = implode('~',$lastanswers);
	$lalist = addslashes(stripslashes($lalist));
	$now = time();
	if ($limit) {
		$query = "UPDATE imas_assessment_sessions SET lastanswers='$lalist' ";
	} else {
		$query = "UPDATE imas_assessment_sessions SET scores='$scorelist',attempts='$attemptslist',seeds='$seedslist',lastanswers='$lalist',";
		$query .= "bestseeds='$bestseedslist',bestattempts='$bestattemptslist',bestscores='$bestscorelist',bestlastanswers='$bestlalist',";
		$query .= "endtime=$now ";
	}
	if ($testsettings['isgroup']>0 && $sessiondata['groupid']>0) {
		$query .= "WHERE agroupid='{$sessiondata['groupid']}'";
	} else {
		$query .= "WHERE id='$testid' LIMIT 1";
	}
	
	mysql_query($query) or die("Query failed : $query " . mysql_error());
}

//can improve question score?
function canimprove($qn) {
	global $qi,$scores,$attempts,$questions,$testsettings;	
	$remainingposs = getremainingpossible($qi[$questions[$qn]],$testsettings,$attempts[$qn]);
	if ($attempts[$qn]<$qi[$questions[$qn]]['attempts'] || $qi[$questions[$qn]]['attempts']==0) {
		if (getpts($scores[$qn])<$remainingposs) {
			return true;
		} 
	}
	return false;
}

//can improve any question?
function canimproveany() {
	global $questions;
	for ($i=0;$i<count($questions);$i++) {
		if (canimprove($i)) {
			return true;
		}
	}
	return false;
}

//basic show question, for
function basicshowq($qn,$seqinactive=false) {
	global $showansduring,$questions,$testsettings,$qi,$seeds,$showhints,$attempts,$regenonreattempt,$showansafterlast,$showeachscore;
	$qshowansduring = ($showansduring && $qi[$questions[$qn]]['showans']=='0');
	$qshowansafterlast = (($showansafterlast && $qi[$questions[$qn]]['showans']=='0') || $qi[$questions[$qn]]['showans']=='F');
	
	if (canimprove($qn)) {
		if ($qshowansduring && $attempts[$qn]>=$testsettings['showans']) {$showa = true;} else {$showa=false;}
	} else {
		$showa = ($qshowansafterlast && $showeachscore);	
	}
	$regen = (($regenonreattempt && $qi[$questions[$qn]]['regen']==0) || $qi[$questions[$qn]]['regen']==1);
	if (!$seqinactive) {
		displayq($qn,$qi[$questions[$qn]]['questionsetid'],$seeds[$qn],$showa,$showhints,$attempts[$qn],false,$regen,$seqinactive);
	} else {
		displayq($qn,$qi[$questions[$qn]]['questionsetid'],$seeds[$qn],$showa,false,$attempts[$qn],false,$regen,$seqinactive);
	}
}

//shows basic points possible, attempts remaining bar
function showqinfobar($qn,$inreview,$single) {
	global $qi,$questions,$attempts,$testsettings;
	if ($inreview) {
		echo '<div class="review">';
	}
	echo 'Points possible: ' . $qi[$questions[$qn]]['points'];
	if ($qi[$questions[$qn]]['attempts']==0) {
		echo "<br/>Unlimited attempts";
	} else {
		echo '<br/>'.($qi[$questions[$qn]]['attempts']-$attempts[$qn])." attempts of ".$qi[$questions[$qn]]['attempts']." remaining.";
	}
	if ($testsettings['showcat']>0 && $qi[$questions[$qn]]['category']!='0') {
		echo "  Category: {$qi[$questions[$qn]]['category']}.";
	}
	if ($single) {
		echo "<input type=hidden name=\"verattempts\" value=\"{$attempts[$qn]}\" />";
	} else {
		echo "<input type=hidden name=\"verattempts[$qn]\" value=\"{$attempts[$qn]}\" />";
	}
	if ($inreview) {
		echo '</div>';
	}
}
?>

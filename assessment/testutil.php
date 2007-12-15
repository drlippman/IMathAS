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
	$query = "SELECT iq.id,iq.questionsetid,iq.category,iq.points,iq.penalty,iq.attempts,iq.regen,iq.showans,iq.withdrawn,il.name FROM imas_questions AS iq LEFT JOIN imas_libraries as il ";
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
	global $inexception;
	$points = $qi['points'];
	if ($inexception) {
		$points = $points*(1-$testsettings['exceptionpenalty']/100);
	}
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
	if (is_array($qi)) {
		foreach($qi as $qii) {
			$poss += $qii['points'];
		}
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

//creates display of score  (chg from previous: does not echo self)
function printscore2($sc) {
	if (strpos($sc,'~')===false) {
		$sc = str_replace('-1','N/A',$sc);
		$out =  "$sc";
	} else {
		$pts = getpts($sc);
		$sc = str_replace('-1','N/A',$sc);
		$sc = str_replace('~',', ',$sc);
		$out =  "$pts (parts: $sc)";
	}	
	return $out;	
}

//scores a question
//qn: question index in questions array
//qi: getquestioninfo[qid]
function scorequestion($qn) { 
	global $questions,$scores,$seeds,$testsettings,$qi,$attempts,$lastanswers,$isreview,$bestseeds,$bestscores,$bestattempts,$bestlastanswers;
	//list($qsetid,$cat) = getqsetid($questions[$qn]);
	$rawscore = scoreq($qn,$qi[$questions[$qn]]['questionsetid'],$seeds[$qn],$_POST["qn$qn"]);
	$scores[$qn] = calcpointsafterpenalty($rawscore,$qi[$questions[$qn]],$testsettings,$attempts[$qn]);
	$rawscore = calcpointsafterpenalty($rawscore,$qi[$questions[$qn]],$testsettings,0);
	$attempts[$qn]++;
	
	if (getpts($scores[$qn])>=getpts($bestscores[$qn]) && !$isreview) {
		$bestseeds[$qn] = $seeds[$qn];
		$bestscores[$qn] = $scores[$qn];
		$bestattempts[$qn] = $attempts[$qn];
		$bestlastanswers[$qn] = $lastanswers[$qn];
	}
	return $rawscore;
}

//records everything but questions array
//if limit=true, only records lastanswers
function recordtestdata($limit=false) { 
	global $isreview,$bestscores,$bestattempts,$bestseeds,$bestlastanswers,$scores,$attempts,$seeds,$lastanswers,$testid,$testsettings,$sessiondata;
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
	if ($isreview) {
		if ($limit) {
			$query = "UPDATE imas_assessment_sessions SET reviewlastanswers='$lalist' ";
		} else {
			$query = "UPDATE imas_assessment_sessions SET reviewscores='$scorelist',reviewattempts='$attemptslist',reviewseeds='$seedslist',reviewlastanswers='$lalist',";
			$query .= "endtime=$now ";
		}
	} else {
		if ($limit) {
			$query = "UPDATE imas_assessment_sessions SET lastanswers='$lalist' ";
		} else {
			$query = "UPDATE imas_assessment_sessions SET scores='$scorelist',attempts='$attemptslist',seeds='$seedslist',lastanswers='$lalist',";
			$query .= "bestseeds='$bestseedslist',bestattempts='$bestattemptslist',bestscores='$bestscorelist',bestlastanswers='$bestlalist',";
			$query .= "endtime=$now ";
		}
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
	if (hasreattempts($qn)) {
		if (getpts($scores[$qn])<$remainingposs) {
			return true;
		} 
	}
	return false;
}

//can improve question bestscore?
function canimprovebest($qn) {
	global $qi,$bestscores,$scores,$attempts,$questions,$testsettings;	
	$remainingposs = getremainingpossible($qi[$questions[$qn]],$testsettings,$attempts[$qn]);
	if (hasreattempts($qn)) {
		if (getpts($bestscores[$qn])<$remainingposs) {
			return true;
		} 
	}
	return false;
}

//do more attempts remain?
function hasreattempts($qn) {
	global $qi,$attempts,$questions,$testsettings;	
	if ($attempts[$qn]<$qi[$questions[$qn]]['attempts'] || $qi[$questions[$qn]]['attempts']==0) {
		return true;
	}
	return false;
}

//do any questions have attempts remaining?
function hasreattemptsany() {
	global $questions;
	for ($i=0;$i<count($questions);$i++) {
		if (hasreattempts($i)) {
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
	global $qi,$questions,$attempts,$testsettings,$noindivscores,$scores,$bestscores;
	if ($inreview) {
		echo '<div class="review">';
	}
	if ($qi[$questions[$qn]]['withdrawn']==1) {
		echo '<span class="red">Question Withdrawn</span> ';
	}
	$pointsremaining = getremainingpossible($qi[$questions[$qn]],$testsettings,$attempts[$qn]);
	if ($pointsremaining == $qi[$questions[$qn]]['points']) {
		echo 'Points possible: ' . $qi[$questions[$qn]]['points'];
	} else {
		echo 'Points available on this attempt: '.$pointsremaining.' of original '.$qi[$questions[$qn]]['points'];
	}
	
	if ($qi[$questions[$qn]]['attempts']==0) {
		echo "<br/>Unlimited attempts.";
	} else {
		//echo '<br/>'.($qi[$questions[$qn]]['attempts']-$attempts[$qn])." attempts of ".$qi[$questions[$qn]]['attempts']." remaining.";
		echo "<br/>This is attempt ".($attempts[$qn]+1)." of " . $qi[$questions[$qn]]['attempts'] . ".";
	}
	if ($testsettings['showcat']>0 && $qi[$questions[$qn]]['category']!='0') {
		echo "  Category: {$qi[$questions[$qn]]['category']}.";
	}
	//if (!$noindivscores) {
	//	echo "<br/>Score in gradebook: ".printscore2($bestscores[$qn]).".";
	//}
	if ($single) {
		echo "<input type=hidden name=\"verattempts\" value=\"{$attempts[$qn]}\" />";
	} else {
		echo "<input type=hidden name=\"verattempts[$qn]\" value=\"{$attempts[$qn]}\" />";
	}
	if ($inreview) {
		echo '</div>';
	}
}

//shows top info bar for seq mode
function seqshowqinfobar($qn,$toshow) {
	global $qi,$questions,$attempts,$testsettings,$scores;
	$reattemptsremain = hasreattempts($qn);
	$pointsremaining = getremainingpossible($qi[$questions[$qn]],$testsettings,$attempts[$qn]);
	$qavail = false;
	if ($qi[$questions[$qn]]['withdrawn']==1) {
		$qlinktxt = "<span class=\"withdrawn\">Question ".($qn+1)."</span>";
	} else {
		$qlinktxt = "Question ".($qn+1);
	}
	if ($qn==$toshow) {
		if (unans($scores[$qn]) && $attempts[$qn]==0) {
			echo "<img src=\"$qnmasroot/img/q_fullbox.gif\"/> ";
		} else {
			echo "<img src=\"$qnmasroot/img/q_halfbox.gif\"/> ";
		}
		echo "<span class=current><a name=\"curq\">$qlinktxt</a></span>  ";
	} else {
		if (unans($scores[$qn]) && $attempts[$qn]==0) {
			echo "<img src=\"$qnmasroot/img/q_fullbox.gif\"/> ";
			echo "<a href=\"showtest.php?action=seq&to=$qn#curq\">$qlinktxt</a>.  ";
			$qavail = true;
		} else if (canimprove($qn)) {
			echo "<img src=\"$qnmasroot/img/q_halfbox.gif\"/> ";
			echo "<a href=\"showtest.php?action=seq&to=$qn#curq\">$qlinktxt</a>.  ";
			$qavail = true;
		} else if ($reattemptsremain) {
			echo "<img src=\"$qnmasroot/img/q_emptybox.gif\"/> ";
			echo "<a href=\"showtest.php?action=seq&to=$qn#curq\">$qlinktxt</a>.  ";
		} else {
			echo "<img src=\"$qnmasroot/img/q_emptybox.gif\"/> ";
			echo "$qlinktxt.  ";
		}
	}
	if ($qi[$questions[$qn]]['withdrawn']==1) {
		echo "<span class=\"red\">Question Withdrawn</span> ";
	}
	if ($showeachscore) {
		$pts = getpts($bestscores[$qn]);
		if ($pts<0) { $pts = 0;}
		echo "Points: $pts out of " . $qi[$questions[$qn]]['points'] . " possible.  ";
		if ($qn==$toshow) {
			echo "$pointsremaining available on this attempt.";
		}
	} else {
		if ($pointsremaining == $qi[$questions[$qn]]['points']) {
			echo "Points possible: ". $qi[$questions[$qn]]['points'];
		} else {
			echo 'Points available on this attempt: '.$pointsremaining.' of original '.$qi[$questions[$qn]]['points'];
		}
		
	}
	
	if ($qn==$toshow && $attempts[$qn]<$qi[$questions[$qn]]['attempts']) {
		if ($qi[$questions[$qn]]['attempts']==0) {
			echo ".  Unlimited attempts";
		} else {
			echo '.  This is attempt '.($attempts[$qn]+1)." of ".$qi[$questions[$qn]]['attempts'].".";
		}
	}
	if ($testsettings['showcat']>0 && $qi[$questions[$qn]]['category']!='0') {
		echo "  Category: {$qi[$questions[$qn]]['category']}.";
	}
	return $qavail;
}

//shows start of test message if no reattempts
function startoftestmessage($perfectscore,$hasreattempts,$allowregen,$noindivscores,$noscores) {
	if ($perfectscore && !$noscores) {
		echo "<p>Assessment is complete with perfect score.</p>";
	}
	if ($hasreattempts) {
		if ($noindivscores) {
			echo "<p><a href=\"showtest.php?reattempt=all\">Reattempt test</a> on questions allowed (note: all scores, correct and incorrect, will be cleared)</p>";
		} else {
			echo "<p><a href=\"showtest.php?reattempt=all\">Reattempt test</a> on questions missed where allowed</p>";
		}
	} else {
		echo "<p>No attempts left on current versions of questions.</p>\n";
	}
	if ($allowregen) {
		if ($perfectscore) {
			echo "<p><a href=\"showtest.php?regenall=all\">Try similar problems</a> for all questions.</p>";
		} else {
			echo "<p><a href=\"showtest.php?regenall=missed\">Try similar problems</a> for all questions with less than perfect scores.</p>";
		}
	}
}
?>

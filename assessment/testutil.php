<?php
//IMathAS:  Test display utility functions
//(c) 2007 David Lippman

//Returns Question settings for a single or set of questions
//qns:  array of or single question ids
//testsettings: assoc array of assessment settings
//Returns:  id,questionsetid,category,points,penalty,attempts
function getquestioninfo($qns,$testsettings,$preloadqsdata=false) {
	global $DBH;
	if (!is_array($qns)) {
		$qns = array($qns);
	}
	//DB $qnlist = "'".implode("','",$qns)."'";
	$qnlist = implode(',', array_map('intval', $qns));
	if ($testsettings['defoutcome']!=0) {
		//we'll need to run two simpler queries rather than a single join query
		$outcomenames = array();
		//DB $query = "SELECT id,name FROM imas_outcomes WHERE courseid='{$testsettings['courseid']}'";
		//DB $result = mysql_query($query) or die("Query failed: $query: " . mysql_error());
		//DB while ($row = mysql_fetch_row($result)) {
		$stm = $DBH->prepare("SELECT id,name FROM imas_outcomes WHERE courseid=:courseid");
		$stm->execute(array(':courseid'=>$testsettings['courseid']));
		while ($row = $stm->fetch(PDO::FETCH_NUM)) {
			$outcomenames[$row[0]] = $row[1];
		}
		//DB $query = "SELECT iq.id,iq.questionsetid,iq.category,iq.points,iq.penalty,iq.attempts,iq.regen,iq.showans,iq.withdrawn,iq.showhints,iqs.qtype,iqs.control ";
		//DB $query .= "FROM imas_questions AS iq JOIN imas_questionset AS iqs ON iq.questionsetid=iqs.id WHERE iq.id IN ($qnlist)";
		if ($preloadqsdata) {
			$query = "SELECT iq.id AS qid,iq.questionsetid,iq.category,iq.points,iq.penalty,iq.attempts,iq.regen,iq.showans,iq.withdrawn,iq.showhints,iq.fixedseeds,";
			$query .= "iqs.qtype,iqs.control,iqs.qcontrol,iqs.qtext,iqs.answer,iqs.hasimg,iqs.extref,iqs.solution,iqs.solutionopts ";
		} else {
			$query = "SELECT iq.id AS qid,iq.questionsetid,iq.category,iq.points,iq.penalty,iq.attempts,iq.regen,iq.showans,iq.withdrawn,iq.showhints,iqs.qtype,iqs.control,iq.fixedseeds ";
		}
		$query .= "FROM imas_questions AS iq JOIN imas_questionset AS iqs ON iq.questionsetid=iqs.id WHERE iq.id IN ($qnlist)";
		$stm = $DBH->query($query);
	} else {
		//DB $query = "SELECT iq.id,iq.questionsetid,iq.category,iq.points,iq.penalty,iq.attempts,iq.regen,iq.showans,iq.withdrawn,iq.showhints,io.name,iqs.qtype,iqs.control ";
		//DB $query .= "FROM (imas_questions AS iq JOIN imas_questionset AS iqs ON iq.questionsetid=iqs.id) LEFT JOIN imas_outcomes as io ";
		//DB $query .= "ON iq.category=io.id WHERE iq.id IN ($qnlist)";
		if ($preloadqsdata) {
			$query = "SELECT iq.id AS qid,iq.questionsetid,iq.category,iq.points,iq.penalty,iq.attempts,iq.regen,iq.showans,iq.withdrawn,iq.showhints,io.name,iq.fixedseeds,";
			$query .= "iqs.qtype,iqs.control,iqs.qcontrol,iqs.qtext,iqs.answer,iqs.hasimg,iqs.extref,iqs.solution,iqs.solutionopts ";
		} else {
			$query = "SELECT iq.id AS qid,iq.questionsetid,iq.category,iq.points,iq.penalty,iq.attempts,iq.regen,iq.showans,iq.withdrawn,iq.showhints,io.name,iqs.qtype,iqs.control,iq.fixedseeds ";
		}
		$query .= "FROM (imas_questions AS iq JOIN imas_questionset AS iqs ON iq.questionsetid=iqs.id) LEFT JOIN imas_outcomes as io ";
		$query .= "ON iq.category=io.id WHERE iq.id IN ($qnlist)";
		$stm = $DBH->query($query);
	}
	//DB $result = mysql_query($query) or die("Query failed: $query: " . mysql_error());
	//DB while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
	while ($line = $stm->fetch(PDO::FETCH_ASSOC)) {
		if (is_numeric($line['category'])) {
			if ($testsettings['defoutcome']!=0) {
				if ($line['category']==0) {
					$line['category'] = $outcomenames[$testsettings['defoutcome']];
				} else {
					$line['category'] = $outcomenames[$line['category']];
				}
			} else if ($line['name']!=null) {
				$line['category'] = $line['name'];
			}
		}
		unset($line['name']);
		if ($line['points']==9999) {
			$line['points'] = $testsettings['defpoints'];
		}
		if ($line['attempts']==9999) {
			$line['attempts'] = $testsettings['defattempts'];
		}
		if ($line['qtype']=='multipart') {
			//if (preg_match('/answeights\s*=\s*("|\')([\d\.\,\s]+)/',$line['control'],$match)) {
			/*$foundweights = false;
			if (($p = strpos($line['control'],'answeights'))!==false || strpos($line['control'],'anstypes')===false) {
				$p = strpos($line['control'],"\n",$p);
				$weights = getansweights($line['id'],$line['control']);
				if (is_array($weights)) {
					$line['answeights'] = $weights;
					$foundweights = true;
				}

			}
			if (!$foundweights) {
				if (preg_match('/anstypes\s*=(.*)/',$line['control'],$match)) {
					$n = substr_count($match[1],',')+1;
					if ($n>1) {
						$line['answeights'] = array_fill(0,$n-1,round(1/$n,5));
						$line['answeights'][] = 1-array_sum($line['answeights']);
					} else {
						$line['answeights'] = array(1);
					}
				} else {
					$line['answeights'] = getansweights($line['id'],$line['control']);
				}
			}
			*/
			$line['answeights'] = getansweights($line['id'],$line['control']);
		}
		$line['allowregen'] = 1-floor($line['regen']/3);  //0 if no, 1 if use default
		$line['regen'] = $line['regen']%3;
		if ($line['showans']=='0') {
			$line['showans'] = $testsettings['showans'];
		}
		$line['showansduring'] = (is_numeric($line['showans']) && $line['showans'] >= 0);
		$line['showansafterlast'] = ($line['showans']==='F' || $line['showans']==='R' || $line['showans']==='J');
		if (!$preloadqsdata) {
			unset($line['qtype']);
			unset($line['control']);
		}
		$out[$line['qid']] = $line;
	}
	return $out;
}

//evals a portion of the control section to extract the $answeights
//which might be randomizer determined, hence the seed
function getansweights($qi,$code) {
	global $seeds,$questions,$attempts;
	if (preg_match('/scoremethod\s*=\s*"(singlescore|acct|allornothing)"/', $code)) {
		return array(1);
	}
	$i = array_search($qi,$questions);
	return sandboxgetweights($code,$seeds[$i],$attempts[$i]);
}

function sandboxgetweights($code,$seed,$attemptn) {
	srand($seed);
	$code = interpret('control','multipart',$code);
	if (($p=strrpos($code,'answeights'))!==false) {
		$np = strpos($code,"\n",$p);
		if ($np !== false) {
			$code = substr($code,0,$np).';if(isset($answeights)){return;};'.substr($code,$np);
		} else {
			$code .= ';if(isset($answeights)){return;};';
		}
		//$code = str_replace("\n",';if(isset($answeights)){return;};'."\n",$code);
	} else {
		$p=strrpos($code,'answeights');
		$np = strpos($code,"\n",$p);
		if ($np !== false) {
			$code = substr($code,0,$np).';if(isset($anstypes)){return;};'.substr($code,$np);
		} else {
			$code .= ';if(isset($answeights)){return;};';
		}
		//$code = str_replace("\n",';if(isset($anstypes)){return;};'."\n",$code);
	}
	eval($code);
	if (!isset($answeights)) {
		if (!is_array($anstypes)) {
			$anstypes = explode(",",$anstypes);
		}
		$n = count($anstypes);
		if ($n>1) {
			$answeights = array_fill(0,$n-1,round(1/$n,5));
			$answeights[] = 1-array_sum($answeights);
		} else {
			$answeights = array(1);
		}
	} else if (!is_array($answeights)) {
		$answeights =  explode(',',$answeights);
	}
	$sum = array_sum($answeights);
	if ($sum==0) {$sum = 1;}
	foreach ($answeights as $k=>$v) {
		$answeights[$k] = $v/$sum;
	}
	return $answeights;
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
	$lastonly = false;
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
	global $questions;
	$poss = 0;
	if (is_array($qi)) {
		foreach ($questions as $qn) {
			$poss += $qi[$qn]['points'];
		}
	}
	return $poss;
}
//Get remaining possible points on a question
//qi:  getquestioninfo[qid]
//attempts: scalar attempts on question
//testsettings: assoc array of assessment settings
function getremainingpossible($qn,$qi,$testsettings,$attempts) {
	global $scores;
	global $regenonreattempt;
	if (isset($qi['answeights']) && $scores[$qn]!=-1) {
		$possible = calcpointsafterpenalty(implode('~',$qi['answeights']),$qi,$testsettings,$attempts);
		$appts = explode('~',$possible);
		$curs = explode('~',$scores[$qn]);
		if (count($curs)==count($appts) && !$regenonreattempt) {
			for ($k=0;$k<count($curs);$k++) {
				if ($appts[$k]>$curs[$k]) { //part after penalty better than orig, replace
					$curs[$k] = $appts[$k];
				}
				if ($curs[$k]<0) {
					$curs[$k] = 0;
				}
			}
			$possible = round(array_sum($curs),1);
		} else {
			$possible = round(array_sum($appts),1);
		}

	} else {
		$possible = calcpointsafterpenalty(1,$qi,$testsettings,$attempts);
	}
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
		//$rposs[$k] = calcpointsafterpenalty(1,$qi[$qid],$testsettings,$attempts[$k]);
		$rposs[$k] = getremainingpossible($k,$qi[$qid],$testsettings,$attempts[$k]);
	}
	return $rposs;
}

//calculates points based on return from scoreq
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

//determines if a question has not been attempted
function unans($sc) {
	if (strpos($sc,'~')===false) {
		return ($sc==-1);
	} else {
		return (strpos($sc,'-1')!==FALSE);
	}
}

function amreattempting($n) {
	global $reattempting;
	return in_array($n,$reattempting);
}

function scorestocolors($sc,$pts,$answ,$noraw) {
	if (!$noraw) {
		$pts = 1;
	}
	if (trim($sc)=='') {return '';}
	if (strpos($sc,'~')===false) {
		if ($pts==0) {
			$color = '';
		} else if ($sc<0) {
			$color = '';
		} else if ($sc==0) {
			$color = 'ansred';
		} else if ($pts-$sc<.011) {
			$color = 'ansgrn';
		} else {
			$color = 'ansyel';
		}
		return array($color);
	} else {
		$scarr = explode('~',$sc);
		if ($noraw) {
			for ($i=0; $i<count($answ)-1; $i++) {
				$answ[$i] = round($answ[$i]*$pts,2);
			}
			//adjust for rounding
			$diff = $pts - array_sum($answ);
			$answ[count($answ)-1] += $diff;
		} else {
			$origansw = $answ;
			$answ = array_fill(0,count($scarr),1);
		}

		$out = array();
		foreach ($scarr as $k=>$v) {
			if ($answ[$k]==0 || (!$noraw && $origansw[$k]==0)) {
				$color = '';
			} else if ($v < 0) {
				$color = '';
			} else if ($v==0) {
				$color = 'ansred';
			} else if ($answ[$k]-$v < .011) {
				$color = 'ansgrn';
			} else {
				$color = 'ansyel';
			}
			$out[$k] = $color;
		}
		return $out;
	}
}

//creates display of score  (chg from previous: does not echo self)
function printscore($sc,$qn) {
	global $qi,$questions,$imasroot,$rawscores;
	$hasmanual = false;
	if (isset($rawscores) && isset($rawscores[$qn])) {
		$thisraw = $rawscores[$qn];
	} else {
		$thisraw = '';
	}
	$poss = $qi[$questions[$qn]]['points'];
	if (strpos($sc,'~')===false) {
		$sc = str_replace('-1','N/A',$sc);
		if ($sc==0 && strpos($thisraw,'-2')!==false) {
			$hasmanual = true;
			$sc = '*';
		}
		$out = sprintf("%g %s %s", $sc, _("out of"), Sanitize::encodeStringForDisplay($poss));
		$pts = Sanitize::onlyFloat($sc);
		if (!is_numeric($pts)) { $pts = 0;}
	} else {
		$ptposs = $qi[$questions[$qn]]['answeights'];
		for ($i=0; $i<count($ptposs)-1; $i++) {
			$ptposs[$i] = round($ptposs[$i]*$poss,2);
		}
		//adjust for rounding
		$diff = $poss - array_sum($ptposs);
		$ptposs[count($ptposs)-1] += $diff;

		$pts = getpts($sc);
		$sc = str_replace('-1','N/A',$sc);

		//$sc = str_replace('~',', ',$sc);
		$scarr = explode('~',$sc);
		if (strpos($thisraw,'-2')!==false) {
			$rawarr = explode('~',$thisraw);
			foreach ($rawarr as $k=>$v) {
				if ($scarr[$k]==0 && $v==-2) {
					$scarr[$k] = '*';
					$hasmanual = true;
				}
			}
		}
		foreach ($scarr as $k=>$v) {
			if ($ptposs[$k]==0) {
				$pm = 'gchk'; $alt = _('Correct');
			} else if (!is_numeric($v) || $v==0) {
				$pm = 'redx'; $alt = _('Incorrect');
			} else if (abs($v-$ptposs[$k])<.011) {
				$pm = 'gchk'; $alt = _('Correct');
			} else {
				$pm = 'ychk'; $alt = _('Partially correct');
			}
			/*if (!is_numeric($v) || $v==0) {
				$w = 1;
			} if ($ptposs[$k]==0) {
				$w = 14;
			} else {
				$w = round(14*$sc/$ptposs[$k]);
			}
			$bar = '<span class="miniscorebarholder">';
			if ($w < 7) {
			     $color = "#f".dechex(floor(16*($w)/7))."0";
			} else if ($w==7) {
			     $color = '#ff0';
			} else {
			     $color = "#". dechex(floor(16*(2-$w/7))) . "f0";
			}
			$wmt = 14-$w;
			$bar .= '<span class="miniscorebarinner" style="background-color:'.$color.';margin-top:'.$wmt.'px;height:'.$w.'px;">&nbsp;</span></span> ';
			//$scarr[$k] = $bar.$v;
			*/
			$bar = "<img src=\"$imasroot/img/$pm.gif\" alt=\"$alt\" />";
			if ($v=='*') {
				$bar = '';
			}
			$scarr[$k] = "$bar $v/{$ptposs[$k]}";
		}
		$sc = implode(', ',$scarr);
		//$ptposs = implode(', ',$ptposs);
		$out = sprintf("%s %s %s (parts: %s)", Sanitize::encodeStringForDisplay($pts), _("out of"),
			Sanitize::encodeStringForDisplay($poss), $sc);
	}
	if ($hasmanual) {
		$out .= _(' (* not auto-graded)');
	}
	$bar = '<span class="scorebarholder">';
	if ($poss==0) {
		$w = 30;
	} else {
		$w = round(30*$pts/$poss);
	}
	if ($w==0) {$w=1;}
	if ($w < 15) {
	     $color = "#f".dechex(floor(16*($w)/15))."0";
	} else if ($w==15) {
	     $color = '#ff0';
	} else {
	     $color = "#". dechex(floor(16*(2-$w/15))) . "f0";
	}

	$bar .= '<span class="scorebarinner" style="background-color:'.$color.';width:'.$w.'px;">&nbsp;</span></span> ';
	return $bar . $out;
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
function scorequestion($qn, $rectime=true) {
	global $DBH,$questions,$scores,$seeds,$testsettings,$qi,$attempts,$lastanswers,$isreview,$bestquestions,$bestseeds,$bestscores,$bestattempts,$bestlastanswers, $reattempting, $rawscores, $bestrawscores, $firstrawscores;
	global $regenonreattempt, $sessiondata;
	//list($qsetid,$cat) = getqsetid($questions[$qn]);
	$lastrawscore = $rawscores[$qn];

	list($unitrawscore,$rawscores[$qn]) = scoreq($qn,$qi[$questions[$qn]]['questionsetid'],$seeds[$qn],$_POST["qn$qn"],$attempts[$qn],$qi[$questions[$qn]]['points']);

	$afterpenalty = calcpointsafterpenalty($unitrawscore,$qi[$questions[$qn]],$testsettings,$attempts[$qn]);

	$rawscore = calcpointsafterpenalty($unitrawscore,$qi[$questions[$qn]],$testsettings,0); //possible

	$noscores = ($testsettings['testtype']=="NoScores");

	//work in progress
	//need to rework canimprove
	if (!$regenonreattempt && $attempts[$qn]>0 && strpos($afterpenalty,'~')!==false && !$noscores) {
		$appts = explode('~',$afterpenalty);
		$prepts = explode('~',$rawscore);
		$curs = explode('~',$scores[$qn]);
		if (count($appts) != count($curs)) { //number of parts has changed - ignore previous work
			$scores[$qn] = $afterpenalty;
		} else {
			for ($k=0;$k<count($curs);$k++) {
				if ($appts[$k]>$curs[$k]) { //part after penalty better than orig, replace
					$curs[$k] = $appts[$k];
				}
				if ($prepts[$k]<$curs[$k]) { //changed correct to incorrect, take away pts
					$curs[$k] = $appts[$k];
				}
			}
			$scores[$qn] = implode('~',$curs);
		}
	} else {
		$scores[$qn] = $afterpenalty;
	}
	if (!$isreview && $attempts[$qn]==0 && strpos($lastanswers[$qn],'##')===false && !$sessiondata['isteacher']) {
		$firstrawscores[$qn] = $rawscores[$qn];
		if ($rectime) {
			global $timesontask;
			$time = explode('~',$timesontask[$qn]);
			$time = $time[0];
		} else {
			$time = 0;  //for all at once display, where time is not useful info
		}
		//DB $query = "INSERT INTO imas_firstscores (courseid,qsetid,score,scoredet,timespent) VALUES ";
		//DB $query .= "('".addslashes($testsettings['courseid'])."','".$qi[$questions[$qn]]['questionsetid']."','".round(100*getpts($unitrawscore))."','".$rawscores[$qn]."','$time')";
		$query = "INSERT INTO imas_firstscores (courseid,qsetid,score,scoredet,timespent) VALUES ";
		$query .= "(:courseid, :qsetid, :score, :scoredet, :timespent)";
		$stm = $DBH->prepare($query);
		$stm->execute(array(':courseid'=>$testsettings['courseid'], ':qsetid'=>$qi[$questions[$qn]]['questionsetid'],
			':score'=>round(100*getpts($unitrawscore)), ':scoredet'=>$rawscores[$qn], ':timespent'=>$time));
		//DB mysql_query($query) or die("Query failed : " . mysql_error());
	}

	//$scores[$qn] = $afterpenalty;
	$attempts[$qn]++;

	$loc = array_search($qn,$reattempting);
	if ($loc!==false) {
		array_splice($reattempting,$loc,1);
	}
	if ((getpts($scores[$qn])>=getpts($bestscores[$qn]) || ($noscores && ($lastrawscore!=$rawscores[$qn] || $rawscore==0)) || $testsettings['displaymethod']=='LivePoll') && !$isreview) {
		$bestseeds[$qn] = $seeds[$qn];
		$bestscores[$qn] = $scores[$qn];
		$bestrawscores[$qn] = $rawscores[$qn];
		$bestattempts[$qn] = $attempts[$qn];
		deletefilesifnotused($bestlastanswers[$qn],$lastanswers[$qn]);
		$bestlastanswers[$qn] = $lastanswers[$qn];
		$bestquestions[$qn] = $questions[$qn];
	}

	return $rawscore;
}

//records everything but questions array
//if limit=true, only records lastanswers
function recordtestdata($limit=false) {
	global $DBH,$isreview,$questions,$bestquestions,$bestscores,$bestattempts,$bestseeds,$bestlastanswers,$scores,$attempts,$seeds,$lastanswers,$testid,$testsettings,$sessiondata,$reattempting,$timesontask,$lti_sourcedid,$qi,$noraw,$rawscores,$bestrawscores,$firstrawscores;

	if ($noraw) {
		$bestscorelist = implode(',',$bestscores);
	} else {
		$bestscorelist = implode(',',$bestscores).';'.implode(',',$bestrawscores).';'.implode(',',$firstrawscores);
	}
	$bestattemptslist = implode(',',$bestattempts);
	$bestseedslist = implode(',',$bestseeds);
	$bestlastanswers = str_replace('~','',$bestlastanswers);
	$bestlalist = implode('~',$bestlastanswers);
	//DB $bestlalist = addslashes(stripslashes($bestlalist));

	if ($noraw) {
		$scorelist = implode(',',$scores);
	} else {
		$scorelist = implode(',',$scores).';'.implode(',',$rawscores);
	}
	$attemptslist = implode(',',$attempts);
	$seedslist = implode(',',$seeds);
	$lastanswers = str_replace('~','',$lastanswers);
	$lalist = implode('~',$lastanswers);
	//DB $lalist = addslashes(stripslashes($lalist));
	$timeslist = implode(',',$timesontask);

	$reattemptinglist = implode(',',$reattempting);
	$questionlist = implode(',', $questions);
	$bestquestionlist = implode(',', $bestquestions);
	if ($questionlist!=$bestquestionlist) {
		$questionlist .= ';'.$bestquestionlist;
	}

	$now = time();
	if ($isreview) {
		if ($limit) {
			//DB $query = "UPDATE imas_assessment_sessions SET reviewlastanswers='$lalist' ";
			$query = "UPDATE imas_assessment_sessions SET reviewlastanswers=:reviewlastanswers ";
			$qarr = array(':reviewlastanswers'=>$lalist);
		} else {
			//DB $query = "UPDATE imas_assessment_sessions SET reviewscores='$scorelist',reviewattempts='$attemptslist',reviewseeds='$seedslist',reviewlastanswers='$lalist',";
			//DB $query .= "reviewreattempting='$reattemptinglist' ";
			$query = "UPDATE imas_assessment_sessions SET reviewscores=:reviewscores,reviewattempts=:reviewattempts,reviewseeds=:reviewseeds,reviewlastanswers=:reviewlastanswers,";
			$query .= "reviewreattempting=:reviewreattempting ";
			$qarr = array(':reviewscores'=>$scorelist, ':reviewattempts'=>$attemptslist, ':reviewseeds'=>$seedslist, ':reviewlastanswers'=>$lalist, ':reviewreattempting'=>$reattemptinglist);
		}
	} else {
		if ($limit) {
			//DB $query = "UPDATE imas_assessment_sessions SET lastanswers='$lalist',timeontask='$timeslist' ";
			$query = "UPDATE imas_assessment_sessions SET lastanswers=:lastanswers,timeontask=:timeontask ";
			$qarr = array(':lastanswers'=>$lalist, ':timeontask'=>$timeslist);
		} else {
			//DB $query = "UPDATE imas_assessment_sessions SET scores='$scorelist',attempts='$attemptslist',seeds='$seedslist',lastanswers='$lalist',";
			//DB $query .= "bestseeds='$bestseedslist',bestattempts='$bestattemptslist',bestscores='$bestscorelist',bestlastanswers='$bestlalist',";
			//DB $query .= "endtime=$now,reattempting='$reattemptinglist',timeontask='$timeslist',questions='$questionlist' ";
			$query = "UPDATE imas_assessment_sessions SET scores=:scores,attempts=:attempts,seeds=:seeds,lastanswers=:lastanswers,";
			$query .= "bestseeds=:bestseeds,bestattempts=:bestattempts,bestscores=:bestscores,bestlastanswers=:bestlastanswers,";
			$query .= "endtime=:endtime,reattempting=:reattempting,timeontask=:timeontask,questions=:questions ";
			$qarr = array(':scores'=>$scorelist, ':attempts'=>$attemptslist, ':seeds'=>$seedslist, ':lastanswers'=>$lalist,
				':bestseeds'=>$bestseedslist, ':bestattempts'=>$bestattemptslist, ':bestscores'=>$bestscorelist,
				':bestlastanswers'=>$bestlalist, ':endtime'=>$now, ':reattempting'=>$reattemptinglist, ':timeontask'=>$timeslist,
				':questions'=>$questionlist);
			
			if (isset($lti_sourcedid) && strlen($lti_sourcedid)>0 && $sessiondata['ltiitemtype']==0) {
				//update lti record.  We only do this for single assessment placements
	
				require_once("../includes/ltioutcomes.php");
	
				$total = 0;
				for ($i =0; $i < count($bestscores);$i++) {
					if (getpts($bestscores[$i])>0) { $total += getpts($bestscores[$i]);}
				}
				$totpossible = totalpointspossible($qi);
				$grade = round($total/$totpossible,4);
				$res = updateLTIgrade('update',$lti_sourcedid,$testsettings['id'],$grade);
			}
		}
	}
	if ($testsettings['isgroup']>0 && $sessiondata['groupid']>0 && !$isreview) {
		//DB $query .= "WHERE agroupid='{$sessiondata['groupid']}' AND assessmentid='{$testsettings['id']}'";
		$query .= "WHERE agroupid=:agroupid AND assessmentid=:assessmentid";
		$qarr[':agroupid']=$sessiondata['groupid'];
		$qarr[':assessmentid']=$testsettings['id'];
	} else {
		//DB $query .= "WHERE id='$testid' LIMIT 1";
		$query .= "WHERE id=:id LIMIT 1";
		$qarr[':id']=$testid;
	}
	$stm = $DBH->prepare($query);
	$stm->execute($qarr);
	//DB mysql_query($query) or die("Query failed : $query " . mysql_error());
}

function deletefilesifnotused($delfrom,$ifnothere) {
	global $testsettings,$sessiondata, $testid, $isreview;
	$outstr = '';
	preg_match_all('/@FILE:(.+?)@/',$delfrom,$matches);
	foreach($matches[0] as $match) {
		if (strpos($ifnothere,$match)===false) {
			$outstr .= $match;
		}
	}
	require_once("../includes/filehandler.php");
	if ($testsettings['isgroup']>0 && $sessiondata['groupid']>0 && !$isreview) {
		deleteasidfilesfromstring2($outstr,'agroupid',$sessiondata['groupid'],$testsettings['id']);
	} else {
		deleteasidfilesfromstring2($outstr,'id',$testid,$testsettings['id']);
	}
	//deleteasidfilesfromstring($outstr);
}

//can improve question score?  (on this version)
function canimprove($qn) {
	global $superdone;
	if ($superdone) { return false;}
	global $qi,$scores,$attempts,$questions,$testsettings,$seeds,$bestseeds,$bestscores;
	$remainingposs = getremainingpossible($qn,$qi[$questions[$qn]],$testsettings,$attempts[$qn]);
	if (hasreattempts($qn)) {
		//this fails in the case of partial credit, where last attempt might not be best
		if ($seeds[$qn] == $bestseeds[$qn]) { //try to handle by using bestscore if current question
			if (getpts($bestscores[$qn])<$remainingposs) {
				return true;
			}
		} else { //we'll just have to use the last score
			if (getpts($scores[$qn])<$remainingposs) {
				return true;
			}
		}
	}
	return false;
}

//can improve question bestscore?
function canimprovebest($qn) {
	global $superdone;
	if ($superdone) { return false;}
	global $qi,$bestscores,$scores,$attempts,$questions,$testsettings;
	$remainingposs = getremainingpossible($qn,$qi[$questions[$qn]],$testsettings,$attempts[$qn]);
	if (hasreattempts($qn)) {
		if (getpts($bestscores[$qn])<$remainingposs) {
			return true;
		}
	}
	return false;
}

//do more attempts remain?
function hasreattempts($qn) {
	global $superdone;
	if ($superdone) { return false;}
	global $qi,$attempts,$questions,$testsettings;
	if ($attempts[$qn]<$qi[$questions[$qn]]['attempts'] || $qi[$questions[$qn]]['attempts']==0) {
		return true;
	}
	return false;
}

//do any questions have attempts remaining?
function hasreattemptsany() {
	global $superdone;
	if ($superdone) { return false;}
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
	global $superdone;
	if ($superdone) { return false;}
	global $questions;
	for ($i=0;$i<count($questions);$i++) {
		if (canimprove($i)) {
			return true;
		}
	}
	return false;
}

//basic show question, for
function basicshowq($qn,$seqinactive=false,$colors=array()) {
	global $showansduring,$questions,$testsettings,$qi,$seeds,$showhints,$attempts,$regenonreattempt,$showansafterlast,$showeachscore,$noraw, $rawscores;
	$qshowansduring = (($showansduring && $qi[$questions[$qn]]['showans']=='0') || $qi[$questions[$qn]]['showansduring']);
	$qshowansafterlast = (($showansafterlast && $qi[$questions[$qn]]['showans']=='0') || $qi[$questions[$qn]]['showans']=='F' || $qi[$questions[$qn]]['showans']=='J');
	if (canimprove($qn)) {
		if (($showansduring && $qi[$questions[$qn]]['showans']=='0' && $attempts[$qn]>=$testsettings['showans']) ||
		    ($qi[$questions[$qn]]['showansduring'] && $attempts[$qn]>=$qi[$questions[$qn]]['showans'])) {$showa = true;} else {$showa=false;}
	} else {
		$showa = (($qshowansduring || $qshowansafterlast) && $showeachscore);
	}

	$regen = ((($regenonreattempt && $qi[$questions[$qn]]['regen']==0) || $qi[$questions[$qn]]['regen']==1)&&amreattempting($qn));
	$thisshowhints = ($qi[$questions[$qn]]['showhints']==2 || ($qi[$questions[$qn]]['showhints']==0 && $showhints));
	if (!$noraw && $showeachscore) { //&& $GLOBALS['questionmanualgrade'] != true) {
		//$colors = scorestocolors($rawscores[$qn], '', $qi[$questions[$qn]]['answeights'], $noraw);
		if (strpos($rawscores[$qn],'~')!==false) {
			$colors = explode('~',$rawscores[$qn]);
		} else {
			$colors = array($rawscores[$qn]);
		}
	}
	if (!$seqinactive) {
		displayq($qn,$qi[$questions[$qn]]['questionsetid'],$seeds[$qn],$showa,$thisshowhints,$attempts[$qn],false,$regen,$seqinactive,$colors);
	} else {
		displayq($qn,$qi[$questions[$qn]]['questionsetid'],$seeds[$qn],$showa,false,$attempts[$qn],false,$regen,$seqinactive,$colors);
	}
}

//shows basic points possible, attempts remaining bar
function showqinfobar($qn,$inreview,$single,$showqnum=0) {
	global $qi,$questions,$attempts,$seeds,$testsettings,$noindivscores,$showeachscore,$scores,$bestscores,$sessiondata,$imasroot,$CFG;
	if (!$sessiondata['istutorial']) {
		if ($inreview) {
			echo '<div class="review clearfix">';
		}
		if ($sessiondata['isteacher']) {
			echo '<span style="float:right;font-size:70%;text-align:right;">'._('Question ID: ').Sanitize::onlyInt($qi[$questions[$qn]]['questionsetid']);
			echo '<br/><a target="license" href="'.$imasroot.'/course/showlicense.php?id='.Sanitize::onlyInt($qi[$questions[$qn]]['questionsetid']).'">'._('License').'</a>';
			if (isset($CFG['GEN']['sendquestionproblemsthroughcourse'])) {
				echo "<br/><a href=\"$imasroot/msgs/msglist.php?add=new&cid={$CFG['GEN']['sendquestionproblemsthroughcourse']}&";
				echo "quoteq=".Sanitize::encodeUrlParam("0-{$qi[$questions[$qn]]['questionsetid']}-{$seeds[$qn]}-reperr-{$GLOBALS['assessver']}")."\" target=\"reperr\">Report Problems</a>";
			}
			echo '</span>';
		} else {
			echo '<span style="float:right;font-size:70%"><a target="license" href="'.$imasroot.'/course/showlicense.php?id='.$qi[$questions[$qn]]['questionsetid'].'">'._('License').'</a></span>';
		}
		if ($showqnum==1) {
			echo _('Question').' '.($qn+1).'. ';
		} else if ($showqnum==2) {
			echo sprintf(_('Question %d of %d'), $qn+1, count($questions)).'<br/>';
		}
		if ($qi[$questions[$qn]]['withdrawn']==1) {
			echo '<span class="noticetext">', _('Question Withdrawn'), '</span> ';
		}
		if ($attempts[$qn]<$qi[$questions[$qn]]['attempts'] || $qi[$questions[$qn]]['attempts']==0) {
			$pointsremaining = getremainingpossible($qn,$qi[$questions[$qn]],$testsettings,$attempts[$qn]);
			if ($pointsremaining == $qi[$questions[$qn]]['points']) {
				echo _('Points possible: '), $qi[$questions[$qn]]['points'], '<br/>';
			} else {
				echo sprintf(_('Points available on this attempt: %1$g of original %2$g'), $pointsremaining, $qi[$questions[$qn]]['points']), '<br/>';
			}
		}

		if ($qi[$questions[$qn]]['attempts']==0) {
			echo _('Unlimited attempts.');
		} else if ($attempts[$qn]>=$qi[$questions[$qn]]['attempts']) {
			echo _('No attempts remain.');
		} else {
			//echo '<br/>'.($qi[$questions[$qn]]['attempts']-$attempts[$qn])." attempts of ".$qi[$questions[$qn]]['attempts']." remaining.";
			echo sprintf(_('This is attempt %1$d of %2$d.'), ($attempts[$qn]+1), $qi[$questions[$qn]]['attempts']);
		}
		if ($testsettings['showcat']>0 && $qi[$questions[$qn]]['category']!='0') {
			echo "  ", _('Category:'), " {$qi[$questions[$qn]]['category']}.";
		}
		if ($attempts[$qn]>0 && $showeachscore) {
			if (strpos($scores[$qn],'~')===false) {
				echo "<br/>", _('Score on last attempt:'). " ".($scores[$qn]<0? 'N/A':$scores[$qn]).". ". _('Score in gradebook:'), " ".($bestscores[$qn]<0? 'N/A':$bestscores[$qn]);
			} else {
				echo "<br/>", _('Score on last attempt:'), " (" . str_replace('~', ', ',$scores[$qn]) . '), ';
				echo _('Score in gradebook:'), " (" . str_replace('~', ', ',$bestscores[$qn]) . '), ';
				$ptposs = $qi[$questions[$qn]]['answeights'];
				for ($i=0; $i<count($ptposs)-1; $i++) {
					$ptposs[$i] = round($ptposs[$i]*$qi[$questions[$qn]]['points'],2);
				}
				//adjust for rounding
				$diff = $qi[$questions[$qn]]['points'] - array_sum($ptposs);
				$ptposs[count($ptposs)-1] += $diff;
				$ptposs = implode(', ',$ptposs);
				echo _('Out of:'), " ($ptposs)";
			}
		}

		//if (!$noindivscores) {
		//	echo "<br/>Score in gradebook: ".printscore2($bestscores[$qn]).".";
		//}
	}
	if ($single) {
		echo "<input type=hidden id=\"verattempts\" name=\"verattempts\" value=\"{$attempts[$qn]}\" />";
	} else {
		echo "<input type=hidden id=\"verattempts$qn\" name=\"verattempts[$qn]\" value=\"{$attempts[$qn]}\" />";
	}
	if (!$sessiondata['istutorial']) {
		$contactlinks = showquestioncontactlinks($qn);
		if ($contactlinks!='') {
			echo '<br/>'.$contactlinks;
		}
		if ($inreview) {
			echo '</div>';
		}
	}
}

function showquestioncontactlinks($qn) {
	global $testsettings,$imasroot,$qi,$seeds,$questions;
	$out = '';
	if ($testsettings['msgtoinstr']==1) {
		$out .= "<a target=\"_blank\" href=\"$imasroot/msgs/msglist.php?cid=".Sanitize::encodeUrlParam($testsettings['courseid']);
		$out .= "&add=new&quoteq=".Sanitize::encodeUrlParam("$qn-{$qi[$questions[$qn]]['questionsetid']}-{$seeds[$qn]}-{$testsettings['id']}-{$GLOBALS['assessver']}")."&to=instr\">". _('Message instructor about this question'). "</a>";
	}
	if ($testsettings['posttoforum']>0) {
		if ($out != '') {
			$out .= "<br/>";
		}
		$out .= "<a target=\"_blank\" href=\"$imasroot/forums/thread.php?cid=".Sanitize::encodeUrlParam($testsettings['courseid'])."&forum=".Sanitize::encodeUrlParam($testsettings['posttoforum']);
		$out .= "&modify=new&quoteq=".Sanitize::encodeUrlParam("$qn-{$qi[$questions[$qn]]['questionsetid']}-{$seeds[$qn]}-{$testsettings['id']}-{$GLOBALS['assessver']}")."\">". _('Post this question to forum'). "</a>";
	}
	return $out;
}

//shows top info bar for seq mode
function seqshowqinfobar($qn,$toshow) {
	global $qi,$questions,$attempts,$testsettings,$scores,$bestscores,$noindivscores,$showeachscore,$imasroot,$CFG,$sessiondata,$seeds,$isreview;
	$reattemptsremain = hasreattempts($qn);
	$pointsremaining = getremainingpossible($qn,$qi[$questions[$qn]],$testsettings,$attempts[$qn]);
	$qavail = false;
	if ($qi[$questions[$qn]]['withdrawn']==1) {
		$qlinktxt = "<span class=\"withdrawn\">" . _('Question') . ' ' .($qn+1)."</span>";
	} else {
		$qlinktxt = _('Question'). ' ' .($qn+1);
	}

	if ($qn==$toshow) {
		echo '<div class="seqqinfocur">';
		if ((unans($scores[$qn]) && $attempts[$qn]==0) || ($noindivscores && amreattempting($qn))) {
			if (isset($CFG['TE']['navicons'])) {
				echo "<img src=\"$imasroot/img/{$CFG['TE']['navicons']['untried']}\" alt=\""._('Untried')."\"/> ";
			} else {
				echo "<img src=\"$imasroot/img/q_fullbox.gif\" alt=\""._('Untried')."\"/> ";
			}
		} else {
			if (isset($CFG['TE']['navicons'])) {
				if ($thisscore==0 || $noindivscores) {
					echo "<img src=\"$imasroot/img/{$CFG['TE']['navicons']['canretrywrong']}\" alt=\""._('Incorrect but can retry')."\"/> ";
				} else {
					echo "<img src=\"$imasroot/img/{$CFG['TE']['navicons']['canretrypartial']}\" alt=\""._('Partially correct and can retry')."\"/> ";
				}
			} else {
				echo "<img src=\"$imasroot/img/q_halfbox.gif\" alt=\""._('Attempted')."\"/> ";
			}
		}
		echo "<span class=current><a name=\"curq\">$qlinktxt</a></span>  ";
	} else {
		$thisscore = getpts($bestscores[$qn]);
		if ((unans($scores[$qn]) && $attempts[$qn]==0) || ($noindivscores && amreattempting($qn))) {
			echo '<div class="seqqinfoavail">';
			if (isset($CFG['TE']['navicons'])) {
				echo "<img src=\"$imasroot/img/{$CFG['TE']['navicons']['untried']}\" alt=\""._('Untried')."\"/> ";
			} else {
			echo "<img src=\"$imasroot/img/q_fullbox.gif\" alt=\""._('Untried')."\"/> ";
			}
			echo "<a href=\"showtest.php?action=seq&to=$qn#curq\" onclick=\"return confirm('", _('Are you sure you want to jump to this question, discarding any work you have not submitted?'), "');\">$qlinktxt</a>.  ";
			$qavail = true;
		} else if (canimprove($qn) && !$noindivscores) {
			echo '<div class="seqqinfoavail">';
			if (isset($CFG['TE']['navicons'])) {
				if ($thisscore==0 || $noindivscores) {
					echo "<img src=\"$imasroot/img/{$CFG['TE']['navicons']['canretrywrong']}\" alt=\""._('Incorrect but can retry')."\"/> ";
				} else {
					echo "<img src=\"$imasroot/img/{$CFG['TE']['navicons']['canretrypartial']}\" alt=\""._('Partially correct and can retry')."\"/> ";
				}
			} else {
				echo "<img src=\"$imasroot/img/q_halfbox.gif\" alt=\""._('Attempted')."\"/> ";
			}
			echo "<a href=\"showtest.php?action=seq&to=$qn#curq\" onclick=\"return confirm('", _('Are you sure you want to jump to this question, discarding any work you have not submitted?'), "');\">$qlinktxt</a>.  ";
			$qavail = true;
		} else if ($reattemptsremain) {
			echo '<div class="seqqinfoinactive">';
			if (isset($CFG['TE']['navicons'])) {
				if (!$showeachscore) {
					echo "<img src=\"$imasroot/img/{$CFG['TE']['navicons']['noretry']}\" alt=\""._('No attempts remaining')."\"/> ";
				} else {
					if ($thisscore == $qi[$questions[$qn]]['points']) {
						echo "<img src=\"$imasroot/img/{$CFG['TE']['navicons']['correct']}\" alt=\""._('Correct')."\"/> ";
					} else if ($thisscore==0) {
						echo "<img src=\"$imasroot/img/{$CFG['TE']['navicons']['wrong']}\" alt=\""._('Incorrect')."\"/> ";
					} else {
						echo "<img src=\"$imasroot/img/{$CFG['TE']['navicons']['partial']}\" alt=\""._('Partially correct')."\"/> ";
					}
				}
			} else {
				echo "<img src=\"$imasroot/img/q_emptybox.gif\" alt=\""._('Unattempted')."\"/> ";
			}
			echo "<a href=\"showtest.php?action=seq&to=$qn#curq\" onclick=\"return confirm('", _('Are you sure you want to jump to this question, discarding any work you have not submitted?'), "');\">$qlinktxt</a>.  ";
		} else {
			echo '<div class="seqqinfoinactive">';
			if (isset($CFG['TE']['navicons'])) {
				if (!$showeachscore) {
					echo "<img src=\"$imasroot/img/{$CFG['TE']['navicons']['noretry']}\" alt=\""._('No attempts remaining')."\"/> ";
				} else {
					if ($thisscore == $qi[$questions[$qn]]['points']) {
						echo "<img src=\"$imasroot/img/{$CFG['TE']['navicons']['correct']}\" alt=\""._('Correct')."\"/> ";
					} else if ($thisscore==0) {
						echo "<img src=\"$imasroot/img/{$CFG['TE']['navicons']['wrong']}\" alt=\""._('Incorrect')."\"/> ";
					} else {
						echo "<img src=\"$imasroot/img/{$CFG['TE']['navicons']['partial']}\" alt=\""._('Partially correct')."\"/> ";
					}
				}
			} else {
			echo "<img src=\"$imasroot/img/q_emptybox.gif\" alt=\""._('Unattempted')."\"/> ";
			}
			echo "$qlinktxt.  ";
		}
	}
	if ($qi[$questions[$qn]]['withdrawn']==1) {
		echo "<span class=\"red\">", _('Question Withdrawn'), "</span> ";
	}
	if ($showeachscore) {
		$pts = getpts($bestscores[$qn]);
		if ($pts<0) { $pts = 0;}
		echo sprintf(_('Points: %1$d out of %2$d possible.'), $pts, $qi[$questions[$qn]]['points']), "  ";
		if ($qn==$toshow) {
			printf(_('%d available on this attempt.'), $pointsremaining);
		}
	} else {
		if ($pointsremaining == $qi[$questions[$qn]]['points']) {
			echo _('Points possible:'), " ", $qi[$questions[$qn]]['points'];
		} else {
			printf(_('Points available on this attempt: %1$d of original %2$d'), $pointsremaining, $qi[$questions[$qn]]['points']);
		}

	}

	if ($qn==$toshow && $attempts[$qn]<$qi[$questions[$qn]]['attempts']) {
		if ($qi[$questions[$qn]]['attempts']==0) {
			echo ".  ", _('Unlimited attempts');
		} else {
			echo '.  ', sprintf(_('This is attempt %1$d of %2$d.'), ($attempts[$qn]+1), $qi[$questions[$qn]]['attempts']);
		}
	}
	if ($testsettings['showcat']>0 && $qi[$questions[$qn]]['category']!='0') {
		echo "  ", _('Category:'), " {$qi[$questions[$qn]]['category']}.";
	}
	$contactlinks = showquestioncontactlinks($qn);
	if ($contactlinks!='') {
		echo '<br/>'.$contactlinks;
	}
	echo '</div>';
	return $qavail;
}

//shows start of test message if no reattempts
function startoftestmessage($perfectscore,$hasreattempts,$allowregen,$noindivscores,$noscores) {
	if ($perfectscore && !$noscores) {
		echo "<p>", _('Assessment is complete with perfect score.'), "</p>";
	}
	if ($hasreattempts) {
		if ($noscores) {
			echo "<p>", _('<a href="showtest.php?reattempt=all">Reattempt assessment</a> on questions where allowed'), "</p>";
		} else if ($noindivscores) {
			echo "<p>", _('<a href="showtest.php?reattempt=all">Reattempt assessment</a> on questions allowed (note: all scores, correct and incorrect, will be cleared)'), "</p>";
		} else {
			echo "<p>", _('<a href="showtest.php?reattempt=all">Reattempt assessment</a> on questions missed where allowed'), "</p>";
		}
	} else {
		echo "<p>", _('No attempts left on current versions of questions.'), "</p>\n";
	}
	if ($allowregen) {
		if ($perfectscore) {
			echo "<p>", _('<a href="showtest.php?regenall=all">Try similar problems</a> for all questions where allowed.'), "</p>";
		} else {
			echo "<p>", _('<a href="showtest.php?regenall=missed">Try similar problems</a> for all questions with less than perfect scores where allowed.'), "</p>";
		}
	}
	if (!$noscores) {
		echo "<p><a href=\"showtest.php?action=embeddone\">", _('When you are done, click here to see a summary of your score'), "</a></p>\n";
	}
}

function embedshowicon($qn) {
	global $qi,$questions,$attempts,$testsettings,$scores,$bestscores,$noindivscores,$showeachscore,$imasroot,$CFG,$sessiondata,$seeds,$isreview;
	$reattemptsremain = hasreattempts($qn);
	$pointsremaining = getremainingpossible($qn,$qi[$questions[$qn]],$testsettings,$attempts[$qn]);
	$qavail = false;
	if ($isreview) {
		$thisscore = getpts($scores[$qn]);
	} else {
		$thisscore = getpts($bestscores[$qn]);
	}
	if ((unans($scores[$qn]) && $attempts[$qn]==0) || ($noindivscores && amreattempting($qn))) {
			if (isset($CFG['TE']['navicons'])) {
				echo "<img class=\"embedicon\" src=\"$imasroot/img/{$CFG['TE']['navicons']['untried']}\" alt=\""._('Untried')."\"/> ";
			} else {
				echo "<img class=\"embedicon\" src=\"$imasroot/img/q_fullbox.gif\" alt=\""._('Untried')."\"/> ";
			}
	} else if (canimprove($qn) && !$noindivscores) {
		if (isset($CFG['TE']['navicons'])) {
			if ($thisscore==0 || $noindivscores) {
				echo "<img class=\"embedicon\" src=\"$imasroot/img/{$CFG['TE']['navicons']['canretrywrong']}\" alt=\""._('Incorrect but can retry')."\"/> ";
			} else {
				echo "<img class=\"embedicon\" src=\"$imasroot/img/{$CFG['TE']['navicons']['canretrypartial']}\" alt=\""._('Partially correct and can retry')."\"/> ";
			}
		} else {
			echo "<img class=\"embedicon\" src=\"$imasroot/img/q_halfbox.gif\" alt=\""._('Attempted')."\"/> ";
		}
	} else if ($reattemptsremain) {
		if (isset($CFG['TE']['navicons'])) {
			if (!$showeachscore) {
				echo "<img class=\"embedicon\" src=\"$imasroot/img/{$CFG['TE']['navicons']['noretry']}\" alt=\""._('No attempts remaining')."\"/> ";
			} else {
				if ($thisscore == $qi[$questions[$qn]]['points']) {
					echo "<img class=\"embedicon\" src=\"$imasroot/img/{$CFG['TE']['navicons']['correct']}\" alt=\""._('Correct')."\"/> ";
				} else if ($thisscore==0) {
					echo "<img class=\"embedicon\" src=\"$imasroot/img/{$CFG['TE']['navicons']['wrong']}\" alt=\""._('Incorrect')."\"/> ";
				} else {
					echo "<img class=\"embedicon\" src=\"$imasroot/img/{$CFG['TE']['navicons']['partial']}\" alt=\""._('Partially correct')."\"/> ";
				}
			}
		} else {
			echo "<img class=\"embedicon\" src=\"$imasroot/img/q_emptybox.gif\" alt=\""._('Unattempted')."\"/> ";
		}
	} else {
		if (isset($CFG['TE']['navicons'])) {
			if (!$showeachscore) {
				echo "<img class=\"embedicon\" src=\"$imasroot/img/{$CFG['TE']['navicons']['noretry']}\" alt=\""._('No attempts remaining')."\"/> ";
			} else {
				if ($thisscore == $qi[$questions[$qn]]['points']) {
					echo "<img class=\"embedicon\" src=\"$imasroot/img/{$CFG['TE']['navicons']['correct']}\" alt=\""._('Correct')."\"/> ";
				} else if ($thisscore==0) {
					echo "<img class=\"embedicon\" src=\"$imasroot/img/{$CFG['TE']['navicons']['wrong']}\" alt=\""._('Incorrect')."\"/> ";
				} else {
					echo "<img class=\"embedicon\" src=\"$imasroot/img/{$CFG['TE']['navicons']['partial']}\" alt=\""._('Partially correct')."\"/> ";
				}
			}
		} else {
			echo "<img class=\"embedicon\" src=\"$imasroot/img/q_emptybox.gif\" alt=\""._('Unattempted')."\"/> ";
		}
	}
}

//output appropriate breadcrumbs for entering an assessment
// like on the password entry page, latepass confirmation, etc.
// this is light breadcrumbs rather than full
function showEnterAssessmentBreadcrumbs($aname) {
	global $isdiag, $sessiondata, $breadcrumbbase, $coursename;
	if (!$isdiag && strpos($_SERVER['HTTP_REFERER'],'treereader')===false && !(isset($sessiondata['ltiitemtype']) && $sessiondata['ltiitemtype']==0)) {
		echo "<div class=breadcrumb>$breadcrumbbase <a href=\"../course/course.php?cid=".Sanitize::courseId($_GET['cid'])."\">".Sanitize::encodeStringForDisplay($coursename)."</a> ";
		echo '&gt; ', Sanitize::encodeStringForDisplay($aname), '</div>';
	}
}

//pull a new question from a question group on regen, if not in review mode
function newqfromgroup($qn) {
	global $testsettings, $questions;
	//find existing question or group
	preg_match('/(^|,)([^,]*'.$questions[$qn].'[^,]*)($|,)/', $testsettings['itemorder'], $matches);
	$q = $matches[2];

	if (strpos($q,'~')!==false) {
		//grouped.  Repick
		$sub = explode('~',$q);
		if (strpos($sub[0],'|')===false) { //backwards compat
			$newq = $sub[array_rand($sub,1)];
		} else {
			$grpparts = explode('|',$sub[0]);
			array_shift($sub);
			if ($grpparts[1]==1) { // With replacement
				$newq = $sub[array_rand($sub,1)];
			} else { //Without replacement
				//look for unused questions
				$notused = array_diff($sub, $questions);
				if (count($notused)>0) {
					$newq = $notused[array_rand($notused,1)];
				} else {
					$newq = $sub[array_rand($sub,1)];
				}
			}
		}
		$questions[$qn] = $newq;
		return true;
	} else {
		//not grouped
		return false;
	}
}

?>

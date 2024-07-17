<?php
//IMathAS: Student sorting report functions
//(c) 2018 David Lippman

require_once "../includes/parsedatetime.php";

function getGBcats($cid) {
	global $DBH;

	$stm = $DBH->prepare("SELECT id,name FROM imas_gbcats WHERE courseid=?");
	$stm->execute(array($cid));
	$out = array();
	while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
		$out[$row['id']] = Sanitize::encodeStringForDisplay($row['name']);
	}
	return $out;
}
function getCourseRuleSets($cid) {
	global $DBH;

	$stm = $DBH->prepare("SELECT jsondata FROM imas_courses WHERE id=?");
	$stm->execute(array($cid));
	$jsondata = json_decode($stm->fetchColumn(0), true);
	if ($jsondata===null || !isset($jsondata['groupingRuleSets'])) {
		return array();
	} else {
		return $jsondata['groupingRuleSets'];
	}
}
function setCourseRuleSets($cid, $rulesets) {
	global $DBH;

	$stm = $DBH->prepare("SELECT jsondata FROM imas_courses WHERE id=?");
	$stm->execute(array($cid));
	$jsondata = json_decode($stm->fetchColumn(0), true);
	if ($jsondata===null) {
		$jsondata = array();
	}
	$jsondata['groupingRuleSets'] = $rulesets;

	$stm = $DBH->prepare("UPDATE imas_courses SET jsondata=? where id=?");
	$stm->execute(array(json_encode($jsondata, JSON_INVALID_UTF8_IGNORE), $cid));
}
function runRuleSet($ruleset) {
	global $cid, $userid, $DBH;
    global $isteacher,$canviewall,$includeduedate,$includelastchange,$iuncludecategoryID,$hidelocked,$alwaysshowIP,$secfilter;

	$isteacher = true;
	$canviewall = true;
	$includeduedate = true;
	$includelastchange = true;
	$includecategoryID = true;
	$hidelocked = true;
	$alwaysshowIP = true;
	$secfilter = -1;

	require_once "gbtable2.php";

	$gb = gbtable();

	$stugrouped = array_fill(0,count($gb)-1,-1);

	$now = time();
	$endoftoday = strtotime("midnight tomorrow -1second", $now);
	$todayday = tzdate('w', $now);
	$daysofweek = array('Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday');
	$compwords = array('no','one','all');

	$groupsout = array();
	foreach ($ruleset as $grpnum=>$rule) {
		if ($rule['timeframe']=='since' || $rule['timeframe']=='between') {
			$rule['sdate'] = parsedatetime($rule['sdate'],"12:00am");
			if ($rule['timeframe']=='between') {
				$rule['edate'] = parsedatetime($rule['edate'],"11:59pm")+59;
			}
		} else if ($rule['timeframe']=='inlast') {
			$rule['sdate'] = strtotime("midnight -".($rule['inlastDays']+1)."days -1sec", $now);
		} else if ($rule['timeframe']=='thisweek') {
			if ($todayday==$rule['dayofweek']) {
				$rule['sdate'] = strtotime("midnight", $now);
			} else {
				$rule['sdate'] = strtotime("last ".$daysofweek[$rule['dayofweek']], $now);
			}
		} else if ($rule['timeframe']=='week') {
			if ($todayday==$rule['dayofweek']) {
				$rule['edate'] = strtotime("midnight +1day -1sec", $now);
			} else {
				$rule['edate'] = strtotime("last ".$daysofweek[$rule['dayofweek']].' +1day -1sec', $now);
			}
			$rule['sdate'] = strtotime("-1week +1sec", $rule['edate']);
		} else if ($rule['timeframe']=='due') {
			$rule['sdate'] = $now;
			$rule['edate'] = strtotime("midnight +".$rule['innextDays']."days", $now);
		}
		$ruleset[$grpnum] = $rule;
	}

	foreach ($ruleset as $grpnum=>$rule) {  //for each rule
		$groupsout[$grpnum] = array();
		for ($i=1;$i<count($gb)-1;$i++) { //foreach student
			if ($stugrouped[$i]!=-1) {continue;}
			$hits = 0; $misses = 0;
			$tot = 0; $poss = 0;
			foreach ($gb[0][1] as $k=>$gbitem) {
				if ($gbitem[4]!=1) {continue;} //only include counted items
				if ($rule['ruleType'] != 'score' && $rule['ruleType'] != 'scores' && $gbitem[6] != 0) {
					//for all types other than score/scores, only include online assessments
					continue;
				}
				//make sure gbitem is within timeframe
				switch($rule['timeframe']) {
					case 'inlast':
					case 'thisweek':
					case 'since':
						if ($gbitem[11]<$rule['sdate']) { continue 2; }
						break;
					case 'week':
					case 'between':
					case 'due':
						if ($gbitem[11]<$rule['sdate'] || $gbitem[11]>$rule['edate']) { continue 2; }
						break;
				}
				if ($rule['gbcat'] != -1 && $rule['gbcat'] != $gbitem[1]) {
					//skip if wrong gbcategory
					continue;
				}

				if ($rule['ruleType'] == 'score' || $rule['ruleType'] == 'scores') {
					//skip if wrong availability type.  **TODO: Doesn't yet account for exceptions
					if ($rule['tocnt']==0 && $gbitem[3]>0) {//past due
						continue;
					} else if ($rule['tocnt']==1 && $gbitem[3]>1) { //past due and avail
						continue;
					} else if ($rule['tocnt']==2 && !isset($gb[$i][1][$k][0])) { //attempted
						continue;
					} else if ($rule['tocnt']==3 && !isset($gb[$i][1][$k][0]) && $gbitem[3]>0) { //past due or attempted
						//not attempted and due date in future
						continue;
					}
				}

				switch($rule['ruleType']) {
					case 'score':
						$tot += 1*(isset($gb[$i][1][$k][0])?$gb[$i][1][$k][0]:0);
						$poss += 1*($gbitem[2]);
						break;
					case 'scores':
						$pct = 100*(isset($gb[$i][1][$k][0])?$gb[$i][1][$k][0]:0)/($gbitem[2]);

						if (($rule['abovebelow']==0 && $pct>=$rule['scorebreak']) ||
						    ($rule['abovebelow']==1 && $pct<$rule['scorebreak'])) {
							$hits++;
						} else {
							$misses++;
						}
						break;
					case 'comp':
						if (isset($gb[$i][1][$k][3]) && ($gb[$i][1][$k][3]%10)==0) {
							//score is not marked as IP or NC
							$hits++;
						} else {
							$misses++;
						}
						break;
					case 'start':
						if (isset($gb[$i][1][$k][0])) { //has score set, so started
							$hits++;
						} else {
							$misses++;
						}
						break;
					case 'late':
						if (isset($gb[$i][1][$k][6]) && $gb[$i][1][$k][6]==2) {
							$hits++;
						}
						break;
					case 'close':
						if (isset($gb[$i][1][$k][9]) && $gb[$i][1][$k][9]!=-1) {
							$started = $gb[$i][1][$k][9]-$gb[$i][1][$k][7]*60;
							//is duedate - startdate < latehrs
							if ($gbitem[11] - $started < $rule['closeTime']*3600) {
								$hits++;
							} else {
								$misses++;
							}
						} else {
							//$hits++;
						}
						break;
				}
				//now, see if we can stop
				switch($rule['ruleType']) {
					case 'comp':
					case 'start':
					case 'close':
					case 'scores':
						if ($rule['numassn']==0 && $hits>0) { //no assn, and found one
							continue 3; //next student
						} else if ($rule['numassn']==2 && $misses>0) {//all assn, one didn't meet
							continue 3; //next student
						} else if ($rule['numassn']==1 && $hits>0) {//one assn, found one
							//add stu to group
							$stugrouped[$i] = $grpnum;
							$groupsout[$grpnum][] = array(prepName($gb[$i][0][0]), $gb[$i][4][0]);
							continue 3; //next student
						}
						break;
					case 'late':
						if ($hits >= $rule['numLP']) {
							//add stu to group
							$stugrouped[$i] = $grpnum;
							$groupsout[$grpnum][] = array(prepName($gb[$i][0][0]), $gb[$i][4][0]);
							continue 3; //next student
						}
						break;
				}
			} //next GB item
			//now, see if the student gets added to the group
			switch($rule['ruleType']) {
				case 'comp':
				case 'start':
				case 'close':
				case 'scores':
					if (($rule['numassn']==0 && $hits==0) ||
					    ($rule['numassn']==2 && $misses==0)) {
						$stugrouped[$i] = $grpnum;
						$groupsout[$grpnum][] = array(prepName($gb[$i][0][0]), $gb[$i][4][0]);
						continue 2; //next student
					}
					break;
				case 'score':
                    if ($poss > 0) {
					    $percent = 100*$tot/$poss;
                    } else {
                        $percent = 0;
                    }
					if (($rule['abovebelow']==0 && $percent>=$rule['scorebreak']) ||
					    ($rule['abovebelow']==1 && $percent<$rule['scorebreak'])) {
						$stugrouped[$i] = $grpnum;
						$groupsout[$grpnum][] = array(prepName($gb[$i][0][0]), $gb[$i][4][0]);
						continue 2; //next student
					}
					break;
			}
		} //next student
	} //next rule

	//define leftovers group
	$grpnum++;
	$groupsout[$grpnum] = array();
	for ($i=1;$i<count($gb)-1;$i++) {
		if ($stugrouped[$i]==-1) {
			$groupsout[$grpnum][] = array(prepName($gb[$i][0][0]), $gb[$i][4][0]);
		}
	}
	return $groupsout;
}
function prepName($name) {
	$name = str_replace('&nbsp;',' ',$name);
	$name = Sanitize::encodeStringForDisplay($name);
	return $name;
}

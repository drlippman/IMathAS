<?php
//IMathAS:  Assessment Session utility functions
//(c) 2007 David Lippman

require_once(__DIR__ . "/../includes/sanitize.php");

function generateAssessmentData($itemorder,$shuffle,$aid,$arrayout=false) {
	global $DBH;
	$ioquestions = explode(",",$itemorder);
	$questions = array();
	foreach($ioquestions as $k=>$q) {
		if (strpos($q,'~')!==false) {
			$sub = explode('~',$q);
			if (strpos($sub[0],'|')===false) { //backwards compat
				$questions[] = $sub[array_rand($sub,1)];
			} else {
				$grpqs = array();
				$grpparts = explode('|',$sub[0]);
				array_shift($sub);
				if ($grpparts[1]==1) { // With replacement
					for ($i=0; $i<$grpparts[0]; $i++) {
						$questions[] = $sub[array_rand($sub,1)];
					}
				} else if ($grpparts[1]==0) { //Without replacement
					shuffle($sub);
					for ($i=0; $i<min($grpparts[0],count($sub)); $i++) {
						$questions[] = $sub[$i];
					}
					//$grpqs = array_slice($sub,0,min($grpparts[0],count($sub)));
					if ($grpparts[0]>count($sub)) { //fix stupid inputs
						for ($i=count($sub); $i<$grpparts[0]; $i++) {
							$questions[] = $sub[array_rand($sub,1)];
						}
					}
				}
			}
		} else {
			$questions[] = $q;
		}
	}
	
	$qlist = array_map('intval',$questions);
	$qlist_query_placeholders = Sanitize::generateQueryPlaceholders($qlist);
	$stm = $DBH->prepare("SELECT id,fixedseeds FROM imas_questions WHERE id IN ($qlist_query_placeholders)");
	$stm->execute($qlist);
	$fixedseeds = array();
	while ($row = $stm->fetch(PDO::FETCH_NUM)) {
		if ($row[1]!==null && $row[1]!='') {
			$fixedseeds[$row[0]] = explode(',',$row[1]);	
		}
	}
	
	if ($shuffle&1) {shuffle($questions);}
	else if ($shuffle&16) {
		$firstq = array_shift($questions);
		shuffle($questions);
		array_unshift($questions, $firstq);
	}
	
	if ($shuffle&2) { //all questions same random seed
		if ($shuffle&4) { //all students same seed
			$seeds = array_fill(0,count($questions),$aid);
			$reviewseeds = array_fill(0,count($questions),$aid+100);
		} else {
			$seeds = array_fill(0,count($questions),rand(1,9999));
			$reviewseeds = array_fill(0,count($questions),rand(1,9999));
		}
	} else {
		if ($shuffle&4) { //all students same seed
			for ($i = 0; $i<count($questions);$i++) {
				if (isset($fixedseeds[$questions[$i]])) {
					$seeds[] = $fixedseeds[$questions[$i]][0];
					$reviewseeds[] = $fixedseeds[$questions[$i]][1%count($fixedseeds[$questions[$i]])];
				} else {
					$seeds[] = $aid + $i;
					$reviewseeds[] = $aid + $i + 100;
				}
			}
		} else {
			for ($i = 0; $i<count($questions);$i++) {
				if (isset($fixedseeds[$questions[$i]])) {
					$n = count($fixedseeds[$questions[$i]]);
					$x = rand(0,$n-1);
					$seeds[] = $fixedseeds[$questions[$i]][$x];
					$reviewseeds[] = $fixedseeds[$questions[$i]][($x+1)%$n];
				} else {
					$seeds[] = rand(1,9999);
					$reviewseeds[] = rand(1,9999);
				}
			}
		}
	}


	$scores = array_fill(0,count($questions),-1);
	$attempts = array_fill(0,count($questions),0);
	$lastanswers = array_fill(0,count($questions),'');
	if ($arrayout) {
		return array($questions,$seeds,$reviewseeds,$scores,$attempts,$lastanswers);
	} else {
		$qlist = implode(",",$questions);
		$seedlist = implode(",",$seeds);
		$reviewseedlist = implode(",",$reviewseeds);
		$scorelist = implode(",",$scores);
		$attemptslist = implode(",",$attempts);
		$lalist = implode("~",$lastanswers);
		return array($qlist,$seedlist,$reviewseedlist,$scorelist,$attemptslist,$lalist);
	}

}

//for future:
//need to rewrite how lastanswers stores data
//change to serialized array.  Keep separate record of typed responses 
/*
format:

$la[0] = the current last answer stuff
	$la[0][i] = last answer info for question i (currently ~ separated)
		$la[0][i][j] = last answer info for regen j (currently attempts are ## separated, regens say ReGen)
		    $la[0][i][j][k] = last answer for attempt k of this version (currently ## separated, not ReGen values)
		       if multipart: $la[0][i][j][k][L] = last answer for part L of multipart question
$la[1] = calculated and unshuffled multchoice, etc.


*/
//and cooresponding useful stuff - unshuffled choices answers, calculated
//values of calc and calcinterval, etc.
function unpackLA($lastr) {
	if (substr($lastr,0,4)=='a:2:') {
		return unserialize($lastr);
	} else {
		$lao = explode('~',$lastr);
		foreach ($lao as $i=>$att) {
			$newatt = array();
			$att = explode('##',$att);
			$j = 0;
			while ($att[$j]=='ReGen') {
				$newatt[$j] = array();
				$j++;
			}
			$att[$j] = array();
			for ($c=$j;$c<count($att);$c++) {
				$att[$j] = explode('&',$att[$j]);
				if (count($att[$j]==1)) {
					$att[$j] = $att[$j][0];
				}
				$newatt[$j][$c-$j] = $att[$j];
			}
			$lao[$i] = $newatt;
		}
		return array($lao,array());
	}
}
?>

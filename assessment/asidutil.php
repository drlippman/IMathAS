<?php
//IMathAS:  Assessment Session utility functions
//(c) 2007 David Lippman

function generateAssessmentData($itemorder,$shuffle,$aid,$arrayout=false) {
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
	if ($shuffle&1) {shuffle($questions);}
	
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
				$seeds[] = $aid + $i;
				$reviewseeds[] = $aid + $i + 100;
			}
		} else {
			for ($i = 0; $i<count($questions);$i++) {
				$seeds[] = rand(1,9999);
				$reviewseeds[] = rand(1,9999);
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

?>

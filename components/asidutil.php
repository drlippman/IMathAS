<?php
namespace app\components;

function generateAssessmentData($itemorder,$shuffle,$aid,$arrayout=false) {
	$ioquestions = explode(",",$itemorder);
	$questions = array();
	foreach($ioquestions as $k=>$q) {
		if (strpos($q,'~')!==false) {
			$sub = explode('~',$q);
			if (strpos($sub[0],'|')===false) { //backwards compat
				$questions[] = $sub[array_rand($sub,AppConstant::NUMERIC_ONE)];
			} else {
				$grpqs = array();
				$grpparts = explode('|',$sub[0]);
				array_shift($sub);
				if ($grpparts[1]==AppConstant::NUMERIC_ONE) { // With replacement
					for ($i=AppConstant::NUMERIC_ZERO; $i<$grpparts[0]; $i++) {
						$questions[] = $sub[array_rand($sub,AppConstant::NUMERIC_ONE)];
					}
				} else if ($grpparts[1]==AppConstant::NUMERIC_ZERO) { //Without replacement
					shuffle($sub);
					for ($i=AppConstant::NUMERIC_ZERO; $i<min($grpparts[0],count($sub)); $i++) {
						$questions[] = $sub[$i];
					}
					if ($grpparts[0]>count($sub)) { //fix stupid inputs
						for ($i=count($sub); $i<$grpparts[0]; $i++) {
							$questions[] = $sub[array_rand($sub,AppConstant::NUMERIC_ONE)];
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
			$seeds = array_fill(AppConstant::NUMERIC_ZERO,count($questions),$aid);
			$reviewseeds = array_fill(AppConstant::NUMERIC_ZERO,count($questions),$aid+AppConstant::NUMERIC_HUNDREAD);
		} else {
			$seeds = array_fill(AppConstant::NUMERIC_ZERO,count($questions),rand(AppConstant::NUMERIC_ONE,AppConstant::QUARTER_NINE));
			$reviewseeds = array_fill(AppConstant::NUMERIC_ZERO,count($questions),rand(AppConstant::NUMERIC_ONE,AppConstant::QUARTER_NINE));
		}
	} else {
		if ($shuffle&4) { //all students same seed
			for ($i = AppConstant::NUMERIC_ZERO; $i<count($questions);$i++) {
				$seeds[] = $aid + $i;
				$reviewseeds[] = $aid + $i + AppConstant::NUMERIC_HUNDREAD;
			}
		} else {
			for ($i = AppConstant::NUMERIC_ZERO; $i<count($questions);$i++) {
				$seeds[] = rand(AppConstant::NUMERIC_ONE,AppConstant::QUARTER_NINE);
				$reviewseeds[] = rand(AppConstant::NUMERIC_ONE,AppConstant::QUARTER_NINE);
			}
		}
	}
	$scores = array_fill(AppConstant::NUMERIC_ZERO,count($questions),AppConstant::NUMERIC_NEGATIVE_ONE);
	$attempts = array_fill(AppConstant::NUMERIC_ZERO,count($questions),AppConstant::NUMERIC_ZERO);
	$lastanswers = array_fill(AppConstant::NUMERIC_ZERO,count($questions),'');
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

/*
 *for future:
 *need to rewrite how lastanswers stores data
 *change to serialized array.  Keep separate record of typed responses
 *format:
 *$la[0] = the current last answer stuff
 *$la[0][i] = last answer info for question i (currently ~ separated)
 *$la[0][i][j] = last answer info for regen j (currently attempts are ## separated, regens say ReGen)
 *$la[0][i][j][k] = last answer for attempt k of this version (currently ## separated, not ReGen values)
 *if multipart: $la[0][i][j][k][L] = last answer for part L of multipart question
 *$la[1] = calculated and unshuffled multchoice, etc.
 *and cooresponding useful stuff - unshuffled choices answers, calculated
 *values of calc and calcinterval, etc.
*/
function unpackLA($lastr) {
	if (substr($lastr,AppConstant::NUMERIC_ZERO,AppConstant::NUMERIC_FOUR)=='a:2:') {
		return unserialize($lastr);
	} else {
		$lao = explode('~',$lastr);
		foreach ($lao as $i=>$att) {
			$newatt = array();
			$att = explode('##',$att);
			$j = AppConstant::NUMERIC_ZERO;
			while ($att[$j]=='ReGen') {
				$newatt[$j] = array();
				$j++;
			}
			$att[$j] = array();
			for ($c=$j;$c<count($att);$c++) {
				$att[$j] = explode('&',$att[$j]);
				if (count($att[$j]==AppConstant::NUMERIC_ONE)) {
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

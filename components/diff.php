<?php

function diff($old, $new){
	foreach($old as $oindex => $ovalue){
		$nkeys = array_keys($new, $ovalue);
		foreach($nkeys as $nindex){
			$matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1]) ?
				$matrix[$oindex - 1][$nindex - 1] + 1 : 1;
			if($matrix[$oindex][$nindex] > $maxlen){
				$maxlen = $matrix[$oindex][$nindex];
				$omax = $oindex + 1 - $maxlen;
				$nmax = $nindex + 1 - $maxlen;
			}
		}	
	}
	if($maxlen == 0) return array(array('d'=>$old, 'i'=>$new));
	return array_merge(
		diff(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)),
		array_slice($new, $nmax, $maxlen),
		diff(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen)));
}

//Added by David Lippman to condense diff into sparse JSON string
//need syntax with delete, add, replace
//lets use:  0: delete, 1: add, 2: replace
function diffsparsejson($old, $new) {
	$diff = diff(diffstringsplit($old), diffstringsplit($new));
	$adj = 0;
	$out = array();
	foreach($diff as $k=>$v) {
		if (is_array($v)) {
			if (empty($v['d']) && empty($v['i'])) {
				$adj += 1;
				continue;
			} else if (empty($v['d'])) {//insert
				$out[] = array(0,$k-$adj,$v['i']);
				$adj += 1;
			} else if (empty($v['i'])) { //delete
				$out[] = array(1,$k-$adj,count($v['d']));
				$adj -= count($v['d'])-1; 
			} else { //replace
				$out[] = array(2,$k-$adj,count($v['d']),$v['i']);
				$adj -= count($v['d'])-1;

			}
		}
	}	
	if (count($out)==0) {
		return '';
	} else {
		if (function_exists('json_encode')) {
			return json_encode($out);
		} else {
			require_once("JSON.php");
			$jsonser = new Services_JSON();
			return $jsonser->encode($out);
		}
	}
}

function diffapplydiff($base,$diff) {
	if (function_exists('json_encode')) {
		$diffs = json_decode($diff);
	} else {
		require_once("JSON.php");
		$jsonser = new Services_JSON();
		return $jsonser->decode($diff);
	}
	for ($i = count($diffs)-1; $i>=0; $i--) {
		if ($diffs[$i][0]==2) { //replace
			array_splice($base,$diffs[$i][1],$diffs[$i][2],$diffs[$i][3]);
		} else if ($diffs[$i][0]==0) { //insert
			array_splice($base,$diffs[$i][1],0,$diffs[$i][2]);
		} else if ($diffs[$i][0]==1) { //delete
			array_splice($base,$diffs[$i][1],$diffs[$i][2]);
		}
	}
	
	return $base;
}

function diffstringsplit($str) {
	if (isset($GLOBALS['wikiver'])) {
		$wikiver = $GLOBALS['wikiver'];
	} else {
		$wikiver = 1;
	}
	$p = preg_split('/(<span\s+class="AM".*?<\/span>|<embed.*?>)/',$str,-1,PREG_SPLIT_DELIM_CAPTURE);
	$out = array();
	foreach ($p as $k=>$cont) {
		if ($k%2==0) {
			$cont = trim($cont);
			if ($cont=='') {continue;}
			if ($wikiver == 2) {
				$cont = str_replace('><','> <',$cont);
			}
			$out = array_merge($out,preg_split('/\s+/',$cont));
			
		} else {
			$out[] = $cont;
		}
	}
	return $out;
}

?>

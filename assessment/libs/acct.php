<?php
//A collection of accounting macros
//
//June 2013

global $allowedmacros;
array_push($allowedmacros,"makejournal","scorejournal","makeaccttable","makeTchart","scoreTchart");

//makejournal(journal array, start number, options, $anstypes, $questions, $answer, $showanswer, $displayformat, $answerboxsize)
//
//journal array should be an array in the form
//$journal[0]['date'] = '5/13/14'
//$journal[0]['debits'] = array("Bonds payable",357.14,"Expense payable",345.13)
//$journal[0]['credits'] = array("Expense due",15.34)
//$journal[0]['note'] = 'to accrue interest'
//
//start number is the starting multipart value for this journal set.
//options is an array of type-ahead values for the descriptions 
//
//for $anstypes through $displayformat, just pass the variable through.
function makejournal($j, $sn, $ops, &$anstypes, &$questions, &$answer, &$showanswer, &$displayformat, &$answerboxsize) {
	if (isset($j['date'])) {//not multiple date
		$new = array($j);
		$j = $new;
	}
	if ($anstypes === null) { $anstypes = array();}
	if ($questions === null) { $questions = array();}
	if ($answer === null) { $answer = array();}
	if ($showanswer === null) { $showanswer = '';}
	if ($displayformat === null) { $displayformat = array();}
	
	$maxsizedescr = 0;  $maxsizeentry = 0;
	foreach ($j as $ix=>$jd) {
		for ($i=0;$i<count($jd['debits']);$i+=2) {
			$sl = strlen($jd['debits'][$i]);
			if ($sl>$maxsizedescr) { $maxsizedescr = $sl;}
			$sl = strlen($jd['debits'][$i+1]);
			if ($sl>$maxsizeentry) { $maxsizeentry = $sl;}
		}
		for ($i=0;$i<count($jd['credits']);$i+=2) {
			$sl = strlen($jd['credits'][$i]);
			if ($sl>$maxsizedescr) { $maxsizedescr = $sl;}
			$sl = strlen($jd['credits'][$i+1]);
			if ($sl>$maxsizeentry) { $maxsizeentry = $sl;}
		}
	}
	$maxsizedescr += 6;
	$maxsizeentry += 3;
	$out = '<table class="gridded"><thead><tr><td>Date</td><td>Description</td><td>Debit</td><td>Credit</td></tr><thead><tbody>';
	$sa .= $out;
	foreach ($j as $ix=>$jd) {
		if ($ix>0) {
			$out .= '<tr><td colspan="4">&nbsp;</td></tr>';
			$sa .= '<tr><td colspan="4">&nbsp;</td></tr>';
		}
		$dateset = false;
		for ($i=0;$i<count($jd['debits']);$i+=2) {
			$out .= '<tr><td>'.($dateset?'':$jd['date']).'</td>';
			$sa .= '<tr><td>'.($dateset?'':$jd['date']).'</td>';
			$dateset = true;
			
			$out .= '<td>[AB'.$sn.']</td><td>[AB'.($sn+1).']</td><td>[AB'.($sn+2).']</td></tr>';
			$jd['debits'][$i+1] = str_replace(array('$',',',' '),'',$jd['debits'][$i+1])*1;
			$sa .= '<td>'.$jd['debits'][$i].'</td><td class="r">'.number_format($jd['debits'][$i+1],2,'.',',').'</td><td></td></tr>';
			$anstypes[$sn] = 'string'; $displayformat[$sn] = 'typeahead'; $questions[$sn] = $ops;  $answer[$sn] = $jd['debits'][$i]; $answerboxsize[$sn] = $maxsizedescr;
			$anstypes[$sn+1] = 'number'; $displayformat[$sn+1] = 'debit'; $answer[$sn+1] = $jd['debits'][$i+1]; $answerboxsize[$sn+1] = $maxsizeentry;
			$anstypes[$sn+2] = 'string'; $displayformat[$sn+2] = 'credit'; $answer[$sn+2] = ''; $answerboxsize[$sn+2] = $maxsizeentry;
			
			$sn += 3;
		}
		for ($i=0;$i<count($jd['credits']);$i+=2) {
			$out .= '<tr><td>'.($dateset?'':$jd['date']).'</td>';
			$sa .= '<tr><td>'.($dateset?'':$jd['date']).'</td>';
			$dateset = true;
			
			$out .= '<td>[AB'.$sn.']</td><td>[AB'.($sn+1).']</td><td>[AB'.($sn+2).']</td></tr>';
			$jd['credits'][$i+1] = str_replace(array('$',',',' '),'',$jd['credits'][$i+1])*1;
			$sa .= '<td>&nbsp;&nbsp;&nbsp;'.$jd['credits'][$i].'</td><td></td><td class="r">'.number_format($jd['credits'][$i+1],2,'.',',').'</td></tr>';
			$anstypes[$sn] = 'string'; $displayformat[$sn] = 'typeahead'; $questions[$sn] = $ops;  $answer[$sn] = $jd['credits'][$i]; $answerboxsize[$sn] = $maxsizedescr;
			$anstypes[$sn+1] = 'string'; $displayformat[$sn+1] = 'debit'; $answer[$sn+1] = ''; $answerboxsize[$sn+1] = $maxsizeentry;
			$anstypes[$sn+2] = 'number'; $displayformat[$sn+2] = 'credit'; $answer[$sn+2] = $jd['credits'][$i+1]; $answerboxsize[$sn+2] = $maxsizeentry;
			
			$sn += 3;
		}
		if (isset($jd['note'])) {
			$out .= '<tr><td></td><td colspan="3">'.$jd['note'].'</td></tr>';
			$sa .= '<tr><td></td><td colspan="3">'.$jd['note'].'</td></tr>';
		}
		if (isset($jd['explanation'])) {
			$sa .= '<tr><td></td><td colspan="3">'.$jd['explanation'].'</td></tr>';
		}
	}
	$out .= '</tbody></table>';
	$sa .= '</tbody></table>';
	$showanswer .= $sa.'<p>&nbsp;</p>';
			
	return $out;
}

//scorejournal($stuanswers, $answer, $journal, start number)
//Call with $stuanswers, and $answer array
//and journal array and the multipart starting number for this journal
//returns a new $answer array
function scorejournal($stua, $answer, $j, $sn) {
	if ($stua == null) {return $answer;}
	$newans = $answer;
	foreach ($j as $ix=>$jd) {
		$n = (count($jd['debits']) + count($jd['credits']))/2;
		for ($i=$sn;$i<$sn+3*$n;$i+=3) {
			$matchtype = array('n',-1); $matchall = array('n',-1);
			for ($k=0;$k<count($jd['debits'])/2;$k+=2) {
				if (trim(strtolower($stua[$i]))==trim(strtolower($jd['debits'][$k]))) {
					$matchtype = array('d',$k);
					if (abs(trim($stua[$i+1]) - $jd['debits'][$k+1])<.01 && trim($stua[$i+2])=='') {
						$matchall = array('d',$k);
						break;
					}
				}
			}
			if ($matchall[0]=='n') {
				for ($k=0;$k<count($jd['credits'])/2;$k+=2) {
					if (trim(strtolower($stua[$i]))==trim(strtolower($jd['credits'][$k]))) {
						$matchtype = array('c',$k);
						if (abs(trim($stua[$i+2]) - $jd['credits'][$k+1])<.01 && trim($stua[$i+1])=='') {
							$matchall = array('c',$k);
							break;
						}
					}
				}
			}
			if ($matchall[0]=='d') {
				$loc = $matchall[1];
				$newans[$i] = $jd['debits'][$loc];
				$newans[$i+1] = $jd['debits'][$loc+1];
				$newans[$i+2] = '';
				$locbak = $sn + 3*$loc/2;
				//echo "ix: $ix, i: $i, loc: $loc, locbak: $locbak<br/>";
				for ($k=0;$k<3;$k++) {
					$newans[$locbak+$k] = $answer[$i+$k];
				}
			} else if ($matchall[0]=='c') {
				$loc = $matchall[1];
				$newans[$i] = $jd['credits'][$loc];
				$newans[$i+2] = $jd['credits'][$loc+1];
				$newans[$i+1] = '';
				$locbak = $sn + 3*count($jd['debits'])/2 + 3*$loc/2;
				//echo "ix: $ix, i: $i, loc: $loc, locbak: $locbak<br/>";
				for ($k=0;$k<3;$k++) {
					$newans[$locbak+$k] = $answer[$i+$k];
				}
			} else if ($matchtype=='d') {
				$loc = $matchtype[1];
				$newans[$i] = $jd['debits'][$loc];
				$newans[$i+1] = $jd['debits'][$loc+1];
				$newans[$i+2] = '';
				$locbak = $sn + 3*$loc/2;
				//echo "ix: $ix, i: $i, loc: $loc, locbak: $locbak<br/>";
				for ($k=0;$k<3;$k++) {
					$newans[$locbak+$k] = $answer[$i+$k];
				}
			} else if ($matchtype[0]=='c') {
				$loc = $matchtype[1];
				$newans[$i] = $jd['credits'][$loc];
				$newans[$i+2] = $jd['credits'][$loc+1];
				$newans[$i+1] = '';
				$locbak = $sn + 3*count($jd['debits'])/2 + 3*$loc/2;
				//echo "ix: $ix, i: $i, loc: $loc, locbak: $locbak<br/>";
				for ($k=0;$k<3;$k++) {
					$newans[$locbak+$k] = $answer[$i+$k];
				}
			}
			//TODO:  Add a matchvalue
		}
		$sn += 3*(count($jd['debits']) + count($jd['credits']))/2;
	}
	return $newans;
}

//makeaccttable(headers, $rows, $anshead, $ansarray, start number, &$anstypes, &$answer, &$showanswer, &$displayformat, &$answerboxsize)
//rowhead:  array of column headers
//rows:  an array of column arrays.  These are the fixed-value columns
//ansarray:  an array of column arrays.  These are the answers to the to-be-filled-in columns
//start number: starting value for multipart
function makeaccttable($rowhead, $rows, $anshead, $ansarray, $sn, &$anstypes, &$answer, &$showanswer, &$displayformat, &$answerboxsize) {
	if (!is_array($rowhead)) {
		$rowhead = array($rowhead);
		$rows = array($rows);
		$anshead = array($anshead);
		$ansarray = array($ansarray);
	}
	
	if ($anstypes === null) { $anstypes = array();}
	if ($answer === null) { $answer = array();}
	if ($showanswer === null) { $showanswer = '';}
	if ($displayformat === null) { $displayformat = array();}
	
	$maxsize = array();  $hasdecimals = false;
	for ($j=0;$j<count($ansarray);$j++) {
		$maxsize[$j] = 0;
		for ($i=0;$i<count($ansarray[$j]);$i++) {
			$sl = strlen($ansarray[$j][$i]);
			if ($sl>$maxsize[$j]) { $maxsize[$j] = $sl;}
			if (strpos($ansarray[$j][$i],'.')!==false) { $hasdecimals = true;}
		}
	}

	$hashead = false;
	for ($j=0;$j<count($rowhead);$j++) {
		if ($rowhead[$j] != '') {
			$out .= '<th>'.$rowhead[$j].'</th>';
			$hashead = true;
		}
	}
	for ($j=0;$j<count($anshead);$j++) {
		if ($anshead[$j] != '') {
			$out .= '<th>'.$anshead[$j].'</th>';
			$hashead = true;
		}
	}
	if ($hashead) {
		$out = '<table class="gridded"><thead><tr>'.$out.'</tr></thead>';
	} else {
		$out = '<table class="gridded">';
	}
	$out .= '<tbody>';
	$sa = $out;
	for ($i=0;$i<count($rows[0]);$i++) {
		$out .= '<tr>';  $sa .= '<tr>';
		for ($j=0;$j<count($rows);$j++) {
			if ($rows[$j][$i]{0}==' ') { $rows[$j][$i] = '&nbsp;'.$rows[$j][$i];}
			$out .= '<td>'.$rows[$j][$i].'</td>';
			$sa .= '<td>'.$rows[$j][$i].'</td>';
		}
		for ($j=0;$j<count($ansarray);$j++) {
			$out .= '<td class="r">'.($ansarray[$j][$i]{0}=='$'?'$':'').'[AB'.$sn.']</td>';
			$sa .= '<td class="r">'.($ansarray[$j][$i]{0}=='$'?'$':'');
			$ansarray[$j][$i] = str_replace(array('$',','),'',$ansarray[$j][$i]) * 1;
			$answer[$sn] = $ansarray[$j][$i];
			if ($hasdecimals) {
				$sa .= number_format($ansarray[$j][$i],2,'.',',');
			} else {
				$sa .= number_format($ansarray[$j][$i]);
			}
			$answerboxsize[$sn] = $maxsize[$j];
			$displayformat[$sn] = 'alignright';
			$anstypes[$sn] = 'number';
			$sn++;
		}
		$out .= '</tr>';
		$sa .= '</tr>';
	}
	$out .= '</tbody></table>';
	$sa .= '</tbody></table>';
	$showanswer .= $sa.'<p>&nbsp;</p>';
	
	return $out;
}

//function scoreTchart($stua,$answer,$numrows,$numrows,$leftentries,$rightentries, $sn)
//returns a new answer array
function scoreTchart($stua,$answer,$numrows,$leftentries,$rightentries, $sn) {
	if ($stua == null) {return $answer;}
	for ($i=0;$i<count($leftentries);$i++) {
		$leftentries[$i] = str_replace(array('$',','),'',$leftentries[$i]) * 1;
	}
	for ($i=0;$i<count($rightentries);$i++) {
		$rightentries[$i] = str_replace(array('$',','),'',$rightentries[$i]) * 1;
	}
	$cntleft = count($leftentries);
	$cntright = count($rightentries);
	for ($i=0;$i<$numrows;$i++) {
		if ($i<$cntleft && trim($stua[$sn+2*$i])!='') { //if should be entry and there's a stuans
			$foundmatch = false;
			foreach($leftentries as $loc=>$val) {
				if (abs($stua[$sn+2*$i] - $val)<.01) {
					$answer[$sn+2*$i] = $val;
					unset($leftentries[$loc]);
					$foundmatch = true;
					break; //from foreach
				}
			}
			if (!$foundmatch) {
				$answer[$sn+2*$i] = $stua[$sn+2*$i] + 50000;
			}
		}
		if ($i<$cntright && trim($stua[$sn+2*$i+1])!='') { //if should be entry and there's a stuans
			$foundmatch = false;
			foreach($rightentries as $loc=>$val) {
				if (abs($stua[$sn+2*$i+1] - $val)<.01) {
					$answer[$sn+2*$i+1] = $val;
					unset($rightentries[$loc]);
					$foundmatch = true;
					break; //from foreach
				}
			}
			if (!$foundmatch) {
				$answer[$sn+2*$i+1] = $stua[$sn+2*$i+1] + 50000;
			}
		}
	}
	return $answer;
}

//makeTchart(title,numrows,leftentries,rightentries, start number, &$anstypes, &$answer, &$showanswer, &$displayformat, &$answerboxsize)
//num rows, leftentries, and rightentries should not include the total - that will be automatically added
function makeTchart($title,$numrows,$leftentries,$rightentries, $sn, &$anstypes, &$answer, &$showanswer, &$displayformat, &$answerboxsize) {
	$out = '<table style="border-collapse:collapse"><thead><tr><td colspan="2" class="c" style="border-bottom:5px solid #000;">'.$title.'</td></tr></thead><tbody>';
	$sa = '<table style="border-collapse:collapse"><thead><tr><td colspan="2" class="c" style="border-bottom:5px solid #000;">'.$title.'</td></tr></thead><tbody>';
	$maxsize = 0;   
	for ($i=0;$i<count($leftentries);$i++) {
		if (strlen($leftentries[$i])>$maxsize) {
			$maxsize = strlen($leftentries[$i]);
		}
		if (strpos($leftentries[$i],'.')!==false) { $hasdecimals = true;}
		$leftentries[$i] = str_replace(array('$',','),'',$leftentries[$i]) * 1;
	}
	for ($i=0;$i<count($rightentries);$i++) {
		if (strlen($rightentries[$i])>$maxsize) {
			$maxsize = strlen($rightentries[$i]);
		}
		if (strpos($rightentries[$i],'.')!==false) { $hasdecimals = true;}
		$rightentries[$i] = str_replace(array('$',','),'',$rightentries[$i]) * 1;
	}
	$tot = 0;
	for ($i=0;$i<$numrows;$i++) {
		$out .= '<tr><td style="border-right: 5px solid #000;">[AB'.$sn.']</td>';
		if (isset($leftentries[$i])) {
			$sa .= '<tr><td style="border-right: 5px solid #000;" class="r">';
			if ($hasdecimals) {
				$sa .= number_format($leftentries[$i],2,'.',',');
			} else {
				$sa .= number_format($leftentries[$i]);
			}
			$sa .= '</td>';
		} else {
			$sa .='<tr><td style="border-right: 5px solid #000;">&nbsp;</td>';
		}
		
		$answerboxsize[$sn] = $maxsize;
		$displayformat[$sn] = 'alignright';
		if (isset($leftentries[$i])) {
			$anstypes[$sn] = 'number';
			$answer[$sn] = $leftentries[$i];
			$tot += $answer[$sn];
		} else {
			$anstypes[$sn] = 'string';
			$answer[$sn] = '';
		}
		$sn++;
		$out .= '<td>[AB'.$sn.']</td></tr>';
		if (isset($rightentries[$i])) {
			$sa .='<td class="r">';
			if ($hasdecimals) {
				$sa .= number_format($rightentries[$i],2,'.',',');
			} else {
				$sa .= number_format($rightentries[$i]);
			}
			$sa .= '</td></tr>';
		} else {
			$sa .='<td></td></tr>';
		}
		$answerboxsize[$sn] = $maxsize;
		$displayformat[$sn] = 'alignright';
		if (isset($rightentries[$i])) {
			$anstypes[$sn] = 'number';
			$answer[$sn] = $rightentries[$i];
			$tot -= $answer[$sn];
		} else {
			$anstypes[$sn] = 'string';
			$answer[$sn] = '';
		}
		$sn++;
	}
	
	$out .= '<tr><td style="border-top: 3px double;border-right:5px solid #000;">[AB'.$sn.']</td>';
	$answerboxsize[$sn] = $maxsize;
	$displayformat[$sn] = 'alignright';
	if ($tot>0) {
		$anstypes[$sn] = 'number';
		$answer[$sn] = $tot;
		$sa .= '<tr><td style="border-top: 3px double;border-right:5px solid #000;" class="r">';
		if ($hasdecimals) {
			$sa .= number_format($tot,2,'.',',');
		} else {
			$sa .= number_format($tot);
		}
		$sa .= '</td>';
	} else {
		$sa .= '<tr><td style="border-top: 3px double;border-right:5px solid #000;">&nbsp;</td>';
		$anstypes[$sn] = 'string';
		$answer[$sn] = '';
	}
	$sn++;
	
	$out .= '<td style="border-top: 3px double;">[AB'.$sn.']</td></tr>';
	$answerboxsize[$sn] = $maxsize;
	$displayformat[$sn] = 'alignright';
	if ($tot<0) {
		$anstypes[$sn] = 'number';
		$answer[$sn] = -$tot;
		$sa .= '<td style="border-top: 3px double;" class="r"></tr>';
		if ($hasdecimals) {
			$sa .= number_format(-$tot,2,'.',',');
		} else {
			$sa .= number_format(-$tot);
		}
		$sa .= '</td>';
	} else {
		$sa .= '<td style="border-top: 3px double;">&nbsp;</td></tr>';
		$anstypes[$sn] = 'string';
		$answer[$sn] = '';
	}
	$sn++;
	
	$out .= '</tbody></table>';
	$sa .= '</tbody></table>';
	$showanswer .= $sa.'<p>&nbsp;</p>';	
	return $out;
}
?>

<?php
//A collection of accounting macros
//
//June 2013

global $allowedmacros;
array_push($allowedmacros,"makejournal","scorejournal","makeaccttable","makeaccttable2","makeTchart","scoreTchart","makestatement","scorestatement","makeinventory");

//makestatement(statement array, start number, options, $anstypes, $questions, $answer, $showanswer, $displayformat, $answerformat, $answerboxsize)
//statement array form:
//$statement[0]['bigtitle'] = "Title for whole table"
//$statement[#]['header'] = "title", or array ("title", value) if fixed value is associated
//$statement[#]['elements'] = array("Title",amount,"title",amount)
//$statement[#]['fixed'] = array(index into elements to have fixed - first col only)
//$statement[#]['totrows'] = total number of entry rows to show. optional, defaults to number of elements
//$statement[#]['tottitle'] = title for total row, if any
//$statement[#]['totaltotal'] = array("title", statement #, statement #)  Totals across multiple statement groups.  Neg to subtract
function makestatement($s, $sn, $ops, &$anstypes, &$questions, &$answer, &$showanswer, &$displayformat,  &$answerformat,  &$answerboxsize) {
	if ($anstypes === null) { $anstypes = array();}
	if ($questions === null) { $questions = array();}
	if ($answer === null) { $answer = array();}
	if ($showanswer === null) { $showanswer = '';}
	if ($displayformat === null) { $displayformat = array();}
	if ($answerformat === null) { $answerformat = array();}
	natsort($ops);
	if ($ops[0] == 'pulldowns') {
		array_shift($ops);
		$disptype = 'select';
	} else {
		$disptype = 'typeahead';
	}
		 
	
	$maxsizedescr = 0;  $maxsizeentry = 0; $hasdecimals = false;
	foreach ($s as $ix=>$jd) {
		if (!isset($jd['elements'])) {continue;}
		for ($i=0;$i<count($jd['elements']);$i+=2) {
			$sl = strlen($jd['elements'][$i]);
			if ($sl>$maxsizedescr) { $maxsizedescr = $sl;}
			$sl = strlen($jd['elements'][$i+1]);
			if ($sl>$maxsizeentry) { $maxsizeentry = $sl;}
			if (!$hasdecimals && strpos($jd['elements'][$i+1],'.')!==false) { $hasdecimals = true;}
		}
		foreach ($ops as $op) {
			$sl = strlen($op);
			if ($sl>$maxsizedescr) { $maxsizedescr = $sl;}
		}
	}
	$maxsizedescr += 6;
	$maxsizeentry += 3;
	if (isset($s[0]['bigtitle'])) {
		$out = '<table class="acctstatement"><thead><tr><td colspan="2" class="c"><b>'.$s[0]['bigtitle'].'</b></td></tr><thead><tbody>';
	} else {
		$out = '<table class="acctstatement"><tbody>';
	}
	$sa = $out;
	$grouptots = array();
	foreach ($s as $ix=>$sg) {
		if ($ix>0) {
			$out .= '<tr><td colspan="2">&nbsp;</td></tr>';
			$sa .= '<tr><td colspan="2">&nbsp;</td></tr>';
		}
		if (isset($sg['header'])) {
			if (is_array($sg['header'])) { 
				$out .= '<tr><td><b>'.$sg['header'][0].'</b></td><td class="r">'.$sg['header'][1].'</td></tr>';
				$sa .= '<tr><td><b>'.$sg['header'][0].'</b></td><td class="r">'.$sg['header'][1].'</td></tr>';
			} else {
				$out .= '<tr><td colspan="2"><b>'.$sg['header'].'</b></td></tr>';
				$sa .= '<tr><td colspan="2"><b>'.$sg['header'].'</b></td></tr>';
			}
		}
		if (isset($sg['elements'])) {
			$tot = 0;
			for ($i=0;$i<count($sg['elements']);$i+=2) {
				if (isset($sg['fixed']) && in_array($i, $sg['fixed'])) {
					if (!isset($sg['header']) && count($sg['elements']==2)) {
						$out .= '<tr><td><b>'.$sg['elements'][$i].'</b></td>';
					} else {
						$out .= '<tr><td>'.$sg['elements'][$i].'</td>';
					}
					$sn--;
				} else {
					$out .= '<tr><td>[AB'.$sn.']</td>';
					$anstypes[$sn] = 'string'; $displayformat[$sn] = $disptype; $questions[$sn] = $ops;  $answer[$sn] = $sg['elements'][$i]; $answerboxsize[$sn] = $maxsizedescr;
				}
				$out .= '<td>[AB'.($sn+1).']</td></tr>';
				$sg['elements'][$i+1] = str_replace(array('$',',',' '),'',$sg['elements'][$i+1])*1;
				$sa .= '<tr><td>'.$sg['elements'][$i].'</td><td class="r">'.($hasdecimals?number_format($sg['elements'][$i+1],2,'.',','):number_format($sg['elements'][$i+1])).'</td></tr>';
				$anstypes[$sn+1] = 'number'; $displayformat[$sn+1] = 'alignright'; $answerformat[$sn+1] = 'parenneg'; $answer[$sn+1] = $sg['elements'][$i+1]; $answerboxsize[$sn+1] = $maxsizeentry;
				$tot += $sg['elements'][$i+1];
				$sn += 2;
			}
			$grouptots[$ix] = $tot;
			if (isset($sg['totrows'])) {
				//echo "count: ".count($sg['elements'])/2." totr: ".$sg['totrows'].'<br/>';
				for($i=count($sg['elements'])/2;$i<$sg['totrows'];$i++) {
					$out .= '<tr><td>[AB'.$sn.']</td><td>[AB'.($sn+1).']</td></tr>';
					//$sa .= '<tr><td>&nbsp;</td><td class="r">&nbsp;</td></tr>';
					$anstypes[$sn] = 'string'; $displayformat[$sn] = $disptype; $questions[$sn] = $ops;  $answer[$sn] = ""; $answerboxsize[$sn] = $maxsizedescr;
					$anstypes[$sn+1] = 'string'; $displayformat[$sn+1] = 'alignright'; $answer[$sn+1] = ''; $answerboxsize[$sn+1] = $maxsizeentry;
					$sn += 2;
				}
			}
			if (isset($sg['tottitle'])) {
				$out .= '<tr><td><b>'.$sg['tottitle'].'</b></td><td style="border-bottom:1px solid">[AB'.$sn.']</td></tr>';
				$sa .= '<tr><td><b>'.$sg['tottitle'].'</b></td><td style="border-bottom:1px solid" class="r">'.($hasdecimals?number_format($tot,2,'.',','):number_format($tot)).'</td></tr>';
				$anstypes[$sn] = 'number'; $displayformat[$sn] = 'alignright'; $answerformat[$sn] = 'parenneg'; $answer[$sn] = $tot; $answerboxsize[$sn] = $maxsizeentry;
				$sn++;
			}
		}
		if (isset($sg['totaltotal'])) {
			$tottot = 0;
			for ($i=1;$i<count($sg['totaltotal']);$i++) {
				if ($sg['totaltotal'][$i]<0) {
					$tottot -= $grouptots[-1*$sg['totaltotal'][$i]];
				} else {
					$tottot += $grouptots[$sg['totaltotal'][$i]];
				}
			}
			$out .= '<tr><td><b>'.$sg['totaltotal'][0].'</b></td><td style="border-bottom:1px solid">[AB'.$sn.']</td></tr>';
			$sa .= '<tr><td><b>'.$sg['totaltotal'][0].'</b></td><td style="border-bottom:1px solid" class="r">'.($hasdecimals?number_format($tottot,2,'.',','):number_format($tottot)).'</td></tr>';
			$anstypes[$sn] = 'number'; $displayformat[$sn] = 'alignright'; $answerformat[$sn] = 'parenneg'; $answer[$sn] = $tottot; $answerboxsize[$sn] = $maxsizeentry;
			$sn++;
		}
	}
	
	$out .= '</tbody></table>';
	$sa .= '</tbody></table>';
	$showanswer .= $sa.'<p>&nbsp;</p>';
			
	return $out;
}


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
	
	if ($ops[0] == 'pulldowns') {
		array_shift($ops);
		$disptype = 'select';
	} else {
		$disptype = 'typeahead';
	}
	foreach ($ops as $op) {
		$sl = strlen($op);
		if ($sl>$maxsizedescr) { $maxsizedescr = $sl;}
	}
	$maxsizedescr = 0;  $maxsizeentry = 0; $hasdecimals = false;
	foreach ($j as $ix=>$jd) {
		for ($i=0;$i<count($jd['debits']);$i+=2) {
			$sl = strlen($jd['debits'][$i]);
			if ($sl>$maxsizedescr) { $maxsizedescr = $sl;}
			$sl = strlen($jd['debits'][$i+1]);
			if ($sl>$maxsizeentry) { $maxsizeentry = $sl;}
			if (!$hasdecimals && strpos($jd['debits'][$i+1],'.')!==false) { $hasdecimals = true;}
		}
		for ($i=0;$i<count($jd['credits']);$i+=2) {
			$sl = strlen($jd['credits'][$i]);
			if ($sl>$maxsizedescr) { $maxsizedescr = $sl;}
			$sl = strlen($jd['credits'][$i+1]);
			if ($sl>$maxsizeentry) { $maxsizeentry = $sl;}
			if (!$hasdecimals && strpos($jd['credits'][$i+1],'.')!==false) { $hasdecimals = true;}
		}
	}
	
	natsort($ops);
	$maxsizedescr += 6;
	$maxsizeentry += 3;
	$out = '<table class="gridded"><thead><tr><td>Date</td><td>Description</td><td>Debit</td><td>Credit</td></tr><thead><tbody>';
	$sa = $out;
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
			$sa .= '<td>'.$jd['debits'][$i].'</td><td class="r">'.($hasdecimals?number_format($jd['debits'][$i+1],2,'.',','):number_format($jd['debits'][$i+1])).'</td><td></td></tr>';
			$anstypes[$sn] = 'string'; $displayformat[$sn] = $disptype; $questions[$sn] = $ops;  $answer[$sn] = $jd['debits'][$i]; $answerboxsize[$sn] = $maxsizedescr;
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
			$sa .= '<td>&nbsp;&nbsp;&nbsp;'.$jd['credits'][$i].'</td><td></td><td class="r">'.($hasdecimals?number_format($jd['credits'][$i+1],2,'.',','):number_format($jd['credits'][$i+1])).'</td></tr>';
			//$anstypes[$sn] = 'string'; $displayformat[$sn] = 'typeahead'; $questions[$sn] = $ops;  $answer[$sn] = $jd['credits'][$i]; $answerboxsize[$sn] = $maxsizedescr;
			$anstypes[$sn] = 'string'; $displayformat[$sn] = $disptype; $questions[$sn] = $ops;  $answer[$sn] =$jd['debits'][$i]; $answerboxsize[$sn] = $maxsizedescr;
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

//scorestatement($stuanswers[$thisq], $answer, $statement array, start number)
//Call with $stuanswers, and $answer array
//and statement array and the multipart starting number for this statement
//returns a new $answer array
function scorestatement($stua, $answer, $s, $sn) {
	//any total and totaltotal entries will be fine.  Just need to handle multiple orders within a group.
	if ($stua == null) {return $answer;}
	
	$newans = $answer;
	foreach ($s as $ix=>$sg) {
		if (isset($sg['elements'])) {
			$n = count($sg['elements'])/2;
			for ($k=0;$k<count($sg['elements']);$k+=2) {
				$sg['elements'][$k+1] = str_replace(array('$',',',' '),'',$sg['elements'][$k+1])*1;
			}
			for ($i=$sn;$i<$sn+2*$n;$i+=2) {
				$matchtype = -1;  $matchall = -1;
				for ($k=0;$k<count($sg['elements']);$k+=2) {
					if (trim(strtolower($stua[$i]))==trim(strtolower($sg['elements'][$k]))) {
						$matchtype = $k;
						$stua[$i+1] = str_replace(array('$',',',' '),'',$stua[$i+1]);
						if ($stua[$i+1]{0}=='(') {
							$stua[$i+1] = -1*substr($stua[$i+1],1,-1);	
						}
						if (abs($stua[$i+1] - $sg['elements'][$k+1])<.01) {
							$matchall = $k;
							break;
						}
					}
				}
				if ($matchall>-1) {
					$newans[$i] = $sg['elements'][$matchall];
					$newans[$i+1] = $sg['elements'][$matchall+1];
					$locbak = $sn + 2*$matchall/2;
					//echo "ix: $ix, i: $i, loc: $loc, locbak: $locbak<br/>";
					for ($k=0;$k<2;$k++) {
						$newans[$locbak+$k] = $answer[$i+$k];
					}
				} else if ($matchtype>-1) {
					$newans[$i] = $sg['elements'][$matchtype];
					$newans[$i+1] = $sg['elements'][$matchtype+1];
					$locbak = $sn + 2*$matchtype/2;
					//echo "ix: $ix, i: $i, loc: $loc, locbak: $locbak<br/>";
					for ($k=0;$k<2;$k++) {
						$newans[$locbak+$k] = $answer[$i+$k];
					}
				} else {
					"no find $i";
				}
				
			}
			$sn += 2*count($sg['elements'])/2;
		}
		if (isset($sg['totrows'])) {
			$sn += 2*($sg['totrows'] - $n);
		}
		if (isset($sg['tottitle'])) {
			$sn++;
		}
		if (isset($sg['totaltotal'])) {
			$sn++;
		}
	}
	return $newans;
}

//scorejournal($stuanswers[$thisq], $answer, $journal, start number)
//Call with $stuanswers, and $answer array
//and journal array and the multipart starting number for this journal
//returns a new $answer array
function scorejournal($stua, $answer, $j, $sn) {
	if ($stua == null) {return $answer;}
	if (isset($j['date'])) {//not multiple date
		$new = array($j);
		$j = $new;
	}
	$newans = $answer;
	foreach ($j as $ix=>$jd) {
		$n = (count($jd['debits']) + count($jd['credits']))/2;
		for ($i=$sn;$i<$sn+3*$n;$i+=3) {
			$matchtype = array('n',-1); $matchall = array('n',-1);
			for ($k=0;$k<count($jd['debits'])/2;$k+=2) {
				$jd['debits'][$k] = str_replace(array('$',',',' '),'',$jd['debits'][$k])*1;
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
					$jd['credits'][$k] = str_replace(array('$',',',' '),'',$jd['credits'][$k])*1;
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
			if (!$hasdecimals && strpos($ansarray[$j][$i],'.')!==false) { $hasdecimals = true;}
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
			$sa .= '</td>';
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

//makeaccttable2(headers, $coltypes, $fixedrows, $cols, $sn, $anstypes, $answer, $showanswer, $displayformat, $answerformat, $answerboxsize)
//headers:  array(title, colspan, title, colspan,...) or array(title,title,title) or array of these for multiple headers
//coltypes: array(true if scores, false if fixed), one for each column
//fixedrows: array(title, colspan, title, colspan,...), ignores coltypes
//columsn: an array for each column of fixed values or answer values
function makeaccttable2($headers, $coltypes, $fixedrows, $cols, $sn, &$anstypes, &$answer, &$showanswer, &$displayformat, &$answerformat, &$answerboxsize) {
	if ($anstypes === null) { $anstypes = array();}
	if ($answer === null) { $answer = array();}
	if ($showanswer === null) { $showanswer = '';}
	if ($displayformat === null) { $displayformat = array();}
	
	$maxsize = array();  $hasdecimals = false;
	for ($j=0;$j<count($coltypes);$j++) {
		if ($coltypes[$j]==false) {continue;} //fixed column
		$maxsize[$j] = 0;
		for ($i=0;$i<count($cols[$j]);$i++) {
			$sl = strlen($cols[$j][$i]);
			if ($sl>$maxsize[$j]) { $maxsize[$j] = $sl;}
			if (!$hasdecimals && strpos($cols[$j][$i],'.')!==false) { $hasdecimals = true;}
		}
	}
	if (count($headers)!=0) {
		if (!is_array($headers[0])) {
			$headers = array($headers);
		}
		$out = '<table class="gridded"><thead>';
		foreach ($headers as $hdr) {
			$out .= '<tr>';
			if (isset($hdr[1]) && is_numeric($hdr[1])) {
				for ($i=0;$i<count($hdr);$i+=2) {
					$out .= '<th';
					if ($hdr[$i+1]>1) {
						$out .= ' colspan="'.$hdr[$i+1].'"';
					}
					$out .= '>'.$hdr[$i].'</th>';
				}
			} else {
				for ($i=0;$i<count($hdr);$i++) {
					$out .= '<th>'.$hdr[$i].'</th>';
				}
			}
			$out .= '</tr>';
		}
		'</thead>';
	} else {
		$out = '<table class="gridded">';
	}
	$out .= '<tbody>';
	$sa = $out;
	foreach ($fixedrows as $fr) {
		$out .= '<tr>';  $sa .= '<tr>';
		foreach ($fr as $el) {
			$out .= '<td class="r">'.$el.'</td>';  $sa .= '<td class="r">'.$el.'</td>';
		}
		$out .= '</tr>';  $sa .= '</tr>';
	}
	for ($i=0;$i<count($cols[0]);$i++) {
		$out .= '<tr>';  $sa .= '<tr>';
		for ($j=0;$j<count($coltypes);$j++) {
			if ($coltypes[$j]==false) {//fixed
				if ($cols[$j][$i]{0}==' ') { $cols[$j][$i] = '&nbsp;'.$cols[$j][$i];}
				$out .= '<td>'.$cols[$j][$i].'</td>';
				$sa .= '<td>'.$cols[$j][$i].'</td>';
				
			} else {
				if ($cols[$j][$i]==='nobox') {$out .= '<td></td>'; $sa.= '<td></td>'; continue;}
				
				$out .= '<td class="r">'.($cols[$j][$i]{0}=='$'?'$':'').'[AB'.$sn.']</td>';
				$sa .= '<td class="r">'.($cols[$j][$i]{0}=='$'?'$':'');
				
				$answer[$sn] = $cols[$j][$i];
				
				if ($cols[$j][$i]!=='') {
					$cols[$j][$i] = str_replace(array('$',','),'',$cols[$j][$i]) * 1;
					if ($hasdecimals) {
						$sa .= number_format($cols[$j][$i],2,'.',',');
					} else {
						$sa .= number_format($cols[$j][$i]);
					}
				}
				$sa .= '</td>';
				$answerboxsize[$sn] = $maxsize[$j];
				$displayformat[$sn] = 'alignright';
				$answerformat[$sn] = 'parenneg';
				if ($cols[$j][$i]!='') {
					$anstypes[$sn] = 'number';
				} else {
					$anstypes[$sn] = 'string';
				}
				$sn++;
			}
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

function makeinventory($invs, $type, $rowper, $sn, &$anstypes, &$answer, &$showanswer, &$displayformat, &$answerformat, &$answerboxsize) {
	$str = array();
	$dates = array();
	foreach($invs as $inv) {
		if ($inv[0]=='init') {
			$pq = array(""); $pu = array(""); $pt = array("");
			$sq = array(""); $su = array(""); $st = array("");
			$iq = array($inv[2]); $iu = array($inv[3]); $it = array($inv[2]*$inv[3]);
			$str[0] = array($inv[2],$inv[3]); //quantity, unit cost
		} else if ($inv[0]=='purch') {
			$str[] = array($inv[2],$inv[3]);
			$pq[] = $inv[2]; $pu[] = $inv[3]; $pt[] = $inv[2]*$inv[3];
			$sq[] = ""; $su[] = ""; $st[] = "";
			for($i=1;$i<$rowper;$i++) {
				$pq[] = "nobox"; $pu[] = "nobox"; $pt[] = "nobox";
				$sq[] = ""; $su[] = ""; $st[] = "";
			}
			foreach ($str as $s) {
				$iq[] = $s[0];  $iu[] = $s[1]; $it[] = $s[0]*$s[1];
			}
			for ($i=count($str);$i<$rowper;$i++) {
				$iq[] = "";  $iu[] = ""; $it[] = "";
			}
		} else if ($inv[0]=='sale') {
			$q = $inv[2];
			$c = 0;
			while($q>0) {
				if (count($str)==0) {echo "Trying to sell more than we have"; break;}
				if ($type=='FIFO') {
					if ($str[0][0]<=$q) { //not enough in stream - depleat it
						//transaction:  sell $str[0][0] units 
						$sq[] = $str[0][0];  $su[] = $str[0][1];  $st[] = $str[0][0] * $str[0][1];  $c++;
						$q -= $str[0][0];
						array_shift($str);  //remove entry
					} else { //got enough in stream 
						$sq[] = $q;  $su[] = $str[0][1];  $st[] = $q * $str[0][1];  $c++;
						$str[0][0] -= $q;
						$q -= $q;
					}
				} else if ($type=='LIFO') {
					$n = count($str)-1;
					if ($str[$n][0]<=$q) { //not enough in stream - depleat it
						//transaction:  sell $str[$n][0] units at $str[$n][1]
						$sq[] = $str[$n][0];  $su[] = $str[$n][1];  $st[] = $str[$n][0] * $str[$n][1];  $c++;
						$q -= $str[$n][0];
						array_pop($str);  //remove entry
					} else { //got enough in stream 
						$sq[] = $q;  $su[] = $str[$n][1];  $st[] = $q * $str[$n][1];  $c++;
						$str[$n][0] -= $q;
						$q -= $q;
					}
				}
			}
			for ($i=$c;$i<$rowper;$i++) {
				$sq[] = ""; $su[] = ""; $st[] = "";
			}
			$pq[] = ""; $pu[] = ""; $pt[] = "";
			for ($i=1;$i<$rowper;$i++) {
				$pq[] = "nobox"; $pu[] = "nobox"; $pt[] = "nobox";
			}
			foreach ($str as $s) {
				$iq[] = $s[0];  $iu[] = $s[1]; $it[] = $s[0]*$s[1];
			}
			for ($i=count($str);$i<$rowper;$i++) {
				$iq[] = "";  $iu[] = ""; $it[] = "";
			}
		}
		$dates[] = $inv[1];
		if ($inv[0] != 'init') {
			for ($i=1;$i<$rowper;$i++) {
				$dates[] = "";
			}
		}
	}
	$headers = array();
	$headers[0] = array("",1,"Purchases",3,"Cost of Goods Sold",3,"Inventory on Hand",3);
	$headers[1] = array("Dates","Quantity","Unit Cost","Total Cost","Quantity","Unit Cost","Total Cost","Quantity","Unit Cost","Total Cost");
	
	
	return makeaccttable2($headers, array(0, 1,1,1, 1,1,1, 1,1,1), array(), array($dates, $pq,$pu,$pt, $sq,$su,$st, $iq,$iu,$it), $sn, $anstypes, $answer, $showanswer, $displayformat, $answerformat, $answerboxsize);
}
?>

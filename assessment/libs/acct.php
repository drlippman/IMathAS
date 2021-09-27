<?php
//A collection of accounting macros
//
//June 2013

global $allowedmacros;
array_push($allowedmacros,"makejournal","scorejournal","makeaccttable","makeaccttable2","makeaccttable3","makeTchart","scoreTchart","makestatement","scorestatement","makeinventory","scoreinventory","makeTchartsfromjournal","scoreTchartsfromjournal","makeledgerfromjournal","maketrialbalance","maketrialbalancefromjournal","scoretrialbalance","scoretrialbalancefromjournal","totalsfromjournal","prettyacct");

//makestatement(statement array, start number, options, $anstypes, $questions, $answer, $showanswer, $displayformat, $answerformat, $answerboxsize)
//statement array form:
//$statement[0]['bigtitle'] = "Title for whole table"
//$statement[#]['header'] = "title", or array ("title", value) if fixed value is associated
//$statement[#]['headerops'] = array of options for header.  If not set, header is fixed.  Can't use with header with value.
//$statement[#]['elements'] = array("Title",amount,"title",amount)
//$statement[#]['explanation'] = array(explanation for each line of elements)
//$statement[#]['indent'] = number of indent for elements group.  If >0, then header and tottitle will be indented one less
//$statement[#]['fixed'] = array(index into elements to have fixed - first col only)
//$statement[#]['totrows'] = total number of entry rows to show. optional, defaults to number of elements
//$statement[#]['tottitle'] = title for total row
//$statement[#]['tottitleops'] = array of options for tottitle.  If not set, tottitle is fixed
//$statement[#]['tottitleline'] = -1 for on last line of elements, not set or 0 for own line.  Only works if indent>0
//$statement[#]['totneg'] = set to make total negative
//$statement[#]['totsigns'] = array of 1,-1,etc to indicate signs for totaling
//$statement[#]['totindent'] = number of indent for total entry.  0 if not set
//$statement[#]['totaltotal'] = array("title", statement #, statement #)  Totals across multiple statement groups.  Neg to subtract
//$statement[#]['totaltotalops'] = array of options for totaltotal.  If not set, totaltotal title is fixed
//$statement[#]['totalindent'] = number of indent for totaltotal entry.  0 if not set
//$statement[#]['dblunder'] = true if double-underline under the totaltotal entry
function makestatement($s, $sn, $ops=array(), &$anstypes, &$questions, &$answer, &$showanswer, &$displayformat,  &$answerformat,  &$answerboxsize) {
	$debug = false;
	if ($anstypes === null) { $anstypes = array();}
	if ($questions === null) { $questions = array();}
	if ($answer === null) { $answer = array();}
	if ($showanswer === null) { $showanswer = '';}
	if ($displayformat === null) { $displayformat = array();}
	if ($answerformat === null) { $answerformat = array();}

	if (isset($ops[0]) && $ops[0] == 'pulldowns') {
		array_shift($ops);
		$disptype = 'select';
	} else {
		$disptype = 'typeahead';
	}
	natsort($ops);
	$maxind = 0;


	$maxsizedescr = 0;  $maxsizeentry = 0; $hasdecimals = false;  $hasexp = false; $expspan = 0; $blankexp = '';
	foreach ($s as $ix=>$jd) {
		if (!isset($jd['elements'])) {continue;}
		for ($i=0;$i<count($jd['elements']);$i+=2) {
			if ($debug && !in_array($jd['elements'][$i], $ops) && !(isset($jd['fixed']) && in_array($i, $jd['fixed']))) {
				echo "Eek: ".$jd['elements'][$i]." not in options array<br/>";
			}
			$sl = strlen($jd['elements'][$i]);
			if ($sl>$maxsizedescr) { $maxsizedescr = $sl;}
			$sl = strlen($jd['elements'][$i+1]);
			if ($sl>$maxsizeentry) { $maxsizeentry = $sl;}
			if (!$hasdecimals && strpos($jd['elements'][$i+1],'.')!==false) { $hasdecimals = true;}
		}
		if (isset($jd['indent']) && $jd['indent']>$maxind) {
			$maxind = $jd['indent'];
		}
		if (isset($jd['totalindent']) && $jd['totalindent']>$maxind) {
			$maxind = $jd['totalindent'];
		}
		if (isset($jd['explanation'])) {
			$hasexp = true; $expspan = 1; $blankexp = '<td></td>';
		}
	}
	foreach ($ops as $op) {
		$sl = strlen($op);
		if ($sl>$maxsizedescr) { $maxsizedescr = $sl;}
	}
	$pre = array("");  $post = array("");
	if ($maxind>0) {
		for ($i=0;$i<=$maxind;$i++) {
			$pre[$i] = ''; $post[$i] = '';
			for ($j=$i;$j<$maxind;$j++) {
				$pre[$i] .= '<td></td>';
			}
			for ($j=0;$j<$i;$j++) {
				if ($j==0) {
					$post[$i] .= '<td class="f"></td>';
				} else {
					$post[$i] .= '<td></td>';
				}
			}
		}
	}

	$maxsizedescr += 6;
	$maxsizeentry += 3;
	if (isset($s[0]['bigtitle'])) {
		$out = '<table class="acctstatement"><caption><b>'.$s[0]['bigtitle'].'</b></caption>';
		$sa = '<table class="acctstatement"><caption><b>'.$s[0]['bigtitle'].'</b></caption>';
	} else {
		$out = '<table class="acctstatement">';
		$sa = '<table class="acctstatement">';
    }
    $out .= '<thead><tr class="sr-only"><th scope=col>Description</th><th scope=col>Amount</th>';    
    $sa .= '<thead><tr class="sr-only"><th scope=col>Description</th><th scope=col>Amount</th>';
    if ($maxind > 0) {
        $out .= '<th scope=col>Total</th>';
        $sa .= '<th scope=col>Total</th>';
    }
    for ($i=1;$i<$maxind;$i++) {
        $out .= '<th scope=col>Group Total</th>';
        $sa .= '<th scope=col>Group Total</th>';
    }
    $sa .= '</tr></thead>';
    $out .= '</tr></thead>';
    $out .= '<tbody>';
    $sa .= '<tbody>';
	$grouptots = array();  $addspacer = false;
	foreach ($s as $ix=>$sg) {
		if (isset($sg['indent']) && $sg['indent']>0) {
			$ind = $sg['indent'];
			$hdrindent = $sg['indent'] - 1;
			if (isset($sg['totindent'])) {
				$totindent = $sg['totindent'];
			} else {
				$totindent = $sg['indent'] - 1;
			}
		} else {
			$hdrindent = 0; $totindent = 0; $ind =0;
		}
		if ($addspacer && !isset($sg['nospacer'])) {
			$out .= '<tr aria-hidden=true><td colspan="'.($maxind+2).'">&nbsp;</td></tr>';
			$sa .= '<tr aria-hidden=true><td colspan="'.($maxind+2+$expspan).'">&nbsp;</td></tr>';
		}
		if (isset($sg['header'])) {
			$addspacer = false;
			if (is_array($sg['header'])) {
				$out .= '<tr><td style="padding-left:'.($hdrindent+.5).'em;"><span class="sr-only">Subcategory,</span><b>'.$sg['header'][0].'</b></td>'.$pre[$hdrindent].'<td class="r">'.$sg['header'][1].'</td>'.$post[$hdrindent].'</tr>';
				$sa .= '<tr><td style="padding-left:'.($hdrindent+.5).'em;"><span class="sr-only">Subcategory,</span><b>'.$sg['header'][0].'</b></td>'.$pre[$hdrindent].'<td class="r">'.$sg['header'][1].'</td>'.$post[$hdrindent].$blankexp.'</tr>';
			} else {
				if (isset($sg['headerops'])) {
					if ($sg['headerops'][0] == 'pulldowns') {
						array_shift($sg['headerops']);
						$tdisptype = 'select';
					} else {
						$tdisptype = 'typeahead';
					}
					natsort($sg['headerops']);
					if ($debug && !in_array($sg['header'], $sg['headerops'])) {
						echo "Eek: ".$sg['header']." not in header options array<br/>";
					}
					$anstypes[$sn] = 'string'; $displayformat[$sn] = $tdisptype; $questions[$sn] = $sg['headerops'];  $answer[$sn] = $sg['header']; $answerboxsize[$sn] = 40;
					$out .= '<tr><td colspan="'.($maxind+2).' style="padding-left:'.($hdrindent+.5).'em;""><span class="sr-only">Subcategory,</span>[AB'.$sn.']</td></tr>';
					$sa .= '<tr><td colspan="'.($maxind+2+$expspan).'" style="padding-left:'.($hdrindent+.5).'em;"><span class="sr-only">Subcategory,</span><b>'.$sg['header'].'</b></td></tr>';
					$sn++;
				} else {
					$out .= '<tr><td colspan="'.($maxind+2).'" style="padding-left:'.($hdrindent+.5).'em;"><span class="sr-only">Subcategory,</span><b>'.$sg['header'].'</b></td></tr>';
					$sa .= '<tr><td colspan="'.($maxind+2+$expspan).'" style="padding-left:'.($hdrindent+.5).'em;"><span class="sr-only">Subcategory,</span><b>'.$sg['header'].'</b></td></tr>';
				}
			}
		}
		if (isset($sg['elements'])) {
			$addspacer = true;
			$tot = 0;
			for ($i=0;$i<count($sg['elements']);$i+=2) {
				if (isset($sg['fixed']) && in_array($i, $sg['fixed'])) {
					if (!isset($sg['header']) && count($sg['elements']==2)) {
						$out .= '<tr><td style="padding-left:'.($ind+.5).'em;"><b>'.$sg['elements'][$i].'</b></td>';
					} else {
						$out .= '<tr><td style="padding-left:'.($ind+.5).'em;">'.$sg['elements'][$i].'</td>';
					}
					$sn--;
				} else {
					$out .= '<tr><td style="padding-left:'.($ind+.5).'em;">[AB'.$sn.']</td>';
					$anstypes[$sn] = 'string'; $displayformat[$sn] = $disptype; $questions[$sn] = $ops;  $answer[$sn] = $sg['elements'][$i]; $answerboxsize[$sn] = $maxsizedescr;
				}
				$out .= $pre[$ind].'<td class="r">[AB'.($sn+1).']</td>'.$post[$ind].'</tr>';
				$sg['elements'][$i+1] = str_replace(array('$',',',' '),'',$sg['elements'][$i+1])*1;
				$sa .= '<tr><td style="padding-left:'.($ind+.5).'em;">'.$sg['elements'][$i].'</td>'.$pre[$ind].'<td class="r">'.($hasdecimals?number_format($sg['elements'][$i+1],2,'.',','):number_format($sg['elements'][$i+1])).'</td>'.$post[$ind];
				if (isset($sg['explanation'])) {
					$sa .= '<td>'.$sg['explanation'][$i/2].'</td></tr>';
				} else {
					$sa .= $blankexp.'</tr>';
				}
				$anstypes[$sn+1] = 'number'; $displayformat[$sn+1] = 'alignright'; $answerformat[$sn+1] = 'parenneg'; $answer[$sn+1] = $sg['elements'][$i+1]; $answerboxsize[$sn+1] = $maxsizeentry;
				$tot += $sg['elements'][$i+1]*(isset($sg['totsigns'])?$sg['totsigns'][$i/2]:1);
				$sn += 2;
			}
			if (isset($sg['totneg'])) {
				$tot *= -1;
			}

			$grouptots[$ix] = $tot;
			if (isset($sg['totrows'])) {
				//echo "count: ".count($sg['elements'])/2." totr: ".$sg['totrows'].'<br/>';
				for($i=count($sg['elements'])/2;$i<$sg['totrows'];$i++) {
					$out .= '<tr><td style="padding-left:'.($ind+.5).'em;">[AB'.$sn.']</td>'.$pre[$ind].'<td class="r">[AB'.($sn+1).']</td>'.$post[$ind].'</tr>';
					//$sa .= '<tr><td>&nbsp;</td><td class="r">&nbsp;</td></tr>';
					$anstypes[$sn] = 'string'; $displayformat[$sn] = $disptype; $questions[$sn] = $ops;  $answer[$sn] = ""; $answerboxsize[$sn] = $maxsizedescr;
					$anstypes[$sn+1] = 'string'; $displayformat[$sn+1] = 'alignright'; $answer[$sn+1] = ''; $answerboxsize[$sn+1] = $maxsizeentry;
					$sn += 2;
				}
            }
            $didextraline = false;
			if (isset($sg['tottitle']) || isset($sg['tottitleline'])) {
				$p = strrpos($out,'<td class="r">');
                $out = substr($out,0,$p).'<td style="border-bottom:1px solid" '.substr($out,$p+4);
                $p = strpos($out,'</td>',$p);
                $out = substr($out,0,$p).'<span class="sr-only">Single line</span>'.substr($out,$p+5);
				$p = strrpos($sa,'<td class="r">');
                $sa = substr($sa,0,$p).'<td style="border-bottom:1px solid" '.substr($sa,$p+4);
                $p = strpos($sa,'</td>',$p);
                $sa = substr($sa,0,$p).'<span class="sr-only">Single line</span>'.substr($sa,$p+5);

                if ($totindent < $ind) {
                    $p = strrpos($out,'<td class="f"></td>');
                    $out = substr($out,0,$p).'<td class="f" style="border-bottom:1px solid"><span class="sr-only">Single line</span></td>'.substr($out,$p+19);
                    $p = strrpos($sa,'<td class="f"></td>');
                    $sa = substr($sa,0,$p).'<td class="f" style="border-bottom:1px solid"><span class="sr-only">Single line</span></td>'.substr($sa,$p+19);
                    $didextraline = true;
                }

				if (isset($sg['tottitleline']) && $sg['tottitleline']<0) {
					$p = strrpos($out,' class="f"><');
					$out = substr($out,0,$p).' class="r">[AB'.$sn.']'.substr($out,$p+11);
					$p = strrpos($sa,' class="f"><');
					$sa = substr($sa,0,$p).' class="r">'.($hasdecimals?number_format($tot,2,'.',','):number_format($tot)).substr($sa,$p+11);
				} else {
					if (isset($sg['tottitleops'])) {
						if ($sg['tottitleops'][0] == 'pulldowns') {
							array_shift($sg['tottitleops']);
							$tdisptype = 'select';
						} else {
							$tdisptype = 'typeahead';
						}
						if ($debug && !in_array($sg['tottitle'], $sg['tottitleops'])) {
							echo "Eek: ".$sg['tottitle']." not in total options array<br/>";
						}
						natsort($sg['tottitleops']);
						$anstypes[$sn] = 'string'; $displayformat[$sn] = $tdisptype; $questions[$sn] = $sg['tottitleops'];  $answer[$sn] = $sg['tottitle']; $answerboxsize[$sn] = $maxsizedescr;
						$out .= '<tr><td style="padding-left:'.($totindent+.5).'em;">[AB'.$sn.']</td>';
						$sa .= '<tr><td style="padding-left:'.($totindent+.5).'em;"><b>'.$sg['tottitle'].'</b></td>';
						$sn++;
					} else {
						$out .= '<tr><td style="padding-left:'.($totindent+.5).'em;"><b>'.$sg['tottitle'].'</b></td>';
						$sa .= '<tr><td style="padding-left:'.($totindent+.5).'em;"><b>'.$sg['tottitle'].'</b></td>';
					}
					$out .= $pre[$totindent].'<td class="r"><span class="sr-only">Single line</span>[AB'.$sn.']</td>'.$post[$totindent].'</tr>';
					$sa .= $pre[$totindent].'<td class="r"><span class="sr-only">Single line</span>'.($hasdecimals?number_format($tot,2,'.',','):number_format($tot)).'</td>'.$post[$hdrindent].$blankexp.'</tr>';

				}
				$anstypes[$sn] = 'number'; $displayformat[$sn] = 'alignright'; $answerformat[$sn] = 'parenneg'; $answer[$sn] = $tot; $answerboxsize[$sn] = $maxsizeentry;
				$sn++;
			}
		}
		if (isset($sg['totaltotal'])) {
			$addspacer = true;
			$p = strrpos($out,'<td class="r">');
            $out = substr($out,0,$p).'<td style="border-bottom:1px solid" '.substr($out,$p+4);
            $p = strpos($out,'</td>',$p);
            $out = substr($out,0,$p).'<span class="sr-only">Single line</span>'.substr($out,$p+5);

			$p = strrpos($sa,'<td class="r">');
            $sa = substr($sa,0,$p).'<td style="border-bottom:1px solid" '.substr($sa,$p+4);
            $p = strpos($sa,'</td>',$p);
            $sa = substr($sa,0,$p).'<span class="sr-only">Single line</span>'.substr($sa,$p+5);

			$tottot = 0;
			if (isset($sg['totalindent'])) {
                $totind = $sg['totalindent'];
			} else {
                $totind = 0;
            }
            if ($totind < $ind && !$didextraline) {
                $p = strrpos($out,'<td class="f"></td>');
                $out = substr($out,0,$p).'<td class="f" style="border-bottom:1px solid"><span class="sr-only">Single line</span></td>'.substr($out,$p+19);
                $p = strrpos($sa,'<td class="f"></td>');
                $sa = substr($sa,0,$p).'<td class="f" style="border-bottom:1px solid"><span class="sr-only">Single line</span></td>'.substr($sa,$p+19);
            }
			for ($i=1;$i<count($sg['totaltotal']);$i++) {
				if ($sg['totaltotal'][$i]<0) {
					$tottot -= $grouptots[-1*round($sg['totaltotal'][$i])];
				} else {
					$tottot += $grouptots[$sg['totaltotal'][$i]];
				}
			}
			if (isset($sg['totaltotalops'])) {
				if ($sg['totaltotalops'][0] == 'pulldowns') {
					array_shift($sg['totaltotalops']);
					$tdisptype = 'select';
				} else {
					$tdisptype = 'typeahead';
				}
				if ($debug && !in_array( $sg['totaltotal'][0], $sg['totaltotalops'])) {
					echo "Eek: ".$sg['totaltotal'][0]." not in total options array<br/>";
				}
				natsort($sg['totaltotalops']);
				$anstypes[$sn] = 'string'; $displayformat[$sn] = $tdisptype; $questions[$sn] = $sg['totaltotalops'];  $answer[$sn] = $sg['totaltotal'][0]; $answerboxsize[$sn] = $maxsizedescr;
				$out .= '<tr><td style="padding-left:'.($totind+.5).'em;">[AB'.$sn.']</td>';
				$sa .= '<tr><td style="padding-left:'.($totind+.5).'em;"><b>'.$sg['totaltotal'][0].'</b></td>';
				$sn++;
			} else {
				$out .= '<tr><td style="padding-left:'.($totind+.5).'em;"><b>'.$sg['totaltotal'][0].'</b></td>';
				$sa .= '<tr><td style="padding-left:'.($totind+.5).'em;"><b>'.$sg['totaltotal'][0].'</b></td>';
			}
			if (isset($sg['dblunder'])) {
                $under = ' style="border-bottom: 3px double #000"';
                $underlabel = '<span class="sr-only">Double line</span>';
			} else {
                $under = '';
                $underlabel = '';
			}
			$out .= $pre[$totind].'<td class="r"'.$under.'><span class="sr-only">Single line</span>[AB'.$sn.']'.$underlabel.'</td>'.$post[$totind].'</tr>';
			$sa .= $pre[$totind].'<td class="r"'.$under.'><span class="sr-only">Single line</span>'.($hasdecimals?number_format($tottot,2,'.',','):number_format($tottot)).$underlabel.'</td>'.$post[$totind].$blankexp.'</tr>';
			$anstypes[$sn] = 'number'; $displayformat[$sn] = 'alignright'; $answerformat[$sn] = 'parenneg'; $answer[$sn] = $tottot; $answerboxsize[$sn] = $maxsizeentry;
			$sn++;
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

	foreach ($s as $ix=>$sg) {
		if (isset($sg['header']) && isset($sg['headerops'])) { //there's a header to score.  Only one answer, so no work to do but advance $sn
			$sn++;
		}
		if (isset($sg['elements'])) {
			$n = count($sg['elements'])/2;
			$usedans = array();
			$i = $sn;
			//for ($i=$sn;$i<$sn+2*$n;$i+=2) {
			for ($iidx=0;$iidx<count($sg['elements']);$iidx+=2) {
				if (isset($sg['fixed']) && in_array($iidx, $sg['fixed'])) {
					$i += 1;
					continue; //don't need to swap since only one answer
				}
				$matchtype = -1;  $matchval = -1;
				$stua[$i+1] = floatval(str_replace(array('$',',',' '), '', $stua[$i+1]));
				//for ($k=$sn;$k<$sn+2*$n;$k+=2) {
				$k = $sn;
				for ($kidx=0;$kidx<count($sg['elements']);$kidx+=2) {
					if (isset($sg['fixed']) && in_array($kidx, $sg['fixed'])) {
						$k += 1;
						continue; //don't need to swap since only one answer
					}
					if (trim(strtolower($stua[$i]))==trim(strtolower($answer[$k]))) {
						$matchtype = $k;
						break;
					} else if (abs(floatval($stua[$i+1])-floatval($answer[$k+1]))<.01) {
						$matchval = $k;
					}
					$k += 2;
				}
				if ($matchtype > -1 && !in_array($matchtype,$usedans)) {
					$tmp = array();
					for ($k=0;$k<2;$k++) {
						$tmp[$k] = $answer[$i+$k];
					}
					for ($k=0;$k<2;$k++) {
						$answer[$i+$k] = $answer[$matchtype+$k];
					}
					for ($k=0;$k<2;$k++) {
						$answer[$matchtype+$k] = $tmp[$k];
					}
					$usedans[] = $i;
				} else if ($matchval > -1 && !in_array($matchval,$usedans)) {
					$tmp = array();
					for ($k=0;$k<2;$k++) {
						$tmp[$k] = $answer[$i+$k];
					}
					for ($k=0;$k<2;$k++) {
						$answer[$i+$k] = $answer[$matchval+$k];
					}
					for ($k=0;$k<2;$k++) {
						$answer[$matchval+$k] = $tmp[$k];
					}
				}
				$i += 2;
			}
			//$sn += 2*count($sg['elements'])/2;
			$sn = $i;
		}
		if (isset($sg['totrows'])) {
			$sn += 2*($sg['totrows'] - $n);
		}
		if (isset($sg['tottitle'])) {
			if (isset($sg['tottitleops'])) {
				$sn++;
			}
			$sn++;
		}
		if (isset($sg['tottitleline'])) {
			$sn++;
		}
		if (isset($sg['totaltotal'])) {
			if (isset($sg['totaltotalops'])) {
				$sn++;
			}
			$sn++;
		}
	}
	return $answer;
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
	$debug = false;
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

	$maxsizedescr = 0;  $maxsizeentry = 0; $hasdecimals = false; $maxsizepostref=0;
	foreach ($j as $ix=>$jd) {
		$usePostRefs = !empty($jd['haspostrefs']);
		$valinc = $usePostRefs?2:1;
        $colinc = $usePostRefs?3:2;
        if (isset($jd['debits'])) {
            for ($i=0;$i<count($jd['debits']);$i+=$colinc) {
                $sl = strlen($jd['debits'][$i]);
                if ($sl>$maxsizedescr) { $maxsizedescr = $sl;}
                if ($usePostRefs) {
                    $sl = strlen($jd['debits'][$i+1]);
                    if ($sl>$maxsizepostref) { $maxsizepostref = $sl; }
                }
                $sl = strlen($jd['debits'][$i+$valinc]);
                if ($sl>$maxsizeentry) { $maxsizeentry = $sl;}
                if (!$hasdecimals && strpos($jd['debits'][$i+$valinc],'.')!==false) { $hasdecimals = true;}
                if ($debug && !in_array($jd['debits'][$i], $ops)) {
                    echo "Eek: ".$jd['debits'][$i]." not in options array<br/>";
                }

            }
        }
        if (isset($jd['credits'])) {
            for ($i=0;$i<count($jd['credits']);$i+=$colinc) {
                $sl = strlen($jd['credits'][$i]);
                if ($sl>$maxsizedescr) { $maxsizedescr = $sl;}
                if ($usePostRefs) {
                    $sl = strlen($jd['credits'][$i+1]);
                    if ($sl>$maxsizepostref) { $maxsizepostref = $sl; }
                }
                $sl = strlen($jd['credits'][$i+$valinc]);
                if ($sl>$maxsizeentry) { $maxsizeentry = $sl;}
                if (!$hasdecimals && strpos($jd['credits'][$i+$valinc],'.')!==false) { $hasdecimals = true;}
                if ($debug && !in_array($jd['credits'][$i], $ops)) {
                    echo "Eek: ".$jd['credits'][$i]." not in options array<br/>";
                }
            }
        }
	}
	foreach ($ops as $op) {
		$sl = strlen($op);
		if ($sl>$maxsizedescr) { $maxsizedescr = $sl;}
	}
	natsort($ops);
	$maxsizedescr += 6;
	$maxsizeentry += 3;
	$maxsizeentry = max($maxsizeentry,10);
	$out = '<table class="acctstatement"><caption class="sr-only">Journal</caption><thead><tr><th scope=col>Date</th><th scope=col>Description</th>';
	if ($usePostRefs) {
		$out .= '<th scope=col>Post Ref.</th>';
	}
	$out .= '<th scope=col>Debit</th><th scope=col>Credit</th></tr></thead><tbody>';
	$sa = $out;
	foreach ($j as $ix=>$jd) {
		$usePostRefs = !empty($jd['haspostrefs']);
		$colinc = $usePostRefs?3:2;
		$valinc = $usePostRefs?2:1;
		$colspan = $usePostRefs ? 5 : 4;
		if ($ix>0) {
			$out .= '<tr aria-hidden=true><td colspan="'.$colspan.'">&nbsp;</td></tr>';
			$sa .= '<tr aria-hidden=true><td colspan="'.$colspan.'">&nbsp;</td></tr>';
		}
        $dateset = false;
        if (isset($jd['debits'])) {
            for ($i=0;$i<count($jd['debits']);$i+=$colinc) {
                $out .= '<tr><th scope=row>'.($dateset?'<span class="sr-only">':'').$jd['date'].($dateset?'</span>':'').'</th>';
                $sa .= '<tr><th scope=row>'.($dateset?'<span class="sr-only">':'').$jd['date'].($dateset?'</span>':'').'</th>';
                $dateset = true;
                $out .= '<td>[AB'.$sn.']</td><td>[AB'.($sn+1).']</td><td>[AB'.($sn+2).']</td>';
                if ($usePostRefs) {
                    $out .= '<td>[AB'.($sn+3).']</td>';
                }
                $out .= '</tr>';
                $anstypes[$sn] = 'string'; $displayformat[$sn] = $disptype; $questions[$sn] = $ops; $answer[$sn] = $jd['debits'][$i]; $answerboxsize[$sn] = $maxsizedescr;
                $sa .= '<td>'.$jd['debits'][$i].'</td>';
                if ($usePostRefs) {
                    $anstypes[$sn+1] = 'string'; $answer[$sn+1] = $jd['debits'][$i+1]; $answerboxsize[$sn+1] = $maxsizepostref;
                    $sa .= '<td>'.$jd['debits'][$i+1].'</td>';
                }

                if ($jd['debits'][$i+$valinc]=='') {
                    $anstypes[$sn+$valinc] = 'number'; $displayformat[$sn+$valinc] = 'debit';
                    $answer[$sn+$valinc] = ''; $answerboxsize[$sn+$valinc] = $maxsizeentry;
                    $sa .= '<td>'.$jd['debits'][$i].'</td><td class="r"></td><td></td></tr>';
                } else {
                    $jd['debits'][$i+$valinc] = str_replace(array('$',',',' '),'',$jd['debits'][$i+$valinc])*1;
                    $sa .= '<td class="r">'.($hasdecimals?number_format($jd['debits'][$i+$valinc],2,'.',','):number_format($jd['debits'][$i+$valinc])).'</td><td></td></tr>';
                    $anstypes[$sn+$valinc] = 'number'; $displayformat[$sn+$valinc] = 'debit';
                    $answer[$sn+$valinc] = $jd['debits'][$i+$valinc]; $answerboxsize[$sn+$valinc] = $maxsizeentry;
                }
                $anstypes[$sn+$colinc] = 'number'; $displayformat[$sn+$colinc] = 'credit';
                $answer[$sn+$colinc] = ''; $answerboxsize[$sn+$colinc] = $maxsizeentry;

                $sn += $colinc+1;
            }
        }
        if (isset($jd['credits'])) {
            for ($i=0;$i<count($jd['credits']);$i+=$colinc) {
                $out .= '<tr><th scope=row>'.($dateset?'<span class="sr-only">':'').$jd['date'].($dateset?'</span>':'').'</th>';
                $sa .= '<tr><th scope=row>'.($dateset?'<span class="sr-only">':'').$jd['date'].($dateset?'</span>':'').'</th>';
                $dateset = true;

                $out .= '<td>[AB'.$sn.']</td><td>[AB'.($sn+1).']</td><td>[AB'.($sn+2).']</td>';
                $sa .= '<td>&nbsp;&nbsp;&nbsp;'.$jd['credits'][$i].'</td>';
                if ($usePostRefs) {
                    $out .= '<td>[AB'.($sn+3).']</td>';
                    $sa .= '<td>'.$jd['credits'][$i+1].'</td>';
                }
                $out .= '</tr>';
                $jd['credits'][$i+$valinc] = str_replace(array('$',',',' '),'',$jd['credits'][$i+$valinc])*1;
                $sa .= '<td></td><td class="r">'.($hasdecimals?number_format($jd['credits'][$i+$valinc],2,'.',','):number_format($jd['credits'][$i+$valinc])).'</td></tr>';
                //$anstypes[$sn] = 'string'; $displayformat[$sn] = 'typeahead'; $questions[$sn] = $ops;  $answer[$sn] = $jd['credits'][$i]; $answerboxsize[$sn] = $maxsizedescr;
                $anstypes[$sn] = 'string'; $displayformat[$sn] = $disptype; $questions[$sn] = $ops;  $answer[$sn] =$jd['credits'][$i]; $answerboxsize[$sn] = $maxsizedescr;
                if ($usePostRefs) {
                    $anstypes[$sn+1] = 'string'; $answer[$sn+1] = $jd['credits'][$i+1]; $answerboxsize[$sn+1] = $maxsizepostref;
                }
                $anstypes[$sn+$valinc] = 'number'; $displayformat[$sn+$valinc] = 'debit';
                $answer[$sn+$valinc] = ''; $answerboxsize[$sn+$valinc] = $maxsizeentry;
                $anstypes[$sn+$colinc] = 'number'; $displayformat[$sn+$colinc] = 'credit';
                $answer[$sn+$colinc] = $jd['credits'][$i+$valinc]; $answerboxsize[$sn+$colinc] = $maxsizeentry;

                $sn += $colinc+1;
            }
        }
		if (isset($jd['extrarows'])) {
			for ($i=0;$i<$jd['extrarows'];$i++) {
				$out .= '<tr><th scope=row>'.($dateset?'<span class="sr-only">':'').$jd['date'].($dateset?'</span>':'').'</th>';
			    $dateset = true;

				$out .= '<td>[AB'.$sn.']</td><td>[AB'.($sn+1).']</td><td>[AB'.($sn+2).']</td>';
				if ($usePostRefs) {
					$out .= '<td>[AB'.($sn+3).']</td>';
				}
				$out .= '</tr>';
				$anstypes[$sn] = 'string'; $displayformat[$sn] = $disptype; $questions[$sn] = $ops;  $answer[$sn] = ""; $answerboxsize[$sn] = $maxsizedescr;
				if ($usePostRefs) {
					$anstypes[$sn+1] = 'string'; $answer[$sn+1] = ''; $answerboxsize[$sn+1] = $maxsizepostref;
				}
				$anstypes[$sn+$valinc] = 'string'; $displayformat[$sn+$valinc] = 'debit'; $answer[$sn+$valinc] = ''; $answerboxsize[$sn+$valinc] = $maxsizeentry;
				$anstypes[$sn+$colinc] = 'string'; $displayformat[$sn+$colinc] = 'credit'; $answer[$sn+$colinc] = ''; $answerboxsize[$sn+$colinc] = $maxsizeentry;
				$sn += $colinc+1;
			}
		}
		if (isset($jd['note'])) {
			$out .= '<tr><th scope=row><span class="sr-only">'.$jd['date'].'</span></th><td colspan="'.($colspan-1).'">'.$jd['note'].'</td></tr>';
			$sa .= '<tr><th scope=row><span class="sr-only">'.$jd['date'].'</span></th><td colspan="'.($colspan-1).'">'.$jd['note'].'</td></tr>';
		}
		if (isset($jd['explanation'])) {
			$sa .= '<tr><th scope=row><span class="sr-only">'.$jd['date'].'</span></th><td colspan="'.($colspan-1).'">'.$jd['explanation'].'</td></tr>';
		}
	}
	$out .= '</tbody></table>';
	$sa .= '</tbody></table>';
	$showanswer .= $sa.'<p>&nbsp;</p>';
	return $out;
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
	//$newans = $answer;
	foreach ($j as $ix=>$jd) {
		$offset = !empty($jd['haspostrefs'])?1:0;
		$n = (count($jd['debits']) + count($jd['credits']))/(2+$offset);
		$usedans = array();
		for ($i=$sn;$i<$sn+(3+$offset)*$n;$i+=3+$offset) {
			$matchtype = -1;  $matchval = -1;
			for ($k=$sn;$k<$sn+(3+$offset)*$n;$k+=3+$offset) {
				if (trim(strtolower($stua[$i]))==trim(strtolower($answer[$k]))) {
					$matchtype = $k;
					break;
				} else if (($answer[$k+1+$offset]=='' && $stua[$i+1+$offset]=='' &&
					abs(floatval($stua[$i+2+$offset]) - floatval($answer[$k+2+$offset]))<.01) ||
					($answer[$k+2+$offset]=='' && $stua[$i+2+$offset]=='' &&
					abs(floatval($stua[$i+1+$offset])-floatval($answer[$k+1+$offset]))<.01)
				) {
					$matchval = $k;
				}
			}
			if ($matchtype > -1 && !in_array($matchtype,$usedans)) {
				$tmp = array();
				for ($k=0;$k<3+$offset;$k++) {
					$tmp[$k] = $answer[$i+$k];
				}
				for ($k=0;$k<3+$offset;$k++) {
					$answer[$i+$k] = $answer[$matchtype+$k];
				}
				for ($k=0;$k<3+$offset;$k++) {
					$answer[$matchtype+$k] = $tmp[$k];
				}
				$usedans[] = $i;
			} else if ($matchval > -1 && !in_array($matchval,$usedans)) {
				$tmp = array();
				for ($k=0;$k<3+$offset;$k++) {
					$tmp[$k] = $answer[$i+$k];
				}
				for ($k=0;$k<3+$offset;$k++) {
					$answer[$i+$k] = $answer[$matchval+$k];
				}
				for ($k=0;$k<3+$offset;$k++) {
					$answer[$matchval+$k] = $tmp[$k];
				}
			}
		}
		//look at order:  look for debits after credits, and credits before debits
		//mark wrong the less harmful ones
		$debaftercred = array();  $firstcred = false;
		for ($i=$sn;$i<$sn+(3+$offset)*$n;$i+=3+$offset) {
			if ($stua[$i+1+$offset]!='' && $stua[$i+2+$offset]=='' && $firstcred) { $debaftercred[] = $i;}
			if ($stua[$i+1+$offset]=='' && $stua[$i+2+$offset]!='') { $firstcred = true; }
		}
		$credbeforedeb = array();  $firstdeb = false;
		for ($i=$sn+(3+$offset)*$n-3-$offset;$i>=$sn;$i-=(3+$offset)) {
			if ($stua[$i+1+$offset]!='' && $stua[$i+2+$offset]=='') { $firstdeb = true;}
			if ($stua[$i+1+$offset]=='' && $stua[$i+2+$offset]!='' && $firstdeb) { $credbeforedeb[] = $i; }
		}
		if (count($debaftercred)+count($credbeforedeb) > 0) {
			if (count($credbeforedeb) > count($debaftercred)) {
				foreach ($debaftercred as $i) {
					for ($k=0;$k<3+$offset;$k++) {
						$answer[$i+$k] = $answer[$i+$k].'50000';
					}
				}
			} else {
				foreach ($credbeforedeb as $i) {
					for ($k=0;$k<3+$offset;$k++) {
						$answer[$i+$k] = $answer[$i+$k].'50000';
					}
				}
			}
		}
		$sn += (3+$offset)*(count($jd['debits']) + count($jd['credits']))/(2+$offset) + (3+$offset)*$jd['extrarows'];
	}
	return $answer;
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
		$out = '<table class="acctstatement"><thead><tr>'.$out.'</tr></thead>';
	} else {
		$out = '<table class="acctstatement">';
	}
	$out .= '<tbody>';
	$sa = $out;
	for ($i=0;$i<count($rows[0]);$i++) {
		$out .= '<tr>';  $sa .= '<tr>';
		for ($j=0;$j<count($rows);$j++) {
			if ($rows[$j][$i][0]==' ') { $rows[$j][$i] = '&nbsp;'.$rows[$j][$i];}
			$out .= '<td>'.$rows[$j][$i].'</td>';
			$sa .= '<td>'.$rows[$j][$i].'</td>';
		}
		for ($j=0;$j<count($ansarray);$j++) {
			$out .= '<td class="r">'.($ansarray[$j][$i][0]=='$'?'$':'').'[AB'.$sn.']</td>';
			$sa .= '<td class="r">'.($ansarray[$j][$i][0]=='$'?'$':'');
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

//makeaccttable2(headers, $coltypes, $fixedrows, $cols, $sn, $anstypes, $answer, $showanswer, $displayformat, $answerformat, $answerboxsize, [$opts, $questions])
//headers:  array(title, colspan, title, colspan,...) or array(title,title,title) or array of these for multiple headers
//coltypes: array(true if scores, false if fixed), one for each column  (use 2 to add dollar signs when not already in column values)
//fixedrows: array of array(title, colspan, title, colspan,...), ignores coltypes
//columsn: an array for each column of fixed values or answer values
//opts: optionsal array of options:
//   $opts['totrow']: row to treat as totals row (decorates above and below with lines) - optional
//   $opts['class']: class to use for table
//   $opts['ops']: a list of typeahead options for string entries
//$questions: you only need to pass this if you're using $opts['ops']
function makeaccttable2($headers, $coltypes, $fixedrows, $cols, $sn, &$anstypes, &$answer, &$showanswer, &$displayformat, &$answerformat, &$answerboxsize, $opts=array(), &$questions=null) {
	if ($anstypes === null) { $anstypes = array();}
	if ($answer === null) { $answer = array();}
	if ($showanswer === null) { $showanswer = '';}
	if ($displayformat === null) { $displayformat = array();}
	if (isset($opts['totrow'])) { $totrow = $opts['totrow'];} else {$totrow = -1;}
	if (isset($opts['class'])) { $tblclass = $opts['class'];} else {$tblclass = 'acctstatement';}
	if (isset($opts['ops'])) {
		if ($opts['ops'][0] == 'pulldowns') {
			array_shift($opts['ops']);
			$strdisptype = 'select';
		} else {
			$strdisptype = 'typeahead';
		}
	} else {
		$strdisptype = '';
	}
	$maxsize = array();  $hasdecimals = false;
	for ($j=0;$j<count($coltypes);$j++) {
		if ($coltypes[$j]==false || $coltypes[$j]<0) {continue;} //fixed column
		$maxsize[$j] = 0;
		for ($i=0;$i<count($cols[$j]);$i++) {
			$sl = strlen($cols[$j][$i]);
			if ($sl>$maxsize[$j]) { $maxsize[$j] = $sl;}
			if (!$hasdecimals && strpos($cols[$j][$i],'.')!==false) { $hasdecimals = true;}
		}
		$maxsize[$j] += floor(($maxsize[$j]-0.5)/3);  //add size to account for commas
	}
	if (count($headers)!=0) {
		if (!is_array($headers[0])) {
			$headers = array($headers);
		}
        $out = '<table class="'.$tblclass.'">';
        if (abs($headers[0][1]) == count($cols)) {
            $out .= '<caption '.($headers[0][1] < 0 ?'class="sr-only"':'').'>'.$headers[0][0].'</caption>';
            array_shift($headers);
        }
        $out .= '<thead>';
		foreach ($headers as $hdr) {
			$out .= '<tr>';
			if (isset($hdr[1]) && is_numeric($hdr[1])) {
				for ($i=0;$i<count($hdr);$i+=2) {
					$out .= '<th scope=col';
					if ($hdr[$i+1]>1) {
						$out .= ' colspan="'.$hdr[$i+1].'"';
					}
					$out .= '>'.$hdr[$i].'</th>';
				}
			} else {
				for ($i=0;$i<count($hdr);$i++) {
					$out .= '<th scope=col>'.$hdr[$i].'</th>';
				}
			}
			$out .= '</tr>';
		}
		'</thead>';
	} else {
		$out = '<table class="'.$tblclass.'">';
	}
	$out .= '<tbody>';
	$sa = $out;
	foreach ($fixedrows as $fr) {
		$out .= '<tr>';  $sa .= '<tr>';
		foreach ($fr as $j=>$el) {
            if ($j==0 && $coltypes[0]==false) {
                $out .= '<th scope=row class="r">'.$el.'</th>';  
                $sa .= '<th scope=row class="r">'.$el.'</th>';
            } else {
                $out .= '<td class="r">'.$el.'</td>';  
                $sa .= '<td class="r">'.$el.'</td>';
            }
		}
		$out .= '</tr>';  $sa .= '</tr>';
	}
	for ($i=0;$i<count($cols[0]);$i++) {
		$out .= '<tr>';  $sa .= '<tr>';
		for ($j=0;$j<count($coltypes);$j++) {
            $beforetxt = '';
            $aftertxt = '';
			if ($i+1==$totrow) {
                $dec = ' style="border-bottom: 1px solid #000;"';
                $aftertxt = '<span class="sr-only">Single line</span>';
			} else if ($i==$totrow) {
                $dec = ' style="border-bottom: 3px double #000;"';
                $beforetxt = '<span class="sr-only">Single line</span>';
                $aftertxt = '<span class="sr-only">Double line</span>';
			} else {
                $dec = ''; 
			}
			if ($coltypes[$j]==false) {//fixed
				if ($cols[$j][$i][0]==' ') { $cols[$j][$i] = '&nbsp;'.$cols[$j][$i];}
                if ($cols[$j][$i] == '') { $cols[$j][$i] = '&nbsp;'; }
                if ($j==0) {
                    $out .= "<th$dec scope=row>".$beforetxt.$cols[$j][$i].$aftertxt.'</th>';
                    $sa .= "<th$dec scope=row>".$beforetxt.$cols[$j][$i].$aftertxt.'</th>';
                } else {
				    $out .= "<td$dec>".$beforetxt.$cols[$j][$i].$aftertxt.'</td>';
                    $sa .= "<td$dec>".$beforetxt.$cols[$j][$i].$aftertxt.'</td>';
                }

			} else {
				if ($i==$totrow && !isset($cols[$j][$i])) {
					$thistot = 0;
					for ($k=0;$k<$totrow;$k++) {
						$thistot += $cols[$j][$k];
					}
					$cols[$j][$i] = $thistot;
				}
				if ($cols[$j][$i]==='nobox') {$out .= "<td$dec></td>"; $sa.= "<td$dec></td>"; continue;}

				if (substr($cols[$j][$i],0,6)=='fixed:') {$f = substr($cols[$j][$i],6); $out .= "<td$dec $class>$beforetxt $f $aftertxt</td>"; $sa.= "<td$dec $class>$beforetxt $f $aftertxt</td>"; continue;}

				$out .= '<td'.$dec.' class="r">'.$beforetxt.(($cols[$j][$i][0]=='$'||$coltypes[$j]===2)?'$':'').'[AB'.$sn.']'.$aftertxt.'</td>';
				$sa .= '<td'.$dec.' class="r">'.$beforetxt.(($cols[$j][$i][0]=='$'||$coltypes[$j]===2)?'$':'');

				$cols[$j][$i] = str_replace('$','',$cols[$j][$i]);
				$answer[$sn] = $cols[$j][$i];

				if (is_numeric($cols[$j][$i])) {
					$cols[$j][$i] = str_replace(array('$',','),'',$cols[$j][$i]) * 1;
					if ($hasdecimals) {
						$sa .= number_format($cols[$j][$i],2,'.',',');
					} else {
						$sa .= number_format($cols[$j][$i]);
					}
					$displayformat[$sn] = 'alignright';
					$answerformat[$sn] = 'parenneg';
					$anstypes[$sn] = 'number';
				} else {
					$sa .= $cols[$j][$i];
					$anstypes[$sn] = 'string';
					if ($strdisptype != '') {
						$displayformat[$sn] = $strdisptype;
						$questions[$sn] = $opts['ops'];
					}
				}
				$answerboxsize[$sn] = $maxsize[$j];
				$sa .= $aftertxt.'</td>';
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

//makeaccttable3(headers, $coltypes, $fixedrows, $cols, $sn, $anstypes, $answer, $showanswer, $displayformat, $questions, $answerformat, $answerboxsize, $opts)
//headers:  array(title, colspan, title, colspan,...) or array(title,title,title) or array of these for multiple headers
//coltypes: array(true if scores, false if fixed), one for each column  (use 2 to add dollar signs when not already in column values, -1 for non-numeric)
//fixedrows: array(title, colspan, title, colspan,...), ignores coltypes
//columsn: an array for each column of fixed values or answer values
//opts: optionsal array of options:
//   $opts['totrow']: row to treat as totals row (decorates above and below with lines) - optional
//   $opts['class']: class to use for table
//   $opts['underline']: array of row=>underline style: 1 single, 2 double
//      can also do row=>[style, column] or row=>[style, [column, column]]
//   $opts['questions'][n] = array of pull-down options for column n
function makeaccttable3($headers, $coltypes, $fixedrows, $cols, $sn, &$anstypes, &$answer, &$showanswer, &$displayformat, &$questions, &$answerformat, &$answerboxsize, $opts=array()) {
	if ($anstypes === null) { $anstypes = array();}
	if ($answer === null) { $answer = array();}
	if ($showanswer === null) { $showanswer = '';}
	if ($displayformat === null) { $displayformat = array();}
	if (isset($opts['totrow'])) { $totrow = $opts['totrow'];} else {$totrow = -1;}
	if (isset($opts['class'])) { $tblclass = $opts['class'];} else {$tblclass = 'acctstatement';}

	$maxsize = array();  $hasdecimals = false;  $rowcnt = 0;
	// handle sparse arrays
	for ($i=0;$i<count($cols);$i++) {
		$rowcnt = max($rowcnt, max(array_keys($cols[$i]))+1);
	}
	for ($j=0;$j<count($coltypes);$j++) {
		if ($coltypes[$j]==false || $coltypes[$j]==-1) {continue;} //fixed column
		$maxsize[$j] = 0;
		foreach ($cols[$j] as $v) {
			$sl = strlen($v);
			if ($sl>$maxsize[$j]) { $maxsize[$j] = $sl;}
			if (!$hasdecimals && strpos($v, '.')!==false) { $hasdecimals = true;}
		}
		$maxsize[$j] += floor(($maxsize[$j]-0.5)/3);  //add size to account for commas
	}
	if (count($headers)!=0) {
		if (!is_array($headers[0])) {
			$headers = array($headers);
		}
		$out = '<table class="'.$tblclass.'">';
        if ($headers[0][1] == count($cols)) {
            $out .= '<caption>'.$headers[0][0].'</caption>';
            array_shift($headers);
        }
        $out .= '<thead>';
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
		$out = '<table class="'.$tblclass.'">';
	}
	$out .= '<tbody>';
	$sa = $out;
	foreach ($fixedrows as $fr) {
		$out .= '<tr>';  $sa .= '<tr>';
		foreach ($fr as $j=>$el) {
            if ($j==0 && $coltypes[0]==false) {
                $out .= '<th scope=row class="r">'.$el.'</th>';  
                $sa .= '<th scope=row class="r">'.$el.'</th>';
            } else {
                $out .= '<td class="r">'.$el.'</td>';  
                $sa .= '<td class="r">'.$el.'</td>';
            }
		}
		$out .= '</tr>';  $sa .= '</tr>';
	}
	for ($i=0;$i<$rowcnt;$i++) {
		$out .= '<tr>';  $sa .= '<tr>';
		for ($j=0;$j<count($coltypes);$j++) {
            $beforetxt = '';
            $aftertxt = '';
			if ($i+1==$totrow) {
                $dec = ' style="border-bottom: 3px double #000;"';
                $aftertxt = '<span class="sr-only">Double line</span>';
			} else if ($i==$totrow) {
                $dec = ' style="border-bottom: 3px double #000;"';
                $aftertxt = '<span class="sr-only">Double line</span>';
                $beforetxt = '<span class="sr-only">Double line</span>';
			} else if (!empty($opts['underline'][$i]) &&
				(!is_array($opts['underline'][$i]) ||
				(is_array($opts['underline'][$i][1]) && in_array($j, $opts['underline'][$i][1])) ||
				(!is_array($opts['underline'][$i][1]) && $j == $opts['underline'][$i][1]))
			) {
				$ustyle = is_array($opts['underline'][$i]) ? $opts['underline'][$i][0] : $opts['underline'][$i];
				if ($ustyle == 2) {
                    $dec = ' style="border-bottom: 3px double #000;"';
                    $aftertxt = '<span class="sr-only">Double line</span>';
				} else {
                    $dec = ' style="border-bottom: 1px solid #000;"';
                    $aftertxt = '<span class="sr-only">Single line</span>';
				}
			} else if (!empty($opts['underline'][$i-1]) &&
                (!is_array($opts['underline'][$i-1]) ||
                (is_array($opts['underline'][$i-1][1]) && in_array($j, $opts['underline'][$i-1][1])) ||
                (!is_array($opts['underline'][$i-1][1]) && $j == $opts['underline'][$i-1][1]))
            ) {
                $ustyle = is_array($opts['underline'][$i-1]) ? $opts['underline'][$i-1][0] : $opts['underline'][$i-1];
                if ($ustyle == 2) {
                    $beforetxt = '<span class="sr-only">Double line</span>';
                } else {
                    $beforetxt = '<span class="sr-only">Single line</span>';
                }
            } else {
				$dec = '';
			}
			if ($coltypes[$j]==false) {//fixed
				if ($cols[$j][$i][0]==' ') { $cols[$j][$i] = '&nbsp;'.$cols[$j][$i];}
				if ($cols[$j][$i] == '') { $cols[$j][$i] = '&nbsp;'; }
				if ($j==0) {
                    $out .= "<th$dec scope=row>".$beforetxt.$cols[$j][$i].$aftertxt.'</th>';
                    $sa .= "<th$dec scope=row>".$beforetxt.$cols[$j][$i].$aftertxt.'</th>';
                } else {
				    $out .= "<td$dec>".$beforetxt.$cols[$j][$i].$aftertxt.'</td>';
                    $sa .= "<td$dec>".$beforetxt.$cols[$j][$i].$aftertxt.'</td>';
                }
			} else {
				if (!isset($cols[$j][$i])) {
					if ($i==$totrow) {
						$thistot = 0;
						for ($k=0;$k<$totrow;$k++) {
							$thistot += $cols[$j][$k];
						}
						$cols[$j][$i] = $thistot;
					} else {
						$cols[$j][$i] = '';
					}
				}

				if ($coltypes[$j]<0) {
					$class = '';
				} else {
					$class = 'class="r"';
				}

				if ($cols[$j][$i]==='nobox') {$out .= "<td$dec></td>"; $sa.= "<td$dec></td>"; continue;}
				if (substr($cols[$j][$i],0,6)=='fixed:') {$f = substr($cols[$j][$i],6); $out .= "<td$dec $class>$beforetxt $f $aftertxt</td>"; $sa.= "<td$dec $class>$beforetxt $f $aftertxt</td>"; continue;}

				$out .= '<td'.$dec.' '.$class.'>'.$beforetxt.(($cols[$j][$i][0]=='$'||$coltypes[$j]===2)?'$':'').'[AB'.$sn.']'.$aftertxt.'</td>';
				$sa .= '<td'.$dec.' '.$class.'>'.$beforetxt.(($cols[$j][$i][0]=='$'||$coltypes[$j]===2)?'$':'');

				$answer[$sn] = $cols[$j][$i];
				if ($cols[$j][$i]!=='') {
					if ($coltypes[$j]>0) {
						$cols[$j][$i] = str_replace(array('$',','),'',$cols[$j][$i]) * 1;
						if ($hasdecimals) {
							$sa .= number_format($cols[$j][$i],2,'.',',');
						} else {
							$sa .= number_format($cols[$j][$i]);
						}
					} else {
						$sa .= $cols[$j][$i];
					}
				}
				$sa .= $aftertxt.'</td>';
				if ($cols[$j][$i]!='' && isset($opts['questions']) && isset($opts['questions'][$j])) {
					$anstypes[$sn] = 'string';
					$displayformat[$sn] = 'select';
					$questions[$sn] = $opts['questions'][$j];
				} else {
					$answerboxsize[$sn] = $maxsize[$j];
					$displayformat[$sn] = 'alignright';
					$answerformat[$sn] = 'parenneg';
					if ($cols[$j][$i]!='') {
						$anstypes[$sn] = 'number';
					} else {
						$anstypes[$sn] = 'string';
					}
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


//makeTchartsfromjournal($j, $order, $sn, $anstypes, $answer, $showanswer, $displayformat, $answerboxsize)
function makeTchartsfromjournal($j, $order, $sn, &$anstypes, &$answer, &$showanswer, &$displayformat, &$answerboxsize, $showtotal=true) {
	$out = '';
	$debits = array(); $credits = array();
	foreach ($j as $jd) {
        if (isset($jd['debits'])) {
            for ($i=0;$i<count($jd['debits']);$i+=2) {
                if ($jd['debits'][$i+1]=='') {continue;}
                if (!isset($debits[$jd['debits'][$i]])) { $debits[$jd['debits'][$i]] = array();}
                $debits[$jd['debits'][$i]][] = $jd['debits'][$i+1];
            }
        }
        if (isset($jd['credits'])) {
            for ($i=0;$i<count($jd['credits']);$i+=2) {
                if (!isset($credits[$jd['credits'][$i]])) { $credits[$jd['credits'][$i]] = array();}
                $credits[$jd['credits'][$i]][] = $jd['credits'][$i+1];
            }
        }
	}
	$max = 1;
	foreach ($order as $o) {
		if (isset($debits[$o]) && count($debits[$o])>$max) {
			$max = count($debits[$o]);
		}
		if (isset($credits[$o]) && count($credits[$o])>$max) {
			$max = count($credits[$o]);
		}
	}
	if (isset($j[0]['numrows'])) {
		$max = $j[0]['numrows'];
	}
	$showanswer .= '<br/>';
	foreach ($order as $o) {
		if (!isset($debits[$o])) {$debits[$o] = array();}
		if (!isset($credits[$o])) {$credits[$o] = array();}
		$out .= makeTchart($o, $max, $debits[$o], $credits[$o], $sn, $anstypes, $answer, $showanswer, $displayformat, $answerboxsize,true, $showtotal);
		$sn += $max*2 + 2;
	}
	$out .= '<br class="clear"/>';
	$showanswer .= '<br class="clear"/><p>&nbsp;</p>';
	return $out;
}

// scoreTchartsfromjournal($stua,$answer,$j,$order,$sn)
function scoreTchartsfromjournal($stua,$answer,$j,$order,$sn) {
	$out = '';
	$debits = array(); $credits = array();
	foreach ($j as $jd) {
        if (isset($jd['debits'])) {
            for ($i=0;$i<count($jd['debits']);$i+=2) {
                if ($jd['debits'][$i+1]=='') {continue;}
                if (!isset($debits[$jd['debits'][$i]])) { $debits[$jd['debits'][$i]] = array();}
                $debits[$jd['debits'][$i]][] = $jd['debits'][$i+1];
            }
        }
        if (isset($jd['credits'])) {
            for ($i=0;$i<count($jd['credits']);$i+=2) {
                if (!isset($credits[$jd['credits'][$i]])) { $credits[$jd['credits'][$i]] = array();}
                $credits[$jd['credits'][$i]][] = $jd['credits'][$i+1];
            }
        }
	}
	$max = 1;
	foreach ($order as $o) {
		if (isset($debits[$o]) && count($debits[$o])>$max) {
			$max = count($debits[$o]);
		}
		if (isset($credits[$o]) && count($credits[$o])>$max) {
			$max = count($credits[$o]);
		}
	}
	foreach ($order as $o) {
		if (!isset($debits[$o])) {$debits[$o] = array();}
		if (!isset($credits[$o])) {$credits[$o] = array();}
		$answer = scoreTchart($stua, $answer, $max, $debits[$o], $credits[$o], $sn);
		$sn += $max*2 + 2;
	}
	return $answer;
}

//makeTchart(title,numrows,leftentries,rightentries, start number, $anstypes, $answer, $showanswer, $displayformat, $answerboxsize, [dofloat, showtotal])
//num rows, leftentries, and rightentries should not include the total - that will be automatically added
function makeTchart($title,$numrows,$leftentries,$rightentries, $sn, &$anstypes, &$answer, &$showanswer, &$displayformat, &$answerboxsize, $dofloat = false, $showtotal=true) {
	$out = '<table class="tchart" '.($dofloat?'style="float:left;margin:10px;"':'').'><caption>'.$title.'</caption><thead><tr><th scope=col style="border-bottom:5px solid #000;"><span class="sr-only">Debit</span></th><th scope=col style="border-bottom:5px solid #000;"><span class="sr-only">Credit</span></th></tr></thead><tbody>';
	$sa = '<table class="tchart" '.($dofloat?'style="float:left;margin:10px;"':'').'><caption>'.$title.'</caption><thead><tr><th scope=col style="border-bottom:5px solid #000;"><span class="sr-only">Debit</span></th><th scope=col style="border-bottom:5px solid #000;"><span class="sr-only">Credit</span></th></tr></thead><tbody>';
	$maxsize = 0;
	for ($i=0;$i<count($leftentries);$i+=2) {
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
		$out .= '<tr><td style="border-right: 5px solid #000;" class="r">[AB'.$sn.']</td>';
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
			$anstypes[$sn] = 'number';
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
			$anstypes[$sn] = 'number';
			$answer[$sn] = '';
		}
		$sn++;
    }   
    $out .= '</tbody>';
    $sa .= '</tbody>';
	if ($showtotal !== false) {
        $out .= '<tfoot>';
        $sa .= '<tfoot>';
		$out .= '<tr><td style="border-top: 3px double;border-right:5px solid #000;" class="r"><span class="sr-only">Double line</span>[AB'.$sn.']</td>';
		$answerboxsize[$sn] = $maxsize;
		$displayformat[$sn] = 'alignright';
		if ($tot>0 || ($tot==0 && $showtotal==='zeroleft')) {
			$anstypes[$sn] = 'number';
			$answer[$sn] = $tot;
			$sa .= '<tr><td style="border-top: 3px double;border-right:5px solid #000;" class="r"><span class="sr-only">Double line</span>';
			if ($hasdecimals) {
				$sa .= number_format($tot,2,'.',',');
			} else {
				$sa .= number_format($tot);
			}
			$sa .= '</td>';
		} else {
			$sa .= '<tr><td style="border-top: 3px double;border-right:5px solid #000;"><span class="sr-only">Double line</span>&nbsp;</td>';
			$anstypes[$sn] = 'string';
			$answer[$sn] = '';
		}
		$sn++;

		$out .= '<td style="border-top: 3px double;"><span class="sr-only">Double line</span>[AB'.$sn.']</td></tr>';
		$answerboxsize[$sn] = $maxsize;
		$displayformat[$sn] = 'alignright';
		if ($tot<0 || ($tot==0 && $showtotal!=='zeroleft')) {
			$anstypes[$sn] = 'number';
			$answer[$sn] = -$tot;
			$sa .= '<td style="border-top: 3px double;" class="r"><span class="sr-only">Double line</span>';
			if ($hasdecimals) {
				$sa .= number_format(-$tot,2,'.',',');
			} else {
				$sa .= number_format(-$tot);
			}
			$sa .= '</td></tr>';
		} else {
			$sa .= '<td style="border-top: 3px double;"><span class="sr-only">Double line</span>&nbsp;</td></tr>';
			$anstypes[$sn] = 'string';
			$answer[$sn] = '';
        }
        $out .= '</tfoot>';
        $sa .= '</tfoot>';
		$sn++;
	}

	$out .= '</table>';
	$sa .= '</table>';
	if ($dofloat) {
		$showanswer .= $sa . '<br class="clear" />';
	} else {
		$showanswer .= $sa.'<p>&nbsp;</p>';
	}
	return $out;
}

//function scoreTchart($stua,$answer,$numrows,$leftentries,$rightentries, $sn)
//returns a new answer array
function scoreTchart($stua,$answer,$numrows,$leftentries,$rightentries, $sn) {
	if ($stua == null) {return $answer;}
	$origstua = $stua;
	for ($i=0;$i<count($leftentries);$i++) {
		$leftentries[$i] = str_replace(array('$',','),'',$leftentries[$i]) * 1;
	}
	for ($i=0;$i<count($rightentries);$i++) {
		$rightentries[$i] = str_replace(array('$',','),'',$rightentries[$i]) * 1;
	}
	$cntleft = count($leftentries);
	$cntright = count($rightentries);
	//change blanks to zeros
	for ($i=0;$i<$numrows;$i++) {
		$stua[$sn+2*$i] = str_replace(array('$',','),'',$stua[$sn+2*$i]);
		$stua[$sn+2*$i+1] = str_replace(array('$',','),'',$stua[$sn+2*$i+1]);
		if (trim($stua[$sn+2*$i])=='') {
			$stua[$sn+2*$i] = 0;
		}
		if (trim($stua[$sn+2*$i+1])=='') {
			$stua[$sn+2*$i+1] = 0;
		}
	}
	//fill out leftentries and rightentries with zeros
	for ($i=$cntleft;$i<$numrows;$i++) {
		$leftentries[] = 0;
	}
	for ($i=$cntright;$i<$numrows;$i++) {
		$rightentries[] = 0;
	}
	for ($i=0;$i<$numrows;$i++) {
		//look for match in left column
		$foundmatch = false;
		foreach($leftentries as $loc=>$val) {
			if (abs(floatval($stua[$sn+2*$i]) - $val)<.01) {
				$answer[$sn+2*$i] = $val;
				unset($leftentries[$loc]);
				$foundmatch = true;
				break; //from foreach
			}
		}

		if (!$foundmatch) {
			$answer[$sn+2*$i] = $stua[$sn+2*$i] + 50000;
		} else if (trim($origstua[$sn+2*$i])=='') {
			$answer[$sn+2*$i] = '';
		}

		//look for match in right column
		$foundmatch = false;
		foreach($rightentries as $loc=>$val) {
			if (abs(floatval($stua[$sn+2*$i+1]) - $val)<.01) {
				$answer[$sn+2*$i+1] = $val;
				unset($rightentries[$loc]);
				$foundmatch = true;
				break; //from foreach
			}
		}
		if (!$foundmatch) {
			$answer[$sn+2*$i+1] = floatval($stua[$sn+2*$i+1]) + 50000;
		} else if (trim($origstua[$sn+2*$i+1])=='') {
			$answer[$sn+2*$i+1] = '';
		}

	}
	return $answer;
}

//types:  1 if debits add to balance, -1 if debits subtract from balance
//start: starting balance
//makeledgerfromjournal($j, $start, $order, $types, $sn, $anstypes, $answer, $questions, $showanswer, $displayformat, $answerboxsize)
function makeledgerfromjournal($j, $start, $order, $types, $sn, &$anstypes, &$answer, &$questions, &$showanswer, &$displayformat, &$answerboxsize) {
	$out = ''; $sa = '';
	$acts = array();
	$dates = array();
	$hasdecimals = false;
	$maxsizeentry = 1;
	foreach ($j as $jd) {
		$dates[] = $jd['date'];
		for ($i=0;$i<count($jd['debits']);$i+=2) {
			if ($jd['debits'][$i+1]=='') {continue;}
			if (!isset($acts[$jd['debits'][$i]])) { $acts[$jd['debits'][$i]] = array();}
			$acts[$jd['debits'][$i]][] = array($jd['date'],'d',$jd['debits'][$i+1]);
			if (!$hasdecimals && strpos($jd['debits'][$i+1],'.')!==false) { $hasdecimals = true;}
			$sl = strlen($jd['debits'][$i+1]);
			if ($sl>$maxsizeentry) { $maxsizeentry = $sl;}
		}
		for ($i=0;$i<count($jd['credits']);$i+=2) {
			if (!isset($acts[$jd['credits'][$i]])) { $acts[$jd['credits'][$i]] = array();}
			$acts[$jd['credits'][$i]][] = array($jd['date'],'c',$jd['credits'][$i+1]);
			if (!$hasdecimals && strpos($jd['credits'][$i+1],'.')!==false) { $hasdecimals = true;}
			$sl = strlen($jd['credits'][$i+1]);
			if ($sl>$maxsizeentry) { $maxsizeentry = $sl;}
		}
	}
	$curbal = $start;
	foreach ($order as $idx=>$o) {
		$out .= '<table class="acctstatement"><caption>'.$o.'</caption><thead><tr><th scope=col>Date</th><th scope=col>Debits</th><th scope=col>Credits</th><th>Balance</th></tr></thead><tbody>';
		$sa .= '<table class="acctstatement"><thead><caption>'.$o.'</caption><thead><tr><th scope=col>Date</th><th scope=col>Debits</th><th scope=col>Credits</th><th>Balance</th></tr></thead><tbody>';
		$out .= '<tr><th scope=row class="r">Beg. Bal.</th><td></td><td></td><td class="r">'.($hasdecimals?number_format($start[$idx],2,'.',','):number_format($start[$idx])).'</td></tr>';
		$sa .= '<tr><th scope=row class="r">Beg. Bal.</th><td></td><td></td><td class="r">'.($hasdecimals?number_format($start[$idx],2,'.',','):number_format($start[$idx])).'</td></tr>';
		foreach ($acts[$o] as $a) {
			$out .= '<tr><th scope=row>[AB'.$sn.']</th>';
			$sa .= '<tr><th scope=row>'.$a[0].'</th>';
			$anstypes[$sn] = 'string'; $displayformat[$sn] = 'select'; $questions[$sn] = $dates;  $answer[$sn] = $a[0];
			$sn++;
			if ($a[1]=='d') {
				$out .= '<td class="r">[AB'.$sn.']</td>';
				$sa .= '<td class="r">'.($hasdecimals?number_format($a[2],2,'.',','):number_format($a[2])).'</td>';
				$anstypes[$sn] = 'number'; $displayformat[$sn] = 'alignright'; $answer[$sn] = $a[2]; $answerboxsize[$sn] = $maxsizeentry;
				$sn++;
				$out .= '<td class="r">[AB'.$sn.']</td>';
				$sa .= '<td class="r"></td>';
				$anstypes[$sn] = 'string'; $displayformat[$sn] = 'alignright'; $answer[$sn] = ''; $answerboxsize[$sn] = $maxsizeentry;
				$sn++;
				if ($types[$idx]==1) {
					$curbal[$idx] += $a[2];
				} else {
					$curbal[$idx] -= $a[2];
				}
			} else {
				$out .= '<td class="r">[AB'.$sn.']</td>';
				$sa .= '<td class="r"></td>';
				$anstypes[$sn] = 'string'; $displayformat[$sn] = 'alignright'; $answer[$sn] = ''; $answerboxsize[$sn] = $maxsizeentry;
				$sn++;
				$out .= '<td class="r">[AB'.$sn.']</td>';
				$sa .= '<td class="r">'.($hasdecimals?number_format($a[2],2,'.',','):number_format($a[2])).'</td>';
				$anstypes[$sn] = 'number'; $displayformat[$sn] = 'alignright';$answer[$sn] = $a[2]; $answerboxsize[$sn] = $maxsizeentry;
				$sn++;
				if ($types[$idx]==1) {
					$curbal[$idx] -= $a[2];
				} else {
					$curbal[$idx] += $a[2];
				}
			}
			$out .= '<td class="r">[AB'.$sn.']</td></tr>';
			$sa .= '<td class="r">'.($hasdecimals?number_format($curbal[$idx],2,'.',','):number_format($curbal[$idx])).'</td></tr>';
			$anstypes[$sn] = 'number'; $displayformat[$sn] = 'alignright'; $answer[$sn] = $curbal[$idx]; $answerboxsize[$sn] = $maxsizeentry;
			$sn++;
		}
		for ($j=count($acts[$o]);$j<max(count($acts[$o])+1,3);$j++) {
			$out .= '<tr><th scope=row>[AB'.$sn.']</th>';
			$anstypes[$sn] = 'string'; $displayformat[$sn] = 'select'; $questions[$sn] = $dates;  $answer[$sn] = '';
			$sn++;
			$out .= '<td class="r">[AB'.$sn.']</td>';
			$anstypes[$sn] = 'string'; $displayformat[$sn] = 'alignright'; $answer[$sn] = ''; $answerboxsize[$sn] = $maxsizeentry;
			$sn++;
			$out .= '<td class="r">[AB'.$sn.']</td>';
			$anstypes[$sn] = 'string'; $displayformat[$sn] = 'alignright'; $answer[$sn] = ''; $answerboxsize[$sn] = $maxsizeentry;
			$sn++;
			$out .= '<td class="r">[AB'.$sn.']</td>';
			$anstypes[$sn] = 'string'; $displayformat[$sn] = 'alignright'; $answer[$sn] = ''; $answerboxsize[$sn] = $maxsizeentry;
			$sn++;
		}
		$out .= '</tbody></table><br/>';
		$sa .=  '</tbody></table><br/>';
	}
	$showanswer .= $sa.'<p>&nbsp;</p>';
	return $out;
}

//scoreinventory($stua, $answer, $invs, $rowper, $sn)
function scoreinventory($stua, $answer, $invs, $rowper, $sn) {
	foreach($invs as $inv) {
		if ($inv[0]=='init') {
			$sn += 9;
		} else {
			$sn += 3; //skip past purch
			for ($i=$sn+3;$i<$sn+$rowper*6;$i+=6) {  //start on inventory
				if ($stua[$i]=='') {continue;}
				$foundmatch = false;
				for ($j=$sn+3;$j<$sn+$rowper*6;$j+=6) {
					if (trim($stua[$i])==$answer[$j] && trim($stua[$i+1])==$answer[$j+1]) {
						$foundmatch = true;
						$matchloc = $j;
						break;
					}
				}
				if ($foundmatch && $i != $matchloc) {
					$tmp = array();
					for ($k=0;$k<3;$k++) {
						$tmp[$k] = $answer[$i+$k];
					}
					for ($k=0;$k<3;$k++) {
						$answer[$i+$k] = $answer[$matchloc+$k];
					}
					for ($k=0;$k<3;$k++) {
						$answer[$matchloc+$k] = $tmp[$k];
					}
				}
			}
			if ($inv[0]=='sale') {
				for ($i=$sn;$i<$sn+$rowper*6;$i+=6) {  //do cogs
					if ($stua[$i]=='') {continue;}
					$foundmatch = false;
					for ($j=$sn;$j<$sn+$rowper*6;$j+=6) {
						if (trim($stua[$i])==$answer[$j] && trim($stua[$i+1])==$answer[$j+1]) {
							$foundmatch = true;
							$matchloc = $j;
							break;
						}
					}
					if ($foundmatch && $i != $matchloc) {
						$tmp = array();
						for ($k=0;$k<3;$k++) {
							$tmp[$k] = $answer[$i+$k];
						}
						for ($k=0;$k<3;$k++) {
							$answer[$i+$k] = $answer[$matchloc+$k];
						}
						for ($k=0;$k<3;$k++) {
							$answer[$matchloc+$k] = $tmp[$k];
						}
					}
				}
			}
			$sn += $rowper*6;
		}

	}
	return $answer;
}

//makeinventory($invs, $type, $rowper, $sn, &$anstypes, $questions, &$answer, &$showanswer, &$displayformat, &$answerformat, &$answerboxsize, $get) {
//invs has form array(array('type','date',quantity, value)), where type is 'init','purch',or 'sale'
//get can be set to 'journal' or 'totals', but defaults to 'rec', which generates a perpetual inventory record
//'journal' returns array(journal record, journal display)
//type is FIFO, LIFO, or WA, or SPEC
//if SPEC, then the form of $invs changes for sales, and should be 'sale','date',array(quantity, purchase#, quantity, purchase #, ...)) where
//init is purchase #0.
//rowsper sets the number of rows for each date.  Will get extended for a particular date if needed
function makeinventory($invs, $type, $rowper, $sn, &$anstypes, &$questions, &$answer, &$showanswer, &$displayformat, &$answerformat, &$answerboxsize, $get='rec') {
	$str = array();  $jc = 0; $totsales = 0; $salesexp = array();
	$dates = array();
	foreach($invs as $inv) {
		$thisrowper = $rowper;
		if ($inv[0]=='init') {
			$pq = array(""); $pu = array(""); $pt = array("");
			$sq = array(""); $su = array(""); $st = array("");
			$iq = array($inv[2]); $iu = array($inv[3]); $it = array($inv[2]*$inv[3]);
			$str[0] = array($inv[2],$inv[3]); //quantity, unit cost
			/*if ($get=='journal') {
				$j[$jc]['date'] = $inv[1];
				$j[$jc]['debits'] = array("No journal entry required","");
				$j[$jc]['extrarows'] = 1;
				$jc++;
			}*/
		} else if ($inv[0]=='purch') {
			if ($type=='WA') {
				$newq = $inv[2] + $str[0][0];
				$newu = ($inv[2]*$inv[3] + $str[0][0]*$str[0][1])/$newq;
				$str[0] = array($newq, $newu);
			} else {
				$str[] = array($inv[2],$inv[3]);
			}
			$pq[] = $inv[2]; $pu[] = $inv[3]; $pt[] = $inv[2]*$inv[3];
			$sq[] = ""; $su[] = ""; $st[] = "";
			$sc = 0;
			foreach ($str as $s) {
				if ($s[0]==0) {continue;}
				$sc++;
				$iq[] = $s[0];  $iu[] = $s[1]; $it[] = $s[0]*$s[1];
			}

			$thisrowper = max($sc,$rowper);

			for($i=1;$i<$thisrowper;$i++) {
				$pq[] = "nobox"; $pu[] = "nobox"; $pt[] = "nobox";
				$sq[] = ""; $su[] = ""; $st[] = "";
			}

			for ($i=$sc;$i<$thisrowper;$i++) {
				$iq[] = "";  $iu[] = ""; $it[] = "";
			}
			if ($get=='journal') {
				$j[$jc]['date'] = $inv[1];
				$j[$jc]['debits'] = array("Inventory", $inv[2]*$inv[3]);
				$j[$jc]['credits'] = array("Accounts Payable", $inv[2]*$inv[3]);
				$jc++;
			}
		} else if ($inv[0]=='sale') {
			$c = 0;  $cogs = 0; $thiscalc = '';
			if ($type=='SPEC') {
				for ($i=0;$i<count($inv[2]);$i+=2) {
					if (isset($inv[3])) { $totsales += $inv[2][$i]*$inv[3]; $salesexp[] = $inv[2][$i].' @ $'.$inv[3];}
					$loc = $inv[2][$i+1];
					if ($str[$loc][0]<$inv[2][$i]) {echo "Trying to sell more than we have: {$inv[2][$i]} from $loc which has {$str[$loc][0]}. "; break;}
					$sq[] = $inv[2][$i];  $su[] = $str[$loc][1];  $st[] = $inv[2][$i] * $str[$loc][1];  $cogs += $inv[2][$i] * $str[$loc][1]; $c++;
					$thiscalc .= $inv[2][$i].' units @ $'.$str[$loc][1].'; ';
					$str[$loc][0] -= $inv[2][$i];
				}
			} else {
				$q = $inv[2];
				if (isset($inv[3])) { $totsales += $inv[2]*$inv[3];}
				while($q>0) {
					if (count($str)==0) {echo "Trying to sell more than we have"; break;}
					if ($type=='FIFO') {
						if ($str[0][0]<=$q) { //not enough in stream - depleat it
							//transaction:  sell $str[0][0] units
							$sq[] = $str[0][0];  $su[] = $str[0][1];  $st[] = $str[0][0] * $str[0][1];  $cogs += $str[0][0] * $str[0][1]; $c++;
							$q -= $str[0][0];
							$thiscalc .= $str[0][0].' units @ $'.$str[0][1].'; ';
							array_shift($str);  //remove entry
						} else { //got enough in stream
							$sq[] = $q;  $su[] = $str[0][1];  $st[] = $q * $str[0][1]; $cogs += $q * $str[0][1]; $c++;
							$str[0][0] -= $q;
							$thiscalc .= $q.' units @ $'.$str[0][1].'; ';
							$q -= $q;

						}
					} else if ($type=='LIFO') {
						$n = count($str)-1;
						if ($str[$n][0]<=$q) { //not enough in stream - depleat it
							//transaction:  sell $str[$n][0] units at $str[$n][1]
							$sq[] = $str[$n][0];  $su[] = $str[$n][1];  $st[] = $str[$n][0] * $str[$n][1]; $cogs += $str[$n][0] * $str[$n][1]; $c++;
							$q -= $str[$n][0];
							$thiscalc .= $str[$n][0].' units @ $'.$str[$n][1].'; ';
							array_pop($str);  //remove entry
						} else { //got enough in stream
							$sq[] = $q;  $su[] = $str[$n][1];  $st[] = $q * $str[$n][1]; $cogs += $q * $str[$n][1]; $c++;
							$str[$n][0] -= $q;
							$thiscalc .= $q.' units @ $'.$str[$n][1].'; ';
							$q -= $q;

						}
					} else if ($type=='WA') {
						if ($q>$str[0][0]) {echo "Trying to sell more than we have"; break;}
						$sq[] = $q; $su[] = $str[0][1]; $st[] = round($q*$str[0][1],2); $cogs += round($q*$str[0][1],2); $c++;
						$str[0][0] -= $q;
						$q -= $q;
						$thiscalc .= $q.' units @ '.round($str[0][1],2);
					}
				}
			}
			$thisrowper = max($rowper,$c);
			for ($i=$c;$i<$thisrowper;$i++) {
				$sq[] = ""; $su[] = ""; $st[] = "";
			}
			$pq[] = ""; $pu[] = ""; $pt[] = "";
			for ($i=1;$i<$thisrowper;$i++) {
				$pq[] = "nobox"; $pu[] = "nobox"; $pt[] = "nobox";
			}
			$sc = 0;
			foreach ($str as $s) {
				if ($s[0]==0) {continue;}
				$sc++;
				$iq[] = $s[0];  $iu[] = $s[1]; $it[] = $s[0]*$s[1];
			}
			for ($i=$sc;$i<$thisrowper;$i++) {
				$iq[] = "";  $iu[] = ""; $it[] = "";
			}
			if ($get=='journal') {
				$j[$jc]['date'] = $inv[1];
				$j[$jc]['debits'] = array("Accounts Receivable", $inv[2]*$inv[3]);
				$j[$jc]['credits'] = array("Sales", $inv[2]*$inv[3]);
				$j[$jc]['explanation'] = $inv[2].' units * $'.$inv[3].' sales price';
				$jc++;
				$j[$jc]['debits'] = array("Cost of Goods Sold", $cogs);
				$j[$jc]['credits'] = array("Inventory", $cogs);
				$j[$jc]['explanation'] = $thiscalc;
				$jc++;
			}
		}
		$dates[] = $inv[1];
		if ($inv[0] != 'init') {
			for ($i=1;$i<$thisrowper;$i++) {
				$dates[] = '<span class="sr-only">'.$inv[1].'</span>';
			}
		}
	}
    $headers = array();
    $headers[0] = array("Inventory", -10);
	$headers[1] = array("",1,"Purchases",3,"Cost of Goods Sold",3,"Inventory on Hand",3);
	$headers[2] = array("Dates","Quantity","Unit Cost","Total Cost","Quantity","Unit Cost","Total Cost","Quantity","Unit Cost","Total Cost");

	if ($get=='totals') {
		$cogs = 0;
		foreach ($st as $stv) {
			if ($stv!=='') {
				$cogs += $stv;
			}
		}
		$totinv = 0; $totinvval = 0;
		foreach ($str as $s) {
			$totinv += $s[0];
			$totinvval += round($s[0]*$s[1],2);
		}
		return array($cogs, $totinv, $totinvval, $totsales);
	} else if ($get=='journal') {
		return array($j,makejournal($j, $sn, array("Accounts Receivable","Sales","Cost of Goods Sold","Inventory","Accounts Payable","No journal entry required"), $anstypes, $questions, $answer, $showanswer, $displayformat, $answerboxsize));
	} else {
		return makeaccttable2($headers, array(0, 1,1,1, 1,1,1, 1,1,1), array(), array($dates, $pq,$pu,$pt, $sq,$su,$st, $iq,$iu,$it), $sn, $anstypes, $answer, $showanswer, $displayformat, $answerformat, $answerboxsize);
	}
}

//maketrialbalancefromjournal($j, $groups, $sn, $numrows, $ops, $bigtitle, &$anstypes, &$answer, &$questions,  &$showanswer, &$displayformat, &$answerboxsize)
//j is journal
//groups is array(array of assets, liab, equity, revenue, expenses)
function maketrialbalancefromjournal($j, $groups, $sn, $numrows, $ops, $bigtitle, &$anstypes, &$answer, &$questions,  &$showanswer, &$displayformat, &$answerboxsize) {
	$debug = false;
	$out = '';
	$totals = array();
	foreach ($j as $jd) {
		for ($i=0;$i<count($jd['debits']);$i+=2) {
			if ($jd['debits'][$i+1]=='') {continue;}
			if (!isset($totals[$jd['debits'][$i]])) { $totals[$jd['debits'][$i]] = 0;}
			$totals[$jd['debits'][$i]] -= $jd['debits'][$i+1];
			if ($debug && !in_array($jd['debits'][$i], $ops)) {
				echo "Eek: ".$jd['debits'][$i]." not in options array<br/>";
			}
		}
		for ($i=0;$i<count($jd['credits']);$i+=2) {
			if (!isset($totals[$jd['credits'][$i]])) { $totals[$jd['credits'][$i]] = 0;}
			$totals[$jd['credits'][$i]] += $jd['credits'][$i+1];
			if ($debug && !in_array($jd['credits'][$i], $ops)) {
				echo "Eek: ".$jd['credits'][$i]." not in options array<br/>";
			}
		}
	}
	$types = array('assets','liabilities','equity','revenue','expenses');
	$data = array();
	for ($i=0;$i<5;$i++) {
		$data[$types[$i]] = array();
		foreach ($groups[$i] as $a) {
			$data[$types[$i]][] = $a;
			if ($types[$i]=='assets' || $types[$i]=='expenses') {
				$data[$types[$i]][] = -1*$totals[$a];
			} else {
				$data[$types[$i]][] = $totals[$a];
			}
		}
	}
	return maketrialbalance($data, $sn, $numrows, $ops, $bigtitle, $anstypes, $answer, $questions, $showanswer, $displayformat, $answerboxsize);
}


function scoretrialbalancefromjournal($stua, $answer, $j, $groups, $numrows, $sn) {
	if ($stua == null) {return $answer;}
	$out = '';
	$totals = array();
	foreach ($j as $jd) {
		for ($i=0;$i<count($jd['debits']);$i+=2) {
			if ($jd['debits'][$i+1]=='') {continue;}
			if (!isset($totals[$jd['debits'][$i]])) { $totals[$jd['debits'][$i]] = 0;}
			$totals[$jd['debits'][$i]] -= $jd['debits'][$i+1];
		}
		for ($i=0;$i<count($jd['credits']);$i+=2) {
			if (!isset($totals[$jd['credits'][$i]])) { $totals[$jd['credits'][$i]] = 0;}
			$totals[$jd['credits'][$i]] += $jd['credits'][$i+1];
		}
	}
	$types = array('assets','liabilities','equity','revenue','expenses');
	$data = array();
	for ($i=0;$i<5;$i++) {
		$data[$types[$i]] = array();
		foreach ($groups[$i] as $a) {
			$data[$types[$i]][] = $a;
			if ($types[$i]=='assets' || $types[$i]=='expenses') {
				$data[$types[$i]][] = -1*$totals[$a];
			} else {
				$data[$types[$i]][] = $totals[$a];
			}
		}
	}
	return scoretrialbalance($stua, $answer, $data, $numrows, $sn);
}

//maketrialbalance($data, $sn, $numrows, $ops, $bigtitle, &$anstypes, &$answer, &$questions, &$showanswer, &$displayformat, &$answerboxsize) {
//$data['assets'] = array(account, value, account, value)
//['liabilities'], [equity],[revenue],[expenses]
function maketrialbalance($data, $sn, $numrows, $ops, $bigtitle, &$anstypes, &$answer, &$questions, &$showanswer, &$displayformat, &$answerboxsize) {
	$out .= '<table class="acctstatement noborder"><caption>'.$bigtitle.'</caption><thead><tr><th>Accounts</th><th>Debits</th><th>Credits</th></tr></thead><tbody>';
	$sa .= '<table class="acctstatement noborder"><caption>'.$bigtitle.'</caption><thead><tr><th>Accounts</th><th>Debits</th><th>Credits</th></tr></thead><tbody>';
	$allaccts = array();
	$maxsizedescr = 4; $hasdecimals = false;
	foreach ($data as $t=>$dt) {
		for ($i=0;$i<count($dt);$i+=2) {
			if (!in_array($dt[$i],$ops)) {
				$ops[] = $dt[$i];
			}
			$sl = strlen($dt[$i]);
			if ($sl>$maxsizedescr) { $maxsizedescr = $sl;}
			if (!$hasdecimals && strpos($dt[$i+1],'.')!==false) { $hasdecimals = true;}
		}
	}
	$c = 0; $totdeb = 0; $totcred = 0; $maxsizedescr += 6;
	foreach ($data as $t=>$dt) {
		for($i=0;$i<count($dt);$i+=2) {
			$out .= '<tr><td>[AB'.$sn.']</td>';
			$sa .= '<tr><td>'.$dt[$i].'</td>';
			$anstypes[$sn] = 'string'; $displayformat[$sn] = 'typeahead'; $questions[$sn] = $ops;  $answer[$sn] = $dt[$i]; $answerboxsize[$sn] = $maxsizedescr;
			$sn++;
			if ((($t=='assets' || $t=='expenses') && $dt[$i+1]>=0) || (!($t=='assets' || $t=='expenses') && $dt[$i+1]<0)) {
				$out .= '<td>[AB'.$sn.']</td>';
				$sa .= '<td class="r">'.($hasdecimals?number_format(abs($dt[$i+1]),2,'.',','):number_format(abs($dt[$i+1]))).'</td>';
				$anstypes[$sn] = 'number'; $displayformat[$sn] = 'alignright'; $answer[$sn] = abs($dt[$i+1]); $answerboxsize[$sn] = 8;
				$sn++;
				$out .= '<td class="r">[AB'.$sn.']</td>';
				$sa .= '<td class="r"></td>';
				$anstypes[$sn] = 'string'; $displayformat[$sn] = 'alignright'; $answer[$sn] = ''; $answerboxsize[$sn] = 8;
				$sn++;
				$totdeb += abs($dt[$i+1]);
			} else {
				$out .= '<td class="r">[AB'.$sn.']</td>';
				$sa .= '<td class="r"></td>';
				$anstypes[$sn] = 'string'; $displayformat[$sn] = 'alignright'; $answer[$sn] = ''; $answerboxsize[$sn] = 8;
				$sn++;
				$out .= '<td>[AB'.$sn.']</td>';
				$sa .= '<td class="r">'.($hasdecimals?number_format(abs($dt[$i+1]),2,'.',','):number_format(abs($dt[$i+1]))).'</td>';
				$anstypes[$sn] = 'number'; $displayformat[$sn] = 'alignright'; $answer[$sn] = abs($dt[$i+1]); $answerboxsize[$sn] = 8;
				$sn++;
				$totcred += abs($dt[$i+1]);
			}
			$c++;
		}
	}
	for ($j=$c; $j<$numrows;$j++) {
		$out .= '<tr><td>[AB'.$sn.']</td>';
		$anstypes[$sn] = 'string'; $displayformat[$sn] = 'typeahead'; $questions[$sn] = $ops;  $answer[$sn] = $dt[$i]; $answerboxsize[$sn] = $maxsizedescr;
		$sn++;
		$out .= '<td class="r">[AB'.$sn.']</td>';
		$anstypes[$sn] = 'string'; $displayformat[$sn] = 'alignright'; $answer[$sn] = ''; $answerboxsize[$sn] = 8;
		$sn++;
		$out .= '<td class="r">[AB'.$sn.']</td>';
		$anstypes[$sn] = 'string'; $displayformat[$sn] = 'alignright'; $answer[$sn] = ''; $answerboxsize[$sn] = 8;
		$sn++;
    }
    $out .= '</tbody><tfoot>';
    $sa .= '</tbody><tfoot>';
	$out .= '<tr><td class="r"><b>Total</b></td>';
	$sa .= '<tr><td class="r"><b>Total</b></td>';
	$out .= '<td style="border-top:1px solid;border-bottom:3px double;"><span class="sr-only">Single line</span>[AB'.$sn.']<span class="sr-only">Double line</span></td>';
	$sa .= '<td class="r" style="border-top:1px solid;border-bottom:3px double;"><span class="sr-only">Single line</span>'.($hasdecimals?number_format($totdeb,2,'.',','):number_format($totdeb)).'<span class="sr-only">Double line</span></td>';
	$anstypes[$sn] = 'number'; $displayformat[$sn] = 'alignright'; $answer[$sn] = $totdeb; $answerboxsize[$sn] = 8;
	$sn++;
	$out .= '<td style="border-top:1px solid;border-bottom:3px double;"><span class="sr-only">Single line</span>[AB'.$sn.']<span class="sr-only">Double line</span></td>';
	$sa .= '<td class="r" style="border-top:1px solid;border-bottom:3px double;"><span class="sr-only">Single line</span>'.($hasdecimals?number_format($totcred,2,'.',','):number_format($totcred)).'<span class="sr-only">Double line</span></td>';
	$anstypes[$sn] = 'number'; $displayformat[$sn] = 'alignright'; $answer[$sn] = $totcred; $answerboxsize[$sn] = 8;
	$sn++;
	$out .= '</tfoot></table>';
	$sa .=  '</tfoot></table>';
	$showanswer .= $sa.'<p>&nbsp;</p>';
	return $out;
}

//scoretrialbalance($stua, $answer, $data, $numrows, $sn)
//$data['assets'] = array(account, value, account, value)
//['liabilities'], [equity],[revenue],[expenses]
function scoretrialbalance($stua, $answer, $data, $numrows, $sn) {
	if ($stua == null) {return $answer;}
	$accttype=array(); $snmap = array();  $c = $sn;
	foreach ($data as $t=>$dt) {
		$nq += count($dt)/2;
		$ansdat[$t] = array();
		for ($i=0;$i<count($dt);$i+=2) {
			$accttype[$dt[$i]] = $t;  //maps accounts->acct group
			$snmap[$dt[$i]] = $c;  //maps accounts->answerbox number
			$c += 3;
		}
	}
	//order idea via Aaron Johnson
	$okorders = array('assets','liabilities','equity','expenses','revenue','assetsassets','assetsliabilities','assetsequity','assetsrevenue','assetsexpenses','liabilitiesliabilities','liabilitiesequity','liabilitiesrevenue','liabilitiesexpenses','equityequity','equityrevenue','equityexpenses','revenuerevenue','revenueexpenses','expenses','expensesexpenses');
	for ($i=$sn;$i<$sn+$numrows*3;$i+=3) { //look for misordered answers
		$ord = '';
		if ($i>$sn && $stua[$i-3]!='') {
			$ord .= isset($accttype[$stua[$i-3]])?$accttype[$stua[$i-3]]:'bad';
		}
		$bad = 0;
		$ord .= isset($accttype[$stua[$i]])?$accttype[$stua[$i]]:'bad';
		if (!in_array($ord,$okorders)) { //invalid order
			$bad++;
		}
		$ord = isset($accttype[$stua[$i]])?$accttype[$stua[$i]]:'bad';
		if ($i<$sn+$numrows-1 && $stua[$i+3]!='') {
			$ord .= isset($accttype[$stua[$i+3]])?$accttype[$stua[$i+3]]:'bad';
		}
		if (!in_array($ord,$okorders)) { //doubly wrong - make it incorrect
			$bad++;
		}
		if ($bad>0) {
			$stua[$i] = '';
		}
	}
	//now score by groups.  We know stua is in the right order
	for ($j=$sn;$j<$sn+$numrows*3;$j+=3) {
		if ($stua[$j]=='') {continue;}
		$foundmatch = false;
		for ($i=$sn;$i<$sn+$nq*3;$i+=3) {
			if (trim(strtolower($stua[$j]))==trim(strtolower($answer[$i]))) {
				$foundmatch = true;
				$matchloc = $i;
				break; //from stua loop
			}
		}
		if ($foundmatch && $matchloc != $j) {
			//swap answer from $answer[$i] to $answer[$j]
			$tmp = array();
			for ($k=0;$k<3;$k++) {
				$tmp[$k] = $answer[$j+$k];
			}
			for ($k=0;$k<3;$k++) {
				$answer[$j+$k] = $answer[$matchloc+$k];
			}
			for ($k=0;$k<3;$k++) {
				$answer[$matchloc+$k] = $tmp[$k];
			}
		}
	}


	for ($i=$sn;$i<$sn+$nq*3;$i+=3) {
		if ($stua[$i]==='' && $answer[$i]!=='') {
			$answer[$i] = 'wrong';
			$answer[$i+1] = $stua[$i+1].'wrong';
			$answer[$i+2] = $stua[$i+2].'wrong';
		}
	}

	return $answer;
}


function totalsfromjournal($j) {
	$totals = array();
	foreach ($j as $jd) {
		for ($i=0;$i<count($jd['debits']);$i+=2) {
			if ($jd['debits'][$i+1]=='') {continue;}
			if (!isset($totals[$jd['debits'][$i]])) { $totals[$jd['debits'][$i]] = 0;}
			$totals[$jd['debits'][$i]] -= $jd['debits'][$i+1];
		}
		for ($i=0;$i<count($jd['credits']);$i+=2) {
			if (!isset($totals[$jd['credits'][$i]])) { $totals[$jd['credits'][$i]] = 0;}
			$totals[$jd['credits'][$i]] += $jd['credits'][$i+1];
		}
	}
	return $totals;
}

function prettyacct($v) {
	if (strpos($v,'.')!==false) {
		return number_format($v,2,'.',',');
	} else {
		return number_format($v);
	}
}

?>

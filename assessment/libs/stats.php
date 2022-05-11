<?php
//A library of Stats functions.  Version 1.10, Nov 17, 2017

global $allowedmacros;
array_push($allowedmacros,"nCr","nPr","mean","stdev","variance","absmeandev","percentile",
 "interppercentile","Nplus1percentile","quartile","TIquartile","Excelquartile",
 "Excelquartileexc","Nplus1quartile","allquartile","median","freqdist","frequency",
 "histogram","fdhistogram","fdbargraph","normrand","expdistrand","boxplot","normalcdf",
 "tcdf","invnormalcdf","invtcdf","invtcdf2","linreg","expreg","countif","binomialpdf",
 "binomialcdf","chicdf","invchicdf","chi2cdf","invchi2cdf","fcdf","invfcdf","piechart",
 "mosaicplot","checklineagainstdata","chi2teststat","checkdrawnlineagainstdata",
 "csvdownloadlink","modes","forceonemode","dotplot","gamma_cdf","gamma_inv","beta_cdf","beta_inv",
 "anova1way_f","anova1way","anova2way","anova_table","anova2way_f","student_t");

//nCr(n,r)
//The Choose function
function nCr($n,$r){
   if ($n < 0 || $r < 0 || !is_finite($n) || !is_finite($r)) {
     echo 'invalid input to nCr';
     return false;
   }
   if ($r > $n)
     return false;
   if (($n-$r) < $r)
     return nCr($n,($n-$r));
   $return = 1;
   for ($i=0;$i < $r;$i++){
     $return *= ($n-$i)/($i+1);
   }
   return $return;
}

//nPr(n,r)
//The Permutations function
function nPr($n,$r){
   if ($n < 0 || $r < 0 || !is_finite($n) || !is_finite($r)) {
     echo 'invalid input to nPr';
     return false;
   }
   if ($r > $n)
     return false;
   if ($r==0)
     return 1;
   $return = 1;
   $i = $n-$r;
   while ($i<$n) {
     $return *= ++$i;
   }
   return $return;
}

//mean(array)
//Finds the mean of an array of numbers
function mean($a,$w=null) {
	if (!is_array($a)) {
		echo 'mean expects an array';
		return false;
	}
  if (is_array($w)) {
    if (count($a) != count($w)) {
      echo 'weights must have same count as array';
      return false;
    }
    for ($i=0;$i<count($a);$i++) {
      $a[$i] *= $w[$i];
    }
    return (array_sum($a)/array_sum($w));
  } else {
	  return (array_sum($a)/count($a));
  }
}

//variance(array)
//the (sample) variance of an array of numbers
function variance($a,$w=null) {
	if (!is_array($a)) {
		echo 'stdev/variance expects an array';
		return false;
	}
  $useW = false;
  if (is_array($w)) {
    if (count($a) != count($w)) {
      echo 'weights must have same count as array';
      return false;
    }
    $useW = true;
  }
	$v = 0;
	$mean = mean($a,$w);
	foreach ($a as $i=>$x) {
		$v += pow($x-$mean,2) * ($useW ? $w[$i] : 1);
	}
	return ($v/(($useW ? array_sum($w) : count($a))-1));
}

//stdev(array)
//the (sample) standard deviation of an array of numbers
function stdev($a,$w=null) {
	return sqrt(variance($a,$w));
}

//absmeandev(array)
//the absolute mean deviation of an array of numbers
function absmeandev($a) {
	if (!is_array($a)) {
		echo 'absmeandev expects an array';
		return false;
	}
	$v = 0;
	$mean = mean($a);
	foreach ($a as $x) {
		$v += abs($x-$mean);
	}
	return ($v/(count($a)));
}

//percentile(array,percentile)
//example: percentile($a,30) would find the 30th percentile of the data
//Calculates using the p/100*(N) method (e.g. Triola)
function percentile($a,$p) {
	if (!is_array($a)) {
		echo 'percentile expects an array';
		return false;
	}
	if ($p<0 || $p>100) {
		echo 'invalid percentage';
		return false;
	}
	sort($a, SORT_NUMERIC);
	if ($p==0) {
		return $a[0];
	} else if ($p==100) {
		return $a[count($a)-1];
	}

	$l = round($p*count($a)/100,2);
	if (floor($l)==$l) {
		return (($a[$l-1]+$a[$l])/2);
	} else {
		return ($a[ceil($l)-1]);
	}
}

//Nplus1percentile(array,percentile)
//example: percentile($a,30) would find the 30th percentile of the data
//Calculates using the p/100*(N+1) method (e.g. OpenStax).
function Nplus1percentile($a,$p) {
	if (!is_array($a)) {
		echo 'percentile expects an array';
		return false;
	}
	if ($p<0 || $p>100) {
		echo 'invalid percentage';
		return false;
	}
	sort($a, SORT_NUMERIC);
	if ($p==0) {
		return $a[0];
	} else if ($p==100) {
		return $a[count($a)-1];
	}

	$l = round(($p/100)*(count($a)+1),2);
	if (floor($l)==$l) {
		return ($a[$l-1]);
	} else if ($l>count($a)) {
		return $a[floor($l)-1];
	} else {
		return (($a[floor($l)-1]+$a[ceil($l)-1])/2);
	}
}

// interppercentile(array, percentile, [mode])
// Interpolated percentile. Finds the percentile using an interpolated method.
// mode=1 (def): Matches Excel's PERCENTILE.EXC, JMP, and recommended by NIST
//   except that this function will return the lowest/highest value if needed.
// mode=2: Matches Excel's PERCENTILE.INC (and older percentile)
// mode=3: Matches Mathlab's prctile function
function interppercentile($a, $p, $mode=1) {
  if (!is_array($a)) {
		echo 'percentile expects an array';
		return false;
	}
	if ($p<0 || $p>100) {
		echo 'invalid percentage';
		return false;
	}
	sort($a, SORT_NUMERIC);
	if ($p==0) {
		return $a[0];
	} else if ($p==100) {
		return $a[count($a)-1];
	}
  $N = count($a);
  $p = $p/100;
  if ($mode == 3) { // c=1/2
    $x = $N*$p + 1/2;
  } else if ($mode == 2) { // c=1
    $x = $p*($N-1) + 1;
  } else { // c = 0
    $x = $p*($N+1);
  }
  if ($x < 1) {
    return $a[0];
  }
  $f = floor($x);
  if ($f >= $N) {
    return $a[$N-1];
  }
  return $a[$f-1] + ($x-$f)*($a[$f] - $a[$f-1]);
}

//quartile(array,quartile)
//finds the 0 (min), 1st, 2nd (median), 3rd, or 4th (max) quartile of an
//array of numbers.  Calculates using percentiles.
function quartile($a,$q) {
	if ($q<0 || $q>4) {
		echo 'invalid quartile number';
		return false;
	}
	return percentile($a,$q*25);
}

//TIquartile(array,quartile)
//finds the 0 (min), 1st, 2nd (median), 3rd, or 4th (max) quartile of an
//array of numbers.  Calculates using the TI-84 method.
function TIquartile($a,$q) {
	if (!is_array($a)) {
		echo 'percentile expects an array';
		return false;
	}
	if ($q<0 || $q>4) {
		echo 'invalid quartile number';
		return false;
	}
	sort($a, SORT_NUMERIC);
	$n = count($a);
	if ($q==0) {
		return $a[0];
	} else if ($q==4) {
		return $a[count($a)-1];
	}
	if ($q==2) {
		if ($n%2==0) { //even
			$m = $n/2;
			return (($a[$m-1] + $a[$m])/2);
		} else {
			return ($a[floor($n/2)]);
		}
	} else {
		if ($n%2==0) { //even
			if ($n%4==0) { //lower half is even
				$m = $n/4;
				if ($q==3) { $m = $n-$m;}
				return (($a[$m-1] + $a[$m])/2);
			} else {
				$m = floor($n/4);
				if ($q==3) { $m = $n-$m-1;}
				return ($a[$m]);
			}
		} else {
			if ((($n-1)/2)%2==0) {//lower half is even
				$m = floor($n/4);
				if ($q==3) { $m = $n-$m;}
				return (($a[$m-1] + $a[$m])/2);
			} else {
				$m = floor($n/4);
				if ($q==3) { $m = $n-$m-1;}
				return ($a[$m]);
			}
		}
	}
}

function Excelquartileexc($a,$q) {
  if (!is_array($a)) {
		echo 'excelquartileexc expects an array';
		return false;
	}
	if ($q<0 || $q>4) {
		echo 'invalid quartile number';
		return false;
	}
  sort($a, SORT_NUMERIC);
	if ($q==0) {
		return $a[0];
	} else if ($q==4) {
		return $a[count($a)-1];
	}
  return interppercentile($a, 25*$q);
}
//Excelquartile(array,quartile)
//finds the 0 (min), 1st, 2nd (median), 3rd, or 4th (max) quartile of an
//array of numbers.  Calculates using the Excel method (quartile.inc)
function Excelquartile($a,$q) {
	if (!is_array($a)) {
		echo 'excelquartile expects an array';
		return false;
	}
	if ($q<0 || $q>4) {
		echo 'invalid quartile number';
		return false;
	}
	sort($a, SORT_NUMERIC);
	$n = count($a);
	if ($q==0) {
		return $a[0];
	} else if ($q==4) {
		return $a[count($a)-1];
	}
	if ($q==2) {
		if ($n%2==0) { //even
			$m = $n/2;
			return (($a[$m-1] + $a[$m])/2);
		} else {
			return ($a[floor($n/2)]);
		}
	} else {
		$l = round((count($a)-1)*.25*$q ,3);
		if (floor($l)==$l) {
			return ($a[$l]);
		} else {
			$d = $l - floor($l);
			return ($d*$a[floor($l)+1] + (1-$d)*$a[floor($l)]);
		}
	}
}

//Nplus1quartile(array,quartile)
//finds the 0 (min), 1st, 2nd (median), 3rd, or 4th (max) quartile of an
//array of numbers.  Calculates using the N+1 method, which is like
//percentiles, but calculated using N+1 (OpenStax).
function Nplus1quartile($a,$q) {
	if ($q<0 || $q>4) {
		echo 'invalid quartile number';
		return false;
	}
	return Nplus1percentile($a,$q*25);
}

//allquartile(array,quartile)
//finds the 0 (min), 1st, 2nd (median), 3rd, or 4th (max) quartile of an
//array of numbers.  Uses all the quartile methods, and returns an "or" joined
//string of all unique answers.
function allquartile($a,$q) {
	if (!is_array($a)) {
		echo 'percentile expects an array';
		return false;
	}
	if ($q<0 || $q>4) {
		echo 'invalid quartile number';
		return false;
	}
	sort($a, SORT_NUMERIC);
	$n = count($a);
	if ($q==0) {
		return $a[0];
	} else if ($q==4) {
		return $a[count($a)-1];
	}
	if ($q==2) {
		if ($n%2==0) { //even
			$m = $n/2;
			return (($a[$m-1] + $a[$m])/2);
		} else {
			return ($a[floor($n/2)]);
		}
	}

	//%4==0, all same except Excel
	//%4==1, q and Excel same, TI and Nplus1 same
	//%4==2, q and TI same, Excel and Nplus1 diff
	//%4==3, all same except Excel
	$qs = array();
	if ($n%4==1) {
		$qs[] = percentile($a,$q*25);
		$qs[] = Nplus1percentile($a,$q*25);
	} else if ($n%4==2) {
		$qs[] = percentile($a,$q*25);
		$qs[] = Nplus1percentile($a,$q*25);
		$qs[] = Excelquartile($a,$q);
	} else {
		$qs[] = percentile($a,$q*25);
		$qs[] = Excelquartile($a,$q);
	}
  $qs[] = interppercentile($a, $q*25); //excel quartile.exc
	return implode(' or ',array_unique($qs));
}

//median(array)
//returns the median of an array of numbers
function median($a) {
	return percentile($a,50);
}

function modes($arr) {
  if (!is_array($arr)) {
    echo "mode expects an array";
    return 'DNE';
  }
  $arr = array_map('strval', $arr);
  $freqs = array_count_values($arr);
  $maxfreq = max($freqs);
  $modes = array_keys($freqs, $maxfreq);
  if (count($modes)==count($freqs)) {
    return 'DNE';
  } else {
    return implode(',', $modes);
  }
}

function forceonemode(&$arr) {
  $modes = modes($arr);
  if (is_numeric($modes)) {
    return $modes;
  }
  if ($modes == 'DNE') {
    $mode = $arr[0];
  } else {
    $mode = explode(',', $modes)[0];
  }
  // add a value after an existing one (in case sorted)
  array_splice($arr, array_search($mode,$arr), 0, $mode);
  foreach ($arr as $k=>$v) {
    if ($v != $mode) {
      array_splice($arr, $k, 1);
      break;
    }
  }
  return $mode;
}

//freqdist(array,label,start,classwidth)
//display macro.  Returns an HTML table that is a frequency distribution of
//the data
// array: array of data values
// label: name of data values
// start: first lower class limit
// classwidth: width of the classes
function freqdist($a,$label,$start,$cw) {
	if (!is_array($a)) {
		echo 'freqdist expects an array';
		return false;
	}
	if ($cw<0) { $cw *= -1;} else if ($cw==0) { echo "Error - classwidth cannot be 0"; return 0;}
	sort($a, SORT_NUMERIC);
	$x = $start;
	$curr = 0;
	$out = "<table class=stats><thead><tr><th>$label</th><th>Freq</th></tr></thead>\n<tbody>\n";
	while ($x <= $a[count($a)-1]) {
		$out .= "<tr><td>`$x <= x < ";
		$x += $cw;
		$out .= "$x`</td><td>";
		$i = $curr;
		while (($a[$i] < $x) && ($i < count($a))) {
			$i++;
		}
		$out .= ($i-$curr) . "</td></tr>\n";
		$curr = $i;
	}
	$out .= "</tbody></table>\n";
	return $out;
}

//frequency(array,start,classwidth)
//Returns an array of frequencies for the data grouped into classes
// array: array of data values
// start: first lower class limit
// classwidth: width of the classes
function frequency($a,$start,$cw,$end=null) {
	if (!is_array($a)) {
		echo 'frequency expects an array';
		return false;
	}
	if ($cw<0) { $cw *= -1;} else if ($cw==0) { echo "Error - classwidth cannot be 0"; return 0;}
	sort($a, SORT_NUMERIC);
	$x = $start;
	$curr = 0;
	while ($x <= ($end!==null ? $end : $a[count($a)-1]+1e-10)) {
		$x += $cw;
		$i = $curr;
		while (($a[$i] < $x) && ($i < count($a))) {
			$i++;
		}
		$out[] = ($i-$curr);
		$curr = $i;
	}
	return $out;
}

//countif(array,condition)
//Returns count of items in array that meet condition
// array: array of data values
// condition: a condition, using x for data values
//Example: countif($a,"x<3 && x>2")
function countif($a,$ifcond) {
	if (!is_array($a)) {
		echo 'countif expects an array';
		return false;
	}
	$rsnoquote = preg_replace('/"[^"]*"/','""',$ifcond);
	$rsnoquote = preg_replace('/\'[^\']*\'/','\'\'',$rsnoquote);
	if (preg_match_all('/([$\w]+)\s*\([^\)]*\)/',$rsnoquote,$funcs)) {
		$ismath = true;
		for ($i=0;$i<count($funcs[1]);$i++) {
			if (strpos($funcs[1][$i],"$")===false) {
				if (!in_array($funcs[1][$i],$allowedmacros)) {
					echo "{$funcs[1][$i]} is not an allowed function<BR>\n";
					return false;
				}
			}
		}
	}
	$ifcond = str_replace('!=','#=',$ifcond);
	$ifcond = mathphp($ifcond,'x');
	$ifcond = str_replace('#=','!=',$ifcond);
	$ifcond = str_replace('(x)','($x)',$ifcond);
	$iffunc = my_create_function('$x','return('.$ifcond.');');

	$cnt = 0;
	foreach ($a as $v) {
		if ($iffunc($v)) {
			$cnt++;
		}
	}
	return $cnt;
}

//histogram(array,label,start,classwidth,[labelstart,upper,width,height])
//display macro.  Creates a histogram from a data set
// array: array of data values
// label: name of data values
// start: first lower class limit
// classwidth: width of the classes
// labelstart (optional): value to start axis labeling at.  Defaults to start
// upper (optional): first upper class limit.  Defaults to start+classwidth
// width,height (optional): width and height in pixels of graph
function histogram($a,$label,$start,$cw,$startlabel=false,$upper=false,$width=300,$height=200) {
	if (!is_array($a)) {
		echo 'histogram expects an array';
		return false;
	}
	if ($cw<0) { $cw *= -1;} else if ($cw==0) { echo "Error - classwidth cannot be 0"; return 0;}
	sort($a, SORT_NUMERIC);
	$x = $start;
	$curr = 0;
	$alt = "Histogram for $label <table class=stats><thead><tr><th>Label on left of box</th><th>Frequency</th></tr></thead>\n<tbody>\n";
	$maxfreq = 0;
	if ($upper===false) {
		$dx = $cw;
		$dxdiff = 0;
	} else {
		$dx = $upper - $start;
		$dxdiff = $cw-$dx;
	}

	while ($x <= $a[count($a)-1]) {
		$alt .= "<tr><td>$x</td>";
		$st .= "rect([$x,0],";
		$x += $dx;
		$st .= "[$x,";
		$i = $curr;
		while (($a[$i] < $x) && ($i < count($a))) {
			$i++;
		}
		if (($i-$curr)>$maxfreq) { $maxfreq = $i-$curr;}
		$alt .= "<td>" . ($i-$curr) . "</td></tr>\n";
		$st .= ($i-$curr) . "]);";
		$curr = $i;
		$x += $dxdiff;
	}
	$alt .= "</tbody></table>\n";
	if ($_SESSION['graphdisp']==0) {
		return $alt;
	}

	$outst = "setBorder(".(40+7*strlen($maxfreq)).",40,20,15);  initPicture(".($start>0?(max($start-.9*$cw,0)):$start).",$x,0,$maxfreq);";

	$power = floor(log10($maxfreq))-1;
	$base = $maxfreq/pow(10,$power);
	if ($base>75) {$step = 20*pow(10,$power);} else if ($base>40) { $step = 10*pow(10,$power);} else if ($base>20) {$step = 5*pow(10,$power);} else if ($base>9) {$step = 2*pow(10,$power);} else {$step = pow(10,$power);}

	//if ($maxfreq>100) {$step = 20;} else if ($maxfreq > 50) { $step = 10; } else if ($maxfreq > 20) { $step = 5;} else if ($maxfreq>9) { $step = 2; } else {$step=1;}
	if ($startlabel===false) {
		//$outst .= "axes($cw,$step,1,1000,$step); fill=\"blue\"; textabs([". ($width/2+15)  .",0],\"$label\",\"above\");";
		$startlabel = $start;
	} //else {
    $maxx = 2*max($a);
		$outst .= "axes($maxx,$step,1,null,$step); fill=\"blue\"; textabs([". ($width/2+15)  .",0],\"$label\",\"above\");";
		$x = $startlabel;
		$tm = -.02*$maxfreq;
		$tx = .02*$maxfreq;
		while ($x <= $a[count($a)-1]+$cw) {
			$outst .= "line([$x,$tm],[$x,$tx]); text([$x,0],\"$x\",\"below\");";
			$x+= $cw;
		}
	//}
	$outst .= "textabs([0,". ($height/2+15)  ."],\"Frequency\",\"right\",90);";
	$outst .= $st;
	return showasciisvg($outst,$width,$height);
}

//fdhistogram(freqarray,label,start,cw,[labelstart,upper,width,height])
//display macro.  Creates a histogram from frequency array
// freqarray: array of frequencies
// label: name of data values
// start: first lower class limit
// classwidth: width of the classes
// labelstart (optional): value to start axis labeling at.  Defaults to start
// upper (optional): first upper class limit.  Defaults to start+classwidth
// width,height (optional): width and height in pixels of graph
function fdhistogram($freq,$label,$start,$cw,$startlabel=false,$upper=false,$width=300,$height=200) {
	if (!is_array($freq)) {echo "freqarray must be an array"; return 0;}
	if ($cw<0) { $cw *= -1;} else if ($cw==0) { echo "Error - classwidth cannot be 0"; return 0;}
	$x = $start;
	$alt = "Histogram for $label <table class=stats><thead><tr><th>Label on left of box</th><th>Frequency</th></tr></thead>\n<tbody>\n";
	$maxfreq = 0;
	if ($upper===false) {
		$dx = $cw;
		$dxdiff = 0;
	} else {
		$dx = $upper - $start;
		$dxdiff = $cw-$dx;
	}
	for ($curr=0; $curr<count($freq); $curr++) {
		$alt .= "<tr><td>$x</td><td>{$freq[$curr]}</td></tr>";
		$st .= "rect([$x,0],";
		$x += $dx;
		$st .= "[$x,{$freq[$curr]}]);";
		if ($freq[$curr]>$maxfreq) { $maxfreq = $freq[$curr];}
		$x += $dxdiff;
	}
	$alt .= "</tbody></table>\n";
	if ($_SESSION['graphdisp']==0) {
		return $alt;
	}
	$outst = "setBorder(".(40+7*strlen($maxfreq)).",40,20,5);  initPicture(".($start>0?(max($start-.9*$cw,0)):$start).",$x,0,$maxfreq);";
	//$outst = "setBorder(10);  initPicture(". ($start-.1*($x-$start)) .",$x,". (-.1*$maxfreq) .",$maxfreq);";
	$power = floor(log10($maxfreq))-1;
	$base = $maxfreq/pow(10,$power);
	if ($base>75) {$step = 20*pow(10,$power);} else if ($base>40) { $step = 10*pow(10,$power);} else if ($base>20) {$step = 5*pow(10,$power);} else if ($base>9) {$step = 2*pow(10,$power);} else {$step = pow(10,$power);}
	//if ($maxfreq>100) {$step = 20;} else if ($maxfreq > 50) { $step = 10; } else if ($maxfreq > 20) { $step = 5;} else if ($maxfreq>9) {$step = 2;} else {$step=1;}
	if ($startlabel===false) {
		//$outst .= "axes($cw,$step,1,1000,$step); fill=\"blue\"; textabs([". ($width/2+15)  .",0],\"$label\",\"above\");";
		$startlabel = $start;
	} //else {
		$outst .= "axes(1000,$step,1,1000,$step); fill=\"blue\"; textabs([". ($width/2+15)  .",0],\"$label\",\"above\");";
		$x = $startlabel;
		$tm = -.02*$maxfreq;
		$tx = .02*$maxfreq;
		for ($curr=0; $curr<count($freq)+1; $curr++) {
			$outst .= "line([$x,$tm],[$x,$tx]); text([$x,0],\"$x\",\"below\");";
			$x+=$cw;
		}
	//}
	//$outst .= "text([".($start-.1*($x-$start)).",".(.5*$maxfreq)."],\"Freq\",,90)";
	//$outst .= "axes($cw,$step,1,1000,$step); fill=\"blue\"; text([". ($start + .5*($x-$start))  .",". (-.1*$maxfreq) . "],\"$label\");";
	$outst .= "textabs([0,". ($height/2+15)  ."],\"Frequency\",\"right\",90);";
	$outst .= $st;
	return showasciisvg($outst,$width,$height);
}

//fdbargraph(barlabels,freqarray,label,[width,height,options])
//barlabels: array of labels for the bars
//freqarray: array of frequencies/heights for the bars
//label: general label for bars
//width,height (optional): width and height for graph
//options (optional): array of options:
//  options['valuelabels'] = array of value labels, to be placed above bars
//  options['showgrid'] = false to hide the horizontal grid lines
//  options['vertlabel'] = label for vertical axis. Defaults to none
//  options['gap'] = gap (0 &le; gap &lt; 1) between bars
//  options['toplabel'] = label for top of chart
function fdbargraph($bl,$freq,$label,$width=300,$height=200,$options=array()) {
	if (!is_array($bl) || !is_array($freq)) {echo "barlabels and freqarray must be arrays"; return 0;}
	if (count($bl) != count($freq)) { echo "barlabels and freqarray must have same length"; return 0;}

	if (isset($options['valuelabels'])) {
		$valuelabels = $options['valuelabels'];
	} else {
		$valuelabels = false;
	}
	if (isset($options['vertlabel'])) {
		$vertlabel = $options['vertlabel'];
		$usevertlabel = true;
	} else {
		$vertlabel = 'Bar Height';
		$usevertlabel = false;
	}
	if (isset($options['gap'])) {
		$gap = $options['gap'];
	} else {
		$gap = 0;
	}

	$alt = "Bar graph for $label <table class=stats><thead><tr><th>Bar Label</th><th>$vertlabel</th></tr></thead>\n<tbody>\n";
	$start = 0;
	$x = $start+1;
	$maxfreq = 0;
	for ($curr=0; $curr<count($bl); $curr++) {
		$alt .= "<tr><td>{$bl[$curr]}</td><td>{$freq[$curr]}</td></tr>";
		$st .= "rect([$x,0],";
		$x += 2;
		$st .= "[$x,{$freq[$curr]}]);";
		$x -= 1;
		$st .= "text([$x,0],\"{$bl[$curr]}\",\"below\");";
		if ($valuelabels!==false) {
			if (is_array($valuelabels)) {
				$st .= "text([$x,{$freq[$curr]}],\"{$valuelabels[$curr]}\",\"above\");";
			} else {
				$st .= "text([$x,{$freq[$curr]}],\"{$freq[$curr]}\",\"above\");";
			}
		}
		$x += 1 + 2*$gap;
		if ($freq[$curr]>$maxfreq) { $maxfreq = $freq[$curr];}
	}
	$x -= 2*$gap;
	$alt .= "</tbody></table>\n";
	if ($_SESSION['graphdisp']==0) {
		return $alt;
	}
	$x++;

	$power = floor(log10($maxfreq))-1;
	$base = $maxfreq/pow(10,$power);

	if ($base>75) {$step = 20*pow(10,$power);} else if ($base>40) { $step = 10*pow(10,$power);} else if ($base>20) {$step = 5*pow(10,$power);} else if ($base>9) {$step = 2*pow(10,$power);} else {$step = pow(10,$power);}

	$topborder = ($valuelabels===false?10:25) + (isset($options['toplabel'])?20:0);
	$leftborder = min(60, 9*max(strlen($maxfreq),strlen($maxfreq-$step))+10) + ($usevertlabel?30:0);
	//$outst = "setBorder(10);  initPicture(". ($start-.1*($x-$start)) .",$x,". (-.1*$maxfreq) .",$maxfreq);";
	$bottomborder = 25+($label===''?0:20);
	$outst = "setBorder($leftborder,$bottomborder,0,$topborder);  initPicture(".($start>0?(max($start-.9*$cw,0)):$start).",$x,0,$maxfreq);";

	if (isset($options['showgrid']) && $options['showgrid']==false) {
		$gdy = 0;
	} else {
		$gdy = $step;
	}
	//if ($maxfreq>100) {$step = 20;} else if ($maxfreq > 50) { $step = 10; } else if ($maxfreq > 20) { $step = 5;} else {$step=1;}
	$outst .= "axes(1000,$step,1,1000,$gdy); fill=\"blue\"; ";
	if ($label!=='') {
		$outst .= "textabs([". ($width/2+15)  .",0],\"$label\",\"above\");";
	}
	if ($usevertlabel) {
		$outst .= "textabs([0,". ($height/2+20) . "],\"$vertlabel\",\"right\",90);";
	}
	if (isset($options['toplabel'])) {
		$outst .= "textabs([". ($width/2+15)  .",$height],\"{$options['toplabel']}\",\"below\");";
	}

	//$outst .= "axes($cw,$step,1,1000,$step); fill=\"blue\"; text([". ($start + .5*($x-$start))  .",". (-.1*$maxfreq) . "],\"$label\");";
	$outst .= $st;
	return showasciisvg($outst,$width,$height);
}

//piechart(percents, labels, {width, height, data label})
//create a piechart
//percents: array of pie percents (should total 100%)
//labels: array of labels for each pie piece
//uses Google Charts API
function piechart($pcts,$labels,$w=250,$h=130) {
	if ($_SESSION['graphdisp']==0) {
		$out .= '<table><caption>'._('Pie Chart').'</caption>';
		$out .= '<tr><th>'._('Label').'</th>';
		$out .= '<th>'._('Percent').'</th></tr>';
		foreach ($labels as $k=>$label) {
			$out .= '<tr><td>'.Sanitize::encodeStringForDisplay($label).'<td>';
			$out .= '<td>'.Sanitize::encodeStringForDisplay($pcts[$k]).'%</td></tr>';
		}
		$out .= '</table>';
		return $out;
	}

  $rows = array();
	foreach ($labels as $k=>$label) {
		$rows[] = array($label, $pcts[$k]);
	}

  $cw = $w - 20;
  $ch = $h - 20;

	$out = '<div class="piechart" data-pie="'.Sanitize::encodeStringForDisplay(json_encode($rows)).'" style="display:inline-block;width:'.Sanitize::onlyInt($w).'px;height:'.Sanitize::onlyInt($h).'px"></div>';
	//load google charts loader if needed
	$out .= '<script type="text/javascript">
		if (typeof window.chartqueue == "undefined") {
			window.chartqueue = [];
			jQuery.getScript("https://www.gstatic.com/charts/loader.js")
			.done(function() {
				google.charts.load("current", {packages: ["corechart"]});
				for (i in window.chartqueue) {
					google.charts.setOnLoadCallback(window.chartqueue[i]);
				}
			});
		};
  initstack.push(function() {
    if (typeof window.piechartcount == "undefined") {
      window.piechartcount = 0;
    }
    $(".piechart").each(function(i,el) {
      var uniqid = "pie" + window.piechartcount;
      window.piechartcount++;
      $(el).attr("id", uniqid).removeClass("piechart");

      var render = function () {
        var data = new google.visualization.DataTable();
        data.addColumn("string", "Data");
        data.addColumn("number", "Percentage");
        data.addRows(JSON.parse(el.getAttribute("data-pie")));
        var chart = new google.visualization.PieChart(document.getElementById(uniqid));
        chart.draw(data, {width:'.$w.', height:'.$h.', sliceVisibilityThreshold: 0, tooltip: {text: "percentage"}, legend:{position:"labeled"}, chartArea:{left:10, top:10, width:'.$cw.', height:'.$ch.'}});
      }
      if (typeof google == "undefined" || typeof google.charts == "undefined") {
    			window.chartqueue.push(render);
    		} else {
    			google.charts.load("current", {packages: ["corechart"]});
    			google.charts.setOnLoadCallback(render);
    		}
    });
  })</script>';

	/*
	$out = "<img src=\"https://chart.apis.google.com/chart?cht=p&amp;chd=t:";
	$out .= implode(',',$pcts);
	$out .= "&amp;chs={$w}x{$h}&amp;chl=";
	$out .= implode('|',$labels);
	$out .= '" alt="Pie Chart" />';
	*/
	return $out;
}

//normrand(mu,sigma,n, [rnd])
//returns an array of n random numbers that are normally distributed with given
//mean mu and standard deviation sigma.  Uses the Box-Muller transform.
//specify rnd to round to that many digits
function normrand($mu,$sig,$n,$rnd=null,$pos=false) {
	if (!is_finite($mu) || !is_finite($sig) || !is_finite($n) || $n < 0 || $n > 5000 || $sig < 0) {
		echo 'invalid inputs to normrand';
		return array();
	}
    global $RND;
    $icnt = 0;
    $z = [];
    while (count($z)<$n && $icnt < 2*$n) {
		do {
			$a = $RND->rand(-32768,32768)/32768;
			$b = $RND->rand(-32768,32768)/32768;
			$r = $a*$a+$b*$b;
			$count++;
		} while ($r==0||$r>1);
        $r = sqrt(-2*log($r)/$r);
        $v1 = $sig*$a*$r + $mu;
        $v2 = $sig*$b*$r + $mu;
        if (!$pos || $v1 > 0) {
            $z[] = ($rnd===null) ? $v1 : round($v1, $rnd);
        }
        if (!$pos || $v2 > 0) {
            $z[] = ($rnd===null) ? $v2 : round($v2, $rnd);
        }
        $icnt++;
    }
    if ($icnt == 2*$n && count($z) < $n) {
        echo "Error: unable to generate enough positive values";
    }
	if (count($z)==$n) {
		return $z;
	} else {
		return (array_slice($z,0,$n));
	}
}

function expdistrand($mu=1, $n=1, $rnd=3) {
	if (!is_finite($mu) || !is_finite($n) || $n < 0 || $n > 5000) {
		echo 'invalid inputs to expdistrand';
		return array();
	}
	global $RND;

	$out = array();
	for ($i=0; $i<$n; $i++) {
		$out[] = round(-$mu*log($RND->rand(1,32768)/32768), $rnd);
	}
	return $out;
}

//boxplot(array,axislabel,[options])
//draws a boxplot based on the data in array, with given axislabel
//and optionally a datalabel (to topleft of boxplot)
//array also be an array of dataarrays to do comparative boxplots
//opts is an array of options:
//   "datalabels" = array of data labels for comparative boxplots
//   "showvals" = true to show 5 number summary above boxplot
//   "showoutliers" = true to put whiskers at 1.5IQR and show outliers
//   "qmethod" = quartile method: "N", "TI", "Excel" or "Nplus1"
//       N: percentile method, using .25*n
//       Nplus1: percentile method, using .25*(n+1)
//       TI: TI calculator method, a mix of n and nplus1 methods
//       Excel: A method based on (n-1), with some linear interpolation
//For backwards compatability, options can also just be an array of datalabels
function boxplot($arr,$label="",$options = array()) {
	if (is_array($arr[0]) && count($options)==count($arr) && isset($options[0])) {
		$dlbls = $options;
		$options = array();
	} else if (isset($options['datalabels'])) {
		$dlbls = $options['datalabels'];
	}
	if (is_array($arr[0])) { $multi = count($arr);} else {$multi = 1;}
	$qmethod = 'quartile';
	if (isset($options['qmethod'])) {
		if ($options['qmethod']=='TI') {
			$qmethod = 'TIquartile';
		} else if ($options['qmethod']=='Excel') {
			$qmethod = 'Excelquartile';
		} else if ($options['qmethod']=='Excel.exc') {
			$qmethod = 'Excelquartileexc';
		} else if ($options['qmethod']=='N') {
			$qmethod = 'quartile';
		} else if ($options['qmethod']=='Nplus1') {
			$qmethod = 'Nplus1quartile';
		}
	}

	$st = '';
	$bigmax = -10000000;
	$bigmin = 100000000;
	$alt = "Boxplot, axis label: $label. ";
	$ybase = 2;
	for ($i=0;$i<$multi;$i++) {
		if ($multi==1) { $a = $arr;} else {$a = $arr[$i];}
		sort($a,SORT_NUMERIC);
		$min = $a[0]*1;
		$max = $a[count($a)-1]*1;
		if ($max>$bigmax) { $bigmax = $max;}
		if ($a[0]<$bigmin) {$bigmin = $a[0];}
		$q1 = $qmethod($a,1)*1;
		$q2 = $qmethod($a,2)*1;
		$q3 = $qmethod($a,3)*1;
		$outliers = array();
		if (count($a)>5 && isset($options['showoutliers'])) {
			$iqr = $q3-$q1;
			$lfence = $q1 - 1.5*$iqr;
			$rfence = $q3 + 1.5*$iqr;
      $mininside = 1e50;
      $maxinside = -1e50;
			foreach ($a as $v) {
				if ($v<$lfence || $v>$rfence) {
					$outliers[] = $v*1;
				} else if ($v < $mininside) {
          $mininside = $v*1;
        } else if ($v > $maxinside) {
          $maxinside = $v*1;
        }
			}
			if (count($outliers)>0) {
				if ($lfence>$min) {
					$min = $mininside;
				}
				if ($rfence<$max) {
					$max = $maxinside;
				}
			}
		}
		$yl = $ybase;
		$ym = $yl+1;
		$yh = $ym+1;
		$st .= "line([$min,$yl],[$min,$yh]); rect([$q1,$yl],[$q3,$yh]); line([$q2,$yl],[$q2,$yh]);";
		$st .= "line([$max,$yl],[$max,$yh]); line([$min,$ym],[$q1,$ym]); line([$q3,$ym],[$max,$ym]);";
		if (isset($options['showvals'])) {
			$st .= "fontsize*=.8;fontfill='blue';text([$min,$yh],'$min','above');";
			$st .= "text([$q1,$yh],'$q1','above');";
			$st .= "text([$q2,$yh],'$q2','above');";
			$st .= "text([$q3,$yh],'$q3','above');";
			$st .= "text([$max,$yh],'$max','above');fontfill='black';fontsize*=1.25;";
			$ybase += 2;
		}
		$alt .= 'Boxplot ';
		if (isset($dlbls[$i])) {
			$alt .= "for {$dlbls[$i]}";
			$ybase += 2;
		} else {
			$alt .= ($i+1);
		}
		$ybase += 3;
		$alt .= ": Left whisker at {$a[0]}. Left side of box at $q1. Line through box at $q2.  Right side of box at $q3. Right whisker at $max.\n";
		if (count($outliers)>0) {
			foreach ($outliers as $v) {
				$st .= "dot([$v,$ym],'open');";
				$alt .= "Dot at $v. ";
			}
		}
	}
	$ycnt = $ybase-1;
	if ($_SESSION['graphdisp']==0) {
		return $alt;
	}
	$dw = $bigmax-$bigmin;

	if ($dw>100) {$step = 20;} else if ($dw > 50) { $step = 10; } else if ($dw > 20) { $step = 5;} else {$step=1;}
	$bigmin = floor($bigmin/$step)*$step;
	$bigmax = ceil($bigmax/$step)*$step;

	$outst = "setBorder(15); initPicture($bigmin,$bigmax,-3,".($ycnt).");";
	$outst .= "axes($step,100,1,null,null,1,'off');";
	$outst .= "text([". ($bigmin+.5*$dw) . ",-3],\"$label\");";
	if (isset($dlbls)) {
		$ybase = 0;
		for ($i=0;$i<$multi;$i++) {
			$ybase += isset($options['showvals'])?7:5;
			if ($multi>1) { $dlbl = $dlbls[$i];} else {$dlbl = $dlbls;}
			$st .= "text([$bigmin,". $ybase ."],\"$dlbl\",\"right\");";
		}
	}
	$outst .= $st;
	$width = isset($options['width'])?$options['width']:400;
	return showasciisvg($outst,$width*1,50+12*$ycnt);
}

//normalcdf(z,[dec])
//calculates the area under the standard normal distribution to the left of the
//z-value z, to dec decimals (defaults to 4, max of 10)
//based on someone else's code - can't remember whose!
/*function normalcdf($ztest,$dec=4) {
	if (!is_finite($ztest)) {
		echo 'invalid value for z';
		return 0;
	}
	if ($dec>10) { $dec = 10;}

	$eps = pow(.1,$dec);
	$eps2 = pow(.1,$dec+3);

	$ds = 1;
	$s = 0;
	$i = 0;
	$z = abs($ztest);
	if ($z>5) { //alternate code, less accuracy; around 10^-8 vs 10^-10 w above
		$b1 =  0.319381530;
		$b2 = -0.356563782;
		$b3 =  1.781477937;
		$b4 = -1.821255978;
		$b5 =  1.330274429;
		$p  =  0.2316419;
		$c  =  0.39894228;

		$x = $ztest;
		if($x >= 0.0) {
		     $t = 1.0 / ( 1.0 + $p * $x );
		      return round((1.0 - $c * exp( -$x * $x / 2.0 ) * $t *
		      ( $t *( $t * ( $t * ( $t * $b5 + $b4 ) + $b3 ) + $b2 ) + $b1 )), $dec);
		} else {
		      $t = 1.0 / ( 1.0 - $p * $x );
		      return round(( $c * exp( -$x * $x / 2.0 ) * $t *
		       ( $t *( $t * ( $t * ( $t * $b5 + $b4 ) + $b3 ) + $b2 ) + $b1 )), $dec);
		}
	}
	$fact = 1;
	while (abs($ds)>$eps2 && $i<50) {
		$ds = pow(-1,$i)*pow($z,2.0*$i+1.0)/(pow(2.0,$i)*$fact*(2.0*$i+1.0));
		$s += $ds;
		$i++;
		$fact *= $i;
		if (abs($s)<$eps && $i>2 && abs($ds)<1) {
			break;
		}
	}

	$s *= 0.3989422804014327;
	$s = round($s,$dec);
	if ($ztest > 0) {
		$pval = .5 + $s;
	} else {
		$pval = .5 - $s;
	}
	if ($pval < $eps) {
		$pval = $eps;
	} else if ($pval > (1-$eps)) {
		$pval = round(1-$eps,$dec);
	}
	return $pval;
}
*/
// port from jStat, MIT License
function erf($x) {
  $cof = [-1.3026537197817094, 6.4196979235649026e-1, 1.9476473204185836e-2,
             -9.561514786808631e-3, -9.46595344482036e-4, 3.66839497852761e-4,
             4.2523324806907e-5, -2.0278578112534e-5, -1.624290004647e-6,
             1.303655835580e-6, 1.5626441722e-8, -8.5238095915e-8,
             6.529054439e-9, 5.059343495e-9, -9.91364156e-10,
             -2.27365122e-10, 9.6467911e-11, 2.394038e-12,
             -6.886027e-12, 8.94487e-13, 3.13092e-13,
             -1.12708e-13, 3.81e-16, 7.106e-15,
             -1.523e-15, -9.4e-17, 1.21e-16,
             -2.8e-17];
  $isneg = false;
  $d = 0;
  $dd = 0;

  if ($x < 0) {
    $x = -$x;
    $isneg = true;
  }

  $t = 2 / (2 + $x);
  $ty = 4 * $t - 2;

  for($j = count($cof) - 1; $j > 0; $j--) {
    $tmp = $d;
    $d = $ty * $d - $dd + $cof[$j];
    $dd = $tmp;
  }

  $res = $t * exp(-$x * $x + 0.5 * ($cof[0] + $ty * $d) - $dd);
  return $isneg ? $res - 1 : 1 - $res;
}
function normalcdf($z,$dec=4) {
  if (!is_finite($ztest)) {
		echo 'invalid value for z';
		return 0;
	}
  return round(0.5 * (1 + erf(($z) / sqrt(2))), $dec);
}

//tcdf(t,df,[dec])
//calculates the area under the t-distribution with "df" degrees of freedom
//to the left of the t-value t
//based on code from www.math.ucla.edu/~tom/distributions/tDist.html
function tcdf($X, $df, $dec=4) {
	if (!is_finite($X) || !is_finite($df)) {
		echo 'invalid inputs to tcdf';
		return 0;
	}
	if ($df<=0) {
		echo "Degrees of freedom must be positive";
		return false;
	} else {
		$A=$df/2;
		$S=$A+.5;
		$Z=$df/($df+$X*$X);
		$BT=exp(gamma_log($S)-gamma_log(.5)-gamma_log($A)+$A*log($Z)+.5*log(1-$Z));
		if ($Z<($A+1)/($S+2)) {
			$betacdf=$BT*Betinc($Z,$A,.5,$dec);
		} else {
			$betacdf=1-$BT*Betinc(1-$Z,.5,$A,$dec);
		}
		if ($X<0) {
			$tcdf=$betacdf/2;
		} else {
			$tcdf=1-$betacdf/2;
		}
	}
	return round($tcdf,$dec);
}

function Betinc($X,$A,$B, $dec) {
	$A0=0;
	$B0=1;
	$A1=1;
	$B1=1;
	$M9=0;
	$A2=0;
	//an epsilon of .00001 seems to give tcdf accurate to 9 decimal places
	$eps = pow(10, -1*$dec);
	while (abs(($A1-$A2)/$A1)>$eps) {
		$A2=$A1;
		$C9=-($A+$M9)*($A+$B+$M9)*$X/($A+2*$M9)/($A+2*$M9+1);
		$A0=$A1+$C9*$A0;
		$B0=$B1+$C9*$B0;
		$M9=$M9+1;
		$C9=$M9*($B-$M9)*$X/($A+2*$M9-1)/($A+2*$M9);
		$A1=$A0+$C9*$A1;
		$B1=$B0+$C9*$B1;
		$A0=$A0/$B1;
		$B0=$B0/$B1;
		$A1=$A1/$B1;
		$B1=1;
	}
	return $A1/$A;
}

/*
Older code, couldn't handle fractional degrees of freedom
function tcdf($ttest,$df,$dec=4) {
	$eps = pow(.1,$dec);

	$t = abs($ttest);
	if ($df > 0) {
	$k3 = 0;
	$c3 = 0;
	$a3 = $t/sqrt($df);
	$b3 = 1+pow($a3,2);
	$y = 0.5;
	if (abs(floor($df/2)*2 - $df) < $eps) {
		$c3 = $a3/(2*pow($b3,0.5));
		$k3 = 0;
	} else {
		$c3 = $a3/($b3*M_PI);
		$y += atan($a3)/M_PI;
		$k3 = 1;
	}
	if ($df > 1) {
		$k3 += 2;
		$y += $c3;
		$c3 *= ($k3-1)/($k3*$b3);
		while ($k3 < $df) {
			$k3 += 2;
			$y += $c3;
			$c3 *= ($k3-1)/($k3*$b3);
		}
	}
	if ($ttest > 0) {
		$pval = round($y,$dec);
	} else {
		$pval = round(1-$y,$dec);
	}


	if ($pval > (1-$eps)) {
		$pval = round(1-$eps,$dec);
	} else if ($pval < $eps) {
		$pval = $eps;
	}
	return $pval;
	} else {return false;}
}
*/

//invnormalcdf(p,[dec])
//Inverse Normal CDF
//finds the z-value with a left-tail area of p, to dec decimals (default 5)
// from Odeh & Evans. 1974. AS 70. Applied Statistics. 23: 96-97
function invnormalcdf($p,$dec=5) {
   if (!is_finite($p) || $p<0 || $p>1) {
	echo 'invalid inputs to invnormalcdf';
	return 0;
   }
      $p0 = -0.322232431088;
      $p1 = -1.0;
      $p2 = -0.342242088547;
      $p3 = -0.0204231210245;
      $p4 = -0.453642210148e-4;
      $q0 =  0.0993484626060;
      $q1 =  0.588581570495;
      $q2 =  0.531103462366;
      $q3 =  0.103537752850;
      $q4 =  0.38560700634e-2;

   if ($p < 0.5) { $pp = $p; }  else  {$pp = 1 - $p; }


   if ($pp < 1E-12) {
      $xp = 99;
   } else {
      $y = sqrt(log(1/($pp*$pp)));
      $xp = $y + (((($y * $p4 + $p3) * $y + $p2) * $y + $p1) * $y + $p0) / (((($y * $q4 + $q3) * $y + $q2) * $y + $q1) * $y + $q0);
   }
   $xp = round($xp,$dec);
   if ($p < 0.5) { return (-1*$xp); }  else { return  $xp; }
}

//invtcdf(p,df,[dec])
//the inverse Student's t-distribution
//computes the t-value with a left-tail probability of p, with df degrees of freedom
//to dec decimal places (default 4)
// from Algorithm 396: Student's t-quantiles by G.W. Hill  Comm. A.C.M., vol.13(10), 619-620, October 1970
function invtcdf($p,$ndf,$dec=4) {
	if (!is_finite($p) || !is_finite($ndf) || $p<0 || $p>1 || $ndf < 0) {
		echo 'invalid inputs to invtcdf';
		return 0;
	}
	$half_pi = M_PI/2;
	$eps = 1e-12;
	$origp = $p;
	if ($p > 0.5)  {$p = 1 - $p; }
	$p = $p*2;

	if ($ndf < 1 || $p > 1 || $p <= 0) {
	  echo "error in params";
	  return false;
	}

	if ( abs($ndf - 2) < $eps ) {  //special case ndf=2
	  $fn_val = round(SQRT(2 / ( $p * (2 - $p) ) - 2),$dec);
	  if ($origp<.5) {return (-1*$fn_val);} else { return $fn_val;}
	} else if (abs($ndf-4) < $eps) {  //special case ndf=4
	  $v = 4/sqrt($p*(2-$p))*cos(1/3*acos(sqrt($p*(2-$p))));
	  $fn_val = round(sqrt($v-4),$dec);
	  if ($origp<.5) {return (-1*$fn_val);} else { return $fn_val;}
	} else if ($ndf < 1+$eps) { //special case ndf=1
	  $prob = $p * $half_pi;
	  $fn_val = round(cos($prob) / sin($prob),$dec);
	  if ($origp<.5) {return (-1*$fn_val);} else { return $fn_val;}
	} else {
	  $a = 1/ ($ndf - 0.5);
	  $b = 48/ pow($a,2);
	  $c = ((20700 * $a / $b - 98) * $a - 16) * $a + 96.36;
	  $d = ((94.5 / ($b + $c) - 3) / $b + 1) * sqrt($a * $half_pi)* $ndf;
	  $x = $d * $p;
	  $y = pow($x,(2/ $ndf));

	  if ($y > 0.05 + $a) {
	    $x = invnormalcdf($origp,$dec+10);
	    $y = pow($x,2);
	    if ($ndf < 5) { $c = $c + 0.3 * ($ndf - 4.5) * ($x + 0.6);}
	    $c = (((0.05 * $d * $x - 5) * $x - 7) * $x - 2) * $x + $b+ $c;

	    $y = (((((0.4*$y + 6.3) * $y + 36) * $y + 94.5) / $c - $y - 3) / $b + 1) * $x;
	    $y = $a * pow($y,2);
	    if ($y > 0.002) {
	      $y = exp($y) - 1;
	    } else {
	      $y = 0.5 * pow($y,2) + $y;
	    }
	  } else {

	    $y = ((1 / ((($ndf + 6) / ($ndf * $y) - 0.089 * $d - 0.822) * ($ndf + 2) * 3) + 0.5 / ($ndf + 4)) * $y - 1) * ($ndf + 1) / ($ndf + 2) + 1 / $y;
	  }
	}
	if ($dec>3) {
		$fn_val = invtrefine(sqrt($ndf*$y),1-$p/2,$ndf,$dec);
		//echo "orig: ".sqrt($ndf*$y).", refined: $fn_val <br/>";
	} else {
		$fn_val = round( sqrt($ndf * $y) , $dec);
	}
	if ($origp<.5) {return (-1*$fn_val);} else { return $fn_val;}
}

function invtrefine($t,$p,$ndf,$dec) {
	$dv = .001;
	$eps = pow(.1,$dec+1);
	while ($dv>$eps) {
		$dv = $dv/2;
		if (tcdf($t,$ndf,$dec+10)>$p) {
			$t = $t-$dv;
		} else {
			$t = $t+$dv;
		}
	}
	return round($t,$dec);
}

//linreg(xarray,yarray)
//Computes the linear correlation coefficient, and slope and intercept of
//regression line, based on array/list of x-values and array/list of y-values
//Returns as array:  r,slope,intercept
function linreg($xarr,$yarr) {
	if (!is_array($xarr)) { $xarr = explode(',',$xarr);}
	if (!is_array($yarr)) { $yarr = explode(',',$yarr);}
	if (count($xarr)!=count($yarr)) {
		echo "Error: linreg requires xarray length = yarray length";
		return false;
	}
	$sx = array_sum($xarr);
	$sy = array_sum($yarr);
	$sxx=0; $syy=0; $sxy = 0;
	for ($i=0; $i<count($xarr); $i++) {
		$sxx += $xarr[$i]*$xarr[$i];
		$syy += $yarr[$i]*$yarr[$i];
		$sxy += $xarr[$i]*$yarr[$i];
	}
	$n = count($xarr);
    $rd = (sqrt($n*$sxx-$sx*$sx)*sqrt($n*$syy-$sy*$sy));
    if ($rd == 0) {
        $r = 1; // perfect horizontal data
    } else {
	    $r = ($n*$sxy - $sx*$sy)/$rd;
    }
	$m = ($n*$sxy - $sx*$sy)/($n*$sxx - $sx*$sx);
	$b = ($sy - $sx*$m)/$n;
	return array($r,$m,$b);
}

//expreg(xarray,yarray)
//Computes the exponential correlation coefficient, and base and intercept of
//regression exponential, based on array/list of x-values and array/list of y-values
//Returns as array:  r,base,intercept
function expreg($xarr,$yarr) {
	if (!is_array($xarr)) { $xarr = explode(',',$xarr);}
	if (!is_array($yarr)) { $yarr = explode(',',$yarr);}
	if (count($xarr)!=count($yarr)) {
		echo "Error: expreg requires xarray length = yarray length";
		return false;
	}
	foreach($yarr as $k=>$y) {
		if ($y==0) { echo "Error: expreg cannot handle y-values of 0"; return false;}
		$yarr[$k] = log($y);
	}
	list($r,$m,$b) = linreg($xarr,$yarr);
	//log(y) = mx+b  y = e^b * (e^m)^x
	return array($r, exp($m), exp($b));
}


//checklineagainstdata(xarray, yarray, student answer, [variable, alpha])
//intended for checking a student answer for fitting a line to data.  Determines
//if the student answer is within the confidence bounds for the regression equation.
//xarray, yarray:  list/array of data values
//student answer:  the $stuanswers[$thisq] which is a line equation like "2x+3"
//variable:  defaults to "x"
//alpha: for confidence bound.  defaults to .05
//return array(answer, showanswer) to be used to set $answer and $showanswer
function checklineagainstdata($xarr,$yarr,$line,$var="x",$alpha=.05) {
	if (!is_array($xarr)) { $xarr = explode(',',$xarr);}
	if (!is_array($yarr)) { $yarr = explode(',',$yarr);}
	if (count($xarr)!=count($yarr)) {
		echo "Error: linreg requires xarray length = yarray length";
		return false;
	}
	if (count($xarr)<3) {
		echo "Requires 3 or more data values";
		return false;
	}
	$sx = array_sum($xarr);
	$sy = array_sum($yarr);
	$sxx=0; $syy=0; $sxy = 0;
	for ($i=0; $i<count($xarr); $i++) {
		$sxx += $xarr[$i]*$xarr[$i];
		$syy += $yarr[$i]*$yarr[$i];
		$sxy += $xarr[$i]*$yarr[$i];
	}
	$n = count($xarr);
	$r = ($n*$sxy - $sx*$sy)/(sqrt($n*$sxx-$sx*$sx)*sqrt($n*$syy-$sy*$sy));
	$m = ($n*$sxy - $sx*$sy)/($n*$sxx - $sx*$sx);
	$b = ($sy - $sx*$m)/$n;

	if ($line=='') {return array(false,makepretty("`$m $var + $b`"));}

	foreach ($_POST as $k=>$v) { //try to catch junk answers
		if ($v==$line) {
			if (preg_match('/[^,\d\.\-]/',$_POST['qn'.substr($k,2).'-vals'])) {
				return array(false,makepretty("`$m $var + $b`"));
			}
		}
	}

  $linefunc = makeMathFunction(makepretty($line), $var);
  if ($linefunc === false) {  //parse eror
    return array(false,makepretty("`$m $var + $b`"));
  }
	$xmin = min($xarr);
	$xmax = max($xarr);
	$dx = ($xmax-$xmin)/5;
	if ($dx<=0) {
		echo "error with xmin/xmax";
		return false;
	}
	$isinbounds = true;
	$sqres = 0;
	for ($i=0;$i<count($xarr);$i++) {
		$sqres += ($yarr[$i] - ($m*$xarr[$i] + $b))*($yarr[$i] - ($m*$xarr[$i] + $b));
	}
	$sqres = sqrt($sqres/($n - 2));
	$sdiv = $sxx - $sx*$sx/$n;
	$tcrit = abs(invtcdf($alpha/2,$n-2));
	$xbar = $sx/$n;
	for ($x = $xmin;$x<$xmax*1.02;$x+=$dx) {
		$ypred = $m*$x+$b;
		$yline = $linefunc([$var=>$x]);
		$yconf = $tcrit*$sqres*sqrt(1+1/$n+($x-$xbar)*($x-$xbar)/$sdiv);
		if (abs($ypred-$yline)>$yconf) {
			$isinbounds = false;
			break;
		}
	}
	if ($isinbounds) {
		return array($line,makepretty("$m $var + $b"));
	} else {
		return array("($line) + 200000",makepretty("`$m $var + $b`"));
	}

}

//checkdrawnlineagainstdata(xarray, yarray, student answer, [grade dots, alpha, grid])
//intended for checking a student answer for drawing a line fit to data.  Determines
//if the student answer is within the confidence bounds for the regression equation.
//xarray, yarray:  list/array of data values
//student answer from draw:  the $stuanswers[$thisq]
//grade dots: default false.  If true, will grade that dots of xarray,yarray were plotted
//alpha: for confidence bound.  defaults to .05
//grid:  If you've modified the grid, include it here
//return array(answer, showanswer) to be used to set $answer and $showanswer
function checkdrawnlineagainstdata($xarr,$yarr,$line, $gradedots=false,$alpha=.05, $gridi="-5,5,-5,5,1,1,300,300") {
	if (!is_array($xarr)) { $xarr = explode(',',$xarr);}
	if (!is_array($yarr)) { $yarr = explode(',',$yarr);}
	$gridi = explode(',',$gridi);
	$grid=array(-5,5,-5,5,1,1,300,300);
	foreach ($gridi as $i=>$v) {
		$grid[$i] = $v;
	}
  if (strpos($grid[0],'0:')!==false) {
		$grid[0] = substr($grid[0],2);
	}
  if (strpos($grid[2],'0:')!==false) {
		$grid[2] = substr($grid[2],2);
	}

	if (count($xarr)!=count($yarr)) {
		echo "Error: linreg requires xarray length = yarray length";
		return false;
	}
	if (count($xarr)<3) {
		echo "Requires 3 or more data values";
		return false;
	}
	$correct = false;
	$answers = array();
	$showanswer = null;
	list($r,$m,$b) = linreg($xarr,$yarr);
	if ($line!='') {
		$lines = gettwopointlinedata($line,$grid[0],$grid[1],$grid[2],$grid[3],$grid[6],$grid[7]);
		if ($lines[0][0]==$lines[0][2]) {
			$stum = 100000;
		} else {
			$stum = ($lines[0][3] - $lines[0][1])/($lines[0][2] - $lines[0][0]);
		}
		$stub = $lines[0][1] - $stum*$lines[0][0];
		list($ans,$sa) = checklineagainstdata($xarr,$yarr,"$stum*x+$stub","x",$alpha);
		if ($gradedots) {
			$answers = arraystodots($xarr,$yarr);
			$answers[] = $ans;
		} else {
			$answers = $ans;
		}
		$eqns = arraystodoteqns($xarr,$yarr);
		$eqns[] = "$m*x+$b,blue";
		$showanswer = showplot($eqns,$grid[0],$grid[1],$grid[2],$grid[3],$grid[4],$grid[5],$grid[6],$grid[7]);
	} else {
		if ($gradedots) {
			$answers = arraystodots($xarr,$yarr);
			$answers[] = "$m*x+$b";
		} else {
			$answers = "$m*x+$b";
		}
	}
	return array($answers,$showanswer);
}


//binomialpdf(N,p,x)
//Computes the probability of x successes out of N trials
//where each trial has probability p of success
function binomialpdf($N,$p,$x) {
	if (!is_finite($p) || !is_finite($N) || $p<0 || $p>1 || $x < 0) {
		echo 'invalid inputs to invtcdf';
		return 0;
	}
	return (nCr($N,$x)*pow($p,$x)*pow(1-$p,$N-$x));
}


//binomialcdf(N,p,x)
//Computes the probably of &lt;=x successes out of N trials
//where each trial has probability p of success
function binomialcdf($N,$p,$x) {
	if (!is_finite($p) || !is_finite($N) || $p<0 || $p>1 || $x < 0) {
		echo 'invalid inputs to invtcdf';
		return 0;
	}
	$out = 0;
	for ($i=0;$i<=$x;$i++) {
		$out += binomialpdf($N,$p,$i);
	}
	return $out;
}

//chi2teststat(m)
//Computes the test stat sum((E-O)^2/E) given a matrix of values
function chi2teststat($m) {
	if (!is_array($m) || !is_array($m[0])) {
		echo 'invalid inputs to chi2teststat';
		return 0;
	}
	$rows = count($m);
	$cols = count($m[0]);
 	$rowtot = array();
 	$coltot = array_fill(0,$cols,0);
	foreach ($m as $i=>$r) {
		$rowtot[$i] = array_sum($r);
		for ($j=0;$j<$cols;$j++) {
			$coltot[$j] += $r[$j];
		}
	}
	$tottot = array_sum($rowtot);
	$teststat = 0;
	for ($r=0;$r<$rows;$r++) {
		for ($c=0;$c<$cols;$c++) {
			$E = $rowtot[$r]*$coltot[$c]/$tottot;
			$teststat += ($E-$m[$r][$c])*($E-$m[$r][$c])/$E;
		}
	}
	return $teststat;

}

//chi2cdf(x,df)
//Computes the area to the left of x under the chi-squared disribution
//with df degrees of freedom
function chi2cdf($x,$a) {
	if ($x<0 || !is_finite($x) || $a < 1) {
		echo 'Invalid input to chi2cdf';
		return 0;
	}
    return gamma_cdf(0.5*$x,0.5*$a,1.0,0.0);
}

function chicdf($x,$a) {
	if ($x<0 || !is_finite($x) || $a < 1) {
		echo 'Invalid input to chi2cdf';
		return 0;
	}
    return gamma_cdf(0.5*$x,0.5*$a,1.0,0.0);
}

function invchicdf($cdf,$a) {
	return invchi2cdf($cdf,$a);
}

//invchi2cdf(p,df)
//Compuates the x value with left-tail probability p under the
//chi-squared distribution with df degrees of freedom
function invchi2cdf($cdf,$a) {
	if (!is_finite($cdf) || !is_finite($a) || $cdf < 0 || $cdf > 1 || $a < 1) {
		echo 'Invalid input to invchicdf';
		return 0;
	}
	$aa = 0.6931471806;
  $c1 = 0.01;
  $c2 = 0.222222;
  $c3 = 0.32;
  $c4 = 0.4;
  $c5 = 1.24;
  $c6 = 2.2;
  $c7 = 4.67;
  $c8 = 6.66;
  $c9 = 6.73;
  $c10 = 13.32;
  $c11 = 60.0;
  $c12 = 70.0;
  $c13 = 84.0;
  $c14 = 105.0;
  $c15 = 120.0;
  $c16 = 127.0;
  $c17 = 140.0;
  $c18 = 175.0;
  $c19 = 210.0;
  $c20 = 252.0;
  $c21 = 264.0;
  $c22 = 294.0;
  $c23 = 346.0;
  $c24 = 420.0;
  $c25 = 462.0;
  $c26 = 606.0;
  $c27 = 672.0;
  $c28 = 707.0;
  $c29 = 735.0;
  $c30 = 889.0;
  $c31 = 932.0;
  $c32 = 966.0;
  $c33 = 1141.0;
  $c34 = 1182.0;
  $c35 = 1278.0;
  $c36 = 1740.0;
  $c37 = 2520.0;
  $c38 = 5040.0;
  $cdf_max = 0.999998;
  $cdf_min = 0.000002;
  $e = 0.0000005;

  $it_max = 20;
  if ($cdf < $cdf_min) {
	  echo "p < min - can't do it!";
	  return 0;
  }
  if ($cdf_max < $cdf) {
	  echo "p > max - can't do it";
	  return 0;
  }
  $xx = 0.5*$a;
  $c = $xx-1.0;
  $g = gamma_log($a/2.0);
  if ($a < -$c5*log($cdf)) {
	  $ch = pow(($cdf*$xx*exp($g+$xx*$aa)),(1.0/$xx));
	  if ($ch < $e) {
		  $x = $ch;
		  return $x;
	  }
  } else if ($a <= $c3) {
	  $ch = $c4;
	  $a2 = log(1.0 - $cdf);
	  while (1) {
		  $q = $ch;
		  $p1 = 1.0+$ch*($c7 + $ch);
		  $p2 = $ch*($c9 + $ch*($c8+$ch));
		  $t = -0.5+($c7 + 2.0*$ch)/$p1 - ($c9 + $ch*($c10 + 3.0*$ch)) / $p2;
		  $ch = $ch - (1.0 - exp($a2 + $g + 0.5*$ch + $c*$aa)*$p2/$p1)/$t;
		  if (abs($q/$ch - 1.0) <= $c1) {
			  break;
		  }
	  }
  } else {
	  $x2 = invnormalcdf($cdf);
	  $p1 = $c2/$a;
	  $ch = $a*pow(($x2*sqrt($p1) + 1.0 - $p1),3);
	  if ($c6*$a + 6.0 < $ch) {
		  $ch = -2.0*(log(1.0 - $cdf) - $c*log(0.5*$ch)+$g);
	  }
  }
  for ($i=1;$i<=$it_max;$i++) {
	  $q = $ch;
	  $p1 = 0.5*$ch;
	  $p2 = $cdf - gamma_inc($xx,$p1);
	  $t = $p2*exp($xx*$aa + $g + $p1 - $c*log($ch));
	  $b = $t/$ch;
	  $a2 = 0.5*$t - $b*$c;
	  $s1 = ($c19 + $a2 * ($c17 + $a2 * ($c14 + $a2 *($c13 + $a2 * ($c12 + $a2 * $c11))))) / $c24;
	  $s2 = ($c24 + $a2 * ($c29 + $a2 * ($c32 + $a2 *($c33 + $a2 * $c35)))) / $c37;
	  $s3 = ($c19 + $a2 * ($c25 + $a2 * ($c28 + $a2 * $c31)))/$c37;
	  $s4 = ($c20 + $a2 * ($c27 + $a2 * $c34) + $c * ($c22 + $a2 * ($c30 + $a2 *$c36)))/$c38;
	  $s5 = ($c13 + $c21 + $a2 + $c * ($c18 + $c26 * $a2))/$c37;
	  $s6 = ($c16 + $c*($c23 + $c16*$c))/$c38;
	  $ch = $ch + $t*(1.0 + 0.5*$t*$s1 - $b*$c * ($s1-$b*($s2-$b*($s3-$b*($s4-$b*($s5-$b*$s6))))));
	  if ($e < abs($q/$ch - 1.0)) {
		  $x = $ch;
		  return ($x);
	  }
  }
  $x = $ch;
  echo "Convergence not reached in invchisquare";
  return $x;
}

function gamma_cdf($x, $shape, $scale=1, $offset=0) {
    return gamma_inc($shape, ($x-$offset)/$scale);
}
function gamma_inc($p,$x,$dec=4) {
	$exp_arg_min = -88.0;
	$overflow = 1e37;
	$plimit = 1000;
	$tol = 1e-7;
	$xbig = 1e8;
	$value = 0.0;

	if ($plimit<$p) {
		$pn1 = 3.0*sqrt($p)*(pow($x/$p,1.0/3.0) + 1.0/(9.0*$p)-1.0);
		$value = normalcdf($pn1,$dec);
		return $value;
	}
	if ($xbig<$x) {
		return 1.0;
	}
	if ($x<=1.0 || $x<$p) {
		$arg = $p*log($x) - $x - gamma_log($p+1.0);
		$c = 1.0;
		$value = 1.0;
		$a = $p;
		while ($c>$tol) {
			$a += 1.0;
			$c = $c*$x/$a;
			$value += $c;
		}
		$arg += log($value);
		if ($exp_arg_min <= $arg) {
			$value = exp($arg);
		} else {
			$value = 0.0;
		}
	} else {
		$arg = $p*log($x)-$x-gamma_log($p);
		$a = 1.0 - $p;
		$b = $a+$x+1.0;
		$c = 0.0;
		$pn1 = 1.0;
		$pn2 = $x;
		$pn3 = $x+1.0;
		$pn4 = $x*$b;
		$value = $pn3/$pn4;
		while (1) {
			$a += 1.0;
			$b += 2.0;
			$c += 1.0;
			$pn5 = $b*$pn3 - $a*$c*$pn1;
			$pn6 = $b*$pn4 - $a*$c*$pn2;

			if (0 < abs($pn6)) {
				$rn = $pn5/$pn6;
				if (abs($value-$rn) <= min($tol,$tol*$rn)) {
					$arg += log($value);
					if ($exp_arg_min <= $arg) {
						$value = 1.0 - exp($arg);
					} else {
						$value = 1.0;
					}
					return $value;
				}
				$value = $rn;
			}
			$pn1 = $pn3;
			$pn2 = $pn4;
			$pn3 = $pn5;
			$pn4 = $pn6;
			if ($overflow <= abs($pn5)) {
				$pn1 = $pn1/$overflow;
				$pn2 = $pn2/$overflow;
				$pn3 = $pn3/$overflow;
				$pn4 = $pn4/$overflow;
			}
		}
	}
	return $value;
}

function gamma_log($x) {
    // from jStat
    $cof = [
        76.18009172947146, -86.50532032941677, 24.01409824083091,
        -1.231739572450155, 0.1208650973866179e-2, -0.5395239384953e-5
    ];
    $ser = 1.000000000190015;
    $y = $xx = $x;
    $tmp = $x + 5.5;
    $tmp -= ($xx + 0.5) * log($tmp);
    for ($j=0; $j < 6; $j++) {
        $ser += $cof[$j] / (++$y);
    }
    return log(2.5066282746310005 * $ser / $xx) - $tmp;
}
/*
function gamma_log($x) {
 $c = array(
    -1.910444077728E-03,
     8.4171387781295E-04,
    -5.952379913043012E-04,
     7.93650793500350248E-04,
    -2.777777777777681622553E-03,
     8.333333333333333331554247E-02,
     5.7083835261E-03 );
  $d1 = - 5.772156649015328605195174E-01;
  $d2 =   4.227843350984671393993777E-01;
  $d4 =   1.791759469228055000094023;
  $frtbig = 1.42E+09;
  $p1 = array(
    4.945235359296727046734888,
    2.018112620856775083915565E+02,
    2.290838373831346393026739E+03,
    1.131967205903380828685045E+04,
    2.855724635671635335736389E+04,
    3.848496228443793359990269E+04,
    2.637748787624195437963534E+04,
    7.225813979700288197698961E+03 );
  $p2 = array(
    4.974607845568932035012064,
    5.424138599891070494101986E+02,
    1.550693864978364947665077E+04,
    1.847932904445632425417223E+05,
    1.088204769468828767498470E+06,
    3.338152967987029735917223E+06,
    5.106661678927352456275255E+06,
    3.074109054850539556250927E+06 );
   $p4 = array(
    1.474502166059939948905062E+04,
    2.426813369486704502836312E+06,
    1.214755574045093227939592E+08,
    2.663432449630976949898078E+09,
    2.940378956634553899906876E+010,
    1.702665737765398868392998E+011,
    4.926125793377430887588120E+011,
    5.606251856223951465078242E+011 );
  $pnt68 = 0.6796875;
  $q1 = array(
    6.748212550303777196073036E+01,
    1.113332393857199323513008E+03,
    7.738757056935398733233834E+03,
    2.763987074403340708898585E+04,
    5.499310206226157329794414E+04,
    6.161122180066002127833352E+04,
    3.635127591501940507276287E+04,
    8.785536302431013170870835E+03 );
  $q2 = array(
    1.830328399370592604055942E+02,
    7.765049321445005871323047E+03,
    1.331903827966074194402448E+05,
    1.136705821321969608938755E+06,
    5.267964117437946917577538E+06,
    1.346701454311101692290052E+07,
    1.782736530353274213975932E+07,
    9.533095591844353613395747E+06 );
  $q4 = array(
    2.690530175870899333379843E+03,
    6.393885654300092398984238E+05,
    4.135599930241388052042842E+07,
    1.120872109616147941376570E+09,
    1.488613728678813811542398E+010,
    1.016803586272438228077304E+011,
    3.417476345507377132798597E+011,
    4.463158187419713286462081E+011 );
  $sqrtpi = 0.9189385332046727417803297;
  $xbig = 4.08E+36;

  if ($x<=0 || $xbig < $x) {
	  return 1e10;
  }
  if ($x <= 1e-10) {
	  $res = -log($x);
  } else if ($x<=1.5) {
	  if ($x<$pnt68) {
		  $corr = -log($x);
		  $xm1 = $x;
	  } else {
		  $corr = 0.0;
		  $xm1 = ($x-0.5) - 0.5;
	  }
	  if ($x<0.5 || $pnt68 <= $x) {
		  $xden = 1.0;
		  $xnum = 0.0;
		  for ($i=0; $i<8; $i++) {
			  $xnum = $xnum*$xm1 + $p1[$i];
			  $xden = $xden*$xm1 + $q1[$i];
		  }
		  $res = $corr + ($xm1*($d1 + $xm1*($xnum/$xden)));
	  } else {
		  $xm2 = ($x-0.5)-0.5;
		  $xden = 1.0;
		  $xnum = 0.0;
		  for ($i=0; $i<8; $i++) {
			  $xnum = $xnum*$xm2 + $p2[$i];
			  $xden = $xden*$xm2 + $q2[$i];
		  }
		  $res = $corr + ($xm2*($d2 + $xm2*($xnum/$xden)));
	  }
  } else if ($x<=4.0) {
	  $xm2 = $x-2.0;
	  $xden = 1.0;
	  $xnum = 0.0;
	  for ($i=0; $i<8; $i++) {
		  $xnum = $xnum*$xm2 + $p2[$i];
		  $xden = $xden*$xm2 + $q2[$i];
	  }
	  $res = $xm2*($d2 + $xm2*($xnum/$xden));
  } else if ($x <= 12.0) {
	  $xm4 = $x - 4.0;
	  $xden = -1.0;
	  $xnum = 0.0;
	  for ($i=0; $i<8; $i++) {
		  $xnum = $xnum*$xm4 + $p4[$i];
		  $xden = $xden*$xm4 + $q4[$i];
	  }
	  $res = $d4 + $xm4*($xnum/$xden);
  } else {
	  $res = 0.0;
	  if ($x<= $frtbig) {
		  $res = $c[6];
		  $xsq = $x*$x;
		  for ($i=0;$i<6;$i++) {
			  $res = $res/$xsq + $c[$i];
		  }
	  }
	  $res = $res/$x;
	  $corr = log($x);
	  $res = $res + $sqrtpi - 0.5*$corr;
	  $res = $res + $x*($corr - 1.0);
  }
  return $res;
}
*/

function gamma_inv($p,$a,$scale=1) {
	//adapted from https://github.com/jstat/jstat/blob/65ce096a99f753d6a22482e5e74accbfc1c33767/src/special.js#L254
    $a1 = $a - 1;
	$EPS = 1e-8;
	$gln = gamma_log($a);

	if ($p >= 1) {
		return $scale*max(100, $a + 100 * sqrt($a));
	}
	if ($p <= 0) {
		return 0;
	}
	if ($a > 1) {
		$lna1 = log($a1);
		$afac = exp($a1 * ($lna1 - 1) - $gln);
		$pp = ($p < 0.5) ? $p : (1 - $p);
		$t = sqrt(-2 * log($pp));
		$x = (2.30753 + $t * 0.27061) / (1 + $t * (0.99229 + $t * 0.04481)) - $t;
		if ($p < 0.5) {
            $x = -$x;
        }
		$x = max(1e-3, $a * pow(1 - 1 / (9 * $a) - $x / (3 * sqrt($a)), 3));
	} else {
		$t = 1 - $a * (0.253 + $a * 0.12);
        if ($p < $t) {
            $x = pow($p / $t, 1 / $a);
        } else {
            $x = 1 - log(1 - ($p - $t) / (1 - $t));
        }
    }

	for($j=0; $j < 12; $j++) {
		if ($x <= 0) {
			return 0;
        }
        $err = gamma_inc($a, $x) - $p;
		if ($a > 1) {
			$t = $afac * exp(-($x - $a1) + $a1 * (log($x) - $lna1));
		} else {
			$t = exp(-$x + $a1 * log($x) - $gln);
        }
        if ($t==0) { 
            break; 
        }
		$u = $err / $t;
		$x -= ($t = $u / (1 - 0.5 * min(1, $u * (($a - 1) / $x - 1))));
		if ($x <= 0) {
			$x = 0.5 * ($x + $t);
		}
		if (abs($t) < $EPS * $x) {
			break;
		}
	}
	return $scale*$x;
}

//fcdf(f,df1,df2)
//Returns the area to right of the F-value f for the f-distribution
//with df1 and df2 degrees of freedom (techinically it's 1-CDF)
//Algorithm is accurate to approximately 4-5 decimals
function fcdf($x,$df1,$df2) {
	if (!is_finite($x) || !is_finite($df1) || !is_finite($df2) || $df1 < 1 || $df2 < 1 || $x < 0) {
		echo 'Invalid input to fcdf';
		return 0;
    }
    //$p1 = fcall(fspin($x,$df1,$df2));
    $p1 = 1-beta_cdf($df1*$x/($df1*$x + $df2), $df1/2, $df2/2);

	return $p1;
}

function fcall($x) {
	if ($x>=0) {
		$x += 0.0000005;
	} else {
		$x -= 0.0000005;
	}
	return $x;
}
function fspin($f,$df1,$df2) {
	$pj2 = M_PI/2;
	$pj4 = M_PI/4;
	$px2 = 2*M_PI;
	$exx = 1.10517091807564;
	$dgr = 180/M_PI;

	$x = $df2/($df1*$f+$df2);
	if (($df1%2)==0) {
		return (LJspin(1-$x,$df2,$df1+$df2-4,$df2-2)*pow($x,$df2/2));
	} else if (($df2%2)==0) {
		return (1-LJspin($x,$df1,$df1+$df2-4,$df1-2)*pow(1-$x,$df1/2));
	}
	$tan = atan(sqrt($df1*$f/$df2));
	$a = $tan/$pj2;
	$sat = sin($tan);
	$cot = cos($tan);
	if ($df2>1) {
		$a = $a+$sat*$cot*LJspin($cot*$cot,2,$df2-3,-1)/$pj2;
	}
	if ($df1==1) {
		return (1-$a);
	}
	$c = 4*LJspin($sat*$sat,$df2+1,$df1+$df2-4,$df2-2)*$sat*pow($cot,$df2)/M_PI;
	if ($df2==1) {
		return (1-$a+$c/2);
	}
	$k = 2;
	while ($k<=($df2-1)/2) {
		$c = $c*$k/($k-0.5);
		$k++;
	}
	return (1-$a+$c);
}

function LJspin($q,$i,$j,$b) {
	$zz = 1;
	$z = $zz;
	$k = $i;
	while ($k<=$j) {
		$zz = $zz*$q*$k/($k-$b);
		$z = $z+$zz;
		$k += 2;
	}
	return $z;
}

function beta_cdf($x, $a, $b) {
    // based on jStat.ibeta
    if ($x > 1) { 
        return 1;
    } else if ($x < 0) { 
        return 0;
    }
    $bt = ($x === 0 || $x === 1) ?  0 :
     (exp(gamma_log($a + $b) - gamma_log($a) -
     gamma_log($b) + $a * log($x) + $b * log(1 - $x)));
    if ($x < 0 || $x > 1) {
        return false;
    }
    if ($x < ($a + 1) / ($a + $b + 2)) {
        // Use continued fraction directly.
        return $bt * jstat_betacf($x, $a, $b) / $a;
    }
    // else use continued fraction after making the symmetry transformation.
    return 1 - $bt * jstat_betacf(1 - $x, $b, $a) / $b;
}

function beta_inv($p, $a, $b) {
    // based on jStat.ibetainv
    $st = microtime(true);
    $EPS = 1e-8;
    $a1 = $a - 1;
    $b1 = $b - 1;

    if ($p <= 0) {
        return 0;
    }
    if ($p >= 1) {
        return 1;
    }
    if ($a >= 1 && $b >= 1) {
        $pp = ($p < 0.5) ? $p : (1 - $p);
        $t = sqrt(-2 * log($pp));
        $x = (2.30753 + $t * 0.27061) / (1 + $t* (0.99229 + $t * 0.04481)) - $t;
        if ($p < 0.5) {
            $x = -$x;
        }
        $al = ($x * $x - 3) / 6;
        $h = 2 / (1 / (2 * $a - 1)  + 1 / (2 * $b - 1));
        $w = ($x * sqrt($al + $h) / $h) - (1 / (2 * $b - 1) - 1 / (2 * $a - 1)) *
            ($al + 5 / 6 - 2 / (3 * $h));
        $x = $a / ($a + $b * exp(2 * $w));
    } else {
        $lna = log($a / ($a + $b));
        $lnb = log($b / ($a + $b));
        $t = exp($a * $lna) / $a;
        $u = exp($b * $lnb) / $b;
        $w = $t + $u;
        if ($p < $t / $w) {
            $x = pow($a * $w * $p, 1 / $a);
        }
        else {
            $x = 1 - pow($b * $w * (1 - $p), 1 / $b);
        }
    }
    $afac = -gamma_log($a) - gamma_log($b) + gamma_log($a + $b);
    for ($j=0; $j < 10; $j++) {
        if ($x < 1e-10 || $x === 1) {
            return round($x,10);
        }
        $err = beta_cdf($x, $a, $b) - $p;
        $t = exp($a1 * log($x) + $b1 * log(1 - $x) + $afac);
        $u = $err / $t;
        $x -= ($t = $u / (1 - 0.5 * min(1, $u * ($a1 / $x - $b1 / (1 - $x)))));
        if ($x <= 0) {
            $x = 0.5 * ($x + $t);
        }
        if ($x >= 1) {
            $x = 0.5 * ($x + $t + 1);
        }
        if (abs($t) < ($EPS * $x) && $j > 0) {
            break;
        }
    }
    return round($x,10);
}

function jstat_betacf($x,$a,$b) {
    $fpmin = 1e-30;
    $qab = $a + $b;
    $qap = $a + 1;
    $qam = $a - 1;
    $c = 1;
    $d = 1 - $qab * $x / $qap;
  
    // These q's will be used in factors that occur in the coefficients
    if (abs($d) < $fpmin) {
      $d = $fpmin;
    }
    $d = 1 / $d;
    $h = $d;
  
    for ($m=1; $m <= 100; $m++) {
      $m2 = 2 * $m;
      $aa = $m * ($b - $m) * $x / (($qam + $m2) * ($a + $m2));
      // One step (the even one) of the recurrence
      $d = 1 + $aa * $d;
      if (abs($d) < $fpmin) {
        $d = $fpmin;
      }
      $c = 1 + $aa / $c;
      if (abs($c) < $fpmin) {
        $c = $fpmin;
      }
      $d = 1 / $d;
      $h *= $d * $c;
      $aa = -($a + $m) * ($qab + $m) * $x / (($a + $m2) * ($qap + $m2));
      // Next step of the recurrence (the odd one)
      $d = 1 + $aa * $d;
      if (abs($d) < $fpmin) {
        $d = $fpmin;
      }
      $c = 1 + $aa / $c;
      if (abs($c) < $fpmin) {
        $c = $fpmin;
      }
      $d = 1 / $d;
      $del = $d * $c;
      $h *= $del;
      if (abs($del - 1.0) < 3e-7) {
        break;
      }
    }
    return $h;  
}

//invfcdf(p,df1,df2)
//Computes the f-value with probability of p to the right
//with degrees of freedom df1 and df2
//Algorithm is accurate to approximately 2-4 decimal places
//Less accurate for smaller p-values
function invfcdf($p,$df1,$df2) {
	if (!is_finite($p) || !is_finite($df1) || !is_finite($df2) || $df1 < 1 || $df2 < 1 || $p < 0 || $p > 1) {
		echo 'Invalid input to invfcdf';
		return 0;
	}
	$v = 0.5;
	$dv = 0.5;
	$f = 0;
	$cnt = 0;
	while ($dv>1e-10) {
		$f = 1/$v-1;
		$dv = $dv/2;
		if (fcdf($f,$df1,$df2)>$p) {
			$v = $v-$dv;
		} else {
			$v = $v+$dv;
		}
		$cnt++;
	}
	return $f;

}

//mosaicplot(rowlabels, columnlabels, count matrix, [width, height])
//creates a mosaic plot (See http://www.wamap.org/course/showlinkedtextpublic.php?cid=1383&id=82972)
//rowlabels: an array of labels for the rows of the display
//columnlabels: an array of labels for the columns of the display
//count matrix: a 2-dimensional array.  $m[1][5] will give the count for
//  rowlabel[1] and columnlabel[5]
//width and height are optional, default to 300 by 300.  Does not include labels
function mosaicplot($rlbl,$clbl,$m, $w = 300, $h=300) {
	$out = '<div>';
	$nrow = count($m);
	$ncol = count($m[0]);
	$cols = array('#f00','#0f0','#00f','#ff0','#f0f','#0ff','#fa0','#ccc','#066','#909','#06f');
	$ccnt = array_fill(0,$ncol,0);
	for ($j=0;$j<$nrow;$j++) {
		for ($i=0;$i<$ncol;$i++) {
			$ccnt[$i] += $m[$j][$i];
		}
	}
	$ctot = array_sum($ccnt);
	$widths = array();

	for ($i=0;$i<$ncol;$i++) {
		$widths[$i] = round($w*$ccnt[$i]/$ctot);
	}

	$out .= '<table style="float:left;"><tbody>';
	$out .= '<tr><td style="height: 3em;">&nbsp;</td></tr>';
	for ($j=0;$j<$nrow;$j++) {
		$out .= '<tr><td style="height: '.round($h*$m[$j][0]/$ccnt[0]).'px; text-align: right;">'.$rlbl[$j].'</td></tr>';
	}
	$out .= '</tbody></table>';
	for ($i=0;$i<$ncol;$i++) {
		$out .= '<table style="table-layout: fixed; float:left;"><tbody>';
		$out .= '<tr style="line-height: 1px;"><td style="text-align: center; width: '.$widths[$i].'px; height: 3em;">'.$clbl[$i].'</td></tr>';
		for ($j=0;$j<$nrow;$j++) {
			$out .= '<tr style="line-height: 1px;"><td style="height: '.round($h*$m[$j][$i]/$ccnt[$i]).'px; background-color: '.$cols[$j].';">&nbsp;</td></tr>';
		}
		$out .= '</tbody></table>';
	}
	$out .= '<div style="height: 1px; clear: left;">&nbsp;</div></div>';
	return $out;
}

//argument should be header,column,header,column,...
function csvdownloadlink() {
  $alist = func_get_args();
  $filename = "data";
  if (count($alist)>1 && is_string($alist[1])) {
      $filename = array_shift($alist);
  }
  if (count($alist)==0 || count($alist)%2==1) {
    echo "invalid arguments to csvdownloadlink";
    return '';
  }
  $rows = array();
  for ($i=0;$i<count($alist);$i+=2) {
    $rows[0] .= '"'.str_replace('"','',$alist[$i]).'",';
    for ($j=0;$j<count($alist[$i+1]);$j++) {
      $rows[$j+1] .= (is_numeric($alist[$i+1][$j]) ?
        floatval($alist[$i+1][$j]) :
        '"'.str_replace('"','',$alist[$i+1][$j]).'"')
        . ',';
    }
  }
  foreach ($rows as $i=>$row) {
    $rows[$i] = rtrim($row,',');
  }
  $str = implode("\n",$rows);
  return '<a download="'.Sanitize::encodeStringForDisplay($filename).'.csv" href="data:text/csv;charset=UTF-8,'.urlencode($str).'">'
    . _('Download CSV').'</a>';
}

//dotplot(array,label,[dot spacing, axis spacing,width,height])
//display macro.  Creates a dotplot from a data set
// array: array of data values
// label: title of the dotplot that will be placed below horizontal axis
// dot spacing: spacing of dots; data will be rounded to nearest (def 1)
// axis spacing: spacing of axis labels (defaults to dot spacing) 
// width,height (optional): width and height in pixels of graph
function dotplot($a,$label,$dotspace=1,$labelspace=null,$width=300,$height=150) {
	if (!is_array($a)) {
		echo 'dotplot expects an array';
		return false;
    }
    if ($dotspace <= 0) {
        $dotspace = 1;
    }
    if ($labelspace === null || $labelspace <= 0) {
        $labelspace = $dotspace;
    }

	sort($a, SORT_NUMERIC);

    $start = round($a[0]/$dotspace)*$dotspace;
	
	$x = $start;
	$curr = 0;
	$alt = "Dotplot for $label <table class=stats><thead><tr><th>Value of Each Dot</th><th>Number of Dots</th></tr></thead>\n<tbody>\n";
	$maxfreq = 0;
	
	// 
	$dx = $dotspace;

    // Create the stack of dots 
	while ($i < count($a)) {
		$alt .= "<tr><td>$x</td>";
		$i = $curr;
		$j = 0.1;
  
		while (($a[$i] < $x+.5*$dx) && ($i < count($a))) {
			$i++;
			$j = $j + 0.6;
			$st .= "dot([$x,$j]);";
		}
		
		$x += $dx;
		
		if (($i-$curr)>$maxfreq) { 
			$maxfreq = $i-$curr;
		}
			
		$alt .= "<td>" . ($i-$curr) . "</td></tr>\n";
		$curr = $i;
	}
  	
	$alt .= "</tbody></table>\n";

	if ($_SESSION['graphdisp']==0) {
		return $alt;
	}
	

	// Start tick marks at the start value
	$x = $start;
	
	// y-values for the size of the tick mark lines
	$tm = -0.025*$maxfreq;
	$tx = 0.025*$maxfreq;

	// initialize 
	$outst = "";
	 
	// Draw the horizontal axes
    // draws the tick marks for the axes.
    $startlabel = floor($start/$labelspace+1e-12)*$labelspace;
    $maxx = round($a[count($a)-1]/$dotspace)*$dotspace;
    $endlabel = ceil($maxx/$labelspace-1e-12)*$labelspace;
    for ($x=$startlabel; $x <=$endlabel; $x+=$labelspace) {
        $outst .= "line([$x,$tm],[$x,$tx]); text([$x,0],\"$x\",\"below\");";
    }  
	
	//initializes SVG frame and canvas.
	$initst = "setBorder(20,40,20,10);initPicture($startlabel,$endlabel,0,$maxfreq);";
  	
	//xtick,ytick,{labels,xgrid,ygrid,dox,doy}
	//,1,null,$step); fill=\"blue\";
	//$initst .= "axes(null,null,null,null,null,0,0); fill=\"blue\"; textabs([". ($width/2+15) .",0],\"$label\",\"above\");";
	$initst .="textabs([". ($width/2+15) .",0],\"$label\",\"above\");";
	$x1 = $startlabel - .2*$labelspace;
	$x2 = $endlabel + .2*$labelspace;
	$initst .="line([$x1,0],[$x2,0]);";
	$outst = $initst.$outst.$st;
	return showasciisvg($outst,$width,$height);
  }

//---------------------------------------------ANOVA-Oneway F ratio-----------------------------------------
// Function: anova1way_f(arr1,arr2, [arr3,...])
// Returns F ratio and the corresponding P value as an array. 
//
// Parameters:
// arr1, arr2, ...: Arrays in the form [2,3,4,5,...]; it also accepts unequal sample sizes. 
//  
// Returns:
// F ratio and the corresponding P value as an array in the form [F ratio, P value].

function anova1way_f(... $arr){
    $out = anova1way(... $arr);
    return [$out[0][3], $out[0][4]];
}


//---------------------------------------------ANOVA-Oneway array-------------------------------------------
// Function: anova1way(arr1,arr2, [arr3,...])
// Returns ANOVA table as an array with each row corresponding to Factor A, error (residual), and totals. 
//
// Parameters:
// arr1, arr2, ...: Arrays in the form [2,3,4,5,...]; it also accepts unequal sample sizes. 
//  
// Returns:
// ANOVA table as an array in the following format. This array can be used in anova_table() to tabulate data for display.
// [[SS_A, df_A, MS_A, F_A, P_A],[SS_E,df_E,MS_E],[SS_T,df_T]] 
// where SS is sum of the squares, df is the degree of freedom, MS is mean square, F is F ratio, and P is P value.
// And A, E, and T correspond to Factor A, error (residual), and total, respectively. 

function anova1way(... $arr){
	$n=array();  
	foreach($arr as $a){
		if (!is_array($a)) { $a = explode(',',$a);};
		$n[]=count($a);
	}
	if (count($n)<2) {
		echo "Error: ANOVA requires two or more arrays";
		return false;
	}
	$N=array_sum($n);	
	$numargs = func_num_args();
    //$args=func_get_args();

	$mean=array();
	$ss=array();
	for($i=0;$i<$numargs;$i++){
		$mean[$i]=mean($arr[$i]);
		$ss[$i]=variance($arr[$i])*(count($arr[$i])-1);
		//$n[$i]=count($args[$i]);
	}
	
	$total = array_map(function($x, $y) { return $x * $y; },
                   $mean, $n);

	$gmean=array_sum($total)/$N; //grand mean

	//Sum of the square for Factor A uneequal sample sizes
	$ssa=array();
	for ($i=0;$i<$numargs;$i++){
		$ssa[$i]=$n[$i]*($mean[$i]-$gmean)**2; 
	}
	$ssA=array_sum($ssa); //Sum of the square for Factor A uneequal sample sizes
	$ssE=array_sum($ss); //Sum of the square for Residual (Error)
	$ssT=$ssA+$ssE;
	$dfA=$numargs-1;
	$dfE=$N-$numargs;
	$dfT=$dfA+$dfE;
	
	
	$msA=$ssA/$dfA; //mean square of Factor A
	$msE=$ssE/$dfE;    //pooled variance (residual or error)
	$msT=$msA+$msE;  //total sum of the squares
	$F_a=$msA/$msE;    //F Ratio
    
	$p_a=fcdf($F_a,$dfA,$dfE); //P value

	return (array([$ssA,$dfA,$msA,$F_a,$p_a],[$ssE,$dfE,$msE],[$ssT,$dfT]));//[$F_a,$p_a]
	

}


//---------------------------------------------ANOVA-Twoway array--------------------------------------------
// Function: anova2way(arr,[replication = False])
// Returns ANOVA table as an array with each row corresponding to Factor A, Factor B, 
// their interaction (only with replication), error (residual), and totals. 
//
// Parameters:
// arr: An array in the follwing form: 
//  for twoway WITH replication - example: $arr=[[[4,5,6,5],[7,9,8,12],[10,12,11,9]],[[6,6,4,4],[13,15,12,12],[12,13,10,13]]]
//  for twoway WITHOUT replication - example: $arr=[[53,61,51],[47,55,51],[46,52,49],[50,58,54],[49,54,50]]
// replication: Optional - boolean (true or false) it specifies whether the ANOVA with replication
//             (multiple observations for each group) or without replication (one observation per group)
//             is to be performed. The default is False - without replication.
// Returns:
// ANOVA table as an array in the following format. This array can be used in anova_table() to tabulate data for display.
// [[SS_A, df_A, MS_A, F_A, P_A],[SS_B, df_B, MS_B, F_B, P_B],[SS_I, df_I, MS_I, F_I, P_I],[SS_E,df_E,MS_E],[SS_T,df_T]] 
// where SS is sum of the squares, df is the degree of freedom, MS is mean square, F is F ratio, and P is P value.
// And A, B, I, E, and T correspond to Factor A, Factor B, their interaction (only with replication), 
// error (residual), and total, respectively.

function anova2way($arr, $replication=False){
	
	//with replication:
	if($replication==True){
		$n_b=count($arr); //number of rows: Factor B	
		$n_r=array();
		$n_col=array();

		foreach($arr as $a){
			$n_col[]=count($a);
			if (count(array_unique($n_col))!=1) {
				echo "Error: ANOVA requires the same length for all arrays";
				return false;
			}
		}
		
		for($i=0;$i<count($arr);++$i){
			foreach($arr[$i] as $a){
				$n_r[]=count($a);
				if (count(array_unique($n_r))!=1) {
					echo "Error: ANOVA requires the same number of replicates for all factors";
					return false;
				}
			}
		}
				
		$n_a=count($arr[0]); //number of columns: Factor A
		$n_b=count($arr); //number of rows: Factor B
		$n_r=count($arr[0][0]); //number of replicates

		$m_r=array();
		
		//mean of the replicates
		for($i=0;$i<count($arr);$i++){
			for($j=0;$j<count($arr[0]);++$j){
				$m_r[$i][$j]=mean($arr[$i][$j]);
				//$g=mean($arr[$i][$j]);
			}
		}

		$m_a=array(); //mean of the columns
		$m_b=array(); //mean of the rows

		for($i=0;$i<count($m_r);$i++){
			$m_b[]=mean($m_r[$i]);
		}
		$m_r_t=array_map(null, ...$m_r);
		
		for($j=0;$j<count($m_r_t);$j++){
			$m_a[]=mean($m_r_t[$j]);
		}
		$gmean=mean($m_a); //grand average

		$ssA=variance($m_a)*($n_a-1)*$n_b*$n_r; //Sum of the square for Factor A
		$ssB=variance($m_b)*($n_b-1)*$n_a*$n_r; //Sum of the square for Factor A
		$dfA=($n_a-1);
		$dfB=($n_b-1);
		$msA=$ssA/$dfA; //mean square of Factor A
		$msB=$ssB/$dfB; //mean square of Factor B

		$ss=array(); //Residual
		for($i=0;$i<$n_b;$i++){
			for($j=0;$j<$n_a;$j++){
				for($k=0;$k<$n_r;$k++){
					$ss[]=($m_r[$i][$j]-$arr[$i][$j][$k])**2;
				}
				
			}
		}
		$ssE=array_sum($ss);
		$dfE=($n_r-1)*$n_b*$n_a;
		$msE=$ssE/$dfE;    //pooled variance-within (residual or error)

		$ss_i=array(); //SS of Interaction between two factors
		for($i=0;$i<count($m_r);$i++){
			for($j=0;$j<count($m_r[0]);$j++){
				$ss_i[]=$n_r*($m_r[$i][$j]-$m_a[$j]-$m_b[$i]+$gmean)**2;
			}
		}

		$ssI=array_sum($ss_i);	
		$dfI=($n_a-1)*($n_b-1);
		$msI=$ssI/$dfI;

		$ssT=$ssA+$ssB+$ssI+$ssE;  //total sum of the squares
		$dfT=($n_a*$n_b*$n_r)-1;

		$F_a=$msA/$msE;    //F Ratio of Factor A
		$F_b=$msB/$msE;    //F Ratio of Factor A
		$F_i=$msI/$msE;    //F Ratio of the interaction of Factors A and B
		
		$p_a=fcdf($F_a,$dfA,$dfE); //P value of factor A
		$p_b=fcdf($F_b,$dfB,$dfE); //P value of factor B
		$p_i=fcdf($F_i,$dfI,$dfE); //P value of factor B
		$ans=array([$ssA,$dfA,$msA,$F_a,$p_a],[$ssB,$dfB,$msB,$F_b,$p_b],[$ssI,$dfI,$msI,$F_i,$p_i],[$ssE,$dfE,$msE],[$ssT,$dfT]);
	
	}

	//without replication:
	else{
		$n_col=array();  
		foreach($arr as $a){
			$n_col[]=count($a);
			if (count(array_unique($n_col))!=1) {
				echo "Error: ANOVA requires the same length for all arrays";
				return false;
			}
		}
		$arr_t=array_map(null, ...$arr);

		$n_a=count($arr[0]); //number of columns: Factor A
		$n_b=count($arr); //number of rows: Factor B
		$m_a=array();
		$m_b=array();
		for($i=0;$i<count($arr);$i++){
			$m_b[]=mean($arr[$i]);
		}

		for($j=0;$j<count($arr_t);$j++){
			$m_a[]=mean($arr_t[$j]);
		}

		$gmean=mean($m_a); //grand average
		$ssA=variance($m_a)*$n_b*($n_a-1); //Sum of the square for Factor A
		$ssB=variance($m_b)*$n_a*($n_b-1); //Sum of the square for Factor A
		$dfA=($n_a-1);
		$dfB=($n_b-1);
		
		$ss=array(); //Residual
		for($i=0;$i<count($arr);$i++){
			for($j=0;$j<count($arr[0]);$j++){
				$ss[]=($arr[$i][$j]-$m_a[$j]-$m_b[$i]+$gmean)**2;
			}
		}
		$ssE=array_sum($ss);
		$dfE=($n_a-1)*($n_b-1);
		$dfT=($n_a*$n_b)-1;
		
		$msA=$ssA/$dfA; //mean square of Factor A
		$msB=$ssB/$dfB; //mean square of Factor B
		$msE=$ssE/$dfE;    //pooled variance (residual or error)
		$ssT=$ssA+$ssB+$ssE;  //total sum of the squares
		$F_a=$msA/$msE;    //F Ratio of Factor A
		$F_b=$msB/$msE;    //F Ratio of Factor A
		
		$p_a=fcdf($F_a,$dfA,$dfE); //P value of factor A
		$p_b=fcdf($F_b,$dfB,$dfE); //P value of factor B
		$ans=array([$ssA,$dfA,$msA,$F_a,$p_a],[$ssB,$dfB,$msB,$F_b,$p_b],[$ssE,$dfE,$msE],[$ssT,$dfT]);
			
	}
	return ($ans);	
}

//---------------------------------------------ANOVA-Twoway F ratio-----------------------------------------
// Function: anova2way_f(arr, [replication=False])
// Returns F ratio and the corresponding P value for Factor A, Factor B and their interaction (if replication is true). 
//
// Parameters:
// arr: An array in the follwing form: 
//  for twoway WITH replication - example: $arr=[[[4,5,6,5],[7,9,8,12],[10,12,11,9]],[[6,6,4,4],[13,15,12,12],[12,13,10,13]]]
//  for twoway WITHOUT replication - example: $arr=[[53,61,51],[47,55,51],[46,52,49],[50,58,54],[49,54,50]] 
// replication: Optional - boolean (true or false) it specifies whether the ANOVA with replication
//             (multiple observations for each group) or without replication (one observation per group)
//             is to be performed. The default is False - without replication.
//  
// Returns:
// F ratio and the corresponding P value for Factor A, Factor B and their Interaction (if replication is true)
// as an array in the form  array([F_A,P_A],[F_B,P_B],[F_I,P_I]). 	

function anova2way_f($arr, $replication=False){
	$k=anova2way($arr,$replication);
	if($replication==True){
		$ans=[[$k[0][3],$k[0][4]],[$k[1][3],$k[1][4]],[$k[2][3],$k[2][4]]];
	}
		else{
			$ans=[[$k[0][3],$k[0][4]],[$k[1][3],$k[1][4]]];
	}
	
	return($ans);
}



//-----------------------------------------------ANOVA Table---------------------------------------------
// Function: anova_table(arr,[factor=1, replication=False, roundto=12, nameA="factorA", nameB="factorB "])
// Returns ANOVA table for both oneway and twoway ANOVA - display only. The output of anova1way_arr() and 
// anova2way_arr() can be used as the input array for this function.
//
// Parameters:
// arr: An array in the follwing form: 
//   for oneway: [[SS_A, df_A, MS_A, F_A, P_A],[SS_E,df_E,MS_E],[SS_T,df_T]]
//   for twoway WITHOUT replication: [[SS_A, df_A, MS_A, F_A, P_A],[SS_B, df_B, MS_B, F_B, P_B],[SS_E,df_E,MS_E],[SS_T,df_T]]
//   for twoway WITH replication: [[SS_A, df_A, MS_A, F_A, P_A],[SS_B, df_B, MS_B, F_B, P_B],[SS_I, df_I, MS_I, F_I, P_I],[SS_E,df_E,MS_E],[SS_T,df_T]]
// factor: number of factors considered in ANOVA - 1 for one-way and 2 for two-way. The default is 1, one-way ANOVA.
// replication: Optional - boolean (true or false) it specifies whether the ANOVA tewoway with replication
//             (multiple observations for each group) or without replication (one observation per group)
//             is performed. The default is False - without replication.
// roundto: Optional - number of decimal places to which data should be rounded off; 
//          default is 12 decimal places. 
// NameA: Optional - the name of factor A as string to be displayed in the table. Default is "Factor A".
// NameB: Optional - the name of factor B as string to be displayed in the table. Default is "Factor B".
// Returns:
// ANOVA table for displaying data. 


function anova_table(array $array, int $factor = 1, $rep=False, int $roundto=12, string $f1="Factor A", string $f2="Factor B"){
	if ($factor!=1 && $factor!=2) { echo 'error: the factor variable only expects 1 for one-way and 2 for two-way ANOVA'; return '';}
	/*if (!function_exists('calconarray')) {
       // require_once(__DIR__.'/assessment/macros.php');
	}*/
	array_walk_recursive($array, function(&$x) use ($roundto) { $x = round($x,$roundto);});
	
	if ($factor==1){
		$r0=$array[0];
		$r1=$array[1];
		$r2=$array[2];
		
		$out = "<table class=stats><CAPTION><EM>Analysis of Variance: One-Way</EM></CAPTION><thead><tr><th>Source</th><th>SS</th><th>df</th><th>MS</th><th>F Ratio</th><th>P value</th></tr></thead>\n<tbody>\n";
		$out .="<tr><td>$f1</td><td>$r0[0]</td><td>$r0[1]</td><td>$r0[2]</td><td>$r0[3]</td><td>$r0[4]</td></tr>";
		$out .="<tr><td>Residual</td><td>$r1[0]</td><td>$r1[1]</td><td>$r1[2]</td></tr>"; //<td></td><td></td>
		$out .="<tr><td>TOTAL</td><td>$r2[0]</td><td>$r2[1]</td></tr>"; //<td></td><td></td><td></td>
		$out .= "</tbody></table>\n";

	}
		elseif($factor==2 && $rep==False){
			$r0=$array[0];
			$r1=$array[1];
			$r2=$array[2];
			$r3=$array[3];
			$out = "<table class=stats><CAPTION><EM>Analysis of Variance: Two-way without Replication</EM></CAPTION><thead><tr><th>Source</th><th>SS</th><th>df</th><th>MS</th><th>F Ratio</th><th>P value</th></tr></thead>\n<tbody>\n";
			$out .="<tr><td>$f1</td><td>$r0[0]</td><td>$r0[1]</td><td>$r0[2]</td><td>$r0[3]</td><td>$r0[4]</td></tr>";
			$out .="<tr><td>$f2</td><td>$r1[0]</td><td>$r1[1]</td><td>$r1[2]</td><td>$r1[3]</td><td>$r1[4]</td></tr>";
			$out .="<tr><td>Residual</td><td>$r2[0]</td><td>$r2[1]</td><td>$r2[2]</td></tr>"; //<td></td><td></td>
			$out .="<tr><td>TOTAL</td><td>$r3[0]</td><td>$r3[1]</td></tr>"; //<td></td><td></td><td></td>
			$out .= "</tbody></table>\n";

		}

		elseif($factor==2 && $rep==True){
			$r0=$array[0];
			$r1=$array[1];
			$r2=$array[2];
			$r3=$array[3];
			$r4=$array[4];
			$out = "<table class=stats><CAPTION><EM>Analysis of Variance: Two-way with Replication</EM></CAPTION><thead><tr><th>Source</th><th>SS</th><th>df</th><th>MS</th><th>F Ratio</th><th>P value</th></tr></thead>\n<tbody>\n";
			$out .="<tr><td>$f1</td><td>$r0[0]</td><td>$r0[1]</td><td>$r0[2]</td><td>$r0[3]</td><td>$r0[4]</td></tr>";
			$out .="<tr><td>$f2</td><td>$r1[0]</td><td>$r1[1]</td><td>$r1[2]</td><td>$r1[3]</td><td>$r1[4]</td></tr>";
			$out .="<tr><td>Interaction</td><td>$r2[0]</td><td>$r2[1]</td><td>$r2[2]</td><td>$r2[3]</td><td>$r2[4]</td></tr>";
			$out .="<tr><td>Residual</td><td>$r3[0]</td><td>$r3[1]</td><td>$r3[2]</td></tr>"; //<td></td><td></td>
			$out .="<tr><td>TOTAL</td><td>$r4[0]</td><td>$r4[1]</td></tr>"; //<td></td><td></td><td></td>
			$out .= "</tbody></table>\n";

		}
		
	return $out;


}

//-------------------------------------------------Student t-test--------------------------------------------------
// Function: student_t(arr1, arr2, [equalVar = False, paired = False, roundto = 12])
// Computes t statistic and coressponding p-value for two sample student t-test 
//
// Parameters:
// arr1, arr2: Arrays in the form [2,3,4,5,...]; unequal sample sizes are accepted for independent samples. 
// equalVar: Optional - Boolean. Set to True for equal variances; default is False.
// paired: Optional - Boolean. Set to True for paired (dependent) samples; default is False.
// roundto: Optional - number of decimal places to which data should be rounded off; default is 12 decimal places. 
//  
// Returns:
// t statistic, coressponding p-value (area to the right of t-value -  one-tail), and degree of freedom for two sample student t-test: [t , P-value, df] 
// where t is the t statistic, P is the P-value, and df is the degree of freedom used to evalute the P-value.

function student_t($arr1, $arr2, bool $equalVar = False, bool $paired = False, int $roundto=12){

	if (!is_array($arr1)) { $arr1 = explode(',',$arr1);};
	if (!is_array($arr2)) { $arr2 = explode(',',$arr2);};

	//means
	$m1 = mean($arr1);
	$m2 = mean($arr2);

	//variances
	$v1 = variance($arr1);
	$v2 = variance($arr2);

	//sample sizes
	$n1=count($arr1);
	$n2=count($arr2);

	// t statistic for equal variances; independent samples
	if ($equalVar==True && $paired==False){

		$sp = sqrt((($n1-1)*$v1 + ($n2-1)*$v2)/($n1+$n2-2));  //pooled variance for equal-variance case
		$t = round(($m1-$m2)/($sp*sqrt(1/$n1 + 1/$n2)), $roundto);
		$df = $n1 + $n2 -2;

	// t statistic for dependent samples (equal variances) 
	} elseif ($equalVar == True && $paired == True){

		if ($n1 != $n2) { echo 'error: the size of samples must be same for paired t-test'; return '';}

		$diff=array();
		for ($i=0; $i<$n1; $i++){
			$diff[$i] = $arr1[$i] - $arr2[$i];
		}

		$mD = mean($diff); //mean of differences
		$vD = variance($diff); //variance of differences
		$t = round($mD/(sqrt($vD/$n1)), $roundto);
		$df = $n1 - 1;

		// t statistic for unequal variances; independent samples
	} elseif ($equalVar == False && $paired == False) {

		$t = round(($m1-$m2)/sqrt($v1/$n1 + $v2/$n2), $roundto);
		$df_num = ($v1/$n1 + $v2/$n2)**2;
		$df_den = ($v1/$n1)**2/($n1-1) + ($v2/$n2)**2/($n2-1);
		$df = $df_num/$df_den;

	} elseif ($equalVar == False && $paired == True){
		echo 'error: In paired t-test, the population variances are equal, i.e., $equalVar must be set to True'; return '';

	}

	$p = 1- tcdf($t,$df,$roundto);

	return [$t,$p,$df];//[$F_a,$p_a]
}
?>

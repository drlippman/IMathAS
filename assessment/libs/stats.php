<?php
//A library of Stats functions.  Version 1.10, Nov 17, 2017

global $allowedmacros;
array_push($allowedmacros,"nCr","nPr","mean","stdev","absmeandev","percentile","Nplus1percentile","quartile","TIquartile","Excelquartile","Nplus1quartile","allquartile","median","freqdist","frequency","histogram","fdhistogram","fdbargraph","normrand","boxplot","normalcdf","tcdf","invnormalcdf","invtcdf","invtcdf2","linreg","expreg","countif","binomialpdf","binomialcdf","chicdf","invchicdf","chi2cdf","invchi2cdf","fcdf","invfcdf","piechart","mosaicplot","checklineagainstdata","chi2teststat","checkdrawnlineagainstdata");

//nCr(n,r)
//The Choose function
function nCr($n,$r){
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
function mean($a) {
	return (array_sum($a)/count($a));
}

//variance(array)
//the (sample) variance of an array of numbers
function variance($a) {
	$v = 0;
	$mean = mean($a);
	foreach ($a as $x) {
		$v += pow($x-$mean,2);
	}
	return ($v/(count($a)-1));
}

//stdev(array)
//the (sample) standard deviation of an array of numbers
function stdev($a) {
	return sqrt(variance($a));
}

//absmeandev(array)
//the absolute mean deviation of an array of numbers
function absmeandev($a) {
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
	sort($a, SORT_NUMERIC);
	if ($p==0) {
		return $a[0];
	} else if ($p==100) {
		return $a[count($a)-1];
	}

	$l = round(($p/100)*(count($a)+1),2);
	if (floor($l)==$l) {
		return ($a[$l-1]);
	} else {
		return (($a[floor($l)-1]+$a[ceil($l)-1])/2);
	}
}

//quartile(array,quartile)
//finds the 0 (min), 1st, 2nd (median), 3rd, or 4th (max) quartile of an
//array of numbers.  Calculates using percentiles.
function quartile($a,$q) {
	return percentile($a,$q*25);
}

//TIquartile(array,quartile)
//finds the 0 (min), 1st, 2nd (median), 3rd, or 4th (max) quartile of an
//array of numbers.  Calculates using the TI-84 method.
function TIquartile($a,$q) {
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

//Excelquartile(array,quartile)
//finds the 0 (min), 1st, 2nd (median), 3rd, or 4th (max) quartile of an
//array of numbers.  Calculates using the Excel method.
function Excelquartile($a,$q) {
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
	return Nplus1percentile($a,$q*25);
}

//allquartile(array,quartile)
//finds the 0 (min), 1st, 2nd (median), 3rd, or 4th (max) quartile of an
//array of numbers.  Uses all the quartile methods, and returns an "or" joined
//string of all unique answers.
function allquartile($a,$q) {
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
	return implode(' or ',array_unique($qs));
}

//median(array)
//returns the median of an array of numbers
function median($a) {
	return percentile($a,50);
}

//freqdist(array,label,start,classwidth)
//display macro.  Returns an HTML table that is a frequency distribution of
//the data
// array: array of data values
// label: name of data values
// start: first lower class limit
// classwidth: width of the classes
function freqdist($a,$label,$start,$cw) {
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
function frequency($a,$start,$cw) {
	if ($cw<0) { $cw *= -1;} else if ($cw==0) { echo "Error - classwidth cannot be 0"; return 0;}
	sort($a, SORT_NUMERIC);
	$x = $start;
	$curr = 0;
	while ($x <= $a[count($a)-1]+1e-10) {
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
	$iffunc = create_function('$x','return('.$ifcond.');');

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
	if ($GLOBALS['sessiondata']['graphdisp']==0) {
		return $alt;
	}
	$outst = "setBorder(".(40+7*strlen($maxfreq)).",40,10,5);  initPicture(".($start>0?(max($start-.9*$cw,0)):$start).",$x,0,$maxfreq);";

	$power = floor(log10($maxfreq))-1;
	$base = $maxfreq/pow(10,$power);
	if ($base>75) {$step = 20*pow(10,$power);} else if ($base>40) { $step = 10*pow(10,$power);} else if ($base>20) {$step = 5*pow(10,$power);} else if ($base>9) {$step = 2*pow(10,$power);} else {$step = pow(10,$power);}

	//if ($maxfreq>100) {$step = 20;} else if ($maxfreq > 50) { $step = 10; } else if ($maxfreq > 20) { $step = 5;} else if ($maxfreq>9) { $step = 2; } else {$step=1;}
	if ($startlabel===false) {
		//$outst .= "axes($cw,$step,1,1000,$step); fill=\"blue\"; textabs([". ($width/2+15)  .",0],\"$label\",\"above\");";
		$startlabel = $start;
	} //else {
		$outst .= "axes(1000,$step,1,1000,$step); fill=\"blue\"; textabs([". ($width/2+15)  .",0],\"$label\",\"above\");";
		$x = $startlabel;
		$tm = -.02*$maxfreq;
		$tx = .02*$maxfreq;
		while ($x <= $a[count($a)-1]+1) {
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
	if ($GLOBALS['sessiondata']['graphdisp']==0) {
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
	if ($GLOBALS['sessiondata']['graphdisp']==0) {
		return $alt;
	}
	$x++;
	$topborder = ($valuelabels===false?10:25) + (isset($options['toplabel'])?20:0);
	$leftborder = min(60, 9*strlen($maxfreq)+10) + ($usevertlabel?30:0);
	//$outst = "setBorder(10);  initPicture(". ($start-.1*($x-$start)) .",$x,". (-.1*$maxfreq) .",$maxfreq);";
	$bottomborder = 25+($label===''?0:20);
	$outst = "setBorder($leftborder,$bottomborder,0,$topborder);  initPicture(".($start>0?(max($start-.9*$cw,0)):$start).",$x,0,$maxfreq);";

	$power = floor(log10($maxfreq))-1;
	$base = $maxfreq/pow(10,$power);

	if ($base>75) {$step = 20*pow(10,$power);} else if ($base>40) { $step = 10*pow(10,$power);} else if ($base>20) {$step = 5*pow(10,$power);} else if ($base>9) {$step = 2*pow(10,$power);} else {$step = pow(10,$power);}

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

//piechart(percents, labels, {width, height})
//create a piechart
//percents: array of pie percents (should total 100%)
//labels: array of labels for each pie piece
//uses Google Charts API
function piechart($pcts,$labels,$w=350,$h=150) {
	$out = "<img src=\"http://chart.apis.google.com/chart?cht=p&amp;chd=t:";
	$out .= implode(',',$pcts);
	$out .= "&amp;chs={$w}x{$h}&amp;chl=";
	$out .= implode('|',$labels);
	$out .= '" alt="Pie Chart" />';
	return $out;
}

//normrand(mu,sigma,n, [rnd])
//returns an array of n random numbers that are normally distributed with given
//mean mu and standard deviation sigma.  Uses the Box-Muller transform.
//specify rnd to round to that many digits
function normrand($mu,$sig,$n,$rnd=null) {
	global $RND;
	for ($i=0;$i<ceil($n/2);$i++) {
		do {
			$a = $RND->rand(-32768,32768)/32768;
			$b = $RND->rand(-32768,32768)/32768;
			$r = $a*$a+$b*$b;
			$count++;
		} while ($r==0||$r>1);
		$r = sqrt(-2*log($r)/$r);
		if ($rnd!==null) {
			$z[] = round($sig*$a*$r + $mu, $rnd);
			$z[] = round($sig*$b*$r + $mu, $rnd);
		} else {
			$z[] = $sig*$a*$r + $mu;
			$z[] = $sig*$b*$r + $mu;
		}
	}
	if ($n%2==0) {
		return $z;
	} else {
		return (array_slice($z,0,count($z)-1));
	}
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
			foreach ($a as $v) {
				if ($v<$lfence || $v>$rfence) {
					$outliers[] = $v*1;
				}
			}
			if (count($outliers)>0) {
				if ($lfence>$min) {
					$min = $lfence;
				}
				if ($rfence<$max) {
					$max = $rfence;
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
	if ($GLOBALS['sessiondata']['graphdisp']==0) {
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
function normalcdf($ztest,$dec=4) {
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
	while (abs($ds)>$eps2) {
		$ds = pow(-1,$i)*pow($z,2.0*$i+1.0)/(pow(2.0,$i)*$fact*(2.0*$i+1.0));
		$s += $ds;
		$i++;
		$fact *= $i;
		if (abs($s)<$eps && $i>2) {
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

//tcdf(t,df,[dec])
//calculates the area under the t-distribution with "df" degrees of freedom
//to the left of the t-value t
//based on code from www.math.ucla.edu/~tom/distributions/tDist.html
function tcdf($X, $df, $dec=4) {
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
	$r = ($n*$sxy - $sx*$sy)/(sqrt($n*$sxx-$sx*$sx)*sqrt($n*$syy-$sy*$sy));
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

	if ($line=='') {return array('',makepretty("`$m $var + $b`"));}

	foreach ($_POST as $k=>$v) { //try to catch junk answers
		if ($v==$line) {
			if (preg_match('/[^,\d\.\-]/',$_POST['qn'.substr($k,2).'-vals'])) {
				return array('',makepretty("`$m $var + $b`"));
			}
		}
	}
	$linec = mathphp(makepretty($line),$var);
	$linec = str_replace("($var)",'($t)',$linec);
	$linefunc = create_function('$t','return('.$linec.');');

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
		$yline = $linefunc($x);
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
		$lines = gettwopointlinedata($line,$grid[0],$grid[1],$grid[2],$grid[3]);
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
	return (nCr($N,$x)*pow($p,$x)*pow(1-$p,$N-$x));
}


//binomialcdf(N,p,x)
//Computes the probably of &lt;=x successes out of N trials
//where each trial has probability p of success
function binomialcdf($N,$p,$x) {
	$out = 0;
	for ($i=0;$i<=$x;$i++) {
		$out += binomialpdf($N,$p,$i);
	}
	return $out;
}

//chi2teststat(m)
//Computes the test stat sum((E-O)^2/E) given a matrix of values
function chi2teststat($m) {
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
	return gamma_cdf(0.5*$x,0.0,1.0,0.5*$a);
}

function chicdf($x,$a) {
	return gamma_cdf(0.5*$x,0.0,1.0,0.5*$a);
}

function invchicdf($cdf,$a) {
	return invchi2cdf($cdf,$a);
}

//invchi2cdf(p,df)
//Compuates the x value with left-tail probability p under the
//chi-squared distribution with df degrees of freedom
function invchi2cdf($cdf,$a) {
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

function gamma_cdf($x,$a,$b,$c) {
	return gamma_inc($c,($x-$a)/$b);
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

//fcdf(f,df1,df2)
//Returns the area to right of the F-value f for the f-distribution
//with df1 and df2 degrees of freedom (techinically it's 1-CDF)
//Algorithm is accurate to approximately 4-5 decimals
function fcdf($x,$df1,$df2) {
	$p1 = fcall(fspin($x,$df1,$df2));
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

//invfcdf(p,df1,df2)
//Computes the f-value with probability of p to the right
//with degrees of freedom df1 and df2
//Algorithm is accurate to approximately 2-4 decimal places
//Less accurate for smaller p-values
function invfcdf($p,$df1,$df2) {
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


?>

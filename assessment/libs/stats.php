<?php
//A library of Stats functions.  Version 1.5, April 9, 2007


global $allowedmacros;
array_push($allowedmacros,"nCr","nPr","mean","stdev","percentile","quartile","median","freqdist","frequency","histogram","fdhistogram","normrand","boxplot","normalcdf","tcdf","invnormalcdf","invtcdf","linreg");


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


//percentile(array,percentile)
//example: percentile($a,30) would find the 30th percentile of the data
//method based on Triola
function percentile($a,$p) {
	sort($a, SORT_NUMERIC);
	if ($p==0) {
		return $a[0];
	} else if ($p==100) {
		return $a[count($a)-1];
	}
	$l = $p*count($a)/100;
	if (floor($l)==$l) {
		return (($a[$l-1]+$a[$l])/2);
	} else {
		return ($a[ceil($l)-1]);
	}
}


//quartile(array,quartile)
//finds the 0 (min), 1st, 2nd (median), 3rd, or 4th (max) quartile of an
//array of numbers.  Calculates using percentiles.
function quartile($a,$q) {
	return percentile($a,$q*25);	
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
	sort($a, SORT_NUMERIC);
	$x = $start;
	$curr = 0;
	while ($x <= $a[count($a)-1]) {
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


//histogram(array,label,start,classwidth,[labelstart,upper])
//display macro.  Creates a histogram from a data set
// array: array of data values
// label: name of data values
// start: first lower class limit
// classwidth: width of the classes
// labelstart (optional): value to start axis labeling at.  Defaults to start
// upper (optional): first upper class limit.  Defaults to start+classwidth
function histogram($a,$label,$start,$cw,$startlabel=false,$upper=false) {
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
	$outst = "setBorder(10);  initPicture(". ($start-.1*($x-$start)) .",$x,". (-.1*$maxfreq) .",$maxfreq);";
	if ($maxfreq>100) {$step = 20;} else if ($maxfreq > 50) { $step = 10; } else if ($maxfreq > 20) { $step = 5;} else if ($maxfreq>10) { $step = 2; } else {$step=1;}
	if ($startlabel===false) {
		$outst .= "axes($cw,$step,1,1000,$step); fill=\"blue\"; text([". ($start + .5*($x-$start))  .",". (-.1*$maxfreq) . "],\"$label\");";
	} else {
		$outst .= "axes(1000,$step,1,1000,$step); fill=\"blue\"; text([". ($start + .5*($x-$start))  .",". (-.1*$maxfreq) . "],\"$label\");";
		$x = $startlabel;
		$tm = -.02*$maxfreq;
		$tx = .02*$maxfreq;
		while ($x <= $a[count($a)-1]) {
			$outst .= "line([$x,$tm],[$x,$tx]); text([$x,0],\"$x\",\"below\");";
			$x+= $cw;
		}
		
	}
	$outst .= $st;
	return showasciisvg($outst,300,200);
}


//fdhistogram(freqarray,label,start,cw,[labelstart,upper])
//display macro.  Creates a histogram from frequency array
// freqarray: array of frequencies
// label: name of data values
// start: first lower class limit
// classwidth: width of the classes
// labelstart (optional): value to start axis labeling at.  Defaults to start
// upper (optional): first upper class limit.  Defaults to start+classwidth
function fdhistogram($freq,$label,$start,$cw,$startlabel=false,$upper=false) {
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
	$outst = "setBorder(10);  initPicture(". ($start-.1*($x-$start)) .",$x,". (-.1*$maxfreq) .",$maxfreq);";
	if ($maxfreq>100) {$step = 20;} else if ($maxfreq > 50) { $step = 10; } else if ($maxfreq > 20) { $step = 5;} else {$step=1;}
	if ($startlabel===false) {
		$outst .= "axes($cw,$step,1,1000,$step); fill=\"blue\"; text([". ($start + .5*($x-$start))  .",". (-.1*$maxfreq) . "],\"$label\");";
	} else {
		$outst .= "axes(1000,$step,1,1000,$step); fill=\"blue\"; text([". ($start + .5*($x-$start))  .",". (-.1*$maxfreq) . "],\"$label\");";
		$x = $startlabel;
		$tm = -.02*$maxfreq;
		$tx = .02*$maxfreq;
		for ($curr=0; $curr<count($freq); $curr++) {
			$outst .= "line([$x,$tm],[$x,$tx]); text([$x,0],\"$x\",\"below\");";
			$x+=$cw;
		}
	}
	//$outst .= "axes($cw,$step,1,1000,$step); fill=\"blue\"; text([". ($start + .5*($x-$start))  .",". (-.1*$maxfreq) . "],\"$label\");";
	$outst .= $st;
	return showasciisvg($outst,300,200);
}


//normrand(mu,sigma,n)
//returns an array of n random numbers that are normally distributed with given
//mean mu and standard deviation sigma.  Uses the Box-Muller transform.
function normrand($mu,$sig,$n) {
	for ($i=0;$i<ceil($n/2);$i++) {
		do {
			$a = rand(-32768,32768)/32768;
			$b = rand(-32768,32768)/32768;
			$r = $a*$a+$b*$b;
			$count++;
		} while ($r==0||$r>1);
		$r = sqrt(-2*log($r)/$r); 
		$z[] = $sig*$a*$r + $mu;
		$z[] = $sig*$b*$r + $mu;
	}
	if ($n%2==0) {
		return $z;
	} else {
		return (array_slice($z,0,count($z)-1));
	}
}


//boxplot(array,axislabel,[datalabel])
//draws a boxplot based on the data in array, with given axislabel
//and optionally a datalabel (to topleft of boxplot)
//array and datalabel can also be an array of dataarrays and
//array of datalabels to do comparative boxplots
function boxplot($arr,$label) {
	if (func_num_args()>2) {
		$dlbls = func_get_arg(2);
	}
	if (is_array($arr[0])) { $multi = count($arr);} else {$multi = 1;}
	$st = '';
	$bigmax = -10000000;
	$bigmin = 100000000;
	$alt = "Boxplot, axis label: $label. ";
	for ($i=0;$i<$multi;$i++) {
		if ($multi==1) { $a = $arr;} else {$a = $arr[$i];}
		sort($a,SORT_NUMERIC);
		$max = $a[count($a)-1];
		if ($max>$bigmax) { $bigmax = $max;}
		if ($a[0]<$bigmin) {$bigmin = $a[0];}
		$q1 = percentile($a,25);
		$q2 = percentile($a,50);
		$q3 = percentile($a,75);
		$yl = 2+5*$i;
		$ym = $yl+1;
		$yh = $ym+1;
		$st .= "line([$a[0],$yl],[$a[0],$yh]); rect([$q1,$yl],[$q3,$yh]); line([$q2,$yl],[$q2,$yh]);";
		$st .= "line([$max,$yl],[$max,$yh]); line([$a[0],$ym],[$q1,$ym]); line([$q3,$ym],[$max,$ym]);";
		$alt .= 'Boxplot ';
		if (isset($dlbls[$i])) {
			$alt .= "for {$dlbls[$i]}";
		} else {
			$alt .= ($i+1);
		}
		$alt .= ": Left whisker at {$a[0]}. Leftside of box at $q1. Line through box at $q2.  Rightside of box at $q3. Right whisker at $max.\n"; 
		
	}
	if ($GLOBALS['sessiondata']['graphdisp']==0) {
		return $alt;
	}
	$outst = "setBorder(15); initPicture($bigmin,$bigmax,-3,".(5*$multi).");";
	$dw = $bigmax-$bigmin;

	if ($dw>100) {$step = 20;} else if ($dw > 50) { $step = 10; } else if ($dw > 20) { $step = 5;} else {$step=1;}
	$outst .= "axes($step,100,1);";
	$outst .= "text([". ($bigmin+.5*$dw) . ",-3],\"$label\");";
	if (isset($dlbls)) {
		for ($i=0;$i<$multi;$i++) {
			if ($multi>1) { $dlbl = $dlbls[$i];} else {$dlbl = $dlbls;}
			$st .= "text([$bigmin,". (5+5*$i)  ."],\"$dlbl\",\"right\");";
		}
	}
	$outst .= $st;
	return showasciisvg($outst,400,50+50*$multi);
}


//normalcdf(z)
//calculates the area under the standard normal distribution to the left of the
//z-value z, to 4 decimals 
//based on someone else's code - can't remember whose!
function normalcdf($ztest) {
	$ds = 1;
	$s = 0;
	$i = 0;
	$z = abs($ztest);
	$fact = 1;
	while (abs($ds)>.000001) {
		$ds = pow(-1,$i)*pow($z,2.0*$i+1.0)/(pow(2.0,$i)*$fact*(2.0*$i+1.0));
		$s += $ds;
		$i++;
		$fact *= $i;
		if (abs($s)<0.0001) {
			break;
		}
	}
	
	$s *= 0.39894228;
	$s = round($s,4);
	if ($ztest > 0) {
		$pval = .5 + $s;
	} else {
		$pval = .5 - $s;
	}
	if ($pval < .0001) {
		$pval = .0001;
	} else if ($pval > .9999) {
		$pval = .9999;
	}
	return $pval;
}


//tcdf(t,df)
//calculates the area under the t-distribution with "df" degrees of freedom
//to the left of the t-value t
//based on someone else's code - can't remember whose!
function tcdf($ttest,$df) {
	$t = abs($ttest);
	if ($df > 0) {
	$k3 = 0;
	$c3 = 0;
	$a3 = $t/sqrt($df);
	$b3 = 1+pow($a3,2);
	$y = 0.5;
	if (abs(floor($df/2)*2 - $df) < .0001) { 
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
		$pval = round($y,4);
	} else {
		$pval = round(1-$y,4);
	}


	if ($pval > .9999) {
		$pval = 0.9999;
	} else if ($pval < .0001) {
		$pval = .0001;
	}
	return $pval;
	} else {return false;}
}




//invnormalcdf(p)
//Inverse Normal CDF
//finds the z-value with a left-tail area of p
// from Odeh & Evans. 1974. AS 70. Applied Statistics. 23: 96-97
function invnormalcdf($p) {
   
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
   $xp = round($xp,5);
   if ($p < 0.5) { return (-1*$xp); }  else { return  $xp; }
}
   
   
//invtcdf(p,df)
//the inverse Student's t-distribution 
//computes the t-value with a left-tail probability of p, with df degrees of freedom
// from Algorithm 396: Student's t-quantiles by G.W. Hill  Comm. A.C.M., vol.13(10), 619-620, October 1970
function invtcdf($p,$ndf) {
	$half_pi = M_PI/2;
	$eps = 1e-5;
	
	if ($ndf < 1 || $p > 1 || $p <= 0) {
	  echo "error in params";
	  return false;
	}
	
	if ( abs($ndf - 2) < $eps ) {
	  $fn_val = SQRT(2 / ( 2*$p * (2 - 2*$p) ) - 2);
	  if ($p<.5) {return (-1*$fn_val);} else { return $fn_val;}
	} else if ($ndf < 1+$eps) {
	  $prob = 2*$p * $half_pi;
	  $fn_val = cos($prob) / sin($prob);
	  if ($p<.5) {return (-1*$fn_val);} else { return $fn_val;}
	} else {
	  $a = 1/ ($ndf - 0.5);
	  $b = 48/ pow($a,2);
	  $c = ((20700 * $a / $b - 98) * $a - 16) * $a + 96.36;
	  $d = ((94.5 / ($b + $c) - 3) / $b + 1) * sqrt($a * $half_pi)* $ndf;
	  $x = $d * $p;
	  $y = pow($x,(2/ $ndf));
	  
	  if ($y > 0.05 + $a) {
	    $x = invnormalcdf($p);
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
	$fn_val = round( sqrt($ndf * $y) , 4);
	if ($p<.5) {return (-1*$fn_val);} else { return $fn_val;}
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


?>

<?php
//A smattering of macros for evaluating complicated functions.  Version 1.0 Oct 4 2009

global $allowedmacros;
array_push($allowedmacros,"fe_lpfnc");

//fe_lpfnc(x,xarray,yarray,[periodic])
//evals a linear piecewise function (connect-the-dots) from the provided
//array of x and y values at the given x value.
//requires x array to be in increasing order
//If periodic=true, then xarray values define the domain of one period, 
//and if x is outside that domain, f(x) = f(x-p) will be used
//domain: starting x <= x < last x, so make sure last x = x[0]+p
function fe_lpfnc($x,$xarray,$yarray,$p=false) {
	$c = count($xarray);
	if ($p == true) {
		$per = $xarray[$c-1] - $xarray[0];
		if ($x < $xarray[0]) {
		 	$x = $x + $per*ceil(($xarray[0]-$x)/$per);
		} else if ($x >= $xarray[$c-1]) {
			$x = $x - $per*(ceil(($x-$xarray[$c-1])/$per + 1e-12));
		}
	}
	/*if ($p>0 && $x>$p) {
		$x = $x - $p*floor($x/$p);
	}
	if ($p>0 && $x<0) {
		$x = $x + $p*ceil(-$x/$p);
		echo $x;
	}
	*/
	if ($x<$xarray[0]) {
		echo "Error - input x is below provided xarray domain";
		return null;
	}
	for ($i=0; $i<$c; $i++) {
		if ($x<=$xarray[$i]) {
			break;
		}
	}
	if ($i==count($xarray)) {
		echo "Error - input x is above provided xarray domain";
		return null;
	}
	if ($i==0) { 
		return $yarray[0]; 
	} else {
		$m = ($yarray[$i] - $yarray[$i-1]) /  ($xarray[$i] - $xarray[$i-1]);
		return ($m*($x - $xarray[$i]) + $yarray[$i]);
	}
}


<?php
//A collection of numerical calculus routines.  These are intended to do
//numerical calculations where they are specifically needed, not substitute
//for entering symbolic derivatives or integrals.
//
//Version 1.1 April 8, 2009

global $allowedmacros;
array_push($allowedmacros,"calculusdiffquotient","calculusnumint");

//calculusdiffquotient(function,variable,a,h)
//calculated the difference quotient (f(a+h)-f(a))/h
function calculusdiffquotient($func,$var,$ainputval,$hval) {
	$func = makeMathFunction(makepretty($func), $var);
	if ($func===false) { return 0;}
	$fah = $func([$var => $ainputval + $hval]);
	$fa = $func([$var => $ainputval]);
	return (($fah - $fa)/$hval);
}

//calculusnumint(func,var,a,b,n,method)
//do a numerical integral approximation on interval [a,b] with
//n subdivisions using specified method
//method = left, right, midpoint, trapezoidal, or simpsons
function calculusnumint($func,$var,$a,$b,$n,$method) {
	if ($n<=0) {
		echo "invalid params to calculusnumint";
		return false;
	}
	if ($method=='simpsons' && $n%2!=0) {
		echo "simpsons requires even n";
		return false;
	}
    if ($a == $b) {
        return 0;
    }
	$func = makeMathFunction(makepretty($func), $var);
	if ($func===false) { return 0;}
	$dx = ($b-$a)/$n;
	if (($method=='right' && $a < $b) || ($method=='left' && $b < $a)) {
		$x = $a+$dx;
	} else if ($method=='midpoint') {
		$x = $a+.5*$dx;
	} else {
		$x = $a;
	}
	if ($method=='left' || $method=='right' || $method=='midpoint') {
		$ntodo = $n;
	} else {
		$ntodo = $n+1;
	}

	$out = 0;
	$mult = 1;
	for ($i=0; $i<$ntodo; $i++) {
		if ($method=='trapezoidal') {
			if ($i==0 || $i==$n) {
				$mult = 1;
			} else {
				$mult = 2;
			}
		} else if ($method=='simpsons') {
			if ($i==0 || $i==$n) {
				$mult = 1;
			} else if ($i%2==0) {
				$mult = 2;
			} else {
				$mult = 4;
			}
		}
		$out += $mult*$func([$var=>$x]);
		$x += $dx;
	}
	if ($method=='trapezoidal') {
		return ($out*$dx/2);
	} else if ($method=='simpsons') {
		return ($out*$dx/3);
	} else {
		return ($out*$dx);
	}
}

?>

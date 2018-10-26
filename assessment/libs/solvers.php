<?php
//Some solving algorithms.  Version 1.1 March 25, 2014
//You should ALMOST NEVER need to use these - in most cases it is better
//to construct an equation from a known solution

global $allowedmacros;
array_push($allowedmacros, "discretenewtons", "bisectionsolve");

//bisectionsolve(function, xmin, xmax)
//applies bisection to find a root of hte function on the interval [xmin, xmax]
//this function only works if there is exactly one root in the interval.
function bisectionsolve($func, $xmin, $xmax) {
	if ($xmin >= $xmax) {
		echo "error: need $xmin < $xmax";
		return null;
	}
	$left = $xmin;  $right = $xmax;
	
	$func = makepretty($func);
	$func = mathphp($func,"x");
	$func = str_replace("(x)",'($x)',$func);
	$f = my_create_function('$x','return ('.$func.');');
	$fleft = $f($left);
	if ($fleft*$f($right)>0) {
		echo "error: function is same sign at both endpoints";
		return null;
	}
	
	do {
		$x = ($left + $right)/2;
		$y = $f($x);
		if ($fleft*$y>0) { //same sign as fleft - $x becomes new left
			$left = $x;
		} else {
			$right = $x;
		}
	} while (abs($left - $right)>1e-7);
	
	return $x;
}
	
//discretenewtons(function,xmin,xmax,[guess]) 
//applies a discrete form of Newton's method to find a root of the function
//on the interval [xmin,xmax].  Starts at midpoint of interval, or at guess if
//provided.  If method fails to converge, or converges outside interval, the
//method returns null.  Make sure function uses the variable x
function discretenewtons($func,$xmin,$xmax,$guess=null) {
	$func = makepretty($func);
	$func = mathphp($func,"x");
	$func = str_replace("(x)",'($x)',$func);
	if ($guess==null) {
		$guess = ($xmin+$xmax)/2;
	}
	$cnt = 0;
	$eps = 1e-7;
	$x = $guess;
	$curx = $guess;
	$dx = min(($xmax-$xmin)/100,.001);
	$y = eval("return ($func);");
	while (abs($y)>$eps && $cnt<20) {
		$x = $curx + $dx;
		$ny = eval("return ($func);");
		$m = ($ny - $y)/$dx;
		$curx = $curx - $y/$m;
		$x = $curx;
		$y = eval("return ($func);");
		$cnt++;
	}
	//echo "N cnt: $cnt. ";
	
	if ($cnt==20) {
		if ($x>$xmax || $x<$xmin) {
			echo "Newton's did not converge within interval";
		} else {
			echo "Newton's did not acheive good accuracy";
		}
		return null;
	} else {
		if ($x>$xmax || $x<$xmin) {
			echo "Newton's did not converge within interval";
			return null;
		} else {
			return $curx;
		}
	}
	
}

?>

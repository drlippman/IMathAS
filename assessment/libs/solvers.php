<?php
//Some solving algorithms.  Version 1.0 Oct 6, 2009

global $allowedmacros;
array_push($allowedmacros, "discretenewtons");


	
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

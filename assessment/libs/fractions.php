<?php
//Fraction operations library

global $allowedmacros;
array_push($allowedmacros, "fractionrand", "fractiondiffdrands", "fractiondiffdrandsfrom", "fractionparse", "fractiontomixed", "fractiontodecimal", "fractionadd","fractionsubtract","fractionmultiply","fractiondivide","fractionreduce","fractionneg","fractionpower", "fractionroot");

//fractionrand(denom)
//returns a proper reduced fraction with the given denominator
function fractionrand($d, $returnval = false) {
	global $RND;
	$goodnum = array('4'=>array(1,3), '6'=>array(1,5), '8'=>array(1,3,5,7), '10'=>array(1,3,7,9), '12'=>array(1,5,7,11));
	$d = round($d);
	if ($d<1) {
		echo "denominator must be larger than 1";
		return "";
	}
	if (isset($goodnum[$d])) {
		$n = randfrom($goodnum[$d]);
	} else {
		do {
			$n = $RND->rand(1,$d-1);
		} while (gcd($d,$n)!=1);
	}
	if ($returnval) {
		return ["$n/$d", $n/$d];
	} else {
		return "$n/$d";
	}
}

//fractiondiffdrands(min,max,n) {
//returns n fractions with different denominators from min to max
function fractiondiffdrands($min,$max,$n, $order = 'any') {
	$ds = diffrands($min,$max,$n);
	$out = array();
	$valout = array();
	foreach ($ds as $d) {
		list($out[], $valout[]) = fractionrand($d, true);
	}
	if ($order == 'inc') {
		asort($valout);
	} else if ($order == 'dec') {
		arsort($valout);
	}
	if ($order == 'inc' || $order == 'dec') {
		$outsorted = [];
		foreach ($valout as $k=>$v) {
			$outsorted[] = $out[$k];
		}
		return $outsorted;
	} else {
		return $out;
	}
}

//fractiondiffdrandsfrom(list,n) {
//returns n fractions with different denominators from the list
function fractiondiffdrandsfrom($list,$n, $order = 'any') {
	$ds = diffrandsfrom($list,$n);
	$out = array();
	$valout = array();
	foreach ($ds as $d) {
		list($out[], $valout[]) = fractionrand($d, true);
	}
	if ($order == 'inc') {
		asort($valout);
	} else if ($order == 'dec') {
		arsort($valout);
	}
	if ($order == 'inc' || $order == 'dec') {
		$outsorted = [];
		foreach ($valout as $k=>$v) {
			$outsorted[] = $out[$k];
		}
		return $outsorted;
	} else {
		return $out;
	}
}

//fractionparse(fraction)
//converts a fraction into an array(num, denom)
function fractionparse($f) {
	if (is_array($f)) {return $f;}
	$p = array_map('trim', explode('/',$f));
	if (trim($p[0])=='') {
		if ($GLOBALS['myrights']>10) {
			echo "Error in fraction: undefined numerator";
		}
		$p[0]=0;
	}
	if (count($p)==1) {
		return array($p[0],1);
	} else {
		if (trim($p[1])=='') {
			if ($GLOBALS['myrights']>10) {
				echo "Error in fraction: undefined denominator";
			}
			$p[1]=1;
		}
		$wp = array_map('trim', explode(' ',$p[0]));
		if (count($wp)==1) {
			return array($p[0],$p[1]);
		} else {
			if ($wp[0]<0) {
				return array($wp[0]*$p[1] - $wp[1], $p[1]);
			} else {
				return array($wp[0]*$p[1] + $wp[1], $p[1]);
			}
		}
	}
}

//fractiontomixed(f)
//converts a fraction string to a mixed number string
function fractiontomixed($fp) {
	if (!is_array($fp)) {
		$fp = fractionparse($fp);
	}
	if ($fp[1]<0) {
		$fp[1] *= -1;
		$fp[0] *= -1;
	}
	if ($fp[1]==1) {
		return $fp[0];
	} else if (abs($fp[0])>abs($fp[1])) {
		$w = floor(abs($fp[0])/abs($fp[1]));
		if ($fp[0]<0) { $w *= -1;}
		$fp[0] -= $w*$fp[1];
		return $w.' '.abs($fp[0]).'/'.$fp[1];
	} else {
		return $fp[0].'/'.$fp[1];
	}
}

//fractiontodecimal(f)
function fractiontodecimal($f) {
    if ($f === '') { return ''; } // bypass on empty values
    else if (is_numeric($f)) { return $f; } // don't parse if already a number
	if (!is_array($f)) {
		$f = fractionparse($f);
	}
	if ($f[1]==0) {
		if ($GLOBALS['myrights']>10) {
			echo "Error: fraction with denominator 0";
		}
		return 0;
	} else if (!is_numeric($f[0])) {
        return $f;
    }
	return $f[0]/$f[1];
}

//fractionreduce(f)
//takes a fraction string or array(num,denom)
//returns a reduced fraction string
function fractionreduce() {
	$args = func_get_args();
	$retarr = false;
	if (count($args)==1) {
		if (is_array($args[0])) {
			$f = $args[0];
		} else {
			$f = fractionparse($args[0]);
		}
	} else if (count($args)==2 && is_array($args[0])) {
		$f = $args[0];
		$retarr = $args[1];
	} else if (count($args)==2) {
		$f = array($args[0],$args[1]);
	} else if (count($args)==3) {
		$f = array($args[0],$args[1]);
		$retarr = $args[2];
	}
	$g = gcd($f[0],$f[1]);
	$f[0] /= $g;
	$f[1] /= $g;
	if ($f[1]<0) {
		$f[0] *= -1;
		$f[1] *= -1;
	}
	if ($retarr) {
		return $f;
	}
	if ($f[1]==1) {
		return $f[0];
	} else {
		return $f[0].'/'.$f[1];
	}
}

//fractionadd(f1,f2,[f3,f4,...])
//adds a set of fractions, returning a reduced fraction sum
function fractionadd() {
	$args = func_get_args();
	if (count($args)<2) {
		echo "fractionadd requires at least 2 inputs";
		return "";
	}
	$fracarr = false;
	if (is_array($args[0])) {
		$fracarr = true;
	}
	$fracs = array();
	$commondenom = 1;
	foreach($args as $f) {
		if (!is_array($f)) {
			$f = fractionparse($f);
		}
		$fracs[] = $f;
		$commondenom *= $f[1];
	}
	$num = 0;
	foreach ($fracs as $f) {
		$num += round($commondenom/$f[1],0)*$f[0];
	}
	return fractionreduce($num,$commondenom,$fracarr);
}

//fractionsubtract(f1,f2,[f3,f4,...])
//subtract a set of fractions, returning a reduced fraction difference
//f1 - f2 - f3 - f4 etc.
function fractionsubtract() {
	$args = func_get_args();
	if (count($args)<2) {
		echo "fractionsubtract requires at least 2 inputs";
		return "";
	}
	$fracarr = false;
	if (is_array($args[0])) {
		$fracarr = true;
	}
	$fracs = array();
	$commondenom = 1;
	foreach($args as $f) {
		if (!is_array($f)) {
			$f = fractionparse($f);
		}
		$fracs[] = $f;
		$commondenom *= $f[1];
	}
	$num = 0;
	foreach ($fracs as $i=>$f) {
		if ($i==0) {
			$num += round($commondenom/$f[1],0)*$f[0];
		} else {
			$num -= round($commondenom/$f[1],0)*$f[0];
		}
	}
	return fractionreduce($num,$commondenom,$fracarr);
}

//fractionmultiply(f1,f2,[f3,f4,...])
//multiply a set of fractions, returning a reduced fraction
function fractionmultiply() {
	$args = func_get_args();
	if (count($args)<2) {
		echo "fractionmultiply requires at least 2 inputs";
		return "";
	}
	$fracarr = false;
	if (is_array($args[0])) {
		$fracarr = true;
	}
	$fracs = array();
	$num = 1; $denom = 1;
	foreach($args as $f) {
		if (!is_array($f)) {
			$f = fractionparse($f);
		}
		$num *= $f[0];
		$denom *= $f[1];
	}
	return fractionreduce($num,$denom,$fracarr);
}

//fractiondivide(f1,f2,[f3,f4,...])
//divide a set of fractions, returning a reduced fraction
//f1 -: f2 -: f3 -: f4
function fractiondivide() {
	$args = func_get_args();
	if (count($args)<2) {
		echo "fractiondivide requires at least 2 inputs";
		return "";
	}
	$fracarr = false;
	if (is_array($args[0])) {
		$fracarr = true;
	}
	$fracs = array();
	$num = 1; $denom = 1;
	foreach($args as $i=>$f) {
		if (!is_array($f)) {
			$f = fractionparse($f);
		}
		if ($i==0) {
			$num *= $f[0];
			$denom *= $f[1];
		} else {
			$num *= $f[1];
			$denom *= $f[0];
		}
	}
	return fractionreduce($num,$denom,$fracarr);
}

//fractionneg(frac)
//change the sign of a fraction
function fractionneg($f) {
    $fracarr = false;
	if (!is_array($f)) {
		$f = fractionparse($f);
	} else {
		$fracarr = true;
	}
	return fractionreduce(-1*$f[0],$f[1],$fracarr);
}

//fractionpower(frac, power)
//raises a fraction to a power
function fractionpower($f,$p) {
    $fracarr = false;
	if (!is_array($f)) {
		$f = fractionparse($f);
	} else {
		$fracarr = true;
	}
	
	$num = pow($f[0], $p);
	$denom = pow($f[1], $p);
	return fractionreduce($num,$denom,$fracarr);
}

//fractionroot(frac, [root])
//finds the root of the fraction (defaults to square root), rationalizing
//the denominator.  The output is a string.
function fractionroot($f,$root=2) {
	if (!is_array($f)) {
		$f = fractionparse($f);
	}
	include_once("radicals.php");
	return reduceradicalfrac(1, $f[0]*$f[1], $f[1]);
}


?>

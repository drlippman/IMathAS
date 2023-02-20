<?php
//Polynomial functions.  Version 1.1, Nov 11, 2007

global $allowedmacros;

// COMMENT OUT BEFORE UPLOADING
if(!is_array($allowedmacros)) {
	$allowedmacros = array();
}

array_push($allowedmacros,"formpoly","formpolyfromroots","writepoly","addpolys","subtpolys","multpolys","scalepoly","roundpoly","quadroot","getcoef","polypower","checkpolypowerorder","derivepoly");


//formpoly(coefficients,powers or degree)
//Creates a polynomial object
//Use writepoly to create a display form of the polynomial
//coefficients: list/array of coefficients
//powers or degree: list/array of powers, or highest degree
//highest degree assumes coefficients correspond to consecutively
//decreasing powers
function formpoly($coef,$deg) {
	$poly = array();
	if (!is_array($coef)) {
		$coef = explode(',',$coef);
	}
	if (is_array($deg) || strpos($deg,',')!=false) {
		if (!is_array($deg)) {
			$deg = explode(',',$deg);
		}
        if (count($coef) != count($deg)) {
            echo "formpoly: coef and deg should have equal lengths";
        }
        $max = max(array_keys($coef));
		for ($i=0;$i<=$max;$i++) {
            if (!isset($coef[$i]) || !isset($deg[$i])) { continue; }
			$poly[$i][0] = $coef[$i]*1;
			$poly[$i][1] = $deg[$i];
		}
	} else {
        $max = max(array_keys($coef));
		for ($i=0;$i<=$max;$i++) {
            if (!isset($coef[$i])) { $deg--; continue; }
			$poly[$i][0] = $coef[$i]*1;
			$poly[$i][1] = $deg;
			$deg--;
		}
	}
	return $poly;
}

//formpolyfromroots(stretch,roots,[multiplicities])
//create a polynomial object from roots
//use writepoly to create a display form of the polynomial
//stretch:  a stretch factor; the A in A*(x-root)(x-root)...
//roots: an array of the roots (zeros, x-intercepts) of the polynomial
//       for a complex root pair a+-bi give a single array [a,b] as the root.
//multiplicites (optional): an array of multiplicites of the roots.  Assumed to
//  be all 1 if not provided
function formpolyfromroots($a,$roots,$mult=1) {
    if (count($roots)==0) { return [[$a,0]]; }
	for($i=0; $i<count($roots); $i++) {
        if (is_array($roots[$i])) { // complex a+bi as [a,b]
            $newpoly = formpoly(array(1,-2*$roots[$i][0],($roots[$i][0])**2 + ($roots[$i][1])**2),2);
        } else {
            $newpoly = formpoly(array(1,-1*$roots[$i]),1);
        }
		if (is_array($mult) && $mult[$i]>1) {
			$newpoly = polypower($newpoly,$mult[$i]);
		}
		if ($i==0) {
			$outpoly = $newpoly;
		} else {
			$outpoly = multpolys($outpoly,$newpoly);
		}
	}
	$outpoly = scalepoly($outpoly,$a);
	return $outpoly;
}

//writepoly(poly,[var,showzeros])
//Creates a display form for polynomial object
//poly: polynomial object, created with formpoly
//var: input variable.  Defaults to x
//showzeros:  optional, defaults to false.  If true, shows zero coefficients
function writepoly($poly,$var="x",$sz=false) {
	$po = '';
    $first = true;
    foreach ($poly as $p) {
		if (!$sz && $p[0]==0) {continue;}
		if (!$first) {
			if ($p[0]<0) {
				$po .= ' - ';
			} else {
				$po .= ' + ';
			}
		} else {
			if ($p[0]<0) {
				$po .= ' - ';
			}
		}
		if (abs($p[0])!=1 || $p[1]==0) {
			$po .= abs($p[0]);
		}
		if ($p[1]>1) {
			$po .= " $var^". $p[1];
		} else if ($p[1]>0) {
			$po .= " $var";
		}
		$first = false;
	}
	return $po;
}

//roundpoly(poly, d)
//rounds the coefficients in the poly to d places
function roundpoly($poly, $d) {
	for ($i=0;$i<count($poly);$i++) {
		$poly[$i][0] = round($poly[$i][0],$d);
	}
	return $poly;
}

//addpolys(poly1,poly2)
//Adds polynomials, arranging terms from highest to lowest powers
function addpolys($p1,$p2) {
	$p = array();
	for ($i=0;$i<count($p1);$i++) {
		$p[$p1[$i][1]]= $p1[$i][0];
	}
	for ($i=0;$i<count($p2);$i++) {
		if (isset($p[$p2[$i][1]])) {
			$p[$p2[$i][1]] += $p2[$i][0];
		} else {
			$p[$p2[$i][1]] = $p2[$i][0];
		}
	}
	krsort($p);
	$po = array();
	$i = 0;
	foreach($p as $deg=>$coef) {
		$po[$i][0] = $coef;
		$po[$i][1] = $deg;
		$i++;
	}
	return $po;
}


//subtpolys(poly1,poly2)
//Subtracts polynomials: poly1-poly2, arranging terms from highest to lowest powers
function subtpolys($p1,$p2) {
	$p = array();
	for ($i=0;$i<count($p1);$i++) {
		$p[$p1[$i][1]]= $p1[$i][0];
	}
	for ($i=0;$i<count($p2);$i++) {
		if (isset($p[$p2[$i][1]])) {
			$p[$p2[$i][1]] = $p[$p2[$i][1]] - $p2[$i][0];
		} else {
			$p[$p2[$i][1]] = -1*$p2[$i][0];
		}
	}
	krsort($p);
	$po = array();
	$i = 0;
	foreach($p as $deg=>$coef) {
		$po[$i][0] = $coef;
		$po[$i][1] = $deg;
		$i++;
	}
	return $po;
}


//multpolys(poly1,poly2)
//Multiplies polynomials
function multpolys($p1,$p2) {
	$p = array();
	for ($i=0;$i<count($p1);$i++) {
		for ($j=0;$j<count($p2);$j++) {
			$newdeg = $p1[$i][1] + $p2[$j][1];
			$newcoef = $p1[$i][0]*$p2[$j][0];
			if (isset($p[$newdeg])) {
				$p[$newdeg] += $newcoef;
			} else {
				$p[$newdeg] = $newcoef;
			}
		}
	}
	krsort($p);
	$po = array();
	$i = 0;
	foreach($p as $deg=>$coef) {
		$po[$i][0] = $coef;
		$po[$i][1] = $deg;
		$i++;
	}
	return $po;
}

//scalepoly(poly,c)
//Multiplies each term of poly by constant c
function scalepoly($poly,$c) {
	for ($i=0; $i<count($poly); $i++) {
		$poly[$i][0] *= $c;
	}
	for ($i=0; $i<count($poly); $i++) {
		$poly[$i][0] = round($poly[$i][0],10);
	}
	return $poly;
}

//polypower(poly,power)
//Calculates poly^power
function polypower($p,$pow) {
	$op = $p;
	for ($i=1;$i<$pow;$i++) {
		$op = multpolys($op,$p);
	}
	return $op;
}


//quadroot(a,b,c)
//Quadratic equation, solving ax^2+bx+c = 0
//Return an array of the two solutions, ordered smaller then larger
//if no solution exists, an array of "DNE" strings is returned
function quadroot($a,$b,$c) {
	$disc = $b*$b - 4*$a*$c;
	if ($disc<0) {
		return (array("DNE","DNE"));
	} else {
		$x1 = (-1*$b + sqrt($disc))/(2*$a);
		$x2 = (-1*$b - sqrt($disc))/(2*$a);
		$mn = min($x1,$x2);
		$mx = max($x1,$x2);

		return (array($mn,$mx));
	}
}


//getcoef(poly,degree)
//Gets the coefficient corresponding to the degree specified
//if no such term is defined, 0 is returned (since that is the coefficient!)
//poly: polynomial object, created with formpoly
//degree: degree of term to get coefficient of
function getcoef($p,$deg) {
	$coef = 0;
	for ($i=0;$i<count($p);$i++) {
		if ($p[$i][1]==$deg) {
			$coef = $p[$i][0];
			break;
		}
	}
	return $coef;
}

//checkpolypowerorder(polystring,[order])
//checks to make sure the degree order of polynomial powers is decreasing
//set order='inc' to instead check if they're increasing
//only works for integer powers
//typical use would be:
// $correctorder = checkpolypowerorder($stuanswers[$thisq])
// $answer = $stuanswers[$thisq] + 1000 if (!$correctorder) //forces a wrong answer
function checkpolypowerorder($p,$ord='dec') {
	if ($p=='' || $p==null) {return false;}
	$pp = preg_split('/[+\-]/',$p);
	$lastpower = ($ord=='dec')?100000000:-10000000;
	for ($i=0;$i<count($pp);$i++) {
		if (($p=strpos($pp[$i],'^'))===false) {
			if (!is_numeric($pp[$i])) {
				$pow = 1;
			} else {
				$pow = 0;
			}
		} else {
			$pow = intval(str_replace(array('(',')'),'',substr($pp[$i],$p+1)));
		}
		if ($ord=='dec' && $pow>$lastpower) { return false;}
		else if ($ord=='inc' && $pow<$lastpower) { return false;}
		$lastpower = $pow;
	}
	return true;
}

// derivative of polynomial
function derivepoly($p) {
	$out = array();
	$j=-1; // this is to prevent index skipping - MPJ 2/28/2021
	for ($i=0;$i<count($p);$i++) {
		if ($p[$i][1] == 0) { continue; }
		$j++;
		$out[$j] = array();
		$out[$j][0] = $p[$i][0]*$p[$i][1];
		$out[$j][1] = $p[$i][1]-1;
    }
	return $out;
}

?>

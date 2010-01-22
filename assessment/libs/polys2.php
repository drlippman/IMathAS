<?php
//Polynomial functions with two variables, like 2x^2+3xy+4y^2.  Version 1.0, Jan 21, 2010


global $allowedmacros;
array_push($allowedmacros,"formpoly2","writepoly2","addpolys2","subtpolys2","multpolys2","scalepoly2","getcoef2","polypower2");


//formpoly2(coefficients,powers,powers2)
//Creates a polynomial object with two variables
//Use writepoly to create a display form of the polynomial
//coefficients: list/array of coefficients
//powers : list/array of powers for first variable
//power2: list/array of power for the second variable
function formpoly2($coef,$deg,$deg2) {
	$poly = array();
	if (!is_array($coef)) {
		$coef = explode(',',$coef);
	}
	
	if (!is_array($deg)) {
		$deg = explode(',',$deg);
	}
	if (!is_array($deg2)) {
		$deg2 = explode(',',$deg2);
	}
	for ($i=0;$i<min(count($deg),count($coef));$i++) {
		$poly[$i][0] = $coef[$i]*1;
		$poly[$i][1] = $deg[$i];
		$poly[$i][2] = $deg2[$i];
	}	
	return $poly;
}

//writepoly2(poly,[var,var2,showzeros])
//Creates a display form for polynomial object
//poly: polynomial object, created with formpoly
//var: first variable, defaults to x
//var2: second variable, defaults to y
//showzeros:  optional, defaults to false.  If true, shows zero coefficients
function writepoly2($poly,$var='x',$vv='y',$sz=false) {
	$po = '';
	$first = true;
	for ($i=0;$i<count($poly);$i++) {
		if (!$sz && $poly[$i][0]==0) {continue;}
		if (!$first) {
			if ($poly[$i][0]<0) {
				$po .= ' - ';
			} else {
				$po .= ' + ';
			}
		} else {
			if ($poly[$i][0]<0) {
				$po .= ' - ';
			}
		}
		if (abs($poly[$i][0])!=1 || ($poly[$i][1]==0 && $poly[$i][2]==0)) {
			$po .= abs($poly[$i][0]);
		}
		if ($poly[$i][1]>1) {
			$po .= " $var^". $poly[$i][1];
		} else if ($poly[$i][1]>0) {
			$po .= " $var";
		}
		if ($poly[$i][2]>1) {
			$po .= " $vv^". $poly[$i][2];
		} else if ($poly[$i][2]>0) {
			$po .= " $vv";
		}
		$first = false;
	}
	return $po;
}


//addpolys2(poly1,poly2)
//Adds polynomials, arranging terms from highest to lowest powers
function addpolys2($p1,$p2) {
	$p = array();
	for ($i=0;$i<count($p1);$i++) {
		$p[$p1[$i][1].','.$p1[$i][2]]= $p1[$i][0];
	}
	for ($i=0;$i<count($p2);$i++) {
		if (isset($p[$p2[$i][1].','.$p2[$i][2]])) {
			$p[$p2[$i][1].','.$p2[$i][2]] += $p2[$i][0];
		} else {
			$p[$p2[$i][1].','.$p2[$i][2]] = $p2[$i][0];
		}
	}
	krsort($p);
	$po = array();
	$i = 0;
	foreach($p as $deg=>$coef) {
		$deg = explode(',',$deg);
		$po[$i][0] = $coef;
		$po[$i][1] = $deg[0];
		$po[$i][2] = $deg[1];
		$i++;
	}
	return $po;
}


//subtpolys2(poly1,poly2)
//Subtracts polynomials: poly1-poly2, arranging terms from highest to lowest powers
function subtpolys2($p1,$p2) {
	$p = array();
	for ($i=0;$i<count($p1);$i++) {
		$p[$p1[$i][1].','.$p1[$i][2]]= $p1[$i][0];
	}
	for ($i=0;$i<count($p2);$i++) {
		if (isset($p[$p2[$i][1].','.$p2[$i][2]])) {
			$p[$p2[$i][1].','.$p2[$i][2]] = $p[$p2[$i][1].','.$p2[$i][2]] - $p2[$i][0];
		} else {
			$p[$p2[$i][1].','.$p2[$i][2]] = -1*$p2[$i][0];
		}
		
	}
	krsort($p);
	$po = array();
	$i = 0;
	foreach($p as $deg=>$coef) {
		$deg = explode(',',$deg);
		$po[$i][0] = $coef;
		$po[$i][1] = $deg[0];
		$po[$i][2] = $deg[1];
		$i++;
	}
	return $po;
}


//multpolys2(poly1,poly2)
//Multiplies polynomials
function multpolys2($p1,$p2) {
	$p = array();
	for ($i=0;$i<count($p1);$i++) {
		for ($j=0;$j<count($p2);$j++) {
			$newdeg = $p1[$i][1] + $p2[$j][1];
			$newdeg2 = $p1[$i][2] + $p2[$j][2];
			$newcoef = $p1[$i][0]*$p2[$j][0];
			if (isset($p[$newdeg.','.$newdeg2])) {
				$p[$newdeg.','.$newdeg2] += $newcoef;
			} else {
				$p[$newdeg.','.$newdeg2] = $newcoef;
			}
		}
	}
	krsort($p);
	$po = array();
	$i = 0;
	foreach($p as $deg=>$coef) {
		$deg = explode(',',$deg);
		$po[$i][0] = $coef;
		$po[$i][1] = $deg[0];
		$po[$i][2] = $deg[1];
		$i++;
	}
	return $po;
}

//scalepoly2(poly,c)
//Multiplies each term of poly by constant c
function scalepoly2($poly,$c) {
	for ($i=0; $i<count($poly); $i++) {
		$poly[$i][0] *= $c;
	}
	return $poly;
}

//polypower2(poly,power)
//Calculates poly^power
function polypower2($p,$pow) {
	$op = $p;
	for ($i=1;$i<$pow;$i++) {
		$op = multpolys2($op,$p);
	}
	return $op;
}

//getcoef2(poly,degree,degree)
//Gets the coefficient corresponding to the degree specified
//if no such term is defined, 0 is returned (since that is the coefficient!)
//poly: polynomial object, created with formpoly
//degree: degree of term to get coefficient of
function getcoef2($p,$deg,$deg2) {
	$coef = 0;
	for ($i=0;$i<count($p);$i++) {
		if ($p[$i][1]==$deg && $p[$i][2]==$deg2) {
			$coef = $p[$i][0];
			break;
		}
	}
	return $coef;
}


?>

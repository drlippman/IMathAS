<?php
//Vector functions.  Version 1.0, April 4, 2006

global $allowedmacros;
array_push($allowedmacros,"dotp","crossp","vecnorm","vecsum","vecdiff","vecprod","veccompareset","veccomparesamespan");

//dotp(a,b)
//dot product of vectors a and b
//a,b arrays (1+ elements)
function dotp($a,$b) {
    if (!is_array($a) || !is_array($b)) {
        echo 'dotp: inputs must be arrays';
        return false;
    }
	if (count($a)!=count($b)) {
		echo "dotp: vectors must be same length";
		return false;
	}
	$dotp = 0;
	for ($i=0;$i<count($a);$i++) {
		$dotp += $a[$i]*$b[$i];
	}
	return $dotp;
}

//crossp(a,b)
//cross product of vectors a and b
//a,b arrays (3 elements).  Returns array.
function crossp($a,$b) {
    if (!is_array($a) || !is_array($b)) {
        echo 'crossp: inputs must be arrays';
        return false;
    }
	if (count($a)!=3 || count($b)!=3) {
		echo "crossp: vectors must have 3 elements";
		return false;
	}
	$crossp[0] = $a[1]*$b[2]-$a[2]*$b[1];
	$crossp[1] = $a[2]*$b[0]-$a[0]*$b[2];
	$crossp[2] = $a[0]*$b[1]-$a[1]*$b[0];
	return $crossp;
}

//vecnorm(a)
//returns magnitude of vector a
//a array (1+ elements)
function vecnorm($a) {
    if (!is_array($a)) {
        echo 'vecnorm: input must be array';
        return false;
    }
	$nrm = 0;
	for ($i=0;$i<count($a);$i++) {
		$nrm += $a[$i]*$a[$i];
	}
	return sqrt($nrm);	
}

//vecsum(a,b)
//returns sum a+b
//a,b arrays same length
function vecsum($a,$b) {
    if (!is_array($a) || !is_array($b)) {
        echo 'vecsum: inputs must be arrays';
        return false;
    }
	if (count($a)!=count($b)) {
		echo "vecsum: inputs must be same length";
		return false;
	}
	for ($i=0;$i<count($a);$i++) {
		$sum[$i] = $a[$i]+$b[$i];
	}
	return $sum;
}

//vecdiff(a,b)
//returns difference a-b
//a,b arrays same length
function vecdiff($a,$b) {
    if (!is_array($a) || !is_array($b)) {
        echo 'vecdiff: inputs must be arrays';
        return false;
    }
	if (count($a)!=count($b)) {
		echo "vecdiff: vectors must be same length";
		return false;
	}
	for ($i=0;$i<count($a);$i++) {
		$sum[$i] = $a[$i]-$b[$i];
	}
	return $sum;
}

//vecprod(a,c)
//returns product c*a
//a array, c scalar
function vecprod($a,$c) {
    if (!is_array($a) || !is_scalar($c)) {
        echo 'vecprod: invalid inputs';
        return false;
    }
	for ($i=0;$i<count($a);$i++) {
		$prod[$i] = $c*$a[$i];
	}
	return $prod;
}


//veccompareset(a,b)
//a and b are both arrays of vectors, e.g. $a = array(array(1,2,3),array(3,4,5))
//returns a value between 0 (no overlap) and 1 (sets are equivalent)
//calculated as n(a intersect b)/max(n(a),n(b))
function veccompareset($a,$b) {
    if (!is_array($a) || !is_array($b)) {
        echo 'veccompareset: inputs must be arrays';
        return false;
    }
	if ($b===null) { return 0;}
	foreach ($b as $k=>$bv) {
		if (is_array($bv)) {continue;}
		if ($bv[0] == '[') { //in matrix notation
			$b[$k] = explode(',',str_replace(array('[',']','(',')'),'',$bv));
		} else { //in compressed notation
			$b[$k] = explode('|',$bv);
		}
	}
	$usedb = array();
	$matches = 0; 
	foreach ($a as $av) {
		foreach ($b as $k=>$bv) {
			if (isset($usedb[$k])) {continue;}
			foreach ($bv as $i=>$bvv) {
				if (abs($bvv - $av[$i])>.001) {
					continue 2;
				}
			}
			$matches++;
			$usedb[$k] = true;
			continue 2;
		}
	}
	return $matches/max(count($a),count($b));
}

//veccomparesamespan(A,B)
//determins if span(A) = span(B), where A is a linearly independent set.
//a and b are both arrays of vectors, e.g. $A = array(array(1,2,3),array(3,4,5))
//Note that if you want to use the columns of a matrix as A, you'll need to
//  transpose the matrix 
//Returns true or false
function veccomparesamespan($a,$b) {
    if (!is_array($a) || !is_array($b)) {
        echo 'veccomparesamespan: inputs must be arrays';
        return false;
    }
	if (count($a)!=count($b)) {return false;}
	include_once("matrix.php");
	if ($b===null) { return 0;}
	foreach ($b as $k=>$bv) {
		if (is_array($bv)) {continue;}
		if ($bv[0] == '[') { //in matrix notation
			$b[$k] = explode(',',str_replace(array('[',']','(',')'),'',$bv));
		} else { //in compressed notation
			$b[$k] = explode('|',$bv);
		}
	}
	foreach ($a as $av) {
		$b[] = $av;
	}

	$s = matrixreduce(matrixtranspose($b),true);
	$n = matrixnumsolutions($s, count($a));
	return (count($a)==$n);
}


?>

<?php
//Vector functions.  Version 1.0, April 4, 2006

global $allowedmacros;
array_push($allowedmacros,"dotp","crossp","vecnorm","vecsum","vecdiff","vecprod");

//dotp(a,b)
//dot product of vectors a and b
//a,b arrays (1+ elements)
function dotp($a,$b) {
	if (count($a)!=count($b)) {
		echo "vectors must be same length";
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
	if (count($a)!=3 || count($b)!=3) {
		echo "vectors must have 3 elements";
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
	if (count($a)!=count($b)) {
		echo "vectors must be same length";
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
	if (count($a)!=count($b)) {
		echo "vectors must be same length";
		return false;
	}
	for ($i=0;$i<count($a);$i++) {
		$sum[$i] = $a[$i]-$b[$i];
	}
	return $sum;
}

//vecprod(a,c)
//returns sum c*a
//a arrays, c scalar
function vecprod($a,$c) {
	for ($i=0;$i<count($a);$i++) {
		$prod[$i] = $c*$a[$i];
	}
	return $prod;
}
?>

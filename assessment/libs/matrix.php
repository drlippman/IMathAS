<?
//Matrix functions.  Version 1.1, Oct 3, 2006

global $allowedmacros;
array_push($allowedmacros,"matrix","matrixformat","matrixsum","matrixdiff","matrixscalar","matrixprod","matrixaugment","matrixrowscale","matrixrowswap","matrixrowcombine","matrixrowcombine3","matrixidentity");

//matrix(vals,rows,cols)
//Creates a new matrix item.  
//Use matrixformat() to create display or $answer from a matrix item
//vals: list or array of numbers to form matrix values: R1C1,R1C2,...,R2C1,R2C2,...
//rows, cols: dimensions of matrix
//To define a matrix using calculations use:
//$m = matrix(array(3/2,2^3,5+1,3),2,2)
function matrix($vals,$rows,$cols) {
	$m = array();
	if (!is_array($vals)) {
		$vals = explode(',',$vals);
	}
	if (count($vals)!=$rows*$cols) {
		echo 'Number of matrix values does not match row/column count';
		return $m;
	}
	for ($i=0;$i<$rows;$i++) {
		$m[$i] = array();
	}
	for ($i=0;$i<count($vals);$i++) {
		$m[floor($i/$cols)][$i%$cols] = $vals[$i];
	}
	return $m;
}

//matrixformat(matrix)
//Formats a matrix item into an ASCIIMath string for display or $answer
function matrixformat($m) {
	$out = '[';
	for ($i=0; $i<count($m); $i++) {
		if ($i!=0) {
			$out .= ',';
		}
		$out .= '(';
		for ($j=0;$j<count($m[0]); $j++) {
			if ($j!=0) {
				$out .= ',';
			}
			$out.= $m[$i][$j];
		}
		$out .= ')';
	}
	$out .= ']';
	return $out;
}

//matrixsum(matrix,matrix)
//Adds two matrices
function matrixsum($m,$n) {
	if (count($m)!=count($n) || count($m[0])!=count($n[0])) {
		echo 'matrix size does not match: cannot add';
		return $m;
	}
	for ($i=0; $i<count($m); $i++) {
		for ($j=0; $j<count($m[0]); $j++) {
			$m[$i][$j] += $n[$i][$j];
		}
	}
	return $m;
}

//matrixdiff(matrix1,matrix2)
//Subtracts matrix1-matrix2
function matrixdiff($m,$n) {
	if (count($m)!=count($n) || count($m[0])!=count($n[0])) {
		echo 'matrix size does not match: cannot add';
		return $m;
	}
	for ($i=0; $i<count($m); $i++) {
		for ($j=0; $j<count($m[0]); $j++) {
			$m[$i][$j] = $m[$i][$j] - $n[$i][$j];
		}
	}
	return $m;
}			

//matrixscalar(matrix,n)
//Multiplies the matrix times the number n
function matrixscalar($m,$n) {
	for ($i=0; $i<count($m); $i++) {
		for ($j=0; $j<count($m[0]); $j++) {
			$m[$i][$j] *= $n;
		}
	}
	return $m;
}	

//matrixprod(matrix1,matrix2)
//Calculates the matrix product matrix1*matrix2
function matrixprod($m,$n) {
	if (count($m[0])!=count($n)) {
		echo 'matrix sizes do not allow product';
		return $m;
	}
	$o = array();
	$o = array();
	for ($i=0;$i<count($m); $i++) {
		for ($j=0;$j<count($n[0]); $j++) {
			$v = 0;
			for ($k=0; $k<count($m[0]); $k++) {
				$v += $m[$i][$k]*$n[$k][$j];
			}
			$o[$i][$j] = $v;
		}
	}
	return $o;
}

//matrixaugment(matrix1,matrix2)
//Augments matrix2 to the right side of matrix1
function matrixaugment($m,$n) {
	if (count($m)!=count($n)) {
		echo 'row count does not match: cannot augment';
		return $m;
	}
	for ($i=0; $i<count($m); $i++) {
		$m[$i] = array_merge($m[$i],$n[$i]);
	}
	return $m;
}

//matrixrowscale(matrix,row,n)
//Multiplies row of matrix by n
//matrix rows are 0-indexed; first row is row 0
function matrixrowscale($m,$r,$n) {
	for ($j=0; $j<count($m[$r]); $j++) {
		$m[$r][$j] *= $n;
	}
	return $m;
}
	
//matrixrowswap(matrix,row1,row2)
//swaps rows in matrix
//matrix rows are 0-indexed; first row is row 0
function matrixrowswap($m,$r,$t) {
	$temp = $m[$t];
	$m[$t] = $m[$r];
	$m[$r] = $temp;
	return $m;	
}

//matrixrowcombine(matrix,row1,a,row2,b,endrow)
//replaces endrow in matrix with a*row1 + b*row2
//matrix rows are 0-indexed; first row is row 0
function matrixrowcombine($m,$r1,$a,$r2,$b,$s) {
	for ($j=0; $j<count($m[$s]); $j++) {
		$m[$s][$j] = $a*$m[$r1][$j] + $b*$m[$r2][$j];
	}
	return $m;
}

//matrixrowcombine3(matrix,row1,a,row2,b,row3,c,endrow)
//replaces endrow in matrix with a*row1 + b*row2 + c*row3
//matrix rows are 0-indexed; first row is row 0
function matrixrowcombine3($m,$r1,$a,$r2,$b,$r3,$c,$s) {
	for ($j=0; $j<count($m[$s]); $j++) {
		$m[$s][$j] = $a*$m[$r1][$j] + $b*$m[$r2][$j] + $c*$m[$r3][$j];
	}
	return $m;
}

//matrixidentity(n)
//Creates an n x n identity matrix
function matrixidentity($n) {
	$m = array();
	for ($i=0; $i< $n; $i++) {
		$m[$i] = array();
		for ($j=0; $j<$n; $j++) {
			$m[$i][$j] = 0;
		}
		$m[$i][$i] = 1;
	}
	return $m;
}


?>



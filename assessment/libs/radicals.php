<?php
//A collection of functions for working with radicals
//Version 1.0 Oct 21, 2010

global $allowedmacros;
array_push($allowedmacros,"reduceradical","reduceradicalfrac");

//reduceradical(inside,[root,format])
//given the inside of a radical, reduces it to Aroot(B)
//inside: inside of the radical.
//root: root of radical.  Default is 2 (sqrt)
//format: default is "string", which returns "4sqrt(5)"
//                   "disp", returns the string wrapped in backticks for display
//                   "parts", returns an array of the parts:  array(4,5)
function reduceradical($in,$root=2,$format="string") {
	if ($in<0 && ($root%2==0)) {
		echo "even roots of negatives can't be handled";
		return;
	} else if ($in<0) {
		$sign = '-';
	} else {
		$sign = '';
	}
	$in = abs($in);
	$max = 	pow($in,1/$root);
	$out = 1;
	//look for biggest perfect power first
	for ($i=floor($max);$i>1;$i--) {
		if ($in%(pow($i,$root))==0) {
			$out *= $i;
			$in /= pow($i,$root);
		}
	}
	if ($format=='string' || $format=='disp') {
		$outstr = '';
		if ($format=='disp') {
			$outstr .= '`';
		}
		$outstr .= $sign;
		if ($out>1 || $in==1) {
			$outstr .= $out;
		}
		if ($in>1) {
			if ($root==2) {
				$outstr .= "sqrt($in)";
			} else {
				$outstr .= "root($root)($in)";
			}
		}
		if ($format=='disp') {
			$outstr .= '`';
		}
		return $outstr;
	} else {
		if ($sign=='-') {$out *= -1;}
		return array($out,$in);
	}
}

//reduceradicalfrac(wholenum,rootnum,denom,[root,format])
//given (wholenum*root(rootnum))/denom, reduces the root then the fraction
//root: root of radical.  Default is 2 (sqrt)
//format: default is "string", which returns "4sqrt(5)/2"
//                   "disp", returns the string wrapped in backticks for display
//                   "parts", returns an array of the parts:  array(4,5,2)
function reduceradicalfrac($n,$rootnum,$d,$root=2,$format="string") {
	list($rootA,$in) = reduceradical($rootnum,$root,"parts");
	$n *= $rootA;
	$g = gcd($n,$d);
	$n = $n/$g;
	$d = $d/$g;	
	if ($d<0) {
		$n = $n*-1;
		$d = $d*-1;
	}
	if ($format=='string' || $format=='disp') {
		$outstr = '';
		if ($format=='disp') {
			$outstr .= '`';
		}
		if ($d>1) {
			$outstr .= '(';
		}
		if (abs($n)!=1 || $in==1) {  //  3root(2) or 1root(1)
			$outstr .= $n;
		} else if ($n==-1) {
			$outstr .= '-';
		}
		if ($in>1) {
			if ($root==2) {
				$outstr .= "sqrt($in)";
			} else {
				$outstr .= "root($root)($in)";
			}
		}
		if ($d>1) {
			$outstr .= ")/$d";
		}
		if ($format=='disp') {
			$outstr .= '`';
		}
		return $outstr;
	} else {
		return array($n,$in,$d);
	}
}

	



?>

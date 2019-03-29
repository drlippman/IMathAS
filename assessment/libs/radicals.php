<?php
//A collection of functions for working with radicals
//Version 3 Nov 27 2017

global $allowedmacros;
array_push($allowedmacros,"reduceradical","reduceradicalfrac","reduceradicalfrac2","reducequadraticform");

//reduceradical(inside,[root,format])
//given the inside of a radical, reduces it to Aroot(B)
//inside: inside of the radical.
//root: root of radical.  Default is 2 (sqrt)
//format: default is "string", which returns "4sqrt(5)"
//                   "disp", returns the string wrapped in backticks for display
//                   "parts", returns an array of the parts:  array(4,5)
function reduceradical($in,$root=2,$format="string") {
	if (!is_finite($in) || !is_finite($root)) {
		echo 'inputs to radicalfrac must be integers';
		return;
	}
	$in = intval($in);
	
	if ($in > 10000000) {
		if ($GLOBALS['myrights'] > 10) {
			echo 'Input to reduceradical was too large';
		}
		return;
	} else if ($in<0 && ($root%2==0)) {
		echo "even roots of negatives can't be handled";
		return;
	}  else	if ($root==0) {
		if (is_string($root)) {
			echo "can't provide a string as the root value - check your parameters";
			return;
		} else {
			echo "can't take the zero'th root";
			return;
		}
	} else if ($root < 0) {
		echo "can't handle negative roots";
		return;
	}
	
	$root = intval($root);
	
	if ($in<0) {
		$sign = '-';
	} else {
		$sign = '';
	}

	$in = abs($in);
	$max = 	pow($in,1/$root);
	$out = 1;
	
	//look for biggest perfect power first
	for ($i=floor($max+.01);$i>1 && $in>1;$i--) {
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
	if (!is_finite($n) || !is_finite($rootnum) || !is_finite($d) || !is_finite($root)) {
		echo 'inputs to reduceradicalfrac must be integers';
		return 0;
	}
	if ($rootnum > 10000000) {
		if ($GLOBALS['myrights'] > 10) {
			echo 'Input to reduceradicalfrac was too large';
		}
		return;
	}
	$n = intval($n);
	$rootnum = intval($rootnum);
	$d = intval($d);
	$root = intval($root);
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
		if ($n<0) {
			$outstr .= '-';
			$n *= -1;
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

//reduceradicalfrac2(num,wholedenom,rootnum,[rationalize,root,format])
//given num/(wholedenom*root(rootnum)), reduces the root then the fraction
//rationalize: rationalize the denominator. Default is false.
//root: root of radical.  Default is 2 (sqrt)
//format: default is "string", which returns "2/(3sqrt(5))"
//                   "disp", returns the string wrapped in backticks for display
//                   "parts", returns an array of the parts:  array(2,3,5)
function reduceradicalfrac2($n,$d,$rootnum,$rat=false,$root=2,$format="string") {
	if ($rat==true) {
		return reduceradicalfrac($n, $rootnum, $d*$rootnum, $root, $format);
	}
	list($rootA,$in) = reduceradical($rootnum,$root,"parts");
	$d *= $rootA;
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
		
		$outstr .= $n;
		if ($d>1 || $in>1) {
			$outstr .= '/';
			if ($d>1 && $in>1) {
				$outstr .= '(';
			}
			if ($d>1) {
				$outstr .= $d;
			}
			if ($in>1) {
				if ($root==2) {
					$outstr .= "sqrt($in)";
				} else {
					$outstr .= "root($root)($in)";
				}
			}
			if ($d>1 && $in>1) {
				$outstr .= ')';
			}
		}
		
		if ($format=='disp') {
			$outstr .= '`';
		}
		return $outstr;
	} else {
		return array($n,$d,$in);
	}
}


//reducequadraticform(a,b,c,d,[format])
//given (a+bsqrt(c))/d, reduces the root then the fraction
//format: default is "string", which returns "(1+4sqrt(5))/2"
//                   "disp", returns the string wrapped in backticks for display
//                   "parts", returns an array of the parts:  array(1,4,5,2)
function reducequadraticform($a,$n,$rootnum,$d,$format="string") {
	if ($rootnum<0) {
		$iscomplex = true;
		$rootnum = abs($rootnum);
	} else {
		$iscomplex = false;
	}
	$root = 2;
	//reduce to (a+n sqrt(in))/d
	list($rootA,$in) = reduceradical($rootnum,$root,"parts");
	$n *= $rootA;
	if ($in==1 && !$iscomplex) {
		$n += $a;
		$a = 0;
	}
	$gr = gcd($n,$d);
	$gw = gcd($a,$d);
	$g = gcd($gr,$gw); //gcd of a,n, and d
	$a = $a/$g;
	$n = $n/$g;
	$d = $d/$g;
	if ($d<0) {
		$a = $a*-1;
		$n = $n*-1;
		$d = $d*-1;
	}
	if ($format=='parts') {
		return array($a, $n, $in*($iscomplex?-1:1), $d);
	}
	$outstr = '';
	if ($format=='disp') {
		$outstr .= '`';
	}
	if ($d>1) {
		$outstr .= '(';
	}
	if ($a != 0) {
		$outstr .= $a;
		if ($n>0) {
			$outstr .= '+';
		} else {
		//	$outstr .= '-';
		}
	}
	if (abs($n)!=1 || $in==1) {  //  3root(2) or 1root(1)
		$outstr .= $n;
	} else if ($n==-1) {
		$outstr .= '-';
	}
	if ($in>1) {
		$outstr .= "sqrt($in)";
	}
	if ($iscomplex) {
		$outstr .= "i";
	}
	if ($d>1) {
		$outstr .= ")/$d";
	}
	if ($format=='disp') {
		$outstr .= '`';
	}
	return $outstr;
}


?>

<?php

//IMathAS:  ASCIIMath to PHP conversion
//(c) 2009 David Lippman

//This is a rewrite of mathphp using a tokenizing approach

//Based on concepts from mathjs from Peter Jipsen's ASCIIsvg.js
//script, (c) Peter Jipsen.  See /javascript/ASCIIsvg.js

//assumes there is variable
//$allowedmacros:  allowable function calls (including math functions)
//mathphp does not use disallowedvar, so be check for those in implementations
//if changing from letter vars to php $vars to be evaled


function mathphppre($st) {
  if (strpos($st,"^-1") || strpos($st,"^(-1)")) {
		$st = str_replace(array("sin^-1","cos^-1","tan^-1","sin^(-1)","cos^(-1)","tan^(-1)"),array("arcsin","arccos","arctan","arcsin","arccos","arctan"),$st);
		$st = str_replace(array("sinh^-1","cosh^-1","tanh^-1","sinh^(-1)","cosh^(-1)","tanh^(-1)"),array("arcsinh","arccosh","arctanh","arcsinh","arccosh","arctanh"),$st);
  }
  $st = preg_replace('/log_([a-zA-Z\d\.\/]+)\s*\(/','nthlog($1,',$st);
  $st = preg_replace('/log_\(([a-zA-Z\/\d\.\/]+)\)\s*\(/','nthlog($1,',$st);
  $st = preg_replace('/(sin|cos|tan|sec|csc|cot)\s*\^\s*(\d+)\s*\(/','$1n($2,', $st);

  return $st;
}
function mathphp($st,$varlist,$skipfactorial=false,$ignorestrings=true) {
	//translate a math formula to php function notation
	// a^b --> pow(a,b)
	// na --> n*a
	// (...)d --> (...)*d
	// n! --> factorial(n)
	// sin^-1 --> asin etc.

	//parenthesize variables with number endings, ie $c2^3 => ($c2)^3
	//not needed since mathphp no longer used on php vars

	//skipfactorial:  legacy: not really used anymore.  Originally intended
	//to handle !something type ifcond.  Might need to reexplore

	$vars = explode('|',$varlist);
	//security check variables (we might evaling with them later)
	global $disallowedvar;
	if (!isset($disallowedvar)) {
		$disallowedvar = array('$link','$qidx','$qnidx','$seed','$qdata','$toevalqtxt','$la','$GLOBALS','$laparts','$anstype','$kidx','$iidx','$tips','$options','$partla','$partnum','$score','$disallowedvar','$allowedmacros');
	}
	foreach ($vars as $var) {
		if (in_array('$'.$var,$disallowedvar) || substr($var,0,7)=='GLOBALS') {
			echo "disallowed variable name";
			return "0;";
		}
	}

	//take care of sin^-1 notation first
	$st = mathphppre($st);
	$st = preg_replace('/(\+|\-)\s+(\+|\-)/',"$1$2",$st);
	//$exp = str_replace(" ", "", $exp);  //caused problems with "x > -3"
	$st = str_replace("+-","-",$st);
	$st = str_replace("-+","-",$st);
	$st = str_replace("--","+",$st);
	return mathphpinterpretline($st.' ',$vars,$ignorestrings);

}
//interpreter some code text.  Returns a PHP code string.
function mathphpinterpretline($str,$vars,$ignorestrings) {
	$str .= ';';
	$bits = array();
	$lines = array();
	$len = strlen($str);
	$cnt = 0;
	$lastsym = '';
	$lasttype = -1;
	$closeparens = 0;
	$symcnt = 0;
	//get tokens from tokenizer
	$syms = mathphptokenize($str,$vars,$ignorestrings);
	$k = 0;
	$symlen = count($syms);
	//$lines holds lines of code; $bits holds symbols for the current line.
	while ($k<$symlen) {
		list($sym,$type) = $syms[$k];
		//first handle stuff that would use last symbol; add it if not needed
		if ($sym=='^' && $lastsym!='') { //found a ^: convert a^b to safepow(a,b)
			$bits[] = 'safepow(';
			$bits[] = $lastsym;
			$bits[] = ',';
			$k++;
			list($sym,$type) = $syms[$k];
			$closeparens++;  //triggers to close safepow after next token
			$lastsym='^';
			$lasttype = 0;
		} else if ($sym=='!' && $lasttype!=0 && $lastsym!='' && $syms[$k+1]{0}!='=') {
			//convert a! to factorial(a), avoiding if(!a) and a!=b
			$bits[] = 'factorial(';
			$bits[] = $lastsym;
			$bits[] = ')';
			$sym = '';
		} else if ($lasttype==2 && $type==4 && substr($lastsym,0,5)=='root(') {
			$bits[] = substr($lastsym,0,-1).',';
			$sym = substr($sym,1);
			$lasttype = 0;
		} else {
			//add last symbol to stack
			if ($lasttype!=7 && $lasttype!=-1) {
				$bits[] = $lastsym;
			}
		}
		if ($closeparens>0 && $lastsym!='^' && $lasttype!=0) {
			//close safepow.  lasttype!=0 to get a^-2 to include -
			while ($closeparens>0) {
				$bits[] = ')';
				$closeparens--;
			}
			//$closeparens = false;
		}


		if ($type==7) {//end of line
			if ($lasttype=='7') {
				//nothing exciting, so just continue
				$k++;
				continue;
			}
			//check for for, if, where and rearrange bits if needed
			$forloc = -1;
			$ifloc = -1;
			$whereloc = -1;
			//collapse bits to a line, add to lines array
			$lines[] = implode('',$bits);
			$bits = array();
		} else if ($type==1) { //is var
			//implict 3$a and $a $b and (3-4)$a
			if ($lasttype==3 || $lasttype==1 || $lasttype==4 || $lasttype==2) {
				$bits[] = '*';
			}
		} else if ($type==2) { //is func
			//implicit $v sqrt(2) and 3 sqrt(3) and (2-3)sqrt(4) and sqrt(2)sqrt(3)
			if ($lasttype==3 || $lasttype==1 || $lasttype==4 || $lasttype==2 ) {
				$bits[] = '*';
			}
		} else if ($type==3) { //is num
			//implicit 2 pi and $var pi
			if ($lasttype==3 || $lasttype == 1 || $lasttype==4 || $lasttype==2) {
				$bits[] = '*';
			}

		} else if ($type==4) { //is parens
			//implicit 3(4) (5)(3)  $v(2)  sin(4)(3)
			if ($lasttype==3 || $lasttype==4 || $lasttype==1 || $lasttype==2) {
				$bits[] = '*';
			}
		} else if ($type==9) {//is error
			//tokenizer returned an error token - exit current loop with error
			return 'error';
		} else if ($sym=='-' && $lastsym=='/') {
			//paren 1/-2 to 1/(-2)
			//avoid bug in PHP 4 where 1/-2*5 = -0.1 but 1/(-2)*5 = -2.5
			$bits[] = '(';
			$closeparens++;
		}


		$lastsym = $sym;
		$lasttype = $type;
		$cnt++;
		$k++;
	}
	//if no explicit end-of-line at end of bits
	if (count($bits)>0) {
		$lines[] = implode('',$bits);
	}
	//collapse to string
	return implode(';',$lines);
}



function mathphptokenize($str,$vars,$ignorestrings) {
	global $allowedmacros;
	if (!isset($allowedmacros)) {
		$allowedmacros = array("sin","cos","tan","sinh","cosh","tanh","arcsin","arccos","arctan","arcsinh","arccosh","sqrt","ceil","floor","round","log","ln","abs","max","min","count","nthlog","sinn","cosn","tann","secn","cscn","cotn");
	}

	$lookfor = array_merge($vars, array("e","pi"));
	$maxvarlen = 0;
	foreach ($lookfor as $v) {
		$l = strlen($v);
		if ($l>$maxvarlen) {
			$maxvarlen = $l;
		}
	}
	$connecttolast = 0;
	$i=0;
	$cnt = 0;
	$len = strlen($str);
	$syms = array();
	$lastsym = array();
	while ($i<$len) {
		$cnt++;
		if ($cnt>100) {
			exit;
		}
		$intype = 0;
		$out = '';
		$c = $str{$i};
		$eatenwhite = 0;
		if ($c>="a" && $c<="z" || $c>="A" && $c<="Z") {
			//is a string or function name
			//need to handle things like:
			//function3(whee)
			//func_name(blah)
			// xy
			// ssin(s)
			// snsin(x)
			// nln(n)
			// ppi   and pip
			// pi

			$intype = 2; //string like function name
			do {
				$out .= $c;
				$i++;
				if ($i==$len) {break;}
				$c = $str{$i};
			} while ($c>="a" && $c<="z" || $c>="A" && $c<="Z" || $c>='0' && $c<='9' || $c=='_');
			//check if it's a special word
			if ($out=='e' && !in_array($out,$vars)) {
				$out = "exp(1)";
				$intype = 3;
			} else if ($out=='pi') {
				$out = "(M_PI)";
				$intype = 3;
			} else {
				//eat whitespace
				while ($c==' ') {
					$i++;
					$c = $str{$i};
					$eatenwhite++;
				}
				//if possible function at end, strip off function
				//look for xsin(  or nsin(  or  nxsin(  or xy(1+x)
				if ($c=='(' && !in_array($out,$allowedmacros) && !in_array($out,$vars)) {// moved to mathphppre-> || ($c=='^' && (substr($str,$i+1,2)=='-1' || substr($str,$i+1,4)=='(-1)'))) {
					$outlen = strlen($out);
					$outend = '';
					for ($j=1; $j<$outlen; $j++) {
						$outend = substr($out,$j);
						if (in_array($outend,$allowedmacros)) {
							$i = $i - $outlen + $j;
							$c = $str{$i};
							$out = substr($out,0,$j);
							break;
						}
						//is end a variable?  like xy(1+x)
						if (in_array($outend,$vars)) {
							$i = $i - $outlen + $j;
							$c = $str{$i};
							$out = substr($out,0,$j);
							break;
						}
					}

				}

				//if there's a ( then it's a function or x(
				if ($c=='(' && $out!='e' && $out!='pi' && !in_array($out,$vars)) {
					//is a function
					//rewrite logs
					if ($out=='log') {
						$out = 'log10';
					} else if ($out=='ln') {
						$out = 'log';
					} else if ($out=='rand') {
						$out = '$GLOBALS[\'RND\']->rand';
					} else {
						//check it's an OK function
						if (!in_array($out,$allowedmacros)) {
							echo "Eeek.. unallowed macro {$out}";
							return array(array('',9));
						}
					}
					//rewrite arctrig into atrig for PHP
					$out = str_replace(array("arcsin","arccos","arctan","arcsinh","arccosh","arctanh"),array("asin","acos","atan","asinh","acosh","atanh"),$out);

					//connect upcoming parens to function
					$connecttolast = 2;
				} else {
					//look for xpi,  pix,  ppi,  xe
					//not a function, so what is it?
					if (in_array($out,$vars)) {
						$intype = 4;
						$out = '('.$out.')';
					} else if ($out=='true' || $out=='false' || $out=='null') {
						//we like this - it's an acceptable unquoted string
					} else {
						$intype = 6;
						//look for varvar
						$outlen = strlen($out);
						$outst = '';
						for ($j=min($maxvarlen,$outlen-1); $j>0; $j--) {
							$outst = substr($out,0,$j);
							if (in_array($outst,$lookfor)) {
								$i = $i - $outlen + $j - $eatenwhite;
								$c = $str{$i};
								$out = $outst;
								if ($out=='e') {
									$out = "exp(1)";
									$intype = 3;
								} else if ($out=='pi') {
									$out = "(M_PI)";
									$intype = 3;
								} else {
									if (in_array($out,$vars)) {
										$out = '('.$out.')';
										$intype = 4;
									}
								}
								break;

							}

						}
						//quote it if not a variable
						if ($intype == 6 && $ignorestrings) {
							$out = "'$out'";
						}

					}

					/*if (isset($GLOBALS['teacherid'])) {
						//an unquoted string!  give a warning to instructor,
						//but treat as a quoted string.
						echo "Warning... unquoted string $out.. treating as string";
						$out = "'$out'";
						$intype = 6;
					}
					*/

				}
			}
		} else if (($c>='0' && $c<='9') || ($c=='.'  && ($str{$i+1}>='0' && $str{$i+1}<='9')) ) { //is num
			$intype = 3; //number
			$cont = true;
			//handle . 3 which needs to act as concat
			if (isset($lastsym[0]) && $lastsym[0]=='.') {
				$syms[count($syms)-1][0] .= ' ';
			}
			do {
				$out .= $c;
				$lastc = $c;
				$i++;
				if ($i==$len) {break;}
				$c= $str{$i};
				if (($c>='0' && $c<='9') || ($c=='.' && $str{$i+1}!='.' && $lastc!='.')) {
					//is still num
				} else if ($c=='e' || $c=='E') {
					//might be scientific notation:  5e6 or 3e-6
					$d = $str{$i+1};
					if ($d>='0' && $d<='9') {
						$out .= $c;
						$i++;
						if ($i==$len) {break;}
						$c= $str{$i};
					} else if (($d=='-'||$d=='+') && ($str{$i+2}>='0' && $str{$i+2}<='9')) {
						$out .= $c.$d;
						$i+= 2;
						if ($i>=$len) {break;}
						$c= $str{$i};
					} else {
						$cont = false;
					}
				} else {
					$cont = false;
				}
			} while ($cont);
		} else if ($c=='(' || $c=='{' || $c=='[') { //parens or curlys
			if ($c=='(') {
				$intype = 4; //parens
				$leftb = '(';
				$rightb = ')';
			} else if ($c=='{') {
				$intype = 5; //curlys
				$leftb = '{';
				$rightb = '}';
			} else if ($c=='[') {
				$intype = 11; //array index brackets
				$leftb = '[';
				$rightb = ']';
			}
			$thisn = 1;
			$inq = false;
			$j = $i+1;
			$len = strlen($str);
			while ($j<$len) {
				//read terms until we get to right bracket at same nesting level
				//we have to avoid strings, as they might contain unmatched brackets
				$d = $str{$j};
				if ($inq) {  //if inquote, leave if same marker (not escaped)
					if ($d==$qtype && $str{$j-1}!='\\') {
						$inq = false;
					}
				} else {
					if ($d=='"' || $d=="'") {
						$inq = true; //entering quotes
						$qtype = $d;
					} else if ($d==$leftb) {
						$thisn++;  //increase nesting depth
					} else if ($d==$rightb) {
						$thisn--; //decrease nesting depth
						if ($thisn==0) {
							//read inside of brackets, send recursively to interpreter
							$inside = mathphpinterpretline(substr($str,$i+1,$j-$i-1),$vars,$ignorestrings);
							if ($inside=='error') {
								//was an error, return error token
								return array(array('',9));
							}
							//if curly, make sure we have a ;, unless preceeded by a $ which
							//would be a variable variable
							if ($rightb=='}' && $lastsym[0]!='$') {
								$out .= $leftb.$inside.';'.$rightb;
							} else {
								$out .= $leftb.$inside.$rightb;
							}
							$i= $j+1;
							break;
						}
					} else if ($d=="\n") {
						//echo "unmatched parens/brackets - likely will cause an error";
					}
				}
				$j++;
			}
			if ($j==$len) {
				$i = $j;
				if (isset($GLOBALS['teacherid'])) {
					echo _('unmatched parens/brackets - likely will cause an error');
				}
			} else {
				$c = $str{$i};
			}
		} else if ($c=='"' || $c=="'") { //string
			$intype = 6;
			$qtype = $c;
			do {
				$out .= $c;
				$i++;
				if ($i==$len) {break;}
				$lastc = $c;
				$c = $str{$i};
			} while (!($c==$qtype && $lastc!='\\'));
			$out .= $c;
			if (!$ignorestrings) {
				$inside = mathphpinterpretline(substr($out,1,strlen($out)-2),$vars,$ignorestrings);
				if ($inside{0}=='\'' && $inside{strlen($inside)-1}=='\'') {
					$inside = substr($inside,1,strlen($inside)-2);
				}
				$out= $qtype . $inside . $qtype;

			}

			$i++;
			$c = $str{$i};
		} else if ($c=="\n") {
			//end of line
			$intype = 7;
			$i++;
			if ($i<$len) {
				$c = $str{$i};
			}
		} else if ($c==';') {
			//end of line
			$intype = 7;
			$i++;
			if ($i<$len) {
				$c = $str{$i};
			}
		} else {
			//no type - just append string.  Could be operators
			$out .= $c;
			$i++;
			if ($i<$len) {
				$c = $str{$i};
			}
		}
		while ($c==' ') { //eat up extra whitespace
			$i++;
			if ($i==$len) {break;}
			$c = $str{$i};
			if ($c=='.' && $intype==3) {//if 3 . needs space to act like concat
				$out .= ' ';
			}
		}
		//if parens or array index needs to be connected to func/var, do it
		if ($connecttolast>0 && $intype!=$connecttolast) {

			$syms[count($syms)-1][0] .= $out;
			$connecttolast = 0;
			if ($c=='[') {// multidim array ref?
				$connecttolast = 1;
			}

		} else {
			//add to symbol list, avoid repeat end-of-lines.
			if ($intype!=7 || $lastsym[1]!=7) {
				$lastsym = array($out,$intype);
				$syms[] =  array($out,$intype);
			}
		}

	}
	return $syms;
}

function safepow($base,$power) {
	if ($base==0) {if($power==0) {return sqrt(-1);} else {return 0;}}
	if ($base<0 && floor($power)!=$power) {
		for ($j=3; $j<50; $j+=2) {
			if (abs(round($j*$power)-($j*$power))<.000001) {
				if (round($j*$power)%2==0) {
					return exp($power*log(abs($base)));
				} else {
					return -1*exp($power*log(abs($base)));
				}
			}
		}
		return sqrt(-1);
	}
	if (floor($base)==$base && floor($power)==$power && $power>0) { //whole # exponents
		$result = pow(abs($base),$power);
	} else { //fractional & negative exponents (pow can't handle?)
		$result = exp($power*log(abs($base)));
	}
	if (($base < 0) && ($power % 2 != 0)) {
		$result = -($result);
	}
	return $result;
}

function root($n,$x) {
	if ($n%2==0 && $x<0) { //if even root and negative base
		return sqrt(-1);
	} else if ($x<0) { //odd root of negative base - negative result
		return -1*exp(1/$n*log(abs($x)));
	} else { //root of positive base
		return exp(1/$n*log(abs($x)));
	}
}

function factorial($x) {
	for ($i=$x-1;$i>0;$i--) {
		$x *= $i;
	}
	return ($x<0?false:($x==0?1:$x));
}
//basic trig cofunctions
function sec($x) {
	return (1/cos($x));
}
function csc($x) {
	return (1/sin($x));
}
function cot($x) {
	return (1/tan($x));
}
function sech($x) {
	return (1/cosh($x));
}
function csch($x) {
	return (1/sinh($x));
}
function coth($x) {
	return (1/tanh($x));
}
function nthlog($n,$x) {
	return (log($x)/log($n));
}
function sinn($n,$x) { return safepow(sin($x), $n);}
function cosn($n,$x) { return safepow(cos($x), $n);}
function tann($n,$x) { return safepow(tan($x), $n);}
function cscn($n,$x) { return 1/safepow(sin($x), $n);}
function secn($n,$x) { return 1/safepow(cos($x), $n);}
function cotn($n,$x) { return 1/safepow(tan($x), $n);}
?>

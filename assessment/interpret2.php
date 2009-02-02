<?php
//IMathAS:  IMathAS question interpreter.  Defines how the IMathAS question
//language works.
//(c) 2006 David Lippman
/*
Converts IMathAS code into PHP code for eval
variables are compared against a blacklist
macros (functions) are compared against a whitelist   (TODO: check for non-approved language constructs too)

rewrites required to make language work:
IMATHAS						PHP
newline 					end-of-line (like ;)
&\n						continue on next line
&&\n						continue on next line, insert <br/>

$a,$b = rands(2,3,2)				list($a,$b) = rands(2,3,2)

$a = 2 if $b==3					if ($b==3) { $a=2;}
$a = 2 if ($b==3)				if ($b==3) { $a=2;}
{$a=2;$b=3} if ($c==1)				if ($c==1) { $a=2; $b=3;}
if $c==1 {$a==1}				if ($c==1) {$a==1}
if ($c==1) {$a==1}				if ($c==1) {$a==1}



*/
/*require_once("mathphp.php");
array_push($allowedmacros,"loadlibrary","array","where");
$disallowedwords = array("exit","die");
$disallowedvar = array('$link','$qidx','$qnidx','$seed','$qdata','$toevalqtxt','$la','$GLOBALS','$laparts','$anstype','$kidx','$iidx','$tips','$options','$partla','$partnum','$score');
*/	
array_push($allowedmacros,"off","true","false","e","pi","null","setseed","a2");
function interpret2($blockname,$anstype,$str)
{
	if ($blockname=="qtext") {
		$str = str_replace('"','\"',$str);
		$str = str_replace("\r\n","\n",$str);
		$str = str_replace("\n\n","<br/><br/>\n",$str);
		return $str;
	} else {
		global $allowedmacros;
		global $mathfuncs;
		global $disallowedwords,$disallowedvar;
		
		$str = str_replace(array('\\frac','\\tan','\\root','\\vec'),array('\\\\frac','\\\\tan','\\\\root','\\\\vec'),$str);
		$str .= ' ';
		$str = str_replace("\r\n","\n",$str);
		$str = str_replace("&&\n","<br/>",$str);
		$str = str_replace("&\n"," ",$str);
		$str = '<?php '.$str.'?>';
		//tokensize.  Scan for unallowed macros or variables.
		$tokens = token_get_all($str);
		/*foreach($tokens as $k=>$c) {
			if (is_array($c)) {
				echo token_name($c[0]).': '.$c[0].': '.$c[1].'<br/>';
			} else {
				echo "$c<br/>";
			}
		}*/
		$instr = false;
		foreach($tokens as $k=>$c) {
			if(is_array($c)) {
				//if ($c[0] == 370 ) { //whitespace
				//	unset($tokens[$k]);
				//	continue;
				//}
				if ($c[0] == 367 || $c[0] == 369) {  //open tag, close tag, whitespace
					unset($tokens[$k]);
					continue;
				}
				if ($c[0]==T_COMMENT) {
					$tokens[$k] = ';';
				}
				if ($c[0] == 309) { //it's a variable
					if (in_array($c[1],$disallowedvar)) {
						echo "Eeek.. unallowed var {$c[1]}!";
						return '';
					}
				}
				if ($c[0] == 307 && !$instr) { //it's a "word"
					if ($c[1]=='userid') {
						$c[1] = "'userid'";
					} else {
					if (!in_array($c[1],$allowedmacros)) {
						echo "Eeek.. unallowed macro {$c[1]}";
						return '';
					}
					}
				}
			} else {
				if ($instr && $c==$strmarker) {
					$instr = false;
				} else if ($c=='"' || $c=="'") {
					$instr = true;
					$strmarker = $c;
				}
			}
		}
		
		//reset indices!
		
		$tokens = array_values($tokens);
		
		return interpretlinesplitter($tokens);
	}
}
		
function interpretlinesplitter($tokens) {
	stripcurlyifneeded($tokens);
	$out = '';
	$last = -1;
	$nestd = 0;
	$cnt = count($tokens);
	for ($i=0; $i<$cnt; $i++) {
	    $c = $tokens[$i];
	    if (is_array($c)) {
		    if ($c[0]==T_CURLY_OPEN || $c[0]==T_DOLLAR_OPEN_CURLY_BRACES) {
			    $nestd++;
		    } else if ($c[0]==T_WHITESPACE && strpos($c[1],"\n")!==false && $nestd==0) {
			   //echo "found newline - sending on ".($i-$last-1)." tokens\n";
			    $out .= interpretline(array_slice($tokens,$last+1,$i-$last-1)).';';
			    $last = $i;  
		    }
	    } else {
		    if ($c=='{') {
			    $nestd++;
		    } else if ($c=='}') {
			    $nestd--;
		    } else if ($c==';' && $nestd==0) { //end of line - send to line interpreter
			  // echo "found ; line - sending on ".($i-$last-1)." tokens\n";
			    if ($i-$last-1>1) {
				    $out .= interpretline(array_slice($tokens,$last+1,$i-$last-1)).';';
			    }
			    $last = $i;
		    }
	    }
	}
	if ($i-$last-1>1) {
	    //echo "found end line - sending on ".($i-$last-1)." tokens\n";
	    	$out .= interpretline(array_slice($tokens,$last+1)).';'; 
	}
	
	return $out;
}

function stripcurlyifneeded(&$tokens) {
	
	//trim left
	while (is_array($tokens[0]) && $tokens[0][0]==T_WHITESPACE) {
		unset($tokens[0]);
	}
	//trim right
	$cnt = count($tokens);
	for ($i=$cnt-1; $i>0; $i--) {
		if (is_array($tokens[$i]) && $tokens[$i][0]== T_WHITESPACE) {
			unset($tokens[$i]);
		} else {
			break;
		}
	}
	$cnt = count($tokens);
	if ($tokens[0]=='{' && $tokens[$cnt-1]=='}') {
		unset($tokens[$cnt-1]);
		unset($tokens[0]);
	}
	$tokens = array_values($tokens);
}

function interpretline($tokens) {
	$nonwhitefound = false;
	$foundequal = -1;
	$commasbeforeequal = false;
	$foundtype = 0;
	$nestd = 0;
	$needmath = false;
	//scan for controls:  30 - for; 20 - if; 10 - where
	$cnt = count($tokens);
	
	for ($i=0; $i<$cnt; $i++) {
		$c = $tokens[$i];
		if (is_array($c)) {
			if ($c[0]==T_CURLY_OPEN || $c[0]==T_DOLLAR_OPEN_CURLY_BRACES) {
				$nestd++;
			} else if ($c[0] == T_FOR && $nestd == 0)  {
				if ($nonwhitefound) {
					echo "for loop used incorrectly - must be start of a line";
					return '';
				} else {
					//HANDLE FOR LOOP
					if ($foundtype < 30) {
						$foundtype = 30; //for
						$loc = $i;
						$beg = true;
					}
				}
			} else if ($c[0] == T_IF && $nestd == 0) {
				if ($foundtype < 20) {
					$foundtype = 20;
					$loc = $i;
					$beg = !$nonwhitefound;
				}
			} else if ($c[0] == T_STRING && $c[1] == 'where' && $nestd == 0) {
				if (!$nonwhitefound) {
					echo "'where' used incorrectly - must be used after a randomizer";
					return '';
				} else {
					if ($foundtype < 10) {
						$foundtype = 10;
						$loc = $i;
						$beg = false;
					}
				}
			} else if ($c[0] == T_STRING && ($c[1]=='e' || $c[1]=='pi' || $c[1]=='log' || $c[1]=='ln')) {
				$needmath = true;
			}
			if ($c[0] != T_WHITESPACE) {
				$nonwhitefound = true;
			}
		} else {
			    if ($c=='{') {
				    $nestd++;
			    } else if ($c=='}') {
				    $nestd--;
			    } else if ($c=='=' && $nestd==0) {
				    $foundequal = $i;
			    } else if ($c==',' && $foundequal==-1) {
				    $commasbeforeequal = true;
			    } else if ($c=='^' || $c=='!') {
				    $needmath = true;
			    }
		    }
	}
	if ($foundtype==0) {//no controls found - output line as written
		if ($commasbeforeequal) {
			array_splice($tokens,$foundequal-1,0,')');
			array_unshift($tokens,"list","(");
			$foundequal += 3;
		}
		return tokenstostring($tokens,$foundequal,false,$needmath);
		
	} else if ($beg) {  //control is at beginning - look for condition
		$i = $loc;
		do {
			$i++;
		} while (isset($tokens[$i]) && $tokens[$i] != '{');
		if (!isset($tokens[$i])) {
			echo 'code in {curly brackets} must follow the conditions for "for" and "if" statements';
			return '';
		}
		$conditional = array_slice($tokens,$loc+1,$i-$loc-1);
		$todo = array_slice($tokens,$i);
		
	} else { //control is in middle - look left for code, right for condition
		$todo = array_slice($tokens,0,$loc);
		$conditional = array_slice($tokens,$loc+1);
	}
	if ($foundtype==20) { //if
		return ('if ('.tokenstostring($conditional,-1,true).') {'.interpretlinesplitter($todo).'}');
	} else if ($foundtype==30) {//for
		
		if (preg_match('/^\s*\(\s*(\$\w+)\s*\=\s*(\d+|\$\w+)\s*\.\.\s*(\d+|\$\w+)\s*\)\s*$/',tokenstostring($conditional,-1,false,false),$matches)) {
			$forcond = array_slice($matches,1,3);
			return "for ({$forcond[0]}=intval({$forcond[1]});{$forcond[0]}<=round(floatval({$forcond[2]}),0);{$forcond[0]}++) {".interpretlinesplitter($todo)."}";
		} else {
			echo 'error with for code.. must be for ($var=a..b) where a and b are whole numbers or variables only';
			return '';
		}
	} else if ($foundtype==10) {//where
		return '$count=0; do{'.interpretlinesplitter($todo).'; $count++;} while (!('.tokenstostring($conditional,-1,true).')&&($count<200)); if ($count==200) {echo "where not met in 200 iterations";}';
	}
		
			
	
}

function tokenstostring($tokens,$equalloc,$skipfac = false,$domathphp=true) {
	$left = '';
	$right = '';
	$stm = microtime(true);
	$cnt = count($tokens);
	if (!$domathphp) {
		$lasttype = 0;  //1 number, 2 string, 3 var, 4 left paren
		for ($i=0; $i<$cnt; $i++) {
			$c = $tokens[$i];
			if (is_array($c)) {
				if ($c[0]==T_NUMBER) {
					$lasttype = 1;
				} else if ($c[0]==T_STRING) {  //implicit: 3sin
					if ($lasttype==1) {
						$tokens[$i][1] = '*'.$c[1];
					}
					//convert arcsin to asin
					$tokens[$i][1] = str_replace(array("arcsin","arccos","arctan","arcsinh","arccosh","arctanh"),array("asin","acos","atan","asinh","acosh","atanh"),$c[1]);
  
					$lasttype = 2;
				} else if ($c[0]==T_VAR) {  //implicit:  2$var
					if ($lasttype==1) {
						$tokens[$i][1] = '*'.$c[1];
					}
					$lasttype = 3;
				} else {
					$lasttype=0;
				}
			} else {
				if ($c == ')') {
					$lasttype = 4;
				} else if ($c == '(') {  //implicit: $var( )  3( )  ()()
					if ($lasttype==4 || $lasttype==3 || $lasttype==1) {
						$tokens[$i] = '*(';
					}
					$lasttype = 0;
				} else {
					$lasttype = 0;
				}
			}
		}
		
		
	}
	
	for ($i=0; $i<$cnt; $i++) {
		$c = $tokens[$i];
		if (is_array($c)) {
			if ($i<=$equalloc) {
				$left .= $c[1];
			} else {
				$right .= $c[1];
			}
		} else {
			if ($i<=$equalloc) {
				$left .= $c;
			} else {
				$right .= $c;
			}
		}
	}
	
	if ($domathphp) {
		$right = mathphp($right.' ',null,$skipfac);
		$right = preg_replace('/([a-zA-Z]+\d+)\*\(/',"$1(",$right);  
		$right = preg_replace('/(\$[a-zA-Z]+\d+)\(/',"$1*(",$right);  //lame bug fix for matrixrowcombine3(stuff) w/o affecting $a3(1-$b)
	} else {
		
		/*
		$stt = microtime(true);
		$right = str_replace('!=','#=',$right);
		//need to avoid doing in quotes
		preg_match_all('/(["\'])(?:\\\\?.)*?\\1/',$right,$strmatches,PREG_SET_ORDER);
		foreach ($strmatches as $k=>$match) {
			 $right =  str_replace($match[0],"(#$k#)",$right);
		}
		//this is super-abbreviated mathphp
		$right = str_replace(array("arcsin","arccos","arctan","arcsinh","arccosh","arctanh"),array("asin","acos","atan","asinh","acosh","atanh"),$right);
  
		$right = preg_replace('/(\$\w+)\(/',"$1*(",$right);
		$right= preg_replace('/([0-9])([\(])/',"$1*$2",$right);
		$right= preg_replace('/([0-9])\s*(\$[a-zA-Z]+)/',"$1*$2",$right);
		$right = preg_replace("/([0-9])(sin|cos|tan|sec|csc|cot|ln|log|exp|asin|acos|atan|sqrt|abs)/","$1*$2",$right);
		//$right = str_replace(')(',')*(',$right);
		$right = preg_replace('/([a-zA-Z]+\d+)\*\(/',"$1(",$right);  
		$right = preg_replace('/(\$[a-zA-Z]+\d+)\(/',"$1*(",$right);  //lame bug fix for matrixrowcombine3(stuff) w/o affecting $a3(1-$b)
	
		foreach ($strmatches as $k=>$match) {
			 $right =  str_replace("(#$k#)",$match[0],$right);
		}
		
		$right = str_replace('#=','!=',$right);
		$loopt += microtime(true) - $stt;
		*/
	}
	
	return $left.$right;
}		

		
/*		
function loadlibrary($str) {
	$str = str_replace(array("/",".",'"'),"",$str);
	$libs = explode(",",$str);
	$libdir = rtrim(dirname(__FILE__), '/\\') .'/libs/';
	foreach ($libs as $lib) {
		if (is_file($libdir . $lib.".php")) {
			include_once($libdir.$lib.".php");
		} else {
			echo "Error loading library $lib\n";	
		}
	}
}
*/
function setseed($ns) {
	if ($ns=="userid") {
		if (isset($GLOBALS['teacherid']) && isset($GLOBALS['teacherreview'])) { //reviewing in gradebook
			srand($GLOBALS['teacherreview']);	
		} else { //in assessment
			srand($GLOBALS['userid']); 
		}
	} else {
		srand($ns);
	}	
}

?>

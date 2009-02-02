<?php
//IMathAS:  IMathAS question interpreter.  Defines how the IMathAS question
//language works.
//(c) 2006 David Lippman
/*require_once("mathphp.php");
array_push($allowedmacros,"loadlibrary","array","where");
$disallowedwords = array("exit","die");
$disallowedvar = array('$link','$qidx','$qnidx','$seed','$qdata','$toevalqtxt','$la','$GLOBALS','$laparts','$anstype','$kidx','$iidx','$tips','$options','$partla','$partnum','$score');
*/
array_push($allowedmacros,"off","true","false","e","pi","null","setseed","if","for","where");
function interpret5($blockname,$anstype,$str)
{
	if ($blockname=="qtext") {
		$str = str_replace('"','\"',$str);
		$str = str_replace("\r\n","\n",$str);
		$str = str_replace("\n\n","<br/><br/>\n",$str);
		return $str;
	} else {
		$str = str_replace(array('\\frac','\\tan','\\root','\\vec'),array('\\\\frac','\\\\tan','\\\\root','\\\\vec'),$str);
		$str .= ' ';
		$str = str_replace("\r\n","\n",$str);
		$str = str_replace("&&\n","<br/>",$str);
		$str = str_replace("&\n"," ",$str);
		return interpretline($str.';');	
	}
}

function interpretline($str) {
	$str .= ';';
	$bits = array();
	$lines = array();
	$len = strlen($str);
	$cnt = 0;
	$ifloc = -1;
	$forloc = -1;
	$whereloc = -1;
	$lastsym = '';
	$lasttype = -1;
	$closeparens = false;
	$symcnt = 0;
	$syms = tokenize($str);
	$k = 0;
	$symlen = count($syms);
	while ($k<$symlen) {
		list($sym,$type) = $syms[$k];//getsymbol($str,$i);
		//first handle stuff that would use last symbol; add it if not needed
		if ($sym=='^' && $lastsym!='') {
			$bits[] = 'safepow(';
			$bits[] = $lastsym;
			$bits[] = ',';
			$k++;
			list($sym,$type) = $syms[$k];//getsymbol($str,$i);
			$closeparens = true;
			$lastsym='^';
			$lasttype = 0;
		} else if ($sym=='!' && $lasttype!=0 && $lastsym!='' && $syms[$k+1]{0}!='=') {
			$bits[] = 'factorial(';
			$bits[] = $lastsym;
			$bits[] = ')';
			$sym = '';
		}  else {
			$bits[] = $lastsym;	
		}
		if ($closeparens==true && $lastsym!='^' && $lasttype!=0) {
			$bits[] = ')';
			$closeparens = false;
		}
		
		
		if ($sym=='=' && $ifloc==-1 && $whereloc==-1 && $lastsym!='<' && $lastsym!='>' && $lastsym!='!') {
			$j = count($bits)-1;
			$hascomma = false;
			while ($j>=0) {
				if ($bits[$j]==',') {
					$hascomma = true;
					break;
				}
				$j--;
			}
			if ($hascomma) {
				array_unshift($bits,"list(");
				array_push($bits,')');
				$hascomma = false;
			}
		} else if ($type==7) {//end of line
			//$bits[] = $sym;
			if ($lasttype=='7') {
				$k++;
				continue;
			}
			if ($forloc>-1) {
				$j = $forloc; 
				while ($bits[$j]{0}!='{' && $j<count($bits)) {
					$j++;
				}
				$cond = implode('',array_slice($bits,$forloc+1,$j-$forloc-1));
				$todo = implode('',array_slice($bits,$j));
				//might be $a..$b or 3.*.4  (remnant of implicit handling)
				if (preg_match('/^\s*\(\s*(\$\w+)\s*\=\s*(\d+|\$\w+)\s*\.\*?\.\s*(\d+|\$\w+)\s*\)\s*$/',$cond,$matches)) {
					$forcond = array_slice($matches,1,3);
					$bits = array( "for ({$forcond[0]}=intval({$forcond[1]});{$forcond[0]}<=round(floatval({$forcond[2]}),0);{$forcond[0]}++) ".$todo."");
				} else {
					print_r($bits);
					echo 'error with for code.. must be for ($var=a..b) where a and b are whole numbers or variables only';
					return array(array('',9));
				}
			} else if ($ifloc == 0) {
				$j = 0; 
				while ($bits[$j]{0}!='{' && $j<count($bits)) {
					$j++;
				}
				if ($j==count($bits)) {
					echo "need curlys for if statement at beginning of line";
					exit;
				}
				$cond = implode('',array_slice($bits,1,$j-2));
				$todo = implode('',array_slice($bits,$j));
				$bits = array("if ($cond) $todo");
			} 
			if ($whereloc>0) {
				if ($ifloc>-1 && $ifloc<$whereloc) {
					echo 'line of type $a=b if $c==0 where $d==0 is invalid';
					exit;
				} 
				$wheretodo = implode('',array_slice($bits,0,$whereloc));
				
				if ($ifloc>-1) {
					$wherecond = implode('',array_slice($bits,$whereloc+1,$ifloc-$whereloc-1));
					$ifcond = implode('',array_slice($bits,$ifloc+1));
					$bits = array('if ('.$ifcond.') {$count=0;do{'.$wheretodo.';$count++;} while (!('.$wherecond.') && $count<200); if ($count==200) {echo "where not met in 200 iterations";}}');
				} else {
					$wherecond = implode('',array_slice($bits,$whereloc+1));
					$bits = array('$count=0;do{'.$wheretodo.';$count++;} while (!('.$wherecond.') && $count<200); if ($count==200) {echo "where not met in 200 iterations";}');
				}
				
				
			} else if ($ifloc > 0) {
				$todo = implode('',array_slice($bits,0,$ifloc));
				$cond = implode('',array_slice($bits,$ifloc+1));
				
				
				$bits = array("if ($cond) { $todo }");	
			}
			
			$forloc = -1;
			$ifloc = -1;
			$whereloc = -1;
			$lines[] = implode('',$bits);
			$bits = array();
		} else if ($type==1) { //is var
			//implict 3$a and $a $b
			if ($lasttype==3 || $lasttype==1) {
				$bits[] = '*';
			}
		} else if ($type==2) { //is func
			if ($lasttype==3 || $lasttype==1) {
				$bits[] = '*';
			}
		} else if ($type==3) { //is num
			if ($lasttype==3 || $lasttype == 1) {
				$bits[] = '*';
			}
			
		} else if ($type==4) { //is parens
			if ($lasttype==3 || $lasttype==4 || $lasttype==1) {
				$bits[] = '*';
			}
		} else if ($type==8) { //is control
			if ($sym=='if') {
				$ifloc = count($bits);
			} else if ($sym=='where') {
				$whereloc = count($bits);
			} else if ($sym=='for') {
				$forloc = count($bits);
			}
		} else if ($type==9) {//is error
			return 'error';
		}
		
		$lastsym = $sym;
		$lasttype = $type;
		$cnt++;
		$k++;
	}
	if (count($bits)>0) {
		$lines[] = implode('',$bits);
	}
	return implode(';',$lines);
}

//get tokens
//eat up extra whitespace at end
//return array of arrays: array($symbol,$symtype)
//types: 1 var, 2 funcname (w/ args), 3 num, 4 parens, 5 curlys, 6 string, 7 endofline, 8 control, 9 error, 0 other
function tokenize($str) {
	global $allowedmacros;
	global $mathfuncs;
	global $disallowedwords,$disallowedvar;
	$i = 0;
	$connecttolast = false;
	$len = strlen($str);
	$syms = array();
	while ($i<$len) {
		$intype = 0;
		$out = '';
		$c = $str{$i};
		$len = strlen($str);
		if ($c=='/' && $str{$i+1}=='/') { //comment
			while ($c!="\n" && $i<$len) {
				$i++;
				$c = $str{$i};
			}
			$i++;
			$c = $str{$i};
			$intype = 7;
		} else if ($c=='$') { //is var
			$intype = 1;
			do {
				$out .= $c;
				$i++;
				if ($i==$len) {break;}
				$c = $str{$i};
			} while ($c>="a" && $c<="z" || $c>="A" && $c<="Z" || $c>='0' && $c<='9' || $c=='_');
			if (in_array($out,$disallowedvar)) {
				echo "Eeek.. unallowed var $out!";
				return array(array('',9));
			}
		
		} else if ($c>="a" && $c<="z" || $c>="A" && $c<="Z") { //is str
			$intype = 2; //string like function name
			do {
				$out .= $c;
				$i++;
				if ($i==$len) {break;}
				$c = $str{$i};
			} while ($c>="a" && $c<="z" || $c>="A" && $c<="Z" || $c>='0' && $c<='9' || $c=='_');
			if ($out=='if' || $out=='where' || $out=='for') {
				$intype = 8;
			} else if ($out=='e') {
				$out = "exp(1)";
				$intype = 3;
			} else if ($out=='pi') {
				$out = "(M_PI)";
				$intype = 3;
			} else if ($out=='userid') {
				$out = '"userid"';
				$intype = 6;
			} else {
				while ($c==' ') {
					$i++;
					$c = $str{$i};
				}
				if ($c=='(' && $out!='e' && $out!='pi') {
					if ($out=='log') {
						$out = 'log10';
					} else if ($out=='ln') {
						$out = 'log';
					}
					$out = str_replace(array("arcsin","arccos","arctan","arcsinh","arccosh","arctanh"),array("asin","acos","atan","asinh","acosh","atanh"),$out);
	  
					//list($i,$sym,$type) = getsymbol($str,$i);
					//$out .= $sym;
					$connecttolast = true;
				} else {
					if ($c=='^' && substr($str,$i+1,2)=='-1') {
						$i += 3;
						$out = 'a'.$out;
					} else if ($c=='^' && substr($str,$i+1,4)=='(-1)') {
						$i += 5;
						$out = 'a'.$out;
					} else {
						if (!in_array($out,$allowedmacros)) {
							echo "Eeek.. unallowed macro {$out}";
							return array(array('',9));
						}
					}
				}
			}
		} else if (($c>='0' && $c<='9') || ($c=='.' &&($str{$i+1}>='0' && $str{$i+1}<='9')) ) { //is num
			$intype = 3; //number
			$cont = true;
			do {
				$out .= $c;
				$lastc = $c;
				$i++;
				if ($i==$len) {break;}
				$c= $str{$i};
				if (($c>='0' && $c<='9') || ($c=='.' && $lastc!='.')) {
					//is still num
				} else if ($c=='e' || $c=='E') {
					$d = $str{$i+1};
					if ($d>='0' && $d<='9') {
						$out .= $c;
						$i++;
						if ($i==$len) {break;}
						$c= $str{$i};
					} else if ($d=='-' && ($str{$i+2}>='0' && $str{$i+2}<='9')) {
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
		} else if ($c=='(' || $c=='{') { //parens or curlys
			if ($c=='(') {
				$intype = 4; //parens
				$leftb = '(';
				$rightb = ')';
			} else if ($c=='{') {
				$intype = 5; //curlys
				$leftb = '{';
				$rightb = '}';
			}
			$thisn = 1;
			$inq = false;
			$j = $i+1;
			$len = strlen($str);
			while ($j<$len) {
				$d = $str{$j};
				if ($inq) {
					if ($d==$qtype && $str{$j-1}!='\\') {
						$inq = false;
					}
				} else {
					if ($d=='"' || $d=="'") {
						$inq = true;
						$qtype = $d;
					} else if ($d==$leftb) {
						$thisn++;
					} else if ($d==$rightb) {
						$thisn--;
						if ($thisn==0) {
							$inside = interpretline(substr($str,$i+1,$j-$i-1));
							if ($inside=='error') {
								return array(array('',9));
							}
							$out .= $leftb.$inside.$rightb;
							$i= $j+1;
							break;
						}
					} else if ($d=="\n") {
						echo "unmatched parens/brackets - likely will cause an error";
					}
				}
				$j++;
			}
			if ($j==$len) {
				$i = $j;
				echo "unmatched parens/brackets - likely will cause an error";
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
			$i++;
			$c = $str{$i};
		} else if ($c=="\n") {
			$intype = 7;
			$i++;
			$c = $str{$i};
		} else if ($c==';') {
			$intype = 7;
			$i++;
			$c = $str{$i};
		} else {
			$out .= $c;
			$i++;
			$c = $str{$i};
		}
		while ($c==' ') { //eat up extra whitespace
			$i++;
			if ($i==$len) {break;}
			$c = $str{$i};
		}
		if ($connecttolast && $intype!=2) {
			$syms[count($syms)-1][0] .= $out;
			$connecttolast = false;
		} else {
			$syms[] =  array($out,$intype);
		}
		
	}
	return $syms;
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

<?php
//IMathAS:  IMathAS question interpreter.  Defines how the IMathAS question
//language works.
//(c) 2006 David Lippman

//TODO:  handle for ($i=0..2) { to handle expressions, array var, etc. for 0 and 2

$GLOBALS['mathfuncs'] = array("sin","cos","tan","sec","csc","cot",
 "sinh","cosh","tanh","sech","csch","coth",
 "arcsin","arccos","arctan","arcsec","arccsc","arccot",
 "arcsinh","arccosh","arctanh","arcsech","arccsch","arccoth",
 "sqrt","ceil","floor","round","log","ln","abs","max","min","count");
if (!isset($GLOBALS['allowedmacros'])) {
    $GLOBALS['allowedmacros'] = $GLOBALS['mathfuncs'];
}

array_push($GLOBALS['allowedmacros'],"loadlibrary","importcodefrom","includecodefrom","array","off","true","false","e","pi","null","setseed","if","for","where");
$GLOBALS['disallowedvar'] = array('$link','$qidx','$qnidx','$seed','$qdata','$toevalqtxt','$la',
  '$laarr','$shanspt','$GLOBALS','$laparts','$anstype','$kidx','$iidx','$tips',
  '$optionsPack','$partla','$partnum','$score','$disallowedvar','$allowedmacros','$allowedConstants',
  '$wherecount','$whilecount','$forloopcnt','$_forends','$countcnt','$myrights','$myspecialrights',
  '$this', '$quesData', '$toevalsoln', '$doShowAnswer', '$doShowAnswerParts','$teacherInGb',
  '$_SERVER','$_POST','$_GET','$_FILES','$_REQUEST','$_SESSION','$_COOKIE','$_ENV','$DBH','$stm');

//main interpreter function.  Returns PHP code string, or HTML if blockname==qtext
function interpret($blockname,$anstype,$str,$countcnt=1,$included_qs=[])
{
    if ($countcnt==1) {
        $GLOBALS['interpretcurvars'] = [];
        $GLOBALS['interpretcurarrvars'] = [];
    }
	if ($blockname=="qtext") {
		$str = preg_replace_callback('/(include|import)qtextfrom\((\d+)\)/',function($m) use ($included_qs) { return getquestionqtext($m, $included_qs); }, $str);
		$str = str_replace('"','\"',$str);
		$str = str_replace("\r\n","\n",$str);
		$str = str_replace("\n\n","<br/><br/>\n",$str);
		$str = removeDisallowedVarsString($str,$anstype,$countcnt,'"',$included_qs);
		return $str;
	} else {
		$str = str_replace(array('\\frac','\\tan','\\root','\\vec'),array('\\\\frac','\\\\tan','\\\\root','\\\\vec'),$str);
		$str .= ' ';
		$str = str_replace("\t", ' ', $str);
		$str = str_replace("\r\n","\n",$str);
		$str = str_replace("&&\n","<br/>",$str);
        $str = preg_replace('/&\s*\n/', ' ', $str);
        $r =  interpretline($str.';',$anstype,$countcnt,$included_qs).';';
		if ($r === 'error;') { 
			return $r;
		}
        if ($countcnt==1) {
            $r = 'loopreset();' . $r;
        }
        if ($countcnt==1 && count($GLOBALS['interpretcurvars']) > 0) {
            $r = genVarInit(array_unique($GLOBALS['interpretcurvars'])) . $r;
        }
        if ($countcnt==1) {
            // independent check of the final PHP, in case the transpiler
            // above emitted something it should not have
            $err = validateInterpretedCode($r);
            if ($err !== '') {
                echo sprintf(_('Eeek.. unsafe code: %s'), Sanitize::encodeStringForDisplay($err));
                return 'error;';
            }
        }
		return $r;
	}
}

function getquestionqtext($m, $included_qs) {
	global $DBH;
	if (in_array($m[2], $included_qs)) {
		echo 'Error: circular reference in includeqtextfrom';
		return '';
	}
	$stm = $DBH->prepare("SELECT qtext FROM imas_questionset WHERE id=:id");
	$stm->execute(array(':id'=>$m[2]));
	$row = $stm->fetch(PDO::FETCH_NUM);
	if ($row === false) {
		echo _('bad question id in includeqtextfrom');
		return "";
	} else {
		$str = $row[0];
		$included_qs[] = $m[2];
		$str = preg_replace_callback('/(include|import)qtextfrom\((\d+)\)/',function($m) use ($included_qs) { return getquestionqtext($m, $included_qs); }, $str);
		return $str;
	}
}
//interpreter some code text.  Returns a PHP code string.
function interpretline($str,$anstype,$countcnt,$included_qs=[]) {
	$str = trim($str);
	$str .= ';';
	$bits = array();
	$lines = array();
	$len = strlen($str);
	$cnt = 0;
	$ifloc = -1;
	$elseloc = array();
	$forloc = -1;
    $foreachloc = -1;
	$whereloc = -1;
	$whileloc = -1;
	$hdrat = array(); //bit indexes of (...) tokens directly following a control word
	$braceat = array(); //bit indexes of {...} tokens
	$lastsym = '';
	$lasttype = -1;
	$closeparens = 0;
	$sawequals = false;
	$symcnt = 0;
	//get tokens from tokenizer
    $syms = tokenize($str,$anstype,$countcnt,$included_qs);;
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
		} else if ($sym=='!' && $lasttype!=0 && $lastsym!='' && $lasttype!=8 && $lasttype!=12) {
			//convert a! to factorial(a), avoiding if(!a) and a!=b and if !a
			$bits[] = 'factorial(';
			$bits[] = $lastsym;
			$bits[] = ')';
			$lastsym = '';
			$lasttype = 2;
			$k++;
			$cnt++;
			continue;
		} else if ($lasttype==2 && $type==4 && substr($lastsym,0,5)=='root(') {
			$bits[] = substr($lastsym,0,-1).',';
			$sym = substr($sym,1);
			$lasttype = 0;
		} else {
			//add last symbol to stack
			if ($lasttype!=7 && $lasttype!=-1 && $lastsym !== '') {
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


		if ($sym=='=' && $ifloc==-1 && $whereloc==-1 && $lastsym!='<' && $lastsym!='>' && $lastsym!='!' && $lastsym!='=' && $syms[$k+1][0]!='=' && $syms[$k+1][0]!='>') {
			if ($lasttype == 2 || (count($bits)>0 && $bits[count($bits)-1] == ')')) {
				// can't assign to function
				echo _('error: cannot assign to a function');
				return 'error';
			}
			$sawequals = true;
			//if equality equal (not comparison or array assn), and before if/where.
			//check for commas to the left, convert $a,$b =  to list($a,$b) =
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
			if ($lasttype=='7' || $lasttype==-1) {
                //nothing exciting, so just continue
                $lines[] = '';
				$k++;
				continue;
			}
    
			//check for for, if, where and rearrange bits if needed
			if ($forloc===0) {
				//convert for($i=a..b) {todo}
				$split = interpretSplitBlock($bits, $forloc, count($bits), $hdrat, $braceat);
				if ($split === false) {
					echo _('error with for code.. must be "for ($var=a..b) {todo}" where a and b are whole numbers or variables only');
					return 'error';
				}
				list($cond, $todo) = $split;

				//might be $a..$b or 3.*.4  (remnant of implicit handling)
				//if (preg_match('/^\s*\(\s*(\$\w+)\s*\=\s*(-?\d+|\$[\w\[\]]+)\s*\.\s?\.\s*(-?\d+|\$[\w\[\]]+)\s*\)\s*$/',$cond,$matches)) {
                if (preg_match('/^\s*\(\s*(\$\w+)\s*\=\s*(.*?)\s*\.\s?\.\s*(.*?)\s*\)\s*$/',$cond,$matches)) {
					$forcond = array_slice($matches,1,3);
					if (!interpretParensBalanced($forcond[1]) || !interpretParensBalanced($forcond[2])) {
						echo _('error with for code.. parentheses must be balanced within each part of the for loop');
						return 'error';
					}
					$bits = array( "if (is_nan({$forcond[2]}) || is_nan({$forcond[1]})) {echo 'part of for loop is not a number';} else {for ({$forcond[0]}=(int)ceil(round(floatval({$forcond[1]}),4)),loopstart('for',{$countcnt});{$forcond[0]}<=(int)floor(round(floatval({$forcond[2]}),4)) && loopstep('for',{$countcnt}); {$forcond[0]}++) {".$todo.";};}");
				} else if (preg_match('/^\s*\(\s*([^;]*?);\s*([^;]*?);\s*([^;]*?)\s*\)\s*$/',$cond,$matches)) {
					$forcond = array_slice($matches,1,3);
					if (!interpretParensBalanced($forcond[0]) || !interpretParensBalanced($forcond[1]) || !interpretParensBalanced($forcond[2])) {
						echo _('error with for code.. parentheses must be balanced within each part of the for loop');
						return 'error';
					}
					$bits = array( "for ({$forcond[0]},loopstart('for',{$countcnt});({$forcond[1]}) && loopstep('for',{$countcnt}); ({$forcond[2]})) {".$todo.";};");
				} else {
					echo _('error with for code.. must be "for ($var=a..b) {todo}" where a and b are whole numbers or variables only');
					return 'error';
				}
			} else if ($foreachloc===0) {
				//convert foreach($arr AS $k=>$v) {todo}
				$split = interpretSplitBlock($bits, $foreachloc, count($bits), $hdrat, $braceat);
				if ($split === false) {
					echo _('error with foreach code.. must be "foreach ($arr as $a=>$b) {todo}" where $arr, $a and $b are variables only');
					return 'error';
				}
				list($cond, $todo) = $split;

				//should be $arr as $k=>$v
				if (preg_match('/^\s*\(\s*(\$\w+)\*?\s*as\s*(\$\w+)\s*=>\s*(\$\w+)\s*\)\s*$/i',$cond,$matches)) {
					$foreachcond = array_slice($matches,1,3);
					$bits = array("if (!is_array({$foreachcond[0]})) {echo 'input of foreach must be an array';} else {loopstart('for',{$countcnt});foreach ({$foreachcond[0]} as {$foreachcond[1]}=>{$foreachcond[2]}) {if (!loopstep('for',{$countcnt})) { break; }{ $todo ;}};}");
				} else {
					echo _('error with foreach code.. must be "foreach ($arr as $a=>$b) {todo}" where $arr, $a and $b are variables only');
					return 'error';
				}
			} else if ($whileloc === 0) {
				//convert while(cond) {todo}
				$split = interpretSplitBlock($bits, $whileloc, count($bits), $hdrat, $braceat);
				if ($split === false) {
					echo _('error with for code.. must be "while(condition) {todo}"');
					return 'error';
				}
				list($cond, $todo) = $split;

                if ($todo !== '' && $cond !== '') {
					$bits = array("loopstart('while',{$countcnt});while (({$cond}) && loopstep('while',{$countcnt})) {{$todo};};");
				} else {
					echo _('error with for code.. must be "while(condition) {todo}"');
					return 'error';
				}
			} else if ($ifloc == 0) {
				//this is if at beginning of line, form:  if ($a==3) {todo}
				$end = count($elseloc)==0 ? count($bits) : $elseloc[0][0];
				$split = interpretSplitBlock($bits, 0, $end, $hdrat, $braceat);
				if ($split === false) {
					echo _('need curlys for if statement at beginning of line');
					return 'error';
				}
				list($cond, $todo) = $split;
				$out = "if ($cond) { $todo ;}";

				for ($i=0; $i<count($elseloc); $i++) {
					$kw = $elseloc[$i][0];
					$end = ($i==count($elseloc)-1) ? count($bits) : $elseloc[$i+1][0];
					if ($elseloc[$i][1]=='elseif') {
						$split = interpretSplitBlock($bits, $kw, $end, $hdrat, $braceat);
						if ($split === false) {
							echo _('need curlys for elseif statement');
							return 'error';
						}
						list($cond, $todo) = $split;
						if ($cond === '') {
							echo _('need condition for elseif');
							return 'error';
						}
						$out .= " else if ($cond) { $todo ;}";
					} else { //else: no condition, so the body starts at a { if there is one
						$j = $kw + 1;
						while ($j<$end && !isset($braceat[$j])) {
							$j++;
						}
						if ($j == $end) {
							$j = $kw + 1;
						}
						$todo = implode('',array_slice($bits,$j,$end-$j));
						$out .= " else { $todo ;}";
					}
				}
				$bits = array($out);

			} else if (count($elseloc)>1 || (count($elseloc)==1 && $whereloc==-1)) {
				echo _('else used without leading if statement');
				return 'error';
			}
			if ($whereloc>0) {
				//handle $a = rand() where ($a==b)
				if ($ifloc>-1 && $ifloc<$whereloc) {
					echo _('line of type $a=b if $c==0 where $d==0 is invalid');
					return 'error';
				}
				$wheretodo = implode('',array_slice($bits,0,$whereloc));
				if ($whereloc+1 >= count($bits)) {
					echo _('missing condition after where');
					return 'error';
				}

				if ($ifloc>-1) {
					//handle $a = rand() where ($a==b) if ($c==0)
					$wherecond = implode('',array_slice($bits,$whereloc+1,$ifloc-$whereloc-1));
					$ifcond = implode('',array_slice($bits,$ifloc+1));
						$bits = array("if ({$ifcond}) {loopstart('where',{$countcnt});do{{$wheretodo};} while (!({$wherecond}) && loopstep('where',{$countcnt}));}");
				} else if (count($elseloc)==1 && $elseloc[0][1]=='else' && $elseloc[0][0]>$whereloc) {
                    $wherecond = implode('',array_slice($bits,$whereloc+1,$elseloc[0][0]-$whereloc-1));
                    $elsetodo = implode('',array_slice($bits, $elseloc[0][0]+1));
                    $bits = array("loopstart('where',{$countcnt},true);do{{$wheretodo};} while (!({$wherecond}) && loopstep('where',{$countcnt})); if (loopcut('where',{$countcnt})) {{$elsetodo};};");
                } else {
					$wherecond = implode('',array_slice($bits,$whereloc+1));
						$bits = array("loopstart('where',{$countcnt});do{{$wheretodo};} while (!({$wherecond}) && loopstep('where',{$countcnt}));");
				}

			} else if ($ifloc > 0) {
				//handle $a = b if ($c==0)
				$todo = implode('',array_slice($bits,0,$ifloc));
				$cond = implode('',array_slice($bits,$ifloc+1));
				if ($cond === '') {
					//also happens if the if follows a for/foreach/while statement
					echo _('missing condition after if');
					return 'error';
				}

				$bits = array("if ($cond) { $todo ; }");
			}

			$forloc = -1;
            $foreachloc = -1;
			$ifloc = -1;
			$whereloc = -1;
			$whileloc = -1;
			$hdrat = array();
			$braceat = array();
			$sawequals = false;
			$elseloc = array();
			//collapse bits to a line, add to lines array
			$lines[] = implode('',$bits);
			$bits = array();
		} else if ($type==1) { //is var
			if ($sawequals && substr($sym,-2)=='[]') {
				echo 'Error: cannot use [] for reading';
				return 'error';
			}
			//implict 3$a and $a $b and (3-4)$a
			if ($lasttype==3 || $lasttype==1 || $lasttype==4) {
				$bits[] = '*';
			}
		} else if ($type==2) { //is func
			//implicit $v sqrt(2) and 3 sqrt(3) and (2-3)sqrt(4) and sqrt(2)sqrt(3)
			if ($lasttype==3 || $lasttype==1 || $lasttype==4 || $lasttype==2 ) {
				$bits[] = '*';
			}
		} else if ($type==3) { //is num
			if ($lastsym=='break' || $lastsym=='continue') {
				// allow break 2 to work
				// TODO: check break level isn't too high
				$sym = ' ' . $sym;
			}
			//implicit 2 pi and $var pi
			if ($lasttype==3 || $lasttype == 1) {
				$bits[] = '*';
			}

		} else if ($type==4) { //is parens
			//implicit 3(4) (5)(3)  $v(2)
			if ($lasttype==3 || $lasttype==4 || $lasttype==1) {
				$bits[] = '*';
			} else if ($lasttype==8) {
				//possible header of a control statement: if (cond), for (...), etc.
				$hdrat[count($bits)] = true;
			}
		} else if ($type==5) { //is curly
			$braceat[count($bits)] = true;
		} else if ($type==8) { //is control
			//mark location of control symbol
			if ($sym=='if') {
				$ifloc = count($bits);
			} else if ($sym=='where') {
				$whereloc = count($bits);
			} else if ($sym=='while') {
				$whileloc = count($bits);
				if ($whileloc !== 0) {
					echo _('invalid use of while.. must be "while (condition) {todo}".');
					return 'error';
				}
			} else if ($sym=='for') {
				$forloc = count($bits);
			} else if ($sym=='foreach') {
				$foreachloc = count($bits);
			} else if ($sym=='else' || $sym=='elseif') {
				$elseloc[] = array(count($bits),$sym);
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
	return implode(";\n",$lines);
}

//Splits the bits of a block statement (if, elseif, for, foreach, while) into
//its condition and its body.
//  $bits: bits of the line, $kw: index of the keyword bit,
//  $end: index just past the last bit of this statement (or branch of an if),
//  $hdrat, $braceat: sets of bit indexes of (...) tokens that directly follow a
//    control word, and of {...} tokens
//Returns array($cond, $todo), or false if the statement has no usable form.
//If there is a {...} body, then the condition is everything between the keyword and
//the {, and can be any expression:  if $a==1 {todo}  or  if ($a)||($b) {todo}
//If not, the condition has to be the single (...) token after the keyword, and the
//body is the rest:  if ($a==1) todo
function interpretSplitBlock($bits, $kw, $end, $hdrat, $braceat) {
	$j = $kw + 1;
	while ($j<$end && !isset($braceat[$j])) {
		$j++;
	}
	if ($j < $end) {
		return array(
			implode('',array_slice($bits,$kw+1,$j-$kw-1)),
			implode('',array_slice($bits,$j,$end-$j))
		);
	}
	if (isset($hdrat[$kw+1]) && $kw+2<$end && strlen($bits[$kw+1])>2) {
		$j = $kw + 2;
		if ($bits[$j]=='*') { //implicit multiplication between header and body
			$j++;
		}
		return array($bits[$kw+1], implode('',array_slice($bits,$j,$end-$j)));
	}
	return false;
}

//get tokens
//eat up extra whitespace at end
//return array of arrays: array($symbol,$symtype)
//types: 1 var, 2 funcname (w/ args), 3 num, 4 parens, 5 curlys, 6 string, 
// 7 endofline, 8 control, 9 error, 0 other, 11 array index [], 12 comparison
function tokenize($str,$anstype,$countcnt,$included_qs=[]) {
	global $DBH, $allowedmacros;
	global $mathfuncs;
	global $disallowedvar;
	$allowedConstants = interpretAllowedConstants();
	$i = 0;
	$connecttolast = 0;
	$len = strlen($str);
	$syms = array();
	$lastsym = array('',-1);
	while ($i<$len) {
		$intype = 0;
		$out = '';
		$c = $str[$i];
		$len = strlen($str);
        if ($c=='/' && $str[$i+1]=='*') { //comment block
            while ($i < $len) {
                if ($c == '/' && $i > 0 && $str[$i-1] == '*') {
                    $i++;
                    $c = $str[$i];
                    break;
                }
                $i++;
                if ($i<$len) {
                    $c = $str[$i];
                }
            }
        }
		if (($c=='/' && $str[$i+1]=='/') || $c=='#') { //comment
			while ($c!="\n" && $i<$len) {
                $i++;
                if ($i<$len) {
                    $c = $str[$i];
                }
            }
            $i++;
            if ($i<$len) {
                $c = $str[$i];
            }
			$intype = 7;
		} else if ($c=='$') { //is var
			$intype = 1;
			//read to end of var
			do {
				$out .= $c;
				$i++;
				if ($i==$len) {break;}
				$c = $str[$i];
			} while ($c>="a" && $c<="z" || $c>="A" && $c<="Z" || $c>='0' && $c<='9' || $c=='_');
			//if [ then array ref - read and connect as part of variable token
			if ($c=='[' || $c=='{') {
				$connecttolast = 1;
			}
			//check if allowed var
			if ($out==='$' && $connecttolast==0) {
				echo _('Variable missing a name');
				return array(array('',9));
			}
			if (in_array($out,$disallowedvar)) {
				echo sprintf(_('Eeek.. unallowed var %s!'), Sanitize::encodeStringForDisplay($out));
				return array(array('',9));
			}
            if ($out !== '$') {
                $GLOBALS['interpretcurvars'][] = $out;
            }

		} else if ($c>="a" && $c<="z" || $c>="A" && $c<="Z" || $c=='_') { //is str
			$intype = 2; //string like function name
			do {
				$out .= $c;
				$i++;
				if ($i==$len) {break;}
				$c = $str[$i];
			} while ($c>="a" && $c<="z" || $c>="A" && $c<="Z" || $c>='0' && $c<='9' || $c=='_');
			//check if it's a special word, and set type appropriately if it is
			if ($out=='if' || $out=='where' || $out=='for' || $out=='foreach' || $out=='while' || $out=='break' || $out=='continue') {
				$intype = 8;
			} else if ($out=='else' || $out=='elseif') {
				$intype = 8;
				if ($out=='else' && substr($str,$i,3)==' if') {
					$out = 'elseif';
					$i += 3;
				}
				while ($lastsym[1]==7) {
					array_pop($syms);
					$lastsym = $syms[count($syms)-1];
				}
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
				//eat whitespace
				while ($c==' ') {
					$i++;
					$c = $str[$i];
				}
				//could be sin^-1 or sin^(-1) - check for them and rewrite if needed
				if ($c=='^' && substr($str,$i+1,2)=='-1') {
					$i += 3;
					$out = 'arc'.$out;
					$c = $str[$i];
					while ($c==' ') {
						$i++;
						$c = $str[$i];
					}
				} else if ($c=='^' && substr($str,$i+1,4)=='(-1)') {
					$i += 3;
					$out = 'arc'.$out;
					$c = $str[$i];
					while ($c==' ') {
						$i++;
						$c = $str[$i];
					}
				}
				//if there's a ( then it's a function
				if ($c=='(' && $out!='e' && $out!='pi') {
					//rewrite logs
					if ($out=='log') {
						$out = 'log10';
					} else if ($out=='ln') {
						$out = 'log';
					} else if ($out=='is_numeric') {
                        $out = 'is_nicenumber';  
                    } else if ($out=='rand') {
						$out = '$GLOBALS[\'RND\']->rand';
					} else {
                        $out = preg_replace('/(ar|arg)(sinh|cosh|tanh|sech|csch|coth)/', 'arc$2', $out);
						//check it's and OK function
						if (!in_array($out,$allowedmacros)) {
							echo sprintf(_('Eeek.. unallowed macro %s'), Sanitize::encodeStringForDisplay($out));
							return array(array('',9));
						}
					}
					//rewrite arctrig into atrig for PHP
					$out = str_replace(array("arcsinh","arccosh","arctanh","arcsech","arccsch","arccoth","arcsin","arccos","arctan","arcsec","arccsc","arccot"),array("asinh","acosh","atanh","asech","acsch","acoth","safeasin","safeacos","atan","asec","acsc","acot"),$out);

					//connect upcoming parens to function
					$connecttolast = 2;
				} else {
					//not a function, so what is it?
                    $outlower = strtolower($out);
					if ($outlower=='true' || $outlower=='false' || $outlower=='null' || $outlower=='as' ||
						in_array($out, $allowedConstants)
					) {
						//we like this - it's an acceptable unquoted string
					} else {//
						//an unquoted string!  give a warning to instructor,
						//but treat as a quoted string.
						if (!empty($GLOBALS['inQuestionTesting'])) {
							echo sprintf(_('Warning... unquoted string %s.. treating as string'), Sanitize::encodeStringForDisplay($out));
						}
						$out = "'$out'";
						$intype = 6;
					}

				}
			}
		} else if (($c>='0' && $c<='9') || ($c=='.'  && ($str[$i+1]>='0' && $str[$i+1]<='9')) ) { //is num
			$intype = 3; //number
			$cont = true;
			//handle . 3 which needs to act as concat
			if ($lastsym[0]=='.') {
				$syms[count($syms)-1][0] .= ' ';
			}
			do {
				$out .= $c;
				$lastc = $c;
				$i++;
				if ($i==$len) {break;}
				$c= $str[$i];
				if (($c>='0' && $c<='9') || ($c=='.' && $str[$i+1]!='.' && $lastc!='.')) {
					//is still num
				} else if ($c=='e' || $c=='E') {
					//might be scientific notation:  5e6 or 3e-6
					$d = $str[$i+1];
					if ($d>='0' && $d<='9') {
						$out .= $c;
						$i++;
						if ($i==$len) {break;}
						$c= $str[$i];
					} else if (($d=='-'||$d=='+') && ($str[$i+2]>='0' && $str[$i+2]<='9')) {
						$out .= $c.$d;
						$i+= 2;
						if ($i>=$len) {break;}
						$c= $str[$i];
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
            $newcnt = 0;
			$lastwascomma = false;
			while ($j<$len) {
				//read terms until we get to right bracket at same nesting level
				//we have to avoid strings, as they might contain unmatched brackets
				$d = $str[$j];
				if ($inq) {  //if inquote, leave if same marker (not escaped)
					if ($d==$qtype && $str[$j-1]!='\\') {
						$inq = false;
					}
				} else {
					if ($d == ',') {
						if ($lastwascomma) {
							if (!empty($GLOBALS['inQuestionTesting'])) {
								echo _('Fatal error: cannot have empty or missing array elements or function arguments');
							}
							return array(array('',9));
						}
						$lastwascomma = true;
					} else if ($d != ' ') {
						$lastwascomma = false;
					}
					if ($d=='"' || $d=="'") {
						$inq = true; //entering quotes
						$qtype = $d;
					} else if ($d==$leftb) {
						$thisn++;  //increase nesting depth
					} else if ($d==$rightb) {
						$thisn--; //decrease nesting depth
						if ($thisn==0) {
							//read inside of brackets, send recursively to interpreter
                            $toprocess = trim(substr($str,$i+1,$j-$i-1));
                            $inside = interpretline($toprocess,$anstype,$countcnt+1,$included_qs);

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
                            if ($newcnt > 0) {
                                $out .= str_repeat("\n", $newcnt);
                            }
                            $newcnt = 0;
							$i= $j+1;
							break;
						}
					} else if ($d=='/' && $str[$j+1]=='/') {
						//comment inside brackers
                        if (!$inq && ($intype == 4 || $intype == 11)) {
                            $str[$j] = ' '; // remove comment contents
                        }
						while ($d!="\n" && $j<$len) {
							$j++;
                            if ($j < $len) {
							    $d = $str[$j];
                                if (!$inq && ($intype == 4 || $intype == 11)) {
                                    $str[$j] = ' ';
                                }
                            }
						}
					} else if ($d=="\n") {
                        //echo "unmatched parens/brackets - likely will cause an error";
                        if (!$inq && ($intype == 4 || $intype == 11)) {
                            $str[$j] = ' ';
                            $newcnt++;
                        }
					}
				}
				$j++;
			}
			if ($j>=$len) {
				$i = $j;
				echo _('unmatched parens/brackets - likely will cause an error');
			} else {
				$c = $str[$i];
			}
		} else if ($c=='"' || $c=="'" || $c=='`') { //string, or unquoted math
			$intype = 6;
			$qtype = $c;
			$strtext = '';
			do {
				$strtext .= $c;
				$i++;
				if ($i==$len) {break;}
				$lastc = $c;
				$c = $str[$i];
			} while (!($c==$qtype && $lastc!='\\'));
			$strtext .= $c;
			if ($c=='`') {
				$out = _('"invalid - unquoted backticks"');
			} else {
				$out .= removeDisallowedVarsString($strtext,$anstype,$countcnt,$qtype,$included_qs);
			}
			$i++;
            if ($i<$len) {
				$c = $str[$i];
			}
		} else if ($c=="\n") {
			//end of line
			$intype = 7;
			$i++;
			if ($i<$len) {
				$c = $str[$i];
			}
		} else if ($c==';') {
			//end of line
			$intype = 7;
            $i++;
			if ($i<$len) {
                $c = $str[$i];
                //eat whitespace
                while ($c==' ') {
                    $i++;
                    $c = $str[$i];
                }
                if ($c=="\n") {
                    $i++;
                    $c = $str[$i];
                }
			}
		} else if ($c=='<' || $c=='>' || (($c=='!' || $c=='=') && (($str[$i + 1] ?? '') == '='))) {
			$intype = 12; // comparison
			$out .= $c;
			$i++;
			while ($i<$len) {
				$c = $str[$i];
				if ($c === '=') {
					$out .= $c;
					$i++;
				} else {
					break;
				}
			}
		} else if ($c=='!' && ($str[$i - 1] ?? '') === ' ' && ($str[$i + 1] ?? '') === ' ' && ($str[$i + 2] ?? '') === '=' && ($str[$i + 3] ?? '') === '=') {
			// special case of ' ! ==' handled as != for legacy/backwards-compatability
			$intype = 12; // comparison 
			$out .= '!==';
			$i += 4;
		} else if ($c=='!' && ($str[$i + 1] ?? '') === ' ' && ($str[$i + 2] ?? '') === '=' && ($str[$i + 3] ?? '') !== '=') {
			// special case of '! = ' handled as != for legacy/backwards-compatability
			$intype = 12; // comparison 
			$out .= '!=';
			$i += 3;
		} else {
			//no type - just append string.  Could be operators
			$out .= $c;
			$i++;
			if ($i<$len) {
				$c = $str[$i];
			}
		}
		while ($c==' ') { //eat up extra whitespace
			$i++;
			if ($i==$len) {break;}
			$c = $str[$i];
			if ($c=='.' && $intype==3) {//if 3 . needs space to act like concat
				$out .= ' ';
			}
		}
		//if parens or array index needs to be connected to func/var, do it
		if ($connecttolast>0 && $intype!=$connecttolast) {
			//if func is loadlibrary, need to do so now so allowedmacros
			//will be expanded before reading the rest of the code
			if ($lastsym[0] == "loadlibrary") {
				loadlibrary(substr($out,1,strlen($out)-2));
				array_pop($syms);
				$connecttolast = 0;
			} else if ($lastsym[0] == 'importcodefrom' || $lastsym[0] == 'includecodefrom') {
				$out = intval(substr($out,1,strlen($out)-2));
                if (in_array($out,$included_qs)) {
                    $inside = 'error';
                    echo 'Error: circular reference in includecodefrom';
                } else {
                    $stm = $DBH->prepare("SELECT control,qtype FROM imas_questionset WHERE id=:id");
                    $stm->execute(array(':id'=>$out));
                    $row = $stm->fetch(PDO::FETCH_NUM);
                    if ($row === false) {
                        //was an error, return error token
                        return array(array('',9));
                    } else {
                        list($thiscontrol, $thisqtype) = $row;
                        //$inside = interpretline(mysql_result($result,0,0),$anstype);
                        $inside = interpret('control',$anstype,$thiscontrol,$countcnt+1, [$out, ...$included_qs ]);
                        if ($thisqtype!=$anstype) {
                            //echo 'Imported code question type does not match current question answer type';
                        }
                    }
                }
				if ($inside=='error') {
					//was an error, return error token
					return array(array('',9));
				} else {
					array_pop($syms);
					$connecttolast = 0;
					$syms[] = array('{$included=true;'.$inside.';$included=false;}',5); //type: curly
					$syms[] = array('',7); //end of line;
					$lastsym = array('',7);
				}
			} else if (strlen($out)>0 && $out[0]=='{' && $lastsym[0]=='$') { //var var
				//conditional value based on if allowed
				// the name expression is evaluated once, inside checkvarvarname
                    $syms[count($syms)-1][0] = '${checkvarvarname('.substr($out,1,-1).')}';
					// placeholder returned for disallowed names; make sure it's initialized
					$GLOBALS['interpretcurvars'][] = '$invalidvariable';
				$connecttolast = 0;
			} else {
				$syms[count($syms)-1][0] .= $out;
				$connecttolast = 0;
				if ($c=='[') {// multidim array ref?
					$connecttolast = 1;
                }
                if ($connecttolast == 0 && 
                    (substr($syms[count($syms)-1][0],0,12) == '$stuanswers[' ||
                    substr($syms[count($syms)-1][0],0,15) == '$stuanswersval[')
                ) {
                    $syms[count($syms)-1][0] = '('.$syms[count($syms)-1][0].' ?? null)';
                } else 
                if ($connecttolast == 0 && 
                    (substr($syms[count($syms)-1][0],0,16) == '$scoreiscorrect[' ||
                    substr($syms[count($syms)-1][0],0,14) == '$scorenonzero[')
                ) {
                    $syms[count($syms)-1][0] = '('.$syms[count($syms)-1][0].' ?? -1)';
                }
                if ($connecttolast == 0 && 
                    substr($syms[count($syms)-1][0],0,14) == '$partattemptn['
                ) {
                    $syms[count($syms)-1][0] = '('.$syms[count($syms)-1][0].' ?? 0)';
                }
			}
		} else {
            //add to symbol list.  avoid repeat end-of-lines.
            $lastsym = array($out,$intype);
            $syms[] =  array($out,$intype);
		}

	}
	return $syms;
}

function testIsEscaped($str,$c) {
    $cnt = 0;
    $i = $c-1;
    while ($i >= 0 && $str[$i] == '\\') {
        $cnt++;
        $i--;
    }
    return (($cnt%2)==1);
}

//handle braces and variable variables in strings and qtext
function removeDisallowedVarsString($str,$anstype,$countcnt=1,$quotetype='"',$included_qs=[]) {
	global $disallowedvar;

	//remove any blatent disallowed var
	$str = preg_replace('/('.str_replace('$','\\$',implode('|',$disallowedvar)).')\b/',_('Invalid variable'),$str);
	//$str = str_replace($disallowedvar,_('Invalid variable'),$str);

    preg_match_all('/(?<!\\\\)(\$[a-zA-Z_]\w*)/', $str, $m);
    foreach ($m[0] as $v) {
        $GLOBALS['interpretcurvars'][] = $v;
    }
    preg_match_all('/(?<!\\\\)(\$[a-zA-Z_]\w*)((\[\d+\])+)/', $str, $m, PREG_SET_ORDER);
    foreach ($m as $v) {
        $GLOBALS['interpretcurarrvars'][] = $v; // will be an array of set matches
    }

    if ($quotetype!='"') {
        return $str;
    }

	$startmarker = 0; $lastend = 0;
	$invarvar = false;
	$inbraces = false;
	$outstr = '';
	$depth = 0;
	for ($c=0;$c<strlen($str);$c++) {
		if ($str[$c]=='{' && !testIsEscaped($str,$c)) {
			if ($invarvar || $inbraces) {
				$depth++;
			} else { //may be starting new brace or varvar item
				if ($c>0 && $str[$c-1]=='$') { //might be varvar
					if ($c>1 && $str[$c-2]=='\\' && ($c<3 || $str[$c-3]!='\\')) {
						//it's braces with escaped $ sign
					} else {
						$invarvar = true;
					}
				}
				if (!$invarvar) {
					if ($c<strlen($str)-1 && $str[$c+1]!=='$') {
						continue; //skip {b and { $a since won't parse as brace
					} else {
						$inbraces = true;
					}
				}
				$startmarker = $c;
				$depth++;
				if ($invarvar) {
					$outstr .= substr($str,$lastend,$c-$lastend-1);
				} else {
					$outstr .= substr($str,$lastend,$c-$lastend);
				}
			}
		} else if ($str[$c]=='}' && ($invarvar || $inbraces)) {
			$depth--;
			if ($depth==0) {
				if ($inbraces) {
					//interpret stuff in braces as code
                    $insidetext = str_replace('\"','"',substr($str,$startmarker+1,$c-$startmarker-1));
					$insidebrace = interpretline($insidetext,$anstype,$countcnt+1,$included_qs);
					if ($insidebrace!='error') {
						$outstr .= '".('.$insidebrace.')."';
					}
				} else if ($invarvar) {
					$insidebrace = substr($str,$startmarker+1,$c-$startmarker-2);
					$outstr .= _('Invalid variable'); //eliminate var var
				}
				$lastend = $c+1;
				$inbraces = false;
				$invarvar = false;
			}
		}
	}
	if ($depth==0) { //if we aren't in a brace, include any remaining string
		$outstr .= substr($str,$lastend);
	}
	return $outstr;
}

//for code, check inside of a variable variable to make sure the
//result is a simple string or variable that is allowed
function checkvarvarisallowed($inside) {
	global $disallowedvar;
	if (preg_match('/^(\w+)$/',$inside,$matches)) {
		if (!in_array('$'.$matches[1], $disallowedvar)) {
			return true;
		}
	}
	echo _('Invalid variable: ').$inside;
	return false;
}

//variable-variable support: returns the variable name if it is a plain word
//that isn't disallowed, otherwise a harmless placeholder name.
function checkvarvarname($inside) {
	global $disallowedvar;
	if (is_scalar($inside) && preg_match('/^\w+$/', (string)$inside) &&
		!in_array('$'.$inside, $disallowedvar, true)
	) {
		return (string)$inside;
	}
	echo _('Invalid variable: ') . Sanitize::encodeStringForDisplay(is_scalar($inside) ? (string)$inside : '');
	return 'invalidvariable';
}

//loads a macro library
function loadlibrary($str) {
	$str = str_replace('"',"",$str);
	$libs = explode(",",$str);
	$libdir = rtrim(dirname(__FILE__), '/\\') .'/libs/';
	foreach ($libs as $lib) {
		if (!preg_match('/^[A-Za-z0-9_]+$/', $lib)) {
			echo sprintf(_("Error loading library %s\n"), Sanitize::encodeStringForDisplay($lib));
		} else if (is_file($libdir . $lib.".php")) {
			require_once $libdir.$lib.".php";
		} else {
			echo sprintf(_("Error loading library %s\n"), Sanitize::encodeStringForDisplay($lib));
		}
	}
}

//sets question seed
function setseed($ns,$ref=0) {
	global $RND;
	if ($ns=="userid") {
		if (isset($GLOBALS['teacherid']) && isset($GLOBALS['teacherreview'])) { //reviewing in gradebook
			$RND->srand($GLOBALS['teacherreview']);
		} else { //in assessment
			$RND->srand($GLOBALS['userid'] ?? 0);
		}
	} else if ($ns=="from") {
		if (isset($GLOBALS['seeds']) && isset($GLOBALS['seeds'][$ref-1])) {
			$RND->srand($GLOBALS['seeds'][$ref-1]);
		}
	} else {
		$RND->srand($ns);
	}
}

//Loop budgets.  State lives in a static, so question code cannot see or alter it.
//  loopstart($kind,$depth[,$quiet]) - call when a loop begins; resets that loop's count
//     (and the shared total, if it's an outermost loop)
//  loopstep($kind,$depth) - call before each iteration; false means stop looping
//  loopcut($kind,$depth) - true if the last loop at that depth was cut off
//  loopreset() - clear everything; call at the start of each block of question code
function &loopstate() {
	static $state = ['n'=>[], 'tot'=>[], 'cut'=>[], 'quiet'=>[], 'dead'=>[], 'told'=>[]];
	return $state;
}
function loopreset() {
	$st = &loopstate();
	$st = ['n'=>[], 'tot'=>[], 'cut'=>[], 'quiet'=>[], 'dead'=>[], 'told'=>[]];
	return true;
}
function loopstart($kind, $depth, $quiet = false) {
	$st = &loopstate();
	$st['n'][$kind][$depth] = 0;
	$st['cut'][$kind][$depth] = false;
	$st['quiet'][$kind][$depth] = $quiet;
	if ($depth == 1) {
		$st['tot'][$kind] = 0;
		$st['dead'][$kind] = false;
		$st['told'][$kind] = [];
	}
	return true;
}
function loopstep($kind, $depth) {
	//per-loop limit, shared limit across nested loops, message
	static $limits = [
		'for' => [1000, 12000, 'for loop exceeded 1000 iterations - giving up'],
		'while' => [200, 1000, 'while not terminated in 200 iterations'],
		'where' => [199, 1000, 'where not met in 200 iterations'],
	];
	if (!isset($limits[$kind])) { return false; }
	$st = &loopstate();
	if (!empty($st['dead'][$kind])) {
		//shared budget already used up earlier in this outermost loop; stop quietly
		$st['cut'][$kind][$depth] = true;
		return false;
	}
	$st['n'][$kind][$depth] = ($st['n'][$kind][$depth] ?? 0) + 1;
	$st['tot'][$kind] = ($st['tot'][$kind] ?? 0) + 1;
	if ($st['n'][$kind][$depth] <= $limits[$kind][0] && $st['tot'][$kind] <= $limits[$kind][1]) {
		return true;
	}
	$st['cut'][$kind][$depth] = true;
	if ($st['n'][$kind][$depth] > $limits[$kind][0]) {
		//this loop ran too long.  Other loops, including siblings, are unaffected,
		//but only report it once per outermost loop.
		$why = 'loop';
		$msg = _($limits[$kind][2]);
	} else {
		//shared budget used up: no more loops of this kind in this outermost loop
		$why = 'total';
		$msg = sprintf(_('nested %s loops exceeded %d iterations - giving up'), $kind, $limits[$kind][1]);
		$st['dead'][$kind] = true;
	}
	if (empty($st['quiet'][$kind][$depth]) && empty($st['told'][$kind][$why])) {
		echo $msg;
	}
	$st['told'][$kind][$why] = true;
	return false;
}
function loopcut($kind, $depth) {
	$st = &loopstate();
	return !empty($st['cut'][$kind][$depth]);
}

function interpretAllowedConstants() {
	return ['PREG_SET_ORDER','PREG_PATTERN_ORDER'];
}

//functions the transpiler itself emits (beyond $allowedmacros)
function interpretInternalFunctions() {
	return ['is_nan','floatval','ceil','floor','round','is_array','exp','safepow','factorial',
		'is_nicenumber','log10','log','safeasin','safeacos','atan','asec','acsc','acot',
		'asinh','acosh','atanh','asech','acsch','acoth','checkvarvarname',
		'loopreset','loopstart','loopstep','loopcut'];
}

//true if parens/brackets/braces in $s are balanced, ignoring quoted strings
function interpretParensBalanced($s) {
	$stack = [];
	$pairs = [')'=>'(', ']'=>'[', '}'=>'{'];
	$len = strlen($s);
	for ($i=0; $i<$len; $i++) {
		$c = $s[$i];
		if ($c=='"' || $c=="'") {
			for ($i++; $i<$len && $s[$i]!=$c; $i++) {
				if ($s[$i]=='\\') { $i++; }
			}
		} else if ($c=='(' || $c=='[' || $c=='{') {
			$stack[] = $c;
		} else if (isset($pairs[$c])) {
			if (array_pop($stack) !== $pairs[$c]) { return false; }
		}
	}
	return count($stack)==0;
}

//Second line of defense.  Takes PHP code about to be eval'd and checks it with
//PHP's own tokenizer against an allowlist, independent of how the code was
//generated.  Returns '' if OK, otherwise a short description of the problem.
function validateInterpretedCode($code) {
	global $allowedmacros, $disallowedvar;
	static $okIds = null;
	if ($okIds === null) {
		$okIds = [];
		foreach (['T_LNUMBER','T_DNUMBER','T_CONSTANT_ENCAPSED_STRING','T_IF','T_ELSE','T_ELSEIF',
			'T_DO','T_AS','T_BREAK','T_CONTINUE','T_ECHO','T_LIST','T_ARRAY','T_ISSET','T_DOUBLE_ARROW',
			'T_INC','T_DEC','T_BOOLEAN_AND','T_BOOLEAN_OR','T_LOGICAL_AND','T_LOGICAL_OR','T_LOGICAL_XOR',
			'T_IS_EQUAL','T_IS_NOT_EQUAL','T_IS_IDENTICAL','T_IS_NOT_IDENTICAL','T_IS_SMALLER_OR_EQUAL',
			'T_IS_GREATER_OR_EQUAL','T_PLUS_EQUAL','T_MINUS_EQUAL','T_MUL_EQUAL','T_DIV_EQUAL',
			'T_CONCAT_EQUAL','T_MOD_EQUAL','T_POW','T_POW_EQUAL','T_SL','T_SR','T_SPACESHIP',
			'T_COALESCE','T_INT_CAST','T_AMPERSAND_FOLLOWED_BY_VAR_OR_VARARG',
				'T_AMPERSAND_NOT_FOLLOWED_BY_VAR_OR_VARARG'] as $name) {
			if (defined($name)) { $okIds[] = constant($name); }
		}
	}
	$okChars = '+-*/%=<>!.,;:?&|^~';	$funcs = interpretInternalFunctions();
	$consts = array_merge(interpretAllowedConstants(), ['M_PI']);

	$toks = [];
	foreach (token_get_all('<?php '.$code) as $k=>$t) {
		if ($k === 0) { continue; } //open tag
		if (is_array($t)) {
			if ($t[0] === T_WHITESPACE) { continue; }
			if ($t[0] === T_NAME_FULLY_QUALIFIED && substr_count($t[1], '\\') === 1) {
				//\round( is the same as round(; the checks below still apply to the bare name
				$t[0] = T_STRING;
				$t[1] = substr($t[1], 1);
			}
			$toks[] = [$t[0], $t[1]];
		} else {
			$toks[] = [$t, $t];
		}
	}
	$n = count($toks);
	$stack = [];
	$inDq = false;
	$prevId = null;
	$prevIsValue = false; //previous token ends a value, so a following ( would be a dynamic call
	for ($i=0; $i<$n; $i++) {
		list($id, $txt) = $toks[$i];
		$nextId = $toks[$i+1][0] ?? null;
		if ($id === '"') { //start/end of an interpolated string
			$inDq = !$inDq;
			$prevIsValue = !$inDq;
			$prevId = $id;
			continue;
		}
		if ($inDq) {
			if ($id === T_ENCAPSED_AND_WHITESPACE || $id === T_NUM_STRING || $id === '[' || $id === ']' || $id === '-') {
				//ok
			} else if ($id === T_VARIABLE) {
				if (in_array($txt, $disallowedvar, true)) { return 'disallowed variable '.$txt; }
			} else if ($id === T_STRING && ($prevId === '[' || $prevId === T_OBJECT_OPERATOR)) {
				//ok: "$a[key]" or "$a->prop"
			} else if ($id === T_OBJECT_OPERATOR && $prevId === T_VARIABLE && $nextId === T_STRING) {
				//ok
			} else {
				return 'unexpected '.(is_int($id) ? token_name($id) : $id).' in string';
			}
			$prevId = $id;
			continue;
		}
		$isValue = false;
		if ($id === T_VARIABLE) {
			if ($txt === '$GLOBALS') {
				//only allowed as the rand() rewrite:  $GLOBALS['RND']->rand(
				if (($toks[$i+1][0] ?? null) === '[' && ($toks[$i+2][1] ?? '') === "'RND'" &&
					($toks[$i+3][0] ?? null) === ']' && ($toks[$i+4][0] ?? null) === T_OBJECT_OPERATOR &&
					($toks[$i+5][1] ?? '') === 'rand' && ($toks[$i+6][0] ?? null) === '('
				) {
					$i += 5;
					$prevId = T_STRING;
					$prevIsValue = false;
					continue;
				}
				return 'disallowed variable $GLOBALS';
			}
			if (in_array($txt, $disallowedvar, true)) {
				return 'disallowed variable '.$txt;
			}
			$isValue = true;
		} else if ($id === '$') {
			//only the variable-variable rewrite:  ${checkvarvarname(
			if (($toks[$i+1][0] ?? null) === '{' && ($toks[$i+2][0] ?? null) === T_STRING &&
				$toks[$i+2][1] === 'checkvarvarname' && ($toks[$i+3][0] ?? null) === '('
			) {
				$stack[] = '{vv';
				$stack[] = '(vv';
				$i += 3;
				$prevId = '(';
				$prevIsValue = false;
				continue;
			}
			return 'variable variable';
		} else if ($id === T_STRING) {
			if ($nextId === '(') {
				if (!in_array($txt, $allowedmacros, true) && !in_array($txt, $funcs, true)) {
					return 'function not allowed: '.$txt;
				}
			} else {
				if (!in_array(strtolower($txt), ['true','false','null'], true) && !in_array($txt, $consts, true)) {
					return 'constant not allowed: '.$txt;
				}
				$isValue = true;
			}
		} else if ($id === T_FOR || $id === T_FOREACH || $id === T_WHILE) {
			$err = interpretCheckLoopGuard($toks, $i);
			if ($err !== '') { return $err; }
		} else if ($id === '(') {
			if ($prevIsValue) { return 'dynamic function call'; }
			$stack[] = '(';
		} else if ($id === ')') {
			$top = array_pop($stack);
			if ($top === '(vv') {
				if ($nextId !== '}') { return 'malformed variable variable'; }
			} else if ($top !== '(') {
				return 'unbalanced parentheses';
			}
			$isValue = true;
		} else if ($id === '[') {
			$stack[] = '[';
		} else if ($id === ']') {
			if (array_pop($stack) !== '[') { return 'unbalanced brackets'; }
			$isValue = true;
		} else if ($id === '{') {
			$stack[] = '{';
		} else if ($id === '}') {
			$top = array_pop($stack);
			if ($top !== '{' && $top !== '{vv') { return 'unbalanced braces'; }
			$isValue = ($top === '{vv');
		} else if (in_array($id, $okIds, true) || (is_string($id) && strpos($okChars, $id) !== false)) {
			$isValue = ($id === T_LNUMBER || $id === T_DNUMBER || $id === T_CONSTANT_ENCAPSED_STRING);
		} else {
			return 'not allowed: '.(is_int($id) ? token_name($id) : $id);
		}
		$prevId = $id;
		$prevIsValue = $isValue;
	}
	if ($inDq || count($stack) > 0) {
		return 'unbalanced code';
	}
	return '';
}

//Builds the code to eval qtext/solution text into variable $varname
//(without the $), after checking it.  Use as: eval(qtextEvalCode('evaledqtext', $toevalqtxt));
//The eval must happen in the caller's scope, so this returns code rather than running it.
function qtextEvalCode($varname, $text) {
	$code = "\$$varname = \"$text\";";
	$err = validateInterpretedCode($code);
	if ($err !== '') {
		return "\$$varname = " . var_export(sprintf(_('Eeek.. unsafe code: %s'), Sanitize::encodeStringForDisplay($err)), true) . ';';
	}
	return $code;
}

//Loops must call loopstep().  It has to be ANDed at the top level of the loop
//condition (no ||, ?:, etc. there), and foreach must call it first thing in the body.
function interpretCheckLoopGuard($toks, $i) {
	$id = $toks[$i][0];
	$n = count($toks);
	if (($toks[$i+1][0] ?? null) !== '(') { return 'loop without condition'; }
	$depth = 0;
	$segs = [[]];
	for ($j = $i+1; $j < $n; $j++) {
		$t = $toks[$j][0];
		if ($t === '(' || $t === '[' || $t === '{') {
			$depth++;
			if ($depth === 1) { continue; }
		} else if ($t === ')' || $t === ']' || $t === '}') {
			$depth--;
			if ($depth === 0) { break; }
		} else if ($depth === 1 && $t === ';' && $id === T_FOR) {
			$segs[] = [];
			continue;
		}
		$segs[count($segs)-1][] = [$depth, $toks[$j], $toks[$j+1][0] ?? null];
	}
	if ($j >= $n) { return 'unterminated loop header'; }
	if ($id === T_FOREACH) {
		// body must start:  { if ( ! loopstep (
		if (($toks[$j+1][0] ?? null) === '{' && ($toks[$j+2][0] ?? null) === T_IF &&
			($toks[$j+3][0] ?? null) === '(' && ($toks[$j+4][0] ?? null) === '!' &&
			($toks[$j+5][1] ?? '') === 'loopstep' && ($toks[$j+6][0] ?? null) === '('
		) {
			return '';
		}
		return 'unguarded foreach loop';
	}
	if ($id === T_FOR) {
		if (count($segs) !== 3) { return 'malformed for loop'; }
		$cond = $segs[1];
	} else {
		$cond = $segs[0];
	}
	$found = false;
	foreach ($cond as list($d, $tk, $nextId)) {
		if ($d !== 1) { continue; }
		if ($tk[0] === T_STRING && $tk[1] === 'loopstep' && $nextId === '(') {
			$found = true;
		} else if (in_array($tk[0], [T_BOOLEAN_OR, T_LOGICAL_OR, T_LOGICAL_XOR, T_COALESCE, '?', ','], true)) {
			return 'unguarded loop condition';
		}
	}
	return $found ? '' : 'unguarded loop';
}

function genVarInit($vars) {
    $prep = '';
    $done = [];
    foreach ($vars as $var) {
        if (is_array($var)) { 
            if (in_array($var[0], $done)) { continue; }
            // is array ref from qtext
            // [whole match, var, whole array ref, last array ref]
            $prep .= 'if (!isset('.$var[1].') || (is_array('.$var[1].') && !isset('.$var[0].'))){'.$var[0].'=null;}';
            $done[] = $var[0];
        } else {
            if (in_array($var, $done)) { continue; }
            $prep .= 'if (!isset('.$var.')){'.$var.'=null;}';
            $done[] = $var;
        }
    }
    return $prep;
}

?>
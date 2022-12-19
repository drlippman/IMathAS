<?php

function isNaN( $var ) {
  // is_numeric catches most things, but not php-generated NAN or INF
  // is_finite catches those
  return (!is_numeric($var) || !is_finite($var));
     //return !preg_match('/^[-]?[0-9]+([\.][0-9]+)?([eE][+\-]?[0-9]+)?$/', $var);
     //possible alternative:
     //return ($var!==$var || $var*2==$var);
}

function ltrimzero($v,$k) {
	return ltrim($v, ' 0');
}
function checkreqtimes($tocheck,$rtimes) {
	global $mathfuncs, $myrights;
    if ($rtimes=='') {return 1;}
	if ($tocheck=='DNE' || $tocheck=='oo' || $tocheck=='+oo' || $tocheck=='-oo') {
		return 1;
	}
	//why?  $cleanans = preg_replace('/[^\w\*\/\+\-\(\)\[\],\.\^=\|<>_!]+/','',$tocheck);
    $cleanans = $tocheck;

	//if entry used pow or exp, we want to replace them with their asciimath symbols for requiretimes purposes
	$cleanans = str_replace("pow","^",$cleanans);
	$cleanans = str_replace("exp","e",$cleanans);
	$cleanans = preg_replace('/\^\((-?[\d\.]+)\)([^\d]|$)/','^$1 $2', $cleanans);
    
	if (is_numeric($cleanans) && $cleanans>0 && $cleanans<1) {
		$cleanans = ltrim($cleanans,'0');
	}
	$ignore_case = true;
	if ($rtimes != '') {
		$list = array_map('trim',explode(",",$rtimes));
		for ($i=0;$i < count($list);$i+=2) {
			if ($list[$i]=='') {continue;}
			if (!isset($list[$i+1]) ||
			   (strlen($list[$i+1])<2 && $list[$i]!='ignore_case' && $list[$i]!='ignore_commas' && $list[$i]!='ignore_symbol')) {
				if ($myrights>10) {
					echo "Invalid requiretimes - check format";
				}
				continue;
			}
			$list[$i+1] = trim($list[$i+1]);
			if ($list[$i]=='ignore_case') {
				$ignore_case = ($list[$i+1]==='1' || $list[$i+1]==='true' || $list[$i+1]==='=1');
				continue;
			} else if ($list[$i]=='ignore_commas') {
				if ($list[$i+1]==='1' || $list[$i+1]==='true' || $list[$i+1]==='=1') {
					$cleanans = str_replace(',','',$cleanans);
				}
				continue;
			} else if ($list[$i]=='ignore_symbol') {
				$cleanans = str_replace($list[$i+1],'',$cleanans);
				continue;
			} else if ($list[$i]=='ignore_spaces') {
				if ($list[$i+1]==='1' || $list[$i+1]==='true' || $list[$i+1]==='=1') {
					$cleanans = str_replace(' ','',$cleanans);
				}
				continue;
            }
			$comp = substr($list[$i+1],0,1);
			if (substr($list[$i+1],1,1)==='=') { //<=, >=, ==, !=
				if ($comp=='<' || $comp=='>') {
					$comp .= '=';
				}
				$num = intval(substr($list[$i+1],2));
			} else {
				$num = intval(substr($list[$i+1],1));
			}
			$grouptocheck = array_map('trim', explode('||',$list[$i]));
			$okingroup = false;
			foreach ($grouptocheck as $lookfor) {
				if ($lookfor=='#') {
					$nummatch = preg_match_all('/[\d\.]+/',$cleanans,$m);
				} else if ($lookfor[0]=='#') {
					if (!isset($all_numbers)) {
						preg_match_all('/[\d\.]+/',$cleanans,$matches);
						$all_numbers = $matches[0];
						array_walk($all_numbers, 'ltrimzero');
					}
					$lookfor = trim(substr($lookfor,1));
                    if ($lookfor[0] == '-') {
                        $lookfor = substr($lookfor,1);
                    }
                    $lookfor = ltrim($lookfor, ' 0');
                    $nummatch = count(array_keys($all_numbers,$lookfor));
				} else if (strlen($lookfor)>6 && substr($lookfor,0,6)=='regex:') {
					$regex = str_replace('/','\\/',substr($lookfor,6));
					$nummatch = preg_match_all('/'.$regex.'/'.($ignore_case?'i':''),$cleanans,$m);
				} else {
					if ($ignore_case || in_array($lookfor, $mathfuncs)) {
						$nummatch = substr_count(strtolower($cleanans),strtolower($lookfor));
					} else {
						$nummatch = substr_count($cleanans,$lookfor);
					}
                }
                
				if ($comp == "=") {
					if ($nummatch==$num) {
						$okingroup = true;
						break;
					}
				} else if ($comp == "<") {
					if ($nummatch<$num) {
						$okingroup = true;
						break;
					}
				} else if ($comp == "<=") {
					if ($nummatch<=$num) {
						$okingroup = true;
						break;
					}
				} else if ($comp == ">") {
					if ($nummatch>$num) {
						$okingroup = true;
						break;
					}
				} else if ($comp == ">=") {
					if ($nummatch>=$num) {
						$okingroup = true;
						break;
					}
				} else if ($comp == "!") {
					if ($nummatch!=$num) {
						$okingroup = true;
						break;
					}
				} else if ($myrights>10) {
					echo "Invalid requiretimes - check format";
				}
			}
			if (!$okingroup) {
				return 0;
			}
		}
	}
	return 1;
}

//parses complex numbers.  Can handle anything, but only with
//one i in it.
function parsecomplex($v) {
	$v = str_replace(' ','',$v);
	$v = str_replace(array('sin','pi'),array('s$n','p$'),$v);
	$v = preg_replace('/\((\d+\*?i|i)\)\/(\d+)/','$1/$2',$v);
	$len = strlen($v);
	//preg_match_all('/(\bi|i\b)/',$v,$matches,PREG_OFFSET_CAPTURE);
	//if (count($matches[0])>1) {
	if (substr_count($v,'i')>1) {
		return _('error - more than 1 i in expression');
	} else {
		//$p = $matches[0][0][1];
		$p = strpos($v,'i');
		if ($p===false) {
			$real = $v;
			$imag = 0;
		} else {
			//look left
			$nd = 0;
			for ($L=$p-1;$L>0;$L--) {
				$c = $v[$L];
				if ($c==')') {
					$nd++;
				} else if ($c=='(') {
					$nd--;
				} else if (($c=='+' || $c=='-') && $nd==0) {
					break;
				}
			}
			if ($L<0) {$L=0;}
			if ($nd != 0) {
				return _('error - invalid form');
			}
			//look right
			$nd = 0;

			for ($R=$p+1;$R<$len;$R++) {
				$c = $v[$R];
				if ($c=='(') {
					$nd++;
				} else if ($c==')') {
					$nd--;
				} else if (($c=='+' || $c=='-') && $nd==0) {
					break;
				}
			}
			if ($nd != 0) {
				return _('error - invalid form');
			}
			//which is bigger?
			if ($p-$L>0 && $R-$p>0 && ($R==$len || $L==0)) {
				//return _('error - invalid form');
				if ($R==$len) {// real + AiB
					$real = substr($v,0,$L);
					$imag = substr($v,$L,$p-$L);
					$imag .= '*'.substr($v,$p+1+($v[$p+1]=='*'?1:0),$R-$p-1);
				} else if ($L==0) { //AiB + real
					$real = substr($v,$R);
					$imag = substr($v,0,$p);
					$imag .= '*'.substr($v,$p+1+($v[$p+1]=='*'?1:0),$R-$p-1);
				} else {
					return _('error - invalid form');
				}
				$imag = str_replace('-*','-1*',$imag);
				$imag = str_replace('+*','+1*',$imag);
			} else if ($p-$L>1) {
				$imag = substr($v,$L,$p-$L);
				$real = substr($v,0,$L) . substr($v,$p+1);
			} else if ($R-$p>1) {
				if ($p>0) {
					if ($v[$p-1]!='+' && $v[$p-1]!='-') {
						return _('error - invalid form');
					}
					$imag = $v[$p-1].substr($v,$p+1+($v[$p+1]=='*'?1:0),$R-$p-1);
					$real = substr($v,0,$p-1) . substr($v,$R);
				} else {
					$imag = substr($v,$p+1,$R-$p-1);
					$real = substr($v,0,$p) . substr($v,$R);
				}
			} else { //i or +i or -i or 3i  (one digit)
				if ($v[$L]=='+') {
					$imag = 1;
				} else if ($v[$L]=='-') {
					$imag = -1;
				} else if ($p==0) {
					$imag = 1;
				} else {
					$imag = $v[$L];
				}
				$real = ($p>0?substr($v,0,$L):'') . substr($v,$p+1);
			}
			if ($real=='') {
				$real = 0;
			}
			if ($imag[0]=='/') {
				$imag = '1'.$imag;
			} else if (($imag[0]=='+' || $imag[0]=='-') && $imag[1]=='/') {
				$imag = $imag[0].'1'.substr($imag,1);
			}
			$imag = str_replace('*/','/',$imag);
			if (substr($imag,-1)=='*') {
				$imag = substr($imag,0,-1);
			}
		}
		$real = str_replace(array('s$n','p$'),array('sin','pi'),$real);
		$imag = str_replace(array('s$n','p$'),array('sin','pi'),$imag);
		return array($real,$imag);
	}
}

/*
 * Parses a list of string ntuples
 * do_or: for each element in list, create an array of "or" alternatives
 * eval: true to eval non-numeric values
 */
function parseNtuple($str, $do_or = false, $do_eval = true) {
	if ($str == 'DNE' || $str == 'oo' || $str == '-oo') {
		return $str;
	}
	$ntuples = [];
	$NCdepth = 0;
	$lastcut = 0;
	$inor = false;
	$str = makepretty($str);
	$matchbracket = array(
		'(' => ')',
		'[' => ']',
		'<' => '>',
		'{' => '}'
	);
	$closebracket = '';
	for ($i=0; $i<strlen($str); $i++) {
		$dec = false;
		if ($str[$i]=='(' || $str[$i]=='[' || $str[$i]=='<' || $str[$i]=='{') {
			if ($NCdepth==0) {
				$lastcut = $i;
				$closebracket = $matchbracket[$str[$i]];
			}
			$NCdepth++;
		} else if ($str[$i]==$closebracket) {
			$NCdepth--;
			if ($NCdepth==0) {
				$thisTuple = array(
					'lb' => $str[$lastcut],
					'rb' => $str[$i],
					'vals' => explode(',', substr($str,$lastcut+1,$i-$lastcut-1))
				);
				if ($do_eval) {
					for ($j=0; $j < count($thisTuple['vals']); $j++) {
						if ($thisTuple['vals'][$j] != 'oo' && $thisTuple['vals'][$j] != '-oo') {
							$thisTuple['vals'][$j] = evalMathParser($thisTuple['vals'][$j]);
						}
					}
				}
				if ($do_or && $inor) {
					$ntuples[count($ntuples)-1][] = $thisTuple;
				} else if ($do_or) {
					$ntuples[] = array($thisTuple);
				} else {
					$ntuples[] = $thisTuple;
				}
				$inor = ($do_or && substr($str, $i+1, 2)==='or');
			}
		}
	}
	return $ntuples;
}

function ntupleToString($ntuples) {
	if (!is_array($ntuples)) {
		return $ntuples;
	}
	$out = array();
	foreach ($ntuples as $ntuple) {
		if (isset($ntuple['lb'])) {
			$out[] = $ntuple['lb'] . implode(',', $ntuple['vals']) . $ntuple['rb'];
		} else if (is_array($ntuple[0])) {
			$sub = array();
			foreach ($ntuple as $subtuple) {
				$sub[] = $subtuple['lb'] . implode(',', $subtuple['vals']) . $subtuple['rb'];
			}
			$out[] = implode(' or ', $sub);
		}
	}
	implode(',', $out);
}

function parseInterval($str, $islist = false) {
    if (strlen($str)<5) { return false; }
	if ($islist) {
		$ints = preg_split('/(?<=[\)\]])\s*,\s*(?=[\(\[])/',$str);
	} else {
		$ints = explode('U',$str);
    }

	$out = array();
	foreach ($ints as $int) {
        $int = trim($int);
        if (strlen($int) < 5) { return false;}
		$i = array();
		$i['lb'] = $int[0];
		$i['rb'] = $int[strlen($int)-1];
		$i['vals'] = array_map('trim', explode(',', substr($int,1,-1)));
		if (count($i['vals']) != 2) {
			return false;
		}
		for ($j=0;$j<2;$j++) {
			if ($i['vals'][$j] == '+oo') {
				$i['vals'][$j] = 'oo';
			}
			if (!is_numeric($i['vals'][$j]) &&
			 	$i['vals'][$j] != 'oo' && $i['vals'][$j] != '-oo'
			) {
				$i['vals'][$j] = evalMathParser($i['vals'][$j]);
			}
		}
		$out[] = $i;
	}
	return $out;
}

function parsedIntervalToString($parsed, $islist) {
	$out = [];
  if ($parsed === false) {
    return '';
  }
	foreach ($parsed as $int) {
		$out[] = $int['lb'] . $int['vals'][0] . ',' . $int['vals'][1] . $int['rb'];
	}
	if ($islist) {
		return implode(',', $out);
	} else {
		return implode('U', $out);
	}
}

function parseChemical($string) {
    $string = str_replace(['<->','<=>'], 'rightleftharpoons', $string);
    $string = str_replace(['to','rarr','implies'], '->', $string);
    $string = preg_replace('/\^{(.*?)}/', '^($1)', $string);
    $string = preg_replace('/\(\(([^\(\)]*)\)\)/', '($1)', $string);
    $string = str_replace('^+','^(+)', $string);
    $parts = preg_split('/(->|rightleftharpoons)/', $string, -1, PREG_SPLIT_DELIM_CAPTURE);
    $reactiontype = (count($parts) > 1) ? $parts[1] : null;
    $sides = [];
    for ($i=0; $i < count($parts); $i += 2) {
        $sideparts = [];
        $lastcut = 0;
        $str = $parts[$i];
        $strlen = strlen($str);
        $depth = 0;
        // cut by + signs, parse out coefficient and chemical
        for ($p=1; $p < $strlen - 1; $p++) {
            $c = $str[$p];
            if ($c == '+' && $depth == 0 && $str[$p-1] != '^' && $str[$p-1] != '_') {
                preg_match('/^\s*(\d+)?\s*\*?\s*(.*?)\s*$/', substr($str, $lastcut, $p-$lastcut), $matches);
                $sideparts[] = [
                    $matches[1] === '' ? 1 : intval($matches[1]),
                    str_replace(' ','',$matches[2])
                ];
                $lastcut = $p+1;
            } else if ($c == '(' || $c == '[') {
                $depth++;
            } else if ($c == ')' || $c == ']') {
                $depth--;
            }
        }
        preg_match('/^\s*(\d+)?\s*\*?\s*(.*?)\s*$/', substr($str, $lastcut), $matches);
        $sideparts[] = [
            $matches[1] === '' ? 1 : intval($matches[1]),
            str_replace(' ','',$matches[2])
        ];
        // sort by chemical to put in standard order
        usort($sideparts, function($a,$b) {
            return strcmp($a[1],$b[1]);
        });
        $sides[] = $sideparts;
    }
    // if dual direction reaction, sort sides to standarize
    if (count($sides)>1 && $reactiontype == 'rightleftharpoons') {
        usort($sides, function($a,$b) {
            return strcmp($a[0][1], $b[0][1]);
        });
    }
    return [$sides, $reactiontype];
}

//checks the format of a value
//tocheck:  string to check
//ansformats:  array of answer formats.  Currently supports:
//   fraction, reducedfraction, fracordec, notrig, nolongdec, scinot, mixednumber, nodecimal
//returns:  false: bad format, true: good format
function checkanswerformat($tocheck,$ansformats) {
	$tocheck = trim($tocheck);
	$tocheck = str_replace(',','',$tocheck);
	if (!is_array($ansformats)) {$ansformats = explode(',',$ansformats);}
	if (strtoupper($tocheck)=='DNE' || $tocheck=='oo' || $tocheck=='+oo' || $tocheck=='-oo') {
		return true;
	}
	if (in_array("allowmixed",$ansformats) && preg_match('/^\s*(\-?\s*\d+)\s*(_|\s)\s*(\d+)\s*\/\s*(\d+)\s*$/',$tocheck,$mnmatches)) {
		//rewrite mixed number as an improper fraction
		$num = str_replace(' ','',$mnmatches[1])*$mnmatches[4] + $mnmatches[3]*1;
		$tocheck = $num.'/'.$mnmatches[4];
	}

	if (in_array("fraction",$ansformats) || in_array("reducedfraction",$ansformats) || in_array("fracordec",$ansformats)) {
		$tocheck = preg_replace('/\s/','',$tocheck);
		if (!preg_match('/^\(?\-?\s*\(?\d+\)?\/\(?\d+\)?$/',$tocheck) && !preg_match('/^\(?\d+\)?\/\(?\-?\d+\)?$/',$tocheck) && !preg_match('/^\s*?\-?\s*\d+\s*$/',$tocheck) && (!in_array("fracordec",$ansformats) || !preg_match('/^\s*?\-?\s*\d*?\.\d*?\s*$/',$tocheck))) {
			return false;
		} else {
			if (in_array("reducedfraction",$ansformats) && strpos($tocheck,'/')!==false) {
				$tocheck = str_replace(array('(',')'),'',$tocheck);
				$tmpa = explode("/",$tocheck);
				if (gcd(abs($tmpa[0]),abs($tmpa[1]))!=1 || $tmpa[1]==1) {
					return false;
				}
			}
		}
	}
	if (in_array("notrig",$ansformats)) {
		if (preg_match('/(sin|cos|tan|cot|csc|sec)/i',$tocheck)) {
			return false;
		}
    }
    if (!in_array("allowdegrees",$ansformats)) {
        if (strpos($tocheck,'degree') !== false) {
            return false;
        }
	}
	if (in_array("nolongdec",$ansformats)) {
		if (preg_match('/\.\d{6}/',$tocheck)) {
			return false;
		}
	}
	if (in_array("decimal", $ansformats)) {
		$totest = str_replace(' ','',$tocheck);
		if (!is_numeric($totest) || !preg_match('/^\-?(\d+|\d+\.\d*|\d*\.\d+)([eE]\-?\d+)?$/',$totest)) {
			return false;
		}
	}
	if (in_array("scinotordec",$ansformats)) {
		$totest = str_replace(' ','',$tocheck);
		if (!is_numeric($totest) && !preg_match('/^\-?[1-9](\.\d*)?(\*|xx|x|X|×|✕)10\^(\(?\-?\d+\)?)$/',$totest)) {
			return false;
		}
	}
	if (in_array("scinot",$ansformats)) {
		$totest = str_replace(' ','',$tocheck);
		if (!preg_match('/^\-?[1-9](\.\d*)?(\*|xx|x|X|×|✕)10\^(\(?\(?\-?\d+\)?\)?)$/',$totest)) {
			return false;
		}
	}

	if (in_array("mixednumber",$ansformats) || in_array("sloppymixednumber",$ansformats) || in_array("mixednumberorimproper",$ansformats)) {
		if (preg_match('/^\(?\-?\s*\(?\d+\)?\/\(?\d+\)?$/',$tocheck) || preg_match('/^\(?\d+\)?\/\(?\-?\d+\)?$/',$tocheck)) { //fraction
			$tmpa = explode("/",str_replace(array(' ','(',')'),'',$tocheck));
			if (in_array("mixednumber",$ansformats)) {
				if (!in_array("allowunreduced",$ansformats) && ((gcd(abs($tmpa[0]),abs($tmpa[1]))!=1) || abs($tmpa[0])>=abs($tmpa[1]))) {
					return false;
				}
			} else if (in_array("mixednumberorimproper",$ansformats)) {
				if (!in_array("allowunreduced",$ansformats) && ((gcd(abs($tmpa[0]),abs($tmpa[1]))!=1))) {
					return false;
				}
			}
		} else if (preg_match('/^\s*\-?\s*\d+\s*(_|\s)\s*\(?(\d+)\)?\s*\/\s*\(?(\d+)\)?\s*$/',$tocheck,$mnmatches)) { //mixed number
			if (in_array("mixednumber",$ansformats)) {
				if ($mnmatches[2]>=$mnmatches[3] || (!in_array("allowunreduced",$ansformats) && gcd($mnmatches[2],$mnmatches[3])!=1)) {
					return false;
				}
			} else if (in_array("mixednumberorimproper",$ansformats)) {
				if ((!in_array("allowunreduced",$ansformats) && gcd($mnmatches[2],$mnmatches[3])!=1) || $mnmatches[2]>=$mnmatches[3])  {
					return false;
				}
			}
		} else if (preg_match('/^\s*\-?\s*\d+\s*$/',$tocheck)) { //integer

		} else { //not a valid format
			return false;
		}
	}

	if (in_array("nodecimal",$ansformats)) {
		if (strpos($tocheck,'.')!==false) {
			return false;
		}
		if (strpos($tocheck,'E-')!==false) {
			return false;
		}
		if (preg_match('/10\^\(?\-/',$tocheck)) {
			return false;
		}
	}
	return true;
}

function formathint($eword,$ansformats,$reqdecimals,$calledfrom, $islist=false,$doshort=false) {
	$tip = '';
	$shorttip = '';
	if (in_array('set',$ansformats) || in_array('exactset',$ansformats)) {
		$listtype = "set";
	} else {
		$listtype = "list";
	}
	if (in_array('fraction',$ansformats)) {
		$tip .= sprintf(_('Enter %s as a fraction (like 3/5 or 10/4) or as an integer (like 4 or -2)'), $eword);
		$shorttip = $islist?sprintf(_('Enter a %s of fractions or integers'), $listtype):_('Enter a fraction or integer');
	} else if (in_array('reducedfraction',$ansformats)) {
		if (in_array('fracordec',$ansformats)) {
			$tip .= sprintf(_('Enter %s as a reduced fraction (like 5/3, not 10/6), as an integer (like 4 or -2), or as a decimal (like 0.5 or 1.25)'), $eword);
			$shorttip = $islist?sprintf(_('Enter a %s of reduced fractions, integers, or decimals'), $listtype):_('Enter a reduced fraction, integer, or decimal');
		} else {
			$tip .= sprintf(_('Enter %s as a reduced fraction (like 5/3, not 10/6) or as an integer (like 4 or -2)'), $eword);
			$shorttip = $islist?sprintf(_('Enter a %s of reduced fractions or integers'), $listtype):_('Enter a reduced fraction or integer');
		}
	} else if (in_array('mixednumber',$ansformats)) {
		if (in_array("allowunreduced",$ansformats)) {
			$tip .= sprintf(_('Enter %s as a mixed number or as an integer.  Example: 2 1/2 = 2 &frac12;'), $eword);
		} else {
			$tip .= sprintf(_('Enter %s as a reduced mixed number or as an integer.  Example: 2 1/2 = 2 &frac12;'), $eword);
		}
		$shorttip = $islist?sprintf(_('Enter a %s of mixed numbers or integers'), $listtype):_('Enter a mixed number or integer');
	} else if (in_array('mixednumberorimproper',$ansformats)) {
		if (in_array("allowunreduced",$ansformats)) {
			$tip .= sprintf(_('Enter %s as a mixed number, proper or improper fraction, or as an integer.  Example: 2 1/2 = 2 &frac12;'), $eword);
			$shorttip = $islist?sprintf(_('Enter a %s of mixed numbers, fractions, or integers'), $listtype):_('Enter a mixed number, proper or improper fraction, or integer');
		} else {
			$tip .= sprintf(_('Enter %s as a reduced mixed number, reduced proper or improper fraction, or as an integer.  Example: 2 1/2 = 2 &frac12;'), $eword);
			$shorttip = $islist?sprintf(_('Enter a %s of mixed numbers, fractions, or integers'), $listtype):_('Enter a reduced mixed number, proper or improper fraction, or integer');
		}
	} else if (in_array('fracordec',$ansformats)) {
		if (in_array("allowmixed",$ansformats)) {
			$tip .= sprintf(_('Enter %s as a mixed number (like 2 1/2), fraction (like 3/5), an integer (like 4 or -2), or decimal (like 0.5 or 1.25)'), $eword);
			$shorttip = $islist?sprintf(_('Enter a %s of mixed numbers, fractions, or decimals'), $listtype):_('Enter a mixed number, fraction, or decimal');
		} else {
			$tip .= sprintf(_('Enter %s as a fraction (like 3/5 or 10/4), an integer (like 4 or -2), or decimal (like 0.5 or 1.25)'), $eword);
			$shorttip = $islist?sprintf(_('Enter a %s of fractions or decimals'), $listtype):_('Enter a fraction or decimal');
		}
	} else if (in_array('decimal',$ansformats)) {
		$tip .= sprintf(_('Enter %s as an integer or decimal value (like 5 or 3.72)'), $eword);
		$shorttip = $islist?sprintf(_('Enter a %s of integer or decimal values'), $listtype):_('Enter an integer or decimal value');
	} else if (in_array('scinotordec',$ansformats)) {
		$tip .= sprintf(_('Enter %s as a decimal or in scientific notation.  Example: 3*10^2 = 3 &middot; 10<sup>2</sup>'), $eword);
		$shorttip = $islist?sprintf(_('Enter a %s of numbers using decimals or scientific notation'), $listtype):_('Enter a number using decimals or scientific notation');
	} else if (in_array('scinot',$ansformats)) {
		$tip .= sprintf(_('Enter %s as in scientific notation.  Example: 3*10^2 = 3 &middot; 10<sup>2</sup>'), $eword);
		$shorttip = $islist?sprintf(_('Enter a %s of numbers using scientific notation'), $listtype):_('Enter a number using scientific notation');
	} else {
		$tip .= sprintf(_('Enter %s as a number (like 5, -3, 2.2172) or as a calculation (like 5/3, 2^3, 5+4)'), $eword);
		$shorttip = $islist?sprintf(_('Enter a %s of mathematical expressions'), $listtype):_('Enter a mathematical expression');
	}
	if ((in_array('fraction',$ansformats) || in_array('reducedfraction',$ansformats)) && !in_array('allowmixed',$ansformats)) {
		$tip .= '<br/>'._('Do not enter mixed numbers');
		$shorttip .= _(' (no mixed numbers)');
	}
	if (!in_array('nosoln',$ansformats) && !in_array('nosolninf',$ansformats)) {
		if ($calledfrom == 'calcinterval') {
			$tip .= "<br/>" . _('Enter DNE for an empty set. Use oo to enter Infinity.');
		} else if ($calledfrom != 'calcmatrix') {
			$tip .= "<br/>" . _('Enter DNE for Does Not Exist, oo for Infinity');
		}
	} else if ($calledfrom == 'calcinterval') {
		$tip .= "<br/>" . _('Use oo to enter Infinity.');
	}
	if (in_array('nodecimal',$ansformats)) {
		$tip .= "<br/>" . _('Decimal values are not allowed');
	} else if (isset($reqdecimals) && $reqdecimals !== null) {
		if ($reqdecimals == 0) {
			$tip .= "<br/>" . sprintf(_('Enter %s accurate to the nearest integer.'), $eword);
		} else {
			$tip .= "<br/>" . sprintf(_('Enter %s accurate to %d decimal places.'), $eword, $reqdecimals);
		}
	}
	if (in_array('notrig',$ansformats)) {
		$tip .= "<br/>" . _('Trig functions (sin,cos,etc.) are not allowed');
    }
    if (in_array('allowdegrees',$ansformats)) {
		$tip .= "<br/>" . _('Degrees are allowed');
	}
	if ($doshort) {
		return array($tip,$shorttip);
	} else {
		return $tip;
	}
}

function getcolormark($c,$wrongformat=false) {
	global $imasroot,$staticroot;

	if (isset($GLOBALS['nocolormark'])) { return '';}

	if ($c=='ansred') {
		return '<img class="scoreboxicon" src="'.$staticroot.'/img/redx.gif" width="8" height="8" alt="'._('Incorrect').'"/>';
	} else if ($c=='ansgrn') {
		return '<img class="scoreboxicon" src="'.$staticroot.'/img/gchk.gif" width="10" height="8" alt="'._('Correct').'"/>';
	} else if ($c=='ansorg') {
		return '<img class="scoreboxicon" src="'.$staticroot.'/img/orgx.gif" width="8" height="8" alt="'._('Correct answer, but wrong format').'"/>';
	} else if ($c=='ansyel') {
		return '<img class="scoreboxicon" src="'.$staticroot.'/img/ychk.gif" width="10" height="8" alt="'._('Partially correct').'"/>';
	} else {
		return '';
	}
}

function setupnosolninf($qn, $answerbox, $answer, $ansformats, $la, $ansprompt, $colorbox, $format="number") {
	$answerbox = preg_replace('/<label.*?<\/label>/','',$answerbox);  //remove existing ansprompt

	$answerbox = str_replace('<table ','<table style="display:inline-table;vertical-align:middle" ', $answerbox);
	$nosoln = _('No solution');
	$infsoln = _('Infinite number of solutions');
    $partnum = $qn%1000;
    $out = '';
    $includeinf = in_array('nosolninf',$ansformats);

	if (in_array('list',$ansformats) || in_array('exactlist',$ansformats) || in_array('orderedlist',$ansformats)) {
		$specsoln = _('One or more solutions: ');
	} else if ($format=='interval') {
		$specsoln = _('Interval notation solution: ');
	} else if ($format=='inequality') {
		$specsoln = _('Inequality notation solution: ');
	} else {
		$specsoln = _('One solution: ');
	}

	if (isset($ansprompt)) {
		$anspromptp = explode(';', $ansprompt);
		unset($ansprompt);
		$specsoln = $anspromptp[0];
		if (count($anspromptp)>1) {
			$nosoln = $anspromptp[1];
		}
		if (count($anspromptp)>2) {
			$infsoln = $anspromptp[2];
		}
	}
	$out .= '<div id="qnwrap'.$qn.'" class="'.$colorbox.'" role="group" ';
  if (preg_match('/aria-label=".*?"/', $answerbox, $arialabel)) {
    $answerbox = preg_replace('/aria-label=".*?"/',
      'aria-label="'.Sanitize::encodeStringForDisplay(str_replace('`','',$specsoln)).'"', $answerbox);
    $out .= $arialabel[0];
  }
  $out .= '>';
	$out .= '<ul class="likelines">';
	$out .= '<li><input type="radio" id="qs'.$qn.'-s" name="qs'.$qn.'" value="spec" ' .
        (($la!='DNE' && (!$includeinf || $la!='oo'))?'checked':'') . 
        '><label for="qs'.$qn.'-s">'.$specsoln.'</label>';
	if ($la=='DNE' || ($includeinf && $la=='oo')) {
		$laqs = $la;
		$answerbox = str_replace('value="'.$la.'"','value=""', $answerbox);
	} else {
		$laqs = '';
	}

	$out .= str_replace(array($colorbox,getcolormark($colorbox)),'',$answerbox);

	$out .= '<span id="previewloctemp'.$partnum.'"></span>';
	$out .= '</li>';

	$out .= '<li><input type="radio" id="qs'.$qn.'-d" name="qs'.$qn.'" value="DNE" '.($laqs=='DNE'?'checked':'').'><label for="qs'.$qn.'-d">'.$nosoln.'</label></li>';
	if ($includeinf) {
		$out .= '<li><input type="radio" id="qs'.$qn.'-i" name="qs'.$qn.'" value="inf" '.($laqs=='oo'?'checked':'').'><label for="qs'.$qn.'-i">'.$infsoln.'</label></li>';
	}
	$out .= '</ul>';
	//$out .= '<span class="floatright">'.getcolormark($colorbox).'</span>';
	$out .= '</div>';

	if (preg_match('/^inf/',$answer) || $answer==='oo' || $answer===$infsoln) {
		$answer = '"'.$infsoln.'"';
	}
	if (preg_match('/^no\s*solution/',$answer) || $answer==='DNE' || $answer===$nosoln) {
		$answer = '"'.$nosoln.'"';
	}

	return array($out,$answer);
}

function scorenosolninf($qn, $givenans, $answer, $ansprompt, $format="number") {
	$nosoln = _('No solution');
	$infsoln = _('Infinite number of solutions');
	if (isset($ansprompt)) {
		$anspromptp = explode(';', $ansprompt);
		unset($ansprompt);
		$specsoln = $anspromptp[0];
		if (count($anspromptp)>1) {
			$nosoln = $anspromptp[1];
		}
		if (count($anspromptp)>2) {
			$infsoln = $anspromptp[2];
		}
	}
	if (preg_match('/^inf/',$answer) || $answer===$infsoln) {
		$answer = 'oo';
	}
	if (preg_match('/^no\s*solution/',$answer) || $answer===$nosoln) {
		$answer = 'DNE';
	}
	$qs = $_POST["qs$qn"] ?? '';
	if ($qs=='DNE') {
		$givenans = "DNE";
	} else if ($qs=='inf') {
		$givenans = "oo";
	}

	return array($givenans, $answer);
}

function rawscoretocolor($sc,$aw) {
	if ($aw==0) {
		return '';
	} else if ($sc<0) {
		return '';
	} else if ($sc==0) {
		return 'ansred';
	} else if ($sc>.98) {
		return 'ansgrn';
	} else {
		return 'ansyel';
	}
}

function normalizemathunicode($str) {
	$str = preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/', '', $str);
	$str = str_replace(array('‒','–','—','―','−'),'-',$str);
	$str = str_replace(array('⁄','∕','⁄ ','÷'),'/',$str);
	$str = str_replace(array('（','）','∞','∪','≤','≥','⋅','·'), array('(',')','oo','U','<=','>=','*','*'), $str);
	//these are the slim vector unicodes: u2329 and u232a
    $str = str_replace(array('⟨','⟩'), array('<','>'), $str);
    $str = str_replace(['⁰','¹','²','³','⁴','⁵','⁶','⁷','⁸','⁹'], ['^0','^1','^2','^3','^4','^5','^6','^7','^8','^9'], $str);
	$str = str_replace(array('₀','₁','₂','₃'), array('_0','_1','_2','_3'), $str);
    $str = str_replace(array('√','∛','°'),array('sqrt','root(3)','degree'), $str);
	$str = preg_replace('/\b(OO|infty)\b/i','oo', $str);
  if (strtoupper(trim($str))==='DNE') {
    $str = 'DNE';
  }
	return $str;
}

if (!function_exists('stripslashes_deep')) {
	function stripslashes_deep($value) {
		return (is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value));
	}
}

// hasarrayval: 0 not array, 1 may be array, 2 must be array
function getOptionVal($options, $key, $multi, $partnum, $hasarrayval=0) {
    if (isset($options[$key])) {
        if ($multi) {
            if ($hasarrayval == 2) { // the normal option value must be an array, so we have to do more logic
                if (is_array($options[$key])) {
                    if (isset($options[$key][$partnum]) && is_array($options[$key][$partnum])) {
                        // we have an array at the part index
                        return $options[$key][$partnum];
                    } else {
                        // no array at part index
                        // check if entries that do exist are arrays
                        if (is_array(current($options[$key]))) {
                            // other entries are array, so this one just isn't defined
                            // do nothing
                        } else {
                            // so array must be intended for all parts
                            return $options[$key];
                        }
                    }
                } // else invalid value - should be array
            } else {
                if (is_array($options[$key])) {
                    if (isset($options[$key][$partnum])) {
                        return $options[$key][$partnum];
                    } 
                } else {
                    return $options[$key];
                }
            }
        } else {
            // single part question.
            if (!is_array($options[$key]) || $hasarrayval > 0) {
                // the normal option value may be an array, or option val is not array
                // just return it
                return $options[$key];
            } 
        }
    }
    // value not found
    if ($key === 'answers') {
        // common mistake to use $answer instead - look for that.
        $altval = getOptionVal($options, 'answer', $multi, $partnum, $hasarrayval);
        return $altval;
    }
    // no value - return empty string
    return '';
}

function rewritePlusMinus($str) {
    return preg_replace('/(.*?)\+\-(.*?)(,|$)/','$1+$2,$1-$2$3',$str);
}

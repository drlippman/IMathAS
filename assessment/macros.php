<?php
//IMathAS:  Core randomizers and display macros
//(c) 2006 David Lippman

require_once __DIR__ . '/../includes/Rand.php';
$GLOBALS['RND'] = new Rand();

array_push($GLOBALS['allowedmacros'],"exp","nthlog",
 "sinn","cosn","tann","secn","cscn","cotn","rand","rrand","rands","rrands",
 "randfrom","randsfrom","jointrandfrom","diffrandsfrom","nonzerorand",
 "nonzerorrand","nonzerorands","nonzerorrands","diffrands","diffrrands",
 "nonzerodiffrands","nonzerodiffrrands","singleshuffle","jointshuffle","is_array",
 "makepretty","makeprettydisp","showplot","addlabel","showarrays","horizshowarrays",
 "showasciisvg","listtoarray","arraytolist","calclisttoarray","sortarray","consecutive",
 "gcd","lcm","calconarray","mergearrays","sumarray","dispreducedfraction","diffarrays",
 "intersectarrays","joinarray","unionarrays","count","polymakepretty",
 "polymakeprettydisp","makexpretty","makexprettydisp","calconarrayif","in_array",
 "prettyint","prettyreal","prettysigfig","roundsigfig","arraystodots","subarray",
 "showdataarray","arraystodoteqns","array_flip","arrayfindindex","fillarray",
 "array_reverse","root","getsnapwidthheight","is_numeric","is_nan","sign","sgn","prettynegs",
 "dechex","hexdec","print_r","replacealttext","randpythag","changeimagesize","mod","fmod",
 "numtowords","randname","randnamewpronouns","randmalename","randfemalename",
 "randnames","randmalenames","randfemalenames","randcity","randcities","prettytime",
 "definefunc","evalfunc","evalnumstr","safepow","arrayfindindices","stringtoarray","strtoupper",
 "strtolower","ucfirst","lcfirst","makereducedfraction","makereducedmixednumber","stringappend",
 "stringprepend","textonimage","addplotborder","addlabelabs","makescinot","today",
 "numtoroman","sprintf","arrayhasduplicates","addfractionaxislabels","decimaltofraction",
 "ifthen","cases","multicalconarray","htmlentities","formhoverover","formpopup","connectthedots",
 "jointsort","stringpos","stringlen","stringclean","substr","substr_count","str_replace",
 "makexxpretty","makexxprettydisp","forminlinebutton","makenumberrequiretimes",
 "comparenumbers","comparefunctions","getnumbervalue","showrecttable","htmldisp",
 "getstuans","checkreqtimes","stringtopolyterms","getfeedbackbasic","getfeedbacktxt",
 "getfeedbacktxtessay","getfeedbacktxtnumber","getfeedbacktxtnumfunc",
 "getfeedbacktxtcalculated","explode","gettwopointlinedata","getdotsdata","getntupleparts",
 "getopendotsdata","gettwopointdata","gettwopointformulas","getlinesdata","getineqdata","adddrawcommand",
 "mergeplots","array_unique","ABarray","scoremultiorder","scorestring","randstate",
 "randstates","prettysmallnumber","makeprettynegative","rawurlencode","fractowords",
 "randcountry","randcountries","sorttwopointdata","addimageborder","formatcomplex",
 "array_values","array_keys","comparelogic","comparesetexp","stuansready","comparentuples","comparenumberswithunits",
 "isset","atan2","keepif","checkanswerformat","preg_match","intval","comparesameform","splicearray",
 "getsigfigs","checksigfigs","prettysigfig_instring","prettyreal_instring","round_instring");

function mergearrays() {
	$args = func_get_args();
	foreach ($args as $k=>$arg) {
		if (!is_array($arg)) {
			$args[$k] = array($arg);
		}
	}
	return call_user_func_array('array_merge',$args);
}
function arrayfindindex($n,$h) {
	return array_search($n,$h);
}
function arrayfindindices($n,$h) {
	return array_keys($h,$n);
}
function stringlen($str) {
	return strlen($str);
}
function stringpos($n,$h) {
    if (!is_scalar($h) || !is_scalar($n)) {
        echo "inputs to stringpos must be strings";
        return -1;
    }
	$p = strpos($h,$n);
	if ($p===false) {
		$p = -1;
	}
	return $p;
}
function stringclean($str,$mode=0) {
	switch($mode) {
	case 0: return trim($str); break;
	case 1: return preg_replace('/\s/','',$str); break;
	case 2: return preg_replace('/\W/','',$str); break;
	}
}
function stringtoarray($str) {
        $str_array=array();
        $len=strlen($str);
        for($i=0;$i<$len;$i++) {$str_array[]=$str[$i];}
        return $str_array;
}
function jointsort() {
	$in = func_get_args();
	if (count($in)<2 || !is_array($in[0]) || !is_array($in[1])) {
		echo "jointsort needs at least two input arrays";
		return array();
	}
	$a = array_shift($in);
    for($i=0;$i<count($in);$i++) {
        if (!is_array($in[$i]) || count($in[$i]) !== count($a)) {
            echo "inputs to jointsort need to be arrays of same length";
            return $in;
        }
    }
	asort($a);
	$out = array();
	foreach ($a as $k=>$v) {
		for($i=0;$i<count($in);$i++) {
			$out[$i][] = $in[$i][$k];
		}
	}
	$a = array_values($a);
	array_unshift($out,$a);
	return $out;
}

//$funcs can be a string or an array of strings.  Each string should have format:
//"function,color,xmin,xmax,startmarker,endmarker,strokewidth,strokedash"
//not all entries are required.  To skip middle ones, leave them empty
function showplot($funcs) { //optional arguments:  $xmin,$xmax,$ymin,$ymax,labels,grid,width,height
	if (!is_array($funcs)) {
		settype($funcs,"array");
	}
	$settings = array(-5,5,-5,5,1,1,200,200);
	for ($i = 1; $i < func_num_args(); $i++) {
        $v = func_get_arg($i);
        if ($v === null) { $v = 0; }
        if (!is_scalar($v)) {
            echo 'Invalid input '.($i+1).' to showplot';
        } else {
            $settings[$i-1] = $v;
        }
	}
	$fqonlyx = false; $fqonlyy = false;
	if (strpos($settings[0],'0:')!==false) {
		$fqonlyx = true;
		$settings[0] = substr($settings[0],2);
	}
	if (strpos($settings[2],'0:')!==false) {
		$fqonlyy = true;
		$settings[2] = substr($settings[2],2);
	}
	$yminauto = false;
	$ymaxauto = false;
	if (substr($settings[2],0,4)=='auto') {
		$yminauto = true;
		if (strpos($settings[2],':')!==false) {
			$ypts = explode(':',$settings[2]);
			$settings[2] = $ypts[1];
		} else {
			$settings[2] = -5;
		}
	}
	if (substr($settings[3],0,4)=='auto') {
		$ymaxauto = true;
		if (strpos($settings[3],':')!==false) {
			$ypts = explode(':',$settings[3]);
			$settings[3] = $ypts[1];
		} else {
			$settings[3] = 5;
		}
	}
	$winxmin = is_numeric($settings[0])?$settings[0]:-5;
	$winxmax = is_numeric($settings[1])?$settings[1]:5;
	$ymin = is_numeric($settings[2])?$settings[2]:-5;
	$ymax = is_numeric($settings[3])?$settings[3]:5;
	$plotwidth = is_numeric($settings[6])?$settings[6]:200;
	$plotheight = is_numeric($settings[7])?$settings[7]:200;
    $noyaxis = false;
    $noxaxis = false;
	if (is_numeric($ymin) && is_numeric($ymax) && $ymin==0 && $ymax==0) {
		$ymin = -0.5;
		$ymax = 0.5;
		$noyaxis = true;
		$settings[2] = -0.5;
		$settings[3] = 0.5;
	}
	$xxmin = $winxmin - 5*($winxmax - $winxmin)/$plotwidth;
	$xxmax = $winxmax + 5*($winxmax - $winxmin)/$plotwidth;
	$yymin = $ymin - 5*($ymax - $ymin)/$plotheight;
	$yymax = $ymax + 5*($ymax - $ymin)/$plotheight;

	//$commands = "setBorder(5); initPicture({$settings[0]},{$settings[1]},{$settings[2]},{$settings[3]});";
	//$alt = "Graph, window x {$settings[0]} to {$settings[1]}, y {$settings[2]} to {$settings[3]}.";
	$commands = '';
	$alt = '';
	if (strpos($settings[4],':')) {
		$lbl = explode(':',$settings[4]);
        $lbl[0] = evalbasic($lbl[0], true, true);
        $lbl[1] = evalbasic($lbl[1], true, true);
	} else {
        $settings[4] = evalbasic($settings[4], true, true);
        $lbl = [];
	}
	if (is_numeric($settings[4]) && $settings[4]>0) {
		$commands .= 'axes('.$settings[4].','.$settings[4].',1';
	} else if (isset($lbl[0]) && is_nicenumber($lbl[0])) {
        if ($lbl[0]==0) {
            $lbl[0] = 1;
            $noxaxis = true;
        }
		if ($lbl[1]==0) {
			$lbl[1] = 1;
			$noyaxis = true;
		}
		if (!isset($lbl[2]) || $lbl[2]!='off') {  //allow xscl:yscl:off for ticks but no labels
			$commands .= 'axes('.$lbl[0].','.$lbl[1].',1';
		} else {
			$commands .= 'axes('.$lbl[0].','.$lbl[1].',null';
		}
	} else {
		$commands .= 'axes(1,1,null';
	}

	if (strpos($settings[5],':')) {
		$settings[5] = str_replace(array('(',')'),'',$settings[5]);
		$grid = explode(':',$settings[5]);
        foreach ($grid as $i=>$v) {
            $grid[$i] = evalbasic($v, true, true);
        }
	} else {
		$settings[5] = evalbasic($settings[5], true, true);
	}
	if (is_numeric($settings[5]) && $settings[5]>0) {
		$commands .= ','.$settings[5].','.$settings[5];
	} else if (isset($grid[0]) && is_numeric($grid[0]) ) {
		$commands .= ','.$grid[0].','.$grid[1];
	} else {
		$commands .= ',0,0';
		//$commands .= ');';
	}

	if ($noyaxis==true || $noxaxis==true) {
		$commands .= ','.($noxaxis?0:1).','.($noyaxis?0:1).',1);';
	} else if ($fqonlyx || $fqonlyy) {
		$commands .= ','.($fqonlyx?'"fq"':1).','.($fqonlyy?'"fq"':1).');';
	} else {
		$commands .= ');';
	}
    $xvar = 'x';
    $yvar = 'y';
    $hasaxislabels = false;
	if (isset($lbl) && count($lbl)>3) {
        $xvar = $lbl[2];
        $yvar = $lbl[3];
        if ($xvar != '') {
		    $commands .= "text([{$winxmax},0],\"{$xvar}\",\"aboveleft\");";
        }
        if ($yvar != '') {
		    $commands .= "text([0,{$ymax}],\"{$yvar}\",\"belowright\");";
        }
        $hasaxislabels = true;
	}
	$absymin = 1E10;
	$absymax = -1E10;
    $globalalt = '';
	foreach ($funcs as $function) {
		if ($function=='') { continue;}
        if (substr($function,0,4) == 'alt:') {
            $globalalt = substr($function, 4);
            continue;
        }
        $function = str_replace('\\,','&x44;', $function);
		$function = listtoarray($function);
        if (!isset($function[0]) || $function[0]==='') { continue; }
		//correct for parametric
		$isparametric = false;
		$isineq = false;
		$isxequals = false;
		//has y= when it shouldn't
		if ($function[0][0] == 'y') {
			$function[0] = preg_replace('/^\s*y\s*=?/', '', $function[0]);
            if ($function[0]==='') { continue; }
		}

		if ($function[0]=='dot') {  //dot,x,y,[closed,color,label,labelloc]
			if (!isset($function[4]) || $function[4]=='') {
				$function[4] = 'black';
			}

			$path = 'stroke="'.$function[4].'";';
			$path .= 'dot(['.$function[1].','.$function[2].']';
			$coord = '('.$function[1].','.$function[2].')';
			if (isset($function[3]) && $function[3]=='open') {
				$path .= ',"open"';
				$alt .= sprintf(_('Open dot at %s'), $coord);
			} else {
				$path .= ',"closed"';
				$alt .= sprintf(_('Dot at %s'), $coord);
			}
			$alt .= ', color '.$function[4];

			if (isset($function[5]) && $function[5]!='') {
                $function[5] = str_replace('&x44;', ',', $function[5]);
				if (!isset($function[6])) {
					$function[6] = 'above';
				}
				$path .= ',"'.Sanitize::encodeStringForJavascript($function[5]).'"';
				$path .= ',"'.$function[6].'"';
				$alt .= ', labeled '.$function[5];
			}
			$alt .= '. ';
			$path .= ');';
			$commands .= $path;
			continue; //skip the stuff below
		} else if ($function[0]=='text') {  //text,x,y,textstring,color,loc,angle
            $function[3] = str_replace('&x44;', ',', $function[3]);
            if (!isset($function[4]) || $function[4]=='') {
				$function[4] = 'black';
			}
			if (!isset($function[5])) {
				$function[5] = 'centered';
			}
			if (!isset($function[6])) {
				$function[6] = 0;
			} else {
				$function[6] = intval($function[6]);
			}
			$path = 'fontfill="'.$function[4].'";';
			$path .= 'text(['.$function[1].','.$function[2].'],"'.$function[3].'","'.$function[5].'",'.$function[6].');';
			$coord = '('.$function[1].','.$function[2].')';
			$alt .= sprintf(_('Text label, color %s, at %s reading: %s'), $function[4], $coord, $function[3]).'. ';
			$commands .= $path;
			continue; //skip the stuff below
		} else if ($function[0][0]=='[') { //strpos($function[0],"[")===0) {
			$isparametric = true;
			$xfunc = makepretty(str_replace("[","",$function[0]));
			$evalxfunc = makeMathFunction($xfunc, "t", [], '', true);
			$yfunc = makepretty(str_replace("]","",$function[1]));
			$evalyfunc = makeMathFunction($yfunc, "t", [], '', true);
			array_shift($function);
			if ($evalxfunc===false || $evalyfunc===false) {continue;}
		} else if ($function[0][0]=='<' || $function[0][0]=='>') {
			$isineq = true;
			if ($function[0][1]=='=') {
				$ineqtype = substr($function[0],0,2);
				$func = makepretty(substr($function[0],2));
			} else {
				$ineqtype = $function[0][0];
				$func = makepretty(substr($function[0],1));
			}
			$evalfunc = makeMathFunction($func, "x", [], '', true);
			if ($evalfunc===false) {continue;}
		} else if (strlen($function[0])>1 && $function[0][0]=='x' && ($function[0][1]=='<' || $function[0][1]=='>' || $function[0][1]=='=')) {
			$isxequals = true;
			if ($function[0][1]=='=') {
				$val = substr($function[0],2);
				if (!is_numeric($val)) {
					// convert to parametric
					$isxequals = false;
					$isparametric = true;
					$yfunc = "t";
					$evalyfunc = makeMathFunction("t", "t");
					$xfunc = makepretty(str_replace('y','t',$val));
					$evalxfunc = makeMathFunction($xfunc, "t", [], '', true);
					if ($evalxfunc===false || $evalyfunc===false) {continue;}
				}
			} else {
				$isineq = true;
				if ($function[0][2]=='=') {
					$ineqtype = substr($function[0],1,2);
					$val= substr($function[0],3);
				} else {
					$ineqtype = $function[0][1];
					$val = substr($function[0],2);
				}
			}
		} else {
			$func = makepretty($function[0]);
			$evalfunc = makeMathFunction($func, "x", [], '', true);
			if ($evalfunc===false) {continue;}
		}

		//even though ASCIIsvg has a plot function, we'll calculate it here to hide the function

		$alt .= "Start Graph";
		$path = '';
		if (isset($function[1]) && $function[1]!='') {
			$path .= "stroke=\"{$function[1]}\";";
			$alt .= ", Color {$function[1]}";
		} else {
			$path .= "stroke=\"black\";";
			$alt .= ", Color black";
		}
		if (isset($function[6]) && $function[6]!='') {
			$path .= "strokewidth=\"{$function[6]}\";";
		} else {
			$path .= "strokewidth=\"1\";";
		}
		if ($isineq && strlen($ineqtype)==1) {  //is < or >
			$path .= "strokedasharray=\"5\";";
			$alt .= ", Dashed";
		} else if (isset($function[7]) && $function[7]!='') {
			if ($function[7]=="dash") {
				$path .= "strokedasharray=\"5\";";
				$alt .= ", Dashed";
			} else {
				$path .= "strokedasharray=\"none\";";
			}
		} else {
			$path .= "strokedasharray=\"none\";";
		}
		$path .= "fill=\"none\";";

		if ($isxequals) { //handle x-equals case separately
			if (isset($function[2]) && $function[2]!='') {
				$thisymin = $function[2];
			} else {$thisymin = $yymin; }
			if (isset($function[3]) && $function[3]!='') {
				$thisymax = $function[3];
			} else {$thisymax = $yymax;}
			$alt .= "<table class=stats><thead><tr><th>$xvar</th><th>$yvar</th></thead></tr><tbody>";
			$alt .= "<tr><td>$val</td><td>$thisymin</td></tr>";
			$alt .= "<tr><td>$val</td><td>$thisymax</td></tr>";
			$alt .= '</tbody></table>';
			$path .= "line([$val,$thisymin],[$val,$thisymax]);";
			if ($isineq) {
                $path .= "stroke=\"none\";strokedasharray=\"none\";";
                if (isset($function[1]) && ($function[1]=='red' || $function[1]=='green')) {
                    $path .= "fill=\"trans{$function[1]}\";";
                } else {
                    $path .= "fill=\"transblue\";";
                }
				if ($ineqtype[0]=='<') {
					$path .= "rect([$xxmin,$thisymin],[$val,$thisymax]);";
					$alt .= "Shaded left";
				} else {
					$path .= "rect([$val,$thisymin],[$xxmax,$thisymax]);";
					$alt .= "Shaded right";
				}
			}
			$commands .= $path;
			continue;
		}
		$avoid = array();
		$domainlimited = false;
		if (isset($function[2]) && $function[2]!='') {
			$xmin = evalbasic($function[2], true, true);
			$domainlimited = true;
            if (!is_numeric($xmin)) {
                echo "Invalid function xmin $xmin";
                continue;
            }
		} else {$xmin = $winxmin;}
		if (isset($function[3]) && $function[3]!='') {
			$xmaxarr = explode('!',$function[3]);
			if ($xmaxarr[0] != '') {
				$xmax = evalbasic($xmaxarr[0], true, true);
			} else {
				$xmax = $winxmax;
			}
            if (!is_numeric($xmax)) {
                echo "Invalid function xmax $xmax";
                continue;
            }
			if (count($xmaxarr)>1) {
				$avoid = array_slice($xmaxarr,1);
				sort($avoid);
			}
			$domainlimited = true;
		} else {$xmax = $winxmax;}

		if ($_SESSION['graphdisp']==0) {
			if ($xmax-$xmin>2 || $xmax==$xmin) {
				$dx = 1;
				$stopat = ($xmax-$xmin)+1;
			} else {
				$dx = ($xmax-$xmin)/10;
				$stopat = 11;//($domainlimited?10:11);
			}
			if ($xmax != $xmin) {
				$alt .= "<table class=stats><thead><tr><th>$xvar</th><th>$yvar</th></thead></tr><tbody>";
			} else {
				$alt .= '. ';
			}
		} else {
			$dx = ($xmax - $xmin + ($domainlimited?0:10*($xmax-$xmin)/$plotwidth) )/100;
			$stopat = ($domainlimited?101:102);
			if ($xmax==$xmin) {
				$stopat = 1;
			}
		}
		if ($xmax==$xmin) {
			$xrnd = 6;
			$yrnd = 6;
		} else {
			$xrnd = max(0,intval(floor(-log10(abs($xmax-$xmin))-1e-12))+5);
			$yrnd = max(0,intval(floor(-log10(abs($ymax-$ymin))-1e-12))+5);
		}

		$lasty = 0;
		$lastl = 0;
		$px = null;
		$py = null;
		$pyorig = null;
		$pathstr = '';
		$firstpoint = false;
		$nextavoid = null;
		$fx = array();  $fy = array();
		$yyaltmin = $yymin-.5*($yymax-$yymin);
		$yyaltmax = $yymax+.5*($yymax-$yymin);
		if (count($avoid)>0) {
			$nextavoid = array_shift($avoid);
		}
		for ($i = 0; $i<$stopat;$i++) {
			if ($isparametric) {
				$t = $xmin + $dx*$i + 1E-10;
				if (in_array($t,$avoid)) { continue;}
				$x = $evalxfunc(['t'=>$t]);
				$yorig = $evalyfunc(['t'=>$t]);
				if (isNaN($x) || isNaN($yorig)) {
					continue;
				}
				$x = round($x,$xrnd);//round(eval("return ($xfunc);"),3);
				$y = round($yorig,$yrnd);//round(eval("return ($yfunc);"),3);
				if ($xmax != $xmin && $y>$yyaltmin && $y<$yyaltmax) {
					$alt .= "<tr><td>$x</td><td>$y</td></tr>";
				}
			} else {
				$x = $xmin + $dx*$i + (($i<$stopat/2)?1E-10:-1E-10) - (($domainlimited || $_SESSION['graphdisp']==0)?0:5*abs($xmax-$xmin)/$plotwidth);
				if (in_array($x,$avoid)) { continue;}
				//echo $func.'<br/>';
                $yorig = $evalfunc(['x'=>$x]);

				if (isNaN($yorig)) {
                    if ($lastl != 0) {
                        if ($py !== null) {
                            $pathstr .= ",[$px,$py]";
                        }
                        $pathstr .= ']);';
                        $lastl = 0;
                        $px = null;
                        $py = null;
                    }
					continue;
				}
				$y = round($yorig,$yrnd);//round(eval("return ($func);"),3);
				$x = round($x,$xrnd);
				if ($xmax != $xmin && $y>$yyaltmin && $y<$yyaltmax) {
					$alt .= "<tr><td>$x</td><td>$y</td></tr>";
				}
			}

			if ($i<2 || $i==$stopat-2) {
				$fx[$i] = $x;
				$fy[$i] = $y;
			}

			if (isNaN($y)) {
				continue;
			}

			if ($py===null) { //starting line

			} else if ($y>$yymax || $y<$yymin) { //going or still out of bounds
                if ($py <= $yymax && $py >= $yymin) { //going out
                    
                    $origy = $y;
					if ($isparametric) {
						$y = $evalyfunc(['t'=>$t-1E-10]);
						$tempy = $evalyfunc(['t'=>$t-$dx/100-1E-10]);
						$temppy = $evalyfunc(['t'=>$t - $dx + $dx/100]);
					} else {
						$y = $evalfunc(['x'=>$x-1E-10]);
						$tempy = $evalfunc(['x'=>$x-$dx/100-1E-10]);
						$temppy = $evalfunc(['x'=>$px + 1/pow(10,$xrnd)]);
                    }

					if ($temppy > $pyorig) {//if ($tempy<$y) { // going up
						$iy = $yymax;
						//if jumping from top of graph to bottom, change value
						//for interpolation purposes
						if ($y<$yymin) { $y = $yymax+.5*($ymax-$ymin);}
					} else { //going down
						$iy = $yymin;
						if ($y>$yymax) { $y = $yymin-.5*($ymax-$ymin);}
					}
					$ix = round(($x-$px)*($iy - $py)/($y-$py) + $px,$xrnd);
					if ($lastl == 0) {$pathstr .= "path([";} else { $pathstr .= ",";}
                    $pathstr .= "[$px,$py],[$ix,$iy]]);";
                    if ($y < $yymax && $y > $yymin) { // lost out of boundness. restore orig
                        $y = $origy;
                    }
					$lastl = 0;
                } else { //still out

				}
			} else if ($py>$yymax || $py<$yymin) { //coming or staying in bounds?
                if ($y <= $yymax && $y >= $yymin) { //coming in
					//need to determine which direction.  Let's calculate an extra value
					//and need un-rounded y-value for comparison
					if ($isparametric) {
						$y = $evalyfunc(['t'=>$t-1E-10]);
						$tempy = $evalyfunc(['t'=>$t-$dx/100-1E-10]);
					} else {
						$y = $evalfunc(['x'=>$x-1E-10]);
						$tempy = $evalfunc(['x'=>$x-$dx/100-1E-10]);
					}
					if ($tempy>$y) { //seems to be coming down
						$iy = $yymax;
						if ($py<$yymin) { $py = $yymax+.5*($ymax-$ymin);}
					} else { //coming from bottom
						$iy = $yymin;
						if ($py>$yymax) { $py = $yymin-.5*($ymax-$ymin);}
					}
					$ix = round(($x-$px)*($iy - $py)/($y-$py) + $px,$xrnd);
					if ($lastl == 0) {$pathstr .= "path([";} else { $pathstr .= ",";}
					$pathstr .= "[$ix,$iy]";
                    $lastl++;
				} else { //still out
                    
				}
			} else {//all in
				if ($lastl == 0) {$pathstr .= "path([";} else { $pathstr .= ",";}
				$pathstr .= "[$px,$py]";
				$lastl++;
				if ($nextavoid !== null && $x > $nextavoid &&
					abs($y-$py) > .4*($yymax-$yymin)
				) {
					// graph jumps over domain gap; break it
					$ix = ($px+$nextavoid)/2;
					$iy = ($py < .5*($yymax+$yymin)) ? $yymin : $yymax;
					$pathstr .= ",[$ix,$iy]]);";
					$ix = ($x+$nextavoid)/2;
					$iy = ($y < .5*($yymax+$yymin)) ? $yymin : $yymax;
					$pathstr .= "path([[$ix,$iy]";
					$lastl = 1;
				}
				if ($i==$stopat-1 && $lastl > 0) {
					$pathstr .= ",[$x,$y]";
				}
				if ($py<$absymin) {
					$absymin = $py;
				}
				if ($py>$absymax) {
					$absymax = $py;
				}
			}
			$px = $x;
			$py = $y;
			$pyorig = $yorig;

			if ($nextavoid !== null && $x > $nextavoid) {
				// grab next avoid point
				if (count($avoid) > 0) {
					$nextavoid = array_shift($avoid);
				} else {
					$nextavoid = null;
				}
			}
			/*if (abs($y-$lasty) > ($ymax-$ymin)) {
				if ($lastl > 1) { $pathstr .= ']);'; $lastl = 0;}
				$lasty = $y;
			} else {
				if ($lastl == 0) {$pathstr .= "path([";} else { $pathstr .= ",";}
				$pathstr .= "[$x,$y]";
				$lasty = $y;
				$lastl++;
				if ($y<$absymin) {
					$absymin = $y;
				}
				if ($y>$absymax) {
					$absymax = $y;
				}
			}
			*/
		}

		if ($lastl > 0) {$pathstr .= "]);";}
		$path .= $pathstr;
		if ($xmax != $xmin) {
			$alt .= "</tbody></table>\n";
		}

		if ($isineq) {
            // combine multiple paths together
            $pathstr = str_replace(']);path([',',', $pathstr);
            $pathstr = substr($pathstr, 0, -3);
			preg_match('/^path\(\[\[(-?[\d\.]+),(-?[\d\.]+).*(-?[\d\.]+),(-?[\d\.]+)\]$/',$pathstr,$matches);
			$sig = ($xxmax-$xxmin)/100;
			$ymid = ($yymax + $yymin)/2;
			if ($ineqtype[0]=='<') {
				if (abs($matches[3] - $xxmax)>$sig && $matches[4]>$ymid) {
					$pathstr .= ",[$xxmax,$yymax]"; //need to add upper right corner
				}
				$pathstr .= ",[$xxmax,$yymin],[$xxmin,$yymin]";
				if (abs($matches[1] - $xxmin)>$sig  && $matches[2]>$ymid) {
					$pathstr .= ",[$xxmin,$yymax]"; //need to add upper left corner
				}
				$pathstr .= ']);';
			} else {
				if (abs($matches[3] - $xxmax)>$sig && $matches[4]<$ymid) {
					$pathstr .= ",[$xxmax,$yymin]"; //need to add lower right corner
				}
				$pathstr .= ",[$xxmax,$yymax],[$xxmin,$yymax]";
				if (abs($matches[1] - $xxmin)>$sig  && $matches[2]<$ymid) {
					$pathstr .= ",[$xxmin,$yymin]"; //need to add lower left corner
				}
                $pathstr .= ']);';
			}
			if (isset($function[1])) {
				$path .= "fill=\"trans{$function[1]}\";";
			} else {
				$path .= "fill=\"transblue\";";
			}
			$path .= "stroke=\"none\";strokedasharray=\"none\";$pathstr";
		}
		if (isset($function[5]) && $function[5]=='open') {
			$path .= "dot([$x,$y],\"open\");";
			$alt .= "Open dot at ($x,$y). ";
		} else if (isset($function[5]) && $function[5]=='closed') {
			$path .= "dot([$x,$y],\"closed\");";
			$alt .= "Closed dot at ($x,$y).). ";
		} else if (isset($function[5]) && $function[5]=='arrow' && isset($fx[$stopat-2])) {
			$path .= "arrowhead([{$fx[$stopat-2]},{$fy[$stopat-2]}],[$x,$y]);";
			$alt .= "Arrow at ($x,$y). ";
		}
		if (isset($function[4]) && $function[4]=='open' && isset($fx[0])) {
			$path .= "dot([{$fx[0]},{$fy[0]}],\"open\");";
			$alt .= "Open dot at ({$fx[0]},{$fy[0]}). ";
		} else if (isset($function[4]) && $function[4]=='closed' && isset($fx[0])) {
			$path .= "dot([{$fx[0]},{$fy[0]}],\"closed\");";
			$alt .= "Closed dot at ({$fx[0]},{$fy[0]}). ";
		} else if (isset($function[4]) && $function[4]=='arrow' && isset($fx[1])) {
			$path .= "arrowhead([{$fx[1]},{$fy[1]}],[{$fx[0]},{$fy[0]}]);";
			$alt .= "Arrow at ({$fx[0]},{$fy[0]}). ";
		}

		$commands .= $path;
	}
	if ($yminauto) {
		$ymin = max($absymin,$ymin);
	}
	if ($ymaxauto) {
		$ymax = min($absymax,$ymax);
	}
	$commands = "setBorder(5); initPicture({$winxmin},{$winxmax},{$ymin},{$ymax});".$commands;
    if ($hasaxislabels) {
	    $alt = "Graphing window shows horizontal axis" .
            ($xvar == '' ? '' : " labeled $xvar") . ": {$winxmin} to {$winxmax}, vertical axis" .
            ($yvar == '' ? '' : " labeled $yvar") . ": {$ymin} to {$ymax}. ".$alt;
    } else {
	    $alt = "Graphing window shows horizontal axis: {$winxmin} to {$winxmax}, vertical axis: {$ymin} to {$ymax}. ".$alt;
    }

	if ($_SESSION['graphdisp']==0) {
		return ($globalalt == '') ? $alt : $globalalt;
	} else {
		return "<embed type='image/svg+xml' align='middle' width='$plotwidth' height='$plotheight' script='$commands' />\n";
	}
}

function addplotborder($plot,$left,$bottom=5,$right=5,$top=5) {
	return preg_replace("/setBorder\(.*?\);/","setBorder($left,$bottom,$right,$top);",$plot);
	//return str_replace("setBorder(5)","setBorder($left,$bottom,$right,$top)",$plot);

}

function replacealttext($plot, $alttext) {
	if ($_SESSION['graphdisp']==0) {
		return $alttext;
	} else {
		if (strpos($plot, 'alt="')!==false) { //replace
			$plot = preg_replace('/alt="[^"]*"/', 'alt="'.Sanitize::encodeStringForDisplay($alttext).'"', $plot);
		} else { //add
			$plot = preg_replace('/(\/?>)/', ' alt="'.Sanitize::encodeStringForDisplay($alttext).'" $1', $plot);
		}
		return $plot;
	}
}

function addlabel($plot,$x,$y,$lbl) {
	if (func_num_args()>4) {
		$color = func_get_arg(4);
	} else {
		$color = "black";
	}
	if ($_SESSION['graphdisp']==0) {
		return $plot .= "Label &quot;$lbl&quot; at ($x,$y). ";
    }
    $lbl = str_replace("'",'&apos;',$lbl);
    $lbl = str_replace('"','\\"',$lbl);

	if (func_num_args()>6) {
		$loc = func_get_arg(5);
		$angle = func_get_arg(6);
		$plot = str_replace("' />","fontfill=\"$color\";text([$x,$y],\"$lbl\",\"$loc\",\"$angle\");' />",$plot);
	} elseif (func_num_args()>5) {
		$loc = func_get_arg(5);
		$plot = str_replace("' />","fontfill=\"$color\";text([$x,$y],\"$lbl\",\"$loc\");' />",$plot);
	} else {
		$plot = str_replace("' />","fontfill=\"$color\";text([$x,$y],\"$lbl\");' />",$plot);
	}
	return $plot;
}
function addlabelabs($plot,$x,$y,$lbl) {
	if (func_num_args()>4) {
		$color = func_get_arg(4);
	} else {
		$color = "black";
	}
	if ($_SESSION['graphdisp']==0) {
		return $plot .= "Label &quot;$lbl&quot; at pixel coordinates ($x,$y).";
    }
    $lbl = str_replace("'",'&apos;',$lbl);
    $lbl = str_replace('"','\\"',$lbl);
	if (func_num_args()>6) {
		$loc = func_get_arg(5);
		$angle = func_get_arg(6);
		$plot = str_replace("' />","fontfill=\"$color\";textabs([$x,$y],\"$lbl\",\"$loc\",\"$angle\");' />",$plot);
	} elseif (func_num_args()>5) {
		$loc = func_get_arg(5);
		$plot = str_replace("' />","fontfill=\"$color\";textabs([$x,$y],\"$lbl\",\"$loc\");' />",$plot);
	} else {
		$plot = str_replace("' />","fontfill=\"$color\";textabs([$x,$y],\"$lbl\");' />",$plot);
	}
	return $plot;
}

function adddrawcommand($plot,$cmd) {
	$cmd = str_replace("'",'"',$cmd);
	return str_replace("' />",$cmd."' />",$plot);
}

function mergeplots($plota) {
	$n = func_num_args();
	if ($n==1) {
		return $plota;
    }
    $plota = preg_replace('/<span.*?<\/span>/','', $plota);
	for ($i=1;$i<$n;$i++) {
        $plotb = func_get_arg($i);
		if ($_SESSION['graphdisp']==0) {
			$newtext = preg_replace('/^Graphs.*?y:.*?to.*?\.\s/', '', $plotb);
			$plota .= $newtext;
		} else {
            $plotb = preg_replace('/<span.*?<\/span>/','', $plotb);
            $newcmds = preg_replace('/^.*?initPicture\(.*?\);\s*(axes\(.*?\);)?(.*?)\'\s*\/>.*$/', '$2', $plotb);
			$plota = str_replace("' />", trim($newcmds)."' />", $plota);
		}
	}
	return $plota;
}

function addfractionaxislabels($plot,$step) {
	if ($_SESSION['graphdisp']==0) {
		return $plot .= "Horizontal axis labels in steps of $step.";
	}
	if (strpos($step,'/')===false) {
		$num = $step; $den = 1;
	} else {
		list($num,$den) = explode('/',$step);
	}
	$ispi = false;
	if (strpos($num,'pi')!==false) {
		$ispi = true;
		$num = str_replace(['*pi','pi'],'',$num);
		if ($num=='') { $num = 1;}
	}
    if (!is_numeric($num) || !is_numeric($den)) {
        echo 'invalid step in addfractionaxislabels';
        return $plot;
    }
	preg_match('/initPicture\(([\-\d\.]+),([\-\d\.]+),([\-\d\.]+),([\-\d\.]+)\)/',$plot,$matches);
    if (!isset($matches[4])) {
        echo "addfractionaxislabels: input must be a plot";
        return $plot;
    }
	$xmin = $matches[1];
	$xmax = $matches[2];
	$yrange = $matches[4] - $matches[3];
	$stepn = $num/$den;
	if ($ispi) { $stepn *= M_PI;}
	if ($stepn==0) {echo "error: bad step size on pilabels"; return;}
	$step = ceil($xmin/$stepn);
	$totstep = ceil(($xmax-$xmin)/$stepn);
	$tm = -.02*$yrange;
	$tx = .02*$yrange;
	$outst = 'fontfill="black";strokewidth=0.5;stroke="black";';
	for ($i=0; $i<$totstep; $i++) {
		$x = $step*$stepn;
		if (abs($x)<.01) {$step++; continue;}
		$g = gcd($step*$num,$den);
		$n = ($step*$num)/$g;  $d = $den/$g;
		if ($ispi) {
			if ($n==1) {
				$xd = '&pi;';
			} else if ($n==-1) {
				$xd = '-&pi;';
			} else {
				$xd = "$n&pi;";
			}
		} else {
			$xd = $n;
		}
		if ($d!=1) {$xd .= "/$d";}
		$outst .= "line([$x,$tm],[$x,$tx]); text([$x,$tm],\"$xd\",\"below\");";
		$step++;
	}
	return str_replace("' />","$outst' />",$plot);

}

function connectthedots($xarray,$yarray,$color='black',$thick=1,$startdot='',$enddot='') {
    if (!is_array($xarray) || !is_array($yarray)) {
        echo "Error: x array and y array need to be arrays";
        return [];
    }
	if (count($xarray)!=count($yarray)) {
		echo "Error: x array and y array need to have the same number of elements";
        return [];
	}
	$outarr = array();
	for ($i=1; $i<count($xarray); $i++) {
		if ($i==1) {$ed = $startdot;} else {$ed = '';}
		if ($i==count($xarray)-1) {$sd = $enddot;} else {$sd = '';}
        if (!isset($xarray[$i]) || !isset($xarray[$i-1]) || !isset($yarray[$i]) || !isset($yarray[$i-1])) {
            echo "error: connectthedots needs arrays without missing elements";
            return [];
        }
		if ($xarray[$i-1]==$xarray[$i]) {
			//vertical line
			$outarr[] = "[{$xarray[$i]},t],$color,{$yarray[$i]},{$yarray[$i-1]},$sd,$ed,$thick";
		} else {
			$xd = $xarray[$i-1] - $xarray[$i];
			$yd = $yarray[$i-1] - $yarray[$i];
			$outarr[] = "[{$xarray[$i]}+t*($xd),{$yarray[$i]}+t*($yd)],$color,0,1,$sd,$ed,$thick";
		}
	}
	return $outarr;
}

function showasciisvg($script, $width=200, $height=200, $alt=null) {
    if (is_array($width)) {
        echo "second argument to showasciisvg should be an integer, not an array";
        $width = 200; $height = 200; $alt = '';
    }
    $script = preg_replace('~//.*?$~m','', $script); //remove comments
    $script = preg_replace('~/\*.*?\*/~s', '', $script);
    $script = str_replace(["\r\n","\r"], "\n", $script); // normalize line endings
    $instr = false; $brackdepth = 0; $parendepth = 0;
    for ($i = 0; $i < strlen($script); $i++) {
        $c = $script[$i];
        if ($c == '"' || $c == "'") {
            if ($instr && $c == $strchar) {
                $instr = false;
            } else if (!$instr) {
                $instr = true;
                $strchar = $c;
            }
        } else if ($c == '[' && !$instr) {
            $brackdepth++;
        } else if ($c == '(' && !$instr) {
            $parendepth++;
        } else if ($c == ']' && !$instr) {
            $brackdepth--;
        } else if ($c == ')' && !$instr) {
            $parendepth--;
        } else if ($c == "\n" && !$instr && $brackdepth==0 && $parendepth==0) {
            $script[$i] = ';';
        } else if ($c == "\n") {
            $script[$i] = ' ';
        }
    }
    if ($alt === null) {
        $alt = "[Graphs generated by this script: $script]";
    }
    if ($_SESSION['graphdisp']==0) {
        return $alt;
    }
    $script = str_replace("'",'"',$script);
    $out = "<embed type='image/svg+xml' align='middle' width='$width' height='$height' script='$script' />";
    if (empty($GLOBALS['hide-sronly'])) {
        $out .= '<span class="sr-only">'.$alt.'</span>';
    }
    return $out;
}


function showarrays() {
	$alist = func_get_args();
	$format = "default";
	$caption = "";
    $tablealign = '';
	if (count($alist)<2) {return false;}
	if (count($alist)%2==1) {
		if (is_array($alist[count($alist)-1])) {
		 	$opts = $alist[count($alist)-1];
			if (isset($opts['align'])) {
				$format = $opts['align'];
			}
			if (isset($opts['caption'])) {
				$caption = $opts['caption'];
			}
            if (isset($opts['tablealign'])) {
				$tablealign = $opts['tablealign'];
			}
		} else if (is_string($alist[count($alist)-1])) {
			$format = $alist[count($alist)-1];
		}
	}
	$ncol = floor(count($alist)/2);
	if ($format !== 'default' && strlen($format) < $ncol) {
		$format = str_repeat($format[0], $ncol);
	}
	if (count($alist)<4 && is_array($alist[0]) && is_array($alist[1]) && is_array($alist[1][0])) {
        // alt input syntax of showarrays(array of headers, array of data arrays)
		for ($i=0;$i<count($alist[0]);$i++) {
			$newalist[] = $alist[0][$i];
			$newalist[] = $alist[1][$i];
		}
		$alist = $newalist;
	}
	$out = '<table class=stats';
    if ($tablealign == 'center') {
        $out .= ' style="margin:0 auto;"';
    }
    $out .= '>';
	if ($caption != '') {
		$out .= '<caption>'.Sanitize::encodeStringForDisplay($caption).'</caption>';
	}
	$hashdr = false;
	$maxlength = 0;
	for ($i = 0; $i<$ncol; $i++) {
        if (!is_scalar($alist[2*$i])) {
            echo 'showarrays: column headers should be strings';
            $alist[2*$i] = '';
        }
		if ($alist[2*$i]!='') {
			$hashdr = true;
		}
		if (!is_array($alist[2*$i+1])) {
			$alist[2*$i+1] = listtoarray($alist[2*$i+1]);
		}
		if (count($alist[2*$i+1])>$maxlength) {
			$maxlength = count($alist[2*$i+1]);
		}
	}
	if ($hashdr) {
		$out .= '<thead><tr>';
		for ($i = 0; $i<floor(count($alist)/2); $i++) {
			$out .= "<th scope=\"col\">{$alist[2*$i]}</th>";
		}
		$out .= "</tr></thead>";
	}
	$out .= "<tbody>";
	for ($j = 0; $j<$maxlength; $j++) {
		$out .="<tr>";
		for ($i = 0; $i<floor(count($alist)/2); $i++) {
			if ($format == 'default' || !isset($format[$i])) {
				$out .= '<td>';
			} else if ($format[$i]=='c' || $format[$i]=='C') {
				$out .= '<td class="c">';
			} else if ($format[$i]=='r' || $format[$i]=='R') {
				$out .= '<td class="r">';
			} else if ($format[$i]=='l' || $format[$i]=='L') {
				$out .= '<td class="l">';
			} else {
				$out .= '<td>';
			}
			if (isset($alist[2*$i+1][$j])) {
				$out .= $alist[2*$i+1][$j];
			}

			$out .= "</td>";
		}
		$out .="</tr>";
	}
	$out .= "</tbody></table>\n";
	return $out;
}

function showrecttable($m,$clabel,$rlabel,$format='') {
	if (count($m)!=count($rlabel) || count($m[0])!=count($clabel)) {
		return 'Error - label counts don\'t match dimensions of the data';
	}
	$out = '<table class=stats><thead><tr><th></th>';
	for ($i = 0; $i<count($clabel); $i++) {
		$out .= "<th scope=\"col\">{$clabel[$i]}</th>";
	}
	$out .= "</tr></thead><tbody>";
	for ($j = 0; $j<count($m); $j++) {
		$out .= "<tr><th scope=\"row\"><b>{$rlabel[$j]}</b></th>";
		for ($i = 0; $i<count($m[$j]); $i++) {
			if ($format=='c' || $format=='C') {
				$out .= '<td class="c">';
			} else if ($format=='r' || $format=='R') {
				$out .= '<td class="r">';
			} else if ($format=='l' || $format=='L') {
				$out .= '<td class="l">';
			} else {
				$out .= '<td>';
			}
			$out .= $m[$j][$i].'</td>';
		}
		$out .="</tr>";
	}
	$out .= "</tbody></table>\n";
	return $out;
}

function horizshowarrays() {
	$alist = func_get_args();
	if (count($alist)<2) {return false;}

	$maxlength = 0;
	for ($i=0; $i<count($alist)/2; $i++) {
		if (!isset($alist[2*$i+1])) {
            $alist[2*$i+1] = [];
        } else if (!is_array($alist[2*$i+1])) {
			$alist[2*$i+1] = listtoarray($alist[2*$i+1]);
		}
		if (count($alist[2*$i+1])>$maxlength) {
			$maxlength = count($alist[2*$i+1]);
		}
	}
	$out = '<table class=stats>';
	for ($i=0; $i<count($alist)/2; $i++) {
		$out .= "<tr><th scope=\"row\"><b>{$alist[2*$i]}</b></th>";
        if (count($alist[2*$i+1]) > 0) {
		    $out .= "<td>" . implode("</td><td>",$alist[2*$i+1]) . "</td>";
        }
		if (count($alist[2*$i+1])<$maxlength) {
			$out .= str_repeat('<td></td>', $maxlength - count($alist[2*$i+1]));
		}
		$out .= "</tr>\n";
	}
	$out .= "</tbody></table>\n";
	return $out;
}


function clean($exp) {
	$exp = preg_replace('/(\+|\-)\s+(\+|\-)/',"$1$2",$exp);
	//$exp = str_replace(" ", "", $exp);  //caused problems with "x > -3"
	$exp = str_replace("+-","-",$exp);
	$exp = str_replace("-+","-",$exp);
	$exp = str_replace("--","+",$exp);
	//$exp = preg_replace('/^1\*?([a-zA-Z\(])/',"$1",$exp);
	//$exp = preg_replace('/([^\d\^\.])1\*?([a-zA-Z\(])/',"$1$2",$exp);
	return $exp;
}

function xclean($exp) {
	//goals are to cleam up  1*, 0*  0+  0-  a^0  a^1
	$exp = clean($exp);
	$exp = preg_replace('/^([a-zA-Z])\^0/','1',$exp); //x^0 -> 1
	$exp = preg_replace('/(\d)\*?([a-zA-Z])\^0$/',"$1",$exp);   //3x^0  -> 3
	$exp = preg_replace('/(\d)\*?([a-zA-Z])\^0([^\d\.])/',"$1$3",$exp); //3x^0+4 -> 3+4
	$exp = preg_replace('/([^\d])\*?([a-zA-Z])\^0$/',"$1 1",$exp); //y*x^0 -> y*1,  y+x^0 -> y+1
	$exp = preg_replace('/([^\d])\*?([a-zA-Z])\^0([^\d\.])/',"$1 1 $3",$exp);  //y*x^0+3 -> y*1+3,  y+x^0+z -> y+1+z
	$exp = preg_replace('/^0\s*\*?[^\+\-\.]+\+?/','',$exp); // 0*23+x
	$exp = preg_replace('/^0\s*([\+\-])/',"$1",$exp);  //0+x, 0-x
	$exp = preg_replace('/[\+\-]\s*0\s*\*?[^\.\+\-]+/','',$exp); //3+0x-4 -> 3-4
	$exp = preg_replace('/[\+\-]\s*0\s*([\+\-])/',"$1",$exp);  //3+0+4 -> 3+4
	$exp = preg_replace('/^1\s*\*?([a-zA-Z])/',"$1",$exp);  //1x -> x
	$exp = preg_replace('/([^\d\^\.])1\s*\*?([a-zA-Z\(])/',"$1$2",$exp);  //3+1x -> 3+x
	$exp = preg_replace('/\^1([^\d])/',"$1",$exp); //3x^1+4 =>3x+4
	$exp = preg_replace('/\^1$/','',$exp);  //4x^1 -> 4x
	$exp = clean($exp);
	if (isset($exp[0]) && $exp[0]=='+') {
		$exp = substr($exp,1);
	}
	return $exp;
}

function polyclean($exp) {
	$exp = clean($exp);

	$i = 0;
	$outstr = '';
	$p = 0;
	$parr = array('','','');
	$onpow = false;
	$lastsign = '+';
	$exp .= '+';
	while ($i<strlen($exp)) {
		$c = $exp[$i];
		if (($c >='0' && $c<='9') || $c=='.' || $c=='/' || $c=='(' || $c==')') {
			if ($onpow) {
				$parr[2] .= $c;
			} else {
				$parr[0] .= $c;
			}
		} else if (($c<='z' && $c>='a') || ($c<='Z' && $c>='A')) {
			$parr[1] .= $c;
		} else if ($c=='^') {
			$onpow = true;
		} else if ($c == '+' || $c == '-') {
			if ($i+1<strlen($exp) && $parr[2]=='' && $onpow) {
				$n = $exp[$i+1];
				if ($c=='-' && (($n>= '0' && $n<='9') || $n=='.')) {
					$parr[2] .= '-';
					$i++;
					continue;
				}
			}
			if ($parr[0]=='0') {
				$parr = array('','','');
				$onpow = false;
				$i++;
				$lastsign = $c;
				continue;
			} else {
				if ($outstr!='' || $lastsign=='-') {
					$outstr .= $lastsign;
				}

			}
			if ($parr[2]=='0' || ($parr[2]=='' && $parr[1]=='')) {
				if ($parr[1]=='') {
					$outstr .= $parr[0]; // n
				} else {
					if ($parr[0] == '') {
						$outstr .= 1; // x^0
					} else {
						$outstr .= $parr[0]; //n x^0
					}
				}
			} else if ($parr[2]=='') {
				if ($parr[0]=='1') {
					$outstr .= $parr[1];
				} else {
					$outstr .= $parr[0].' '.$parr[1];
				}
			} else if ($parr[2]=='1') {
				if ($parr[0]=='1') {
					$outstr .= $parr[1];
				} else {
					$outstr .= $parr[0] . ' ' . $parr[1]; //n x^1
				}
			} else {
				if ($parr[0]=='1') {
					$outstr .= $parr[1] . '^' . $parr[2]; // 1 x^m
				} else {
					$outstr .= $parr[0] . ' ' . $parr[1] . '^' . $parr[2]; // n x^m
				}
			}
			$lastsign = $c;
			$parr = array('','','');
			$onpow = false;
		}
		$i++;
	}
	return $outstr;
	/*
	$exp = clean($exp);
	if ($exp[0]=='+') {
		$exp = substr($exp,1);
	}
	return $exp;
	*/
}

function makepretty($exp) {
	if (is_array($exp)) {
		for ($i=0;$i<count($exp);$i++) {
			$exp[$i]=clean($exp[$i]);
		}
	} else {
		$exp = clean($exp);
	}
	return $exp;
}

function makexpretty($exp) {
	if (is_array($exp)) {
		for ($i=0;$i<count($exp);$i++) {
			$exp[$i]=xclean($exp[$i]);
		}
	} else {
		$exp = xclean($exp);
	}
    return $exp;
    
    //return makexxpretty($exp);
}

function makexxpretty($exp,$funcs=array()) {
	if (is_array($exp)) {
		for ($i=0;$i<count($exp);$i++) {
			$exp[$i] = clean($exp[$i]);
			$exp[$i]=cleanbytoken($exp[$i],$funcs);
		}
	} else {
		$exp = clean($exp);
		$exp = cleanbytoken($exp,$funcs);
	}
	return $exp;
}

function polymakepretty($exp) {
	if (is_array($exp)) {
		for ($i=0;$i<count($exp);$i++) {
			$exp[$i]=polyclean($exp[$i]);
		}
	} else {
		$exp = polyclean($exp);
	}
	return $exp;
}

function makeprettynegative($exp) {
	//3--4 to 3-(-4).  3+-4 to 3+(-4).  3-+4 to 3-4
	$exp = preg_replace('/(\+|\-)\s+(\+|\-)/',"$1$2",$exp);
	$exp = str_replace("-+","-",$exp);
	$exp = preg_replace('/--([\d\.]+)/', '-(-$1)', $exp);
	$exp = preg_replace('/\+-([\d\.]+)/', '+(-$1)', $exp);
	return $exp;
}

function randpythag($min=1,$max=100) {
	list($min,$max) = checkMinMax($min, $max, true, 'randpythag');
	$m = $GLOBALS['RND']->rand(ceil(sqrt($min+1)), floor(sqrt($max-1)));
	$n = $GLOBALS['RND']->rand(1, floor(min($m-1, sqrt($m*$m-$min), sqrt($max-$m*$m))));
	$v = array($m*$m-$n*$n, 2*$m*$n, $m*$m+$n*$n);
	sort($v);
	return $v;
}


function makeprettydisp($exp) {
	if (is_array($exp)) {
		for ($i=0;$i<count($exp);$i++) {
			$exp[$i]="`".clean($exp[$i])."`";
		}
	} else {
		$exp = "`".clean($exp)."`";
	}
	return $exp;
}

function makexprettydisp($exp) {
	if (is_array($exp)) {
		for ($i=0;$i<count($exp);$i++) {
			$exp[$i]="`".xclean($exp[$i])."`";
		}
	} else {
		$exp = "`".xclean($exp)."`";
	}
    return $exp;
    //return makexxprettydisp($exp);
}

function makexxprettydisp($exp,$funcs=array()) {
	if (is_array($exp)) {
		for ($i=0;$i<count($exp);$i++) {
			$exp[$i] = clean($exp[$i]);
			$exp[$i]="`".cleanbytoken($exp[$i],$funcs)."`";
		}
	} else {
		$exp = clean($exp);
		$exp = "`".cleanbytoken($exp,$funcs)."`";
	}
	return $exp;
}

function polymakeprettydisp($exp) {
	if (is_array($exp)) {
		for ($i=0;$i<count($exp);$i++) {
			$exp[$i]="`".polyclean($exp[$i])."`";
		}
	} else {
		$exp = "`".polyclean($exp)."`";
	}
	return $exp;
}


function makeprettyarray($a) {
	for ($i=0;$i<count($a);$i++) {
		$a = makepretty($a);
	}
}


function makeprettydisparray($a) {
	for ($i=0;$i<count($a);$i++) {
		$a = "`".makepretty($a)."`";
	}
}

function prettynegs($a) {
	return str_replace('-','&#x2212;',$a);
}

function rrand($min,$max,$p=0) {
	if (func_num_args()!=3) { echo "Error: rrand expects 3 arguments"; return $min;}
	if ($p<=0) {echo "Error with rrand: need to set positive step size"; return false;}
	list($min,$max) = checkMinMax($min, $max, false, 'rrand');

	$rn = max(0, getRoundNumber($p), getRoundNumber($min));
	$out = round($min + $p*$GLOBALS['RND']->rand(0,floor(($max-$min)/$p + 1e-12)), $rn);
	if ($rn==0) { $out = (int) $out;}
	return( $out );
}


function rands($min,$max,$n=0,$ord='def') {
	if (func_num_args()<3) { echo "rands expects 3 arguments"; return $min;}
	list($min,$max) = checkMinMax($min, $max, true, 'rands');
	$n = floor($n);
	if ($n<=0) { echo "rands: need n &gt; 0";}
    $r = [];
	for ($i = 0; $i < $n; $i++) {
		$r[$i] = $GLOBALS['RND']->rand($min,$max);
	}
  if ($ord == 'inc') {
    sort($r);
  } else if ($ord == 'dec') {
    rsort($r);
  }
	return $r;
}


function rrands($min,$max,$p=0,$n=0,$ord='def') {
	if (func_num_args()<4) { echo "rrands expects 4 arguments"; return $min;}
	if ($p<=0) {echo "Error with rrands: need to set positive step size"; return false;}
	list($min,$max) = checkMinMax($min, $max, false, 'rrands');

	$rn = max(0, getRoundNumber($p), getRoundNumber($min));
    $n = floor($n);
	if ($n<=0) { echo "rrands: need n &gt; 0";}
    $r = [];
	$maxi = floor(($max-$min)/$p + 1e-12);
	for ($i = 0; $i < $n; $i++) {
		$r[$i] = round($min + $p*$GLOBALS['RND']->rand(0,$maxi), $rn);
		if ($rn==0) { $r[$i] = (int) $r[$i];}
	}
  if ($ord == 'inc') {
    sort($r);
  } else if ($ord == 'dec') {
    rsort($r);
  }
	return $r;
}


function randfrom($lst) {
	if (func_num_args()!=1) { echo "randfrom expects 1 argument"; return 1;}
	if (!is_array($lst)) {
		$lst = listtoarray($lst);
	}
    if (count($lst) == 0) {
        echo 'cannot pick randfrom empty list';
        return '';
    }
	return $lst[$GLOBALS['RND']->rand(0,count($lst)-1)];
}


function randsfrom($lst,$n,$ord='def') {
	if (func_num_args()<2) { echo "randsfrom expects 2 arguments"; return 1;}
	if (!is_array($lst)) {
		$lst = listtoarray($lst);
	}
    $n = floor($n);
	if ($n<=0) { echo "randsfrom: need n &gt; 0";}
    $r = [];
	for ($i=0; $i<$n;$i++) {
		$r[$i] = $lst[$GLOBALS['RND']->rand(0,count($lst)-1)];
	}
  if ($ord == 'inc') {
    sort($r);
  } else if ($ord == 'dec') {
    rsort($r);
  }
	return $r;
}


function jointrandfrom() {
    $args = func_get_args();
	if (count($args)<2) { echo "jointrandfrom expects at least 2 arguments"; return array(1,1);}
    $min = 1e12;
    foreach ($args as $k=>$arg) {
        if (!is_array($arg)) {
            $args[$k] = listtoarray($arg);
        }
        $min = min($min, count($args[$k])-1);
    }
    $l = $GLOBALS['RND']->rand(0,$min);
    $out = array();
    foreach ($args as $k=>$arg) {
        $out[] = $arg[$l];
    }
	return $out;
}


function diffrandsfrom($lst,$n,$ord='def') {
	if (func_num_args()<2) { echo "diffrandsfrom expects 2 arguments"; return array();}
	if (!is_array($lst)) {
		$lst = listtoarray($lst);
	}
    $n = floor($n);
	if ($n<=0) { echo "diffrandsfrom: need n &gt; 0";}
	$GLOBALS['RND']->shuffle($lst);
	$r = array_slice($lst,0,$n);
  if ($ord == 'inc') {
    sort($r);
  } else if ($ord == 'dec') {
    rsort($r);
  }
  return $r;
}


function nonzerorand($min,$max) {
	if (func_num_args()!=2) { echo "nonzerorand expects 2 arguments"; return $min;}
	list($min,$max) = checkMinMax($min, $max, true, 'nonzerorand');
	if ($min == 0 && $max == 0) { return 0; }
	do {
		$ret = $GLOBALS['RND']->rand($min,$max);
	} while ($ret == 0);
	return $ret;
}


function nonzerorrand($min,$max,$p=0) {
	if (func_num_args()!=3) { echo "nonzerorrand expects 3 arguments"; return $min;}
	list($min,$max) = checkMinMax($min, $max, false, 'nonzerorrand');
	if ($min == 0 && $max == 0) { return 0; }
	if ($p<=0) {echo "Error with nonzerorrand: need to set positive step size"; return $min;}
	$maxi = floor(($max-$min)/$p + 1e-12);
	if ($maxi==0) {
		return $min;
	}

	$rn = max(0, getRoundNumber($p), getRoundNumber($min));
  $cnt = 0;
	do {
		$ret = round($min + $p*$GLOBALS['RND']->rand(0,$maxi), $rn);
    $cnt++;
    if ($cnt > 1000) {
      echo "Error in nonzerorrand - not able to find valid value";
      break;
    }
	} while (abs($ret)< 1e-14);
	if ($rn==0) { $ret = (int) $ret;}
	return $ret;
}


function nonzerorands($min,$max,$n=0,$ord='def') {
	if (func_num_args()<3) { echo "nonzerorands expects 3 arguments"; return $min;}
	list($min,$max) = checkMinMax($min, $max, true, 'nonzerorands');
	if ($min == 0 && $max == 0) { return 0; }
    $n = floor($n);
	if ($n<=0) { echo "nonzerorands: need n &gt; 0";}
    $r = [];
	for ($i = 0; $i < $n; $i++) {
		do {
			$r[$i] = $GLOBALS['RND']->rand($min,$max);
		} while ($r[$i] == 0);
	}
  if ($ord == 'inc') {
    sort($r);
  } else if ($ord == 'dec') {
    rsort($r);
  }
	return $r;
}


function nonzerorrands($min,$max,$p=0,$n=0,$ord='def') {
	if (func_num_args()<4) { echo "nonzerorrands expects 4 arguments"; return $min;}
	$n = floor($n);
	list($min,$max) = checkMinMax($min, $max, false, 'nonzerorrands');

	if ($p<=0) {echo "Error with nonzerorrands: need to set positive step size"; return array_fill(0,$n,$min);}
	$maxi = floor(($max-$min)/$p + 1e-12);
	if ($maxi==0) {
		return array_fill(0, $n, $min);
	}

	$rn = max(0, getRoundNumber($p), getRoundNumber($min));
    $n = floor($n);
    $r = [];
	if ($n<=0) { echo "nonzerorrands: need n &gt; 0";}
	for ($i = 0; $i < $n; $i++) {
    $cnt = 0;
		do {
			$r[$i] = round($min + $p*$GLOBALS['RND']->rand(0,$maxi), $rn);
			if ($rn==0) { $r[$i] = (int) $r[$i];}
      $cnt++;
      if ($cnt > 1000) {
        echo "Error in nonzerorrands - not able to find valid value";
        break;
      }
		} while (abs($r[$i]) <1e-14);
	}
  if ($ord == 'inc') {
    sort($r);
  } else if ($ord == 'dec') {
    rsort($r);
  }
	return $r;
}


function diffrands($min,$max,$n=0,$ord='def') {
	if (func_num_args()<3) { echo "diffrands expects 3 arguments"; return $min;}
	list($min,$max) = checkMinMax($min, $max, true, 'diffrands');
	if ($max == $min) {echo "diffrands: Need min&lt;max"; return array_fill(0,$n,$min);}
	/*if ($n > $max-$min+1) {
		if ($GLOBALS['myrights']>10) {
			echo "diffrands: min-max not far enough for n requested";
		}
	}*/

	$n = floor($n);
	if ($n<=0) { echo "diffrands: need n &gt; 0";}
	if ($n<.1*($max-$min)) {
		$out = array();
    $cnt = 0;
		while (count($out)<$n) {
			$x = $GLOBALS['RND']->rand($min,$max);
			if (!in_array($x,$out)) {
				$out[] = $x;
			}
      $cnt++;
      if ($cnt > 2000) {
        echo "Error in diffrands - not able to find valid values";
        break;
      }
		}
	} else {
		$r = range($min,$max);
		while ($n>count($r)) {
			$r = array_merge($r,$r);
		}
		$GLOBALS['RND']->shuffle($r);
		$out = array_slice($r,0,$n);
	}
  if ($ord == 'inc') {
    sort($out);
  } else if ($ord == 'dec') {
    rsort($out);
  }
  return $out;
}


function diffrrands($min,$max,$p=0,$n=0,$ord='def',$nonzero=false) {
	if (func_num_args()<4) { echo "diffrrands expects 4 arguments"; return $min;}
	$n = floor($n);
	list($min,$max) = checkMinMax($min, $max, false, 'diffrrands');

	if ($p<=0) {echo "Error with diffrrands: need to set positive step size"; return array_fill(0,$n,$min);}
    $n = floor($n);
	if ($n<=0) { echo "diffrrands: need n &gt; 0";}

	$maxi = floor(($max-$min)/$p + 1e-12);

	if ($maxi==0) {
		echo "Error with diffrrands: step size is greater than max-min"; return array_fill(0,$n,$min);
	}

	$rn = max(0, getRoundNumber($p), getRoundNumber($min));

	if ($n<.1*$maxi) {
		$out = array();
    $cnt = 0;
		while (count($out)<$n) {
			$x = round($min + $p*$GLOBALS['RND']->rand(0,$maxi), $rn);
			if ($rn==0) { $x = (int) $x;}
			if (!in_array($x,$out) && (!$nonzero || abs($x)>1e-14)) {
				$out[] = $x;
			}
      $cnt++;
      if ($cnt > 2000) {
        echo "Error in diffrrands - not able to find valid values";
        break;
      }
		}
    $r = $out;
	} else {
		$r = range(0,$maxi);
        if ($nonzero) {
			if ($min <= 0 && $max >= 0) {
				array_splice($r,-1*$min/$p,1);
			}
		}
		while ($n>count($r)) {
			$r = array_merge($r,$r);
		}
		
		$GLOBALS['RND']->shuffle($r);
		$r = array_slice($r,0,$n);
		for ($i=0;$i<$n;$i++) {
			$r[$i] = round($min+$p*$r[$i], $rn);
			if ($rn==0) { $r[$i] = (int) $r[$i];}
		}
	}
  if ($ord == 'inc') {
    sort($r);
  } else if ($ord == 'dec') {
    rsort($r);
  }
  return $r;
}


function nonzerodiffrands($min,$max,$n=0,$ord='def',$nowarn=false) {
	if (func_num_args()<3) { echo "nonzerodiffrands expects 3 arguments"; return $min;}
	list($min,$max) = checkMinMax($min, $max, true, 'nonzerodiffrands');
	if ($max == $min) {echo "nonzerodiffrands: Need min&lt;max"; return array_fill(0,$n,$min);}
    $n = floor($n);
	if ($n<=0) { echo "nonzerodiffrands: need n &gt; 0";}
	if ($n > $max-$min+1 || ($min*$max<=0 && $n>$max-$min)) {
		if ($GLOBALS['myrights']>10 && !$nowarn) {
			echo "nonzerodiffrands: min-max not far enough for n requested";
		}
	}

	if ($n<.1*($max-$min)) {
		$out = array();
    $cnt = 0;
		while (count($out)<$n) {
			$x = $GLOBALS['RND']->rand($min,$max);
			if ($x!=0 && !in_array($x,$out)) {
				$out[] = $x;
			}
      $cnt++;
      if ($cnt > 2000) {
        echo "Error in nonzerodiffrrands - not able to find valid values";
        break;
      }
		}
	} else {
		$r = range($min,$max);
		if ($min <= 0 && $max >= 0) {
			array_splice($r,-1*$min,1);
		}
		while ($n>count($r)) {
			$r = array_merge($r,$r);
		}
		$GLOBALS['RND']->shuffle($r);
		$out = array_slice($r,0,$n);
	}
  if ($ord == 'inc') {
    sort($out);
  } else if ($ord == 'dec') {
    rsort($out);
  }
  return $out;
}


function nonzerodiffrrands($min,$max,$p=0,$n=0,$ord='def') {
	return diffrrands($min,$max,$p,$n, $ord, true);
}


function singleshuffle($a) {
	if (!is_array($a)) {
		$a = listtoarray($a);
	}
	$GLOBALS['RND']->shuffle($a);
	if (func_num_args()>1) {
		$n = func_get_arg(1);
		return array_slice($a,0,$n);
	} else {
		return $a;
	}
}


function jointshuffle($a1,$a2) {  //optional third & fourth params $n1 and $n2
	if (!is_array($a1)) {
		$a1 = listtoarray($a1);
	}
	if (!is_array($a2)) {
		$a2 = listtoarray($a2);
	}
    if (count($a1) != count($a2)) {
        echo "jointshuffle should be called with two arrays of equal length";
        return [$a1,$a2];
    }
	$r = $GLOBALS['RND']->array_rand($a1,count($a1));
	$GLOBALS['RND']->shuffle($r);
	for ($j=0;$j<count($r);$j++) {
		$ra1[$j] = $a1[$r[$j]];
		$ra2[$j] = $a2[$r[$j]];
	}
	if (func_num_args() > 2) {
		$n=func_get_arg(2);
		if (func_num_args() > 3) {$n2 = func_get_arg(3);} else {$n2 = $n;}
		return array(array_slice($ra1,0,$n),array_slice($ra2,0,$n2));
	} else {

		return array($ra1,$ra2);
	}
}


function listtoarray($l) {
	if (func_num_args()>1) {
		echo "Warning:  listtoarray expects one argument, more than one provided";
	}
    if ($l==='') { return []; }
	if (is_array($l)) {
		return $l;
	}
	return array_map('trim',explode(',',$l));
}


function arraytolist($a, $sp=false) {
	if (!is_array($a)) {
		if ($GLOBALS['myrights']>10) {
			echo "Error: arraytolist expect an array as input";
		}
		return $a;
	}
	if ($sp) {
		return (implode(', ',$a));
	} else {
		return (implode(',',$a));
	}
}

function joinarray($a,$s=',') {
	if (!is_array($a)) {
		if ($GLOBALS['myrights']>10) {
			echo "Error: joinarray expect an array as input";
		}
		return $a;
	}
	return (implode($s,$a));
}

function calclisttoarray($l) {
	$l = listtoarray($l);
	foreach ($l as $k=>$tocalc) {
		$l[$k] = evalMathParser($tocalc,null);
	}
	return $l;
}


function sortarray($a) {
	if (!is_array($a)) {
		$a = listtoarray($a);
	}
	if (func_num_args()>1) {
		$dir = func_get_arg(1);
	}
	if (isset($dir) && $dir=="rev") {
		if (isset($a[0]) && is_numeric($a[0])) {
			rsort($a, SORT_NUMERIC);
		} else {
			rsort($a);
		}
	} else {
		if (isset($a[0]) && is_numeric($a[0])) {
			sort($a, SORT_NUMERIC);
		} else {
			sort($a);
		}
	}
	return $a;
}

function splicearray($a,$offset, $length=null, $replacement=[]) {
	if (!is_array($a)) {
		$a = listtoarray($a);
	}
	array_splice($a, $offset, $length, $replacement);
	return $a;
}


function consecutive($min,$max,$step=1) {
	$a = array();
	if ($min<$max && $step>0) {
		for ($i=$min;$i<$max+$step/100.0;$i+=$step) {
			$a[] = $i;
		}
	} else if ($min > $max && $step < 0) {
		for ($i=$min;$i>$max+$step/100.0;$i+=$step) {
			$a[] = $i;
		}
	} else if (abs($min-$max) < .9*abs($step)) {
		$a[] = $min;
	} else {
		echo "Invalid inputs to consecutive";
	}
	return $a;
}

/*
function gcd($n,$m){ //greatest common divisor
    if (!is_numeric($n) || !is_numeric($m)) {
        echo "gcd requires numeric inputs.";
        return 1;
    }
	$m = (int) round(abs($m));
	$n = (int) round(abs($n));
    if($m==0 && $n==0) {return 1;} // not technically correct, but will avoid divide by 0 issues in the case of bad input
	if($m==0)return$n;
	if($n==0)return$m;
	return $m<$n?gcd($m,$n%$m):gcd($n,$m%$n);
}
*/
function gcd(...$args) {
    if (count($args)==1 && is_array($args[0])) {
        $args = $args[0];
    }
    $g = null;
    foreach ($args as $v) {
        if (!is_numeric($v)) {
            echo "gcd requires numeric inputs.";
            return 1;
        } else if ($g === null) {
            $g = (int) round(abs($v));
        } else {
            $n = (int) round(abs($v));
            if($g==0 && $n==0) {return 1;} // not technically correct, but will avoid divide by 0 issues in the case of bad input
            while ($n > 0) {
                $t = $n;
                $n = $g%$n;
                $g = $t;
            }
        }
    }
    return $g;
}
/*function lcm($n, $m) //least common multiple
{
   return round($m*($n/gcd($n,$m)));
}
*/
function lcm(...$args) 
{
    $g = null;
    foreach ($args as $v) {
        if (!is_numeric($v)) {
            echo "lcm requires numeric inputs.";
            return 1;
        } else if ($g === null) {
            $g = (int) round($v);
        } else {
            $v = (int) round($v);
            $g = round($g*($v/gcd($g,$v)));
        }
    }
    return $g;
}
function dispreducedfraction($n,$d,$dblslash=false,$varinnum=false) {
	return '`'.makereducedfraction($n,$d,$dblslash,$varinnum).'`';
}

function makereducedmixednumber($n,$d) {
	if ($n==0) {return '0';}
    if ($d==0) {return 'undefined';}
	$g = gcd($n,$d);
    if ($g > 1) {
        $n = $n/$g;
        $d = $d/$g;
    }
	if ($d<0) {
		$n = $n*-1;
		$d = $d*-1;
	}
	if ($d==1) {
		return "$n";
	} else {
		if (abs($n)>$d) {
			$w = floor(abs($n)/$d);
			if ($n<0) { $w *= -1;}
			$n -= $w*$d;
			return "$w ".abs($n)."/$d";
		} else {
			return "$n/$d";
		}
	}
}

function makereducedfraction($n,$d,$dblslash=false,$varinnum=false) {
	if ($n==0) {return '0';}
    if ($d==0) {return 'undefined';}
    if (!is_numeric($n) || !is_numeric($d)) {
        echo "makereducedfraction requires numeric inputs";
        return $n.'/'.$d;
    }
	$g = gcd($n,$d);
	if ($g > 1) {
        $n = $n/$g;
        $d = $d/$g;
    }
	if ($d<0) {
		$n = $n*-1;
		$d = $d*-1;
	}
    if ($dblslash === 'parts') {
        return [$n,$d];
    }
	if ($varinnum!==false) {
		if ($n==1) {
			$n = '';
		} else if ($n==-1) {
			$n = '-';
		}
	}
	if ($d==1) {
		if ($varinnum===false) {
			return "$n";
		} else {
			return "$n$varinnum";
		}
	} else {
		if ($dblslash) {
			$slash = '//';
		} else {
			$slash = '/';
		}
		if ($varinnum===false) {
			return "$n$slash$d";
		} else {
			return "($n$varinnum)$slash$d";
		}
	}
}

//use: calconarray($a,"x^$p")
function calconarray($array,$todo) {
    if (!is_array($array)) { 
        echo "Error - First argument to calconarray must be an array";
        return $array; 
    }
	/*global $disallowedwords,$allowedmacros;
	$todo = str_replace($disallowedwords,"",$todo);
	$todo = clean($todo);
	$rsnoquote = preg_replace('/"[^"]*"/','""',$todo);
	$rsnoquote = preg_replace('/\'[^\']*\'/','\'\'',$rsnoquote);
	if (preg_match_all('/([$\w]+)\s*\([^\)]*\)/',$rsnoquote,$funcs)) {
		for ($i=0;$i<count($funcs[1]);$i++) {
			if (strpos($funcs[1][$i],"$")===false) {
				if (!in_array($funcs[1][$i],$allowedmacros)) {
					echo "{$funcs[1][$i]} is not an allowed function<BR>\n";
					return false;
				}
			}
		}
	}
	*/
	$todo = mathphp($todo,'x',false,false);
	$todo = str_replace('(x)','($x)',$todo);
    $todo = tryWrapEvalTodo('return('.$todo.');', 'calconarray');
	return array_map(my_create_function('$x',$todo),$array);
}

function tryWrapEvalTodo($todo, $func='') {
    return 'try { '. $todo .'} catch(Throwable $t) {
        if ($GLOBALS[\'myrights\'] > 10 && !empty($GLOBALS[\'inQuestionTesting\'])) {
            echo "Parse error in '.$func.': ".$t->getMessage().". ";
        }
    }';
}

function keepif($array, $todo) {
    $todo = mathphp($todo,'x',false,true);
	$todo = str_replace('(x)','($x)',$todo);
    $todo = tryWrapEvalTodo('return('.$todo.');', 'keepif');
	return array_values(array_filter($array,my_create_function('$x',$todo)));
}

function arrayremovenull($array) {
    return array_values(array_filter($array, function ($v) {
        return (!is_null($v) && $v !== '');
    }));
}

function multicalconarray() {
	$args = func_get_args();
	$nargs = count($args);
	$todo = array_shift($args);
	$vars = array_shift($args);
	$vars = listtoarray($vars);
	foreach ($vars as $k=>$v) {
		$vars[$k] = preg_replace('/[^\w]/','',$v);
		if ($vars[$k]=='') {
			echo "multicalconarray: Invalid variable";
			return false;
		}
	}
	if ($nargs-2 != count($vars)) {
		echo "multicalconarray: incorrect number of data arrays";
		return false;
	}
    if (!is_array($args[0])) {
        echo "multicalconarray: value array must be an array";
        return false;
    }
	$cnt = count($args[0]);
	for ($i=1; $i<count($args); $i++) {
        if (!is_array($args[$i])) {
            echo "multicalconarray: value array must be an array";
            return false;
        }
		if (count($args[$i]) != $cnt) {
			echo "multicalconarray: Unequal array lengths";
			return false;
		}
	}

	$todo = mathphp($todo,implode('|',$vars),false,false);
	if ($todo=='0;') { return 0;}
	for ($i=0;$i<count($vars);$i++) {
		$todo = str_replace('('.$vars[$i].')','($'.$vars[$i].')',$todo);
	}
	$varlist = '$'.implode(',$',$vars);
	$func = my_create_function($varlist, tryWrapEvalTodo('return('.$todo.');', 'multicalconarray'));
	$out = array();
	for ($j=0;$j<count($args[0]);$j++) {
		$inputs = array();
		for ($i=0; $i<count($args); $i++) {
			$inputs[] = $args[$i][$j];
		}
		$out[] = call_user_func_array($func, $inputs);
	}
	return $out;
}


//use: calconarray($a,"x + .01","floor(x)==x")
function calconarrayif($array,$todo,$ifcond) {
    if (!is_array($array)) { 
        echo "Error - First argument to calconarrayif must be an array";
        return $array; 
    }
	/*global $disallowedwords,$allowedmacros;
	$todo = str_replace($disallowedwords,"",$todo);
	$todo = clean($todo);
	$ifcond = clean($ifcond);
	$rsnoquote = preg_replace('/"[^"]*"/','""',$todo);
	$rsnoquote = preg_replace('/\'[^\']*\'/','\'\'',$rsnoquote);
	if (preg_match_all('/([$\w]+)\s*\([^\)]*\)/',$rsnoquote,$funcs)) {
		for ($i=0;$i<count($funcs[1]);$i++) {
			if (strpos($funcs[1][$i],"$")===false) {
				if (!in_array($funcs[1][$i],$allowedmacros)) {
					echo "{$funcs[1][$i]} is not an allowed function<BR>\n";
					return false;
				}
			}
		}
	}
	*/
	$todo = mathphp($todo,'x',false,false);
	$todo = str_replace('(x)','($x)',$todo);
	/*
	$rsnoquote = preg_replace('/"[^"]*"/','""',$ifcond);
	$rsnoquote = preg_replace('/\'[^\']*\'/','\'\'',$rsnoquote);
	if (preg_match_all('/([$\w]+)\s*\([^\)]*\)/',$rsnoquote,$funcs)) {
		$ismath = true;
		for ($i=0;$i<count($funcs[1]);$i++) {
			if (strpos($funcs[1][$i],"$")===false) {
				if (!in_array($funcs[1][$i],$allowedmacros)) {
					echo "{$funcs[1][$i]} is not an allowed function<BR>\n";
					return false;
				}
			}
		}
	}
	$ifcond = str_replace('!=','#=',$ifcond);
	*/
	$ifcond = mathphp($ifcond,'x',false,false);
	//$ifcond = str_replace('#=','!=',$ifcond);
	$ifcond = str_replace('(x)','($x)',$ifcond);

	$iffunc = my_create_function('$x',tryWrapEvalTodo('return('.$ifcond.');', 'calconarrayif'));

	$tmpfunc = my_create_function('$x',tryWrapEvalTodo('return('.$todo.');', 'calconarrayif'));
	foreach($array as $k=>$x) {
		if ($iffunc($x)) {
			$array[$k] = $tmpfunc($x);
		}
	}
	return $array;
}

function sumarray($array) {
    if (!is_array($array)) {
        echo "sumarray: input must be an array";
        return '';
    }
	return array_sum($array);
}

function intersectarrays($a1,$a2) {
	return array_values(array_intersect($a1,$a2));
}

function diffarrays($a1,$a2) {
	return array_values(array_diff($a1,$a2));
}

function unionarrays($a1,$a2) {
	foreach ($a2 as $v) {
		if (!in_array($v,$a1)) {
			$a1[] = $v;
		}
	}
	return array_values($a1);
}

function prettyint($n) {
    if (!is_numeric($n)) {
        return $n;
    }
	return number_format($n);
}

function prettyreal($aarr,$d=0,$comma=',') {
    if (!is_array($aarr)) {
		$arrayout = false;
		$aarr = array($aarr);
	} else {
		$arrayout = true;
	}
	$out = array();
    $d = intval($d);
	foreach ($aarr as $a) {
        $a = str_replace(',','',$a);
        if (is_numeric($a)) {
            $out[] = number_format($a,$d,'.',$comma);
        } else {
            $out[] = $a;
        }
    }
	if ($arrayout) {
		return $out;
	} else {
		return $out[0];
	}
}
function prettyreal_instring($str,$d=0,$comma=',') {
    return preg_replace_callback('/\d*\.?\d+/', function($m) use ($d,$comma) {
        return prettyreal($m[0],$d,$comma);
    }, $str);
}
function round_instring($str,$d=0) {
    return preg_replace_callback('/\d*\.?\d+/', function($m) use ($d) {
        return round($m[0],$d);
    }, $str);
}
function prettysmallnumber($n, $space=false) {
	if (abs($n)<.01) {
		$a = explode("E",$n);
		if (count($a)==2) {
			if ($n<0) {
				$sign = '-';
			} else {
				$sign = '';
			}
			$n = str_repeat("0", -$a[1]-1).str_replace('.','',abs($a[0]));
			if ($space) {
				$n = preg_replace('/(\d{3})/','$1&thinsp;', $n);
			}
			$n = $sign."0.".$n;
		}
	}
	return $n;
}

function roundsigfig($val, $sigfig) {
	$log = (int) floor(log10(abs($val)));
	return round($val, $sigfig - $log - 1);
}

function prettysigfig($aarr,$sigfig,$comma=',',$choptrailing=false,$orscinot=false,$sigfigbar=false) {
    if (!is_array($aarr)) {
		$arrayout = false;
		$aarr = array($aarr);
	} else {
		$arrayout = true;
	}
	$out = array();

	foreach ($aarr as $a) {
        $a = str_replace(',','',$a);
        if ($a === 'DNE') {
            $out[] = $a;
            continue;
        }
		if ($orscinot && is_numeric($a) && (abs($a)>1000 || abs($a)<.001)) {
			$out[] = makescinot($a, $sigfig-1, '*');
			continue;
		}
		$a = str_replace('xx','*',$a);
		if (strpos($a,'*')!==false) {
			$pts = explode('*', $a);
			$a = $pts[0];
			$scinot = '*'.$pts[1];
		} else {
			$scinot = '';
		}
		if ($a==0) { $out[] = 0; continue;}
        if (!is_numeric($a)) { $out[] = $a; continue;}
		if ($a < 0 ) {
			$sign = '-';
			$a *= -1;
		} else {
			$sign = '';
		}
        $loga = log10($a);
		$v = floor(-$loga-1e-12);
		if ($v+$sigfig <= 0) {
            $multof3 = floor(-($v+$sigfig)/3);
            $tmp = round($a/pow(10,$multof3*3), $v+$sigfig+$multof3*3);
            $a = number_format($tmp,0,'.',$comma).str_repeat($comma.'000',$multof3);
            if ($sigfigbar) {
                //number of digits before first comma
                $digbc = floor(($loga+1)%3)+3*(($loga+1)%3==0);
                $anums = preg_replace('/[^\d]/','',$a);
                if ($comma != '') {
                    //number of commas before sigfig digit
                    $acom = ($sigfig>$digbc)+floor(($sigfig-1-$digbc)/3)*($sigfig>$digbc);
                } else {
                    $acom = 0;
                }
                if (isset($anums[$sigfig]) && $anums[$sigfig] === '0' && $anums[$sigfig-1] === '0') {
                    $a = substr_replace($a, 'overline(0)', $sigfig-1+$acom*strlen($comma), 1);
                } elseif ($anums[$sigfig-1] === '0' && !isset($anums[$sigfig])) {
                    $a = $a.".";
                }
            }
            $out[] = $sign.$a.$scinot;
		} else {
			$nv = round($a, $v+$sigfig);
			$n = number_format($a,$v+$sigfig,'.',$comma);
			if ($choptrailing && ($v+$sigfig > 0) && abs($a - round($a,$v+$sigfig))<1e-12) {
				$n = rtrim($n,'0');
				$n = rtrim($n,'.');
			} else {
				if (floor(-log10($nv)-1e-12) != $v) {  //adjust for .009 -> .010 1 sig
					$n = substr($n,0,-1);
				}
				$n = rtrim($n,'.');
			}
			$out[] = $sign.$n.$scinot;
		}
	}
	if ($arrayout) {
		return $out;
	} else {
		return $out[0];
	}
}

function prettysigfig_instring($str,$sigfig,$comma=',',$choptrailing=false,$orscinot=false,$sigfigbar=false) {
    return preg_replace_callback('/\d*\.?\d+/', function($m) use ($sigfig,$comma,$choptrailing,$orscinot,$sigfigbar) {
        return prettysigfig($m[0],$sigfig,$comma=',',$choptrailing=false,$orscinot=false,$sigfigbar=false);
    }, $str);
}

function makescinot($n,$d=8,$f="x") {
    if (!is_numeric($n)) {
        echo "makescinot needs numeric input; $n given.";
        return $n;
    }
	if ($n==0) { return "0";}
	$isneg = "";
	if ($n<0) { $isneg = "-"; $n = abs($n);}
	$exp = floor(log10($n)+1e-12);
	if ($d==-1) {
		$mant = round($n/pow(10,$exp),8);
	} else {
		$mant = number_format($n/pow(10,$exp),$d);
	}
	if ($f=="*") {
		return "$isneg$mant*10^($exp)";
	} else if ($f=="E") {
		return "$isneg{$mant}E$exp";
	} else {
		return "$isneg$mant xx 10^($exp)";
	}
}

function stringappend($v,$s) {
	if (is_array($v)) {
		foreach($v as $k=>$y) {
			$v[$k] = $v[$k].$s;
		}
	} else {
		$v = $v.$s;
	}
	return $v;
}
function stringprepend($v,$s) {
	if (is_array($v)) {
		foreach($v as $k=>$y) {
			$v[$k] = $s.$v[$k];
		}
	} else {
		$v = $s.$v;
	}
	return $v;
}

function arraystodots($x,$y) {
    if (!is_array($x) || !is_array($y)) {
        echo "Error: inputs to arraystodots must be arrays";
        return [];
    }
	$out = array();
	for ($i=0;$i<count($x);$i++)  {
		$out[] = $x[$i].','.$y[$i];
	}
	return $out;
}

function arraystodoteqns($x,$y,$color='blue') {
    if (!is_array($x) || !is_array($y)) {
        echo "Error: inputs to arraystodoteqns must be arrays";
        return [];
    }
    if (count($x) != count($y)) {
        echo "arraystodoteqns x and y should have same length";
    }
	$out = array();
	for ($i=0;$i<count($x);$i++)  {
        if (isset($y[$i])) {
            $out[] = "dot,".$x[$i].','.$y[$i].',closed,'.$color;
        }
	}
	return $out;
}


function subarray($a) {
	if (is_array(func_get_arg(1))) {
		$args = func_get_arg(1);
	} else {
		$args = func_get_args();
		array_shift($args);
	}
	if (count($args)<1) {return array();}
	$out = array();
    ksort($args);
    foreach ($args as $k=>$v) {
		if (strpos($v,':')!==false) {
			$p = explode(':',$v);
            $p = array_map('evalbasic', $p);
            if (!is_numeric($p[0]) || !is_numeric($p[1])) {
                echo "subarray index ranges need to be numeric or simple calculations";
                continue;
            }
			array_splice($out,count($out),0,array_slice($a,$p[0],$p[1]-$p[0]+1));
		} else {
			$out[] = $a[$v] ?? '';
		}
	}
	return $out;
}

function showdataarray($a,$n=1,$opts=null) {
	if (!is_array($a)) {
		return '';
	}
    $n = floor($n);
    $a = array_values($a);
    $format = 'table';
    $align = "default";
	$caption = "";
    $tablealign = '';
    if (is_array($opts)) {
        if (isset($opts['format'])) {
            $format = $opts['format'];
        }
        if (isset($opts['align'])) {
            $align = strtolower($opts['align']);
        }
        if (isset($opts['caption'])) {
            $caption = $opts['caption'];
        }
        if (isset($opts['tablealign'])) {
            $tablealign = $opts['tablealign'];
        }
    } else if (is_string($opts)) {
        if ($opts == 'pre') {
            $format = 'pre';
        } else {
            $align = strtolower($opts);
        }
    }

	if ($format == 'pre') {
		$maxwidth = 1; $cnt = 0;
		foreach ($a as $v) {
			if (strlen($v)>$maxwidth) {
				$maxwidth = strlen($v);
			}
		}
		$out = '<pre>';
		while ($cnt<count($a)) {
			for ($i=0;$i<$n;$i++) {
				$out .= sprintf("%{$maxwidth}s ",$a[$cnt]);
				$cnt++;
			}
			$out .= "\n";
		}
		$out .= '</pre>';
	} else {
        $cellclass = '';
        if ($align=='c' || $align=='r' || $align=='l') {
            $cellclass = 'class='.$align;
        }
		$out = '<table class=stats';
        if ($tablealign == 'center') {
            $out .= ' style="margin:0 auto;"';
        }
        $out .= '>';
        if ($caption != '') {
            $out .= '<caption>'.Sanitize::encodeStringForDisplay($caption).'</caption>';
        }
        $out .= '<tbody>';
		$cnt = 0;
		while ($cnt<count($a)) {
			$out .= '<tr>';
			for ($i=0;$i<$n;$i++) {
				if (isset($a[$cnt])) {
					$out .= '<td '.$cellclass.'>'.$a[$cnt].'</td>';
				} else {
					$out .= '<td></td>';
				}
				$cnt++;
			}
			$out .= '</tr>';
		}
		$out .= '</tbody></table>';
	}
	return $out;
}

$ones = array( "", " one", " two", " three", " four", " five", " six", " seven", " eight", " nine", " ten", " eleven", " twelve", " thirteen", " fourteen", " fifteen", " sixteen", " seventeen", " eighteen", " nineteen");
$onesth = array("th"," first"," second", " third", " fourth", " fifth", " sixth", " seventh", " eighth", " ninth", " tenth"," eleventh", " twelfth", " thirteenth", " fourteenth"," fifteenth", " sixteenth", " seventeenth", " eighteenth"," nineteenth");
$tens = array( "", "", " twenty", " thirty", " forty", " fifty", " sixty", " seventy", " eighty", " ninety");
$tensth = array("",""," twentieth", " thirtieth", " fortieth", " fiftieth", " sixtieth", " seventieth", " eightieth", " ninetieth");
$triplets = array( "", " thousand", " million", " billion", " trillion", " quadrillion", " quintillion", " sextillion", " septillion", " octillion", " nonillion");
$placevals = array( "", "tenth", "hundredth", "thousandth", "ten-thousandth", "hundred-thousandth", "millionth", "ten-millionth", "hundred-millionth", "billionth");
 // recursive fn, converts three digits per pass
function convertTri($num, $tri, $doth=false, $addcommas=false) {
  global $ones, $onesth, $tens, $tensth, $triplets;

  // chunk the number, ...rxyy
  $r = (int) ($num / 1000);
  $x = ($num / 100) % 10;
  $y = $num % 100;

  // init the output string
  $str = "";

  // do hundreds
  if ($x > 0)
   $str = $ones[$x] . " hundred";

  // do ones and tens
  if ($y < 20) {
	  if ($doth && $tri==0) {
		  $str .= $onesth[$y];
	  } else {
		  $str .= $ones[$y];
	  }
  } else {
	  if ($doth && $tri==0) {
		  if ($y%10==0) {
			  $str .= $tensth[(int) ($y / 10)];
		  } else {
			  $str .= $tens[(int) ($y / 10)] .'-'. trim($onesth[$y % 10]);
		  }
	  } else {
	  	$str .= $tens[(int) ($y / 10)] .(($y%10==0)?'':'-'.trim($ones[$y % 10]));
	  }
  }
  // add triplet modifier only if there
  // is some output to be modified...
  if ($str != "")
   $str .= $triplets[$tri];
  // continue recursing?
  if ($r > 0) {
   $prev = convertTri($r, $tri+1, false, $addcommas);
   return $prev.(($addcommas && $prev != '' && $str != '')?',':'').$str;
  } else {
   return $str;
  }
 }

function numtowords($num,$doth=false,$addcontractiontonum=false,$addcommas=false) {
	global $placevals;

	if ($addcontractiontonum) {
		$num = strval($num);
		$len = strlen($num);
		$last = $num[$len-1];
		if ($len>1 && $num[$len-2]=="1") { //ie 612
			$c = "th";
		} else if ($last=="1") {
			$c = "st";
		} else if ($last=="2") {
			$c = "nd";
		} else if ($last=="3") {
			$c = "rd";
		} else {
			$c = "th";
		}
		return $num.$c;
	}
	if ($num==0) {
		return "zero";
	}
	$out = '';
	if ($num<0) {
		$out .= 'negative ';
		$num = abs($num);
	}
    $num = round($num, 9);
	$int = floor($num);
	$dec = round($num-$int,9);
    
	if ($int>0) {
		$out .= convertTri($int,0,$doth,$addcommas);
		if (abs($dec)>1e-10) {
			$out .= " and ";
		}
	}
	if (abs($dec)>1e-10) {
		$cnt = 0;
		while (abs($dec-round($dec))>1e-10 && $cnt<9) {
			$dec = round(10*$dec, 9);
			$cnt++;
		}
		$out .= convertTri(round($dec),0);
		$out .= ' '.$placevals[$cnt];
		if (round($dec)!=1) {
			$out .= 's';
		}

	}
	return trim($out);
}

function fractowords($numer,$denom,$options='no') { //options can combine 'hyphen','mixed','over','by' and 'literal'
  global $placevals;

  if (strpos($options,'mixed')===false) {
    $int='';
  }
  $numersign=sign($numer);
  $denomsign=sign($denom);
  //creates integer and new numerator for mixed numbers
  if (strpos($options,'mixed')!==false || strpos($options,'literal')===false) { //mixed or not literal
    if (abs($numer-floor($numer))>1e-9 || abs($denom-floor($denom))>1e-9) { //integers only
      return '';
    }
    if ($denom==0) {
      echo 'Eek! Division by zero.';
      return '';
    }
    if ($numer==0) {
      return 'zero';
    }
      $numernew=abs($numer)%(abs($denom));
      $numer=abs($numer); //numer and denom now positive
      $denom=abs($denom);
      $int=floor($numer/$denom);

      if ($numernew==0) {//did fraction reduce to a whole number?
        $int='';
        $numer=$numer*$numersign;
        $denom=$denom*$denomsign;
        return numtowords($numer/$denom);
      } elseif ($numernew!=0) {//is there a remainder after dividing?
        if ($int==0) {//was the fraction proper to begin with?
          $numer=$numernew*$numersign*$denomsign;
          $int='';
      } elseif ($int!=0) {//was the fraction improper to begin with?
        if (strpos($options,'mixed')===false) {//not mixed and not literal
          $int='';
          $numer=$numer*$numersign*$denomsign;
        } elseif (strpos($options,'mixed')!==false) {//mixed and not literal
          $int=numtowords($int*$numersign*$denomsign).' and ';
          $numer=$numernew;
        }
      }
    }
  } //end (mixed or not literal)

//handles non-mixed numbers or fractional part of mixed numbers
  if (abs($numer-floor($numer))>1e-9 || abs($denom-floor($denom))>1e-9) { //integers only
    return '';
  }
  if ($denom==0) {
    return '';
  } else {
    if (strpos($options,'over')===false && strpos($options,'by')===false) { //not over, not by
      $top=numtowords($numer);
      if ($denom==1) {
        $bot='whole';
      } elseif ($denom==-1) {
        $bot='negative whole';
      } elseif ($denom==2) {
        if (abs($numer)==1) {
          $bot='half';
        } elseif ($numer!=1) {
          $bot='halve';
        }
      } elseif ($denom==-2) {
        if ($numer==1) {
          $bot='negative half';
        } else {
          $bot='negative halve';
        }
      } else if (abs(round(log10(abs($denom))) - log10(abs($denom)))<1e-12) { // is multiple of 10
        $bot = $placevals[round(log10(abs($denom)))];
      } else {
        $bot=str_replace(' ','-',numtowords($denom,true));
      }

      $dohypen = (strpos($options,'hyphen')!==false && strpos($top,'-')===false && strpos($bot,'-')===false);
      if (abs($numer)==1) {
        return $int.$top.($dohypen?'-':' ').$bot;
      } else {
        return $int.$top.($dohypen?'-':' ').$bot.'s';
      }

    } elseif (strpos($options,'over')!==false) {//over or overby, prefers over
      return $int.numtowords($numer).' over '.numtowords($denom);
    } elseif (strpos($options,'by')!==false) {//by or overby
      return $int.numtowords($numer).' by '.numtowords($denom);
    }
  }
}

$namearray[0] = ['Aaron','Aarón','Aarush','Abraham','Adam','Aden','Adewale','Adrian','Adriel','Agustín','Ahanu','Ahmed','Ahmod','Aidan','Aiden','Alan','Alang','Alejandro','Alex','Alexander','Alexei','Alexis','Alfonso','Alfredo','Alo','Alonso','Alonzo','Alphonso','Álvaro','Alvin','Amari','Amir','Anakin','Anderson','Andre','Andrei','Andres','Andrés','Andrew','Ángel','Angelo','Anthony','Antoine','Antonio','Arjun','Armando','Arno','Arturo','Arun','Asaad','Asher','Ashton','Atharv','Austin','Autry','Áxel','Ayden','Azarias','Bastián','Bautista','Ben','Benicio','Benjamin','Bill','Billy','Blake','Booker','Braden','Bradley','Brady','Brandon','Brayden','Brendan','Bret','Brian','Brody','Bruno','Bryan','Bryce','Bryson','Caden','Cai','Caleb','Calian','Calvin','Cameron','Canon','Carlos','Carlton','Carson','Carter','Casey','Cavanaugh','Cayden','Cesar','Chad','Chan','Chance','Charles','Chase','Cheick','Cheng','Chris','Christian','Christopher','CJ','Cody','Colan','Colby','Cole','Collin','Colton','Conner','Connor','Cooper','Corey','Cornell','Courri','Craig','Cristian','Curtis','Dajon','Dak','Dakota','Dale','Dalton','Damian','Damien','Daniel','Danny','Dante','Daran','Dario','Darius','Darnell','Darrell','Darryl','David','Daymond','Deandre','DeAndre','Deion','Demetrius','Denali','Derek','Deshawn','Devante','Devin','Devon','Devonte','Diego','Dion','Dmitry','Dominic','Dominique','Donald','Donnel','Donovan','Duan','Dustin','Dwayne','Dwight','Dylan','Edgar','Eduardo','Edward','Edwin','Eli','Elías','Elijah','Emanuel','Emiliano','Emilio','Emmanuel','Emmett','Enrique','Enzo','Eric','Erick','Erik','Ermias','Ervin','Esteban','Ethan','Evan','Facundo','Farrell','Farzad','Felipe','Fernando','Finn','Foluso','Forest','Francisco','Franco','Frank','Frederick','Furnell','Gabriel','Gael','Gage','Garlin','Garrett','Gavin','George','Gerardo','Giovanni','Gonzalo','Grant','Gregory','Guyton','Hakeem','Hampton','Hao','Harrison','Hayden','Hector','Henry','Herold','Hopi','Hu','Hudson','Hugo','Hunter','Ian','Ibrahim','Ibram','Ignacio','Iker','Isaac','Isaiah','Israel','Ivan','Izaak','Izan','Jabulani','Jace','Jack','Jackson','Jacob','Jacy','Jaden','Jahkil','Jaime','Jake','Jalen','Jamaal','Jamal','James','Jamison','Jared','Jason','Javier','Jay','Jayceon','Jayden','Jaylen','Jayson','Jeff','Jeffrey','Jeremiah','Jeremy','Jermaine','Jerome','Jerónimo','Jesse','Jessie','Jimmy','Joaquín','Joel','Joey','Johan','John','John Carlo','John Lloyd','John Mark','John Michael','John Paul','John Rey','Jon','Jonah','Jonathan','Jordan','Jorge','Jose','José','Joseph','Josh','Joshua','Josiah','Juan','Juan José','Juan Martín','Juan Pablo','Julian','Julián','Julio','Justice','Justin','Juwan','Kaden','Kai','Kaiden','Kaleb','Kareem','Karlus','Kayden','Keegan','Kehinde','Kele','Ken','Kendrick','Kenneth','Kenny','Kerel','Kevin','Keyon','Kim','King','Kirk','Kosumi','Krishna','Kwame','Kwan','Kyle','Kyrie','Lamont','Landon','LeBron','Lee','Lennon','Leo','León','Leonardo','Leonel','Lester','Levi','Lewis','Li','Lian','Lloyd','Logan','Londell','Lorenzo','Louis','Loyiso','Luan','Luca','Lucas','Luciano','Luis','Luke','Luther','Major','Malachi','Malcolm','Malik','Mamadou','Mandla','Manny','Manuel','Marcel','Marcelo','Marco','Marcos','Marcus','Mario','Mark','Marquis','Marshall','Martin','Martín','Mason','Mateo','Matías','Mato','Matt','Matthew','Maurice','Mauricio','Max','Maxim','Maximiliano','Maxwell','Mayowa','Meng','Micah','Michael','Miguel','Mika','Mikhail','Miles','Mitchell','Mohamed','Mondaire','Montana','Moussa','Nahele','Nasir','Nate','Nathan','Nathaniel','Nayati','Neel','Nicholas','Nick','Nicolás','Nihad','Noah','Nodin','Noel','Nolan','Nova','Nuka','Nyeeam','Oliver','Omar','Oscar','Otis','Owen','Pablo','Parker','Parnell','Parvez','Patrick','Paul','Paulo','Pedro','Perry','Peter','Peyton','Phillip','Pierre','Porter','Pranav','Preston','Qasim','Quan','Quinn','Rafael','Rahquez','Ramogi','Randall','Raymond','Recardo','Reggie','Reginald','Ren','Reza','Ricardo','Richard','Ricky','Riley','Rippy','Ritchie','Robert','Roberto','Rodney','Rodrigo','Roman','Roscoe','Ross','Roy','Russell','Ryan','Salvador','Sam','Samuel','Santiago','Santino','Santos','Scott','Sean','Sebastián','Seni','Sergio','Seth','Shane','Shaun','Shaurya','Shawn','Simón','Skyler','Spencer','Stacey','Stephen','Sterling','Steven','Tadeo','Takoda','Ta-Nehisi','Tanner','Tarell','Tauri','Taylor','Terrance','Terrell','Terrence','Tevin','Thiago','Thomas','Timothy','Tobias','Todd','Tokala','Tom','Tomás','Tony','Tran','Travis','Tremanie','Trent','Trenton','Trevante','Trevion','Trevon','Trevor','Trey','Treyvon','Tristan','Trymaine','Tyler','Tyrone','Valentino','Van','Vicente','Victor','Vihaan','Vincent','Wade','Walter','Warren','Wayne','Wayra','Wesley','William','Willie','Wiyot','Wyatt','Xavier','Yan','Yane','Yoni','Zach','Zachary','Zaire','Zeke','Zephan','Zhang','Zion'];
$namearray[1] = ['Aaliyah','Abby','Abigail','Abril','Addison','Adriana','Adrianna','Agustina','Ainhoa','Aisha','Aitana','Aiyana','Akilah','Alana','Alba','Alecia','Alejandra','Aleshya','Alexa','Alexandra','Alexandria','Alexia','Alexis','Alexus','Alice','Alicia','Alina','Aliyah','Allison','Alma','Alondra','Althea','Alyce','Alyson','Alyssa','Amahle','Amanda','Amani','Amber','Amelia','Amina','Aminata','Amy','Ana','Ana Paula','Ananya','Anastasia','Andrea','Angel','Angela','Angelica','Angelina','Angeline','Anika','Aniyah','Anna','Antonella','Antonia','Anushka','Anya','Aponi','April','Ariana','Arianna','Ashley','Ashlyn','Ashton','Asia','Aubrey','Audrey','Aurora','Autumn','Ava','Averie','Avery','Avni','Bailey','Banu','Bao','Bea','Bella','Betty','Bianca','Bisa','Braelin','Breanna','Brenda','Breonna','Bria','Brianna','Brigeth','Brittany','Brooke','Brooklyn','Caitlyn','Camila','Candela','Capria','Carie','Carissa','Carla','Carlota','Carmen','Carolina','Caroline','Carolyn','Carrie','Cassandra','Cassidy','Catalina','Catherine','Catori','Cecilia','Cedrica','Charlotte','Chasity','Chee','Chelsea','Cheyenne','Chloe','Christina','Christine','Christy','Ciara','Claire','Clara','Claudia','Colleen','Constanza','Cori','Courtney','Cristina','Crystal','Daisy','Dallas','Dana','DaNeeka','Daniela','Danielle','Danna','Dawn','Daysha','Dazzline','Deborah','Deja','Delaney','Delfina','Delia','DeShuna','Destiny','Diamond','Diana','Dyani','Ebony','Edith','Elana','Elena','Elisa','Elizabeth','Ella','Ellie','Elu','Emilia','Emily','Emma','Enola','Erica','Erika','Erin','Esmeralda','Eva','Eve','Evelyn','Ezra','Faith','Fan','Fatema','Fatoumata','Fayth','Fernanda','Francesca','Gabriela','Gabriella','Gabrielle','Gail','Genesis','Gianna','Giselle','Grace','Gracie','Guadalupe','Gwen','Hailey','Haley','Halona','Hanita','Hanna','Hannah','Hazzell','Heather','Heaven','Hillary','Himari','Holly','Hope','Icema','Ida','Imani','Indigo','Isabel','Isabella','Isabelle','Isfa','Issa','Istas','Ivanna','Jacqueline','Jada','Jade','Jamie','Janai','Jane','Janelle','Janet','Janice','Jashanna','Jasmin','Jasmine','Jayla','Jazmin','Jeanette','Jenna','Jennifer','Jenny','Jessa Mae','Jessica','Jia','Jillian','Jocelyn','Johnetta','Joni','Jordan','Jordyn','Josefa','Josefina','Juana','Julia','Juliana','Julie','Julieta','Kaileika','Kaitlyn','Kamala','Karen','Karina','Karissa','Karla','Kasa','Kassandra','Kate','Katelyn','Kateri','Katherine','Kathryn','Katie','Katrice','Kayla','Kaylee','Kelly','Kelsey','Kelsi','Kendall','Kendra','Kenita','Kennedy','Keyanna','Kiana','Kiara','Kiersten','Kimani','Kimberlé','Kimberly','Kimi','Kimora','Kira','Kisha','Kizzmekia','Kori','Kristel','Kristen','Kristina','Kristyn','Krystal','Kyla','Kylee','Kylie','Lacee','Lafyette','Laia','Laila','Latasha','Lateefah','LaTosha','Laura','Lauren','Laverne','Layla','Leah','Leanne','Leilani','Leire','Lena','Leslie','Liana','Liliana','Lillian','Lily','Linda','Lindsay','Lindsey','Lola','Lomasi','London','Lu','Lucía','Luciana','Lucy','Luna','Lydia','Lynda','Mackenzie','Madelaine','Madeline','Madelyn','Madison','Maggie','Maia','Maisha','Maite','Maji','Makayla','Makenzie','Manuela','Margaret','Mari','María','María Fernanda','María Victoria','Mariah','Mariam','Mariana','Marie','Mariel','Maris','Marissa','Marley','Marsai','Martina','Mary Grace','Mary Joy','Maxine','Maya','Maylin','Maymay','Mckenzie','Megan','Mei','Melanie','Melique','Melissa','Mellody','Melynda','Meredith','Merryll','Mia','Michelle','Mika','Mikayla','Mini','Miracle','Miranda','Misty','Mitena','Molly','Monique','Morgan','Mya','Na’estse','Nadia','Nadine','Nakala','Nandi','Naomi','Natalia','Natalie','Natasha','Navaeh','Navya','Neichelle','Nevaeh','Neveah','Nia','Nicole','Nikole','Nina','Noa','Noelle','Nylah','Odina','Olivia','Opal','Orenda','Orlena','Paige','Palesa','Pamela','Pari','Paris','Patricia','Patriciana','Patrisse','Paula','Paulina','Pavati','Payton','Peyton','Pilar','Precious','Prisha','Priya','Quetta','Rachael','Rachel','Rachelle','Rafaela','Raquel','Rashida','Raven','Reagan','Rebecca','Regina','Renata','Renee','Reshanda','Rhianna','Riley','Rita','Riya','Romina','Rosa','Rosetta','Roya','Ruby','Rylee','Saada','Sabrina','Sadie','Sadiqa','Sahana','Sakari','Salomé','Samantha','Samira','Sara','Sarah','Savannah','Scarlett','Scherita','Serena','Serenity','Shani','Shania','Shanice','Shannon','Shante','Shantel','Sharlee','Shelby','Sheniqua','Sierra','Skylar','Sloane','Sofía','Sonia','Sonya','Sophia','Sophie','Soraya','Soyala','Stacey','Stacy','Stephanie','Summer','Sunny','Sybil','Sydney','Tabria','Tallulah','Tamika','Tanya','Tara','Tarana','Tatiana','Tayen','Taylor','Teresa','Teyonah','Thandiwe','Thulile','Tia','Tiana','Tiara','Tierra','Tiffany','Tiva','Tomi','Tracee','Tracey','Trashia','Treasure','Trinidad','Trinity','Umbrosia','Urika','Valentina','Valeria','Valerie','Vanessa','Veronica','Victoria','Violeta','Vivian','Wei','Wendy','Whitney','Winona','Ximena','Yara','Yolanda','Yvette','Zari','Zhao','Zheng','Zoe','Zoey','Zuri'];

$cityarray_US = explode(',','Los Angeles,Dallas,Houston,Atlanta,Detroit,San Francisco,Minneapolis,St. Louis,Baltimore,Pittsburgh,Cincinnati,Cleveland,San Antonio,Las Vegas,Milwaukee,Oklahoma City,New Orleans,Tucson,New York City,Chicago,Philadelphia,Miami,Boston,Phoenix,Seattle,San Diego,Tampa,Denver,Portland,Sacramento,Orlando,Kansas City,Nashville,Memphis,Hartford,Salt Lake City');
$cityarray_CA = explode(',','Toronto,Montreal,Calgary,Ottawa,Edmonton,Mississauga,Winnipeg,Vancouver,Brampton,Hamilton,Québec City,Surrey,Laval,Halifax,London,Gatineau,Saskatoon,Kitchener,Burnaby,Windsor,Regina,Victoria,Richmond,Fredericton,Saint John,Yellowknife,Sydney,Iqaluit,Charlottetown,Whitehorse');

$countryarray = explode(',','Afghanistan,Albania,Algeria,Andorra,Angola,Antigua & Deps,Argentina,Armenia,Australia,Austria,Azerbaijan,Bahamas,Bahrain,Bangladesh,Barbados,Belarus,Belgium,Belize,Benin,Bhutan,Bolivia,Bosnia Herzegovina,Botswana,Brazil,Brunei,Bulgaria,Burkina,Burundi,Cambodia,Cameroon,Canada,Cape Verde,Central African Rep,Chad,Chile,China,Colombia,Comoros,Congo,Congo,Costa Rica,Croatia,Cuba,Cyprus,Czech Republic,Denmark,Djibouti,Dominica,Dominican Republic,East Timor,Ecuador,Egypt,El Salvador,Equatorial Guinea,Eritrea,Estonia,Ethiopia,Fiji,Finland,France,Gabon,Gambia,Georgia,Germany,Ghana,Greece,Grenada,Guatemala,Guinea,Guinea-Bissau,Guyana,Haiti,Honduras,Hungary,Iceland,India,Indonesia,Iran,Iraq,Ireland,Israel,Italy,Ivory Coast,Jamaica,Japan,Jordan,Kazakhstan,Kenya,Kiribati,North Korea,South Korea,Kosovo,Kuwait,Kyrgyzstan,Laos,Latvia,Lebanon,Lesotho,Liberia,Libya,Liechtenstein,Lithuania,Luxembourg,Macedonia,Madagascar,Malawi,Malaysia,Maldives,Mali,Malta,Marshall Islands,Mauritania,Mauritius,Mexico,Micronesia,Moldova,Monaco,Mongolia,Montenegro,Morocco,Mozambique,Myanmar,Namibia,Nauru,Nepal,Netherlands,New Zealand,Nicaragua,Niger,Nigeria,Norway,Oman,Pakistan,Palau,Panama,Papua New Guinea,Paraguay,Peru,Philippines,Poland,Portugal,Qatar,Romania,Russia,Rwanda,St Kitts & Nevis,St Lucia,Saint Vincent & the Grenadines,Samoa,San Marino,Sao Tome & Principe,Saudi Arabia,Senegal,Serbia,Seychelles,Sierra Leone,Singapore,Slovakia,Slovenia,Solomon Islands,Somalia,South Africa,South Sudan,Spain,Sri Lanka,Sudan,Suriname,Swaziland,Sweden,Switzerland,Syria,Taiwan,Tajikistan,Tanzania,Thailand,Togo,Tonga,Trinidad & Tobago,Tunisia,Turkey,Turkmenistan,Tuvalu,Uganda,Ukraine,United Arab Emirates,United Kingdom,United States,Uruguay,Uzbekistan,Vanuatu,Vatican City,Venezuela,Vietnam,Yemen,Zambia,Zimbabwe');

function randcities($n=1, $country="USA") {
	global $cityarray_US,$cityarray_CA;
	
	if ($country=="Canada"){
		$cityarray = $cityarray_CA;
	} elseif ($country == "USA") {
		$cityarray = $cityarray_US;
	} else { echo "randcity only accepts 'USA' and 'Canada' at the moment."; return "";}

	
	$c = count($cityarray);
	if ($n==1) {
		return $cityarray[$GLOBALS['RND']->rand(0,$c-1)];
	} else {
		$out = $cityarray;
		$GLOBALS['RND']->shuffle($out);
		return array_slice($out,0,$n);
	}
}

function randcountries($n=1) {
	global $countryarray;
	$c = count($countryarray);
	if ($n==1) {
		return $countryarray[$GLOBALS['RND']->rand(0,$c-1)];
	} else {
		$out = $countryarray;
		$GLOBALS['RND']->shuffle($out);
		return array_slice($out,0,$n);
	}
}

function randstates($n=1, $country="USA") {
	
	if ($country=="Canada"){
		$states = array("Alberta","British Columbia","Manitoba","New Brunswick","Newfoundland and Labrador","Northwest Territories","Nova Scotia","Nunavut","Ontario","Prince Edward Island","Quebec","Saskatchewan","Yukon");
	} elseif ($country == "USA") {
		$states = array("Alabama","Alaska","Arizona","Arkansas","California","Colorado","Connecticut","Delaware","Dist. of Columbia","Florida","Georgia","Hawaii","Idaho","Illinois","Indiana","Iowa","Kansas","Kentucky","Louisiana","Maine","Maryland","Massachusetts","Michigan","Minnesota","Mississippi","Missouri","Montana","Nebraska","Nevada","New Hampshire","New Jersey","New Mexico","New York","North Carolina","North Dakota","Ohio","Oklahoma","Oregon","Pennsylvania","Rhode Island","South Carolina","South Dakota","Tennessee","Texas","Utah","Vermont","Virginia","Washington","West Virginia","Wisconsin","Wyoming");
	} else { echo "randstate only accepts 'USA' and 'Canada'."; return "";}
	
	$c = count($states);
	if ($n==1) {
		return $states[$GLOBALS['RND']->rand(0,$c-1)];
	} else {
		$GLOBALS['RND']->shuffle($states);
		return array_slice($states,0,$n);
	}
}

function randstate($country="USA") {
	return randstates(1, $country);
}
function randcity($country="USA") {
	return randcities(1, $country);
}
function randcountry() {
  return randcountries(1);
}

function randnames($n=1,$gender=2) {
	global $namearray;
    $n = floor($n);
	if ($n==1) {
		if ($gender==2) {
			$gender = $GLOBALS['RND']->rand(0,1);
		}
        $maxNameIndex = count($namearray[$gender]) - 1;
		return $namearray[$gender][$GLOBALS['RND']->rand(0,$maxNameIndex)];
	} else {
		$out = array();
        $maxNameIndex = [count($namearray[0]) - 1, count($namearray[1]) - 1];
        // use a step to avoid adjacent names to avoid too-similar names
        $step = max(1, min(20,floor(min($maxNameIndex)/$n)));
        $locs = [];
		$locs[0] = diffrrands(0,$maxNameIndex[0],$step,$n);
        $locs[1] = diffrrands(0,$maxNameIndex[1],$step,$n);
        $thisgender = $gender;
		for ($i=0; $i<$n;$i++) {
			if ($gender==2) {
				$thisgender = $GLOBALS['RND']->rand(0,1);
			}
			$out[] = $namearray[$thisgender][$locs[$thisgender][$i]];
		}
		return $out;
	}
}

function randmalenames($n=1) {
	return randnames($n,0);
}
function randfemalenames($n=1) {
  return randnames($n,1);
}
function randname() {
	return randnames(1,2);
}
function randnamewpronouns($g=2) {
  $gender = $GLOBALS['RND']->rand(0,1);
  
  if ($g==2) {
  	if ($gender==0) { //male
  		return array(randnames(1,0), _('he'), _('him'), _('his'), _('his'), _('himself'));
  	} else {
  		return array(randnames(1,1), _('she'), _('her'), _('her'), _('hers'), _('herself'));
  	}
  } elseif ($g=='neutral') {
    if ($gender==0) { //male
  		return array(randnames(1,0), _('they'), _('them'), _('their'), _('theirs'), _('themself'));
  	} else {
  		return array(randnames(1,1), _('they'), _('them'), _('their'), _('theirs'), _('themself'));
  	}
  }

}
function randmalename() {
	return randnames(1,0);
}
function randfemalename() {
	return randnames(1,1);
}

function prettytime($time,$in,$out) {
	if ($in=='m') {
		$time *= 60;
	} else if ($in=='h') {
		$time *= 60*60;
	}
	$hrs = $time/3600;
	$min = $time/60;
	$outst = '';
	if (strpos($out,'clock')!==false) { //clock time
		$hrs = floor($hrs);
		$min = floor($min -60*$hrs);
		$sec = round($time - 60*$min - 3600*$hrs);
		while ($hrs>24) {
			$hrs -= 24;
		}
		$ampm = ($hrs<12?"am":"pm");
		if ($hrs>=13) {
			$hrs -= 12;
		} else if ($hrs==0) {
			$hrs = 12;
		}
		if ($out=='sclock') {
			if ($min<10) {	$min = '0'.$min;}
			if ($sec<10) {	$sec = '0'.$sec;}
			$outst = "$hrs:$min:$sec $ampm";
		} else {
			if ($min<10) {	$min = '0'.$min;}
			$outst = "$hrs:$min $ampm";
		}
		return $outst;

	}
	if (strpos($out,'h')!==false) { //has hrs
		if (strpos($out,'m')!==false) { //has min
			$hrs = floor($hrs);
			if (strpos($out,'s')!==false) {  //hrs min sec
				$min = floor($min-60*$hrs);
				$sec = round($time - 60*$min - 3600*$hrs,4);
				$outst = "$hrs hour" . ($hrs>1 ? 's':'');
				$outst .= ", $min minute" . ($min>1 ? 's':'');
				$outst .= ", and $sec second" . ($sec!=1 ? 's':'');
			} else { //hrs min
				$min = round($min - 60*$hrs,4);
				$outst = "$hrs hour" . ($hrs>1 ? 's':'');
				$outst .= " and $min minute" . ($min!=1 ? 's':'');
			}
		} else { //no min
			if (strpos($out,'s')!==false) {  //hrs sec
				$hrs = floor($hrs);
				$sec = round($time - 3600*$hrs,4);
				$outst = "$hrs hour" . ($hrs>1 ? 's':'');
				$outst .= " and $sec second" . ($sec!=1 ? 's':'');
			} else {//just hrs
				$hrs = round($hrs,4);
				$outst = "$hrs hour" . ($hrs!=1 ? 's':'');
			}
		}
	} else { //no hours
		if (strpos($out,'m')!==false) { //
			if (strpos($out,'s')!==false) {  //min sec
				$min = floor($min);
				$sec = round($time - 60*$min,4);
				$outst = "$min minute" . ($min>1 ? 's':'');
				$outst .= " and $sec second" . ($sec!=1 ? 's':'');
			} else { //min only
				$min = round($min,4);
				$outst = "$min minute" . ($min!=1 ? 's':'');
			}
		} else if (strpos($out,'s')!==false) {  //sec
			$time = round($time,4);
			$outst = "$time second". ($time!=1 ? 's':'');
		}
	}
	return $outst;
}

function definefunc($func,$varlist) {
	$vars = listtoarray($varlist);
	/*$toparen = implode('|',$vars);
	if ($toparen != '') {
		$reg = "/(" . $toparen . ")(" . $toparen . ')$/';
		  $func= preg_replace($reg,"($1)($2)",$func);
		  $reg = "/(" . $toparen . ")(sqrt|ln|log|sin|cos|tan|sec|csc|cot|abs)/";
		  $func= preg_replace($reg,"($1)$2",$func);
		  $reg = "/(" . $toparen . ")(" . $toparen . ')([^a-df-zA-Z\(])/';
		  $func= preg_replace($reg,"($1)($2)$3",$func);
		  $reg = "/([^a-zA-Z])(" . $toparen . ")([^a-zA-Z])/";
		  $func= preg_replace($reg,"$1($2)$3",$func);
		  //need second run through to catch x*x
		  $func= preg_replace($reg,"$1($2)$3",$func);
		  $reg = "/^(" . $toparen . ")([^a-zA-Z])/";
		  $func= preg_replace($reg,"($1)$2",$func);
		  $reg = "/([^a-zA-Z])(" . $toparen . ")$/";
		  $func= preg_replace($reg,"$1($2)",$func);
		  $reg = "/^(" . $toparen . ")$/";
		  $func= preg_replace($reg,"($1)",$func);

		  $reg = "/\(\((" . $toparen . ")\)\)/";
		  $func= preg_replace($reg,"($1)",$func);
		  $func= preg_replace($reg,"($1)",$func);
	}
	*/
	return array($func,$varlist);
}

function getstuans($v,$q,$i=0,$blankasnull=true) {
    if (!isset($v[$q])) { return null;}
	if (is_array($v[$q])) {
        if (!isset($v[$q][$i])) {
            return null;
        } else if ($blankasnull && ($v[$q][$i]==='' || $v[$q][$i]==='NA')) {
            return null;
        }
		return $v[$q][$i];
	} else {
        if ($blankasnull && ($v[$q]==='' || $v[$q]==='NA')) {
            return null;
        }
		return $v[$q];
	}
}

function evalnumstr($str, $docomplex=false) {
    if (trim($str) === '') { return ''; }
    return evalMathParser($str, $docomplex);
}

function evalfunc($farr) {
	$args = func_get_args();
	array_shift($args);
	if (is_array($farr)) {
		list($func,$varlist) = $farr;
		//$skipextracleanup = true;
	} else {
		$func = $farr;
		$varlist = array_shift($args);
		//$skipextracleanup = false;
	}
	$skipextracleanup = false;
	$func = makepretty($func);
	$vars = listtoarray($varlist);
	if (count($args)==count($vars)+1) {
		$skipextracleanup = true;
		array_pop($args);
	} else if (count($vars)!=count($args)) {
		echo "Number of inputs to function doesn't match number of variables";
        return false;
	}
	$isnum = true;
	for ($i=0;$i<count($args);$i++) {
		if (!is_numeric($args[$i])) {
			$isnum = false;
		}
	}
	foreach ($vars as $k=>$v) {
		$vars[$k] = preg_replace('/[^\w]/','',$v);
		if ($vars[$k]=='') {
			echo "Invalid variable";
			return false;
		}
	}
	$toparen = implode('|',$vars);

	if ($isnum) {
        $func = makeMathFunction($func, implode(',', $vars), [], '', true);
        if ($func === false) {
            return '';
        }
		foreach ($vars as $i=>$var) {
			if (is_array($args[$i]) || !is_numeric($args[$i])) {
                echo 'invalid input to evalfunc';
                return false;
            }
			$varvals[$var] = $args[$i];
		}
		return $func($varvals);
	} else { //just replacing
		if ($toparen != '') { // && !$skipextracleanup) {
			  $reg = "/(" . $toparen . ")(" . $toparen . ')$/';
			  $func= preg_replace($reg,"($1)($2)",$func);
			  $reg = "/(" . $toparen . ")(sqrt|ln|log|sin|cos|tan|sec|csc|cot|abs)/";
			  $func= preg_replace($reg,"($1)$2",$func);
			  $reg = "/(" . $toparen . ")(" . $toparen . ')([^a-df-zA-Z\(])/';
			  $func= preg_replace($reg,"($1)($2)$3",$func);
			  $reg = "/([^a-zA-Z])(" . $toparen . ")([^a-zA-Z])/";
			  $func= preg_replace($reg,"$1($2)$3",$func);
			  //need second run through to catch x*x
			  $func= preg_replace($reg,"$1($2)$3",$func);
			  $reg = "/^(" . $toparen . ")([^a-zA-Z])/";
			  $func= preg_replace($reg,"($1)$2",$func);
			  $reg = "/([^a-zA-Z])(" . $toparen . ")$/";
			  $func= preg_replace($reg,"$1($2)",$func);
			  $reg = "/^(" . $toparen . ")$/";
			  $func= preg_replace($reg,"($1)",$func);

			  $reg = "/\(\((" . $toparen . ")\)\)/";
			  $func= preg_replace($reg,"($1)",$func);
			  $func= preg_replace($reg,"($1)",$func);
		}
		foreach ($vars as $i=>$var) {
            if (is_array($args[$i])) {
                echo 'invalid input to evalfunc';
                return false;
            }
			$func = str_replace("($var)","({$args[$i]})",$func);
		}

		if (!$skipextracleanup) {
			$reg = '/^\((\d*?\.?\d*?)\)([^\d\.])/';
			$func= preg_replace($reg,"$1$2",$func);
			$reg = '/^\(([a-zA-Z])\)([^a-zA-Z])/';
			$func= preg_replace($reg,"$1$2",$func);

			//$reg = '/([^\d\.])\((\d*?\.?\d*?)\)$/';
			//$func= preg_replace($reg,"$1$2",$func);
			$reg = '/([^a-zA-Z])\(([a-zA-Z])\)$/';
			$func= preg_replace($reg,"$1$2",$func);

			//$reg = '/([^\d\.])\((\d*?\.?\d*?)\)([^\d\.])/';
			//$func= preg_replace($reg,"$1$2$3",$func);
			$reg = '/([^a-zA-Z])\(([a-zA-Z])\)([^a-zA-Z])/';
			$func= preg_replace($reg,"$1$2$3",$func);
		}
		return $func;
	}
}

function textonimage() {
	$args = func_get_args();
    $img = array_shift($args);

    if (substr($img,0,4)=='http') {
        $img = '<img src="'.Sanitize::encodeStringForDisplay($img).'" alt="" />';
    }
    
	$out = '<div style="position: relative;" class="txtimgwrap">';
	$out .= '<div class="txtimgwrap" style="position:relative;top:0px;left:0px;">'.$img.'</div>';
	
    while (count($args)>2) {
		$text = array_shift($args);
		$left = array_shift($args);
        $top = array_shift($args);
        $hidden = (strpos($text,'[AB')===false)?'aria-hidden=true':'';
		$out .= "<div $hidden style=\"position:absolute;top:{$top}px;left:{$left}px;\">$text</div>";
    }
	$out .= '</div>';
	return $out;
}

function changeimagesize($img,$w,$h='') {
	$img = preg_replace('/(width|height)\s*=\s*"?\d+"?/','',$img);
	$img = preg_replace('/(width|height):\s*\w+;/','',$img);
	$sizestr = 'width='.Sanitize::onlyInt($w);
	if ($h != '') {
		$sizestr .= ' height='.Sanitize::onlyInt($h);
	}
	$img = str_replace('<img', '<img '.$sizestr, $img);
	return $img;
}

function addimageborder($img, $w=1, $m=0) {
    $style = 'border:'.intval($w).'px solid black;';
    if ($m>0) {
        $style .= 'margin:'.intval($m).'px;';
    }
    if (strpos($img,'style=')!==false) {
        $img = str_replace('style="','style="'.$style, $img);
    } else {
        $img = str_replace('<img ','<img style="'.$style.'" ', $img);
    }
    return $img;
}

function today($str = "F j, Y") {
	return (date($str));
}

function numtoroman($n,$uc=true) {
    $n = intval($n);
	if ($uc) {
		$lookup = array('M'=>1000,'CM'=>900,'D'=>500,'CD'=>400,'C'=>100,'XC'=>90,'L'=>50,'XL'=>40,'X'=>10,'IX'=>9,'V'=>5,'IV'=>4,'I'=>1,'<span style="border-top:1px solid;">SS</span>'=>0.5);
	} else {
		$lookup = array('m'=>1000,'cm'=>900,'d'=>500,'cd'=>400,'c'=>100,'xc'=>90,'l'=>50,'xl'=>40,'x'=>10,'ix'=>9,'v'=>5,'iv'=>4,'i'=>1,'<span style="border-top:1px solid;">ss</span>'=>0.5);
	}
	$roman = '';
	foreach($lookup as $r=>$v) {
		while($n >= $v) {
			$roman .= $r;
			$n -= $v;
		}
	}
	return $roman;
}

function fillarray($v,$n,$s=0) {
	return array_fill($s,$n,$v);
}

function arrayhasduplicates($arr) {
	if (count($arr)==count(array_unique($arr))) {
		return false;
	} else {
		return true;
	}
}

function ifthen($c,$t,$f) {
	return $c?$t:$f;
}

function cases($val, $inputs, $outputs, $default = '', $tol = .0015) {
    if (is_array($val)) {
        echo "First input to cases must be a number or string";
        return '';
    } else if (!is_array($inputs) || !is_array($outputs)) {
        echo "Second and third inputs to cases must be arrays";
        return '';
    } else if (count($inputs) != count($outputs)) {
        echo "Second and third inputs to cases must have same number of elements";
        return '';
    }
    $abstol = false;
    if (strval($tol)[0]=='|') {
		$tol = floatval(substr($tol,1));
        $abstol = true;
	}
    foreach ($inputs as $k=>$x) {
        if (is_numeric($x) && is_numeric($val)) {
            if ($abstol) {
                if (abs($x-$val) < $tol+1E-12) {return $outputs[$k];}
            } else {
                if (abs($x-$val)/(abs($x)+.0001) < $tol+1E-12) {return $outputs[$k];}
            }
        } else if ((string) $x === (string) $val) {
            return $outputs[$k];
        }
    }
    return $default;
}


//adapted from http://www.mindspring.com/~alanh/fracs.html
function decimaltofraction($d,$format="fraction",$maxden = 10000000) {
    if (!is_numeric($d)) {
        echo 'decimaltofraction expects numeric input';
        return $d;
    }
	if (abs(floor($d)-$d)<1e-12) {
		return floor($d);
	}
	if (abs($d)<1e-12) {
		return '0';
    }
    $maxden = min($maxden, 1e16);

	if ($d<0) {
		$sign = '-';
	} else {
		$sign = '';
	}
	$d = abs($d);
	$numerators = array(0, 1);
	$denominators = array(1, 0);

	$d2 = $d;
	$calcD = -1;
	$prevCalcD = -1;
	for ($i = 2; $i < 1000; $i++)  {
        $L2 = floor($d2);
        $newdenom = $L2 * $denominators[$i-1] + $denominators[$i-2];
        if (abs($newdenom)>$maxden) {
            $i--;
            break;
        }
		$numerators[$i] = $L2 * $numerators[$i-1] + $numerators[$i-2];
		//if (Math.abs(numerators[i]) > maxNumerator) return;
		$denominators[$i] = $newdenom;
		
		$calcD = $numerators[$i] / $denominators[$i];
		if ($calcD == $prevCalcD) { break; }

		//appendFractionsOutput(numerators[i], denominators[i]);

		//if ($calcD == $d) { break;}
		if (abs($calcD - $d)<1e-14) { break;}

		$prevCalcD = $calcD;

		$d2 = 1/($d2-$L2);
    }
	if ($i<1000 && abs($numerators[$i]/$denominators[$i] - $d)>1e-12) {
		return $sign.$d;
    }
	if ($format=="mixednumber") {
		$w = floor($numerators[$i]/$denominators[$i]);
		if ($w>0) {
            $out = "{$sign}$w";
			$n = $numerators[$i] - $w*$denominators[$i];
            if (abs($n)>1e-12) {
                $out .= " $n/".$denominators[$i];
            }
			return $out;
		} else {
			return $sign.$numerators[$i].'/'.$denominators[$i];
		}
	} else {
		return $sign.$numerators[$i].'/'.$denominators[$i];
	}
}

function makenumberrequiretimes($arr) {
	if (!is_array($arr)) {
		$arr = listtoarray($arr);
	}
	if (count($arr)==0) {
		return "";
	}
	foreach ($arr as $k=>$num) {
        if (!is_numeric($num)) {
            echo 'inputs to makenumberrequirestimes must be numeric';
            continue;
        }
        $arr[$k] = abs($num);
	}
	$uniq = array_unique($arr);
	$out = array();
	foreach ($uniq as $num) {
		$nummatch = count(array_keys($arr,$num));
		$out[] = "#$num,=$nummatch";
	}
	return implode(',',$out);
}

function evalbasic($str, $doextra = false, $zerofornan = false) {
	global $myrights;
    $str = str_replace(',','',$str);
    $str = preg_replace('/(\d)pi/', '$1*pi', $str);
	$str = str_replace('pi','3.141592653',$str);
	$str = clean($str);
	if (is_numeric($str)) {
		return $str;
	} else if (preg_match('/[^\d+\-\/\*\.\(\)]/',$str)) {
        if ($doextra) {
            $ret = evalnumstr($str);
            if ($zerofornan && !is_nicenumber($ret)) {
                return 0;
            }
            return $ret;
        } else {
		    return $str;
        }
	} else if ($str === '') {
    return 0;
  } else {
		try {
			eval("\$ret = $str;");
		} catch (Throwable $t) {
			if ($myrights>10) {
				echo '<p>Caught error in evaluating '.Sanitize::encodeStringForDisplay($str).' in this question: ';
				echo Sanitize::encodeStringForDisplay($t->getMessage());
				echo '</p>';
			}
            if (!isset($ret) || !is_nicenumber($ret)) {
                return 0;
            }
		}
        if ($zerofornan && !is_nicenumber($ret)) {
            return 0;
        }
		return $ret;
	}
}

function formhoverover($label,$tip) {
	if (function_exists('filter')) {
		$tip = filter($tip);
	} 
	$tip = htmlentities($tip);
	$tip = str_replace('`','&#96;', $tip);
	return '<span role="button" tabindex="0" class="link" data-tip="'.$tip.'" onmouseover="tipshow(this)" onfocus="tipshow(this)" onmouseout="tipout()" onblur="tipout()">'.$label.'</span>';

}

function formpopup($label,$content,$width=600,$height=400,$type='link',$scroll='null',$id='popup',$ref='') {
	global $urlmode;
	$labelSanitized = Sanitize::encodeStringForDisplay($label);
    if (!is_scalar($content)) { echo "invalid content in formpopup"; return ''; }
    if (!is_scalar($label)) { echo "invalid label in formpopup"; return ''; }
    if (is_array($width)) { echo "width should not be array in formpopup"; $width = 600; }

	if ($scroll != null) {
		$scroll = ','.$scroll;
	}
	if ($height=='fit') {
		$height = "'fit'";
	}
	if ($ref!='') {
		if (strpos($ref,'-')!==false) {
			$ref = explode('-',$ref);
			$contentadd = 'Q'.$ref[1].': ';
			$ref = $ref[0];
		} else {
			$contentadd = '';
		}
		if (strpos($content,'watchvid.php')!==false) {
			$cp = explode('?url=',$content);
			$rec = "recclick('extref',$ref,'".$contentadd.trim(htmlentities(urldecode($cp[1])))."');";
		} else {
			$rec = "recclick('extref',$ref,'".$contentadd.trim(htmlentities($content))."');";
		}

	} else {
		$rec = '';
	}
	if (strpos($label,'<img')!==false) {
        return '<button type="button" class="nopad plain" onClick="'.$rec.'popupwindow(\''.$id.'\',\''.Sanitize::encodeStringForJavascript($content).'\','.$width.','.$height.$scroll.')">'.$label.'</button>';
	} else {
		if ($type=='link') {
			return '<a href="#" onClick="'.$rec.'popupwindow(\''.$id.'\',\''.Sanitize::encodeStringForJavascript($content).'\','.$width.','.$height.$scroll.');return false;">'.$labelSanitized.'</a>';
		} else if ($type=='button') {
			if (substr($content,0,31)=='http://www.youtube.com/watch?v=') {
				$content = $GLOBALS['basesiteurl'] . "/assessment/watchvid.php?url=".Sanitize::encodeUrlParam($content);
				$width = 660;
				$height = 525;
			}
            return '<button type="button" onClick="'.$rec.'popupwindow(\''.$id.'\',\''.Sanitize::encodeStringForJavascript($content).'\','.$width.','.$height.$scroll.')">'.$labelSanitized.'</button>';

		}
	}
}

function forminlinebutton($label,$content,$style='button',$outstyle='block') {
    if (!is_scalar($content)) { echo "invalid content in forminlinebutton"; return ''; }
    if (!is_scalar($style)) { echo "invalid style in forminlinebutton"; return ''; }
    if (!is_scalar($label)) { echo "invalid label in forminlinebutton"; return ''; }

	$r = uniqid();
	$label = str_replace('"','',$label);
	$common = 'id="inlinebtn'.$r.'" aria-controls="inlinebtnc'.$r.'" aria-expanded="false" onClick="toggleinlinebtn(\'inlinebtnc'.$r.'\', \'inlinebtn'.$r.'\');return false;"';
    if ($style=='link') {
        $out = '<a href="#" '.$common.'>'.$label.'</a>';
    } else {
		$out = '<button type="button" '.$common.'>'.$label.'</button>';
	}
	if ($outstyle=='inline') {
		$out .= ' <span id="inlinebtnc'.$r.'" style="display:none;" aria-hidden="true">'.$content.'</span>';
	} else {
		$out .= '<div id="inlinebtnc'.$r.'" style="display:none;" aria-hidden="true">'.$content.'</div>';
	}
	return $out;
}

function ineqtointerval($str, $var) {
	if ($str === 'DNE') {
		return $str;
	}
	$str = strtolower($str);
    $var = strtolower($var);
    $str = preg_replace('/(\d)\s*,\s*(?=\d{3}\b)/',"$1", $str);
	if (preg_match('/all\s*real/', $str)) {
		return '(-oo,oo)';
    }
    $outpieces = [];
    $orpts = preg_split('/\s*or\s*/', $str);
    foreach ($orpts as $str) {
        if (count($orpts)==1 && strpos($str, '!=') !== false) {
            // special handling for != 
            $pieces = explode('!=', $str);
            if (count($pieces) != 2 || trim($pieces[0]) != $var) {
                return false;
            }
            return '(-oo,' . $pieces[1] . ')U(' . $pieces[1] . ',oo)';
        }
        $pieces = preg_split('/(<=?|>=?)/', $str, -1, PREG_SPLIT_DELIM_CAPTURE);
        $cnt = count($pieces);
        $pieces = array_map('trim', $pieces);
        if ($cnt != 3 && $cnt != 5) {
            return false; //invalid
        } else if ($cnt == 5 && ($pieces[1][0] != $pieces[3][0] || $pieces[2] != $var)) {
            return false; // mixes > with <
        }
        if ($cnt==3 && $pieces[0]==$var && $pieces[1][0]=='>') {
            $outpieces[] = ($pieces[1]=='>'?'(':'[') . $pieces[2] . ',oo)';
        } else if ($cnt==3 && $pieces[0]==$var && $pieces[1][0]=='<') {
            $outpieces[] = '(-oo,' . $pieces[2] . ($pieces[1]=='<'?')':']');
        } else if ($cnt==3 && $pieces[2]==$var && $pieces[1][0]=='>') {
            $outpieces[] = '(-oo,' . $pieces[0] . ($pieces[1]=='>'?')':']');
        } else if ($cnt==3 && $pieces[2]==$var && $pieces[1][0]=='<') {
            $outpieces[] = ($pieces[1]=='<'?'(':'[') . $pieces[0] . ',oo)';
        } else if ($cnt==5 && $pieces[1][0]=='<') {
            $outpieces[] = ($pieces[1]=='<'?'(':'[') . $pieces[0] . ',' .
                $pieces[4] . ($pieces[3]=='<'?')':']');
        } else if ($cnt==5 && $pieces[1][0]=='>') {
            $outpieces[] = ($pieces[3]=='>'?'(':'[') . $pieces[4] . ',' .
                $pieces[0] . ($pieces[1]=='>'?')':']');
        }
    }
    if (count($outpieces) > 0) {
        return implode('U', $outpieces);
    }
	return false;
}

function intervaltoineq($str,$var) {
	if ($str=='DNE') {
		return 'DNE';
	} else if (trim($str)=='') { 
        return '';
    }
	$arr = explode('U',$str);
	$out = array();
    $mightbeineq = '';
	foreach ($arr as $v) {
		$v = trim($v);
		$sm = $v[0];
		$em = $v[strlen($v)-1];
		$pts = explode(',',substr($v,1,strlen($v)-2));
        if (count($pts) !== 2) {
            echo 'Invalid interval notation';
            return '';
        }
		if ($pts[0]=='-oo') {
			if ($pts[1]=='oo') {
				$out[] = '"all real numbers"';
			} else {
				$out[] = $var . ($em==']'?'le':'lt') . $pts[1];
                if ($em==')') { $mightbeineq = $pts[1]; }
			}
		} else if ($pts[1]=='oo') {
			$out[] = $var . ($sm=='['?'ge':'gt') . $pts[0];
            if ($mightbeineq!=='' && count($arr)==2 && $sm=='(' && $mightbeineq==$pts[0]) {
                return $var . ' != ' . $pts[0];
            }
		} else {
			$out[] = $pts[0] . ($sm=='['?' le ':' lt ') . $var . ($em==']'?' le ':' lt ') . $pts[1];
		}
	}
	return implode(' \\ "or" \\ ',$out);
}

function cleanbytoken($str,$funcs = array()) {
	if (is_array($str)) { return $str;} //avoid errors by just skipping this if called with an array somehow
	$str = str_replace(['`','\\'], '', $str);
	$instr = 0;
	$primeoff = 0;
	while (($p = strpos($str, "'", $primeoff))!==false) {
		if ($instr == 0) {  //if not a match for an earlier quote
			if ($p>0 && (ctype_alpha($str[$p-1]) || $str[$p-1]=='`')) {
				$str[$p] = '`';
			} else {
				$instr = 1-$instr;
			}
		}
		$primeoff = $p+1;
	}
    $str = preg_replace('/&(gt|lt|ge|le|ne);/',' $1 ', $str);
    $finalout = array();
    if (trim($str)=='') {return '';}
    $tokens = cleantokenize(trim($str),$funcs);

    $out = array();
    $lasti = count($tokens)-1;
    $grplasti = -2;
    for ($i=0; $i<=$lasti; $i++) {
        $token = $tokens[$i];
        $lastout = count($out)-1;
        if ($i>0 && $tokens[$i-1][1]==12) {// following a separator
            $lastout = -1;
        }
        if ($grplasti < $i-1) { // find next separator
            $grplasti = $lasti;
            for ($j=$i; $j<=$lasti;$j++) {
                if ($tokens[$j][1]==12) {
                    $grplasti = $j-1;
                    break;
                }
            }
        }

        if ($token[1]==12) { // separator
            if (count($out)>0 && $out[0]=='+') {
                array_shift($out);
            }
            if (count($out)==0 && $i>0) {
                $finalout[] = '0';
            } else {
                /* Disabled for now - causing issues with badly written questions
                // try to strip extraneous parens
                $cout = count($out);
                for ($j=0;$j<$cout;$j++) {
                    if (is_string($out[$j]) && strlen($out[$j])>2 && $out[$j][0]=='(' && $out[$j][strlen($out[$j])-1]==')' && strpos($out[$j],',')===false) {
                        if (($j==0 || $out[$j-1]=='+') &&
                            ($j==$cout-1 || $out[$j+1]=='+' || $out[$j+1]=='-')) {
                                $out[$j] = substr($out[$j],1,-1);
                        }
                    }
                }
                */
                $finalout[] = implode('',$out);
            }
            $finalout[] = $token[0];
            $out = []; //reset
            if ($i==$grplasti) { // if nothing is following last separator, prevent 0 being added
                $out[] = ' ';
            }
        } else if ($token[1]==3 && $token[0]==='0') { //is the number 0 by itself
            $isone = 0;
            if ($lastout>-1) { //if not first character
                if ($out[$lastout] == '^') {
                    $isone = 2;
                    if ($lastout>=2 && ($out[$lastout-2]=='+'|| $out[$lastout-2]=='-'|| $out[$lastout-2]=='pm')) {
                        //4x+x^0 -> 4x+1
                        array_splice($out,-2);
                        $out[] = 1;
                    } else if ($lastout>=2) {
                        $isone = 1;
                        //4x^0->4, 5(x+3)^0 -> 5
                        array_splice($out,-2);
                    } else if ($lastout==1) {
                        //x^0 -> 1
                        $out = array(1);
                    }
                } else if ($out[$lastout] == '_') {
                    $out[] = 0;
                    continue;
                } else {
                    //( )0, + 0, x0
                    while ($lastout>-1 && $out[$lastout]!= '+' && $out[$lastout]!= '-' && $out[$lastout]!= 'pm') {
                        array_pop($out);
                        $lastout--;
                    }
                    if ($lastout>-1) {
                        array_pop($out);
                        $lastout--;
                    }

                }
            }
            if ($i<$grplasti) { //if not last character
                if ($tokens[$i+1][0]=='^') {
                    //0^3
                    $i+=2; //skip over ^ and 3
                } else if ($isone) {
                    if ($tokens[$i+1][0]!= '+' && $tokens[$i+1][0]!= '-' && $tokens[$i+1][0]!= 'pm' && $tokens[$i+1][0]!= '/') {
                        if ($isone==2) {
                            array_pop($out);  //pop the 1 we added since it apperears to be multiplying
                        }
                        if ($tokens[$i+1][0]=='*') {  //x^0*y
                            $i++;
                        }
                    }
                } else {
                    while ($i<$grplasti && $tokens[$i+1][0]!= '+' && $tokens[$i+1][0]!= '-' && $tokens[$i+1][0]!= 'pm') {
                        $i++;
                    }
                }
                if ($lastout==-1 && $i<$grplasti && $tokens[$i+1][0]== '+') {
                    $i++; // skip leading + if we removed 0 from start
                }
            } 
        } else if ($token[1]==3 && $token[0]==='1') {
            $dontuse = false;
            if ($lastout>-1) { //if not first character
                if ($out[$lastout] != '^' && $out[$lastout] != '/' && $out[$lastout]!='+' && $out[$lastout]!='-' && $out[$lastout]!='pm' && $out[$lastout]!=' ' && $out[$lastout]!='_') {
                    //( )1, x1,*1
                    if ($out[$lastout]=='*') { //elim *
                        array_pop($out);
                    }
                    $dontuse = true;
                } else if ($out[$lastout] == '^' || $out[$lastout] == '/' ) {
                    if ($lastout>=1) {
                        //4+x^1 -> 4+x, 4x^1 -> 4x,   x/1 -> x
                        array_pop($out);
                        $dontuse = true;
                        continue;
                    }
                } else if ($out[$lastout]=='_') {
                    $out[] = 1;
                    continue;
                }
            }

            if ($i<$grplasti) { //if not last character
                if ($tokens[$i+1][0]=='^') {
                    //1^3
                    $i+=2; //skip over ^ and 3
                } else if ($tokens[$i+1][0]=='*') {
                    $i++;  //skip over *
                    $dontuse = true;
                } else if ($tokens[$i+1][0]!= '+' && $tokens[$i+1][0]!= '-' && $tokens[$i+1][0]!= 'pm' && $tokens[$i+1][0]!= '/' && !is_numeric($tokens[$i+1][0])) {
                    // 1x, 1(), 1sin
                    if ($lastout<2 || (($out[$lastout-1] != '^' && $out[$lastout-1] != '/') || $out[$lastout] != '-')) { //exclude ^-1 case and /-1 case
                        $dontuse = true;
                    }
                }
            }

            if (!$dontuse) {
                $out[] = 1;
            } else {
                continue;
            }
        } else {
            $out[] = $token[0];
        }
        if ($i<$grplasti && (($token[1]==3 && $tokens[$i+1][1]==3) || ($token[1]==4 && ($tokens[$i+1][1]==4 || $tokens[$i+1][1]==2)))) {
            $out[] = ' ';
        }
    }
    if (count($out)>0 && $out[0]=='+') {
        array_shift($out);
    }

    if (count($out)==0 && $lastout == -1) {
        $finalout[] = '0';
    } else {
        /* Disabled for now - causing issues with badly written questions
        // try to strip extraneous parens
        $cout = count($out);
        for ($i=0;$i<$cout;$i++) {
            if (is_string($out[$i]) && strlen($out[$i])>2 && $out[$i][0]=='(' && $out[$i][strlen($out[$i])-1]==')' && strpos($out[$i],',')===false) {
                if (($i==0 || $out[$i-1]=='+') &&
                    ($i==$cout-1 || $out[$i+1]=='+' || $out[$i+1]=='-')) {
                        $out[$i] = substr($out[$i],1,-1);
                }
            }
        }
        */
        $finalout[] = implode('',$out);
    }
	
	return str_replace('`',"'", implode(' ',$finalout));
}


function cleantokenize($str,$funcs) {
	/*
	2: function name
	3: number
	4: variable
	5: curlys 
	6: string
	7: line break?
	8: parens
	11: array index brackets
	12: separator
	*/
    $str = (string) $str;
	$knownfuncs = array_merge($funcs,array("sin","cos","sec","csc","tan","csc","cot","sinh","cosh","sech","csch","tanh","coth","arcsin","arccos","arcsec","arccsc","arctan","arccot","arcsinh","arccosh","arctanh","sqrt","ceil","floor","root","log","ln","abs","max","min"));

	$lookfor = array("e","pi");
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
		$c = $str[$i];
        $eatenwhite = 0;
        if ($c == ',') {
            $intype = 12;
            $out .= $c;
            $i++;
        } else if ($c == '<' || $c == '>' || $c=='=') {
            $intype = 12;
            $out .= $c;
            $i++;
            if ($i<$len && $str[$i]=='=') {
                $out .= $str[$i];
                $i++;
            }
        } else if ($c>="a" && $c<="z" || $c>="A" && $c<="Z") {
			//is a string or function name

			$intype = 2; //string like function name
			do {
				$out .= $c;
				$i++;
				if ($i==$len) {break;}
				$c = $str[$i];
			} while ($c>="a" && $c<="z" || $c>="A" && $c<="Z"); // took out : || $c>='0' && $c<='9'  don't need sin3 type function names for cleaning
			//check if it's a special word
			if ($out=='e') {
				$intype = 3;
			} else if ($out=='pi') {
				$intype = 3;
			} else if ($out=='gt' || $out=='lt' || $out=='ge' || $out=='le' || $out=='geq' || $out=='leq' || $out=='ne' || $out=='or') {
                $intype = 12; // separator
            } else if ($out=='pm') {
                $intype = 0;
            } else {
				//eat whitespace
				while ($c==' ') {
					$i++;
					$c = $str[$i];
					$eatenwhite++;
				}
				//if known function at end, strip off function
				if ($c=='(' && !in_array($out,$knownfuncs)) {// moved to mathphppre-> || ($c=='^' && (substr($str,$i+1,2)=='-1' || substr($str,$i+1,4)=='(-1)'))) {
					$outlen = strlen($out);
					$outend = '';
					for ($j=1; $j<$outlen-1; $j++) {
						$outend = substr($out,$j);
						if (in_array($outend,$knownfuncs)) {
							$i = $i - $outlen + $j;
							$c = $str[$i];
							$out = substr($out,0,$j);
							break;
						}
					}

				}

				//if there's a ( then it's a function if it's in our list
				if ($c=='(' && $out!='e' && $out!='pi' && in_array($out,$knownfuncs)) {
					//connect upcoming parens to function
					$connecttolast = 2;
				} else {
					//is it a known function?
					if (in_array($out,$knownfuncs)) {
						$intype = 6;
					} else {
						//if not, assume it's a variable
						$intype = 4;
					}
				}
			}
		} else if (($c>='0' && $c<='9') || ($c=='.'  && (isset($str[$i+1]) && $str[$i+1]>='0' && $str[$i+1]<='9')) ) { //is num
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
				$c= $str[$i];
				if (($c>='0' && $c<='9') || ($c=='.' && (!isset($str[$i+1]) || $str[$i+1]!='.') && $lastc!='.')) {
					//is still num
				} else if (($c=='e' || $c=='E') && isset($str[$i+1])) {
					//might be scientific notation:  5e6 or 3e-6
					$d = $str[$i+1];
					if ($d>='0' && $d<='9') {
						$out .= $c;
						$i++;
						if ($i==$len) {break;}
						$c= $str[$i];
					} else if (($d=='-'||$d=='+') && (isset($str[$i+2]) && $str[$i+2]>='0' && $str[$i+2]<='9')) {
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
				$intype = 8; //parens
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
                $d = $str[$j];
				if ($inq) {  //if inquote, leave if same marker (not escaped)
					if ($d==$qtype && $str[$j-1]!='\\') {
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
                            $inside = cleanbytoken(substr($str,$i+1,$j-$i-1), $funcs);
							if ($inside=='error') {
								//was an error, return error token
								return array(array('',9));
							}
							//if curly, make sure we have a ;, unless preceeded by a $ which
                            //would be a variable variable
							//if ($rightb=='}' && $lastsym[0]!='$') {
                            //	$out .= $leftb.$inside.';'.$rightb;
                            // removed 10/15/20 ^^ why the semicolon??
                            if ($rightb=='}') {
                                $out .= $leftb.' '.$inside.$rightb;
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
				echo "unmatched parens/brackets - likely will cause an error";
			} else if ($i<$len) {
				$c = $str[$i];
			}
		} else if ($c=='"' || $c=="'") { //string
			$intype = 6;
			$qtype = $c;
			do {
				$out .= $c;
				$i++;
				if ($i==$len) {break;}
				$lastc = $c;
				$c = $str[$i];
			} while (!($c==$qtype && $lastc!='\\'));
			$out .= $c;

            $i++;
            if ($i<$len) {
                $c = $str[$i];
            }
		}  else {
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
        if ($intype == 12) { // remove whitespace before and after separator
            while ($i<$len && $str[$i]==' ') {
                $i++;
            }
            while (count($syms)>0 && $syms[count($syms)-1][0]==' ') {
                array_pop($syms);
            }
            $connecttolast = 0;
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

function is_nicenumber($x) {
    return (is_numeric($x) && is_finite($x));
}

function comparenumbers($a,$b,$tol='.001') {
	if (strval($tol)[0]=='|') {
		$abstolerance = floatval(substr($tol,1));
	}
	if (!is_numeric($a)) {
		$a = evalMathParser($a);
	}
	if (!is_numeric($b)) {
		$b = evalMathParser($b);
	}
	//echo "comparing $a and $b ";
	if (isset($abstolerance)) {
		if (abs($a-$b) < $abstolerance+1E-12) {return true;}
	} else {
		if (abs($a-$b)/(abs($a)+.0001) < $tol+1E-12) {return true;}
	}
	return false;
}

function comparefunctions($a,$b,$vars='x',$tol='.001',$domain='-10,10') {
	if ($a=='' || $b=='') { return false;}
	//echo "comparing $a and $b";
	if (strval($tol)[0]=='|') {
		$abstolerance = floatval(substr($tol,1));
	}
	$type = "expression";
	if (strpos($a, '=')!==false && strpos($b, '=')!==false) {
		$type = "equation";
	}

    list($variables, $tps, $flist) = numfuncGenerateTestpoints($vars, $domain);

	$vlist = implode(",",$variables);
    $a = numfuncPrepForEval($a, $variables);
    $b = numfuncPrepForEval($b, $variables);

	if ($type=='equation') {
		if (substr_count($a, '=')!=1) {return false;}
		$a = preg_replace('/(.*)=(.*)/','$1-($2)',$a);
		if (substr_count($b, '=')!=1) {return false;}
		$b = preg_replace('/(.*)=(.*)/','$1-($2)',$b);
	}

	$afunc = makeMathFunction($a, $vlist, [], $flist, true);
	$bfunc= makeMathFunction($b, $vlist, [], $flist, true);
	if ($afunc === false || $bfunc === false) {
		if (!empty($GLOBALS['inQuestionTesting'])) {
			echo "<p>Debug info: one function failed to compile.</p>";
		}
		return false;
	}

	$cntnana = 0;
	$cntnanb = 0;
    $cntbothzero = 0;
    $cntzero = 0;
    $diffnan = 0;
	$correct = true;
	$ratios = array();
	$evalerr = false;
	for ($i = 0; $i < 20; $i++) {
		$varvals = array();
		for($j=0; $j < count($variables); $j++) {
			$varvals[$variables[$j]] = $tps[$i][$j];
		}
		$ansa = $afunc($varvals);
		$ansb = $bfunc($varvals);

		if ($ansa===false || $ansb===false) {
			$evalerr = true;
			break;
		}
		//echo "real: $ansa, my: $ansb <br/>";
		if (isNaN($ansa)) {
            $cntnana++; 
            if (isNaN($ansb)) {
                $cntnanb++;
            } else {
                $diffnan++;
            }
            continue;
        } //avoid NaN problems
		if (isNaN($ansb)) {
            $cntnanb++;
            $diffnan++;
            continue;
        }

		if ($type=='equation') {
			if (abs($ansa)>.000001 && is_numeric($ansb)) {
				$ratios[] = $ansb/$ansa;
				if (abs($ansb)<=.00000001 && $ansa!=0) {
					$cntzero++;
				}
			} else if (abs($ansa)<=.000001 && is_numeric($ansb) && abs($ansb)<=.00000001) {
				$cntbothzero++;
			}
		} else {
			if (isset($abstolerance)) {
				if (abs($ansa-$ansb) > $abstolerance-1E-12) {$correct = false; break;}
			} else {
				if ((abs($ansa-$ansb)/(abs($ansa)+.0001) > $tol-1E-12)) {$correct = false; break;}
			}
		}
	}
	//echo "$i, $ansa, $ansb, $cntnana, $cntnanb";
	if ($cntnana==20 || $cntnanb==20) {
		if (isset($GLOBALS['teacherid'])) {
			echo "<p>Debug info: one function evaled to Not-a-number at all test points.  Check \$domain</p>";
			echo "<p>Funcs: $a and $b</p>";
		}
		return false;
	} else if ($evalerr) {
		if (isset($GLOBALS['teacherid'])) {
			echo "<p>Debug info: one function was invalid.</p>";
			echo "<p>Funcs: $a and $b</p>";
		}
		return false;
	} else if ($i<20) { //broke out early
		return false;
	}

	if ($diffnan>1) {
		return false;
	}
	if ($type=="equation") {
		if ($cntbothzero>18) {
			$correct = true;
		} else if (count($ratios)>0) {
			if (count($ratios)==$cntzero) {
				$correct = false;
			} else {
				$meanratio = array_sum($ratios)/count($ratios);
				for ($i=0; $i<count($ratios); $i++) {
					if (isset($abstolerance)) {
						if (abs($ratios[$i]-$meanratio) > $abstolerance-1E-12) {$correct = false; break;}
					} else {
						if ((abs($ratios[$i]-$meanratio)/(abs($meanratio)+.0001) > $tol-1E-12)) {$correct = false; break;}
					}
				}
			}
		} else {
			$correct = false;
		}
	}
	if ($correct) {
		return true;
	} else {
		return false;
	}
}

function getnumbervalue($a) {
	$a = str_replace(',','',$a);
	if (is_numeric($a)) {
		return $a*1;
	} else {
		$a = evalMathParser($a);
		return $a;
	}
}

function htmldisp($str,$var='') {
	$str = preg_replace('/\^(\w+)/','#@#$1#@%',$str);
	$str = preg_replace('/_(\w+)/','#$#$1#$%',$str);
	if ($var=='') {
		$str = preg_replace('/([a-zA-Z]+)/','<i>$1</i>',$str);
	} else {
		$var = str_replace(',','|',$var);
		$str = preg_replace("/($var)/",'<i>$1</i>',$str);
	}
	$str = str_replace(array('#@#','#@%','#$#','#$%'),array('<sup>','</sup>','<sub>','</sub>'),$str);
	return $str;
}

function stringtopolyterms($str) {
	$out = array();
	$str = str_replace(' ','',$str);
    if ($str=='') { return []; }
	$arr = preg_split('/(?<!\^)([-+])/',$str,-1,PREG_SPLIT_DELIM_CAPTURE);
	if ($arr[0]=='') {  //string started -3x or something; skip 0 index
		$out[] = (($arr[1]=='-')?'-':'').$arr[2];
		$start = 3;
	} else {  //string started 3x or something
		$out[] = $arr[0];
		$start = 1;
	}
	for ($i=$start;$i<count($arr);$i+=2) {
		$out[] = (($arr[$i]=='-')?'-':'').$arr[$i+1];
	}
	return $out;
}

/*
 abstracts some of the common numfunc processing
 @param $variables string (comma separated) 
 @param $domain   string
 return
 array of
   $variables  array
   $testpoints  2d array
   $flist string (function variables, comma separated) 
*/
function numfuncGenerateTestpoints($variables,$domain='') {
    $variables = array_values(array_filter(array_map('trim', explode(",", $variables)), 'strlen'));
    $ofunc = array();
    for ($i = 0; $i < count($variables); $i++) {
        //find f() function variables
        if (strpos($variables[$i],'()')!==false) {
            $ofunc[] = substr($variables[$i],0,strpos($variables[$i],'('));
            $variables[$i] = substr($variables[$i],0,strpos($variables[$i],'('));
        }
    }
    if (!empty($domain)) {
        $fromto = array_map('trim', explode(',', $domain));
    } else {
        $fromto = array(-10, 10);
    }

    $domaingroups = array();
    $i=0;
    $haderr = false;
    while ($i<count($fromto)) {
        if (!isset($fromto[$i+1])) {
            if (!$haderr) {
                echo "domain values must be include min,max";
                $haderr = true;
            }
            $fromto[$i] = -10;
            $fromto[$i+1] = 10;
        }
        if (!is_numeric($fromto[$i])) {
            $fromto[$i] = evalbasic($fromto[$i], true);
            if (!is_numeric($fromto[$i])) {
                if (!$haderr) {
                    echo "domain values must be numbers or elementary calculations";
                    $haderr = true;
                }
                $fromto[$i] = -10;
            }
        }
        if (!is_numeric($fromto[$i+1])) {
            $fromto[$i+1] = evalbasic($fromto[$i+1], true);
            if (!is_numeric($fromto[$i+1])) {
                if (!$haderr) {
                    echo "domain values must be numbers or elementary calculations";
                    $haderr = true;
                }
                $fromto[$i+1] = 10;
            }
        }
        if (isset($fromto[$i+2]) && ($fromto[$i+2]=='integers' || $fromto[$i+2]=='integer')) {
            $domaingroups[] = array($fromto[$i], $fromto[$i+1], true);
            $i += 3;
        } else if (isset($fromto[$i+1])) {
            $domaingroups[] = array($fromto[$i], $fromto[$i+1], false);
            $i += 2;
        } else {
            break;
        }
    }
    uasort($variables,'lensort');
    $newdomain = array();
    $restrictvartoint = array();
    foreach($variables as $i=>$v) {
        if (isset($domaingroups[$i])) {
            $touse = $i;
        } else {
            $touse = 0;
        }
        $newdomain[] = $domaingroups[$touse][0];
        $newdomain[] = $domaingroups[$touse][1];
        $restrictvartoint[] = $domaingroups[$touse][2];
    }
    $fromto = $newdomain;
    $variables = array_values($variables);

    $flist = '';
    if (count($ofunc)>0) {
        usort($ofunc,'lensort');
        $flist = implode(",",$ofunc);
    }

    $tps = [];
    for($j=0; $j < count($variables); $j++) {
        if ($fromto[2*$j+1]==$fromto[2*$j]) {
            for ($i = 0; $i < 20; $i++) {
                $tps[$i][$j] = $fromto[2*$j];
            } 
        } else if ($restrictvartoint[$j]) {
            if ($fromto[2*$j+1]-$fromto[2*$j] > 200) {
                for ($i = 0; $i < 20; $i++) {
                    $tps[$i][$j] = rand($fromto[2*$j],$fromto[2*$j+1]);
                }
            } else {
                $allbetween = range($fromto[2*$j],$fromto[2*$j+1]);
                shuffle($allbetween);
                $n = count($allbetween);
                for ($i = 0; $i < 20; $i++) {
                    $tps[$i][$j] = $allbetween[$i%$n];
                }
            }
        } else {
            $dx = ($fromto[2*$j+1]-$fromto[2*$j])/20;
            for ($i = 0; $i < 20; $i++) {
                $tps[$i][$j] = $fromto[2*$j] + $dx*$i + $dx*rand(1,499)/500.0;
            }
        }
    }

    return [$variables, $tps, $flist];
}

/* do any necessary rewrite of expression (not including equation rewrite)
   before eval
   @param $expr  string  the expression to rewrite
   @param $variables array  of variables
   return:
   string of rewritten expression
*/
function numfuncPrepForEval($expr, $variables) {
    $expr = preg_replace('/(\d)\s*,\s*(?=\d{3}(\D|\b))/','$1',$expr);

    for ($i = 0; $i < count($variables); $i++) {
        if ($variables[$i]=='lambda') { //correct lamda/lambda
            $expr = str_replace('lamda', 'lambda', $expr);
        }
        // front end will submit p_(left) rather than p_left; strip parens
        if (preg_match('/^(\w+)_(\w+)$/', $variables[$i], $m)) {
            $expr = preg_replace('/'.$m[1].'_\('.$m[2].'\)/', $m[0], $expr);
        }
    }

    return $expr;
}


function getfeedbackbasic($correct,$wrong,$thisq,$partn=null) {
	global $rawscores,$imasroot,$staticroot;
	if (isset($GLOBALS['testsettings']['testtype']) && ($GLOBALS['testsettings']['testtype']=='NoScores' || $GLOBALS['testsettings']['testtype']=='EndScore')) {
		return '';
	}
	if (isset($GLOBALS['assessUIver']) && $GLOBALS['assessUIver'] > 1) {
		$val = $GLOBALS['assess2-curq-iscorrect'] ?? -1;
        if (is_array($partn) && is_array($val)) {
            $res = 1;
            foreach ($partn as $i) {
                if (!isset($val[$i])) {
                    $res = -1;
                    break;
                } else if ($val[$i] < $res) {
                    $res = $val[$i];
                }
            }
        } else if ($partn !== null && is_array($val)) {
            if (isset($val[$partn])) {
			    $res = $val[$partn];
            } else {
                $res = -1;
            }
		} else {
			$res = $val;
		}
		if ($res > 0 && $res < 1) {
			$res = 0;
		}
	} else if (isset($rawscores[$thisq-1])) {
		$qn = $thisq-1;
		if (strpos($rawscores[$qn],'~')===false) {
			$res = ($rawscores[$qn]<0)?-1:(($rawscores[$qn]==1)?1:0);
		} else {
			$sp = explode('~',$rawscores[$qn]);
			if ($partn===null) {
				$res = 1;
				for ($j=0;$j<count($sp);$j++) {
					if ($sp[$j]!=1) {
						$res=0;
						break;
					}
				}
			} else {
				$res = ($sp[$partn]==1)?1:0;
			}
		}
	} else {
        return '';
    }
	if ($res==-1) {
		return '';
	} else if ($res==1) {
		return '<div class="feedbackwrap correct"><img src="'.$staticroot.'/img/gchk.gif" alt="Correct"/> '.$correct.'</div>';
	} else if ($res==0) {
		return '<div class="feedbackwrap incorrect"><img src="'.$staticroot.'/img/redx.gif" alt="Incorrect"/> '.$wrong.'</div>';
	}
}

function getfeedbacktxt($stu,$fbtxt,$ans) {
	global $imasroot,$staticroot;
	if (isset($GLOBALS['testsettings']['testtype']) && ($GLOBALS['testsettings']['testtype']=='NoScores' || $GLOBALS['testsettings']['testtype']=='EndScore')) {
		return '';
	}
	if ($stu===null || !is_scalar($stu) || !is_scalar($ans)) {
		return " ";
	} else if ($stu==='NA') {
		return '<div class="feedbackwrap"><img src="'.$staticroot.'/img/redx.gif" alt="Incorrect"/> ' . _("No answer selected. Try again.") . '</div>';
    } else {
        $anss = explode(' or ', $ans);
        foreach ($anss as $ans) {
            if ($stu==$ans) {
                $out = '<div class="feedbackwrap correct"><img src="'.$staticroot.'/img/gchk.gif" alt="Correct"/> ';
                if (isset($fbtxt[$stu])) {
                    $out .= $fbtxt[$stu];
                }
                return $out .= '</div>';
            } 
        }
        $out = '<div class="feedbackwrap incorrect"><img src="'.$staticroot.'/img/redx.gif" alt="Incorrect"/> ';
        if (isset($fbtxt[$stu])) {
            $out .= $fbtxt[$stu];
        }
        return $out .= '</div>';
    } 
}

function getfeedbacktxtessay($stu,$fbtxt) {
	if (isset($GLOBALS['testsettings']['testtype']) && ($GLOBALS['testsettings']['testtype']=='NoScores' || $GLOBALS['testsettings']['testtype']=='EndScore')) {
		return '';
	}
	if ($stu===null || !is_scalar($stu) || trim($stu)=='') {
		return '';
	} else {
		return '<div class="feedbackwrap correct">'.$fbtxt.'</div>';
	}
}

function getfeedbacktxtnumber($stu, $partial, $fbtxt, $deffb='Incorrect', $tol=.001) {
	global $imasroot,$staticroot;
	if (isset($GLOBALS['testsettings']['testtype']) && ($GLOBALS['testsettings']['testtype']=='NoScores' || $GLOBALS['testsettings']['testtype']=='EndScore')) {
		return '';
    }

	if ($stu===null || !is_scalar($stu)) {
		return " ";
	} else {
        $stu = trim($stu);
        // handle DNE,oo,+-oo
        if (strtoupper($stu)==='DNE' || $stu==='oo' || $stu==='-oo' || $stu==='+oo') {
            if ($stu=='+oo') {
                $stu = 'oo';
            }
            for ($i=0;$i<count($partial);$i+=2) {
                if ($partial[$i]==='+oo') {
                    $partial[$i]='oo';
                }
                if ($stu===$partial[$i]) {
                    if ($partial[$i+1]<1) {
                        return '<div class="feedbackwrap incorrect"><img src="'.$staticroot.'/img/redx.gif" alt="Incorrect"/> '.$fbtxt[$i/2].'</div>';
                    } else {
                        return '<div class="feedbackwrap correct"><img src="'.$staticroot.'/img/gchk.gif" alt="Correct"/> '.$fbtxt[$i/2].'</div>';
                    }
                }
            }
            return '<div class="feedbackwrap incorrect"><img src="'.$staticroot.'/img/redx.gif" alt="Incorrect"/> '.$deffb.'</div>';
        }
		$stu = preg_replace('/[^\-\d\.eE]/','',$stu);
    }
    
    if (!is_numeric($stu)) {
		return '<div class="feedbackwrap incorrect"><img src="'.$staticroot.'/img/redx.gif" alt="Incorrect"/> ' . _("This answer does not appear to be a valid number.") . '</div>';
	} else {
		if (strval($tol)[0]=='|') {
			$abstol = true;
			$tol = substr($tol,1);
		} else {
			$abstol =false;
		}
		$match = -1;
		if (!is_array($partial)) { $partial = listtoarray($partial);}
		for ($i=0;$i<count($partial);$i+=2) {
            if ($partial[$i]==='DNE' || $partial[$i]==='oo' || $partial[$i]==='-oo' || $partial[$i]==='+oo') {
                continue;
            }
			if (!is_numeric($partial[$i])) {
				$partial[$i] = evalMathParser($partial[$i]);
			}
            $eps = (($partial[$i]==0||abs($partial[$i])>1)?1E-12:(abs($partial[$i])*1E-12));
			if ($abstol) {
				if (abs($stu-$partial[$i]) < $tol + $eps) { $match = $i; break;}
			} else {
				if (abs($stu - $partial[$i])/(abs($partial[$i])+$eps) < $tol+ 1E-12) {$match = $i; break;}
			}
		}
		if ($match>-1 && isset($fbtxt[$match/2])) {
			if ($partial[$i+1]<1) {
				return '<div class="feedbackwrap incorrect"><img src="'.$staticroot.'/img/redx.gif" alt="Incorrect"/> '.$fbtxt[$match/2].'</div>';
			} else {
				return '<div class="feedbackwrap correct"><img src="'.$staticroot.'/img/gchk.gif" alt="Correct"/> '.$fbtxt[$match/2].'</div>';
			}
		} else {
			return '<div class="feedbackwrap incorrect"><img src="'.$staticroot.'/img/redx.gif" alt="Incorrect"/> '.$deffb.'</div>';
		}
	}
}

function getfeedbacktxtcalculated($stu, $stunum, $partial, $fbtxt, $deffb='Incorrect', $answerformat = '', $requiretimes = '', $tol=.001) {
	global $imasroot,$staticroot;
	if (isset($GLOBALS['testsettings']['testtype']) && ($GLOBALS['testsettings']['testtype']=='NoScores' || $GLOBALS['testsettings']['testtype']=='EndScore')) {
		return '';
	}
	if ($stu===null || !is_numeric($stunum)) {
		return " ";
	} else {
		if (strval($tol)[0]=='|') {
			$abstol = true;
			$tol = substr($tol,1);
		} else {
			$abstol =false;
		}
		$match = -1;
		if (!is_array($partial)) { $partial = listtoarray($partial);}
		for ($i=0;$i<count($partial);$i+=2) {
			$idx = $i/2;
			if (is_array($requiretimes)) {
				if ($requiretimes[$idx]!='') {
					if (checkreqtimes(str_replace(',','',$stu),$requiretimes[$idx])==0) {
						$rightanswrongformat = $i;
						continue;
					}
				}
			} else if ($requiretimes!='') {
				if (checkreqtimes(str_replace(',','',$stu),$requiretimes)==0) {
					$rightanswrongformat = $i;
					continue;
				}
			}
			if (is_array($answerformat)) {
				if ($answerformat[$idx]!='') {
					if (checkanswerformat($stu,$answerformat[$idx])==0) {
						$rightanswrongformat = $i;
						continue;
					}
				}
			} else if ($answerformat!='') {
				if (checkanswerformat($stu,$answerformat)==0) {
					$rightanswrongformat = $i;
					continue;
				}
			}
			if (!is_numeric($partial[$i])) {
				$partial[$i] = evalMathParser($partial[$i]);
			}
            $eps = (($partial[$i]==0||abs($partial[$i])>1)?1E-12:(abs($partial[$i])*1E-12));
			if ($abstol) {
				if (abs($stunum-$partial[$i]) < $tol + $eps) { $match = $i; break;}
			} else {
				if (abs($stunum - $partial[$i])/(abs($partial[$i])+$eps) < $tol+ 1E-12) {$match = $i; break;}
			}
		}
		if ($match>-1) {
			if ($partial[$i+1]<1) {
				return '<div class="feedbackwrap incorrect"><img src="'.$staticroot.'/img/redx.gif" alt="Incorrect"/> '.$fbtxt[$match/2].'</div>';
			} else {
				return '<div class="feedbackwrap correct"><img src="'.$staticroot.'/img/gchk.gif" alt="Correct"/> '.$fbtxt[$match/2].'</div>';
			}
		} else {
			return '<div class="feedbackwrap incorrect"><img src="'.$staticroot.'/img/redx.gif" alt="Incorrect"/> '.$deffb.'</div>';
		}
	}
}

//$partial = array(answer,partialcreditval,answer,partialcreditval,...)
function getfeedbacktxtnumfunc($stu, $partial, $fbtxt, $deffb='Incorrect', $vars='x', $requiretimes = '', $tol='.001',$domain='-10,10') {
	global $imasroot,$staticroot;
	if (isset($GLOBALS['testsettings']['testtype']) && ($GLOBALS['testsettings']['testtype']=='NoScores' || $GLOBALS['testsettings']['testtype']=='EndScore')) {
		return '';
	}
	if ($stu===null || trim($stu)==='') {
		return " ";
	} else {
		if (strval($tol)[0]=='|') {
			$abstol = true;
			$tol = substr($tol,1);
		} else {
			$abstol =false;
		}
		$type = "expression";
		if (strpos($stu, '=')!==false) {
			$type = "equation";
		}
		$stuorig = $stu;
		$stu = str_replace(array('[',']'),array('(',')'), $stu);
		if ($type=='equation') {
			$stu = preg_replace('/(.*)=(.*)/','$1-($2)',$stu);
		}

        list($variables, $tps, $flist) = numfuncGenerateTestpoints($vars, $domain);
        $numpts = count($tps);
		$vlist = implode(",",$variables);
        $stu = numfuncPrepForEval($stu, $variables);

		$origstu = $stu;
		$stufunc = makeMathFunction(makepretty($stu), $vlist, [], $flist, true);
		if ($stufunc===false) {
			return '<div class="feedbackwrap incorrect"><img src="'.$staticroot.'/img/redx.gif" alt="Incorrect"/> '.$deffb.'</div>';
		}

		$stupts = array();
		$cntnana = 0;
		$correct = true;
		for ($i = 0; $i < $numpts; $i++) {
			$varvals = array();
			for($j=0; $j < count($variables); $j++) {
				$varvals[$variables[$j]] = $tps[$i][$j];
			}
			$stupts[$i] = $stufunc($varvals);
			if (isNaN($stupts[$i])) {$cntnana++;}
			if ($stupts[$i]===false) {$correct = false; break;}
		}
		if ($cntnana==$numpts || !$correct) { //evald to NAN at all points
			return '<div class="feedbackwrap incorrect"><img src="'.$staticroot.'/img/redx.gif" alt="Incorrect"/> '.$deffb.'</div>';
		}

		$match = -1;
		if (!is_array($partial)) { $partial = listtoarray($partial);}
		for ($k=0;$k<count($partial);$k+=2) {
			$correct = true;
			$b =  numfuncPrepForEval($partial[$k], $variables);
			if ($type=='equation') {
				if (substr_count($b, '=')!=1) {continue;}
				$b = preg_replace('/(.*)=(.*)/','$1-($2)',$b);
			} else if (strpos($b, '=')!==false) {continue;}
			$origb = $b;
			$bfunc = makeMathFunction(makepretty($b), $vlist, [], $flist, true);
			if ($bfunc === false) {
				//parse error - skip it
				continue;
			}
			$cntnanb = 0;
            $cntbothzero = 0;
            $cntzero = 0;
            $diffnan = 0;
			$ratios = array();
			for ($i = 0; $i < $numpts; $i++) {
				$varvals = array();
				for($j=0; $j < count($variables); $j++) {
					$varvals[$variables[$j]] = $tps[$i][$j];
				}
				$ansb = $bfunc($varvals);

				//echo "real: $ansa, my: $ansb <br/>";
				if (isNaN($ansb)) {
                    $cntnanb++; 
                    if (!isNaN($stupts[$i])) { $diffnan++; }
                    continue;
                } else if (isNaN($stupts[$i])) {
                    $diffnan++;
                }
				if (isNaN($stupts[$i])) {continue;} //avoid NaN problems

				if ($type=='equation') {
					if (abs($stupts[$i])>.000001 && is_numeric($ansb)) {
						$ratios[] = $ansb/$stupts[$i];
						if (abs($ansb)<=.00000001 && $stupts[$i]!=0) {
							$cntzero++;
						}
					} else if (abs($stupts[$i])<=.000001 && is_numeric($ansb) && abs($ansb)<=.00000001) {
						$cntbothzero++;
					}
				} else {
					if ($abstol) {
						if (abs($stupts[$i]-$ansb) > $tol-1E-12) {$correct = false; break;}
					} else {
						if ((abs($stupts[$i]-$ansb)/(abs($stupts[$i])+.0001) > $tol-1E-12)) {$correct = false; break;}
					}
				}
			}
			//echo "$i, $ansa, $ansb, $cntnana, $cntnanb";
			if ($cntnanb==20) {
				continue;
			} else if ($i<20) {
				continue;
			}
			if ($diffnan>1) {
				continue;
			}
			if ($type=="equation") {
				if ($cntbothzero>$numpts-2) {
					$match = $k; break;
				} else if (count($ratios)>0) {
					if (count($ratios)==$cntzero) {
						continue;
					} else {
						$meanratio = array_sum($ratios)/count($ratios);
						for ($i=0; $i<count($ratios); $i++) {
							if ($abstol) {
								if (abs($ratios[$i]-$meanratio) > $tol-1E-12) {continue 2;}
							} else {
								if ((abs($ratios[$i]-$meanratio)/(abs($meanratio)+.0001) > $tol-1E-12)) {continue 2;}
							}
						}
					}
				} else {
					continue;
				}
			}
			if ($correct) {
				if (is_array($requiretimes)) {
					if ($requiretimes[$k/2]!='') {
						if (checkreqtimes(str_replace(',','',$stuorig),$requiretimes[$k/2])==0) {
							$rightanswrongformat = $k;
							continue;
						}
					}
				} else if ($requiretimes!='') {
					if (checkreqtimes(str_replace(',','',$stuorig),$requiretimes)==0) {
						$rightanswrongformat = $k;
						continue;
					}
				}
				$match = $k; break;
			} else {
				continue;
			}

		}
		//WHAT to do with right answer, wrong format??
		if ($match>-1) {
			if ($partial[$match+1]<1) {
				return '<div class="feedbackwrap incorrect"><img src="'.$staticroot.'/img/redx.gif" alt="Incorrect"/> '.$fbtxt[$match/2].'</div>';
			} else {
				return '<div class="feedbackwrap correct"><img src="'.$staticroot.'/img/gchk.gif" alt="Correct"/> '.$fbtxt[$match/2].'</div>';
			}
		} else {
			return '<div class="feedbackwrap incorrect"><img src="'.$staticroot.'/img/redx.gif" alt="Incorrect"/> '.$deffb.'</div>';
		}
	}
}

function parsedrawgrid($str, $snaptogrid) {
    $p = array_map('trim',explode(',', $str));
    $xmin = isset($p[0]) ? $p[0] : -5;
    if (is_string($xmin)) {
        $pts = explode(':', $xmin);
        $xmin = $pts[count($pts)-1];
    }
    $xmax = isset($p[1]) ? $p[1] : 5;
    $ymin = isset($p[2]) ? $p[2] : -5;
    if (is_string($ymin)) {
        $pts = explode(':', $ymin);
        $ymin = $pts[count($pts)-1];
    }
    $ymax = isset($p[3]) ? $p[3] : 5;
    $w = isset($p[6]) ? $p[6] : 300;
    $h = isset($p[7]) ? $p[7] : 300;
    if ($snaptogrid !== null) { // snaptogrid given
        list($neww,$newh) = getsnapwidthheight($xmin,$xmax,$ymin,$ymax,$w,$h,$snaptogrid);
        if (abs($neww - $w)/$w<.1) {
            $w = $neww;
        }
        if (abs($newh- $h)/$h<.1) {
            $h = $newh;
        }
    }
    return [$xmin, $xmax, $ymin, $ymax, $w, $h];
}

function sorttwopointdata($data, $type='') {
    if ($type=='line' || $type=='lineseg' || $type=='cos' || $type=='exp' || $type=='log') {
        foreach ($data as $k=>$v) {
            if ($v[2] < $v[0]) {
                $data[$k] = [$v[2],$v[3],$v[0],$v[1]];
            }
        }
    }
    usort($data, function($a,$b) {
        if ($a[0] == $b[0]) { 
            return ($a[1] < $b[1]) ? -1 : 1;
        }
        return ($a[0] < $b[0]) ? -1 : 1;
    });
    return $data;
}

function gettwopointlinedata($str,$xmin=null,$xmax=null,$ymin=null,$ymax=null,$w=null,$h=null) {
	return gettwopointdata($str,'line',$xmin,$xmax,$ymin,$ymax,$w,$h);
}
function gettwopointdata($str,$type,$xmin=null,$xmax=null,$ymin=null,$ymax=null,$w=null,$h=null) {
    if (trim($str)=='') { return []; }
    if (is_string($xmin) && strpos($xmin,',')!==false) {
        list($xmin,$xmax,$ymin,$ymax,$w,$h) = parsedrawgrid($xmin,$xmax);
    } else {
        if ($xmin === null) { $xmin = -5;}
        if ($xmax === null) { $xmax = 5;}
        if ($ymin === null) { $ymin = -5;}
        if ($ymax === null) { $ymax = 5;}
        if ($w === null) { $w = 300;}
        if ($h === null) { $h = 300;}
    }
	if ($type=='line') {
		$code = 5;
	} else if ($type=='lineseg') {
		$code = 5.3;
	} else if ($type=='ray') {
		$code = 5.2;
	} else if ($type=='parab') {
		$code = 6;
	} else if ($type=='horizparab') {
		$code = 6.1;
	} else if ($type=='cubic') {
		$code = 6.3;
	} else if ($type=='sqrt') {
		$code = 6.5;
	} else if ($type=='cuberoot') {
		$code = 6.6;
	} else if ($type=='abs') {
		$code = 8;
	} else if ($type=='rational') {
		$code = 8.2;
	} else if ($type=='exp') {
		$code = 8.3;
	} else if ($type=='log') {
		$code = 8.4;
	} else if ($type=='genexp') {
        $code = 8.5;
    } else if ($type=='genlog') {
        $code = 8.6;
    } else if ($type=='circle' || $type=='circlerad') {
		$code = 7;
	} else if ($type=='ellipse' || $type=='ellipserad') {
        $code = 7.2;
    } else if ($type=='sin') {
		$code = 9.1;
	} else if ($type=='cos') {
		$code = 9;
	} else if ($type=='vector') {
		$code = 5.4;
	} else {
		$code = -1;
	}
	$imgborder = 5;
	$pixelsperx = ($w - 2*$imgborder)/($xmax-$xmin);
	$pixelspery = ($h - 2*$imgborder)/($ymax -$ymin);
	$outpts = array();
	list($lines,$dots,$odots,$tplines,$ineqlines) = explode(';;',$str);
	if ($tplines=='') {return array();}
	$tplines = explode('),(', substr($tplines,1,strlen($tplines)-2));
	foreach ($tplines as $k=>$val) {
		$pts = explode(',',$val);
		if ($pts[0]==$code) {
			$pts[1] = ($pts[1] - $imgborder)/$pixelsperx + $xmin;
			$pts[3] = ($pts[3] - $imgborder)/$pixelsperx + $xmin;
			$pts[2] = ($h - $pts[2] - $imgborder)/$pixelspery + $ymin;
			$pts[4] = ($h - $pts[4] - $imgborder)/$pixelspery + $ymin;
            $outpt = array($pts[1], $pts[2], $pts[3], $pts[4]);
            if ($type=='ellipserad') {
                $pts[3] = abs($pts[3]-$pts[1]);
                $pts[4] = abs($pts[4]-$pts[2]);
                $outpt = array($pts[1], $pts[2], $pts[3], $pts[4]);
            } else if ($type=='circlerad') {
                $pts[3] = sqrt(pow($pts[3]-$pts[1],2)+pow($pts[4]-$pts[2],2));
                $outpt = array($pts[1], $pts[2], $pts[3]);
            } else if ($type=='genexp' || $type=='genlog') {
                $pts[5] = ($pts[5] - $imgborder)/$pixelsperx + $xmin;
                $pts[6] = ($h - $pts[6] - $imgborder)/$pixelspery + $ymin;
                // Last value is the asymptote: y val for genexp, x val for genlog
                $outpt = ($type=='genexp') ? array($pts[3], $pts[4], $pts[5], $pts[6], $pts[2]) : array($pts[3], $pts[4], $pts[5], $pts[6], $pts[1]);
            }
			$outpts[] = $outpt;
		}
	}
	return $outpts;
}

function gettwopointformulas($str,$type,$xmin=null,$xmax=null,$ymin=null,$ymax=null,$w=null,$h=null) {
  $args = func_get_args();
  $eqnvars = [];
  $showequation = false;
  foreach ($args as $key => $arg) {
    if (strpos($arg,'showequation') !== false) {
      $showequation = true;
      if (!is_array($args[$key])) {
        $eqnvars = explode(",",$args[$key]);
      }
    }
  }
  $x = (!empty($eqnvars[1])) ? $eqnvars[1] : 'x';
  $y = (!empty($eqnvars[2])) ? $eqnvars[2] : 'y';
  $pts = gettwopointdata($str,$type,$xmin,$xmax,$ymin,$ymax,$w,$h);
  $outexps = [];
  $outeqs = [];
  if (!empty($pts)) {
    if ($type=='line' || $type=='lineseg' || $type=='ray') {
  		foreach ($pts as $key => $pt) {
        if (abs($pt[0]-$pt[2]) > 1E-12) {
          $slope = ($pt[3]-$pt[1])/($pt[2]-$pt[0]);
          $int = $pt[1] - $slope*$pt[0];
          $outexps[] = makexxpretty("$slope $x + $int");
          $outeqs[] = makexxpretty("$y = $slope $x + $int");
        }
      }
  	} else if ($type=='parab') {
      foreach ($pts as $key => $pt) {
        if (abs($pt[0]-$pt[2]) > 1E-12) {
          $k = ($pt[3]-$pt[1])/pow($pt[2]-$pt[0],2);
          $coef1 = -2*$pt[0]*$k;
          $coef2 = pow($pt[0],2)*$k + $pt[1];
          $outexps[] = makexxpretty("$k $x^2 + $coef1 $x + $coef2");
          $outeqs[] = makexxpretty("$y = $k $x^2 + $coef1 $x + $coef2");
        }
      }
  	} else if ($type=='horizparab') {
  	  foreach ($pts as $key => $pt) {
        if (abs($pt[1]-$pt[3]) > 1E-12) {
          $k = ($pt[2]-$pt[0])/pow($pt[3]-$pt[1],2);
          $coef1 = -2*$pt[1]*$k;
          $coef2 = pow($pt[1],2)*$k + $pt[0];
          $outexps[] = makexxpretty("$k $y^2 + $coef1 $y + $coef2");
          $outeqs[] = makexxpretty("$x = $k $y^2 + $coef1 $y + $coef2");
        }
      }
  	} else if ($type=='cubic') {
      foreach ($pts as $key => $pt) {
        if (abs($pt[0]-$pt[2]) > 1E-12) {
          $k = ($pt[3]-$pt[1])/pow($pt[2]-$pt[0],3);
          $coef1 = -3*$pt[0]*$k;
          $coef2 = 3*pow($pt[0],2)*$k;
          $coef3 = -1*pow($pt[0],3)*$k+$pt[1];
          $outexps[] = makexxpretty("$k $x^3 + $coef1 $x^2 + $coef2 $x + $coef3");
          $outeqs[] = makexxpretty("$y = $k $x^3 + $coef1 $x^2 + $coef2 $x + $coef3");
        }
      }
  	} else if ($type=='sqrt') {
      foreach ($pts as $key => $pt) {
    	if (abs($pt[0]-$pt[2]) > 1E-12) {
          $k = ($pt[3]-$pt[1])/sqrt(abs($pt[2]-$pt[0]));
          $j = ($pt[2] > $pt[0]) ? 1 : -1;
          $outexps[] = makexxpretty("$k sqrt($j $x - $pt[0]) + $pt[1]");
          $outeqs[] = makexxpretty("$y = $k sqrt($j $x - $pt[0]) + $pt[1]");
        }
      }
  	} else if ($type=='cuberoot') {
      foreach ($pts as $key => $pt) {
    		if (abs($pt[0]-$pt[2]) > 1E-12) {
          $k = abs($pt[3]-$pt[1])/pow(abs($pt[2]-$pt[0]),1/3);
          $k *= (($pt[2]-$pt[0])*($pt[3]-$pt[1]) < 0) ? -1 : 1;
          $outexps[] = makexxpretty("$k root(3)($x - $pt[0]) + $pt[1]");
          $outeqs[] = makexxpretty("$y = $k root(3)($x - $pt[0]) + $pt[1]");
        }
      }
  	} else if ($type=='abs') {
      foreach ($pts as $key => $pt) {
    		if (abs($pt[0]-$pt[2]) > 1E-12) {
          $k = ($pt[2] > $pt[0]) ? ($pt[3]-$pt[1])/($pt[2]-$pt[0]) : -1*($pt[3]-$pt[1])/($pt[2]-$pt[0]);
          $outexps[] = makexxpretty("$k abs($x - $pt[0]) + $pt[1]");
          $outeqs[] = makexxpretty("$y = $k abs($x - $pt[0]) + $pt[1]");
        }
      }
  	} else if ($type=='rational') {
      foreach ($pts as $key => $pt) {
        if (abs($pt[0]-$pt[2]) > 1E-12 && abs($pt[1]-$pt[3]) > 1E-12) {
          $k = ($pt[3]-$pt[1])*($pt[2]-$pt[0]);
          $outexps[] = makexxpretty("$k/($x - $pt[0]) + $pt[1]");
          $outeqs[] = makexxpretty("$y = $k/($x - $pt[0]) + $pt[1]");
        }
      }
  	} else if ($type=='exp') {
      foreach ($pts as $key => $pt) {
        if ($pt[1]*$pt[3] > 0) {
          if (abs($pt[0]-$pt[2]) > 1E-12) {
            $k = pow($pt[3]/$pt[1],1/($pt[2]-$pt[0]));
            $j = $pt[1]/pow($k,$pt[0]);
          } 
          $outexps[] = ($pt[3]==$pt[1]) ? $pt[1] : makexxpretty("$j($k)^$x");
          $outeqs[] = ($pt[3]==$pt[1]) ? "$y = $pt[1]" : makexxpretty("$y = $j($k)^$x");
        } else if ($pt[1] == 0 && $pt[3] == 0) {
          $outexps[] = 0;
          $outeqs[] = "$y = 0";
        }
      }
  	} else if ($type=='log') {
      foreach ($pts as $key => $pt) {
        if (abs($pt[0]-$pt[2]) > 1E-12 && abs($pt[1]-$pt[3]) > 1E-12 && $pt[0]*$pt[2] > 0) {
          $j = abs($pt[0])/$pt[0];
          $k = ($pt[3]-$pt[1])/(log($j*$pt[2])-log($j*$pt[0]));
          $b = $pt[1]-$k*log($j*$pt[0]);
          $outexps[] = makexxpretty("$k ln($j $x) + $b");
          $outeqs[] = makexxpretty("$y = $k ln($j $x) + $b");
        }
      }
  	} else if ($type=='genexp') {
      foreach ($pts as $key => $pt) {
        if (abs($pt[1]-$pt[3]) < 1E-12) {
          $outexp = "$pt[1]";
          $outeq = "$y = $pt[1]";
        } else if (($pt[1]-$pt[4])*($pt[3]-$pt[4]) > 0 && abs($pt[0]-$pt[2]) > 1E-12) {
          if (abs($pt[1]-$pt[3]) > 1E-12) {
            $b = pow(($pt[3]-$pt[4])/($pt[1]-$pt[4]),1/($pt[2]-$pt[0]));
            $a = ($pt[3]-$pt[1])/(pow($b,$pt[2])-pow($b,$pt[0]));
          } elseif (abs($pt[1]-$pt[3]) <= 1E-12) {
            $outexp = "$pt[1]";
            $outeq = "$y = $pt[1]";
          }
          $outexp = makexxpretty("$a($b)^$x + $pt[4]");
          $outeq = "$y=".$outexp;
        }
        $outexps[] = $outexp;
        $outeqs[] = $outeq;
      }
  	} else if ($type=='genlog') {
      foreach ($pts as $key => $pt) {
        if (($pt[0]-$pt[4])*($pt[2]-$pt[4]) > 0 && abs($pt[0]-$pt[2]) > 1E-12 && abs($pt[1]-$pt[3]) > 1E-12) {
          $a = ($pt[3]-$pt[1])/(log(abs($pt[2]-$pt[4]))-log(abs($pt[0]-$pt[4])));
          $b = $pt[1] - $a*log(abs($pt[0]-$pt[4]));
          $j = ($pt[0] > $pt[4]) ? 1 : -1;
          $shift = $pt[4]*$j;
          $outexp = makexxpretty("$a ln($j $x - $shift) + $b");
          $outeq = "$y=".$outexp;
        }
        $outexps[] = $outexp;
        $outeqs[] = $outeq;
      }
  	} else if ($type=='circle') {
      foreach ($pts as $key => $pt) {
        if (!(abs($pt[0]-$pt[2]) < 1E-12 && abs($pt[1]-$pt[3]) < 1E-12)) {
          $rs = pow($pt[2]-$pt[0],2) + pow($pt[3]-$pt[1],2);
          $xexp = ($pt[0]==0) ? "$x^2" : "($x-$pt[0])^2";
          $yexp = ($pt[1]==0) ? "$y^2" : "($y-$pt[1])^2";
          $outexps[] = makexxpretty("$xexp/$rs + $yexp/$rs");
          $outeqs[] = makexxpretty("$xexp + $yexp = $rs");
        }
      }
  	} else if ($type=='ellipse') {
      foreach ($pts as $key => $pt) {
        if (abs($pt[0]-$pt[2]) > 1E-12 && abs($pt[1]-$pt[3]) > 1E-12) {
          $as = pow($pt[2]-$pt[0],2);
          $bs = pow($pt[3]-$pt[1],2);
          $xexp = ($pt[0]==0) ? "$x^2/$as" : "($x-$pt[0])^2/$as";
          $yexp = ($pt[1]==0) ? "$y^2/$bs" : "($y-$pt[1])^2/$bs";
          $outexps[] = makexxpretty("$xexp + $yexp");
          $outeqs[] = makexxpretty("$xexp + $yexp = 1");
        }
      }
    } else if ($type=='sin') {
      foreach ($pts as $key => $pt) {
        if (abs($pt[0]-$pt[2]) > 1E-12) {
          if (abs($pt[1]-$pt[3]) > 1E-12) {
            $a = abs($pt[3]-$pt[1]);
            $b = M_PI/(2*abs($pt[2]-$pt[0]));
            $c = $pt[1];
            $shift = (($pt[1]-$pt[3])*($pt[0]-$pt[2]) > 0) ? fmod($pt[0]*$b,2*M_PI) : fmod((2*$pt[2]-$pt[0])*$b,2*M_PI);
            $outexp = makexxpretty("$a sin($b $x - $shift) + $c");
            $outeq = "$y=".$outexp;
          } else if (abs($pt[1]-$pt[3]) <= 1E-12) {
            $outexp = $pt[1];
            $outeq = "$y = $pt[1]";
          }
        }
        $outexps[] = $outexp;
        $outeqs[] = $outeq;
      }
  	} else if ($type=='cos') {
      foreach ($pts as $key => $pt) {
        if (abs($pt[0]-$pt[2]) > 1E-12) {
          if (abs($pt[1]-$pt[3]) > 1E-12) {
            $a = abs($pt[3]-$pt[1])/2;
            $b = M_PI/abs($pt[2]-$pt[0]);
            $c = ($pt[1] + $pt[3])/2;
            $shift = ($pt[1] > $pt[3]) ? fmod($pt[0]*$b,2*M_PI) : fmod($pt[2]*$b,2*M_PI);
            $outexp = makexxpretty("$a cos($b $x - $shift) + $c");
            $outeq = "$y=".$outexp;
          } else if (abs($pt[1]-$pt[3]) <= 1E-12) {
            $outexp = $pt[1];
            $outeq = "$y=$pt[1]";
          }
        }
      }
      $outexps[] = $outexp;
      $outeqs[] = $outeq;
  	}
  }
  if ($showequation === true) {
    return $outeqs;
  } else {
    return $outexps;  
  }
}

function getineqdata($str,$type='linear',$xmin=null,$xmax=null,$ymin=null,$ymax=null,$w=null,$h=null) {
    if (is_string($xmin) && strpos($xmin,',')!==false) {
        list($xmin,$xmax,$ymin,$ymax,$w,$h) = parsedrawgrid($xmin,$xmax);
    }
    if (trim($str)=='' || $xmax==$xmin || $ymax==$ymin) { return []; } // invalid
	$imgborder = 5;
	$pixelsperx = ($w - 2*$imgborder)/($xmax-$xmin);
	$pixelspery = ($h - 2*$imgborder)/($ymax -$ymin);
	$outpts = array();
	list($lines,$dots,$odots,$tplines,$ineqlines) = explode(';;',$str);
	if ($ineqlines=='') {return array();}
	$ineqlines = explode('),(', substr($ineqlines,1,strlen($ineqlines)-2));
	foreach ($ineqlines as $k=>$val) {
		if ($type == 'linear' && $pts[0] > 10.2) { continue; }
		else if ($type == 'quadratic' && $pts[0] < 10.3) { continue; }
		$pts = explode(',',$val);
		$pts[1] = ($pts[1] - $imgborder)/$pixelsperx + $xmin;
		$pts[3] = ($pts[3] - $imgborder)/$pixelsperx + $xmin;
		$pts[5] = ($pts[5] - $imgborder)/$pixelsperx + $xmin;
		$pts[2] = ($h - $pts[2] - $imgborder)/$pixelspery + $ymin;
		$pts[4] = ($h - $pts[4] - $imgborder)/$pixelspery + $ymin;
		$pts[6] = ($h - $pts[6] - $imgborder)/$pixelspery + $ymin;
		if ($pts[0] == 10.2 || $pts[0] == 10.4) {
			$pts[0] = 'ne';
		} else {
			$pts[0] = 'eq';
		}
		$outpts[] = $pts;
	}
	return $outpts;
}

function getdotsdata($str,$xmin=null,$xmax=null,$ymin=null,$ymax=null,$w=null,$h=null) {
    if (is_string($xmin) && strpos($xmin,',')!==false) {
        list($xmin,$xmax,$ymin,$ymax,$w,$h) = parsedrawgrid($xmin,$xmax);
    }
    if (trim($str)=='' || $xmax==$xmin || $ymax==$ymin) { return []; } // invalid
	$imgborder = 5;
	$pixelsperx = ($w - 2*$imgborder)/($xmax-$xmin);
	$pixelspery = ($h - 2*$imgborder)/($ymax -$ymin);
	list($lines,$dots,$odots,$tplines,$ineqlines) = explode(';;',$str);
	if ($dots=='') { return array();}
	$dots = explode('),(', substr($dots,1,strlen($dots)-2));
	foreach ($dots as $k=>$pt) {
		 $pt =  explode(',',$pt);
		 $pt[0] = ($pt[0] - $imgborder)/$pixelsperx + $xmin;
		 $pt[1] = ($h - $pt[1] - $imgborder)/$pixelspery + $ymin;
		 $dots[$k] = $pt;
	}
	return $dots;
}
function getopendotsdata($str,$xmin=null,$xmax=null,$ymin=null,$ymax=null,$w=null,$h=null) {
    if (is_string($xmin) && strpos($xmin,',')!==false) {
        list($xmin,$xmax,$ymin,$ymax,$w,$h) = parsedrawgrid($xmin,$xmax);
    }
    if (trim($str)=='' || $xmax==$xmin || $ymax==$ymin) { return []; } // invalid
	$imgborder = 5;
	$pixelsperx = ($w - 2*$imgborder)/($xmax-$xmin);
	$pixelspery = ($h - 2*$imgborder)/($ymax -$ymin);
	list($lines,$dots,$odots,$tplines,$ineqlines) = explode(';;',$str);
	if ($odots=='') { return array();}
	$dots = explode('),(', substr($odots,1,strlen($odots)-2));
	foreach ($dots as $k=>$pt) {
		 $pt =  explode(',',$pt);
		 $pt[0] = ($pt[0] - $imgborder)/$pixelsperx + $xmin;
		 $pt[1] = ($h - $pt[1] - $imgborder)/$pixelspery + $ymin;
		 $dots[$k] = $pt;
	}
	return $dots;
}
function getlinesdata($str,$xmin=null,$xmax=null,$ymin=null,$ymax=null,$w=null,$h=null) {
    if (is_string($xmin) && strpos($xmin,',')!==false) {
        list($xmin,$xmax,$ymin,$ymax,$w,$h) = parsedrawgrid($xmin,$xmax);
    }
    if (trim($str)=='' || $xmax==$xmin || $ymax==$ymin) { return []; } // invalid
	$imgborder = 5;
	$pixelsperx = ($w - 2*$imgborder)/($xmax-$xmin);
	$pixelspery = ($h - 2*$imgborder)/($ymax -$ymin);
	list($lines,$dots,$odots,$tplines,$ineqlines) = explode(';;',$str);
	if ($lines=='') { return array();}
	$lines = explode(';',$lines);
	$out = array();
	foreach ($lines as $i=>$line) {
		$out[$i] = array();
		$pts = explode('),(', substr($line,1,strlen($line)-2));
		foreach ($pts as $k=>$pt) {
			 $pt =  explode(',',$pt);
             if (count($pt) != 2) { continue; }
			 $pt[0] = ($pt[0] - $imgborder)/$pixelsperx + $xmin;
			 $pt[1] = ($h - $pt[1] - $imgborder)/$pixelspery + $ymin;
			 $out[$i][$k] = array($pt[0],$pt[1]);
		}
	}
	return $out;
}

function getsnapwidthheight($xmin,$xmax,$ymin,$ymax,$width,$height,$snaptogrid) {
	$imgborder = 5;

	if (strpos($snaptogrid,':')!==false) {
		$snapparts = explode(':',$snaptogrid);
	} else {
		$snapparts = array($snaptogrid,$snaptogrid);
	}
	$snapparts = array_map('floatval', $snapparts);
	if ($xmax - $xmin>0 && !empty($snapparts[0])) {
		$newwidth = ($xmax - $xmin)*(round($snapparts[0]*($width-2*$imgborder)/($xmax - $xmin))/$snapparts[0]) + 2*$imgborder;
	} else {
		$newwidth = $width;
	}
	if ($ymax - $ymin>0 && !empty($snapparts[1])) {
		$newheight = ($ymax - $ymin)*(round($snapparts[1]*($height-2*$imgborder)/($ymax - $ymin))/$snapparts[1]) + 2*$imgborder;
	} else {
		$newheight = $height;
	}
	return array($newwidth,$newheight);
}

function mod($p,$n) {
    if ($n==0) { return false; }
	return (($p % $n) + $n)%$n;
}

function getscorenonzero() {
	global $scores;
	$out = array();
	foreach ($scores as $i=>$v) {
		if (strpos($v,'~')===false) {
			$out[$i+1] = ($v<0)?-1:(($v>0)?1:0);
		} else {
			$sp = explode('~',$v);
			$out[$i+1] = array();
			for ($j=0;$j<count($sp);$j++) {
				$out[$i+1][$j] = ($sp[$j]>0)?1:0;
			}
		}
	}
	return $out;
}

function getiscorrect() {
	global $rawscores;
	$out = array();
	if (!is_array($rawscores)) {
		return $out;
	}
	foreach ($rawscores as $i=>$v) {
		if (strpos($v,'~')===false) {
			$out[$i+1] = ($v<0)?-1:(($v==1)?1:0);
		} else {
			$sp = explode('~',$v);
			$out[$i+1] = array();
			for ($j=0;$j<count($sp);$j++) {
				$out[$i+1][$j] = ($sp[$j]==1)?1:0;
			}
		}
	}
	return $out;
}

function ABarray($s,$n) {
    $s = (int) $s;
    $n = (int) $n;
	$out = array();
	for ($i=$s;$i<$s+$n;$i++) {
		$out[] = '[AB'.$i.']';
	}
	return $out;
}

function scorestring($answer,$showanswer,$words,$stu,$qn,$part=null,$highlight=true) {
	$wc = array();
	if (!is_array($words)) {
		$words = listtoarray($words);
	}
    if ($answer === null || $stu === null) {
        return [$answer, $showanswer];
    }
	/*
	foreach ($words as $w) {
		if (!isset($wc[$w])) {
			$wc[$w] = 1;
		} else {
			$wc[$w]++;
		}
	}
	$words = array_keys($wc);
	*/
	if ($part === null) {
		$ans = $answer;
	} else if (!isset($answer[$part])) {
        return [$answer, $showanswer];
    } else {
		$ans = $answer[$part];
	}
	if ($highlight) {
		$sa = $ans;
		foreach ($words as $w) {
			$sa = str_replace($w,'<span style="color:#f00">'.$w.'</span>',$sa);
		}
	} else {
		$sa = $ans;
	}
	$iscorrect = true;
	if ($part===null) {
		$stua = $stu[$qn];
	} else {
		$stua = getstuans($stu,$qn,$part);
	}

	if ($stua != null) {
		foreach ($words as $w) {
			//if (substr_count($stua, $w) != $wc[$w]) {
			if (strpos($stua, $w) === false) {
				$iscorrect = false;
				break;
			}
		}
	} else {
		$iscorrect = false;
	}
	if ($part === null) {
		if ($iscorrect) {
			$answer = $stua;
		} else {
			$answer = $ans;
		}
		$showanswer = $sa;
	} else {
		if ($iscorrect) {
			$answer[$part] = $stua;
		} else {
			$answer[$part] = $ans;
		}
		$showanswer[$part] = $sa;
	}

	return array($answer, $showanswer);
}

function parsesloppycomplex($v) {
    $func = makeMathFunction($v, 'i', [], '', true);
    if ($func===false) {
        return false;
    }
    $a = $func(['i'=>0]);
    $apb = $func(['i'=>4]);
    if (isNaN($a) || isNaN($apb)) {
        return false;
    }
    return array($a,($apb-$a)/4);
}

//scoremultiorder($stua, $answer, $swap, [$type='string', $weights])
//allows groups of questions to be scored in different orders
//only works if $stua and $answer are directly comparable (i.e. basic string type or exact number)
//$swap is an array of entries of the form "1,2,3;4,5,6"  says to treat 1,2,3 as a group of questions.
//$weights is answeights, and use function like $answer,$answeights = scoremultiorder(...) if set
//comparison is made on first entry in group
function scoremultiorder($stua, $answer, $swap, $type='string', $weights=null) {
	if ($stua == null) {
        if ($weights !== null) {
            return [$answer,$weights];
        } else {
            return $answer;
        }
    }

    $swapindices = [];
    if (!is_array($swap)) {
        $swap = [$swap];
    }
	foreach ($swap as $k=>$sw) {
		$swap[$k] = explode(';', $sw);
		foreach ($swap[$k] as $i=>$s) {
			$swap[$k][$i] = listtoarray($s);
            $swapindices[] = $swap[$k][$i][0];
		}
    }

    $newans = $answer;
    $newansval = [];
    if ($type == 'calculated') {
        foreach ($swapindices as $k) {
            $v = $newans[$k];
            $newansval[$k] = evalMathParser($v);
        }
    } else if ($type == 'complex' || $type == 'calccomplex') {
        foreach ($swapindices as $k) {
            $v = $newans[$k];
            $newansval[$k] = parsesloppycomplex($v);
        }
        foreach ($stua as $k=>$v) {
            $stua[$k] = parsesloppycomplex($v);
        }
    } else if ($type == 'ntuple' || $type == 'calcntuple') {
        foreach ($swapindices as $k) {
            $v = $newans[$k];
            $newansval[$k] = explode(',', substr($v,1,-1));
            if ($type == 'calcntuple') {
                foreach ($newansval[$k] as $j=>$vv) {
                    $newansval[$k][$j] = evalMathParser($vv);
                }
            }
        }
        foreach ($stua as $k=>$v) {
            $stua[$k] = explode(',', substr($v,1,-1));
        }
    }
    if ($weights !== null) {
        if (!is_array($weights)) {
            $weights = explode(',', $weights);
        }
        $newweights = $weights;
    }

	foreach ($swap as $sw) {
		//for ($i=0;$i<count($sw);$i++) {
        for ($i=count($sw)-1;$i>=0;$i--) {
            if (!isset($sw[$i][0]) || !isset($stua[$sw[$i][0]])) { continue; }
			$tofind = $stua[$sw[$i][0]];
			$loc = -1;
			//for ($k=0;$k<count($sw);$k++) {
            for ($k=count($sw)-1;$k>=0;$k--) {
				if ($type=='string' && preg_replace('/\s+/','',strtolower($tofind))==preg_replace('/\s+/','',strtolower($newans[$sw[$k][0]]))) {
					$loc = $k; break;
				} else if ($type=='number' && is_numeric($tofind) && is_numeric($newans[$sw[$k][0]]) && abs($tofind - $newans[$sw[$k][0]])<0.01) {
					$loc = $k; break;
				} else if ($type=='calculated' && is_numeric($tofind) && abs($tofind - $newansval[$sw[$k][0]])<0.01) {
                    $loc = $k; break;
                } else if ($type=='numfunc' && $tofind !== '' && comparefunctions($tofind, $newans[$sw[$k][0]])) {
                    $loc = $k; break;
                } else if (($type=='complex' || $type=='calccomplex' || $type=='ntuple' || $type == 'calcntuple') && 
                    $tofind !== false && count($tofind) == 2 && is_numeric($tofind[0]) && is_numeric($tofind[1]) && 
                    abs($tofind[0] - $newansval[$sw[$k][0]][0])<0.01 && abs($tofind[1] - $newansval[$sw[$k][0]][1])<0.01
                ) {
                    $loc = $k; break;
                }
			}
			if ($loc>-1 && $i!=$loc) {
				//want to swap entries from $sw[$loc] with sw[$i] and swap $answer values
                $tmp = array();
                $tmpw = array();
                $tmpv = array();
				foreach ($sw[$i] as $k=>$v) {
                    $tmp[$k] = $newans[$v];
                    if ($weights !== null) {
                        $tmpw[$k] = $newweights[$v];
                    }
                    if (isset($newansval[$v])) {
                        $tmpv[$k] = $newansval[$v];
                    }
				}
				foreach ($sw[$loc] as $k=>$v) {
                    $newans[$sw[$i][$k]] = $newans[$v];
                    if ($weights !== null) {
                        $newweights[$sw[$i][$k]] = $newweights[$v];
                    }
                    if (isset($newansval[$v])) {
                        $newansval[$sw[$i][$k]] = $newansval[$v];
                    }
				}
				foreach ($tmp as $k=>$v) {
                    $newans[$sw[$loc][$k]] = $tmp[$k];
                    if ($weights !== null) {
                        $newweights[$sw[$loc][$k]] = $tmpw[$k];
                    }
                    if (isset($tmpv[$k])) {
                        $newansval[$sw[$loc][$k]] = $tmpv[$k];
                    }
				}
			}
		}
    }

    if ($weights !== null) {
        return array($newans, $newweights);
    } else {
        return $newans;
    }
}

function lensort($a,$b) {
	return strlen($b)-strlen($a);
}

function parsereqsigfigs($reqsigfigs) {
    $origstr = $reqsigfigs;
    $reqsigfigs = str_replace('+/-','+-',$reqsigfigs);
	$reqsigfigoffset = 0;
	$reqsigfigparts = array_map('trim', explode('+-',$reqsigfigs));
	$reqsigfigs = $reqsigfigparts[0];
	$sigfigscoretype = array('abs',0,'def');
	if (count($reqsigfigparts)>1) {
		if (substr($reqsigfigparts[1], -1)=='%') {
			$sigfigscoretype = array('rel', floatval(substr($reqsigfigparts[1], 0, -1)));
		} else {
			$sigfigscoretype = array('abs', floatval($reqsigfigparts[1]));
		}
	}
	if ($reqsigfigs[0]=='=') {
		$exactsigfig = true;
		$reqsigfigs = substr($reqsigfigs,1);
	} else if ($reqsigfigs[0]=='[') {
		$exactsigfig = false;
		$reqsigfigparts = listtoarray(substr($reqsigfigs,1,-1));
		$reqsigfigs = $reqsigfigparts[0];
        if (isset($reqsigfigparts[1])) {
		    $reqsigfigoffset = $reqsigfigparts[1] - $reqsigfigparts[0];
        } 
	} else {
		$exactsigfig = false;
	}
    if (!is_numeric($reqsigfigs)) {
        echo "Invalid reqsigfigs/reqdecimals string $origstr";
    }
	return array(intval($reqsigfigs), $exactsigfig, $reqsigfigoffset, $sigfigscoretype);
}

function getsigfigs($val, $targetsigfigs=0) {
    $val = trim(ltrim($val," \n\r\t\v\x00-"));
    if (!is_numeric($val) || $val == 0) { return 0; }
    if (strpos($val,'E')!==false) {
        preg_match('/^(\d*)\.?(\d*)E/', $val, $matches);
        if (!isset($matches[1])) { return 0; } // invalid
        $sigfigs = strlen($matches[1])+strlen($matches[2]);
    } else {
        $gadploc = strpos($val,'.');
        if ($gadploc===false) { // no decimal place
            $sigfigs = max($targetsigfigs, strlen(rtrim($val,'0')));
        } else if (abs($val)<1) {
            $sigfigs = strlen(ltrim(substr($val,$gadploc+1),'0'));
        } else {
            $sigfigs = strlen(ltrim($val,'0'))-1;
        }
    }
    return $sigfigs;
}

function checksigfigs($givenans, $anans, $reqsigfigs, $exactsigfig, $reqsigfigoffset, $sigfigscoretype) {
	if (!is_numeric($givenans) || !is_numeric($anans) || $givenans*$anans < 0) { return false;} //move on if opposite signs
    if ($anans != 0) {
        $gasigfig = getsigfigs($givenans, $reqsigfigs);
        if ($exactsigfig) {
			if ($gasigfig != $reqsigfigs) {return false;}
		} else {
			if ($gasigfig < $reqsigfigs) {return false;}
			if ($reqsigfigoffset>0 && $gasigfig-$reqsigfigs>$reqsigfigoffset) {return false;}
		}
    } else {
        $gasigfig = 0;
    }
    if ($sigfigscoretype === false) {
        return true;
    }
    $epsilon = (($anans==0||abs($anans)>1)?1E-12:(abs($anans)*1E-12));
    // We've confirmed the sigfigs on givenans are acceptable, so
    // now round anans to givenans's sigfigs for numeric comparison
    $anans = roundsigfig($anans, $gasigfig);

    //checked format, now check value
	if ($sigfigscoretype[0]=='abs') {
		if (abs($anans-$givenans)< $sigfigscoretype[1]+$epsilon) {return true;}
	} else if ($sigfigscoretype[0]=='rel') {
		if ($anans==0) {
			if (abs($anans - $givenans) < $sigfigscoretype[1]+$epsilon) {return true;}
		} else {
			if (abs($anans - $givenans)/(abs($anans)+$epsilon) < $sigfigscoretype[1]/100+$epsilon) {return true;}
		}
	}
	return false;
}
/* for reference, in case new version has issues
function checksigfigs_old($givenans, $anans, $reqsigfigs, $exactsigfig, $reqsigfigoffset, $sigfigscoretype) {
	if (!is_numeric($givenans) || !is_numeric($anans) || $givenans*$anans < 0) { return false;} //move on if opposite signs
	if ($anans!=0) {
		$v = -1*floor(-log10(abs($anans))-1e-12) - $reqsigfigs;
	}
	$epsilon = (($anans==0||abs($anans)>1)?1E-12:(abs($anans)*1E-12));
	
	if (strpos($givenans,'E')!==false) {  //handle computer-style scientific notation
		preg_match('/^-?[1-9]\.?(\d*)E/', $givenans, $matches);
		$gasigfig = 1+strlen($matches[1]);
		if ($exactsigfig) {
			if ($gasigfig != $reqsigfigs) {return false;}
		} else {
			if ($gasigfig < $reqsigfigs) {return false;}
			if ($reqsigfigoffset>0 && $gasigfig-$reqsigfigs>$reqsigfigoffset) {return false;}
		}
	} else {
		if (!$exactsigfig) {
			$absgivenans = str_replace('-','',$givenans);
			$gadploc = strpos($absgivenans,'.');
			if ($gadploc===false) { // no decimal place
                if ($anans != 0 && strlen($absgivenans) < $reqsigfigs) { return false; } //not enough digits
                if ($anans != 0 && $reqsigfigoffset>0 && strlen(rtrim($absgivenans,'0')) > $reqsigfigs + $reqsigfigoffset) {return false;} //too many sigfigs
                $gasigfig = max($reqsigfigs, strlen(rtrim($absgivenans,'0')));
            } else {
				if (abs($givenans)<1) {
					$gasigfig = strlen(ltrim(substr($absgivenans,$gadploc+1),'0'));
				} else {
					$gasigfig = strlen(ltrim($absgivenans,'0'))-1;
				}
                if ($anans != 0 && $gasigfig < $reqsigfigs ) { return false; } //not enough sigfigs
                if ($anans != 0 && $reqsigfigoffset>0 && $gasigfig > $reqsigfigs + $reqsigfigoffset) {return false;} //too many sigfigs
            }
		} else {
			$absgivenans = str_replace('-','',$givenans);
			$gadploc = strpos($absgivenans,'.');
			if ($gadploc===false) { //no decimal place
				if (strlen(rtrim($absgivenans,'0')) > $reqsigfigs || 
                    strlen($absgivenans) < $reqsigfigs
                ) { 
                    return false;
                }
                $gasigfig = $reqsigfigs;
			} else {
				if (abs($givenans)<1) {
					if (strlen(ltrim(substr($absgivenans,$gadploc+1),'0')) != $reqsigfigs) { return false;}
				} else {
					if (strlen(ltrim($absgivenans,'0'))-1 != $reqsigfigs) { return false;}
				}
                $gasigfig = $reqsigfigs;
			}
		}
	}
    if ($sigfigscoretype === false) {
        return true;
    }
    // We've confirmed the sigfigs on givenans are acceptable, so
    // now round anans to givenans's sigfigs for numeric comparison
    $anans = roundsigfig($anans, $gasigfig);

    //checked format, now check value
	if ($sigfigscoretype[0]=='abs') {
		if (abs($anans-$givenans)< $sigfigscoretype[1]+$epsilon) {return true;}
	} else if ($sigfigscoretype[0]=='rel') {
		if ($anans==0) {
			if (abs($anans - $givenans) < $sigfigscoretype[1]+$epsilon) {return true;}
		} else {
			if (abs($anans - $givenans)/(abs($anans)+$epsilon) < $sigfigscoretype[1]/100+$epsilon) {return true;}
		}
	}
	return false;
}
*/

function evalMathPHP($str,$vl) {
	return evalReturnValue('return ('.mathphp($str,$vl).');', $str);
}
function evalReturnValue($str,$errordispstr='',$vars=array()) {
	$preevalerror = error_get_last();
	foreach ($vars as $v=>$val) {
		${$v} = $val;
	}
	try {
		$res = @eval($str);
	} catch (Throwable $t) {
		if ($GLOBALS['myrights']>10) {
			echo '<p>Caught error in evaluating a function in this question: ';
			echo Sanitize::encodeStringForDisplay($t->getMessage());
			if ($errordispstr!='') {
				echo ' while evaluating '.htmlspecialchars($errordispstr);
			}
			echo '</p>';
		}
		return false;
	}
	if ($res===false) {
		$error = error_get_last();
		if ($GLOBALS['myrights']>10 && $error!=$preevalerror) {
			echo '<p>Caught error in evaluating a function in this question: ',$error['message'];
			if ($errordispstr!='') {
				echo ' while evaluating '.htmlspecialchars($errordispstr);
			}
			echo '</p>';
		}
	} else {
		$error = error_get_last();
		if ($error && $error!=$preevalerror && $error['type']==E_ERROR && $GLOBALS['myrights']>10) {
			echo '<p>Caught error in evaluating a function in this question: ',$error['message'];
			if ($errordispstr!='') {
				echo ' while evaluating '.htmlspecialchars($errordispstr);
			}
			echo '</p>';
		}
	}
	return $res;
}

function getRoundNumber($val) {
	$str = (string) $val;
  $str = str_replace('e','E',$str);
	if (($s = strpos($str,'.'))===false) { //no decimal places
		return 0;
	} else if (($p = strpos($str,'E'))!==false) { //scientific notation
		$exp = ceil(-log10(abs($val)));
		if ($p-$s == 2 && $str[$s+1]=='0') { //is 3.0E-5 type
			return ($exp);
		} else {
			return ($exp + $p - $s - 1);
		}
	} else { //regular non-scientific notation
		return (strlen($str) - $s - 1);
	}
}

function checkMinMax($min, $max, $isint, $funcname) {
	$err = '';
	if (!is_numeric($min) || !is_numeric($max)) {
		$err .= "min and max need to be numbers. ";
	} else if (is_infinite($min) || is_infinite($max)) {
		$err .= "min and max need to be finite values. ";
		$min = 1;
		$max = 10;
	} else if ($isint && (floor($min)!=$min || floor($max)!=$max)) {
		$err .= "rands expects integer min and max. ";
		$min = ceil($min);
		$max = floor($max);
	}
	if ($isint) {
		$min = intval($min);
		$max = intval($max);
	} else {
		$min = floatval($min);
		$max = floatval($max);
	}
	if ($max < $min) {
		$err .= "Need min&lt;max. ";
		$t = $max;
		$max = $min;
		$min = $t;
	}
	if ($max == 0 && $min == 0) {
		$err .= "min=0 and max=0. May suggest a problem. ";
	}
	if ($GLOBALS['myrights']>10 && $err!='') {
		echo "Possible error in ".Sanitize::encodeStringForDisplay($funcname).': ';
		echo $err;
	}
	return array($min,$max);
}

function encryptval($val, $key) {
	$cipher = "AES128";
	$ivlen = openssl_cipher_iv_length($cipher);
  $iv = openssl_random_pseudo_bytes($ivlen);
	return base64_encode($iv). '.' . openssl_encrypt(json_encode($val), $cipher, $key, 0, $iv);
}
function decryptval($val, $key) {
	$cipher = "AES128";
	list($iv,$val) = explode('.', $val);
	return json_decode(openssl_decrypt($val, $cipher, $key, 0, base64_decode($iv)), true);
}

function formatcomplex($real,$imag) {
    if ($imag == 0) {
        return $real;
    } else {
        if ($imag == 1) {
            return ($real==0) ? 'i' : "$real+i";
        } else if ($imag == -1) {
            return ($real==0) ? '-i' : "$real-i";
        } else if ($imag < 0) {
            return "$real{$imag}i";
        } else {
            return "$real+{$imag}i";
        }
    }
}

function getntupleparts($string, $expected=null, $checknumeric=false) {
    if (empty($string) || !is_scalar($string) || strlen($string)<5) {
        return false;
    }
    if (!preg_match('/^\s*[\[\({<].*[\]\)}>]\s*$/', $string)) {
        return false;
    }
    $ntuples = parseNtuple($string,false,false);
    if (!is_array($ntuples) || !isset($ntuples[0])) {
        return false;
    }
    if (is_numeric($expected) && $expected != count($ntuples[0]['vals'])) {
        return false;
    }
    if ($checknumeric) {
        foreach ($ntuples[0]['vals'] as $v) {
            if (!is_numeric($v)) {
                return false;
            }
        }
    }
    return $ntuples[0]['vals'];
}

function comparelogic($a,$b,$vars) {
    if (!is_array($vars)) {
        $vars = array_map('trim', explode(',', $vars));
    }
    if ($a === null || $b === null || trim($a) == '' || trim($b) == '') {
        return false;
    }
    $varlist = implode(',', $vars);

	$keywords = ['\\', 'and', '^^', 'wedge', 'xor', 'oplus', 'or', 'vv', 'vee', '~', '¬', 'neg', 'iff', '<->', '<=>', 'implies', '->', '=>', 'rarr', 'to'];
	$replace = 	['',   '#a',  '#a', '#a',    '#x',  '#x',    '#o', '#o', '#o',  '!', '!', '!',   '#b',  '#b',	 '#b',  '#i',      '#i', '#i', '#i',   '#i'];
    $a = str_replace($keywords,$replace,$a);
    $b = str_replace($keywords,$replace,$b);

    $afunc = makeMathFunction($a, $varlist, [], '', true);
    if ($afunc === false) {
        return false;
    }
    $bfunc = makeMathFunction($b, $varlist, [], '', true);
    if ($bfunc === false) {
        return false;
    }
    $n = count($vars);
    $max = pow(2,$n);
    $map = array_combine($vars, array_fill(0,count($vars),0));
    for ($i=0; $i<$max; $i++) {
        $aval = $afunc($map);
        $bval = $bfunc($map);
        if ($aval != $bval) { 
            return false;
        }
        for ($j=0;$j<$n;$j++) {
            if ($map[$vars[$j]] == 0) { // if it's 0, add 1 and stop
                $map[$vars[$j]] = 1; break;
            } else {
                $map[$vars[$j]] = 0; // if it's 1, set to 0 and continue on to the next one
            }
        }
    }
    return true;
}

function comparesetexp($a,$b,$vars) {
    if (!is_array($vars)) {
        $vars = array_map('trim', explode(',', $vars));
    }
    if ($a === null || $b === null || trim($a) == '' || trim($b) == '') {
        return false;
    }
    $varlist = implode(',', $vars);
	
	$keywords = ['and', 'nn', 'cap', 'xor', 'oplus', 'ominus', 'triangle', 'or', 'cup', 'uu', '-',  '\''];
	$replace = 	['#a',  '#a', '#a',	 '#x',  '#x',    '#x',     '#x',       '#o', '#o',  '#o', '#m',	'^c'];

	$ab = [$a,$b];
	foreach($ab as &$str){
		$str = str_replace($keywords,$replace,$str);	

		// Since complement symbols in set expresions are unary operations *after* the operand, we will shift the complement operator to before the operand here, rather than overcomplicating MathParser
		// Remove double negations
		$str = preg_replace('/(\'|\^c){1}\s*(\'|\^c){1}/','',$str);
		// Remove any spaces before a complement symbol
		$str = preg_replace('/\s*(\'|\^c)/','$1',$str);
		// If symbol before a complement is an object from $vars, place a ! immediately before the $var
		foreach($vars as $var){
			$str = preg_replace("/($var)(\'|\^c)/",'!$1',$str);
		}
		// If symbol before a complement is a right paren/bracket, place a ! before the corresponding left paren/bracket
		$rindex = max(strpos($str,")^c"),strpos($str,"]^c"));
		while($rindex){
			$str = substr_replace($str,'',$rindex+1,2);
			$balanced = 1;
			for($i = $rindex-1; $i >= 0; $i--){
				if($str[$i] == ')' || $str[$i] == ']'){
					$balanced++;
				}
				elseif($str[$i] == '(' || $str[$i] == '['){
					$balanced--;
				}
				if($balanced==0){
					$str = substr_replace($str,'!',$i,0);
					break;
				}
			}
			$rindex = max(strpos($str,")^c"),strpos($str,"]^c"));
		}
		
	}
	$a = $ab[0];
	$b = $ab[1];
    $afunc = makeMathFunction($a, $varlist, [], '', true);
    if ($afunc === false) {
        return false;
    }
    $bfunc = makeMathFunction($b, $varlist, [], '', true);
    if ($bfunc === false) {
        return false;
    }
    $n = count($vars);
    $max = pow(2,$n);
    $map = array_combine($vars, array_fill(0,count($vars),0));
    for ($i=0; $i<$max; $i++) {
        $aval = $afunc($map);
        $bval = $bfunc($map);
        if ($aval != $bval) { 
            return false;
        }
        for ($j=0;$j<$n;$j++) {
            if ($map[$vars[$j]] == 0) { // if it's 0, add 1 and stop
                $map[$vars[$j]] = 1; break;
            } else {
                $map[$vars[$j]] = 0; // if it's 1, set to 0 and continue on to the next one
            }
        }
    }
    return true;
}
function comparentuples() {
  $par = false;
  $args = func_get_args();
  if (in_array("ignoreparens",$args)) {
    $par = true;
    unset($args[array_search("ignoreparens",$args)]);
    $args = array_values($args);
  }
  $utup = $args[0];
  $vtup = $args[1];
  
  if (empty($utup) || empty($vtup)) {
    echo 'Eek! Comparentuples needs two nutples to compare.';
    return false;
  }
  if (!preg_match('/^[\(\[\{\<]{1}.*[\)\]\}\>]{1}$/',$utup) || !preg_match('/^[\(\[\{\<]{1}.*[\)\]\}\>]{1}$/',$vtup)) {
    return false;
  }
  if (!isset($args[2])) {
    $args[2] = '0.001';
  }
  $tol = $args[2];
  $correct = 0;
  $uparen = [$utup[0],$utup[strlen($utup)-1]];
  $vparen = [$vtup[0],$vtup[strlen($vtup)-1]];
  $u = listtoarray(substr($utup,1,-1));
  $v = listtoarray(substr($vtup,1,-1));
  
  if (count($u) != count($v) || count($u) == 0 || count($v) == 0) {return false;}
  $dim = count($u);
  if (!is_array($tol)) {
    $tol = listtoarray($tol);
  }
  // repeat single tol for every entry
  if (count($tol) == 1) {
    $tol = fillarray("$tol[0]",$dim);
  }
  // fill in missing values at end of tol array with default value
  if (count($tol) < $dim) {
    for ($i=count($tol); $i<$dim; $i++) {
      $tol[$i] = '0.001';
    }
  }
  foreach ($tol as $key => $in) {
    // fill empty tol's in list with default value
    if (empty($in)) {
      $tol[$key] = '0.001';
    }
  }
  for ($i=0; $i<$dim; $i++) {
    if (comparenumbers($u[$i],$v[$i],"$tol[$i]")) {
      $correct += 1;
    }
  }
  if ($par == false) {
    if ($uparen[0] != $vparen[0] || $uparen[1] != $vparen[1]) {
      $correct += -1;
    }
  }
  if ($correct == $dim) {
    return true;
  }
}

function comparenumberswithunits($unitExp1, $unitExp2, $tol='0.001') {
  require_once __DIR__.'/../assessment/libs/units.php';
  if (strval($tol)[0]=='|') {
    $abstolerance = floatval(substr($tol,1));
  }
  [$unitVal1, $unitArray1] = parseunits($unitExp1);
  [$unitVal2, $unitArray2] = parseunits($unitExp2);
  if ($unitArray1 !== $unitArray2) {return false;}
  if ($unitArray1 === $unitArray2) {
    if (isset($abstolerance)) {
      if (abs($unitVal1 - $unitVal2) < $abstolerance+1E-12) {
        return true;
      }
    } else {
      if (abs($unitVal1 - $unitVal2)/abs($unitVal1+0.0001) < $tol+1E-12 && abs($unitVal1 - $unitVal2)/abs($unitVal2+0.0001) < $tol+1E-12) {
        return true;
      }
    }
  }
}

function comparesameform($a,$b,$vars="x") {
    $variables = array_values(array_filter(array_map('trim', explode(",", $vars)), 'strlen'));
    $ofunc = array();
    for ($i = 0; $i < count($variables); $i++) {
        //find f() function variables
        if (strpos($variables[$i],'()')!==false) {
            $ofunc[] = substr($variables[$i],0,strpos($variables[$i],'('));
            $variables[$i] = substr($variables[$i],0,strpos($variables[$i],'('));
        }
    }
    $vlist = implode(',', $variables);
    $flist = implode(',', $ofunc);
    $afunc = parseMathQuiet($a, $vlist, [], $flist);
    $bfunc = parseMathQuiet($b, $vlist, [], $flist);
    if ($afunc === false || $bfunc === false) {
        return false;
    }

    return ($afunc->normalizeTreeString() === $bfunc->normalizeTreeString());
}

function stuansready($stu, $qn, $parts, $anstypes = null, $answerformat = null) {
    if (!isset($stu[$qn])) {
        return false;
    }
    if (!is_array($stu[$qn])) {
        if ($parts === null) { // if not multipart, change it so it looks like one
            $stu[$qn] = [$stu[$qn]];
            $parts = [0];
        } else {
            return false;
        }
    }
    if (!is_array($parts) && is_numeric($parts)) {
        $parts = [$parts];
    }
    if ($anstypes !== null && !is_array($anstypes)) {
        $anstypes = array_map('trim', explode(',', $anstypes));
        if (count($parts)==1 && count($anstypes)==1) {
            // string given as anstypes, and only one part indicated, so use that string as
            // anstype for that part
            $anstypes[$parts[0]] = $anstypes[0];
        }
    }
    foreach ($parts as $part) {
        $ors = array_map('trim', explode(' or ', $part));
        $partok = false;
        foreach ($ors as $v) {
            $blankok = false;
            if (is_string($v) && $v[0]=='~') {
                $blankok = true;
                $v = substr($v,1);
            }
            if (!isset($stu[$qn][$v])) { 
                continue;
            }
            if ($anstypes !== null && $answerformat !== null && $stu[$qn][$v] !== '') {
                $thisaf = '';
                if (is_array($answerformat) && !empty($answerformat[$v])) {
                    $thisaf = $answerformat[$v];
                } else if (!is_array($answerformat)) {
                    $thisaf = $answerformat;
                }
                if (($anstypes[$v] == 'calculated' || $anstypes[$v]=='number') && strpos($thisaf,'checknumeric')!==false) {
                    if (!is_numeric($stu[$qn][$v])) {
                        continue;
                    }
                } else if ($thisaf !== '') {
                    if ($anstypes[$v] == 'calculated' && !checkanswerformat($stu[$qn][$v],$thisaf)) {
                        continue;
                    }
                }
            } 
            //echo $stu[$qn][$v];
            if ($anstypes !== null && ($anstypes[$v] === 'matrix' || $anstypes[$v] === 'calcmatrix') &&
                isset($stu[$qn][$v]) && ($stu[$qn][$v]==='' || strpos($stu[$qn][$v],'||')!==false || 
                $stu[$qn][$v][0] === '|' || $stu[$qn][$v][strlen($stu[$qn][$v])-1] === '|' ||
                strpos($stu[$qn][$v],'NaN')!==false ||
                ($anstypes[$v] === 'matrix' && strpos($stu[$qn][$v],'|')!==false && preg_match('/[^\d\.\-\+E\s\|]/',$stu[$qn][$v])) )
            ) {
                // empty looking matrix entry
                continue;
            }
            if ($anstypes !== null && $anstypes[$v] === 'choices' && !$blankok && $stu[$qn][$v] === 'NA') {
                continue;
            }
            if (isset($stu[$qn][$v]) && ($blankok || (trim($stu[$qn][$v]) !== '' && $stu[$qn][$v] !== ';;;;;;;;'))) {
                $partok = true; 
                break;
            }
        }
        if ($partok === false) {
            return false;
        }
    }
    return true;
}

// returns [matrix elements as 1D array, numrows]
function parseMatrixToArray($str) {
    /*
    Handles:
      Matrices like [(1,2),(3,4)], ((1,2),(3,4)), |(1,2),(3,4)|, and [[1,2],[3,4]]
      Where parens confuse: [(sqrt(4),(2pi)/3),(5,6)]
      1x1 matrix like [3] or [(2pi)/3]
      Stored stuans format: 3|4|5|6  - won't be able to return numrows in this case
    */
    if (strpos($str,'|')!==false && strpos($str,',')===false) {
        // stored stuans format
        return [explode('|',$str), null];
    } else if (strpos($str,',')===false) {
        // 1x1 matrix
        $val = substr($str,1,-1);
        if (strlen($val)>2 && $val[0]=='(') {
            $depth = 0;
            for ($i=0;$i<strlen($val);$i++) {
                if ($val[$i] == '(') { $depth++; }
                if ($val[$i] == ')') { $depth--; }
                if ($depth == 0) {
                    if ($i == strlen($val)-1) {
                        $val = substr($val,1,-1);
                    } else {
                        break;
                    }
                }
            }
        }
        return [[$val], 1];
    } else {
        $out = [];
        $rowcnt = 0;
        $depth = 0;
        $lastcut = 1;
		$bracketpairs = ['('=>')','['=>']'];
		$rowbracket = '';
        $rowendbracket = '';
        for ($i=1;$i<strlen($str)-1;$i++) {
            $c = $str[$i];
			if ($rowbracket === '' && ($c == '(' || $c == '[')) {
				$rowbracket = $c;
				$rowendbracket = $bracketpairs[$c];
			}
            if ($c == $rowbracket) {
                if ($depth == 0) {
                    $lastcut = $i+1;
                }
                $depth++;
            } else if ($c == $rowendbracket) {
                $depth--;
                if ($depth == 0) { // new row 
                    $out[] = trim(substr($str, $lastcut, $i-$lastcut));
                    $rowcnt++;
                }
            } else if ($c == ',') {
                if ($depth == 1) { // new entry in column
                    $out[] = trim(substr($str, $lastcut, $i-$lastcut));
                }
                $lastcut = $i+1;
            }
        }
        if ($depth != 0) { // something wrong
            return [false, null];
        }
        return [$out, $rowcnt];
    }
}

?>

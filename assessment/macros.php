<?php
//IMathAS:  Core randomizers and display macros
//(c) 2006 David Lippman

require_once(__DIR__ . '/../includes/Rand.php');
$RND = new Rand();

array_push($allowedmacros,"exp","sec","csc","cot","sech","csch","coth","nthlog",
 "sinn","cosn","tann","secn","cscn","cotn","rand","rrand","rands","rrands",
 "randfrom","randsfrom","jointrandfrom","diffrandsfrom","nonzerorand",
 "nonzerorrand","nonzerorands","nonzerorrands","diffrands","diffrrands",
 "nonzerodiffrands","nonzerodiffrrands","singleshuffle","jointshuffle",
 "makepretty","makeprettydisp","showplot","addlabel","showarrays","horizshowarrays",
 "showasciisvg","listtoarray","arraytolist","calclisttoarray","sortarray","consecutive",
 "gcd","lcm","calconarray","mergearrays","sumarray","dispreducedfraction","diffarrays",
 "intersectarrays","joinarray","unionarrays","count","polymakepretty",
 "polymakeprettydisp","makexpretty","makexprettydisp","calconarrayif","in_array",
 "prettyint","prettyreal","prettysigfig","roundsigfig","arraystodots","subarray",
 "showdataarray","arraystodoteqns","array_flip","arrayfindindex","fillarray",
 "array_reverse","root","getsnapwidthheight","is_numeric","sign","sgn","prettynegs",
 "dechex","hexdec","print_r","replacealttext","randpythag","changeimagesize","mod",
 "numtowords","randname","randnamewpronouns","randmalename","randfemalename",
 "randnames","randmalenames","randfemalenames","randcity","randcities","prettytime",
 "definefunc","evalfunc","evalnumstr","safepow","arrayfindindices","stringtoarray","strtoupper",
 "strtolower","ucfirst","makereducedfraction","makereducedmixednumber","stringappend",
 "stringprepend","textonimage","addplotborder","addlabelabs","makescinot","today",
 "numtoroman","sprintf","arrayhasduplicates","addfractionaxislabels","decimaltofraction",
 "ifthen","multicalconarray","htmlentities","formhoverover","formpopup","connectthedots",
 "jointsort","stringpos","stringlen","stringclean","substr","substr_count","str_replace",
 "makexxpretty","makexxprettydisp","forminlinebutton","makenumberrequiretimes",
 "comparenumbers","comparefunctions","getnumbervalue","showrecttable","htmldisp",
 "getstuans","checkreqtimes","stringtopolyterms","getfeedbackbasic","getfeedbacktxt",
 "getfeedbacktxtessay","getfeedbacktxtnumber","getfeedbacktxtnumfunc",
 "getfeedbacktxtcalculated","explode","gettwopointlinedata","getdotsdata",
 "getopendotsdata","gettwopointdata","getlinesdata","getineqdata","adddrawcommand",
 "mergeplots","array_unique","ABarray","scoremultiorder","scorestring","randstate",
 "randstates","prettysmallnumber","makeprettynegative","rawurlencode","fractowords",
 "randcountry","randcountries");

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
		$settings[$i-1] = func_get_arg($i);
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
		$settings[4] = str_replace(array('(',')'),'',$settings[4]);
		$lbl = explode(':',$settings[4]);
		$lbl = array_map('evalbasic', $lbl);
	} else {
		$settings[4] = evalbasic($settings[4]);
	}
	if (is_numeric($settings[4]) && $settings[4]>0) {
		$commands .= 'axes('.$settings[4].','.$settings[4].',1';
	} else if (isset($lbl[0]) && is_numeric($lbl[0]) && $lbl[0]>0) {
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
		$grid = array_map('evalbasic', $grid);
	} else {
		$settings[5] = evalbasic($settings[5]);
	}
	if (is_numeric($settings[5]) && $settings[5]>0) {
		$commands .= ','.$settings[5].','.$settings[5];
	} else if (isset($grid[0]) && is_numeric($grid[0]) ) {
		$commands .= ','.$grid[0].','.$grid[1];
	} else {
		$commands .= ',0,0';
		//$commands .= ');';
	}

	if ($noyaxis==true) {
		$commands .= ',1,0,1);';
	} else if ($fqonlyx || $fqonlyy) {
		$commands .= ','.($fqonlyx?'"fq"':1).','.($fqonlyy?'"fq"':1).');';
	} else {
		$commands .= ');';
	}

	if (isset($lbl) && count($lbl)>3) {
		$commands .= "text([{$winxmax},0],\"{$lbl[2]}\",\"aboveleft\");";
		$commands .= "text([0,{$ymax}],\"{$lbl[3]}\",\"belowright\");";
	}
	$absymin = 1E10;
	$absymax = -1E10;
	foreach ($funcs as $function) {
		if ($function=='') { continue;}
        $function = str_replace('\\,','&x44;', $function);
		$function = listtoarray($function);
		//correct for parametric
		$isparametric = false;
		$isineq = false;
		$isxequals = false;
		//has y= when it shouldn't
		if ($function[0][0] == 'y') {
			$function[0] = preg_replace('/^\s*y\s*=?/', '', $function[0]);
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
		} else if ($function[0]{0}=='[') { //strpos($function[0],"[")===0) {
			$isparametric = true;
			$xfunc = makepretty(str_replace("[","",$function[0]));
			$evalxfunc = makeMathFunction($xfunc, "t");
			$yfunc = makepretty(str_replace("]","",$function[1]));
			$evalyfunc = makeMathFunction($yfunc, "t");
			array_shift($function);
			if ($evalxfunc===false || $evalyfunc===false) {continue;}
		} else if ($function[0]{0}=='<' || $function[0]{0}=='>') {
			$isineq = true;
			if ($function[0]{1}=='=') {
				$ineqtype = substr($function[0],0,2);
				$func = makepretty(substr($function[0],2));
			} else {
				$ineqtype = $function[0]{0};
				$func = makepretty(substr($function[0],1));
			}
			$evalfunc = makeMathFunction($func, "x");
			if ($evalfunc===false) {continue;}
		} else if (strlen($function[0])>1 && $function[0]{0}=='x' && ($function[0]{1}=='<' || $function[0]{1}=='>' || $function[0]{1}=='=')) {
			$isxequals = true;
			if ($function[0]{1}=='=') {
				$val = substr($function[0],2);
				if (!is_numeric($val)) {
					// convert to parametric
					$isxequals = false;
					$isparametric = true;
					$yfunc = "t";
					$evalyfunc = makeMathFunction("t", "t");
					$xfunc = makepretty(str_replace('y','t',$val));
					$evalxfunc = makeMathFunction($xfunc, "t");
					if ($evalxfunc===false || $evalyfunc===false) {continue;}
				}
			} else {
				$isineq = true;
				if ($function[0]{2}=='=') {
					$ineqtype = substr($function[0],1,2);
					$val= substr($function[0],3);
				} else {
					$ineqtype = $function[0]{1};
					$val = substr($function[0],2);
				}
			}
		} else {
			$func = makepretty($function[0]);
			$evalfunc = makeMathFunction($func, "x");
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
			$alt .= "<table class=stats><thead><tr><th>x</th><th>y</th></thead></tr><tbody>";
			$alt .= "<tr><td>$val</td><td>$thisymin</td></tr>";
			$alt .= "<tr><td>$val</td><td>$thisymax</td></tr>";
			$alt .= '</tbody></table>';
			$path .= "line([$val,$thisymin],[$val,$thisymax]);";
			$path .= "stroke=\"none\";strokedasharray=\"none\";";
			if ($function[1]=='red' || $function[1]=='green') {
				$path .= "fill=\"trans{$function[1]}\";";
			} else {
				$path .= "fill=\"transblue\";";
			}
			if ($isineq) {
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
		if (isset($function[2]) && $function[2]!='' && is_numeric($function[2])) {
			$xmin = $function[2];
			$domainlimited = true;
		} else {$xmin = $winxmin;}
		if (isset($function[3]) && $function[3]!='') {
			$xmaxarr = explode('!',$function[3]);
			if (is_numeric($xmaxarr[0])) {
				$xmax = $xmaxarr[0];
			} else {
				$xmax = $winxmax;
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
				$stopat = ($domainlimited?10:11);
			}
			if ($xmax != $xmin) {
				$alt .= "<table class=stats><thead><tr><th>x</th><th>y</th></thead></tr><tbody>";
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
			$xrnd = max(0,intval(floor(-log10(abs($xmax-$xmin))-1e-12))+4);
			$yrnd = max(0,intval(floor(-log10(abs($ymax-$ymin))-1e-12))+4);
		}

		$lasty = 0;
		$lastl = 0;
		$px = null;
		$py = null;
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
				$y = $evalyfunc(['t'=>$t]);
				if (isNaN($x) || isNaN($y)) {
					continue;
				}
				$x = round($x,$xrnd);//round(eval("return ($xfunc);"),3);
				$y = round($y,$yrnd);//round(eval("return ($yfunc);"),3);
				if ($xmax != $xmin && $y>$yyaltmin && $y<$yyaltmax) {
					$alt .= "<tr><td>$x</td><td>$y</td></tr>";
				}
			} else {
				$x = $xmin + $dx*$i + (($i<$stopat/2)?1E-10:-1E-10) - (($domainlimited || $_SESSION['graphdisp']==0)?0:5*abs($xmax-$xmin)/$plotwidth);
				if (in_array($x,$avoid)) { continue;}
				//echo $func.'<br/>';
                $y = $evalfunc(['x'=>$x]);
				if (isNaN($y)) {
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
				$y = round($y,$yrnd);//round(eval("return ($func);"),3);
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
					if ($isparametric) {
						$y = $evalyfunc(['t'=>$t-1E-10]);
						$tempy = $evalyfunc(['t'=>$t-$dx/10]);
					} else {
						$y = $evalfunc(['x'=>$x-1E-10]);
						$tempy = $evalfunc(['x'=>$x-$dx/10]);
					}
					if ($tempy<$y) { // going up
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
					$lastl = 0;
                } else { //still out

				}
			} else if ($py>$yymax || $py<$yymin) { //coming or staying in bounds?
				if ($y <= $yymax && $y >= $yymin) { //coming in
					//need to determine which direction.  Let's calculate an extra value
					//and need un-rounded y-value for comparison
					if ($isparametric) {
						$y = $evalyfunc(['t'=>$t-1E-10]);
						$tempy = $evalyfunc(['t'=>$t-$dx/10]);
					} else {
						$y = $evalfunc(['x'=>$x-1E-10]);
						$tempy = $evalfunc(['x'=>$x-$dx/10]);
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

			$pathstr = substr($pathstr,0,-3);
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
			if ($function[1]=='red' || $function[1]=='green') {
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
		} else if (isset($function[5]) && $function[5]=='arrow') {
			$path .= "arrowhead([{$fx[$stopat-2]},{$fy[$stopat-2]}],[$x,$y]);";
			$alt .= "Arrow at ($x,$y). ";
		}
		if (isset($function[4]) && $function[4]=='open') {
			$path .= "dot([{$fx[0]},{$fy[0]}],\"open\");";
			$alt .= "Open dot at ({$fx[0]},{$fy[0]}). ";
		} else if (isset($function[4]) && $function[4]=='closed') {
			$path .= "dot([{$fx[0]},{$fy[0]}],\"closed\");";
			$alt .= "Closed dot at ({$fx[0]},{$fy[0]}). ";
		} else if (isset($function[4]) && $function[4]=='arrow') {
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
	$alt = "Graphs with window x: {$winxmin} to {$winxmax}, y: {$ymin} to {$ymax}. ".$alt;

	if ($_SESSION['graphdisp']==0) {
		return $alt;
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
			$plota = str_replace("' />", $newcmds."' />", $plota);
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
		$num = str_replace('pi','',$num);
		if ($num=='') { $num = 1;}
	}
	preg_match('/initPicture\(([\-\d\.]+),([\-\d\.]+),([\-\d\.]+),([\-\d\.]+)\)/',$plot,$matches);
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
	if (count($xarray)!=count($yarray)) {
		echo "Error: x array and y array need to have the same number of elements";
	}
	$outarr = array();
	for ($i=1; $i<count($xarray); $i++) {
		if ($i==1) {$ed = $startdot;} else {$ed = '';}
		if ($i==count($xarray)-1) {$sd = $enddot;} else {$sd = '';}
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

function showasciisvg($script, $width=200, $height=200, $alt="") {
    if ($alt == '') {
        $alt = "[Graphs generated by this script: $script]";
    }
    if ($_SESSION['graphdisp']==0) {
        return $alt;
    }
    $script = str_replace("'",'"',$script);
    $out = "<embed type='image/svg+xml' align='middle' width='$width' height='$height' script='$script' />";
    $out .= '<span class="sr-only">'.$alt.'</span>';
    return $out;
}


function showarrays() {
	$alist = func_get_args();
	$format = "default";
	$caption = "";
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
		} else {
			$format = $alist[count($alist)-1];
		}
	}
	$ncol = floor(count($alist)/2);
	if ($format !== 'default' && strlen($format) < $ncol) {
		$format = str_repeat($format[0], $ncol);
	}
	if (count($alist)<4 && is_array($alist[0])) {
		for ($i=0;$i<count($alist[0]);$i++) {
			$newalist[] = $alist[0][$i];
			$newalist[] = $alist[1][$i];
		}
		$alist = $newalist;
	}
	$out = '<table class=stats>';
	if ($caption != '') {
		$out .= '<caption>'.Sanitize::encodeStringForDisplay($caption).'</caption>';
	}
	$hashdr = false;
	$maxlength = 0;
	for ($i = 0; $i<$ncol; $i++) {
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
			if ($format == 'default') {
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
		if (!is_array($alist[2*$i+1])) {
			$alist[2*$i+1] = listtoarray($alist[2*$i+1]);
		}
		if (count($alist[2*$i+1])>$maxlength) {
			$maxlength = count($alist[2*$i+1]);
		}
	}
		$out = '<table class=stats>';
	for ($i=0; $i<count($alist)/2; $i++) {
		$out .= "<tr><th scope=\"row\"><b>{$alist[2*$i]}</b></th>";
		$out .= "<td>" . implode("</td><td>",$alist[2*$i+1]) . "</td>";
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
	if ($exp[0]=='+') {
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
	if ($n==0) { echo "Need n &gt; 0";}
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
	return $lst[$GLOBALS['RND']->rand(0,count($lst)-1)];
}


function randsfrom($lst,$n,$ord='def') {
	if (func_num_args()<2) { echo "randsfrom expects 2 arguments"; return 1;}
	if (!is_array($lst)) {
		$lst = listtoarray($lst);
	}
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
		while ($n>count($r)) {
			$r = array_merge($r,$r);
		}
		if ($nonzero) {
			if ($min <= 0 && $max >= 0) {
				array_splice($r,-1*$min/$p,1);
			}
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


function nonzerodiffrands($min,$max,$n=0,$ord='def') {
	if (func_num_args()<3) { echo "nonzerodiffrands expects 3 arguments"; return $min;}
	list($min,$max) = checkMinMax($min, $max, true, 'nonzerodiffrands');
	if ($max == $min) {echo "nonzerodiffrands: Need min&lt;max"; return array_fill(0,$n,$min);}
	if ($n > $max-$min+1 || ($min*$max<=0 && $n>$max-$min)) {
		if ($GLOBALS['myrights']>10) {
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
	if (func_num_args()>1 && ($_SESSION['isteacher'] || isset($GLOBALS['teacherid']))) {
		echo "Warning:  listtoarray expects one argument, more than one provided";
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
		if (is_numeric($a[0])) {
			rsort($a, SORT_NUMERIC);
		} else {
			rsort($a);
		}
	} else {
		if (is_numeric($a[0])) {
			sort($a, SORT_NUMERIC);
		} else {
			sort($a);
		}
	}
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


function gcd($n,$m){ //greatest common divisor
	$m = round(abs($m));
	$n = round(abs($n));
	if(!$m)return$n;
	if(!$n)return$m;
	return $m<$n?gcd($m,$n%$m):gcd($n,$m%$n);
}
function lcm($n, $m) //least common multiple
{
   return round($m*($n/gcd($n,$m)));
}

function dispreducedfraction($n,$d,$dblslash=false,$varinnum=false) {
	return '`'.makereducedfraction($n,$d,$dblslash,$varinnum).'`';
}

function makereducedmixednumber($n,$d) {
	if ($n==0) {return '0';}
	$g = gcd($n,$d);
	$n = $n/$g;
	$d = $d/$g;
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
	$g = gcd($n,$d);
	$n = $n/$g;
	$d = $d/$g;
	if ($d<0) {
		$n = $n*-1;
		$d = $d*-1;
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
	return array_map(my_create_function('$x','return('.$todo.');'),$array);
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
			echo "Invalid variable";
			return false;
		}
	}
	if ($nargs-2 != count($vars)) {
		echo "incorrect number of data arrays";
		return false;
	}
	$cnt = count($args[0]);
	for ($i=1; $i<count($args); $i++) {
		if (count($args[$i]) != $cnt) {
			echo "Unequal array lengths";
			return false;
		}
	}

	$todo = mathphp($todo,implode('|',$vars),false,false);
	if ($todo=='0;') { return 0;}
	for ($i=0;$i<count($vars);$i++) {
		$todo = str_replace('('.$vars[$i].')','($'.$vars[$i].')',$todo);
	}
	$varlist = '$'.implode(',$',$vars);
	$func = my_create_function($varlist, 'return('.$todo.');');
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

	$iffunc = my_create_function('$x','return('.$ifcond.');');

	$tmpfunc = my_create_function('$x','return('.$todo.');');
	foreach($array as $k=>$x) {
		if ($iffunc($x)) {
			$array[$k] = $tmpfunc($x);
		}
	}
	return $array;
}

function sumarray($array) {
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

function prettysigfig($aarr,$sigfig,$comma=',',$choptrailing=false,$orscinot=false) {
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
		if ($a==0) { return 0;}
		if ($a < 0 ) {
			$sign = '-';
			$a *= -1;
		} else {
			$sign = '';
		}

		$v = floor(-log10($a)-1e-12);
		if ($v+$sigfig <= 0) {
			if ($v<-16 && $scinot=='') { //special handling of really huge numbers
				$multof3 = floor(-($v+$sigfig)/3);
				$tmp = round($a/pow(10,$multof3*3), $v+$sigfig+$multof3*3);
				$out[] = $sign.number_format($tmp,0,'.',$comma).str_repeat(',000',$multof3).$scinot;
			} else {
				$out[] = $sign.number_format(round($a,$v+$sigfig),0,'.',$comma).$scinot;
			}
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

function makescinot($n,$d=8,$f="x") {
	if ($n==0) { return "0";}
	$isneg = "";
	if ($n<0) { $isneg = "-"; $n = abs($n);}
	$exp = floor(log10($n));
	if ($d==-1) {
		$mant = round($n/pow(10,$exp),8);
	} else {
		$mant = number_format($n/pow(10,$exp),$d);
	}
	if ($f=="*") {
		return "$isneg $mant * 10^($exp)";
	} else if ($f=="E") {
		return "$isneg $mant E $exp";
	} else {
		return "$isneg $mant xx 10^($exp)";
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
	$out = array();
	for ($i=0;$i<count($x);$i++)  {
		$out[] = $x[$i].','.$y[$i];
	}
	return $out;
}

function arraystodoteqns($x,$y,$color='blue') {
	$out = array();
	for ($i=0;$i<count($x);$i++)  {
		$out[] = $y[$i].','.$color.','.$x[$i].','.$x[$i].','.'closed';
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
	for ($i=0;$i<count($args);$i++) {
		if (strpos($args[$i],':')!==false) {
			$p = explode(':',$args[$i]);
			array_splice($out,count($out),0,array_slice($a,$p[0],$p[1]-$p[0]+1));
		} else {
			$out[] = $a[$args[$i]];
		}
	}
	return $out;
}

function showdataarray($a,$n=1,$format='table') {
	if (!is_array($a)) {
		return '';
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
		$out = '<table class=stats><tbody>';
		$cnt = 0;
		while ($cnt<count($a)) {
			$out .= '<tr>';
			for ($i=0;$i<$n;$i++) {
				if (isset($a[$cnt])) {
					$out .= '<td>'.$a[$cnt].'</td>';
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
$onesth = array(""," first"," second", " third", " fourth", " fifth", " sixth", " seventh", " eighth", " ninth", "tenth"," eleventh", " twelfth", " thirteenth", " fourteenth"," fifteenth", " sixteenth", " seventeenth", " eighteenth"," nineteenth");
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
	$int = floor($num);
	$dec = 	$num-$int;

	if ($int>0) {
		$out .= convertTri($int,0,$doth,$addcommas);
		if (abs($dec)>1e-9) {
			$out .= " and ";
		}
	}
	if (abs($dec)>1e-9) {
		$cnt = 0;
		while (abs($dec-round($dec))>1e-9 && $cnt<9) {
			$dec *= 10;
			$cnt++;
		}
		$out .= convertTri(round($dec),0);
		$out .= ' '.$placevals[$cnt];
		if ($dec!=1) {
			$out .= 's';
		}

	}
	return trim($out);
}

function fractowords($numer,$denom,$options='no') { //options can combine 'mixed','over','by' and 'literal'

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
      } else {
        $bot=numtowords($denom,$doth=true);
      }

      if (abs($numer)==1) {
        return $int.$top.' '.$bot;
      } else {
        return $int.$top.' '.$bot.'s';
      }

    } elseif (strpos($options,'over')!==false) {//over or overby, prefers over
      return $int.numtowords($numer).' over '.numtowords($denom);
    } elseif (strpos($options,'by')!==false) {//by or overby
      return $int.numtowords($numer).' by '.numtowords($denom);
    }
  }
}

$namearray[0] = explode(',',"Aaron,Ahmed,Aidan,Alan,Alex,Alfonso,Andres,Andrew,Antonio,Armando,Arturo,Austin,Ben,Bill,Blake,Bradley,Brayden,Brendan,Brian,Bryce,Caleb,Cameron,Carlos,Casey,Cesar,Chad,Chance,Chase,Chris,Cody,Collin,Colton,Conner,Corey,Dakota,Damien,Danny,Darius,David,Deandre,Demetrius,Derek,Devante,Devin,Devonte,Diego,Donald,Dustin,Dylan,Eduardo,Emanuel,Enrique,Erik,Ethan,Evan,Francisco,Frank,Gabriel,Garrett,Gerardo,Gregory,Ian,Isaac,Jacob,Jaime,Jake,Jamal,James,Jared,Jason,Jeff,Jeremy,Jesse,John,Jordan,Jose,Joseph,Josh,Juan,Julian,Julio,Justin,Juwan,Keegan,Ken,Kevin,Kyle,Landon,Levi,Logan,Lucas,Luis,Malik,Manuel,Marcus,Mark,Matt,Micah,Michael,Miguel,Nate,Nick,Noah,Omar,Paul,Quinn,Randall,Ricardo,Ricky,Roberto,Roy,Russell,Ryan,Salvador,Sam,Santos,Scott,Sergio,Shane,Shaun,Skyler,Spencer,Stephen,Taylor,Tevin,Todd,Tom,Tony,Travis,Trent,Trevor,Trey,Tristan,Tyler,Wade,Warren,Wyatt,Zach");
$namearray[1] = explode(',',"Adriana,Adrianna,Alejandra,Alexandra,Alexis,Alice,Alicia,Alma,Amanda,Amber,Amy,Andrea,Angela,Anna,April,Ariana,Ashley,Ashton,Autumn,Bianca,Bria,Brianna,Brittany,Brooke,Caitlyn,Carissa,Carolyn,Carrie,Cassandra,Catherine,Chasity,Chelsea,Chloe,Christy,Ciara,Claudia,Colleen,Courtney,Cristina,Crystal,Dana,Danielle,Delaney,Destiny,Diana,Elizabeth,Emily,Emma,Erica,Erin,Esmeralda,Gabrielle,Guadalupe,Haley,Hanna,Heather,Hillary,Holly,Jacqueline,Jamie,Jane,Jasmine,Jenna,Jennifer,Jessica,Julia,Karen,Karina,Karissa,Karla,Kathryn,Katie,Kayla,Kelly,Kelsey,Kendra,Kimberly,Kori,Kristen,Kristina,Krystal,Kylie,Laura,Lauren,Leah,Linda,Lindsey,Mackenzie,Madison,Maggie,Mariah,Marissa,Megan,Melissa,Meredith,Michelle,Mikayla,Miranda,Molly,Monique,Morgan,Naomi,Natalie,Natasha,Nicole,Nina,Noelle,Paige,Patricia,Rachael,Raquel,Rebecca,Renee,Riley,Rosa,Samantha,Sarah,Savannah,Shannon,Shantel,Sierra,Sonya,Sophia,Stacy,Stephanie,Summer,Sydney,Tatiana,Taylor,Tiana,Tiffany,Valerie,Vanessa,Victoria,Vivian,Wendy,Whitney,Zoe");

$cityarray = explode(',','Los Angeles,Dallas,Houston,Atlanta,Detroit,San Francisco,Minneapolis,St. Louis,Baltimore,Pittsburg,Cincinnati,Cleveland,San Antonio,Las Vegas,Milwaukee,Oklahoma City,New Orleans,Tucson,New York City,Chicago,Philadelphia,Miami,Boston,Phoenix,Seattle,San Diego,Tampa,Denver,Portland,Sacramento,Orlando,Kansas City,Nashville,Memphis,Hartford,Salt Lake City');

$countryarray = explode(',','Afghanistan,Albania,Algeria,Andorra,Angola,Antigua & Deps,Argentina,Armenia,Australia,Austria,Azerbaijan,Bahamas,Bahrain,Bangladesh,Barbados,Belarus,Belgium,Belize,Benin,Bhutan,Bolivia,Bosnia Herzegovina,Botswana,Brazil,Brunei,Bulgaria,Burkina,Burundi,Cambodia,Cameroon,Canada,Cape Verde,Central African Rep,Chad,Chile,China,Colombia,Comoros,Congo,Congo,Costa Rica,Croatia,Cuba,Cyprus,Czech Republic,Denmark,Djibouti,Dominica,Dominican Republic,East Timor,Ecuador,Egypt,El Salvador,Equatorial Guinea,Eritrea,Estonia,Ethiopia,Fiji,Finland,France,Gabon,Gambia,Georgia,Germany,Ghana,Greece,Grenada,Guatemala,Guinea,Guinea-Bissau,Guyana,Haiti,Honduras,Hungary,Iceland,India,Indonesia,Iran,Iraq,Ireland,Israel,Italy,Ivory Coast,Jamaica,Japan,Jordan,Kazakhstan,Kenya,Kiribati,North Korea,South Korea,Kosovo,Kuwait,Kyrgyzstan,Laos,Latvia,Lebanon,Lesotho,Liberia,Libya,Liechtenstein,Lithuania,Luxembourg,Macedonia,Madagascar,Malawi,Malaysia,Maldives,Mali,Malta,Marshall Islands,Mauritania,Mauritius,Mexico,Micronesia,Moldova,Monaco,Mongolia,Montenegro,Morocco,Mozambique,Myanmar,Namibia,Nauru,Nepal,Netherlands,New Zealand,Nicaragua,Niger,Nigeria,Norway,Oman,Pakistan,Palau,Panama,Papua New Guinea,Paraguay,Peru,Philippines,Poland,Portugal,Qatar,Romania,Russia,Rwanda,St Kitts & Nevis,St Lucia,Saint Vincent & the Grenadines,Samoa,San Marino,Sao Tome & Principe,Saudi Arabia,Senegal,Serbia,Seychelles,Sierra Leone,Singapore,Slovakia,Slovenia,Solomon Islands,Somalia,South Africa,South Sudan,Spain,Sri Lanka,Sudan,Suriname,Swaziland,Sweden,Switzerland,Syria,Taiwan,Tajikistan,Tanzania,Thailand,Togo,Tonga,Trinidad & Tobago,Tunisia,Turkey,Turkmenistan,Tuvalu,Uganda,Ukraine,United Arab Emirates,United Kingdom,United States,Uruguay,Uzbekistan,Vanuatu,Vatican City,Venezuela,Vietnam,Yemen,Zambia,Zimbabwe');

function randcities($n=1) {
	global $cityarray;
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

function randstates($n=1) {
	$states = array("Alabama","Alaska","Arizona","Arkansas","California","Colorado","Connecticut","Delaware","Dist. of Columbia","Florida","Georgia","Hawaii","Idaho","Illinois","Indiana","Iowa","Kansas","Kentucky","Louisiana","Maine","Maryland","Massachusetts","Michigan","Minnesota","Mississippi","Missouri","Montana","Nebraska","Nevada","New Hampshire","New Jersey","New Mexico","New York","North Carolina","North Dakota","Ohio","Oklahoma","Oregon","Pennsylvania","Rhode Island","South Carolina","South Dakota","Tennessee","Texas","Utah","Vermont","Virginia","Washington","West Virginia","Wisconsin","Wyoming");

	$c = count($states);
	if ($n==1) {
		return $states[$GLOBALS['RND']->rand(0,$c-1)];
	} else {
		$GLOBALS['RND']->shuffle($states);
		return array_slice($states,0,$n);
	}
}
function randstate() {
	return randstates(1);
}
function randcity() {
	return randcities(1);
}
function randcountry() {
  return randcountries(1);
}

function randnames($n=1,$gender=2) {
	global $namearray;
	if ($n==1) {
		if ($gender==2) {
			$gender = $GLOBALS['RND']->rand(0,1);
		}
		return $namearray[$gender][$GLOBALS['RND']->rand(0,137)];
	} else {
		$out = array();
		$locs = diffrands(0,137,$n);
		for ($i=0; $i<$n;$i++) {
			if ($gender==2) {
				$gender = $GLOBALS['RND']->rand(0,1);
			}
			$out[] = $namearray[$gender][$locs[$i]];
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
				$outst = "$hrs hours" . ($hrs!=1 ? 's':'');
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
			$outst = "$time second". ($sec!=1 ? 's':'');
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

function evalnumstr($str) {
    return evalMathParser($str);
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
		$func = makeMathFunction($func, implode(',', $vars));
		foreach ($vars as $i=>$var) {
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

function today($str = "F j, Y") {
	return (date($str));
}

function numtoroman($n,$uc=true) {
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


//adapted from http://www.mindspring.com/~alanh/fracs.html
function decimaltofraction($d,$format="fraction",$maxden = 10000000) {
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
		$numerators[$i] = $L2 * $numerators[$i-1] + $numerators[$i-2];
		//if (Math.abs(numerators[i]) > maxNumerator) return;
		$denominators[$i] = $L2 * $denominators[$i-1] + $denominators[$i-2];
		if (abs($denominators[$i])>$maxden) {
			break;
		}
		$calcD = $numerators[$i] / $denominators[$i];
		if ($calcD == $prevCalcD) { break; }

		//appendFractionsOutput(numerators[i], denominators[i]);

		//if ($calcD == $d) { break;}
		if (abs($calcD - $d)<1e-14) { break;}

		$prevCalcD = $calcD;

		$d2 = 1/($d2-$L2);
    }
	if (abs($numerators[$i]/$denominators[$i] - $d)>1e-10) {
		return $d;
    }
	if ($format=="mixednumber") {
		$w = floor($numerators[$i]/$denominators[$i]);
		if ($w>0) {
			$n = $numerators[$i] - $w*$denominators[$i];
			return "{$sign}$w $n/".$denominators[$i];
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
		$arr[$k] = str_replace(array('-', ' '),'',$num);
	}
	$uniq = array_unique($arr);
	$out = array();
	foreach ($uniq as $num) {
		$nummatch = count(array_keys($arr,$num));
		$out[] = "#$num,=$nummatch";
	}
	return implode(',',$out);
}

function evalbasic($str) {
	global $myrights;
	$str = str_replace(',','',$str);
	$str = str_replace('pi','3.141592653',$str);
	$str = clean($str);
	if (is_numeric($str)) {
		return $str;
	} else if (preg_match('/[^\d+\-\/\*\.]/',$str)) {
		return $str;
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
			if (!isset($ret) || !is_numeric($ret)) {
				return 0;
			}
		}
		return $ret;
	}
}

function formhoverover($label,$tip) {
	if (function_exists('filter')) {
		//return '<span class="link" onmouseover="tipshow(this,\''.str_replace("'","\\'",htmlentities(filter($tip))).'\')" onmouseout="tipout()">'.$label.'</span>';
		return '<span role="button" tabindex="0" class="link" data-tip="'.htmlentities(filter($tip)).'" onmouseover="tipshow(this)" onfocus="tipshow(this)" onmouseout="tipout()" onblur="tipout()">'.$label.'</span>';

	} else {
		///return '<span class="link" onmouseover="tipshow(this,\''.str_replace("'","\\'",htmlentities($tip)).'\')" onmouseout="tipout()">'.$label.'</span>';
		return '<span role="button" tabindex="0" class="link" data-tip="'.htmlentities($tip).'" onmouseover="tipshow(this)" onfocus="tipshow(this)" onmouseout="tipout()" onblur="tipout()">'.$label.'</span>';

	}
}

function formpopup($label,$content,$width=600,$height=400,$type='link',$scroll='null',$id='popup',$ref='') {
	global $urlmode;
	$labelSanitized = Sanitize::encodeStringForDisplay($label);
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
		return str_replace('<img', '<img class="clickable" onClick="'.$rec.'popupwindow(\''.$id.'\',\''.str_replace('\'','\\\'',htmlentities($content)).'\','.$width.','.$height.$scroll.')"',$labelSanitized);
	} else {
		if ($type=='link') {
			return '<span class="link" onClick="'.$rec.'popupwindow(\''.$id.'\',\''.str_replace('\'','\\\'',htmlentities($content)).'\','.$width.','.$height.$scroll.')">'.$labelSanitized.'</span>';
		} else if ($type=='button') {
			if (substr($content,0,31)=='http://www.youtube.com/watch?v=') {
				$content = $GLOBALS['basesiteurl'] . "/assessment/watchvid.php?url=".Sanitize::encodeUrlParam($content);
				$width = 660;
				$height = 525;
			}
			return '<span class="spanbutton" onClick="'.$rec.'popupwindow(\''.$id.'\',\''.str_replace('\'','\\\'',htmlentities($content)).'\','.$width.','.$height.$scroll.')">'.$labelSanitized.'</span>';
		}
	}
}

function forminlinebutton($label,$content,$style='button',$outstyle='block') {
	$r = uniqid();
	$label = str_replace('"','',$label);
	$common = 'id="inlinebtn'.$r.'" aria-controls="inlinebtnc'.$r.'" aria-expanded="false" value="'.$label.'" onClick="toggleinlinebtn(\'inlinebtnc'.$r.'\', \'inlinebtn'.$r.'\');"';
	if ($style=='classic') {
		$out = '<input type="button" '.$common.'/>';
	} else if ($style=='link') {
		$out = '<span class="link" '.$common.'>'.$label.'</span>';
	} else {
		$out = '<span class="spanbutton" '.$common.'>'.$label.'</span>';
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
	}
	$arr = explode('U',$str);
	$out = array();
	foreach ($arr as $v) {
		$v = trim($v);
		$sm = $v[0];
		$em = $v[strlen($v)-1];
		$pts = explode(',',substr($v,1,strlen($v)-2));
		if ($pts[0]=='-oo') {
			if ($pts[1]=='oo') {
				$out[] = '"all real numbers"';
			} else {
				$out[] = $var . ($em==']'?'le':'lt') . $pts[1];
			}
		} else if ($pts[1]=='oo') {
			$out[] = $var . ($sm=='['?'ge':'gt') . $pts[0];
		} else {
			$out[] = $pts[0] . ($sm=='['?'le':'lt') . $var . ($em==']'?'le':'lt') . $pts[1];
		}
	}
	return implode(' \\ "or" \\ ',$out);
}

function cleanbytoken($str,$funcs = array()) {
	if (is_array($str)) { return $str;} //avoid errors by just skipping this if called with an array somehow
	$str = str_replace('`', '', $str);
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
	$parts = preg_split('/(<=|>=|=|,|<|>|&gt;|&lt;|&ge;|&le;|&ne;|\blt\b|\bgt\b|\ble\b|\bge\b|\bne\b|\bor\b)/',$str,-1,PREG_SPLIT_DELIM_CAPTURE);
	$finalout = array();
	for ($k=0;$k<count($parts);$k+=2) {
		$finalout = array();
		$substr = $parts[$k];
		if (trim($substr)=='') {$parts[$k] = ''; continue;}
		$tokens = cleantokenize(trim($substr),$funcs);
		//print_r($tokens);
		$out = array();
		$lasti = count($tokens)-1;
		for ($i=0; $i<=$lasti; $i++) {
			$token = $tokens[$i];
			$lastout = count($out)-1;
			if ($token[1]==3 && $token[0]==='0') { //is the number 0 by itself
				$isone = 0;
				if ($lastout>-1) { //if not first character
					if ($out[$lastout] != '^') {
						//( )0, + 0, x0
						while ($lastout>-1 && $out[$lastout]!= '+' && $out[$lastout]!= '-') {
							array_pop($out);
							$lastout--;
						}
						if ($lastout>-1) {
							array_pop($out);
						}

					} else if ($out[$lastout] == '^') {
						$isone = 2;
						if ($lastout>=2 && ($out[$lastout-2]=='+'|| $out[$lastout-2]=='-')) {
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
					}
				}
				if ($i<$lasti) { //if not last character
					if ($tokens[$i+1][0]=='^') {
						//0^3
						$i+=2; //skip over ^ and 3
					} else if ($isone) {
						if ($tokens[$i+1][0]!= '+' && $tokens[$i+1][0]!= '-' && $tokens[$i+1][0]!= '/') {
							if ($isone==2) {
								array_pop($out);  //pop the 1 we added since it apperears to be multiplying
							}
							if ($tokens[$i+1][0]=='*') {  //x^0*y
								$i++;
							}
						}
					} else {
						while ($i<$lasti && $tokens[$i+1][0]!= '+' && $tokens[$i+1][0]!= '-') {
							$i++;
						}
					}
				}
			} else if ($token[1]==3 && $token[0]==='1') {
				$dontuse = false;
				if ($lastout>-1) { //if not first character
					if ($out[$lastout] != '^' && $out[$lastout] != '/' && $out[$lastout]!='+' && $out[$lastout]!='-' && $out[$lastout]!=' ') {
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
						}
					}
				}
				if ($i<$lasti) { //if not last character
					if ($tokens[$i+1][0]=='^') {
						//1^3
						$i+=2; //skip over ^ and 3
					} else if ($tokens[$i+1][0]=='*') {
						$i++;  //skip over *
						$dontuse = true;
					} else if ($tokens[$i+1][0]!= '+' && $tokens[$i+1][0]!= '-' && $tokens[$i+1][0]!= '/' && !is_numeric($tokens[$i+1][0])) {
						// 1x, 1(), 1sin
						if ($lastout<2 || ($out[$lastout-1] != '^' || $out[$lastout] != '-')) { //exclude ^-1 case
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
			if ($i<$lasti && (($token[1]==3 && $tokens[$i+1][1]==3) || ($token[1]==4 && $tokens[$i+1][1]==4))) {
				$out[] = ' ';
			}
		}
		if ($out[0]=='+') {
			array_shift($out);
		}
		if (count($out)==0) {
			$finalout[] = '0';
		} else {
			$finalout[] = implode('',$out);
		}
		$parts[$k] = implode('',$finalout);
	}
	return str_replace('`',"'", implode(' ',$parts));
}


function cleantokenize($str,$funcs) {
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
		if ($c>="a" && $c<="z" || $c>="A" && $c<="Z") {
			//is a string or function name

			$intype = 2; //string like function name
			do {
				$out .= $c;
				$i++;
				if ($i==$len) {break;}
				$c = $str[$i];
			} while ($c>="a" && $c<="z" || $c>="A" && $c<="Z" || $c=='_'); // took out : || $c>='0' && $c<='9'  don't need sin3 type function names for cleaning
			//check if it's a special word
			if ($out=='e') {
				$intype = 3;
			} else if ($out=='pi') {
				$intype = 3;
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
		} else if (($c>='0' && $c<='9') || ($c=='.'  && ($str[$i+1]>='0' && $str[$i+1]<='9')) ) { //is num
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
				echo "unmatched parens/brackets - likely will cause an error";
			} else {
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
			$c = $str[$i];
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


function comparenumbers($a,$b,$tol='.001') {
	if ($tol[0]=='|') {
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
	if ($tol[0]=='|') {
		$abstolerance = floatval(substr($tol,1));
	}
	$type = "expression";
	if (strpos($a, '=')!==false && strpos($b, '=')!==false) {
		$type = "equation";
	}
	$fromto = listtoarray($domain);
	$variables = listtoarray($vars);
	$vlist = implode(",",$variables);
	for ($i = 0; $i < 20; $i++) {
		for($j=0; $j < count($variables); $j++) {
			if (isset($fromto[2]) && $fromto[2]=="integers") {
				$tps[$i][$j] = $GLOBALS['RND']->rand($fromto[0],$fromto[1]);
			} else if (isset($fromto[2*$j+1])) {
				$tps[$i][$j] = $fromto[2*$j] + ($fromto[2*$j+1]-$fromto[2*$j])*$GLOBALS['RND']->rand(0,499)/500.0 + 0.001;
			} else {
				$tps[$i][$j] = $fromto[0] + ($fromto[1]-$fromto[0])*$GLOBALS['RND']->rand(0,499)/500.0 + 0.001;
			}
		}
	}
	if ($type=='equation') {
		if (substr_count($a, '=')!=1) {return false;}
		$a = preg_replace('/(.*)=(.*)/','$1-($2)',$a);
		if (substr_count($b, '=')!=1) {return false;}
		$b = preg_replace('/(.*)=(.*)/','$1-($2)',$b);
	}

	$afunc = makeMathFunction($a, $vlist);
	$bfunc= makeMathFunction($b, $vlist);
	if ($afunc === false || $bfunc === false) {
		if (isset($GLOBALS['teacherid'])) {
			echo "<p>Debug info: one function failed to compile.</p>";
		}
		return false;
	}

	$cntnana = 0;
	$cntnanb = 0;
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
		if (isNaN($ansa)) {$cntnana++; if (isNaN($ansb)) {$cntnanb++;}; continue;} //avoid NaN problems
		if (isNaN($ansb)) {$cntnanb++; continue;}

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

	if (abs($cntnana - $cntnanb)>1) {
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

function getfeedbackbasic($correct,$wrong,$thisq,$partn=null) {
	global $rawscores,$imasroot,$staticroot;
	if (isset($GLOBALS['testsettings']['testtype']) && ($GLOBALS['testsettings']['testtype']=='NoScores' || $GLOBALS['testsettings']['testtype']=='EndScore')) {
		return '';
	}
	if (isset($GLOBALS['assessUIver']) && $GLOBALS['assessUIver'] > 1) {
		$val = $GLOBALS['assess2-curq-iscorrect'];
		if ($partn !== null && is_array($GLOBALS['assess2-curq-iscorrect'])) {
			$res = $GLOBALS['assess2-curq-iscorrect'][$partn];
		} else {
			$res = $GLOBALS['assess2-curq-iscorrect'];
		}
		if ($res > 0 && $res < 1) {
			$res = 0;
		}
	} else {
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
	if ($stu===null) {
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
	if ($stu==null || trim($stu)=='') {
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
	if ($stu !== null) {
		$stu = preg_replace('/[^\-\d\.e]/','',$stu);
	}
	if ($stu===null) {
		return " ";
	} else if (!is_numeric($stu)) {
		return '<div class="feedbackwrap incorrect"><img src="'.$staticroot.'/img/redx.gif" alt="Incorrect"/> ' . _("This answer does not appear to be a valid number.") . '</div>';
	} else {
		if ($tol[0]=='|') {
			$abstol = true;
			$tol = substr($tol,1);
		} else {
			$abstol =false;
		}
		$match = -1;
		if (!is_array($partial)) { $partial = listtoarray($partial);}
		for ($i=0;$i<count($partial);$i+=2) {
			if (!is_numeric($partial[$i])) {
				$partial[$i] = evalMathParser($partial[$i]);
			}
			if ($abstol) {
				if (abs($stu-$partial[$i]) < $tol + 1E-12) { $match = $i; break;}
			} else {
				if (abs($stu - $partial[$i])/(abs($partial[$i])+.0001) < $tol+ 1E-12) {$match = $i; break;}
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

function getfeedbacktxtcalculated($stu, $stunum, $partial, $fbtxt, $deffb='Incorrect', $answerformat = '', $requiretimes = '', $tol=.001) {
	global $imasroot,$staticroot;
	if (isset($GLOBALS['testsettings']['testtype']) && ($GLOBALS['testsettings']['testtype']=='NoScores' || $GLOBALS['testsettings']['testtype']=='EndScore')) {
		return '';
	}
	if ($stu===null) {
		return " ";
	} else {
		if ($tol[0]=='|') {
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
			if ($abstol) {
				if (abs($stunum-$partial[$i]) < $tol + 1E-12) { $match = $i; break;}
			} else {
				if (abs($stunum - $partial[$i])/(abs($partial[$i])+.0001) < $tol+ 1E-12) {$match = $i; break;}
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
		if ($tol[0]=='|') {
			$abstol = true;
			$tol = substr($tol,1);
		} else {
			$abstol =false;
		}
		$type = "expression";
		if (strpos($stu, '=')!==false && strpos($stu, '=')!==false) {
			$type = "equation";
		}
		$stuorig = $stu;
		$stu = str_replace(array('[',']'),array('(',')'), $stu);
		if ($type=='equation') {
			$stu = preg_replace('/(.*)=(.*)/','$1-($2)',$stu);
		}

		$fromto = listtoarray($domain);
		$variables = listtoarray($vars);
		$vlist = implode(",",$variables);
		$origstu = $stu;
		$stufunc = makeMathFunction(makepretty($stu), $vlist);
		if ($stufunc===false) {
			return '<div class="feedbackwrap incorrect"><img src="'.$staticroot.'/img/redx.gif" alt="Incorrect"/> '.$deffb.'</div>';
		}

		$numpts = 20;
		for ($i = 0; $i < $numpts; $i++) {
			for($j=0; $j < count($variables); $j++) {
				if (isset($fromto[2]) && $fromto[2]=="integers") {
					$tps[$i][$j] = $GLOBALS['RND']->rand($fromto[0],$fromto[1]);
				} else if (isset($fromto[2*$j+1])) {
					$tps[$i][$j] = $fromto[2*$j] + ($fromto[2*$j+1]-$fromto[2*$j])*$GLOBALS['RND']->rand(0,499)/500.0 + 0.001;
				} else {
					$tps[$i][$j] = $fromto[0] + ($fromto[1]-$fromto[0])*$GLOBALS['RND']->rand(0,499)/500.0 + 0.001;
				}
			}
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
			$b = $partial[$k];
			if ($type=='equation') {
				if (substr_count($b, '=')!=1) {continue;}
				$b = preg_replace('/(.*)=(.*)/','$1-($2)',$b);
			}
			$origb = $b;
			$bfunc = makeMathFunction(makepretty($b), $vlist);
			if ($bfunc === false) {
				//parse error - skip it
				continue;
			}
			$cntnanb = 0;
			$ratios = array();
			for ($i = 0; $i < $numpts; $i++) {
				$varvals = array();
				for($j=0; $j < count($variables); $j++) {
					$varvals[$variables[$j]] = $tps[$i][$j];
				}
				$ansb = $bfunc($varvals);

				//echo "real: $ansa, my: $ansb <br/>";
				if (isNaN($ansb)) {$cntnanb++; continue;}
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
			if (abs($cntnana - $cntnanb)>1) {
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

function gettwopointlinedata($str,$xmin=-5,$xmax=5,$ymin=-5,$ymax=5,$w=300,$h=300) {
	return gettwopointdata($str,'line',$xmin,$xmax,$ymin,$ymax,$w,$h);
}
function gettwopointdata($str,$type,$xmin=-5,$xmax=5,$ymin=-5,$ymax=5,$w=300,$h=300) {
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
	} else if ($type=='circle') {
		$code = 7;
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
			$outpts[] = array($pts[1], $pts[2], $pts[3], $pts[4]);
		}
	}
	return $outpts;
}

function getineqdata($str,$type='linear',$xmin=-5,$xmax=5,$ymin=-5,$ymax=5,$w=300,$h=300) {
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

function getdotsdata($str,$xmin=-5,$xmax=5,$ymin=-5,$ymax=5,$w=300,$h=300) {
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
function getopendotsdata($str,$xmin=-5,$xmax=5,$ymin=-5,$ymax=5,$w=300,$h=300) {
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
function getlinesdata($str,$xmin=-5,$xmax=5,$ymin=-5,$ymax=5,$w=300,$h=300) {
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
	if ($xmax - $xmin>0) {
		$newwidth = ($xmax - $xmin)*(round($snapparts[0]*($width-2*$imgborder)/($xmax - $xmin))/$snapparts[0]) + 2*$imgborder;
	} else {
		$newwidth = $width;
	}
	if ($ymax - $ymin>0) {
		$newheight = ($ymax - $ymin)*(round($snapparts[1]*($height-2*$imgborder)/($ymax - $ymin))/$snapparts[1]) + 2*$imgborder;
	} else {
		$newheight = $height;
	}
	return array($newwidth,$newheight);
}

function mod($p,$n) {
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
    $newans = $answer;
    if ($weights !== null) {
        if (!is_array($weights)) {
            $weights = explode(',', $weights);
        }
        $newweights = $weights;
    }
	foreach ($swap as $k=>$sw) {
		$swap[$k] = explode(';', $sw);
		foreach ($swap[$k] as $i=>$s) {
			$swap[$k][$i] = listtoarray($s);
		}
	}
	foreach ($swap as $sw) {
		for ($i=0;$i<count($sw);$i++) {
			$tofind = $stua[$sw[$i][0]];
			$loc = -1;
			for ($k=0;$k<count($sw);$k++) {
				if ($type=='string' && trim(strtolower($tofind))==trim(strtolower($newans[$sw[$k][0]]))) {
					$loc = $k; break;
				} else if ($type=='number' && abs($tofind - $newans[$sw[$k][0]])<0.01) {
					$loc = $k; break;
				}
			}
			if ($loc>-1 && $i!=$loc) {
				//want to swap entries from $sw[$loc] with sw[$i] and swap $answer values
                $tmp = array();
                $tmpw = array();
				foreach ($sw[$i] as $k=>$v) {
                    $tmp[$k] = $newans[$v];
                    if ($weights !== null) {
                        $tmpw[$k] = $newweights[$v];
                    }
				}
				foreach ($sw[$loc] as $k=>$v) {
                    $newans[$sw[$i][$k]] = $newans[$v];
                    if ($weights !== null) {
                        $newweights[$sw[$i][$k]] = $newweights[$v];
                    }
				}
				foreach ($tmp as $k=>$v) {
                    $newans[$sw[$loc][$k]] = $tmp[$k];
                    if ($weights !== null) {
                        $newweights[$sw[$loc][$k]] = $tmpw[$k];
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
	$reqsigfigoffset = 0;
	$reqsigfigparts = explode('+-',$reqsigfigs);
	$reqsigfigs = $reqsigfigparts[0];
	$sigfigscoretype = array('abs',0);
	if (count($reqsigfigparts)>1) {
		if (substr($reqsigfigparts[1], -1)=='%') {
			$sigfigscoretype = array('rel', substr($reqsigfigparts[1], 0, -1));
		} else {
			$sigfigscoretype = array('abs',$reqsigfigparts[1]);
		}
	}
	if ($reqsigfigs[0]=='=') {
		$exactsigfig = true;
		$reqsigfigs = substr($reqsigfigs,1);
	} else if ($reqsigfigs[0]=='[') {
		$exactsigfig = false;
		$reqsigfigparts = listtoarray(substr($reqsigfigs,1,-1));
		$reqsigfigs = $reqsigfigparts[0];
		$reqsigfigoffset = $reqsigfigparts[1] - $reqsigfigparts[0];
	} else {
		$exactsigfig = false;
	}
	return array($reqsigfigs, $exactsigfig, $reqsigfigoffset, $sigfigscoretype);
}

function checksigfigs($givenans, $anans, $reqsigfigs, $exactsigfig, $reqsigfigoffset, $sigfigscoretype) {
	if ($givenans*$anans < 0) { return false;} //move on if opposite signs
	if ($anans!=0) {
		$v = -1*floor(-log10(abs($anans))-1e-12) - $reqsigfigs;
	}
	$epsilon = (($anans==0||abs($anans)>1)?1E-12:(abs($anans)*1E-12));
	if ($sigfigscoretype[0]=='abs') {
		$sigfigscoretype[1] = max(pow(10,$v)/2, $sigfigscoretype[1]);
	} else if ($sigfigscoretype[1]/100 * $anans < pow(10,$v)/2) {
        // relative tolerance, but too small
        $sigfigscoretype = ['abs', pow(10,$v)/2];
    }
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
			$gadploc = strpos($givenans,'.');
			if ($gadploc===false) {$gadploc = strlen($givenans);}
			if ($anans != 0 && $v < 0 && strlen($givenans) - $gadploc-1 + $v < 0) { return false; } //not enough decimal places
			if ($anans != 0 && $reqsigfigoffset>0 && strlen($givenans) - $gadploc-1 + $v>$reqsigfigoffset) {return false;} //too many sigfigs
		} else {
			$absgivenans = str_replace('-','',$givenans);
			$gadploc = strpos($absgivenans,'.');
			if ($gadploc===false) { //no decimal place
				if (strlen(rtrim($absgivenans,'0')) != $reqsigfigs) { return false;}
			} else {
				if (abs($givenans)<1) {
					if (strlen(ltrim(substr($absgivenans,$gadploc+1),'0')) != $reqsigfigs) { return false;}
				} else {
					if (strlen(ltrim($absgivenans,'0'))-1 != $reqsigfigs) { return false;}
				}
			}
		}
	}
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
		$exp = ceil(-log10($val));
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

?>

<?php
//IMathAS:  Core randomizers and display macros
//(c) 2006 David Lippman



array_push($allowedmacros,"exp","sec","csc","cot","sech","csch","coth","nthlog","sinn","cosn","tann","secn","cscn","cotn","rand","rrand","rands","rrands","randfrom","randsfrom","jointrandfrom","diffrandsfrom","nonzerorand","nonzerorrand","nonzerorands","nonzerorrands","diffrands","diffrrands","nonzerodiffrands","nonzerodiffrrands","singleshuffle","jointshuffle","makepretty","makeprettydisp","showplot","addlabel","showarrays","horizshowarrays","showasciisvg","listtoarray","arraytolist","calclisttoarray","sortarray","consecutive","gcd","lcm","calconarray","mergearrays","sumarray","dispreducedfraction","diffarrays","intersectarrays","joinarray","unionarrays","count","polymakepretty","polymakeprettydisp","makexpretty","makexprettydisp","calconarrayif","in_array","prettyint","prettyreal","prettysigfig","arraystodots","subarray","showdataarray","arraystodoteqns","array_flip","arrayfindindex","fillarray","array_reverse","root","getsnapwidthheight","is_numeric","sign","prettynegs","dechex","hexdec","print_r","replacealttext");
array_push($allowedmacros,"numtowords","randname","randnamewpronouns","randmalename","randfemalename","randnames","randmalenames","randfemalenames","randcity","randcities","prettytime","definefunc","evalfunc","safepow","arrayfindindices","stringtoarray","strtoupper","strtolower","ucfirst","makereducedfraction","makereducedmixednumber","stringappend","stringprepend","textonimage","addplotborder","addlabelabs","makescinot","today","numtoroman","sprintf","arrayhasduplicates","addfractionaxislabels","decimaltofraction","ifthen","multicalconarray","htmlentities","formhoverover","formpopup","connectthedots","jointsort","stringpos","stringlen","stringclean","substr","substr_count","str_replace","makexxpretty","makexxprettydisp","forminlinebutton","makenumberrequiretimes","comparenumbers","comparefunctions","getnumbervalue","showrecttable","htmldisp","getstuans","checkreqtimes","stringtopolyterms","getfeedbackbasic","getfeedbacktxt","getfeedbacktxtessay","getfeedbacktxtnumber","getfeedbacktxtnumfunc","getfeedbacktxtcalculated","explode","gettwopointlinedata","getdotsdata","gettwopointdata","getlinesdata","adddrawcommand","mergeplots","array_unique","ABarray","scoremultiorder","scorestring","randstate","randstates","prettysmallnumber");
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
        for($i=0;$i<$len;$i++) {$str_array[]=$str{$i};}
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
	$ymin = $settings[2];
	$ymax = $settings[3];
	$noyaxis = false;
	if (is_numeric($ymin) && is_numeric($ymax) && $ymin==0 && $ymax==0) {
		$ymin = -0.5;
		$ymax = 0.5;
		$noyaxis = true;
		$settings[2] = -0.5;
		$settings[3] = 0.5;
	}
	$xxmin = $settings[0] - 5*($settings[1] - $settings[0])/$settings[6];
	$xxmax = $settings[1] + 5*($settings[1] - $settings[0])/$settings[6];
	$yymin = $settings[2] - 5*($settings[3] - $settings[2])/$settings[7];
	$yymax = $settings[3] + 5*($settings[3] - $settings[2])/$settings[7];
	$yminauto = false;
	$ymaxauto = false;
	if (substr($ymin,0,4)=='auto') {
		$yminauto = true;
		if (strpos($ymin,':')!==false) {
			$ypts = explode(':',$ymin);
			$ymin = $ypts[1];
		} else {
			$ymin = -5;
		}
	}
	if (substr($ymax,0,4)=='auto') {
		$ymaxauto = true;
		if (strpos($ymax,':')!==false) {
			$ypts = explode(':',$ymax);
			$ymax = $ypts[1];
		} else {
			$ymax = 5;
		}
	}
	//$commands = "setBorder(5); initPicture({$settings[0]},{$settings[1]},{$settings[2]},{$settings[3]});";
	//$alt = "Graph, window x {$settings[0]} to {$settings[1]}, y {$settings[2]} to {$settings[3]}.";
	$commands = '';
	$alt = '';
	if (strpos($settings[4],':')) {
		$settings[4] = str_replace(array('(',')'),'',$settings[4]);
		$lbl = explode(':',$settings[4]);
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
		$commands .= "text([{$settings[1]},0],\"{$lbl[2]}\",\"aboveleft\");";
		$commands .= "text([0,{$settings[3]}],\"{$lbl[3]}\",\"belowright\");";
	}
	$absymin = 1E10;
	$absymax = -1E10;
	foreach ($funcs as $function) {
		if ($function=='') { continue;}
		$alt .= "Start Graph";
		$function = explode(",",$function);
		//correct for parametric
		$isparametric = false;
		$isineq = false;
		$isxequals = false;
		if ($function[0]{0}=='[') { //strpos($function[0],"[")===0) {
			$isparametric = true;
			$xfunc = makepretty(str_replace("[","",$function[0]));
			$xfunc = mathphp($xfunc,"t");
			$xfunc = str_replace("(t)",'($t)',$xfunc);
			$yfunc = makepretty(str_replace("]","",$function[1]));
			$yfunc = mathphp($yfunc,"t");
			$yfunc = str_replace("(t)",'($t)',$yfunc);
			array_shift($function);
			$evalxfunc = create_function('$t','return('.$xfunc.');');
			$evalyfunc = create_function('$t','return('.$yfunc.');');
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
			$func = mathphp($func,"x");
			$func = str_replace("(x)",'($x)',$func);
			$evalfunc = create_function('$x','return('.$func.');');
			if ($evalfunc===false) {continue;}
		} else if (strlen($function[0])>1 && $function[0]{0}=='x' && ($function[0]{1}=='<' || $function[0]{1}=='>' || $function[0]{1}=='=')) {
			$isxequals = true;
			if ($function[0]{1}=='=') {
				$val = substr($function[0],2);
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
			$func = mathphp($func,"x");
			$func = str_replace("(x)",'($x)',$func);
			$evalfunc = create_function('$x','return('.$func.');');
			if ($evalfunc===false) {continue;}
		}

		//even though ASCIIsvg has a plot function, we'll calculate it here to hide the function


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
				if ($ineqtype{0}=='<') {
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
			$xmin = $function[2];
			$domainlimited = true;
		} else {$xmin = $settings[0];}
		if (isset($function[3]) && $function[3]!='') {
			$xmaxarr = explode('!',$function[3]);
			$xmax = $xmaxarr[0];
			$avoid = array_slice($xmaxarr,1);
			$domainlimited = true;
		} else {$xmax = $settings[1];}

		if ($GLOBALS['sessiondata']['graphdisp']==0) {
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
			$dx = ($xmax - $xmin + ($domainlimited?0:10*($xmax-$xmin)/$settings[6]) )/100;
			$stopat = ($domainlimited?101:102);
			if ($xmax==$xmin) {
				$stopat = 1;
			}
		}
		if ($xmax==$xmin) {
			$xrnd = 6;
			$yrnd = 6;
		} else {
			$xrnd = floor(-log10(abs($xmax-$xmin))-1e-12)+4;
			$yrnd = floor(-log10(abs($ymax-$ymin))-1e-12)+4;
		}

		$lasty = 0;
		$lastl = 0;
		$px = null;
		$py = null;
		$pathstr = '';
		$firstpoint = false;
		$fx = array();  $fy = array();
		for ($i = 0; $i<$stopat;$i++) {
			if ($isparametric) {
				$t = $xmin + $dx*$i + 1E-10;
				if (in_array($t,$avoid)) { continue;}
				$x = round($evalxfunc($t),$xrnd);//round(eval("return ($xfunc);"),3);
				$y = round($evalyfunc($t),$yrnd);//round(eval("return ($yfunc);"),3);
				if ($xmax != $xmin) {
					$alt .= "<tr><td>$x</td><td>$y</td></tr>";
				}
			} else {
				$x = $xmin + $dx*$i + (($i<$stopat/2)?1E-10:-1E-10) - (($domainlimited || $GLOBALS['sessiondata']['graphdisp']==0)?0:5*abs($xmax-$xmin)/$settings[6]);
				if (in_array($x,$avoid)) { continue;}
				//echo $func.'<br/>';
				$y = round($evalfunc($x),$yrnd);//round(eval("return ($func);"),3);
				$x = round($x,$xrnd);
				if ($xmax != $xmin) {
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
					if ($yymax-$py < .5*($yymax-$yymin)) { //closer to top
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
						$y = $evalyfunc($t);
						$tempy = $evalyfunc($t-$dx/10);
					} else {
						$y = $evalfunc($x);
						$tempy = $evalfunc($x-$dx/10);
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
				if ($i==$stopat-1) {
					$pathstr .= ",[$x,$y]";
				}
				$lastl++;
				if ($py<$absymin) {
					$absymin = $py;
				}
				if ($py>$absymax) {
					$absymax = $py;
				}
			}
			$px = $x;
			$py = $y;
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
			if ($ineqtype{0}=='<') {
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
		$settings[2] = max($absymin,$ymin);
	}
	if ($ymaxauto) {
		$settings[3] = min($absymax,$ymax);
	}
	$commands = "setBorder(5); initPicture({$settings[0]},{$settings[1]},{$settings[2]},{$settings[3]});".$commands;
	$alt = "Graphs with window x: {$settings[0]} to {$settings[1]}, y: {$settings[2]} to {$settings[3]}. ".$alt;

	if ($GLOBALS['sessiondata']['graphdisp']==0) {
		return $alt;
	} else {
		return "<embed type='image/svg+xml' align='middle' width='$settings[6]' height='$settings[7]' script='$commands' />\n";
	}
}

function addplotborder($plot,$left,$bottom=5,$right=5,$top=5) {
	return str_replace("setBorder(5)","setBorder($left,$bottom,$right,$top)",$plot);

}

function replacealttext($plot, $alttext) {
	if ($GLOBALS['sessiondata']['graphdisp']==0) {
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
	if ($GLOBALS['sessiondata']['graphdisp']==0) {
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
	if ($GLOBALS['sessiondata']['graphdisp']==0) {
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
	for ($i=1;$i<$n;$i++) {
		$plotb = func_get_arg($i);
		if ($GLOBALS['sessiondata']['graphdisp']==0) {
			$newtext = preg_replace('/^Graphs.*?y:.*?to.*?\.\s/', '', $plotb);
			$plota .= $newtext;
		} else {
			$newcmds = preg_replace('/^.*?initPicture\(.*?\);\s*(axes\(.*?\);)?(.*?)\'\s*\/>.*$/', '$2', $plotb);
			$plota = str_replace("' />", $newcmds."' />", $plota);
		}
	}
	return $plota;
}

function addfractionaxislabels($plot,$step) {
	if ($GLOBALS['sessiondata']['graphdisp']==0) {
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
	if ($GLOBALS['sessiondata']['graphdisp']==0) {
		if ($alt != '') {
			return $alt;
		} else {
			return "[Graphs generated by this script: $script]";
		}
	} else {
		$script = str_replace("'",'"',$script);
		return "<embed type='image/svg+xml' align='middle' width='$width' height='$height' script='$script' />\n";
	}
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
			$format = substr($alist[count($alist)-1],0,1);
		}
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
	for ($i = 0; $i<floor(count($alist)/2); $i++) {
		if ($alist[2*$i]!='') {
			$hashdr = true;
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
			if ($format=='c' || $format=='C') {
				$out .= '<td class="c">';
			} else if ($format=='r' || $format=='R') {
				$out .= '<td class="r">';
			} else if ($format=='l' || $format=='L') {
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
	if ($exp{0}=='+') {
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
		$c = $exp{$i};
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
				$n = $exp{$i+1};
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
	if ($exp{0}=='+') {
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
	if ($max < $min) {echo "rrand: Need min&lt;max"; return $min;}
	if ($p<=0) {echo "Error with rrand: need to set positive step size"; return false;}
	$rn = 0;
	if (($s = strpos( (string) $p,'.'))!==false) { $rn = max($rn, strlen((string) $p) - $s - 1); }
	if (($q = strpos((string) $min,'.'))!==false) { $rn = max($rn, strlen((string) $min) - $q - 1); }
	
	$out = round($min + $p*$GLOBALS['RND']->rand(0,floor(($max-$min)/$p)), $rn);
	if ($rn==0) { $out = (int) $out;}
	return( $out );
}


function rands($min,$max,$n=0) {
	if (func_num_args()!=3) { echo "rands expects 3 arguments"; return $min;}
	if (floor($min)!=$min || floor($max)!=$max) {
		if ($GLOBALS['myrights']>10) {
			echo "rands expects integer min and max";
		}
		$min = ceil($min);
		$max = floor($max);
	}
	if ($max < $min) {echo "Need min&lt;max"; return $min;}
	$n = floor($n);
	if ($n==0) { echo "Need n &gt; 0";}
	for ($i = 0; $i < $n; $i++) {
		$r[$i] = $GLOBALS['RND']->rand($min,$max);
	}
	return $r;
}


function rrands($min,$max,$p=0,$n=0) {
	if (func_num_args()!=4) { echo "rrands expects 4 arguments"; return $min;}
	if ($max < $min) {echo "Need min&lt;max"; return $min;}
	if ($p<=0) {echo "Error with rrands: need to set positive step size"; return false;}
	$rn = 0;
	if (($s = strpos( (string) $p,'.'))!==false) { $rn = max($rn, strlen((string) $p) - $s - 1); }
	if (($q = strpos((string) $min,'.'))!==false) { $rn = max($rn, strlen((string) $min) - $q - 1); }

	for ($i = 0; $i < $n; $i++) {
		$r[$i] = round($min + $p*$GLOBALS['RND']->rand(0,floor(($max-$min)/$p)), $rn);
		if ($rn==0) { $r[$i] = (int) $r[$i];}
	}
	return $r;
}


function randfrom($lst) {
	if (func_num_args()!=1) { echo "randfrom expects 1 argument"; return 1;}
	if (!is_array($lst)) {
		$lst = explode(",",$lst);
	}
	return $lst[$GLOBALS['RND']->rand(0,count($lst)-1)];
}


function randsfrom($lst,$n) {
	if (func_num_args()!=2) { echo "randsfrom expects 2 arguments"; return 1;}
	if (!is_array($lst)) {
		$lst = explode(",",$lst);
	}
	for ($i=0; $i<$n;$i++) {
		$r[$i] = $lst[$GLOBALS['RND']->rand(0,count($lst)-1)];
	}
	return $r;
}


function jointrandfrom($lst1,$lst2) {
	if (func_num_args()!=2) { echo "jointrandfrom expects 2 arguments"; return array(1,1);}
	if (!is_array($lst1)) {
		$lst1 = explode(",",$lst1);
	}
	if (!is_array($lst2)) {
		$lst2 = explode(",",$lst2);
	}
	$l = $GLOBALS['RND']->rand(0,min(count($lst1)-1,count($lst2)-1));
	return array($lst1[$l],$lst2[$l]);
}


function diffrandsfrom($lst,$n) {
	if (func_num_args()!=2) { echo "diffrandsfrom expects 2 arguments"; return array();}
	if (!is_array($lst)) {
		$lst = explode(",",$lst);
	}
	$GLOBALS['RND']->shuffle($lst);
	return array_slice($lst,0,$n);
}


function nonzerorand($min,$max) {
	if (func_num_args()!=2) { echo "nonzerorand expects 2 arguments"; return $min;}
	if (floor($min)!=$min || floor($max)!=$max) {
		if ($GLOBALS['myrights']>10) {
			echo "nonzerorand expects integer min and max";
		}
		$min = ceil($min);
		$max = floor($max);
	}
	if ($max < $min) {echo "Need min&lt;max"; return $min;}
	if ($min==0 && $max==0) {
		echo "min=0, max=0 bad."; return 0;
	}
	do {
		$ret = $GLOBALS['RND']->rand($min,$max);
	} while ($ret == 0);
	return $ret;
}


function nonzerorrand($min,$max,$p=0) {
	if (func_num_args()!=3) { echo "nonzerorrand expects 3 arguments"; return $min;}
	if ($max < $min) {echo "Need min&lt;max"; return $min;}
	if ($min==0 && $max==0) {
		echo "min=0, max=0 bad."; return 0;
	}
	if (floor(($max-$min)/$p)==0) {
		return $min;
	}
	if ($p<=0) {echo "Error with nonzerorrand: need to set positive step size"; return $min;}
	$rn = 0;
	if (($s = strpos( (string) $p,'.'))!==false) { $rn = max($rn, strlen((string) $p) - $s - 1); }
	if (($q = strpos((string) $min,'.'))!==false) { $rn = max($rn, strlen((string) $min) - $q - 1); }

	do {
		$ret = round($min + $p*$GLOBALS['RND']->rand(0,floor(($max-$min)/$p)), $rn);
	} while (abs($ret)< 1e-14);
	if ($rn==0) { $ret = (int) $ret;}
	return $ret;
}


function nonzerorands($min,$max,$n=0) {
	if (func_num_args()!=3) { echo "nonzerorands expects 3 arguments"; return $min;}
	if (floor($min)!=$min || floor($max)!=$max) {
		if ($GLOBALS['myrights']>10) {
			echo "nonzerorands expects integer min and max";
		}
		$min = ceil($min);
		$max = floor($max);
	}
	$min = ceil($min);
	$max = floor($max);
	if ($max < $min) {echo "Need min&lt;max"; return array_fill(0,$n,$min);}
	if ($min==0 && $max==0) {
		echo "min=0, max=0 bad."; return 0;
	}
	for ($i = 0; $i < $n; $i++) {
		do {
			$r[$i] = $GLOBALS['RND']->rand($min,$max);
		} while ($r[$i] == 0);
	}
	return $r;
}


function nonzerorrands($min,$max,$p=0,$n=0) {
	if (func_num_args()!=4) { echo "nonzerorrands expects 4 arguments"; return $min;}
	if ($max < $min) {echo "Need min&lt;max"; return $min;}
	$n = floor($n);
	if ($min==0 && $max==0) {
		echo "min=0, max=0 bad."; return 0;
	}
	if ($p<=0) {echo "Error with nonzerorrands: need to set positive step size"; return array_fill(0,$n,$min);}
	if (floor(($max-$min)/$p)==0) {
		return array_fill(0, $n, $min);
	}
	$rn = 0;
	if (($s = strpos( (string) $p,'.'))!==false) { $rn = max($rn, strlen((string) $p) - $s - 1); }
	if (($q = strpos((string) $min,'.'))!==false) { $rn = max($rn, strlen((string) $min) - $q - 1); }

	for ($i = 0; $i < $n; $i++) {
		do {
			$r[$i] = round($min + $p*$GLOBALS['RND']->rand(0,($max-$min)/$p), $rn);
			if ($rn==0) { $r[$i] = (int) $r[$i];}
		} while (abs($r[$i]) <1e-14);
	}
	return $r;
}


function diffrands($min,$max,$n=0) {
	if (func_num_args()!=3) { echo "diffrands expects 3 arguments"; return $min;}
	if (floor($min)!=$min || floor($max)!=$max) {
		if ($GLOBALS['myrights']>10) {
			echo "diffrands expects integer min and max";
		}
		$min = ceil($min);
		$max = floor($max);
	}
	$n = floor($n);
	if ($max < $min) {echo "Need min&lt;max"; return array_fill(0,$n,$min);}
	if ($n<.1*($max-$min)) {
		$out = array();
		while (count($out)<$n) {
			$x = $GLOBALS['RND']->rand($min,$max);
			if (!in_array($x,$out)) {
				$out[] = $x;
			}
		}
		return $out;
	} else {
		$r = range($min,$max);
		while ($n>count($r)) {
			$r = array_merge($r,$r);
		}
		$GLOBALS['RND']->shuffle($r);
		return array_slice($r,0,$n);
	}
}


function diffrrands($min,$max,$p=0,$n=0, $nonzero=false) {
	if (func_num_args()<4) { echo "diffrrands expects 4 arguments"; return $min;}
	$n = floor($n);
	if ($max < $min) {echo "Need min&lt;max"; return array_fill(0,$n,$min);}
	if ($min==0 && $max==0) {
		echo "min=0, max=0 bad."; return array_fill(0,$n,0);
	}
	if ($p<=0) {echo "Error with diffrrands: need to set positive step size"; return array_fill(0,$n,$min);}

	if (floor(($max-$min)/$p)==0) {
		echo "Error with diffrrands: step size is greater than max-min"; return array_fill(0,$n,$min);
	}

	$rn = 0;
	if (($s = strpos( (string) $p,'.'))!==false) { $rn = max($rn, strlen((string) $p) - $s - 1); }
	if (($q = strpos((string) $min,'.'))!==false) { $rn = max($rn, strlen((string) $min) - $q - 1); }

	$maxi = floor(($max-$min)/$p);

	if ($n<.1*$maxi) {
		$out = array();

		while (count($out)<$n) {
			$x = round($min + $p*$GLOBALS['RND']->rand(0,$maxi), $rn);
			if ($rn==0) { $x = (int) $x;}
			if (!in_array($x,$out) && (!$nonzero || abs($x)>1e-14)) {
				$out[] = $x;
			}
		}
		return $out;
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
		return $r;
	}
}


function nonzerodiffrands($min,$max,$n=0) {
	if (func_num_args()!=3) { echo "nonzerodiffrands expects 3 arguments"; return $min;}
	if (floor($min)!=$min || floor($max)!=$max) {
		if ($GLOBALS['myrights']>10) {
			echo "nonzerodiffrands expects integer min and max";
		}
		$min = ceil($min);
		$max = floor($max);
	}
	if ($max < $min) {echo "Need min&lt;max"; return array_fill(0,$n,$min);}
	if ($min==0 && $max==0) {
		echo "min=0, max=0 bad."; return array_fill(0,$n,$min);
	}
	if ($n<.1*($max-$min)) {
		$out = array();
		while (count($out)<$n) {
			$x = $GLOBALS['RND']->rand($min,$max);
			if ($x!=0 && !in_array($x,$out)) {
				$out[] = $x;
			}
		}
		return $out;
	} else {
		$r = range($min,$max);
		if ($min <= 0 && $max >= 0) {
			array_splice($r,-1*$min,1);
		}
		$GLOBALS['RND']->shuffle($r);
		return array_slice($r,0,$n);
	}
}


function nonzerodiffrrands($min,$max,$p=0,$n=0) {
	return diffrrands($min,$max,$p,$n, true);
}


function singleshuffle($a) {
	if (!is_array($a)) {
		$a = explode(",",$a);
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
		$a1 = explode(",",$a1);
	}
	if (!is_array($a2)) {
		$a2 = explode(",",$a2);
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
	if (func_num_args()>1 && ($GLOBALS['sessiondata']['isteacher'] || isset($GLOBALS['teacherid']))) {
		echo "Warning:  listtoarray expects one argument, more than one provided";
	}
	return (explode(",",$l));
}


function arraytolist($a, $sp=false) {
	if ($sp) {
		return (implode(', ',$a));
	} else {
		return (implode(',',$a));
	}
}

function joinarray($a,$s) {
	return (implode($s,$a));
}


function calclisttoarray($l) {
	$l = explode(",",$l);
	foreach ($l as $k=>$tocalc) {
		eval('$l[$k] = ' . mathphp($tocalc,null).';');
	}
	return $l;
}


function sortarray($a) {
	if (!is_array($a)) {
		$a = explode(",",$a);
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
	return array_map(create_function('$x','return('.$todo.');'),$array);
}

function multicalconarray() {
	$args = func_get_args();
	$nargs = count($args);
	$todo = array_shift($args);
	$vars = array_shift($args);
	$vars = explode(',',$vars);
	if ($nargs-2 != count($vars)) {
		echo "incorrect number of data arrays";
		return false;
	}

	$todo = mathphp($todo,implode('|',$vars),false,false);
	if ($todo=='0;') { return 0;}
	for ($i=0;$i<count($vars);$i++) {
		$todo = str_replace('('.$vars[$i].')','($'.$vars[$i].')',$todo);
	}
	$todo = str_replace("'","\'",$todo);
	$varlist = '$'.implode(',$',$vars);
	$evalstr = "return(array_map(create_function('$varlist','return($todo);')";
	$cnt = count($args[0]);
	for ($i=0; $i<count($args); $i++) {
		$evalstr .= ',$args['.$i.']';
		if (count($args[$i])!=$cnt) {
			echo "unequal element count in arrays";
			return false;
		}
	}
	$evalstr .= '));';
	return eval($evalstr);
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

	$iffunc = create_function('$x','return('.$ifcond.');');

	$tmpfunc = create_function('$x','return('.$todo.');');
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
function prettyreal($n,$d,$comma=',') {
	return number_format($n,$d,'.',$comma);
}
function prettysmallnumber($n) {
	if (abs($n)<.01) {
		$a = explode("E",$n);
		if (count($a)==2) {
			if ($n<0) {
				$sign = '-';
			} else {
				$sign = '';
			}
			$n = $sign."0.".str_repeat("0", -$a[1]-1).str_replace('.','',abs($a[0]));
		}
	}
	return $n;
}

function prettysigfig($a,$sigfig,$comma=',',$choptrailing=false) {
	$a = str_replace(',','',$a);
	if ($a==0) { return 0;}
	if ($a < 0 ) {
		$sign = '-';
		$a *= -1;
	} else {
		$sign = '';
	}

	$v = floor(-log10($a)-1e-12);
	if ($v+$sigfig <= 0) {
		return $sign.number_format(round($a,$v+$sigfig),0,'.',$comma);
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
		return $sign.$n;
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
function convertTri($num, $tri, $doth=false) {
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
  if ($r > 0)
   return convertTri($r, $tri+1).$str;
  else
   return $str;
 }

function numtowords($num,$doth=false,$addcontractiontonum=false) {
	global $placevals;

	if ($addcontractiontonum) {
		$num = strval($num);
		$len = strlen($num);
		$last = $num{$len-1};
		if ($len>1 && $num{$len-2}=="1") { //ie 612
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
		$out .= convertTri($int,0,$doth);
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

$namearray[0] = explode(',',"Aaron,Ahmed,Aidan,Alan,Alex,Alfonso,Andres,Andrew,Antonio,Armando,Arturo,Austin,Ben,Bill,Blake,Bradley,Brayden,Brendan,Brian,Bryce,Caleb,Cameron,Carlos,Casey,Cesar,Chad,Chance,Chase,Chris,Cody,Collin,Colton,Conner,Corey,Dakota,Damien,Danny,Darius,David,Deandre,Demetrius,Derek,Devante,Devin,Devonte,Diego,Donald,Dustin,Dylan,Eduardo,Emanuel,Enrique,Erik,Ethan,Evan,Francisco,Frank,Gabriel,Garrett,Gerardo,Gregory,Ian,Isaac,Jacob,Jaime,Jake,Jamal,James,Jared,Jason,Jeff,Jeremy,Jesse,John,Jordan,Jose,Joseph,Josh,Juan,Julian,Julio,Justin,Juwan,Keegan,Ken,Kevin,Kyle,Landon,Levi,Logan,Lucas,Luis,Malik,Manuel,Marcus,Mark,Matt,Micah,Michael,Miguel,Nate,Nick,Noah,Omar,Paul,Quinn,Randall,Ricardo,Ricky,Roberto,Roy,Russell,Ryan,Salvador,Sam,Santos,Scott,Sergio,Shane,Shaun,Skyler,Spencer,Stephen,Taylor,Tevin,Todd,Tom,Tony,Travis,Trent,Trevor,Trey,Tristan,Tyler,Wade,Warren,Wyatt,Zach");
$namearray[1] = explode(',',"Adriana,Adrianna,Alejandra,Alexandra,Alexis,Alice,Alicia,Alma,Amanda,Amber,Amy,Andrea,Angela,Anna,April,Ariana,Ashley,Ashton,Autumn,Bianca,Bria,Brianna,Brittany,Brooke,Caitlyn,Carissa,Carolyn,Carrie,Cassandra,Catherine,Chasity,Chelsea,Chloe,Christy,Ciara,Claudia,Colleen,Courtney,Cristina,Crystal,Dana,Danielle,Delaney,Destiny,Diana,Elizabeth,Emily,Emma,Erica,Erin,Esmeralda,Gabrielle,Guadalupe,Haley,Hanna,Heather,Hillary,Holly,Jacqueline,Jamie,Jane,Jasmine,Jenna,Jennifer,Jessica,Julia,Karen,Karina,Karissa,Karla,Kathryn,Katie,Kayla,Kelly,Kelsey,Kendra,Kimberly,Kori,Kristen,Kristina,Krystal,Kylie,Laura,Lauren,Leah,Linda,Lindsey,Mackenzie,Madison,Maggie,Mariah,Marissa,Megan,Melissa,Meredith,Michelle,Mikayla,Miranda,Molly,Monique,Morgan,Naomi,Natalie,Natasha,Nicole,Nina,Noelle,Paige,Patricia,Rachael,Raquel,Rebecca,Renee,Riley,Rosa,Samantha,Sarah,Savannah,Shannon,Shantel,Sierra,Sonya,Sophia,Stacy,Stephanie,Summer,Sydney,Tatiana,Taylor,Tiana,Tiffany,Valerie,Vanessa,Victoria,Vivian,Wendy,Whitney,Zoe");

$cityarray = explode(',','Los Angeles,Dallas,Houston,Atlanta,Detroit,San Francisco,Minneapolis,St. Louis,Baltimore,Pittsburg,Cincinnati,Cleveland,San Antonio,Las Vegas,Milwaukee,Oklahoma City,New Orleans,Tucson,New York City,Chicago,Philadelphia,Miami,Boston,Phoenix,Seattle,San Diego,Tampa,Denver,Portland,Sacramento,Orlando,Kansas City,Nashville,Memphis,Hartford,Salt Lake City');

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
function randnamewpronouns() {
	$gender = $GLOBALS['RND']->rand(0,1);	
	$name = randnames(1,$gender);
	if ($gender==0) { //male
		return array(randnames(1,0), _('he'), _('him'), _('his'), _('his'));
	} else {
		return array(randnames(1,1), _('she'), _('her'), _('her'), _('hers'));
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
	$vars = explode(',',$varlist);
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

function getstuans($v,$q,$i=0) {
	if (is_array($v[$q])) {
		return $v[$q][$i];
	} else {
		return $v[$q];
	}
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
	$vars = explode(',',$varlist);
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

	$toparen = implode('|',$vars);

	if ($isnum) {
		$func = mathphp($func,$toparen);
		if ($func=='0;') { return 0;}
		$toeval = '';
		foreach ($vars as $i=>$var) {
			$func = str_replace("($var)","(\$$var)",$func);
			$toeval .= "\$$var = {$args[$i]};";
		}
		$toeval .= "\$out = $func;\n";
		eval($toeval);
		return $out;
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
	$img = preg_replace('/^.*src="(.*?)".*$/',"$1",$img);
	$out = '<div style="position: relative;" class="txtimgwrap">';
	$out .= '<img src="'.$img.'" style="position: relative; top: 0px; left: 0px;" />';
	while (count($args)>2) {
		$text = array_shift($args);
		$left = array_shift($args);
		$top = array_shift($args);
		$out .= "<div style=\"position: absolute; top: {$top}px; left: {$left}px;\">$text</div>";
	}
	$out .= '</div>';
	return $out;
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
function decimaltofraction($d,$format="fraction",$maxden = 5000) {
	if (floor($d)==$d) {
		return floor($d);
	}
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
		if (abs($calcD - $d)<1e-9) { break;}

		$prevCalcD = $calcD;

		$d2 = 1/($d2-$L2);
	}
	if (abs($numerators[$i]/$denominators[$i] - $d)>1e-9) {
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
		$arrlist = $arr;
		$arr = explode(',',$arr);
	} else {
		$arrlist = implode(',',$arr);
	}
	if (count($arr)==0) {
		return "";
	}
	$out = array();
	foreach ($arr as $num) {
		$num = abs($num);
		$nummatch = substr_count($arrlist,$num);
		$out[] = "$num,=$nummatch";
	}
	return implode(',',$out);
}

function evalbasic($str) {
	$str = str_replace(',','',$str);
	$str = str_replace('pi','3.141592653',$str);
	$str = clean($str);
	if (is_numeric($str)) {
		return $str;
	} else if (preg_match('/[^\d+\-\/\*\.]/',$str)) {
		return $str;
	} else {
		eval("\$ret = $str;");
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

function intervaltoineq($str,$var) {
	if ($str=='DNE') {
		return 'DNE';
	}
	$arr = explode('U',$str);
	$out = array();
	foreach ($arr as $v) {
		$v = trim($v);
		$sm = $v{0};
		$em = $v{strlen($v)-1};
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
	$str = str_replace('`', '', $str);
	$instr = 0;
	$primeoff = 0;
	while (($p = strpos($str, "'", $primeoff))!==false) {
		if ($instr == 0) {  //if not a match for an earlier quote
			if ($p>0 && (ctype_alpha($str{$p-1}) || $str{$p-1}=='`')) {
				$str{$p} = '`';
			} else {
				$instr = 1-$instr;
			}
		}
		$primeoff = $p+1;
	}
	$parts = preg_split('/(<=|>=|=|,|<|>|&gt;|&lt;|&ge;|&le;|&ne;|\blt\b|\bgt\b|\ble\b|\bge\b|\bne\b)/',$str,-1,PREG_SPLIT_DELIM_CAPTURE);
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
					if ($out[$lastout] != '^' && $out[$lastout] != '/' && $out[$lastout]!='+' && $out[$lastout]!='-') {
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
					} else if ($tokens[$i+1][0]!= '+' && $tokens[$i+1][0]!= '-' && $tokens[$i+1][0]!= '/') {
						// 1x, 1(), 1sin
						if ($lastout<2 || ($out[$lastout-1] != '^' || $out[$lastout] != '-')) { //exclude ^-1 case
							$dontuse = true;
						}
					}
				}
				if (!$dontuse) {
					$out[] = 1;
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
		$c = $str{$i};
		$eatenwhite = 0;
		if ($c>="a" && $c<="z" || $c>="A" && $c<="Z") {
			//is a string or function name

			$intype = 2; //string like function name
			do {
				$out .= $c;
				$i++;
				if ($i==$len) {break;}
				$c = $str{$i};
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
					$c = $str{$i};
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
							$c = $str{$i};
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
		}  else {
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


function comparenumbers($a,$b,$tol='.001') {
	if ($tol{0}=='|') {
		$abstolerance = floatval(substr($tol,1));
	}
	if (!is_numeric($a)) {
		$a = @eval('return('.mathphp($a,null).');');
	}
	if (!is_numeric($b)) {
		$b = @eval('return('.mathphp($b,null).');');
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
	if ($tol{0}=='|') {
		$abstolerance = floatval(substr($tol,1));
	}
	$type = "expression";
	if (strpos($a, '=')!==false && strpos($b, '=')!==false) {
		$type = "equation";
	}
	$fromto = explode(',',$domain);
	$variables = explode(',',$vars);
	$vlist = implode("|",$variables);
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

	$a = mathphp(makepretty(mathphppre($a)), $vlist);
	$b = mathphp(makepretty(mathphppre($b)), $vlist);
	if ($a=='' || $b=='') {
		if (isset($GLOBALS['teacherid'])) {
			echo "<p>Debug info: one function failed to compile.</p>";
		}
		return false;
	}
	//echo "pretty: $a, $b";
	for($i=0; $i < count($variables); $i++) {
		$a = str_replace("(".$variables[$i].")",'($tp['.$i.'])',$a);
		$b = str_replace("(".$variables[$i].")",'($tp['.$i.'])',$b);
	}

	$cntnana = 0;
	$cntnanb = 0;
	$correct = true;
	$ratios = array();
	$evalerr = false;
	for ($i = 0; $i < 20; $i++) {
		for($j=0; $j < count($variables); $j++) {
			$tp[$j] = $tps[$i][$j];
		}
		$ansa = @eval("return ($a);");
		$ansb = @eval("return ($b);");
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
		$a = @eval('return('.mathphp($a,null).');');
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
	global $rawscores,$imasroot;
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
	if ($res==-1) {
		return '';
	} else if ($res==1) {
		return '<div class="feedbackwrap correct"><img src="'.$imasroot.'/img/gchk.gif" alt="Correct"/> '.$correct.'</div>';
	} else if ($res==0) {
		return '<div class="feedbackwrap incorrect"><img src="'.$imasroot.'/img/redx.gif" alt="Incorrect"/> '.$wrong.'</div>';
	}
}

function getfeedbacktxt($stu,$fbtxt,$ans) {
	global $imasroot;
	if ($stu===null) {
		return " ";
	} else if ($stu==='NA') {
		return '<div class="feedbackwrap"><img src="'.$imasroot.'/img/redx.gif" alt="Incorrect"/> ' . _("No answer selected. Try again.") . '</div>';
	} else if (isset($fbtxt[$stu])) {
		if ($stu==$ans) {
			return '<div class="feedbackwrap correct"><img src="'.$imasroot.'/img/gchk.gif" alt="Correct"/> '.$fbtxt[$stu].'</div>';
		} else {
			return '<div class="feedbackwrap incorrect"><img src="'.$imasroot.'/img/redx.gif" alt="Incorrect"/> '.$fbtxt[$stu].'</div>';
		}
	} else {
		if ($stu==$ans) {
			return '<div class="feedbackwrap correct"><img src="'.$imasroot.'/img/gchk.gif" alt="Correct"/></div>';
		} else {
			return '<div class="feedbackwrap incorrect"><img src="'.$imasroot.'/img/redx.gif" alt="Incorrect"/></div>';
		}
	}
}

function getfeedbacktxtessay($stu,$fbtxt) {
	if ($stu==null || trim($stu)=='') {
		return '';
	} else {
		return '<div class="feedbackwrap correct">'.$fbtxt.'</div>';
	}
}

function getfeedbacktxtnumber($stu, $partial, $fbtxt, $deffb='Incorrect', $tol=.001) {
	global $imasroot;
	if ($stu===null) {
		return " ";
	} else if (!is_numeric($stu)) {
		return '<div class="feedbackwrap incorrect"><img src="'.$imasroot.'/img/redx.gif" alt="Incorrect"/> ' . _("This answer does not appear to be a valid number.") . '</div>';
	} else {
		if ($tol{0}=='|') {
			$abstol = true;
			$tol = substr($tol,1);
		} else {
			$abstol =false;
		}
		$match = -1;
		if (!is_array($partial)) { $partial = explode(',',$partial);}
		for ($i=0;$i<count($partial);$i+=2) {
			if (!is_numeric($partial[$i])) {
				$partial[$i] = @eval('return('.mathphp($partial[$i],null).');');
			}
			if ($abstol) {
				if (abs($stu-$partial[$i]) < $tol + 1E-12) { $match = $i; break;}
			} else {
				if (abs($stu - $partial[$i])/(abs($partial[$i])+.0001) < $tol+ 1E-12) {$match = $i; break;}
			}
		}
		if ($match>-1) {
			if ($partial[$i+1]<1) {
				return '<div class="feedbackwrap incorrect"><img src="'.$imasroot.'/img/redx.gif" alt="Incorrect"/> '.$fbtxt[$match/2].'</div>';
			} else {
				return '<div class="feedbackwrap correct"><img src="'.$imasroot.'/img/gchk.gif" alt="Correct"/> '.$fbtxt[$match/2].'</div>';
			}
		} else {
			return '<div class="feedbackwrap incorrect"><img src="'.$imasroot.'/img/redx.gif" alt="Incorrect"/> '.$deffb.'</div>';
		}
	}
}

function getfeedbacktxtcalculated($stu, $stunum, $partial, $fbtxt, $deffb='Incorrect', $answerformat = '', $requiretimes = '', $tol=.001) {
	global $imasroot;
	if ($stu===null) {
		return " ";
	} else {
		if ($tol{0}=='|') {
			$abstol = true;
			$tol = substr($tol,1);
		} else {
			$abstol =false;
		}
		$match = -1;
		if (!is_array($partial)) { $partial = explode(',',$partial);}
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
				$partial[$i] = @eval('return('.mathphp($partial[$i],null).');');
			}
			if ($abstol) {
				if (abs($stunum-$partial[$i]) < $tol + 1E-12) { $match = $i; break;}
			} else {
				if (abs($stunum - $partial[$i])/(abs($partial[$i])+.0001) < $tol+ 1E-12) {$match = $i; break;}
			}
		}
		if ($match>-1) {
			if ($partial[$i+1]<1) {
				return '<div class="feedbackwrap incorrect"><img src="'.$imasroot.'/img/redx.gif" alt="Incorrect"/> '.$fbtxt[$match/2].'</div>';
			} else {
				return '<div class="feedbackwrap correct"><img src="'.$imasroot.'/img/gchk.gif" alt="Correct"/> '.$fbtxt[$match/2].'</div>';
			}
		} else {
			return '<div class="feedbackwrap incorrect"><img src="'.$imasroot.'/img/redx.gif" alt="Incorrect"/> '.$deffb.'</div>';
		}
	}
}

//$partial = array(answer,partialcreditval,answer,partialcreditval,...)
function getfeedbacktxtnumfunc($stu, $partial, $fbtxt, $deffb='Incorrect', $vars='x', $requiretimes = '', $tol='.001',$domain='-10,10') {
	global $imasroot;
	if ($stu===null || trim($stu)==='') {
		return " ";
	} else {
		if ($tol{0}=='|') {
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

		$fromto = explode(',',$domain);
		$variables = explode(',',$vars);
		$vlist = implode("|",$variables);
		$origstu = $stu;
		$stu = mathphp(makepretty(mathphppre($stu)), $vlist);
		if ($stu=='') {
			return '<div class="feedbackwrap incorrect"><img src="'.$imasroot.'/img/redx.gif" alt="Incorrect"/> '.$deffb.'</div>';
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

		for($i=0; $i < count($variables); $i++) {
			$stu = str_replace("(".$variables[$i].")",'($tp['.$i.'])',$stu);
		}
		$stupts = array();
		$cntnana = 0;
		$correct = true;
		for ($i = 0; $i < $numpts; $i++) {
			for($j=0; $j < count($variables); $j++) {
				$tp[$j] = $tps[$i][$j];
			}
			$stupts[$i] = evalReturnValue("return ($stu);", $origstu, array('tp'=>$tp));//@eval("return ($stu);");
			if (isNaN($stupts[$i])) {$cntnana++;}
			if ($stupts[$i]===false) {$correct = false; break;}
		}
		if ($cntnana==$numpts || !$correct) { //evald to NAN at all points
			return '<div class="feedbackwrap incorrect"><img src="'.$imasroot.'/img/redx.gif" alt="Incorrect"/> '.$deffb.'</div>';
		}

		$match = -1;
		if (!is_array($partial)) { $partial = explode(',',$partial);}
		for ($k=0;$k<count($partial);$k+=2) {
			$correct = true;
			$b = $partial[$k];
			if ($type=='equation') {
				if (substr_count($b, '=')!=1) {continue;}
				$b = preg_replace('/(.*)=(.*)/','$1-($2)',$b);
			}
			$origb = $b;
			$b = mathphp(makepretty(mathphppre($b)), $vlist);
			for($j=0; $j < count($variables); $j++) {
				$b = str_replace("(".$variables[$j].")",'($tp['.$j.'])',$b);
			}

			$cntnanb = 0;
			$ratios = array();
			for ($i = 0; $i < $numpts; $i++) {
				for($j=0; $j < count($variables); $j++) {
					$tp[$j] = $tps[$i][$j];
				}
				$ansb = evalReturnValue("return ($b);", $origb, array('tp'=>$tp));//@eval("return ($b);");
				if ($ansb===false) { //invalid option - skip it
					continue 2;
				}
				//echo "real: $ansa, my: $ansb <br/>";
				if (isNaN($stupts[$i])) {if (isNaN($ansb)) {$cntnanb++;}; continue;} //avoid NaN problems
				if (isNaN($ansb)) {$cntnanb++; continue;}

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
				return '<div class="feedbackwrap incorrect"><img src="'.$imasroot.'/img/redx.gif" alt="Incorrect"/> '.$fbtxt[$match/2].'</div>';
			} else {
				return '<div class="feedbackwrap correct"><img src="'.$imasroot.'/img/gchk.gif" alt="Correct"/> '.$fbtxt[$match/2].'</div>';
			}
		} else {
			return '<div class="feedbackwrap incorrect"><img src="'.$imasroot.'/img/redx.gif" alt="Incorrect"/> '.$deffb.'</div>';
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
	} else if ($type=='sqrt') {
		$code = 6.5;
	} else if ($type=='abs') {
		$code = 8;
	} else if ($type=='rational') {
		$code = 8.2;
	} else if ($type=='exp') {
		$code = 8.3;
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
		$words = explode(',',$words);
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

//scoremultiorder($stua, $answer, $swap, [$type='string'])
//allows groups of questions to be scored in different orders
//only works if $stua and $answer are directly comparable (i.e. basic string type or exact number)
//$swap is an array of entries of the form "1,2,3;4,5,6"  says to treat 1,2,3 as a group of questions.
//comparison is made on first entry in group
function scoremultiorder($stua, $answer, $swap, $type='string') {
	if ($stua == null) {return $answer;}
	$newans = $answer;
	foreach ($swap as $k=>$sw) {
		$swap[$k] = explode(';', $sw);
		foreach ($swap[$k] as $i=>$s) {
			$swap[$k][$i] = explode(',', $s);
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
				foreach ($sw[$i] as $k=>$v) {
					$tmp[$k] = $newans[$v];
				}
				foreach ($sw[$loc] as $k=>$v) {
					$newans[$sw[$i][$k]] = $newans[$v];
				}
				foreach ($tmp as $k=>$v) {
					$newans[$sw[$loc][$k]] = $tmp[$k];
				}
			}
		}
	}
	return $newans;
}

function sign($a,$str=false) {
	if ($str==="onlyneg") {
		return ($a<0)?"-":"";
	} else if ($str !== false) {
		return ($a<0)?"-":"+";
	} else {
		return ($a<0)?-1:1;
	}
}

function lensort($a,$b) {
	return strlen($b)-strlen($a);
}

function checksigfigs($givenans, $anans, $reqsigfigs, $exactsigfig, $reqsigfigoffset, $sigfigscoretype) {
	if ($givenans*$anans < 0) { return false;} //move on if opposite signs
	if ($anans!=0) {
		$v = -1*floor(-log10(abs($anans))-1e-12) - $reqsigfigs;
	}
	$epsilon = (($anans==0||abs($anans)>1)?1E-12:(abs($anans)*1E-12));
	if ($sigfigscoretype[0]=='abs' && $sigfigscoretype[1]==0) {
		$sigfigscoretype[1] = pow(10,$v)/2;
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
			if ($anans != 0 && $reqsigfigoffset>0 && $v<0 && strlen($givenans) - $gadploc-1 + $v>$reqsigfigoffset) {return false;} //too many sigfigs
		} else {
			$gadploc = strpos($givenans,'.');
			$absgivenans = str_replace('-','',$givenans);
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

class Rand {
	private $seed;
	private $randmax;

	function __construct() {
		$this->seed = rand();
		$this->randmax = getrandmax();
	}

	public function srand($n=0) {
		if ($n==0) {
			srand();
			if (isset($GLOBALS['assessver']) && $GLOBALS['assessver']>0) {
				$this->seed = rand();
			}
		} else {
			$n = (int)$n;
			if (isset($GLOBALS['assessver']) && $GLOBALS['assessver']>0) {
				$this->seed = $n;
			} else {
				srand($n);
			}
		}
	}

	public function rand($min=0,$max=null) {  //simple xorshift
		if (isset($GLOBALS['assessver']) && $GLOBALS['assessver']>0) {
			if ($max===null) {
				$max = $this->randmax;
			}
			$min = (int)$min;
			$max = (int)$max;
			if ($min < $max) {
				if ($GLOBALS['assessver']>1) {
					$this->seed = ($this->seed^($this->seed << 13)) & 0xffffffff;
					if ($this->seed >  0x7fffffff) { $this->seed -= 0x100000000;}
					$this->seed = ($this->seed^($this->seed >> 17)) & 0xffffffff;
					if ($this->seed >  0x7fffffff) { $this->seed -= 0x100000000;}
					$this->seed = ($this->seed^($this->seed << 5)) & 0x7fffffff;
				} else { //broken; assessver=1 only
					$this->seed ^= ($this->seed << 13);
					$this->seed ^= ($this->seed >> 17);
					$this->seed ^= ($this->seed << 5);
					$this->seed &= 0x7fffffff;
				}
				return ($this->seed % ($max + 1 - $min)) + $min;
			} else if($min > $max){
				return $this->rand($max,$min);
			} else if ($min == $max) {
				return $min;
			}
		} else {
			if ($max===null) {
				return rand();
			} else {
				return rand($min,$max);
			}
		}
	}

	public function shuffle(&$arr) {
		if (isset($GLOBALS['assessver']) && $GLOBALS['assessver']>0) {
			for ($i=count($arr)-1;$i>0;$i--) {
				if ($GLOBALS['assessver']>1) {
					$this->seed = ($this->seed^($this->seed << 13)) & 0xffffffff;
					if ($this->seed >  0x7fffffff) { $this->seed -= 0x100000000;}
					$this->seed = ($this->seed^($this->seed >> 17)) & 0xffffffff;
					if ($this->seed >  0x7fffffff) { $this->seed -= 0x100000000;}
					$this->seed = ($this->seed^($this->seed << 5)) & 0x7fffffff;
				} else { //broken; assessver=1 only
					$this->seed ^= ($this->seed << 13);
					$this->seed ^= ($this->seed >> 17);
					$this->seed ^= ($this->seed << 5);
					$this->seed &= 0x7fffffff;
				}
				$j = $this->seed % ($i+1); //$this->rand(0,$i);
				if ($i!=$j) {
					$tmp = $arr[$j];
					$arr[$j] = $arr[$i];
					$arr[$i] = $tmp;
				}
			}
		} else {
			shuffle($arr);
		}
	}

	public function str_shuffle($str) {
		if (isset($GLOBALS['assessver']) && $GLOBALS['assessver']>0) {
			$arr = str_split($str);
			$this->shuffle($arr);
			return implode('', $arr);
		} else {
			return str_shuffle($str);
		}
	}

	public function array_rand($arr, $n=1) {
		if (isset($GLOBALS['assessver']) && $GLOBALS['assessver']>0) {
			$keys = array_keys($arr);
			if ($n==1) {
				$n = $this->rand(0,count($keys)-1);
				return $keys[$n];
			} else if ($n==count($arr)) { //no point in shuffling since php's internal function doesn't shuffle
				return $keys;
			} else {
				$n = (int)$n;
				$this->shuffle($keys);
				return array_slice($keys,0,$n);
			}
		} else {
			return array_rand($arr,$n);
		}
	}

}
$RND = new Rand();

function evalMathPHP($str,$vl) {
	return evalReturnValue('return ('.mathphp($str,$vl).');', $str);
}
function evalReturnValue($str,$errordispstr='',$vars=array()) {
	global $myrights;
	$preevalerror = error_get_last();
	foreach ($vars as $v=>$val) {
		${$v} = $val;
	}
	$res = @eval($str);
	if ($res===false) {
		if ($myrights>10) {
			$error = error_get_last();
			echo '<p>Caught error in evaluating a function in this question: ',$error['message'];
			if ($errordispstr!='') {
				echo ' while evaluating '.htmlspecialchars($errordispstr);
			}
			echo '</p>';
		}
	} else {
		$error = error_get_last();
		if ($error && $error!=$preevalerror && $error['type']==E_ERROR && $myrights>10) {
			echo '<p>Caught error in evaluating a function in this question: ',$error['message'];
			if ($errordispstr!='') {
				echo ' while evaluating '.htmlspecialchars($errordispstr);
			}
			echo '</p>';
		}
	}
	return $res;
}

?>

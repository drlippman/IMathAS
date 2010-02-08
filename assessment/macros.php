<?php
//IMathAS:  Core randomizers and display macros
//(c) 2006 David Lippman


array_push($allowedmacros,"exp","sec","csc","cot","sech","csch","coth","rand","rrand","rands","rrands","randfrom","randsfrom","jointrandfrom","diffrandsfrom","nonzerorand","nonzerorrand","nonzerorands","nonzerorrands","diffrands","diffrrands","nonzerodiffrands","nonzerodiffrrands","singleshuffle","jointshuffle","makepretty","makeprettydisp","showplot","addlabel","showarrays","horizshowarrays","showasciisvg","listtoarray","arraytolist","calclisttoarray","sortarray","consecutive","gcd","lcm","calconarray","mergearrays","sumarray","dispreducedfraction","diffarrays","intersectarrays","joinarray","unionarrays","count","polymakepretty","polymakeprettydisp","makexpretty","makexprettydisp","calconarrayif","in_array","prettyint","prettyreal","arraystodots","subarray","showdataarray","arraystodoteqns","array_flip","arrayfindindex","fillarray","array_reverse","root");
array_push($allowedmacros,"numtowords","randname","randmalename","randfemalename","randnames","randmalenames","randfemalenames","randcity","randcities","prettytime","definefunc","evalfunc","safepow","arrayfindindices","stringtoarray","strtoupper","strtolower","ucfirst","makereducedfraction","stringappend","stringprepend","textonimage","addplotborder","addlabelabs","makescinot","today","numtoroman","sprintf","arrayhasduplicates","addfractionaxislabels","decimaltofraction","ifthen","multicalconarray","htmlentities","formhoverover","formpopup","connectthedots","jointsort","stringpos","stringlen","stringclean","substr");
function mergearrays($a,$b) {
	if (!is_array($a)) {
		$a = array($a);
	}
	if (!is_array($b)) {
		$b = array($b);
	}
	return array_merge($a,$b);
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
	$ymin = $settings[2];
	$ymax = $settings[3];
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
	} else if (isset($lbl[0]) && is_numeric($lbl[0]) && $lbl[0]>0 && $lbl[1]>0) {
		if (!isset($lbl[2])) {  //allow xscl:yscl:off for ticks but no labels
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
		$commands .= ','.$settings[5].','.$settings[5].');';
	} else if (isset($grid[0]) && is_numeric($grid[0]) && $grid[0]>0 && $grid[1]>0) {
		$commands .= ','.$grid[0].','.$grid[1].');';
	} else {
		$commands .= ');';
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
			$alt .= "<table class=stats><thead><tr><th>x</th><th>y</th></thead></tr><tbody>";
			$alt .= "<tr><td>$val</td><td>$ymin</td></tr>";
			$alt .= "<tr><td>$val</td><td>$ymax</td></tr>";
			$alt .= '</tbody></table>';
			$path .= "line([$val,$ymin],[$val,$ymax]);";
			$path .= "stroke=\"none\";strokedasharray=\"none\";";
			if ($function[1]=='red' || $function[1]=='green') {
				$path .= "fill=\"trans{$function[1]}\";";
			} else {
				$path .= "fill=\"transblue\";";
			}
			if ($isineq) {
				if ($ineqtype{0}=='<') {
					$path .= "rect([$xxmin,$yymin],[$val,$yymax]);";
					$alt .= "Shaded left";
				} else {
					$path .= "rect([$val,$yymin],[$xxmax,$yymax]);";
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
			$dx = 1;
			$alt .= "<table class=stats><thead><tr><th>x</th><th>y</th></thead></tr><tbody>";
			$stopat = ($xmax-$xmin)+1;
		} else {
			$dx = ($xmax - $xmin + ($domainlimited?0:10*($xmax-$xmin)/$settings[6]) )/100;
			$stopat = ($domainlimited?101:102);
			if ($xmax==$xmin) {
				$stopat = 1;
			}
		}
		$lasty = 0;
		$lastl = 0;
		$px = null;
		$py = null;
		$pathstr = '';
		$firstpoint = false;
		for ($i = 0; $i<$stopat;$i++) {
			if ($isparametric) {
				$t = $xmin + $dx*$i + 1E-10;
				if (in_array($t,$avoid)) { continue;}
				$x = round($evalxfunc($t),3);//round(eval("return ($xfunc);"),3);
				$y = round($evalyfunc($t),3);//round(eval("return ($yfunc);"),3);
				$alt .= "<tr><td>$x</td><td>$y</td></tr>";
			} else {
				$x = $xmin + $dx*$i + 1E-10 - ($domainlimited?0:5*($xmax-$xmin)/$settings[6]);
				if (in_array($x,$avoid)) { continue;}
				//echo $func.'<br/>';
				$y = round($evalfunc($x),3);//round(eval("return ($func);"),3);
				$alt .= "<tr><td>".($xmin + $dx*$i)."</td><td>$y</td></tr>";
			}
			
			if (isNaN($y)) {
				continue;
			}
			if ($py===null) { //starting line
				
			} else if ($y>$ymax || $y<$ymin) { //going or still out of bounds
				if ($py <= $ymax && $py >= $ymin) { //going out
					if ($y>$ymax) { //going up
						$iy = $ymax + 5*($ymax-$ymin)/$settings[7];
					} else { //going down
						$iy = $ymin - 5*($ymax-$ymin)/$settings[7];
					}
					$ix = ($x-$px)*($iy - $py)/($y-$py) + $px;
					if ($lastl == 0) {$pathstr .= "path([";} else { $pathstr .= ",";}
					$pathstr .= "[$px,$py],[$ix,$iy]]);";
					$lastl = 0;
				} else { //still out
					
				}
			} else if ($py>$ymax || $py<$ymin) { //coming or staying in bounds?
				if ($y <= $ymax && $y >= $ymin) { //coming in
					if ($py>$ymax) { //going up
						$iy = $ymax + 5*($ymax-$ymin)/$settings[7];
					} else { //going down
						$iy = $ymin - 5*($ymax-$ymin)/$settings[7];
					}
					$ix = ($x-$px)*($iy - $py)/($y-$py) + $px;
					if ($lastl == 0) {$pathstr .= "path([";} else { $pathstr .= ",";}
					$pathstr .= "[$ix,$iy]";
					$lastl++;
				} else { //still out
					
				}
			} else {//all in
				if ($lastl == 0) {$pathstr .= "path([";} else { $pathstr .= ",";}
				$pathstr .= "[$px,$py]";
				$lastl++;
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
		$alt .= "</tbody></table>\n";
		
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
			$alt .= "Open dot at $x,$y";
		} else if (isset($function[5]) && $function[5]=='closed') {
			$path .= "dot([$x,$y],\"closed\");";
			$alt .= "Closed dot at $x,$y";
		}
		if (isset($function[4]) && $function[4]=='open') {
			if ($isparametric) {
				$t = $xmin;
				$x = round(eval("return ($xfunc);"),3);
				$y = round(eval("return ($yfunc);"),3);
			} else {
				$x = $xmin; $y = round(eval("return ($func);"),4); 
			}
			$path .= "dot([$x,$y],\"open\");";
			if ($y<$absymin) {
				$absymin = $y;
			}
			if ($y>$absymax) {
				$absymax = $y;
			}
			$alt .= "Open dot at $x,$y";
		} else if (isset($function[4]) && $function[4]=='closed') {
			if ($isparametric) {
				$t = $xmin;
				$x = round(eval("return ($xfunc);"),3);
				$y = round(eval("return ($yfunc);"),3);
			} else {
				$x = $xmin; $y = round(eval("return ($func);"),4); 
			}
			$path .= "dot([$x,$y],\"closed\");";
			if ($y<$absymin) {
				$absymin = $y;
			}
			if ($y>$absymax) {
				$absymax = $y;
			}
			$alt .= "Closed dot at $x,$y";
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
	$alt = "Graph, window x {$settings[0]} to {$settings[1]}, y {$settings[2]} to {$settings[3]}.".$alt;
	
	if ($GLOBALS['sessiondata']['graphdisp']==0) {
		return $alt;
	} else {
		return "<embed type='image/svg+xml' align='middle' width='$settings[6]' height='$settings[7]' src='{$GLOBALS['imasroot']}/javascript/d.svg' script='$commands' />\n";
	}
}

function addplotborder($plot,$left,$bottom=5,$right=5,$top=5) {
	return str_replace("setBorder(5)","setBorder($left,$bottom,$right,$top)",$plot);	
	
}

function addlabel($plot,$x,$y,$lbl) {
	if (func_num_args()>4) {
		$color = func_get_arg(4);
	} else {
		$color = "black";
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

function addfractionaxislabels($plot,$step) {
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
	$yrange = $matches[4] - $matches[2];
	$stepn = $num/$den;
	if ($ispi) { $stepn *= M_PI;}
	if ($stepn==0) {echo "error: bad step size on pilabels"; return;}
	$step = ceil($xmin/$stepn);
	$totstep = ceil(($xmax-$xmin)/$stepn);
	$tm = -.03*$yrange;
	$tx = .03*$yrange;
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
		$outst .= "line([$x,$tm],[$x,$tx]); text([$x,0],\"$xd\",\"below\");";
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

function showasciisvg($script) {
	if (func_num_args()>2) {
		$width = func_get_arg(1);
		$height = func_get_arg(2);
	} else {
		$width = 200; $height = 200;
	}
	$script = str_replace("'",'"',$script);
	return "<embed type='image/svg+xml' align='middle' width='$width' height='$height' src='{$GLOBALS['imasroot']}/javascript/d.svg' script='$script' />\n";
}


function showarrays() {
	$alist = func_get_args();
	if (count($alist)<2) {return false;}
	if (count($alist)%2==1) {
		$format = substr($alist[count($alist)-1],0,1);
	} 
	if (count($alist)<4 && is_array($alist[0])) {
		for ($i=0;$i<count($alist[0]);$i++) {
			$newalist[] = $alist[0][$i];
			$newalist[] = $alist[1][$i];
		}
		$alist = $newalist;
	}
	$out = '<table class=stats><thead><tr>';
	for ($i = 0; $i<floor(count($alist)/2); $i++) {
		$out .= "<th scope=\"col\">{$alist[2*$i]}</th>";
	}
	$out .= "</tr></thead><tbody>";
	for ($j = 0; $j<count($alist[1]); $j++) {
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
			$out .= "{$alist[2*$i+1][$j]}</td>";
		}
		$out .="</tr>";
	}
	$out .= "</tbody></table>\n";
	return $out;
}


function horizshowarrays() {
	$alist = func_get_args();
	if (count($alist)<2) {return false;}

	
	$out = '<table class=stats>';
	for ($i=0; $i<count($alist)/2; $i++) {
		$out .= "<tr><th scope=\"row\"><b>{$alist[2*$i]}</b></th>";
		$out .= "<td>" . implode("</td><td>",$alist[2*$i+1]) . "</td></tr>\n";
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
	$exp = clean($exp);
	$exp = preg_replace('/^([a-zA-Z])\^0/','1',$exp);
	$exp = preg_replace('/(\d)\*?([a-zA-Z])\^0$/',"$1",$exp);
	$exp = preg_replace('/(\d)\*?([a-zA-Z])\^0([^\d\.])/',"$1$3",$exp);
	$exp = preg_replace('/([^\d])\*?([a-zA-Z])\^0$/',"$1 1",$exp);
	$exp = preg_replace('/([^\d])\*?([a-zA-Z])\^0([^\d\.])/',"$1 1 $3",$exp);
	$exp = preg_replace('/^0\s*\*?[^\+\-]*\+?/','',$exp);
	$exp = preg_replace('/[\+\-]\s*0\s*\*?[^\+\-]*/','',$exp);
	$exp = preg_replace('/^1\s*\*?([a-zA-Z])/',"$1",$exp);
	$exp = preg_replace('/([^\d\^\.])1\s*\*?([a-zA-Z\(])/',"$1$2",$exp);
	$exp = preg_replace('/\^1([^\d])/',"$1",$exp);
	$exp = preg_replace('/\^1$/','',$exp);
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
				if ($parr[0]==1) {
					$outstr .= $parr[1];
				} else {
					$outstr .= $parr[0] . ' ' . $parr[1]; //n x^1
				}
			} else {
				if ($parr[0]==1) {
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


function rrand($min,$max,$p) {
	if (func_num_args()!=3) { echo "rrand expects 3 arguments"; return $min;}
	if ($max < $min) {echo "Need min&lt;max"; return $min;}
	if ($p==0) {echo "Error with rrand: need to set step size"; return false;}
	return($min + $p*rand(0,($max-$min)/$p));	
}


function rands($min,$max,$n) {
	if (func_num_args()!=3) { echo "rands expects 3 arguments"; return $min;}
	if ($max < $min) {echo "Need min&lt;max"; return $min;}
	for ($i = 0; $i < $n; $i++) {
		$r[$i] = rand($min,$max);
	}
	return $r;
}


function rrands($min,$max,$p,$n) {
	if (func_num_args()!=4) { echo "rrands expects 4 arguments"; return $min;}
	if ($max < $min) {echo "Need min&lt;max"; return $min;}
	if ($p==0) {echo "Error with rrands: need to set step size"; return false;}
	for ($i = 0; $i < $n; $i++) {
		$r[$i] = $min + $p*rand(0,($max-$min)/$p);
	}
	return $r;
}


function randfrom($lst) {
	if (func_num_args()!=1) { echo "randfrom expects 1 argument"; return 1;}
	if (!is_array($lst)) {
		$lst = explode(",",$lst);
	}
	return $lst[rand(0,count($lst)-1)];	
}


function randsfrom($lst,$n) {
	if (func_num_args()!=2) { echo "randsfrom expects 2 arguments"; return 1;}
	if (!is_array($lst)) {
		$lst = explode(",",$lst);
	}
	for ($i=0; $i<$n;$i++) {
		$r[$i] = $lst[rand(0,count($lst)-1)];
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
	$l = rand(0,min(count($lst1)-1,count($lst2)-1));
	return array($lst1[$l],$lst2[$l]);
}


function diffrandsfrom($lst,$n) {
	if (func_num_args()!=2) { echo "diffrandsfrom expects 2 arguments"; return array();}
	if (!is_array($lst)) {
		$lst = explode(",",$lst);
	}
	shuffle($lst);
	return array_slice($lst,0,$n);	
}


function nonzerorand($min,$max) {
	if (func_num_args()!=2) { echo "nonzerorand expects 2 arguments"; return $min;}
	$min = ceil($min);
	$max = floor($max);
	if ($max < $min) {echo "Need min&lt;max"; return $min;}
	if ($min==0 && $max==0) {
		echo "min=0, max=0 bad."; return 0;
	}
	do {
		$ret = rand($min,$max);
	} while ($ret == 0);
	return $ret;
}


function nonzerorrand($min,$max,$p) {
	if (func_num_args()!=3) { echo "nonzerorrand expects 3 arguments"; return $min;}
	if ($max < $min) {echo "Need min&lt;max"; return $min;}
	if ($min==0 && $max==0) {
		echo "min=0, max=0 bad."; return 0;
	}
	if ($p==0) {echo "Error with nonzerorrand: need to set step size"; return false;}
	do {
		$ret = $min + $p*rand(0,($max-$min)/$p);
	} while (abs($ret)< 1e-14);
	return $ret;
}


function nonzerorands($min,$max,$n) {
	if (func_num_args()!=3) { echo "nonzerorands expects 3 arguments"; return $min;}
	$min = ceil($min);
	$max = floor($max);
	if ($max < $min) {echo "Need min&lt;max"; return $min;}
	if ($min==0 && $max==0) {
		echo "min=0, max=0 bad."; return 0;
	}
	for ($i = 0; $i < $n; $i++) {	
		do {
			$r[$i] = rand($min,$max);
		} while ($r[$i] == 0);
	}
	return $r;
}


function nonzerorrands($min,$max,$p,$n) {
	if (func_num_args()!=4) { echo "nonzerorrands expects 4 arguments"; return $min;}
	if ($max < $min) {echo "Need min&lt;max"; return $min;}
	if ($min==0 && $max==0) {
		echo "min=0, max=0 bad."; return 0;
	}
	if ($p==0) {echo "Error with nonzerorrands: need to set step size"; return false;}
	for ($i = 0; $i < $n; $i++) {	
		do {
			$r[$i] = $min + $p*rand(0,($max-$min)/$p);
		} while (abs($r[$i]) <1e-14);
	}
	return $r;
}


function diffrands($min,$max,$n) {
	if (func_num_args()!=3) { echo "diffrands expects 3 arguments"; return $min;}
	if ($max < $min) {echo "Need min&lt;max"; return $min;}
	if ($n<.1*($max-$min)) {
		$out = array();
		while (count($out)<$n) {
			$x = rand($min,$max);
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
		shuffle($r);
		return array_slice($r,0,$n);
	}
}


function diffrrands($min,$max,$p,$n) {
	if (func_num_args()!=4) { echo "diffrrands expects 4 arguments"; return $min;}
	if ($max < $min) {echo "Need min&lt;max"; return $min;}
	if ($p==0) {echo "Error with diffrrands: need to set step size"; return false;}
	$maxi = ($max-$min)/$p;
	if ($n<.1*$maxi) {
		$out = array();
		
		while (count($out)<$n) {
			$x = $min + $p*rand(0,$maxi);
			if (!in_array($x,$out)) {
				$out[] = $x;
			}
		}
		return $out;
	} else {
		$r = range(0,$maxi);
		while ($n>count($r)) {
			$r = array_merge($r,$r);
		}
		shuffle($r);
		$r = array_slice($r,0,$n);
		for ($i=0;$i<$n;$i++) {
			$r[$i] = $min+$p*$r[$i];
		}
		return $r;
	}
}


function nonzerodiffrands($min,$max,$n) {
	if (func_num_args()!=3) { echo "nonzerodiffrands expects 3 arguments"; return $min;}
	$min = ceil($min);
	$max = floor($max);
	if ($max < $min) {echo "Need min&lt;max"; return $min;}
	if ($min==0 && $max==0) {
		echo "min=0, max=0 bad."; return 0;
	}
	if ($n<.1*($max-$min)) {
		$out = array();
		while (count($out)<$n) {
			$x = rand($min,$max);
			if ($x!=0 && !in_array($x,$out)) {
				$out[] = $x;
			}
		}
		return $out;
	} else {
		$r = range($min,$max);
		if ($min < 0 && $max > 0) {
			array_splice($r,-1*$min,1);
		}
		shuffle($r);
		return array_slice($r,0,$n);
	}
}


function nonzerodiffrrands($min,$max,$p,$n) {
	if (func_num_args()!=4) { echo "nonzerodiffrrands expects 4 arguments"; return $min;}
	if ($max < $min) {echo "Need min&lt;max"; return $min;}
	if ($p==0) {echo "Error with nonzerodiffrrands: need to set step size"; return false;}
	if ($min==0 && $max==0) {
		echo "min=0, max=0 bad."; return 0;
	}
	$maxi = ($max-$min)/$p;
	if ($n<.1*$maxi) {
		$out = array();
		
		while (count($out)<$n) {
			$x = $min + $p*rand(0,$maxi);
			if (abs($x)>1e-14 && !in_array($x,$out)) {
				$out[] = $x;
			}
		}
		return $out;
	} else {
		$r = range(0,$maxi);
		if ($min < 0 && $max > 0) {
			array_splice($r,-1*$min/$p,1);
		}
		shuffle($r);
		$r = array_slice($r,0,$n);
		for ($i=0;$i<$n;$i++) {
			$r[$i] = $min+$p*$r[$i];
		}
		return $r;
	}
	
}


function singleshuffle($a) {
	if (!is_array($a)) {
		$a = explode(",",$a);
	}
	shuffle($a);
	if (func_num_args()>1) {
		return array_slice($a,0,func_get_arg(1));
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
	$r = array_rand($a1,count($a1));
	shuffle($r);
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

function dispreducedfraction($n,$d,$dblslash=false) {
	return '`'.makereducedfraction($n,$d,$dblslash).'`';
}

function makereducedfraction($n,$d,$dblslash=false) {
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
		if ($dblslash) {
			return "$n//$d";
		} else {
			return "$n/$d";
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
function prettyreal($n,$d) {
	return number_format($n,$d);
}

function makescinot($n,$d=8,$f="x") {
	if ($n==0) { return "0";}
	$isneg = "";
	if ($n<0) { $isneg = "-"; $n = abs($n);}
	$exp = floor(log10($n));
	$mant = round($n/pow(10,$exp),$d);
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

function showdataarray($a,$n=1) {
	if (!is_array($a)) {
		return '';
	}
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
	return $out;
}

$ones = array( "", " one", " two", " three", " four", " five", " six", " seven", " eight", " nine", " ten", " eleven", " twelve", " thirteen", " fourteen", " fifteen", " sixteen", " seventeen", " eighteen", " nineteen");
$onesth = array(""," first"," second", " third", " fourth", " fifth", " sixth", " seventh", " eighth", " ninth", "tenth"," eleventh", " twelfth", " thirteenth", " fourteenth"," fifteenth", " sixteenth", " seventeenth", " eighteenth"," nineteenth"); 
$tens = array( "", "", " twenty", " thirty", " forty", " fifty", " sixty", " seventy", " eighty", " ninety");
$tensth = array("","","twentieth", "thirtieth", "fortieth", "fiftieth", "sixtieth", "seventieth", "eightieth", "ninetieth");
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
			  $str .= $tens[(int) ($y / 10)] . $onesth[$y % 10];
		  }
	  } else {
		 $str .= $tens[(int) ($y / 10)] . $ones[$y % 10];
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

function numtowords($num,$doth=false) {
	global $placevals;
	
	if ($num==0) {
		return "zero";
	}
	$int = floor($num);
	$dec = 	$num-$int;
	$out = '';
	if ($int>0) {
		$out .= convertTri($int,0,$doth);
		if ($dec>0) {
			$out .= " and ";
		}
	}
	if ($dec>0) {
		$cnt = 0;
		while (abs($dec-round($dec))>1e-9 && $cnt<9) {
			$dec *= 10;
			$cnt++;
		}
		$out .= convertTri($dec,0);
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
		return $cityarray[rand(0,$c-1)];
	} else {
		$out = $cityarray;
		shuffle($out);
		return array_slice($out,0,$n);
	}
}

function randcity() {
	return randcities(1);
}

function randnames($n=1,$gender=2) { 
	global $namearray;
	if ($n==1) { 
		if ($gender==2) { 
			$gender = rand(0,1); 
		} 
		return $namearray[$gender][rand(0,137)]; 
	} else { 
		$out = array(); 
		$locs = diffrands(0,137,$n); 
		for ($i=0; $i<$n;$i++) { 
			if ($gender==2) { 
				$gender = rand(0,1); 
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
		$ampm = ($hrs<12?"am":"pm");
		$hrs = floor($hrs);
		if ($out=='sclock') {
			$min = floor($min -60*$hrs);
			$sec = round($time - 60*$min - 3600*$hrs);
			if ($min<10) {	$min = '0'.$min;}
			if ($sec<10) {	$sec = '0'.$sec;}
			$outst = "$hrs:$min:$sec $ampm";	
		} else {
			$min = round($min -60*$hrs);
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
	$func = makepretty($func);
	$vars = explode(',',$varlist);
	if (count($vars)!=count($args)) {
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
		$reg = '/^\((\d*?\.?\d*?)\)([^\d\.])/';
		$func= preg_replace($reg,"$1$2",$func);
		$reg = '/^\(([a-zA-Z])\)([^a-zA-Z])/';
		$func= preg_replace($reg,"$1$2",$func);
		
		$reg = '/([^\d\.])\((\d*?\.?\d*?)\)$/';
		$func= preg_replace($reg,"$1$2",$func);
		$reg = '/([^a-zA-Z])\(([a-zA-Z])\)$/';
		$func= preg_replace($reg,"$1$2",$func);
		
		$reg = '/([^\d\.])\((\d*?\.?\d*?)\)([^\d\.])/';
		$func= preg_replace($reg,"$1$2$3",$func);
		$reg = '/([^a-zA-Z])\(([a-zA-Z])\)([^a-zA-Z])/';
		$func= preg_replace($reg,"$1$2$3",$func);
		
		return $func;
	}
}

function textonimage() {
	$args = func_get_args();
	$img = array_shift($args);
	$img = preg_replace('/^.*src="(.*?)".*$/',"$1",$img);
	$out = '<div style="position: relative;">';
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
			return "$w $n/".$denominators[$i];
		} else {
			return $numerators[$i].'/'.$denominators[$i];
		}
	} else {
		return $numerators[$i].'/'.$denominators[$i];
	}
}

function evalbasic($str) {
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
		return '<span class="link" onmouseover="tipshow(this,\''.htmlentities(filter($tip),ENT_QUOTES).'\')" onmouseout="tipout()">'.$label.'</span>';
	} else {
		return '<span class="link" onmouseover="tipshow(this,\''.htmlentities($tip,ENT_QUOTES).'\')" onmouseout="tipout()">'.$label.'</span>';
	}
}

function formpopup($label,$content,$width=600,$height=400,$type='link',$scroll='null') {
	if (strpos($label,'<img')!==false) {
		return str_replace('<img', '<img class="clickable" onClick="popupwindow(\''.str_replace('\'','\\\'',htmlentities($content)).'\','.$width.','.$height.','.$scroll.')"',$label);
	} else {
		if ($type=='link') {
			return '<span class="link" onClick="popupwindow(\''.str_replace('\'','\\\'',htmlentities($content)).'\','.$width.','.$height.','.$scroll.')">'.$label.'</span>';
		} else if ($type=='button') {
			return '<span class="spanbutton" onClick="popupwindow(\''.str_replace('\'','\\\'',htmlentities($content)).'\','.$width.','.$height.','.$scroll.')">'.$label.'</span>';
		}
	}
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

?>

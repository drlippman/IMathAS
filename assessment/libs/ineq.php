<?php
//Inequality graphing functions.  Version 1.0, Oct 30, 2006

global $allowedmacros;
array_push($allowedmacros,"ineqplot","ineqbetweenplot");

//ineqplot("funcstring",[xmin,xmax,ymin,ymax,labels,grid,width,height])
//graphs a set of functions, shading above or below each graph as specified
//
//funcstring format: (function and filltype are required) - one string or array
//  function of x,filltype,fillcolor,linecolor,dash,strokewidth
//
//filltypes: above, below
function ineqplot($funcs) {
	if (!is_array($funcs)) {
		settype($funcs,"array");
	}
	$settings = array(-5,5,-5,5,1,1,200,200);
	for ($i = 1; $i < func_num_args(); $i++) {
		$settings[$i-1] = func_get_arg($i);
	}
	$outstr = array();
	foreach ($funcs as $k=>$function) {
		$of = array_fill(0,6,'');
		$f = explode(",",$function);
		//  function of x,filltype,fillcolor,linecolor,dash,strokewidth
		if ($f[1]=='above') {
			if (isset($f[4]) && $f[4]=='dash') {
				$of[0] = '>'.$f[0];
			} else {
				$of[0] = '>='.$f[0];
			}
		} else {
			if (isset($f[4]) && $f[4]=='dash') {
				$of[0] = '<'.$f[0];
			} else {
				$of[0] = '<='.$f[0];
			}
		}
        if (isset($f[2])) { 
		    $of[1] = $f[2];
        }
        if (isset($f[5])) {
		    $of[6] = $f[5];
        }
		$outstr[] = implode(',', $of);
	}
	return showplot($outstr,$settings[0],$settings[1],$settings[2],$settings[3],$settings[4],$settings[5],$settings[6],$settings[7]);
}


//ineqbetweenplot("funcstring",[xmin,xmax,ymin,ymax,labels,grid,width,height])
//graphs a set of functions, shading the area satisfying all above or below
//  specifications.
//
//funcstring format: (function and filltype are required) - one string or array
//  function of x,above or below,linecolor,dash,strokewidth,fillcolor
//  or x=number,right or left,linecolor,dash,strokewidth,fillcolor
function ineqbetweenplot($funcs) {
	if (!is_array($funcs)) {
		settype($funcs,"array");
	}
	$settings = array(-5,5,-5,5,1,1,200,200);
	for ($i = 1; $i < func_num_args(); $i++) {
		$settings[$i-1] = func_get_arg($i);
	}

	$mins = array(); $maxs = array();
	$xmin = $settings[0];
	$xmax = $settings[1];
	//$dx = ($xmax - $xmin)/100;
	//$stopat = 100;
	$dx = ($xmax - $xmin + 10*($xmax-$xmin)/$settings[6])/100;
	$stopat = 102;
	$xmin -= 5*($xmax-$xmin)/$settings[6];
	$newfuncstr = array();
	$skipi = array();
	$fillcolor = 'blue';
	$shadedir = array();
	foreach ($funcs as $k=>$function) {
		$function = array_map('trim',explode(",",$function));
        if (!isset($function[1])) { 
            echo "Missing filltype in ineqbetweenplot";
        }
		$filltype = $function[1] ?? '';
		$shadedir[$k] = $filltype;
		if (!isset($function[2])) {$function[2] = '';}
		if (!isset($function[4])) {$function[4] = 1;}
		if (!isset($function[3])) {$function[3] = '';}
		if (isset($function[5])) {$fillcolor=$function[5];}
		$newfuncstr[] = $function[0].','.$function[2].',,,,,'.$function[4].','.$function[3];
		for ($i = 0; $i<$stopat;$i++) {
			$mins[$i][$k]=$settings[2] - 5*($settings[3]-$settings[2])/$settings[7];
			$maxs[$i][$k]=$settings[3] + 5*($settings[3]-$settings[2])/$settings[7];
		}
		//correct for parametric
		if (substr($function[0],0,2)=='x=') {
			$val = evalnumstr(substr($function[0],2));
			$ix = ($val-$xmin)/$dx;
			if ($ix>0 && $ix<102) {
				if ($filltype=='right') {
					for ($i=0;$i<$ix;$i++) {
						$skipi[$i] = true;
					}
					$skipi[$i-1] = $ix;
				} else {
					for ($i=102;$i>$ix;$i--) {
						$skipi[$i] = true;
					}
					$skipi[$i+1] = $ix;
				}
			}
		} else {
			$xfunc = makeMathFunction(makepretty($function[0]), "x");
			for ($i = 0; $i<$stopat;$i++) {
				$x = $xmin + $dx*$i;
				$y = $xfunc(["x"=>$x]);
				if (is_nan($y)) {
					if (!isset($skipi[$i])) {
						$skipi[$i] = $i;
					}
					continue;
				}
				$y = round($y,3);
				if ($filltype=='above') {
					$mins[$i][$k] = $y;
				} else {
					$maxs[$i][$k] = $y;
				}
			}
		}
	}
	$inshape = false;
	$shape = array();
	$shapecnt = -1;
	for ($i = 0; $i<$stopat;$i++) {
		if (isset($mins[$i])) {
			$min = max($mins[$i]);
			$max = min($maxs[$i]);
		} else {
			$min=$settings[2] - 5*($settings[3]-$settings[2])/$settings[7];
			$max=$settings[3] + 5*($settings[3]-$settings[2])/$settings[7];
		}
		if ($min<$max && !isset($skipi[$i])) { //point is in shape
			if ($inshape==false) {
				$inshape = true;
				$shapecnt++;
				$shape[$shapecnt] = array();
				if ($i==0 || isset($skipi[$i-1])) {
					if (isset($skipi[$i-1])) { //entering from x=
						$ti = $skipi[$i-1];
						$shape[$shapecnt][] = array($ti,$min,$max);
					}
					//in shape from beginning
					$shape[$shapecnt][] = array($i,$min,$max);
				} else {
					//entering shape partway through
					//interpolate entry point
					//identify curve each value came from
					$minidx = array_search($min, $mins[$i]);
					$maxidx = array_search($max, $maxs[$i]);
					//find intersection between (i-1, mins[i-1][minidx]) to (i,min)
					// and (i-1,maxs[i-1][maxidx]), (i,max)
					$ti = ($mins[$i-1][$minidx] - $maxs[$i-1][$maxidx])/($max-$maxs[$i-1][$maxidx]-$min+$mins[$i-1][$minidx]);
					$yi = ($max-$maxs[$i-1][$maxidx])*$ti + $maxs[$i-1][$maxidx];
					$shape[$shapecnt][] = array($i-1+$ti,$yi);
				}
			} else {
				$shape[$shapecnt][] = array($i,$min,$max);
			}
		} else { //point is not in shape
			if ($inshape==true) {
				if (!isset($skipi[$i])) {
					//exiting shape
					//interpolate exit point
					//identify curve each value came from
					$minidx = array_search($min, $mins[$i]);
					$maxidx = array_search($max, $maxs[$i]);
					//find intersection between (i-1, mins[i-1][minidx]) to (i,min)
					// and (i-1,maxs[i-1][maxidx]), (i,max)
					$ti = ($mins[$i-1][$minidx] - $maxs[$i-1][$maxidx])/($max-$maxs[$i-1][$maxidx]-$min+$mins[$i-1][$minidx]);
					$yi = ($max-$maxs[$i-1][$maxidx])*$ti + $maxs[$i-1][$maxidx];
					$shape[$shapecnt][] = array($i-1+$ti,$yi);
				} else {
					//exiting shape by hitting x=
					$ti = $skipi[$i];
					$shape[$shapecnt][] = array($ti,$min,$max);
				}
				$inshape = false;
			}
		}
	}
	$path = '';
	for ($i=0;$i<count($shape);$i++) {
		if (count($shape[$i])>0) {
			$path .= 'path([';
		}
		for ($j=0;$j<count($shape[$i]);$j++) {
			if ($j>0) { $path .= ',';}
			$x = round($xmin + $dx*$shape[$i][$j][0],3);
			$y = $shape[$i][$j][1];
			$path .= "[$x,$y]";
		}
		for ($j=count($shape[$i])-1;$j>=0;$j--) {
			if (!isset($shape[$i][$j][2])) { continue;}
			$x = round($xmin + $dx*$shape[$i][$j][0],3);
			$y = $shape[$i][$j][2];
			$path .= ",[$x,$y]";
		}
		if (count($shape[$i])>0) {
			$path .= ']);';
		}
	}

	$p = showplot($newfuncstr,$settings[0],$settings[1],$settings[2],$settings[3],$settings[4],$settings[5],$settings[6],$settings[7]);
	if ($_SESSION['graphdisp']==0) {
		$parts = explode('<table', $p);
		for ($i=0;$i<count($parts)-1;$i++) {
			$parts[$i] .= ', Shaded '.$shadedir[$i];
		}
		$p = implode('<table', $parts);
	} else {
		$p = str_replace("' />","fill=\"trans$fillcolor\";strokewidth=0;$path;' />",$p);
	}
	return $p;
}

/*
old code

function ineqplot($funcs) {
	if (!is_array($funcs)) {
		settype($funcs,"array");
	}
	$settings = array(-5,5,-5,5,1,1,200,200);
	for ($i = 1; $i < func_num_args(); $i++) {
		$settings[$i-1] = func_get_arg($i);
	}
	$ymin = $settings[2];
	$ymax = $settings[3];
	$commands = "setBorder(5); initPicture({$settings[0]},{$settings[1]},{$settings[2]},{$settings[3]});";
	$alt = "Graph, window x {$settings[0]} to {$settings[1]}, y {$settings[2]} to {$settings[3]}.";
	if (strpos($settings[4],':')) {
		$lbl = explode(':',$settings[4]);
	}
	if (is_numeric($settings[4]) && $settings[4]>0) {
		$commands .= 'axes('.$settings[4].','.$settings[4].',1';
	} else if (isset($lbl[0]) && is_numeric($lbl[0]) && $lbl[0]>0 && $lbl[1]>0) {
		$commands .= 'axes('.$lbl[0].','.$lbl[1].',1';
	} else {
		$commands .= 'axes(1,1,null';
	}

	if (strpos($settings[5],':')) {
		$grid = explode(':',$settings[5]);
	}
	if (is_numeric($settings[5]) && $settings[5]>0) {
		$commands .= ','.$settings[5].','.$settings[5].');';
		$dgrid = $settings[5];
	} else if (isset($grid[0]) && is_numeric($grid[0]) && $grid[0]>0 && $grid[1]>0) {
		$commands .= ','.$grid[0].','.$grid[1].');';
		$dgrid = $grid[0];
	} else {
		$commands .= ');';
		$dgrid = 1;
	}

	foreach ($funcs as $k=>$function) {
		$alt .= "Start Graph";
		$function = explode(",",$function);
		//correct for parametric
		$func = makepretty($function[0]);
		$func = mathphp($func,"x");
		$func = str_replace("x",'$x',$func);
		$xfunc = my_create_function('$x','return ('.$func.');');
		//even though ASCIIsvg has a plot function, we'll calculate it here to hide the function
		//  function of x,filltype,fillcolor,linecolor,dash,strokewidth

		$path = '';
		$shades = '';
		$filltype = $function[1];
		if ($filltype == "abovediag" || $filltype == "belowdiag") {
			$fillslope = ($xfunc(.1)-$xfunc(0))/.1;
			if (abs($fillslope) < 0.00001) {
				$fillslope = 100000;
			} else {
				$fillslope = -1/$fillslope;
			}
		} else {
			$fillslope = false;
		}

		if ($function[2]!='') {
			$shades .= "stroke=\"{$function[2]}\";strokedasharray=\"none\";";
			$alt .= "Shaded in {$function[2]} " . substr($filltype,0,5);
		} else {
			$shades .= "stroke=\"blue\";strokedasharray=\"none\";";
			$alt .= "Shaded in blue " . substr($filltype,0,5);
		}
		if ($function[3]!='') {
			$path .= "stroke=\"{$function[3]}\";";
			$alt .= ", Color {$function[3]}";
		} else {
			$path .= "stroke=\"black\";";
			$alt .= ", Color black";
		}
		if ($function[5]!='') {
			$path .= "strokewidth=\"{$function[5]}\";";
		} else {
			$path .= "strokewidth=\"1\";";
		}
		if ($function[4]!='') {
			if ($function[4]=="dash") {
				$path .= "strokedasharray=\"5\";";
				$alt .= ", Dashed";
			} else {
				$path .= "strokedasharray=\"none\";";
			}
		} else {
			$path .= "strokedasharray=\"none\";";
		}

		$xmin = $settings[0];
		$xmax = $settings[1];

		if ($_SESSION['graphdisp']==0) {
			$dx = 1;
			$alt .= "<table class=stats><thead><tr><th>x</th><th>y</th></thead></tr><tbody>";
			$stopat = ($xmax-$xmin)+1;
		} else {
			$dx = ($xmax - $xmin)/100;
			$stopat = 100;
		}
		$lasty = 0;
		$lastl = 0;
		for ($i = 0; $i<$stopat;$i++) {

			$x = $xmin + $dx*$i;
			$y = round($xfunc($x),3);

			$alt .= "<tr><td>$x</td><td>$y</td></tr>";

			if (abs($y-$lasty) > ($ymax-$ymin)) {
				if ($lastl > 1) { $path .= ']);'; $lastl = 0;}
				$lasty = $y;
			} else {
				if ($lastl == 0) {$path .= "path([";} else { $path .= ",";}
				$path .= "[$x,$y]";
				$lasty = $y;
				$lastl++;
			}
		}
		if ($lastl > 0) {$path .= "]);";}
		$alt .= "</tbody></table>\n";
		$commands .= $path;

		//do shades
		for ($x = $xmin+$dgrid/2*($k+1)/(count($funcs)+1); $x<$xmax; $x+=$dgrid/2) {
			$y = round($xfunc($x),3);
			if ($y>$ymax || $y<$ymin) {
				continue;
			}
			$shades .= "line([$x,$y],";
			if ($filltype=="above") {
				$shades .= "[$x,$ymax]);";
			} else if ($filltype=="below") {
				$shades .= "[$x,$ymin]);";
			} else if (($filltype=="abovediag" && $fillslope>0) || ($filltype=="belowdiag" && $fillslope<=0)) {
				$yend = $fillslope*($xmax-$x) + $y;
				$shades .= "[$xmax,$yend]);";
			} else if (($filltype=="abovediag" && $fillslope<0) || ($filltype=="belowdiag" && $fillslope>=0)) {
				$yend = $fillslope*($xmin-$x) + $y;
				$shades .= "[$xmin,$yend]);";
			}
		}
		$commands .= $shades;
	}
	if ($_SESSION['graphdisp']==0) {
		return $alt;
	} else {
		return "<embed type='image/svg+xml' align='middle' width='$settings[6]' height='$settings[7]' src='{$GLOBALS['imasroot']}/javascript/d.svg' script='$commands' />\n";
	}
}


function ineqbetweenplot($funcs) {
	if (!is_array($funcs)) {
		settype($funcs,"array");
	}
	$settings = array(-5,5,-5,5,1,1,200,200);
	for ($i = 1; $i < func_num_args(); $i++) {
		$settings[$i-1] = func_get_arg($i);
	}
	$ymin = $settings[2];
	$ymax = $settings[3];
	$commands = "setBorder(5); initPicture({$settings[0]},{$settings[1]},{$settings[2]},{$settings[3]});";
	$alt = "Graph, window x {$settings[0]} to {$settings[1]}, y {$settings[2]} to {$settings[3]}.";
	if (strpos($settings[4],':')) {
		$lbl = explode(':',$settings[4]);
	}
	if (is_numeric($settings[4]) && $settings[4]>0) {
		$commands .= 'axes('.$settings[4].','.$settings[4].',1';
	} else if (isset($lbl[0]) && is_numeric($lbl[0]) && $lbl[0]>0 && $lbl[1]>0) {
		$commands .= 'axes('.$lbl[0].','.$lbl[1].',1';
	} else {
		$commands .= 'axes(1,1,null';
	}

	if (strpos($settings[5],':')) {
		$grid = explode(':',$settings[5]);
	}
	if (is_numeric($settings[5]) && $settings[5]>0) {
		$commands .= ','.$settings[5].','.$settings[5].');';
		$dgrid = $settings[5];
	} else if (isset($grid[0]) && is_numeric($grid[0]) && $grid[0]>0 && $grid[1]>0) {
		$commands .= ','.$grid[0].','.$grid[1].');';
		$dgrid = $grid[0];
	} else {
		$commands .= ');';
		$dgrid = 1;
	}

	foreach ($funcs as $k=>$function) {
		$alt .= "Start Graph";
		$function = explode(",",$function);
		//correct for parametric
		$func = makepretty($function[0]);
		$func = mathphp($func,"x");
		$func = str_replace("x",'$x',$func);
		$xfunc = my_create_function('$x','return ('.$func.');');
		//even though ASCIIsvg has a plot function, we'll calculate it here to hide the function
		//  function of x,filltype,fillcolor,linecolor,dash,strokewidth

		$path = '';
		$shades = '';
		$filltype = $function[1];


		$shades .= "stroke=\"blue\";strokedasharray=\"none\";";
		$alt .= "Shaded in blue " . substr($filltype,0,5);

		if ($function[2]!='') {
			$path .= "stroke=\"{$function[2]}\";";
			$alt .= ", Color {$function[2]}";
		} else {
			$path .= "stroke=\"black\";";
			$alt .= ", Color black";
		}
		if ($function[4]!='') {
			$path .= "strokewidth=\"{$function[4]}\";";
		} else {
			$path .= "strokewidth=\"1\";";
		}
		if ($function[3]!='') {
			if ($function[3]=="dash") {
				$path .= "strokedasharray=\"5\";";
				$alt .= ", Dashed";
			} else {
				$path .= "strokedasharray=\"none\";";
			}
		} else {
			$path .= "strokedasharray=\"none\";";
		}

		$xmin = $settings[0];
		$xmax = $settings[1];

		if ($_SESSION['graphdisp']==0) {
			$dx = 1;
			$alt .= "<table class=stats><thead><tr><th>x</th><th>y</th></thead></tr><tbody>";
			$stopat = ($xmax-$xmin)+1;
		} else {
			$dx = ($xmax - $xmin)/100;
			$stopat = 100;
		}
		$lasty = 0;
		$lastl = 0;
		for ($i = 0; $i<$stopat;$i++) {

			$x = $xmin + $dx*$i;
			$y = round($xfunc($x),3);

			$alt .= "<tr><td>$x</td><td>$y</td></tr>";

			if (abs($y-$lasty) > ($ymax-$ymin)) {
				if ($lastl > 1) { $path .= ']);'; $lastl = 0;}
				$lasty = $y;
			} else {
				if ($lastl == 0) {$path .= "path([";} else { $path .= ",";}
				$path .= "[$x,$y]";
				$lasty = $y;
				$lastl++;
			}
		}
		if ($lastl > 0) {$path .= "]);";}
		$alt .= "</tbody></table>\n";
		$commands .= $path;

				//do shades
		for ($i=0; $i<($xmax-$xmin)*4/$dgrid;$i++) {
			$x = $xmin + $dgrid*(.125+.25*$i);
			$y = round($xfunc($x),3);
			if ($filltype=="above") {
				$mins[$i][] = $y;
			} else if ($filltype=="below") {
				$maxs[$i][] = $y;
			}
		}
	}
	for ($i=0; $i<($xmax-$xmin)*4/$dgrid;$i++) {
		$x = $xmin + $dgrid*(.125+.25*$i);
		if (count($mins)==0) {
			$miny = $ymin-2;
		} else {
			$miny = max($mins[$i]);
		}
		if (count($maxs)==0) {
			$maxy = $ymax+2;
		} else {
			$maxy = min($maxs[$i]);
		}
		if ($miny<$maxy) {
			$shades .= "line([$x,$miny],[$x,$maxy]);";
		}
	}
	$commands .= $shades;
	if ($_SESSION['graphdisp']==0) {
		return $alt;
	} else {
		return "<embed type='image/svg+xml' align='middle' width='$settings[6]' height='$settings[7]' src='{$GLOBALS['imasroot']}/javascript/d.svg' script='$commands' />\n";
	}
}
*/

?>

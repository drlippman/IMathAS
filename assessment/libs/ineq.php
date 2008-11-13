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
//filltypes: above, below, abovediag, belowdiag  - last two draw perp to slope 
//  of curve at (xmin+xmax)/2)
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
		$xfunc = create_function('$x','return ('.$func.');');
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
		
		if ($GLOBALS['sessiondata']['graphdisp']==0) {
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
	if ($GLOBALS['sessiondata']['graphdisp']==0) {
		return $alt;
	} else {
		return "<embed type='image/svg+xml' align='middle' width='$settings[6]' height='$settings[7]' src='{$GLOBALS['imasroot']}/javascript/d.svg' script='$commands' />\n";
	}
}

//ineqbetweenplot("funcstring",[xmin,xmax,ymin,ymax,labels,grid,width,height])
//graphs a set of functions, shading the area satisfying all above or below
//  specifications.
//
//funcstring format: (function and filltype are required) - one string or array
//  function of x,above or below,linecolor,dash,strokewidth
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
		$xfunc = create_function('$x','return ('.$func.');');
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
		
		if ($GLOBALS['sessiondata']['graphdisp']==0) {
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
	if ($GLOBALS['sessiondata']['graphdisp']==0) {
		return $alt;
	} else {
		return "<embed type='image/svg+xml' align='middle' width='$settings[6]' height='$settings[7]' src='{$GLOBALS['imasroot']}/javascript/d.svg' script='$commands' />\n";
	}
}

?>

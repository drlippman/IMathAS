<?php
//Interval functions, Version 1.0 April 14, 2007

global $allowedmacros;
array_push($allowedmacros,"linegraph","forminterval");

//forminterval(min,max,minmarkertype,maxmarkertype)
//Forms an interval string, like "[2,3)"
//min,max: min and max of interval
//minmarkertype, maxmarkertype: 0 for open, 1 for closed
function forminterval($min,$max,$mint,$maxt) {
	$out = '';
	if ($mint==1) {
		$out .= '[';
	} else {
		$out .= '(';
	}
	$out .= "$min,$max";
	if ($maxt==1) {
		$out .= ']';
	} else {
		$out .= ')';
	}
	return $out;
}
	

//linegraph(intervals,[xmin,xmax,xscl,width,height])
//Creates a line graph
//intervals:  string or array of strings in interval notation, ex: [2,5)
function linegraph($intvs) {
	if (!is_array($intvs)) {
		settype($intvs,"array");
	}
	$settings = array(-5,5,1,300,100);
	for ($i = 1; $i < func_num_args(); $i++) {
		$settings[$i-1] = func_get_arg($i);
	}
	
	$commands = "setBorder(5); initPicture({$settings[0]},{$settings[1]},-.5,.5);";
	$alt = "Line Graph, x {$settings[0]} to {$settings[1]}.";
	$commands .= 'axes('.$settings[2].',1,1); stroke="blue"; strokewidth=2;';
	foreach ($intvs as $intv) {
		$commands .= 'line([';
		$intv = str_replace(' ','',$intv);
		$parts = explode(',',$intv);
		$ssym = $parts[0]{0};
		$min = substr($parts[0],1);
		$esym = substr($parts[1],-1);
		$max = substr($parts[1],0,strlen($parts[1])-1);
		if ($min=='-oo' || $min<$settings[0]) {
			$commands .= $settings[0];
		} else {
			$commands .= $min;
		}
		$commands .= ',0],[';
		if ($max=='oo' || $max=='+oo' || $max>$settings[1]) {
			$commands .= $settings[1];
		} else {
			$commands .= $max;
		}
		$commands .= ',0]);';
		$alt .= "Line from $min to $max";
		if ($min=='-oo' || $min<$settings[0]) {
			$offlitt = $settings[0] + .1;
			$commands .= 'marker="arrow";';
			$commands .= "line([$offlitt,0],[{$settings[0]},0]);";
			$commands .= 'marker="none";';
		} else {
			$commands .= 'dot(['.$min.',0],';
			if ($ssym=='[') {
				$commands .= '"closed"';
				$alt .= ", closed dot at $min";
			} else {
				$commands .= '"open"';
				$alt .= ", open dot at $min";
			}
			$commands .= ');';
		}
		if ($max=='oo' || $max=='+oo' || $max>$settings[1]) {
			$offlitt = $settings[1] - .1;
			$commands .= 'marker="arrow";';
			$commands .= "line([$offlitt,0],[{$settings[1]},0]);";
			$commands .= 'marker="none";';
		} else {
			$commands .= 'dot(['.$max.',0],';
			if ($esym==']') {
				$commands .= '"closed"';
				$alt .= ", closed dot at $max";
			} else {
				$commands .= '"open"';
				$alt .= ", open dot at $min";
			}
			$commands .= ');';
		}
	}
	if ($GLOBALS['sessiondata']['graphdisp']==0) {
		return $alt;
	} else {
		return "<embed type='image/svg+xml' align='middle' width='$settings[3]' height='$settings[4]' src='{$GLOBALS['imasroot']}/javascript/d.svg' script='$commands' />\n";
	}
	
}
?>

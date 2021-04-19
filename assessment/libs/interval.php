<?php
//Interval functions, Version 1.1 March 2, 2010

global $allowedmacros;
array_push($allowedmacros,"linegraph","linegraphbrackets","forminterval","intervalstodraw");

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
	$settings = array(-5,5,1,300,70);
	for ($i = 1; $i < func_num_args(); $i++) {
		$settings[$i-1] = func_get_arg($i);
	}

	$commands = "setBorder(5); initPicture({$settings[0]},{$settings[1]},-.5,.5);";
	$alt = "Line Graph, x {$settings[0]} to {$settings[1]}.";
	$commands .= 'axes('.$settings[2].',1,1,0,0,1,"off"); stroke="blue"; strokewidth=2;';
	foreach ($intvs as $intv) {
		$commands .= 'line([';
		$intv = str_replace(' ','',$intv);
		$parts = explode(',',$intv);
		$ssym = $parts[0][0];
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
	if ($_SESSION['graphdisp']==0) {
		return $alt;
	} else {
		return "<embed type='image/svg+xml' align='middle' width='$settings[3]' height='$settings[4]' src='{$GLOBALS['imasroot']}/javascript/d.svg' script='$commands' />\n";
	}

}

//linegraphbrackets(intervals,[xmin,xmax,xscl,width,height])
//Creates a line graph using brackets/parens instead of open/closed dots
//intervals:  string or array of strings in interval notation, ex: [2,5)
function linegraphbrackets($intvs) {
	if (!is_array($intvs)) {
		settype($intvs,"array");
	}
	$settings = array(-5,5,1,300,70);
	for ($i = 1; $i < func_num_args(); $i++) {
		$settings[$i-1] = func_get_arg($i);
	}

	$commands = "setBorder(5); initPicture({$settings[0]},{$settings[1]},-.5,.5);";
	$alt = "Line Graph, x {$settings[0]} to {$settings[1]}.";
	$commands .= 'axes('.$settings[2].',1,1,0,0,1,"off"); stroke="blue"; strokewidth=2;';
	foreach ($intvs as $intv) {
		$commands .= 'line([';
		$intv = str_replace(' ','',$intv);
		$parts = explode(',',$intv);
		$ssym = $parts[0][0];
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
			$x = $min+.1;
			if ($ssym=='[') {
				$commands .= "path([[$x,.1],[$min,.1],[$min,-.1],[$x,-.1]]);";
				$alt .= ", left bracket at $min";
			} else {
				$commands .= "arc([$x,.1],[$x,-.1],.112);";
				$alt .= ", left parenthesis at $min";
			}
		}
		if ($max=='oo' || $max=='+oo' || $max>$settings[1]) {
			$offlitt = $settings[1] - .1;
			$commands .= 'marker="arrow";';
			$commands .= "line([$offlitt,0],[{$settings[1]},0]);";
			$commands .= 'marker="none";';
		} else {
			$x = $max-.1;
			if ($esym==']') {
				$commands .= "path([[$x,.1],[$max,.1],[$max,-.1],[$x,-.1]]);";
				$alt .= ", right bracket at $max";
			} else {
				$commands .= "arc([$x,-.1],[$x,.1],.112);";
				$alt .= ", right parenthesis at $max";
			}
		}
	}
	if ($_SESSION['graphdisp']==0) {
		return $alt;
	} else {
		return "<embed type='image/svg+xml' align='middle' width='$settings[3]' height='$settings[4]' src='{$GLOBALS['imasroot']}/javascript/d.svg' script='$commands' />\n";
	}

}


//intervalstodraw(interval,xmin,xmax)
//converts an interval like (-oo,4]U(3,5] into an array of strings
//suitable for use as $answers for a drawing question with
//$answerformat = 'numberline'
//also provide xmin and xmax, the min and max of the drawing grid
//if different than the default -5 to 5
function intervalstodraw($intvs,$xmin=-5,$xmax=5) {
	$intvs = explode('U',$intvs);
	$out = array();
	foreach ($intvs as $intv) {
		$intv = str_replace(' ','',$intv);
		$parts = explode(',',$intv);
		$ssym = $parts[0][0];
		$min = substr($parts[0],1);
		$esym = substr($parts[1],-1);
		$max = substr($parts[1],0,strlen($parts[1])-1);
		$dot = '';

		if ($max=='oo' || $max>$xmax) {
			$max= $xmax+1;
		}
		if ($min=='-oo' || $min<$xmin) {
			$min = $xmin-1;
		}
		if ($min != $max) {
			$out[] = "0,$min,$max";
		}
		if ($min>=$xmin) {
			if ($ssym=='[') {
				$out[] = "$min,0,closed";
			} else {
				$out[] = "$min,0,open";
			}
		}
		if ($max<=$xmax && $min != $max) {
			if ($esym==']') {
				$out[] = "$max,0,closed";
			} else {
				$out[] = "$max,0,open";
			}
		}

	}
	return $out;
}
?>

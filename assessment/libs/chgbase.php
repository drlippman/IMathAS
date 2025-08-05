<?php
//A library of base changing functions.  
// Version 1.1, Aug 2025
// Version 1.0, Nov 13, 2007

global $allowedmacros;
array_push($allowedmacros,"baseconvert","asciitodec","dectoascii","draw_mayan_number");

//baseconvert(num,from,to)
//converts the number num from base-"from" to base-"to"
//For example, baseconvert(23,5,7) converts 23-base5 to base7
//if a base is >10, then each place should be given (or will be returned)
// separated by commas, eg. 5,12,3
function baseconvert($n,$f,$t) {
	if ($f>10) {
		$p = explode(',',$n);
		foreach ($p as $k=>$v) {
			if ($v>9) {
				$p[$k] = chr($v+87);	
			}
		}
		$n = implode('',$p);
	}
	$o = base_convert($n,$f,$t);
	if ($t>10) {
		$p = stringtoarray($o);
		foreach($p as $k=>$v) {
			if (!is_numeric($v)) {
				$p[$k] = ord($v)-87;
			}
		}
		$o = implode(',',$p);
	}
	return $o;
}

//asciitodec(char)
//returns the ASCII decimal code for a character
function asciitodec($c) {
	return ord($c);
}

//dectoascii(num)
//returns the ASCII character for a decimal value
function dectoascii($c) {
	return chr($c);
}

function draw_mayan_number($n, $width = 50, $mode = 'standard') {
	if ($mode == 'standard' || $n < 360) {
		$v = explode(',', baseconvert($n, 10, 20));
	} else if ($mode == 'modified') {
		$b = $n%360;
		$bc = explode(',', baseconvert($b, 10, 20));
		if (count($bc)==1) {
			array_unshift($bc,0);
		}
		$v = explode(',', baseconvert(($n-$b)/360, 10, 18));
		$v = array_merge($v, $bc);
	}
	$alt = 'A mayan number.';
	$multi = count($v);
	if ($multi>1) {
		$alt .= " $multi vertical groups.";
	}
	$cnt = 0;
	$ybase = 0;
	$cmd = "initPicture(-.1,1.1,-.1,$multi.1);strokewidth=0;fill='black';";
	for ($i=$multi-1;$i>=0;$i--) {
		$cnt++;
		if ($multi>1) {
			$alt .= " Group $cnt from the bottom: ";
		}
		$val = $v[$i];
		$bars = floor($val/5);
		$dots = $val%5;
		$nexty = $ybase + .2;
		if ($val == 0) {
			$ymid = $ybase + .5;
			$alt .= " A shell.";
			$cmd .= "strokewidth=2;fill='none';arc([.1,$ymid],[.9,$ymid],.6);arc([.9,$ymid],[.1,$ymid],.7);arc([.1,$ymid],[.9,$ymid],2);strokewidth=0;fill='black';";
		} else {
			if ($bars > 0 && $dots > 0) {
				$alt .= " $dots dots atop $bars bars.";
			} else if ($bars > 0) {
				$alt .= " $bars bars.";
			} else if ($dots > 0) {
				$alt .= " $dots bars.";
			}
			for ($j=0;$j<$bars;$j++) {
				$ybot = $nexty - .05;
				$ytop = $nexty + .05;
				$cmd .= "rect([0,$ybot],[1,$ytop]);";
				$nexty += .2;
			}
			$dotspace = .8/3;
			$nextx = .5 - $dotspace*($dots - 1)/2;
			for ($j=0;$j<$dots;$j++) {
				$cmd .= "circle([$nextx,$nexty],.1);";
				$nextx += $dotspace;
			}
		}
		$ybase++;
	}
	return showasciisvg($cmd, $width, $width*$multi, $alt);
}

?>

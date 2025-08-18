<?php
//A library of base changing functions.  
// Version 1.1, Aug 2025
// Version 1.0, Nov 13, 2007

global $allowedmacros;
array_push($allowedmacros,"baseconvert","asciitodec","dectoascii","draw_mayan_number","draw_babylonian_number");

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
	$alt = 'A Mayan number.';
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
		if ($bars == 0) {
			$nexty = $ybase + .5;
		} else {
			$nexty = $ybase + .2;
		}
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

function draw_babylonian_number($n, $height=40, $zeroinmiddle=0) {
	$alt = 'A Bablyonian number.';
	$v = [];
	if ($n == 0) {
		$v = [0];
	} else {
		while ($n > 0) {
			$v[] = ($n%60);
			$n = floor($n/60);
		}
	}
	$v = array_reverse($v);
	$multi = count($v);
	if ($multi > 1) {
		$alt .= " $multi horizontal groups.";
	}
	$cnt = 0;
	$xmax = 0;
	$cmd = '';
	$xstart = 0;
	$groupspacing = 2;
	foreach ($v as $k=>$val) {
		if ($k > 0) {
			$xstart += $groupspacing;
			$xmax += $groupspacing;
		}
		$cnt++;
		if ($multi > 1) {
			$alt .= "Group $cnt from the left: ";
		}

		$ones = $val%10;
		$tens = floor($val/10);
		if ($val == 0) {
			if (($zeroinmiddle == 1 && $k > 0 && $k < $multi-1) || $zeroinmiddle == 2) {
				$alt .= "Two sideways wedges. ";
				// todo: add commands
				$x1 = $xstart;
				$x2 = $xstart+1;
				$x3 = $xstart+2;
				$x4 = $xstart+3;
				$cmd .= "path([[$x3,.3],[$x2,1.3],[$x1,1.3],[$x2,2.3],[$x2,1.3]]);";
				$cmd .= "path([[$x4,1.7],[$x3,2.7],[$x2,2.7],[$x3,3.7],[$x3,2.7]]);";
				$groupwidth = 3;
			} else {
				$alt .= "Empty. ";
				$groupwidth = 3;
			}
		} else if ($ones > 0 && $tens > 0) {
			$alt .= "$ones vertical wedges and $tens sideways chevrons. ";
		} else if ($ones > 0) {
			$alt .= "$ones vertical wedges. ";
		} else if ($tens > 0) {
			$alt .= "$tens sideways chevrons. ";
		}
		if ($val > 0) {
			$onerows = ceil($ones/3);
			$onecols = ($ones > 2 ? 3 : $ones);
			$tenwidth = ($tens > 2 ? 3 : $tens);
			$totwidth = $onecols + $tenwidth;
			if ($ones > 0 && $tens > 0) {
				$totwidth += 0.3;
			}
			$groupwidth = $totwidth;
			
			$firstx = $xstart + $groupwidth / 2 - $totwidth / 2;

			if ($tens > 0) {
				if ($tens < 4) {
					for ($i=0;$i<$tens;$i++) {
						$cmd .= gen_babylonian_ten($firstx+$i,2,2);
					}
				} else {
					$cmd .= gen_babylonian_ten($firstx,1.6,.6);
					$cmd .= gen_babylonian_ten($firstx+1,2.4,.6);
					$cmd .= gen_babylonian_ten($firstx+2,3.2,.6);
					$cmd .= gen_babylonian_ten($firstx+2,1.6,.6);
					if ($tens == 5) {
						$cmd .= gen_babylonian_ten($firstx+1,.8,.6);
					}
				}
				$firstx += $tenwidth + 0.3;
			}
			if ($ones > 0) {
				$rh = 4/($onerows+1);
				for ($r=0;$r<$onerows;$r++) {
					$thiscols = 3;
					if ($r == 0 && ($ones%3) > 0) {
						$thiscols = $ones%3;
					}
					if ($onerows > 1) {
						$rfirstx = $firstx + 1.5 - $thiscols/2;
					} else {
						$rfirstx = $firstx;
					}
					for ($c=0;$c<$thiscols;$c++) {
						$cmd .= gen_babylonian_one($rfirstx+$c, $r > 0 ? $rh*($r+1) : $rh*$r, $rh, $r > 0);
					}
				}
			}
		}
		//$cmd .= "rect([$xstart,-1],[$xstart+$groupwidth,5]);";
		$xstart += $groupwidth;
		$xmax += $groupwidth;
	}
	$cmd = "setBorder(2,0,15,0);initPicture(0,$xmax,-.5,4.5);fill='none';" . $cmd;
	return showasciisvg($cmd, $height*$xmax/5+17, $height, $alt);
}
function gen_babylonian_one($x,$y,$rh,$stubby) {
	if ($stubby) {
		$yt = $y+$rh;
		$xr = $x+1;
		$xh = $x+.5;
		$hy = $y + .3*$rh;
		$yb = $y;
	} else {
		$yt = $y+$rh*2;
		$xr = $x+1;
		$xh = $x+.5;
		$hy = $y + $rh;
		$yb = $y;
	}
	return "path([[$xh,$hy],[$x,$yt],[$xr,$yt],[$xh,$hy],[$xh,$yb]]);";
}
function gen_babylonian_ten($x,$y,$h) {
	$yb = $y-$h;
	$yt = $y+$h;
	$xr = $x+1;
	$xh = $x+.5;
	$ybh = $y - $h/2;
	$yth = $y + $h/2;
	return "path([[$xr,$yt],[$x,$y],[$xr,$yb]]);line([$xh,$yth],[$xh,$ybh]);";
}

?>

<?php
//IMathAS:  Core randomizers and display macros
//(c) 2006 David Lippman


array_push($allowedmacros,"sec","csc","cot","rand","rrand","rands","rrands","randfrom","randsfrom","jointrandfrom","diffrandsfrom","nonzerorand","nonzerorrand","nonzerorands","nonzerorrands","diffrands","diffrrands","nonzerodiffrands","nonzerodiffrrands","singleshuffle","jointshuffle","makepretty","makeprettydisp","showplot","addlabel","showarrays","horizshowarrays","showasciisvg","listtoarray","arraytolist","calclisttoarray","sortarray","consecutive","gcd","lcm","calconarray","mergearrays","sumarray","dispreducedfraction","diffarrays","intersectarrays","joinarray","unionarrays","count");





function mergearrays($a,$b) {
	return array_merge($a,$b);
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
	} else if (isset($grid[0]) && is_numeric($grid[0]) && $grid[0]>0 && $grid[1]>0) {
		$commands .= ','.$grid[0].','.$grid[1].');';
	} else {
		$commands .= ');';
	}
		
	foreach ($funcs as $function) {
		$alt .= "Start Graph";
		$function = explode(",",$function);
		//correct for parametric
		if (strpos($function[0],"[")===0) {
			$isparametric = true;
			$xfunc = makepretty(str_replace("[","",$function[0]));
			$xfunc = mathphp($xfunc,"t");
			$xfunc = str_replace("(t)",'($t)',$xfunc);
			$yfunc = makepretty(str_replace("]","",$function[1]));
			$yfunc = mathphp($yfunc,"t");
			$yfunc = str_replace("(t)",'($t)',$yfunc);
			array_shift($function);
		} else {
			$isparametric = false;
			$func = makepretty($function[0]);
			$func = mathphp($func,"x");
			$func = str_replace("(x)",'($x)',$func);
		}
		
		//even though ASCIIsvg has a plot function, we'll calculate it here to hide the function
		
		
		$path = '';
		if ($function[1]!='') {
			$path .= "stroke=\"{$function[1]}\";";
			$alt .= ", Color {$function[1]}";
		} else {
			$path .= "stroke=\"black\";";
			$alt .= ", Color black";
		}
		if ($function[6]!='') {
			$path .= "strokewidth=\"{$function[6]}\";";
		} else {
			$path .= "strokewidth=\"1\";";
		}
		if ($function[7]!='') {
			if ($function[7]=="dash") {
				$path .= "strokedasharray=\"5\";";
				$alt .= ", Dashed";
			} else {
				$path .= "strokedasharray=\"none\";";
			}
		} else {
			$path .= "strokedasharray=\"none\";";
		}
		
		$avoid = array();
		if ($function[2]!='') {$xmin = $function[2];} else {$xmin = $settings[0];}
		if ($function[3]!='') {
			$xmaxarr = explode('!',$function[3]);
			$xmax = $xmaxarr[0];
			$avoid = array_slice($xmaxarr,1);
		} else {$xmax = $settings[1];}
		
		if ($GLOBALS['sessiondata']['graphdisp']==0) {
			$dx = 1;
			$alt .= "<table class=stats><thead><tr><th>x</th><th>y</th></thead></tr><tbody>";
			$stopat = ($xmax-$xmin)+1;
		} else {
			$dx = ($xmax - $xmin)/100;
			$stopat = 101;
		}
		$lasty = 0;
		$lastl = 0;
		for ($i = 0; $i<$stopat;$i++) {
			if ($isparametric) {
				$t = $xmin + $dx*$i;
				if (in_array($t,$avoid)) { continue;}
				$x = round(eval("return ($xfunc);"),3);
				$y = round(eval("return ($yfunc);"),3);
			} else {
				$x = $xmin + $dx*$i;
				if (in_array($x,$avoid)) { continue;}
				$y = round(eval("return ($func);"),3);
			}
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
		if ($function[5]=='open') {
			$path .= "dot([$x,$y],\"open\");";
			$alt .= "Open dot at $x,$y";
		} else if ($function[5]=='closed') {
			$path .= "dot([$x,$y],\"closed\");";
			$alt .= "Closed dot at $x,$y";
		}
		if ($function[4]=='open') {
			$x = $xmin; $y = round(eval("return ($func);"),4); $path .= "dot([$x,$y],\"open\");";
			$alt .= "Open dot at $x,$y";
		} else if ($function[4]=='closed') {
			$x = $xmin; $y = round(eval("return ($func);"),4); $path .= "dot([$x,$y],\"closed\");";
			$alt .= "Closed dot at $x,$y";
		}
		
		$commands .= $path;
	}
	if ($GLOBALS['sessiondata']['graphdisp']==0) {
		return $alt;
	} else {
		return "<embed type='image/svg+xml' align='middle' width='$settings[6]' height='$settings[7]' src='{$GLOBALS['imasroot']}/javascript/d.svg' script='$commands' />\n";
	}
}


function addlabel($plot,$x,$y,$lbl) {
	if (func_num_args()>4) {
		$color = func_get_arg(4);
	} else {
		$color = black;
	}
	if (func_num_args()>5) {
		$loc = func_get_arg(5);
		$plot = str_replace("' />","fontfill=\"$color\";text([$x,$y],\"$lbl\",\"$loc\");' />",$plot);
	} else {
		$plot = str_replace("' />","fontfill=\"$color\";text([$x,$y],\"$lbl\");' />",$plot);
	}
	return $plot;
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
	$out = '<table class=stats><thead><tr>';
	for ($i = 0; $i<count($alist)/2; $i++) {
		$out .= "<th scope=\"col\">{$alist[2*$i]}</th>";
	}
	$out .= "</tr></thead><tbody>";
	for ($j = 0; $j<count($alist[1]); $j++) {
		$out .="<tr>";
		for ($i = 0; $i<count($alist)/2; $i++) {
			$out .= "<td>{$alist[2*$i+1][$j]}</td>";
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
	$exp = str_replace(" ", "", $exp);
	$exp = str_replace("+-","-",$exp);
	$exp = str_replace("-+","-",$exp);
	$exp = str_replace("--","+",$exp);
	//$exp = preg_replace('/^1\*?([a-zA-Z\(])/',"$1",$exp);
	//$exp = preg_replace('/([^\d\^\.])1\*?([a-zA-Z\(])/',"$1$2",$exp);
	return $exp;
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
	if ($p==0) {echo "Error with rrand: need to set step size"; return false;}
	return($min + $p*rand(0,($max-$min)/$p));	
}


function rands($min,$max,$n) {
	for ($i = 0; $i < $n; $i++) {
		$r[$i] = rand($min,$max);
	}
	return $r;
}


function rrands($min,$max,$p,$n) {
	if ($p==0) {echo "Error with rrands: need to set step size"; return false;}
	for ($i = 0; $i < $n; $i++) {
		$r[$i] = $min + $p*rand(0,($max-$min)/$p);
	}
	return $r;
}


function randfrom($lst) {
	if (!is_array($lst)) {
		$lst = explode(",",$lst);
	}
	return $lst[rand(0,count($lst)-1)];	
}


function randsfrom($lst,$n) {
	if (!is_array($lst)) {
		$lst = explode(",",$lst);
	}
	for ($i=0; $i<$n;$i++) {
		$r[$i] = $lst[rand(0,count($lst)-1)];
	}
	return $r;	
}


function jointrandfrom($lst1,$lst2) {
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
	if (!is_array($lst)) {
		$lst = explode(",",$lst);
	}
	shuffle($lst);
	return array_slice($lst,0,$n);	
}


function nonzerorand($min,$max) {
	do {
		$ret = rand($min,$max);
	} while ($ret == 0);
	return $ret;
}


function nonzerorrand($min,$max,$p) {
	if ($p==0) {echo "Error with nonzerorrand: need to set step size"; return false;}
	do {
		$ret = $min + $p*rand(0,($max-$min)/$p);
	} while ($ret == 0);
	return $ret;
}


function nonzerorands($min,$max,$n) {
	for ($i = 0; $i < $n; $i++) {	
		do {
			$r[$i] = rand($min,$max);
		} while ($r[$i] == 0);
	}
	return $r;
}


function nonzerorrands($min,$max,$p,$n) {
	if ($p==0) {echo "Error with nonzerorrands: need to set step size"; return false;}
	for ($i = 0; $i < $n; $i++) {	
		do {
			$r[$i] = $min + $p*rand(0,($max-$min)/$p);
		} while ($r[$i] == 0);
	}
	return $r;
}


function diffrands($min,$max,$n) {
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
		shuffle($r);
		return array_slice($r,0,$n);
	}
}


function diffrrands($min,$max,$p,$n) {
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
		shuffle($r);
		$r = array_slice($r,0,$n);
		for ($i=0;$i<$n;$i++) {
			$r[$i] = $min+$p*$r[$i];
		}
		return $r;
	}
}


function nonzerodiffrands($min,$max,$n) {
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
	if ($p==0) {echo "Error with nonzerodiffrrands: need to set step size"; return false;}
	$maxi = ($max-$min)/$p;
	if ($n<.1*$maxi) {
		$out = array();
		
		while (count($out)<$n) {
			$x = $min + $p*rand(0,$maxi);
			if ($x!=0 && !in_array($x,$out)) {
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
	return (explode(",",$l));	
}


function arraytolist($a) {
	return (implode(',',$a));
}

function joinarray($a,$s) {
	return (implode($s,$a));
}


function calclisttoarray($l) {
	$l = explode(",",$l);
	foreach ($l as $k=>$tocalc) {
		$l[$k] = mathphp($tocalc,null);
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


function consecutive($min,$max) {
	if (func_num_args()>2) {
		$step = func_get_arg(2);
	} else {$step = 1;}
	
	for ($i=$min;$i<$max+$step/100.0;$i+=$step) {
		$a[] = $i;
	}
	return $a;
}


function gcd($n,$m){ //greatest common divisor
	$m = abs($m);
	$n = abs($n);
	if(!$m)return$n;
	if(!$n)return$m;
	return $m<$n?gcd($m,$n%$m):gcd($n,$m%$n);
}
function lcm($n, $m) //least common multiple 
{ 
   return $m*($n/gcd($n,$m)); 
} 

function dispreducedfraction($n,$d) {
	$g = gcd($n,$d);
	$n = $n/$g;
	$d = $d/$g;	
	if ($d<0) {
		$n = $n*-1;
		$d = $d*-1;
	}
	if ($d==1) {
		return "`$n`";
	} else {
		return "`$n/$d`";
	}
}

//use: calconarray($a,"x^$p")
function calconarray($array,$todo) {
	global $disallowedwords,$allowedmacros;
	$todo = str_replace($disallowedwords,"",$todo);
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
	$todo = mathphp($todo,'x');
	$todo = str_replace('(x)','($x)',$todo);
	return array_map(create_function('$x','return('.$todo.');'),$array);	
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




?>

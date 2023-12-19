<?php
//A library of Geometry functions.  Version 1.0, June 1, 2006
//WORK IN PROGRESS

global $allowedmacros;
array_push($allowedmacros,"drawrect","drawtriSSS");

//draws a rectangle.
//drawrect(w,h,[top,right,bottom,left])
//w and h are width and height of the rectangle
//top,right,bottom,left are optional, for labeling the sides
function drawrect($w,$h) {
	$labels =   array_slice(func_get_args(),2);
	for ($i=0;$i<4;$i++) {
		if (!isset($labels[$i])) { $labels[$i] = '';}
		$len[$i] = strlen($labels[$i]);
	}
	$m = max($w,$h);
	$com = "setBorder(10); initPicture(".(-.1*$m*$len[3]).','.($w+.1*$m*$len[1]).','.(-.1*$m*$len[2]).','.($h+.1*$m*$len[0]).');';
	$com .= "rect([0,0],[$w,$h]);";
	if ($len[0]>0) {
		$com .= "text([$w/2,$h],\"{$labels[0]}\",\"above\");";
	}
	if ($len[1]>0) {
		$com .= "text([$w,$h/2],\"{$labels[1]}\",\"right\");";
	}
	if ($len[2]>0) {
		$com .= "text([$w/2,0],\"{$labels[2]}\",\"below\");";
	}
	if ($len[3]>0) {
		$com .= "text([0,$h/2],\"{$labels[3]}\",\"left\");";
	}
	if ($w>$h) {
		$dw = 200; 
		//$dh = 200*$h/$w;
		$dh = 200*($h+.3*$m)/($w);
	} else {
		$dh = 200;
		//$dw = 200*$w/$h;
		$dw = 200*($w+.1*$m*$len[1]+.1*$m*$len[3])/($h);
	}
	//$dw = 200;
	//$dh = 200;
	return showasciisvg($com,$dw,$dh);
}

//draws a triangle given 3 sides.
//drawtriSSS(a,b,c,[la,lb,lc])
//a,b,c are sides of the triangle, b is the flat base
//la,lb,lc are optional, for labeling the sides
function drawtriSSS($a,$b,$c) {
	$labels =   array_slice(func_get_args(),3);
	for ($i=0;$i<6;$i++) {
		if (!isset($labels[$i])) { $labels[$i] = '';}
		$len[$i] = strlen($labels[$i]);
	}
	$xp = ($a*$a+$b*$b-$c*$c)/(2*$b);
	$yp = sqrt($a*$a - $xp*$xp);
	//$com = "setBorder(10); initPicture(".(-.1*$m*$len[3]).','.($w+.1*$m*$len[1]).','.(-.1*$m*$len[2]).','.($h+.1*$m*$len[0]).');';
	$m = max(max($b,$xp),$yp);
	$com = "setBorder(10); initPicture(-.1*$m,1.1*$m,-.1*$m,1.1*$m);";
	$com .= "path([[0,0],[$xp,$yp],[$b,0],[0,0]]);";
	
	if ($len[0]>0) {
		$com .= "text([$xp/2-.1*$b/$yp,$yp/2+.1*$b/$yp],\"{$labels[0]}\",\"left\");";
	}
	if ($len[1]>0) {
		$com .= "text([$b/2,0],\"{$labels[1]}\",\"below\");";
	}
	if ($len[2]>0) {
		$com .= "text([($b+$xp+.1*$b/$yp)/2,$yp/2+.1*$b/$yp],\"{$labels[2]}\",\"right\");";
	}
		
	$dw = 200;
	$dh = 200;
	return showasciisvg($com,$dw,$dh);
}
?>

<?php 
//various line utility functions


global $allowedmacros;
array_push($allowedmacros,"lineboundarycoord");


//function lineboundarycoord(xmin,xmax,ymin,ymax,m,b,[order])
//looks for where the line crosses the graph boundary.  
//supply graph boundaries, and m and b of the line.
//order can be an array of "right", "top", "left", "bottom" indicating order of search
//returns array(x,y,loc), where loc is a best guess for where it would be good to put the label
function lineboundarycoord($xmin,$xmax,$ymin,$ymax,$m,$b,$order=array("right","top","left","bottom")) {
	foreach ($order as $ord) {
		if ($ord=='right') {
			$yr = $m*$xmax+$b;
			if ($yr<=$ymax && $yr >= $ymin) {
				return array($xmax,$yr,($m>0)?"aboveleft":"belowleft");
			}
		} else if ($ord=='top' && $m!=0) {
			$xt = ($ymax - $b)/$m;
			if ($xt<=$xmax && $xt >= $xmin) {
				return array($xt,$ymax,($m>0)?"belowright":"belowleft");
			}
		} else if ($ord=='left') {
			$yl = $m*$xmin+$b;
			if ($yl<=$ymax && $yl >= $ymin) {
				return array($xmin,$yl,($m>0)?"belowright":"aboveright");
			}
		} else if ($ord=='bottom' && $m!=0) {
			$xb = ($ymin - $b)/$m;
			if ($xb<=$xmax && $xb >= $xmin) {
				return array($xb,$ymin,($m>0)?"aboveleft":"aboveright");
			}
		}
	}
}


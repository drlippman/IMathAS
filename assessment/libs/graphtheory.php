<?php
//A library of graph theory functions.  Version 0.1, May 6, 2009
//THIS LIBRARY IS NOT COMPLETE.  THE SYNTAX OR NAMES OF THESE FUNCTIONS
//MAY CHANGE

global $allowedmacros;
array_push($allowedmacros,"graphlayout","graphcirclelayout","graphcomplete","graphgridlayout");
	
//graphlayout(g,[options])
//draws a graph based on a graph incidence matrix
//using a randomized spring layout engine
//g is a 2-dimensional upper triangular matrix
//g[i][j] > 0 if vertices i and j are connected. i<j used if
//not a digraph
//options is an array of options:
//  options['width'] = width of output, in pixels.  Defaults to 300.
//  options['height'] = height of output, in pixels.  Defaults to 300.
//  options['digraph'] = true/false.  If true, g[i][j] > 0 means i leads to j
//  options['useweights'] = true/false.  If true, g[i][j] used as a weight
//  options['labels'] = "letters" or array of labels.  If "letters", letters
//    A-Z used for labels.  If array, label[i] used for vertex g[i]
function graphlayout($g,$op=array()) {
	$iterations = 40;
	$t = 1;
	$dim = 2;
	$n = count($g[0]);
	$k = sqrt(1/$n);
	$dt = $t/$iterations;
	$pos = array();
	
	for ($i=0; $i<$n; $i++) {
		$pos[$i] = array();
		for ($x = 0; $x<$dim; $x++) {
			$pos[$i][$x] = rand(0,32000)/32000;
		}
	}
	
	for ($it = 0; $it<$iterations; $it++) {
		for ($i = 0; $i<$n; $i++) {
			for ($x = 0; $x<$dim; $x++) {
				$disp[$i][$x] = 0;
			}
		}
		for ($i = 0; $i<$n; $i++) {
			for ($j = $i+1; $j<$n; $j++) {
				$square_dist = 0;
				for ($x = 0; $x<$dim; $x++) {
					$delta[$x] = $pos[$i][$x] - $pos[$j][$x];
					$square_dist += $delta[$x]*$delta[$x];
				}
				if ($square_dist<0.01) {
					$square_dist = 0.01;
				}
				//repel
				$force = $k*$k/$square_dist;
				//if neighbors, attract
				if ($g[$i][$j]>0 || $g[$j][$i]>0) {
					$force -= sqrt($square_dist)/$k;
				}
				for ($x = 0; $x<$dim; $x++) {
					$disp[$i][$x] += $delta[$x]*$force;
					$disp[$j][$x] -= $delta[$x]*$force;
				}	
			}
		}
		for ($i = 0; $i<$n; $i++) {
			$square_dist = 0;
			for ($x = 0; $x<$dim; $x++) {
				$square_dist += $disp[$i][$x]*$disp[$i][$x];
			}
			$scale = $t/($square_dist<0.01?1:sqrt($square_dist));
			for ($x = 0; $x<$dim; $x++) {
				$pos[$i][$x] += $disp[$i][$x]*$scale;
			}
		}
		$t -= $dt;
	}
	
	$pxmin = 100; $pxmax = -100; $pymin = 100; $pymax = -100;
	for ($i=0; $i<$n; $i++) {
		if ($pos[$i][0]<$pxmin) {$pxmin = $pos[$i][0];}
		if ($pos[$i][0]>$pxmax) {$pxmax = $pos[$i][0];}
		if ($pos[$i][1]<$pymin) {$pymin = $pos[$i][1];}
		if ($pos[$i][1]>$pymax) {$pymax = $pos[$i][1];}
	}
	$op['xmin'] = $pxmin;
	$op['xmax'] = $pxmax;
	$op['ymin'] = $pymin;
	$op['ymax'] = $pymax;
	
	return graphdrawit($pos,$g,$op);
	
}

//graphcirclelayout(graph,[width,height])
//draws a graph based on a graph incidence matrix
//using a rectangular grid layout.  Could hide
//some edges that connect colinear vertices
//g is a 2-dimensional upper triangular matrix
//g[i][j] = 1 if vertexes i and j are connected, i<j
function graphgridlayout($g,$op=array()) {
	$n = count($g[0]);
	$sn = ceil(sqrt($n));
	$gd = 10/$sn;
	for ($i=0; $i<$n; $i++) {
		$pos[$i][0] = floor($i/$sn)*$gd;
		$pos[$i][1] = ($i%$sn)*$gd;
	}	
	$op['xmin'] = 10;
	$op['xmax'] = 0;
	$op['ymin'] = 10;
	$op['ymax'] = 0;
	for ($i=0; $i<$n; $i++) {
		if ($pos[$i][0]<$op['xmin']) {$op['xmin'] = $pos[$i][0];}
		if ($pos[$i][0]>$op['xmax']) {$op['xmax'] = $pos[$i][0];}
		if ($pos[$i][1]<$op['ymin']) {$op['ymin'] = $pos[$i][1];}
		if ($pos[$i][1]>$op['ymax']) {$op['ymax'] = $pos[$i][1];}
	}
	return graphdrawit($pos,$g,$op);
}

//graphcirclelayout(graph,[width,height])
//draws a graph based on a graph incidence matrix
//using a circular layout
//g is a 2-dimensional upper triangular matrix
//g[i][j] = 1 if vertexes i and j are connected, i<j
function graphcirclelayout($g,$op=array()) {
	$n = count($g[0]);
	$dtheta = 2*M_PI/$n;
	for ($i = 0; $i<$n; $i++) {
		$pos[$i][0] = 10*cos($dtheta*$i);
		$pos[$i][1] = 10*sin($dtheta*$i);
	}
	$op['xmin'] = -10;
	$op['xmax'] = 10;
	$op['ymin'] = -10;
	$op['ymax'] = 10;
	return graphdrawit($pos,$g,$op);
}

//graphcomplete(n,[width,height])
//draws a complete graph with a circular layout
//with n vertices
function graphcomplete($n,$op=array()) {
	$dtheta = 2*M_PI/$n;
	for ($i = 0; $i<$n; $i++) {
		$pos[$i][0] = 10*cos($dtheta*$i);
		$pos[$i][1] = 10*sin($dtheta*$i);
		$g[$i] = array_fill(0,$n,1);
	}
	$op['xmin'] = -10;
	$op['xmax'] = 10;
	$op['ymin'] = -10;
	$op['ymax'] = 10;
	return graphdrawit($pos,$g,$op);
}

//internal function, not to be used
function graphdrawit($pos,$g,$op) {
	if (!isset($op['width'])) {$op['width'] = 300;}
	if (!isset($op['height'])) {$op['height'] = 300;}
	$lettersarray = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
	$com = "setBorder(40);initPicture({$op['xmin']},{$op['xmax']},{$op['ymin']},{$op['ymax']});";
	$cx = ($op['xmin'] + $op['xmax'])/2;
	$cy = ($op['ymin'] + $op['ymax'])/2;
	
	$n = count($pos);
	for ($i=0; $i<$n; $i++) {
		$com .= "dot([".$pos[$i][0].",".$pos[$i][1]."]);";
		if (isset($op['labels'])) {
			if ($pos[$i][1]>$cy) { $ps = "above"; } else {$ps = "below";}
			if ($pos[$i][0]>$cx) { $ps .= "right"; } else {$ps .= "left";}
			if (is_array($op['labels'])) {
				$com .= "fontfill='blue';text([".$pos[$i][0].",".$pos[$i][1]."],'".$op['labels'][$i]."','$ps');";	
			} else {
				$com .= "fontfill='blue';text([".$pos[$i][0].",".$pos[$i][1]."],'".$lettersarray[$i]."','$ps');";	
			}
		}
		for ($j=$i+1; $j<$n; $j++) {
			if ($op['digraph']) {
				if ($g[$j][$i]>0 && $g[$i][$j]==0) {
					$com .= 'marker="arrow";';	
					$com .= "line([".$pos[$j][0].",".$pos[$j][1]."],[".$pos[$i][0].",".$pos[$i][1]."]);";
				} else if ($g[$i][$j]>0 && $g[$j][$i]==0) {
					$com .= 'marker="arrow";';	
					$com .= "line([".$pos[$i][0].",".$pos[$i][1]."],[".$pos[$j][0].",".$pos[$j][1]."]);";
				} else if ($g[$j][$i]>0 && $g[$i][$j]>0) {
					$com .= 'marker=null;';
					$com .= "line([".$pos[$j][0].",".$pos[$j][1]."],[".$pos[$i][0].",".$pos[$i][1]."]);";
				}
				
			} else {
				if ($g[$i][$j]>0) {
					$com .= "line([".$pos[$i][0].",".$pos[$i][1]."],[".$pos[$j][0].",".$pos[$j][1]."]);";
				}
			}
			if ($op['useweights'] && ($g[$i][$j]>0 || $g[$j][$i]>0)) {
				$mx = ($pos[$i][0] + $pos[$j][0])/2;
				$my = ($pos[$i][1] + $pos[$j][1])/2;
				$com .= "fontfill='red';text([$mx,$my],'".max($g[$i][$j],$g[$j][$i])."');";
			}
		}
	}
	return showasciisvg($com,$op['width'],$op['height']);	
}



?>

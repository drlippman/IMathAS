<?php
//A library of graph theory functions.  Version 0.1, May 6, 2009
//THIS LIBRARY IS NOT COMPLETE.  THE SYNTAX OR NAMES OF THESE FUNCTIONS
//MAY CHANGE
//Most graphing functions in this library use an options array.  Here are the
//common options - specific functions will mention other options.
//  options['width'] = width of output, in pixels.  Defaults to 300.
//  options['height'] = height of output, in pixels.  Defaults to 300.
//  options['digraph'] = true/false.  If true, g[i][j] > 0 means i leads to j
//  options['useweights'] = true/false.  If true, g[i][j] used as weight
//  options['labels'] = "letters" or array of labels.  If "letters", letters
//    A-Z used for labels.  If array, label[i] used for vertex g[i]
//  options['connected'] = true/false.  When randomizing graphs, whether you
//    want to force the result to be connected.  If false for a tree, this
//    forces a disconnected graph
//  options['randweights'] = max or array(min,max).  Randomizes weights of edges
//  options['randedges'] = probability (0-1).  Randomly keeps edges of original 
//    graph with given probability.
//  options['tree'] = true.  Creates a minimum cost spanning tree from the original graph
//  options['labelposition'] = "above","below","right","left","aboveright",etc.
//    position of vertex labels.  

global $allowedmacros;
array_push($allowedmacros,"graphspringlayout","graphcirclelayout","graphgridlayout","graphpathlayout","graphcircleladder","graphcircle","graphbipartite","graphgrid","graphrandom","graphrandomgridschedule","graphemptygraph","graphdijkstra","graphbackflow","graphkruskal","graphadjacencytoincidence","graphincidencetoadjacency");
	
//graphspringlayout(g,[options])
//draws a graph based on a graph incidence matrix
//using a randomized spring layout engine
//g is a 2-dimensional upper triangular matrix
//g[i][j] > 0 if vertices i and j are connected. i<j used if
//not a digraph
function graphspringlayout($g,$op=array()) {
	$iterations = 40;
	$t = 2;
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
	
	return graphdrawit($pos,$g,$op);
}

//graphcirclelayout(graph,[options])
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

//graphgridlayout(graph,[options])
//draws a graph based on a graph incidence matrix
//using a rectangular grid layout.  Could hide
//some edges that connect colinear vertices
//use options['wiggle'] = true to perterb off exact grid
//g is a 2-dimensional matrix
//g[i][j] = 1 if vertexes i and j are connected
function graphgridlayout($g,$op=array()) {
	$n = count($g[0]);
	if (isset($op['gridv'])) {
		$sn = $op['gridv'];
	} else {
		$sn = ceil(sqrt($n));
	}
	$gd = 10/$sn;
	for ($i=0; $i<$n; $i++) {
		$pos[$i][0] = floor($i/$sn)*$gd  + ($op['wiggle']?$gd/5*sin(3*$i):0);;
		$pos[$i][1] = ($i%$sn)*$gd + ($op['wiggle']?$gd/5*sin(4*$i):0);
	}	
	return graphdrawit($pos,$g,$op);
}

//graphpathlayout(graph,[options])
//draws a graph based on a graph incidence matrix
//using a backflow to place the vertices in approximate
//order of incidence.  Could hide
//some edges that connect colinear vertices
//use options['wiggle'] = true to perterb off exact grid
//g is a 2-dimensional matrix
//g[i][j] = 1 if vertexes i and j are connected
function graphpathlayout($g,$op=array()) {
	$n = count($g[0]);
	list($dist,$next) = graphbackflow($g);
	$maxh = max($dist);
	$maxv = ceil($n/$maxh);
	$dh = 10/$maxh;
	$dv = 10/$maxv;
	$odv = $dv/$maxh;
	
	
	for ($i=0; $i<$n; $i++) {
		if ($dist[$i]<0) { $dist[$i] = 0;}
		$pos[$i][0] = 1-$dh*$dist[$i];
		$pos[$i][1] = 5 + ($loccnt[$dist[$i]]%2==0?1:-1)*$dv*ceil($loccnt[$dist[$i]]/2)+ ($op['wiggle']?$dv/5*sin(4*$dist[$i]):0);
		$loccnt[$dist[$i]]++;
	}

	return graphdrawit($pos,$g,$op);
}

//graphcircleladder(n,m,[options])
//draws a circular ladder graph
//n vertices around a circle
//m concentric circles
//connected around circle and between circles
//returns array($pic,$g)
function graphcircleladder($n,$m,$op=array()) {
	$tot = $n*$m;
	$dtheta = 2*M_PI/$n;
	$dr = 10/$m;
	$g = graphemptygraph($tot);
	for ($i = 0; $i<$n; $i++) {
		$c = cos($dtheta*$i);
		$s = sin($dtheta*$i);
		for ($j = 0; $j<$m; $j++) {
			$pos[$n*$j+$i][0] = ($j+1)*$dr*$c;
			$pos[$n*$j+$i][1] = ($j+1)*$dr*$s;
			if ($i<$n-1) {
				$g[$n*$j+$i][$n*$j+$i+1] = 1;  //around circle
			} else {
				$g[$n*$j][$n*$j+$i] = 1;  //around circle
			}
			if ($j<$m-1) {
				$g[$n*$j+$i][$n*($j+1)+$i] = 1;  //inside circle
			}
		}
	}
	//print_r($g);
	$op['xmin'] = -10;
	$op['xmax'] = 10;
	$op['ymin'] = -10;
	$op['ymax'] = 10;
	$g = graphprocessoptions($g,$op);
	return array(graphdrawit($pos,$g,$op),$g);
}


//graphcircle(n,[options])
//draws a complete graph with a circular layout
//with n vertices
//returns array($pic,$g)
function graphcircle($n,$op=array()) {
	$g = graphemptygraph($n);
	for ($i = 0; $i<$n; $i++) {
		for ($j=$i+1;$j<$n; $j++) {
			$g[$i][$j] = 1;
		}
	}
	$g = graphprocessoptions($g,$op);
	return array(graphcirclelayout($g,$op),$g);
}

//graphbipartite(n,[options])
//draws a complete bipartite graph (every vertex on left is 
//connected to every vertex on the right)
//with n vertices in the first column, m in the second
//returns array($pic,$g)
function graphbipartite($n,$m,$op=array()) {
	$tot = $n+$m;
	$g = graphemptygraph($tot);
	$dv = 10/($n+1);
	for ($i=0; $i<$n; $i++) {
		$pos[$i][0] = -10;
		$pos[$i][1] = ($i+1)*$dv;
	}
	$dv = 10/($m+1);
	for ($i=0; $i<$m; $i++) {
		$pos[$n+$i][0] = 10;
		$pos[$n+$i][1] = ($i+1)*$dv;
	}
	for ($i=0; $i<$n; $i++) {
		for ($j=$n; $j<$tot; $j++) {
			$g[$i][$j] = 1;
		}
	}
		
	$g = graphprocessoptions($g,$op);
	return array(graphdrawit($pos,$g,$op),$g);
}

//graphgrid(n, m, [options])
//draws a n by m grid of vertices.
//returns array($pic,$g)
function graphgrid($n,$m,$op=array()) {
	$tot = $n*$m;
	$g = graphemptygraph($tot);
	for ($i = 0; $i<$tot; $i++) {
		if (($i+1)%$n!=0) {
			$g[$i][$i+1] = 1;
		}
		if (($i+$n<$tot)) {
			$g[$i][$i+$n] = 1;
		}
	}
	$op['gridv'] = $n;
	$g = graphprocessoptions($g,$op);
	return array(graphgridlayout($g,$op),$g);	
}

//graphrandom(n, p,[options])
//draws a random graph with n vertices. 
function graphrandom($n,$p,$op=array()) {
	$g = graphemptygraph($n);
	for ($i = 0; $i<$n; $i++) {
		for ($j=$i+1;$j<$n; $j++) {
			$g[$i][$j] = 1;
		}
	}
	$g = graphprocessoptions($g,$op);
	
	return array(graphspringlayout($g,$op),$g);	
}

//graphrandomgridschedule(n, m, p,[options])
//draws a n by m grid of vertices.  Each pair of neighboring
//and diagonal vertices has a p probabilility (0 to 1) of being connected
//a start and end vertex are added
//options['weights'] as an array of n*m elements will be used as weights.
//options['weights'] as a single number will randomize weights from 1 to that
//  value
//if options['labels'] are used, "start" and "end" will be added automatically
function graphrandomgridschedule($n,$m,$p,$op=array()) {
	$op['digraph'] = true;
	$lettersarray = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
	$tot = $n*$m+2;
	$useweights = false;
	if (isset($op['weights'])) {
		$useweights = true;
		if (!is_array($op['weights'])) {
			$op['weights'] = rands(1,$op['weights'],$n*$m);
		}
	}
			
	$g = graphemptygraph($tot);
	$sn = $n;
	$gd = 10/$sn;
	$pos[0][0] = -$gd;
	$pos[0][1] = $gd*(($n-1)/2);
	$pos[$tot-1][0] = $m*$gd;
	$pos[$tot-1][1] = $gd*(($n-1)/2);
	for ($i=1; $i<$tot-1; $i++) {
		$pos[$i][0] = floor(($i-1)/$sn)*$gd  + ($op['wiggle']?$gd/5*sin(3*$i):0);;
		$pos[$i][1] = (($i-1)%$sn)*$gd + ($op['wiggle']?$gd/5*sin(4*$i):0);
	}	
	//connections to start and end
	for ($i = 1; $i<$n+1; $i++) {
		$g[0][$i] = 1;
		$g[$tot-1-$i][$tot-1] = 1;
	}
	//connections between
	for ($i = 1; $i<$tot; $i++) {
		if ($i<$tot-$n) {
			$r[0] = rand(0,99);
			$r[1] = rand(0,99);
			$r[2] = rand(0,99);
			
			$out = false;
			if ($r[0]<$p*100) {
				$g[$i][$i+$n] = 1;
				$out = true;
			}
			
			if ($r[1]<$p*100  && ($i)%$n!=0) {
				$g[$i][$i+$n+1] = 1;
				$out = true;
			}
			
			if ($r[2]<$p*100 && ($i-1)%$n!=0) {
				$g[$i][$i+$n-1] = 1;
				$out = true;
			}
			//force one outgoing
			if (!$out) {
				$d = rand(0,2);
				if ($d<2) {
					$g[$i][$i+$n] = 1;
				} else {
					if ($i%$n==0) {
						$g[$i][$i+$n-1] = 1;
					} else {
						$g[$i][$i+$n+1] = 1;
					}
				}
			}
		}
		//force one incoming
		$connected = false;
		if ($i<=$n) {
			$connected = true;
		} else if ($i<=$n || $g[$i-1][$i]==1 || $g[$i-$n][$i]==1 || $g[$i-$n-1][$i]==1 ||  $g[$i-$n+1][$i]==1) {
			$connected = true;
		}
		if (!$connected) {
			$g[$i-$n][$i] = 1;
		}
		
	}
	
	if (isset($op['labels'])) {
		if ($op['labels']=="letters") {
			$op['labels'] = array_slice($lettersarray,0,$tot-2);
		} 
		array_unshift($op['labels'],"Start");
		array_push($op['labels'],"End");
		if ($useweights) {
			for ($i=1; $i<$tot-1; $i++) {
				$op['labels'][$i] .= ' ('.$op['weights'][$i-1].')';
			}
		}
	}
	
	return graphdrawit($pos,$g,$op);
}


//graphemptygraph(n)
//creates an empty graph matrix, nxn
function graphemptygraph($n) {
	$g = array();
	for ($i = 0; $i<$n; $i++) {
		$g[$i] = array_fill(0,$n,0);
	}
	return $g;
}


//graphdijkstra(g) 
//computes dijkstras algorithm on the graph g
//g is a 2-dimensional matrix
//g[i][j] = 1 if vertexes i and j are connected
//the last vertex will be used as the destination vertex
//returns array(dist,next) where
//dist[i] is the shortest dist to end, and
//next[i] is the vertex next closest to the end
function graphdijkstra($g) {
	$n = count($g[0]);
	$dist = array();
	$next = array();
	$eaten = array();
	$inf = 1e16;
	for ($i=0; $i<$n; $i++) {
		$dist[$i] = $inf;
	}
	$dist[$n-1] = 0;
	while (count($eaten)<$n) {
		$cur = -1;
		//find starting vertex, if any
		for ($i=0; $i<$n; $i++) {	
			if (!isset($eaten[$i])) {
				$cur = $i;
				break;
			}
		}
		if ($cur==-1) {break;}
		//find vertex w/ smallest dist
		for ($i=0; $i<$n; $i++) {
			if (!isset($eaten[$i]) && $dist[$i]<$dist[$cur]) {
				$cur = $i;
			}
		}
		if ($dist[$cur]==$inf) {
			break;  //can't access remaining verticies
		}
		$eaten[$cur] = 1; //remove vertex
		for ($i=0; $i<$n; $i++) {
			if (!isset($eaten[$i]) && $g[$i][$cur]>0) { //vertices leading to $cur
				$alt = $dist[$cur] + $g[$i][$cur];
				if ($alt<$dist[$i]) {
					$dist[$i] = $alt;
					$next[$i] = $cur;
				}
			}
		}
	}
	return array($dist,$next);
}

//graphbackflow(g) 
//computes longest-path algorithm on the graph g
//g is a 2-dimensional matrix
//g[i][j] = 1 if vertexes i leads to j
//This might give bad/weird results if graph has a circuit
//the last vertex will be used as the destination vertex
//returns array(dist,next) where
//dist[i] is the longest dist to end, and
//next[i] is the vertex next closest to the end
function graphbackflow($g) {
	$n = count($g[0]);
	$dist = array();
	$next = array();
	$eaten = array();
	$inf = 1e16;
	for ($i=0; $i<$n; $i++) {
		$dist[$i] = -1;
	}
	$dist[$n-1] = 0;
	$toprocess = array($n-1);
	while (count($eaten)<$n) {
		if (count($toprocess)==0) { break;}
		for ($k=0; $k<count($toprocess); $k++) {
			$cur = $toprocess[$k];
			$newtoprocess = array();
			for ($i=0; $i<$n; $i++) {
				if (!isset($eaten[$i]) && $g[$i][$cur]>0) { //vertices leading to $cur
					$alt = $dist[$cur] + $g[$i][$cur];
					if ($alt>$dist[$i]) {
						$dist[$i] = $alt;
						$next[$i] = $cur;
						if (!in_array($i,$newtoprocess)) {
							$douse = true;
							//don't use if not terminal 
							for ($j=0; $j<$n; $j++) {
								if ($g[$i][$j]>0 && !isset($eaten[$j]) && $j!=$cur) {
									$douse = false; break;
								}
							}
							if ($douse) {
								$newtoprocess[] = $i;
							}
						}
					}
				}
			}
			$eaten[$cur] = 1;
		}
		$toprocess = $newtoprocess;
	}
	return array($dist,$next);
}


//graphkruskal(g) 
//return a minimum cost spanning tree from matrix g
function graphkruskal($g) {
	$n = count($g[0]);
	$edgelist = array();
	$addededges = array();
	$clusters = array();
	$c = 0;
	for ($i=0; $i<$n; $i++) {
		$clusters[$i] = $i;
	}
	for ($i=0; $i<$n; $i++) {
		for ($j=$i+1;$j<$n;$j++) {
			if ($g[$i][$j]>0 || $g[$j][$i]>0) {
				$edgelist[$c] = max($g[$i][$j],$g[$j][$i]);
				$edges[$c] = array($i,$j);
				$c++;
			}
		}
	}
	asort($edgelist);
	$keys = array_keys($edgelist);
	$steps = 0;
	while (count($addededges)<$n-1) {
		$steps++;
		$c = array_shift($keys);
		if ($clusters[$edges[$c][0]] != $clusters[$edges[$c][1]]) {
			$addededges[] = $c;
			//merge clusters
			for ($i=0; $i<$n; $i++) {
				if ($i==$edges[$c][1]) { continue;}
				if ($clusters[$i] == $clusters[$edges[$c][1]]) {
					$clusters[$i] = $clusters[$edges[$c][0]];
				}
			}
			$clusters[$edges[$c][1]] = $clusters[$edges[$c][0]];
			
		}
	}
	$g = graphemptygraph($n);
	foreach ($addededges as $c) {
		$g[$edges[$c][0]][$edges[$c][1]] = $edgelist[$c];	
	}
	return $g;
}

//graphadjacencytoincidence(g,[options])
//create incidence lists from adjacency matrix g
//g[i][j]>0 if edge from i to j
//outputs list where list[i] is array of vertices
//  that i leads to
function graphadjacencytoincidence($g,$op) {
	$n = count($g[0]);
	$list = array();
	for ($i=0; $i<$n; $i++) {
		$list[$i] = array();
	}
	for ($i=0; $i<$n; $i++) {
		for ($j=$i+1;$j<$n;$j++) {
			if ($op['digraph']) {
				if($g[$i][$j]>0) {
					$list[$i][] = $j;
				}
				if($g[$j][$i]>0) {
					$list[$j][] = $i;
				}
			} else {
				if ($g[$i][$j]>0 || $g[$j][$i]>0) {
					$list[$i][] = $j;
					$list[$j][] = $i;
				}
			}
		}
	}
	return $list;
}

//graphincidencetoadjacency(list,[options])
//create adjacency matrix g from incidence list
//list is where list[i] is array of vertices
//  that i leads to
//outputs matrix g[i][j]=1 if edge from i to j
function graphincidencetoadjacency($list,$op) {
	$n = count($list);
	$g = graphemptygraph($n);
	for ($i=0; $i<$n; $i++) {
		foreach ($list[$i] as $j) {
			$g[$i][$j] = 1;
		}
	}
	return $g;
}

//internal function, not to be used directly
function graphprocessoptions($g,$op) {
	$n = count($g[0]);
	if (!isset($op['connected'])) {
		if ($op['tree']) {
			$op['connected'] = true;
		} else {
			$op['connected'] = false;
		}
	}
	if (isset($op['randweights'])) {
		if (is_array($op['randweights'])) {
			$rmin = $op['randweights'][0];
			$rmax = $op['randweights'][1];
		} else {
			$rmin = 1;
			$rmax = $op['randweights'];
		}
		for ($i=0; $i<$n; $i++) {
			for ($j=$i+1; $j<$n; $j++) {
				if ($op['digraph']) {
					if ($g[$i][$j]>0) {
						$g[$i][$j] = rand($rmin,$rmax);
					} 
					if ($g[$j][$i]>0) {
						$g[$j][$i] = rand($rmin,$rmax);
					}
				} else {
					if ($g[$i][$j]>0 || $g[$j][$i]>0) {
						$g[$i][$j] = rand($rmin,$rmax);
					}
				}
			}
		}
	}
	if (isset($op['randedges'])) {
		$origg = $g;
	}
	if ($op['tree'] || (isset($op['randedges']) && $op['connected'])) {
		$g = graphkruskal($g);
	} else if (isset($op['randedges']) && !$op['connected']) {
		$g = graphemptygraph($n);
	}
	if (isset($op['randedges'])) {
		$rnd = $op['randedges'];
		for ($i=0; $i<$n; $i++) {
			for ($j=$i+1; $j<$n; $j++) {
				$p = rand(0,99);
				if ($p < $rnd*100) {
					if ($origg[$i][$j]>0) {
						$g[$i][$j] = $origg[$i][$j];
					}
					if ($origg[$j][$i]>0) {
						$g[$j][$i] = $origg[$j][$i];
					}
				}		
			}
		}
	}
	if ($op['tree'] && !$op['connected']) {
		$list = graphadjacencytoincidence($g,$op);
		for ($i=0; $i<count($list); $i++) {
			if (count($list[$i])>1) {
				for ($j=0; $j<count($list[$i]); $j++) {
					if (count($list[$list[$i][$j]])>1) {
						$g[$list[$i][$j]][$i] = 0;
						$g[$i][$list[$i][$j]] = 0;
						break 2;
					}
				}
			}
		}
	}
	return $g;	
}
//internal function, not to be used directly
function graphdrawit($pos,$g,$op) {
	if (!isset($op['width'])) {$op['width'] = 360;}
	if (!isset($op['height'])) {$op['height'] = 300;}
	$n = count($pos);
	if (!isset($op['xmin'])) {
		$pxmin = 10000; $pxmax = -10000; $pymin = 10000; $pymax = -10000;
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
	}
	
	$lettersarray = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
	$com = "setBorder(60,30,60,30);initPicture({$op['xmin']},{$op['xmax']},{$op['ymin']},{$op['ymax']});";
	$cx = ($op['xmin'] + $op['xmax'])/2;
	$cy = ($op['ymin'] + $op['ymax'])/2;
	
	
	for ($i=0; $i<$n; $i++) {
		$com .= "dot([".$pos[$i][0].",".$pos[$i][1]."]);";
		if (isset($op['labels'])) {
			if (isset($op['labelposition'])) {
				$ps = $op['labelposition'];
			} else {
				if ($pos[$i][1]>$cy) { $ps = "above"; } else {$ps = "below";}
				if ($pos[$i][0]>$cx) { $ps .= "right"; } else {$ps .= "left";}
			}
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

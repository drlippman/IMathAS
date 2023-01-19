<?php
//A library of graph theory and scheduling functions.  Version 1.1, April 3, 2012
//
//Most graphing functions in this library use an options array.  Here are the
//common options - specific functions will mention other options.
//  options['width'] = width of output, in pixels.  Defaults to 300.
//  options['height'] = height of output, in pixels.  Defaults to 300.
//  options['digraph'] = true/false.  If true, g[i][j] > 0 means i leads to j
//  options['useweights'] = true/false.  If true, g[i][j] used as weight
//  options['labels'] = "letters" or array of labels.  If "letters", letters
//    A-Z used for labels.  If array, label[i] used for vertex g[i]
//  options['weightoffset'] = position (0-1) along edge where weights should go
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
array_push($allowedmacros,"graphspringlayout","graphcirclelayout","graphgridlayout","graphpathlayout","graphcircleladder","graphcircle","graphbipartite","graphgrid","graphrandom","graphrandomgridschedule","graphemptygraph","graphdijkstra","graphbackflow","graphkruskal","graphadjacencytoincidence","graphincidencetoadjacency","graphdrawit","graphdecreasingtimelist","graphcriticaltimelist","graphcircledstar","graphcircledstarlayout","graphmaketable","graphsortededges","graphcircuittoarray","graphcircuittostringans","graphnearestneighbor","graphrepeatednearestneighbor","graphgetedges","graphgettotalcost","graphnestedpolygons","graphmakesymmetric","graphisconnected","graphgetedgesarray","graphsequenceeuleredgedups","graphsequenceishamiltonian","graphshortestpath","graphgetpathlength","graphcomparecircuits","graphlistprocessing","graphscheduletaskinfo","graphschedulecompletion","graphscheduleidle","graphdrawschedule","graphschedulelayout","graphscheduleproctasks","graphschedulemultchoice","graphprereqtable","graphgetcriticalpath");

///graphcircleladder(n,m,[options])
//draws a circular ladder graph
//n vertices around a circle
//m concentric circles
//connected around circle and between circles
//returns array(pic,g)
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

//graphnestedpolygons(n,m,[options])
//draws a graph of offset nested polygons
//n vertices around a polygon
//m concentric polygons
//vertices of inner polygon touches midpoint of outer polygon
//returns array(pic,g)
function graphnestedpolygons($n,$m,$op=array()) {
	$tot = $n*$m;
	$dtheta = M_PI/$n;
	$g = graphemptygraph($tot);
	$r = 1;
	$v = 0;
	//TODO:  MAKE WORK
	for ($i = 0; $i<$m; $i++) { //inner to outer
		$r = $r/cos($dtheta);
		for ($j = 0; $j<$n; $j++) {
			$pos[$v][0] = $r*cos($dtheta*($j*2+$i%2));
			$pos[$v][1] = $r*sin($dtheta*($j*2+$i%2));
			if ($i>0) { //in a points to previous
				if ($j<$n-1) {
					if ($i%2==1) {
						$g[$v-$n][$v] = 1;
						$g[$v-$n+1][$v] = 1;
					} else {
						$g[$v-$n][$v] = 1;
						if ($j==0) {
							$g[$v-1][$v] = 1;
						} else {
							$g[$v-$n-1][$v] = 1;
						}
					}

				} else {
					$g[$v-$n][$v] = 1;
					if ($i%2==1) {
						$g[$v-2*$n+1][$v] = 1;
					} else {
						$g[$v-$n-1][$v] = 1;
					}
				}
			} else {
				if ($j<$n-1) {
					$g[$v][$v+1] = 1;  //around circle
				} else {
					$g[$v-$n+1][$v] = 1;  //around circle
				}
			}
			$v++;

		}
	}
	//print_r($g);
	$op['xmin'] = -$r;
	$op['xmax'] = $r;
	$op['ymin'] = -$r;
	$op['ymax'] = $r;
	$g = graphprocessoptions($g,$op);
	return array(graphdrawit($pos,$g,$op),$g);
}


//graphcircle(n,[options])
//draws a complete graph with a circular layout
//with n vertices
//returns array(pic,g)
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

//graphcircledstar(n,[options])
//draws n vertices in a circle, plus 1 vertex in the middle
//vertices are connected along the circle, plus all
//connected to the center vertex
//returns array(pic,g)
function graphcircledstar($n,$op=array()) {
	$g = graphemptygraph($n+1);
	for ($i = 1; $i<=$n; $i++) {
		$g[0][$i] = 1;
		if ($i==1) {
			$g[1][$n] = 1;
		} else {
			$g[$i-1][$i] = 1;
		}
	}
	$g = graphprocessoptions($g,$op);
	return array(graphcircledstarlayout($g,$op),$g);
}

//graphbipartite(n,[options])
//draws a complete bipartite graph (every vertex on left is
//connected to every vertex on the right)
//with n vertices in the first column, m in the second
//returns array(pic,g)
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
//returns array(pic,g)
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


//graphrandom(n,[options])
//draws a randomly spring laid out graph with n vertices.
function graphrandom($n,$op=array()) {
	$g = graphemptygraph($n);
	for ($i = 0; $i<$n; $i++) {
		for ($j=$i+1;$j<$n; $j++) {
			$g[$i][$j] = 1;
		}
	}
	$g = graphprocessoptions($g,$op);

	return array(graphspringlayout($g,$op),$g);
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

//graphdijkstra(g,[dest])
//computes dijkstras algorithm on the graph g
//g is a 2-dimensional matrix
//g[i][j]  &gt; 0 if vertexes i and j are connected
//if dest vertex not set then
//the last vertex will be used as the destination vertex
//returns array(dist,next) where
//dist[i] is the shortest dist to end, and
//next[i] is the vertex next closest to the end
function graphdijkstra($g,$dest=-1) {
	$n = count($g[0]);
	$dist = array();
	$next = array();
	$eaten = array();
	$inf = 1e16;
	for ($i=0; $i<$n; $i++) {
		$dist[$i] = $inf;
	}
	if ($dest==-1) {
		$dist[$n-1] = 0;
	} else {
		$dist[$dest] = 0;
	}
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
			if (!isset($eaten[$i]) && (!empty($op['digraph']) || $i<$cur)?(isset($g[$i][$cur]) && $g[$i][$cur]>0):(isset($g[$cur][$i]) && $g[$cur][$i]>0)) { //vertices leading to $cur
				$alt = $dist[$cur] + ((!empty($op['digraph']) || $i<$cur)?$g[$i][$cur]:$g[$cur][$i]);
				if ($alt<$dist[$i]) {
					$dist[$i] = $alt;
					$next[$i] = $cur;
				}
			}
		}
	}
	return array($dist,$next);
}


//graphkruskal(g)
//return a minimum cost spanning tree graph from graph g
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
			if ((isset($g[$i][$j]) && $g[$i][$j]>0) || (isset($g[$j][$i]) && $g[$j][$i]>0)) {
				$edgelist[$c] = max($g[$i][$j] ?? -1, $g[$j][$i] ?? -1);
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
		if (count($keys) == 0) {break;}
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

//graphrepeatednearestneighbor(g, [multi])
//returns a hamiltonian circuit graph using repeated nearest neighbor
//set multi to true to return an array of graphs if found
function graphrepeatednearestneighbor($g, $multi=false) {
	$n = count($g[0]);
	$minval = 1e16;
	for ($i=0; $i<$n; $i++) {
		list($ng,$v) = graphnearestneighbor($g,$i,true);
		if ($v<$minval) {
			$minat = array($i);
			$minval = $v;
			if ($multi) {
				$curming = array($ng);
			} else {
				$curming = $ng;
			}
		} else if ($v==$minval) {
			$minat[] = $i;
			if ($multi) {
				$curming[] = $ng;
			}
		}
	}
	return array($curming,$minat);
}
//graphnearestneighbor(g,start)
//returns a hamiltonian circuit graph using nearest neighbor
//starting at vertex start
function graphnearestneighbor($g,$start,$returnweight=false) {
	$n = count($g[0]);
	$visited = array();
	$cur = $start;
	$ng = graphemptygraph($n);
	$totalweight = 0;
	while (count($visited)<$n-1) {
		$visited[$cur] = 1;
		$minat = -1;
		$minval = 1e16;
		for ($i=0; $i<$n; $i++) {
			if (isset($visited[$i])) {continue;}
			$m = max($g[$cur][$i],$g[$i][$cur]);
			if ($m>0 && $m<$minval) {
				$minat = $i;
				$minval = $m;
			}
		}
		if ($minat==-1) { break;}
		$ng[$cur][$minat] = $minval;
		$ng[$minat][$cur] = $minval;
		$totalweight += $minval;
		$cur = $minat;
	}
	$ng[$cur][$start] = max($g[$cur][$start],$g[$start][$cur]);
	$ng[$start][$cur] = $ng[$cur][$start];
	$totalweight += $ng[$cur][$start];

	if ($returnweight) {
		return array($ng,$totalweight);
	} else {
		return $ng;
	}
}

//graphsortededges(g)
//returns a hamiltonian circuit graph using sorted edges
function graphsortededges($g) {
	$n = count($g[0]);
	$edgelist = array();
	$addededges = array();
	$clusters = array();
	$valence = array();
	$c = 0;
	for ($i=0; $i<$n; $i++) {
		$clusters[$i] = $i;
	}
	for ($i=0; $i<$n; $i++) {
		for ($j=$i+1;$j<$n;$j++) {
            if ((isset($g[$i][$j]) && $g[$i][$j]>0) || (isset($g[$j][$i]) && $g[$j][$i]>0)) {
				$edgelist[$c] = max($g[$i][$j] ?? -1, $g[$j][$i] ?? -1);
				$edges[$c] = array($i,$j);
				$c++;
			}
		}
		$valence[$i] = 0;
	}
	asort($edgelist);
	$keys = array_keys($edgelist);
	$steps = 0;
	while (count($addededges)<$n) {
		$steps++;
		if (count($keys) == 0) {break;}
		$c = array_shift($keys);
		if ($valence[$edges[$c][0]]<2 && $valence[$edges[$c][1]]<2 && ($clusters[$edges[$c][0]] != $clusters[$edges[$c][1]] || count($addededges)==$n-1)) {
			$addededges[] = $c;
			//merge clusters
			for ($i=0; $i<$n; $i++) {
				if ($i==$edges[$c][1]) { continue;}
				if ($clusters[$i] == $clusters[$edges[$c][1]]) {
					$clusters[$i] = $clusters[$edges[$c][0]];
				}
			}
			$clusters[$edges[$c][1]] = $clusters[$edges[$c][0]];
			$valence[$edges[$c][1]]++;
			$valence[$edges[$c][0]]++;
		}
	}
	$g = graphemptygraph($n);
	foreach ($addededges as $c) {
		$g[$edges[$c][0]][$edges[$c][1]] = $edgelist[$c];
	}
	return $g;
}

//function graphsequenceeuleredgedups(g,op,seq)
//determines if given sequence of labels determines
//an edge-covering circuit on graph g.  options op is needed
//for labels
//returns -1 if not all edges covered or seq uses nonexisting edges
//returns 0 if all edges covered with no dup
//returns # of dups if covers all edges with duplications
function graphsequenceeuleredgedups($g,$op,$seq) {
	$n = count($g[0]);
    $seq = trim($seq);
	if (isset($op['labels']) && $op['labels'] != 'letters') {
		$lbl = $op['labels'];
	} else {
		$lbl = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
        $seq = strtoupper($seq); // account for lowercase sequence
	}
	$lblrev = array_flip($lbl);
	$len = strlen($seq);
    if ($len < 2) {
        return -1; //not long enough
    }
	$vseq = array();
	for ($i=0; $i<$len; $i++) {
        if (!isset($lblrev[$seq[$i]])) { // invalid entry
            return -1;
        }
		$vseq[$i] = $lblrev[$seq[$i]];
	}
	if ($vseq[0] != $vseq[$len-1]) {
		return -1; //doesn't return to start
	}
	$dup = 0;
	for ($i=1; $i<$len; $i++) {
        if ((isset($g[$vseq[$i]][$vseq[$i-1]]) && $g[$vseq[$i]][$vseq[$i-1]]>0) || (isset($g[$vseq[$i-1]][$vseq[$i]]) && $g[$vseq[$i-1]][$vseq[$i]]>0)) {
			//edge exists
			$g[$vseq[$i]][$vseq[$i-1]] = -1;
			$g[$vseq[$i-1]][$vseq[$i]] = -1;
		} else if (isset($g[$vseq[$i]][$vseq[$i-1]]) && $g[$vseq[$i]][$vseq[$i-1]]<0) {
			//used this edge before.  naughty naughty
			$g[$vseq[$i]][$vseq[$i-1]]--;
			$g[$vseq[$i-1]][$vseq[$i]]--;
			$dup++;
		} else {
			//no edge exists
			return -1;
		}
	}
	for ($i=0; $i<$n; $i++) {
		for ($j=$i+1; $j<$n; $j++) {
            if ((isset($g[$i][$j]) && $g[$i][$j]>0) || (isset($g[$j][$i]) && $g[$j][$i]>0)) {
				//unused edge
				return -1;
			}
		}
	}
	return $dup;
}

//function graphsequencishamiltonian(g,op,seq)
//determines if given sequence of labels determines
//a hamiltonian circuit on graph g.  options op is needed
//for labels
function graphsequenceishamiltonian($g,$op,$seq) {
	$n = count($g[0]);
	if ($op['labels'] != 'letters') {
		$lbl = $op['labels'];
	} else {
		$lbl = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
		$seq = strtoupper($seq);
	}
	$lblrev = array_flip($lbl);
	$len = strlen($seq);
    if ($len != $n+1) {
		return false; //doesn't return to start or not long enough
	}
	$vseq = array();
	for ($i=0; $i<$len; $i++) {
        if (!isset($lblrev[$seq[$i]])) { // invalid entry
            return false;
        }
		$vseq[$i] = $lblrev[$seq[$i]];
	}
	if ($vseq[0] != $vseq[$len-1]) {
		return false;
	}
	
	$notvis = array_fill(0,$n,1);
	for ($i=1; $i<$len; $i++) {
        if ((isset($g[$vseq[$i]][$vseq[$i-1]]) && $g[$vseq[$i]][$vseq[$i-1]]>0) || (isset($g[$vseq[$i-1]][$vseq[$i]]) && $g[$vseq[$i-1]][$vseq[$i]]>0)) {
			//edge exists
			if ($notvis[$vseq[$i]]==1) {
				$notvis[$vseq[$i]] = 0;
			} else {
				//second visit to vertex;

				return false;
			}
		}
	}
	if (array_sum($notvis)>0) {
		//some vertex not visisted.  Should have already been caught
		return false;
	}
	return true;
}

//graphgetpathlength(g,op,seq)
//Given sequence seq of graph labels, determine the
//length of the path on graph g
//options needed for vertex labels
function graphgetpathlength($g,$op,$seq) {
	$n = count($g[0]);
	if ($op['labels'] != 'letters') {
		$lbl = $op['labels'];
	} else {
		$lbl = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
		$seq = strtoupper($seq);
	}
	$lblrev = array_flip($lbl);
	$len = strlen($seq);
    if ($len == 0) { return 0; }
	$pathlen = 0;
    if (!isset($lblrev[$seq[0]])) { // invalid entry
        return -1;
    }
    $last = $lblrev[$seq[0]];
	for ($i=1; $i<$len; $i++) {
        if (!isset($lblrev[$seq[$i]])) { // invalid entry
            return -1;
        }
		$cur = $lblrev[$seq[$i]];
        if (!isset($g[$last][$cur]) && !isset($g[$cur][$last])) { return -1; }
		$pathlen += max($g[$last][$cur],$g[$cur][$last]);
		$last = $cur;
	}
	return $pathlen;
}

//graphshortestpath(g,op,start,end,[type])
//find shortest path on graph g from vertex start to end
//returns array(shortest path,length of path)
//for path,
//type=0 returns labeledpath, like ABCD
//type=1 returns array of vertex indices
function graphshortestpath($g,$op,$start,$end,$type=0) {
	if ($op['labels'] != 'letters') {
		$lbl = $op['labels'];
	} else {
		$lbl = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
	}
	list($dist,$next) = graphdijkstra($g,$end);
	$vertarr = array($start);
	$cur = $start;
	if ($dist[$start]==1e16) {
		//no path exists
		return array("",1e16);
	}
	while ($cur != $end) {
		$cur = $next[$cur];
		$vertarr[] = $cur;
	}
	if ($type==1) {
		return array($vertarr,$dist[$start]);
	} else {
		$path = '';
		foreach ($vertarr as $vert) {
			$path .= $lbl[$vert];
		}
		return array($path,$dist[$start]);
	}
}

//graphcircuittostringans(g, [labels, start])
//converts graph or array of graphs containing a circuit to
//a string of labels that can be used as an $answer
function graphcircuittostringans($gs, $lbl='', $start=0) {
	if ($lbl=='') {
		$lbl = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
	}
	if (!is_array($gs[0][0])) {//is not array of graphs
		$gs = array($gs);
	}
	$out = array();
	foreach ($gs as $g) {
		$n = count($g[0]);
        if (!isset($lbl[$start])) {
            echo "insufficient labels for all vertices";
            continue;
        }
		$order = array($lbl[$start]);
		$cur = $start;
		$last = -1;
		while (count($order)<$n) {
			for ($i=0;$i<$n;$i++) {
				if (((isset($g[$cur][$i]) && $g[$cur][$i]>0) || (isset($g[$i][$cur]) && $g[$i][$cur]>0)) && $i!=$last) {
                    if (!isset($lbl[$i])) {
                        echo "insufficient labels for all vertices";
                        continue;
                    }
					$order[] = $lbl[$i];
					$last = $cur;
					$cur = $i;
					break;
				}
			}
			if ($i==$n) {
				break;
			}
		}
		$order[] = $lbl[$start];
		$str = implode("",$order);
		if (!in_array($str, $out)) {
			$out[] = $str;
			$out[] = strrev($str);
		}
	}
	return implode(' or ', $out);
}

//graphcircuittoarray(g,[start])
//converts graph containing a circuit to an array
//of vertices in circuit order
function graphcircuittoarray($g,$start=0) {
	$n = count($g[0]);
	$order = array($start);
	$cur = $start;
	$last = -1;
	while (count($order)<$n) {
		for ($i=0;$i<$n;$i++) {
			if (((isset($g[$cur][$i]) && $g[$cur][$i]>0) || (isset($g[$i][$cur]) && $g[$i][$cur]>0)) && $i!=$last) {
				$order[] = $i;
				$last = $cur;
				$cur = $i;
				break;
			}
		}
		if ($i==$n) {
			break;
		}
	}
	return $order;
}

//graphgetedges(g,op)
//gets list of edges in and not in graph
//need op['labels'] set
//return (goodedges,badedges)
function graphgetedges($g,$op) {
	if ($op['labels'] != 'letters') {
		$lbl = $op['labels'];
	} else {
		$lbl = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
	}
	$n = count($g[0]);
	$good = array();
	$bad = array();
	for ($i=0; $i<$n; $i++) {
		for ($j=$i+1;$j<$n;$j++) {
			if (!empty($op['digraph'])) {
				if(isset($g[$i][$j]) && $g[$i][$j]>0) {
					$good[] = $lbl[$i] . $lbl[$j];
				} else {
					$bad[] = $lbl[$i] . $lbl[$j];
				}
				if(isset($g[$j][$i]) && $g[$j][$i]>0) {
					$good[] = $lbl[$j] . $lbl[$i];
				} else {
					$bad[] = $lbl[$j] . $lbl[$i];
				}
			} else {
                if ((isset($g[$i][$j]) && $g[$i][$j]>0) || (isset($g[$j][$i]) && $g[$j][$i]>0)) {
					$good[] = $lbl[$i] . $lbl[$j];
				} else {
					$bad[] = $lbl[$i] . $lbl[$j];
				}
			}
		}
	}
	return array($good,$bad);
}

//graphgetedgesarray(g)
//gets array of edges in a graph
//returns array of edges; each edge is array(startvert,endvert)
function graphgetedgesarray($g) {
	$n = count($g[0]);
	$good = array();
	for ($i=0; $i<$n; $i++) {
		for ($j=$i+1;$j<$n;$j++) {
			if (!empty($op['digraph'])) {
				if(isset($g[$i][$j]) && $g[$i][$j]>0) {
					$good[] = array($i,$j);
				}
				if(isset($g[$j][$i]) && $g[$j][$i]>0) {
					$good[] = array($j,$i);
				}
			} else {
				if ((isset($g[$i][$j]) && $g[$i][$j]>0) || (isset($g[$j][$i]) && $g[$j][$i]>0)) {
					$good[] = array($i,$j);
				}
			}
		}
	}
	return $good;
}

//graphgettotalcost(g)
//gets total cost of all edges in a graph
function graphgettotalcost($g) {
	$n = count($g[0]);
	$totalcost = 0;
	for ($i=0; $i<$n; $i++) {
		for ($j=$i+1;$j<$n;$j++) {
			$totalcost += max($g[$i][$j],$g[$j][$i]);
		}
	}
	return $totalcost;
}

//graphadjacencytoprereqs(g,[options])
//create incidence lists from adjacency matrix g
//g[i][j] &gt; 0 if edge from i to j
//outputs list where list[i] is array of vertices
//  that lead to i.  Only intended for digraphs.
function graphadjacencytoprereqs($g,$op) {
	$n = count($g[0]);
	$list = array();
	for ($i=0; $i<$n; $i++) {
		$list[$i] = array();
	}
	for ($i=0; $i<$n; $i++) {
		for ($j=$i+1;$j<$n;$j++) {
			if(isset($g[$i][$j]) && $g[$i][$j]>0) {
				$list[$j][] = $i;
			}
			if(isset($g[$j][$i]) && $g[$j][$i]>0) {
				$list[$i][] = $j;
			}
		}
	}
	return $list;
}

//graphprereqtable(g,w,[op])
//creates an HTML table showing the tasks in g, the task times in w, and
//the tasks that must be completed first.
//use $op['labels'] to provide array of labels, or ='letters' to use letters
function graphprereqtable($g,$w,$op=array()) {
	$prereq = graphadjacencytoprereqs($g,$op);
	if ($op['labels'] != 'letters') {
		$lbl = $op['labels'];
	} else {
		$lbl = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
	}
	$out = '<table class="stats"><thead><tr><th>Task</th><th>Time Required</th><th>Tasks that must be completed first</th></tr></thead><tbody>';
	for ($i=0;$i<count($prereq)-1;$i++) {
		$out .= '<tr><td class="c">'.$lbl[$i].'</td><td class="c">'.$w[$i].'</td><td class="l">';
		foreach ($prereq[$i] as $k=>$p) {
			$prereq[$i][$k] = $lbl[$p];
		}
		$out .= implode(', ',$prereq[$i]);
		$out .= '</td></tr>';
	}
	$out .= '</tbody></table>';
	return $out;

}

//graphadjacencytoincidence(g,[options])
//create incidence lists from adjacency matrix g
//g[i][j] &gt; 0 if edge from i to j
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
			if (!empty($op['digraph'])) {
				if(isset($g[$i][$j]) && $g[$i][$j]>0) {
					$list[$i][] = $j;
				}
				if(isset($g[$j][$i]) && $g[$j][$i]>0) {
					$list[$j][] = $i;
				}
			} else {
				if ((isset($g[$i][$j]) && $g[$i][$j]>0) || (isset($g[$j][$i]) && $g[$j][$i]>0)) {
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
function graphincidencetoadjacency($list,$op=array()) {
	$n = count($list);
	$g = graphemptygraph($n);
	for ($i=0; $i<$n; $i++) {
		foreach ($list[$i] as $j) {
			$g[$i][$j] = 1;
		}
	}
	return $g;
}

//graphgetvalence(g,vert,[dir])
//gets valence(degree) of vertex vert
//if digraph, can use dir:
//   0: indegree, 1: outdegree, 2: both (default)
function graphgetvalence($g,$vert,$dir=2) {
	$n = count($g[0]);
	$cnt = 0;
	for ($i=0; $i<$n; $i++) {
		if ($dir==2 && ((isset($g[$i][$vert]) && $g[$i][$vert]>0) || (isset($g[$vert][$i]) && $g[$vert][$i]>0))) {
			$cnt++;
		} else if ($dir==0 && (isset($g[$i][$vert]) && $g[$i][$vert]>0)) {
			$cnt++;
		} else if ($dir==1 && (isset($g[$vert][$i]) && $g[$vert][$i]>0)) {
			$cnt++;
		}
	}
	return $cnt;
}

//graphmakesymmetric(g)
//ensures that all edges are bidirectional.
function graphmakesymmetric($g) {
	$n = count($g[0]);
	for ($i=0; $i<$n; $i++) {
		for ($j=$i+1; $j<$n; $j++) {
			$m = max($g[$i][$j] ?? 0,$g[$j][$i] ?? 0);
			if ($m>0) {
				$g[$i][$j] = $m;
				$g[$j][$i] = $m;
			}
		}
	}
	return $g;
}

//graphisconnected
//checks if graph is connected
function graphisconnected($g) {
	$n = count($g[0]);
	$ng = graphmakesymmetric($g);
	$inf = 1e16;
	list($dist,$next) = graphdijkstra($ng);
	for ($i=0; $i<$n; $i++) {
		if ($dist[$i]==$inf) {
			return false;
		}
	}
	return true;
}

//graphmaketable(g,[op])
//makes a weights table based on a given graph
function graphmaketable($g,$op=array()) {
	$n = count($g[0]);
	$lettersarray = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
	if (!isset($op['labels']) || !is_array($op['labels'])) {
		$op['labels'] = array_slice($lettersarray,0,$n);
	}
	$table = '<table class="stats"><thead>';
	$table .= '<tr><th></th>';
	for ($i=0; $i<$n; $i++) {
		$table .= '<th>'.$op['labels'][$i].'</th>';
	}
	$table .= '</tr></thead><tbody>';
	for ($i=0; $i<$n; $i++) {
		$table .= '<tr>';
		$table .= '<th>'.$op['labels'][$i].'</th>';
		for ($j=0; $j<$n; $j++) {
			if ($g[$i][$j]==0 && $g[$j][$i]==0) {
				$table .= '<td>--</td>';
			} else {
				$table .= '<td>'.max($g[$i][$j],$g[$j][$i]).'</td>';
			}
		}
		$table .= '</tr>';
	}
	$table .= '</tbody>';
	$table .= '</table>';
	return $table;
}

//graphspringlayout(g,[options])
//draws a graph based on a graph incidence matrix
//using a randomized spring layout engine.  Doesn't work great.
//g is a 2-dimensional upper triangular matrix
//g[i][j] &gt; 0 if vertices i and j are connected
//not a digraph
function graphspringlayout($g,$op=array()) {
	global $RND;
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
			$pos[$i][$x] = $RND->rand(0,32000)/32000;
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
                if ((isset($g[$i][$j]) && $g[$i][$j]>0) || (isset($g[$j][$i]) && $g[$j][$i]>0)) {
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
//g[i][j] &gt; 0 if vertexes i and j are connected
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

//graphcircledstarlayout(graph,[options])
//draws a graph based on a graph incidence matrix
//using a circular layout with the first vertex in the center of the circle
//g is a 2-dimensional upper triangular matrix
//g[i][j] &gt; 0 if vertexes i and j are connected
function graphcircledstarlayout($g,$op=array()) {
	$n = count($g[0])-1;
	$dtheta = 2*M_PI/$n;
	$pos[0][0] = 0;
	$pos[0][1] = 0;
	for ($i = 0; $i<$n; $i++) {
		$pos[$i+1][0] = 10*cos($dtheta*$i);
		$pos[$i+1][1] = 10*sin($dtheta*$i);
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
//g[i][j] &gt; 0 if vertexes i and j are connected
function graphgridlayout($g,$op=array()) {
	$n = count($g[0]);
	if (isset($op['gridv'])) {
		$sn = $op['gridv'];
	} else {
		$sn = ceil(sqrt($n));
	}
	$gd = 10/$sn;
	for ($i=0; $i<$n; $i++) {
		$pos[$i][0] = floor($i/$sn)*$gd  + (!empty($op['wiggle'])?$gd/5*sin(3*$i):0);
		$pos[$i][1] = ($i%$sn)*$gd + (!empty($op['wiggle'])?$gd/5*sin(4*$i):0);
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
//g[i][j] &gt; 0 if vertexes i and j are connected
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
		$pos[$i][1] = 5 + ($loccnt[$dist[$i]]%2==0?1:-1)*$dv*ceil($loccnt[$dist[$i]]/2)+ (!empty($op['wiggle'])?$dv/5*sin(4*$dist[$i]):0);
		$loccnt[$dist[$i]]++;
	}

	return graphdrawit($pos,$g,$op);
}


//internal function, not to be used directly
function graphprocessoptions($g,$op) {
	global $RND;
	$n = count($g[0]);

	if (!isset($op['connected'])) {
		if (!empty($op['tree'])) {
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
		$nedg = 0;
		for ($i=0; $i<$n; $i++) {
			for ($j=$i+1; $j<$n; $j++) {
                if ((isset($g[$i][$j]) && $g[$i][$j]>0) || (isset($g[$j][$i]) && $g[$j][$i]>0)) {
					$nedg++;
				}
				if (!empty($op['digraph']) && (isset($g[$i][$j]) && $g[$i][$j]>0) && (isset($g[$j][$i]) && $g[$j][$i]>0)) {
					$nedg++;
				}
			}
		}
		$rweights = diffrands($rmin,$rmax,$nedg);
		$c = 0;
		for ($i=0; $i<$n; $i++) {
			for ($j=$i+1; $j<$n; $j++) {
				if (!empty($op['digraph'])) {
					if (isset($g[$i][$j]) && $g[$i][$j]>0) {
						$g[$i][$j] = $rweights[$c];
						$c++;
					}
					if (isset($g[$j][$i]) && $g[$j][$i]>0) {
						$g[$j][$i] = $rweights[$c];
						$c++;
					}
				} else {
					if ((isset($g[$i][$j]) && $g[$i][$j]>0) || (isset($g[$j][$i]) && $g[$j][$i]>0)) {
						$g[$i][$j] = $rweights[$c];
						$c++;
					}
				}
			}
		}
	}
	if (isset($op['randedges'])) {
		$origg = $g;
	}
	if (!empty($op['tree']) || (isset($op['randedges']) && $op['connected'])) {
		$g = graphkruskal($g);
	} else if (isset($op['randedges']) && !$op['connected']) {
		$g = graphemptygraph($n);
	}
	if (isset($op['randedges'])) {
		$rnd = $op['randedges'];
		for ($i=0; $i<$n; $i++) {
			for ($j=$i+1; $j<$n; $j++) {
				$p = $RND->rand(0,99);
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
	if (!empty($op['tree']) && !$op['connected']) {
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

//internal function, not usually used directly
//can get called with graph matrix g, g[i][j] if vertex i has edge to j
//pos is array where pos[i] = array(x,y) positions for vertices
function graphdrawit($pos,$g,$op) {
	if (!isset($op['width'])) {$op['width'] = 360;}
	if (!isset($op['height'])) {$op['height'] = 300;}
	if (!isset($op['weightoffset'])) { $op['weightoffset'] = .5; }
    if (isset($op['labels']) && is_array($op['labels']) && count($op['labels']) < count($g)) {
        echo "insufficient labels for all vertices";
        $op['labels'] = "letters";
    }
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
	$com = "setBorder(60,30,60,30);initPicture({$op['xmin']},{$op['xmax']},{$op['ymin']},{$op['ymax']}); fontsize=14;";
	$cx = ($op['xmin'] + $op['xmax'])/2;
	$cy = ($op['ymin'] + $op['ymax'])/2;

	$com .= "fontstyle='none';";
	if (!empty($op['digraph'])) {
		$com .= 'marker="arrow";';
	} else {
		$com .= 'marker=null;';
	}
 
	for ($i=0; $i<$n; $i++) {
		for ($j=$i+1; $j<$n; $j++) {
			if (!empty($op['digraph'])) {
				if ((isset($g[$j][$i]) && $g[$j][$i]>0) && (!isset($g[$i][$j]) || $g[$i][$j]<=0)) {
					$com .= "line([".$pos[$j][0].",".$pos[$j][1]."],[".$pos[$i][0].",".$pos[$i][1]."]);";
				} else if ((isset($g[$i][$j]) && $g[$i][$j]>0) && (!isset($g[$j][$i]) || $g[$j][$i]<=0)) {
					$com .= "line([".$pos[$i][0].",".$pos[$i][1]."],[".$pos[$j][0].",".$pos[$j][1]."]);";
				} else if ((isset($g[$i][$j]) && $g[$i][$j]>0) && (isset($g[$j][$i]) && $g[$j][$i]>0)) {
					$com .= "line([".$pos[$j][0].",".$pos[$j][1]."],[".$pos[$i][0].",".$pos[$i][1]."]);line([".$pos[$i][0].",".$pos[$i][1]."],[".$pos[$j][0].",".$pos[$j][1]."]);";
				}
			} else {
				if ((isset($g[$i][$j]) && $g[$i][$j]>0) || (isset($g[$j][$i]) && $g[$j][$i]>0)) {
					$com .= "line([".$pos[$i][0].",".$pos[$i][1]."],[".$pos[$j][0].",".$pos[$j][1]."]);";
				}
			}
		}
	}
	$com .= "fontbackground='white';";
	for ($i=0; $i<$n; $i++) {
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
		$com .= "dot([".$pos[$i][0].",".$pos[$i][1]."]);";
		for ($j=$i+1; $j<$n; $j++) {
			if (!empty($op['useweights']) && ((isset($g[$i][$j]) && $g[$i][$j]>0) || (isset($g[$j][$i]) && $g[$j][$i]>0))) {
				if (($i+$j)%2==0) {
					$mx = $pos[$j][0] + ($pos[$i][0] - $pos[$j][0])*($op['weightoffset']);
					$my = $pos[$j][1] + ($pos[$i][1] - $pos[$j][1])*($op['weightoffset']);
				} else {
					$mx = $pos[$j][0] + ($pos[$i][0] - $pos[$j][0])*(1-$op['weightoffset']);
					$my = $pos[$j][1] + ($pos[$i][1] - $pos[$j][1])*(1-$op['weightoffset']);
				}
				$com .= "fontfill='red';text([$mx,$my],'".max($g[$i][$j],$g[$j][$i])."');";

			}
		}
	}
	return showasciisvg($com,$op['width'],$op['height']);
}

//graphcomparecircuits(A,B)
//returns true or false
//compares two circuits to see if they are the same, regardless of starting
//vertex.  So "ABCDA" would be considered equivalent to "DCBAD"
//can be used with the conditional answer type to score circuits.
function graphcomparecircuits($a,$b) {
	$lena = strlen($a);
	$lenb = strlen($b);
	if ($a[0]==$a[$lena-1]) {
		$a = substr($a,0,-1);
		$lena--;
	}
	if ($b[0]==$b[$lenb-1]) {
		$b = substr($b,0,-1);
		$lenb--;
	}
	if ($lena!=$lenb) {return false;}
	$loc = strpos($b,$a[0]);
	$newb = substr($b,$loc).substr($b,0,$loc);
	$a = $a.$a[0];
	$newb = $newb.$newb[0];
	//echo "$a, $newb";
	if ($a==$newb || $a==strrev($newb)) {
		return true;
	} else {
		return false;
	}
}


//graphrandomgridschedule(n, m, p,[options])
//draws a n by m grid of vertices.  Each pair of neighboring
//and diagonal vertices has a p probabilility (0 to 1) of being connected
//an end vertex is added
//options['weights'] as an array of n*m elements will be used as weights.
//options['weights'] as a single number will randomize weights from 1 to that
//  value
//if options['labels'] are used, "start" and "end" will be added automatically
function graphrandomgridschedule($n,$m,$p,$op=array()) {
	global $RND;
	$op['digraph'] = true;
	$lettersarray = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
	$tot = $n*$m+1;
	$useweights = false;
	if (isset($op['weights'])) {
		$useweights = true;
		if (!is_array($op['weights'])) {
			$op['weights'] = diffrands(1,$op['weights'],$n*$m);
		}
	}

	$g = graphemptygraph($tot);
	$sn = $n;
	$gd = 1;//10/$sn;
	//$pos[0][0] = -$gd;
	//$pos[0][1] = $gd*(($n-1)/2);
	$pos[$tot-1][0] = $m*$gd;
	$pos[$tot-1][1] = $gd*(($n-1)/2);
	for ($i=0; $i<$tot-1; $i++) {
		$pos[$i][0] = floor(($i)/$sn)*$gd  + (!empty($op['wiggle'])?$gd/5*sin(3*$i):0);;
		$pos[$i][1] = $sn-1-(($i)%$sn)*$gd + (!empty($op['wiggle'])?$gd/5*sin(4*$i):0);
	}
	//connections to start and end
	for ($i = 1; $i<$n+1; $i++) {
		//$g[0][$i] = 1;
		$g[$tot-1-$i][$tot-1] = 1;
	}
	//connections between
	for ($i = 0; $i<$tot; $i++) {
		if ($i<$tot-$n) {
			$r[0] = $RND->rand(0,99);
			$r[1] = $RND->rand(0,99);
			$r[2] = $RND->rand(0,99);

			$out = false;
			if ($r[0]<$p*100) {
				$g[$i][$i+$n] = 1;
				$out = true;
			}

			if ($r[1]<$p*100  && ($i+1)%$n!=0) {
				$g[$i][$i+$n+1] = 1;
				$out = true;
			}

			if ($r[2]<$p*100 && ($i)%$n!=0) {
				$g[$i][$i+$n-1] = 1;
				$out = true;
			}
			//force one outgoing
			if (!$out) {
				$d = $RND->rand(0,2);
				if ($d<2) {
					$g[$i][$i+$n] = 1;
				} else {
					if (($i+1)%$n==0) {
						$g[$i][$i+$n-1] = 1;
					} else {
						$g[$i][$i+$n+1] = 1;
					}
				}
			}
			if (isset($op['forcemultpath']) && $op['forcemultpath']==$i) {
				if ($g[$i][$i+$n] == 1) {
					if (($i+1)%$n==0) {
						$g[$i][$i+$n-1] = 1;
					} else {
						$g[$i][$i+$n+1] = 1;
					}
				} else {
					$g[$i][$i+$n] = 1;
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
			$op['labels'] = array_slice($lettersarray,0,$tot-1);
		} else {
			$op['labels'] = array_slice($op['labels'],0,$tot-1);
		}
		//array_unshift($op['labels'],"Start");
		array_push($op['labels'],"End");
		if ($useweights) {
			for ($i=0; $i<$tot-1; $i++) {
				$op['labels'][$i] .= ' ('.$op['weights'][$i].')';
			}
		}
	}
	//array_unshift($op['weights'],0);
	array_push($op['weights'],0);
	return array(graphdrawit($pos,$g,$op),$g,$op['weights']);
}

//graphdecreasingtimelist(g,w)
//uses the scheduling priority list generated by the decreasing time list
//algorithm
//g is the graph
//w is the array of task times
function graphdecreasingtimelist($g,$w) {
	$k = graphgetkeysweights($w); //$k = array_keys($w);  //array_reverse(array_keys($w));
	while ($w[$k[count($k)-1]]==0) {
		array_pop($k);
	}
	return $k;
}

//graphcriticaltimelist(g,w)
//produces the scheduling priority list generated by the critical path algorithm
//g is the graph
//w is the array of task times
function graphcriticaltimelist($g,$w) {
	list($dist,$next) = graphbackflow($g,$w);
	unset($dist[count($dist)-1]);
	//unset($dist[0]);
	//asort($dist);
	$k = graphgetkeysweights($dist);
	return $k;
}

//graphgetcriticalpath(g,w)
//returns array(path,time,isunique) where path is an array of task indexes corresonding to
// the critical path, and time is the length of the critical path (the critical time)
// isunique is true if there is only one critical path
//g is the graph
//w is the array of task times
function graphgetcriticalpath($g,$w) {
	list($dist,$next) = graphbackflow($g,$w);
	unset($dist[count($dist)-1]);
	$maxs = array_keys($dist, max($dist));
	$isunique = (count($maxs)==1);
	$path = array();
	$cur = $maxs[0];
	$time = $dist[$cur];
	while (isset($dist[$cur]) && $dist[$cur]>0) {
		$path[] = $cur;
		$cur = $next[$cur];
	}
	return array($path,$time,$isunique);
}

function graphgetkeysweights($w) {
	//have array of task=>weight
	//want array of tasks, where weights are decreasing order, but tasks for same weights are increasing order
	foreach ($w as $t=>$v) {
		$w[$t] += (100-$t-1)/100;
	}
	arsort($w);
	return array_keys($w);
}

//graphbackflow(g,[w])
//computes longest-path algorithm on the graph g
//g is a 2-dimensional matrix
//g[i][j] &gt; 1 if vertexes i leads to j
//This might give bad/weird results if graph has a circuit
//the last vertex will be used as the destination vertex
//w are weights for the vertices, if a scheduling digraph and
//tasks rather than edges have weights
//returns array(dist,next) where
//dist[i] is the longest dist to end, and
//next[i] is the vertex next closest to the end
function graphbackflow($g,$w=array()) {
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
		$newtoprocess = array();
		for ($k=0; $k<count($toprocess); $k++) {
			$cur = $toprocess[$k];
			for ($i=0; $i<$n; $i++) {
				if (!isset($eaten[$i]) && isset($g[$i][$cur]) && $g[$i][$cur]>0) { //vertices leading to $cur
					if (count($w)>0) {
						$alt = $dist[$cur] + $w[$i];
					} else {
						$alt = $dist[$cur] + $g[$i][$cur];
					}
					if ($alt>$dist[$i]) {
						$dist[$i] = $alt;
						$next[$i] = $cur;

					}
					if (!in_array($i,$newtoprocess)) {
						$douse = true;
						//don't use if not terminal
						for ($j=0; $j<$n; $j++) {
							if (isset($g[$i][$j]) && $g[$i][$j]>0 && !isset($eaten[$j]) && $j!=$cur) {
								$douse = false; break;
							}
						}
						if ($douse) {
							$newtoprocess[] = $i;
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


//graphlistprocessing(g,t,L,p,[options])
//calculates the list processing algorithm on p processors with priority list L
//t is an array of task times
//L is an array of indices into g showing the priority list
//g is a 2-dimensional matrix
//g[i][j] > 0 if vertexes i leads to j, where cost of task i is c
//This might give bad/weird results if graph has a circuit
//the last vertex will be used as the destination vertex; it should be the
//last task on the priority list and have task time 0.
//output is out[processor] = array of array(task, timestarted, tasklength)
function graphlistprocessing($g,$t,$L,$p,$op=array()) {
	//print_r($L);
	//print_r($t);
	$out = array();
	for ($i=0;$i<$p;$i++) {
		$out[$i] = array();
	}
	$n = count($g[0]);
	$done = array();
	$started = array();
	$curtime = 0;
	$proc = array_fill(0,$p,0); //holds time when each processor is done w current task
	$prereqs = graphadjacencytoprereqs($g,$op);
	$cnt = 0;
	while (count($done)<$n-1) { //we don't worry about the last task.
		//mark done tasks
		if ($curtime > 0) {
			for ($i=0;$i<$p;$i++) {
				if ($proc[$i]<=$curtime && count($out[$i])>0) { //if processor is done
					$done[$out[$i][count($out[$i])-1][0]] = 1; //mark this proc's last task as done
					//echo "marking".$out[$i][count($out[$i])-1][0]." done<br/>";
				}
			}
		}
		//get ready tasks
		$ready = array();
		for ($i=0;$i<$n;$i++) {
            if (!isset($L[$i])) { continue;} // "End" node not included in priority list
			if (isset($started[$L[$i]])) {continue; } //skip if done
			$isready = true;
			foreach ($prereqs[$L[$i]] as $j) { //foreach prereq check if done
				if (!isset($done[$j])) {
					$isready = false; break;
				}
			}
			if ($isready) {
				$ready[] = $L[$i];
				if ($L[$i]==$n-1) {break 2;} //last last (dest) is ready
			}
		}
		//echo "curtime: $curtime, ready: ".implode(',',$ready)."<br/>";
		//assign ready tasks
		if (count($ready) > 0) { //if anything is ready
			$todo = count($ready);
			for ($i=0;$i<$p && $todo>0;$i++) {
				if ($proc[$i]<=$curtime) { //if processor is done
					$toassign = array_shift($ready);  //shift off first ready task
					//echo "assigning $toassign to $i<br/>";
					$out[$i][] = array($toassign,$curtime,$t[$toassign]);  //add to output
					$proc[$i] = $curtime + $t[$toassign];  //update time when proc will be done
					$started[$toassign] = 1;
					$todo--;
				}
			}
			$curtime = min($proc); //update next time to check to when next proc is done.
		} else {
			//nothing is ready, so need to idle until next task is done
			$nexttime = 1000000000;
			foreach ($proc as $j) {
				if ($j>$curtime && $j<$nexttime) {
					$nexttime = $j;
				}
			}
			$curtime = $nexttime;
		}
	}
	return $out;
}


//graphscheduletaskinfo(schedule,n)
//where schedule is the result of graphlistprocessing
//and n is the task number (0 indexed)
//returns array(processor assigned (0 indexed), task start, task time)
function graphscheduletaskinfo($sc,$n) {
	foreach ($sc as $p=>$tl) {
		foreach ($tl as $taskitems) {
			if ($taskitems[0]==$n) {
				return array($p,$taskitems[1], $taskitems[2]);
			}
		}
	}
}

//graphschedulecompletion(schedule)
//where schedule is the result of graphlistprocessing
//returns completion time of the schedule
function graphschedulecompletion($sc) {
	$time = 0;
	foreach ($sc as $pl) {
        if (count($pl)==0) { continue; } // processsor did nothing
		$ptime = $pl[count($pl)-1][1] + $pl[count($pl)-1][2];
		if ($ptime>$time) {
			$time = $ptime;
		}
	}
	return $time;
}

//graphscheduleidle(schedule,p)
//where schedule is the result of graphlistprocessing
//and p is the processor number (0-indexed)
function graphscheduleidle($sc,$p) {
	$idle = 0;
	$lastend = 0;
	foreach ($sc[$p] as $i=>$ti) {
		$idle += ($ti[1] - $lastend);
		$lastend = $ti[1]+$ti[2];
	}
	$idle += (graphschedulecompletion($sc) - $lastend);
	return $idle;
}

//graphscheduleproctasks(schedule,p,[oneindex])
//return an array of the tasks the processor worked on, in order
//set oneindex=true to add one to the last index to get task numbers
function graphscheduleproctasks($sc,$p,$oneindex=false) {
	$out = array();
	foreach ($sc[$p] as $ti) {
		$out[] = $ti[0] + ($oneindex?1:0);
	}
	return $out;
}


//graphdrawschedule(sc,[width,height,names])
//draws the schedule generated by graphlistprocessing
//defaults to width=600,height=70*# of processors
//can provide array of task names, or it will default to task numbers (starting at 1)
function graphdrawschedule($sc,$w=600,$h=-1,$names=array()) {
	$p = count($sc);
	if ($h==-1) {
		$h = 70*$p;
	}
	//text label is around 40 px, so can do w/40 labels.  Divide ct / (w/40)
	$colors = array('red','blue','green','orange','cyan','yellow','purple');
	$ct = graphschedulecompletion($sc);
	$sp = ceil($ct/($w/25));
	//penacto - video thing
	$com = "setBorder(12,8,12,20);initPicture(0,$ct,0,$p);stroke='gray';";
	for ($i=0;$i<=$ct;$i++) {
		$com .= "line([$i,-.1],[$i,$p+.1]);";
	}
	$com .= "stroke='black';";
	for ($i=0;$i<=$ct;$i+=$sp) {
		$com .= "text([$i,$p],'$i','above');";
		$com .= "line([$i,-.1],[$i,$p+.1]);";
	}
	$com .= "fill='gray';rect([0,0],[$ct,$p]);";
	for ($i=1;$i<$p;$i++) {
		$com .= "line([0,$i],[$ct,$i]);";
	}
	$com .= "fontbackground='white';";
	$cnt = 0;
	foreach ($sc as $n=>$tl) {
		foreach ($tl as $ti) {
			$col = $colors[$cnt%7]; $cnt++;
			if (count($names)==0) {
				$tn = 'T'.($ti[0]+1);
			} else {
				$tn = $names[$ti[0]];
			}
			$com .= "fill='$col';rect([".$ti[1].",".($p-$n-1)."],[".($ti[1]+$ti[2]).",".($p-$n)."]);";
			$com .= "text([".($ti[1]+.5*$ti[2]).",".($p-$n-.5)."],'$tn');";
		}
	}

	return showasciisvg($com,$w,$h);
}

//graphschedulelayout(g,w,pos,[op])
//draws a schedule digraph
//g is the graph, w is an array of task times
//pos is array where L[task number] = array(column,row (counting up from bottom))
//use $options['labels'] to specify labels, or T1 - TN will be used
function graphschedulelayout($g,$w,$pos,$op=array()) {
	$n = count($g[0]);
	$op['digraph'] = true;
	if (!isset($op['width'])) {
		$op['width'] = 600;
	}
	if (!isset($op['labels'])) {
		for ($i=0;$i<$n-1;$i++) {
			$op['labels'][$i] = 'T'.($i+1);
		}
		$op['labels'][$n-1] = 'End';
	}
	for ($i=0;$i<$n-1;$i++) {
		$op['labels'][$i] .= ' ('.$w[$i].')';
	}
	return graphdrawit($pos,$g,$op);
}

//graphschedulemultchoice(g,t,L,p)
//attempts to return 4 schedules which are different.  The first is the
//correct schedule based on the provided priority list L
function graphschedulemultchoice($g,$t,$L,$p) {
	$sc = graphlistprocessing($g,$t,$L,$p);
	$tocomp = serialize($sc);
	$n = count($L)-1;
	$out = array($sc);
	$found = 0;
	for ($cnt=0;$cnt<20 && $found<4;$cnt++) {
		$pl = diffrands(0,$n-1,$n);
		$pl[] = $n;
		$newsc = graphlistprocessing($g,$t,$pl,$p);
		if (serialize($newsc)!=$tocomp) {
			$found++;
			$out[] = $newsc;
		}
	}
	//echo "its: $cnt, found: $found<br/>";
	return $out;
}
?>

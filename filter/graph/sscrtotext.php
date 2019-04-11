<?php
//create textual representation based on shortscript for asciisvg graph
//(c) 2016 David Lippman

function shortscriptToText($sscript) {
	$sa = explode(',', $sscript);
	$out = '';
	if (count($sa)>10) {
		$out .= 'Graphs on the window x='.$sa[0].' to '.$sa[1].' and y='.$sa[2].' to '.$sa[3].': ';
		$inx = 11;
		while (count($sa)>$inx+9) {
			$out .= $sa[$inx+7] . " ";
			if ($sa[$inx+9] != "") {
				if ($sa[$inx+9]=='2') {
						$out .= "dotted ";
				} else if ($sa[$inx+9]=='5') {
						$out .= "dashed ";
				} else if ($sa[$inx+9]=='5 2') {
						$out .= "tight dashed ";
				} else if ($sa[$inx+9]=='7 3 2 3') {
						$out .= "dash-dot ";
				}
			}
			if ($sa[$inx]=="slope") {
				$out .= "slopefield where dy/dx=".$sa[$inx+1] . ". ";
			} else if ($sa[$inx]=="label") {
				$out .= "label with text ".$sa[$inx+1] . ' at the point ('.$sa[$inx+5].','.$sa[$inx+6].'). ';
			} else {
				if ($sa[$inx]=="func") {
					$out .= "graph of y=".$sa[$inx+1];
					$varlet = 'x';
				} else if ($sa[$inx] == "polar") {
					$out .= "polar graph of r=".$sa[$inx+1];
					$varlet = 'r';
				} else if ($sa[$inx] == "param") {
					$out .= "parametric graph of x(t)=".$sa[$inx+1] . ", y(t)=" . $sa[$inx+2];
					$varlet = 't';
				}
				if ($sa[$inx+5] != "") {
					$out .= " from " . $varlet . '='.$sa[$inx+5]. ' ';
					if ($sa[$inx+3]==1) {
						$out .= 'with an arrow ';
					} else if ($sa[$inx+3]==2) {
						$out .= 'with an open dot ';
					} else if ($sa[$inx+3]==3) {
						$out .= 'with a closed dot ';
					}
					$out .= "to ".$varlet.'='.$sa[$inx+6].' ';
					if ($sa[$inx+4]==1) {
						$out .= 'with an arrow ';
					} else if ($sa[$inx+4]==2) {
						$out .= 'with an open dot ';
					} else if ($sa[$inx+4]==3) {
						$out .= 'with a closed dot ';
					}
				}
				$out .= '. ';
			}
			$inx .= 10;
		}
	}
	return $out;
}

?>

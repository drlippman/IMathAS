<?php

/* Differential equations functions

2023 David Lippman
*/

global $allowedmacros;
array_push($allowedmacros,"diffeq_slopefield", "diffeq_comparegensoln");

// func is either just y'(x,y), or x'(x,y),y'(x,y)
function diffeq_slopefield($func,$options=[]) {
    if ($func == '') { return ''; }
    $xmin = floatval($options['xmin'] ?? -4);
    $xmax = floatval($options['xmax'] ?? 4);
    $ymin = floatval($options['ymin'] ?? -4);
    $ymax = floatval($options['ymax'] ?? 4);
    $xgrid = floatval($options['xgrid'] ?? 0);
    $ygrid = floatval($options['ygrid'] ?? $xgrid);
    $xlbl = floatval($options['xlbl'] ?? 1);
    $ylbl = floatval($options['ylbl'] ?? $xlbl);
    $dx = floatval($options['dx'] ?? 1);
    $dy = floatval($options['dy'] ?? $dx);
    $vars = $options['vars'] ?? 'x,y';
    $width = floatval($options['width'] ?? 300);
    $height = floatval($options['height'] ?? 300);
    $showarrows = !empty($options['arrows']) ? true : false;
    $labels = (!empty($options['labels'] ?? true)) ? 1 : 'null';
    $linewidth = floatval($options['linewidth'] ?? 1);
    $color = $options['color'] ?? 'blue';
    $len = floatval($options['length'] ?? 0.75);

    $vs = array_map('trim', explode(',',$vars));
    $ystretch = ($ymax-$ymin)/($xmax-$xmin);
    $len = min($dx,$dy/$ystretch)*$len;
    if ($xmin > $xmax || $ymin > $ymax || $dx<=0 || $dy <= 0 || count($vs) != 2) {
        echo 'invalid options to draw_slopefield';
        return '';
    }
    $xrnd = max(0,intval(floor(-log10(abs($xmax-$xmin))-1e-12))+5);
	$yrnd = max(0,intval(floor(-log10(abs($ymax-$ymin))-1e-12))+5);
    $doalt = ($_SESSION['graphdisp']==0);

    $funcparts = explode(',', $func);
    $issystem = (count($funcparts)==2);
    if ($issystem) {
        $xfunc = makeMathFunction($funcparts[0], $vars);
        $yfunc = makeMathFunction($funcparts[1], $vars);
    } else {
        $yfunc = makeMathFunction($funcparts[0], $vars);
        $vx = 1;
    }

    if ($doalt) {
        $alt = '<table class=gridded><caption>'._('Graph of a slopefield. The table lists the slope at various points.').'</caption>';
        $alt .= '<thead><tr><th></th>';
        for ($y = $xmin; $y < $ymax + $dy/2; $y += $dy) {
            $alt .= '<th>'.$vs[1].'='.round($y,$yrnd).'</th>';
        }
        $alt .= '</tr></thead><tbody>';
    }

    $out = "setBorder(10);initPicture($xmin,$xmax,$ymin,$ymax);axes($xlbl,$ylbl,$labels,$xgrid,$ygrid);stroke=\"$color\";fill=\"$color\";";
    if ($showarrows) {
        $out .= 'marker="arrow";';
        $pixlen = min($width/($xmax-$xmin)*$dx, $height/($ymax-$ymin)*$dy);
        $relsize = ($pixlen * .2)/15;
        $out .= "arrowrelsize=$relsize;";
        if ($linewidth > 1) {
            $out .= "arrowoffset=-".($linewidth).';';
        }
    }
    $out .= "strokewidth=$linewidth;";


    for ($x = $xmin; $x < $xmax + $dx/2; $x += $dx) {
        if ($doalt) {
            $alt .= '<tr><th scope=row>'.$vs[0].'='.round($x,$xrnd).'</th>';
        }
        for ($y = $ymin; $y < $ymax + $dy/2; $y += $dy) {
            $vy = $yfunc([$vs[0]=>$x, $vs[1]=>$y]);
            if ($issystem) {
                $vx = $xfunc([$vs[0]=>$x, $vs[1]=>$y]);
            }
            if (!is_finite($vy) || !is_finite($vx)) {
                if ($doalt) {
                    $alt .= '<td>-</td>';
                }
                continue;
            }
            if ($doalt) {
                $alt .= '<td>' . ($vx==0 ? _('undefined') : round($vy/$vx, 2)) . '</td>';
            } else {
                $mag = sqrt($vx*$vx+$vy/$ystretch*$vy/$ystretch);
                if ($mag == 0) { continue; }
                $x0 = round($x - 0.5*$vx*$len/$mag, $xrnd);
                $x1 = round($x + 0.5*$vx*$len/$mag, $xrnd);
                $y0 = round($y - 0.5*$vy*$len/$mag, $yrnd);
                $y1 = round($y + 0.5*$vy*$len/$mag, $yrnd);
                $out .= "line([$x0,$y0],[$x1,$y1]);";
            }
        }
    }
    if ($doalt) {
        $alt .= '</tbody></table>';
		return $alt;
	} else {
		return "<embed type='image/svg+xml' align='middle' width='$width' height='$height' alt='Graph of slopefield; enable text alternatives in user preferences for more' script='$out' />\n";
	}

}

function diffeq_comparegensoln($a,$b,$vars,$const,$tol='.001',$domain='-10,10') {
    if ($a=='' || $b=='') { return false;}
    if (!is_array($vars)) {
        $vars = array_map('trim', explode(',', $vars));
    }
    if (!is_array($const)) {
        $const = array_map('trim', explode(',', $const));
    }
    if (strval($tol)[0]=='|') {
        $abstolerance = floatval(substr($tol,1));
    }
    
    $allvars = implode(',',$vars).','.implode(',',$const);
    list($variables, $tps, $flist) = numfuncGenerateTestpoints($allvars, $domain);
    $vlist = implode(",",$variables);
    $a = numfuncPrepForEval($a, $variables);
    $b = numfuncPrepForEval($b, $variables);

    $afunc = makeMathFunction($a, $vlist, [], $flist, true);
    $bfunc= makeMathFunction($b, $vlist, [], $flist, true);
    if ($afunc === false || $bfunc === false) {
        if (!empty($GLOBALS['inQuestionTesting'])) {
            echo "<p>Debug info: one function failed to compile.</p>";
        }
        return false;
    }

    $avals = [];
    $bvals = [];
    $varcnt = count($vars);
    for ($idx=-1; $idx < count($const); $idx++) {
        for ($i = 0; $i < 20; $i++) {
            $varvals = array();
            for($j=0; $j < count($vars); $j++) {
                $varvals[$vars[$j]] = $tps[$i][$j];
            }
            for($j=0; $j < count($const); $j++) {
                $varvals[$const[$j]] = ($j==$idx)?$tps[$i][$varcnt]:0;
            }
            $avals[$idx][$i] = $afunc($varvals);
            $bvals[$idx][$i] = $bfunc($varvals);

            if ($avals[$idx][$i]===false || $bvals[$idx][$i]===false) {
                return false;
            }
            if ($idx > -1) {
                if (isset($avals[-1][$i]) && !isNaN($avals[-1][$i])) {
                    $avals[$idx][$i] -= $avals[-1][$i];
                }
                if (isset($bvals[-1][$i]) && !isNaN($bvals[-1][$i])) {
                    $bvals[$idx][$i] -= $bvals[-1][$i];
                }
            }
        }
    }
    $usedbs = [];
    for ($ai=-1;$ai<count($const);$ai++) {
        for ($bi=($ai==-1)?-1:0;$bi<($ai==-1?0:count($const));$bi++) {
            if (!empty($usedbs[$bi])) { continue; } // only use each once
            $cntnana = 0;
            $cntnanb = 0;
            $diffnan = 0;
            $cntzero = 0;
            $cntbothzero = 0;
            $ratios = [];
            for ($i = 0; $i < 20; $i++) {
                $aval = $avals[$ai][$i];
                $bval = $bvals[$bi][$i];
                if (isNaN($aval)) {
                    $cntnana++; 
                    if (isNaN($bval)) {
                        $cntnanb++;
                    } else {
                        $diffnan++;
                    }
                    continue;
                } else if (isNaN($bval)) {
                    $cntnanb++;
                    $diffnan++;
                    continue;
                } else if ($ai > -1 && abs($aval)>.00000001 && is_numeric($bval)) {
                    $ratios[] = $bval/$aval;
                    if (abs($bval)<=.00000001 && $aval!=0) {
                        $cntzero++;
                    }
                } else if ($ai > -1 && abs($aval)<=.00000001 && is_numeric($bval) && abs($bval)<=.00000001) {
                    $cntbothzero++;
                }
                if ($ai==-1) {
                    // for yp, test now using normal method
                    if (isset($abstolerance)) {
                        if (abs($aval-$bval) > $abstolerance-1E-12) {return false;}
                    } else {
                        if ((abs($aval-$bval)/(abs($aval)+.0001) > $tol-1E-12)) {return false;}
                    }
                }
            }
            if ($cntnana==20 || $cntnanb==20 || ($ai==-1 && $diffnan>1)) {
                // either function evaluated to nan at all points, or for yp, nans are diff
                return false;
            }   
            if ($ai>-1) {
                $match = false;
                if ($cntbothzero > 18-min($cntnana,$cntnanb)) {
                    $match = true;
                } else if (count($ratios)>2 && count($ratios)!=$cntzero) {
                    $meanratio = array_sum($ratios)/count($ratios);
                    $match = true;
                    for ($i=0; $i<count($ratios); $i++) {
                        if (isset($abstolerance)) {
                            if (abs($ratios[$i]-$meanratio) > $abstolerance+1E-12) {$match = false; break;}
                        } else {
                            if ((abs($ratios[$i]-$meanratio)/(abs($meanratio)+.0001) > $tol+1E-12)) {$match = false; break;}
                        }
                    }
                }
                if ($match) {
                    $usedbs[$bi] = 1;
                    continue 2; // continue to next ai
                }
            }
        }
    }
    // see if we matched all
    return (count($usedbs)==count($const)); 
}

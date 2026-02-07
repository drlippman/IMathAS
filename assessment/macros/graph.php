<?php

// graphing macros

array_push(
    $GLOBALS['allowedmacros'],
    'showplot',
    'addplotborder',
    'replacealttext',
    'addlabel',
    'addlabelabs',
    'adddrawcommand',
    'mergeplots',
    'addfractionaxislabels',
    'connectthedots',
    'showasciisvg',
    'arraystodots',
    'arraystodoteqns',
    'textonimage',
    'changeimagesize',
    'addimageborder',
    'invertplot'
);

//$funcs can be a string or an array of strings.  Each string should have format:
//"function,color,xmin,xmax,startmarker,endmarker,strokewidth,strokedash"
//not all entries are required.  To skip middle ones, leave them empty
function showplot($funcs) { //optional arguments:  $xmin,$xmax,$ymin,$ymax,labels,grid,width,height
    if (!is_array($funcs)) {
        settype($funcs, "array");
    }
    $settings = array(-5, 5, -5, 5, 1, 1, 200, 200);
    for ($i = 1; $i < func_num_args(); $i++) {
        $v = func_get_arg($i);
        if ($v === null) {
            $v = 0;
        }
        if (!is_scalar($v)) {
            echo 'Invalid input ' . ($i + 1) . ' to showplot';
        } else {
            $settings[$i - 1] = $v;
        }
    }
    $fqonlyx = false;
    $fqonlyy = false;
    if (strpos($settings[0], '0:') !== false) {
        $fqonlyx = true;
        $settings[0] = substr($settings[0], 2);
    }
    if (strpos($settings[2], '0:') !== false) {
        $fqonlyy = true;
        $settings[2] = substr($settings[2], 2);
    }
    $yminauto = false;
    $ymaxauto = false;
    if (substr($settings[2], 0, 4) == 'auto') {
        $yminauto = true;
        if (strpos($settings[2], ':') !== false) {
            $ypts = explode(':', $settings[2]);
            $settings[2] = $ypts[1];
        } else {
            $settings[2] = -5;
        }
    }
    if (substr($settings[3], 0, 4) == 'auto') {
        $ymaxauto = true;
        if (strpos($settings[3], ':') !== false) {
            $ypts = explode(':', $settings[3]);
            $settings[3] = $ypts[1];
        } else {
            $settings[3] = 5;
        }
    }
    $winxmin = is_numeric($settings[0]) ? $settings[0] : -5;
    $winxmax = is_numeric($settings[1]) ? $settings[1] : 5;
    $ymin = is_numeric($settings[2]) ? $settings[2] : -5;
    $ymax = is_numeric($settings[3]) ? $settings[3] : 5;
    $plotwidth = is_numeric($settings[6]) ? $settings[6] : 200;
    $plotheight = is_numeric($settings[7]) ? $settings[7] : 200;
    $noyaxis = false;
    $noxaxis = false;
    if (is_numeric($ymin) && is_numeric($ymax) && $ymin == 0 && $ymax == 0) {
        $ymin = -0.5;
        $ymax = 0.5;
        $noyaxis = true;
        $settings[2] = -0.5;
        $settings[3] = 0.5;
    }
    $xxmin = $winxmin - 5 * ($winxmax - $winxmin) / $plotwidth;
    $xxmax = $winxmax + 5 * ($winxmax - $winxmin) / $plotwidth;
    $yymin = $ymin - 5 * ($ymax - $ymin) / $plotheight;
    $yymax = $ymax + 5 * ($ymax - $ymin) / $plotheight;

    //$commands = "setBorder(5); initPicture({$settings[0]},{$settings[1]},{$settings[2]},{$settings[3]});";
    //$alt = "Graph, window x {$settings[0]} to {$settings[1]}, y {$settings[2]} to {$settings[3]}.";
    $commands = '';
    $alt = '';
    if (strpos($settings[4], ':')) {
        $lbl = explode(':', $settings[4]);
        $lbl[0] = evalbasic($lbl[0], true, true);
        $lbl[1] = evalbasic($lbl[1], true, true);
    } else {
        $settings[4] = evalbasic($settings[4], true, true);
        $lbl = [];
    }
    if (is_numeric($settings[4]) && $settings[4] > 0) {
        $commands .= 'axes(' . $settings[4] . ',' . $settings[4] . ',1';
    } else if (isset($lbl[0]) && is_nicenumber($lbl[0])) {
        if ($lbl[0] == 0) {
            $lbl[0] = 1;
            $noxaxis = true;
        }
        if ($lbl[1] == 0) {
            $lbl[1] = 1;
            $noyaxis = true;
        }
        if (!isset($lbl[2]) || $lbl[2] != 'off') {  //allow xscl:yscl:off for ticks but no labels
            $commands .= 'axes(' . $lbl[0] . ',' . $lbl[1] . ',1';
        } else {
            $commands .= 'axes(' . $lbl[0] . ',' . $lbl[1] . ',null';
        }
    } else {
        $commands .= 'axes(1,1,null';
    }

    if (strpos($settings[5], ':')) {
        $settings[5] = str_replace(array('(', ')'), '', $settings[5]);
        $grid = explode(':', $settings[5]);
        foreach ($grid as $i => $v) {
            $grid[$i] = evalbasic($v, true, true);
        }
    } else {
        $settings[5] = evalbasic($settings[5], true, true);
    }
    if (is_numeric($settings[5]) && $settings[5] > 0) {
        $commands .= ',' . $settings[5] . ',' . $settings[5];
    } else if (isset($grid[0]) && is_numeric($grid[0])) {
        $commands .= ',' . $grid[0] . ',' . $grid[1];
    } else {
        $commands .= ',0,0';
        //$commands .= ');';
    }

    if ($noyaxis == true || $noxaxis == true) {
        $commands .= ',' . ($noxaxis ? 0 : 1) . ',' . ($noyaxis ? 0 : 1) . ',1);';
    } else if ($fqonlyx || $fqonlyy) {
        $commands .= ',' . ($fqonlyx ? '"fq"' : 1) . ',' . ($fqonlyy ? '"fq"' : 1) . ');';
    } else {
        $commands .= ');';
    }
    $xvar = 'x';
    $yvar = 'y';
    $hasaxislabels = false;
    if (isset($lbl) && count($lbl) > 3) {
        $xvar = $lbl[2];
        $yvar = $lbl[3];
        if ($xvar != '') {
            $commands .= "text([{$winxmax},0],\"{$xvar}\",\"aboveleft\");";
        }
        if ($yvar != '') {
            $commands .= "text([0,{$ymax}],\"{$yvar}\",\"belowright\");";
        }
        $hasaxislabels = true;
    }
    $absymin = 1E10;
    $absymax = -1E10;
    $globalalt = '';
    $allcolors = [];
    $labelassoc = [];
    if ($_SESSION['graphdisp'] == 0) {
        foreach ($funcs as $k=>$function) {
            if (is_array($function) || $function == '') {
                continue;
            }
            if (substr($function,0,5) == 'text,') {
                $function = str_replace('\\,', '&x44;', $function);
                $function = listtoarray($function);
                if (isset($function[7])) {
                    $labelassoc[$function[7]] = $function[3];
                    $funcs[$k] = '';
                }
            }
        }
    }
    foreach ($funcs as $k=>$function) {
        if (is_array($function)) {
            echo _('Input to showplot should be a single function or array of functions, with parameters added with commas after, not as an array');
            continue;
        }
        if ($function == '') {
            continue;
        }
        if (substr($function, 0, 4) == 'alt:') {
            $globalalt = substr($function, 4);
            continue;
        }
        $function = str_replace('\\,', '&x44;', $function);
        $function = listtoarray($function);
        if (!isset($function[0]) || $function[0] === '') {
            continue;
        }
        //correct for parametric
        $isparametric = false;
        $isineq = false;
        $isxequals = false;
        //has y= when it shouldn't
        if ($function[0][0] == 'y') {
            $function[0] = preg_replace('/^\s*y\s*=?/', '', $function[0]);
            if ($function[0] === '') {
                continue;
            }
        }

        // rewrite "y,color,x,x,closed" as dot
        if (
            count($function) > 4 && $function[0] != 'dot' && $function[0] != 'text' && is_numeric($function[0]) &&
            $function[2] == $function[3] && ($function[4] == 'closed' || $function[4] == 'open')
        ) {
            $function = ['dot', $function[2], $function[0], $function[4], $function[1]];
        }

        if ($function[0] == 'dot') {  //dot,x,y,[closed,color,label,labelloc]
            if (!isset($function[4]) || $function[4] == '') {
                $function[4] = 'black';
            }

            $path = 'stroke="' . $function[4] . '";';
            $path .= 'dot([' . evalbasic($function[1], true) . ',' . evalbasic($function[2], true) . ']';
            $coord = '(' . $function[1] . ',' . $function[2] . ')';
            if (isset($function[3]) && $function[3] == 'open') {
                $path .= ',"open"';
                if ($noyaxis) {
                    $alt .= sprintf(_('Open dot at %s'), $function[1]);
                } else {
                    $alt .= sprintf(_('Open dot at %s'), $coord);
                }
            } else {
                $path .= ',"closed"';
                if ($noyaxis) {
                    $alt .= sprintf(_('Solid dot at %s'), $function[1]);
                } else {
                    $alt .= sprintf(_('Dot at %s'), $coord);
                }
            }
            $alt .= ', color ' . $function[4];
            $allcolors[] = $function[4];

            if (isset($function[5]) && $function[5] != '') {
                $function[5] = str_replace('&x44;', ',', $function[5]);
                if (!isset($function[6])) {
                    $function[6] = 'above';
                }
                $path .= ',"' . Sanitize::encodeStringForJavascript($function[5]) . '"';
                $path .= ',"' . $function[6] . '"';
                $alt .= ', labeled ' . $function[5];
            }
            $alt .= '. ';
            $path .= ');';
            $commands .= $path;
            continue; //skip the stuff below
        } else if ($function[0] == 'text') {  //text,x,y,textstring,color,loc,angle
            $function[3] = str_replace('&x44;', ',', $function[3]);
            if (!isset($function[4]) || $function[4] == '') {
                $function[4] = 'black';
            }
            if (!isset($function[5]) || $function[5] == '') {
                $function[5] = 'centered';
            }
            if (!isset($function[6]) || $function[6] == '') {
                $function[6] = 0;
            } else {
                $function[6] = intval($function[6]);
            }
            $path = 'fontfill="' . $function[4] . '";';
            $path .= 'text([' . evalbasic($function[1], true) . ',' . evalbasic($function[2], true) . '],"' . $function[3] . '","' . $function[5] . '",' . $function[6] . ');';
            $coord = '(' . $function[1] . ',' . $function[2] . ')';
            $alt .= sprintf(_('Text label, color %s, at %s reading: %s'), $function[4], $coord, $function[3]) . '. ';
            $allcolors[] = $function[4];
            $commands .= $path;
            continue; //skip the stuff below
        } else if ($function[0][0] == '[') { //strpos($function[0],"[")===0) {
            $isparametric = true;
            $xfunc = makepretty(str_replace("[", "", $function[0]));
            $evalxfunc = makeMathFunction($xfunc, "t", [], '', true);
            $yfunc = makepretty(str_replace("]", "", $function[1]));
            $evalyfunc = makeMathFunction($yfunc, "t", [], '', true);
            array_shift($function);
            if ($evalxfunc === false || $evalyfunc === false) {
                continue;
            }
        } else if ($function[0][0] == '<' || $function[0][0] == '>') {
            $isineq = true;
            if ($function[0][1] == '=') {
                $ineqtype = substr($function[0], 0, 2);
                $func = makepretty(substr($function[0], 2));
            } else {
                $ineqtype = $function[0][0];
                $func = makepretty(substr($function[0], 1));
            }
            $evalfunc = makeMathFunction($func, "x", [], '', true);
            if ($evalfunc === false) {
                continue;
            }
        } else if (strlen($function[0]) > 1 && $function[0][0] == 'x' && ($function[0][1] == '<' || $function[0][1] == '>' || $function[0][1] == '=')) {
            $isxequals = true;
            if ($function[0][1] == '=') {
                $val = substr($function[0], 2);
                if (!is_numeric($val)) {
                    // convert to parametric
                    $isxequals = false;
                    $isparametric = true;
                    $yfunc = "t";
                    $evalyfunc = makeMathFunction("t", "t");
                    $xfunc = makepretty(str_replace('y', 't', $val));
                    $evalxfunc = makeMathFunction($xfunc, "t", [], '', true);
                    if ($evalxfunc === false || $evalyfunc === false) {
                        continue;
                    }
                }
            } else {
                $isineq = true;
                if ($function[0][2] == '=') {
                    $ineqtype = substr($function[0], 1, 2);
                    $val = substr($function[0], 3);
                } else {
                    $ineqtype = $function[0][1];
                    $val = substr($function[0], 2);
                }
            }
        } else {
            $func = makepretty($function[0]);
            $evalfunc = makeMathFunction($func, "x", [], '', true);
            if ($evalfunc === false) {
                continue;
            }
        }

        //even though ASCIIsvg has a plot function, we'll calculate it here to hide the function

        $alt .= "Start Graph";
        $path = '';
        if (isset($function[1]) && $function[1] != '') {
            $path .= "stroke=\"{$function[1]}\";";
            $alt .= ", color {$function[1]}";
            $allcolors[] = $function[1];
        } else {
            $path .= "stroke=\"black\";";
            $alt .= ", color black";
            $allcolors[] = 'black';
        }
        if (isset($labelassoc[$k])) {
            $alt .= ', labeled ' . $labelassoc[$k];
        }
        if (isset($function[6]) && $function[6] != '') {
            $path .= "strokewidth=\"{$function[6]}\";";
        } else {
            $path .= "strokewidth=\"1\";";
        }
        if ($isineq && strlen($ineqtype) == 1) {  //is < or >
            $path .= "strokedasharray=\"5\";";
            $alt .= ", Dashed";
        } else if (isset($function[7]) && $function[7] != '') {
            if (is_numeric($function[7]) && $function[7] > 0) {
                $path .= "strokedasharray=\"{$function[7]}\";";
                $alt .= ", Dashed";
            } else if ($function[7] == "dash") {
                $path .= "strokedasharray=\"5\";";
                $alt .= ", Dashed";
            } else {
                $path .= "strokedasharray=\"none\";";
            }
        } else {
            $path .= "strokedasharray=\"none\";";
        }
        $path .= "fill=\"none\";";

        if ($isxequals) { //handle x-equals case separately
            if (isset($function[2]) && $function[2] != '') {
                $thisymin = $function[2];
            } else {
                $thisymin = $yymin;
            }
            if (isset($function[3]) && $function[3] != '') {
                $thisymax = $function[3];
            } else {
                $thisymax = $yymax;
            }
            $alt .= "<table class=stats><thead><tr><th>$xvar</th><th>$yvar</th></thead></tr><tbody>";
            $alt .= "<tr><td>$val</td><td>$thisymin</td></tr>";
            $alt .= "<tr><td>$val</td><td>$thisymax</td></tr>";
            $alt .= '</tbody></table>';
            $path .= "line([$val,$thisymin],[$val,$thisymax]);";
            if ($isineq) {
                $path .= "stroke=\"none\";strokedasharray=\"none\";";
                if (isset($function[1]) && ($function[1] == 'red' || $function[1] == 'green')) {
                    $path .= "fill=\"trans{$function[1]}\";";
                } else {
                    $path .= "fill=\"transblue\";";
                }
                if ($ineqtype[0] == '<') {
                    $path .= "rect([$xxmin,$thisymin],[$val,$thisymax]);";
                    $alt .= "Shaded left";
                } else {
                    $path .= "rect([$val,$thisymin],[$xxmax,$thisymax]);";
                    $alt .= "Shaded right";
                }
            }
            $commands .= $path;
            continue;
        }
        $avoid = array();
        $domainlimited = false;
        if (isset($function[2]) && $function[2] != '') {
            $xmin = evalbasic($function[2], true, true);
            $domainlimited = true;
            if (!is_numeric($xmin)) {
                echo "Invalid function xmin $xmin";
                continue;
            }
        } else {
            $xmin = $winxmin;
        }
        if (isset($function[3]) && $function[3] != '') {
            $xmaxarr = explode('!', $function[3]);
            if ($xmaxarr[0] != '') {
                $xmax = evalbasic($xmaxarr[0], true, true);
            } else {
                $xmax = $winxmax;
            }
            if (!is_numeric($xmax)) {
                echo "Invalid function xmax $xmax";
                continue;
            }
            if (count($xmaxarr) > 1) {
                $avoid = array_slice($xmaxarr, 1);
                sort($avoid);
            }
            $domainlimited = true;
        } else {
            $xmax = $winxmax;
        }

        if ($_SESSION['graphdisp'] == 0) {
            if (is_numeric($function[0]) && $noyaxis) {
                // TODO!  Not sure this is right. Maybe oversimplifies the graph in some cases
                if ($xmin < $winxmin) {
                    $xmin = 'negative infinity';
                }
                if ($xmax > $winxmax) {
                    $xmax = 'infinity';
                }
                $alt .= ". Line segment from $xmin to $xmax. ";
                continue;
            } else if (($xmax - $xmin > 2 && $xmax==(int)$xmax && $xmin==(int)$xmin) || $xmax == $xmin) {
                $dx = 1;
                $stopat = ($xmax - $xmin) + 1;
            } else {
                $dx = ($xmax - $xmin) / 10;
                $stopat = 11; //($domainlimited?10:11);
            }
            if ($xmax != $xmin) {
                $alt .= "<table class=stats><thead><tr><th>$xvar</th><th>$yvar</th></thead></tr><tbody>";
            } else {
                $alt .= '. ';
            }
        } else {
            $dx = ($xmax - $xmin + ($domainlimited ? 0 : 10 * ($xmax - $xmin) / $plotwidth)) / 100;
            $stopat = ($domainlimited ? 101 : 102);
            if ($xmax == $xmin) {
                $stopat = 1;
            }
        }
        if ($xmax == $xmin) {
            $xrnd = 6;
            $yrnd = 6;
        } else {
            $xrnd = max(0, intval(floor(-log10(abs($xmax - $xmin)) - 1e-12)) + 5);
            $yrnd = max(0, intval(floor(-log10(abs($ymax - $ymin)) - 1e-12)) + 5);
        }

        $lasty = 0;
        $lastl = 0;
        $px = null;
        $py = null;
        $pyorig = null;
        $pathstr = '';
        $firstpoint = false;
        $nextavoid = null;
        $fx = array();
        $fy = array();
        $yyaltmin = -1e10;//$yymin - .5 * ($yymax - $yymin);
        $yyaltmax = 1e10; //$yymax + .5 * ($yymax - $yymin);
        if (count($avoid) > 0) {
            $nextavoid = array_shift($avoid);
        }
        for ($i = 0; $i < $stopat; $i++) {
            if ($isparametric) {
                $t = $xmin + $dx * $i + 1E-10;
                if (in_array($t, $avoid)) {
                    continue;
                }
                $x = $evalxfunc(['t' => $t]);
                $yorig = $evalyfunc(['t' => $t]);
                if (isNaN($x) || isNaN($yorig)) {
                    continue;
                }
                $x = cleanround($x, $xrnd); //round(eval("return ($xfunc);"),3);
                $y = cleanround($yorig, $yrnd); //round(eval("return ($yfunc);"),3);
                if ($xmax != $xmin && $y > $yyaltmin && $y < $yyaltmax) {
                    $alt .= "<tr><td>$x</td><td>$y</td></tr>";
                }
            } else {
                $x = $xmin + $dx * $i + (($i < $stopat / 2) ? 1E-10 : -1E-10) - (($domainlimited || $_SESSION['graphdisp'] == 0) ? 0 : 5 * abs($xmax - $xmin) / $plotwidth);
                if (in_array($x, $avoid)) {
                    continue;
                }
                //echo $func.'<br/>';
                $yorig = $evalfunc(['x' => $x]);

                if (isNaN($yorig)) {
                    if ($lastl != 0) {
                        if ($py !== null) {
                            $pathstr .= ",[$px,$py]";
                        }
                        $pathstr .= ']);';
                        $lastl = 0;
                        $px = null;
                        $py = null;
                    }
                    continue;
                }
                $y = cleanround($yorig, $yrnd); //round(eval("return ($func);"),3);
                $x = cleanround($x, $xrnd);
                if ($xmax != $xmin && $y > $yyaltmin && $y < $yyaltmax) {
                    $alt .= "<tr><td>$x</td><td>$y</td></tr>";
                }
            }

            if ($i < 2 || $i == $stopat - 2) {
                $fx[$i] = $x;
                $fy[$i] = $y;
            }

            if (isNaN($y)) {
                continue;
            }

            if ($py === null) { //starting line

            } else if ($y > $yymax || $y < $yymin) { //going or still out of bounds
                if ($py <= $yymax && $py >= $yymin) { //going out

                    $origy = $y;
                    if ($isparametric) {
                        $y = $evalyfunc(['t' => $t - 1E-10]);
                        $tempy = $evalyfunc(['t' => $t - $dx / 100 - 1E-10]);
                        $temppy = $evalyfunc(['t' => $t - $dx + $dx / 100]);
                    } else {
                        $y = $evalfunc(['x' => $x - 1E-10]);
                        $tempy = $evalfunc(['x' => $x - $dx / 100 - 1E-10]);
                        $temppy = $evalfunc(['x' => $px + 1 / pow(10, $xrnd)]);
                    }

                    if ($temppy > $pyorig) { //if ($tempy<$y) { // going up
                        $iy = $yymax;
                        //if jumping from top of graph to bottom, change value
                        //for interpolation purposes
                        if ($y < $yymin) {
                            $y = $yymax + .5 * ($ymax - $ymin);
                        }
                    } else { //going down
                        $iy = $yymin;
                        if ($y > $yymax) {
                            $y = $yymin - .5 * ($ymax - $ymin);
                        }
                    }
                    $ix = round(($x - $px) * ($iy - $py) / ($y - $py) + $px, $xrnd);
                    if ($lastl == 0) {
                        $pathstr .= "path([";
                    } else {
                        $pathstr .= ",";
                    }
                    $pathstr .= "[$px,$py],[$ix,$iy]]);";
                    if ($y < $yymax && $y > $yymin) { // lost out of boundness. restore orig
                        $y = $origy;
                    }
                    $lastl = 0;
                } else { //still out

                }
            } else if ($py > $yymax || $py < $yymin) { //coming or staying in bounds?
                if ($y <= $yymax && $y >= $yymin) { //coming in
                    //need to determine which direction.  Let's calculate an extra value
                    //and need un-rounded y-value for comparison
                    if ($isparametric) {
                        $y = $evalyfunc(['t' => $t - 1E-10]);
                        $tempy = $evalyfunc(['t' => $t - $dx / 100 - 1E-10]);
                    } else {
                        $y = $evalfunc(['x' => $x - 1E-10]);
                        $tempy = $evalfunc(['x' => $x - $dx / 100 - 1E-10]);
                    }
                    if ($tempy > $y) { //seems to be coming down
                        $iy = $yymax;
                        if ($py < $yymin) {
                            $py = $yymax + .5 * ($ymax - $ymin);
                        }
                    } else { //coming from bottom
                        $iy = $yymin;
                        if ($py > $yymax) {
                            $py = $yymin - .5 * ($ymax - $ymin);
                        }
                    }
                    $ix = round(($x - $px) * ($iy - $py) / ($y - $py) + $px, $xrnd);
                    if ($lastl == 0) {
                        $pathstr .= "path([";
                    } else {
                        $pathstr .= ",";
                    }
                    $pathstr .= "[$ix,$iy]";
                    $lastl++;
                } else { //still out

                }
            } else { //all in
                if ($lastl == 0) {
                    $pathstr .= "path([";
                } else {
                    $pathstr .= ",";
                }
                $pathstr .= "[$px,$py]";
                $lastl++;
                if (
                    $nextavoid !== null && $x > $nextavoid &&
                    abs($y - $py) > .4 * ($yymax - $yymin)
                ) {
                    // graph jumps over domain gap; break it
                    $ix = ($px + $nextavoid) / 2;
                    $iy = ($py < .5 * ($yymax + $yymin)) ? $yymin : $yymax;
                    $pathstr .= ",[$ix,$iy]]);";
                    $ix = ($x + $nextavoid) / 2;
                    $iy = ($y < .5 * ($yymax + $yymin)) ? $yymin : $yymax;
                    $pathstr .= "path([[$ix,$iy]";
                    $lastl = 1;
                }
                if ($i == $stopat - 1 && $lastl > 0) {
                    $pathstr .= ",[$x,$y]";
                }
                if ($py < $absymin) {
                    $absymin = $py;
                }
                if ($py > $absymax) {
                    $absymax = $py;
                }
            }
            $px = $x;
            $py = $y;
            $pyorig = $yorig;

            if ($nextavoid !== null && $x > $nextavoid) {
                // grab next avoid point
                if (count($avoid) > 0) {
                    $nextavoid = array_shift($avoid);
                } else {
                    $nextavoid = null;
                }
            }
            /*if (abs($y-$lasty) > ($ymax-$ymin)) {
				if ($lastl > 1) { $pathstr .= ']);'; $lastl = 0;}
				$lasty = $y;
			} else {
				if ($lastl == 0) {$pathstr .= "path([";} else { $pathstr .= ",";}
				$pathstr .= "[$x,$y]";
				$lasty = $y;
				$lastl++;
				if ($y<$absymin) {
					$absymin = $y;
				}
				if ($y>$absymax) {
					$absymax = $y;
				}
			}
			*/
        }

        if ($lastl > 0) {
            $pathstr .= "]);";
        }
        $path .= $pathstr;
        if ($xmax != $xmin) {
            $alt .= "</tbody></table>\n";
        }

        if ($isineq) {
            // combine multiple paths together
            if ($domainlimited) {
                $thisxxmin = $xmin;
                $thisxxmax = $xmax;
            } else {
                $thisxxmin = $xxmin;
                $thisxxmax = $xxmax;
            }
            $pathstr = str_replace(']);path([', ',', $pathstr);
            $pathstr = substr($pathstr, 0, -3);
            preg_match('/^path\(\[\[(-?[\d\.]+),(-?[\d\.]+).*(-?[\d\.]+),(-?[\d\.]+)\]$/', $pathstr, $matches);
            if (isset($matches[4])) {
                $sig = ($thisxxmax - $thisxxmin) / 100;
                $ymid = ($yymax + $yymin) / 2;
                if ($ineqtype[0] == '<') {
                    if (abs($matches[3] - $thisxxmax) > $sig && $matches[4] > $ymid) {
                        $pathstr .= ",[$thisxxmax,$yymax]"; //need to add upper right corner
                    }
                    $pathstr .= ",[$thisxxmax,$yymin],[$thisxxmin,$yymin]";
                    if (abs($matches[1] - $thisxxmin) > $sig  && $matches[2] > $ymid) {
                        $pathstr .= ",[$thisxxmin,$yymax]"; //need to add upper left corner
                    }
                    $pathstr .= ']);';
                } else {
                    if (abs($matches[3] - $thisxxmax) > $sig && $matches[4] < $ymid) {
                        $pathstr .= ",[$thisxxmax,$yymin]"; //need to add lower right corner
                    }
                    $pathstr .= ",[$thisxxmax,$yymax],[$thisxxmin,$yymax]";
                    if (abs($matches[1] - $thisxxmin) > $sig  && $matches[2] < $ymid) {
                        $pathstr .= ",[$thisxxmin,$yymin]"; //need to add lower left corner
                    }
                    $pathstr .= ']);';
                }
                if (isset($function[1])) {
                    $path .= "fill=\"trans{$function[1]}\";";
                } else {
                    $path .= "fill=\"transblue\";";
                }
                $path .= "stroke=\"none\";strokedasharray=\"none\";$pathstr";
            } else {
                if (($ineqtype[0] == '<' && $y>$ymax) || 
                    ($ineqtype[0] == '>' && $y<$ymin)
                ) {
                    $path .= 'stroke="none";';
                    if (isset($function[1])) {
                        $path .= "fill=\"trans{$function[1]}\";";
                    } else {
                        $path .= "fill=\"transblue\";";
                    }
                    $path .= "rect([$thisxxmin,$yymin],[$thisxxmax,$yymax]);";
                }
            }
        }
        if (isset($function[5]) && $function[5] == 'open') {
            $path .= "dot([$x,$y],\"open\");";
            $alt .= "Open dot at ($x,$y). ";
        } else if (isset($function[5]) && $function[5] == 'closed') {
            $path .= "dot([$x,$y],\"closed\");";
            $alt .= "Closed dot at ($x,$y). ";
        } else if (isset($function[5]) && $function[5] == 'arrow' && isset($fx[$stopat - 2])) {
            $path .= "arrowhead([{$fx[$stopat - 2]},{$fy[$stopat - 2]}],[$x,$y]);";
            $alt .= "Arrow at ($x,$y). ";
        }
        if (isset($function[4]) && $function[4] == 'open' && isset($fx[0])) {
            $path .= "dot([{$fx[0]},{$fy[0]}],\"open\");";
            $alt .= "Open dot at ({$fx[0]},{$fy[0]}). ";
        } else if (isset($function[4]) && $function[4] == 'closed' && isset($fx[0])) {
            $path .= "dot([{$fx[0]},{$fy[0]}],\"closed\");";
            $alt .= "Closed dot at ({$fx[0]},{$fy[0]}). ";
        } else if (isset($function[4]) && $function[4] == 'arrow' && isset($fx[1])) {
            $path .= "arrowhead([{$fx[1]},{$fy[1]}],[{$fx[0]},{$fy[0]}]);";
            $alt .= "Arrow at ({$fx[0]},{$fy[0]}). ";
        }

        $commands .= $path;
    }
    if ($yminauto) {
        $ymin = max($absymin, $ymin);
    }
    if ($ymaxauto) {
        $ymax = min($absymax, $ymax);
    }
    $commands = "setBorder(5); initPicture({$winxmin},{$winxmax},{$ymin},{$ymax});" . $commands;
    if ($hasaxislabels) {
        $alt = "Graphing window shows horizontal axis" .
            ($xvar == '' ? '' : " labeled $xvar") . ": {$winxmin} to {$winxmax}" .
            ($noyaxis? "" : ", vertical axis" .
                ($yvar == '' ? '' : " labeled $yvar") . ": {$ymin} to {$ymax}") . '.&nbsp;' . $alt;
    } else {
        $alt = "Graphing window shows horizontal axis: {$winxmin} to {$winxmax}" . 
            ($noyaxis?"":", vertical axis: {$ymin} to {$ymax}.") . '.&nbsp;' . $alt;
    }

    if ($_SESSION['graphdisp'] == 0) {
        if (count(array_unique($allcolors)) == 1) {
            $alt = str_replace(', color ' . $allcolors[0], '', $alt);
        }
        return ($globalalt == '') ? $alt : $globalalt;
    } else {
        return "<embed type='image/svg+xml' align='middle' width='$plotwidth' height='$plotheight' script='$commands' />\n";
    }
}

function addplotborder($plot, $left, $bottom = 5, $right = 5, $top = 5) {
    return preg_replace("/setBorder\(.*?\);/", "setBorder($left,$bottom,$right,$top);", $plot);
    //return str_replace("setBorder(5)","setBorder($left,$bottom,$right,$top)",$plot);

}

function replacealttext($plot, $alttext) {
    if ($_SESSION['graphdisp'] == 0) {
        return $alttext;
    } else {
        if (strpos($plot, 'alt="') !== false) { //replace
            $plot = preg_replace('/alt="[^"]*"/', 'alt="' . Sanitize::encodeStringForDisplay($alttext) . '"', $plot);
        } else { //add
            $plot = preg_replace('/(\/?>)/', ' alt="' . Sanitize::encodeStringForDisplay($alttext) . '" $1', $plot);
        }
        return $plot;
    }
}

function addlabel($plot, $x, $y, $lbl, $color = "black", $loc = "", $angle = 0, $size = 0) {
    if ($_SESSION['graphdisp'] == 0) {
        return $plot .= "Label &quot;$lbl&quot; at ($x,$y). ";
    }
    $lbl = str_replace("'", '&apos;', $lbl);
    $lbl = str_replace('"', '\\"', $lbl);

    $outstr = 'fontfill="' . $color . '";';
    if ($size > 0) {
        $outstr .= 'fontsize=' . floatval($size) . ';';
    }
    $outstr .= "text([$x,$y],\"$lbl\"";
    if ($loc != "" || $angle != 0) {
        $outstr .= ",\"$loc\"";
    }
    if ($angle != 0) {
        $outstr .= ",\"$angle\"";
    }
    $outstr .= ');';
    $plot = str_replace("' />", "$outstr' />", $plot);
    return $plot;
}
function addlabelabs($plot, $x, $y, $lbl) {
    if (func_num_args() > 4) {
        $color = func_get_arg(4);
    } else {
        $color = "black";
    }
    if ($_SESSION['graphdisp'] == 0) {
        return $plot .= "Label &quot;$lbl&quot; at pixel coordinates ($x,$y).";
    }
    $lbl = str_replace("'", '&apos;', $lbl);
    $lbl = str_replace('"', '\\"', $lbl);
    if (func_num_args() > 6) {
        $loc = func_get_arg(5);
        $angle = func_get_arg(6);
        $plot = str_replace("' />", "fontfill=\"$color\";textabs([$x,$y],\"$lbl\",\"$loc\",\"$angle\");' />", $plot);
    } elseif (func_num_args() > 5) {
        $loc = func_get_arg(5);
        $plot = str_replace("' />", "fontfill=\"$color\";textabs([$x,$y],\"$lbl\",\"$loc\");' />", $plot);
    } else {
        $plot = str_replace("' />", "fontfill=\"$color\";textabs([$x,$y],\"$lbl\");' />", $plot);
    }
    return $plot;
}

function adddrawcommand($plot, $cmd) {
    $cmd = str_replace("'", '"', $cmd);
    return preg_replace("/'(\s+alt=\"[^\"]*\")?\s*\/>/", "$cmd'\\1 />", $plot);
}

function mergeplots($plota) {
    $n = func_num_args();
    if ($n == 1) {
        return $plota;
    }
    $plota = preg_replace('/<span.*?<\/span>/', '', $plota);
    for ($i = 1; $i < $n; $i++) {
        $plotb = func_get_arg($i);
        if ($_SESSION['graphdisp'] == 0) {
            $newtext = preg_replace('/^Graphs.*?y:.*?to.*?\.\s/', '', $plotb);
            $plota .= $newtext;
        } else {
            $plotb = preg_replace('/<span.*?<\/span>/', '', $plotb);
            $newcmds = preg_replace('/^.*?initPicture\(.*?\);\s*(axes\(.*?\);)?(.*?)\'\s*\/>.*$/', '$2', $plotb);
            $plota = str_replace("' />", trim($newcmds) . "' />", $plota);
        }
    }
    return $plota;
}

function addfractionaxislabels($plot, $step, $axis = "x") {
    if ($_SESSION['graphdisp'] == 0) {
        return $plot .= "Horizontal axis labels in steps of $step.";
    }
    if (strpos($step, '/') === false) {
        $num = $step;
        $den = 1;
    } else {
        list($num, $den) = explode('/', $step);
    }
    $ispi = false;
    if (strpos($num, 'pi') !== false) {
        $ispi = true;
        $num = str_replace(['*pi', 'pi'], '', $num);
        if ($num == '') {
            $num = 1;
        }
    }
    if (!is_numeric($num) || !is_numeric($den)) {
        echo 'invalid step in addfractionaxislabels';
        return $plot;
    }
    preg_match('/initPicture\(([\-\d\.]+),([\-\d\.]+),([\-\d\.]+),([\-\d\.]+)\)/', $plot, $matches);
    if (!isset($matches[4])) {
        echo "addfractionaxislabels: input must be a plot";
        return $plot;
    }
    if ($axis === 'x') {
        $min = $matches[1];
        $max = $matches[2];
        $crossrange = $matches[4] - $matches[3];
    } else if ($axis === 'y') {
        $min = $matches[3];
        $max = $matches[4];
        $crossrange = $matches[2] - $matches[1];
    }
    $stepn = $num / $den;
    if ($ispi) {
        $stepn *= M_PI;
    }
    if ($stepn == 0) {
        echo "error: bad step size on pilabels";
        return;
    }
    $tm = -.02 * $crossrange;
    $tx = .02 * $crossrange;
    $step = ceil($min / $stepn);
    $totstep = ceil(($max - $min) / $stepn);

    $outst = 'fontfill="black";strokewidth=0.5;stroke="black";';
    for ($i = 0; $i < $totstep; $i++) {
        $av = $step * $stepn;
        if (abs($av) < .01) {
            $step++;
            continue;
        }
        $g = gcd($step * $num, $den);
        $n = ($step * $num) / $g;
        $d = $den / $g;
        if ($ispi) {
            if ($n == 1) {
                $ld = '&pi;';
            } else if ($n == -1) {
                $ld = '-&pi;';
            } else {
                $ld = "$n&pi;";
            }
        } else {
            $ld = $n;
        }
        if ($d != 1) {
            $ld .= "/$d";
        }
        if ($axis === 'x') {
            $outst .= "line([$av,$tm],[$av,$tx]); text([$av,$tm],\"$ld\",\"below\");";
        } else if ($axis === 'y') {
            $outst .= "line([$tm,$av],[$tx,$av]); text([$tm,$av],\"$ld\",\"left\");";
        }
        $step++;
    }
    return str_replace("' />", "$outst' />", $plot);
}

function connectthedots($xarray, $yarray, $color = 'black', $thick = 1, $startdot = '', $enddot = '') {
    if (!is_array($xarray) || !is_array($yarray)) {
        echo "Error: x array and y array need to be arrays";
        return [];
    }
    if (count($xarray) != count($yarray)) {
        echo "Error: x array and y array need to have the same number of elements";
        return [];
    }
    $outarr = array();
    for ($i = 1; $i < count($xarray); $i++) {
        if ($i == 1) {
            $ed = $startdot;
        } else {
            $ed = '';
        }
        if ($i == count($xarray) - 1) {
            $sd = $enddot;
        } else {
            $sd = '';
        }
        if (!isset($xarray[$i]) || !isset($xarray[$i - 1]) || !isset($yarray[$i]) || !isset($yarray[$i - 1])) {
            echo "error: connectthedots needs arrays without missing elements";
            return [];
        }
        if ($xarray[$i - 1] == $xarray[$i]) {
            //vertical line
            $outarr[] = "[{$xarray[$i]},t],$color,{$yarray[$i]},{$yarray[$i - 1]},$sd,$ed,$thick";
        } else {
            $xd = $xarray[$i - 1] - $xarray[$i];
            $yd = $yarray[$i - 1] - $yarray[$i];
            $outarr[] = "[{$xarray[$i]}+t*($xd),{$yarray[$i]}+t*($yd)],$color,0,1,$sd,$ed,$thick";
        }
    }
    return $outarr;
}

function showasciisvg($script, $width = 200, $height = 200, $alt = null) {
    if (is_array($width)) {
        echo "second argument to showasciisvg should be an integer, not an array";
        $width = 200;
        $height = 200;
        $alt = '';
    }
    $script = preg_replace('~//.*?$~m', '', $script); //remove comments
    $script = preg_replace('~/\*.*?\*/~s', '', $script);
    $script = str_replace(["\r\n", "\r"], "\n", $script); // normalize line endings
    $instr = false;
    $brackdepth = 0;
    $parendepth = 0;
    $strchar = null;
    for ($i = 0; $i < strlen($script); $i++) {
        $c = $script[$i];
        if ($c == '"' || $c == "'") {
            if ($instr && $c === $strchar) {
                $instr = false;
            } else if (!$instr) {
                $instr = true;
                $strchar = $c;
            }
        } else if ($c == '[' && !$instr) {
            $brackdepth++;
        } else if ($c == '(' && !$instr) {
            $parendepth++;
        } else if ($c == ']' && !$instr) {
            $brackdepth--;
        } else if ($c == ')' && !$instr) {
            $parendepth--;
        } else if ($c == "\n" && !$instr && $brackdepth == 0 && $parendepth == 0) {
            $script[$i] = ';';
        } else if ($c == "\n") {
            $script[$i] = ' ';
        }
    }
    if ($alt === null) {
        $alt = "[Graphs generated by this script: $script]";
    }
    if ($_SESSION['graphdisp'] == 0) {
        return $alt;
    }
    $script = str_replace("'", '"', $script);
    $out = "<embed type='image/svg+xml' align='middle' width='$width' height='$height' script='$script' />";
    if (empty($GLOBALS['hide-sronly'])) {
        $out .= '<span class="sr-only">' . $alt . '</span>';
    }
    return $out;
}

function arraystodots($x, $y) {
    if (!is_array($x) || !is_array($y)) {
        echo "Error: inputs to arraystodots must be arrays";
        return [];
    }
    $out = array();
    for ($i = 0; $i < count($x); $i++) {
        if (is_array($x[$i]) || is_array($y[$i])) {
            echo "Error: inputs to arraystodots must be arrays of numbers";
            return [];
        }
        $out[] = $x[$i] . ',' . $y[$i];
    }
    return $out;
}

function arraystodoteqns($x, $y, $color = 'blue') {
    if (!is_array($x) || !is_array($y)) {
        echo "Error: inputs to arraystodoteqns must be arrays";
        return [];
    }
    if (count($x) != count($y)) {
        echo "arraystodoteqns x and y should have same length";
    }
    $out = array();
    for ($i = 0; $i < count($x); $i++) {
        if (is_array($x[$i]) || is_array($y[$i])) {
            echo "Error: inputs to arraystodoteqns must be arrays of numbers";
            return [];
        }
        if (isset($y[$i])) {
            $out[] = "dot," . $x[$i] . ',' . $y[$i] . ',closed,' . $color;
        }
    }
    return $out;
}

function textonimage() {
    $args = func_get_args();
    $img = array_shift($args);

    if (substr($img, 0, 4) == 'http') {
        $img = '<img src="' . Sanitize::encodeStringForDisplay($img) . '" alt="" />';
    }

    $out = '<div style="position: relative;" class="txtimgwrap">';
    $out .= '<div class="txtimgwrap" style="position:relative;top:0px;left:0px;">' . $img . '</div>';

    while (count($args) > 2) {
        $text = array_shift($args);
        $left = array_shift($args);
        $top = array_shift($args);
        $hidden = (strpos($text, '[AB') === false) ? 'aria-hidden=true' : '';
        if ($_SESSION['graphdisp'] == 0) {
            if ($hidden == '') {
                $out .= "<span>$text</span>";
            }
        } else {
            $out .= "<div $hidden style=\"position:absolute;top:{$top}px;left:{$left}px;\">$text</div>";
        }
    }
    $out .= '</div>';
    return $out;
}

function changeimagesize($img, $w, $h = '') {
    $img = preg_replace('/(width|height)\s*=\s*"?\d+"?/', '', $img);
    $img = preg_replace('/(width|height):\s*\w+;/', '', $img);
    $sizestr = 'width=' . Sanitize::onlyInt($w);
    if ($h != '') {
        $sizestr .= ' height=' . Sanitize::onlyInt($h);
    }
    $img = str_replace('<img', '<img ' . $sizestr, $img);
    return $img;
}

function addimageborder($img, $w = 1, $m = 0) {
    $style = 'border:' . intval($w) . 'px solid black;';
    if ($m > 0) {
        $style .= 'margin:' . intval($m) . 'px;';
    }
    if (strpos($img, 'style=') !== false) {
        $img = str_replace('style="', 'style="' . $style, $img);
    } else {
        $img = str_replace('<img ', '<img style="' . $style . '" ', $img);
    }
    return $img;
}

// private, used by invertplot
function invertpts ($m) {
    $pts = explode('],[', substr($m[2],1,-1));
    foreach ($pts as $k=>$pt) {
        $pt = explode(',', $pt);
        $pts[$k] = $pt[1].','.$pt[0];
    }
    if ($m[1] == 'path') {
        return 'path([[' . implode('],[', $pts) . ']]);';
    } else if ($m[1] == 'line') {
        return 'line([' . implode('],[', $pts) . ']);';
    }
}

function invertplot($plot) {
    $plot = preg_replace_callback('/(path)\(\[(.*?)\]\);/', 'invertpts', $plot);
    $plot = preg_replace_callback('/(line)\((.*?)\);/', 'invertpts', $plot);
    return $plot;
}

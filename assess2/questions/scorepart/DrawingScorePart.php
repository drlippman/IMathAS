<?php

namespace IMathAS\assess2\questions\scorepart;

require_once(__DIR__ . '/ScorePart.php');
require_once(__DIR__ . '/../models/ScorePartResult.php');

use IMathAS\assess2\questions\models\ScorePartResult;
use IMathAS\assess2\questions\models\ScoreQuestionParams;

class DrawingScorePart implements ScorePart
{
    private $scoreQuestionParams;

    public function __construct(ScoreQuestionParams $scoreQuestionParams)
    {
        $this->scoreQuestionParams = $scoreQuestionParams;
    }

    public function getResult(): ScorePartResult
    {
        global $mathfuncs;

        $scorePartResult = new ScorePartResult();

        $RND = $this->scoreQuestionParams->getRandWrapper();
        $options = $this->scoreQuestionParams->getVarsForScorePart();
        $qn = $this->scoreQuestionParams->getQuestionNumber();
        $givenans = $this->scoreQuestionParams->getGivenAnswer();
        $multi = $this->scoreQuestionParams->getIsMultiPartQuestion();
        $partnum = $this->scoreQuestionParams->getQuestionPartNumber();

        $defaultreltol = .0015;

        $optionkeys = ['grid', 'snaptogrid', 'answerformat', 'scoremethod', 'reltolerance', 'abstolerance'];
        foreach ($optionkeys as $optionkey) {
            ${$optionkey} = getOptionVal($options, $optionkey, $multi, $partnum);
        }
        $optionkeys = ['answers','partweights'];
        foreach ($optionkeys as $optionkey) {
            ${$optionkey} = getOptionVal($options, $optionkey, $multi, $partnum, 1);
        }

        if ($reltolerance === '') {
            if (isset($GLOBALS['CFG']['AMS']['defaultdrawtol'])) {
                $reltolerance =  $GLOBALS['CFG']['AMS']['defaultdrawtol'];
            } else {
                $reltolerance = 1;
            }
        }

        if ($multi) { $qn = ($qn+1)*1000+$partnum; }
        $scorePartResult->setLastAnswerAsGiven($givenans);

        if (!empty($scoremethod) && $scoremethod=='takeanything') {
          if ($givenans==';;;;;;;;') {
              $scorePartResult->setRawScore(0);
              return $scorePartResult;
          } else {
              $scorePartResult->setRawScore(1);
              return $scorePartResult;
          }
        }
        $imgborder = 5; $step = 5;
        if (empty($answerformat)) {
            $answerformat = array('line','dot','opendot');
        } else if (!is_array($answerformat)) {
            $answerformat = explode(',',$answerformat);
        }
        if ($answerformat[0]=='numberline') {
            $settings = array(-5,5,-0.5,0.5,1,0,300,50);
        } else {
            $settings = array(-5,5,-5,5,1,1,300,300);
        }
        if (!empty($grid)) {
            if (!is_array($grid)) {
                $grid = array_map('trim',explode(',',$grid));
            } else if (strpos($grid[0],',')!==false) {//forgot to set as multipart?
                $grid = array();
            }
            for ($i=0; $i<count($grid); $i++) {
                if ($grid[$i]!='') {
                    if (strpos($grid[$i],':')!==false) {
                        $pts = explode(':',$grid[$i]);
                        foreach ($pts as $k=>$v) {
                            $pts[$k] = evalbasic($v);
                        }
                        $settings[$i] = implode(':',$pts);
                    } else {
                        $settings[$i] = evalbasic($grid[$i]);
                    }
                }
            }
            if (strpos($settings[0],'0:')===0) {
                $settings[0] = substr($settings[0],2);
            }
            if (strpos($settings[2],'0:')===0) {
                $settings[2] = substr($settings[2],2);
            }
        }
        if ($answerformat[0]=='numberline') {
            $settings[2] = -0.5;
            $settings[3] = 0.5;
        }
        if (empty($snaptogrid)) {
    			$snaptogrid = 0;
        } else {
          $snapparts = explode(':', $snaptogrid);
          $snapparts = array_map('evalbasic', $snapparts);
          $snaptogrid = implode(':', $snapparts);
        }
        if ($snaptogrid !== 0) {
            list($newwidth,$newheight) = getsnapwidthheight($settings[0],$settings[1],$settings[2],$settings[3],$settings[6],$settings[7],$snaptogrid);
            if (abs($newwidth - $settings[6])/$settings[6]<.1) {
                $settings[6] = $newwidth;
            }
            if (abs($newheight- $settings[7])/$settings[7]<.1) {
                $settings[7] = $newheight;
            }
        }
        $pixelsperx = ($settings[6] - 2*$imgborder)/($settings[1]-$settings[0]);
        $pixelspery = ($settings[7] - 2*$imgborder)/($settings[3]-$settings[2]);

        $xtopix = my_create_function('$x',"return ((\$x - ({$settings[0]}))*($pixelsperx) + ($imgborder));");
        $ytopix = my_create_function('$y',"return (({$settings[7]}) - (\$y- ({$settings[2]}))*($pixelspery) - ($imgborder));");

        $anslines = array();
        $ansdots = array();
        $ansodots = array();
        $anslineptcnt = array();
        $types = array();
        $extrastuffpenalty = 0;
        $linepts = 0;
        if ((is_array($answers) && count($answers)==0) || (!is_array($answers) && $answers=='')) {
            if ($givenans==';;;;;;;;') {
                $scorePartResult->setRawScore(1);
                return $scorePartResult;
            } else {
                $scorePartResult->setRawScore(0);
                return $scorePartResult;
            }
        }
        if (!is_array($answers)) {
            settype($answers,"array");
        }
        $answers = array_map('clean', $answers);

        if ($answerformat[0]=="polygon" || $answerformat[0]=='closedpolygon') {
            foreach ($answers as $key=>$function) {
                $function = array_map('trim',explode(',',$function));
                $pixx = (evalbasic($function[0]) - $settings[0])*$pixelsperx + $imgborder;
                $pixy = $settings[7] - (evalbasic($function[1])-$settings[2])*$pixelspery - $imgborder;
                $ansdots[$key] = array($pixx,$pixy);
            }

            if (!empty($ansdots)) {
              for ($i=0; $i<count($ansdots)-1; $i++) {
                if (isset($ansdots[$i+1]) && abs($ansdots[$i][0] - $ansdots[$i+1][0])<1E-9 && abs($ansdots[$i][1] - $ansdots[$i+1][1])<1E-9) {
                    unset($ansdots[$i]);
                }
              }
              $ansdots = array_values($ansdots);
            }

            $isclosed = false;
            $stuclosed = false;
            if (abs($ansdots[0][0]-$ansdots[count($ansdots)-1][0])<.01 && abs($ansdots[0][1]-$ansdots[count($ansdots)-1][1])<.01) {
                $isclosed = true;
                array_pop($ansdots);
            }
            list($lines,$dots,$odots,$tplines,$ineqlines) = array_slice(explode(';;',$givenans),0,5);
            if ($lines=='') {
                $line = array();
                $extrapolys = 0;
            } else {
                $lines = explode(';',$lines);
                $extrapolys = count($lines)-1;
                $line = $lines[0]; //only use first line
                $line = explode('),(',substr($line,1,strlen($line)-2));
                foreach ($line as $j=>$pt) {
                    $line[$j] = explode(',',$pt);
                }
                if ($isclosed && ($line[0][0]-$line[count($line)-1][0])*($line[0][0]-$line[count($line)-1][0]) + ($line[0][1]-$line[count($line)-1][1])*($line[0][1]-$line[count($line)-1][1]) <=25*max(1,$reltolerance)) {
                    array_pop($line);
                    $stuclosed = true;
                }
            }

            $matchstu = array();
            for ($i=0; $i<count($ansdots); $i++) {
                for ($j=0;$j<count($line);$j++) {
                    if (($ansdots[$i][0]-$line[$j][0])*($ansdots[$i][0]-$line[$j][0]) + ($ansdots[$i][1]-$line[$j][1])*($ansdots[$i][1]-$line[$j][1]) <=25*max(1,$reltolerance)) {
                        $matchstu[$i] = $j;
                    }
                }
            }
            if ($isclosed && $stuclosed && isset($matchstu[0])) {
                $matchstu[count($ansdots)] = $matchstu[0];
            }

            $totaladj = 0;  $correctadj = 0;
            for ($i =0;$i<count($ansdots) - ($isclosed?0:1);$i++) {
                $totaladj++;
                /*if ($i==count($ansdots)-1) {
          if (!isset($matchstu[$i]) || !isset($matchstu[0])) {
            $diff = -1;
          } else {
            $diff = abs($matchstu[0]-$matchstu[$i]);
          }
        } else {
        */
                if (!isset($matchstu[$i]) || !isset($matchstu[$i+1])) {
                    $diff = -1;
                } else {
                    $diff = abs($matchstu[$i]-$matchstu[$i+1]);
                }

                //}
                if ($diff==1 || ($isclosed && $diff == count($matchstu)-2 && count($matchstu)!=0)) {
                    $correctadj++;
                }
            }
            //echo "Total adjacencies: $totaladj.  Correct: $correctadj <br/>";

            if ($isclosed && isset($matchstu[0])) {
                $vals = (count($matchstu)-1)/max(count($line),count($ansdots));
            } else {
                $vals = (count($matchstu))/max(count($line),count($ansdots));
            }

            $adjv = $correctadj/$totaladj;

            $totscore = ($vals+$adjv)/2;
            if ($extrapolys>0) {
                $totscore = $totscore/(1+$extrapolys);
            }
            //echo "Vals score: $vals, adj score: $adjv. </p>";

            if ($abstolerance !== '') {
                if ($totscore<$abstolerance) {
                    $scorePartResult->setRawScore(0);
                    return $scorePartResult;
                } else {
                    $scorePartResult->setRawScore(1);
                    return $scorePartResult;
                }
            } else {
                $scorePartResult->setRawScore($totscore);
                return $scorePartResult;
            }

        } else if ($answerformat[0]=="twopoint") {
            $anscircs = array();
            $ansparabs = array();
            $anshparabs = array();
            $ansabs = array();
            $anssqrts = array();
            $anscubics = array();
            $anscuberoots = array();
            $ansexps = array();
            $anslogs = array();
            $anscoss = array();
            $ansvecs = array();
            $ansrats = array();
            $ansellipses = array();
            $anshyperbolas = array();
            $epsilon = ($settings[1]-$settings[0])/499;
            $x0 = $settings[0] - 3*$epsilon;
            $x1 = 1/4*$settings[1] + 3/4*$settings[0] - $epsilon;
            $x2 = 1/2*$settings[1] + 1/2*$settings[0] + $epsilon;
            $x3 = 3/4*$settings[1] + 1/4*$settings[0] + 3*$epsilon;
            $x4 = $settings[1] + 5*$epsilon;
            $x0p = $xtopix($x0);
            $x1p = $xtopix($x1); //($x1 - $settings[0])*$pixelsperx + $imgborder;
            $x2p = $xtopix($x2); //($x2 - $settings[0])*$pixelsperx + $imgborder;
            $x3p = $xtopix($x3); //($x3 - $settings[0])*$pixelsperx + $imgborder;
            $x4p = $xtopix($x4); //($x4 - $settings[0])*$pixelsperx + $imgborder;
            $ymid = ($settings[2]+$settings[3])/2;
            $ymidp = $ytopix($ymid); //$settings[7] - ($ymid-$settings[2])*$pixelspery - $imgborder;
            $scoretype = array();
            $leftrightdir = '';
            foreach ($answers as $key=>$function) {
                if ($function=='') { continue; }
                $function = array_map('trim',explode(',',$function));
                if ($function[0] == 'optional') {
                    $scoretype[$key] = 1;
                    array_shift($function);
                } else {
                    $scoretype[$key] = 0;
                }
                if (count($function)==2 && ($function[1][0]==='<' || $function[1][0]==='>')) {
                    $leftrightdir = $function[1][0];
                    array_pop($function);
                }
                //curves: function
                //    function, xmin, xmax
                //dot:  x,y
                //  x,y,"closed" or "open"
                //form: function, color, xmin, xmax, startmaker, endmarker
                if (count($function)==2 || (count($function)==3 && ($function[2]=='open' || $function[2]=='closed'))) { //is dot
                    $pixx = (evalbasic($function[0]) - $settings[0])*$pixelsperx + $imgborder;
                    $pixy = $settings[7] - (evalbasic($function[1])-$settings[2])*$pixelspery - $imgborder;
                    if (count($function)==2 || $function[2]=='closed') {
                        $ansdots[$key] = array($pixx,$pixy);
                    } else {
                        $ansodots[$key] = array($pixx,$pixy);
                    }
                    continue;
                } else if ($function[0]=='vector') {
                    if (count($function)>4) { // form "vector, x_start, y_start, x_end, y_end"
                        $ansvecs[$key] = array('p', $xtopix($function[1]), $ytopix($function[2]), $xtopix($function[3]), $ytopix($function[4]));
                    } else if (count($function)>2) {  //form "vector, dx, dy"
                        $ansvecs[$key] = array('d', $function[1]*$pixelsperx, -1*$function[2]*$pixelspery);
                    }
                } else if ($function[0]=='circle') {  // form "circle,x_center,y_center,radius"
                    //$anscircs[$key] = array(($function[1] - $settings[0])*$pixelsperx + $imgborder,$settings[7] - ($function[2]-$settings[2])*$pixelspery - $imgborder,$function[3]*$pixelsperx);
                    $ansellipses[$key] = array(($function[1] - $settings[0])*$pixelsperx + $imgborder,$settings[7] - ($function[2]-$settings[2])*$pixelspery - $imgborder,$function[3]*$pixelsperx,$function[3]*$pixelsperx);
                } else if ($function[0]=='ellipse') {  //form ellipse,x_center,y_center,x_radius,y_radius
                    $ansellipses[$key] = array(($function[1] - $settings[0])*$pixelsperx + $imgborder,$settings[7] - ($function[2]-$settings[2])*$pixelspery - $imgborder,abs($function[3]*$pixelsperx),abs($function[4]*$pixelspery));
                } else if ($function[0]=='verthyperbola') {  //form verthyperbola,x_center,y_center,horiz "radius",vert "radius"
                    $anshyperbolas[$key] = array(($function[1] - $settings[0])*$pixelsperx + $imgborder,$settings[7] - ($function[2]-$settings[2])*$pixelspery - $imgborder,abs($function[4]*$pixelspery),abs($function[3]*$pixelsperx),'vert');
                } else if ($function[0]=='horizhyperbola') {  //form verthyperbola,x_center,y_center,horiz "radius",vert "radius"
                    $anshyperbolas[$key] = array(($function[1] - $settings[0])*$pixelsperx + $imgborder,$settings[7] - ($function[2]-$settings[2])*$pixelspery - $imgborder,abs($function[3]*$pixelsperx),abs($function[4]*$pixelspery),'horiz');
                } else if (substr($function[0],0,2)=='x=') {
                    if (strpos($function[0],'y')!==false && strpos($function[0],'^2')!==false) { //horiz parab
                        $y1 = 1/4*$settings[3] + 3/4*$settings[2];
                        $y2 = 1/2*$settings[3] + 1/2*$settings[2];
                        $y3 = 3/4*$settings[3] + 1/4*$settings[2];
                        $y1p = $ytopix($y1);
                        $y2p = $ytopix($y2);
                        $y3p = $ytopix($y3);
                        $func = makepretty(substr($function[0],2));
                        $func = makeMathFunction($func, 'y');
                        if ($func === false) { continue; }
                        $Lx1p = $xtopix(@$func(['y'=>$y1]));
                        $Lx2p = $xtopix(@$func(['y'=>$y2]));
                        $Lx3p = $xtopix(@$func(['y'=>$y3]));
                        $denom = ($y1p - $y2p)*($y1p - $y3p)*($y2p - $y3p);
                        $A = ($y3p * ($Lx2p - $Lx1p) + $y2p * ($Lx1p - $Lx3p) + $y1p * ($Lx3p - $Lx2p)) / $denom;
                        $B = ($y3p*$y3p * ($Lx1p - $Lx2p) + $y2p*$y2p * ($Lx3p - $Lx1p) + $y1p*$y1p * ($Lx2p - $Lx3p)) / $denom;
                        $C = ($y2p * $y3p * ($y2p - $y3p) * $Lx1p + $y3p * $y1p * ($y3p - $y1p) * $Lx2p + $y1p * $y2p * ($y1p - $y2p) * $Lx3p) / $denom;

                        $yv = -$B/(2*$A);
                        $xv = $C-$B*$B/(4*$A);
                        //TODO:  adjust 20px to be based on drawing window and grid
                        //   maybe ~1 grid units?
                        $yt = -$B/(2*$A)+20;
                        $xatyt = $A*$yt*$yt+$B*$yt+$C;
                        if (abs($xatyt - $xv)<20) {
                            $yatxt = sign($A)*sqrt(abs(20/$A))+$yv;
                            $anshparabs[$key] = array('y', $xv, $yv, $yatxt);
                        } else {
                            //use vertex and x value at y of vertex + 20 pixels
                            $anshparabs[$key] = array('x', $xv, $yv, $xatyt);
                        }
                    } else { //vertical line
                        $xp = $xtopix(substr($function[0],2));
                        if (count($function)==3) { //line segment or ray
                            if ($function[1]=='-oo') { //ray down
                                $y1p = $ytopix(floatval($function[2])-1);
                                $y2p = $ytopix(floatval($function[2]));
                                $ansvecs[$key] = array('r', $xp, $y2p, $xp, $y1p);
                            } else if ($function[2]=='oo') { //ray up
                                $y1p = $ytopix(floatval($function[1]));
                                $y2p = $ytopix(floatval($function[1])+1);
                                $ansvecs[$key] = array('r', $xp, $y1p, $xp, $y2p);
                            } else { //line seg
                                $y1p = $ytopix(floatval($function[1]));
                                $y2p = $ytopix(floatval($function[2]));
                                $ansvecs[$key] = array('ls', $xp, $y1p, $xp, $y2p);
                            }
                        } else {
                            //$anslines[$key] = array('x',10000,(substr($function[0],2)- $settings[0])*$pixelsperx + $imgborder );
                            $anslines[$key] = array('x',10000, $xp);
                        }
                    }
                } else if (count($function)==3) { //line segment or ray
                    $func = makeMathFunction(makepretty($function[0]), 'x');
                    if ($func === false) { continue; }
                    if ($function[1]=='-oo') { //ray to left
                        $y1p = $ytopix($func(['x'=>floatval($function[2])-1]));
                        $y2p = $ytopix($func(['x'=>floatval($function[2])]));
                        $ansvecs[$key] = array('r', $xtopix($function[2]), $y2p, $xtopix(floatval($function[2])-1), $y1p);
                    } else if ($function[2]=='oo') { //ray to right
                        $y1p = $ytopix($func(['x'=>floatval($function[1])]));
                        $y2p = $ytopix($func(['x'=>floatval($function[1])+1]));
                        $ansvecs[$key] = array('r', $xtopix($function[1]), $y1p, $xtopix(floatval($function[1])+1), $y2p);
                    } else { //line seg
                        if ($function[1]>$function[2]) {  //if xmin>xmax, swap
                            $tmp = $function[2];
                            $function[2] = $function[1];
                            $function[1] = $tmp;
                        }
                        $y1p = $ytopix($func(['x'=>floatval($function[1])]));
                        $y2p = $ytopix($func(['x'=>floatval($function[2])]));
                        $ansvecs[$key] = array('ls', $xtopix($function[1]), $y1p, $xtopix($function[2]), $y2p);
                    }
                } else {
                    $func = makeMathFunction(makepretty($function[0]), 'x');
                    if ($func === false) { continue; }

                    $y1 = @$func(['x'=>$x1]);
                    $y2 = @$func(['x'=>$x2]);
                    $y3 = @$func(['x'=>$x3]);

                    $y1p = $settings[7] - ($y1-$settings[2])*$pixelspery - $imgborder;
                    $y2p = $settings[7] - ($y2-$settings[2])*$pixelspery - $imgborder;
                    $y3p = $settings[7] - ($y3-$settings[2])*$pixelspery - $imgborder;
                    $yop = $imgborder + $settings[3]*$pixelspery;

                    if ($settings[0]<0 && $settings[1]>0) {
                        $xop = $xtopix(0);
                    } else {
                        $xop = $x2p;
                    }
                    if (($logloc = strpos($function[0],'log'))!==false ||
                        ($lnloc = strpos($function[0],'ln'))!==false) { //is log

                        $nestd = 0; $vertasy = 0;
                        $startloc = strpos($function[0],'(',$logloc!==false?$loglog:$lnloc);
                        if ($function[0][$startloc-1] == '_') {
                            // is parens for base; skip to next paren
                            $startloc = strpos($function[0],'(',$startloc+1);
                        }
                        for ($i = $startloc; $i<strlen($function[0]); $i++) {
                            if ($function[0][$i]=='(') {
                                $nestd++;
                            } else if ($function[0][$i]==')') {
                                $nestd--;
                                if ($nestd == 0 && $i>$startloc) {
                                    $loginside = substr($function[0], $startloc, $i-$startloc+1);
                                    if (strpos($loginside,'x')===false) { //found a log w/o variable
                                        //look for another one
                                        if (($logloc = strpos($function[0],'log', $i))!==false ||
                                            ($lnloc = strpos($function[0],'ln', $i))!==false) { //is another log
                                            $startloc = ($logloc!==false)?($logloc+3):($lnloc+2);
                                            continue;
                                        }
                                    }
                                    $inlogfunc = makeMathFunction(makepretty($loginside), 'x');
                                    if ($func === false) { continue; }
                                    //We're going to assume inside is linear
                                    //Calculate (0,y0), (1,y1).  m=(y1-y0), y=(y1-y0)x+y0
                                    //solve for when this is =0
                                    // x = -y0/(y1-y0)
                                    $inlogy0 = $inlogfunc(['x'=>0]);
                                    $inlogy1 = $inlogfunc(['x'=>1]);
                                    $vertasy = -$inlogy0/($inlogy1 - $inlogy0);
                                    break;
                                }
                            } else if ($nestd == 0) {
                                $startloc = $i+1; //reset start to handle log_2(x-5) and such
                            }
                        }

                        //treat like x=ab^y+vertasy
                        //is a pos or neg?
                        if ($x1<$vertasy) {  //x1 to the left of VA
                            if (!is_nan($y1)) {  //if y1 is defined, a is neg
                                $asign = -1;
                            } else {
                                $asign = 1;
                            }
                        } else {  //x1 is to the right of VA
                            if (!is_nan($y1)) { //if y1 is defined, a is pos
                                $asign = 1;
                            } else {
                                $asign = -1;
                            }
                        }
                        $xa = $vertasy + $asign*1;
                        $xb = $vertasy + $asign*2;
                        $ya = @$func(['x'=>$xa]);
                        $yb = @$func(['x'=>$xb]);
                        $xap = $xtopix($xa);
                        $xbp = $xtopix($xb);
                        $vertasyp = $xtopix($vertasy);
                        $yap = $ytopix($ya);
                        $ybp = $ytopix($yb);

                        /*old without shift
            //treat like x=ab^y
            if (!is_nan($y1)) {
              $yap = $y1p;
              $xap = $x1p;
              $yb = @$func($x1-1);
              $xbp = $x1p - $pixelsperx;
            } else {
              $yap = $y3p;
              $xap = $x3p;
              $yb = @$func($x3+1);
              $xbp = $x3p + $pixelsperx;
            }
            $ybp = $settings[7] - ($yb-$settings[2])*$pixelspery - $imgborder;
            */
                        if ($ybp>$yap) {
                            $base = safepow(($vertasyp-$xbp)/($vertasyp-$xap), 1/($ybp-$yap));
                        } else {
                            $base = safepow(($vertasyp-$xap)/($vertasyp-$xbp), 1/($yap-$ybp));
                        }
                        $str = ($vertasyp-$xbp)/safepow($base,$ybp-$yop);

                        $anslogs[$key] = array($str, $base, $vertasyp);

                    } else if (strpos($function[0],'abs')!==false) { //is abs
                        $y0 = $func(['x'=>$x0]);
                        $y4 = $func(['x'=>$x4]);
                        $y0p = $settings[7] - ($y0-$settings[2])*$pixelspery - $imgborder;
                        $y4p = $settings[7] - ($y4-$settings[2])*$pixelspery - $imgborder;
                        if (abs(($y2-$y1)-($y1-$y0))<1e-9) { //if first 3 points are colinear
                            $slope = ($y2p-$y1p)/($x2p-$x1p);
                        } else if (abs(($y4-$y3)-($y3-$y2))<1e-9) { //if last 3 points are colinear
                            $slope = -1*($y4p-$y3p)/($x4p-$x3p);  //mult by -1 to get slope on left
                        }
                        if ($slope==0) {
                            $anslines[$key] = array('y',$slope,$y2p);
                        } else {
                            $xip = ($slope*($x4p+$x0p)+$y4p-$y0p)/(2*$slope);  //x value of "vertex"
                            $ansabs[$key] = array($xip,$slope*($xip-$x0p)+$y0p, $slope);
                        }
                    } else if (($p = strpos($function[0],'sqrt('))!==false) { //is sqrt
                        $nested = 1;
                        for ($i=$p+5;$i<strlen($function[0]);$i++) {
                            if ($function[0][$i]=='(') {$nested++;}
                            else if ($function[0][$i]==')') {$nested--;}
                            if ($nested==0) {break;}
                        }
                        if ($nested==0) {
                            $infunc = makepretty(substr($function[0],$p+5,$i-$p-5));
                            $infunc = makeMathFunction($infunc, 'x');
                            if ($func === false) { continue; }
                            $y0 = $infunc(['x'=>0]);
                            $y1 = $infunc(['x'=>1]);
                            $xint = -$y0/($y1-$y0);
                            $xintp = ($xint - $settings[0])*$pixelsperx + $imgborder;
                            $yint = $func(['x'=>$xint]);
                            $yintp = $settings[7] - ($yint-$settings[2])*$pixelspery - $imgborder;
                            $flip = ($y1>$y0)?1:-1;
                            $secx = $xint + ($x4-$x0)/5*$flip;  //over 1/5 of grid width
                            $secy = $func(['x'=>$secx]);
                            $secyp = $settings[7] - ($secy-$settings[2])*$pixelspery - $imgborder;
                            $anssqrts[$key] = array($xintp,$yintp,$secyp,$flip);
                        }
                    } else if (($p = strpos($function[0],'cos'))!==false || ($q = strpos($function[0],'sin'))!==false) { //is sin/cos
                        if ($p===false) { $p = $q;}
                        $nested = 1;
                        for ($i=$p+4;$i<strlen($function[0]);$i++) {
                            if ($function[0][$i]=='(') {$nested++;}
                            else if ($function[0][$i]==')') {$nested--;}
                            if ($nested==0) {break;}
                        }
                        if ($nested==0) {
                            $infunc = makepretty(substr($function[0],$p+4,$i-$p-4));
                            $infunc = makeMathFunction($infunc, 'x');
                            if ($func === false) { continue; }
                            $y0 = $infunc(['x'=>0]);
                            $y1 = $infunc(['x'=>1]);
                            $period = 2*M_PI/($y1-$y0); //slope of inside function
                            $xint = -$y0/($y1-$y0);
                            if (strpos($function[0],'sin')!==false) {
                                $xint += $period/4;
                            }
                            $secx = $xint + $period/2;
                            $xintp = ($xint - $settings[0])*$pixelsperx + $imgborder;
                            $secxp = ($secx - $settings[0])*$pixelsperx + $imgborder;
                            $yint = $func(['x'=>$xint]);
                            $yintp = $settings[7] - ($yint-$settings[2])*$pixelspery - $imgborder;
                            $secy = $func(['x'=>$secx]);
                            $secyp = $settings[7] - ($secy-$settings[2])*$pixelspery - $imgborder;
                            if ($yintp>$secyp) {
                                $anscoss[$key] = array($xintp,$secxp,$yintp,$secyp);
                            } else {
                                $anscoss[$key] = array($secxp,$xintp,$secyp,$yintp);
                            }
                        }
                    } else if (strpos($function[0],'^3')!==false) { //cubic
                        $y4p = $ytopix($func(['x'=>$x4]));
                        $a1 = safepow($x3p,3)-2*safepow($x2p,3)+safepow($x1p,3);
                        $a2 = safepow($x4p,3)-2*safepow($x3p,3)+safepow($x2p,3);
                        $b1 = safepow($x3p,2)-2*safepow($x2p,2)+safepow($x1p,2);
                        $b2 = safepow($x4p,2)-2*safepow($x3p,2)+safepow($x2p,2);
                        $c1 = $y3p - 2*$y2p + $y1p;
                        $c2 = $y4p - 2*$y3p + $y2p;
                        $a = ($c1*$b2 - $c2*$b1)/($a1*$b2-$a2*$b1);
                        $b = ($a1*$c2 - $a2*$c1)/($a1*$b2-$a2*$b1);
                        $h = -$b/(3*$a);
                        $str = ($y2p - $y1p)/(safepow($x2p-$h,3)-safepow($x1p-$h,3));
                        $k = $y2p - $str*safepow($x2p-$h,3);
                        $anscubics[$key] = array($h, $k, safepow($str,1/3));
                    } else if (strpos($function[0],'root(3)')!==false || strpos($function[0],'^(1/3)')!==false) { //cube root
                        //y=str*cuberoot(x-h)^3+k is equiv to x=(1/str^3)(y-k)^3+h
                        $y4p = $ytopix($func(['x'=>$x4]));
                        $a1 = safepow($y3p,2)-safepow($y1p,2)+ $y3p*$y2p - $y1p*$y2p;
                        $a2 = safepow($y4p,2)-safepow($y2p,2)+ $y4p*$y3p - $y2p*$y3p;
                        $b1 = $y3p - $y1p;
                        $b2 = $y4p - $y2p;
                        $c1 = ($x3p - $x2p)/($y3p - $y2p) - ($x2p - $x1p)/($y2p - $y1p);
                        $c2 = ($x4p - $x3p)/($y4p - $y3p) - ($x3p - $x2p)/($y3p - $y2p);
                        $a = ($c1*$b2 - $c2*$b1)/($a1*$b2-$a2*$b1);
                        $b = ($a1*$c2 - $a2*$c1)/($a1*$b2-$a2*$b1);
                        $k = -$b/(3*$a);
                        $invstr = ($x2p - $x1p)/(safepow($y2p-$k,3)-safepow($y1p-$k,3));
                        $h = $x2p - $invstr*safepow($y2p-$k,3);
                        //$str = 1/safepow($invstr,1/3);
                        $anscuberoots[$key] = array($h, $k, 1/$invstr);
                    } else if (preg_match('/\^[^2]/',$function[0])) { //exponential
                        /*
            To do general exponential, we'll need 3 points.
            Need to solve y = ab^x + c for a, b, c
            If x1, x2, and x3 are equally spaced, then
            b = ((y3-y2)/(y2-y1))^(1/(x3-x2))

            y1 = ab^x1 + c,  y2 = ab^x2 + c
            y1 - ab^x1 = y2 - ab^x2
            a(b^x2 - b^x1) = y2 - y1
            a = (y2 - y1)/(b^x2 - b^x1)
            c = y1 - a*b^x1

            y = ab^x
            */

                        $base = safepow(($y3p-$y2p)/($y2p-$y1p), 1/($x3p-$x2p));
                        $str = ($y1p - $y2p)/(safepow($base, $x2p-$xop) - safepow($base, $x1p-$xop));
                        $asy = $y1p + $str*safepow($base, $x1p-$xop);

                        /* old version
            $base = safepow(($yop-$y3p)/($yop-$y1p), 1/($x3p-$x1p));
            $str = ($yop-$y3p)/safepow($base,$x3p-$xop);
            */

                        $ansexps[$key] = array($str, $base, $asy);

                    } else if (strpos($function[0],'/x')!==false || preg_match('|/\s*\([^\)]*x|', $function[0])) {
                        $h = ($x1*$x2*$y1-$x1*$x2*$y2-$x1*$x3*$y1+$x1*$x3*$y3+$x2*$x3*$y2-$x2*$x3*$y3)/(-$x1*$y2+$x1*$y3+$x2*$y1-$x2*$y3-$x3*$y1+$x3*$y2);
                        $k = (($x1*$y1-$x2*$y2)-$h*($y1-$y2))/($x1-$x2);
                        $c = ($y1-$k)*($x1-$h) * $pixelspery/$pixelsperx; // adjust for scaling

                        $hp = ($h - $settings[0])*$pixelsperx + $imgborder;
                        $kp = $settings[7] - ($k-$settings[2])*$pixelspery - $imgborder;
                        //eval at point on graph closest to (h,k), at h+sqrt(c)
                        $np = $settings[7] - (@$func(['x'=>$h+sqrt(abs($c))])-$settings[2])*$pixelspery - $imgborder;
                        $ansrats[$key] = array($hp,$kp,$np);

                    } else if (abs(($y3-$y2)-($y2-$y1))<1e-9) {
                        //colinear
                        $slope = ($y2p-$y1p)/($x2p-$x1p);
                        if (abs($slope)>1.4) {
                            //use x value at ymid
                            $anslines[$key] = array('x',$slope,$x1p+($ymidp-$y1p)/$slope);
                        } else {
                            //use y value at x2
                            $anslines[$key] = array('y',$slope,$y2p);
                        }
                    } else {
                        //assume parabolic for now
                        $denom = ($x1p - $x2p)*($x1p - $x3p)*($x2p - $x3p);
                        $A = ($x3p * ($y2p - $y1p) + $x2p * ($y1p - $y3p) + $x1p * ($y3p - $y2p)) / $denom;
                        $B = ($x3p*$x3p * ($y1p - $y2p) + $x2p*$x2p * ($y3p - $y1p) + $x1p*$x1p * ($y2p - $y3p)) / $denom;
                        $C = ($x2p * $x3p * ($x2p - $x3p) * $y1p + $x3p * $x1p * ($x3p - $x1p) * $y2p + $x1p * $x2p * ($x1p - $x2p) * $y3p) / $denom;
                        $xv = -$B/(2*$A);
                        $yv = $C-$B*$B/(4*$A);
                        //TODO:  adjust 20px to be based on drawing window and grid
                        //   maybe ~1 grid units?
                        $xt = -$B/(2*$A)+20;
                        $yatxt = $A*$xt*$xt+$B*$xt+$C;
                        if (abs($yatxt - $yv)<20) {
                            $xatyt = sign($A)*sqrt(abs(20/$A))+$xv;
                            $ansparabs[$key] = array('x', $xv, $yv, $xatyt, $leftrightdir);
                        } else {
                            //use vertex and y value at x of vertex + 20 pixels
                            $ansparabs[$key] = array('y', $xv, $yv, $yatxt, $leftrightdir);
                        }
                        //**finish me!!
                    }
                }
            }
            list($lines,$dots,$odots,$tplines,$ineqlines) = array_slice(explode(';;',$givenans),0,5);
            $lines = array();
            $parabs = array();
            $hparabs = array();
            $circs = array();
            $abs = array();
            $sqrts = array();
            $coss = array();
            $exps = array();
            $logs = array();
            $vecs = array();
            $rats = array();
            $ellipses = array();
            $hyperbolas = array();
            $cubics = array();
            $cuberoots = array();
            if ($tplines=='') {
                $tplines = array();
            } else {
                $tplines = explode('),(', substr($tplines,1,strlen($tplines)-2));
                foreach ($tplines as $k=>$val) {
                    $pts = explode(',',$val);
                    if ($pts[1]==$pts[3] && $pts[2]==$pts[4]) {
                      //the points are the same; skip it
                      unset($tplines[$k]);
                      continue;
                    }
                    if ($pts[0]==5) {
                        //line
                        if ($pts[3]==$pts[1]) {
                            $lines[] = array('x',10000,$pts[1]);
                        } else {
                            $slope = ($pts[4]-$pts[2])/($pts[3]-$pts[1]);
                            if (abs($slope)>100) {$slope = 10000;}
                            if (abs($slope)>1) {
                                $lines[] = array('x',$slope,$pts[1]+($ymidp-$pts[2])/$slope,$pts[2]+($x2p-$pts[1])*$slope);
                            } else {
                                $lines[] = array('y',$slope,$pts[2]+($x2p-$pts[1])*$slope);
                            }
                        }
                    } else if ($pts[0]==5.2) {
                        $vecs[] = array($pts[1],$pts[2],$pts[3],$pts[4],'r');
                    } else if ($pts[0]==5.3) {
                        $vecs[] = array($pts[1],$pts[2],$pts[3],$pts[4],'ls');
                    } else if ($pts[0]==5.4) {
                        $vecs[] = array($pts[1],$pts[2],$pts[3],$pts[4],'v');
                    } else if ($pts[0]==6 || $pts[0] == 6.2) {
                        $leftrightdir = '';
                        if ($pts[0] == 6.2) {
                            $leftrightdir = ($pts[3] < $pts[1]) ? '<' : '>';
                        }
                        //parab
                        //for y at x+20, y=a(x-h)^2+k is y=a*20^2+k
                        //for x at y+-20, y=a(x-h)^2+k
                        //                20 = a(x-h)^2
                        //                abs(20/a) = (x-h)^2
                        if ($pts[4]==$pts[2]) {
                            $lines[] = array('y',0,$pts[4]);
                        } else if ($pts[3]!=$pts[1]) {
                            $a = ($pts[4]-$pts[2])/(($pts[3]-$pts[1])*($pts[3]-$pts[1]));
                            $y = $pts[2]+$a*400;
                            $x = $pts[1]+sign($a)*sqrt(abs(20/$a));
                            $parabs[] = array($pts[1],$pts[2],$y,$x,$leftrightdir);
                        }
                    } else if ($pts[0]==6.1) {
                        //same as above, but swap x and y
                        if ($pts[3]==$pts[1]) {
                            $lines[] = array('x',0,$pts[3]);
                        } else if ($pts[4]!=$pts[2]) {
                            $a = ($pts[3]-$pts[1])/(($pts[4]-$pts[2])*($pts[4]-$pts[2]));
                            $x = $pts[1]+$a*400;
                            $y = $pts[2]+sign($a)*sqrt(abs(20/$a));
                            $hparabs[] = array($pts[1],$pts[2],$y,$x);
                        }
                    } else if ($pts[0]==6.5) {//sqrt
                        $flip = ($pts[3] < $pts[1])?-1:1;
                        $stretch = ($pts[4] - $pts[2])/sqrt($flip*($pts[3]-$pts[1]));

                        $secxp = $pts[1] + ($x4p-$x0p)/5*$flip;  //over 1/5 of grid width
                        $secyp = $stretch*sqrt($flip*($secxp - $pts[1]))+($pts[2]);
                        $sqrts[] = array($pts[1],$pts[2],$secyp,$flip);
                    } else if ($pts[0]==6.3) {
                        //cubic
                        if ($pts[4]==$pts[2]) {
                            $lines[] = array('y',0,$pts[4]);
                        } else if ($pts[3]!=$pts[1]) {
                            //this is the cube root of the stretch factor
                            $a = safepow($pts[4]-$pts[2], 1/3)/($pts[3]-$pts[1]);
                            $cubics[] = array($pts[1],$pts[2], $a);
                        }
                    } else if ($pts[0]==6.6) {
                        //cube root
                        if ($pts[4]==$pts[2]) {
                            $lines[] = array('y',0,$pts[4]);
                        } else if ($pts[3]!=$pts[1]) {
                            $a = safepow($pts[4]-$pts[2],3)/($pts[3]-$pts[1]);
                            $cuberoots[] = array($pts[1],$pts[2],$a);
                        }
                    } else if ($pts[0]==7) {
                        //circle
                        $rad = sqrt(($pts[3]-$pts[1])*($pts[3]-$pts[1]) + ($pts[4]-$pts[2])*($pts[4]-$pts[2]));
                        //$circs[] = array($pts[1],$pts[2],$rad);
                        $ellipses[] = array($pts[1],$pts[2],$rad,$rad);
                    } else if ($pts[0]==7.2) {
                        //ellipse
                        $ellipses[] = array($pts[1],$pts[2],abs($pts[3]-$pts[1]),abs($pts[4]-$pts[2]));
                    } else if ($pts[0]==7.4) {
                        //vert hyperbola
                        $hyperbolas[] = array($pts[1],$pts[2],abs($pts[3]-$pts[1]),abs($pts[4]-$pts[2]),'vert');
                    } else if ($pts[0]==7.5) {
                        //horiz hyperbola
                        $hyperbolas[] = array($pts[1],$pts[2],abs($pts[3]-$pts[1]),abs($pts[4]-$pts[2]),'horiz');
                    } else if ($pts[0]==8) {
                        //abs
                        if ($pts[1]==$pts[3]) {
                            if ($pts[4]>$pts[2]) {
                                $slope = -10000000000;
                            } else {
                                $slope = 10000000000;
                            }
                        } else {
                            $slope = ($pts[4]-$pts[2])/($pts[3]-$pts[1]);
                            if ($pts[3]>$pts[1]) {//we just found slope on right, so reverse for slope on left
                                $slope *= -1;
                            }
                        }
                        $abs[] = array($pts[1],$pts[2], $slope);
                    } else if ($pts[0]==8.3 || $pts[0]==8.5) {
                        if ($pts[0]==8.3) {
                            $horizasy = $yop;
                            $adjy2 = $horizasy - $pts[4];
                            $adjy1 = $horizasy - $pts[2];
                            $Lx1p = $pts[1];
                            $Lx2p = $pts[3];
                        } else if ($pts[0]==8.5) {
                            $horizasy = $pts[2];
                            $adjy2 = $horizasy - $pts[6];
                            $adjy1 = $horizasy - $pts[4];
                            $Lx1p = $pts[3];
                            $Lx2p = $pts[5];
                        }

                        if ($adjy1*$adjy2>0 && $Lx1p!=$Lx2p) {
                            $base = safepow($adjy2/$adjy1,1/($Lx2p-$Lx1p));
                            if (abs($Lx1p-$xop)<abs($Lx2p-$xop)) {
                                $str = $adjy1/safepow($base,$Lx1p-$xop);
                            } else {
                                $str = $adjy2/safepow($base,$Lx2p-$xop);
                            }
                            //$exps[] = array($str,$base);
                            $exps[] = array($Lx1p-$xop, $adjy1, $Lx2p-$xop, $adjy2, $base, $horizasy);
                        }
                    } else if ($pts[0]==8.4 || $pts[0]==8.6) {
                        if ($pts[0]==8.4) {
                            $vertasy = $xop;
                            $adjx2 = $vertasy - $pts[3];
                            $adjx1 = $vertasy - $pts[1];
                            $Ly1p = $pts[2];
                            $Ly2p = $pts[4];
                        } else if ($pts[0]==8.6) {
                            $vertasy = $pts[1];
                            $adjx2 = $vertasy - $pts[5];
                            $adjx1 = $vertasy - $pts[3];
                            $Ly1p = $pts[4];
                            $Ly2p = $pts[6];
                        }
                        if ($adjx1*$adjx2>0 && $Ly1p!=$Ly2p) {
                            $base = safepow($adjx2/$adjx1,1/($Ly2p-$Ly1p));
                            if (abs($pts[2]-$yop)<abs($Ly2p-$yop)) {
                                $str = $adjx1/safepow($base,$Ly1p-$yop);
                            } else {
                                $str = $adjx2/safepow($base,$Ly2p-$yop);
                            }
                            $logs[] = array($Ly1p-$yop, $adjx1, $Ly2p-$yop, $adjx2, $base, $vertasy);
                        }
                    } else if ($pts[0]==8.2) { //rational
                        if ($pts[1]!=$pts[3] && $pts[2]!=$pts[4]) {
                            $stretch = ($pts[3]-$pts[1])*($pts[4]-$pts[2]);
                            $yp = $pts[2]+(($stretch>0)?1:-1)*sqrt(abs($stretch));

                            $rats[] = array($pts[1],$pts[2],$yp);
                        }
                    } else if ($pts[0]==9 || $pts[0]==9.1) {
                        if ($pts[0]==9.1) { // sine, convert to cos points
                            $pts[1] -= ($pts[3] - $pts[1]);
                            $pts[2] -= ($pts[4] - $pts[2]);
                        }
                        if ($pts[4]>$pts[2]) {
                            $coss[] = array($pts[3],$pts[1],$pts[4],$pts[2]);
                        } else {
                            $coss[] = array($pts[1],$pts[3],$pts[2],$pts[4]);
                        }
                    }
                }
            }
            if ($dots=='') {
                $dots = array();
            } else {
                $dots = explode('),(', substr($dots,1,strlen($dots)-2));
                foreach ($dots as $k=>$pt) {
                    $dots[$k] = explode(',',$pt);
                }
            }
            if ($odots=='') {
                $odots = array();
            } else {
                $odots = explode('),(', substr($odots,1,strlen($odots)-2));
                foreach ($odots as $k=>$pt) {
                    $odots[$k] = explode(',',$pt);
                }
            }

            $scores = array(array(), array());

            foreach ($ansdots as $key=>$ansdot) {
                $scores[$scoretype[$key]][$key] = 0;
                for ($i=0; $i<count($dots); $i++) {
                    if (($dots[$i][0]-$ansdot[0])*($dots[$i][0]-$ansdot[0]) + ($dots[$i][1]-$ansdot[1])*($dots[$i][1]-$ansdot[1]) <= 25*max(1,$reltolerance)) {
                        $scores[$scoretype[$key]][$key] = 1;
                        break;
                    }
                }
            }
            foreach ($ansodots as $key=>$ansodot) {
                $scores[$scoretype[$key]][$key] = 0;
                for ($i=0; $i<count($odots); $i++) {
                    if (($odots[$i][0]-$ansodot[0])*($odots[$i][0]-$ansodot[0]) + ($odots[$i][1]-$ansodot[1])*($odots[$i][1]-$ansodot[1]) <= 25*max(1,$reltolerance)) {
                        $scores[$scoretype[$key]][$key] = 1;
                        break;
                    }
                }
            }
            $deftol = .1;
            $defpttol = 5;

            $usedline = [];
            foreach ($anslines as $key=>$ansline) {
                $scores[$scoretype[$key]][$key] = 0;
                for ($i=0; $i<count($lines); $i++) {
                    if (!empty($usedline[$i])) { continue; }
                    //check slope
                    $toladj = pow(10,-1-6*abs($ansline[1]));
                    if (abs($ansline[1]-$lines[$i][1])/(abs($ansline[1])+$toladj)>$deftol*$reltolerance) {
                        continue;
                    }
                    if ($ansline[0]!=$lines[$i][0]) {
                        if (abs(abs($ansline[1])-1)<.4) {
                            //check intercept
                            if (abs($ansline[2]-$lines[$i][3])>$defpttol*$reltolerance) {
                                continue;
                            }
                        } else {
                            continue;
                        }
                    } else {
                        if (abs($ansline[2]-$lines[$i][2])>$defpttol*$reltolerance) {
                            continue;
                        }
                    }
                    $usedline[$i] = 1;
                    $scores[$scoretype[$key]][$key] = 1;
                    break;
                }
            }

            $usedcirc = [];
            foreach ($anscircs as $key=>$anscirc) {
                $scores[$scoretype[$key]][$key] = 0;
                for ($i=0; $i<count($circs); $i++) {
                    if (!empty($usedcirc[$i])) { continue; }
                    if (abs($anscirc[0]-$circs[$i][0])>$defpttol*$reltolerance) {
                        continue;
                    }
                    if (abs($anscirc[1]-$circs[$i][1])>$defpttol*$reltolerance) {
                        continue;
                    }
                    if (abs($anscirc[2]-$circs[$i][2])>$defpttol*$reltolerance) {
                        continue;
                    }
                    $usedcirc[$i] = 1;
                    $scores[$scoretype[$key]][$key] = 1;
                    break;
                }
            }

            $usedellipse = [];
            foreach ($ansellipses as $key=>$ansellipse) {
                $scores[$scoretype[$key]][$key] = 0;
                for ($i=0; $i<count($ellipses); $i++) {
                    if (!empty($usedellipse[$i])) { continue; }
                    if (abs($ansellipse[0]-$ellipses[$i][0])>$defpttol*$reltolerance) {
                        continue;
                    }
                    if (abs($ansellipse[1]-$ellipses[$i][1])>$defpttol*$reltolerance) {
                        continue;
                    }
                    if (abs($ansellipse[2]-$ellipses[$i][2])>$defpttol*$reltolerance) {
                        continue;
                    }
                    if (abs($ansellipse[3]-$ellipses[$i][3])>$defpttol*$reltolerance) {
                        continue;
                    }
                    $usedellipse[$i] = 1;
                    $scores[$scoretype[$key]][$key] = 1;
                    break;
                }
            }

            $usedhyperbola = [];
            foreach ($anshyperbolas as $key=>$anshyperbola) {
                $scores[$scoretype[$key]][$key] = 0;
                for ($i=0; $i<count($hyperbolas); $i++) {
                    if (!empty($usedhyperbola[$i])) { continue; }
                    if ($anshyperbola[4]!=$hyperbolas[$i][4]) {continue;}
                    if (abs($anshyperbola[0]-$hyperbolas[$i][0])>$defpttol*$reltolerance) {
                        continue;
                    }
                    if (abs($anshyperbola[1]-$hyperbolas[$i][1])>$defpttol*$reltolerance) {
                        continue;
                    }
                    if (abs($anshyperbola[2]-$hyperbolas[$i][2])>$defpttol*$reltolerance) {
                        continue;
                    }
                    if (abs($anshyperbola[3]-$hyperbolas[$i][3])>$defpttol*$reltolerance) {
                        continue;
                    }
                    $usedhyperbola[$i] = 1;
                    $scores[$scoretype[$key]][$key] = 1;
                    break;
                }
            }

            if ($scoremethod == 'direction' || $scoremethod == 'relativelength') {
                $veclen = [];
                foreach ($vecs as $i=>$vec) {
                    $vecs[$i][5] = atan2($vec[3]-$vec[1], $vec[2]-$vec[0]); // angle
                    $vecs[$i][6] = sqrt(($vec[3]-$vec[1])**2 + ($vec[2]-$vec[0])**2); // length
                }
                $rellengths = [];
            }

            foreach ($ansvecs as $key=>$ansvec) {
                $scores[$scoretype[$key]][$key] = 0;
                if (($scoremethod == 'direction' || $scoremethod == 'relativelength') &&
                    ($ansvec[0]=='p' || $ansvec[0] == 'd')
                ) {
                    if ($ansvec[0]=='p') {
                        $ansvec[5] = atan2($ansvec[4]-$ansvec[2], $ansvec[3]-$ansvec[1]);
                        $ansvec[6] = sqrt(($ansvec[4]-$ansvec[2])**2 + ($ansvec[3]-$ansvec[1])**2); // length
                    } else if ($ansvec[0]=='d') {
                        $ansvec[5] = atan2($ansvec[2], $ansvec[1]);
                        $ansvec[6] = sqrt(($ansvec[2])**2 + ($ansvec[1])**2); // length
                    }
                    for ($i=0; $i<count($vecs); $i++) {
                        if ($vecs[$i][4]!='v') {continue;}
                        if ($ansvec[0]=='p') { 
                            // check starting point
                            if (abs($ansvec[1]-$vecs[$i][0])>$defpttol*$reltolerance) {
                                continue;
                            }
                            if (abs($ansvec[2]-$vecs[$i][1])>$defpttol*$reltolerance) {
                                continue;
                            }
                        }
                        // check direction; allow about 4 degree error
                        if (abs($ansvec[5] - $vecs[$i][5]) > 0.07*$reltolerance) {
                            continue;
                        }
                        if ($ansvec[6] == 0) {
                            $rellengths[$key] = 0;
                        } else {
                            $rellengths[$key] = $vecs[$i][6]/$ansvec[6];
                        }
                        $scores[$scoretype[$key]][$key] = 1;
                        break;
                    }
                } else if ($ansvec[0]=='p') {  //point
                    for ($i=0; $i<count($vecs); $i++) {
                        if ($vecs[$i][4]!='v') {continue;}
                        if (abs($ansvec[1]-$vecs[$i][0])>$defpttol*$reltolerance) {
                            continue;
                        }
                        if (abs($ansvec[2]-$vecs[$i][1])>$defpttol*$reltolerance) {
                            continue;
                        }
                        if (abs($ansvec[3]-$vecs[$i][2])>$defpttol*$reltolerance) {
                            continue;
                        }
                        if (abs($ansvec[4]-$vecs[$i][3])>$defpttol*$reltolerance) {
                            continue;
                        }
                        $scores[$scoretype[$key]][$key] = 1;
                        break;
                    }
                } else if ($ansvec[0]=='r') {  //ray
                    for ($i=0; $i<count($vecs); $i++) {
                        if ($vecs[$i][4]!='r') {continue;}
                        //make sure base point matches
                        if (abs($ansvec[1]-$vecs[$i][0])>$defpttol*$reltolerance) {
                            continue;
                        }
                        if (abs($ansvec[2]-$vecs[$i][1])>$defpttol*$reltolerance) {
                            continue;
                        }

                        //compare slopes
                        $correctdx = $ansvec[3] - $ansvec[1];
                        $correctdy = $ansvec[4] - $ansvec[2];
                        $studx = $vecs[$i][2] - $vecs[$i][0];
                        $study = $vecs[$i][3] - $vecs[$i][1];

                        //find angle between correct ray and stu ray
                        $cosang = ($studx*$correctdx+$study*$correctdy)/(sqrt($studx*$studx+$study*$study)*sqrt($correctdx*$correctdx+$correctdy*$correctdy));
                        $ang = acos($cosang)*57.2957795;
                        if (abs($ang)>1.4*$reltolerance) {
                            continue;
                        }

                        /*
            slope based grading
            if (abs($correctdy)>abs($correctdx)) {
              $m = $correctdx/$correctdy;
              $stum = $studx/$study;
            } else {
              $m = $correctdy/$correctdx;
              $stum =$study/$studx;
            }
            $toladj = pow(10,-1-6*abs($m));
            if (abs($m-$stum)/abs($m+$toladj)>$deftol*$reltolerance) {
              continue;
            }
            */
                        $scores[$scoretype[$key]][$key] = 1;
                        break;
                    }
                } else if ($ansvec[0]=='ls') { //line segment
                    for ($i=0; $i<count($vecs); $i++) {
                        if ($vecs[$i][4]!='ls') {continue;}
                        if (abs($ansvec[1]-$vecs[$i][0])<=$defpttol*$reltolerance && abs($ansvec[2]-$vecs[$i][1])<=$defpttol*$reltolerance) { //x1 of ans matched first vec x
                            if (abs($ansvec[3]-$vecs[$i][2])>$defpttol*$reltolerance) {
                                continue;
                            }
                            if (abs($ansvec[4]-$vecs[$i][3])>$defpttol*$reltolerance) {
                                continue;
                            }
                        } else if (abs($ansvec[1]-$vecs[$i][2])<=$defpttol*$reltolerance && abs($ansvec[2]-$vecs[$i][3])<=$defpttol*$reltolerance) { //x1 of ans matched second vec x
                            if (abs($ansvec[3]-$vecs[$i][0])>$defpttol*$reltolerance) {
                                continue;
                            }
                            if (abs($ansvec[4]-$vecs[$i][1])>$defpttol*$reltolerance) {
                                continue;
                            }
                        } else {
                            continue;
                        }
                        $scores[$scoretype[$key]][$key] = 1;
                        break;
                    }
                } else if ($ansvec[0]=='d') {  //direction vector
                    for ($i=0; $i<count($vecs); $i++) {
                        if ($vecs[$i][4]!='v') {continue;}
                        if (abs($ansvec[1]-($vecs[$i][2] - $vecs[$i][0]))>$defpttol*$reltolerance) {
                            continue;
                        }
                        if (abs($ansvec[2]-($vecs[$i][3] - $vecs[$i][1]))>$defpttol*$reltolerance) {
                            continue;
                        }
                        $scores[$scoretype[$key]][$key] = 1;
                        break;
                    }
                }
            }
            // check rellengths
            if ($scoremethod == 'relativelength' && count($rellengths) > 1) {
                // want to make sure all rellengths values are the same (within reasonable tolerance)
                asort($rellengths);
                $lastval = -1;
                $freqs = [];
                foreach ($rellengths as $k=>$v) {
                    if (($v==0 && $lastval==0) || ($lastval>0 && abs($v-$lastval)/$lastval<.001)) {
                        $freqs[$lastval]++;
                    } else {
                        $freqs[$v] = 1;
                        $lastval = $v;
                    }
                }
                if (count($freqs)>1) { // one different than others
                    arsort($freqs);
                    $bestlen = array_key_first($freqs);
                    foreach ($rellengths as $k=>$v) {
                        if ($v != $bestlen) {
                            $scores[$scoretype[$key]][$k] = 0;
                        }
                    }
                }
            }

            $usedparab = [];
            foreach ($ansparabs as $key=>$ansparab) {
                $scores[$scoretype[$key]][$key] = 0;
                for ($i=0; $i<count($parabs); $i++) {
                    if (!empty($usedparab[$i])) { continue; }
                    if (abs($ansparab[1]-$parabs[$i][0])>$defpttol*$reltolerance) {
                        continue;
                    }
                    if (abs($ansparab[2]-$parabs[$i][1])>$defpttol*$reltolerance) {
                        continue;
                    }
                    if ($ansparab[4] !== $parabs[$i][4]) {
                        continue;
                    }
                    if ($ansparab[0]=='x') { //compare x at yv+-20
                        if (abs($ansparab[3]-$parabs[$i][3])>$defpttol*$reltolerance) {
                            continue;
                        }
                    } else { //compare y at xv+20
                        if (abs($ansparab[3]-$parabs[$i][2])>$defpttol*$reltolerance) {
                            continue;
                        }
                    }
                    $usedparab[$i] = 1;
                    $scores[$scoretype[$key]][$key] = 1;
                    break;
                }
            }

            $usedhparab = [];
            foreach ($anshparabs as $key=>$ansparab) {
                $scores[$scoretype[$key]][$key] = 0;
                for ($i=0; $i<count($hparabs); $i++) {
                    if (!empty($usedhparab[$i])) { continue; }
                    if (abs($ansparab[1]-$hparabs[$i][0])>$defpttol*$reltolerance) {
                        continue;
                    }
                    if (abs($ansparab[2]-$hparabs[$i][1])>$defpttol*$reltolerance) {
                        continue;
                    }
                    if ($ansparab[0]=='x') { //compare x at yv+-20
                        if (abs($ansparab[3]-$hparabs[$i][3])>$defpttol*$reltolerance) {
                            continue;
                        }
                    } else { //compare y at xv+20
                        if (abs($ansparab[3]-$hparabs[$i][2])>$defpttol*$reltolerance) {
                            continue;
                        }
                    }
                    $usedhparab[$i] = 1;
                    $scores[$scoretype[$key]][$key] = 1;
                    break;
                }
            }
            $usedcubic = [];
            foreach ($anscubics as $key=>$anscubic) {
                $scores[$scoretype[$key]][$key] = 0;
                for ($i=0; $i<count($cubics); $i++) {
                    if (!empty($usedcubic[$i])) { continue; }
                    if (abs($anscubic[0]-$cubics[$i][0])>$defpttol*$reltolerance) {
                        continue;
                    }
                    if (abs($anscubic[1]-$cubics[$i][1])>$defpttol*$reltolerance) {
                        continue;
                    }
                    if (abs($anscubic[2]-$cubics[$i][2])/abs($anscubic[2]) > $deftol*$reltolerance) {
                        continue;
                    }
                    $usedcubic[$i] = 1;
                    $scores[$scoretype[$key]][$key] = 1;
                    break;
                }
            }
            //print_r($anscuberoots);
            //print_r($cuberoots);
            $usedcuberoot = [];
            foreach ($anscuberoots as $key=>$anscuberoot) {
                $scores[$scoretype[$key]][$key] = 0;
                for ($i=0; $i<count($cuberoots); $i++) {
                    if (!empty($usedcuberoot[$i])) { continue; }
                    if (abs($anscuberoot[0]-$cuberoots[$i][0])>$defpttol*$reltolerance) {
                        continue;
                    }
                    if (abs($anscuberoot[1]-$cuberoots[$i][1])>$defpttol*$reltolerance) {
                        continue;
                    }
                    if (abs($anscuberoot[2]-$cuberoots[$i][2])/abs($anscuberoot[2]) > $deftol*$reltolerance) {
                        continue;
                    }
                    $usedcuberoot[$i] = 1;
                    $scores[$scoretype[$key]][$key] = 1;
                    break;
                }
            }
            $usedsqrt = [];
            foreach ($anssqrts as $key=>$anssqrt) {
                $scores[$scoretype[$key]][$key] = 0;
                for ($i=0; $i<count($sqrts); $i++) {
                    if (!empty($usedsqrt[$i])) { continue; }
                    if ($anssqrt[3] !== $sqrts[$i][3]) { //horiz flip doesn't match
                        continue;
                    }
                    if (abs($anssqrt[0]-$sqrts[$i][0])>$defpttol*$reltolerance) {
                        continue;
                    }
                    if (abs($anssqrt[1]-$sqrts[$i][1])>$defpttol*$reltolerance) {
                        continue;
                    }
                    if (abs($anssqrt[2]-$sqrts[$i][2])>$defpttol*$reltolerance) {
                        continue;
                    }
                    $usedsqrt[$i] = 1;
                    $scores[$scoretype[$key]][$key] = 1;
                    break;
                }
            }
            $usedrat = [];
            foreach ($ansrats as $key=>$ansrat) {
                $scores[$scoretype[$key]][$key] = 0;
                for ($i=0; $i<count($rats); $i++) {
                    if (!empty($usedrat[$i])) { continue; }
                    if (abs($ansrat[0]-$rats[$i][0])>$defpttol*$reltolerance) {
                        continue;
                    }
                    if (abs($ansrat[1]-$rats[$i][1])>$defpttol*$reltolerance) {
                        continue;
                    }
                    if (sqrt(2)*abs($ansrat[2]-$rats[$i][2])>$defpttol*$reltolerance) {
                        continue;
                    }
                    $usedrat[$i] = 1;
                    $scores[$scoretype[$key]][$key] = 1;
                    break;
                }
            }
            $usedexp = [];
            foreach ($ansexps as $key=>$ansexp) {
                $scores[$scoretype[$key]][$key] = 0;
                for ($i=0; $i<count($exps); $i++) {
                    if (!empty($usedexp[$i])) { continue; }
                    //if (abs($ansexp[0]-$exps[$i][0])>$defpttol*$reltolerance) {
                    //  continue;
                    //}
                    //check base
                    if (abs($ansexp[1]-$exps[$i][4])/(abs($ansexp[1]-1)+1e-18)>$deftol*$reltolerance) {
                        continue;
                    }
                    //check asymptote
                    if (abs($ansexp[2]-$exps[$i][5])>$defpttol*$reltolerance) {
                        continue;
                    }
                    //check left point if base>1
                    if ($ansexp[1]>1 && abs($ansexp[0]*safepow($ansexp[1],$exps[$i][0]) - $exps[$i][1]) >$defpttol*$reltolerance) {
                        continue;
                    }
                    //check right point if base<=
                    if ($ansexp[1]<=1 && abs($ansexp[0]*safepow($ansexp[1],$exps[$i][2]) - $exps[$i][3]) >$defpttol*$reltolerance) {
                        continue;
                    }
                    $usedexp[$i] = 1;
                    $scores[$scoretype[$key]][$key] = 1;
                    break;
                }
            }
            $usedlogs = [];
            foreach ($anslogs as $key=>$anslog) {
                $scores[$scoretype[$key]][$key] = 0;
                for ($i=0; $i<count($logs); $i++) {
                    if (!empty($usedlogs[$i])) { continue; }
                    //check base
                    if (abs($anslog[1]-$logs[$i][4])/(abs($anslog[1]-1)+1e-18)>$deftol*$reltolerance) {
                        continue;
                    }
                    //check asymptote
                    if (abs($anslog[2]-$logs[$i][5])>$defpttol*$reltolerance) {
                        continue;
                    }
                    //check bottom point if base>1
                    if ($anslog[1]>1 && abs($anslog[0]*safepow($anslog[1],$logs[$i][0]) - $logs[$i][1]) >$defpttol*$reltolerance) {
                        continue;
                    }
                    //check top point if base<=
                    if ($anslog[1]<=1 && abs($anslog[0]*safepow($anslog[1],$logs[$i][2]) - $logs[$i][3]) >$defpttol*$reltolerance) {
                        continue;
                    }
                    $usedlogs[$i] = 1;
                    $scores[$scoretype[$key]][$key] = 1;
                    break;
                }
            }
            $usedcos = [];
            foreach ($anscoss as $key=>$anscos) {
                $scores[$scoretype[$key]][$key] = 0;
                for ($i=0; $i<count($coss); $i++) {
                    if (!empty($usedcos[$i])) { continue; }
                    $per = abs($anscos[0] - $anscos[1])*2;
                    // make sure horizontal shift is ok
                    $adjdiff = abs($anscos[0]-$coss[$i][0]);
                    $adjdiff = abs($adjdiff - $per*round($adjdiff/$per));
                    if ($adjdiff>$defpttol*$reltolerance) {
                        continue;
                    }
                    // check period is OK
                    $per2 = abs($coss[$i][0] - $coss[$i][1])*2;
                    if (abs($per - $per2) > 2*$defpttol*$reltolerance) {
                        continue;
                    }
                    if (abs($anscos[2]-$coss[$i][2])>$defpttol*$reltolerance) {
                        continue;
                    }
                    if (abs($anscos[3]-$coss[$i][3])>$defpttol*$reltolerance) {
                        continue;
                    }
                    $scores[$scoretype[$key]][$key] = 1;
                    $usedcos[$i] = 1;
                    break;
                }
            }
            $usedabs = [];
            foreach ($ansabs as $key=>$aabs) {
                $scores[$scoretype[$key]][$key] = 0;
                for ($i=0; $i<count($abs); $i++) {
                    if (!empty($usedabs[$i])) { continue; }
                    if (abs($aabs[0]-$abs[$i][0])>$defpttol*$reltolerance) {
                        continue;
                    }
                    if (abs($aabs[1]-$abs[$i][1])>$defpttol*$reltolerance) {
                        continue;
                    }
                    //check slope
                    $toladj = pow(10,-1-6*abs($aabs[2]));
                    if (abs($aabs[2]-$abs[$i][2])/(abs($aabs[2])+$toladj)>$deftol*$reltolerance) {
                        continue;
                    }
                    $scores[$scoretype[$key]][$key] = 1;
                    $usedabs[$i] = 1;
                    break;
                }
            }
            //extra stuff is total count of drawn items - # of scored items - # of correct optional items
            $extrastuffpenalty = max((count($tplines)+count($dots)+count($odots)-count($scores[0])-array_sum($scores[1]))/(max(count($scores[0]),count($tplines)+count($dots)+count($odots))),0);
            // don't need optional scores anymore
            $scores = $scores[0];
        } else if ($answerformat[0]=="inequality") {
            list($lines,$dots,$odots,$tplines,$ineqlines) = array_slice(explode(';;',$givenans),0,5);
            /*$x1 = 1/3*$settings[0] + 2/3*$settings[1];
      $x2 = 2/3*$settings[0] + 1/3*$settings[1];
      $x1p = ($x1 - $settings[0])*$pixelsperx + $imgborder;
      $x2p = ($x2 - $settings[0])*$pixelsperx + $imgborder;
      $ymid = ($settings[2]+$settings[3])/2;
      $ymidp = $settings[7] - ($ymid-$settings[2])*$pixelspery - $imgborder;*/
            $epsilon = ($settings[1]-$settings[0])/97;
            $x0 = $settings[0] + $epsilon;
            $x1 = 1/4*$settings[1] + 3/4*$settings[0] + $epsilon;
            $x2 = 1/2*$settings[1] + 1/2*$settings[0] + $epsilon;
            $x3 = 3/4*$settings[1] + 1/4*$settings[0] + $epsilon;
            $x4 = $settings[1] + $epsilon;
            $x0p = ($x0 - $settings[0])*$pixelsperx + $imgborder;
            $x1p = ($x1 - $settings[0])*$pixelsperx + $imgborder;
            $x2p = ($x2 - $settings[0])*$pixelsperx + $imgborder;
            $x3p = ($x3 - $settings[0])*$pixelsperx + $imgborder;
            $x4p = ($x4 - $settings[0])*$pixelsperx + $imgborder;
            $ymid = ($settings[2]+$settings[3])/2;
            $ymidp = $settings[7] - ($ymid-$settings[2])*$pixelspery - $imgborder;
            foreach ($answers as $key=>$function) {
                if ($function=='') { continue; }
                $function = array_map('trim',explode(',',$function));
                if ($function[0][0]=='x' && ($function[0][1]=='<' || $function[0][1]=='>')) {
                    $isxequals = true;
                    $function[0] = substr($function[0],1);
                } else {
                    $isxequals = false;
                }
                if ($function[0][1]=='=') {
                    $type = 10;
                    $c = 2;
                } else {
                    $type = 10.2;
                    $c = 1;
                }
                $dir = $function[0][0];
                if ($isxequals) {
                    $anslines[$key] = array('x',$dir,$type,-10000,(substr($function[0],$c)- $settings[0])*$pixelsperx + $imgborder );
                } else {
                    $func = makepretty(substr($function[0],$c));
                    $func = makeMathFunction($func, 'x');
                    if ($func === false) { continue; }
                    $y1 = $func(['x'=>$x1]);
                    $y2 = $func(['x'=>$x2]);
                    $y3 = $func(['x'=>$x3]);
                    $y1p = $settings[7] - ($y1-$settings[2])*$pixelspery - $imgborder;
                    $y2p = $settings[7] - ($y2-$settings[2])*$pixelspery - $imgborder;
                    $y3p = $settings[7] - ($y3-$settings[2])*$pixelspery - $imgborder;
                    $denom = ($x1p - $x2p)*($x1p - $x3p)*($x2p - $x3p);
                    $A = ($x3p * ($y2p - $y1p) + $x2p * ($y1p - $y3p) + $x1p * ($y3p - $y2p)) / $denom;
                    if (strpos($function[0],'abs')!==false) { //abs inequality
                        $type = ($type == 10) ? 10.5 : 10.6;
                        $y0 = $func(['x'=>$x0]);
                        $y4 = $func(['x'=>$x4]);
                        $y0p = $settings[7] - ($y0-$settings[2])*$pixelspery - $imgborder;
                        $y4p = $settings[7] - ($y4-$settings[2])*$pixelspery - $imgborder;
                        if (abs(($y2-$y1)-($y1-$y0))<1e-9) { //if first 3 points are colinear
                            $slope = ($y2p-$y1p)/($x2p-$x1p);
                        } else if (abs(($y4-$y3)-($y3-$y2))<1e-9) { //if last 3 points are colinear
                            $slope = -1*($y4p-$y3p)/($x4p-$x3p);  //mult by -1 to get slope on left
                        }
                        $xip = ($slope*($x4p+$x0p)+$y4p-$y0p)/(2*$slope);  //x value of "vertex"
                        $yip = $slope*($xip-$x0p)+$y0p;
                        $anslines[$key] = array('y',$dir,$type,$xip,$yip,$slope);
                    } else if(abs($A)>1e-5){//quadratic inequality:  Contributed by Cam Joyce
                        if($type == 10){//switch to quadratic
                            $type = 10.3;
                        }
                        else{
                            $type = 10.4;
                        }
                        $B = ($x3p*$x3p * ($y1p - $y2p) + $x2p*$x2p * ($y3p - $y1p) + $x1p*$x1p * ($y2p - $y3p)) / $denom;
                        $C = ($x2p * $x3p * ($x2p - $x3p) * $y1p + $x3p * $x1p * ($x3p - $x1p) * $y2p + $x1p * $x2p * ($x1p - $x2p) * $y3p) / $denom;
                        $anslines[$key] = array('y',$dir,$type,$A,-$B/(2*$A),$C-$B*$B/(4*$A));
                    } else{//linear inequality
                        $slope = ($y2p-$y1p)/($x2p-$x1p);
                        if (abs($slope)>1.4) {
                            //use x value at ymid
                            $anslines[$key] = array('x',$dir,$type,$slope,$x1p+($ymidp-$y1p)/$slope);
                        } else {
                            //use y value at x2
                            $anslines[$key] = array('y',$dir,$type,$slope,$y2p);
                        }
                    }
                }
            }
            if ($ineqlines=='') {
                $ineqlines = array();
            } else {
                $ineqlines = explode('),(', substr($ineqlines,1,strlen($ineqlines)-2));
                foreach ($ineqlines as $k=>$val) {
                    $pts = explode(',',$val);
                    if($pts[0]<10.3){//linear
                        if ($pts[3]==$pts[1]) {
                            $slope = 10000;
                        } else {
                            $slope = ($pts[4]-$pts[2])/($pts[3]-$pts[1]);
                        }
                        if (abs($slope)>50) {
                            if ($pts[5]>$pts[3]) {
                                $dir = '>';
                            } else {
                                $dir = '<';
                            }
                            $ineqlines[$k] = array('x',$dir,$pts[0],-10000,$pts[1]);

                        } else {

                            $yatpt5 = $slope*($pts[5] - $pts[1]) + $pts[2];
                            if ($yatpt5 < $pts[6]) {
                                $dir = '<';
                            } else {
                                $dir = '>';
                            }
                            if (abs($slope)>50) {$slope = -10000;}
                            if (abs($slope)>1) {
                                $ineqlines[$k] = array('x',$dir,$pts[0],$slope,$pts[1]+($ymidp-$pts[2])/$slope,$pts[2]+($x2p-$pts[1])*$slope);
                            } else {
                                $ineqlines[$k] = array('y',$dir,$pts[0],$slope,$pts[2]+($x2p-$pts[1])*$slope);
                            }
                        }
                    } else if($pts[0]<10.5){//quadratic
                        $aUser = ($pts[4] - $pts[2])/(($pts[3]-$pts[1])*($pts[3]-$pts[1]));
                        $yatpt5 = $aUser*($pts[5]-$pts[1])*($pts[5]-$pts[1])+$pts[2];
                        if($yatpt5 < $pts[6]){
                            $dir = '<';
                        } else {
                            $dir = '>';
                        }
                        $ineqlines[$k] = array('y',$dir,$pts[0],$aUser,$pts[1],$pts[2]);
                    } else { //abs 
                        if ($pts[3]!=$pts[1]) {
                            $slope = ($pts[4]-$pts[2])/($pts[3]-$pts[1]);
                            if ($pts[3] < $pts[1]) {
                                $slope *= -1;
                            }
                            $yatpt5 = $slope*abs($pts[5] - $pts[1]) + $pts[2];
                            if ($yatpt5 < $pts[6]) {
                                $dir = '<';
                            } else {
                                $dir = '>';
                            }
                            $ineqlines[$k] = array('y',$dir,$pts[0],$pts[1],$pts[2],-1*$slope);
                        }
                    }
                }
            }
            $scores = array();
            $deftol = .1;
            $defpttol = 5;

            foreach ($anslines as $key=>$ansline) {
                $scores[$key] = 0;
                for ($i=0; $i<count($ineqlines); $i++) {
                    if ($ansline[2]!=$ineqlines[$i][2]) { continue;}
                    if ($ansline[1]!=$ineqlines[$i][1]) { continue;}
                    if($ansline[2] < 10.3){//linear inequality
                        //check slope
                        $toladj = pow(10,-1-6*abs($ansline[3]));
                        $relerr = abs($ansline[3]-$ineqlines[$i][3])/(abs($ansline[3])+$toladj);
                        if ($relerr>$deftol*$reltolerance) {
                            continue;
                        }
                        if ($ansline[0]!=$ineqlines[$i][0]) {
                            if (abs(abs($ansline[3])-1)<.4) {
                                //check intercept
                                if (abs($ansline[4]-$ineqlines[$i][5])>$defpttol*$reltolerance) {
                                    continue;
                                }
                            } else {
                                continue;
                            }
                        } else {
                            if (abs($ansline[4]-$ineqlines[$i][4])>$defpttol*$reltolerance) {
                                continue;
                            }
                        }
                        $scores[$key] = 1;
                        break;
                    } else if ($ansline[2] < 10.5){//quadratic inequality
                        //check values in y = a(x-p)+q
                        $toladj = pow(10,-1-6*abs($ansline[3]));
                        $relerr = abs($ansline[3]-$ineqlines[$i][3])/(abs($ansline[3])+$toladj);
                        if ($relerr>$deftol*$reltolerance) {
                            continue;
                        }
                        if (abs($ansline[4]-$ineqlines[$i][4])>$defpttol*$reltolerance) {
                            continue;
                        }
                        if (abs($ansline[5]-$ineqlines[$i][5])>$defpttol*$reltolerance) {
                            continue;
                        }
                        $scores[$key] = 1;
                        break;
                    } else { // abs ineq
                        if (abs($ansline[3]-$ineqlines[$i][3])>$defpttol*$reltolerance) {
                            continue;
                        }
                        if (abs($ansline[4]-$ineqlines[$i][4])>$defpttol*$reltolerance) {
                            continue;
                        }
                        $toladj = pow(10,-1-6*abs($ansline[5]));
                        if (abs($ansline[5]-$ineqlines[$i][5])/(abs($ansline[5])+$toladj)>$deftol*$reltolerance) {
                            continue;
                        }
                        $scores[$key] = 1;
                        break;
                    }
                }
            }
            $extrastuffpenalty = max((count($ineqlines)-count($answers))/(max(count($answers),count($ineqlines))),0);

        } else if ($answerformat[0]=='numberline') {
            foreach ($answers as $key=>$function) {
                if ($function=='') { continue; }
                $function = array_map('trim',explode(',',$function));
                if (count($function)==2 || (count($function)==3 && ($function[2]=='open' || $function[2]=='closed'))) { //is dot
                $pixx = ($function[0] - $settings[0])*$pixelsperx + $imgborder;
                $pixy = $settings[7] - ($function[1]-$settings[2])*$pixelspery - $imgborder;
                $newdot = array($pixx, $pixy);
                if (count($function)==2 || $function[2]=='closed') {
                  if (!in_array($newdot, $ansdots)) { // no duplicates
                    $ansdots[$key] = $newdot;
                  }
                } else {
                  if (!in_array($newdot, $ansodots)) { // no duplicates
                    $ansodots[$key] = $newdot;
                  }
                }
              } else {
                if (trim($function[1])==='-oo') {
                    $xminpix = 1;
                } else {
                    $xminpix = round(max(1,($function[1] - $settings[0])*$pixelsperx + $imgborder));
                }
                if (trim($function[2])==='oo') {
                    $xmaxpix = $settings[6]-1;
                } else {
                    $xmaxpix = round(min($settings[6]-1,($function[2] - $settings[0])*$pixelsperx + $imgborder));
                }
                if ($xminpix == $xmaxpix) { continue; } // skip if zero-length line
      					$overlap = false;
                foreach ($anslines as $lk=>$line) {
                  if ($line[0] <= $xmaxpix && $line[1] >= $xminpix) { // overlap
                    $anslines[$lk] = array(min($line[0], $xminpix), max($line[1], $xmaxpix));
                    $overlap = true;
                  }
                }
                if (!$overlap) {
                  $anslines[$key] = array($xminpix,$xmaxpix);
                }
              }
            }

            list($lines,$dots,$odots,$tplines,$ineqlines) = array_slice(explode(';;',$givenans),0,5);

            if ($lines=='') {
                $lines = array();
            } else {
                $lines = explode(';',$lines);
                foreach ($lines as $k=>$line) {
                    $lines[$k] = explode('),(',substr($line,1,strlen($line)-2));
                    $minp = explode(',', $lines[$k][0]);
                    $maxp = explode(',', $lines[$k][count($lines[$k])-1]);
                    $lines[$k] = array(min($minp[0], $maxp[0]), max($minp[0], $maxp[0]));
                }
                $newlines = array($lines[0]);
                for ($i=1;$i<count($lines);$i++) {
                    $overlap = -1;
                    for ($j=count($newlines)-1;$j>=0;$j--) {
                        if ($lines[$i][0]<=$newlines[$j][1] && $lines[$i][1]>=$newlines[$j][0]) {
                            //has overlap
                            if ($overlap>-1) {
                                //already had overlap - merge
                                $newlines[$overlap][0] = min($newlines[$j][0], $newlines[$overlap][0], $lines[$i][0]);
                                $newlines[$overlap][1] = max($newlines[$j][1], $newlines[$overlap][1], $lines[$i][1]);
                                unset($newlines[$j]);
                            } else {
                                $newlines[$j][0] = min($newlines[$j][0], $lines[$i][0]);
                                $newlines[$j][1] = max($newlines[$j][1], $lines[$i][1]);
                                $overlap = $j;
                            }
                        }
                    }
                    if ($overlap==-1) {
                        $newlines[] = $lines[$i];
                    }
                }
                $lines = $newlines;
            }
            $defpttol = 5;
            if ($dots=='') {
                $dots = array();
            } else {
                $dots = explode('),(', substr($dots,1,strlen($dots)-2));
                foreach ($dots as $k=>$pt) {
                    $dots[$k] = explode(',',$pt);
                }
                //remove duplicate dots
                for ($k=count($dots)-1;$k>0;$k--) {
                    for ($j=0;$j<$k;$j++) {
                        if (abs($dots[$k][0]-$dots[$j][0])<$defpttol && abs($dots[$k][1]==$dots[$j][1])<$defpttol) {
                            unset($dots[$k]);
                            continue 2;
                        }
                    }
                }

            }
            if ($odots=='') {
                $odots = array();
            } else {
                $odots = explode('),(', substr($odots,1,strlen($odots)-2));
                foreach ($odots as $k=>$pt) {
                    $odots[$k] = explode(',',$pt);
                }
                //remove duplicate odots, and dots below odots
                for ($k=count($odots)-1;$k>=0;$k--) {
                    for ($j=0;$j<count($dots);$j++) {
                        if (abs($odots[$k][0]-$dots[$j][0])<$defpttol && abs($odots[$k][1]==$dots[$j][1])<$defpttol) {
                            unset($dots[$j]);
                            break;
                        }
                    }
                    for ($j=0;$j<$k;$j++) {
                        if (abs($odots[$k][0]-$odots[$j][0])<$defpttol && abs($odots[$k][1]==$odots[$j][1])<$defpttol) {
                            unset($odots[$k]);
                            continue 2;
                        }
                    }
                }
            }

            $scores = array();
            if ((count($dots)+count($odots))==0) {
                $extradots = 0;
            } else {
                $extradots = max((count($dots) + count($odots) - count($ansdots) - count($ansodots))/(count($dots)+count($odots)),0);
            }

            foreach ($ansdots as $key=>$ansdot) {
                $scores[$key] = 0;
                for ($i=0; $i<count($dots); $i++) {
                    if (($dots[$i][0]-$ansdot[0])*($dots[$i][0]-$ansdot[0]) + ($dots[$i][1]-$ansdot[1])*($dots[$i][1]-$ansdot[1]) <= $defpttol*$defpttol*max(1,$reltolerance)) {
                        $scores[$key] = 1-$extradots;
                        break;
                    }
                }
            }
            foreach ($ansodots as $key=>$ansodot) {
                $scores[$key] = 0;
                for ($i=0; $i<count($odots); $i++) {
                    if (($odots[$i][0]-$ansodot[0])*($odots[$i][0]-$ansodot[0]) + ($odots[$i][1]-$ansodot[1])*($odots[$i][1]-$ansodot[1]) <= $defpttol*$defpttol*max(1,$reltolerance)) {
                        $scores[$key] = 1-$extradots;
                        break;
                    }
                }
            }

            foreach ($anslines as $key=>$ansline) {
                $scores[$key] = 0;
                for ($i=0; $i<count($lines); $i++) {
                    if (abs($ansline[0]-$lines[$i][0])>$defpttol*$reltolerance) {
                        continue;
                    }
                    if (abs($ansline[1]-$lines[$i][1])>$defpttol*$reltolerance) {
                        continue;
                    }
                    $scores[$key] = 1;
                    break;
                }
            }

            $extrastuffpenalty = max((count($lines)-count($anslines))/(max(count($answers),count($anslines))),0);

        } else {
            //not polygon or twopoint, continue with regular grading
            //evaluate all the functions in $answers
            foreach ($answers as $key=>$function) {
                if ($function=='') { continue; }
                $function = explode(',',$function);
                //curves: function
                //    function, xmin, xmax
                //dot:  x,y
                //  x,y,"closed" or "open"
                //form: function, color, xmin, xmax, startmaker, endmarker
                if (count($function)==2 || (count($function)==3 && ($function[2]=='open' || $function[2]=='closed'))) { //is dot
                    $pixx = (evalbasic($function[0]) - $settings[0])*$pixelsperx + $imgborder;
                    $pixy = $settings[7] - (evalbasic($function[1])-$settings[2])*$pixelspery - $imgborder;
                    if (count($function)==2 || $function[2]=='closed') {
                        $ansdots[$key] = array($pixx,$pixy);
                    } else {
                        $ansodots[$key] = array($pixx,$pixy);
                    }
                    continue;
                }
                $anslines[$key] = array();
                $func = makeMathFunction(makepretty($function[0]), 'x');
                if ($func === false) { continue; }
                
                if (!isset($function[1])) {
                    $function[1] = $settings[0];
                }
                if (!isset($function[2])) {
                    $function[2] = $settings[1];
                }
                if ($function[1]>$function[2]) {  //if xmin>xmax, swap
                    $tmp = $function[2];
                    $function[2] = $function[1];
                    $function[1] = $tmp;
                }
                $xminpix = round(max(2*$imgborder,($function[1] - $settings[0])*$pixelsperx + $imgborder));
                $xmaxpix = round(min($settings[6]-2*$imgborder,($function[2] - $settings[0])*$pixelsperx + $imgborder));

                for ($k=ceil($xminpix/$step); $k*$step <= $xmaxpix; $k++) {
                    $x = $k*$step;
                    $coordx = ($x - $imgborder)/$pixelsperx + $settings[0]+1E-10;
                    $coordy = $func(['x'=>$coordx]);
                    if ($coordy>$settings[2] && $coordy<$settings[3]) {
                        $anslines[$key][$k] = $settings[7] - ($coordy-$settings[2])*$pixelspery - $imgborder;
                        if (!isset($anslineptcnt[$k])) {
                            $anslineptcnt[$k] =1;
                        } else {
                            $anslineptcnt[$k]++;
                        }
                        $linepts++;
                    }
                }
            }
            //break apart student entry
            list($lines,$dots,$odots,$tplines,$ineqlines) = array_slice(explode(';;',$givenans),0,5);

            if ($lines=='') {
                $lines = array();
            } else {
                $lines = explode(';',$lines);
                foreach ($lines as $k=>$line) {
                    $lines[$k] = explode('),(',substr($line,1,strlen($line)-2));
                    foreach ($lines[$k] as $j=>$pt) {
                        $lines[$k][$j] = explode(',',$pt);
                    }
                }
            }

            if ($dots=='') {
                $dots = array();
            } else {
                $dots = explode('),(', substr($dots,1,strlen($dots)-2));
                foreach ($dots as $k=>$pt) {
                    $dots[$k] = explode(',',$pt);
                }
            }
            if ($odots=='') {
                $odots = array();
            } else {
                $odots = explode('),(', substr($odots,1,strlen($odots)-2));
                foreach ($odots as $k=>$pt) {
                    $odots[$k] = explode(',',$pt);
                }
            }

            //interp the lines
            $linedata = array();
            $totinterp = 0;
            foreach ($lines as $k=>$line) {
                for ($i=1;$i<count($line);$i++) {
                    $leftx = round(max(min($line[$i][0],$line[$i-1][0]), 2*$imgborder));
                    $rightx = round(min(max($line[$i][0],$line[$i-1][0]), $settings[6]-2*$imgborder));
                    if ($line[$i][0]==$line[$i-1][0]) {
                        $m = 9999;
                    } else {
                        $m = ($line[$i][1] - $line[$i-1][1])/($line[$i][0]-$line[$i-1][0]);
                    }
                    for ($k = ceil($leftx/$step); $k*$step<=$rightx; $k++) {
                        $x = $k*$step;
                        $y = $line[$i-1][1] + $m*($x-$line[$i-1][0]);
                        if ($y>$imgborder && $y<($settings[7]-$imgborder)) {
                            $linedata[$k][] = $y;
                            $totinterp++;
                        }
                    }
                }
            }

            $stdevs = array();
            $stcnts = array();
            $scores = array();
            $unmatchedanspts = array();
            $unmatchedanskeys = array();
            //compare lines
            foreach ($anslines as $key=>$answerline) {
                $unmatchedptcnt = 0;
                $stdevs[$key] = 0;
                $stcnts[$key] = 0;
                foreach($answerline as $k=>$ansy) {
                    //if there are more ans pts than drawn, want to match up better than this;
                    //mark it for coming back to
                    //if less ans pts than drawn, that's already accounted for in $percentoffpts
                    if (!isset($linedata[$k]) || $anslineptcnt[$k]>count($linedata[$k])) {
                        $unmatchedanspts[$k] = 1;
                        continue;
                    }
                    $minerr = $settings[7];
                    for ($i=0; $i<count($linedata[$k]);$i++) {
                        if (abs($ansy-$linedata[$k][$i])<$minerr) {
                            $minerr = abs($ansy-$linedata[$k][$i]);
                        }
                    }
                    if ($minerr<$settings[7]) {
                        $stdevs[$key] += $minerr*$minerr;
                        $stcnts[$key]++;
                    }
                }
            }
            //go back and match up drawn points with unmatched answer points
            //we have more answer points than drawn points here
            foreach (array_keys($unmatchedanspts) as $k) {
                if (!isset($linedata[$k])) {continue;}
                for ($i=0; $i<count($linedata[$k]); $i++) {
                    $minerr = $settings[7];
                    $minerrkey = -1;
                    foreach ($anslines as $key=>$answerline) {
                        if (abs($answerline[$k]-$linedata[$k][$i])<$minerr) {
                            $minerr = abs($answerline[$k]-$linedata[$k][$i]);
                            $minerrkey = $key;
                        }
                    }
                    if ($minerrkey>-1) {
                        $stdevs[$minerrkey] += $minerr*$minerr;
                        $stcnts[$minerrkey]++;
                    }
                }
            }
            //time to grade!
            $percentunmatcheddrawn = 0; //counts extra drawn points: percent of drawn that are extras
            if ($totinterp>0) {
                $percentunmatcheddrawn = max(($totinterp-$linepts)/$totinterp-.05*$reltolerance,0);
            }
            //divide up over all the lines
            $percentunmatcheddrawn = $percentunmatcheddrawn;
            //if ($GLOBALS['myrights']==100) {
            //  print_r($anslines);
            //  print_r($linedata);
            //}
            foreach ($anslines as $key=>$answerline) {
                if ($stcnts[$key]<2) {
                    $stdevs[$key] = 0;
                } else {
                    $stdevs[$key] = sqrt($stdevs[$key]/($stcnts[$key]-1));
                }
                $stdevpen = max(8*($stdevs[$key]-5)/($settings[7]),0);
                if (count($answerline)==0) {
                    $percentunmatchedans = 1;
                } else {
                    $percentunmatchedans = max((count($answerline)-$stcnts[$key])/(count($answerline)),0);
                }
                if ($percentunmatchedans<.05*$reltolerance) {
                    $percentunmatchedans = 0;
                }
                $scores[$key] = 1-($stdevpen + $percentunmatcheddrawn + $percentunmatchedans)/$reltolerance;
                //if ($GLOBALS['myrights']==100) {
                //echo "Line: $key, stdev: {$stdevs[$key]}, unmatchedrawn: $percentunmatcheddrawn, unmatchedans: $percentunmatchedans <br/>";
                //}
                if ($scores[$key]<0) {
                    $scores[$key] = 0;
                } else if ($scores[$key]>1) {
                    $scores[$key] = 1;
                }
            }
            //go through dots
            //echo count($dots) .','.count($odots).','.count($ansdots).','.count($ansodots).'<br/>';
            if ((count($dots)+count($odots))==0) {
                $extradots = 0;
            } else {
                $extradots = max((count($dots) + count($odots) - count($ansdots) - count($ansodots))/(count($dots)+count($odots)),0);
            }

            foreach ($ansdots as $key=>$ansdot) {
                $scores[$key] = 0;
                for ($i=0; $i<count($dots); $i++) {
                    if (($dots[$i][0]-$ansdot[0])*($dots[$i][0]-$ansdot[0]) + ($dots[$i][1]-$ansdot[1])*($dots[$i][1]-$ansdot[1]) <= 25*max(1,$reltolerance)) {
                        $scores[$key] = 1 - $extradots;
                        break;
                    }
                }
            }
            //and open dots
            foreach ($ansodots as $key=>$ansdot) {
                $scores[$key] = 0;
                for ($i=0; $i<count($odots); $i++) {
                    if (($odots[$i][0]-$ansdot[0])*($odots[$i][0]-$ansdot[0]) + ($odots[$i][1]-$ansdot[1])*($odots[$i][1]-$ansdot[1]) <= 25*max(1,$reltolerance)) {
                        $scores[$key] = 1 - $extradots;
                        break;
                    }
                }
            }

        }
        if (empty($partweights)) {
            $scores = array_values($scores); // re-index so partweights applies right
            $partweights = array_fill(0,count($scores),1/count($scores));
        } else {
            if (!is_array($partweights)) {
                $partweights = array_map('trim',explode(',',$partweights));
            }
        }
        $totscore = 0;
        foreach ($scores as $key=>$score) {
            $totscore += $score*$partweights[$key];
        }
        if ($extrastuffpenalty>0) {
            $totscore = max($totscore*(1-$extrastuffpenalty),0);
        }
        if ($abstolerance !== '') {
            if ($totscore<$abstolerance) {
                $scorePartResult->setRawScore(0);
                return $scorePartResult;
            } else {
                $scorePartResult->setRawScore(1);
                return $scorePartResult;
            }
        } else {
            $scorePartResult->setRawScore($totscore);
            return $scorePartResult;
        }
    }
}

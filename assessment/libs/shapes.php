<?php

global $allowedmacros;
array_push($allowedmacros,"draw_circle","draw_circlesector","draw_square","draw_rectangle","draw_triangle","draw_polygon");

//--------------------------------------------draw_circle()----------------------------------------------------
// circle("[center,[label]]","[radius,[label]]","[diameter]","angle,measurement,[label,point]")
// Each "option" is a list in quotes. Here are the available options:
// "center,[label]" This draws the center point with an optional label.
// "radius,[label]" This draws a radius with an optional label.
// "diameter,[label]" This draws a diameter with an optional label.
// "angle,measurement,[label,point]" This draws an angle in standard position, with optional angle label and point on circle.
// Note: Angles are given in degrees, and degree symbol is displayed by default. To not display degree symbol, use e.g. "label rad".
// "point,angle,[label]" This draws a point on the circle at "angle" degrees, counterclockwise from positive x-direction. Optional label on point.
// Note: To get the "pi" symbol in an angle label, use e.g. "3 pi/4" for the label. This is for display only -- angle measurements must be given in degrees.
// "axes" This draws x and y axes.

// All arguments are optional and may be used in any order
function draw_circle() {

  $args = "circle([0,0],1);";
  $degSymbol = "&deg;";
  $input = func_get_args();
  $argsArray = [];
  foreach ($input as $list) {
    if (!is_array($list)) {
      $list = listtoarray($list);
    }
    $list = array_map('trim', $list);
    $argsArray[]=$list;
  }
  foreach ($argsArray as $in) {
    if (in_array("angle",$in) && $in[1]%360 > 315) {
      $angleBlock = true;
      $ang = $in[1];
    }
    if (in_array("radian",$in)) {
      $degSymbol = "";
    }
    if (in_array("axes",$in)) {
      $args = $args . "stroke='grey';line([-1.3,0],[1.3,0]);line([0,-1.3],[0,1.3]);stroke='black';";
    }
  }
  foreach ($argsArray as $key => $in) {
    if (in_array("center",$in)) {
      $lab = "";
      if (isset($in[1]) && $ang !== 0) {
        if ($ang <= 180) {
          $xCentLab = 0.2*cos(M_PI*225/180);
          $yCentLab = 0.2*sin(M_PI*225/180);
        } elseif ($ang > 180 && $ang <= 270) {
          $xCentLab = 0.2*cos(M_PI*315/180);
          $yCentLab = 0.2*sin(M_PI*315/180);
        } else {
          $xCentLab = 0.15*cos(M_PI*225/180);
          $yCentLab = 0.15*sin(M_PI*225/180);
        }
        $in[1] = str_replace(';',',',$in[1]);
        $lab = "text([$xCentLab,$yCentLab],'".$in[1]."');";
      }
      $args = $args."dot([0,0]);".$lab;
    }
    
    if (in_array("radius",$in)) {
      $lab = "";
      if (isset($in[1])) {
        if ($angleBlock === true) {
          $lab = "text([0.5,0],'".$in[1]."',above);";
        } elseif ($angleBlock !== true) {
          $lab = "text([0.5,0],'".$in[1]."',below);";
        }
      }
      $args = $args."line([0,0],[1,0]);".$lab;
    }
    
    if (in_array("diameter",$in)) {
      if (!isset($in[1])) {
        $in[1] = '';
      }
      $args = $args."line([-1,0],[1,0]);text([0,0],'$in[1]',below);";
    }
    
    if (in_array("angle",$in)) {
      $angleKey = $key;
      $lab = "";
      if (!isset($in[1])) {
        echo 'Warning! "angle" must be followed by an angle in degrees.';
        return '';
      }
      if (isset($in[1])) {
        $ang = $in[1];
        $x = cos(M_PI*$ang/180);
        $y = sin(M_PI*$ang/180);
        if ($ang>360) {
          $ang = $ang%360;
        }
        if ($ang<=180) {
          $arc = "arc([.25,0],[.25*$x,.25*$y],.25);";
        }
        if ($ang>180) {
          $arc = "arc([.25,0],[-.25,0],.25);arc([-.25,0],[.25*$x,.25*$y],.25);";
        }
        $args = $args.$arc;
        
        if (isset($in[2])) {
          $angLab = $in[2];
          $greekSpelled = ["/alpha/","/beta/","/gamma/","/theta/","/phi/","/tau/","/pi/","/rad/"];
          $greekSymbol = ["&alpha;","&beta;","&gamma;","&theta;","&phi;","&tau;","&pi;",""];
          if (!empty($angLab)) {
            for ($j=0;$j<count($greekSpelled);$j++) {
              if (preg_match($greekSpelled[$j],$angLab)) {
                $degSymbol = '';
                $angLab = preg_replace($greekSpelled[$j],$greekSymbol[$j],$angLab);
              }
            }  
          }
          
          // draw angle label
          $halfAngle = $ang/2;
          if ($ang <= 40) {
            $xlab = .4*cos(M_PI*($ang+18)/180);
            $ylab = .4*sin(M_PI*($ang+18)/180);
          } elseif ($ang > 40 && $ang < 90) {
            $xlab = .4*cos(M_PI*($halfAngle)/180);
            $ylab = .4*sin(M_PI*($halfAngle)/180);
          } elseif ($ang >= 90 && $ang <= 180) {
            $xlab = .4*cos(M_PI*45/180);
            $ylab = .4*sin(M_PI*45/180);
          } elseif ($ang > 180 || $ang <= 360) {
            $xlab = .4*cos(M_PI*135/180);
            $ylab = .4*sin(M_PI*135/180);
          }
          
          $lab = "text([$xlab,$ylab],'".$angLab."$degSymbol');";
        }
        $args = $args.$lab;
      }
      $args = $args."line([0,0],[1,0]);line([0,0],[$x,$y]);";
    }
    if (in_array("dot",$in) || in_array("point",$in)) {
      if (!isset($in[1])) {
        echo 'Warning! "point" must be followed by an angle in degrees.';
      }
      if (isset($in[1]) && is_numeric($in[1])) {
        $angForPt = $in[1];
        $xPtLoc = cos(M_PI*$angForPt/180);
        $yPtLoc = sin(M_PI*$angForPt/180);
        $args = $args."dot([$xPtLoc,$yPtLoc]);";
      }
      $minDiff = 7;
      if (isset($in[2])) {
        //matches pi in expressions, not in words
        $in[2] = str_replace(';',',',$in[2]);
        if (abs($angForPt%360) < $minDiff || abs($angForPt%360-360) < $minDiff) {
          if ($angForPt%360 < 2*$minDiff) {
            $rotAng = 5;
          } else {
            $rotAng = -5;
          }
        } elseif (abs($angForPt%360-90) < $minDiff) {
          if ($angForPt%360>90) {
            $rotAng = 7;
          } else {
            $rotAng = -7;
          }
        } elseif (abs($angForPt%360-180) < $minDiff) {
          if ($angForPt%360>180) {
            $rotAng = 5;
          } else {
            $rotAng = -5;
          }
        } elseif (abs($angForPt%360-270) < $minDiff) {
          if ($angForPt%360>270) {
            $rotAng = 7;
          } else {
            $rotAng = -7;
          }
        }
        $xLabLoc = 1.25*cos(M_PI*($angForPt+$rotAng)/180);
        $yLabLoc = 1.25*sin(M_PI*($angForPt+$rotAng)/180);
        $args = $args . "text([$xLabLoc,$yLabLoc],'$in[2]');";
      }
    }
  }
  
  $gr = showasciisvg("setBorder(5);initPicture(-1.5,1.5,-1.5,1.5);$args",300,300);
  return $gr;
}

//--------------------------------------------draw_circlesector()----------------------------------------------------

// draw_circlesector("angle,A,[label]", ["option 1"], ["option 2"])
// You must include at least "angle, A", where "A" is the degree measurement of the internal angle, and "label" is optional label for the angle.
// Note: Angle must be in degrees. In the label, the degree symbol is shown by default. To not show the degree symbol, use "rad" as in "angle, 57, 1 rad".
// Labels involving "pi" or "theta" will display with those Greek letters.
// Options include:
// "center,[label]" Plots the center of the circle with optional label. If label is a point, use "center, (x;y)".
// "radius,[label]" Labels the radius from the center to (1,0).
// "point,angle,[label]" Plots a point on the circle at angle measured counterclockwise. If optional label is a point, use e.g. "point, 90, (0;1)".

function draw_circlesector() {
  $degSymbol = "&deg;";
  $input = func_get_args();
  $argsArray = [];
  foreach ($input as $list) {
    if (!is_array($list)) {
      $list = listtoarray($list);
    }
    $list = array_map('trim', $list);
    $argsArray[]=$list;
  }
  foreach ($argsArray as $key => $in) {
    if (in_array("angle",$in)) {
      $angleKey = $key;
      $ang = $in[1];
    }
  }
  if (!isset($angleKey)) {
    echo 'Eek! "circlesector" must include "angle, no. of degrees".';
    return '';
  }
  
  foreach ($argsArray as $key => $in) {
    if (in_array("axes",$in)) {
      $args = $args . "stroke='grey';line([-1.3,0],[1.3,0]);line([0,-1.3],[0,1.3]);stroke='black';";
    }
    
    if (in_array("point",$in)) {
      if (!isset($in[1]) || !is_numeric($in[1])) {
        echo 'Warning! "point" must be followed by an angle in degrees.';
        return '';
      }
      $angPt = $in[1];
      $xAngPt = cos($angPt*M_PI/180);
      $yAngPt = sin($angPt*M_PI/180);
      $args = $args . "dot([$xAngPt,$yAngPt]);";
      if (!isset($in[2])) {
        $in[2] = '';
      }
      $in[2] = preg_replace('/;/',',',$in[2]);
      $args = $args . "text([".(1.2*$xAngPt).",".(1.2*$yAngPt)."],'$in[2]');";
    }
    
    if (in_array("center",$in)) {
      $centerKey = $key;
      $in = preg_replace('/;/',',',$in);
      if (!isset($in[1])) {
        $in[1] = '';
      }
      if ($ang <= 180) {
        $lab = "text([0,0],'".$in[1]."',below);";
      } elseif ($ang > 180) {
        $lab = "text([0,0],'".$in[1]."',above);";
      }
      
      $args = $args."dot([0,0]);".$lab;
    }
    
    if (in_array("radius",$in)) {
      $lab = "";
      if (!isset($in[1])) {
        $in[1] = '';
      }
      if (preg_match('/(^\s*pi[^a-zA-Z]+)|([^a-zA-Z\s]+pi[^a-zA-Z])|([^a-zA-z]pi[^a-zA-Z\s]+)|(^\s*pi\s)|(\spi\s)|(\spi$)|([^a-zA-Z]pi$)|(^pi$)/',$in[1])) {
        $in[1] = str_replace("pi","&pi;",$in[1]);
      }
      if ($ang > 330) {
        $lab = "text([0.5,0],'".$in[1]."',above);";
      } elseif ($ang <= 330) {
        $lab = "text([0.5,0],'".$in[1]."',below);";
      }
      $args = $args."line([0,0],[1,0]);".$lab;
    }
    
    if (in_array("angle",$in)) {
      $lab = "";
      if ($ang > 360 || $ang < 0) {
        echo 'Warning! Angle should be between 0 and 360.';
        $ang = $ang%360;
      }
      $x = cos(M_PI*$ang/180);
      $y = sin(M_PI*$ang/180);
      if ($ang <= 180) {
        $sectorArc = "arc([1,0],[$x,$y],1);";
        $arc = "arc([.25,0],[.25*$x,.25*$y],.25);";
      } elseif ($ang > 180) {
        $sectorArc = "arc([1,0],[-1,0],1);arc([-1,0],[$x,$y],1);";
        $arc = "arc([0.25,0],[-0.25,0],0.25);arc([-0.25,0],[.25*$x,.25*$y],.25);";
      }
      $args = $args.$sectorArc.$arc;
      if (isset($in[2])) {
        if (preg_match('/rad/',$in[2])) {
          $in[2] = str_replace('rad','',$in[2]);
          $degSymbol = "";
        }
        if (preg_match('/(^\s*pi[^a-zA-Z]+)|([^a-zA-Z\s]+pi[^a-zA-Z])|([^a-zA-z]pi[^a-zA-Z\s]+)|(^\s*pi\s)|(\spi\s)|(\spi$)|([^a-zA-Z]pi$)|(^pi$)/',$in[2])) {
          $in[2] = str_replace("pi","&pi;",$in[2]);
          $degSymbol = "";
        }
        $greekSpelled = ["alpha","beta","gamma","theta","phi","tau"];
        $greekSymbol = ["&alpha;","&beta;","&gamma;","&theta;","&phi;","&tau;"];
        foreach ($greekSpelled as $greek) {
          if (strpos($greek,$in[2]) !== false) {
            $degSymbol = '';
          }
        }
        $in[2] = str_replace($greekSpelled,$greekSymbol,$in[2]);
        
        $halfAngle = $ang/2;
        $xlab = .45*cos(M_PI*$halfAngle/180);
        $ylab = .4*sin(M_PI*$halfAngle/180);
        if ($ang <= 40) {
          $xlab = .45*cos(M_PI*($ang+15)/180);
          $ylab = .4*sin(M_PI*($ang+15)/180);
        }
        $lab = "text([$xlab,$ylab],'".$in[array_search('angle',$in)+2]."$degSymbol');";
      }
      $args = $args.$lab;
      $args = $args."line([0,0],[1,0]);line([0,0],[$x,$y]);";
    }
  }
  
  $gr = showasciisvg("setBorder(5);initPicture(-1.35,1.35,-1.35,1.35);$args",300,300);
  return $gr;
}

//--------------------------------------------draw_square()----------------------------------------------------

// draw_square([options])
// draw_square() draws a square with no labels.
// Option "base,b" labels the bottom, horizontal side with "b".
// Option "height,h" labels the right, vertical side with "h".
// Option "points,lab1,lab2,lab3,lab4" plots the vertex points with optional labels.

function draw_square() {
  
  $input = func_get_args();
  $argsArray = [];
  foreach ($input as $list) {
    if (!is_array($list)) {
      $list = listtoarray($list);
    }
    $list = array_map('trim', $list);
    $argsArray[]=$list;
  }
  $hasPoints = false;
  foreach ($argsArray as $key => $in) {
    if (in_array("base",$in)) {
      $baseKey = $key;
    }
    if (in_array("height",$in)) {
      $heightKey = $key;
    }
    if (in_array("points",$in)) {
      $hasPoints = true;
      $pointKey = $key;
    }
  }
  
  if (!isset($argsArray[$baseKey][1])) {
    $argsArray[$baseKey][1] = "";
  }
  
  if (!isset($argsArray[$heightKey][1])) {
    $argsArray[$heightKey][1] = "";
  }
  
  $baseLab = $argsArray[$baseKey][1];
  $heightLab = $argsArray[$heightKey][1];
  for ($i=0;$i<4;$i++) {
    if (!isset($argsArray[$pointKey][$i+1])) {
      $argsArray[$pointKey][$i+1] = '';
    }
    $pointLab[$i] = $argsArray[$pointKey][$i+1];
    $pointLab[$i] = preg_replace('/;/',',',$pointLab[$i]);
  }
  $args = $args . "text([0,-1],'$baseLab',below);";
  $args = $args . "text([1,0],'$heightLab',right);";
  
  if ($hasPoints === true) {
    $args = $args . "dot([-1,-1]);dot([1,-1]);dot([1,1]);dot([-1,1]);";
    $args = $args . "text([-1,-1],'$pointLab[0]',below);";
    $args = $args . "text([1,-1],'$pointLab[1]',below);";
    $args = $args . "text([1,1],'$pointLab[2]',above);";
    $args = $args . "text([-1,1],'$pointLab[3]',above);";
  }
  
  $gr = showasciisvg("setBorder(5);initPicture(-1.5,1.5,-1.5,1.5);rect([-1,-1],[1,1]);$args",250,250);
  return $gr;
}

//--------------------------------------------draw_rectangle----------------------------------------------------

// draw_rectangle([options])
// draw_rectangle() draws a random rectangle with no labels.
// Options include:
// "base,number,[label]" and "height,number,[label]" These must be used together. Draws a scaled rectangle with given base and heigh numbers. Optional labels.
// "points,[lab,lab,lab,lab]" Plots vertex points with optional labels.

function draw_rectangle() {
  
  $input = func_get_args();
  $argsArray = [];
  foreach ($input as $list) {
    if (!is_array($list)) {
      $list = listtoarray($list);
    }
    $list = array_map('trim', $list);
    $argsArray[]=$list;
  }
  $hasPoints = false;
  $hasBase = false;
  $hasHeight = false;
  foreach ($argsArray as $key => $in) {
    if (in_array("points",$in)) {
      $hasPoints = true;
    }
    if (in_array("base",$in)) {
      $hasBase = true;
    }
    if (in_array("height",$in)) {
      $hasHeight = true;
    }
  }
  
  [$rndBase,$rndHeight] = diffrands(2,8,2);
  
  if (count($argsArray) == 0 || ($hasBase === false && $hasHeight === false)) {
    $argsArray[] = ["base",$rndBase,""];
    $argsArray[] = ["height",$rndHeight,""];
  }

  foreach ($argsArray as $key => $in) {
    if (in_array("points",$in)) {
      $hasPoints = true;
      $pointKey = $key;
    }
    if (in_array("base",$in)) {
      $hasBase = true;
      $baseKey = $key;
    }
    if (in_array("height",$in)) {
      $hasHeight = true;
      $heightKey = $key;
    }
  }
  
  if (($hasBase === true || $hasHeight === true) && !($hasBase === true && $hasHeight === true)) {
    echo 'Eek! "base" and "height" must both be given';
    return '';
  }

  if (count($argsArray[$baseKey]) == 1) {
    echo 'Warning! "base" should be followed by a number.';
    $argsArray[$baseKey][1] = $rndBase;
    $argsArray[$baseKey][2] = "";
  }
  
  if (count($argsArray[$heightKey]) == 1) {
    echo 'Warning! "height" should be followed by a number.';
    $argsArray[$heightKey][1] = $rndHeight;
    $argsArray[$heightKey][2] = "";
  }
  
  if ($hasPoints === true) {
    for ($i=1;$i<5;$i++) {
      if (!isset($argsArray[$pointKey][$i])) {
        $argsArray[$pointKey][$i] = "";
      }
    }
    $argsArray[$pointKey] = preg_replace('/;/',',',$argsArray[$pointKey]);
  }

  $base = $argsArray[$baseKey][1];
  $baseLab = $argsArray[$baseKey][2];
  $height = $argsArray[$heightKey][1];
  $heightLab = $argsArray[$heightKey][2];
  for ($i=0;$i<4;$i++) {
    $pointLab[$i] = $argsArray[$pointKey][$i+1];
  }

  $maxLen = max($base,$height);
  
  if ($base <= 0 || $height <= 0) {
    echo "Eek! Base and height must be positive numbers.";
    return '';
  }
  $xmin = -$base/($maxLen);
  $xmax = $base/($maxLen);
  $ymin = -$height/($maxLen);
  $ymax = $height/($maxLen);
  
  if ($hasPoints === true) {
    $args = $args."dot([$xmin,$ymin]);dot([$xmax,$ymin]);dot([$xmax,$ymax]);dot([$xmin,$ymax]);";
    $args = $args."text([$xmin,$ymin],'$pointLab[0]',belowleft);";
    $args = $args."text([$xmax,$ymin],'$pointLab[1]',belowright);";
    $args = $args."text([$xmax,$ymax],'$pointLab[2]',aboveright);";
    $args = $args."text([$xmin,$ymax],'$pointLab[3]',aboveleft);";
  }
  
  $args = $args."text([0,".$ymin."],'".$baseLab."',below);";
  $args = $args."text([".$xmax.",0],'".$heightLab."',right);";

  $args = $args."rect([".$xmin.",".$ymin."],[".$xmax.",".$ymax."]);";
  
  $gr = showasciisvg("setBorder(5);initPicture(-1.7,1.7,-1.7,1.7);$args",250,250);
  
  return $gr;
}

//--------------------------------------------draw_triangle()----------------------------------------------------

// triangle(["option 1","option 2",...])
// draw_triangle() This makes a random triangle with no labels.
// Here, each "option" is a list in quotes. Here are the possible options:
// "[angles,A,B,C],lab1,lab2,lab3,arc1,arc2,arc3" Here, A,B,C are angles (in degrees) that define the triangle; lab1,lab2,lab3 are the angle labels; and arc1,arc2,arc3 can be (,(( or ((( to make arc symbols.
// Note: If "angles" is given after "sides", then A,B,C are omitted (since the "sides,a,b,c" will define the triangle).
// Note: All angles should be given in degrees, and the degree symbol is displayed by default. To not display the degree symbol, use e.g. "lab1 rad", etc.
// Note: To get the "pi" symbol in an angle label, use e.g. "lab1 pi". This is only for labels -- angles that define the triangle must be given in degrees.
// Note: The right angle box symbol is always displayed, unless an angle arc is also used on the right angle.
// "[sides,a,b,c],lab1,lab2,lab3,tick1,tick2,tick3" Here, a,b,c define the triangle, lab1,lab2,lab3 are the side labels, and tick1,tick2,tick3 can be |,|| or ||| to make side tickmarks.
// Note: In "side,a,b,c", the number "a" is the side opposite angle "A", etc.
// Note: If "sides" is used after "angles", the a,b,c are omitted (since the "angles,A,B,C" will define the triangle).
// "points,[P,Q,R]" Here, P,Q,R are optional labels on the points.
// "bisector,1,1,1" The entries following "bisector" correspond to the order of the angles. Putting a "1" will draw the angle bisector for that angle.
// "median,1,1,1" Same as with "bisector. Putting a "1" will draw the median for that angle.
// "random" Using this means "angles" should omit A,B,C and "sides" should omit a,b,c, both having just labels.
// Note: All triangles are drawn with random rotation, unless "horizontal" is used.
// "horizontal" Using this draws the triangle so the side opposite to the largest angle will be drawn horizontally.

function draw_triangle() {
  $rndNum = rand(0,360)*M_PI/180;
  $input = func_get_args();
  $argsArray = [];
  foreach ($input as $list) {
    if (!is_array($list)) {
      $list = listtoarray($list);
    }
    $list = array_map('trim', $list);
    $argsArray[]=$list;
  }
  foreach ($argsArray as &$row) {
    $row = preg_replace('/(angle)$/','$1s',$row);
    $row = preg_replace('/(side)$/','$1s',$row);
    $row = preg_replace('/(point)$/','$1s',$row);
  }
  
  $noAngles = true;
  $noSides = true;
  $randomTriangle = false;
  $hasBisectors = false;
  $hasMedian = false;
  $points = false;
  $isHorizontal = false;
  $hasArcs = false;
  $hasMarks = false;
  $hasAltitude = false;
  
  foreach ($argsArray as $in) {
    if (in_array("angles",$in)) {
      $noAngles = false;
    }
    if (in_array("sides",$in)) {
      $noSides = false;
    }
    if (in_array("horizontal",$in)) {
      $isHorizontal = true;
    }
  }
  
  if (count($argsArray) === 0) {
    $argsArray[0] = ["random"];
  }
  
  foreach ($argsArray as $in) {
    if (in_array("random",$in) || in_array("rand",$in) || ($noAngles === true && $noSides === true)) {
      $randomTriangle = true;
    }
  }
  
  foreach ($argsArray as $key => $in) {
    if (in_array("angles",$in)) {
      $noAngles = false;
      $angleKey = $key;
      
      if (count($in) < 4) {
        echo "Eek! 'angles' must be followed by at least three numbers.";
        return '';
      }
    }
    if (in_array("sides",$in)) {
      $noSides = false;
      $sideKey = $key;
      if (count($in) < 4) {
        echo "Eek! 'sides' must be followed by at least three numbers.";
        return '';
      }
    }
    if (in_array("bisector",$in)) {
      $hasBisectors = true;
      $bisectorKey = $key;
    }
    if (in_array("points",$in)) {
      $points = true;
      $pointKey = $key;
    }
    if (in_array("median",$in)) {
      $hasMedian = true;
      $medianKey = $key;
    }
    if (in_array("altitude",$in)) {
      $hasAltitude = true;
      $altitudeKey = $key;
    }
  }

  if ($randomTriangle === true) {
    $ang[0]=rand(25,70);
    $ang[1]=rand(25,70);
    $ang[2]=180-$ang[0]-$ang[1];
    
    if (isset($argsArray[$sideKey][4])) {
      $hasMarks = true;
    }
    if ($noSides ===  true) {
      $argsArray[] = ["sides",'','','','','',''];
      $sideKey = count($argsArray)-1;
    }
    for ($i=4;$i<7;$i++) {
      if (!isset($argsArray[$sideKey][$i])) {
        $argsArray[$i] = '';
      }
    }

    if ($noAngles === false) {
      if (isset($argsArray[$angleKey][4])) {
        $hasArcs = true;
      }
      for ($i=4;$i<7;$i++) {
        if (!isset($argsArray[$angleKey][$i])) {
          $argsArray[$angleKey][$i] = '';
        }
      }
      $arcSymbTmp = array_slice($argsArray[$angleKey],4,3);
      $angToLabel = array_slice($argsArray[$angleKey],1,3);
      $argsArray[$angleKey] = ["angles",$ang[0],$ang[1],$ang[2],$angToLabel[0],$angToLabel[1],$angToLabel[2],$arcSymbTmp[0],$arcSymbTmp[1],$arcSymbTmp[2]];
    }
    if ($noAngles === true) {
      // Uses unshift so "angles" will come before "sides" in the argsArray
      array_unshift($argsArray,["angles",$ang[0],$ang[1],$ang[2],'','','','','','']);
      $angleKey = 0;
      
      /////// Important: Indices must be changed because unshift was used. ///////
      $sideKey = $sideKey + 1;
      $bisectorKey = $bisectorKey + 1;
      $pointKey = $pointKey + 1;
      $medianKey = $medianKey + 1;
      $altitudeKey = $altitudeKey + 1;
      $noAngles = false;
    }
    if ($isHorizontal === true) {
      if (max($ang) == $ang[2]) {
        $rndNum = (90 - $ang[0] - $ang[1])*M_PI/180;
      } elseif (max($ang) == $ang[1]) {
        $rndNum = (2*$ang[2] - $ang[0] - $ang[2] + 90)*M_PI/180;
      } elseif (max($ang) == $ang[0]) {
        $rndNum = (180 + $ang[1] + $ang[2] - 90)*M_PI/180;
      }
    }
  }
  // End random triangles
  
  if ($randomTriangle === false) {
    // Has angles and sides
    if ($noSides === false && $noAngles === false) {
      for ($i=1;$i<4;$i++) {
        if (!isset($argsArray[$angleKey][$i])) {
          $argsArray[$angleKey][$i] = "";
        }
      }
      
      if ($angleKey < $sideKey) {
        if (isset($argsArray[$angleKey][7])) {
          $hasArcs = true;
        }
        if (isset($argsArray[$sideKey][4])) {
          $hasMarks = true;
        }
        // Set angle arcs and side marks blank if not set
        for ($i=7;$i<10;$i++) {
          if (!isset($argsArray[$angleKey][$i])) {
            $argsArray[$angleKey][$i] = '';
          }
        }
        for ($i=4;$i<7;$i++) {
          if (!isset($argsArray[$sideKey][$i])) {
            $argsArray[$sideKey][$i] = '';
          }
        }
      }
      
      if ($sideKey < $angleKey) {
        if (isset($argsArray[$sideKey][7])) {
          $hasMarks = true;
          for ($i=7;$i<10;$i++) {
            if (!isset($argsArray[$sideKey][$i])) {
              $argsArray[$sideKey][$i] = '';
            }
          }
          // Get the side mark symbols
          $sideMarkTmp = array_slice($argsArray[$sideKey],7,3);
        }
        if (isset($argsArray[$angleKey][4])) {
          $hasArcs = true;
          for ($i=4;$i<7;$i++) {
            if (!isset($argsArray[$angleKey][$i])) {
              $argsArray[$angleKey][$i] = '';
            }
          }
          // Get the arc symbols
          $angArcTmp = array_slice($argsArray[$angleKey],4,3);
        }
        // These are the side lengths from which to build the angles
        $sid = array_slice($argsArray[$sideKey],1,3);
        $sidLabTmp = array_slice($argsArray[$sideKey],4,3);
        $angTmp = array_slice($argsArray[$angleKey],1,3);
        if (($sid[0]+$sid[1]<=$sid[2]) || ($sid[1]+$sid[2]<=$sid[0]) || ($sid[2]+$sid[0]<=$sid[1])) {
          echo 'Eek! No triangle possible with these side lengths.';
          return '';
        }
        $angS1 = 180*acos((pow($sid[0],2)-pow($sid[1],2)-pow($sid[2],2))/(-2*$sid[1]*$sid[2]))/(M_PI);
        $angS2 = 180*acos((pow($sid[1],2)-pow($sid[0],2)-pow($sid[2],2))/(-2*$sid[0]*$sid[2]))/(M_PI);
        $angS3 = 180*acos((pow($sid[2],2)-pow($sid[1],2)-pow($sid[0],2))/(-2*$sid[1]*$sid[0]))/(M_PI);

        $argsArray[$angleKey] = ["angles",$angS1,$angS2,$angS3,$angTmp[0],$angTmp[1],$angTmp[2],$angArcTmp[0],$angArcTmp[1],$angArcTmp[2]];
        $argsArray[$sideKey] = ["sides",$sidLabTmp[0],$sidLabTmp[1],$sidLabTmp[2],$sideMarkTmp[0],$sideMarkTmp[1],$sideMarkTmp[2]];
      }
    }
    
    // Has sides, but no angles
    if ($noSides === false && $noAngles === true) {
      $sid = array_slice($argsArray[$sideKey],1,3);
      if (isset($argsArray[$sideKey][7])) {
        $hasMarks = true;
      }
      for ($i=4;$i<7;$i++) {
        if (!isset($argsArray[$sideKey][$i])) {
          $sidTmp[$i] = '';
        } else {
          $sidTmp[$i] = $argsArray[$sideKey][$i];
        }
      }
      for ($i=7;$i<10;$i++) {
        if (!isset($argsArray[$sideKey][$i])) {
          $argsArray[$sideKey][$i] = '';
        } else {
          $sideMarkTmp[$i] = $argsArray[$sideKey][$i];
        }
      }
      if (($sid[0]+$sid[1]<=$sid[2]) || ($sid[1]+$sid[2]<=$sid[0]) || ($sid[2]+$sid[0]<=$sid[1])) {
        echo 'Eek! No triangle possible with these side lengths.';
        return '';
      }
      // Find angles from sides
      $angS1 = 180*acos((pow($sid[0],2)-pow($sid[1],2)-pow($sid[2],2))/(-2*$sid[1]*$sid[2]))/(M_PI);
      $angS2 = 180*acos((pow($sid[1],2)-pow($sid[0],2)-pow($sid[2],2))/(-2*$sid[0]*$sid[2]))/(M_PI);
      $angS3 = 180*acos((pow($sid[2],2)-pow($sid[1],2)-pow($sid[0],2))/(-2*$sid[1]*$sid[0]))/(M_PI);
      
      $argsArray[$sideKey] = ["sides",$sidTmp[4],$sidTmp[5],$sidTmp[6],$sideMarkTmp[7],$sideMarkTmp[8],$sideMarkTmp[9]];
      $argsArray[] = ["angles",$angS1,$angS2,$angS3,'','','','','',''];
      $angleKey = count($argsArray)-1;
    }
    // Has angles, but no sides
    if ($noSides === true && $noAngles === false) {
      if (isset($argsArray[$angleKey][7])) {
        $hasArcs = true;
      }
      // makes angle labels and arcs empty if they weren't already set
      for ($i=4;$i<10;$i++) {
        if (!isset($argsArray[$angleKey][$i])) {
          $argsArray[$angleKey][$i] = '';
        }
      }
      $argsArray[] = ["sides",'','','','','',''];
      $sideKey = count($argsArray)-1;
    }
  }
  
  ///Now, make the triangle
  $ang = array_slice($argsArray[$angleKey],1,3);
  if ($isHorizontal === true) {
    if (max($ang) == $ang[2]) {
      $rndNum = (90 - $ang[0] - $ang[1])*M_PI/180;
    } elseif (max($ang) == $ang[1]) {
      $rndNum = (2*$ang[2] - $ang[0] - $ang[2] + 90)*M_PI/180;
    } elseif (max($ang) == $ang[0]) {
      $rndNum = (180 + $ang[1] + $ang[2] - 90)*M_PI/180;
    }
  }
  
  
  // If altitudes are used, make triangle horizontal so altitude is drawn vertically
  if ($hasAltitude === true) {
    // This will be defined again when altitudes are drawn
    $altitudes = array_slice($argsArray[$altitudeKey],1,3);
    for ($i=0;$i<3;$i++) {
      if (!isset($altitudes[$i])) {
        $altitudes[$i] = '';
      }
      if ($altitudes[$i] == 1) {
        if ($i == 2) {
          $rndNum = (90 - $ang[0] - $ang[1])*M_PI/180;
        } elseif ($i == 1) {
          $rndNum = (2*$ang[2] - $ang[0] - $ang[2] + 90)*M_PI/180;
        } elseif ($i == 0) {
          $rndNum = (180 + $ang[1] + $ang[2] - 90)*M_PI/180;
        }
      }
    }
  }
  
  foreach ($ang as $key => $angle) {
    if (abs($angle - 90) < 1E-9) {
      // Finds the right angle
      $perpKey = $key;
      $hasPerp = true;
    }
  }
  // The angle labels given by user
  $angLab = array_slice($argsArray[$angleKey],4,3);
  // The side labels given by user
  $sidLab = array_slice($argsArray[$sideKey],1,3);
  
  if (abs($ang[0] + $ang[1] + $ang[2] - 180) > 1E-9 ) {
    echo "Eek! Sum of 'angles' is not 180.";
    return '';
  }
  $angRad = calconarray($ang,'x*M_PI/180');
  //coordinates of vertices of triangle
  $x[0] = cos($rndNum);
  $y[0] = sin($rndNum);
  $x[1] = cos(2*$angRad[0] + $rndNum);
  $y[1] = sin(2*$angRad[0] + $rndNum);
  $x[2] = cos(2*$angRad[0] + 2*$angRad[1] + $rndNum);
  $y[2] = sin(2*$angRad[0] + 2*$angRad[1] + $rndNum);
  
  $xmin = min($x);
  $xmax = max($x);
  $ymin = min($y);
  $ymax = max($y);
  $xyDiff = max(($xmax-$xmin),($ymax-$ymin))/2;
  $xminDisp = (($xmax + $xmin)/2-1.35*$xyDiff); //window settings
  $xmaxDisp = (($xmax + $xmin)/2+1.35*$xyDiff);
  $yminDisp = (($ymax + $ymin)/2-1.35*$xyDiff);
  $ymaxDisp = (($ymax + $ymin)/2+1.35*$xyDiff);
  [$xMid[0],$yMid[0]] = [($x[0]+$x[1])/2,($y[0]+$y[1])/2]; //midpoints of sides
  [$xMid[1],$yMid[1]] = [($x[1]+$x[2])/2,($y[1]+$y[2])/2];
  [$xMid[2],$yMid[2]] = [($x[0]+$x[2])/2,($y[0]+$y[2])/2];
  
  // length of side opposite angle 1 (Used for angle placement and angle bisectors)
  $sL1 = sqrt(pow($x[1]-$x[0],2)+pow($y[1]-$y[0],2));
  $sL2 = sqrt(pow($x[2]-$x[1],2)+pow($y[2]-$y[1],2));
  $sL3 = sqrt(pow($x[2]-$x[0],2)+pow($y[2]-$y[0],2));
  $xab[0] = $sL2/($sL2+$sL3)*$x[0] + $sL3/($sL2+$sL3)*$x[1]; //coords of where bisector of angle 1 intersects opposite side
  $yab[1] = $sL2/($sL2+$sL3)*$y[0] + $sL3/($sL2+$sL3)*$y[1];
  $xab[1] = $sL1/($sL1+$sL3)*$x[2] + $sL3/($sL1+$sL3)*$x[1]; //coords of where bisector of angle 2 intersects opposite side
  $yab[2] = $sL1/($sL1+$sL3)*$y[2] + $sL3/($sL1+$sL3)*$y[1];
  $xab[2] = $sL2/($sL2+$sL1)*$x[0] + $sL1/($sL2+$sL1)*$x[2]; //coords of where bisector of angle 3 intersects opposite side
  $yab[3] = $sL2/($sL2+$sL1)*$y[0] + $sL1/($sL2+$sL1)*$y[2];
  
  //points toward which to move small angle labels, and where the side labels go
  [$xDraw[0],$yDraw[0]] = [$xMid[0] - $xyDiff/2*($y[0]-$yMid[0])/sqrt(pow($y[0]-$yMid[0],2)+pow($x[0] - $xMid[0],2)), $yMid[0] + $xyDiff/2*($x[0] - $xMid[0])/sqrt(pow($y[0]-$yMid[0],2)+pow($x[0] - $xMid[0],2))];
  [$xDraw[1],$yDraw[1]] = [$xMid[1] - $xyDiff/2*($y[1]-$yMid[1])/sqrt(pow($y[1]-$yMid[1],2)+pow($x[1] - $xMid[1],2)), $yMid[1] + $xyDiff/2*($x[1] - $xMid[1])/sqrt(pow($y[1]-$yMid[1],2)+pow($x[1] - $xMid[1],2))];
  [$xDraw[2],$yDraw[2]] = [$xMid[2] - $xyDiff/2*($y[2]-$yMid[2])/sqrt(pow($y[2]-$yMid[2],2)+pow($x[2] - $xMid[2],2)), $yMid[2] + $xyDiff/2*($x[2] - $xMid[2])/sqrt(pow($y[2]-$yMid[2],2)+pow($x[2] - $xMid[2],2))];
  
  if ($hasPerp === true) {
    //changes angle indices to point indices
    $indTmp = [2,0,1];
    $perpPointKey = $indTmp[$perpKey];
    // These are the non-right angles, needed to create the right angle box symbol
    $notInd = array_values(array_diff([0,1,2],[$perpPointKey]));
    // Vectors for drawing right angle box
    foreach ($notInd as $k => $ind) {
      $perpVec[$k] = [.1*($x[$ind]-$x[$perpPointKey])/(sqrt(pow($x[$ind]-$x[$perpPointKey],2) + pow($y[$ind]-$y[$perpPointKey],2))), .1*($y[$ind]-$y[$perpPointKey])/(sqrt(pow($x[$ind]-$x[$perpPointKey],2) + pow($y[$ind]-$y[$perpPointKey],2)))];
    }
    $perpPoint[0] = [$x[$perpPointKey]+$perpVec[0][0], $y[$perpPointKey]+$perpVec[0][1]];
    $perpPoint[1] = [$x[$perpPointKey]+$perpVec[1][0], $y[$perpPointKey]+$perpVec[1][1]];
    $perpPoint[2] = [$x[$perpPointKey]+$perpVec[0][0]+$perpVec[1][0], $y[$perpPointKey]+$perpVec[0][1]+$perpVec[1][1]];
    
    // Need to see if there will be an arc symbol covering the right angle box. If so, omit right angle box.
    $arcTypeTmp = array_slice($argsArray[$angleKey],7,3);
    
    if ($hasArcs === false || ($hasArcs === true && empty($arcTypeTmp[$perpKey]))) {
      $args = $args."path([[{$perpPoint[0][0]},{$perpPoint[0][1]}],[{$perpPoint[2][0]},{$perpPoint[2][1]}],[{$perpPoint[1][0]},{$perpPoint[1][1]}]]);";
    }
    
    if (isset($angLab[$perpKey]) && empty($arcTypeTmp[$perpKey])) {
      // Kills the angle label given by user if angle is 90 degrees, later will be changed to perpendicular box symbol
      // Doesn't kill the angle label if angle arcs are shown.
      $angLab[$perpKey] = '';
    }
  }
  
  // DRAW TRIANGLE SIDES
  $args = $args."strokewidth=2;line([$x[0],$y[0]],[$x[1],$y[1]]);line([$x[1],$y[1]],[$x[2],$y[2]]);line([$x[2],$y[2]],[$x[0],$y[0]]);strokewidth=1;";
  
  // PLACE ANGLE LABELS
  //For angles labeled outside the triangle, this is the fraction of distance between vertex and (xDraw,yDraw) point.
  $labRat = 0.33;
  //Cut-off angle sizes for labeling inside or outside triangle
  $angMin = 25;
  $angMax = 125;
  if ($ang[0] < $angMin || $ang[0] > $angMax || abs($ang[2]-$ang[1]) > 110 || min($ang) < 17) {
    if ($ang[1] <= $ang[2]) {$rp[0] = 2;}
    elseif ($ang[1] > $ang[2]) {$rp[0] = 1;}
    $angLabLoc[0] = [$x[2]+$labRat*($xDraw[$rp[0]]-$x[2]), $y[2]+$labRat*($yDraw[$rp[0]]-$y[2])];
  } else {
    // For angles labeled inside the triangle, labRat here is default fraction of distance between
    // vertex and where angle bisector intersects opposite side.
    $angLabLoc[0] = [$x[2] + $labRat*($xab[0]-$x[2]),$y[2] + $labRat*($yab[1]-$y[2])];
  }
  
  if ($ang[1] < $angMin || $ang[1] > $angMax || abs($ang[2]-$ang[0]) > 110 || min($ang) < 17) {
    if ($ang[0] <= $ang[2]) {$rp[1] = 2;}
    elseif ($ang[0] > $ang[2]) {$rp[1] = 0;}
    $angLabLoc[1] = [$x[0]+$labRat*($xDraw[$rp[1]]-$x[0]), $y[0]+$labRat*($yDraw[$rp[1]]-$y[0])];
  } else {
    $angLabLoc[1] = [$x[0] + $labRat*($xab[1]-$x[0]),$y[0] + $labRat*($yab[2]-$y[0])];
  }
  
  if ($ang[2] < $angMin || $ang[2] > $angMax || abs($ang[1]-$ang[0]) > 110 || min($ang) < 17) {
    if ($ang[0] <= $ang[1]) {$rp[2] = 1;}
    elseif ($ang[0] > $ang[1]) {$rp[2] = 0;}
    $angLabLoc[2] = [$x[1]+$labRat*($xDraw[$rp[2]]-$x[1]), $y[1]+$labRat*($yDraw[$rp[2]]-$y[1])];
  } else {
    $angLabLoc[2] = [$x[1] + $labRat*($xab[2]-$x[1]),$y[1] + $labRat*($yab[3]-$y[1])];
  }
  
  // SHOW/HIDE DEGREE SYMBOL
  for ($i=0;$i<3;$i++) {
    $degSymbol[$i] = "&deg;";
    if (preg_match('/rad/',$angLab[$i]) || ($i == $perpKey && $hasPerp === true) || $angLab[$i] == '') {
      $angLab[$i] = preg_replace('/rad/','',$angLab[$i]);
      $degSymbol[$i] = '';
    }
    $greekSpelled = ["/alpha/","/beta/","/gamma/","/theta/","/phi/","/tau/","/pi/"];
    $greekSymbol = ["&alpha;","&beta;","&gamma;","&theta;","&phi;","&tau;","&pi;"];
    if (!empty($angLab[$i])) {
      for ($j=0;$j<count($greekSpelled);$j++) {
        if (preg_match($greekSpelled[$j],$angLab[$i])) {
          $degSymbol[$i] = '';
          $angLab[$i] = preg_replace($greekSpelled[$j],$greekSymbol[$j],$angLab[$i]);
        }
      }  
    }
    $args = $args."text([{$angLabLoc[$i][0]},{$angLabLoc[$i][1]}],'".$angLab[$i]."$degSymbol[$i]');";
  }

  // PLACE SIDE LABELS
  $sidLabLoc[0] = [$xMid[0] - $xyDiff/5*($y[0]-$yMid[0])/sqrt(pow($y[0]-$yMid[0],2)+pow($x[0] - $xMid[0],2)), $yMid[0] + $xyDiff/5*($x[0] - $xMid[0])/sqrt(pow($y[0]-$yMid[0],2)+pow($x[0] - $xMid[0],2))];
  $sidLabLoc[1] = [$xMid[1] - $xyDiff/5*($y[1]-$yMid[1])/sqrt(pow($y[1]-$yMid[1],2)+pow($x[1] - $xMid[1],2)), $yMid[1] + $xyDiff/5*($x[1] - $xMid[1])/sqrt(pow($y[1]-$yMid[1],2)+pow($x[1] - $xMid[1],2))];
  $sidLabLoc[2] = [$xMid[2] - $xyDiff/5*($y[2]-$yMid[2])/sqrt(pow($y[2]-$yMid[2],2)+pow($x[2] - $xMid[2],2)), $yMid[2] + $xyDiff/5*($x[2] - $xMid[2])/sqrt(pow($y[2]-$yMid[2],2)+pow($x[2] - $xMid[2],2))];

  for ($i=0;$i<3;$i++) {
    $args = $args."text([{$sidLabLoc[$i][0]},{$sidLabLoc[$i][1]}],'".$sidLab[$i]."');";
  }
  
  // DRAW ANGLE BISECTORS
  if ($hasBisectors === true) {
    $bisectors = array_slice($argsArray[$bisectorKey],1,6);
    for ($i=1;$i<7;$i++) {
      if (!isset($bisectors[$i])) {
        $bisectors[$i] = '';
      }
    }
    if ($bisectors[0]==1) {
      $args = $args."line([$x[2],$y[2]],[$xab[0],$yab[1]]);dot([$xab[0],$yab[1]]);";
    }
    if ($bisectors[1]==1) {
      $args = $args."line([$x[0],$y[0]],[$xab[1],$yab[2]]);dot([$xab[1],$yab[2]]);";
    }
    if ($bisectors[2]==1) {
      $args = $args."line([$x[1],$y[1]],[$xab[2],$yab[3]]);dot([$xab[2],$yab[3]]);";
    }
    $bisRat = $xyDiff/7;
    $medLabLoc[0] = [$xab[0] - $bisRat*($y[0]-$yMid[0])/sqrt(pow($xMid[0]-$x[0],2)+pow($yMid[0]-$y[0],2)),$yab[1] + $bisRat*($x[0]-$xMid[0])/sqrt(pow($xMid[0]-$x[0],2)+pow($yMid[0]-$y[0],2))];
    $medLabLoc[1] = [$xab[1] - $bisRat*($y[1]-$yMid[1])/sqrt(pow($xMid[1]-$x[1],2)+pow($yMid[1]-$y[1],2)),$yab[2] + $bisRat*($x[1]-$xMid[1])/sqrt(pow($xMid[1]-$x[1],2)+pow($yMid[1]-$y[1],2))];
    $medLabLoc[2] = [$xab[2] - $bisRat*($y[2]-$yMid[2])/sqrt(pow($xMid[2]-$x[2],2)+pow($yMid[2]-$y[2],2)),$yab[3] + $bisRat*($x[2]-$xMid[2])/sqrt(pow($xMid[2]-$x[2],2)+pow($yMid[2]-$y[2],2))];
    for ($i=0;$i<3;$i++) {
      $args = $args."text([{$medLabLoc[$i][0]},{$medLabLoc[$i][1]}],'".$bisectors[($i+3)]."');";
    }
  }
  
  // DRAW MEDIANS
  if ($hasMedian === true) {
    $medians = array_slice($argsArray[$medianKey],1,6);
    for ($i=1;$i<7;$i++) {
      if (!isset($medians[$i])) {
        $medians[$i] = '';
      }
    }
    if ($medians[0]==1) {
      $args = $args."line([$x[2],$y[2]],[$xMid[0],$yMid[0]]);dot([$xMid[0],$yMid[0]]);";
    }
    if ($medians[1]==1) {
      $args = $args."line([$x[0],$y[0]],[$xMid[1],$yMid[1]]);dot([$xMid[1],$yMid[1]]);";
    }
    if ($medians[2]==1) {
      $args = $args."line([$x[1],$y[1]],[$xMid[2],$yMid[2]]);dot([$xMid[2],$yMid[2]]);";
    }
    $medRat = $xyDiff/7;
    $medLabLoc[0] = [$xMid[0] - $medRat*($y[0]-$yMid[0])/sqrt(pow($xMid[0]-$x[0],2)+pow($yMid[0]-$y[0],2)),$yMid[0] + $medRat*($x[0]-$xMid[0])/sqrt(pow($xMid[0]-$x[0],2)+pow($yMid[0]-$y[0],2))];
    $medLabLoc[1] = [$xMid[1] - $medRat*($y[1]-$yMid[1])/sqrt(pow($xMid[1]-$x[1],2)+pow($yMid[1]-$y[1],2)),$yMid[1] + $medRat*($x[1]-$xMid[1])/sqrt(pow($xMid[1]-$x[1],2)+pow($yMid[1]-$y[1],2))];
    $medLabLoc[2] = [$xMid[2] - $medRat*($y[2]-$yMid[2])/sqrt(pow($xMid[2]-$x[2],2)+pow($yMid[2]-$y[2],2)),$yMid[2] + $medRat*($x[2]-$xMid[2])/sqrt(pow($xMid[2]-$x[2],2)+pow($yMid[2]-$y[2],2))];
    for ($i=0;$i<3;$i++) {
      $args = $args."text([{$medLabLoc[$i][0]},{$medLabLoc[$i][1]}],'".$medians[($i+3)]."');";
    }
  }
  
  // DRAW ALTITUDES
  if ($hasAltitude === true) {
    $altitudes = array_slice($argsArray[$altitudeKey],1,6);
    // Need to move point labels to avoid altitude point
    if ($points === true) {
      $pointLabTmp = array_slice($argsArray[$pointKey],1,3);
      for ($i=0;$i<3;$i++) {
        if (!isset($pointLabTmp[$i])) {
          $pointLabTmp[$i] = '';
        }
        $pointLabTmp[$i] = preg_replace('/;/',',',$pointLabTmp[$i]);
      }
    }
    for ($i=1;$i<7;$i++) {
      if (!isset($altitudes[$i])) {
        $altitudes[$i] = '';
      }
    }
    // Area used to find length of altitudes
    $triAreaHalf = abs($x[0]*($y[1]-$y[2])+$x[1]*($y[2]-$y[0])+$x[2]*($y[0]-$y[1]));
    for ($i=0;$i<3;$i++) {
      // Length of side opposite angle $i
      $triSidLen[$i] = sqrt(pow($x[$i]-$x[($i+1)%3],2)+pow($y[$i]-$y[($i+1)%3],2));
      // Unit vector perpendicular to side $i of triangle, pointed away from vertex at angle $i
      $altVec[$i] = [-($y[$i]-$yMid[$i])/sqrt(pow($x[$i]-$xMid[$i],2)+pow($y[$i]-$yMid[$i],2)),($x[$i]-$xMid[$i])/sqrt(pow($x[$i]-$xMid[$i],2)+pow($y[$i]-$yMid[$i],2))];
      // Length of altitude
      $altLen[$i] = $triAreaHalf/$triSidLen[$i];
      // Initial point of altitude
      $altStart[$i] = [$x[($i+2)%3],$y[($i+2)%3]];
      // End point of altitude
      $altEnd[$i] = [$x[($i+2)%3] + $altLen[$i]*$altVec[$i][0], $y[($i+2)%3] + $altLen[$i]*$altVec[$i][1]];
      if ($altitudes[$i] == 1) {
        $args = $args . "line([{$altStart[$i][0]},{$altStart[$i][1]}],[{$altEnd[$i][0]},{$altEnd[$i][1]}]);";
        $args = $args . "dot([{$altEnd[$i][0]},{$altEnd[$i][1]}]);";
        // Draws dashed extension of base for terminal point of altitude
        if ($ang[0]>90 || $ang[1]>90 || $ang[2]>90) {
          if ($ang[($i+1)%3] > 90) {
            $startAltDash = [$x[$i],$y[$i]];
            $endAltDash = [$x[$i]+4*($altEnd[$i][0]-$x[$i]),$y[$i]+4*($altEnd[$i][1]-$y[$i])];
          } elseif ($ang[($i+2)%3] > 90) {
            $startAltDash = [$x[($i+1)%3],$y[($i+1)%3]];
            $endAltDash = [$x[($i+1)%3]+4*($altEnd[$i][0]-$x[($i+1)%3]),$y[($i+1)%3]+4*($altEnd[$i][1]-$y[($i+1)%3])];
          }
          // Dashed line to extend the base of the triangle
          $args = $args . "strokedasharray='4 3';line([$startAltDash[0],$startAltDash[1]],[$endAltDash[0],$endAltDash[1]]);strokedasharray='1 0';";
        }
        // Find in which direction to draw right-angle box symbol
        // Index of maximum non-altitude angle
        if ($ang[($i+1)%3] != 90 && $ang[($i+2)%3] != 90) {
          if ($ang[(($i+1)%3)] > $ang[(($i+2)%3)]) {
            $maxNotAltInd = ($i+3)%3;
          } elseif ($ang[(($i+1)%3)] <= $ang[(($i+2)%3)]) {
            $maxNotAltInd = ($i+4)%3;
          }
          // Vector pointing away from maximum non-altitude angle (and parallel to side opposite angle $i)
          // (to keep the right angle box out of the way of the larger)
          $altSidVec[$i] = [($altEnd[$i][0]-$x[$maxNotAltInd])/sqrt(pow($altEnd[$i][0]-$x[$maxNotAltInd],2)+pow($altEnd[$i][1]-$y[$maxNotAltInd],2)),($altEnd[$i][1]-$y[$maxNotAltInd])/sqrt(pow($altEnd[$i][0]-$x[$maxNotAltInd],2)+pow($altEnd[$i][1]-$y[$maxNotAltInd],2))];
          // Drawing the right-angle box
          $altRat = $xyDiff/12;
          $altPerp[0] = [$altEnd[$i][0]-$altRat*$altVec[$i][0],$altEnd[$i][1]-$altRat*$altVec[$i][1]];
          $altPerp[1] = [$altPerp[0][0]+$altRat*$altSidVec[$i][0],$altPerp[0][1]+$altRat*$altSidVec[$i][1]];
          $altPerp[2] = [$altEnd[$i][0]+$altRat*$altSidVec[$i][0],$altEnd[$i][1]+$altRat*$altSidVec[$i][1]];
          $args = $args . "line([{$altPerp[0][0]},{$altPerp[0][1]}],[{$altPerp[1][0]},{$altPerp[1][1]}]);";
          $args = $args . "line([{$altPerp[1][0]},{$altPerp[1][1]}],[{$altPerp[2][0]},{$altPerp[2][1]}]);";
          // Labels end point of altitude
          $altEndLab[$i] = [$altEnd[$i][0] + $xyDiff/10*$altVec[$i][0],$altEnd[$i][1] + $xyDiff/10*$altVec[$i][1]];
          $args = $args . "text([{$altEndLab[$i][0]},{$altEndLab[$i][1]}],'{$altitudes[$i+3]}');";  
        }
        // This finds the last altitude set, which was used to determine which side is horizontal.
        $lastAltTmp = $i;
      }
    }
    // Labels vertices of triangle below or above points
    $args = $args . "text([{$x[($lastAltTmp+2)%3]},{$y[($lastAltTmp+2)%3]}],'{$pointLabTmp[$lastAltTmp]}',above);";
    $args = $args . "text([{$x[($lastAltTmp+1)%3]},{$y[($lastAltTmp+1)%3]}],'{$pointLabTmp[($lastAltTmp+2)%3]}',below);";
    $args = $args . "text([{$x[$lastAltTmp]},{$y[$lastAltTmp]}],'{$pointLabTmp[($lastAltTmp+1)%3]}',below);";
  }
    
  // PLACE SIDE TICK MARKS
  if ($hasMarks === true) {
    // Half the length of the tick mark
    $markRat = $xyDiff/25;
    $markType = array_slice($argsArray[$sideKey],4,3);
    $markNum = ["|" => 1, "||" => 2, "|||" => 3];
    // Distances between tick marks
    $rMark = array(0,-$xyDiff/25,$xyDiff/25);
    for ($i=0;$i<3;$i++) {
      // Unit vector along side (perpendicular to tick mark)
      $markVecSide[$i] = [($x[$i]-$xMid[$i])/sqrt(pow($x[$i]-$xMid[$i],2)+pow($y[$i]-$yMid[$i],2)),($y[$i]-$yMid[$i])/sqrt(pow($x[$i]-$xMid[$i],2)+pow($y[$i]-$yMid[$i],2))];
      // Unit vector in direction of tick mark, pointed away from triangle center
      $markVecTick[$i] = [-($y[$i]-$yMid[$i])/sqrt(pow($x[$i]-$xMid[$i],2)+pow($y[$i]-$yMid[$i],2)),($x[$i]-$xMid[$i])/sqrt(pow($x[$i]-$xMid[$i],2)+pow($y[$i]-$yMid[$i],2))];
      for ($j=0;$j<$markNum[$markType[$i]];$j++) {
        $markStart[$i] = [$xMid[$i]+$rMark[$j]*$markVecSide[$i][0]-$markRat*$markVecTick[$i][0], $yMid[$i]+$rMark[$j]*$markVecSide[$i][1]-$markRat*$markVecTick[$i][1]];
        $markEnd[$i] = [$xMid[$i]+$rMark[$j]*$markVecSide[$i][0]+$markRat*$markVecTick[$i][0], $yMid[$i]+$rMark[$j]*$markVecSide[$i][1]+$markRat*$markVecTick[$i][1]];
        $args = $args . "strokewidth=2;line([{$markStart[$i][0]},{$markStart[$i][1]}],[{$markEnd[$i][0]},{$markEnd[$i][1]}]);strokewidth=2;";
      }
    }
  }
  
  // PLACE VERTEX POINTS AND LABELS
  if ($points === true) {
    $verRat = $xyDiff/5;
    $args = $args."dot([$x[0],$y[0]]);";
    $args = $args."dot([$x[1],$y[1]]);";
    $args = $args."dot([$x[2],$y[2]]);";
    // Labels the points, unless altitudes are drawn
    if ($hasAltitude !== true) {
      $verLab = array_slice($argsArray[$pointKey],1,3);
      for ($i=0;$i<3;$i++) {
        if (!isset($verLab[$i])) {
          $verLab[$i] = '';
        }
        $verLab[$i] = preg_replace('/;/',',',$verLab[$i]);
      }
      
      $verLabLoc[0] = [$x[2] - $verRat*($xab[0]-$x[2])/sqrt(pow($xab[0]-$x[2],2)+pow($yab[1]-$y[2],2)),$y[2] - $verRat*($yab[1]-$y[2])/sqrt(pow($xab[0]-$x[2],2)+pow($yab[1]-$y[2],2))];
      $verLabLoc[1] = [$x[0] - $verRat*($xab[1]-$x[0])/sqrt(pow($xab[1]-$x[0],2)+pow($yab[2]-$y[0],2)),$y[0] - $verRat*($yab[2]-$y[0])/sqrt(pow($xab[1]-$x[0],2)+pow($yab[2]-$y[0],2))];
      $verLabLoc[2] = [$x[1] - $verRat*($xab[2]-$x[1])/sqrt(pow($xab[2]-$x[1],2)+pow($yab[3]-$y[1],2)),$y[1] - $verRat*($yab[3]-$y[1])/sqrt(pow($xab[2]-$x[1],2)+pow($yab[3]-$y[1],2))];
      for ($i=0;$i<3;$i++) {
        $args = $args."text([{$verLabLoc[$i][0]},{$verLabLoc[$i][1]}],'".$verLab[$i]."');";
      }
    }
  }
  
  // PLACE ANGLE ARCS
  if ($hasArcs === true) {
    $arcType = array_slice($argsArray[$angleKey],7,3);
    $rArc = [0.18*$xyDiff,0.14*$xyDiff,0.10*$xyDiff];
    $arcNum = ["(" => 1, "((" => 2, "(((" => 3];
    
    // Rotate to make one side flat
    $flatAng = M_PI/2 - $angRad[0] - $angRad[1];
    // coordinates of vertices
    $xf[0] = cos($flatAng);
    $yf[0] = sin($flatAng);
    $xf[1] = cos(2*$angRad[0] + $flatAng);
    $yf[1] = sin(2*$angRad[0] + $flatAng);
    $xf[2] = cos(2*$angRad[0] + 2*$angRad[1] + $flatAng);
    $yf[2] = sin(2*$angRad[0] + 2*$angRad[1] + $flatAng);
    
    $getBack = $angRad[0] + $angRad[1] - M_PI/2 + $rndNum;
    
    // Draw arc(s) for angle 0
    if (is_numeric($arcNum[$arcType[0]])) {
      for ($j=0;$j<$arcNum[$arcType[0]];$j++) {
        $arcStart = [$xf[2] + $rArc[$j],$yf[2]];
        $arcEnd = [$xf[2] + $rArc[$j]*cos($angRad[0]),$yf[2] + $rArc[$j]*sin($angRad[0])];
        $arcStartDisp = [cos($getBack)*$arcStart[0]-sin($getBack)*$arcStart[1],sin($getBack)*$arcStart[0]+cos($getBack)*$arcStart[1]];
        $arcEndDisp = [cos($getBack)*$arcEnd[0]-sin($getBack)*$arcEnd[1],sin($getBack)*$arcEnd[0]+cos($getBack)*$arcEnd[1]];
        $args = $args . "strokewidth=2;arc([{$arcStartDisp[0]},{$arcStartDisp[1]}],[{$arcEndDisp[0]},{$arcEndDisp[1]}]),$rArc[$j];strokewidth=1;";
      }
    }
    // Draw arc(s) for angle 1
    if (is_numeric($arcNum[$arcType[1]])) {
      for ($j=0;$j<$arcNum[$arcType[1]];$j++) {
        $arcStart = [$xf[0] - $rArc[$j]*cos($angRad[1]),$yf[0] + $rArc[$j]*sin($angRad[1])];
        $arcEnd = [$xf[0] - $rArc[$j],$yf[0]];
        $arcStartDisp = [cos($getBack)*$arcStart[0]-sin($getBack)*$arcStart[1],sin($getBack)*$arcStart[0]+cos($getBack)*$arcStart[1]];
        $arcEndDisp = [cos($getBack)*$arcEnd[0]-sin($getBack)*$arcEnd[1],sin($getBack)*$arcEnd[0]+cos($getBack)*$arcEnd[1]];
        $args = $args . "strokewidth=2;arc([{$arcStartDisp[0]},{$arcStartDisp[1]}],[{$arcEndDisp[0]},{$arcEndDisp[1]}]),$rArc[$j];strokewidth=1;";
      }
    }
    // Draw arc(s) for angle 2
    if (is_numeric($arcNum[$arcType[2]])) {
      for ($j=0;$j<$arcNum[$arcType[2]];$j++) {
        $arcStart = [$xf[1] - $rArc[$j]*cos($angRad[0]),$yf[1] - $rArc[$j]*sin($angRad[0])];
        $arcEnd = [$xf[1] + $rArc[$j]*cos($angRad[1]),$yf[1] - $rArc[$j]*sin($angRad[1])];
        $arcStartDisp = [cos($getBack)*$arcStart[0]-sin($getBack)*$arcStart[1],sin($getBack)*$arcStart[0]+cos($getBack)*$arcStart[1]];
        $arcEndDisp = [cos($getBack)*$arcEnd[0]-sin($getBack)*$arcEnd[1],sin($getBack)*$arcEnd[0]+cos($getBack)*$arcEnd[1]];
        $args = $args . "strokewidth=2;arc([{$arcStartDisp[0]},{$arcStartDisp[1]}],[{$arcEndDisp[0]},{$arcEndDisp[1]}]),$rArc[$j];strokewidth=1;";
      }
    }
  }
  $gr = showasciisvg("setBorder(10);initPicture($xminDisp,$xmaxDisp,$yminDisp,$ymaxDisp);$args;",350,350);
  
  return $gr;
}

//--------------------------------------------draw_polygon()----------------------------------------------------

// draw_polygon([number],[options])
// draw_polygon() will draw a random polygon from 3 to 9 sides.
// draw_polygon(n) will draw a polygon with n sides.
// Options include:
// "points,[lab1,lab2,...]" Draws points on the vertices with optional labels for vertices
// "regular" Makes the polygon regular.
// "norotate" Makes the first vertex on the positive x-axis.

function draw_polygon() {
  
  $randAng = rand(0,360)*M_PI/180;
  $randSides = rand(3,9);
  $isRegular = false;
  $hasPoints = false;
  $noRotate = false;
  $input = func_get_args();
  $argsArray = [];
  foreach ($input as $list) {
    if (!is_array($list)) {
      $list = listtoarray($list);
    }
    $list = array_map('trim', $list);
    $argsArray[] = $list;
  }
  if (count($argsArray) == 0) {
    $argsArray[] = [$randSides];
  }
  if (count($argsArray) > 0 && !is_numeric($argsArray[0][0])) {
    echo "Warning! First input must be a whole number greater than 2.";
    array_unshift($argsArray,[$randSides]);
  }
  
  foreach ($argsArray as $key => $in) {
    if (in_array("regular",$in)) {
      $isRegular = true;
      $regularKey = $key;
    }
    if (in_array("points",$in)) {
      $hasPoints = true;
      $pointKey = $key;
    }
    if (in_array("norotate",$in)) {
      $noRotate = true;
      $randAng = 0;
    }
    if (in_array("axes",$in)) {
      $args = "stroke='grey';line([-1.3,0],[1.3,0]);line([0,-1.3],[0,1.3]);stroke='black';";
    }
  }
  
  //number of sides
  $n = $argsArray[0][0];
  if (!is_numeric($n) || $n%round($n)!=0 || $n < 3) {
    echo 'Eek! First argument must be a whole number of sides greater than 2.';
    return '';
  }
  
  // Draw the polygon
  $n1 = $n-1;
  $sumAngles = ($n-2)*180;
  $avgCenterAngle = 180 - $sumAngles/$n;
  $minAngle = ceil(0.9*$avgCenterAngle);
  $maxAngle = floor($avgCenterAngle*(1+1/$n));
  
  if ($isRegular === true) {
    for ($i=0;$i<$n;$i++) {
      $ang[] = $avgCenterAngle*M_PI/180;
    }
  } else {
    for ($i=0;$i<$n;$i++) {
      $ang[] = rand($minAngle,$maxAngle)*M_PI/180;
    }
  }
  
  $x[0] = cos($randAng);
  $y[0] = sin($randAng);
  $args = $args . "path([[$x[0],$y[0]]";
  for ($i=0;$i<$n;$i++) {
    $partialSum[$i] = array_sum(array_slice($ang,0,$i));
    $x[$i] = cos($partialSum[$i] + $randAng);
    $y[$i] = sin($partialSum[$i] + $randAng);
    $args = $args . ",[$x[$i],$y[$i]]";
    if ($hasPoints === true) {
      $xPointLab[$i] = 1.2*cos($partialSum[$i] + $randAng);
      $xPointLabPlus[$i] = 1.2*cos($partialSum[$i] + $randAng + 0.1);
      $yPointLab[$i] = 1.2*sin($partialSum[$i] + $randAng);
      $yPointLabPlus[$i] = 1.2*sin($partialSum[$i] + $randAng + 0.1);
      if (!isset($argsArray[$pointKey][1+$i])) {
        $argsArray[$pointKey][1+$i] = "";
      }
    }
  }
  $args = $args . ",[$x[0],$y[0]]]);";

  if ($hasPoints === true) {
    $pointLab = array_slice($argsArray[$pointKey],1);
    for ($i=0;$i<$n;$i++) {
      $pointLab[$i] = preg_replace('/;/',',',$pointLab[$i]);
      $args = $args . "dot([$x[$i],$y[$i]]);";
      $args = $args . "text([$xPointLabPlus[$i],$yPointLabPlus[$i]],'$pointLab[$i]');";
    }
  }
  $gr = showasciisvg("setBorder(10);initPicture(-1.5,1.5,-1.5,1.5);$args;",300,300);
  return $gr;
  }
  
?>

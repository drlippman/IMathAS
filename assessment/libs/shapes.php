<?php

# Library name: shapes
# Functions for creating asciisvg for common shapes.  Version 1.  December 2020
# Contributors:  Nick Chura


global $allowedmacros;
array_push($allowedmacros,"draw_angle","draw_circle","draw_circlesector","draw_square","draw_rectangle","draw_triangle","draw_polygon","draw_prismcubes","draw_cylinder");

//--------------------------------------------draw_angle()----------------------------------------------------

// draw_angle("measurement,[label]","rotate","axes")
// You must include at least the angles measurement to define the angle, and "label" is optional for the angle.
// Note: Angle must be in degrees. In the label, the degree symbol is shown by default. To not show the degree symbol, use "rad" as in "57, 1 rad".
// Labels involving alpha, beta, gamma, theta, phi, pi or tau will display with those Greek letters.
// Options include:
// "rotate,[ang]" Rotates the image by ang degrees counterclockwise. Using just "rotate" will rotate by a random angle.
// "axes" Draws xy axes.

function draw_angle() {
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
  $size = 300;
  foreach ($argsArray as $in) {
    if ($in[0] == "rotate") {
      if (isset($in[1])) {
        $rot = $in[1];
      } elseif (!isset($in[1])) {
        $rot = $GLOBALS['RND']->rand(0,360);
      }
    }
    if ($in[0] == "size") {
      $size = $in[1];
    }
    if (in_array("axes",$in)) {
      $args = $args . "stroke='grey';line([-1.3,0],[1.3,0]);line([0,-1.3],[0,1.3]);stroke='black';";
    }
  }
  
  $rotRad = $rot*M_PI/180;
  $ang = $argsArray[0][0];
  if (abs($ang)>=2000) {
    echo "Angle must be between less than 2000 degrees in magnitude.";
    return '';
  }
  $angRad = $ang*M_PI/180;
  $lab = $argsArray[0][1];
  $xStart = cos($rotRad);
  $yStart = sin($rotRad);
  $xEnd = cos($angRad+$rotRad);
  $yEnd = sin($angRad+$rotRad);
  
  // Draw the sides of the angle
  $args = $args."strokewidth=2;line([0,0],[$xStart,$yStart]);line([0,0],[$xEnd,$yEnd]);dot([0,0]);strokewidth=1;";
  
  // Label the angle
  $belowFactor = 1;
  $labFact = 0.35+0.05*abs(ceil($ang/360));
  if (abs($ang)<30) {
    if ($ang<0) {
      $belowFactor = -1;
    }
    [$xLabLoc,$yLabLoc] = [$labFact*cos(($rot+$ang+$belowFactor*20)*M_PI/180),$labFact*sin(($rot+$ang+$belowFactor*20)*M_PI/180)];
  } elseif (abs($ang)>=30) {
    $halfAngle = $ang/2;
    if (($halfAngle%90<20 && $halfAngle%90>=0) || $halfAngle%90<-70) {
      $moveAxis = 40;
    } elseif (($halfAngle%90>-20 && $halfAngle%90<0) || $halfAngle%90>70) {
      $moveAxis = -40;
    } 
    [$xLabLoc,$yLabLoc] = [$labFact*cos(($halfAngle+$rot+$moveAxis)*M_PI/180),$labFact*sin(($halfAngle+$rot+$moveAxis)*M_PI/180)];
  }
  
  $greekSpelled = ["/alpha/","/beta/","/gamma/","/theta/","/phi/","/tau/","/pi/","/rad/"];
  $greekSymbol = ["&alpha;","&beta;","&gamma;","&theta;","&phi;","&tau;","&pi;",""];
  if (!empty($lab)) {
    for ($j=0;$j<count($greekSpelled);$j++) {
      if (preg_match($greekSpelled[$j],$lab)) {
        $degSymbol = '';
        $lab = preg_replace($greekSpelled[$j],$greekSymbol[$j],$lab);
      }
    }  
  }
  $args = $args."text([$xLabLoc,$yLabLoc],'$lab$degSymbol');";
  
  // Draw the angle arc
  if ($ang < 0) {
    [$xStartTmp,$yStartTmp] = [$xStart,$yStart];
    [$xStart,$yStart] = [$xEnd,$yEnd];
    [$xEnd,$yEnd] = [$xStartTmp,$yStartTmp]; 
  }
  // Starting position of the arc
  $arcRad = 0.15;
  if (abs($ang)>360) {
    $minAng = $rotRad;
    $maxAng = $rotRad+$angRad;
    if ($ang<0) {
      $minAng = $rotRad+$angRad;
      $maxAng = $rotRad;
    }
    $args = $args."plot(['($arcRad+.06/(2*pi)*abs(t-$rotRad))*cos(t)','($arcRad+.06/(2*pi)*abs(t-$rotRad))*sin(t)'],$minAng,$maxAng);";
    //$args = $args."plot(['0.4*cos(t)','0.4*sin(t)'],0,pi);";
  } else {
    if (abs($ang) <= 180) {
      $arc = "arc([$arcRad*$xStart,$arcRad*$yStart],[$arcRad*$xEnd,$arcRad*$yEnd],$arcRad);";
    } elseif (abs($ang) > 180 && abs($ang)<=360) {
      $arc = "arc([$arcRad*$xStart,$arcRad*$yStart],[-$arcRad*$xStart,-$arcRad*$yStart],$arcRad);arc([-$arcRad*$xStart,-$arcRad*$yStart],[$arcRad*$xEnd,$arcRad*$yEnd],$arcRad);";
    } 
    $args = $args.$sectorArc.$arc;
  }
  
  $gr = showasciisvg("setBorder(5);initPicture(-1.1,1.1,-1.1,1.1);$args",$size,$size);
  
  return $gr;
}

//--------------------------------------------draw_circle()----------------------------------------------------
// circle("[center,[label]]","[radius,[label]]","[diameter,[label]]","[angle,measurement,[label]]")
// Each "option" is a list in quotes. Here are the available options:
// "center,[label]" This draws the center point with an optional label.
// "radius,[label]" This draws a radius with an optional label.
// "diameter,[label]" This draws a diameter with an optional label.
// "angle,measurement,[label]" This draws an angle in standard position, with optional angle label.
// Note: Angles are given in degrees, and degree symbol is displayed by default. To not display degree symbol, use e.g. "label rad".
// "point,angle,[label]" This draws a point on the circle at "angle" degrees, counterclockwise from positive x-direction. Optional label on point.
// Note: If point label is (a,b), type it as (a;b).
// Note: To get the "pi" symbol in an angle label, use e.g. "3 pi/4" for the label.
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
  foreach ($argsArray as $key => $in) {
    if ($in[0]=="size" && isset($in[1])) {
      $size = $in[1];
    } else {
      $size = 350;
    }
    if ($in[0]=="angle" && $in[1]%360 > 315) {
      $angleBlock = true;
      $ang = $in[1];
    }
    if (in_array("axes",$in)) {
      $args = $args . "stroke='grey';line([-1.3,0],[1.3,0]);line([0,-1.3],[0,1.3]);stroke='black';";
    }
  }
  foreach ($argsArray as $key => $in) {
    if ($in[0]=="center") {
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
    
    if ($in[0]=="radius") {
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
    
    if ($in[0]=="diameter") {
      if (!isset($in[1])) {
        $in[1] = '';
      }
      $args = $args."line([-1,0],[1,0]);text([0,0],'$in[1]',below);";
    }
    
    if ($in[0]=="angle") {
      $angleKey = $key;
      $lab = "";
      if (!isset($in[1])) {
        echo 'Eek! "angle" must be followed by an angle in degrees.';
        return '';
      }
      if (isset($in[1])) {
        if ($in[1] > 360 || $in[1] < 0) {
          echo 'Eek! Angle must be between 0 and 360.';
          return '';
        }
        $ang = $in[1];
        $x = cos(M_PI*$ang/180);
        $y = sin(M_PI*$ang/180);
        if ($ang>360) {
          $ang = $ang%360;
        }
        if ($ang<=180) {
          $arc = "arc([.3,0],[.3*$x,.3*$y],.3);";
        }
        if ($ang>180) {
          $arc = "arc([.3,0],[-.3,0],.3);arc([-.3,0],[.3*$x,.3*$y],.3);";
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
            $xlab = .45*cos(M_PI*($ang+18)/180);
            $ylab = .45*sin(M_PI*($ang+18)/180);
          } elseif ($ang > 40 && $ang < 90) {
            $xlab = .45*cos(M_PI*($halfAngle)/180);
            $ylab = .45*sin(M_PI*($halfAngle)/180);
          } elseif ($ang >= 90 && $ang <= 180) {
            $xlab = .45*cos(M_PI*45/180);
            $ylab = .45*sin(M_PI*45/180);
          } elseif ($ang > 180 || $ang <= 360) {
            $xlab = .45*cos(M_PI*135/180);
            $ylab = .45*sin(M_PI*135/180);
          }
          
          $lab = "text([$xlab,$ylab],'".$angLab."$degSymbol');";
        }
        $args = $args.$lab;
      }
      $args = $args."line([0,0],[1,0]);line([0,0],[$x,$y]);";
    }
    if ($in[0]=="point") {
      if (!isset($in[1])) {
        echo 'Warning! "point" must be followed by an angle in degrees.';
      }
      if (isset($in[1]) && is_numeric($in[1])) {
        if ($in[1] > 360 || $in[1] < 0) {
          echo 'Eek! Point angle must be between 0 and 360.';
          return '';
        }
        $angForPt = $in[1];
        $xPtLoc = cos(M_PI*$angForPt/180);
        $yPtLoc = sin(M_PI*$angForPt/180);
        $args = $args."dot([$xPtLoc,$yPtLoc]);";
      }
      $minDiff = 7;
      if (isset($in[2])) {
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
  
  $gr = showasciisvg("setBorder(5);initPicture(-1.5,1.5,-1.5,1.5);$args",$size,$size);
  return $gr;
}

//--------------------------------------------draw_circlesector()----------------------------------------------------

// draw_circlesector("angle,measurement,[label]", ["option 1"], ["option 2"])
// You must include at least "angle, measurement" to define the degree measurement of the internal angle, and "label" is optional label for the angle.
// Note: Angle must be in degrees. In the label, the degree symbol is shown by default. To not show the degree symbol, use "rad" as in "angle, 57, 1 rad".
// Labels involving alpha, beta, gamma, theta, phi, pi or tau will display with those Greek letters.
// Options include:
// "center,[label]" Plots the center of the circle with optional label. If label is a point (a,b), type it as (a;b).
// "radius,[label]" Labels the radius from the center to the far-right point on the circle.
// "point,angle,[label]" Plots a point on the circle at angle measured counterclockwise. If optional label is a point (a,b), type it as (a;b).

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
  
  $angs = [];
  $xs = [];
  $ys = [];
  $numAngles = 0;
  $size = 300;
  
  foreach ($argsArray as $key => $in) {
    if ($in[0]=="angle") {
      $angleKey = $key;
      $angs[] = $in[1];
    }
  }
  $xs = calconarray($angs,"cos(M_PI*x/180)");
  $ys = calconarray($angs,"sin(M_PI*x/180)");
  if (max($angs)<=90) {
    $minXWindow = -0.35;
    $minYWindow = -0.35;
  } elseif (max($angs)>90 && max($angs)<180) {
    $minXWindow = min($xs)-0.35;
    $minYWindow = -0.35;
  } elseif (max($angs)>=180 && max($angs)<=270) {
    $minXWindow = -1.35;
    $minYWindow = min($ys)-0.35;
  } elseif (max($angs)>=270) {
    $minYWindow = -1.35;
    $minXWindow = -1.35;
  }
  $minxyDisp = min($minXWindow,$minYWindow);

  if (!isset($angleKey)) {
    echo 'Eek! "circlesector" must include "angle, no. of degrees".';
    return '';
  }
  
  foreach ($argsArray as $key => $in) {
    if ($in[0]=="size" && isset($in[1])) {
      $size = $in[1];
    }
    
    if (in_array("axes",$in)) {
      $args = $args . "stroke='grey';line([-1.3,0],[1.3,0]);line([0,-1.3],[0,1.3]);stroke='black';";
    }
    
    if ($in[0]=="point") {
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
    
    if ($in[0]=="center") {
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
    
    if ($in[0]=="angle") {
      $ang = $in[1];
      $lab = "";
      if ($ang > 360 || $ang < 0) {
        echo 'Eek! Angles should be between 0 and 360.';
        return '';
      }
      $x = cos(M_PI*$ang/180);
      $y = sin(M_PI*$ang/180);
      
      $arcRad = 0.25+0.075*$numAngles;
      if ($ang == min($angs)) {
        $angLabRad = 0.5;
      }
      if ($ang == max($angs) && count($angs) > 1) {
        $angLabRad = 0.8;
      }
      
      if ($ang <= 180) {
        $sectorArc = "arc([1,0],[$x,$y],1);";
        $arc = "arc([$arcRad,0],[$arcRad*$x,$arcRad*$y],$arcRad);";
      } elseif ($ang > 180) {
        $sectorArc = "arc([1,0],[-1,0],1);arc([-1,0],[$x,$y],1);";
        $arc = "arc([$arcRad,0],[-$arcRad,0],$arcRad);arc([-$arcRad,0],[$arcRad*$x,$arcRad*$y],$arcRad);";
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
        // Rotate the label position away from the angle line
        $angLabOffset = [-30+$angLabRad*22,30-$angLabRad*22];
        if ($minxyDisp > -0.7) {
          $angLabOffset = [-30+$angLabRad*27,30-$angLabRad*27];
        }
        $xlab = $angLabRad*cos(M_PI*($ang+$angLabOffset[0])/180);
        $ylab = ($angLabRad-0.05)*sin(M_PI*($ang+$angLabOffset[0])/180);
        if ($ang <= 35) {
          $xlab = $angLabRad*cos(M_PI*($ang+$angLabOffset[1])/180);
          $ylab = ($angLabRad-0.05)*sin(M_PI*($ang+$angLabOffset[1])/180);
        }
        if (count($angs)>1 && $ang == max($angs) && max($angs)-min($angs) < 25) {
          $xlab = $angLabRad*cos(M_PI*($ang+$angLabOffset[1])/180);
          $ylab = ($angLabRad-0.05)*sin(M_PI*($ang+$angLabOffset[1])/180);
        }
        $lab = "text([$xlab,$ylab],'".$in[2]."$degSymbol');";
      }
      $args = $args.$lab;
      $args = $args."line([0,0],[1,0]);line([0,0],[$x,$y]);";
      $numAngles = $numAngles + 1;
    }
    
    if ($in[0]=="radius") {
      $lab = "";
      if (!isset($in[1])) {
        $in[1] = '';
      }
      if (preg_match('/(^\s*pi[^a-zA-Z]+)|([^a-zA-Z\s]+pi[^a-zA-Z])|([^a-zA-z]pi[^a-zA-Z\s]+)|(^\s*pi\s)|(\spi\s)|(\spi$)|([^a-zA-Z]pi$)|(^pi$)/',$in[1])) {
        $in[1] = str_replace("pi","&pi;",$in[1]);
      }
      $above = "above";
      foreach ($angs as $an) {
        if ($an > 330 || (count($angs)>1 && (max($angs)-min($angs)<25) && max($angs)>300)) {
          $angleBlock = true;
        }
      }
      if ($angleBlock === true) {
        foreach ($angs as $an) {
          if ($an < 30) {
            $angleDoubleBlock = true;
          }
        }
        $radLabRad = 0.5;
        if ($angleDoubleBlock === true) {
          $radLabRad = 1;
          $above = "right";
        }
        $lab = "text([$radLabRad,0],'".$in[1]."',$above);";
      } elseif ($angleBlock !== true) {
        $lab = "text([0.5,0],'".$in[1]."',below);";
      }
      $args = $args."line([0,0],[1,0]);".$lab;
    }
  }
  
  $gr = showasciisvg("setBorder(5);initPicture($minxyDisp,1.35,$minxyDisp,1.35);$args",$size,$size);
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
  $size = 300;
  foreach ($argsArray as $key => $in) {
    if ($in[0]=="size" && isset($in[1])) {
      $size = $in[1];
    }
    if ($in[0]=="base") {
      $baseKey = $key;
    }
    if ($in[0]=="height") {
      $heightKey = $key;
    }
    if ($in[0]=="points") {
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
  
  $gr = showasciisvg("setBorder(5);initPicture(-1.5,1.5,-1.5,1.5);rect([-1,-1],[1,1]);$args",$size,$size);
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
  $size = 300;
  foreach ($argsArray as $key => $in) {
    if ($in[0]=="size" && isset($in[1])) {
      $size = $in[1];
    }
    if ($in[0]=="points") {
      $hasPoints = true;
    }
    if ($in[0]=="base") {
      $hasBase = true;
    }
    if ($in[0]=="height") {
      $hasHeight = true;
    }
  }
  
  [$rndBase,$rndHeight] = diffrands(2,8,2);
  
  if (count($argsArray) == 0 || ($hasBase === false && $hasHeight === false)) {
    $argsArray[] = ["base",$rndBase,""];
    $argsArray[] = ["height",$rndHeight,""];
  }

  foreach ($argsArray as $key => $in) {
    if ($in[0]=="points") {
      $hasPoints = true;
      $pointKey = $key;
    }
    if ($in[0]=="base") {
      $hasBase = true;
      $baseKey = $key;
    }
    if ($in[0]=="height") {
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
  
  $gr = showasciisvg("setBorder(5);initPicture(-1.7,1.7,-1.7,1.7);$args",$size,$size);
  
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
// "bisectors,1,1,1,lab1,lab2,lab3,labpt1,labpt2,labpt3" The first three entries following "bisectors" correspond to the order of the angles. Putting a "1" will draw the angle bisector for that angle. The next three entries are labels for the bisectors, and the last three entries are labels for the terminal points.
// "medians,1,1,1,lab1,lab2,lab3,labpt1,labpt2,labpt3" Same as with bisectors. Putting a "1" will draw the median for that angle. The next three entries are labels for the medians, and the last three entries are labels for the terminal points.
// "altitudes,1,1,1,lab1,lab2,lab3,labpt1,labpt2,labpt3" Similar to bisectors and medians.
// "random" Using this creates random angles for the triangle. This means "angles" should omit A,B,C and "sides" should omit a,b,c, both having just labels.
// Note: By default, triangles are drawn with angle 0 at bottom left, angle 1 at bottom right, and angle 2 at the top.
// "rotate" Use to randomly rotate the triangle.

function draw_triangle() {
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
    $row = preg_replace(['/(angle)$/','/(side)$/','/(point)$/','/(median)$/','/(bisector)$/','/(altitude)$/'],'$1s',$row);
  }
  
  $noAngles = true;
  $noSides = true;
  $randomTriangle = false;
  $hasBisectors = false;
  $hasMedian = false;
  $hasPoints = false;
  $rotateTriangle = false;
  $hasArcs = false;
  $hasMarks = false;
  $hasAltitude = false;
  $hasSize = false;
  $size = 350;
  
  foreach ($argsArray as $in) {
    if ($in[0]=="angles") {
      $noAngles = false;
    }
    if ($in[0]=="sides") {
      $noSides = false;
    }
    if (in_array("rotate",$in)) {
      $rotateTriangle = true;
    }
  }
  
  if (count($argsArray) === 0) {
    $argsArray[0] = ["random"];
  }
  
  foreach ($argsArray as $in) {
    if (in_array("random",$in) || ($noAngles === true && $noSides === true)) {
      $randomTriangle = true;
    }
  }
  
  foreach ($argsArray as $key => $in) {
    if ($in[0]=="size" && isset($in[1])) {
      $size = $in[1];
    }
    if ($in[0]=="angles") {
      $noAngles = false;
      $angleKey = $key;
      for ($i=1;$i<4;$i++) {
        if (!isset($in[$i])) {
          $in[$i] = "";
        }
      }
    }
    if ($in[0]=="sides") {
      $noSides = false;
      $sideKey = $key;
      for ($i=1;$i<4;$i++) {
        if (!isset($in[$i])) {
          $in[$i] = "";
        }
      }
    }
    if ($in[0]=="bisectors") {
      $hasBisectors = true;
      $bisectorKey = $key;
    }
    if ($in[0]=="points") {
      $hasPoints = true;
      $pointKey = $key;
    }
    if ($in[0]=="medians") {
      $hasMedian = true;
      $medianKey = $key;
    }
    if ($in[0]=="altitudes") {
      $hasAltitude = true;
      $altitudeKey = $key;
    }
  }

  if ($randomTriangle === true) {
    $ang[0]=$GLOBALS['RND']->rand(25,70);
    $ang[1]=$GLOBALS['RND']->rand(25,70);
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
    if ($rotateTriangle === true) {
      $rndNum = $GLOBALS['RND']->rand(0,360)*M_PI/180;
    } elseif ($rotateTriangle === false) {
      $rndNum = $ang[2]*M_PI/180 - M_PI/2;
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
        for ($i=1;$i<4;$i++) {
          if (empty($argsArray[$angleKey][$i])) {
            echo 'Eek! "Angles" must be followed by three angles in degrees.';
            return '';
          }
        }
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
        for ($i=1;$i<4;$i++) {
          if (empty($argsArray[$sideKey][$i])) {
            echo 'Eek! "Sides" must be followed by three side lengths.';
            return '';
          }
        }
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
      for ($i=1;$i<4;$i++) {
        if (empty($argsArray[$angleKey][$i])) {
          echo 'Eek! "Angles" must be followed by three angles in degrees.';
          return '';
        }
      }
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
  $angRad = calconarray($ang,'x*M_PI/180');
  if ($rotateTriangle === false) {
    $rndNum = $angRad[2] - M_PI/2;
  } elseif ($rotateTriangle === true) {
    $rndNum = $GLOBALS['RND']->rand(0,360)*M_PI/180;
  }
  
  foreach ($ang as $key => $angle) {
    if ($angle <= 0) {
      echo "Eek! Angles must be positive numbers.";
      return '';
    }
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
  $sL0 = sqrt(pow($x[1]-$x[0],2)+pow($y[1]-$y[0],2));
  $sL1 = sqrt(pow($x[2]-$x[1],2)+pow($y[2]-$y[1],2));
  $sL2 = sqrt(pow($x[2]-$x[0],2)+pow($y[2]-$y[0],2));
  $xab[0] = $sL1/($sL1+$sL2)*$x[0] + $sL2/($sL1+$sL2)*$x[1]; //coords of where bisector of angle 1 intersects opposite side
  $yab[0] = $sL1/($sL1+$sL2)*$y[0] + $sL2/($sL1+$sL2)*$y[1];
  $xab[1] = $sL0/($sL0+$sL2)*$x[2] + $sL2/($sL0+$sL2)*$x[1]; //coords of where bisector of angle 2 intersects opposite side
  $yab[1] = $sL0/($sL0+$sL2)*$y[2] + $sL2/($sL0+$sL2)*$y[1];
  $xab[2] = $sL1/($sL1+$sL0)*$x[0] + $sL0/($sL1+$sL0)*$x[2]; //coords of where bisector of angle 3 intersects opposite side
  $yab[2] = $sL1/($sL1+$sL0)*$y[0] + $sL0/($sL1+$sL0)*$y[2];
  
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
    $angLabLoc[0] = [$x[2] + $labRat*($xab[0]-$x[2]),$y[2] + $labRat*($yab[0]-$y[2])];
  }
  
  if ($ang[1] < $angMin || $ang[1] > $angMax || abs($ang[2]-$ang[0]) > 110 || min($ang) < 17) {
    if ($ang[0] <= $ang[2]) {$rp[1] = 2;}
    elseif ($ang[0] > $ang[2]) {$rp[1] = 0;}
    $angLabLoc[1] = [$x[0]+$labRat*($xDraw[$rp[1]]-$x[0]), $y[0]+$labRat*($yDraw[$rp[1]]-$y[0])];
  } else {
    $angLabLoc[1] = [$x[0] + $labRat*($xab[1]-$x[0]),$y[0] + $labRat*($yab[1]-$y[0])];
  }
  
  if ($ang[2] < $angMin || $ang[2] > $angMax || abs($ang[1]-$ang[0]) > 110 || min($ang) < 17) {
    if ($ang[0] <= $ang[1]) {$rp[2] = 1;}
    elseif ($ang[0] > $ang[1]) {$rp[2] = 0;}
    $angLabLoc[2] = [$x[1]+$labRat*($xDraw[$rp[2]]-$x[1]), $y[1]+$labRat*($yDraw[$rp[2]]-$y[1])];
  } else {
    $angLabLoc[2] = [$x[1] + $labRat*($xab[2]-$x[1]),$y[1] + $labRat*($yab[2]-$y[1])];
  }
  
  // SHOW/HIDE DEGREE SYMBOL
  for ($i=0;$i<3;$i++) {
    $degSymbol[$i] = "&deg;";
    if (preg_match('/rad/',$angLab[$i]) || ($i == $perpKey && $hasPerp === true && empty($arcTypeTmp[$i])) || $angLab[$i] == '') {
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
    $bisectors = array_slice($argsArray[$bisectorKey],1,9);
    for ($i=1;$i<10;$i++) {
      if (!isset($bisectors[$i])) {
        $bisectors[$i] = '';
      }
    }
    $bisRat = $xyDiff/6;
    for ($i=0;$i<3;$i++) {
      // Where to label the endpoint of the bisector
      $abPtLabLoc[$i] = [$xab[$i] - $bisRat*($y[$i]-$yMid[$i])/sqrt(pow($xMid[$i]-$x[$i],2)+pow($yMid[$i]-$y[$i],2)),$yab[$i] + $bisRat*($x[$i]-$xMid[$i])/sqrt(pow($xMid[$i]-$x[$i],2)+pow($yMid[$i]-$y[$i],2))];
      // "Midpoints" of angle bisectors
      $abMid[$i] = [($x[($i+2)%3]+2*$xab[$i])/3,($y[($i+2)%3]+2*$yab[$i])/3];
      if ($bisectors[$i] == 1) {
        // Draws the bisector
        $args = $args."line([{$x[($i+2)%3]},{$y[($i+2)%3]}],[$xab[$i],$yab[$i]]);dot([$xab[$i],$yab[$i]]);";
        // Labels the endpoint of the bisector
        $bisectors[$i+6] = preg_replace('/;/',',',$bisectors[$i+6]);
        $args = $args."text([{$abPtLabLoc[$i][0]},{$abPtLabLoc[$i][1]}],'".$bisectors[($i+6)]."');";
        // Where to label the bisector
        if ($ang[($i+1)%3] > $ang[($i+2)%3]) {
          $abLabLoc[$i] = [$abMid[$i][0]+$xyDiff/8*($x[($i+1)%3]-$x[$i])/sqrt(pow($x[($i+1)%3]-$x[$i],2)+pow($y[($i+1)%3]-$y[$i],2)),$abMid[$i][1]+$xyDiff/8*($y[($i+1)%3]-$y[$i])/sqrt(pow($x[($i+1)%3]-$x[$i],2)+pow($y[($i+1)%3]-$y[$i],2))];
        } elseif ($ang[($i+1)%3] <= $ang[($i+2)%3]) {
          $abLabLoc[$i] = [$abMid[$i][0]+$xyDiff/8*($x[$i]-$x[($i+1)%3])/sqrt(pow($x[$i]-$x[($i+1)%3],2)+pow($y[$i]-$y[($i+1)%3],2)),$abMid[$i][1]+$xyDiff/8*($y[$i]-$y[($i+1)%3])/sqrt(pow($x[$i]-$x[($i+1)%3],2)+pow($y[$i]-$y[($i+1)%3],2))];
        }
        // Labels the bisector
        $args = $args."text([{$abLabLoc[$i][0]},{$abLabLoc[$i][1]}],'".$bisectors[$i+3]."');";
      }
    }
  }
  
  // DRAW MEDIANS
  if ($hasMedian === true) {
    $medians = array_slice($argsArray[$medianKey],1,9);
    for ($i=1;$i<10;$i++) {
      if (!isset($medians[$i])) {
        $medians[$i] = '';
      }
    }
    $medRat = $xyDiff/5;
    for ($i=0;$i<3;$i++) {
      if ($medians[$i]==1) {
        $args = $args."line([{$x[($i+2)%3]},{$y[($i+2)%3]}],[$xMid[$i],$yMid[$i]]);dot([$xMid[$i],$yMid[$i]]);";
        $medPtLabLoc[$i] = [$xMid[$i] - $medRat*($y[$i]-$yMid[$i])/sqrt(pow($xMid[$i]-$x[$i],2)+pow($yMid[$i]-$y[$i],2)),$yMid[$i] + $medRat*($x[$i]-$xMid[$i])/sqrt(pow($xMid[$i]-$x[$i],2)+pow($yMid[$i]-$y[$i],2))];
        // "Midpoints" of the medians
        $medianMid[$i] = [($x[($i+2)%3]+2*$xMid[$i])/3,($y[($i+2)%3]+2*$yMid[$i])/3];
        // Where to label the median
        if ($ang[($i+1)%3] > $ang[($i+2)%3]) {
          $medianLabLoc[$i] = [$medianMid[$i][0]+$xyDiff/8*($y[$i]-$y[($i+2)%3])/sqrt(pow($xMid[$i]-$x[($i+2)%3],2)+pow($yMid[$i]-$y[($i+2)%3],2)),$medianMid[$i][1]-$xyDiff/8*($xMid[$i]-$x[($i+2)%3])/sqrt(pow($xMid[$i]-$x[($i+2)%3],2)+pow($yMid[$i]-$y[($i+2)%3],2))];
        } elseif ($ang[($i+1)%3] <= $ang[($i+2)%3]) {
          $medianLabLoc[$i] = [$medianMid[$i][0]-$xyDiff/8*($y[$i]-$y[($i+2)%3])/sqrt(pow($xMid[$i]-$x[($i+2)%3],2)+pow($yMid[$i]-$y[($i+2)%3],2)),$medianMid[$i][1]+$xyDiff/8*($xMid[$i]-$x[($i+2)%3])/sqrt(pow($xMid[$i]-$x[($i+2)%3],2)+pow($yMid[$i]-$y[($i+2)%3],2))];
        }
        $args = $args."text([{$medianLabLoc[$i][0]},{$medianLabLoc[$i][1]}],'".$medians[$i+3]."');";
      }
    }
    // Labels endpoints of medians
    for ($i=0;$i<3;$i++) {
      $medians[$i+6] = preg_replace('/;/',',',$medians[$i+6]);
      $args = $args."text([{$medPtLabLoc[$i][0]},{$medPtLabLoc[$i][1]}],'".$medians[($i+6)]."');";
    }
  }
  
  // DRAW ALTITUDES
  if ($hasAltitude === true) {
    $altitudes = array_slice($argsArray[$altitudeKey],1,9);
    for ($i=0;$i<9;$i++) {
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
      // "Midpoint" of altitude
      $altMid[$i] = [($altStart[$i][0]+2*$altEnd[$i][0])/3,($altStart[$i][1]+2*$altEnd[$i][1])/3];
      
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
        // Indices of points or minimum and maximum non-altitude angle
        if ($ang[(($i+1)%3)] > $ang[(($i+2)%3)]) {
          $maxNotAltInd = ($i)%3;
          $minNotAltInd = ($i+1)%3;
        } elseif ($ang[(($i+1)%3)] <= $ang[(($i+2)%3)]) {
          $maxNotAltInd = ($i+1)%3;
          $minNotAltInd = ($i)%3;
        }
        if ($ang[($i+1)%3] >= 90 || $ang[($i+2)%3] >= 90) {
          // Move altitude label on far left or right if outside the triangle.
          $altOtherSideLabel = -1;
        } else {$altOtherSideLabel = 1;}
        // Vector pointing away from maximum non-altitude angle (and parallel to side opposite angle $i)
        // (to keep the right angle box out of the way of the larger)
        $altSidVec[$i] = [($x[$minNotAltInd]-$x[$maxNotAltInd])/sqrt(pow($x[$minNotAltInd]-$x[$maxNotAltInd],2)+pow($y[$minNotAltInd]-$y[$maxNotAltInd],2)),($y[$minNotAltInd]-$y[$maxNotAltInd])/sqrt(pow($x[$minNotAltInd]-$x[$maxNotAltInd],2)+pow($y[$minNotAltInd]-$y[$maxNotAltInd],2))];
        // Drawing the right-angle box
        $altRat = $xyDiff/12;
        if ($ang[($i+1)%3] != 90 && $ang[($i+2)%3] != 90) {
          
          $altPerp[0] = [$altEnd[$i][0]-$altRat*$altVec[$i][0],$altEnd[$i][1]-$altRat*$altVec[$i][1]];
          $altPerp[1] = [$altPerp[0][0]+$altRat*$altSidVec[$i][0],$altPerp[0][1]+$altRat*$altSidVec[$i][1]];
          $altPerp[2] = [$altEnd[$i][0]+$altRat*$altSidVec[$i][0],$altEnd[$i][1]+$altRat*$altSidVec[$i][1]];
          $args = $args . "line([{$altPerp[0][0]},{$altPerp[0][1]}],[{$altPerp[1][0]},{$altPerp[1][1]}]);";
          $args = $args . "line([{$altPerp[1][0]},{$altPerp[1][1]}],[{$altPerp[2][0]},{$altPerp[2][1]}]);";
        }
        // Labels end point of altitude
        $altEndLab[$i] = [$altEnd[$i][0] + $xyDiff/6*$altVec[$i][0],$altEnd[$i][1] + $xyDiff/6*$altVec[$i][1]];
        $altitudes[$i+6] = preg_replace('/;/',',',$altitudes[$i+6]);
        $args = $args . "text([{$altEndLab[$i][0]},{$altEndLab[$i][1]}],'{$altitudes[$i+6]}');";
        // Labels the altitude
        $altLab[$i] = [$altMid[$i][0]+$xyDiff/7*$altOtherSideLabel*$altSidVec[$i][0],$altMid[$i][1]+$xyDiff/7*$altOtherSideLabel*$altSidVec[$i][1]];
        $args = $args . "text([{$altLab[$i][0]},{$altLab[$i][1]}],'{$altitudes[$i+3]}');";
      }
    }
    // Redefine window settings in case extended base and altitude intersect outside the standard window
    $toCheckx = [$x[0],$x[1],$x[2],$altEnd[0][0],$altEnd[1][0],$altEnd[2][0]];
    $toChecky = [$y[0],$y[1],$y[2],$altEnd[0][1],$altEnd[1][1],$altEnd[2][1]];
    $xmin = min($toCheckx);
    $xmax = max($toCheckx);
    $ymin = min($toChecky);
    $ymax = max($toChecky);
    $xyDiff = max(($xmax-$xmin),($ymax-$ymin))/2;
    $xminDisp = (($xmax + $xmin)/2-1.35*$xyDiff); //window settings
    $xmaxDisp = (($xmax + $xmin)/2+1.35*$xyDiff);
    $yminDisp = (($ymax + $ymin)/2-1.35*$xyDiff);
    $ymaxDisp = (($ymax + $ymin)/2+1.35*$xyDiff);
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
  if ($hasPoints === true) {
    $verRat = $xyDiff/6;
    $args = $args."dot([$x[0],$y[0]]);";
    $args = $args."dot([$x[1],$y[1]]);";
    $args = $args."dot([$x[2],$y[2]]);";
    // Labels the points
    $verLab = array_slice($argsArray[$pointKey],1,3);
    for ($i=0;$i<3;$i++) {
      if (!isset($verLab[$i])) {
        $verLab[$i] = '';
      }
      $verLab[$i] = preg_replace('/;/',',',$verLab[$i]);
    }
    
    $verLabLoc[0] = [$x[2] - $verRat*($xab[0]-$x[2])/sqrt(pow($xab[0]-$x[2],2)+pow($yab[0]-$y[2],2)),$y[2] - $verRat*($yab[0]-$y[2])/sqrt(pow($xab[0]-$x[2],2)+pow($yab[0]-$y[2],2))];
    $verLabLoc[1] = [$x[0] - $verRat*($xab[1]-$x[0])/sqrt(pow($xab[1]-$x[0],2)+pow($yab[1]-$y[0],2)),$y[0] - $verRat*($yab[1]-$y[0])/sqrt(pow($xab[1]-$x[0],2)+pow($yab[1]-$y[0],2))];
    $verLabLoc[2] = [$x[1] - $verRat*($xab[2]-$x[1])/sqrt(pow($xab[2]-$x[1],2)+pow($yab[2]-$y[1],2)),$y[1] - $verRat*($yab[2]-$y[1])/sqrt(pow($xab[2]-$x[1],2)+pow($yab[2]-$y[1],2))];
    for ($i=0;$i<3;$i++) {
      $args = $args."text([{$verLabLoc[$i][0]},{$verLabLoc[$i][1]}],'".$verLab[$i]."');";
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
  $gr = showasciisvg("setBorder(10);initPicture($xminDisp,$xmaxDisp,$yminDisp,$ymaxDisp);$args;",$size,$size);
  
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
  
  $randSides = $GLOBALS['RND']->rand(3,9);
  $isRegular = false;
  $hasPoints = false;
  $hasSides = false;
  $noRotate = false;
  $size = 300;
  $rotatePolygon = false;
  
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
    if ($in[0]=="sides") {
      $hasSides = true;
      $sideKey = $key;
    }
    if (in_array("rotate",$in)) {
      $rotatePolygon = true;
    }
    if ($in[0]=="size" && isset($in[1])) {
      $size = $in[1];
    }
    if (in_array("regular",$in)) {
      $isRegular = true;
    }
    if ($in[0]=="points") {
      $hasPoints = true;
      $pointKey = $key;
    }
    if (in_array("axes",$in)) {
      $args = "stroke='grey';line([-1.3,0],[1.3,0]);line([0,-1.3],[0,1.3]);stroke='black';";
    }
  }
  
  //number of sides
  $n = $argsArray[0][0];
  if (!is_numeric($n) || abs($n-round($n))>1E-9 || $n < 3 || $n > 50) {
    echo 'Eek! First argument must be a whole number of sides greater than 2 (maximum of 50).';
    return '';
  }
  
  // Draw the polygon
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
      $ang[] = $GLOBALS['RND']->rand($minAngle,$maxAngle)*M_PI/180;
    }
  }
  if ($rotatePolygon === true) {
    $randAng = $GLOBALS['RND']->rand(0,360)*M_PI/180;
  } else {
    $randAng = -($ang[0]/2+M_PI/2);
  }
  $x[0] = cos($randAng);
  $y[0] = sin($randAng);
  $args = $args . "path([[$x[0],$y[0]]";
  for ($i=0;$i<$n;$i++) {
    $partialSum[$i] = array_sum(array_slice($ang,0,$i));
    $x[$i] = cos($partialSum[$i] + $randAng);
    $y[$i] = sin($partialSum[$i] + $randAng);
    
    // Draw path through vertices
    $args = $args . ",[$x[$i],$y[$i]]";
    if ($hasPoints === true) {
      $xPointLab[$i] = 1.2*cos($partialSum[$i] + $randAng);
      $xPointLabPlus[$i] = 1.25*cos($partialSum[$i] + $randAng);
      $yPointLab[$i] = 1.2*sin($partialSum[$i] + $randAng);
      $yPointLabPlus[$i] = 1.25*sin($partialSum[$i] + $randAng);
      if (!isset($argsArray[$pointKey][1+$i])) {
        $argsArray[$pointKey][1+$i] = "";
      }
    }
  }
  $args = $args . ",[$x[0],$y[0]]]);";
  
  if ($hasSides === true) {
    $sidLab = array_slice($argsArray[$sideKey],1,$n);
    for ($i=0;$i<$n;$i++) {
      // Midpoint of ith side
      $midPt[$i] = [($x[$i]+$x[($i+1)%$n])/2,($y[$i]+$y[($i+1)%$n])/2];
      $sidLabLoc[$i] = [$midPt[$i][0]-0.15*($y[$i]-$y[($i+1)%$n])/sqrt(pow($x[$i]-$x[($i+1)%$n],2)+pow($y[$i]-$y[($i+1)%$n],2)),$midPt[$i][1]+0.15*($x[$i]-$x[($i+1)%$n])/sqrt(pow($x[$i]-$x[($i+1)%$n],2)+pow($y[$i]-$y[($i+1)%$n],2))];
      $args = $args . "text([{$sidLabLoc[$i][0]},{$sidLabLoc[$i][1]}],'".$sidLab[$i]."');";
    }
  }
  
  if ($hasPoints === true) {
    $pointLab = array_slice($argsArray[$pointKey],1);
    for ($i=0;$i<$n;$i++) {
      $pointLab[$i] = preg_replace('/;/',',',$pointLab[$i]);
      $args = $args . "dot([$x[$i],$y[$i]]);";
      $args = $args . "text([$xPointLabPlus[$i],$yPointLabPlus[$i]],'$pointLab[$i]');";
    }
  }
  $gr = showasciisvg("setBorder(10);initPicture(-1.5,1.5,-1.5,1.5);$args;",$size,$size);
  return $gr;
}

//--------------------------------------------draw_prismcubes()----------------------------------------------------

// draw_prismcubes("cubes,length,height,depth",["labels,lab1,lab2,lab3"],["size,length"])
// draw_prismcubes() will draw a cube with no labels.
// Options include:
// "cubes,length,height,depth" Draws a rectangular prism made of cubes that is length x height x depth.
// "labels,lab1,lab2,lab3" This labels the length, height and depth of the prism.
// "size,length" Sets the sqare image size to be length x length.

function draw_prismcubes() {
  $size = 300;
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
    if ($in[0]=="cubes") {
      $hasCubes = true;
    }
  }
  if ($hasCubes !== true || empty($argsArray)) {
    $argsArray[] = ["cubes",1,1,1];
  }

  foreach ($argsArray as $key => $in) {
    if ($in[0]=="cubes") {
      for ($i=1;$i<4;$i++) {
        if ($in[$i]<1 || abs($in[$i]-round($in[$i]))>1E-9) {
          echo "Eek! Sides must be whole numbers greater than zero.";
          return '';
        }
      }
      $length = $in[1];
      $height = $in[2];
      $depth = $in[3];
      if ($length > 20 || $height > 20 || $depth > 20) {
        echo 'Eek! Exceeded maximum of 20 for length, height or width.';
        return '';
      }
    }
    if ($in[0] == "size") {
      $size = $in[1];
    }
    if ($in[0]=="labels") {
      $labels = array_slice($in,1,3);
      for ($i=0;$i<3;$i++) {
        if (!isset($labels[$i])) {
          $labels[$i] = '';
        }
      }
    }
  }
  $xMax = $length+$depth/sqrt(2)+1;
  $yMax = $height+$depth/sqrt(2)+1;
  $xyMax = max($xMax,$yMax);
  $xyLabDiff = $xyMax/12;
  $xyMin = $xyMax/8;
  
  for ($i=0;$i<($height+1);$i++) {
    $args = $args . "line([0,$i],[$length,$i]);";
    $args = $args . "line([$length,$i],[$length+$depth/sqrt(2),$i+$depth/sqrt(2)]);";
  }
  for ($i=0;$i<($length+1);$i++) {
    $args = $args . "line([$i,0],[$i,$height]);";
    $args = $args . "line([$i,$height],[$i+$depth/sqrt(2),$height+$depth/sqrt(2)]);";
  }
  for ($i=0;$i<($depth+1);$i++) {
    $args = $args . "line([$i/sqrt(2),$height+$i/sqrt(2)],[$length+$i/sqrt(2),$height+$i/sqrt(2)]);";
    $args = $args . "line([$length+$i/sqrt(2),$i/sqrt(2)],[$length+$i/sqrt(2),$height+$i/sqrt(2)]);";
  }
  $args = $args . "line([$depth/sqrt(2),$height+$depth/sqrt(2)],[$length+$depth/sqrt(2),$height+$depth/sqrt(2)]);";
  $args = $args . "line([$length+$depth/sqrt(2),$depth/sqrt(2)],[$length+$depth/sqrt(2),$height+$depth/sqrt(2)]);";
  
  $args = $args . "text([$length/2,-$xyLabDiff],'$labels[0]');";
  $args = $args . "text([$length+$depth/sqrt(2)+$xyLabDiff,$depth/sqrt(2)+$height/2],'$labels[1]');";
  $args = $args . "text([$length+$xyLabDiff+0.5*$depth/sqrt(2),0.5*$depth/sqrt(2)],'$labels[2]');";
  
  $gr = showasciisvg("setBorder(10);initPicture(-$xyMin,1.1*$xyMax,-$xyMin,1.1*$xyMax);$args;",$size,$size);
  return $gr;
}


//--------------------------------------------draw_cylinder----------------------------------------------------

// draw_cylinder(diameter,height,[option1],[option2],...)
// draw_cylinder() draws a random cylinder with no labels.
// draw_cylinder(a,b) scale drawing of a cylinder with diameter "a" and height "b".
// Options are lists in quotes, including:
// "radius,[label]" Draws a radius on the top circle with optional label.
// "diameter,[label]" Draws a diameter on the top circle with optional label.
// "height,[label]" Labels the height of the cylinder.
// "fill,[percent]" Fills a specified percent of the cylinder. If 'percent' is omitted, fills a random percent.
// "size,length" Sets the size of the image to length x length.

function draw_cylinder() {
  $size = 300;
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
    if ($in[0] == "size") {
      if (is_numeric($in[1])) {
        $size = $in[1];
      }
    }
    if ($in[0] == "fill") {
      $hasFill = true;
      if (!is_numeric($in[1])) {
        $fillPercent = $GLOBALS['RND']->rand(20,80);
      } elseif (is_numeric($in[1])) {
        if ($in[1]<0 || $in[1]>100) {
          echo 'Eek! Fill percent must be between 0 and 100.';
          return '';
        }
        $fillPercent = $in[1];
      }
    }
  }
  
  if ((is_numeric($argsArray[0][0]) && !is_numeric($argsArray[1][0])) || (!is_numeric($argsArray[0][0]) && is_numeric($argsArray[1][0]))) {
    echo 'Warning! Gave diameter without height.';
  }
  if (is_numeric($argsArray[0][0]) && $argsArray[0][0]>0 && is_numeric($argsArray[1][0]) && $argsArray[1][0]>0) {
    $diameter = $argsArray[0][0];
    $height = $argsArray[1][0];
  } else {
    [$diameter,$height] = diffrands(3,8,2);
  }
  
  // Set the window dimensions
  $af = 4;
  $dim = max(1.5*$diameter/2,1.5*($height/2+$diameter/$af));
  
  // Fill cylinder
  $fillColor = 'black';
  if ($hasFill === true) {
    $fillHeight = $fillPercent/100*$height;
    $fillColor = 'slategray';
    $args = $args . "strokewidth=0; fill='lightblue'; fillopacity=0.5; plot(['$diameter/2*cos(t)','$diameter/$af*sin(t)-$height/2'],0,2*pi); plot(['$diameter/2*cos(t)','$diameter/$af*sin(t)-$height/2+$fillHeight'],0,2*pi); rect([-$diameter/2,-$height/2],[$diameter/2,-$height/2+$fillHeight]); strokewidth=1; fill='none'; stroke='steelblue'; plot(['$diameter/2*cos(t)','$diameter/$af*sin(t)-$height/2+$fillHeight'],0,2*pi); stroke='black';";
  }
  
  // Draw the cylinder
  $args = $args . "strokewidth=2.5; strokedasharray='4 4'; stroke='$fillColor'; plot(['$diameter/2*cos(t)','$diameter/$af*sin(t)-$height/2'],0,pi); stroke='black'; strokedasharray='1 0'; fill='none'; ellipse([0,$height/2],$diameter/2,$diameter/$af); fill='none'; line([-$diameter/2,-$height/2],[-$diameter/2,$height/2]); line([$diameter/2,-$height/2],[$diameter/2,$height/2]); plot(['$diameter/2*cos(t)','$diameter/$af*sin(t)-$height/2'],pi,2*pi);";
  
  // Draw and label the radius
  foreach ($argsArray as $in) {
    if ($in[0] == "radius") {
      $args = $args . "strokewidth=1; line([0,$height/2],[$diameter/2,$height/2]);";
      if (isset($in[1])) {
        $args = $args . "strokewidth=1; arc([$diameter/2,$height/2+1.5*$diameter/$af+$diameter/(10*$af)],[$diameter/4,$height/2+$diameter/(10*$af)],$diameter); text([$diameter/2,$height/2+1.5*$diameter/$af],'$in[1]',above);strokewidth=2;";
      }
    }
  }
  
  // Draw and label the diameter
  foreach ($argsArray as $in) {
    if ($in[0] == "diameter") {
      $args = $args . "strokewidth=1; line([-$diameter/2,$height/2],[$diameter/2,$height/2]);";
      if (isset($in[1])) {
        $args = $args . "strokewidth=1; arc([$diameter/2,$height/2+1.5*$diameter/$af+$diameter/(10*$af)],[$diameter/4,$height/2+$diameter/(10*$af)],$diameter); text([$diameter/2,$height/2+1.5*$diameter/$af],'$in[1]',above);strokewidth=2;";
      }
    }
  }
  
  // Label the height
  foreach ($argsArray as $in) {
    if ($in[0] == "height") {
      $args = $args . "text([$diameter/2,0],'$in[1]',right);";
    }
  }

  $gr = showasciisvg("setBorder(20);initPicture(-0.75*$dim,1.25*$dim,-$dim,$dim);$args",$size,$size);
  return $gr;
}
?>

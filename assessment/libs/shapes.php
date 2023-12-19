<?php

# Library name: shapes
# Functions for creating asciisvg for common shapes.  Version 1.  December 2020
# Contributors:  Nick Chura


global $allowedmacros;
array_push($allowedmacros,"draw_angle","draw_circle","draw_circlesector","draw_square","draw_rectangle","draw_triangle","draw_polygon","draw_prismcubes","draw_cylinder","draw_cone","draw_sphere","draw_pyramid","draw_rectprism","draw_polyomino");

//--------------------------------------------draw_angle()----------------------------------------------------

// draw_angle("measurement,[label],[noarc]","rotate","axes")
// You must include at least the angle's measurement to define the angle, and "label" is optional for the angle.
// Angle arc is shown by default. Put noarc in the last entry to not show the angle arc.
// Note: Angle must be in degrees. In the label, the degree symbol is shown by default. To not show the degree symbol, use "rad" as in "57, 1 rad".
// Labels involving alpha, beta, gamma, theta, phi, pi or tau will display with those Greek letters.
// Options include:
// "rotate,[ang]" Rotates the image by ang degrees counterclockwise. Using just "rotate" will rotate by a random angle.
// "axes" Draws xy axes.
function draw_angle() {
  $input = func_get_args();
  if (empty($input)) {
    echo "Eek! Must include an angle to draw.";
    return '';
  }
  $argsArray = [];
  $args = '';
  $size = 300;
  $rot = 0;
  $ang = 0;
  $hasArc = false;
  $lab = "";
  $direction = "";
  $altLabel = "";
  $altTurnsLabel = "";
  $altAnother = "";
  $altDegSymbol = "";
  $hasUserAltText = false;
  foreach ($input as $list) {
    if (!is_array($list)) {
      $list = listtoarray($list);
    }
    $list = array_map('trim', $list);
    $argsArray[]=$list;
  }
  if (is_array($argsArray[0])) {
    if (!array_key_exists(0, $argsArray[0]) || !is_numeric($argsArray[0][0])) {
      echo "Eek! Must include an angle to draw.";
      return '';
    }
  } else {
    if (!is_numeric($argsArray[0])) {
      echo "Eek! Must include an angle to draw.";
      return '';
    }
  }

  foreach ($argsArray as $in) {
    if ($in[0] == "rotate") {
      if (isset($in[1])) {
        $rot = ($in[1]>=0) ? $in[1] : 360+$in[1];
      } elseif (!isset($in[1])) {
        $rot = $GLOBALS['RND']->rand(0,360);
      }
    }
    if ($in[0] == "size") {
      if (array_key_exists(1,$in)) {
        if (is_numeric($in[1])) {
      $size = $in[1];
    }
      }
    }
    if (in_array("axes",$in)) {
      $args = $args . "stroke='grey';line([-1.3,0],[1.3,0]);line([0,-1.3],[0,1.3]);stroke='black';";
    }
    if ($in[0] == "alttext") {
      if (isset($in[1])) {
        $hasUserAltText = true;
        $userAltText = $in[1];
      }
    }
  }
  $rotRad = $rot*M_PI/180;
  if (is_numeric($argsArray[0][0])) {
    $ang = $argsArray[0][0];
    $hasArc = true;
    if (isset($argsArray[0][1])) {
      $lab = $argsArray[0][1];
      $degSymbol = "&deg;";
    }
    if (isset($argsArray[0][2])) {
      if ($argsArray[0][2] === "noarc") {$hasArc = false;}
    }
  }
  if (abs($ang)>=2000) {
    echo "Angle must be strictly between -2000 degrees and 2000 degrees.";
    return '';
  }
  $angRad = $ang*M_PI/180;
  $xStart = cos($rotRad);
  $yStart = sin($rotRad);
  $xEnd = cos($angRad+$rotRad);
  $yEnd = sin($angRad+$rotRad);
  
  // Draw the sides of the angle
  $args = $args."stroke='blue'; strokewidth=2;line([0,0],[$xStart,$yStart]);line([0,0],[$xEnd,$yEnd]);dot([0,0]);strokewidth=1; stroke='black';";
  
  // Label the angle
  $belowFactor = 1;
  $labFact = 0.3;
  $halfAngle = ($ang+$rot)/2;
  if ($hasArc === true) {
    $labFact += 0.09*(abs($ang/360));
  }
  $quadAng = (abs($ang+$rot)%90 != 0) ? (4*($ang+$rot<0 || ($ang+$rot)%360>270) + 1*ceil((($ang+$rot)/90))%4) : 0;
  $quadRot = (abs($rot)%90 != 0) ? (4*($rot<0) + 1*ceil($rot/90)) : 0;
  $labAngArray = diffarrays([1,2,3,4],[$quadAng,$quadRot]);
  $labAng = ($labAngArray[$GLOBALS['RND']->rand(0,count($labAngArray)-1)]*90-45)*M_PI/180;
  [$xLabLoc,$yLabLoc] = [$labFact*cos($labAng),$labFact*sin($labAng)];

  $altAngleLab = $lab;
  $greekSpelled = ["/alpha/","/beta/","/gamma/","/theta/","/phi/","/tau/","/pi/","/rad/"];
  $greekSymbol = ["&alpha;","&beta;","&gamma;","&theta;","&phi;","&tau;","&pi;",""];
  $altGreekSymbol = [" alpha"," beta"," gamma"," theta"," phi"," tau"," pi",""];
  if (!empty($lab)) {
    for ($j=0;$j<count($greekSpelled);$j++) {
      if (preg_match($greekSpelled[$j],$lab)) {
        $degSymbol = '';
        $lab = preg_replace($greekSpelled[$j],$greekSymbol[$j],$lab);
        $altAngleLab = preg_replace($greekSpelled[$j],$altGreekSymbol[$j],$altAngleLab);
      }
    }  
  } elseif (empty($lab)) {$degSymbol='';}
  $args = $args."text([$xLabLoc,$yLabLoc],'$lab$degSymbol');";
  
  // Draw the angle arc
  if ($hasArc === true) {
    if ($ang < 0) {
      [$xStartTmp,$yStartTmp] = [$xStart,$yStart];
      [$xStart,$yStart] = [$xEnd,$yEnd];
      [$xEnd,$yEnd] = [$xStartTmp,$yStartTmp]; 
    }
    // Starting position of the arc
    $arcRad = (abs($ang)<=90) ? 0.85 : 0.15;
    if (abs($ang)>=0) {
      $minAng = $rotRad;
      $maxAng = $rotRad+$angRad;
      if ($ang<0) {
        $minAng = $rotRad+$angRad;
        $maxAng = $rotRad;
      }
      if (abs($ang) > 10) {
        $args = $args."plot(['($arcRad+.09/(2*pi)*abs(t-$rotRad))*cos(t)','($arcRad+.09/(2*pi)*abs(t-$rotRad))*sin(t)'],$minAng,$maxAng);";
      }
    }
    if (abs($ang) <= 180) {
      $arrowOffset = (abs($ang)<=90) ? 0.2*abs($angRad) : 0.7;
    } elseif (abs($ang) > 180 && abs($ang) < 360) {
      $arrowOffset = (abs($ang)<=270) ? 0.15*abs($angRad) : 0.1*abs($angRad);
    } else {
      $arrowOffset = 0.7-0.01*abs($angRad);
    }
    $arrowWeight = 0;
    if ($angRad >= 0) {
      if ($ang > 10) {
        [$xArrowStart,$yArrowStart] = [($arcRad+.09/(2*M_PI)*abs($angRad))*cos($maxAng-$arrowOffset),($arcRad+.09/(2*M_PI)*abs($angRad))*sin($maxAng-$arrowOffset)];
        [$xArrowEnd,$yArrowEnd] = [($arcRad+.09/(2*M_PI)*abs($angRad))*cos($maxAng),($arcRad+.09/(2*M_PI)*abs($angRad))*sin($maxAng)];
      } else {
        $arrowWeight = 1;
        [$xArrowStart,$yArrowStart] = [($arcRad+.09/(2*M_PI)*abs($angRad))*cos($maxAng+0.3),($arcRad+.09/(2*M_PI)*abs($angRad))*sin($maxAng+0.3)];
        [$xArrowEnd,$yArrowEnd] = [($arcRad+.09/(2*M_PI)*abs($angRad))*cos($maxAng),($arcRad+.09/(2*M_PI)*abs($angRad))*sin($maxAng)];
        [$xArrowStart1,$yArrowStart1] = [($arcRad+.09/(2*M_PI)*abs($angRad))*cos($minAng-0.3),($arcRad+.09/(2*M_PI)*abs($angRad))*sin($minAng-0.3)];
        [$xArrowEnd1,$yArrowEnd1] = [($arcRad+.09/(2*M_PI)*abs($angRad))*cos($minAng),($arcRad+.09/(2*M_PI)*abs($angRad))*sin($minAng)];
      }
    } else {
      if ($ang < -10) {
        [$xArrowStart,$yArrowStart] = [($arcRad+.09/(2*M_PI)*abs($angRad))*cos($minAng+$arrowOffset),($arcRad+.09/(2*M_PI)*abs($angRad))*sin($minAng+$arrowOffset)];
        [$xArrowEnd,$yArrowEnd] = [($arcRad+.09/(2*M_PI)*abs($angRad))*cos($minAng),($arcRad+.09/(2*M_PI)*abs($angRad))*sin($minAng)];
      } else {
        $arrowWeight = 1;
        [$xArrowStart,$yArrowStart] = [($arcRad+.09/(2*M_PI)*abs($angRad))*cos($minAng-0.3),($arcRad+.09/(2*M_PI)*abs($angRad))*sin($minAng-0.3)];
        [$xArrowEnd,$yArrowEnd] = [($arcRad+.09/(2*M_PI)*abs($angRad))*cos($minAng),($arcRad+.09/(2*M_PI)*abs($angRad))*sin($minAng)];
        [$xArrowStart1,$yArrowStart1] = [($arcRad+.09/(2*M_PI)*abs($angRad))*cos($maxAng+0.3),($arcRad+.09/(2*M_PI)*abs($angRad))*sin($maxAng+0.3)];
        [$xArrowEnd1,$yArrowEnd1] = [($arcRad+.09/(2*M_PI)*abs($angRad))*cos($maxAng),($arcRad+.09/(2*M_PI)*abs($angRad))*sin($maxAng)];
      }
    }
    $args .= "marker='arrow'; strokewidth=$arrowWeight; line([$xArrowStart,$yArrowStart],[$xArrowEnd,$yArrowEnd]); strokewidth=1; marker='none';";
    if (abs($ang) <= 10) {
      $args .= "marker='arrow'; line([$xArrowStart1,$yArrowStart1],[$xArrowEnd1,$yArrowEnd1]); strokewidth=1; marker='none';";
    }
  }
  
  // Build alt text
  if ($hasUserAltText !== true) {
    $altRefLabel = "";
    $angRef = abs($ang)%360;
    $angTurns = floor(abs($ang)/360);
    if ($angTurns > 0) {
      $altAnother = " an additional";
      if ($angRef > 270 && $angRef < 360) {
        $altAnother = " additional";
      }
    }
    if ($angRef == 0 && $angTurns == 0) {
      $altRefLabel = " with zero rotation";
    }
    elseif ($angRef > 0 && $angRef < 90) {
      $altRefLabel = " less than one quarter of".$altAnother." rotation";
    }
    elseif ($angRef == 90) {
      $altRefLabel = " one quarter of".$altAnother." rotation";
    }
    elseif ($angRef > 90 && $angRef < 180) {
      $altRefLabel = " between one quarter and one half of".$altAnother." rotation";
    }
    elseif ($angRef == 180) {
      $altRefLabel = " one half of".$altAnother." rotation";
    }
    elseif ($angRef > 180 && $angRef < 270) {
      $altRefLabel = " between one half and three quarters of".$altAnother." rotation";
    }
    elseif ($angRef == 270) {
      $altRefLabel = " three quarters of".$altAnother." rotation";
    }
    elseif ($angRef > 270 && $angRef < 360) {
      $altRefLabel = " between three quarters and one".$altAnother." full rotation";
    }
    
    if (abs($angTurns) > 0) {
      $altTurnsAnd = "";
      if ($angRef > 0) {
        $altTurnsAnd = " and also ";
      }
      $howManyTurns = "rotation" . $altTurnsAnd;
      if (abs($angTurns) > 1) {
        $howManyTurns = "rotations" . $altTurnsAnd;
      }
      $altTurnsLabel = " ".numtowords($angTurns)." complete ".$howManyTurns;
    }
    if ($degSymbol == "&deg;") {
      $altDegSymbol = " degrees";
    }
    
    if ($ang > 0) {
      $direction = " turned counterclockwise";
    } elseif ($ang < 0) {
      $direction = " turned clockwise";
    }
    if (isset($argsArray[0][1])) {
      $altLabel = ", and labeled $altAngleLab$altDegSymbol";
    }
    $alt = "An angle".$direction.$altTurnsLabel.$altRefLabel.$altLabel.".";
  } else {
    $alt = $userAltText;
  }
  $gr = showasciisvg("setBorder(5);initPicture(-1.1,1.1,-1.1,1.1);$args",$size,$size,$alt);
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
  $size = 350;
  $degSymbol = "&deg;";
  $input = func_get_args();
  $argsArray = [];
  $numPts = 0;
  $rotAng = 0;
  $hasAxes = false;
  $hasAnd = false;
  $hasCenterLabel = false;
  $hasRadiusLabel = false;
  $hasDiameterLabel = false;
  $hasAngleLabel = false;
  $hasCenter = false;
  $hasAngle = false;
  $hasRadius = false;
  $hasDiameter = false;
  $hasPoint = [];
  $altAngForPt = [];
  $hasPointLabel = [];
  $altPointLab = [];
  $altDegSymbol = "";
  $altPointAndLabel = "";
  $altAxes = "";
  $altCenterLabel = "";
  $altRadius = "";
  $altRadiusLabel = "";
  $altDiameter = "";
  $altDiameterLabel = "";
  $altAngle = "";
  $altAngleLabel = "";
  $altCenter = "";
  $hasUserAltText = false;
  $angleBlock = false;
  $ang = 0;
  foreach ($input as $list) {
    if (!is_array($list)) {
      $list = listtoarray($list);
    }
    $list = array_map('trim', $list);
    $argsArray[]=$list;
  }
  foreach ($argsArray as $key => $in) {
    if (!array_key_exists(0,$in) || trim($in[0]) === "") {
      echo "Eek! draw_circle cannot start with an empty argument.";
      return '';
    }
    
    if ($in[0] == "size") {
      if (array_key_exists(1,$in)) {
        if (is_numeric($in[1])) {
            $size = $in[1];
        }
      }
    }

    if ($in[0]=="angle") {
      if (array_key_exists(1,$in)) {
        if (is_numeric($in[1])) {
          $ang = $in[1];
        } else {
            echo "Eek! Angle must be followed by a number.";
            return '';
        }
      } else {
        echo "Eek! Angle must be followed by a number.";
        return '';
      }
    }
    if ($in[0]=="angle" && $in[1]%360 > 315) {
      $angleBlock = true;
    }
    if (in_array("axes",$in)) {
      $hasAxes = true;
      $args = $args . "stroke='grey';line([-1.3,0],[1.3,0]);line([0,-1.3],[0,1.3]);stroke='black';";
    }
  }
  foreach ($argsArray as $key => $in) {
    if ($in[0] == "alttext") {
      if (isset($in[1])) {
        $hasUserAltText = true;
        $userAltText = $in[1];
      }
    }
    if ($in[0]=="center") {
      $hasCenter = true;
      $lab = "";
      if (isset($in[1]) && $ang !== 0) {
        $hasCenterLabel = true;
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
        $centerLab = $in[1];
        $lab = "text([$xCentLab,$yCentLab],'".$centerLab."');";
      }
      $args = $args."dot([0,0]);".$lab;
    }
    
    if ($in[0]=="radius") {
      $hasRadius = true;
      $lab = "";
      if (isset($in[1])) {
        $hasRadiusLabel = true;
        $radiusLab = $in[1];
        if ($angleBlock === true) {
          $lab = "text([0.5,0],'".$radiusLab."',above);";
        } elseif ($angleBlock !== true) {
          $lab = "text([0.5,0],'".$radiusLab."',below);";
        }
      }
      $args = $args."line([0,0],[1,0]);".$lab;
    }
    
    if ($in[0]=="diameter") {
      $hasDiameter = true;
      if (!isset($in[1])) {
        $in[1] = '';
      } elseif (isset($in[1])) {
        $hasDiameterLabel = true;
        $diameterLab = $in[1];
      }
      $args = $args."line([-1,0],[1,0]);text([0,0],'$diameterLab',below);";
    }
    
    if ($in[0]=="angle") {
      $hasAngle = true;
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
        $angArray[] = $ang;
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
          $hasAngleLabel = true;
          $angLab = $in[2];
          $altAngleLab = $in[2];
          $greekSpelled = ["/\s?alpha/","/\sbeta/","/\s?gamma/","/\s?theta/","/\s?phi/","/\s?tau/","/\s?pi/","/\s?rad/"];
          $greekSymbol = ["&alpha;","&beta;","&gamma;","&theta;","&phi;","&tau;","&pi;",""];
          $altGreekSymbol = [" alpha"," beta"," gamma"," theta"," phi"," tau"," pi",""];
          if (!empty($angLab)) {
            for ($j=0;$j<count($greekSpelled);$j++) {
              if (preg_match($greekSpelled[$j],$angLab)) {
                $degSymbol = '';
                $angLab = preg_replace($greekSpelled[$j],$greekSymbol[$j],$angLab);
                $altAngleLab = preg_replace($greekSpelled[$j],$altGreekSymbol[$j],$altAngleLab);
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
      if (array_key_exists(1,$in)) {
        if (is_numeric($in[1])) {
          $angForPt = $in[1];
          if ($angForPt > 360 || $angForPt < 0) {
          echo 'Eek! Point angle must be between 0 and 360.';
          return '';
        }
          $numPts += 1;
          $hasPoint[] = true;
        $altAngForPt[] = $in[1];
        $xPtLoc = cos(M_PI*$angForPt/180);
        $yPtLoc = sin(M_PI*$angForPt/180);
        $args = $args."dot([$xPtLoc,$yPtLoc]);";
      $minDiff = 7;
      if (isset($in[2])) {
        $hasPointLabel[] = true;
        $in[2] = str_replace(';',',',$in[2]);
        $altPointLab[] = $in[2];
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
      } elseif (!isset($in[2])) {
        $altPointLab[] = '';
        $hasPointLabel[] = false;
      }
        } else {
          echo "Eek! Point must be followed by a number of degrees.";
          return '';
    }
      } else {
        echo "Eek! Point must be followed by a number of degrees.";
        return '';
  }
  
      
      
      //if (!isset($in[1])) {
        //echo 'Warning! "point" must be followed by an angle in degrees.';
      //}
      //if (isset($in[1]) && is_numeric($in[1])) {
        //if ($in[1] > 360 || $in[1] < 0) {
          //echo 'Eek! Point angle must be between 0 and 360.';
          //return '';
        //}
        //$angForPt = $in[1];
        //$altAngForPt[] = $in[1];
        //$xPtLoc = cos(M_PI*$angForPt/180);
        //$yPtLoc = sin(M_PI*$angForPt/180);
        //$args = $args."dot([$xPtLoc,$yPtLoc]);";
      //}
      //$minDiff = 7;
      //if (isset($in[2])) {
        //$hasPointLabel[] = true;
        //$in[2] = str_replace(';',',',$in[2]);
        //$altPointLab[] = $in[2];
        //if (abs($angForPt%360) < $minDiff || abs($angForPt%360-360) < $minDiff) {
          //if ($angForPt%360 < 2*$minDiff) {
            //$rotAng = 5;
          //} else {
            //$rotAng = -5;
          //}
        //} elseif (abs($angForPt%360-90) < $minDiff) {
          //if ($angForPt%360>90) {
            //$rotAng = 7;
          //} else {
            //$rotAng = -7;
          //}
        //} elseif (abs($angForPt%360-180) < $minDiff) {
          //if ($angForPt%360>180) {
            //$rotAng = 5;
          //} else {
            //$rotAng = -5;
          //}
        //} elseif (abs($angForPt%360-270) < $minDiff) {
          //if ($angForPt%360>270) {
            //$rotAng = 7;
          //} else {
            //$rotAng = -7;
          //}
        //}
        //$xLabLoc = 1.25*cos(M_PI*($angForPt+$rotAng)/180);
        //$yLabLoc = 1.25*sin(M_PI*($angForPt+$rotAng)/180);
        //$args = $args . "text([$xLabLoc,$yLabLoc],'$in[2]');";
      //} elseif (!isset($in[2])) {
        //$altPointLab[] = '';
        //$hasPointLabel[] = false;
      //}
    }
  }
  
  // Build alt text
  if ($hasUserAltText !== true) {
    $altAxes = '';
    $altCenter = '';
    $altCenterLabel = '';
    $altRadius = '';
    $altRadiusLabel = '';
    $altDiameter = '';
    $altDiameterLabel = '';
    $altAngle = '';
    $altAngleLabel = '';
    $altPointAndLabel = '';
    $altAnd = ", and";
    $unlabeled = " unlabeled";
    if ($degSymbol == "&deg;") {
      $altDegSymbol = " degrees";
    }
    $hasAnd = false;
    if ($hasAxes === true) {
      $altAxes = " centered at the origin of the coordinate axes";
      $hasAnd = true;
    }
    if ($hasCenter === true) {
      if ($hasCenterLabel === true) {
        $unlabeled = "";
      }
      $altCenter = " with$unlabeled center point";
      if ($hasAnd === true) {
        $altCenter = $altAnd.$altCenter;
      }
      $hasAnd = true;
      if ($hasCenterLabel === true) {
        $altCenterLabel = " labeled $centerLab";
      }
    }
    
    if ($hasRadius === true) {
      $unlabeled = " unlabeled";
      if ($hasRadiusLabel === true) {
        $unlabeled = "";
      }
      $altRadius = " with$unlabeled radius drawn from the center to the right";
      if ($hasAnd === true) {
        $altRadius = $altAnd.$altRadius;
      }
      $hasAnd = true;
      if ($hasRadiusLabel === true) {
        $altRadiusLabel = " labeled $radiusLab";
      }
    }
    
    if ($hasDiameter === true) {
      $unlabeled = " unlabeled";
      if ($hasDiameterLabel === true) {
        $unlabeled = "";
      }
      $altDiameter = " with$unlabeled diameter drawn horizontally";
      if ($hasAnd === true) {
        $altDiameter = $altAnd.$altDiameter;
      }
      $hasAnd = true;
      if ($hasDiameterLabel === true) {
        $altDiameterLabel = " labeled $diameterLab";
      }
    }
    
    if ($hasAngle === true) {
      $unlabeled = " unlabeled";
      $quadrant = ["pointed directly to the right with zero rotation","less than one quarter of a rotation counterclockwise from the right","equal to one quarter of a rotation counterclockwise from the right","between one quarter and one half of a rotation counterclockwise from the right","equal to one half of a rotation counterclockwise from the right","between one half and three quarters of a rotation counterclockwise from the right","equal to three quarters of a rotation counterclockwise from the right","between three quarters and one full rotation counterclockwise from the right","of one full rotation counterclockwise from the right"];
      $altAngleIndex = 2*ceil(($ang%360)/90) - ceil(($ang%90-(1E-9))/90);
      $altAngleQuadrant = $quadrant[$altAngleIndex];
      
      if ($hasAngleLabel === true) {
        $unlabeled = "";
      }
      
      $altAngle = " with$unlabeled angle $altAngleQuadrant";
      if ($hasAnd === true) {
        $altAngle = $altAnd.$altAngle;
      }
      $hasAnd = true;
      if ($hasAngleLabel === true) {
        $altAngleLabel = " labeled $altAngleLab$altDegSymbol";
      }
    }
    
    for ($i=0;$i<$numPts;$i++) {
      
      $unlabeled = " unlabeled";
      $quadrant = [" on the circle at far right"," on the circle in the top-right quarter"," on the circle at the top"," on the circle in the top-left quarter"," on the circle at far left"," on the circle in bottom-left quarter"," on the circle at bottom"," on the circle in bottom-right quarter"];
      $altPointAngleIndex = 2*ceil(($altAngForPt[$i]%360)/90) - ceil(($altAngForPt[$i]%90-(1E-9))/90);
      $altPointAngleQuadrant = $quadrant[$altPointAngleIndex];
      if ($hasPointLabel[$i] === true) {
        $unlabeled = "";
      }
      $altPoint = " with$unlabeled point$altPointAngleQuadrant";
      if ($hasAnd === true) {
        $altPoint = $altAnd.$altPoint;
      }
      $hasAnd = true;
      $altPointLabel = '';
      if ($hasPointLabel[$i] === true) {
        $altPointLabel = " labeled $altPointLab[$i]";
      }
      $altPointAndLabel = $altPointAndLabel.$altPoint.$altPointLabel;
    }

    $alt = "A circle".$altAxes.$altCenter.$altCenterLabel.$altRadius.$altRadiusLabel.$altDiameter.$altDiameterLabel.$altAngle.$altAngleLabel.$altPointAndLabel.".";
  } else {
    $alt = $userAltText;
  }
  $gr = showasciisvg("setBorder(5);initPicture(-1.5,1.5,-1.5,1.5);$args",$size,$size,$alt);
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
  if (empty($input)) {
    echo "Eek! draw_circlesector cannot have an empty argument.";
    return '';
  }
  $argsArray = [];
  $args = '';
  $hasAxes = false;
  $hasPoint = false;
  $altNumPts = 0;
  $altPointLab = "";
  $hasUserAltText = false;
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
  $angleBlock = false;
  
  foreach ($argsArray as $key => $in) {
    if (!array_key_exists(0,$in)) {
      echo "Eek! draw_circlesector cannot start with an empty argument.";
      return '';
    }
    if (array_key_exists(0,$in) && trim($in[0]) === "") {
      echo "Eek! draw_circlesector cannot start with an empty argument.";
      return '';
    }
    if ($in[0]=="angle") {
      $angleKey = $key;
      if (!array_key_exists(1,$in)) {
        echo "Eek! \"angle\" must be followed by a number.";
        return '';
      } else {
        if (!is_numeric($in[1])) {
          echo "Eek! \"angle\" must be followed by a number.";
          return '';
        }
      }
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
    if ($in[0] == "alttext") {
      if (isset($in[1])) {
        $hasUserAltText = true;
        $userAltText = $in[1];
      }
    }
    
    if ($in[0]=="size" && isset($in[1])) {
      $size = $in[1];
    }
    
    if (in_array("axes",$in)) {
      $hasAxes = true;
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
      if (isset($in[2]) && $in[2] != '') {
        $altPointLabel = " labeled ".$in[2];
      }
      $args = $args . "text([".(1.2*$xAngPt).",".(1.2*$yAngPt)."],'$in[2]');";
      $hasPoint = true;
      $altNumPts = $altNumPts + 1;
      $altPtsArray[] = [$angPt,$in[2],$altPointLabel];
    }
    
    if ($in[0]=="center") {
      $hasCenter = true;
      $centerKey = $key;
      $in = preg_replace('/;/',',',$in);
      if (!isset($in[1])) {
        $in[1] = '';
      } elseif (isset($in[1])) {
        $altCenterLabel = $in[1];
      }
      if (max($angs) <= 180) {
        $lab = "text([0,0],'".$in[1]."',below);";
      } elseif (max($angs) > 180) {
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
        $altAngleLab = $in[2];
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
      } elseif (isset($in[1])) {
        $altRadiusLabel = $in[1];
      }
      // Really unnecessary way to replace pi with its Greek letter symbol
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
  
  // Build alt text
  if ($hasUserAltText !== true) {
    $altNumPts = $altNumPts + 1;
    
    if ($ang < 90) {
      $alt = "A sector less than one quarter of a full circle";
    } elseif ($ang == 90) {
      $alt = "A sector equal to one quarter of a circle";
    } elseif ($ang > 90 && $ang < 180) {
      $alt = "A sector between one quarter and one half of a circle";
    } elseif ($ang == 180) {
      $alt = "A sector equal to one half of a circle";
    } elseif ($ang > 180 && $ang < 270) {
      $alt = "A sector between one half and three quarters of a circle";
    } elseif ($ang == 270) {
      $alt = "A sector equal to three quarters of a circle";
    } elseif ($ang > 270 && $ang < 360) {
      $alt = "A sector between three quarters and a full circle";
    } elseif ($ang == 360) {
      $alt = "A complete circle";
    }
    if (isset($altAngleLab)) {
      $alt = $alt." and labeled ".$altAngleLab;
      if ($degSymbol != '') {
        $alt = $alt." degrees";
      }
    }
    if ($hasAxes === true) {
      $alt = $alt.", with angle centered on the coordinate axes";
    }
    if (isset($altCenterLabel)) {
      $alt = $alt.", with center point labeled ".$altCenterLabel;
    }
    if (isset($altRadiusLabel)) {
      $alt = $alt.", and with radius labeled ".$altRadiusLabel;
    }
    
    if ($hasPoint === true) {
      foreach ($altPtsArray as $altPt) {
        if ($altPt[0] == 0) {
          $altPointLoc = " at the far right point on the circle";
        } elseif ($altPt[0] < 90) {
          $altPointLoc = " in the top right quarter of the circle";
        } elseif ($altPt[0] == 90) {
          $altPointLoc = " at the top point on the circle";
        } elseif ($altPt[0] > 90 && $altPt[0] < 180) {
          $altPointLoc = " in the top left quarter of the circle";
        } elseif ($altPt[0] == 180) {
          $altPointLoc = " at the far left point on the circle";
        } elseif ($altPt[0] > 180 && $altPt[0] < 270) {
          $altPointLoc = " in the bottom left quarter of the circle";
        } elseif ($altPt[0] == 270) {
          $altPointLoc = " at the bottom point on the circle";
        } elseif ($altPt[0] > 270 && $altPt[0] < 360) {
          $altPointLoc = " on the bottom right quarter of the circle";
        } elseif ($altPt[0] == 360) {
          $altPointLoc = " at the far right point on the circle";
        }
        $alt = $alt.", and a point".$altPointLoc.$altPt[2];
      }
    }
    $alt .= ".";
  } else {
    $alt = $userAltText;
  }
  $gr = showasciisvg("setBorder(5);initPicture($minxyDisp,1.35,$minxyDisp,1.35);$args",$size,$size,$alt);
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
  $args = '';
  $hasBase = false;
  $hasHeight = false;
  $hasUserAltText = false;
  $baseKey = "";
  $heightKey = "";
  $pointKey = "";
  $altPoint = "";
  $altBase = "";
  $altHeight = "";
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
    if (empty($in) || count($in) == 0) {
      $in = ["",""];
    }
    if ($in[0] == "alttext") {
      if (isset($in[1])) {
        $hasUserAltText = true;
        $userAltText = $in[1];
      }
    }
    if ($in[0]=="size" && isset($in[1])) {
      $size = $in[1];
    }
    if ($in[0]=="base") {
      $baseKey = $key;
      $hasBase = true;
    }
    if ($in[0]=="height") {
      $heightKey = $key;
      $hasHeight = true;
    }
    if ($in[0]=="points") {
      $hasPoints = true;
      $pointKey = $key;
    }
  }
  
  //echo $baseKey . $argsArray[$baseKey][1];
  if (!isset($argsArray[$baseKey][1])) {
    $argsArray[$baseKey][1] = "";
  }
  //echo $baseKey . $argsArray[$baseKey][1];
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
    $altPointLoc = ["bottom left","bottom right","top right","top left"];
  }
  
  // Build alt text
  if ($hasUserAltText !== true) {
    $alt = "A square";
    $usedLab = array_filter($pointLab,'strlen');
    $countLab = count($usedLab);
    if ($countLab > 0) {
      $alt .= " with";
      $pointLabKeys = array_keys($usedLab);
      for ($i=0; $i<$countLab; $i++) {
        $altPoint .= ($countLab > 1 && $i == $countLab-1) ? " and" : "";
        $altPoint .= " a point at {$altPointLoc[$pointLabKeys[$i]]} labeled {$pointLab[$pointLabKeys[$i]]}";
        $altPoint .= ($countLab > 2 && $i < $countLab-1) ? "," : "";
      }
    }
    if ($hasBase === true) {
      $altBase = ($hasPoints === true) ? "," : "";
      $altBase .= " with base labeled $baseLab";
    }
    if ($hasHeight === true) {
      $altHeight = ($hasBase === true) ? ", and" : (($hasPoints === true) ? "," : "");
      $altHeight .= " with height labeled $heightLab";
    }
    $alt = $alt.$altPoint.$altBase.$altHeight.".";
  } else {
    $alt = $userAltText;
  }
  $gr = showasciisvg("setBorder(5);initPicture(-1.5,1.5,-1.5,1.5);rect([-1,-1],[1,1]);$args",$size,$size,$alt);
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
  $args = '';
  $altPoint = "";
  $hasBaseLab = false;
  $hasHeightLab = false;
  $altBase = "";
  $altHeight = "";
  $hasUserAltText = false;
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
    if (array_key_exists(0,$in)) {
    if ($in[0] == "alttext") {
      if (isset($in[1])) {
        $hasUserAltText = true;
        $userAltText = $in[1];
      }
    }
    if ($in[0]=="size" && isset($in[1])) {
      $size = $in[1];
    }
    if ($in[0]=="points") {
      $hasPoints = true;
    }
    if ($in[0]=="base") {
      $hasBase = true;
      $hasBaseLab = (!empty($in[2])) ? true : false;
    }
    if ($in[0]=="height") {
      $hasHeight = true;
      $hasHeightLab = (!empty($in[2])) ? true : false;
    }
    } else {
      echo "Eek! Cannot use an empty argument for draw_rectangle.";
      return '';
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
    for ($i=0;$i<4;$i++) {
      $pointLab[$i] = $argsArray[$pointKey][$i+1];
    }
  }

  $base = $argsArray[$baseKey][1];
  if (isset($argsArray[$baseKey][2])) {
  $baseLab = $argsArray[$baseKey][2];
  } else {
    $baseLab = "";
  }
  $height = $argsArray[$heightKey][1];
  if (isset($argsArray[$heightKey][2])) {
  $heightLab = $argsArray[$heightKey][2];
  } else {
    $heightLab = "";
  }
  
  //for ($i=0;$i<4;$i++) {
    //$pointLab[$i] = $argsArray[$pointKey][$i+1];
  //}

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
    $altPointLoc = ["bottom left","bottom right","top right","top left"];
  }
  
  $args = $args."text([0,".$ymin."],'".$baseLab."',below);";
  $args = $args."text([".$xmax.",0],'".$heightLab."',right);";

  $args = $args."rect([".$xmin.",".$ymin."],[".$xmax.",".$ymax."]);";
  
  // Build alt text
  $altPoint = '';
  if ($hasUserAltText !== true) {
    $alt = "A rectangle";
    if ($hasPoints ===  true) {
      $usedLab = array_filter($pointLab,'strlen');
    $countLab = count($usedLab);
    if ($countLab > 0) {
      $alt .= " with";
      if ($countLab > 0) {
        $pointLabKeys = array_keys($usedLab);
        for ($i=0; $i<$countLab; $i++) {
          $altPoint .= ($countLab > 1 && $i == $countLab-1) ? " and" : "";
          $altPoint .= " a point at {$altPointLoc[$pointLabKeys[$i]]} labeled {$pointLab[$pointLabKeys[$i]]}";
          $altPoint .= ($countLab > 2 && $i < $countLab-1) ? "," : "";
        }
      }
    }
    }

    if ($hasBaseLab === true) {
      $altBase = ($hasPoints === true && $hasHeightLab === false) ? ", and" : (($hasPoints === true && $hasHeightLab === true) ? "," : "");
      $altBase .= " with base labeled $baseLab";
    }
    if ($hasHeightLab === true) {
      $altHeight = ($hasBaseLab === true) ? ", and" : (($hasPoints === true) ? ", and" : "");
      $altHeight .= " with height labeled $heightLab";
    }
    $alt = $alt.$altPoint.$altBase.$altHeight.".";
  } else {
    $alt = $userAltText;
  }
  $gr = showasciisvg("setBorder(5);initPicture(-1.7,1.7,-1.7,1.7);$args",$size,$size,$alt);
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
  $rotateTriangleBy = 0;
  $randomTriangle = false;
  $hasBisector = false;
  $hasMedian = false;
  $hasPoints = false;
  $rotateTriangle = false;
  $hasArcs = false;
  $hasAltArcs = false;
  $hasMarks = false;
  $hasAltMarks = false;
  $hasAltitude = false;
  $hasSize = false;
  $sideKey = "";
  $bisectorKey = "";
  $pointKey = "";
  $medianKey = "";
  $altitudeKey = "";
  $hasAltSidLab = false;
  $hasUserAltText = false;
  $size = 350;
  $args = '';
  
  foreach ($argsArray as $key => $in) {
    if (empty($in) || count($in) == 0) {
      unset($argsArray[$key]);
    }
  }
  foreach ($argsArray as $in) {
    if ($in[0]=="angles") {
      $noAngles = false;
    }
    if ($in[0]=="sides") {
      $noSides = false;
    }
    if ($in[0]=="rotate") {
      $rotateTriangle = true;
      if (isset($in[1])) {
        if (is_numeric($in[1])) {
          $rotateTriangleBy = true;
          $rotateTriangleByAngle = ($in[1] >= 0) ? $in[1]%360 : 360+$in[1]%360;
        }
      }
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
      $hasBisector = true;
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
    if ($in[0] == "alttext") {
      if (isset($in[1])) {
        $hasUserAltText = true;
        $userAltText = $in[1];
      }
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
      if (is_numeric($bisectorKey)) {$bisectorKey = $bisectorKey + 1;}
      if (is_numeric($pointKey)) {$pointKey = $pointKey + 1;}
      if (is_numeric($medianKey)) {$medianKey = $medianKey + 1;}
      if (is_numeric($altitudeKey)) {$altitudeKey = $altitudeKey + 1;}
      $noAngles = false;
    }
    if ($rotateTriangle === true) {
      if ($rotateTriangleBy===true) {
        $rndNum = ($rotateTriangleByAngle + $ang[2])*M_PI/180 - M_PI/2;
      } elseif ($rotateTriangleBy === false) {
        $rndNum = $GLOBALS['RND']->rand(0,360)*M_PI/180;
      }
    } elseif ($rotateTriangle !== true) {
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
        } else {
          $sideMarkTmp = ["","",""];
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
        } else {
            $angArcTmp = ["","",""];
        }
        // These are the side lengths from which to build the angles
        $sid = array_slice($argsArray[$sideKey],1,3);
        for ($i=1; $i<4; $i++) {
          if (!isset($argsArray[$sideKey][$i]) || !is_numeric($argsArray[$sideKey][$i])) {
            $sid[$i-1] = 0;
          }
        }
        $sidLabTmp = array_slice($argsArray[$sideKey],4,3);
        $hasSidLabTmp = true;
        $angTmp = array_slice($argsArray[$angleKey],1,3);
        if (($sid[0]+$sid[1]<=$sid[2]) || ($sid[1]+$sid[2]<=$sid[0]) || ($sid[2]+$sid[0]<=$sid[1])) {
          echo 'Eek! No triangle possible with these side lengths.';
          return '';
        }
        $angS1 = 180*acos((pow($sid[0],2)-pow($sid[1],2)-pow($sid[2],2))/(-2*$sid[1]*$sid[2]))/(M_PI);
        $angS2 = 180*acos((pow($sid[1],2)-pow($sid[0],2)-pow($sid[2],2))/(-2*$sid[0]*$sid[2]))/(M_PI);
        $angS3 = 180*acos((pow($sid[2],2)-pow($sid[1],2)-pow($sid[0],2))/(-2*$sid[1]*$sid[0]))/(M_PI);

        $argsArray[$angleKey] = ["angles",$angS1,$angS2,$angS3,$angTmp[0]??'',$angTmp[1]??'',$angTmp[2]??'',$angArcTmp[0]??'',$angArcTmp[1]??'',$angArcTmp[2]??''];
        $argsArray[$sideKey] = ["sides",$sidLabTmp[0]??'',$sidLabTmp[1]??'',$sidLabTmp[2]??'',$sideMarkTmp[0]??'',$sideMarkTmp[1]??'',$sideMarkTmp[2]??''];
      }
    }
    
    // Has sides, but no angles
    if ($noSides === false && $noAngles === true) {
      $sid = array_slice($argsArray[$sideKey],1,3);
      for ($i=1; $i<4; $i++) {
        if (!isset($argsArray[$sideKey][$i]) || !is_numeric($argsArray[$sideKey][$i])) {
          $sid[$i-1] = 0;
        }
      }
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
          //$argsArray[$sideKey][$i] = '';
          $sideMarkTmp[$i] = '';
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
    if ($rotateTriangleBy === true) {
      $rndNum = $rotateTriangleByAngle*M_PI/180 + $angRad[2] - M_PI/2;
    } elseif ($rotateTriangleBy !== true) {
      $rndNum = $GLOBALS['RND']->rand(0,360)*M_PI/180;
    }
  }
  
  $hasPerp = false;
  $perpKey = "";
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
    $altDegSymbol[$i] = " degrees";
    if (preg_match('/rad/',$angLab[$i]) || ($hasPerp === true && $i == $perpKey && empty($arcTypeTmp[$i])) || $angLab[$i] == '') {
      $angLab[$i] = preg_replace('/rad/','',$angLab[$i]);
      $degSymbol[$i] = '';
      $altDegSymbol[$i] = '';
    }
    $greekSpelled = ["/alpha/","/beta/","/gamma/","/theta/","/phi/","/tau/","/pi/"];
    $greekSymbol = ["&alpha;","&beta;","&gamma;","&theta;","&phi;","&tau;","&pi;"];
    if (!empty($angLab[$i])) {
      for ($j=0;$j<count($greekSpelled);$j++) {
        if (preg_match($greekSpelled[$j],$angLab[$i])) {
          $degSymbol[$i] = '';
          $altDegSymbol[$i] = " degrees";
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
  if ($hasBisector === true) {
    $bisectors = array_slice($argsArray[$bisectorKey],1,9);
    for ($i=1;$i<10;$i++) {
      if (!isset($bisectors[$i]) || empty($bisectors[$i])) {
        $bisectors[$i] = "";
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
  } else {
    $bisectors = [0,0,0,0,0,0,0,0,0];
  }
  
  // DRAW MEDIANS
  if ($hasMedian === true) {
    $medians = array_slice($argsArray[$medianKey],1,9);
    for ($i=0;$i<9;$i++) {
      if (!isset($medians[$i]) || empty($medians[$i])) {
        $medians[$i] = "";
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
      if (!empty($medPtLabLoc[$i])) {
        $args = $args."text([{$medPtLabLoc[$i][0]},{$medPtLabLoc[$i][1]}],'".$medians[($i+6)]."');";
      }
    }
  } else {
    $medians = [0,0,0,0,0,0,0,0,0];
  }
  
  // DRAW ALTITUDES
  if ($hasAltitude === true) {
    $altitudes = array_slice($argsArray[$altitudeKey],1,9);
    for ($i=0;$i<9;$i++) {
      if (!isset($altitudes[$i])) {
        $altitudes[$i] = "";
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
      
      $startAltDash = ["",""];
      $endAltDash = ["",""];
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
  $markNum = ["|" => 1, "||" => 2, "|||" => 3, "none" => 0];
  if ($hasMarks === true) {
    for ($i=4; $i<7; $i++) {
      if (!isset($argsArray[$sideKey][$i]) || !in_array($argsArray[$sideKey][$i], array_keys($markNum))) {
        $argsArray[$sideKey][$i] = "none";
      }
    }
  }
  $markType = array_slice($argsArray[$sideKey],4,3);
  //for ($i=0;$i<3;$i++) {
    //if (!isset($markNum[$markType[$i]])) { // invalid mark
      //  $hasMarks = false; 
    //}
  //}

  

  if ($hasMarks === true) {
    // Half the length of the tick mark
    $markRat = $xyDiff/25;
  
    // Distances between tick marks
    $rMark = array(0,-$xyDiff/25,$xyDiff/25);
    for ($i=0;$i<3;$i++) {
      $hasAltMark[$i] = is_numeric($markNum[$markType[$i]]);

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
    if ($hasAltMark[0] === true || $hasAltMark[1] === true || $hasAltMark[2] === true) {
      $hasAltMarks = true;
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
  $arcNum = ["(" => 1, ")" => 1, "((" => 2, "))" => 2, "(((" => 3, ")))" => 3, "none" => 0];
  if ($hasArcs === true) {
    for ($i=7; $i<10; $i++) {
      if (!isset($argsArray[$angleKey][$i]) || !in_array($argsArray[$angleKey][$i], array_keys($arcNum))) {
        $argsArray[$angleKey][$i] = "none";
      }
    }
  }
    $arcType = array_slice($argsArray[$angleKey],7,3);
  
  if ($hasArcs === true) {
    $rArc = [0.18*$xyDiff,0.14*$xyDiff,0.10*$xyDiff];
    
    
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
    
    $hasAltArc = [false,false,false];
    // Draw arc(s) for angle 0
    if (isset($arcNum[$arcType[0]]) && is_numeric($arcNum[$arcType[0]])) {
      $hasAltArc[0] = true;
      for ($j=0;$j<$arcNum[$arcType[0]];$j++) {
        $arcStart = [$xf[2] + $rArc[$j],$yf[2]];
        $arcEnd = [$xf[2] + $rArc[$j]*cos($angRad[0]),$yf[2] + $rArc[$j]*sin($angRad[0])];
        $arcStartDisp = [cos($getBack)*$arcStart[0]-sin($getBack)*$arcStart[1],sin($getBack)*$arcStart[0]+cos($getBack)*$arcStart[1]];
        $arcEndDisp = [cos($getBack)*$arcEnd[0]-sin($getBack)*$arcEnd[1],sin($getBack)*$arcEnd[0]+cos($getBack)*$arcEnd[1]];
        $args = $args . "strokewidth=2;arc([{$arcStartDisp[0]},{$arcStartDisp[1]}],[{$arcEndDisp[0]},{$arcEndDisp[1]}]),$rArc[$j];strokewidth=1;";
      }
    }
    // Draw arc(s) for angle 1
    if (isset($arcNum[$arcType[1]]) && is_numeric($arcNum[$arcType[1]])) {
      $hasAltArc[1] = true;
      for ($j=0;$j<$arcNum[$arcType[1]];$j++) {
        $arcStart = [$xf[0] - $rArc[$j]*cos($angRad[1]),$yf[0] + $rArc[$j]*sin($angRad[1])];
        $arcEnd = [$xf[0] - $rArc[$j],$yf[0]];
        $arcStartDisp = [cos($getBack)*$arcStart[0]-sin($getBack)*$arcStart[1],sin($getBack)*$arcStart[0]+cos($getBack)*$arcStart[1]];
        $arcEndDisp = [cos($getBack)*$arcEnd[0]-sin($getBack)*$arcEnd[1],sin($getBack)*$arcEnd[0]+cos($getBack)*$arcEnd[1]];
        $args = $args . "strokewidth=2;arc([{$arcStartDisp[0]},{$arcStartDisp[1]}],[{$arcEndDisp[0]},{$arcEndDisp[1]}]),$rArc[$j];strokewidth=1;";
      }
    }
    // Draw arc(s) for angle 2
    if (isset($arcNum[$arcType[2]]) && is_numeric($arcNum[$arcType[2]])) {
      $hasAltArc[2] = true;
      for ($j=0;$j<$arcNum[$arcType[2]];$j++) {
        $arcStart = [$xf[1] - $rArc[$j]*cos($angRad[0]),$yf[1] - $rArc[$j]*sin($angRad[0])];
        $arcEnd = [$xf[1] + $rArc[$j]*cos($angRad[1]),$yf[1] - $rArc[$j]*sin($angRad[1])];
        $arcStartDisp = [cos($getBack)*$arcStart[0]-sin($getBack)*$arcStart[1],sin($getBack)*$arcStart[0]+cos($getBack)*$arcStart[1]];
        $arcEndDisp = [cos($getBack)*$arcEnd[0]-sin($getBack)*$arcEnd[1],sin($getBack)*$arcEnd[0]+cos($getBack)*$arcEnd[1]];
        $args = $args . "strokewidth=2;arc([{$arcStartDisp[0]},{$arcStartDisp[1]}],[{$arcEndDisp[0]},{$arcEndDisp[1]}]),$rArc[$j];strokewidth=1;";
      }
    }
    if ($hasAltArc[0] === true || $hasAltArc[1] === true || $hasAltArc[2] === true) {
      $hasAltArcs = true;
    }
  }
  // build the alt text
  $hasAltAngLab = false;
  if ($hasUserAltText !== true) {
    $alt = "A triangle.";
    $altAngStart = "";
    $hasAltAngLab = false;
    foreach ($angLab as $lab) {
      if (!empty($lab) || $hasAltArcs === true) {
        $altAngStart = " Going around counterclockwise,";
        $hasAltAngLab = true;
      }
    }
    foreach ($sidLab as $lab) {
      if (!empty($lab) || $hasAltMarks === true || $hasAltitude === true || $hasMedian === true || $hasBisector === true) {
        $altSidStart = " Going around counterclockwise,";
        $hasAltSidLab = true;
      }
    }
    $alt .= $altAngStart;
    
    for ($i=0;$i<3;$i++) {
        if ($hasArcs === true && !empty($arcNum[$arcType[$i]])) {
            $altArc[$i] = ($hasAltArc[$i] === true) ? " with ".$arcNum[$arcType[$i]]." hash mark" : "";
            $altArc[$i] .= ($arcNum[$arcType[$i]] > 1) ? "s" : "";
        } else {
            $altArc[$i] = '';
        }
        $altVerLab[$i] = (!empty($verLab[$i])) ? " at vertex ".$verLab[$i] : "";
        $altVerLabNoAngle[$i] = (!empty($verLab[$i])) ? " and its opposite vertex is labeled ".$verLab[$i] : "";
        $altMark[$i] = '';
        if ($hasMarks === true) {
            $altMark[$i] = (!empty($hasAltMark[$i])) ? " with ".$markNum[$markType[$i]]." hash mark" : "";
            $altMark[$i] .= ($markNum[$markType[$i]] > 1) ? "s" : "";
        }
        $altSidLab[$i] = (!empty($sidLab[$i])) ? " is labeled ".$sidLab[$i] : " is unlabeled";
    } 
    if ($hasAltAngLab === true || $hasAltArcs === true) {
      for ($i=0;$i<3;$i++) {
        if (!empty($angLab[$i])) {
          $alt .= ($i==0) ? " an " : " An ";
          $alt .= "angle".$altVerLab[$i].$altArc[$i]." is labeled ".$angLab[$i].$altDegSymbol[$i];
          if (!empty($sidLab[$i]) || !empty($hasAltMark[$i])) {
            $alt .= " and its opposite side".$altMark[$i].$altSidLab[$i];
          }
        } else if (empty($angLab[$i])) {
          $alt .= ($i==0) ? " an " : " An ";
          $alt .= "angle".$altVerLab[$i].$altArc[$i];
          $alt .= ($hasPerp && $i == $perpKey && empty($arcTypeTmp[$perpKey])) ? " has a right angle symbol" : " is unlabeled";
          if (!empty($sidLab[$i]) || !empty($hasAltMark[$i])) {
            $alt .= " and its opposite side".$altMark[$i].$altSidLab[$i];
          }
        }
        $alt .= ".";
        if ($hasAltitude === true) {
          if ($altitudes[$i] == 1) {
            $altAltLab[$i] = (!empty($altitudes[$i+3])) ? " labeled ".$altitudes[$i+3]." " : "";
            $altAltPtLab[$i] = (!empty($altitudes[$i+6])) ? " at point ".$altitudes[$i+6] : "";
            $alt .= " An altitude".$altAltLab[$i]." is drawn from that angle's vertex to the line containing its opposite side".$altAltPtLab[$i];
            $alt .= ($medians[$i] == 1 || $bisectors[$i] == 1) ? "," : ".";
            $alt .= (($medians[$i] == 1 || $bisectors[$i] == 1) && !($medians[$i] == 1 && $bisectors[$i] == 1)) ? " and " : " ";
          }
        }
        if ($hasMedian === true) {
          if ($medians[$i] == 1) {
            $altMedLab[$i] = (!empty($medians[$i+3])) ? " labeled ".$medians[$i+3]." " : "";
            $altMedPtLab[$i] = (!empty($medians[$i+6])) ? " at point ".$medians[$i+6] : "";
            $alt .= ($altitudes[$i] == 1) ? " a" : " A";
            $alt .= " median".$altMedLab[$i]." is drawn from that angle's vertex to its opposite side".$altMedPtLab[$i];
            $alt .= ($bisectors[$i] == 1) ? ", and" : ".";
          }
        }
        if ($hasBisector === true) {
          if ($bisectors[$i] == 1) {
            $altBisLab[$i] = (!empty($bisectors[$i+3])) ? " labeled ".$bisectors[$i+3]." " : "";
            $altBisPtLab[$i] = (!empty($bisectors[$i+6])) ? " at point ".$bisectors[$i+6] : "";
            $alt .= ($altitudes[$i] == 1 || $medians[$i] == 1) ? " an" : " An";
            $alt .= " angle bisector ".$altBisLab[$i]." is drawn from that angle's vertex to its opposite side".$altBisPtLab[$i];
            $alt .= ".";
          }
        }
      }
    } else {
      if ($hasAltSidLab === true || ($hasAltSidLab !== true && ($hasAltitude === true || $hasMedian === true || $hasBisector === true))) {
        $alt .= $altSidStart;
        for ($i=0;$i<3;$i++) {
          if (!empty($sidLab[$i])) {
            $alt .= ($i == 0) ? " a" : " A";
            $alt .= " side".$altMark[$i]." is labeled ".$sidLab[$i].$altVerLabNoAngle[$i];
          } else {
            $alt .= ($i == 0) ? " a" : " A";
            $alt .= " side".$altMark[$i]." is unlabeled".$altVerLabNoAngle[$i];
          }
          if ($hasPerp === true && $i == $perpKey) {
            $alt .= " with its opposite angle labeled with a right angle box";
          }
          $alt .= ".";
          if ($hasAltitude === true) {
            if ($altitudes[$i] == 1) {
              $altAltLab[$i] = (!empty($altitudes[$i+3])) ? " labeled ".$altitudes[$i+3]." " : "";
              $altAltPtLab[$i] = (!empty($altitudes[$i+6])) ? " at point ".$altitudes[$i+6] : "";
              $alt .= " An altitude".$altAltLab[$i]." is drawn from that side".$altAltPtLab[$i]." to its opposite vertex";
              $alt .= ($medians[$i] == 1 || $bisectors[$i] == 1) ? "," : ".";
              $alt .= (($medians[$i] == 1 || $bisectors[$i] == 1) && !($medians[$i] == 1 && $bisectors[$i] == 1)) ? " and " : " ";
            }
          }
          if ($hasMedian === true) {
            if ($medians[$i] == 1) {
              $altMedLab[$i] = (!empty($medians[$i+3])) ? " labeled ".$medians[$i+3]." " : "";
              $altMedPtLab[$i] = (!empty($medians[$i+6])) ? " at point ".$medians[$i+6] : "";
              if ($hasAltitude === true) {
              $alt .= ($altitudes[$i] == 1) ? " a" : " A";
              }
              $alt .= " median".$altMedLab[$i]." is drawn from that side".$altMedPtLab[$i]." to its opposite vertex";
              $alt .= ($bisectors[$i] == 1) ? ", and" : ".";
            }
          }
          if ($hasBisector === true) {
            if ($bisectors[$i] == 1) {
              $altBisLab[$i] = (!empty($bisectors[$i+3])) ? " labeled ".$bisectors[$i+3]." " : "";
              $altBisPtLab[$i] = (!empty($bisectors[$i+6])) ? " at point ".$bisectors[$i+6] : "";
              $alt .= ((isset($altitudes[$i]) && $altitudes[$i] == 1) || $medians[$i] == 1) ? " an" : " An";
              $alt .= " angle bisector ".$altBisLab[$i]." is drawn from that side".$altBisPtLab[$i]." to its opposite vertex";
              $alt .= ".";
            }
          }
        }
      } elseif ($hasPerp === true && $hasAltSidLab !== true && $hasAltAngLab !== true) {
        $alt .= " One angle is labeled with a right angle box.";
      }
    }
  } else {
    $alt = $userAltText;
  }
  $gr = showasciisvg("setBorder(10);initPicture($xminDisp,$xmaxDisp,$yminDisp,$ymaxDisp);$args;",$size,$size,$alt);
  return $gr;
}


//--------------------------------------------draw_polygon()----------------------------------------------------
// draw_polygon([number],[options])
// draw_polygon() will draw a random polygon from 3 to 9 sides.
// draw_polygon(n) will draw a polygon with n sides.
// Options include:
// "points,[lab1,lab2,...]" Draws points on the vertices with optional labels for vertices
// "regular" Makes the polygon regular.
// "rotate" Rotates the polygon to a random angle.
function draw_polygon() {
  
  $randSides = $GLOBALS['RND']->rand(3,9);
  $isRegular = false;
  $hasPoints = false;
  $hasSides = false;
  $noRotate = false;
  $hasSideLab = false;
  $hasPointLab = false;
  $size = 300;
  $rotatePolygon = false;
  $hasUserAltText = false;
  
  $input = func_get_args();
  $argsArray = [];
  $args = '';
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
    array_unshift($argsArray,[$randSides]);
  }
  foreach ($argsArray as $key => $in) {
    if ($in[0] == "alttext") {
      if (isset($in[1])) {
        $hasUserAltText = true;
        $userAltText = $in[1];
      }
    }
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
  // keep track of which points and sides are labeled
  $pointLabKeys = [];
  $sideLabKeys = [];
  if ($hasSides === true) {
    $sidLab = array_slice($argsArray[$sideKey],1);
    for ($i=0;$i<$n;$i++) {
      if (empty($sidLab[$i]) || !isset($sidLab[$i])) {
        $sideLabKeys[$i] = 0;
      } else {
        $sideLabKeys[$i] = 1;
      }
    }
    if (in_array(1,$sideLabKeys)) {
      $hasSideLab = true;
    }
    for ($i=0;$i<$n;$i++) {
      // Midpoint of ith side
      $midPt[$i] = [($x[$i]+$x[($i+1)%$n])/2,($y[$i]+$y[($i+1)%$n])/2];
      $sidLabLoc[$i] = [$midPt[$i][0]-0.15*($y[$i]-$y[($i+1)%$n])/sqrt(pow($x[$i]-$x[($i+1)%$n],2)+pow($y[$i]-$y[($i+1)%$n],2)),$midPt[$i][1]+0.15*($x[$i]-$x[($i+1)%$n])/sqrt(pow($x[$i]-$x[($i+1)%$n],2)+pow($y[$i]-$y[($i+1)%$n],2))];
      $args .= (isset($sidLab[$i])) ? "text([{$sidLabLoc[$i][0]},{$sidLabLoc[$i][1]}],'".$sidLab[$i]."');" : "";
    }
  }
  if ($hasPoints === true) {
    $pointLab = array_slice($argsArray[$pointKey],1);
    for ($i=0;$i<$n;$i++) {
      if (empty($pointLab[$i]) || !isset($pointLab[$i])) {
        $pointLabKeys[$i] = 0;
      } else {
        $pointLabKeys[$i] = 1;
      }
    }
    if (in_array(1,$pointLabKeys)) {
      $hasPointLab = true;
    }
    for ($i=0;$i<$n;$i++) {
      $pointLab[$i] = preg_replace('/;/',',',$pointLab[$i]);
      $args = $args . "dot([$x[$i],$y[$i]]);";
      $args = $args . "text([$xPointLabPlus[$i],$yPointLabPlus[$i]],'$pointLab[$i]');";
    }
  }
  // build the alt text
  if ($hasUserAltText !== true) {
    $alt = "A polygon with ".$n." sides";
    $alt .= ($hasPoints === true) ? " and points drawn on its vertices. " : ". ";
    if ($hasPointLab === true) {
      $alt .= "Going counterclockwise around the polygon,";
      for ($i=0;$i<$n;$i++) {
        $alt .= ($pointLabKeys[$i] == 0) ? " a vertex is unlabeled" : " a vertex is labeled ".$pointLab[$i];
        if ($i < $n-2) {
          $alt .= ",";
        } elseif ($i == $n-2) {
          $alt .= ", and";
        }
      }
      $alt .= ".";
    }
    if ($hasSideLab === true) {
      $alt .= ($hasPointLab === true) ? " Beginning immediately counterclockwise from the first vertex, and going" : " Going";
      $alt .= " counterclockwise around the polygon,";
      for ($i=0;$i<$n;$i++) {
        $alt .= ($sideLabKeys[$i] == 0) ? " a side is unlabeled" : " a side is labeled ".$sidLab[$i];
        if ($i < $n-2) {
          $alt .= ",";
        } elseif ($i == $n-2) {
          $alt .= ", and";
        }
      }
      $alt .= ".";
    }
  } else {
    $alt = $userAltText;
  }
  $gr = showasciisvg("setBorder(10);initPicture(-1.5,1.5,-1.5,1.5);$args;",$size,$size,$alt);
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
  $args = '';
  $labels = ["","",""];
  $hasLabels = false;
  $hasUserAltText = false;

  foreach ($input as $list) {
    if (!is_array($list)) {
      $list = listtoarray($list);
    }
    $list = array_map('trim', $list);
    $argsArray[]=$list;
  }
  $hasCubes = false;
  foreach ($argsArray as $in) {
    if ($in[0]=="cubes") {
      $hasCubes = true;
    }
  }
  if ($hasCubes !== true || empty($argsArray)) {
    $argsArray[] = ["cubes",1,1,1];
  }

  foreach ($argsArray as $key => $in) {
    if ($in[0] == "alttext") {
      if (isset($in[1])) {
        $hasUserAltText = true;
        $userAltText = $in[1];
      }
    }
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
      //for ($i=1; $i<4; $i++) {
        //if (empty($in[$i]) || !isset($in[$i])) {
          //$in[$i] = "";
        //}
      //}
      $labels = array_slice($in,1,3);
      $edgeDescription = ["the front bottom edge of the prism from left to right is ","the rear vertical edge from top to bottom is ","the right bottom edge from front to back is "];
      $altLab = ["","",""];
      for ($i=0;$i<3;$i++) {
        if (!isset($labels[$i])) {
          $labels[$i] = '';
        }
        
        if (!empty($labels[$i])) {
          $altLab[$i] .= $edgeDescription[$i] . "labeled " . $labels[$i];
        } else {
          $altLab[$i] .= $edgeDescription[$i] . "unlabeled";
        }
      }
      if (!empty($altLab)) {
        $altLab = array_values($altLab);
        $altLabLen = count($altLab);
      }
      if ($altLabLen == 1) {
        $altLabEnd = ["","",""];
      } elseif ($altLabLen == 2) {
        $altLabEnd = [", and ","",""];
      } elseif ($altLabLen == 3) {
        $altLabEnd = [", ",", and ",""];
      }
      $altLabText = ucfirst($altLab[0].$altLabEnd[0].$altLab[1].$altLabEnd[1].$altLab[2].$altLabEnd[2]);
      $hasLabels = true;
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
  
  // build the alt text
  if ($hasUserAltText !== true) {
    $alt = "A rectangular prism";
    if ($hasCubes === true) {
      $alt .= " made of cubes that is ".$length." cubes long from left to right, ".$height." cubes tall from top to bottom, and ".$depth." cubes wide from front to back.";
    } else {
      $alt .= ".";
    }
    if ($hasLabels === true) {
      $alt .= " ".$altLabText.".";
    }
  } else {
    $alt = $userAltText;
  }
  $gr = showasciisvg("setBorder(10);initPicture(-$xyMin,1.1*$xyMax,-$xyMin,1.1*$xyMax);$args;",$size,$size,$alt);
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
  $args = '';
  $hasFill = false;
  $hasDiameter = false;
  $hasRadius = false;
  $hasRadiusLab = false;
  $hasHeight = false;
  $hasHeightLab = false;
  $hasUserAltText = false;
  $altFill = "";
  foreach ($input as $list) {
    if (!is_array($list)) {
      $list = listtoarray($list);
    }
    $list = array_map('trim', $list);
    $argsArray[]=$list;
  }
  foreach ($argsArray as $in) {
    if (array_key_exists(0,$in)) {
      if (empty($in[0])) {
        echo "Eek! Cannot use an empty argument for draw_cylinder.";
        return '';
      }
    if ($in[0] == "alttext") {
      if (isset($in[1])) {
        $hasUserAltText = true;
        $userAltText = $in[1];
      }
    }
    if ($in[0] == "size") {
        if (isset($in[1]) && is_numeric($in[1])) {
        $size = $in[1];
      }
    }
    if ($in[0] == "fill") {
      $hasFill = true;
      if (!isset($in[1]) || !is_numeric($in[1])) {
        $fillPercent = $GLOBALS['RND']->rand(20,80);
      } elseif (is_numeric($in[1])) {
        if ($in[1]<0 || $in[1]>100) {
          echo 'Eek! Fill percent must be between 0 and 100.';
          $fillPercent = $GLOBALS['RND']->rand(20,80);
        } else {
          $fillPercent = $in[1];
        }
        $altFill = ($fillPercent > 0) ? true : false;
      }
    }
    } else {
        echo "Eek! Cannot have empty arguments for draw_cylinder.";
        return '';
  }
  }
  if (isset($argsArray[0]) && isset($argsArray[0][0]) && isset($argsArray[1]) && isset($argsArray[1][0])) {
  if ((is_numeric($argsArray[0][0]) && !is_numeric($argsArray[1][0])) || (!is_numeric($argsArray[0][0]) && is_numeric($argsArray[1][0]))) {
      echo 'Warning! Diameter and height must both be numeric.';
      return '';
  }
  if (is_numeric($argsArray[0][0]) && $argsArray[0][0]>0 && is_numeric($argsArray[1][0]) && $argsArray[1][0]>0) {
    $diameter = $argsArray[0][0];
    $height = $argsArray[1][0];
    }
    if (!is_numeric($argsArray[0][0])) {
      [$diameter,$height] = diffrands(3,6,2);
    }
  } else {
    [$diameter,$height] = diffrands(3,8,2);
  }
  
  
  //if ((is_numeric($argsArray[0][0]) && !is_numeric($argsArray[1][0])) || (!is_numeric($argsArray[0][0]) && is_numeric($argsArray[1][0]))) {
    //echo 'Warning! Gave diameter without height.';
  //}
  //if (is_numeric($argsArray[0][0]) && $argsArray[0][0]>0 && is_numeric($argsArray[1][0]) && $argsArray[1][0]>0) {
    //$diameter = $argsArray[0][0];
    //$height = $argsArray[1][0];
  //} else {
    //[$diameter,$height] = diffrands(3,8,2);
  //}
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
      $hasRadius = true;
      $args = $args . "strokewidth=1; line([0,$height/2],[$diameter/2,$height/2]);";
      if (isset($in[1])) {
        $hasRadiusLab = true;
        $radiusLab = $in[1];
        $args = $args . "strokewidth=1; arc([$diameter/2,$height/2+1.5*$diameter/$af+$diameter/(10*$af)],[$diameter/4,$height/2+$diameter/(10*$af)],$diameter); text([$diameter/2,$height/2+1.5*$diameter/$af],'$in[1]',above);strokewidth=2;";
      }
    }
  }
  // Draw and label the diameter
  foreach ($argsArray as $in) {
    if ($in[0] == "diameter") {
      $hasDiameter = true;
      $args = $args . "strokewidth=1; line([-$diameter/2,$height/2],[$diameter/2,$height/2]);";
      if (isset($in[1])) {
        $hasDiameterLab = true;
        $diameterLab = $in[1];
        $args = $args . "strokewidth=1; arc([$diameter/2,$height/2+1.5*$diameter/$af+$diameter/(10*$af)],[$diameter/4,$height/2+$diameter/(10*$af)],$diameter); text([$diameter/2,$height/2+1.5*$diameter/$af],'$in[1]',above);strokewidth=2;";
      }
    }
  }
  // Label the height
  foreach ($argsArray as $in) {
    if ($in[0] == "height") {
      if (isset($in[1])) {
        $heightLab = $in[1];
        $hasHeightLab = true;
        $args = $args . "text([$diameter/2,0],'$in[1]',right);";
      }
    }
  }
  // build the alt text
  if ($hasUserAltText !== true) {
    $alt = "A circular cylinder";
    if ($hasRadius === true) {
      $alt .= " with the radius of its circular base drawn";
      if ($hasRadiusLab === true) {
        $alt .= " and labeled ".$radiusLab;
      }
    }
    if ($hasDiameter === true) {
      $alt .= ($hasRadius === true) ? ", " : "";
      $alt .= " with the diameter of its circular base drawn";
      if ($hasDiameterLab === true) {
        $alt .= " and labeled ".$diameterLab;
      }
    }
    if ($hasHeightLab === true) {
      $alt .= ($hasRadius === true || $hasDiameter === true) ? ", and" : "";
      $alt .= " with the height labeled ".$heightLab;
    }
    $alt .= ".";
    if ($altFill === true) {
      $alt .= " The cylinder is partially filled with colored material.";
    }
  } else {
    $alt = $userAltText;
  }
  $gr = showasciisvg("setBorder(20);initPicture(-0.75*$dim,1.25*$dim,-$dim,$dim);$args",$size,$size,$alt);
  return $gr;
}


//--------------------------------------------draw_cone----------------------------------------------------

// draw_cone(diameter,height,[option1],[option2],...)
// draw_cone() draws a random, right circular cone with no labels. Tip of cone is at the top.
// draw_cone(a,b) scale drawing of a cone with diameter "a" and height "b".
// Options are lists in quotes, including:
// "radius,[label]" draws a radius on the circular base with optional label.
// "diameter,[label]" draws a diameter on the circular base with optional label.
// "height,[label]" draws the height of the cone with optional label.
// "slant,label" labels the slant height of the cone.
// "fill,[percent]" fills the cone up to a specified percent of its height. If 'percent' is omitted, fills up to a random percent of the height.
// "invert" inverts the cone.
// "size,length" sets the size of the image to length x length.
function draw_cone() {
  $size = 300;
  $input = func_get_args();
  $argsArray = [];
  $args = '';
  $inverted = false;
  $hasFill = false;
  $hasDiameter = false;
  $hasDiameterLab = false;
  $hasRadius = false;
  $hasRadiusLab = false;
  $hasHeight = false;
  $hasHeightLab = false;
  $hasSlant = false;
  $fillPercent = 0;
  $hasUserAltText = false;

  foreach ($input as $list) {
    if (!is_array($list)) {
      $list = listtoarray($list);
    }
    $list = array_map('trim', $list);
    $argsArray[]=$list;
  }
  $inv = 1;
  foreach ($argsArray as $in) {
    if (array_key_exists(0,$in)) {
      if (empty($in[0])) {
        echo "Eek! Cannot start with an empty argument.";
        return '';
      }
    if ($in[0] == "alttext") {
      if (isset($in[1])) {
        $hasUserAltText = true;
        $userAltText = $in[1];
      }
    }
    if ($in[0] == "invert") {
      $inv = -1;
      $inverted = true;
    }
    if ($in[0] == "size") {
        if (isset($in[1]) && is_numeric($in[1])) {
        $size = $in[1];
      }
    }
    if ($in[0] == "fill") {
      $hasFill = true;
      if (!isset($in[1]) || !is_numeric($in[1])) {
        $fillPercent = $GLOBALS['RND']->rand(20,80);
      } elseif (is_numeric($in[1])) {
        if ($in[1]<0 || $in[1]>100) {
          $in[1] = 0;
          echo 'Eek! Fill percent must be between 0 and 100.';
        }
        $fillPercent = $in[1];
      }
    }
    } else {
      echo "Eek! Cannot have empty arguments for draw_cone.";
      return '';
  }
  }
  if (isset($argsArray[0]) && isset($argsArray[0][0]) && isset($argsArray[1]) && isset($argsArray[1][0])) {
  if ((is_numeric($argsArray[0][0]) && !is_numeric($argsArray[1][0])) || (!is_numeric($argsArray[0][0]) && is_numeric($argsArray[1][0]))) {
      echo 'Warning! Diameter and height must both be numeric.';
      return '';
  }
  if (is_numeric($argsArray[0][0]) && $argsArray[0][0]>0 && is_numeric($argsArray[1][0]) && $argsArray[1][0]>0) {
    $diameter = $argsArray[0][0];
    $height = $argsArray[1][0];
    }
    if (!is_numeric($argsArray[0][0])) {
      [$diameter,$height] = diffrands(3,6,2);
    }
  } else {
    [$diameter,$height] = diffrands(3,6,2);
  }
  
  // Set the window dimensions
  $af = 4;
  $dim = max(1.5*$diameter/2,1.5*($height/2));
  $invdash = 2+2*$inv;
  $invbelow = ($inv == 1) ? 'below' : 'above';
  $invabove = ($inv == 1) ? 'above' : 'below';

  // Fill cone
  $baseRad = ($dim/9 >= $diameter/2) ? 0.8*$diameter/2 : $dim/9;
  if ($inverted !== true) {
    if ($height > $baseRad) {
      [$t1,$t2] = [2*atan((0.5*(-1*sqrt(-4*pow($baseRad,2)+pow($height,2)+2*pow($height,2)+pow($height,2))+$height+$height))/$baseRad),2*atan((0.5*(sqrt(-4*pow($baseRad,2)+pow($height,2)+2*pow($height,2)+pow($height,2))+$height+$height))/$baseRad)];
    } else {
      [$t1,$t2] = [M_PI/2,M_PI/2];
    }
  } else {
    if ($height > $baseRad) {
      [$t1,$t2] = [-2*atan((0.5*(-1*sqrt(-4*pow($baseRad,2)+pow($height,2)+2*pow($height,2)+pow($height,2))+$height+$height))/$baseRad),M_PI+2*atan((0.5*(-1*sqrt(-4*pow($baseRad,2)+pow($height,2)+2*pow($height,2)+pow($height,2))+$height+$height))/$baseRad)];
    } else {
      [$t1,$t2] = [3*M_PI/2,3*M_PI/2];
    }
  }
  $fillColor = 'black';
  $fillHeight = -$height/2 + $fillPercent/100*$height;
  // points where lines of cone meet the bottom and top ellipses
  [$dashBRX,$dashBRY] = [$diameter/2*cos($t1),-1*$inv*$height/2+$baseRad*sin($t1)];
  [$dashBLX,$dashBLY] = [$diameter/2*cos($t2),-1*$inv*$height/2+$baseRad*sin($t2)];
  [$dashTRX,$dashTRY] = [$diameter/2*((1-2*$fillPercent/100)/2*($inv+1)+$fillPercent/100)*cos($t1),$baseRad*((1-2*$fillPercent/100)/2*($inv+1)+$fillPercent/100)*sin($t1)+$fillHeight];
  [$dashTLX,$dashTLY] = [$diameter/2*((1-2*$fillPercent/100)/2*($inv+1)+$fillPercent/100)*cos($t2),$baseRad*((1-2*$fillPercent/100)/2*($inv+1)+$fillPercent/100)*sin($t2)+$fillHeight];
  if ($hasFill === true && $fillPercent > 0) {
    if ($inverted) {
      $args .= "strokewidth=0; fill='lightblue'; fillopacity=0.5; plot(['$diameter/2*$fillPercent/100*cos(t)','$baseRad*$fillPercent/100*sin(t)+$fillHeight'],
      $t1,$t2); path([[0,-$height/2],[$dashTLX,$dashTLY],[$dashTRX,$dashTRY],[0,-$height/2]]); fill='none';";
      $args .= "strokewidth=1; fill='none'; stroke='steelblue'; plot(['$diameter/2*($fillPercent/100)*cos(t)','$baseRad*($fillPercent/100)*sin(t)+$fillHeight'],0,2*pi);";
    } else {
      $fillColor = "slategray";
      $args .= "strokewidth=0; fill='lightblue'; fillopacity=0.5; plot(['$diameter/2*cos(t)','$inv*(-$height/2+$baseRad*sin(t))'],pi-$t1,2*pi+$t1); plot(['$diameter/2*(1-$fillPercent/100)*cos(t)','$baseRad*(1-$fillPercent/100)*sin(t)+$inv*$fillHeight'],
      $t1,$t2); path([[$dashBRX,$dashBRY],[$dashBLX,$dashBLY],[$dashTLX,$dashTLY],[$dashTRX,$dashTRY],[$dashBRX,$dashBRY]]); fill='none';";
      $args .= "strokewidth=1; fill='none'; stroke='steelblue'; plot(['$diameter/2*(1-$fillPercent/100)*cos(t)','$baseRad*(1-$fillPercent/100)*sin(t)+$fillHeight'],0,2*pi);";
    }
  }
  // Draw the cone
  if ($height > $baseRad) {
    $args = $args . "strokewidth=2.5; strokedasharray='4 $invdash'; stroke='$fillColor'; plot(['$diameter/2*cos(t)','-1*$inv*$height/2 + $baseRad*sin(t)'],$t1,$t2); strokewidth=2.5; stroke='black'; strokedasharray='4 0'; fill='none'; line([$dashBRX,$dashBRY],[0,$inv*$height/2]); line([$dashBLX,$dashBLY],[0,$inv*$height/2]); plot(['$diameter/2*cos(t)','-1*$inv*$height/2+$baseRad*sin(t)'],$t2,2*pi+$t1);";
  } else {
    $args = $args . "strokewidth=0.5; stroke='black'; strokedasharray='4 0'; fill='none'; dot([0,$inv*$height/2]); strokewidth=2.5; plot(['$diameter/2*cos(t)','-1*$inv*$height/2+$baseRad*sin(t)'],0,2*pi);";
  }
  
  // Draw and label the radius
  foreach ($argsArray as $in) {
    if ($in[0] == "radius") {
      $hasRadius = true;
      $args = $args . "strokewidth=1; stroke='blue'; line([0,-1*$inv*$height/2],[$diameter/2,-1*$inv*$height/2]); stroke='black';";
      if (isset($in[1]) && !empty($in[1])) {
        $hasRadiusLab = true;
        $radiusLab = $in[1];
        $radArcs = [[0,-1*$inv*$height/2-1*$inv*$dim/2],[$diameter/4,$inv*(-1*$height/2-$dim/30)]];
        $args .= "strokewidth=1; stroke='blue'; marker='none'; arc([{$radArcs[(1-$inv)/2][0]},{$radArcs[(1-$inv)/2][1]}],[{$radArcs[(1+$inv)/2][0]},{$radArcs[(1+$inv)/2][1]}],$dim); text([0,$inv*(-1*$height/2-$dim/2)],'$in[1]',$invbelow); stroke='black';";
      }
    }
  }
  // Draw and label the diameter
  foreach ($argsArray as $in) {
    if ($in[0] == "diameter") {
      $hasDiameter = true;
      $args = $args . "strokewidth=1; stroke='blue'; line([-$diameter/2,-1*$inv*$height/2],[$diameter/2,-1*$inv*$height/2]); stroke='black';";
      if (isset($in[1]) && !empty($in[1])) {
        $hasDiameterLab = true;
        $diameterLab = $in[1];
        $radArcs = [[0,-1*$inv*$height/2-1*$inv*$dim/2],[$diameter/4,$inv*(-1*$height/2-$dim/30)]];
        $args .= "strokewidth=1; stroke='blue'; marker='none'; arc([{$radArcs[(1-$inv)/2][0]},{$radArcs[(1-$inv)/2][1]}],[{$radArcs[(1+$inv)/2][0]},{$radArcs[(1+$inv)/2][1]}],$dim); text([0,$inv*(-1*$height/2-$dim/2)],'$in[1]',$invbelow); stroke='black';";
      }
    }
  }
  // Draw and label the height
  foreach ($argsArray as $in) {
    if ($in[0] == "height") {
      $args .= "strokewidth=1; stroke='blue'; line([0,-$height/2],[0,$height/2]); stroke='black';";
      $hasHeight = true;
      $args .= "stroke='blue'; path([[0,$inv*(-1*$height/2+$dim/20)],[$dim/20,$inv*(-1*$height/2+$dim/20)],[$dim/20,$inv*(-1*$height/2)]]); stroke='black';";
      if (isset($in[1]) && !empty($in[1])) {
        $hasHeightLab = true;
        $heightLab = $in[1];
        $hRot = (strlen($in[1]) > 3) ? 270 : 0;
        if ($height >= $diameter/2) {
          $args .= "strokewidth=1; marker='arrow'; line([$diameter/2+$dim/6,0],[$diameter/2+$dim/6,-$height/2]); line([$diameter/2+$dim/6,0],[$diameter/2+$dim/6,$height/2]); text([$diameter/2+$dim/6,0],'$in[1]',right,$hRot); marker='none';";
        } else {
          $args .= "strokewidth=1; marker='arrow'; line([$diameter/2+$dim/6,$height/2+$dim/3],[$diameter/2+$dim/6,$height/2]); line([$diameter/2+$dim/6,-$height/2-$dim/3],[$diameter/2+$dim/6,-$height/2]); text([$diameter/2+$dim/6,0],'$in[1]',right,$hRot); marker='none';";
        }
        $args .= "marker='none'; line([$diameter/2+0.8*$dim/6,-$height/2],[$diameter/2+1.2*$dim/6,-$height/2]); line([$diameter/2+0.8*$dim/6,$height/2],[$diameter/2+1.2*$dim/6,$height/2]);";
      }
    }
  }
  // Label the slant height
  foreach ($argsArray as $in) {
    if ($in[0] == "slant") {
      if (isset($in[1]) && !empty($in[1])) {
        $args .= "strokewidth=1; line([-$diameter/4,0],[-$dim/2,$inv*$dim/4]); text([-$dim/2,$inv*$dim/4],'$in[1]',$invabove);";
        $hasSlant = true;
        $slantLab = $in[1];
      }
    }
  }
  // Build the alt text
  if ($hasUserAltText !== true) {
    $alt = "A right circular cone";
    if ($inverted !== true) {
      $alt .= " with circular base at bottom and tip at top";
    } else {
      $alt .= " with tip at bottom and circular base at top";
    }
    if ($hasRadius === true) {
      $alt .= " with its radius drawn";
      if ($hasRadiusLab === true) {
        $alt = $alt . " and labeled " . $radiusLab;
      }
    }
    if ($hasDiameter === true) {
      if ($hasRadius === true) {
        $alt .= ",";
      }
      $alt .= " with its diameter drawn";
      if ($hasDiameterLab === true) {
        $alt = $alt . " and labeled " . $diameterLab;
      }
    }
    if ($hasHeight === true) {
      if ($hasDiameter === true || $hasRadius === true) {
        $alt .= ",";
      }
      if (($hasDiameter === true || $hasRadius === true) && $hasSlant !== true) {
        $alt .= " and";
      }
      $alt .= " with its height drawn";
      if ($hasHeightLab === true) {
        $alt = $alt . " and labeled " . $heightLab;
      }
    }
    if ($hasSlant === true) {
      if ($hasDiameter === true || $hasRadius === true || $hasHeight === true) {
        $alt .= ", and";
      }
      $alt = $alt . " with its slant height labeled " . $slantLab;
    }
    $alt .= ".";
    if ($hasFill === true) {
      $alt .= " The cone is partially filled will colored material.";
    }
  } else {
    $alt = $userAltText;
  }
  $gr = showasciisvg("setBorder(20);initPicture(-$dim,$dim,(-1-1/4*$inv)*$dim,(1-1/4*$inv)*$dim);$args",$size,$size,$alt);
  return $gr;
}

//--------------------------------------------draw_sphere----------------------------------------------------

// draw_sphere([option1],[option2],...)
// draw_sphere() draws a sphere with no labels.
// Options are lists in quotes, including:
// "radius,[label]" draws a radius in the sphere with optional label.
// "diameter,[label]" draws a diameter in the sphere with optional label.
// "fill,[percent]" fills the sphere up to a specified percent of its height. If 'percent' is omitted, fills up to a random percent of the height.
// "size,length" sets the size of the image to length x length.
function draw_sphere() {
  $size = 300;
  $input = func_get_args();
  $argsArray = [];
  $args = '';
  $hasDiameter = false;
  $hasDiameterLab = false;
  $hasRadius = false;
  $hasRadiusLab = false;
  $hasFill = false;
  $hasUserAltText = false;

  foreach ($input as $list) {
    if (!is_array($list)) {
      $list = listtoarray($list);
    }
    $list = array_map('trim', $list);
    $argsArray[]=$list;
  }
  foreach ($argsArray as $in) {
    if (!array_key_exists(0,$in)) {
      echo "Eek! Cannot use empty arguments with draw_sphere.";
      return "";
    }
    if ($in[0] == "alttext") {
      if (isset($in[1])) {
        $hasUserAltText = true;
        $userAltText = $in[1];
      }
    }
    if ($in[0] == "size") {
      if (is_numeric($in[1])) {
        $size = $in[1];
      } else {
        $size = 300;
      }
    }
    if ($in[0] == "radius") {
      $hasRadius = true;
      if (isset($in[1]) && !empty($in[1])) {
        $radiusLab = $in[1];
        $hasRadiusLab = true;
      }
    }
    if ($in[0] == "diameter") {
      $hasDiameter = true;
      if (isset($in[1]) && !empty($in[1])) {
        $diameterLab = $in[1];
        $hasDiameterLab = true;
      }
    }
    if ($in[0] == "fill") {
      $hasFill = true;
      if (isset($in[1])) {
      if (!is_numeric($in[1])) {
        $fillPercent = $GLOBALS['RND']->rand(20,80);
      } elseif (is_numeric($in[1])) {
          if ($in[1]<0) {
            $fillPercent = 0;
          } elseif ($in[1]>100) {
            $fillPercent = 100;
          } else {
        $fillPercent = $in[1];
      }
    }
      } else {
        $fillPercent = $GLOBALS['RND']->rand(20,80);
        }
      $fillHeight = -1 + 2*$fillPercent/100;
    }
    
  }
  // Fill the sphere
  if ($hasFill === true) {
    if ($fillPercent > 0) {
      // x value where fill ellipse intersects sphere circle
      $xfill = sqrt(1-pow($fillHeight,2));
      // try to make fill ellipse grow or shrink to match view perspective
      $radFact = ($fillPercent >= 50) ? -1 : -0.5;
      $heightFactX = ($fillPercent >= 50) ? 0 : 0.08;
      $heightFactY = ($fillPercent <= 15) ? 1 : 0;
      $args = "fill='lightblue'; stroke='lightblue'; strokewidth=2; plot(['($xfill-$heightFactX*(50-$fillPercent)/100)*cos(t)','$fillHeight+(0.2*(1-$heightFactY*(0.8-0.7/15*$fillPercent))*(1+$radFact*abs($fillHeight)))*sin(t)'],-0.02,pi+0.02);";
      if ($fillPercent <= 50) {
        $args .= "arc([-1*$xfill,$fillHeight],[$xfill,$fillHeight],1); fill='none';";
      } elseif ($fillPercent > 50) {
        $args .= "arc([-1,0],[1,0],1); arc([1,0],[$xfill,$fillHeight],1); arc([-1*$xfill,$fillHeight],[-1,0],1); path([[1,0],[$xfill,$fillHeight],[-1*$xfill,$fillHeight],[-1,0],[$xfill,0]]); fill='none';";
      }
      $args .= "strokewidth=1; stroke='steelblue'; ellipse([0,$fillHeight],$xfill-$heightFactX*(50-$fillPercent)/100,0.2*(1-$heightFactY*(0.8-0.7/15*$fillPercent))*(1+$radFact*abs($fillHeight))); stroke='black';";
    }
  }
  // Draw the radius
  if ($hasRadius === true) {
    $args .= "stroke='blue'; line([0,0],[1,0]); stroke='black';";
    if ($hasRadiusLab === true) {
      $args .= "stroke='blue'; strokewidth=1; arc([0.5,-0.05],[1,-1],2); text([1,-1],'$radiusLab',below); stroke='black';";
    }
  }
  // Draw the diameter
  if ($hasDiameter === true) {
    $args .= "stroke='blue'; line([-1,0],[1,0]); stroke='black';";
    if ($hasDiameterLab === true) {
      $args .= "stroke='blue'; strokewidth=1; arc([0.5,-0.05],[1,-1],2); text([1,-1],'$diameterLab',below); stroke='black';";
    }
  }
  // Draw the sphere
  $args .= "strokewidth=2.5; strokedasharray='3 3'; stroke='gray'; plot(['cos(t)','0.2*sin(t)'],0,pi); strokedasharray='1 0'; stroke='black'; strokewidth=2.5; circle([0,0],1); plot(['cos(t)','0.2*sin(t)'],pi,2*pi);";
  // Build the alt text
  if ($hasUserAltText !== true) {
    $alt = "A sphere";
    if ($hasRadius === true) {
      $alt .= " with radius drawn";
      if ($hasRadiusLab === true) {
        $alt = $alt . " and labeled " . $radiusLab;
      }
    }
    if ($hasDiameter === true) {
      $alt .= " with diameter drawn";
      if ($hasDiameterLab === true) {
        $alt = $alt . " and labeled " . $diameterLab;
      }
    }
    if ($hasFill === true && $fillPercent > 0) {
      if ($hasRadius === true || $hasDiameter === true) {
        $alt .= ", and";
      }
      $alt = $alt . " filled partially with colored material";
    }
    $alt .= ".";
  } else {
    $alt = $userAltText;
  }
  $gr = showasciisvg("setBorder(20);initPicture(-1.5,1.5,-1.5,1.5);$args",$size,$size,$alt);
  return $gr;
}


//--------------------------------------------draw_pyramid----------------------------------------------------

// draw_pyramid(length,width,height,[option1],[option2],...)
// draw_pyramid() draws a random pyramid with rectangular base and no labels. Tip of pyramid is at the top.
// draw_pyramid(a,b,c) scale drawing of a pyramid with length "a", width "b" and height "c".
// Options are lists in quotes, including:
// "length,label" labels the front edge of the rectangular base.
// "width,label" labels the front edge of the rectangular base.
// "height,[label]" draws the height of the pyramid with optional label.
// "slant1,[label]" draws the slant height on the right face of the pyramid with optional label.
// "slant2,[label]" draws the slant height on the front face of the pyramid with optional label.
// "fill,[percent]" fills the pyramid up to a specified percent of its height. If 'percent' is omitted, fills up to a random percent of the height.
// "invert" inverts the pyramid.
// "size,length" sets the size of the image to length x length.
function draw_pyramid() {
  $size = 300;
  $input = func_get_args();
  $argsArray = [];
  $args = '';
  [$hasSlant1,$hasSlant2] = [false,false];
  $inverted = false;
  $hasLengthLab = false;
  $hasHeight = false;
  $hasWidthLab = false;
  $hasFill = false;
  $fillPercent = 0;
  $hasUserAltText = false;


  foreach ($input as $list) {
    if (!is_array($list)) {
      $list = listtoarray($list);
    }
    $list = array_map('trim', $list);
    $argsArray[]=$list;
  }
  $inv = 1;
  foreach ($argsArray as $in) {
    if ($in[0] == "alttext") {
      if (isset($in[1])) {
        $hasUserAltText = true;
        $userAltText = $in[1];
      }
    }
    if ($in[0] == "invert") {
      $inv = -1;
      $inverted = true;
    }
    if ($in[0] == "size") {
      if (is_numeric($in[1])) {
        $size = $in[1];
      }
    }
    if ($in[0] == "fill") {
      $hasFill = true;
      if (isset($in[1]) && !is_numeric($in[1])) {
        $fillPercent = $GLOBALS['RND']->rand(20,80);
      } elseif (isset($in[1]) && is_numeric($in[1])) {
        if ($in[1]<0 || $in[1]>100) {
          echo 'Eek! Fill percent must be between 0 and 100.';
          return '';
        }
        $fillPercent = $in[1];
      }
    }
  }
  [$length,$width,$height] = diffrands(3,8,3);
  if (array_key_exists(0,$argsArray) && array_key_exists(1,$argsArray) && array_key_exists(2,$argsArray)) {
  if ((is_numeric($argsArray[0][0]) && !(is_numeric($argsArray[1][0]) && is_numeric($argsArray[2][0]))) || (is_numeric($argsArray[1][0]) && !(is_numeric($argsArray[0][0]) 
  && is_numeric($argsArray[2][0]))) || (is_numeric($argsArray[2][0]) && !(is_numeric($argsArray[0][0]) && is_numeric($argsArray[1][0])))) {
    echo 'Warning! Must give length, width and height.';
  }
  if (is_numeric($argsArray[0][0]) && $argsArray[0][0]>0 && is_numeric($argsArray[1][0]) && $argsArray[1][0]>0 && is_numeric($argsArray[2][0]) && $argsArray[2][0]>0) {
    $length = $argsArray[0][0];
    $width = $argsArray[1][0];
    $height = $argsArray[2][0];
  }
  }
  // Set the window dimensions
  // Check for a good view angle with visAng
  $visAngs = [M_PI/10,M_PI/5];
  foreach ($visAngs as $key => $ang) {
    [$frxT,$fryT] = [$length/2-$width/2*cos($ang),-$height/2-$width/2*sin($ang)];
    [$ptxT,$ptyT] = [0,$height/2];
    [$cnxT,$cnyT] = [0,-$height/2];
    [$u1,$u2] = [$ptxT-$cnxT,$ptyT-$cnyT];
    [$w1,$w2] = [$frxT-$cnxT,$fryT-$cnyT];
    $cosAng[$key] = abs(($u1*$w1+$u2*$w2)/(sqrt(pow($u1,2)+pow($u2,2))*sqrt(pow($w1,2)+pow($w2,2))));
  }
  $keyMaxCosAng = array_keys($cosAng, min($cosAng));
  $visAng = $visAngs[$keyMaxCosAng[0]];
  $dim = max($length/2+$width*cos($visAng),$height/2+$width*sin($visAng));
  $invdash = 2-2*$inv;
  $invbelow = ($inv == 1) ? 'below' : 'above';
  $invabove = ($inv == 1) ? 'above' : 'below';
  $args = "";
  
  // Corners of pyramid (flx = frontleftx, frontrightx, etc.)
  if ($inv == 1) {
    [$flx,$fly] = [-$length/2-$width/2*cos($visAng),$inv*(-$height/2-$width/2*sin($visAng))];
    [$frx,$fry] = [$length/2-$width/2*cos($visAng),$inv*(-$height/2-$width/2*sin($visAng))];
    [$blx,$bly] = [-$length/2+$width/2*cos($visAng),$inv*(-$height/2+$width/2*sin($visAng))];
    [$brx,$bry] = [$length/2+$width/2*cos($visAng),$inv*(-$height/2+$width/2*sin($visAng))];
  } elseif ($inv == -1) {
    [$blx,$bly] = [-$length/2+$width/2*cos($visAng),$inv*(-$height/2-$width/2*sin($visAng))];
    [$brx,$bry] = [$length/2+$width/2*cos($visAng),$inv*(-$height/2-$width/2*sin($visAng))];
    [$flx,$fly] = [-$length/2-$width/2*cos($visAng),$inv*(-$height/2+$width/2*sin($visAng))];
    [$frx,$fry] = [$length/2-$width/2*cos($visAng),$inv*(-$height/2+$width/2*sin($visAng))];
  }
  [$ptx,$pty] = [0,$inv*$height/2];
  
  // Fill pyramid
  $fillColor = 'black';
  if ($hasFill === true && $fillPercent > 0) {
    if ($inv == 1) {
      [$fflx,$ffly] = [$flx+($ptx-$flx)*((1-1*$inv)/2+$inv*$fillPercent/100),$fly+($pty-$fly)*((1-1*$inv)/2+$inv*$fillPercent/100)];
      [$ffrx,$ffry] = [$frx+($ptx-$frx)*((1-1*$inv)/2+$inv*$fillPercent/100),$fry+($pty-$fry)*((1-1*$inv)/2+$inv*$fillPercent/100)];
      [$fbrx,$fbry] = [$brx+($ptx-$brx)*((1-1*$inv)/2+$inv*$fillPercent/100),$bry+($pty-$bry)*((1-1*$inv)/2+$inv*$fillPercent/100)];
      [$fblx,$fbly] = [$blx+($ptx-$blx)*((1-1*$inv)/2+$inv*$fillPercent/100),$bly+($pty-$bly)*((1-1*$inv)/2+$inv*$fillPercent/100)];
    } elseif ($inv == -1) {
      [$fflx,$ffly] = [$flx+($ptx-$flx)*((1-1*$inv)/2+$inv*$fillPercent/100),$fly+($pty-$fly)*((1-1*$inv)/2+$inv*$fillPercent/100)];
      [$ffrx,$ffry] = [$frx+($ptx-$frx)*((1-1*$inv)/2+$inv*$fillPercent/100),$fry+($pty-$fry)*((1-1*$inv)/2+$inv*$fillPercent/100)];
      [$fbrx,$fbry] = [$brx+($ptx-$brx)*((1-1*$inv)/2+$inv*$fillPercent/100),$bry+($pty-$bry)*((1-1*$inv)/2+$inv*$fillPercent/100)];
      [$fblx,$fbly] = [$blx+($ptx-$blx)*((1-1*$inv)/2+$inv*$fillPercent/100),$bly+($pty-$bly)*((1-1*$inv)/2+$inv*$fillPercent/100)];
    }
    if ($inv == 1) {
      $args .= "strokewidth=0; fill='lightblue'; path([[$fbrx,$fbry],[$fblx,$fbly],[$fflx,$ffly],[$flx,$fly],[$frx,$fry],[$brx,$bry],[$fbrx,$fbry]]);";
      if ($blx != $flx && $ptx != $flx) {
        if (($bly-$fly)*($ptx-$flx) > ($blx-$flx)*($pty-$fly)) {
          $args .= "path([[$flx,$fly],[$blx,$bly],[$fblx,$fbly],[$fflx,$ffly],[$flx,$fly]]);";
        }
      }
      if ($pty < $bly) {
        $args .= "path([[$blx,$bly],[$brx,$bry],[$fbrx,$fbry],[$fblx,$fbly],[$blx,$bly]]);";
      }
    } elseif ($inv == -1) {
      $args .= "strokewidth=0; fill='lightblue'; path([[$ptx,$pty],[$fflx,$ffly],[$fblx,$fbly],[$fbrx,$fbry],[$ptx,$pty]]);";
      if ($frx != $brx) {
        if (($bry-$pty)*($frx-$ptx) > ($fry-$pty)*($brx-$ptx)) {
          $args .= "path([[$ptx,$pty],[$ffrx,$ffry],[$fbrx,$fbry],[$ptx,$pty]]);";
        }
      }
    }
    $args .= "stroke='slategray'; strokewidth=1; path([[$fflx,$ffly],[$ffrx,$ffry],[$fbrx,$fbry],[$fblx,$fbly],[$fflx,$ffly]]); fill='none';";
  }
  // Draw the pyramid
  // Check for hidden edges
  // [leftfloor,backleftslant,backfloor,backrightslant]
  $hStroke = [0,0,0,0,0,0];
  // Left floor edge and back left slanted edge
  $blSlantStroke = 2.5;
  $lFloorStroke = 2.5;
  if ($blx != $flx && $ptx != $flx) {
    if ($inv == 1 && ($bly-$fly)*($ptx-$flx) < ($blx-$flx)*($pty-$fly)) {
      $hStroke[0] = 4;
      $lFloorStroke = 1.5;
      if ($pty > $bly) {
        $blSlantStroke = 1.5;
        $hStroke[1] = 4;
      }
    } elseif ($inv == -1) {
      $hStroke[1] = 4;
      $blSlantStroke = 1.5;
    }
  }
  // Rear floor edge
  $bFloorStroke = 2.5;
  if ($inv == 1 && $bry < $pty) {
    $hStroke[2] = 4;
    $bFloorStroke = 1.5;
  }
  // Rear right slant edge
  $brSlantStroke = 2.5;
  if ($inv == -1 && ($bry-$pty)*($frx-$ptx) > ($fry-$pty)*($brx-$ptx)) {
    $hStroke[3] = 4;
    $brSlantStroke = 1.5;
  }
  // Front left slant edge
  $flSlantStroke = 2.5;
  if ($inv == -1 && $fly < $pty) {
    $hStroke[4] = 4;
    $flSlantStroke = 1.5;
  }
  // Front right slant edge
  $frSlantStroke = 2.5;
  if ($inv == -1 && $pty > $fry && ($ptx <= $frx || ($pty-$fry)*($brx-$frx) > ($bry-$fry)*($ptx-$frx))) {
    $hStroke[5] = 4;
    $frSlantStroke = 1.5;
  }
  $args .= "strokewidth=2.5; strokedasharray='4 0'; stroke='$fillColor'; line([$flx,$fly],[$frx,$fry]); line([$frx,$fry],[$brx,$bry]);";
  $args .= "strokewidth=$lFloorStroke; strokedasharray='4 $hStroke[0]'; line([$blx,$bly],[$flx,$fly]); strokewidth=$blSlantStroke; strokedasharray='4 $hStroke[1]';
    line([$blx,$bly],[$ptx,$pty]); strokewidth=$bFloorStroke; strokedasharray='4 $hStroke[2]'; line([$blx,$bly],[$brx,$bry]); strokewidth=$brSlantStroke; strokedasharray='4 $hStroke[3]';
    line([$ptx,$pty],[$brx,$bry]); strokewidth=$flSlantStroke; strokedasharray='4 $hStroke[4]'; line([$ptx,$pty],[$flx,$fly]); strokewidth=$frSlantStroke; strokedasharray='4 $hStroke[5]';
    line([$ptx,$pty],[$frx,$fry]);";
  $hasSlant1 = false;
  $hasSlant2 = false;
  foreach ($argsArray as $in) {
    if ($in[0] == "length") {
      if (isset($in[1]) && !empty($in[1])) {
        $hasLengthLab = true;
        $lengthLab = $in[1];
        $args .= ($inv == 1) ? "text([($flx+$frx)/2,($fly+$fry)/2],'$lengthLab',below);" : "text([($blx+$brx)/2,($bly+$bry)/2],'$lengthLab',above);";
      }
    }
    if ($in[0] == "width") {
      if (isset($in[1]) && !empty($in[1])) {
        $hasWidthLab = true;
        $widthLab = $in[1];
        if ($inv == 1) {
          [$perpCentx,$perpCenty] = [($brx+$frx)/2+$fry-$bry,($bry+$fry)/2+$brx-$frx];
          [$wlabx,$wlaby] = [($frx+$brx)/2+$dim/8*(($frx+$brx)/2-$perpCentx)/sqrt(pow(($frx+$brx)/2-$perpCentx,2)+pow(($fry+$bry)/2-$perpCenty,2)),($fry+$bry)/2+$dim/8*(($fry+$bry)/2-$perpCenty)/sqrt(pow(($frx+$brx)/2-$perpCentx,2)+pow(($fry+$bry)/2-$perpCenty,2))];
        } elseif ($inv == -1) {
          [$perpCentx,$perpCenty] = [($blx+$flx)/2+$bly-$fly,($bly+$fly)/2+$flx-$blx];
          [$wlabx,$wlaby] = [($flx+$blx)/2+$dim/16*(($flx+$blx)/2-$perpCentx)/sqrt(pow(($flx+$blx)/2-$perpCentx,2)+pow(($fly+$bly)/2-$perpCenty,2)),($fly+$bly)/2+$dim/16*(($fly+$bly)/2-$perpCenty)/sqrt(pow(($flx+$blx)/2-$perpCentx,2)+pow(($fly+$bly)/2-$perpCenty,2))];
        }
        $args .= "text([$wlabx,$wlaby],'$widthLab','none',$visAng*180/3.14159);";
      }
    }
    // Draw and label the height
    if ($in[0] == "height") {
      $hasHeight = true;
      $args = $args . "stroke='blue'; strokedasharray='1 0'; strokewidth=1.5; line([0,-$height/2],[0,$height/2]); path([[$dim/20,$inv*-1*$height/2],[$dim/20,$inv*-1*$height/2+$inv*$dim/20],[0,$inv*-1*$height/2+$inv*$dim/20]]);";
      if (isset($in[1]) && !empty($in[1])) {
        $hasHeightLab = true;
        $heightLab = $in[1];
        $heightArrowFact = ($height >= 0.5*max($length,$width)) ? 0 : 1;
        $args .= "marker='arrow'; stroke='black'; line([-$dim,-1*$heightArrowFact*($height/2+$dim/4)],[-$dim,-$height/2]); line([-$dim,$heightArrowFact*($height/2+$dim/4)],[-$dim,$height/2]); text([-$dim,0],'$heightLab',right); marker='none'; line([-0.97*$dim,$height/2],[-1.03*$dim,$height/2]); line([-0.97*$dim,-$height/2],[-1.03*$dim,-$height/2]);";
      }
    }
    // Draw and label the right slant
    if ($in[0] == "slant1") {
      $hasSlant1 = true;
      // slant right base x, slant right base y
      [$s1bx,$s1by] = [($frx+$brx)/2,($fry+$bry)/2];
      $args .= "stroke='blue'; strokedasharray='1 0'; strokewidth=1.5; line([$s1bx,$s1by],[$ptx,$pty]);";
      // make the right angle symbol on the right slant
      [$sraax,$sraay] = [$s1bx+$dim/20*($ptx-1*$s1bx)/sqrt(pow($ptx-1*$s1bx,2)+pow($pty-1*$s1by,2)),$s1by+$dim/20*($pty-1*$s1by)/sqrt(pow($ptx-1*$s1bx,2)+pow($pty-1*$s1by,2))];
      [$srabx,$sraby] = [$s1bx+$dim/20*($brx-1*$s1bx)/sqrt(pow($brx-1*$s1bx,2)+pow($bry-1*$s1by,2)),$s1by+$dim/20*($bry-1*$s1by)/sqrt(pow($brx-1*$s1bx,2)+pow($bry-1*$s1by,2))];
      [$sracx,$sracy] = [$s1bx+$dim/20*($ptx-1*$s1bx)/sqrt(pow($ptx-1*$s1bx,2)+pow($pty-1*$s1by,2))+$dim/20*($brx-1*$s1bx)/sqrt(pow($brx-1*$s1bx,2)+pow($bry-1*$s1by,2)),
      $s1by+$dim/20*($pty-1*$s1by)/sqrt(pow($ptx-1*$s1bx,2)+pow($pty-1*$s1by,2))+$dim/20*($bry-1*$s1by)/sqrt(pow($brx-1*$s1bx,2)+pow($bry-1*$s1by,2))];
      
      $args .= "path([[$sraax,$sraay],[$sracx,$sracy],[$srabx,$sraby]]);";
      if (isset($in[1]) && !empty($in[1])) {
        $hasSlant1Lab = true;
        $slant1Lab = $in[1];
        if ($inv == 1) {
          $args .= "strokewidth=1; marker='none'; arc([($s1bx+$ptx)/2,($s1by+$pty)/2],[$dim/2,$height/2],2*$dim) ; text([$dim/2,$height/2],'$in[1]',above);";
        } elseif ($inv == -1) {
          $args .= "strokewidth=1; marker='none'; arc([$dim/2,-1*$height/2],[($s1bx+$ptx)/2,($s1by+$pty)/2],2*$dim) ; text([$dim/2,-1*$height/2],'$in[1]',below);";
        }
      }
    }
    // Draw and label the left slant
    if ($in[0] == "slant2") {
      $hasSlant2 = true;
      [$s2bx,$s2by] = [($flx+$frx)/2,($fly+$fry)/2];
      $args .= "stroke='blue'; strokedasharray='1 0'; strokewidth=1.5; line([$s2bx,$s2by],[$ptx,$pty]);";
      // make the right angle symbol on the front slant
      [$s2raax,$s2raay] = [$s2bx+$dim/20*($ptx-1*$s2bx)/sqrt(pow($ptx-1*$s2bx,2)+pow($pty-1*$s2by,2)),$s2by+$dim/20*($pty-1*$s2by)/sqrt(pow($ptx-1*$s2bx,2)+pow($pty-1*$s2by,2))];
      [$s2rabx,$s2raby] = [$s2bx+$dim/20*($flx-1*$s2bx)/sqrt(pow($flx-1*$s2bx,2)+pow($fly-1*$s2by,2)),$s2by+$dim/20*($fly-1*$s2by)/sqrt(pow($flx-1*$s2bx,2)+pow($fly-1*$s2by,2))];
      [$s2racx,$s2racy] = [$s2bx+$dim/20*($ptx-1*$s2bx)/sqrt(pow($ptx-1*$s2bx,2)+pow($pty-1*$s2by,2))+$dim/20*($flx-1*$s2bx)/sqrt(pow($flx-1*$s2bx,2)+pow($fly-1*$s2by,2)),
      $s2by+$dim/20*($pty-1*$s2by)/sqrt(pow($ptx-1*$s2bx,2)+pow($pty-1*$s2by,2))+$dim/20*($fly-1*$s2by)/sqrt(pow($flx-1*$s2bx,2)+pow($fly-1*$s2by,2))];

      $args .= "path([[$s2raax,$s2raay],[$s2racx,$s2racy],[$s2rabx,$s2raby]]);";
      if (isset($in[1]) && !empty($in[1])) {
        $hasSlant2Lab = true;
        $slant2Lab = $in[1];
        if ($inv == 1) {
          $args .= "strokewidth=1; marker='none'; arc([-$dim/2,$height/2+$dim/8],[($s2bx+$ptx)/2,($s2by+$pty)/2],2*$dim) ; text([-$dim/2,$height/2+$dim/8],'$slant2Lab',above);";
        } elseif ($inv == -1) {
          $args .= "strokewidth=1; marker='none'; arc([($s2bx+$ptx)/2,($s2by+$pty)/2],[-$dim/2,-1*($height/2+$dim/8)],2*$dim) ; text([-$dim/2,-1*($height/2+$dim/8)],'$slant2Lab',below);";
        }
      }
    }
  }
  // Build the alt text
  if ($hasUserAltText !== true) {
    $alt = "A pyramid";
    if ($inverted !== true) {
      $alt .= " with rectangular base at bottom and tip at top";
    } else {
      $alt .= " with tip at bottom and rectangular base at top";
    }
    if ($hasFill === true) {
      if ($fillPercent > 0 && $fillPercent < 100) {
        $alt .= ", filled partially with colored material";
      }
    }
    if ($hasLengthLab === true) {
      $frontedge = ($inv == 1) ? "front" : "rear";
      $alt .= ", with the ".$frontedge." edge of the base labeled ".$lengthLab;
    }
    if ($hasWidthLab === true) {
      $alt .= ($hasLengthLab === true) ? " and" : " with";
      $rightedge = ($inv == 1) ? "right" : "left";
      $alt .= " the ".$rightedge." edge of the base labeled ".$widthLab;
    }
    if ($hasHeight === true) {
      $alt .= ", with its height drawn";
      if ($hasHeightLab === true) {
        $alt = $alt . " and labeled " . $heightLab;
      }
    }
    if ($hasSlant1 === true) {
      if ($hasSlant1Lab === true) {
        $altSlant1Lab = " labeled " . $slant1Lab;
      }
      $alt .= ", with a slanted segment".$altSlant1Lab." that connects the tip of the pyramid to the right edge of the rectangular base at a right angle";
    }
    if ($hasSlant2 === true) {
      if ($hasSlant2Lab === true) {
        $altSlant2Lab = " labeled " . $slant2Lab;
      }
      $alt .= ", with a slanted segment".$altSlant2Lab." that connects the tip of the pyramid to the front edge of the rectangular base at a right angle";
    }
    $alt .= ".";
  } else {
    $alt = $userAltText;
  }
  $gr = showasciisvg("setBorder(20);initPicture(-$dim,$dim,-$dim,$dim);$args",$size,$size,$alt);
  return $gr;
}


//--------------------------------------------draw_rectprism()----------------------------------------------------
// draw_rectprism([length,height,depth],["labels,lab1,lab2,lab3"],["fill,[percent]"],["size,length"])
// draw_rectprism() will draw a rectangular prism with no labels.
// Options include:
// "labels,lab1,lab2,lab3" labels the length, height and depth of the prism.
// "fill,percent" will fill the prism to a percent of its height
// "size,length" sets the image size to be length x length.
function draw_rectprism() {
  $size = 300;
  $input = func_get_args();
  $argsArray = [];
  $args = '';
  $hasFill = false;
  $hasLabels = false;
  $altFillText = "";
  $hasUserAltText = false;
  foreach ($input as $list) {
    if (!is_array($list)) {
      $list = listtoarray($list);
    }
    $list = array_map('trim', $list);
    $argsArray[]=$list;
  }
  if (!isset($argsArray[0]) || !isset($argsArray[1]) || !isset($argsArray[1])) {
    [$length,$depth,$height] = diffrands(3,8,3);
  }
  if (array_key_exists(0,$argsArray)) {
    if (isset($argsArray[0][0]) && is_numeric($argsArray[0][0]) && $argsArray[0][0]>0 && isset($argsArray[1][0]) && is_numeric($argsArray[1][0]) && $argsArray[1][0]>0 && isset($argsArray[2][0]) && is_numeric($argsArray[2][0]) && $argsArray[2][0]>0) {
    $length = $argsArray[0][0];
    $depth = $argsArray[1][0];
    $height = $argsArray[2][0];
  } else {
      //echo "Warning! Must include length, depth and height.";
    [$length,$depth,$height] = diffrands(3,8,3);
  }
  }
  
  foreach ($argsArray as $in) {
    if (array_key_exists(0,$in)) {
    if ($in[0] == "alttext") {
      if (isset($in[1])) {
        $hasUserAltText = true;
        $userAltText = $in[1];
      }
    }
    if ($in[0] == "size") {
        if (isset($in[1]) && is_numeric($in[1])) {
      $size = $in[1];
    }
      }
    if ($in[0]=="fill") {
      $hasFill = true;
      if (isset($in[1]) && !empty($in[1])) {
        if ($in[1] < 0 || $in[1] > 100) {
          echo "Eek! Fill percent must be between 0 and 100.";
          $fillPercent = $GLOBALS['RND']->rand(20,80);
        } else {
          $fillPercent = $in[1];
        }
      } else {
        $fillPercent = $GLOBALS['RND']->rand(20,80);
      }
      $altFillText = " partially filled with colored material.";
    }
  }
  }
  $labels = ["","",""];
  foreach ($argsArray as $key => $in) {
    if ($in[0]=="labels") {
      $labels = array_slice($in,1,3);
      $edgeDescription = ["the front bottom edge of the prism from left to right is labeled ","the right bottom edge from front to back is labeled ","the rear vertical edge from top to bottom is labeled "];
      for ($i=0;$i<3;$i++) {
        if (!isset($labels[$i])) {
          $labels[$i] = '';
        }
        $altLab[$i] = "";
        if (!empty($labels[$i])) {
          $altLab[$i] .= $edgeDescription[$i].$labels[$i];
        }
      }
      if (!empty($altLab)) {
        $altLab = array_values($altLab);
        $altLabLen = count($altLab);
      }
      if ($altLabLen == 1) {
        $altLabEnd = ["","",""];
      } elseif ($altLabLen == 2) {
        $altLabEnd = [", and ","",""];
      } elseif ($altLabLen == 3) {
        $altLabEnd = [", ",", and ",""];
      }
      $altLabText = ucfirst($altLab[0].$altLabEnd[0].$altLab[1].$altLabEnd[1].$altLab[2].$altLabEnd[2]);
      $hasLabels = true;
    }
  }
  $xMax = $length+$depth/2;
  $yMax = $height+$depth*sqrt(3)/2;
  $xyMax = max($xMax,$yMax);
  $args = "";
  // Fill the prism
  if ($hasFill === true) {
    $fillHeight = $height*$fillPercent/100;
    $args .= "fill='lightblue'; stroke='none';";
    $args .= "path([[0,0],[0,$fillHeight],[$depth/2,$fillHeight+$depth*sqrt(3)/2],[$length+$depth/2,$fillHeight+$depth*sqrt(3)/2],[$length+$depth/2,$depth*sqrt(3)/2],[$length,0],[0,0]]);";
    $args .= "fill='none'; stroke='steelblue';";
    $args .= "path([[0,$fillHeight],[$depth/2,$fillHeight+$depth*sqrt(3)/2],[$length+$depth/2,$fillHeight+$depth*sqrt(3)/2],[$length,$fillHeight],[0,$fillHeight]]);";
    $args .= "stroke='black';";
  }
  // Draw the prism edges
  $args .= "strokewidth=2.5; fill='none';";
  $args .= "rect([0,0],[$length,$height]); path([[0,$height],[$depth/2,$height+$depth*sqrt(3)/2],[$length+$depth/2,$height+$depth*sqrt(3)/2],[$length,$height]]); path([[$length,0],[$length+$depth/2,$depth*sqrt(3)/2],[$length+$depth/2,$height+$depth*sqrt(3)/2]]);";
  $args .= "strokewidth = 2; strokedasharray='3 3';";
  $args .= "line([0,0],[$depth/2,$depth*sqrt(3)/2]); line([$depth/2,$depth*sqrt(3)/2],[$depth/2,$height+$depth*sqrt(3)/2]); line([$depth/2,$depth*sqrt(3)/2],[$depth/2+$length,$depth*sqrt(3)/2]);";
  // Label the edges
  $args .= "text([$length/2,0],'$labels[0]',below); text([$length+$depth/4,$depth*sqrt(3)/4],'$labels[1]',right); text([$length+$depth/2,$height/2+$depth*sqrt(3)/2],'$labels[2]',right);";
  // build the alt text
  if ($hasUserAltText !== true) {
    $alt = "A rectangular prism".$altFillText;
    if ($hasFill !== true) {
      $alt .= ".";
    }
    if ($hasLabels === true) {
      $alt .= " ".$altLabText.".";
    }
  } else {
    $alt = $userAltText;
  }
  $gr = showasciisvg("setBorder(10);initPicture(-$xyMax/10,1.1*$xyMax,-$xyMax/10,1.1*$xyMax);$args;",$size,$size,$alt);
  return $gr;
}


//--------------------------------------------draw_polyomino()----------------------------------------------------
// draw_polyomino([n],[options],["size,length"])
// draw_polyomino() will draw a random, connected polyomino, shaded and on a grid.
// draw_polyomino(n) will draw a random, connected polyomino with (at least) n squares, shaded and on a grid.
// Options include:
// "color,[transred]" will shade in the squares of the polyomino. Best to use a transparent color, such as transblue, gransgreen, etc.
// Using just "color" will shade with a random color
// "grid" will show the background grid
// "data" will output an array [graph,area,perimeter]
// "size,length" sets the image size to be length x length.
function draw_polyomino() {
  $size = 300;
  $input = func_get_args();
  $argsArray = [];
  $getdata = false;
  $hasUserAltText = false;
  foreach ($input as $list) {
    if (!is_array($list)) {
      $list = listtoarray($list);
    }
    $list = array_map('trim', $list);
    $argsArray[]=$list;
  }
  $axes = ";";
  $fillcolor = 'none';
  foreach ($argsArray as $in) {
    if ($in[0]=="data") {
      $getdata = true;
    }
    if ($in[0]=="grid") {
      $axes = "axes(300,300,1,1,1);";
    }
    if ($in[0]=="fill") {
      if (isset($in[1])) {
        $fillcolor = $in[1];
      } else {
        $fillcolor = randfrom("translightblue,transblue,transpurple,translightgreen,transgreen,transred");
      }
    }
    if ($in[0] == "size") {
      $size = $in[1];
    }
    if ($in[0]=="alttext") {
      if (isset($in[1])) {
        $hasUserAltText = true;
        $alttext = $in[1];
      }
    }
  }
  if (isset($argsArray[0][0]) && is_numeric($argsArray[0][0])) {
    $squares = $argsArray[0][0]-1;
    if ($squares > 99 || $squares < 0 || !is_int($squares)) {
      echo "Number of squares should be an integer in the range [1,100].";
      return "";
    }
  } else {
    $squares = $GLOBALS['RND']->rand(3,8);
  }
  $used = [[100,100]];
  $xused = [100];
  $yused = [100];
  $pt = "rect([100,100],[101,101]);";
  // add a square
  for ($i=1; $i<=$squares; $i++) {
    $togo = [];
    $im = $i-1;
    for ($l=0; $l <= $im; $l++) {
      $nearby0 = [$used[$l][0]+1,$used[$l][1]];
      $nearby1 = [$used[$l][0]-1,$used[$l][1]];
      $nearby2 = [$used[$l][0],$used[$l][1]+1];
      $nearby3 = [$used[$l][0],$used[$l][1]-1];
      if (!in_array($nearby0,$used) || !in_array($nearby1,$used) || !in_array($nearby2,$used) || !in_array($nearby3,$used)) {
        if (!in_array($nearby0,$used) && !in_array($nearby0,$togo)) {$togo[] = $nearby0;}
        if (!in_array($nearby1,$used) && !in_array($nearby1,$togo)) {$togo[] = $nearby1;}
        if (!in_array($nearby2,$used) && !in_array($nearby2,$togo)) {$togo[] = $nearby2;}
        if (!in_array($nearby3,$used) && !in_array($nearby3,$togo)) {$togo[] = $nearby3;}
      }
    }
    $q = randfrom($togo);
    $used[] = $q;
    $xused[] = $q[0];
    $yused[] = $q[1];
    $pt .= "stroke='red'; rect([{$used[$i][0]},{$used[$i][1]}],[{$used[$i][0]}+1,{$used[$i][1]}+1]);";
  }
  [$xmin,$xmax] = [min($xused),max($xused)];
  [$ymin,$ymax] = [min($yused),max($yused)];
  // Now look for holes in the shape
  // $empty will start with all empty squares along the rectangular boundary
  $empty = [];
  for ($i=$xmin; $i<=$xmax; $i++) {
    for ($j=$ymin; $j <= $ymax; $j++) {
      // $thegrid is the whole rectangular grid
      $thegrid[] = [$i,$j];
      if ($i==$xmin || $j==$ymax || $i==$xmax || $j==$ymin) {
        if (!in_array([$i,$j],$used)) {
          $empty[] = [$i,$j];
        }
      }
    }
  }
  $emptycount = count($empty)-1;
  // $knownempty contains the empty squares that are connected to the rectangular boundary
  if (count($empty) > 0) {
  $knownempty = [$empty[0]];
  }
  for ($i=0; $i <= $emptycount; $i++) {
    // $check contains the points up, down, left and right of $empty[$i]
    $check = [[$empty[$i][0]+1,$empty[$i][1]],[$empty[$i][0]-1,$empty[$i][1]],[$empty[$i][0],$empty[$i][1]+1],[$empty[$i][0],$empty[$i][1]-1]];
    for ($j=0; $j<4; $j++) {
      // is it in the grid?
      if (in_array($check[$j],$thegrid) && !in_array($check[$j],$knownempty)) {
        // is it empty?
        if (!in_array($check[$j],$used)) {
          $knownempty[] = $check[$j];
            if (!in_array($check[$j],$empty)) {
            $empty[] = $check[$j];
          }
          // redefine $emptycount to update the size of the for loop
          $emptycount = count($empty)-1;
        }
      }
    }
  }
  $emptycount = count($empty);
  // if there is a hole in the shape, color it in
  $hasholes = ($xmax+1-$xmin)*($ymax+1-$ymin) - (count($used) + count($empty));
  $tofill = [];
  if ($hasholes > 0) {
    $gridcount = ($xmax-$xmin+1)*($ymax-$ymin+1)-1;
    for ($i=0; $i <= $gridcount; $i++) {
      if (!in_array($thegrid[$i],$used) && !in_array($thegrid[$i],$empty)) {
        $tofill[$i] = $thegrid[$i];
        $used[] = $thegrid[$i];
        $xused[] = $thegrid[$i][0];
        $yused[] = $thegrid[$i][1];
        $pt .= "rect([{$thegrid[$i][0]},{$thegrid[$i][1]}],[{$thegrid[$i][0]}+1,{$thegrid[$i][1]}+1]);";
      }
    }
    // Attempt below sometimes creates disconnected parts of the polyomino
    // Now find squares to erase from left-most columns to bring total squares back to $squares
    /*for ($i=0; $i<$hasholes; $i++) {
      for ($col=$xmin; $col<=$xmax; $col++) {
        for ($row=$ymin; $row<=$ymax; $row++) {
          if (in_array([$col,$row],$used)) {
            $toblank[] = [$col,$row];
            $pt .= "fill='transblue';rect([$col,$row],[$col+1,$row+1]);";
            $filled++;
            $key = array_search([$col,$row],$used);
            unset($used[$key]);
            if ($filled == $hasholes) {break;}
          }
          if ($filled == $hasholes) {break;}
        }
        if ($filled == $hasholes) {break;}
      }
    }*/
  }
  //$used = array_values($used);
  $xused = [];
  $yused = [];
  $ptcode = '';
  for ($i=0; $i<count($used); $i++) {
    $ptcode .= "rect([{$used[$i][0]},{$used[$i][1]}],[{$used[$i][0]}+1,{$used[$i][1]}+1]);";
    $xused[] = $used[$i][0];
    $yused[] = $used[$i][1];
  }
  [$xmin,$xmax] = [min($xused),max($xused)];
  [$ymin,$ymax] = [min($yused),max($yused)];
  $xl = $xmax-$xmin+3;
  $yl = $ymax-$ymin+3;
  $countused = count($used)-1;
  $perimeter = 4*($countused+1);
  if ($hasUserAltText != true) {
    $alttext = "A connected region of shaded squares. On the coordinate plane, each shaded square has its bottom left corner given in coordinate form in the following list of x y pairs: ";
    for ($i=0; $i<=$countused; $i++) {
      if (in_array([$used[$i][0],$used[$i][1]+1],$used)) {
        $perimeter -= 2;
      }
      if (in_array([$used[$i][0]+1,$used[$i][1]],$used)) {
        $perimeter -= 2;
      }
      [$thisx,$thisy] = [$xused[$i]-$xmin,$yused[$i]-$ymin];
      $alttext .= ifthen($i==0,"($thisx,$thisy)",", ($thisx,$thisy)");
    }
  }
  $gr = showasciisvg("setBorder(40,40*$yl/$xl,40,40*$yl/$xl); initPicture($xmin-1,$xmax+2,$ymin-1,$ymax+2); $axes; strokewidth=1.5; fill='$fillcolor'; $ptcode;",$size,$yl/$xl*$size,$alttext);
  if ($getdata === true) {
    return [$gr,count($used),$perimeter];
  } else {
    return $gr;
  }
  
}
?>

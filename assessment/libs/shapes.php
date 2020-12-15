<?php

global $allowedmacros;
array_push($allowedmacros,"circle","circlesector","square","rectangle","triangle","polygon");

//--------------------------------------------Circle----------------------------------------------------
//&#928; is the pi symbol
//circle("center,[label]","radius,[label]","diameter","angle,measurement,[label,point]")
//All arguments (center, radius, diameter, angle) are optional and may be used in any order
function circle() {

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
    if (in_array("angle",$in) && $in[array_search("angle",$in)+1]%360 > 315) {
      $angleBlock = true;
    }
    if (in_array("radian",$in)) {
      $degSymbol = "";
    }
  }
  foreach ($argsArray as $in) {
    
    if (in_array("center",$in)) {
      $lab = "";
      if (isset($in[array_search("center",$in)+1])) {
        $lab = "text([0,0],'".$in[array_search('center',$in)+1]."',aboveleft);";
      }
      $args = $args."dot([0,0]);".$lab;
    }
    
    if (in_array("radius",$in)) {
      $lab = "";
      if (isset($in[array_search("radius",$in)+1])) {
        if ($angleBlock === true) {
          $lab = "text([0.5,0],'".$in[array_search('radius',$in)+1]."',above);";
        } elseif ($angleBlock !== true) {
          $lab = "text([0.5,0],'".$in[array_search('radius',$in)+1]."',below);";
        }
      }
      $args = $args."line([0,0],[1,0]);".$lab;
    }
    
    if (in_array("diameter",$in)) {
      $args = $args."line([-1,0],[1,0]);";
    }
    
    
    if (in_array("angle",$in)) {
      $lab = "";
      if (isset($in[array_search("angle",$in)+1])) {
        $ang = $in[array_search("angle",$in)+1];
        $x = cos(M_PI*$ang/180);
        $y = sin(M_PI*$ang/180);
        if ($ang>360) {
          $ang = $ang%360;
        }
        if ($ang<=180) {
          $arc = "arc([.2,0],[.2*$x,.2*$y],.2);";
        }
        if ($ang>180) {
          $arc = "arc([.2,0],[-.2,0],.2);arc([-.2,0],[.2*$x,.2*$y],.2);";
        }
        $args = $args.$arc;
        if ($in[array_search("angle",$in)+3]=="dot" || $in[array_search("angle",$in)+3]=="point") {
          $args = $args."dot([$x,$y]);";
        }
        if (isset($in[array_search("angle",$in)+2]) && $in[array_search("angle",$in)+2] != "") {
          $angLab = $in[array_search("angle",$in)+2];
          if (preg_match('/(^\s*pi[^a-zA-Z]+)|([^a-zA-Z\s]+pi[^a-zA-Z])|([^a-zA-z]pi[^a-zA-Z\s]+)|(^\s*pi\s)|(\spi\s)|(\spi$)|([^a-zA-Z]pi$)|(^pi$)/',$angLab)) {
            $angLab = str_replace("pi","&pi;",$angLab);
            $degSymbol = "";
          }
          if (preg_match('/rad/',$angLab)) {
            $angLab = str_replace('rad','',$angLab);
            $degSymbol = "";
          }
          
          $halfAngle = $ang/2;
          $xlab = .4*cos(M_PI*$halfAngle/180);
          $ylab = .4*sin(M_PI*$halfAngle/180);
          if ($ang <= 40) {
            $xlab = .4*cos(M_PI*($ang+15)/180);
            $ylab = .4*sin(M_PI*($ang+15)/180);
          }
          $lab = "text([$xlab,$ylab],'".$angLab."$degSymbol');";
        }
        $args = $args.$lab;
      }
      $args = $args."line([0,0],[1,0]);line([0,0],[$x,$y]);";
    }
  }
  
  $gr = showasciisvg("setBorder(5);initPicture(-1.5,1.5,-1.5,1.5);$args",300,300);
  return $gr;
}




//--------------------------------------------Circle Sector----------------------------------------------------

//circlesector("angle,measurement,[label]", "[center,[label]]", "[radius,[label]]")
//must include an argument with "angle, measurement", but all others are optional
function circlesector() {
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
  $justFine = false;
  foreach ($argsArray as $in) {
    if (in_array("angle",$in) && isset($in[array_search("angle",$in)+1])) {
      $justFine = true;
    }
    if (in_array("radian",$in)) {
      $degSymbol = "";
    }
  }
  if ($justFine === false) {
    echo "Eek! circlesector must include 'angle, measurement'.";
    return '';
  }
  foreach ($argsArray as $in) {
    
    if (in_array("center",$in)) {
      $lab = "";
      if (isset($in[array_search("center",$in)+1])) {
        $lab = "text([0,0],'".$in[array_search('center',$in)+1]."',aboveleft);";
      }
      $args = $args."dot([0,0]);".$lab;
    }
    
    if (in_array("radius",$in)) {
      $lab = "";
      if (isset($in[array_search("radius",$in)+1])) {
        $lab = "text([0.5,0],'".$in[array_search('radius',$in)+1]."',below);";
      }
      $args = $args."line([0,0],[1,0]);".$lab;
    }
    
    if (in_array("angle",$in)) {
      $lab = "";
      if (isset($in[array_search("angle",$in)+1])) {
        $ang = $in[array_search("angle",$in)+1];
        if ($ang>360) {
          $ang = 360;
        }
        $x = cos(M_PI*$ang/180);
        $y = sin(M_PI*$ang/180);

        $sectorArc = "arc([1,0],[$x,$y],1);";
        $arc = "arc([.2,0],[.2*$x,.2*$y],.2);";

        $args = $args.$sectorArc.$arc;

        if (isset($in[array_search("angle",$in)+2])) {
          $halfAngle = $ang/2;
          $xlab = .4*cos(M_PI*$halfAngle/180);
          $ylab = .4*sin(M_PI*$halfAngle/180);
          if ($ang <= 40) {
            $xlab = .4*cos(M_PI*($ang+15)/180);
            $ylab = .4*sin(M_PI*($ang+15)/180);
          }
          $lab = "text([$xlab,$ylab],'".$in[array_search('angle',$in)+2]."$degSymbol');";
        }
        $args = $args.$lab;
      }
      $args = $args."line([0,0],[1,0]);line([0,0],[$x,$y]);";
    }
  }
  
  $gr = showasciisvg("setBorder(5);initPicture(-1.2,1.2,-1.2,1.2);$args",300,300);
  return $gr;
}

//--------------------------------------------Square----------------------------------------------------

//square("base,label", "height,label")

function square() {
  
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
    $pointLab[$i] = $argsArray[$pointKey][$i+1];
  }
  $args = $args . "text([0,-1],'$baseLab',below);";
  $args = $args . "text([1,0],'$heightLab',right);";
  
  if ($hasPoints === true) {
    $args = $args . "dot([-1,-1]);dot([1,-1]);dot([1,1]);dot([-1,1]);";
    $args = $args . "text([-1,-1],'$pointLab[0]',belowleft);";
    $args = $args . "text([1,-1],'$pointLab[1]',belowright);";
    $args = $args . "text([1,1],'$pointLab[2]',aboveright);";
    $args = $args . "text([-1,1],'$pointLab[3]',aboveleft);";
  }
  
  $gr = showasciisvg("setBorder(5);initPicture(-1.5,1.5,-1.5,1.5);rect([-1,-1],[1,1]);$args",250,250);
  return $gr;
}


//--------------------------------------------Rectangle----------------------------------------------------

//rectangle("base,num,label", "height,num,label","points,lab,lab,lab,lab")

function rectangle() {
  
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
    echo 'Warning! "base" should be followed by a number.';
    $argsArray[$heightKey][1] = $rndHeight;
    $argsArray[$heightKey][2] = "";
  }
  
  if ($hasPoints === true) {
    for ($i=1;$i<5;$i++) {
      if (!isset($argsArray[$pointKey][$i])) {
        $argsArray[$pointKey][$i] = "";
      }
    }
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
  
  $gr = showasciisvg("setBorder(5);initPicture(-1.5,1.5,-1.5,1.5);$args",250,250);
  
  return $gr;
}


//--------------------------------------------Triangle----------------------------------------------------

//triangle("[random],[angles,ang1,ang2,ang3,lab1,lab2,lab3]")
//If angle is used, it must be followed by three angles. Can also be followed by labels.
function triangle() {
  $rndNum = $GLOBALS['RND']->rand(0,360);
  //$rndNum = 0;
  $input = func_get_args();
  $argsArray = [];
  foreach ($input as $list) {
    if (!is_array($list)) {
      $list = listtoarray($list);
    }
    $list = array_map('trim', $list);
    $argsArray[]=$list;
  }
  $noAngles = true;
  $noSides = true;
  $randomTriangle = false;
  $angBis = false;
  $points = false;
  
  //Checking first to see whether to declare randomTriangle
  foreach ($argsArray as $in) {
    if (in_array("angles",$in)) {
      $noAngles = false;
    }
    if (in_array("sides",$in)) {
      $noSides = false;
    }
  }
  
  //If random is set, then angles and sides can each only have three arguments for their labels
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
      if ($randomTriangle === true && count($in) > 4) {
        echo "Warning! If 'random' is used, then 'angles' should be followed by at most three labels.";
      }
    }
    if (in_array("sides",$in)) {
      $noSides = false;
      $sideKey = $key;
      if (count($in) < 4) {
        echo "Eek! 'sides' must be followed by at least three numbers.";
        return '';
      }
      if ($randomTriangle === true && count($in) > 4) {
        echo "Warning! If 'random' is used, then 'sides' should be followed by at most three labels.";
      }
    }
    if (in_array("angbis",$in)) {
      $angBis = true;
      $angBisKey = $key;
    }
    if (in_array("points",$in)) {
      $points = true;
      $pointKey = $key;
    }
  }
  

  if ($randomTriangle === true) {
    $ang[0]=rand(25,70);
    $ang[1]=rand(25,70);
    $ang[2]=180-$ang[0]-$ang[1];
    
    if ($noSides ===  true) {
      $argsArray[] = ["sides",'','',''];
      $sideKey = count($argsArray)-1;
    }
    if ($noAngles === false) {
      $angToLabel = array_slice($argsArray[$angleKey],1,3);
      $argsArray[$angleKey] = ["angles",$ang[0],$ang[1],$ang[2],$angToLabel[0],$angToLabel[1],$angToLabel[2]];
    }
    if ($noAngles === true) {
      array_unshift($argsArray,["angles",$ang[0],$ang[1],$ang[2],'','','']);
      $angleKey = 0;
      $sideKey = $sideKey + 1; //indices changed because unshift was used
      $angBisKey = $angBisKey + 1;
      $pointKey = $pointKey + 1;
      $noAngles = false;
    }
  }
  // End random triangles.
  // If random, now argsArray has "angle,A,B,C,Alab,Blab,Clab" and may or may not have "sides,a,b,c"
  if ($randomTriangle === false) {
    if ($noSides === false && $noAngles === false) { //has angles and sides
      for ($i=1;$i<4;$i++) {
        if (!isset($argsArray[$angleKey][$i])) {
          $argsArray[$angleKey][$i] = "";
        }
      }
      if ($angleKey < $sideKey) {
        if (count($argsArray[$sideKey]) > 4) {
          echo "Warning! If first argument is 'angles,a,b,c', then 'sides' should be followed by at most three labels";
        }
        for ($i=4;$i<7;$i++) {
          if (!isset($argsArray[$angleKey][$i])) {
            $argsArray[$angleKey][$i] = "";
          }
        }
      }
      
      if ($sideKey < $angleKey) {
        if (count($argsArray[$angleKey]) > 4) {
          echo "Warning! If first argument is 'sides,a,b,c', then 'angles' should be followed by at most three labels";
        }
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

        $argsArray[$angleKey] = ["angles",$angS1,$angS2,$angS3,$angTmp[0],$angTmp[1],$angTmp[2]];
        
        $argsArray[$sideKey] = ["sides",$sidLabTmp[0],$sidLabTmp[1],$sidLabTmp[2]];
      }
    }
    
    if ($noSides === false && $noAngles === true) { //has sides, but no angles
      $sid = array_slice($argsArray[$sideKey],1,3);
      for ($i=4;$i<7;$i++) {
        if (!isset($argsArray[$sideKey][$i])) {
          $sidTmp[$i] = '';
        }
      }
      if (($sid[0]+$sid[1]<=$sid[2]) || ($sid[1]+$sid[2]<=$sid[0]) || ($sid[2]+$sid[0]<=$sid[1])) {
        echo 'Eek! No triangle possible with these side lengths.';
        return '';
      }
      $angS1 = 180*acos((pow($sid[0],2)-pow($sid[1],2)-pow($sid[2],2))/(-2*$sid[1]*$sid[2]))/(M_PI);
      $angS2 = 180*acos((pow($sid[1],2)-pow($sid[0],2)-pow($sid[2],2))/(-2*$sid[0]*$sid[2]))/(M_PI);
      $angS3 = 180*acos((pow($sid[2],2)-pow($sid[1],2)-pow($sid[0],2))/(-2*$sid[1]*$sid[0]))/(M_PI);
      
      $argsArray[$sideKey] = ["sides",$sidTmp[0],$sidTmp[1],$sidTmp[2]];
      $argsArray[] = ["angles",$angS1,$angS2,$angS3,'','',''];
      $angleKey = count($argsArray)-1;
    }
    if ($noSides === true && $noAngles === false) { // has angles, but no sides
      for ($i=4;$i<7;$i++) {
        if (!isset($argsArray[$angleKey][$i])) {
          $argsArray[$angleKey][$i] = '';
        }
      }
      $argsArray[] = ["sides",'','',''];
      $sideKey = count($argsArray)-1;
    }
  } // End non-random triangles.
  
  ///Now, make the triangle
  $ang = array_slice($argsArray[$angleKey],1,3);
  foreach ($ang as $key => $angle) {
    if (abs($angle - 90) < 1E-9) {
      $perpKey = $key; //this is the 0-enumerated number of the angle
      $hasPerp = true;
    }
  }
  $angLab = array_slice($argsArray[$angleKey],4,3);
  $sidLab = array_slice($argsArray[$sideKey],1,3);
  
  if (abs($ang[0] + $ang[1] + $ang[2] - 180) > 1E-9 ) {
    echo "Eek! Sum of 'angles' is not 180.";
    return '';
  }
  
  $x[0] = cos(0+M_PI*$rndNum/180); //coordinates of vertices
  $y[0] = sin(0+M_PI*$rndNum/180);
  $x[1] = cos(2*M_PI*$ang[0]/180 + M_PI*$rndNum/180);
  $y[1] = sin(2*M_PI*$ang[0]/180 + M_PI*$rndNum/180);
  $x[2] = cos(2*M_PI*$ang[0]/180 + 2*M_PI*$ang[1]/180 + M_PI*$rndNum/180);
  $y[2] = sin(2*M_PI*$ang[0]/180 + 2*M_PI*$ang[1]/180 + M_PI*$rndNum/180);

  $xmin = min($x);
  $xmax = max($x);
  $ymin = min($y);
  $ymax = max($y);
  $xyDiff = max(($xmax-$xmin),($ymax-$ymin))/2;
  $xminDisp = (($xmax + $xmin)/2-1.35*$xyDiff); //window settings
  $xmaxDisp = (($xmax + $xmin)/2+1.35*$xyDiff);
  $yminDisp = (($ymax + $ymin)/2-1.35*$xyDiff);
  $ymaxDisp = (($ymax + $ymin)/2+1.35*$xyDiff);
  $mid1 = [($x[0]+$x[1])/2,($y[0]+$y[1])/2]; //midpoints of sides
  $mid2 = [($x[1]+$x[2])/2,($y[1]+$y[2])/2];
  $mid3 = [($x[2]+$x[0])/2,($y[2]+$y[0])/2];
  
  // Used for angle placement and angle bisectors
  $sL1 = sqrt(pow($x[1]-$x[0],2)+pow($y[1]-$y[0],2)); //length of side opposite angle 1
  $sL2 = sqrt(pow($x[2]-$x[1],2)+pow($y[2]-$y[1],2));
  $sL3 = sqrt(pow($x[2]-$x[0],2)+pow($y[2]-$y[0],2));
  $xab[0] = $sL2/($sL2+$sL3)*$x[0] + $sL3/($sL2+$sL3)*$x[1]; //coords of where bisector of angle 1 intersects opposite side
  $yab[1] = $sL2/($sL2+$sL3)*$y[0] + $sL3/($sL2+$sL3)*$y[1];
  $xab[1] = $sL1/($sL1+$sL3)*$x[2] + $sL3/($sL1+$sL3)*$x[1]; //coords of where bisector of angle 2 intersects opposite side
  $yab[2] = $sL1/($sL1+$sL3)*$y[2] + $sL3/($sL1+$sL3)*$y[1];
  $xab[2] = $sL2/($sL2+$sL1)*$x[0] + $sL1/($sL2+$sL1)*$x[2]; //coords of where bisector of angle 3 intersects opposite side
  $yab[3] = $sL2/($sL2+$sL1)*$y[0] + $sL1/($sL2+$sL1)*$y[2];
  
  if ($hasPerp === true) {
    $indTmp = [2,0,1]; //changes angle indices to point indices
    $perpPointKey = $indTmp[$perpKey];
    $notInd = array_values(array_diff([0,1,2],[$perpPointKey]));
    foreach ($notInd as $k => $ind) {
      $perpVec[$k] = [.15*($x[$ind]-$x[$perpPointKey])/(sqrt(pow($x[$ind]-$x[$perpPointKey],2) + pow($y[$ind]-$y[$perpPointKey],2))), .15*($y[$ind]-$y[$perpPointKey])/(sqrt(pow($x[$ind]-$x[$perpPointKey],2) + pow($y[$ind]-$y[$perpPointKey],2)))];
    }
    $perpPoint[0] = [$x[$perpPointKey]+$perpVec[0][0], $y[$perpPointKey]+$perpVec[0][1]];
    $perpPoint[1] = [$x[$perpPointKey]+$perpVec[1][0], $y[$perpPointKey]+$perpVec[1][1]];
    $perpPoint[2] = [$x[$perpPointKey]+$perpVec[0][0]+$perpVec[1][0], $y[$perpPointKey]+$perpVec[0][1]+$perpVec[1][1]];
    $args = $args."path([[{$perpPoint[0][0]},{$perpPoint[0][1]}],[{$perpPoint[2][0]},{$perpPoint[2][1]}],[{$perpPoint[1][0]},{$perpPoint[1][1]}]]);";
    
    if (isset($angLab[$perpKey])) {
      $angLab[$perpKey] = '';
    }
  }
  
  //Drawing the triangle
  $args = $args."line([$x[0],$y[0]],[$x[1],$y[1]]);line([$x[1],$y[1]],[$x[2],$y[2]]);line([$x[2],$y[2]],[$x[0],$y[0]]);";
  
  $angLabLoc[0] = [$x[2] + 0.3*($xab[0]-$x[2]),$y[2] + 0.3*($yab[1]-$y[2])];
  $angLabLoc[1] = [$x[0] + 0.3*($xab[1]-$x[0]),$y[0] + 0.3*($yab[2]-$y[0])];
  $angLabLoc[2] = [$x[1] + 0.3*($xab[2]-$x[1]),$y[1] + 0.3*($yab[3]-$y[1])];
  for ($i=0;$i<3;$i++) {
    if (preg_match('/rad/',$angLab[$i]) || ($i == $perpKey && $hasPerp === true) || $angLab[$i] == '') {
      $angLab[$i] = preg_replace('/rad/','',$angLab[$i]);
      $degSymbol[$i] = '';
    } else {
      $degSymbol[$i] = "&deg;";
    }
    $args = $args."text([{$angLabLoc[$i][0]},{$angLabLoc[$i][1]}],'".$angLab[$i]."$degSymbol[$i]');";
  }
  
  $sidLabLoc[0] = [$x[2] + (1 + 0.11*pow(2,$ang[0]/90))*($xab[0]-$x[2]),$y[2] + (1 + 0.11*pow(2,$ang[0]/90))*($yab[1]-$y[2])];
  $sidLabLoc[1] = [$x[0] + (1 + 0.11*pow(2,$ang[1]/90))*($xab[1]-$x[0]),$y[0] + (1 + 0.11*pow(2,$ang[1]/90))*($yab[2]-$y[0])];
  $sidLabLoc[2] = [$x[1] + (1 + 0.11*pow(2,$ang[2]/90))*($xab[2]-$x[1]),$y[1] + (1 + 0.11*pow(2,$ang[2]/90))*($yab[3]-$y[1])];
  for ($i=0;$i<3;$i++) {
    $args = $args."text([{$sidLabLoc[$i][0]},{$sidLabLoc[$i][1]}],'".$sidLab[$i]."');";
  }

  if ($angBis === true) {
    $angBis = array_slice($argsArray[$angBisKey],1,3);
    
    if ($angBis[0]==1) {
      $args = $args."line([$x[2],$y[2]],[$xab[0],$yab[1]]);";
    }
    if ($angBis[1]==1) {
      $args = $args."line([$x[0],$y[0]],[$xab[1],$yab[2]]);";
    }
    if ($angBis[2]==1) {
      $args = $args."line([$x[1],$y[1]],[$xab[2],$yab[3]]);";
    }
  }
  
  if ($points === true) {
    $args = $args."dot([$x[0],$y[0]]);";
    $args = $args."dot([$x[1],$y[1]]);";
    $args = $args."dot([$x[2],$y[2]]);";
    $verLab = array_slice($argsArray[$pointKey],1,3);
    $verLabLoc[0] = [$x[2] - (0.1*pow(2,$ang[0]/90))*($xab[0]-$x[2]),$y[2] - (0.1*pow(2,$ang[0]/90))*($yab[1]-$y[2])];
    $verLabLoc[1] = [$x[0] - (0.1*pow(2,$ang[1]/90))*($xab[1]-$x[0]),$y[0] - (0.1*pow(2,$ang[1]/90))*($yab[2]-$y[0])];
    $verLabLoc[2] = [$x[1] - (0.1*pow(2,$ang[2]/90))*($xab[2]-$x[1]),$y[1] - (0.1*pow(2,$ang[2]/90))*($yab[3]-$y[1])];
    for ($i=0;$i<3;$i++) {
      $args = $args."text([{$verLabLoc[$i][0]},{$verLabLoc[$i][1]}],'".$verLab[$i]."');";
    }
  }
  
  $gr = showasciisvg("setBorder(10);initPicture($xminDisp,$xmaxDisp,$yminDisp,$ymaxDisp);$args;",300,300);
  return $gr;
}

//--------------------------------------------Polygon----------------------------------------------------

//polygon(sides,"points","regular")

function polygon() {
  
  $randAng = rand(0,360)*M_PI/180;
  $randSides = rand(3,9);
  //$randAng = 0;
  $isRegular = false;
  $hasPoints = false;
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
  }
  
  $n = $argsArray[0][0]; //number of sides
  if (!is_numeric($n) || $n[0]%round($n)!=0 || $n < 3) {
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
    $ang[0] = $sumAngles*M_PI - array_sum(array_slice($ang,1));
  }
  
  $x[0] = cos($randAng);
  $y[0] = sin($randAng);
  $args = "path([[$x[0],$y[0]]";
  for ($i=0;$i<$n;$i++) {
    $partialSum[$i] = array_sum(array_slice($ang,0,$i));
    $x[$i] = cos($partialSum[$i] + $randAng);
    $y[$i] = sin($partialSum[$i] + $randAng);
    $args = $args . ",[$x[$i],$y[$i]]";
  }
  $args = $args . ",[$x[0],$y[0]]]);";

  if ($hasPoints === true) {
    for ($i=0;$i<$n;$i++) {
      $args = $args . "dot([$x[$i],$y[$i]]);";
    }
  }
    
  $gr = showasciisvg("setBorder(10);initPicture(-1.5,1.5,-1.5,1.5);$args;",300,300);
  return $gr;
  }
  

?>

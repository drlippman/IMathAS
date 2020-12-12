<?php

global $allowedmacros;
array_push($allowedmacros,"circle","square","triangle","circlesector","rectangle");

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
  foreach ($argsArray as $in) {
    if (in_array("base",$in)) {
      $lab = "";
      if (isset($in[array_search("base",$in)+1])) {
        $lab = "text([0,-1],'".$in[array_search('base',$in)+1]."',below);";
        $args = $args.$lab;
      }
    }
    if (in_array("height",$in)) {
      $lab = "";
      if (isset($in[array_search("height",$in)+1])) {
        $lab = "text([1,0],'".$in[array_search('height',$in)+1]."',right);";
        $args = $args.$lab;
      }
    }
  }
  
  $gr = showasciisvg("setBorder(5);initPicture(-1.5,1.5,-1.5,1.5);rect([-1,-1],[1,1]);$args",400,400);
  return $gr;
}


//--------------------------------------------Rectangle----------------------------------------------------

//rectangle("base,label", "height,label")

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
  [$xLen,$yLen] = diffrrands(.2,1,.2,2);
  foreach ($argsArray as $in) {
    if (array_search("base",$in)) {
      $base = $in[array_search("base",$in)+1];
    } 
    if (array_search("height",$in)) {
      $height = $in[array_search("height",$in)+1];
    }
  }
  echo $base." and  ".$height; //Base and Height coming up empty...

  if ($base>0 && $height>0) {
    if ($base>$height) {
      $yLen = $height/(2*$base);
      $xLen = 1;
    } elseif ($base<=$height) {
      $xLen = $base/(2*$height);
      $yLen = 1;
    }
  }
  foreach ($argsArray as $in) {
    if (in_array("base",$in)) {
      $lab = "";
      if (isset($in[array_search("base",$in)+1])) {
        $lab = "text([0,-".$yLen."],'".$in[array_search('base',$in)+1]."',below);";
        $args = $args.$lab;
      }
    }
    if (in_array("height",$in)) {
      $lab = "";
      if (isset($in[array_search("height",$in)+1])) {
        $lab = "text([".$xLen.",0],'".$in[array_search('height',$in)+1]."',right);";
        $args = $args.$lab;
      }
    }
  }
  $args = $args."rect([-".$xLen.",-".$yLen."],[".$xLen.",".$yLen."]);";
  
  $gr = showasciisvg("setBorder(5);initPicture(-1.5,1.5,-1.5,1.5);$args",400,400);
  
  return $gr;
}


//--------------------------------------------Triangle----------------------------------------------------

//triangle("[angle,ang1,ang2,ang3],[lab1,lab2,lab3]")
//If angle is used, it must be followed by three angles. Can also be followed by labels.
function triangle() {
  $degSymbol = "&deg;"; //defaults to display degree symbol for angles
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
  foreach ($argsArray as $in) {
    if (in_array("angles",$in)) {
      $noAngles = false;
    }
    if (in_array("radian",$in)) {
      $degSymbol = "";
    }
    if (in_array("sides",$in)) {
      $noSides = false;
    }
  }

  if ($noAngles === true && $noSides === true) { //make random angles if nothing is given
    $ang[0]=rand(25,70);
    $ang[1]=rand(25,70);
    $ang[2]=180-$ang[0]-$ang[1];
    array_unshift($argsArray,["angles",$ang[0],$ang[1],$ang[2]]); //
    $noAngles = false;
  }
  
  if ($noSides === false && $noAngles === true) { //turns sides into angles
    if (!isset($in[3])) {
      echo 'Eek! "sides" must be followed by three side lengths.';
      return '';
    }
    $sid = array_slice($in,1,3);
    if (($sid[0]+$sid[1]<$sid[2]) || ($sid[1]+$sid[2]<$sid[0]) || ($sid[2]+$sid[0]<$sid[1])) {
      echo 'Eek! No triangle possible.';
      return '';
    }
    $angS1 = 180*acos((pow($sid[0],2)-pow($sid[1],2)-pow($sid[2],2))/(-2*$sid[1]*$sid[2]))/(M_PI);
    $angS2 = 180*acos((pow($sid[1],2)-pow($sid[0],2)-pow($sid[2],2))/(-2*$sid[0]*$sid[2]))/(M_PI);
    $angS3 = 180*acos((pow($sid[2],2)-pow($sid[1],2)-pow($sid[0],2))/(-2*$sid[1]*$sid[0]))/(M_PI);
    //echo $angS1+$angS2+$angS3;
    //return '';
    array_unshift($argsArray,["angles",$angS1,$angS2,$angS3]);
  }
  
  if ($noAngles === false && $noSides === false) {
    echo 'Eek! Cannot use both "angles,a,c,b" and "sides,a,b,c" to define triangle.';
    return '';
  }
  //print_r($argsArray);
  //return "";
  
  //Doesn't label well if an angle is less than 20 degrees or greater than 140 degrees
  foreach ($argsArray as $in) {
    if (in_array("angles",$in)) {
      if (!isset($in[3])) {
        echo 'Eek! "angles" must be followed by list of three angles.';
        return '';
      }
      if (isset($in[array_search("angles",$in)+3])) {
        $ang = array_slice($in,1,3);
        
        if (abs($ang[0] + $ang[1] + $ang[2] - 180) > 1E-9 ) {
          echo "Eek! 'angles' array sum is greater than 180 degrees.";
          return '';
        }
        $rnd = $GLOBALS['RND']->rand(0,360);
        //$rnd = 0;
        $x1 = cos(0+M_PI*$rnd/180); //coords of vertices
        $y1 = sin(0+M_PI*$rnd/180);
        $x2 = cos(2*M_PI*$ang[0]/180 + M_PI*$rnd/180);
        $y2 = sin(2*M_PI*$ang[0]/180 + M_PI*$rnd/180);
        $x3 = cos(2*M_PI*$ang[0]/180 + 2*M_PI*$ang[1]/180 + M_PI*$rnd/180);
        $y3 = sin(2*M_PI*$ang[0]/180 + 2*M_PI*$ang[1]/180 + M_PI*$rnd/180);

        $xmin = min($x1,$x2,$x3);
        $xmax = max($x1,$x2,$x3);
        $ymin = min($y1,$y2,$y3);
        $ymax = max($y1,$y2,$y3);
        $xyDiff = max(($xmax-$xmin),($ymax-$ymin))/2;
        $xminDisp = 1.1*(($xmax + $xmin)/2-$xyDiff); //sets window 
        $xmaxDisp = 1.1*(($xmax + $xmin)/2+$xyDiff);
        $yminDisp = 1.1*(($ymax + $ymin)/2-$xyDiff);
        $ymaxDisp = 1.1*(($ymax + $ymin)/2+$xyDiff);
        $mid1 = [($x1+$x2)/2,($y1+$y2)/2]; //midpoints of sides
        $mid2 = [($x2+$x3)/2,($y2+$y3)/2];
        $mid3 = [($x3+$x1)/2,($y3+$y1)/2];
        
        //used for placing angles, but should change
        $x11 = cos(M_PI*$ang[0]/180 + M_PI*$rnd/180); //angle bisector points on circle
        $y11 = sin(M_PI*$ang[0]/180 + M_PI*$rnd/180);
        $x22 = cos(2*M_PI*$ang[0]/180 + M_PI*$ang[1]/180 + M_PI*$rnd/180);
        $y22 = sin(2*M_PI*$ang[0]/180 + M_PI*$ang[1]/180 + M_PI*$rnd/180);
        $x33 = cos(2*M_PI*($ang[0]+$ang[1]+$ang[2]/2)/180 + M_PI*$rnd/180);
        $y33 = sin(2*M_PI*($ang[0]+$ang[1]+$ang[2]/2)/180 + M_PI*$rnd/180);
        
        // Needed for angle placement and angle bisectors
        $sL1 = sqrt(pow($x2-$x1,2)+pow($y2-$y1,2)); //length of side opposite angle 1
        $sL2 = sqrt(pow($x3-$x2,2)+pow($y3-$y2,2));
        $sL3 = sqrt(pow($x3-$x1,2)+pow($y3-$y1,2));
        $xab1 = $sL2/($sL2+$sL3)*$x1 + $sL3/($sL2+$sL3)*$x2; //coords of where bisector of angle 1 intersects opposite side
        $yab1 = $sL2/($sL2+$sL3)*$y1 + $sL3/($sL2+$sL3)*$y2;
        $xab2 = $sL1/($sL1+$sL3)*$x3 + $sL3/($sL1+$sL3)*$x2; //coords of where bisector of angle 2 intersects opposite side
        $yab2 = $sL1/($sL1+$sL3)*$y3 + $sL3/($sL1+$sL3)*$y2;
        $xab3 = $sL2/($sL2+$sL1)*$x1 + $sL1/($sL2+$sL1)*$x3; //coords of where bisector of angle 3 intersects opposite side
        $yab3 = $sL2/($sL2+$sL1)*$y1 + $sL1/($sL2+$sL1)*$y3;
        
        $args = $args."line([$x1,$y1],[$x2,$y2]);line([$x2,$y2],[$x3,$y3]);line([$x3,$y3],[$x1,$y1]);";
      }
      if (isset($in[array_search("angles",$in)+4])) {
        $angLab = array_slice($in, 4);
      
        $angLabLoc[0] = [$x3 + 0.25*(180-$ang[0])/180*($x11-$x3),$y3 + 0.25*(180-$ang[0])/180*($y11-$y3)];
        $angLabLoc[1] = [$x1 + 0.25*(180-$ang[1])/180*($x22-$x1),$y1 + 0.25*(180-$ang[1])/180*($y22-$y1)];
        $angLabLoc[2] = [$x2 + 0.25*(180-$ang[2])/180*($x33-$x2),$y2 + 0.25*(180-$ang[2])/180*($y33-$y2)];
        for ($i=0;$i<3;$i++) {
          $args = $args."text([{$angLabLoc[$i][0]},{$angLabLoc[$i][1]}],'".$angLab[$i]."$degSymbol');";
        }
      }
    } //end of "angle"
    
    
    
    if (isset($in[array_search("angbis",$in)])) {
      $angBis = array_slice($in,1,3);
      
      if ($angBis[0]==1) {
        $args = $args."line([$x3,$y3],[$xab1,$yab1]);";
      }
      if ($angBis[1]==1) {
        $args = $args."line([$x1,$y1],[$xab2,$yab2]);";
      }
      if ($angBis[2]==1) {
        $args = $args."line([$x2,$y2],[$xab3,$yab3]);";
      }
    }
  } //done looping over argsArray
  foreach ($argsArray as $in) {
    if (in_array("vertex",$in) || in_array("vertices",$in)) {
      $args = $args."dot([$x1,$y1]);";
      $args = $args."dot([$x2,$y2]);";
      $args = $args."dot([$x3,$y3]);";
    }
  }
  //triangle("angles,A,B,C,labA,labB,labC","vertices,A,B,C","sides,a,b,c")
  
  $gr = showasciisvg("setBorder(5);initPicture($xminDisp,$xmaxDisp,$yminDisp,$ymaxDisp);$args;",300,300);
  return $gr;
}

//--------------------------------------------Square----------------------------------------------------

//square("base,label", "height,label")

?>

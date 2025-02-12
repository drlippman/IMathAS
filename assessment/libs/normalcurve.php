<?php
//normal curve functions

global $allowedmacros;
array_push($allowedmacros, "normalcurve", "normalcurve2", "normalcurve3");

//normalcurve(mu, sigma, a, b, [axislabel, x, dirx, y, diry, color, width, height])
//draws a normal curve with mean mu and standard deviation sigma
//This function is good for a basic normal curve, vertically scaled to fit
//with axis labels at multiples of sigma
//
//draws on bounds a&lt;x&lt;b, with axis markings at mu+k*sigma
//axis label gives an axis label
//optionally can shade a region based on one or two values x and y
//indiciate shade direction using dirx and diry, which can be "left" or "right"
//you set use null for y to set later values
//color indicates color of the shaded regions
//width and height are pixels for the displayed graph.  defaults to 400 by 150
function normalcurve($mu, $sigma, $a, $b, $axislabel='',$x=null, $dirx='left', $y=null, $diry='right', $color='blue', $w=400,$h=150) {
   if ($sigma<=0) { echo 'Invalid sigma'; return;}
   if ($y!==null && $y<$x) { echo 'Second value should be bigger than the first'; return; }
   if ($a>=$b) { echo 'xmin should be smaller than xmax'; return; }
   if ($y!==null && $dirx==$diry) { echo 'directions should not be equal'; return;}

   $za = ($a-$mu)/$sigma;
   $zb = ($b-$mu)/$sigma;
   if ($x!==null) {
   	   $zx = ($x-$mu)/$sigma;
   }
   if ($y!==null) {
     $zy = ($y-$mu)/$sigma;
   }
   $dz = ($zb-$za)/80;
   $zab=$za-$sigma;
   $zbb=$zb+$sigma;
   $plot = "setBorder(10,30,10,5);initPicture($za,$zb,-.1,.45);line([$zab,0],[$zbb,0]);";
   for ($i=ceil($za);$i<$zb+$dz;$i++) {
	$label = $mu + $i*$sigma;
	$plot .= "line([$i,.02],[$i,-.02]);text([$i,-.01],\"$label\",\"below\");";
   }

   $midx = $w/2;
   $plot .= "textabs([$midx,0],'$axislabel','above');";
   $pts = array();
   $xpts = array();
   $ypts = array();

   $coef = 1/sqrt(2*3.1415926);

   if ($x !== null && $dirx=='right') {
      $py = $coef*exp(-$zx*$zx/2);
      $xpts[] = "[$zx,0]";
      $xpts[] = "[$zx,$py]";
   }
   if ($y !== null && $diry=='right') {
      $py = $coef*exp(-$zy*$zy/2);
      $ypts[] = "[$zy,$py]";
   }


   for ($z=$za;$z<=$zb+.00001;$z+=$dz) {
	$py = $coef*exp(-$z*$z/2);
        $pts[] = "[$z,$py]";
        if ($x !== null && (($dirx == 'left' && $z<$zx) || ($dirx == 'right' && $z>$zx && ($y===null || $z<$zy )))) {
           $xpts[] = "[$z,$py]";
        }
        if ($y !== null && $diry=='right' && $z>$zy) {
           $ypts[] = "[$z,$py]";
        }
   }
   if ($x !== null && $dirx=='left') {
      $py = $coef*exp(-$zx*$zx/2.0);
      $xpts[] = "[$zx,$py]";
      $xpts[] = "[$zx,0]";
      $xpts[] = "[$za,0]";
   }
   if ($x !== null && $dirx=='right' && $y===null) {
      $xpts[] = "[$zb,0]";
   }
   if ($y !== null && $diry=='left') {
      $py = $coef*exp(-$zy*$zy/2.0);
      $xpts[] = "[$zy,$py]";
      $xpts[] = "[$zy,0]";
      $xpts[] = "[$zx,0]";
   }
   if ($y !== null && $diry=='right') {
      $ypts[] = "[$zb,0]";
      $ypts[] = "[$zy,0]";
   }

   $plot .= 'fill="none";path([' . implode(',',$pts) . ']);';
   if ($x !== null) {
       $plot .= 'fill="'.$color.'";path([' . implode(',',$xpts) . ']);';
   }
   if ($y !== null && $diry=='right') {
       $plot .= 'fill="'.$color.'";path([' . implode(',',$ypts) . ']);';
   }

   return showasciisvg($plot,$w,$h);
}


//normalcurve2(mu, sigma, a, b, [axisspacing, ymax, axislabel, x, dirx, y, diry, color, width, height])
//draws a normal curve with mean mu and standard deviation sigma
//This function allows for axis spacing other than multiples of sigma, and does
//not scale the graph vertically to fit.
//
//draws on bounds a&lt;x&lt;b
//specify axisspacing, or defaults to sigma
//set ymax to set max of vertical axis, or "auto" for ymax to autoscale
//axis label gives an axis label
//optionally can shade a region based on one or two values x and y
//indiciate shade direction using dirx and diry, which can be "left" or "right"
//you set use null for y to set later values
//color indicates color of the shaded regions
//width and height are pixels for the displayed graph.  defaults to 400 by 150
function normalcurve2($mu, $sigma, $a, $b, $axisspacing=null,$ymax=1, $axislabel='',$x=null, $dirx='left', $y=null, $diry='right', $color='blue', $w=400,$h=150) {
   if ($sigma<=0) { echo 'Invalid sigma'; return;}
   if ($y!==null && $y<$x) { echo 'Second value should be bigger than the first'; return; }
   if ($a>=$b) { echo 'xmin should be smaller than xmax'; return; }
   if ($y!==null && $dirx==$diry) { echo 'directions should not be equal'; return;}
   if ($axisspacing==null) {
   	   $axisspacing = $sigma;
   }
	 $coef = 1/($sigma*sqrt(2*3.1415926));
	 if ($ymax=="auto") {
		 $ymax = $coef*1.1;
	 }
   if ($ymax<=0) { echo 'invalid ymax'; return;}
   if ($axisspacing<=0) { echo 'invalid axis spacing'; return;}
   if ($x!==null) {
   	   $zx = ($x-$mu)/$sigma;
   }
   if ($y!==null) {
     $zy = ($y-$mu)/$sigma;
   }
   $dx = ($b-$a)/80;
   $zab=$a-$sigma;
   $zbb=$b+$sigma;
	 $ymin = .24*$ymax;
	 $tick = .04*$ymax;
   $plot = "setBorder(10,30,10,5);initPicture($a,$b,-$ymin,$ymax);line([$zab,0],[$zbb,0]);";
   if ($axisspacing==$sigma) {
		 $za = ($a-$mu)/$sigma;
	   $zb = ($b-$mu)/$sigma;
		 $dz = ($zb-$za)/80;
	   for ($i=ceil($za);$i<$zb+$dz;$i++) {
			 $label = $mu + $i*$sigma;
			 $plot .= "line([$label,$tick],[$label,-$tick]);text([$label,-$tick],\"$label\",\"below\");";
	   }
   } else {
   	  for ($i=$a;$i<$b+$dx;$i+=$axisspacing) {
				 $plot .= "line([$i,$tick],[$i,-$tick]);text([$i,-$tick],\"$i\",\"below\");";
	   	}
   }
   $midx = $w/2;
   $plot .= "textabs([$midx,0],'$axislabel','above');";
   $pts = array();
   $xpts = array();
   $ypts = array();

   $ss = $sigma*$sigma;

   if ($x !== null && $dirx=='right') {
      $py = $coef*exp(-0.5*($x-$mu)*($x-$mu)/$ss);
      $xpts[] = "[$x,0]";
      $xpts[] = "[$x,$py]";
   }
   if ($y !== null && $diry=='right') {
      $py = $coef*exp(-0.5*($y-$mu)*($y-$mu)/$ss);
      $ypts[] = "[$y,$py]";
   }


   for ($xv=$a;$xv<=$b+.00001;$xv+=$dx) {
	$py = $coef*exp(-0.5*($xv-$mu)*($xv-$mu)/$ss);
        $pts[] = "[$xv,$py]";
        if ($x !== null && (($dirx == 'left' && $xv<$x) || ($dirx == 'right' && $xv>$x && ($y===null || $xv<$y )))) {
           $xpts[] = "[$xv,$py]";
        }
        if ($y !== null && $diry=='right' && $xv>$y) {
           $ypts[] = "[$xv,$py]";
        }
   }
   if ($x !== null && $dirx=='left') {
      $py = $coef*exp(-0.5*($x-$mu)*($x-$mu)/$ss);
      $xpts[] = "[$x,$py]";
      $xpts[] = "[$x,0]";
      $xpts[] = "[$a,0]";
   }
   if ($x !== null && $dirx=='right' && $y===null) {
      $xpts[] = "[$b,0]";
   }
   if ($y !== null && $diry=='left') {
      $py = $coef*exp(-0.5*($y-$mu)*($y-$mu)/$ss);
      $xpts[] = "[$y,$py]";
      $xpts[] = "[$y,0]";
      $xpts[] = "[$x,0]";
   }
   if ($y !== null && $diry=='right') {
      $ypts[] = "[$b,0]";
      $ypts[] = "[$y,0]";
   }

   $plot .= 'fill="none";path([' . implode(',',$pts) . ']);';
   if ($x !== null) {
       $plot .= 'fill="'.$color.'";path([' . implode(',',$xpts) . ']);';
   }
   if ($y !== null && $diry=='right') {
       $plot .= 'fill="'.$color.'";path([' . implode(',',$ypts) . ']);';
   }

   return showasciisvg($plot,$w,$h);
}


//normalcurve3(mu, sigma, a, b, [axislabel, x, dirx, y, diry, color, q, dirw, r, dirr, color, width, height])
//draws a normal curve with mean mu and standard deviation sigma
//This function is good for a basic normal curve, vertically scaled to fit
//with axis labels at multiples of sigma.  This versions allows for the shading
//of TWO regions with different colors
//
//draws on bounds a&lt;x&lt;b, with axis markings at mu+k*sigma
//axis label gives an axis label
//optionally can shade a region based on one or two values x and y
//indiciate shade direction using dirx and diry, which can be "left" or "right"
//optionally can shade a second region based on one or two values q and r
//indiciate shade direction using dirq and dirr, which can be "left" or "right"
//you set use null for y, q, and/or r to set later values
//color indicates color of the shaded regions
//width and height are pixels for the displayed graph.  defaults to 400 by 150
function normalcurve3($mu, $sigma, $a, $b, $axislabel='',$x=null, $dirx='left', $y=null, $diry='right', $color='blue', $q=null, $dirq='left', $r=null, $dirr='right', $color2='red', $w=400,$h=150) {
   if ($sigma<=0) { echo 'Invalid sigma'; return;}
   if ($y!==null && $y<$x) { echo 'Second value should be bigger than the first'; return; }
   if ($a>=$b) { echo 'xmin should be smaller than xmax'; return; }
   if ($y!==null && $dirx==$diry) { echo 'directions should not be equal'; return;}

   $za = ($a-$mu)/$sigma;
   $zb = ($b-$mu)/$sigma;
   if ($x!==null) {
   	   $zx = ($x-$mu)/$sigma;
   }
   if ($y!==null) {
     $zy = ($y-$mu)/$sigma;
   }
   if ($q!==null) {
   	   $zq = ($q-$mu)/$sigma;
   }
   if ($r!==null) {
     $zr = ($r-$mu)/$sigma;
   }
   $dz = ($zb-$za)/80;
   $zab=$za-$sigma;
   $zbb=$zb+$sigma;
   $plot = "setBorder(10,30,10,5);initPicture($za,$zb,-.1,.45);line([$zab,0],[$zbb,0]);";
   for ($i=ceil($za);$i<$zb+$dz;$i++) {
	$label = $mu + $i*$sigma;
	$plot .= "line([$i,.02],[$i,-.02]);text([$i,-.01],\"$label\",\"below\");";
   }

   $midx = $w/2;
   $plot .= "textabs([$midx,0],'$axislabel','above');";
   $pts = array();
   $xpts = array();
   $ypts = array();
   $qpts = array();
   $rpts = array();

   $coef = 1/sqrt(2*3.1415926);

   if ($x !== null && $dirx=='right') {
      $py = $coef*exp(-$zx*$zx/2);
      $xpts[] = "[$zx,0]";
      $xpts[] = "[$zx,$py]";
   }
   if ($y !== null && $diry=='right') {
      $py = $coef*exp(-$zy*$zy/2);
      $ypts[] = "[$zy,$py]";
   }
   if ($q !== null && $dirq=='right') {
      $pr = $coef*exp(-$zq*$zq/2);
      $qpts[] = "[$zq,0]";
      $qpts[] = "[$zq,$pr]";
   }
   if ($r !== null && $dirr=='right') {
      $pr = $coef*exp(-$zr*$zr/2);
      $rpts[] = "[$zr,$pr]";
   }

   for ($z=$za;$z<=$zb+.00001;$z+=$dz) {
	$py = $coef*exp(-$z*$z/2);
        $pts[] = "[$z,$py]";
        if ($x !== null && (($dirx == 'left' && $z<$zx) || ($dirx == 'right' && $z>$zx && ($y===null || $z<$zy )))) {
           $xpts[] = "[$z,$py]";
        }
        if ($y !== null && $diry=='right' && $z>$zy) {
           $ypts[] = "[$z,$py]";
        }
        if ($q !== null && (($dirq == 'left' && $z<$zq) || ($dirq == 'right' && $z>$zq && ($r===null || $z<$zr )))) {
           $qpts[] = "[$z,$py]";
        }
        if ($r !== null && $dirr=='right' && $z>$zr) {
           $rpts[] = "[$z,$py]";
        }
   }
   if ($x !== null && $dirx=='left') {
      $py = $coef*exp(-$zx*$zx/2.0);
      $xpts[] = "[$zx,$py]";
      $xpts[] = "[$zx,0]";
      $xpts[] = "[$za,0]";
   }
   if ($x !== null && $dirx=='right' && $y===null) {
      $xpts[] = "[$zb,0]";
   }
   if ($y !== null && $diry=='left') {
      $py = $coef*exp(-$zy*$zy/2.0);
      $xpts[] = "[$zy,$py]";
      $xpts[] = "[$zy,0]";
      $xpts[] = "[$zx,0]";
   }
   if ($y !== null && $diry=='right') {
      $ypts[] = "[$zb,0]";
      $ypts[] = "[$zy,0]";
   }

   if ($q !== null && $dirq=='left') {
      $py = $coef*exp(-$zq*$zq/2.0);
      $qpts[] = "[$zq,$py]";
      $qpts[] = "[$zq,0]";
      $qpts[] = "[$zq,0]";
   }
   if ($q !== null && $dirq=='right' && $r===null) {
      $qpts[] = "[$zb,0]";
   }
   if ($r !== null && $dirr=='left') {
      $py = $coef*exp(-$zr*$zr/2.0);
      $qpts[] = "[$zr,$py]";
      $qpts[] = "[$zr,0]";
      $qpts[] = "[$zx,0]";
   }
   if ($r !== null && $dirr=='right') {
      $rpts[] = "[$zb,0]";
      $rpts[] = "[$zr,0]";
   }

   $plot .= 'fill="none";path([' . implode(',',$pts) . ']);';
   if ($x !== null) {
       $plot .= 'fill="'.$color.'";path([' . implode(',',$xpts) . ']);';
   }
   if ($y !== null && $diry=='right') {
       $plot .= 'fill="'.$color.'";path([' . implode(',',$ypts) . ']);';
   }
   if ($q !== null) {
       $plot .= 'fill="'.$color2.'";path([' . implode(',',$qpts) . ']);';
   }
   if ($r !== null && $dirr=='right') {
       $plot .= 'fill="'.$color2.'";path([' . implode(',',$rpts) . ']);';
   }
   return showasciisvg($plot,$w,$h);
}

?>

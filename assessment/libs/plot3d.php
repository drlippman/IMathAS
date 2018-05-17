<?php
//Basic 3D display, using HTML5 Canvas or flash fallback.
// Version 2.0 May 3 2018 adding CalcPlot3D functions

global $allowedmacros;
array_push($allowedmacros,"plot3d","spacecurve","replace3dalttext","CalcPlot3Dembed","CalcPlot3Dlink");

//plot3d(f(x,y),xmin,xmax,ymin,ymax,[disc,width,height,axes,alttext]) or
//plot3d("[x(u,v),y(u,v),z(u,v)]",umin,umax,vmin,vmax,[disc,width,height,axes,bounds,alttext])
//discritization is optional - defaults to 20
//width and height default to 300
//axes defaults to 1 (on), set to 0 for off
//bounds: xmin,xmax,ymin,ymax,zmin,zmax
//alttext: text for non-visual users. Can also be added later using replace3dalttext
function plot3d($func,$umin,$umax,$vmin,$vmax,$disc=20,$width=300,$height=300,$axes=1) {
	global $imasroot;
	if ($GLOBALS['inquestiondisplay'] == false) {return '';}

	$alt = '3D Plot';
	if (func_num_args()>14) {
		$bounds = array_slice(func_get_args(),9,6);
		if (func_num_args()>15) {
			$alt = func_get_arg(15);	
		}
	} else if (func_num_args()>9) {
		$alt = func_get_arg(9);	
	}

	if (strpos($func,',')!==FALSE) {
		$isparam = true;
		$func = str_replace('[','',$func);
		$func = str_replace(']','',$func);
		$func = explode(',',$func);
		foreach ($func as $k=>$v) {
			$func[$k] = mathphp($v,'u|v');;
			$func[$k] = str_replace('(u)','($u)',$func[$k]);
			$func[$k] = str_replace('(v)','($v)',$func[$k]);
			$usefunc[$k] = create_function('$u,$v','return('.$func[$k].');');
		}

	} else {
		$isparam = false;
		$func = mathphp($func,'x|y');
		$func = str_replace('(x)','($u)',$func);
		$func = str_replace('(y)','($v)',$func);
		$zfunc = create_function('$u,$v','return('.$func.');');
	}
	$count = 0;
	$du = ($umax-$umin)/($disc-1);
	$dv = ($vmax-$vmin)/($disc-1);
	$urnd = max(floor(-log10($du)-1e-12)+3,2);
	$vrnd = max(floor(-log10($dv)-1e-12)+3,2);
	$zrnd = max($urnd,$vrnd);
	for ($i=0; $i<$disc;$i++) {
		  for ($j=0;$j<$disc;$j++) {
			  if ($count > 0) { $verts .= '~';}
			  $u = $umin+$du*$i;
			  $v = $vmin+$dv*$j;
			  if ($isparam) {
				  $x = round($usefunc[0]($u,$v),$urnd);
				  $y = round($usefunc[1]($u,$v),$vrnd);
				  $z = round($usefunc[2]($u,$v),$zrnd);
				  $verts .= "$x,$y,$z";
			  } else {
				  $z = round($zfunc($u,$v),$zrnd);
				  $u = round($u,$urnd);
				  $v = round($v,$vrnd);
				  $verts .= "$u,$v,$z";
			  }
			  $count++;
		  }
	  }
	  $count = 0;
	  for ($i=0; $i<$disc-1;$i++) {
		  for ($j=0;$j<$disc-1;$j++) {
			  if ($count > 0) { $faces .= '~';}
			  $faces .= ($i*$disc+$j) . ',' ;
			  $faces .= (($i+1)*$disc+$j) . ',';
			  $faces .= (($i+1)*$disc+$j+1) . ',';
			  $faces .= ($i*$disc+$j+1);

			  $count++;
		  }
	  }

	  $useragent = $_SERVER['HTTP_USER_AGENT'];
	  $oldschool = false;
	  if (isset($GLOBALS['sessiondata']['useflash'])) {
		$oldschool = true;
	  } else if (preg_match('/MSIE\s*(\d+)/i',$useragent,$matches)) {
		if ($matches[1]<9) {
			$oldschool =true;
		}
	  }

	  if ($oldschool || isset($GLOBALS['sessiondata']['useflash'])) {
	  	  if (!isset($GLOBALS['3dplotcnt'])) {
			  $r = 1;
		  } else {
			  $r = $GLOBALS['3dplotcnt']+1;
		  }
		  $GLOBALS['3dplotcnt'] = $r;
		  $html .= "<div id=\"plot3d$r\">";
		  $html .= '<p><a href="http://www.adobe.com/go/getflashplayer"><img src="http://www.adobe.com/images/shared/download_buttons/get_flash_player.gif" alt="Get Adobe Flash player" /></a></p>';
		  $html .= '</div>';
		  $html .= '<script type="text/javascript">';
		  $html .= 'var FlashVars = {';
		  $html .= '  verts: "'.$verts.'",';
		  $html .= '  faces: "'.$faces.'",';
		  $html .= "  width: $width, height: $height };";
		  $html .= "  swfobject.embedSWF(\"$imasroot/assessment/libs/viewer3d.swf\", \"plot3d$r\", \"$width\", \"$height\", \"9.0.0\", \"$imasroot/assessment/libs/expressInstall.swf\",FlashVars);";
		  $html .= '</script>';
	  } else {
	  	 if (!isset($GLOBALS['3dplotcnt'])) {
			  $r = 1;
			  $html .= '<script type="text/javascript" src="'.$imasroot.'/javascript/3dviewer.js"></script>';
		  } else {
			  $r = $GLOBALS['3dplotcnt']+1;
		  }
	  	  $GLOBALS['3dplotcnt'] = $r;
	  	  $html .= "<canvas id=\"plot3d$r\" width=\"$width\" height=\"$height\" ";
	  	  $html .= 'role="img" tabindex="0" aria-label="'.Sanitize::encodeStringForDisplay($alt).'" ';
	  	  $html .= ">";
	  	  if (isset($bounds)) {
			  $bndtxt = 'bounds:"' . implode(',',$bounds) . '",';
		  } else {
		  	  $bndtxt='';
		  }
	  	  $url = $GLOBALS['basesiteurl'] . substr($_SERVER['SCRIPT_NAME'],strlen($imasroot)) . (isset($_SERVER['QUERY_STRING'])?'?'.Sanitize::encodeStringForDisplay($_SERVER['QUERY_STRING']).'&useflash=true':'?useflash=true');
		  $html .= "<span aria-hidden=true>Not seeing the 3D graph?  <a href=\"$url\">Try Flash Alternate</a></span>";
	  	  $html .= "</canvas>";
	  	  $html .= "<script type=\"text/javascript\">$(window).on('load',function() {var plot3d$r = new Viewer3D({verts: '$verts', faces: '$faces', $bndtxt width: '$width', height:'$height'}, 'plot3d$r');});</script>";
	  }
	  return $html;

}

//spacecurve("[x(t),y(t),z(t)]",tmin,tmax,[disc,width,height,axes,bounds,alttext])
//discritization is optional - defaults to 50
//width and height default to 300
//axes defaults to 1 (on), set to 0 for off
//bounds: xmin,xmax,ymin,ymax,zmin,zmax
//alttext: text for non-visual users. Can also be added later using replace3dalttext
function spacecurve($func,$tmin,$tmax) {
	global $imasroot;
	if ($GLOBALS['inquestiondisplay'] == false) {return '';}
	if (func_num_args()>3) {
		$disc = func_get_arg(3);
		if (!is_numeric($disc)) {
			$disc = 50;
		}
	} else {
		$disc = 50;
	}
	if (func_num_args()>5) {
		$width = func_get_arg(4);
		$height = func_get_arg(5);
	} else {
		$width = 300;
		$height = 300;
	}
	if (func_num_args()>6) {
		$axes = func_get_arg(6);
	} else {
		$axes = 1;
	}
	if (func_num_args()>12) {
		$bounds = array_slice(func_get_args(),7,6);
	}
	if (func_num_args()>13) {
		$alt = func_get_arg(13);
	} else {
		$alt = '3D Spacecurve';
	}

	$useragent = $_SERVER['HTTP_USER_AGENT'];
	$oldschool = false;
	if (isset($GLOBALS['sessiondata']['useflash'])) {
		$oldschool = true;
	} else if (preg_match('/MSIE\s*(\d+)/i',$useragent,$matches)) {
		if ($matches[1]<9) {
			$oldschool =true;
		}
	}

	if ($oldschool) {

		$func = str_replace('[','',$func);
		$func = str_replace(']','',$func);
		$func = explode(',',$func);
		$func[0] = "(1+.01*cos(u))*({$func[0]})";
		$func[1] = "(1+.01*cos(u))*({$func[1]})";
		$func[2] = "(1+.01*sin(u))*({$func[2]})";
		foreach ($func as $k=>$v) {
			$func[$k] = mathphp($v,'u|t');
			$func[$k] = str_replace('(u)','($u)',$func[$k]);
			$func[$k] = str_replace('(t)','($t)',$func[$k]);
			$usefunc[$k] = create_function('$u,$t','return('.$func[$k].');');
		}

		$count = 0;
		$dt = ($tmax-$tmin)/($disc-1);
		for ($i=0; $i<4;$i++) {
			  for ($j=0;$j<$disc;$j++) {
				  if ($count > 0) { $verts .= '~';}
				  $u = 1.571*$i;
				  $t = $vmin+$dt*$j;

				  $x = $usefunc[0]($u,$t);
				  $y = $usefunc[1]($u,$t);
				  $z = $usefunc[2]($u,$t);
				  $verts .= "$x,$y,$z";

				  $count++;
			  }
		  }
		  $count = 0;
		  for ($i=0; $i<3;$i++) {
			  for ($j=0;$j<$disc-1;$j++) {
				  if ($count > 0) { $faces .= '~';}
				  $faces .= ($i*$disc+$j) . ',' ;
				  $faces .= (($i+1)*$disc+$j) . ',';
				  $faces .= (($i+1)*$disc+$j+1) . ',';
				  $faces .= ($i*$disc+$j+1);

				  $count++;
			  }
		  }
		  if (!isset($GLOBALS['3dplotcnt'])) {
			  $r = 1;
		  } else {
			  $r = $GLOBALS['3dplotcnt']+1;
		  }
		  $GLOBALS['3dplotcnt'] = $r;
		  $html .= "<div id=\"plot3d$r\">";
		  $html .= '<p><a href="http://www.adobe.com/go/getflashplayer"><img src="http://www.adobe.com/images/shared/download_buttons/get_flash_player.gif" alt="Get Adobe Flash player" /></a></p>';
		  $html .= '</div>';
		  $html .= '<script type="text/javascript">';
		  $html .= 'var FlashVars = {';
		  $html .= '  verts: "'.$verts.'",';
		  $html .= '  faces: "'.$faces.'",';
		  $html .= "  width: $width, height: $height };";
		  $html .= "  swfobject.embedSWF(\"$imasroot/assessment/libs/viewer3d.swf\", \"plot3d$r\", \"$width\", \"$height\", \"9.0.0\", \"$imasroot/assessment/libs/expressInstall.swf\",FlashVars);";
		  $html .= '</script>';
	} else {
		//new approach
		$func = str_replace('[','',$func);
		$func = str_replace(']','',$func);
		$func = explode(',',$func);
		foreach ($func as $k=>$v) {
			$func[$k] = mathphp($v,'t');
			$func[$k] = str_replace('(t)','($t)',$func[$k]);
			$usefunc[$k] = create_function('$t','return('.$func[$k].');');
		}

		$count = 0;
		$dt = ($tmax-$tmin)/($disc-1);
		for ($j=0;$j<$disc;$j++) {
			  if ($count > 0) { $verts .= '~';}
			  $t = $vmin+$dt*$j;

			  $x = $usefunc[0]($t);
			  $y = $usefunc[1]($t);
			  $z = $usefunc[2]($t);
			  $verts .= "$x,$y,$z";

			  $count++;
		 }

	  	 if (!isset($GLOBALS['3dplotcnt'])) {
			  $r = 1;
			  $html .= '<script type="text/javascript" src="'.$imasroot.'/javascript/3dviewer.js"></script>';
		 } else {
			  $r = $GLOBALS['3dplotcnt']+1;
		 }
	  	 $GLOBALS['3dplotcnt'] = $r;
	  	 $html .= "<canvas id=\"plot3d$r\" width=\"$width\" height=\"$height\" ";
	  	 $html .= 'role="img" tabindex="0" aria-label="'.Sanitize::encodeStringForDisplay($alt).'" ';
	  	 $html .= ">";
	  	 
	  	 $url = $GLOBALS['basesiteurl'] . substr($_SERVER['SCRIPT_NAME'],strlen($imasroot)) . (isset($_SERVER['QUERY_STRING'])?'?'.Sanitize::encodeStringForDisplay($_SERVER['QUERY_STRING']).'&useflash=true':'?useflash=true');
		  
		 $html .= "<span aria-hidden=true>Not seeing the 3D graph?  <a href=\"$url\">Try Alternate</a></span>";
	  	 $html .= "</canvas>";
	  	 $html .= "<script type=\"text/javascript\">$(window).on('load',function() {var plot3d$r = new Viewer3D({verts: '$verts', curves: true, width: '$width', height:'$height'}, 'plot3d$r');});</script>";
	}

	  return $html;
}

function replace3dalttext($plot, $alttext) {
	return preg_replace('/aria-label="[^"]*"/', 'aria-label="'.Sanitize::encodeStringForDisplay($alttext).'"', $plot);	
}

//CalcPlot3Dembed(functions, [width, height, xmin, xmax, ymin, ymax, zmin, zmax, xscale, yscale, zscale, zclipmin, zclipmax])
//funcs is array of function strings
function CalcPlot3Dembed($funcs, $width=500, $height=500, $xmin=-2, $xmax=2, $ymin=-2, $ymax=2, $zmin=-2, $zmax=2, $xscl=1, $yscl=1, $zscl=1, $zclipmin=null,$zclipmax=null) {
	if ($zclipmin===null) {
		$zclipmin = $zmin - .5*($zmax-$zmin);
	}
	if ($zclipmax===null) {
		$zclipmax = $zmax + .5*($zmax-$zmin);
	}
	$querystring = CalcPlot3Dquerystring($funcs, $xmin, $xmax, $ymin, $ymax, $zmin, $zmax, $xscl, $yscl, $zscl, $zclipmin, $zclipmax);
	$out = '<div class="video-wrapper-wrapper" style="max-width: '.Sanitize::onlyInt($width).'px">';
	$aspectRatio = round(100*$height/$width,2);
	$out .= '<div class="fluid-width-video-wrapper" style="padding-top:'.$aspectRatio.'%">';
	$out .= '<iframe frameborder=0 scrolling="no" ';
	//$querystring is sanitized as it's constructed
	$out .= 'src="https://www.monroecc.edu/faculty/paulseeburger/CalcPlot3D/dynamicFigure/?'.$querystring.'"></iframe>';
	$out .= '</div></div>';
	return $out;
}

//CalcPlot3Dlink(functions, link text, [xmin, xmax, ymin, ymax, zmin, zmax, xscale, yscale, zscale, zclipmin, zclipmax])
//funcs is array of function strings
function CalcPlot3Dlink($funcs, $linktext="View Graph", $xmin=-2, $xmax=2, $ymin=-2, $ymax=2, $zmin=-2, $zmax=2, $xscl=1, $yscl=1, $zscl=1, $zclipmin=null,$zclipmax=null) {
	if ($zclipmin===null) {
		$zclipmin = $zmin - .5*($zmax-$zmin);
	}
	if ($zclipmax===null) {
		$zclipmax = $zmax + .5*($zmax-$zmin);
	}
	$querystring = CalcPlot3Dquerystring($funcs, $xmin, $xmax, $ymin, $ymax, $zmin, $zmax, $xscl, $yscl, $zscl, $zclipmin, $zclipmax);
	//$querystring is sanitized as it's constructed
	$out = '<a href="https://www.monroecc.edu/faculty/paulseeburger/CalcPlot3D/?'.$querystring.'" target="_blank">';
	$out .= Sanitize::encodeStringForDisplay($linktext).'</a>';
	return $out;
}


function CalcPlot3Dquerystring($funcs, $xmin, $xmax, $ymin, $ymax, $zmin, $zmax, $xscl, $yscl, $zscl, $zclipmin, $zclipmax) {
	$out = array();
	if (!is_array($funcs)) {
		$funcs = array($funcs);
	}
	foreach ($funcs as $func) {
		$out[] = CalcPlot3DprepFunc($func, $xmin, $xmax, $ymin, $ymax, $zmin, $zmax);
	}
	$win = "type=window;xmin=$xmin;xmax=$xmax;ymin=$ymin;ymax=$ymax;zmin=$zmin;zmax=$zmax;";
	$win .= "xscale=$xscl;yscale=$yscl;zscale=$zscl;zcmin=$zclipmin;zcmax=$zclipmax";
	$out[] = $win;
	return implode('&', array_map('Sanitize::encodeUrlParam', $out));
}

//Function string formats:
//  Regular: 		z=x^2+y^2,[xmin,xmax,ymin,ymax,gridlines]
//				defaults: window's xmin,xmax,ymin,ymax, 30
//  Implicit: 		x^2+y^2=z^2,[xmin,ymin,xmax,ymax,zmin,zmax,cubes]
//				defaults: window's xmin,xmax,ymin,ymax,zmin,zmax 16
//  Spacecurve: 	curve,x(t),y(t),z(t),[tmin,tmax,tsteps]
//				defaults: -10,10,100
//  Parametric surf: 	psurf,x(u,v),y(u,v),z(u,v),[umin,umax,vmin,vmax,usteps,vsteps]
//				defaults: 0,2pi,0,pi,30,15
//  Region: 		region,y=f(x) bottom func,y=g(x) top func,z top function,[xmin,xmax]
//				xmin,xmax defaults to -1,1
//          			Example:  region,y=1,y=2-x^2,z=x^2+y^2
//         		region,x=f(y) left func,x=g(y) right func,z top function,[ymin,ymax]
function CalcPlot3DprepFunc($str,$gxmin=-2,$gxmax=2,$gymin=-2,$gymax=2,$gzmin=-2,$gzmax=2) {
	$bits = array_map('trim', explode(',', $str));
	$out = array();
	if ($bits[0] == 'region') {
		if (count($bits)<4) {
			echo 'Insufficient information provided for CalcPlot3D region';
			return '';
		}
		$out[] = 'type=region';
		if ($bits[1][0]=='y') {
			$out[] = 'region=x';
		} else {
			$out[] = 'region=y';;
		}
		$out[] = 'bot2d='.substr($bits[1],2);
		$out[] = 'top2d='.substr($bits[2],2);
		$out[] = 'top3d='.substr($bits[3],2);
		$def = array(array('umin','umax'), array(-1,1));
		$start = 4;
	} else if ($bits[0]=='curve') {
		if (count($bits)<4) {
			echo 'Insufficient information provided for CalcPlot3D spacecurve';
			return '';
		}
		$out[] = 'type=spacecurve';
		$out[] = 'spacecurve=curve';
		$out[] = 'x='.$bits[1];
		$out[] = 'y='.$bits[2];
		$out[] = 'z='.$bits[3];
		$def = array(array('tmin','tmax','tsteps'), array(-10,10,100));
		$start = 4;
	} else if ($bits[0]=='psurf') {
		if (count($bits)<4) {
			echo 'Insufficient information provided for CalcPlot3D parametric surface';
			return '';
		}
		$out[] = 'type=parametric';
		$out[] = 'parametric=2';
		$out[] = 'x='.$bits[1];
		$out[] = 'y='.$bits[2];
		$out[] = 'z='.$bits[3];
		$def = array(array('umin','umax','vmin','vmax','usteps','vsteps'), array(0,"2pi",0,"pi",30,15));
		$start = 4;
	} else {
		$funcparts = explode('=',$bits[0]);
		if (count($funcparts)==1) {
			$funcparts = array('z',$funcparts[0]);
		}
		if ($funcparts[0] == 'z') { //basic z= function
			$out[] = 'type=z';
			$out[] = 'z='.$funcparts[1];
			$def = array(array('umin','umax','vmin','vmax','grid'), array($gxmin,$gxmax,$gymin,$gymax,30));
			$start = 1;
		} else { //implicit
			$out[] = 'type=implicit';
			$out[] = 'equation='.$funcparts[0].'~'.$funcparts[1];
			$def = array(array('xmin','xmax','ymin','ymax','zmin','zmax','cubes'), array($gxmin,$gxmax,$gymin,$gymax,$gzmin,$gzmax,16));
			$start = 1;
		}
	}
	for ($i=0;$i<count($def[0]);$i++) {
		if (isset($bits[$start+$i]) && $bits[$start+$i]!=='') {
			$out[] = $def[0][$i].'='.$bits[$start+$i];
		} else {
			$out[] = $def[0][$i].'='.$def[1][$i];
		}
	}
	$out[] = 'visible=true';
	return implode(';',$out);
}
?>

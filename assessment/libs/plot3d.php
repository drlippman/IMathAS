<?php
//Basic 3D display, using Java applet.  Version 1.1, April 24, 2006
//requires copying Viewer.class into assessment/libs directory

require_once("../../includes/sanitize.php");

global $allowedmacros;
array_push($allowedmacros,"plot3d","spacecurve");

//plot3d(f(x,y),xmin,xmax,ymin,ymax,[disc,width,height,axes]) or
//plot3d("[x(u,v),y(u,v),z(u,v)]",umin,umax,vmin,vmax,[disc,width,height,axes,bounds])
//discritization is optional - defaults to 20
//width and height default to 300
//axes defaults to 1 (on), set to 0 for off
//bounds: xmin,xmax,ymin,ymax,zmin,zmax
function plot3d($func,$umin,$umax,$vmin,$vmax) {
	global $imasroot;
	if ($GLOBALS['inquestiondisplay'] == false) {return '';}
	if (func_num_args()>5) {
		$disc = func_get_arg(5);
		if (!is_numeric($disc)) {
			$disc = 20;
		}
	} else {
		$disc = 20;
	}
	if (func_num_args()>7) {
		$width = func_get_arg(6);
		$height = func_get_arg(7);
	} else {
		$width = 300;
		$height = 300;
	}
	if (func_num_args()>8) {
		$axes = func_get_arg(8);
	} else {
		$axes = 1;
	}
	if (func_num_args()>14) {
		$bounds = array_slice(func_get_args(),9,6);
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
	  	  $html .= "<canvas id=\"plot3d$r\" width=\"$width\" height=\"$height\">";
	  	  if (isset($bounds)) {
			  $bndtxt = 'bounds:"' . implode(',',$bounds) . '",';
		  } else {
		  	  $bndtxt='';
		  }
	  	  $url = $GLOBALS['urlmode']  . Sanitize::domainNameWithPort($_SERVER['HTTP_HOST']) . $_SERVER['PHP_SELF'] . (isset($_SERVER['QUERY_STRING'])?'?'.$_SERVER['QUERY_STRING'].'&useflash=true':'?useflash=true');
		  $html .= "Not seeing the 3D graph?  <a href=\"$url\">Try Flash Alternate</a>";
	  	  $html .= "</canvas>";
	  	  $html .= "<script type=\"text/javascript\">$(window).on('load',function() {var plot3d$r = new Viewer3D({verts: '$verts', faces: '$faces', $bndtxt width: '$width', height:'$height'}, 'plot3d$r');});</script>";
	  } 
	/*	  
	  } else {
		  $html .= "<applet codebase=\"{$GLOBALS['imasroot']}/assessment/libs\" code=\"Viewer.class\" width=$width height=$height>\n";
		  $html .= "<param name=\"verts\" value=\"$verts\">\n";
		  $html .= "<param name=\"faces\" value=\"$faces\">\n";
		  if ($axes==1) {
			  $html .= "<param name=\"axes\" value=\"show\">\n";
		  } else {
			  $html .= "<param name=\"axes\" value=\"hide\">\n";
		  }
		  if (isset($bounds)) {
			  $html .= "<param name=\"bounds\" value=\"" . implode(',',$bounds) . "\">\n";
		  }
		  $url = $GLOBALS['urlmode']  . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . (isset($_SERVER['QUERY_STRING'])?'?'.$_SERVER['QUERY_STRING'].'&useflash=true':'?useflash=true');
				 
		  $html .= "</applet><br/>Not seeing the 3D graph?  <a href=\"$url\">Try Flash Alternate</a>\n";
	  }
	  */
	  return $html;
	
}

//spacecurve("[x(t),y(t),z(t)]",tmin,tmax,[disc,width,height,axes,bounds])
//discritization is optional - defaults to 50
//width and height default to 300
//axes defaults to 1 (on), set to 0 for off
//bounds: xmin,xmax,ymin,ymax,zmin,zmax
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
		  /*if (isset($GLOBALS['sessiondata']['useflash'])) {
			  
		  } else {
			  $html = "<applet codebase=\"{$GLOBALS['imasroot']}/assessment/libs\" code=\"Viewer.class\" width=$width height=$height>\n";
			  $html .= "<param name=\"verts\" value=\"$verts\">\n";
			  $html .= "<param name=\"faces\" value=\"$faces\">\n";
			  if ($axes==1) {
				  $html .= "<param name=\"axes\" value=\"show\">\n";
			  } else {
				  $html .= "<param name=\"axes\" value=\"hide\">\n";
			  }
			  $html .= "<param name=\"edges\" value=\"hide\">\n";
			  if (isset($bounds)) {
				  $html .= "<param name=\"bounds\" value=\"" . implode(',',$bounds) . "\">\n";
			  }
			  $url = $GLOBALS['urlmode']  . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . (isset($_SERVER['QUERY_STRING'])?'?'.$_SERVER['QUERY_STRING'].'&useflash=true':'?useflash=true');
					 
			  $html .= "Not seeing the 3D graph?  <a href=\"$url\">Try Alternate</a></applet>\n";
		  } */
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
	  	 $html .= "<canvas id=\"plot3d$r\" width=\"$width\" height=\"$height\">";
	  	 $url = $GLOBALS['urlmode']  . Sanitize::domainNameWithPort($_SERVER['HTTP_HOST']) . $_SERVER['PHP_SELF'] . (isset($_SERVER['QUERY_STRING'])?'?'.$_SERVER['QUERY_STRING'].'&useflash=true':'?useflash=true');
		 $html .= "Not seeing the 3D graph?  <a href=\"$url\">Try Alternate</a>";
	  	 $html .= "</canvas>";
	  	 $html .= "<script type=\"text/javascript\">$(window).on('load',function() {var plot3d$r = new Viewer3D({verts: '$verts', curves: true, width: '$width', height:'$height'}, 'plot3d$r');});</script>";
	}
	  
	  return $html;
	
}
?>

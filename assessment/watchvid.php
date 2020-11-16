<?php
require_once(__DIR__ . '/../includes/sanitize.php');

$url = $_GET['url'];
$doembed = false;
 if((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO']=='https'))  {
	 $urlmode = 'https://';
 } else {
 	 $urlmode = 'http://';
 }
$isyoutube = false;
if (strpos($url,'youtube.com/watch')!==false) {
	//youtube
	$vidid = substr($url,strrpos($url,'v=')+2);
  $vidid = str_replace(array('?','%3F','%3D'),array('&','&','='),$vidid);
	if (strpos($vidid,'&')!==false) {
		$vidid = substr($vidid,0,strpos($vidid,'&'));
	}
	if (strpos($vidid,'#')!==false) {
		$vidid = substr($vidid,0,strpos($vidid,'#'));
	}
  $isyoutube = true;
}
if (strpos($url,'youtu.be/')!==false) {
	//youtube
	$vidid = substr($url,strpos($url,'.be/')+4);
  $vidid = str_replace(array('%3F','%26','%3D'),array('?','&','='),$vidid);
	if (strpos($vidid,'#')!==false) {
		$vidid = substr($vidid,0,strpos($vidid,'#'));
	}
	if (strpos($vidid,'?')!==false) {
		$vidid = substr($vidid,0,strpos($vidid,'?'));
	}
  if (strpos($vidid,'&')!==false) {
		$vidid = substr($vidid,0,strpos($vidid,'&'));
	}
  $isyoutube = true;
}
if (strpos($url,'youtube.com/embed')!==false) {
	//youtube
	$vidid = substr($url,strpos($url,'embed/')+6);
  $vidid = str_replace(array('%3F','%26','%3D'),array('?','&','='),$vidid);
	if (strpos($vidid,'#')!==false) {
		$vidid = substr($vidid,0,strpos($vidid,'#'));
	}
	if (strpos($vidid,'?')!==false) {
		$vidid = substr($vidid,0,strpos($vidid,'?'));
	}
  if (strpos($vidid,'&')!==false) {
		$vidid = substr($vidid,0,strpos($vidid,'&'));
	}
  $isyoutube = true;
}
if ($isyoutube) {
	$vidid = str_replace(array(" ","\n","\r","\t"),'',$vidid);
  $vidid = preg_replace('/[^A-Za-z0-9_-]/','',$vidid);
	$timestart = '?rel=0';
	if (strpos($url,'start=')!==false) {
		preg_match('/start=(\d+)/',$url,$m);
		$timestart .= '&'.$m[0];
	} else if (strpos($url,'t=')!==false) {
		preg_match('/\Wt=((\d+)m)?((\d+)s)?/',$url,$m);
		$timestart .= '&start='.((empty($m[2])?0:$m[2]*60) + (empty($m[4])?0:$m[4]*1));
	}

	if (strpos($url,'end=')!==false) {
		preg_match('/end=(\d+)/',$url,$m);
		$timestart .= '&'.$m[0];
	}
	$doembed = true;
	$videoUrl = $urlmode.'www.youtube.com/embed/'.$vidid.$timestart;
	$out = '<iframe width="853" height="480" src="'.Sanitize::url($videoUrl).'" frameborder="0" allowfullscreen></iframe>';
}
if (strpos($url,'vimeo.com/')!==false) {
	//youtube
	$vidid = substr($url,strpos($url,'.com/')+5);
  $vidid = preg_replace('/[^0-9]/','',$vidid);
	$doembed = true;
	$videoUrl = 'http://player.vimeo.com/video/'.$vidid;
	$out = '<iframe width="853" height="480" src="'.Sanitize::url($videoUrl).'" frameborder="0" allowfullscreen></iframe>';
}
if ($doembed) {
	echo '<html><head><title>Video</title>';
	echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
	echo '<style type="text/css"> html, body {margin: 0px} html {padding:0px} body {padding: 10px;}</style>';
	echo '<script type="text/javascript">childTimer = window.setInterval(function(){try{window.opener.popupwins[\'video\'] = window;} catch(e){}}, 300);imasroot="";</script>';
	echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js" type="text/javascript"></script>';
    echo '<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/fitvids/1.2.0/jquery.fitvids.min.js"></script>';
    echo '<script>$(function() {$("body").fitVids();});</script>';
	echo '</head>';
	echo '<body><div style="width:100%">'.$out.'</div></body></html>';
} else {
  http_response_code(403);
  exit;
	//header("Location:". Sanitize::url($url));
}
?>

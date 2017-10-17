<?php
require_once(__DIR__ . '/../includes/sanitize.php');

$url = $_GET['url'];
$doembed = false;
 if((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO']=='https'))  {
	 $urlmode = 'https://';
 } else {
 	 $urlmode = 'http://';
 }
if (strpos($url,'youtube.com/watch')!==false) {
	//youtube
	$vidid = substr($url,strrpos($url,'v=')+2);
	if (strpos($vidid,'&')!==false) {
		$vidid = substr($vidid,0,strpos($vidid,'&'));
	}
	if (strpos($vidid,'#')!==false) {
		$vidid = substr($vidid,0,strpos($vidid,'#'));
	}
	$vidid = str_replace(array(" ","\n","\r","\t"),'',$vidid);
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
	$out = '<iframe width="640" height="510" src="'.Sanitize::url($videoUrl).'" frameborder="0" allowfullscreen></iframe>';
}
if (strpos($url,'youtu.be/')!==false) {
	//youtube
	$vidid = substr($url,strpos($url,'.be/')+4);
	if (strpos($vidid,'#')!==false) {
		$vidid = substr($vidid,0,strpos($vidid,'#'));
	}
	if (strpos($vidid,'?')!==false) {
		$vidid = substr($vidid,0,strpos($vidid,'?'));
	}
	$vidid = str_replace(array(" ","\n","\r","\t"),'',$vidid);
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
	$out = '<iframe width="640" height="510" src="'.Sanitize::url($videoUrl).'" frameborder="0" allowfullscreen></iframe>';
}
if (strpos($url,'vimeo.com/')!==false) {
	//youtube
	$vidid = substr($url,strpos($url,'.com/')+5);
	$doembed = true;
	$videoUrl = 'http://player.vimeo.com/video/'.$vidid;
	$out = '<iframe width="640" height="510" src="'.Sanitize::url($videoUrl).'" frameborder="0" allowfullscreen></iframe>';
}
if ($doembed) {
	echo '<html><head><title>Video</title>';
	echo '<meta name="viewport" content="width=660, initial-scale=1">';
	echo '<style type="text/css"> html, body {margin: 0px} html {padding:0px} body {padding: 10px;}</style>';
	echo '<script type="text/javascript">childTimer = window.setInterval(function(){try{window.opener.popupwins[\'video\'] = window;} catch(e){}}, 300);</script>';
	echo '</head>';
	echo '<body>'.$out.'</body></html>';
} else {
	header("Location:". Sanitize::url($url));
}
?>

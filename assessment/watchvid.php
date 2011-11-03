<?php
$url = $_GET['url'];
$doembed = false;

if (strpos($url,'youtube.com/watch')!==false) {
	//youtube 	
	$vidid = substr($url,strrpos($url,'v=')+2);
	if (strpos($vidid,'&')!==false) {
		$vidid = substr($vidid,0,strpos($vidid,'&'));
	} 
	$doembed = true;
	$out = '<iframe width="640" height="510" src="http://www.youtube.com/embed/'.$vidid.'" frameborder="0" allowfullscreen></iframe>';
}
if (strpos($url,'youtu.be/')!==false) {
	//youtube 	
	$vidid = substr($url,strpos($url,'.be/')+4);
	$doembed = true;
	$out = '<iframe width="640" height="510" src="http://www.youtube.com/embed/'.$vidid.'" frameborder="0" allowfullscreen></iframe>';
}
if (strpos($url,'vimeo.com/')!==false) {
	//youtube 	
	$vidid = substr($url,strpos($url,'.com/')+5);
	$doembed = true;
	$out = '<iframe width="640" height="510" src="http://player.vimeo.com/video/'.$vidid.'" frameborder="0" allowfullscreen></iframe>';
}
if ($doembed) {
	echo '<html><head><title>Video</title>';
	echo '<style type="text/css"> html, body {margin: 0px} html {padding:0px} body {padding: 10px;}</style>';
	echo '</head>';
	echo '<body>'.$out.'</body></html>';
} else {
	header("Location: $url");
}
?>

<?php
$url = $_GET['url'];

if (strpos($url,'youtube.com/watch')!==false) {
	//youtube 	
	$vidid = substr($url,strrpos($url,'v=')+2);
	if (strpos($vidid,'&')!==false) {
		$vidid = substr($vidid,0,strpos($vidid,'&'));
	}
	/*$out = '<object width="640" height="505">';
	$out .= '<param name="movie" value="http://www.youtube.com/v/'.$vidid.'&hl=en_US&fs=1&"></param>';
	$out .= '<param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param>';
	$out .= '<embed src="http://www.youtube.com/v/'.$vidid.'&hl=en_US&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="640" height="505"></embed></object>';
	*/
	$out = '<iframe width="640" height="510" src="http://www.youtube.com/embed/'.$vidid.'" frameborder="0" allowfullscreen></iframe>';
	
	echo '<html><head><title>Video</title>';
	echo '<style type="text/css"> html, body {margin: 0px} html {padding:0px} body {padding: 10px;}</style>';
	echo '</head>';
	echo '<body>'.$out.'</body></html>';
} else {
	header("Location: $url");
}
?>

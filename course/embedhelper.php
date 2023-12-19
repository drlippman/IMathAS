<!DOCTYPE html>
<html lang="en">
<head>
    <title>Embedded Content</title>
<style type="text/css">
body,html {
	margin: 0; 
	padding: 0;
}
</style>
</head>
<body>
<?php
require_once "../includes/sanitize.php";
if((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO']=='https'))  {
 	 $urlmode = 'https://';
} else {
 	 $urlmode = 'http://';
}
$url = $_GET['url'];
$type = $_GET['type'];
$w = $_GET['w'];
$h = $_GET['h'];
if ($type=='tegrity' && substr($url,0,18)=='https://tegr.it/y/') {
	echo '<script type="text/javascript" src="' . Sanitize::encodeStringForDisplay($url) . '"></script>';
} else if ($type=='cdf') {
  	echo '<script type="text/javascript" src="https://www.wolfram.com/cdf-player/plugin/v2.1/cdfplugin.js"></script>';
	echo '<script type="text/javascript">var cdf = new cdfplugin();';
	echo "cdf.embed('" . Sanitize::encodeStringForJavascript($url) . "'," . Sanitize::encodeStringForJavascript($w) . "," . Sanitize::encodeStringForJavascript($h) . ");</script>";
}
?>
</body>
</html>

<html>
<head>
<style type="text/css">
body,html {
	margin: 0; 
	padding: 0;
}
</style>
</head>
<body>
<?php
if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') {
 	 $urlmode = 'https://';
} else {
 	 $urlmode = 'http://';
}
$url = $_GET['url'];
$type = $_GET['type'];
$w = $_GET['w'];
$h = $_GET['h'];
if ($type=='tegrity') {
	echo '<script type="text/javascript" src="'.$url.'"></script>';
} else if ($type=='cdf') {
  	echo '<script type="text/javascript" src="'.$urlmode.'www.wolfram.com/cdf-player/plugin/v2.1/cdfplugin.js"></script>';
	echo '<script type="text/javascript">var cdf = new cdfplugin();';
	echo "cdf.embed('$url',$w,$h);</script>";
}
?>
</body>
</html>

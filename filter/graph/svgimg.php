<?php
	$dbsetup = true; //to prevent database connection
	require("../../config.php");
	if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') {
		 $urlmode = 'https://';
	 } else {
		 $urlmode = 'http://';
	 }
	$imgdir = 'imgs/'; //relative to current dir
	$host  = $_SERVER['HTTP_HOST'];
	$uri  = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
	if (isset($_GET['script']) && trim($_GET['script']!='')) {
		$_GET['script'] = stripslashes($_GET['script']);
		$fn = md5($_GET['script']);
		if (!file_exists($imgdir.$fn.'.png')) {
			include("asciisvgimg.php");
			$AS = new AStoIMG(300,300);
			$AS->processScript($_GET['script']);
			$AS->outputimage($imgdir.$fn.'.png');
		}
	} else if (isset($_GET['sscr'])) {
		$_GET['sscr'] = stripslashes($_GET['sscr']);
		$fn = md5($_GET['sscr']);
		if (!file_exists($imgdir.$fn.'.png')) {
			include("asciisvgimg.php");
			$AS = new AStoIMG(300,300);
			$AS->processShortScript($_GET['sscr']);
			$AS->outputimage($imgdir.$fn.'.png');
		}
	}
	header("Location: $urlmode$host$uri/$imgdir$fn.png");
?>

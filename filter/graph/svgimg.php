<?php
	$dbsetup = true; //to prevent database connection
	require_once "../../init_without_validate.php";
	$myrights = 5;
	$imgdir = __DIR__ . '/imgs/'; //relative to current dir

	if (isset($_GET['script']) && trim($_GET['script']!='')) {
		$fn = md5($_GET['script']);
		if (!file_exists($imgdir.$fn.'.png')) {
			require_once "asciisvgimg.php";
			$AS = new AStoIMG(300,300);
			$AS->processScript($_GET['script']);
			$AS->outputimage($imgdir.$fn.'.png');
		}
	} else if (isset($_GET['sscr'])) {
		$fn = md5($_GET['sscr']);
		if (!file_exists($imgdir.$fn.'.png')) {
			require_once "asciisvgimg.php";
			$AS = new AStoIMG(300,300);
			$AS->processShortScript($_GET['sscr']);
			$AS->outputimage($imgdir.$fn.'.png');
		}
	}
	//header("Location: " . $GLOBALS['basesiteurl'] . "/filter/graph/$imgdir$fn.png");
	header("Content-Type: image/png");
	header("Content-Length: " . filesize($imgdir.$fn.'.png'));
	$fp = fopen($imgdir.$fn.'.png', 'rb');
	fpassthru($fp);
?>

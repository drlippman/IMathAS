<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title><?php echo $installname;?> Assessment</title>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<?php
$start_time = microtime(true); 
//load filter
$curdir = rtrim(dirname(__FILE__), '/\\');
require("$curdir/../filter/filter.php");
?>
<link rel="stylesheet" href="<?php echo $imasroot . "/assessment/mathtest.css";?>" type="text/css"/>
<?php
if ($isdiag) {
	echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"$imasroot/diag/print.css\" media=\"print\"/>\n";
} else {
	echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"$imasroot/assessment/print.css\" media=\"print\"/>\n";
}

if ($sessiondata['mathdisp']==1) {
	echo "<script src=\"$imasroot/javascript/ASCIIMathML.js\" type=\"text/javascript\"></script>\n";
} else if ($sessiondata['mathdisp']==2) {
	echo '<script type="text/javascript">var AMTcgiloc = "'.$mathimgurl.'";</script>'; 
	echo "<script src=\"$imasroot/javascript/ASCIIMathTeXImg.js\" type=\"text/javascript\"></script>\n";
} else if ($sessiondata['mathdisp']==0) {
	echo '<script type="text/javascript">var noMathRender = true;</script>';	
}

if ($sessiondata['graphdisp']==1) {
	echo "<script src=\"$imasroot/javascript/ASCIIsvg.js\" type=\"text/javascript\"></script>\n";
	echo "<script src=\"$imasroot/course/editor/plugins/AsciiSvg/ASCIIsvgAddon.js\" type=\"text/javascript\"></script>\n";
} else {
	echo "<script src=\"$imasroot/javascript/mathjs.js\" type=\"text/javascript\"></script>\n";
}
/*
<script src="<?php echo $imasroot . "/javascript/ASCIIMathML.js";?>" type="text/javascript"></script>
<script src="<?php echo $imasroot . "/javascript/ASCIIsvg.js";?>" type="text/javascript"></script>
<script src="<?php echo $imasroot . "/course/editor/plugins/AsciiSvg/ASCIIsvgAddon.js";?>" type="text/javascript"></script>

*/
?>
<script src="<?php echo $imasroot . "/javascript/AMhelpers.js";?>" type="text/javascript"></script>
<script src="<?php echo $imasroot . "/javascript/confirmsubmit.js";?>" type="text/javascript"></script>
</head>
<body>
<div class=main>

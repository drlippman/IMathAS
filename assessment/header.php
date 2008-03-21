<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<meta http-equiv="X-UA-Compatible" content="IE=7" />
<title><?php echo $installname;?> Assessment</title>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<?php
$start_time = microtime(true); 
//load filter
$curdir = rtrim(dirname(__FILE__), '/\\');
$loadgraphfilter = true;
require("$curdir/../filter/filter.php");
?>
<script type="text/javascript">
function init() {
	for (var i=0; i<initstack.length; i++) {
		var foo = initstack[i]();
	}
}
initstack = new Array();
window.onload = init;
</script>
<link rel="stylesheet" href="<?php echo $imasroot . "/assessment/mathtest.css";?>" type="text/css"/>
<?php
if ($isdiag) {
	echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"$imasroot/diag/print.css\" media=\"print\"/>\n";
} else {
	echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"$imasroot/assessment/print.css\" media=\"print\"/>\n";
}

if ($sessiondata['mathdisp']==1) {
	echo "<script type=\"text/javascript\" src=\"$imasroot/javascript/ASCIIMathML.js\"></script>\n";
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
<!--[if IE]><script type="text/javascript" src="<?php echo $imasroot;?>/javascript/excanvas.js"></script><![endif]-->
<script type="text/javascript" src="<?php echo $imasroot;?>/javascript/drawing.js"></script>
<?php
echo "<script type=\"text/javascript\">imasroot = '$imasroot';</script>";
if ($useeditor==1 && $sessiondata['useed']==1) {
	echo <<<END
	<script type="text/javascript">
	  _editor_url = "$imasroot/course/editor";
	  _imasroot = "$imasroot/";
	  _editor_lang = "en";
	</script>
	<script type="text/javascript" src="$imasroot/course/editor/htmlarea.js"></script>
	<script type="text/javascript">
END;
	if (!isset($sessiondata['mathdisp']) || $sessiondata['mathdisp']==1 || $sessiondata['mathdisp']==2) {
	 echo 'HTMLArea.loadPlugin("AsciiMath");';
	} 
	if (!isset($sessiondata['graphdisp']) || $sessiondata['graphdisp']==1) {
	 echo 'HTMLArea.loadPlugin("AsciiSvg");';
	 echo 'var svgimgbackup = false;';
	} else if ($sessiondata['graphdisp']==2) {
	 echo 'HTMLArea.loadPlugin("AsciiSvg");';
	 echo 'var svgimgbackup = true;';
	}
	 echo 'var AScgiloc ="'.$imasroot.'/filter/graph/svgimg.php";'; 
	echo <<<END
	</script>
	
	<script type="text/javascript">
	var editor = new Array();
	var editornames = new Array();
	function initEditor() {
		for (i=0;i<editornames.length;i++) {
			editor[i] = new HTMLArea(editornames[i]);
			editor[i].config.hideSomeButtons(" popupeditor lefttoright righttoleft htmlmode ");
END;
	if (!isset($sessiondata['mathdisp']) || $sessiondata['mathdisp']==1 || $sessiondata['mathdisp']==2) {
		echo "editor[i].registerPlugin(AsciiMath);\n";
		//surrounds AsciiMath in red box while editting.  Change to your liking
		echo "editor[i].config.pageStyle = \"span.AMedit {border:solid 1px #ff0000}\";\n";
		echo "editor[i].config.toolbar[1].push(\"separator\",\"insertnewmath\",\"insertmath\",\"swapmathmode\");\n";
	}
	if (!isset($sessiondata['graphdisp']) || $sessiondata['graphdisp']==1 || $sessiondata['graphdisp']==2) {
		echo "editor[i].registerPlugin(AsciiSvg);\n";
		echo "editor[i].config.toolbar[1].push(\"separator\",\"insertsvg\");\n";
	}
	echo "editor[i].generate();\n";
	echo "}";
	echo "return false; }; </script>";
} else {
	echo "<script>var editornames = new Array(); function initEditor() { };</script>";
}
?>

</head>
<body>
<div class=main>

<!DOCTYPE html>
<html>
<head>
<meta http-equiv="X-UA-Compatible" content="IE=7" />
<title>Math Chat - <?php echo $roomname;?></title>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<link rel="stylesheet" href="mathchat.css" type="text/css" />
<!--[if lt IE 7]>
<script type="text/javascript">
function resizechat() {
	winW = document.body.offsetWidth;
	winH = document.body.offsetHeight;
	document.getElementById("msgbody").style.height = (winH - 200) + 'px';
	document.getElementById("users").style.height = (winH - 200) + 'px';
	document.getElementById("msgbody").style.width = (winW - 200) + 'px';
	window.onresize = resizechat;
	}
window.onload = resizechat;
</script>
<![endif]-->
<script type="text/javascript" src="mathchat.js"></script>
<script type="text/javascript" src="../javascript/ASCIIsvg_min.js"></script>
<script type="text/javascript" src="../javascript/mathjs.js"></script>
<script type="text/javascript">var AMTcgiloc = "<?php echo $mathimgurl;?>";</script>
<script type="text/javascript">var AScgiloc = "<?php echo $svgimgurl;?>";</script>
<?php
if ($mcsession['mathdisp']==2) {
	echo "<script src=\"../javascript/ASCIIMathTeXImg_min.js?ver=092514\" type=\"text/javascript\"></script>\n";
	echo "<script type=\"text/javascript\">var usingASCIIMath = false; var AMnoMathML=true; function rendermathnode(el) {AMprocessNode(el);}</script>";
} else {
	echo "<script src=\"../javascript/ASCIIMathTeXImg_min.js?ver=092314\" type=\"text/javascript\"></script>\n";
	echo '<script type="text/x-mathjax-config">
		if (MathJax.Hub.Browser.isChrome || MathJax.Hub.Browser.isSafari) {
			MathJax.Hub.Config({"HTML-CSS": {preferredFont: "STIX", imageFont:null}});
		} else {
			MathJax.Hub.Config({"HTML-CSS": {preferredFont: "STIX", webFont: "STIX-Web", imageFont:null}});
		}
		</script>';
		// webFont: "STIX-Web", 
	//echo '<script type="text/javascript" src="https://c328740.ssl.cf1.rackcdn.com/mathjax/latest/MathJax.js?config=AM_HTMLorMML"></script>';
	//echo '<script>window.MathJax || document.write(\'<script type="text/x-mathjax-config">MathJax.Hub.Config({"HTML-CSS":{imageFont:null}});<\/script><script src="'.$imasroot.'/mathjax/MathJax.js?config=AM_HTMLorMML"><\/script>\')</script>';
	echo '<script type="text/javascript" src="../mathjax/MathJax.js?config=AM_HTMLorMML"></script>';
	echo '<script type="text/javascript">noMathRender = false; var usingASCIIMath = true; var AMnoMathML = true; function rendermathnode(node) { MathJax.Hub.Queue(["Typeset", MathJax.Hub, node]); }</script>'; 
}
if ($mcsession['graphdisp']==2) {
	echo '<script type="text/javascript">ASnoSVG = true;</script>';
} 

$start_time = microtime(true); 
if (isset($placeinhead)) {
	echo $placeinhead;
}
if (isset($useeditor) && $mcsession['useed']==1) {
echo <<<END
<script type="text/javascript" src="$editorloc/tiny_mce.js"></script>

<script type="text/javascript">
tinyMCE.init({
    mode : "exact",
    elements : "$useeditor",
    theme : "advanced",
    theme_advanced_buttons1 : "bold,italic,underline,separator,cut,copy,paste,separator,numlist,bullist,forecolor,backcolor,separator,asciimath,asciimathcharmap,asciisvg",
    theme_advanced_buttons2 : "",
    theme_advanced_buttons3 : "",
    theme_advanced_fonts : "Arial=arial,helvetica,sans-serif,Courier New=courier new,courier,monospace,Georgia=georgia,times new roman,times,serif,Tahoma=tahoma,arial,helvetica,sans-serif,Times=times new roman,times,serif,Verdana=verdana,arial,helvetica,sans-serif",
    theme_advanced_toolbar_location : "top",
    theme_advanced_toolbar_align : "left",
    plugins : 'safari,asciimath,asciisvg,inlinepopups',
    theme_advanced_resizing : true,
    AScgiloc : '$svgimgurl',
    ASdloc : '$svgdloc'
});
</script>
<!-- /TinyMCE -->

</head>
<body>

END;

} else {
	echo "</head>\n";
	echo "<body>\n";
}

?>
<div class=mainbody>

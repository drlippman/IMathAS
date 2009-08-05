<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
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
<script type="text/javascript" src="../javascript/ASCIIMathMLwFallback.js"></script>
<script type="text/javascript" src="../javascript/ASCIIsvg.js"></script>
<script type="text/javascript">var AMTcgiloc = "<?php echo $mathimgurl;?>";</script>
<script type="text/javascript">var AScgiloc = "<?php echo $svgimgurl;?>";</script>
<?php
if ($mcsession['mathdisp']==2) {
	echo '<script type="text/javascript">AMnoMathML = true;</script>';
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

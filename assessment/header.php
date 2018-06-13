<?php if (!isset($imasroot)) {exit;} ?>
<!DOCTYPE html>
<?php if (isset($CFG['locale'])) {
	echo '<html lang="'.$CFG['locale'].'">';
} else {
	echo '<html lang="en">';
}
?>
<head>

<title><?php echo $installname;?> Assessment</title>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php

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
function recordanswer(val, qn, part) {
	if (part!=null) {
		qn = (qn+1)*1000 + part;
	}
	document.getElementById("qn"+qn).value = val;
}
var imasprevans = [];
function getlastanswer(qn, part) {
	if (part != null) {
		return imasprevans[qn+'-'+part];
	} else {
		return imasprevans[qn];
	}
}
//add require_once style script loader
initstack = new Array();
window.onload = init;
var imasroot = '<?php echo $imasroot; ?>'; var cid = <?php echo (isset($cid) && is_numeric($cid))?$cid:0; ?>;
</script>
<link rel="stylesheet" href="<?php echo $imasroot . "/assessment/mathtest.css?ver=060918";?>" type="text/css"/>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js" type="text/javascript"></script>
<script type="text/javascript">
  if (!window.jQuery) {  document.write('<script src="<?php echo $imasroot;?>/javascript/jquery.min.js"><\/script>');}
</script>
<?php
if (isset($CFG['locale'])) {
	$lang = substr($CFG['locale'],0,2);
	if (file_exists(rtrim(dirname(__FILE__), '/\\').'/../i18n/locale/'.$lang.'/messages.js')) {
		echo '<script type="text/javascript" src="'.$imasroot.'/i18n/locale/'.$lang.'/messages.js"></script>';
	}
}
if (isset($sessiondata['coursetheme'])) {
	if (isset($flexwidth) || isset($usefullwidth)) {
		$coursetheme = str_replace(array('_fw1920','_fw1000','_fw'),'',$sessiondata['coursetheme']);
	} else {
		$coursetheme = $sessiondata['coursetheme'];
		$isfw = false;
		if (strpos($coursetheme,'_fw1920')!==false) {
			$isfw = 1920;
			$coursetheme = str_replace('_fw1920','',$coursetheme);
		} else if (strpos($coursetheme,'_fw')!==false) {
			$isfw = 1000;
			$coursetheme = str_replace(array('_fw1000','_fw'),'',$coursetheme);
		}
	}
	echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"$imasroot/themes/$coursetheme?v=042217\"/>\n";
}
echo '<link rel="stylesheet" href="'.$imasroot.'/handheld.css?v=101817" media="handheld,only screen and (max-width:480px)"/>';
if ($isdiag) {
	echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"$imasroot/diag/print.css\" media=\"print\"/>\n";
} else {
	echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"$imasroot/assessment/print.css\" media=\"print\"/>\n";
}
if (isset($CFG['GEN']['favicon'])) {
	echo '<link rel="shortcut icon" href="'.$CFG['GEN']['favicon'].'" />';
} else {
	echo '<link rel="shortcut icon" href="/favicon.ico" />';
}
if (!empty($CFG['use_csrfp']) && class_exists('csrfProtector')) {
	echo csrfProtector::output_header_code();
}

echo '<script src="' . $imasroot . '/javascript/assessment_min.js?v=060618" type="text/javascript"></script>';

/*

/assessment_min.js bundles: general.js, mathjs.js, AMhelpers.js, confirmsubmit.js, drawing.js, and eqntips.js

echo '<script src="' . $imasroot . '/javascript/general.js?v=060618" type="text/javascript"></script>';
echo '<script src="' . $imasroot . '/javascript/mathjs.js?v=050918" type="text/javascript"></script>';
echo '<script src="' . $imasroot . '/javascript/AMhelpers.js?v=041818" type="text/javascript"></script>';
echo '<script src="' . $imasroot . '/javascript/confirmsubmit.js?v=031018" type="text/javascript"></script>';
echo '<script src="' . $imasroot . '/javascript/drawing.js?v=030118" type="text/javascript"></script>';
echo '<script src="' . $imasroot . '/javascript/eqntips.js?v=082616" type="text/javascript"></script>';

*/

if (isset($sessiondata['ltiitemtype']) && ($sessiondata['mathdisp']==1 || $sessiondata['mathdisp']==3)) {
	echo '<script type="text/x-mathjax-config">
		MathJax.Hub.Queue(function () {
			sendLTIresizemsg();
		});
		MathJax.Hub.Register.MessageHook("End Process", sendLTIresizemsg);
	     </script>';
}
//$sessiondata['mathdisp'] = 3;
if (!isset($sessiondata['mathdisp'])) {
	echo '<script type="text/javascript">var AMnoMathML = true;var ASnoSVG = true;var AMisGecko = 0;var AMnoTeX = false; var mathRenderer = "none"; function rendermathnode(el) {AMprocessNode(el);};</script>';
	//echo '<script type="text/javascript" src="'.$imasroot.'/mathjax/MathJax.js?config=AM_CHTML&rev=2.6.1"></script>';
	echo '<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.2/MathJax.js?config=AM_CHTML-full"></script>';
	echo "<script src=\"$imasroot/javascript/mathgraphcheck.js?v=021215\" type=\"text/javascript\"></script>\n";
} else if ($sessiondata['mathdisp']==1 || $sessiondata['mathdisp']==3) {
	echo '<script type="text/javascript">var AMTcgiloc = "'.$mathimgurl.'";</script>';
	echo "<script src=\"$imasroot/javascript/ASCIIMathTeXImg_min.js?ver=042318\" type=\"text/javascript\"></script>\n";
	echo '<script type="text/x-mathjax-config">
		if (MathJax.Hub.Browser.isChrome || MathJax.Hub.Browser.isSafari) {
			MathJax.Hub.Config({"HTML-CSS": {preferredFont: "STIX", imageFont:null}, "messageStyle": "none"});
		} else {
			MathJax.Hub.Config({"HTML-CSS": {preferredFont: "STIX", webFont: "STIX-Web", imageFont:null}, "messageStyle": "none"});
		}
		MathJax.Ajax.config.path["Local"] = "'.$imasroot.'/mathjax/extensions";
		MathJax.Hub.config.extensions.push("[Local]/InputToDataAttrCDN.js");
		</script>';
		//webFont: "STIX-Web",
	echo '<script type="text/javascript" async src="https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.2/MathJax.js?config=AM_CHTML-full"></script>';
	//echo '<script>window.MathJax || document.write(\'<script src="'.$imasroot.'/mathjax/MathJax.js?config=AM_CHTML&rev=2.6.1"><\/script>\')</script>';
	//echo '<script type="text/javascript" src="'.$imasroot.'/mathjax/MathJax.js?config=AM_CHTML&rev=2.6.1"></script>';
	echo '<script type="text/javascript">noMathRender = false; var usingASCIIMath = true; var AMnoMathML = false; var MathJaxCompatible = true; var mathRenderer = "MathJax"; function rendermathnode(node) { MathJax.Hub.Queue(["Typeset", MathJax.Hub, node]); } </script>';
	echo '<style type="text/css">span.MathJax { font-size: 105%;}</style>';
} else if ($sessiondata['mathdisp']==6) {
	//Katex experimental
	echo '<script type="text/javascript">var AMTcgiloc = "'.$mathimgurl.'";</script>';
	echo "<script src=\"$imasroot/javascript/ASCIIMathTeXImg_min.js?ver=042318\" type=\"text/javascript\"></script>\n";

	echo '<script type="text/x-mathjax-config">
		if (MathJax.Hub.Browser.isChrome || MathJax.Hub.Browser.isSafari) {
			MathJax.Hub.Config({"HTML-CSS": {preferredFont: "STIX", imageFont:null}, "messageStyle": "none", skipStartupTypeset: true});
		} else {
			MathJax.Hub.Config({"HTML-CSS": {preferredFont: "STIX", webFont: "STIX-Web", imageFont:null}, "messageStyle": "none", skipStartupTypeset: true});
		}
		MathJax.Ajax.config.path["Local"] = "'.$imasroot.'/mathjax/extensions";
		MathJax.Hub.config.extensions.push("[Local]/InputToDataAttrCDN.js");
		MathJax.Hub.Register.StartupHook("Begin Config", setupKatexAutoRenderWhenReady);
		</script>
		<script type="text/javascript">
		function setupKatexAutoRenderWhenReady() {
			if (typeof setupKatexAutoRender == "function") {setupKatexAutoRender();} else { setTimeout(setupKatexAutoRenderWhenReady,50);}
		}
		</script>';
	//echo '<script>window.MathJax || document.write(\'<script src="'.$imasroot.'/mathjax/MathJax.js?config=AM_CHTML&rev=2.6.1"><\/script>\')</script>';
	//echo '<script type="text/javascript" src="'.$imasroot.'/mathjax/MathJax.js?config=AM_CHTML&rev=2.6.1"></script>';
	//echo '<script src="'.$imasroot.'/katex/katex.min.js"></script>';
	echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/KaTeX/0.7.1/katex.min.js" integrity="sha384-/y1Nn9+QQAipbNQWU65krzJralCnuOasHncUFXGkdwntGeSvQicrYkiUBwsgUqc1" crossorigin="anonymous"></script>';
	//echo '<link rel="stylesheet" href="'.$imasroot.'/katex/katex.min.css"/>';
	echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/KaTeX/0.7.1/katex.min.css" integrity="sha384-wITovz90syo1dJWVh32uuETPVEtGigN07tkttEqPv+uR2SE/mbQcG7ATL28aI9H0" crossorigin="anonymous">';
	echo '<script type="text/javascript" src="'.$imasroot.'/katex/auto-render.js?v=083116"></script>';
	echo '<script type="text/javascript" async src="https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.2/MathJax.js?config=AM_CHTML-full"></script>';
	echo '<script type="text/javascript">noMathRender = false; var usingASCIIMath = true; var AMnoMathML = true; var MathJaxCompatible = true; var mathRenderer = "Katex";</script>';
	//echo '<style type="text/css">span.AM { font-size: 105%;}</style>';
} else if ($sessiondata['mathdisp']==2) {
	echo '<script type="text/javascript">var AMTcgiloc = "'.$mathimgurl.'";</script>';
	echo "<script src=\"$imasroot/javascript/ASCIIMathTeXImg_min.js?v=042318\" type=\"text/javascript\"></script>\n";
	echo "<script type=\"text/javascript\">var usingASCIIMath = false;var MathJaxCompatible = false;var mathRenderer = \"img\";function rendermathnode(el) {AMprocessNode(el);}</script>";
} else if ($sessiondata['mathdisp']==0) {
	echo '<script type="text/javascript">var noMathRender = true; var usingASCIIMath = false; var MathJaxCompatible = false; var mathRenderer = "none";function rendermathnode(el) {}</script>';
}
if ($sessiondata['graphdisp']==1) {
	echo "<script src=\"$imasroot/javascript/ASCIIsvg_min.js?v=112817\" type=\"text/javascript\"></script>\n";
	echo "<script type=\"text/javascript\">var usingASCIISvg = true;</script>";
} else {
	echo "<script type=\"text/javascript\">var usingASCIISvg = false;</script>";
}
?>
<!--[if lte IE 6]>
<style type="text/css">
div { zoom: 1; }
.clear { line-height: 0;}
#mqarea { height: 2em;}
#GB_overlay, #GB_window {
 position: absolute;
 top: expression(0+((e=document.documentElement.scrollTop)?e:document.body.scrollTop)+'px');
 left: expression(0+((e=document.documentElement.scrollLeft)?e:document.body.scrollLeft)+'px');}
}
</style>
<![endif]-->
<!--[if lt IE 9]>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/swfobject/2.2/swfobject.js"></script>
<script type="text/javascript" src="<?php echo $imasroot;?>/javascript/excanvas_min.js?v=120811"></script>
<![endif]-->



<?php

echo "<script type=\"text/javascript\">imasroot = '$imasroot';</script>";
if (isset($useeditor) && $sessiondata['useed']==1) {
	echo '<script type="text/javascript" src="'.$imasroot.'/tinymce4/tinymce_bundled.js?v=013118"></script>';
	//echo '<script type="text/javascript" src="'.$imasroot.'/tinymce4/tinymce.min.js?v=082716"></script>';
	echo "\n";
	echo '<script type="text/javascript">';
	echo 'var usingTinymceEditor = true;';
	echo 'var coursetheme = "'.$coursetheme.'";';
	echo 'var tinymceUseSnippets = '.($myrights>10?1:0).';';
	if (!isset($CFG['GEN']['noFileBrowser'])) {
		echo 'var filePickerCallBackFunc = filePickerCallBack;';
	} else {
		echo 'var filePickerCallBackFunc = null;';
	}
	echo 'initeditor("textareas","mceEditor");';
	echo '</script>';
} else {
	echo '<script type="text/javascript">var usingTinymceEditor = false;</script>';
}
if ((isset($useeditor) && $sessiondata['useed']==1) || isset($loadiconfont)) {
	echo '<link rel="stylesheet" href="'.$imasroot . '/iconfonts/imathasfont.css?v=013118" type="text/css" />';
	echo '<!--[if lte IE 7]><link rel="stylesheet" href="'.$imasroot . '/iconfonts/imathasfontie7.css?v=013118" type="text/css" /><![endif]-->';
}
if ($useeqnhelper==1 || $useeqnhelper==2) {
	echo '<script type="text/javascript">var eetype='.$useeqnhelper.'</script>';
	echo "<script type=\"text/javascript\" src=\"$imasroot/javascript/eqnhelper.js?v=062216\"></script>";
	echo '<style type="text/css"> div.question input.btn { margin-left: 10px; } </style>';

} else if ($useeqnhelper==3 || $useeqnhelper==4) {
	echo "<link rel=\"stylesheet\" href=\"$imasroot/assessment/mathquill.css?v=062416\" type=\"text/css\" />";
	if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')!==false) {
		echo '<!--[if lte IE 7]><style style="text/css">
			.mathquill-editable.empty { width: 0.5em; }
			.mathquill-rendered-math .numerator.empty, .mathquill-rendered-math .empty { padding: 0 0.25e  m;}
			.mathquill-rendered-math sup { line-height: .8em; }
			.mathquill-rendered-math .numerator {float: left; padding: 0;}
			.mathquill-rendered-math .denominator { clear: both;width: auto;float: left;}
			</style><![endif]-->';
	}
	//This bundles mathquill.js, mathquilled.js, and AMtoMQ.js
	echo "<script type=\"text/javascript\" src=\"$imasroot/javascript/MQbundle_min.js?v=071916\"></script>";
	echo '<style type="text/css"> div.question input.btn { margin-left: 10px; } </style>';
}

$curdir = rtrim(dirname(__FILE__), '/\\');
if (isset($placeinhead)) {
	echo $placeinhead;
}
if (isset($CFG['GEN']['headerscriptinclude'])) {
	require("$curdir/../{$CFG['GEN']['headerscriptinclude']}");
}
if (isset($CFG['GEN']['translatewidgetID'])) {
	echo '<meta name="google-translate-customization" content="'.$CFG['GEN']['translatewidgetID'].'"></meta>';
}
if (isset($sessiondata['ltiitemtype'])) {
	echo '<script type="text/javascript">
	if (mathRenderer == "Katex") {
		window.katexDoneCallback = sendLTIresizemsg;
	} else {
		$(sendLTIresizemsg);
	}
	</script>';
}
echo '</head>';
if ($isfw!==false) {
	echo "<body class=\"fw$isfw\">\n";
} else {
	echo "<body>\n";
}

$insertinheaderwrapper = ' ';
echo '<div class=mainbody>';
if (isset($insertinheaderwrapper)) {
	//echo '<div class="headerwrapper">'.$insertinheaderwrapper.'</div>';
}
if (!isset($flexwidth)) {
	echo '<div class="headerwrapper">';
}

if (isset($CFG['GEN']['headerinclude']) && !isset($flexwidth)) {
	require("$curdir/../{$CFG['GEN']['headerinclude']}");
}

if (isset($cid) && !isset($flexwidth) && !$isdiag && (!isset($sessiondata['intreereader']) || $sessiondata['intreereader']==false)) {
	echo '<div id="navlistcont" role="navigation" aria-label="'._('Course Navigation').'">';
	echo '<ul id="navlist">';

	echo "<li><a href=\"$imasroot/course/course.php?cid=$cid\">Course</a></li> ";
	if ($coursemsgset<4) { //messages
		echo "<li><a href=\"$imasroot/msgs/msglist.php?cid=$cid\">Messages</a></li> ";
	}

	if (($coursetoolset&2)==0) { //forums
		echo "<li><a href=\"$imasroot/forums/forums.php?cid=$cid\">Forums</a></li>";
	}

	if (isset($teacherid)) { //Roster
		echo "<li><a href=\"$imasroot/course/listusers.php?cid=$cid\">Roster</a></li>\n";
	}

	if (($coursetoolset&1)==0) { //Calendar
		echo "<li><a href=\"$imasroot/course/showcalendar.php?cid=$cid\">Calendar</a></li>\n";
	}

	echo "<li><a href=\"$imasroot/course/gradebook.php?cid=$cid\">Gradebook</a></li>"; //Gradebook

	if (!isset($haslogout)) { //Log out
		echo "<li><a href=\"$imasroot/actions.php?action=logout\">Log Out</a></li>";
	}
	echo '</ul>';

	echo '<div class="clear"></div>';
	echo '</div>';
	$didnavlist = true;
}

if (!isset($flexwidth)) {
	echo '</div>';
}
echo '<div class="midwrapper" role="main">';


?>

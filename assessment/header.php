<?php if (!isset($imasroot)) {exit;} ?>
<!DOCTYPE html>
<?php if (isset($CFG['locale'])) {
	echo '<html lang="'.$CFG['locale'].'">';
} else {
	echo '<html lang="en">';
}
if (!isset($myrights)) { 
    $myrights = 0; // avoid errors in headercontent if not defined
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
var isImathasAssessment = true;
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
<link rel="stylesheet" href="<?php echo $staticroot . "/assessment/mathtest.css?ver=040520";?>" type="text/css"/>
<?php

if (!empty($CFG['GEN']['uselocaljs'])) {
	echo '<script src="'.$staticroot.'/javascript/jquery.min.js"></script>';
} else {
	echo '<script src="https://code.jquery.com/jquery-1.12.4.min.js" integrity="sha256-ZosEbRLbNQzLpnKIkEdrPv7lOy9C27hHQ+Xp8a4MxAQ=" crossorigin="anonymous"></script>';	echo '<script>window.jQuery || document.write(\'<script src="'.$staticroot.'/javascript/jquery.min.js"><\/script>\')</script>';
}
echo "<script type=\"text/javascript\">imasroot = '$imasroot';staticroot='$staticroot';</script>";

if (isset($CFG['locale'])) {
	$lang = substr($CFG['locale'],0,2);
	if (file_exists(rtrim(dirname(__FILE__), '/\\').'/../i18n/locale/'.$lang.'/messages.js')) {
		echo '<script type="text/javascript" src="'.$staticroot.'/i18n/locale/'.$lang.'/messages.js"></script>';
	}
}

if (isset($coursetheme)) {
	$_SESSION['coursetheme'] = $coursetheme;
}
if (isset($_SESSION['coursetheme'])) {
	if (isset($flexwidth) || isset($usefullwidth)) {
		$coursetheme = str_replace(array('_fw1920','_fw1000','_fw'),'',$_SESSION['coursetheme']);
	} else {
		$coursetheme = $_SESSION['coursetheme'];
		$isfw = false;
		if (strpos($coursetheme,'_fw1920')!==false) {
			$isfw = 1920;
			$coursetheme = str_replace('_fw1920','',$coursetheme);
		} else if (strpos($coursetheme,'_fw')!==false) {
			$isfw = 1000;
			$coursetheme = str_replace(array('_fw1000','_fw'),'',$coursetheme);
		}
	}
	echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"$staticroot/themes/$coursetheme?v=042217\"/>\n";
}
echo '<link rel="stylesheet" href="'.$staticroot.'/handheld.css?v=101817" media="handheld,only screen and (max-width:480px)"/>';
if (!empty($isdiag)) {
	echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"$staticroot/diag/print.css\" media=\"print\"/>\n";
} else {
	echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"$staticroot/assessment/print.css\" media=\"print\"/>\n";
}
if (isset($CFG['GEN']['favicon'])) {
	echo '<link rel="shortcut icon" href="'.$CFG['GEN']['favicon'].'" />';
} else {
	echo '<link rel="shortcut icon" href="/favicon.ico" />';
}
if (!empty($CFG['use_csrfp']) && class_exists('csrfProtector')) {
	echo csrfProtector::output_header_code();
}

echo '<script src="' . $staticroot . '/javascript/assessment_min.js?v=112420" type="text/javascript"></script>';


//assessment_min.js bundles: general.js, mathjs.js, AMhelpers.js, confirmsubmit.js, drawing.js, and eqntips.js
/*
echo '<script src="' . $imasroot . '/javascript/general.js?v=042220" type="text/javascript"></script>';
echo '<script src="' . $imasroot . '/javascript/mathjs.js?v=033120" type="text/javascript"></script>';
echo '<script src="' . $imasroot . '/javascript/AMhelpers.js?v=060920" type="text/javascript"></script>';
echo '<script src="' . $imasroot . '/javascript/confirmsubmit.js?v=031018" type="text/javascript"></script>';
echo '<script src="' . $imasroot . '/javascript/drawing.js?v=042920" type="text/javascript"></script>';
echo '<script src="' . $imasroot . '/javascript/eqntips.js?v=082616" type="text/javascript"></script>';

*/

if (isset($_SESSION['ltiitemtype']) && ($_SESSION['mathdisp']==1 || $_SESSION['mathdisp']==3)) {
	echo '<script type="text/x-mathjax-config">
		MathJax.Hub.Queue(function () {
			sendLTIresizemsg();
		});
		MathJax.Hub.Register.MessageHook("End Process", sendLTIresizemsg);
	     </script>';
}
//$_SESSION['mathdisp'] = 3;
if (!isset($_SESSION['mathdisp'])) {
	echo '<script type="text/javascript">var AMnoMathML = true;var ASnoSVG = true;var AMisGecko = 0;var AMnoTeX = false; var mathRenderer = "none"; function rendermathnode(el) {AMprocessNode(el);};</script>';
	//echo '<script type="text/javascript" src="'.$imasroot.'/mathjax/MathJax.js?config=AM_CHTML&rev=2.6.1"></script>';
	if (!empty($CFG['GEN']['uselocaljs'])) {
		echo '<script type="text/javascript" async src="'.$staticroot.'/mathjax/MathJax.js?config=AM_CHTML-full"></script>';
	} else {
		echo '<script type="text/javascript" async src="https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.9/MathJax.js?config=AM_CHTML-full"></script>';
	}
	echo "<script src=\"$staticroot/javascript/mathgraphcheck.js?v=021215\" type=\"text/javascript\"></script>\n";
} else if ($_SESSION['mathdisp']==1 || $_SESSION['mathdisp']==3 || $_SESSION['mathdisp']==7 || $_SESSION['mathdisp']==8) {
	echo '<script type="text/javascript">var AMTcgiloc = "'.$mathimgurl.'";</script>';
	echo "<script src=\"$staticroot/javascript/ASCIIMathTeXImg_min.js?ver=100418\" type=\"text/javascript\"></script>\n";
	echo '<script type="text/x-mathjax-config">
		MathJax.Hub.Config({"messageStyle": "none", asciimath2jax: {ignoreClass:"skipmathrender"}});
		MathJax.Ajax.config.path["Local"] = "'.$staticroot.'/javascript/mathjax";
		MathJax.Hub.config.extensions.push("[Local]/InputToDataAttrCDN.js");
		</script>';
	if (!empty($CFG['GEN']['uselocaljs'])) {
		echo '<script type="text/javascript" async src="'.$staticroot.'/mathjax/MathJax.js?config=AM_CHTML-full"></script>';
	} else {
		echo '<script type="text/javascript" async src="https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.9/MathJax.js?config=AM_CHTML-full"></script>';
	}
	echo '<script type="text/javascript">noMathRender = false; var usingASCIIMath = true; var AMnoMathML = false; var MathJaxCompatible = true; var mathRenderer = "MathJax"; function rendermathnode(node) { MathJax.Hub.Queue(["Typeset", MathJax.Hub, node]); } </script>';
	echo '<style type="text/css">span.MathJax { font-size: 105%;}</style>';
} else if ($_SESSION['mathdisp']==6) {
	//Katex experimental
	echo '<script type="text/javascript">var AMTcgiloc = "'.$mathimgurl.'";</script>';
	echo "<script src=\"$staticroot/javascript/ASCIIMathTeXImg_min.js?ver=100418\" type=\"text/javascript\"></script>\n";
	// removed MathJax fallback since Katex covers pretty much everything now, and MathJax load was slowing display.
	/*echo '<script type="text/x-mathjax-config">
		MathJax.Hub.Config({"messageStyle": "none", asciimath2jax: {ignoreClass:"skipmathrender"}, skipStartupTypeset: true});
		MathJax.Ajax.config.path["Local"] = "'.$imasroot.'/javascript/mathjax";
		MathJax.Hub.config.extensions.push("[Local]/InputToDataAttrCDN.js");
		MathJax.Hub.Register.StartupHook("Begin Config", setupKatexAutoRenderWhenReady);
		</script>
		<script type="text/javascript">
		function setupKatexAutoRenderWhenReady() {
			if (typeof setupKatexAutoRender == "function") {setupKatexAutoRender();} else { setTimeout(setupKatexAutoRenderWhenReady,50);}
		}
		</script>';*/
	if (!empty($CFG['GEN']['uselocaljs'])) {
		echo '<script src="'.$staticroot.'/katex/katex.min.js"></script>';
		echo '<link rel="stylesheet" href="'.$staticroot.'/katex/katex.min.css" />';
		//echo '<script type="text/javascript" async src="'.$imasroot.'/mathjax/MathJax.js?config=AM_CHTML-full"></script>';
	} else {
		echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.11.1/dist/katex.min.css" integrity="sha384-zB1R0rpPzHqg7Kpt0Aljp8JPLqbXI3bhnPWROx27a9N0Ll6ZP/+DiW/UqRcLbRjq" crossorigin="anonymous">';
		echo '<script src="https://cdn.jsdelivr.net/npm/katex@0.11.1/dist/katex.min.js" integrity="sha384-y23I5Q6l+B6vatafAwxRu/0oK/79VlbSz7Q9aiSZUvyWYIYsd+qj+o24G5ZU2zJz" crossorigin="anonymous"></script>';
		//echo '<script type="text/javascript" async src="https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.9/MathJax.js?config=AM_CHTML-full"></script>';
	}
	echo '<script type="text/javascript" src="'.$staticroot.'/katex/auto-render.js?v=120118"></script>';
	echo '<script type="text/javascript">setupKatexAutoRender();</script>';
	// re-route MathJax render requests to katex. Allows jsxgraph to work.
	echo '<script type="text/javascript">
	  var MathJax = {Hub: {Queue: function(arr) { rendermathnode(arr[2]);}}};
		</script>';
	echo '<script type="text/javascript">noMathRender = false; var usingASCIIMath = true; var AMnoMathML = true; var MathJaxCompatible = true; var mathRenderer = "Katex";</script>';
	//echo '<style type="text/css">span.AM { font-size: 105%;}</style>';
} else if ($_SESSION['mathdisp']==2) {
	echo '<script type="text/javascript">var AMTcgiloc = "'.$mathimgurl.'";</script>';
	echo "<script src=\"$staticroot/javascript/ASCIIMathTeXImg_min.js?v=042318\" type=\"text/javascript\"></script>\n";
	echo "<script type=\"text/javascript\">var usingASCIIMath = false;var MathJaxCompatible = false;var mathRenderer = \"img\";function rendermathnode(el) {AMprocessNode(el);}</script>";
} else if ($_SESSION['mathdisp']==0) {
	echo '<script type="text/javascript">var noMathRender = true; var usingASCIIMath = false; var MathJaxCompatible = false; var mathRenderer = "none";function rendermathnode(el) {}</script>';
}
if (isset($_SESSION['graphdisp']) && $_SESSION['graphdisp']==0) {
    echo "<script type=\"text/javascript\">var usingASCIISvg = false;</script>";
} else {
	echo "<script src=\"$staticroot/javascript/ASCIIsvg_min.js?v=052520\" type=\"text/javascript\"></script>\n";
	echo "<script type=\"text/javascript\">var usingASCIISvg = true;</script>";
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
<script type="text/javascript" src="<?php echo $staticroot;?>/javascript/excanvas_min.js?v=120811"></script>
<![endif]-->



<?php

if (isset($useeditor) && !empty($_SESSION['useed'])) {
	echo '<script type="text/javascript" src="'.$staticroot.'/tinymce4/tinymce_bundled.min.js?v=051919"></script>';
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
	echo 'initeditor("textareas","mceEditor",1);';
	echo '</script>';
} else {
	echo '<script type="text/javascript">var usingTinymceEditor = false;</script>';
}
if ((isset($useeditor) && $_SESSION['useed']==1) || isset($loadiconfont)) {
	echo '<link rel="stylesheet" href="'.$staticroot . '/iconfonts/imathasfont.css?v=013118" type="text/css" />';
	echo '<!--[if lte IE 7]><link rel="stylesheet" href="'.$staticroot . '/iconfonts/imathasfontie7.css?v=013118" type="text/css" /><![endif]-->';
}
if (!isset($useeqnhelper)) { $useeqnhelper = 0; }

if ($useeqnhelper==1 || $useeqnhelper==2) {
	echo '<script type="text/javascript">var eetype='.$useeqnhelper.'</script>';
	echo "<script type=\"text/javascript\" src=\"$staticroot/javascript/eqnhelper.js?v=062216\"></script>";
	echo '<style type="text/css"> div.question input.btn { margin-left: 10px; } </style>';

} else if ($useeqnhelper==3 || $useeqnhelper==4) {
	echo "<link rel=\"stylesheet\" href=\"$staticroot/assessment/mathquill.css?v=062416\" type=\"text/css\" />";
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
	echo "<script type=\"text/javascript\" src=\"$staticroot/javascript/MQbundle_min.js?v=021920\"></script>";
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
if (isset($_SESSION['ltiitemtype'])) {
	echo '<script type="text/javascript">
	if (mathRenderer == "Katex") {
		window.katexDoneCallback = sendLTIresizemsg;
	} else {
		$(sendLTIresizemsg);
	}
	</script>';
	}
echo '</head>';
if (!empty($isfw)) {
	echo "<body class=\"fw$isfw\">\n";
} else {
	echo "<body>\n";
}

$insertinheaderwrapper = ' ';
echo '<div class=mainbody>';
if (isset($insertinheaderwrapper)) {
	//echo '<div class="headerwrapper">'.$insertinheaderwrapper.'</div>';
}
if (!isset($flexwidth) && !isset($hideAllHeaderNav)) {
	echo '<div class="headerwrapper">';
}

if (isset($CFG['GEN']['headerinclude']) && !isset($flexwidth) && !isset($hideAllHeaderNav)) {
	require("$curdir/../{$CFG['GEN']['headerinclude']}");
}

if (isset($cid) && !isset($flexwidth) && !isset($hideAllHeaderNav) && !$isdiag && (!isset($_SESSION['intreereader']) || $_SESSION['intreereader']==false)) {
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

if (!isset($flexwidth) && !isset($hideAllHeaderNav)) {
	echo '</div>';
}
echo '<div class="midwrapper" role="main">';


?>

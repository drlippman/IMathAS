<!DOCTYPE html>
<html>
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
<link rel="stylesheet" href="<?php echo $imasroot . "/assessment/mathtest.css?ver=012614";?>" type="text/css"/>
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
echo "<script type=\"text/javascript\" src=\"$imasroot/javascript/general.js?ver=062416\"></script>\n";
if (isset($sessiondata['coursetheme'])) {
	if (isset($flexwidth) || isset($usefullwidth)) {
		$coursetheme = str_replace('_fw','',$sessiondata['coursetheme']);
	} else {
		$coursetheme = $sessiondata['coursetheme'];
	}
	echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"$imasroot/themes/$coursetheme\"/>\n";
}
echo '<link rel="stylesheet" href="'.$imasroot.'/handheld.css?v=070816" media="handheld,only screen and (max-width:480px)"/>';
if ($isdiag) {
	echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"$imasroot/diag/print.css\" media=\"print\"/>\n";
} else {
	echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"$imasroot/assessment/print.css\" media=\"print\"/>\n";
}
//$sessiondata['mathdisp'] = 3;
if (!isset($sessiondata['mathdisp'])) {
	echo '<script type="text/javascript">var AMnoMathML = true;var ASnoSVG = true;var AMisGecko = 0;var AMnoTeX = false; var mathRenderer = "none"; function rendermathnode(el) {AMprocessNode(el);};</script>';
	echo '<script type="text/javascript" src="'.$imasroot.'/mathjax/MathJax.js?config=AM_CHTML&rev=2.6.1"></script>';
	echo "<script src=\"$imasroot/javascript/mathgraphcheck.js?v=021215\" type=\"text/javascript\"></script>\n";
} else if ($sessiondata['mathdisp']==1 || $sessiondata['mathdisp']==3) {
	echo '<script type="text/javascript">var AMTcgiloc = "'.$mathimgurl.'";</script>';
	echo "<script src=\"$imasroot/javascript/ASCIIMathTeXImg_min.js?ver=092314\" type=\"text/javascript\"></script>\n";
	echo '<script type="text/x-mathjax-config">
		if (MathJax.Hub.Browser.isChrome || MathJax.Hub.Browser.isSafari) {
			MathJax.Hub.Config({"HTML-CSS": {preferredFont: "STIX", imageFont:null}});
		} else {
			MathJax.Hub.Config({"HTML-CSS": {preferredFont: "STIX", webFont: "STIX-Web", imageFont:null}});
		}
		MathJax.Hub.config.extensions.push("InputToDataAttr.js");
		</script>';
		//webFont: "STIX-Web",
	//echo '<script type="text/javascript" src="https://c328740.ssl.cf1.rackcdn.com/mathjax/latest/MathJax.js?config=AM_HTMLorMML"></script>';
	//echo '<script>window.MathJax || document.write(\'<script type="text/x-mathjax-config">MathJax.Hub.Config({"HTML-CSS":{imageFont:null}});<\/script><script src="'.$imasroot.'/mathjax/MathJax.js?config=AM_HTMLorMML"><\/script>\')</script>';
	echo '<script type="text/javascript" src="'.$imasroot.'/mathjax/MathJax.js?config=AM_CHTML&rev=2.6.1"></script>';
	echo '<script type="text/javascript">noMathRender = false; var usingASCIIMath = true; var AMnoMathML = false; var MathJaxCompatible = true; var mathRenderer = "MathJax"; function rendermathnode(node) { MathJax.Hub.Queue(["Typeset", MathJax.Hub, node]); } </script>';
	echo '<style type="text/css">span.MathJax { font-size: 105%;}</style>';
} else if ($sessiondata['mathdisp']==6) {
	//Katex experimental
	echo '<script type="text/javascript">var AMTcgiloc = "'.$mathimgurl.'";</script>';
	echo "<script src=\"$imasroot/javascript/ASCIIMathTeXImg_min.js?ver=061016\" type=\"text/javascript\"></script>\n";

	echo '<script type="text/x-mathjax-config">
		if (MathJax.Hub.Browser.isChrome || MathJax.Hub.Browser.isSafari) {
			MathJax.Hub.Config({"HTML-CSS": {preferredFont: "STIX", imageFont:null}, skipStartupTypeset: true, messageStyle: "none"});
		} else {
			MathJax.Hub.Config({"HTML-CSS": {preferredFont: "STIX", webFont: "STIX-Web", imageFont:null}, skipStartupTypeset: true,messageStyle: "none"});
		}
		MathJax.Hub.config.extensions.push("InputToDataAttr.js");
		</script>';
		// webFont: "STIX-Web",
	//echo '<script type="text/javascript" src="https://c328740.ssl.cf1.rackcdn.com/mathjax/latest/MathJax.js?config=AM_HTMLorMML"></script>';
	//echo '<script>window.MathJax || document.write(\'<script type="text/x-mathjax-config">MathJax.Hub.Config({"HTML-CSS":{imageFont:null}});<\/script><script src="'.$imasroot.'/mathjax/MathJax.js?config=AM_HTMLorMML"><\/script>\')</script>';
	echo '<script type="text/javascript" src="'.$imasroot.'/mathjax/MathJax.js?config=AM_CHTML&rev=2.6.1"></script>';
	echo '<script src="'.$imasroot.'/katex/katex.min.js"></script>';
	echo '<link rel="stylesheet" href="'.$imasroot.'/katex/katex.min.css"/>';
	echo '<script type="text/javascript" src="'.$imasroot.'/katex/auto-render.js?v=070816"></script>';
	echo '<script type="text/javascript">noMathRender = false; var usingASCIIMath = true; var AMnoMathML = true; var MathJaxCompatible = true; var mathRenderer = "Katex"; function rendermathnode(node) {renderMathInElement(node);}</script>';
	//echo '<style type="text/css">span.AM { font-size: 105%;}</style>';
} else if ($sessiondata['mathdisp']==2) {
	echo '<script type="text/javascript">var AMTcgiloc = "'.$mathimgurl.'";</script>';
	echo "<script src=\"$imasroot/javascript/ASCIIMathTeXImg_min.js?v=092314\" type=\"text/javascript\"></script>\n";
	echo "<script type=\"text/javascript\">var usingASCIIMath = false;var MathJaxCompatible = false;var mathRenderer = \"img\";function rendermathnode(el) {AMprocessNode(el);}</script>";
} else if ($sessiondata['mathdisp']==0) {
	echo '<script type="text/javascript">var noMathRender = true; var usingASCIIMath = false; var MathJaxCompatible = false; var mathRenderer = "none";function rendermathnode(el) {}</script>';
}
echo "<script src=\"$imasroot/javascript/mathjs.js?v=063016\" type=\"text/javascript\"></script>\n";
if ($sessiondata['graphdisp']==1) {
	echo "<script src=\"$imasroot/javascript/ASCIIsvg_min.js?v=071516\" type=\"text/javascript\"></script>\n";
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
<script src="<?php echo $imasroot . "/javascript/AMhelpers_min.js?v=072216";?>" type="text/javascript"></script>
<script src="<?php echo $imasroot . "/javascript/confirmsubmit.js?v=012115";?>" type="text/javascript"></script>
<!--[if lt IE 9]>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/swfobject/2.2/swfobject.js"></script>
<script type="text/javascript" src="<?php echo $imasroot;?>/javascript/excanvas_min.js?v=120811"></script><![endif]-->
<script type="text/javascript" src="<?php echo $imasroot;?>/javascript/drawing_min.js?v=080416"></script>
<?php
echo "<script type=\"text/javascript\">imasroot = '$imasroot';</script>";
if (isset($useeditor) && $sessiondata['useed']==1) {
	echo '<script type="text/javascript" src="'.$imasroot.'/tinymce4/tinymce.min.js?v=111612"></script>';
	echo "\n";
	echo '<script type="text/javascript">';
	echo 'var usingTinymceEditor = true;';
	if (isset($sessiondata['coursetheme'])) {
		echo 'var coursetheme = "'.$sessiondata['coursetheme'].'";';
	} else {
		echo 'var coursetheme = "'.$coursetheme.'";';
	}
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
	echo "<script type=\"text/javascript\" src=\"$imasroot/javascript/mathquill_min.js?v=102113\"></script>";
	echo "<script type=\"text/javascript\" src=\"$imasroot/javascript/mathquilled.js?v=071916\"></script>";
	echo "<script type=\"text/javascript\" src=\"$imasroot/javascript/AMtoMQ.js?v=102113\"></script>";
	echo '<style type="text/css"> div.question input.btn { margin-left: 10px; } </style>';
}

//IP: eqntips
if ($showtips==2) {
	echo "<script type=\"text/javascript\" src=\"$imasroot/javascript/eqntips.js?v=062216\"></script>";
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
	$(function(){parent.postMessage(JSON.stringify({subject:\'lti.frameResize\', height: $(document).height()+"px"}), \'*\');});
	</script>';
}
echo '</head><body>';

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
if (!isset($coursetopbar)) {
	$coursetopbar = explode('|',$sessiondata['coursetopbar']);
	$coursetopbar[0] = explode(',',$coursetopbar[0]);
	$coursetopbar[1] = explode(',',$coursetopbar[1]);
	if (!isset($coursetopbar[2])) { $coursetopbar[2] = 0;}
	if ($coursetopbar[0][0] == null) {unset($coursetopbar[0][0]);}
	if ($coursetopbar[1][0] == null) {unset($coursetopbar[1][0]);}
	$coursetoolset = $sessiondata['coursetoolset'];
}

if (isset($cid) && !isset($flexwidth) && (!isset($sessiondata['intreereader']) || $sessiondata['intreereader']==false) && $sessiondata['isteacher'] && $coursetopbar[2]==1 && count($coursetopbar[1])>0) {
	echo '<div id="navlistcont">';
	echo '<ul id="navlist">';
	echo "<li><a href=\"$imasroot/course/course.php?cid=$cid\">Course</a></li> ";
	if (in_array(0,$coursetopbar[1]) && $msgset<4) { //messages
		echo "<li><a href=\"$imasroot/msgs/msglist.php?cid=$cid\">Messages</a></li> ";
	}
	if (in_array(6,$coursetopbar[1]) && (($coursetoolset&2)==0 || !isset($CFG['CPS']['topbar']) || $CFG['CPS']['topbar'][1]==1)) { //Forums
		echo "<li><a href=\"$imasroot/forums/forums.php?cid=$cid\">Forums</a></li>";
	}
	if (in_array(1,$coursetopbar[1])) { //Stu view
		echo "<li><a href=\"$imasroot/course/course.php?cid=$cid&stuview=0\">Student View</a></li>";
	}
	if (in_array(3,$coursetopbar[1])) { //List stu
		echo "<li><a href=\"$imasroot/course/listusers.php?cid=$cid\">Roster</a></li>\n";
	}
	if (in_array(4,$coursetopbar[1])  && (($coursetoolset&1)==0 || !isset($CFG['CPS']['topbar']) || $CFG['CPS']['topbar'][1]==1)) { //Calendar
		echo "<li><a href=\"$imasroot/course/showcalendar.php?cid=$cid\">Calendar</a></li>\n";
	}
	if (in_array(2,$coursetopbar[1])) { //Gradebook
		echo "<li><a href=\"$imasroot/course/gradebook.php?cid=$cid\">Gradebook</a>$gbnewflag</li>";
	}
	if (in_array(7,$coursetopbar[1])) { //Groups
		echo "<li><a href=\"$imasroot/course/managestugrps.php?cid=$cid\">Groups</a></li>\n";
	}
	if (in_array(5,$coursetopbar[1])) { //Quickview
		echo "<li><a href=\"$imasroot/course/course.php?cid=$cid&quickview=on\">Quick View</a></li>\n";
	}

	if (in_array(9,$coursetopbar[1]) && !isset($haslogout)) { //Log out
		echo "<li><a href=\"$imasroot/actions.php?action=logout\">Log Out</a></li>";
	}
	echo '</ul>';
	echo '<br class="clear" />';
	echo '</div>';
} else if (isset($cid) && !isset($flexwidth)  && (!isset($sessiondata['intreereader']) || $sessiondata['intreereader']==false) && !$sessiondata['isteacher'] && $coursetopbar[2]==1 && count($coursetopbar[0])>0) {
	echo '<div id="navlistcont">';
	echo '<ul id="navlist">';
	echo "<li><a href=\"$imasroot/course/course.php?cid=$cid\">Course</a></li> ";
	if (in_array(0,$coursetopbar[0]) && $msgset<4) { //messages
		echo "<li><a href=\"$imasroot/msgs/msglist.php?cid=$cid\">Messages</a></li> ";
	}
	if (in_array(3,$coursetopbar[0]) && (($coursetoolset&2)==0 || !isset($CFG['CPS']['topbar']) || $CFG['CPS']['topbar'][1]==1)) { //forums
		echo "<li><a href=\"$imasroot/forums/forums.php?cid=$cid\">Forums</a></li> ";
	}
	if (in_array(2,$coursetopbar[0]) && (($coursetoolset&1)==0 || !isset($CFG['CPS']['topbar']) || $CFG['CPS']['topbar'][1]==1)) { //Calendar
		echo "<li><a href=\"$imasroot/course/showcalendar.php?cid=$cid\">Calendar</a></li>\n";
	}
	if (in_array(1,$coursetopbar[0])) { //Gradebook
		echo "<li><a href=\"$imasroot/course/gradebook.php?cid=$cid\">Gradebook</a></li> ";
	}
	if (in_array(9,$coursetopbar[0]) && !isset($haslogout)) { //Log out
		echo "<li><a href=\"$imasroot/actions.php?action=logout\">Log Out</a></li>";
	}
	echo '</ul>';
	echo '<br class="clear" />';
	echo '</div>';
}
if (!isset($flexwidth)) {
	echo '</div>';
}
echo '<div class="midwrapper">';


?>

<?php if (!isset($imasroot)) {exit;} ?>
<!DOCTYPE html>
<?php if (isset($CFG['locale'])) {
	echo '<html lang="'.$CFG['locale'].'">';
} else {
	echo '<html lang="en">';
}
?>
<head>
<title><?php echo $installname; if (isset($pagetitle)) { echo " - $pagetitle";}?></title>
<meta http-equiv="X-UA-Compatible" content="IE=7, IE=Edge" />
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js" type="text/javascript"></script>
<script type="text/javascript">
  if (!window.jQuery) {  document.write('<script src="<?php echo $imasroot;?>/javascript/jquery.min.js"><\/script>');}
</script>
<link rel="stylesheet" href="<?php echo $imasroot . "/imascore.css?ver=011018";?>" type="text/css" />
<?php
if (isset($coursetheme)) {
	if (isset($flexwidth) || isset($usefullwidth)) {
		$coursetheme = str_replace(array('_fw1920','_fw1000','_fw'),'',$coursetheme);
	}
	$isfw = false;
	if (strpos($coursetheme,'_fw1920')!==false) {
		$isfw = 1920;
		$coursetheme = str_replace('_fw1920','',$coursetheme);
	} else if (strpos($coursetheme,'_fw')!==false) {
		$isfw = 1000;
		$coursetheme = str_replace(array('_fw1000','_fw'),'',$coursetheme);
	}
	?>
<link rel="stylesheet" href="<?php echo $imasroot . "/themes/$coursetheme?v=042217";?>" type="text/css" />
<link rel="stylesheet" href="<?php echo $imasroot;?>/handheld.css?v=042217" media="only screen and (max-width:480px)"/>

<?php
}
if (isset($CFG['GEN']['favicon'])) {
	echo '<link rel="shortcut icon" href="'.$CFG['GEN']['favicon'].'" />';
} else {
	echo '<link rel="shortcut icon" href="/favicon.ico" />';
}
?>

<!--[if lte IE 6]>
<style>
div { zoom: 1; }
.clearlooks2, .clearlooks2 div { zoom: normal;}
.clear { line-height: 0;}
#GB_overlay, #GB_window {
 position: absolute;
 top: expression(0+((e=document.documentElement.scrollTop)?e:document.body.scrollTop)+'px');
 left: expression(0+((e=document.documentElement.scrollLeft)?e:document.body.scrollLeft)+'px');}
}
</style>
<![endif]-->
<style type="text/css" media="print">
div.breadcrumb { display:none;}
#headerlogo { display:none;}
</style>
<script type="text/javascript">
var imasroot = '<?php echo $imasroot; ?>'; var cid = <?php echo (isset($cid) && is_numeric($cid))?$cid:0; ?>;
</script>
<script type="text/javascript" src="<?php echo $imasroot;?>/javascript/general.js?v=020118"></script>
<?php
//$sessiondata['mathdisp'] = 3;
//writesessiondata();
if (isset($CFG['locale'])) {
	$lang = substr($CFG['locale'],0,2);
	if (file_exists(rtrim(dirname(__FILE__), '/\\').'/i18n/locale/'.$lang.'/messages.js')) {
		echo '<script type="text/javascript" src="'.$imasroot.'/i18n/locale/'.$lang.'/messages.js"></script>';
	}
}
if (isset($coursetheme) && strpos($coursetheme,'_dark')!==false) {$mathdarkbg = true;} else {$mathdarkbg = false;}
if (isset($ispublic) && $ispublic && !isset($sessiondata['mathdisp'])) {
	$sessiondata['mathdisp'] = 1;
	$sessiondata['graphdisp'] = 1;
}
if (!isset($sessiondata['mathdisp'])) {
	echo '<script type="text/javascript">var AMnoMathML = true;var ASnoSVG = true;var AMisGecko = 0;var AMnoTeX = false;</script>';
	//echo '<script type="text/javascript" src="'.$imasroot.'/mathjax/MathJax.js?config=AM_CHTML&rev=2.6.1"></script>';
	echo '<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.2/MathJax.js?config=AM_CHTML-full"></script>';
	echo "<script src=\"$imasroot/javascript/mathgraphcheck.js?v=021215\" type=\"text/javascript\"></script>\n";
} else if ($sessiondata['mathdisp']==1 || $sessiondata['mathdisp']==3) {
	//merged, eliminating original AsciiMath display; MathJax only now
	if (isset($useeditor) && $sessiondata['useed']==1) {
		echo '<script type="text/javascript">var AMTcgiloc = "'.$mathimgurl.'";</script>';
		echo "<script src=\"$imasroot/javascript/ASCIIMathTeXImg_min.js?ver=071116\" type=\"text/javascript\"></script>\n";
	}
	//Contrib not hosted in CDN yet
	//MathJax.Hub.config.extensions.push("[Contrib]/InputToDataAttr/InputToDataAttr.js");

	echo '<script type="text/x-mathjax-config">
		if (MathJax.Hub.Browser.isChrome || MathJax.Hub.Browser.isSafari) {
			MathJax.Hub.Config({"HTML-CSS": {preferredFont: "STIX", imageFont:null}, "messageStyle": "none", asciimath2jax: {ignoreClass:"skipmathrender"}});
		} else {
			MathJax.Hub.Config({"HTML-CSS": {preferredFont: "STIX", webFont: "STIX-Web", imageFont:null}, "messageStyle": "none", asciimath2jax: {ignoreClass:"skipmathrender"}});
		}
		MathJax.Ajax.config.path["Local"] = "'.$imasroot.'/mathjax/extensions";
		MathJax.Hub.config.extensions.push("[Local]/InputToDataAttrCDN.js");
		</script>';
	echo '<script type="text/javascript" async src="https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.2/MathJax.js?config=AM_CHTML-full"></script>';
	//echo '<script>window.MathJax || document.write(\'<script src="'.$imasroot.'/mathjax/MathJax.js?config=AM_CHTML&rev=2.6.1"><\/script>\')</script>';
	//echo '<script type="text/javascript" src="'.$imasroot.'/mathjax/MathJax.js?config=AM_CHTML&rev=2.7.0"></script>';
	echo '<script type="text/javascript">noMathRender = false; var usingASCIIMath = true; var AMnoMathML = true; var MathJaxCompatible = true; function rendermathnode(node) { MathJax.Hub.Queue(["Typeset", MathJax.Hub, node]); }</script>';
	echo '<style type="text/css">span.AM { font-size: 105%;}</style>';
} else if ($sessiondata['mathdisp']==6) {
	//Katex experimental
	echo '<script type="text/javascript">var AMTcgiloc = "'.$mathimgurl.'";</script>';
	echo "<script src=\"$imasroot/javascript/ASCIIMathTeXImg_min.js?ver=071116\" type=\"text/javascript\"></script>\n";

	echo '<script type="text/x-mathjax-config">
		if (MathJax.Hub.Browser.isChrome || MathJax.Hub.Browser.isSafari) {
			MathJax.Hub.Config({"HTML-CSS": {preferredFont: "STIX", imageFont:null}, "messageStyle": "none", asciimath2jax: {ignoreClass:"skipmathrender"}, skipStartupTypeset: true});
		} else {
			MathJax.Hub.Config({"HTML-CSS": {preferredFont: "STIX", webFont: "STIX-Web", imageFont:null}, "messageStyle": "none", asciimath2jax: {ignoreClass:"skipmathrender"}, skipStartupTypeset: true});
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
	echo '<script type="text/javascript" src="'.$imasroot.'/katex/auto-render.js?v=061217"></script>';
	echo '<script type="text/javascript" async src="https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.2/MathJax.js?config=AM_CHTML-full"></script>';
	echo '<script type="text/javascript">noMathRender = false; var usingASCIIMath = true; var AMnoMathML = true; var MathJaxCompatible = true; var mathRenderer = "Katex";</script>';
	//echo '<style type="text/css">span.AM { font-size: 105%;}</style>';
} else if ($sessiondata['mathdisp']==2 && isset($useeditor) && $sessiondata['useed']==1) {
	//these scripts are used by the editor to make image-based math work in the editor
	echo '<script type="text/javascript">var AMTcgiloc = "'.$mathimgurl.'";';
	if ($mathdarkbg) {echo 'var mathbg = "dark";';}
	echo '</script>';
	echo "<script src=\"$imasroot/javascript/ASCIIMathTeXImg_min.js?ver=071116\" type=\"text/javascript\"></script>\n";
	echo "<script type=\"text/javascript\">var usingASCIIMath = false; var AMnoMathML=true; var MathJaxCompatible = false; function rendermathnode(el) {AMprocessNode(el);}</script>";
} else if ($sessiondata['mathdisp']==2) {
	echo "<script type=\"text/javascript\">var usingASCIIMath = false; var AMnoMathML=true; var MathJaxCompatible = false; function rendermathnode(el) {AMprocessNode(el);}</script>";
} else if ($sessiondata['mathdisp']==0) {
	echo "<script type=\"text/javascript\">var usingASCIIMath = false; var AMnoMathML=true; var MathJaxCompatible = false; function rendermathnode(el) {}</script>";
}
echo "<script src=\"$imasroot/javascript/mathjs.js?ver=052016\" type=\"text/javascript\"></script>\n";
if (isset($sessiondata['graphdisp']) && $sessiondata['graphdisp']==1) {
	echo "<script src=\"$imasroot/javascript/ASCIIsvg_min.js?ver=112817\" type=\"text/javascript\"></script>\n";
	echo "<script type=\"text/javascript\">var usingASCIISvg = true;</script>";
	//echo "<script src=\"$imasroot/course/editor/plugins/AsciiSvg/ASCIIsvgAddon.js\" type=\"text/javascript\"></script>\n";
} else if (isset($sessiondata['graphdisp'])) {
	echo "<script type=\"text/javascript\">var usingASCIISvg = false; var ASnoSVG=true;</script>";
}


if (isset($placeinhead)) {
	echo $placeinhead;
}
if (isset($useeditor) && $sessiondata['useed']==1) {
	echo '<script type="text/javascript" src="'.$imasroot.'/tinymce4/tinymce_bundled.js?v=013118"></script>';
	//echo '<script type="text/javascript" src="'.$imasroot.'/tinymce4/tinymce.min.js?v=082716"></script>';
	echo "\n";
	echo '<script type="text/javascript">';
	echo 'var coursetheme = "'.$coursetheme.'";';
	echo 'var tinymceUseSnippets = '.($myrights>10?1:0).';';
	if (!isset($CFG['GEN']['noFileBrowser'])) {
		echo 'var filePickerCallBackFunc = filePickerCallBack;';
	} else {
		echo 'var filePickerCallBackFunc = null;';
	}
	if ($useeditor!="noinit") {
		echo 'initeditor("exact","'.$useeditor.'");';
	}
	echo '</script>';
}
if ((isset($useeditor) && $sessiondata['useed']==1) || isset($loadiconfont)) {
	echo '<link rel="stylesheet" href="'.$imasroot . '/iconfonts/imathasfont.css?v=013118" type="text/css" />';
	echo '<!--[if lte IE 7]><link rel="stylesheet" href="'.$imasroot . '/iconfonts/imathasfontie7.css?v=013118" type="text/css" /><![endif]-->';
}

$curdir = rtrim(dirname(__FILE__), '/\\');
if (isset($CFG['GEN']['headerscriptinclude'])) {
	require("$curdir/{$CFG['GEN']['headerscriptinclude']}");
}
if (isset($CFG['GEN']['translatewidgetID'])) {
	echo '<meta name="google-translate-customization" content="'.$CFG['GEN']['translatewidgetID'].'"></meta>';
}
if (isset($sessiondata['ltiitemtype'])) {
	echo '<script type="text/javascript">
	$(function(){parent.postMessage(JSON.stringify({subject:\'lti.frameResize\', height: $(document).height()+"px"}), \'*\');});
	</script>';
}
echo "</head>\n";
if ($isfw!==false) {
	echo "<body class=\"fw$isfw\">\n";
} else {
	echo "<body class=\"notfw\">\n";
}

echo '<div class="mainbody">';

$insertinheaderwrapper = ' '; //"<h1>$coursename</h1>";
if (isset($insertinheaderwrapper)) {
	//echo '<div class="headerwrapper">'.$insertinheaderwrapper.'</div>';
}
if (!isset($flexwidth)) {
	echo '<div class="headerwrapper">';
}
if (isset($CFG['GEN']['headerinclude']) && !isset($flexwidth)) {
	require("$curdir/{$CFG['GEN']['headerinclude']}");
}
$didnavlist = false;  $essentialsnavcnt = 0;
function getactivetab() {
	$a = 'course';
	$path = $_SERVER['PHP_SELF'];
	if (strpos($path,'/msgs/')!==false) {
		$a = 'msg';
	} else if (strpos($path,'/forums/')!==false) {
		$a = 'forum';
	} else if (strpos($path,'showcalendar.php')!==false) {
		$a = 'cal';
	} else if (strpos($path,'grade')!==false || strpos($path,'/gb')!==false) {
		$a = 'gb';
	} else if (strpos($path,'listusers')!==false || strpos($path,'/latepass')!==false) {
		$a = 'roster';
	}
	return $a;
}
if (isset($cid) && !isset($flexwidth)) {
	echo '<div id="navlistcont" role="navigation" aria-label="'._('Course Navigation').'">';
	echo '<ul id="navlist">';
	$a = array('course'=>'', 'msg'=>'', 'forum'=>'', 'cal'=>'', 'gb'=>'', 'roster'=>'');
	$c = getactivetab();
	$a[$c] = 'class="activetab"';

	echo "<li><a {$a['course']} href=\"$imasroot/course/course.php?cid=$cid\">Course</a></li> ";
	if ($coursemsgset<4) { //messages
		echo "<li><a {$a['msg']} href=\"$imasroot/msgs/msglist.php?cid=$cid\">Messages</a></li> ";
	}

	if (($coursetoolset&2)==0) { //forums
		echo "<li><a {$a['forum']} href=\"$imasroot/forums/forums.php?cid=$cid\">Forums</a></li>";
	}

	if (isset($teacherid)) { //Roster
		echo "<li><a {$a['roster']} href=\"$imasroot/course/listusers.php?cid=$cid\">Roster</a></li>\n";
	}

	if (($coursetoolset&1)==0) { //Calendar
		echo "<li><a {$a['cal']} href=\"$imasroot/course/showcalendar.php?cid=$cid\">Calendar</a></li>\n";
	}

	echo "<li><a {$a['gb']} href=\"$imasroot/course/gradebook.php?cid=$cid\">Gradebook</a></li>"; //Gradebook

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

//load filter
$curdir = rtrim(dirname(__FILE__), '/\\');
require_once("$curdir/filter/filter.php");

//CUSTOMIZE:  put a small (max 120px wide) logo on upper right of course pages

if (!isset($nologo)) {
	echo '<div id="headerlogo" class="hideinmobile" ';
	if ($myrights>10 && !$ispublic && !isset($sessiondata['ltiitemtype'])) {
		echo 'onclick="mopen(\'homemenu\',';
		if (isset($cid) && is_numeric($cid)) {
			echo $cid;
		} else {
			echo 0;
		}
		echo ')" onmouseout="mclosetime()"';
	}
	echo '>'.$smallheaderlogo.'</div>';
	if ($myrights>10 && !$ispublic && !isset($sessiondata['ltiitemtype'])) {
		echo '<div id="homemenu" class="ddmenu" onmouseover="mcancelclosetime()" onmouseout="mclosetime()">';
		echo '</div>';
	}

}


?>

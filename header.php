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
//Look to see if a hook file is defined, and include if it is
if (isset($CFG['hooks']['header'])) {
    require_once $CFG['hooks']['header'];
}
?>
<head>
<title><?php echo $installname; if (isset($pagetitle)) { echo " - $pagetitle";}?></title>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<?php
if (!empty($CFG['GEN']['uselocaljs'])) {
	echo '<script src="'.$staticroot.'/javascript/jquery.min.js"></script>';
} else {
    echo '<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.1/dist/jquery.min.js" integrity="sha256-o88AwQnZB+VDvE9tvIXrMQaPlFFSUTR+nldQm1LuPXQ=" crossorigin="anonymous"></script>';
	echo '<script>window.jQuery || document.write(\'<script src="'.$staticroot.'/javascript/jquery.min.js"><\/script>\')</script>';
}
if (empty($_SESSION['tzoffset']) && !empty($CFG['static_server'])) {
    echo '<script src="'.$CFG['static_server'].'/javascript/staticcheck.js"></script>';
}
?>
<link rel="stylesheet" href="<?php echo $staticroot . "/imascore.css?ver=021426";?>" type="text/css" />
<?php
$isfw = false;
if (isset($coursetheme)) {
	if (strpos($coursetheme,'_fw1920')!==false) {
		$isfw = 1920;
		$coursetheme = str_replace('_fw1920','',$coursetheme);
	} else if (strpos($coursetheme,'_fw')!==false) {
		$isfw = 1000;
		$coursetheme = str_replace(array('_fw1000','_fw'),'',$coursetheme);
	}
} 
if (isset($CFG['GEN']['favicon'])) {
	echo '<link rel="icon" sizes="32x32" href="'.$CFG['GEN']['favicon'].'" />';
} else {
	echo '<link rel="icon" sizes="32x32" href="/favicon.ico" />';
}
if (isset($CFG['GEN']['svgfavicon'])) {
	echo '<link rel="icon" sizes="any" type="image/svg+xml" href="'.$CFG['GEN']['svgfavicon'].'" />';
}
if (isset($CFG['GEN']['96icon'])) {
	echo '<link rel="icon" sizes="96x96" href="'.$CFG['GEN']['96icon'].'" />';
} 
if (isset($CFG['GEN']['appleicon'])) {
	echo '<link rel="apple-touch-icon" href="'.$CFG['GEN']['appleicon'].'" />';
}

if (isset($CFG['GEN']['webmanifest'])) {
	echo '<link rel="manifest" href="'.$CFG['GEN']['webmanifest'].'" />';
}
if (!empty($CFG['use_csrfp']) && class_exists('csrfProtector')) {
	echo csrfProtector::output_header_code();
}
?>

<style type="text/css" media="print">
div.breadcrumb { display:none;}
#headerlogo { display:none;}
</style>
<script type="text/javascript">
var imasroot = '<?php echo $imasroot; ?>'; var cid = <?php echo (isset($cid) && is_numeric($cid))?$cid:0; ?>;
var staticroot = '<?php echo $staticroot; ?>';
<?php if (!empty($CFG['nocommathousandsseparator'])) { echo 'var commasep = false;'; } ?>
<?php if (isset($CFG['S3']['altendpoint'])) { echo 'var altfilesendpoint = "'.Sanitize::encodeStringForDisplay($CFG['S3']['altendpoint']).'";';} ?>
</script>
<script type="text/javascript" src="<?php echo $staticroot;?>/javascript/general.js?v=021326"></script>
<?php
// override allowedImgDomains if set in config
if (isset($CFG['GEN']['allowedImgDomains'])) {
	echo '<script>allowedImgDomains = ' . $CFG['GEN']['allowedImgDomains'] . ';</script>';
}
//$_SESSION['mathdisp'] = 3;
//
if (isset($CFG['locale'])) {
	$lang = substr($CFG['locale'],0,2);
	if (file_exists(rtrim(dirname(__FILE__), '/\\').'/i18n/locale/'.$lang.'/messages.js')) {
		echo '<script type="text/javascript" src="'.$staticroot.'/i18n/locale/'.$lang.'/messages.js"></script>';
	}
}
if (isset($coursetheme) && strpos($coursetheme,'_dark')!==false) {$mathdarkbg = true;} else {$mathdarkbg = false;}
if (isset($ispublic) && $ispublic && !isset($_SESSION['mathdisp'])) {
	$_SESSION['mathdisp'] = 1;
	$_SESSION['graphdisp'] = 1;
}

if ((isset($useeditor) && $_SESSION['useed']==1) || // using editor
	(isset($_SESSION['mathdisp']) && $_SESSION['mathdisp']==6) // katex
) {
	echo '<script type="text/javascript">var AMTcgiloc = "'.$mathimgurl.'";';
	if (!empty($CFG['GEN']['mathcgisvg'])) {
		echo 'var AMTcgilocUseSVG = true;';
	}
	if (isset($_SESSION['mathdisp']) && $_SESSION['mathdisp']==2 && $mathdarkbg) {
		echo 'var mathbg = "dark";';
	}
	echo '</script>';
	echo "<script src=\"$staticroot/javascript/ASCIIMathTeXImg_min.js?ver=111923\" type=\"text/javascript\"></script>\n";
}
if (!isset($_SESSION['mathdisp'])) {
	echo '<script type="text/javascript">var AMnoMathML = true;var ASnoSVG = true;var AMisGecko = 0;var AMnoTeX = false;var mathRenderer="none";</script>';
} else if ($_SESSION['mathdisp']==6) { // Katex
	echo '<script src="'.$staticroot.'/katex/katex.min.js"></script>';
	echo '<link rel="stylesheet" href="'.$staticroot.'/katex/katex.min.css" />';
	echo '<script type="text/javascript" src="'.$staticroot.'/katex/auto-render.js?v=111025"></script>';
	echo '<script type="text/javascript">setupKatexAutoRender();</script>';
	echo '<script type="text/javascript">noMathRender = false; var usingASCIIMath = true; var AMnoMathML = true; var MathJaxCompatible = true; var mathRenderer = "Katex";</script>';
	//echo '<style type="text/css">span.AM { font-size: 105%;}</style>';
} else if ($_SESSION['mathdisp']==2) {
	echo "<script type=\"text/javascript\">var usingASCIIMath = false; var AMnoMathML=true; var MathJaxCompatible = false; var mathRenderer=\"Image\";function rendermathnode(el,callback) {AMprocessNode(el);} if(typeof callback=='function'){callback();}</script>";
} else if ($_SESSION['mathdisp'] == 8) { // mathjax 3
	echo '<script>var mathjaxdisp = 8;</script>'; 
    echo "<script src=\"$staticroot/javascript/mathjaxconfig.js?ver=020426\" type=\"text/javascript\"></script>\n";
    echo '<script src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/startup.js" id="MathJax-script"></script>';
	echo '<style type="text/css">span.AM { font-size: 105%;} </style>';
} else if ($_SESSION['mathdisp'] > 0) { // mathjax
	echo '<script>var mathjaxdisp = 9;</script>'; // default MJ 4
	echo '<script nomodule>mathjaxdisp = 8;</script>'; // fallback to MJ 3 for old browsers
    echo "<script src=\"$staticroot/javascript/mathjaxconfig.js?ver=020426\" type=\"text/javascript\"></script>\n";
	if (!empty($CFG['GEN']['uselocaljs'])) {
    	echo '<script type="module" src="'.$staticroot.'/javascript/mathjax4/startup.js?ver=020426" id="MathJax-script"></script>';
	} else {
		echo '<script type="module" src="https://cdn.jsdelivr.net/npm/mathjax@4/startup.js" id="MathJax-script"></script>';
	}
	echo '<script nomodule src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/startup.js" id="MathJax-script-fb"></script>';
	echo '<style type="text/css">span.AM { font-size: 105%;} </style>';
} else if ($_SESSION['mathdisp']==0) { // none
	echo "<script type=\"text/javascript\">var usingASCIIMath = false; var AMnoMathML=true; var MathJaxCompatible = false; var mathRenderer=\"none\";function rendermathnode(el,callback) {if(typeof callback=='function'){callback();}}</script>";
}
echo "<script src=\"$staticroot/javascript/mathparser_min.js?v=100125\" type=\"text/javascript\"></script>\n";
if (isset($_SESSION['graphdisp']) && $_SESSION['graphdisp']==1) {
	echo "<script src=\"$staticroot/javascript/ASCIIsvg_min.js?v=122025\" type=\"text/javascript\"></script>\n";
	echo "<script type=\"text/javascript\">var usingASCIISvg = true;</script>";
	//echo "<script src=\"$imasroot/course/editor/plugins/AsciiSvg/ASCIIsvgAddon.js\" type=\"text/javascript\"></script>\n";
} else if (isset($_SESSION['graphdisp'])) {
	echo "<script type=\"text/javascript\">var usingASCIISvg = false; var ASnoSVG=true;</script>";
}


if (isset($useeditor) && $_SESSION['useed']==1) {
    echo '<script type="text/javascript" src="'.$staticroot.'/tinymce8/tinymce.min.js?v=073125" referrerpolicy="origin" crossorigin="anonymous"></script>';

	echo "\n";
	echo '<script type="text/javascript">';
	echo 'var coursetheme = "'.$coursetheme.'";';
	echo 'var tinymceUseSnippets = '.($myrights>10?1:0).';';
	if (!isset($CFG['GEN']['noFileBrowser'])) {
		echo 'var filePickerCallBackFunc = filePickerCallBack;';
	} else {
		echo 'var filePickerCallBackFunc = null;';
	}
	if ($useeditor!="noinit" && $useeditor != "review" && $useeditor != "reviewifneeded") {
		echo 'initeditor("exact","'.$useeditor.'");';
	}
	echo '</script>';
}
if (isset($loadiconfont)) {
	echo '<link rel="stylesheet" href="'.$staticroot . '/iconfonts/imathasfont.css?v=013118" type="text/css" />';
}
if (isset($placeinhead)) {
	echo $placeinhead;
}
$curdir = rtrim(dirname(__FILE__), '/\\');
if (isset($CFG['GEN']['headerscriptinclude'])) {
	require_once "$curdir/{$CFG['GEN']['headerscriptinclude']}";
}
if (function_exists('insertIntoHead')) {
    insertIntoHead();
}
if (isset($coursetheme)) {
	echo '<link rel="stylesheet" href="'. $staticroot . "/themes/$coursetheme?v=081225\" type=\"text/css\" />";
}
echo '<link rel="stylesheet" href="'. $staticroot . '/handheld.css?v=071320" media="only screen and (max-width:480px)"/>';
if (isset($CFG['GEN']['translatewidgetID'])) {
	echo '<meta name="google-translate-customization" content="'.$CFG['GEN']['translatewidgetID'].'"></meta>';
}
if (isset($_SESSION['ltiitemtype'])) {
	echo '<script type="text/javascript">
	if (typeof mathRenderer != "undefined" && mathRenderer == "Katex") {
		window.katexDoneCallback = sendLTIresizemsg;
	} else {
		jQuery(sendLTIresizemsg);
	}
	</script>';
}
echo "</head>\n";
if ($isfw!==false) {
	if (!empty($flexwidth) || !empty($usefullwidth)) {
		echo "<body data-fw=\"fw$isfw\" class=\"notfw\">\n";
	} else {
		echo "<body class=\"fw$isfw\">\n";
	}
} else {
	echo "<body class=\"notfw\">\n";
}
echo '<div class="mainbody">';
if (empty($noskipnavlink)) {
	echo '<a href="#" id="pageskipnav" class="sr-only">'._('Skip Navigation').'</a>';
}

$insertinheaderwrapper = ' '; //"<h1>$coursename</h1>";
if (isset($insertinheaderwrapper)) {
	//echo '<div class="headerwrapper">'.$insertinheaderwrapper.'</div>';
}
if (!isset($flexwidth) && !isset($hideAllHeaderNav)) {
	echo '<div class="headerwrapper">';
}
if (isset($CFG['GEN']['headerinclude']) && !isset($flexwidth) && !isset($hideAllHeaderNav)) {
    $prepend = '/' == substr($CFG['GEN']['headerinclude'], 0, 1) ? '' : $curdir;
	require_once "$prepend/{$CFG['GEN']['headerinclude']}";
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
if (!empty($cid) && !isset($flexwidth) && !isset($hideAllHeaderNav) && !isset($nocoursenav)) {
	echo '<div id="navlistcont" role="navigation" aria-label="'._('Course Navigation').'">';
	echo '<ul id="navlist">';
	$a = array('course'=>'', 'msg'=>'', 'forum'=>'', 'cal'=>'', 'gb'=>'', 'roster'=>'');
	$c = getactivetab();
	$a[$c] = 'class="activetab"';

	echo "<li><a {$a['course']} href=\"$imasroot/course/course.php?cid=$cid\">",_('Course'),"</a></li> ";
	if (isset($coursemsgset) && $coursemsgset<4) { //messages
		echo "<li><a {$a['msg']} href=\"$imasroot/msgs/msglist.php?cid=$cid\">",_('Messages'),"</a></li> ";
	}

	if (isset($coursetoolset) && ($coursetoolset&2)==0) { //forums
		echo "<li><a {$a['forum']} href=\"$imasroot/forums/forums.php?cid=$cid\">",_('Forums'),"</a></li>";
	}

	if (isset($teacherid)) { //Roster
		echo "<li><a {$a['roster']} href=\"$imasroot/course/listusers.php?cid=$cid\">",_('Roster'),"</a></li>\n";
	}

	if (isset($coursetoolset) && ($coursetoolset&1)==0) { //Calendar
		echo "<li><a {$a['cal']} href=\"$imasroot/course/showcalendar.php?cid=$cid\">",_('Calendar'),"</a></li>\n";
	}

    if (isset($coursetoolset) && ($coursetoolset&4)==0) {
	    echo "<li><a {$a['gb']} href=\"$imasroot/course/gradebook.php?cid=$cid\">",_('Gradebook'),"</a></li>"; //Gradebook
    }

	if (!isset($haslogout)) { //Log out
		echo "<li><a href=\"$imasroot/actions.php?action=logout\">",_('Log Out'),"</a></li>";
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

//load filter
$curdir = rtrim(dirname(__FILE__), '/\\');
require_once "$curdir/filter/filter.php";

//CUSTOMIZE:  put a small (max 120px wide) logo on upper right of course pages

if (!isset($nologo) && !empty($smallheaderlogo)) {
	echo '<div id="headerlogo" class="hideinmobile" ';
	if (isset($myrights) && $myrights>10 && !$ispublic && !isset($_SESSION['ltiitemtype'])) {
		echo 'onclick="GB_show(\''._('My Classes').'\',\''.$imasroot.'/gethomemenu.php\',800,\'auto\',true);"';
	}
	echo '>'.$smallheaderlogo.'</div>';
}


?>

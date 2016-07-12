<!DOCTYPE html>
<html>
<head>
<title><?php echo $installname; if (isset($pagetitle)) { echo " - $pagetitle";}?></title>
<meta http-equiv="X-UA-Compatible" content="IE=7, IE=Edge" />
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js" type="text/javascript"></script>
<script type="text/javascript">
  if (!window.jQuery) {  document.write('<script src="<?php echo $imasroot;?>/javascript/jquery.min.js"><\/script>');}
</script>
<link rel="stylesheet" href="<?php echo $imasroot . "/imascore.css?ver=070816";?>" type="text/css" />
<?php if (isset($coursetheme)) { 
	if (isset($flexwidth) || isset($usefullwidth)) {
		$coursetheme = str_replace('_fw','',$coursetheme);
	}
	?>
<link rel="stylesheet" href="<?php echo $imasroot . "/themes/$coursetheme?v=121713";?>" type="text/css" />
<link rel="stylesheet" href="<?php echo $imasroot;?>/handheld.css?v=070816" media="only screen and (max-width:480px)"/>

<?php } ?>
<link rel="shortcut icon" href="/favicon.ico" />
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
<script type="text/javascript" src="<?php echo $imasroot;?>/javascript/general.js?v=070816"></script>
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
	echo '<script type="text/javascript" src="'.$imasroot.'/mathjax/MathJax.js?config=AM_CHTML&rev=2.6.1"></script>';
	echo "<script src=\"$imasroot/javascript/mathgraphcheck.js?v=021215\" type=\"text/javascript\"></script>\n";
} else if ($sessiondata['mathdisp']==1 || $sessiondata['mathdisp']==3) {
	//merged, eliminating original AsciiMath display; MathJax only now
	if (isset($useeditor) && $sessiondata['useed']==1) {
		echo '<script type="text/javascript">var AMTcgiloc = "'.$mathimgurl.'";</script>';
		echo "<script src=\"$imasroot/javascript/ASCIIMathTeXImg_min.js?ver=071116\" type=\"text/javascript\"></script>\n";
	}
	echo '<script type="text/x-mathjax-config">
		if (MathJax.Hub.Browser.isChrome || MathJax.Hub.Browser.isSafari) {
			MathJax.Hub.Config({"HTML-CSS": {preferredFont: "STIX", imageFont:null}});
		} else {
			MathJax.Hub.Config({"HTML-CSS": {preferredFont: "STIX", webFont: "STIX-Web", imageFont:null}});
		}
		MathJax.Hub.config.extensions.push("InputToDataAttr.js");
		</script>';
		// webFont: "STIX-Web", 
	//echo '<script type="text/javascript" src="https://c328740.ssl.cf1.rackcdn.com/mathjax/latest/MathJax.js?config=AM_HTMLorMML"></script>';
	//echo '<script>window.MathJax || document.write(\'<script type="text/x-mathjax-config">MathJax.Hub.Config({"HTML-CSS":{imageFont:null}});<\/script><script src="'.$imasroot.'/mathjax/MathJax.js?config=AM_HTMLorMML"><\/script>\')</script>';
	echo '<script type="text/javascript" src="'.$imasroot.'/mathjax/MathJax.js?config=AM_CHTML&rev=2.6.1"></script>';
	echo '<script type="text/javascript">noMathRender = false; var usingASCIIMath = true; var AMnoMathML = true; var MathJaxCompatible = true; function rendermathnode(node) { MathJax.Hub.Queue(["Typeset", MathJax.Hub, node]); }</script>'; 
	echo '<style type="text/css">span.AM { font-size: 105%;}</style>';
} else if ($sessiondata['mathdisp']==6) {
	//Katex experimental
	echo '<script type="text/javascript">var AMTcgiloc = "'.$mathimgurl.'";</script>';
	echo "<script src=\"$imasroot/javascript/ASCIIMathTeXImg_min.js?ver=071116\" type=\"text/javascript\"></script>\n";
	
	echo '<script type="text/x-mathjax-config">
		if (MathJax.Hub.Browser.isChrome || MathJax.Hub.Browser.isSafari) {
			MathJax.Hub.Config({"HTML-CSS": {preferredFont: "STIX", imageFont:null}, skipStartupTypeset: true});
		} else {
			MathJax.Hub.Config({"HTML-CSS": {preferredFont: "STIX", webFont: "STIX-Web", imageFont:null}, skipStartupTypeset: true});
		}
		MathJax.Hub.config.extensions.push("InputToDataAttr.js");
		</script>';
		// webFont: "STIX-Web", 
	//echo '<script type="text/javascript" src="https://c328740.ssl.cf1.rackcdn.com/mathjax/latest/MathJax.js?config=AM_HTMLorMML"></script>';
	//echo '<script>window.MathJax || document.write(\'<script type="text/x-mathjax-config">MathJax.Hub.Config({"HTML-CSS":{imageFont:null}});<\/script><script src="'.$imasroot.'/mathjax/MathJax.js?config=AM_HTMLorMML"><\/script>\')</script>';
	echo '<script type="text/javascript" src="'.$imasroot.'/mathjax/MathJax.js?config=AM_CHTML&rev=2.6.1"></script>';
	echo '<script src="'.$imasroot.'/katex/katex.min.js"></script>';
	echo '<link rel="stylesheet" href="'.$imasroot.'/katex/katex.min.css"/>';
	echo '<script type="text/javascript" src="'.$imasroot.'/katex/auto-render.js?v=070616"></script>';
	echo '<script type="text/javascript">noMathRender = false; var usingASCIIMath = true; var AMnoMathML = true; var MathJaxCompatible = true; function rendermathnode(node) {renderMathInElement(node);}</script>'; 
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
	echo "<script src=\"$imasroot/javascript/ASCIIsvg_min.js?ver=070516\" type=\"text/javascript\"></script>\n";
	echo "<script type=\"text/javascript\">var usingASCIISvg = true;</script>";
	//echo "<script src=\"$imasroot/course/editor/plugins/AsciiSvg/ASCIIsvgAddon.js\" type=\"text/javascript\"></script>\n";
} else if (isset($sessiondata['graphdisp'])) {
	echo "<script type=\"text/javascript\">var usingASCIISvg = false; var ASnoSVG=true;</script>";
}


if (isset($placeinhead)) {
	echo $placeinhead;
}
if (isset($useeditor) && $sessiondata['useed']==1) {
	echo '<script type="text/javascript" src="'.$imasroot.'/tinymce4/tinymce.min.js?v=111612"></script>';
	echo "\n";
	echo '<script type="text/javascript">';
	echo 'var coursetheme = "'.$coursetheme.'";';
	if (!isset($CFG['GEN']['noFileBrowser'])) {
		echo 'var filePickerCallBackFunc = filePickerCallBack;';
	} else {
		echo 'var filePickerCallBackFunc = null;';
	}
	echo 'initeditor("exact","'.$useeditor.'");';
	echo '</script>';
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
echo "<body>\n";


$insertinheaderwrapper = ' '; //"<h1>$coursename</h1>";
echo '<div class=mainbody>';
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
	$t = 10; $s = 10;
	$path = $_SERVER['PHP_SELF'];
	if (strpos($path,'/msgs/')!==false) {
		$t = 0;   $s = 0;
	} else if (strpos($path,'/forums/')!==false) {
		$t= 6;  $s = 3;
	} else if (strpos($path,'showcalendar.php')!==false) {
		$t = 4;  $s = 2;
	} else if (strpos($path,'stugrps')!==false) {
		$t = 7;
	} else if (strpos($path,'grade')!==false || strpos($path,'/gb')!==false) {
		$t = 2; $s = 1;
	} else if (strpos($path,'listusers')!==false || strpos($path,'/latepass')!==false) {
		$t = 3;
	} 
	return array($t,$s);
}
if (isset($cid) && isset($teacherid) && $coursetopbar[2]==1 && count($coursetopbar[1])>0 && !isset($flexwidth)) {
	echo '<div id="navlistcont">';
	echo '<ul id="navlist">';
	$a = array_fill(0,11,"");
	$c = getactivetab();
	$a[$c[0]] = 'class="activetab"';

	echo "<li><a {$a[10]} href=\"$imasroot/course/course.php?cid=$cid\">Course</a></li> ";
	if (in_array(0,$coursetopbar[1]) && $coursemsgset<4) { //messages
		echo "<li><a {$a[0]} href=\"$imasroot/msgs/msglist.php?cid=$cid\">Messages</a></li> ";
		$essentialsnavcnt++;
	}
	if (in_array(6,$coursetopbar[1]) && (($coursetoolset&2)==0 || !isset($CFG['CPS']['topbar']) || $CFG['CPS']['topbar'][1]==1)) { //forums
		echo "<li><a {$a[6]} href=\"$imasroot/forums/forums.php?cid=$cid\">Forums</a></li>";
		$essentialsnavcnt++;
	}
	if (in_array(1,$coursetopbar[1])) { //Stu view
		echo "<li><a href=\"$imasroot/course/course.php?cid=$cid&stuview=0\">Student View</a></li>";
	}
	if (in_array(3,$coursetopbar[1])) { //List stu
		echo "<li><a {$a[3]} href=\"$imasroot/course/listusers.php?cid=$cid\">Roster</a></li>\n";
		$essentialsnavcnt++;
	}
	if (in_array(4,$coursetopbar[1])  && (($coursetoolset&1)==0 || !isset($CFG['CPS']['topbar']) || $CFG['CPS']['topbar'][1]==1)) { //Calendar
		echo "<li><a {$a[4]} href=\"$imasroot/course/showcalendar.php?cid=$cid\">Calendar</a></li>\n";
	}
	if (in_array(2,$coursetopbar[1])) { //Gradebook
		echo "<li><a {$a[2]} href=\"$imasroot/course/gradebook.php?cid=$cid\">Gradebook</a>$gbnewflag</li>";
		$essentialsnavcnt++;
	}
	if (in_array(7,$coursetopbar[1])) { //Groups
		echo "<li><a {$a[7]} href=\"$imasroot/course/managestugrps.php?cid=$cid\">Groups</a></li>\n";
	}
	
	
	if (in_array(5,$coursetopbar[1])) { //Quick view
		echo "<li><a {$a[5]} href=\"$imasroot/course/course.php?cid=$cid&quickview=on\">Quick View</a></li>\n";
	}
	
	if (in_array(9,$coursetopbar[1]) && !isset($haslogout)) { //Log out
		echo "<li><a href=\"$imasroot/actions.php?action=logout\">Log Out</a></li>";
	}
	echo '</ul>';
	//echo '<br class="clear" />';
	echo '<div class="clear"></div>';
	echo '</div>';
	$didnavlist = true;
} else if (isset($cid) && !isset($teacherid) && $coursetopbar[2]==1 && count($coursetopbar[0])>0 && !isset($flexwidth)) {
	echo '<div id="navlistcont">';
	echo '<ul id="navlist">';
	$a = array_fill(0,11,"");
	$c = getactivetab();
	$a[$c[1]] = 'class="activetab"';
	echo "<li><a {$a[10]} href=\"$imasroot/course/course.php?cid=$cid\">Course</a></li> ";
	if (in_array(0,$coursetopbar[0]) && $coursemsgset<4) { //messages
		echo "<li><a {$a[0]} href=\"$imasroot/msgs/msglist.php?cid=$cid\">Messages</a></li> ";
		$essentialsnavcnt++;
	}
	if (in_array(3,$coursetopbar[0]) && (($coursetoolset&2)==0 || !isset($CFG['CPS']['topbar']) || $CFG['CPS']['topbar'][1]==1)) { //forums
		echo "<li><a {$a[3]} href=\"$imasroot/forums/forums.php?cid=$cid\">Forums</a></li> ";
		$essentialsnavcnt++;
	}
	if (in_array(2,$coursetopbar[0]) && (($coursetoolset&1)==0 || !isset($CFG['CPS']['topbar']) || $CFG['CPS']['topbar'][1]==1)) { //Calendar
		echo "<li><a {$a[2]} href=\"$imasroot/course/showcalendar.php?cid=$cid\">Calendar</a></li>\n";
		$essentialsnavcnt++;
	}
	if (in_array(1,$coursetopbar[0])) { //Gradebook
		echo "<li><a {$a[1]} href=\"$imasroot/course/gradebook.php?cid=$cid\">Gradebook</a></li> ";
		$essentialsnavcnt++;
	}
	
	if (in_array(9,$coursetopbar[0]) && !isset($haslogout)) { //Log out
		echo "<li><a href=\"$imasroot/actions.php?action=logout\">Log Out</a></li>";
	}
	echo '</ul>';
	echo '<br class="clear" />';
	echo '</div>';
	$didnavlist = true;
}
if (!isset($flexwidth)) {
	echo '</div>';
}
echo '<div class="midwrapper">';

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



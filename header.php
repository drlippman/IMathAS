<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title><?php echo $installname; if (isset($pagetitle)) { echo " - $pagetitle";}?></title>
<meta http-equiv="X-UA-Compatible" content="IE=7, IE=Edge" />
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js" type="text/javascript"></script>
<link rel="stylesheet" href="<?php echo $imasroot . "/imascore.css?ver=070513";?>" type="text/css" />
<?php if (isset($coursetheme)) { 
	if (isset($flexwidth) || isset($usefullwidth)) {
		$coursetheme = str_replace('_fw','',$coursetheme);
	}
	?>
<link rel="stylesheet" href="<?php echo $imasroot . "/themes/$coursetheme?v=012810";?>" type="text/css" />
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
<script type="text/javascript" src="<?php echo $imasroot;?>/javascript/general.js?ver=070513"></script>
<?php
if (isset($CFG['locale'])) {
	$lang = substr($CFG['locale'],0,2);
	if (file_exists(rtrim(dirname(__FILE__), '/\\').'/i18n/locale/'.$lang.'/messages.js')) {
		echo '<script type="text/javascript" src="'.$imasroot.'/i18n/locale/'.$lang.'/messages.js"></script>';
	}
}
if (isset($coursetheme) && strpos($coursetheme,'_dark')!==false) {$mathdarkbg = true;} else {$mathdarkbg = false;}
if (isset($ispublic) && $ispublic) {
	echo "<script src=\"$imasroot/javascript/ASCIIMathMLwFallback.js?ver=082911\" type=\"text/javascript\"></script>\n";
	echo "<script src=\"$imasroot/javascript/ASCIIsvg_min.js?ver=091311\" type=\"text/javascript\"></script>\n";
	echo "<script type=\"text/javascript\">var usingASCIIMath = true; var usingASCIISvg = true;</script>"; 
	echo '<script type="text/javascript">var AMTcgiloc = "'.$mathimgurl.'";';
	if ($mathdarkbg) {echo 'var mathbg = "dark";';}
	echo '</script>'; 
	echo '<script type="text/javascript">var AScgiloc = "'.$imasroot.'/filter/graph/svgimg.php";</script>'; 
} else {
if (!isset($sessiondata['mathdisp'])) {
	echo '<script type="text/javascript">var AMnoMathML = true;var ASnoSVG = true;var AMisGecko = 0;var AMnoTeX = false;</script>';
	echo "<script src=\"$imasroot/javascript/mathgraphcheck.js?v=051313\" type=\"text/javascript\"></script>\n";
} else if ($sessiondata['mathdisp']==1) {
	echo "<script src=\"$imasroot/javascript/ASCIIMathML_min.js?v=100411\" type=\"text/javascript\"></script>\n";
	echo "<script type=\"text/javascript\">var usingASCIIMath = true;</script>";
} else if ($sessiondata['mathdisp']==2 && isset($useeditor) && $sessiondata['useed']==1) {
	//these scripts are used by the editor to make image-based math work in the editor
	echo '<script type="text/javascript">var AMTcgiloc = "'.$mathimgurl.'";';
	if ($mathdarkbg) {echo 'var mathbg = "dark";';}
	echo '</script>'; 
	echo "<script src=\"$imasroot/javascript/ASCIIMathTeXImg_min.js?ver=122912\" type=\"text/javascript\"></script>\n";
	echo "<script type=\"text/javascript\">var usingASCIIMath = false; var AMnoMathML=true;</script>";
} else {
	echo "<script type=\"text/javascript\">var usingASCIIMath = false; var AMnoMathML=true;</script>";
}
if (isset($sessiondata['graphdisp']) && $sessiondata['graphdisp']==1) {
	echo "<script src=\"$imasroot/javascript/ASCIIsvg_min.js?ver=091311\" type=\"text/javascript\"></script>\n";
	echo "<script type=\"text/javascript\">var usingASCIISvg = true;</script>";
	//echo "<script src=\"$imasroot/course/editor/plugins/AsciiSvg/ASCIIsvgAddon.js\" type=\"text/javascript\"></script>\n";
} else if (isset($sessiondata['graphdisp'])) {
	echo "<script src=\"$imasroot/javascript/mathjs.js\" type=\"text/javascript\"></script>\n";
	echo "<script type=\"text/javascript\">var usingASCIISvg = false; var ASnoSVg=true;</script>";
}
}


$start_time = microtime(true); 
if (isset($placeinhead)) {
	echo $placeinhead;
}
if (isset($useeditor) && $sessiondata['useed']==1) {
	echo '<script type="text/javascript" src="'.$imasroot.'/editor/tiny_mce.js?v=111612"></script>';
	echo "\n";
	echo '<script type="text/javascript">';
	echo 'var coursetheme = "'.$coursetheme.'";';
	if (!isset($CFG['GEN']['noFileBrowser'])) {
		echo 'var fileBrowserCallBackFunc = "fileBrowserCallBack";';
	} else {
		echo 'var fileBrowserCallBackFunc = null;';
	}
	echo 'initeditor("exact","'.$useeditor.'");';
	echo '</script>';
}


 
$curdir = rtrim(dirname(__FILE__), '/\\');
if (isset($CFG['GEN']['headerscriptinclude'])) {
	require("$curdir/{$CFG['GEN']['headerscriptinclude']}");
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
$didnavlist = false;
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
	if (in_array(0,$coursetopbar[1]) && $msgset<4) { //messages
		echo "<li><a {$a[0]} href=\"$imasroot/msgs/msglist.php?cid=$cid\">Messages</a></li> ";
	}
	if (in_array(6,$coursetopbar[1]) && (($coursetoolset&2)==0 || !isset($CFG['CPS']['topbar']) || $CFG['CPS']['topbar'][1]==1)) { //forums
		echo "<li><a {$a[6]} href=\"$imasroot/forums/forums.php?cid=$cid\">Forums</a></li>";
	}
	if (in_array(1,$coursetopbar[1])) { //Stu view
		echo "<li><a href=\"$imasroot/course/course.php?cid=$cid&stuview=0\">Student View</a></li>";
	}
	if (in_array(3,$coursetopbar[1])) { //List stu
		echo "<li><a {$a[3]} href=\"$imasroot/course/listusers.php?cid=$cid\">Roster</a></li>\n";
	}
	if (in_array(2,$coursetopbar[1])) { //Gradebook
		echo "<li><a {$a[2]} href=\"$imasroot/course/gradebook.php?cid=$cid\">Gradebook</a>$gbnewflag</li>";
	}
	if (in_array(7,$coursetopbar[1])) { //Groups
		echo "<li><a {$a[7]} href=\"$imasroot/course/managestugrps.php?cid=$cid\">Groups</a></li>\n";
	}
	if (in_array(4,$coursetopbar[1])  && (($coursetoolset&1)==0 || !isset($CFG['CPS']['topbar']) || $CFG['CPS']['topbar'][1]==1)) { //Calendar
		echo "<li><a {$a[4]} href=\"$imasroot/course/showcalendar.php?cid=$cid\">Calendar</a></li>\n";
	}
	if (in_array(5,$coursetopbar[1])) { //Quick view
		echo "<li><a {$a[5]} href=\"$imasroot/course/course.php?cid=$cid&quickview=on\">Quick View</a></li>\n";
	}
	
	if (in_array(9,$coursetopbar[1])) { //Log out
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
	if (in_array(0,$coursetopbar[0]) && $msgset<4) { //messages
		echo "<li><a {$a[0]} href=\"$imasroot/msgs/msglist.php?cid=$cid\">Messages</a></li> ";
	}
	if (in_array(3,$coursetopbar[0])) { //forums
		echo "<li><a {$a[3]} href=\"$imasroot/forums/forums.php?cid=$cid\">Forums</a></li> ";
	}
	if (in_array(1,$coursetopbar[0])) { //Gradebook
		echo "<li><a {$a[1]} href=\"$imasroot/course/gradebook.php?cid=$cid\">Gradebook</a></li> ";
	}
	if (in_array(2,$coursetopbar[0])) { //Calendar
		echo "<li><a {$a[2]} href=\"$imasroot/course/showcalendar.php?cid=$cid\">Calendar</a></li>\n";
	}
	if (in_array(9,$coursetopbar[0])) { //Log out
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
	echo '<div id="headerlogo" ';
	if ($myrights>10 && !$ispublic) {
		echo 'onclick="mopen(\'homemenu\',';
		if (isset($cid) && is_numeric($cid)) {
			echo $cid;
		} else {
			echo 0;
		}
		echo ')" onmouseout="mclosetime()"';
	}
	echo '>'.$smallheaderlogo.'</div>';
	if ($myrights>10 && !$ispublic) {
		echo '<div id="homemenu" class="ddmenu" onmouseover="mcancelclosetime()" onmouseout="mclosetime()">';
		echo '</div>';
	}
	
}


?>



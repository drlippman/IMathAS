<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title><?php echo $installname; if (isset($pagetitle)) { echo " - $pagetitle";}?></title>
<meta http-equiv="X-UA-Compatible" content="IE=7, IE=9" />
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<link rel="stylesheet" href="<?php echo $imasroot . "/imascore.css?ver=061010";?>" type="text/css" />
<?php if (isset($coursetheme)) { 
	if (isset($flexwidth)) {
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
var imasroot = '<?php echo $imasroot; ?>';
</script>
<script type="text/javascript" src="<?php echo $imasroot;?>/javascript/general.js?ver=022110"></script>
<?php
if (isset($coursetheme) && strpos($coursetheme,'_dark')!==false) {$mathdarkbg = true;} else {$mathdarkbg = false;}
if (isset($ispublic) && $ispublic) {
	echo "<script src=\"$imasroot/javascript/ASCIIMathMLwFallback.js?ver=082911\" type=\"text/javascript\"></script>\n";
	echo "<script src=\"$imasroot/javascript/ASCIIsvg_min.js?ver=111909\" type=\"text/javascript\"></script>\n";
	echo "<script type=\"text/javascript\">var usingASCIIMath = true; var usingASCIISvg = true;</script>"; 
	echo '<script type="text/javascript">var AMTcgiloc = "'.$mathimgurl.'";';
	if ($mathdarkbg) {echo 'var mathbg = "dark";';}
	echo '</script>'; 
	echo '<script type="text/javascript">var AScgiloc = "'.$imasroot.'/filter/graph/svgimg.php";</script>'; 
} else {
if (!isset($sessiondata['mathdisp'])) {
	echo '<script type="text/javascript">var AMnoMathML = true;var ASnoSVG = true;var AMisGecko = 0;var AMnoTeX = false;</script>';
	echo "<script src=\"$imasroot/javascript/mathgraphcheck.js?v=082911\" type=\"text/javascript\"></script>\n";
} else if ($sessiondata['mathdisp']==1) {
	echo "<script src=\"$imasroot/javascript/ASCIIMathML.js?v=101210\" type=\"text/javascript\"></script>\n";
	echo "<script type=\"text/javascript\">var usingASCIIMath = true;</script>";
} else if ($sessiondata['mathdisp']==2 && isset($useeditor) && $sessiondata['useed']==1) {
	//these scripts are used by the editor to make image-based math work in the editor
	echo '<script type="text/javascript">var AMTcgiloc = "'.$mathimgurl.'";';
	if ($mathdarkbg) {echo 'var mathbg = "dark";';}
	echo '</script>'; 
	echo "<script src=\"$imasroot/javascript/ASCIIMathTeXImg_min.js?ver=082911\" type=\"text/javascript\"></script>\n";
	echo "<script type=\"text/javascript\">var usingASCIIMath = false;</script>";
} else {
	echo "<script type=\"text/javascript\">var usingASCIIMath = false;</script>";
}
if (isset($sessiondata['graphdisp']) && $sessiondata['graphdisp']==1) {
	echo "<script src=\"$imasroot/javascript/ASCIIsvg.js?ver=111909\" type=\"text/javascript\"></script>\n";
	echo "<script type=\"text/javascript\">var usingASCIISvg = true;</script>";
	//echo "<script src=\"$imasroot/course/editor/plugins/AsciiSvg/ASCIIsvgAddon.js\" type=\"text/javascript\"></script>\n";
} else if (isset($sessiondata['graphdisp'])) {
	echo "<script src=\"$imasroot/javascript/mathjs.js\" type=\"text/javascript\"></script>\n";
	echo "<script type=\"text/javascript\">var usingASCIISvg = false;</script>";
}
}

$start_time = microtime(true); 
if (isset($placeinhead)) {
	echo $placeinhead;
}
if (isset($useeditor) && $sessiondata['useed']==1) {
	//cleanup_callback : "imascleanup",
echo <<<END
<script type="text/javascript" src="$imasroot/editor/tiny_mce.js?v=082911"></script>

<script type="text/javascript">
tinyMCE.init({
    mode : "exact",
    elements : "$useeditor",
    theme : "advanced",
    theme_advanced_buttons1 : "fontselect,fontsizeselect,formatselect,bold,italic,underline,strikethrough,separator,sub,sup,separator,cut,copy,paste,pasteword,undo,redo",
    theme_advanced_buttons2 : "justifyleft,justifycenter,justifyright,justifyfull,separator,numlist,bullist,outdent,indent,separator,forecolor,backcolor,separator,hr,anchor,link,unlink,charmap,image,table,code,separator,asciimath,asciimathcharmap,asciisvg",
    theme_advanced_buttons3 : "",
    theme_advanced_fonts : "Arial=arial,helvetica,sans-serif,Courier New=courier new,courier,monospace,Georgia=georgia,times new roman,times,serif,Tahoma=tahoma,arial,helvetica,sans-serif,Times=times new roman,times,serif,Verdana=verdana,arial,helvetica,sans-serif",
    theme_advanced_toolbar_location : "top",
    theme_advanced_toolbar_align : "left",
    theme_advanced_statusbar_location : "bottom",
    theme_advanced_source_editor_height: "500",
    plugins : 'asciimath,asciisvg,table,inlinepopups,paste,media',
    gecko_spellcheck : true,
    extended_valid_elements : 'iframe[src|width|height|name|align],param[name|value],@[sscr]',
    content_css : '$imasroot/themes/$coursetheme',
    popup_css_add : '$imasroot/themes/$coursetheme',
    theme_advanced_resizing : true,
    cleanup_callback : "imascleanup",
    AScgiloc : '$imasroot/filter/graph/svgimg.php',
    ASdloc : '$imasroot/javascript/d.svg'
END;
if (isset($AWSkey)) {
echo <<<END
    ,file_browser_callback : "fileBrowserCallBack"
});
function fileBrowserCallBack(field_name, url, type, win) {
	var connector = "$imasroot/editor/file_manager.php";
	my_field = field_name;
	my_win = win;
	switch (type) {
		case "image":
			connector += "?type=img";
			break;
		case "file":
			connector += "?type=files";
			break;
	}
	tinyMCE.activeEditor.windowManager.open({
		file : connector,
		title : 'File Manager',
		width : 350,  
		height : 450,
		resizable : "yes",
		inline : "yes",  
		close_previous : "no"
	    }, {
		window : win,
		input : field_name
	    });

	//window.open(connector, "file_manager", "modal,width=450,height=440,scrollbars=1");
}

END;
} else {
	echo "});";
}
echo <<<END
function imascleanup(type, value) {
	if (type=="get_from_editor") {
		//value = value.replace(/[\x84\x93\x94]/g,'"');
		//var rl = '\u2122,<sup>TM</sup>,\u2026,...,\u201c|\u201d,",\u2018|\u2019,\',\u2013|\u2014|\u2015|\u2212,-'.split(',');
		//for (var i=0; i<rl.length; i+=2) {
		//	value = value.replace(new RegExp(rl[i], 'gi'), rl[i+1]);
		//}
		value = value.replace(/<!--([\s\S]*?)-->|&lt;!--([\s\S]*?)--&gt;|<style>[\s\S]*?<\/style>/g, "");  // Word comments
		value = value.replace(/class="?Mso\w+"?/g,'');
		value = value.replace(/<p\s*>\s*<\\/p>/gi,'');
		value = value.replace(/<script.*?\/script>/gi,'');
		value = value.replace(/<input[^>]*button[^>]*>/gi,'');
	}
	return value;
}
</script>
<!-- /TinyMCE -->

</head>
<body>

END;

} else {
	echo "</head>\n";
	echo "<body>\n";
}

$insertinheaderwrapper = ' '; //"<h1>$coursename</h1>";
echo '<div class=mainbody>';
if (isset($insertinheaderwrapper)) {
	//echo '<div class="headerwrapper">'.$insertinheaderwrapper.'</div>';
}
echo '<div class="headerwrapper">';
$curdir = rtrim(dirname(__FILE__), '/\\');
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
	if (in_array(6,$coursetopbar[1])) { //Forums
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
	if (in_array(4,$coursetopbar[1])) { //Calendar
		echo "<li><a {$a[4]} href=\"$imasroot/course/showcalendar.php?cid=$cid\">Calendar</a></li>\n";
	}
	if (in_array(5,$coursetopbar[1])) { //Calendar
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
echo '</div>';
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



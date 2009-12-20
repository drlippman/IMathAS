<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<meta http-equiv="X-UA-Compatible" content="IE=7" />
<title><?php echo $installname; if (isset($pagetitle)) { echo " - $pagetitle";}?></title>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<link rel="stylesheet" href="<?php echo $imasroot . "/imascore.css?ver=101009";?>" type="text/css" />
<?php if (isset($coursetheme)) { ?>
<link rel="stylesheet" href="<?php echo $imasroot . "/themes/$coursetheme";?>" type="text/css" />
<?php } ?>
<link rel="shortcut icon" href="/favicon.ico" />
<style type="text/css" media="print">
div.breadcrumb { display:none;}
#headerlogo { display:none;}
</style>
<script type="text/javascript">
var imasroot = '<?php echo $imasroot; ?>';
</script>
<script type="text/javascript" src="<?php echo $imasroot;?>/javascript/general.js?ver=100509"></script>
<?php
if (isset($coursetheme) && strpos($coursetheme,'_dark')!==false) {$mathdarkbg = true;} else {$mathdarkbg = false;}
if ($ispublic) {
	echo "<script src=\"$imasroot/javascript/ASCIIMathMLwFallback.js?ver=102009\" type=\"text/javascript\"></script>\n";
	echo "<script src=\"$imasroot/javascript/ASCIIsvg_min.js?ver=111909\" type=\"text/javascript\"></script>\n";
	echo "<script type=\"text/javascript\">var usingASCIIMath = true; var usingASCIISvg = true;</script>"; 
	echo '<script type="text/javascript">var AMTcgiloc = "'.$mathimgurl.'";';
	if ($mathdarkbg) {echo 'var mathbg = "dark";';}
	echo '</script>'; 
	echo '<script type="text/javascript">var AScgiloc = "'.$imasroot.'/filter/graph/svgimg.php";</script>'; 
} else {
if (!isset($sessiondata['mathdisp'])) {
	echo '<script type="text/javascript">var AMnoMathML = true;var ASnoSVG = true;var AMisGecko = 0;var AMnoTeX = false;</script>';
	echo "<script src=\"$imasroot/javascript/mathgraphcheck.js\" type=\"text/javascript\"></script>\n";
}
if ($sessiondata['mathdisp']==1) {
	echo "<script src=\"$imasroot/javascript/ASCIIMathML_min.js\" type=\"text/javascript\"></script>\n";
	echo "<script type=\"text/javascript\">var usingASCIIMath = true;</script>";
} else if ($sessiondata['mathdisp']==2 && isset($useeditor) && $sessiondata['useed']==1) {
	//these scripts are used by the editor to make image-based math work in the editor
	echo '<script type="text/javascript">var AMTcgiloc = "'.$mathimgurl.'";';
	if ($mathdarkbg) {echo 'var mathbg = "dark";';}
	echo '</script>'; 
	echo "<script src=\"$imasroot/javascript/ASCIIMathTeXImg_min.js?ver=102009\" type=\"text/javascript\"></script>\n";
	echo "<script type=\"text/javascript\">var usingASCIIMath = false;</script>";
} else {
	echo "<script type=\"text/javascript\">var usingASCIIMath = false;</script>";
}
if ($sessiondata['graphdisp']==1) {
	echo "<script src=\"$imasroot/javascript/ASCIIsvg_min.js?ver=111909\" type=\"text/javascript\"></script>\n";
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
<script type="text/javascript" src="$imasroot/editor/tiny_mce.js"></script>

<script type="text/javascript">
tinyMCE.init({
    mode : "exact",
    elements : "$useeditor",
    theme : "advanced",
    theme_advanced_buttons1 : "fontselect,fontsizeselect,formatselect,bold,italic,underline,strikethrough,separator,sub,sup,separator,cut,copy,paste,pasteword,undo,redo",
    theme_advanced_buttons2 : "justifyleft,justifycenter,justifyright,justifyfull,separator,numlist,bullist,outdent,indent,separator,forecolor,backcolor,separator,hr,link,unlink,charmap,image,table,code,separator,asciimath,asciimathcharmap,asciisvg",
    theme_advanced_buttons3 : "",
    theme_advanced_fonts : "Arial=arial,helvetica,sans-serif,Courier New=courier new,courier,monospace,Georgia=georgia,times new roman,times,serif,Tahoma=tahoma,arial,helvetica,sans-serif,Times=times new roman,times,serif,Verdana=verdana,arial,helvetica,sans-serif",
    theme_advanced_toolbar_location : "top",
    theme_advanced_toolbar_align : "left",
    theme_advanced_statusbar_location : "bottom",
    plugins : 'safari,asciimath,asciisvg,table,inlinepopups,paste,media',
    gecko_spellcheck : true,
    extended_valid_elements : 'iframe[src|width|height|name|align],param[name|value],@[sscr]',
    content_css : '$imasroot/themes/$coursetheme',
    popup_css_add : '$imasroot/themes/$coursetheme',
    theme_advanced_resizing : true,
    cleanup_callback : "imascleanup",
    content_css : '$imasroot/themes/$coursetheme',
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

//load filter
$curdir = rtrim(dirname(__FILE__), '/\\');
require_once("$curdir/filter/filter.php");

//CUSTOMIZE:  put a small (max 120px wide) logo on upper right of course pages
if (!isset($nologo)) {
	//echo '<img id="headerlogo" style="position: absolute; right: 5px; top: 5px;" src="/img/state_logo.gif" alt="logo"/>';
	//echo '<img id="headerlogo" style="position: absolute; right: 5px; top: 12px;" src="/img/wamaplogosmall.gif" alt="logo"/>';
	echo '<span id="headerlogo" ';
	if ($myrights>10 && !$ispublic) {
		echo 'onclick="mopen(\'homemenu\',';
		if (isset($cid) && is_numeric($cid)) {
			echo $cid;
		} else {
			echo 0;
		}
		echo ')" onmouseout="mclosetime()"';
	}
	echo '>'.$smallheaderlogo.'</span>';
	if ($myrights>10 && !$ispublic) {
		echo '<div id="homemenu" class="ddmenu" onmouseover="mcancelclosetime()" onmouseout="mclosetime()">';
		/*<b>Switch to:</b><ul class="nomark">';
		$query = "SELECT imas_courses.name,imas_courses.id FROM imas_teachers,imas_courses ";
		$query .= "WHERE imas_teachers.courseid=imas_courses.id AND imas_teachers.userid='$userid' ";
		$query .= "AND (imas_courses.available=0 OR imas_courses.available=1) ";
		$query .= "UNION SELECT imas_courses.name,imas_courses.id FROM imas_students,imas_courses ";
		$query .= "WHERE imas_students.courseid=imas_courses.id AND imas_students.userid='$userid' ";
		$query .= "AND (imas_courses.available=0 OR imas_courses.available=2) ";
		$query .= "ORDER BY name";
		$result = mysql_query($query) or die("Query failed : " . mysql_error());
		while ($row = mysql_fetch_row($result)) {
			echo "<li><a href=\"$imasroot/course/course.php?cid={$row[1]}\">{$row[0]}</a></li>";
		}
		echo "<li><a href=\"$imasroot/actions.php?action=logout\">Log Out</a></li>";
		echo '</ul></div>';
		*/
		echo '</div>';
	}
}

$insertinheaderwrapper = ' ';
echo '<div class=mainbody>';
if (isset($insertinheaderwrapper)) {
	echo '<div class="headerwrapper">'.$insertinheaderwrapper.'</div>';
}
echo '<div class="midwrapper">';

?>



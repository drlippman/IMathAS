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
<meta http-equiv="X-UA-Compatible" content="IE=7, IE=Edge" />
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
<link rel="stylesheet" href="<?php echo $staticroot . "/imascore.css?ver=071025";?>" type="text/css" />
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
var staticroot = '<?php echo $staticroot; ?>';
<?php if (!empty($CFG['nocommathousandsseparator'])) { echo 'var commasep = false;'; } ?>
</script>
<script type="text/javascript" src="<?php echo $staticroot;?>/javascript/general.js?v=071125"></script>
<?php
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

if (!isset($_SESSION['mathdisp'])) {
	echo '<script type="text/javascript">var AMnoMathML = true;var ASnoSVG = true;var AMisGecko = 0;var AMnoTeX = false;var mathRenderer="none";</script>';
} else if ($_SESSION['mathdisp']==1 || $_SESSION['mathdisp']==3) {
	//merged, eliminating original AsciiMath display; MathJax only now
	if (isset($useeditor) && $_SESSION['useed']==1) {
		echo '<script type="text/javascript">var AMTcgiloc = "'.$mathimgurl.'";</script>';
		echo "<script src=\"$staticroot/javascript/ASCIIMathTeXImg_min.js?ver=111923\" type=\"text/javascript\"></script>\n";
    }
    if (isset($_SESSION['ltiitemtype'])) {
        echo '<script type="text/x-mathjax-config">
            MathJax.Hub.Queue(function () {
                sendLTIresizemsg();
            });
            MathJax.Hub.Register.MessageHook("End Process", sendLTIresizemsg);
            </script>';
    }
    //Contrib not hosted in CDN yet
	echo '<script type="text/x-mathjax-config">
        MathJax.Hub.Config({"messageStyle": "none", asciimath2jax: {ignoreClass:"skipmathrender"}});
        MathJax.Ajax.config.path["Local"] = "'.$staticroot.'/javascript/mathjax";
        MathJax.Hub.config.extensions.push("[Local]/InputToDataAttrCDN.js");
        MathJax.Hub.Register.StartupHook("AsciiMath Jax Ready", function () {
            var AM = MathJax.InputJax.AsciiMath.AM;
            AM.newsymbol({input: "o-", tag:"mo", output:"\u2296", ttype:AM.TOKEN.CONST});
            AM.newsymbol({input: "ominus", tag:"mo", output:"\u2296", ttype:AM.TOKEN.CONST});
            AM.newsymbol({input: "rightleftharpoons", tag:"mo", output:"\u21CC", ttype:AM.TOKEN.CONST});
            AM.newsymbol({input: "hbar", tag:"mi", output:"\u210F", ttype:AM.TOKEN.CONST});
            ["arcsec","arccsc","arccot"].forEach(function(v) {
                AM.newsymbol({input:v, tag:"mi", output:v, ttype:AM.TOKEN.UNARY, func:true});
            });
        });
        </script>';
    if (!empty($CFG['GEN']['uselocaljs'])) {
        echo '<script type="text/javascript" async src="'.$staticroot.'/mathjax/MathJax.js?config=AM_CHTML-full"></script>';
    } else {
		echo '<script type="text/javascript" async src="https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.9/MathJax.js?config=AM_CHTML-full"></script>';
    }
    echo '<script type="text/javascript">noMathRender = false; var usingASCIIMath = true; var AMnoMathML = true; var MathJaxCompatible = true;var mathRenderer="MathJax";
        function rendermathnode(node,callback) {
            if (window.MathJax) {
                MathJax.Hub.Queue(["Typeset", MathJax.Hub, node]);
                if (typeof callback == "function") {
                    MathJax.Hub.Queue(callback);
                }
            } else {
                setTimeout(function() {rendermathnode(node, callback);}, 100);
            }
        }</script>';

    echo '<style type="text/css">span.AM { font-size: 105%;} .mq-editable-field.mq-math-mode var { font-style: normal;}</style>';
} else if ($_SESSION['mathdisp']==7 || $_SESSION['mathdisp']==8) { // mathjax 3
    if (isset($useeditor) && $_SESSION['useed']==1) {
		echo '<script type="text/javascript">var AMTcgiloc = "'.$mathimgurl.'";</script>';
		echo "<script src=\"$staticroot/javascript/ASCIIMathTeXImg_min.js?ver=111923\" type=\"text/javascript\"></script>\n";
    }
    // for autoload of a11y extension, add "a11y/semantic-enrich" to load, and put in options enrichSpeech: "shallow",
    echo '<script>
    window.MathJax = {
      loader: {
        load: ["input/asciimath", "output/chtml", "ui/menu"'.
            (($_SESSION['mathdisp']==8) ? ',"a11y/semantic-enrich"' : '')
        .']
      },
      options: {
        ignoreHtmlClass: "skipmathrender",'.
        (($_SESSION['mathdisp']==8) ? 'sre:{speech:"shallow"},' : '')
        .'renderActions: {
            addattr: [150,
                function (doc) {for (const math of doc.math) {MathJax.config.addDataAttr(math, doc)}},
                function (math, doc) {MathJax.config.addDataAttr(math, doc)}
            ]
        }
      },
      addDataAttr: function (math, doc) {
        math.typesetRoot.setAttribute("data-asciimath", math.math);
      },
      startup: {
          ready: function() {
            var AM = MathJax.InputJax.AsciiMath.AM;
            AM.newsymbol({input: "o-", tag:"mo", output:"\u2296", ttype:AM.TOKEN.CONST});
            AM.newsymbol({input: "ominus", tag:"mo", output:"\u2296", ttype:AM.TOKEN.CONST});
            AM.newsymbol({input: "rightleftharpoons", tag:"mo", output:"\u21CC", ttype:AM.TOKEN.CONST});
            AM.newsymbol({input: "hbar", tag:"mi", output:"\u210F", ttype:AM.TOKEN.CONST});
            ["arcsec","arccsc","arccot"].forEach(function(v) {
                AM.newsymbol({input:v, tag:"mi", output:v, ttype:AM.TOKEN.UNARY, func:true});
            });
            MathJax.startup.defaultReady();
          }
       }
    };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/startup.js" id="MathJax-script"></script>';
    echo '<script type="text/javascript">noMathRender = false; var usingASCIIMath = true; var AMnoMathML = true; var MathJaxCompatible = true;var mathRenderer="MathJax";
		function rendermathnode(node,callback) {
			if (window.MathJax && window.MathJax.typesetPromise) {
                if (typeof callback != "function") {
					callback = function () {};
				}
                MathJax.typesetClear([node]);
                MathJax.typesetPromise([node]).then(sendLTIresizemsg).then(callback);
			} else {
				setTimeout(function() {rendermathnode(node, callback);}, 100);
			}
        }</script>';
    if (isset($_SESSION['ltiitemtype'])) {
        echo '<script type="text/javascript">
            MathJax.startup.promise = MathJax.startup.promise.then(sendLTIresizemsg);
            </script>';
    }
	echo '<style type="text/css">span.AM { font-size: 105%;} </style>';
} else if ($_SESSION['mathdisp']==6) {
	//Katex experimental
	echo '<script type="text/javascript">var AMTcgiloc = "'.$mathimgurl.'";</script>';
	echo "<script src=\"$staticroot/javascript/ASCIIMathTeXImg_min.js?ver=111923\" type=\"text/javascript\"></script>\n";

	if (!empty($CFG['GEN']['uselocaljs'])) {
		echo '<script src="'.$staticroot.'/katex/katex.min.js"></script>';
		echo '<link rel="stylesheet" href="'.$staticroot.'/katex/katex.min.css" />';
		//echo '<script type="text/javascript" async src="'.$imasroot.'/mathjax/MathJax.js?config=AM_CHTML-full"></script>';
	} else {
		echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.0/dist/katex.css" integrity="sha256-tVkQ3/3zHAr/iep04F8LJwgzNzJ2NzX8oHqg71BvzYU=" crossorigin="anonymous">';
		echo '<script src="https://cdn.jsdelivr.net/npm/katex@0.16.0/dist/katex.min.js" integrity="sha256-6xggdIcWFnTnFwh8MX2xSsGmLa2uzMuAJJnOFzv+tzk=" crossorigin="anonymous"></script>';
		//echo '<script type="text/javascript" async src="https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.9/MathJax.js?config=AM_CHTML-full"></script>';
	}
	echo '<script type="text/javascript" src="'.$staticroot.'/katex/auto-render.js?v=073119"></script>';
	echo '<script type="text/javascript">setupKatexAutoRender();</script>';
	echo '<script type="text/javascript">noMathRender = false; var usingASCIIMath = true; var AMnoMathML = true; var MathJaxCompatible = true; var mathRenderer = "Katex";</script>';
	//echo '<style type="text/css">span.AM { font-size: 105%;}</style>';
} else if ($_SESSION['mathdisp']==2 && isset($useeditor) && $_SESSION['useed']==1) {
	//these scripts are used by the editor to make image-based math work in the editor
	echo '<script type="text/javascript">var AMTcgiloc = "'.$mathimgurl.'";';
	if ($mathdarkbg) {echo 'var mathbg = "dark";';}
	echo '</script>';
	echo "<script src=\"$staticroot/javascript/ASCIIMathTeXImg_min.js?ver=111923\" type=\"text/javascript\"></script>\n";
	echo "<script type=\"text/javascript\">var usingASCIIMath = false; var AMnoMathML=true; var MathJaxCompatible = false; var mathRenderer=\"Image\"; function rendermathnode(el,callback) {AMprocessNode(el); if(typeof callback=='function'){callback();}}</script>";
} else if ($_SESSION['mathdisp']==2) {
	echo "<script type=\"text/javascript\">var usingASCIIMath = false; var AMnoMathML=true; var MathJaxCompatible = false; var mathRenderer=\"Image\";function rendermathnode(el,callback) {AMprocessNode(el);} if(typeof callback=='function'){callback();}</script>";
} else if ($_SESSION['mathdisp']==0) {
	echo "<script type=\"text/javascript\">var usingASCIIMath = false; var AMnoMathML=true; var MathJaxCompatible = false; var mathRenderer=\"none\";function rendermathnode(el,callback) {if(typeof callback=='function'){callback();}}</script>";
}
echo "<script src=\"$staticroot/javascript/mathjs_min.js?v=20250308\" type=\"text/javascript\"></script>\n";
if (isset($_SESSION['graphdisp']) && $_SESSION['graphdisp']==1) {
	echo "<script src=\"$staticroot/javascript/ASCIIsvg_min.js?ver=032925\" type=\"text/javascript\"></script>\n";
	echo "<script type=\"text/javascript\">var usingASCIISvg = true;</script>";
	//echo "<script src=\"$imasroot/course/editor/plugins/AsciiSvg/ASCIIsvgAddon.js\" type=\"text/javascript\"></script>\n";
} else if (isset($_SESSION['graphdisp'])) {
	echo "<script type=\"text/javascript\">var usingASCIISvg = false; var ASnoSVG=true;</script>";
}


if (isset($useeditor) && $_SESSION['useed']==1) {
    echo '<script type="text/javascript" src="'.$staticroot.'/tinymce4/tinymce_bundled.min.js?v=021324"></script>';
    //echo '<script type="text/javascript" src="'.$imasroot.'/tinymce4/tinymce.js?v=062821"></script>';

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
if ((isset($useeditor) && $_SESSION['useed']==1) || isset($loadiconfont)) {
	echo '<link rel="stylesheet" href="'.$staticroot . '/iconfonts/imathasfont.css?v=013118" type="text/css" />';
	echo '<!--[if lte IE 7]><link rel="stylesheet" href="'.$staticroot . '/iconfonts/imathasfontie7.css?v=013118" type="text/css" /><![endif]-->';
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
	echo '<link rel="stylesheet" href="'. $staticroot . "/themes/$coursetheme?v=042217\" type=\"text/css\" />";
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

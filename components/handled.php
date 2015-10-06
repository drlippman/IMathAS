

    <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php

    $start_time = microtime(true);
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
        var imasroot = '<?php echo \app\components\AppUtility::getHomeURL(); ?>'; var cid = <?php echo (isset($cid) && is_numeric($cid))?$cid:0; ?>;
    </script>
    <?php  ?>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js" type="text/javascript"></script>
    <?php

    if (isset($sessiondata['coursetheme'])) {
        if (isset($flexwidth) || isset($usefullwidth)) {
            $coursetheme = str_replace('_fw','',$sessiondata['coursetheme']);
        } else {
            $coursetheme = $sessiondata['coursetheme'];
        }
        echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"$imasroot/themes/$coursetheme\"/>\n";
    }
    echo '<link rel="stylesheet" href="'.$imasroot.'css/handheld.css?ver=12" media="handheld,only screen and (max-device-width:480px)"/>';
    if ($isdiag) {
        echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"$imasroot"."diag/print.css\" media=\"print\"/>\n";
    } else {
        echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"$imasroot"."css/print.css\" media=\"print\"/>\n";
    }
    if (!isset($sessiondata['mathdisp'])) {
        echo '<script type="text/javascript">var AMnoMathML = true;var ASnoSVG = true;var AMisGecko = 0;var AMnoTeX = false; function rendermathnode(el) {AMprocessNode(el);}</script>';
        echo '<script type="text/javascript" src="'.$imasroot.'js/mathjax/MathJax.js?config=AM_HTMLorMML"></script>';
        echo "<script src=\"$imasroot"."js/mathgraphcheck.js?v=021215\" type=\"text/javascript\"></script>\n";
    } else if ($sessiondata['mathdisp']==1 || $sessiondata['mathdisp']==3) {
        echo '<script type="text/javascript">var AMTcgiloc = "'.$mathimgurl.'";</script>';
        echo "<script src=\"$imasroot"."js/ASCIIMathTeXImg_min.js?ver=092314\" type=\"text/javascript\"></script>\n";
        echo '<script type="text/x-mathjax-config">
		if (MathJax.Hub.Browser.isChrome || MathJax.Hub.Browser.isSafari) {
			MathJax.Hub.Config({"HTML-CSS": {preferredFont: "STIX", imageFont:null}});
		} else {
			MathJax.Hub.Config({"HTML-CSS": {preferredFont: "STIX", webFont: "STIX-Web", imageFont:null}});
		}
		</script>';
        //webFont: "STIX-Web",
        echo '<script type="text/javascript" src="'.$imasroot.'js/mathjax/MathJax.js?config=AM_HTMLorMML"></script>';
        echo '<script type="text/javascript">noMathRender = false; var usingASCIIMath = true; var AMnoMathML = false; var MathJaxCompatible = true; function rendermathnode(node) { MathJax.Hub.Queue(["Typeset", MathJax.Hub, node]); } </script>';
        echo '<style type="text/css">span.MathJax { font-size: 105%;}</style>';
    } else if ($sessiondata['mathdisp']==2) {
        echo '<script type="text/javascript">var AMTcgiloc = "'.$mathimgurl.'";</script>';
        echo "<script src=\"$imasroot"."js/ASCIIMathTeXImg_min.js?v=092314\" type=\"text/javascript\"></script>\n";
        echo "<script type=\"javascript\">var usingASCIIMath = false;var MathJaxCompatible = false;function rendermathnode(el) {AMprocessNode(el);}</script>";
    } else if ($sessiondata['mathdisp']==0) {
        echo '<script type="javascript">var noMathRender = true; var usingASCIIMath = false; var MathJaxCompatible = false; function rendermathnode(el) {}</script>';
    }

    if ($sessiondata['graphdisp']==1) {
        echo "<script src=\"$imasroot"."js/ASCIIsvg_min.js?v=121514\" type=\"text/javascript\"></script>\n";
        echo "<script type=\"text/javascript\">var usingASCIISvg = true;</script>";
    } else {
        echo "<script src=\"$imasroot"."js/mathjs.js?v=101314\" type=\"text/javascript\"></script>\n";
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
    <script type="text/javascript" src="<?php echo $imasroot;?>js/excanvas.js?v=120811"></script>
    <script type="text/javascript" src="<?php echo $imasroot;?>js/drawing_min.js?v=060914"></script>
    <?php
    echo "<script type=\"text/javascript\">imasroot = '$imasroot';</script>";
    if (isset($useeditor) && $sessiondata['useed']==1) {
        echo '<script type="text/javascript" src="'.$imasroot.'/editor/tiny_mce.js?v=111612"></script>';
        echo "\n";
        echo '<script type="text/javascript">';
        echo 'var usingTinymceEditor = true;';
        if (isset($sessiondata['coursetheme'])) {
            echo 'var coursetheme = "'.$sessiondata['coursetheme'].'";';
        } else {
            echo 'var coursetheme = "'.$coursetheme.'";';
        }
        if (!isset($CFG['GEN']['noFileBrowser'])) {
            echo 'var fileBrowserCallBackFunc = "fileBrowserCallBack";';
        } else {
            echo 'var fileBrowserCallBackFunc = null;';
        }
        echo 'initeditor("textareas","mceEditor");';
        echo '</script>';
    } else {
        echo '<script type="text/javascript">var usingTinymceEditor = false;</script>';
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
    ?>

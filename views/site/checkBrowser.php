<?php

use app\components\AppUtility;
use app\components\AppConstant;

$this->title = 'Check Browser';
$this->params['breadcrumbs'][] = $this->title;

$sessionData['mathdisp'] = 1;
$sessionData['graphdisp'] = 1;
$nologo = true;
?>
<div class="item-detail-header">
    <?php echo $this->render("../itemHeader/_indexWithLeftContent", ['link_title' => [AppUtility::t('Home', false)], 'link_url' => [AppUtility::getHomeURL() . 'site/index']]); ?>
</div>
<div class = "title-container">
    <div class="row">
        <div class="pull-left page-heading">
            <div class="vertical-align title-page"><?php echo $this->title ?></div>
        </div>
    </div>
</div>
<div class="tab-content shadowBox non-nav-tab-item padding-top-nine">
    <div style="margin-right:30px; margin-left: 30px;margin-bottom: 30px; background-color: #fafafa">
        <div style="padding-right: 20px; padding-left: 20px; padding-bottom: 20px;">
            <h2 style="padding-top:15px"><?php echo AppConstant::INSTALL_NAME; ?><?php AppUtility::t('Browser Check')?></h2>
            <p><?php echo AppConstant::INSTALL_NAME; ?><?php AppUtility::t('requires JavaScript. Visual Math and Graph display is
                supported both through image-based display,
                which requires no setup, or browser-based display. Browser-based display is faster, more accurate, and prettier
                than image-based display. The rest of this page will guide you through setting up browser-based Math and Graph
                display.')?></p>

            <p>For browser-based Math and Graph display, <?php echo AppConstant::INSTALL_NAME; ?> recommends:<br/>
                Windows: Internet Explorer 6 or higher + MathPlayer + AdobeSVG, or Internet Explorer 9 + MathPlayer, or <a
                    href="http://www.mozilla.com/firefox/">FireFox 1.5 or higher</a><br/>
                Mac: <a href="http://www.mozilla.com/firefox/">FireFox 1.5 or higher</a>.</p>
            <p>The rest of this page will guide you through setting up browser-based Math and Graph display.</p>

            <p>Quick Plugin install for Windows Internet Explorer 6 or higher:
                <a href="http://www.dessci.com/en/dl/MathPlayerSetup.asp">MathPlayer</a>,
                <a href="http://download.adobe.com/pub/adobe/magic/svgviewer/win/3.x/3.03/en/SVGView.exe">AdobeSVGViewer</a></p>

            <h3><?php AppUtility::t('Math Display')?></h3>
            <p>A formula: `sqrt(x^2-3)/5`</p>
            <p>You should see a mathematical formula displayed above, like you'd see in a textbook. If the
                formula looks like \`sqrt(x^2-3)/5\`, you need to install
                MathML support. If you're using Internet Explorer,
                <A HREF="http://www.dessci.com/en/products/mathplayer/download.htm">download MathPlayer</a>.
                If you're using FireFox and the formula doesn't display right, you may need to
                <A HREF="http://www.mozilla.org/projects/mathml/fonts/">download Math fonts</a>.</p>

            <h3>Graph Display</h3>
            <embed src="<?php echo Yii::$app->homeUrl; ?>js/d.svg" width="200" height="200"
                   script='setBorder(0);initPicture(-5,5,-5,5);axes(1,1,1,1,1);plot("x^2",-5,5);'/>
            <p>You should see a graph of `y=x^2` shown above. If you do not see the graph and just see a box,
                you need to install SVG support. If you're using Internet Explorer,
                <A HREF="http://www.adobe.com/svg/viewer/install/main.html">download Adobe SVG Viewer</a></p>

            <h3>JavaScript</h3>
            <div id="javas">JavaScript is not enabled in your browser. You need to enabled
                JavaScript support. Provided you have a modern
                browser like Internet Explorer 6+ or FireFox 1.5+, you should be able to enable JavaScript
                by changing your browser options.
            </div>
            <script>
                jsdiv = document.getElementById("javas");
                var n = jsdiv.childNodes.length;
                for (var i = 0; i < n; i++)
                    jsdiv.removeChild(jsdiv.firstChild);
                jsdiv.appendChild(document.createTextNode("JavaScript is enabled in your browser.  Nothing to do here."));
            </script>

            <h3>Is it working?</h3>
            When everything is working, <A href="<?php echo AppUtility::getURLFromHome('site', 'login') ?>">Return to Login Page</a>
    </div>
  </div>
</div>
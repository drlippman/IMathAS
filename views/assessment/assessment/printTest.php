<?php
use yii\helpers\Html;
use app\components\AppUtility;
?>
<script type="text/javascript">var AMTcgiloc = "http://www.imathas.com/cgi-bin/mimetex.cgi";</script>
<script src="<?php echo AppUtility::getHomeURL() ?>js/ASCIIMathTeXImg_min.js?ver=092314\" type=\"text/javascript\"></script>

<script type="text/x-mathjax-config">
if (MathJax.Hub.Browser.isChrome || MathJax.Hub.Browser.isSafari) {
MathJax.Hub.Config({"HTML-CSS": {preferredFont: "STIX", imageFont:null}});
} else {
MathJax.Hub.Config({"HTML-CSS": {preferredFont: "STIX", webFont: "STIX-Web", imageFont:null}});
}
</script>
<script type="text/javascript" src="<?php echo AppUtility::getHomeURL() ?>js/mathjax/MathJax.js?config=AM_HTMLorMML"></script>
<script type="text/javascript">noMathRender = false; var usingASCIIMath = true; var AMnoMathML = false; var MathJaxCompatible = true; function rendermathnode(node) { MathJax.Hub.Queue(["Typeset", MathJax.Hub, node]); } </script>
<script type="text/javascript" charset="utf8" src="<?php echo AppUtility::getHomeURL() ?>js/confirmsubmit.js"></script>
<script type="text/javascript" charset="utf8" src="<?php echo AppUtility::getHomeURL() ?>js/AMhelpers.js"></script>
<script type="text/javascript" charset="utf8" src="<?php echo AppUtility::getHomeURL() ?>js/drawing.js"></script>
<style type="text/css">span.MathJax { font-size: 105%;}</style>

<?php print_r($response); ?>


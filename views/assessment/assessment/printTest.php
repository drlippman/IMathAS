<?php
use yii\helpers\Html;
use app\components\AppUtility;
?>
<script type="text/javascript">var AMTcgiloc = "http://www.imathas.com/cgi-bin/mimetex.cgi";</script>
<?php
AppUtility::includeJS('ASCIIMathTeXImg_min.js');
?>

<script type="text/x-mathjax-config">
if (MathJax.Hub.Browser.isChrome || MathJax.Hub.Browser.isSafari) {
MathJax.Hub.Config({"HTML-CSS": {preferredFont: "STIX", imageFont:null}});
} else {
MathJax.Hub.Config({"HTML-CSS": {preferredFont: "STIX", webFont: "STIX-Web", imageFont:null}});
}
</script>
<script type="text/javascript">noMathRender = false; var usingASCIIMath = true; var AMnoMathML = false; var MathJaxCompatible = true; function rendermathnode(node) { MathJax.Hub.Queue(["Typeset", MathJax.Hub, node]); } </script>
<script type="text/javascript" src="<?php echo AppUtility::getHomeURL() ?>js/mathjax/MathJax.js?config=AM_HTMLorMML"></script>

<?php
AppUtility::includeJS('confirmsubmit.js');
AppUtility::includeJS('AMhelpers.js');
AppUtility::includeJS('drawing.js');
?>
<style type="text/css">span.MathJax { font-size: 105%;}</style>

<?php print_r($response); ?>


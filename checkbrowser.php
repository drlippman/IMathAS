<?php
	require("init_without_validate.php");
	$sessiondata['mathdisp']=1;
	$sessiondata['graphdisp']=1;
	$nologo = true;
	require("header.php");
	
?>

<h1><?php echo $installname;?> Browser Check</h1>
<p><?php echo $installname;?> requires JavaScript.  Visual Math and Graph display is supported both through image-based display,
which requires no setup, or browser-based display.  Browser-based display is faster, more accurate, and prettier
than image-based display.  The rest of this page will guide you through setting up browser-based Math and Graph display.</p>

<p>For browser-based Math and Graph display, <?php echo $installname;?> recommends:<br/>
Windows: Internet Explorer 6 or higher + MathPlayer + AdobeSVG, or Internet Explorer 9 + MathPlayer, or <a href="http://www.mozilla.com/firefox/">FireFox 1.5 or higher</a><br/>
Mac: <a href="http://www.mozilla.com/firefox/">FireFox 1.5 or higher</a>.</p>
<p>The rest of this page will guide you through setting up browser-based Math and Graph display.</p>

<p>Quick Plugin install for Windows Internet Explorer 6 or higher: 
<a href="http://www.dessci.com/en/dl/MathPlayerSetup.asp">MathPlayer</a>, 
<a href="http://download.adobe.com/pub/adobe/magic/svgviewer/win/3.x/3.03/en/SVGView.exe">AdobeSVGViewer</a></p>

<h2>Math Display</h2>
<p>A formula: `sqrt(x^2-3)/5`</p>
<p>You should see a mathematical formula displayed above, like you'd see in a textbook.  If the
formula looks like \`sqrt(x^2-3)/5\`, you need to install
MathML support.  If you're using Internet Explorer, 
<A HREF="http://www.dessci.com/en/products/mathplayer/download.htm">download MathPlayer</a>.
If you're using FireFox and the formula doesn't display right, you may need to 
<A HREF="http://www.mozilla.org/projects/mathml/fonts/">download Math fonts</a>.</p>

<h2>Graph Display</h2>
<embed src="<?php echo $imasroot;?>/javascript/d.svg" width="200" height="200" script='setBorder(0);initPicture(-5,5,-5,5);axes(1,1,1,1,1);plot("x^2",-5,5);' />
<p>You should see a graph of `y=x^2` shown above.  If you do not see the graph and just see a box,
you need to install SVG support.  If you're using Internet Explorer,
<A HREF="http://www.adobe.com/svg/viewer/install/main.html">download Adobe SVG Viewer</a></p>

<h2>JavaScript</h2>
<div id="javas">JavaScript is not enabled in your browser.  You need to enabled
JavaScript support.  Provided you have a modern
browser like Internet Explorer 6+ or FireFox 1.5+, you should be able to enable JavaScript
by changing your browser options.</div>
<script>
jsdiv = document.getElementById("javas"); 
var n = jsdiv.childNodes.length;
for (var i=0; i<n; i++)
    jsdiv.removeChild(jsdiv.firstChild);
jsdiv.appendChild(document.createTextNode("JavaScript is enabled in your browser.  Nothing to do here."));
</script>

<h2>Is it working?</h2>
When everything is working, <A href="index.php">Return to Login Page</a>
<?php
	require("footer.php");
?>

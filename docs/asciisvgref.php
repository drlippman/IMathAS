<?php
require '../init_without_validate.php';

$pagetitle = "ASCIIsvg Reference";
require '../header.php';

?>
<h1>ASCIIsvg Reference</h1>
<p>ASCIIsvg is a programatic drawing language, allowing you to create randomized basic drawings and illustrations.</p>
<p>When using the <strong>showasciisvg</strong> macro when writing a question the format is</p>
<p><span style="font-family: courier new,courier,monospace;">$pic = showasciisvg(asciisvgcode,[width,height,alttext])</span></p>
<p>Here asciisvgcode is a string, enclosed in quotes, using the following commands, separated with semicolons.&nbsp; Beware that since this string is enclosed in quotes, any quotes needed in the string either need to be 'single quotes' or escaped using backlashes like \"this\"</p>

<h2>Command Reference</h2>
<p>optional parameters are shown in {curly brackets}. These can be left out if not needed.</p>
<p><strong>setBorder(border)</strong> or <strong>setBorder(left,bottom,right,top)</strong>:&nbsp; Sets the border, in pixels, around the main drawing window.&nbsp; Text can spill into the border, which is why one is often used.&nbsp; This should be used before calling initPicture.</p>
<p><strong>initPicture(xmin,xmax,{ymin,ymax})</strong>:&nbsp; Initializes the drawing window.</p>
<p><strong>axes(xtick,ytick,{labels,xgrid,ygrid,dox,doy})</strong>:&nbsp; Draws axes.&nbsp; xtick and ytick are numbers giving the spacing of tick marks and labels on the x and y axes respectively.&nbsp; Set <em>labels</em> to 0 to turn off axes labels, 1 to turn them on.&nbsp; xgrid and ygrid can set the spacing of x and y grid lines; set to 0 to turn off.&nbsp; dox and doy can be set to 0 to turn off tick marks and labels for the corresponding axis.</p>
<p><strong>plot("f(x)",{xmin,xmax,steps})</strong>:&nbsp; Plot function f(x).&nbsp;&nbsp; Can specify limited domain and number of steps if desired.<br><strong>plot(["x(t)","y(t)"],{tmin,tmax,steps})</strong>:&nbsp; Plot parametric curve<br><strong>slopefield("dy/dx",{xstep,ystep})</strong>:&nbsp; Plot a slopefield/direction field.&nbsp; <em>xstep</em> and <em>ystep</em> are spacing of slopes.<br><br><strong>line([x1,y1],[x2,y2])</strong>:&nbsp; draw line from point [x1,y1] to [x2,y2]<br><strong>path([[x1,y1],...,[xn,yn]])</strong>:&nbsp; draws line segments connecting the list of points<br><strong>circle([x1,y1],rad)</strong>:&nbsp; draws a circle centered at [x1,y1] with radius <em>rad</em><br><strong>ellipse([x1,y1],xrad,yrad)</strong>:&nbsp; draws an ellipse with given center and radius in the x and y directions<br><strong>arc([x1,y1],[x2,y2],rad)</strong>:&nbsp; draws a circular arc counter-clockwise from [x1,y1] to [x2,y2] with radius <em>rad</em><br><strong>sector([x,y],radius,angle_start,angle_end)</strong>: Draws a sector<strong><br>rect([x1,y1],[x2,y2])</strong>:&nbsp; draws a rectangle with given diagonally opposite corner points<br><strong>dot([x1,y1],{type,label,pos})</strong>:&nbsp;&nbsp; draws a dot at the given point.&nbsp; <em>type</em> can be used to set the type: open or closed (default).&nbsp; Optionally, a label for the dot can be provided, and the position for that label can be specified.</p>
<p><strong>text([x1,y1],"string",{pos,angle})</strong>: &nbsp; draws the basic text string at given point.&nbsp; No html or typeset math.&nbsp; By default, center of text is placed at given point;&nbsp; <em>pos</em> can be used to change. pos should be a string like left,right,above,below,aboveleft,etc.&nbsp; <em>angle</em>, in degrees, can be used to rotate the text.<br><strong>textabs([pixelx,pixely],"string",{pos,angle})</strong>:&nbsp; same as text function, but coordinates are pixel locations rather than coordinate system locations.</p>
<h3>Settings</h3>
<p><strong>stroke = "color"</strong>:&nbsp; sets line color:&nbsp; white, black, gray, red, orange, yellow, green, blue, cyan, purple<br><strong>fill = "color"</strong>:&nbsp; sets the fill for rectangles, circles, etc. &nbsp;Same colors as above plus transred, transblue, transgreen for translucent colors.<br><strong>fontfill = "color"</strong> :&nbsp; sets the text color<br><strong>fontbackground = "color"</strong> : background color for text<br><strong>strokewidth=width</strong>:&nbsp; sets line thickness<br><strong>strokedasharray="array</strong>" :&nbsp; dash array, ie "5 3" for 5 pixels color, 3 pixels white<br><strong>marker = "marker" </strong>:&nbsp; turns on marks for the end of line segments.&nbsp; marker: "dot", "arrow", "arrowdot" or "none"</p>

<h2>Example</h2>
<p><code>$pic = showasciisvg("setBorder(20);initPicture(-5,5,0,30); axes(1,5,1); plot('x^2'); marker='arrow'; line([1,1],[4,4]);")</code></p>
<p>Additional examples can be found in the "Examples" question library.</p>

<?php

require '../footer.php';
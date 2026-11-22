/* ASCIIsvg.js
==============
JavaScript routines to dynamically generate Scalable Vector Graphics
using a mathematical xy-coordinate system (y increases upwards) and
very intuitive JavaScript commands (no programming experience required).
ASCIIsvg.js is good for learning math and illustrating online math texts.
Works with Internet Explorer+Adobe SVGviewer and SVG enabled Mozilla/Firefox.

Ver 1.2.7 Oct 13, 2005 (c) Peter Jipsen http://www.chapman.edu/~jipsen
Latest version at http://www.chapman.edu/~jipsen/svg/ASCIIsvg.js
If you use it on a webpage, please send the URL to jipsen@chapman.edu

A few modifications were made for use with IMathAS, especially removal
of plot and drawPictures functions, and changes to mathjs.

Merged ASCIIsvgAddon for tinyMCE use (c) 9/19/2008

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or (at
your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
General Public License (at http://www.gnu.org/copyleft/gpl.html)
for more details.*/

//reserved:
//minify using something that will preserve these variables:
//like uglify on http://refresh-sf.com/
//origin,border,strokewidth,strokedasharray,stroke,fill,fontstyle,fontfamily,fontsize,fontweight,fontstroke,fontfill,fontbackground,fillopacity,markerstrokewidth,markerstroke,markerfill,marker,arrowfill,dotradius,ticklength,axesstroke,gridstroke,xmin,xmax,ymin,ymax,xscl,yscl,xgrid,ygrid,xtick,ytick,width,height

(function() {
var ASnoSVG = false;
var checkIfSVGavailable = true;
var notifyIfNoSVG = false;
var alertIfNoSVG = false;
var xunitlength = 20;  // pixels
var yunitlength = 20;  // pixels
var origin = [0,0];   // in pixels (default is bottom left corner)
var defaultwidth = 300; defaultheight = 200; defaultborder = [0,0,0,0];
var border = defaultborder;
var strokewidth, strokedasharray, stroke, fill;
var fontstyle, fontfamily, fontsize, fontweight, fontstroke, fontfill, fontbackground;
var fillopacity = .5;
var markerstrokewidth = 1;
var markerstroke = "black";
var markerfill = "yellow";
var marker = "none";
var arrowfill = stroke;
var arrowrelsize = 1;
var arrowoffset = 0;
var dotradius = 4;
var ticklength = 4;
var axesstroke = "black";
var gridstroke = "#757575";
var pointerpos = null;
var coordinates = null;
var above = "above";
var below = "below";
var left = "left";
var right = "right";
var aboveleft = "aboveleft";
var aboveright = "aboveright";
var belowleft = "belowleft";
var belowright = "belowright";
var xmin, xmax, ymin, ymax, xscl, yscl,
    xgrid, ygrid, xtick, ytick, initialized;
var isOldIE = document.createElementNS==null;
var picture, svgpicture, doc, width, height, a, b, c, d, i, n, p, t, x, y;
var ASgraphidcnt = 0;

function chop(x,n) {
  if (n==null) n=0;
  return Math.floor(x*Math.pow(10,n))/Math.pow(10,n);
}

function prepWithMath(str) {
  // avoid double-prep cased by script wrap of prepWithMath followed by
  // secondary after prepWithMath
  // String literals are left untouched (so text([2,3],"`sqrt(5)`") is safe);
  // plot() function strings have any Math. stripped later by makeMathFunction.
  return str.replace(/("(?:[^"\\\n]|\\.)*"|'(?:[^'\\\n]|\\.)*')|[^"']+|["']/g, function(m, strlit) {
    if (strlit) { return m; }
    m = m.replace(/Ma(t|\(t\)\*)h\./g,'');
    m = m.replace(/\b(abs|acos|asin|atan|ceil|floor|sign|cos|sin|tan|sqrt|exp|max|min|pow)\(/g, 'Math.$1(');
    m = m.replace(/\bPI\b/g,'Math.PI'); // bare PI, including what was Math.PI before the strip above
    m = m.replace(/\(E\)/g,'(Math.E)');
    m = m.replace(/\((PI|pi)\)/g,'(Math.PI)');
    return m;
  });
}

function ran(a,b,n) {
  if (n==null) n=0;
  return chop((b+Math.pow(10,-n)-a)*Math.random()+a,n);
}


function myCreateElementXHTML(t) {
  if (isOldIE) return document.createElement(t);
  else return document.createElementNS("http://www.w3.org/1999/xhtml",t);
}


function isSVGavailable() {
  //WebKit got good at SVG after 531.22.7
  if ((ver = navigator.userAgent.toLowerCase().match(/webkit\/(\d+)/))!=null) {
		if (ver[1]>531) {
			return null;
		}
  }
  if (navigator.product && navigator.product=='Gecko') {
	   var rv = navigator.userAgent.toLowerCase().match(/rv:\s*([\d\.]+)/);
	   if (rv!=null) {
		rv = rv[1].split('.');
		if (rv.length<3) { rv[2] = 0;}
		if (rv.length<2) { rv[1] = 0;}
	   }
	   if (rv!=null && 10000*rv[0]+100*rv[1]+1*rv[2]>=10800) return null;
	   else return 1;
  }
  else if (navigator.appName.slice(0,9)=="Microsoft") {
    version = parseFloat(navigator.appVersion.split("MSIE")[1]);
    if (version >= 9) {
    	    //IE9+ can do SVG
	    return null;
    } else {
		  return 1;
    }
  } else return 1;
}




function less(x,y) { return x < y }  // used for scripts in XML files
                                     // since IE does not handle CDATA well
function setText(st,id) {
  var node = document.getElementById(id);
  if (node!=null)
    if (node.childNodes.length!=0) node.childNodes[0].nodeValue = st;
    else node.appendChild(document.createTextNode(st));
}


function myCreateElementSVG(t) {
  if (isOldIE) return doc.createElement(t);
  else return doc.createElementNS("http://www.w3.org/2000/svg",t);
}


function getX() {
  return (doc.getElementById("pointerpos").getAttribute("cx")-origin[0])/xunitlength;
}


function getY() {
  return (height-origin[1]-doc.getElementById("pointerpos").getAttribute("cy"))/yunitlength;
}


function mousemove_listener(evt) {
  if (svgpicture.getAttribute("xbase")!=null)
    pointerpos.cx.baseVal.value = evt.clientX-svgpicture.getAttribute("xbase");
  if (svgpicture.getAttribute("ybase")!=null)
    pointerpos.cy.baseVal.value = evt.clientY-svgpicture.getAttribute("ybase");
}


function top_listener(evt) {
  svgpicture.setAttribute("ybase",evt.clientY);
}


function bottom_listener(evt) {
  svgpicture.setAttribute("ybase",evt.clientY-height+1);
}


function left_listener(evt) {
  svgpicture.setAttribute("xbase",evt.clientX);
}


function right_listener(evt) {
  svgpicture.setAttribute("xbase",evt.clientX-width+1);
}

function asciisvgexpand(evt) {
	var el = evt.currentTarget.parentNode;
	var aspect = el.getAttribute("width")/el.getAttribute("height");
	var w = Math.min(800,$(window).width()*0.8);
	var h = $(window).height()*0.9-30;
	if ((aspect>=1 && w/aspect<h)) { //wider than tall
		h = Math.min(h, w/aspect);
	} else { //taller than wide
		w = Math.min(w, h*aspect);
	}
	h = Math.floor(h);
	w = Math.floor(w);

	var html = '<div style="text-align:center"><embed data-enlarged="true" type="image/svg+xml" width="'+w+'" height="'+h+'" ';
	if (el.hasAttribute("data-script")) {
		html += 'script="' + el.getAttribute("data-script").replace(/"/g,"&quot;") + '"';
	} else if (el.hasAttribute("data-sscr")) {
		var sscrarr = el.getAttribute("data-sscr").split(',');
		sscrarr[9] = w;
		sscrarr[10] = h;
		html += 'sscr="' + sscrarr.join(',').replace(/"/g,'&quot;') + '"';
	}
	html += ' /></div>';
	GB_show(_("Enlarged Graph"), html, w+6, h+66);
	setTimeout(drawPics, 500);
}

function switchTo(id) {
//alert(id);
  picture = document.getElementById(id);
  width = picture.getAttribute("width")-0;
  height = picture.getAttribute("height")-0;
  strokewidth = "1" // pixel
  stroke = "black"; // default line color
  fill = "none";    // default fill color
  marker = "none";
  if ((picture.nodeName == "EMBED" || picture.nodeName == "embed") && isOldIE) {
    svgpicture = picture.getSVGDocument().getElementById("root");
    doc = picture.getSVGDocument();
  } else {
    svgpicture = picture;
    doc = document;
  }
  xunitlength = svgpicture.getAttribute("xunitlength")-0;
  yunitlength = svgpicture.getAttribute("yunitlength")-0;
  xmin = svgpicture.getAttribute("xmin")-0;
  xmax = svgpicture.getAttribute("xmax")-0;
  ymin = svgpicture.getAttribute("ymin")-0;
  ymax = svgpicture.getAttribute("ymax")-0;
  origin = [svgpicture.getAttribute("ox")-0,svgpicture.getAttribute("oy")-0];
}


function updatePicture(obj) {
//alert(typeof obj)
  var src = document.getElementById((typeof obj=="string"?
              obj:"picture"+(obj+1)+"input")).value;
  xmin = null; xmax = null; ymin = null; ymax = null;
  xscl = null; xgrid = null; yscl = null; ygrid = null;
  initialized = false;
  switchTo((typeof obj=="string"?obj.slice(0,8):"picture"+(obj+1)));
  src = src.replace(/plot\(\x20*([^\"f\[][^\n\r]+?)\,/g,"plot\(\"$1\",");
  src = src.replace(/plot\(\x20*([^\"f\[][^\n\r]+)\)/g,"plot(\"$1\")");
  src = src.replace(/([0-9])([a-zA-Z])/g,"$1*$2");
  src = src.replace(/\)([\(0-9a-zA-Z])/g,"\)*$1");
//alert(src);
  try {
    eval(prepWithMath(src));
  } catch(err) {console.log(err+"\n"+src)}
}


function showHideCode(obj) {
  var node = obj.nextSibling;
  while (node != null && node.nodeName != "BUTTON" &&

    node.nodeName != "button") node = node.nextSibling;
  if (node.style.display == "none") node.style.display = "";
  else node.style.display = "none";
  while (node != null && node.nodeName != "TEXTAREA" &&
    node.nodeName != "textarea") node = node.previousSibling;
  if (node.style.display == "none") node.style.display = "";
  else node.style.display = "none";
//  updatePicture(node.getAttribute("id"));
}


function hideCode() { //do nothing
}


function showcode() { //do nothing
}


function nobutton() { //do nothing
}


function setBorder(l,b,r,t) {
	if (t==null) {
		border = new Array(l,l,l,l);
	} else {
		border = new Array(l,b,r,t);
	}
}

function initPicture(x_min,x_max,y_min,y_max) {
 if (x_min!=null) xmin = x_min;
 if (x_max!=null) xmax = x_max;
 if (y_min!=null) ymin = y_min;
 if (y_max!=null) ymax = y_max;
 if (xmin==null) xmin = -5;
 if (xmax==null) xmax = 5;
 if (typeof xmin != "number" || typeof xmax != "number" || xmin >= xmax)
   alert("Picture requires at least two numbers: xmin < xmax");
 else if (y_max != null && (typeof y_min != "number" ||
  				typeof y_max != "number" || y_min >= y_max))
   alert("initPicture(xmin,xmax,ymin,ymax) requires numbers ymin < ymax");
 else {
  //if (width==null)
  width = picture.getAttribute("width");
  //else picture.setAttribute("width",width);
  if (width==null || width=="") width=defaultwidth;
  //if (height==null)
  height = picture.getAttribute("height");
  //else picture.setAttribute("height",height);
  if (height==null || height=="") height=defaultheight;
  xunitlength = (width-border[0]-border[2])/(xmax-xmin);
  yunitlength = xunitlength;
  //alert(xmin+" "+xmax+" "+ymin+" "+ymax)
  if (y_min==null) {
  	origin = [-xmin*xunitlength+border[0],height/2];
  	ymin = -(height-border[1]-border[3])/(2*yunitlength);
  	ymax = -ymin;
  } else {
  	if (y_max!=null) yunitlength = (height-border[1]-border[3])/(ymax-ymin);
  	else ymax = (height-border[1]-border[3])/yunitlength + ymin;
  	origin = [-xmin*xunitlength+border[0],-ymin*yunitlength+border[1]];
  }
  winxmin = Math.max(border[0]-5,0);
  winxmax = Math.min(width-border[2]+5,width);
  winymin = Math.max(border[3]-5,0);
  winymax = Math.min(height-border[1]+5,height);
 }
 if (!initialized) {
  strokewidth = "1"; // pixel
  strokedasharray = null;
  stroke = "black"; // default line color
  fill = "none";    // default fill color
  fontstyle = "italic"; // default shape for text labels
  fontfamily = "times"; // default font
  fontsize = "16";      // default size
  fontweight = "normal";
  fontstroke = "black";  // default font outline color
  fontfill = "black";    // default font color
  fontbackground = "none";
  marker = "none";
  initialized = true;
//  if (true ||picture.nodeName == "EMBED" || picture.nodeName == "embed") {
    if (isOldIE) {
      svgpicture = picture.getSVGDocument().getElementById("root");
      while (svgpicture.childNodes.length()>5)
        svgpicture.removeChild(svgpicture.lastChild);
      svgpicture.setAttribute("width",width);
      svgpicture.setAttribute("height",height);
      doc = picture.getSVGDocument();
    } else {
      var qnode = document.createElementNS("http://www.w3.org/2000/svg","svg");
	var picid = picture.getAttribute("id");
	picture.setAttribute("id",picid+'-embed');
      qnode.setAttribute("id", picid);

      if (picture.hasAttribute("data-enlarged")) {
      	      qnode.setAttribute("viewBox","0 0 "+picture.getAttribute("width")+" "+picture.getAttribute("height"));
      } else {
      	      qnode.setAttribute("style","display:inline; "+picture.getAttribute("style"));
      	      qnode.setAttribute("width",picture.getAttribute("width"));
      	      qnode.setAttribute("height",picture.getAttribute("height"));
      }
      if (picture.hasAttribute("data-nomag")) {
        qnode.setAttribute("data-nomag",1);
      }
      if (picture.hasAttribute("data-noexplore")) {
        // text alternative is provided separately; hide graph from AT and don't make explorable
        qnode.setAttribute("data-noexplore", 1);
        qnode.setAttribute("aria-hidden", true);
      }
      if (picture.getAttribute('align') === 'middle') {
        qnode.style.verticalAlign = 'middle';
      }

      qnode.setAttribute("alt", picture.getAttribute("alt") || '');
      qnode.setAttribute("role", "img");

      if (picture.parentNode!=null) {
        //picture.parentNode.replaceChild(qnode,picture);
				picture.parentNode.insertBefore(qnode,picture);
				picture.style.display="none";
				if (picture.hasAttribute("sscr")) {
					qnode.setAttribute("data-sscr", picture.getAttribute("sscr"));
					picture.removeAttribute("sscr");
				}
				if (picture.hasAttribute("script")) {
          var scr = picture.getAttribute("script");
          scr = scr.replace(/<img[^>]*?alt="(.*?)".*?>/g,'`$1`');
					qnode.setAttribute("data-script", scr);
					picture.removeAttribute("script");
				}
      } else {
        svgpicture.parentNode.replaceChild(qnode,svgpicture);
      }

      svgpicture = qnode;

      if (picture.getAttribute("alt") != '' && picture.getAttribute("alt") != null) {
        var srdiv = document.getElementById(picid+"-srlabel");
        if (!srdiv) {
          srdiv = document.createElement("div");
          srdiv.id = picid+"-srlabel";
          srdiv.classList = "sr-only";
          svgpicture.after(srdiv);
        }
        srdiv.innerText = picture.getAttribute("alt");
        svgpicture.setAttribute("aria-hidden", true);
        /*
        var title = document.createElement("title");
        svgpicture.appendChild(title);
        title.innerText = picture.getAttribute("alt");
        title.id = picid+"-label";
        svgpicture.setAttribute("aria-labelledby", picid+"-label");
        */
      }

      doc = document;
      pointerpos = doc.getElementById("pointerpos");
      if (pointerpos==null) {
        pointerpos = myCreateElementSVG("circle");
        pointerpos.setAttribute("id","pointerpos");
        pointerpos.setAttribute("cx",0);
        pointerpos.setAttribute("cy",0);
        pointerpos.setAttribute("r",0.5);
        pointerpos.setAttribute("fill","red");
        svgpicture.appendChild(pointerpos);
      }
      if (!picture.hasAttribute("data-enlarged")) {
      	  //svgpicture.addEventListener("click", asciisvgexpand);
      }
    }
//  } else {
//    svgpicture = picture;
//    doc = document;
//  }

  if (!isOldIE && picture.getAttribute("onmousemove")!=null) {
    svgpicture.addEventListener("mousemove", mousemove_listener, true);
    var st = picture.getAttribute("onmousemove");
    svgpicture.addEventListener("mousemove", eval(st.slice(0,st.indexOf("("))), true);
    node = myCreateElementSVG("polyline");
    node.setAttribute("points","0,0 "+width+",0");
    node.setAttribute("style","stroke:white; stroke-width:3");
    node.addEventListener("mousemove", top_listener, true);
    svgpicture.appendChild(node);
    node = myCreateElementSVG("polyline");
    node.setAttribute("points","0,"+height+" "+width+","+height);
    node.setAttribute("style","stroke:white; stroke-width:3");
    node.addEventListener("mousemove", bottom_listener, true);
    svgpicture.appendChild(node);
    node = myCreateElementSVG("polyline");
    node.setAttribute("points","0,0 0,"+height);
    node.setAttribute("style","stroke:white; stroke-width:3");
    node.addEventListener("mousemove", left_listener, true);
    svgpicture.appendChild(node);
    node = myCreateElementSVG("polyline");
    node.setAttribute("points",(width-1)+",0 "+(width-1)+","+height);
    node.setAttribute("style","stroke:white; stroke-width:3");
    node.addEventListener("mousemove", right_listener, true);
    svgpicture.appendChild(node);
  }
  border = defaultborder;
 } else {
 	 //clear out svg
	 while (svgpicture.lastChild) {
		 svgpicture.removeChild(svgpicture.lastChild);
	 }
 }
 if (svgpicture.hasAttribute("viewBox")) {
 	 svgpicture.setAttribute("viewBox", "0 0 "+width+" "+height);
 } else {
 	 svgpicture.setAttribute("height", height);
	 svgpicture.style.height = height+"px";
	 svgpicture.setAttribute("width", width);
	 svgpicture.style.width = width+"px";
 }
 svgpicture.setAttribute("xunitlength",xunitlength);
 svgpicture.setAttribute("yunitlength",yunitlength);
 svgpicture.setAttribute("xmin",xmin);
 svgpicture.setAttribute("xmax",xmax);
 svgpicture.setAttribute("ymin",ymin);
 svgpicture.setAttribute("ymax",ymax);
 svgpicture.setAttribute("ox",origin[0]);
 svgpicture.setAttribute("oy",origin[1]);
 // registry of graph elements, used for keyboard exploration
 svgpicture._as = {svg:svgpicture, els:[], on:false, cur:0, t:0, mk:null,
   view:{xu:xunitlength, yu:yunitlength, ox:origin[0], oy:origin[1],
     h:Number(height), w:Number(width), xmin:xmin, xmax:xmax, ymin:ymin, ymax:ymax}};
 var node = myCreateElementSVG("rect");
 node.setAttribute("x","0");
 node.setAttribute("y","0");
 node.setAttribute("width",width);
 node.setAttribute("height",height);
 node.setAttribute("fill","white");
 svgpicture.appendChild(node);
}

function setBackgroundColor(color) {
    var bgrect = svgpicture.getElementsByTagName("rect")[0];
    bgrect.setAttribute("fill", color);
}
function setStrokeFill(node) {
  node.setAttribute("stroke-width", strokewidth);
  if (strokedasharray!=null)
    node.setAttribute("stroke-dasharray", strokedasharray);
  node.setAttribute("stroke", stroke);
  if (fill.substr(0,5)=='trans') {
  	  node.setAttribute("fill", fill.substring(5));
  	  node.setAttribute("fill-opacity",fillopacity);
  } else {
  	  node.setAttribute("fill", fill);
  }
}

function line(p,q,id) { // segment connecting points p,q (coordinates in units)
  var node;
  if (id!=null) node = doc.getElementById(id);
  if (node==null) {
    node = myCreateElementSVG("path");
    if (id!=null) node.setAttribute("id", id);
    svgpicture.appendChild(node);
  }
  node.setAttribute("d","M"+(p[0]*xunitlength+origin[0])+","+
    (height-p[1]*yunitlength-origin[1])+" "+
    (q[0]*xunitlength+origin[0])+","+(height-q[1]*yunitlength-origin[1]));
  setStrokeFill(node);
  if (marker=="dot" || marker=="arrowdot") {
    ASdot(p,4,markerstroke,markerfill);
    if (marker=="arrowdot") arrowhead(p,q);
    ASdot(q,4,markerstroke,markerfill);
  } else if (marker=="arrow") arrowhead(p,q);
  var mk = ASendMarkers();
  ASregisterElement({kind:'line', node:node, pts:[[p[0],p[1]],[q[0],q[1]]], startMark:mk.s, endMark:mk.e});
}


// Optional startmk/endmk ("open", "closed" or "arrow") draw an end dot/arrowhead as part of the path,
// so keyboard exploration treats them as the ends of this path rather than as separate elements.
function path(plist,id,c,startmk,endmk) {
  if (c==null) c="";
  var node, st, i;
  if (id!=null) node = doc.getElementById(id);
  if (node==null) {
    node = myCreateElementSVG("path");
    if (id!=null) node.setAttribute("id", id);
    svgpicture.appendChild(node);
  }
  if (typeof plist == "string") st = plist;
  else {
    st = "M";
    st += (plist[0][0]*xunitlength+origin[0])+","+
          (height-plist[0][1]*yunitlength-origin[1])+" "+c;
    for (i=1; i<plist.length; i++)
      st += (plist[i][0]*xunitlength+origin[0])+","+
            (height-plist[i][1]*yunitlength-origin[1])+" ";
  }
  node.setAttribute("d", st);
  setStrokeFill(node);
  if (marker=="dot" || marker=="arrowdot")
    for (i=0; i<plist.length; i++)
      if (c!="C" && c!="T" || i!=1 && i!=2)
        ASdot(plist[i],4,markerstroke,markerfill);
  var el = {kind:(c=="T"?'curve':(c=="C"?'loop':'path')), node:node};
  if (typeof plist != "string") {
    el.pts = plist.map(function(q) {return [q[0],q[1]]});
    el.polyline = (c=="");
    if (marker=="dot" || marker=="arrowdot") {
      el.startMark = el.endMark = "dot";
      if (el.polyline) el.vertMark = "dot";
    }
    if (plist.length > 1 && (startmk || endmk)) {
      var mkNames = {open:"open dot", closed:"closed dot", arrow:"arrow, graph continues"};
      var last = plist.length - 1;
      ASnoreg++;  // these belong to the path, not separate explorable elements
      try {
        if (startmk === "arrow") { arrowhead(plist[1], plist[0]); }
        else if (startmk === "open" || startmk === "closed") { dot(plist[0], startmk); }
        if (endmk === "arrow") { arrowhead(plist[last-1], plist[last]); }
        else if (endmk === "open" || endmk === "closed") { dot(plist[last], endmk); }
      } finally {
        ASnoreg--;
      }
      if (mkNames[startmk]) { el.startMark = mkNames[startmk]; }
      if (mkNames[endmk]) { el.endMark = mkNames[endmk]; }
    }
  }
  ASregisterElement(el);
}


function curve(plist,id) {
  path(plist,id,"T");
}


function circle(center,radius,id) { // coordinates in units
  var node;
  if (id!=null) node = doc.getElementById(id);
  if (node==null) {
    // an ellipse, so the circle is correct in graph units when the axes are unequal
    node = myCreateElementSVG("ellipse");
    if (id!=null) node.setAttribute("id", id);
    svgpicture.appendChild(node);
  }

  node.setAttribute("cx",center[0]*xunitlength+origin[0]);
  node.setAttribute("cy",height-center[1]*yunitlength-origin[1]);
  node.setAttribute("rx",radius*xunitlength);
  node.setAttribute("ry",radius*yunitlength);
  setStrokeFill(node);
  ASregisterElement({kind:'circle', node:node, c:[center[0],center[1]], r:radius});
}


function loop(p,d,id) {
// d is a direction vector e.g. [1,0] means loop starts in that direction
  if (d==null) d=[1,0];
  path([p,[p[0]+d[0],p[1]+d[1]],[p[0]-d[1],p[1]+d[0]],p],id,"C");
  if (marker=="arrow" || marker=="arrowdot")
    arrowhead([p[0]+Math.cos(1.4)*d[0]-Math.sin(1.4)*d[1],
               p[1]+Math.sin(1.4)*d[0]+Math.cos(1.4)*d[1]],p);
}

function sector(center,radius,startang,endang,id) {
	var node, v;
	if (id!=null) node = doc.getElementById(id);
	if (node==null) {
		node = myCreateElementSVG("path");
		if (id!=null) node.setAttribute("id", id);
		svgpicture.appendChild(node);
	}
	var arctype = 0;
	if (Math.abs(endang-startang)>3.142) {
		arctype = 1;
	}
	var angdir = 0;
	if (endang<startang) {
		angdir = 1;
	}
	var start = [center[0] + radius*Math.cos(startang), center[1] + radius*Math.sin(startang)];
	var end = [center[0] + radius*Math.cos(endang), center[1] + radius*Math.sin(endang)];

	var pathstr = "M"+(center[0]*xunitlength+origin[0])+","+
		(height-center[1]*yunitlength-origin[1])+
		" L"+(start[0]*xunitlength+origin[0])+","+
		(height-start[1]*yunitlength-origin[1])+ " A"+radius*xunitlength+","+
		radius*yunitlength+" 0 "+arctype+","+angdir+" "+(end[0]*xunitlength+origin[0])+","+
		(height-end[1]*yunitlength-origin[1]) +
		" z";
	node.setAttribute("d",pathstr);
	node.setAttribute("stroke-width", strokewidth);
	node.setAttribute("stroke", stroke);
	if (fill.substr(0,5)=='trans') {
		node.setAttribute("fill", fill.substring(5));
		node.setAttribute("fill-opacity",fillopacity);
	} else {
		node.setAttribute("fill", fill);
	}
	ASregisterElement({kind:'sector', node:node, c:[center[0],center[1]], r:radius});
}


function arc(start,end,radius,id) { // coordinates in units
  var node, v;
//alert([fill, stroke, origin, xunitlength, yunitlength, height])
  if (id!=null) node = doc.getElementById(id);
  if (radius==null) {
    v=[end[0]-start[0],end[1]-start[1]];
    radius = Math.sqrt(v[0]*v[0]+v[1]*v[1]);
  }
  if (node==null) {
    node = myCreateElementSVG("path");
    if (id!=null) node.setAttribute("id", id);
    svgpicture.appendChild(node);
  }
  node.setAttribute("d","M"+(start[0]*xunitlength+origin[0])+","+
    (height-start[1]*yunitlength-origin[1])+" A"+radius*xunitlength+","+
     radius*yunitlength+" 0 0,0 "+(end[0]*xunitlength+origin[0])+","+
    (height-end[1]*yunitlength-origin[1]));
  setStrokeFill(node);
  if (marker=="arrow" || marker=="arrowdot") {
    u = [(end[1]-start[1])/4,(start[0]-end[0])/4];
    v = [(end[0]-start[0])/2,(end[1]-start[1])/2];
//alert([u,v])
    v = [start[0]+v[0]+u[0],start[1]+v[1]+u[1]];
  } else v=[start[0],start[1]];
  if (marker=="dot" || marker=="arrowdot") {
    ASdot(start,4,markerstroke,markerfill);
    if (marker=="arrowdot") arrowhead(v,end);
    ASdot(end,4,markerstroke,markerfill);
  } else if (marker=="arrow") arrowhead(v,end);
  var mk = ASendMarkers();
  ASregisterElement({kind:'arc', node:node, from:[start[0],start[1]], to:[end[0],end[1]], startMark:mk.s, endMark:mk.e});
}


function ellipse(center,rx,ry,id) { // coordinates in units

  var node;
  if (id!=null) node = doc.getElementById(id);
  if (node==null) {
    node = myCreateElementSVG("ellipse");
    if (id!=null) node.setAttribute("id", id);
    svgpicture.appendChild(node);
  }
  node.setAttribute("cx",center[0]*xunitlength+origin[0]);
  node.setAttribute("cy",height-center[1]*yunitlength-origin[1]);
  node.setAttribute("rx",rx*xunitlength);
  node.setAttribute("ry",ry*yunitlength);
  setStrokeFill(node);
  ASregisterElement({kind:'ellipse', node:node, c:[center[0],center[1]], rx:rx, ry:ry});
}


function rect(p,q,id,rx,ry) { // opposite corners in units, rounded by radii
  var node;
  if (id!=null) node = doc.getElementById(id);
  if (node==null) {
    node = myCreateElementSVG("rect");
    if (id!=null) node.setAttribute("id", id);
    svgpicture.appendChild(node);
  }
  node.setAttribute("x",Math.min(p[0],q[0])*xunitlength+origin[0]);
  node.setAttribute("y",height-Math.max(q[1],p[1])*yunitlength-origin[1]);
  node.setAttribute("width",Math.abs(q[0]-p[0])*xunitlength);
  node.setAttribute("height",Math.abs(q[1]-p[1])*yunitlength);
  if (rx!=null) node.setAttribute("rx",rx*xunitlength);
  if (ry!=null) node.setAttribute("ry",ry*yunitlength);
  setStrokeFill(node);
  ASregisterElement({kind:'rect', node:node, p:[p[0],p[1]], q:[q[0],q[1]]});
}

function text(p,st,pos,angle) {
	p[0] = p[0]*xunitlength+origin[0];
	p[1] = p[1]*yunitlength+origin[1];
	textabs(p,st,pos,angle);
}

function textabs(p,st,pos,angle,id,fontsty) {
  ASregisterElement({kind:'text', at:[(p[0]-origin[0])/xunitlength, (p[1]-origin[1])/yunitlength], str:st, stroke:fontfill});
  if (angle==null) {
	  angle = 0;
  } else {
	  angle = (360 - angle)%360;
  }
  if (typeof st == 'string' && st.indexOf('`') > -1 && rendermathnode && (mathRenderer ?? 'none') != "none") {
    return mathtextabs(p, st, pos, angle);
  }
  var textanchor = "middle";
  var dx=0; var dy=0;
  if (angle==270) {
	  var dy = 0; var dx = fontsize/3;
	  if (pos!=null) {
	    if (pos.match(/left/)) {dx = -fontsize/2-2;}
	    if (pos.match(/right/)) {dx = 1*fontsize+2;}
	    if (pos.match(/above/)) {
	      textanchor = "start";
	      dy = -fontsize/2-2;
	    }
	    if (pos.match(/below/)) {
	      textanchor = "end";
	      dy = fontsize/2+2;
	    }
	  }
  }
  if (angle==90) {
	  var dy = 0; var dx = -fontsize/3;
	  if (pos!=null) {
	    if (pos.match(/left/)) dx = -fontsize-2;
	    if (pos.match(/right/)) dx = fontsize/2+2;
	    if (pos.match(/above/)) {
	      textanchor = "end";
	      dy = -fontsize/2-2;
	    }
	    if (pos.match(/below/)) {
	      textanchor = "start";
	      dy = fontsize/2+2;
	    }
	  }
  }
  if (angle==0) {
	  var dx = 0; var dy = fontsize/3;
	  if (pos!=null) {
	    if (pos.match(/above/)) { dy = -fontsize/3-2; }
	    if (pos.match(/below/)) { dy = 1*fontsize+2; }
	    if (pos.match(/right/)) {
	      textanchor = "start";
	      dx = fontsize/3+2;
	    }
	    if (pos.match(/left/)) {
	      textanchor = "end";
	      dx = -fontsize/3-2;
	    }
	  }
  }

  var node;
  if (id!=null) node = doc.getElementById(id);
  if (node==null) {
    node = myCreateElementSVG("text");
    if (id!=null) node.setAttribute("id", id);
    svgpicture.appendChild(node);
  } else {
    node.replaceChildren();
  }
  st = st.toString().replace(/[₀-₉]/g, function(ch) {return '<sub>'+(ch.codePointAt(0) - 0x2080)+'</sub>'});
  st = st.replace(/<\/sub><sub>/g,'');
  var parts = st.split(/(<sub>.*?<\/sub>)/i);
  var subMatch;
  var part;
  for (part of parts) {
      subMatch = part.match(/^<sub>(.*?)<\/sub>$/i);
      if (subMatch) {
          var tspan = myCreateElementSVG("tspan");
          tspan.setAttribute("baseline-shift", "sub");
          tspan.setAttribute("font-size", "0.7em");
          tspan.appendChild(doc.createTextNode(subMatch[1]));
          node.appendChild(tspan);
      } else if (part !== "") {
          node.appendChild(doc.createTextNode(part));
      }
  }
  
  node.setAttribute("x",p[0]+dx);
  node.setAttribute("y",height-p[1]+dy);
  if (angle != 0) {
	  node.setAttribute("transform","rotate("+angle+" "+(p[0]+dx)+" "+(height-p[1]+dy)+")");
  }
  node.setAttribute("font-style",(fontsty!=null?fontsty:fontstyle));
  node.setAttribute("font-family",fontfamily);
  node.setAttribute("font-size",fontsize);
  node.setAttribute("font-weight",fontweight);
  node.setAttribute("text-anchor",textanchor);
  //if (fontstroke!="none") node.setAttribute("stroke",fontstroke);
  if (fontfill!="none") node.setAttribute("fill",fontfill);
  node.setAttribute("stroke-width","0px");
  if (fontbackground!="none") {
	  try {
		 var bb = node.getBBox();
		  var bgnode = myCreateElementSVG("rect");

		  bgnode.setAttribute("fill",fontbackground);
		  bgnode.setAttribute("stroke-width","0px");
		  bgnode.setAttribute("x",bb.x-2);
		  bgnode.setAttribute("y",bb.y-1);
		  bgnode.setAttribute("width",bb.width+4);
		  bgnode.setAttribute("height",bb.height+2);
		  if (angle != 0) {
			   bgnode.setAttribute("transform","rotate("+angle+" "+(p[0]+dx)+" "+(height-p[1]+dy)+")");
		  }
		  svgpicture.insertBefore(bgnode,node);
	  } catch (e) {

	  }

  }
  return p;
}

function rotatePt(x, y, angle_deg) {
  const r = angle_deg * Math.PI / 180;
  return { x: x * Math.cos(r) - y * Math.sin(r),
           y: x * Math.sin(r) + y * Math.cos(r) };
}

// How far the AABB extends from the element's center in each screen direction
function aabbHalfExtents(w, h, angle_deg) {
  const corners = [
    rotatePt(-w/2, -h/2, angle_deg),
    rotatePt( w/2, -h/2, angle_deg),
    rotatePt( w/2,  h/2, angle_deg),
    rotatePt(-w/2,  h/2, angle_deg),
  ];
  const xs = corners.map(c => c.x), ys = corners.map(c => c.y);
  return {
    left:   -Math.min(...xs),
    right:   Math.max(...xs),
    top:    -Math.min(...ys),
    bottom:  Math.max(...ys),
  };
}

function mathtextabs(p,st,pos,angle) {
  var fo = document.createElementNS('http://www.w3.org/2000/svg', 'foreignObject');
  var div = document.createElementNS('http://www.w3.org/1999/xhtml', 'div');
  var span = document.createElementNS('http://www.w3.org/1999/xhtml', 'span');
  div.style.fontSize = (fontsize * .9) + 'px';
  div.style.textAlign = 'left';
  if (fontfill!="none") { div.style.color = fontfill; }
  div.innerHTML = st;
  p[1] = height - p[1];
  fo.setAttribute('x', p[0] - fontsize/2);
  fo.setAttribute('y', p[1] - fontsize/2);
  fo.setAttribute('width', width);
  fo.setAttribute('height', height);
  fo.appendChild(div);
  var ge = document.createElementNS('http://www.w3.org/2000/svg', 'g');
  ge.appendChild(fo);
  ge.style.visibility = "hidden";
  svgpicture.appendChild(ge);
  var basep = p;
  rendermathnode(div, function() {
    var w = div.firstElementChild.getBoundingClientRect().width;
    var h = div.getBoundingClientRect().height;

    const ext = aabbHalfExtents(w, h, angle);
    let dx = 0, dy = 0;
    if (pos!=null) {
      if (pos.match(/above/))  dy =  ext.bottom;
      if (pos.match(/below/))  dy = -ext.top;
      if (pos.match(/left/))   dx =  ext.right;
      if (pos.match(/right/))  dx = -ext.left;
    }
    const cx = basep[0] - dx, cy = basep[1] - dy;
    ge.setAttribute('transform', `translate(${cx}, ${cy}) rotate(${angle})`);
    fo.setAttribute('x', -w/2);
    fo.setAttribute('y', -h/2);
    ge.style.visibility = "";
  });
}

var fraccnt = 0;
function textfrac(p,n,d,pos) {
  if (d == 1) {
    text(p, n, pos);
    return;
  }
  ASregisterElement({kind:'text', at:[p[0],p[1]], str:n+" over "+d, stroke:fontfill});
  ASnoreg++;
  try {
    textfrac_(p,n,d,pos);
  } finally {
    ASnoreg--;
  }
}
function textfrac_(p,n,d,pos) {
  p[0] = p[0]*xunitlength+origin[0];
	p[1] = p[1]*yunitlength+origin[1];
  let dy = 0;
  if (pos!=null) {
    if (pos.match(/above/)) {
      dy = -fontsize - 3;
    } else if (pos.match(/below/)) {
      dy = fontsize + 3;
    } 
  }
  textabs([p[0],p[1]-dy-.6*fontsize],d,'',0,'asfracd'+fraccnt);
  textabs([p[0],p[1]-dy+.6*fontsize],n,'',0,'asfracn'+fraccnt);
  let nnode = document.getElementById('asfracn'+fraccnt);
  let nnodebox = nnode.getBBox();
  let dnode = document.getElementById('asfracd'+fraccnt);
  let dnodebox = dnode.getBBox();
  let hshift = 0;
  if (pos!=null && pos.match(/(right|left)/)) {
    let maxw = Math.max(nnodebox.width,dnodebox.width);
    if (pos.match(/right/)) {
      hshift = maxw/2 + 3;
    } else {
      hshift = -maxw/2 + -3;
    }
    nnode.setAttribute('x', parseFloat(nnode.getAttribute('x')) + hshift);
    dnode.setAttribute('x', parseFloat(dnode.getAttribute('x')) + hshift);
  }
  
  let fleft = Math.min(nnodebox.x,dnodebox.x) + hshift;
  let fright = Math.max(nnodebox.x+nnodebox.width, dnodebox.x+dnodebox.width) + hshift;
  
  node = myCreateElementSVG("path");
  node.setAttribute("d","M"+(fleft)+","+(height-p[1]+dy)+" "+(fright)+","+(height-p[1]+dy));
  node.setAttribute("stroke-width", 1);
  node.setAttribute("stroke", fontfill);
  svgpicture.appendChild(node);
  fraccnt++;
}

function ASdot(center,radius,s,f) { // coordinates in units, radius in pixel
  if (s==null) s = stroke; if (f==null) f = fill;
  var node = myCreateElementSVG("circle");
  node.setAttribute("cx",center[0]*xunitlength+origin[0]);
  node.setAttribute("cy",height-center[1]*yunitlength-origin[1]);
  node.setAttribute("r",radius);
  node.setAttribute("stroke-width", strokewidth);
  node.setAttribute("stroke", s);
  node.setAttribute("fill", f);
  svgpicture.appendChild(node);
}


function dot(center, typ, label, pos, id) {
  var node;
  var cx = center[0]*xunitlength+origin[0];
  var cy = height-center[1]*yunitlength-origin[1];
  if (id!=null) node = doc.getElementById(id);
  if (typ=="+" || typ=="-" || typ=="|") {
    if (node==null) {
      node = myCreateElementSVG("path");
      if (id!=null) node.setAttribute("id", id);
      svgpicture.appendChild(node);
    }
    if (typ=="+") {
      node.setAttribute("d",
        " M "+(cx-ticklength)+" "+cy+" L "+(cx+ticklength)+" "+cy+
        " M "+cx+" "+(cy-ticklength)+" L "+cx+" "+(cy+ticklength));
      node.setAttribute("stroke-width", .5);
      node.setAttribute("stroke", axesstroke);
    } else {
      if (typ=="-") node.setAttribute("d",
        " M "+(cx-ticklength)+" "+cy+" L "+(cx+ticklength)+" "+cy);
      else node.setAttribute("d",
        " M "+cx+" "+(cy-ticklength)+" L "+cx+" "+(cy+ticklength));
      node.setAttribute("stroke-width", strokewidth);
      node.setAttribute("stroke", stroke);
    }
  } else {
    if (node==null) {
      node = myCreateElementSVG("circle");
      if (id!=null) node.setAttribute("id", id);
      svgpicture.appendChild(node);
    }
    node.setAttribute("cx",cx);
    node.setAttribute("cy",cy);
    node.setAttribute("r",dotradius);
    node.setAttribute("stroke-width", strokewidth);
    node.setAttribute("stroke", stroke);
    node.setAttribute("fill", (typ=="open"?"white":stroke));
  }
  ASregisterElement({kind:'dot', node:node, at:[center[0],center[1]], typ:typ, label:label});
  if (label!=null) {
    ASnoreg++;
    try {
      text(center,label,(pos==null?"below":pos),(id==null?id:id+"label"));
    } finally {
      ASnoreg--;
    }
  }
}


function arrowhead(p,q) { // draw arrowhead at q (in units)
  var up;
  var v = [p[0]*xunitlength+origin[0],height-p[1]*yunitlength-origin[1]];
  var w = [q[0]*xunitlength+origin[0],height-q[1]*yunitlength-origin[1]];
  var u = [w[0]-v[0],w[1]-v[1]];
  var d = Math.sqrt(u[0]*u[0]+u[1]*u[1]);
  if (d > 0.00000001) {
    u = [u[0]/d, u[1]/d];
    up = [-u[1],u[0]];
    var node = myCreateElementSVG("path");
    node.setAttribute("d","M "+(w[0]-arrowrelsize*15*u[0]-arrowrelsize*4*up[0]-1*arrowoffset*u[0])+" "+
      (w[1]-arrowrelsize*15*u[1]-arrowrelsize*4*up[1]-1*arrowoffset*u[1])+" L "+(w[0]-(1*markerstrokewidth+1*arrowoffset)*u[0])+" "+(w[1]-(1*markerstrokewidth+1*arrowoffset)*u[1])+" L "+
      (w[0]-arrowrelsize*15*u[0]+arrowrelsize*4*up[0]-1*arrowoffset*u[0])+" "+(w[1]-arrowrelsize*15*u[1]+arrowrelsize*4*up[1]-1*arrowoffset*u[1])+" z");
    node.setAttribute("stroke-width", markerstrokewidth);
    node.setAttribute("stroke", stroke); /*was markerstroke*/
    node.setAttribute("fill", stroke); /*was arrowfill*/
    svgpicture.appendChild(node);
  }
}

function addMagGlass() {
  node = myCreateElementSVG("circle");
  node.setAttribute("id", "magglass1");
  node.setAttribute("cx",width-10);
  node.setAttribute("cy",height-10);
  node.setAttribute("r",5);
  node.setAttribute("stroke-width", 2);
  node.setAttribute("stroke", "grey");
  node.setAttribute("stroke-opacity", 0.5);
  node.setAttribute("fill", "none");
  svgpicture.appendChild(node);
  node = myCreateElementSVG("line");
  node.setAttribute("id", "magglass2");
  node.setAttribute("x1",width-1);
  node.setAttribute("y1",height-1);
  node.setAttribute("x2",width-6);
  node.setAttribute("y2",height-6);
  node.setAttribute("stroke-width", 2);
  node.setAttribute("stroke", "grey");
  node.setAttribute("stroke-opacity", 0.5);
  node.setAttribute("fill", "none");
  svgpicture.appendChild(node);
  node = myCreateElementSVG("rect");
  node.setAttribute("id", "magglass3");
  node.setAttribute("x",width-20);
  node.setAttribute("y",height-20);
  node.setAttribute("width",20);
  node.setAttribute("height",20);
  node.setAttribute("stroke", "none");
  node.setAttribute("fill", "white");
  node.setAttribute("fill-opacity", 0.01);
  node.style.cursor = "pointer";
  svgpicture.appendChild(node);
  node.addEventListener("click", asciisvgexpand);
  if (svgpicture.hasAttribute("data-noexplore")) {
    // The svg is aria-hidden, so a focusable control inside it is not exposed to assistive tech.
    // Use a real button after the svg instead, and show focus on the magnifier.
    var btnid = svgpicture.getAttribute("id") + "-expandbtn";
    var btn = document.getElementById(btnid);
    if (!btn) {
      btn = document.createElement("button");
      btn.type = "button";
      btn.id = btnid;
      btn.className = "sr-only";
      btn.textContent = "Expand Graph";
      svgpicture.after(btn);
    }
    var glassnode = node;
    btn.onclick = function() { asciisvgexpand({currentTarget: glassnode}); };
    btn.onfocus = function() {
      glassnode.setAttribute("stroke", "black");
      glassnode.setAttribute("stroke-width", 2);
      glassnode.setAttribute("fill-opacity", 0.3);
    };
    btn.onblur = function() {
      glassnode.setAttribute("stroke", "none");
      glassnode.setAttribute("fill-opacity", 0.01);
    };
    return;
  }
  node.setAttribute("tabindex", 0);
  node.setAttribute("role", "button");
  node.setAttribute("aria-label", "Expand Graph");
  node.addEventListener("keydown", function(e) {
  	if (e.keyCode === 13) {asciisvgexpand(e);}
  });
}

function chopZ(st) {
  var k = st.indexOf(".");
  if (k==-1) return st;
  for (var i=st.length-1; i>k && st.charAt(i)=="0"; i--);
  if (i==k) i--;
  return st.slice(0,i+1);
}
function rounddec(v,dec) {
  dec = 2 + Math.max(0,dec-2);
  var m = Math.pow(10,dec);
  return Math.round(v*m)/m;
}


function grid(dx,dy) { // for backward compatibility
  axes(dx,dy,null,dx,dy)
}


function noaxes() {
  if (!initialized) initPicture();
}


function axislabels(xname, yname) { // names used for the axes in the keyboard-explore descriptions
  if (svgpicture && svgpicture._as) {
    if (xname) { svgpicture._as.view.xname = String(xname); }
    if (yname) { svgpicture._as.view.yname = String(yname); }
  }
}
function axes(dx,dy,labels,gdx,gdy,dox,doy,smallticks) {
  ASnoreg++;  // axes, ticks and tick labels aren't explorable graph elements
  try {
    axes_(dx,dy,labels,gdx,gdy,dox,doy,smallticks);
  } finally {
    ASnoreg--;
  }
}
function ASgreatestCommonDivisor(a, b) { return b === 0 ? a : ASgreatestCommonDivisor(b, a % b); }

// Parse an axis step given as a string: "0.5", "1/4", "pi/2", "3pi/4", "2*pi".
// Returns null if invalid, else {val:number, frac:null or {num,den,ispi,val}}.
function ASparseStepSize(s) {
  var m = String(s).match(/^\s*([+-]?\d+(?:\.\d+)?)?\s*\*?\s*(pi|π)?\s*(?:\/\s*(\d+))?\s*$/i);
  if (!m || (m[1] === undefined && !m[2])) { return null; }
  var ispi = !!m[2];
  var num = (m[1] === undefined) ? 1 : parseFloat(m[1]);
  var den = (m[3] === undefined) ? 1 : parseInt(m[3], 10);
  if (!(den > 0) || !(num > 0)) { return null; }
  var val = num*(ispi ? Math.PI : 1)/den;
  if (Math.floor(num) !== num || (!ispi && den === 1)) { return {val:val, frac:null}; }
  var g = ASgreatestCommonDivisor(num, den);
  return {val:val, frac:{num:num/g, den:den/g, ispi:ispi, val:val}};
}

// Text for k times the fraction step f, as [numerator string, denominator]
function ASfractionParts(k, f) {
  var n = k*f.num, g = ASgreatestCommonDivisor(Math.abs(n), f.den);
  n = n/g;
  var d = f.den/g, a = Math.abs(n);
  var ns = f.ispi ? (a === 1 ? 'π' : a + 'π') : String(a);
  return [(n < 0 ? '-' : '') + ns, d];
}

function ASaxisLabels(k, f, pt, pos) { // draw a fraction-style axis label
  if (k === 0) { text(pt, '0', pos); return; }
  var pr = ASfractionParts(k, f);
  if (pr[1] === 1) { text(pt, pr[0], pos); } else { textfrac(pt, pr[0], pr[1], pos); }
}

function ASfractionFormat(v, f) { // v as a fraction/multiple of pi consistent with f, or null if it isn't one
  if (Math.abs(v) < 1e-12) { return '0'; }
  var r = v/(f.ispi ? Math.PI : 1);
  for (var d = 1; d <= 64; d++) {
    var m = r*d;
    if (Math.abs(m - Math.round(m)) < 1e-5*Math.max(1, Math.abs(m))) {  // loose enough for points found numerically
      var n = Math.round(m);
      if (n === 0) { return '0'; }
      var g = ASgreatestCommonDivisor(Math.abs(n), d), a = Math.abs(n)/g, dd = d/g;
      var s = f.ispi ? (a === 1 ? 'π' : a + 'π') : String(a);
      return (n < 0 ? '−' : '') + s + (dd === 1 ? '' : '/' + dd);
    }
  }
  return null;
}

// Smooth stand-in for a path that is the graph of a function: the path only has samples, and the
// straight chords between them cut under peaks. A cubic spline through the samples (C2, so accurate
// even when the samples are sparse), broken into separate pieces at sharp corners. Returns
// {f:function(x), hs} or null if x isn't strictly increasing along the points.
function ASsmoothFunction(pts0, v) {
  if (!pts0 || pts0.length < 3) { return null; }
  var pts = pts0.slice(), n = pts.length, xs = [], ys = [], mL = [], mR = [], i, j;
  if (pts[0][0] > pts[n-1][0]) { pts.reverse(); }
  for (i = 0; i < n; i++) {
    if (i > 0 && !(pts[i][0] > pts[i-1][0])) { return null; }
    xs.push(pts[i][0]); ys.push(pts[i][1]);
  }
  var seg = function(k) { return (ys[k+1] - ys[k])/(xs[k+1] - xs[k]); };
  var hh = function(k) { return xs[k+1] - xs[k]; };
  // pieces run between corners
  var starts = [0];
  for (i = 1; i < n - 1; i++) {
    if (Math.abs(Math.atan(seg(i)*v.yu/v.xu) - Math.atan(seg(i-1)*v.yu/v.xu)) > Math.PI/6) { starts.push(i); }
  }
  starts.push(n - 1);
  for (var s = 0; s < starts.length - 1; s++) {
    var a = starts[s], b = starts[s+1], m = b - a + 1, sl = [], sub = [], rhs = [], dg = [], up = [];
    if (m === 2) {
      mR[a] = mL[b] = seg(a);
      continue;
    }
    // end slopes from a parabola through the first (last) three points of the piece
    sl[0] = seg(a) + (seg(a) - seg(a+1))*hh(a)/(hh(a) + hh(a+1));
    sl[m-1] = seg(b-1) + (seg(b-1) - seg(b-2))*hh(b-1)/(hh(b-1) + hh(b-2));
    // interior slopes: h[i] m[i-1] + 2(h[i-1]+h[i]) m[i] + h[i-1] m[i+1] = 3(h[i] d[i-1] + h[i-1] d[i])
    for (j = 1; j < m - 1; j++) {
      var k = a + j, hp = hh(k-1), hn = hh(k);
      sub[j] = hn; dg[j] = 2*(hp + hn); up[j] = hp;
      rhs[j] = 3*(hn*seg(k-1) + hp*seg(k));
    }
    rhs[1] -= sub[1]*sl[0];
    rhs[m-2] -= up[m-2]*sl[m-1];
    for (j = 2; j < m - 1; j++) {  // Thomas algorithm
      var w = sub[j]/dg[j-1];
      dg[j] -= w*up[j-1];
      rhs[j] -= w*rhs[j-1];
    }
    for (j = m - 2; j >= 1; j--) {
      sl[j] = (rhs[j] - (j < m - 2 ? up[j]*sl[j+1] : 0))/dg[j];
    }
    mR[a] = sl[0]; mL[b] = sl[m-1];
    for (j = 1; j < m - 1; j++) { mL[a+j] = mR[a+j] = sl[j]; }
  }
  return {
    hs: (xs[n-1] - xs[0])/(n-1),
    f: function(x) {
      if (!(x >= xs[0] && x <= xs[n-1])) { return null; }
      var lo = 0, hi = n - 2;
      while (lo < hi) {
        var mid = (lo + hi + 1) >> 1;
        if (xs[mid] <= x) { lo = mid; } else { hi = mid - 1; }
      }
      var h = xs[lo+1] - xs[lo], t = (x - xs[lo])/h, t2 = t*t, t3 = t2*t;
      return (2*t3 - 3*t2 + 1)*ys[lo] + (t3 - 2*t2 + t)*h*mR[lo] +
        (-2*t3 + 3*t2)*ys[lo+1] + (t3 - t2)*h*mL[lo+1];
    }
  };
}

function ASformatAxisValue(v, dec, f) { // number to text for an axis, as a fraction if the axis uses fraction labels
  if (f) {
    var s = ASfractionFormat(v, f);
    if (s !== null) { return s; }
  }
  return ASformatNumber(v, dec);
}

function ASfractionSteps(f, W) { // keyboard steps when an axis is labeled in fractions
  return {normal:f.val/2, small:f.val/8, large:f.val*Math.max(1, Math.round(W/(4*f.val)))};
}

function axes_(dx,dy,labels,gdx,gdy,dox,doy,smallticks) {
//xscl=x is equivalent to xtick=x; xgrid=x; labels=true;
// dx and dy may be strings giving fractions or multiples of pi, like "1/4" or "pi/2";
// the axis is then labeled with fractions
  var x, y, ldx, ldy, lx, ly, lxp, lyp, pnode, st, xf = null, yf = null, k, pd;
  if (!initialized) initPicture();
  if (typeof dx=="string") {
    pd = ASparseStepSize(dx);
    if (pd) { dx = pd.val; xf = pd.frac; if (labels==null) { labels = 1; } } else { dx = null; }
  }
  if (typeof dy=="string") {
    pd = ASparseStepSize(dy);
    if (pd) { dy = pd.val; yf = pd.frac; if (labels==null) { labels = 1; } } else { dy = null; }
  }
  if (xscl!=null) {dx = xscl; gdx = xscl; labels = dx; xf = null;}
  if (yscl!=null) {dy = yscl; gdy = yscl; yf = null;}
  if (svgpicture && svgpicture._as) {
    svgpicture._as.view.xfrac = xf;
    svgpicture._as.view.yfrac = yf;
  }
  if (xtick!=null) {dx = xtick}
  if (ytick!=null) {dy = ytick}
  if (dox==null) {dox = true;}
  if (doy==null) {doy = true;}
  var fqonlyx = false; var fqonlyy = false;
  if (dox=="fq") {fqonlyx = true;}
  if (doy=="fq") {fqonlyy = true;}
  if (dox=="off" || dox==0) { dox = false;} else {dox = true;}
  if (doy=="off" || doy==0) { doy = false;} else {doy = true;}

//alert(null)
    if (gdx!=null && gdx>0 && (xmax-xmin)/gdx > width) {
    	    gdx = xmax-xmin;
    }
    if (gdy!=null && gdy>0 && (ymax-ymin)/gdy > height) {
    	    gdy = ymax-ymin;
    }
    if ((xmax-xmin)/dx > width) {
    	    dx = xmax-xmin;
    }
    if ((ymax-ymin)/dy > height) {
    	    dy = ymax-ymin;
    }

  dx = (dx==null?xunitlength:dx*xunitlength);
  dy = (dy==null?dx:dy*yunitlength);
  if (!dox) {
    fontsize = Math.floor(Math.min(Math.abs(dy)/1.5, 16));//alert(fontsize)
  } else if (!doy) {
    fontsize = Math.floor(Math.min(Math.abs(dx)/1.5, 16));//alert(fontsize)
  } else {
    fontsize = Math.floor(Math.min(Math.abs(dx)/1.5, Math.abs(dy)/1.5,16));//alert(fontsize)
  }
  ticklength = fontsize/4;
  if (xgrid!=null) gdx = xgrid;
  if (ygrid!=null) gdy = ygrid;
  
  if (smallticks!=null && smallticks==1) {
    var gridymin = height - origin[1] + .7*ticklength;
    var gridymax = height - origin[1] - .7*ticklength;
    var gridxmin = origin[0] - .7*ticklength;
    var gridxmax = origin[0] + .7*ticklength;
  } else {
    var gridymin = winymin;
    var gridymax = winymax;
    var gridxmin = winxmin;
    var gridxmax = winxmax;
  }

  gdx = (typeof gdx=="string"?dx:gdx*xunitlength);
  gdy = (typeof gdy=="string"?dy:gdy*yunitlength);
    
  st="";
  if (dox && gdx !== null && gdx>0) {
    for (x = origin[0]; x<=winxmax; x = x+gdx)
      if (x>=winxmin) st += " M"+x+","+gridymin+" "+x+","+(fqonlyy?height-origin[1]:gridymax);
    if (!fqonlyx) {
      for (x = origin[0]-gdx; x>=winxmin; x = x-gdx)
        if (x<=winxmax) st += " M"+x+","+gridymin+" "+x+","+(fqonlyy?height-origin[1]:gridymax);
    }
  }

  if (doy && gdy !== null && gdy>0) {
    if (!fqonlyy) {
      for (y = height-origin[1]; y<=winymax; y = y+gdy)
        if (y>=winymin) st += " M"+(fqonlyx?origin[0]:gridxmin)+","+y+" "+gridxmax+","+y;
    }
    for (y = height-origin[1]-gdy; y>=winymin; y = y-gdy)
      if (y<=winymax) st += " M"+(fqonlyx?origin[0]:gridxmin)+","+y+" "+gridxmax+","+y;
  }
  if (st !== '') {
    pnode = myCreateElementSVG("path");
    pnode.setAttribute("d",st);
    pnode.setAttribute("stroke-width", .5);
    pnode.setAttribute("stroke", gridstroke);
    pnode.setAttribute("fill", fill);
    svgpicture.appendChild(pnode);
  }
  
  st="";
  
  if (dox && ymin < 1e-8 && ymax > -1e-8) {
	  st="M"+(fqonlyx?origin[0]:winxmin)+","+(height-origin[1])+" "+winxmax+","+
    (height-origin[1]);
  }
  if (doy && xmin < 1e-8 && xmax > -1e-8) {
	  st += " M"+origin[0]+","+winymin+" "+origin[0]+","+(fqonlyy?height-origin[1]:winymax);
  }

  if (dox && dx>0 && ymin < 1e-8 && ymax > -1e-8) {
	  for (x = origin[0]; x<winxmax; x = x+dx)
	    if (x>=winymin) st += " M"+x+","+(height-origin[1]+ticklength)+" "+x+","+
		   (height-origin[1]-ticklength);
	  if (!fqonlyx) {
	    for (x = origin[0]-dx; x>winxmin; x = x-dx)
	      if (x<=winxmax) st += " M"+x+","+(height-origin[1]+ticklength)+" "+x+","+
	  	  	(height-origin[1]-ticklength);
	  }
  }
  if (doy && dy>0 && xmin < 1e-8 && xmax > -1e-8) {
	   if (!fqonlyy) {
	     for (y = height-origin[1]; y<winymax; y = y+dy)
	      if (y>=winymin) st += " M"+(origin[0]+ticklength)+","+y+" "+(origin[0]-ticklength)+","+y;
	   }

	  for (y = height-origin[1]-dy; y>winymin; y = y-dy)
	      if (y<=winymax) st += " M"+(origin[0]+ticklength)+","+y+" "+(origin[0]-ticklength)+","+y;

  }
  if (labels!=null) {
    ldx = dx/xunitlength;
    ldy = dy/yunitlength;
    lx = (xmin>0 || xmax<0?xmin:0);
    ly = (ymin>0 || ymax<0?ymin:0);
    lxp = (ly==0?"below":"above");
    lyp = (lx==0?"left":"right");
    var ddx = Math.floor(1-Math.log(ldx)/Math.log(10))+1;
    var ddy = Math.floor(1-Math.log(ldy)/Math.log(10))+1;
    if (ddy<0) { ddy = 0;}
    if (ddx<0) { ddx = 0;}
    if (dox && dx>0) {
      if (xf) {
        for (k = (doy?1:0); k*ldx<=xmax+1e-9; k++)
          if (k*ldx>=xmin-1e-9) ASaxisLabels(k, xf, [k*ldx,ly], lxp);
        if (!fqonlyx) {
          for (k = -1; k*ldx>=xmin-1e-9; k--)
            if (k*ldx<=xmax+1e-9) ASaxisLabels(k, xf, [k*ldx,ly], lxp);
        }
      } else {
	    for (x = (doy?ldx:0); x<=xmax; x = x+ldx)
	      if (x>=xmin) text([x,ly], rounddec(x, ddx),lxp);
	    if (!fqonlyx) {
	      for (x = -ldx; xmin<=x; x = x-ldx)
	        if (x<=xmax) text([x,ly], rounddec(x, ddx),lxp);
	    }
      }
    }
    if (doy && dy>0) {
      if (yf) {
        for (k = (dox?1:0); k*ldy<=ymax+1e-9; k++)
          if (k*ldy>=ymin-1e-9) ASaxisLabels(k, yf, [lx,k*ldy], lyp);
        if (!fqonlyy) {
          for (k = -1; k*ldy>=ymin-1e-9; k--)
            if (k*ldy<=ymax+1e-9) ASaxisLabels(k, yf, [lx,k*ldy], lyp);
        }
      } else {
	    for (y = (dox?ldy:0); y<=ymax; y = y+ldy)
	      if (y>=ymin) text([lx,y], rounddec(y, ddy), lyp);
      	    if (!fqonlyy) {
	      for (y = -ldy; ymin<=y; y = y-ldy)
	        if (y<=ymax) text([lx,y], rounddec(y, ddy), lyp);
	    }
      }
    }
  }
  if (st !== '') {
    pnode = myCreateElementSVG("path");
    pnode.setAttribute("d",st);
    pnode.setAttribute("stroke-width", .8);
    pnode.setAttribute("stroke", axesstroke);
    pnode.setAttribute("fill", fill);
    svgpicture.appendChild(pnode);
  }
}


function slopefield(fun,dx,dy) {
  var g = fun;
  if (typeof fun=="string") {
    g = makeMathFunction(fun.replace(/Math\./g,''), 'x|y');
  }
  if (dx==null) dx=1;
  if (dy==null) dy=1;
  ASregisterElement({kind:'slopefield', src:(typeof fun=="string"?fun:null), g:g, gdx:dx, gdy:dy});
  ASnoreg++;
  try {
    slopefield_(g,dx,dy);
  } finally {
    ASnoreg--;
  }
}
// Explorable field from precomputed values, so the equation need not be revealed.
// xs, ys: grid coordinates (ascending); vals[j][i]: [vx,vy] at (xs[i],ys[j]), or null if undefined.
// vector: describe as vectors rather than slopes. Call fieldend() after drawing the marks,
// so the marks aren't registered as separate line elements.
function fielddata(xs, ys, vals, vector) {
  ASregisterElement({kind:'slopefield', src:null, xs:xs, ys:ys, vals:vals, vector:!!vector});
  ASnoreg++;
}
function fieldend() {
  if (ASnoreg > 0) { ASnoreg--; }
}
function slopefield_(fun,dx,dy) {
  var g = fun;
  if (typeof fun=="string") {
    g = makeMathFunction(g.replace(/Math\./g,''), 'x|y');
  }
  var gxy,x,y,u,v,dz;
  if (dx==null) dx=1;
  if (dy==null) dy=1;
  dz = Math.sqrt(dx*dx+dy*dy)/6;
  var x_min = dx*Math.ceil(xmin/dx);
  var y_min = dy*Math.ceil(ymin/dy);
  for (x = x_min; x <= xmax; x += dx)
    for (y = y_min; y <= ymax; y += dy) {
      gxy = g({x:x,y:y});
      if (!isNaN(gxy)) {
        if (Math.abs(gxy)=="Infinity") {u = 0; v = dz;}
        else {u = dz/Math.sqrt(1+gxy*gxy); v = gxy*u;}
        line([x-u,y-v],[x+u,y+v]);
      }
    }
}

//ASCIIsvgAddon.js dumped here
function drawPictures() {
	drawPics()
}

function prepmath(str) {
    if (str === 'null') { return 'null';}
    return evalMathParser(str);
}

//ShortScript format:
//xmin,xmax,ymin,ymax,xscl,yscl,labels,xgscl,ygscl,width,height plotcommands(see blow)
//plotcommands: type,eq1,eq2,startmaker,endmarker,xmin,xmax,color,strokewidth,strokedash
function parseShortScript(sscript,gw,gh) {
	if (sscript==null) {
		sscript = picture.sscr;
		initialized = false;
	}

	var sa= sscript.split(",");

	if (gw && gh) {
		sa[9] = gw;
		sa[10] = gh;
		sscript = sa.join(",");
		picture.setAttribute("sscr", sscript);
	}
	if (picture.hasAttribute("viewBox")) {
		picture.setAttribute("viewBox", "0 0 "+sa[9]+" "+sa[10]);
	} else {
		picture.setAttribute("width", sa[9]);
		picture.setAttribute("height", sa[10]);
		picture.style.width = sa[9] + "px";
		picture.style.height = sa[10] + "px";
	}

	if (sa.length > 10) {
		commands = 'setBorder(5);';
		commands += 'width=' +sa[9] + '; height=' +sa[10] + ';';
		commands += 'initPicture(' + prepmath(sa[0]) +','+ prepmath(sa[1]) +','+ prepmath(sa[2]) +','+ prepmath(sa[3]) + ');';
		commands += 'axes(' + prepmath(sa[4]) +','+ prepmath(sa[5]) +','+ prepmath(sa[6]) +','+ prepmath(sa[7]) +','+ prepmath(sa[8]) + ');';

		var inx = 11;
		var varlet = '';
		var eqnlist = 'Graphs on the window x='+sa[0]+' to '+sa[1]+' and y='+sa[2]+' to '+sa[3]+': ';
    var origfontfill = fontfill;

		while (sa.length > inx+9) {
		   commands += 'stroke="' + sa[inx+7] + '";';
		   eqnlist += sa[inx+7] + " ";
		   commands += 'strokewidth="' + sa[inx+8] + '";'
		   //commands += 'strokedasharray="' + sa[inx+9] + '";'
		   if (sa[inx+9] != "") {
			   commands += 'strokedasharray="' + sa[inx+9].replace(/\s+/g,',') + '";';
			   if (sa[inx+9]=='2') {
			   	   eqnlist += "dotted ";
			   } else if (sa[inx+9]=='5') {
			   	   eqnlist += "dashed ";
			   } else if (sa[inx+9]=='5 2') {
			   	   eqnlist += "tight dashed ";
			   } else if (sa[inx+9]=='7 3 2 3') {
			   	   eqnlist += "dash-dot ";
			   }
		   }
		   if (sa[inx]=="slope") {
			   eqnlist += "slopefield where dy/dx="+sa[inx+1] + ". ";
			   commands += 'slopefield("' + sa[inx+1] + '",' + sa[inx+2] + ',' + sa[inx+2] + ');';
		   } else if (sa[inx]=="label") {
			   eqnlist += "label with text "+sa[inx+1] + ' at the point ('+sa[inx+5]+','+sa[inx+6]+'). ';
         sa[inx+1] = sa[inx+1].replace(/&(amp;)?x44;/g,',').replace(/&(amp;)?quot;/g,'\\"').replace(/\\"|"/g,'\\"');
			   commands += 'fontfill="'+sa[inx+7]+'";text(['+sa[inx+5]+','+sa[inx+6]+'],"'+sa[inx+1]+'");fontfill="'+origfontfill+'";';
		   } else {
			if (sa[inx]=="func") {
				eqnlist += "graph of y="+sa[inx+1];
				eqn = '"' + sa[inx+1] + '"';
				varlet = 'x';
			} else if (sa[inx] == "polar") {
				eqnlist += "polar graph of r="+sa[inx+1];
				eqn = '["cos(t)*(' + sa[inx+1] + ')","sin(t)*(' + sa[inx+1] + ')"]';
				varlet = 'r';
			} else if (sa[inx] == "param") {
				eqnlist += "parametric graph of x(t)="+sa[inx+1] + ", y(t)=" + sa[inx+2];
				eqn = '["' + sa[inx+1] + '","'+ sa[inx+2] + '"]';
				varlet = 't';
			}


			if (typeof eval(sa[inx+5]) == "number") {
		//	if ((sa[inx+5]!='null')&&(sa[inx+5].length>0)) {
				//commands += 'myplot(' + eqn +',"' + sa[inx+3] +  '","' + sa[inx+4]+'",' + sa[inx+5] + ',' + sa[inx+6]  +');';
				commands += 'plot(' + eqn +',' + prepmath(sa[inx+5]) + ',' + prepmath(sa[inx+6]) +',null,null,' + prepmath(sa[inx+3]) +  ',' + prepmath(sa[inx+4]) +');';
				eqnlist += " from " + varlet + '='+sa[inx+5]+ ' ';
				if (sa[inx+3]==1) {
					eqnlist += 'with an arrow ';
				} else if (sa[inx+3]==2) {
					eqnlist += 'with an open dot ';
				} else if (sa[inx+3]==3) {
					eqnlist += 'with a closed dot ';
				}
				eqnlist += "to "+varlet+'='+sa[inx+6]+' ';
				if (sa[inx+4]==1) {
					eqnlist += 'with an arrow ';
				} else if (sa[inx+4]==2) {
					eqnlist += 'with an open dot ';
				} else if (sa[inx+4]==3) {
					eqnlist += 'with a closed dot ';
				}
			} else {
				commands += 'plot(' + eqn +',null,null,null,null,' + prepmath(sa[inx+3]) +  ',' + prepmath(sa[inx+4])+');';
			}
			eqnlist += '. ';
		   }
		   inx += 10;
		}
    if (!picture.getAttribute("alt")) {
		  picture.setAttribute("alt",eqnlist);
    }

		try {
			eval(commands);
			if (!svgpicture.hasAttribute("viewBox") && !svgpicture.hasAttribute("data-nomag")) {
				addMagGlass();
			}
			ASmakeAccessible();
		} catch (e) {
			if (picture.hasAttribute("data-failedrenders")) {
				var fails = 1*picture.getAttribute("data-failedrenders");
				if (fails>3) {
					return commands;
				} else {
					picture.setAttribute("data-failedrenders",fails + 1);
				}
			} else {
				picture.setAttribute("data-failedrenders",1);
			}
			var tofixid = svgpicture.getAttribute("id");
			setTimeout(function() {switchTo(tofixid);parseShortScript(sscript,gw,gh)},100);
		}


		//picture.setAttribute("width", sa[9]);
		//picture.setAttribute("height", sa[9]);

		return commands;
	}
}




function drawPics(base) {
  var index, nd;
  base = base || document;
  pictures = base.getElementsByTagName("embed");
 // might be needed if setTimeout on parseShortScript isn't working

 	if (!ASnoSVG && isOldIE) {
	   try {
		  for (var i = 0; i < pictures.length; i++) {
		  	  if (pictures[i].getAttribute("sscr")!='' || pictures[i].getAttribute("script")!='') {
				  if (pictures[i].getSVGDocument().getElementById("root") == null) {
					setTimeout(drawPics,100);
					return;
				  }
			  }
		  }
	  } catch (e) {
		  setTimeout(drawPics,100);
		  return;
	  }
 	}
	var len = pictures.length;


  //for (index = 0; index < len; index++) {
	  //picture = ((!ASnoSVG && isOldIE) ? pictures[index] : pictures[0]);
	var sscr, src;
  for (index = len-1; index >=0; index--) {
	  picture = pictures[index];
	  if (!picture.hasAttribute("id") || picture.getAttribute("id")=="") {
	  	  picture.setAttribute("id", "ASnewid"+ASgraphidcnt);
	  	  ASgraphidcnt++;
	  }
	  if (!ASnoSVG) {
		  initialized = false;
		  sscr = picture.hasAttribute("data-sscr")?picture.getAttribute("data-sscr"):picture.getAttribute("sscr");
		  if ((sscr != null) && (sscr != "")) { //sscr from editor
			  try {
				  parseShortScript(sscr);
			  } catch (e) {}
		  } else {
			  src = picture.hasAttribute("data-script")?picture.getAttribute("data-script"):picture.getAttribute("script"); //script from showplot
			  if ((src!=null) && (src != "")) {
          src = src.replace(/<img[^>]*?alt="(.*?)".*?>/g,'`$1`');
				  try {
					  eval(prepWithMath(src));
					  if (!picture.hasAttribute("data-enlarged") && !picture.hasAttribute("data-nomag")) {
					  	  addMagGlass();
					  }
					  ASmakeAccessible();
				  } catch(err) {console.log(err+"\n"+src)}
			  }
		  }
	  } else {
		sscr = picture.hasAttribute("data-sscr")?picture.getAttribute("data-sscr"):picture.getAttribute("sscr");
		if ((sscr != null) && (sscr != "")) {
			  n = document.createElement('img');
			  n.setAttribute("style",picture.getAttribute("style"));
			  n.setAttribute("src",AScgiloc+'?sscr='+encodeURIComponent(sscr));
			  pn = picture.parentNode;
			  pn.replaceChild(n,picture);
		}
	  }
	}
}

// Adaptively sample g({x:x}) over [xa,xb].
// Starts with a uniform grid of nseed intervals, then recursively bisects any
// interval whose midpoint is more than tol screen pixels from the chord joining
// its endpoints. y is clamped to [ylo,yhi] before comparing, so wildly
// off-screen values don't trigger refinement. Intervals with an undefined
// endpoint or midpoint are also bisected (down to maxdepth) to locate domain
// edges and asymptotes.
// A jump discontinuity is flagged when an interval that is still too curved at
// maxdepth has both ends on screen, a vertical gap over jumppx pixels, and a
// gap that hasn't shrunk (>=90%) since the previous bisection.
// Returns {x:[], y:[], brk:{}} where brk[i] is set if point i shouldn't be
// connected to point i-1.
function adaptiveSamples(g, xa, xb, ylo, yhi) {
  var nseed = 47,  // odd, so grid is unlikely to line up with a function's period
      maxdepth = 7, tol = 0.25, maxpts = 2500, jumppx = 3;
  var xs = [xa], ys = [g({x:xa})], brk = {};
  var bad = function(y) { return isNaN(y) || Math.abs(y)=="Infinity"; };
  var sy = function(y) { return yunitlength*Math.min(yhi, Math.max(ylo, y)); };
  var rec = function(x0, y0, x1, y1, d, pgap) {
    var nan0 = bad(y0), nan1 = bad(y1);
    var gap = (nan0 || nan1) ? 0 : Math.abs(sy(y1) - sy(y0));
    if (d < maxdepth && xs.length < maxpts) {
      var xm = (x0 + x1)/2, ym = g({x:xm}), nanm = bad(ym), split;
      if (nan0 && nan1 && nanm) {
        split = false;
      } else if (nan0 || nan1 || nanm) {
        split = true;
      } else {
        split = Math.abs(sy(ym) - (sy(y0) + sy(y1))/2) > tol;
      }
      if (split) {
        rec(x0, y0, xm, ym, d+1, gap);
        rec(xm, ym, x1, y1, d+1, gap);
        return;
      }
    } else if (d >= maxdepth && !nan0 && !nan1 && gap > jumppx && gap >= 0.9*pgap &&
      y0 >= ylo && y0 <= yhi && y1 >= ylo && y1 <= yhi) {
      brk[xs.length] = true;
    }
    xs.push(x1);
    ys.push(y1);
  };
  var step = (xb - xa)/nseed, x0 = xa, y0 = ys[0], x1, y1;
  for (var j = 1; j <= nseed; j++) {
    x1 = (j == nseed) ? xb : xa + step*j;
    y1 = g({x:x1});
    rec(x0, y0, x1, y1, 0, 0);
    x0 = x1;
    y0 = y1;
  }
  return {x:xs, y:ys, brk:brk};
}

//modified by David Lippman from original in AsciiSVG.js by Peter Jipsen
//added min/max type:  0:nothing, 1:arrow, 2:open dot, 3:closed dot
//non-parametric plots use adaptive sampling unless points is given
function plot(fun,x_min,x_max,points,id,min_type,max_type) {
  ASnoreg++;  // the plot's pieces and endpoint dots are registered as one element
  try {
    return plot_(fun,x_min,x_max,points,id,min_type,max_type);
  } finally {
    ASnoreg--;
  }
}
function plot_(fun,x_min,x_max,points,id,min_type,max_type) {
  var pth = [];
  var vt0 = null, vt1 = null;  // first and last parameter values with a defined point
  var f = function(obj) { return obj.t }, g = fun;
  var name = null;
  var isParam = (typeof fun=="object");
  if (typeof fun=="string") {
    g = makeMathFunction(fun.replace(/Math\./g,''), 'x');
  }
  else if (typeof fun=="object") {
    f = makeMathFunction(fun[0].replace(/Math\./g,''), 't');
    g = makeMathFunction(fun[1].replace(/Math\./g,''), 't');
  }
  if (typeof x_min=="string") { name = x_min; x_min = xmin }
  else name = id;
  var min = (x_min==null?xmin:x_min);
  var max = (x_max==null?xmax:x_max);
  if (max <= min) { return null;}
  //else {
  var inc = max-min-0.000001*(max-min);
  var ts = [], gs = [], brk = {}, t, gt, i;
  if (!isParam && ymax > ymin) {
    var smp = adaptiveSamples(g, min, max-0.000001*(max-min), ymin-(ymax-ymin), ymax+(ymax-ymin));
    ts = smp.x;
    gs = smp.y;
    brk = smp.brk;
  } else {
    inc = (points==null?inc/200:inc/points);
    for (t = min; t <= max; t += inc) {
      ts.push(t);
      gs.push(isParam ? g({t:t}) : g({x:t}));
    }
  }
  var firstpts = [];  // first two points, for min_type

  for (i = 0; i < ts.length; i++) {
    t = ts[i];
    gt = gs[i];
    if (!(isNaN(gt)||Math.abs(gt)=="Infinity")) {
	    if (vt0 === null) { vt0 = t; }
	    vt1 = t;
	    if ((pth.length > 0) && (brk[i] || Math.abs(gt-pth[pth.length-1][1]) > (ymax-ymin))) {
		    // jump or too steep to connect; end this path and start another
		    if (pth.length > 1)  path(pth,name);
		    pth.length=0;
	    }
	    pth[pth.length] = [f({t:t}), gt];
	    if (firstpts.length < 2) { firstpts.push(pth[pth.length-1]); }
    }
  }
  if (pth.length > 1) path(pth,name);
  if (min_type == 1) {
	arrowhead(firstpts[1],firstpts[0]);
  } else if (min_type == 2) {
	dot(firstpts[0], "open");
  } else if (min_type == 3) {
	dot(firstpts[0], "closed");
  }
  if (max_type == 1) {
	arrowhead(pth[pth.length-2],pth[pth.length-1]);
  } else if (max_type == 2) {
	dot(pth[pth.length-1], "open");
  } else if (max_type == 3) {
	dot(pth[pth.length-1], "closed");
  }

  if (vt0 !== null) {
    var markName = function(mt) {
      return mt == 1 ? "arrow, graph continues" : (mt == 2 ? "open dot" : (mt == 3 ? "closed dot" : null));
    };
    ASregisterElement({kind:(isParam?'param':'plot'), f:f, g:g, src:(typeof fun=="function"?null:fun),
      min:min, max:max, t0:vt0, t1:vt1, startMark:markName(min_type), endMark:markName(max_type)}, true);
  }

  return p;
  //}
}

//end ASCIIsvgAddon.js dump

/*
 * Keyboard / screen reader exploration of graphs.
 *
 * Drawing functions register what they draw in svgpicture._as.els (see ASregisterElement).
 * Once a graph is drawn, ASmakeAccessible makes the svg focusable. Enter starts
 * exploring: the window and number of elements are announced and the first
 * element is selected, with a marker drawn on it.
 *   Arrow keys           move along the element (Right/Up forward, Left/Down back)
 *   Shift+arrow          smaller step
 *   PageUp / PageDown    larger step
 *   Home / End           jump to the ends of the element
 *   Alt+Down / Alt+Up    next / previous graph element
 *   Enter                describe the current element again
 *   W, H                 describe the window, help
 *   Escape               stop exploring
 */
var ASnoreg = 0;  // >0 while drawing things that shouldn't be explorable elements

function ASregisterElement(el, force) {
  if ((ASnoreg > 0 && !force) || !svgpicture || !svgpicture._as) { return; }
  if (el.stroke === undefined) { el.stroke = stroke; }
  el.dash = strokedasharray;
  el.fill = fill;
  var els = svgpicture._as.els;
  if (el.node) {
    for (var i = 0; i < els.length; i++) {
      if (els[i].node === el.node) { els[i] = el; return; }
    }
  }
  els.push(el);
}

// Inequality shading is drawn as a separate filled shape that isn't explorable.
// ASmarkShadeStart is called before the boundary is drawn, and ASshaded afterward
// notes the shading direction on the boundary's element(s) so it gets described.
var ASshadeStart = 0;
function ASmarkShadeStart() {
  ASshadeStart = (svgpicture && svgpicture._as) ? svgpicture._as.els.length : 0;
}
function ASshaded(direction) {
  if (!svgpicture || !svgpicture._as) { return; }
  var els = svgpicture._as.els;
  for (var i = ASshadeStart; i < els.length; i++) { els[i].shaded = direction; }
}

function ASendMarkers() { // markers currently set for line/arc ends
  if (marker=="dot") { return {s:"dot", e:"dot"}; }
  if (marker=="arrowdot") { return {s:"dot", e:"dot and arrow"}; }
  if (marker=="arrow") { return {s:null, e:"arrow"}; }
  return {s:null, e:null};
}

var ASkindLabels = {plot:'curve', param:'parametric curve', line:'line segment', path:'path',
  curve:'curve', loop:'loop', circle:'circle', ellipse:'ellipse', rect:'rectangle',
  sector:'sector', arc:'arc', dot:'point', text:'text label', slopefield:'slope field'};

var ASpalette = {black:[0,0,0], gray:[128,128,128], white:[255,255,255], red:[255,0,0],
  orange:[255,165,0], yellow:[255,255,0], green:[0,128,0], 'light green':[0,255,0],
  blue:[0,0,255], cyan:[0,255,255], purple:[128,0,128], magenta:[255,0,255],
  pink:[255,192,203], brown:[165,42,42]};

function ASformatNumber(v, dec) { // number to text, with a real minus sign so it is read aloud
  var r = Number(v.toFixed(dec));
  if (r === 0) { r = 0; }
  return String(r).replace('-', '−');
}

function ASdecimalsForRange(range) { // decimals to show for a given axis range
  if (!(range > 0)) { return 2; }
  return Math.max(0, Math.min(6, 3 - Math.floor(Math.log10(range))));
}

function ASroundStepDown(v) { // round down to 1, 2 or 5 times a power of 10
  var e = Math.pow(10, Math.floor(Math.log10(v)));
  var m = v/e;
  return parseFloat(((m >= 5 ? 5 : (m >= 2 ? 2 : 1))*e).toPrecision(12));
}

function AStenthOf(n) { return parseFloat((n/10).toPrecision(12)); }

function ASdecimalsForStep(step) { return Math.max(0, Math.min(8, -Math.floor(Math.log10(step)))); }

function ASisInvalid(y) { return isNaN(y) || Math.abs(y) == Infinity; }

function ASmapColorName(c) {
  if (c == null || c === 'none') { return 'black'; }
  c = String(c).trim().toLowerCase();
  if (/^[a-z]+$/.test(c)) { return c; }
  var rgb = null, m;
  if ((m = c.match(/^#([0-9a-f]{3})$/))) {
    rgb = [parseInt(m[1].charAt(0)+m[1].charAt(0),16), parseInt(m[1].charAt(1)+m[1].charAt(1),16), parseInt(m[1].charAt(2)+m[1].charAt(2),16)];
  } else if ((m = c.match(/^#([0-9a-f]{6})$/))) {
    rgb = [parseInt(m[1].substr(0,2),16), parseInt(m[1].substr(2,2),16), parseInt(m[1].substr(4,2),16)];
  } else if ((m = c.match(/^rgba?\(\s*(\d+)[\s,]+(\d+)[\s,]+(\d+)/))) {
    rgb = [+m[1], +m[2], +m[3]];
  }
  if (!rgb) { return c; }
  var best = 'black', bd = Infinity;
  for (var name in ASpalette) {
    var d = Math.pow(rgb[0]-ASpalette[name][0],2) + Math.pow(rgb[1]-ASpalette[name][1],2) + Math.pow(rgb[2]-ASpalette[name][2],2);
    if (d < bd) { bd = d; best = name; }
  }
  return best;
}

function ASspeakableEquation(s) { // make an equation a bit friendlier to hear
  return String(s)
    .replace(/\^2(?![\d.])/g, ' squared')
    .replace(/\^3(?![\d.])/g, ' cubed')
    .replace(/\^\(([^()]*)\)/g, ' to the power $1 ')
    .replace(/\^(-?[\w.]+)/g, ' to the power $1 ')
    .replace(/\*/g, ' times ').replace(/\//g, ' over ')
    .replace(/\+/g, ' plus ').replace(/-/g, ' minus ')
    .replace(/\s+/g, ' ').trim();
}

function ASstyle(el) {
  var s = [];
  var dash = ASdashType(el.dash);
  if (dash) { s.push(dash); }
  s.push(ASmapColorName(el.stroke));
  return s.join(' ');
}

// stroke-dasharray value to 'dotted', 'dashed' or null. Values like "", "none" and "0" are solid lines.
function ASdashType(d) {
  if (d == null) { return null; }
  var nums = String(d).match(/\d*\.?\d+/g);
  if (!nums) { return null; }
  var vals = nums.map(parseFloat);
  if (!vals.some(function(x) { return x > 0; })) { return null; }
  return vals[0] <= 2 ? 'dotted' : 'dashed';
}

function ASinWindow(p, v) { // is the point in the visible graph window?
  var ex_ = (v.xmax - v.xmin)*1e-6, ey_ = (v.ymax - v.ymin)*1e-6;
  return !!p && p[1] != null && p[0] >= v.xmin - ex_ && p[0] <= v.xmax + ex_ &&
    p[1] >= v.ymin - ey_ && p[1] <= v.ymax + ey_;
}

// If the natural starting point is off the graph window, start at the first visible point instead.
function ASfirstVisiblePoint(ex, v) {
  if (ASinWindow(ex.pos(ex.start), v)) { return; }
  var lo = ex.closed ? 0 : ex.lowT, hi = ex.closed ? ex.T : ex.highT;
  for (var i = 1; i <= 400; i++) {
    var t = lo + (hi - lo)*i/400;
    if (ASinWindow(ex.pos(t), v)) {
      if (ex.snap && hi > lo) {  // tidy onto the step grid if that is still visible
        var tt = parseFloat((Math.ceil(t/ex.steps.normal - 1e-9)*ex.steps.normal).toPrecision(12));
        if (tt <= hi && ASinWindow(ex.pos(tt), v)) { t = tt; }
      }
      ex.start = t;
      return;
    }
  }
}

function ASpointText(q, ex) {
  return ex.xn + ' ' + ex.fx(q[0]) + ', ' + ex.yn + ' ' + ex.fy(q[1]);
}

function ASplainText(st) {
  return String(st).replace(/<[^>]*>/g, '').replace(/`/g, '');
}

// Slope fields are traced on the grid of slope marks: rows of fixed y, left to right.
function ASprepareSlopeGrid(el, ex, v) {
  var gx = el.gdx, gy = el.gdy, xs = [], ys = [], i, r = function(n) { return parseFloat(n.toPrecision(12)); };
  if (el.vals) {  // precomputed grid
    xs = el.xs; ys = el.ys;
    gx = xs.length > 1 ? xs[1] - xs[0] : v.xmax - v.xmin;
    gy = ys.length > 1 ? ys[1] - ys[0] : v.ymax - v.ymin;
  } else {
    if (!(gx > 0) || !(gy > 0)) { return; }
    var x0 = Math.ceil(v.xmin/gx - 1e-9), y0 = Math.ceil(v.ymin/gy - 1e-9);
    for (i = x0; i*gx <= v.xmax + 1e-9*gx; i++) { xs.push(r(i*gx)); }
    for (i = y0; i*gy <= v.ymax + 1e-9*gy; i++) { ys.push(r(i*gy)); }
  }
  if (!xs.length || !ys.length) { return; }
  ex.grid = {xs:xs, ys:ys, ix:0, iy:0};
  ex.dx = Math.max(ex.dx, ASdecimalsForStep(gx));
  ex.dy = Math.max(ex.dy, ASdecimalsForStep(gy));
  ASsetSlopePoint(el, ex, 0, 0);
}

function ASsetSlopePoint(el, ex, ix, iy) {
  var G = ex.grid;
  G.ix = ix; G.iy = iy;
  ex.at = [G.xs[ix], G.ys[iy]];
}

function ASslopeText(el, ex) {
  var G = ex.grid, s, t, d = Math.max(2, Math.min(6, ex.dx));
  if (el.vals) {
    var w = el.vals[G.iy] && el.vals[G.iy][G.ix];
    if (!w) { return el.vector ? 'vector undefined' : 'slope undefined'; }
    if (el.vector) { return 'vector ' + ASformatNumber(w[0], d) + ', ' + ASformatNumber(w[1], d); }
    if (w[0] === 0) { return 'slope ' + (w[1] === 0 ? 'undefined' : 'vertical'); }
    s = w[1]/w[0];
  } else {
    s = el.g({x:G.xs[G.ix], y:G.ys[G.iy]});
  }
  if (isNaN(s)) { t = 'undefined'; }
  else if (Math.abs(s) === Infinity) { t = 'vertical'; }
  else { t = ASformatNumber(s, d); }
  return 'slope ' + t;
}

// Right/Up and Left/Down step through the points of a row (continuing on to the next row);
// Shift+arrow and PageUp/PageDown move to the next/previous row; Home/End go to the ends of the row.
function ASslopeKey(a, k, shift) {
  var el = a.els[a.cur], ex = ASprepareElement(el, a), G = ex.grid;
  var nx = G.xs.length, ny = G.ys.length, ix = G.ix, iy = G.iy, note = '', dir = 0, rowMove = false;
  switch (k) {
    case 'ArrowRight': case 'ArrowUp': dir = 1; rowMove = shift; break;
    case 'ArrowLeft': case 'ArrowDown': dir = -1; rowMove = shift; break;
    case 'PageUp': dir = 1; rowMove = true; break;
    case 'PageDown': dir = -1; rowMove = true; break;
    case 'Home': ix = 0; note = 'Start of row. '; break;
    case 'End': ix = nx - 1; note = 'End of row. '; break;
    default: return null;
  }
  if (dir) {
    if (rowMove) {
      if (iy + dir < 0 || iy + dir >= ny) { return (dir > 0 ? 'Top' : 'Bottom') + ' row of the slope field.'; }
      iy += dir;
      note = 'Row at ' + ex.yn + ' ' + ex.fy(G.ys[iy]) + '. ';
    } else if (ix + dir >= 0 && ix + dir < nx) {
      ix += dir;
    } else if (iy + dir >= 0 && iy + dir < ny) {
      iy += dir;
      ix = dir > 0 ? 0 : nx - 1;
      note = 'New row. ';
    } else {
      return dir > 0 ? 'Last slope mark in the field.' : 'First slope mark in the field.';
    }
  }
  ASsetSlopePoint(el, ex, ix, iy);
  ASplaceMarker(a);
  return note + ASpositionText(el, a, 0);
}

// Work out how to move along an element. Cached on the element.
function ASprepareElement(el, a) {
  if (el.ex) { return el.ex; }
  var v = a.view, kind = el.kind;
  var ex = {isStatic:false, snap:false, closed:false, verts:[], sign:1, at:null,
    dx:ASdecimalsForRange(v.xmax - v.xmin), dy:ASdecimalsForRange(v.ymax - v.ymin),
    xn:v.xname || 'x', yn:v.yname || 'y'};
  ex.fx = function(n) { return ASformatAxisValue(n, ex.dx, v.xfrac); };
  ex.fy = function(n) { return ASformatAxisValue(n, ex.dy, v.yfrac); };
  el.ex = ex;
  if (kind === 'dot' || kind === 'text' || kind === 'slopefield') {
    ex.isStatic = true;
    ex.at = el.at || null;
    if (kind === 'slopefield' && (el.g || el.vals)) { ASprepareSlopeGrid(el, ex, v); }
    return ex;
  }
  if (kind === 'plot' || kind === 'param') {
    var W;
    ex.snap = true;
    if (kind === 'plot') {
      ex.lowT = Math.max(el.t0, v.xmin);
      ex.highT = Math.min(el.t1, v.xmax);
      if (ex.lowT > ex.highT) { ex.lowT = el.t0; ex.highT = el.t1; }
      ex.pos = function(t) {
        var y = el.g({x:t});
        return ASisInvalid(y) ? null : [t, y];
      };
      ex.lowName = 'Left end';
      ex.highName = 'Right end';
    } else {
      ex.lowT = el.t0;
      ex.highT = el.t1;
      ex.pos = function(t) {
        var x = el.f({t:t}), y = el.g({t:t});
        return (ASisInvalid(x) || ASisInvalid(y)) ? null : [x, y];
      };
      ex.lowName = 'Start';
      ex.highName = 'End';
    }
    // function plots step relative to the x window (-5..5 gives 0.5); parametric plots relative to t
    W = (kind === 'plot') ? v.xmax - v.xmin : ex.highT - ex.lowT;
    if (!(W > 0)) { W = 1; }
    var n = ASroundStepDown(W/20);
    ex.steps = {normal:n, small:AStenthOf(n), large:ASroundStepDown(W/4)};
    if (kind === 'plot' && v.xfrac) {
      ex.steps = ASfractionSteps(v.xfrac, W);
      ex.xsteps = ex.steps;  // land on exact multiples of the step, not offsets from the window edge
    }
    ex.dx = Math.max(ex.dx, ASdecimalsForStep(ex.steps.small));
    ex.lowMark = (ex.lowT === el.t0) ? el.startMark : null;
    ex.highMark = (ex.highT === el.t1) ? el.endMark : null;
    ex.start = ex.lowT;
    if (kind === 'plot') {  // y is a function of x, with t being x
      ex.xfunc = true;
      ex.xlo = ex.lowT;
      ex.xhi = ex.highT;
      ex.sAtX = function(x) { return x; };
    }
    ASfirstVisiblePoint(ex, v);
    return ex;
  }
  // everything else is a shape in the svg; move along it by arc length (pixels)
  var node = el.node, T = NaN;
  try { T = node.getTotalLength(); } catch (e) {}
  if (!(T > 0)) {
    ex.isStatic = true;
    ex.at = (el.pts && el.pts[0]) || el.c || el.from || el.p || null;
    return ex;
  }
  ex.T = T;
  ex.pos = function(s) {
    var pt = node.getPointAtLength(Math.min(Math.max(s, 0), T));
    return [(pt.x - v.ox)/v.xu, (v.h - pt.y - v.oy)/v.yu];
  };
  var closed = (kind === 'circle' || kind === 'ellipse' || kind === 'rect' || kind === 'sector');
  if (el.pts && el.pts.length > 2) {
    var f = el.pts[0], l = el.pts[el.pts.length-1];
    if (f[0] === l[0] && f[1] === l[1]) { closed = true; }
  } else if (kind === 'path' && node.getAttribute('d') && /z\s*$/i.test(node.getAttribute('d'))) {
    closed = true;
  }
  ex.closed = closed;
  if (el.polyline && el.pts) { // corners are places to stop
    var cum = 0, px = function(q) { return [q[0]*v.xu + v.ox, v.h - q[1]*v.yu - v.oy]; };
    var pp = el.pts.map(px), mono = true, mdir = 0;
    for (var i = 1; i < pp.length; i++) { // is x monotone, i.e. does it look like a function graph?
      var dxs = Math.sign(pp[i][0] - pp[i-1][0]);
      if (dxs !== 0) {
        if (mdir !== 0 && dxs !== mdir) { mono = false; break; }
        mdir = dxs;
      }
    }
    // Points on a function-like path are usually just samples of a curve, so only
    // count sharp turns as corners. Markers drawn on every point count as corners too.
    var minTurn = (mono ? 30 : 10)*Math.PI/180;
    for (var i = 1; i < pp.length - 1; i++) {
      var d0 = [pp[i][0]-pp[i-1][0], pp[i][1]-pp[i-1][1]];
      var d1 = [pp[i+1][0]-pp[i][0], pp[i+1][1]-pp[i][1]];
      cum += Math.hypot(d0[0], d0[1]);
      var turn = Math.abs(Math.atan2(d0[0]*d1[1]-d0[1]*d1[0], d0[0]*d1[0]+d0[1]*d1[1]));
      if (el.vertMark || turn > minTurn) { ex.verts.push(cum); }
    }
  }
  ex.steps = {normal:T/20, small:T/100, large:T/4};
  if (closed) {
    ex.start = 0;
    ex.lowT = ex.highT = 0;
  } else {
    var p0 = ex.pos(0), p1 = ex.pos(T);
    var ddx = (p1[0]-p0[0])*v.xu, ddy = (p1[1]-p0[1])*v.yu;
    var horiz = Math.abs(ddx) >= Math.abs(ddy);
    if (!horiz && ddx !== 0 && Math.abs(ddx) >= 0.1*T) {
      // A path whose x never reverses is a function-like graph even if it is steep
      // (e.g. one branch of 1/((x-2)(x-3))); keep Left/Right matching x.
      var sgx = ddx > 0 ? 1 : -1, lastx = null, mono = true, tx = 0.01/v.xu;
      for (var k = 0; k <= 200 && mono; k++) {
        var xk = ex.pos(T*k/200)[0];
        if (lastx !== null && (xk - lastx)*sgx < -tx) { mono = false; }
        lastx = xk;
      }
      if (mono) { horiz = true; }
    }
    ex.sign = (horiz ? ddx : ddy) >= 0 ? 1 : -1;  // so that Right/Up goes toward the right/top end
    ex.lowT = ex.sign > 0 ? 0 : T;
    ex.highT = ex.sign > 0 ? T : 0;
    ex.lowName = horiz ? 'Left end' : 'Bottom end';
    ex.highName = horiz ? 'Right end' : 'Top end';
    ex.lowMark = ex.sign > 0 ? el.startMark : el.endMark;
    ex.highMark = ex.sign > 0 ? el.endMark : el.startMark;
    ex.start = ex.lowT;
    // If it looks like y is a function of x (x never reverses and it isn't nearly vertical),
    // step through round x values based on the x window, like plot() does.
    var xmono = true, last = null, tolx = 0.01/v.xu;
    for (var j = 0; j <= 200 && xmono; j++) {
      var xx = ex.pos(T*j/200)[0];
      if (last !== null && (xx - last)*ex.sign < -tolx) { xmono = false; }
      last = xx;
    }
    if (xmono && Math.abs(ddx) >= 0.1*T) {
      var Wx = v.xmax - v.xmin, nx = ASroundStepDown(Wx/20);
      ex.xsteps = v.xfrac ? ASfractionSteps(v.xfrac, Wx) : {normal:nx, small:AStenthOf(nx), large:ASroundStepDown(Wx/4)};
      ex.xlo = Math.min(p0[0], p1[0]);
      ex.xhi = Math.max(p0[0], p1[0]);
      ex.dx = Math.max(ex.dx, ASdecimalsForStep(ex.xsteps.small));
      ex.lowName = "Left end"; ex.highName = "Right end";
      ex.xfunc = true;
      if (el.polyline && el.pts && !el.vertMark) {  // report y from a smooth curve through the samples, not the chords
        var smo = ASsmoothFunction(el.pts, v);
        if (smo) {
          var rawpos = ex.pos;
          ex.pos = function(s) {
            var p = rawpos(s), y = p ? smo.f(p[0]) : null;
            return y === null ? p : [p[0], y];
          };
          ex.smooth = true;
          ex.hs = smo.hs;
        }
      }
      ex.sAtX = function(nx) { // x is monotone along the path, so bisect for the position
        if (nx <= ex.xlo || nx >= ex.xhi) { return ((nx <= ex.xlo) === (ex.sign > 0)) ? 0 : ex.T; }
        var sa = 0, sb = ex.T;
        for (var it = 0; it < 40; it++) {
          var sm = (sa + sb)/2;
          if ((ex.pos(sm)[0] - nx)*ex.sign < 0) { sa = sm; } else { sb = sm; }
        }
        return (sa + sb)/2;
      };
    }
  }
  ASfirstVisiblePoint(ex, v);
  return ex;
}

function ASdescribe(el, a) { // what the element is, without a position
  return ASdescribeBase(el, a) + (el.shaded ? ', shaded ' + el.shaded : '');
}

function ASdescribeBase(el, a) {
  var ex = ASprepareElement(el, a), v = a.view;
  var st = ASstyle(el), filled = (el.fill && el.fill !== 'none');
  var P = function(q) { return ASpointText(q, ex); };
  var r = function(n) { return ASformatNumber(n, ex.dx); };
  switch (el.kind) {
    case 'plot':
      var s = st + ' curve';
      if (el.src != null) { s += ', ' + ex.yn + ' equals ' + ASspeakableEquation(el.src); }
      if (el.min !== v.xmin || el.max !== v.xmax) {
        s += ', for ' + ex.xn + ' from ' + r(el.min) + ' to ' + r(el.max);
      }
      return s;
    case 'param':
      return st + ' parametric curve, ' + ex.xn + ' equals ' + ASspeakableEquation(el.src[0]) + ', ' + ex.yn + ' equals ' + ASspeakableEquation(el.src[1]) +
        ', for t from ' + r(el.min) + ' to ' + r(el.max);
    case 'line':
      return st + ' line segment from ' + P(el.pts[0]) + ' to ' + P(el.pts[1]);
    case 'path':
    case 'curve':
      return st + ' ' + (el.kind === 'path' ? 'path' : 'curve') +
        (el.pts && ex.verts.length > 0 ? ' through ' + el.pts.length + ' points' : '') + (filled ? ', filled' : '');
    case 'loop':
      return st + ' loop';
    case 'circle':
      return st + ' circle, center ' + P(el.c) + ', radius ' + r(el.r) + (filled ? ', filled' : '');
    case 'ellipse':
      return st + ' ellipse, center ' + P(el.c) + ', horizontal radius ' + r(el.rx) +
        ', vertical radius ' + r(el.ry) + (filled ? ', filled' : '');
    case 'rect':
      return st + ' rectangle from ' + P(el.p) + ' to ' + P(el.q) + (filled ? ', filled' : '');
    case 'sector':
      return st + ' sector, center ' + P(el.c) + ', radius ' + r(el.r) + (filled ? ', filled' : '');
    case 'arc':
      return st + ' arc from ' + P(el.from) + ' to ' + P(el.to);
    case 'dot':
      return ASmapColorName(el.stroke) + ' ' + (el.typ === 'open' ? 'open ' : (el.typ === 'closed' ? 'closed ' : '')) +
        'point' + (el.label != null ? ', labeled ' + ASplainText(el.label) : '');
    case 'text':
      return ASmapColorName(el.stroke) + ' text label "' + ASplainText(el.str) + '"';
    case 'slopefield':
      return (el.vector ? 'vector field' : 'slope field') + (el.src != null ? ', slope equals ' + ASspeakableEquation(el.src) : '');
  }
  return el.kind;
}

function ASpositionText(el, a, t) { // where we are on the element
  var ex = ASprepareElement(el, a), v = a.view;
  if (ex.isStatic) {
    if (!ex.at) { return ''; }
    return ASpointText(ex.at, ex) + (ex.grid ? ', ' + ASslopeText(el, ex) : '');
  }
  var p = ex.pos(t);
  if (!p) {
    return el.kind === 'plot' ? ex.xn + ' ' + ex.fx(t) + ', undefined' : 'undefined';
  }
  var s = ASpointText(p, ex);
  var ex_ = (v.xmax - v.xmin)*1e-6, ey_ = (v.ymax - v.ymin)*1e-6;
  if (p[0] < v.xmin - ex_ || p[0] > v.xmax + ex_ || p[1] < v.ymin - ey_ || p[1] > v.ymax + ey_) {
    s += ', outside the graph window';
  }
  return s;
}

function ASwindowDescription(a) {
  var v = a.view, dx = ASdecimalsForRange(v.xmax - v.xmin), dy = ASdecimalsForRange(v.ymax - v.ymin);
  return 'Graph window: ' + (v.xname || 'x') + ' from ' + ASformatAxisValue(v.xmin, dx, v.xfrac) + ' to ' + ASformatAxisValue(v.xmax, dx, v.xfrac) +
    ', ' + (v.yname || 'y') + ' from ' + ASformatAxisValue(v.ymin, dy, v.yfrac) + ' to ' + ASformatAxisValue(v.ymax, dy, v.yfrac) + '.';
}

function ASsummary(a) {
  var counts = {}, order = [], n = a.els.length;
  a.els.forEach(function(el) {
    var l = ASkindLabels[el.kind] || el.kind;
    if (!counts[l]) { counts[l] = 0; order.push(l); }
    counts[l]++;
  });
  return n + ' graph element' + (n == 1 ? '' : 's') + ': ' + order.map(function(l) {
    return counts[l] + ' ' + l + (counts[l] == 1 ? '' : 's');
  }).join(', ') + '.';
}

var AShelptext = 'Arrow keys move along the current element. Hold shift for smaller steps. ' +
  'Page up and page down take larger steps. Home and end jump to the ends. ' +
  'N and P jump to the next and previous point of interest: an intercept or a local maximum or minimum. ' +
  'On a slope field, arrow keys step through the slope marks, announcing x, y and slope; ' +
  'shift arrow or page up and page down move to the next or previous row. ' +
  'Alt down and alt up move to the next and previous graph element. ' +
  'Enter describes the current element. W describes the graph window. Escape leaves the graph.';

function ASannounce(svg, msg) {
  var live = svg._asLive;
  if (!live) { return; }
  live._flip = !live._flip;  // so repeating the same message is still announced
  live.textContent = msg + (live._flip ? ' ' : '');
}

function ASplaceMarker(a) { // put the visible marker at the current position
  var svg = a.svg, el = a.els[a.cur], ex = ASprepareElement(el, a), v = a.view;
  if (!a.mk || !a.mk.isConnected) {
    var g = myCreateElementSVG('g'), c;
    g.setAttribute('pointer-events', 'none');
    c = myCreateElementSVG('circle');
    c.setAttribute('r', 7); c.setAttribute('fill', 'none');
    c.setAttribute('stroke', 'white'); c.setAttribute('stroke-width', 4);
    g.appendChild(c);
    c = myCreateElementSVG('circle');
    c.setAttribute('r', 7); c.setAttribute('fill', 'none');
    c.setAttribute('stroke', '#c00'); c.setAttribute('stroke-width', 2);
    g.appendChild(c);
    c = myCreateElementSVG('circle');
    c.setAttribute('r', 2); c.setAttribute('fill', '#c00');
    g.appendChild(c);
    c = myCreateElementSVG('text');  // visible coordinates, drawn with a white halo so it stays readable
    c.setAttribute('font-size', 13); c.setAttribute('font-family', 'sans-serif');
    c.setAttribute('fill', '#c00'); c.setAttribute('stroke', 'white');
    c.setAttribute('stroke-width', 3); c.setAttribute('stroke-linejoin', 'round');
    c.setAttribute('paint-order', 'stroke'); c.setAttribute('aria-hidden', 'true');
    g.appendChild(c);
    g._label = c;
    svg.appendChild(g);
    a.mk = g;
  }
  var p = ex.isStatic ? ex.at : ex.pos(a.t);
  var eps = 1e-6;
  if (!p || p[1] == null || p[0] < v.xmin - (v.xmax-v.xmin)*eps || p[0] > v.xmax + (v.xmax-v.xmin)*eps ||
      p[1] < v.ymin - (v.ymax-v.ymin)*eps || p[1] > v.ymax + (v.ymax-v.ymin)*eps) {
    a.mk.style.display = 'none';
  } else {
    a.mk.style.display = '';
    var px = p[0]*v.xu + v.ox, py = v.h - p[1]*v.yu - v.oy;
    a.mk.setAttribute('transform', 'translate(' + px + ',' + py + ')');
    var lbl = a.mk._label;
    lbl.textContent = '(' + ex.fx(p[0]) + ', ' + ex.fy(p[1]) + ')';
    var tw = 0;
    try { tw = lbl.getComputedTextLength(); } catch (e) {}
    if (!(tw > 0)) { tw = lbl.textContent.length*7.5; }
    // put the label up and to the right of the point, flipping to stay inside the graph
    var left = (px + 10 + tw > v.w) && (px - 10 - tw >= 0);
    var below = (py - 12 < 12);
    lbl.setAttribute('text-anchor', left ? 'end' : 'start');
    lbl.setAttribute('x', left ? -10 : 10);
    lbl.setAttribute('y', below ? 20 : -10);
  }
}

// x of the current position, if the current element is a function of x (else null)
function AScurrentX(a) {
  var el = a.els[a.cur], ex = ASprepareElement(el, a);
  if (!ex.xfunc) { return null; }
  if (ex.xsteps) { return (a.sx && a.sx.el === el && a.sx.t === a.t) ? a.sx.x : ex.pos(a.t)[0]; }
  return a.t;
}

// Select element i and put the marker at its start (or at x=keepX if given and the element
// is also a function of x with a visible point there). Returns the announcement.
function ASselectElement(a, i, keepX) {
  var n = a.els.length;
  a.cur = ((i % n) + n) % n;
  var el = a.els[a.cur], ex = ASprepareElement(el, a);
  a.t = ex.isStatic ? 0 : ex.start;
  a.sx = null;
  if (keepX != null && ex.xfunc && keepX >= ex.xlo - 1e-9 && keepX <= ex.xhi + 1e-9) {
    var kx = Math.min(Math.max(keepX, ex.xlo), ex.xhi), kt = ex.sAtX(kx);
    if (ASinWindow(ex.pos(kt), a.view)) {
      a.t = kt;
      if (ex.xsteps) { a.sx = {el:el, t:kt, x:kx}; }
    }
  }
  ASplaceMarker(a);
  return ASdescribeFully(a, true);
}

function ASdescribeFully(a, withEnds) {
  var el = a.els[a.cur], ex = ASprepareElement(el, a);
  var parts = ['Element ' + (a.cur+1) + ' of ' + a.els.length + ': ' + ASdescribe(el, a)];
  if (withEnds && !ex.isStatic && !ex.closed && ex.lowMark && Math.abs(a.t - ex.lowT) < ex.steps.small*1e-6) {
    parts.push(ex.lowMark + ' at the ' + ex.lowName.toLowerCase());
  }
  var pos = ASpositionText(el, a, a.t);
  if (pos) { parts.push(pos); }
  return parts.join('. ') + '.';
}

function ASendpointText(ex, high) {
  var mark = high ? ex.highMark : ex.lowMark;
  return (high ? ex.highName : ex.lowName) + (mark ? ', ' + mark : '');
}

function ASmoveAlong(a, dir, size) { // dir: +1 forward (right/up), -1 back
  var el = a.els[a.cur], ex = ASprepareElement(el, a);
  if (ex.isStatic) {
    return el.kind === 'dot' || el.kind === 'text' ?
      'This is a single ' + ASkindLabels[el.kind] + '. Use alt up or alt down to move to another element.' :
      'This element cannot be traced. Use alt up or alt down to move to another element.';
  }
  var t = a.t, st = ex.steps[size], nt, ds = 0, eps = ex.steps.small*1e-6;
  var mn = Math.min(ex.lowT, ex.highT), mx = Math.max(ex.lowT, ex.highT);
  if (ex.snap) {  // land on multiples of the step size
    nt = dir > 0 ? (Math.floor(t/st + 1e-9) + 1)*st : (Math.ceil(t/st - 1e-9) - 1)*st;
    nt = parseFloat(nt.toPrecision(12));
  } else if (ex.xsteps) {  // y is a function of x: land on round x values
    var xst = ex.xsteps[size];
    // use the x we snapped to last time if we are still there; reading it back from the svg is slightly inexact
    var cx = (a.sx && a.sx.el === el && a.sx.t === t) ? a.sx.x : ex.pos(t)[0];
    var nx = dir > 0 ? (Math.floor(cx/xst + 1e-4) + 1)*xst : (Math.ceil(cx/xst - 1e-4) - 1)*xst;
    nx = Math.min(Math.max(parseFloat(nx.toPrecision(12)), ex.xlo), ex.xhi);
    nt = ex.sAtX(nx);
    ds = nt - t;
  } else {
    ds = dir*ex.sign*st;
    nt = t + ds;
  }
  var wrapped = false, vertex = false;
  if (ex.closed) {
    var raw = nt;
    nt = ((nt % ex.T) + ex.T) % ex.T;
    wrapped = (raw !== nt);
  } else if (nt < mn) { nt = mn; } else if (nt > mx) { nt = mx; }
  // don't step over a corner (but on a function-like path, sharp turns are mostly just sampling at a peak)
  if (!ex.snap && !wrapped && ex.verts.length && (!ex.xsteps || el.vertMark)) {
    var best = null;
    ex.verts.forEach(function(vv) {
      if (ds > 0 && vv > t + 0.01 && vv < nt - 0.01 && (best === null || vv < best)) { best = vv; }
      if (ds < 0 && vv < t - 0.01 && vv > nt + 0.01 && (best === null || vv > best)) { best = vv; }
    });
    if (best !== null) { nt = best; vertex = true; }
  }
  if (!ex.closed && Math.abs(nt - t) < eps) {
    return ASendpointText(ex, dir > 0);
  }
  a.t = nt;
  a.sx = (ex.xsteps && !vertex) ? {el:el, t:nt, x:nx} : null;
  ASplaceMarker(a);
  var parts = [];
  if (!ex.closed && Math.abs(nt - ex.lowT) < eps) {
    parts.push(ASendpointText(ex, false));
  } else if (!ex.closed && Math.abs(nt - ex.highT) < eps) {
    parts.push(ASendpointText(ex, true));
  } else if (vertex) {
    parts.push(el.vertMark || 'vertex');
  }
  parts.push(ASpositionText(el, a, nt));
  return parts.join('. ');
}

function ASjumpToEndpoint(a, toEnd) {
  var el = a.els[a.cur], ex = ASprepareElement(el, a);
  if (ex.isStatic) { return ASmoveAlong(a, 1, 'normal'); }
  a.t = ex.closed ? 0 : (toEnd ? ex.highT : ex.lowT);
  ASplaceMarker(a);
  return (ex.closed ? '' : ASendpointText(ex, toEnd) + '. ') + ASpositionText(el, a, a.t);
}

// Find the x-intercepts, y-intercepts and local extrema of an element that are in the graph
// window, by sampling and then refining. Sorted by position along the element and cached.
function ASpointsOfInterest(el, a) {
  var ex = ASprepareElement(el, a), v = a.view;
  if (ex.poi) { return ex.poi; }
  ex.poi = [];
  if (ex.isStatic) { return ex.poi; }
  var lo = ex.closed ? 0 : Math.min(ex.lowT, ex.highT);
  var hi = ex.closed ? ex.T : Math.max(ex.lowT, ex.highT);
  var L = hi - lo;
  if (!(L > 0)) { return ex.poi; }
  var N = ex.snap ? 2000 : 800, h = L/N, ext = ex.closed ? 1 : 0, list = [], i;
  var wrap = function(t) { return ex.closed ? ((t - lo) % L + L) % L + lo : t; };
  var P = function(t) { return ex.pos(wrap(t)); };
  var ts = [], ps = [];
  for (i = -ext; i <= N + ext; i++) { ts.push(lo + h*i); ps.push(P(lo + h*i)); }
  var W = [v.xmax - v.xmin, v.ymax - v.ymin];
  var add = function(t, labels) {
    var p = P(t);
    if (ex.smooth && v.xfrac && p) {  // found from samples; if it is within a fraction of a sample of a label value, it is that value
      var xl = Math.round(p[0]/v.xfrac.val)*v.xfrac.val;
      if (Math.abs(p[0] - xl) < 0.2*ex.hs) { t = ex.sAtX(xl); p = P(t); }
    }
    if (ASinWindow(p, v)) { list.push({t:wrap(t), p:p, labels:labels}); }
  };
  // c is the coordinate to scan: 1 for y (x-intercepts, max/min of y), 0 for x
  var scan = function(c, zeroLabel, maxLabel, minLabel) {
    var tol = (ex.snap ? 1e-9 : 2e-6)*W[c], zt = 1e-4*W[c];
    var prev = null, dir = 0, mvEnd = 0;
    var fv = function(t, sg) { var pt = P(t); return (pt && pt[c] != null) ? sg*pt[c] : -Infinity; };
    for (var j = 0; j < ts.length; j++) {
      var q = ps[j];
      if (!q || q[c] == null || !isFinite(q[c])) { prev = null; dir = 0; continue; }
      var val = q[c];
      if (prev === null) {
        if (zeroLabel && Math.abs(val) <= tol) { add(ts[j], [zeroLabel]); }
      } else {
        if (zeroLabel && Math.abs(val) <= tol && Math.abs(prev) > tol) {
          add(ts[j], [zeroLabel]);
        } else if (zeroLabel && Math.abs(val) > tol && Math.abs(prev) > tol && (val > 0) !== (prev > 0)) {
          var A = ts[j-1], B = ts[j];
          for (var it = 0; it < 50; it++) {
            var m = (A + B)/2, pm = P(m);
            if (!pm || pm[c] == null) { break; }
            if ((pm[c] > 0) === (prev > 0)) { A = m; } else { B = m; }
          }
          var tz = (A + B)/2, pz = P(tz);
          if (pz && pz[c] != null && Math.abs(pz[c]) <= zt) { add(tz, [zeroLabel]); }  // else a jump, not a crossing
        }
        var d = val - prev, s = d > tol ? 1 : (d < -tol ? -1 : 0);
        if (s !== 0) {
          if (dir !== 0 && s !== dir && maxLabel) {  // the direction flipped, so there is an extremum nearby
            var sg = dir > 0 ? 1 : -1, A2 = ts[Math.max(mvEnd - 1, 0)], B2 = ts[j];
            for (var k = 0; k < 60; k++) {
              var m1 = A2 + (B2 - A2)/3, m2 = B2 - (B2 - A2)/3;
              if (fv(m1, sg) < fv(m2, sg)) { A2 = m1; } else { B2 = m2; }
            }
            var te = (A2 + B2)/2, pe = P(te), labs = [sg > 0 ? maxLabel : minLabel];
            if (zeroLabel && pe && pe[c] != null && Math.abs(pe[c]) <= 1e-7*W[c]) { labs.unshift(zeroLabel); }
            add(te, labs);
          }
          dir = s;
          mvEnd = j;
        }
      }
      prev = val;
    }
  };
  if ((el.kind === 'circle' || el.kind === 'ellipse') && el.c) {
    // Fixed stops at the top, bottom, left and right of the shape, plus any axis intercepts.
    var rx = (el.kind === 'circle') ? el.r : el.rx, ry = (el.kind === 'circle') ? el.r : el.ry;
    scan(1, 'x-intercept', null, null);
    scan(0, 'y-intercept', null, null);
    [[el.c[0] + rx, el.c[1], 'rightmost point'], [el.c[0], el.c[1] - ry, 'lowest point'],
     [el.c[0] - rx, el.c[1], 'leftmost point'], [el.c[0], el.c[1] + ry, 'highest point']
    ].forEach(function(tg) {
      var dist = function(t) {
        var q = P(t);
        return (q && q[1] != null) ? Math.hypot((q[0] - tg[0])*v.xu, (q[1] - tg[1])*v.yu) : Infinity;
      };
      var bj = 0, bd = Infinity;
      for (var j = 0; j < ts.length; j++) {
        var dd = dist(ts[j]);
        if (dd < bd) { bd = dd; bj = j; }
      }
      var A = ts[bj] - h, B = ts[bj] + h;  // refine around the closest sample
      for (var it = 0; it < 60; it++) {
        var m1 = A + (B - A)/3, m2 = B - (B - A)/3;
        if (dist(m1) < dist(m2)) { B = m2; } else { A = m1; }
      }
      add((A + B)/2, [tg[2]]);
    });
  } else {
    scan(1, 'x-intercept', ex.closed ? 'highest point' : 'local maximum', ex.closed ? 'lowest point' : 'local minimum');
  }
  if (el.kind === 'circle' || el.kind === 'ellipse') {
    // x extremes already handled above
  } else if (ex.xfunc) {
    scan(0, 'y-intercept', null, null);
  } else {
    scan(0, 'y-intercept', ex.closed ? 'rightmost point' : 'local x maximum',
      ex.closed ? 'leftmost point' : 'local x minimum');
  }
  // combine features that land on the same spot
  list.sort(function(p, q) { return p.t - q.t; });
  var out = [];
  var near = function(p, q) { return Math.hypot((p.p[0]-q.p[0])*v.xu, (p.p[1]-q.p[1])*v.yu) < 1.5; };
  var merge = function(into, e) {
    e.labels.forEach(function(l) { if (into.labels.indexOf(l) < 0) { into.labels.push(l); } });
  };
  list.forEach(function(e) {
    var last = out[out.length - 1];
    if (last && near(last, e)) { merge(last, e); } else { out.push(e); }
  });
  if (ex.closed && out.length > 1 && near(out[0], out[out.length - 1])) { merge(out[0], out.pop()); }
  out.forEach(function(e) {
    e.labels.sort(function(p, q) { return (/intercept/.test(q) ? 1 : 0) - (/intercept/.test(p) ? 1 : 0); });
  });
  ex.poi = out;
  return out;
}

function ASnextPointOfInterest(a, dir) { // dir: +1 toward the right/top end (like the right and up arrows), -1 back
  var el = a.els[a.cur], ex = ASprepareElement(el, a);
  if (ex.isStatic) {
    return 'This element has no points of interest. Use alt up or alt down to move to another element.';
  }
  var list = ASpointsOfInterest(el, a);
  if (!list.length) {
    return 'No intercepts or local extrema on this element within the graph window.';
  }
  var fwd = dir*ex.sign > 0, eps = (ex.closed ? ex.T : Math.abs(ex.highT - ex.lowT))*1e-4, pick = null;
  list.forEach(function(e) {
    if (fwd && e.t > a.t + eps && (pick === null || e.t < pick.t)) { pick = e; }
    if (!fwd && e.t < a.t - eps && (pick === null || e.t > pick.t)) { pick = e; }
  });
  if (pick === null && ex.closed) {
    pick = fwd ? list[0] : list[list.length - 1];
    if (Math.abs(pick.t - a.t) <= eps && list.length === 1) { return 'This is the only point of interest on this element.'; }
  }
  if (pick === null) {
    return 'No more points of interest toward the ' + (dir > 0 ? ex.highName : ex.lowName).toLowerCase() + '.';
  }
  a.t = pick.t;
  a.sx = ex.xsteps ? {el:el, t:pick.t, x:pick.p[0]} : null;
  ASplaceMarker(a);
  return pick.labels.join(' and ') + '. ' + ASpositionText(el, a, a.t);
}

// While exploring, take the expand button out of the tab order so Tab leaves the graph.
function ASexpandTabStop(a, tabindex) {
  var btn = a.svg.querySelector('[role="button"]');
  if (btn) { btn.setAttribute('tabindex', tabindex); }
}

function ASstopNavigation(a) {
  a.on = false;
  ASexpandTabStop(a, 0);
  if (a.mk && a.mk.parentNode) { a.mk.parentNode.removeChild(a.mk); }
  a.mk = null;
}

function ASkeyDown(e) {
  var svg = e.currentTarget;
  if (e.target !== svg || !svg._as) { return; }  // e.g. the expand button inside the graph
  var a = svg._as, k = e.key;
  if (e.ctrlKey || e.metaKey || !a.els.length) { return; }
  if (!a.on) {
    if (k === 'Enter' || k === ' ') {
      e.preventDefault();
      a.on = true;
      ASexpandTabStop(a, -1);
      ASannounce(svg, 'Exploring graph. ' + ASwindowDescription(a) + ' ' + ASsummary(a) + ' ' + ASselectElement(a, 0) +
        ' Press H for keyboard help.');
    }
    return;
  }
  var msg = null, size = e.shiftKey ? 'small' : 'normal';
  if (e.altKey) {
    if (k === 'ArrowDown') { msg = ASselectElement(a, a.cur + 1, AScurrentX(a)); }
    else if (k === 'ArrowUp') { msg = ASselectElement(a, a.cur - 1, AScurrentX(a)); }
    else { return; }
  } else if (a.els[a.cur].kind === 'slopefield' && ASprepareElement(a.els[a.cur], a).grid &&
      (msg = ASslopeKey(a, k, e.shiftKey)) !== null) {
    // handled as slope field movement
  } else {
    switch (k) {
      case 'ArrowRight': case 'ArrowUp': msg = ASmoveAlong(a, 1, size); break;
      case 'ArrowLeft': case 'ArrowDown': msg = ASmoveAlong(a, -1, size); break;
      case 'PageUp': msg = ASmoveAlong(a, 1, 'large'); break;
      case 'PageDown': msg = ASmoveAlong(a, -1, 'large'); break;
      case 'Home': msg = ASjumpToEndpoint(a, false); break;
      case 'End': msg = ASjumpToEndpoint(a, true); break;
      case 'n': case 'N': msg = ASnextPointOfInterest(a, 1); break;
      case 'p': case 'P': msg = ASnextPointOfInterest(a, -1); break;
      case 'Enter': case ' ': msg = ASdescribeFully(a, false); break;
      case 'w': case 'W': msg = ASwindowDescription(a); break;
      case 'h': case 'H': case '?': msg = AShelptext; break;
      case 'Escape':
        ASstopNavigation(a);
        msg = 'Stopped exploring graph.';
        break;
      default: return;
    }
  }
  e.preventDefault();
  e.stopPropagation();
  ASannounce(svg, msg);
}

function ASblurHandler(e) {
  var a = e.currentTarget._as;
  if (a && a.on) { ASstopNavigation(a); }
}

// Call after a graph is fully drawn. Makes it keyboard explorable if it has anything in it.
function ASmakeAccessible() {
  var svg = svgpicture;
  if (!svg || !svg._as || svg.nodeName.toLowerCase() !== 'svg' || svg._as.els.length === 0) { return; }
  if (svg.hasAttribute('data-noexplore')) { return; }  // text alternative provided instead
  var picid = svg.getAttribute('id');
  var alt = svg.getAttribute('alt') || '';
  var srdiv = document.getElementById(picid + '-srlabel');
  if (srdiv) { srdiv.parentNode.removeChild(srdiv); }  // the label now lives on the svg
  svg.removeAttribute('aria-hidden');
  svg.setAttribute('role', 'application');
  svg.setAttribute('tabindex', '0');
  svg.setAttribute('aria-label', (alt === '' ? 'Graph.' : alt) + ' Interactive graph. Press Enter to explore with the keyboard.');
  var live = document.getElementById(picid + '-live');
  if (!live) {
    live = document.createElement('div');
    live.id = picid + '-live';
    live.className = 'small'; // 'sr-only';
    //live.style.cssText = 'position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;';
    live.setAttribute('aria-live', 'assertive');
    live.setAttribute('aria-atomic', 'true');
    svg.after(live);
  }
  svg._asLive = live;
  if (!svg._asBound) {
    svg._asBound = true;
    svg.addEventListener('keydown', ASkeyDown);
    svg.addEventListener('blur', ASblurHandler);
  }
}


$(function() {
	drawPics();
});


if (checkIfSVGavailable) {
  checkifSVGavailable = false;
  nd = isSVGavailable();
  ASnoSVG = nd!=null;
}

window.drawPictures = drawPictures;
window.drawPics = drawPics;
window.ASnoSVG = ASnoSVG;
window.parseShortScript = parseShortScript;
})();

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
var markerstrokewidth = "1";
var markerstroke = "black";
var markerfill = "yellow";
var marker = "none";
var arrowfill = stroke;
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
  str = str.replace(/Ma(t|\(t\)\*)h\./g,'');
	str = str.replace(/\b(abs|acos|asin|atan|ceil|floor|cos|sin|tan|sqrt|exp|max|min|pow)\(/g, 'Math.$1(');
	str = str.replace(/\(E\)/g,'(Math.E)');
	str = str.replace(/\((PI|pi)\)/g,'(Math.PI)');
	return str;
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
	    try	{
	      var oSVG=eval("new ActiveXObject('Adobe.SVGCtl.3');");
		return null;
	    } catch (e) {

		return 1;
	    }
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
		html += 'sscr="' + sscrarr.join(',') + '"';
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
  } catch(err) {alert(err+"\n"+src)}
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
					qnode.setAttribute("data-script", picture.getAttribute("script"));
					picture.removeAttribute("script");
				}
      } else {
        svgpicture.parentNode.replaceChild(qnode,svgpicture);
      }

      svgpicture = qnode;

      if (picture.getAttribute("alt") != '' && picture.getAttribute("alt") != null) {
        var title = document.createElement("title");
        svgpicture.appendChild(title);
        title.innerText = picture.getAttribute("alt");
        title.id = picid+"-label";
        svgpicture.setAttribute("aria-labelledby", picid+"-label");
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
 var node = myCreateElementSVG("rect");
 node.setAttribute("x","0");
 node.setAttribute("y","0");
 node.setAttribute("width",width);
 node.setAttribute("height",height);
 node.setAttribute("style","stroke-width:1;fill:white");
 svgpicture.appendChild(node);
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
    node.setAttribute("id", id);
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
}


function path(plist,id,c) {
  if (c==null) c="";
  var node, st, i;
  if (id!=null) node = doc.getElementById(id);
  if (node==null) {
    node = myCreateElementSVG("path");
    node.setAttribute("id", id);
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
}


function curve(plist,id) {
  path(plist,id,"T");
}


function circle(center,radius,id) { // coordinates in units
  var node;
  if (id!=null) node = doc.getElementById(id);
  if (node==null) {
    node = myCreateElementSVG("circle");
    node.setAttribute("id", id);
    svgpicture.appendChild(node);
  }

  node.setAttribute("cx",center[0]*xunitlength+origin[0]);
  node.setAttribute("cy",height-center[1]*yunitlength-origin[1]);
  node.setAttribute("r",radius*xunitlength);
  setStrokeFill(node);
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
		node.setAttribute("id", id);
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
    node.setAttribute("id", id);
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
}


function ellipse(center,rx,ry,id) { // coordinates in units

  var node;
  if (id!=null) node = doc.getElementById(id);
  if (node==null) {
    node = myCreateElementSVG("ellipse");
    node.setAttribute("id", id);
    svgpicture.appendChild(node);
  }
  node.setAttribute("cx",center[0]*xunitlength+origin[0]);
  node.setAttribute("cy",height-center[1]*yunitlength-origin[1]);
  node.setAttribute("rx",rx*xunitlength);
  node.setAttribute("ry",ry*yunitlength);
  setStrokeFill(node);
}


function rect(p,q,id,rx,ry) { // opposite corners in units, rounded by radii
  var node;
  if (id!=null) node = doc.getElementById(id);
  if (node==null) {
    node = myCreateElementSVG("rect");
    node.setAttribute("id", id);
    svgpicture.appendChild(node);
  }
  node.setAttribute("x",Math.min(p[0],q[0])*xunitlength+origin[0]);
  node.setAttribute("y",height-Math.max(q[1],p[1])*yunitlength-origin[1]);
  node.setAttribute("width",Math.abs(q[0]-p[0])*xunitlength);
  node.setAttribute("height",Math.abs(q[1]-p[1])*yunitlength);
  if (rx!=null) node.setAttribute("rx",rx*xunitlength);
  if (ry!=null) node.setAttribute("ry",ry*yunitlength);
  setStrokeFill(node);
}

function text(p,st,pos,angle) {
	p[0] = p[0]*xunitlength+origin[0];
	p[1] = p[1]*yunitlength+origin[1];
	textabs(p,st,pos,angle);
}

function textabs(p,st,pos,angle,id,fontsty) {
  if (angle==null) {
	  angle = 0;
  } else {
	  angle = (360 - angle)%360;
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
    node.setAttribute("id", id);
    svgpicture.appendChild(node);
    node.appendChild(doc.createTextNode(st));
  }
  node.lastChild.nodeValue = st;
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
      node.setAttribute("id", id);
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
      node.setAttribute("id", id);
      svgpicture.appendChild(node);
    }
    node.setAttribute("cx",cx);
    node.setAttribute("cy",cy);
    node.setAttribute("r",dotradius);
    node.setAttribute("stroke-width", strokewidth);
    node.setAttribute("stroke", stroke);
    node.setAttribute("fill", (typ=="open"?"white":stroke));
  }
  if (label!=null)
    text(center,label,(pos==null?"below":pos),(id==null?id:id+"label"))
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
    node.setAttribute("d","M "+(w[0]-15*u[0]-4*up[0])+" "+
      (w[1]-15*u[1]-4*up[1])+" L "+(w[0]-3*u[0])+" "+(w[1]-3*u[1])+" L "+
      (w[0]-15*u[0]+4*up[0])+" "+(w[1]-15*u[1]+4*up[1])+" z");
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
  node.setAttribute("tabindex", 0);
  node.setAttribute("aria-label", "Expand Graph");
  node.addEventListener("click", asciisvgexpand);
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


function axes(dx,dy,labels,gdx,gdy,dox,doy,smallticks) {
//xscl=x is equivalent to xtick=x; xgrid=x; labels=true;
  var x, y, ldx, ldy, lx, ly, lxp, lyp, pnode, st;
  if (!initialized) initPicture();
  if (typeof dx=="string") { labels = dx; dx = null; }
  if (typeof dy=="string") { gdx = dy; dy = null; }
  if (xscl!=null) {dx = xscl; gdx = xscl; labels = dx}
  if (yscl!=null) {dy = yscl; gdy = yscl}
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
  if (gdx!=null && gdx>0) {
    if (smallticks!=null && smallticks==1) {
    	  var gridymin = origin[1] + .7*ticklength;
	  var gridymax = origin[1] - .7*ticklength;
	  var gridxmin = origin[0] - .7*ticklength;
	  var gridxmax = origin[0] + .7*ticklength;
    } else {
	  var gridymin = winymin;
	  var gridymax = winymax;
	  var gridxmin = winxmin;
	  var gridxmax = winxmax;
    }

    gdx = (typeof gdx=="string"?dx:gdx*xunitlength);
    gdy = (gdy==null?dy:gdy*yunitlength);
    pnode = myCreateElementSVG("path");
    st="";
    if (dox && gdx>0) {
	    for (x = origin[0]; x<=winxmax; x = x+gdx)
	      if (x>=winxmin) st += " M"+x+","+gridymin+" "+x+","+(fqonlyy?height-origin[1]:gridymax);
	    if (!fqonlyx) {
	    	    for (x = origin[0]-gdx; x>=winxmin; x = x-gdx)
	    	    	    if (x<=winxmax) st += " M"+x+","+gridymin+" "+x+","+(fqonlyy?height-origin[1]:gridymax);
	    }
    }

    if (doy && gdy>0) {
	    if (!fqonlyy) {
	      for (y = height-origin[1]; y<=winymax; y = y+gdy)
	        if (y>=winymin) st += " M"+(fqonlyx?origin[0]:gridxmin)+","+y+" "+gridxmax+","+y;
	    }
	    for (y = height-origin[1]-gdy; y>=winymin; y = y-gdy)
	        if (y<=winymax) st += " M"+(fqonlyx?origin[0]:gridxmin)+","+y+" "+gridxmax+","+y;
    }
    pnode.setAttribute("d",st);
    pnode.setAttribute("stroke-width", .5);
    pnode.setAttribute("stroke", gridstroke);
    pnode.setAttribute("fill", fill);
    svgpicture.appendChild(pnode);
  }
  pnode = myCreateElementSVG("path");
  if (dox) {
	  st="M"+(fqonlyx?origin[0]:winxmin)+","+(height-origin[1])+" "+winxmax+","+
    (height-origin[1]);
  }
  if (doy) {
	  st += " M"+origin[0]+","+winymin+" "+origin[0]+","+(fqonlyy?height-origin[1]:winymax);
  }

  if (dox && dx>0) {
	  for (x = origin[0]; x<winxmax; x = x+dx)
	    if (x>=winymin) st += " M"+x+","+(height-origin[1]+ticklength)+" "+x+","+
		   (height-origin[1]-ticklength);
	  if (!fqonlyx) {
	    for (x = origin[0]-dx; x>winxmin; x = x-dx)
	      if (x<=winxmax) st += " M"+x+","+(height-origin[1]+ticklength)+" "+x+","+
	  	  	(height-origin[1]-ticklength);
	  }
  }
  if (doy && dy>0) {
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
	    for (x = (doy?ldx:0); x<=xmax; x = x+ldx)
	      if (x>=xmin) text([x,ly], rounddec(x, ddx),lxp);
	    if (!fqonlyx) {
	      for (x = -ldx; xmin<=x; x = x-ldx)
	        if (x<=xmax) text([x,ly], rounddec(x, ddx),lxp);
	    }
    }
    if (doy && dy>0) {
	    for (y = (dox?ldy:0); y<=ymax; y = y+ldy)
	      if (y>=ymin) text([lx,y], rounddec(y, ddy), lyp);
      	    if (!fqonlyy) {
	      for (y = -ldy; ymin<=y; y = y-ldy)
	        if (y<=ymax) text([lx,y], rounddec(y, ddy), lyp);
	    }
    }
  }
  pnode.setAttribute("d",st);
  pnode.setAttribute("stroke-width", .5);
  pnode.setAttribute("stroke", axesstroke);
  pnode.setAttribute("fill", fill);
  svgpicture.appendChild(pnode);
}


function slopefield(fun,dx,dy) {
  var g = fun;
  if (typeof fun=="string")
    eval("g = function(x,y){ return "+prepWithMath(mathjs(fun,"x|y"))+" }");
  var gxy,x,y,u,v,dz;
  if (dx==null) dx=1;
  if (dy==null) dy=1;
  dz = Math.sqrt(dx*dx+dy*dy)/6;
  var x_min = dx*Math.ceil(xmin/dx);
  var y_min = dy*Math.ceil(ymin/dy);
  for (x = x_min; x <= xmax; x += dx)
    for (y = y_min; y <= ymax; y += dy) {
      gxy = g(x,y);
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
    return prepWithMath(mathjs(str));
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
			   commands += 'text(['+sa[inx+5]+','+sa[inx+6]+'],"'+sa[inx+1]+'");';
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

		picture.setAttribute("alt",eqnlist);

		try {
			eval(commands);
			if (!svgpicture.hasAttribute("viewBox") && !svgpicture.hasAttribute("data-nomag")) {
				addMagGlass();
			}
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
				  try {
					  eval(prepWithMath(src));
					  if (!picture.hasAttribute("data-enlarged") && !picture.hasAttribute("data-nomag")) {
					  	  addMagGlass();
					  }
				  } catch(err) {alert(err+"\n"+src)}
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

//modified by David Lippman from original in AsciiSVG.js by Peter Jipsen
//added min/max type:  0:nothing, 1:arrow, 2:open dot, 3:closed dot
function plot(fun,x_min,x_max,points,id,min_type,max_type) {
  var pth = [];
  var f = function(x) { return x }, g = fun;
  var name = null;
  if (typeof fun=="string")
    eval("g = function(x){ return "+prepWithMath(mathjs(fun,"x"))+" }");
  else if (typeof fun=="object") {
    eval("f = function(t){ return "+prepWithMath(mathjs(fun[0],"t"))+" }");
    eval("g = function(t){ return "+prepWithMath(mathjs(fun[1],"t"))+" }");
  }
  if (typeof x_min=="string") { name = x_min; x_min = xmin }
  else name = id;
  var min = (x_min==null?xmin:x_min);
  var max = (x_max==null?xmax:x_max);
  if (max <= min) { return null;}
  //else {
  var inc = max-min-0.000001*(max-min);
  inc = (points==null?inc/200:inc/points);
  var gt;
//alert(typeof g(min))
  for (var t = min; t <= max; t += inc) {
    gt = g(t);
    if (!(isNaN(gt)||Math.abs(gt)=="Infinity")) {
	    if ((pth.length > 0) && (Math.abs(gt-pth[pth.length-1][1]) > (ymax-ymin))) {
		    if (pth.length > 1)  path(pth,name);
		    pth.length=0;
	    } else {
		    pth[pth.length] = [f(t), gt];
	    }
    }
  }
  if (pth.length > 1) path(pth,name);
  if (min_type == 1) {
	arrowhead(pth[1],pth[0]);
  } else if (min_type == 2) {
	dot(pth[0], "open");
  } else if (min_type == 3) {
	dot(pth[0], "closed");
  }
  if (max_type == 1) {
	arrowhead(pth[pth.length-2],pth[pth.length-1]);
  } else if (max_type == 2) {
	dot(pth[pth.length-1], "open");
  } else if (max_type == 3) {
	dot(pth[pth.length-1], "closed");
  }

  return p;
  //}
}

//end ASCIIsvgAddon.js dump


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

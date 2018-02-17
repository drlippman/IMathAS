var canvases = new Array();
var drawla = new Array();

var imathasDraw = (function($) {
var mouseisdown = false;
var targets = new Array();
var imgs = new Array();
var targetOuts = new Array();
var a11ytargets = new Array();
var lines = new Array();
var dots = new Array();
var odots = new Array();
var tplines = new Array();
var tptypes = new Array();
var ineqlines = new Array();
var ineqtypes = new Array();
var curLine = null;
var drawstyle = [];
var drawlocky = [];
var curTPcurve = null;
var curIneqcurve = null; //inequalities
var ineqcolors = ["rgb(0,0,255)","rgb(255,0,0)","rgb(0,255,0)"];
var ineqacolors = ["rgba(0,0,255,.4)","rgba(255,0,0,.4)","rgba(0,255,0,.4)"];
var dragObj = null;
var oldpointpos = null;
var curTarget = null;
var curCursor = null;
var nocanvaswarning = false;
var hasTouch = false;
var didMultiTouch = false;
var clickmightbenewcurve = false;
var hasTouchTimer = null;
/*
   Canvas-based function drawing script
   (c) David Lippman, part of www.imathas.com
   Quadratic inequality code contributed by Cam Joyce
   GNU 2 Licensed - see license in IMathAS distribution

   HTML should have <canvas id="canvas##"></canvas>
   and include a <script> tag that defines
   canvases[##] = [##,'background img',xmin,xmax,ymin,ymax,border,imgwidth,imgheight,defmode,dotline,locky];
   where
   	background img is filename is a specific directory (anyone else would need
   	  	to adjust the directory in the code)
   	xmin,xmax,ymin,ymax,border are based on the background image coordinates.
   	    	these are not used by the JS, but could be to convert from
   	    	pixel locations to graph coordinate locations
   	imgwidth, imgheight are the pixels of the canvas element
   	defmode is the default tool that should be selected (see below for modes)
   	dotline will, if true, add dots at the end of line segments in mode 0.
   		this is useful for drawing polygons
   	locky will, if true, only allow drawing along the center x-axis.
   		this is useful for numberline graphing

   Will automatically output to <input id="qn##" />

   JS can interact with the drawing item by calling:
      clearcanvas(##)
      settool(this,##,mode)
   where ## is the ## in the canvas id, and mode is one of:

   targets.mode
   	0:  set of line segments / freeform drawing
   	0.5: single line segment (basic tool)
   	0.7:  freeform drawing on mousedown only (no click-line)
   	1: solid dot
   	2: open dot
   tptypes
	5: line
	5.2:  ray (no arrow)
	5.3:  line segment
	5.4:  vector
	6: parabola
	6.1: horiz parabola
	6.5: square root
	7: circle (only works on square grids)
	7.2: ellipse
	7.4: vertical hyperbola
	7.5: horizontal hyperbola
	8: abs value
	8.2: linear/linear rational
	8.3: exponential (unshifted)
	9: cosine/sine
   ineqtypes
   	10: linear >= or <=
   	10.2: linear < or >
	10.3: quadratic <= or =>
	10.4: quadratic < or >

*/
function clearcanvas(tarnum) {
	lines[tarnum].length = 0;
	dots[tarnum].length = 0;
	odots[tarnum].length = 0;
	tplines[tarnum].length = 0;
	tptypes[tarnum].length = 0;
	ineqlines[tarnum].length = 0;
	ineqtypes[tarnum].length = 0;
	curTarget = tarnum;
	drawTarget();
	curTarget = null;
	curLine = null;
	dragObj = null;
}

function addA11yTarget(canvdata, thisdrawla) {
	var tarnum = canvdata[0];
	a11ytargets.push(tarnum);
	var ansformats = canvdata[1].substr(9).split(',');
	var xmin = canvdata[2];
	var xmax = canvdata[3];
	var ymin = canvdata[4];
	var ymax = canvdata[5];
	if (ymin==ymax) { //numberlines
		ymin = -1; ymax = 1;
	}
	var imgborder = canvdata[6];
	var imgwidth = canvdata[7];
	var imgheight = canvdata[8];
	var tarel = document.getElementById("a11ydraw"+tarnum);
	targetOuts[tarnum] = document.getElementById('qn'+tarnum);
	targets[tarnum] = {el: tarel, xmin: xmin, xmax: xmax, ymin: ymin, ymax: ymax, imgborder: imgborder, imgwidth: imgwidth, imgheight: imgheight};
	targets[tarnum].pixperx = (imgwidth - 2*imgborder)/(xmax-xmin);
	targets[tarnum].pixpery = (ymin==ymax)?1:((imgheight - 2*imgborder)/(ymax-ymin));
	var afgroup;
	//massae ansformats array to account for default behaviors
	if (ansformats[0]=="inequality") {
		afgroup = ansformats.shift();
		if (ansformats.length==0) {
			ansformats = ["line"];
		} else if (ansformats[0]=="both") {
			ansformats = ["line","parab"];
		}
	} else if (ansformats[0]=="twopoint") {
		afgroup = ansformats.shift();
		if (ansformats.length==0) {
			ansformats = ["line","parab","abs","circle","dot"];
		}
	} else if (ansformats[0]=="numberline") {
		afgroup = "basic";
		ansformats.shift();
	} else {
		afgroup = "basic";
	}
	targets[tarnum].afgroup = afgroup;
	var types = {
		"inequality": {
			"line": [
				{"mode":10, "descr":_("Linear inequality with solid line"), inN: 3, "input":_("Enter 2 points on the line, then a third point in the shaded region")},
				{"mode":10.2, "descr":_("Linear inequality with dashed line"), inN: 3, "input":_("Enter 2 points on the line, then a third point in the shaded region")}
			],
			"parab": [
				{"mode":10.3, "descr":_("Parabolic inequality with solid line"), inN: 3, "input":_("Enter 2 points on the line, then a third point in the shaded region")},
				{"mode":10.4, "descr":_("Parabolic inequality with dashed line"), inN: 3, "input":_("Enter 2 points on the line, then a third point in the shaded region")}
			]
		},
		"twopoint": {
			"line": [{"mode":5, "descr":_("Line"), inN: 2, "input":_("Enter two points on the line")}],
			"lineseg": [{"mode":5.3, "descr":_("Line segment"), inN: 2, "input":_("Enter the starting and ending point of the line segment")}],
			"ray": [{"mode":5.2, "descr":_("Ray"), inN: 2, "input":_("Enter the starting point of the ray and another point on the ray")}],
			"parab": [{"mode":6, "descr":_("Parabola"), inN: 2, "input":_("Enter the vertex, then another point on the parabola")}],
			"horizparab": [{"mode":6.1, "descr":_("Parabola opening right or left"), inN: 2, "input":_("Enter the vertex, then another point on the parabola")}],
			"sqrt": [{"mode":6.5, "descr":_("Square root"), inN: 2, "input":_("Enter the starting point of the square root, then another point on the graph")}],
			"abs": [{"mode":8, "descr":_("Absolute value"), inN: 2, "input":_("Enter the corner point of the absolute value, then another point on the graph")}],
			"rational": [{"mode":8.2, "descr":_("Rational"), inN: 2, "input":_("Enter the point where the vertical and horizontal asymptote cross, then a point on the graph")}],
			"exp": [{"mode":8.3, "descr":_("Exponential"), inN: 2, "input":_("Enter two points on the graph")}],
			"circle": [{"mode":7, "descr":_("Circle"), inN: 2, "input":_("Enter the center point of the circle, then a point on the graph")}],
			"ellipse": [{"mode":7.2, "descr":_("Ellipse"), inN: 2, "input":_("Enter the center point of the ellipse, then a point offset from the center by the horizontal radius and vertical radius")}],
			"hyperbola": [
				{"mode":7.4, "descr":_("Vertical hyperbola"), inN: 2, "input":_("Enter the center point of the hyperbola, then a point (x,y) where x is the x-coordinate of the co-vertex and y is the y-coordinate of the vertex")},
				{"mode":7.5, "descr":_("Horizontal hyperbola"), inN: 2, "input":_("Enter the center point of the hyperbola, then a point (x,y) where x is the x-coordinate of the vertex and y is the y-coordinate of the co-vertex")},
			],
			"dot": [{"mode":1, "descr":_("Solid dot"), inN: 1, "input":_("Enter the coordinates of the dot")}],
			"opendot": [{"mode":2, "descr":_("Open dot"), inN: 1, "input":_("Enter the coordinates of the dot")}],
			"trig": [
				{"mode":9, "descr":_("Cosine"), inN: 2, "input":_("Enter a point at the start of a phase, then a point half a phase further")},
				{"mode":9.2, "descr":_("Sine"), inN: 2, "input":_("Enter a point at the start of a phase, then a point a quarter phase further")}
			],
			"vector": [{"mode":5.4, "descr":_("Vector"), inN: 2, "input":_("Enter the starting and ending point of the vector")}],
		},
		"basic": {
			"line": [{"mode":0, "descr":_("Lines"), inN: "list", "input":_("Enter list of points to connect with lines")}],
			"lineseg": [{"mode":0.5, "descr":_("Line segment"), inN: 2, "input":_("Enter the starting and ending point of the line segment")}],
			"freehand": [{"mode":0.7, "descr":_("Freehand"), inN: "list", "input":_("Enter list of points to connect with lines")}],
			"dot": [{"mode":1, "descr":_("Solid dot"), inN: 1, "input":_("Enter the coordinates of the dot")}],
			"opendot": [{"mode":2, "descr":_("Open dot"), inN: 1, "input":_("Enter the coordinates of the dot")}],
			"polygon": [{"mode":1, "descr":_("Polygon"), inN: "list", "input":_("Enter list of points to place dots connected with lines"), "dotline":1}],
			"closedpolygon": [{"mode":1, "descr":_("Polygon"), inN: "list", "input":_("Enter list of points to place dots connected with lines"), "dotline":2}],
		}
	};

	var defmode, inputmodes = [], selects = [], input, moderef=[],op;
	for (var i=0;i<ansformats.length;i++) {
		if (types[afgroup].hasOwnProperty(ansformats[i])) {
			if (i==0) {
				defmode = types[afgroup][ansformats[i]][0].mode;
			}
			for (var j=0;j<types[afgroup][ansformats[i]].length;j++) {
				moderef[types[afgroup][ansformats[i]][j].mode] = types[afgroup][ansformats[i]][j];
				inputmodes.push(types[afgroup][ansformats[i]][j].mode);
				op = '<option value="'+types[afgroup][ansformats[i]][j].mode+'"';
				op += ' data-af="'+ansformats[i]+'">'+types[afgroup][ansformats[i]][j].descr+'</option>';
				selects.push(op);
			}
		}
	}
	targets[tarnum].defmode = defmode;
	targets[tarnum].inputmodes = inputmodes;
	targets[tarnum].selects = selects;
	targets[tarnum].moderef = moderef;

	//how can we restore what the student entered, if we're allowing
	//things like (2/3, 0)?  Either we convert that to a decimal, or
	// we have to store original typed answer.
	//maybe new drawla[5] for that purpose?
	//need to be able to get acccess to drawla from here
	//TODO:  Check if thisdrawla was defined
	if (thisdrawla == null && lines.hasOwnProperty(tarnum)) {
		for (var i=0;i<lines[tarnum].length;i++) {
			adda11ydraw(tarnum, 0, pixcoordstopointlist(lines[tarnum][i], tarnum));
		}
		for (var i=0;i<dots[tarnum].length;i++) {
			adda11ydraw(tarnum, 1, pixcoordstopointlist(dots[tarnum][i], tarnum));
		}
		for (var i=0;i<odots[tarnum].length;i++) {
			adda11ydraw(tarnum, 2, pixcoordstopointlist(odots[tarnum][i], tarnum));
		}
		for (var i=0;i<tplines[tarnum].length;i++) {
			adda11ydraw(tarnum, tptypes[tarnum][i], pixcoordstopointlist(tplines[tarnum][i][0], tarnum)+","+pixcoordstopointlist(tplines[tarnum][i][1], tarnum));
		}
		for (var i=0;i<ineqlines[tarnum].length;i++) {
			adda11ydraw(tarnum, ineqtypes[tarnum][i], pixcoordstopointlist(ineqlines[tarnum][i][0], tarnum)+","+pixcoordstopointlist(ineqlines[tarnum][i][1], tarnum)+","+pixcoordstopointlist(ineqlines[tarnum][i][2], tarnum));
		}
	} else if (thisdrawla != null) {
		for (var i=0;i<thisdrawla.length;i++) {
			//format:  mode, "entered answer"
			//but somehow have to preserve parens in the entered answer??
			adda11ydraw(tarnum, thisdrawla[i][0], thisdrawla[i][1].replace(/\[/g,"(").replace(/\]/g,")"));
		}
	}

}

function adda11ydraw(tarnum,initmode,defval) {
	var thistarg = targets[tarnum];
	var mode = initmode || thistarg.defmode;
	var val = defval || "";
	var afgroup = thistarg.afgroup;
	html = '<select aria-label="'+_("Drawing element type")+'" onchange="imathasDraw.changea11ydraw(this,\''+tarnum+'\')">';
	for (j in thistarg.selects) {
		html += thistarg.selects[j];
	}
	html += '</select><br/>';
	html += '<span class="a11ydrawinstr"></span><br/>';
	html += '<input type="text" aria-label="'+_("Point list")+'" value="'+val+'"/>';
	html += '<button type="button" class="imgbutton" onclick="imathasDraw.removea11ydraw(this)">';
	html += _("Remove")+'</button>';
	var li = $("<li>", {class:"a11ydrawrow"}).html(html);
	$(thistarg.el).append(li);
	li.find("select").val(mode);
	li.find(".a11ydrawinstr").text(thistarg.moderef[mode].input);
	if (!defval) {
		li.find("select").focus();
	}
}

function removea11ydraw(el) {
	$(el).parent().remove();
}
function changea11ydraw(tarel, tarnum) {
	var curmode = $(tarel).val();
	var modedata = targets[tarnum].moderef[curmode];
	$(tarel).parent().find(".a11ydrawinstr").text(modedata.input);
}
function pixcoordstopointlist(vals,tarnum) {
	var thistarg = targets[tarnum];
	var x,y;
	x = (vals[0] - thistarg.imgborder)/thistarg.pixperx + thistarg.xmin;
	y = (thistarg.imgheight - vals[1] - thistarg.imgborder)/thistarg.pixpery + thistarg.ymin;
	x = Math.round(x*100)/100;
	y = Math.round(y*100)/100;
	return "("+x+","+y+")";
}
function encodea11ydraw() {
	for (var i=0;i<a11ytargets.length;i++) {
		var tarnum = a11ytargets[i];
		var thistarg = targets[tarnum];
		var lines = [];
		var dots = [];
		var odots = [];
		var tplines = [];
		var tpineq = [];
		var saveinput = [];
		var afgroup = targets[tarnum].afgroup;
		$("#a11ydraw"+tarnum).find(".a11ydrawrow").each(function(i,el) {
			var mode = $(el).find("select").val();
			var input = $(el).find("input").val();
			saveinput.push("["+mode+',"'+input+'"]');
			input = input.replace(/[\(\)]/g,'').split(/\s*,\s*/);
			var outpts = [];
			for (var i=1;i<input.length;i+=2) {
				try {
					input[i-1] = eval(prepWithMath(mathjs(input[i-1])));
				} catch(e) {
					input[i-1] = NaN;
				}
				try {
					input[i] = eval(prepWithMath(mathjs(input[i])));
				} catch(e) {
					input[i] = NaN;
				}
				input[i-1] = (input[i-1] - thistarg.xmin)*thistarg.pixperx + thistarg.imgborder;
				input[i] = thistarg.imgheight - (input[i] - thistarg.ymin)*thistarg.pixpery - thistarg.imgborder;
				outpts.push(Math.round(input[i-1])+','+Math.round(input[i]));
			}
			if (mode==1) {
				dots.push('('+outpts.join('),(')+')');
			} else if (mode==2) {
				odots.push('('+outpts.join('),(')+')');
			} else if (mode<1) {
				lines.push('('+outpts.join('),(')+')');
			} else if (mode>=5 && mode<10 && outpts.length==2) {
				tplines.push('('+mode+','+outpts.join(',')+')');
			} else if (mode>=10 && outpts.length==3) {
				tpineq.push('('+mode+','+outpts.join(',')+')');
			}
		});
		targetOuts[tarnum].value = lines.join(';')+';;'+dots.join(',')+';;'+odots.join(',')+';;'+tplines.join(',')+';;'+tpineq.join(',')+';;'+saveinput.join(',');
	}
}

function addTarget(tarnum,target,imgpath,formel,xmin,xmax,ymin,ymax,imgborder,imgwidth,imgheight,defmode,dotline,locky,snaptogrid) {
	var tarel = document.getElementById(target);
	tarel.style.userSelect = "none";
	tarel.style.webkitUserSelect = "none";
	tarel.style.MozUserSelect = "none";

	var tarpos = getPosition(tarel);

	targets[tarnum] = {el: tarel, left: tarpos.x, top: tarpos.y, width: tarel.offsetWidth, height: tarel.offsetHeight, xmin: xmin, xmax: xmax, ymin: ymin, ymax: ymax, imgborder: imgborder, imgwidth: imgwidth, imgheight: imgheight, mode: defmode, dotline: dotline};
	if (typeof snaptogrid=="string" && snaptogrid.indexOf(":")!=-1) {
		snaptogrid = snaptogrid.split(":");
		targets[tarnum].snaptogridx = 1*snaptogrid[0];
		targets[tarnum].snaptogridy = 1*snaptogrid[1];
	} else {
		targets[tarnum].snaptogridx = snaptogrid;
		targets[tarnum].snaptogridy = snaptogrid;
	}
	targets[tarnum].pixperx = (imgwidth - 2*imgborder)/(xmax-xmin);
	targets[tarnum].pixpery = (ymin==ymax)?1:((imgheight - 2*imgborder)/(ymax-ymin));

	targetOuts[tarnum] = document.getElementById(formel);
	if (lines[tarnum]==null) {lines[tarnum] = new Array();}
	if (dots[tarnum]==null) {dots[tarnum] = new Array();}
	if (odots[tarnum]==null) {odots[tarnum] = new Array();}
	if (tplines[tarnum]==null) {tplines[tarnum] = new Array();}
	if (tptypes[tarnum]==null) {tptypes[tarnum] = new Array();}
	if (ineqlines[tarnum]==null) {ineqlines[tarnum] = new Array();}
	if (ineqtypes[tarnum]==null) {ineqtypes[tarnum] = new Array();}
	if (defmode>=5) {
		drawstyle[tarnum] = 1;
	} else {
		drawstyle[tarnum] = 0;
	}
	drawlocky[tarnum] = locky;
	imgs[tarnum] = new Image();
	imgs[tarnum].onload = function() {
		var oldcurTarget = curTarget;
		curTarget = tarnum;
		drawTarget();
		curTarget = oldcurTarget;
	};
	imgs[tarnum].src = imgpath;
}

function settool(curel,tarnum,mode) {
	var mydiv = document.getElementById("drawtools"+tarnum);
	var mycel       = mydiv.getElementsByTagName("span");
	for (var i=0; i<mycel.length; i++) {
		mycel[i].className = '';
	}
	mycel       = mydiv.getElementsByTagName("img");
	for (var i=0; i<mycel.length; i++) {
		mycel[i].className = '';
	}
	curel.className = "sel";

	setDrawMode(tarnum,mode);
	curTarget = tarnum;
	drawTarget();
}
function setDrawMode(tarnum,mode) {
	targets[tarnum].mode = mode;
}
function setDotLine(tarnum,onoff) {
	targets[tarnum].dotline = onoff;
}

function drawTarget(x,y) {
	try {
		var ctx = targets[curTarget].el.getContext('2d');
	} catch(e) {
		if (!nocanvaswarning) {
			nocanvaswarning = true;
			alert("Your browser does not support drawing answer entry.  Please try again using Internet Explorer 6+ (Windows), FireFox 1.5+ (Win/Mac), Safari 1.3+ (Mac), Opera 9+ (Win/Mac), or Camino (Mac)");
		}
	}

	ctx.fillStyle = "rgb(0,0,255)";
	ctx.lineWidth = 2;
	ctx.strokeStyle = "rgb(0,0,255)";
	ctx.clearRect(0,0,targets[curTarget].imgwidth,targets[curTarget].imgheight);

	ctx.drawImage(imgs[curTarget],0,0);
	ctx.beginPath();
	for (var i=0;i<ineqlines[curTarget].length; i++) {
		var colornum = i%3;
		ctx.strokeStyle = ineqcolors[colornum];
		ctx.fillStyle = ineqcolors[colornum];
		//is an inequality
		var slope = null;
		var x2 = null; var x3 = null;
		var y2 = null; var y3 = null;
		if (ineqlines[curTarget][i].length==3) { //three points set
			x2 = ineqlines[curTarget][i][1][0];
			y2 = ineqlines[curTarget][i][1][1];
			x3 = ineqlines[curTarget][i][2][0];
			y3 = ineqlines[curTarget][i][2][1];
		} else if (ineqlines[curTarget][i].length==2) { //two points set
			x2 = ineqlines[curTarget][i][1][0];
			y2 = ineqlines[curTarget][i][1][1];
			x3 = x;
			y3 = y;
		} else if (curIneqcurve==i && x!= null && ineqlines[curTarget][i].length==1) {
			x2 = x;
			y2 = y;
		}
		if (x3 != null) { //third point is set or varying; plot dot at point and shade that side
			ctx.save();
			ctx.fillStyle = ineqacolors[colornum];
			//ctx.fillStyle = "rgba(0,0,255,0.4)";
			//ctx.fillStyle = "rgb(0,255,0)";
			ctx.beginPath();

			if(ineqtypes[curTarget][i] <= 10.2){//linear inequality
				if (x2!=ineqlines[curTarget][i][0][0]) {
					var slope = (y2 - ineqlines[curTarget][i][0][1])/(x2-ineqlines[curTarget][i][0][0]);
				}
				if (Math.abs(x2-ineqlines[curTarget][i][0][0])<1 || Math.abs(slope)>100) {
					ctx.moveTo(ineqlines[curTarget][i][0][0],0);
					ctx.lineTo(ineqlines[curTarget][i][0][0],targets[curTarget].imgheight);
					if (x3>x2) {//shade right
						ctx.lineTo(targets[curTarget].imgwidth,targets[curTarget].imgheight);
						ctx.lineTo(targets[curTarget].imgwidth,0);
						ctx.closePath();
					} else {
						ctx.lineTo(0,targets[curTarget].imgheight);
						ctx.lineTo(0,0);
						ctx.closePath();
					}
				} else {
					var yb = slope*(x3 - x2) + y2;
					var yleft = ineqlines[curTarget][i][0][1] - slope*ineqlines[curTarget][i][0][0];
					var yright = ineqlines[curTarget][i][0][1] + slope*(targets[curTarget].imgwidth-ineqlines[curTarget][i][0][0]);

					ctx.moveTo(0,yleft);
					ctx.lineTo(targets[curTarget].imgwidth,yright);
					if (y3 > yb) { //shade above
						ctx.lineTo(targets[curTarget].imgwidth,targets[curTarget].imgheight);
						ctx.lineTo(0,targets[curTarget].imgheight);
						ctx.closePath();
					} else { //shade below
						ctx.lineTo(targets[curTarget].imgwidth,0);
						ctx.lineTo(0,0);
						ctx.closePath();
					}
				}
			} else {//quadratic inequality shading
				shadeParabola(ctx,ineqlines[curTarget][i][0][0],ineqlines[curTarget][i][0][1],x2,y2,x3,y3,targets[curTarget].imgwidth,targets[curTarget].imgheight);
			}
			ctx.fill();
			ctx.restore();
		}
		ctx.beginPath();

		if (x2 != null) { //at least one point set
			if(ineqtypes[curTarget][i] <= 10.2){//linear inequality
				if (x2!=ineqlines[curTarget][i][0][0]) {
					var slope = (y2 - ineqlines[curTarget][i][0][1])/(x2-ineqlines[curTarget][i][0][0]);
				}
				if (Math.abs(x2-ineqlines[curTarget][i][0][0])<1 || Math.abs(slope)>100) { //vert line
					//document.getElementById("ans0-0").innerHTML = 'vert';
					if(ineqtypes[curTarget][i]==10.2) {
						if (dragObj != null && dragObj.mode>=10 && dragObj.mode<11 && dragObj.num==i && dragObj.subnum==0) {
							ctx.dashedLine(ineqlines[curTarget][i][1][0],ineqlines[curTarget][i][1][1],ineqlines[curTarget][i][1][0],targets[curTarget].imgheight);
							ctx.dashedLine(ineqlines[curTarget][i][1][0],ineqlines[curTarget][i][1][1],ineqlines[curTarget][i][1][0],0);
						} else {
							ctx.dashedLine(ineqlines[curTarget][i][0][0],ineqlines[curTarget][i][0][1],ineqlines[curTarget][i][0][0],targets[curTarget].imgheight);
							ctx.dashedLine(ineqlines[curTarget][i][0][0],ineqlines[curTarget][i][0][1],ineqlines[curTarget][i][0][0],0);
						}
					} else {
						ctx.moveTo(ineqlines[curTarget][i][0][0],0);
						ctx.lineTo(ineqlines[curTarget][i][0][0],targets[curTarget].imgheight);
						ctx.stroke();
					}
				} else {
					//document.getElementById("ans0-0").innerHTML = slope;
					var yleft = ineqlines[curTarget][i][0][1] - slope*ineqlines[curTarget][i][0][0];
					var yright = ineqlines[curTarget][i][0][1] + slope*(targets[curTarget].imgwidth-ineqlines[curTarget][i][0][0]);
					//TODO:  fix for very large slopes;
					if (ineqtypes[curTarget][i]==10.2) {
						if (dragObj != null && dragObj.mode>=10 && dragObj.mode<11 && dragObj.num==i && dragObj.subnum==0) {
							ctx.dashedLine(ineqlines[curTarget][i][1][0],ineqlines[curTarget][i][1][1],targets[curTarget].imgwidth,yright);
							ctx.dashedLine(ineqlines[curTarget][i][1][0],ineqlines[curTarget][i][1][1],0,yleft);
						} else {
							ctx.dashedLine(ineqlines[curTarget][i][0][0],ineqlines[curTarget][i][0][1],targets[curTarget].imgwidth,yright);
							ctx.dashedLine(ineqlines[curTarget][i][0][0],ineqlines[curTarget][i][0][1],0,yleft);
						}
					} else {
						ctx.moveTo(0,yleft);
						ctx.lineTo(targets[curTarget].imgwidth,yright);
						ctx.stroke();
					}
				}
			}
			else{//quadratic inequalities
				if (ineqtypes[curTarget][i]==10.3) {//solid parabola
					if (x2 != ineqlines[curTarget][i][0][0]) {
						if (y2==ineqlines[curTarget][i][0][1]) {
							ctx.moveTo(0,y2);
							ctx.lineTo(targets[curTarget].imgwidth,y2);
						} else {
							var stretch = (y2 - ineqlines[curTarget][i][0][1])/((x2 - ineqlines[curTarget][i][0][0])*(x2 - ineqlines[curTarget][i][0][0]));
							if (y2>ineqlines[curTarget][i][0][1]) {
								//crosses at y=imgheight
								var inta = Math.sqrt((targets[curTarget].imgheight - ineqlines[curTarget][i][0][1])/stretch)+ineqlines[curTarget][i][0][0];
								var intb = -1*Math.sqrt((targets[curTarget].imgheight - ineqlines[curTarget][i][0][1])/stretch)+ineqlines[curTarget][i][0][0];
								var cnty = ineqlines[curTarget][i][0][1] - (targets[curTarget].imgheight - ineqlines[curTarget][i][0][1]);
								var qy = targets[curTarget].imgheight;
							} else {
								var inta = Math.sqrt((0 - ineqlines[curTarget][i][0][1])/stretch)+ineqlines[curTarget][i][0][0];
								var intb = -1*Math.sqrt((0 - ineqlines[curTarget][i][0][1])/stretch)+ineqlines[curTarget][i][0][0];
								var cnty = 2*ineqlines[curTarget][i][0][1];
								var qy = 0;
							}
							var cp1x = inta + 2.0/3.0*(ineqlines[curTarget][i][0][0] - inta);
							var cp1y = qy + 2.0/3.0*(cnty - qy);
							var cp2x = cp1x + (intb - inta)/3.0;
							var cp2y = cp1y;
							ctx.moveTo(inta,qy);
							ctx.bezierCurveTo(cp1x,cp1y,cp2x,cp2y,intb,qy);
						}
					}
					ctx.stroke();
				} else {//10.4, dashed parabola
					if(x2 != ineqlines[curTarget][i][0][0]){
						dashedParabola(ctx,ineqlines[curTarget][i][0][0],ineqlines[curTarget][i][0][1],x2,y2,targets[curTarget].imgwidth,targets[curTarget].imgheight);
					}
				}
			}
		}
		ctx.beginPath();
		for (var j=0; j<ineqlines[curTarget][i].length; j++) {
			if (j==2) {
				ctx.arc(ineqlines[curTarget][i][j][0],ineqlines[curTarget][i][j][1],4,0,Math.PI*2,true);
				ctx.fill();
			} else {
				ctx.fillRect(ineqlines[curTarget][i][j][0]-3,ineqlines[curTarget][i][j][1]-3,6,6);
			}
		}
		if (ineqlines[curTarget][i].length==1 && x2!=null) {
			ctx.fillRect(x2-3,y2-3,6,6);
		}
		ctx.beginPath();
	}
	ctx.fillStyle = "rgb(0,0,255)";
	if (drawlocky[curTarget]==1) {
		ctx.lineWidth = 4;
	} else {
		ctx.lineWidth = 2;
	}
	ctx.strokeStyle = "rgb(0,0,255)";
	for (var i=0;i<tplines[curTarget].length; i++) {
		if (tptypes[curTarget][i]>=5 && tptypes[curTarget][i]<6) {//if a tpline
			var slope = null;
			var x2 = null;
			var y2 = null; var u, uperp;
			if (tplines[curTarget][i].length==2) {  //if two points set
				x2 = tplines[curTarget][i][1][0];
				y2 = tplines[curTarget][i][1][1];
			} else if (curTPcurve==i && x!=null && tplines[curTarget][i].length==1) {  //one point set, use mouse pos for third
				x2 = x;
				y2 = y;
			}
			if (x2 != null) { //at least one point set
				if (tptypes[curTarget][i]==5.3 || tptypes[curTarget][i]==5.4) {
					ctx.moveTo(tplines[curTarget][i][0][0],tplines[curTarget][i][0][1]);
					ctx.lineTo(x2,y2);
					if (tptypes[curTarget][i]==5.4) {
						var u = [x2 - tplines[curTarget][i][0][0], y2 - tplines[curTarget][i][0][1]];
						var d = Math.sqrt(u[0]*u[0]+u[1]*u[1]);
						if (d > 0.0001) {
						 	 u = [u[0]/d, u[1]/d];
						 	 uperp = [-u[1],u[0]];
						 	 ctx.moveTo(x2 - 15*u[0]-4*uperp[0],y2-15*u[1]-5*uperp[1]);
						 	 ctx.lineTo(x2,y2);
						 	 ctx.moveTo(x2 - 15*u[0]+4*uperp[0],y2-15*u[1]+5*uperp[1]);
						 	 ctx.lineTo(x2,y2);
						 }
					}
				} else {
					if (x2!=tplines[curTarget][i][0][0]) {
						var slope = (y2 - tplines[curTarget][i][0][1])/(x2-tplines[curTarget][i][0][0]);
					}
					if (Math.abs(x2-tplines[curTarget][i][0][0])<1 || Math.abs(slope)>100) { //vert line
						//document.getElementById("ans0-0").innerHTML = 'vert';
						if (tptypes[curTarget][i]==5.2) {
							ctx.moveTo(tplines[curTarget][i][0][0],tplines[curTarget][i][0][1]);
							if (y2>tplines[curTarget][i][0][1]) {
								ctx.lineTo(tplines[curTarget][i][0][0],targets[curTarget].imgheight);
							} else {
								ctx.lineTo(tplines[curTarget][i][0][0],0);
							}
						} else {
							ctx.moveTo(tplines[curTarget][i][0][0],0);
							ctx.lineTo(tplines[curTarget][i][0][0],targets[curTarget].imgheight);
						}
					} else {
						var yleft = tplines[curTarget][i][0][1] - slope*tplines[curTarget][i][0][0];
						var yright = tplines[curTarget][i][0][1] + slope*(targets[curTarget].imgwidth-tplines[curTarget][i][0][0]);

						//document.getElementById("ans0-0").innerHTML = slope;
						var yleft = tplines[curTarget][i][0][1] - slope*tplines[curTarget][i][0][0];
						var yright = tplines[curTarget][i][0][1] + slope*(targets[curTarget].imgwidth-tplines[curTarget][i][0][0]);
						if (tptypes[curTarget][i]==5.2) {
							ctx.moveTo(tplines[curTarget][i][0][0],tplines[curTarget][i][0][1]);
							if (x2>tplines[curTarget][i][0][0]) {
								ctx.lineTo(targets[curTarget].imgwidth,yright);
							} else {
								ctx.lineTo(0,yleft);
							}
						} else {
							//TODO:  fix for very large slopes;
							ctx.moveTo(0,yleft);
							ctx.lineTo(targets[curTarget].imgwidth,yright);
						}
					}
				}
			}
		} else if (tptypes[curTarget][i]==6 || tptypes[curTarget][i]==6.1) {//if a tp parabola
			var y2 = null;
			var x2 = null;
			if (tplines[curTarget][i].length==2) {
				x2 = tplines[curTarget][i][1][0];
				y2 = tplines[curTarget][i][1][1];
			} else if (curTPcurve==i && x!=null && tplines[curTarget][i].length==1) {
				x2 = x;
				y2 = y;
			}
			if (x2 != null) {
				if (tptypes[curTarget][i]==6) {
					if (y2==tplines[curTarget][i][0][1]) {
						ctx.moveTo(0,y2);
						ctx.lineTo(targets[curTarget].imgwidth,y2);
					} else if (x2 == tplines[curTarget][i][0][0]) {
						ctx.moveTo(x2,tplines[curTarget][i][0][1]);
						if (y2>tplines[curTarget][i][0][1]) {
							ctx.lineTo(x2,targets[curTarget].imgheight);
						} else {
							ctx.lineTo(x2,0);
						}
					} else {
						var stretch = (y2 - tplines[curTarget][i][0][1])/((x2 - tplines[curTarget][i][0][0])*(x2 - tplines[curTarget][i][0][0]));
						if (y2>tplines[curTarget][i][0][1]) {
							//crosses at y=imgheight
							var inta = Math.sqrt((targets[curTarget].imgheight - tplines[curTarget][i][0][1])/stretch)+tplines[curTarget][i][0][0];
							var intb = -1*Math.sqrt((targets[curTarget].imgheight - tplines[curTarget][i][0][1])/stretch)+tplines[curTarget][i][0][0];
							var cnty = tplines[curTarget][i][0][1] - (targets[curTarget].imgheight - tplines[curTarget][i][0][1]);
							var qy = targets[curTarget].imgheight;
						} else {
							var inta = Math.sqrt((0 - tplines[curTarget][i][0][1])/stretch)+tplines[curTarget][i][0][0];
							var intb = -1*Math.sqrt((0 - tplines[curTarget][i][0][1])/stretch)+tplines[curTarget][i][0][0];
							var cnty = 2*tplines[curTarget][i][0][1];
							var qy = 0;
						}
						var cp1x = inta + 2.0/3.0*(tplines[curTarget][i][0][0] - inta);
						var cp1y = qy + 2.0/3.0*(cnty - qy);
						var cp2x = cp1x + (intb - inta)/3.0;
						var cp2y = cp1y;
						ctx.moveTo(inta,qy);
						ctx.bezierCurveTo(cp1x,cp1y,cp2x,cp2y,intb,qy);
					}
				} else if (tptypes[curTarget][i]==6.1) {
					if (x2==tplines[curTarget][i][0][0]) {
						ctx.moveTo(x2,0);
						ctx.lineTo(x2,targets[curTarget].imgheight);
					} else if (y2 == tplines[curTarget][i][0][1]) {
						ctx.moveTo(tplines[curTarget][i][0][0],y2);
						if (x2>tplines[curTarget][i][0][0]) {
							ctx.lineTo(targets[curTarget].imgwidth,y2);
						} else {
							ctx.lineTo(0,y2);
						}
					} else {
						var stretch = (x2 - tplines[curTarget][i][0][0])/((y2 - tplines[curTarget][i][0][1])*(y2 - tplines[curTarget][i][0][1]));
						if (x2>tplines[curTarget][i][0][0]) {
							//crosses at x=imgwidth
							var inta = Math.sqrt((targets[curTarget].imgwidth - tplines[curTarget][i][0][0])/stretch)+tplines[curTarget][i][0][1];
							var intb = -1*Math.sqrt((targets[curTarget].imgwidth - tplines[curTarget][i][0][0])/stretch)+tplines[curTarget][i][0][1];
							var cntx = tplines[curTarget][i][0][0] - (targets[curTarget].imgwidth - tplines[curTarget][i][0][0]);
							var qx = targets[curTarget].imgwidth;
						} else {
							var inta = Math.sqrt((0 - tplines[curTarget][i][0][0])/stretch)+tplines[curTarget][i][0][1];
							var intb = -1*Math.sqrt((0 - tplines[curTarget][i][0][0])/stretch)+tplines[curTarget][i][0][1];
							var cntx = 2*tplines[curTarget][i][0][0];
							var qx = 0;
						}
						var cp1y = inta + 2.0/3.0*(tplines[curTarget][i][0][1] - inta);
						var cp1x = qx + 2.0/3.0*(cntx - qx);
						var cp2y = cp1y + (intb - inta)/3.0;
						var cp2x = cp1x;
						ctx.moveTo(qx,inta);
						ctx.bezierCurveTo(cp1x,cp1y,cp2x,cp2y,qx,intb);
					}
				}

			}
		} else if (tptypes[curTarget][i]==6.5) {//if a tp sqrtt
			var y2 = null;
			var x2 = null;
			if (tplines[curTarget][i].length==2) {
				x2 = tplines[curTarget][i][1][0];
				y2 = tplines[curTarget][i][1][1];
			} else if (curTPcurve==i && x!=null && tplines[curTarget][i].length==1) {
				x2 = x;
				y2 = y;
			}
			if (x2 != null && x2!=tplines[curTarget][i][0][0]) {
				if (y2==tplines[curTarget][i][0][1]) {
					ctx.moveTo(tplines[curTarget][i][0][0],y2);
					if (x2 > tplines[curTarget][i][0][0]) {
						ctx.lineTo(targets[curTarget].imgwidth,y2);
					} else {
						ctx.lineTo(0,y2);
					}
				} else {
					var flip = (x2 < tplines[curTarget][i][0][0])?-1:1;
					var stretch = (y2-tplines[curTarget][i][0][1])/Math.sqrt(flip*(x2-tplines[curTarget][i][0][0]));
					ctx.moveTo(tplines[curTarget][i][0][0],tplines[curTarget][i][0][1]);
					curx = tplines[curTarget][i][0][0]; cury = tplines[curTarget][i][0][1];

					do {
						curx += flip*3;
						ctx.lineTo(curx, stretch*Math.sqrt(flip*(curx - tplines[curTarget][i][0][0])) + tplines[curTarget][i][0][1]);
					} while (curx > 0 && curx < targets[curTarget].imgwidth && cury > 0 && cury < targets[curTarget].imgheight);
				}
			}
		} else if (tptypes[curTarget][i]>=7 && tptypes[curTarget][i]<8) {//if a tp circle
			var y2 = null;
			var x2 = null;
			if (tplines[curTarget][i].length==2) {
				x2 = tplines[curTarget][i][1][0];
				y2 = tplines[curTarget][i][1][1];
			} else if (curTPcurve==i && x!=null && tplines[curTarget][i].length==1) {
				x2 = x;
				y2 = y;
			}
			if (x2 != null && (x2!=tplines[curTarget][i][0][0] || y2!=tplines[curTarget][i][0][1])) {
				if (tptypes[curTarget][i]==7) { //is a tp circle
					var rad = Math.sqrt((x2-tplines[curTarget][i][0][0])*(x2-tplines[curTarget][i][0][0]) + (y2-tplines[curTarget][i][0][1])*(y2-tplines[curTarget][i][0][1]));
					ctx.arc(tplines[curTarget][i][0][0],tplines[curTarget][i][0][1],rad,0,2*Math.PI,true);
				} else if (tptypes[curTarget][i]==7.2) { //if a tp ellipse
					var rx = Math.abs(x2-tplines[curTarget][i][0][0]);
					var ry = Math.abs(y2-tplines[curTarget][i][0][1]);
					if (curTPcurve==i || (dragObj != null && dragObj.num==i)) {
						ctx.strokeStyle = "rgb(0,255,255)";
						ctx.lineWidth = 1;
						ctx.dashedLine(x2,y2,x2-2*(x2-tplines[curTarget][i][0][0]),y2,5);
						ctx.dashedLine(x2,y2,x2,y2-2*(y2-tplines[curTarget][i][0][1]),5);
						ctx.dashedLine(x2,y2-2*(y2-tplines[curTarget][i][0][1]),x2-2*(x2-tplines[curTarget][i][0][0]),y2-2*(y2-tplines[curTarget][i][0][1]),5);
						ctx.dashedLine(x2-2*(x2-tplines[curTarget][i][0][0]),y2,x2-2*(x2-tplines[curTarget][i][0][0]),y2-2*(y2-tplines[curTarget][i][0][1]),5);
						ctx.lineWidth = 2;
					}
					ctx.strokeStyle = "rgb(0,0,255)";
					ctx.save(); // save state
					ctx.beginPath();
					ctx.translate(tplines[curTarget][i][0][0]-rx, tplines[curTarget][i][0][1]-ry);
					ctx.scale(rx, ry);
					ctx.arc(1, 1, 1, 0, 2 * Math.PI, false);
					ctx.restore(); // restore to original state
				} else if (tptypes[curTarget][i]==7.4) { //if a tp vert hyperbola
					var b = Math.abs(x2-tplines[curTarget][i][0][0]);
					var a = Math.abs(y2-tplines[curTarget][i][0][1]);
					var m = Math.abs(a/b);
					ctx.strokeStyle = "rgb(0,255,0)";
					ctx.dashedLine(tplines[curTarget][i][0][0],tplines[curTarget][i][0][1],targets[curTarget].imgwidth,tplines[curTarget][i][0][1]+m*(targets[curTarget].imgwidth-tplines[curTarget][i][0][0]));
					ctx.dashedLine(tplines[curTarget][i][0][0],tplines[curTarget][i][0][1],targets[curTarget].imgwidth,tplines[curTarget][i][0][1]-m*(targets[curTarget].imgwidth-tplines[curTarget][i][0][0]));
					ctx.dashedLine(tplines[curTarget][i][0][0],tplines[curTarget][i][0][1],0,tplines[curTarget][i][0][1]-m*tplines[curTarget][i][0][0]);
					ctx.dashedLine(tplines[curTarget][i][0][0],tplines[curTarget][i][0][1],0,tplines[curTarget][i][0][1]+m*tplines[curTarget][i][0][0]);
					if (curTPcurve==i || (dragObj != null && dragObj.num==i)) {
						ctx.strokeStyle = "rgb(0,255,255)";
						ctx.lineWidth = 1;
						ctx.dashedLine(x2,y2,x2-2*(x2-tplines[curTarget][i][0][0]),y2,5);
						ctx.dashedLine(x2,y2,x2,y2-2*(y2-tplines[curTarget][i][0][1]),5);
						ctx.dashedLine(x2,y2-2*(y2-tplines[curTarget][i][0][1]),x2-2*(x2-tplines[curTarget][i][0][0]),y2-2*(y2-tplines[curTarget][i][0][1]),5);
						ctx.dashedLine(x2-2*(x2-tplines[curTarget][i][0][0]),y2,x2-2*(x2-tplines[curTarget][i][0][0]),y2-2*(y2-tplines[curTarget][i][0][1]),5);
						ctx.lineWidth = 2;
					}
					ctx.beginPath();
					ctx.strokeStyle = "rgb(0,0,255)";
					for (var curx=0;curx < targets[curTarget].imgwidth+4;curx += 3) {
						cury = tplines[curTarget][i][0][1] + Math.sqrt((Math.pow(curx-tplines[curTarget][i][0][0], 2)/(b*b) + 1)*a*a);
						if (cury<-100) { cury = -100;}
						if (cury>targets[curTarget].imgheight+100) { cury=targets[curTarget].imgheight+100;}
						if (curx==0) {
							ctx.moveTo(curx,cury);
						} else {
							ctx.lineTo(curx,cury);
						}
					}
					ctx.stroke();
					ctx.beginPath();
					for (var curx=0;curx < targets[curTarget].imgwidth+4;curx += 3) {
						cury = tplines[curTarget][i][0][1] - Math.sqrt((Math.pow(curx-tplines[curTarget][i][0][0], 2)/(b*b) + 1)*a*a);
						if (cury<-100) { cury = -100;}
						if (cury>targets[curTarget].imgheight+100) { cury=targets[curTarget].imgheight+100;}
						if (curx==0) {
							ctx.moveTo(curx,cury);
						} else {
							ctx.lineTo(curx,cury);
						}
					}
				} else if (tptypes[curTarget][i]==7.5) { //if a tp horiz hyperbola
					var a = Math.abs(x2-tplines[curTarget][i][0][0]);
					var b = Math.abs(y2-tplines[curTarget][i][0][1]);
					var m = Math.abs(b/a);
					ctx.strokeStyle = "rgb(0,255,0)";
					ctx.dashedLine(tplines[curTarget][i][0][0],tplines[curTarget][i][0][1],targets[curTarget].imgwidth,tplines[curTarget][i][0][1]+m*(targets[curTarget].imgwidth-tplines[curTarget][i][0][0]));
					ctx.dashedLine(tplines[curTarget][i][0][0],tplines[curTarget][i][0][1],targets[curTarget].imgwidth,tplines[curTarget][i][0][1]-m*(targets[curTarget].imgwidth-tplines[curTarget][i][0][0]));
					ctx.dashedLine(tplines[curTarget][i][0][0],tplines[curTarget][i][0][1],0,tplines[curTarget][i][0][1]-m*tplines[curTarget][i][0][0]);
					ctx.dashedLine(tplines[curTarget][i][0][0],tplines[curTarget][i][0][1],0,tplines[curTarget][i][0][1]+m*tplines[curTarget][i][0][0]);
					if (curTPcurve==i || (dragObj != null && dragObj.num==i)) {
						ctx.strokeStyle = "rgb(0,255,255)";
						ctx.lineWidth = 1;
						ctx.dashedLine(x2,y2,x2-2*(x2-tplines[curTarget][i][0][0]),y2,5);
						ctx.dashedLine(x2,y2,x2,y2-2*(y2-tplines[curTarget][i][0][1]),5);
						ctx.dashedLine(x2,y2-2*(y2-tplines[curTarget][i][0][1]),x2-2*(x2-tplines[curTarget][i][0][0]),y2-2*(y2-tplines[curTarget][i][0][1]),5);
						ctx.dashedLine(x2-2*(x2-tplines[curTarget][i][0][0]),y2,x2-2*(x2-tplines[curTarget][i][0][0]),y2-2*(y2-tplines[curTarget][i][0][1]),5);
						ctx.lineWidth = 2;
					}
					ctx.beginPath();
					ctx.strokeStyle = "rgb(0,0,255)";
					for (var cury=0;cury < targets[curTarget].imgwidth+4;cury += 3) {
						curx = tplines[curTarget][i][0][0] + Math.sqrt((Math.pow(cury-tplines[curTarget][i][0][1], 2)/(b*b) + 1)*a*a);
						if (curx<-100) { curx = -100;}
						if (curx>targets[curTarget].imgwidth+100) { curx=targets[curTarget].imgwidth+100;}
						if (cury==0) {
							ctx.moveTo(curx,cury);
						} else {
							ctx.lineTo(curx,cury);
						}
					}
					ctx.stroke();
					ctx.beginPath();
					for (var cury=0;cury < targets[curTarget].imgwidth+4;cury += 3) {
						curx = tplines[curTarget][i][0][0] - Math.sqrt((Math.pow(cury-tplines[curTarget][i][0][1], 2)/(b*b) + 1)*a*a);
						if (curx<-100) { curx = -100;}
						if (curx>targets[curTarget].imgwidth+100) { curx=targets[curTarget].imgwidth+100;}
						if (cury==0) {
							ctx.moveTo(curx,cury);
						} else {
							ctx.lineTo(curx,cury);
						}
					}
				}
			}
		} else if (tptypes[curTarget][i]==8) {//if a tp absolute value
			var slope = null;
			var x2 = null;
			var y2 = null;
			if (tplines[curTarget][i].length==2) {  //if two points set
				x2 = tplines[curTarget][i][1][0];
				y2 = tplines[curTarget][i][1][1];
			} else if (curTPcurve==i && x!=null && tplines[curTarget][i].length==1) {  //one point set, use mouse pos for third
				x2 = x;
				y2 = y;
			}
			if (x2 != null) { //at least one point set
				if (x2!=tplines[curTarget][i][0][0]) {
					var slope = (y2 - tplines[curTarget][i][0][1])/(x2-tplines[curTarget][i][0][0]);
					if (x2<tplines[curTarget][i][0][0]) {  //want slope on right
						slope *= -1;
					}
				}
				if (Math.abs(x2-tplines[curTarget][i][0][0])<1 || Math.abs(slope)>100) { //vert line
					ctx.moveTo(tplines[curTarget][i][0][0],tplines[curTarget][i][0][1]);
					if (y2>tplines[curTarget][i][0][1]) {
						ctx.lineTo(tplines[curTarget][i][0][0],targets[curTarget].imgheight);
					} else {
						ctx.lineTo(tplines[curTarget][i][0][0],0);
					}
				} else {
					var yleft = tplines[curTarget][i][0][1] + slope*tplines[curTarget][i][0][0];
					var yright = tplines[curTarget][i][0][1] + slope*(targets[curTarget].imgwidth-tplines[curTarget][i][0][0]);
					ctx.moveTo(0,yleft);
					ctx.lineTo(tplines[curTarget][i][0][0],tplines[curTarget][i][0][1]);
					ctx.lineTo(targets[curTarget].imgwidth,yright);

				}

			}
		} else if (tptypes[curTarget][i]==8.3) {//if a tp exponential (unshifted)
			var y2 = null;
			var x2 = null;
			if (tplines[curTarget][i].length==2) {
				x2 = tplines[curTarget][i][1][0];
				y2 = tplines[curTarget][i][1][1];
			} else if (curTPcurve==i && x!=null && tplines[curTarget][i].length==1) {
				x2 = x;
				y2 = y;
			}
			if (x2 != null && x2!=tplines[curTarget][i][0][0]) {
				if (y2==tplines[curTarget][i][0][1]) {
					ctx.moveTo(0,y2);
					ctx.lineTo(targets[curTarget].imgwidth,y2);
				} else {
					// (x1, y1) (x2, y2)
					// b^(x2-x1) = y2/y1
					// a = y1/b^x1

					var originy = targets[curTarget].ymax*targets[curTarget].pixpery + targets[curTarget].imgborder;
					var adjy1 = originy - tplines[curTarget][i][0][1];
					var adjy2 = originy - y2;
					if (adjy1*adjy2>0 && x2 != tplines[curTarget][i][0][0]) {
						var expbase = safepow(adjy2/adjy1, 1/(x2-tplines[curTarget][i][0][0]));
						var stretch = adjy2/safepow(expbase,x2);
						ctx.moveTo(tplines[curTarget][i][0][0],tplines[curTarget][i][0][1]);
						var cury = 0;
						for (var curx=0;curx < targets[curTarget].imgwidth+4;curx += 3) {
							cury = originy - stretch*safepow(expbase,curx);
							if (cury<-100) { cury = -100;}
							if (cury>targets[curTarget].imgheight+100) { cury=targets[curTarget].imgheight+100;}
							if (curx==0) {
								ctx.moveTo(curx,cury);
							} else {
								ctx.lineTo(curx,cury);
							}
						}
					}
				}
			}
		} else if (tptypes[curTarget][i]==8.4) {//if a tp log (unshifted)
			var y2 = null;
			var x2 = null;
			if (tplines[curTarget][i].length==2) {
				x2 = tplines[curTarget][i][1][0];
				y2 = tplines[curTarget][i][1][1];
			} else if (curTPcurve==i && x!=null && tplines[curTarget][i].length==1) {
				x2 = x;
				y2 = y;
			}
			if (x2 != null && x2!=tplines[curTarget][i][0][0] && y2!=tplines[curTarget][i][0][1]) {
				// Treat as x = ab^y
				// (x1, y1) (x2, y2)
				// b^(y2-y1) = x2/x1
				// a = x1/b^y1

				var originx = -targets[curTarget].xmin*targets[curTarget].pixperx + targets[curTarget].imgborder;
				var adjx1 = originx - tplines[curTarget][i][0][0];
				var adjx2 = originx - x2;
				if (adjx1*adjx2>0 && y2 != tplines[curTarget][i][0][1]) {
					var expbase = safepow(adjx2/adjx1, 1/(y2-tplines[curTarget][i][0][1]));
					var stretch = adjx2/safepow(expbase,y2);
					ctx.moveTo(tplines[curTarget][i][0][0],tplines[curTarget][i][0][1]);
					var cury = 0;
					for (var cury=0;cury < targets[curTarget].imgheight+4;cury += 3) {
						curx = originx - stretch*safepow(expbase,cury);
						if (curx<-100) { curx = -100;}
						if (curx>targets[curTarget].imgwidth+100) { curx=targets[curTarget].imgwidth+100;}
						if (cury==0) {
							ctx.moveTo(curx,cury);
						} else {
							ctx.lineTo(curx,cury);
						}
					}
				}

			}
		} else if (tptypes[curTarget][i]==8.2) {//if a tp linear/linear rational
			var y2 = null;
			var x2 = null;
			if (tplines[curTarget][i].length==2) {
				x2 = tplines[curTarget][i][1][0];
				y2 = tplines[curTarget][i][1][1];
			} else if (curTPcurve==i && x!=null && tplines[curTarget][i].length==1) {
				x2 = x;
				y2 = y;
			}
			ctx.strokeStyle = "rgb(0,255,0)";
			ctx.dashedLine(5,tplines[curTarget][i][0][1],targets[curTarget].imgwidth,tplines[curTarget][i][0][1]);
			ctx.dashedLine(tplines[curTarget][i][0][0],5,tplines[curTarget][i][0][0],targets[curTarget].imgheight);
			ctx.beginPath();
			ctx.strokeStyle = "rgb(0,0,255)";
			if (x2 != null && x2!=tplines[curTarget][i][0][0] && y2!=tplines[curTarget][i][0][1]) {

				//y = c/(x-p) + k
				var stretch = (y2 - tplines[curTarget][i][0][1])*(x2 - tplines[curTarget][i][0][0]);

				for (var curx=tplines[curTarget][i][0][0]-1;curx>-4;curx -= 3) {
					cury = stretch/(curx - tplines[curTarget][i][0][0]) + tplines[curTarget][i][0][1];
					if (cury<-100) { cury = -100;}
					if (cury>targets[curTarget].imgheight+100) { cury=targets[curTarget].imgheight+100;}
					if (curx==tplines[curTarget][i][0][0]-1) {
						ctx.moveTo(curx,cury);
					} else {
						ctx.lineTo(curx,cury);
					}
				}
				for (var curx=tplines[curTarget][i][0][0]+1;curx<targets[curTarget].imgwidth+4;curx += 3) {
					cury = stretch/(curx - tplines[curTarget][i][0][0]) + tplines[curTarget][i][0][1];
					if (cury<-100) { cury = -100;}
					if (cury>targets[curTarget].imgheight+100) { cury=targets[curTarget].imgheight+100;}
					if (curx==tplines[curTarget][i][0][0]+1) {
						ctx.moveTo(curx,cury);
					} else {
						ctx.lineTo(curx,cury);
					}
				}
				ctx.stroke();
			}
		} else if (tptypes[curTarget][i]==9  || tptypes[curTarget][i]==9.1 ) {//if a tp sin/cos
			var y2 = null;
			var x2 = null;
			if (tplines[curTarget][i].length==2) {
				x2 = tplines[curTarget][i][1][0];
				y2 = tplines[curTarget][i][1][1];
			} else if (curTPcurve==i && x!=null && tplines[curTarget][i].length==1) {
				x2 = x;
				y2 = y;
			}
			if (x2 != null && x2!=tplines[curTarget][i][0][0]) {
				if (y2==tplines[curTarget][i][0][1]) {
					ctx.moveTo(0,y2);
					ctx.lineTo(targets[curTarget].imgwidth,y2);
				} else {
					if (tptypes[curTarget][i]==9) {
						var amp = -1*Math.abs(y2-tplines[curTarget][i][0][1])/2;
						var mid = (y2+tplines[curTarget][i][0][1])/2;
						var stretch = Math.PI/Math.abs(x2-tplines[curTarget][i][0][0]);
						var horizs = (y2 < tplines[curTarget][i][0][1])?x2:tplines[curTarget][i][0][0];
					} else if (tptypes[curTarget][i]==9.1) {
						var amp = -1*Math.abs(y2-tplines[curTarget][i][0][1]);
						var mid = tplines[curTarget][i][0][1];
						var stretch = 0.5*Math.PI/Math.abs(x2-tplines[curTarget][i][0][0]);
						var horizs = (y2 < tplines[curTarget][i][0][1])?x2:(x2+2*Math.abs(x2-tplines[curTarget][i][0][0]));
					}

					var cury = 0;
					for (var curx=0;curx < targets[curTarget].imgwidth+4;curx += 3) {
						cury = amp*Math.cos(stretch*(curx - horizs)) + mid;
						if (curx==0) {
							ctx.moveTo(curx,cury);
						} else {
							ctx.lineTo(curx,cury);
						}
					}
				}
			}
		}
		ctx.stroke();
		ctx.beginPath();
	}
	var linefirstx, linefirsty, linelastx, linelasty;
	ctx.fillStyle = 'rgba(0,0,255,.5)';
	for (var i=0;i<lines[curTarget].length; i++) {
		ctx.beginPath();
		for (var j=0;j<lines[curTarget][i].length; j++) {
			if (j==0) {
				ctx.moveTo(lines[curTarget][i][j][0],lines[curTarget][i][j][1]);
				linefirstx = lines[curTarget][i][j][0];
				linefirsty = lines[curTarget][i][j][1];
			} else {
				ctx.lineTo(lines[curTarget][i][j][0],lines[curTarget][i][j][1]);
				linelastx = lines[curTarget][i][j][0];
				linelasty = lines[curTarget][i][j][1];
			}
		}
		if (i==curLine && x!=null) {
			ctx.lineTo(x,y);
			linelastx = x;
			linelasty = y;
		}
		var arrowsize = targets[curTarget].imgwidth*.02;
		if (drawlocky[curTarget]==1 && linelastx>targets[curTarget].imgwidth*.98) {
			ctx.moveTo(linelastx,linelasty);
			ctx.lineTo(linelastx-arrowsize,linelasty+arrowsize);
			ctx.moveTo(linelastx,linelasty);
			ctx.lineTo(linelastx-arrowsize,linelasty-arrowsize);
		} else if (drawlocky[curTarget]==1 && linefirstx>targets[curTarget].imgwidth*.98) {
			ctx.moveTo(linefirstx,linefirsty);
			ctx.lineTo(linefirstx-arrowsize,linefirsty+arrowsize);
			ctx.moveTo(linefirstx,linefirsty);
			ctx.lineTo(linefirstx-arrowsize,linefirsty-arrowsize);
		}
		if (drawlocky[curTarget]==1 && linelastx<targets[curTarget].imgwidth*.02) {
			ctx.moveTo(linelastx,linelasty);
			ctx.lineTo(linelastx+arrowsize,linelasty+arrowsize);
			ctx.moveTo(linelastx,linelasty);
			ctx.lineTo(linelastx+arrowsize,linelasty-arrowsize);
		} else if (drawlocky[curTarget]==1 && linefirstx<targets[curTarget].imgwidth*.02) {
			ctx.moveTo(linefirstx,linefirsty);
			ctx.lineTo(linefirstx+arrowsize,linefirsty+arrowsize);
			ctx.moveTo(linefirstx,linefirsty);
			ctx.lineTo(linefirstx+arrowsize,linefirsty-arrowsize);
		}
		ctx.stroke();
		if (targets[curTarget].dotline>1) {
			var ml = lines[curTarget][i].length-1;
			if (ml<1) {continue;}
			if (Math.pow((linelastx - linefirstx),2)+Math.pow((linelasty - linefirsty),2)<25) {
				//ctx.fillStyle('rgba(0,0,255,.5)');
				ctx.moveTo(lines[curTarget][i][0][0],lines[curTarget][i][0][1]);
				for (var j=1;j<lines[curTarget][i].length; j++) {
					ctx.lineTo(lines[curTarget][i][j][0],lines[curTarget][i][j][1]);
				}
				ctx.closePath();
				ctx.fill();
			}
		}
	}
	ctx.fillStyle = 'rgb(0,0,255)';
	ctx.beginPath();
	for (var i=0; i<dots[curTarget].length; i++) {
		ctx.moveTo(dots[curTarget][i][0]+5,dots[curTarget][i][1]);
		ctx.arc(dots[curTarget][i][0],dots[curTarget][i][1],5,0,Math.PI*2,true);
	}
	ctx.fill();
	if (targets[curTarget].dotline>0) {
		ctx.beginPath();
		for (var i=0;i<lines[curTarget].length; i++) {
			for (var j=0;j<lines[curTarget][i].length; j++) {
				if ((j==0) || Math.pow((lines[curTarget][i][j][0] - lines[curTarget][i][j-1][0]),2)+Math.pow((lines[curTarget][i][j][1] - lines[curTarget][i][j-1][1]),2)>25) {
					ctx.moveTo(lines[curTarget][i][j][0]+5,lines[curTarget][i][j][1]);
					ctx.arc(lines[curTarget][i][j][0],lines[curTarget][i][j][1],5,0,Math.PI*2,true);
				}
			}
		}
		ctx.fill();
	}

	ctx.fillStyle = "rgb(255,255,255)";
	ctx.beginPath();
	for (var i=0; i<odots[curTarget].length; i++) {
		ctx.moveTo(odots[curTarget][i][0]+5,odots[curTarget][i][1]);
		ctx.arc(odots[curTarget][i][0],odots[curTarget][i][1],4,0,Math.PI*2,true);
	}
	if (targets[curTarget].mode<3) {
		ctx.fill();
	} else {
		ctx.stroke();
	}
	ctx.lineWidth = 2;
	ctx.beginPath();
	for (var i=0; i<odots[curTarget].length; i++) {
		ctx.moveTo(odots[curTarget][i][0]+5,odots[curTarget][i][1]);
		ctx.arc(odots[curTarget][i][0],odots[curTarget][i][1],4,0,Math.PI*2,true);
	}
	ctx.stroke();

	for (var i=0;i<tplines[curTarget].length; i++) {
		//draw control points
		if (tptypes[curTarget][i]==targets[curTarget].mode) {
			ctx.fillStyle = "rgb(255,0,0)";
			for (var j=0; j<tplines[curTarget][i].length; j++) {
				ctx.fillRect(tplines[curTarget][i][j][0]-3,tplines[curTarget][i][j][1]-3,6,6);
			}
			if (tplines[curTarget][i].length==1 && x!=null && curTPcurve==i) {
				ctx.fillRect(x-3,y-3,6,6);
			}
			ctx.fillStyle = "rgb(0,0,255)";
		}
	}

	encodeDraw();
	//targetOuts[curTarget].value =  php_serialize(lines[curTarget]) + ';;'+php_serialize(dots[curTarget])+ ';;'+php_serialize(odots[curTarget]);

}

function encodeDraw() {
	var out = '';
	for (var i=0;i<lines[curTarget].length; i++) {
		if (i!=0) {
			out += ';';
		}
		for (var j=0;j<lines[curTarget][i].length; j++) {
			if (j!=0) {
				out += ',';
			}
			out +=	'('+lines[curTarget][i][j][0]+','+lines[curTarget][i][j][1]+')';

		}
	}
	out += ';;';
	for (var i=0; i<dots[curTarget].length; i++) {
		if (i!=0) {
			out += ',';
		}
		out += '('+dots[curTarget][i][0]+','+dots[curTarget][i][1]+')';
	}
	out += ';;';
	for (var i=0; i<odots[curTarget].length; i++) {
		if (i!=0) {
			out += ',';
		}
		out += '('+odots[curTarget][i][0]+','+odots[curTarget][i][1]+')';
	}
	out += ';;';
	var tplineout = [];
	for (var i=0; i<tplines[curTarget].length; i++) {
		//if (i!=0) {
		//	out += ',';
		//}
		if (tplines[curTarget][i].length>1) {
			tplineout.push('('+tptypes[curTarget][i]+','+tplines[curTarget][i][0][0]+','+tplines[curTarget][i][0][1]+','+tplines[curTarget][i][1][0]+','+tplines[curTarget][i][1][1]+')');
		}
	}
	out += tplineout.join(",");
	out += ';;';
	var tpineqout = [];
	for (var i=0; i<ineqlines[curTarget].length; i++) {
		//if (i!=0) {
		//	out += ',';
		//}
		if (ineqlines[curTarget][i].length>2) {
			tpineqout.push('('+ineqtypes[curTarget][i]+','+ineqlines[curTarget][i][0][0]+','+ineqlines[curTarget][i][0][1]+','+ineqlines[curTarget][i][1][0]+','+ineqlines[curTarget][i][1][1]+','+ineqlines[curTarget][i][2][0]+','+ineqlines[curTarget][i][2][1]+')');
		}
	}
	out += tpineqout.join(",");
	targetOuts[curTarget].value = out;
}
var clickcnt=0;
function drawMouseDown(ev) {
	clickcnt++;
	clearAllDrawListners();
	if (hasTouch && ev.originalEvent.touches.length>1) {
		//hasTouch = false;
		didMultiTouch = true;
		//$(".tips").html("multi mousedown");
		//$(document).on("mousemove.imathasdraw", drawMouseMove);
		$(document).on("touchend.imathasdraw", drawMouseUp);
		return true;  //bypass when multitouching to prevent interference with pinch zoom
	} else {
		//$(".tips").html("other mousedown");
	}
	if (hasTouch) {
		window.clearTimeout(hasTouchTimer);
		$(".drawcanvas").on("touchstart.imathasdraw", function(ev) { hasTouch=true; drawMouseDown(ev);});
		$(".drawcanvas").on("touchmove.imathasdraw", drawMouseMove);
		$(document).on("touchend.imathasdraw", drawMouseUp);
		$(document).on("touchcancel.imathasdraw", drawMouseUp);
	} else {
		$(document).on("mousemove.imathasdraw", drawMouseMove);
		$(document).on("mouseup.imathasdraw", drawMouseUp);
	}
	var mousePos = mouseCoords(ev);
	//see if mouse click is inside a target; if so, select it (unless currently in a line from another target)
	if (curTarget==null || (curLine==null && curTPcurve==null && curIneqcurve==null)) {
		for (i in targets) {
			var tarelpos = getPosition(targets[i].el);
			if (tarelpos.x<mousePos.x && (tarelpos.x+targets[i].width>mousePos.x) && tarelpos.y<mousePos.y && (tarelpos.y+targets[i].height>mousePos.y)) {
				curTarget = i;
				break;
			}
		}
	}

	if (curTarget!=null) { //is a target currectly in action?
		ev.preventDefault();  //prevent scrolling
		mouseisdown = true;
		var tarelpos = getPosition(targets[curTarget].el);
		//$(".tips").html(curTPcurve+","+clickcnt);
		var mouseOff = {x:(mousePos.x - tarelpos.x), y: (mousePos.y-tarelpos.y)};

		//are we inside target region?
		if (mouseOff.x>-1 && mouseOff.x<targets[curTarget].width && mouseOff.y>-1 && mouseOff.y<targets[curTarget].height) {
			/*  not necessary
			if( navigator.userAgent.match(/Android/i) ) {
				ev.preventDefault(); //prevent pinch-zoom too
			}
			*/
			if (targets[curTarget].snaptogridx > 0) {mouseOff = snaptogrid(mouseOff,curTarget);}
			if (drawlocky[curTarget]==1) {
				mouseOff.y = targets[curTarget].imgheight/2;
			}

			//see if current point

			var foundpt = findnearpoint(curTarget,mouseOff);
			if (foundpt!=null) {
				if (curLine!=null && foundpt[0]<1 && curLine!=foundpt[1]) {
					foundpt = null;
				} else if (curTPcurve!=null & foundpt[0]>=5 && foundpt[0]<10 && curTPcurve!=foundpt[1]) {
					foundpt = null;
				} else if (curIneqcurve!=null && foundpt[0]>=10 && foundpt[0]<11 && curIneqcurve!=foundpt[1]) {
					foundpt = null;
				}
			}

			if (foundpt==null) { //not a current point
				setCursor('pendown');
				//targets[curTarget].el.style.cursor = 'url('+imasroot+'/img/pendown.cur), auto';
				if (targets[curTarget].mode==1) {//if in dot mode
					dots[curTarget].push([mouseOff.x,mouseOff.y]);
					dragObj = {mode: 1, num: dots[curTarget].length-1};
					//targets[curTarget].el.style.cursor = 'move';
				} else if (targets[curTarget].mode==2) {//if in open dot mode
					odots[curTarget].push([mouseOff.x,mouseOff.y]);
					dragObj = {mode: 2, num: odots[curTarget].length-1};
					//targets[curTarget].el.style.cursor = 'move';
				} else if (targets[curTarget].mode==0 || targets[curTarget].mode==0.7) { //in line mode
					if (curLine==null) { //start new line
						lines[curTarget].push([[mouseOff.x,mouseOff.y]]);
						curLine = lines[curTarget].length-1;
					} else {//in existing line
						lines[curTarget][curLine].push([mouseOff.x,mouseOff.y]);
					}
				} else if (targets[curTarget].mode==0.5) { //in single line mode
					if (curLine==null) { //start new line
						lines[curTarget].push([[mouseOff.x,mouseOff.y]]);
						curLine = lines[curTarget].length-1;
					} else {//in existing line
						lines[curTarget][curLine].push([mouseOff.x,mouseOff.y]);
						curLine = null;
						dragObj = null;
					}
				} else if (targets[curTarget].mode>=5 && targets[curTarget].mode<10) {//in twopoint mode
					if (curTPcurve==null) { //start new tpline
						tplines[curTarget].push([[mouseOff.x,mouseOff.y]]);
						curTPcurve = tplines[curTarget].length-1;
						tptypes[curTarget][curTPcurve] = targets[curTarget].mode;
						mouseisdown = false;
					} else {//in existing line
						tplines[curTarget][curTPcurve].push([mouseOff.x,mouseOff.y]);
						if (tplines[curTarget][curTPcurve].length==2) {
							//second point is set.  switch to drag and end line
							dragObj = {mode: targets[curTarget].mode, num: curTPcurve, subnum: 1};
							curTPcurve = null;
						}
					}
				} else if (targets[curTarget].mode>=10 && targets[curTarget].mode<11) {//in ineqline mode
					if (curIneqcurve==null) { //start new tpline
						ineqlines[curTarget].push([[mouseOff.x,mouseOff.y]]);
						curIneqcurve = ineqlines[curTarget].length-1;
						ineqtypes[curTarget][curIneqcurve] = targets[curTarget].mode;
						mouseisdown = false;
					} else {//in existing line
						ineqlines[curTarget][curIneqcurve].push([mouseOff.x,mouseOff.y]);
						if (ineqlines[curTarget][curIneqcurve].length==2) {
							dragObj = {mode: targets[curTarget].mode, num: curIneqcurve, subnum: 1};
						} else if (ineqlines[curTarget][curIneqcurve].length==3) {
							//second point is set.  switch to drag and end line
							dragObj = {mode: targets[curTarget].mode, num: curIneqcurve, subnum: 2};
							curIneqcurve = null;
						}
					}
				}
			} else { //clicked on current point
				if (foundpt[0]==0 || foundpt[0]==0.5) { //if point is on line
					if (curLine==null) {//not current in line
						setCursor('move');
						//targets[curTarget].el.style.cursor = 'move';
						//start dragging
						dragObj = {mode: targets[curTarget].mode, num: foundpt[1], subnum: foundpt[2]};
						oldpointpos = lines[curTarget][foundpt[1]][foundpt[2]];
						if (foundpt[2] == lines[curTarget][foundpt[1]].length-1 && targets[curTarget].mode==0) {
							//if last point in line, continue line too
							curLine = foundpt[1];
						} else if (foundpt[2] == 0 && targets[curTarget].mode==0) {
							curLine = foundpt[1];
							lines[curTarget][curLine].reverse();
							dragObj.subnum = lines[curTarget][curLine].length-1;
						}
					} else { //already in line
						if (foundpt[1]==curLine && foundpt[2] == lines[curTarget][foundpt[1]].length-1) {
							//clicked last point; end line
							if (lines[curTarget][curLine].length<2) {
								lines[curTarget].splice(curLine,1);
							}
							curLine = null;
						} else if (targets[curTarget].dotline>1 && foundpt[1]==curLine && foundpt[2]==0) {
							//clicked on first point, and are in closed poly mode.  Set point to same as first point and end line
							lines[curTarget][curLine].push(lines[curTarget][curLine][0]);
							curLine = null;
						} else {
							//if (foundpt[1]!=curLine) { //so long as point is not on current line, add it
								setCursor('pendown');
								//targets[curTarget].el.style.cursor = 'url('+imasroot+'/img/pendown.cur), auto';
								lines[curTarget][curLine].push([mouseOff.x,mouseOff.y]);
							//}
						}
					}
				} else if (foundpt[0]==1) { //if point is a dot
					setCursor('move');
					//targets[curTarget].el.style.cursor = 'move';
					dragObj = {mode: 1, num: foundpt[1]};
				} else if (foundpt[0]==2) { //if point is a open dot
					setCursor('move');
					//targets[curTarget].el.style.cursor = 'move';
					dragObj = {mode: 2, num: foundpt[1]};
				} else if (foundpt[0]>=5 && foundpt[0]<10) { //if point is on twopoint
					setCursor('move');
					//targets[curTarget].el.style.cursor = 'move';
					//start dragging
					dragObj = {mode: foundpt[0], num: foundpt[1], subnum: foundpt[2]};
					oldpointpos = tplines[curTarget][foundpt[1]][foundpt[2]];
					//curTPcurve = foundpt[1];
				} else if (foundpt[0]>=10 && foundpt[0]<11) { //if point is on ineqline
					setCursor('move');
					//targets[curTarget].el.style.cursor = 'move';
					//start dragging
					dragObj = {mode: foundpt[0], num: foundpt[1], subnum: foundpt[2]};
					oldpointpos = ineqlines[curTarget][foundpt[1]][foundpt[2]];
					//curIneqcurve = foundpt[1];
				}
				clickmightbenewcurve = true;
			}
			drawTarget();
		} else {  //clicked outside currect target region
			if (curLine!=null) {
				if (lines[curTarget][curLine].length<2) {
					lines[curTarget].splice(curLine,1);
				}
				setCursor('pen');
				//targets[curTarget].el.style.cursor = 'url('+imasroot+'/img/pen.cur), auto';
			}
			if (curTPcurve != null) {
				if (tplines[curTarget][curTPcurve].length<2) {
					tplines[curTarget].splice(curTPcurve,1);
				}
				setCursor('pen');
				//targets[curTarget].el.style.cursor = 'url('+imasroot+'/img/pen.cur), auto';
			}
			if (curIneqcurve != null) {
				if (ineqlines[curTarget][curIneqcurve].length<3) {
					ineqlines[curTarget].splice(curIneqcurve,1);
				}
				setCursor('pen');
				//targets[curTarget].el.style.cursor = 'url('+imasroot+'/img/pen.cur), auto';
			}
			curLine = null;
			curTPcurve = null;
			curIneqcurve = null;
			dragObj = null;
			drawTarget();
			curTarget = null;

		}
		//ev.preventDefault();
	}

}

function findnearpoint(thetarget,mouseOff) {
	if (hasTouch) {
		var chkdist = Math.max(15*window.innerWidth/screen.width,15);
		chkdist *= chkdist;
	} else {
		var chkdist = 25;
	}
	if (drawstyle[thetarget]==0) {
		if (targets[thetarget].mode==0 || targets[thetarget].mode==0.5) { //if in line mode
			for (var i=0;i<lines[thetarget].length;i++) { //check lines
				for (var j=lines[thetarget][i].length-1; j>=0;j--) {
					var dist = Math.pow(lines[thetarget][i][j][0]-mouseOff.x,2) + Math.pow(lines[thetarget][i][j][1]-mouseOff.y,2);
					if (dist<chkdist) {
						return [targets[thetarget].mode,i,j];
					}
				}
			}
		} else if (targets[thetarget].mode==1) {
			for (var i=0; i<dots[thetarget].length;i++) { //check dots
				if (Math.pow(dots[thetarget][i][0]-mouseOff.x,2) + Math.pow(dots[thetarget][i][1]-mouseOff.y,2)<chkdist) {
					return [1,i];
				}
			}
		} else if (targets[thetarget].mode==2) {
			for (var i=0; i<odots[thetarget].length;i++) { //check opendots
				if (Math.pow(odots[thetarget][i][0]-mouseOff.x,2) + Math.pow(odots[thetarget][i][1]-mouseOff.y,2)<chkdist) {
					return [2,i];
				}
			}
		} else if (targets[thetarget].mode>=5 && targets[thetarget].mode<10) { //if in twopoint mode
			for (var i=0;i<tplines[thetarget].length;i++) { //check lines
				if (tptypes[thetarget][i]!=targets[thetarget].mode) {continue;}
				for (var j=tplines[thetarget][i].length-1; j>=0;j--) {
					var dist = Math.pow(tplines[thetarget][i][j][0]-mouseOff.x,2) + Math.pow(tplines[thetarget][i][j][1]-mouseOff.y,2);
					if (dist<chkdist) {
						return [tptypes[thetarget][i],i,j];
					}
				}
			}
		} else if (targets[thetarget].mode>=10 && targets[thetarget].mode<11) { //if in ineqline mode
			for (var i=0;i<ineqlines[thetarget].length;i++) { //check lines
				for (var j=ineqlines[thetarget][i].length-1; j>=0;j--) {
					var dist = Math.pow(ineqlines[thetarget][i][j][0]-mouseOff.x,2) + Math.pow(ineqlines[thetarget][i][j][1]-mouseOff.y,2);
					if (dist<chkdist) {
						return [ineqtypes[thetarget][i],i,j];
					}
				}
			}
		}
	} else {
		if (targets[thetarget].mode==1) {
			for (var i=0; i<dots[thetarget].length;i++) { //check dots
				if (Math.pow(dots[thetarget][i][0]-mouseOff.x,2) + Math.pow(dots[thetarget][i][1]-mouseOff.y,2)<chkdist) {
					return [1,i];
				}
			}
		} else if (targets[thetarget].mode==2) {
			for (var i=0; i<odots[thetarget].length;i++) { //check opendots
				if (Math.pow(odots[thetarget][i][0]-mouseOff.x,2) + Math.pow(odots[thetarget][i][1]-mouseOff.y,2)<chkdist) {
					return [2,i];
				}
			}
		} else if (targets[thetarget].mode>=5 && targets[thetarget].mode<10) { //if in tpline mode
			for (var i=0;i<tplines[thetarget].length;i++) { //check lines
				for (var j=tplines[thetarget][i].length-1; j>=0;j--) {
					if (tptypes[thetarget][i]!=targets[thetarget].mode) {continue;}

					var dist = Math.pow(tplines[thetarget][i][j][0]-mouseOff.x,2) + Math.pow(tplines[thetarget][i][j][1]-mouseOff.y,2);
					if (dist<chkdist) {
						return [tptypes[thetarget][i],i,j];
					}
				}
			}
		} else if (targets[thetarget].mode>=10 && targets[thetarget].mode<11) { //if in ineqline mode
			for (var i=0;i<ineqlines[thetarget].length;i++) { //check inqs
				for (var j=ineqlines[thetarget][i].length-1; j>=0;j--) {
					var dist = Math.pow(ineqlines[thetarget][i][j][0]-mouseOff.x,2) + Math.pow(ineqlines[thetarget][i][j][1]-mouseOff.y,2);
					if (dist<chkdist) {
						return [ineqtypes[thetarget][i],i,j];
					}
				}
			}
		}
	}
	return null;
}

var lastdrawmouseup = null;
function drawMouseUp(ev) {
	//$(".tips").html("mouseup" + curTarget + dragObj);
	var mousePos = mouseCoords(ev);
	mouseisdown = false;
	if (curTarget!=null) {
		var tarelpos = getPosition(targets[curTarget].el);
		var mouseOff = {x:(mousePos.x - tarelpos.x), y: (mousePos.y-tarelpos.y)};
		if (targets[curTarget].snaptogridx > 0) {mouseOff = snaptogrid(mouseOff,curTarget);}
		var releaseInTarget = (mouseOff.x>-1 && mouseOff.x<targets[curTarget].width && mouseOff.y>-1 && mouseOff.y<targets[curTarget].height);

		if (clickmightbenewcurve==true) {
			if (targets[curTarget].mode>=5 && targets[curTarget].mode<10) {
				tplines[curTarget].push([[mouseOff.x,mouseOff.y]]);
				curTPcurve = tplines[curTarget].length-1;
				tptypes[curTarget][curTPcurve] = targets[curTarget].mode;
			} else if (targets[curTarget].mode>=10 && targets[curTarget].mode<11) {//in ineqline mode
				ineqlines[curTarget].push([[mouseOff.x,mouseOff.y]]);
				curIneqcurve = ineqlines[curTarget].length-1;
				ineqtypes[curTarget][curIneqcurve] = targets[curTarget].mode;
			}
		}
		if (lastdrawmouseup!=null && mousePos.x==lastdrawmouseup.x && mousePos.y==lastdrawmouseup.y) {
			//basically a double-click which IE can handle
			if (curLine!=null && dragObj==null) {
				if (lines[curTarget][curLine].length<2) {
					lines[curTarget].splice(curLine,1);
				}
				curLine = null;
				dragObj = null;
				drawTarget();
			}
		}
		if (curLine != null && dragObj==null) {
			if (targets[curTarget].mode==0.5 && lines[curTarget][curLine].length>1) {
				curLine = null;
				dragobj = null;
				drawTarget();
			} else if (targets[curTarget].mode==0.7) {
				curLine = null;
				dragobj = null;
				drawTarget();
			}
		}
		if (curTPcurve!=null && tplines[curTarget][curTPcurve].length==1) {
			if (didMultiTouch || !releaseInTarget) {
				tplines[curTarget].splice(curTPcurve,1);
				curTPcurve = null;
				drawTarget();
			} else if (findnearpoint(curTarget,mouseOff)==null) {
				tplines[curTarget][curTPcurve].push([mouseOff.x,mouseOff.y]);
				curTPcurve = null;
				drawTarget();
			}
		}
		if (curIneqcurve!=null && ineqlines[curTarget][curIneqcurve].length==1) {
			if (didMultiTouch || !releaseInTarget) {
				ineqlines[curTarget].splice(curIneqcurve,1);
				curIneqcurve = null;
				drawTarget();
			} else if (findnearpoint(curTarget,mouseOff)==null) {
				ineqlines[curTarget][curIneqcurve].push([mouseOff.x,mouseOff.y]);
				drawTarget();
			}
		}
		if (curLine==null && curTPcurve == null) {
			setCursor('move');
			//targets[curTarget].el.style.cursor = 'move';
		} else {
			setCursor('pen');
			//targets[curTarget].el.style.cursor = 'url('+imasroot+'/img/pen.cur), auto';
		}
		if (typeof ev != 'undefined') {
			ev.preventDefault();
		}
	}
	lastdrawmouseup = mousePos;
	if (curTarget!=null && dragObj!=null) { //is a target currectly in action, and dragging
		var tarelpos = getPosition(targets[curTarget].el);

		//are we inside target region?
		if (mouseOff.x>-1 && mouseOff.x<targets[curTarget].width && mouseOff.y>-1 && mouseOff.y<targets[curTarget].height) {
			if (dragObj.mode==0 && targets[curTarget].dotline>1) {
				if (dragObj.subnum==0 || dragObj.subnum==lines[curTarget][dragObj.num].length-1) {
					//first or last dot being moved
					if (Math.pow((lines[curTarget][dragObj.num][0][0] - lines[curTarget][dragObj.num][lines[curTarget][dragObj.num].length-1][0]),2)+Math.pow((lines[curTarget][dragObj.num][0][1] - lines[curTarget][dragObj.num][lines[curTarget][dragObj.num].length-1][1]),2)<25) {
						if (dragObj.subnum==0) {
							lines[curTarget][dragObj.num][lines[curTarget][dragObj.num].length-1][0] = lines[curTarget][dragObj.num][0][0];
							lines[curTarget][dragObj.num][lines[curTarget][dragObj.num].length-1][1] = lines[curTarget][dragObj.num][0][1];
						} else {
							lines[curTarget][dragObj.num][0][0] = lines[curTarget][dragObj.num][lines[curTarget][dragObj.num].length-1][0];
							lines[curTarget][dragObj.num][0][1] = lines[curTarget][dragObj.num][lines[curTarget][dragObj.num].length-1][1];
						}
						curLine = null;
						drawTarget();
					}
				}
			}

			dragObj = null;

		} else {
			if (drawlocky[curTarget]==1) {
				mouseOff.y = targets[curTarget].imgheight/2;
			}
			if (dragObj.mode==1) { //if dot, delete dot
				dots[curTarget].splice(dragObj.num,1);
			} else if (dragObj.mode==2) { //if open dot, delete dot
				odots[curTarget].splice(dragObj.num,1);
			} else if (dragObj.mode==0.5) {
				lines[curTarget].splice(dragObj.num,1);
			} else if (dragObj.mode==0) { //if line, return pt to orig pos
				lines[curTarget][dragObj.num][dragObj.subnum] = oldpointpos;
			} else if (dragObj.mode>=5 && dragObj.mode<10) { //if twopoint, delete line
				tplines[curTarget].splice(dragObj.num,1);
				//tplines[curTarget][dragObj.num][dragObj.subnum] = oldpointpos;
				curTPcurve = null;
			} else if (dragObj.mode>=10 && dragObj.mode<11) { //if ineq, delete ineq
				ineqlines[curTarget].splice(dragObj.num,1);
				//ineqlines[curTarget][dragObj.num][dragObj.subnum] = oldpointpos;
				curIneqcurve = null;
			}
			dragObj = null;
			drawTarget();
		}
	}
	didMultiTouch = false;
	if (hasTouch) {
		hasTouchTimer = window.setTimeout(function () {
			hasTouch = false;
			clearAllDrawListners();
			$(document).on("mousemove.imathasdraw", drawMouseMove);
			$(".drawcanvas").on("touchstart.imathasdraw", function(ev) { hasTouch=true; drawMouseDown(ev);});
			$(document).on("mousedown.imathasdraw", drawMouseDown);
		}, 350);
	} else {
		clearAllDrawListners();
		$(document).on("mousemove.imathasdraw", drawMouseMove);
		$(".drawcanvas").on("touchstart.imathasdraw", function(ev) { hasTouch=true; drawMouseDown(ev);});
		$(document).on("mousedown.imathasdraw", drawMouseDown);
	}
}

function drawMouseMove(ev) {
	var tempTarget = null;
	clickmightbenewcurve = false;
	var mousePos = mouseCoords(ev);
	//$(".tips").html("move"+didMultiTouch);
	//document.getElementById("ans0-0").innerHTML = dragObj + ';' + curTPcurve;
	//if (curTarget==null) {
		for (i in targets) {
			var tarelpos = getPosition(targets[i].el);
			if (tarelpos.x<mousePos.x && (tarelpos.x+targets[i].width>mousePos.x) && tarelpos.y<mousePos.y && (tarelpos.y+targets[i].height>mousePos.y)) {
				tempTarget = i;
				break;
			}
		}
	//}
	if (tempTarget!=null) {
		var tarelpos = getPosition(targets[tempTarget].el);
		var mouseOff = {x:(mousePos.x - tarelpos.x), y: (mousePos.y-tarelpos.y)};
		if (targets[tempTarget].snaptogridx > 0) {mouseOff = snaptogrid(mouseOff,tempTarget);}
		if (drawlocky[tempTarget]==1) {
			mouseOff.y = targets[tempTarget].imgheight/2;
		}
		if (mouseOff.x>-1 && mouseOff.x<targets[tempTarget].width && mouseOff.y>-1 && mouseOff.y<targets[tempTarget].height) {
			if (dragObj==null) {
				if (curLine==null) {
					var foundpt = findnearpoint(tempTarget,mouseOff);
					if (foundpt==null) {
						setCursor('pen', tempTarget);
						//targets[tempTarget].el.style.cursor = 'url('+imasroot+'/img/pen.cur), auto';
					} else {
						setCursor('move', tempTarget);
						//targets[tempTarget].el.style.cursor = 'move';
					}
				}
			}
		}
	}
	if (curTarget!=null) {
		if (ev.originalEvent.touches && ev.originalEvent.touches.length>1) {
			didMultiTouch = true;
			//$(".tips").html("multi mousemove");
			return true;  //bypass when multitouching to prevent interference with pinch zoom
		} else if (typeof ev != 'undefined') {
			ev.preventDefault();
		}
		var tarelpos = getPosition(targets[curTarget].el);
		var mouseOff = {x:(mousePos.x - tarelpos.x), y: (mousePos.y-tarelpos.y)};
		if (targets[curTarget].snaptogridx > 0) {mouseOff = snaptogrid(mouseOff,curTarget);}
		if (drawlocky[curTarget]==1) {
			mouseOff.y = targets[curTarget].imgheight/2;
		}
		//are we inside target region?
		if (mouseOff.x>-1 && mouseOff.x<targets[curTarget].width && mouseOff.y>-1 && mouseOff.y<targets[curTarget].height) {
			if (dragObj==null) { //notdragging
				if (curLine!=null) {
					if (mouseisdown && (targets[curTarget].mode==0 || targets[curTarget].mode==0.7)) {
						var last = lines[curTarget][curLine].length-1;
						var dist = Math.pow(lines[curTarget][curLine][last][0]-mouseOff.x,2) + Math.pow(lines[curTarget][curLine][last][1]-mouseOff.y,2);
						//add point to line
						if (dist>25) {
							lines[curTarget][curLine].push([mouseOff.x,mouseOff.y]);
							drawTarget();
						} else {
							drawTarget(mouseOff.x,mouseOff.y);
						}
					} else if (mouseisdown && targets[curTarget].mode==0.5) {
						var last = lines[curTarget][curLine].length-1;
						if (last==0) {
							var dist = Math.pow(lines[curTarget][curLine][last][0]-mouseOff.x,2) + Math.pow(lines[curTarget][curLine][last][1]-mouseOff.y,2);
							if (dist>25) {
								lines[curTarget][curLine].push([mouseOff.x,mouseOff.y]);
								drawTarget();
							} else {
								drawTarget(mouseOff.x,mouseOff.y);
							}
						} else {
							lines[curTarget][curLine][last] = [mouseOff.x,mouseOff.y];
							drawTarget();
						}

					} else {
						//draw temp line
						drawTarget(mouseOff.x,mouseOff.y);
					}
				} else if (curTPcurve!=null) {
					if (mouseisdown) {
						drawTarget();
					} else {
						drawTarget(mouseOff.x,mouseOff.y);
					}

				} else if (curIneqcurve!=null) {
					if (mouseisdown) {
						drawTarget();
					} else {
						drawTarget(mouseOff.x,mouseOff.y);
					}

				} else { //see if we're near a point
					var foundpt = findnearpoint(curTarget,mouseOff);
					if (foundpt==null) {
						setCursor('pen');
						//targets[curTarget].el.style.cursor = 'url('+imasroot+'/img/pen.cur), auto';
					} else {
						setCursor('move');
						//targets[curTarget].el.style.cursor = 'move';
					}
				}
			} else { //dragging
				setCursor('move');
				//targets[curTarget].el.style.cursor = 'move';
				if (dragObj.mode==0 || dragObj.mode==0.5) {
					lines[curTarget][dragObj.num][dragObj.subnum] = [mouseOff.x,mouseOff.y];
				} else if (dragObj.mode==1) {
					dots[curTarget][dragObj.num] = [mouseOff.x,mouseOff.y];
				} else if (dragObj.mode==2) {
					odots[curTarget][dragObj.num] = [mouseOff.x,mouseOff.y];
				} else if (dragObj.mode>=5 && dragObj.mode<10) {
					tplines[curTarget][dragObj.num][dragObj.subnum] = [mouseOff.x,mouseOff.y];
				} else if (dragObj.mode>=10 && dragObj.mode<11) {
					ineqlines[curTarget][dragObj.num][dragObj.subnum] = [mouseOff.x,mouseOff.y];
				}
				drawTarget();
			}
			return false;
		}
	}

}
function setCursor(cursor, target) {
	target = target || curTarget;
	
	if (targets[target].cursor != cursor) {
		if (cursor=='move') {
			targets[target].el.style.cursor = cursor;
		} else {
			targets[target].el.style.cursor = 'url('+imasroot+'/img/'+cursor+'.cur), auto';
		}
		targets[target].cursor = cursor;
	}
}
function mouseCoords(ev){

	ev = ev || window.event;

	if (hasTouch) {
		var touch = ev.originalEvent.changedTouches[0] || ev.originalEvent.touches[0];
		return {x:touch.pageX, y:touch.pageY};
	}

	if(ev.pageX || ev.pageY){
		return {x:ev.pageX, y:ev.pageY};
	}

	var dd = document.documentElement, db = document.body;
	if (dd && (dd.scrollTop || dd.scrollLeft)) {
		var SL = dd.scrollLeft;
		var ST = dd.scrollTop;
	} else if (db) {
		var SL = db.scrollLeft;
		var ST = db.scrollTop;
	} else {
		var SL = 0;
		var ST = 0;
	}

	return {
		x:ev.clientX + SL,
		y:ev.clientY + ST
	};

}

function snaptogrid(mousepos, curt) {
	var posx = mousepos.x - targets[curt].imgborder + targets[curt].xmin*targets[curt].pixperx;
	var posy = mousepos.y - targets[curt].imgborder - targets[curt].ymax*targets[curt].pixpery;
	posx = Math.round(Math.round(posx/(targets[curt].pixperx*targets[curt].snaptogridx))*targets[curt].pixperx*targets[curt].snaptogridx + targets[curt].imgborder  - targets[curt].xmin*targets[curt].pixperx);
	posy = Math.round(Math.round(posy/(targets[curt].pixpery*targets[curt].snaptogridy))*targets[curt].pixpery*targets[curt].snaptogridy + targets[curt].imgborder  + targets[curt].ymax*targets[curt].pixpery);
	return {x: posx, y: posy};
}

function getMouseOffset(target, ev){

	var docPos    = getPosition(target);
	var mousePos  = mouseCoords(ev);
	return {x:mousePos.x - docPos.x, y:mousePos.y - docPos.y};
}

function getPosition(e){
	var left = 0;
	var top  = 0;

	//seems to be causing issues
	/*if (e.getBoundingClientRect) {
		var box = e.getBoundingClientRect();
		var scrollTop = Math.max(document.documentElement.scrollTop, document.body.scrollTop);
                var scrollLeft = Math.max(document.documentElement.scrollLeft, document.body.scrollLeft);
                return {x: box.left + scrollLeft, y: box.top + scrollTop};
	}*/
	if (e.className.match(/hidden/)) {
		return {x:-10000, y:-10000};
	}
	while (e.offsetParent){
		left += e.offsetLeft;
		top  += e.offsetTop;
		e     = e.offsetParent;
		if (e.className.match(/hidden/)) {
			return {x:-10000, y:-10000};
		}
	}

	left += e.offsetLeft;
	top  += e.offsetTop;
	return {x:left, y:top};

}


function clearAllDrawListners() {
	$(document).off("mousedown.imathasdraw").off("mousemove.imathasdraw").off("mouseup.imathasdraw");
	$(document).off("touchstart.imathasdraw").off("touchmove.imathasdraw").off("touchend.imathasdraw").off("touchcancel.imathasdraw");
	$(".drawcanvas").off("touchstart.imathasdraw").off("touchmove.imathasdraw").off("touchend.imathasdraw").off("touchcancel.imathasdraw");
}
function initCanvases(k) {
	clearAllDrawListners();
	$(".drawcanvas").on("mousemove.imathasdraw", drawMouseMove);
	$(".drawcanvas").on("touchstart.imathasdraw", function(ev) { hasTouch=true; drawMouseDown(ev);});
	$(document).on("mousedown.imathasdraw", drawMouseDown);
	
	try {

		CanvasRenderingContext2D.prototype.dashedLine = function(x1, y1, x2, y2, dashLen) {
		    if (dashLen == undefined) dashLen = 10;

		    this.beginPath();
		    this.moveTo(x1, y1);

		    var dX = x2 - x1;
		    var dY = y2 - y1;
		    var dashes = Math.sqrt(dX * dX + dY * dY) / dashLen;
		    var dashX = dX / dashes;
		    var dashY = dY / dashes;
		    dashes = Math.floor(dashes);

		    var q = 0;
		    while (q++ < dashes && y1>-1 && y1<targets[curTarget].imgheight+1 && x1>-1 && x1<targets[curTarget].imgwidth+1) {
		     x1 += dashX;
		     y1 += dashY;
		     this[q % 2 == 0 ? 'moveTo' : 'lineTo'](x1, y1);
		    }
		    this[q % 2 == 0 ? 'moveTo' : 'lineTo'](x2, y2);

		    this.stroke();
		    this.closePath();
		};
	} catch(e) { }
	for (var i in canvases) {
		if (typeof(k)=='undefined' || k==i) {
			var thisdrawla = null;
			if (drawla[i]!=null && drawla[i].length>2) {
				lines[canvases[i][0]] = drawla[i][0];
				dots[canvases[i][0]] = drawla[i][1];
				odots[canvases[i][0]] = drawla[i][2];
				tptypes[canvases[i][0]] = [];
				tplines[canvases[i][0]] = [];
				if (drawla[i].length>3 && drawla[i][3].length>0) {
					for (var j=0; j<drawla[i][3].length;j++) {
						tptypes[canvases[i][0]][j] = drawla[i][3][j][0];
						tplines[canvases[i][0]][j] = [drawla[i][3][j].slice(1,3),drawla[i][3][j].slice(3)];
					}
				}
				ineqtypes[canvases[i][0]] = [];
				ineqlines[canvases[i][0]] = [];
				if (drawla[i].length>4 && drawla[i][4].length>0) {
					for (var j=0; j<drawla[i][4].length;j++) {
						//CHECK
						ineqtypes[canvases[i][0]][j] = drawla[i][4][j][0];
						ineqlines[canvases[i][0]][j] = [drawla[i][4][j].slice(1,3),drawla[i][4][j].slice(3,5),drawla[i][4][j].slice(5)];
					}
				}
				if (drawla[i].length>5) {
					thisdrawla = drawla[i][5];
				}
			}
			if (canvases[i][1].substr(0,8)=="a11ydraw") {
				addA11yTarget(canvases[i], thisdrawla);
			} else {
				addTarget(canvases[i][0],'canvas'+canvases[i][0],imasroot+'/filter/graph/imgs/'+canvases[i][1],'qn'+canvases[i][0],canvases[i][2],canvases[i][3],canvases[i][4],canvases[i][5],canvases[i][6],canvases[i][7],canvases[i][8],canvases[i][9],canvases[i][10],canvases[i][11],canvases[i][12]);
			}
		}
	}
}

if (typeof(initstack)!='undefined') {
	initstack.push(initCanvases);
} else {
	$(function() {
		initCanvases();
	});
}


/*
normal distribution slider code

For each normal grapher on the page, include this html, and push the id (number on the end of each id) to normslider.idnums
<div class="normgrapher">
<p>Shade: <select id="shaderegions0" onchange="chgnormtype(this.id.substring(12));"><option value="1L">Left of a value</option><option value="1R">Right of a value</option>
<option value="2B">Between two values</option><option value="2O">2 regions</option></select>. Click and drag and arrows to adjust the values.


<div style="position: relative; width: 500px; height:220px;padding:0px;background:#fff;">
<div style="position: absolute; left:0; top:0; height:200px; width:0px; background:#00f; z-index:-1" id="normleft0">&nbsp;</div>
<div style="position: absolute; right:0; top:0; height:200px; width:0px; background:#00f; z-index:-1" id="normright0">&nbsp;</div>
<img style="position: absolute; left:0; top:0;" src="img/normalcurve.gif"/>
<img style="position: absolute; top:142px;left:0px;cursor:pointer;" id="slid10" src="img/uppointer.gif"/>
<img style="position: absolute; top:142px;left:0px;cursor:pointer;" id="slid20" src="img/uppointer.gif"/>
<div style="position: absolute; top:170px;left:0px;" id="slid1txt0">-2.5</div>
<div style="position: absolute; top:170px;left:0px;" id="slid2txt0">1.6</div>
</div>

reads/writes values from "qn"+id

*/
var normslider = {idnums:[], curslider:{el: null, startpos: [0,0], outnode: null}};
function addnormslider(k) {
	if (arraysearch(k,normslider.idnums)==-1) { //not in there yet.  First load
		normslider.idnums.push(k);
	} else {
		//resubmit.  Must be on embedded reload.  call pageload
		slideronpageload(k);
	}
}
function slideronpageload(k) {
	var el,id;
	for(var i=0; i<normslider.idnums.length; i++) {
		if (typeof(k)=='undefined' || k==normslider.idnums[i]) {
			id = normslider.idnums[i];
			initnormslider(id);
			$("#slid1"+id).on("touchstart.normslider mousedown.normslider", onsliderstart);
			$("#slid2"+id).on("touchstart.normslider mousedown.normslider", onsliderstart);
		}
	}
}
if (typeof(initstack)!='undefined') {
	initstack.push(slideronpageload);
}
function initnormslider(id) {
	var type, p1, p2, v;
	var str = document.getElementById("qn"+id).value;
	if (v = str.match(/\(-oo,([\-\d\.]+)\)U\(([\-\d\.]+),oo\)/)) {
		type = 3;
		p1 = v[1];
		p2 = v[2];
	} else if (v = str.match(/\(-oo,([\-\d\.]+)\)/)) {
		type = 0;
		p1 = v[1];
		p2 = 2.5;
	} else if (v = str.match(/\(([\-\d\.]+),oo\)/)) {
		type = 1;
		p1 = v[1];
		p2 = 2.5;
	} else if (v = str.match(/\(([\-\d\.]+),([\-\d\.]+)\)/)) {
		type = 2;
		p1 = v[1];
		p2 = v[2];
	} else {
		type = 0;
		p1 = -1.5;
		p2 = 2.5;
	}
	document.getElementById("slid1"+id).style.left = (p1*60+250-6)+"px";
	document.getElementById("slid2"+id).style.left = (p2*60+250-6)+"px";
	document.getElementById("shaderegions"+id).selectedIndex = type;
	//alert(document.getElementById("shaderegions").selectedIndex +","+ type + ","+p1+","+p2);
	normslider.curslider.type = document.getElementById("shaderegions"+id).value;
	chgnormtype(id);

}
function onsliderstart(ev) {
	ev = ev || window.event;
	if (ev.originalEvent.touches && ev.originalEvent.touches.length>0) {
		hasTouch = true;
		$(document).on("touchmove.normslider", onsliderchange);
		$(document).on("touchend.normslider", onsliderstop);
	} else {
		$(document).on("mousemove.normslider", onsliderchange);
		$(document).on("mouseup.normslider", onsliderstop);
		//normslider.curslider.el.parentNode.onmousemove = onsliderchange;
	}
	normslider.curslider.el = ev.target || ev.srcElement;
	normslider.curslider.id = normslider.curslider.el.id.substring(5);
	normslider.curslider.type = document.getElementById("shaderegions"+normslider.curslider.id).value;
	normslider.curslider.outnode = document.getElementById(normslider.outputid);
	normslider.curslider.startpos = getMouseOffset(normslider.curslider.el,ev);
	normslider.curslider.el.parentNode.style.cursor = 'pointer';
	var parentpos = getPosition(normslider.curslider.el.parentNode);
	if (ev.preventDefault) {ev.preventDefault()};
	return false;
}
function onsliderchange(ev) {
	var id = normslider.curslider.id;
	ev = ev || window.event;
	var curpos = getMouseOffset(normslider.curslider.el.parentNode,ev);
	if (curpos.x<5 || curpos.x>normslider.curslider.el.parentNode.offsetWidth-5 || curpos.y<5 || curpos.y>normslider.curslider.el.parentNode.offsetHeight-5) {
		return;
	}
	var posx =  Math.round((curpos.x-normslider.curslider.startpos.x - 10)/6)*6+10;
	normslider.curslider.el.style.left = posx + "px";

	normupdatevalues(id);

	if (ev.preventDefault) {ev.preventDefault()};
	return false;
}
function onsliderstop(ev) {
	if (normslider.curslider.el !== null) {
		hasTouch = false;
		$(document).off("touchmove.normslider touchend.normslider");
		$(document).off("mousemove.normslider mouseup.normslider");
		normslider.curslider.el.parentNode.style.cursor = '';
		normslider.curslider.el = null;
	}
}
function normupdatevalues(id) {

	var p1 = parseInt(document.getElementById("slid1"+id).style.left)+6;
	var p2 = parseInt(document.getElementById("slid2"+id).style.left)+6;
	var minp = Math.min(p1,p2);
	var maxp = Math.max(p1,p2);
	if (normslider.curslider.type=='2O') {
		document.getElementById("normleft"+id).style.width = (minp+1) + "px";
	} else if (normslider.curslider.type=='1L') {
		document.getElementById("normleft"+id).style.width = (p1+1) + "px";
	} else if (normslider.curslider.type=='2B') {
		document.getElementById("normleft"+id).style.left = (minp) + "px";
		document.getElementById("normleft"+id).style.width = (maxp-minp+1) + "px";
	}
	if (normslider.curslider.type=='2O') {
		document.getElementById("normright"+id).style.width = (500-maxp) + "px";
	} else if (normslider.curslider.type=='1R') {
		document.getElementById("normright"+id).style.width = (500-p1) + "px";
	}
	var lbl1val = (-4+(p1 - 10)/60).toFixed(1);
	var lbl2val =(-4+(p2 - 10)/60).toFixed(1);
	var lbl1 = document.getElementById("slid1txt"+id);
	lbl1.innerHTML = lbl1val;
	lbl1.style.left = (p1-6) + "px";
	var lbl2 = document.getElementById("slid2txt"+id);
	lbl2.innerHTML = lbl2val;
	lbl2.style.left = (p2-6) + "px";
	var minlbl = Math.min(lbl1val,lbl2val);
	var maxlbl = Math.max(lbl1val,lbl2val);
	if (normslider.curslider.type=='2O') {
		var outstr = "(-oo,"+minlbl+")U("+maxlbl+",oo)";
	} else if (normslider.curslider.type=='2B') {
		var outstr = "("+minlbl+","+maxlbl+")";
	} else if (normslider.curslider.type=='1L') {
		var outstr = "(-oo,"+lbl1val+")";
	} else if (normslider.curslider.type=='1R') {
		var outstr = "("+lbl1val+",oo)";
	}
	document.getElementById("qn"+id).value = outstr;
}

function chgnormtype(id) {
	var type = document.getElementById("shaderegions"+id).value;
	normslider.curslider.type = type;
	var p1 = parseInt(document.getElementById("slid1"+id).style.left)+6;
	var p2 = parseInt(document.getElementById("slid2"+id).style.left)+6;
	var minp = Math.min(p1,p2);
	var maxp = Math.max(p1,p2);
	if (type == "1R" || type == "1L") {
		document.getElementById("slid2"+id).style.display = "none";
		document.getElementById("slid2txt"+id).style.display = "none";
		if (type=="1R") {
			document.getElementById("normleft"+id).style.display = "none";
			document.getElementById("normright"+id).style.display = "";
			document.getElementById("normright"+id).style.right = 0+"px";
			document.getElementById("normright"+id).style.width = (500-p1)+"px";
		} else {
			document.getElementById("normright"+id).style.display = "none";
			document.getElementById("normleft"+id).style.display = "";
			document.getElementById("normleft"+id).style.left = 0+"px";
			document.getElementById("normleft"+id).style.width = p1+"px";
		}
	} else if (type=='2O') {
		document.getElementById("slid2"+id).style.display = "";
		document.getElementById("slid2txt"+id).style.display = "";
		document.getElementById("normleft"+id).style.display = "";
		document.getElementById("normright"+id).style.display = "";
		document.getElementById("normright"+id).style.right = 0+"px";
		document.getElementById("normleft"+id).style.left = 0+"px";
		document.getElementById("normright"+id).style.width = (500-maxp)+"px";
		document.getElementById("normleft"+id).style.width = minp+"px";
	} else if (type=='2B') {
		document.getElementById("slid2"+id).style.display = "";
		document.getElementById("slid2txt"+id).style.display = "";
		document.getElementById("normleft"+id).style.display = "";
		document.getElementById("normright"+id).style.display = "none";
		document.getElementById("normleft"+id).style.left = minp+"px";
		document.getElementById("normleft"+id).style.width = (maxp-minp)+"px";
	}
	normupdatevalues(id);
}

//a parabola given two points,
//context to draw on - ctx
//the vertex - (Vx,Vy)
//another point on the parabola - (x,y)
//screen width - sw
//screen height - sh
function dashedParabola(ctx,Vx,Vy,x,y,sw,sh){

	if (y==Vy) {//horizontal line across screen
		ctx.moveTo(0,y);
		ctx.dashedLine(0,y,sw,y);
	}

	else{
		//find out where the parabola touches the edge of the screen
        	var a = (y-Vy)/((x-Vx)*(x-Vx));
		var b = -2*a*Vx;
	    	var shift = 0;
		if(y > Vy){//hits image height, not 0
			shift = sh;
		}
        	var c = a*Vx*Vx+Vy - shift;

        	//calculate control points
		var discr = Math.sqrt(b*b-4*a*c);
		c += shift;
        	var p0x = (-b - discr)/(2*a);
		var p0y = a*p0x*p0x + b*p0x + c;

		var p2x = (-b + discr)/(2*a);
		var p2y = p0y;

        	if(p2x < p0x){
            		var temp = p0x;
            		p0x = p2x;
            		p2x = temp;
        	}

        	var stroke = 8;//length of dashes
        	var strokeSqr = stroke*stroke;
		var xChange = p2x - p0x;
		var px = Vx;
        	var py = Vy;
        	var counter = 0;
		while(px > p0x-5 && px>-5) {

			if(counter%2 == 0)
                		ctx.moveTo(px,py);
            		else
                		ctx.lineTo(px,py);

            		//px -= Math.sqrt(strokeSqr/(1+Math.pow(2*a*(px-Vx),2)));
            		px -= Math.min(Math.sqrt(strokeSqr/(1+Math.pow(2*a*(px-Vx),2))), Math.sqrt(stroke/Math.abs(a)));
            		py = a*px*px+b*px+c;
            		counter++;
		}

        	px = Vx;
        	py = Vy;
        	counter = 0;
        	while(px < p2x+5 && px < sw+5){
           		if(counter%2 == 0)
                		ctx.moveTo(px,py);
            		else
                		ctx.lineTo(px,py);

            		//px += Math.sqrt(strokeSqr/(1+Math.pow(2*a*(px-Vx),2)));
            		px += Math.min(Math.sqrt(strokeSqr/(1+Math.pow(2*a*(px-Vx),2))), Math.sqrt(stroke/Math.abs(a)));
            		py = a*px*px+b*px+c;
            		counter++;
        	}

	}
	ctx.stroke();
}

//a parabola given two points,
//context to draw on - ctx
//the vertex - (Vx,Vy)
//another point on the parabola - (x,y)
//point to determine which side to shade (shX,shY)
//screen width - sw
//screen height - sh
function shadeParabola(ctx,Vx,Vy,x,y,shX,shY,sw,sh){
	ctx.beginPath();
	if (y==Vy) {//horizontal line across screen
		ctx.moveTo(0,y);
		ctx.lineTo(sw,y);
		if(shY < Vy){//top half
			ctx.lineTo(sw,0);
			ctx.lineTo(0,0);
		}
		else{
			ctx.lineTo(sw,sh);
			ctx.lineTo(0,sh);
		}
	}
	else {
		//find out where the parabola touches the edge of the screen
        	var a = (y-Vy)/((x-Vx)*(x-Vx));
		var b = -2*a*Vx;
	    	var shift = 0;
		if(y > Vy){//hits image height, not 0
			shift = sh;
		}
        	var c = a*Vx*Vx+Vy - shift;

        	//calculate control points
		var discr = Math.sqrt(b*b-4*a*c);
		c += shift;
        	var p0x = (-b - discr)/(2*a);
		var p0y = a*p0x*p0x + b*p0x + c;

		var p2x = (-b + discr)/(2*a);
		var p2y = p0y;

        	if(p2x < p0x){
            		var temp = p0x;
            		p0x = p2x;
            		p2x = temp;
        	}

		var m0 = 2*a*(p0x-Vx);
		var b0 = p0y - m0*p0x;
		var m2 = -m0;
		var b2 = p2y - m2*p2x;

		var p1x = (b0-b2)/(m2-m0);
		var p1y = m0*p1x+b0;//either line, but use p1x

		if(a<0 && shY < a*shX*shX+b*shX+c || a>0 && shY > a*shX*shX+b*shX+c){//shade inside parabola
			ctx.moveTo(p0x,p0y);
			ctx.quadraticCurveTo(p1x,p1y,p2x,p2y);
		}
		else{
			var otherY = sh;
			if(Math.abs(p0y-sh) < 2)
				otherY = 0;

			ctx.moveTo(0,p0y);
			ctx.lineTo(p0x,p0y);
			ctx.quadraticCurveTo(p1x,p1y,p2x,p2y);
			ctx.lineTo(sw,p2y);
			ctx.lineTo(sw,otherY);
			ctx.lineTo(0,otherY);
		}
	}
	ctx.closePath();
}

var drawexport = {
	initCanvases:initCanvases,
	clearcanvas:clearcanvas,
	settool:settool,
	addnormslider:addnormslider,
	chgnormtype:chgnormtype,
	adda11ydraw:adda11ydraw,
	changea11ydraw:changea11ydraw,
	encodea11ydraw:encodea11ydraw,
	removea11ydraw:removea11ydraw
};
return drawexport;
}(jQuery));

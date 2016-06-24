var mouseisdown = false;
var targets = new Array();
var imgs = new Array();
var targetOuts = new Array();
var lines = new Array();
var dots = new Array();
var odots = new Array();
var tplines = new Array();
var tptypes = new Array();
var ineqlines = new Array();
var ineqtypes = new Array();
var canvases = new Array();
var drawla = new Array();
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
var nocanvaswarning = false;
var hasTouch = false;
var didMultiTouch = false;
var clickmightbenewcurve = false;
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
	6.5: square root
	7: circle (only works on square grids)
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
		} else if (tptypes[curTarget][i]==6) {//if a tp parabola
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
		} else if (tptypes[curTarget][i]==7) {//if a tp circle
			if (tplines[curTarget][i].length==2) {
				x2 = tplines[curTarget][i][1][0];
				y2 = tplines[curTarget][i][1][1];
			} else if (curTPcurve==i && x!=null && tplines[curTarget][i].length==1) {
				x2 = x;
				y2 = y;
			}
			if (x2 != null && (x2!=tplines[curTarget][i][0][0] || y2!=tplines[curTarget][i][0][1])) {
				var rad = Math.sqrt((x2-tplines[curTarget][i][0][0])*(x2-tplines[curTarget][i][0][0]) + (y2-tplines[curTarget][i][0][1])*(y2-tplines[curTarget][i][0][1]));
				ctx.arc(tplines[curTarget][i][0][0],tplines[curTarget][i][0][1],rad,0,2*Math.PI,true);
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
	for (var i=0; i<tplines[curTarget].length; i++) {
		if (i!=0) {
			out += ',';	
		}
		if (tplines[curTarget][i].length>1) {
			out += '('+tptypes[curTarget][i]+','+tplines[curTarget][i][0][0]+','+tplines[curTarget][i][0][1]+','+tplines[curTarget][i][1][0]+','+tplines[curTarget][i][1][1]+')';
		}
	}
	out += ';;';
	for (var i=0; i<ineqlines[curTarget].length; i++) {
		if (i!=0) {
			out += ',';	
		}
		if (ineqlines[curTarget][i].length>2) {
			out += '('+ineqtypes[curTarget][i]+','+ineqlines[curTarget][i][0][0]+','+ineqlines[curTarget][i][0][1]+','+ineqlines[curTarget][i][1][0]+','+ineqlines[curTarget][i][1][1]+','+ineqlines[curTarget][i][2][0]+','+ineqlines[curTarget][i][2][1]+')';
		}
	}
	targetOuts[curTarget].value = out;
}

function drawMouseDown(ev) {
	
	if (hasTouch && ev.touches.length>1) {
		return true;  //bypass when multitouching to prevent interference with pinch zoom
	}
	var mousePos = mouseCoords(ev);
	if (curTarget==null) { //see if mouse click is inside a target; if so, select it
		for (i in targets) {
			var tarelpos = getPosition(targets[i].el);
			if (tarelpos.x<mousePos.x && (tarelpos.x+targets[i].width>mousePos.x) && tarelpos.y<mousePos.y && (tarelpos.y+targets[i].height>mousePos.y)) {
				curTarget = i;
				break;
			}
		}
	}
	
	if (curTarget!=null) { //is a target currectly in action?
		if( navigator.userAgent.match(/Android/i) ) {
			ev.preventDefault();
		}
		mouseisdown = true;
		var tarelpos = getPosition(targets[curTarget].el);
		var mouseOff = {x:(mousePos.x - tarelpos.x), y: (mousePos.y-tarelpos.y)};
		  
		//are we inside target region?
		if (mouseOff.x>-1 && mouseOff.x<targets[curTarget].width && mouseOff.y>-1 && mouseOff.y<targets[curTarget].height) {
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
				targets[curTarget].el.style.cursor = 'url('+imasroot+'/img/pendown.cur), default';
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
						if (ineqlines[curTarget][curIneqcurve].length==3) {
							//second point is set.  switch to drag and end line
							dragObj = {mode: targets[curTarget].mode, num: curIneqcurve, subnum: 1};
							curIneqcurve = null;
						}		
					}
				}
			} else { //clicked on current point
				if (foundpt[0]==0 || foundpt[0]==0.5) { //if point is on line
					if (curLine==null) {//not current in line
						targets[curTarget].el.style.cursor = 'move';
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
								targets[curTarget].el.style.cursor = 'url('+imasroot+'/img/pendown.cur), default';
								lines[curTarget][curLine].push([mouseOff.x,mouseOff.y]);
							//}
						}
					}
				} else if (foundpt[0]==1) { //if point is a dot
					targets[curTarget].el.style.cursor = 'move';
					dragObj = {mode: 1, num: foundpt[1]};
				} else if (foundpt[0]==2) { //if point is a open dot
					targets[curTarget].el.style.cursor = 'move';
					dragObj = {mode: 2, num: foundpt[1]};
				} else if (foundpt[0]>=5 && foundpt[0]<10) { //if point is on twopoint
					targets[curTarget].el.style.cursor = 'move';
					//start dragging
					dragObj = {mode: foundpt[0], num: foundpt[1], subnum: foundpt[2]};
					oldpointpos = tplines[curTarget][foundpt[1]][foundpt[2]];
					//curTPcurve = foundpt[1];
				} else if (foundpt[0]>=10 && foundpt[0]<11) { //if point is on ineqline
					targets[curTarget].el.style.cursor = 'move';
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
				targets[curTarget].el.style.cursor = 'url('+imasroot+'/img/pen.cur), default';
			}
			if (curTPcurve != null) {
				if (tplines[curTarget][curTPcurve].length<2) {
					tplines[curTarget].splice(curTPcurve,1);
				}
				targets[curTarget].el.style.cursor = 'url('+imasroot+'/img/pen.cur), default';
			}
			if (curIneqcurve != null) {
				if (ineqlines[curTarget][curIneqcurve].length<3) {
					ineqlines[curTarget].splice(curIneqcurve,1);
				}
				targets[curTarget].el.style.cursor = 'url('+imasroot+'/img/pen.cur), default';
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
	var mousePos = mouseCoords(ev);
	mouseisdown = false;
	if (curTarget!=null) {
		var tarelpos = getPosition(targets[curTarget].el);
		var mouseOff = {x:(mousePos.x - tarelpos.x), y: (mousePos.y-tarelpos.y)};
		if (targets[curTarget].snaptogridx > 0) {mouseOff = snaptogrid(mouseOff,curTarget);}
		
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
			if (didMultiTouch) {
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
			if (didMultiTouch) {
				ineqlines[curTarget].splice(curIneqcurve,1);
				curIneqcurve = null;
				drawTarget();
			} else if (findnearpoint(curTarget,mouseOff)==null) {
				ineqlines[curTarget][curIneqcurve].push([mouseOff.x,mouseOff.y]);
				drawTarget();
			}
		}
		if (curLine==null && curTPcurve == null) {
			targets[curTarget].el.style.cursor = 'move';
		} else {
			targets[curTarget].el.style.cursor = 'url('+imasroot+'/img/pen.cur), default';
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
		
}

function drawMouseMove(ev) {
	var tempTarget = null;
	clickmightbenewcurve = false;
	var mousePos = mouseCoords(ev);
	
	//document.getElementById("ans0-0").innerHTML = dragObj + ';' + curTPcurve;
	if (curTarget==null) {
		for (i in targets) {
			var tarelpos = getPosition(targets[i].el);
			if (tarelpos.x<mousePos.x && (tarelpos.x+targets[i].width>mousePos.x) && tarelpos.y<mousePos.y && (tarelpos.y+targets[i].height>mousePos.y)) {
				tempTarget = i;
				break;
			}
		}
	}
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
						targets[tempTarget].el.style.cursor = 'url('+imasroot+'/img/pen.cur), default';
					} else {
						targets[tempTarget].el.style.cursor = 'move';
					}
				}
			}
		}
	}
	if (curTarget!=null) {
		if (hasTouch && ev.touches.length>1) {
			didMultiTouch = true;
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
						targets[curTarget].el.style.cursor = 'url('+imasroot+'/img/pen.cur), default';
					} else {
						targets[curTarget].el.style.cursor = 'move';
					}
				}
			} else { //dragging
				targets[curTarget].el.style.cursor = 'move';
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

function mouseCoords(ev){
	
	ev = ev || window.event;
	
	if (hasTouch) {
		var touch = ev.changedTouches[0] || ev.touches[0];
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

	if (e.getBoundingClientRect) {
		var box = e.getBoundingClientRect();
		var scrollTop = Math.max(document.documentElement.scrollTop, document.body.scrollTop);
                var scrollLeft = Math.max(document.documentElement.scrollLeft, document.body.scrollLeft);
                return {x: box.left + scrollLeft, y: box.top + scrollTop};
	}
	while (e.offsetParent){
		left += e.offsetLeft;
		top  += e.offsetTop;
		e     = e.offsetParent;
	}
	
	left += e.offsetLeft;
	top  += e.offsetTop;

	return {x:left, y:top};
}

function drawTouchCatch(ev) {
	hasTouch = true;
	drawMouseDown(ev);
	document.addEventListener('touchstart',drawMouseDown);
	document.addEventListener('touchmove',drawMouseMove);
	document.addEventListener('touchend',drawMouseUp);
	document.removeEventListener('touchstart',drawTouchCatch);
	document.onmousedown = null;
	document.onmouseup =  null;
	document.onmousemove = null;
}

function initCanvases(k) {
	if (document.addEventListener) {
		document.addEventListener('touchstart',drawTouchCatch);	
	}
	document.onmousedown =  drawMouseDown;
	document.onmouseup =  drawMouseUp;
	document.onmousemove = drawMouseMove;
	
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
		    dashes = Math.round(dashes);
		    
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
			}
			addTarget(canvases[i][0],'canvas'+canvases[i][0],imasroot+'/filter/graph/imgs/'+canvases[i][1],'qn'+canvases[i][0],canvases[i][2],canvases[i][3],canvases[i][4],canvases[i][5],canvases[i][6],canvases[i][7],canvases[i][8],canvases[i][9],canvases[i][10],canvases[i][11],canvases[i][12]);
		}
	}	
}

if (typeof(initstack)!='undefined') {
	initstack.push(initCanvases);
} else {
// GO1.1 Generic onload by Brothercake 
// http://www.brothercake.com/
//setup onload function
if(typeof window.addEventListener != 'undefined')
{
  //.. gecko, safari, konqueror and standard
  window.addEventListener('load', initCanvases, false);
}
else if(typeof document.addEventListener != 'undefined')
{
  //.. opera 7
  document.addEventListener('load', initCanvases, false);
}
else if(typeof window.attachEvent != 'undefined')
{
  //.. win/ie
  window.attachEvent('onload', initCanvases);
}
//** remove this condition to degrade older browsers
else
{
  //.. mac/ie5 and anything else that gets this far
  //if there's an existing onload function
  if(typeof window.onload == 'function')
  {
    //store it
    var existing = onload;
    //add new onload handler
    window.onload = function()
    {
      //call existing onload function
      existing();
      //call generic onload function
      initCanvases();
    };
  }
  else
  {
    //setup onload function
    window.onload = initCanvases;
  }
}
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
			el = document.getElementById("slid1"+id);
			if (hasTouch) {
				el.addEventListener('touchstart',onsliderstart);
				el.parentNode.addEventListener('touchend',onsliderstop);
				el = document.getElementById("slid2"+id);
				el.addEventListener('touchstart',onsliderstart);
				el.parentNode.addEventListener('touchend',onsliderstop);
				
			} else {
				el.onmousedown =  onsliderstart;
				el.parentNode.onmouseup =  onsliderstop;
				el = document.getElementById("slid2"+id);
				el.onmousedown =  onsliderstart;
				el.parentNode.onmouseup =  onsliderstop;
			}
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
	normslider.curslider.el = ev.target || ev.srcElement;
	normslider.curslider.id = normslider.curslider.el.id.substring(5);
	normslider.curslider.type = document.getElementById("shaderegions"+normslider.curslider.id).value;
	normslider.curslider.outnode = document.getElementById(normslider.outputid);
	normslider.curslider.startpos = getMouseOffset(normslider.curslider.el,ev);
	if (hasTouch) {
		document.addEventListener('touchmove',onsliderchange);
	} else {
		normslider.curslider.el.parentNode.onmousemove = onsliderchange;
	}
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
		if (hasTouch) {
			document.removeEventListener('touchmove',onsliderchange);
		} else {
			normslider.curslider.el.parentNode.onmousemove = null;
		}
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

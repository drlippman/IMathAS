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

function addTarget(tarnum,target,imgpath,formel,xmin,xmax,ymin,ymax,imgborder,imgwidth,imgheight,defmode,dotline,locky) {
	var tarel = document.getElementById(target);
	var tarpos = getPosition(tarel);
	
	targets[tarnum] = {el: tarel, left: tarpos.x, top: tarpos.y, width: tarel.offsetWidth, height: tarel.offsetHeight, xmin: xmin, xmax: xmax, ymin: ymin, ymax: ymax, imgborder: imgborder, imgwidth: imgwidth, imgheight: imgheight, mode: defmode, dotline: dotline};
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
			ctx.fill();
			ctx.restore();	
		}
		ctx.beginPath();
		if (x2 != null) { //at least one point set
			if (x2!=ineqlines[curTarget][i][0][0]) {
				var slope = (y2 - ineqlines[curTarget][i][0][1])/(x2-ineqlines[curTarget][i][0][0]);
			}
			if (Math.abs(x2-ineqlines[curTarget][i][0][0])<1 || Math.abs(slope)>100) { //vert line
				//document.getElementById("ans0-0").innerHTML = 'vert';
				if (ineqtypes[curTarget][i]==10.2) {
					//TODO:  line dash
					var dy = targets[curTarget].imgheight/20;
					for (var j=0; j<10; j++) {
						ctx.moveTo(ineqlines[curTarget][i][0][0],dy*2*j);
						ctx.lineTo(ineqlines[curTarget][i][0][0],dy*(2*j+1));
						ctx.stroke();
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
					//TODO:  line dash
					var dx = targets[curTarget].imgwidth/20;
					if (Math.abs(slope)>1) {
						dx = dx/Math.abs(slope);
						if (dx<1) {dx = 1;}
					}
					var n = Math.ceil(targets[curTarget].imgwidth/dx);
					for (var j=0; j<n; j++) {
						ctx.moveTo(dx*2*j,yleft + slope*dx*2*j);
						ctx.lineTo(dx*(2*j+1),yleft + slope*dx*(2*j+1));
						ctx.stroke();
					}
					
				} else {
					ctx.moveTo(0,yleft);
					ctx.lineTo(targets[curTarget].imgwidth,yright);	
					ctx.stroke();
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
			var y2 = null;
			if (tplines[curTarget][i].length==2) {  //if two points set
				x2 = tplines[curTarget][i][1][0];
				y2 = tplines[curTarget][i][1][1];
			} else if (curTPcurve==i && x!=null && tplines[curTarget][i].length==1) {  //one point set, use mouse pos for third
				x2 = x;
				y2 = y;
			}
			if (x2 != null) { //at least one point set
				if (tptypes[curTarget][i]==5.3) {
					ctx.moveTo(tplines[curTarget][i][0][0],tplines[curTarget][i][0][1]);
					ctx.lineTo(x2,y2);
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
		}
		ctx.stroke();
		for (var j=0; j<tplines[curTarget][i].length; j++) {
			ctx.fillRect(tplines[curTarget][i][j][0]-3,tplines[curTarget][i][j][1]-3,6,6);
		}
		ctx.beginPath();
	}
	var linefirstx, linefirsty, linelastx, linelasty;
	for (var i=0;i<lines[curTarget].length; i++) {
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
	}
	ctx.stroke();
	
	ctx.beginPath();
	for (var i=0; i<dots[curTarget].length; i++) {
		ctx.moveTo(dots[curTarget][i][0]+5,dots[curTarget][i][1]);
		ctx.arc(dots[curTarget][i][0],dots[curTarget][i][1],5,0,Math.PI*2,true);
	}
	ctx.fill();
	if (targets[curTarget].dotline==true) {
		ctx.beginPath();
		for (var i=0;i<lines[curTarget].length; i++) {
			for (var j=0;j<lines[curTarget][i].length; j++) {
				if ((j==0) || Math.pow((lines[curTarget][i][j][0] - lines[curTarget][i][0][0]),2)+Math.pow((lines[curTarget][i][j][1] - lines[curTarget][i][0][1]),2)>25) {
					ctx.moveTo(lines[curTarget][i][j][0]+5,lines[curTarget][i][j][1]);
					ctx.arc(lines[curTarget][i][j][0],lines[curTarget][i][j][1],5,0,Math.PI*2,true);
				}
			}
		}
		ctx.fill();
	}
	//ctx.beginPath();
	
	ctx.fillStyle = "rgb(255,255,255)";
	ctx.beginPath();
	for (var i=0; i<odots[curTarget].length; i++) {
		ctx.moveTo(odots[curTarget][i][0]+5,odots[curTarget][i][1]);
		ctx.arc(odots[curTarget][i][0],odots[curTarget][i][1],4,0,Math.PI*2,true);
	}
	ctx.fill();
	ctx.lineWidth = 2;
	ctx.beginPath();
	for (var i=0; i<odots[curTarget].length; i++) {
		ctx.moveTo(odots[curTarget][i][0]+5,odots[curTarget][i][1]);
		ctx.arc(odots[curTarget][i][0],odots[curTarget][i][1],4,0,Math.PI*2,true);
	}
	ctx.stroke();
	
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
		mouseisdown = true;
		var tarelpos = getPosition(targets[curTarget].el);
		var mouseOff = {x:(mousePos.x - tarelpos.x), y: (mousePos.y-tarelpos.y)};
		  
		//are we inside target region?
		if (mouseOff.x>-1 && mouseOff.x<targets[curTarget].width && mouseOff.y>-1 && mouseOff.y<targets[curTarget].height) {
			if (drawlocky[curTarget]==1) {
				mouseOff.y = targets[curTarget].imgheight/2;
			}
			
			//see if current point
			
			var foundpt = findnearpoint(curTarget,mouseOff);
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
				} else if (targets[curTarget].mode==0) { //in line mode
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
				} else if (targets[curTarget].mode>=5 && targets[curTarget].mode<10) {//in tpline mode
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
				} else if (foundpt[0]>=5 && foundpt[0]<10) { //if point is on tpline
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
	}		
}

function findnearpoint(thetarget,mouseOff) {
	if (drawstyle[thetarget]==0) {
		if (targets[thetarget].mode==0 || targets[thetarget].mode==0.5) { //if in line mode
			for (var i=0;i<lines[thetarget].length;i++) { //check lines
				for (var j=lines[thetarget][i].length-1; j>=0;j--) {
					var dist = Math.pow(lines[thetarget][i][j][0]-mouseOff.x,2) + Math.pow(lines[thetarget][i][j][1]-mouseOff.y,2);
					if (dist<25) {
						return [targets[thetarget].mode,i,j];
					}
				}
			}
		} else if (targets[thetarget].mode==1) {
			for (var i=0; i<dots[thetarget].length;i++) { //check dots
				if (Math.pow(dots[thetarget][i][0]-mouseOff.x,2) + Math.pow(dots[thetarget][i][1]-mouseOff.y,2)<25) {
					return [1,i];
				}
			}
		} else if (targets[thetarget].mode==2) {
			for (var i=0; i<odots[thetarget].length;i++) { //check opendots
				if (Math.pow(odots[thetarget][i][0]-mouseOff.x,2) + Math.pow(odots[thetarget][i][1]-mouseOff.y,2)<25) {
					return [2,i];
				}
			}
		} else if (targets[thetarget].mode>=5 && targets[thetarget].mode<10) { //if in tpline mode
			for (var i=0;i<tplines[thetarget].length;i++) { //check lines
				for (var j=tplines[thetarget][i].length-1; j>=0;j--) {
					var dist = Math.pow(tplines[thetarget][i][j][0]-mouseOff.x,2) + Math.pow(tplines[thetarget][i][j][1]-mouseOff.y,2);
					if (dist<25) {
						return [tptypes[thetarget][i],i,j];
					}
				}
			}
		} else if (targets[thetarget].mode>=10 && targets[thetarget].mode<11) { //if in ineqline mode
			for (var i=0;i<ineqlines[thetarget].length;i++) { //check lines
				for (var j=ineqlines[thetarget][i].length-1; j>=0;j--) {
					var dist = Math.pow(ineqlines[thetarget][i][j][0]-mouseOff.x,2) + Math.pow(ineqlines[thetarget][i][j][1]-mouseOff.y,2);
					if (dist<25) {
						return [ineqtypes[thetarget][i],i,j];
					}
				}
			}
		}
	} else {
		for (var i=0; i<dots[thetarget].length;i++) { //check dots
			if (Math.pow(dots[thetarget][i][0]-mouseOff.x,2) + Math.pow(dots[thetarget][i][1]-mouseOff.y,2)<25) {
				return [1,i];
			}
		}
		for (var i=0; i<odots[thetarget].length;i++) { //check opendots
			if (Math.pow(odots[thetarget][i][0]-mouseOff.x,2) + Math.pow(odots[thetarget][i][1]-mouseOff.y,2)<25) {
				return [2,i];
			}
		}
		for (var i=0;i<tplines[thetarget].length;i++) { //check lines
			for (var j=tplines[thetarget][i].length-1; j>=0;j--) {
				var dist = Math.pow(tplines[thetarget][i][j][0]-mouseOff.x,2) + Math.pow(tplines[thetarget][i][j][1]-mouseOff.y,2);
				if (dist<25) {
					return [tptypes[thetarget][i],i,j];
				}
			}
		}
		for (var i=0;i<ineqlines[thetarget].length;i++) { //check inqs
			for (var j=ineqlines[thetarget][i].length-1; j>=0;j--) {
				var dist = Math.pow(ineqlines[thetarget][i][j][0]-mouseOff.x,2) + Math.pow(ineqlines[thetarget][i][j][1]-mouseOff.y,2);
				if (dist<25) {
					return [ineqtypes[thetarget][i],i,j];
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
			}
		}
		if (curLine==null && curTPcurve == null) {
			targets[curTarget].el.style.cursor = 'move';
		} else {
			targets[curTarget].el.style.cursor = 'url('+imasroot+'/img/pen.cur), default';
		}
	}
	lastdrawmouseup = mousePos;
	if (curTarget!=null && dragObj!=null) { //is a target currectly in action, and dragging
		var tarelpos = getPosition(targets[curTarget].el);
		var mouseOff = {x:(mousePos.x - tarelpos.x), y: (mousePos.y-tarelpos.y)};
		
		//are we inside target region?
		if (mouseOff.x>-1 && mouseOff.x<targets[curTarget].width && mouseOff.y>-1 && mouseOff.y<targets[curTarget].height) {
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
			} else if (dragObj.mode>=5 && dragObj.mode<10) { //if line, return pt to orig pos
				tplines[curTarget][dragObj.num][dragObj.subnum] = oldpointpos;
				curTPcurve = null;
			} else if (dragObj.mode>=10 && dragObj.mode<11) { //if ineq, return pt to orig pos
				ineqlines[curTarget][dragObj.num][dragObj.subnum] = oldpointpos;
				curIneqcurve = null;
			}
			dragObj = null;
			drawTarget();
		}
	}
}

function drawMouseMove(ev) {
	var tempTarget = null;
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
		var tarelpos = getPosition(targets[curTarget].el);
		var mouseOff = {x:(mousePos.x - tarelpos.x), y: (mousePos.y-tarelpos.y)};
		if (drawlocky[curTarget]==1) {
			mouseOff.y = targets[curTarget].imgheight/2;
		}
		//are we inside target region?
		if (mouseOff.x>-1 && mouseOff.x<targets[curTarget].width && mouseOff.y>-1 && mouseOff.y<targets[curTarget].height) {
			if (dragObj==null) { //notdragging
				if (curLine!=null) {
					if (mouseisdown && targets[curTarget].mode==0) {
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

function initCanvases() {
	for (var i=0;i<canvases.length;i++) {
		if (drawla[i]!=null && drawla[i].length>2) {
			lines[canvases[i][0]] = drawla[i][0];
			dots[canvases[i][0]] = drawla[i][1];
			odots[canvases[i][0]] = drawla[i][2];
			if (drawla[i].length>3 && drawla[i][3].length>0) {
				tptypes[canvases[i][0]] = [];
				tplines[canvases[i][0]] = [];
				for (var j=0; j<drawla[i][3].length;j++) {
					tptypes[canvases[i][0]][j] = drawla[i][3][j][0];
					tplines[canvases[i][0]][j] = [drawla[i][3][j].slice(1,3),drawla[i][3][j].slice(3)];
				}
			}
			if (drawla[i].length>4 && drawla[i][4].length>0) {
				ineqtypes[canvases[i][0]] = [];
				ineqlines[canvases[i][0]] = [];
				for (var j=0; j<drawla[i][4].length;j++) {
					//CHECK
					ineqtypes[canvases[i][0]][j] = drawla[i][4][j][0];
					ineqlines[canvases[i][0]][j] = [drawla[i][4][j].slice(1,3),drawla[i][4][j].slice(3,5),drawla[i][4][j].slice(5)];
				}
			}
		}
		addTarget(canvases[i][0],'canvas'+canvases[i][0],imasroot+'/filter/graph/imgs/'+canvases[i][1],'qn'+canvases[i][0],canvases[i][2],canvases[i][3],canvases[i][4],canvases[i][5],canvases[i][6],canvases[i][7],canvases[i][8],canvases[i][9],canvases[i][10],canvases[i][11]);
	}
}

document.onmousedown =  drawMouseDown;
document.onmouseup =  drawMouseUp;
document.onmousemove = drawMouseMove;

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



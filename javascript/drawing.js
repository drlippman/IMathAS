var mouseisdown = false;
var targets = new Array();
var imgs = new Array();
var targetOuts = new Array();
var lines = new Array();
var dots = new Array();
var odots = new Array();
var tplines = new Array();
var tptypes = new Array();
var canvases = new Array();
var drawla = new Array();
var curLine = null;
var drawstyle = [];
var curTPcurve = {tpline:null, tpparab:null, tpcirc:null, tpellipse:null};
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
	curTarget = tarnum;
	drawTarget();
	curTarget = null;
	curLine = null;
	dragObj = null;
}

function addTarget(tarnum,target,imgpath,formel,xmin,xmax,ymin,ymax,imgborder,imgwidth,imgheight,defmode,dotline) {
	var tarel = document.getElementById(target);
	var tarpos = getPosition(tarel);
	
	targets[tarnum] = {el: tarel, left: tarpos.x, top: tarpos.y, width: tarel.offsetWidth, height: tarel.offsetHeight, xmin: xmin, xmax: xmax, ymin: ymin, ymax: ymax, imgborder: imgborder, imgwidth: imgwidth, imgheight: imgheight, mode: defmode, dotline: dotline};
	targetOuts[tarnum] = document.getElementById(formel);
	if (lines[tarnum]==null) {lines[tarnum] = new Array();}
	if (dots[tarnum]==null) {dots[tarnum] = new Array();}
	if (odots[tarnum]==null) {odots[tarnum] = new Array();}
	if (tplines[tarnum]==null) {tplines[tarnum] = new Array();}
	if (tptypes[tarnum]==null) {tptypes[tarnum] = new Array();}
	if (defmode>=5) {
		drawstyle[tarnum] = 1;
	} else {
		drawstyle[tarnum] = 0;
	}
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
	for (var i=0;i<tplines[curTarget].length; i++) {
		if (tptypes[curTarget][i]>=5 && tptypes[curTarget][i]<6) {//if a tpline 
			var slope = null;
			var x2 = null;
			var y2 = null;
			if (tplines[curTarget][i].length==2) {
				x2 = tplines[curTarget][i][1][0];
				y2 = tplines[curTarget][i][1][1];
			} else if (curTPcurve.tpline==i && x!=null && tplines[curTarget][i].length==1) {
				x2 = x;
				y2 = y;
			}
			if (x2 != null) {
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
			} else if (curTPcurve.tpline==i && x!=null && tplines[curTarget][i].length==1) {
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
			} else if (curTPcurve.tpline==i && x!=null && tplines[curTarget][i].length==1) {
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
	for (var i=0;i<lines[curTarget].length; i++) {
		for (var j=0;j<lines[curTarget][i].length; j++) {
			if (j==0) {
				ctx.moveTo(lines[curTarget][i][j][0],lines[curTarget][i][j][1]);
			} else {
				ctx.lineTo(lines[curTarget][i][j][0],lines[curTarget][i][j][1]);
			}
		}
		if (i==curLine && x!=null) {
			ctx.lineTo(x,y);
		} 
	}
	for (var i=0; i<odots[curTarget].length; i++) {
		ctx.moveTo(odots[curTarget][i][0]+5,odots[curTarget][i][1]);
		ctx.arc(odots[curTarget][i][0],odots[curTarget][i][1],4,0,Math.PI*2,true);
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
				} else if (targets[curTarget].mode>=5) {//in tpline mode
					if (curTPcurve.tpline==null) { //start new tpline
						tplines[curTarget].push([[mouseOff.x,mouseOff.y]]);
						curTPcurve.tpline = tplines[curTarget].length-1;
						tptypes[curTarget][curTPcurve.tpline] = targets[curTarget].mode;
						mouseisdown = false;
					} else {//in existing line
						tplines[curTarget][curTPcurve.tpline].push([mouseOff.x,mouseOff.y]);
						if (tplines[curTarget][curTPcurve.tpline].length==2) {
							//second point is set.  switch to drag and end line
							dragObj = {mode: targets[curTarget].mode, num: curTPcurve.tpline, subnum: 1};
							curTPcurve.tpline = null;
						}		
					}
				}
			} else { //clicked on current point
				if (foundpt[0]==0) { //if point is on line
					if (curLine==null) {//not current in line
						targets[curTarget].el.style.cursor = 'move';
						//start dragging
						dragObj = {mode: 0, num: foundpt[1], subnum: foundpt[2]};
						oldpointpos = lines[curTarget][foundpt[1]][foundpt[2]];
						if (foundpt[2] == lines[curTarget][foundpt[1]].length-1) {
							//if last point in line, continue line too
							curLine = foundpt[1];
						} else if (foundpt[2] == 0) {
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
				} else if (foundpt[0]>=5) { //if point is on tpline
					targets[curTarget].el.style.cursor = 'move';
					//start dragging
					dragObj = {mode: foundpt[0], num: foundpt[1], subnum: foundpt[2]};
					oldpointpos = tplines[curTarget][foundpt[1]][foundpt[2]];
					//curTPcurve.tpline = foundpt[1];
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
			if (curTPcurve.tpline != null) {
				if (tplines[curTarget][curTPcurve.tpline].length<2) {
					tplines[curTarget].splice(curTPcurve.tpline,1);
				}
				targets[curTarget].el.style.cursor = 'url('+imasroot+'/img/pen.cur), default';
			}
			curLine = null;
			curTPcurve.tpline = null;
			dragObj = null;
			drawTarget();
			curTarget = null;
			
		}
	}		
}

function findnearpoint(thetarget,mouseOff) {
	if (drawstyle[thetarget]==0) {
		if (targets[thetarget].mode==0) { //if in line mode
			for (var i=0;i<lines[thetarget].length;i++) { //check lines
				for (var j=lines[thetarget][i].length-1; j>=0;j--) {
					var dist = Math.pow(lines[thetarget][i][j][0]-mouseOff.x,2) + Math.pow(lines[thetarget][i][j][1]-mouseOff.y,2);
					if (dist<25) {
						return [0,i,j];
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
		} else if (targets[thetarget].mode>=5) { //if in tpline mode
			for (var i=0;i<tplines[thetarget].length;i++) { //check lines
				for (var j=tplines[thetarget][i].length-1; j>=0;j--) {
					var dist = Math.pow(tplines[thetarget][i][j][0]-mouseOff.x,2) + Math.pow(tplines[thetarget][i][j][1]-mouseOff.y,2);
					if (dist<25) {
						return [tptypes[thetarget][i],i,j];
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
		if (curLine==null && curTPcurve.tpline == null) {
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
			if (dragObj.mode==1) { //if dot, delete dot
				dots[curTarget].splice(dragObj.num,1);
			} else if (dragObj.mode==2) { //if open dot, delete dot
				odots[curTarget].splice(dragObj.num,1);
			} else if (dragObj.mode==0) { //if line, return pt to orig pos
				lines[curTarget][dragObj.num][dragObj.subnum] = oldpointpos;
			} else if (dragObj.mode>=5) { //if line, return pt to orig pos
				tplines[curTarget][dragObj.num][dragObj.subnum] = oldpointpos;
				curTPcurve.tpline = null;
			}
			dragObj = null;
			drawTarget();
		}
	}
}

function drawMouseMove(ev) {
	var tempTarget = null;
	var mousePos = mouseCoords(ev);
	//document.getElementById("ans0-0").innerHTML = dragObj + ';' + curTPcurve.tpline;
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
		//are we inside target region?
		if (mouseOff.x>-1 && mouseOff.x<targets[curTarget].width && mouseOff.y>-1 && mouseOff.y<targets[curTarget].height) {
			if (dragObj==null) { //notdragging
				if (curLine!=null) {
					if (mouseisdown) {
						var last = lines[curTarget][curLine].length-1;
						var dist = Math.pow(lines[curTarget][curLine][last][0]-mouseOff.x,2) + Math.pow(lines[curTarget][curLine][last][1]-mouseOff.y,2);
						//add point to line
						if (dist>25) {
							lines[curTarget][curLine].push([mouseOff.x,mouseOff.y]);
							drawTarget();
						} else {
							drawTarget(mouseOff.x,mouseOff.y);
						}
					} else {
						//draw temp line
						drawTarget(mouseOff.x,mouseOff.y);
					}
				} else if (curTPcurve.tpline!=null) {
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
				if (dragObj.mode==0) {
					lines[curTarget][dragObj.num][dragObj.subnum] = [mouseOff.x,mouseOff.y];
				} else if (dragObj.mode==1) {
					dots[curTarget][dragObj.num] = [mouseOff.x,mouseOff.y];
				} else if (dragObj.mode==2) {
					odots[curTarget][dragObj.num] = [mouseOff.x,mouseOff.y];
				} else if (dragObj.mode>=5) {
					tplines[curTarget][dragObj.num][dragObj.subnum] = [mouseOff.x,mouseOff.y];
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
		}
		addTarget(canvases[i][0],'canvas'+canvases[i][0],imasroot+'/filter/graph/imgs/'+canvases[i][1],'qn'+canvases[i][0],canvases[i][2],canvases[i][3],canvases[i][4],canvases[i][5],canvases[i][6],canvases[i][7],canvases[i][8],canvases[i][9],canvases[i][10]);
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



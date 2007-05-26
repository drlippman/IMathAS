var mouseisdown = false;
var targets = new Array();
var imgs = new Array();
var targetOuts = new Array();
var lines = new Array();
var dots = new Array();
var odots = new Array();
var canvases = new Array();
var drawla = new Array();
var curLine = null;
var dragObj = null;
var oldpointpos = null;
var curTarget = null;
var nocanvaswarning = false;

function clearcanvas(tarnum) {
	lines[tarnum].length = 0;
	dots[tarnum].length = 0;
	odots[tarnum].length = 0;
	curTarget = tarnum;
	drawTarget();
	curTarget = null;
	curLine = null;
	dragObj = null;
}

function addTarget(tarnum,target,imgpath,formel,xmin,xmax,ymin,ymax,imgborder,defmode) {
	var tarel = document.getElementById(target);
	var tarpos = getPosition(tarel);
	
	targets[tarnum] = {el: tarel, left: tarpos.x, top: tarpos.y, width: tarel.offsetWidth, height: tarel.offsetHeight, xmin: xmin, xmax: xmax, ymin: ymin, ymax: ymax, imgborder: imgborder, mode: defmode};
	targetOuts[tarnum] = document.getElementById(formel);
	if (lines[tarnum]==null) {lines[tarnum] = new Array();}
	if (dots[tarnum]==null) {dots[tarnum] = new Array();}
	if (odots[tarnum]==null) {odots[tarnum] = new Array();}
	imgs[tarnum] = new Image();
	imgs[tarnum].onload = function() {
		var oldcurTarget = curTarget;
		curTarget = tarnum;
		drawTarget();
		curTarget = oldcurTarget;
	}
	imgs[tarnum].src = imgpath;
}

function settool(curel,tarnum,mode) {
	var mydiv = document.getElementById("drawtools"+tarnum);
	var mycel       = mydiv.getElementsByTagName("span");
	for (var i=0; i<mycel.length; i++) {
		mycel[i].className = '';
	}
	curel.className = "sel";
	setDrawMode(tarnum,mode);
}
function setDrawMode(tarnum,mode) {
	targets[tarnum].mode = mode;
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
	ctx.clearRect(0,0,300,300);
	ctx.drawImage(imgs[curTarget],0,0);
	ctx.beginPath();
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
			curLine = null;
			dragObj = null;
			drawTarget();
			curTarget = null;
			
		}
	}		
}

function findnearpoint(thetarget,mouseOff) {
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
		if (curLine==null) {
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
			}
			dragObj = null;
			drawTarget();
		}
	}
}

function drawMouseMove(ev) {
	var tempTarget = null;
	var mousePos = mouseCoords(ev);
	
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
		if (drawla[i]!=null && drawla[i].length==3) {
			lines[canvases[i][0]] = drawla[i][0];
			dots[canvases[i][0]] = drawla[i][1];
			odots[canvases[i][0]] = drawla[i][2];
		}
		addTarget(canvases[i][0],'canvas'+canvases[i][0],imasroot+'/filter/graph/imgs/'+canvases[i][1],'qn'+canvases[i][0],canvases[i][2],canvases[i][3],canvases[i][4],canvases[i][5],canvases[i][6],canvases[i][7]);
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


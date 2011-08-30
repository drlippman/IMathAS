/*** 
Table row / column header locker
(c) David Lippman, 2008.  http://www.pierce.ctc.edu/dlippman

v0.2 3/2/09  Fix bug with tablesorter
v0.1 10/23/08

This script allows you to lock the header row and column of an html table.
Assumes table has one header <tr> in <thead>, and multiple <tr> in <tbody>.

useage:
var ts = new tablescroller(tableid,lockonload);

tableid is the ID of your table; lockonload is true/false of whether you want
the table to autolock the headers on page load.

Public functions:
ts.lock()		Locks the headers
ts.unlock()		Unlocks the headers
ts.toggle()		Toggles locked/unlocked. Returns corresponding 1 or 0.

This library is free software; you can redistribute it and/or modify it 
under the terms of the GNU Lesser General Public License as published by the 
Free Software Foundation; either version 2.1 of the License, or (at your option)
any later version.

This library is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE. See the GNU Lesser General Public License for more details.
***/

//from http://www.webreference.com/programming/javascript/onloads/
/*  moved to general.js 
function addLoadEvent(func) { 
	  var oldonload = window.onload; 
	  if (typeof window.onload != 'function') { 
	    window.onload = func; 
	  } else { 
	    window.onload = function() { 
	      if (oldonload) { 
	        oldonload(); 
	      } 
	      func(); 
	    } 
	  } 
} */
/*
function findPos(obj) { //from quirksmode.org
	var curleft = curtop = 0;
	if (obj.offsetParent) {
		do {
			curleft += obj.offsetLeft;
			curtop += obj.offsetTop;
		} while (obj = obj.offsetParent);
	}
	return [curleft,curtop];
}
*/
function tablescroller(id,lockonload) {
	var t = this;
	var thetable;
	var tblcont;
	var bigcont;
	var tblid = id;
	var winw;
	var winh;
	var thr;
	var margleft;
	var margtop;
	var leftth;
	var vertadj;
	var tblbrowser;
	var upleftdiv;
	var toggletracker = 0;
	var locktds = new Array();
	var ispreinited = false;
	
//preinit is called onload
//fixes column widths and heights by injecting div's 
//into first and second rows and columns, locking in non-scrolling layout
this.preinit = function(try2) {
	thetable = document.getElementById(tblid);
	if (!(thetable.addEventListener || thetable.attachEvent)) {
		return;
	}
	tblcont = document.createElement("div");
	tblcont.style.margin = 0;
	tblcont.style.padding = 0;
	tblcont.id = "tblcont"+tblid;
	bigcont = document.createElement("div");
	bigcont.style.margin = 0;
	bigcont.style.padding = 0;
	bigcont.id = "bigcont"+tblid;
	thetable.parentNode.insertBefore(bigcont,thetable);
	bigcont.appendChild(tblcont);
	tblcont.appendChild(thetable);
	if (navigator.userAgent.toLowerCase().match(/safari\/(\d+)/)!=null) {
		tblbrowser = 'safari';
		thetable.style.position = 'static';
	} else if (navigator.product && navigator.product=='Gecko') {
		tblbrowser = 'gecko';
		thetable.style.position = 'static';
	} else if (navigator.appName.slice(0,9)=="Microsoft") {
		tblbrowser = 'ie';;
	} 
	if (tblbrowser == 'ie') {
		
	} else {
	
		//Approach:  Start with table layed out without scrolling
		//fix column widths and heights by injecting div's into first and 
		//second rows and columns.  Then when we restrict the container to
		//create scroll, we don't have to worry about different wrapping.
		var trs = document.getElementsByTagName("tr");
		if (trs.length > 100 && try2!=true) {
			return;
		} else if (trs.length > 100) {
			alert("This might take a minute... header locking is slow with lots of students.");
		}
		var theads = trs[0].getElementsByTagName("th");
		leftth = theads[0];
		var firstthcontent = theads[0].innerHTML;
		var first = trs[1].getElementsByTagName("td");
		//fix column widths by injecting fixed-width div in thead tr th's and 
		//first tbody tr td's
		for (var i=0; i<theads.length; i++) {
			var max = theads[i].offsetWidth;
			if (i==0) {
				max += 30;
			}
			var nn = document.createElement("div");
			nn.style.width= max +"px";
			nn.innerHTML = theads[i].innerHTML;
			theads[i].innerHTML = "";
			theads[i].appendChild(nn);
			var nn = document.createElement("div");
			nn.style.width= max +"px";
			nn.innerHTML = first[i].innerHTML;
			first[i].innerHTML = "";
			first[i].appendChild(nn);
		}
		//fix row heights by injecting fixed-height divs in first columns, 
		//and adding a new column of fixed-height divs
		var nnb = document.createElement("div");
		nnb.style.display = "table-cell";
		nnb.style.verticalAlign = "middle";
		for (var i=0;i<trs.length;i++) {
			var nodes = trs[i].getElementsByTagName((i==0?"th":"td"));
			
			var max = nodes[0].offsetHeight;
			if (i==0) {
				margtop = max;
			} else {
				locktds.push(nodes[0]);
			}
			var nn = nnb.cloneNode(true);
			nn.style.height= max +"px";
			nn.innerHTML = nodes[0].innerHTML;
			nodes[0].innerHTML = "";
			nodes[0].appendChild(nn);
			var nn = nnb.cloneNode(true);
			nn.style.height= max +"px";
			var ntd = document.createElement((i==0?"th":"td"));
			ntd.style.paddingLeft = "0px";
			ntd.style.paddingRight = "0px";
			ntd.appendChild(nn);
			trs[i].insertBefore(ntd,nodes[1]);
		}
		if (tblbrowser=='gecko') {
			vertadj = 0;
		} else {
			vertadj = 0;
			upleftdiv = document.createElement("div");
			upleftdiv.style.left = "0px";
			upleftdiv.style.top = "0px";
			upleftdiv.style.position = "absolute";
			upleftdiv.style.visibility = "hidden";
			upleftdiv.style.zIndex = 40;
			upleftdiv.style.display = "table";
			upleftdiv.style.backgroundColor = "#fff";
			upleftdiv.style.overflow = "hidden";
			ndivt = document.createElement("div");
			ndivt.className = theads[0].className;
			ndivt.style.display = "table-cell";
			ndivt.style.verticalAlign = "middle";
			ndiv = document.createElement("div");
			ndiv.style.textAlign = "center";
			ndiv.innerHTML = firstthcontent;
			ndivt.appendChild(ndiv);
			upleftdiv.appendChild(ndivt);
			bigcont.appendChild(upleftdiv);
		}
		if (typeof tableWidget_arraySort[thetable.getAttribute('tableIndex')] == 'object') {
			var cur = tableWidget_arraySort[thetable.getAttribute('tableIndex')];
			tableWidget_arraySort[thetable.getAttribute('tableIndex')] = Array(cur[0],'S').concat(cur.splice(1));
		}
	
	}
	ispreinited = true;

}
//handles adjusing headers during scrolling
scrollhandler = function(e) {
	if (e.target.nodeName=="DIV") {
		var el = e.target;	
		if (tblbrowser=='gecko') {
			thr.style.left = (-1*el.scrollLeft + margleft) + "px";
			leftth.style.left = (el.scrollLeft -margleft)+ "px";
		} else {
			thr.style.left = (-1*el.scrollLeft) + "px";
		}
		
		for (var i=0; i<locktds.length; i++) {
			locktds[i].style.top = (parseInt(locktds[i].getAttribute("origtop")) - el.scrollTop + vertadj ) + "px";
		}
	}
}
//this is called to reset left column positions after table sorting
resettoplocs = function() {
	var trs = document.getElementsByTagName("tr");
	locktds.length = 0;
	for (var i=1;i<trs.length;i++) {
		var nodes = trs[i].getElementsByTagName("td");
		locktds.push(nodes[0]);
	}
	for (var i=0; i<locktds.length; i++) {
		locktds[i].style.position = "static";
	}
	for (var i=0; i<locktds.length; i++) {
		locktds[i].setAttribute("origtop",locktds[i].offsetTop + margtop - vertadj);
	}
	for (var i=0; i<locktds.length; i++) {
		locktds[i].style.position = "absolute";
		locktds[i].style.top = (parseInt(locktds[i].getAttribute("origtop")) - tblcont.scrollTop + vertadj ) + "px";
	}
}
//called after sort to reset in IE
ierelock = function() {
	 var trs = document.getElementsByTagName("tr");
	  var theads = trs[0].getElementsByTagName("th");
	  for (var i=0; i<theads.length; i++) {	  
		  theads[i].style.setExpression("top",'document.getElementById("'+tblcont.id+'").scrollTop-2');
	  }
	  for (var i=1;i<trs.length;i++) {
		  var nodes = trs[i].getElementsByTagName("td");
		  nodes[0].style.position = "relative";
		  nodes[0].style.setExpression("left",'parentNode.parentNode.parentNode.parentNode.scrollLeft');
	  }	
	
}
//locks the header row and column
//adjust the winw and winh calculations to adjust sizing - currently handles
//IE and non-IE separately
this.lock = function() {
	if (!ispreinited) {
		t.preinit(true);
	}
	toggletracker = 1;
	if (tblbrowser == 'ie') {
		if( document.documentElement && ( document.documentElement.clientWidth || document.documentElement.clientHeight ) ) {
		    //IE 6+ in 'standards compliant mode'
		    winw = document.documentElement.clientWidth;
		    winh = document.documentElement.clientHeight;
		  } else if( document.body && ( document.body.clientWidth || document.body.clientHeight ) ) {
		    //IE 4 compatible
		    winw = document.body.clientWidth;
		    winh = document.body.clientHeight;
		  }
		  winw = tblcont.offsetWidth;
		  //winw = Math.round(winw*.95);
		  winh = Math.round(winh*.7);
		  tblcont.style.width = winw+"px";
		  tblcont.style.height = winh+"px";
		  tblcont.style.position = "relative";
		  tblcont.style.overflow = "auto";
		  tblcont.style.border = "1px solid #000";
		  var trs = document.getElementsByTagName("tr");
		  var theads = trs[0].getElementsByTagName("th");
		  //For IE, we'll use IE-specific techniques from
		  //http://home.tampabay.rr.com/bmerkey/examples/locked-column-csv.html
		  theads[0].style.setExpression("left",'parentNode.parentNode.parentNode.parentNode.scrollLeft');
		  theads[0].style.zIndex = 40;
		  for (var i=0; i<theads.length; i++) {	  
			  theads[i].style.setExpression("top",'document.getElementById("'+tblcont.id+'").scrollTop-2');
		  }
		  for (var i=1;i<trs.length;i++) {
			  var nodes = trs[i].getElementsByTagName("td");
			  nodes[0].style.position = "relative";
			  nodes[0].style.setExpression("left",'parentNode.parentNode.parentNode.parentNode.scrollLeft');
		  }
		  trs[0].attachEvent('onclick', ierelock); 
	} else {
		if (window.innerHeight<600) {
			winh = Math.min(Math.round(.9*window.innerHeight),thetable.offsetHeight+30);	
		} else {
			winh = Math.min(Math.round(window.innerHeight - findPos(thetable)[1]-10),thetable.offsetHeight+30);
		}
		//winw = Math.round(.95*window.innerWidth);
		winw = tblcont.offsetWidth;
		
		//Approach:  Start with table layed out without scrolling
		//fix column widths and heights by injecting div's into first and 
		//second rows and columns.  Then when we restrict the container to
		//create scroll, we don't have to worry about different wrapping.
		var trs = document.getElementsByTagName("tr");
		var theads = trs[0].getElementsByTagName("th");
		leftth = theads[0];
		var firstthcontent = theads[0].innerHTML;
		//record current top of row headers
		for (var i=0; i<locktds.length; i++) {
			locktds[i].setAttribute("origtop",locktds[i].offsetTop);
		}
		//set row headers out of document flow - we'll adjust the
		//position onscroll
		for (var i=0; i<locktds.length; i++) {
			locktds[i].style.position = "absolute";
			locktds[i].style.left = "0px";
		}
		margleft = locktds[0].offsetWidth;
		margtop = leftth.offsetHeight;
		
		//constrain size.  bigcont is the injected outsize div
		//tblcont holds the table, and is shifted right to allow room
		//for the out-of-flow row headers
		bigcont.style.width = winw+"px";
		bigcont.style.height = winh+"px";
		bigcont.style.position = "relative";
		bigcont.style.overflow = "hidden";
		bigcont.style.border = "1px solid #000";
		tblcont.style.marginLeft = margleft+"px";
		tblcont.style.marginTop = margtop+"px";
		tblcont.style.width = (winw-margleft)+"px";
		tblcont.style.height = (winh-margtop)+"px";
		tblcont.style.overflow = "auto";
		thetable.style.margin = "0px";
		
		thr = trs[0];
		thr.style.position = "absolute";
		thr.style.top = "0px";
		
		//gecko lets us take the top-left cell and remove it
		//independently from the flow.  Safari doesn't, so we put
		//a div over the top-left cell to cover it.
		if (tblbrowser=='gecko') {
			thr.style.left = margleft + "px";
			leftth.style.position = "absolute";
			leftth.style.zIndex = 40;
			leftth.style.left = -margleft + "px";
		} else {
			thr.style.left = "0px";
			upleftdiv.style.height= (margtop) +"px";
			upleftdiv.style.width= margleft +"px";
			upleftdiv.style.visibility = "visible";
		}
		
		//onclick is to reset heights after table sorting clicks
		thr.addEventListener('click', resettoplocs , false); //
		tblcont.addEventListener('scroll', scrollhandler, false);
	}
}
//unlocks headers - undoes this.lock
this.unlock = function() {
	toggletracker = 0;
	if (tblbrowser == 'ie') {
		  //these should be auto, but something's not working right
		  //take advantage of IE's expanding of divs by
		  //clearing overflow hidden
		  //tblcont.style.width = "auto";
		  //tblcont.style.height = "auto";
		  tblcont.style.position = "relative";
		  tblcont.style.overflow = "";
		  tblcont.style.border = "0px";
		  var trs = document.getElementsByTagName("tr");
		  var theads = trs[0].getElementsByTagName("th");
		  theads[0].style.removeExpression("left");
		  theads[0].style.left = "0px";
		  for (var i=0; i<theads.length; i++) {	
			  theads[i].style.removeExpression("top");
			  theads[i].style.top = "0px";
		  }
		  for (var i=1;i<trs.length;i++) {
			  var nodes = trs[i].getElementsByTagName("td");
			  nodes[0].style.position = "";
			  nodes[0].style.removeExpression("left");
		  }
		  trs[0].detachEvent('onclick',ierelock); 
		 
	} else {
	
	var trs = document.getElementsByTagName("tr");
	var theads = trs[0].getElementsByTagName("th");
	leftth = theads[0];
	var firstthcontent = theads[0].innerHTML;
	
	bigcont.style.width = "auto";
	bigcont.style.height = "auto";
	bigcont.style.overflow = "";
	bigcont.style.border = "0px";
	tblcont.style.marginLeft = "0px";
	tblcont.style.marginTop = "0px";
	tblcont.style.width = "auto";
	tblcont.style.height = "auto";
	tblcont.style.overflow = "";
	for (var i=0; i<locktds.length; i++) {
		locktds[i].style.position = "";
		locktds[i].style.width= "";
	}
	thr = trs[0];   
	thr.style.position = "";
	thr.removeEventListener('click', resettoplocs,false);
	if (tblbrowser=='gecko') {
		leftth.style.position = "";
	} else {
		upleftdiv.style.visibility = "hidden";
		//Safari has an issue - this resets page layout
		tblcont.innerHTML = tblcont.innerHTML + " ";	
		locktds.length = 0;
		for (var i=1;i<trs.length;i++) {
			var nodes = trs[i].getElementsByTagName("td");
			locktds.push(nodes[0]);
		}
		thetable = tblcont.getElementsByTagName("table")[0];
	}
	
	tblcont.removeEventListener('scroll', scrollhandler, false);
	
	}
}
//toggles locked/unlocked
this.toggle = function() {
	if (toggletracker==0) {
		this.lock();
		return 1;
	} else {
		this.unlock();
		return 0;
	}
	
}
this.status = function() {
	return toggletracker;
}
	
	if(lockonload) {
		addLoadEvent(this.lock);
	} else {
		addLoadEvent(this.preinit);
	}
}

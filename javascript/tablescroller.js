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
	} 

function tablescroller(id,lockonload) {
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
	

this.preinit = function() {
	thetable = document.getElementById(tblid);
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
	} else if (navigator.product && navigator.product=='Gecko') {
		tblbrowser = 'gecko';
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
	for (var i=0;i<trs.length;i++) {
		var nodes = trs[i].getElementsByTagName((i==0?"th":"td"));
		
		var max = nodes[0].offsetHeight;
		if (i==0) {
			margtop = max;
		} else {
			locktds.push(nodes[0]);
		}
		var nn = document.createElement("div");
		nn.style.height= max +"px";
		nn.style.display = "table-cell";
		nn.style.verticalAlign = "middle";
		nn.innerHTML = nodes[0].innerHTML;
		nodes[0].innerHTML = "";
		nodes[0].appendChild(nn);
		var nn = document.createElement("div");
		nn.style.height= max +"px";
		nn.style.display = "table-cell";
		nn.style.verticalAlign = "middle";
		var ntd = document.createElement((i==0?"th":"td"));
		ntd.appendChild(nn);
		trs[i].insertBefore(ntd,nodes[1]);
	}
	if (tblbrowser=='gecko') {
		vertadj = 0;
	} else {
		vertadj = 15;
		upleftdiv = document.createElement("div");
		upleftdiv.style.left = "0px";
		upleftdiv.style.top = "0px";
		upleftdiv.style.position = "absolute";
		upleftdiv.style.visibility = "hidden";
		upleftdiv.style.zIndex = 40;
		upleftdiv.style.textAlign = "center";
		upleftdiv.style.backgroundColor = "#fff";
		upleftdiv.style.verticalAlign = "middle";
		upleftdiv.innerHTML = firstthcontent ;
		bigcont.appendChild(upleftdiv);
	}
		
	
	}

}

this.scrollhandler = function(e) {
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
this.resettoplocs = function() {
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
this.ierelock = function() {
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

this.lock = function() {
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
		  winw = Math.round(winw*.95);
		  winh = Math.round(winh*.7);
		  tblcont.style.width = winw+"px";
		  tblcont.style.height = winh+"px";
		  tblcont.style.position = "relative";
		  tblcont.style.overflow = "auto";
		  tblcont.style.border = "1px solid #000";
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
		  trs[0].attachEvent('onclick', this.ierelock); 
	} else {
	
	winh = Math.round(.9*window.innerHeight);
	winw = Math.round(.95*window.innerWidth);
	
	//Approach:  Start with table layed out without scrolling
	//fix column widths and heights by injecting div's into first and 
	//second rows and columns.  Then when we restrict the container to
	//create scroll, we don't have to worry about different wrapping.
	var trs = document.getElementsByTagName("tr");
	var theads = trs[0].getElementsByTagName("th");
	leftth = theads[0];
	var firstthcontent = theads[0].innerHTML;
	
	for (var i=0; i<locktds.length; i++) {
		locktds[i].setAttribute("origtop",locktds[i].offsetTop);
	}
	for (var i=0; i<locktds.length; i++) {
		locktds[i].style.position = "absolute";
		locktds[i].style.left = "0px";
	}
	margleft = locktds[0].offsetWidth;
	margtop = leftth.offsetHeight;
	bigcont.style.width = winw+"px";
	bigcont.style.height = winh+"px";
	bigcont.style.position = "relative";
	bigcont.style.overflow = "hidden";
	tblcont.style.marginLeft = margleft+"px";
	tblcont.style.marginTop = margtop+"px";
	tblcont.style.width = (winw-margleft)+"px";
	tblcont.style.height = (winh-margtop)+"px";
	tblcont.style.overflow = "auto";
	
	thr = trs[0];
	thr.style.position = "absolute";
	thr.style.top = "0px";
	
	
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
	///thr.addEventListener('click', this.resettoplocs , false); //
	tblcont.addEventListener('scroll', this.scrollhandler, false);
	}
}
this.unlock = function() {
	toggletracker = 0;
	if (tblbrowser == 'ie') {
		  tblcont.style.width = "auto";
		  tblcont.style.height = "auto";
		  tblcont.style.position = "relative";
		  tblcont.style.overflow = "";
		  tblcont.style.border = "0px";
		  var trs = document.getElementsByTagName("tr");
		  var theads = trs[0].getElementsByTagName("th");
		  for (var i=0; i<theads.length; i++) {	  
			  theads[i].style.removeExpression("top");
		  }
		  for (var i=1;i<trs.length;i++) {
			  var nodes = trs[i].getElementsByTagName("td");
			  nodes[0].style.position = "";
			  nodes[0].style.removeExpression("left");
		  }
		  trs[0].detachEvent('onclick',this.ierelock); 
		  
	} else {
	
	//Approach:  Start with table layed out without scrolling
	//fix column widths and heights by injecting div's into first and 
	//second rows and columns.  Then when we restrict the container to
	//create scroll, we don't have to worry about different wrapping.
	var trs = document.getElementsByTagName("tr");
	var theads = trs[0].getElementsByTagName("th");
	leftth = theads[0];
	var firstthcontent = theads[0].innerHTML;
	
	bigcont.style.width = "auto";
	bigcont.style.height = "auto";
	bigcont.style.overflow = "";
	tblcont.style.marginLeft = "0px";
	tblcont.style.marginTop = "0px";
	tblcont.style.width = "auto";
	tblcont.style.height = "auto";
	tblcont.style.overflow = "";
	for (var i=0; i<locktds.length; i++) {
		locktds[i].style.position = "";
	}
	thr = trs[0];   
	thr.style.position = "";
	thr.removeEventListener('click', this.resettoplocs,false);
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
	}
	
	tblcont.removeEventListener('scroll', this.scrollhandler, false);
	
	}
}
this.toggle = function() {
	if (toggletracker==0) {
		this.lock();
		return 1;
	} else {
		this.unlock();
		return 0;
	}
	
}
	addLoadEvent(this.preinit);
	if(lockonload) {
		//addLoadEvent(this.lock);
	}
}

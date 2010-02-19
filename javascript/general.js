//dropdown menu
var closetimer	= 0;
var ddmenuitem	= 0;
var homemenuloaded = 0;
// open hidden layer
function mopen(id,cid) {	
	if (id=='homemenu' && homemenuloaded==0) {
		basicahah(imasroot+'/gethomemenu.php?cid='+cid,'homemenu');
		homemenuloaded = 1;
	}
	mcancelclosetime();
	if(ddmenuitem) ddmenuitem.style.visibility = 'hidden';
	ddmenuitem = document.getElementById(id);
	ddmenuitem.style.visibility = 'visible';
}
// close showed layer
function mclose() {
	if(ddmenuitem) ddmenuitem.style.visibility = 'hidden';
}
// go close timer
function mclosetime() {
	closetimer = window.setTimeout(mclose, 250);
}
// cancel close timer
function mcancelclosetime() {
	if(closetimer)
	{
		window.clearTimeout(closetimer);
		closetimer = null;
	}
}

function basicahah(url, target, def) {
  if (def==null) { def =  ' Fetching data... ';}
  document.getElementById(target).innerHTML = def;
  if (window.XMLHttpRequest) { 
    req = new XMLHttpRequest(); 
  } else if (window.ActiveXObject) { 
    req = new ActiveXObject("Microsoft.XMLHTTP"); 
  } 
  if (req != undefined) { 
    req.onreadystatechange = function() {basicahahDone(url, target);}; 
    req.open("GET", url, true); 
    req.send(""); 
  } 
}  

function basicahahDone(url, target) { 
  if (req.readyState == 4) { // only if req is "loaded" 
    if (req.status == 200) { // only if "OK" 
      document.getElementById(target).innerHTML = req.responseText; 
    } else { 
      document.getElementById(target).innerHTML=" AHAH Error:\n"+ req.status + "\n" +req.statusText; 
    } 
  } 
}

function arraysearch(needle,hay) {
      for (var i=0; i<hay.length;i++) {
            if (hay[i]==needle) {
                  return i;
            }
      }
      return -1;
   }
   
var tipobj = 0;
function tipshow(el,tip) {
	if (typeof tipobj!= 'object') {
		tipobj = document.createElement("div");
		tipobj.className = "tips";
		document.getElementsByTagName("body")[0].appendChild(tipobj);
	} 
	tipobj.innerHTML = tip;
	tipobj.style.display = "block";
	
	if (typeof AMnoMathML!='undefined' && typeof noMathRender != 'undefined') {
		if (!AMnoMathML && !noMathRender) {
			AMprocessNode(tipobj);
		}
	}
	var p = findPos(el);

	if (self.innerHeight) {
                x = self.innerWidth;
        } else if (document.documentElement && document.documentElement.clientHeight) {
                x = document.documentElement.clientWidth;
        } else if (document.body) {
                x = document.body.clientWidth;
        }
        var scrOfX = 0;
        if( typeof( window.pageYOffset ) == 'number' ) {
	    scrOfX = window.pageXOffset;
	  } else if( document.body && ( document.body.scrollLeft || document.body.scrollTop ) ) {
	    scrOfX = document.body.scrollLeft;
	  } else if( document.documentElement && ( document.documentElement.scrollLeft || document.documentElement.scrollTop ) ) {
	    scrOfX = document.documentElement.scrollLeft;
	  }

        x += scrOfX;
        if ((p[0] + tipobj.offsetWidth)>x) {
        	p[0] = x - tipobj.offsetWidth - 30;
        }
        
	tipobj.style.left = (p[0]+20) + "px";
	if (p[1] < 30) {
		tipobj.style.top = (p[1]+20) + "px";
	} else {
		tipobj.style.top = (p[1]-tipobj.offsetHeight) + "px";
	}
}

function popupwindow(content,width,height,scroll) {
	var attr = "width="+width+",height="+height+",status=0,resizeable=1,directories=0,menubar=0";
	if (scroll!=null && scroll==true) {
		attr += ",scrollbars=1";
	}
	if (content.match(/^http/)) {
		window.open(content,"popup",attr);
	} else {
		var win1 = window.open('',"popup",attr);
		win1.document.write('<html><head><title>Popup</title></head><body>');
		win1.document.write(content);
		win1.document.write('</body></html>');
		win1.document.close();
	}
}
function tipout(el) {
	tipobj.style.display = "none";	
}

function findPos(obj) { //from quirksmode.org
	var curleft = curtop = 0;
	if (obj.offsetParent) {
		do {
			curleft += obj.offsetLeft;
			curtop += obj.offsetTop;
			if (obj.offsetParent) {
				if (obj.parentNode && obj.offsetParent!=obj.parentNode) {
					curleft -= obj.parentNode.scrollLeft;
					curtop -= obj.parentNode.scrollTop;
				} else {
					curleft -= obj.offsetParent.scrollLeft;
					curtop -= obj.offsetParent.scrollTop;
				}
			}
		} while (obj = obj.offsetParent);
	}
	return [curleft,curtop];
}
function togglepic(el) {
	if (el.getAttribute("src").match("userimg_sm")) {
		el.setAttribute("src",el.getAttribute("src").replace("_sm","_"));
	} else {
		el.setAttribute("src",el.getAttribute("src").replace("_","_sm"));
	}
}

//from http://www.webreference.com/programming/javascript/onloads/
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
var GB_loaded = false;
function GB_show(caption,url,width,height) {
	if (GB_loaded == false) {
		var gb_overlay = document.createElement("div");
		gb_overlay.id = "GB_overlay";
		document.getElementsByTagName("body")[0].appendChild(gb_overlay);
		var gb_window = document.createElement("div");
		gb_window.id = "GB_window";
		gb_window.innerHTML = '<div id="GB_caption"></div><div id="GB_loading">Loading...</div><div id="GB_frameholder" src=></div>';
		document.getElementsByTagName("body")[0].appendChild(gb_window);
		GB_loaded  = true;
	}
	document.getElementById("GB_frameholder").innerHTML = '<iframe onload="GB_doneload()" id="GB_frame" src="'+url+'"></iframe>';
	if (url.match(/libtree/)) {
		var btnhtml = '<span class="floatright"><input type="button" value="Use Libraries" onClick="document.getElementById(\'GB_frame\').contentWindow.setlib()" /> ';
		btnhtml += '<span class="pointer" onclick="GB_hide()">[X]</span>&nbsp;</span>Select Libraries<div class="clear"></div>';
		document.getElementById("GB_caption").innerHTML = btnhtml;
	} else {
		document.getElementById("GB_caption").innerHTML = '<span class="floatright">[X]</span>'+caption;
		document.getElementById("GB_caption").onclick = GB_hide;
	}
	document.getElementById("GB_window").style.display = "block";
	document.getElementById("GB_overlay").style.display = "block";
	document.getElementById("GB_loading").style.display = "block";
	
	var de = document.documentElement;
	var w = self.innerWidth || (de&&de.clientWidth) || document.body.clientWidth;
	var h = self.innerHeight || (de&&de.clientHeight) || document.body.clientHeight;
	document.getElementById("GB_window").style.width = width + "px";
	document.getElementById("GB_window").style.height = (h-30) + "px";
	document.getElementById("GB_window").style.left = ((w - width)/2)+"px";
	document.getElementById("GB_frame").style.height = (h - 30 -34)+"px";
}
function GB_doneload() {
	document.getElementById("GB_loading").style.display = "none";
}
function GB_hide() {
	document.getElementById("GB_window").style.display = "none";
	document.getElementById("GB_overlay").style.display = "none";
}

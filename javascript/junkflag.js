//IMathAS (c) 2010 David Lippman
//Flag toggles for junk library items

function toggleJunkFlagcolor(libitemid,tagged) {
	var imgchg = document.getElementById("tag"+libitemid);
	if (tagged==1) {
		imgchg.src = staticroot+"/img/flagfilled.gif";
	} else {
		imgchg.src = staticroot+"/img/flagempty.gif";
	}
}

function toggleJunkFlag(libitemid) {
	var tochg = document.getElementById("tag"+libitemid);
	
	if (tochg.src.match("filled")) {
		submitJunkFlag(libitemid,0);
	} else {
		submitJunkFlag(libitemid,1);
	}
	return false;
}

function submitJunkFlag(libitemid,tagged) { 
  url = JunkFlagsaveurl + '?libitemid='+libitemid+'&flag='+tagged;
  if (window.XMLHttpRequest) { 
    req = new XMLHttpRequest(); 
  } else if (window.ActiveXObject) { 
    req = new ActiveXObject("Microsoft.XMLHTTP"); 
  } 
  if (typeof req != 'undefined') { 
    req.onreadystatechange = function() {submitJunkFlagDone(url, libitemid, tagged);}; 
    req.open("GET", url, true); 
    req.send(""); 
  } 
}  

function submitJunkFlagDone(url, libitemid, tagged) { 
  if (req.readyState == 4) { // only if req is "loaded" 
    if (req.status == 200) { // only if "OK" 
	    if (req.responseText=='OK') {
		    toggleJunkFlagcolor(libitemid, tagged);
	    } else {
		    alert(req.responseText);
		    alert("Oops, error toggling the flag");
	    }
    } else { 
	   alert(" Couldn't save changes:\n"+ req.status + "\n" +req.statusText); 
    } 
  } 
}

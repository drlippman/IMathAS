//IMathAS (c) 2007 David Lippman
//Flag toggles for discussion forum thread list

function togglecolor(threadid,tagged) {
	var trchg = document.getElementById("tr"+threadid);
	var imgchg = document.getElementById("tag"+threadid);
	if (tagged==1) {
		$(trchg).addClass("tagged");
		imgchg.src = staticroot+"/img/flagfilled.gif";
	} else {
		$(trchg).removeClass("tagged");
		imgchg.src = staticroot+"/img/flagempty.gif";
	}
}

function toggletagged(threadid) {
	var trchg = document.getElementById("tr"+threadid);
	if ($(trchg).hasClass("tagged")) {
		submitTagged(threadid,0);
	} else {
		submitTagged(threadid,1);
	}
	return false;
}

function chgtagfilter() {
	var tagfilter = document.getElementById("tagfilter").value;
	window.location = tagfilterurl+'&tagfilter='+tagfilter;
}
function chgfilter() {
	var ffilter = document.getElementById("ffilter").value;
	window.location = tagfilterurl+'&ffilter='+ffilter;
}
function submitTagged(thread,tagged) {
  url = AHAHsaveurl + '&threadid='+thread+'&tagged='+tagged;
  if (window.XMLHttpRequest) {
    req = new XMLHttpRequest();
  } else if (window.ActiveXObject) {
    req = new ActiveXObject("Microsoft.XMLHTTP");
  }
  if (typeof req != 'undefined') {
    req.onreadystatechange = function() {ahahDone(url, thread, tagged);};
    req.open("GET", url, true);
    req.send("");
  }
}

function ahahDone(url, threadid, tagged) {
  if (req.readyState == 4) { // only if req is "loaded"
    if (req.status == 200) { // only if "OK"
	    if (req.responseText=='OK') {
		    togglecolor(threadid, tagged);
	    } else {
		    alert(req.responseText);
		    alert("Oops, error toggling the tag");
	    }
    } else {
	   alert(" Couldn't save changes:\n"+ req.status + "\n" +req.statusText);
    }
  }
}

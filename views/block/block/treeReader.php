<?php
use app\components\AppUtility;
$imasroot = AppUtility::getURLFromHome('block', 'block/tree-reader?cid='.$courseId.'&folder='.$params['folder'].'&recordbookmark=" + id');
$imasroot1 = AppUtility::getHomeURL();
$pageinhead = '<script type="text/javascript">function toggle(id) {
	node = document.getElementById(id);
	button = document.getElementById("b"+id);
	if (node.className.match("show")) {
		node.className = node.className.replace(/show/,"hide");
		button.innerHTML = "+";
	} else {
		node.className = node.className.replace(/hide/,"show");
		button.innerHTML = "-";
	}
}
function resizeiframe() {
	var windowheight = document.documentElement.clientHeight;
	var theframe = document.getElementById("readerframe");
	var framepos = findPos(theframe);
	var height =  (windowheight - framepos[1] - 15);
	theframe.style.height =height + "px";
}

function recordlasttreeview(id) {
	var url = "'.$imasroot.' ";
	basicahah(url, "bmrecout");
}
var treereadernavstate = 1;
function toggletreereadernav() {
	if (treereadernavstate==1) {
		document.getElementById("leftcontent").style.width = "20px";
		document.getElementById("leftcontenttext").style.display = "none";
		document.getElementById("centercontent").style.marginLeft = "30px";
		document.getElementById("navtoggle").src= document.getElementById("navtoggle").src.replace(/collapse/,"expand");
	} else {
		document.getElementById("leftcontent").style.width = "250px";
		document.getElementById("leftcontenttext").style.display = "";
		document.getElementById("centercontent").style.marginLeft = "260px";
		document.getElementById("navtoggle").src= document.getElementById("navtoggle").src.replace(/expand/,"collapse");
	}
	resizeiframe();
	treereadernavstate = (treereadernavstate+1)%2;
}
function updateTRunans(aid, status) {
	var urlbase = "'.$imasroot1.'";
	if (status==0) {
		document.getElementByI1d("aimg"+aid).src = urlbase+"/img/q_fullbox.gif";
	} else if (status==1) {
		document.getElementById("aimg"+aid).src = urlbase+"/img/q_halfbox.gif";
	} else {
		document.getElementById("aimg"+aid).src = urlbase+"/img/q_emptybox.gif";
	}
}
addLoadEvent(resizeiframe);
</script>';
var original = null;
var wikihistory = null;
var userinfo = null;

var curcontent = null;
var curversion = 0;
var contentdiv = null;
var showrev = 0;
function seehistory(n) { //+ older, - newer
 	if (n>0 && curversion==wikihistory.length-1) {
 		return false;
 	} else if (n<0 && curversion==0) {
 		return false;
 	}
 	if (n==1) {
 		curversion++;	
 		curcontent = applydiff(curcontent,curversion);
 		username = userinfo[wikihistory[curversion].u];
 		time = wikihistory[curversion].t;
 	} else {
 		curversion += n;
 		curcontent = jumptoversion(curversion);
 		username = userinfo[wikihistory[curversion].u];
 		time = wikihistory[curversion].t;
 	}
 	if (showrev==1) {
		contentdiv.innerHTML = colorrevisions(curcontent,curversion);
	} else {
		contentdiv.innerHTML = curcontent.join(' ');
	}
 	if (curversion==0) {
 		document.getElementById("newer").className = "grayout";
 		document.getElementById("last").className = "grayout";
 		document.getElementById("revrevert").style.display = "none";
 	} else {
 		document.getElementById("newer").className = "";
 		document.getElementById("last").className = "";
 		document.getElementById("revrevert").style.display = "";
 		document.getElementById("revrevert").href = reverturl+"&torev="+wikihistory[curversion].id+"&disprev="+(wikihistory.length-curversion);
 	}
 	if (curversion==wikihistory.length-1) {
 		document.getElementById("older").className = "grayout";
 		document.getElementById("first").className = "grayout";
 	} else {
 		document.getElementById("older").className = "";
 		document.getElementById("first").className = "";
 	}
 	html = 'Revision '+(wikihistory.length - curversion)+'.  Edited by '+username+' on '+time;
 	document.getElementById("revisioninfo").innerHTML = html;
 	wikirendermath();
 	return false;
}

function jumpto(n) {  //1: oldest, 0: most recent
	if (n==0) {
		seehistory(-1*curversion);	
	} else {
		seehistory(wikihistory.length - curversion-1);	
	}
}
/*
function applydifftostr(current,ver) {
	return applydiff(current.split(/\s/),ver).join(' ');
}
*/
function applydiff(current, ver) {
	//0: insert, 1: delete, 2 replace.  
	//
	var diff = wikihistory[ver].c;
	for (var i=diff.length-1; i>=0; i--) {
		if (diff[i][0]==2) { //replace
			current = current.slice(0,diff[i][1]).concat(diff[i][3]).concat(current.slice(diff[i][1]+diff[i][2]));
			//current.splice(diff[i][1], diff[i][2], diff[i][3]);
		} else if (diff[i][0]==0) {//insert
			current = current.slice(0,diff[i][1]).concat(diff[i][2]).concat(current.slice(diff[i][1]));
			//current.splice(diff[i][1], 0, diff[i][2]);
		} else if (diff[i][0]==1) {//delete
			current.splice(diff[i][1], diff[i][2]);
		}
	}
	return current;
}

function jumptoversion(ver) {
	var cur = original.slice();
	if (ver==0) { 
		return cur;
	}
	for (var i=1; i<=ver; i++) {
		cur = applydiff(cur,i);
	}
	return cur;
}
function showrevisions() {
	//toggle
	showrev = 1 - showrev;
	if (showrev==1) {
		contentdiv.innerHTML = colorrevisions(curcontent,curversion);
		document.getElementById("showrev").value = "Hide Changes";
	} else {
		contentdiv.innerHTML = curcontent.join(' ');
		document.getElementById("showrev").value = "Show Changes";
	}
	wikirendermath();
}

function colorrevisions(content,ver) {
	if (ver==wikihistory.length-1) {return content.join(' ');};
	current = content.slice();
	var diff = wikihistory[ver+1].c;
	for (var i=diff.length-1; i>=0; i--) {
		deled = null;  insed = null;
		if (diff[i][0]==2) {
			deled = diff[i][3].join(' ');
			insed = current.splice(diff[i][1], diff[i][2]).join(' ');
		} else if (diff[i][0]==0) {
			deled = diff[i][2].join(' ');
		} else if (diff[i][0]==1) {
			insed = current.splice(diff[i][1], diff[i][2]).join(' ');
		}
		if (insed != null) {
			if (insed.match(/<p>/)) {
				insed = insed.split('<p>').join('<p><ins>');
			}
			if (insed.match(/<\/p>/)) {
				insed = insed.split('</p>').join('</ins></p>');
			}	
		}
		if (deled != null) {
			if (deled.match(/<p>/)) {
				deled = deled.split('<p>').join('<p><del>');
			}
			if (deled.match(/<\/p>/)) {
				deled = deled.split('</p>').join('</del></p>');
			}	
		}
		if (diff[i][0]==2) { //replace
			current.splice(diff[i][1], 0, "<del>"+deled+"</del><ins>"+insed+"</ins>");
		} else if (diff[i][0]==0) {//insert
			current.splice(diff[i][1], 0, "<del>"+deled+"</del>");
		} else if (diff[i][0]==1) {//delete
			current.splice(diff[i][1], 0, "<ins>"+insed+"</ins>");
		}
	}
	return current.join(' ');
}

//addLoadEvent(initwiki);

function initwiki() {
	curcontent = original.slice();
	contentdiv = document.getElementById("wikicontent");
	contentdiv.innerHTML = original.join(' ');
	wikirendermath();
}

function wikirendermath() {
	if (usingASCIIMath) {
		rendermathnode(contentdiv);
	}
	if (usingASCIISvg) {
		setTimeout("drawPics()",100);
	}
}	

var req = null;
function initrevisionview() {
	document.getElementById("prevrev").innerHTML = "Loading revision history....";
	
	if (window.XMLHttpRequest) { 
		req = new XMLHttpRequest(); 
	} else if (window.ActiveXObject) { 
		req = new ActiveXObject("Microsoft.XMLHTTP"); 
	} 
	if (typeof req != 'undefined') { 
		req.onreadystatechange = function() {revloaded();}; 
		req.open("GET", AHAHrevurl, true); 
		req.send(""); 
	}	
}
function revloaded() {
	if (req.readyState == 4) { // only if req is "loaded" 
		if (req.status == 200) { // only if "OK" 
			var respobj = eval('('+req.responseText+')');
			original = respobj.o;
			curcontent = original.slice();
			userinfo = respobj.u;
			wikihistory = respobj.h;
			contentdiv = document.getElementById("wikicontent");
			contentdiv.innerHTML = original.join(' ');
			wikirendermath();
			document.getElementById("prevrev").innerHTML="";
			document.getElementById("revcontrol").style.display = "";
		} else { 
			document.getElementById("prevrev").innerHTML=" Load Error:\n"+ req.status + "\n" +req.statusText; 
		} 
	} 
}


/*
* IMathAS eqn helper (c) 2009 David Lippman
*/
var eecurel = null;
var eeinit = false;
var eeselstore = null;
var eeactivetab = 1;
var eeclosetimer = 0;
var ddclosetimer = 0;
var cureedd = null;

function eeinsert(ins) {
	el = document.getElementById(eecurel);
	if (el.setSelectionRange){
		var len = el.selectionEnd - el.selectionStart;
	} else if (document.selection && document.selection.createRange) {
        	el.focus();
		//var range = document.selection.createRange();
		var range = eeselstore;
		var len = range.text.length;
		//alert(range.text);
		//alert(eeselstore.text);
	}
	posshift = 0;
    	if (ins=='(') {
    		insb = '(';
		insa = ')';
		posshift = 1;
	} else if (ins=='pow') {
		if (len > 0) {
			insb = '(';
			insa = ')^';
		} else {
			insb = '';
			insa = '^';
		}
	} else if (ins=='e') {
		if (len > 0) {
			insb = 'e^(';
			insa = ')';
		} else {
			insb = '';
			insa = 'e^()';
			posshift = 1;
		}
	} else if (ins=='frac') {
		if (len > 0) {
			insb = '(';
			insa = ')/';
		} else {
			insb = '';
			insa = '/';
		}
	} else {
		if (ins.substr(0,3)=='sym') {
			insb = '';
			insa = ins.substr(3);
		} else {
			if (ins.substr(ins.length-2)=='^2') {
				insb = '('+ins.substr(0,ins.length-2) + '(';
				insa = '))^2';
				posshift = 4;
			} else {
				insb = ins + '(';
				insa = ')';
				posshift = 1;
			}
		}
	}
    if (el.setSelectionRange){
    	var pos = el.selectionEnd + insa.length + insb.length;
        el.value = el.value.substring(0,el.selectionStart) + insb + el.value.substring(el.selectionStart,el.selectionEnd) + insa + el.value.substring(el.selectionEnd,el.value.length);
	el.focus();
	//move inside empty function
	if (len==0 && posshift>0) {
		pos -= posshift;
	}
	el.setSelectionRange(pos,pos);
    }
    else if (document.selection && document.selection.createRange) {
        //el.focus();
        //var range = document.selection.createRange();
        range.text = insb + range.text + insa;
	if (len==0 && posshift>0) {
		range.move("character",-1*posshift);
	}
	range.select();
    }
   eeselstore = null;
   unhideee(0);
}
function eetoggleactive(n) {
	for (var i=1; i<=3; i++) {
		if (n==i) {
			document.getElementById("ee"+i).style.display = "";
			document.getElementById("eetab"+i).style.backgroundColor = "#cfc";
		} else {
			document.getElementById("ee"+i).style.display = "none";
			document.getElementById("eetab"+i).style.backgroundColor = "#fff";
		}
	}
	eeactivetab = n;
	el = document.getElementById(eecurel);
	if (el.setSelectionRange){
		el.focus();
	    }
	    else if (document.selection && document.selection.createRange) {
		eeselstore.select();
	    }
	unhideee(0);
}

function showeeselstore(eln) {
	if (eeselstore==null && document.selection && document.selection.createRange) {
		document.getElementById(eln).focus();
		eeselstore = document.selection.createRange();
	}
}
function showee(eln) {
	el = document.getElementById(eln);
	if (el.setSelectionRange){
		var len = el.selectionEnd - el.selectionStart;
	} else if (document.selection && document.selection.createRange) {
        	var range = eeselstore;
		var len = range.text.length;
	}
	var ee = document.getElementById('ee');
	if (eeinit == false) {
		els = ee.getElementsByTagName("td");
		for (var i=0; i<els.length; i++) {
			els[i].onmouseover = eecellhighlight;
			els[i].onmouseout = eecellunhighlight;
			els[i].onmousedown = eecellhighlightdown;
			els[i].onmouseup = eecellhighlight;
		}
		eeinit = true;
	}
	if (eetype==1) {
		document.getElementById("eeadvanced").style.display = "none";
		document.getElementById("eesimple").style.display = "";
	} else {
		document.getElementById("eeadvanced").style.display = "";
		document.getElementById("eesimple").style.display = "none";
	}
	if (eln != eecurel) {
		eecurel = eln;
		var offset = jQuery(el).offset();
		ee.style.left = offset.left + "px";
		ee.style.top = (offset.top + el.offsetHeight) + "px";
		ee.style.display = "block";
	} else {
		ee.style.display = "none";
		eecurel = null;
	}
	unhideee(0);
	//el.focus();
	if (el.setSelectionRange){
		el.focus();
		//el.setSelectionRange(el.selectionStart,el.selectionEnd);
	 } else if (document.selection && document.selection.createRange) {
		range.select();
	 }
}


function unhideee(t) {
	eecancelclosetimer();
}
function hideee(t) {
	if (eecurel!=null) {
		eeclosetimer = window.setTimeout(reallyhideee,250);
	}
}
function hideeedd() {
	ddclosetimer = window.setTimeout(function() {cureedd = null; document.getElementById("eedd").style.display = "none";},250);
}
function reallyhideee() {
	var ee = document.getElementById('ee');
	ee.style.display = "none";
	eecurel = null;
}
function eecancelclosetimer() {
	if (eeclosetimer) {
		window.clearTimeout(eeclosetimer);
		eeclosetimer = null;
	}
}

function eecellhighlight() {
	this.style.background = "#ccf";
}
function eecellunhighlight() {
	if (this.id=="eetab"+eeactivetab) {
		this.style.background = "#cfc";
	} else {
		this.style.background = "#fff";
	}
}
function eecellhighlightdown() {
	this.style.background = "#99f";
	if (eeselstore==null && document.selection && document.selection.createRange) {
		document.getElementById(eecurel).focus();
		eeselstore = document.selection.createRange();
	}
}
function showeedd(eln) {
	if (ddclosetimer && eln!=eecurel) { // && eln!=cureedd
		window.clearTimeout(ddclosetimer);
		ddclosetimer = null;
	}
	if (eln!=eecurel) {
		var dd = document.getElementById("eedd");
		var el = document.getElementById(eln);
		var offset = jQuery(el).offset();
		//dd.style.left = p[0] + "px";
		//dd.style.top = (p[1] + el.offsetHeight) + "px";
		//dd.style.width = el.offsetWidth + "px";
		dd.style.left = (offset.left+el.offsetWidth) + "px";
		dd.style.top = offset.top + "px";
		dd.style.height = (el.offsetHeight-2) + "px";
		dd.style.lineHeight = el.offsetHeight + "px";
		dd.style.width = "10px";
		dd.style.display = "block";
	}
	cureedd = eln;
}
function updateeeddpos() {
	if (!cureedd) {return;}
	var dd = document.getElementById("eedd");
	var el = document.getElementById(cureedd);
	var offset = jQuery(el).offset();
	dd.style.left = (offset.left+el.offsetWidth) + "px";
	dd.style.top = offset.top + "px";
}

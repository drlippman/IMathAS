/*
* IMathAS equation entry hint popover
*/

var ehcurel = null;
var ehclosetimer = 0;
var ehddclosetimer = 0;
var curehdd = null;
var eecurel = null; //so will be defined if eqnhelper isn't being used

//show expanded eqn tip
function showeh(eln) {
	if (eecurel!=null) {
		return;
	}
	unhideeh(0);

	var el = document.getElementById(eln);
	var eh = document.getElementById('eh');
	if (eln != ehcurel) {
		ehcurel = eln;
		var p = findPos(el);
		eh.style.display = "block";
		//eh.style.left = p[0] + "px";
		//eh.style.top = (p[1]-eh.offsetHeight) + "px"; // + el.offsetHeight
		eh.style.left = p[0] + "px";
		eh.style.top = (p[1] + el.offsetHeight) + "px";
	} else {
		eh.style.display = "none";
		ehcurel = null;
	}

	el.focus();
}

function reshrinkeh(eln) {
	if (eecurel!=null) {
		return;
	}
	if (eln==ehcurel) {
		document.getElementById("ehdd").style.display = "block";
		document.getElementById('eh').style.display = "none";
		ehcurel = null;
		curehdd = eln;
		unhideeh(0);
	}
}

function unhideeh(t) {
	ehcancelclosetimer();
}
function hideeh(t) {
	if (ehcurel==null) {
		ehddclosetimer = window.setTimeout(function() {curehdd = null; document.getElementById("ehdd").style.display = "none";},250);
	} else {
		ehclosetimer = window.setTimeout(reallyhideeh,250);
	}
}
function reallyhideeh() {
	var eh = document.getElementById('eh');
	eh.style.display = "none";
	ehcurel = null;
}
function ehcancelclosetimer() {
	if (ehclosetimer) {
		window.clearTimeout(ehclosetimer);
		ehclosetimer = null;
	}
}
function hideAllEhTips() {
	if (ehclosetimer) {
		window.clearTimeout(ehclosetimer);
		ehclosetimer = null;
	}
	if (ehddclosetimer) {
		window.clearTimeout(ehddclosetimer);
		ehddclosetimer = null;
	}
	curehdd = null;
	document.getElementById("ehdd").style.display = "none";
	document.getElementById('eh').style.display = "none";
}
//show eqn tip dropdown (shorttipe)
function showehdd(eln,shorttip,qn) {
	if (eecurel!=null && eecurel==eln) {
		return;
	}
	if (document.getElementById("tips"+qn)==null) {
		return;
	}
	//if new el, no need to timeout, since moving it
	if (ehddclosetimer && eln!=curehdd) {
		window.clearTimeout(ehddclosetimer);
		ehddclosetimer = null;
	}
	if (eln!=ehcurel) { //if new el, change position and content
		var ehdd = document.getElementById("ehdd");
		var el = document.getElementById(eln);
		var p = findPos(el);
		document.getElementById("ehddtext").innerHTML = shorttip;
		document.getElementById("eh").innerHTML = document.getElementById("tips"+qn).innerHTML;
		ehdd.style.display = "block";
		//ehdd.style.left = p[0] + "px";
		//ehdd.style.top = (p[1] - ehdd.offsetHeight) + "px";
		ehdd.style.left = p[0] + "px";
		ehdd.style.top = (p[1] + el.offsetHeight) + "px";
		//ehdd.style.top = (p[1] + el.offsetHeight) + "px";
		//ehdd.style.width = el.offsetWidth + "px";

	}
	curehdd = eln;
}

function updateehpos() {
	if (!curehdd && !ehcurel) return;
	var eh = document.getElementById("eh");
	var ehdd = document.getElementById("ehdd");
	var el = document.getElementById(curehdd || ehcurel);
	var p = findPos(el);
	eh.style.left = p[0] + "px";
	eh.style.top = (p[1] + el.offsetHeight) + "px";
	ehdd.style.left = p[0] + "px";
	ehdd.style.top = (p[1] + el.offsetHeight) + "px";
}

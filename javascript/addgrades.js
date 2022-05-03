/*******************************************************

AutoSuggest - a javascript automatic text input completion component
Copyright (C) 2005 Joe Kepley, The Sling & Rock Design Group, Inc.

This library is free software; you can redistribute it and/or
modify it under the terms of the GNU Lesser General Public
License as published by the Free Software Foundation; either
version 2.1 of the License, or (at your option) any later version.

This library is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public
License along with this library; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*******************************************************

Please send any useful modifications or improvements via
email to joekepley at yahoo (dot) com

*******************************************************/

function AutoSuggest(elem, suggestions)
{

	//The 'me' variable allow you to access the AutoSuggest object
	//from the elem's event handlers defined below.
	var me = this;

	//A reference to the element we're binding the list to.
	this.elem = elem;

	this.suggestions = suggestions;

	//Arrow to store a subset of eligible suggestions that match the user's input
	this.eligible = new Array();

	//The text input by the user.
	this.inputText = null;

	//A pointer to the index of the highlighted eligible item. -1 means nothing highlighted.
	this.highlighted = -1;

	//A div to use to create the dropdown.
	this.div = document.getElementById("autosuggest");


	//Do you want to remember what keycode means what? Me neither.
	var TAB = 9;
	var ESC = 27;
	var KEYUP = 38;
	var KEYDN = 40;
	var ENTER = 13;


	//The browsers' own autocomplete feature can be problematic, since it will
	//be making suggestions from the users' past input.
	//Setting this attribute should turn it off.
	elem.setAttribute("autocomplete","off");

	//We need to be able to reference the elem by id. If it doesn't have an id, set one.
	if(!elem.id)
	{
		var id = "autosuggest" + idCounter;
		idCounter++;

		elem.id = id;
	}


	/********************************************************
	onkeydown event handler for the input elem.
	Tab key = use the highlighted suggestion, if there is one.
	Esc key = get rid of the autosuggest dropdown
	Up/down arrows = Move the highlight up and down in the suggestions.
	********************************************************/
	elem.onkeydown = function(ev)
	{
		var key = me.getKeyCode(ev);

		switch(key)
		{
			case TAB:
			me.useSuggestion("tab");
			break;

			case ENTER:
			me.useSuggestion("enter");
			return false;
			break;

			case ESC:
			me.hideDiv();
			break;

			case KEYUP:
			if (me.highlighted > 0)
			{
				me.highlighted--;
			}
			me.changeHighlight(key);
			break;

			case KEYDN:
			if (me.highlighted < (me.eligible.length - 1))
			{
				me.highlighted++;
			}
			me.changeHighlight(key);
			break;
		}
	};

	/********************************************************
	onkeyup handler for the elem
	If the text is of sufficient length, and has been changed,
	then display a list of eligible suggestions.
	********************************************************/
	elem.onkeyup = function(ev)
	{
		var key = me.getKeyCode(ev);
		switch(key)
		{
		//The control keys were already handled by onkeydown, so do nothing.
		case TAB:
		case ESC:
		case KEYUP:
		case KEYDN:
			return;
		default:

			if (this.value.length > 0) //this.value != me.inputText &&
			{
				me.inputText = this.value;
				me.getEligible();
				if (me.eligible.length>0) {
					me.highlighted = 0;
				} else {
					me.highlighted = -1;
				}
				me.createDiv();
				me.positionDiv();
				me.showDiv();
			}
			else
			{
				me.hideDiv();
				if (this.value.length==0) {
					me.inputText = '';
				}
			}
		}
	};
	elem.onblur = function(ev) {
		setTimeout(me.hideDiv,100);
	}



	/********************************************************
	Insert the highlighted suggestion into the input box, and
	remove the suggestion dropdown.
	********************************************************/
	this.useSuggestion = function(how)
	{
		if (this.highlighted > -1)
		{
			this.elem.value = this.eligible[this.highlighted];
			this.hideDiv();
			//It's impossible to cancel the Tab key's default behavior.
			//So this undoes it by moving the focus back to our field right after
			//the event completes.
			//setTimeout("document.getElementById('" + this.elem.id + "').focus()",0);
			var namev = this.elem.value;
			for (var i=1;i<trs.length;i++) {
				var tds = trs[i].getElementsByTagName("td");
				if (tds[0].innerHTML.match(namev) || tds[0].innerHTML==namev) {
					document.getElementById("qascore").value = tds[tds.length-3].getElementsByTagName("input")[0].value;
					if (window.tinymce) {
						tinymce.get("qafeedback").setContent(tinymce.get(tds[tds.length-2].getElementsByTagName("input")[0].name).getContent());
					} else {
						document.getElementById("qafeedback").value = tds[tds.length-2].getElementsByTagName("textarea")[0].value;
					}
				}
			}
			if (how != "tab") {
				document.getElementById("qascore").focus();
				document.getElementById("qascore").select();
			}
		} else {
			this.elem.value = '';
			this.hideDiv();
		}
	};

	/********************************************************
	Display the dropdown. Pretty straightforward.
	********************************************************/
	this.showDiv = function()
	{
		me.div.style.display = 'block';
	};

	/********************************************************
	Hide the dropdown and clear any highlight.
	********************************************************/
	this.hideDiv = function()
	{
		me.div.style.display = 'none';
		me.highlighted = -1;
	};

	/********************************************************
	Modify the HTML in the dropdown to move the highlight.
	********************************************************/
	this.changeHighlight = function()
	{
		var lis = this.div.getElementsByTagName('LI');
		for (i in lis)
		{
			var li = lis[i];
			if (this.highlighted == i)
			{
				li.className = "selected";
			}
			else
			{
				li.className = "";
			}

		}
	};

	/********************************************************
	Position the dropdown div below the input text field.
	********************************************************/
	this.positionDiv = function()
	{
		var el = this.elem;
		var pos = findPos(el);
		pos[1] += el.offsetHeight;

		this.div.style.left = pos[0] + 'px';
		this.div.style.top = pos[1] + 'px';
	};

	/********************************************************
	Build the HTML for the dropdown div
	********************************************************/
	this.createDiv = function()
	{
		var ul = document.createElement('ul');

		//Create an array of LI's for the words.
		for (i in this.eligible)
		{
			var word = this.eligible[i];

			var li = document.createElement('li');
			var a = document.createElement('a');
			a.href="#";//javascript:false;";
			a.onclick= function() {return false;}
			a.innerHTML = word;
			li.appendChild(a);

			if (me.highlighted == i)
			{
				li.className = "selected";
			}

			ul.appendChild(li);
		}

		this.div.replaceChild(ul,this.div.childNodes[0]);


		/********************************************************
		mouseover handler for the dropdown ul
		move the highlighted suggestion with the mouse
		********************************************************/
		ul.onmouseover = function(ev)
		{
			//Walk up from target until you find the LI.
			var target = me.getEventSource(ev);
			while (target.parentNode && target.tagName.toUpperCase() != 'LI')
			{
				target = target.parentNode;
			}

			var lis = me.div.getElementsByTagName('LI');


			for (i in lis)
			{
				var li = lis[i];
				if(li == target)
				{
					me.highlighted = i;
					break;
				}
			}
			me.changeHighlight();
		};

		/********************************************************
		click handler for the dropdown ul
		insert the clicked suggestion into the input
		********************************************************/
		ul.onclick = function(ev)
		{
			me.useSuggestion("click");
			me.hideDiv();
			me.cancelEvent(ev);
			return false;
		};

		this.div.className="suggestion_list";
		this.div.style.position = 'absolute';

	};

	/********************************************************
	determine which of the suggestions matches the input
	********************************************************/
	this.getEligible = function()
	{
		this.eligible = new Array();
		var added = ',';
		if (this.inputText.indexOf(" ") == -1) {
			var bndreg = new RegExp("\\b"+this.inputText.toLowerCase());
			for (i in this.suggestions)
			{
				var suggestion = this.suggestions[i];
				if(suggestion.toLowerCase().match(bndreg))
				{
					this.eligible[this.eligible.length]=suggestion;
					added += i+',';
				}
			}
		}
		for (i in this.suggestions)
		{
			var suggestion = this.suggestions[i];

			if(suggestion.toLowerCase().indexOf(this.inputText.toLowerCase()) >-1 && added.indexOf(','+i+',')<0)
			{
				this.eligible[this.eligible.length]=suggestion;
			}
		}
	};

	/********************************************************
	Helper function to determine the keycode pressed in a
	browser-independent manner.
	********************************************************/
	this.getKeyCode = function(ev)
	{
		if(ev)			//Moz
		{
			return ev.keyCode;
		}
		if(window.event)	//IE
		{
			return window.event.keyCode;
		}
	};

	/********************************************************
	Helper function to determine the event source element in a
	browser-independent manner.
	********************************************************/
	this.getEventSource = function(ev)
	{
		if(ev)			//Moz
		{
			return ev.target;
		}

		if(window.event)	//IE
		{
			return window.event.srcElement;
		}
	};

	/********************************************************
	Helper function to cancel an event in a
	browser-independent manner.
	(Returning false helps too).
	********************************************************/
	this.cancelEvent = function(ev)
	{
		if(ev)			//Moz
		{
			ev.preventDefault();
			ev.stopPropagation();
		}
		if(window.event)	//IE
		{
			window.event.returnValue = false;
		}
	}
}

//counter to help create unique ID's
var idCounter = 0;

/**  END AUTOSUGGEST CODE **/
//Remaining code is (c) 2010 David Lippman, IMathAS project
var names = [];
function initsuggest() {
	var table = document.getElementById("myTable");
	var tbod = table.getElementsByTagName("tbody")[0];
	trs = tbod.getElementsByTagName("tr");
	for (var i=1;i<trs.length;i++) {
		names.push(trs[i].getElementsByTagName("td")[0].innerText);
	}
	new AutoSuggest(document.getElementById("qaname"),names);
}
addLoadEvent(initsuggest);
$(function() {
	$("#qafeedback").on("keydown", function(event) {
		var code = event.keyCode || event.which;
		if (code === 9) { //tab
			event.preventDefault();
			addsuggest();
			return false;
		}
	});
})
function addsuggest() {
	var namev = document.getElementById("qaname").value;
	var scorev = document.getElementById("qascore").value;
	if (window.tinymce) {
		var feedbv = tinymce.get("qafeedback").getContent();
	} else {
		var feedbv = document.getElementById("qafeedback").value;
	}
	if (namev != '') {
		var found = false;
		for (var i=1;i<trs.length;i++) {
			var tds = trs[i].getElementsByTagName("td");
			if (tds[0].innerText==namev) {
				found = true;
				tds[tds.length-3].getElementsByTagName("input")[0].value = scorev;
				if (window.tinymce) {
					tinymce.get(tds[tds.length-2].getElementsByTagName("input")[0].name).setContent(feedbv);
				} else {
					tds[tds.length-2].getElementsByTagName("textarea")[0].value = feedbv;
				}
			}
		}
		if (!found) {
			for (var i=1;i<trs.length;i++) {
				var tds = trs[i].getElementsByTagName("td");
				if (tds[0].innerText.match(namev)) {
					tds[tds.length-3].getElementsByTagName("input")[0].value = scorev;
					if (window.tinymce) {
						tinymce.get(tds[tds.length-2].getElementsByTagName("input")[0].name).setContent(feedbv);
					} else {
						tds[tds.length-2].getElementsByTagName("textarea")[0].value = feedbv;
					}
				}
			}
		}
	}
	document.getElementById("qaname").value = '';
	document.getElementById("qascore").value = '';

	if (window.tinymce) {
		tinymce.get("qafeedback").setContent("");
	} else {
		document.getElementById("qafeedback").value = '';
	}
	document.getElementById("qaname").focus();
}

function qaonenter(e,field) {
	if (window.event) {
		var key = window.event.keyCode;
	} else if (e.which) {
		var key = e.which;
	}
	if (key==13) {
		document.getElementById("qafeedback").focus();
		return false;
	} else {
		return true;
	}
}

function onenter(e,field) {
	if (window.event) {
		var key = window.event.keyCode;
	} else if (e.which) {
		var key = e.which;
	}
	if (key==13) {
		var i;
                for (i = 0; i < field.form.elements.length; i++)
                   if (field == field.form.elements[i])
                       break;
              i = (i + 2) % field.form.elements.length;
              field.form.elements[i].focus();
              return false;
	} else {
		return true;
	}
}
function onarrow(e,field) {
	if (window.event) {
		var key = window.event.keyCode;
	} else if (e.which) {
		var key = e.which;
	}

	if (key==40 || key==38) {
		var i;
                for (i = 0; i < field.form.elements.length; i++)
                   if (field == field.form.elements[i])
                       break;

	      if (key==38) {
		      i = i-2;
		      if (i<0) { i=0;}
	      } else {
		      i = (i + 2) % field.form.elements.length;
	      }
	      if (field.form.elements[i].type=='text') {
		      field.form.elements[i].focus();
	      }
              return false;
	} else {
		return true;
	}
}
function togglefeedback(btn) {
	var form = document.getElementById("mainform");
	for (i = 0; i < form.elements.length; i++) {
		el = form.elements[i];
		if (el.type == 'textarea') {
			if (el.rows==1) {
				el.rows = 4;
			} else {
				el.rows = 1;
			}
		}
	}
	if (btn.value=="Expand Feedback Boxes") {
		btn.value = "Shrink Feedback Boxes";
	} else{
		btn.value = "Expand Feedback Boxes";
	}
}

function doonblur(value) {
	if (value=='') {return ('');}
	if (value.match(/^\s*X\s*$/i)) {return 'X';}
	value = value.replace(/\b0+(\d+)/g, '$1');
	try {
		return (eval(mathjs(value)));
	} catch (e) {
		return '';
	}
}

//w:  0: score, 1: feedback
function sendtoall(w,type) {
	var form=document.getElementById("mainform");
	if (w==1) {
		if (window.tinymce) { tinymce.triggerSave(); }
		var pastfb, editor;
		var toall = $("input[name=toallfeedback]").val();
	}
	if (type==2) {
		if (w==0 && document.getElementById("toallgrade").value == "" && !confirm("Clear all scores?")) {
			return;
		}
		if (w==1 && (toall == "" || toall == "<p></p>") && !confirm("Clear all feedback?")) {
			return;
		}
	}
	for (var e = 0; e<form.elements.length; e++) {
		 var el = form.elements[e];
		 if (w==1) {
			if (el.name.match(/feedback/) && el.name!="toallfeedback") {
				pastfb = $(el).val();
				if (window.tinymce) {
					editor = tinymce.get(el.name);
					if (type==1) { editor.setContent(toall + pastfb);}
					else if (type==0) { editor.setContent(pastfb+toall);}
					else if (type==2) { editor.setContent(toall);}
				} else {
					if (type==1) { el.value = toall + el.value;}
					else if (type==0) { el.value = el.value+toall;}
					else if (type==2) { el.value = toall;}
				}

			}
		 } else if (w==0) {
			if (document.getElementById("toallgrade").value.match(/\d/)) {
				if (el.type=="text" && el.id.match(/score/)) {
					if (type==0) { el.value = doonblur(el.value+'+'+document.getElementById("toallgrade").value);}
					else if (type==1) { el.value = doonblur(el.value+'*'+document.getElementById("toallgrade").value);}
					else if (type==2) { el.value = document.getElementById("toallgrade").value;}
				}
			} else if (document.getElementById("toallgrade").value == "") {
				if (el.type=="text" && el.id.match(/score/) && type==2) {
					el.value = '';
				}
			}
		 }
	}
	if (window.tinymce) {
		tinymce.get("toallfeedback").setContent("");
	} else {
		document.getElementById("toallfeedback").value = '';
	}
	document.getElementById("toallgrade").value = '';
}

var quickaddshowing = false;
function togglequickadd(el) {
	if (!quickaddshowing) {
		document.getElementById("quickadd").style.display = "";
		$(el).html(_("Hide Quicksearch Entry"));
		quickaddshowing = true
	} else {
		document.getElementById("quickadd").style.display = "none";
		$(el).html(_("Show Quicksearch Entry"));
		quickaddshowing = false;
	}
}

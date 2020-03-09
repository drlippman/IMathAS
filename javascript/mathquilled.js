var mqarea;

var mqmousebase;
$(function() {

  $(window).load(function() {
        var hasTouch = 'ontouchstart' in document.documentElement;
        mqarea = $('.mathquill-editable');
	$('.mathquill-rendered-math').mathquill('redraw');
  	mqarea.find('input,textarea').focus();
  	$('input,textarea').attr('autocapitalize', 'off');


  //	if (!hasTouch) {
  		$('#mqee td.mqeebtn').bind('mouseover mouseup', function() {
			if (!$(this).hasClass("mqeeactive")) {
				$(this).addClass("mqeehighlight");
			}
		}).bind('mousedown', function () {
			$(this).addClass("mqeeclick");
		}).bind('mouseout', function () {
			$(this).removeClass("mqeehighlight");
			$(this).removeClass("mqeeclick");
		}).bind('mouseup', function () {
			$(this).removeClass("mqeeclick");
		}).bind('click',mqeeinsert);
		$('#mqeeclosebutton').bind('click',hideee);
  		$('#mqeetopbar').mousedown(function(evt) {
  			if (evt.preventDefault) {evt.preventDefault()};
  			$("body").addClass("unselectable");
			mqmousebase = {left:evt.pageX, top: evt.pageY};
			$("body").bind('mousemove',mqeemousemove);
			$("body").mouseup(function(event) {
				var p = $('#mqee').offset();
				lasteepos.left = p.left;
				lasteepos.top = p.top;
				$("body").unbind('mousemove',mqeemousemove);
				$("body").removeClass("unselectable");
				$(this).unbind(event);
			});
		});
  	//} else {
  		$('#mqee td.mqeebtn').bind('touchstart', function (e) {
  			if (e.preventDefault) {e.preventDefault()};
			$(this).addClass("mqeeclick");
		}).bind('touchend', function (e) {
			if (e.preventDefault) {e.preventDefault()};
			$(this).delay(500).removeClass("mqeeclick");
		}).bind('touchend', mqeeinsert);
		$('#mqeeclosebutton').bind('touchend',function(e) {if (e.preventDefault) {e.preventDefault()}; hideee()});
		$('#mqeetopbar').bind('touchstart', function(evt) {
			if (evt.preventDefault) {evt.preventDefault()};
			var touch = evt.originalEvent.changedTouches[0] || evt.originalEvent.touches[0];
			mqmousebase = {left:touch.pageX, top: touch.pageY};
			$("body").addClass("unselectable");
			$("body").bind('touchmove',mqeetouchmove);
			$("body").bind('touchend', function(event) {
				var p = $('#mqee').offset();
				lasteepos.left = p.left;
				lasteepos.top = p.top;
				$("body").unbind('touchmove',mqeetouchmove);
				$("body").removeClass("unselectable");
				$(this).unbind(event);
			});
		});
  //	}
  	/* prevents stuff outside of mathquill from getting clicked, but also prevents
  	   selecting text in touch mode
  		$('#mqeeinsides').bind("touchstart", function (evt) {
  			if (evt.preventDefault) {evt.preventDefault()};
  			if (evt.stopPropagation) {evt.stopPropagation()};
		});*/

  });

  //tabs
//from http://www.sohtanaka.com/web-design/simple-tabs-w-css-jquery/
$(document).ready(function() {

	//When page loads...
	$(".tab_content").hide(); //Hide all content

	//On Click Event
	$("ul.tabs li").click(function() {

		$("ul.tabs li").removeClass("active"); //Remove any "active" class
		$(this).addClass("active"); //Add "active" class to selected tab
		$(".tab_content").hide(); //Hide all tab content

		var activeTab = $(this).find("a").attr("href"); //Find the href attribute value to identify the active tab + content
		$(activeTab).show(); //Fade in the active ID content
		return false;
	});

	//$("td.mqeebtn span.mathquill-rendered-math").mathquill().mathquill("redraw");
});
});

function mqPrepTabs(type,extras) {   //type: 0 basic, 1 advanced.  extras = 'interval' or 'ineq'
	$(".tab_content").hide();
	$("ul.tabs li").removeClass("active").each(function(index,el) {
		if (index==0) {
			if (true || type==0) {
				$(el).addClass("active").show();
				var activeTab = $(el).find("a").attr("href"); //Find the href attribute value to identify the active tab + content
				$(activeTab).show();
			} else {
				$(el).hide();
			}
		} else if (index==1) {
			if (false && type==1) {
				$(el).addClass("active").show();
				var activeTab = $(el).find("a").attr("href"); //Find the href attribute value to identify the active tab + content
				$(activeTab).show();
			} else {
				$(el).hide();
			}
		} else if (index==2) {
			if (extras=='int') {
				$(el).show();
			} else {
				$(el).hide();
			}
		} else if (index==3) {
			if (extras=='ineq') {
				$(el).show();
			} else {
				$(el).hide();
			}
		} else {
			if (type==1) {
				$(el).show();
			} else {
				$(el).hide();
			}
		}
	});

}
function mqeemousemove(evt) {
	$('#mqee').css('left', (evt.pageX - mqmousebase.left) + lasteepos.left)
	  .css('top', (evt.pageY - mqmousebase.top) + lasteepos.top);
	if (evt.preventDefault) {evt.preventDefault()};
	if (evt.stopPropagation) {evt.stopPropagation()};
	return false;
}
function mqeetouchmove(evt) {
	var touch = evt.originalEvent.changedTouches[0] || evt.originalEvent.touches[0];

	$('#mqee').css('left', (touch.pageX - mqmousebase.left) + lasteepos.left)
	  .css('top', (touch.pageY - mqmousebase.top) + lasteepos.top);
	if (evt.preventDefault) {evt.preventDefault()};
	if (evt.stopPropagation) {evt.stopPropagation()};

	return false;
}

function mqeeinsert(e) {
	if (e.preventDefault) {e.preventDefault()};
	if (e.stopPropagation) {e.stopPropagation()};
	var t = $(this).attr("btntext");
        var type = $(this).attr("btntype");
        t = t.replace('\\\\','\\');
        if (type==0) {
		mqarea.mathquill('cmd', t).find('input,textarea').focus();
	} else if (type==1) {
		mqarea.mathquill('write', t).find('input,textarea').focus();
	} else if (type==2) {
		mqarea.mathquill('writesimpfunc', t).find('input,textarea').focus();
	} else if (type==3) {
		mqarea.mathquill('writefunc', t).find('input,textarea').focus();
	} else if (type==4) {
		mqarea.mathquill('writeint', t).find('input,textarea').focus();
	} else if (type==5) {
		mqarea.mathquill('movecursor', t).find('input,textarea').focus();
	} else if (type==6) {
		mqarea.mathquill('writebracket', t).find('input,textarea').focus();
	} else if (type==7) {
		mqarea.mathquill('writefrac', t).find('input,textarea').focus();
	}
	return false;
}

var mqeeddclosetimer = null;
var cureedd = null;
var lasteepos = null;
function showeedd(eln,type,extras) {
	if (mqeeddclosetimer) {
		window.clearTimeout(mqeeddclosetimer);
		mqeeddclosetimer = null;
	}
	hideee();
	cureedd = {"id":eln, "type":type, "extras": extras};
	var dd = $("#mqeedd");
	var el = $("#"+eln);

	var p = el.offset();
	p.left += el.outerWidth();
	dd.css('left',p.left+"px").css('top',p.top+"px").height(el.outerHeight()-2).show();
}
function updateeeddpos() {
	if (!cureedd) {return;}
	var dd = $("#mqeedd");
	var el = $("#"+cureedd.id);
	var p = el.offset();
	p.left += el.outerWidth();
	dd.css('left',p.left+"px").css('top',p.top+"px").height(el.outerHeight()-2).show();
}
function hideeedd() {
	mqeeddclosetimer = setTimeout(function() {$("#mqeedd").hide();}, 250);
}

function mqeetoggleactive(n) {
	for (var i=1; i<=5; i++) {
		if (n==i) {
			$('#mqeetab'+i).addClass('mqeeactive').removeClass("mqeehighlight");
			document.getElementById("mqee"+i).style.display = "";
		} else {
			document.getElementById("mqee"+i).style.display = "none";
			$('#mqeetab'+i).removeClass('mqeeactive');
		}
	}
	mqeeactivetab = n;
	mqarea.find('input,textarea').focus();
}

function showee() {
	mqPrepTabs(cureedd.type - 3,cureedd.extras);
	var mqee = $("#mqee");
	if (!lasteepos) {

		lasteepos = {
			left: ($(window).width() - mqee.outerWidth())/2,
			top: $(window).scrollTop() + ((window.innerHeight ? window.innerHeight : $(window).height()) - mqee.outerHeight())/2,
			scroll: $(window).scrollTop()
		};
	} else {
		var scrollchg = $(window).scrollTop() - lasteepos.scroll;
		lasteepos.top = lasteepos.top + scrollchg;
		lasteepos.scroll = $(window).scrollTop();
	}
	mqee.css('left',lasteepos.left).css('top',lasteepos.top).show();
	//console.log(AMtoMQ($("#"+cureedd.id).val()));
	mqarea.mathquill('latex', AMtoMQ($("#"+cureedd.id).val()));
	mqarea.find('input,textarea').focus();

}
function hideee() {
	$("#mqee").hide();
}
function savemathquill() {
	var AM = MQtoAM(mqarea.mathquill('latex'));
	$("#"+cureedd.id).val(AM);
	if (AM!='') {
		$("#"+cureedd.id).siblings("input[value=spec]").prop("checked",true);
	}
	var btn = $("#pbtn"+cureedd.id.substr(2));
	if (btn) {
		btn.trigger("click");
	}
	hideee();
}



/*
* basic eqn helper for number and interval
*/
var eebasiccurel = null;
var eebasicinit = false;
var eebasicselstore = null;
var eebasicclosetimer = 0;
var ddbasicclosetimer = 0;
var cureebasicdd = null;
var eebasictype = 0;

function eebasicinsert(ins) {
	el = document.getElementById(eebasiccurel);
	if (el.setSelectionRange){
		var len = el.selectionEnd - el.selectionStart;
	} else if (document.selection && document.selection.createRange) {
        	el.focus();
		//var range = document.selection.createRange();
		var range = eebasicselstore;
		var len = range.text.length;
		//alert(range.text);
		//alert(eebasicselstore.text);
	}
	posshift = 0;
    	if (ins=='(') {
    		insb = '(';
		insa = ')';
		posshift = 1;
	} else if (ins.substr(0,3)=='sym') {
		insb = '';
		insa = ins.substr(3);
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
   eebasicselstore = null;
   //showeebasicdd(eebasiccurel,eebasictype);
   //unhideebasice(0);
}

function showeebasicselstore(eln) {
	if (eebasicselstore==null && document.selection && document.selection.createRange) {
		document.getElementById(eln).focus();
		eebasicselstore = document.selection.createRange();
	}
}
function showeebasic(eln) {
	el = document.getElementById(eln);
	if (el.setSelectionRange){
		var len = el.selectionEnd - el.selectionStart;
	} else if (document.selection && document.selection.createRange) {
        	var range = eebasicselstore;
		var len = range.text.length;
	}
	var eebasic = document.getElementById('eebasic');
	if (eebasicinit == false) {
		els = eebasic.getElementsByTagName("td");
		for (var i=0; i<els.length; i++) {
			els[i].onmouseover = eebasiccellhighlight;
			els[i].onmouseout = eebasiccellunhighlight;
			els[i].onmousedown = eebasiccellhighlightdown;
			els[i].onmouseup = eebasiccellhighlight;
		}
		eebasicinit = true;
	}
	if (eebasictype==1) {
		document.getElementById("eenumber").style.display = "none";
		document.getElementById("eeinterval").style.display = "";
	} else {
		document.getElementById("eenumber").style.display = "";
		document.getElementById("eeinterval").style.display = "none";
	}
	if (eln != eebasiccurel) {
		eebasiccurel = eln;
		var offset = jQuery(el).offset();
		eebasic.style.top = (offset.top + el.offsetHeight) + "px";
		eebasic.style.display = "block";
		if (eebasic.offsetWidth<el.offsetWidth) {
			eebasic.style.left = (offset.left + el.offsetWidth + 10 - eebasic.offsetWidth )+"px";
		} else {
			eebasic.style.left = offset.left + "px";
		}

	} else {
		eebasic.style.display = "none";
		eebasiccurel = null;
	}
	unhideebasice(0);
	//el.focus();
	if (el.setSelectionRange){
		el.focus();
		//el.setSelectionRange(el.selectionStart,el.selectionEnd);
	 } else if (document.selection && document.selection.createRange) {
		range.select();
	 }
}


function unhideebasice(t) {
	eebasiccancelclosetimer();
}
function hideebasice(t) {
	if (eebasiccurel!=null) {
		eebasicclosetimer = window.setTimeout(reallyhideebasice,250);
	}
}
function hideebasicedd() {
	ddbasicclosetimer = window.setTimeout(function() {cureebasicdd = null; document.getElementById("eebasicdd").style.display = "none";},250);
}
function reallyhideebasice() {
	var eebasic = document.getElementById('eebasic');
	eebasic.style.display = "none";
	eebasiccurel = null;
}
function eebasiccancelclosetimer() {
	if (eebasicclosetimer) {
		window.clearTimeout(eebasicclosetimer);
		eebasicclosetimer = null;
	}
}

function eebasiccellhighlight() {
	this.style.background = "#ccf";
}
function eebasiccellunhighlight() {
	this.style.background = "#fff";
}
function eebasiccellhighlightdown() {
	this.style.background = "#99f";
	if (eebasicselstore==null && document.selection && document.selection.createRange) {
		document.getElementById(eebasiccurel).focus();
		eebasicselstore = document.selection.createRange();
	}
}
function showeebasicdd(eln,type) {
	eebasictype = type;
	if (ddbasicclosetimer) { // && eln!=eebasiccurel) { // && eln!=cureebasicdd
		window.clearTimeout(ddbasicclosetimer);
		ddbasicclosetimer = null;
	}
	//if (eln!=eebasiccurel) {
		var dd = document.getElementById("eebasicdd");
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
	//}
	cureebasicdd = eln;
}

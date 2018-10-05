//var imasrubrics = new Array();
//imasrubrics[2] = {'type':1,'data':[['Opening and Closing','',20],['Includes units','',30],['Includes values','',30],['Other considerations','Ex: public perception',20]]};
//imasrubrics[5] = {'type':2,'data':[['Good','Includes everything'],['Good, but missing details',''],['Nice use of descriptors','']]};
var hasTouch;
var rubricbase, lastrubricpos;

function imasrubric_getpttot(rubricid) {
	var pttot = 0;
	for (var i=0;i<imasrubrics[rubricid].data.length; i++) {
		if (imasrubrics[rubricid].type==0 || imasrubrics[rubricid].type==1 ) {  //score breakdown or score and feedback
			pttot += imasrubrics[rubricid].data[i][2];
		} else if (imasrubrics[rubricid].type==3 || imasrubrics[rubricid].type==4) {
			pttot = Math.max(pttot, imasrubrics[rubricid].data[i][2]);
		}
	}
	pttot = Math.round(10*pttot)/10;
	return pttot;
}
function imasrubric_show(rubricid,pointsposs,scoreboxid,feedbackid,qn,width) {
	hasTouch = 'ontouchstart' in document.documentElement;
	if (GB_loaded == false) {
		//var gb_overlay = document.createElement("div");
		//gb_overlay.id = "GB_overlay";
		//gb_overlay.onclick = GB_hide;
		//document.getElementsByTagName("body")[0].appendChild(gb_overlay);
		var gb_window = document.createElement("div");
		gb_window.id = "GB_window";
		gb_window.innerHTML = '<div id="GB_caption"></div><div id="GB_loading">Loading...</div><div id="GB_frameholder"></div>';
		document.getElementsByTagName("body")[0].appendChild(gb_window);
		GB_loaded  = true;
	}
	document.getElementById("GB_caption").innerHTML = '<span style="float:right;"><span class="pointer clickable" onclick="GB_hide()">[X]</span></span> Rubric';
	//document.getElementById("GB_caption").onclick = GB_hide;
	document.getElementById("GB_caption").style.cursor = "move";
	document.getElementById("GB_window").style.display = "block";
	document.getElementById("GB_window").style.position = "absolute";
	document.getElementById("GB_window").style.height = "auto";
	document.getElementById("GB_window").style.margin = "0";
	//document.getElementById("GB_overlay").style.display = "block";
	document.getElementById("GB_loading").style.display = "block";
	if (!hasTouch) {
		$('#GB_caption').mousedown(function(evt) {
			rubricbase = {left:evt.pageX, top: evt.pageY};
			$("body").bind('mousemove',rubricmousemove);
			$("body").mouseup(function(event) {
				var p = $('#GB_window').offset();
				lastrubricpos.left = p.left;
				lastrubricpos.top = p.top;
				$("body").unbind('mousemove',rubricmousemove);
				$(this).unbind(event);
			});
		});
	} else {
		$('#GB_caption').bind('touchstart', function(evt) {
			var touch = evt.originalEvent.changedTouches[0] || evt.originalEvent.touches[0];
			rubricbase = {left:touch.pageX, top: touch.pageY};
			$("body").bind('touchmove',rubricmousemove);
			$("body").bind('touchend', function(event) {
				var p = $('#GB_window').offset();
				lastrubricpos.left = p.left;
				lastrubricpos.top = p.top;
				$("body").unbind('touchmove',rubricmousemove);
				$(this).unbind(event);
			});
		});

	}
	var html = "<div style='margin: 10px;'><form id='imasrubricform'><table><tbody>";
	if (imasrubrics[rubricid].type<2) {
		html += '<tr><td></td><td colspan="3"><a href="#" onclick="imasrubric_fullcredit();return false;">'+_('Full Credit')+'</a></td></tr>';
	}
	var pttot = imasrubric_getpttot(rubricid);

	for (var i=0;i<imasrubrics[rubricid].data.length; i++) {
		if (imasrubrics[rubricid].type==0 || imasrubrics[rubricid].type==1 ) {  //score breakdown or score and feedback
			html += "<tr><td>"+imasrubrics[rubricid].data[i][0];
			if (imasrubrics[rubricid].data[i][1]!="") {
				html += "<br/><i>"+imasrubrics[rubricid].data[i][1]+"</i>";
			}
			totpts = Math.round( 100*Math.round(pointsposs*imasrubrics[rubricid].data[i][2])/pttot )/100;
			html += '</td><td width="10%"><input type="radio" name="rubricgrp'+i+'" value="'+totpts+'"/> '+totpts+'</td>';
			//if (totpts==2) {
			//	html += '</td><td width="10%"><input type="radio" name="rubricgrp'+i+'" value="1"/> 1</td>';
			//}
			html += '<td width="10%"><input type="radio" name="rubricgrp'+i+'" value="0" checked="checked"/> 0</td>';
			html += '<td width="10%" style="white-space:nowrap;"><input type="radio" name="rubricgrp'+i+'" id="rubricgrpother'+i+'" value="-1"/> Other: <input onfocus="document.getElementById(\'rubricgrpother'+i+'\').checked=true" type="number" step="0.1" min="0" max="'+totpts+'" size="3" id="rubricother'+i+'" value=""/></td></tr>';
		} else if (imasrubrics[rubricid].type==2) { //just feedback
			html += "<tr><td>"+imasrubrics[rubricid].data[i][0];
			if (imasrubrics[rubricid].data[i][1]!="") {
				html += "<br/><i>"+imasrubrics[rubricid].data[i][1]+"</i>";
			}
			html += '</td><td><input type="checkbox" id="rubricchk'+i+'" value="1"/></td></tr>';
		} else if (imasrubrics[rubricid].type==3 || imasrubrics[rubricid].type==4) { //score total
			html += "<tr><td>"+imasrubrics[rubricid].data[i][0];
			if (imasrubrics[rubricid].data[i][1]!="") {
				html += "<br/><i>"+imasrubrics[rubricid].data[i][1]+"</i>";
			}
			totpts = Math.round( 100*Math.round(pointsposs*imasrubrics[rubricid].data[i][2])/pttot )/100;
			html += '</td><td width="10%"><input type="radio" name="rubricgrp" value="'+i+'"/> '+totpts+'</td></tr>';
		}
	}
	html += '</tbody></table><br/><input type="button" value="Record" onclick="imasrubric_record(\''+rubricid+'\',\''+scoreboxid+'\',\''+feedbackid+'\',\''+qn+'\','+pointsposs+',false)" />';
	html += '<input type="button" value="Clear Existing and Record" onclick="imasrubric_record(\''+rubricid+'\',\''+scoreboxid+'\',\''+feedbackid+'\',\''+qn+'\','+pointsposs+',true)" /></form></div>';


	document.getElementById("GB_frameholder").innerHTML = html;
	document.getElementById("GB_loading").style.display = "none";

	var de = document.documentElement;
	var w = self.innerWidth || (de&&de.clientWidth) || document.body.clientWidth;
	var h = self.innerHeight || (de&&de.clientHeight) || document.body.clientHeight;
	document.getElementById("GB_window").style.width = width + "px";
	if ($("#GB_window").outerHeight() > h - 30) {
		document.getElementById("GB_window").style.height = (h-30) + "px";
	}
	document.getElementById("GB_window").style.left = ((w - width)/2)+"px";
	lastrubricpos = {
		left: ($(window).width() - $("#GB_window").outerWidth())/2,
		top: $(window).scrollTop() + ((window.innerHeight ? window.innerHeight : $(window).height()) - $("#GB_window").outerHeight())/2,
		scroll: $(window).scrollTop()
	};
	document.getElementById("GB_window").style.top = lastrubricpos.top+"px";

	//document.getElementById("GB_frame").style.height = (h - 30 -34)+"px";
}

function rubricmousemove(evt) {
	$('#GB_window').css('left', (evt.pageX - rubricbase.left) + lastrubricpos.left)
	.css('top', (evt.pageY - rubricbase.top) + lastrubricpos.top);
	return false;
}
function rubrictouchmove(evt) {
	var touch = evt.originalEvent.changedTouches[0] || evt.originalEvent.touches[0];

	$('#GB_window').css('left', (touch.pageX - rubricbase.left) + lastrubricpos.left)
	.css('top', (touch.pageY - rubricbase.top) + lastrubricpos.top);
	evt.preventDefault();

	return false;
}

function imasrubric_record(rubricid,scoreboxid,feedbackid,qn,pointsposs,clearexisting) {
	var feedback = '';
	if (qn != null && qn != 'null' && qn != '0' && !feedbackid.match(/^fb-/)) {
		feedback += '#'+qn+': ';
	}
	if (window.tinymce) {
		tinymce.triggerSave();
		var pastfb = $("input[name="+feedbackid+"]").val();
	} else {
		var pastfb = $("textarea[name="+feedbackid+"]").val();
	}
	
	var pttot = imasrubric_getpttot(rubricid);
	if (imasrubrics[rubricid].type==0 || imasrubrics[rubricid].type==1 ) {  //score breakdown and feedback
		var score = 0;
		for (var i=0;i<imasrubrics[rubricid].data.length; i++) {
			val = getRadioValue('rubricgrp'+i);
			if (val==-1) {
				thisscore = 1*document.getElementById('rubricother'+i).value;
			} else {
				thisscore = 1*val;
			}
			score += thisscore;
			totpts = Math.round(pointsposs*imasrubrics[rubricid].data[i][2])/pttot;

			feedback += '<li>'+imasrubrics[rubricid].data[i][0]+': '+thisscore+'/'+totpts+'.</li>';
		}
		if (feedback != '') {
			feedback = '<ul class=nomark>'+feedback+'</ul>';
		} 
		document.getElementById(scoreboxid).value = score;
		if (imasrubrics[rubricid].type==1) {
			if (clearexisting) {
				if (window.tinymce) {
					tinymce.get(feedbackid).setContent(feedback);
				} else {
					document.getElementById(feedbackid).value = feedback;
				}
			} else {
				if (window.tinymce) {
					tinymce.get(feedbackid).setContent(pastfb + feedback);
				} else {
					document.getElementById(feedbackid).value = pastfb + feedback;
				}
			}
		}
	} else if (imasrubrics[rubricid].type==2) { //just feedback
		for (var i=0;i<imasrubrics[rubricid].data.length; i++) {
			if (document.getElementById('rubricchk'+i).checked) {
				feedback += '<li>'+imasrubrics[rubricid].data[i][0]+'.</li>';
			}
		}
		if (feedback != '') {
			feedback = '<ul class=nomark>'+feedback+'</ul>';
		} 
		if (clearexisting) {
			if (window.tinymce) {
				tinymce.get(feedbackid).setContent(feedback);
			} else {
				document.getElementById(feedbackid).value = feedback;
			}
		} else {
			if (window.tinymce) {
				tinymce.get(feedbackid).setContent(pastfb + feedback);
			} else {
				document.getElementById(feedbackid).value = pastfb + feedback;
			}
		}
	} else if (imasrubrics[rubricid].type==3 || imasrubrics[rubricid].type==4 ) {  //score total and feedback
		loc = getRadioValue('rubricgrp');
		totpts = Math.round(pointsposs*imasrubrics[rubricid].data[loc][2])/pttot;
		feedback += imasrubrics[rubricid].data[loc][0];//+': '+totpts+'/'+pointsposs+'. ';
		document.getElementById(scoreboxid).value = totpts;
		if (imasrubrics[rubricid].type==3) {
			if (clearexisting) {
				if (window.tinymce) {
					tinymce.get(feedbackid).setContent(feedback);
				} else {
					document.getElementById(feedbackid).value = feedback;
				}
			} else {
				if (window.tinymce) {
					tinymce.get(feedbackid).setContent(pastfb + feedback);
				} else {
					document.getElementById(feedbackid).value = pastfb + feedback;
				}
			}
		}
	}
	
	if (p = feedbackid.match(/^fb-(\d+)/)) {
		revealfb(p[1]);
	}
	GB_hide();

}

function imasrubric_chgtype() {
	var val = document.getElementById("rubtype").value;
	els = document.getElementsByTagName("input");
	for (i in els) {
		if (els[i].className=='rubricpoints') {
			if (val==2) {
				els[i].style.display = 'none';
				document.getElementById("pointsheader").style.display = 'none';
				$("#breakdowninstr").hide();
				$("#scoretotalinstr").hide();
			} else {
				els[i].style.display = '';
				document.getElementById("pointsheader").style.display = '';
				if (val==0 || val==1) {
					$("#breakdowninstr").show();
					$("#scoretotalinstr").hide();
				} else if (val==3||val==4) {
					$("#breakdowninstr").hide();
					$("#scoretotalinstr").show();
				}
			}
		}
	}
}

function imasrubric_fullcredit() {
	$("#imasrubricform tr").find("input:radio:first").attr('checked',true);
}
function getRadioValue(theRadioGroup) {
	var els = document.getElementsByName(theRadioGroup);
	for (var i = 0; i <  els.length; i++) {
		if ( els[i].checked) {
			return els[i].value;
		}
	}
}

function quickgrade(qn,type,prefix,todo,vals) {
	if (type==0) { //all
		for (var i=0;i<todo;i++) {
			document.getElementById(prefix+qn+"-"+i).value = vals[i];
		}
	} else {  //select
		for (var i=0;i<todo.length;i++) {
			document.getElementById(prefix+qn+"-"+todo[i]).value = vals[todo[i]];
		}
	}
}
function quicksetscore(el,score) {
	document.getElementById(el).value = score;
}

function markallfullscore() {
	$('.quickgrade').click();
}

function revealfb(qn) {
	$("#fb-"+qn+"-wrap").show();
	$("#fb-"+qn+"-add").hide();
	return false;
}

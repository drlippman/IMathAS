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
	var html = "<div style='margin: 10px;'><form id='imasrubricform'><table><thead>";
	if (imasrubrics[rubricid].type<2) {
		html += '<tr><th>'+_('Item')+'</th><th><a href="#" onclick="imasrubric_fullcredit();return false;">'+_('Full Credit')+'</a></th>';
		html += '<th><a href="#" onclick="imasrubric_nocredit();return false;">'+_('No Credit')+'</a></th><th>'+_('Other')+'</th></tr>';
	} else if (imasrubrics[rubricid].type<2) {
		html += '<tr><th>'+_('Feedback')+'</th><th>'+_('Select')+'</th></tr>';
	} else {
		html += '<tr><th>'+_('Feedback')+'</th><th>'+_('Points')+'</th></tr>';
	} 
	html += '</thead><tbody>';
	var pttot = imasrubric_getpttot(rubricid);

	for (var i=0;i<imasrubrics[rubricid].data.length; i++) {
		if (imasrubrics[rubricid].type==0 || imasrubrics[rubricid].type==1 ) {  //score breakdown or score and feedback
			html += "<tr scope=row role=group aria-labelledby=rr"+i+"><th><span id=rr"+i+">"+imasrubrics[rubricid].data[i][0]+"</span>";
			if (imasrubrics[rubricid].data[i][1]!="") {
				html += "<br/><i>"+imasrubrics[rubricid].data[i][1]+"</i>";
			}
			totpts = Math.round( 100*Math.round(pointsposs*imasrubrics[rubricid].data[i][2])/pttot )/100;
			html += '</th><td width="10%" style="white-space:nowrap;"><label><input type="radio" name="rubricgrp'+i+'" value="'+totpts+'"/> '+totpts+'</label></td>';
			//if (totpts==2) {
			//	html += '</td><td width="10%"><input type="radio" name="rubricgrp'+i+'" value="1"/> 1</td>';
			//}
			html += '<td width="10%"><label><input type="radio" name="rubricgrp'+i+'" value="0" checked="checked"/> 0</label></td>';
			html += '<td width="10%" style="white-space:nowrap;"><label><input type="radio" name="rubricgrp'+i+'" id="rubricgrpother'+i+'" value="-1"/> Other</label>: <input onfocus="document.getElementById(\'rubricgrpother'+i+'\').checked=true" type="number" step="0.1" min="0" max="'+totpts+'" size="3" id="rubricother'+i+'" value="" aria-label="points to assign"/></td></tr>';
		} else if (imasrubrics[rubricid].type==2) { //just feedback
			html += "<tr><th scope=row><label for=rubricchk"+i+">"+imasrubrics[rubricid].data[i][0]+"</label>";
			if (imasrubrics[rubricid].data[i][1]!="") {
				html += "<br/><i>"+imasrubrics[rubricid].data[i][1]+"</i>";
			}
			html += '</th><td><input type="checkbox" id="rubricchk'+i+'" value="1"/></td></tr>';
		} else if (imasrubrics[rubricid].type==3 || imasrubrics[rubricid].type==4) { //score total
			html += "<tr><th scope=row>"+imasrubrics[rubricid].data[i][0];
			if (imasrubrics[rubricid].data[i][1]!="") {
				html += "<br/><i>"+imasrubrics[rubricid].data[i][1]+"</i>";
			}
			totpts = Math.round( 100*Math.round(pointsposs*imasrubrics[rubricid].data[i][2])/pttot )/100;
			html += '</th><td width="10%"><label><input type="radio" name="rubricgrp" value="'+i+'"/> '+totpts+'</label></td></tr>';
		}
	}
	html += '</tbody></table><br/><input type="button" value="Record" onclick="imasrubric_record(\''+rubricid+'\',\''+scoreboxid+'\',\''+feedbackid+'\',\''+qn+'\','+pointsposs+',false)" />';
	html += '<input type="button" value="Clear Existing and Record" onclick="imasrubric_record(\''+rubricid+'\',\''+scoreboxid+'\',\''+feedbackid+'\',\''+qn+'\','+pointsposs+',true)" /></form></div>';

	GB_show(_('Rubric'), html, 800, 'content');

}

function imasrubric_record(rubricid,scoreboxid,feedbackid,qn,pointsposs,clearexisting) {
	var feedback = '';
    var qninf = '';
	if (qn !== null && qn !== 'null') {
		qninf = '#'+qn+': ';
	}
	if (window.tinymce) {
		var pastfb = tinymce.get(feedbackid).getContent();
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
			feedback = qninf + '<ul class=nomark>'+feedback+'</ul>';
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
			feedback = qninf + '<ul class=nomark>'+feedback+'</ul>';
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
		feedback += qninf + imasrubrics[rubricid].data[loc][0];//+': '+totpts+'/'+pointsposs+'. ';
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
	document.getElementById(feedbackid).dispatchEvent(new CustomEvent('input'));
	document.getElementById(scoreboxid).dispatchEvent(new CustomEvent('input'));

	if (p = feedbackid.match(/^fb-(\d+)/)) {
		revealfb(p[1]);
	}
	GB_hide();

}

function imasrubric_chgtype() {
	var val = document.getElementById("rubtype").value;
	els = document.getElementsByTagName("input");
	if (val == 2) {
		$(".rubricpoints").hide();
		$("#breakdowninstr").hide();
		$("#scoretotalinstr").hide();
	} else {
		$(".rubricpoints").show();
	}
	if (val < 2) {
		$("#pointsheader").text(_('Portion of Score'));
		$("#breakdowninstr").show();
		$("#scoretotalinstr").hide();
	} else if (val > 2) {
		$("#pointsheader").text(_('Total Score'));
		$("#breakdowninstr").hide();
		$("#scoretotalinstr").show();
	}
	if (val == 0 || val == 4) {
		$(".hfeedback").hide();
	} else {
		$(".hfeedback").show();
	}
}

function imasrubric_fullcredit() {
	$("#imasrubricform tr").each(function() {
		$(this).find("input[type=radio]").first().prop('checked',true);
	});
}
function imasrubric_nocredit() {
	$("#imasrubricform tr").each(function() {
		$(this).find("input[type=radio]").eq(1).prop('checked',true);
	});
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
function quicksetscore(el,score,clickel) {
	document.getElementById(el).value = score;
	if (clickel != null) {
		var html = " | "+_('Quick feedback: ');
		html += '<a href="#" onclick="return quicksetfb(this)">'+_('Good!')+'</a> | ';
		html += '<a href="#" onclick="return quicksetfb(this)">'+_('Great work.')+'</a> | ';
		html += '<a href="#" onclick="return quicksetfb(this)">'+_('Excellent!')+'</a> ';
		$(clickel).siblings(".quickfb").html(html);
	}
}

function quicksetfb(el) {
	var feedback = $(el).text();
	var feedbackid = $(el).closest(".review, .scoredetails").find(".fbbox").attr("id");
	if (window.tinymce) {
		tinymce.get(feedbackid).setContent(feedback);
	} else {
		document.getElementById(feedbackid).value = feedback;
	}
	return false;
}
function markallfullscore() {
	$('.quickgrade').click();
}

function revealfb(qn,dofocus) {
	$("#fb-"+qn+"-wrap").show();
	$("#fb-"+qn+"-add").hide();
	if (dofocus) {
		$("#fb-"+qn).focus();
	}
	return false;
}

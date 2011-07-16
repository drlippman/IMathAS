//var imasrubrics = new Array();
//imasrubrics[2] = {'type':1,'data':[['Opening and Closing','',20],['Includes units','',30],['Includes values','',30],['Other considerations','Ex: public perception',20]]};
//imasrubrics[5] = {'type':2,'data':[['Good','Includes everything'],['Good, but missing details',''],['Nice use of descriptors','']]};

function imasrubric_show(rubricid,pointsposs,scoreboxid,feedbackid,qn,width) {
	if (GB_loaded == false) {
		var gb_overlay = document.createElement("div");
		gb_overlay.id = "GB_overlay";
		gb_overlay.onclick = GB_hide;
		document.getElementsByTagName("body")[0].appendChild(gb_overlay);
		var gb_window = document.createElement("div");
		gb_window.id = "GB_window";
		gb_window.innerHTML = '<div id="GB_caption"></div><div id="GB_loading">Loading...</div><div id="GB_frameholder"></div>';
		document.getElementsByTagName("body")[0].appendChild(gb_window);
		GB_loaded  = true;
	}
	document.getElementById("GB_caption").innerHTML = '<span style="float:right;"><span class="pointer clickable" onclick="GB_hide()">[X]</span></span> Rubric';
	document.getElementById("GB_caption").onclick = GB_hide;
	document.getElementById("GB_window").style.display = "block";
	document.getElementById("GB_overlay").style.display = "block";
	document.getElementById("GB_loading").style.display = "block";
	
	var html = "<div style='margin: 10px;'><form id='imasrubricform'><table><tbody>";
	for (var i=0;i<imasrubrics[rubricid].data.length; i++) {
		if (imasrubrics[rubricid].type==0 || imasrubrics[rubricid].type==1 ) {  //score breakdown or score and feedback
			html += "<tr><td>"+imasrubrics[rubricid].data[i][0];
			if (imasrubrics[rubricid].data[i][1]!="") {
				html += "<br/><i>"+imasrubrics[rubricid].data[i][1]+"</i>";
			}
			totpts = Math.round(pointsposs*imasrubrics[rubricid].data[i][2])/100;
			html += '</td><td width="10%"><input type="radio" name="rubricgrp'+i+'" value="'+totpts+'"/> '+totpts+'</td>';
			if (totpts==2) {
				html += '</td><td width="10%"><input type="radio" name="rubricgrp'+i+'" value="1"/> 1</td>';
			}
			html += '<td width="10%"><input type="radio" name="rubricgrp'+i+'" value="0" checked="checked"/> 0</td>';
			html += '<td width="10%"><input type="radio" name="rubricgrp'+i+'" id="rubricgrpother'+i+'" value="-1"/> Other: <input onfocus="document.getElementById(\'rubricgrpother'+i+'\').checked=true" type="text" size="3" id="rubricother'+i+'" value=""/></td></tr>';
		} else if (imasrubrics[rubricid].type==2) { //just feedback
			html += "<tr><td>"+imasrubrics[rubricid].data[i][0];
			if (imasrubrics[rubricid].data[i][1]!="") {
				html += "<br/><i>"+imasrubrics[rubricid].data[i][1]+"</i>";
			}
			html += '</td><td><input type="checkbox" id="rubricchk'+i+'" value="1"/></td></tr>';
		} else if (imasrubrics[rubricid].type==3 || imasrubrics[rubricid].type==3) { //score total 
			html += "<tr><td>"+imasrubrics[rubricid].data[i][0];
			if (imasrubrics[rubricid].data[i][1]!="") {
				html += "<br/><i>"+imasrubrics[rubricid].data[i][1]+"</i>";
			}
			totpts = Math.round(pointsposs*imasrubrics[rubricid].data[i][2])/100;
			html += '</td><td width="10%"><input type="radio" name="rubricgrp" value="'+i+'"/> '+totpts+'</td></tr>';
		}
	}
	html += '</tbody></table><br/><input type="button" value="Record" onclick="imasrubric_record(\''+rubricid+'\',\''+scoreboxid+'\',\''+feedbackid+'\',\''+qn+'\','+pointsposs+')" /></form></div>';
	
	
	document.getElementById("GB_frameholder").innerHTML = html;
	document.getElementById("GB_loading").style.display = "none";
	
	var de = document.documentElement;
	var w = self.innerWidth || (de&&de.clientWidth) || document.body.clientWidth;
	var h = self.innerHeight || (de&&de.clientHeight) || document.body.clientHeight;
	document.getElementById("GB_window").style.width = width + "px";
	document.getElementById("GB_window").style.height = (h-30) + "px";
	document.getElementById("GB_window").style.left = ((w - width)/2)+"px";
	//document.getElementById("GB_frame").style.height = (h - 30 -34)+"px";
}

function imasrubric_record(rubricid,scoreboxid,feedbackid,qn,pointsposs) {
	var feedback = '';
	if (qn != null && qn != 'null' && qn != '0') {
		feedback += '#'+qn+': ';
	}
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
			totpts = Math.round(pointsposs*imasrubrics[rubricid].data[i][2])/100;
			
			feedback += imasrubrics[rubricid].data[i][0]+': '+thisscore+'/'+totpts+'. ';
		}
		document.getElementById(scoreboxid).value = score;
		if (imasrubrics[rubricid].type==1) {
			document.getElementById(feedbackid).value = document.getElementById(feedbackid).value + feedback;
		}
	} else if (imasrubrics[rubricid].type==2) { //just feedback
		for (var i=0;i<imasrubrics[rubricid].data.length; i++) {
			if (document.getElementById('rubricchk'+i).checked) {
				feedback += imasrubrics[rubricid].data[i][0]+'. ';
			}
		}
		document.getElementById(feedbackid).value = document.getElementById(feedbackid).value + feedback;
	} else if (imasrubrics[rubricid].type==3 || imasrubrics[rubricid].type==4 ) {  //score total and feedback
		loc = getRadioValue('rubricgrp');
		totpts = Math.round(pointsposs*imasrubrics[rubricid].data[loc][2])/100;
		feedback += imasrubrics[rubricid].data[loc][0];//+': '+totpts+'/'+pointsposs+'. ';
		document.getElementById(scoreboxid).value = totpts;
		if (imasrubrics[rubricid].type==3) {
			document.getElementById(feedbackid).value = document.getElementById(feedbackid).value + feedback;
		}
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
			} else {
				els[i].style.display = '';
				document.getElementById("pointsheader").style.display = '';
				if (val==0 || val==1) {
					document.getElementById("pointsheader").innerHTML='Percentage of score<br/>Should add to 100';
				} else if (val==3 || val==4) {
					document.getElementById("pointsheader").innerHTML='Percentage of score';
				}
			}
		}
	}	
}

function getRadioValue(theRadioGroup) {
	var els = document.getElementsByName(theRadioGroup);
	for (var i = 0; i <  els.length; i++) {
		if ( els[i].checked) {
			return els[i].value;
		}
	}
}


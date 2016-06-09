var livepoll = new function() {
	var socket;
	var isteacher;
	var curquestion = -1;
	var curqresults;
	var curstate = 0;
	var working = false;
	var LPtimer;
	var LPtimestart = 0;
	var qdata = [];
	var results = [];
	var settings {
		showqonload = true,
		showreslive = false,
		showresonclose = true,
		showansonclose = true
		};
	
	this.init = function(server, room, timestamp, sig) {
		isteacher = room.match(/teacher/);
		
		var querystr = 'room='+room+'&now='+timestamp+'&sig='+sig
		socket = io('http://'+server+':3000', {query: querystr});
		
		if (isteacher) {
			socket.on('livepoll usercount', updateUsercount);
			socket.on('livepoll qans', addResult);
			setupInstructorPanel();
		} else {
			socket.on('livepoll show', showHandler);
		}
	}
	
	this.restoreState(qn, action) {
		showHandler({qn: qn, action=act});
	}
	
	this.updateSettings() {
		settings.showqonload = $("#LPsettings-dispq").is(":checked");
		settings.showreslive = $("#LPsettings-liveres").is(":checked");
		settings.showresonclose = $("#LPsettings-resafter").is(":checked");
		settings.showansonclose = $("#LPsettings-showans").is(":checked");
	}
	
	function setupInstructorPanel() {
		$(function() {
			$("a[data-showq]").on("click", showQuestionHandler);
			$("#LPshowqchkbox").on("change", function() {
				$("#livepollqcontent").toggle($("#LPshowqchkbox").is(":checked"));
			});
			$("#LPshowrchkbox").on("change", function() {
				$("#livepollrcontent").toggle($("#LPshowrchkbox").is(":checked"));
			});
			$("#LPshowanschkbox").on("change", showAnsIfAllowed);
			$("#LPstartq").on("click", startQuestion);
			$("#LPstopq").on("click", stopQuestion);
		});	
	}
	
	function showAnsIfAllowed() {
		if ($("#LPshowanschkbox").is(":checked") && (curstate==3 || curstate==4)) {
			$(".LPcorrect").addClass("LPshowcorrect");	
			$(".LPwrong").addClass("LPshowwrong");	
			if (curstate==3 && curquestions>=0) {
				$.ajax({
					url: assesspostbackurl+'&action=livepollstopq&qn='+curquestion+'&newstate=4;
				}).done(function(data) {	
					curstate = 4;
				}
			}
		} else {
			$(".LPcorrect").removeClass("LPshowcorrect");	
			$(".LPwrong").removeClass("LPshowwrong");	
			if (curstate==4 && curquestions>=0) {
				$.ajax({
					url: assesspostbackurl+'&action=livepollstopq&qn='+curquestion+'&newstate=3;
				}).done(function(data) {	
					curstate = 3;
				}
			}
		}	
	}
	
	function showHandler(data) {
		clearInterval(LPtimer);
		LPtimestart = 0;
		//handle question show	
		var qn = data.qn;
		if (data.action=='showq') {
			$.ajax({
				url: assesspostbackurl+'&action=livepollshowq&qn='+data.qn+'&seed='+data.seed;
			}).done(function(data) {
				var parsed = preProcess(data);
				var button = '<div><span id="livepollsubmit"><button type="button" onclick="livepoll.submitQuestion('+qn+')">Submit</button></span></div>';
				$("#livepollqcontent").html(parsed.html+button);
				postProcess('livepollqcontent',parsed.code);
				curquestion = qn;
				curstate = 2;
				LPtimer = setInterval(LPtimerkeeper,1000);
			});
		} else if (data.action=='3') {
			$("#livepollsubmit").remove();
			$("#livepollqcontent").find("input").attr("disabled",true);
			curstate = 3;
			curquestion = qn;
		} else if (data.action=='4') {
			$.ajax({
				url: assesspostbackurl+'&action=livepollshowqscore&qn='+data.qn
			}).done(function(data) {
				var parsed = preProcess(data);
				$("#livepollqcontent").html(parsed.html);
				postProcess('livepollqcontent',parsed.code);
				curquestion = qn;
				curstate = 4;
			});
		} else if (data.action=='0') {
			$("#livepollqcontent").html(_('Waiting for the instructor to start a question'));
			curstate = 0;
		}
				
	}
	
	function updateUsercount(data) {
		//receive usercount data	
		$("#livepollactivestu").html(data.cnt+" student"+(data.cnt==1?'':'s'));
	}
	
	function addResult(data) {
		//add question result data
		if (!results.hasOwnProperty(curquestion)) {
			results[curquestion] = [];
		}
		results[curquestion][data.user] = data;
		updateResults();
	}
	this.loadResults = function(data) {
		results = data;
	}
	
	function updateResults() {
		var datatots = [];
		var scoredat = [];
		if (qdata[curquestion].choices.length>0) {
			for (i=0;i<qdata[curquestion].choices.length;i++) {
				datatots[i] = 0;
				scoredat[i] = 0;
			}
		}
		if (qdata[curquestion].anstypes=="choices" || qdata[curquestion].anstypes=="multans") {
			if (qdata[curquestion].anstypes=="choices") {
				var anss = qdata[curquestion].ans.split(/\s+or\s+/);
			} else if (qdata[curquestion].anstypes=="multans") { 
				var anss = qdata[curquestion].ans.split(/\s*,\s*/);
			}
			for (i=0;i<anss.length;i++) {
				scoredat[anss[i]] = 1;
			}
		}
		var ischoices = false;
		var rescnt = 0;
		var drawinitstack = [];
		for (i in results[curquestion]) {
			ischoices = (qdata[curquestion].anstypes=="choices" || qdata[curquestion].anstypes=="multans");
			if (ischoices) {
				pts = results[curquestion][i].ans.split("!$!");
				subpts = pts[2].split("|");
			} else {
				pts = results[curquestion][i].ans.split("!#!");
				subpts = [pts[0]];
			}
			if (qdata[curquestion].anstypes.match(/calc/) || qdata[curquestion].anstypes=="numfunc") {
				subpts[0] = "`"+subpts[0]+"`";
			} else if (qdata[curquestion].anstypes=="draw") {
				var la = subpts[0].replace("(","[").replace(")","]");
				la = la.split(";;")
				if  (la[0]!='') {
					la[0] = '['+la[0].replace(";","],[")+"]";	
				}
				la = '[['+la.join('],[')+']];
				drawinitstack.push(function() {
					drawla["draw"+curquestion+"-"+i] = la;
					initCanvases("draw"+curquestion+"-"+i);
				})
			}
			for (var j=0;j<subpts.length;j++) {
				if (datatots.hasOwnProperty(subpts[j])) {
					datatots[subpts[j]] += 1;
				} else {
					datatots[subpts[j]] = 1;
					scoredat[subpts[j]] = results[curquestion][i].score;
				}
			}
			rescnt++;
		}
		var out = '';
		if (qdata[curquestion].choices.length>0) {
			if (qdata[curquestion].initrdisp) {
				for (i=0;i<qdata[curquestion].randkeys.length;i++) {
					partn = qdata[curquestion].randkeys[i];
					$("#LPresval"+partn).text(datatots[partn]);
				}
			} else {
				out += "<table class=\"LPres\"><thead><tr><th>Answer</th><th>Frequency</th></tr></thead><tbody>";
				for (i=0;i<qdata[curquestion].randkeys.length;i++) {
					partn = qdata[curquestion].randkeys[i];
					out += '<tr class="';
					if (scoredat[partn]>0) {
						out += "LPcorrect";
					} else {
						out += "LPwrong";
					}
					out += '"><td>';
					out += qdata[curquestion].choices[partn];
					out += '</td><td id="LPresval'+partn+'">'+datatots[partn]+"</td></tr>";
				}
				out += "</tbody></table>";
				$("#livepollrcontent").html(out);
				qdata[curquestion].initrdisp = true;
				if (usingASCIIMath) {
					rendermathnode(document.getElementById("livepollrcontent"));
				}
				if (usingASCIISvg) {
					setTimeout("drawPics()",100);
				}
			}
		} else {
			var sortedkeys = getSortedKeys(datatots);
			out += "<table class=\"LPres\"><thead><tr><th>Answer</th><th>Frequency</th></tr></thead><tbody>";
			for (i=0;i<sortedkeys.length;i++) {
				out += '<tr class="';
				if (scoredat[sortedkeys[i]]>0) {
					out += "LPcorrect";
				} else {
					out += "LPwrong";
				}
				out += '"><td>';
				out += sortedkeys[i];
				out += "</td><td>";datatots[sortedkeys[i]]+"</td></tr>";
			}
			out += "</tbody></table>";
			$("#livepollrcontent").html(out);
			if (usingASCIIMath) {
				rendermathnode(document.getElementById("livepollrcontent"));
			}
			if (usingASCIISvg) {
				setTimeout("drawPics()",100);
			}
			for (i in drawinitstack) {
				drawinitstack[i]();
			}
		}
		
		$("#livepollrcnt").html(rescnt+" result"+(rescnt==1?"":"s")+" received.");
	}
	
	function getSortedKeys(obj) {
		var keys = []; for(var key in obj) keys.push(key);
		return keys.sort(function(a,b){return obj[b]-obj[a]});
	}
	
	function showQuestionHandler(e) {
		e.preventDefault();
		var qn = $(this).attr("data-showq")*1;
		showQuestion(qn);
		return false;
	}
	
	function showQuestion(qn, forceregen) {
		if (!working) {
			if (qn==curquestion && typeof forceregen == 'undefined') {
				//redisplaying the same q?  just ignore it
				return;
			} else if (curquestion != -1 && curstate==2) {
				//if another question is currently open, stop it
				stopQuestion();
			}
			
			$("#LPstopq").hide();
			$("#LPstartq").hide();
			
			$("#LPqnumber").text("Question "+(qn+1));
			$("#livepollqcontent").html("Loading...");
			$("#livepollrcontent").html("");
			
			if (typeof forceregen != 'undefined') {
				var regenstr = '&forceregen=true';	
			} else {
				var regenstr = '';
			}
			
			working = true;
			$.ajax({
				url: assesspostbackurl+'&action=livepollshowq&includeqinfo=true&qn='+qn+regenstr,
				dataType: "json"
			}).done(function(data) {
				curquestion = qn;
				curstate = 1;
				
				$("#LPshowqchkbox").attr("checked", settings.showqonload).trigger("change");
				$("#LPshowrchkbox").attr("checked", settings.showreslive).trigger("change");
				$("#LPshowanschkbox").attr("checked", settings.showansonclose).trigger("change");
				$("#LPshowansmsg").text("Show Answers When Closed");
				
				var parsed = preProcess(data.html);
				$("#livepollqcontent").html(parsed.html);
				postProcess('livepollqcontent',parsed.code);
				$("livepollqcontent").append('<p><a href="#" onclick="showQuestion('+qn+',true);return false;">Clear results and generate a new version of this question</a></p>');
				$("#LPstartq").show();
				
				qdata[qn] = {choices: data.choices, ans: data.ans.toString(), anstypes: data.anstypes, seed: data.seed, drawinit: data.drawinit, initrdisp:false};
				if (typeof forceregen != 'undefined') {
					results[qn] = [];	
				}
				updateResults();
			}).always(function(data) {
				working = false;
			});
		}

		return false;
	}
	
	function preProcess(resptxt) {
		var scripts = new Array();
		while(resptxt.indexOf("<script") > -1 || resptxt.indexOf("</script") > -1) {
			var s = resptxt.indexOf("<script");
			var s_e = resptxt.indexOf(">", s);
			var e = resptxt.indexOf("</script", s);
			var e_e = resptxt.indexOf(">", e);
			    
			// Add to scripts array
			scripts.push(resptxt.substring(s_e+1, e));
			// Strip from strcode
			resptxt = resptxt.substring(0, s) + resptxt.substring(e_e+1);
		}
		return {html: resptxt, code: scripts}
	}
	function postProcess(el,scripts) {
		if (usingASCIIMath) {
			rendermathnode( document.getElementById(el));
		}
		if (usingASCIISvg) {
			setTimeout("drawPics()",100);
		}
		if (usingTinymceEditor) {
			initeditor("textareas","mceEditor");
		}	
		// Loop through every script collected and eval it
		initstack.length = 0;
		for(var i=0; i<scripts.length; i++) {	    
		    try {
			    if (k=scripts[i].match(/canvases\[(\d+)\]/)) {
				if (typeof G_vmlCanvasManager != 'undefined') {
					scripts[i] = scripts[i] + 'G_vmlCanvasManager.initElement(document.getElementById("canvas'+k[1]+'"));';
				}
				scripts[i] = scripts[i] + "initCanvases("+k[1]+");";     
			    }
			    eval(scripts[i]);
		    }
		    catch(ex) {
			    // do what you want here when a script fails
		    }
		}
		for (var i=0; i<initstack.length; i++) {
		    var foo = initstack[i]();
		}
		$(window).trigger("ImathasEmbedReload");
		initcreditboxes();
	}
	
	function startQuestion() {
		var qn = curquestion;
		if (qn<0 || curstate==2 || working) { return;}
		$("#LPstartq").text("Opening Student Input...");
		working = true;
		
		$.ajax({
			url: assesspostbackurl+'&action=livepollopenq&qn='+qn+'&seed='+qdata[qn].seed;
		}).done(function(data) {
			$("#LPstartq").text("Open Student Input").hide();
			$("#LPstopq").show();
			curstate = 2;
			LPtimer = setInterval(LPtimerkeeper,1000);
			showAnsIfAllowed();
			$("#LPshowansmsg").text("Show Answers When Closed");
		}).always(function(data) {
			working = false;	
		});
	}
	function stopQuestion() {
		if (curquestion<0 || curstate!=2 || working) { return;}
		$("#LPstopq").text("Closing Student Input...");
		working = true;
		if ( $("#LPshowanschkbox").is(":checked") ) {
			var newstate = 4;
		} else {
			var newstate = 3;
		}
		$.ajax({
			url: assesspostbackurl+'&action=livepollstopq&qn='+curquestion+'&newstate='+newstate;
		}).done(function(data) {
			$("#LPstopq").text("Close Student Input").hide();
			$("#LPstartq").show();
			curstate = newstate;
			clearInterval(LPtimer);
			LPtimestart = 0;
			$("#LPshowansmsg").text("Show Answers");
			showAnsIfAllowed();
			$("#LPshowrchkbox").attr("checked", settings.showresonclose || $("#LPshowrchkbox").is(":checked")).trigger("change");
		}).always(function(data) {
			working = false;	
		});
	}
	function LPtimerkeeper() {
		var now = Date.now();
		if (LPtimestart==0) {
			LPtimestart = now;
		}
		var elapsed = Math.round((now - LPtimestart)/1000);
		var sec = elapsed%60;
		var min = (Math.floor(elapsed/60))%60;
		var hrs = Math.floor(elapsed/3600);
		var timestr = " ";
		if (hrs>0) {
			timestr += hrs+":";
		}
		if (min>9 || hrs==0) {
			timestr += min+":";
		} else if (min>0) {
			timestr += "0"+min+":";
		} else {
			timestr += "0:";
		}
		if (sec>9) {
			timestr += sec;	
		} else {
			timestr += "0"+sec;
		}
		$("#livepolltopright").html(timestr);
	}
	
	this.submitQuestion = function(qn) {
		$("#livepollsubmit").html("Saving...");
		if (typeof tinyMCE != 'undefined') {tinyMCE.triggerSave();}
		doonsubmit();	
		params = {
			embedpostback: true,
			toscore: qn,
			asidverify: document.getElementById("asidverify").value,
			disptime: document.getElementById("disptime").value,
			isreview: document.getElementById("isreview").value 
		};
		var els = new Array();
		var tags = document.getElementsByTagName("input");
		for (var i=0;i<tags.length;i++) {
			els.push(tags[i]);
		}
		var tags = document.getElementsByTagName("select");
		for (var i=0;i<tags.length;i++) {
			els.push(tags[i]);
		}
		var tags = document.getElementsByTagName("textarea");
		for (var i=0;i<tags.length;i++) {
			els.push(tags[i]);
		}
		var regex = new RegExp("^(qn|tc)("+qn+"\\b|"+(qn+1)+"\\d{3})");
		for (var i=0;i<els.length;i++) {
			if (els[i].name.match(regex)) {
				if ((els[i].type!='radio' && els[i].type!='checkbox') || els[i].checked) {
					params[els[i].name] = els[i].value;
					//params += ('&'+els[i].name+'='+encodeURIComponent(els[i].value));
				}
			}
		}
		
		$.ajax({
			type: "POST",
			url: assesspostbackurl+'&action=livepollscoreq',
			data: params
		}).done(function(data) {
			$("#livepollsubmit").html("Saved");	
		});		
		
	}
	
};




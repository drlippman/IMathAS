var livepoll = new function() {
	var socket;
	var isteacher;
	var curquestion = -1;
	var curqresults;
	var working = false;
	var qdata = [];
	var results = [];
	
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
	
	function setupInstructorPanel() {
		$(function() {
			$("a[data-showq]").on("click", showQuestion);
			$("#LPshowqchkbox").on("change", function() {
				$("#livepollqcontent").toggle($("#LPshowqchkbox").is(":checked"));
			});
			$("#LPshowrchkbox").on("change", function() {
				$("#livepollrcontent").toggle($("#LPshowrchkbox").is(":checked"));
			});
			$("#LPstartq").on("click", startQuestion);
			$("#LPstopq").on("click", stopQuestion);
		});	
	}
	function showHandler(data) {
		//handle question show	
		var qn = data.qn;
		if (data.action=='showq') {
			$.ajax({
				url: assesspostbackurl+'&action=livepollshowq&qn='+data.qn
			}).done(function(data) {
				var parsed = preProcess(data);
				var button = '<div><span id="livepollsubmit"><button type="button" onclick="livepoll.submitQuestion('+qn+')">Submit</button></span></div>';
				$("#livepollqcontent").html(parsed.html+button);
				postProcess('livepollqcontent',parsed.code);
			});
		} else if (data.action=='wait') {
			$("#livepollqcontent").html(_('Waiting for the instructor to start a question'));
		} else if (data.action=='showscore') {
			$.ajax({
				url: assesspostbackurl+'&action=livepollshowqscore&qn='+data.qn
			}).done(function(data) {
				var parsed = preProcess(data);
				$("#livepollqcontent").html(parsed.html);
				postProcess('livepollqcontent',parsed.code);
			});
		}
	}
	
	function updateUsercount(data) {
		//receive usercount data	
		$("#livepollactivestu").html(data.cnt+" connected student"+(data.cnt==1?'':'s'));
	}
	
	function addResult(data) {
		//add question result data
		if (!results.hasOwnProperty(curquestion)) {
			results[curquestion] = [];
		}
		results[curquestion][data.user] = data;
		updateResults();
	}
	
	function updateResults() {
		var datatots = [];
		var scoredat = [];
		if (qdata[curquestion].choices.length>0) {
			for (i=0;i<qdata[curquestion].choices.length;i++) {
				datatots[i] = 0;
			}
		}
		var ischoices = false;
		for (i in results[curquestion]) {
			pts = results[curquestion][i].ans.split(/\$(!|#)\$/);
			ischoices = (pts.length>1);
			subpts = pts[0].split("|");
			for (var j=0;j<subpts.length;j++) {
				if (datatots.hasOwnProperty(subpts[j])) {
					datatots[subpts[j]] += 1;
				} else {
					datatots[subpts[j]] = 1;
					scoredat[subpts[j]] = results[curquestion][i].score;
				}
			}
		}
		var out = '';
		if (qdata[curquestion].choices.length>0) {
			out += "<table><tr><td>Answer</td><td>Frequency</td></tr>";
			for (i=0;i<qdata[curquestion].choices.length;i++) {
				out += "<tr><td>";
				out += qdata[curquestion].choices[i];
				out += "</td><td>"+datatots[i]+"</td></tr>";
			}
			out += "</table>";
		} else {
			var sortedkeys = getSortedKeys(datatots);
			out += "<table><tr><td>Answer</td><td>Frequency</td></tr>";
			for (i=0;i<sortedkeys.length;i++) {
				out += "<tr><td>";
				out += sortedkeys[i];
				out += "</td><td>"+datatots[sortedkeys[i]]+"</td></tr>";
			}
			out += "</table>";
		}
		$("#livepollrcontent").html(out);
	}
	
	function getSortedKeys(obj) {
		var keys = []; for(var key in obj) keys.push(key);
		return keys.sort(function(a,b){return obj[b]-obj[a]});
	}
	
	function showQuestion(e) {
		e.preventDefault();
		if (!working) {
			$("#LPstopq").hide();
			$("#LPstartq").hide();
			var qn = $(this).attr("data-showq")*1;
			if (curquestion != -1 && qn != curquestion) {
				stopQuestion();
			}
			
			$("#LPqnumber").text("Question "+(qn+1));
			$("#livepollqcontent").html("Loading...");
			$("#livepollrcontent").html("");
			working = true;
			$.ajax({
				url: assesspostbackurl+'&action=livepollshowq&includeqinfo=true&qn='+qn,
				dataType: "json"
			}).done(function(data) {
				console.log(data);
				console.log(data.html);
				var parsed = preProcess(data.html);
				$("#livepollqcontent").html(parsed.html);
				postProcess('livepollqcontent',parsed.code);
				$("#LPstartq").show();
				curquestion = qn;
				qdata[qn] = {qtype: data.qtype, choices: data.choices};
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
		if (qn<0 || working) { return;}
		$("#LPstartq").text("Opening Student Input...");
		working = true;
		$.ajax({
			url: assesspostbackurl+'&action=livepollopenq&qn='+qn
		}).done(function(data) {
			$("#LPstartq").text("Open Student Input").hide();
			$("#LPstopq").show();
		}).always(function(data) {
			working = false;	
		});
	}
	function stopQuestion() {
		if (curquestion<0 || working) { return;}
		$("#LPstopq").text("Closing Student Input...");
		working = true;
		$.ajax({
			url: assesspostbackurl+'&action=livepollstopq&qn='+curquestion
		}).done(function(data) {
			$("#LPstopq").text("Close Student Input").hide();
			$("#LPstartq").show();
		}).always(function(data) {
			working = false;	
		});
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




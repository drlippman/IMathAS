var livepoll = new function() {
	var socket;
	var isteacher;
	var curquestion = -1;
	var curqresults;
	var working = false;
	
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
		if (data.action=='showq') {
			$.ajax({
				url: assesspostbackurl+'&action=livepollshowq&qn='+data.qn
			}).done(function(data) {
				$("#livepollqcontent").html(data);
			});
		} else if (data.action=='wait') {
			$("#livepollqcontent").html(_('Waiting for the instructor to start a question'));
		} else if (data.action=='showscore') {
			$.ajax({
				url: assesspostbackurl+'&action=livepollshowqscore&qn='+data.qn
			}).done(function(data) {
				$("#livepollqcontent").html(data);
			});
		}
	}
	
	function updateUsercount(data) {
		//receive usercount data	
		$("#livepollactivestu").html(data.cnt+" connected students");
	}
	
	function addResult(data) {
		//add question result data	
		
		updateResults();
	}
	
	function updateResults() {
		
		
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
			working = true;
			$.ajax({
				url: assesspostbackurl+'&action=livepollshowq&qn='+qn
			}).done(function(data) {
				$("#livepollqcontent").html(data);
				$("#LPstartq").show();
				curquestion = qn;
			}).always(function(data) {
				working = false;	
			});
		}

		return false;
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
	
};




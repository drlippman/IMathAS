function hidecorrect() {
	var butn = $("#hctoggle");
	if (!butn.hasClass("hchidden")) {
		butn.html(_("Show Questions with Perfect Scores"));
		butn.addClass("hchidden");
	} else {
		butn.html(_("Hide Questions with Perfect Scores"));
		butn.removeClass("hchidden");
	}
	$(".iscorrect").toggleClass("pseudohidden");
}
function hidenonzero() {
	var butn = $("#nztoggle");
	if (!butn.hasClass("nzhidden")) {
		if (!$("#hctoggle").hasClass("hchidden")) { hidecorrect();}
		butn.html(_("Show Nonzero Score Questions"));
		butn.addClass("nzhidden");
	} else {
		if ($("#hctoggle").hasClass("hchidden")) { hidecorrect();}
		butn.html(_("Hide Nonzero Score Questions"));
		butn.removeClass("nzhidden");
	}
	$(".isnonzero").toggleClass("pseudohidden");
}
function hideperfect() {
	var butn = $("#hptoggle");
	if (!butn.hasClass("hphidden")) {
		butn.html(_("Show Perfect Questions"));
		butn.addClass("hphidden");
		$(".isperfect").hide();
	} else {
		butn.html(_("Hide Perfect Questions"));
		butn.removeClass("hphidden");
		$(".isperfect").show();
	}
}
function hideNA() {
	var butn = $("#hnatoggle");
	if (!butn.hasClass("hnahidden")) {
		butn.html(_("Show Unanswered Questions"));
		butn.addClass("hnahidden");
	} else {
		butn.html(_("Hide Unanswered Questions"));
		butn.removeClass("hnahidden");
	}
	$(".notanswered").toggleClass("pseudohidden");
}
function showallans() {
	$("span[id^='ans']").removeClass("hidden");
	$(".sabtn").replaceWith("<span>Answer: </span>");
}
function previewall() {
	$('input[value="Preview"]').trigger('click').remove();
}
function allvisfullcred() {
	$(".fullcredlink").not(function() {return $(this).closest(".pseudohidden").length}).trigger("click");
}
function allvisnocred() {
	$("input[name^=ud]").not(function() {return $(this).closest(".pseudohidden").length}).val("0");
}
function preprint() {
	$("span[id^='ans']").removeClass("hidden");
	$(".sabtn").replaceWith("<span>Answer: </span>");
	$('input[value="Preview"]').trigger('click').remove();
	document.getElementById("preprint").style.display = "none";
}
function quicksave() {
	var url = $("#mainform").attr("action")+"&quick=true";
	$("#quicksavenotice").html(_("Saving...") + ' <img src="../img/updating.gif"/>');
	tinymce.triggerSave();
	$.ajax({
		url: url,
		type: "POST",
		data: $("#mainform").serialize()
	}).done(function(msg) {
		if (msg=="saved") {
			$("#quicksavenotice").html(_("Saved"));
			setTimeout(function() {$("#quicksavenotice").html("&nbsp;");}, 2000);
		} else {
			$("#quicksavenotice").html(msg);
		}
	}).fail(function(jqXHR, textStatus) {
		$("#quicksavenotice").html(textStatus);
	});
}
function hidegroupdup(el) {  //el.checked = one per group
	 var divs = document.getElementsByTagName("div");
	 for (var i=0;i<divs.length;i++) {
		 if (divs[i].className=="groupdup") {
				 if (el.checked) {
							 divs[i].style.display = "none";
				 } else { divs[i].style.display = "block"; }
		 }
		}
		var hfours = document.getElementsByTagName("h4");
	 for (var i=0;i<hfours.length;i++) {
		 if (hfours[i].className=="person") {
			hfours[i].style.display = el.checked?"none":"";
		 } else if (hfours[i].className=="group") {
			hfours[i].style.display = el.checked?"":"none";
		 }
		}
		var spans = document.getElementsByTagName("span");
	 for (var i=0;i<spans.length;i++) {
		 if (spans[i].className=="person") {
			spans[i].style.display = el.checked?"none":"";
		 } else if (spans[i].className=="group") {
			spans[i].style.display = el.checked?"":"none";
		 }
		}
}
function clearfeedback() {
	var els=document.getElementsByTagName("textarea");
	for (var i=0;i<els.length;i++) {
		if (els[i].id.match(/feedback/)) {
			els[i].value = '';
		}
	}
}
function cleardeffeedback() {
	var els=document.getElementsByTagName("textarea");
	for (var i=0;i<els.length;i++) {
		if (els[i].value==GBdeffbtext) {
			els[i].value = '';
		}
	}
}

var focuscolorlock = false;
$(function() {
	$(".review input[id*='-']").each(function(i, el) {
		var partname = $(el).attr("id").replace(/scorebox/,'');
		var idparts = partname.split("-");
		var qn = (idparts[0]*1+1)*1000+idparts[1]*1;
		$(el).on("mouseover", function () {
			if (!focuscolorlock) {$("#qn"+qn+", #tc"+qn+", #qnwrap"+qn+", #showansbtn"+partname+", #scorebox"+partname+", #ptpos"+partname).css("background-color","yellow")};
		}).on("mouseout", function () {
			if (!focuscolorlock) {$("#qn"+qn+", #tc"+qn+", #qnwrap"+qn+", #showansbtn"+partname+", #scorebox"+partname+", #ptpos"+partname).css("background-color","")};
		}).on("focus", function () {
			focuscolorlock = true;
			$("#qn"+qn+", #tc"+qn+", #qnwrap"+qn+", #showansbtn"+partname+", #scorebox"+partname+", #ptpos"+partname).css("background-color","yellow");
		}).on("blur", function () {
			focuscolorlock = false;
			$("#qn"+qn+", #tc"+qn+", #qnwrap"+qn+", #showansbtn"+partname+", #scorebox"+partname+", #ptpos"+partname).css("background-color","");
		});
	});
	$("input[id^='showansbtn']").each(function(i, el) {
		var partname = $(el).attr("id").substring(10);
		var idparts = partname.split("-");
		if (idparts.length>1) {
			var qn = (idparts[0]*1+1)*1000+idparts[1]*1;
			$(el).on("mouseover", function () {
				if (!focuscolorlock) {$("#qn"+qn+", #tc"+qn+", #qnwrap"+qn+", #showansbtn"+partname+", #scorebox"+partname+", #ptpos"+partname).css("background-color","yellow")};
			}).on("mouseout", function () {
				if (!focuscolorlock) {$("#qn"+qn+", #tc"+qn+", #qnwrap"+qn+", #showansbtn"+partname+", #scorebox"+partname+", #ptpos"+partname).css("background-color","")};
			});
		}
	});
	$("input[id^='qn'], input[id^='tc'], select[id^='qn'], div[id^='qnwrap'], span[id^='qnwrap']").each(function(i,el) {
		var qn = $(el).attr("id");
		if (qn.length>6 && qn.substring(0,6)=="qnwrap") {
			qn = qn.substring(6)*1;
		} else {
			qn = qn.substring(2)*1;
		}
		if (qn>999) {
			var partname = (Math.floor(qn/1000)-1)+"-"+(qn%1000);
			$(el).on("mouseover", function () {
				if (!focuscolorlock) {$("#qn"+qn+", #tc"+qn+", #qnwrap"+qn+", #showansbtn"+partname+", #scorebox"+partname+", #ptpos"+partname).css("background-color","yellow")};
			}).on("mouseout", function () {
				if (!focuscolorlock) {$("#qn"+qn+", #tc"+qn+", #qnwrap"+qn+", #showansbtn"+partname+", #scorebox"+partname+", #ptpos"+partname).css("background-color","")};
			}).on("focus", function () {
				focuscolorlock = true;
				$("#qn"+qn+", #tc"+qn+", #qnwrap"+qn+", #showansbtn"+partname+", #scorebox"+partname+", #ptpos"+partname).css("background-color","yellow");
			}).on("blur", function () {
				focuscolorlock = false;
				$("#qn"+qn+", #tc"+qn+", #qnwrap"+qn+", #showansbtn"+partname+", #scorebox"+partname+", #ptpos"+partname).css("background-color","");
			});
		}
	});
});

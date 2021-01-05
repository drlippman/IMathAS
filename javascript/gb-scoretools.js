function hidecorrect() {
	var butn = $("#hctoggle");
	if (!butn.hasClass("hchidden")) {
		butn.html(_("Show Questions with Perfect Scores"));
		butn.addClass("hchidden");
	} else {
		butn.html(_("Hide Questions with Perfect Scores"));
		butn.removeClass("hchidden");
	}
	$(".iscorrect").toggle();
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
	$(".isnonzero").toggle();
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
	$(".notanswered").toggle();
}
function showallans() {
	$("span[id^='ans']").removeClass("hidden").show();
	$(".sabtn").replaceWith("<span>Answer: </span>");
}
function previewall() {
	$('input[value="Preview"]').trigger('click').remove();
}
function previewallfiles() {
	$("span.clickable").trigger("click");
	$(".question span[id^=fileembedbtn], .sidepreview span[id^=fileembedbtn], .viewworkwrap span[id^=fileembedbtn]").trigger("click");
}
function showallwork() {
	$(".viewworkwrap > button").trigger("click");
}
function allvisfullcred() {
	$(".fullcredlink").not(function() {return !$(this).closest(".bigquestionwrap").is(":visible")}).trigger("click");
}
function allvisnocred() {
	$("input[name^=ud]").not(function() {return !$(this).closest(".bigquestionwrap").is(":visible")}).val("0");
}
function updatefilters() {
    $(".bigquestionwrap").show();
    var filters = ['unans','zero','nonzero','perfect','fb','nowork'];
    for (var i=0; i<6; i++) {
        if (document.getElementById('filter-' + filters[i]).checked) {
            $(".bigquestionwrap.qfilter-" + filters[i]).hide();
        }
    }
}
function toggleWork(el) {
	var next = $(el).next();
	if (next.is(':hidden')) {
		el.innerText = _('Hide Work');
        next.show();
	} else {
		el.innerText = _('Show Work');
		next.hide();
	}
}
function preprint() {
	$("span[id^='ans']").show().removeClass("hidden");
	$(".sabtn,.keybtn").replaceWith("<span>Answer: </span>");
	$('input[value="Preview"]').trigger('click').remove();
	document.getElementById("preprint").style.display = "none";
}
function quicksave() {
	var url = $("#mainform").attr("action")+"&quick=true";
	$("#quicksavenotice").html(_("Saving...") + ' <img src="'+staticroot+'/img/updating.gif"/>');
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
		var paras = document.getElementsByTagName("p");
		for (var i=0;i<paras.length;i++) {
			 if (paras[i].className=="person") {
				paras[i].style.display = el.checked?"none":"";
			 } else if (paras[i].className=="group") {
				paras[i].style.display = el.checked?"":"none";
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

function showgraphtip(el, la, init) {
	var initpts = init;
	if (typeof init == 'string') {
		init = init.replace(/"|'/g,'').split(",");
	}
	for (var j=1;j<Math.max(initpts.length,11);j++) {
		initpts[j] *= 1;  //convert to number
	}
	var drawwidth = initpts[6];
	var drawheight = initpts[7];
	la = la.replace(/\(/g,"[").replace(/\)/g,"]").split(";;")
	if  (la[0]!='') {
		la[0] = '['+la[0].replace(/;/g,"],[")+"]";
	}
	la = '[['+la.join('],[')+']]';
	var id = randID();
	canvases["GBR"+id] = initpts.slice();
	canvases["GBR"+id].unshift("GBR"+id);
	drawla["GBR"+id] = JSON.parse(la);
	var out = '<canvas class="drawcanvas" id="canvasGBR'+id+'" width='+drawwidth+' height='+drawheight+'></canvas>';
	out += '<input type="hidden" id="qnGBR'+id+'"/>';
	tipshow(el, out);
	imathasDraw.initCanvases("GBR"+id);
}

var focuscolorlock = false;
$(initAnswerboxHighlights);
function initAnswerboxHighlights() {
	$("input[id*='-']").each(function(i, el) {
		var partname = $(el).attr("id").replace(/scorebox/,'');
		var idparts = partname.split("-");
		var qn = (idparts[0]*1+1)*1000+idparts[1]*1;
		$(el).off("mouseover.ansbox").on("mouseover.ansbox", function () {
			if (!focuscolorlock) {$("#qn"+qn+", #tc"+qn+", #mqinput-qn"+qn+", #qnwrap"+qn+", #showansbtn"+partname+", #scorebox"+partname+", #ptpos"+partname).css("background-color","#FFC")};
		}).off("mouseout.ansbox").on("mouseout.ansbox", function () {
			if (!focuscolorlock) {$("#qn"+qn+", #tc"+qn+", #mqinput-qn"+qn+", #qnwrap"+qn+", #showansbtn"+partname+", #scorebox"+partname+", #ptpos"+partname).css("background-color","")};
		}).off("focus.ansbox").on("focus.ansbox", function () {
			focuscolorlock = true;
			$("#qn"+qn+", #tc"+qn+", #mqinput-qn"+qn+", #qnwrap"+qn+", #showansbtn"+partname+", #scorebox"+partname+", #ptpos"+partname).css("background-color","#FFC");
		}).off("blur.ansbox").on("blur.ansbox", function () {
			focuscolorlock = false;
			$("#qn"+qn+", #tc"+qn+", #mqinput-qn"+qn+", #qnwrap"+qn+", #showansbtn"+partname+", #scorebox"+partname+", #ptpos"+partname).css("background-color","");
		});
	});

	$("input[id^='showansbtn']").each(function(i, el) {
		var partname = $(el).attr("id").substring(10);
		var idparts = partname.split("-");
		if (idparts.length>1) {
			var qn = (idparts[0]*1+1)*1000+idparts[1]*1;
			$(el).off("mouseover.ansbox").on("mouseover.ansbox", function (e) {
				if (!focuscolorlock) {$(e.target).closest('.bigquestionwrap').parent()
					.find("#qn"+qn+", #tc"+qn+", #qnwrap"+qn+", #showansbtn"+partname+", input[id^=scorebox][id$="+partname+"], #ptpos"+partname).css("background-color","#FFC");
				};
			}).off("mouseout.ansbox").on("mouseout.ansbox", function (e) {
				if (!focuscolorlock) {$(e.target).closest('.bigquestionwrap').parent()
					.find("#qn"+qn+", #tc"+qn+", #qnwrap"+qn+", #showansbtn"+partname+", input[id^=scorebox][id$="+partname+"], #ptpos"+partname).css("background-color","")};
			});
		}
	});
	$("input[id^='qn'], input[id^='tc'], select[id^='qn'], div[id^='qnwrap'], span[id^='qnwrap'], span[id^='mqinput-qn']").each(function(i,el) {
		var qn = $(el).attr("id");
		if (qn.length>6 && qn.substring(0,6)=="qnwrap") {
			qn = qn.substring(6)*1;
		} else if (qn.length>6 && qn.substring(0,10)=="mqinput-qn") {
			qn = qn.substring(10)*1;
		} else {
			qn = qn.substring(2)*1;
		}
		if (qn>999) {
			var partname = (Math.floor(qn/1000)-1)+"-"+(qn%1000);
			$(el).on("mouseover.ansbox focus.ansbox").on("mouseover.ansbox focus.ansbox", function (e) {
				if (!focuscolorlock) {$(e.target).closest('.bigquestionwrap').parent()
					.find("#qn"+qn+", #tc"+qn+", #mqinput-qn"+qn+", #qnwrap"+qn+", #showansbtn"+partname+", input[id^=scorebox][id$="+partname+"], #ptpos"+partname).css("background-color","#FFC")};
			}).on("mouseout.ansbox blur.ansbox").on("mouseout.ansbox blur.ansbox", function (e) {
				if (!focuscolorlock) {$(e.target).closest('.bigquestionwrap').parent()
					.find("#qn"+qn+", #tc"+qn+", #mqinput-qn"+qn+", #qnwrap"+qn+", #showansbtn"+partname+", input[id^=scorebox][id$="+partname+"], #ptpos"+partname).css("background-color","")};
			});
		}
	});
};

function sidebysidegrading() {
	$("body").removeClass("fw1000").removeClass("fw1920");
	$(".scrollpane").wrap('<div class="sidebyside">');
	$(".sidebyside").append('<div class="sidepreview">');
	$(".sidebyside").css('display','flex').css('flex-wrap','nowrap');
	$(".sidepreview").css('border-left','1px solid #ccc').css('padding','10px');
	$(".scrollpane,.sidepreview").css('width','50%');
	// will have to adjust fileembedbtn to open in sidepreview
	$(".question div.introtext").each(function(i,el) {
		$(el).find(".keywrap.inwrap").insertAfter($(el));
		var tgt = $(el).closest(".sidebyside").find('.sidepreview');
		$(el).after('<div class="subdued">('+(i+1)+')</div>');
		tgt.append('<div class="subdued">('+(i+1)+') </div>').append(el);
	});
	$(".lastfilesub").each(function(i,el) {
		var tgt = $(el).closest(".sidebyside").find('.sidepreview');
		$(el).after('<span class="subdued">('+(i+1)+')</span>');
		tgt.append('<span class="subdued">('+(i+1)+') </span>').append(el);
	});
	$(".viewworkwrap").each(function(i,el) {
		$(el).css('margin','0');
		$(el).closest(".sidebyside").find('.sidepreview').append(el);
	});
}

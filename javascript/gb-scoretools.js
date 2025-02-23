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
function showallans(el) {
    if (el) { el.disabled = true; }
	$("span[id^='ans']").show();
    $(".sabtn").replaceWith("<span>Answer: </span>");
    toggleshowallans(true);
}
function toggleshowallans(state, base) {
    if (typeof base === 'undefined') { base = 'body'; }
    $(base).find("span[id^='ans']").toggleClass("hidden", !state);
    $(base).find("div[id^=dsbox]").toggleClass("hidden", !state).attr("aria-hidden", !state)
        .attr("aria-expanded", state);
    $(base).find("button[aria-controls^=ans],input[aria-controls^=dsbox]").attr('aria-expanded', state);
}
function previewall() {
	$('input[value="Preview"]').trigger('click').remove();
}
function previewallfiles(el) {
    if (el) { el.disabled = true; }
	$("span.clickable").trigger("click");
	togglepreviewallfiles(true);
}
function togglepreviewallfiles(state, base) {
    if (typeof base === 'undefined') { base = 'body'; }
    $(base).find(".question span[id^=fileembedbtn], .sidepreview span[id^=fileembedbtn], .viewworkwrap span[id^=fileembedbtn]").each(function(i,el) {
        togglefileembed(el.id,state);
    });
    if ($(base).hasClass("viewworkwrap")) {
        $(base).find("span[id^=fileembedbtn]").each(function(i,el) {
            togglefileembed(el.id,state);
        });
    }
}
function showallwork(el) {
    if (el) { el.disabled = true; }
    toggleshowallwork(true);
}
function toggleshowallwork(state) {
    $(".viewworkwrap > button").each(function(i,el) {
        toggleWork(el,state);
    });
}
function allvisfullcred() {
    if (confirm(_('Are you SURE you want to give all students full credit?'))) {
	    $(".fullcredlink").not(function() {return !$(this).closest(".bigquestionwrap").is(":visible")}).trigger("click");
    }
}
function allmanualfullcred() {
    if (confirm(_('Are you SURE you want to give all students full credit on manually-graded parts?'))) {
	    $(".fullcredmanuallink").not(function() {return !$(this).closest(".bigquestionwrap").is(":visible")}).trigger("click");
    }
}
function allvisnocred() {
    if (confirm(_('Are you SURE you want to give all students zero credit?'))) {
    	$("input[name^=ud]").not(function() {return !$(this).closest(".bigquestionwrap").is(":visible")}).val("0");
    }
}
function updatefilters() {
    $(".bigquestionwrap").show();
    var filters = ['unans','zero','nonzero','perfect','fb','nowork', 'work', '100'];
    for (var i=0; i<filters.length; i++) {
        if (document.getElementById('filter-' + filters[i]).checked) {
            $(".bigquestionwrap.qfilter-" + filters[i]).hide();
        }
    }
    $(".bigquestionwrap .headerpane,.scoredetails .person").toggle(!document.getElementById('filter-names').checked);
}
function toggleWork(el, state) {
	var next = $(el).next();
	if (next.is(':hidden') && state !== false) {
		el.innerText = _('Hide Work');
        next.show();
	} else if (state !== true) {
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
		 if (divs[i].className.match(/groupdup/)) {
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
function sortByLastChange() {
    var wrap = document.getElementById("qlistwrap");
    [].map.call( wrap.children, Object ).sort( function ( a, b ) {
        return Date.parse(b.getAttribute('data-lastchange')) - Date.parse(a.getAttribute('data-lastchange'));
    }).forEach( function ( elem ) {
        wrap.appendChild( elem );
    });
}
function sortByName() {
    var wrap = document.getElementById("qlistwrap");
    [].map.call( wrap.children, Object ).sort( function ( a, b ) {
        return $(a).children(".headerpane").text().localeCompare($(b).children(".headerpane").text());
    }).forEach( function ( elem ) {
        wrap.appendChild( elem );
    });
}
function sortByRand() {
    var wrap = document.getElementById("qlistwrap");
    [].map.call( wrap.children, Object ).sort( function ( a, b ) {
        return 0.5 - Math.random();
    }).forEach( function ( elem ) {
        wrap.appendChild( elem );
    });
}
function clearfeedback() {
	var els=document.getElementsByTagName("textarea");
	for (var i=0;i<els.length;i++) {
		if (els[i].id.match(/feedback/)) {
			els[i].value = '';
		}
	}
    $("div.fbbox").empty();
}
function cleardeffeedback() {
	var els=document.getElementsByTagName("textarea");
	for (var i=0;i<els.length;i++) {
		if (els[i].value==GBdeffbtext) {
			els[i].value = '';
		}
	}
    $("div.fbbox").each(function(i,el) {
        if (el.innerHTML==GBdeffbtext) {
            $(el).empty();
        }
    });
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

var sidebysideenabled = false;
function sidebysidegrading(state) {
    if (typeof state === 'undefined') {
        sidebysideenabled = !sidebysideenabled;
    } else {
        if (state == sidebysideenabled) { return; }
        sidebysideenabled = state;
    }
    if (sidebysideenabled) {
        if ($("body").hasClass("fw1000")) { $("body").data("origfw", "fw1000");}
        if ($("body").hasClass("fw1920")) { $("body").data("origfw", "fw1920");}
        $("body").removeClass("fw1000").removeClass("fw1920");
    } else {
        if ($("body").data("origfw")) { $("body").addClass($("body").data("origfw")); }
    }
	if (sidebysideenabled && $(".sidebyside").length == 0) {
        $(".scrollpane").wrap('<div class="sidebyside">');
        $(".sidebyside").append('<div class="sidepreview">');
        $(".sidebyside").css('display','flex').css('flex-wrap','nowrap');
    }
    if (sidebysideenabled) {
        $(".sidepreview").css('border-left','1px solid #ccc').css('padding','10px');
	    $(".scrollpane,.sidepreview").css('width','50%');
    } else {
        $(".sidepreview").css('border-left','').css('padding','0px');
	    $(".scrollpane").css('width','100%');
        $(".sidepreview").css('width','0%');
    }
    sidebysidemoveels(sidebysideenabled);
    if (sidebysideenabled) {
        $(".scrollpane .viewworkwrap").each(function(i,el) {
            $(el).css('margin','0');
            $(el).closest(".sidebyside").find('.sidepreview').append(el);
        });
    } else {
        $(".sidepreview .viewworkwrap").each(function(i,el) {
            $(el).css('margin','');
            $(el).closest(".sidebyside").find('.scrollpane').append(el);
        });
    }
}

function sidebysidemoveels(state,base) {
    if (typeof base === 'undefined') { base = 'body'; }
    if (state) {
		$(base).closest(".sidebyside").find(".sidepreviewtarget").empty();
        $(base).find(".question div.introtext").each(function(i,el) {
            $(el).find(".keywrap.inwrap").insertAfter($(el));
            var tgt = $(el).closest(".sidebyside").find('.sidepreview');
            if (tgt.find(".sidepreviewtarget").length > 0) {
                tgt = tgt.find(".sidepreviewtarget");
            }
            $(el).after('<div class="subdued" id="it_s'+i+'">('+(i+1)+')</div>');
            tgt.append('<div class="subdued" id="it_d'+i+'">('+(i+1)+') </div>').append(el);
        });
        $(base).find(".question .lastfilesub").each(function(i,el) {
            var tgt = $(el).closest(".sidebyside").find('.sidepreview');
            if (tgt.find(".sidepreviewtarget").length > 0) {
                tgt = tgt.find(".sidepreviewtarget");
            }
            $(el).after('<span class="subdued" id="lf_s'+i+'">('+(i+1)+')</span>');
            tgt.append('<span class="subdued" id="lf_d'+i+'">('+(i+1)+') </span>').append(el);
        });
    } else {
        $(base).find("div[id^=it_s").each(function(i,el) {
            let n = el.id.substr(4);
            let srcnum = $('#it_d'+n);
            let tomove = srcnum.next();
            el.replaceWith(tomove[0]);
            srcnum.remove();
        });
        $(base).find("span[id^=lf_s").each(function(i,el) {
            let n = el.id.substr(4);
            let srcnum = $('#lf_d'+n);
            let tomove = srcnum.next();
            el.replaceWith(tomove[0]);
            srcnum.remove();
        });
    }
}

var scrollingscoreboxes = false;
function toggleScrollingScoreboxes(el) {
    if (scrollingscoreboxes) {
        if (el) {el.innerText = _('Floating Scoreboxes')}
    } else {
        if (el) {el.innerText = _('Fixed Scoreboxes')}
    }
    scrollingscoreboxes = !scrollingscoreboxes;
    toggleScrollingScoreboxState(scrollingscoreboxes);
}

var scrollingscoreboxesstate = false;
function toggleScrollingScoreboxState(state) {
    if (state == scrollingscoreboxesstate) { return; }
    scrollingscoreboxesstate = state;
    if (!state) {
        $(window).off('scroll.scoreboxes');
        $(".scoredetails").removeClass("hoverbox").css("position","static").css("width","auto").css("margin-left",0);
        $(".bigquestionwrap .scrollpane").css("margin-bottom","0");
    } else {
        $(window).on('scroll.scoreboxes', updatescoreboxscroll);
        updatescoreboxscroll();
    }
}

function updatescoreboxscroll() {
    var scroll = window.scrollY || window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
    var viewbot = scroll + document.documentElement.clientHeight;
    var wraps = document.getElementsByClassName("bigquestionwrap");
    var objtop, scoredet, objbot;
    for (var i=0; i<wraps.length; i++) {
        if (wraps[i].style.display == "none") { continue; }
        var rect = wraps[i].querySelector(".scrollpane").getBoundingClientRect();
        objtop = rect.top + scroll; 
        scoredet = wraps[i].childNodes[2];
        objbot = objtop + wraps[i].querySelector(".scrollpane").offsetHeight + scoredet.offsetHeight ;
        if (viewbot > objtop + scoredet.offsetHeight + 20 && 
            viewbot < objbot && 
            scoredet.offsetHeight < .5*document.documentElement.clientHeight 
        ) {
            if (scoredet.style.position == "static") { 
                scoredet.style.width = $(scoredet).width() + "px";
                wraps[i].querySelector(".scrollpane").style.marginBottom = scoredet.offsetHeight + "px";
                scoredet.style.position = "fixed";
                scoredet.style.bottom = 0;
                scoredet.style.marginLeft = '5px';
                scoredet.classList.add("hoverbox");
            }
        } else {
            scoredet.style.position = "static";
            scoredet.style.width = 'auto';
            scoredet.style.marginLeft = '0';
            wraps[i].querySelector(".scrollpane").style.marginBottom = 0;
            scoredet.classList.remove("hoverbox");
        }
    };
}

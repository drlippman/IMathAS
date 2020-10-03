/*

What is needed:

1) x syntax check inputs
2) x Do any pre-preview cleanup
3) x Get numeric values for preview (when showvals is set)
4) x Get numeric values for submission (different for inequality)

- Do input validation for numeric types
x No livepreview for matrix/calcmatrix

On question load:
  x call init(jsParamArr)
    x add LivePreviews
    x add click handler for preview buttons
    x sets up entry tips (via setupTips)

On livepreview preview:
  x use preformat function in LivePreviews code, based on qtype

For final preview (w possible showvals and syntax checking)
  x Called from button click or LivePreviews timeout
  - May also want to add a more advanced onblur (w onfocus listener to cancel)
    for numeric types without a perview button, to provide syntax warnings.
  x Calls showPreview with the qn
    x Looks up params from allParams
    x if matrix type with matrixsize, calls processSizedMatrix function
    x otherwise, gets string from input
    x based on qtype, calls the appropriate process___ function with string
      x returns [err, numeric for preview, numeric for submission]
    x forms preview string, displays it

For onsubmit
  x For use cases without ajax submission / frontend,
    x Have a generic preSubmitForm function that would be called from form onsubmit
    x that loops over allParams
    x Gets the presubmit values and appends new hidden inputs to the form
  x Have individual preSubmit function that takes qn as input
    x calls processSizedMatrix or process____ functions
    x returns the numeric string, to be included in FormData

Previewers / processors:
    Number:  "number"
  x  Calculated: "calculated"
    Multiple Choice: "choices"
    Multiple Answer: "multans"
    Matching: "matching"
  x  Function/expression: "numfunc"
  x  Drawing: "draw"
    N-tuple: "ntuple"
  x  Calculated N-tuple: "calcntuple"
  ~  Matrix: "matrix"
  x  Calculated Matrix: "calcmatrix"
    Complex: "complex"
  x  Calculated Complex: "calccomplex"
    Interval: "interval"
  x  Calculated Interval: "calcinterval"
    Essay: "essay"
    File Upload: "file"
  x  String: "string"

TODO: capture any errors echoed during question generation and append
to question output or something.

 */
var initstack = [];
var loadedscripts = [];
var callbackstack = {};

var imathasAssess = (function($) {

var allParams = {};

function clearparams(paramarr) {
  var qn;
  for (qn in paramarr) {
    delete allParams[qn];
  }
}

function toMQwVars(str, elid) {
    var qn = elid.substr(2).split(/-/)[0];
    var qtype = allParams[qn].qtype;
    if (qtype === 'numfunc') {
        str = AMnumfuncPrepVar(qn, str)[1];
    }
    return AMtoMQ(str);
}
function fromMQwText(str, elid) {
    str = MQtoAM(str);
    str = str.replace(/\(text\((.*?)\)\)/g,'($1)')
            .replace(/text\((.*?)\)/g,' $1 ');
    return str;
}

function init(paramarr, enableMQ, baseel) {
  MQeditor.setConfig({toMQ: toMQwVars, fromMQ: fromMQwText});
  if ($("#arialive").length==0) {
    $('body').append($('<p>', {
      id: "arialive",
      "aria-live": "assertive",
      "aria-atomic": "true",
      class: "sr-only"
    }));
  }
  var qn, params, i, el, str;
  for (qn in paramarr) {
    if (isNaN(parseInt(qn))) { continue; }
    //save the params to the master record
    allParams[qn] = paramarr[qn];
    params = paramarr[qn];
    if (params.helper && params.qtype.match(/^(calc|numfunc|string|interval|matrix)/)) { //want mathquill
      el = document.getElementById("qn"+qn);
      str = params.qtype;
      if (params.calcformat) {
        str += ','+params.calcformat;
      }
      if (params.displayformat) {
        str += ','+params.displayformat;
      }
      if (params.matrixsize) {
        str += ',matrixsized';
        $("input[id^=qn"+qn+"-]").attr("data-mq", str);
      } else {
        el.setAttribute("data-mq", str);
      }
      if (params.vars) {
        el.setAttribute("data-mq-vars", params.vars);
      }
      //TODO: Need to adjust behavior for calcmatrix with answersize
      if (enableMQ) {
        if (params.matrixsize) {
          MQeditor.toggleMQAll("input[id^=qn"+qn+"-]", true, true);
        } else {
          MQeditor.toggleMQ(el, true, true);
        }
        $("#pbtn"+qn).hide();
      }
    }
    if (params.preview) { //setup preview TODO: check for userpref
      document.getElementById("pbtn"+qn).addEventListener('click', (function(thisqn) {
          return function() {showPreview(thisqn);}
        })(qn));
      if (params.preview == 1 && !params.qtype.match(/matrix/)) { //no live preview for matrix types
        if (LivePreviews.hasOwnProperty(qn)) {
          delete LivePreviews[qn]; // want to reinit
        }
        setupLivePreview(qn, enableMQ);
        document.getElementById("qn"+qn).addEventListener('keyup', updateLivePreview);
        if (enableMQ) {
          showSyntaxCheckMQ(qn);
        }
        //document.getElementById("pbtn"+qn).style.display = 'none';
      } //TODO: when matrix, clear preview on further input
    } else if (document.getElementById("qn"+qn)) {
        var thisqn = qn;
        document.getElementById("qn"+qn).addEventListener('keyup', function() { syntaxCheckMQ(thisqn) });
    } //TODO: for non-preview types, still check syntax
    if (params.format === 'debit') {
      document.getElementById("qn"+qn).addEventListener('keyup', editdebit);
    } else if (params.format === 'credit') {
      document.getElementById("qn"+qn).addEventListener('keyup', editcredit);
      initcreditboxes();
    } else if (params.format === 'normslider') {
      imathasDraw.addnormslider(qn, true);
    }
    if (params.autosuggest) {
      if (!autoSuggestLists.hasOwnProperty(params.autosuggest) &&
        params.hasOwnProperty(params.autosuggest)
      ) {
        autoSuggestLists[params.autosuggest] = params[params.autosuggest];
      }
      if (autoSuggestLists.hasOwnProperty(params.autosuggest)) {
        autoSuggestObjects[qn] = new AutoSuggest(document.getElementById("qn"+qn), autoSuggestLists[params.autosuggest]);
      }
    }
    if (params.tip) {
      if (el = document.getElementById("qn"+qn+"-0")) {
        // setup for matrix sub-parts
        i=0;
        while (document.getElementById("qn"+qn+"-"+i)) {
          setupTips("qn"+qn+"-"+i, params.tip, params.longtip);
          i++;
        }
      } else {
        setupTips("qn"+qn, params.tip, params.longtip);
      }
    }
    if (params.qtype === 'draw') {
      setupDraw(qn);
    }
    if (params.qtype === 'file') {
      initFileAlt(document.getElementById("qn"+qn));
    }
    if (params.qtype === 'multans') {
      initMultAns(qn);
    }
    if (params.usetinymce) {
      if (document.getElementById("qn"+qn).disabled &&
        !document.getElementById("tinyprev"+qn)
      ) {
        var html = $("#qn"+qn).val();
        var div = $("<div>", {"id": "tinyprev"+qn, "class": "introtext"});
        div.html(html);
        $("#qn"+qn).hide().after(div);
      } else {
        initeditor("selector","#qn" + qn + ".mceEditor",null,false,function(ed) {
          ed.on('blur', function (e) {
            tinymce.triggerSave();
            jQuery(e.target.targetElm).triggerHandler('change');
          }).on('focus', function (e) {
            jQuery(e.target.targetElm).triggerHandler('focus');
          })
        });
      }
    }
    if (params.qtype == 'essay') {
      $("#qnwrap"+qn+".introtext img").on('click', rotateimg);
    }
    initEnterHandler(qn);
  }
  initDupRubrics();
  initShowAnswer2();
  if (baseel) {
    setScoreMarkers(baseel);
  }
  initqsclickchange();
  initClearScoreMarkers();
  if (paramarr.scripts) {
    function handleScript(arr, cnt) {
      if (arr[cnt][0] == 'code') {
        try {
          window.eval(arr[cnt][1]);
        } catch (e) { console.log("Error executing question script:" + arr[cnt][1]);}
        if (arr.length > cnt+1) {
          handleScript(arr, cnt+1);
        }
      } else if (loadedscripts.indexOf(arr[cnt][1]) == -1) {
        jQuery.getScript(arr[cnt][1]).always(function() {
          loadedscripts.push(arr[cnt][1]);
          if (arr.length > cnt+1) {
            handleScript(arr, cnt+1);
          }
        });
      } else {
        if (arr.length > cnt+1) {
          handleScript(arr, cnt+1);
        }
      }
      if (arr.length <= cnt+1) {
        for (var i=0; i<initstack.length; i++) {
              var foo = initstack[i]();
        }
        initstack.length = 0;
      }
    }
    handleScript(paramarr.scripts, 0);
  }
}

// setup tip focus/blur handlers
function setupTips(id, tip, longtip) {
  var el = document.getElementById(id);
  if (!el) { return; }
  el.setAttribute('data-tip', tip);
  var ref = id.substr(2).split(/-/)[0];
  if (!document.getElementById("tips"+ref)) {
    $("body").append($("<div>", {class:"hidden", id:"tips"+ref}).html(longtip));
  }
  el.setAttribute('aria-describedby', 'tips'+ref);
  el.addEventListener('focus', function() {
    showehdd(id, tip, ref);
  });
  el.addEventListener('blur', hideeh);
  el.addEventListener('click', function() {
    reshrinkeh(id);
  });
}

function clearTips() {
  hideAllEhTips();
}

function initqsclickchange() {
	$('input[id^=qs][value=spec]').each(function(i,qsel) {
		$(qsel).siblings('input[type=text]').off('keyup.qsclickchange')
		 .on('keyup.qsclickchange', function(e) {
			if (e.keyCode != 8 && e.keyCode != 46) {
				$(qsel).prop("checked",true);
			}
		 });
	});
}

function clearScoreMarkers(e) {
  var m;
  var target = e.currentTarget
  if ((m = target.className.match(/(ansgrn|ansred|ansyel|ansorg)/)) !== null) {
    $(target).removeClass(m[0]);
    $(target).nextAll('.scoremarker.sr-only').first().remove();
    if (m[0]=='ansorg') {
        $(target).nextAll('.scoremarker').first().remove();
    }
    if (target.tagName.toLowerCase() == 'select') {
      $(target).nextAll('svg.scoremarker').first().remove();
    }
    if (target.type == 'hidden') { // may be MQ box
      $("#mqinput-"+target.id).removeClass(m[0]);
    }
  } else {
    var wrap = $(target).closest("[id^=qnwrap]");
    if (wrap.length > 0 &&
      ((m = wrap[0].className.match(/(ansgrn|ansred|ansyel|ansorg)/)) !== null)
    ) {
      wrap.removeClass(m[0]);
      wrap.find(".scoremarker").remove();
    }
  }
}

function setScoreMarkers(base) {
  var svgchk = '<svg class="scoremarker" viewBox="0 0 24 24" width="16" height="16" stroke="green" stroke-width="3" fill="none" role="img" aria-hidden=true>';
  svgchk += '<polyline points="20 6 9 17 4 12"></polyline></svg>';
  svgchk += '<span class="sr-only scoremarker">' + _('Correct') + '</span>';
  var svgychk = '<svg class="scoremarker" viewBox="0 0 24 24" width="16" height="16" stroke="rgb(255,187,0)" stroke-width="3" fill="none" role="img" aria-hidden=true>';
  svgychk += '<path d="M 5.3,10.6 9,14.2 18.5,4.6 21.4,7.4 9,19.8 2.7,13.5 z" /></svg>';
  svgychk += '<span class="sr-only scoremarker">' + _('Partially correct') + '</span>';
  var svgx = '<svg class="scoremarker" viewBox="0 0 24 24" width="16" height="16" stroke="rgb(153,0,0)" stroke-width="3" fill="none" role="img" aria-hidden=true>';
  svgx += '<path d="M18 6 L6 18 M6 6 L18 18" /></svg>';
  svgx += '<span class="sr-only scoremarker">' + _('Incorrect') + '</span>';
  var svgox = '<svg class="scoremarker" viewBox="0 0 24 24" width="16" height="16" stroke="rgb(255,85,0)" stroke-width="3" fill="none" role="img" aria-hidden=true>';
  svgox += '<path d="M18 6 L6 18 M6 6 L18 18" /></svg>';
  svgox += '<span class="sr-only scoremarker">' + _('Incorrect, wrong format') + '</span>';
  $(base).find('.scoremarker').remove();
  $(base).find('div.ansgrn,table.ansgrn').append(svgchk);
  $(base).find('div.ansyel,table.ansyel').append(svgychk);
  $(base).find('div.ansred,table.ansred').append(svgx);
  $(base).find('div.ansorg,table.ansorg').append(svgox);
  $(base).find('select.ansgrn').after(svgchk);
  $(base).find('select.ansyel').after(svgychk);
  $(base).find('select.ansred').after(svgx);
  $(base).find('select.ansorg').after(svgox);
  $(base).find('span[id^=mqinput-].ansgrn,input[type=text].ansgrn').after('<span class="scoremarker sr-only">' + _('Correct') + '</span>');
  $(base).find('span[id^=mqinput-].ansyel,input[type=text].ansyel').after('<span class="scoremarker sr-only">' + _('Partially correct') + '</span>');
  $(base).find('span[id^=mqinput-].ansred,input[type=text].ansred').after('<span class="scoremarker sr-only">' + _('Incorrect') + '</span>');
  $(base).find('span[id^=mqinput-].ansorg,input[type=text].ansorg').after('<span class="scoremarker sr-only">' + _('Incorrect, wrong format') + '</span>');
  $(base).find('span[id^=mqinput-].ansorg,input[type=text].ansorg').after(
      $('<span>', {
          role: "button",
          class: "scoremarker",
          tabindex: 0,
          "aria-label": _('Incorrect, wrong format'),
          "data-tip": _('Your answer is equivalent to the correct answer, but is not simplified or is in the wrong format'),
          "data-tooltipclass": "dropdown-pane tooltip-pane"
      }).on('mouseover focus', function () {tipshow(this)})
      .on('mouseleave blur', tipout)
      .html('<svg style="vertical-align:middle;margin-left:3px;" viewBox="0 0 24 24" width="18" height="18" stroke="rgb(255,85,0)" stroke-width="2" stroke-linecap="round" fill="none" role="img" aria-hidden=true><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12" y2="16"></line></svg>')
  );
}

function initClearScoreMarkers() {
  $('input[id^=qn]')
    .off('input.clearmarkers')
    .on('input.clearmarkers', clearScoreMarkers);
  $('input[id^=qs],select[id^=qn]')
    .off('change.clearmarkers')
    .on('change.clearmarkers', clearScoreMarkers);
}

function initEnterHandler(qn) {
	$("input[type=text][name=qn"+qn+"]").off("keydown.enterhandler")
	  .on("keydown.enterhandler", function(e) {
		if (e.which==13) {
			var btn = $(this).closest(".questionwrap").find(".submitbtnwrap .primary");
            if (btn.length>0) {
                e.preventDefault();
            }
            if (!btn.is(':disabled')) {
                btn.trigger('click');
            }
		}
	});
}

function handleMQenter(id) {
  var btn = $("#"+id).closest(".questionwrap").find(".submitbtnwrap .primary");
  if (!btn.is(':disabled')) {
    btn.trigger('click');
  }
}

function initDupRubrics() {
  $(".rubriclink").each(function(i,el) {
    $(el).removeClass("rubriclink");
    var inref = el.id.substring(16);
    //var clone = $(el).clone(true, true);
    if (inref.indexOf('-') !== -1) {
      var pts = inref.split('-');
      inref = (pts[0]*1 + 1)*1000 + pts[1]*1;
    }
    var inbox = $("#mqinput-qn"+inref+",input[type=text]#qn"+inref+",select#qn"+inref+",textarea#qn"+inref+",div.intro#qnwrap"+inref);
    if (inbox.length > 0) {
      inbox.after(el);
      $(el).show();
    }
  });
}

function initShowAnswer2() {
  var icon = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"></path></svg>';
  $("input.sabtn + span.hidden, input.sabtn + div.hidden").each(function(i, el) {

    var qref = el.id.substring(3);
    var inref = qref;
    var label;
    if (inref.indexOf('-') !== -1) {
      var pts = inref.split('-');
      inref = (pts[0]*1 + 1)*1000 + pts[1]*1;
      label = _('Question ') + (pts[0]*1+1) + _(' Part ') + (pts[1]*1 + 1);
    } else {
      label = _('Question ') + (inref*1+1);
    }
    var key = $('<span>', {'class': 'keywrap'}).append(
      $('<button>', {
        type: 'button',
        'aria-controls': 'ans'+qref,
        'aria-expanded': 'false',
        'class': 'keybtn',
        'aria-label': _('View Key for ') + label,
        title: _('View Key')
      }).on('click', function(e) {
          var curstate = (e.currentTarget.getAttribute('aria-expanded') == 'true');
          e.currentTarget.setAttribute('aria-expanded', curstate ? 'false' : 'true');
          $("#ans"+qref).toggle(!curstate);
        })
        .html(icon)
    );
    if ($(el).closest('.autoshowans').length > 0) {
      var wrap = $("#qnwrap"+inref);
      if (wrap.length > 0) {
        $(el).prev(".sabtn").remove();
        key.append($(el).hide().removeClass("hidden"))
          .addClass("inwrap");
        wrap.append(key);
        return;
      }
      var inbox = $("#mqinput-qn"+inref+",input[type=text]#qn"+inref+",select#qn"+inref+",textarea#qn"+inref);
      if (inbox.length > 0) {
        $(el).prev(".sabtn").remove();
        key.append($(el).hide().removeClass("hidden"));
        inbox.after(key);
        return;
      }
    }
    // not in autoshowans or no match, so don't want to relocate, just refresh
    var parel = $(el).parent();
    key.append($(el).hide().removeClass("hidden"));
    parel.empty().append(key);
  });

  // setup detailed solutions button the old way
  $("input.dsbtn + div.hidden").attr("aria-hidden",true).attr("aria-expanded",false);
	$("input.dsbtn").each(function() {
		var idnext = $(this).siblings("div:first-of-type").attr("id");
		$(this).attr("aria-expanded",false).attr("aria-controls",idnext)
		  .off("click.sashow").on("click.sashow", function() {
			$(this).attr("aria-expanded",true)
		  	  .siblings("div:first-of-type")
				.attr("aria-expanded",true).attr("aria-hidden",false)
				.removeClass("hidden");
		});
	});
}

function initShowAnswer() {
	$("input.sabtn + span.hidden").attr("aria-hidden",true).attr("aria-expanded",false);
	$("input.sabtn").each(function() {
		var idnext = $(this).siblings("span:first-of-type").attr("id");
		$(this).attr("aria-expanded",false).attr("aria-controls",idnext)
		  .off("click.sashow").on("click.sashow", function() {
			$(this).attr("aria-expanded",true)
		  	  .siblings("span:first-of-type")
				.attr("aria-expanded",true).attr("aria-hidden",false)
				.removeClass("hidden");
		});
	});
	$("input.dsbtn + div.hidden").attr("aria-hidden",true).attr("aria-expanded",false);
	$("input.dsbtn").each(function() {
		var idnext = $(this).siblings("div:first-of-type").attr("id");
		$(this).attr("aria-expanded",false).attr("aria-controls",idnext)
		  .off("click.sashow").on("click.sashow", function() {
			$(this).attr("aria-expanded",true)
		  	  .siblings("div:first-of-type")
				.attr("aria-expanded",true).attr("aria-hidden",false)
				.removeClass("hidden");
		});
	});
}

function setupDraw(qn) {
  var la = document.getElementById("qn"+qn).value;
  var laarr, i;
  var laarr = la.split(';;');
  var todraw = [];
  for (i=0; i<laarr.length; i++) {
    if (i==5) {
      laarr[i] = '[' + laarr[i].replace(/&quot;/g,'"') + ']';
    } else {
      laarr[i] = '[' + laarr[i].replace(/\(/g,'[').replace(/\)/g,']') + ']';
      if (i==0 && laarr[i].length > 2) {
        laarr[i] = '[' + laarr[i].replace(/;/g, '],[') + ']';
      }
    }
    if (laarr[i] === '') {
      todraw[i] = [];
    } else {
      try {
        todraw[i] = JSON.parse(laarr[i]);
      } catch (e) {
        todraw[i] = [];
      }
    }
  }
  window.drawla[qn] = todraw;
  window.canvases[qn] = allParams[qn].canvas;
  imathasDraw.initCanvases(qn);
  var drawtools = document.getElementById('drawtools'+qn);
  if (drawtools) {
    drawtools.addEventListener('click', function(event) {
      var target = event.target;
      if (target.hasAttribute('data-drawaction')) {
        var action = target.getAttribute('data-drawaction');
        var qn = target.getAttribute('data-qn');
        if (action === 'clearcanvas') {
          imathasDraw.clearcanvas(qn);
        } else if (action === 'settool') {
          var val = target.getAttribute('data-val');
          imathasDraw.settool(target, qn, val);
        }
      }
    });
  }
  var a11ydrawbtn = document.getElementById("qn"+qn).parentNode.querySelector(".a11ydrawadd");
  if (a11ydrawbtn) {
    a11ydrawbtn.addEventListener('click', function(event) {
      var qn = event.target.getAttribute('data-qn');
      imathasDraw.adda11ydraw(qn);
    });
  }
}

function initMultAns(qn) {
  var hasnone = $("#qnwrap"+qn).find('label:last').text().match(/none\s+of/i);
  if (hasnone) {
    var boxes = $('input[name^="qn'+qn+'["]');
    boxes.on('change', function () {
      if (this.checked && this.value == boxes.length-1) {
        boxes.not(':last').prop('checked', false);
      } else if (this.checked) {
        boxes.last().prop('checked', false);
      }
    });
  }
}

function isBlank(str) {
	return (!str || 0 === str.length || /^\s*$/.test(str));
}
function editdebit(e) {
  var el = e.target;
	var descr = $('#qn'+(el.id.substr(2)*1 - 1));
	if (!isBlank(el.value) && descr.hasClass("iscredit")) {
    var leftpad = descr.css('padding-left');
		if (descr.is('select')) {
			descr.css('margin-right',20);
		} else {
			descr.width('');
		}
		descr.css('padding-left','');
		descr.removeClass("iscredit");
	}
}
function editcredit(e) {
  var el = e.target;
	var descr = $('#qn'+(el.id.substr(2)*1 - 2));
	if (!isBlank(el.value) && !descr.hasClass("iscredit")) {
    var leftpad = parseInt(descr.css('padding-left'));
		if (descr.is('select')) {
			descr.css('margin-right',0);
		} else {
			descr.width(descr.width()-20);
		}
		descr.css('padding-left',20+leftpad);
		descr.addClass("iscredit");
	}
}
function initcreditboxes() {
	$('.creditbox').each(function(i, el) {
		editcredit({target: el});
	});
}

var LivePreviews = [];
function setupLivePreview(qn, skipinitial) {
    if (mathRenderer=="MathJax" && !window.MathJax) {
        var thisqn = qn; var thisskipinitial = skipinitial;
        setTimeout(100, function() { setupLivePreview(thisqn, thisskipinitial)});
        return;
    }
	if (!LivePreviews.hasOwnProperty(qn)) {
		if (mathRenderer=="MathJax" || mathRenderer=="Katex") {
			LivePreviews[qn] = {
			  delay: (mathRenderer=="MathJax"?100:0),   // delay after keystroke before updating
			  finaldelay: 1000,
			  preview: null,     // filled in by Init below
			  buffer: null,      // filled in by Init below

			  timeout: null,     // store setTimout id
			  finaltimeout: null,  // setTimeout id for clicking preview
			  mjRunning: false,  // true when MathJax is processing
			  mjPending: false,  // true when a typeset has been queued
			  oldText: null,     // used to check if an update is needed

			  //
			  //  Get the preview and buffer DIV's
			  //
			  Init: function(skipinitial) {
  				$("#p"+qn).css("positive","relative")
  					.append('<span id="lpbuf1'+qn+'" style="visibility:hidden;position:absolute;"></span>')
  					.append('<span id="lpbuf2'+qn+'" style="visibility:hidden;position:absolute;"></span>');
  				this.preview = document.getElementById("lpbuf1"+qn);
  				this.buffer = document.getElementById("lpbuf2"+qn);
          if (!skipinitial) {
            showPreview(qn);  //TODO: review this
          }
			  },

			  SwapBuffers: function () {
			    var buffer = this.preview, preview = this.buffer;
			    this.buffer = buffer; this.preview = preview;
			    buffer.style.visibility = "hidden"; buffer.style.position = "absolute";
			    preview.style.position = ""; preview.style.visibility = "";
			  },

			  Update: function (content) {
			    if (this.timeout) {clearTimeout(this.timeout)}
			    if (this.finaltimeout) {clearTimeout(this.finaltimeout)}
			    this.timeout = setTimeout(this.callback,this.delay);
			    this.finaltimeout = setTimeout(this.DoFinalPreview,this.finaldelay);
			  },

			  RenderNow: function(text) {
				  //called by preview button
			      this.buffer.innerHTML = this.oldtext = text;
			      this.mjRunning = true;
			      this.RenderBuffer();
			  },
			  RenderBuffer: function() {
			      if (mathRenderer=="MathJax") {
				      MathJax.Hub.Queue(
					      ["Typeset",MathJax.Hub,this.buffer],
					      ["PreviewDone",this]
				      );
			      } else if (mathRenderer=="Katex") {
			      	      renderMathInElement(this.buffer);
				      if (typeof MathJax != "undefined" && MathJax.version && $(this.buffer).children(".mj").length>0) {//has MathJax elements
					      MathJax.Hub.Queue(["PreviewDone",this]);
				      } else {
					      this.PreviewDone();
				      }
			      }
			  },

			  DoFinalPreview: function() {
          $("#pbtn"+qn).trigger("click");
			  },

			  preformat: function(text) {
          var qtype = allParams[qn].qtype;
          var calcformat = allParams[qn].calcformat;
          return preformat(qn, text, qtype, calcformat);
			  },

			  CreatePreview: function () {
			    this.timeout = null;
			    if (this.mjPending) return;
			    var text = document.getElementById("qn"+qn).value;
			    if (text === this.oldtext) return;
			    if (this.mjRunning) {
			      this.mjPending = true;
			      MathJax.Hub.Queue(["CreatePreview",this]);
			    } else {
			      this.oldtext = text;
			      this.buffer.innerHTML = "`"+this.preformat(text)+"`";
			      this.mjRunning = true;
			      this.RenderBuffer();
			    }
			  },

			  PreviewDone: function () {
			    this.mjRunning = this.mjPending = false;
			    this.SwapBuffers();
			    //updateeeddpos();  //TODO: re-enable later
			    updateehpos();
			  }

			};
			if (typeof MathJax != "undefined") {
				LivePreviews[qn].callback = MathJax.Callback(["CreatePreview",LivePreviews[qn]]);
				LivePreviews[qn].callback.autoReset = true;  // make sure it can run more than once
			} else {
				LivePreviews[qn].callback = function() { LivePreviews[qn].CreatePreview(); };
			}
			LivePreviews[qn].Init(skipinitial);
		} else {
			LivePreviews[qn] = {
				finaldelay: 1000,
				finaltimeout: null,  // setTimeout id for clicking preview

				Update: function (content) {
          if (this.finaltimeout) {clearTimeout(this.finaltimeout)}
				  this.finaltimeout = setTimeout(this.DoFinalPreview,this.finaldelay);
        },

				  RenderNow: function(text) {
				      var outnode = document.getElementById("p"+qn);
				      outnode.innerHTML = text;
				      rendermathnode(outnode);
				  },

				  DoFinalPreview: function() {
            $("#pbtn"+qn).trigger("click");
				  }
			}
		}
	}
}

function updateLivePreview(event) {
	var qn = event.target.id.substr(2);
	setupLivePreview(qn);
	LivePreviews[qn].Update();
}

function clearLivePreviewTimeouts() {
  for (var i in LivePreviews) {
    clearTimeout(LivePreviews[i].finaltimeout);
  }
}

function normalizemathunicode(str) {
	str = str.replace(/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/g, "");
	str = str.replace(/\u2013|\u2014|\u2015|\u2212/g, "-");
	str = str.replace(/\u2044|\u2215/g, "/");
	str = str.replace(/∞/g,"oo").replace(/≤/g,"<=").replace(/≥/g,">=").replace(/∪/g,"U");
	str = str.replace(/±/g,"+-").replace(/÷/g,"/").replace(/·|✕|×|⋅/g,"*");
	str = str.replace(/√/g,"sqrt").replace(/∛/g,"root(3)");
	str = str.replace(/⁰/g,"^0").replace(/¹/g,"^1").replace(/²/g,"^2").replace(/³/g,"^3").replace(/⁴/g,"^4").replace(/⁵/g,"^5").replace(/⁶/g,"^6").replace(/⁷/g,"^7").replace(/⁸/g,"^8").replace(/⁹/g,"^9");
	str = str.replace(/\u2329/g, "<").replace(/\u232a/g, ">");
	str = str.replace(/₀/g,"_0").replace(/₁/g,"_1").replace(/₂/g,"_2").replace(/₃/g,"_3");
	str = str.replace(/\bOO\b/gi,"oo").replace(/°/g,'degree');
	str = str.replace(/θ/g,"theta").replace(/ϕ/g,"phi").replace(/φ/g,"phi").replace(/π/g,"pi").replace(/σ/g,"sigma").replace(/μ/g,"mu")
	str = str.replace(/α/g,"alpha").replace(/β/g,"beta").replace(/γ/g,"gamma").replace(/δ/g,"delta").replace(/ε/g,"epsilon").replace(/κ/g,"kappa");
	str = str.replace(/λ/g,"lambda").replace(/ρ/g,"rho").replace(/τ/g,"tau").replace(/χ/g,"chi").replace(/ω/g,"omega");
	str = str.replace(/Ω/g,"Omega").replace(/Γ/g,"Gamma").replace(/Φ/g,"Phi").replace(/Δ/g,"Delta").replace(/Σ/g,"Sigma");
	return str;
}

function htmlEntities(str) {
  return str.replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/&/g,'&amp;');
}

/**
 * Called on preview button click on livepreview timeout
 * Displays rendered preview, along with
 */
function showPreview(qn) {
  var params = allParams[qn];
  var outstr = '';
  var res = processByType(qn);
  if (res.str) {
    outstr = '`' + htmlEntities(res.str) + '`';
  }
  if (res.dispvalstr && res.dispvalstr != '' && params.calcformat.indexOf('showval')!=-1) {
    outstr += (outstr==''?'':' = ') + '`' + htmlEntities(res.dispvalstr) + '`';
  }
  if (res.err && res.err != '' && res.str != '') {
    outstr += (outstr=='``')?'':'. ' + '<span class=noticetext>' + res.err + '</span>';
  }
  if (LivePreviews.hasOwnProperty(qn)) {
    LivePreviews[qn].RenderNow(outstr);
  } else {
    var previewel = document.getElementById('p'+qn);
    previewel.innerHTML = outstr;
    rendermathnode(previewel);
  }
  a11ypreview(outstr);
}

function a11ypreview(str) {
  var el = $("<div>",{class:"inactive"}).html(str).appendTo("body")[0];
  rendermathnode(el, function () {
    var arialiveel = document.getElementById('arialive');
    arialiveel.innerHTML = '';
    el.className = '';
    // replace mathjax spans with aria-label
    $(el).find("[aria-label]").each(function(i,e) {
      $(e).html($(e).attr("aria-label"));
    })
    arialiveel.appendChild(el);
  });
}

var MQsyntaxtimer = null;
/**
 * Called on MathQuill edit
 * @param   id  mathquill element id, mqinput-qn#
 * @param   str the asciimath string entered
 */
function syntaxCheckMQ(id, str) {
  clearTimeout(MQsyntaxtimer);
  var qn = parseInt(id.replace(/\D/g,''));
  MQsyntaxtimer = setTimeout(function() { showSyntaxCheckMQ(qn);}, 1000);
}

function showSyntaxCheckMQ(qn) {
  var res = processByType(qn);
  var outstr = '';
  if (res.err && res.err != '' && res.str != '') {
    outstr += '<span class=noticetext>' + res.err + '</span>';
  }
  if (LivePreviews.hasOwnProperty(qn) && (mathRenderer=="MathJax" || mathRenderer=="Katex")) {
    var previewel = document.getElementById('p'+qn).firstChild;
    previewel.innerHTML = outstr;
    previewel.style.visibility = '';
    previewel.style.position = '';
  } else {
    var previewel = document.getElementById('p'+qn);
    previewel.innerHTML = outstr;
  }
  a11ypreview('`'+htmlEntities(document.getElementById("qn"+qn).value)+'` ' + outstr);
}

/**
 * Takes a form element as input. Runs presubmit on everything
 * and adds the elements to the form.
 */
function preSubmitForm(form) {
  var presub;
  for (var qn in allParams) {
    // reuse existing if there is one
    var ex;
    presub = preSubmit(qn);
    if (presub === false) {
      continue;
    }
    if (ex = document.getElementById('qn' + qn + '-val')) {
      ex.value = presub;
    } else {
      var el = document.createElement('input');
      el.type = 'hidden';
      el.name = 'qn' + qn + '-val';
      el.value = presub;
      form.appendChild(el);
    }
  }
}

/**
 * For pre-submission, gets the numeric string to append to form as
 * qn$qn-val
 */
function preSubmit(qn) {
  var res = processByType(qn);
  if (res.submitstr) {
    return res.submitstr;
  } else {
    return false;
  }
}

function preSubmitString(name, str) {
  var qn = parseInt(name.substr(2));
  if (!allParams.hasOwnProperty(qn)) {
    return str;
  }
  var params = allParams[qn];
  str = normalizemathunicode(str);
  str = str.replace(/^\s+/,'').replace(/\s+$/,'');
  if (params.qtype == 'numfunc') {
    str = AMnumfuncPrepVar(qn, str)[3];
  }
  return str;
}

/**
 * Processes each question type.  Return object has:
 *   .str:  the input, formatted for rendering
 *   .dispvalstr: the evaluated string, formatted for display
 *   .submitstr: the evaluated answer, formatted for submission
 */
function processByType(qn) {
  if (!allParams.hasOwnProperty(qn)) {
    return false;
  }
  var params = allParams[qn];
  var res = {};
  if (params.qtype == 'draw') {
    imathasDraw.encodea11ydraw();
    return {};
  } else if (params.qtype == 'choices' || params.qtype == 'multans' || params.qtype == 'matching') {
    return {};
  } else if (params.hasOwnProperty('matrixsize')) {
    res = processSizedMatrix(qn);
  } else {
    var el = document.getElementById('qn'+qn);
    if (!el) {
      return false;
    }
    var str = el.value;
    str = normalizemathunicode(str);
    str = str.replace(/^\s+/,'').replace(/\s+$/,'');
    if (str.match(/^\s*$/)) {
      return {str: '', displvalstr: '', submitstr: ''};
    } else if (str.match(/^\s*DNE\s*$/i)) {
      return {str: 'DNE', displvalstr: '', submitstr: 'DNE'};
    } else if (str.match(/^\s*oo\s*$/i)) {
      return {str: 'oo', displvalstr: '', submitstr: 'oo'};
    } else if (str.match(/^\s*\+oo\s*$/i)) {
      return {str: '+oo', displvalstr: '', submitstr: '+oo'};
    } else if (str.match(/^\s*-oo\s*$/i)) {
      return {str: '-oo', displvalstr: '', submitstr: '-oo'};
    }
    switch (params.qtype) {
      case 'number':
        res = processNumber(str, params.calcformat);
        break;
      case 'calculated':
        res = processCalculated(str, params.calcformat);
        break;
      case 'interval':
      case 'calcinterval':
        res = processCalcInterval(str, params.calcformat, params.vars);
        break;
      case 'calcntuple':
        res = processCalcNtuple(str, params.calcformat);
        break;
      case 'calccomplex':
        res = processCalcComplex(str, params.calcformat);
        break;
      case 'calcmatrix':
        res = processCalcMatrix(str, params.calcformat);
        break;
      case 'numfunc':
        res = processNumfunc(qn, str, params.calcformat);
        break;
      case 'matrix':
        res = processCalcMatrix(str, 'decimal');
        break;
    }
    res.str = preformat(qn, str, params.qtype, params.calcformat);
  }
  return res;
}



/**
 * Formats the string for rendering
 */
function preformat(qn, text, qtype, calcformat) {
  text = normalizemathunicode(text);
  if (qtype.match(/interval/)) {
    if (!calcformat.match(/inequality/)) {
      text = text.replace(/U/g,"uu");
    } else {
      text = text.replace(/<=/g,' le ').replace(/>=/g,' ge ').replace(/</g,' lt ').replace(/>/g,' gt ');
      if (text.match(/all\s*real/i)) {
        text = "text("+text+")";
      }
    }
  } else if (qtype == 'numfunc') {
    text = AMnumfuncPrepVar(qn, text)[1];
  } else if (qtype == 'calcntuple') {
    text = text.replace(/<+/g, '(:').replace(/>+/g, ':)');
  } else if (qtype == 'calculated') {
    if (calcformat.indexOf('list')==-1 && calcformat.indexOf('set')==-1) {
      text = text.replace(/(\d)\s*,\s*(?=\d{3}\b)/g,"$1");
    }
    if (calcformat.indexOf('scinot')!=-1) {
      text = text.replace(/(x|X|\u00D7)/,"xx");
    }
  }
  text = text.replace(/[^\u0000-\u007f]/g, '?');
  return text;
}

var greekletters = ['alpha','beta','chi','delta','epsilon','gamma','varphi','phi','psi','sigma','rho','theta','lambda','mu','nu','omega','tau'];

function AMnumfuncPrepVar(qn,str) {
  var vars = allParams[qn].vars.slice();
  var vl = vars.map(escapeRegExp).join('|');
  var fvarslist = allParams[qn].fvars.map(escapeRegExp).join('|');
  vars.push("DNE");

  if (vl.match(/lambda/)) {
  	  str = str.replace(/lamda/, 'lambda');
  }

  var foundaltcap = [];
  var dispstr = str;

  dispstr = dispstr.replace(/(arcsinh|arccosh|arctanh|arcsech|arccsch|arccoth|arcsin|arccos|arctan|arcsec|arccsc|arccot|sinh|cosh|tanh|sech|csch|coth|sqrt|ln|log|exp|sin|cos|tan|sec|csc|cot|abs|root)/g, functoindex);
  str = str.replace(/(arcsinh|arccosh|arctanh|arcsech|arccsch|arccoth|arcsin|arccos|arctan|arcsec|arccsc|arccot|sinh|cosh|tanh|sech|csch|coth|sqrt|ln|log|exp|sin|cos|tan|sec|csc|cot|abs|root)/g, functoindex);
  for (var i=0; i<vars.length; i++) {
    // handle double parens
    if (vars[i].match(/\(.+\)/)) { // variable has parens, not funcvar
      str = str.replace(/\(\((.*?)\)\)/g,'($1)');
    }
  	if (vars[i] == "varE") {
		  str = str.replace("E","varE");
		  dispstr = dispstr.replace("E","varE");
	  } else {
	  	foundaltcap[i] = false;
	  	for (var j=0; j<vars.length; j++) {
	  		if (i!=j && vars[j].toLowerCase()==vars[i].toLowerCase() && vars[j]!=vars[i]) {
	  			foundaltcap[i] = true;
	  			break;
	  		}
	  	}
	  }
  }
  //sequentially escape variables from longest to shortest, then unescape
  str = str.replace(new RegExp("("+vl+")","gi"), function(match,p1) {
	 for (var i=0; i<vars.length;i++) {
		if (vars[i]==p1 || (!foundaltcap[i] && vars[i].toLowerCase()==p1.toLowerCase())) {
			return '@v'+i+'@';
		}
	 }});
  str = str.replace(/@v(\d+)@/g, function(match,contents) {
  	  return vars[contents];
       });
  dispstr = dispstr.replace(new RegExp("("+vl+")","gi"), function(match,p1) {
	 for (var i=0; i<vars.length;i++) {
		if (vars[i]==p1 || (!foundaltcap[i] && vars[i].toLowerCase()==p1.toLowerCase())) {
			return '@v'+i+'@';
		}
	 }});
  // fix display of /n!
  dispstr = dispstr.replace(/(@v(\d+)@|\d+(\.\d+)?)!/g, '{:$&:}');
  dispstr = dispstr.replace(/@v(\d+)@/g, function(match,contents) {
  	  return vars[contents];
       });

  var submitstr = str;
  //quote out multiletter variables
  var varstoquote = new Array(); var regmod;
  for (var i=0; i<vars.length; i++) {
	  if (vars[i].length>1) {
		  var isgreek = false;
		  if (greekletters.indexOf(vars[i].toLowerCase())!=-1) {
			  isgreek = true;
		  }
		  if (vars[i].match(/^\w+_\w+$/)) {
		  	if (!foundaltcap[i]) {
		  		regmod = "gi";
		  	} else {
		  		regmod = "g";
		  	}
		  	//var varpts = vars[i].match(new RegExp(/^(\w+)_(\d*[a-zA-Z]+\w+)$/,regmod));
		  	var varpts = new RegExp(/^(\w+)_(\w+)$/,regmod).exec(vars[i]);
		  	var remvarparen = new RegExp(varpts[1]+'_\\('+varpts[2]+'\\)', regmod);
		  	dispstr = dispstr.replace(remvarparen, vars[i]);
		  	str = str.replace(remvarparen, vars[i]);
        submitstr = submitstr.replace(new RegExp(varpts[0],regmod), varpts[1]+'_'+varpts[2]);
        submitstr = submitstr.replace(
          new RegExp(varpts[1]+'_\\('+varpts[2]+'\\)',regmod),
          varpts[1]+'_('+varpts[2]+')');
		  	if (varpts[1].length>1 && greekletters.indexOf(varpts[1].toLowerCase())==-1) {
		  		varpts[1] = '"'+varpts[1]+'"';
		  	}
		  	if (varpts[2].length>1 && greekletters.indexOf(varpts[2].toLowerCase())==-1) {
		  		varpts[2] = '"'+varpts[2]+'"';
		  	}
		  	dispstr = dispstr.replace(new RegExp(varpts[0],regmod), varpts[1]+'_'+varpts[2]);
		  	//this repvars was needed to workaround with mathjs confusion with subscripted variables
		  	str = str.replace(new RegExp(varpts[0],"g"), "repvars"+i);
		  	vars[i] = "repvars"+i;
		  } else if (!isgreek && vars[i]!="varE" && vars[i].replace(/[^\w_]/g,'').length>1) {
			  varstoquote.push(vars[i]);
		  }
      if (vars[i].match(/[^\w_]/) || vars[i].match(/^(break|case|catch|continue|debugger|default|delete|do|else|finally|for|function|if|in|instanceof|new|return|switch|this|throw|try|typeof|var|void|while|and with)$/)) {
        str = str.replace(new RegExp(escapeRegExp(vars[i]),"g"), "repvars"+i);
		  	vars[i] = "repvars"+i;
      }
	  }
  }

  if (varstoquote.length>0) {
	  vltq = varstoquote.join("|");
	  var reg = new RegExp("("+vltq+")","g");
	  dispstr = dispstr.replace(reg,"\"$1\"");
  }
  dispstr = dispstr.replace("varE","E");
  dispstr = dispstr.replace(/@(\d+)@/g, indextofunc);
  str = str.replace(/@(\d+)@/g, indextofunc);
  submitstr = submitstr.replace(/@(\d+)@/g, indextofunc);

  //Correct rendering when f or g is a variable not a function
  if (vl.match(/\bf\b/) && !fvarslist.match(/\bf\b/)) {
  	  dispstr = dispstr.replace(/([^a-zA-Z])f\^([\d\.]+)([^\d\.])/g, "$1f^$2{::}$3");
  	  dispstr = dispstr.replace(/([^a-zA-Z])f\(/g, "$1f{::}(");
  }
  if (vl.match(/\bg\b/) && !fvarslist.match(/\bg\b/)) {
  	  dispstr = dispstr.replace(/([^a-zA-Z])g\^([\d\.]+)([^\d\.])/g, "$1g^$2{::}$3");
  	  dispstr = dispstr.replace(/([^a-zA-Z])g\(/g, "$1g{::}(");
  }
  return [str,dispstr,vars.join("|"),submitstr];
}


/**
 *  These functions should return:
 *   .str:  the input, formatted for rendering
 *   .dispvalstr: the evaluated string, formatted for display
 *   .submitstr: the evaluated answer, formatted for submission
 */

function processNumber(origstr, format) {
    var err = '';
    if (format.indexOf('list')!== -1) {
        var strs = origstr.split(/\s*,\s*/);
    } else {
        var strs = [origstr.replace(/,/,'')];
    }
    var str;
    for (var j=0;j<strs.length;j++) {
        str = strs[j];
        if (format.indexOf('units')!=-1) {
            var unitformat = _('Units must be given as [decimal number]*[unit]^[power]*[unit]^[power].../[unit]^[power]*[unit]^[power]...');
            if (!str.match(/^\s*(\d+\.?\d*|\.\d+|\d\.?\d*\s*(E|\*\s*10\s*\^)\s*[\-\+]?\d+)/)) {
                err += _('Answer must start with a number. ');
            }
            // strip number
            str = str.replace(/^\s*(\d\.?\d*\s*(E|\*\s*10\s*\^)\s*[\-\+]?\d+|\d+\.?\d*|\.\d+)\s*[\-\*]?\s*/,'');
            str = str.replace(/\s*\-\s*([a-zA-Z])/g,'*$1');
            str = str.replace(/\*\*/g,'^');
            str = str.replace(/\s*(\/|\^|\-)\s*/g,'$1');
            str = str.replace(/\(\s*(.*?)\s*\)\s*\//, '$1/').replace(/\/\s*\(\s*(.*?)\s*\)/,'/$1');
            str = str.replace(/\s*[\*\s]\s*/g,'*');
            // strip word^power since those are valid
            str = str.replace(/([a-zA-Z]\w*)\^[\-\+]?\d+/g, '$1');
            if (str.match(/\^/)) {
                err += _('Invalid exponents. ');
                str = str.replace(/\^/g,'');
            }
            if ((str.match(/\//g) || []).length > 1) {
                err += _('Only one division symbol allowed in the units. ');
            }
            str = str.replace(/\//g,'*').trim();
            console.log(str);
            if (str.length > 0) {
                var pts = str.split(/\s*\*\s*/);
                var unitsregex = /^(yotta|zetta|exa|peta|tera|giga|mega|kilo|hecto|deka|deci|centi|milli|micro|nano|pico|fempto|atto|zepto|yocto)?(m|meters?|km|cm|mm|um|microns?|nm|[aA]ngstroms?|pm|fm|fermi|in|inch|inches|ft|foot|feet|mi|miles?|furlongs?|yd|yards?|s|sec|seconds?|ms|us|ns|min|minutes?|hr|hours?|days?|weeks?|mo|months?|yr|years?|fortnights?|acres?|ha|hectares?|b|barns?|L|liters?|litres?|dL|ml|mL|cc|gal|gallons?|cups?|pints?|quarts?|tbsp|tablespoons?|tsp|teaspoons?|rad|radians?|deg|degrees?|gradians?|knots?|kt|c|mph|kph|kg|g|grams?|mg|tonnes?|k?[hH]z|[hH]ertz|revs?|revolutions?|cycles?|N|[nN]ewtons?|kips?|dynes?|lbs?|pounds?|tons?|[kK]?J|[jJ]oules?|ergs?|lbf|lbft|ftlb|cal|calories?|kcal|eV|electronvolts?|k[wW]h|btu|BTU|W|[wW]atts?|kW|hp|horsepower|Pa|[pP]ascals?|kPa|MPa|GPa|atms?|atmospheres?|bars?|barometers?|mbars?|[tT]orr|mmHg|cmWater|psi|C|[cC]oulombs?|V|[vV]olts?|mV|MV|[fF]arad|ohms?|ohms|amps?|[aA]mperes?|T|[tT]eslas?|G|Gauss|Wb|Weber|H|Henry|lm|lumens?|lx|lux|amu|[dD]altons?|me|mol|mole|Ci|curies?|R|roentgens?|sr|steradians?|Bq|bequerel|ls|lightsecond|ly|lightyears?|AU|au|parsecs?|kpc|solarmass|solarradius|degF|degC|degK|microns?|cmH2O)$/;
                for (var i=0; i<pts.length; i++) {
                    if (!unitsregex.test(pts[i])) {
                        err += _('Unknown unit ')+'"'+pts[i]+'". ';
                    }
                }
            } else {
                err += _("Missing units");
            }
        } else if (format.indexOf('integer')!=-1) {
            if (!str.match(/^\s*\d+\s*$/)) {
                err += _('This is not an integer.');
            }
        } else {
            if (!str.match(/^\s*(\d+\.?\d*|\.\d+|\d\.?\d*\s*E\s*[\-\+]?\d+)\s*$/)) {
                err += _('This is not a decimal or integer value.');
            }
        }
    }
    return {
        err: err,
        dispvalstr: origstr
    };
}

function processCalculated(fullstr, format) {
  fullstr = fullstr.replace(/=/,'');
  if (format.indexOf('list')!=-1) {
	  var strarr = fullstr.split(/,/);
  } else if (format.indexOf('set')!=-1) {
  	var strarr = fullstr.replace(/[\{\}]/g,'').split(/,/);
  } else {
	  var strarr = [fullstr];
  }
  var err = '', res, outvals = [];
  for (var sc=0;sc<strarr.length;sc++) {
    str = strarr[sc];
    err += singlevalsyntaxcheck(str, format);
    err += syntaxcheckexpr(str, format);
    res = singlevaleval(str, format);
    err += res[1];
    outvals.push(res[0]);
  }
  var dispstr = outvals.join(', ');
  if (format.indexOf('set')!=-1) {
    dispstr = '{' + dispstr + '}';
  }
  return {
    err: err,
    dispvalstr: dispstr,
    submitstr:  outvals.join(',')
  };
}

function processCalcInterval(fullstr, format, ineqvar) {
  var origstr = fullstr;
  if (format.indexOf('inequality')!=-1) {
    fullstr = fullstr.replace(/or/g,' or ');
    var conv = ineqtointerval(fullstr, ineqvar);
    if (conv.length>1) { // has error
      return {
        err: (conv[1]=='wrongvar')?
          _('you may have used the wrong variable'):
          _('invalid inequality notation')
      }
    }
    fullstr = conv[0];
  }
  var strarr = [], submitstrarr = [], dispstrarr = [], joinchar = 'U';
  //split into array of intervals
  if (format.indexOf('list')!=-1) {
    joinchar = ',';
    var lastpos = 0;
    for (var pos = 1; pos<fullstr.length-1; pos++) {
      if (fullstr.charAt(pos)==',') {
        if ((fullstr.charAt(pos-1)==')' || fullstr.charAt(pos-1)==']')
          && (fullstr.charAt(pos+1)=='(' || fullstr.charAt(pos+1)=='[')
        ) {
          strarr.push(fullstr.substring(lastpos,pos));
          lastpos = pos+1;
        }
      }
    }
    strarr.push(fullstr.substring(lastpos));
  } else {
     strarr = fullstr.split(/\s*U\s*/i);
  }

  var err = ''; var str, vals, res, calcvals = [];
  for (i=0; i<strarr.length; i++) {
    str = strarr[i];
    sm = str.charAt(0);
    em = str.charAt(str.length-1);
    vals = str.substring(1,str.length-1);
    vals = vals.split(/,/);
    // check right basic format
    if (vals.length != 2 || ((sm != '(' && sm != '[') || (em != ')' && em != ']'))) {
      if (format.indexOf('inequality')!=-1) {
        err += _("invalid inequality notation") + '. ';
      } else {
        err += _("invalid interval notation") + '. ';
      }
      break;
    }
    for (j=0; j<2; j++) {
      if (format.indexOf('decimal')!=-1 && vals[j].match(/[\d\.]e\-?\d/)) {
        vals[j] = vals[j].replace(/e/,"E"); // allow 3e-4 in place of 3E-4 for decimal answers
      }
      err += singlevalsyntaxcheck(vals[j], format);
      err += syntaxcheckexpr(vals[j], format);
      if (vals[j].match(/^\s*\-?\+?oo\s*$/)) {
        calcvals[j] = vals[j];
      } else {
        res = singlevaleval(vals[j], format);
        err += res[1];
        calcvals[j] = res[0];
      }
    }

    submitstrarr[i] = sm + calcvals[0] + ',' + calcvals[1] + em;
    if (format.indexOf('inequality')!=-1) {
      // reformat as inequality
      if (calcvals[0].toString().match(/oo/)) {
        if (calcvals[1].toString().match(/oo/)) {
          dispstrarr[i] = 'RR';
        } else {
          dispstrarr[i] = ineqvar + (em==']'?'le':'lt') + calcvals[1];
        }
      } else if (calcvals[1].toString().match(/oo/)) {
        dispstrarr[i] = ineqvar + (sm=='['?'ge':'gt') + calcvals[0];
      } else {
        dispstrarr[i] = calcvals[0] + (sm=='['?'le':'lt') + ineqvar + (em==']'?'le':'lt') + calcvals[1];
      }
    }
  }
  if (format.indexOf('inequality')!=-1) {
    return {
      err: err,
      dispvalstr: dispstrarr.join(' "or" '),
      submitstr:  submitstrarr.join(joinchar)
    };
  } else {
    return {
      err: err,
      dispvalstr: submitstrarr.join(' uu '),
      submitstr: submitstrarr.join(joinchar)
    };
  }
}

function processCalcNtuple(fullstr, format) {
  var outcalced = '';
  var NCdepth = 0;
  var lastcut = 0;
  var err = "";
  var notationok = true;
  var res = NaN;
  var dec;
  fullstr = fullstr.replace(/(\s+,\s+|,\s+|\s+,)/, ',');
  fullstr = fullstr.replace(/<<(.*)>>/, '<$1>');
  if (!fullstr.charAt(0).match(/[\(\[\<\{]/)) {
    notationok=false;
  }
  for (var i=0; i<fullstr.length; i++) {
    dec = false;
    if (NCdepth==0) {
      outcalced += fullstr.charAt(i);
      lastcut = i+1;
      if (fullstr.charAt(i)==',') {
        if (!fullstr.substring(i+1).match(/^\s*[\(\[\<\{]/) ||
          !fullstr.substring(0,i).match(/[\)\]\>\}]\s*$/)
        ) {
          notationok=false;
        }
      }
    }
    if (fullstr.charAt(i).match(/[\(\[\<\{]/)) {
      NCdepth++;
    } else if (fullstr.charAt(i).match(/[\)\]\>\}]/)) {
      NCdepth--;
      dec = true;
    }

    if ((NCdepth==0 && dec) || (NCdepth==1 && fullstr.charAt(i)==',')) {
      sub = fullstr.substring(lastcut,i).replace(/^\s+/,'').replace(/\s+$/,'');
      if (sub=='oo' || sub=='+oo' || sub=='-oo') {
        outcalced += sub;
      } else {
        err += singlevalsyntaxcheck(sub, format);
        err += syntaxcheckexpr(sub, format);
        res = singlevaleval(sub, format);
        err += res[1];
        outcalced += res[0];
      }
      outcalced += fullstr.charAt(i);
      lastcut = i+1;
    }
  }
  if (NCdepth!=0) {
    notationok = false;
  }
  if (notationok==false) {
    err = _("Invalid notation")+". " + err;
  }
  return {
    err: err,
    dispvalstr: outcalced,
    submitstr: outcalced
  };
}

function processCalcComplex(fullstr, format) {
  var err = '';
  var arr = fullstr.split(',');
  var str = '';
  var outstr = '';
  var outarr = [];
  var real, imag, imag2, prep;
  for (var cnt=0; cnt<arr.length; cnt++) {
    str = arr[cnt].replace(/^\s+/,'').replace(/\s+$/,'');
    if (format.indexOf("sloppycomplex")==-1) {
      var cparts = parsecomplex(arr[cnt]);
      if (typeof cparts == 'string') {
        err += cparts;
      } else {
        err += singlevalsyntaxcheck(cparts[0], format);
        err += singlevalsyntaxcheck(cparts[1], format);
      }
    }
    err + syntaxcheckexpr(str, format);
    prep = prepWithMath(mathjs(str,'i'));
    real = scopedeval('var i=0;'+prep);
    imag = scopedeval('var i=1;'+prep);
    imag2 = scopedeval('var i=-1;'+prep);
    if (real=="synerr" || imag=="synerr") {
      err += _("syntax incomplete");
      real = NaN;
    }
    if (!isNaN(real) && real!="Infinity" && !isNaN(imag) && !isNaN(imag2) && imag!="Infinity") {
      imag -= real;
      outstr = Math.abs(real)<1e-16?'':real;
      outstr += Math.abs(imag)<1e-16?'':((imag>0&&outstr!=''?'+':'')+imag+'i');
      outarr.push(outstr);
    }
  }
  return {
    err: err,
    dispvalstr: outarr.join(', '),
    submitstr: outarr.join(',')
  };
}

function processSizedMatrix(qn) {
  var params = allParams[qn];
  var size = params.matrixsize;
  var format = '';
  if (params.calcformat) {
    format = params.calcformat;
  }
  var out = [];
  var outcalc = [];
  var outsub = [];
  var count = 0;
  var str;
  var err = '';
  for (var row=0; row < size[0]; row++) {
    out[row] = [];
    outcalc[row] = [];
    for (var col=0; col<size[1]; col++) {
      str = document.getElementById('qn' + qn + '-' + count).value;
      str = normalizemathunicode(str);
      err += syntaxcheckexpr(str,format);
      err += singlevalsyntaxcheck(str,format);
      out[row][col] = str;
      res = singlevaleval(str, format);
      err += res[1];
      outcalc[row][col] = res[0];
      outsub.push(res[0]);
      count++;
    }
    out[row] = '(' + out[row].join(',') + ')';
    outcalc[row] = '(' + outcalc[row].join(',') + ')';
  }
  return {
    err: err,
    str: '[' + out.join(',') + ']',
    dispvalstr: (params.qtype=='calcmatrix')?('[' + outcalc.join(',') + ']'):'',
    submitstr: outsub.join('|')
  };
}

function processCalcMatrix(fullstr, format) {
  var okformat = true;
  fullstr = fullstr.replace(/\[/g, '(');
  fullstr = fullstr.replace(/\]/g, ')');
  fullstr = fullstr.replace(/\s+/g,'');
  if (fullstr.length < 2 || fullstr.charAt(0) !== '(' ||
    fullstr.charAt(fullstr.length-1) !== ')'
  ) {
    okformat = false;
  }
  fullstr = fullstr.substring(1,fullstr.length-1);
  var err = '';
  var rowlist = [];
  var lastcut = 0;
  var MCdepth = 0;
  for (var i=0; i<fullstr.length; i++) {
    if (fullstr.charAt(i)=='(') {
      MCdepth++;
    } else if (fullstr.charAt(i)==')') {
      MCdepth--;
    } else if (fullstr.charAt(i)==',' && MCdepth==0) {
      rowlist.push(fullstr.substring(lastcut+1,i-1));
      lastcut = i+1;
    }
  }
  rowlist.push(fullstr.substring(lastcut+1,fullstr.length-1));
  var lastnumcols = -1;
  if (MCdepth !== 0) {
    okformat = false;
  }
  var collist, str;
  var outcalc = [];
  var outsub = [];
  for (var i=0; i<rowlist.length; i++) {
    outcalc[i] = [];
    collist = rowlist[i].split(',');
    if (lastnumcols > -1 && collist.length != lastnumcols) {
      okformat = false;
    }
    lastnumcols = collist.length;
    for (var j=0; j<collist.length; j++) {
      str = collist[j].replace(/^\s+/,'').replace(/\s+$/,'');
      err += syntaxcheckexpr(str,format);
      err += singlevalsyntaxcheck(str,format);
      res = singlevaleval(str, format);
      err += res[1];
      outcalc[i][j] = res[0];
      outsub.push(res[0]);
    }
    outcalc[i] = '(' + outcalc[i].join(',') + ')';
  }
  if (!okformat) {
    err = _('Invalid matrix format')+'. ';
  }
  return {
    err: err,
    dispvalstr: '[' + outcalc.join(',') + ']',
    submitstr: outsub.join('|')
  };
}

//vars and fvars are arrays; format is string
function processNumfunc(qn, fullstr, format) {
  var params = allParams[qn];
  var vars = params.vars;
  var fvars = params.fvars;
  var domain = params.domain;
  var iseqn = format.match(/equation/);
  var isineq = format.match(/inequality/);
  var err = '';

  var strprocess = AMnumfuncPrepVar(qn, fullstr);

  var totesteqn = strprocess[0];
  totesteqn = totesteqn.replace(/,/g,"").replace(/^\s+/,'').replace(/\s+$/,'').replace(/degree/g,'');
  var remapVars = strprocess[2].split('|');

  if (fullstr.match(/(<=|>=|<|>)/)) {
    if (!isineq) {
      if (iseqn) {
        err += _("syntax error: you gave an inequality, not an equation");
      } else {
        err += _("syntax error: you gave an inequality, not an expression");
      }
    } else if (fullstr.match(/(<=|>=|<|>)/g).length>1) {
      err += _("syntax error: your inequality should only contain one inequality symbol");
    }
    totesteqn = totesteqn.replace(/(.*)(<=|>=|<|>)(.*)/,"$1-($3)");
  } else if (fullstr.match(/=/)) {
    if (isineq) {
      err += _("syntax error: you gave an equation, not an inequality");
    } else if (!iseqn) {
      err += _("syntax error: you gave an equation, not an expression");
    } else if (fullstr.match(/=/g).length>1) {
      err += _("syntax error: your equation should only contain one equal sign");
    }
    totesteqn = totesteqn.replace(/(.*)=(.*)/,"$1-($2)");
  } else if (iseqn) {
    err += _("syntax error: this is not an equation");
  } else if (isineq) {
    err += _("syntax error: this is not an inequality");
  }

  if (fvars.length > 0) {
	  reg = new RegExp("("+fvars.join('|')+")\\(","g");
	  totesteqn = totesteqn.replace(reg,"$1*sin($1+");
  }

  totesteqn = prepWithMath(mathjs(totesteqn,remapVars.join('|')));

  var i,j,totest,testval,res;
  var successfulEvals = 0;
  for (j=0; j < 20; j++) {
    totest = 'var DNE=1;';
    for (i=0; i < remapVars.length - 1; i++) {  // -1 to skip DNE pushed to end
      if (domain[i][2]) { //integers
        testval = Math.floor(Math.random()*(domain[i][0] - domain[i][1] + 1) + domain[i][0]);
      } else { //any real between min and max
        testval = Math.random()*(domain[i][0] - domain[i][1]) + domain[i][0];
      }
      totest += 'var ' + remapVars[i] + '=' + testval + ';';
    }
    res = scopedeval(totest + totesteqn);
    if (res !== 'synerr') {
      successfulEvals++;
      break;
    }
  }
  if (successfulEvals === 0) {
    err += _("syntax error") + '. ';
  }
  err += syntaxcheckexpr(strprocess[0], format, vars.map(escapeRegExp).join('|'));
  return {
    err: err
  };
}

function simplifyVariable(str) {
  //get rid of anything that's no alphanumeric, underscore, power, or +/-
  return str.replace(/[^\w_\^\-+]/g,'');
}

//Function to convert inequalities into interval notation
function ineqtointerval(strw, intendedvar) {
  var simpvar = simplifyVariable(intendedvar);
  strw = strw.replace(/(\d)\s*,\s*(?=\d{3}\b)/g,"$1");
	if (strw.match(/all\s*real/i)) {
    return ['(-oo,oo)'];
  } else if (strw.match(/DNE/)) {
    return ['DNE'];
  }
  var pat, interval, out = [];
  var strpts = strw.split(/\s*or\s*/);
	for (var i=0; i<strpts.length; i++) {
		str = strpts[i];
    if (pat = str.match(/^(.*?)(<=?|>=?)(.*?)(<=?|>=?)(.*?)$/)) {
      if (simplifyVariable(pat[3]) != simpvar) { // wrong var
        return ['', 'wrongvar'];
      } else if (pat[2].charAt(0) != pat[4].charAt(0)) { // mixes > and <
        return ['', 'invalid'];
      } else if (pat[1].trim()=='' || pat[5].trim()=='') {
        return ['', 'invalid'];
      }
      if (pat[2].charAt(0)=='<') {
        interval = (pat[2]=='<'?'(':'[') + pat[1] + ',' + pat[5] + (pat[4]=='<'?')':']');
      } else {
        interval = (pat[4]=='>'?'(':'[') + pat[5] + ',' + pat[1] + (pat[2]=='>'?')':']');
      }
      out.push(interval);
    } else if (pat = str.match(/^(.*?)(<=?|>=?)(.*?)$/)) {
      if (simplifyVariable(pat[1])== simpvar) { // x> or x<
        if (pat[2].charAt(0)=='<') { // x<
          interval = '(-oo,' + pat[3] + (pat[2]=='<'?')':']');
        } else { // x>
          interval = (pat[2]=='>'?'(':'[') + pat[3] + ',oo)';
        }
        out.push(interval);
      } else if (simplifyVariable(pat[3])== simpvar) { // 3<x or 3>x
        if (pat[2].charAt(0)=='<') { // 3<x
          interval = (pat[2]=='<'?'(':'[') + pat[1] + ',oo)';
        } else { // x>
          interval = '(-oo,' + pat[1] + (pat[2]=='>'?')':']');
        }
        out.push(interval);
      } else {
        return ['', 'wrongvar'];
      }
    } else {
      return ['', 'invalid'];
    }
  }
  return [out.join("U")];
}

function parsecomplex(v) {
	var real,imag,c,nd,p,R,L;
	v = v.replace(/\s/,'');
	v = v.replace(/\((\d+\*?i|i)\)\/(\d+)/g,'$1/$2');
	v = v.replace(/sin/,'s$n');
	v = v.replace(/pi/,'p$');
	var len = v.length;
	//preg_match_all('/(\bi|i\b)/',v,matches,PREG_OFFSET_CAPTURE);
	//if (count(matches[0])>1) {
	if (v.split("i").length>2) {
		return _('error - more than 1 i in expression');
	} else {
		p = v.indexOf('i');
		if (p==-1) {
			real = v;
			imag = "0";
		} else {
			//look left
			nd = 0;
			for (L=p-1;L>0;L--) {
				c = v.charAt(L);
				if (c==')') {
					nd++;
				} else if (c=='(') {
					nd--;
				} else if ((c=='+' || c=='-') && nd==0) {
					break;
				}
			}
			if (L<0) {L=0;}
			if (nd != 0) {
				return _('error - invalid form');
			}
			//look right
			nd = 0;

			for (R=p+1;R<len;R++) {
				c = v.charAt(R);
				if (c=='(') {
					nd++;
				} else if (c==')') {
					nd--;
				} else if ((c=='+' || c=='-') && nd==0) {
					break;
				}
			}
			if (nd != 0) {
				return _('error - invalid form');
			}
			//which is bigger?
			if (p-L>0 && R-p>0 && (R==len || L==0)) {
				if (R==len) { //real + AiB
					real = v.substr(0,L);
					imag = v.substr(L,p-L);
				} else if (L==0) {
					real = v.substr(R);
					imag = v.substr(0,p);
				} else {
					return _('error - invalid form');
				}
				imag += '*'+v.substr(p+1+(v.charAt(p+1)=='*'?1:0),R-p-1);
				imag = imag.replace("-*","-1*").replace("+*","+1*");
				imag = imag.replace(/(\+|-)1\*(.+)/g,'$1$2');
			} else if (p-L>1) {
				imag = v.substr(L,p-L);
				real = v.substr(0,L) + v.substr(p+1);
			} else if (R-p>1) {
				if (p>0) {
					if (v.charAt(p-1)!='+' && v.charAt(p-1)!='-') {
						return _('error - invalid form');
					}
					imag = v.charAt(p-1)+ v.substr(p+1+(v.charAt(p+1)=='*'?1:0),R-p-1);
					real = v.substr(0,p-1) + v.substr(R);
				} else {
					imag = v.substr(p+1,R-p-1);
					real = v.substr(0,p) + v.substr(R);
				}
			} else { //i or +i or -i or 3i  (one digit)
				if (v.charAt(L)=='+') {
					imag = "1";
				} else if (v.charAt(L)=='-') {
					imag = "-1";
				} else if (p==0) {
					imag = "1";
				} else {
					imag = v.charAt(L);
				}
				real = (p>0?v.substr(0,L):'') + v.substr(p+1);
			}
			if (real=='') {
				real = "0";
			}
			if (imag.charAt(0)=='/') {
				imag = '1'+imag;
			} else if ((imag.charAt(0)=='+' || imag.charAt(0)=='-') && imag.charAt(1)=='/') {
				imag = imag.charAt(0)+'1'+imag.substr(1);
			}
			if (imag.charAt(imag.length-1)=='*') {
				imag = imag.substr(0,imag.length-1);
			}
			if (imag.charAt(0)=="+") {
				imag = imag.substr(1);
			}
			if (real.charAt(0)=="+") {
				real = real.substr(1);
			}
		}
		real = real.replace("s$n","sin");
		real = real.replace("p$","pi");
		imag = imag.replace("s$n","sin");
		imag = imag.replace("p$","pi");
		imag = imag.replace(/\*\//g,"/");
		return [real,imag];
	}
}

var onlyAscii = /^[\u0000-\u007f]*$/;

function singlevalsyntaxcheck(str,format) {
  str = str.replace(/(\d)\s*,\s*(?=\d{3}\b)/g,"$1");
	if (str.match(/DNE/i)) {
		 return '';
	} else if (str.match(/-?\+?oo$/) || str.match(/-?\+?oo\W/)) {
		 return '';
	} else if (str.match(/,/)) {
    return _("Invalid use of a comma.");
  } else if (format.indexOf('allowmixed')!=-1 &&
		str.match(/^\s*\-?\s*\d+\s*(_|\s)\s*(\d+|\(\d+\))\s*\/\s*(\d+|\(\d+\))\s*$/)) {
		//if allowmixed and it's mixed, stop checking
		return '';
	} else if (format.indexOf('fracordec')!=-1) {
		  str = str.replace(/([0-9])\s+([0-9])/g,"$1*$2").replace(/\s/g,'');
		  if (!str.match(/^\-?\(?\d+\s*\/\s*\-?\d+\)?$/) && !str.match(/^\-?\d+$/) && !str.match(/^\-?(\d+|\d+\.\d*|\d*\.\d+)$/)) {
			return (_(" invalid entry format")+". ");
		  }
	} else if (format.indexOf('fraction')!=-1 || format.indexOf('reducedfraction')!=-1) {
		  str = str.replace(/([0-9])\s+([0-9])/g,"$1*$2").replace(/\s/g,'');
		 // if (!str.match(/^\s*\-?\(?\d+\s*\/\s*\-?\d+\)?\s*$/) && !str.match(/^\s*?\-?\d+\s*$/)) {
		  if (!str.match(/^\(?\-?\(?\d+\)?\/\(?\d+\)?$/) && !str.match(/^\(?\d+\)?\/\(?\-?\d+\)?$/) && !str.match(/^\s*?\-?\d+\s*$/)) {
			return (_("not a valid fraction")+". ");
		  }
	} else if (format.indexOf('mixednumber')!=-1) {
		  if (!str.match(/^\(?\-?\s*\(?\d+\)?\/\(?\d+\)?$/) && !str.match(/^\(?\d+\)?\/\(?\-?\d+\)?$/) && !str.match(/^\s*\-?\s*\d+\s*(_|\s)\s*(\d+|\(\d+\))\s*\/\s*(\d+|\(\d+\))\s*$/) && !str.match(/^\s*\-?\s*\d+\s*$/)) {
			return (_("not a valid mixed number")+". ");
		  }
		  str = str.replace(/_/,' ');
	} else if (format.indexOf('scinot')!=-1) {
		  str = str.replace(/\s/g,'');
		  str = str.replace(/(xx|x|X|\u00D7)/,"xx");
		  if (!str.match(/^\-?[1-9](\.\d*)?(\*|xx)10\^(\(?\-?\d+\)?)$/)) {
		  	if (format.indexOf('scinotordec')==-1) { //not scinotordec
		  		return (_("not valid scientific notation")+". ");
		  	} else if (!str.match(/^\-?(\d+|\d+\.\d*|\d*\.\d+)$/)) {
		  		return (_("not valid decimal or scientific notation")+". ");
		  	}
		  }
	} else if (format.indexOf('decimal')!=-1 && format.indexOf('nodecimal')==-1) {
		if (!str.match(/^\-?(\d+|\d+\.\d*|\d*\.\d+)([eE]\-?\d+)?$/)) {
			return (_(" not a valid integer or decimal number")+". ");
		}
	} else if (!onlyAscii.test(str)) {
		return _("Your answer contains an unrecognized symbol")+". ";
  	}
	return '';
}

function syntaxcheckexpr(str,format,vl) {
	  var err = '';
	  if (format.indexOf('notrig')!=-1 && str.match(/(sin|cos|tan|cot|sec|csc)/i)) {
		  err += _("no trig functions allowed")+". ";
	  } else if (format.indexOf('nodecimal')!=-1 && str.indexOf('.')!=-1) {
		  err += _("no decimals allowed")+". ";
	  } else if (format.indexOf('mixed')==-1 &&
		str.match(/\-?\s*\d+\s*(_|\s)\s*(\d+|\(\d+\))\s*\/\s*(\d+|\(\d+\))/)) {
		err += _("mixed numbers are not allowed")+". ";
	  } else if (format.indexOf('allowdegrees')==-1 && str.match(/degree/)) {
        err += _("no degree symbols allowed")+". ";
      } 
	  var Pdepth = 0; var Bdepth = 0; var Adepth = 0;
	  for (var i=0; i<str.length; i++) {
		if (str.charAt(i)=='(') {
			Pdepth++;
		} else if (str.charAt(i)==')') {
			Pdepth--;
		} else if (str.charAt(i)=='[') {
			Bdepth++;
		} else if (str.charAt(i)==']') {
			Bdepth--;
		} else if (str.charAt(i)=='|') {
			Adepth = 1-Adepth;
		}
	  }
	  if (Pdepth!=0 || Bdepth!=0) {
		  err += " ("+_("unmatched parens")+"). ";
	  }
	  if (Adepth!=0) {
	  	  err += " ("+_("unmatched absolute value bars")+"). ";
	  }
	  if (vl) {
	  	  reg = new RegExp("(sqrt|ln|log|exp|sinh|cosh|tanh|sech|csch|coth|sin|cos|tan|sec|csc|cot|abs)\s*("+vl+"|\\d+)", "i");
	  } else {
	  	  reg = new RegExp("(sqrt|ln|log|exp|sinh|cosh|tanh|sech|csch|coth|sin|cos|tan|sec|csc|cot|abs)\s*(\\d+)", "i");
	  }
	  errstuff = str.match(reg);
	  if (errstuff!=null) {
		  err += "["+_("use function notation")+" - "+_("use $1 instead of $2",errstuff[1]+"("+errstuff[2]+")",errstuff[0])+"]. ";
	  }
	  if (vl) {
	  	  reg = new RegExp("(repvars\\d+|degree|arc|sqrt|root|ln|log|exp|sinh|cosh|tanh|sech|csch|coth|sin|cos|tan|sec|csc|cot|abs|pi|sign|DNE|e|oo|"+vl+")", "ig");
	  	  if (str.replace(reg,'').match(/[a-zA-Z]/)) {
	  	  	err += _(" Check your variables - you might be using an incorrect one")+". ";
	  	  }
	  }
	  if ((str.match(/\|/g)||[]).length>2) {
	  	  var regex = /\|.*?\|\s*(.|$)/g;
	  	  while (match = regex.exec(str)) {
	  	  	if (match[1]!="" && match[1].match(/[^+\-\*\/\^\)]/)) {
	  	  		err += _(" You may want to use abs(x) instead of |x| for absolute values to avoid ambiguity")+". ";
	  	  		break;
	  	  	}
	  	  }
	  }
	  if (str.match(/%/) && !str.match(/^\s*[+-]?\s*((\d+(\.\d*)?)|(\.\d+))\s*%\s*$/)) {
	  	  err += _(" Do not use the percent symbol, %")+". ";
	  }
	  return err;
}

// returns [numval, errmsg]
function singlevaleval(evalstr, format) {
  evalstr = evalstr.replace(/,/g, '');
  if (evalstr.match(/^\s*[+-]?\s*((\d+(\.\d*)?)|(\.\d+))\s*%\s*$/)) {//single percent
    evalstr = evalstr.replace(/%/g,'') + '/100';
  }
  if (format.indexOf('mixed')!=-1) {
    evalstr = evalstr.replace(/(\d+)\s+(\d+|\(\d+\))\s*\/\s*(\d+|\(\d+\))/g,"($1+$2/$3)");
  }
  if (format.indexOf('allowxtimes')!=-1) {
    evalstr = evalstr.replace(/(xx|x|X|\u00D7)/,"*");  
  }
  if (format.indexOf('scinot')!=-1) {
      evalstr = evalstr.replace("xx","*");
  }
  try {
    var res = scopedmatheval(evalstr);
    if (res === '') {
      return [NaN, _("syntax incomplete")+". "];
    }
    return [res, ''];
  } catch(e) {
    return [NaN, _("syntax incomplete")+". "];
  }
}

function escapeRegExp(string) {
  return string.replace(/[.*+\-?^${}()|[\]\\]/g, '\\$&'); // $& means the whole matched string
}

function scopedeval(c) {
	try {
		return eval(c);
	} catch(e) {
		return "synerr";
	}
}

function scopedmatheval(c) {
	if (c.match(/^\s*[a-df-zA-Z]\s*$/)) {
		return '';
	}
	try {
		return eval(prepWithMath(mathjs(c)));
	} catch(e) {
		return '';
	}
}

return {
  init: init,
  clearparams: clearparams,
  preSubmitForm: preSubmitForm,
  preSubmit: preSubmit,
  preSubmitString: preSubmitString,
  clearLivePreviewTimeouts: clearLivePreviewTimeouts,
  syntaxCheckMQ: syntaxCheckMQ,
  clearTips: clearTips,
  handleMQenter: handleMQenter
};

}(jQuery));

// need in global scope for drawing
function prepWithMath(str) {
	str = str.replace(/\b(abs|acos|asin|atan|ceil|floor|cos|sin|tan|sqrt|exp|max|min|pow)\(/g, 'Math.$1(');
	str = str.replace(/\(E\)/g,'(Math.E)');
	str = str.replace(/\((PI|pi)\)/g,'(Math.PI)');
	return str;
}

function toggleinlinebtn(n,p){ //n: target, p: click el
	var btn = document.getElementById(p);
	var el=document.getElementById(n);
	if (el.style.display=="none") {
		el.style.display="";
		el.setAttribute("aria-hidden",false);
		btn.setAttribute("aria-expanded",true);
	} else {
		el.style.display="none";
		el.setAttribute("aria-hidden",true);
		btn.setAttribute("aria-expanded",false);
	}
	var k=btn.innerHTML;
	btn.innerHTML = k.match(/\[\+\]/)?k.replace(/\[\+\]/,'[-]'):k.replace(/\[\-\]/,'[+]');
}


/*******************************************************

AutoSuggest - a javascript automatic text input completion component
Copyright (C) 2005 Joe Kepley, The Sling & Rock Design Group, Inc.

This library is free software; you can redistribute it and/or
modify it under the terms of the GNU Lesser General Public
License as published by the Free Software Foundation; either
version 2.1 of the License, or (at your option) any later version.

This library is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public
License along with this library; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*******************************************************

Please send any useful modifications or improvements via
email to joekepley at yahoo (dot) com

*******************************************************/

function AutoSuggest(elem, suggestions)
{

	//The 'me' variable allow you to access the AutoSuggest object
	//from the elem's event handlers defined below.
	var me = this;

	//A reference to the element we're binding the list to.
	this.elem = elem;

	this.suggestions = suggestions;

	//Arrow to store a subset of eligible suggestions that match the user's input
	this.eligible = new Array();

	//The text input by the user.
	this.inputText = null;

	//A pointer to the index of the highlighted eligible item. -1 means nothing highlighted.
	this.highlighted = -1;

	//A div to use to create the dropdown.
	this.div = document.getElementById("autosuggest");
	if (this.div == null) {
		this.div = document.createElement("div");
		this.div.id = "autosuggest";
		document.getElementsByTagName('body')[0].appendChild(this.div);
		this.div.appendChild(document.createElement("ul"));
	}


	//Do you want to remember what keycode means what? Me neither.
	var TAB = 9;
	var ESC = 27;
	var KEYUP = 38;
	var KEYDN = 40;
	var ENTER = 13;


	//The browsers' own autocomplete feature can be problematic, since it will
	//be making suggestions from the users' past input.
	//Setting this attribute should turn it off.
	elem.setAttribute("autocomplete","off");

	//We need to be able to reference the elem by id. If it doesn't have an id, set one.
	if(!elem.id)
	{
		var id = "autosuggest" + AutoSuggestIdCounter;
		AutoSuggestIdCounter++;

		elem.id = id;
	}


	/********************************************************
	onkeydown event handler for the input elem.
	Tab key = use the highlighted suggestion, if there is one.
	Esc key = get rid of the autosuggest dropdown
	Up/down arrows = Move the highlight up and down in the suggestions.
	********************************************************/
	elem.onkeydown = function(ev)
	{
		var key = me.getKeyCode(ev);

		switch(key)
		{
			case TAB:
			me.useSuggestion("tab");
			break;

			case ENTER:
            if (me.highlighted > -1) {
                ev.stopImmediatePropagation();
            }
			me.useSuggestion("enter");
			return false;
			break;

			case ESC:
			me.hideDiv();
			break;

			case KEYUP:
			if (me.highlighted > 0)
			{
				me.highlighted--;
			}
			me.changeHighlight(key);
			break;

			case KEYDN:
			if (me.highlighted < (me.eligible.length - 1))
			{
				me.highlighted++;
			}
			me.changeHighlight(key);
			break;
		}
	};

	/********************************************************
	onkeyup handler for the elem
	If the text is of sufficient length, and has been changed,
	then display a list of eligible suggestions.
	********************************************************/
	elem.onkeyup = function(ev)
	{
		var key = me.getKeyCode(ev);
		switch(key)
		{
		//The control keys were already handled by onkeydown, so do nothing.
		case TAB:
		case ESC:
		case KEYUP:
		case KEYDN:
			return;
		default:

			if (this.value.length > 1) //this.value != me.inputText &&
			{
				me.inputText = this.value;
				me.getEligible();
				if (me.eligible.length>0) {
					me.highlighted = 0;
				} else {
					me.highlighted = -1;
				}
				me.createDiv();
				me.positionDiv();
				me.showDiv();
			}
			else
			{
				me.hideDiv();
				if (this.value.length==0) {
					me.inputText = '';
				}
			}
		}
	};
	elem.onblur = function(ev) {
        //setTimeout(me.hideDiv,100);
        me.hideDiv();
	}



	/********************************************************
	Insert the highlighted suggestion into the input box, and
	remove the suggestion dropdown.
	********************************************************/
	this.useSuggestion = function(how)
	{
		if (this.highlighted > -1)
		{
			this.elem.value = this.eligible[this.highlighted];
			this.hideDiv();
		} else {
			//this.elem.value = '';
			this.hideDiv();
		}
	};

	/********************************************************
	Display the dropdown. Pretty straightforward.
	********************************************************/
	this.showDiv = function()
	{
		this.div.style.display = 'block';
	};

	/********************************************************
	Hide the dropdown and clear any highlight.
	********************************************************/
	this.hideDiv = function()
	{
		me.div.style.display = 'none';
		me.highlighted = -1;
	};

	/********************************************************
	Modify the HTML in the dropdown to move the highlight.
	********************************************************/
	this.changeHighlight = function()
	{
		var lis = this.div.getElementsByTagName('LI');
		for (i in lis)
		{
			var li = lis[i];
			if (this.highlighted == i)
			{
				li.className = "selected";
			}
			else
			{
				li.className = "";
			}

		}
	};

	/********************************************************
	Position the dropdown div below the input text field.
	********************************************************/
	this.positionDiv = function()
	{
		var el = this.elem;
		var pos = findPos(el);
		pos[1] += el.offsetHeight;

		this.div.style.left = pos[0] + 'px';
		this.div.style.top = pos[1] + 'px';
	};

	/********************************************************
	Build the HTML for the dropdown div
	********************************************************/
	this.createDiv = function()
	{
		var ul = document.createElement('ul');

		//Create an array of LI's for the words.
		for (i in this.eligible)
		{
			var word = this.eligible[i];

			var li = document.createElement('li');
			var a = document.createElement('a');
			a.href="#";//javascript:false;";
			a.onclick= function() {return false;}
			a.innerHTML = word;
			li.appendChild(a);

			if (me.highlighted == i)
			{
				li.className = "selected";
			}

			ul.appendChild(li);
		}

		this.div.replaceChild(ul,this.div.childNodes[0]);


		/********************************************************
		mouseover handler for the dropdown ul
		move the highlighted suggestion with the mouse
		********************************************************/
		ul.onmouseover = function(ev)
		{
			//Walk up from target until you find the LI.
			var target = me.getEventSource(ev);
			while (target.parentNode && target.tagName.toUpperCase() != 'LI')
			{
				target = target.parentNode;
			}

			var lis = me.div.getElementsByTagName('LI');


			for (i in lis)
			{
				var li = lis[i];
				if(li == target)
				{
					me.highlighted = i;
					break;
				}
			}
			me.changeHighlight();
		};

		/********************************************************
		click handler for the dropdown ul
		insert the clicked suggestion into the input
        ********************************************************/
        
		ul.onmousedown = ul.ontouchstart = function(ev)
		{
			me.useSuggestion("click");
			me.hideDiv();
			me.cancelEvent(ev);
			return false;
		};

		this.div.className="suggestion_list";
		this.div.style.position = 'absolute';

	};

	/********************************************************
	determine which of the suggestions matches the input
	********************************************************/
	this.getEligible = function()
	{
		this.eligible = new Array();
		var added = ',';
		if (this.inputText.indexOf(" ") == -1) {
			var bndreg = new RegExp("\\b"+this.inputText.toLowerCase());
			for (i in this.suggestions)
			{
				var suggestion = this.suggestions[i];
				if(suggestion.toLowerCase().match(bndreg))
				{
					this.eligible[this.eligible.length]=suggestion;
					added += i+',';
				}
			}
		}
		/*for (i in this.suggestions)
		{
			var suggestion = this.suggestions[i];

			if(suggestion.toLowerCase().indexOf(this.inputText.toLowerCase()) >-1 && added.indexOf(','+i+',')<0)
			{
				this.eligible[this.eligible.length]=suggestion;
			}
		}*/
	};

	/********************************************************
	Helper function to determine the keycode pressed in a
	browser-independent manner.
	********************************************************/
	this.getKeyCode = function(ev)
	{
		if(ev)			//Moz
		{
			return ev.keyCode;
		}
		if(window.event)	//IE
		{
			return window.event.keyCode;
		}
	};

	/********************************************************
	Helper function to determine the event source element in a
	browser-independent manner.
	********************************************************/
	this.getEventSource = function(ev)
	{
		if(ev)			//Moz
		{
			return ev.target;
		}

		if(window.event)	//IE
		{
			return window.event.srcElement;
		}
	};

	/********************************************************
	Helper function to cancel an event in a
	browser-independent manner.
	(Returning false helps too).
	********************************************************/
	this.cancelEvent = function(ev)
	{
		if(ev)			//Moz
		{
			ev.preventDefault();
			ev.stopPropagation();
		}
		if(window.event)	//IE
		{
			window.event.returnValue = false;
		}
	}
}

//counter to help create unique ID's
var AutoSuggestIdCounter = 0;
var autoSuggestLists = {};
var autoSuggestObjects = {};

//override document.write to prevent errors
document.write = function() {};

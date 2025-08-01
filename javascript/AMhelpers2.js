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
var scriptqueue = [];
var processingscriptsqueue = false;
var callbackstack = {};

if (typeof commasep === 'undefined') {
  commasep = true;
}

var imathasAssess = (function($) {

var allParams = {};
var allKekule = {};

function clearparams(paramarr) {
  var qn;
  for (qn in paramarr) {
    delete allParams[qn];
  }
}

function toMQwVars(str, elid) {
    var qn = elid.substr(2).split(/-/)[0];
    var qtype = allParams[qn].qtype;
    if (qtype === 'numfunc' || (qtype === 'calcinterval' && allParams[qn].calcformat.indexOf('inequality')!=-1)) {
        str = AMnumfuncPrepVar(qn, str)[1];
    }
    var nomatrices = !(qtype.match(/matrix/) || qtype === 'string');
    return AMtoMQ(str, elid, nomatrices);
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

    if (params.helper && params.qtype.match(/^(calc|numfunc|string|interval|matrix|chemeqn|complexmatrix|alg)/)) { //want mathquill
      el = document.getElementById("qn"+qn);
      if (!el && !params.matrixsize) { continue; }
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
        if (params.vars) {
            $("input[id^=qn"+qn+"-]").attr("data-mq-vars", params.vars);
        }
      } else {
        el.setAttribute("data-mq", str);
        if (params.vars) {
          el.setAttribute("data-mq-vars", params.vars);
        }
      }

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
      }  //TODO: when matrix, clear preview on further input
    } else if (params.matrixsize) {
        $("input[id^=qn"+qn+"-]").on('input', (function(thisqn) { 
          return function () {syntaxCheckMQ(thisqn) }; })(qn));
    } else if (document.getElementById("qn"+qn)) {
        document.getElementById("qn"+qn).addEventListener('keyup', (function(thisqn) { 
            return function () {syntaxCheckMQ(thisqn) }; })(qn));
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
    if (params.qtype === 'molecule') { // do after scripts are loaded
      initMolecule(qn);
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
        var extendsetup = {};
        if (params.nopaste) {
            extendsetup['paste_preprocess'] = function(plugin, args) { args.content = '';}
        }
        extendsetup['valid_classes'] = 'gridded,centered,attach';
        initeditor("selector","#qn" + qn + ".mceEditor",null,false,function(ed) {
          ed.on('blur', function (e) {
            tinymce.triggerSave();
            jQuery(e.target.targetElm).triggerHandler('change');
          }).on('focus', function (e) {
            jQuery(e.target.targetElm).triggerHandler('focus');
          })
        }, extendsetup);
      }
    }
    if (params.qtype == 'essay') {
      $("#qnwrap"+qn+".introtext img").on('click', rotateimg);
    }
    initEnterHandler(qn);
    $("input[id^=qn"+qn+"]:not([type=file])").attr("maxlength",8000);
  }
  initDupRubrics();
  initShowAnswer2();
  if (baseel) {
    setScoreMarkers(baseel);
  }
  initqsclickchange();
  initClearScoreMarkers();
  
  if (paramarr.scripts) {
    for (var i in paramarr.scripts) {
        if (paramarr.scripts[i][0] == 'code') {
            scriptqueue.push(paramarr.scripts[i]);
        } else if (loadedscripts.indexOf(paramarr.scripts[i][1]) == -1) {
            scriptqueue.push(paramarr.scripts[i]);
            loadedscripts.push(paramarr.scripts[i][1]);
        }
    }
    if (scriptqueue.length > 0 && processingscriptsqueue === false) {
        processScriptQueue();
    }
  }
}

function processScriptQueue() {
    processingscriptsqueue = true;
    if (scriptqueue.length == 0) { return; }
    var nextscript = scriptqueue.shift();

    if (nextscript[0] == 'code') {
      try {
        window.eval(nextscript[1]);
      } catch (e) { console.log("Error executing question script:" + nextscript[1]);}
      processScriptQueueNext()
    } else {
      jQuery.getScript(nextscript[1]).always(function() { // force sync
        processScriptQueueNext()
      });
    }
}
function processScriptQueueNext() {
    for (var i=0; i<initstack.length; i++) {
        var foo = initstack[i]();
    }
    initstack.length = 0;
    if (scriptqueue.length == 0) {
        processingscriptsqueue = false;
    } else {
        processScriptQueue();
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
    var inbox = $("#mqinput-qn"+inref+",input[type=text]#qn"+inref+",select#qn"+inref+",textarea#qn"+inref+",div.introtext#qnwrap"+inref);
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
          $("#ans"+qref).toggleClass("hidden", curstate);
          if (!curstate) {
            $("#ans"+qref).attr("tabindex","-1").focus();
          }
          sendLTIresizemsg();
        })
        .html(icon)
    );
    if ($(el).closest('.autoshowans').length > 0) {
      var wrap = $("#qnwrap"+inref);
      if (wrap.length > 0) {
        $(el).prev(".sabtn").remove();
        key.append($(el))
          .addClass("inwrap");
        wrap.append(key);
        return;
      }
      var inbox = $("#mqinput-qn"+inref+",input[type=text]#qn"+inref+",select#qn"+inref+",textarea#qn"+inref);
      if (inbox.length > 0) {
        $(el).prev(".sabtn").remove();
        key.append($(el));
        inbox.after(key);
        return;
      }
    }
    // not in autoshowans or no match, so don't want to relocate, just refresh
    var parel = $(el).parent();
    key.append($(el));
    parel.empty().append(key);
  });

  // setup detailed solutions button the old way
  $("input.dsbtn + div.hidden").attr("aria-hidden",true).attr("aria-expanded",false);
	$("input.dsbtn").each(function() {
		var idnext = $(this).siblings("div:first-of-type").attr("id");
		$(this).attr("aria-expanded",false).attr("aria-controls",idnext)
		  .off("click.sashow").on("click.sashow", function() {
            var curstate = ($(this).attr("aria-expanded") == 'true');
			$(this).attr("aria-expanded",!curstate)
		  	  .siblings("div:first-of-type")
				.attr("aria-expanded",!curstate).attr("aria-hidden",curstate)
				.toggleClass("hidden",curstate);
            sendLTIresizemsg();
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
  $("#qn"+qn).parent().find(".a11ydrawadd:not(.inited)").off("click.adda11ydraw").on("click.adda11ydraw", function(event) {
    var qn = event.target.getAttribute('data-qn');
    $(event.target).addClass("inited");
    imathasDraw.adda11ydraw(qn);
  });
}

function initMultAns(qn) {
  var hasnone = $("#qnwrap"+qn).attr('data-multans') == 'hasnone';
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

function initMolecule(qn) {
  if (typeof Kekule === 'undefined') {
    var curqn = qn;
    setTimeout( function(){ initMolecule(curqn);}.bind(this), 100);
    return;
  }
  // ref: https://partridgejiang.github.io/Kekule.js/documents/tutorial/examples/composer.html
  allKekule[qn] = new Kekule.Editor.Composer(document.getElementById("chemdraw" + qn));
  allKekule[qn]
    .setEnableOperHistory(true)
    .setEnableLoadNewFile(false)
    .setEnableCreateNewDoc(false)
    .setAllowCreateNewChild(false)
    .setCommonToolButtons(["undo", "redo", "copy", "cut", "paste", "zoomIn", "reset", "zoomOut", ]) 
    .setChemToolButtons(["manipulate", "erase", "bond", "atomAndFormula", "ring", "charge"])
    .setStyleToolComponentNames([]);
  if (allParams[qn].displayformat === 'condensed') {
    var renderconfig = new Kekule.Render.Render2DConfigs();
    renderconfig.getMoleculeDisplayConfigs().setDefMoleculeDisplayType(Kekule.Render.MoleculeDisplayType.CONDENSED);
    allKekule[qn].setRenderConfigs(renderconfig);
  }
  allKekule[qn].getEditor().on('editObjsUpdated', function(e) {
    processMolecule(qn);
  });
  if (allParams[qn].chemla) {
    allKekule[qn].setChemObj(Kekule.IO.loadFormatData(allParams[qn].chemla, "cml"));
    window.setTimeout(function () {
      let editor = allKekule[qn].getEditor();  // suppose this.widget is a composer
      editor.scrollClientToObject(editor.getChemObj().getChildren());  // centers the current loaded molecules (childen of chemDocument)
    }, 100);


    /*  
    Notes:
    
Hi @sowiso, you can change the default size of chem document by the config object of editor:

composer.getEditorConfigs().getChemSpaceConfigs().setDefScreenSize2D({x: 600, y: 400});
composer.newDoc();
Or using the changeChemSpaceScreenSize method to modify the screen size of an opened document:

composer.getEditor().changeChemSpaceScreenSize({x: 600, y: 400});


The following code may help to shrink the large structures to fit the client of composer:

var editor = composer.getEditor();
var objBox = editor.getObjectsContainerBox(editor.getChemSpace().getChildren());
var visualBox = chemEditor.getVisibleClientScreenBox();
if (objBox && visualBox)
{
  var sx = (visualBox.x2 - visualBox.x1) / (objBox.x2 - objBox.x1);
  var sy = (visualBox.y2 - visualBox.y1) / (objBox.y2 - objBox.y1);
  var ratio = Math.min(sx, sy);
  if (ratio < 1)
  {
    chemEditor.setZoom(chemEditor.getCurrZoom() * ratio);
    chemEditor.scrollClientToObject(chemEditor.getChemSpace().getChildren());
  }
}



    */
    
  }
  var SAel = document.getElementById('chemsa' + qn);
  if (SAel) { // has show answer el
    var chemSAViewer = new Kekule.ChemWidget.Viewer(SAel, null, Kekule.Render.RendererType.R2D);
    chemSAViewer.setEnableToolbar(false)
      .setPadding(20)
      .setChemObj(Kekule.IO.loadFormatData(SAel.getAttribute('data-cmldata'), "cml"));
    if (allParams[qn].displayformat === 'condensed') {
      chemSAViewer.setMoleculeDisplayType(Kekule.Render.Molecule2DDisplayType.CONDENSED);
    }
  }
}

function isBlank(str) {
	return (!str || 0 === str.length || /^\s*$/.test(str));
}
function editdebit(e) {
  var el = e.target;
	//var descr = $('#qn'+(el.id.substr(2)*1 - 1));
	var descr = $(el).closest('tr').find("input").first();
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
	//var descr = $('#qn'+(el.id.substr(2)*1 - 2));
	var descr = $(el).closest('tr').find("input").first();
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
    if (mathRenderer=="MathJax" && (!window.MathJax || (!window.MathJax.Hub && !window.MathJax.typesetPromise))) {
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
              mjPromise: null,

			  //
			  //  Get the preview and buffer DIV's
			  //
			  Init: function(skipinitial) {
  				$("#p"+qn).css("positive","relative")
  					.append('<span id="lpbuf1'+qn+'" style="visibility:hidden;position:absolute;"></span>')
  					.append('<span id="lpbuf2'+qn+'" style="visibility:hidden;position:absolute;"></span>');
  				this.preview = document.getElementById("lpbuf1"+qn);
  				this.buffer = document.getElementById("lpbuf2"+qn);
                if (mathRenderer=="MathJax" && MathJax.typesetPromise) {
                    this.mjPromise = Promise.resolve();
                }
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

			  RenderNow: function(text, formatted) {
				  //called by preview button
            this.oldtext = text;
			      this.buffer.innerHTML = formatted ? text : this.preformat(text);
			      this.mjRunning = true;
			      this.RenderBuffer();
			  },
			  RenderBuffer: function() {
			      if (mathRenderer=="MathJax") {
                      if (MathJax.typesetPromise && this.mjPromise) {
                        this.mjPromise = this.mjPromise.then(function () {
                            //MathJax.typesetClear([this.buffer]);
                            MathJax.typesetPromise([this.buffer]).then(this.PreviewDone.bind(this));
                        }.bind(this));
                      } else if (parseInt(MathJax.version)===2) {
                        MathJax.Hub.Queue(
                            ["Typeset",MathJax.Hub,this.buffer],
                            ["PreviewDone",this]
                        );
                      } 
			      } else if (mathRenderer=="Katex") {
			      	  renderMathInElement(this.buffer);
				      if (typeof MathJax != "undefined" && MathJax.Hub && !MathJax.typesetPromise && $(this.buffer).children(".mj").length>0) {//has MathJax elements
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
                  if (this.mjPromise) {
                    this.mjPromise = this.mjPromise.then(this.CreatePreview().bind(this));
                  } else if (MathJax.Hub && parseInt(MathJax.version)===2) {
                    MathJax.Hub.Queue(["CreatePreview",this]);
                  }
			    } else {
			      this.oldtext = text;
			      this.buffer.innerHTML = "`"+htmlEntities(this.preformat(text))+"`";
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
            if (typeof MathJax != "undefined" && !MathJax.typesetPromise) {
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
				      outnode.innerHTML = htmlEntities(text);
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
	str = str.replace(/\b(OO|infty)\b/gi,"oo").replace(/°/g,'degree');
	str = str.replace(/θ/g,"theta").replace(/ϕ/g,"phi").replace(/φ/g,"phi").replace(/π/g,"pi").replace(/σ/g,"sigma").replace(/μ/g,"mu")
	str = str.replace(/α/g,"alpha").replace(/β/g,"beta").replace(/γ/g,"gamma").replace(/δ/g,"delta").replace(/ε/g,"epsilon").replace(/κ/g,"kappa");
	str = str.replace(/λ/g,"lambda").replace(/ρ/g,"rho").replace(/τ/g,"tau").replace(/χ/g,"chi").replace(/ω/g,"omega");
	str = str.replace(/Ω/g,"Omega").replace(/Γ/g,"Gamma").replace(/Φ/g,"Phi").replace(/Δ/g,"Delta").replace(/Σ/g,"Sigma");
    str = str.replace(/&(ZeroWidthSpace|nbsp);/g, ' ').replace(/\u200B/g, ' ');
    str = str.replace(/degree\s+s\b/g,'degree');
    // remove extra parens on numbers, like roots and logs
    str = str.replace(/\(\((-?\d+)\)\)/g, '($1)');
	return str;
}

function htmlEntities(str) {
  return str.replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/&/g,'&amp;');
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
    outstr += '`' + htmlEntities(res.str) + '`';
  }
  if (res.dispvalstr && res.dispvalstr != '' && params.calcformat.indexOf('showval')!=-1) {
    outstr += (outstr==''?'':' &asymp; ') + '`' + htmlEntities(res.dispvalstr) + '`';
  }
  if (res.err && res.err != '' && res.str != '') {
    outstr += (outstr=='``')?'':'. ' + '<span class=noticetext>' + res.err + '</span>';
  }
  if (LivePreviews.hasOwnProperty(qn)) {
    LivePreviews[qn].RenderNow(outstr, true);
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
  var qn = parseInt(id.replace(/.*qn(\d+)\b.*/g,'$1'));
  MQsyntaxtimer = setTimeout(function() { showSyntaxCheckMQ(qn);}, 1000);
}

function showSyntaxCheckMQ(qn) {
  var params = allParams[qn];
  var res = processByType(qn);
  var outstr = '';
  if (res.dispvalstr && res.dispvalstr != '' && res.dispvalstr != 'NaN' && params.calcformat && params.calcformat.indexOf('showval')!=-1) {
    outstr += '`' + htmlEntities(res.str) + '`';
    if (params.qtype == 'calcmatrix' || params.qtype == 'calccomplexmatrix' || (params.qtype == 'calcinterval' && params.calcformat.match(/inequality/))) {
        outstr += ' &asymp; `' + htmlEntities(res.dispvalstr) + '` ';
    } else {
        outstr += ' &asymp; ' + htmlEntities(res.dispvalstr) + ' ';
    }
  }
  if (res.err && res.err != '' && res.str != '') {
    outstr += '<span class=noticetext>' + res.err + '</span>';
  }
  if (LivePreviews.hasOwnProperty(qn) && (mathRenderer=="MathJax" || mathRenderer=="Katex")) {
    LivePreviews[qn].RenderNow(outstr, true);
  } else {
    var previewel = document.getElementById('p'+qn);
    if (previewel) {
        previewel.innerHTML = outstr;
        rendermathnode(previewel);
    }
  }
  if (document.getElementById("qn"+qn)) {
    a11ypreview('`'+htmlEntities(document.getElementById("qn"+qn).value)+'` ' + outstr);
  } else {
    a11ypreview(outstr);
  }
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
  if (params.qtype == 'numfunc' && name.substr(0,2) != 'qs') {
    str = AMnumfuncPrepVar(qn, str)[3];
  }
  if (str.length > 30000) {
    str = str.substr(0,30000);
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
  } else if (params.qtype == 'molecule') {
    processMolecule(qn);
    return {};
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
      case 'ntuple':
      case 'calcntuple':
      case 'complexntuple':
      case 'calccomplexntuple':
      case 'algntuple':
        res = processCalcNtuple(qn, str, params.calcformat, params.qtype);
        break;
      case 'complex':
      case 'calccomplex':
        res = processCalcComplex(str, params.calcformat);
        break;
      case 'numfunc':
        res = processNumfunc(qn, str, params.calcformat);
        break;
      case 'matrix':
      case 'complexmatrix':
        res = processCalcMatrix(qn, str, params.calcformat, params.qtype);
        break;
      case 'calcmatrix':
      case 'calccomplexmatrix':
      case 'algmatrix':
        res = processCalcMatrix(qn, str, params.calcformat, params.qtype);
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
      text = AMnumfuncPrepVar(qn, text)[1];
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
    if (calcformat.indexOf('list')==-1 && calcformat.indexOf('set')==-1 && commasep) {
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
  var vars, fvarslist = '';
  if (typeof allParams[qn].vars === 'string') {
    vars = [allParams[qn].vars];
  } else {
    vars = allParams[qn].vars.slice();
  }

  var vl = vars.map(escapeRegExp).join('|');
  if (allParams[qn].fvars) {
    fvarslist = allParams[qn].fvars.map(escapeRegExp).join('|');
  }
  vars.push("DNE");

  if (vl.match(/lambda/)) {
  	  str = str.replace(/lamda/, 'lambda');
  }

  var foundaltcap = [];
  var dispstr = str;

  dispstr = dispstr.replace(/(arcsinh|arccosh|arctanh|arcsech|arccsch|arccoth|argsinh|argcosh|argtanh|argsech|argcsch|argcoth|arsinh|arcosh|artanh|arsech|arcsch|arcoth|arcsin|arccos|arctan|arcsec|arccsc|arccot|sinh|cosh|tanh|sech|csch|coth|sqrt|ln|log|exp|sin|cos|tan|sec|csc|cot|abs|root|pi)/g, functoindex);
  str = str.replace(/(arcsinh|arccosh|arctanh|arcsech|arccsch|arccoth|argsinh|argcosh|argtanh|argsech|argcsch|argcoth|arsinh|arcosh|artanh|arsech|arcsch|arcoth|arcsin|arccos|arctan|arcsec|arccsc|arccot|sinh|cosh|tanh|sech|csch|coth|sqrt|ln|log|exp|sin|cos|tan|sec|csc|cot|abs|root|pi)/g, functoindex);
  for (var i=0; i<vars.length; i++) {
    // handle double parens
    if (vars[i].match(/\(.+\)/)) { // variable has parens, not funcvar
      str = str.replace(/\(\(([^\(]*?)\)\)/g,'($1)');
    }
  	if (vars[i] == "E" || vars[i] == "e") {
          foundaltcap[i] = true;  // always want to treat e and E as different
	  } else {
	  	foundaltcap[i] = allParams[qn].calcformat.match(/casesensitivevars/); // default false unless casesensitivevars is used
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
     }
     return p1;
    });
  str = str.replace(/@v(\d+)@/g, function(match,contents) {
  	  return vars[contents];
       });
  dispstr = dispstr.replace(new RegExp("("+vl+")","gi"), function(match,p1) {
	 for (var i=0; i<vars.length;i++) {
		if (vars[i]==p1 || (!foundaltcap[i] && vars[i].toLowerCase()==p1.toLowerCase())) {
			return '@v'+i+'@';
		}
     }
     return p1;
    });
  // fix variable pairs being interpreted as asciimath symbol, like in
  dispstr = dispstr.replace(/(@v\d+@)(@v\d+@)/g,"$1 $2");
  dispstr = dispstr.replace(/(@v\d+@)(@v\d+@)/g,"$1 $2");
  // fix display of /n!
  dispstr = dispstr.replace(/(@v(\d+)@|\d+(\.\d+)?)!(?!=)/g, '{:$&:}');
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
		  	str = str.replace(new RegExp(varpts[0],"g"), " repvars"+i);
		  	vars[i] = "repvars"+i;
		  } else if (!isgreek && vars[i].replace(/[^\w_]/g,'').length>1) {
			  varstoquote.push(vars[i]);
		  }
      if (vars[i].match(/[^\w_]/) || vars[i].match(/^(break|case|catch|continue|debugger|default|delete|do|else|finally|for|function|if|in|instanceof|new|return|switch|this|throw|try|typeof|var|void|while|and with)$/)) {
        str = str.replace(new RegExp(escapeRegExp(vars[i]),"g"), " repvars"+i);
		  	vars[i] = "repvars"+i;
      }
	  }
  }

  if (varstoquote.length>0) {
	  vltq = varstoquote.join("|");
	  var reg = new RegExp("("+vltq+")","g");
	  dispstr = dispstr.replace(reg,"\"$1\"");
  }
  dispstr = dispstr.replace(/(@\d+@)/g, " $1");
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
 * Rounds values for showval display to 4 decimal places or 4 sigfigs, no trailing zeros.
 * @param number|array vals 
 * @returns 
 */
function roundForDisp(val) {
    if (Array.isArray(val)) {
        return val.map(roundForDisp);
    } else if (typeof val == 'number') {
        if (Math.abs(val) < 1) {
            return val.toPrecision(4).replace(/\.?0+$/,'');
        } else {
            return val.toFixed(4).replace(/\.?0+$/,'');
        }
    } else {
        return val;
    }
}

/**
 *  These functions should return:
 *   .str:  the input, formatted for rendering
 *   .dispvalstr: the evaluated string, formatted for display
 *   .submitstr: the evaluated answer, formatted for submission
 */

 function processNumber(origstr, format) {
     var err = '';
     origstr = origstr.replace(/^\s+|\s+$/g, '');
     if (format.indexOf('set') !== -1) {
        if (origstr.charAt(0) !== '{' || origstr.substr(-1) !== '}') {
            err += _('Invalid set notation');
        } else {
            origstr = origstr.slice(1, -1);
        }
     }
     if (format.indexOf('list')!== -1 || format.indexOf('set') !== -1) {
         var strs = origstr.split(/\s*,\s*/);
     } else {
        if (!commasep && origstr.match(/,/)) {
          err += _("Invalid use of a comma.");
        }
         var strs = [origstr.replace(/,/g,'')];
     }
     var str;
     for (var j=0;j<strs.length;j++) {
         str = strs[j];
         if (format.indexOf('units')!=-1) {
             var unitformat = _('Units must be given as [decimal number]*[unit]^[power]*[unit]^[power].../[unit]^[power]*[unit]^[power]...');
             if (!str.match(/^\s*(-?\s*\d+\.?\d*|-?\s*\.\d+|-?\s*\d\.?\d*\s*(E|\*\s*10\s*\^)\s*[\-\+]?\d+)/)) {
                 err += _('Answer must start with a number. ');
             }
             // disallow (sq|cu|square|cubic|squared|cubed)^power
             if (str.match(/\b(sq|square|cu|cubic|squared|cubed)\s*\^\s*[\-\+]?\s*\d+/)) {
               err += _('Invalid base for exponent. ');
               str = str.replace(/\^/,'');
             }
             // disallow (sq|square|cu|cubic)per
             if (str.match(/\b(sq|square|cu|cubic)\s+per\b/)) {
               err += _('Missing unit before "per". ');
               str = str.replace(/per\b/,'');
             }
             // disallow per(squared|cubed)
             if (str.match(/\bper\s+(squared|cubed)/)) {
               err += _('Missing unit after "per". ');
               str = str.replace(/\bper/,'');
             }
             // strip unit^number (squared|cubed)
             str = str.replace(/([a-zA-Z]\w*\s*)(\^\s*[\-\+]?\s*\d+\s*)(?:squared|cubed)\b/g, '$1');
             // strip (sq|cu|square|cubic) unit and unit (squared|cubed) since those are valid
             str = str.replace(/(?:sq|square|cu|cubic)\s+([a-zA-Z]\w*)/g,'$1');
             str = str.replace(/([a-zA-Z]\w*)\s+(?:squared|cubed)/g,'$1');
             // "this per that" => this/that
             str = str.replace(/\sper\s/g,'/');
             // strip number
             str = str.replace(/^\s*(-?\s*\d\.?\d*\s*(E|\*\s*10\s*\^)\s*[\-\+]?\d+|-?\s*\d+\.?\d*|-?\s*\.\d+)\s*[\-\*]?\s*/,'');
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
             
             str = str.replace(/\//g,'*').replace(/^\s*\*/,'').trim();

             if (str.length > 0) {
               var unitsregexlongprefix = "(yotta|zetta|exa|peta|tera|giga|mega|kilo|hecto|deka|deca|deci|centi|milli|micro|nano|pico|fempto|atto|zepto|yocto)";
               var unitsregexabbprefix = "(Y|Z|E|P|T|G|M|k|h|da|d|c|m|u|n|p|f|a|z|y)";
               var unitsregexfull = /^(m|meter|metre|micron|angstrom|fermi|in|inch|inches|ft|foot|feet|mi|mile|furlong|yd|yard|s|sec|second|min|minute|h|hr|hour|day|week|mo|month|yr|year|fortnight|acre|ha|hectare|b|barn|L|liter|litre|cc|gal|gallon|cup|pt|pint|qt|quart|tbsp|tablespoon|tsp|teaspoon|rad|radian|deg|degree|arcminute|arcsecond|grad|gradian|knot|kt|c|mph|kph|g|gram|gramme|t|tonne|Hz|hertz|rev|revolution|cycle|N|newton|kip|dyn|dyne|lb|pound|lbf|ton|J|joule|erg|lbft|ftlb|cal|calorie|eV|electronvolt|Wh|Btu|therm|W|watt|hp|horsepower|Pa|pascal|atm|atmosphere|bar|Torr|mmHg|umHg|cmWater|psi|ksi|Mpsi|C|coulomb|V|volt|farad|F|ohm|amp|ampere|A|T|tesla|G|gauss|Wb|weber|H|henry|lm|lumen|lx|lux|amu|dalton|Da|me|mol|mole|Ci|curie|R|roentgen|sr|steradian|Bq|becquerel|ls|lightsecond|ly|lightyear|AU|au|parsec|pc|solarmass|solarradius|degF|degC|degK|K)$/;
               //000 noprefix, noplural, sensitive
               //var unitsregex000 = "(in|mi|yd|min|h|hr|mo|yr|ha|gal|pt|qt|tbsp|tsp|rad|deg|grad|kt|c|mph|kph|rev|lbf|atm|mmHg|umHg|cmWater|psi|ksi|Mpsi|weber|amu|me|R|AU|au|degF|degC|degK|K)";
               //100 abb, noplural, sensitive
               var unitsregex100 = "(m|ft|s|b|cc|g|t|N|dyn|J|cal|eV|Wh|W|hp|Pa|C|V|F|A|T|G|Wb|H|lm|lx|Da|mol|M|Ci|sr|Bq|ls|ly|pc)";
               //001 noprefix, noplural, insensitive
               var unitsregex001 = "(inch|inches|lbft|ftlb|solarmass|solarradius)";
               //101 abb, noplural, insensitive
               var unitsregex101 = "(L|Hz|Btu)";
               //201 long, noplural, insensitive
               var unitsregex201 = "(fermi|foot|feet|sec|hertz|horsepower|Torr|gauss|lux)";
               //011 noprefix, plural, insensitive
               var unitsregex011 = "(micron|mile|furlong|yard|minute|hour|day|week|month|year|fortnight|acre|hectare|gallon|cup|pint|quart|tablespoon|teaspoon|radian|degree|gradian|knot|revolution|cycle|kip|lb|therm|atmosphere|roentgen)";
               //211 long, plural, insensitive
               var unitsregex211 = "(meter|metre|angstrom|second|barn|liter|litre|arcminute|arcsecond|gram|gramme|tonne|newton|dyne|pound|ton|joule|erg|calorie|electronvolt|watt|pascal|coulomb|volt|farad|ohm|amp|ampere|tesla|weber|henry|lumen|dalton|mole|curie|steradian|becquerel|lightsecond|lightyear|parsec)";
               
               var unitsregexfull100 = new RegExp("^" + unitsregexabbprefix + "?" + unitsregex100 + "$");
               var unitsregexfull001 = new RegExp("^" + unitsregex001 + "$", 'i');
               var unitsregexfull101 = new RegExp("^" + unitsregexabbprefix + "?" + unitsregex101 + "$", 'i');
               var unitsregexfull201 = new RegExp("^" + unitsregexlongprefix + "?" + unitsregex201 + "$", 'i');
               var unitsregexfull011 = new RegExp("^" + unitsregex011 + "s?$", 'i');
               var unitsregexfull211 = new RegExp("^" + unitsregexlongprefix + "?" + unitsregex211 + "s?$", 'i');

                 var pts = str.split(/\s*\*\s*/);
                 for (var i=0; i<pts.length; i++) {
                    // get matches
                    unitsregexmatch = pts[i].match(unitsregexfull101);
                    let unitsbadcase = false;
                    // It should have three defined matches:  [fullmatch, prefix, unit]
                    if (unitsregexmatch && typeof unitsregexmatch[1] !== 'undefined') {
                        // check that the prefix match is case sensitive
                        var unitsregexabbprefixfull = new RegExp("^" + unitsregexabbprefix + "$");
                        if (!unitsregexabbprefixfull.test(unitsregexmatch[1])) {
                            unitsbadcase = true;
                        }
                     }
                     if ((!unitsregexfull.test(pts[i]) && !unitsregexfull100.test(pts[i]) && !unitsregexfull001.test(pts[i]) && !unitsregexfull101.test(pts[i]) && !unitsregexfull201.test(pts[i]) && !unitsregexfull011.test(pts[i]) && !unitsregexfull211.test(pts[i])) || unitsbadcase) {
                         err += _('Unknown unit ')+'"'+pts[i]+'". ';
                     }
                 }
             } else {
                 err += _("Missing units");
             }
         } else if (format.indexOf('integer')!=-1) {
             if (!str.match(/^\s*\-?\d+\s*$/)) {
                 err += _('This is not an integer.');
             }
         } else {
             if (!str.match(/^\s*(\+|\-)?(\d+\.?\d*|\.\d+|\d*\.?\d*\s*E\s*[\-\+]?\d+)\s*$/)) {
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
  // give error instead.  fullstr = fullstr.replace(/=/,'');
  if (format.indexOf('allowplusminus')!=-1) {
    fullstr = fullstr.replace(/(.*?)\+\-(.*?)(,|$)/g, '$1+$2,$1-$2$3');
  }
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
  var dispstr = roundForDisp(outvals).join(', ');
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
  fullstr = fullstr.replace(/cup/g,'U');
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
    fullstr = fullstr.replace(/\s*,\s*/g,',').replace(/(^,|,$)/g,'');
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

  var err = ''; var str, vals, res, calcvals = [], calcvalsdisp = [];
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
    calcvalsdisp = roundForDisp(calcvals);
    submitstrarr[i] = sm + calcvals[0] + ',' + calcvals[1] + em;
    if (format.indexOf('inequality')!=-1) {
      // reformat as inequality
      if (calcvals[0].toString().match(/oo/)) {
        if (calcvals[1].toString().match(/oo/)) {
          dispstrarr[i] = 'RR';
        } else {
          dispstrarr[i] = ineqvar + (em==']'?' le ':' lt ') + calcvalsdisp[1];
        }
      } else if (calcvals[1].toString().match(/oo/)) {
        dispstrarr[i] = ineqvar + (sm=='['?' ge ':' gt ') + calcvalsdisp[0];
      } else {
        dispstrarr[i] = calcvalsdisp[0] + (sm=='['?' le ':' lt ') + ineqvar + (em==']'?' le ':' lt ') + calcvalsdisp[1];
      }
    } else {
        dispstrarr[i] = sm + calcvalsdisp[0] + ',' + calcvalsdisp[1] + em;
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
      dispvalstr: dispstrarr.join(' uu '),
      submitstr: submitstrarr.join(joinchar)
    };
  }
}

function processCalcNtuple(qn, fullstr, format, qtype) {
  var outcalced = '';
  var outcalceddisp = '';
  var NCdepth = 0;
  var lastcut = 0;
  var err = "";
  var notationok = true;
  var res = NaN;
  var dec;
  // Need to be able to handle (2,3),(4,5) and (2(2),3),(4,5) while avoiding (2)(3,4)
  fullstr = normalizemathunicode(fullstr);
  fullstr = fullstr.replace(/(\s+,\s+|,\s+|\s+,)/g, ',').replace(/(^,|,$)/g,'');
  fullstr = fullstr.replace(/<<(.*?)>>/g, '<$1>');
  if (!fullstr.charAt(0).match(/[\(\[\<\{]/)) {
    notationok=false;
  }
  for (var i=0; i<fullstr.length; i++) {
    dec = false;
    if (NCdepth==0) {
      outcalced += fullstr.charAt(i);
      outcalceddisp += fullstr.charAt(i);
      lastcut = i+1;
      if (fullstr.charAt(i)==',') {
        if (!fullstr.substring(i+1).match(/^\s*[\(\[\<\{]/) ||
          !fullstr.substring(0,i).match(/[\)\]\>\}]\s*$/)
        ) {
          notationok=false;
        } 
      } else if (i > 0 && fullstr.charAt(i-1) != ',') {
        notationok=false;
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
      if (sub == '') {notationok = false;}
      if (qtype.match(/complex/)) {
        res = evalcheckcomplex(sub, format);
        err += res.err;
        outcalceddisp += res.outstrdisp;
        outcalceddisp += fullstr.charAt(i);
        if (res.outstr) {
            outcalced += res.outstr;
            outcalced += fullstr.charAt(i);
        }
      } else if (qtype === 'algntuple') {
        res = processNumfunc(qn, sub, format);
        err += res.err;
      } else {
        err += singlevalsyntaxcheck(sub, format);
        err += syntaxcheckexpr(sub, format);
        res = singlevaleval(sub, format);
        err += res[1];
        outcalced += res[0];
        outcalceddisp += roundForDisp(res[0]);
        outcalced += fullstr.charAt(i);
        outcalceddisp += fullstr.charAt(i);
      }
      lastcut = i+1;
    }
  }
  if (NCdepth!=0) {
    notationok = false;
  }
  if (notationok==false) {
    err = _("Invalid notation")+". " + err;
  }
  if (qtype === 'algntuple') {
    outcalceddisp = '';
  }
  if (format.match(/generalcomplex/)) {
    outcalced = '';
  }
  return {
    err: err,
    dispvalstr: outcalceddisp,
    submitstr: outcalced
  };
}

function processCalcComplex(fullstr, format) {
  if (format.indexOf('allowplusminus')!=-1) {
    fullstr = fullstr.replace(/(.*?)\+\-(.*?)(,|$)/g, '$1+$2,$1-$2$3');
  }
  var err = '';
  var arr = fullstr.split(',');
  var str = '';
  var outstr = '';
  var outstrdisp = '';
  var outarr = [];
  var outarrdisp = [];
  var real, imag, imag2, prep, res;
  for (var cnt=0; cnt<arr.length; cnt++) {
    res = evalcheckcomplex(arr[cnt], format);
    err += res.err;
    outarrdisp.push(res.outstrdisp);
    if (res.outstr) {
        outarr.push(res.outstr);
    }
  }
  if (format.indexOf("generalcomplex")!=-1) {
    return {
      err: err,
      dispvalstr: outarrdisp.join(', ')
    };
  } else {
    return {
      err: err,
      dispvalstr: outarrdisp.join(', '),
      submitstr: outarr.join(',')
    };
  }
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
  var str, res;
  var err = '';
  for (var row=0; row < size[0]; row++) {
    out[row] = [];
    outcalc[row] = [];
    for (var col=0; col<size[1]; col++) {
      str = document.getElementById('qn' + qn + '-' + count).value;
      str = normalizemathunicode(str);
      if (params.qtype.match(/complex/)) {
        res = evalcheckcomplex(str, format);
        err += res.err;
        outcalc[row][col] = res.outstrdisp;
        if (res.outstr) {
            outsub.push(res.outstr);
        }
      } else if (params.qtype === 'algmatrix') {
        res = processNumfunc(qn, str, format);
        err += res.err;
      } else {
        if (str !== '') {
            err += syntaxcheckexpr(str,format);
            err += singlevalsyntaxcheck(str,format);
        }
        out[row][col] = str;
        res = singlevaleval(str, format);
        err += res[1];
        outcalc[row][col] = res[0];
        outsub.push(res[0]);
      }
      count++;
    }
    out[row] = '(' + out[row].join(',') + ')';
    outcalc[row] = '(' + roundForDisp(outcalc[row]).join(',') + ')';
  }
  return {
    err: err,
    str: '[' + out.join(',') + ']',
    dispvalstr: (params.qtype.match(/calc/))?('[' + outcalc.join(',') + ']'):'',
    submitstr: outsub.join('|')
  };
}

function processCalcMatrix(qn, fullstr, format, anstype) {
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
  var blankerr = '';
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
  if (lastcut == 0 && fullstr.charAt(0) != '(') {
    rowlist.push(fullstr);
  } else {
    rowlist.push(fullstr.substring(lastcut+1,fullstr.length-1));
  }
  var lastnumcols = -1;
  if (MCdepth !== 0) {
    okformat = false;
  }
  var collist, str, res;
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
      if (str == '') {
        blankerr = _('No elements of the matrix should be left blank.');
        outcalc[i][j] = '';
        outsub.push('');
      } else if (anstype === 'algmatrix') {
        res = processNumfunc(qn, str, format);
        err += res.err;
      } else if (anstype.match(/complex/)) {
        res = evalcheckcomplex(str, format);
        err += res.err;
        outcalc[i][j] = res.outstrdisp;
        if (res.outstr) {
            outsub.push(res.outstr);
        }
      } else {
        err += syntaxcheckexpr(str,format);
        err += singlevalsyntaxcheck(str,format);
        res = singlevaleval(str, format);
        err += res[1];
        outcalc[i][j] = roundForDisp(res[0]);
        outsub.push(res[0]);
      }
    }
    outcalc[i] = '(' + outcalc[i].join(',') + ')';
  }
  if (!okformat) {
    err = _('Invalid matrix format')+'. ';
  }
  err += blankerr;
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

  var totesteqn;
  var totestarr;
  if (format.match(/list/)) {
      totestarr = strprocess[0].split(/,/);
  } else {
      if (!commasep && strprocess[0].match(/,/)) {
        err += _("Invalid use of a comma.");
      }
      totestarr = [strprocess[0]];
  }
  var i,j,totest,testval,res;
  var successfulEvals = 0;
  for (var tti=0; tti < totestarr.length; tti++) {
    totesteqn = totestarr[tti];
    totesteqn = totesteqn.replace(/,/g,"").replace(/^\s+/,'').replace(/\s+$/,'').replace(/degree/g,'');
    var remapVars = strprocess[2].split('|');

    if (totesteqn.match(/(<=|>=|<|>|!=)/)) {
        if (!isineq) {
            if (iseqn) {
                err += _("syntax error: you gave an inequality, not an equation") + '. ';
            } else {
                err += _("syntax error: you gave an inequality, not an expression")+ '. ';
            }
        } else if (totesteqn.match(/(<=|>=|<|>|!=)/g).length>1) {
            err += _("syntax error: your inequality should only contain one inequality symbol")+ '. ';
        } else if (totesteqn.match(/(^(<|>|!))|(=|>|<)$/)) {
            err += _("syntax error: your inequality should have expressions on both sides")+ '. ';
        }
        totesteqn = totesteqn.replace(/(.*)(<=|>=|<|>|!=)(.*)/,"$1-($3)");
    } else if (totesteqn.match(/=/)) {
        if (isineq && !iseqn) {
            err += _("syntax error: you gave an equation, not an inequality")+ '. ';
        } else if (!iseqn) {
            err += _("syntax error: you gave an equation, not an expression")+ '. ';
        } else if (totesteqn.match(/=/g).length>1) {
            err += _("syntax error: your equation should only contain one equal sign")+ '. ';
        } else if (totesteqn.match(/(^=)|(=$)/)) {
            err += _("syntax error: your equation should have expressions on both sides")+ '. ';
        }
        totesteqn = totesteqn.replace(/(.*)=(.*)/,"$1-($2)");
    } else if (iseqn && isineq) {
        err += _("syntax error: this is not an equation or inequality")+ '. ';
    } else if (iseqn) {
        err += _("syntax error: this is not an equation")+ '. ';
    } else if (isineq) {
        err += _("syntax error: this is not an inequality")+ '. ';
    }
    if (!format.match(/generalcomplex/)) {
      if (fvars.length > 0) {
          reg = new RegExp("("+fvars.join('|')+")\\(","g");
          totesteqn = totesteqn.replace(/\w+/g, functoindex); // avoid sqrt(3) matching t() funcvar
          totesteqn = totesteqn.replace(reg,"$1*sin($1+");
          totesteqn = totesteqn.replace(/@(\d+)@/g, indextofunc);
      }

      totesteqn = prepWithMath(mathjs(totesteqn,remapVars.join('|')));
      successfulEvals = 0;
      for (j=0; j < 20; j++) {
          totest = 'var DNE=1;';
          for (i=0; i < remapVars.length - 1; i++) {  // -1 to skip DNE pushed to end
            if (domain[i][2]) { //integers
                //testval = Math.floor(Math.random()*(domain[i][0] - domain[i][1] + 1) + domain[i][0]);
                testval = Math.floor(domain[i][0] + (domain[i][1] - domain[i][0])*j/20);
            } else { //any real between min and max
                //testval = Math.random()*(domain[i][1] - domain[i][0]) + domain[i][0];
                testval = domain[i][0] + (domain[i][1] - domain[i][0])*j/20;
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
    }
    err += syntaxcheckexpr(strprocess[0], format + ',isnumfunc', vars.map(escapeRegExp).join('|'));
  }
  return {
    err: err
  };
}

function processMolecule(qn) {
  var mol = allKekule[qn].exportObjs(Kekule.Molecule)[0];
  if (typeof mol === 'undefined') {
    document.getElementById("qn" + qn).value = '';
  } else {
    var smi = Kekule.IO.saveFormatData(mol, 'smi');
    var cml = Kekule.IO.saveFormatData(mol, 'cml');
    document.getElementById("qn" + qn).value = smi + '~~~' + cml;
  }
}

function simplifyVariable(str) {
  //get rid of anything that's no alphanumeric, underscore, power, or +/-
  return str.replace(/[^\w_\^\-+]/g,'');
}

//Function to convert inequalities into interval notation
function ineqtointerval(strw, intendedvar) {
  var simpvar = simplifyVariable(intendedvar);
  if (commasep) {
    strw = strw.replace(/(\d)\s*,\s*(?=\d{3}\b)/g,"$1");
  }
	if (strw.match(/all\s*real/i)) {
    return ['(-oo,oo)'];
  } else if (strw.match(/DNE/)) {
    return ['DNE'];
  }
  var pat, interval, out = [];
  var strpts = strw.split(/\s*or\s*/);
  if (strpts.length == 1 && strw.match(/!=/)) {
    var ineqpts = strw.split(/!=/);
    if (ineqpts.length != 2) {
        return ['', 'invalid'];
    } else if (simplifyVariable(ineqpts[0]) != simpvar) {
        return ['', 'wrongvar'];
    }
    return ['(-oo,' + ineqpts[1] + ')U(' + ineqpts[1] + ',oo)'];
  }
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
  var outstr = out.join("U");
  if (outstr.match(/[\(\[],|,[\)\]]/)) { // catch "x > " without a value
    return ['', 'invalid'];
  }
  return [outstr];
}

function evalcheckcomplex(str, format) {
    var err = '';
    var out = '';
    var outstr = '';
    var outstrdisp = '';
    str = str.replace(/^\s+/,'').replace(/\s+$/,'');
    if (format.indexOf("allowjcomplex")!=-1) {
        str = str.replace(/j/g,'i');
    }
    // general check
    err += syntaxcheckexpr(str, format);
    if (format.indexOf("generalcomplex")!=-1) {
        // no eval
        return {
            err: err,
            outstrdisp: str
        }
    } else if (format.indexOf("sloppycomplex")==-1) {
        // regular a+bi complex; check formats
        var cparts = parsecomplex(str);
        if (typeof cparts == 'string') {
            err += cparts;
        } else {
            err += singlevalsyntaxcheck(cparts[0], format);
            err += singlevalsyntaxcheck(cparts[1], format);
        }
    }
    
    // evals
    if (str !== '') {
        var prep = prepWithMath(mathjs(str,'i'));
        var real = scopedeval('var i=0;'+prep);
        var imag = scopedeval('var i=1;'+prep);
        var imag2 = scopedeval('var i=-1;'+prep);
        if (real=="synerr" || imag=="synerr") {
        err += _("syntax incomplete");
        real = NaN;
        }
        if (!isNaN(real) && real!="Infinity" && !isNaN(imag) && !isNaN(imag2) && imag!="Infinity") {
            imag -= real;
            outstr = Math.abs(real)<1e-16?'':real;
            outstrdisp = Math.abs(real)<1e-16?'':roundForDisp(real);
            outstr += Math.abs(imag)<1e-16?'':((imag>0&&outstr!=''?'+':'')+imag+'i');
            outstrdisp += Math.abs(imag)<1e-16?'':((imag>0&&outstr!=''?'+':'')+roundForDisp(imag)+'i');
        }
    }
    return {
        err: err,
        outstrdisp: outstrdisp,
        outstr: outstr
    };
}

function parsecomplex(v) {
	var real,imag,c,nd,p,R,L;
	v = v.replace(/\s/,'');
	v = v.replace(/\((\d+\*?i|i)\)\/(\d+)/g,'$1/$2');
	v = v.replace(/sin/g,'s$n');
	v = v.replace(/pi/g,'p$');
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
		real = real.replace(/s\$n/g,"sin");
		real = real.replace(/p\$/g,"pi");
		imag = imag.replace(/s\$n/g,"sin");
		imag = imag.replace(/p\$/g,"pi");
		imag = imag.replace(/\*\//g,"/");
		return [real,imag];
	}
}

var onlyAscii = /^[\u0000-\u007f]*$/;

function singlevalsyntaxcheck(str,format) {
  if (commasep) {
    str = str.replace(/(\d)\s*,\s*(?=\d{3}\b)/g,"$1");
  }
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
		  if (!str.match(/^\(?\-?\(?\d+\)?\/\(?\d+\)?$/) && !str.match(/^\(?\d+\)?\/\(?\-?\d+\)?$/) && !str.match(/^\-?\d+$/) && !str.match(/^\-?(\d+|\d+\.\d*|\d*\.\d+)$/)) {
			return (_(" invalid entry format")+". ");
		  }
	} else if (format.indexOf('fraction')!=-1 || format.indexOf('reducedfraction')!=-1) {
		  str = str.replace(/([0-9])\s+([0-9])/g,"$1*$2").replace(/\s/g,'');
		 // if (!str.match(/^\s*\-?\(?\d+\s*\/\s*\-?\d+\)?\s*$/) && !str.match(/^\s*?\-?\d+\s*$/)) {
		  if (!str.match(/^\(?\-?\(?\d+\)?\/\(?\-?\d+\)?$/) && !str.match(/^\s*?\-?\d+\s*$/)) {
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
		  if (!str.match(/^\-?[1-9](\.\d*)?(\*|xx)10\^(\(?\(?\-?\d+\)?\)?)$/)) {
		  	if (format.indexOf('scinotordec')==-1) { //not scinotordec
		  		return (_("not valid scientific notation")+". ");
		  	} else if (!str.match(/^\-?(\d+|\d+\.\d*|\d*\.\d+)([eE]\-?\d+)?$/)) {
		  		return (_("not valid decimal or scientific notation")+". ");
		  	}
		  }
	} else if (format.indexOf('decimal')!=-1 && format.indexOf('nodecimal')==-1) {
		if (!str.match(/^\-?(\d+|\d+\.\d*|\d*\.\d+)([eE]\-?\d+)?$/)) {
			return (_(" not a valid integer or decimal number")+". ");
		}
	} else if (format.indexOf('integer')!=-1) {
    if (!str.match(/^\s*\-?\d+(\.0*)?\s*$/)) {
      return (_(" not an integer number")+". ");
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
      if (format.match(/casesensitivevars/)) {
        var reglist = 'degree|arc|arg|ar|sqrt|root|ln|log|exp|sinh|cosh|tanh|sech|csch|coth|sin|cos|tan|sec|csc|cot|abs|pi|sign|DNE|e|oo'.split('|');
        reglist.sort(function(x,y) { return y.length - x.length});
        let reg1 = new RegExp("("+reglist.join('|')+")", "ig");
        var reglist = vl.split('|');
        reglist.sort(function(x,y) { return y.length - x.length});
        let reg2 = new RegExp("("+reglist.join('|')+")", "g");
        if (str.replace(/repvars\d+/g,'').replace(reg1,'').replace(reg2,'').match(/[a-zA-Z]/)) {
          err += _(" Check your variables - you might be using an incorrect one")+". ";
        }
      } else {
        var reglist = 'degree|arc|arg|ar|sqrt|root|ln|log|exp|sinh|cosh|tanh|sech|csch|coth|sin|cos|tan|sec|csc|cot|abs|pi|sign|DNE|e|oo'.split('|').concat(vl.split('|'));
        reglist.sort(function(x,y) { return y.length - x.length});
	  	  let reg = new RegExp("("+reglist.join('|')+")", "ig");
        if (str.replace(/repvars\d+/g,'').replace(reg,'').match(/[a-zA-Z]/)) {
          err += _(" Check your variables - you might be using an incorrect one")+". ";
        }
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
    if (str.match(/\(\s*\)/)) {
      err += _(" Empty function input or parentheses") + ". ";
    }
	  if (str.match(/%/) && !str.match(/^\s*[+-]?\s*((\d+(\.\d*)?)|(\.\d+))\s*%\s*$/)) {
	  	  err += _(" Do not use the percent symbol, %")+". ";
	  }
      if (str.match(/=/) && !format.match(/isnumfunc/)) {
        err += _("You gave an equation, not an expression")+ '. ';
      }

	  return err;
}

// returns [numval, errmsg]
function singlevaleval(evalstr, format) {
  if (commasep) {
    evalstr = evalstr.replace(/(\d)\s*,\s*(?=\d{3}\b)/g,"$1");
  }
  if (evalstr.match(/,/)) {
    return [NaN, _("syntax incomplete")+". "];
  }
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
    if (res === '' || typeof res === 'undefined') {
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
    /* we don't check for 
      if (Number.isNaN(v)) {
      because we don't want to give away if values just aren't
      in domain of student's function
    */
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
    sendLTIresizemsg();
}

var seqgroupcollapsed = {};
function setupSeqPartToggles(base) {
    if (base.id && base.id.match(/questionwrap/)) {
        var qn = parseInt(base.id.substr(12));
        var seqseps = $(base).find(".seqsep");
        if (!seqgroupcollapsed[qn] || seqseps.length == 1) {
            seqgroupcollapsed[qn] = {};
        }
        if (seqseps.length > 1) {
            for (var i=0; i < seqseps.length - 1; i++) {
                $(seqseps[i]).next().attr("id", "seqgrp"+qn+"-"+i);
                if (seqgroupcollapsed[qn][i]) {
                    $(seqseps[i]).next().hide();
                }
                $(seqseps[i]).prepend($("<button>", {
                    class: "plain slim",
                    style: "color: inherit",
                    "aria-expanded": !seqgroupcollapsed[qn][i],
                    "aria-controls": "seqgrp"+qn+"-"+i,
                    "aria-label": seqgroupcollapsed[qn][i] ? _('Expand') : _('Collapse'),
                    html: seqgroupcollapsed[qn][i] ? '&#x25B6;' : '&#x25BC;',
                    type: 'button',
                    click: function (e) {
                        var state = (this.getAttribute("aria-expanded") == 'true');
                        var ctls = this.getAttribute("aria-controls");
                        if (state) {
                            this.setAttribute("aria-expanded", "false");
                            this.setAttribute("aria-label", _('Expand'));
                            this.innerHTML = '&#x25B6;'
                        } else {
                            this.setAttribute("aria-expanded", "true");
                            this.setAttribute("aria-label", _('Collapse'));
                            this.innerHTML ='&#x25BC;';
                        }
                        $("#"+ctls).slideToggle(300);
                        var pts = ctls.substr(6).split("-");
                        seqgroupcollapsed[pts[0]][pts[1]] = state;
                        sendLTIresizemsg();
                    }
                }));
            }
        }
    }
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
        this.setHighlight = function(ev)
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
        ul.onmouseover = me.setHighlight;

		/********************************************************
		click handler for the dropdown ul
		insert the clicked suggestion into the input
        ********************************************************/
        
		ul.onmousedown = ul.ontouchstart = function(ev)
		{
            me.setHighlight(ev);
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

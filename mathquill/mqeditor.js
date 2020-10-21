/*
  Equation Editor palette tool for MathQuill
  (c) David Lippman 2019
  Mozilla Public License, v. 2.0: http://mozilla.org/MPL/2.0/

  You can toggle a plain input field into a MathQuill field with attached
  editor using toggleMQ.  Alternatively if you already have an MathQuill
  mathfield, you can attach the editor using attachEditor.  If pre-creating
  mathfields, make sure you use substituteTextarea if your layoutstyle is 'OSK'

  To get anything to display, you'll need to first call setConfig with a config
  object defining either layout (an array describing the layout) or
  getLayout, a function that returns a layout array.

 */

var MQeditor = (function($) {
  var config = {
    layoutstyle: 'auto',
    layout: []
  };
  var MQconfig = {};
  var initialized = false;
  var curMQfield = null;
  var blurTimer = null;
  var keyRepeatInterval = null;
  var MQ = MathQuill.getInterface(MathQuill.getInterface.MAX);

  /*
    Config object for MQeditor
    layoutstyle:
      "under" for on-focus floating under entry box,
      "OSK" for on-focus fixed-position on-screen-keyboard
    layout: array that defines the palette buttons
    getLayout: a function that returns a layout. Gets passed the MQ element.
    getLayoutstyle: a function that returns a value for layoutstyle.
    onShow: a callback for when editor is shown. Gets passed the MQ element.
    toMQ:  function to convert text to MQ latex
    fromMQ: function to convert MQ latex to input
   */
  function setConfig(newconfig) {
    for (var i in newconfig) {
      config[i] = newconfig[i];
    }
  }

  /*
    MQconfig holds the config used when creating MQ editable fields
   */
  function setMQconfig(newconfig) {
    MQconfig = newconfig;
  }
  function inIframe () {
    try {
        return window.self !== window.top;
    } catch (e) {
        return true;
    }
  }

  /*
    Toggles the given input field to/from a MathQuill field
    and attaches the editor to the field
    el: the input field node or id of input. Must have a unique id
    state: (optional) true to toggle to MQ; false to regular input
   */
  function toggleMQ(el, state, nofocus) {
    if (typeof el == 'string') {
      el = document.getElementById(el);
    }
    var newstate = (typeof state == 'boolean') ? state : (el.type != 'hidden');
    var textId = el.id;
    if (newstate === true) { // enable MQ
      var initval = $(el).attr("type","hidden").val();
      if (config.hasOwnProperty('toMQ')) { // convert format if needed
        initval = config.toMQ(initval, textId);
      }
      var mqfield = $("#mqinput-"+textId);
      if (mqfield.length == 0) {  // no existing MQ input
        var span = $("<span/>", {
          id: "mqinput-"+textId,
          class: "mathquill-math-field",
          text: initval
        });
        var m;
        if ((m = el.className.match(/(ansred|ansyel|ansgrn|ansorg)/)) !== null) {
          span.addClass(m[0]);
        }
        var size = (el.hasAttribute("size") ? (el.size > 3 ? el.size/1.8 : el.size) : 10);
        span.css("min-width", size + "em");
        span.insertAfter(el);

        var thisMQconfig = {
          handlers: {
            edit: onMQedit,
            enter: onMQenter
          }
        };
        if (config.hasOwnProperty("getLayoutstyle")) {
          config.curlayoutstyle = config.getLayoutstyle();
        } else if (config.layoutstyle == 'auto') {
          config.curlayoutstyle = getLayoutstyle();
        } else {
          config.curlayoutstyle = config.layoutstyle;
        }
        if (config.curlayoutstyle == 'OSK') {
          thisMQconfig.substituteTextarea = function () {
            var s = document.createElement('span');
            s.setAttribute('tabindex', 0);
            return s;
          };
          thisMQconfig.keyboardPassthrough = true;
        }
        thisMQconfig.autoOperatorNames = thisMQconfig.autoParenOperators = 
            'ln log abs exp sin cos tan arcsin arccos arctan sec csc cot arcsec arccsc arccot sinh cosh sech csch tanh coth arcsinh arccosh arctanh';
        var vars = el.getAttribute("data-mq-vars") || '';
        if (vars != '') {
            vars = (vars=='') ? [] : vars.split(/,/);
            for (var i=0; i<vars.length; i++) {
                if (vars[i].length > 1 && vars[i].match(/^[a-zA-Z]+$/)) {
                    thisMQconfig.autoOperatorNames += ' ' + vars[i];
                }
            }
        }

        if (el.disabled) {
          mqfield = MQ.StaticMath(span[0]);
          span.addClass("disabled");
        } else {
          mqfield = MQ.MathField(span[0], thisMQconfig).config(MQconfig);
          attachEditor(span);
          // if original input has input changed programmatically and change
          // event triggered, update mathquill.
          $(el).on('change.mqed', function(e, fromblur) {
            if (!fromblur) {
              var val = el.value;
              if (config.hasOwnProperty('toMQ')) {
                val = config.toMQ(val);
              }
              mqfield.latex(val);
            }
          });
        }

      } else { // has existing MQ input
        mqfield.show();
  			mqfield = MQ(mqfield[0]).latex(initval);
      }
      if (nofocus !== true) {
        mqfield.focus();
      }
    } else { // disable MQ
      $(el).attr("type","text").off('change.mqed');
      if (nofocus !== true) {
        $(el).focus();
      }
      $("#mqinput-"+textId).hide();
    }

  }

  /*
    Toggles all inputs matching the selector to/from a MathQuill field
    state: (optional) true to toggle to MQ; false to regular input
   */
  function toggleMQAll(selector, state) {
    var newstate = state || null;
    $(selector).each(function(i,el) {
      toggleMQ(el, newstate, true);
    });
  }

  /*
    Sets onfocus/onblur listeners to show/hide the editor on focus/blur
    call with the MQ node
   */
  function attachEditor(mqel) {
    // set up editor to display on focus
    $(mqel).find(".mq-textarea > *")
      .on('focus.mqeditor', showEditor)
      .on('blur.mqeditor', function() {
        blurTimer = setTimeout(hideEditor, 100);
        if (config.hasOwnProperty('onBlur')) {
            config.onBlur();
        }
      });
    $(mqel).on('click.mqeditor', function(e) {
      // hack to handle MQ entries inside radio button labels
      var p = $(e.target).closest("label");
      if (p.length > 0) {
        if (p.attr("for") !== 'undefined') {
          $("#" + p.attr("for")).prop("checked",true);
        }
        e.stopPropagation();
        return false;
      }
    });
  }

  /*
    Display the editor, rebuilding the panel if needed
   */
  function showEditor(event) {
    clearTimeout(blurTimer);
    var mqel = $(event.target).closest(".mathquill-math-field");
    if (initialized === false) {
      // first time through: inject the mqeditor div
      $("body").append($("<div/>", {id:"mqeditor", class:"mqeditor"}));
      // prevent clicks in editor from triggering blur in MQ field
      $("#mqeditor").on("mousedown touchstart", function(evt) {evt.preventDefault();});
      initialized = true;
    }
    // update layoutStyle if needed
    var lastlayoutstyle = config.curlayoutstyle;
    if (config.hasOwnProperty("getLayoutstyle")) {
      config.curlayoutstyle = config.getLayoutstyle();
    } else if (config.layoutstyle == 'auto') {
      config.curlayoutstyle = getLayoutstyle();
    } else {
      config.curlayoutstyle = config.layoutstyle;
    }
    if (config.curlayoutstyle === 'OSK') {
      if (!inIframe()) {
        $("#mqeditor").addClass("fixedbottom").removeClass("iframeosk");
      } else {
        $("#mqeditor").addClass("iframeosk").removeClass("fixedbottom"); 
      }
      if (!document.getElementById("mqe-fb-spacer")) {
        var div = document.createElement("div");
        div.style.height = "200px";
        div.id = "mqe-fb-spacer";
        $("body").append(div);
      }
    } else {
      $("#mqeditor").removeClass("fixedbottom iframeosk");
    }
    var rebuild = false;
    // see if the field has changed
    if (curMQfield === null || mqel[0] != curMQfield.el() ||
      lastlayoutstyle !== config.curlayoutstyle
    ) {
      rebuild = true;
      // trigger change on last field
      if (curMQfield !== null) {
        $("#"+curMQfield.el().id.substring(8)).trigger('change', true);
      }

      // new field; need to build the panel
      // update the layout based on the element
      if (config.hasOwnProperty("getLayout")) {
        config.layout = config.getLayout(mqel[0], config.curlayoutstyle);
      }
      // empty existing
      $("#mqeditor").empty().show();
      // build new panel
      if (config.layout.tabs) {
        buildTabPanel(document.getElementById("mqeditor"), config.layout, "mqeditor");
      } else {
        buildPanel(document.getElementById("mqeditor"), config.layout, "mqeditor");
      }
      // render buttons then hide tabpanels
      $("#mqeditor .mqed-btn.rend").each(function() {MQ.StaticMath(this, {mouseEvents: false}); });

      if ($("#mqeditor .mqed-tabrow").length > 0) {
        $("#mqeditor .mqed-tabpanel").hide().first().show();
        var tabpanelwrap = $("#mqeditor .mqed-tabrow + div");
        tabpanelwrap.css("height", tabpanelwrap.height());
      }
    }
    // now show and position the editor
    if (config.curlayoutstyle === 'OSK' && !inIframe()) {
      $("#mqeditor").slideDown(50, function () {
        var mqedheight = $("#mqeditor").height() + 5;
        var mqedDistBottom = $(window).height() - (mqel.offset().top + mqel.outerHeight() - $(window).scrollTop());
        if (mqedDistBottom < mqedheight) {
          $(window).scrollTop($(window).scrollTop() + (mqedheight - mqedDistBottom));
        }
      });
    } else {
      $("#mqeditor").show();
    }
    positionEditor(mqel);
    curMQfield = MQ.MathField(mqel[0]);
    $("#"+mqel[0].id.substring(8)).triggerHandler('focus');
    if (config.hasOwnProperty('onShow')) {
      config.onShow(mqel[0], config.curlayoutstyle, rebuild);
    }
    $(document).trigger('mqeditor:show');
  }

  /*
    Hide the editor
   */
  function hideEditor(event) {
    $(document).trigger('mqeditor:hide');
    
    if (config.curlayoutstyle === 'OSK' && !inIframe()) {
      $("#mqeditor").slideUp(50);
    } else {
      $("#mqeditor").hide();
    }
    $("#"+curMQfield.el().id.substring(8)).trigger('change', true);
    curMQfield = null;
  }

  /*
    Hide the editor and clear curMQfield
   */
  function resetEditor() {
    clearTimeout(blurTimer);
    $("#mqeditor").hide();
    curMQfield = null;
  }


  /*
    Positions the editor below the MQ field, if layoutstyle dictates
   */
  function positionEditor(ref) {
    if (config.curlayoutstyle == 'under' || inIframe()) {
    	var mqfield = $(ref).closest(".mathquill-math-field");
    	var offset = mqfield.offset();
    	var height = mqfield.outerHeight();
      var editorLeft = offset.left;
      if (document.getElementById("mqeditor")) {
        var editorWidth = document.getElementById("mqeditor").offsetWidth;
        if (editorLeft + editorWidth > document.documentElement.clientWidth) {
          editorLeft = document.documentElement.clientWidth - editorWidth-5;
        }
      }
      if (inIframe()) {
        $("#mqeditor").css("top", offset.top + height + 3).css("left", 0);
      } else {
        $("#mqeditor").css("top", offset.top + height + 3).css("left", editorLeft);
      }
    } else {
      $("#mqeditor").css("top", "auto").css("left", 0);
    }
  }
  /*
    Update the editor position, and update the regular input field
   */
  function onMQedit(mf) {
  	var el = mf.el();
    positionEditor(el);
    if (config.hasOwnProperty('onResize')) {
        config.onResize(el, config.curlayoutstyle);
    }
  	if (el.id.match(/mqinput/)) {
      var latex = mf.latex();
      if (config.hasOwnProperty('fromMQ')) {
        //convert to input format
        latex = config.fromMQ(latex, el.id);
      }
  		//document.getElementById(el.id.substring(8)).value = latex;
      $("#"+el.id.substring(8)).val(latex).trigger('input');
      // handle nosolninf, since not traditional input
      if (latex != '') {
        $("#"+el.id.substring(8)).siblings("input[id^=qs][value=spec]").prop("checked",true);
      }

      if (config.hasOwnProperty('onEdit')) {
        config.onEdit(el.id, latex);
      }
  	}
  }

  /*
    Handle the enter
   */
  function onMQenter(mf) {
    if (config.hasOwnProperty('onEnter')) {
      config.onEnter(mf.el().id);
    }
  }

  /*
    The default layoutstyle calculator
   */
  function getLayoutstyle() {
    var width = document.documentElement.clientWidth;
    if (navigator.userAgent.match(/Android/) ||
      navigator.userAgent.match(/iPhone/) ||
      navigator.userAgent.match(/iPad/) ||
      width < 500
    ) {
      return 'OSK';
    } else {
      return 'under';
    }
  }

  function buildTabPanel(baseel, layoutarr, baseid) {
    var tabdiv = document.createElement("div");
    tabdiv.className = "mqed-row mqed-tabrow";
    var panelcont = document.createElement("div");
    var btncont, btn, paneldiv, tabcnt = 0;
    baseel.appendChild(tabdiv);
    baseel.appendChild(panelcont);
    for (var i=0; i<layoutarr.tabs.length; i++) {
      if (layoutarr.tabs[i].enabled !== true) {
        continue;
      }
      tabcnt++;
      buildButton(tabdiv, layoutarr.tabs[i], baseid+'-'+i);
      paneldiv = document.createElement("div");
      paneldiv.className = "mqed-row mqed-tabpanel";
      paneldiv.id = baseid+'-'+i+'-tabpanel';
      panelcont.appendChild(paneldiv);
      for (var j=0; j < layoutarr.tabs[i].tabcontent.length; j++) {
        btn = layoutarr.tabs[i].tabcontent[j];
        if (btn.hasOwnProperty("flow") && btn.contents.length == 0) {
          continue; // skip container if empty
        }
        if (btn.hasOwnProperty("flow")) { //sub layout
          buildPanel(paneldiv, btn, baseid+'-'+i+'-'+j);
        } else {
          buildButton(paneldiv, btn, baseid+'-'+i+'-'+j);
        }
      }
    }
    if (tabcnt > 1) {
      $(baseel).find(".mqed-tab").first().addClass("mqed-activetab");
    } else {
      $(tabdiv).hide();
    }
  }

  /*
    Build the editor panel
  */
  function buildPanel(baseel, layoutarr, baseid) {
    var flow = layoutarr.flow;
    var maxsize = 100;
    if (layoutarr.hasOwnProperty('s') && flow=='row') {
      maxsize = layoutarr.s;
    }
    var wrapel = document.createElement("div");
    if (layoutarr.hasOwnProperty('s')) {
      wrapel.style.flexGrow = layoutarr.s;
    }
    if (layoutarr.hasOwnProperty('class')) {
      wrapel.className = layoutarr.class;
    }
    if (layoutarr.hasOwnProperty('tabpanel')) {
      wrapel.id = layoutarr.tabpanel.id;
      wrapel.style.display = layoutarr.tabpanel.hidden ? 'none':'';
    }
    var flowel = document.createElement("div");
    flowel.className = 'mqed-'+flow; //mqed-row or mqed-col

    var curRowSize = 0;
    var btn, btncont, subwrap;
    var thisSize = 0;
    for (var i=0; i < layoutarr.contents.length; i++) {
      btn = layoutarr.contents[i];
      if (btn.hasOwnProperty("flow") && btn.contents.length == 0) {
        continue; // skip container if empty
      }
      if (btn.hasOwnProperty('s')) {
        thisSize = btn.s;
      } else {
        thisSize = 1;
      }
      if (curRowSize + thisSize > maxsize) {
        wrapel.appendChild(flowel);
        curRowSize = 0;
        var flowel = document.createElement("div");
        flowel.className = 'mqed-'+flow; //mqed-row or mqed-col
      }
      if (btn.hasOwnProperty("flow")) { //sub layout
        buildPanel(flowel, btn, baseid+'-'+i);
      } else {
        buildButton(flowel, btn, baseid+'-'+i);
      }
      curRowSize += thisSize;
    }
    wrapel.appendChild(flowel);
    baseel.appendChild(wrapel);
  }

  /*
    Build a button
  */
  function buildButton(baseel, btn, baseid) {
    var btn,btnel,btncont,cmdtype,cmdval;

    btncont = document.createElement("div");
    btncont.className = "mqed-btn-cont";
    // set size
    if (btn.s) {
      btncont.style.flexGrow = btn.s;
    }
    if (btn.contid) {
      btncont.id = btn.contid;
    }
    if (btn.contclass) {
      $(btncont).addClass(btn.contclass);
    }
    baseel.appendChild(btncont);
    if (!btn.l && !btn.b && !btn.p) {
      //spacer
      return;
    }

    btnel = document.createElement("span");
    btnel.tabIndex = 0;
    if (btn.l) { // latex button
      if (btn.op) {
        btnel.className = "mqed-btn mq-math-mode";
        btnel.innerHTML = '<span class="mq-root-block"><var class="mq-operator-name">'+btn.l.substring(1)+'</var></span>';
      } else if (btn.pr) {
        btnel.className = "mqed-btn mq-math-mode";
        btnel.innerHTML = '<span class="mq-root-block">'+btn.pr+'</span>';
      } else if (btn.l.match(/\\left(.)\\right(.)/)) {
        var m = btn.l.match(/\\left(.)\\right(.)/);
        btnel.className = "mqed-btn mq-math-mode";
        btnel.innerHTML = '<span class="mq-non-leaf"><span class="mq-scaled mq-paren" style="transform: scale(1, 1.2);">'+m[1]+'</span><span class="mq-non-leaf mq-empty"></span><span class="mq-scaled mq-paren" style="transform: scale(1, 1.2);">'+m[2]+'</span></span>'
      } else {
        btnel.className = "mqed-btn rend";
        btnel.innerText = btn.l;
      }
      cmdtype = 'c';
      cmdval = btn.l.substring(1);
    } else if (btn.b) { // rendered text button
      if (btn.r) {
          btnel.className = "mqed-btn rend";
          btnel.innerHTML = btn.b;
      } else {
        btnel.className = "mqed-btn mq-math-mode";
        if (btn.v) {
            btnel.innerHTML = '<span class="mq-root-block"><var>'+btn.b+'</var></span>';
        } else {
            btnel.innerHTML = '<span class="mq-root-block"><span>'+btn.b+'</span></span>';
        }
      }
      cmdtype = 't';
      cmdval = btn.b;
      if (cmdval.match(/^\d$/) || cmdval=='.') {
        $(btnel).addClass("mqed-digitkey");
      }
    } else { // plain text button
      btnel.className = "mqed-btn";
      btnel.innerHTML = btn.p;
      cmdtype = 't';
      cmdval = btn.p;
    }
    // override default command type
    if (btn.c) {
      cmdtype = btn.c;
      if (cmdtype == 'shift') {
        $(btnel).addClass("mqed-shift");
      } else if (cmdtype == 'k') {
        $(btnel).addClass("mqed-navkey");
      }
    }
    // make it small; 1 for 90%, 2 for 80%, etc.
    if (btn.sm) {
      btnel.style.fontSize = (100-10*btn.sm) + '%';
    }

    // override what to write
    if (btn.w) {
      cmdval = btn.w;
    }
    if (btn.tabcontent) { // if it has tabcontent, mark it as tab control
      $(btnel).addClass("mqed-tab");
      cmdtype = 'showtabpanel';
      cmdval = baseid+'-tabpanel';
    }
    $(btnel).data("cmdtype", cmdtype).data("cmdval", cmdval);
    $(btnel).on("click mousedown touchstart keydown", makeBtnListener(cmdtype, cmdval))
      .on("touchend", function(evt) { setTimeout(function() {$(evt.currentTarget).removeClass("mactive"); }, 50);})
      .on("touchend mouseup", function() {clearTimeout(keyRepeatInterval); keyRepeatInterval = null;});
    btncont.appendChild(btnel);
  }

  /*
    Returns a listener function. Used in buildPanel
  */
  function makeBtnListener(cmdtype, cmdval) {
    return function (event) {
      handleMQbtn(event,cmdtype,cmdval);
    }
  }

  /*
    Handle an editor button click
  */
  function handleMQbtn(event, cmdtype, cmdval) {
    //handle backspace with mousedown instead of click to allow repeat
    if (event.type=='mousedown' && cmdval !== 'Backspace') {
      return;
    } else if (event.type=='click' && cmdval === 'Backspace') {
      return;
    }
    if (event.type=='keydown' && event.key !== 'Enter') {
      return;
    }
    if (event.type=='touchstart') {
      event.preventDefault();
      $(event.currentTarget).addClass("mactive");
    }


    // return focus to editor
    //clearTimeout(blurTimer);
    //curMQfield.focus();

    if (cmdtype == 't') {
      // do typedText
      if (cmdval.match(/^[a-zA-Z]$/)) {
        // check for shift key being active
        if ($(event.target).closest('.mqed-tabpanel').find(".mqed-shift").hasClass("active")) {
          cmdval = cmdval.toUpperCase();
        }
      }
      curMQfield.typedText(cmdval);
    } else if (cmdtype == 'c') {
      // do MQ cmd
      curMQfield.cmd(cmdval);
    } else if (cmdtype == 'l') {
      curMQfield.latex(cmdval);
    } else if (cmdtype=='w') {
      // do MQ write
      curMQfield.write(cmdval);
      // if matrix, move cursor to first entry
      if (m = cmdval.match(/bmatrix}(.*?)(\\\\|\\end{bm)/)) {
        var len = m[1].split(/&/).length;
        for (var i=0;i<len;i++) {
          curMQfield.keystroke('Left');
        }
      }
    } else if (cmdtype=='k') {
      // do MQ keystroke
      curMQfield.keystroke(cmdval);
      if (cmdval === 'Backspace') {
        // set up for repeats - wait 600ms then repeat every 70ms
        keyRepeatInterval = setTimeout(function () {
          handleMQbtn(event, cmdtype, cmdval);
        }, keyRepeatInterval===null ? 600 : 70);
      }
    } else if (cmdtype=='m') {
      // do MQ matrixCmd
      curMQfield.matrixCmd(cmdval);
    } else if (cmdtype=='f') {
      // function; if there's a selection, wrap it in parens and apply function
  		var sel = curMQfield.getSelection();
  		if (sel) {
  			curMQfield.write(cmdval+'\\left('+sel+'\\right)');
        curMQfield.keystroke('Left');
  		} else if (cmdval.match(/{}$/)) {
        curMQfield.typedText(cmdval.replace(/{}$/,''));
      } else {
  		  curMQfield.write(cmdval);
  		}
    } else if (cmdtype=='sf') {
      // simple function, that doesn't need parens.
  		var sel = curMQfield.getSelection();
  		if (sel) {
  			curMQfield.write(cmdval+'{'+sel+'}');
  		} else {
  		  curMQfield.cmd(cmdval);
  		}
  		curMQfield.keystroke('Left');
    } else if (cmdtype=='i') {
      // interval: if there's a selection, wrap it
  		var sel = curMQfield.getSelection();
      if (!sel) {
        sel = '';
      }
      if (typeof cmdval === 'string') {
        var leftsym = cmdval.charAt(0);
        var rightsym = cmdval.charAt(1);
    		curMQfield.write('\\left'+leftsym+sel+'\\right'+rightsym);
      } else {
        curMQfield.write(cmdval[0]+sel+cmdval[1]);
      }
      if (sel=='') {
        curMQfield.keystroke('Left');
      }
    } else if (cmdtype=='showtabpanel') {
      var target = $(event.target).closest('.mqed-btn');
      target.closest(".mqeditor").find(".mqed-tabpanel").hide();
      target.closest(".mqeditor").find(".mqed-btn.mqed-activetab").removeClass("mqed-activetab");
      $("#"+cmdval).show();
      target.addClass("mqed-activetab");
      if (config.hasOwnProperty('onTab')) {
        config.onTab(target[0], config.curlayoutstyle, cmdval);
      }
    } else if (cmdtype=='shift') {
      // enable Shift in alphabet panel
      var target = $(event.target).closest('.mqed-btn');
      var subpanel = $(event.target).closest('.mqed-tabpanel');
      var goingActive = !target.hasClass("active");
      if (goingActive) {
        target.addClass("active");
      } else {
        target.removeClass("active");
      }
      // change case of each button label
      subpanel.find(".mqed-btn").each(function(i,el) {
        var txt = el.textContent;
        if (txt.match(/^[a-zA-Z]$/)) {
          if (goingActive) {
            el.textContent = el.textContent.toUpperCase();
          } else {
            el.textContent = el.textContent.toLowerCase();
          }
        }
      });
    }
  }
  return {
    setConfig: setConfig,
    setMQconfig: setMQconfig,
    toggleMQ: toggleMQ,
    toggleMQAll: toggleMQAll,
    attachEditor: attachEditor,
    getLayoutstyle: getLayoutstyle,
    resetEditor: resetEditor
  }
})(jQuery);

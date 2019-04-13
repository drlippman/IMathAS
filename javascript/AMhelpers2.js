/*

What is needed:

1) syntax check inputs
2) x Do any pre-preview cleanup
3) Get numeric values for preview (when showvals is set)
4) Get numeric values for submission (different for inequality)

- Do input validation for numeric types
- No livepreview for matrix/calcmatrix

On question load:
  - call init(jsParamArr)
    x add LivePreviews
    x add click handler for preview buttons
    x sets up entry tips (via setupTips)

On livepreview preview:
  x use preformat function in LivePreviews code, based on qtype

For final preview (w possible showvals and syntax checking)
  - Called from button click or LivePreviews timeout
  - May also want to add a more advanced onblur (w onfocus listener to cancel)
    for numeric types without a perview button, to provide syntax warnings.
  - Calls showPreview with the qn
    - Looks up params from allParams
    - if matrix type with matrixsize, calls processSizedMatrix function
    - otherwise, gets string from input
    - based on qtype, calls the appropriate process___ function with string
      - returns [err, numeric for preview, numeric for submission]
    - forms preview string, displays it

For onsubmit
  - For use cases without ajax submission / frontend,
    - Have a generic preSubmitForm function that would be called from form onsubmit
    - that loops over allParams
    - Gets the presubmit values and appends new hidden inputs to the form
  - Have individual preSubmit function that takes qn as input
    - calls processSizedMatrix or process____ functions
    - returns the numeric string, to be included in FormData

 */


var imathasAssess = (function($) {

var allParams = {};

function init(paramarr) {
  var qn, params, i, el;
  //save the params to the master record
  allParams = Object.assign(allParams, paramarr);

  for (qn in paramarr) {
    params = paramarr[qn];
    if (params.helper) { //want mathquill
      //TODO
    }
    if (params.preview) { //setup preview TODO: check for userpref
      document.getElementById("pbtn"+qn).addEventListener('click', function() {showPreview(qn)});
      if (!params.qtype.match(/matrix/)) { //no live preview for matrix types
        if (LivePreviews.hasOwnProperty(qn)) {
          delete LivePreviews[qn]; // want to reinit
        }
        setupLivePreview(qn);
        document.getElementById("qn"+qn).addEventListener('keyup', updateLivePreview);
        //document.getElementById("pbtn"+qn).style.display = 'none';
      }
    }
    if (params.format === 'debit') {
      document.getElementById("qn"+qn).addEventListener('keyup', editdebit);
    } else if (params.format === 'credit') {
      document.getElementById("qn"+qn).addEventListener('keyup', editcredit);
    } else if (params.format === 'normslider') {
      imathasDraw.addnormslider(qn);
    }
    if (params.tip) {
      if (el = document.getElementById("qn"+qn+"-0") && el.type == 'text') {
        // setup for matrix sub-parts
        i=0;
        while (document.getElementById("qn"+qn+"-"+i)) {
          setupTips("qn"+qn+"-"+i, params.tip);
          i++;
        }
      } else {
        setupTips("qn"+qn, params.tip);
      }
    }
    // TODO: handle setting up the individual question types, particularly
    // setting up the Preview button and the on
  }
}

// setup tip focus/blur handlers
function setupTips(id, tip) {
  var el = document.getElementById(id);
  var ref = el.getAttribute('aria-describedby').substr(4);
  el.addEventListener('focus', function() {
    showehdd(id, tip, ref);
  });
  el.addEventListener('blur', hideeh);
  el.addEventListener('click', function() {
    reshrinkeh(id);
  });
}

var LivePreviews = [];
function setupLivePreview(qn) {
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
			  Init: function() {
  				$("#p"+qn).css("positive","relative")
  					.append('<span id="lpbuf1'+qn+'" style="visibility:hidden;position:absolute;"></span>')
  					.append('<span id="lpbuf2'+qn+'" style="visibility:hidden;position:absolute;"></span>');
  				this.preview = document.getElementById("lpbuf1"+qn);
  				this.buffer = document.getElementById("lpbuf2"+qn);
          showPreview(qn);  //TODO: review this
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
				      if (typeof MathJax != "undefined" && $(this.buffer).children(".mj").length>0) {//has MathJax elements
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
          return preformat(text, qtype, calcformat);
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
			LivePreviews[qn].Init();
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

function normalizemathunicode(str) {
	str = str.replace(/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/g, "");
	str = str.replace(/\u2013|\u2014|\u2015|\u2212/g, "-");
	str = str.replace(/\u2044|\u2215/g, "/");
	str = str.replace(/∞/g,"oo").replace(/≤/g,"<=").replace(/≥/g,">=").replace(/∪/g,"U");
	str = str.replace(/±/g,"+-").replace(/÷/g,"/").replace(/·|✕|×|⋅/g,"*");
	str = str.replace(/√/g,"sqrt").replace(/∛/g,"root(3)");
	str = str.replace(/²/g,"^2").replace(/³/g,"^3");
	str = str.replace(/\u2329/g, "<").replace(/\u232a/g, ">");
	str = str.replace(/₀/g,"_0").replace(/₁/g,"_1").replace(/₂/g,"_2").replace(/₃/g,"_3");
	str = str.replace(/\bOO\b/i,"oo");
	str = str.replace(/θ/,"theta").replace(/ϕ/,"phi").replace(/φ/,"phi").replace(/π/,"pi").replace(/σ/,"sigma").replace(/μ/,"mu")
	str = str.replace(/α/,"alpha").replace(/β/,"beta").replace(/γ/,"gamma").replace(/δ/,"delta").replace(/ε/,"epsilon").replace(/κ/,"kappa");
	str = str.replace(/λ/,"lambda").replace(/ρ/,"rho").replace(/τ/,"tau").replace(/χ/,"chi").replace(/ω/,"omega");
	str = str.replace(/Ω/,"Omega").replace(/Γ/,"Gamma").replace(/Φ/,"Phi").replace(/Δ/,"Delta").replace(/Σ/,"Sigma");
	return str;
}

/**
 * Called on preview button click on livepreview timeout
 * Displays rendered preview, along with
 */
function showPreview(qn) {
  var params = allParams[qn];
  var outstr = '';
  var res = processByType(qn);
  var outstr = '`' + res.str + '`';
  if (res.dispvalstr != '' && params.calcformat.indexOf('showval')!=-1) {
    outstr += ' = ' + res.dispvalstr;
  }
  if (res.err != '' && res.str != '') {
    outstr += (outstr=='``')?'':'. ' + '<span class=noticetext>' + res.err + '</span>';
  }
  if (LivePreviews.hasOwnProperty(qn)) {
    LivePreviews[qn].RenderNow(outstr);
  } else {
    var previewel = document.getElementById('p'+qn);
    previewel.innerHTML = outstr;
    rendermathnode(previewel);
  }
}

/**
 * Takes a form element as input. Runs presubmit on everything
 * and adds the elements to the form.
 */
function preSubmitForm(form) {
  for (var qn in allParams) {
    // reuse existing if there is one
    var ex;
    if (ex = document.getElementById('qn' + qn + '-val')) {
      ex.value = preSubmit(qn);
    } else {
      var el = document.createElement('input');
      el.type = 'hidden';
      el.name = 'qn' + qn + '-val';
      el.value = preSubmit(qn);
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
  return res.submitstr;
}

/**
 * Processes each question type.  Return object has:
 *   .str:  the input, formatted for rendering
 *   .dispvalstr: the evaluated string, formatted for display
 *   .submitstr: the evaluated answer, formatted for submission
 */
function processByType(qn) {
  var params = allParams[qn];
  var res;
  if (params.hasOwnProperty('matrixsize')) {
    res = processSizedMatrix(qn);
  } else {
    var str = document.getElementById('qn'+qn).value;
    var res;
    switch (params.qtype) {
      case 'calculated':
        res = processCalculated(str, params.calcformat);
        break;
    }
    res.str = preformat(str, params.qtype, params.calcformat);
  }
  return res;
}



/**
 * Formats the string for rendering
 */
function preformat(text, qtype, calcformat) {
  text = normalizemathunicode(text);
  if (qtype == 'calcinterval') {
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
    text = text.replace(/</g, '(:').replace(/>/g, ':)');
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

function AMnumfuncPrepVar(qn,str) {
  var vars = allParams[qn].vars;
  var vl = vars.join('|');
  var fvarslist = allParams[qn].fvars.join('|');
  vars.push("DNE");

  if (vl.match(/lambda/)) {
  	  str = str.replace(/lamda/, 'lambda');
  }

  str = str.replace(/,/g,"").replace(/^\s+/,'').replace(/\s+$/,'');
  var foundaltcap = [];
  var dispstr = str;
  dispstr = dispstr.replace(/(arcsinh|arccosh|arctanh|arcsech|arccsch|arccoth|arcsin|arccos|arctan|arcsec|arccsc|arccot|sinh|cosh|tanh|sech|csch|coth|sqrt|ln|log|exp|sin|cos|tan|sec|csc|cot|abs|root)/g, functoindex);
  for (var i=0; i<vars.length; i++) {
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
  dispstr = dispstr.replace(/@v(\d+)@/g, function(match,contents) {
  	  return vars[contents];
       });

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
		  } else if (!isgreek && vars[i]!="varE") {
			  varstoquote.push(vars[i]);
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

  //Correct rendering when f or g is a variable not a function
  if (vl.match(/\bf\b/) && !fvarslist.match(/\bf\b/)) {
  	  dispstr = dispstr.replace(/([^a-zA-Z])f\^([\d\.]+)([^\d\.])/g, "$1f^$2{::}$3");
  	  dispstr = dispstr.replace(/([^a-zA-Z])f\(/g, "$1f{::}(");
  }
  if (vl.match(/\bg\b/) && !fvarslist.match(/\bg\b/)) {
  	  dispstr = dispstr.replace(/([^a-zA-Z])g\^([\d\.]+)([^\d\.])/g, "$1g^$2{::}$3");
  	  dispstr = dispstr.replace(/([^a-zA-Z])g\(/g, "$1g{::}(");
  }
  return [str,dispstr,vars.join("|")];
}


/**
 *  These functions should return:
 *   .str:  the input, formatted for rendering
 *   .dispvalstr: the evaluated string, formatted for display
 *   .submitstr: the evaluated answer, formatted for submission
 */

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

function singlevalsyntaxcheck(str,format) {
	if (str.match(/DNE/i)) {
		 return '';
	} else if (str.match(/oo$/) || str.match(/oo\W/)) {
		 return '';
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
		  str = str.replace(/(x|X|\u00D7)/,"xx");
		  if (!str.match(/^\-?[1-9](\.\d*)?(\*|xx)10\^(\(?\-?\d+\)?)$/)) {
		  	if (format.indexOf('scinotordec')==-1) { //not scinotordec
		  		return (_("not valid scientific notation")+". ");
		  	} else if (!str.match(/^\-?(\d+|\d+\.\d*|\d*\.\d+)$/)) {
		  		return (_("not valid decimal or scientific notation")+". ");
		  	}
		  }
	} else if (format.indexOf('decimal')!=-1 && format.indexOf('nodecimal')==-1) {
		if (!str.match(/^\-?(\d+|\d+\.\d*|\d*\.\d+)$/)) {
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
	  	  reg = new RegExp("(repvars\\d+|arc|sqrt|root|ln|log|exp|sinh|cosh|tanh|sech|csch|coth|sin|cos|tan|sec|csc|cot|abs|pi|sign|DNE|e|oo|"+vl+")", "ig");
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
  evalstr = evalstr.replace(/,/, '');
  if (evalstr.match(/^\s*[+-]?\s*((\d+(\.\d*)?)|(\.\d+))\s*%\s*$/)) {//single percent
    evalstr = evalstr.replace(/%/,'') + '/100';
  }
  if (format.indexOf('mixed')!=-1) {
    evalstr = evalstr.replace(/(-?\s*\d+)\s+(\d+|\(\d+\))\s*\/\s*(\d+|\(\d+\))/g,"($1+$2/$3)");
  }
  if (format.indexOf('scinot')!=-1) {
      evalstr = evalstr.replace("xx","*");
  }
  try {
    var res = scopedmatheval(evalstr);
    return [res, ''];
  } catch(e) {
    return [NaN, _("syntax incomplete")+". "];
  }
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

function prepWithMath(str) {
	str = str.replace(/\b(abs|acos|asin|atan|ceil|floor|cos|sin|tan|sqrt|exp|max|min|pow)\(/g, 'Math.$1(');
	str = str.replace(/\(E\)/g,'(Math.E)');
	str = str.replace(/\((PI|pi)\)/g,'(Math.PI)');
	return str;
}

return {
  init: init,
  preSubmitForm: preSubmitForm,
  preSubmit: preSubmit
};

}(jQuery));

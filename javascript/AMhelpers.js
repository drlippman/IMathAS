//IMathAS:  Handles preview buttons and pre-submit calculations for assessments
//(c) 2006 David Lippman

//define these to be overwritten later in case the corresponding options aren't used
function updateeeddpos() {}
function updateehpos() {}

var LivePreviews = [];
function setupLivePreview(qn) {
	if (!LivePreviews.hasOwnProperty(qn)) {
		if (mathRenderer=="MathJax" || mathRenderer=="Katex") {
			LivePreviews[qn] = {
			  delay: (mathRenderer=="MathJax"?100:20),   // delay after keystroke before updating
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
				      if ($(this.buffer).children(".mj").length>0) {//has MathJax elements
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
			  	if (intcalctoproc.hasOwnProperty(qn)) {
			  		if (!calcformat[qn].match(/inequality/)) {
			  			text = text.replace(/U/g,"uu");
			  		} else {
			  			text = text.replace(/<=/g,' le ').replace(/>=/g,' ge ').replace(/</g,' lt ').replace(/>/g,' gt ');
			  			if (text.match(/all\s*real/i)) {
			  				text = "text("+text+")";
			  			}
			  		}
			  	} else if (vlist.hasOwnProperty(qn)) {
			  		text = AMnumfuncPrepVar(qn, text)[1];

			  	} else if (calcformat.hasOwnProperty(qn)) {
			  		var format = calcformat[qn];
			  		if (format.indexOf('list')==-1 && format.indexOf('set')==-1) {
			  			text = text.replace(/(\d)\s*,\s*(?=\d{3}\b)/g,"$1");
			  		}
			  	}
			  	return text;
			  },

			  CreatePreview: function () {
			    this.timeout = null;
			    if (this.mjPending) return;
			    if (document.getElementById("tc"+qn)==null) { //string preview
			    	    var text = document.getElementById("qn"+qn).value;
			    } else {
			    	    var text = document.getElementById("tc"+qn).value;
			    }
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
			    updateeeddpos();
			    updateehpos();
			  }

			};
			LivePreviews[qn].callback = MathJax.Callback(["CreatePreview",LivePreviews[qn]]);
			LivePreviews[qn].callback.autoReset = true;  // make sure it can run more than once
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
function updateLivePreview(targ) {
	var qn = targ.id.substr(2);
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
	str = str.replace(/θ/,"theta").replace(/φ/,"phi").replace(/π/,"pi").replace(/σ/,"sigma").replace(/μ/,"mu")
	str = str.replace(/α/,"alpha").replace(/β/,"beta").replace(/γ/,"gamma").replace(/δ/,"delta").replace(/ε/,"epsilon").replace(/κ/,"kappa");
	str = str.replace(/λ/,"lambda").replace(/ρ/,"rho").replace(/τ/,"tau").replace(/χ/,"chi").replace(/ω/,"omega");
	str = str.replace(/Ω/,"Omega").replace(/Γ/,"Gamma").replace(/Φ/,"Phi").replace(/Δ/,"Delta").replace(/Σ/,"Sigma");
	return str;
}
function wrapAMnotice(str) {
	return '<span class="AMHnotice">'+str+'</span>';
}
//handles preview button for calculated type
function calculate(inputId,outputId,format) {
  var fullstr = document.getElementById(inputId).value;
  fullstr = normalizemathunicode(fullstr);
  fullstr = fullstr.replace(/=/,'');

  if (format.indexOf('list')!=-1) {
	  var strarr = fullstr.split(/,/);
  } else if (format.indexOf('set')!=-1) {
  	  var strarr = fullstr.replace(/[\{\}]/g,'').split(/,/);
  } else {
	  var strarr = new Array();
	  strarr[0] = fullstr;
  }
  for (var sc=0;sc<strarr.length;sc++) {
	  str = strarr[sc];
	  str = str.replace(/(\d)\s*,\s*(?=\d{3}\b)/g,"$1");
	  var err = "";
	  if (str.match(/DNE/i)) {
		  str = str.toUpperCase();
	  } else if (str.match(/oo$/) || str.match(/oo\W/)) {
		  str = "`"+str+"`";
	  } else {
		  err += singlevalsyntaxcheck(str,format);
		  if (str.match(/,/)) {
		  	  err += _("Invalid use of a comma.");
		  }
		  if (format.indexOf('allowxtimes')!=-1) {
		  	  str = str.replace(/(x|X|\u00D7)/,"*");
		  }
		  if (format.indexOf('mixed')!=-1) {
		  	  str = str.replace(/_/g,' ');
		  } else if (format.indexOf('scinot')!=-1) {
			  str = str.replace(/(x|X|\u00D7)/,"xx");
		  } else {
		  	  str = str.replace(/([0-9])\s+([0-9])/g,"$1*$2");
		  	  str = str.replace(/\s/g,'');
		  }
		  err += syntaxcheckexpr(str,format);
		  try {
			  var evalstr = str;
			  evalstr = evalstr.replace(',','*NaN*'); //force eval error on lingering commas
			  if (evalstr.match(/^\s*[+-]?\s*((\d+(\.\d*)?)|(\.\d+))\s*%\s*$/)) {//single percent
			  	evalstr = evalstr.replace(/%/,'') + '/100';
			  }
			  if (format.indexOf('mixed')!=-1) {
				  evalstr = evalstr.replace(/(\d+)\s+(\d+|\(\d+\))\s*\/\s*(\d+|\(\d+\))/g,"($1+$2/$3)");
			  }
			  if (format.indexOf('scinot')!=-1) {
			  	  evalstr = evalstr.replace("xx","*");
			  }
			  var res = eval(prepWithMath(mathjs(evalstr)));
		  } catch(e) {
		  	  err = _("syntax incomplete")+'. '+err;
		  	  res = NaN;
		  }
		  if (!isNaN(res) && res!="Infinity") {
			  if (format.indexOf('fraction')!=-1 || format.indexOf('reducedfraction')!=-1 || format.indexOf('mixednumber')!=-1 || format.indexOf('scinot')!=-1 || format.indexOf('noval')!=-1) {
				  str = "`"+str+"` " + wrapAMnotice(err);
			  } else {
				  str = "`"+str+" =` "+(Math.abs(res)<1e-15?0:res)+". "+wrapAMnotice(err);
			  }
		  } else if (str!="") {
			  str = "`"+str+"` = "+_("undefined")+". "+wrapAMnotice(err);
		  }
	  }
	  strarr[sc] = str+" ";
  }
  fullstr = strarr.join(', ');
  if (format.indexOf('set')!=-1) {
  	  if (!document.getElementById(inputId).value.match(/^\s*{.*?}\s*$/)) {
  	  	  fullstr += wrapAMnotice(_("syntax error: this answer must be in set notation, a list wrapped in braces like {1,2,3}"));
  	  } else {
  	  	  fullstr = '{'+fullstr+'}';
  	  }
  }
  var qn = outputId.substr(1);
  setupLivePreview(qn);
  LivePreviews[qn].RenderNow(fullstr);
  /*
  var outnode = document.getElementById(outputId);
  var n = outnode.childNodes.length;
  for (var i=0; i<n; i++)
    outnode.removeChild(outnode.firstChild);
  outnode.appendChild(document.createTextNode(fullstr));
  if (!noMathRender) {
	  rendermathnode(outnode);
  }
  */
}

//Function to convert inequalities into interval notation
function ineqtointerval(strw) {
	strw = strw.replace(/(\d)\s*,\s*(?=\d{3}\b)/g,"$1");
	var strpts = strw.split(/or/);
	for (i=0; i<strpts.length; i++) {
		str = strpts[i];
		var out = '';
		if (pat = str.match(/^([^<]+)\s*(<=?)\s*([a-zA-Z]\(\s*[a-zA-Z]\s*\)|[a-zA-Z]+)\s*(<=?)([^<]+)$/)) {
			if (pat[2]=='<=') {out += '[';} else {out += '(';}
			out += pat[1] + ',' + pat[5];
			if (pat[4]=='<=') {out += ']';} else {out += ')';}
		} else if (pat = str.match(/^([^>]+)\s*(>=?)\s*([a-zA-Z]\(\s*[a-zA-Z]\s*\)|[a-zA-Z]+)\s*(>=?)([^>]+)$/)) {
			if (pat[4]=='>=') {out += '[';} else {out += '(';}
			out += pat[5] + ',' + pat[1];
			if (pat[2]=='>=') {out += ']';} else {out += ')';}
		} else if (pat = str.match(/^([^><]+)\s*([><]=?)\s*([a-zA-Z]\(\s*[a-zA-Z]\s*\)|[a-zA-Z]+)\s*$/)) {
			if (pat[2]=='>') { out = '(-oo,'+pat[1]+')';} else
			if (pat[2]=='>=') { out = '(-oo,'+pat[1]+']';} else
			if (pat[2]=='<') { out = '('+pat[1]+',oo)';} else
			if (pat[2]=='<=') { out = '['+pat[1]+',oo)';}
		} else if (pat = str.match(/^\s*([a-zA-Z]\(\s*[a-zA-Z]\s*\)|[a-zA-Z]+)\s*([><]=?)\s*([^><]+)$/)) {
			if (pat[2]=='<') { out = '(-oo,'+pat[3]+')';} else
			if (pat[2]=='<=') { out = '(-oo,'+pat[3]+']';} else
			if (pat[2]=='>') { out = '('+pat[3]+',oo)';} else
			if (pat[2]=='>=') { out = '['+pat[3]+',oo)';}
		} else if (str.match(/all\s*real/i)) {
			out = '(-oo,oo)';
		} else {
			out = '';
		}
		strpts[i] = out;
	}
	out =  strpts.join('U');
	return out;
}
//preview for calcinterval type
function intcalculate(inputId,outputId,format) {
  var fullstr = document.getElementById(inputId).value;
  if (format.indexOf('mixed')!=-1) {
	  fullstr = fullstr.replace(/_/g,' ').replace(/^\s+/,'').replace(/\s+$/,'');
  } else {
	  fullstr = fullstr.replace(/\s+/g,'');
  }
  fullstr = normalizemathunicode(fullstr);
  if (fullstr.match(/DNE/i)) {
	  fullstr = fullstr.toUpperCase();
  } else if (fullstr.replace(/\s+/g,'')=='') {
	  fullstr = _("no answer given");
  } else {
	  var calcvals = new Array();
	  var calcstrarr = new Array();
	  if (format.indexOf('inequality')!=-1) {
		fullstr = fullstr.replace(/or/g,' or ');
		var origstr = fullstr;
		fullstr = ineqtointerval(fullstr);
		var pats = str.match(/\b([a-zA-Z]\(\s*[a-zA-Z]\s*\)|[a-zA-Z]+\b)/);
		var pat = str.match(/([a-zA-Z]\(\s*[a-zA-Z]\s*\)|[a-zA-Z]+)/);
		var ineqvar = (pats != null)?pats[1]:((pat != null)?pat[1]:'');
	  } else {
	  	  var origstr = fullstr;
	  }
	  if (format.indexOf('list')!=-1) {
	  	var lastpos = 0; var strarr = [];
		for (var pos = 1; pos<fullstr.length-1; pos++) {
			if (fullstr.charAt(pos)==',') {
				if ((fullstr.charAt(pos-1)==')' || fullstr.charAt(pos-1)==']')
					&& (fullstr.charAt(pos+1)=='(' || fullstr.charAt(pos+1)=='[')) {
					strarr.push(fullstr.substring(lastpos,pos));
					lastpos = pos+1;
				}
			}
		}
		strarr.push(fullstr.substring(lastpos));
	  } else {
	  	  var strarr = fullstr.split(/\s*U\s*/i);
	  }
	  var isok = true; var fullerr="";
	  for (i=0; i<strarr.length; i++) {
		  str = strarr[i];
		  sm = str.charAt(0);
		  em = str.charAt(str.length-1);
		  vals = str.substring(1,str.length-1);
		  vals = vals.split(/,/);
		  if (vals.length != 2 || ((sm != '(' && sm != '[') || (em != ')' && em != ']'))) {
		  	  if(format.indexOf('inequality')!=-1) {
		  	  	  origstr = origstr.replace('<=','le').replace('>=','ge').replace('<','lt').replace('>','gt');
		  	  	  fullstr = "`"+origstr+"`: " + wrapAMnotice(_("invalid inequality notation"));
		  	  } else {
		  	  	  fullstr = "`"+origstr.replace(/U/g,"uu")+"`: " + wrapAMnotice(_("invalid interval notation"));
		  	  }
			  isok = false;
			  break;
		  }
		  for (j=0; j<2; j++) {
			  if (vals[j].match(/oo$/) || vals[j].match(/oo\W/)) {
				  calcvals[j] = vals[j];
			  } else {
				  var err = "";
				  res = NaN;
				  err += singlevalsyntaxcheck(vals[j], format);
				  if (format.indexOf('mixed')!=-1) {
					  vals[j] = vals[j].replace(/_/g,' ');
				  } else {
					  vals[j] = vals[j].replace(/\s/g,'');
				  }
				  err += syntaxcheckexpr(vals[j], format);

				  if (err=='') {
					  try {
					  	toeval = vals[j];
					  	if (format.indexOf('mixed')!=-1) {
					  		toeval = toeval.replace(/(\d+)\s+(\d+|\(\d+\))\s*\/\s*(\d+|\(\d+\))/g,"($1+$2/$3)");
					  	}
					  	var res = eval(prepWithMath(mathjs(toeval)));
					  } catch(e) {
					  	err = _("syntax incomplete")+". ";
					  }
				  }
				  if (!isNaN(res) && res!="Infinity") {
					 // if (format.indexOf('fraction')!=-1 || format.indexOf('reducedfraction')!=-1 || format.indexOf('mixednumber')!=-1) {
						  vals[j] = vals[j];
						  calcvals[j] = (Math.abs(res)<1e-15?0:res).toString();//+wrapAMnotice(err);
					  //} else {
						//  str = "`"+str+" =` "+(Math.abs(res)<1e-15?0:res)+err;
					  //}
				  } else {
				  	  calcvals[j] = _("undefined");
				  }
				  if (err != '') {
				  	  fullerr += err;
				  }

			  }
		  }

		  strarr[i] = sm + vals[0] + ',' + vals[1] + em;
		  if (format.indexOf('inequality')!=-1) {
		  	  if (calcvals[0].match(/oo/)) {
				  if (calcvals[1].match(/oo/)) {
					  calcstrarr[i] = 'RR';
				  } else {
					  calcstrarr[i] = ineqvar + (em==']'?'le':'lt') + calcvals[1];
				  }
			  } else if (calcvals[1].match(/oo/)) {
				  calcstrarr[i] = ineqvar + (sm=='['?'ge':'gt') + calcvals[0];
			  } else {
				  calcstrarr[i] = calcvals[0] + (sm=='['?'le':'lt') + ineqvar + (em==']'?'le':'lt') + calcvals[1];
			  }
			  calcstrarr[i] = calcstrarr[i].replace("undefined",'"undefined"');
		  } else {
			calcstrarr[i] = sm + calcvals[0] + ',' + calcvals[1] + em;
		  }

	 }
	 if (isok) {
		 if (format.indexOf('inequality')!=-1) {
			 if (origstr.match(/all\s*real/)) {
				 fullstr = origstr;
			 } else {
				 origstr = origstr.replace(/or/g,' \\ "or" \\ ');
				 origstr = origstr.replace(/<=/g,'le');
				 origstr = origstr.replace(/>=/g,'ge');
				 origstr = origstr.replace(/</g,'lt');
				 origstr = origstr.replace(/>/g,'gt');
				 if (format.indexOf('fraction')!=-1 || format.indexOf('reducedfraction')!=-1 || format.indexOf('mixednumber')!=-1 || format.indexOf('scinot')!=-1 || format.indexOf('noval')!=-1) {
				 	 fullstr = '`'+origstr + '`'+". "+wrapAMnotice(fullerr);
				 } else {
				 	 fullstr = '`'+origstr + '= ' + calcstrarr.join(' \\ "or" \\ ')+'`'+". "+wrapAMnotice(fullerr);
				 }
			 }
		 } else {
		 	 if (format.indexOf('fraction')!=-1 || format.indexOf('reducedfraction')!=-1 || format.indexOf('mixednumber')!=-1 || format.indexOf('scinot')!=-1 || format.indexOf('noval')!=-1) {
				  fullstr = '`'+strarr.join('uu') + '`'+". "+wrapAMnotice(fullerr);
			 } else {
			 	 if (format.indexOf('list')!=-1) {
			 	 	 fullstr = '`'+strarr.join(',') + '` = ' + calcstrarr.join(' , ')+". "+wrapAMnotice(fullerr);
			 	 } else {
			 	 	 fullstr = '`'+strarr.join('uu') + '` = ' + calcstrarr.join(' U ')+". "+wrapAMnotice(fullerr);
			 	 }
			 }
		 }
	 }
  }

  var qn = outputId.substr(1);
  setupLivePreview(qn);
  LivePreviews[qn].RenderNow(fullstr);
  /*var outnode = document.getElementById(outputId);
  var n = outnode.childNodes.length;
  for (var i=0; i<n; i++)
    outnode.removeChild(outnode.firstChild);
  outnode.appendChild(document.createTextNode(fullstr));
  if (!noMathRender) {
	  rendermathnode(outnode);
  }
  */

}

//preview for calcntuple
function ntuplecalc(inputId,outputId,format) {
	var fullstr = document.getElementById(inputId).value;
	fullstr = normalizemathunicode(fullstr);
	if (format.indexOf('mixed')!=-1) {
		fullstr = fullstr.replace(/_/g,' ').replace(/^\s+/,'').replace(/\s+$/,'');
	} else {
		fullstr = fullstr.replace(/\s+/g,'');
	}
	if (fullstr.match(/DNE/i)) {
		fullstr = fullstr.toUpperCase();
		outcalced = 'DNE';
		outstr = 'DNE';
	} else {
		var outcalced = '';
		var NCdepth = 0;
		var lastcut = 0;
		var err = "";
		var notationok = true;
		if (!fullstr.charAt(0).match(/[\(\[\<\{]/)) {
			notationok=false;
		}
		for (var i=0; i<fullstr.length; i++) {
			dec = false;
			if (NCdepth==0) {
				outcalced += fullstr.charAt(i);
				lastcut = i+1;
				if (fullstr.charAt(i)==',') {
					if (!fullstr.charAt(i+1).match(/[\(\[\<\{]/) || !fullstr.charAt(i-1).match(/[\)\]\>\}]/)) {
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
				sub = fullstr.substring(lastcut,i);
				res = NaN;
				err += singlevalsyntaxcheck(sub, format);
				err += syntaxcheckexpr(sub, format);
				try {
					if (format.indexOf('mixed')!=-1) {
						sub = sub.replace(/(\d+)\s+(\d+|\(\d+\))\s*\/\s*(\d+|\(\d+\))/g,"($1+$2/$3)");
					}
					var res = eval(prepWithMath(mathjs(sub)));
				} catch(e) {
					err += _("syntax incomplete")+". ";
				}
				if (!isNaN(res) && res!="Infinity") {
					outcalced += res;
				} else {
					outcalced += _("undefined");
				}
				outcalced += fullstr.charAt(i);
				lastcut = i+1;
			}
		}
		if (NCdepth!=0) {
			notationok = false;
		}
		if (notationok==false) {
			err += _("Invalid notation")+". ";
		}
		//outstr = '`'+fullstr+'` = '+outcalced;
		if (format.indexOf('fraction')!=-1 || format.indexOf('reducedfraction')!=-1 || format.indexOf('mixednumber')!=-1 || format.indexOf('scinot')!=-1 || format.indexOf('noval')!=-1 || notationok==false) {
			 outstr = '`'+fullstr+'`'+". " + wrapAMnotice(err);
		} else {
			 outstr = '`'+fullstr+'` = '+outcalced +". " + wrapAMnotice(err);
		}
	}
	if (outputId != null) {
		 /*var outnode = document.getElementById(outputId);
		 var n = outnode.childNodes.length;
		 for (var i=0; i<n; i++) {
		    outnode.removeChild(outnode.firstChild);
		 }
		 outnode.appendChild(document.createTextNode(outstr));
		 if (!noMathRender) {
			  rendermathnode(outnode);
		 }*/
		 var qn = outputId.substr(1);
		 setupLivePreview(qn);
		 LivePreviews[qn].RenderNow(outstr);
	}
	 return outcalced;
}

//preview for calccomplex
function complexcalc(inputId,outputId,format) {
	var fullstr = document.getElementById(inputId).value;
	var outcalced, outstr, err;
	fullstr = normalizemathunicode(fullstr);
	if (format.indexOf('mixed')!=-1) {
		fullstr = fullstr.replace(/_/g,' ').replace(/^\s+/,'').replace(/\s+$/,'');
	} else {
		fullstr = fullstr.replace(/\s+/g,'');
	}
	if (fullstr.match(/DNE/i)) {
		fullstr = fullstr.toUpperCase();
		outcalced = 'DNE';
		outstr = 'DNE';
	} else {
		outcalced = ''; err='';
		var arr = fullstr.split(',');
		for (var cnt=0; cnt<arr.length; cnt++) {
			var prep = arr[cnt];
			if (format.indexOf('mixed')!=-1) {
				prep = prep.replace(/(\d+)\s+(\d+|\(\d+\))\s*\/\s*(\d+|\(\d+\))/g,"($1+$2/$3)");
			}
			var prep = mathjs(prep,'i');
			if (format.indexOf("sloppycomplex")==-1) {
				var cparts = parsecomplex(arr[cnt]);
				if (typeof cparts == 'string') {
					err += cparts;
				} else {
					err += singlevalsyntaxcheck(cparts[0], format);
					err += singlevalsyntaxcheck(cparts[1], format);
				}
			}
			err += syntaxcheckexpr(arr[cnt], format);
			if (err == '') {
				try {
				    var real = scopedeval('var i=0;'+prepWithMath(prep));
				    var imag = scopedeval('var i=1;'+prepWithMath(prep));
						var imag2 = scopedeval('var i=-1;'+prepWithMath(prep));
				} catch(e) {
				    err += _("syntax incomplete");
				}
			}
			if (real=="synerr" || imag=="synerr") {
			    err += _("syntax incomplete");
			    real = NaN;
			}
			if (!isNaN(real) && real!="Infinity" && !isNaN(imag) && !isNaN(imag2) && imag!="Infinity") {
				imag -= real;
				if (cnt!=0) {
					outcalced += ',';
				}
				outcalced += real+(imag>=0?'+':'')+imag+'i';
			} else {
				outcalced += _("undefined");
				break;
			}
		}
		if (format.indexOf('fraction')!=-1 || format.indexOf('reducedfraction')!=-1 || format.indexOf('mixednumber')!=-1 || format.indexOf('scinot')!=-1 || format.indexOf('noval')!=-1) {
			outstr = '`'+fullstr+'`'+". "+wrapAMnotice(err);
		} else {
			outstr = '`'+fullstr+'` = '+outcalced+". "+wrapAMnotice(err);
		}
	}
	if (outputId != null) {
		 /*var outnode = document.getElementById(outputId);
		 var n = outnode.childNodes.length;
		 for (var i=0; i<n; i++) {
		    outnode.removeChild(outnode.firstChild);
		 }
		 outnode.appendChild(document.createTextNode(outstr));
		 if (!noMathRender) {
			  rendermathnode(outnode);
		 }*/
		 var qn = outputId.substr(1);
		 setupLivePreview(qn);
		 LivePreviews[qn].RenderNow(outstr);
	}
	 return outcalced;
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

function matrixcalc(inputId,outputId,rows,cols) {

	function calced(estr) {
		var err='';
		try {
			var res = eval(prepWithMath(mathjs(estr)));
		} catch(e) {
			err = _("syntax incomplete");
		}
		if (!isNaN(res) && res!="Infinity")
			estr = (Math.abs(res)<1e-15?0:res)+err;
		else if (estr!="") estr = _("undefined");
		return estr;
	}
	if (rows!=null && cols!=null) {
		var count=0;
		var str = "[";
		var calcstr = "[";
		for (var row=0; row < rows; row++) {
			if (row>0) { str += ","; calcstr += ","}
			str += "(";
			calcstr += "(";
			for (var col=0; col<cols; col++) {
				if (col>0) {str += ","; calcstr += ",";}
				val = normalizemathunicode(document.getElementById(inputId+'-'+count).value);
				str += val;
				calcstr += calced(val);
				count++;
			}
			str += ")";
			calcstr += ")";
		}
		str += "]";
		calcstr += "]";
	} else {
		var str = normalizemathunicode(document.getElementById(inputId).value);
		var calcstr = str;
		var MCdepth = 0;
		calcstr = calcstr.replace('[','(');
		calcstr = calcstr.replace(']',')');
		calcstr = calcstr.replace(/\s+/g,'');
		var calclist = new Array();
		calcstr = calcstr.substring(1,calcstr.length-1);
		var lastcut = 0;
		for (var i=0; i<calcstr.length; i++) {
			if (calcstr.charAt(i)=='(') {
				MCdepth++;
			} else if (calcstr.charAt(i)==')') {
				MCdepth--;
			} else if (calcstr.charAt(i)==',' && MCdepth==0) {
				calclist[calclist.length] = calcstr.substring(lastcut+1,i-1);
				lastcut = i+1;
			}
		}
		calclist[calclist.length] = calcstr.substring(lastcut+1,calcstr.length-1);
		for (var i=0; i<calclist.length; i++) {
			calclist2 = calclist[i].split(',');
			for (var j=0; j<calclist2.length; j++) {
				calclist2[j] = calced(calclist2[j]);
			}
			calclist[i] = calclist2.join(',');
		}
		calcstr = '[('+calclist.join('),(')+')]';
	}
	//calcstr = calcstr.replace(/([^\[\(\)\],]+)/g, calced);
	str = "`"+str+"` = `"+calcstr+"`";

	if (outputId != null) {
		var outnode = document.getElementById(outputId);
		var n = outnode.childNodes.length;
		for (var i=0; i<n; i++)
			outnode.removeChild(outnode.firstChild);
		outnode.appendChild(document.createTextNode(str));
		if (!noMathRender) {
			rendermathnode(outnode);
		}
	}
	return calcstr;
}

function mathjsformat(inputId,outputId) {
  var str = document.getElementById(inputId).value;
  var outnode = document.getElementById(outputId);
  outnode.value = mathjs(str);
}

function stringqpreview(inputId,outputId) {
	var str = document.getElementById(inputId).value;

	var qn = outputId.substr(1);
	setupLivePreview(qn);
	LivePreviews[qn].RenderNow("`"+str+"`");

	/*var outnode = document.getElementById(outputId);
	var n = outnode.childNodes.length;
	for (var i=0; i<n; i++)
		outnode.removeChild(outnode.firstChild);
	outnode.appendChild(document.createTextNode('`'+str+'`'));
	if (!noMathRender) {
		rendermathnode(outnode);
	}*/
}

function AMnumfuncPrepVar(qn,str) {
  var vl = vlist[qn];
  var fl = flist[qn];
  var vars = vl.split("|");
  vars.push("DNE");

  str = str.replace(/,/g,"").replace(/^\s+/,'').replace(/\s+$/,'');
  str = normalizemathunicode(str);
  var foundaltcap = [];
  var dispstr = str;
  dispstr = dispstr.replace(/(arcsinh|arccosh|arctanh|arcsech|arccsch|arccoth|arcsin|arccos|arctan|arcsec|arccsc|arccot|sinh|cosh|tanh|sech|csch|coth|sqrt|ln|log|sin|cos|tan|sec|csc|cot|abs|root)/g, functoindex);
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
		  if (arraysearch(vars[i].toLowerCase(), greekletters)!=-1) {
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
		  	if (varpts[1].length>1 && arraysearch(varpts[1].toLowerCase(), greekletters)==-1) {
		  		varpts[1] = '"'+varpts[1]+'"';
		  	}
		  	if (varpts[2].length>1 && arraysearch(varpts[2].toLowerCase(), greekletters)==-1) {
		  		varpts[2] = '"'+varpts[2]+'"';
		  	}
		  	dispstr = dispstr.replace(new RegExp(varpts[0],regmod), varpts[1]+'_'+varpts[2]);
		  	//this repvars was needed to workaround with mathjs confusion with subscripted variables
		  	str = str.replace(new RegExp(varpts[0],"g"), "repvars"+i);
		  	vars[i] = "repvars"+i;
		  } else if (!isgreek && vars[i]!="varE") {
			  varstoquote.push(vars[i]);
		  }
		  /*
		  if (!isgreek && vars[i].match(/^\w+_\d*[a-zA-Z]+\w+$/)) {
		  	if (!foundaltcap[i]) {
		  		regmod = "gi";
		  	} else {
		  		regmod = "g";
		  	}
		  	//var varpts = vars[i].match(new RegExp(/^(\w+)_(\d*[a-zA-Z]+\w+)$/,regmod));
		  	var varpts = new RegExp(/^(\w+)_(\d*[a-zA-Z]+\w+)$/,regmod).exec(vars[i]);
		  	dispstr = dispstr.replace(new RegExp(varpts[0],regmod), '"'+varpts[1]+'"_"'+varpts[2]+'"');
		  	//this repvars was needed to workaround with mathjs confusion with subscripted variables
		  	str = str.replace(varpts[0], "repvars"+i);
		  	vars[i] = "repvars"+i;
		  }
		  if (!isgreek && !vars[i].match(/^(\w)_\d+$/) && vars[i]!="varE") {
			  varstoquote.push(vars[i]);
		  }
		  */
	  }
  }
  if (varstoquote.length>0) {
	  vltq = varstoquote.join("|");
	  var reg = new RegExp("("+vltq+")","g");
	  dispstr = dispstr.replace(reg,"\"$1\"");
  }
  dispstr = dispstr.replace("varE","E");
  dispstr = dispstr.replace(/@(\d+)@/g, indextofunc);

  return [str,dispstr,vars.join("|")];
}

//preview button for numfunc type
function AMpreview(inputId,outputId) {
  var qn = inputId.slice(2);
  var strprocess = AMnumfuncPrepVar(qn, document.getElementById(inputId).value);
  var str = strprocess[0];
  var dispstr = strprocess[1];
  var vl = strprocess[2];
  //the following does a quick syntax check of the formula

  var fl = flist[qn];

  ptlist = pts[qn].split(",");
  var err = '';
  var tstpt = 0; var res = NaN; var isnoteqn = false;
  if (iseqn[qn]==1) {
  	if (!str.match(/=/)) {isnoteqn = true;}
  	else if (str.match(/=/g).length>1) {isnoteqn = true;}
	str = str.replace(/(.*)=(.*)/,"$1-($2)");
  } else {
  	if (!str.match(/=/)) {isnoteqn = true;}
  }
  if (fl!='') {
	  reg = new RegExp("("+fl+")\\(","g");
	  str = str.replace(reg,"$1*sin($1+");
  }
  vars = vl.split('|');

  var totesteqn = prepWithMath(mathjs(str,vl));

  while (tstpt<ptlist.length && (isNaN(res) || res=="Infinity")) {
	  var totest = '';
	  testvals = ptlist[tstpt].split("~");

	  for (var j=0; j<vars.length; j++) {
		totest += "var " + vars[j] + "="+testvals[j]+";";
	  }
	  totest += totesteqn;
	  err =_("syntax ok");
	  try {
	    var res = scopedeval(totest);
	  } catch(e) {
	    err = _("syntax error");
	  }
	  if (res=="synerr") {
	  	  err = _("syntax error");
	  }
	  tstpt++;
  }
  var formaterr = syntaxcheckexpr(str,"",vl);
  if (isNaN(res) || res=="Infinity") {
  	  err = _("syntax error");
  }
  if (formaterr!='') {
  	  if (err==_("syntax ok")) {
  	  	  err += ". "+_("warning")+": "+formaterr;
  	  } else {
  	  	  err += ". "+formaterr;
  	  }
  }
  if (iseqn[qn]==1 && isnoteqn) { err = _("syntax error: this is not an equation");}
  else if ((typeof iseqn[qn] === 'undefined') && !isnoteqn) { err = _("syntax error: you gave an equation, not an expression");}
  //outnode.appendChild(document.createTextNode(" " + err));

  if (dispstr=='"DNE"' || dispstr=="oo") {
  	  err = _("syntax ok");
  }
  var qn = outputId.substr(1);
  setupLivePreview(qn);
  LivePreviews[qn].RenderNow('`'+dispstr+'` '+wrapAMnotice(err));

}

//preview for matrix type
function AMmathpreview(inputId,outputId) {

  var str = document.getElementById(inputId).value;

  var outnode = document.getElementById(outputId);
  var n = outnode.childNodes.length;
  for (var i=0; i<n; i++)
    outnode.removeChild(outnode.firstChild);
  //outnode.appendChild(AMparseMath(str));
   outnode.appendChild(document.createTextNode('`'+str+'`'));
    if (!noMathRender) {
	rendermathnode(outnode);
    }

}

function singlevalsyntaxcheck(str,format) {
	if (str.match(/DNE/i)) {
		 return '';
	} else if (str.match(/oo$/) || str.match(/oo\W/)) {
		 return '';
	} else if (format.indexOf('fracordec')!=-1) {
		  str = str.replace(/\s/g,'');
		  if (!str.match(/^\-?\(?\d+\s*\/\s*\-?\d+\)?$/) && !str.match(/^\-?\d+$/) && !str.match(/^\-?(\d+|\d+\.\d*|\d*\.\d+)$/)) {
			return (_(" invalid entry format")+". ");
		  }
	} else if (format.indexOf('fraction')!=-1 || format.indexOf('reducedfraction')!=-1) {
		  str = str.replace(/\s/g,'');
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
			return (_("not valid scientific notation")+". ");
		  }
	}
	return '';
}

function syntaxcheckexpr(str,format,vl) {
	  var err = '';
	  if (format.indexOf('notrig')!=-1 && str.match(/(sin|cos|tan|cot|sec|csc)/i)) {
		  err += _("no trig functions allowed")+". ";
	  } else if (format.indexOf('nodecimal')!=-1 && str.indexOf('.')!=-1) {
		  err += _("no decimals allowed")+". ";
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
	  	  reg = new RegExp("(sqrt|ln|log|sinh|cosh|tanh|sech|csch|coth|sin|cos|tan|sec|csc|cot|abs)\s*("+vl+"|\\d+)", "i");
	  } else {
	  	  reg = new RegExp("(sqrt|ln|log|sinh|cosh|tanh|sech|csch|coth|sin|cos|tan|sec|csc|cot|abs)\s*(\\d+)", "i");
	  }
	  errstuff = str.match(reg);
	  if (errstuff!=null) {
		  err += "["+_("use function notation")+" - "+_("use $1 instead of $2",errstuff[1]+"("+errstuff[2]+")",errstuff[0])+"]. ";
	  }
	  if (vl) {
	  	  reg = new RegExp("(repvars\\d+|arc|sqrt|root|ln|log|sinh|cosh|tanh|sech|csch|coth|sin|cos|tan|sec|csc|cot|abs|pi|sign|DNE|e|oo|"+vl+")", "ig");
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

var greekletters = ['alpha','beta','chi','delta','epsilon','gamma','phi','psi','sigma','rho','theta','lambda','mu','nu','omega','tau'];
var calctoproc = {};
var intcalctoproc = {};
var calcformat = {};
var functoproc ={};
var matcalctoproc = {};
var ntupletoproc = {};
var complextoproc = {};
var callbackstack = {};
var matsize = {};
var vlist = {};
var flist = {};
var pts = {};
var iseqn = {};

function doonsubmit(form,type2,skipconfirm) {
	for (var qn in callbackstack) {
		callbackstack[qn](qn);
	}
	if (form!=null) {
		if (form.className == 'submitted') {
			alert(_("You have already submitted this page.  Please be patient while your submission is processed."));
			form.className = "submitted2";
			return false;
		} else if (form.className == 'submitted2') {
			return false;
		} else {
			form.className = 'submitted';
		}
		if (!skipconfirm) {
			if (type2 && $("input[name=saveforlater]").attr("data-clicked")==1) {
				//var reallysubmit = confirmSubmit2(form);
				var reallysubmit = true;
				$("input[name=saveforlater]").attr("data-clicked",0);
			} else {
				var reallysubmit = confirmSubmit(form);
			}
			if (!reallysubmit) {
				form.className = '';
				return false;
			}
		}
		
	}
	imathasDraw.encodea11ydraw();

	for (var qn in intcalctoproc) { //i=0; i<intcalctoproc.length; i++) {
		qn = parseInt(qn);
		if (document.getElementById("tc"+qn)==null) {continue;}
		fullstr = document.getElementById("tc"+qn).value;
		//fullstr = fullstr.replace(/^\s+/g,'').replace(/\s+$/,'');
		if (calcformat[qn].indexOf('mixed')!=-1) {
			fullstr = fullstr.replace(/_/g,' ').replace(/^\s+/,'').replace(/\s+$/,'');
		} else {
			fullstr = fullstr.replace(/\s+/g,'');
		}
		fullstr = normalizemathunicode(fullstr);
		if (fullstr.match(/DNE/i)) {
			  fullstr = fullstr.toUpperCase();
		  } else {
			  if (calcformat[qn].indexOf('inequality')!=-1) {
				  fullstr = ineqtointerval(fullstr);
			  }
			  if (calcformat[qn].indexOf('list')!=-1) {
				var lastpos = 0; var strarr = [];
				for (var pos = 1; pos<fullstr.length-1; pos++) {
					if (fullstr.charAt(pos)==',') {
						if ((fullstr.charAt(pos-1)==')' || fullstr.charAt(pos-1)==']')
							&& (fullstr.charAt(pos+1)=='(' || fullstr.charAt(pos+1)=='[')) {
							strarr.push(fullstr.substring(lastpos,pos));
							lastpos = pos+1;
						}
					}
				}
				strarr.push(fullstr.substring(lastpos));
			  } else {
				  var strarr = fullstr.split(/\s*U\s*/i);
			  }
			  for (k=0; k<strarr.length; k++) {
				  str = strarr[k];
				  if (str.length>0 && str.match(/,/)) {
					  sm = str.charAt(0);
					  em = str.charAt(str.length-1);
					  vals = str.substring(1,str.length-1);
					  vals = vals.split(/,/);
					  for (j=0; j<2; j++) {
						  if (!vals[j].match(/oo$/) && !vals[j].match(/oo\W/)) {//(!vals[j].match(/oo/)) {
							  var err = "";

							  try {
							  	  if (calcformat[qn].indexOf('mixed')!=-1) {
							  	  	  vals[j] = vals[j].replace(/(\d+)\s+(\d+|\(\d+\))\s*\/\s*(\d+|\(\d+\))/g,"($1+$2/$3)");
							  	  }
							  	  var res = eval(prepWithMath(mathjs(vals[j])));
							  } catch(e) {
							  	  err = "syntax incomplete";
							  }
							  if (!isNaN(res) && res!="Infinity") {
								  vals[j] = (Math.abs(res)<1e-15?0:res)+err;
							  }
						  }
					  }
					  strarr[k] = sm + vals[0] + ',' + vals[1] + em;
				  }
			 }
			 if (calcformat[qn].indexOf('list')!=-1) {
			 	 fullstr = strarr.join(',');
			 } else {
			 	 fullstr = strarr.join('U');
			 }
		  }
		  document.getElementById("qn" + qn).value = fullstr;
	}
	for (var qn in calctoproc) { //i=0; i<calctoproc.length; i++) {
		qn = parseInt(qn);
		if (document.getElementById("tc"+qn)==null) {continue;}
		str = document.getElementById("tc"+qn).value;
		str = normalizemathunicode(str);
		str = str.replace(/=/,'');

		if (calcformat[qn].indexOf('list')!=-1) {
			strarr = str.split(/,/);
		} else if (calcformat[qn].indexOf('set')!=-1) {
			if (!str.match(/^\s*{.*?}\s*$/)) {
				continue;
			} else {
				strarr = str.replace(/^\s*{(.*?)}\s*$/,'$1').split(/,/);
			}
		} else {
			var strarr = new Array();
			strarr[0] = str;
		}
		for (var sc=0;sc<strarr.length;sc++) {
			str = strarr[sc];
			str = str.replace(/(\d)\s*,\s*(?=\d{3}\b)/g,"$1");
			str = str.replace(',','*NaN*'); //force eval error
			//str = str.replace(/,/g,"");
			if (calcformat[qn].indexOf('scinot')!=-1 || calcformat[qn].indexOf('allowxtimes')!=-1) {
				str = str.replace(/(x|X|\u00D7)/,"*");
			}
			if (str.match(/^\s*[+-]?\s*((\d+(\.\d*)?)|(\.\d+))\s*%\s*$/)) {//single percent
				str = str.replace(/%/,'') + '/100';
			}
			str = str.replace(/(\d+)\s*_\s*(\d+|\(\d+\))\s*\/\s*(\d+|\(\d+\))/g,"($1+$2/$3)");
			if (calcformat[qn].indexOf('mixed')!=-1) {
				str = str.replace(/(\d+)\s+(\d+|\(\d+\))\s*\/\s*(\d+|\(\d+\))/g,"($1+$2/$3)");
			}
			if (str.match(/^\s*$/)) {
				var res = '';
			} else if (str.match(/oo$/) || str.match(/oo\W/)) {
				var res = str;
			} else if (str.match(/DNE/i)) {
				var res = str.toUpperCase();
			} else {
				try {
					var res = eval(prepWithMath(mathjs(str)));
				} catch(e) {
					var res = '';
				}
			}
			strarr[sc] = res;
		}
		document.getElementById("qn" + qn).value = strarr.join(',');
	}
	for (var qn in matcalctoproc) {//i=0; i<matcalctoproc.length; i++) {
		qn = parseInt(qn);
		if (matsize[qn]!= null) {
			if (document.getElementById("qn"+qn+"-0")==null) {continue;}
			msize = matsize[qn].split(",");
			str = matrixcalc("qn"+qn,null,msize[0],msize[1]);
		} else {
			if (document.getElementById("tc"+qn)==null) {continue;}
			str = matrixcalc("tc"+qn,null);
		}
		document.getElementById("qn" +  qn).value = str;
	}
	for (var qn in ntupletoproc) {//i=0; i<ntupletoproc.length; i++) {
		qn = parseInt(qn);
		if (document.getElementById("tc"+qn)==null) {continue;}
		str = ntuplecalc("tc"+qn,null,calcformat[qn]);
		document.getElementById("qn" + qn).value = str;
	}
	for (var qn in complextoproc) { //i=0; i<complextoproc.length; i++) {
		qn = parseInt(qn);
		if (document.getElementById("tc"+qn)==null) {continue;}
		str = complexcalc("tc"+qn,null,calcformat[qn]);
		document.getElementById("qn" + qn).value = str;
	}
	for (var qn in functoproc) { //fcnt=0; fcnt<functoproc.length; fcnt++) {
		qn = parseInt(qn);
		if (document.getElementById("tc"+qn)==null) {continue;}
		str = document.getElementById("tc"+qn).value;
		str = str.replace(/,/g,"");
		str = normalizemathunicode(str);
		var strprocess = AMnumfuncPrepVar(qn, str);
		str = strprocess[0];
		varlist = strprocess[2];

		if (iseqn[qn]==1) {
			str = str.replace(/(.*)=(.*)/,"$1-($2)");
		} else {
			if (str.match("=")) {continue;}
		}
		fl = flist[qn];

		if (fl!='') {
			reg = new RegExp("("+fl+")\\(","g");
			str = str.replace(reg,"$1*sin($1+");
		}
		vars = varlist.split("|");
		var nh = document.getElementById("qn" + qn);
		nh.value = mathjs(str,varlist);
		ptlist = pts[qn].split(",");
		vals= new Array();
		for (var fj=0; fj<ptlist.length;fj++) { //for each set of inputs
			inputs = ptlist[fj].split("~");
			totest = '';
			for (var fk=0; fk<inputs.length; fk++) {
				//totest += varlist.charAt(k) + "=" + inputs[k] + ";";
				totest += "var " + vars[fk] + "=" + inputs[fk] + ";";
			}
			if (nh.value=='') {
				totest += Math.random()+";";
			} else {
				totest += prepWithMath(nh.value)+";";
			}
			try {
				vals[fj] = scopedeval(totest);
			} catch (e) {
				vals[fj] = NaN;
			}
			if (vals[fj]=="synerr") {
				vals[fj] = NaN;
			}
		}
		document.getElementById("qn" + qn+"-vals").value = vals.join(",");
	}
	return true;
}

function scopedeval(c) {
	var res;
	try {
		res = eval(c);
		return res;
	} catch(e) {
		return "synerr";
	}
}

function arraysearch(needle,hay) {
      for (var i=0; i<hay.length;i++) {
            if (hay[i]==needle) {
                  return i;
            }
      }
      return -1;
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
//triggered by blur, this saves the one question without scoring
function assessbackgsave() {
	var m = $(this).attr("name").match(/^(qs|qn|tc)(\d+)/);
	if (m !== null && !!window.FormData) {
		var qn = m[2]*1;
		if (qn>1000) {
			qn = Math.floor(qn/1000 + .001)-1;
		}
		if (typeof tinyMCE != "undefined") {tinyMCE.triggerSave();}
		doonsubmit();
		var tosubFormData = new FormData();
		var regex = new RegExp("^(qn|tc|qs)("+qn+"\\b|"+(qn+1)+"\\d{3})");
		$("input,select,textarea").each(function(i,el) {
			if (el.name.match(regex)) {
				if ((el.type!='radio' && el.type!='checkbox') || el.checked) {
					if (el.type=='file') {
						tosubFormData.append(el.name, el.files[0]);
					} else {
						tosubFormData.append(el.name, el.value);
					}
				}
			}
		});
		tosubFormData.append("backgroundsaveforlater",1);
		tosubFormData.append("tosaveqn",qn);
		tosubFormData.append("asidverify", document.getElementById("asidverify").value);
		tosubFormData.append("disptime", document.getElementById("disptime").value);
		tosubFormData.append("isreview", document.getElementById("isreview").value);
		$.ajax({
			type:"POST", 
			url: window.location.origin+window.location.pathname, 
			data: tosubFormData, 
			contentType: false, 
			processData: false,
			qn: qn
		}).done(function(msg){
			if (assessFormIsDirty.indexOf(this.qn*1)!=-1) {
		    	    assessFormIsDirty.splice(assessFormIsDirty.indexOf(this.qn*1),1);
		    	}
			//console.log(msg);
		});
	}
}
//this submits one question for grading, in Embedded display
function assessbackgsubmit(qn,noticetgt) {
	if (!confirmSubmit($("#embedqwrapper"+qn)[0])) {
		return false;	
	}
	if (typeof tinyMCE != 'undefined') {tinyMCE.triggerSave();}
	doonsubmit();
	var params = {};
	var useFormData = !! window.FormData;
	if (useFormData) {
		var tosubFormData = new FormData();
	}

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
	if (qn != null) {
		var regex = new RegExp("^(qn|tc|qs)("+qn+"\\b|"+(qn+1)+"\\d{3})");
	} else {
		var regex = new RegExp("^(qn|tc|qs)");
	}
	for (var i=0;i<els.length;i++) {
		if (els[i].name.match(regex)) {
			if ((els[i].type!='radio' && els[i].type!='checkbox') || els[i].checked) {
				if (useFormData) {
					if (els[i].type=='file') {
						tosubFormData.append(els[i].name, els[i].files[0]);
					} else {
						tosubFormData.append(els[i].name, els[i].value);
					}
				} else {
					if (els[i].type=='file') {
						alert("File upload submissions are not supported by this browser");
					} else {
						params[els[i].name] = els[i].value;
					}
				}
			}
		}
	}
	if (qn !== null) {
		if (useFormData) {
			tosubFormData.append('toscore', qn);
			tosubFormData.append('verattempts', document.getElementById("verattempts"+qn).value);
		} else {
			params['toscore'] = qn;
			params['verattempts'] = document.getElementById("verattempts"+qn).value;
		}
	} else {
		if (useFormData) {
			tosubFormData.append('verattempts', document.getElementById("verattempts").value);
		} else {
			params['verattempts'] = document.getElementById("verattempts").value;
		}
	}
	if (useFormData) {
		tosubFormData.append('asidverify', document.getElementById("asidverify").value);
		tosubFormData.append('disptime', document.getElementById("disptime").value);
		tosubFormData.append('isreview', document.getElementById("isreview").value);
	} else {
		params['asidverify'] = document.getElementById("asidverify").value;
		params['disptime'] = document.getElementById("disptime").value;
		params['isreview'] = document.getElementById("isreview").value;
	}
	var options = {
		type: "POST",
		url: assesspostbackurl,
		data: useFormData?tosubFormData:params,
		dataType: "text",
		qn: qn,
		noticetgt: noticetgt
	};
	if (useFormData) {
		options.contentType = false;
		options.processData = false;
	}
	$.ajax(options).done(function(msg) {
		if (this.noticetgt != null) {
		    document.getElementById(this.noticetgt).innerHTML = "";
		}
		if (this.qn !== null) {
		    var qn = this.qn;
		    var scripts = new Array();         // Array which will store the script's code
		    var resptxt = msg;
		    // Strip out tags
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


		    document.getElementById("embedqwrapper"+qn).innerHTML = resptxt;
		    if (usingASCIIMath) {
			    rendermathnode( document.getElementById("embedqwrapper"+qn));
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
					scripts[i] = scripts[i] + "imathasDraw.initCanvases("+k[1]+");";
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

				var qnlen = (qn+1).toString().length;
				for (i in LivePreviews) {
					if (i==qn || (i>999 && i.toString().substr(0,qnlen)==qn+1)) {
						LivePreviews[i].Init();
					}
				}
		    /*
		    if (LivePreviews.hasOwnProperty(qn)) {
		    	 LivePreviews[qn].Init();
		    }
				*/
		    $(window).trigger("ImathasEmbedReload", [qn]);
		    if (assessFormIsDirty.indexOf(qn*1)!=-1) {
		    	    assessFormIsDirty.splice(assessFormIsDirty.indexOf(qn*1),1);
		    }
		    $("#embedqwrapper"+qn).find("input,textarea").on("change", trackDirty);
		    
		    var pagescroll = 0;
		    if(typeof window.pageYOffset!= 'undefined'){
			//most browsers
			pagescroll = window.pageYOffset;
		    }
		    else{
			var B= document.body; //IE 'quirks'
			var D= document.documentElement; //IE with doctype
			D= (D.clientHeight)? D: B;
			pagescroll = D.scrollTop;
		    }
		    var elpos = findPos(document.getElementById("embedqwrapper"+qn))[1];
		    if (pagescroll > elpos) {
		    	    setTimeout(function () {window.scroll(0,elpos);}, 150);
		    }
		}
	}).fail(function(msg) {
		if (this.noticetgt != null) {
		    document.getElementById(this.noticetgt).innerHTML = _("Submission Error");
		}
	});


}

function embedEnterHandler(el) {
	$("#"+el+" input[type=text][name^=qn]").off("keydown.embedenterhandler")
	  .on("keydown.embedenterhandler", function(e) {
		if (e.which==13) {
			e.preventDefault();
			var id = $(this).closest(".embedqwrapper").attr("id").substr(13);
			assessbackgsubmit(id, "submitnotice"+id);
		}
	});
}
$(window).on("ImathasEmbedReload", function(e, qn) {
	embedEnterHandler("embedqwrapper"+qn);	
});

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
		setTimeout(me.hideDiv,100);
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
		ul.onclick = function(ev)
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

function isBlank(str) {
	return (!str || 0 === str.length || /^\s*$/.test(str));
}

function editdebit(el) {
	var descr = $('#qn'+(el.id.substr(2)*1 - 1));
	if (!isBlank(el.value) && descr.hasClass("iscredit")) {
		if (descr.is('select')) {
			descr.css('margin-right',20);
		} else {
			descr.width(descr.width()+20);
		}
		descr.css('padding-left',0);
		descr.removeClass("iscredit");
	}
}
function editcredit(el) {
	var descr = $('#qn'+(el.id.substr(2)*1 - 2));
	if (!isBlank(el.value) && !descr.hasClass("iscredit")) {
		if (descr.is('select')) {
			descr.css('margin-right',0);
		} else {
			descr.width(descr.width()-20);
		}
		descr.css('padding-left',20);
		descr.addClass("iscredit");
	}
}
function initcreditboxes() {
	$('.creditbox').each(function(i, el) {
		if (!isBlank(el.value) && $(el).css('padding-left')!=20) {
			var descr = $('#qn'+(el.id.substr(2)*1 - 2));
			if (descr.is('select')) {
				descr.css('margin-right',0);
			} else {
				descr.width(descr.width()-20);
			}
			descr.css('padding-left',20);
			descr.addClass("iscredit");
		}
	});
}
initstack.push(initcreditboxes);
$(window).on("ImathasEmbedReload", initcreditboxes);

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
$(window).on("ImathasEmbedReload", initqsclickchange);
initstack.push(initqsclickchange);

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
}
$(window).on("ImathasEmbedReload", initShowAnswer);
initstack.push(initShowAnswer);

function assessmentTimer(duration, timelimitkickout) {
	var start = Date.now(), remaining, hours, minutes, seconds, countdowntimer, timestr;
	function updatetimer() {
		remaining = duration - Math.floor((Date.now() - start)/1000);
		if (remaining <= 0) {
			remaining = 0;
			clearInterval(countdowntimer);
			if (timelimitkickout) {
				document.getElementById('timelimitholder').className = "noticetext";
				//document.getElementById('timelimitholder').style.color = "#f00";
				document.getElementById('timelimitholder').innerHTML = _('Time limit expired - submitting now');
				document.getElementById('timelimitholder').style.fontSize="300%";
				if (document.getElementById("qform") == null) {
					setTimeout("window.location.href='"+imasroot+"/assessment/showtest.php?action=skip&superdone=true'",2000);
					return;
				} else {
					var theform = document.getElementById("qform");
					var action = theform.getAttribute("action");
					theform.setAttribute("action",action+'&superdone=true');
					if (doonsubmit(theform,true,true)) { setTimeout('document.getElementById("qform").submit()',2000);}
				}
				return 0;
			} else {
				alert(_('Time Limit has elapsed'));
			}
		} // end remaining <= 0
		seconds = Math.floor((remaining)%60);
		minutes = Math.floor((remaining/60)%60);
		hours = Math.floor(remaining/3600);
		if (hours==0 && minutes < 5) {document.getElementById("timeremaining").className="noticetext";}
		if (hours==0 && minutes==0 && seconds <= 5) {document.getElementById("timeremaining").style.fontSize="150%";}
		timestr = ((hours>0)?hours+":":"") + ((hours>0 && minutes<10)?"0":"") + minutes+":" + (seconds<10?"0":"")+seconds;
		document.getElementById("timeremaining").innerHTML = timestr;
	}
	countdowntimer = setInterval(updatetimer, 1000);
	$(document).ready(function() {
		var s = $("#timerwrap");
		var pos = s.position();
		$(window).scroll(function() {
		   var windowpos = $(window).scrollTop();
		   if (windowpos >= pos.top) {
		     s.addClass("sticky");
		   } else {
		     s.removeClass("sticky");
		   }
		 });
	});
}
function toggletimer() {
	if ($("#timerhide").text()=="[x]") {
		$("#timercontent").hide();
		$("#timerhide").text("["+_("Show Timer")+"]");
		$("#timerhide").attr("title","["+_("Show Timer")+"]");
	} else {
		$("#timercontent").show();
		$("#timerhide").text("[x]");
		$("#timerhide").attr("title",_("Hide Timer"));
	}
}
function toggleintroshow(n) {
      var link = document.getElementById("introtoggle"+n);
      var content = document.getElementById("intropiece"+n);
      if (link.innerHTML.match("Hide")) {
	   link.innerHTML = link.innerHTML.replace("Hide","Show");
	   $(link).attr("aria-expanded",false);
	   content.style.display = "none";
	   $(content).attr("aria-hidden",true).attr("aria-expanded",false);
      } else {
	   link.innerHTML = link.innerHTML.replace("Show","Hide");
	   $(link).attr("aria-expanded",true);
	   content.style.display = "block";
	   $(content).attr("aria-hidden",false).attr("aria-expanded",true);
      }
}
function togglemainintroshow(el) {
	if ($("#intro").hasClass("hidden")) {
		$(el).html(_("Hide Intro/Instructions")).attr("aria-expanded",true);
		$("#intro").removeClass("hidden").addClass("intro").attr("aria-hidden",false).attr("aria-expanded",true);
	} else {
		$("#intro").addClass("hidden").attr("aria-hidden",true).attr("aria-expanded",false);
		$(el).html(_("Show Intro/Instructions")).attr("aria-expanded",false);
	}
}

function prepWithMath(str) {
	str = str.replace(/\b(abs|acos|asin|atan|ceil|floor|cos|sin|tan|sqrt|exp|max|min|pow)\(/g, 'Math.$1(');
	str = str.replace(/\((E|PI)\)/g,'(Math.$1)');
	return str;
}

var assessFormIsDirty = [];
function trackDirty() {
	var name = $(this).attr("name");
	var m = name.match(/^(qs|qn|tc)(\d+)/);
	if (m !== null) {
		var qn = m[2]*1;
		if (qn>1000) {
			qn = Math.floor(qn/1000 + .001)-1;
		}
		if (assessFormIsDirty.indexOf(qn)==-1) {
			assessFormIsDirty.push(qn);
		}
	}
}
$(function() {
	$("div.question").find("input,select,textarea").on("change", trackDirty);
	$(window).on("beforeunload",function() {
		if ($("form.submitted,form.submitted2").length==0 && assessFormIsDirty.length>0) {
			return _('Are you sure you want to leave this assessment? You may have unsubmitted work');
		}
	});
});


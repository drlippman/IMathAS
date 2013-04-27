//IMathAS:  Handles preview buttons and pre-submit calculations for assessments
//(c) 2006 David Lippman

//handles preview button for calculated type
function calculate(inputId,outputId,format) {
  var fullstr = document.getElementById(inputId).value;
  if (format.indexOf('list')!=-1) {
	  var strarr = fullstr.split(/,/);
  } else {
	  var strarr = new Array();
	  strarr[0] = fullstr;
  }
  for (var sc=0;sc<strarr.length;sc++) {
	  str = strarr[sc];
	  str = str.replace(/,/g,"");
	  var err = "";
	  if (str.match(/DNE/i)) {
		  str = str.toUpperCase();
	  } else if (str.match(/oo$/) || str.match(/oo\W/)) {
		  str = "`"+str+"`";
	  } else {
		  if (format.indexOf('fraction')!=-1 || format.indexOf('reducedfraction')!=-1) {
			  str = str.replace(/\s/g,'');
			 // if (!str.match(/^\s*\-?\(?\d+\s*\/\s*\-?\d+\)?\s*$/) && !str.match(/^\s*?\-?\d+\s*$/)) {
			  if (!str.match(/^\(?\-?\(?\d+\)?\/\(?\d+\)?$/) && !str.match(/^\(?\d+\)?\/\(?\-?\d+\)?$/) && !str.match(/^\s*?\-?\d+\s*$/)) {
				err += _("not a valid fraction");  
			  }
		  } else if (format.indexOf('fracordec')!=-1) {
			  str = str.replace(/\s/g,'');
			  if (!str.match(/^\s*\-?\(?\d+\s*\/\s*\-?\d+\)?\s*$/) && !str.match(/^\s*?\-?\d+\s*$/) && !str.match(/^(\d+|\d+\.\d*|\d*\.\d+)$/)) {
				err += _(" invalid entry format");  
			  }
		  } else if (format.indexOf('mixednumber')!=-1) {
			  if (!str.match(/^\s*\-?\s*\d+\s*(_|\s)\s*\d+\s*\/\s*\d+\s*$/) && !str.match(/^\s*?\-?\d+\s*$/) && !str.match(/^\s*\-?\d+\s*\/\s*\-?\d+\s*$/)) {
				err += _("not a valid mixed number");
			  }
			  str = str.replace(/_/,' ');
		  } else if (format.indexOf('scinot')!=-1) {
			  str = str.replace(/\s/g,'');
			  str = str.replace("x","xx");
			  if (!str.match(/^\-?[1-9](\.\d*)?(\*|xx)10\^(\(?\-?\d+\)?)$/)) {
				err += _("not valid scientific notation");  
			  }
		  } 
		  if (format.indexOf('notrig')!=-1 && str.match(/(sin|cos|tan|cot|sec|csc)/)) {
			  str = _("no trig functions allowed");
		  } else if (format.indexOf('nodecimal')!=-1 && str.indexOf('.')!=-1) {
			  str = _("no decimals allowed");
		  } else {
			  try {
				  var evalstr = str;
				  if (format.indexOf('allowmixed')!=-1 || format.indexOf('mixednumber')!=-1) {
					  evalstr = evalstr.replace(/(\d+)\s+(\d+\s*\/\s*\d+)/,"($1+$2)");
				  }
				  if (format.indexOf('scinot')!=-1) {
					   evalstr = evalstr.replace("xx","*");
				  }
			          with (Math) var res = eval(mathjs(evalstr));
			  } catch(e) {
			    err = _("syntax incomplete");
			  }
			  if (!isNaN(res) && res!="Infinity") {
				  if (format.indexOf('fraction')!=-1 || format.indexOf('reducedfraction')!=-1 || format.indexOf('mixednumber')!=-1 || format.indexOf('scinot')!=-1 || format.indexOf('noval')!=-1) {
					  str = "`"+str+"` " + err;
				  } else {
					  str = "`"+str+" =` "+(Math.abs(res)<1e-15?0:res)+err;
				  }
			  } else if (str!="") {
				  var Pdepth = 0; var Bdepth = 0;
				  for (var i=0; i<str.length; i++) {
					if (str.charAt(i)=='(') {
						Pdepth++;
					} else if (str.charAt(i)==')') {
						Pdepth--;
					} else if (str.charAt(i)=='[') {
						Bdepth++;
					} else if (str.charAt(i)==']') {
						Bdepth--;
					}
				  }
				  str = "`"+str+"` = "+_("undefined");
				  if (Pdepth!=0 || Bdepth!=0) {
					  str += " ("+_("unmatched parens")+")";
				  }
				  trg = str.match(/(sin|cos|tan|sec|csc|cot)\^/);
				  reg = new RegExp("(sqrt|ln|log|sin|cos|tan|sec|csc|cot|abs)([^\(])");
				  errstuff = str.match(reg)
				  if (trg!=null) {
					  trg = trg[1];
					  //str += "["+_("use")+" ("+trg+"(x))^2 "+_("instead of ")+trg+_("^2(x)]");
					  str += "["+_("use $1 instead of $2","("+trg+"(x))^2", trg+"^2(x)")+"]";
					//  "["+_("use")+" ("+trg+"(x))^2 "+_("instead of ")+trg+_("^2(x)]");
				  } else if (errstuff!=null) {  
					  str += "["+_("use function notation")+" - "+_("use $1 instead of $2",errstuff[1]+"("+errstuff[2]+")",errstuff[0])+"]";
				  }
			  }
		  }
	  }
	  strarr[sc] = str+" ";
  }
  fullstr = strarr.join(', ');
  var outnode = document.getElementById(outputId);
  var n = outnode.childNodes.length;
  for (var i=0; i<n; i++)
    outnode.removeChild(outnode.firstChild);
  outnode.appendChild(document.createTextNode(fullstr));
  if (!noMathRender) {
	  AMprocessNode(outnode);
  }
}

//not used yet.  Function to convert inequalities into interval notation
function ineqtointerval(strw) {
	var strpts = strw.split(/or/);
	for (i=0; i<strpts.length; i++) {
		str = strpts[i];
		var out = '';	
		if (pat = str.match(/^([^<]+)\s*(<=?)\s*[a-zA-Z]\s*(<=?)([^<]+)$/)) {
			if (pat[2]=='<=') {out += '[';} else {out += '(';}
			out += pat[1] + ',' + pat[4];
			if (pat[3]=='<=') {out += ']';} else {out += ')';}
		} else if (pat = str.match(/^([^>]+)\s*(>=?)\s*[a-zA-Z]\s*(>=?)([^>]+)$/)) {
			if (pat[3]=='>=') {out += '[';} else {out += '(';}
			out += pat[4] + ',' + pat[1];
			if (pat[2]=='>=') {out += ']';} else {out += ')';}
		} else if (pat = str.match(/^([^><]+)\s*([><]=?)\s*[a-zA-Z]\s*$/)) {
			if (pat[2]=='>') { out = '(-oo,'+pat[1]+')';} else
			if (pat[2]=='>=') { out = '(-oo,'+pat[1]+']';} else
			if (pat[2]=='<') { out = '('+pat[1]+',oo)';} else
			if (pat[2]=='<=') { out = '['+pat[1]+',oo)';}
		} else if (pat = str.match(/^\s*[a-zA-Z]\s*([><]=?)\s*([^><]+)$/)) {
			if (pat[1]=='<') { out = '(-oo,'+pat[2]+')';} else
			if (pat[1]=='<=') { out = '(-oo,'+pat[2]+']';} else
			if (pat[1]=='>') { out = '('+pat[2]+',oo)';} else
			if (pat[1]=='>=') { out = '['+pat[2]+',oo)';}
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
		var pats = str.match(/\b([a-zA-Z])\b/);
		var pat = str.match(/([a-zA-Z]+)/);
		var ineqvar = (pats != null)?pats[1]:((pat != null)?pat[1]:'');
	  } else {
		  fullstr = fullstr.replace(/\s+/g,'');
	  }
	  var strarr = fullstr.split(/U/);
	  var isok = true;
	  for (i=0; i<strarr.length; i++) {
		  str = strarr[i];
		  sm = str.charAt(0);
		  em = str.charAt(str.length-1);
		  vals = str.substring(1,str.length-1);
		  vals = vals.split(/,/);
		  if (vals.length != 2) {
			  fullstr = _("syntax incomplete");
			  isok = false;
			  break;
		  }
		  for (j=0; j<2; j++) {	
			  if (vals[j].match(/oo$/) || vals[j].match(/oo\W/)) {
				  calcvals[j] = vals[j];
			  } else {
				  var err = "";
				  
				  try {
				    with (Math) var res = eval(mathjs(vals[j]));
				  } catch(e) {
				    err = _("syntax incomplete");
				  }
				  if (!isNaN(res) && res!="Infinity") {
					 // if (format.indexOf('fraction')!=-1 || format.indexOf('reducedfraction')!=-1 || format.indexOf('mixednumber')!=-1) {
						  vals[j] = vals[j];
						  calcvals[j] = (Math.abs(res)<1e-15?0:res)+err;
					  //} else {
						//  str = "`"+str+" =` "+(Math.abs(res)<1e-15?0:res)+err;
					  //}
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
				 if (format.indexOf('noval')!=-1) {
				 	 fullstr = '`'+origstr + '`';
				 } else {
				 	 fullstr = '`'+origstr + '= ' + calcstrarr.join(' \\ "or" \\ ')+'`';
				 }
			 }
		 } else {
		 	 if (format.indexOf('noval')!=-1) {
				 fullstr = '`'+strarr.join('uu') + '`';	 
			 } else {
			 	 fullstr = '`'+strarr.join('uu') + '` = ' + calcstrarr.join(' U ');
			 }
		 }
	 }
  }
  
  
  var outnode = document.getElementById(outputId);
  var n = outnode.childNodes.length;
  for (var i=0; i<n; i++)
    outnode.removeChild(outnode.firstChild);
  outnode.appendChild(document.createTextNode(fullstr));
  if (!noMathRender) {
	  AMprocessNode(outnode);
  }
	
}

//preview for calcntuple
function ntuplecalc(inputId,outputId) {
	var fullstr = document.getElementById(inputId).value;
	fullstr = fullstr.replace(/\s+/g,'');
	if (fullstr.match(/DNE/i)) {
		fullstr = fullstr.toUpperCase();
		outcalced = 'DNE';
		outstr = 'DNE';
	} else {
		var outcalced = '';
		var NCdepth = 0;
		var lastcut = 0;
		for (var i=0; i<fullstr.length; i++) {
			dec = false;
			if (NCdepth==0) {
				outcalced += fullstr.charAt(i);
				lastcut = i+1;
			} 
			if (fullstr.charAt(i).match(/[\(\[\<\{]/)) {
				NCdepth++;		
			} else if (fullstr.charAt(i).match(/[\)\]\>\}]/)) {
				NCdepth--;	
				dec = true;
			}	
			
			if ((NCdepth==0 && dec) || (NCdepth==1 && fullstr.charAt(i)==',')) {
				sub = fullstr.substring(lastcut,i);
				var err = "";
				try {
				    with (Math) var res = eval(mathjs(sub));
				} catch(e) {
				    err = _("syntax incomplete");
				}
				if (!isNaN(res) && res!="Infinity") {
					outcalced += res;
				} else {
					outcalced += err;
				}
				outcalced += fullstr.charAt(i);
				lastcut = i+1;
			}
		}
		outstr = '`'+fullstr+'` = '+outcalced;
	}
	if (outputId != null) {
		 var outnode = document.getElementById(outputId);
		 var n = outnode.childNodes.length;
		 for (var i=0; i<n; i++) {
		    outnode.removeChild(outnode.firstChild);
		 }
		 outnode.appendChild(document.createTextNode(outstr));
		 if (!noMathRender) {
			  AMprocessNode(outnode);
		 }
	}
	 return outcalced;
}

//preview for calccomplex
function complexcalc(inputId,outputId) {
	var fullstr = document.getElementById(inputId).value;
	fullstr = fullstr.replace(/\s+/g,'');
	if (fullstr.match(/DNE/i)) {
		fullstr = fullstr.toUpperCase();
		outcalced = 'DNE';
		outstr = 'DNE';
	} else {
		var outcalced = '';
		var arr = fullstr.split(',');
		for (var cnt=0; cnt<arr.length; cnt++) {
			var prep = mathjs(arr[cnt],'i');
			var err='';
			try {
			    with (Math) var real = scopedeval('var i=0;'+prep);
			    with (Math) var imag = scopedeval('var i=1;'+prep);
			} catch(e) {
			    err = _("syntax incomplete");
			}
			if (!isNaN(real) && real!="Infinity" && !isNaN(imag) && imag!="Infinity") {
				imag -= real;
				if (cnt!=0) {
					outcalced += ',';
				}
				outcalced += real+(imag>=0?'+':'')+imag+'i';
			} else {
				outcalced = err;
				break;
			}
		}
		outstr = '`'+fullstr+'` = '+outcalced;
	}
	if (outputId != null) {
		 var outnode = document.getElementById(outputId);
		 var n = outnode.childNodes.length;
		 for (var i=0; i<n; i++) {
		    outnode.removeChild(outnode.firstChild);
		 }
		 outnode.appendChild(document.createTextNode(outstr));
		 if (!noMathRender) {
			  AMprocessNode(outnode);
		 }
	}
	 return outcalced;
}

function matrixcalc(inputId,outputId,rows,cols) {
	
	function calced(estr) {
		var err='';
		try {
			with (Math) var res = eval(mathjs(estr));
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
				str += document.getElementById(inputId+'-'+count).value;
				calcstr += calced(document.getElementById(inputId+'-'+count).value);
				count++;
			}
			str += ")";
			calcstr += ")";
		}
		str += "]";
		calcstr += "]";
	} else {
		var str = document.getElementById(inputId).value;
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
			AMprocessNode(outnode);
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
	var outnode = document.getElementById(outputId);
	var n = outnode.childNodes.length;
	for (var i=0; i<n; i++)
		outnode.removeChild(outnode.firstChild);
	outnode.appendChild(document.createTextNode('`'+str+'`'));
	if (!noMathRender) {
		AMprocessNode(outnode);
	}
}

//preview button for numfunc type
function AMpreview(inputId,outputId) {
  var qn = inputId.slice(2);
  var vl = vlist[qn];
  var fl = flist[qn];
  vars = vl.split("|");
  
  var str = document.getElementById(inputId).value;
  str = str.replace(/,/g,"");
   var dispstr = str;
   
  for (var i=0; i<vars.length; i++) {
  	  if (vars[i].charCodeAt(0)>96) { //lowercase
		  if (arraysearch(vars[i].toUpperCase(),vars)==-1) {
			//vars[i] = vars[i].toLowerCase();
			str = str.replace(new RegExp(vars[i],"gi"),vars[i]);	  
		  }
	  } else {
	  	  if (arraysearch(vars[i].toLowerCase(),vars)==-1) {
		   	//vars[i] = vars[i].toLowerCase();
		   	str = str.replace(new RegExp(vars[i],"gi"),vars[i]);	  
		  }
	  }
  }
  vl = vars.join("|");
 
  //quote out multiletter variables
  var varstoquote = new Array();
  for (var i=0; i<vars.length; i++) {
	  if (vars[i].length>1) {
		  var isgreek = false;
		  for (var j=0; j<greekletters.length;j++) {
			  if (vars[i]==greekletters[j]) {
				isgreek = true; 
				break;
			  }
		  }
		  if (!isgreek && !vars[i].match(/^(\w)_\d+$/)) {
			  varstoquote.push(vars[i]);
		  }
	  }
  }
  if (varstoquote.length>0) {
	  vltq = varstoquote.join("|");
	  var reg = new RegExp("("+vltq+")","g");
	  dispstr = str.replace(reg,"\"$1\"");
  }
  
  var outnode = document.getElementById(outputId);
  var n = outnode.childNodes.length;
  for (var i=0; i<n; i++)
    outnode.removeChild(outnode.firstChild);
    outnode.appendChild(document.createTextNode('`'+dispstr+'`'));
    if (!noMathRender) {
	AMprocessNode(outnode);
    }
  //outnode.appendChild(AMparseMath(dispstr));
  
  //the following does a quick syntax check of the formula
  
  
  ptlist = pts[qn].split(",");
  var tstpt = 0; var res = NaN; var isnoteqn = false;
  if (iseqn[qn]==1) {
  	  if (!str.match(/=/)) {isnoteqn = true;}
	str = str.replace(/(.*)=(.*)/,"$1-($2)");
  }
  if (fl!='') {
	  reg = new RegExp("("+fl+")\\(","g");
	  str = str.replace(reg,"$1*sin($1+");
	  vl = vl+'|'+fl;
  }
  vars = vl.split('|');
  var totesteqn = mathjs(str,vl); 
  	  
  while (tstpt<ptlist.length && (isNaN(res) || res=="Infinity")) {
	  var totest = '';
	  testvals = ptlist[tstpt].split("~");
	  for (var j=0; j<vars.length; j++) {
		totest += "var " + vars[j] + "="+testvals[j]+";"; 
	  }
	  totest += totesteqn;
	  var err=_("syntax ok");
	  try {
	    with (Math) var res = scopedeval(totest);
	  } catch(e) {
	    err = _("syntax error");
	  }
	  tstpt++;
  }

  if (isNaN(res) || res=="Infinity") {
	  trg = str.match(/(sin|cos|tan|sec|csc|cot)\^/);
	  reg = new RegExp("(sqrt|ln|log|sinh|cosh|tanh|sech|csch|coth|sin|cos|tan|sec|csc|cot|abs)("+vl+"|\\d)");
	  errstuff = str.match(reg)
	  if (trg!=null) {
		  trg = trg[1];
		  //err = _("syntax error")+": "+_("use")+" ("+trg+"(x))^2 "+_("instead of ")+trg+"^2(x)";
		  err += _("syntax error")+": "+_("use $1 instead of $2","("+trg+"(x))^2", trg+"^2(x)");
	  } else if (errstuff!=null) {  
		  err += ": "+_("use $1 instead of $2", errstuff[1]+"("+errstuff[2]+")", errstuff[0]);
	  } else {
		  var Pdepth = 0; var Bdepth = 0;
		  for (var i=0; i<str.length; i++) {
			if (str.charAt(i)=='(') {
				Pdepth++;
			} else if (str.charAt(i)==')') {
				Pdepth--;
			} else if (str.charAt(i)=='[') {
				Bdepth++;
			} else if (str.charAt(i)==']') {
				Bdepth--;
			}
		  }
		  if (Pdepth!=0 || Bdepth!=0) {
			  err += ": "+_("unmatched parens");
		  } else {
			 //catch (cos)(x)
			 reg = new RegExp("(sqrt|ln|log|sin|cos|tan|sec|csc|cot|abs)([^\(])");
			 errstuff = str.match(reg);
			 
			if (errstuff!=null && errstuff[2]!='h') {
				err =_("syntax error")+": "+_("use function notation")+" - "+errstuff[1]+"(x)";
			} else {
				err = _("syntax error");
			}
		  }
		  //err = "syntax error";
	  }
  } else {
	reg = new RegExp("(sqrt|ln|log|sinh|cosh|tanh|sech|csch|tanh|sin|cos|tan|sec|csc|cot|abs)\\s*("+vl+")");
	errstuff = str.match(reg);
	if (errstuff!=null) {
		err += ". "+_("warning")+": "+_("use $1 instead of $2",errstuff[1]+"("+errstuff[2]+")",errstuff[0])
	}
  }
  if (iseqn[qn]==1 && isnoteqn) { err = _("syntax error: this is not an equation");}
  outnode.appendChild(document.createTextNode(" " + err));
  //clear out variables that have been defined - not needed with scopedeval
  /*var toclear = ''; 
  for (var j=0; j<vl.length; j++) {
	toclear += vars[j] + "=NaN;"; 
  }
  eval(toclear);
  */
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
	AMprocessNode(outnode);
    }
 
}

var greekletters = ['alpha','beta','delta','epsilon','gamma','phi','psi','sigma','rho','theta','lambda','mu','nu'];
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
			if (type2) {
				var reallysubmit = confirmSubmit2(form);
			} else {
				var reallysubmit = confirmSubmit(form);
			}
			if (!reallysubmit) {
				form.className = '';
				return false;
			}
		}
	}
	for (var qn in callbackstack) {
		callbackstack[qn](qn);
	}
	for (var qn in intcalctoproc) { //i=0; i<intcalctoproc.length; i++) {
		qn = parseInt(qn);
		
		fullstr = document.getElementById("tc"+qn).value;
		fullstr = fullstr.replace(/\s+/g,'');
		
		if (fullstr.match(/DNE/i)) {
			  fullstr = fullstr.toUpperCase();
		  } else {
			  if (calcformat[qn].indexOf('inequality')!=-1) {
				  fullstr = ineqtointerval(fullstr);
			  }
			  strarr = fullstr.split(/U/);
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
							    with (Math) var res = eval(mathjs(vals[j]));
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
			 fullstr = strarr.join('U');
		  }
		  document.getElementById("qn" + qn).value = fullstr;
	}
	for (var qn in calctoproc) { //i=0; i<calctoproc.length; i++) {
		qn = parseInt(qn);
		
		str = document.getElementById("tc"+qn).value;
		if (calcformat[qn].indexOf('list')!=-1) {
			strarr = str.split(/,/);
		} else {
			var strarr = new Array();
			strarr[0] = str;
		}
		for (var sc=0;sc<strarr.length;sc++) {
			str = strarr[sc];
			
			str = str.replace(/,/g,"");
			if (calcformat[qn].indexOf('scinot')!=-1) {
				str = str.replace("x","*");
			}
			str = str.replace(/(\d+)\s*_\s*(\d+\s*\/\s*\d+)/,"($1+$2)");
			if (calcformat[qn].indexOf('mixednumber')!=-1 || calcformat[qn].indexOf('allowmixed')!=-1) {
				str = str.replace(/(\d+)\s+(\d+\s*\/\s*\d+)/,"($1+$2)");
			}
			if (str.match(/^\s*$/)) {
				var res = '';
			} else if (str.match(/oo$/) || str.match(/oo\W/)) {
				var res = str;
			} else if (str.match(/DNE/i)) {
				var res = str.toUpperCase();
			} else {
				try {
					with (Math) var res = eval(mathjs(str));
				} catch(e) {
					err = _("syntax incomplete");
				}
			}
			strarr[sc] = res;
		}
		document.getElementById("qn" + qn).value = strarr.join(',');
	}
	for (var qn in matcalctoproc) {//i=0; i<matcalctoproc.length; i++) {
		qn = parseInt(qn);
		if (matsize[qn]!= null) {
			msize = matsize[qn].split(",");
			str = matrixcalc("qn"+qn,null,msize[0],msize[1]);
		} else {
			str = matrixcalc("tc"+qn,null);
		}
		document.getElementById("qn" +  qn).value = str;
	}
	for (var qn in ntupletoproc) {//i=0; i<ntupletoproc.length; i++) {
		qn = parseInt(qn);
		str = ntuplecalc("tc"+qn,null);
		document.getElementById("qn" + qn).value = str;
	}
	for (var qn in complextoproc) { //i=0; i<complextoproc.length; i++) {
		qn = parseInt(qn);
		str = complexcalc("tc"+qn,null);
		document.getElementById("qn" + qn).value = str;
	}
	for (var qn in functoproc) { //fcnt=0; fcnt<functoproc.length; fcnt++) {
		qn = parseInt(qn);
		str = document.getElementById("tc"+qn).value;
		str = str.replace(/,/g,"");
		if (iseqn[qn]==1) {
			str = str.replace(/(.*)=(.*)/,"$1-($2)");
		}
		fl = flist[qn];
		varlist = vlist[qn];
		
		vars = varlist.split("|");
		for (var j=0; j<vars.length; j++) {
			  if (vars[j].charCodeAt(0)>96) { //lowercase
				  if (arraysearch(vars[j].toUpperCase(),vars)==-1) {
					  vars[j] = vars[j].toLowerCase();
					  str = str.replace(new RegExp(vars[j],"gi"),vars[j]);	  
				  }
			  } else {
				  if (arraysearch(vars[j].toLowerCase(),vars)==-1) {
					vars[j] = vars[j].toLowerCase();
					str = str.replace(new RegExp(vars[j],"gi"),vars[j]);	  
				  }
			  }
		  }
		varlist = vars.join("|");
		
		if (fl!='') {
			reg = new RegExp("("+fl+")\\(","g");
			str = str.replace(reg,"$1*sin($1+");
			varlist = varlist+'|'+fl;
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
				totest += nh.value+";";
			}
			try {
				with (Math) vals[fj] = scopedeval(totest);
			} catch (e) {
				vals[fj] = NaN;
			}	
		}
		document.getElementById("qn" + qn+"-vals").value = vals.join(",");
	}
	return true;
}

function scopedeval(c) {
	var res;
	with (Math) res = eval(c); 
	return res;
}

function arraysearch(needle,hay) {
      for (var i=0; i<hay.length;i++) {
            if (hay[i]==needle) {
                  return i;
            }
      }
      return -1;
   }
   
function toggleinlinebtn(n,p){
	var el=document.getElementById(n);
	el.style.display=="none"?el.style.display="":el.style.display="none";
	if (p!=null) {
		var s=document.getElementById(p);
		var k=s.innerHTML;
		s.innerHTML = k.match(/\+/)?k.replace(/\+/,'-'):k.replace(/\-/,'+');
	}
}

function assessbackgsubmit(qn,noticetgt) {
	if (noticetgt != null && document.getElementById(noticetgt).innerHTML == _("Submitting...")) {return false;}
	if (window.XMLHttpRequest) { 
		req = new XMLHttpRequest();
	} else if (window.ActiveXObject) { 
		req = new ActiveXObject("Microsoft.XMLHTTP");
	} 
	if (typeof req != 'undefined') { 
		if (typeof tinyMCE != 'undefined') {tinyMCE.triggerSave();}
		doonsubmit();
		params = "embedpostback=true";
		if (qn != null) {
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
						params += ('&'+els[i].name+'='+encodeURIComponent(els[i].value));
					}
				}
			}
			params += '&toscore='+qn;
			params += '&verattempts='+document.getElementById("verattempts"+qn).value;
		} else {
			var els = document.getElementsByTagName("input");
			for (var i=0;i<els.length;i++) {
				if (els[i].name.match(/^(qn|tc)/)) {
					if (els[i].type!='radio' || els[i].type!='checkbox' || els[i].checked) {
						params += ('&'+els[i].name+'='+encodeURIComponent(els[i].value));
					}
				}
			}
			params += '&verattempts='+document.getElementById("verattempts").value;
		}
		params += '&asidverify='+document.getElementById("asidverify").value;
		params += '&disptime='+document.getElementById("disptime").value;
		params += '&isreview='+document.getElementById("isreview").value;
		
		if (noticetgt != null) {
			document.getElementById(noticetgt).innerHTML = _("Submitting...");
		}
		req.open("POST", assesspostbackurl, true);
		req.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		req.setRequestHeader("Content-length", params.length);
		req.setRequestHeader("Connection", "close");
		req.onreadystatechange = function() {assessbackgsubmitCallback(qn,noticetgt);}; 
		req.send(params);  
	} else {
		if (noticetgt != null) {
			document.getElementById(noticetgt).innerHTML = _("Error Submitting.");
		}
	}
}  

function assessbackgsubmitCallback(qn,noticetgt) { 
  if (req.readyState == 4) { // only if req is "loaded" 
    if (req.status == 200) { // only if "OK" 
	    if (noticetgt != null) {
		    document.getElementById(noticetgt).innerHTML = "";
	    }
	    if (qn != null) {
		    var scripts = new Array();         // Array which will store the script's code
		    var resptxt = req.responseText;
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
			    AMprocessNode( document.getElementById("embedqwrapper"+qn));
		    }
		    if (usingASCIISvg) {
			    setTimeout("drawPics()",100);
		    }
		    if (usingTinymceEditor) {
			    initeditor("textareas","mceEditor");
		    }
		    // Loop through every script collected and eval it
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
	    //var todo = eval('('+req.responseText+')');
	   
    } else { 
	    if (noticetgt != null) {
		    document.getElementById(noticetgt).innerHTML = _("Submission Error")+":\n"+ req.status + "\n" +req.statusText; 
	    }
    }
  } 
}	
	

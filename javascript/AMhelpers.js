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
	  } else if (str.match(/oo/)) {
		  str = "`"+str+"`";
	  } else {
		  if (format.indexOf('mixednumber')!=-1) {
			  str = str.replace(/_/,' ');
		  }
		  if (format.indexOf('notrig')!=-1 && str.match(/(sin|cos|tan|cot|sec|csc)/)) {
			  str = "no trig functions allowed";
		  } else if (format.indexOf('nodecimal')!=-1 && str.indexOf('.')!=-1) {
			  str = "no decimals allowed";
		  } else {
			  try {
			    with (Math) var res = eval(mathjs(str));
			  } catch(e) {
			    err = "syntax incomplete";
			  }
			  if (!isNaN(res) && res!="Infinity") {
				  if (format.indexOf('fraction')!=-1 || format.indexOf('reducedfraction')!=-1 || format.indexOf('mixednumber')!=-1) {
					  str = "`"+str+"`";
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
				  str = "`"+str+"` = undefined";
				  if (Pdepth!=0 || Bdepth!=0) {
					  str += " (unmatched parens)";
				  }
			  }
		  }
	  }
	  strarr[sc] = str;
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

//preview for calcinterval type - NOT ANYWHERE NEAR DONE - need to do onsubmit too
function intcalculate(inputId,outputId) {
  var fullstr = document.getElementById(inputId).value;
  fullstr = fullstr.replace(/\s+/g,'');
  if (fullstr.match(/DNE/i)) {
	  fullstr = fullstr.toUpperCase();
  } else {
	  var calcvals = new Array();
	  var calcstrarr = new Array();
	  strarr = fullstr.split(/U/);
	  for (i=0; i<strarr.length; i++) {
		  str = strarr[i];
		  sm = str.charAt(0);
		  em = str.charAt(str.length-1);
		  vals = str.substring(1,str.length-1);
		  vals = vals.split(/,/);
		  for (j=0; j<2; j++) {	
			  if (vals[j].match(/oo/)) {
				  calcvals[j] = vals[j];
			  } else {
				  var err = "";
				  
				  try {
				    with (Math) var res = eval(mathjs(vals[j]));
				  } catch(e) {
				    err = "syntax incomplete";
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
		  calcstrarr[i] = sm + calcvals[0] + ',' + calcvals[1] + em;
	 }
  }
  fullstr = '`'+strarr.join('uu') + '` = ' + calcstrarr.join(' U ');
  
  var outnode = document.getElementById(outputId);
  var n = outnode.childNodes.length;
  for (var i=0; i<n; i++)
    outnode.removeChild(outnode.firstChild);
  outnode.appendChild(document.createTextNode(fullstr));
  if (!noMathRender) {
	  AMprocessNode(outnode);
  }
	
}

function matrixcalc(inputId,outputId,rows,cols) {
	
	function calced(estr) {
		err='';
		try {
			with (Math) var res = eval(mathjs(estr));
		} catch(e) {
			err = "syntax incomplete";
		}
		if (!isNaN(res) && res!="Infinity") 
			estr = (Math.abs(res)<1e-15?0:res)+err; 
		else if (estr!="") estr = "undefined";
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

//preview button for numfunc type
function AMpreview(inputId,outputId) {
  var qn = inputId.slice(2);
  var vl = vlist[qn];
  vars = vl.split("|");
  
  var str = document.getElementById(inputId).value;
  var dispstr = str;
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
		  if (!isgreek) {
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
  var tstpt = 0; res = NaN;
  if (iseqn[qn]==1) {
	str = str.replace(/(.*)=(.*)/,"$1-($2)");
  }
  var totesteqn = mathjs(str,vl);
  while (tstpt<ptlist.length && (isNaN(res) || res=="Infinity")) {
	  var totest = '';
	  testvals = ptlist[tstpt].split("~");
	  for (var j=0; j<vl.length; j++) {
		totest += vars[j] + "="+testvals[j]+";"; 
	  }
	  totest += totesteqn;
	 
	  var err="syntax ok";
	  try {
	    with (Math) var res = eval(totest);
	  } catch(e) {
	    err = "syntax error";
	  }
	  tstpt++;
  }

  if (isNaN(res) || res=="Infinity") {
	  trg = str.match(/(sin|cos|tan|sec|csc|cot)\^/);
	  reg = new RegExp("(sqrt|ln|log|sin|cos|tan|sec|csc|cot|abs)("+vl+"|\\d)");
	  errstuff = str.match(reg)
	  if (trg!=null) {
		  trg = trg[1];
		  err = "syntax error: use ("+trg+"(x))^2 instead of "+trg+"^2(x)";
	  } else if (errstuff!=null) {  
		  err += ": use "+errstuff[1]+"("+errstuff[2]+"), not "+errstuff[0];
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
			  err += ": unmatched parens";
		  } else {
			 //catch (cos)(x)
			reg = new RegExp("(sqrt|ln|log|sin|cos|tan|sec|csc|cot|abs)[^\(]");
			errstuff = str.match(reg);
			if (errstuff!=null) {
				err = "syntax error: use function notation - "+errstuff[1]+"(x)";
			} else {
				err = "syntax error";
			}
		  }
		  //err = "syntax error";
	  }
  } else {
	  
	reg = new RegExp("(sqrt|ln|log|sin|cos|tan|sec|csc|cot|abs)\\s*("+vl+")");
	errstuff = str.match(reg);
	if (errstuff!=null) {
		err += ": warning: use "+errstuff[1]+"("+errstuff[2]+") rather than "+errstuff[0];
	}
  }
  outnode.appendChild(document.createTextNode(" " + err));
  //clear out variables that have been defined
  var toclear = ''; 
  for (var j=0; j<vl.length; j++) {
	toclear += vars[j] + "=NaN;"; 
  }
  eval(toclear);
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

var greekletters = ['alpha','beta','delta','gamma','phi','psi','sigma','rho','theta'];
var calctoproc = new Array();
var intcalctoproc = new Array();
var calcformat = new Array();
var functoproc = new Array();
var matcalctoproc = new Array();
var matsize = new Array();
var vlist = new Array();
var pts = new Array();
var iseqn = new Array();

function doonsubmit(form,type2,skipconfirm) {
	if (form!=null) {
		if (!skipconfirm) {
			if (type2) {
				var reallysubmit = confirmSubmit2(form);
			} else {
				var reallysubmit = confirmSubmit(form);
			}
			if (!reallysubmit) {
				return false;
			}
		}
	}
	for (var i=0; i<intcalctoproc.length; i++) {
		var nh = document.createElement("INPUT");
		nh.type = "hidden";
		nh.name = "qn" + intcalctoproc[i];
		fullstr = document.getElementById("tc"+intcalctoproc[i]).value;
		fullstr = fullstr.replace(/\s+/g,'');
		if (fullstr.match(/DNE/i)) {
			  fullstr = fullstr.toUpperCase();
		  } else {
			  strarr = fullstr.split(/U/);
			  for (k=0; k<strarr.length; k++) {
				  str = strarr[k];
				  if (str.length>0 && str.match(/,/)) {
					  sm = str.charAt(0);
					  em = str.charAt(str.length-1);
					  vals = str.substring(1,str.length-1);
					  vals = vals.split(/,/);
					  for (j=0; j<2; j++) {	  
						  if (!vals[j].match(/oo/)) {
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
		  }
		  nh.value = strarr.join('U');
		  outn = document.getElementById("p"+intcalctoproc[i]);
		  outn.appendChild(nh);
	}
	for (var i=0; i<calctoproc.length; i++) {
		var nh = document.createElement("INPUT");
		nh.type = "hidden";
		nh.name = "qn" + calctoproc[i];
		str = document.getElementById("tc"+calctoproc[i]).value;
		if (calcformat[calctoproc[i]].indexOf('list')!=-1) {
			strarr = str.split(/,/);
		} else {
			var strarr = new Array();
			strarr[0] = str;
		}
		for (var sc=0;sc<strarr.length;sc++) {
			str = strarr[sc];
			
			str = str.replace(/,/g,"");
			str = str.replace(/(\d+)\s*_\s*(\d+\s*\/\s*\d+)/,"($1+$2)");
			if (str.match(/^\s*$/)) {
				var res = '';
			} else if (str.match(/oo/)) {
				var res = str;
			} else if (str.match(/DNE/i)) {
				var res = str.toUpperCase();
			} else {
				try {
					with (Math) var res = eval(mathjs(str));
				} catch(e) {
					err = "syntax incomplete";
				}
			}
			strarr[sc] = res;
		}
		nh.value = strarr.join(',');
		outn = document.getElementById("p"+calctoproc[i]);
		outn.appendChild(nh);
	}
	for (var i=0; i<matcalctoproc.length; i++) {
		var nh = document.createElement("INPUT");
		nh.type = "hidden";
		nh.name = "qn" + matcalctoproc[i];
		if (matsize[matcalctoproc[i]]!= null) {
			msize = matsize[matcalctoproc[i]].split(",");
			str = matrixcalc("qn"+matcalctoproc[i],null,msize[0],msize[1]);
		} else {
			str = matrixcalc("tc"+matcalctoproc[i],null);
		}
		nh.value = str;
		outn = document.getElementById("p"+matcalctoproc[i]);
		outn.appendChild(nh);
	}
	for (var i=0; i<functoproc.length; i++) {
		var nh = document.createElement("INPUT");
		nh.type = "hidden";
		nh.name = "qn" + functoproc[i];
		str = document.getElementById("tc"+functoproc[i]).value;
		if (iseqn[functoproc[i]]==1) {
			str = str.replace(/(.*)=(.*)/,"$1-($2)");
		}
		varlist = vlist[functoproc[i]];
		vars = varlist.split("|");
		nh.value = mathjs(str,varlist);

		outn = document.getElementById("p"+functoproc[i]);
		outn.appendChild(nh);
		
		ptlist = pts[functoproc[i]].split(",");
		vals= new Array();
		for (var j=0; j<ptlist.length;j++) { //for each set of inputs
			inputs = ptlist[j].split("~");
			totest = '';
			for (var k=0; k<inputs.length; k++) {
				//totest += varlist.charAt(k) + "=" + inputs[k] + ";";
				totest += vars[k] + "=" + inputs[k] + ";";
			}
			totest += nh.value;
			try {
				with (Math) vals[j] = eval(totest);
			} catch (e) {
				vals[j] = NaN;
			}	
		}
		var nh2 = document.createElement("input");
		nh2.type = "hidden";
		nh2.name = "qn" + functoproc[i] + "-vals";
		nh2.value = vals.join(",");
		outn.appendChild(nh2);
		
	}
	return true;
}

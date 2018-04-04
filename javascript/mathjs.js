var pi = Math.PI, ln = Math.log, e = Math.E;
var arcsin = Math.asin, arccos = Math.acos, arctan = Math.atan;
var sec = function(x) { return 1/Math.cos(x) };
var csc = function(x) { return 1/Math.sin(x) };
var cot = function(x) { return 1/Math.tan(x) };

var arcsec = function(x) { return arccos(1/x) };
var arccsc = function(x) { return arcsin(1/x) };
var arccot = function(x) { return arctan(1/x) };
var sinh = function(x) { return (Math.exp(x)-Math.exp(-x))/2 };
var cosh = function(x) { return (Math.exp(x)+Math.exp(-x))/2 };
var tanh =
  function(x) { return (Math.exp(x)-Math.exp(-x))/(Math.exp(x)+Math.exp(-x)) };
var sech = function(x) { return 1/cosh(x) };
var csch = function(x) { return 1/sinh(x) };
var coth = function(x) { return 1/tanh(x) };

var arcsinh = function(x) { return ln(x+Math.sqrt(x*x+1)) };
var arccosh = function(x) { return ln(x+Math.sqrt(x*x-1)) };
var arctanh = function(x) { return ln((1+x)/(1-x))/2 };
var sech = function(x) { return 1/cosh(x) };
var csch = function(x) { return 1/sinh(x) };
var coth = function(x) { return 1/tanh(x) };
var arcsech = function(x) { return arccosh(1/x) };
var arccsch = function(x) { return arcsinh(1/x) };
var arccoth = function(x) { return arctanh(1/x) };
var sign = function(x) { return (x==0?0:(x<0?-1:1)) };
var logten = function(x) { return (Math.LOG10E*Math.log(x)) };
var sinn = function(n,x) {return Math.pow(Math.sin(x),n)};
var cosn = function(n,x) {return Math.pow(Math.cos(x),n)};
var tann = function(n,x) {return Math.pow(Math.tan(x),n)};
var cscn = function(n,x) {return 1/Math.pow(Math.sin(x),n)};
var secn = function(n,x) {return 1/Math.pow(Math.cos(x),n)};
var cotn = function(n,x) {return 1/Math.pow(Math.tan(x),n)};

function factorial(x,n) {
  if (n==null) n=1;
  for (var i=x-n; i>0; i-=n) x*=i;
  return (x<0?NaN:(x==0?1:x));
}


function C(x,k) {
  var res=1;
  for (var i=0; i<k; i++) res*=(x-i)/(k-i);
  return res;
}

function matchtolower(match) {
	return match.toLowerCase();
}
function nthroot(n,base) {
	return safepow(base,1/n);
}

function nthlogten(n,v) {
	return ((Math.log(v))/(Math.log(n)));
}
var funcstoindexarr = "sinh|cosh|tanh|sech|csch|coth|sqrt|ln|log|sin|cos|tan|sec|csc|cot|abs|root|arcsin|arccos|arctan|arcsec|arccsc|arccot|arcsinh|arccosh|arctanh|arcsech|arccsch|arccoth".split("|");
function functoindex(match) {
	for (var i=0;i<funcstoindexarr.length;i++) {
		if (funcstoindexarr[i]==match) {
			return '@'+i+'@';
		}
	}
}
function indextofunc(match, contents) {
	return funcstoindexarr[contents];
}

function safepow(base,power) {
	if (base<0 && Math.floor(power)!=power) {
		for (var j=3; j<50; j+=2) {
			if (Math.abs(Math.round(j*power)-(j*power))<.000001) {
				if (Math.round(j*power)%2==0) {
					return Math.pow(Math.abs(base),power);
				} else {
					return -1*Math.pow(Math.abs(base),power);
				}
			}
		}
		return Math.sqrt(-1);
	} else {
		return Math.pow(base,power);
	}
}
//varlist should be pipe-separated list of variables, presorted from longest to shortest
function mathjs(st,varlist) {
  //translate a math formula to js function notation
  // a^b --> pow(a,b)
  // na --> n*a
  // (...)d --> (...)*d
  // n! --> factorial(n)
  // sin^-1 --> arcsin etc.
  //while ^ in string, find term on left and right
  //slice and concat new formula string
  //parenthesizes the function variables
  st = st.replace("[","(");
  st = st.replace("]",")");
  st = st.replace(/root\s*(\d+)/,"root($1)");
  st = st.replace(/\|(.*?)\|/g,"abs($1)");
  st = st.replace(/arc(sin|cos|tan|sec|csc|cot|sinh|cosh|tanh|sech|csch|coth)/gi,"$1^-1");
  st = st.replace(/(Sin|Cos|Tan|Sec|Csc|Cot|Arc|Abs|Log|Ln|Sqrt)/gi, matchtolower);
  //hide functions for now
  st = st.replace(/(sinh|cosh|tanh|sech|csch|coth|sqrt|ln|log|sin|cos|tan|sec|csc|cot|abs|root)/g, functoindex);
  //escape variables so regex's won't interfere
  if (varlist != null && varlist != '') {
  	  var vararr = varlist.split("|");
  	  //search for alt capitalization to escape alt caps correctly
  	  var foundaltcap = [];
  	  for (var i=0; i<vararr.length; i++) {
  	  	  foundaltcap[i] = false;
  	  	  for (var j=0; j<vararr.length; j++) {
  	  	  	  if (i!=j && vararr[j].toLowerCase()==vararr[i].toLowerCase() && vararr[j]!=vararr[i]) {
	  			foundaltcap[i] = true;
	  			break;
	  		}
	  	}
	  }
	  st = st.replace(new RegExp("("+varlist+")","gi"), function(match,p1) {
		 for (var i=0; i<vararr.length;i++) {
			if (vararr[i]==p1 || (!foundaltcap[i] && vararr[i].toLowerCase()==p1.toLowerCase())) { 
				return '(@v'+i+'@)';
			}	
		 }});
  }
  //temp store of scientific notation
  st = st.replace(/([0-9])E([\-0-9])/g,"$1(EE)$2");
  
  //convert named constants
  st = st.replace(/pi/g,"(pi)");
  st = st.replace(/e/g, "(E)");
  
  //restore functions
  st = st.replace(/@(\d+)@/g, indextofunc);
  
  //convert functions
  st = st.replace(/log_([a-zA-Z\d\.]+)\s*\(/g,"nthlog($1,");
  st = st.replace(/log_\(([a-zA-Z\/\d\.]+)\)\s*\(/g,"nthlog($1,");
  st = st.replace(/log/g,"logten");
  st = st.replace(/(sin|cos|tan|sec|csc|cot|sinh|cosh|tanh|sech|csch|coth)\^-1/g,"arc$1");
  st = st.replace(/(sin|cos|tan|sec|csc|cot)\^(\d+)\s*\(/g,"$1n($2,");
  st = st.replace(/root\s*\((\d+)\)\s*\(/g,"nthroot($1,");
  	
  //add implicit mult for "3 4"
  st = st.replace(/([0-9])\s+([0-9])/g,"$1*$2");
  
  //clean up
  st = st.replace(/#/g,"");
  st = st.replace(/\s/g,"");
  
  //restore variables
  if (varlist != null && varlist != '') {
    st = st.replace(/@v(\d+)@/g, function(match,contents) {
  	  return vararr[contents];
       });
  }
  
  //add implicit multiplication
  st = st.replace(/([0-9])([\(a-zA-Z])/g,"$1*$2");
  st = st.replace(/(!)([0-9\(a-zA-Z])/g,"$1*$2");
  st = st.replace(/\)([\(0-9a-zA-Z]|\.\d+)/g,"\)*$1");

  //restore scientific notation

  st= st.replace(/([0-9])\*\(EE\)\*?([\-0-9])/g,"$1e$2");

  //convert powers and factorials
  var i,j,k, ch, nested;
    while ((i=st.indexOf("!"))!=-1) {
    //find left argument
    if (i==0) return "Error: missing argument";
    j = i-1;
    ch = st.charAt(j);
    if (ch>="0" && ch<="9") {// look for (decimal) number
      j--;
      while (j>=0 && (ch=st.charAt(j))>="0" && ch<="9") j--;
      if (ch==".") {
        j--;
        while (j>=0 && (ch=st.charAt(j))>="0" && ch<="9") j--;
      }
    } else if (ch==")") {// look for matching opening bracket and function name
      nested = 1;
      j--;
      while (j>=0 && nested>0) {
        ch = st.charAt(j);
        if (ch=="(") nested--;
        else if (ch==")") nested++;
        j--;
      }
      while (j>=0 && ((ch=st.charAt(j))>="a" && ch<="z" || ch>="A" && ch<="Z"))
        j--;
    } else if (ch>="a" && ch<="z" || ch>="A" && ch<="Z") {// look for variable
      j--;
      while (j>=0 && ((ch=st.charAt(j))>="a" && ch<="z" || ch>="A" && ch<="Z"))
        j--;
    } else {
      return "Error: incorrect syntax in "+st+" at position "+j;
    }
    st = st.slice(0,j+1)+"factorial("+st.slice(j+1,i)+")"+st.slice(i+1);
  }

  while ((i=st.lastIndexOf("^"))!=-1) {

    //find left argument
    if (i==0) return "Error: missing argument";
    j = i-1;
    ch = st.charAt(j);
    if (ch>="0" && ch<="9") {// look for (decimal) number
      j--;
      while (j>=0 && (ch=st.charAt(j))>="0" && ch<="9") j--;
      if (ch==".") {
        j--;
        while (j>=0 && (ch=st.charAt(j))>="0" && ch<="9") j--;
      }
    } else if (ch==")") {// look for matching opening bracket and function name
      nested = 1;
      j--;
      while (j>=0 && nested>0) {
        ch = st.charAt(j);
        if (ch=="(") nested--;
        else if (ch==")") nested++;
        j--;
      }
      while (j>=0 && ((ch=st.charAt(j))>="a" && ch<="z" || ch>="A" && ch<="Z"))
        j--;
    } else if (ch>="a" && ch<="z" || ch>="A" && ch<="Z") {// look for variable
      j--;
      while (j>=0 && ((ch=st.charAt(j))>="a" && ch<="z" || ch>="A" && ch<="Z"))
        j--;
    } else {
      return "Error: incorrect syntax in "+st+" at position "+j;
    }
    //find right argument
    if (i==st.length-1) return "Error: missing argument";
    k = i+1;
    ch = st.charAt(k);
    nch = st.charAt(k+1);
    if (ch>="0" && ch<="9" || (ch=="-" && nch!="(") || ch==".") {// look for signed (decimal) number
      k++;
      while (k<st.length && (ch=st.charAt(k))>="0" && ch<="9") k++;
      if (ch==".") {
        k++;
        while (k<st.length && (ch=st.charAt(k))>="0" && ch<="9") k++;
      }
    } else if (ch=="(" || (ch=="-" && nch=="(")) {// look for matching closing bracket and function name
      if (ch=="-") { k++;}
      nested = 1;
      k++;
      while (k<st.length && nested>0) {
        ch = st.charAt(k);
        if (ch=="(") nested++;
        else if (ch==")") nested--;
        k++;
      }
    } else if (ch>="a" && ch<="z" || ch>="A" && ch<="Z") {// look for variable
      k++;
      while (k<st.length && ((ch=st.charAt(k))>="a" && ch<="z" ||
               ch>="A" && ch<="Z")) k++;
      if (ch=='(' && st.slice(i+1,k).match(/^(sinn|cosn|tann|secn|cscn|cotn|sin|cos|tan|sec|csc|cot|logten|nthlogten|log|ln|exp|arcsin|arccos|arctan|arcsec|arccsc|arccot|sinh|cosh|tanh|sech|csch|coth|arcsinh|arccosh|arctanh|arcsech|arccsch|arccoth|sqrt|abs|nthroot|factorial|safepow)$/)) {
	      nested = 1;
	      k++;
	      while (k<st.length && nested>0) {
		ch = st.charAt(k);
		if (ch=="(") nested++;
		else if (ch==")") nested--;
		k++;
	      }
      }
    } else {
      return "Error: incorrect syntax in "+st+" at position "+k;
    }
    st = st.slice(0,j+1)+"safepow("+st.slice(j+1,i)+","+st.slice(i+1,k)+")"+
           st.slice(k);
  }

  return st;
}

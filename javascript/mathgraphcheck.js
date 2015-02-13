var MathJaxCompatible = true;
var AMnoMathML = true;
var ASnoSVG = true;

function AMisMathMLavailable() {
	//return null if OK, 1 otherwise
	if (isMathJaxavailable()) {
		return null;
	} else {
		return 1;
	}
}

AMnoMathML = (AMisMathMLavailable() != null);

function isMathJaxavailable() {
	var MINVERSION = {
		Firefox: 3.0,
		Opera: 9.52,
		MSIE: 6.0,
		Chrome: 0.3,
		Safari: 2.0,
		Konqueror: 4.0,
		Unknown: 10000.0 // always disable unknown browsers
	};
	
	if (!MathJax.Hub||!MathJax.Hub.Browser.versionAtLeast(MINVERSION[MathJax.Hub.Browser]||0.0)) {
		return false;
	} else {
		return true;
	}
}
MathJaxCompatible = isMathJaxavailable();

function isSVGavailable() {
//return null if we've got SVG

//WebKit got good at SVG after 531.22.7
  if ((ver = navigator.userAgent.toLowerCase().match(/webkit\/(\d+)/))!=null) {
  	  if (navigator.userAgent.toLowerCase().match(/android/)) {
  	  	  return 1;  //Android still can't do SVG yet
  	  }
  	  if (ver[1]>531) {
		return null;
	  }
  }
//Opera can do SVG, but not very pretty, so skip it 
// } else if ((ver = navigator.userAgent.toLowerCase().match(/opera\/([\d\.]+)/))!=null) {
//		if (ver[1]>9.1) {
//			return null;
//		}
  else if (navigator.product && navigator.product=='Gecko') {
	   var rv = navigator.userAgent.toLowerCase().match(/rv:\s*([\d\.]+)/);
	   if (rv!=null) {
		rv = rv[1].split('.');
		if (rv.length<3) { rv[2] = 0;}
		if (rv.length<2) { rv[1] = 0;}
	   }
	   if (rv!=null && 10000*rv[0]+100*rv[1]+1*rv[2]>=10800) return null;
	   else return 1;
  }
  else if (navigator.appName.slice(0,9)=="Microsoft") {
    version = parseFloat(navigator.appVersion.split("MSIE")[1]);
    if (version >= 9) {
    	    //IE 9+ can do SVG
	    return null;
    } else {
	    try	{
	      var oSVG=eval("new ActiveXObject('Adobe.SVGCtl.3');");
		return null;
	    } catch (e) {
		    
		return 1;
	    }
    }
  } else return 1;
}
ASnoSVG = (isSVGavailable()!=null);


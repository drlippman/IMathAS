var AMnoMathML = true;
var ASnoSVG = true;
var AMisGecko = 0;
function AMisMathMLavailable() {
    if (navigator.product && navigator.product=='Gecko' && !navigator.userAgent.toLowerCase().match(/webkit/)) {
	   var rv = navigator.userAgent.toLowerCase().match(/rv:\s*([\d\.]+)/);
	   if (rv!=null) {
		rv = rv[1].split('.');
		if (rv.length<3) { rv[2] = 0;}
		if (rv.length<2) { rv[1] = 0;}
	   }
	   if (rv!=null && 10000*rv[0]+100*rv[1]+1*rv[2]>=10100) {
		   AMisGecko = 10000*rv[0]+100*rv[1]+1*rv[2];
		   return null;
	   } else {
		   return 1;
	   }
    }
  else if (navigator.appName.slice(0,9)=="Microsoft")
    try {
        var ActiveX = new ActiveXObject("MathPlayer.Factory.1");
        return null;
    } catch (e) {
        return 1;
    }
  else return 1;
}

function AMBBoxFor(s) {
	document.getElementById("hidden").innerHTML = 
      '<nobr><span class="typeset"><span class="scale">'+s+'</span></span></nobr>';
      var bbox = {w: document.getElementById("hidden").offsetWidth, h: document.getElementById("hidden").offsetHeight};
      document.getElementById("hidden").innerHTML = '';
      return bbox;
}
//check for TeX font based on approach from jsMath
function AMcheckTeX() {
	hiddendiv = document.createElement("div");
	hiddendiv.style.visibility = "hidden";
	hiddendiv.id = "hidden";
	document.body.appendChild(hiddendiv);
	if (AMisGecko<10900) { //Mozilla 1.8 could use cmex fonts; Mozilla 1.9 only works well with STIX
		wh = AMBBoxFor('<span style="font-family: STIXgeneral, cmex10, serif">&#xEFE8;</span>');
	} else {
		wh = AMBBoxFor('<span style="font-family: STIXgeneral, serif">&#xEFE8;</span>');
	}
	wh2 = AMBBoxFor('<span style="font-family: serif">&#xEFE8;</span>');
	nofonts = (wh.w==wh2.w && wh.h==wh2.h);
	if (nofonts) {
		AMnoMathML = true;
		AMnoTeX = true;
	} else {
		AMnoMathML = false;
		AMnoTeX = false;
	}
}
AMnoTeX = false;
AMnoMathML = (AMisMathMLavailable() != null);

if (!AMnoMathML && (AMisGecko>0)) {
	window.onload = AMcheckTeX;	
}

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


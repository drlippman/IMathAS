var AMnoMathML = true;
var ASnoSVG = true;
var AMisGecko = false;
function AMisMathMLavailable() {
    if (navigator.product && navigator.product=='Gecko') {
	   var rv = navigator.userAgent.toLowerCase().match(/rv:\s*([\d\.]+)/)[1].split('.');
	   if (rv!=null && 10000*rv[0]+100*rv[1]+1*rv[2]>=10100) {
		   AMisGecko = true;
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
	wh = AMBBoxFor('<span style="font-family: cmex10, serif">&#xEFE8;</span>');
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

if (!AMnoMathML && AMisGecko) {
	window.onload = AMcheckTeX;	
}

function isSVGavailable() {
  if (navigator.product && navigator.product=='Gecko') {
	   var rv = navigator.userAgent.toLowerCase().match(/rv:\s*([\d\.]+)/)[1].split('.');
	   if (rv!=null && 10000*rv[0]+100*rv[1]+1*rv[2]>=10800) return null;
	   else return 1;
  }
  else if (navigator.appName.slice(0,9)=="Microsoft")
    try	{
      var oSVG=eval("new ActiveXObject('Adobe.SVGCtl.3');");
        return null;
    } catch (e) {
        return 1;
    }
  else return 1;
}
ASnoSVG = (isSVGavailable()!=null);


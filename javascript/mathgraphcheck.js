var AMnoMathML = true;
var ASnoSVG = true;
function AMisMathMLavailable() {
    if (navigator.product && navigator.product=='Gecko') {
	   var rv = navigator.userAgent.toLowerCase().match(/rv:\s*([\d\.]+)/)[1].split('.');
	   if (rv!=null && 10000*rv[0]+100*rv[1]+1*rv[2]>=10100) return null;
	   else return 1;
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

AMnoMathML = (AMisMathMLavailable() != null);

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


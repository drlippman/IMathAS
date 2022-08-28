/*
ASCIIMathML.js Version 1.4.7 Aug 30, 2005, (c) Peter Jipsen http://www.chapman.edu/~jipsen
w/ small modification for HTMLArea plugin use by David Lippman
Licensed under GNU General Public License (at http://www.gnu.org/copyleft/gpl.html) 
*/

var checkForMathML = true;   // check if browser can display MathML
var notifyIfNoMathML = false; // display note if no MathML capability
var alertIfNoMathML = false;  // show alert box if no MathML capability
var mathcolor = "";       // change it to "" (to inherit) or any other color
var mathfontfamily = "Times,STIXGeneral,serif"; // change to "" to inherit (works in IE) 
                              // or another family (e.g. "arial")
var displaystyle = true;      // puts limits above and below large operators
var showasciiformulaonhover = true; // helps students learn ASCIIMath
var decimalsign = ".";        // change to "," if you like, beware of `(1,2)`!
var AMdelimiter1 = "`", AMescape1 = "\\\\`"; // can use other characters
//var AMdelimiter2 = "$", AMescape2 = "\\\\\\$", AMdelimiter2regexp = "\\$";
var doubleblankmathdelimiter = false; // if true,  x+1  is equal to `x+1`
                                      // for IE this works only in <!--   -->
var fixphi = true;  		//false to return to legacy phi/varphi mapping
				      
var noMathRender = false;
//var separatetokens;// has been removed (email me if this is a problem)
var AMisIE = (navigator.appName.slice(0,9)=="Microsoft");// document.createElementNS==null;

if (document.getElementById==null) 
  alert("This webpage requires a recent browser such as\
\nMozilla/Netscape 7+ or Internet Explorer 6+MathPlayer")

// all further global variables start with "AM"

function AMcreateElementXHTML(t) {
  if (AMisIE) return document.createElement(t);
  else return document.createElementNS("http://www.w3.org/1999/xhtml",t);
}

function AMnoMathMLNote() {
  var nd = AMcreateElementXHTML("h3");
  nd.setAttribute("align","center")
  nd.appendChild(AMcreateElementXHTML("p"));
  nd.appendChild(document.createTextNode("To view the "));
  var an = AMcreateElementXHTML("a");
  an.appendChild(document.createTextNode("ASCIIMathML"));
  an.setAttribute("href","http://www.chapman.edu/~jipsen/asciimath.html");
  nd.appendChild(an);
  nd.appendChild(document.createTextNode(" notation use Internet Explorer 6+"));  
  an = AMcreateElementXHTML("a");
  an.appendChild(document.createTextNode("MathPlayer"));
  an.setAttribute("href","http://www.dessci.com/en/products/mathplayer/download.htm");
  nd.appendChild(an);
  nd.appendChild(document.createTextNode(" or Netscape/Mozilla/Firefox"));
  nd.appendChild(AMcreateElementXHTML("p"));
  return nd;
}

function AMisMathMLavailable() {
  //if (navigator.appName.slice(0,8)=="Netscape") 
    //if (navigator.appVersion.slice(0,1)>="5") return null;
    //else return AMnoMathMLNote();
    if (navigator.product && navigator.product=='Gecko') {
	   var rv = navigator.userAgent.toLowerCase().match(/rv:\s*([\d\.]+)/)[1].split('.');
	   if (rv.length<3) { rv[2] = 0;}
	   if (rv.length<2) { rv[1] = 0;}
	   if (10000*rv[0]+100*rv[1]+1*rv[2]>=10100) return null;
	   else return AMnoMathMLNote();
    }
  else if (navigator.appName.slice(0,9)=="Microsoft")
    try {
        var ActiveX = new ActiveXObject("MathPlayer.Factory.1");
        return null;
    } catch (e) {
        return AMnoMathMLNote();
    }
  else return AMnoMathMLNote();
}

// character lists for Mozilla/Netscape fonts
var AMcal = [0xEF35,0x212C,0xEF36,0xEF37,0x2130,0x2131,0xEF38,0x210B,0x2110,0xEF39,0xEF3A,0x2112,0x2133,0xEF3B,0xEF3C,0xEF3D,0xEF3E,0x211B,0xEF3F,0xEF40,0xEF41,0xEF42,0xEF43,0xEF44,0xEF45,0xEF46];
var AMfrk = [0xEF5D,0xEF5E,0x212D,0xEF5F,0xEF60,0xEF61,0xEF62,0x210C,0x2111,0xEF63,0xEF64,0xEF65,0xEF66,0xEF67,0xEF68,0xEF69,0xEF6A,0x211C,0xEF6B,0xEF6C,0xEF6D,0xEF6E,0xEF6F,0xEF70,0xEF71,0x2128];
var AMbbb = [0xEF8C,0xEF8D,0x2102,0xEF8E,0xEF8F,0xEF90,0xEF91,0x210D,0xEF92,0xEF93,0xEF94,0xEF95,0xEF96,0x2115,0xEF97,0x2119,0x211A,0x211D,0xEF98,0xEF99,0xEF9A,0xEF9B,0xEF9C,0xEF9D,0xEF9E,0x2124];
//var AMcal =["\uD835\uDC9C","\u212C","\uD835\uDC9E","\uD835\uDC9F","\u2130","\u2131","\uD835\uDCA2","\u210B","\u2110","\uD835\uDCA5","\uD835\uDCA6","\u2112","\u2133","\uD835\uDCA9","\uD835\uDCAA","\uD835\uDCAB","\uD835\uDCAC","\u211B","\uD835\uDCAE","\uD835\uDCAF","\uD835\uDCB0","\uD835\uDCB1","\uD835\uDCB2","\uD835\uDCB3","\uD835\uDCB4","\uD835\uDCB5","\uD835\uDCB6","\uD835\uDCB7","\uD835\uDCB8","\uD835\uDCB9","\u212F","\uD835\uDCBB","\u210A","\uD835\uDCBD","\uD835\uDCBE","\uD835\uDCBF","\uD835\uDCC0","\uD835\uDCC1","\uD835\uDCC2","\uD835\uDCC3","\u2134","\uD835\uDCC5","\uD835\uDCC6","\uD835\uDCC7","\uD835\uDCC8","\uD835\uDCC9","\uD835\uDCCA","\uD835\uDCCB","\uD835\uDCCC","\uD835\uDCCD","\uD835\uDCCE","\uD835\uDCCF"];
//var AMfrk =["\uD835\uDD04","\uD835\uDD05","\u212D","\uD835\uDD07","\uD835\uDD08","\uD835\uDD09","\uD835\uDD0A","\u210C","\u2111","\uD835\uDD0D","\uD835\uDD0E","\uD835\uDD0F","\uD835\uDD10","\uD835\uDD11","\uD835\uDD12","\uD835\uDD13","\uD835\uDD14","\u211C","\uD835\uDD16","\uD835\uDD17","\uD835\uDD18","\uD835\uDD19","\uD835\uDD1A","\uD835\uDD1B","\uD835\uDD1C","\u2128","\uD835\uDD1E","\uD835\uDD1F","\uD835\uDD20","\uD835\uDD21","\uD835\uDD22","\uD835\uDD23","\uD835\uDD24","\uD835\uDD25","\uD835\uDD26","\uD835\uDD27","\uD835\uDD28","\uD835\uDD29","\uD835\uDD2A","\uD835\uDD2B","\uD835\uDD2C","\uD835\uDD2D","\uD835\uDD2E","\uD835\uDD2F","\uD835\uDD30","\uD835\uDD31","\uD835\uDD32","\uD835\uDD33","\uD835\uDD34","\uD835\uDD35","\uD835\uDD36","\uD835\uDD37"];
//var AMbbb =["\uD835\uDD38","\uD835\uDD39","\u2102","\uD835\uDD3B","\uD835\uDD3C","\uD835\uDD3D","\uD835\uDD3E","\u210D","\uD835\uDD40","\uD835\uDD41","\uD835\uDD42","\uD835\uDD43","\uD835\uDD44","\u2115","\uD835\uDD46","\u2119","\u211A","\u211D","\uD835\uDD4A","\uD835\uDD4B","\uD835\uDD4C","\uD835\uDD4D","\uD835\uDD4E","\uD835\uDD4F","\uD835\uDD50","\u2124","\uD835\uDD52","\uD835\uDD53","\uD835\uDD54","\uD835\uDD55","\uD835\uDD56","\uD835\uDD57","\uD835\uDD58","\uD835\uDD59","\uD835\uDD5A","\uD835\uDD5B","\uD835\uDD5C","\uD835\uDD5D","\uD835\uDD5E","\uD835\uDD5F","\uD835\uDD60","\uD835\uDD61","\uD835\uDD62","\uD835\uDD63","\uD835\uDD64","\uD835\uDD65","\uD835\uDD66","\uD835\uDD67","\uD835\uDD68","\uD835\uDD69","\uD835\uDD6A","\uD835\uDD6B"]; 

var CONST = 0, UNARY = 1, BINARY = 2, INFIX = 3, LEFTBRACKET = 4, 
    RIGHTBRACKET = 5, SPACE = 6, UNDEROVER = 7, DEFINITION = 8,
    LEFTRIGHT = 9, TEXT = 10; // token types

var AMsqrt = {input:"sqrt", tag:"msqrt", output:"sqrt", tex:null, ttype:UNARY},
  AMroot  = {input:"root", tag:"mroot", output:"root", tex:null, ttype:BINARY},
  AMfrac  = {input:"frac", tag:"mfrac", output:"/",    tex:null, ttype:BINARY},
  AMdiv   = {input:"/",    tag:"mfrac", output:"/",    tex:null, ttype:INFIX},
  AMover  = {input:"stackrel", tag:"mover", output:"stackrel", tex:null, ttype:BINARY},
  AMsub   = {input:"_",    tag:"msub",  output:"_",    tex:null, ttype:INFIX},
  AMsup   = {input:"^",    tag:"msup",  output:"^",    tex:null, ttype:INFIX},
  AMtext  = {input:"text", tag:"mtext", output:"text", tex:null, ttype:TEXT},
  AMmbox  = {input:"mbox", tag:"mtext", output:"mbox", tex:null, ttype:TEXT},
  AMquote = {input:"\"",   tag:"mtext", output:"mbox", tex:null, ttype:TEXT};

var AMsymbols = [
//some greek symbols
{input:"alpha",  tag:"mi", output:"\u03B1", tex:null, ttype:CONST},
{input:"beta",   tag:"mi", output:"\u03B2", tex:null, ttype:CONST},
{input:"chi",    tag:"mi", output:"\u03C7", tex:null, ttype:CONST},
{input:"delta",  tag:"mi", output:"\u03B4", tex:null, ttype:CONST},
{input:"Delta",  tag:"mo", output:"\u0394", tex:null, ttype:CONST},
{input:"epsi",   tag:"mi", output:"\u03B5", tex:"epsilon", ttype:CONST},
{input:"varepsilon", tag:"mi", output:"\u025B", tex:null, ttype:CONST},
{input:"eta",    tag:"mi", output:"\u03B7", tex:null, ttype:CONST},
{input:"gamma",  tag:"mi", output:"\u03B3", tex:null, ttype:CONST},
{input:"Gamma",  tag:"mo", output:"\u0393", tex:null, ttype:CONST},
{input:"iota",   tag:"mi", output:"\u03B9", tex:null, ttype:CONST},
{input:"kappa",  tag:"mi", output:"\u03BA", tex:null, ttype:CONST},
{input:"lambda", tag:"mi", output:"\u03BB", tex:null, ttype:CONST},
{input:"Lambda", tag:"mo", output:"\u039B", tex:null, ttype:CONST},
{input:"lamda", tag:"mi", output:"\u03BB", tex:null, ttype:CONST},
{input:"Lamda", tag:"mo", output:"\u039B", tex:null, ttype:CONST},
{input:"mu",     tag:"mi", output:"\u03BC", tex:null, ttype:CONST},
{input:"nu",     tag:"mi", output:"\u03BD", tex:null, ttype:CONST},
{input:"omega",  tag:"mi", output:"\u03C9", tex:null, ttype:CONST},
{input:"Omega",  tag:"mo", output:"\u03A9", tex:null, ttype:CONST},
{input:"phi",    tag:"mi", output:fixphi?"\u03D5":"\u03C6", tex:null, ttype:CONST},
{input:"varphi", tag:"mi", output:fixphi?"\u03C6":"\u03D5", tex:null, ttype:CONST},
{input:"Phi",    tag:"mo", output:"\u03A6", tex:null, ttype:CONST},
{input:"pi",     tag:"mi", output:"\u03C0", tex:null, ttype:CONST},
{input:"Pi",     tag:"mo", output:"\u03A0", tex:null, ttype:CONST},
{input:"psi",    tag:"mi", output:"\u03C8", tex:null, ttype:CONST},
{input:"Psi",    tag:"mi", output:"\u03A8", tex:null, ttype:CONST},
{input:"rho",    tag:"mi", output:"\u03C1", tex:null, ttype:CONST},
{input:"sigma",  tag:"mi", output:"\u03C3", tex:null, ttype:CONST},
{input:"Sigma",  tag:"mo", output:"\u03A3", tex:null, ttype:CONST},
{input:"tau",    tag:"mi", output:"\u03C4", tex:null, ttype:CONST},
{input:"theta",  tag:"mi", output:"\u03B8", tex:null, ttype:CONST},
{input:"vartheta", tag:"mi", output:"\u03D1", tex:null, ttype:CONST},
{input:"Theta",  tag:"mo", output:"\u0398", tex:null, ttype:CONST},
{input:"upsilon", tag:"mi", output:"\u03C5", tex:null, ttype:CONST},
{input:"xi",     tag:"mi", output:"\u03BE", tex:null, ttype:CONST},
{input:"Xi",     tag:"mo", output:"\u039E", tex:null, ttype:CONST},
{input:"zeta",   tag:"mi", output:"\u03B6", tex:null, ttype:CONST},

//binary operation symbols
{input:"*",  tag:"mo", output:"\u22C5", tex:"cdot", ttype:CONST},
{input:"**", tag:"mo", output:"\u2217", tex:"ast", ttype:CONST},
{input:"***", tag:"mo", output:"\u22C6", tex:"star", ttype:CONST},
{input:"//", tag:"mo", output:"/",      tex:null, ttype:CONST},
{input:"\\\\", tag:"mo", output:"\\",   tex:"backslash", ttype:CONST},
{input:"setminus", tag:"mo", output:"\\", tex:null, ttype:CONST},
{input:"xx", tag:"mo", output:"\u00D7", tex:"times", ttype:CONST},
{input:"-:", tag:"mo", output:"\u00F7", tex:"div", ttype:CONST},
{input:"divide",   tag:"mo", output:"-:", tex:null, ttype:DEFINITION},
{input:"@",  tag:"mo", output:"\u2218", tex:"circ", ttype:CONST},
{input:"o+", tag:"mo", output:"\u2295", tex:"oplus", ttype:CONST},
{input:"o-", tag:"mo", output:"\u2296", tex:"ominus", ttype:CONST},
{input:"ox", tag:"mo", output:"\u2297", tex:"otimes", ttype:CONST},
{input:"o.", tag:"mo", output:"\u2299", tex:"odot", ttype:CONST},
{input:"sum", tag:"mo", output:"\u2211", tex:null, ttype:UNDEROVER},
{input:"prod", tag:"mo", output:"\u220F", tex:null, ttype:UNDEROVER},
{input:"^^",  tag:"mo", output:"\u2227", tex:"wedge", ttype:CONST},
{input:"^^^", tag:"mo", output:"\u22C0", tex:"bigwedge", ttype:UNDEROVER},
{input:"vv",  tag:"mo", output:"\u2228", tex:"vee", ttype:CONST},
{input:"vvv", tag:"mo", output:"\u22C1", tex:"bigvee", ttype:UNDEROVER},
{input:"nn",  tag:"mo", output:"\u2229", tex:"cap", ttype:CONST},
{input:"nnn", tag:"mo", output:"\u22C2", tex:"bigcap", ttype:UNDEROVER},
{input:"uu",  tag:"mo", output:"\u222A", tex:"cup", ttype:CONST},
{input:"uuu", tag:"mo", output:"\u22C3", tex:"bigcup", ttype:UNDEROVER},

//binary relation symbols
{input:"!=",  tag:"mo", output:"\u2260", tex:"ne", ttype:CONST},
{input:":=",  tag:"mo", output:":=",     tex:null, ttype:CONST},
{input:"lt",  tag:"mo", output:"<",      tex:null, ttype:CONST},
{input:"gt",  tag:"mo", output:">",      tex:null, ttype:CONST},
{input:"<=",  tag:"mo", output:"\u2264", tex:"le", ttype:CONST},
{input:"lt=", tag:"mo", output:"\u2264", tex:"leq", ttype:CONST},
{input:"gt=",  tag:"mo", output:"\u2265", tex:"geq", ttype:CONST},
{input:">=",  tag:"mo", output:"\u2265", tex:"ge", ttype:CONST},
{input:"-<",  tag:"mo", output:"\u227A", tex:"prec", ttype:CONST},
{input:"-lt", tag:"mo", output:"\u227A", tex:null, ttype:CONST},
{input:">-",  tag:"mo", output:"\u227B", tex:"succ", ttype:CONST},
{input:"-<=", tag:"mo", output:"\u2AAF", tex:"preceq", ttype:CONST},
{input:">-=", tag:"mo", output:"\u2AB0", tex:"succeq", ttype:CONST},
{input:"in",  tag:"mo", output:"\u2208", tex:null, ttype:CONST},
{input:"!in", tag:"mo", output:"\u2209", tex:"notin", ttype:CONST},
{input:"sub", tag:"mo", output:"\u2282", tex:"subset", ttype:CONST},
{input:"sup", tag:"mo", output:"\u2283", tex:"supset", ttype:CONST},
{input:"sube", tag:"mo", output:"\u2286", tex:"subseteq", ttype:CONST},
{input:"supe", tag:"mo", output:"\u2287", tex:"supseteq", ttype:CONST},
{input:"-=",  tag:"mo", output:"\u2261", tex:"equiv", ttype:CONST},
{input:"~=",  tag:"mo", output:"\u2245", tex:"cong", ttype:CONST},
{input:"~~",  tag:"mo", output:"\u2248", tex:"approx", ttype:CONST},
{input:"prop", tag:"mo", output:"\u221D", tex:"propto", ttype:CONST},


//logical symbols
{input:"and", tag:"mtext", output:"and", tex:null, ttype:SPACE},
{input:"xor",  tag:"mo", output:"\u2295", tex:"oplus", ttype:CONST},
{input:"or",  tag:"mtext", output:"or",  tex:null, ttype:SPACE},
{input:"not", tag:"mo", output:"\u00AC", tex:"neg", ttype:CONST},
{input:"=>",  tag:"mo", output:"\u21D2", tex:"implies", ttype:CONST},
{input:"if",  tag:"mo", output:"if",     tex:null, ttype:SPACE},
{input:"<=>", tag:"mo", output:"\u21D4", tex:"iff", ttype:CONST},
{input:"AA",  tag:"mo", output:"\u2200", tex:"forall", ttype:CONST},
{input:"EE",  tag:"mo", output:"\u2203", tex:"exists", ttype:CONST},
{input:"_|_", tag:"mo", output:"\u22A5", tex:"bot", ttype:CONST},
{input:"TT",  tag:"mo", output:"\u22A4", tex:"top", ttype:CONST},
{input:"|--",  tag:"mo", output:"\u22A2", tex:"vdash", ttype:CONST},
{input:"|==",  tag:"mo", output:"\u22A8", tex:"models", ttype:CONST},

//grouping brackets
{input:"(", tag:"mo", output:"(", tex:null, ttype:LEFTBRACKET},
{input:")", tag:"mo", output:")", tex:null, ttype:RIGHTBRACKET},
{input:"[", tag:"mo", output:"[", tex:null, ttype:LEFTBRACKET},
{input:"]", tag:"mo", output:"]", tex:null, ttype:RIGHTBRACKET},
{input:"{", tag:"mo", output:"{", tex:null, ttype:LEFTBRACKET},
{input:"}", tag:"mo", output:"}", tex:null, ttype:RIGHTBRACKET},
{input:"|", tag:"mo", output:"|", tex:null, ttype:LEFTRIGHT},
//{input:"||", tag:"mo", output:"||", tex:null, ttype:LEFTRIGHT},
{input:"(:", tag:"mo", output:"\u2329", tex:"langle", ttype:LEFTBRACKET},
{input:":)", tag:"mo", output:"\u232A", tex:"rangle", ttype:RIGHTBRACKET},
{input:"<<", tag:"mo", output:"\u2329", tex:null, ttype:LEFTBRACKET},
{input:">>", tag:"mo", output:"\u232A", tex:null, ttype:RIGHTBRACKET},
{input:"{:", tag:"mo", output:"{:", tex:null, ttype:LEFTBRACKET, invisible:true},
{input:":}", tag:"mo", output:":}", tex:null, ttype:RIGHTBRACKET, invisible:true},

//miscellaneous symbols
{input:"int",  tag:"mo", output:"\u222B", tex:null, ttype:CONST},
{input:"dx",   tag:"mi", output:"{:d x:}", tex:null, ttype:DEFINITION},
{input:"dy",   tag:"mi", output:"{:d y:}", tex:null, ttype:DEFINITION},
{input:"dz",   tag:"mi", output:"{:d z:}", tex:null, ttype:DEFINITION},
{input:"dt",   tag:"mi", output:"{:d t:}", tex:null, ttype:DEFINITION},
{input:"oint", tag:"mo", output:"\u222E", tex:null, ttype:CONST},
{input:"del",  tag:"mo", output:"\u2202", tex:"partial", ttype:CONST},
{input:"grad", tag:"mo", output:"\u2207", tex:"nabla", ttype:CONST},
{input:"+-",   tag:"mo", output:"\u00B1", tex:"pm", ttype:CONST},
{input:"O/",   tag:"mo", output:"\u2205", tex:"emptyset", ttype:CONST},
{input:"oo",   tag:"mo", output:"\u221E", tex:"infty", ttype:CONST},
{input:"aleph", tag:"mo", output:"\u2135", tex:null, ttype:CONST},
{input:"...",  tag:"mo", output:"...",    tex:"ldots", ttype:CONST},
{input:":.",  tag:"mo", output:"\u2234",  tex:"therefore", ttype:CONST},
{input:"/_",  tag:"mo", output:"\u2220",  tex:"angle", ttype:CONST},
{input:"/_\\",  tag:"mo", output:"\u25B3",  tex:"triangle", ttype:CONST},
{input:"\\ ",  tag:"mo", output:"\u00A0", tex:null, ttype:CONST},
{input:"quad", tag:"mo", output:"\u00A0\u00A0", tex:null, ttype:CONST},
{input:"qquad", tag:"mo", output:"\u00A0\u00A0\u00A0\u00A0", tex:null, ttype:CONST},
{input:"cdots", tag:"mo", output:"\u22EF", tex:null, ttype:CONST},
{input:"vdots", tag:"mo", output:"\u22EE", tex:null, ttype:CONST},
{input:"ddots", tag:"mo", output:"\u22F1", tex:null, ttype:CONST},
{input:"diamond", tag:"mo", output:"\u22C4", tex:null, ttype:CONST},
{input:"square", tag:"mo", output:"\u25A1", tex:null, ttype:CONST},
{input:"|__", tag:"mo", output:"\u230A",  tex:"lfloor", ttype:CONST},
{input:"__|", tag:"mo", output:"\u230B",  tex:"rfloor", ttype:CONST},
{input:"|~", tag:"mo", output:"\u2308",  tex:"lceiling", ttype:CONST},
{input:"~|", tag:"mo", output:"\u2309",  tex:"rceiling", ttype:CONST},
{input:"CC",  tag:"mo", output:"\u2102", tex:null, ttype:CONST},
{input:"NN",  tag:"mo", output:"\u2115", tex:null, ttype:CONST},
{input:"QQ",  tag:"mo", output:"\u211A", tex:null, ttype:CONST},
{input:"RR",  tag:"mo", output:"\u211D", tex:null, ttype:CONST},
{input:"ZZ",  tag:"mo", output:"\u2124", tex:null, ttype:CONST},
{input:"f",   tag:"mi", output:"f",      tex:null, ttype:UNARY, func:true},
{input:"g",   tag:"mi", output:"g",      tex:null, ttype:UNARY, func:true},
{input:"'",  tag:"mo", output:"\u2032", tex:"prime", ttype:CONST}, 

//standard functions
{input:"lim",  tag:"mo", output:"lim", tex:null, ttype:UNDEROVER},
{input:"Lim",  tag:"mo", output:"Lim", tex:null, ttype:UNDEROVER},
{input:"sin",  tag:"mo", output:"sin", tex:null, ttype:UNARY, func:true},
{input:"cos",  tag:"mo", output:"cos", tex:null, ttype:UNARY, func:true},
{input:"tan",  tag:"mo", output:"tan", tex:null, ttype:UNARY, func:true},
{input:"arcsin",  tag:"mo", output:"arcsin", tex:null, ttype:UNARY, func:true},
{input:"arccos",  tag:"mo", output:"arccos", tex:null, ttype:UNARY, func:true},
{input:"arctan",  tag:"mo", output:"arctan", tex:null, ttype:UNARY, func:true},
{input:"sinh", tag:"mo", output:"sinh", tex:null, ttype:UNARY, func:true},
{input:"cosh", tag:"mo", output:"cosh", tex:null, ttype:UNARY, func:true},
{input:"tanh", tag:"mo", output:"tanh", tex:null, ttype:UNARY, func:true},
{input:"coth",  tag:"mo", output:"coth", tex:null, ttype:UNARY, func:true},
{input:"sech",  tag:"mo", output:"sech", tex:null, ttype:UNARY, func:true},
{input:"csch",  tag:"mo", output:"csch", tex:null, ttype:UNARY, func:true},
{input:"cot",  tag:"mo", output:"cot", tex:null, ttype:UNARY, func:true},
{input:"sec",  tag:"mo", output:"sec", tex:null, ttype:UNARY, func:true},
{input:"csc",  tag:"mo", output:"csc", tex:null, ttype:UNARY, func:true},
{input:"log",  tag:"mo", output:"log", tex:null, ttype:UNARY, func:true},
{input:"ln",   tag:"mo", output:"ln",  tex:null, ttype:UNARY, func:true},
{input:"abs",   tag:"mo", output:"abs",  tex:null, ttype:UNARY}, //, func:true
{input:"Sin",  tag:"mo", output:"sin", tex:null, ttype:UNARY, func:true},
{input:"Cos",  tag:"mo", output:"cos", tex:null, ttype:UNARY, func:true},
{input:"Tan",  tag:"mo", output:"tan", tex:null, ttype:UNARY, func:true},
{input:"Arcsin",  tag:"mo", output:"arcsin", tex:null, ttype:UNARY, func:true},
{input:"Arccos",  tag:"mo", output:"arccos", tex:null, ttype:UNARY, func:true},
{input:"Arctan",  tag:"mo", output:"arctan", tex:null, ttype:UNARY, func:true},
{input:"Sinh", tag:"mo", output:"sinh", tex:null, ttype:UNARY, func:true},
{input:"Sosh", tag:"mo", output:"cosh", tex:null, ttype:UNARY, func:true},
{input:"Tanh", tag:"mo", output:"tanh", tex:null, ttype:UNARY, func:true},
{input:"Cot",  tag:"mo", output:"cot", tex:null, ttype:UNARY, func:true},
{input:"Sec",  tag:"mo", output:"sec", tex:null, ttype:UNARY, func:true},
{input:"Csc",  tag:"mo", output:"csc", tex:null, ttype:UNARY, func:true},
{input:"Log",  tag:"mo", output:"log", tex:null, ttype:UNARY, func:true},
{input:"Ln",   tag:"mo", output:"ln",  tex:null, ttype:UNARY, func:true},
{input:"Abs",   tag:"mo", output:"abs",  tex:null, ttype:UNARY, func:true},
{input:"det",  tag:"mo", output:"det", tex:null, ttype:UNARY, func:true},
{input:"exp",  tag:"mo", output:"exp", tex:null, ttype:UNARY, func:true},
{input:"dim",  tag:"mo", output:"dim", tex:null, ttype:CONST},
{input:"mod",  tag:"mo", output:"mod", tex:null, ttype:CONST},
{input:"gcd",  tag:"mo", output:"gcd", tex:null, ttype:UNARY, func:true},
{input:"lcm",  tag:"mo", output:"lcm", tex:null, ttype:UNARY, func:true},
{input:"lub",  tag:"mo", output:"lub", tex:null, ttype:CONST},
{input:"glb",  tag:"mo", output:"glb", tex:null, ttype:CONST},
{input:"min",  tag:"mo", output:"min", tex:null, ttype:UNDEROVER},
{input:"max",  tag:"mo", output:"max", tex:null, ttype:UNDEROVER},

//{input:"longdiv", tag:"mtable", output:"longdiv", tex:null, ttype:BINARY},

//arrows
{input:"uarr", tag:"mo", output:"\u2191", tex:"uparrow", ttype:CONST},
{input:"darr", tag:"mo", output:"\u2193", tex:"downarrow", ttype:CONST},
{input:"rarr", tag:"mo", output:"\u2192", tex:"rightarrow", ttype:CONST},
{input:"->",   tag:"mo", output:"\u2192", tex:"to", ttype:CONST},
{input:"|->",  tag:"mo", output:"\u21A6", tex:"mapsto", ttype:CONST},
{input:">->",   tag:"mo", output:"\u21A3", tex:"rightarrowtail", ttype:CONST},
{input:"->>",   tag:"mo", output:"\u21A0", tex:"twoheadrightarrow", ttype:CONST},
{input:">->>",   tag:"mo", output:"\u2916", tex:"twoheadrightarrowtail", ttype:CONST},
{input:"larr", tag:"mo", output:"\u2190", tex:"leftarrow", ttype:CONST},
{input:"harr", tag:"mo", output:"\u2194", tex:"leftrightarrow", ttype:CONST},
{input:"rArr", tag:"mo", output:"\u21D2", tex:"Rightarrow", ttype:CONST},
{input:"lArr", tag:"mo", output:"\u21D0", tex:"Leftarrow", ttype:CONST},
{input:"hArr", tag:"mo", output:"\u21D4", tex:"Leftrightarrow", ttype:CONST},

//commands with argument
AMsqrt, AMroot, AMfrac, AMdiv, AMover, AMsub, AMsup,
{input:"Sqrt", tag:"msqrt", output:"sqrt", tex:null, ttype:UNARY},
{input:"cancel", tag:"menclose", output:"cancel", tex:null, ttype:UNARY},
{input:"hat", tag:"mover", output:"\u005E", tex:null, ttype:UNARY, acc:true},
{input:"bar", tag:"mover", output:"\u00AF", tex:"overline", ttype:UNARY, acc:true},
{input:"vec", tag:"mover", output:"\u2192", tex:null, ttype:UNARY, acc:true},
{input:"tilde", tag:"mover", output:"~", tex:null, ttype:UNARY, acc:true}, 
{input:"dot", tag:"mover", output:".",      tex:null, ttype:UNARY, acc:true},
{input:"ddot", tag:"mover", output:"..",    tex:null, ttype:UNARY, acc:true},
{input:"ul", tag:"munder", output:"\u0332", tex:"underline", ttype:UNARY, acc:true},
AMtext, AMmbox, AMquote,
//{input:"var", tag:"mstyle", atname:"fontstyle", atval:"italic", output:"var", tex:null, ttype:UNARY},
{input:"color", tag:"mstyle", ttype:BINARY},
{input:"bb", tag:"mstyle", atname:"fontweight", atval:"bold", output:"bb", tex:null, ttype:UNARY},
{input:"mathbf", tag:"mstyle", atname:"fontweight", atval:"bold", output:"mathbf", tex:null, ttype:UNARY},
{input:"sf", tag:"mstyle", atname:"fontfamily", atval:"sans-serif", output:"sf", tex:null, ttype:UNARY},
{input:"mathsf", tag:"mstyle", atname:"fontfamily", atval:"sans-serif", output:"mathsf", tex:null, ttype:UNARY},
{input:"bbb", tag:"mstyle", atname:"mathvariant", atval:"double-struck", output:"bbb", tex:null, ttype:UNARY, codes:AMbbb},
{input:"mathbb", tag:"mstyle", atname:"mathvariant", atval:"double-struck", output:"mathbb", tex:null, ttype:UNARY, codes:AMbbb},
{input:"cc",  tag:"mstyle", atname:"mathvariant", atval:"script", output:"cc", tex:null, ttype:UNARY, codes:AMcal},
{input:"mathcal", tag:"mstyle", atname:"mathvariant", atval:"script", output:"mathcal", tex:null, ttype:UNARY, codes:AMcal},
{input:"tt",  tag:"mstyle", atname:"fontfamily", atval:"monospace", output:"tt", tex:null, ttype:UNARY},
{input:"mathtt", tag:"mstyle", atname:"fontfamily", atval:"monospace", output:"mathtt", tex:null, ttype:UNARY},
{input:"fr",  tag:"mstyle", atname:"mathvariant", atval:"fraktur", output:"fr", tex:null, ttype:UNARY, codes:AMfrk},
{input:"mathfrak",  tag:"mstyle", atname:"mathvariant", atval:"fraktur", output:"mathfrak", tex:null, ttype:UNARY, codes:AMfrk}
];

function compareNames(s1,s2) {
  if (s1.input > s2.input) return 1
  else return -1;
}

var AMnames = []; //list of input symbols

function AMinitSymbols() {
  var texsymbols = [], i;
  for (i=0; i<AMsymbols.length; i++)
    if (AMsymbols[i].tex) 
      texsymbols[texsymbols.length] = {input:AMsymbols[i].tex, 
        tag:AMsymbols[i].tag, output:AMsymbols[i].output, ttype:AMsymbols[i].ttype};
  AMsymbols = AMsymbols.concat(texsymbols);
  AMsymbols.sort(compareNames);
  for (i=0; i<AMsymbols.length; i++) AMnames[i] = AMsymbols[i].input;
}

var AMmathml = "http://www.w3.org/1998/Math/MathML";

function AMcreateElementMathML(t) {
  if (AMisIE) return document.createElement("m:"+t);
  else return document.createElementNS(AMmathml,t);
}

function AMcreateMmlNode(t,frag) {
//  var node = AMcreateElementMathML(name);
  if (AMisIE) var node = document.createElement("m:"+t);
  else var node = document.createElementNS(AMmathml,t);
  node.appendChild(frag);
  return node;
}

function newcommand(oldstr,newstr) {
  AMsymbols = AMsymbols.concat([{input:oldstr, tag:"mo", output:newstr, 
                                 tex:null, ttype:DEFINITION}]);
}

function AMremoveCharsAndBlanks(str,n) {
//remove n characters and any following blanks
  var st;
  if (str.charAt(n)=="\\" && str.charAt(n+1)!="\\" && str.charAt(n+1)!=" ") 
    st = str.slice(n+1);
  else st = str.slice(n);
  for (var i=0; i<st.length && st.charCodeAt(i)<=32; i=i+1);
  return st.slice(i);
}

function AMposition(arr, str, n) { 
// return position >=n where str appears or would be inserted
// assumes arr is sorted
  if (n==0) {
    var h,m;
    n = -1;
    h = arr.length;
    while (n+1<h) {
      m = (n+h) >> 1;
      if (arr[m]<str) n = m; else h = m;
    }
    return h;
  } else
    for (var i=n; i<arr.length && arr[i]<str; i++);
  return i; // i=arr.length || arr[i]>=str
}

function AMgetSymbol(str) {
//return maximal initial substring of str that appears in names
//return null if there is none
  var k = 0; //new pos
  var j = 0; //old pos
  var mk; //match pos
  var st;
  var tagst;
  var match = "";
  var more = true;
  for (var i=1; i<=str.length && more; i++) {
    st = str.slice(0,i); //initial substring of length i
    j = k;
    k = AMposition(AMnames, st, j);
    if (k<AMnames.length && str.slice(0,AMnames[k].length)==AMnames[k]){
      match = AMnames[k];
      mk = k;
      i = match.length;
    }
    more = k<AMnames.length && str.slice(0,AMnames[k].length)>=AMnames[k];
  }
  AMpreviousSymbol=AMcurrentSymbol;
  if (match!=""){
    AMcurrentSymbol=AMsymbols[mk].ttype;
    return AMsymbols[mk]; 
  }
// if str[0] is a digit or - return maxsubstring of digits.digits
  AMcurrentSymbol=CONST;
  k = 1;
  st = str.slice(0,1);
  var integ = true;
 	  
  while ("0"<=st && st<="9" && k<=str.length) {
    st = str.slice(k,k+1);
    k++;
  }
  if (st == decimalsign) {
    st = str.slice(k,k+1);
    if ("0"<=st && st<="9") {
      integ = false;
      k++;
      while ("0"<=st && st<="9" && k<=str.length) {
        st = str.slice(k,k+1);
        k++;
      }
    }
  }
  if ((integ && k>1) || k>2) {
    st = str.slice(0,k-1);
    tagst = "mn";
  } else {
    k = 2;
    st = str.slice(0,1); //take 1 character
    tagst = (("A">st || st>"Z") && ("a">st || st>"z")?"mo":"mi");
  }
  if (st=="-" && AMpreviousSymbol==INFIX) {
    AMcurrentSymbol = INFIX;
    return {input:st, tag:tagst, output:st, ttype:UNARY, func:true};
  }
  return {input:st, tag:tagst, output:st, ttype:CONST};
}

function AMremoveBrackets(node) {
  var st;
  if (node.nodeName=="mrow" || node.nodeName=="M:MROW") {
    st = node.firstChild.firstChild.nodeValue;
    if (st=="(" || st=="[" || st=="{") node.removeChild(node.firstChild);
  }
  if (node.nodeName=="mrow" || node.nodeName=="M:MROW") {
    st = node.lastChild.firstChild.nodeValue;
    if (st==")" || st=="]" || st=="}") node.removeChild(node.lastChild);
  }
}

/*Parsing ASCII math expressions with the following grammar
v ::= [A-Za-z] | greek letters | numbers | other constant symbols
u ::= sqrt | text | bb | other unary symbols for font commands
b ::= frac | root | stackrel         binary symbols
l ::= ( | [ | { | (: | {:            left brackets
r ::= ) | ] | } | :) | :}            right brackets
S ::= v | lEr | uS | bSS             Simple expression
I ::= S_S | S^S | S_S^S | S          Intermediate expression
E ::= IE | I/I                       Expression
Each terminal symbol is translated into a corresponding mathml node.*/

var AMnestingDepth,AMpreviousSymbol,AMcurrentSymbol;

function AMparseSexpr(str) { //parses str and returns [node,tailstr]
  var symbol, node, result, i, st,// rightvert = false,
    newFrag = document.createDocumentFragment();
  str = AMremoveCharsAndBlanks(str,0);
  symbol = AMgetSymbol(str);             //either a token or a bracket or empty
  if (symbol == null || symbol.ttype == RIGHTBRACKET && AMnestingDepth > 0) {
    return [null,str];
  }
  if (symbol.ttype == DEFINITION) {
    str = symbol.output+AMremoveCharsAndBlanks(str,symbol.input.length); 
    symbol = AMgetSymbol(str);
  }
  switch (symbol.ttype) {
  case UNDEROVER:
  case CONST:
    str = AMremoveCharsAndBlanks(str,symbol.input.length); 
    return [AMcreateMmlNode(symbol.tag,        //its a constant
                             document.createTextNode(symbol.output)),str];
  case LEFTBRACKET:   //read (expr+)
    AMnestingDepth++;
    str = AMremoveCharsAndBlanks(str,symbol.input.length); 
    result = AMparseExpr(str,true);
    AMnestingDepth--;
    if (typeof symbol.invisible == "boolean" && symbol.invisible) 
      node = AMcreateMmlNode("mrow",result[0]);
    else {
      node = AMcreateMmlNode("mo",document.createTextNode(symbol.output));
      node = AMcreateMmlNode("mrow",node);
      node.appendChild(result[0]);
    }
    return [node,result[1]];
  case TEXT:
      if (symbol!=AMquote) str = AMremoveCharsAndBlanks(str,symbol.input.length);
      if (str.charAt(0)=="{") i=str.indexOf("}");
      else if (str.charAt(0)=="(") i=str.indexOf(")");
      else if (str.charAt(0)=="[") i=str.indexOf("]");
      else if (symbol==AMquote) i=str.slice(1).indexOf("\"")+1;
      else i = 0;
      if (i==-1) i = str.length;
      st = str.slice(1,i);
      if (st.charAt(0) == " ") {
        node = AMcreateElementMathML("mspace");
        node.setAttribute("width","1ex");
        newFrag.appendChild(node);
      }
      newFrag.appendChild(
        AMcreateMmlNode(symbol.tag,document.createTextNode(st)));
      if (st.charAt(st.length-1) == " ") {
        node = AMcreateElementMathML("mspace");
        node.setAttribute("width","1ex");
        newFrag.appendChild(node);
      }
      str = AMremoveCharsAndBlanks(str,i+1);
      return [AMcreateMmlNode("mrow",newFrag),str];
  case UNARY:
      str = AMremoveCharsAndBlanks(str,symbol.input.length); 
      result = AMparseSexpr(str);
      if (result[0]==null) return [AMcreateMmlNode(symbol.tag,
                             document.createTextNode(symbol.output)),str];
      if (typeof symbol.func == "boolean" && symbol.func) { // functions hack
        st = str.charAt(0);
        if (st=="^" || st=="_" || st=="/" || st=="|" || st=="," || ((symbol.input=="f" || symbol.input=="g") && st!="(")) {
          return [AMcreateMmlNode(symbol.tag,
                    document.createTextNode(symbol.output)),str];
        } else {
          node = AMcreateMmlNode("mrow",
           AMcreateMmlNode(symbol.tag,document.createTextNode(symbol.output)));
          node.appendChild(result[0]);
          return [node,result[1]];
        }
      }
      AMremoveBrackets(result[0]);
      if (symbol.input == "sqrt") {           // sqrt
        return [AMcreateMmlNode(symbol.tag,result[0]),result[1]];
      } else  if (symbol.input == "abs") {           // abs
         node = AMcreateMmlNode("mrow", AMcreateMmlNode("mo",document.createTextNode('|')));
         node.appendChild(result[0]);
         node.appendChild(AMcreateMmlNode("mo",document.createTextNode('|')));
         return [node,result[1]];
      } else if (symbol.input == "cancel") {   // cancel
        node = AMcreateMmlNode(symbol.tag,result[0]);
	node.setAttribute("notation","updiagonalstrike");
	return [node,result[1]];
      } else if (typeof symbol.acc == "boolean" && symbol.acc) {   // accent
        node = AMcreateMmlNode(symbol.tag,result[0]);
	node.setAttribute("accent","true");
	node.appendChild(AMcreateMmlNode("mo",document.createTextNode(symbol.output)));
        return [node,result[1]];
      } else {                        // font change command
        if (!AMisIE && typeof symbol.codes != "undefined") {
          for (i=0; i<result[0].childNodes.length; i++)
            if (result[0].childNodes[i].nodeName=="mi" || result[0].nodeName=="mi") {
              st = (result[0].nodeName=="mi"?result[0].firstChild.nodeValue:
                              result[0].childNodes[i].firstChild.nodeValue);
              var newst = [];
              for (var j=0; j<st.length; j++)
                if (st.charCodeAt(j)>64 && st.charCodeAt(j)<91) newst = newst + symbol.codes[st.charCodeAt(j)-65]; 
                  //String.fromCharCode(symbol.codes[st.charCodeAt(j)-65]);
                else newst = newst + st.charAt(j);
              if (result[0].nodeName=="mi")
                result[0]=AMcreateElementMathML("mo").
                          appendChild(document.createTextNode(newst));
              else result[0].replaceChild(AMcreateElementMathML("mo").
          appendChild(document.createTextNode(newst)),result[0].childNodes[i]);
            }
        }
        node = AMcreateMmlNode(symbol.tag,result[0]);
        node.setAttribute(symbol.atname,symbol.atval);
        return [node,result[1]];
      }
  case BINARY:
    str = AMremoveCharsAndBlanks(str,symbol.input.length); 
    result = AMparseSexpr(str);
    if (result[0]==null) return [AMcreateMmlNode("mo",
                           document.createTextNode(symbol.input)),str];
    AMremoveBrackets(result[0]);
    var result2 = AMparseSexpr(result[1]);
    if (result2[0]==null) return [AMcreateMmlNode("mo",
                           document.createTextNode(symbol.input)),str];
    AMremoveBrackets(result2[0]);
    if (symbol.input=="color") {
	if (str.charAt(0)=="{") i=str.indexOf("}");
        else if (str.charAt(0)=="(") i=str.indexOf(")");
        else if (str.charAt(0)=="[") i=str.indexOf("]");
	st = str.slice(1,i);
	node = AMcreateMmlNode(symbol.tag,result2[0]);
	node.setAttribute("color",st);
	return [node,result2[1]];
    }
    /*FireFox doesn't support yet
    if (symbol.input=="longdiv") {
	    node = AMcreateMmlNode("mtd",result2[0].firstChild);
	    node.setAttribute("columnalign","right");
	    mtr = AMcreateMmlNode("mtr",node);
	    node = AMcreateMmlNode("menclose",result[0].firstChild);
	    node.setAttribute("notation","longdiv");
	    node2 = AMcreateMmlNode("mtd",node);
	    mtr.appendChild(node2);
	    newFrag.appendChild(mtr);
	    return [AMcreateMmlNode(symbol.tag,newFrag),result2[1]];
    }*/
    if (symbol.input=="root" || symbol.input=="stackrel") 
      newFrag.appendChild(result2[0]);
    newFrag.appendChild(result[0]);
    if (symbol.input=="frac") newFrag.appendChild(result2[0]);
    return [AMcreateMmlNode(symbol.tag,newFrag),result2[1]];
  case INFIX:
    str = AMremoveCharsAndBlanks(str,symbol.input.length); 
    return [AMcreateMmlNode("mo",document.createTextNode(symbol.output)),str];
  case SPACE:
    str = AMremoveCharsAndBlanks(str,symbol.input.length); 
    node = AMcreateElementMathML("mspace");
    node.setAttribute("width","1ex");
    newFrag.appendChild(node);
    newFrag.appendChild(
      AMcreateMmlNode(symbol.tag,document.createTextNode(symbol.output)));
    node = AMcreateElementMathML("mspace");
    node.setAttribute("width","1ex");
    newFrag.appendChild(node);
    return [AMcreateMmlNode("mrow",newFrag),str];
  case LEFTRIGHT:
//    if (rightvert) return [null,str]; else rightvert = true;
    AMnestingDepth++;
    str = AMremoveCharsAndBlanks(str,symbol.input.length); 
    result = AMparseExpr(str,false);
    AMnestingDepth--;
    var st = "";
    if (result[0].lastChild!=null)
      st = result[0].lastChild.firstChild.nodeValue;
//alert(result[0].lastChild+"***"+st);
    if (st == "|") { // its an absolute value subterm
      node = AMcreateMmlNode("mo",document.createTextNode(symbol.output));
      node = AMcreateMmlNode("mrow",node);
      node.appendChild(result[0]);
      return [node,result[1]];
    } else { // the "|" is a \mid
      node = AMcreateMmlNode("mo",document.createTextNode(symbol.output));
      node = AMcreateMmlNode("mrow",node);
      return [node,str];
    }
  default:
//alert("default");
    str = AMremoveCharsAndBlanks(str,symbol.input.length); 
    return [AMcreateMmlNode(symbol.tag,        //its a constant
                             document.createTextNode(symbol.output)),str];
  }
}

function AMparseIexpr(str) {
  var symbol, sym1, sym2, node, result, underover;
  str = AMremoveCharsAndBlanks(str,0);
  sym1 = AMgetSymbol(str);
  result = AMparseSexpr(str);
  node = result[0];
  str = result[1];
  symbol = AMgetSymbol(str);
  if (symbol.ttype == INFIX && symbol.input != "/") {
    str = AMremoveCharsAndBlanks(str,symbol.input.length);
   // if (symbol.input == "/") result = AMparseIexpr(str); else 
    result = AMparseSexpr(str);
    if (result[0] == null) // show box in place of missing argument
      result[0] = AMcreateMmlNode("mo",document.createTextNode("\u25A1"));
    else AMremoveBrackets(result[0]);
    str = result[1];
//    if (symbol.input == "/") AMremoveBrackets(node);
    if (symbol.input == "_") {
      sym2 = AMgetSymbol(str);
      underover = (sym1.ttype == UNDEROVER);
      if (sym2.input == "^") {
        str = AMremoveCharsAndBlanks(str,sym2.input.length);
        var res2 = AMparseSexpr(str);
        AMremoveBrackets(res2[0]);
        str = res2[1];
        node = AMcreateMmlNode((underover?"munderover":"msubsup"),node);
        node.appendChild(result[0]);
        node.appendChild(res2[0]);
        node = AMcreateMmlNode("mrow",node); // so sum does not stretch
      } else {
        node = AMcreateMmlNode((underover?"munder":"msub"),node);
        node.appendChild(result[0]);
      }
    } else {
      node = AMcreateMmlNode(symbol.tag,node);
      node.appendChild(result[0]);
    }
    if (typeof sym1.func == "boolean" && sym1.func) {
  	  var symbol = AMgetSymbol(str);
  	  if (symbol.ttype != INFIX) {
  	  	var node = AMcreateMmlNode("mrow",node);
  	  	result = AMparseSexpr(str);
  	  	node.appendChild(result[0]);
  	  	str = result[1];
  	  }
    }
  }
  return [node,str];
}

function AMparseExpr(str,rightbracket) {
  var symbol, node, result, i, nodeList = [],
  newFrag = document.createDocumentFragment();
  do {
    str = AMremoveCharsAndBlanks(str,0);
    result = AMparseIexpr(str);
    node = result[0];
    str = result[1];
    symbol = AMgetSymbol(str);
    if (symbol.ttype == INFIX && symbol.input == "/") {
      str = AMremoveCharsAndBlanks(str,symbol.input.length);
      result = AMparseIexpr(str);
      if (result[0] == null) // show box in place of missing argument
        result[0] = AMcreateMmlNode("mo",document.createTextNode("\u25A1"));
      else AMremoveBrackets(result[0]);
      str = result[1];
      AMremoveBrackets(node);
      node = AMcreateMmlNode(symbol.tag,node);
      node.appendChild(result[0]);
      newFrag.appendChild(node);
      symbol = AMgetSymbol(str);
    } 
    else if (node!=undefined) newFrag.appendChild(node);
  } while ((symbol.ttype != RIGHTBRACKET && 
           (symbol.ttype != LEFTRIGHT || rightbracket)
           || AMnestingDepth == 0) && symbol!=null && symbol.output!="");
  if (symbol.ttype == RIGHTBRACKET || symbol.ttype == LEFTRIGHT) {
//    if (AMnestingDepth > 0) AMnestingDepth--;
    var len = newFrag.childNodes.length;
    if (len>0 && newFrag.childNodes[len-1].nodeName == "mrow" ) { //matrix
    	    //removed 5/25/10 to allow row vecs: //&& len>1 && 
    	    //newFrag.childNodes[len-2].nodeName == "mo" &&
    	    //newFrag.childNodes[len-2].firstChild.nodeValue == ","
      var right = newFrag.childNodes[len-1].lastChild.firstChild.nodeValue;
      if (right==")" || right=="]") {
        var left = newFrag.childNodes[len-1].firstChild.firstChild.nodeValue;
        if (left=="(" && right==")" && symbol.output != "}" || 
            left=="[" && right=="]") {
        var pos = []; // positions of commas
        var matrix = true;
        var m = newFrag.childNodes.length;
        for (i=0; matrix && i<m; i=i+2) {
          pos[i] = [];
          node = newFrag.childNodes[i];
          if (matrix) matrix = node.nodeName=="mrow" && 
            (i==m-1 || node.nextSibling.nodeName=="mo" && 
            node.nextSibling.firstChild.nodeValue==",")&&
            node.firstChild.firstChild.nodeValue==left &&
            node.lastChild.firstChild.nodeValue==right;
          if (matrix) 
            for (var j=0; j<node.childNodes.length; j++)
              if (node.childNodes[j].firstChild.nodeValue==",")
                pos[i][pos[i].length]=j;
          if (matrix && i>1) matrix = pos[i].length == pos[i-2].length;
        }
        matrix = matrix && (pos.length>1 || pos[0].length>0);
        if (matrix) {
          var row, frag, n, k, table = document.createDocumentFragment();
          for (i=0; i<m; i=i+2) {
            row = document.createDocumentFragment();
            frag = document.createDocumentFragment();
            node = newFrag.firstChild; // <mrow>(-,-,...,-,-)</mrow>
            n = node.childNodes.length;
            k = 0;
            node.removeChild(node.firstChild); //remove (
            for (j=1; j<n-1; j++) {
              if (typeof pos[i][k] != "undefined" && j==pos[i][k]){
                node.removeChild(node.firstChild); //remove ,
                row.appendChild(AMcreateMmlNode("mtd",frag));
                k++;
              } else frag.appendChild(node.firstChild);
            }
            row.appendChild(AMcreateMmlNode("mtd",frag));
            if (newFrag.childNodes.length>2) {
              newFrag.removeChild(newFrag.firstChild); //remove <mrow>)</mrow>
              newFrag.removeChild(newFrag.firstChild); //remove <mo>,</mo>
            }
            table.appendChild(AMcreateMmlNode("mtr",row));
          }
          node = AMcreateMmlNode("mtable",table);
          if (typeof symbol.invisible == "boolean" && symbol.invisible) node.setAttribute("columnalign","left");
          newFrag.replaceChild(node,newFrag.firstChild);
        }
       }
      }
    }
    str = AMremoveCharsAndBlanks(str,symbol.input.length);
    if (typeof symbol.invisible != "boolean" || !symbol.invisible) {
      node = AMcreateMmlNode("mo",document.createTextNode(symbol.output));
      newFrag.appendChild(node);
    }
  }
  return [newFrag,str];
}

function AMparseMath(str) {
  var result, node = AMcreateElementMathML("mstyle");
  if (mathcolor != "") node.setAttribute("mathcolor",mathcolor);
  if (displaystyle) node.setAttribute("displaystyle","true");
  if (mathfontfamily != "") node.setAttribute("fontfamily",mathfontfamily);
  AMnestingDepth = 0;
  //DLMOD to remove &nbsp;, which editor adds on multiple spaces
  str = str.replace(/&nbsp;/g,"");
  str = str.replace(/&gt;/g,">");
  str = str.replace(/&lt;/g,"<");
  node.appendChild(AMparseExpr(str.replace(/^\s+/g,""),false)[0]);
  node = AMcreateMmlNode("math",node);
  if (showasciiformulaonhover)                      //fixed by djhsu so newline
    node.setAttribute("title",str.replace(/\s+/g," "));//does not show in Gecko
  if (mathfontfamily != "" && (AMisIE || mathfontfamily != "serif")) {
    var fnode = AMcreateElementXHTML("font");
    fnode.setAttribute("face",mathfontfamily);
    fnode.appendChild(node);
    return fnode;
  }
  return node;
}

function AMstrarr2docFrag(arr, linebreaks) {
  var newFrag=document.createDocumentFragment();
  var expr = false;
  for (var i=0; i<arr.length; i++) {
    if (expr) newFrag.appendChild(AMparseMath(arr[i]));
    else {
      var arri = (linebreaks ? arr[i].split("\n\n") : [arr[i]]);
      newFrag.appendChild(AMcreateElementXHTML("span").
      appendChild(document.createTextNode(arri[0])));
      for (var j=1; j<arri.length; j++) {
        newFrag.appendChild(AMcreateElementXHTML("p"));
        newFrag.appendChild(AMcreateElementXHTML("span").
        appendChild(document.createTextNode(arri[j])));
      }
    }
    expr = !expr;
  }
  return newFrag;
}

function AMprocessNodeR(n, linebreaks) {
  var mtch, str, arr, frg, i;
  if (n.childNodes.length == 0) {
   if ((n.nodeType!=8 || linebreaks) &&
    n.parentNode.nodeName!="form" && n.parentNode.nodeName!="FORM" &&
    n.parentNode.nodeName!="textarea" && n.parentNode.nodeName!="TEXTAREA" &&
    n.parentNode.nodeName!="pre" && n.parentNode.nodeName!="PRE") {
    str = n.nodeValue;
    if (!(str == null)) {
      str = str.replace(/\r\n\r\n/g,"\n\n");
      if (doubleblankmathdelimiter) {
        str = str.replace(/\x20\x20\./g," "+AMdelimiter1+".");
        str = str.replace(/\x20\x20,/g," "+AMdelimiter1+",");
        str = str.replace(/\x20\x20/g," "+AMdelimiter1+" ");
      }
      str = str.replace(/\x20+/g," ");
      str = str.replace(/\s*\r\n/g," ");
      mtch = false;
    //  str = str.replace(new RegExp(AMescape2, "g"),
    //          function(st){mtch=true;return "AMescape2"});
      str = str.replace(new RegExp(AMescape1, "g"),
              function(st){mtch=true;return "AMescape1"});
    //  str = str.replace(new RegExp(AMdelimiter2regexp, "g"),AMdelimiter1);
      arr = str.split(AMdelimiter1);
      for (i=0; i<arr.length; i++)
	      arr[i]=arr[i].replace(/AMescape1/g,AMdelimiter1);
//	      arr[i]=arr[i].replace(/AMescape2/g,AMdelimiter2).
  //                    replace(/AMescape1/g,AMdelimiter1);
      if (arr.length>1 || mtch) {
        if (checkForMathML) {
          checkForMathML = false;
          var nd = AMisMathMLavailable();
          AMnoMathML = nd != null;
          if (AMnoMathML && notifyIfNoMathML) 
            if (alertIfNoMathML)
              alert("To view the ASCIIMathML notation use Internet Explorer 6 +\nMathPlayer (free from www.dessci.com)\n\
                or Firefox/Mozilla/Netscape");
            else AMbody.insertBefore(nd,AMbody.childNodes[0]);
        }
        if (!AMnoMathML) {
          frg = AMstrarr2docFrag(arr,n.nodeType==8);
          var len = frg.childNodes.length;
          n.parentNode.replaceChild(frg,n);
          return len-1;
        } else return 0;
      }
    }
   } else return 0;
  } else if (n.nodeName!="math") {
    for (i=0; i<n.childNodes.length; i++)
      i += AMprocessNodeR(n.childNodes[i], linebreaks);
  }
  return 0;
}

function AMprocessNode(n, linebreaks, spanclassAM) {
  var frag,st;
  if (spanclassAM!=null) {;
    frag = document.getElementsByTagName("span")
    for (var i=0;i<frag.length;i++)
      if (frag[i].className == "AM")
        AMprocessNodeR(frag[i],linebreaks);
  } else {
    try {
      st = n.innerHTML;
    } catch(err) {}
    if (st==null || 
        st.indexOf(AMdelimiter1)!=-1)// || st.indexOf(AMdelimiter2)!=-1) 
      AMprocessNodeR(n,linebreaks);
  }
  if (AMisIE) { //needed to match size and font of formula to surrounding text
    frag = document.getElementsByTagName('math');
    for (var i=0;i<frag.length;i++) frag[i].update()
  }
}

var AMbody;
var AMnoMathML = false, AMtranslated = false;

function translate(spanclassAM) {
  if (!AMtranslated) { // run this only once
    AMtranslated = true;
    AMbody = document.getElementsByTagName("body")[0];
    AMprocessNode(AMbody, false, spanclassAM);
  }
}

AMinitSymbols();

if (AMisIE) { // avoid adding MathPlayer info explicitly to each webpage
  document.write("<object id=\"mathplayer\"\
  classid=\"clsid:32F66A20-7614-11D4-BD11-00104BD3F987\"></object>");
  document.write("<?import namespace=\"m\" implementation=\"#mathplayer\"?>");
}

if (typeof(initstack)!='undefined') {
	initstack.push(translate);
} else {
// GO1.1 Generic onload by Brothercake 
// http://www.brothercake.com/
//onload function (replaces the onload="translate()" in the <body> tag)
function generic()
{
  translate();
};
//setup onload function
if(typeof window.addEventListener != 'undefined')
{
  //.. gecko, safari, konqueror and standard
  window.addEventListener('load', generic, false);
}
else if(typeof document.addEventListener != 'undefined')
{
  //.. opera 7
  document.addEventListener('load', generic, false);
}
else if(typeof window.attachEvent != 'undefined')
{
  //.. win/ie
  window.attachEvent('onload', generic);
}
//** remove this condition to degrade older browsers
else
{
  //.. mac/ie5 and anything else that gets this far
  //if there's an existing onload function
  if(typeof window.onload == 'function')
  {
    //store it
    var existing = onload;
    //add new onload handler
    window.onload = function()
    {
      //call existing onload function
      existing();
      //call generic onload function
      generic();
    };
  }
  else
  {
    //setup onload function
    window.onload = generic;
  }
}
}

if (checkForMathML) {
          checkForMathML = false;
          var nd = AMisMathMLavailable();
          AMnoMathML = nd != null;
}

var AMtoMQ=function(){var i,v,c,g,$,s=".",m=0,x=1,y=2,w=3,h=4,_=5,f=6,b=7,d=8,q=9,A=10,t={input:"sqrt",tag:"msqrt",output:"sqrt",tex:null,ttype:x},e={input:"root",tag:"mroot",output:"root",tex:null,ttype:y},u={input:"frac",tag:"mfrac",output:"/",tex:null,ttype:y},p={input:"/",tag:"mfrac",output:"/",tex:null,ttype:w},n={input:"stackrel",tag:"mover",output:"stackrel",tex:null,ttype:y},l={input:"_",tag:"msub",output:"_",tex:null,ttype:w},a={input:"^",tag:"msup",output:"^",tex:null,ttype:w},o={input:"text",tag:"mtext",output:"text",tex:null,ttype:A},r={input:"mbox",tag:"mtext",output:"mbox",tex:null,ttype:A},O={input:'"',tag:"mtext",output:"mbox",tex:null,ttype:A},E=[{input:"alpha",tag:"mi",output:"α",tex:null,ttype:m},{input:"beta",tag:"mi",output:"β",tex:null,ttype:m},{input:"chi",tag:"mi",output:"χ",tex:null,ttype:m},{input:"delta",tag:"mi",output:"δ",tex:null,ttype:m},{input:"Delta",tag:"mo",output:"Δ",tex:null,ttype:m},{input:"epsi",tag:"mi",output:"ε",tex:"epsilon",ttype:m},{input:"varepsilon",tag:"mi",output:"ɛ",tex:null,ttype:m},{input:"eta",tag:"mi",output:"η",tex:null,ttype:m},{input:"gamma",tag:"mi",output:"γ",tex:null,ttype:m},{input:"Gamma",tag:"mo",output:"Γ",tex:null,ttype:m},{input:"iota",tag:"mi",output:"ι",tex:null,ttype:m},{input:"kappa",tag:"mi",output:"κ",tex:null,ttype:m},{input:"lambda",tag:"mi",output:"λ",tex:null,ttype:m},{input:"Lambda",tag:"mo",output:"Λ",tex:null,ttype:m},{input:"mu",tag:"mi",output:"μ",tex:null,ttype:m},{input:"nu",tag:"mi",output:"ν",tex:null,ttype:m},{input:"omega",tag:"mi",output:"ω",tex:null,ttype:m},{input:"Omega",tag:"mo",output:"Ω",tex:null,ttype:m},{input:"phi",tag:"mi",output:"φ",tex:null,ttype:m},{input:"varphi",tag:"mi",output:"ϕ",tex:null,ttype:m},{input:"Phi",tag:"mo",output:"Φ",tex:null,ttype:m},{input:"pi",tag:"mi",output:"π",tex:null,ttype:m},{input:"Pi",tag:"mo",output:"Π",tex:null,ttype:m},{input:"psi",tag:"mi",output:"ψ",tex:null,ttype:m},{input:"Psi",tag:"mi",output:"Ψ",tex:null,ttype:m},{input:"rho",tag:"mi",output:"ρ",tex:null,ttype:m},{input:"sigma",tag:"mi",output:"σ",tex:null,ttype:m},{input:"Sigma",tag:"mo",output:"Σ",tex:null,ttype:m},{input:"tau",tag:"mi",output:"τ",tex:null,ttype:m},{input:"theta",tag:"mi",output:"θ",tex:null,ttype:m},{input:"vartheta",tag:"mi",output:"ϑ",tex:null,ttype:m},{input:"Theta",tag:"mo",output:"Θ",tex:null,ttype:m},{input:"upsilon",tag:"mi",output:"υ",tex:null,ttype:m},{input:"xi",tag:"mi",output:"ξ",tex:null,ttype:m},{input:"Xi",tag:"mo",output:"Ξ",tex:null,ttype:m},{input:"zeta",tag:"mi",output:"ζ",tex:null,ttype:m},{input:"*",tag:"mo",output:"⋅",tex:"cdot",ttype:m},{input:"-:",tag:"mo",output:"÷",tex:"div",ttype:m},{input:"sum",tag:"mo",output:"∑",tex:null,ttype:b},{input:"^^",tag:"mo",output:"∧",tex:"wedge",ttype:m},{input:"xor",tag:"mo",output:"⊕",tex:"oplus",ttype:m},{input:"oplus",tag:"mo",output:"⊕",tex:"oplus",ttype:m},{input:"ominus",tag:"mo",output:"⊖",tex:"ominus",ttype:m},{input:"neg",tag:"mo",output:"¬",tex:"neg",ttype:m},{input:"vv",tag:"mo",output:"∨",tex:"vee",ttype:m},{input:"nn",tag:"mo",output:"∩",tex:"cap",ttype:m},{input:"uu",tag:"mo",output:"∪",tex:"cup",ttype:m},{input:"xx",tex:"times",ttype:m},{input:"!=",tag:"mo",output:"≠",tex:"ne",ttype:m},{input:"<=",tag:"mo",output:"≤",tex:"le",ttype:m},{input:">=",tag:"mo",output:"≥",tex:"ge",ttype:m},{input:"geq",tag:"mo",output:"≥",tex:null,ttype:m},{input:"in",tag:"mo",output:"∈",tex:null,ttype:m},{input:"sub",tag:"mo",output:"⊂",tex:"subset",ttype:m},{input:"sube",tag:"mo",output:"⊆",tex:"subseteq",ttype:m},{input:"(",tag:"mo",output:"(",tex:null,ttype:h},{input:")",tag:"mo",output:")",tex:null,ttype:_},{input:"[",tag:"mo",output:"[",tex:null,ttype:h},{input:"]",tag:"mo",output:"]",tex:null,ttype:_},{input:"{",tag:"mo",output:"{",tex:"{",ttype:h,notexcopy:!0},{input:"}",tag:"mo",output:"}",tex:"}",ttype:_,notexcopy:!0},{input:"|",tag:"mo",output:"|",tex:null,ttype:q},{input:"(:",tag:"mo",output:"〈",tex:"langle",ttype:h},{input:":)",tag:"mo",output:"〉",tex:"rangle",ttype:_},{input:"<<",tag:"mo",output:"〈",tex:"langle",ttype:h},{input:">>",tag:"mo",output:"〉",tex:"rangle",ttype:_},{input:"{:",tag:"mo",output:"{:",tex:null,ttype:h,invisible:!0},{input:":}",tag:"mo",output:":}",tex:null,ttype:_,invisible:!0},{input:"int",tag:"mo",output:"∫",tex:null,ttype:m},{input:"+-",tag:"mo",output:"±",tex:"pm",ttype:m},{input:"O/",tag:"mo",output:"∅",tex:"emptyset",ttype:m},{input:"oo",tag:"mo",output:"∞",tex:"infty",ttype:m},{input:"rarr",tag:"mo",output:"→",tex:"rightarrow",ttype:m},{input:"->",tag:"mo",output:"→",tex:"to",ttype:m},{input:"=>",tag:"mo",output:"⇒",tex:"implies",ttype:m},{input:"<=>",tag:"mo",output:"⇔",tex:"iff",ttype:m},{input:"rightleftharpoons",tag:"mo",output:"⇌",tex:null,ttype:m},{input:"RR",tag:"mo",output:"ℝ",tex:"mathbb{R}",ttype:m,notexcopy:!0},{input:"f",tag:"mi",output:"f",tex:null,ttype:x,func:!0,val:!0},{input:"degree",tag:"mo",tex:null,ttype:m},{input:"degrees",output:"degree",ttype:d},{input:"sin",tag:"mo",output:"sin",tex:null,ttype:x,func:!0},{input:"cos",tag:"mo",output:"cos",tex:null,ttype:x,func:!0},{input:"tan",tag:"mo",output:"tan",tex:null,ttype:x,func:!0},{input:"arcsin",tag:"mo",output:"arcsin",tex:null,ttype:x,func:!0},{input:"arccos",tag:"mo",output:"arccos",tex:null,ttype:x,func:!0},{input:"arctan",tag:"mo",output:"arctan",tex:null,ttype:x,func:!0},{input:"arcsec",tag:"mo",output:"arcsec",tex:null,ttype:x,func:!0},{input:"arccsc",tag:"mo",output:"arccsc",tex:null,ttype:x,func:!0},{input:"arccot",tag:"mo",output:"arccot",tex:null,ttype:x,func:!0},{input:"sinh",tag:"mo",output:"sinh",tex:null,ttype:x,func:!0},{input:"cosh",tag:"mo",output:"cosh",tex:null,ttype:x,func:!0},{input:"tanh",tag:"mo",output:"tanh",tex:null,ttype:x,func:!0},{input:"cot",tag:"mo",output:"cot",tex:null,ttype:x,func:!0},{input:"coth",tag:"mo",output:"coth",tex:null,ttype:x,func:!0},{input:"sech",tag:"mo",output:"sech",tex:null,ttype:x,func:!0},{input:"csch",tag:"mo",output:"csch",tex:null,ttype:x,func:!0},{input:"sec",tag:"mo",output:"sec",tex:null,ttype:x,func:!0},{input:"csc",tag:"mo",output:"csc",tex:null,ttype:x,func:!0},{input:"log",tag:"mo",output:"log",tex:null,ttype:x,func:!0},{input:"ln",tag:"mo",output:"ln",tex:null,ttype:x,func:!0},{input:"abs",tag:"mo",output:"abs",tex:null,ttype:x},{input:"Sin",tag:"mo",output:"sin",tex:null,ttype:x,func:!0},{input:"Cos",tag:"mo",output:"cos",tex:null,ttype:x,func:!0},{input:"Tan",tag:"mo",output:"tan",tex:null,ttype:x,func:!0},{input:"Arcsin",tag:"mo",output:"arcsin",tex:null,ttype:x,func:!0},{input:"Arccos",tag:"mo",output:"arccos",tex:null,ttype:x,func:!0},{input:"Arctan",tag:"mo",output:"arctan",tex:null,ttype:x,func:!0},{input:"Sinh",tag:"mo",output:"sinh",tex:null,ttype:x,func:!0},{input:"Sosh",tag:"mo",output:"cosh",tex:null,ttype:x,func:!0},{input:"Tanh",tag:"mo",output:"tanh",tex:null,ttype:x,func:!0},{input:"Cot",tag:"mo",output:"cot",tex:null,ttype:x,func:!0},{input:"Sec",tag:"mo",output:"sec",tex:null,ttype:x,func:!0},{input:"Csc",tag:"mo",output:"csc",tex:null,ttype:x,func:!0},{input:"Log",tag:"mo",output:"log",tex:null,ttype:x,func:!0},{input:"Ln",tag:"mo",output:"ln",tex:null,ttype:x,func:!0},{input:"Abs",tag:"mo",output:"abs",tex:null,ttype:x,func:!0},t,e,u,p,n,l,a,{input:"Sqrt",tag:"msqrt",output:"sqrt",tex:null,ttype:x},{input:"hat",tag:"mover",output:"^",tex:null,ttype:x,acc:!0},{input:"bar",tag:"mover",output:"¯",tex:"overline",ttype:x,acc:!0},{input:"vec",tag:"mover",output:"→",tex:null,ttype:x,acc:!0},o,r,O];function R(t,e){return t.input>e.input?1:-1}function S(t,e){var u,p;for(u="\\"==t.charAt(e)&&"\\"!=t.charAt(e+1)&&" "!=t.charAt(e+1)?t.slice(e+1):t.slice(e),p=0;p<u.length&&u.charCodeAt(p)<=32;p+=1);return u.slice(p)}function z(t,e,u){var p,n,l;if(0==u){for(u=-1,p=t.length;u+1<p;)t[n=u+p>>1]<e?u=n:p=n;return p}for(l=u;l<t.length&&t[l]<e;l++);return l}function D(t){var e,u,p,n,l,a=0,o="",r=!0;for(n=1;n<=t.length&&r;n++)u=t.slice(0,n),(a=z(i,u,a))<i.length&&t.slice(0,i[a].length)==i[a]&&(n=(o=i[e=a]).length),r=a<i.length&&t.slice(0,i[a].length)>=i[a];if(c=g,""!=o)return g=E[e].ttype,E[e];for(g=m,a=1,u=t.slice(0,1),l=!0;"0"<=u&&u<="9"&&a<=t.length;)u=t.slice(a,a+1),a++;if(u==s&&(u=t.slice(a,a+1),a++,"0"<=u&&u<="9"))for(l=!1;"0"<=u&&u<="9"&&a<=t.length;)u=t.slice(a,a+1),a++;return p=l&&1<a||2<a?(u=t.slice(0,a-1),"mn"):(a=2,((u=t.slice(0,1))<"A"||"Z"<u)&&(u<"a"||"z"<u)?"mo":"mi"),"-"==u&&" "!==t.charAt(1)&&c==w?(g=w,{input:u,tag:p,output:u,ttype:x,func:!0,val:!0}):{input:u,tag:p,output:u,ttype:m,val:!0}}function I(t){var e,u;return"{"==t.charAt(0)&&"}"==t.charAt(t.length-1)&&(u=0,"\\left"==(e=t.substr(1,5))?"("==(e=t.charAt(6))||"["==e||"{"==e?u=7:"\\lbrace"==(e=t.substr(6,7))&&(u=13):"("!=(e=t.charAt(1))&&"["!=e||(u=2),0<u&&("\\right)}"==(e=t.substr(t.length-8))||"\\right]}"==e||"\\right.}"==e?t=(t="{"+t.substr(u)).substr(0,t.length-8)+"}":"\\rbrace}"==e&&(t=(t="{"+t.substr(u)).substr(0,t.length-14)+"}"))),t}function N(t){return"boolean"==typeof t.val&&t.val?pre="":pre="\\",null==t.tex?pre+t.input:pre+t.tex}function Z(t){return null==t.tex?t.input:"\\"+t.tex}function B(t){var e,u,p,n,l,a,o,r="";if(null==(e=D(t=S(t,0)))||e.ttype==_&&0<v)return[null,t];switch(e.ttype==d&&(e=D(t=e.output+S(t,e.input.length))),e.ttype){case b:case m:return t=S(t,e.input.length),"\\"==(l=N(e)).charAt(0)||"mo"==e.tag?[l,t]:["{"+l+"}",t];case h:return v++,u=L(t=S(t,e.input.length),!0),v--,u[0].match(/bmatrix/)?[u[0].substring(0,u[0].length-7),u[1]]:("\\right"==u[a=0].substr(0,6)&&(")"==(n=u[0].charAt(6))||"]"==n||"}"==n?a=6:"."==n?a=7:"\\rbrace"==(n=u[0].substr(6,7))&&(a=13)),[0<a?(u[0]=u[0].substr(a),"boolean"==typeof e.invisible&&e.invisible?"{"+u[0]+"}":"{"+Z(e)+u[0]+"}"):"boolean"==typeof e.invisible&&e.invisible?"{"+u[0]+"}":"{\\left"+Z(e)+u[0]+"}",u[1]]);case A:return e!=O&&(t=S(t,e.input.length)),-1==(p="{"==t.charAt(0)?t.indexOf("}"):"("==t.charAt(0)?t.indexOf(")"):"["==t.charAt(0)?t.indexOf("]"):e==O?t.slice(1).indexOf('"')+1:0)&&(p=t.length),[r+="\\text{"+(n=t.slice(1,p))+"}",t=S(t,p+1)];case x:return null==(u=B(t=S(t,e.input.length)))[0]?["{"+N(e)+"}",t]:"boolean"==typeof e.func&&e.func?"^"==(n=t.charAt(0))||"_"==n||"/"==n||"|"==n||","==n||1==e.input.length&&e.input.match(/\w/)&&"("!=n?["{"+N(e)+"}",t]:["{"+N(e)+"{"+u[0]+"}}",u[1]]:(u[0]=I(u[0]),"sqrt"==e.input?["\\sqrt{"+u[0]+"}",u[1]]:"abs"==e.input?["\\left|{"+u[0]+"}\\right|",u[1]]:"cancel"==e.input?["\\cancel{"+u[0]+"}",u[1]]:void 0!==e.rewriteleftright?["{\\left"+e.rewriteleftright[0]+u[0]+"\\right"+e.rewriteleftright[1]+"}",u[1]]:"boolean"==typeof e.acc&&e.acc?[N(e)+"{"+u[0]+"}",u[1]]:["{"+N(e)+"{"+u[0]+"}}",u[1]]);case y:return null==(u=B(t=S(t,e.input.length)))[0]?["{"+N(e)+"}",t]:(u[0]=I(u[0]),null==(o=B(u[1]))[0]?["{"+N(e)+"}",t]:(o[0]=I(o[0]),[r="color"==e.input?"{\\color{"+u[0].replace(/[\{\}]/g,"")+"}"+o[0]+"}":"root"==e.input?"{\\sqrt["+u[0]+"]{"+o[0]+"}}":"{"+N(e)+"{"+u[0]+"}{"+o[0]+"}}",o[1]]));case w:return t=S(t,e.input.length),[e.output,t];case f:return t=S(t,e.input.length),["{\\quad\\text{"+e.input+"}\\quad}",t];case q:return v++,u=L(t=S(t,e.input.length),!1),v--,n="","|"==(n=u[0].charAt(u[0].length-1))?["{\\left|"+u[0]+"}",u[1]]:["{\\mid}",t];default:return t=S(t,e.input.length),["{"+N(e)+"}",t]}}function C(t){var e,u,p,n,l,a;return u=D(t=S(t,0)),n=(l=B(t))[0],(e=D(t=l[1])).ttype==w&&"/"!=e.input&&(null==(l=B(t=S(t,e.input.length)))[0]?l[0]="{}":l[0]=I(l[0]),t=l[1],"_"==e.input?"^"==(p=D(t)).input?((a=B(t=S(t,p.input.length)))[0]=I(a[0]),t=a[1],n="{"+n,n+="_{"+l[0]+"}",n+="^{"+a[0]+"}",n+="}"):n+="_{"+l[0]+"}":n=n+"^{"+l[0]+"}",void 0!==u.func&&u.func&&(p=D(t)).ttype!=w&&p.ttype!=_&&(n="{"+n+(l=C(t))[0]+"}",t=l[1])),[n,t]}function L(t,e){for(var u,p,n,l,a,o,r,i,c,g,s,m,x,y,h,f,b,d="",A=!1;p=(n=C(t=S(t,0)))[0],(u=D(t=n[1])).ttype==w&&"/"==u.input?(null==(n=C(t=S(t,u.input.length)))[0]?n[0]="{}":n[0]=I(n[0]),t=n[1],p="\\frac{"+(p=I(p))+"}",d+=p+="{"+n[0]+"}",u=D(t)):null!=p&&(d+=p),(u.ttype!=_&&(u.ttype!=q||e)||0==v)&&null!=u&&""!=u.output;);if(u.ttype==_||u.ttype==q){if(a=d.length,$&&2<a&&"{"==d.charAt(0)&&0<d.indexOf(",")&&(")"==(o=d.charAt(a-2))||"]"==o)&&("("==(r=d.charAt(6))&&")"==o&&"}"!=u.output||"["==r&&"]"==o)){for(i="\\begin{bmatrix}",(c=new Array).push(0),g=!0,(m=[])[s=0]=[0],y=x=0,l=1;l<a-1;l++)d.charAt(l)==r&&s++,d.charAt(l)==o&&0==--s&&","==d.charAt(l+2)&&"{"==d.charAt(l+3)&&(c.push(l+2),m[x=l+2]=[l+2]),"["!=d.charAt(l)&&"("!=d.charAt(l)&&"{"!=d.charAt(l)||y++,"]"!=d.charAt(l)&&")"!=d.charAt(l)&&"}"!=d.charAt(l)||y--,","==d.charAt(l)&&1==y&&m[x].push(l);if(c.push(a),h=-1,0==s&&0<c.length)for(l=0;l<c.length-1;l++){if(0<l&&(i+="\\\\"),0==l)if(1==m[c[l]].length)f=[d.substr(c[l]+7,c[l+1]-c[l]-15)];else{for(f=[d.substring(c[l]+7,m[c[l]][1])],b=2;b<m[c[l]].length;b++)f.push(d.substring(m[c[l]][b-1]+1,m[c[l]][b]));f.push(d.substring(m[c[l]][m[c[l]].length-1]+1,c[l+1]-8))}else if(1==m[c[l]].length)f=[d.substr(c[l]+8,c[l+1]-c[l]-16)];else{for(f=[d.substring(c[l]+8,m[c[l]][1])],b=2;b<m[c[l]].length;b++)f.push(d.substring(m[c[l]][b-1]+1,m[c[l]][b]));f.push(d.substring(m[c[l]][m[c[l]].length-1]+1,c[l+1]-8))}0<h&&f.length!=h?g=!1:-1==h&&(h=f.length),i+=f.join("&")}i+="\\end{bmatrix}",g&&(d=i)}t=S(t,u.input.length),A=("boolean"==typeof u.invisible&&u.invisible||(d+=p="\\right"+Z(u)),!0)}return 0<v&&!A&&(d+="\\right)"),[d,t]}return i=[],$=!0,function(){var t,e=[];for(t=0;t<E.length;t++)!E[t].tex||"boolean"==typeof E[t].notexcopy&&E[t].notexcopy||(e[e.length]={input:E[t].tex,tag:E[t].tag,output:E[t].output,ttype:E[t].ttype,acc:E[t].acc||!1});for((E=E.concat(e)).sort(R),t=0;t<E.length;t++)i[t]=E[t].input}(),function(t,e,u){return!(v=0)===u&&($=!1),t=t.replace(/(&nbsp;|\u00a0|&#160;|{::})/g,""),document.getElementById(e)&&document.getElementById(e).getAttribute("data-mq").match(/(ntuple|string)/)&&(t=t.replace(/<([^<].*?,.*?[^>])>/g,"<<$1>>")),t=(t=(t=(t=(t=(t=t.replace(/&gt;/g,">")).replace(/&lt;/g,"<")).replace(/\s*\bor\b\s*/g,'" or "')).replace(/all\s+real\s+numbers/g,'"all real numbers"')).replace(/(\)|\])\s*u\s*(\(|\[)/g,"$1U$2")).replace(/\bDNE\b/gi,'"DNE"'),document.getElementById(e)&&document.getElementById(e).getAttribute("data-mq").match(/interval/)&&(t=t.replace(/\bU\b/g,"cup")),null==t.match(/\S/)?"":L(t.replace(/^\s+/g,""),!1)[0]}}();function MQtoAM(t,e){var u,p,n,l,a,o;if(t=(t=t.replace(/\\:/g," ")).replace(/\\operatorname{(\w+)}/g," $1"),e)t=t.replace(/\\Re/g,"RR");else{for(;-1!=(o=t.lastIndexOf("\\left|"));)t=-1!=(p=t.indexOf("\\right|",o+1))?(n=t.substring(0,o).match(/(arcsinh|arccosh|arctanh|arcsech|arccsch|arccoth|arcsin|arccos|arctan|arcsec|arccsc|arccot|sinh|cosh|tanh|sech|csch|coth|ln|log|exp|sin|cos|tan|sec|csc|cot)(\^\d+)?$/),(t=t.substring(0,p)+")"+(n?")":"")+t.substring(p+7)).substring(0,o)+(n?"(":"")+"abs("+t.substring(o+6)):t.substring(0,o)+"|"+t.substring(o+6);t=(t=(t=(t=(t=t.replace(/\\text{\s*or\s*}/g," or ")).replace(/\\text{all\s+real\s+numbers}/g,"all real numbers")).replace(/\\text{DNE}/g,"DNE")).replace(/\\varnothing/g,"DNE")).replace(/\\Re/g,"all real numbers")}for(t=(t=(t=(t=(t=(t=(t=(t=(t=(t=(t=(t=(t=(t=(t=(t=(t=(t=(t=(t=(t=(t=(t=(t=(t=(t=t.replace(/\\begin{.?matrix}(.*?)\\end{.?matrix}/g,function(t,e){return"[("+e.replace(/\\\\/g,"),(").replace(/&/g,",")+")]"})).replace(/\\le(?=(\b|\d))/g,"<=")).replace(/\\ge(?=(\b|\d))/g,">=")).replace(/\\ne(?=(\b|\d))/g,"!=")).replace(/\+\-/g,"+ -")).replace(/\\pm/g,"+-")).replace(/\\approx/g,"~~")).replace(/(\\arrow|\\rightarrow)/g,"rarr")).replace(/\\rightleftharpoons/g,"rightleftharpoons")).replace(/\\sim/g,"~")).replace(/\\vee/g,"vv").replace(/\\wedge/g," ^^ ")).replace(/\\Rightarrow/g,"=>").replace(/\\Leftrightarrow/g,"<=>")).replace(/\\times/g,"xx")).replace(/\\left\\{/g,"lbrace").replace(/\\right\\}/g,"rbrace")).replace(/\\left/g,"")).replace(/\\right/g,"")).replace(/\\langle/g,"<<")).replace(/\\rangle/g,">>")).replace(/\\cdot/g,"*")).replace(/\\infty/g,"oo")).replace(/\\nthroot/g,"root")).replace(/\\mid/g,"|")).replace(/\\/g,"")).replace(/sqrt\[(.*?)\]/g,"root($1)")).replace(/(\d)frac/g,"$1 frac")).replace(/degree/g,"degree ");-1!=(o=t.indexOf("frac{"));){for(u=1,l=o+5;0<u&&l<t.length-1;)l++,"{"==(a=t.charAt(l))?u++:"}"==a&&u--;t=0==u?t.substring(0,o)+"("+t.substring(o+5,l)+")/"+t.substring(l+1):t.substring(0,o)+t.substring(o+4)}return(t=(t=(t=(t=(t=(t=(t=(t=(t=(t=(t=(t=(t=(t=(t=(t=(t=(t=(t=(t=(t=t.replace(/_(\w)(\w)/g,"_$1 $2")).replace(/(\^|_)([+\-])([^\^])/g,"$1$2 $3")).replace(/\^(\w)(\w)/g,"^$1 $2")).replace(/_{([\d\.]+)}\^/g,"_$1^")).replace(/_{([\d\.]+)}([^\^])/g,"_$1 $2")).replace(/_{([\d\.]+)}$/g,"_$1")).replace(/_{(\w+)}$/g,"_($1)")).replace(/{/g,"(").replace(/}/g,")")).replace(/lbrace/g,"{").replace(/rbrace/g,"}")).replace(/\(([\d\.]+)\)\/\(([\d\.]+)\)/g,"$1/$2 ")).replace(/\/\(([\d\.]+)\)/g,"/$1")).replace(/\(([\d\.]+)\)\//g,"$1/")).replace(/\/\(([a-zA-Z])\)/g,"/$1")).replace(/\(([a-zA-Z])\)\//g,"$1/")).replace(/\^\((-?[\d\.]+)\)(\d)/g,"^$1 $2")).replace(/\^\(-1\)/g,"^-1")).replace(/\^\((-?[\d\.]+)\)/g,"^$1")).replace(/\/\(([a-zA-Z])\^([\d\.]+)\)/g,"/$1^$2 ")).replace(/\(([a-zA-Z])\^([\d\.]+)\)\//g,"$1^$2/")).replace(/text\(([^)]*)\)/g,"$1")).replace(/\(\s*(\w)/g,"($1").replace(/(\w)\s*\)/g,"$1)")).replace(/^\s+|\s+$/g,"")}

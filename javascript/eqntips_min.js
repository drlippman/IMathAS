var ehcurel=null,ehclosetimer=0,ehddclosetimer=0,curehdd=null,eecurel=null;function showeh(e){var t,l,d;null==eecurel&&(unhideeh(0),t=document.getElementById(e),l=document.getElementById("eh"),e!=ehcurel?(ehcurel=e,d=jQuery(t).offset(),l.style.display="block",l.style.left=d.left+"px",l.style.top=d.top+t.offsetHeight+"px",document.getElementById("ehdd").style.display="none"):(l.style.display="none",ehcurel=null),(e.match(/mqinput/)?MQ(t):t).focus())}function reshrinkeh(e){null==eecurel&&e==ehcurel&&(document.getElementById("ehdd").style.display="block",document.getElementById("eh").style.display="none",ehcurel=null,curehdd=e,unhideeh(0))}function unhideeh(e){ehcancelclosetimer()}function hideeh(e){null==ehcurel?ehddclosetimer=window.setTimeout(function(){curehdd=null,document.getElementById("ehdd").style.display="none"},250):ehclosetimer=window.setTimeout(reallyhideeh,250)}function reallyhideeh(){document.getElementById("eh").style.display="none",ehcurel=null}function ehcancelclosetimer(){ehclosetimer&&(window.clearTimeout(ehclosetimer),ehclosetimer=null)}function hideAllEhTips(){ehclosetimer&&(window.clearTimeout(ehclosetimer),ehclosetimer=null),ehddclosetimer&&(window.clearTimeout(ehddclosetimer),ehddclosetimer=null),curehdd=null,document.getElementById("ehdd").style.display="none",document.getElementById("eh").style.display="none"}function showehdd(e,t,l){var d,n,o;null!=eecurel&&eecurel==e||null!=document.getElementById("tips"+l)&&(ehddclosetimer&&e!=curehdd&&(window.clearTimeout(ehddclosetimer),ehddclosetimer=null),e!=ehcurel&&(d=document.getElementById("ehdd"),n=document.getElementById(e),o=jQuery(n).offset(),document.getElementById("ehddtext").innerHTML=t,document.getElementById("eh").innerHTML=document.getElementById("tips"+l).innerHTML,d.style.display="block",d.style.left=o.left+"px",d.style.top=o.top+n.offsetHeight+"px"),curehdd=e)}function updateehpos(){var e,t,l,d;(curehdd||ehcurel)&&(e=document.getElementById("eh"),t=document.getElementById("ehdd"),l=document.getElementById(curehdd||ehcurel),d=jQuery(l).offset(),e.style.left=d.left+"px",e.style.top=d.top+l.offsetHeight+"px",t.style.left=d.left+"px",t.style.top=d.top+l.offsetHeight+"px")}
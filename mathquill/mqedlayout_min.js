var MQ,myMQeditor=function(m){var e={tabs:[{p:"Basic",enabled:!0,tabcontent:[{flow:"row",s:2,contents:[{l:"\\left(\\right)",c:"i",w:"()",pr:'<span class="mq-non-leaf"><span class="mq-scaled mq-paren" style="transform: scale(1, 1.2);">(</span><span class="mq-non-leaf mq-empty"></span><span class="mq-scaled mq-paren" style="transform: scale(1, 1.2);">)</span></span>'},{l:"x^{}",c:"t",w:"^",nb:1,pr:'<var>x</var><span class="mq-supsub mq-non-leaf mq-sup-only"><span class="mq-sup mq-empty"></span></span>'},{l:"\\pi",nb:1,pr:'<span class="mq-nonSymbola">π</span>'},{l:"\\sqrt{}",c:"c",w:"sqrt",nb:1,pr:'<span class="mq-non-leaf"><span class="mq-scaled mq-sqrt-prefix" style="transform: scale(1, 0.955556);">√</span><span class="mq-non-leaf mq-sqrt-stem mq-empty"></span></span>'},{l:"x_{}",c:"t",w:"_",nb:1,pr:'<var>x</var><span class="mq-supsub mq-non-leaf"><span class="mq-sub mq-empty"></span></span>'},{l:"\\sqrt[n]{}",c:"c",w:"nthroot",nb:1,pr:'<sup class="mq-nthroot mq-non-leaf"><var>n</var></sup><span class="mq-scaled"><span class="mq-sqrt-prefix mq-scaled" style="transform: scale(1, 0.955556);">√</span><span class="mq-sqrt-stem mq-non-leaf mq-empty"></span></span>'},{p:"DNE",sm:2},{l:"\\left|\\right|",c:"i",w:"||"}]},{s:.1},{flow:"row",s:4,contents:[{b:"7"},{b:"8"},{b:"9"},{l:"\\frac{}{}",c:"t",w:"/",pr:'<span class="mq-fraction mq-non-leaf"><span class="mq-numerator mq-empty"></span><span class="mq-denominator mq-empty"></span><span style="display:inline-block;width:0">&#8203;</span></span>'},{b:"4"},{b:"5"},{b:"6"},{b:"*"},{b:"1"},{b:"2"},{b:"3"},{b:"-"},{b:"0"},{b:"."},{s:1},{b:"+"}]},{s:.1},{flow:"row",s:2,contents:[{s:.5},{b:"&uarr;",c:"k",w:"Up"},{s:.5},{b:"&larr;",c:"k",w:"Left"},{b:"&rarr;",c:"k",w:"Right"},{s:.5},{b:"&darr;",c:"k",w:"Down"},{s:.5},{b:"&#x232B;",s:2,c:"k",w:"Backspace"}]}]},{p:"Funcs",enabled:!1,tabcontent:[{flow:"row",s:4,contents:[{l:"\\log",c:"f",op:1},{l:"\\ln",c:"f",op:1},{l:"\\log_{}",c:"f",pr:'<var class="mq-operator-name">log</var><span class="mq-supsub mq-non-leaf"><span class="mq-sub mq-empty"></span></span>'},{l:"e^{}",c:"t",w:"e^",pr:'<var>e</var><span class="mq-supsub mq-non-leaf mq-sup-only"><span class="mq-sup mq-empty"></span></span>'}]}]},{p:"Trig",enabled:!1,tabcontent:[{flow:"row",s:6,contents:[{l:"\\sin",c:"f",op:1},{l:"\\cos",c:"f",op:1},{l:"\\tan",c:"f",op:1},{l:"\\sec",c:"f",op:1},{l:"\\csc",c:"f",op:1},{l:"\\cot",c:"f",op:1},{l:"\\sin^{-1}",c:"f",pr:'<var class="mq-operator-name">sin</var><span class="mq-supsub mq-non-leaf mq-sup-only"><span class="mq-sup">−1</span></span>'},{l:"\\cos^{-1}",c:"f",pr:'<var class="mq-operator-name">cos</var><span class="mq-supsub mq-non-leaf mq-sup-only"><span class="mq-sup">−1</span></span>'},{l:"\\tan^{-1}",c:"f",pr:'<var class="mq-operator-name">tan</var><span class="mq-supsub mq-non-leaf mq-sup-only"><span class="mq-sup">−1</span></span>'},{l:"\\sinh",c:"f",op:1},{l:"\\cosh",c:"f",op:1},{l:"\\tanh",c:"f",op:1},{l:"\\pi",nb:1,pr:'<span class="mq-nonSymbola">π</span>'},{s:1},{s:4}]}]},{p:"Inequality",enabled:!1,tabcontent:[{flow:"row",s:4,contents:[{l:"\\lt",pr:'<span class="mq-binary-operator">&lt;</span>'},{l:"\\gt",pr:'<span class="mq-binary-operator">&gt;</span>'},{l:"\\le",pr:'<span class="mq-binary-operator">&le;</span>'},{l:"\\ge",pr:'<span class="mq-binary-operator">&ge;</span>'},{p:"or",c:"w",w:"\\text{ or }"},{p:"DNE",sm:2},{p:"all reals",c:"w",w:"\\text{all reals}",s:2}]}]},{p:"Interval",enabled:!1,tabcontent:[{flow:"row",s:4,contents:[{l:"\\left(\\right)",c:"i",w:"()"},{l:"\\left[\\right]",c:"i",w:"[]"},{l:"\\left(\\right]",c:"i",w:"(]"},{l:"\\left[\\right)",c:"i",w:"[)"},{l:"\\infty",pr:"<span>∞</span>"},{l:"-\\infty",c:"w",w:"-\\infty",pr:"<span>−∞</span>"},{l:"\\cup",pr:'<span class="mq-binary-operator">∪</span>'},{s:1}]}]},{p:"Matrix",sm:1,enabled:!1,tabcontent:[{flow:"row",s:4,contents:[{p:"2×2",c:"w",w:"\\begin{bmatrix}&\\\\&\\end{bmatrix}"},{p:"2×3",c:"w",w:"\\begin{bmatrix}&&\\\\&&\\end{bmatrix}"},{p:"3×3",c:"w",w:"\\begin{bmatrix}&&\\\\&&\\\\&&\\end{bmatrix}"},{p:"3×4",c:"w",w:"\\begin{bmatrix}&&&\\\\&&&\\\\&&&\\end{bmatrix}"},{p:"+Col",c:"m",w:"addColumn"},{p:"-Col",c:"m",w:"deleteColumn"},{p:"+Row",c:"m",w:"addRow"},{p:"-Row",c:"m",w:"deleteRow"}]}]},{p:"=<%",enabled:!0,tabcontent:[{flow:"row",s:5,contents:[{p:"[",s:.5},{p:"]",s:.5},{p:"{",s:.5},{p:"}",s:.5},{p:"(",s:.5},{p:")",s:.5},{l:"\\left\\langle\\right\\rangle",c:"i",w:["\\left\\langle","\\right\\rangle"]},{l:"\\left|\\right|",c:"i",w:"||"},{p:"="},{l:"\\lt",pr:'<span class="mq-binary-operator">&lt;</span>'},{l:"\\gt",pr:'<span class="mq-binary-operator">&gt;</span>'},{l:"\\le",pr:'<span class="mq-binary-operator">&le;</span>'},{l:"\\ge",pr:'<span class="mq-binary-operator">&ge;</span>'},{p:"%"},{p:","},{l:"\\infty",pr:"<span>∞</span>"},{p:"!"},{p:"?"}]}]},{p:"ABC",enabled:!0,tabcontent:[{flow:"row",s:10,contents:[{p:"q"},{p:"w"},{p:"e"},{p:"r"},{p:"t"},{p:"y"},{p:"u"},{p:"i"},{p:"o"},{p:"p"},{s:.5},{p:"a"},{p:"s"},{p:"d"},{p:"f"},{p:"g"},{p:"h"},{p:"j"},{p:"k"},{p:"l"},{s:.5},{b:"&#8679;",c:"shift",s:1.5},{p:"z"},{p:"x"},{p:"c"},{p:"v"},{p:"b"},{p:"n"},{p:"m"},{b:"&#x232B;",c:"k",w:"Backspace",s:1.5},{p:"%"},{p:","},{p:"Space",s:5,c:"t",w:" "},{p:"."},{b:"&larr;",c:"k",w:"Left"},{b:"&rarr;",c:"k",w:"Right"}]}]}]},l={tabs:[{p:"Basic",enabled:!0,tabcontent:[{flow:"row",s:5,contents:[{l:"\\frac{}{}",c:"t",w:"/",pr:'<span class="mq-fraction mq-non-leaf"><span class="mq-numerator mq-empty"></span><span class="mq-denominator mq-empty"></span><span style="display:inline-block;width:0">&#8203;</span></span>'},{l:"x^{}",c:"t",w:"^",nb:1,pr:'<var>x</var><span class="mq-supsub mq-non-leaf mq-sup-only"><span class="mq-sup mq-empty"></span></span>'},{l:"x_{}",c:"t",w:"_",nb:1,pr:'<var>x</var><span class="mq-supsub mq-non-leaf"><span class="mq-sub mq-empty"></span></span>'},{l:"\\sqrt{}",c:"c",w:"sqrt",nb:1,pr:'<span class="mq-non-leaf"><span class="mq-scaled mq-sqrt-prefix" style="transform: scale(1, 0.955556);">√</span><span class="mq-non-leaf mq-sqrt-stem mq-empty"></span></span>'},{l:"\\sqrt[n]{}",c:"c",w:"nthroot",nb:1,pr:'<sup class="mq-nthroot mq-non-leaf"><var>n</var></sup><span class="mq-scaled"><span class="mq-sqrt-prefix mq-scaled" style="transform: scale(1, 0.955556);">√</span><span class="mq-sqrt-stem mq-non-leaf mq-empty"></span></span>'},{l:"\\left(\\right)",c:"i",w:"()"},{l:"\\left|\\right|",c:"i",w:"||"},{l:"\\pi",nb:1,pr:'<span class="mq-nonSymbola">π</span>'},{l:"\\infty",pr:"<span>∞</span>"},{p:"DNE",sm:2}]},{s:.1},{flow:"row",s:2,contents:[{b:"&uarr;",c:"k",w:"Up"},{b:"&darr;",c:"k",w:"Down"},{b:"&larr;",c:"k",w:"Left"},{b:"&rarr;",c:"k",w:"Right"},{b:"&#x232B;",s:2,c:"k",w:"Backspace"}]}]},{p:"Funcs",enabled:!1,tabcontent:[{flow:"row",s:4,contents:[{l:"\\log",c:"f",op:1},{l:"\\ln",c:"f",op:1},{l:"\\log_{}",c:"f",pr:'<var class="mq-operator-name">log</var><span class="mq-supsub mq-non-leaf"><span class="mq-sub mq-empty"></span></span>'},{l:"e^{}",c:"t",w:"e^",pr:'<var>e</var><span class="mq-supsub mq-non-leaf mq-sup-only"><span class="mq-sup mq-empty"></span></span>'}]}]},{p:"Trig",enabled:!1,tabcontent:[{flow:"row",s:6,contents:[{l:"\\sin",c:"f",op:1},{l:"\\cos",c:"f",op:1},{l:"\\tan",c:"f",op:1},{l:"\\sec",c:"f",op:1},{l:"\\csc",c:"f",op:1},{l:"\\cot",c:"f",op:1},{l:"\\sin^{-1}",c:"f",pr:'<var class="mq-operator-name">sin</var><span class="mq-supsub mq-non-leaf mq-sup-only"><span class="mq-sup">−1</span></span>'},{l:"\\cos^{-1}",c:"f",pr:'<var class="mq-operator-name">cos</var><span class="mq-supsub mq-non-leaf mq-sup-only"><span class="mq-sup">−1</span></span>'},{l:"\\tan^{-1}",c:"f",pr:'<var class="mq-operator-name">tan</var><span class="mq-supsub mq-non-leaf mq-sup-only"><span class="mq-sup">−1</span></span>'},{l:"\\sinh",c:"f",op:1},{l:"\\cosh",c:"f",op:1},{l:"\\tanh",c:"f",op:1},{l:"\\pi",nb:1,pr:'<span class="mq-nonSymbola">π</span>'},{s:1},{s:4}]}]},{p:"Inequality",enabled:!1,tabcontent:[{flow:"row",s:4,contents:[{l:"\\lt",pr:'<span class="mq-binary-operator">&lt;</span>'},{l:"\\gt",pr:'<span class="mq-binary-operator">&gt;</span>'},{l:"\\le",pr:'<span class="mq-binary-operator">&le;</span>'},{l:"\\ge",pr:'<span class="mq-binary-operator">&ge;</span>'},{p:"or",c:"w",w:"\\text{ or }"},{p:"DNE",sm:2},{p:"all reals",c:"w",w:"\\text{all reals}",s:2}]}]},{p:"Interval",enabled:!1,tabcontent:[{flow:"row",s:4,contents:[{l:"\\left(\\right)",c:"i",w:"()"},{l:"\\left[\\right]",c:"i",w:"[]"},{l:"\\left(\\right]",c:"i",w:"(]"},{l:"\\left[\\right)",c:"i",w:"[)"},{l:"\\infty",pr:"<span>∞</span>"},{l:"-\\infty",c:"w",w:"-\\infty",pr:"<span>−∞</span>"},{l:"\\cup",pr:'<span class="mq-binary-operator">∪</span>'},{s:1}]}]},{p:"Matrix",sm:1,enabled:!1,tabcontent:[{flow:"row",s:4,contents:[{p:"2×2",c:"w",w:"\\begin{bmatrix}&\\\\&\\end{bmatrix}"},{p:"2×3",c:"w",w:"\\begin{bmatrix}&&\\\\&&\\end{bmatrix}"},{p:"3×3",c:"w",w:"\\begin{bmatrix}&&\\\\&&\\\\&&\\end{bmatrix}"},{p:"3×4",c:"w",w:"\\begin{bmatrix}&&&\\\\&&&\\\\&&&\\end{bmatrix}"},{p:"+Col",c:"m",w:"addColumn"},{p:"-Col",c:"m",w:"deleteColumn"},{p:"+Row",c:"m",w:"addRow"},{p:"-Row",c:"m",w:"deleteRow"}]}]}]};return{getLayout:function(a,s){var a=a.id.substring(8),a=m("#"+a),n=""==(n=a.attr("data-mq-vars")||"")?[]:n.split(/,/),t=(a=a.attr("data-mq")).split(/,/)[0],p=[];return"OSK"===s?(p=m.extend(!0,[],e),a.match(/\bdecimal/)&&"numfunc"!=t?(p.tabs[0].tabcontent[0].s=1,p.tabs[0].tabcontent[0].contents=[{l:"\\infty",pr:"<span>∞</span>"},{p:"DNE",sm:2}],p.tabs[0].tabcontent[2]={flow:"row",s:4,contents:[{b:"7"},{b:"8"},{b:"9"},{s:1},{b:"4"},{b:"5"},{b:"6"},{s:1},{b:"1"},{b:"2"},{b:"3"},{b:"-"},{b:"0"},{b:"."},a.match(/(list|set)/)||t.match(/(ntuple|interval)/)?{b:","}:{s:1},"calcntuple"===t&&!a.match(/vector/)||a.match(/point/)?{l:"\\left(\\right)",c:"t",w:"("}:{s:1}]}):a.match(/(fraction|mixednumber|fracordec)/)&&"numfunc"!=t?(p.tabs[0].tabcontent[0].s=1,p.tabs[0].tabcontent[0].contents=[{l:"\\frac{n}{}",c:"t",w:"/",pr:'<span class="mq-fraction mq-non-leaf"><span class="mq-numerator"><var>n</var></span><span class="mq-denominator mq-empty"></span><span style="display:inline-block;width:0">&#8203;</span></span>'},{l:"\\frac{}{}",c:"c",w:"\\frac",pr:'<span class="mq-fraction mq-non-leaf"><span class="mq-numerator mq-empty"></span><span class="mq-denominator mq-empty"></span><span style="display:inline-block;width:0">&#8203;</span></span>'},{l:"\\infty",pr:"<span>∞</span>"},{p:"DNE",sm:2}],p.tabs[0].tabcontent[2]={flow:"row",s:4,contents:[{b:"7"},{b:"8"},{b:"9"},{s:1},{b:"4"},{b:"5"},{b:"6"},{s:1},{b:"1"},{b:"2"},{b:"3"},{b:"-"},{b:"0"},a.match(/fracordec/)?{b:"."}:{s:1},a.match(/(list|set)/)||t.match(/(ntuple|interval)/)?{b:","}:{s:1},"calcntuple"===t&&!a.match(/vector/)||a.match(/point/)?{l:"\\left(\\right)",c:"t",w:"("}:t.match(/complex/)?{b:"+"}:{s:1}]}):(a.match(/(list|set)/)||t.match(/(interval|string|ntuple)/)?p.tabs[0].tabcontent[2].contents[14]={b:","}:a.match(/equation/)&&(p.tabs[0].tabcontent[2].contents[14]={b:"="}),a.match(/nodecimal/)&&(p.tabs[0].tabcontent[2].contents[13]={s:1}))):(p=m.extend(!0,[],l),a.match(/\bdecimal/)?(p.tabs[0].tabcontent[0].s=3,p.tabs[0].tabcontent[0].contents=[{l:"\\infty",pr:"<span>∞</span>"},{p:"DNE",sm:2},"calcntuple"===t&&!a.match(/vector/)||a.match(/point/)?{l:"\\left(\\right)",c:"t",w:"("}:{s:1}]):a.match(/(fraction|mixednumber|fracordec)/)&&(p.tabs[0].tabcontent[0].s=4,p.tabs[0].tabcontent[0].contents=[{l:"\\frac{n}{}",c:"t",w:"/",pr:'<span class="mq-fraction mq-non-leaf"><span class="mq-numerator"><var>n</var></span><span class="mq-denominator mq-empty"></span><span style="display:inline-block;width:0">&#8203;</span></span>'},{l:"\\frac{}{}",c:"c",w:"\\frac",pr:'<span class="mq-fraction mq-non-leaf"><span class="mq-numerator mq-empty"></span><span class="mq-denominator mq-empty"></span><span style="display:inline-block;width:0">&#8203;</span></span>'},{l:"\\infty",pr:"<span>∞</span>"},{p:"DNE",sm:2}],("calcntuple"===t&&!a.match(/vector/)||a.match(/point/))&&p.tabs[0].tabcontent[0].contents.push({l:"\\left(\\right)",c:"t",w:"("},{s:3})),"numfunc"==t&&a.match(/inequality/)&&(p.tabs[3].enabled=!0,p.tabs[3].tabcontent[0].contents.splice(4,3))),a.match(/(fraction|mixednumber|fracordec|\bdecimal|logic|sexp)/)||(p.tabs[1].enabled=!0,a.match(/notrig/)||(p.tabs[2].enabled=!0,a.match(/allowdegrees/)&&(p.tabs[2].tabcontent[0].contents[13]={l:"\\degree"}))),t.match(/interval/)?a.match(/inequality/)?p.tabs[3].enabled=!0:p.tabs[4].enabled=!0:!t.match(/matrix/)&&!a.match(/matrix/)||a.match(/matrixsized/)?a.match(/set/)?p.tabs[0].tabcontent.unshift({flow:"row",s:1,contents:[{l:"\\lbrace{\\rbrace}",c:"i",w:["\\left\\{","\\right\\}"]}]},{s:.1}):t.match(/complex/)?p.tabs[0].tabcontent.unshift({flow:"row",s:1,contents:[{b:"i",v:1}]},{s:.1}):a.match(/vector/)&&p.tabs[0].tabcontent.unshift({flow:"row",s:1,contents:[{l:"\\left\\langle\\right\\rangle",c:"i",w:["\\left\\langle","\\right\\rangle"]}]},{s:.1}):p.tabs[5].enabled=!0,a.match(/logic/)&&(p.tabs[0].p="Logic",p.tabs[0].tabcontent[0].contents=[{l:"\\vee",pr:'<span class="mq-binary-operator">∨</span>'},{l:"\\wedge",pr:'<span class="mq-binary-operator">∧</span>'},{l:"\\neg",pr:'<span class="mq-binary-operator">¬</span>'},{l:"\\oplus",pr:'<span class="mq-binary-operator">⊕</span>'},{l:"\\left(\\right)",c:"i",w:"()",pr:'<span class="mq-non-leaf"><span class="mq-scaled mq-paren" style="transform: scale(1, 1.2);">(</span><span class="mq-non-leaf mq-empty"></span><span class="mq-scaled mq-paren" style="transform: scale(1, 1.2);">)</span></span>'},{l:"\\to",pr:'<span class="mq-binary-operator">→</span>'},{l:"\\iff",pr:'<span class="mq-binary-operator">↔</span>'}],"OSK"!==s&&(p.tabs[0].tabcontent[0].s=4)),a.match(/sexp/)&&(p.tabs[0].p="Set",p.tabs[0].tabcontent[0].contents=[{l:"\\cup",pr:'<span class="mq-binary-operator">∪</span>'},{l:"\\cap",pr:'<span class="mq-binary-operator">∩</span>'},{l:"\\^c",c:"w",pr:'<span class="mq-non-leaf mq-empty"></span><sup class="mq-binary-operator">c</sup>'},{l:"\\oplus",pr:'<span class="mq-binary-operator">⊕</span>'},{l:"\\left(\\right)",c:"i",w:"()",pr:'<span class="mq-non-leaf"><span class="mq-scaled mq-paren" style="transform: scale(1, 1.2);">(</span><span class="mq-non-leaf mq-empty"></span><span class="mq-scaled mq-paren" style="transform: scale(1, 1.2);">)</span></span>'}],"OSK"!==s&&(p.tabs[0].tabcontent[0].s=3)),0<n.length&&("basic"==(t=function(a,s){var n,t,p,e,l,r;for(n=1,t=[],s="OSK"==s?4:2,e=0;e<a.length;e++){for(p=a[e].split(/_/),l=0;l<p.length;l++)p[l]=p[l].replace(/\b(alpha|beta|chi|delta|epsilon|gamma|varphi|phi|psi|sigma|rho|theta|lambda|mu|nu|omega|tau)\b/i,"\\$&");a[e]=p.join("_"),("\\"!=a[e].charAt(0)||1<p.length)&&a[e].length>n&&(n=a[e].length),a[e]=a[e].replace(/_(\w{2,})/,"_{$1}"),1==a[e].length?t.push({b:a[e],c:"w",v:1}):t.push({l:a[e].replace(/(\\)?([a-zA-Z0-9]{2,})/g,function(a,s,n){return s?a:"\\text{"+n+"}"}),w:a[e],c:"w",r:1})}r=Math.min(8,Math.max(4,Math.ceil(a.length/4)));a.length%r!=0&&t.push({s:r-a.length%r});return{format:a.length<=s&&n<4?"basic":"tab",btns:t,perrow:r}}(n,s)).format?p.tabs[0].tabcontent.unshift({flow:"row",s:1,contents:t.btns},{s:.1}):p.tabs.splice(1,0,{p:"Vars",enabled:!0,tabcontent:[{flow:"row",s:t.perrow,contents:t.btns}]})),p},onShow:function(a,s,n){var t,p,e,l,r,c,o;n&&"under"===s?(t=a.id.substring(8),(p=m("#"+t))[0].hasAttribute("data-tip")&&(l=(e=m("#mqeditor .mqed-tabpanel").first()).children("div").last().position().left-12,o=p.attr("data-tip"),r=document.createElement("div"),m(r).html(o),p[0].hasAttribute("aria-describedby")&&(c=p[0].getAttribute("aria-describedby"),document.getElementById(c).textContent!=o&&(o=m("<a>",{href:"#",text:_("[more..]")}).on("click",function(a){return a.preventDefault(),m(a.target).parent().html(m("#"+c).html()),!1}),m(r).append(" ").append(o))),e.parent().css("height","auto").append(m("<div>",{width:l,class:"mqed-tipholder"}).append(r)))):n&&"OSK"===s&&(t=a.id.substring(8),(p=m("#"+t))[0].hasAttribute("data-tip")&&(o=t.substr(2).split(/-/)[0],reshrinkeh(a.id),showehdd(a.id,p[0].getAttribute("data-tip"),o)))},onBlur:function(){hideeh()},onResize:function(a,s){"OSK"===s&&updateehpos()},onTab:function(a,s,n){"under"===s&&(n.match(/mqeditor-0-tabpanel/)?m(".mqed-tipholder").show():m(".mqed-tipholder").hide())}}}(jQuery);MQeditor.setConfig({getLayout:myMQeditor.getLayout,onShow:myMQeditor.onShow,onBlur:myMQeditor.onBlur,onResize:myMQeditor.onResize,onTab:myMQeditor.onTab,toMQ:AMtoMQ,fromMQ:MQtoAM,onEdit:imathasAssess.syntaxCheckMQ,onEnter:imathasAssess.handleMQenter}),(MQ=MathQuill.getInterface(MathQuill.getInterface.MAX)).config({spaceBehavesLikeTab:!0,leftRightIntoCmdGoes:"up",supSubsRequireOperand:!0,charsThatBreakOutOfSupSub:"=<>",charsThatBreakOutOfSupSubVar:"+-(",charsThatBreakOutOfSupSubOp:"+-(",restrictMismatchedBrackets:!0,autoCommands:"pi theta root sqrt ^oo degree",autoParenOperators:!0,addCommands:{oo:["VanillaSymbol","\\infty ","&infin;"],xor:["VanillaSymbol","\\oplus ","&oplus;"],not:["VanillaSymbol","\\neg ","&not;"]}});
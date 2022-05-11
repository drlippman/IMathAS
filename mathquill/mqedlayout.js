/*
  This object contains the layouts we'll be using. These are exposed by the
  getLayoutstyle and getLayout functions it returns.
  We'll pass those functions to the MQeditor in the config.
 */
var myMQeditor = (function($) {
  var mobileLayout3 = {
    tabs: [
      {
        p: 'Basic',
        enabled: true,
        tabcontent: [
          {
            flow: 'row',
            s: 2,
            contents: [
              {l:'\\left(\\right)', c:'i', w:'()',pr:'<span class="mq-non-leaf"><span class="mq-scaled mq-paren" style="transform: scale(1, 1.2);">(</span><span class="mq-non-leaf mq-empty"></span><span class="mq-scaled mq-paren" style="transform: scale(1, 1.2);">)</span></span>'},
              {l:'x^{}', c:'t', w:'^', nb:1, pr:'<var>x</var><span class="mq-supsub mq-non-leaf mq-sup-only"><span class="mq-sup mq-empty"></span></span>'},
              {l:'\\pi', nb:1, pr:'<span class="mq-nonSymbola">π</span>'},
              {l:'\\sqrt{}', c:'c', w:'sqrt', nb:1, pr:'<span class="mq-non-leaf"><span class="mq-scaled mq-sqrt-prefix" style="transform: scale(1, 0.955556);">√</span><span class="mq-non-leaf mq-sqrt-stem mq-empty"></span></span>'},
              {l:'x_{}', c:'t', w:'_', nb:1, pr:'<var>x</var><span class="mq-supsub mq-non-leaf"><span class="mq-sub mq-empty"></span></span>'},
              {l:'\\sqrt[n]{}', c:'c', w:'nthroot', nb:1, pr:'<sup class="mq-nthroot mq-non-leaf"><var>n</var></sup><span class="mq-scaled"><span class="mq-sqrt-prefix mq-scaled" style="transform: scale(1, 0.955556);">√</span><span class="mq-sqrt-stem mq-non-leaf mq-empty"></span></span>'},
              {p:'DNE', 'sm':2},
              {l:'\\left|\\right|', c:'i', w:'||'},
            ]
          },
          {s:.1},
          {
            flow: 'row',
            s:4,
            contents: [
              {b:'7'},
              {b:'8'},
              {b:'9'},
              {l:'\\frac{}{}', c:'t', w:'/', pr:'<span class="mq-fraction mq-non-leaf"><span class="mq-numerator mq-empty"></span><span class="mq-denominator mq-empty"></span><span style="display:inline-block;width:0">&#8203;</span></span>'},
              {b:'4'},
              {b:'5'},
              {b:'6'},
              {b:'*'},
              {b:'1'},
              {b:'2'},
              {b:'3'},
              {b:'-'},
              {b:'0'},
              {b:'.'},
              {s:1},
              {b:'+'},
            ]
          },
          {s:.1},
          {
            flow: 'row',
            s:2,
            contents: [
              {s:.5},
              {b:'&uarr;', c:'k', w:'Up'},
              {s:.5},
              {b:'&larr;', c:'k', w:'Left'},
              {b:'&rarr;', c:'k', w:'Right'},
              {s:.5},
              {b:'&darr;', c:'k', w:'Down'},
              {s:.5},
              {b:'&#x232B;', s:2, c:'k', w:'Backspace'},
            ]
          }
        ]
      },
      {
        p:'Funcs',
        enabled: false,
        tabcontent: [
          {
            flow: 'row',
            s: 4,
            contents: [
              {l:'\\log', c:'f', op:1},
              {l:'\\ln', c:'f', op:1},
              {l:'\\log_{}', c:'f', pr:'<var class="mq-operator-name">log</var><span class="mq-supsub mq-non-leaf"><span class="mq-sub mq-empty"></span></span>'},
              {l:'e^{}', c:'t', w:'e^', pr:'<var>e</var><span class="mq-supsub mq-non-leaf mq-sup-only"><span class="mq-sup mq-empty"></span></span>'},
            ]
          }
        ]
      },
      {
        p:'Trig',
        enabled: false,
        tabcontent: [
          {
            flow: 'row',
            s: 6,
            contents: [
              {l:'\\sin', c:'f', op:1},
              {l:'\\cos', c:'f', op:1},
              {l:'\\tan', c:'f', op:1},
              {l:'\\sec', c:'f', op:1},
              {l:'\\csc', c:'f', op:1},
              {l:'\\cot', c:'f', op:1},
              {l:'\\sin^{-1}', c:'f', pr:'<var class="mq-operator-name">sin</var><span class="mq-supsub mq-non-leaf mq-sup-only"><span class="mq-sup">−1</span></span>'},
              {l:'\\cos^{-1}', c:'f', pr:'<var class="mq-operator-name">cos</var><span class="mq-supsub mq-non-leaf mq-sup-only"><span class="mq-sup">−1</span></span>'},
              {l:'\\tan^{-1}', c:'f', pr:'<var class="mq-operator-name">tan</var><span class="mq-supsub mq-non-leaf mq-sup-only"><span class="mq-sup">−1</span></span>'},
              {l:'\\sinh', c:'f', op:1},
              {l:'\\cosh', c:'f', op:1},
              {l:'\\tanh', c:'f', op:1},
              {l:'\\pi', nb:1, pr:'<span class="mq-nonSymbola">π</span>'},
              {s:1},
              {s:4}
            ]
          }
        ]
      },
      {
        p:'Inequality',
        enabled: false,
        tabcontent: [
          {
            flow: 'row',
            s: 4,
            contents: [
              {l:'\\lt', pr:'<span class="mq-binary-operator">&lt;</span>'},
              {l:'\\gt', pr:'<span class="mq-binary-operator">&gt;</span>'},
              {l:'\\le', pr:'<span class="mq-binary-operator">&le;</span>'},
              {l:'\\ge', pr:'<span class="mq-binary-operator">&ge;</span>'},
              {p:'or', c:'w', w:'\\text{ or }'},
              {p:'DNE', 'sm':2},
              {p:'all reals', c:'w', w:'\\text{all reals}', s:2}
            ]
          }
        ]
      },
      {
        p:'Interval',
        enabled: false,
        tabcontent: [
          {
            flow: 'row',
            s: 4,
            contents: [
              {l:'\\left(\\right)', c:'i', w:'()'},
              {l:'\\left[\\right]', c:'i', w:'[]'},
              {l:'\\left(\\right]', c:'i', w:'(]'},
              {l:'\\left[\\right)', c:'i', w:'[)'},
              {l:'\\infty',pr:'<span>∞</span>'},
              {l:'-\\infty', c:'w', w:'-\\infty',pr:'<span>−∞</span>'},
              {l:'\\cup',pr:'<span class="mq-binary-operator">∪</span>'},
              {s:1}
            ]
          }
        ]
      },
      {
        p:'Matrix',
        sm: 1,
        enabled: false,
        tabcontent: [
          {
            flow: 'row',
            s: 4,
            contents: [
              {p:'2×2', c:'w', w:'\\begin{bmatrix}&\\\\&\\end{bmatrix}'},
              {p:'2×3', c:'w', w:'\\begin{bmatrix}&&\\\\&&\\end{bmatrix}'},
              {p:'3×3', c:'w', w:'\\begin{bmatrix}&&\\\\&&\\\\&&\\end{bmatrix}'},
              {p:'3×4', c:'w', w:'\\begin{bmatrix}&&&\\\\&&&\\\\&&&\\end{bmatrix}'},
              {p:'+Col', c:'m', w:'addColumn'},
              {p:'-Col', c:'m', w:'deleteColumn'},
              {p:'+Row', c:'m', w:'addRow'},
              {p:'-Row', c:'m', w:'deleteRow'}
            ]
          }
        ]
      },
      {
        p:'=<%',
        enabled: true,
        tabcontent: [
          {
            flow: 'row',
            s: 5,
            contents: [
              {p:'[', s:.5},
              {p:']', s:.5},
              {p:'{', s:.5},
              {p:'}', s:.5},
              {p:'(', s:.5},
              {p:')', s:.5},
              {l:'\\left\\langle\\right\\rangle', c:'i', w:['\\left\\langle','\\right\\rangle']},
              {l:'\\left|\\right|', c:'i', w:'||'},
              {p:'='},
              {l:'\\lt', pr:'<span class="mq-binary-operator">&lt;</span>'},
              {l:'\\gt', pr:'<span class="mq-binary-operator">&gt;</span>'},
              {l:'\\le', pr:'<span class="mq-binary-operator">&le;</span>'},
              {l:'\\ge', pr:'<span class="mq-binary-operator">&ge;</span>'},
              {p:'%'},
              {p:','},
              {l:'\\infty',pr:'<span>∞</span>'},
              {p:'!'},
              {p:'?'}
            ]
          }
        ]
      },
      {
        p:'ABC',
        enabled: true,
        tabcontent: [
          {
            flow: 'row',
            s: 10,
            contents: [
              {p:'q'},{p:'w'},{p:'e'},{p:'r'},{p:'t'},
              {p:'y'},{p:'u'},{p:'i'},{p:'o'},{p:'p'},
              {s:.5},{p:'a'},{p:'s'},{p:'d'},{p:'f'},{p:'g'},
              {p:'h'},{p:'j'},{p:'k'},{p:'l'},{s:.5},
              {b:'&#8679;', c:'shift', s:1.5},
              {p:'z'},{p:'x'},{p:'c'},{p:'v'},{p:'b'},
              {p:'n'},{p:'m'},
              {b:'&#x232B;', c:'k', w:'Backspace', s:1.5},
              {p:'%'},
              {p:','},
              {p:'Space', s:5, c:'t', w:' '},
              {p:'.'},
              {b:'&larr;', c:'k', w:'Left'},
              {b:'&rarr;', c:'k', w:'Right'}
            ]
          }
        ]
      }
    ]
  };

  var underLayout3 = {
    tabs: [
      {
        p: 'Basic',
        enabled: true,
        tabcontent: [
          {
            flow: 'row',
            s: 5,
            contents: [
              {l:'\\frac{}{}', c:'t', w:'/', pr:'<span class="mq-fraction mq-non-leaf"><span class="mq-numerator mq-empty"></span><span class="mq-denominator mq-empty"></span><span style="display:inline-block;width:0">&#8203;</span></span>'},
              {l:'x^{}', c:'t', w:'^', nb:1, pr:'<var>x</var><span class="mq-supsub mq-non-leaf mq-sup-only"><span class="mq-sup mq-empty"></span></span>'},
              {l:'x_{}', c:'t', w:'_', nb:1, pr:'<var>x</var><span class="mq-supsub mq-non-leaf"><span class="mq-sub mq-empty"></span></span>'},
              {l:'\\sqrt{}', c:'c', w:'sqrt', nb:1, pr:'<span class="mq-non-leaf"><span class="mq-scaled mq-sqrt-prefix" style="transform: scale(1, 0.955556);">√</span><span class="mq-non-leaf mq-sqrt-stem mq-empty"></span></span>'},
              {l:'\\sqrt[n]{}', c:'c', w:'nthroot', nb:1, pr:'<sup class="mq-nthroot mq-non-leaf"><var>n</var></sup><span class="mq-scaled"><span class="mq-sqrt-prefix mq-scaled" style="transform: scale(1, 0.955556);">√</span><span class="mq-sqrt-stem mq-non-leaf mq-empty"></span></span>'},
              {l:'\\left(\\right)', c:'i', w:'()'},
              {l:'\\left|\\right|', c:'i', w:'||'},
              {l:'\\pi', nb:1, pr:'<span class="mq-nonSymbola">π</span>'},
              {l:'\\infty',pr:'<span>∞</span>'},
              {p:'DNE', 'sm':2}
            ]
          },
          {s:.1},
          {
            flow: 'row',
            s:2,
            contents: [
              {b:'&uarr;', c:'k', w:'Up'},
              {b:'&darr;', c:'k', w:'Down'},
              {b:'&larr;', c:'k', w:'Left'},
              {b:'&rarr;', c:'k', w:'Right'},
              {b:'&#x232B;', s:2, c:'k', w:'Backspace'},
            ]
          }
        ]
      },
      {
        p:'Funcs',
        enabled: false,
        tabcontent: [
          {
            flow: 'row',
            s: 4,
            contents: [
              {l:'\\log', c:'f', op:1},
              {l:'\\ln', c:'f', op:1},
              {l:'\\log_{}', c:'f', pr:'<var class="mq-operator-name">log</var><span class="mq-supsub mq-non-leaf"><span class="mq-sub mq-empty"></span></span>'},
              {l:'e^{}', c:'t', w:'e^', pr:'<var>e</var><span class="mq-supsub mq-non-leaf mq-sup-only"><span class="mq-sup mq-empty"></span></span>'},
            ]
          }
        ]
      },
      {
        p:'Trig',
        enabled: false,
        tabcontent: [
          {
            flow: 'row',
            s: 6,
            contents: [
              {l:'\\sin', c:'f', op:1},
              {l:'\\cos', c:'f', op:1},
              {l:'\\tan', c:'f', op:1},
              {l:'\\sec', c:'f', op:1},
              {l:'\\csc', c:'f', op:1},
              {l:'\\cot', c:'f', op:1},
              {l:'\\sin^{-1}', c:'f', pr:'<var class="mq-operator-name">sin</var><span class="mq-supsub mq-non-leaf mq-sup-only"><span class="mq-sup">−1</span></span>'},
              {l:'\\cos^{-1}', c:'f', pr:'<var class="mq-operator-name">cos</var><span class="mq-supsub mq-non-leaf mq-sup-only"><span class="mq-sup">−1</span></span>'},
              {l:'\\tan^{-1}', c:'f', pr:'<var class="mq-operator-name">tan</var><span class="mq-supsub mq-non-leaf mq-sup-only"><span class="mq-sup">−1</span></span>'},
              {l:'\\sinh', c:'f', op:1},
              {l:'\\cosh', c:'f', op:1},
              {l:'\\tanh', c:'f', op:1},
              {l:'\\pi', nb:1, pr:'<span class="mq-nonSymbola">π</span>'},
              {s:1},
              {s:4}
            ]
          }
        ]
      },
      {
        p:'Inequality',
        enabled: false,
        tabcontent: [
          {
            flow: 'row',
            s: 4,
            contents: [
              {l:'\\lt', pr:'<span class="mq-binary-operator">&lt;</span>'},
              {l:'\\gt', pr:'<span class="mq-binary-operator">&gt;</span>'},
              {l:'\\le', pr:'<span class="mq-binary-operator">&le;</span>'},
              {l:'\\ge', pr:'<span class="mq-binary-operator">&ge;</span>'},
              {p:'or', c:'w', w:'\\text{ or }'},
              {p:'DNE', 'sm':2},
              {p:'all reals', c:'w', w:'\\text{all reals}', s:2}
            ]
          }
        ]
      },
      {
        p:'Interval',
        enabled: false,
        tabcontent: [
          {
            flow: 'row',
            s: 4,
            contents: [
              {l:'\\left(\\right)', c:'i', w:'()'},
              {l:'\\left[\\right]', c:'i', w:'[]'},
              {l:'\\left(\\right]', c:'i', w:'(]'},
              {l:'\\left[\\right)', c:'i', w:'[)'},
              {l:'\\infty',pr:'<span>∞</span>'},
              {l:'-\\infty', c:'w', w:'-\\infty',pr:'<span>−∞</span>'},
              {l:'\\cup',pr:'<span class="mq-binary-operator">∪</span>'},
              {s:1}
            ]
          }
        ]
      },
      {
        p:'Matrix',
        sm: 1,
        enabled: false,
        tabcontent: [
          {
            flow: 'row',
            s: 4,
            contents: [
              {p:'2×2', c:'w', w:'\\begin{bmatrix}&\\\\&\\end{bmatrix}'},
              {p:'2×3', c:'w', w:'\\begin{bmatrix}&&\\\\&&\\end{bmatrix}'},
              {p:'3×3', c:'w', w:'\\begin{bmatrix}&&\\\\&&\\\\&&\\end{bmatrix}'},
              {p:'3×4', c:'w', w:'\\begin{bmatrix}&&&\\\\&&&\\\\&&&\\end{bmatrix}'},
              {p:'+Col', c:'m', w:'addColumn'},
              {p:'-Col', c:'m', w:'deleteColumn'},
              {p:'+Row', c:'m', w:'addRow'},
              {p:'-Row', c:'m', w:'deleteRow'}
            ]
          }
        ]
      }
    ]
  };
  var underLayout2 = {
    flow: 'row',
    contents: [
      {
        flow: 'col',
        s: 2,
        contents: [
        ]
      },
      {s:.1},
      {
        flow: 'row',
        s: 5,
        contents: [
          {l:'\\frac{}{}', c:'t', w:'/', pr:'<span class="mq-fraction mq-non-leaf"><span class="mq-numerator mq-empty"></span><span class="mq-denominator mq-empty"></span><span style="display:inline-block;width:0">&#8203;</span></span>'},
          {l:'x^{}', c:'t', w:'^', nb:1, pr:'<var>x</var><span class="mq-supsub mq-non-leaf mq-sup-only"><span class="mq-sup mq-empty"></span></span>'},
          {l:'x_{}', c:'t', w:'_', nb:1, pr:'<var>x</var><span class="mq-supsub mq-non-leaf"><span class="mq-sub mq-empty"></span></span>'},
          {l:'\\sqrt{}', c:'c', w:'sqrt', nb:1, pr:'<span class="mq-non-leaf"><span class="mq-scaled mq-sqrt-prefix" style="transform: scale(1, 0.955556);">√</span><span class="mq-non-leaf mq-sqrt-stem mq-empty"></span></span>'},
          {l:'\\sqrt[n]{}', c:'c', w:'nthroot', nb:1, pr:'<sup class="mq-nthroot mq-non-leaf"><var>n</var></sup><span class="mq-scaled"><span class="mq-sqrt-prefix mq-scaled" style="transform: scale(1, 0.955556);">√</span><span class="mq-sqrt-stem mq-non-leaf mq-empty"></span></span>'},
          {l:'\\left(\\right)', c:'t', w:'('},
          {l:'\\left|\\right|', c:'t', w:'|', nb:1},
          {l:'\\pi', nb:1, pr:'<span class="mq-nonSymbola">π</span>'},
          {l:'\\infty',pr:'<span>∞</span>'},
          {p:'DNE', 'sm':2}
        ]
      },
      {s:.1},
      {
        flow: 'row',
        s:2,
        contents: [
          {b:'&uarr;', c:'k', w:'Up'},
          {b:'&darr;', c:'k', w:'Down'},
          {b:'&larr;', c:'k', w:'Left'},
          {b:'&rarr;', c:'k', w:'Right'},
          {b:'&#x232B;', s:2, c:'k', w:'Backspace'},
        ]
      }
    ]
  };


  function getLayout(el, layoutstyle) {
    var baseid = el.id.substring(8);
    var textel = $('#'+baseid);
    //TODO: fix this - need to get from params
    var vars = textel.attr("data-mq-vars") || '';
    vars = (vars=='') ? [] : vars.split(/,/);
    var calcformat = textel.attr("data-mq");
    var qtype = calcformat.split(/,/)[0];
    var baselayout = [];
    if (layoutstyle === 'OSK') {
      baselayout = $.extend(true, [], mobileLayout3);
      if (calcformat.match(/\bdecimal/) && qtype != 'numfunc') {
        baselayout.tabs[0].tabcontent[0].s = 1;
        baselayout.tabs[0].tabcontent[0].contents = [
          {l:'\\infty',pr:'<span>∞</span>'},
          {p:'DNE', 'sm':2},
        ];
        baselayout.tabs[0].tabcontent[2] = {
          flow:'row',
          s:4,
          contents: [
            {b:'7'},
            {b:'8'},
            {b:'9'},
            {s:1},
            {b:'4'},
            {b:'5'},
            {b:'6'},
            {s:1},
            {b:'1'},
            {b:'2'},
            {b:'3'},
            {b:'-'},
            {b:'0'},
            {'b':'.'},
            (calcformat.match(/(list|set)/) ||
            qtype.match(/(ntuple|interval)/)) ? {'b':','} : {s:1},
            ((qtype === 'calcntuple' && !calcformat.match(/vector/)) ||
              calcformat.match(/point/)) ? {l:'\\left(\\right)', c:'t', w:'('} : {s:1}
          ]
        };
      } else if (calcformat.match(/(fraction|mixednumber|fracordec)/) && qtype != 'numfunc') {
        baselayout.tabs[0].tabcontent[0].s = 1;
        baselayout.tabs[0].tabcontent[0].contents = [
          {l:'\\frac{n}{}', c:'t', w:'/', pr:'<span class="mq-fraction mq-non-leaf"><span class="mq-numerator"><var>n</var></span><span class="mq-denominator mq-empty"></span><span style="display:inline-block;width:0">&#8203;</span></span>'},
          {l:'\\frac{}{}', c:'c', w:'\\frac', pr:'<span class="mq-fraction mq-non-leaf"><span class="mq-numerator mq-empty"></span><span class="mq-denominator mq-empty"></span><span style="display:inline-block;width:0">&#8203;</span></span>'},
          {l:'\\infty',pr:'<span>∞</span>'},
          {p:'DNE', 'sm':2},
        ];
        baselayout.tabs[0].tabcontent[2] = {
          flow:'row',
          s:4,
          contents: [
            {b:'7'},
            {b:'8'},
            {b:'9'},
            {s:1},
            {b:'4'},
            {b:'5'},
            {b:'6'},
            {s:1},
            {b:'1'},
            {b:'2'},
            {b:'3'},
            {b:'-'},
            {b:'0'},
            calcformat.match(/fracordec/) ? {'b':'.'} : {s:1},
            (calcformat.match(/(list|set)/) ||
             qtype.match(/(ntuple|interval)/)) ? {'b':','} : {s:1},
            ((qtype === 'calcntuple' && !calcformat.match(/vector/)) ||
              calcformat.match(/point/)) ? {l:'\\left(\\right)', c:'t', w:'('} :
              (qtype.match(/complex/) ? {b:'+'} : {s:1})
          ]
        };
      } else {
        if (calcformat.match(/(list|set)/) || qtype.match(/(interval|string|ntuple)/)) {
          baselayout.tabs[0].tabcontent[2].contents[14] = {'b':','};
        } else if (calcformat.match(/equation/)) { // replace , with =
          baselayout.tabs[0].tabcontent[2].contents[14] = {'b':'='};
        }
        if (calcformat.match(/nodecimal/)) {
          baselayout.tabs[0].tabcontent[2].contents[13] = {s:1};
        }
      }
    } else {
      baselayout = $.extend(true, [], underLayout3);
      if (calcformat.match(/\bdecimal/)) {
        baselayout.tabs[0].tabcontent[0].s = 3;
        baselayout.tabs[0].tabcontent[0].contents = [
          {l:'\\infty',pr:'<span>∞</span>'},
          {p:'DNE', 'sm':2},
          ((qtype === 'calcntuple' && !calcformat.match(/vector/)) ||
            calcformat.match(/point/)) ? {l:'\\left(\\right)', c:'t', w:'('} : {s:1}
        ];
      } else if (calcformat.match(/(fraction|mixednumber|fracordec)/)) {
        baselayout.tabs[0].tabcontent[0].s = 4;
        baselayout.tabs[0].tabcontent[0].contents = [
          {l:'\\frac{n}{}', c:'t', w:'/', pr:'<span class="mq-fraction mq-non-leaf"><span class="mq-numerator"><var>n</var></span><span class="mq-denominator mq-empty"></span><span style="display:inline-block;width:0">&#8203;</span></span>'},
          {l:'\\frac{}{}', c:'c', w:'\\frac', pr:'<span class="mq-fraction mq-non-leaf"><span class="mq-numerator mq-empty"></span><span class="mq-denominator mq-empty"></span><span style="display:inline-block;width:0">&#8203;</span></span>'},
          {l:'\\infty',pr:'<span>∞</span>'},
          {p:'DNE', 'sm':2},
        ];
        if ((qtype === 'calcntuple' && !calcformat.match(/vector/)) ||
          calcformat.match(/point/)) {
          baselayout.tabs[0].tabcontent[0].contents.push(
            {l:'\\left(\\right)', c:'t', w:'('},
            {s: 3}
          );
        }
      }
      if (qtype=='numfunc' && calcformat.match(/inequality/)) {
        baselayout.tabs[3].enabled = true;
        baselayout.tabs[3].tabcontent[0].contents.splice(4,3);
      }
    }
    if (!calcformat.match(/(fraction|mixednumber|fracordec|\bdecimal|logic)/)) {
      baselayout.tabs[1].enabled = true;
      if (!calcformat.match(/notrig/)) {
        baselayout.tabs[2].enabled = true;
        if (calcformat.match(/allowdegrees/)) {
            baselayout.tabs[2].tabcontent[0].contents[13] = {l:'\\degree'};
        }
      }
    }
    if (qtype.match(/interval/)) {
      if (calcformat.match(/inequality/)) {
        baselayout.tabs[3].enabled = true;
      } else {
        baselayout.tabs[4].enabled = true;
      }
    } else if ((qtype.match(/matrix/) || calcformat.match(/matrix/)) && !calcformat.match(/matrixsized/)) {
      baselayout.tabs[5].enabled = true;
    } else if (calcformat.match(/set/)) {
      baselayout.tabs[0].tabcontent.unshift({
        flow: 'row',
        s: 1,
        contents: [{l:'\\lbrace{\\rbrace}', c:'i', w:['\\left\\{','\\right\\}']}]
      }, {s:.1});
    } else if (qtype.match(/complex/)) {
      baselayout.tabs[0].tabcontent.unshift({
        flow: 'row',
        s: 1,
        contents: [{b:'i', v:1}]
      }, {s:.1});
    } else if (calcformat.match(/vector/)) {
      baselayout.tabs[0].tabcontent.unshift({
        flow: 'row',
        s: 1,
        contents: [{l:'\\left\\langle\\right\\rangle', c:'i', w:['\\left\\langle','\\right\\rangle']}]
      }, {s:.1});
    }
    if (calcformat.match(/logic/)) {
        baselayout.tabs[0].p = "Logic";
        baselayout.tabs[0].tabcontent[0].contents = [
            {l:'\\vee',pr:'<span class="mq-binary-operator">∨</span>'},
            {l:'\\wedge',pr:'<span class="mq-binary-operator">∧</span>'},
            {b:'~'},
            {l:'\\left(\\right)', c:'i', w:'()',pr:'<span class="mq-non-leaf"><span class="mq-scaled mq-paren" style="transform: scale(1, 1.2);">(</span><span class="mq-non-leaf mq-empty"></span><span class="mq-scaled mq-paren" style="transform: scale(1, 1.2);">)</span></span>'},
            {l:'\\implies',pr:'<span class="mq-binary-operator">⇒</span>'},
            {l:'\\iff',pr:'<span class="mq-binary-operator">⇔</span>'}
        ];
        if (layoutstyle !== 'OSK') {
            baselayout.tabs[0].tabcontent[0].s = 3;
        }
    }

    // for both
    if (vars.length > 0) {
        var varbtns = getVarsButtons2(vars,layoutstyle);
        if (varbtns.format == 'basic') {
          baselayout.tabs[0].tabcontent.unshift({
            flow: 'row',
            s: 1,
            contents: varbtns.btns
          }, {s:.1});
        } else {
          baselayout.tabs.splice(1, 0, {
            p: 'Vars',
            enabled: true,
            tabcontent: [{
              flow: 'row',
              s: varbtns.perrow,
              contents: varbtns.btns
            }]
          });
        }
    }
    return baselayout;
  }

  function getVarsButtons2(vars,layoutstyle) {
    var maxlen = 1;
    var btns = [];
    var maxbasic = (layoutstyle=='OSK' ? 4 : 2);
    var varpts
    for (var i=0; i<vars.length; i++) {
        varpts = vars[i].split(/_/);
        for (var j=0; j<varpts.length; j++) {
            varpts[j] = varpts[j].replace(/\b(alpha|beta|chi|delta|epsilon|gamma|varphi|phi|psi|sigma|rho|theta|lambda|mu|nu|omega|tau)\b/i,
                '\\$&');
        }
        vars[i] = varpts.join('_');
      if ((vars[i].charAt(0)!='\\' || varpts.length>1) && vars[i].length > maxlen) {
        maxlen = vars[i].length;
      }
      vars[i] = vars[i].replace(/_(\w{2,})/,"_{$1}");

      if (vars[i].length == 1) {
        btns.push({'b':vars[i], c:'w', v:1});
      } else {
        btns.push({'l':vars[i].replace(/(\\)?([a-zA-Z0-9]{2,})/g, function(m,p1,p2) {
            return p1 ? m : "\\text{"+p2+"}";
        }), 'w':vars[i], c:'w', r:1});
      }
    }
    var perrow = Math.min(8,Math.max(4, Math.ceil(vars.length/4)));
    if (vars.length%perrow !== 0) {
      btns.push({'s': perrow - vars.length%perrow});
    }
    return {
      format: (vars.length <= maxbasic && maxlen < 4) ? 'basic' : 'tab',
      btns: btns,
      perrow: perrow
    };
  }

  function onShow(mqel, layoutstyle, rebuild) {
    if (rebuild && layoutstyle === 'under') {
      var baseid = mqel.id.substring(8);
      var textel = $('#'+baseid);
      if (textel[0].hasAttribute("data-tip")) {
        var tabpanel = $("#mqeditor .mqed-tabpanel").first();
        var lastdiv = tabpanel.children("div").last();
        var tipwidth = lastdiv.position().left - 12;
        var tiptext = textel.attr("data-tip");
        var tipdiv = document.createElement("div");
        $(tipdiv).html(tiptext);
        if (textel[0].hasAttribute("aria-describedby")) {
          var fulltipRef = textel[0].getAttribute("aria-describedby");
          if (document.getElementById(fulltipRef).textContent != tiptext) {
            var morelink = $("<a>", {
              href: "#",
              text: _("[more..]"),
            }).on('click', function(e) {
              e.preventDefault();
              $(e.target).parent().html($("#"+fulltipRef).html());
              return false;
            });
            $(tipdiv).append(" ").append(morelink);
          }
        }
        tabpanel.parent().css("height", "auto").append($("<div>", {
          width: tipwidth,
          class: "mqed-tipholder"
        }).append(tipdiv));
      }
    } else if (rebuild && layoutstyle === 'OSK') {
      var baseid = mqel.id.substring(8);
      var textel = $('#'+baseid);
      if (textel[0].hasAttribute("data-tip")) {
        var ref = baseid.substr(2).split(/-/)[0];
        reshrinkeh(mqel.id);
        showehdd(mqel.id, textel[0].getAttribute("data-tip"), ref);
      }
    }
  }

  function onBlur() {
    hideeh();
  }
  function onResize(el, layoutstyle) {
    if (layoutstyle === 'OSK') {
        updateehpos();
    }
  }

  function onTab(tabbtn, layoutstyle, tabid) {
    if (layoutstyle === 'under') {
      if (tabid.match(/mqeditor-0-tabpanel/)) {
        $(".mqed-tipholder").show();
      } else {
        $(".mqed-tipholder").hide();
      }
    }
  }

  return {
    getLayout: getLayout,
    onShow: onShow,
    onBlur: onBlur,
    onResize: onResize,
    onTab: onTab
  }
})(jQuery);


/*
  This code initializes everything
 */
// Tell the editor to use our functions above to generate layout
MQeditor.setConfig({
  getLayout: myMQeditor.getLayout,
  onShow: myMQeditor.onShow,
  onBlur: myMQeditor.onBlur,
  onResize: myMQeditor.onResize,
  onTab: myMQeditor.onTab,
  toMQ: AMtoMQ,
  fromMQ: MQtoAM,
  onEdit: imathasAssess.syntaxCheckMQ,
  onEnter: imathasAssess.handleMQenter
});

// set the default MathQuill config.
var MQ = MathQuill.getInterface(MathQuill.getInterface.MAX);
MQ.config({
  spaceBehavesLikeTab: true,
  leftRightIntoCmdGoes: 'up',
  supSubsRequireOperand: true,
  charsThatBreakOutOfSupSub: '=<>',
  charsThatBreakOutOfSupSubVar: "+-(",
  charsThatBreakOutOfSupSubOp: "+-(",
  restrictMismatchedBrackets: true,
  autoCommands: 'pi theta root sqrt ^oo degree',
  autoParenOperators: true,
  addCommands: {'oo': ['VanillaSymbol', '\\infty ', '&infin;']},
});

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
              {l:'\\left(\\right)', c:'i', w:'()',a:_('parentheses'), pr:'<span class="mq-non-leaf"><span class="mq-scaled mq-paren" style="transform: scale(1, 1.2);">(</span><span class="mq-non-leaf mq-empty"></span><span class="mq-scaled mq-paren" style="transform: scale(1, 1.2);">)</span></span>'},
              {l:'x^{}', c:'t', w:'^', nb:1, a:_('exponent'), pr:'<var>x</var><span class="mq-supsub mq-non-leaf mq-sup-only"><span class="mq-sup mq-empty"></span></span>'},
              {l:'\\pi', nb:1, a:_('pi'),pr:'<span class="mq-nonSymbola">π</span>'},
              {l:'\\sqrt{}', c:'c', w:'sqrt', nb:1, a:_('square root'),pr:'<span class="mq-non-leaf mq-sqrt-container"><span class="mq-scaled mq-sqrt-prefix"><svg preserveAspectRatio="none" viewBox="0 0 32 54"><path d="M0 33 L7 27 L12.5 47 L13 47 L30 0 L32 0 L13 54 L11 54 L4.5 31 L0 33"></path></svg></span><span class="mq-non-leaf mq-sqrt-stem mq-empty"></span></span>'},
              {l:'x_{}', c:'t', w:'_', nb:1, a:_('subscript'), pr:'<var>x</var><span class="mq-supsub mq-non-leaf"><span class="mq-sub mq-empty"></span></span>'},
              {l:'\\sqrt[n]{}', c:'c', w:'nthroot', nb:1, a:_('n-th root'),pr:'<span class="mq-nthroot-container mq-non-leaf"><sup class="mq-nthroot mq-non-leaf"><var>n</var></sup><span class="mq-scaled mq-sqrt-container"><span class="mq-sqrt-prefix mq-scaled"><svg preserveAspectRatio="none" viewBox="0 0 32 54"><path d="M0 33 L7 27 L12.5 47 L13 47 L30 0 L32 0 L13 54 L11 54 L4.5 31 L0 33"></path></svg></span><span class="mq-sqrt-stem mq-non-leaf mq-empty"></span></span></span>'},
              {p:'DNE', 'sm':2},
              {l:'\\left|\\right|', c:'i', w:'||', a:_('absolute value')},
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
              {l:'\\frac{}{}', c:'t', w:'/', a:_('fraction'),pr:'<span class="mq-fraction mq-non-leaf"><span class="mq-numerator mq-empty"></span><span class="mq-denominator mq-empty"></span><span style="display:inline-block;width:0">&#8203;</span></span>'},
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
              {b:'&uarr;', c:'k', w:'Up', a:_('Move up')},
              {s:.5},
              {b:'&larr;', c:'k', w:'Left', a:_('Move left')},
              {b:'&rarr;', c:'k', w:'Right', a:_('Move right')},
              {s:.5},
              {b:'&darr;', c:'k', w:'Down', a:_('Move down')},
              {s:.5},
              {b:'&#x232B;', s:2, c:'k', w:'Backspace', a:_('Backspace')},
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
              {l:'\\log', c:'f', op:1, a:_('log')},
              {l:'\\ln', c:'f', op:1, a:_('natural log')},
              {l:'\\log_{}', c:'f', a:_('log with base'),  pr:'<var class="mq-operator-name">log</var><span class="mq-supsub mq-non-leaf"><span class="mq-sub mq-empty"></span></span>'},
              {l:'e^{}', c:'t', w:'e^', a:_('e to power'), pr:'<var>e</var><span class="mq-supsub mq-non-leaf mq-sup-only"><span class="mq-sup mq-empty"></span></span>'},
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
              {l:'\\sin', c:'f', op:1, a:_('sine')},
              {l:'\\cos', c:'f', op:1, a:_('cosine')},
              {l:'\\tan', c:'f', op:1, a:_('tangent')},
              {l:'\\sec', c:'f', op:1, a:_('secant')},
              {l:'\\csc', c:'f', op:1, a:_('cosecant')},
              {l:'\\cot', c:'f', op:1, a:_('cotangent')},
              {l:'\\sin^{-1}', c:'f', a:_('arc sine'), pr:'<var class="mq-operator-name">sin</var><span class="mq-supsub mq-non-leaf mq-sup-only"><span class="mq-sup">−1</span></span>'},
              {l:'\\cos^{-1}', c:'f', a:_('arc cosine'), pr:'<var class="mq-operator-name">cos</var><span class="mq-supsub mq-non-leaf mq-sup-only"><span class="mq-sup">−1</span></span>'},
              {l:'\\tan^{-1}', c:'f', a:_('arc tangent'), pr:'<var class="mq-operator-name">tan</var><span class="mq-supsub mq-non-leaf mq-sup-only"><span class="mq-sup">−1</span></span>'},
              {l:'\\sinh', c:'f', a:_('hyperbolic sin'), op:1},
              {l:'\\cosh', c:'f', a:_('hyperbolic cosine'), op:1},
              {l:'\\tanh', c:'f', a:_('hyperbolic tangent'), op:1},
              {l:'\\pi', nb:1, a:_('pi'), pr:'<span class="mq-nonSymbola">π</span>'},
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
              {l:'\\lt', a:_('less than'), pr:'<span class="mq-binary-operator">&lt;</span>'},
              {l:'\\gt', a:_('greater than'), pr:'<span class="mq-binary-operator">&gt;</span>'},
              {l:'\\le', a:_('less than or equal'), pr:'<span class="mq-binary-operator">&le;</span>'},
              {l:'\\ge', a:_('greater than or equal'), pr:'<span class="mq-binary-operator">&ge;</span>'},
              {l:'\\ne', a:_('not equal'), pr:'<span class="mq-binary-operator">&ne;</span>'},
              {p:'or', c:'w', w:'\\text{ or }'},
              {p:'DNE', 'sm':2},
              {p:'all reals', c:'w', w:'\\text{all reals}', 'sm':2}
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
              {l:'\\left(\\right)', c:'i', a:_('left and right parentheses'), w:'()'},
              {l:'\\left[\\right]', c:'i', a:_('left and right brackets'), w:'[]'},
              {l:'\\left(\\right]', c:'i', a:_('left parenthesis, right bracket'), w:'(]'},
              {l:'\\left[\\right)', c:'i', a:_('left bracket, right parenthesis'), w:'[)'},
              {l:'\\infty', a:_('infinity'),pr:'<span>∞</span>'},
              {l:'-\\infty', c:'w', w:'-\\infty', a:_('negative infinity'),pr:'<span>−∞</span>'},
              {l:'\\cup', a:_('union'),pr:'<span class="mq-binary-operator">∪</span>'},
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
            s: 6,
            contents: [
              {p:'2×1', c:'w', a:_('2 by 1 matrix'), w:'\\begin{bmatrix}\\\\\\end{bmatrix}'},
              {p:'2×2', c:'w', a:_('2 by 2 matrix'),w:'\\begin{bmatrix}&\\\\&\\end{bmatrix}'},
              {p:'2×3', c:'w', a:_('2 by 3 matrix'),w:'\\begin{bmatrix}&&\\\\&&\\end{bmatrix}'},
              {p:'3×1', c:'w', a:_('3 by 1 matrix'),w:'\\begin{bmatrix}\\\\\\\\\\end{bmatrix}'},
              {p:'3×3', c:'w', a:_('3 by 3 matrix'),w:'\\begin{bmatrix}&&\\\\&&\\\\&&\\end{bmatrix}'},
              {p:'3×4', c:'w', a:_('3 by 4 matrix'),w:'\\begin{bmatrix}&&&\\\\&&&\\\\&&&\\end{bmatrix}'},
              {p:'+Col', c:'m', a:_('add column'),w:'addColumn'},
              {p:'-Col', c:'m', a:_('delete column'),w:'deleteColumn'},
              {p:'+Row', c:'m', a:_('add row'),w:'addRow'},
              {p:'-Row', c:'m', a:_('add column'),w:'deleteRow'}
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
              {p:'[', a:_('left bracket'),s:.5},
              {p:']', a:_('right bracket'),s:.5},
              {p:'{', a:_('left brace'),s:.5},
              {p:'}', a:_('right brace'),s:.5},
              {p:'(', a:_('left parenthesis'),s:.5},
              {p:')', a:_('right parenthesis'),s:.5},
              {l:'\\left\\langle\\right\\rangle', c:'i', a:_('vector brackets'),w:['\\left\\langle','\\right\\rangle']},
              {l:'\\left|\\right|', c:'i', a:_('absolute value'),w:'||'},
              {p:'='},
              {l:'\\lt', a:_('less than'),pr:'<span class="mq-binary-operator">&lt;</span>'},
              {l:'\\gt', a:_('greater than'),pr:'<span class="mq-binary-operator">&gt;</span>'},
              {l:'\\le', a:_('less than or equal'),pr:'<span class="mq-binary-operator">&le;</span>'},
              {l:'\\ge', a:_('greater than or equal'),pr:'<span class="mq-binary-operator">&ge;</span>'},
              {p:'%', a:_('percent')},
              {p:',', a:_('comma')},
              {l:'\\infty',a:_('infinity'),pr:'<span>∞</span>'},
              {p:'!', a:_('exclamation point')},
              {p:'?', a:_('question mark')}
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
              {b:'&#8679;', c:'shift', a:_('capitalize'),  s:1.5},
              {p:'z'},{p:'x'},{p:'c'},{p:'v'},{p:'b'},
              {p:'n'},{p:'m'},
              {b:'&#x232B;', c:'k', w:'Backspace', a:_('backspace'), s:1.5},
              {p:'%', a:_('percent')},
              {p:',', a:_('comma')},
              {p:'Space', s:5, c:'t', w:' '},
              {p:'.'},
              {b:'&larr;', c:'k', w:'Left', a:_('move left')},
              {b:'&rarr;', c:'k', w:'Right', a:_('move right')}
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
              {l:'\\frac{}{}', c:'t', w:'/', a:_('fraction'), pr:'<span class="mq-fraction mq-non-leaf"><span class="mq-numerator mq-empty"></span><span class="mq-denominator mq-empty"></span><span style="display:inline-block;width:0">&#8203;</span></span>'},
              {l:'x^{}', c:'t', w:'^', nb:1, a:_('exponent'), pr:'<var>x</var><span class="mq-supsub mq-non-leaf mq-sup-only"><span class="mq-sup mq-empty"></span></span>'},
              {l:'x_{}', c:'t', w:'_', nb:1, a:_('subscript'),pr:'<var>x</var><span class="mq-supsub mq-non-leaf"><span class="mq-sub mq-empty"></span></span>'},
              {l:'\\sqrt{}', c:'c', w:'sqrt', nb:1, a:_('square root'),pr:'<span class="mq-non-leaf mq-sqrt-container"><span class="mq-scaled mq-sqrt-prefix"><svg preserveAspectRatio="none" viewBox="0 0 32 54"><path d="M0 33 L7 27 L12.5 47 L13 47 L30 0 L32 0 L13 54 L11 54 L4.5 31 L0 33"></path></svg></span><span class="mq-non-leaf mq-sqrt-stem mq-empty"></span></span>'},
              {l:'\\sqrt[n]{}', c:'c', w:'nthroot', nb:1, a:_('n-th root'),pr:'<span class="mq-nthroot-container mq-non-leaf"><sup class="mq-nthroot mq-non-leaf"><var>n</var></sup><span class="mq-scaled mq-sqrt-container"><span class="mq-sqrt-prefix mq-scaled"><svg preserveAspectRatio="none" viewBox="0 0 32 54"><path d="M0 33 L7 27 L12.5 47 L13 47 L30 0 L32 0 L13 54 L11 54 L4.5 31 L0 33"></path></svg></span><span class="mq-sqrt-stem mq-non-leaf mq-empty"></span></span></span>'},
              {l:'\\left(\\right)', c:'i', a:_('parentheses'),w:'()'},
              {l:'\\left|\\right|', c:'i', a:_('absolute value'),w:'||'},
              {l:'\\pi', nb:1, a:_('pi'),pr:'<span class="mq-nonSymbola">π</span>'},
              {l:'\\infty',a:_('infinity'),pr:'<span>∞</span>'},
              {p:'DNE', 'sm':2}
            ]
          },
          {s:.1},
          {
            flow: 'row',
            s:2,
            contents: [
              {b:'&uarr;', c:'k', a:_('move up'),w:'Up'},
              {b:'&darr;', c:'k', a:_('move down'),w:'Down'},
              {b:'&larr;', c:'k', a:_('move left'),w:'Left'},
              {b:'&rarr;', c:'k', a:_('move right'),w:'Right'},
              {b:'&#x232B;', s:2, c:'k', a:_('backspace'),w:'Backspace'},
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
              {l:'\\ln', c:'f', a:_('natural log'),op:1},
              {l:'\\log_{}', c:'f', a:_('log with base'),pr:'<var class="mq-operator-name">log</var><span class="mq-supsub mq-non-leaf"><span class="mq-sub mq-empty"></span></span>'},
              {l:'e^{}', c:'t', w:'e^', a:_('e to a power'),pr:'<var>e</var><span class="mq-supsub mq-non-leaf mq-sup-only"><span class="mq-sup mq-empty"></span></span>'},
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
              {l:'\\sin', c:'f', a:_('sine'),op:1},
              {l:'\\cos', c:'f', a:_('cosine'),op:1},
              {l:'\\tan', c:'f', a:_('tangent'),op:1},
              {l:'\\sec', c:'f', a:_('secant'),op:1},
              {l:'\\csc', c:'f', a:_('cosecant'),op:1},
              {l:'\\cot', c:'f', a:_('cotangent'),op:1},
              {l:'\\sin^{-1}', c:'f', a:_('arcsine'),pr:'<var class="mq-operator-name">sin</var><span class="mq-supsub mq-non-leaf mq-sup-only"><span class="mq-sup">−1</span></span>'},
              {l:'\\cos^{-1}', c:'f', a:_('arccosine'),pr:'<var class="mq-operator-name">cos</var><span class="mq-supsub mq-non-leaf mq-sup-only"><span class="mq-sup">−1</span></span>'},
              {l:'\\tan^{-1}', c:'f', a:_('arctangent'),pr:'<var class="mq-operator-name">tan</var><span class="mq-supsub mq-non-leaf mq-sup-only"><span class="mq-sup">−1</span></span>'},
              {l:'\\sinh', c:'f', a:_('hyperbolic sine'),op:1},
              {l:'\\cosh', c:'f', a:_('hyperbolic cosine'),op:1},
              {l:'\\tanh', c:'f', a:_('hyperbolic tangent'),op:1},
              {l:'\\pi', nb:1, a:_('pi'),pr:'<span class="mq-nonSymbola">π</span>'},
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
                {l:'\\lt', a:_('less than'), pr:'<span class="mq-binary-operator">&lt;</span>'},
                {l:'\\gt', a:_('greater than'), pr:'<span class="mq-binary-operator">&gt;</span>'},
                {l:'\\le', a:_('less than or equal'), pr:'<span class="mq-binary-operator">&le;</span>'},
                {l:'\\ge', a:_('greater than or equal'), pr:'<span class="mq-binary-operator">&ge;</span>'},
                {l:'\\ne', a:_('not equal'), pr:'<span class="mq-binary-operator">&ne;</span>'},
                {p:'or', c:'w', w:'\\text{ or }'},
                {p:'DNE', 'sm':2},
                {p:'all reals', c:'w', w:'\\text{all reals}', 'sm':2}  
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
              {l:'\\left(\\right)', c:'i', a:_('left and right parentheses'), w:'()'},
              {l:'\\left[\\right]', c:'i', a:_('left and right brackets'), w:'[]'},
              {l:'\\left(\\right]', c:'i', a:_('left parenthesis, right bracket'), w:'(]'},
              {l:'\\left[\\right)', c:'i', a:_('left bracket, right parenthesis'), w:'[)'},
              {l:'\\infty', a:_('infinity'),pr:'<span>∞</span>'},
              {l:'-\\infty', c:'w', w:'-\\infty', a:_('negative infinity'),pr:'<span>−∞</span>'},
              {l:'\\cup', a:_('union'),pr:'<span class="mq-binary-operator">∪</span>'},
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
            s: 6,
            contents: [
              {p:'2×1', c:'w', a:_('2 by 1 matrix'), w:'\\begin{bmatrix}\\\\\\end{bmatrix}'},
              {p:'2×2', c:'w', a:_('2 by 2 matrix'),w:'\\begin{bmatrix}&\\\\&\\end{bmatrix}'},
              {p:'2×3', c:'w', a:_('2 by 3 matrix'),w:'\\begin{bmatrix}&&\\\\&&\\end{bmatrix}'},
              {p:'3×1', c:'w', a:_('3 by 1 matrix'),w:'\\begin{bmatrix}\\\\\\\\\\end{bmatrix}'},
              {p:'3×3', c:'w', a:_('3 by 3 matrix'),w:'\\begin{bmatrix}&&\\\\&&\\\\&&\\end{bmatrix}'},
              {p:'3×4', c:'w', a:_('3 by 4 matrix'),w:'\\begin{bmatrix}&&&\\\\&&&\\\\&&&\\end{bmatrix}'},
              {p:'+Col', c:'m', a:_('add column'),w:'addColumn'},
              {p:'-Col', c:'m', a:_('delete column'),w:'deleteColumn'},
              {p:'+Row', c:'m', a:_('add row'),w:'addRow'},
              {p:'-Row', c:'m', a:_('add column'),w:'deleteRow'}
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
          {l:'\\frac{}{}', c:'t', w:'/', a:_('fraction'), pr:'<span class="mq-fraction mq-non-leaf"><span class="mq-numerator mq-empty"></span><span class="mq-denominator mq-empty"></span><span style="display:inline-block;width:0">&#8203;</span></span>'},
          {l:'x^{}', c:'t', w:'^', nb:1, a:_('exponent'), pr:'<var>x</var><span class="mq-supsub mq-non-leaf mq-sup-only"><span class="mq-sup mq-empty"></span></span>'},
          {l:'x_{}', c:'t', w:'_', nb:1, a:_('subscript'),pr:'<var>x</var><span class="mq-supsub mq-non-leaf"><span class="mq-sub mq-empty"></span></span>'},
          {l:'\\sqrt{}', c:'c', w:'sqrt', nb:1, a:_('square root'),pr:'<span class="mq-non-leaf mq-sqrt-container"><span class="mq-scaled mq-sqrt-prefix"><svg preserveAspectRatio="none" viewBox="0 0 32 54"><path d="M0 33 L7 27 L12.5 47 L13 47 L30 0 L32 0 L13 54 L11 54 L4.5 31 L0 33"></path></svg></span><span class="mq-non-leaf mq-sqrt-stem mq-empty"></span></span>'},
          {l:'\\sqrt[n]{}', c:'c', w:'nthroot', nb:1, a:_('n-th root'),pr:'<span class="mq-nthroot-container mq-non-leaf"><sup class="mq-nthroot mq-non-leaf"><var>n</var></sup><span class="mq-scaled mq-sqrt-container"><span class="mq-sqrt-prefix mq-scaled"><svg preserveAspectRatio="none" viewBox="0 0 32 54"><path d="M0 33 L7 27 L12.5 47 L13 47 L30 0 L32 0 L13 54 L11 54 L4.5 31 L0 33"></path></svg></span><span class="mq-sqrt-stem mq-non-leaf mq-empty"></span></span></span>'},
          {l:'\\left(\\right)', c:'i', a:_('parentheses'),w:'()'},
          {l:'\\left|\\right|', c:'i', a:_('absolute value'),w:'||'},
          {l:'\\pi', nb:1, a:_('pi'),pr:'<span class="mq-nonSymbola">π</span>'},
          {l:'\\infty',a:_('infinity'),pr:'<span>∞</span>'},
          {p:'DNE', 'sm':2}
        ]
      },
      {s:.1},
      {
        flow: 'row',
        s:2,
        contents: [
          {b:'&uarr;', c:'k', a:_('move up'),w:'Up'},
          {b:'&darr;', c:'k', a:_('move down'),w:'Down'},
          {b:'&larr;', c:'k', a:_('move left'),w:'Left'},
          {b:'&rarr;', c:'k', a:_('move right'),w:'Right'},
          {b:'&#x232B;', s:2, c:'k', a:_('backspace'),w:'Backspace'},
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
          {l:'\\infty',pr:'<span>∞</span>',a:_('infinity')},
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
            (calcformat.match(/(list|set\b)/) ||
            qtype.match(/(ntuple|interval)/)) ? {'b':','} : {s:1},
            ((qtype === 'calcntuple' && !calcformat.match(/vector/)) ||
              calcformat.match(/point/)) ? {l:'\\left(\\right)', c:'t', w:'(',a:_('parentheses')} : {s:1}
          ]
        };
      } else if (calcformat.match(/(fraction|mixednumber|fracordec)/) && qtype != 'numfunc') {
        baselayout.tabs[0].tabcontent[0].s = 1;
        baselayout.tabs[0].tabcontent[0].contents = [
          {l:'\\frac{n}{}', c:'t', w:'/',a:_('fraction'), pr:'<span class="mq-fraction mq-non-leaf"><span class="mq-numerator"><var>n</var></span><span class="mq-denominator mq-empty"></span><span style="display:inline-block;width:0">&#8203;</span></span>'},
          {l:'\\frac{}{}', c:'c', w:'\\frac',a:_('new fraction'), pr:'<span class="mq-fraction mq-non-leaf"><span class="mq-numerator mq-empty"></span><span class="mq-denominator mq-empty"></span><span style="display:inline-block;width:0">&#8203;</span></span>'},
          {l:'\\infty',a:_('infinity'),pr:'<span>∞</span>'},
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
            (calcformat.match(/(list|set\b)/) ||
             qtype.match(/(ntuple|interval)/)) ? {'b':','} : {s:1},
            ((qtype === 'calcntuple' && !calcformat.match(/vector/)) ||
              calcformat.match(/point/)) ? {l:'\\left(\\right)', c:'t', w:'(',a:_('parentheses')} :
              (qtype.match(/complex/) ? {b:'+'} : {s:1})
          ]
        };
      } else {
        if (calcformat.match(/(list|set\b)/) || qtype.match(/(interval|string|ntuple)/)) {
          baselayout.tabs[0].tabcontent[2].contents[14] = {'b':','};
        } else if (calcformat.match(/equation/)) { // replace , with =
          baselayout.tabs[0].tabcontent[2].contents[14] = {'b':'='};
        }
        if (calcformat.match(/nodecimal/)) {
          baselayout.tabs[0].tabcontent[2].contents[13] = {s:1};
        }
      }
      if (calcformat.match(/allowplusminus/)) {
        baselayout.tabs[6].tabcontent[0].contents[17] = {l:'\\pm',a:_('plus or minus'), pr:'<span class="mq-binary-operator">&plusmn;</span>'};
      }
      if (calcformat.match(/inequality/)) {
        //baselayout.tabs[6].tabcontent[0].contents[8].s = 0.5;
        //baselayout.tabs[6].tabcontent[0].contents.splice(9,0,  {l:'\\ne', s:0.5, pr:'<span class="mq-binary-operator">&ne;</span>'});
        baselayout.tabs[3].p = 'Ineq';
      }
    } else {
      baselayout = $.extend(true, [], underLayout3);
      if (calcformat.match(/\bdecimal/)) {
        baselayout.tabs[0].tabcontent[0].s = 3;
        baselayout.tabs[0].tabcontent[0].contents = [
          {l:'\\infty',pr:'<span>∞</span>',a:_('infinity')},
          {p:'DNE', 'sm':2},
          ((qtype === 'calcntuple' && !calcformat.match(/vector/)) ||
            calcformat.match(/point/)) ? {l:'\\left(\\right)', c:'t', w:'(',a:_('parentheses')} : {s:1}
        ];
      } else if (calcformat.match(/(fraction|mixednumber|fracordec)/)) {
        baselayout.tabs[0].tabcontent[0].s = 4;
        baselayout.tabs[0].tabcontent[0].contents = [
          {l:'\\frac{n}{}', c:'t', w:'/',a:_('fraction'), pr:'<span class="mq-fraction mq-non-leaf"><span class="mq-numerator"><var>n</var></span><span class="mq-denominator mq-empty"></span><span style="display:inline-block;width:0">&#8203;</span></span>'},
          {l:'\\frac{}{}', c:'c', w:'\\frac',a:_('new fraction'), pr:'<span class="mq-fraction mq-non-leaf"><span class="mq-numerator mq-empty"></span><span class="mq-denominator mq-empty"></span><span style="display:inline-block;width:0">&#8203;</span></span>'},
          {l:'\\infty',a:_('infinity'),pr:'<span>∞</span>'},
          {p:'DNE', 'sm':2},
        ];
        if ((qtype === 'calcntuple' && !calcformat.match(/vector/)) ||
          calcformat.match(/point/)) {
          baselayout.tabs[0].tabcontent[0].contents.push(
            {l:'\\left(\\right)', c:'t', w:'(',a:_('parentheses')},
            {s: 3}
          );
        }
      }
      
    }
    if (qtype=='numfunc' && calcformat.match(/inequality/)) {
      baselayout.tabs[3].enabled = true;
      baselayout.tabs[3].tabcontent[0].contents.splice(5,3);
      baselayout.tabs[3].tabcontent[0].s = 5;
    } else if (qtype=='string' && calcformat.match(/inequality/)) {
      baselayout.tabs[3].enabled = true;
    }
    if (!calcformat.match(/(fraction|mixednumber|fracordec|\bdecimal|logic|setexp|chemeqn)/)) {
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
    } else if (calcformat.match(/set\b/)) {
      baselayout.tabs[0].tabcontent.unshift({
        flow: 'row',
        s: 1,
        contents: [{l:'\\lbrace{\\rbrace}', c:'i', w:['\\left\\{','\\right\\}'],a:_('braces')}]
      }, {s:.1});
    } else if (qtype.match(/complex/)) {
      baselayout.tabs[0].tabcontent.unshift({
        flow: 'row',
        s: 1,
        contents: [{b: calcformat.match(/allowjcomplex/)?'j':'i', v:1}]
      }, {s:.1});
    } else if (calcformat.match(/vector/)) {
      baselayout.tabs[0].tabcontent.unshift({
        flow: 'row',
        s: 1,
        contents: [{l:'\\left\\langle\\right\\rangle', c:'i', w:['\\left\\langle','\\right\\rangle'],a:_('vector brackets')}]
      }, {s:.1});
    }
    if (calcformat.match(/logic/)) {
        baselayout.tabs[0].p = "Logic";
        baselayout.tabs[0].tabcontent[0].contents = [
            {l:'\\vee',pr:'<span class="mq-binary-operator">∨</span>'},
            {l:'\\wedge',pr:'<span class="mq-binary-operator">∧</span>'},
            {l:'\\oplus',pr:'<span class="mq-binary-operator">⊕</span>'},
            {l:'\\left(\\right)',a:_('parentheses'), c:'i', w:'()',pr:'<span class="mq-non-leaf"><span class="mq-scaled mq-paren" style="transform: scale(1, 1.2);">(</span><span class="mq-non-leaf mq-empty"></span><span class="mq-scaled mq-paren" style="transform: scale(1, 1.2);">)</span></span>'},
            {l:'\\neg',pr:'¬',a:_('negation')},
            {b:'~',a:_('tilde')},
            {l:'\\implies',pr:'<span class="mq-binary-operator">⇒</span>'},
            {l:'\\iff',a:_('if and only if'),pr:'<span class="mq-binary-operator">⇔</span>'}
        ];
        if (layoutstyle !== 'OSK') {
            baselayout.tabs[0].tabcontent[0].s = 4;
        }
    }
    if (calcformat.match(/setexp/)) {
      baselayout.tabs[0].p = "Set Exp";
      baselayout.tabs[0].tabcontent[0].contents = [
          {l:'\\cup',pr:'<span class="mq-binary-operator">∪</span>'},
          {l:'\\cap',pr:'<span class="mq-binary-operator">∩</span>'},
          {l:'\\^c',a:_('complement'),c:"w",pr:'<span class="mq-non-leaf mq-empty mq-empty-box"></span><span class="mq-supsub mq-non-leaf mq-sup-only"><span class="mq-sub"><var>c</var></span></span>'},
          {l:'\\ominus',pr:'<span class="mq-binary-operator">⊖</span>'},
          {l:'\\left(\\right)',a:_('parentheses'), c:'i', w:'()',pr:'<span class="mq-non-leaf"><span class="mq-scaled mq-paren" style="transform: scale(1, 1.2);">(</span><span class="mq-non-leaf mq-empty"></span><span class="mq-scaled mq-paren" style="transform: scale(1, 1.2);">)</span></span>'}
      ];
      if (layoutstyle !== 'OSK') {
          baselayout.tabs[0].tabcontent[0].s = 3;
      }
    }
    if (qtype=='chemeqn') {
        baselayout.tabs[0].tabcontent[0].contents = [
            {l:'x_{}', c:'t', w:'_', nb:1,a:_('subscript'), pr:'<var>x</var><span class="mq-supsub mq-non-leaf"><span class="mq-sub mq-empty"></span></span>'},
            {l:'x^{}', c:'t', w:'^', nb:1,a:_('superscript'), pr:'<var>x</var><span class="mq-supsub mq-non-leaf mq-sup-only"><span class="mq-sup mq-empty"></span></span>'},
            {l:'\\left(\\right)', c:'i', w:'()',a:_('parentheses'),pr:'<span class="mq-non-leaf"><span class="mq-scaled mq-paren" style="transform: scale(1, 1.2);">(</span><span class="mq-non-leaf mq-empty"></span><span class="mq-scaled mq-paren" style="transform: scale(1, 1.2);">)</span></span>'},
        ];
        if (calcformat.match(/reaction/)) {
            baselayout.tabs[0].tabcontent[0].contents.push(
                {l:'\\to',a:_('arrow right'),pr:'<span class="mq-binary-operator">→</span>'},
                {l:'\\rightleftharpoons',a:_('arrow left and right'),pr:'<span class="mq-binary-operator">⇌</span>'}
            );
        }
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
            p: (qtype=='chemeqn') ? 'Atoms' : 'Vars',
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
      let varlen = 0;
      varpts = vars[i].split(/_/);
      for (var j=0; j<varpts.length; j++) {
        varpts[j] = varpts[j].replace(/\b(alpha|beta|chi|delta|epsilon|gamma|varphi|phi|psi|sigma|rho|theta|lambda|mu|nu|omega|tau)\b/i,
          '\\$&');
        if (varpts[j].charAt(0)=='\\') {
          varlen++;
        } else if (varpts[j].charAt(0)=='(') {
          varlen += varpts[j].length - 2;
        } else if (varpts[j].match(/^(hat|bar|vec)\(([^\(]*?)\)$/)) {
          varlen += varpts[j].length - 5;
        } else {
          varlen += varpts[j].length;
        }
      }
      vars[i] = varpts.join('_');

      if (varlen > maxlen) {
        maxlen = varlen;
      }
      vars[i] = vars[i].replace(/_(\w{2,})/,"_{$1}");
      vars[i] = vars[i].replace(/_\(([^\(]*?)\)/,"_{$1}");
      vars[i] = vars[i].replace(/^(hat|bar|vec)\(([^\(]*?)\)/,"\\$1{$2}");

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
            }).on('click touchstart', function(e) {
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
  addCommands: {'oo': ['VanillaSymbol', '\\infty ', '&infin;'], 
                'xor': ['VanillaSymbol', '\\oplus ', '&oplus;'], 
                'uu': ['VanillaSymbol', '\\cup ', '&cup;'], 
                'nn': ['VanillaSymbol', '\\cap ', '&cap;'],
                'rightleftharpoons': ['BinaryOperator', '\\rightleftharpoons ', '&rlhar;']}
});

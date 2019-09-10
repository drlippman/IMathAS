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
              {l:'\\left(\\right)', c:'i', w:'()'},
              {l:'x^{}', c:'t', w:'^', nb:1},
              {l:'\\pi', nb:1},
              {l:'\\sqrt{}', c:'c', w:'sqrt', nb:1},
              {l:'\\infty'},
              {l:'\\sqrt[n]{}', c:'c', w:'nthroot', nb:1},
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
              {l:'\\frac{}{}', c:'t', w:'/'},
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
              {l:'\\log', c:'f'},
              {l:'\\ln', c:'f'},
              {l:'\\log_{}', c:'f'},
              {l:'e^{}', c:'t', w:'e^'},
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
              {l:'\\sin', c:'f'},
              {l:'\\cos', c:'f'},
              {l:'\\tan', c:'f'},
              {l:'\\sec', c:'f'},
              {l:'\\csc', c:'f'},
              {l:'\\cot', c:'f'},
              {l:'\\sin^{-1}', c:'f'},
              {l:'\\cos^{-1}', c:'f'},
              {l:'\\tan^{-1}', c:'f'},
              {l:'\\sinh', c:'f'},
              {l:'\\cosh', c:'f'},
              {l:'\\tanh', c:'f'}
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
              {l:'\\lt'},
              {l:'\\gt'},
              {l:'\\le'},
              {l:'\\ge'},
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
              {l:'\\infty'},
              {l:'-\\infty', c:'w'},
              {l:'\\cup'},
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
              {l:'\\left[\\right]', c:'i', w:'[]'},
              {l:'\\lbrace{\\rbrace}', c:'i', w:['\\left\\{','\\right\\}']},
              {l:'\\left\\langle\\right\\rangle', c:'i', w:['\\left\\langle','\\right\\rangle']},
              {p:'Space', s:2, c:'t', w:' '},
              {p:'%'},
              {p:','},
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
              {l:'\\frac{}{}', c:'t', w:'/'},
              {l:'x^{}', c:'t', w:'^', nb:1},
              {l:'x_{}', c:'t', w:'_', nb:1},
              {l:'\\sqrt{}', c:'c', w:'sqrt', nb:1},
              {l:'\\sqrt[n]{}', c:'c', w:'nthroot', nb:1},
              {l:'\\left(\\right)', c:'i', w:'()'},
              {l:'\\left|\\right|', c:'i', w:'||'},
              {l:'\\pi', nb:1},
              {l:'\\infty'},
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
              {l:'\\log', c:'f'},
              {l:'\\ln', c:'f'},
              {l:'\\log_{}', c:'f'},
              {l:'e^{}', c:'t', w:'e^'},
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
              {l:'\\sin', c:'f'},
              {l:'\\cos', c:'f'},
              {l:'\\tan', c:'f'},
              {l:'\\sec', c:'f'},
              {l:'\\csc', c:'f'},
              {l:'\\cot', c:'f'},
              {l:'\\sin^{-1}', c:'f'},
              {l:'\\cos^{-1}', c:'f'},
              {l:'\\tan^{-1}', c:'f'},
              {l:'\\sinh', c:'f'},
              {l:'\\cosh', c:'f'},
              {l:'\\tanh', c:'f'}
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
              {l:'\\lt'},
              {l:'\\gt'},
              {l:'\\le'},
              {l:'\\ge'},
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
              {l:'\\infty'},
              {l:'-\\infty', c:'w', w:'-\\infty'},
              {l:'\\cup'},
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
          {l:'\\frac{}{}', c:'t', w:'/'},
          {l:'x^{}', c:'t', w:'^', nb:1},
          {l:'x_{}', c:'t', w:'_', nb:1},
          {l:'\\sqrt{}', c:'c', w:'sqrt', nb:1},
          {l:'\\sqrt[n]{}', c:'c', w:'nthroot', nb:1},
          {l:'\\left(\\right)', c:'t', w:'('},
          {l:'\\left|\\right|', c:'t', w:'|', nb:1},
          {l:'\\pi', nb:1},
          {l:'\\infty'},
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
      if (calcformat.match(/(fraction|mixednumber|fracordec)/)) {
        baselayout.tabs[0].tabcontent[0].s = 1;
        baselayout.tabs[0].tabcontent[0].contents = [
          {l:'\\frac{n}{}', c:'t', w:'/'},
          {b:'\\frac{}{}', c:'c', w:'\\frac'},
          {l:'\\infty'},
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
            calcformat.match(/(list|set)/) ? {'b':','} : {s:1},
            calcformat.match(/point/) ? {l:'\\left(\\right)', c:'t', w:'('} : {s:1}
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
      if (vars.length > 0) {
        var varbtns = getVarsButtons2(vars);
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
    } else {
      baselayout = $.extend(true, [], underLayout3);
      if (calcformat.match(/(fraction|mixednumber|fracordec)/)) {
        baselayout.tabs[0].tabcontent[0].s = 4;
        baselayout.tabs[0].tabcontent[0].contents = [
          {l:'\\frac{n}{}', c:'t', w:'/'},
          {l:'\\frac{}{}', c:'c', w:'\\frac'},
          {l:'\\infty'},
          {p:'DNE', 'sm':2},
        ];
        if (calcformat.match(/point/)) {
          baselayout.tabs[0].tabcontent[0].contents.push(
            {l:'\\left(\\right)', c:'t', w:'('},
            {s: 3}
          );
        }
      }
    }
    // for both
    if (!calcformat.match(/(fraction|mixednumber|fracordec)/)) {
      baselayout.tabs[1].enabled = true;
      if (!calcformat.match(/notrig/)) {
        baselayout.tabs[2].enabled = true;
      }
    }
    if (qtype=='calcinterval') {
      if (calcformat.match(/inequality/)) {
        baselayout.tabs[3].enabled = true;
      } else {
        baselayout.tabs[4].enabled = true;
      }
    } else if (qtype=='calcmatrix' && !calcformat.match(/matrixsized/)) {
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
        contents: [{b:'i'}]
      }, {s:.1});
    } else if (calcformat.match(/vector/)) {
      baselayout.tabs[0].tabcontent.unshift({
        flow: 'row',
        s: 1,
        contents: [{l:'\\left\\langle\\right\\rangle', c:'i', w:['\\left\\langle','\\right\\rangle']}]
      }, {s:.1});
    }
    return baselayout;
  }
  var greekletters = [''];

  function getVarsButtons2(vars) {
    var maxlen = 1;
    var btns = [];
    for (var i=0; i<vars.length; i++) {
      vars[i] = vars[i].replace(/alpha|beta|chi|delta|epsilon|gamma|varphi|phi|psi|sigma|rho|theta|lambda|mu|nu|omega|tau/i,
        '\\$&');
      if (vars[i].charAt(0)!='\\' && vars[i].length > maxlen) {
        maxlen = vars[i].length;
      }
      btns.push({'b':vars[i], c:'w'});
    }
    var perrow = Math.min(8,Math.max(4, Math.ceil(vars.length/4)));
    if (vars.length%perrow !== 0) {
      btns.push({'s': perrow - vars.length%perrow});
    }
    return {
      format: (vars.length<5 && maxlen < 4) ? 'basic' : 'tab',
      btns: btns,
      perrow: perrow
    };
  }

  function getVarsButtons(vars) {
    for (var i=0; i<vars.length; i++) {
      vars[i] = vars[i].replace(/alpha|beta|chi|delta|epsilon|gamma|varphi|phi|psi|sigma|rho|theta|lambda|mu|nu|omega|tau/i,
        '\\$&');
    }
    if (vars.length<3 &&
      vars[0].length<3 &&
      vars[1].length<3
    ) {
      //put them as regular buttons.
      if (vars.length==1) {
        return {'b':vars[0]};
      } else {
        return {
          flow: 'row',
          contents: [{'b':vars[0]},{'b':vars[1]}]
        };
      }
    } else {
      var perrow = Math.min(8,Math.max(4, Math.ceil(vars.length/4)));
      var subarr = [];
      var cnt=0;
      for (nr=0;nr<Math.ceil(vars.length/perrow);nr++) {
        subarr[nr] = [];
        for (nc=0;nc<perrow;nc++) {
          if (cnt<vars.length) {
            subarr[nr][nc] = {'b':vars[cnt], c:'w'};
          } else {
            subarr[nr][nc] = {'s':1};
          }
          cnt++;
        }
      }
      return {'p':'Vars', 'panel':subarr.slice()};
    }
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
  autoCommands: 'pi theta sqrt oo',
  autoParenOperators: true,
  addCommands: {'oo': ['VanillaSymbol', '\\infty ', '&infin;']},
});

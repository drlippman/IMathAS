// CodeMirror, copyright (c) by Marijn Haverbeke and others
// Distributed under an MIT license: http://codemirror.net/LICENSE
// Adaptation of PHP mode for IMathAS's variant by David Lippman

(function(mod) {
  if (typeof exports == "object" && typeof module == "object") // CommonJS
    mod(require("../../lib/codemirror"),  require("../clike/clike"));
  else if (typeof define == "function" && define.amd) // AMD
    define(["../../lib/codemirror",  "../clike/clike"], mod);
  else // Plain browser env
    mod(CodeMirror);
})(function(CodeMirror) {
  "use strict";

  function keywords(str) {
    var obj = {}, words = str.split(" ");
    for (var i = 0; i < words.length; ++i) obj[words[i]] = true;
    return obj;
  }

  // Helper for phpString
  function matchSequence(list, end, escapes) {
    if (list.length == 0) return phpString(end);
    return function (stream, state) {
      var patterns = list[0];
      for (var i = 0; i < patterns.length; i++) if (stream.match(patterns[i][0])) {
        state.tokenize = matchSequence(list.slice(1), end);
        return patterns[i][1];
      }
      state.tokenize = phpString(end, escapes);
      return "string";
    };
  }
  function phpString(closing, escapes) {
    return function(stream, state) { return phpString_(stream, state, closing, escapes); };
  }
  function phpString_(stream, state, closing, escapes) {
    // "Complex" syntax
    if (escapes !== false && stream.match("${", false) || stream.match("{$", false)) {
      state.tokenize = null;
      return "string";
    }

    // Simple syntax
    if (escapes !== false && stream.match(/^\$[a-zA-Z_][a-zA-Z0-9_]*/)) {
      // After the variable name there may appear array or object operator.
      if (stream.match("[", false)) {
        // Match array operator
        state.tokenize = matchSequence([
          [["[", null]],
          [[/\d[\w\.]*/, "number"],
           [/\$[a-zA-Z_][a-zA-Z0-9_]*/, "variable-2"],
           [/[\w\$]+/, "variable"]],
          [["]", null]]
        ], closing, escapes);
      }
      if (stream.match(/\-\>\w/, false)) {
        // Match object operator
        state.tokenize = matchSequence([
          [["->", null]],
          [[/[\w]+/, "variable"]]
        ], closing, escapes);
      }
      return "variable-2";
    }

    var escaped = false;
    // Normal string
    while (!stream.eol() &&
           (escaped || escapes === false ||
            (!stream.match("{$", false) &&
             !stream.match(/^(\$[a-zA-Z_][a-zA-Z0-9_]*|\$\{)/, false)))) {
      if (!escaped && stream.match(closing)) {
        state.tokenize = null;
        state.tokStack.pop(); state.tokStack.pop();
        break;
      }
      escaped = stream.next() == "\\" && !escaped;
    }
    return "string";
  }

  var imathasKeywords = "and array as break case catch continue do else elseif " +
    "for foreach if or off switch try while where empty isset list";
  var imathasAtoms = "true false null TRUE FALSE NULL";
  var imathasBuiltin = "exp sec csc cot sech csch coth nthlog sinn cosn tann secn cscn cotn rand rrand rands rrands randfrom randsfrom jointrandfrom diffrandsfrom nonzerorand nonzerorrand nonzerorands nonzerorrands diffrands diffrrands nonzerodiffrands nonzerodiffrrands singleshuffle jointshuffle makepretty makeprettydisp makeprettynegative showplot addlabel showarrays horizshowarrays showasciisvg listtoarray arraytolist calclisttoarray sortarray consecutive gcd lcm calconarray mergearrays sumarray dispreducedfraction diffarrays intersectarrays joinarray unionarrays count polymakepretty polymakeprettydisp makexpretty makexprettydisp calconarrayif in_array prettyint prettyreal prettysmallnumber prettysigfig roundsigfig arraystodots subarray showdataarray arraystodoteqns array_flip arrayfindindex fillarray array_reverse root getsnapwidthheight is_numeric sign prettynegs dechex hexdec numtowords fractowords randname randnamewpronouns randmalename randfemalename randnames randmalenames randfemalenames randcity randcities prettytime definefunc evalfunc safepow arrayfindindices stringtoarray strtoupper strtolower ucfirst makereducedfraction makereducedmixednumber stringappend stringprepend textonimage addplotborder addlabelabs makescinot today numtoroman sprintf arrayhasduplicates addfractionaxislabels decimaltofraction ifthen multicalconarray htmlentities formhoverover formpopup connectthedots jointsort stringpos stringlen stringclean substr substr_count str_replace makexxpretty makexxprettydisp forminlinebutton makenumberrequiretimes comparenumbers comparefunctions getnumbervalue showrecttable htmldisp getstuans checkreqtimes stringtopolyterms getfeedbacktxt getfeedbacktxtessay getfeedbacktxtnumber explode gettwopointlinedata getineqdata getdotsdata getopendotsdata gettwopointdata getlinesdata adddrawcommand array_unique array_values ABarray scoremultiorder scorestring randstate randstates randpythag";
  imathasBuiltin += " nCr nPr mean stdev percentile Nplus1percentile quartile TIquartile Excelquartile Nplus1quartile allquartile median freqdist frequency histogram fdhistogram fdbargraph normrand expdistrand boxplot normalcdf tcdf invnormalcdf invtcdf invtcdf2 linreg expreg countif binomialpdf binomialcdf chicdf invchicdf chi2cdf invchi2cdf fcdf invfcdf piechart mosaicplot checklineagainstdata chi2teststat checkdrawnlineagainstdata ineqplot ineqbetweenplot linegraph linegraphbrackets forminterval intervalstodraw reduceradical reduceradicalfrac reducequadraticform matrix matrixformat matrixformatfrac matrixsystemdisp matrixsum matrixdiff matrixscalar matrixprod matrixaugment matrixrowscale matrixrowswap matrixrowcombine matrixrowcombine3 matrixidentity matrixtranspose matrixrandinvertible matrixrandunreduce matrixinverse matrixinversefrac matrixsolve matrixsolvefrac polyregression matrixgetentry matrixgetrow matrixgetcol matrixgetsubmatrix matrixdisplaytable matrixreduce matrixnumsolutions matrixround matrixGetRank arrayIsZeroVector matrixFormMatrixFromEigValEigVec matrixIsRowsLinInd matrixIsColsLinInd matrixIsEigVec matrixIsEigVal matrixGetRowSpace matrixGetColumnSpace matrixAxbHasSolution matrixAspansB matrixAbasisForB matrixGetMinor matrixDet matrixRandomMatrix matrixRandomSpan matrixNumberOfRows matrixNumberOfColumns matrixParseStuans dotp crossp vecnorm vecsum vecdiff vecprod veccompareset veccomparesamespan csvdownloadlink";
  imathasBuiltin += " loadlibrary importcodefrom includecodefrom setseed replacealttext randcountry randcountries";
  imathasBuiltin += " sin cos tan sinh cosh tanh arcsin arccos arctan arcsinh arccosh arctanh sqrt ceil floor round log ln abs max min count";
  imathasBuiltin += " mergeplots gamma_cdf gamma_inv beta_cdf beta_inv anova1way_f anova1way anova2way anova_table anova2way_f changeimagesize addimageborder";

  var imathasSpecialVars = keywords("$abstolerance $ansprompt $anstypes $answeights $answer $answerbox $answerboxsize $answerformat $answers $answersize $answertitle $background $displayformat $domain $formatfeedbackon $grid $hidepreview $hidetips $matchlist $noshuffle $partialcredit $partweights $previewloc $questions $questiontitle $reltolerance $reqdecimals $reqsigfigs $requiretimes $requiretimeslistpart $scoremethod $showanswer $showanswerloc $snaptogrid $strflags $variables");
  var imathasDisallowedVars = keywords("$link $qidx $qnidx $seed $qdata $toevalqtxt $la $laarr $shanspt $GLOBALS $laparts $anstype $kidx $iidx $tips $optionsPack $partla $partnum $score $disallowedvar $allowedmacros $wherecount $countcnt $myrights $myspecialrights");
  CodeMirror.registerHelper("hintWords", "php", [imathasKeywords, imathasAtoms, imathasBuiltin].join(" ").split(" "));
  CodeMirror.registerHelper("wordChars", "php", /[\w$]/);

  var phpConfig = {
    name: "clike",
    helperType: "php",
    keywords: keywords(imathasKeywords),
    blockKeywords: keywords("catch do else elseif for foreach if switch try while finally"),
    defKeywords: keywords("class function interface namespace trait"),
    atoms: keywords(imathasAtoms),
    builtin: keywords(imathasBuiltin),
    multiLineStrings: true,
    hooks: {
      "$": function(stream) {
        stream.eatWhile(/[\w\$_]/);
        if (imathasSpecialVars.propertyIsEnumerable(stream.current())) {
        	return "variable-3";
        } else if (imathasDisallowedVars.propertyIsEnumerable(stream.current())) {
        	return "error";
        } else {
        	return "variable-2";
        }
      },
      "<": function(stream, state) {
        var before;
        if (before = stream.match(/<<\s*/)) {
          var quoted = stream.eat(/['"]/);
          stream.eatWhile(/[\w\.]/);
          var delim = stream.current().slice(before[0].length + (quoted ? 2 : 1));
          if (quoted) stream.eat(quoted);
          if (delim) {
            (state.tokStack || (state.tokStack = [])).push(delim, 0);
            state.tokenize = phpString(delim, quoted != "'");
            return "string";
          }
        }
        return false;
      },
      "#": function(stream) {
        while (!stream.eol() && !stream.match("?>", false)) stream.next();
        return "comment";
      },
      "/": function(stream) {
        if (stream.eat("/")) {
          while (!stream.eol() && !stream.match("?>", false)) stream.next();
          return "comment";
        }
        return false;
      },
      '"': function(_stream, state) {
        (state.tokStack || (state.tokStack = [])).push('"', 0);
        state.tokenize = phpString('"');
        return "string";
      },
      "{": function(_stream, state) {
        if (state.tokStack && state.tokStack.length)
          state.tokStack[state.tokStack.length - 1]++;
        return false;
      },
      "}": function(_stream, state) {
        if (state.tokStack && state.tokStack.length > 0 &&
            !--state.tokStack[state.tokStack.length - 1]) {
          state.tokenize = phpString(state.tokStack[state.tokStack.length - 2]);
        }
        return false;
      },
      indent: function(state,ctx,textAfter) {
      	      //only do indent on { } or non-statements
      	      if (!state.prevToken.match(/[\{\}]/) && ctx.type=="statement") {
      	      	   return state.indented;
      	      }
      }
    }
  };

  CodeMirror.defineMIME("text/x-imathas", phpConfig);
});


// CodeMirror, copyright (c) by Marijn Haverbeke and others
// Distributed under an MIT license: http://codemirror.net/LICENSE
// Adaptation of overlay mode to highlight PHP variables in html

(function(mod) {
  if (typeof exports == "object" && typeof module == "object") // CommonJS
    mod(require("../../lib/codemirror"));
  else if (typeof define == "function" && define.amd) // AMD
    define(["../../lib/codemirror"], mod);
  else // Plain browser env
    mod(CodeMirror);
})(function(CodeMirror) {
"use strict";

CodeMirror.overlayMode = function(base, overlay, combine) {
  return {
    startState: function() {
      return {
        base: CodeMirror.startState(base),
        overlay: CodeMirror.startState(overlay),
        basePos: 0, baseCur: null,
        overlayPos: 0, overlayCur: null,
        streamSeen: null
      };
    },
    copyState: function(state) {
      return {
        base: CodeMirror.copyState(base, state.base),
        overlay: CodeMirror.copyState(overlay, state.overlay),
        basePos: state.basePos, baseCur: null,
        overlayPos: state.overlayPos, overlayCur: null
      };
    },

    token: function(stream, state) {
      if (stream != state.streamSeen ||
          Math.min(state.basePos, state.overlayPos) < stream.start) {
        state.streamSeen = stream;
        state.basePos = state.overlayPos = stream.start;
      }

      if (stream.start == state.basePos) {
        state.baseCur = base.token(stream, state.base);
        state.basePos = stream.pos;
      }
      if (stream.start == state.overlayPos) {
        stream.pos = stream.start;
        state.overlayCur = overlay.token(stream, state.overlay);
        state.overlayPos = stream.pos;
      }
      stream.pos = Math.min(state.basePos, state.overlayPos);

      // state.overlay.combineTokens always takes precedence over combine,
      // unless set to null
      if (state.overlayCur == null) return state.baseCur;
      else if (state.baseCur != null &&
               state.overlay.combineTokens ||
               combine && state.overlay.combineTokens == null)
        return state.baseCur + " " + state.overlayCur;
      else return state.overlayCur;
    },

    indent: base.indent && function(state, textAfter) {
      return base.indent(state.base, textAfter);
    },
    electricChars: base.electricChars,

    innerMode: function(state) { return {state: state.base, mode: base}; },

    blankLine: function(state) {
      if (base.blankLine) base.blankLine(state.base);
      if (overlay.blankLine) overlay.blankLine(state.overlay);
    }
  };
};

CodeMirror.defineMode("imathasqtext", function(config, parserConfig) {
  var imathasqtextOverlay = {
    token: function(stream, state) {
      var ch,curdepth;
      if (stream.match(/^\{\$[a-zA-Z_]/)) {
      	  curdepth=0;
      	  while ((ch = stream.next()) != null) {
      	  	  if (ch=='[') {
      	  	  	  curdepth++;
		  } else if (ch==']') {
			  curdepth--;
		  } else if (ch=="}") {
      	  	  	  stream.eat("}");
      	  	  	  if (curdepth>0) {
      	  	  	  	  return "error";
      	  	  	  } else {
      	  	  	  	  return "variable-2";
      	  	  	  }
      	  	  } else if (curdepth==0 && ch.match(/[^\w\$]/)) {
      	  	  	 stream.backUp(1);
      	  	  	 return "error";
      	  	  }
      	  }
      	  return "error";
      } else if (stream.match(/^\$[a-zA-Z_]/)) {
      	curdepth=0;
        while ((ch = stream.next()) != null) {
          if (ch=='[') {
          	  curdepth++;
          } else if (ch==']') {
          	  curdepth--;
          	  if (curdepth==0) {
          	  	  return "variable-2";
          	  }
          } else if (curdepth==0 && (stream.peek()==null || ch.match(/\W/))) {
          	  if (ch.match(/\W/)) {
          	  	  stream.backUp(1);
          	  }
          	  return "variable-2";
          } else if (curdepth>0 && ch.match(/[^\w\$]/)) {
          	  stream.backUp(1);
          	  return "error";
          }
        }
        if (ch==null) {return "variable-2";}
      }
      while (stream.next() != null && !stream.match(/^\$[a-zA-Z_]/, false) && !stream.match(/^\{\$[a-zA-Z_]/, false)) {}
      return null;
    }
  };
  return CodeMirror.overlayMode(CodeMirror.getMode(config, parserConfig.backdrop || "text/html"), imathasqtextOverlay);
});


});

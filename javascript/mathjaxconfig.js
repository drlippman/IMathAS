window.MathJax = {
    loader: {
        load: ["input/asciimath", "output/chtml", "ui/menu"]
    },
    options: {
        ignoreHtmlClass: "skipmathrender",
        renderActions: {
            addattr: [150,
                function (doc) {for (const math of doc.math) {MathJax.config.addDataAttr(math, doc)}},
                function (math, doc) {MathJax.config.addDataAttr(math, doc)}
            ]
        }
    },
    addDataAttr: function (math, doc) {
        math.typesetRoot.setAttribute("data-asciimath", math.math);
    },
    startup: {
        ready: function() {
        var AM = MathJax.InputJax.AsciiMath.AM;
        AM.newsymbol({input: "o-", tag:"mo", output:"\u2296", ttype:AM.TOKEN.CONST});
        AM.newsymbol({input: "ominus", tag:"mo", output:"\u2296", ttype:AM.TOKEN.CONST});
        AM.newsymbol({input: "rightleftharpoons", tag:"mo", output:"\u21CC", ttype:AM.TOKEN.CONST});
        AM.newsymbol({input: "hbar", tag:"mi", output:"\u210F", ttype:AM.TOKEN.CONST});
        ["arcsec","arccsc","arccot"].forEach(function(v) {
            AM.newsymbol({input:v, tag:"mi", output:v, ttype:AM.TOKEN.UNARY, func:true});
        });
        MathJax.startup.defaultReady();
        }
    }
};

if (mathjaxdisp == 8) {
    window.MathJax.loader.load.push("a11y/semantic-enrich");
    window.MathJax.options['sre'] = {speech:"shallow"};
}

var noMathRender = false; var usingASCIIMath = true; var AMnoMathML = true; 
var MathJaxCompatible = true; var mathRenderer="MathJax";

function rendermathnode(node,callback) {
    if (window.MathJax && window.MathJax.typesetPromise) {
        if (typeof callback != "function") {
            callback = function () {};
        }
        MathJax.typesetClear([node]);
        MathJax.typesetPromise([node]).then(sendLTIresizemsg).then(callback);
    } else {
        setTimeout(function() {rendermathnode(node, callback);}, 100);
    }
}

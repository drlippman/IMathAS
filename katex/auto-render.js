function setupKatexAutoRender() {

var findEndOfMath = function(delimiter, text, startIndex) {
    // Adapted from
    // https://github.com/Khan/perseus/blob/master/src/perseus-markdown.jsx

    var index = startIndex;
    var braceLevel = 0;

    var delimLength = delimiter.length;

    while (index < text.length) {
        var character = text[index];

        //asciimath doesn't require matched braces
        if ((braceLevel <= 0 || delimiter === "`") &&
            text.slice(index, index + delimLength) === delimiter) {
            return index;
        } else if (character === "\\") {
            index++;
        } else if (character === "{") {
            braceLevel++;
        } else if (character === "}") {
            braceLevel--;
        }

        index++;
    }

    return -1;
};

var splitAtDelimiters = function(startData, leftDelim, rightDelim, display, format) {
    var finalData = [];

    for (var i = 0; i < startData.length; i++) {
        if (startData[i].type === "text") {
            var text = startData[i].data;

            var lookingForLeft = true;
            var currIndex = 0;
            var nextIndex;

            nextIndex = text.indexOf(leftDelim);
            while (nextIndex > 0 && text[nextIndex-1] === "\\") {
            	nextIndex = text.indexOf(leftDelim,nextIndex+1);
            }
            if (nextIndex !== -1) {
                currIndex = nextIndex;
                finalData.push({
                    type: "text",
                    data: text.slice(0, currIndex)
                });
                lookingForLeft = false;
            }

            while (true) {
                if (lookingForLeft) {
                    nextIndex = text.indexOf(leftDelim, currIndex);
                    while (nextIndex > 0 && text[nextIndex-1] === "\\") {
			nextIndex = text.indexOf(leftDelim,nextIndex+1);
		    }
                    if (nextIndex === -1) {
                        break;
                    }

                    finalData.push({
                        type: "text",
                        data: text.slice(currIndex, nextIndex)
                    });

                    currIndex = nextIndex;
                } else {
                    nextIndex = findEndOfMath(
                        rightDelim,
                        text,
                        currIndex + leftDelim.length);
                    if (nextIndex === -1) {
                        break;
                    }

                    finalData.push({
                        type: "math",
                        data: text.slice(
                            currIndex + leftDelim.length,
                            nextIndex),
                        rawData: text.slice(
                            currIndex,
                            nextIndex + rightDelim.length),
                        display: display,
                        format: format
                    });

                    currIndex = nextIndex + rightDelim.length;
                }

                lookingForLeft = !lookingForLeft;
            }

            finalData.push({
                type: "text",
                data: text.slice(currIndex)
            });
        } else {
            finalData.push(startData[i]);
        }
    }

    return finalData;
};

var splitWithDelimiters = function(text, delimiters) {
    var data = [{type: "text", data: text}];
    for (var i = 0; i < delimiters.length; i++) {
        var delimiter = delimiters[i];
        data = splitAtDelimiters(
            data, delimiter.left, delimiter.right,
            delimiter.display || false, delimiter.format);
    }
    return data;
};
var normalizemathunicode = function(str) {
	str = str.replace(/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/g, "");
	str = str.replace(/\u2013|\u2014|\u2015|\u2212/g, "-");
	str = str.replace(/\u2044|\u2215/g, "/");
	str = str.replace(/∞/g,"oo").replace(/≤/g,"<=").replace(/≥/g,">=").replace(/∪/g,"U");
	str = str.replace(/±/g,"+-").replace(/÷/g,"/").replace(/·|✕|×|⋅/g,"*");
	str = str.replace(/√/g,"sqrt").replace(/∛/g,"root(3)");
	str = str.replace(/²/g,"^2").replace(/³/g,"^3");
	str = str.replace(/\bOO\b/i,"oo");
	str = str.replace(/θ/,"theta").replace(/φ/,"phi").replace(/π/,"pi").replace(/σ/,"sigma").replace(/μ/,"mu")
	str = str.replace(/α/,"alpha").replace(/β/,"beta").replace(/γ/,"gamma").replace(/δ/,"delta").replace(/ε/,"epsilon").replace(/κ/,"kappa");
	str = str.replace(/λ/,"lambda").replace(/ρ/,"rho").replace(/τ/,"tau").replace(/χ/,"chi").replace(/ω/,"omega");
	str = str.replace(/Ω/,"Omega").replace(/Γ/,"Gamma").replace(/Φ/,"Phi").replace(/Δ/,"Delta").replace(/Σ/,"Sigma");
	return str;
}
var renderMathInText = function(text, delimiters) {
    var data = splitWithDelimiters(text, delimiters);

    var fragment = document.createDocumentFragment();

    for (var i = 0; i < data.length; i++) {
        if (data[i].type === "text") {
            fragment.appendChild(document.createTextNode(data[i].data.replace("\\`","`")));
        } else {
            var span = document.createElement("span");
            var math = normalizemathunicode(data[i].data);
            if (data[i].format == "asciimath") {
            	    math = "\\displaystyle "+AMTparseAMtoTeX(math);
            } else if (math.indexOf("\\displaystyle")==-1) {
            	    math = "\\displaystyle "+math;
            }
            math = math.replace(/\$/g,'\\$');
            try {
            	//bit of a hack since katex can't handle the alignment pieces of matrices
                katex.render(math.replace(/matrix}{[clr]+}/,'matrix}'), span, {
                    displayMode: data[i].display
                });
                if (data[i].format == "asciimath") {
                	span.setAttribute("data-asciimath", data[i].data);
                } else {
                	span.setAttribute("data-tex", data[i].data);
                }
            } catch (e) {
                if (!(e instanceof katex.ParseError)) {
                    throw e;
                }
                span.className = "mj";
                if (data[i].format == "asciimath") {
                	span.innerHTML = "`"+data[i].data+"`";
                } else {
                	span.innerHTML = "\\("+data[i].data+"\\)";
                }
                if (typeof MathJax != "undefined" && MathJax.version) {
                	MathJax.Hub.Queue(["Typeset",MathJax.Hub,span]);
                	usedMathJax = true;
                }
            }
            fragment.appendChild(span);
        }
    }

    return fragment;
};

var renderElem = function(elem, delimiters, ignoredTags, ignoreClassRegex) {
    for (var i = 0; i < elem.childNodes.length; i++) {
        var childNode = elem.childNodes[i];
        if (childNode.nodeType === 3) {
            // Text node
            var frag = renderMathInText(childNode.textContent, delimiters);
            i += frag.childNodes.length - 1;
            elem.replaceChild(frag, childNode);
        } else if (childNode.nodeType === 1) {
            // Element node
            var shouldRender = ignoredTags.indexOf(
                childNode.nodeName.toLowerCase()) === -1;

            if (ignoreClassRegex !== null) {
            	    shouldRender = shouldRender && !ignoreClassRegex.test(childNode.getAttribute("class"));
            }
            if (shouldRender) {
                renderElem(childNode, delimiters, ignoredTags, ignoreClassRegex);
            }
        }
        // Otherwise, it's something else, and ignore it.
    }
};

var defaultOptions = {
    delimiters: [
        {left: "`", right: "`", display: false, format: "asciimath"},
        {left: "[latex]", right: "[/latex]", display: false, format: "tex"}
    ],

    ignoredTags: [
        "script", "noscript", "style", "textarea", "pre", "code"
    ],

    ignoreClass: "skipmathrender"
};

var extend = function(obj) {
    // Adapted from underscore.js' `_.extend`. See LICENSE.txt for license.
    var source, prop;
    for (var i = 1, length = arguments.length; i < length; i++) {
        source = arguments[i];
        for (prop in source) {
            if (Object.prototype.hasOwnProperty.call(source, prop)) {
                obj[prop] = source[prop];
            }
        }
    }
    return obj;
};

var usedMathJax;
var renderMathInElement = function(elem, options) {
    if (!elem) {
        throw new Error("No element provided to render");
    }

    options = extend({}, defaultOptions, options);
    if (options.ignoreClass.length>0) {
    	    options.ignoreClassRegex = new RegExp("\\b("+options.ignoreClass+")\\b");
    } else {
    	    options.ignoreClassRegex = null;
    }
    usedMathJax = false;

    renderElem(elem, options.delimiters, options.ignoredTags, options.ignoreClassRegex);
    if (window.hasOwnProperty("katexDoneCallback")) {
    	    if (usedMathJax && typeof MathJax != "undefined") {
    	    	    MathJax.Hub.Queue(window.katexDoneCallback);
    	    } else {
    	    	    window.katexDoneCallback();
    	    }
    }
};

window.renderMathInElement = renderMathInElement;
window.rendermathnode = function (node, callback) {
  renderMathInElement(node);
  if(typeof callback=='function'){callback();}
}
$(function() {
	renderMathInElement(document.body);
});

};

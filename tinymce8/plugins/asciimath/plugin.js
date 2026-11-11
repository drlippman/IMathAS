/**
 * ASCIIMath Plugin for TinyMCE editor v8
 *   port of ASCIIMath plugin for HTMLArea written by
 *   David Lippman & Peter Jipsen
 *
 * @author David Lippman
 * @copyright Copyright © 2008 David Lippman.
 *
 * Updated for TinyMCE v8
 */

(function() {
    'use strict';

    tinymce.PluginManager.add('asciimath', function(editor, url) {
        var lastAMnode = null;
        var loaded = false;
        var preventAMrender = false;
        var justinserted = null;

        // Helper function to test AM class
        function testAMclass(el) {
            return el && (el.className === 'AM' || el.className === 'AMedit');
        }

        // Clean math function
        function cleanmath(str) {
            return str.replace(/`/g, "")
                .replace(/<([^>]*)>/g, "")
                .replace(/&(m|n)dash;/g, "-")
                .replace(/&?nbsp;?/g, " ")
                .replace(/&(.*?);/g, function(m, p) {
                    if (p === 'lt' || p === 'gt') {
                        return m;
                    } else {
                        return p;
                    }
                });
        }

        // Convert math to ascii
        function math2ascii(el) {
            // Fast path for rendered math: skip serializing innerHTML, which
            // contains a large data: URI that is expensive to serialize/regex
            if (el.firstElementChild && el.title !== '' && el.title.indexOf('math') === -1 &&
                el.title.indexOf('img') === -1 && el.title.indexOf('`') === -1
            ) {
                el.innerHTML = "`" + cleanmath(el.title.replace(/>/g, "&gt;").replace(/</g, "&lt;")) + "`";
                return;
            }
            var myAM = el.innerHTML;
            if (myAM.indexOf("`") === -1) {
                if (el.title !== '' && el.title.indexOf('math') === -1 && el.title.indexOf('img') === -1) {
                    myAM = el.title;
                } else if (myAM.indexOf('title') === -1) {
                    myAM = myAM.replace(/.+alt=\"(.*?)\".+/g, "$1");
                    myAM = myAM.replace(/.+alt=\'(.*?)\'.+/g, "$1");
                    myAM = myAM.replace(/.+alt=([^>]*?)\s.*>.*/g, "$1");
                    myAM = myAM.replace(/.+alt=(.*?)>.*/g, "$1");
                } else {
                    myAM = myAM.replace(/.+title=\"(.*?)\".+/g, "$1");
                    myAM = myAM.replace(/.+title=\'(.*?)\'.+/g, "$1");
                    myAM = myAM.replace(/.+title=([^>]*?)\s.*>.*/g, "$1");
                    myAM = myAM.replace(/.+title=(.*?)>.*/g, "$1");
                }
                myAM = myAM.replace(/<span[^>]*>(.*?)<\/span>/, "$1");
                myAM = myAM.replace(/<br\s*\/>/, " ");
                myAM = myAM.replace(/>/g, "&gt;");
                myAM = myAM.replace(/</g, "&lt;");
                myAM = "`" + cleanmath(myAM) + "`";
                el.innerHTML = myAM;
            }
        }

        // Convert MathJax to ascii
        function mathjax2ascii(el) {
            var myAM = el.getAttribute("data-asciimath");
            var attr = el.attributes;
            var i = attr.length;
            while (i--) {
                el.removeAttribute(attr[i].name);
            }
            el.className = "AM";
            el.title = myAM;
            el.innerHTML = "`" + myAM + "`";
        }

        // Cache of rendered SVG data URIs, keyed by AsciiMath string. Content is
        // un-rendered and re-rendered on every exec command, so avoid reconverting.
        var svgCache = new Map();
        var SVGCACHEMAX = 500;

        // Build a standalone <img> from cached {src, va}
        function cachedToImg(entry, str) {
            var img = document.createElement('img');
            img.className = 'AMimg';
            // Tells TinyMCE's image scanner/auto-uploader to leave this image
            // alone (otherwise it gets blob-cached, uploaded and rewritten)
            img.setAttribute('data-mce-placeholder', '1');
            img.src = entry.src;
            img.title = str;
            img.alt = str;
            if (entry.va) {
                img.style.verticalAlign = entry.va;
            }
            return img;
        }

        // In-flight conversions keyed by AsciiMath string, so duplicates queued
        // before the first finishes share one conversion
        var svgPending = new Map();

        // Styles MathJax's SVG output needs, inlined into each standalone SVG
        // since an <img> can't see the page's stylesheets
        var MJ_SVG_CSS = [
            'svg a{fill:blue;stroke:blue}',
            '[data-mml-node="merror"]>g{fill:red;stroke:red}',
            '[data-mml-node="merror"]>rect[data-background]{fill:yellow;stroke:none}',
            '[data-frame],[data-line]{stroke-width:70px;fill:none}',
            '.mjx-dashed{stroke-dasharray:140}',
            '.mjx-dotted{stroke-linecap:round;stroke-dasharray:0,140}',
            'use[data-c],path[data-c]{stroke-width:3px}'
        ].join('');

        // Build a cache entry {src, va} from MathJax's SVG output, and cache it
        function mjSvgToEntry(svg, str) {
            svg = svg.cloneNode(true);
            svg.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
            // strip semantic/speech enrichment; unused in an <img>.
            // Keep data-mml-node / data-table / data-line, which MathJax's
            // stylesheet selectors rely on.
            svg.querySelectorAll('*').forEach(function (el) {
                Array.from(el.attributes).forEach(function (a) {
                    if (/^data-(semantic|speech)/.test(a.name)) {
                        el.removeAttribute(a.name);
                    }
                });
            });
            // An <img> doesn't see the page's stylesheets, so copy over the MathJax
            // rules that target SVG internals
            var styleEl = document.createElementNS('http://www.w3.org/2000/svg', 'style');
            styleEl.textContent = MJ_SVG_CSS;
            svg.insertBefore(styleEl, svg.firstChild);
            svg.removeAttribute('aria-hidden');
            svg.removeAttribute('role');
            svg.removeAttribute('focusable');
            // glyphs may reference a shared global cache that an <img> can't see
            var cache = document.querySelector('svg defs[id$="global-cache"]');
            var uses = svg.querySelectorAll('use');
            if (cache && uses.length) {
                // copy only the glyph defs this SVG references, not the whole
                // page-wide cache (which makes every data URI enormous)
                var needed = {};
                uses.forEach(function (u) {
                    var h = u.getAttribute('href') || u.getAttribute('xlink:href');
                    if (h && h.charAt(0) === '#') {
                        needed[h.substring(1)] = true;
                    }
                });
                var newcache = cache.cloneNode(false);
                Array.from(cache.children).forEach(function (c) {
                    if (needed[c.getAttribute('id')]) {
                        newcache.appendChild(c.cloneNode(true));
                    }
                });
                svg.insertBefore(newcache, svg.firstChild);
            }
            // an <img> doesn't inherit text color
            var svgText = new XMLSerializer().serializeToString(svg)
                .replace(/currentColor/g, '#000000');
            var entry = {
                src: 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svgText),
                va: svg.style.verticalAlign
            };
            if (svgCache.size >= SVGCACHEMAX) {
                svgCache.delete(svgCache.keys().next().value);
            }
            svgCache.set(str, entry);
            return entry;
        }

        // Render via MathJax SVG if available. Returns a promise, or null if unavailable
        function renderWithMathJaxSVG(outnode, str) {
            if (typeof MathJax === 'undefined') {
                return null;
            }
            var cached = svgCache.get(str);
            if (cached) {
                // apply synchronously; no need to wait on MathJax
                outnode.innerHTML = '';
                outnode.appendChild(cachedToImg(cached, str));
                outnode.title = str;
                return Promise.resolve();
            }
            function convert() {
                // the asciimath2svg helpers use whatever output jax the page configured
                var oj = MathJax.startup && MathJax.startup.output;
                if (!oj || oj.name !== 'SVG') {
                    throw new Error('MathJax is not using SVG output');
                }
                // MJ4 promise version waits on dynamic font loading
                var conv = MathJax.asciimath2svgPromise ||
                    (MathJax.asciimath2svg && function (s) { return Promise.resolve(MathJax.asciimath2svg(s)); });
                if (!conv) {
                    throw new Error('MathJax asciimath2svg not available');
                }
                // Stop the render pipeline at TYPESET so the a11y explorer (which runs
                // later, and fails on this detached node) never attaches.
                var opts = {};
                try {
                    opts.end = MathJax._.core.MathItem.STATE.TYPESET;
                } catch (e) {}
                return conv(cleanmath(str), opts);
            }
            var ready;
            if (MathJax.asciimath2svgPromise || MathJax.asciimath2svg) {
                ready = Promise.resolve();
            } else if (MathJax.startup && MathJax.startup.promise) {
                // MathJax still loading; convenience methods get defined at startup
                ready = MathJax.startup.promise;
            } else {
                return null;
            }
            var pending = svgPending.get(str);
            if (!pending) {
                pending = ready.then(convert).then(function (container) {
                    var svg = container.querySelector('svg');
                    if (!svg) {
                        throw new Error('no svg in MathJax output');
                    }
                    return mjSvgToEntry(svg, str);
                });
                svgPending.set(str, pending);
                var clear = function () { svgPending.delete(str); };
                pending.then(clear, clear);
            }
            return pending.then(function (entry) {
                outnode.innerHTML = '';
                outnode.appendChild(cachedToImg(entry, str));
                outnode.title = str;
            });
        }

        // Convert node to AM
        function nodeToAM(outnode) {
            var str = outnode.innerHTML.replace(/`/g, "");
            str = str.replace(/<span[^>]*>(.*?)<\/span>/, "$1")
                .replace(/<br\s*\/>/, " ")
                .replace(/<img[^>]*title="(.*?)".*?>/g, "$1");
            str = str.replace(/[\u200B-\u200D\uFEFF]/g, '');
            
            var mjpromise = renderWithMathJaxSVG(outnode, str);
            if (mjpromise) {
                mjpromise.catch(function () {
                    legacyNodeToAM(outnode, str);
                });
                return;
            }
            legacyNodeToAM(outnode, str);
        }

        // Render via ASCIIMathTeXImg.js and an external image service
        function legacyNodeToAM(outnode, str) {
            if (tinymce.Env.ie) {
                var newAM = document.createElement("span");
                newAM.className = "AM";
                if (typeof AMTparseMath !== 'undefined') {
                    newAM.appendChild(AMTparseMath(str));
                }
                outnode.innerHTML = newAM.innerHTML;
                outnode.title = str;
            } else {
                var myAM = "`" + cleanmath(str) + "`";
                outnode.innerHTML = myAM;
                if (typeof AMprocessNode !== 'undefined') {
                    AMprocessNode(outnode);
                }
                outnode.title = myAM.replace(/`/g, "");
            }
            if (window.AMTcgilocUseSVG) {
                // set vertical-align
                var imgnode = outnode.querySelector('img');
                if (imgnode) {
                    $.get({
                        url: imgnode.src, 
                        dataType: 'text'
                    }).done(function (svgText) {
                        var va = svgText.match(/vertical-align:\s*([\-\.\d\w]+);/)[1]
                        imgnode.style.verticalAlign = va;
                    });
                }
            }
        }

        // Wrap image
        function imgwrap(imgnode) {
            var p = editor.dom.getParent(imgnode, testAMclass);
            if (p === null) {
                var newAM = document.createElement("span");
                newAM.className = "AM";
                var rimgnode = imgnode.parentNode.replaceChild(newAM, imgnode);
                newAM.appendChild(rimgnode);
                p = newAM;
            }
        }

        // Register commands
        editor.addCommand('mceAsciimath', function(ui,val) {
            if (lastAMnode === null) {
                var existing = editor.selection.getContent();
                if (existing.indexOf('class=AM') === -1) {
                    existing = existing.replace(/<([^>]*)>/g, "");
                    existing = existing.replace(/&(m|n)dash;/g, "-");
                    existing = existing.replace(/&?nbsp;?/g, " ");
                    existing = existing.replace(/&(.*?);/g, "$1");
                    if (val) {
                        existing = val;
                    }
                    var entity = '<span class="AMedit">`' + existing + '<span id="removeme"></span>`</span>&nbsp;';

                    editor.insertContent(entity);

                    var removeElement = editor.dom.get('removeme');
                    if (removeElement) {
                        editor.selection.setCursorLocation(removeElement, 0);
                        editor.dom.remove('removeme');
                    }
                    justinserted = true;
                    editor.nodeChanged();
                    justinserted = null;
                }
            } else if (val) {
                editor.insertContent(' ' + val + ' ');
            }
        });

        editor.addCommand('mceAsciimathDlg', function() {
            if (typeof AMTcgiloc === 'undefined') {
                AMTcgiloc = "";
            }
            editor.windowManager.openUrl({
                title: "Math Symbols",
                url: url + '/amcharmap.htm',
                width: 720,
                height: 420
            });
        });

        editor.ui.registry.addIcon('math','<svg width="24" height="24" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><path fill="none" stroke="black" stroke-width="7" stroke-linecap="round" stroke-linejoin="round" d="M 80 15 H 20 L 60 50 L 20 85 H 80"/></svg>');
        editor.ui.registry.addIcon('mathadd','<svg width="24" height="24" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><path fill="none" stroke="black" stroke-width="7" stroke-linecap="round" stroke-linejoin="round" d="M 70 15 H 10 L 50 50 L 10 85 H 70 M 60 50 L 90 50 M 75 35 L 75 65"/></svg>');
        // Register buttons
        editor.ui.registry.addButton('asciimath', {
            tooltip: 'Add New Math',
            icon: 'mathadd',
            onAction: function() {
                editor.execCommand('mceAsciimath');
            }
        });
        editor.ui.registry.addMenuItem('asciimath', {
            text: 'New Math',
            icon: 'mathadd',
            onAction: function() {
                editor.execCommand('mceAsciimath');
            }
        });

        editor.ui.registry.addButton('asciimathcharmap', {
            tooltip: 'Math Symbols',
            icon: 'math',
            onAction: function() {
                editor.execCommand('mceAsciimathDlg');
            }
        });
        editor.ui.registry.addMenuItem('asciimathcharmap', {
            text: 'Math Symbols',
            icon: 'math',
            onAction: function() {
                editor.execCommand('mceAsciimathDlg');
            }
        });

        // Event handlers
        editor.on('keydown', function(ev) {
            if (ev.keyCode === 13 || ev.keyCode === 35 || ev.keyCode === 40) {
                var AMcontainer = editor.dom.getParent(editor.selection.getNode(), 'span.AMedit');
                if (AMcontainer) {
                    editor.selection.select(AMcontainer);
                    editor.selection.collapse(false);
                }
            } else if (ev.keyCode === 46 || ev.keyCode === 8) {
                var node = editor.selection.getNode();
                var AMcontainer = editor.dom.getParent(node, 'span.AM');
                if (AMcontainer) {
                    AMcontainer.parentNode.removeChild(AMcontainer);
                }
            }
        });

        editor.on('keypress', function(ev) {
            var key = String.fromCharCode(ev.charCode || ev.keyCode);
            if (key === '`') {
                if (lastAMnode === null) {
                    var existing = editor.selection.getContent();
                    if (existing.indexOf('class=AM') === -1) {
                        existing = existing.replace(/<([^>]*)>/g, "");
                        existing = existing.replace(/&(m|n)dash;/g, "-");
                        existing = existing.replace(/&?nbsp;?/g, " ");
                        existing = existing.replace(/&(.*?);/g, "$1");
                        var entity = '<span class="AMedit">`' + existing + '<span id="removeme"></span>`</span>&nbsp;';

                        editor.insertContent(entity);

                        var removeElement = editor.dom.get('removeme');
                        if (removeElement) {
                            editor.selection.setCursorLocation(removeElement, 0);
                            editor.dom.remove('removeme');
                        }
                        justinserted = true;
                        editor.nodeChanged();
                        justinserted = null;
                    }
                }
                ev.stopPropagation();
                ev.preventDefault();
            }
        });

        editor.on('PastePreProcess', function(e) {
            // convert mjx-container to span so doesn't get stripped in processing
            // safety net: replace any rendered math images with their AsciiMath
            // source; they get re-rendered after insertion
            e.content = e.content.replace(/<img\b[^>]*\bclass=["']?AMimg\b[^>]*>/g, function (tag) {
                var m = tag.match(/\btitle="([^"]*)"/) || tag.match(/\balt="([^"]*)"/);
                return m ? '`' + m[1].replace(/`/g, '') + '`' : '';
            });
            e.content = e.content.replace(/<mjx-container/g,'<span').replace(/<\/mjx-container/g,'*</span');
        });

        editor.on('PastePostProcess', function(o) {
            var AMtags = editor.dom.select('span.AM', o.node);
            for (var i = 0; i < AMtags.length; i++) {
                math2ascii(AMtags[i]);
            }
            
            var MJtags = editor.dom.select('span[data-asciimath]', o.node);
            for (var i = 0; i < MJtags.length; i++) {
                mathjax2ascii(MJtags[i]);
            }
            
            MJtags = editor.dom.select('mjx-container[data-asciimath]', o.node);
            for (var i = 0; i < MJtags.length; i++) {
                var newspan = editor.dom.create('span', {'data-asciimath': MJtags[i].getAttribute("data-asciimath")});
                MJtags[i].parentNode.replaceChild(newspan, MJtags[i]);
                mathjax2ascii(newspan);
            }
        });

        editor.on('PreProcess', function(o) {
            if (o.get) {
                var AMtags = editor.dom.select('span.AM', o.node);
                for (var i = 0; i < AMtags.length; i++) {
                    math2ascii(AMtags[i]);
                }
                AMtags = editor.dom.select('span.AMedit', o.node);
                for (var i = 0; i < AMtags.length; i++) {
                    var myAM = AMtags[i].innerHTML;
                    myAM = "`" + cleanmath(myAM) + "`";
                    AMtags[i].innerHTML = myAM;
                    AMtags[i].className = "AM";
                }
            }
        });

        editor.on('LoadContent', function(o) {
            var AMtags = editor.dom.select('span.AM');
            for (var i = 0; i < AMtags.length; i++) {
                nodeToAM(AMtags[i]);
            }
            loaded = true;
        });

        editor.on('SetContent', function(o) {
            if (loaded) {
                var AMtags = editor.dom.select('span.AM');
                for (var i = 0; i < AMtags.length; i++) {
                    if (!isRendered(AMtags[i])) {
                        nodeToAM(AMtags[i]);
                    }
                }
            }
        });

        editor.on('BeforeSetContent', function(o) {
            o.content = o.content.replace(/(<span[^>]+AM.*?<\/span>)</, "$1 <");
        });

        // Commands that don't modify content, so math needn't be un/re-rendered
        var NOCONTENTCMDS = /^(mceFocus|mceRepaint|selectAll|mceAutoResize|mceResize|mceSelectNode|mceFullScreen|mceCodeEditor|mceTogglePlainTextPaste|mceToggleVisualAid|mceToggleAccessibilityChecker|mceHelp)$/i;

        function isRendered(el) {
            return el.querySelector('img, math, svg') !== null;
        }

        editor.on('BeforeExecCommand', function(e) {
            if (lastAMnode !== null) {
                if (e.command === 'Superscript' || e.command === 'Subscript' || 
                    e.command === 'Bold' || e.command === 'mceToggleFormat') {
                    e.preventDefault();
                    return;
                } else if (e.command === 'mceShowCharmap') {
                    e.preventDefault();
                    editor.execCommand('mceAsciimathDlg');
                    return;
                }
            }
            if (e.command !== 'mceAsciimath' && e.command !== 'mceAsciimathDlg' && !NOCONTENTCMDS.test(e.command)) {
                var AMtags = editor.dom.select('span.AM');
                for (var i = 0; i < AMtags.length; i++) {
                    if (isRendered(AMtags[i])) {
                        math2ascii(AMtags[i]);
                    }
                }
            }
        });

        editor.on('ExecCommand', function(e) {
            if (e.command !== 'mceAsciimath' && e.command !== 'mceAsciimathDlg' && !NOCONTENTCMDS.test(e.command)) {
                var AMtags = editor.dom.select('span.AM');
                for (var i = 0; i < AMtags.length; i++) {
                    if (!isRendered(AMtags[i])) {
                        if (AMtags[i].title !== '') {
                            AMtags[i].innerHTML = AMtags[i].title;
                        }
                        nodeToAM(AMtags[i]);
                    }
                }
                var AMimgs = editor.dom.select('img.AMimg');
                for (var i = 0; i < AMimgs.length; i++) {
                    imgwrap(AMimgs[i]);
                }
            }
        });

        editor.on('NodeChange', function(e) {
            var doprocessnode = true;
            var p;
            
            if (testAMclass(e.element)) {
                p = e.element;
            } else {
                p = editor.dom.getParent(e.element, testAMclass);
            }

            if (p !== null) {
                if (lastAMnode === p) {
                    doprocessnode = false;
                } else {
                    math2ascii(p);
                    p.className = 'AMedit';
                    if (lastAMnode !== null) {
                        nodeToAM(lastAMnode);
                        lastAMnode.className = 'AM';
                    }
                    if (justinserted === null && p.parentNode.innerHTML.match(/<span[^>]*class="AMedit"[^<]*?<\/span>(\s*<br>)?\s*$/)) {
                        while (p.parentNode.lastChild.nodeName.toLowerCase() !== 'span') {
                            p.parentNode.removeChild(p.parentNode.lastChild);
                        }
                        p.parentNode.appendChild(editor.dom.doc.createTextNode("\u00A0"));
                    }
                    if (justinserted === null && p.parentNode.innerHTML.match(/^\s*<span[^>]*class="AMedit"/)) {
                        while (p.parentNode.firstChild.nodeName.toLowerCase() !== 'span') {
                            p.parentNode.removeChild(p.parentNode.firstChild);
                        }
                        p.parentNode.insertBefore(editor.dom.doc.createTextNode("\u00A0"), p.parentNode.firstChild);
                    }
                    if (justinserted === null) {
                        editor.selection.setCursorLocation(p, 0);
                    }
                    lastAMnode = p;
                    doprocessnode = false;
                }
            }
            
            if (doprocessnode && (lastAMnode !== null)) {
                if (lastAMnode.innerHTML.match(/`(&nbsp;|\s|\u00a0|&#160;)*`/) || 
                    lastAMnode.innerHTML.match(/^(&nbsp;|\s|\u00a0|&#160;)*$/)) {
                    p = lastAMnode.parentNode;
                    p.removeChild(lastAMnode);
                } else {
                    nodeToAM(lastAMnode);
                    lastAMnode.className = 'AM';
                }
                lastAMnode = null;
            }
        });

        editor.on('blur', function(e) {
            if (lastAMnode !== null) {
                if (lastAMnode.innerHTML.match(/`(&nbsp;|\s)*`/) || 
                    lastAMnode.innerHTML.match(/^(&nbsp;|\s|\u00a0|&#160;)*$/)) {
                    var p = lastAMnode.parentNode;
                    p.removeChild(lastAMnode);
                } else {
                    nodeToAM(lastAMnode);
                    lastAMnode.className = 'AM';
                }
                lastAMnode = null;
            }
        });

        // Return plugin info
        return {
            getMetadata: function() {
                return {
                    name: 'ASCIIMath plugin',
                    url: 'http://dlippman.imathas.com'
                };
            }
        };
    });
})();
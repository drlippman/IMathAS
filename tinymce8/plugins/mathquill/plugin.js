/**
 * TinyMCE 8 MathQuill Equation Editor Plugin
 * 
 * Usage:
 *   tinymce.init({
 *     plugins: ['mathquill'],
 *     toolbar: 'mathquill',
 *     mathquill_latex_endpoint: '/render-latex',  // required: ?latex=... → image
 *     mathquill_palette_url: '/plugins/mathquill/palette/index.html', // optional override
 *     mathquill_img_class: 'mq-equation',  // optional, default: 'mq-equation'
 *   });
 *
 * The latex-to-image endpoint receives:  GET /render-latex?latex=<encoded-latex>
 * and must return an image (png/svg/etc).
 *
 * When an existing equation image is selected and the toolbar button is clicked,
 * the plugin extracts the latex from the src URL and pre-populates the palette.
 */

(function () {
  'use strict';

  // ── helpers ──────────────────────────────────────────────────────────────

  function uid() {
    return 'mq-eq-' + Math.random().toString(36).slice(2, 10);
  }


  function selectedMathImg(editor, imgClass) {
    var node = editor.selection.getNode();
    if (node && node.nodeName === 'IMG' && node.classList.contains(imgClass)) {
      return node;
    }
    return null;
  }

  // ── plugin registration ──────────────────────────────────────────────────

  tinymce.PluginManager.add('mathquill', function (editor) {

    var imgClass   = editor.getParam('mathquill_img_class', 'mq-equation');
    var endpoint   = editor.getParam('mathquill_latex_endpoint', '/render-latex');
    var paletteUrl = editor.getParam(
      'mathquill_palette_url',
      tinymce.baseURL + '/plugins/mathquill/palette/index.html?v=070326c'
    );

    // track the window reference so we can focus it if already open
    var paletteWin = null;

    // ── open / re-open palette ────────────────────────────────────────────

    function openPalette() {
      var existingAsciimath = '';
      var existingImg   = selectedMathImg(editor, imgClass);
      if (existingImg) {
        existingAsciimath = existingImg.getAttribute('title') || '';
      } else {
        // Check whether the cursor is inside a span with class AM or AMedit
        var node = editor.selection.getNode();
        if (node.nodeName === 'SPAN' && 
          (node.classList.contains('AM') || node.classList.contains('AMedit'))
        ) {
          existingAsciimath = node.textContent.replace(/`/g,'');
        } else {
          var seltext = editor.selection.getContent();
          if (seltext.indexOf('class=AM') === -1) {
            seltext = seltext.replace(/<([^>]*)>/g, "");
            seltext = seltext.replace(/&(m|n)dash;/g, "-");
            seltext = seltext.replace(/&?nbsp;?/g, " ");
            seltext = seltext.replace(/&(.*?);/g, "$1");
          }
          existingAsciimath = seltext;
        }
      }

      // Build palette URL, passing any pre-existing latex and the endpoint
      var sep      = paletteUrl.indexOf('?') === -1 ? '?' : '&';
      var fullUrl  = paletteUrl
        + sep + 'endpoint=' + encodeURIComponent(endpoint)
        + '&imgclass=' + encodeURIComponent(imgClass)
        + (existingAsciimath ? '&asciimath=' + encodeURIComponent(existingAsciimath) : '');

      // Use TinyMCE's openUrl so it opens in a dialog / popup properly handled
      // by TinyMCE 8's dialog system.  We use WindowManager.openUrl.
      if (paletteWin && !paletteWin.closed) {
        // bring it to front and send updated state
        try {
          paletteWin.focus();
          paletteWin.postMessage({ type: 'mq:setAsciimath', asciimath: existingAsciimath }, window.location.origin);
        } catch(e) {}
        return;
      }

      paletteWin = editor.windowManager.openUrl({
        title: 'Equation Editor',
        url: fullUrl,
        width: 550,
        height: 500,
        buttons: [
          {
            type: 'cancel',
            text: 'Cancel'
          },
          {
            type: 'custom',
            name: 'insert-equation',
            text: 'Insert Equation',
            primary: true
          }
        ],
        onAction: function (dialogApi, details) {
          if (details.name === 'insert-equation') {
            // Ask the palette iframe for its current latex, then handle it
            dialogApi.sendMessage({ type: 'mq:requestAsciimath' });
          }
        },
        onMessage: function (dialogApi, details) {
          handlePaletteMessage(dialogApi, details.data);
        },
        onClose: function () {
          paletteWin = null;
        }
      });

      const dlg = document.querySelector('.tox-dialog:last-of-type'); // or the topmost .tox-dialog
      if (dlg) {
        dlg.style.resize = 'both';
        dlg.style.overflow = 'auto';   // resize needs overflow other than visible
        dlg.style.minWidth = '405px';
        dlg.style.maxWidth = '900px';
        dlg.style.minHeight = '415px';
        dlg.style.maxHeight = '600px';
      }
    }

    // ── handle messages from the palette page ─────────────────────────────

    function handlePaletteMessage(dialogApi, data) {
      if (!data || data.type !== 'mq:insert') return;

      var latex = data.latex || '';
      var asciimath = data.asciimath || '';
      if (!latex || !asciimath) return;

      var imgSrc = endpoint + '?' + encodeURIComponent(latex);
      var id     = uid();

      // Build the img tag
      var imgHtml = '<span class="AM" title="' + tinymce.DOM.encode(asciimath) + '"><img'
        + ' src="' + tinymce.DOM.encode(imgSrc) + '"'
        + ' class="' + tinymce.DOM.encode(imgClass) + '"'
        + ' data-mq-id="' + id + '"'
        + ' title="' + tinymce.DOM.encode(asciimath) + '"'
        + '></span>';

      var existingImg = selectedMathImg(editor, imgClass);
      if (existingImg) {
        // Replace existing equation image
        editor.selection.select(existingImg);
      } else {
        var node = editor.selection.getNode();
        if (node.nodeName === 'SPAN' && 
          (node.classList.contains('AM') || node.classList.contains('AMedit'))
        ) {
          editor.selection.select(node);
        } 
      }
      editor.insertContent(imgHtml);
      if (window.AMTcgilocUseSVG) {
        var imgnode = editor.dom.select('img[data-mq-id="'+id+'"]');
        if (imgnode.length) {
            $.get({
                url: imgnode[0].src, 
                dataType: 'text'
            }).done(function (svgText) {
                var va = svgText.match(/vertical-align:\s*([\-\.\d\w]+);/)[1]
                imgnode[0].style.verticalAlign = va;
            });
        }
      }

      if (dialogApi && dialogApi.close) {
        dialogApi.close();
      }
      paletteWin = null;
    }

    // ── toolbar button ────────────────────────────────────────────────────
    editor.ui.registry.addIcon('mathmq','<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"> <rect x="3.25" y="3" width="17.5" height="18" rx="2.5" fill="none" stroke="currentColor" stroke-width="1.5"/> <rect x="3.25" y="3" width="17.5" height="4" rx="2.5" fill="currentColor" stroke="none"/> <rect x="3.25" y="5.5" width="17.5" height="1.5" fill="currentColor" stroke="none"/> <path d="M15.7,10 L8.3,10 L12.8,14 L8.3,18 L15.7,18" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>');
    editor.ui.registry.addButton('mathquill', {
      icon: 'mathmq',
      tooltip: 'Equation Editor',
      /*onSetup: function (buttonApi) {
        // Enable the button always; highlight it when a math image is selected
        var unbind = editor.selection.selectorChangedWithUnbind(
          'img.' + imgClass,
          function (state) {
            buttonApi.setActive(state);
          }
        ).unbind;
        return unbind;
      },*/
      onAction: function () {
        openPalette();
      }
    });

    // Also register as a menu item
    editor.ui.registry.addMenuItem('mathquill', {
      text: 'Insert Equation…',
      icon: 'formula',
      onAction: function () {
        openPalette();
      }
    });

    // Return plugin metadata
    return {
      getMetadata: function () {
        return {
          name: 'MathQuill Equation Editor',
          url: 'https://github.com/your-org/tinymce-mathquill'
        };
      }
    };
  });

})();

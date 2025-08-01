(function () {

var defs = {}; // id -> {dependencies, definition, instance (possibly undefined)}

// Used when there is no 'main' module.
// The name is probably (hopefully) unique so minification removes for releases.
var register_9273 = function (id) {
  var module = dem(id);
  var fragments = id.split('.');
  var target = Function('return this;')();
  for (var i = 0; i < fragments.length - 1; ++i) {
    if (target[fragments[i]] === undefined)
      target[fragments[i]] = {};
    target = target[fragments[i]];
  }
  target[fragments[fragments.length - 1]] = module;
};

var instantiate = function (id) {
  var actual = defs[id];
  var dependencies = actual.deps;
  var definition = actual.defn;
  var len = dependencies.length;
  var instances = new Array(len);
  for (var i = 0; i < len; ++i)
    instances[i] = dem(dependencies[i]);
  var defResult = definition.apply(null, instances);
  if (defResult === undefined)
     throw 'module [' + id + '] returned undefined';
  actual.instance = defResult;
};

var def = function (id, dependencies, definition) {
  if (typeof id !== 'string')
    throw 'module id must be a string';
  else if (dependencies === undefined)
    throw 'no dependencies for ' + id;
  else if (definition === undefined)
    throw 'no definition function for ' + id;
  defs[id] = {
    deps: dependencies,
    defn: definition,
    instance: undefined
  };
};

var dem = function (id) {
  var actual = defs[id];
  if (actual === undefined)
    throw 'module [' + id + '] was undefined';
  else if (actual.instance === undefined)
    instantiate(id);
  return actual.instance;
};

var req = function (ids, callback) {
  var len = ids.length;
  var instances = new Array(len);
  for (var i = 0; i < len; ++i)
    instances.push(dem(ids[i]));
  callback.apply(null, callback);
};

var ephox = {};

ephox.bolt = {
  module: {
    api: {
      define: def,
      require: req,
      demand: dem
    }
  }
};

var define = def;
var require = req;
var demand = dem;
// this helps with minificiation when using a lot of global references
var defineGlobal = function (id, ref) {
  define(id, [], function () { return ref; });
};
/*jsc
["tinymce.plugins.snippet.Plugin","ephox.katamari.api.Fun","tinymce.core.dom.DOMUtils","tinymce.core.PluginManager","tinymce.core.util.JSON","tinymce.core.util.Tools","tinymce.core.util.XHR","tinymce.plugins.snippet.core.DateTimeHelper","tinymce.plugins.snippet.core.snippets","global!Array","global!Error","global!tinymce.util.Tools.resolve"]
jsc*/
defineGlobal("global!Array", Array);
defineGlobal("global!Error", Error);
define(
  'ephox.katamari.api.Fun',

  [
    'global!Array',
    'global!Error'
  ],

  function (Array, Error) {

    var noop = function () { };

    var compose = function (fa, fb) {
      return function () {
        return fa(fb.apply(null, arguments));
      };
    };

    var constant = function (value) {
      return function () {
        return value;
      };
    };

    var identity = function (x) {
      return x;
    };

    var tripleEquals = function(a, b) {
      return a === b;
    };

    // Don't use array slice(arguments), makes the whole function unoptimisable on Chrome
    var curry = function (f) {
      // equivalent to arguments.slice(1)
      // starting at 1 because 0 is the f, makes things tricky.
      // Pay attention to what variable is where, and the -1 magic.
      // thankfully, we have tests for this.
      var args = new Array(arguments.length - 1);
      for (var i = 1; i < arguments.length; i++) args[i-1] = arguments[i];

      return function () {
        var newArgs = new Array(arguments.length);
        for (var j = 0; j < newArgs.length; j++) newArgs[j] = arguments[j];

        var all = args.concat(newArgs);
        return f.apply(null, all);
      };
    };

    var not = function (f) {
      return function () {
        return !f.apply(null, arguments);
      };
    };

    var die = function (msg) {
      return function () {
        throw new Error(msg);
      };
    };

    var apply = function (f) {
      return f();
    };

    var call = function(f) {
      f();
    };

    var never = constant(false);
    var always = constant(true);
    

    return {
      noop: noop,
      compose: compose,
      constant: constant,
      identity: identity,
      tripleEquals: tripleEquals,
      curry: curry,
      not: not,
      die: die,
      apply: apply,
      call: call,
      never: never,
      always: always
    };
  }
);

defineGlobal("global!tinymce.util.Tools.resolve", tinymce.util.Tools.resolve);
/**
 * ResolveGlobal.js
 *
 * Released under LGPL License.
 * Copyright (c) 1999-2017 Ephox Corp. All rights reserved
 *
 * License: http://www.tinymce.com/license
 * Contributing: http://www.tinymce.com/contributing
 */

define(
  'tinymce.core.dom.DOMUtils',
  [
    'global!tinymce.util.Tools.resolve'
  ],
  function (resolve) {
    return resolve('tinymce.dom.DOMUtils');
  }
);

/**
 * ResolveGlobal.js
 *
 * Released under LGPL License.
 * Copyright (c) 1999-2017 Ephox Corp. All rights reserved
 *
 * License: http://www.tinymce.com/license
 * Contributing: http://www.tinymce.com/contributing
 */

define(
  'tinymce.core.PluginManager',
  [
    'global!tinymce.util.Tools.resolve'
  ],
  function (resolve) {
    return resolve('tinymce.PluginManager');
  }
);

/**
 * ResolveGlobal.js
 *
 * Released under LGPL License.
 * Copyright (c) 1999-2017 Ephox Corp. All rights reserved
 *
 * License: http://www.tinymce.com/license
 * Contributing: http://www.tinymce.com/contributing
 */

define(
  'tinymce.core.util.JSON',
  [
    'global!tinymce.util.Tools.resolve'
  ],
  function (resolve) {
    return resolve('tinymce.util.JSON');
  }
);

/**
 * ResolveGlobal.js
 *
 * Released under LGPL License.
 * Copyright (c) 1999-2017 Ephox Corp. All rights reserved
 *
 * License: http://www.tinymce.com/license
 * Contributing: http://www.tinymce.com/contributing
 */

define(
  'tinymce.core.util.Tools',
  [
    'global!tinymce.util.Tools.resolve'
  ],
  function (resolve) {
    return resolve('tinymce.util.Tools');
  }
);

/**
 * ResolveGlobal.js
 *
 * Released under LGPL License.
 * Copyright (c) 1999-2017 Ephox Corp. All rights reserved
 *
 * License: http://www.tinymce.com/license
 * Contributing: http://www.tinymce.com/contributing
 */

define(
  'tinymce.core.util.XHR',
  [
    'global!tinymce.util.Tools.resolve'
  ],
  function (resolve) {
    return resolve('tinymce.util.XHR');
  }
);

define(
  'tinymce.plugins.snippet.core.Snippets',

  [
    'tinymce.core.util.Tools',
    'tinymce.core.util.XHR',
  ],

  function (Tools, XHR) {
    var createSnippetList = function (editorSettings, callback) {
      return function () {
        var SnippetList = editorSettings.snippets;

        if (typeof SnippetList == "function") {
          SnippetList(callback);
          return;
        }

        if (typeof SnippetList == "string") {
          XHR.send({
            url: SnippetList,
            success: function (text) {
              callback(JSON.parse(text));
            }
          });
        } else {
          callback(SnippetList);
        }
      };
    };

  
    var insertSnippet = function (editor, ui, html) {
      var el, n, dom = editor.dom, sel = editor.selection.getContent();

      editor.execCommand('mceInsertContent', false, html);
      editor.addVisual();
    };

    return {
      createSnippetList: createSnippetList,
      insertSnippet: insertSnippet
    };
  }
);

/**
 * Plugin.js
 *
 * Released under LGPL License.
 * Copyright (c) 1999-2017 Ephox Corp. All rights reserved
 *
 * License: http://www.tinymce.com/license
 * Contributing: http://www.tinymce.com/contributing
 */

/**
 * This class contains all core logic for the code plugin.
 *
 * @class tinymce.snippet.Plugin
 * @private
 */
define(
  'tinymce.plugins.snippet.Plugin',
  [
    'ephox.katamari.api.Fun',
    'tinymce.core.dom.DOMUtils',
    'tinymce.core.PluginManager',
    'tinymce.core.util.JSON',
    'tinymce.core.util.Tools',
    'tinymce.core.util.XHR',
    'tinymce.plugins.snippet.core.Snippets'
  ],
  function (Fun, DOMUtils, PluginManager, JSON, Tools, XHR, Snippets) {

    var insertIframeHtml = function (editor, win, html) {
      if (html.indexOf('<html>') == -1) {
        var contentCssLinks = '';

        Tools.each(editor.contentCSS, function (url) {
          contentCssLinks += '<link type="text/css" rel="stylesheet" href="' +
                  editor.documentBaseURI.toAbsolute(url) +
                  '">';
        });

        var bodyClass = editor.settings.body_class || '';
        if (bodyClass.indexOf('=') != -1) {
          bodyClass = editor.getParam('body_class', '', 'hash');
          bodyClass = bodyClass[editor.id] || '';
        }

        html = (
                '<!DOCTYPE html>' +
                '<html>' +
                '<head>' +
                contentCssLinks +
                '</head>' +
                '<body class="' + bodyClass + '">' +
                html +
                '</body>' +
                '</html>'
              );
      }

      var doc = win.find('iframe')[0].getEl().contentWindow.document;
      doc.open();
      doc.write(html);
      doc.close();
    };

    PluginManager.add('snippet', function (editor) {
      function showDialog(SnippetList) {
        var win, values = [], subvalues=[], SnippetHtml, snipbox;

        if (!SnippetList || SnippetList.length === 0) {
          var message = editor.translate('No Snippets defined.');
          message += '<br><a href="'+imasroot+'/course/editsnippets.php" target="_blank">';
          message += editor.translate('Create some')+'</a>';
          editor.notificationManager.open({ text: message, type: 'info' });
          return;
        }

        Tools.each(SnippetList, function (Snippet) {
          values.push({
            selected: !values.length,
            text: Snippet.text,
            value: {items: Snippet.items}
          });
        });
        Tools.each(SnippetList[0].items, function (item) {
		subvalues.push({
			selected: !subvalues.length,
			text: item.text,
			value: {content: item.content}
		});
	  });

        var onSelectSnippetGroup = function (e) {
          var value = e.control.value();
          var subvalues = [];
          Tools.each(value.items, function (item) {
          	subvalues.push({
          		text: item.text,
          		value: {content: item.content}
          	});
          });
          //insertIframeHtml(editor, win, SnippetHtml);
          snipbox.menu = null;
       
          snipbox.state.data.menu = snipbox.settings.values = subvalues;
          snipbox.value(subvalues[0].value);
          win.find('listbox')[1].fire('select');
        };
        var onSelectSnippet = function (e) {
          var value = e.control.value();
          if (value.content) {
          	  SnippetHtml = value.content;
          	  insertIframeHtml(editor, win, SnippetHtml);
          }

        };

        win = editor.windowManager.open({
          title: 'Insert Prewritten Snippet',
          layout: 'flex',
          direction: 'column',
          align: 'stretch',
          padding: 15,
          spacing: 10,
          items: [
            {
              type: 'form',
              flex: 0,
              padding: 0,
              items: [
              	
                {
                  type: 'container',
                  label: 'Group',
                  items: {
                    type: 'listbox',
                    label: 'Group',
                    name: 'snippetgroup',
                    values: values,
                    onselect: onSelectSnippetGroup
                  }
                }, 
                {
                  type: 'container',
                  label: 'Snippets',
                  items: {
                    type: 'listbox',
                    id: "snippet",
                    label: 'Snippets',
                    name: 'snippet',
                    values: subvalues,
                    onselect: onSelectSnippet,
                    onPostRender: function() {
                    	    snipbox = this;
                    }
                  }
                }
              ]
            },
            
            {
              type: 'iframe',
              flex: 1,
              border: 1
            }
          ],
          buttons: [
            {
          	text: "Add / Edit Snippets", onclick: function () {
          		window.open(imasroot+'/course/editsnippets.php', "_blank");
          		tinymce.activeEditor.windowManager.close();
          	}
            },
            { type: "spacer", flex: 1 },
            {
          	text: "Insert", subtype: 'primary', onclick: function () {
          		Snippets.insertSnippet(editor, false, SnippetHtml);
          		tinymce.activeEditor.windowManager.close();
          	}
            },
            {
          	text: "Cancel", onclick: function () {
          		tinymce.activeEditor.windowManager.close();
          	}
            },
          ],
          onsubmit: function () {
            Snippets.insertSnippet(editor, false, SnippetHtml);
          },

          minWidth: Math.min(DOMUtils.DOM.getViewPort().w, editor.getParam('snippet_popup_width', 600)),
          minHeight: Math.min(DOMUtils.DOM.getViewPort().h, editor.getParam('snippet_popup_height', 500))
        });

        win.find('listbox')[0].fire('select');
      }

      editor.addCommand('mceInsertSnippet', Fun.curry(Snippets.insertSnippet, editor));

      if (typeof editor.settings.snippets != 'undefined' && editor.settings.snippets!==false) {
	      editor.addButton('snippet', {
		title: 'Insert Prewritten Snippet',
		onclick: Snippets.createSnippetList(editor.settings, showDialog)
	      });
	
	      editor.addMenuItem('snippet', {
		text: 'Prewritten Snippet',
		onclick: Snippets.createSnippetList(editor.settings, showDialog),
		context: 'insert',
		icon: 'snippet',
	      });
      }

    });


    return function () { };
  }
);
dem('tinymce.plugins.snippet.Plugin')();
})();

/**
 * editor_plugin_src.js
 *
 * Copyright 2009, Moxiecode Systems AB
 * Released under LGPL License.
 *
 * License: http://tinymce.moxiecode.com/license
 * Contributing: http://tinymce.moxiecode.com/contributing
 */

(function() {
	tinymce.PluginManager.requireLangPack('attach');
	tinymce.create('tinymce.plugins.AttachPlugin', {
		init : function(ed, url) {
			this.editor = ed;

			// Register commands
			ed.addCommand('mceAttach', function() {
				
				// No selection and not in link
				//if (se.isCollapsed() && !ed.dom.getParent(se.getNode(), 'A'))
				//	return;

				ed.windowManager.open({
					file : url + '/attach.htm',
					width : 460 + parseInt(ed.getLang('advlink.delta_width', 0)),
					height : 210 + parseInt(ed.getLang('advlink.delta_height', 0)),
					inline : 1
				}, {
					plugin_url : url
				});
			});

			// Register buttons
			ed.addButton('attach', {
				title : 'attach.desc',
				cmd : 'mceAttach',
				image : url + '/img/ed_attach.gif'
			});

			

			ed.onNodeChange.add(function(ed, cm, n, co) {
				cm.setDisabled('link', n.nodeName == 'A' && n.className == 'attach');
				cm.setActive('attach', n.nodeName == 'A' && n.className == 'attach' && !n.name);
			});
		},

		getInfo : function() {
			return {
				longname : 'Attachment plugin',
				author : 'David Lippman',
				authorurl : '',
				infourl : '',
				version : "1.0"
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('attach', tinymce.plugins.AttachPlugin);
})();
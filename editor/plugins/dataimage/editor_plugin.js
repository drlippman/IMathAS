(function() {
	tinymce.create('tinymce.plugins.dataimagePlugin', {
		init : function(ed, url) {
			var t = this;

			t.editor = ed;
			t.url = url;
			var m;
			
			ed.onPreProcess.add(function(ed,o) {
				var imgtags = ed.dom.select('img', o.node);
				
				for (var i=imgtags.length-1; i>=0; i--) {
					if (m = imgtags[i].src.match(/data:image/)) {
						if (imgtags[i].src.length > 64000) {
							alert("This image is too large to be pasted this way.  Please save the image and upload it by clicking on the Insert Image icon (a green tree), then clicking the Browse icon to the right of the Image URL box");
							ed.dom.remove(imgtags[i]);
						}
					}
				}
			});
			
			ed.onSaveContent.add(function(ed,o) {
				var imgtags = ed.dom.select('img', o.node);
				for (var i=imgtags.length-1; i>=0; i--) {
					if (m = imgtags[i].src.match(/data:image/)) {
						if (imgtags[i].src.length > 64000) {
							alert("This image is too large to be pasted this way.  Please save the image and upload it by clicking on the Insert Image icon (a green tree), then clicking the Browse icon to the right of the Image URL box");
							ed.dom.remove(imgtags[i]);
						}
					}
				}
					
			});
		},
		
		cleanimg: function(txt) {
						
		}
	});
	// Register plugin
	tinymce.PluginManager.add('dataimage', tinymce.plugins.dataimagePlugin);
})();		
			
		

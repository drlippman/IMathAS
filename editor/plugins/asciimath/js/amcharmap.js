tinyMCEPopup.requireLangPack();
var waitforAMTcgiloc = true;

var AsciimathDialog = {
	init : function() {
		AMTcgiloc = tinyMCEPopup.getWindowArg('AMTcgiloc');
	},

	set : function(val) {
		tinyMCEPopup.restoreSelection();
		// Insert the contents from the input into the document
		tinyMCEPopup.editor.execCommand('mceAsciimath', val);
		tinyMCEPopup.close();
	}
};

tinyMCEPopup.onInit.add(AsciimathDialog.init, AsciimathDialog);

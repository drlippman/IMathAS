//Modified from version by By Martin Honnen
//taken from http://www.faqts.com/knowledge_base/view.phtml/aid/1756/fid/129
function checkComplete(baseel) {
	if (typeof tinyMCE != "undefined") {
		try{tinyMCE.triggerSave();}catch(err1){};
	}
	var complete = true;
	$(baseel).find("input:visible,textarea:visible,select:visible").each(function(i,el) {
		if (typeof el.type == "undefined" || typeof el.name == "undefined" || el.name == "") {
			return 1; //continue
		}
		if (el.type == 'text' || el.type == 'textarea' ||
		    el.type == 'password' || el.type == 'file' ) {
			if (el.value == '') {
				if ($("#qs"+el.id.substr(2)+"-d:checked,#qs"+el.id.substr(2)+"-i:checked").length==0) {
					complete = false;
			      	}
		      	}
		} else if (el.type.indexOf('select') != -1) {
			if (el.selectedIndex == -1 || (el.name.substr(0,2)=="qn" && el.selectedIndex ==0)) {
				complete = false;
			}
		} else if (el.type == 'radio') {
			if ($("input[name="+el.name+"]:checked").length==0) {
				complete = false;
			}
		}
	});
	return complete;
}

function confirmSubmit (form) {
	var allans = checkComplete(form);
	if (!allans) {
		var msg = "Not all question parts have been answered.  Are you sure you want to submit this question?";
		return confirm(msg);
	} else {
		return true;
	}
}
function confirmSubmit2 (form) {
	var allans = checkComplete(form);
	if (!allans) {
		var msg = "Not all questions have been answered completely.  If you are saving your answers for later, this is fine.  If you are submitting for grading, are you sure you want to submit now?";
		return confirm(msg);
	} else {
		return true;
	}
}

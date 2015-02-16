//Modified from version by By Martin Honnen
//taken from http://www.faqts.com/knowledge_base/view.phtml/aid/1756/fid/129
function checkComplete (form) {
	if (typeof tinyMCE != "undefined") {
		try{tinyMCE.triggerSave();}catch(err1){};
	}
	if (!form.elements) { return true;} //temp fix for editor preventing this from working right
  for (var e = 0; e < form.elements.length; e++) {
    var el = form.elements[e];
    if (typeof el.type == "undefined" || typeof el.name == "undefined" || el.name == "") {
	    continue;
    }
    if ($(el).is(":not(:visible)")) {
    	    continue;
    }
    if (el.type == 'text' || el.type == 'textarea' ||
        el.type == 'password' || el.type == 'file' ) {
      if (el.value == '') {
        return false;
      }
    }
    else if (el.type.indexOf('select') != -1) {
      if (el.selectedIndex == -1) {
        return false;
      }
    }
    else if (el.type == 'radio') {
      var group = form[el.name];
      var checked = false;
      if (!group.length)
        checked = el.checked;
      else
        for (var r = 0; r < group.length; r++)
          if ((checked = group[r].checked))
            break;
      if (!checked) {
        return false;
      }
    }
  }
  return true;
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

//By Martin Honnen
//taken from http://www.faqts.com/knowledge_base/view.phtml/aid/1756/fid/129
function validateForm (form) {
  for (var e = 0; e < form.elements.length; e++) {
    var el = form.elements[e];
    if (typeof el.type == "undefined" || typeof el.name == "undefined") {
	    continue;
    }
    if (el.type == 'text' || el.type == 'textarea' ||
        el.type == 'password' || el.type == 'file' ) { 
      if (el.value == '') {
        //alert('Please fill out all text field ' + el.name);
        alert('Please complete all fields');
	el.focus();
        return false;
      }
    }
    else if (el.type.indexOf('select') != -1) {
      if (el.selectedIndex == -1) {
        //alert('Please select a value of the select field ' + el.name);
        alert('Please complete all fields');
	el.focus();
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
        //alert('Please check one of the radio buttons ' + el.name);
        alert('Please complete all fields');
	el.focus();
        return false;
      }
    }
    else if (el.type == 'checkbox') {
      var group = form[el.name];
      if (group.length) {
        var checked = false;
        for (var r = 0; r < group.length; r++)
          if ((checked = group[r].checked))
            break;
        if (!checked) {
          //alert('Please check one of the checkboxes ' + el.name);
          alert('Please complete all fields');
	  el.focus();
          return false;
        }
      }
    }
  }
  return true;
}

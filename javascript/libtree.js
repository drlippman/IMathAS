function toggle(id) {
	node = document.getElementById(id);
	button = document.getElementById('b'+id);
	if (node.className == "show") {
		node.className = "hide";
		button.innerHTML = "+";
	} else {
		node.className = "show";
		button.innerHTML = "-";
	}
}

function setlib(frm) {
	var cnt = 0;
	var chlibs = new Array();
	var chlibsn = new Array();
	for (i = 0; i <= frm.elements.length; i++) {
		try{
			if(frm.elements[i].name == 'libs[]' || frm.elements[i].name=='libs') {
				if (frm.elements[i].checked == true) {
					chlibs[cnt] = frm.elements[i].value;
					chlibsn[cnt] = document.getElementById('n'+chlibs[cnt]).innerHTML.replace(/\s+/g,' ').trim();
					cnt++;
				}
			}
		} catch(er) {}
	}
	opener.setlib(chlibs.join(","));
	opener.setlibnames(chlibsn.join(", "));
	self.close();
}

function libchkAll(frm, arr, mark) {
  for (i = 0; i <= frm.elements.length; i++) {
   try{
     if(frm.elements[i].name == arr) {
       frm.elements[i].checked = mark;
     }
   } catch(er) {}
  }

 uls = document.getElementsByTagName("UL");
 if (mark==true) {
	 for (i=0; i< uls.length; i++) {
		 if (uls[i].className == "hide") {
			 uls[i].className = "show";
			 document.getElementById('b'+uls[i].id).innerHTML = "-";
		 }
	 }
 } else {
	 for (i=0; i< uls.length; i++) {
		 if (uls[i].className == "show") {
			 uls[i].className = "hide";
			 document.getElementById('b'+uls[i].id).innerHTML = "+";
		 }
	 } 
 }
}
$(function() {
	$("input[type=checkbox]:not(:disabled)").on('dblclick', function(evt) {
		var state = $(evt.target).prop("checked");
		$(evt.target).parent().find("input:checkbox:not(:disabled)").prop("checked",!state);
	});
});

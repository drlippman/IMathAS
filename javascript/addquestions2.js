function moveitem(from) {
	var to = document.getElementById(from).value;
	var grp = document.getElementById('group').value;
	if (to != from) {
  		var toopen = addqaddr+'&from=' + from + '&to=' + to + '&grp=' + grp;
		window.location = toopen;
		}
}

function previewq(formn,loc,qn,docheck,onlychk) {
   var addr = previewqaddr+'&qsetid='+qn;
   if (formn!=null) {
	    addr +='&formn='+formn;
   }
   if (loc!=null) {
	   addr +='&loc='+loc;
   }
   if (docheck) {
      addr += '&checked=1';
   }
   if (onlychk) {
      addr += '&onlychk=1';
   }

   previewpop = window.open(addr,'Testing','width='+(.4*screen.width)+',height='+(.8*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(.6*screen.width-20));
   previewpop.focus();
}
function sethighlightrow(loc) {
	$("tr.highlight").removeClass("highlight");
	$("#"+loc).closest("tr").addClass("highlight");
}
function previewsel(formn) {
	var form = document.getElementById(formn);
	for (var e = 0; e < form.elements.length; e++) {
		var el = form.elements[e];
		if (el.type == 'checkbox' && el.name=='nchecked[]' && el.checked) {
			previewq(formn,el.id.substring(2),el.value,true,true);
			return false;
		}
	}
	alert("No questions selected");
}
function getnextprev(formn,loc,onlychk) {
	var onlychk = (onlychk == null) ? false : true;
	var form = document.getElementById(formn);
	if (form==null) {
		return null;
	}
	var prevl = 0; var nextl = 0; var found=false;
	var prevq = 0; var nextq = 0;
	var cntchecked = 0;  var remaining = 0;
	var looking = true;
	for (var e = 0; e < form.elements.length; e++) {
		var el = form.elements[e];
		if (typeof el.type == "undefined" || el.value.match(/text/)) {
			continue;
		}
		if (((el.type == 'checkbox' && el.name=='nchecked[]') || ((el.type=='checkbox' || el.type=='hidden') && el.name=='checked[]')) && (!onlychk || el.checked)) {
			if (el.checked) {
				cntchecked++;
			}
			if (looking) {
				if (found) {
					nextq = el.value;
					nextl = el.id;
					remaining++;
					looking=false;//break;
				} else if (el.id==loc) {
					found = true;
				} else {
					prevq = el.value;
					prevl = el.id;
				}
			} else {
				remaining++;
			}
		}
	}
	if (formn=='curqform') {
		if (prevl!=0) {
			prevq = document.getElementById('o'+prevl).value;
		}
		if (nextl!=0) {
			nextq = document.getElementById('o'+nextl).value;
		}
	}
	return ([[prevl,prevq],[nextl,nextq],cntchecked,remaining]);
}

function chkAll(frm, arr, mark) {
  for (i = 0; i <= frm.elements.length; i++) {
   try{
     if(frm.elements[i].name == arr) {
       frm.elements[i].checked = mark;
     }
   } catch(er) {}
  }
}

function libselect() {
    var listlibs = '';
    if (cursearchtype == 'libs') {
        listlibs = curlibs;
    }
    GB_show('Library Select','libtree2.php?libtree=popup&libs='+listlibs,500,500);
}
function setlib(libs) {
	//document.getElementById("libs").value = libs;
    curlibs = libs;
    cursearchtype = 'libs';
    $("#cursearchtype").text(_('In Libraries'));
}
function setlibnames(libn) {
	document.getElementById("libnames").innerHTML = libn.replace(/<span.*?<\/span.*?>/g,'');
}
function assessselect() {
    var lista = '';
    if (cursearchtype == 'assess') {
        lista = curlibs;
    }
    GB_show('Assessment Select',aselectaddr+'&curassess='+lista,900,500);
}
function setassess(aids) {
    curlibs = aids;
    cursearchtype = 'assess';
    $("#cursearchtype").text(_('In Assessments'));
}
function setassessnames(aidn) {
	document.getElementById("libnames").innerHTML = aidn.replace(/<span.*?<\/span.*?>/g,'');
}

function prePageChange() {
	if ($("#selq input[type=checkbox]:checked").length > 0) {
		return confirm(_('You have questions selected which will get lost if you continue.  Continue anyway?'));
	} else {
		return true;
	}
}

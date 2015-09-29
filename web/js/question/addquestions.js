$(document).ready(function () {
    selectCheckBox();
    selectCheckBox1();
    $('input[name = "header-checked"]:checked').prop('checked', false);
    $('input[name = "potentialq-header-checked"]:checked').prop('checked', false);
    $('input[name = "manage-question-header-checked"]:checked').prop('checked', false);
    $('input[name = "header-checked1"]:checked').prop('checked', false);
});

function selectCheckBox() {

    $('.potential-question-table input[name = "header-checked"]').click(function(){
        if($(this).prop("checked") == true){
            $('#potential-question-information-table input:checkbox').each(function () {
                $(this).prop('checked', true);
            })
        }
        else if($(this).prop("checked") == false){
            $('#potential-question-information-table input:checkbox').each(function () {
                $(this).prop('checked', false);
            })
        }
    });
    $('#add-remove-ques-table input[name = "potentialq-header-checked"]').click(function(){
        if($(this).prop("checked") == true){
            $('#potential-question-assessment-information-table input:checkbox').each(function () {
                $(this).prop('checked', true);
            })
        }
        else if($(this).prop("checked") == false){
            $('#potential-question-assessment-information-table input:checkbox').each(function () {
                $(this).prop('checked', false);
            })
        }
    });
}
function selectCheckBox1() {
    $('#add-remove-ques-table input[id = "header-checked1"]').click(function(){
        if($(this).prop("checked") == true){
            $('#question-information-table input:checkbox').each(function () {
                $(this).prop('checked', true);
            })
        }
        else if($(this).prop("checked") == false){
            $('#question-information-table input:checkbox').each(function () {
                $(this).prop('checked', false);
            })
        }
    });
}

function moveitem(from) {
	var to = document.getElementById(from).value; 
	var grp = document.getElementById('group').value; 
	if (to != from) {
  		var toopen = addqaddr+'&from=' + from + '&to=' + to + '&grp=' + grp;
		window.location = toopen; 
		}
}

function previewq(formn,loc,qn,docheck,onlychk) {
    var previewqaddr = document.getElementById("address").value;
   var addr = previewqaddr+'&qsetid='+qn;alert(addr);
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
		if (typeof el.type == "undefined") {
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
	window.open('question/question/library-tree?libtree=popup&libs='+curlibs,'libtree','width=400,height='+(.7*screen.height)+',scrollbars=1,resizable=1,status=1,top=20,left='+(screen.width-420));
}
function setlib(libs) {
	document.getElementById("libs").value = libs;
	curlibs = libs;
}
function setlibnames(libn) {
	document.getElementById("libnames").innerHTML = libn;
}

$("#add-question").click(function() {
    $("input[name=some_name]").attr("name", "add");
    document.forms["selq"].submit()
});
$("#add-question-default").click(function() {
    $("input[name=some_name]").attr("name", "addquick");
    document.forms["selq"].submit()
});

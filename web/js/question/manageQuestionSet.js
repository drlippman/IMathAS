$(document).ready(function () {
    manageQuestionSelectedCheckbox();
    $('input[name = "manage-question-header-checked"]:checked').prop('checked', false);
});
function manageQuestionSelectedCheckbox() {
    $('.manage-question-table input[name = "manage-question-header-checked"]').click(function(){
        if($(this).prop("checked") == true){
            $('#manage-question-set-table input:checkbox').each(function () {
                $(this).prop('checked', true);
            })
        }
        else if($(this).prop("checked") == false){
            $('#manage-question-set-table input:checkbox').each(function () {
                $(this).prop('checked', false);
            })
        }
    });
}

function setlib(libs) {
    document.getElementById("libs").value = libs;
    curlibs = libs;
}
function setlibnames(libn) {
    document.getElementById("libnames").innerHTML = libn;
}
function getnextprev(formn,loc) {
    var form = document.getElementById(formn);
    var prevq = 0; var nextq = 0; var found=false;
    var prevl = 0; var nextl = 0;
    for (var e = 0; e < form.elements.length; e++) {
        var el = form.elements[e];
        if (typeof el.type == "undefined") {
            continue;
        }
        if (el.type == 'checkbox' && el.name=='nchecked[]') {
            if (found) {
                nextq = el.value;
                nextl = el.id;
                break;
            } else if (el.id==loc) {
                found = true;
            } else {
                prevq = el.value;
                prevl = el.id;
            }
        }
    }
    return ([[prevl,prevq],[nextl,nextq]]);
}
var chgliblaststate = 0;
function chglibtoggle(rad) {
    var val = rad.value;
    var help = document.getElementById("chglibhelp");
    if (val==0) {
        help.innerHTML = "Select libraries to add these questions to. ";
        if (chgliblaststate==2) {
            initlibtree(false);
        }
    } else if (val==1) {
        help.innerHTML = "Select libraries to add these questions to.  Questions will only be removed from existing libraries if you have the rights to make those changes.";
        if (chgliblaststate==2) {
            initlibtree(false);
        }
    } else if (val==2) {
        help.innerHTML = "Unselect the libraries you want to remove questions from.  The questions will not be deleted; they will be moved to Unassigned if no other library assignments exist.  Questions will only be removed from existing libraries if you have the rights to make those changes.";
        if (chgliblaststate==0 || chgliblaststate==1) {
            initlibtree(true);
        }
    }
    chgliblaststate = val;
}
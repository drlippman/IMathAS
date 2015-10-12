$(document).ready(function () {
//    $.fn.dataTableExt.sErrMode = 'throw';
    manageQuestionSelectedCheckbox();
    $('input[name = "manage-question-header-checked"]:checked').prop('checked', false);
//    $('.manage-question-table').DataTable({ordering:false,
//        "bLengthChange": false
//    });
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
$("#manage-question-chglib").click(function() {

    $('#chglibButton').trigger('click');
});
$("#manage-question-license").click(function() {
    $('#licenseButton').trigger('click');
});
$("#manage-question-chgrights").click(function() {
    $('#chgrightsButton').trigger('click');
});
$("#manage-question-remove").click(function() {
    var questionListArray = createQuestionsList();
    var questionCount = questionListArray.length;
if(!questionCount){
    $("input[name=manage_ques_some_name]").attr("name", "remove");
    document.forms["selform"].submit();
}else{
    event.preventDefault();
    var html ='<div><p>Are you SURE you want to delete these questions from the Question Set.</p></div>';
    html +='<div><p>This will make them unavailable to all users.</p></div>';
    html +='<div><p class="floatleft">If any are currently being used in an assessment, it will mess up that assessment.</p></div>';
    $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
        modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
        width: 'auto', resizable: false,
        closeText: "hide",
        buttons: {
            "Cancel": function () {
                $(this).dialog('destroy').remove();
                $('.form-control-for-question').val("0");
                return false;
            },
            "Confirm": function () {
                $(this).dialog("close");
                $('#removeButton').trigger('click');
                $("input[name=manage_ques_some_name]").attr("name", "remove");
                document.forms["selform"].submit();
            }
        },
        Close: function (event, ui) {
            $(this).remove();
            $('.form-control-for-question').val("0");
            return false;
        },
        open: function(){
            jQuery('.ui-widget-overlay').bind('click',function(){
                jQuery('#dialog').dialog('close');
            })
        }
    });
}
});
$("#manage-question-transfer").click(function() {
    $('#transferButton').trigger('click');
});

function createQuestionsList(){
    var markArray = [];
    $('#manage-question-set-table input[name = "nchecked[]"]:checked').each(function () {
        markArray.push($(this).val());
    });
    return markArray;
}
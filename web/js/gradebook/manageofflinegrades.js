
$(document).ready(function () {
    selectCheckBox();
    deleteGrade();
    selectCheckBoxForGradeName();
});

function selectCheckBoxForGradeName() {
    $('.grade-name-table input[name = "header-checked"]').click(function(){
        if($(this).prop("checked") == true){
            $('.grade-name-table-body input:checkbox').each(function () {
                $(this).prop('checked', true);
            })
        }
        else if($(this).prop("checked") == false){
            $('.grade-name-table-body input:checkbox').each(function () {
                $(this).prop('checked', false);
            })
        }
    });
}

function selectCheckBox() {
    $('.grade-option-table input[name = "header-check-box"]').click(function(){
        if($(this).prop("checked") == true){
            $('.grade-option-table-name input:checkbox').each(function () {
                $(this).prop('checked', true);
            })
        }
        else if($(this).prop("checked") == false){
            $('.grade-option-table-name input:checkbox').each(function () {
                $(this).prop('checked', false);
            })
        }
    });
}

function deleteGrade() {
    $("#mark-delete").click(function (e) {

        var markArray = [];
        $('.grade-name-table-body input[id="Checkbox"]:checked').each(function () {
            markArray.push($(this).val());
        });
        if (markArray.length != 0) {
            var html = '<div><p>Are you SURE you want to delete these offline grade items and the associated student grades?<br/>If you haven\'t already, you might want to back up the gradebook first..</p></div>';
            var cancelUrl = $(this).attr('href');
            e.preventDefault();
            $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
                modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
                width: 'auto', resizable: false, draggable: false,
                closeText: "hide",
                buttons: {
                    "Cancel": function () {
                        $(this).dialog('destroy').remove();
                        $('.grade-name-table-body input[name="msg-check"]:checked').each(function () {
                            $(this).prop('checked', false);
                        });
                        return false;
                    },
                    "Confirm": function () {
                        $('.grade-name-table-body input[name="msg-check"]:checked').each(function () {
                            $(this).prop('checked', false);
                            $(this).closest('tr').remove();
                        });
                        $(this).dialog("close");
                        var readMsg = {checkedMsg: markArray};
                        jQuerySubmit('grade-delete-ajax', readMsg,'gradeDeleteSuccess');
                        return true;
                    }
                },
                close: function (event, ui) {
                    $(this).remove();
                },
                open: function(){
                    hideBodyScroll();
                    jQuery('.ui-widget-overlay').bind('click',function(){
                        jQuery('#dialog').dialog('close');
                    })
                }
            });
        }
        else {

            var msg ="Select atleast one grade to delete";
            CommonPopUp(msg);
        }
    });
}

function gradeDeleteSuccess(response)
{
    location.reload();
}

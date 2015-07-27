
$(document).ready(function () {
    selectCheckBox();
    deleteGrade();
});
function selectCheckBox(){
    $('.check-all').click(function () {
        $('.grade-name-table-body input:checkbox').each(function () {
            $(this).prop('checked', true);
        })
    });

    $('.uncheck-all').click(function () {
        $('.grade-name-table-body input:checkbox').each(function () {
            $(this).prop('checked', false);
        })
    });
}

function deleteGrade() {
    $("#mark-delete").click(function (e) {

        var markArray = [];
        $('.grade-name-table-body input[id="Checkbox"]:checked').each(function () {
            markArray.push($(this).val());
        });
        if (markArray.length != 0) {
            var html = '<div><p>Are you sure? This will delete your message from</p>' +
                '<p>Inbox.</p></div>';
            var cancelUrl = $(this).attr('href');
            e.preventDefault();
            $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
                modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
                width: 'auto', resizable: false,
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
                        //console.log(markArray);
                        jQuerySubmit('grade-delete-ajax', readMsg,'gradeDeleteSuccess');
                        return true;
                    }
                },
                close: function (event, ui) {
                    $(this).remove();
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

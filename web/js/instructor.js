$(document).ready(function(){

});

    $("#mark-as-delete").click(function (e) {

        var html = '<div><p>Are you SURE you want to delete this text item?</p></div>';
        $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
            modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
            width: 'auto', resizable: false,
            closeText: "hide",
            buttons: {
                "Cancel": function () {
                    $(this).dialog('destroy').remove();
                    return false;
                },
                "Confirm": function () {
                    $(this).dialog("close");

                    var readMsg = {id:id};

                    jQuerySubmit('inline-delete-ajax',readMsg,'inlineDeleteSuccess');
                    return true;
                }
            },
            close: function (event, ui) {
                $(this).remove();
            }
        });
    });

function inlineDeleteSuccess(response)
{
    location.reload();
}
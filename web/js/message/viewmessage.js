$(document).ready(function () {
    markAsUnread();
    markAsDelete();
});
function markAsUnread() {
    $('#mark-as-unread').click(function () {
        var cid = $(".msg-id").val();
        var markArray = [cid];
        var readMsg = {checkedMsg: markArray};
        jQuerySubmit('mark-as-unread-ajax', readMsg, 'markAsUnreadSuccess');
    });

}
function markAsUnreadSuccess(response) {
    var result = JSON.parse(response);
    if (result.status == 0) {
        var cid = $(".send-msg").val();
        window.location = "index?cid=" + cid;
    }
}

function markAsDelete() {
    $("#mark-delete").click(function (e) {
        var cid = $(".msg-id").val();
        var markArray = [cid];

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
                    return false;
                },
                "Confirm": function () {

                    $(this).dialog("close");

                    var readMsg = {checkedMsg: markArray};
                    jQuerySubmit('mark-as-delete-ajax', readMsg, 'markAsDeleteSuccess');
                    return true;
                }
            },
            close: function (event, ui) {
                $(this).remove();
            }
        });
    });
}

function markAsDeleteSuccess(response) {
    var result = JSON.parse(response);
    if (result.status == 0) {
        var cid = $(".send-msg").val();
        window.location = "index?cid=" + cid;
    }
}

function showtrimmedcontent(el) {
    if (el.innerHTML.match(/Show/)) {
        document.getElementById("trimmed").style.display="block";
        el.innerHTML = "[Hide trimmed content]";
    } else {
        document.getElementById("trimmed").style.display="none";
        el.innerHTML = "[Show trimmed content]";
    }
}

function changeImage(element, temp, rowId)
{

    var row = {rowId: rowId};
    jQuerySubmit('toggle-tagged-ajax', row,'responseOfUpdateFlag');
}

function responseOfUpdateFlag()
{
    window.location.reload();
}
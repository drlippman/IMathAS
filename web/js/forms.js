function deleteGroup(groupId)
{
    event.preventDefault();
    var html ='<div><p>Are you SURE you want to remove this group?</p></div>';
    $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
        modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
        width: 'auto', resizable: false,
        closeText: "hide",
        buttons: {
            "Cancel": function () {
                $(this).dialog('destroy').remove();
                return false;
            },
            "confirm": function () {
                $(this).dialog("close");
                window.location = "actions?action=delgroup&id="+groupId;
                return true;
            }
        },
        close: function (event, ui) {
            $(this).remove();
        }
    });
}

function deleteLtiUser(ltiUserId)
{
    event.preventDefault();
    var html ='<div><p>Are you SURE you want to remove this Lti User?</p></div>';
    $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
        modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
        width: 'auto', resizable: false,
        closeText: "hide",
        buttons: {
            "Cancel": function () {
                $(this).dialog('destroy').remove();
                return false;
            },
            "confirm": function () {
                $(this).dialog("close");
                window.location = "actions?action=delltidomaincred&id="+ltiUserId;
                return true;
            }
        },
        close: function (event, ui) {
            $(this).remove();
        }
    });
}
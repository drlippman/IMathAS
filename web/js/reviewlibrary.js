
function deleteLibQuestion(source,offset,lib)
{
        var courseId = $('#admin').val();
        var html ='<div><p>Are you SURE you want to delete this question?' ;
        html+='Question will be removed from ALL libraries.</p></div>';
        $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
        modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
        width: 'auto', resizable: false,
        closeText: "hide",
        buttons: {
        "Cancel": function ()
        {
        $(this).dialog('destroy').remove();
        return false;
        },
        "Confirm": function ()
        {
            $(this).dialog("close");
            window.location = "review-library?cid="+courseId+"&source="+source+"&offset="+offset+"&lib="+lib+"&delete=Delete&confirm=confirm";
            return true;
            }
        },
        close: function (event, ui)
        {
            $(this).remove();
        },
        open: function()
        {
            jQuery('.ui-widget-overlay').bind('click',function(){
                jQuery('#dialog').dialog('close');
            })
        }
        });
}

function removeLibQuestion(source,offset,lib)
    {
        var courseId = $('#admin').val();
        var html ='<div><p>Are you SURE you want to remove this question from this library?' ;
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
            window.location = "review-library?cid="+courseId+"&source="+source+"&offset="+offset+"&lib="+lib+"&remove=remove&confirm=confirm";
            return true;
            }
        },
        close: function (event, ui) {
            $(this).remove();
            },
        open: function(){
            jQuery('.ui-widget-overlay').bind('click',function(){
                jQuery('#dialog').dialog('close');
            })
        }
    });
}

function deleteSingle(remove)
{
    var courseId = $('#admin').val();
    alert(courseId);
    var html ='<div><p>Are you SURE you want to remove this question from this library?' ;
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
                window.location = "review-library?cid="+courseId+"&source="+source+"&offset="+offset+"&lib="+lib+"&remove=remove&confirm=confirm";
                return true;
            }
        },
        close: function (event, ui) {
            $(this).remove();
        },
        open: function(){
            jQuery('.ui-widget-overlay').bind('click',function(){
                jQuery('#dialog').dialog('close');
            })
        }
    });
}
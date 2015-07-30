/*
 * Item Ordering on assessment page
 */
var homePath = $('.home-path').val();
function moveitem(from,blk) {
    var to = document.getElementById(blk+'-'+from).value;

    if (to != from) {
        var toopen = homePath+'&block=' + blk + '&from=' + from + '&to=' + to;
        window.location = toopen;
    }
}
// Add new items
function additem(blk,tb) {
    var courseId = $('#courseIdentity').val();
    var type = document.getElementById('addtype'+blk+'-'+tb).value;
    if (tb=='BB' || tb=='LB') { tb = 'b';}
    if (type!='') {
        var toOpen = homePath+'&block='+blk+'&tb='+tb+'&type='+type;
        }
        window.location = toOpen;
}

function deleteItem(id,type,block,courseId) {
    var itemType = type;
    var html = '<div><p>Are you sure? This will delete the '+itemType+' .</p></div>';
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

                jQuerySubmit('delete-items-ajax', {id: id, itemType: type, block: block, courseId: courseId},'responseSuccess');

                $(this).dialog('destroy').remove();
                return true;
            }
        },
        close: function (event, ui) {
            $(this).remove();
        }
    });
}
function responseSuccess(response)
{
    window.location = homePath;
}

function copyItem(id,type,block,courseId) {
    var itemType = type;
    var html = '<div><p>Are you sure? This will copy the '+ itemType+' .</p></div>';
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

                jQuerySubmit('copy-items-ajax', {copyid: id, itemType: type, block: block, courseId: courseId},'copyResponseSuccess');

                $(this).dialog('destroy').remove();
                return true;
            }
        },
        close: function (event, ui) {
            $(this).remove();
        }
    });
}
function copyResponseSuccess(response)
{console.log(response);
    window.location = homePath;
}
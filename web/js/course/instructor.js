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
     if(type == 'Block')
     {
        var message = "Are you SURE you want to delete this Block?</br>";
         message+="<span id='post-type-radio-list'><input type='radio'  name='delete' value='0'>Move all items out of block</br>";
         message+="<input type='radio' checked='checked' name='delete' value='1'>Also Delete all items in block</br></span>";
         var html = '<div><p>'+message+'</p></div>';
         $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
             modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
             width: 'auto', resizable: false,
             closeText: "hide",
             buttons:
             {
                 "Cancel": function ()
                 {
                     $(this).dialog('destroy').remove();
                     return false;
                 },
                 "confirm": function ()
                 {
                     var sel = $("#post-type-radio-list input[type='radio']:checked");
                     var selected = sel.val();
                     jQuerySubmit('delete-items-ajax', {id: id, itemType: type, block: block, courseId: courseId,selected:selected},'responseSuccess');
                     $(this).dialog('destroy').remove();
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
     else
     {
         if(type == 'Forum'){
            var message = "Are you SURE you want to delete this forum and all associated postings?";
        }else if(type == 'Wiki'){
            var message = "Are you SURE you want to delete this Wiki and all associated revisions?";
        }else if(type == 'Assessment'){
            var message = "Are you SURE you want to delete this assessment and all associated student attempts?";
        }else if(type == 'InlineText'){
            var message = "Are you SURE you want to delete this text item?";
        }else if(type == 'LinkedText'){
            var message = "Are you SURE you want to delete this link item?";
        }else if(type == 'Calendar')
         {
             var message = "Are you SURE you want to delete Calendar?";
         }
        var html = '<div><p>'+message+'</p></div>';
        $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
            modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
            width: 'auto', resizable: false,
            closeText: "hide",
            buttons:
            {
                "Cancel": function ()
                {
                    $(this).dialog('destroy').remove();
                    return false;
                },
                "Confirm": function ()
                {
                    jQuerySubmit('delete-items-ajax', {id: id, itemType: type, block: block, courseId: courseId},'responseSuccess');
                    $(this).dialog('destroy').remove();
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
}

function responseSuccess(response)
{console.log(response);
    window.location = homePath;
}

function copyItem(id,type,block,courseId) {
    var itemType = type;
    var html = '<div><p>Are you SURE you want to copy this '+ itemType+'?</p></div>';
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
                jQuerySubmit('copy-items-ajax', {copyid: id, itemType: type, block: block, courseId: courseId},'copyResponseSuccess');
                $(this).dialog('destroy').remove();
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

function copyResponseSuccess(response)
{console.log(response);
    window.location = homePath;
}
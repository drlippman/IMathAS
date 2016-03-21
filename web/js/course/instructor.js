/*
 * Item Ordering on assessment page
 */
var homePath = jQuery('.home-path-course').val();
var webPath = jQuery('.web-path').val();
function moveitem(from,blk) {
    var to = document.getElementById(blk+'-'+from).value;
    if (to != from) {
        var toopen = homePath+'&block=' + blk + '&from=' + from + '&to=' + to;
        window.location = toopen;
    }
}

function getAddItem(blk,tb) {
        var courseId = jQuery('.calender-course-id').val();
        var html = '<div class="">' +
            '<a href="../../assessment/assessment/add-assessment?cid='+ courseId+'&block='+blk+'&tb='+tb+'">' +
            '<div class="assessment itemLink" >' +
            '<img class="icon-center icon-size" id=\"addtype$parent-$tb\" src="../../img/iconAssessment.png">' +
            '<div class="item-name">Assessment</div>'+
            '</div>' +
            '</a>' +

            '<a href="../../course/course/modify-inline-text?cid='+ courseId+'&block='+blk+'&tb='+tb+'">'+'<div class="inline-text itemLink">' +
            '<img class="icon-center icon-size" src="../../img/inlineText.png">' +
            '<div class="item-name">Inline Text</div>'+
            '</div></a>' +

            '<a href="../../course/course/add-link?cid='+ courseId+'&block='+blk+'&tb='+tb+'"><div class="link itemLink">' +
            '<img class="icon-center icon-size" src="../../img/link.png">' +
            '<div class="item-name-small">Link</div>'+
            '</div></a>' +

            '<a href="../../forum/forum/add-forum?cid='+ courseId+'&block='+blk+'&tb='+tb+'"><div class="forum itemLink">' +
            '<img class="icon-center icon-size" src="../../img/iconForum.png">' +
            '<div class="item-name-small">Forum</div>'+
            '</div></a>' +

            '<a href="../../wiki/wiki/add-wiki?cid='+ courseId+'&block='+blk+'&tb='+tb+'"><div class="wiki itemLink">' +
            '<img class="icon-center icon-size" src="../../img/iconWiki.png">' +
            '<div class="item-name-small">Wiki</div>'+
            '</div></a>' +

            '<a href="../../course/course/course?cid='+ courseId+'&block='+blk+'&tb='+tb+'"><div class="calendar-pop-up itemLink">' +
            '<img class="icon-center icon-size" src="../../img/iconCalendar.png">' +
            '<div class="item-name">Calendar</div>'+
            '</div></a>' +

            '<a href="../../block/block/add-block?cid='+ courseId+'&block='+blk+'&tb='+tb+'"><div class="block-item itemLink">' +
            '<img class="icon-center icon-size" src="../../img/block.png">' +
            '<div class="item-name-small block-name-alignment">Block</div>'+
            '</div></a>' +
            '</div>';
        jQuery('<div class="dialog-items close-box" id="dialog"></div>').appendTo('body').html(html).dialog({
            modal: true, message: 'Add An Item', zIndex: 10000, autoOpen: true, width: '410px',height: '419px', title: 'Add an Item...',
            closeText: "show", draggable: false,
            close: function (event, ui) {
                jQuery(this).remove();
            },
            open: function(){
                jQuery('.ui-widget-overlay').bind('click',function(){
                    jQuery('#dialog').dialog('close');
                })
            }
        });
}

function showBodyScroll() {
    jQuery('html, body').css('overflow', 'auto');
}

function hideBodyScroll() {
    jQuery('html, body').css('overflow', 'hidden');
}

function deleteItem(id,type,block,courseId) {
     if(type == 'Block')
     {
        var message = "Are you SURE you want to delete this Block?</br>";
         message+="<span id='post-type-radio-list'><input type='radio'  name='delete' value='0'>Move all items out of block</br>";
         message+="<input type='radio' checked='checked' name='delete' value='1'>Also Delete all items in block</br></span>";
         var html = '<div><p>'+message+'</p></div>';
         jQuery('<div id="dialog"></div>').appendTo('body').html(html).dialog({
             modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
             width: 'auto', resizable: false,
             closeText: "hide",
             buttons:
             {
                 "Cancel": function ()
                 {
                     showBodyScroll();
                     jQuery(this).dialog('destroy').remove();
                     return false;
                 },
                 "Confirm": function ()
                 {
                     showBodyScroll();
                     var sel = jQuery("#post-type-radio-list input[type='radio']:checked");
                     var selected = sel.val();
                     jQuerySubmit('delete-items-ajax', {id:id,itemType:type,block:block,courseId:courseId,selected:selected},'responseSuccess');
                     jQuery(this).dialog('destroy').remove();
                     return true;
                 }
             },
             close: function (event, ui) {
                 showBodyScroll();
                 jQuery(this).remove();
             },
             open: function(){
                 hideBodyScroll();
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
         jQuery('<div id="dialog"></div>').appendTo('body').html(html).dialog({
            modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
            width: 'auto', resizable: false,
            closeText: "hide",
            buttons:
            {
                "Cancel": function ()
                {
                    showBodyScroll();
                    jQuery(this).dialog('destroy').remove();
                    return false;
                },
                "Confirm": function ()
                {
                    showBodyScroll();
                    jQuerySubmit('delete-items-ajax',{id:id, itemType:type, block:block, courseId:courseId},'responseSuccess');
                    jQuery(this).dialog('destroy').remove();
                    return true;
                }
            },
            close: function (event, ui) {
                showBodyScroll();
                jQuery(this).remove();
            },
            open: function(){
                hideBodyScroll();
                jQuery('.ui-widget-overlay').bind('click',function(){
                    jQuery('#dialog').dialog('close');
                })
            }
        });
     }
}

function responseSuccess(response)
{
    //window.location.reload();
    window.location = homePath;
}

function copyItem(id,type,block,courseId) {
    var itemType = type;
    var html = '<div><p>Are you SURE you want to copy this '+ itemType+'?</p></div>';
    jQuery('<div id="dialog"></div>').appendTo('body').html(html).dialog({
        modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
        width: 'auto', resizable: false,
        closeText: "hide",
        buttons: {
            "Cancel": function () {
                jQuery(this).dialog('destroy').remove();
                return false;
            },
            "Confirm": function () {
                jQuerySubmit('copy-items-ajax', {copyid: id, itemType: type, block: block, courseId: courseId},'copyResponseSuccess');
                jQuery(this).dialog('destroy').remove();
                return true;
            }
        },
        close: function (event, ui) {
            jQuery(this).remove();
        },
        open: function(){
            jQuery('.ui-widget-overlay').bind('click',function(){
                jQuery('#dialog').dialog('close');
            })
        }
    });
}

function copyResponseSuccess(response)
{
    //window.location.reload();
    window.location = homePath;

}
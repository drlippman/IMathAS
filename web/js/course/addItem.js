$(document).ready(function()
{
         assessmentLink = $(".assessment-link").val();
         courseId = $(".courseId").val();
    getAddItem();

        $("#plus-icon, #add-item").click(function () {
            $('<div id=""></div>').appendTo('body').html(html).dialog({
                modal: true, zIndex: 10000, autoOpen: true,
                width: '30%', resizable: false,
                closeText: "hide",
                close: function (event, ui) {
                    $(this).remove();
                }
            });
        });
});

function getAddItem123(){
    $('.add-item').on('click', function (evt)
    {
        var block = 0;
        var tb = 't';

        var html = '<div class="">' +
            '<a href="../../assessment/assessment/add-assessment?cid='+ courseId+'">' +
            '<div class="assessment itemLink" >' +
            '<img class="icon-center icon-size" id=\"addtype$parent-$tb\" onclick= \"additem(1, t)" src="../../img/iconAssessment.png">' +
            '<div class="item-name">Assessment</div>'+
            '</div>' +
            '</a>' +

            '<a href="../../course/course/modify-inline-text?cid='+ courseId+'">'+'<div class="inline-text itemLink">' +
            '<img class="icon-center icon-size" src="../../img/inlineText.png">' +
            '<div class="item-name">Inline Text</div>'+
            '</div></a>' +

            '<a href="../../course/course/add-link?cid='+ courseId+'"><div class="link itemLink">' +
            '<img class="icon-center icon-size" src="../../img/link.png">' +
            '<div class="item-name-small">Link</div>'+
            '</div></a>' +

            '<a href="../../forum/forum/add-forum?cid='+ courseId+'"><div class="forum itemLink">' +
            '<img class="icon-center icon-size" src="../../img/iconForum.png">' +
            '<div class="item-name-small">Forum</div>'+
            '</div></a>' +

            '<a href="../../wiki/wiki/add-wiki?cid='+ courseId+'"><div class="wiki itemLink">' +
            '<img class="icon-center icon-size" src="../../img/iconWiki.png">' +
            '<div class="item-name-small">Wiki</div>'+
            '</div></a>' +

            '<a href="../../instructor/instructor/index?cid='+ courseId+'&block='+block+'&tb='+tb+'&type='+"calendar"+'"><div class="calendar-pop-up itemLink">' +
            '<img class="icon-center icon-size" src="../../img/iconCalendar.png">' +
            '<div class="item-name">Calendar</div>'+
            '</div></a>' +

            '<a href="../../block/block/add-block?courseId='+ courseId+'&block='+block+'&tb='+tb+'"><div class="block-item itemLink">' +
            '<img class="icon-center icon-size" src="../../img/block.png">' +
            '<div class="item-name-small block-name-alignment">Block</div>'+
            '</div></a>' +
            '</div>';
        $('<div class="dialog-items close-box" id="dialog"></div>').appendTo('body').html(html).dialog({
            modal: true, message: 'Add An Item', zIndex: 10000, autoOpen: true, width: '410px',height: '419px', title: 'Add an Item...',
            closeText: "show",
            close: function (event, ui) {
                $(this).remove();
            },
            open: function(){
                jQuery('.ui-widget-overlay').bind('click',function(){
                    jQuery('#dialog').dialog('close');
                })
            }
        });
    });
}
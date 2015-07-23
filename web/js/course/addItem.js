$(document).ready(function(){
        var assessmentLink = $(".assessment-link").val();
        var courseId = $(".courseId").val();
        $('.add-item').click(function (evt) {
            var html = '<div class="">' +
                            '<a href="../../assessment/assessment/add-assessment?cid='+ courseId+'"><div class="assessment">' +
                                '<i class="fa fa-bars fa-2x icon-center"></i>' +
                                '<div class="item-name">Assessment</div>'+
                            '</div></a>' +

                            '<a href="../../course/course/modify-inline-text?courseId=' + courseId+'"><div class="inline-text">' +
                                '<i class="fa fa-bars fa-2x icon-center"></i>' +
                                '<div class="item-name">Inline Text</div>'+
                            '</div></a>' +

                            '<a href="../../course/course/add-link?cid='+ courseId+'"><div class="link">' +
                                '<i class="fa fa-bars fa-2x icon-center"></i>' +
                                '<div class="item-name-small">Link</div>'+
                            '</div></a>' +

                            '<a href="../../forum/forum/add-forum?cid='+ courseId+'"><div class="forum">' +
                                '<i class="fa fa-bars fa-2x icon-center"></i>' +
                                '<div class="item-name-small">Forum</div>'+
                            '</div></a>' +

                            '<a href="../../wiki/wiki/add-wiki?courseId='+ courseId+'"><div class="wiki">' +
                                '<i class="fa fa-bars fa-2x icon-center"></i>' +
                                '<div class="item-name-small">Wiki</div>'+
                            '</div></a>' +

                            '<a href="../../course/course/calendar?cid='+ courseId+'"><div class="calendar">' +
                                '<i class="fa fa-calendar-o fa-2x icon-center"></i>' +
                                '<div class="item-name">Calendar</div>'+
                            '</div></a>' +
                        '</div>';
            $('<div class="dialog-items" id="dialog"></div>').appendTo('body').html(html).dialog({
                modal: true, message: 'Add An Item', zIndex: 10000, autoOpen: true,width: '31%',
                closeText: "hide",
                close: function (event, ui) {
                    $(this).remove();
                }
            });
        });

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

    $('.select_button1').click(function() {
        $('.select1').toggle();
    });
});
